<?php

// echo  "ПРОШЛИ КОННЕКТ<br>";

require_once ("../main_info.php");
require_once "../_no_git/secret_info.php";
require_once("../vendor/autoload.php");
require_once ("ozon_pay_function.php");

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
if ($period == 'day') {
    $product_name = 'Оплата за день';
} elseif ($period == 'month') {
    $product_name = 'Оплата за месяц';
} elseif ($period == 'year') {
    $product_name = 'Оплата за год';
} else {
    die ('Не смогли выбрать период для оплаты');
}

    $stmt = $pdo->prepare("SELECT `price_count` FROM `prices` WHERE `price_name` = :price_name");
    $stmt->execute(['price_name' => $period]);
    $arr_summa = $stmt->fetch(PDO::FETCH_ASSOC);
    $summa = $arr_summa['price_count']*100; // сумма для оплаты

if ($summa < 100) {
     die ('Не смогли определить сумму для оплаты');
}

//////////////////////////////////////////
// $summa = 200; ///////////////////////////
//////////////////////////////////////////

//*********************************************************************************************************** */
// вытаскиваем ID магазина, для которого будем проблять период
//*********************************************************************************************************** */
     if (empty($id_shop)) {
        die ('Не смогли найти магазин для оплаты');
     }

    $stmt = $pdo->prepare("SELECT * FROM `shops` WHERE `id` = :id_shop");
    $stmt->execute(['id_shop' => $id_shop]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
// echo "<pre>";
// print_r($shop);


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
// echo "<pre>";
// print_r($email);

//**********************************************************************************************
// Формируем массив для создания заказа в БД
//**********************************************************************************************/

    $subscription_start = date('Y-m-d H:i:s');
    $subscription_end = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $expiresAt = date('Y-m-d\TH:i:s\Z', strtotime('+1 hour')); // для платежа
    $shop_id = $shop['id'];
    $client_id = $shop['client_id'];
    $seller_id = $shop['seller_id'];
    $orderNumber = 'ord-' . date('Ymd') . '-'. $seller_id. '-'. $client_id.'-'. uniqid();
   


//**********************************************************************************************
// Проверяем, есть ли уже активный заказ для этого клиента и магазина
//**********************************************************************************************/

    $checkSql = "SELECT COUNT(*) FROM `paid_orders` 
                 WHERE `client_id` = :client_id 
                 AND `shop_id` = :shop_id 
                 AND `summa` = :summa 
                 AND `updated_at` > NOW() 
                 AND `status` = 'readyForPay'"; // можно добавить условие на статус
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt ->execute([
        'client_id' => $client_id,
        'shop_id'   => $shop_id,
        'summa' => $summa
    ]);

   $activeCount = $checkStmt->fetchColumn();


if ($activeCount > 0) {
    // Есть заказ на оплату – можно либо выдать сообщение, либо обновить существующую
    echo "У данного клиента уже есть активная оплата для этого магазина";
    // при необходимости можно продлить существующую запись, обновив subscription_end
   // переводим на оплату этого заказа сразу

    $SqlQuery = "SELECT * FROM `paid_orders` 
                    WHERE `client_id` = :client_id 
                    AND `shop_id` = :shop_id 
                    AND `updated_at` > NOW() 
                    AND `status` = 'readyForPay'"; // можно добавить условие на статус
    $stmt = $pdo->prepare($SqlQuery);
                $stmt ->execute([
                'client_id' => $client_id,
                'shop_id'   => $shop_id,
            ]);


     $GetPayOrder = $stmt->fetch(PDO::FETCH_ASSOC) ?? '';


       print_r( $GetPayOrder);


     $order_id = $GetPayOrder['id'];
     $orderNumber = $GetPayOrder['order_number'];
     $payLink = $GetPayOrder['link_ozon_pay'];

     if (empty($payLink)) {
          echo ", но отсутствует ссылка на оплату";
    // отменяем этот заказа. Статус cancelled
    
echo "<br>*****************************************************<br>";
    $result_status_update = updateStatusOrderPay ($pdo, $order_id, 'cancelled');
    // echo  $result_status_update;
echo "<br>*******************************************<br>";

     } else {
      
      header('Location: '.$payLink);
      exit; // или return, если внутри функции
     }
}





// die('ggggggggggggggggggggggggg');

// Подготавливаем запрос (исправлены плейсхолдеры)
$insert = $pdo->prepare("INSERT INTO `paid_orders` 
    (`client_id`, `user_email`, `shop_id`,  `summa`, `created_at`, `order_number`, `status`, `updated_at`, `product_name`)
    VALUES (:client_id, :user_email, :shop_id, :summa, :created_at, :order_number, :status, :updated_at, :product_name)");

try {
        $insert->execute([
            'client_id'    => $client_id,
            'user_email'   => $email, // или из сессии
            'shop_id'      => $shop_id,
            'summa'        => $summa,
            'created_at'   => $subscription_start,
            'order_number' => $orderNumber,
            'status'       => 'new', // можно константой: STATUS_NEW = 1
            'updated_at'   => $subscription_end ,
            'product_name' => $product_name,
        ]);


 
    $newId = $pdo->lastInsertId();
    // echo "Заказ на оплату сформирован . ID: $newId";
} catch (PDOException $e) {
    // Логируем ошибку и показываем пользователю общее сообщение
    error_log("Ошибка не смогли сформировать заказа на оплату: " . $e->getMessage());
    echo " Попробуйте позже.";
}

 

//**********************************************************************************************
// Формируем массиа для созжания платежа в озон банке
//**********************************************************************************************/
require_once "make_data_for_ozon_pay.php";
/////////////////////////////////////////////////////////////////////////////////////

// die('<br>JNGHFDRF V OZON');


// $result_query_finance_ozon = ozonFinancePaycreateOrder($send_json) ;

////////////////////////////////////////////////////////////////////////////////////////////////////
$successUrl___ = "../_ozon_pay/pay_ok_ozon.php/?data=".urlencode(encryptData($extId));
header('Location: '.$successUrl___);
////////////////////////////////////////////////////////////////////////////////////////////////////

if (isset($result_query_finance_ozon['order']['payLink'])) {
    $payLink = $result_query_finance_ozon['order']['payLink'];

 // сохраняем ссылку на оплату в заказ и обновляем статус заказа

updateStatusOrderPay ($pdo, $newId, 'readyForPay'); // обновляем статус заказа
updatePayLinkOrderPay ($pdo, $newId, $payLink); // добавляем ссылку на оплату в заказ
    // $update = $pdo->prepare("UPDATE `paid_orders` 
    //                      SET `status` = :status, 
    //                          `link_ozon_pay` = :link_ozon_pay 
    //                      WHERE `id` = :newId");

    //         try {
    //             $update->execute([
    //                 'status'       => 'readyForPay',
    //                 'link_ozon_pay'   => $payLink,
    //                 'newId' => $newId
    //             ]);

    //             if ($update->rowCount() > 0) {
    //                 echo "Статус заказа $orderNumber обновлён.";
    //             } else {
    //                 echo "Заказ с номером $orderNumber не найден.";
    //             }
    //         } catch (PDOException $e) {
    //             error_log("Ошибка: " . $e->getMessage());
    //             echo "Ошибка обновления.";
    //         }


    //  header('Location: '.$payLink);
}


// print_r($result_query_finance_ozon);

die('dddd');

    