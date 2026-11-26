<?php

function ozon_authorization ($pdo, $alarm_redirect_adress){

// ---------- Авторизация ----------
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
    $remember_token = $_COOKIE['remember_token'];
    $stmt = $pdo->prepare("SELECT * FROM `sellers` WHERE `remember_token` = :remember_token AND `remember_token_expiry` > NOW()");
    $stmt->execute(['remember_token' => $remember_token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $autoLogin = true;
        // setRememberToken($pdo, $user['id']);
    } else {
        setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    }
}

if (!$autoLogin || !$user) {
    header('Location: '.$alarm_redirect_adress);
    exit();
}

/// получаем токен выбранного магазина
if (isset($_GET['data'])) {
    $stmt = $pdo->prepare("SELECT * FROM `shops` WHERE `id` = :id");
    $stmt->execute(['id' => decryptData($_GET['data'])]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($shop) {
        $token = $shop['ozon_token'];
        $client_id = $shop['client_id'];
        $seller_id = $shop['seller_id'];
        $shop_id = $shop['id'];
    } else {
        session_destroy();
         header('Location: '.$alarm_redirect_adress);
         exit();
    }
} else {
     header('Location: '.$alarm_redirect_adress);
     exit();
}

return [$token , $client_id, $seller_id, $shop_id];
}