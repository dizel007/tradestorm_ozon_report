<?php
require_once 'config.php';
require_once "_no_git/secret_info.php";


//**********************************************************************************************
// Формируем массиа для созжания платежа в озон банке
//**********************************************************************************************/


$ozon_link = 'https://payapi.ozon.ru/v1/createOrder'; // ссылка для метода озон эквайринга
$dateTime = new DateTime();
$dateTime->modify('+15 minutes');
$expiresAt = $dateTime->format('Y-m-d\TH:i:s\Z'); // Дата время окончания оплаты
$extId = $orderData['order_number']; // Уникальный номер оплаты
$amount = ['currencyCode' => '643', 'value' => $orderData['total_amount']*100];
$fingerprint = sprintf("%s%s%s%s%s%s%s%s", $accessKey, $expiresAt, $extId, $fiscalizationType, $paymentAlgorithm, $amount['currencyCode'], $amount['value'], $secretKey);
$requestSign = hash('sha256', $fingerprint);

// Адреса перехода при удачно и неудачной оплате
$successUrl = "https://gardenborders.ru/pay_ok_ozon.php/?order_number=".$extId;
$failUrl = "https://gardenborders.ru/pay/no_order/?order_number=".$extId;

// данные товаров
foreach ($orderData['cart_items'] as $cart_items) {
$array_items[] = array ( 
        "extId"=>    $cart_items['article'] ,
        "name"=>     $cart_items['name'],
        "price"=>    ['currencyCode' => '643', 'value' => $cart_items['price']*100], 
        "quantity"=> $cart_items['quantity'],
        "type"=>     "TYPE_PRODUCT",
        "unitType"=> "UNIT_PIECE",
        "vat"=>      "VAT_NONE"
      );
}




$send_data = array (
"accessKey"=> $accessKey,
"amount"=> $amount,
"enableFiscalization"=> false,
"expiresAt"=> $expiresAt,
"extId"=> $extId ,
"failUrl"=> $failUrl,
// "fiscalizationPhone"=> "79122020299",
"fiscalizationType"=> $fiscalizationType,
"items"=> $array_items,
"mode"=> "MODE_FULL",
"paymentAlgorithm"=> $paymentAlgorithm,
"receiptEmail" =>"dizel007@yandex.ru",
"requestSign" => $requestSign,
"successUrl"=> $successUrl

);

$send_json = json_encode($send_data);


$result_query_finance_ozon = post_with_data_ozon_finance($send_json, $ozon_link) ;
// вносим ссылку на оплату в заказ 
$stmt = $pdo->prepare("UPDATE orders SET link_ozon_finance = :link_ozon_finance WHERE order_number = :extId");
$stmt->execute([
    'link_ozon_finance' => $result_query_finance_ozon['order']['payLink'],
    'extId' => $extId
]);


if (isset($result_query_finance_ozon['order']['payLink'])) {
    $payLink = $result_query_finance_ozon['order']['payLink'];
     header('Location: '.$payLink);
}


// print_r($result_query_finance_ozon);

die();
/* **************************************************************************************************************
*********  Функция обновляния данных на ОЗОН
************************************************************************************************************** */

function post_with_data_ozon_finance($send_data, $ozon_link) {

	$ch = curl_init($ozon_link);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		// 'Api-Key:' . '',
		// 'Client-Id:' . $client_id_ozon, 
		'Content-Type:application/json'
	));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $send_data); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_HEADER, false);
	$res = curl_exec($ch);

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Получаем HTTP-код

	// curl_close($ch);
	
	$res = json_decode($res, true);

    if (intdiv($http_code,100) > 2) {
        echo     '<br>Результат обмена озон (с данными POST): '.$http_code. "<br>";
		echo "<pre>";
        print_r($res);
        }

    return($res);	
    }

    