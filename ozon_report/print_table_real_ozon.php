<?php 
// print_r($arr_article[1620789328]);


// CSS цепляем
echo "<link rel=\"stylesheet\" href=\"css/main_ozon_reports.css\">";

// Начинаем отрисовывать таблицу 
echo "<table class=\"real_money fl-table\">";

// ШАПКА ТАблицы
echo <<<HTML
<tr>
    <th class ="name_row">Наименование</th>
    <th>SKU</th>
    
    <th>К-во<br>Заказ<br>(шт)</th>
    <th>К-во<br>Возвр<br>(шт)</th>
    <th>К-во<br>продн<br>(шт)</th>
    
    <th>Цена<br>для пок-ля<br>(руб)</th>
    <th>Сумма<br>продаж<br>(руб)</th>
    <th>Комиссия <br>озон<br>(руб)</th>
    <th>Стоимость<br>логистики<br>(руб)</th>
    <th>Стоимость<br>сервисов<br>(руб)</th>
    <th>Эквайринг<br>(руб)</th>
    <th>Цена за вычетом <br>всего где<br> есть арктикул(руб)</th>
    <th>% от общей<br>суммы продаж<br>(руб)</th>
    <th>Затраты на<br>доп.услуги<br>(руб)</th>
    <th>Цена за вычетом <br>всего (руб)</th>
    
</tr>
HTML;
$sum_proc_item_ot_vsey_summi = 0;
$sum_dop_uslugi =0;
$sum_summa_bez_vsego =0;
$sum_summa_bez_vsego_gde_est_artikul = 0;

foreach ($arr_article as $key=>$print_item) {   
$link_for_report_article = "../ozon_report_po_article/index_ozon_razbor_article.php?file_name_ozon=$file_name_ozon&article=".$print_item['sku']."&clt=$client_id";

echo "<tr>";
// 


   echo "<td>". $print_item['name']. "</td>";
   echo "<td>"." <a href =\"$link_for_report_article\" target=\"_blank\">". $print_item['sku']. "</td>";


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
   } else  {
    $temp=0;
   }
   print_two_strings_in_table_two_parametrs(round($print_item['accruals_for_sale']['summa'],0), $temp, $color_class = '');
   $arr_summ['Цена для покупателя'] = @$arr_summ['Цена для покупателя'] + $print_item['accruals_for_sale']['summa'];
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
   $arr_summ['Сумма продаж'] = @$arr_summ['Сумма продаж'] + $print_item['amount']['summa'];
 } else {
     echo "<td>" . "-" . "</td>"; 
 }
//    print_one_string_in_table($print_item['amount'],  'summa');
   

 /// *******************   Комиссия озона   **************************
 if (isset($print_item['sale_commission']['summa'])) {
   if (isset($print_item['count']['summa']) AND ($print_item['count']['summa'] !=0)) {
   $temp = round ($print_item['sale_commission']['summa']/$print_item['count']['summa'],0);
   } else  {
    $temp=0;
   }
   print_two_strings_in_table_two_parametrs(round($print_item['sale_commission']['summa'],0), $temp, $color_class = '');
   $arr_summ['Комиссия озона'] = @$arr_summ['Комиссия озона'] + $print_item['sale_commission']['summa'];
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
   $arr_summ['Логистика'] = @$arr_summ['Логистика'] + $print_item['logistika']['summa'];
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
    $arr_summ['Сервисы'] = @$arr_summ['Сервисы'] + $print_item['services']['summa'];
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
    $arr_summ['Эквайринг'] = @$arr_summ['Эквайринг'] + $print_item['amount_ecvairing'];

 } else {
     echo "<td>" . "-" . "</td>"; 
 }


 // Цена за вычетом всего где есть артикул

    if (isset($print_item['count']['summa']) AND ($print_item['count']['summa'] !=0)) {
   $temp = round ($print_item['summa_bez_vsego_gde_est_artikul']/$print_item['count']['summa'],0);
   } else  {
    $temp=0;
   }
   print_two_strings_in_table_two_parametrs(round($print_item['summa_bez_vsego_gde_est_artikul'],0), $temp, $color_class = '');
    $arr_summ['Цена за вычетом с арктикулом'] = @$arr_summ['Цена за вычетом с арктикулом'] + $print_item['summa_bez_vsego_gde_est_artikul'];

// **************** Процент распределения стоимости *****************
   print_one_string_in_table($print_item,  'proc_item_ot_vsey_summi');
   $arr_summ['Процент распределения стоимости'] = @$arr_summ['Процент распределения стоимости'] + $print_item['proc_item_ot_vsey_summi'];
 // Дополнительные услуги   
   if (isset($print_item['count']['summa']) AND ($print_item['count']['summa'] !=0)) {
   $temp = round ($print_item['dop_uslugi']/$print_item['count']['summa'],0);
   } else  {
    $temp=0;
   }
   print_two_strings_in_table_two_parametrs(round($print_item['dop_uslugi'],0), $temp, $color_class = '');
   $arr_summ['Сумма распределения доп.услуг'] = @$arr_summ['Сумма распределения доп.услуг'] + $print_item['dop_uslugi'];

   // Цена за вычетом всех расходов 

    if (isset($print_item['count']['summa']) AND ($print_item['count']['summa'] !=0)) {
   $temp = round ($print_item['summa_bez_vsego']/$print_item['count']['summa'],0);
   } else  {
    $temp=0;
   }
   print_two_strings_in_table_two_parametrs(round($print_item['summa_bez_vsego'],0), $temp, $color_class = '');
   $arr_summ['Сумма без всего'] = @$arr_summ['Сумма без всего'] + $print_item['summa_bez_vsego'];

  echo "</tr>";


}

// СТРОКА ИТОГО ТАблицы
echo "<tr>"; 
    echo "<td>"."-"."</td>"; 
    echo "<td>"."-"."</td>";
    echo "<td>"."-"."</td>";
    echo "<td>"."-"."</td>";
    echo "<td>"."-"."</td>";
    echo "<td>".$arr_summ['Цена для покупателя']."</td>";
    echo "<td>".$arr_summ['Сумма продаж']."</td>";
    echo "<td>".$arr_summ['Комиссия озона']."</td>";
    echo "<td>".$arr_summ['Логистика']."</td>";
    echo "<td>".$arr_summ['Сервисы']."</td>";
    echo "<td>".$arr_summ['Эквайринг']."</td>";
    echo "<td>".$arr_summ['Цена за вычетом с арктикулом']."</td>";
    echo "<td>".$arr_summ['Процент распределения стоимости']."</td>";
    echo "<td>".$arr_summ['Сумма распределения доп.услуг']."</td>";
    echo "<td>".$arr_summ['Сумма без всего']."</td>";
echo "</tr>";

// Дополнительная информация по суммам
echo "<tr>";
    echo "<td>"."-"."</td>"; 
    echo "<td>"."-"."</td>";
    echo "<td colspan=\"3\">"." Продажи <br> Возвраты <hr> Суммма <hr> Дельnа"."</td>";
    
    $temp = $arr_for_sum_table['Продажи']['-'] + $arr_for_sum_table['Возвраты']['-'];
    $temp_2 = $arr_summ['Цена для покупателя'] - $temp;
    echo "<td>".$arr_for_sum_table['Продажи']['-']."<br>" .$arr_for_sum_table['Возвраты']['-']."<hr>". $temp."<hr>". $temp_2."</td>";
   echo "<td>"."-"."</td>";
echo "</tr>";
echo "</table>";

print_r($arr_for_sum_table);
print_r($arr_summ);