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

if (isset($_POST['dateFrom'])) {
    $date_from = $_POST['dateFrom'];
} else {
    $date = date('Y-m-d');
    $day = '01';
    $month = date('m', strtotime($date));
    $year = date('Y', strtotime($date));
    $date_from = $year.'-'.$month.'-'.$day;
    $priznak_date = 0; 
    // echo "$date_from";
}

if (isset($_POST['dateTo'])) {
    $date_to = $_POST['dateTo'];
} else {
    $date_to = date('Y-m-d');
}


echo <<<HTML
<div class="center_block"> Выберите дату начала запроса и дату окончания (промежоток запроса не более 32 дней) </div>
<div class="center_block">
 
 <form class="date-form" action="#" method="post">
     
        <div class="date-group">
            <label for="start-date">С:</label>
           <input required type="date" name = "dateFrom" value="$date_from">
        </div>
        <div class="date-group">
            <label for="end-date">По:</label>
            <input required type="date" name = "dateTo" value="$date_to">
        </div>
        
        <input hidden type="text" name = "token" value="$token">
        <input hidden type="text" name = "client_id" value="$client_id">

        <button type="submit">Запросить данные</button>

HTML;
if ($priznak_date == 0)  {
    die ('');
 } 

echo "</form></div>";


$file_name_ozon = "../!cache" ."/".$client_id."/".$client_id."_(".date('Y-m-d').")".".json";


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
$row_count = $res['result']['row_count'];
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
file_put_contents($file_name_ozon,json_encode($prod_array, JSON_UNESCAPED_UNICODE));




$prod_array = json_decode(file_get_contents($file_name_ozon) ,true);


echo "<pre>";
// print_r ($prod_array);

// die(); ///////////////////////// DELETEE ********* 1597373292 ***********

require_once "razbor_dannih.php";

die();


