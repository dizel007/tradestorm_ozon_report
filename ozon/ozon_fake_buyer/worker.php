<?php
// В PHP операторы use должны быть в самом начале файла
use App\Ozon\OzonClient;
use App\Ozon\Service\ProductService;
use App\Ozon\Service\FinanceService;

chdir(__DIR__);

require_once "../../main_info.php";
require_once '../../vendor/autoload.php';
require_once "function_report/razbor_type_item.php";

// =========================================================================
// ПОЛУЧАЕМ ПАРАМЕТРЫ
// =========================================================================
if (!empty($_SERVER['TOKEN'])) {
    // Данные пришли из start.php (через переменные окружения на VDS)
    $token     = $_SERVER['TOKEN'];
    $client_id = $_SERVER['CLIENT_ID'] ?? '';
    $seller_id = $_SERVER['SELLER_ID'] ?? '';
    $shop_id   = $_SERVER['SHOP_ID'] ?? '';
} elseif (!empty($argv[1])) {
    // Данные пришли из консоли (Windows или ручной запуск)
    $token     = $argv[1];
    $client_id = $argv[2] ?? '';
    $seller_id = $argv[3] ?? '';
    $shop_id   = $argv[4] ?? '';
} else {
    die("Ошибка: не переданы параметры token/client_id/seller_id/shop_id\n");
}

// Отправка письма о старте (если функция не настроена на VDS, она просто не сработает)
$text = 'Зашли в воркер' . '->client_id:'. $client_id. ' seller_id:'. $seller_id. ' shop_id:'. $shop_id;
@send_many_emails('dizel007@yandex.ru', SITE_NAME, $text , $mail_for_send_letter, $mail_pass);

// Подключение к БД
try {  
    $pdo = new PDO('mysql:host='.$host.';dbname='.$db.';charset=utf8', $user, $password);
    $pdo->exec('SET NAMES utf8');
} catch (PDOException $e) {
    die("Has errors: " . $e->getMessage());
}

set_time_limit(300);
ini_set('memory_limit', '1024M');

// Инициализация клиента Ozon
$ozon    = new OzonClient($token, $client_id);
$product = new ProductService($ozon);
$finance = new FinanceService($ozon);

// Справочник типов затрат
$type_fees = json_decode(file_get_contents('spravochnik/types_spend.json'), true);

// Создаем каталог и файл для работы 
$dir = '../../_cache/' . 'seller_'.$seller_id.'/fake_buers/';
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}
$file_name_ozon_mainSell = $dir . '/' . $client_id . '_main_data.json';

// Получаем последнюю дату опроса
$checkUserDate = "SELECT last_date_query_fake_returns FROM shops WHERE seller_id = :seller_id AND id = :shop_id";
$checkStmt = $pdo->prepare($checkUserDate);
$checkStmt->execute([
    ':seller_id' => (string)$seller_id,
    ':shop_id'   => (string)$shop_id
]);
$lastDate = $checkStmt->fetchColumn();

if (empty($lastDate) || $lastDate === false || $lastDate === '0000-00-00') {
    // Если даты нет — берем сегодня минус 11 месяцев
    $lastDate = date('Y-m-d', strtotime('-364 days'));
} else {
    $lastDate = date('Y-m-d', strtotime($lastDate));
}

$date_from = $lastDate; 
$today = date('Y-m-d');

// Цикл по 30 дней
while ($date_from < $today) {
    
    // Прибавляем 29 дней, чтобы период включительно был 30 дней
    $date_to = date('Y-m-d', strtotime("$date_from +29 days"));
    
    // Если вылезли за сегодня — обрезаем по сегодня
    if ($date_to > $today) {
        $date_to = $today;
    }

    // ЗАПРОС К ОЗОН
    $finance->getAccrualsByDay($date_from, $date_to, $file_name_ozon_mainSell);
    $data_by_days = json_decode(file_get_contents($file_name_ozon_mainSell), true);

    // Сброс массивов перед новым периодом, чтобы не тащить мусор
    $arr_by_accrued_category = [];
    $array_by_fake_users     = [];
    $array_delivery_shema    = [];
    $array_fake_buyers       = [];

    // Разбиваем массив по категориям расходов
    if (is_array($data_by_days)) {
        foreach ($data_by_days as $one_data) {
            if (isset($one_data['accrued_category'])) {
                $arr_by_accrued_category[$one_data['accrued_category']][] = $one_data;
            }
        }
    }

    // Разбираем массив POSTING
    if (isset($arr_by_accrued_category['POSTING'])) {
        $array_fake_buyers = razbor_POSTING_category($arr_by_accrued_category['POSTING'], $type_fees);
    }

    // Формируем данные
    if (isset($array_fake_buyers) && is_array($array_fake_buyers)) {
        foreach ($array_fake_buyers as $posting) {
            $id_fake_buyer  = explode('-', $posting['unit_number'])[0]; 
            $order_number   = $id_fake_buyer.'-'.explode('-', $posting['unit_number'])[1]; 
            $delivery_shema = $posting['posting']['delivery_schema'] ?? '';
            
            foreach ($posting['posting']['products'] as $products) {
                @$array_by_fake_users[$id_fake_buyer][$posting['unit_number']][$products['sku']] += $products['quantity']; 
                @$array_delivery_shema[$posting['unit_number']] = $delivery_shema;
            }
        }
    }

    // Сохраняем в БД
    if (isset($array_by_fake_users) && is_array($array_by_fake_users)) {
        foreach ($array_by_fake_users as $id_fake_buyer => $orders) {
            foreach ($orders as $order_number => $items) {
                foreach ($items as $sku => $quantity) {
                    insert_fake_buyer_in_db($pdo, $id_fake_buyer, $order_number, $quantity, $sku, $client_id, $array_delivery_shema[$order_number] ?? '');
                } 
            }
        }
    }

    // ВАЖНО: прибавляем 1 день, чтобы следующий период начался со следующего дня
    // $date_from = date('Y-m-d', strtotime("$date_to +1 day"));
    $date_from = $date_to;

}

// Обновляем дату в БД
$updateSql = "UPDATE shops 
              SET last_date_query_fake_returns = :last_date 
              WHERE seller_id = :seller_id AND id = :shop_id";
$updateStmt = $pdo->prepare($updateSql);
try {
    $updateStmt->execute([
        ':last_date' => $today,
        ':seller_id' => $seller_id,
        ':shop_id'   => $shop_id
    ]);
$lastDate; 
    $text = 'Обновили дату при формировании БД возвратов seller_id='.$seller_id.' shop_id='.$shop_id.' lastDate='.$lastDate;
    @send_many_emails('dizel007@yandex.ru', SITE_NAME,  $text , $mail_for_send_letter, $mail_pass);

} catch (PDOException $e) {
    @send_many_emails('dizel007@yandex.ru', SITE_NAME, 'Не обновили дату при формировании БД возвратов', $mail_for_send_letter, $mail_pass);
}


// =========================================================================
// ФУНКЦИЯ ЗАПИСИ В БД
// =========================================================================
function insert_fake_buyer_in_db($pdo, $id_fake_user, $order_number, $quantity, $sku, $client_id, $delivery_shema = '') {
    $checkSql = "SELECT COUNT(*) FROM fake_buyers WHERE order_number = :order_number AND sku = :sku";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([
        ':order_number' => (string)$order_number,
        ':sku'          => (string)$sku
    ]);
    
    $exists = $checkStmt->fetchColumn();

    if ($exists == 0) {
        $insertSql = "INSERT INTO fake_buyers (id_fake_user, order_number, quantity, sku, client_id, delivery_shema) 
                      VALUES (:id_fake_user, :order_number, :quantity, :sku, :client_id, :delivery_shema)";
        $insertStmt = $pdo->prepare($insertSql);
        $insertStmt->execute([
            ':id_fake_user'    => $id_fake_user,
            ':order_number'    => (string)$order_number,
            ':quantity'        => $quantity,
            ':sku'             => (string)$sku,
            ':client_id'       => $client_id,
            ':delivery_shema'  => (string)$delivery_shema
        ]);
    }
}