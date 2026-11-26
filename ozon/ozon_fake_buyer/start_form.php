<?php


$date_from = $_GET['dateFrom'] ?? date('Y-m-d');
$date_to   = $_GET['dateTo']   ?? date('Y-m-d');

 $stop_priznak_net_magazina = 0;

$reportHref = 'search_fake_buyer.php?'.'data' => $url_data ?? ''],
    '',
    '&',
    PHP_QUERY_RFC3986
);


echo <<<HTML
<head>
<link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/start_form.css">

</head>
<body>

<h1 class="page-title">Ищем покупателей которые возвращают товары</h1>

<form  action="#" method="get">
   <label>Магазин</label>
      <b>$shop_name</b>

   <label>дата начала</label>
   <input hidden type="text" name="data" value="$url_data">
   <input required type="date" name = "dateFrom" value="$date_from">
   <label>дата окончания</label>
   <input required type="date" name = "dateTo" value="$date_to">
  
   <input type="submit"  value="START">
</form>
<div style="max-width: 1000px; margin: 20px auto 0; text-align: right;">
   <a href="search_fake_buyer.php?data=$reportHref" class="report-link" target="_blank">
    Поиск по ID покупателей с возвратами
</a>
</div>

HTML;


// die();
if (($date_from == false) or ($date_to == false)) {
   die ('Нужно выбрать даты');
 } 

 if ($stop_priznak_net_magazina == 999) {
   die ('');
 } 
