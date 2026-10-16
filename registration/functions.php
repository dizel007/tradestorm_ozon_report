<?php
// ---------------------------------------------------------------------
// Генерация и установка remember-токена
// ---------------------------------------------------------------------
function setRememberToken($pdo, $userId) {
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', time() + 72000); // 20 часов

    $update = $pdo->prepare("UPDATE `sellers` SET `remember_token` = :token, `remember_token_expiry` = :expiry WHERE `id` = :id");
    $update->execute(['token' => $token, 'expiry' => $expiry, 'id' => $userId]);

    setcookie('remember_token', $token, time() + 72000, '/', '', false, true);
    return $token;
}

