<?php
//0a2679cf-74cb-43eb-b042-94997de5f748
//1724451
echo <<<HTML

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Форма ввода параметров</title>
    <link rel="stylesheet" href="css/index_page.css">
</head>
HTML;

if ((isset($_POST['token'])) && (isset($_POST['client_id']))) {
 $token = htmlspecialchars($_POST['token']);
 $client_id =  htmlspecialchars($_POST['client_id']);
 $date_now = date('Y-m-d');
 $ozon_link = 'v3/finance/transaction/list';
 $send_data = array(
    "filter" => array(
        "date" => array (
            "from" => $date_now."T00:00:00.000Z",
            "to"=> $date_now."T00:00:00.000Z"
    ),
        "operation_type" => [],
        "posting_number" => "",
        "transaction_type" => "all"
    ),
    "page" => 1,
    "page_size" => 1000
);
$send_data = json_encode($send_data);
$http_code = send_query_on_ozon($token, $client_id, $send_data, $ozon_link );

   if (intdiv($http_code,100) > 2) {

echo <<<HTML
<div class="alarm_text">Токен или ID_клиента не верны</div>
HTML;

    } else {
        // Если токен рабочий, то создаем папки и уходим на добычу информации
            $file_client_path =  '!cache/'.$client_id;
            try {
                createDirectoryIfNotExists($file_client_path);
            } catch (Exception $e) {
                echo "Ошибка: " . $e->getMessage();
            }
            // кладем токен и ИД
        file_put_contents($file_client_path."/token.txt",$token);
        file_put_contents($file_client_path."/client_id.txt",$client_id);
        $secret_client_id = base64_encode(''.$client_id);
        header('Location: ozon_report/index_ozon_razbor.php?clt='.$secret_client_id, true, 301);
        exit();
    }
}





echo <<<HTML


<body>
    <h2>Форма инициализации</h2>
    
    <form action="#" method="POST">
        <!-- Первый параметр -->
        <div class="form-group">
            <label for="param1">ozon_client_id:</label>
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
</body>
</html>
HTML;


/*******************************************************************
 * функция взаимодейсвтия с озоном
 /********************************************************************/

function send_query_on_ozon($token, $client_id, $send_data, $ozon_dop_url ) {
 
	$ch = curl_init('https://api-seller.ozon.ru/'.$ozon_dop_url);
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

	curl_close($ch);
	
	$res = json_decode($res, true);

  

   
    return ($http_code);	

}


/*******************************************************************
 * функция которая созает папку, если ее нет
 /********************************************************************/

function createDirectoryIfNotExists($path) {
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
