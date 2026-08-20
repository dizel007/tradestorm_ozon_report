<?php
require_once("../main_info.php");
require_once("../_no_git/secret_info.php");
require_once("../vendor/autoload.php");
require_once("../ozon/mp_functions/ozon_api_functions.php");
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

// Проверка авторизации
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

if (!$autoLogin || !$user) {
    header('Location: index.php');
    exit();
}

// Обработка logout
if (isset($_GET['logout'])) {
    session_destroy();
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    header('Location: index.php');
    exit();
}

$showDeleted = isset($_GET['show_deleted']) && $_GET['show_deleted'] == 1;
$addNewShop = isset($_GET['add_shop']);
$error = null;

// Обработка POST действий (add_shop, restore)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Ошибка безопасности (CSRF). Попробуйте снова.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add_shop') {
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
                        header('Location: dashboard.php');
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
                            header('Location: dashboard.php');
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
        } elseif ($action === 'restore') {
            $shop_id = isset($_POST['shop_id']) ? (int)$_POST['shop_id'] : 0;
            if ($shop_id <= 0) {
                $error = "Неверный ID магазина.";
            } else {
                $stmt = $pdo->prepare("SELECT id FROM `shops` WHERE `id` = :id AND `seller_id` = :seller_id AND `deleted` = 1");
                $stmt->execute(['id' => $shop_id, 'seller_id' => $user['id']]);
                if ($stmt->fetch()) {
                    $update = $pdo->prepare("UPDATE `shops` SET `deleted` = 0 WHERE `id` = :id");
                    $update->execute(['id' => $shop_id]);
                    header('Location: dashboard.php?restored=1');
                    exit();
                } else {
                    $error = "Магазин не найден или уже активен.";
                }
            }
        }
    }

    if ($error) {
        // Если ошибка, покажем форму добавления или таблицу? Лучше показать таблицу с ошибкой.
        $addNewShop = false;
    }
}

// Получение списка магазинов
$clients = [];
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
    $client['data_clt'] = encryptData($client['id_clt_base64']); ;


    }
unset($client);


// echo "<pre>";
// print_r($clients);
// die();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TradeStorm - Список магазинов</title>
    <link rel="stylesheet" href="../css/registration_page.css">
</head>
<body>
    <div class="form-wrapper" style="max-width: 90%;">
        <h2>Аналитическая система TradeStorm</h2>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['restored']) && $_GET['restored'] == 1): ?>
            <div class="success-message">Магазин успешно восстановлен!</div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
            <div class="success-message">Магазин успешно удален!</div>
        <?php endif; ?>

        <?php if ($addNewShop): ?>
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
            <a href="dashboard.php" class="action-btn" style="display:inline-block; margin-bottom:20px;">← Вернуться к списку</a>
        <?php else: ?>
            <h3>Список магазинов пользователя: <?= htmlspecialchars($client_email) ?>:</h3>

            <table class="client-table">
                <thead>
                    <tr>
                        <th>пп</th>
                        <th>Название магазина</th>
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
                        $isActive = $client['is_active'];
                        $rowClass = $isDeleted ? 'deleted-row' : '';
                    ?>
                        <tr class="<?= $rowClass ?>">
                            <td><?= $counter++ ?></td>
                            <td>
                                <?php if ($isActive && $client['shop_name'] !== 'Нет данных'): ?>
                                    <a href="../ozon/ozon_report?data=<?= $client['data_clt'] ?>" class="shop-name-link">
                                        <?= htmlspecialchars($client['shop_name']) ?>
                                    </a>
                                <?php else: ?>
                                    <?= htmlspecialchars($client['shop_name']) ?>
                                <?php endif; ?>
                            </td>
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
                                    <form action="#" method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="restore">
                                        <input type="hidden" name="shop_id" value="<?= $client['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                        <button type="submit" class="table-action-btn btn-restore">
                                            🔄 Восстановить
                                        </button>
                                    </form>
                                <?php elseif ($isActive): ?>
                                    <a href="edit_shop.php?id=<?= $client['id'] ?>" class="table-action-btn btn-edit">
                                        ✏️ Редактировать
                                    </a>
                                <?php else: ?>
                                    <a href="../_ozon_pay/form_pay_order.php" class="table-action-btn btn-pay" style="background: #ffc107; color: #212529;">
                                        💳 Продлить
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($clients)): ?>
                        <tr><td colspan="6">Нет магазинов</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <div class="table-actions">
                <a href="dashboard.php?add_shop=1" class="action-btn add-btn">➕ Добавить магазин</a>
                <?php if ($showDeleted): ?>
                    <a href="dashboard.php" class="action-btn" style="background: #6c757d;">🙈 Скрыть удалённые</a>
                <?php else: ?>
                    <a href="dashboard.php?show_deleted=1" class="action-btn" style="background: #ffc107; color: #212529;">👁️ Показать удалённые</a>
                <?php endif; ?>
                <a href="dashboard.php?logout=1" class="action-btn logout-btn">🚪 Выйти</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>