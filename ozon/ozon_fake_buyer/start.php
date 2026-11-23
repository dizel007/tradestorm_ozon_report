<?php
require_once("ozon_authorization.php");

// 1. Подключение к БД
try {
    $pdo = new PDO('mysql:host=' . $host . ';dbname=' . $db . ';charset=utf8', $user, $password);
    $pdo->exec('SET NAMES utf8');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// 2. Получаем данные авторизации
[$token, $client_id, $seller_id, $shop_id] = ozon_authorization($pdo, '../../registration');

// Проверка, что данные получены
if (empty($token) || empty($client_id)) {
    die("Ошибка: не удалось получить токен или client_id. Проверьте авторизацию.");
}

// 3. Закрываем сессию, чтобы браузер не ждал (важно для VDS)
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}

$worker = __DIR__ . DIRECTORY_SEPARATOR . 'worker.php';

// =====================================================================
// ВАРИАНТ ДЛЯ WINDOWS (XAMPP)
// =====================================================================
if (PHP_OS_FAMILY === 'Windows') {
    $phpBinary = 'C:\\xampp\\php\\php.exe';
    
    // Передаем параметры как аргументы командной строки
    $args = sprintf(
        '%s %s %s %s',
        escapeshellarg($token),
        escapeshellarg($client_id),
        escapeshellarg($seller_id),
        escapeshellarg($shop_id)
    );
    
    $cmd = sprintf('start /B "" "%s" "%s" %s > NUL 2>&1', $phpBinary, $worker, $args);
    pclose(popen($cmd, 'r'));
    
    // echo "Запущено в фоне (Windows XAMPP)";
    // exit;
}

// =====================================================================
// ВАРИАНТ ДЛЯ LINUX (VDS / NETANGELS)
// =====================================================================
$phpBinary = '/usr/bin/php7.4.33';
$phpIni    = '/home/web/etc/php/php.ini'; 
$logFile   = __DIR__ . '/worker.log';

// Передаем данные через переменные окружения (самый надежный способ для Linux)
$cmd = sprintf(
    'export TOKEN=%s; export CLIENT_ID=%s; export SELLER_ID=%s; export SHOP_ID=%s; /usr/bin/nohup %s -c %s "%s" > %s 2>&1 & echo $!',
    escapeshellarg($token),
    escapeshellarg($client_id),
    escapeshellarg($seller_id),
    escapeshellarg($shop_id),
    $phpBinary,
    $phpIni,
    $worker,
    $logFile
);

// Выполняем и получаем PID процесса
$pid = shell_exec($cmd);

if (empty(trim($pid))) {
    // echo "ОШИБКА: процесс не запустился. Возможно, shell_exec запрещен хостингом.";
} else {
    // echo "Процесс запущен в фоне (VDS). PID: " . trim($pid);
}
// exit;