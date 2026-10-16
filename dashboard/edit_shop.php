<?php

require_once("../main_info.php");
require_once("../_no_git/secret_info.php");
require_once("../vendor/autoload.php");
require_once("../registration/functions.php");

use App\Ozon\OzonClient;
use App\Ozon\Service\FinanceService;


// echo "<pre>";
// print_r($_POST);

// require_once("../ozon/mp_functions/ozon_api_functions.php");
// require_once("../registration/functions.php");

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
    header('Location: ../index.php');
    exit();
}
// получаем и декобируем _GET данные
$shop_id = isset($_GET['data']) ? decryptData($_GET['data']) : 0;

if ($shop_id <= 0) {
    die("Неверный ID магазина.");
}

// $now = date('Y-m-d H:i:s');
$stmt = $pdo->prepare("SELECT * FROM `shops` WHERE `id` = :id AND `seller_id` = :seller_id AND `deleted` = 0");
$stmt->execute(['id' => $shop_id, 'seller_id' => $user['id']]);
$shop = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$shop) {
    die("Магазин не найден, удалён или срок подписки истёк. Редактирование недоступно.");
}

$shop_token = maskString ($shop['ozon_token'], 3, 3, '*');
$client_id = $shop['client_id'];
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

//=======================================================================================================================
// Проверка токен в Озоне
                $priznak = 0;
                $today = date('Y-m-d');
                $ozon    = new OzonClient($new_token, $client_id);
                $finance = new FinanceService($ozon);

                $temp_priz = $finance->getAccrualsByDay( $today,  $today, '');
                if (isset($temp_priz['last_id'])) {$priznak++;}

                    // Проверка Product
                    $data = ["cursor" => "","filter" => ["visibility" => "ALL"], "limit" => 1];
                    $temp_priz_2 = $ozon->post("v5/product/info/prices", $data);
                if (isset($temp_priz_2['items'][0])) {$priznak++;}
            
//=======================================================================================================================
            
            
                if ($priznak == 2) {
                    $update = $pdo->prepare("UPDATE `shops` SET `ozon_token` = :token WHERE `id` = :id");
                    $update->execute(['token' => $new_token, 'id' => $shop_id]);
                    $success = true;
                    $shop['ozon_token'] = $new_token;
                } else {
                    $error = "Неверный токен: требуются токен Admin read-only.";
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

$ozon = new OzonClient($shop['ozon_token'], $shop['client_id']);
$info = $ozon->post("v1/seller/info");

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование магазина – TradeStorm</title>
    <link rel="stylesheet" href="../css/registration_page.css">
    <link rel="stylesheet" href="css/edit_shop.css">
    

</head>
<body>
    <div class="form-wrapper">
        <h2>Редактирование магазина</h2>
        <p><strong>Client ID:</strong> <?= htmlspecialchars($shop['client_id']) ?></p>
        <p><strong>Название:</strong> 
             <?php 
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
                       value="<?= htmlspecialchars($shop_token) ?>" 
                       placeholder="Новый токен с правами Admin read-only">
            </div>
            <button type="submit">Обновить токен</button>
        </form>

        <!-- Блок с кнопкой удаления и ссылкой назад -->
        <div class="bottom-actions">
            <div class="left">
                <form action="#" method="POST" onsubmit="return confirm('Вы уверены, что хотите удалить этот магазин?');">
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




<?php
/*************************************************************************************
 * скрыть все символы между первым и последним, но оставить тире
 *************************************************************************************/


function maskString(string $str, int $visibleFirst = 1, int $visibleLast = 1, string $maskChar = '*'): string
{
    $len = strlen($str);
    if ($len <= $visibleFirst + $visibleLast) {
        return $str; // если строка короткая – ничего не маскируем
    }

    $result = '';
    for ($i = 0; $i < $len; $i++) {
        $char = $str[$i];
        // Дефисы всегда оставляем
        if ($char === '-') {
            $result .= '-';
        } 
        // Первые $visibleFirst и последние $visibleLast символов оставляем
        elseif ($i < $visibleFirst || $i >= $len - $visibleLast) {
            $result .= $char;
        } 
        // Всё остальное маскируем
        else {
            $result .= $maskChar;
        }
    }
    return $result;
}
