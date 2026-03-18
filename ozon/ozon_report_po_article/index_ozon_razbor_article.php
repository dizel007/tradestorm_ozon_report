<?php

/**********************************************************************************************************
 *     ***************    Получаем массив всех транзакций
 *********************************************************************************************************/

require_once("../../main_info.php");
require_once "../mp_functions/ozon_api_functions.php";
// формируем признак принудительного обновления данных 

try {
    $pdo = new PDO('mysql:host=' . $host . ';dbname=' . $db . ';charset=utf8', $user, $password);
    $pdo->exec('SET NAMES utf8');
} catch (PDOException $e) {
    print "Has errors: " . $e->getMessage();
    die();
}



$need_SKU = '';
if (isset($_GET['data'])) {
    $decoded_data = base64_decode(urldecode($_GET['data']));
    parse_str($decoded_data, $params);
// echo "<pre>";
//     print_r($params);
//     die();
    // Достаем токен и ИД клинета
    $mp_article = $params['article'];
    $article_sebest = $params['sebest'];
    $acquiring_sum=$params['acquiring'];
    $acquiring_count=$params['acquiring_count'];

    $dop_uslugi=$params['dop_uslugi'];

   


    $secret_client_id = $params['clt'];

    $sth = $pdo->prepare("SELECT * from `tokens` WHERE id_clt_base64 =:id_clt_base64");
    $sth->execute(array("id_clt_base64" => $secret_client_id));
    $arr_tokens = $sth->fetch(PDO::FETCH_ASSOC);

    // если не нашли клиетна в БД то уходим на начало
    if (!isset($arr_tokens['id_client'])) {
        header('Location: ../');
    }
    $client_id = $arr_tokens['id_client'];
    // $token = $arr_tokens['ozon_token'];
    $secret_client_id = $arr_tokens['id_clt_base64'];

    $need_SKU = $params['sku'];
    $file_name_ozon_small = $params['file_name_ozon_small'];
    $file_name_ozon = "../!cache" . "/" . $client_id . "/" . $file_name_ozon_small . ".json";

    $prod_array = json_decode(file_get_contents($file_name_ozon), true);
} else {
    die('Не нашли файл с данными');
}

echo "<pre>";
print_r($need_SKU);
// print_r($prod_array);
// echo "<br>";

echo <<<HTML
<head>
<link rel="stylesheet" href="../css/main_ozon.css">
</head>
HTML;




/*******************************************************************************************
 *  ***** ДАННЫЕ ПОЛУЧЕНЫ ОТСЮДА НАЧИНАЕМ ИХ ОБРАБАТЫВАТЬ ******************************
 *******************************************************************************************/


foreach ($prod_array as $arr_temp) {
    // Формируем скозвной массив из элеметов для разбора (убираем вложенность)
    foreach ($arr_temp as $add) {
        if (isset($add['items'])) {
            foreach ($add['items'] as $items) {
                /// оставляем только те массивы где есть наше СКУ
                if ($need_SKU == $items['sku']) {
                    $array_MINI[] = $add;
                }
            }
        }
        // Формируем массив где нет товаров, но есть номера заказов -- типа штрафы к заказу
     if ((count($add['items']) == 0) AND (isset($add['posting']['posting_number'])) AND (strpos($add['posting']['posting_number'], '-')))
       {
            $arr_penalty_posting_numbers[] = $add;
       }
    }
}

unset($prod_array);
unset($items);



// echo "<pre>";
// print_r($arr_penalty_posting_numbers);
// die();


// НАчинаем перебирать перечень элементов по типу 
foreach ($array_MINI as $item) {

    // если в номере заказа нет тире , то переносим этот массив в массив без заказа
    // создаем массив затрат для нет номера заказа
    if (!strpos($item['posting']['posting_number'], '-')) {
        $array_without_posting_number[$item['type']][] = $item; // 

    } else {
        // создаем массив, где есть номер заказа
        $arr_type_items_WITH_POSTING_NUMBER[$item['type']][] = $item;
    }
}


// echo "<pre>";
// print_r($array_without_posting_number);

echo "<br>ВСЕГО = " . count($array_MINI) . "<br>";
// Выводм количество элементво каждого массивы
if (isset($array_without_posting_number)) {
    foreach ($array_without_posting_number as $key => $temp_3) {
        echo "Количество элементов по тратам (БЕЗ НОМЕРА ЗАКАЗА) $key = " . count($temp_3) . "<br>";
    }
}
// Выводм количество элементво каждого массивы
foreach ($arr_type_items_WITH_POSTING_NUMBER as $key => $temp_3) {
    echo "Количество элементов по тратам (С НОМЕРОМ ЗАКАЗА) $key = " . count($temp_3) . "<br>";
}

// print_r($array_without_posting_number);
// print_r($arr_type_items_WITH_POSTING_NUMBER);
// die();

echo "****************************************** ZZZZZZZZZZZZZZZZZZZZZZZZZZZ  <br>";
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


function make_posting_number($posting_temp_number)
{
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
