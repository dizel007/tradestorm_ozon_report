<?php
// ************** DASHBOARD ******************************************
// ************** DASHBOARD ******************************************
// ************** DASHBOARD ******************************************
// ************** DASHBOARD ******************************************

require_once("../main_info.php");
require_once("../_no_git/secret_info.php");
require_once("../vendor/autoload.php");
// require_once("../registration/functions.php");

use App\Ozon\OzonClient;
use App\Ozon\Service\FinanceService;

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

// ---------- Авторизация ----------
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
    header('Location: ../registration');
    exit();
}

if (isset($_GET['logout'])) {
    session_destroy();
    setcookie('remember_token', '', time() - 3600, '/', '', false, true);
    header('Location: ../registration');
    exit();
}

// ---------- Флаги UI ----------
$showDeleted = isset($_GET['show_deleted']) && $_GET['show_deleted'] == 1;
$addNewShop  = isset($_GET['add_shop']);
$error       = null;

// ---------- POST-обработка ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Ошибка безопасности (CSRF). Попробуйте снова.";
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'add_shop') {
            $client_id = trim($_POST['client_id'] ?? '');
            $token     = trim($_POST['token'] ?? '');

            if (empty($client_id) || empty($token)) {
                $error = "Заполните Client ID и Token";
            } else {
                $stmt = $pdo->prepare("SELECT id, deleted FROM `shops` WHERE `seller_id` = :seller_id AND `client_id` = :client_id");
                $stmt->execute(['seller_id' => $user['id'], 'client_id' => $client_id]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    if ($existing['deleted'] == 1) {
                        $subscription_start  = date('Y-m-d H:i:s');
                        $subscription_end    = date('Y-m-d H:i:s', strtotime('+7 days'));
                        $subscription_status = 'trial';
                        $secret_client_id    = base64_encode($client_id);

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
                            'token'          => $token,
                            'id_clt_base64'  => $secret_client_id,
                            'date'           => date('Y-m-d H:i:s'),
                            'start'          => $subscription_start,
                            'end'            => $subscription_end,
                            'status'         => $subscription_status,
                            'id'             => $existing['id']
                        ]);
                        header('Location: ');
                        exit();
                    } else {
                        $error = "Этот магазин уже добавлен в ваш аккаунт и активен.";
                    }
                } else {

//==================================================================================================================
                    $priznak = 0;
                    $today   = date('Y-m-d');
                    $ozon    = new OzonClient($token, $client_id);
                    $finance = new FinanceService($ozon);

                    $temp_priz = $finance->getAccrualsByDay($today, $today, '');
                    if (isset($temp_priz['last_id'])) { $priznak++; }
                    $data         = ["cursor" => "", "filter" => ["visibility" => "ALL"], "limit" => 1];
                    $temp_priz_2  = $ozon->post("v5/product/info/prices", $data);
                    if (isset($temp_priz_2['items'][0])) { $priznak++; }
//==================================================================================================================

                    if ($priznak == 2) {
                        $subscription_start  = date('Y-m-d H:i:s');
                        $subscription_end    = date('Y-m-d H:i:s', strtotime('+7 days'));
                        $subscription_status = 'trial';
                        $secret_client_id    = base64_encode($client_id);

                        $insertShop = $pdo->prepare("INSERT INTO `shops` 
                            (`seller_id`, `client_id`, `ozon_token`, `id_clt_base64`, `date`, `subscription_start`, `subscription_end`, `subscription_status`)
                            VALUES (:seller_id, :client_id, :ozon_token, :id_clt_base64, :date, :start, :end, :status)");
                        try {
                            $insertShop->execute([
                                'seller_id'      => $user['id'],
                                'client_id'      => $client_id,
                                'ozon_token'     => $token,
                                'id_clt_base64'  => $secret_client_id,
                                'date'           => date('Y-m-d H:i:s'),
                                'start'          => $subscription_start,
                                'end'            => $subscription_end,
                                'status'         => $subscription_status
                            ]);
                            header('Location: index.php');
                            exit();
                        } catch (PDOException $e) {
                            if ($e->errorInfo[1] == 1062) {
                                $error = "Этот магазин уже существует (конфликт).";
                            } else {
                                $error = "Ошибка добавления: " . $e->getMessage();
                            }
                        }
                    } else {
                        $error = "Неверный Client ID или Token (Токен должен быть Admin read-only)";
                    }
                }
            }
        } elseif ($action === 'restore') {
            $shop_id = isset($_POST['shop_id']) ? decryptData($_POST['shop_id']) : 0;

            if ($shop_id <= 0) {
                $error = "Неверный ID магазина.";
            } else {
                $stmt = $pdo->prepare("SELECT id FROM `shops` WHERE `id` = :id AND `seller_id` = :seller_id AND `deleted` = 1");
                $stmt->execute(['id' => $shop_id, 'seller_id' => $user['id']]);
                if ($stmt->fetch()) {
                    $update = $pdo->prepare("UPDATE `shops` SET `deleted` = 0 WHERE `id` = :id");
                    $update->execute(['id' => $shop_id]);
                    header('Location: ?restored=1');
                    exit();
                } else {
                    $error = "Магазин не найден или уже активен.";
                }
            }
        }
    }

    if ($error) {
        $addNewShop = false;
    }
}

// ---------- Список магазинов ----------
$clients = [];
$sql = "SELECT * FROM `shops` WHERE `seller_id` = :seller_id";
if (!$showDeleted) {
    $sql .= " AND `deleted` = 0";
}
$stmt = $pdo->prepare($sql);
$stmt->execute(['seller_id' => $user['id']]);
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);

usort($clients, function ($a, $b) {
    return $a['deleted'] - $b['deleted'];
});

$client_email = $user['email'];

foreach ($clients as &$client) {
    $ozon = new OzonClient($client['ozon_token'], $client['client_id']);
    $info = $ozon->post("v1/seller/info");

    if ($info && isset($info['company']['name'])) {
        $client['shop_name'] = $info['company']['name'] . " (" . $info['company']['legal_name'] . ")";
    } else {
        $client['shop_name'] = 'Нет данных';
    }

    $client['is_subscription_active'] = strtotime($client['subscription_end']) > time();

    $endTimestamp    = strtotime($client['subscription_end']);
    $now             = time();
    $daysLeft        = ($endTimestamp - $now) / (60 * 60 * 24);
    $client['days_left'] = $daysLeft;

    $client['is_active']  = ($client['deleted'] == 0 && $client['is_subscription_active']);
    $client['data_clt']   = encryptData($client['id']);
    $client['end_period'] = substr($client['subscription_end'], 0, 10);
    $client['sh_id']      = encryptData($client['id']);
}
unset($client);

// ---------- Рендер ----------
require __DIR__ . '/templates/template_dashboard.php';