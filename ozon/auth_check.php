<?php
// auth_check.php – проверка авторизации и получение данных пользователя

require_once "../../registration/functions.php";
if (session_status() === PHP_SESSION_NONE) {
        // Устанавливаем сесии
    
    session_start();
}

// Предполагается, что $pdo уже определён в вызывающем файле
// Если нет – можно подключить БД и здесь, но лучше передавать через глобал

function checkAuth($pdo) {
    $user = null;
    $isLoggedIn = false;

    if (isset($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM `sellers` WHERE `id` = :id");
        $stmt->execute(['id' => $_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $isLoggedIn = true;
        } else {
            session_destroy();
        }
    }

    if (!$isLoggedIn && isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        $stmt = $pdo->prepare("SELECT * FROM `sellers` WHERE `remember_token` = :token AND `remember_token_expiry` > NOW()");
        $stmt->execute(['token' => $token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $isLoggedIn = true;
            setRememberToken($pdo, $user['id']);
        } else {
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }
    }

    return ['user' => $user, 'loggedIn' => $isLoggedIn];
}
?>