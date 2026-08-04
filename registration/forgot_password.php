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
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Введите корректный email.";
    } else {
        // Проверяем, есть ли пользователь
        $stmt = $pdo->prepare("SELECT id, email FROM `sellers` WHERE `email` = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            // Генерируем токен
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', time() + 3600); // 1 час
            $update = $pdo->prepare("UPDATE `sellers` SET `reset_token` = :token, `reset_token_expiry` = :expiry WHERE `id` = :id");
            $update->execute(['token' => $token, 'expiry' => $expiry, 'id' => $user['id']]);

            // Ссылка для сброса (замените на ваш домен)
            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . "".$domen_name."registration/reset_password.php?token=" . $token;

            // Отправка письма
            $subject = "Восстановление пароля в TradeStorm";
            $message = "Здравствуйте!\n\nВы запросили восстановление пароля.\nПерейдите по ссылке для установки нового пароля:\n$reset_link\n\nЕсли вы не запрашивали восстановление, просто проигнорируйте это письмо.\n\nС уважением, TradeStorm.";
            // Используем вашу функцию отправки писем
            send_many_emails($user['email'], $subject, $message, $mail_for_send_letter, $mail_pass);

            $success = true;
        } else {
            // Не говорим, что email не найден, чтобы не давать информацию о существовании пользователей
            $success = true; // всё равно показываем успех для безопасности
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Восстановление пароля – TradeStorm</title>
    <link rel="stylesheet" href="../css/registration_page.css">
</head>
<body>
    <div class="form-wrapper">
        <h2>Восстановление пароля</h2>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message" style="color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                Если указанный email зарегистрирован в системе, на него отправлена инструкция по восстановлению пароля.
            </div>
        <?php else: ?>
            <form action="#" method="POST">
                <div class="form-group">
                    <label for="email">Ваш email:</label>
                    <input type="email" id="email" name="email" required placeholder="your@email.com">
                </div>
                <button type="submit">Отправить ссылку для сброса</button>
            </form>
        <?php endif; ?>

        <div style="margin-top: 20px; text-align: center;">
            <a href="index.php">← Вернуться ко входу</a>
        </div>
    </div>
</body>
</html>