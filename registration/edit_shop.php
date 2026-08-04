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

$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM `sellers` WHERE `id` = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}
if (!$user) {
    header('Location: index.php');
    exit();
}

$shop_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($shop_id <= 0) {
    die("Неверный ID магазина.");
}

$now = date('Y-m-d H:i:s');
$stmt = $pdo->prepare("SELECT * FROM `shops` WHERE `id` = :id AND `seller_id` = :seller_id AND `deleted` = 0 AND `subscription_end` > :now");
$stmt->execute(['id' => $shop_id, 'seller_id' => $user['id'], 'now' => $now]);
$shop = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$shop) {
    die("Магазин не найден, удалён или срок подписки истёк. Редактирование недоступно.");
}

$error = null;
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Ошибка безопасности (CSRF). Попробуйте снова.";
    } else {
        $action = $_POST['action'];

        if ($action === 'update_token') {
            $new_token = trim($_POST['ozon_token'] ?? '');
            if (empty($new_token)) {
                $error = "Токен не может быть пустым.";
            } else {
                $priznak = check_ozon_token($new_token, $shop['client_id'], $pdo);
                if ($priznak == 2) {
                    $update = $pdo->prepare("UPDATE `shops` SET `ozon_token` = :token WHERE `id` = :id");
                    $update->execute(['token' => $new_token, 'id' => $shop_id]);
                    $success = true;
                    $shop['ozon_token'] = $new_token;
                } else {
                    $error = "Неверный токен: требуются права Report и Product read-only.";
                }
            }
        } elseif ($action === 'delete_shop') {
            $update = $pdo->prepare("UPDATE `shops` SET `deleted` = 1 WHERE `id` = :id AND `seller_id` = :seller_id");
            $update->execute(['id' => $shop_id, 'seller_id' => $user['id']]);
            header('Location: index.php?deleted=1');
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование магазина – TradeStorm</title>
    <link rel="stylesheet" href="../css/registration_page.css">
    <style>
        .form-wrapper { max-width: 600px; }
        .back-link { display: inline-block; margin-top: 20px; }
        /* Маленькая красная кнопка удаления */
        .delete-btn-small {
            background: #dc3545;
            color: white;
            border: none;
            padding: 4px 12px;
            font-size: 0.8em;
            border-radius: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .delete-btn-small:hover {
            background: #b52d3a;
        }
        .bottom-actions {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .bottom-actions .left {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .bottom-actions .right a {
            color: #007bff;
            text-decoration: none;
        }
        .bottom-actions .right a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="form-wrapper">
        <h2>Редактирование магазина</h2>
        <p><strong>Client ID:</strong> <?= htmlspecialchars($shop['client_id']) ?></p>
        <p><strong>Название:</strong> 
            <?php 
            $info = post_with_data_ozon($shop['ozon_token'], $shop['client_id'], "", "v1/seller/info");
            if ($info && isset($info['company']['name'])) {
                echo htmlspecialchars($info['company']['name']);
            } else {
                echo "Нет данных";
            }
            ?>
        </p>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success-message" style="color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                Токен успешно обновлён!
            </div>
        <?php endif; ?>

        <!-- Форма обновления токена -->
        <form action="#" method="POST">
            <input type="hidden" name="action" value="update_token">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            <div class="form-group">
                <label for="ozon_token">Ozon Token (API-ключ):</label>
                <input type="text" id="ozon_token" name="ozon_token" required 
                       value="<?= htmlspecialchars($shop['ozon_token']) ?>" 
                       placeholder="Новый токен с правами Report и Product read-only">
            </div>
            <button type="submit">Сохранить токен</button>
        </form>

        <!-- Блок с кнопкой удаления и ссылкой назад -->
        <div class="bottom-actions">
            <div class="left">
                <form action="#" method="POST" onsubmit="return confirm('Вы уверены, что хотите удалить этот магазин? Это действие можно отменить только через поддержку.');">
                    <input type="hidden" name="action" value="delete_shop">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <button type="submit" class="delete-btn-small">🗑 Удалить</button>
                </form>
            </div>
            <div class="right">
                <a href="index.php">← Вернуться к списку</a>
            </div>
        </div>
    </div>
</body>
</html>