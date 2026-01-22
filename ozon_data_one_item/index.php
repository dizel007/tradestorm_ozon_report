<?php
$offset = "../";
require_once $offset . "main_info.php";
require_once $offset . "mp_functions/ozon_api_functions.php";

/*****************************************************************
 * Вычитываем информацию о товаре - цену / скидки и прочую хрень
 **************************************************************/
// echo "Вычитываем информацию о товаре - цену / скидки и прочую хрень"."<br>";

      try {  
        $pdo = new PDO('mysql:host='.$host.';dbname='.$db.';charset=utf8', $user, $password);
        $pdo->exec('SET NAMES utf8');
        } catch (PDOException $e) {
          print "Has errors: " . $e->getMessage();  die();
        }


$queryString = $_SERVER['QUERY_STRING'] ?? '';

// Разобрать вручную
parse_str($queryString, $params);

// находим ID клиента
if (isset($params['clt'])) {
    $article = $params['art'];
    $secret_client_id = $params['clt'];

    $sth = $pdo->prepare("SELECT * from `tokens` WHERE id_clt_base64 =:id_clt_base64");
    $sth->execute(array("id_clt_base64" => $secret_client_id));
    $arr_tokens = $sth->fetch(PDO::FETCH_ASSOC);
    
   
    $client_id = $arr_tokens['id_client'];
    $token = $arr_tokens['ozon_token'];
    $secret_client_id = $arr_tokens['id_clt_base64'];
     
} else {
    die('Не нашли файл с данными');
}

// находим время доставки за последнюю неделю 
$ozon_dop_url = "v1/analytics/average-delivery-time/summary";
$send_data = '';
$average_delivery_time = post_with_data_ozon($token, $client_id, $send_data, $ozon_dop_url ) ;

// берем информацию по данному артикулу

$ozon_dop_url = "v5/product/info/prices";
$send_data =  array(
"cursor" => "",
"filter" => array (
                   "offer_id"=> array ("$article" ),
                //    "visibility" => "ALL",
                //    "visibility" => "IN_SALE"
                   ),
"limit" => 1000
);
$send_data = json_encode($send_data);

$data = post_with_data_ozon($token, $client_id, $send_data, $ozon_dop_url ) ;

// echo "<pre>";
// print_r($data);
// die();

require_once "index_y.php";