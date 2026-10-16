<?php

$extId = $orderNumber; // Уникальный номер оплаты
$amount = ['currencyCode' => '643', 'value' => $summa];
$fingerprint = sprintf("%s%s%s%s%s%s%s%s", $accessKey, $expiresAt, $extId, $fiscalizationType, $paymentAlgorithm, $amount['currencyCode'], $amount['value'], $secretKey);
$requestSign = hash('sha256', $fingerprint);

// Адреса перехода при удачно и неудачной оплате
$successUrl = "https://tradestorm.ru/_ozon_pay/pay_ok_ozon.php/?data=".urlencode(encryptData($extId));
$failUrl    = "https://tradestorm.ru/_ozon_pay/pay_false_ozon.php?data=".urlencode(encryptData($extId));


// echo "<br>$successUrl<br>";
// данные товаров

$array_items[] = array ( 
        "extId"=>    'сервис аналитики' ,
        "name"=>     $product_name,
        "price"=>    $amount , 
        "quantity"=> 1,
        "type"=>     "TYPE_SERVICE",
        "unitType"=> "UNIT_PIECE",
        "vat"=>      "VAT_NONE"
      );





$send_data = array (
        "accessKey"=> $accessKey,
        "amount"=> $amount,
        "enableFiscalization"=> false,
        "expiresAt"=> $expiresAt,
        "extId"=> $extId,
        "fiscalizationPhone"=> "79122020299",
        "fiscalizationType"=> $fiscalizationType,
        "items"=> $array_items,
        "mode"=> "MODE_FULL",
        "paymentAlgorithm"=> $paymentAlgorithm,
        "receiptEmail" =>"dizel007@yandex.ru",
        "requestSign" => $requestSign,
        "failUrl"     => $failUrl,
        "successUrl"  => $successUrl

);

$send_json = json_encode($send_data);