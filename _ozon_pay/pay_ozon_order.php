<?php

// echo  "ПРОШЛИ КОННЕКТ<br>";

require_once ("../main_info.php");
require_once "../_no_git/secret_info.php";
require_once("../vendor/autoload.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Проверка, авторизован ли уже пользователь
$user = null;
$autoLogin = false;

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM `sellers` WHERE `id` = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $autoLogin = true;
    } else {
        session_destroy();
    }
}

if (!$autoLogin && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = $pdo->prepare("SELECT * FROM `sellers` WHERE `remember_token` = :token AND `remember_token_expiry` > NOW()");
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $autoLogin = true;
        setRememberToken($pdo, $user['id']);
    } else {
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
}

if ($autoLogin && $user) {
    // Уже авторизован, перенаправляем на dashboard
    $id_shop = (decryptData($_GET['data']) ?? '');
    $period = trim($_GET['period'] ?? '');
}


//*********************************************************************************************************** */
// запрашиваем ценe на продление за выбранный период
//*********************************************************************************************************** */
     if (empty($period)) {
        die ('Не смогли определить период для оплаты');
     }

    $stmt = $pdo->prepare("SELECT `price_count` FROM `prices` WHERE `price_name` = :price_name");
    $stmt->execute(['price_name' => $period]);
    $arr_summa = $stmt->fetch(PDO::FETCH_ASSOC);
    $summa = $arr_summa['price_count']*100; // сумма для оплаты

if ($summa < 50) {
     die ('Не смогли определить сумму для оплаты');
}


//*********************************************************************************************************** */
// dвытаскиваем ID магазина, для которого будем проблять период
//*********************************************************************************************************** */
     if (empty($id_shop)) {
        die ('Не смогли найти магазин для оплаты');
     }

    $stmt = $pdo->prepare("SELECT * FROM `shops` WHERE `id` = :id_shop");
    $stmt->execute(['id_shop' => $id_shop]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($shop);


//*********************************************************************************************************** */
// вытаскиваем почту плательщика 
//*********************************************************************************************************** */
$seller_id = $shop['seller_id'];
     if (empty($seller_id)) {
        die ('Не смогли найти почту плательщика');
     }

    $stmt = $pdo->prepare("SELECT email FROM `sellers` WHERE `id` = :seller_id");
    $stmt->execute(['seller_id' => $seller_id]);
    $email_array = $stmt->fetch(PDO::FETCH_ASSOC);

$email = $email_array['email'];
echo "<pre>";
print_r($email);






//**********************************************************************************************
// Формируем массив для создания заказа в БД
//**********************************************************************************************/

    $subscription_start = date('Y-m-d H:i:s');
    $subscription_end = date('Y-m-d H:i:s', strtotime('+7 days'));
    $product_name = 'paid for: '. $period;
    $orderNumber = 'ord-' . date('Ymd') . '-'. $shop['seller_id']. '-'. $shop['client_id'].'-'. uniqid();
   


// Подготавливаем запрос (исправлены плейсхолдеры)
$insert = $pdo->prepare("INSERT INTO `paid_orders` 
    (`client_id`, `user_email`, `shop_id`,  `summa`, `created_at`, `order_number`, `status`, `updated_at`, `product_name`)
    VALUES (:client_id, :user_email, :shop_id, :summa, :created_at, :order_number, :status, :updated_at, :product_name)");

// Выполняем с корректными данными
$insert->execute([
    'client_id'    => 1,
    'user_email'   => 'user@example.com', // или из сессии
    'shop_id'      => 1,
    'summa'      => $summa,
    'created_at'   =>   $subscription_start,
    'order_number' => $orderNumber,
    'status'       => 1, // можно константой: STATUS_NEW = 1
    'updated_at'   =>  $subscription_end ,
    'product_name' => $product_name,
]);




    // $subscription_start = date('Y-m-d H:i:s');
    // $subscription_end = date('Y-m-d H:i:s', strtotime('+7 days'));
    // $subscription_status = 'trial';
    // $secret_token = base64_encode($token);

    // $insertShop = $pdo->prepare("INSERT INTO `shops` 
    //     (`seller_id`, `client_id`, `ozon_token`, `id_clt_base64`, `date`, `subscription_start`, `subscription_end`, `subscription_status`)
    //     VALUES (:seller_id, :client_id, :ozon_token, :id_clt_base64, :date, :start, :end, :status)");
    // try {
    //     $insertShop->execute([
    //         'seller_id' => $userId,
    //         'client_id' => $client_id,
    //         'ozon_token' => $token,
    //         'id_clt_base64' => $secret_token,
    //         'date' => date('Y-m-d H:i:s'),
    //         'start' => $subscription_start,
    //         'end' => $subscription_end,
    //         'status' => $subscription_status
    //     ]);
    // } catch (PDOException $e) {
    //     if ($e->errorInfo[1] == 1062) {
    //         $error = "Этот магазин (Client ID) уже добавлен.";
    //     } else {
    //         $error = "Ошибка добавления магазина: " . $e->getMessage();
    //     }
    //     if (isset($userId)) {
    //         $pdo->prepare("DELETE FROM `sellers` WHERE `id` = ?")->execute([$userId]);
    //     }
    // }

//**********************************************************************************************
// Формируем массиа для созжания платежа в озон банке
//**********************************************************************************************/

// ссылка для метода озон эквайринга
$dateTime = new DateTime();
$dateTime->modify('+15 minutes');
$expiresAt = $dateTime->format('Y-m-d\TH:i:s\Z'); // Дата время окончания оплаты
$extId = '22665986546464654213'; // Уникальный номер оплаты
$amount = ['currencyCode' => '643', 'value' => 1*100];
$fingerprint = sprintf("%s%s%s%s%s%s%s%s", $accessKey, $expiresAt, $extId, $fiscalizationType, $paymentAlgorithm, $amount['currencyCode'], $amount['value'], $secretKey);
$requestSign = hash('sha256', $fingerprint);

// Адреса перехода при удачно и неудачной оплате
$successUrl = "https://tradestorm.ru/_ozon_pay/pay_ok_ozon.php/?order_number=".$extId;
$failUrl    = "https://tradestorm.ru/_ozon_pay/pay_false_ozon.php?order_number=".$extId;

// данные товаров

$array_items[] = array ( 
        "extId"=>    '1 month' ,
        "name"=>     'оплата за 1 месяц',
        "price"=>    ['currencyCode' => '643', 'value' => 1*100], 
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
"extId"=> $extId ,
"failUrl"=> $failUrl,
"fiscalizationPhone"=> "79122020299",
"fiscalizationType"=> $fiscalizationType,
"items"=> $array_items,
"mode"=> "MODE_FULL",
"paymentAlgorithm"=> $paymentAlgorithm,
"receiptEmail" =>"dizel007@yandex.ru",
"requestSign" => $requestSign,
"successUrl"=> $successUrl

);

$send_json = json_encode($send_data);

die('<br>JNGHFDRF V OZON');
$result_query_finance_ozon = ozonFinancePaycreateOrder($send_json) ;
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

function ozonFinancePaycreateOrder($send_data) {
$ozon_link = 'https://payapi.ozon.ru/v1/createOrder';
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

    