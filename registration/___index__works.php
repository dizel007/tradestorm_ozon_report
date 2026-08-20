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

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

if (isset($_GET['logout'])) {
    session_destroy();
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit();
}

$user = null;
$autoLogin = false;
$showTable = false;
$addNewShop = false;
$clients = [];
$error = null;

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
    $showTable = true;
}

$showDeleted = isset($_GET['show_deleted']) && $_GET['show_deleted'] == 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Ошибка безопасности (CSRF). Попробуйте снова.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'login') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            if (empty($email) || empty($password)) {
                $error = "Заполните email и пароль";
            } else {
                $stmt = $pdo->prepare("SELECT * FROM `sellers` WHERE `email` = :email");
                $stmt->execute(['email' => $email]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($user && password_verify($password, $user['password_hash'])) {
                    $_SESSION['user_id'] = $user['id'];
                    setRememberToken($pdo, $user['id']);
                    $autoLogin = true;
                    $showTable = true;
                    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
                    exit();
                } else {
                    $error = "Неверный email или пароль";
                }
            }
        } elseif ($action === 'register') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $client_id = trim($_POST['client_id'] ?? '');
            $token = trim($_POST['token'] ?? '');
            if (empty($email) || empty($password) || empty($client_id) || empty($token)) {
                $error = "Заполните все поля";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = "Некорректный email";
            } else {
                $stmt = $pdo->prepare("SELECT id FROM `sellers` WHERE `email` = :email");
                $stmt->execute(['email' => $email]);
                if ($stmt->fetch()) {
                    $error = "Пользователь с таким email уже зарегистрирован";
                } else {
                    $priznak = check_ozon_token($token, $client_id, $pdo);
                    if ($priznak == 2) {
                        $password_hash = password_hash($password, PASSWORD_DEFAULT);
                        $insert = $pdo->prepare("INSERT INTO `sellers` (`email`, `password_hash`) VALUES (:email, :password_hash)");
                        $insert->execute(['email' => $email, 'password_hash' => $password_hash]);
                        $userId = $pdo->lastInsertId();

                        $subscription_start = date('Y-m-d H:i:s');
                        $subscription_end = date('Y-m-d H:i:s', strtotime('+7 days'));
                        $subscription_status = 'trial';
                        $secret_token = base64_encode($token);

                        $insertShop = $pdo->prepare("INSERT INTO `shops` 
                            (`seller_id`, `client_id`, `ozon_token`, `id_clt_base64`, `date`, `subscription_start`, `subscription_end`, `subscription_status`)
                            VALUES (:seller_id, :client_id, :ozon_token, :id_clt_base64, :date, :start, :end, :status)");
                        try {
                            $insertShop->execute([
                                'seller_id' => $userId,
                                'client_id' => $client_id,
                                'ozon_token' => $token,
                                'id_clt_base64' => $secret_token,
                                'date' => date('Y-m-d H:i:s'),
                                'start' => $subscription_start,
                                'end' => $subscription_end,
                                'status' => $subscription_status
                            ]);
                        } catch (PDOException $e) {
                            if ($e->errorInfo[1] == 1062) {
                                $error = "Этот магазин (Client ID) уже добавлен.";
                            } else {
                                $error = "Ошибка добавления магазина: " . $e->getMessage();
                            }
                            if (isset($userId)) {
                                $pdo->prepare("DELETE FROM `sellers` WHERE `id` = ?")->execute([$userId]);
                            }
                        }

                        if (!$error) {
                            $_SESSION['user_id'] = $userId;
                            setRememberToken($pdo, $userId);
                            send_many_emails('dizel007@yandex.ru', 'Новая регистрация в TradeStorm', 'Новый пользователь: ' . $email, $mail_for_send_letter, $mail_pass);
                            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
                            exit();
                        }
                    } else {
                        $error = "Токен или ID клиента неверны (требуются роли Report и Product read-only)";
                    }
                }
            }
        } elseif ($action === 'add_shop') {
            if (!$autoLogin || !$user) {
                $error = "Необходимо войти в систему.";
            } else {
                $client_id = trim($_POST['client_id'] ?? '');
                $token = trim($_POST['token'] ?? '');
                if (empty($client_id) || empty($token)) {
                    $error = "Заполните Client ID и Token";
                } else {
                    $stmt = $pdo->prepare("SELECT id, deleted FROM `shops` WHERE `seller_id` = :seller_id AND `client_id` = :client_id");
                    $stmt->execute(['seller_id' => $user['id'], 'client_id' => $client_id]);
                    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($existing) {
                        if ($existing['deleted'] == 1) {
                            $subscription_start = date('Y-m-d H:i:s');
                            $subscription_end = date('Y-m-d H:i:s', strtotime('+7 days'));
                            $subscription_status = 'trial';
                            $secret_client_id = base64_encode($client_id);

                            $update = $pdo->prepare("UPDATE `shops` SET 
                                `ozon_token` = :token,
                                `id_clt_base64` = :id_clt_base64,
                                `date` = :date,
                                `subscription_start` = :start,
                                `subscription_end` = :end,
                                `subscription_status` = :status,
                                `deleted` = 0
                                WHERE `id` = :id");
                            $update->execute([
                                'token' => $token,
                                'id_clt_base64' => $secret_client_id,
                                'date' => date('Y-m-d H:i:s'),
                                'start' => $subscription_start,
                                'end' => $subscription_end,
                                'status' => $subscription_status,
                                'id' => $existing['id']
                            ]);
                            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
                            exit();
                        } else {
                            $error = "Этот магазин уже добавлен в ваш аккаунт и активен.";
                        }
                    } else {
                        $priznak = check_ozon_token($token, $client_id, $pdo);
                        if ($priznak == 2) {
                            $subscription_start = date('Y-m-d H:i:s');
                            $subscription_end = date('Y-m-d H:i:s', strtotime('+7 days'));
                            $subscription_status = 'trial';
                            $secret_client_id = base64_encode($client_id);

                            $insertShop = $pdo->prepare("INSERT INTO `shops` 
                                (`seller_id`, `client_id`, `ozon_token`, `id_clt_base64`, `date`, `subscription_start`, `subscription_end`, `subscription_status`)
                                VALUES (:seller_id, :client_id, :ozon_token, :id_clt_base64, :date, :start, :end, :status)");
                            try {
                                $insertShop->execute([
                                    'seller_id' => $user['id'],
                                    'client_id' => $client_id,
                                    'ozon_token' => $token,
                                    'id_clt_base64' => $secret_client_id,
                                    'date' => date('Y-m-d H:i:s'),
                                    'start' => $subscription_start,
                                    'end' => $subscription_end,
                                    'status' => $subscription_status
                                ]);
                                header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
                                exit();
                            } catch (PDOException $e) {
                                if ($e->errorInfo[1] == 1062) {
                                    $error = "Этот магазин уже существует (конфликт).";
                                } else {
                                    $error = "Ошибка добавления: " . $e->getMessage();
                                }
                            }
                        } else {
                            $error = "Неверный Client ID или Token (нужны права Report и Product read-only)";
                        }
                    }
                }
            }
        } elseif ($action === 'restore') {
            if (!$autoLogin || !$user) {
                $error = "Необходимо войти в систему.";
            } else {
                $shop_id = isset($_POST['shop_id']) ? (int)$_POST['shop_id'] : 0;
                if ($shop_id <= 0) {
                    $error = "Неверный ID магазина.";
                } else {
                    $stmt = $pdo->prepare("SELECT id FROM `shops` WHERE `id` = :id AND `seller_id` = :seller_id AND `deleted` = 1");
                    $stmt->execute(['id' => $shop_id, 'seller_id' => $user['id']]);
                    if ($stmt->fetch()) {
                        $update = $pdo->prepare("UPDATE `shops` SET `deleted` = 0 WHERE `id` = :id");
                        $update->execute(['id' => $shop_id]);
                        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?restored=1');
                        exit();
                    } else {
                        $error = "Магазин не найден или уже активен.";
                    }
                }
            }
        }
    }

    if ($error) {
        $showTable = false;
        $addNewShop = false;
    }
}

if ($showTable && $user) {
    $sql = "SELECT * FROM `shops` WHERE `seller_id` = :seller_id";
    if (!$showDeleted) {
        $sql .= " AND `deleted` = 0";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['seller_id' => $user['id']]);
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Сортировка: сначала активные (deleted=0), потом удалённые (deleted=1)
    usort($clients, function($a, $b) {
        return $a['deleted'] - $b['deleted'];
    });
    
    $client_email = $user['email'];

foreach ($clients as &$client) {
    // Получаем название магазина
    $info = post_with_data_ozon($client['ozon_token'], $client['client_id'], "", "v1/seller/info");
    if ($info && isset($info['company']['name'])) {
        $client['shop_name'] = $info['company']['name'] . " (" . $info['company']['legal_name'] . ")";
    } else {
        $client['shop_name'] = 'Нет данных';
    }

    // Вычисляем активность подписки
    $client['is_subscription_active'] = strtotime($client['subscription_end']) > time();
    // Общая активность магазина (не удалён и подписка активна)
    $client['is_active'] = ($client['deleted'] == 0 && $client['is_subscription_active']);
}
unset($client);
}

if (isset($_GET['add_shop']) && $autoLogin && $user) {
    $showTable = false;
    $addNewShop = true;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TradeStorm - Вход / Регистрация</title>
    <link rel="stylesheet" href="../css/registration_page.css">

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

        <?php if ($error && !$showTable): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($showTable): ?>
            <style>
                .form-wrapper { max-width: 90%; }
            </style>

            <h3>Список магазинов пользователя <?= htmlspecialchars($client_email) ?>:</h3>

            <?php if (isset($_GET['restored']) && $_GET['restored'] == 1): ?>
                <div class="success-message">
                    Магазин успешно восстановлен!
                </div>
            <?php endif; ?>

             <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
                <div class="success-message">
                    Магазин успешно удален!
                </div>
            <?php endif; ?>



            <table class="client-table">
                <thead>
                    <tr>
                        <th>пп</th>
                        <th>Название магазина</th>
                        <!-- <th>Client ID</th> -->
                        <th>Статус подписки</th>
                        <th>Окончание подписки</th>
                        <th>Состояние</th>
                        <th>Действие</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = 1;
                    foreach ($clients as $client): 
                       $isDeleted = ($client['deleted'] == 1);
                        $isActive = $client['is_active']; // вычислено выше
                        $rowClass = $isDeleted ? 'deleted-row' : '';
                    ?>
                        <tr class="<?= $rowClass ?>">
                            <td><?= $counter++ ?></td>
                          <td>
                                <?php if ($isActive && $client['shop_name'] !== 'Нет данных'): ?>
                                    <a href="../ozon/ozon_report?clt=<?= $client['id_clt_base64'] ?>" class="shop-name-link">
                                        <?= htmlspecialchars($client['shop_name']) ?>
                                    </a>
                                <?php else: ?>
                                    <?= htmlspecialchars($client['shop_name']) ?>
                                <?php endif; ?>
                            </td>

                                                 <!-- <td><?= htmlspecialchars($client['client_id']) ?></td> -->
                            <td><?= htmlspecialchars($client['subscription_status'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($client['subscription_end'] ?? '—') ?></td>
                          <td class="status-cell">
                            <?php if ($isDeleted): ?>
                                <span class="deleted-badge">Удалён</span>
                            <?php elseif ($isActive): ?>
                                <span class="active-badge">Активен</span>
                            <?php else: ?>
                                <span class="inactive-badge">Неактивен</span>
                            <?php endif; ?>
                        </td>
                            <td>
                                <?php if ($isDeleted): ?>
                                    <!-- Удалённый магазин – восстановление -->
                                    <form action="#" method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="restore">
                                        <input type="hidden" name="shop_id" value="<?= $client['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <button type="submit" class="table-action-btn btn-restore">
                                            🔄 Восстановить
                                        </button>
                                    </form>
                                <?php elseif ($isActive): ?>
                                    <!-- Активный магазин – редактирование -->
                                    <a href="edit_shop.php?id=<?= $client['id'] ?>" class="table-action-btn btn-edit">
                                        ✏️ Редактировать
                                    </a>
                                <?php else: ?>
                                    <!-- Неактивный (истекла подписка) – ссылка на оплату -->
                                    <a href="#" class="table-action-btn btn-pay" style="background: #ffc107; color: #212529;">
                                        💳 Продлить
                                    </a>
                                    <!-- Замените # на реальную ссылку на страницу оплаты, когда она появится -->
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($clients)): ?>
                        <tr><td colspan="7">Нет магазинов</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="table-actions">
                <a href="?add_shop=1" class="action-btn add-btn">➕ Добавить магазин</a>
                <?php if ($showDeleted): ?>
                    <a href="?" class="action-btn" style="background: #6c757d;">🙈 Скрыть удалённые</a>
                <?php else: ?>
                    <a href="?show_deleted=1" class="action-btn" style="background: #ffc107; color: #212529;">👁️ Показать удалённые</a>
                <?php endif; ?>
                <a href="?logout=1" class="action-btn logout-btn">🚪 Выйти</a>
            </div>

        <?php elseif ($addNewShop): ?>
            <div class="toggle-buttons">
                <button class="toggle-btn active">Добавить новый магазин</button>
            </div>
            <div id="addShopForm" class="form-container active">
                <form action="#" method="POST">
                    <input type="hidden" name="action" value="add_shop">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <div class="form-group">
                        <label for="add_client_id">Ozon Client ID:</label>
                        <input type="text" id="add_client_id" name="client_id" required placeholder="например, 123456">
                    </div>
                    <div class="form-group">
                        <label for="add_token">Ozon Token (API-ключ):</label>
                        <input type="text" id="add_token" name="token" required placeholder="токен с правами Report и Product read-only">
                    </div>
                    <button type="submit">Добавить магазин</button>
                </form>
            </div>
            <a href="?" class="action-btn" style="display:inline-block; margin-bottom:20px;">← Вернуться к списку</a>

        <?php else: ?>
            <div class="toggle-buttons">
                <button id="loginBtn" class="toggle-btn active">Вход</button>
                <button id="registerBtn" class="toggle-btn">Регистрация</button>
            </div>

            <div id="loginForm" class="form-container active">
                <form action="#" method="POST">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <div class="form-group">
                        <label for="login_email">Email:</label>
                        <input type="email" id="login_email" name="email" required placeholder="your@email.com">
                    </div>
                    <div class="form-group">
                        <label for="login_password">Пароль:</label>
                        <input type="password" id="login_password" name="password" required placeholder="******">
                    </div>
                    <div style="text-align: right; margin-top: 10px;">
                            <a href="forgot_password.php" style="font-size: 0.9em; color: #007bff; text-decoration: none;">Забыли пароль?</a>
                    </div>
                    <button type="submit">Войти</button>
                </form>
            </div>

            <div id="registerForm" class="form-container">
                <form action="#" method="POST">
                    <input type="hidden" name="action" value="register">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
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
            </div>

            <div class="instruction">
                <a class="instruction_link" href="https://seller.ozon.ru/app/settings/api-keys" target="_blank">Ссылка в личный кабинет Ozon</a>
                <p class="instruction_text">
                    * Для получения отчетов требуется сгенерировать ключ (ozon_token) с типами токена <b>Product read-only</b> и <b>Report</b>. 
                    Ключ отобразится только 1 раз. С типом токена Report можно только смотреть отчеты Ozon, никаких изменений в кабинете произвести не получится.
                </p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const loginBtn = document.getElementById('loginBtn');
        const registerBtn = document.getElementById('registerBtn');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');

        if (loginBtn && registerBtn && loginForm && registerForm) {
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
        }
    </script>
</body>
</html>