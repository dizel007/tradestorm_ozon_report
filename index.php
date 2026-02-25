<?php
require_once("main_info.php");
require_once("vendor/autoload.php");
try {
    $pdo = new PDO('mysql:host=' . $host . ';dbname=' . $db . ';charset=utf8', $user, $password);
    $pdo->exec('SET NAMES utf8');
} catch (PDOException $e) {
    print "Has errors: " . $e->getMessage();
    die();
}

ob_start();
echo <<<HTML
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TradeStorm - аналитика маркетплейсов</title>
    <link rel="stylesheet" href="css/index_page.css">
</head>
HTML;



// ************* Проверяем введен ли токен и ИДКлиента
// Если есть токен и ИД клиетна то проверяем его 
$priznak_nalichia_token = 0;
if ((isset($_POST['token'])) && (isset($_POST['client_id']))) {
    $token = htmlspecialchars($_POST['token']);
    $client_id =  htmlspecialchars($_POST['client_id']);
    $date_now = date('Y-m-d');
    $priznak_nalichia_token = 1;
}

// Если есть токе и илклиент то проверяем есть ли роль "РЕПОРТС"
if ($priznak_nalichia_token == 1) {
    $ozon_link = 'v3/finance/transaction/list';
    $send_data = array(
        "filter" => array(
            "date" => array(
                "from" => $date_now . "T00:00:00.000Z",
                "to" => $date_now . "T00:00:00.000Z"
            ),
            "operation_type" => [],
            "posting_number" => "",
            "transaction_type" => "all"
        ),
        "page" => 1,
        "page_size" => 1
    );
    $send_data = json_encode($send_data);
    $http_code = send_query_on_ozon($token, $client_id, $send_data, $ozon_link);
    // echo "<br>11111111111 http_code = " . $http_code;
    if (intdiv($http_code, 100) > 2) {
        echo <<<HTML
    <div class="alarm_text">Токен или ID_клиента не верны (проверьте роль Report)</div>
    HTML;
    } else {
        /// если обмен прошел полодительно то увеличиваем признака
        $priznak_nalichia_token ++;
    }
    // проверяем если ли роль токена  PRODUCTS ONLY
        $ozon_link = "v5/product/info/prices";
        $send_data =  array(
        "cursor" => "",
        "filter" => array ("visibility" => "ALL",),
        "limit" => 1
        );
    $send_data = json_encode($send_data);
    $http_code = send_query_on_ozon($token, $client_id, $send_data, $ozon_link);
    // echo "222222222 http_code = " . $http_code;
    if (intdiv($http_code, 100) > 2) {
        echo <<<HTML
    <div class="alarm_text">Токен или ID_клиента не верны (проверьте роль Product read-only)</div>
    HTML;
    } else {
        /// если обмен прошел полодительно то увеличиваем признака
        $priznak_nalichia_token ++;
    }
}




if ($priznak_nalichia_token == 3) {;
// Если токен рабочий, то создаем папки и уходим на добычу информации
$file_client_path =  '!cache/' . $client_id;
try {
    createDirectoryIfNotExists($file_client_path);
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage();
}
// кладем токен и ИД
file_put_contents($file_client_path . "/token.txt", $token);
file_put_contents($file_client_path . "/client_id.txt", $client_id);
$secret_client_id = base64_encode('' . $client_id);

// записываем токен и илклиента в БД
// сначала проверяем есть ли такой идклиента
$check = $pdo->prepare("SELECT * from `tokens`WHERE id_clt_base64 =:id_clt_base64");
$check->execute(array("id_clt_base64" => $secret_client_id));
$arr_token = $check->fetch(PDO::FETCH_ASSOC);

// смотрим есть ли такой клиент айди если нет то добавляем его
if (!isset($arr_token['id_clt_base64'])) {
    // добавляем новый токен
    $sth = $pdo->prepare("INSERT INTO `tokens` SET `ozon_token` = :ozon_token, `id_client` = :id_client, 
                                                `id_clt_base64` = :id_clt_base64,  `date` = :date");
    $sth->execute(array(
        'ozon_token' => $token,
        'id_client' => $client_id,
        'id_clt_base64' => $secret_client_id,
        'date' => date('Y-m-d H:i:m')
    ));
    // письмецо на почту 
    send_many_emails('dizel007@yandex.ru', 'Кто то тестанул тейдштром', 'новый клиент: ' . $client_id, $mail_for_send_letter, $mail_pass);
} else {
    // смотрим совпадает ли старый токен с новым, еесли нет то обновляем его
    if (($arr_token['ozon_token'] !== $token)  && ($arr_token['id_client'] == $client_id)) {
        $sth = $pdo->prepare("UPDATE `tokens` SET  `ozon_token`= :ozon_token WHERE `id_client` =:id_client");
        $sth->execute(array(
            "ozon_token" => $token,
            "id_client" => $client_id
        ));
    }
}
header('Location: ozon_report?clt='.$secret_client_id, true, 301);
exit();
}






echo <<<HTML
<body>
    <!-- Кнопка скачивания PDF -->
    <div class="pdf-download-container">
        <a href="files/doc1.pdf" download class="pdf-download-btn">
            <svg class="pdf-icon" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
            </svg>
            Скачать инструкцию по подключению(PDF)
        </a>
    </div>
    <!-- Кнопка скачивания PDF -->
    <div class="pdf-download-container2">
        <a href="files/doc2.pdf" download class="pdf-download-btn">
            <svg class="pdf-icon" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
            </svg>
            Скачать инструкцию по работе (PDF)
        </a>
    </div>
    <h2>Форма инициализации</h2>
    
    <form action="#" method="POST">
        <!-- Первый параметр -->
        <div class="form-group">
            <label for="param1">ozon_client_id: </label>
            <input type="text" id="param1" name="client_id" required 
                   placeholder="ozon_client_id">
        </div>

        <!-- Второй параметр -->
        <div class="form-group">
            <label for="param2">ozon_token:</label>
            <input type="text" id="ozon_token" name="token" required 
                   placeholder="ozon_token">
        </div>

        <button type="submit">Отправить</button>
    </form>
    <div class="instruction"> 
        <!-- <p> -->
            <a class ="instruction_link" href ="https://seller.ozon.ru/app/settings/api-keys" target="_blank">  ссылка в личный кабинет озон</a>
        <!-- </p> -->
    <p  class="instruction_text">
        * Для получения отчетов требуется сгенерировать ключ (ozon_token) с типом токена <b>Product read-only</b> и <b>Report</b>. Ключ отобразится только 1 раз. С типом токена Report, можно только смотреть отчеты озона, никаких изменений в кабинете произвести не получится
    </p>
    <!-- <img class ="instruction_pics" src="pics/ozon-token.jpg" alt="альтернативный текст"> -->
    </div>
</body>
</html>
HTML;


/*******************************************************************
 * функция взаимодейсвтия с озоном
 /********************************************************************/

function send_query_on_ozon($token, $client_id, $send_data, $ozon_dop_url)
{

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
    $res = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Получаем HTTP-код

    // $res = json_decode($res, true);
    // echo "<pre>";
    // print_r($res);
    return ($http_code);
}


/*******************************************************************
 * функция которая созает папку, если ее нет
 /********************************************************************/

function createDirectoryIfNotExists($path)
{
    // Проверяем, существует ли папка
    if (!is_dir($path)) {
        // Пытаемся создать папку
        if (mkdir($path, 0777, true)) {
            return true;
        } else {
            throw new Exception("Не удалось создать папку: $path");
        }
    }
    return true;
}
