<?php
require_once("../main_info.php");
require_once("../vendor/autoload.php");

try {
    $pdo = new PDO('mysql:host=' . $host . ';dbname=' . $db . ';charset=utf8', $user, $password);
    $pdo->exec('SET NAMES utf8');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    print "Has errors: " . $e->getMessage();
    die();
}

// Добавляем необходимые колонки в таблицу tokens, если их нет
try {
    $pdo->exec("ALTER TABLE `tokens` ADD COLUMN IF NOT EXISTS `email` VARCHAR(255) UNIQUE");
    $pdo->exec("ALTER TABLE `tokens` ADD COLUMN IF NOT EXISTS `password_hash` VARCHAR(255)");
} catch (PDOException $e) {
    // Если ALTER не поддерживает IF NOT EXISTS, делаем через проверку
    $stmt = $pdo->query("SHOW COLUMNS FROM `tokens` LIKE 'email'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `tokens` ADD COLUMN `email` VARCHAR(255) UNIQUE");
    }
    $stmt = $pdo->query("SHOW COLUMNS FROM `tokens` LIKE 'password_hash'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE `tokens` ADD COLUMN `password_hash` VARCHAR(255)");
    }
}

ob_start();
// echo "<pre>";
// print_r( $_POST);

// Обработка POST-запросов
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $email = htmlspecialchars(trim($_POST['email'] ?? ''));
        $password = htmlspecialchars($_POST['password'] ?? '');
// echo "<br>email = $email";
// echo "<br>password = $password";

        if (empty($email) || empty($password)) {
            $error = "Заполните email и пароль";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM `tokens` WHERE `email` = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);






            if ($user && password_verify($password, $user['password_hash'])) {
                // Проверяем валидность сохраненных токена и client_id через Ozon API
                $token = $user['ozon_token'];
                $client_id = $user['id_client'];

            // ---  ПРОВЕРКА ПОДПИСКИ ---
                if (!isSubscriptionActive($user, $pdo)) {
                    $error = "Ваш оплаченный(пробный) период истёк. Оформите подписку, чтобы продолжить пользоваться сервисом.";
                    // Не делаем редирект, а показываем ошибку на той же странице
                } else {
                    // Всё хорошо – обновляем дату и редиректим
                    $update = $pdo->prepare("UPDATE `tokens` SET `date` = :date WHERE `id` = :id");
                    $update->execute(['date' => date('Y-m-d H:i:s'), 'id' => $user['id']]);
                    $secret_client_id = base64_encode($user['id_client']);
                    header('Location: ../ozon/ozon_report?clt=' . $secret_client_id, true, 301);
                    exit();
                }
              
            } else {
                $error = "Неверный email или пароль";
            }
        }
} elseif ($action === 'register') {
    $email = htmlspecialchars(trim($_POST['email'] ?? ''));
    $password = htmlspecialchars($_POST['password'] ?? '');
    $token = htmlspecialchars(trim($_POST['token'] ?? ''));
    $client_id = htmlspecialchars(trim($_POST['client_id'] ?? ''));

    $subscription_start = date('Y-m-d H:i:s');
    $subscription_end = date('Y-m-d H:i:s', strtotime('+7 days'));
    $subscription_status = 'trial';



    if (empty($email) || empty($password) || empty($token) || empty($client_id)) {
        $error = "Заполните все поля";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Некорректный email";
    } else {
        // Проверяем, существует ли такой email
        $stmt = $pdo->prepare("SELECT id FROM `tokens` WHERE `email` = :email");
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) {
            $error = "Пользователь с таким email уже зарегистрирован";
        } 
        // Проверяем, не зарегистрирован ли уже этот client_id
        elseif (checkClientIdExists($pdo, $client_id)) {
            $error = "Этот Ozon-аккаунт уже зарегистрирован другим пользователем. Используйте вход или другой Client ID.";
        }
        else {
            // Проверяем токен через Ozon API
            $priznak = check_ozon_token($token, $client_id, $pdo);
            if ($priznak == 2) {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                $secret_client_id = base64_encode($client_id);
                $insert = $pdo->prepare("INSERT INTO `tokens` 
                    (`ozon_token`, `id_client`, `id_clt_base64`, `date`, `email`, `password_hash`,
                    `subscription_start`, `subscription_end`, `subscription_status`) 
                    VALUES (:token, :client_id, :base64, :date, :email, :hash, 
                    :subscription_start, :subscription_end, :subscription_status)");
                $insert->execute([
                    'token' => $token,
                    'client_id' => $client_id,
                    'base64' => $secret_client_id,
                    'date' => date('Y-m-d H:i:s'),
                    'email' => $email,
                    'hash' => $password_hash,

                    'subscription_start' => $subscription_start,
                    'subscription_end' => $subscription_end,
                    'subscription_status' => $subscription_status


                ]);
                send_many_emails('dizel007@yandex.ru', 'Новая регистрация в TradeStorm', 'Новый пользователь: ' . $email, $mail_for_send_letter, $mail_pass);
                header('Location: ../ozon/ozon_report?clt=' . $secret_client_id, true, 301);
                exit();
            } else {
                $error = "Токен или ID клиента неверны (требуются роли Report и Product read-only)";
            }
        }
    }
}

}

// Функция проверки токена Ozon на обе роли (возвращает 3 если ок)
function check_ozon_token($token, $client_id, $pdo) {
    $priznak = 0;
    $date_now = date('Y-m-d');
    
    // Проверка Report
    $ozon_link = 'v3/finance/transaction/list';
    $send_data = json_encode([
        "filter" => [
            "date" => [
                "from" => $date_now . "T00:00:00.000Z",
                "to" => $date_now . "T00:00:00.000Z"
            ],
            "operation_type" => [],
            "posting_number" => "",
            "transaction_type" => "all"
        ],
        "page" => 1,
        "page_size" => 1
    ]);
    $http_code = send_query_on_ozon($token, $client_id, $send_data, $ozon_link);
    if (intdiv($http_code, 100) <= 2) $priznak++;
    
    // Проверка Product read-only
    $ozon_link = "v5/product/info/prices";
    $send_data = json_encode([
        "cursor" => "",
        "filter" => ["visibility" => "ALL"],
        "limit" => 1
    ]);
    $http_code = send_query_on_ozon($token, $client_id, $send_data, $ozon_link);
    if (intdiv($http_code, 100) <= 2) $priznak++;
    
    return $priznak;
}

// Функция send_query_on_ozon уже существует, оставляем как есть
function send_query_on_ozon($token, $client_id, $send_data, $ozon_dop_url) {
    $ch = curl_init('https://api-seller.ozon.ru/' . $ozon_dop_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Api-Key:' . $token,
        'Client-Id:' . $client_id,
        'Content-Type:application/json'
    ));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $send_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $http_code;
}

// Функция createDirectoryIfNotExists (не используется напрямую в новом коде, но оставим)
function createDirectoryIfNotExists($path) {
    if (!is_dir($path)) {
        if (mkdir($path, 0777, true)) {
            return true;
        } else {
            throw new Exception("Не удалось создать папку: $path");
        }
    }
    return true;
}

// Проверяет, существует ли уже client_id в базе (неважно, с email или без)
function checkClientIdExists($pdo, $client_id) {
    $stmt = $pdo->prepare("SELECT id FROM `tokens` WHERE `id_client` = :client_id");
    $stmt->execute(['client_id' => $client_id]);
    return $stmt->fetch() !== false;
}


/*****************************************************************************************************************
 * Проверяет статус подписки пользователя
 * @return bool true - доступ разрешён, false - доступ запрещён
 **********************************************************************************************************/
function isSubscriptionActive($user, $pdo) {
    $now = date('Y-m-d H:i:s');
    $status = $user['subscription_status'];

// echo "<pre>";
//     print_r($user);
//     die();
    $subscription_start = $user['subscription_start'];
    $subscription_end = $user['subscription_end'];

    // 1. Пробный период
    if ($status === 'trial') {
        if ($subscription_end > $now) {

            return true; // ещё действует
        } else {
            // Пробный период закончился – обновляем статус
            $update = $pdo->prepare("UPDATE `tokens` SET `subscription_status` = 'expired' WHERE `id` = :id");
            $update->execute(['id' => $user['id']]);
            return false;
        }
    }

    // 2. Платная подписка
    if ($status === 'active') {
        $end = $user['subscription_end'];
        if ($end > $now) {
            return true;
        } else {
            // Истекла платная подписка
            $update = $pdo->prepare("UPDATE `tokens` SET `subscription_status` = 'expired' WHERE `id` = :id");
            $update->execute(['id' => $user['id']]);
            return false;
        }
    }

    // 3. Любой другой статус (expired, cancelled и т.д.)
    return false;
}


?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TradeStorm - Вход / Регистрация</title>
    <link rel="stylesheet" href="css/index_page.css">
   </head>
<body>
    <div class="pdf-download-container">
        <a href="../ozon/files/doc1.pdf" download class="pdf-download-btn">
            <svg class="pdf-icon" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
            </svg>
            Скачать инструкцию по подключению (PDF)
        </a>
    </div>
    <div class="pdf-download-container2">
        <a href="../ozon/files/doc2.pdf" download class="pdf-download-btn">
            <svg class="pdf-icon" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
            </svg>
            Скачать инструкцию по работе (PDF)
        </a>
    </div>
 <div class="form-wrapper">
    <h2>TradeStorm</h2>

    <?php if (isset($error)): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="toggle-buttons">
        <button id="loginBtn" class="toggle-btn active">Вход</button>
        <button id="registerBtn" class="toggle-btn">Регистрация</button>
    </div>

    <!-- Форма входа -->
    <div id="loginForm" class="form-container active">
        <form action="#" method="POST">
            <input type="hidden" name="action" value="login">
            <div class="form-group">
                <label for="login_email">Email:</label>
                <input type="email" id="login_email" name="email" required placeholder="your@email.com">
            </div>
            <div class="form-group">
                <label for="login_password">Пароль:</label>
                <input type="password" id="login_password" name="password" required placeholder="******">
            </div>
            <button type="submit">Войти</button>
        </form>
    </div>

    <!-- Форма регистрации -->
    <div id="registerForm" class="form-container">
        <form action="#" method="POST">
            <input type="hidden" name="action" value="register">
            <div class="form-group">
                <label for="reg_email">Email:</label>
                <input type="email" id="reg_email" name="email" required placeholder="your@email.com">
            </div>
            <div class="form-group">
                <label for="reg_password">Пароль:</label>
                <input type="password" id="reg_password" name="password" required placeholder="******">
            </div>
            <div class="form-group">
                <label for="reg_client_id">Ozon Client ID:</label>
                <input type="text" id="reg_client_id" name="client_id" required placeholder="например, 123456">
            </div>
            <div class="form-group">
                <label for="reg_token">Ozon Token (API-ключ):</label>
                <input type="text" id="reg_token" name="token" required placeholder="токен с правами Report и Product read-only">
            </div>
            <button type="submit">Зарегистрироваться</button>
        </form>
   

    <div class="instruction">
        <a class="instruction_link" href="https://seller.ozon.ru/app/settings/api-keys" target="_blank">Ссылка в личный кабинет Ozon</a>
        <p class="instruction_text">
            * Для получения отчетов требуется сгенерировать ключ (ozon_token) с типами токена <b>Product read-only</b> и <b>Report</b>. 
            Ключ отобразится только 1 раз. С типом токена Report можно только смотреть отчеты Ozon, никаких изменений в кабинете произвести не получится.
        </p>
    </div>
 </div>
  </div>
    <script>
        // Переключение между формами входа и регистрации
        const loginBtn = document.getElementById('loginBtn');
        const registerBtn = document.getElementById('registerBtn');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');

        loginBtn.addEventListener('click', () => {
            loginBtn.classList.add('active');
            registerBtn.classList.remove('active');
            loginForm.classList.add('active');
            registerForm.classList.remove('active');
        });

        registerBtn.addEventListener('click', () => {
            registerBtn.classList.add('active');
            loginBtn.classList.remove('active');
            registerForm.classList.add('active');
            loginForm.classList.remove('active');
        });
    </script>
</body>
</html>