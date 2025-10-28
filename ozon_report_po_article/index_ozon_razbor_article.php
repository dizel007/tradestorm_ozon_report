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

if (isset($_GET['file_name_ozon'])) {
    $file_name_ozon = $_GET['file_name_ozon'];
    $client_id = $_GET['clt'];
    $token = file_get_contents('../!cache/'.$client_id."/token.txt");
} else {
    die('Не нашли файл с данными');
}



if (isset($_GET['article'])) {
    $need_article = mb_strtolower($_GET['article']);
} else {
    $need_article = '';
}



   

echo <<<HTML
<head>
<link rel="stylesheet" href="../css/main_ozon.css">

</head>
HTML;



if (file_exists($file_name_ozon)) {
    echo "<br>Берем данные из загруженного файла<br>";
    $prod_array = json_decode(file_get_contents($file_name_ozon), true);
} 

// echo "<pre>";
// print_r($prod_array);

/*******************************************************************************************
 *  ***** ДАННЫЕ ПОЛУЧЕНЫ ОТСЮДА НАЧИНАЕМ ИХ ОБРАБАТЫВАТЬ ******************************
 *******************************************************************************************/
foreach ($prod_array as $arr_temp) {
    foreach ($arr_temp as $add) 
    {
        $array_MINI[] = $add;   
    }
  unset($prod_array);
}

// echo "<pre>";
// print_r($array_MINI);
// die();

foreach ($array_MINI as $item) {
   
    $posting_number = $item['posting']['posting_number'];
    $posting_number_for_array = make_posting_number ($posting_number);
  
    $prod_array_2[$posting_number_for_array][] = $item;
    $prod_array_posts[$posting_number_for_array][] = $posting_number_for_array;

}

$prod_array = $prod_array_2;
unset($prod_array_2);


// echo "<pre>";
// print_r($array_MINI);
// die();
//62050202-0064-1
echo "<br> Количество заказов ".count($array_MINI) ."<br>";
$i=0;

 // Разбиваем наш массив на массивы по ТИПУ
foreach ($array_MINI as $item_element) {
   // Заказ товара и прямая логистика (ИНОСТРАННЫЕ ЗАКАЗЫ)
    // if (($item_element['type'] == 'orders') && ($item_element['accruals_for_sale'] == 0)) {
    //     $arr_orders_ino[] = $item_element;
    //     unset($array_MINI[$i]);
    
    // } 
    // Заказ товара и прямая логистика (РОССИЙСКИЕ ЗАКАЗЫ + СНГ)
    if (($item_element['type'] == 'orders') ) {
        $arr_orders[] = $item_element;
        unset($array_MINI[$i]);
    }elseif ($item_element['type'] == 'other') {
        // Эквайринг + еще херня какая то 
        $arr_other[] = $item_element;
        unset($array_MINI[$i]);

    } elseif ($item_element['type'] == 'services') {
        $posting_number_serv = make_posting_number ($item_element['posting']['posting_number']);
        if ((strpos($posting_number_serv, "-")) && (!strpos($item_element['operation_type_name'], "Premium "))) {
            $count_services_with_posting_number = @$count_services_with_posting_number + 1;
        }
        // СЕрвисы (тут вся херня в том числе без заказа)
        $arr_services[] = $item_element;
        unset($array_MINI[$i]);
    } elseif ($item_element['type'] == 'returns') {
        // возвраты
        $arr_returns[] = $item_element;
        unset($array_MINI[$i]);
    }  elseif ($item['type'] == 'compensation') {
        // Всякие удержания 
        $arr_compensation[] = $item_element;
        unset($array_MINI[$i]);
    }  else {
        $arr_unknows[] = $item_element;
    }
    $i++;
}
$i=0;




echo "<br>Количество Неразобранных элементов массива --".count($array_MINI) ."<br>";
if (isset($arr_orders) ) {
echo "Количество элементов массива ORDERS --".count($arr_orders) ."<br>";
}
// if (isset($arr_orders_ino) ) {
//     echo "Количество элементов массива ZAKAZI INO--".count($arr_orders_ino) ."<br>";
//     }
if (isset($arr_other) ) {
echo "Количество элементов массива OTHER --".count($arr_other) ."<br>";
}
if (isset($arr_services) ) {
echo "Количество элементов массива SERVICES --".count($arr_services) ."  из них с НОМЕРОМ ЗАКАЗА -- $count_services_with_posting_number<br>";
}
if (isset($arr_returns) ) {
echo "Количество элементов массива RETURNS --".count($arr_returns) ."<br>";
}
if (isset($arr_compensation) ) {
echo "Количество элементов массива COMPENSATION --".count($arr_compensation) ."<br>";
}
if (isset($arr_unknows) ) {
    echo "<b>Количество элементов массива UNKNOWS --".count($arr_unknows) ." (ПЛОХО нужно смотреть что не разобрано)</b><br>";
    // echo "<pre>";
    // print_r($arr_unknows);
} else {
    echo "!!!!!!!!!!!!!!!!!!  НЕТ массива UNKNOWS !!!!!!!!!!!!!!!!!!!!!!!!!!!<br>";
}

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
    if ($posting_temp_number == '') {
        return '';
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
