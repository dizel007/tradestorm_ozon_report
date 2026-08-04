<?php
require_once("../main_info.php");
require_once("../vendor/autoload.php");
require_once("functions.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $pdo = new PDO('mysql:host=' . $host . ';dbname=' . $db . ';charset=utf8', $user, $password);
    $pdo->exec('SET NAMES utf8');
    $pdo->exec("SET time_zone = '+03:00'"); // ставим Московское время
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

$error = null;
$success = false;
$token = $_GET['token'] ?? '';

if (empty($token)) {
    die("Неверная ссылка восстановления.");
}

// Проверяем токен
$stmt = $pdo->prepare("SELECT id FROM `sellers` WHERE `reset_token` = :token AND `reset_token_expiry` > NOW()");
$stmt->execute(['token' => $token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$user) {
    die("Ссылка недействительна или истекла. Запросите восстановление заново.");
}

$userId = $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    if (empty($password) || empty($confirm)) {
        $error = "Заполните оба поля.";
    } elseif ($password !== $confirm) {
        $error = "Пароли не совпадают.";
    } elseif (strlen($password) < 6) {
        $error = "Пароль должен быть не менее 6 символов.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE `sellers` SET `password_hash` = :hash, `reset_token` = NULL, `reset_token_expiry` = NULL WHERE `id` = :id");
        $update->execute(['hash' => $hash, 'id' => $userId]);
        $success = true;
        // Можно сразу авторизовать пользователя, но лучше предложить войти.
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Новый пароль – TradeStorm</title>
    <link rel="stylesheet" href="../css/registration_page.css">
</head>
<body>
    <div class="form-wrapper">
        <h2>Установка нового пароля</h2>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message" style="color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                Пароль успешно изменён! Теперь вы можете <a href="index.php">войти</a> с новым паролем.
            </div>
        <?php else: ?>
            <form action="#" method="POST">
                <div class="form-group">
                    <label for="password">Новый пароль:</label>
                    <input type="password" id="password" name="password" required placeholder="минимум 6 символов">
                </div>
                <div class="form-group">
                    <label for="confirm">Подтвердите пароль:</label>
                    <input type="password" id="confirm" name="confirm" required placeholder="повторите пароль">
                </div>
                <button type="submit">Сохранить пароль</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>