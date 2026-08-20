<?php
// require_once 'config.php';
require_once "../_no_git/secret_info.php";


//**********************************************************************************************
// Формируем массиа для созжания платежа в озон банке
//**********************************************************************************************/


; // ссылка для метода озон эквайринга
$dateTime = new DateTime();
$dateTime->modify('+15 minutes');
$expiresAt = $dateTime->format('Y-m-d\TH:i:s\Z'); // Дата время окончания оплаты
$extId = '111a1a1a1sцуцуцd12r'; // Уникальный номер оплаты

$month_price = 1.99;

$amount = ['currencyCode' => '643', 'value' => $month_price*100];
$fingerprint = sprintf("%s%s%s%s%s%s%s%s", $accessKey, $expiresAt, $extId, $fiscalizationType, $paymentAlgorithm, $amount['currencyCode'], $amount['value'], $secretKey);
$requestSign = hash('sha256', $fingerprint);

// Адреса перехода при удачно и неудачной оплате
$redirectUrl = "https://tradestorm.ru/_ozon_pay/pay_ok_ozon.php/?order_number_edirectUrlr=".$extId;
$successUrl = "https://tradestorm.ru/_ozon_pay/pay_ok_ozon.php/?order_number_successUrl22=".$extId;

$failUrl    = "https://tradestorm.ru/_ozon_pay/pay_false_ozon.php?order_number=".$extId;



$order = array(
"amount"=> $amount,
"enableFiscalization" => false,
"expiresAt" =>   $expiresAt,  //Дата истечения платёжной ссылки заказа.

"failUrl" => $failUrl,
"items"=> array (
    "name" => "Оплата за месяц",
    "price"=> $amount,
    "quantity" => 1,
    "type" => 'TYPE_SERVICE', // услуга
    "vat" => 'VAT_NONE'
    ),
 "paymentAlgorithm" => $paymentAlgorithm,
 "successUrl" => $successUrl,
);



$send_data = array (
// *************** required
"accessKey"=> $accessKey,
"amount"=> $amount,
"extId" => '111a1a1a1sцуцуцd12r',
"order" => $order,

"payType" => "SBP",
"redirectUrl" => $redirectUrl,
"requestSign" => $requestSign,


// *************** END  required



// "expiresAt"=> $expiresAt,
// "extId"=> $extId ,
// "failUrl"=> $failUrl,
// "fiscalizationPhone"=> "79122020299",
"fiscalizationType"=> $fiscalizationType,

"mode"=> "MODE_FULL",
"paymentAlgorithm"=> $paymentAlgorithm,
"receiptEmail" =>"dizel007@yandex.ru",


);


echo  "<pre>";

print_r($send_data);
$send_json = json_encode($send_data);


$result_query_finance_ozon = ozonFinancePayCreateSbpPayment($send_json) ;
// вносим ссылку на оплату в заказ 
// $stmt = $pdo->prepare("UPDATE orders SET link_ozon_finance = :link_ozon_finance WHERE order_number = :extId");
// $stmt->execute([
//     'link_ozon_finance' => $result_query_finance_ozon['order']['payLink'],
//     'extId' => $extId
// ]);




if (isset($result_query_finance_ozon['order']['payLink'])) {
    $payLink = $result_query_finance_ozon['order']['payLink'];
     header('Location: '.$payLink);
}


// print_r($result_query_finance_ozon);

die();
/* **************************************************************************************************************
*********  Функция обновляния данных на ОЗОН
************************************************************************************************************** */

function ozonFinancePayCreateSbpPayment($send_data) {
$ozon_link = 'https://payapi.ozon.ru/v1/createPayment';
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

    