<?php
// echo  "ПРОШЛИ КОННЕКТ<br>";

require_once("../../_no_git/secret_info.php");
require_once ("../../main_info.php");
require_once("../../_no_git/secret_info.php");
require_once("../auth_check.php");
require_once '../../vendor/autoload.php';


require_once("../ozon_authorization.php");


use App\Ozon\OzonClient;
use App\Ozon\Service\ProductService;
use App\Ozon\Service\FinanceService;

// echo date('H:m:s')."<br>";
// echo $_GET['data']."<br>";

// die();
/**********************************************************************************************************
 *     ***************    Получаем массив всех транзакций
 *********************************************************************************************************/
set_time_limit(300);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


 
$url_data = ($_GET['data']);
[$token , $client_id, $seller_id, $shop_id] = ozon_authorization ($pdo , '../../registration');


require_once "start_form.php";


$cluster_from = $_GET['ClusterFrom'] ?? '';
$cluster_to   = $_GET['ClusterTo'] ?? '';

// Если магазин не выбран — показываем пустую форму
if (empty($shop_id)) {
    render_form([], $cluster_from, $cluster_to);
    exit;
}

// Проверка наличия токена
if (!isset($token)) {
    echo "<p style='text-align:center; color:red;'>Ошибка: Не найден магазин '{}'.</p>";
    render_form([], $cluster_from, $cluster_to);
    exit;
}


$ozon = new OzonClient($token, $client_id);

$arr_good_tovar = [];

try {
    $data = [
        "filter" => [
            "offer_id"   => [],
            "product_id" => [],
            "skus"       => [],
            "visibility" => "ALL"
        ],
        "limit" => 100
    ];

    $tovary = $ozon->post('v3/product/list', $data);

    if (!empty($tovary['result']['items'])) {
        $array_sku = [];
        foreach ($tovary['result']['items'] as $item) {
            $arr_good_tovar[$item['offer_id']]['sku'] = $item['sku'];
            $array_sku[] = $item['sku'];
        }

        $data2 = [
            "filter" => [
                "offer_id"   => [],
                "product_id" => [],
                "sku"        => $array_sku,
                "visibility" => "ALL"
            ],
            "limit"    => 100,
            "sort_dir" => "ASC"
        ];

        $harak2 = $ozon->post('v4/product/info/attributes', $data2);

        if (!empty($harak2['result'])) {
            foreach ($harak2['result'] as $item) {
                $arr_good_tovar[$item['offer_id']]['value']  = round($item['height'] * $item['depth'] * $item['width'] / 1000000, 2);
                $arr_good_tovar[$item['offer_id']]['weight'] = $item['weight'];
            }
        }
    }
} catch (Throwable $e) {
    echo "<p style='text-align:center; color:red;'>Ошибка API Ozon: " . htmlspecialchars($e->getMessage()) . "</p>";
}



render_form($arr_good_tovar, $cluster_from, $cluster_to);