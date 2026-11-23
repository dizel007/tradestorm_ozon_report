<?php
require_once("../../_no_git/secret_info.php");
require_once ("../../main_info.php");
require_once("../auth_check.php");
require_once '../../vendor/autoload.php';
require_once "function_report/razbor_type_item.php";
require_once("../ozon_authorization.php");




use App\Ozon\OzonClient;
use App\Ozon\Service\ProductService;
use App\Ozon\Service\FinanceService;


set_time_limit(300);
ini_set('memory_limit', '1024M');
// Вставляем форму для ввода даты
$url_data = urlencode($_GET['data']) ?? '';

print_r($url_data);


[$token , $client_id, $seller_id, $shop_id] = ozon_authorization ($pdo , '../../registration');






/**********************************************************************************************************
 *     ***************    Получаем массив всех транзакций
 *********************************************************************************************************/
// echo  "ПРОШЛИ КОННЕКТ<br>";
// получаем токен запрашиваемого магазина


// echo "<br>";
// print_r ($token);
// echo "<br>";
// print_r ($client_id);
// echo "<br>";
// print_r ($seller_id);
// echo "<br>";
// print_r ($shop_id);


$ozon    = new OzonClient($token, $client_id);
$product = new ProductService($ozon);
$finance = new FinanceService($ozon);

//******************************************************************************************************

    
    $info = $ozon->post("v1/seller/info");

    if ($info && isset($info['company']['name'])) {
        $shop_name = $info['company']['name'] ;
        
    } else {
        $shop_name = 'Не смогли получить данные. Возможно токен истек';
        
  
    }
/******************************************************************************
 *  Отрисовываем форму ввода дат 
 ******************************************************************************/
require_once "start_form.php";

/******************************************************************************
 *  ЗАПРОС фин отчета по проанным товарам  С ОЗОНА (ВСЕ ДАТЫ)
 ******************************************************************************/

//  Запрос товаров проданных в РФ
$dir = '../../_cache/' . 'seller'.$seller_id.'/fake_buers_/';
if (!is_dir($dir)) {
    mkdir($dir, 0775, true);
}
/// Запрос основных продаж 
$file_name_ozon_mainSell = $dir . '/' . $client_id . '_main_data.json';
$finance->getAccrualsByDay( $date_from,  $date_to, $file_name_ozon_mainSell);
$data_by_days = json_decode(file_get_contents($file_name_ozon_mainSell), true);



/******************************************************************************
 * КОНЕЦ запроса данных с озона (ВСЕ ДАТЫ)
 ******************************************************************************/

 /// Разбиваем массив по категориям расходов ///////////////////////////////
foreach ($data_by_days as $one_data) {
    $category[$one_data['accrued_category']] = $one_data['accrued_category'];
    if (isset($one_data['accrued_category'])) {
        $arr_by_accrued_category[$one_data['accrued_category']][] = $one_data;
    } 
}
////////////////////////////////////////////////////////////////////


//////////////////////////////////////////////////////////////////////////////////////////////////////
// берем из JSON файла статьи всех расходов с ID и названием и описанием
//////////////////////////////////////////////////////////////////////////////////////////////////////
$type_fees = json_decode(file_get_contents('spravochnik/types_spend.json'), true);
//////////////////////////////////////////////////////////////////////////////////////////////////////

//////////////////////////////////////////////////////////////////////////////////////////////////////
//  Разбираем массив по категориям затрат
//////////////////////////////////////////////////////////////////////////////////////////////////////
$sum_array =[];

if (isset ($arr_by_accrued_category['POSTING'])) {
    $array_fake_buyers = razbor_POSTING_category($arr_by_accrued_category['POSTING'], $type_fees);
}

// Если нашли массивы с возвратами, то формируем массив для вывода, кто и сколько вернул

if (isset($array_fake_buyers)) {
foreach ($array_fake_buyers as $posting) {
    // ID Покупателя
$id_fake_buyer = explode('-', $posting['unit_number'])[0]; //
$order_number = $id_fake_buyer.'-'.explode('-', $posting['unit_number'])[1]; 
$delivery_shema = $posting['posting']['delivery_schema'] ;
foreach ($posting['posting']['products'] as $products) {

    @$array_by_fake_users[$id_fake_buyer][$posting['unit_number']][$products['sku']] += $products['quantity']; 
    @$array_delivery_shema[$posting['unit_number']] = $delivery_shema;


  }
}
     

}

// echo "<pre>";
// print_r($array_by_fake_users);
// echo "</pre>";
// die();

if (isset($array_by_fake_users)) {
foreach ($array_by_fake_users as $id_fake_buyer=> $orders) {
    foreach ($orders as $order_number=>$items) {
            foreach ($items as $sku=>$quantity) {
        // print_r($sku);
        // echo "<br>";
    insert_fake_buyer_in_db($pdo, $id_fake_buyer, $order_number, $quantity, $sku, $client_id, $array_delivery_shema[$order_number]);
            } 
    }
}
} else {
    die('Нет данных за запрошенный промежуток времени');
}


// echo "<pre>";
// print_r($array_fake_buyers );

//===============================================================================================
// цепляем иностарнные продажи *************************
//===============================================================================================
function insert_fake_buyer_in_db($pdo, $id_fake_user, $order_number, $quantity, $sku, $client_id, $delivery_shema = '') {
    // 1. Проверяем, есть ли уже такой заказ с таким товаром (sku) в базе
    $checkSql = "SELECT COUNT(*) FROM fake_buyers WHERE order_number = :order_number AND sku = :sku";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([
        ':order_number' => (string)$order_number,
        ':sku'          => (string)$sku
    ]);
    
    $exists = $checkStmt->fetchColumn();

    // 2. Если записи нет — добавляем
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
        
        // echo "Запись добавлена: {$order_number} / SKU: {$sku}<br>";
    } else {
        // Запись уже существует, пропущено
        // echo "Запись уже существует: {$order_number} / SKU: {$sku} — пропущено<br>";
    }
}
// die();

require_once "print_info/fake_buyer_table.php";

die();
