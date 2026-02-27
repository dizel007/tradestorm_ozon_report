<?php
/**********************************************************************************************************
 *     ***************    Получаем массив всех транзакций
*********************************************************************************************************/
// echo  "ПРОШЛИ КОННЕКТ<br>";
require_once ("../main_info.php");
require_once "../mp_functions/ozon_api_functions.php";


      try {  
        $pdo = new PDO('mysql:host='.$host.';dbname='.$db.';charset=utf8', $user, $password);
        $pdo->exec('SET NAMES utf8');
        } catch (PDOException $e) {
          print "Has errors: " . $e->getMessage();  die();
        }


$queryString = $_SERVER['QUERY_STRING'] ?? '';

// Разобрать вручную
parse_str($queryString, $params);
// echo "<pre>";
// print_r($params);
// находим ID клиента
if (isset($params['clt']) AND ($params['clt'] !='')) {
    $secret_client_id = $params['clt'];

    $sth = $pdo->prepare("SELECT * from `tokens` WHERE id_clt_base64 =:id_clt_base64");
    $sth->execute(array("id_clt_base64" => $secret_client_id));
    $arr_tokens = $sth->fetch(PDO::FETCH_ASSOC);
    
    // если не нашли клиетна в БД то уходим на начало
    if (!isset($arr_tokens['id_client'])) {
      header('Location: ../');  
    } 
    $client_id = $arr_tokens['id_client'];
    $token = $arr_tokens['ozon_token'];
    $secret_client_id = $arr_tokens['id_clt_base64'];

     
} else {
 header('Location: ../');

    die('Не нашли файл с данными');
}

$priznak_date = 1; 
// Настраиваем дату начала отпроса
if (isset($params['dateFrom'])) { $date_from = $params['dateFrom'];} 
else {
    $date = date('Y-m-d');
    $day = '01';
    $month = date('m', strtotime($date));
    $year = date('Y', strtotime($date));
    $date_from = $year.'-'.$month.'-'.$day;
    $priznak_date = 0; 
    
}

// Настраиваем дату окончания
if (isset($params['dateTo'])) {$date_to = $params['dateTo'];} else {$date_to = date('Y-m-d');}

// Настраиваем тип сортировки если он есть 
if (isset($params['type_sort'])) {

    $type_sort = base64_decode($params['type_sort']);
 
    // Удаляем этот параметр
    unset($params['type_sort']);
    // формируем Query строку без этого парамаетра, чтобы он постоянно не прилипал к строке при кажой сортировке
    $queryString = http_build_query($params);



} else {
   $type_sort = ''; 
}

//// Отрисовываем форму вводы ДАТ
echo <<<HTML

<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/form_dates.css">
    <title>TradeStorm - аналитика</title>
    
</head>
<body>
    <div class="table-container">
    <div class="form-container">
        <form id="dateForm">
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
             </div>
            <input hidden type="text" id = "clientId" value="$secret_client_id">
        </form>
       </div>
    </div>
 <script src="css/script.js" type="text/javascript"></script>

HTML;

// die();
if ($priznak_date == 0)  {die ('');} 

// формируем название папки и файла
$file_name_ozon = "../!cache" ."/".$client_id."/".$client_id."_(".date('Y-m-d').")".".json";
$file_name_ozon_small = "_(".date('Y-m-d').")";


// Непосредственный запрос данных с озона и сохранение данных в файл
//*********************************************************************************************************************** */
// если вернулись сюда с параметров изменения типа сортировки массива, то новые данные не запрашиваем
if ($type_sort == '') {
   
        $prod_array = query_report_data_from_api_ozon($token, $client_id, $date_from, $date_to);
        file_put_contents($file_name_ozon,json_encode($prod_array, JSON_UNESCAPED_UNICODE));
        if ($prod_array === false) {die('НЕТ ДАННЫХ для выдачи');} // Если нам ничего ОЗон не вернул
}
//*********************************************************************************************************************** */

// Берем из БД себестоимость и желаемую цену 
require_once "get_sebestoimost.php";

// берем данные из файла
$prod_array = json_decode(file_get_contents($file_name_ozon) ,true);

// echo "<pre>";
// print_r ($prod_array);

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
    // echo "<pre>";
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
if (isset($prod_array)) {return  $prod_array;}
else {return false;}
}
