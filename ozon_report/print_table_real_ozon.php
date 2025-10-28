<?php 

// CSS цепляем
echo "<link rel=\"stylesheet\" href=\"css/main_ozon_reports.css\">";

// Начинаем отрисовывать таблицу 
echo "<table class=\"real_money fl-table\">";

// ШАПКА ТАблицы
echo "<tr>";
    echo "<th>Наименование</th>";
    echo "<th>Артикул</th>";
    echo "<th>Кол-во<br>продано<br>(шт)</th>";
    echo "<th>Цена<br>для пок-ля<br>(руб)</th>";
    echo "<th>Сумма<br>продаж<br>(руб)</th>";
    echo "<th>% от общей<br>суммы продаж<br>(руб)</th>";
    echo "<th>Затраты на<br>доп.услуги<br>(руб)</th>";

    echo "<th>Комиссия <br>озон<br>(руб)</th>"; 
    echo "<th>Стоимость<br>логистики<br>(руб)</th>";
    echo "<th>Стоимость<br>сервисов<br>(руб)</th>";
     echo "<th>Эквайринг<br>(руб)</th>";

    echo "<th>Цена за вычетом <br>всего (руб)</th>";
    // echo "<th>Цена за  штуку <br>всего (руб)</th>";
    // echo "<th>Желаемая цена<br>(руб)</th>";
    // echo "<th>Себестоимость</th>";
    // echo "<th>Заработали<br>с артикула</th>";
echo "</tr>";

$sum_proc_item_ot_vsey_summi = 0;
$sum_dop_uslugi =0;
$sum_summa_bez_vsego =0;
foreach ($arr_article as $key=>$print_item) {   
$link_for_report_article = "../ozon_report_po_article/index_ozon_razbor_article.php?file_name_ozon=$file_name_ozon&article=".$print_item['sku']."&clt=$client_id";
echo "<tr>";
   echo "<td>". $print_item['name']. "</td>";
   echo "<td>"." <a href =\"$link_for_report_article\" target=\"_blank\">". $print_item['sku']. "</td>";
   if (isset($print_item['count']['summa'])) {
   print_one_string_in_table($print_item['count'],  'summa');
   } else {
       echo "<td>" . "0" . "</td>";
   }
// Цена для покупателя (стоимость товара в личном кабинете)
   if (isset($print_item['accruals_for_sale']['summa']) ) {
   if (isset($print_item['count']['summa']) AND ($print_item['count']['summa'] !=0)) {
   $temp = round ($print_item['accruals_for_sale']['summa']/$print_item['count']['summa'],0);
   } else  {
    $temp=0;
   }
   print_two_strings_in_table_two_parametrs(round($print_item['accruals_for_sale']['summa'],0), $temp, $color_class = '');
} else {
    echo "<td>" . "-" . "</td>"; 
}
//    print_one_string_in_table($print_item['accruals_for_sale'],  'summa');

// ************************** Цена продажи *******************************************************************
 if (isset($print_item['amount']['summa']) ) {
   if (isset($print_item['count']['summa']) AND ($print_item['count']['summa'] !=0)) {
   $temp = round ($print_item['amount']['summa']/$print_item['count']['summa'],0);
   } else  {
    $temp=0;
   }
   print_two_strings_in_table_two_parametrs(round($print_item['amount']['summa'],0), $temp, $color_class = '');
 } else {
     echo "<td>" . "-" . "</td>"; 
 }
//    print_one_string_in_table($print_item['amount'],  'summa');
   
// **************** Процент распределения стоимости *****************

   print_one_string_in_table($print_item,  'proc_item_ot_vsey_summi');
   $sum_proc_item_ot_vsey_summi  = $sum_proc_item_ot_vsey_summi  + $print_item['proc_item_ot_vsey_summi'];
 // Дополнительные услуги   

   if (isset($print_item['count']['summa']) AND ($print_item['count']['summa'] !=0)) {
   $temp = round ($print_item['dop_uslugi']/$print_item['count']['summa'],0);
   } else  {
    $temp=0;
   }
   print_two_strings_in_table_two_parametrs(round($print_item['dop_uslugi'],0), $temp, $color_class = '');

//    print_one_string_in_table($print_item,  'dop_uslugi');

   $sum_dop_uslugi  = $sum_dop_uslugi  + $print_item['dop_uslugi'];

 /// *******************   Комиссия озона   **************************
 if (isset($print_item['sale_commission']['summa'])) {
   if (isset($print_item['count']['summa']) AND ($print_item['count']['summa'] !=0)) {
   $temp = round ($print_item['sale_commission']['summa']/$print_item['count']['summa'],0);
   } else  {
    $temp=0;
   }
   print_two_strings_in_table_two_parametrs(round($print_item['sale_commission']['summa'],0), $temp, $color_class = '');
 } else {
     echo "<td>" . "-" . "</td>"; 
 }

/// *******************   логистка  **************************
 if (isset($print_item['logistika']['summa'])) {
   if (isset($print_item['count']['summa']) AND ($print_item['count']['summa'] !=0)) {
   $temp = round ($print_item['logistika']['summa']/$print_item['count']['summa'],0);
   } else  {
    $temp=0;
   }
   print_two_strings_in_table_two_parametrs(round($print_item['logistika']['summa'],0), $temp, $color_class = '');
 } else {
     echo "<td>" . "-" . "</td>"; 
 }


  /// *******************  Сервисы  **************************
   if (isset($print_item['services']['summa'])) {
   if (isset($print_item['count']['summa']) AND ($print_item['count']['summa'] !=0)) {
   $temp = round ($print_item['services']['summa']/$print_item['count']['summa'],0);
   } else  {
    $temp=0;
   }
   print_two_strings_in_table_two_parametrs(round($print_item['services']['summa'],0), $temp, $color_class = '');
 } else {
     echo "<td>" . "-" . "</td>"; 
 }

  /// ******************* Эквайринг  **************************
   if (isset($print_item['amount_ecvairing'])) {
   if (isset($print_item['count']['summa']) AND ($print_item['count']['summa'] !=0)) {
   $temp = round ($print_item['amount_ecvairing']/$print_item['count']['summa'],0);
   } else  {
    $temp=0;
   }
   print_two_strings_in_table_two_parametrs(round($print_item['amount_ecvairing'],0), $temp, $color_class = '');
 } else {
     echo "<td>" . "-" . "</td>"; 
 }




   // Цена за вычетом всех расходов 

    if (isset($print_item['count']['summa']) AND ($print_item['count']['summa'] !=0)) {
   $temp = round ($print_item['summa_bez_vsego']/$print_item['count']['summa'],0);
   } else  {
    $temp=0;
   }
   print_two_strings_in_table_two_parametrs(round($print_item['summa_bez_vsego'],0), $temp, $color_class = '');


//    print_one_string_in_table($print_item,  'summa_bez_vsego');
   $sum_summa_bez_vsego  = $sum_summa_bez_vsego  + $print_item['summa_bez_vsego'];

//    print_one_string_in_table($print_item,  'real_price_minus_all_one_shtuka');


   
//    print_two_strings_in_table ($print_item, 'accruals_for_sale' , 'one_shtuka_buyer' );
//    print_one_string_in_table  ($print_item, 'price_minus_all_krome_dop_uslug');
//    print_one_string_in_table  ($print_item, 'proc_item_ot_vsey_summi');
//    print_one_string_in_table  ($print_item, 'dop_uslugi_each_item');
//    print_two_strings_in_table ($print_item, 'real_price_minus_all' , 'real_price_minus_all_one_shtuka');
   // Желаемая цена за товар
// if ($print_item['main_price_delta']  >= 0) {
//     $color_desired_price = 'good_desired_price';
//    }else {
//     $color_desired_price = 'bad_desired_price';
//    }
//    print_two_strings_in_table($print_item, 'main_price' , 'main_price_delta' , $color_desired_price );
// // Себестоимость
//    if ($print_item['min_price_delta']  >= 0) {
//     $color_desired_price = 'good_desired_price';
//    }else {
//     $color_desired_price = 'bad_desired_price';
//    }
//    print_two_strings_in_table($print_item, 'min_price' , 'min_price_delta' ,$color_desired_price );
// // Заработали на артикуле 
// if ($print_item['zarabotali_na_artikule']  >= 0) {
//     $color_desired_price = 'good_desired_price';
//    }else {
//     $color_desired_price = 'bad_desired_price';
//    }

//    print_one_string_in_table($print_item,  'zarabotali_na_artikule' ,$color_desired_price);

    echo "</tr>";


}

// СТРОКА ИТОГО ТАблицы
echo "<tr>"; 
echo "<td></td>"; 
echo "<td></td>"; 
echo "<td></td>"; 
echo "<td></td>"; 
echo "<td></td>"; 
echo "<td>$sum_proc_item_ot_vsey_summi</td>"; 
echo "<td>$sum_dop_uslugi</td>"; 
echo "<td>$sum_summa_bez_vsego</td>"; 

// echo "<td></td>"; // арктикул

    // echo "<td></td>"; // Себестоимость
    // if ($arr_sum_data['zarabotali_na_artikule']  >= 0) {
    //     $color_desired_price = 'good_desired_price';
    //    }else {
    //     $color_desired_price = 'bad_desired_price';
    //    }

    // print_one_string_in_table($arr_sum_data, 'zarabotali_na_artikule' ,$color_desired_price);
              
echo "</tr>";

echo "</table>";
