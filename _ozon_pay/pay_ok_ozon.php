<?php
// require_once "config.php";
$mail_path = ""; 

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
    $order_number = (decryptData($_GET['data']) ?? '');
}


// print_r($order_number);

// echo  "<pre>";
// достаем номер заказа, который оплачен



/************************************************************************************************/
// Назодим наш заказ
if (!empty($order_number)) {
    $stmt = $pdo->prepare("SELECT * FROM `paid_orders` WHERE `order_number` = :order_number");
    $stmt->execute(['order_number' => $order_number]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
}


// echo "<pre>";
// print_r($order);
// die();

// получаем на сколько дней нужно продлить дату следующей оплаты
    $order_summa = $order['summa']/100;

    $stmt = $pdo->prepare("SELECT `update_period` FROM `prices` WHERE `price_count` = :order_summa");
    $stmt->execute(['order_summa' => $order_summa]);
    $update_period = $stmt->fetch(PDO::FETCH_ASSOC);

/// доставем дату окончания срока действия оплаты по магазину 
    $stmt = $pdo->prepare("SELECT `subscription_end` FROM `shops` WHERE `id` = :id");
    $stmt->execute(['id' => $order['shop_id']]);
    $subscription_end = $stmt->fetch(PDO::FETCH_ASSOC);


// Обновляем данные в таблице, нужно продлить срок работы на выбранный промежуток времени 

/******************************************************************************************** 
теперь нужно обновить дату
 для этого нужно либо к дате оплаты добаыить период, либо к окончанию срока, если он еще не закончился
********************************************************************************************/
$new_update_date = 1;

$new_update_date = extendSubscription($subscription_end['subscription_end'], $update_period['update_period']);


updateStatusOrderPay ($pdo, $order['id'], 'paid');
update_Paid_Preiod_In_Shops ($pdo, $order['shop_id'], $new_update_date);
update_Status_In_Shops ($pdo, $order['shop_id'], 'active');
// print_r($order);

// die();






// формируем content для письма
// require_once "mailer/shablon_order_email.php";
send_many_emails('dizel007@yandex.ru', 'ПРИШЕЛ ЗАКАЗ',  'fffffff', $mail_for_send_letter, $mail_pass);

 header('Location: ../../dashboard/');