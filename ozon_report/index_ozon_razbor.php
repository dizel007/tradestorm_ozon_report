<?php
/**********************************************************************************************************
 *     ***************    Получаем массив всех транзакций
*********************************************************************************************************/
/// $toen = b4371bbd-08fa-4ce0-ab50-362dfa656c72
require_once "../mp_functions/ozon_api_functions.php";

if (isset($_GET['clt'])) {
    $client_id = $_GET['clt'];
    $token = file_get_contents('../!cache/'.$client_id."/token.txt");
  
} else {
    die('Не нашли файл с данными');
}



echo <<<HTML
<head>
    <link rel="stylesheet" href="css/form_dates.css">
   
</head>

HTML;

$priznak_date = 1; 

if (isset($_GET['dateFrom'])) {
    $date_from = $_GET['dateFrom'];
} else {
    $date = date('Y-m-d');
    $day = '01';
    $month = date('m', strtotime($date));
    $year = date('Y', strtotime($date));
    $date_from = $year.'-'.$month.'-'.$day;
    $priznak_date = 0; 
    // echo "$date_from";
}

if (isset($_GET['dateTo'])) {
    $date_to = $_GET['dateTo'];
} else {
    $date_to = date('Y-m-d');
}


echo <<<HTML

<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TradeStorm - контролируй свои продажы</title>
    
</head>
<body>
    <div class="form-container">
        <!-- <h1 class="form-title">Выберите период</h1> -->
        <form id="dateForm">
          <!-- <form id="dateForm" > -->

            <div class="form-content">
                <div class="date-fields">
                    <div class="date-group">
                        <label for="startDate" class="date-label">Начальная дата</label>
                        <input type="date" id="startDate" class="date-input" value="$date_from" required >
                    </div>

                    

                    <div class="date-group">
                        <label for="endDate" class="date-label">Конечная дата</label>
                        <input type="date" id="endDate" class="date-input" value="$date_to" required>
                  </div>
                </div>
                
                 <div class="date-group">
                        <button type="submit" class="submit-btn">Запросить данные</button>
                 </div>

                 <div class="actions">
                    <a href="https://seller.ozon.ru/app/finances/balance?tab=IncomesExpenses" target="_blank" class="link-btn">Ссылка Озон Выплаты</a>
                </div>

            </div>
            <input hidden type="text" id = "clientId" value="$client_id">
        </form>
    </div>

    <!-- Дополнительный контент для демонстрации -->
    <!-- <div class="content">
        <h2>Остальной контент страницы</h2>
        <p>Форма с кнопкой и ссылкой сбоку, адаптированная для монитора и телефона</p>
    </div> -->

 
 <script src="css/script.js" type="text/javascript"></script>
</body>
</html>

HTML;

if ($priznak_date == 0)  {
    die ('');
 } 

echo "</form></div>";

// формируем название папки и файла
$file_name_ozon = "../!cache" ."/".$client_id."/".$client_id."_(".date('Y-m-d').")".".json";

// Непосредственный запрос данных с озона и сохранение данных в файл
// $prod_array = query_report_data_from_api_ozon($token, $client_id, $date_from, $date_to);
// file_put_contents($file_name_ozon,json_encode($prod_array, JSON_UNESCAPED_UNICODE));




// берем данные из файла
$prod_array = json_decode(file_get_contents($file_name_ozon) ,true);

// echo "<pre>";
// print_r ($prod_array);

// die(); ///////////////////////// DELETEE ********* 1597373292 ***********

require_once "razbor_dannih.php";

die();

function query_report_data_from_api_ozon($token, $client_id, $date_from, $date_to) {
    // echo "Период запроса с ($date_from) по  ($date_to)<br>";
$ozon_link = 'v3/finance/transaction/list';
$send_data = array(
    "filter" => array(
        "date" => array (
            "from" => $date_from."T00:00:00.000Z",
            "to"=> $date_to."T00:00:00.000Z"
    ),
        "operation_type" => [],
        "posting_number" => "",
        "transaction_type" => "all"
    ),
    "page" => 1,
    "page_size" => 1000
);


$send_data = json_encode($send_data);
$res = send_injection_on_ozon($token, $client_id, $send_data, $ozon_link );

// если ошибка при обмене то выводим е
if (isset($res['message'])) {
    echo "<pre>";
    print_r($res);
    die('ОШИБКА ПРИ ЗАПРОСЕ');
}

$page_count = $res['result']['page_count'];
// $row_count = $res['result']['row_count'];
for ($i=1; $i <=$page_count; $i ++) {
    $send_data = array(
        "filter" => array(
            "date" => array (
                "from" => $date_from."T00:00:00.000Z",
                "to"=> $date_to."T00:00:00.000Z"
        ),
            "operation_type" => [],
            "posting_number" => "",
            "transaction_type" => "all"
        ),
        "page" => $i,
        "page_size" => 1000
    );
    $send_data = json_encode($send_data);
    $res = send_injection_on_ozon($token, $client_id, $send_data, $ozon_link );
    $prod_array[] = $res['result']['operations'];

}
return  $prod_array;
}
