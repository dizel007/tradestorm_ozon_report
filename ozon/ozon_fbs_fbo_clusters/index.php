
<?php
/**********************************************************************************************************
 *     ***************    Получаем массив всех транзакций
*********************************************************************************************************/

// echo  "ПРОШЛИ КОННЕКТ<br>";
require_once("../../_no_git/secret_info.php");
require_once ("../../main_info.php");
require_once("../../_no_git/secret_info.php");
require_once("../auth_check.php");
require_once '../../vendor/autoload.php';

require_once 'functions_orders_fbo_fbs.php';
require_once 'print_all_orders.php';



use App\Ozon\OzonClient;
use App\Ozon\Service\ProductService;
use App\Ozon\Service\FinanceService;


/**********************************************************************************************************
 *     ***************    Получаем массив всех транзакций
 *********************************************************************************************************/
set_time_limit(300);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $pdo = new PDO('mysql:host=' . $host . ';dbname=' . $db . ';charset=utf8', $user, $password);
    $pdo->exec('SET NAMES utf8');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}


if (isset($_GET['data'])) {
    $url_data = ($_GET['data']);
    $stmt = $pdo->prepare("SELECT * FROM `shops` WHERE `id` = :id");
    $stmt->execute(['id' => decryptData($_GET['data'])]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($shop) {
        $autoLogin = true;
        $token = $shop['ozon_token'];
        $client_id = $shop['client_id'];
        $seller_id = $shop['seller_id'];
        $shop_id = $shop['id'];
    } else {
        session_destroy();
    }
} else {
        header('Location: ../../registration/index.php');
        exit();
}


$ozon    = new OzonClient($token, $client_id);
$product = new ProductService($ozon);
$finance = new FinanceService($ozon);


//===================================================================================================================
// Получаем название кабинета в озоне 
//===================================================================================================================

  $info = $ozon->post("v1/seller/info");

    if ($info && isset($info['company']['name'])) {
        $seller_shop_name_from_ozon  = $info['company']['name'] ;
    } else {
        $seller_shop_name_from_ozon = 'Нет данных';
    }


// Вставляем форму для ввода даты
require_once "start_form.php";

  
//// Проверяем и есть нет папки, то создаем ее, для каждого пользователя своя папка в !cache
 
$dir_for_cache = "../../_cache/seller_".$seller_id."/ozon_fbs_fbo_clusters/";
if (!is_dir($dir_for_cache)) {
    if (mkdir($dir_for_cache, 0755, true)) {
        echo "Папка создана.";
    } else {
        echo "Не удалось создать папку.";
    }
}

$filename_fbs_orders = $dir_for_cache."json_fbs_Orders_".$client_id.".json";
$filename_fbo_orders = $dir_for_cache."json_fbo_Orders_".$client_id.".json";



/***************************************************************************************************************
 * Заказы для ООО Анм
 **************************************************************************************************************/

//==============================================================
// Получаем массив ФБС заказов 
//==============================================================
// $orders_FBS_ooo = query_FBS_orders_from_ozon ($token, $client_id, $date_from, $date_to);




$send_data_array = array("dir"=> "ASC",
                 "filter"=> array("since" => $date_from."T00:00:00.000Z",
                                 "to" =>  $date_to."T23:59:59.000Z",

                                "statuses" => ['awaiting_registration', 'acceptance_in_progress', 'awaiting_packaging', 'awaiting_deliver',
                                'delivering', 'delivered' , 'driver_pickup' , 'cancelled', 'awaiting_approve',
                                'arbitration' , 'client_arbitration', 'not_accepted' ],


                                                
                                 ),
                  "limit" =>  100,
                  "cursor" =>  '',
                  "sort_dir" => "ASC",
                  "translit" => false,
                  "with" => array(
                  "analytics_data" => true,
                  "legal_info" => true,
                  "financial_data" => true
                  )
);

// запустили запрос на озона
do {
    $res = $ozon->post('v4/posting/fbs/list', $send_data_array);
    $summ_data[]=$res['postings']; // положили первый массив и смотрим нужно ли еще искать
    $send_data_array['cursor'] = $res['cursor'];
} while ($res['has_next']);

// делаем сплошной массив с заказами
foreach ($summ_data as $oneQueryData) {
    foreach ($oneQueryData as $oneData) {
        $orders_FBS_ooo[]=$oneData;
    }
}

if (!isset($orders_FBS_ooo)) {
   $orders_FBS_ooo = [];
}
unset($summ_data);
// сохраняем файл на диске, чтобы потом детелаьно ковырятся в нем и не запрашивать новый
file_put_contents($filename_fbs_orders, json_encode($orders_FBS_ooo, JSON_UNESCAPED_UNICODE));
$orders_FBS_article_ooo = [];
$orders_FBS_article_ooo = make_array_with_all_FBS_FBO_orders ($orders_FBS_ooo);
//==============================================================
// Получаем массив ФБO заказов 
//==============================================================
$send_data_array = array("dir"=> "ASC",
                 "filter"=> array("since" => $date_from."T00:00:00.000Z",
                                "status" => "",
                                   "to" =>  $date_to."T23:59:59.000Z"
                ),
                     "limit" =>  100,
                     "cursor" =>  '',
                     "offset" =>  0,
                     "sort_dir" => "ASC",
                     "translit" => false,
                     "with" => array(
                     "analytics_data" => true,
                     "legal_info" => true,
                     "financial_data" => true
)
);

// запустили запрос на озона
do {
     
    $res = $ozon->post('v3/posting/fbo/list', $send_data_array);

    $summ_data[]=$res['postings']; // положили первый массив и смотрим нужно ли еще искать
    $send_data_array['cursor'] = $res['cursor'];
} while ($res['has_next']);

// делаем сплошной массив с заказами
foreach ($summ_data as $oneQueryData) {
    foreach ($oneQueryData as $oneData) {
        $orders_FBO_ooo[]=$oneData;
    }
}

if (!isset($orders_FBO_ooo)) {
   $orders_FBO_ooo=[];
}

unset($summ_data);
// сохраняем файл на диске, чтобы потом детелаьно ковырятся в нем и не запрашивать новый
file_put_contents($filename_fbo_orders,json_encode($orders_FBO_ooo, JSON_UNESCAPED_UNICODE));
$orders_FBO_article_ooo = [];
$orders_FBO_article_ooo = make_array_with_all_FBS_FBO_orders ($orders_FBO_ooo);

//==============================================================
// формируем перечень артикулов которые были проданы
//==============================================================
 if (isset ($orders_FBS_article_ooo)) {
foreach ($orders_FBS_article_ooo as $key=>$z) {
   $art_ar_ooo[$key] = $key;
 }
 }
 if (isset ($orders_FBO_article_ooo)) {
foreach ($orders_FBO_article_ooo as $key=>$z) {
   $art_ar_ooo[$key] = $key;
}
 }
// Сортировка по возрастанию с сохранением ключей
if (isset($art_ar_ooo)) {
asort($art_ar_ooo);
} else {
    echo "Нет данных для выдачи";
    die();
}
print_sum_table($shop_id, $seller_shop_name_from_ozon ,$art_ar_ooo, $orders_FBS_article_ooo, $orders_FBO_article_ooo);
