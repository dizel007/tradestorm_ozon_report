<?php

// Настраиваем дату начала отпроса
if (isset($_GET['dateFrom'])) {
    $date_from = $_GET['dateFrom'];
} else {
    $date = date('Y-m-d');
    $day = '01';
    $month = date('m', strtotime($date));
    $year = date('Y', strtotime($date));
    $date_from = $year . '-' . $month . '-' . $day;
    $priznak_date = 0;
}

// Настраиваем дату окончания
if (isset($_GET['dateTo'])) {
    $date_to = $_GET['dateTo'];
} else {
    $date_to = date('Y-m-d');
}


//// Отрисовываем форму вводы ДАТ
echo <<<HTML

<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="css/form_dates.css?v=<?php echo filemtime('css/form_dates.css'); ?>">

    <title>Отчет по заказам OZON</title>
</head>
<body>
    <div class="table-container">
         <div class="info_about_client">$seller_shop_name_from_ozon</div>
  
    <div class="form-container">
        <form id="dateForm" method="get">

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
            <input hidden type="text" id="url_data" value="$url_data">

        </form>
       </div>
    </div>

<script src="js/script.js?v= <?php echo filemtime('js/script.js'); ?>"></script>

HTML;

if (!isset($_GET['dateFrom'], $_GET['dateTo'])) {
    die('');
}
