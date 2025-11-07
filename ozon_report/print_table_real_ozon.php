<?php 
// print_r($arr_article[1620789328]);
// print_r($arr_article);
// echo "<pre>";
// print_r($arr_sebestoimost); 

// CSS цепляем
echo "<link rel=\"stylesheet\" href=\"css/main_ozon_reports.css\">";

// Начинаем отрисовывать таблицу 
echo "<table class=\"real_money fl-table\">";

// ШАПКА ТАблицы
echo <<<HTML
<tr>
    <th class ="name_row">Наименование</th>
    <th>SKU<br>Артикл</th>
    
    <th>К-во<br>Заказ<br>(шт)</th>
    <th>К-во<br>Возвр<br>(шт)</th>
    <th>К-во<br>продн<br>(шт)</th>
    
    <th>Цена<br>для пок-ля<br>(руб)</th>
    <th>Комиссия <br>озон<br>(руб)</th>
    <th>Стоимость<br>логистики<br>(руб)</th>

    <th>Сумма<br>продаж<br>(руб)</th>

    <th>Стоимость<br>сервисов<br>(руб)</th>
    <th>Эквайринг<br>(руб)<br>(9)</th>
    <th>Цена за<br>вычетом<br>всего где<br> есть<br>арктикул<br>(руб)</th>
    <th>% от общей<br>суммы<br>продаж<br>(руб)</th>
    <th>доп.услуги<br>(руб)</th>
    <th>Цена за<br>вычетом<br>всего (руб)</th>
    <th>Хор.цена<br>(руб)</th>
    <th>Себест-сть<br>(руб)</th>
    <th>Прибыль<br>(руб)</th>
    
</tr>

<tr>
    <td></td>
    <td></td>
    <td>(1)</td>
    <td>(2)</td>
    <td>(3)=1-2</td>
    <td>(4)</td>
    <td>(5)</td>
    <td>(6)</td>
    <td>(7)=4+5+6</td>
    <td>(8)</td>
    <td>(9)</td>
    <td>(10)=7+8+9</td>
    <td>(11)</td>
    <td>(12)</td>
    <td>(13)=10+12</td>
    <td>(14)</td>
    <td>(15)</td>
    <td>(16)</td>
    
</tr>

HTML;

    $arr_summ['Цена для покупателя'] = 0;
    $arr_summ['Сумма продаж'] = 0;
    $arr_summ['Комиссия озона'] = 0;
    $arr_summ['Логистика'] = 0;
    $arr_summ['Сервисы'] = 0;
    $arr_summ['Эквайринг'] = 0;
    $arr_summ['Цена за вычетом с арктикулом'] = 0;
    $arr_summ['Процент распределения стоимости'] = 0;
    $arr_summ['Сумма распределения доп.услуг'] = 0;
    $arr_summ['Сумма без всего'] = 0;
// echo "<pre>";
// print_r($arr_article);
foreach ($arr_article as $key=>$print_item) {   
$link_for_report_article = "../ozon_report_po_article/index_ozon_razbor_article.php?file_name_ozon=$file_name_ozon&article=".$print_item['sku']."&clt=$client_id";

echo "<tr>";
// 


   echo "<td>". $print_item['name']. "</td>";
   echo "<td>"." <a href =\"$link_for_report_article\" target=\"_blank\">". $print_item['sku']."</a><hr>". 
    $arr_sebestoimost[$print_item['sku']]['mp_article']." </td>";


// Количество заказаыын товаров
if (isset($print_item['count']['direct'])) {print_one_string_in_table($print_item['count'],  'direct');} 
else {echo "<td>" . "0" . "</td>";}
// Количество возвратов товаров
if (isset($print_item['count']['return'])) {print_one_string_in_table($print_item['count'],  'return');} 
else {echo "<td>" . "0" . "</td>";}
// Количетво выкупленных товаров
if (isset($print_item['count']['summa'])) {print_one_string_in_table($print_item['count'],  'summa');} 
else {echo "<td>" . "0" . "</td>";}
// Цена для покупателя (стоимость товара в личном кабинете)

   if (isset($print_item['accruals_for_sale']['summa']) ) {
   if (isset($print_item['count']['summa']) AND ($print_item['count']['summa'] !=0)) {
       $temp = round ($print_item['accruals_for_sale']['summa']/$print_item['count']['summa'],0);
       $one_procent_from_accruals_for_sale = round($print_item['accruals_for_sale']['summa']/100,4);
   } else  {
    $temp=0;
    
   }
   print_two_strings_in_table_two_parametrs(round($print_item['accruals_for_sale']['summa'],0), $temp, $color_class = '');
   $arr_summ['Цена для покупателя'] = @$arr_summ['Цена для покупателя'] + $print_item['accruals_for_sale']['summa'];
} else {
    echo "<td>" . "-" . "</td>"; 
}
/**************************************************************************************/
/// *******************   Комиссия озона   **************************
/**************************************************************************************/

 if (isset($print_item['sale_commission']['summa'])) {
   if (isset($print_item['count']['summa']) AND ($print_item['count']['summa'] !=0)) {
   $temp = round ($print_item['sale_commission']['summa']/$print_item['count']['summa'],0);
   $sale_commission_procent = abs(round(($print_item['sale_commission']['summa']/$one_procent_from_accruals_for_sale),1));
   $data1 = number_format(round($print_item['sale_commission']['summa'],0),0 ,',',' ')."($sale_commission_procent%)";
   } else  {
    $temp=0;
   }
   print_two_strings_for_table($data1, $temp, $color_class = 'red_color');
//    print_two_strings_in_table_two_parametrs(round($print_item['sale_commission']['summa'],0), $temp, $color_class = 'red_color');
   $arr_summ['Комиссия озона'] = $arr_summ['Комиссия озона'] + $print_item['sale_commission']['summa'];
 } else {
     echo "<td>" . "-" . "</td>"; 
 }

 /**************************************************************************************/
/// *******************   логистка  **************************
/**************************************************************************************/

 if (isset($print_item['logistika']['summa'])) {
   if (isset($print_item['count']['summa']) AND ($print_item['count']['summa'] !=0)) {
   $temp = round ($print_item['logistika']['summa']/$print_item['count']['summa'],0);

   $sale_commission_procent = abs(round(($print_item['logistika']['summa']/$one_procent_from_accruals_for_sale),1));
   $data1 = number_format(round($print_item['logistika']['summa'],0),0 ,',',' ')."($sale_commission_procent%)";

   } else  {
    $temp=0;
   }
   print_two_strings_for_table($data1, $temp, $color_class = 'red_color');

//    print_two_strings_in_table_two_parametrs(round($print_item['logistika']['summa'],0), $temp, $color_class = 'red_color');
   $arr_summ['Логистика'] = $arr_summ['Логистика'] + $print_item['logistika']['summa'];
 } else {
     echo "<td>" . "-" . "</td>"; 
 }

// ************************** Цена продажи *******************************************************************
 if (isset($print_item['amount']['summa']) ) {
   if (isset($print_item['count']['summa']) AND ($print_item['count']['summa'] !=0)) {
   $temp = round ($print_item['amount']['summa']/$print_item['count']['summa'],0);
   } else  {
    $temp=0;
   }
   print_two_strings_in_table_two_parametrs(round($print_item['amount']['summa'],0), $temp, $color_class = '');
   $arr_summ['Сумма продаж'] = @$arr_summ['Сумма продаж'] + $print_item['amount']['summa'];
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
   print_two_strings_in_table_two_parametrs(round($print_item['services']['summa'],0), $temp, 'red_color');
    $arr_summ['Сервисы'] = $arr_summ['Сервисы'] + $print_item['services']['summa'];
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
   print_two_strings_in_table_two_parametrs(round($print_item['amount_ecvairing'],0), $temp, 'red_color');
    $arr_summ['Эквайринг'] = $arr_summ['Эквайринг'] + $print_item['amount_ecvairing'];

 } else {
     echo "<td>" . "-" . "</td>"; 
 }

 // Цена за вычетом всего где есть артикул

    if (isset($print_item['count']['summa']) AND ($print_item['count']['summa'] !=0)) {
   $temp = round ($print_item['summa_bez_vsego_gde_est_artikul']/$print_item['count']['summa'],0);
   } else  {
    $temp=0;
   }
   print_two_strings_in_table_two_parametrs(round($print_item['summa_bez_vsego_gde_est_artikul'],0), $temp, '');
    $arr_summ['Цена за вычетом с арктикулом'] = $arr_summ['Цена за вычетом с арктикулом'] + $print_item['summa_bez_vsego_gde_est_artikul'];

// **************** Процент распределения стоимости *****************
   print_one_string_in_table($print_item,  'proc_item_ot_vsey_summi');
   $arr_summ['Процент распределения стоимости'] = $arr_summ['Процент распределения стоимости'] + $print_item['proc_item_ot_vsey_summi'];
 // Дополнительные услуги   
   if (isset($print_item['count']['summa']) AND ($print_item['count']['summa'] !=0)) {
   $temp = round ($print_item['dop_uslugi']/$print_item['count']['summa'],0);
   } else  {
    $temp=0;
   }
   print_two_strings_in_table_two_parametrs(round($print_item['dop_uslugi'],0), $temp, 'red_color');
   $arr_summ['Сумма распределения доп.услуг'] = $arr_summ['Сумма распределения доп.услуг'] + $print_item['dop_uslugi'];

   // Цена за вычетом всех расходов 

    if (isset($print_item['count']['summa']) AND ($print_item['count']['summa'] !=0)) {
   $price_for_one_tovar = round ($print_item['summa_bez_vsego']/$print_item['count']['summa'],0);
   } else  {
    $price_for_one_tovar=0;
   }
   print_two_strings_in_table_two_parametrs(round($print_item['summa_bez_vsego'],0), $price_for_one_tovar, '');
   $arr_summ['Сумма без всего'] = @$arr_summ['Сумма без всего'] + $print_item['summa_bez_vsego'];

// Хорошая цена и разница от цена продажи после всех вычетов

    if (isset($arr_sebestoimost[$print_item['sku']]['main_price']) ) {
       $diff_price = round(($price_for_one_tovar - $arr_sebestoimost[$print_item['sku']]['main_price']) ,0 );
 $diff_price >=0? $color_class = 'green_color':$color_class = 'red_color';
          print_two_strings_in_table_two_parametrs($arr_sebestoimost[$print_item['sku']]['main_price'], $diff_price, $color_class);
   } else  {
    $temp=0;
   }




// Себестоимость и разница от цена продажи после всех вычетов

    if (isset($arr_sebestoimost[$print_item['sku']]['min_price']) ) {
       $arr_summ['Сумма себестоимость'] = @$arr_summ['Сумма себестоимость'] + $arr_sebestoimost[$print_item['sku']]['min_price']*$print_item['count']['summa']; 
       $diff_price = round(($price_for_one_tovar - $arr_sebestoimost[$print_item['sku']]['min_price']) ,0 );
       $diff_price >=0? $color_class = 'green_color':$color_class = 'red_color';
          print_two_strings_in_table_two_parametrs($arr_sebestoimost[$print_item['sku']]['min_price'], $diff_price, $color_class);
   } else  {
    $temp=0;
   }
// Прибыль считается от себестоимости
if ((isset($print_item['count']['summa'])) AND ($print_item['count']['summa'] != 0)) {
    $pribil['pribil'] = round($print_item['count']['summa'] * $diff_price,0);
    $pribil['pribil'] >=0? $color_class = 'green_color':$color_class = 'red_color';
    $arr_summ['Сумма прибыль'] = @$arr_summ['Сумма прибыль'] + $pribil['pribil'];
    print_summa_in_table($pribil, 'pribil', $color_class );
    // print_one_string_in_table($pribil,  'pribil' , $color_class);
} 
else {echo "<td>" . "0" . "</td>";}



  echo "</tr>";



}

// СТРОКА ИТОГО ТАблицы
echo "<tr>"; 
    echo "<td>"."ИТОГО"."</td>"; 
    echo "<td>"."-"."</td>";
    echo "<td>"."-"."</td>";
    echo "<td>"."-"."</td>";
    echo "<td>"."-"."</td>";
    print_summa_in_table($arr_summ, 'Цена для покупателя', $color_class = '');
    print_summa_in_table($arr_summ, 'Комиссия озона', 'red_color');
    print_summa_in_table($arr_summ, 'Логистика', 'red_color');
    print_summa_in_table($arr_summ, 'Сумма продаж', $color_class = '');
    print_summa_in_table($arr_summ, 'Сервисы', 'red_color');
    print_summa_in_table($arr_summ, 'Эквайринг', 'red_color');
    print_summa_in_table($arr_summ, 'Цена за вычетом с арктикулом', $color_class = '');
    echo "<td>".round($arr_summ['Процент распределения стоимости'],2)."</td>";
    print_summa_in_table($arr_summ, 'Сумма распределения доп.услуг', 'red_color');
    print_summa_in_table($arr_summ, 'Сумма без всего', $color_class = '');
    echo "<td>"."-"."</td>";
    print_summa_in_table($arr_summ, 'Сумма себестоимость', $color_class = '');
    print_summa_in_table($arr_summ, 'Сумма прибыль', $color_class = '');




echo "</tr>";
echo "</table>";

