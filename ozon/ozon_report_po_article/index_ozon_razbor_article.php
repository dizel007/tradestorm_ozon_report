<?php
/**********************************************************************************************************
 *     ***************    Получаем массив всех транзакций
*********************************************************************************************************/

// require_once "../connect_db.php";
require_once "../mp_functions/ozon_api_functions.php";
// require_once "../pdo_functions/pdo_functions.php";
require_once "../_libs_ozon/function_ozon_reports.php"; // массив с себестоимостью товаров
require_once "../_libs_ozon/sku_fbo_na_fbs.php"; // массив с себестоимостью товаров
// формируем признак принудительного обновления данных 

$need_SKU = '';
if (isset($_GET['data'])) {
    $decoded_data = base64_decode(urldecode($_GET['data']));
     parse_str($decoded_data, $params);
     $client_id = $params['clt'];
     $need_SKU = $params['article'];
     $file_name_ozon_small = $params['file_name_ozon_small'];
     $file_name_ozon = "../!cache" ."/".$client_id."/".$client_id. $file_name_ozon_small.".json";
     $token = file_get_contents('../!cache/'.$client_id."/token.txt");
     $prod_array = json_decode(file_get_contents($file_name_ozon), true);
} else {
    die('Не нашли файл с данными');
}



echo <<<HTML
<head>
<link rel="stylesheet" href="../css/main_ozon.css">
</head>
HTML;




/*******************************************************************************************
 *  ***** ДАННЫЕ ПОЛУЧЕНЫ ОТСЮДА НАЧИНАЕМ ИХ ОБРАБАТЫВАТЬ ******************************
 *******************************************************************************************/

// echo "<pre>";
// print_r($prod_array);
// die();
// Формируем скозвной массив из элеметов для разбора (убираем вложенность)
foreach ($prod_array as $arr_temp) {
    foreach ($arr_temp as $add) 
    {$array_MINI[] = $add;}
 }
unset($prod_array);

// НАчинаем перебирать перечень элементов по типу 
foreach ($array_MINI as $item) {
    
    // если в номере заказа нет тире , то переносим этот массив в массив без заказа
    // создаем массив затрат для нет номера заказа
    if (!strpos( $item['posting']['posting_number'], '-')) {
         $array_without_posting_number[$item['type']][] = $item; // 
        
    } else {
    // создаем массив, где есть номер заказа


    // if      (($item['type']) == 'orders') {require "parts_article/orders_article.php";}
    // elseif  (($item['type']) == 'returns') {require "parts_article/returns_article.php";}
    // elseif  (($item['type']) == 'other')    {require "parts_article/other_article.php";}
    // elseif  (($item['type']) == 'services') {require "parts_article/servici_article.php";}
    // elseif  (($item['type']) == 'compensation') {require "parts_article/uderzhania_article.php";}


    $arr_type_items_WITH_POSTING_NUMBER[$item['type']][] = $item;
    }
}


// echo "<pre>";

// print_r($arr_type_items_WITH_POSTING_NUMBER);

echo "<br>ВСЕГО = ". count($array_MINI) ."<br>";
// Выводм количество элементво каждого массивы
foreach ($array_without_posting_number as $key=>$temp_3) {
     echo "Количество элементов по тратам (БЕЗ НОМЕРА ЗАКАЗА) $key = ".count($temp_3)."<br>";
   } 
// Выводм количество элементво каждого массивы
foreach ($arr_type_items_WITH_POSTING_NUMBER as $key=>$temp_3) {
     echo "Количество элементов по тратам (С НОМЕРОМ ЗАКАЗА) $key = ".count($temp_3)."<br>";
   } 

// print_r($arr_type_items_WITH_POSTING_NUMBER);
// print_r($arr_type_items_WITH_POSTING_NUMBER);
// die();
   echo "К****************************************** ZZZZZZZZZZZZZZZZZZZZZZZZZZZ  <br>";
require_once "razbor_dannih_article.php";

die();


/***************** ФУНКЦИИ ПОШЛИ **********************************************************************************************
 **********************************************************************************************************************/
function print_one_string_in_table($print_item, $parametr, $color_class = '')
// Выводит одну строку с данными из массива
{
    if (isset($print_item[$parametr])) {
        echo "<td class=\"$color_class\">" . round($print_item[$parametr], 2) . "</td>";
    } else {
        echo "<td>" . "" . "</td>";
    }
}

function print_two_strings_in_table($print_item, $parametr1, $parametr2, $color_class = '')
// Выводит две строки с данными из массива
{
    if (isset($print_item[$parametr1])) {
        echo "<td class=\"$color_class\">" .  round($print_item[$parametr1], 2) . "<br>" .  round($print_item[$parametr2], 2) . "</td>";
    } else {
        echo "<td>" . "-" . "</td>";
    }
}

//// НАХОДИМ ЦЕНУ ИЗ БД (минимальную или нормальную)
function get_min_price_ozon($otchet_article, $arr_all_nomenklatura, $type_price)
{
    foreach ($arr_all_nomenklatura as $nomenclatura) {

        // echo "$otchet_article)  ({$nomenclatura['main_article_1c']}";
        $price = 0;
        if (mb_strtolower($otchet_article) == mb_strtolower($nomenclatura['main_article_1c'])) {

            if ($type_price == 'min') {
                $price = $nomenclatura['min_price'];
            }
            if ($type_price == 'norm') {
                $price = $nomenclatura['main_price'];
            }
            break;
        }
    }


    return $price;
}


function make_posting_number ($posting_temp_number) {
// Функуия вовзращает номер Заказа (удаляю из него номер отправления)
    if ($posting_temp_number == '') {
        return false;
    }
$pos1 = strpos($posting_temp_number, '-');
$pos2 = strpos($posting_temp_number, '-', $pos1 + strlen('-'));
if ($pos2 > 0) {
$pos4  = mb_substr($posting_temp_number, 0, $pos2);
} else {
    $pos4 = $posting_temp_number;
}
return $pos4;

}
