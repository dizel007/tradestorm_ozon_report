<?php
require_once("../main_info.php");
require_once("../vendor/autoload.php");
require_once("functions.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $id_client = trim($_POST['id_client'] ?? '');

    // Валидация полей
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Введите корректный email.";
    } elseif (empty($id_client)) {
        $error = "Введите ID клиента.";
    } else {
   // Проверяем, есть ли пользователь с таким email 
        $stmt = $pdo->prepare("SELECT id  FROM `sellers` WHERE `email` = :email");
        $stmt->execute(['email' => $email]);
        $seller_id  = $stmt->fetch(PDO::FETCH_ASSOC);

        // var_dump($seller_id);
  // Проверяем, есть ли пользователь с таким client_id
        $stmt = $pdo->prepare("SELECT *  FROM `shops` WHERE `seller_id` = :seller_id AND `client_id` = :client_id ");
        $stmt->execute(['seller_id' => $seller_id['id'], 
                        'client_id' => $id_client]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
// echo "<pre>";
//     print_r($user);

        // die();
        if ($user) {
            // Генерируем токен
            $reset_token = bin2hex(random_bytes(32));
            $reset_token_expiry = date('Y-m-d H:i:s', time() + 14400); // 1 час
            $update = $pdo->prepare("UPDATE `sellers` SET `reset_token` = :reset_token, `reset_token_expiry` = :reset_token_expiry WHERE `id` = :id");
            $update->execute(['reset_token' => $reset_token, 'reset_token_expiry' => $reset_token_expiry, 'id' => $user['seller_id']]);

            // Ссылка для сброса
            $reset_link = "https://" . $_SERVER['HTTP_HOST'] . $domen_name . "registration/reset_password.php?token=" . $reset_token;

            // Отправка письма
            $subject = "Восстановление пароля в TradeStorm";
            $message = "Здравствуйте!\n\nВы запросили восстановление пароля.\nПерейдите по ссылке для установки нового пароля:\n$reset_link\n\nЕсли вы не запрашивали восстановление, просто проигнорируйте это письмо.\n\nС уважением, TradeStorm.";
            send_many_emails($email, $subject, $message, $mail_for_send_letter, $mail_pass);

            $success = true;
        } else {
            // Не сообщаем, что данные не найдены, для безопасности показываем успех
            $success = true;
        }
    }
}

// die();
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
                Если указанные email и ID клиента соответствуют зарегистрированному пользователю, на него отправлена инструкция по восстановлению пароля.
            </div>
        <?php else: ?>
            <form action="#" method="POST">
                <div class="form-group">
                    <label for="email">Ваш email:</label>
                    <input type="email" id="email" name="email" required placeholder="your@email.com">
                </div>
                <div class="form-group">
                    <label for="id_client">ID клиента:</label>
                    <input type="text" id="id_client" name="id_client" required placeholder="Ваш ID клиента">
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