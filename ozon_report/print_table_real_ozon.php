<?php 
// print_r($arr_article[1620789328]);
// print_r($arr_article);


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
    <th>Цена за<br>вычетом <br>всего где<br> есть<br>арктикул<br>(руб)</th>
    <th>% от общей<br>суммы продаж<br>(руб)</th>
    <th>Затраты на<br>доп.услуги<br>(руб)</th>
    <th>Цена за вычетом <br>всего (руб)</th>
    
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
   $arr_summ['Комиссия озона'] = $arr_summ['Комиссия озона'] + $print_item['sale_commission']['summa'];
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
   $arr_summ['Логистика'] = $arr_summ['Логистика'] + $print_item['logistika']['summa'];
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
   print_two_strings_in_table_two_parametrs(round($print_item['amount_ecvairing'],0), $temp, $color_class = '');
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
   print_two_strings_in_table_two_parametrs(round($print_item['summa_bez_vsego_gde_est_artikul'],0), $temp, $color_class = '');
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
   print_two_strings_in_table_two_parametrs(round($print_item['dop_uslugi'],0), $temp, $color_class = '');
   $arr_summ['Сумма распределения доп.услуг'] = $arr_summ['Сумма распределения доп.услуг'] + $print_item['dop_uslugi'];

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
    echo "<td>"."ИТОГО"."</td>"; 
    echo "<td>"."-"."</td>";
    echo "<td>"."-"."</td>";
    echo "<td>"."-"."</td>";
    echo "<td>"."-"."</td>";
    echo "<td>".round($arr_summ['Цена для покупателя'],0)."</td>";
    echo "<td>".round($arr_summ['Сумма продаж'],0)."</td>";
    echo "<td>".round($arr_summ['Комиссия озона'],0)."</td>";
    echo "<td>".round($arr_summ['Логистика'],0)."</td>";
    echo "<td>".round($arr_summ['Сервисы'],0)."</td>";
    echo "<td>".round($arr_summ['Эквайринг'],0)."</td>";
    echo "<td>".round($arr_summ['Цена за вычетом с арктикулом'],0)."</td>";
    echo "<td>".round($arr_summ['Процент распределения стоимости'],0)."</td>";
    echo "<td>".round($arr_summ['Сумма распределения доп.услуг'],0)."</td>";
    echo "<td>".round($arr_summ['Сумма без всего'],0)."</td>";
echo "</tr>";

// Дополнительная информация по суммам
echo "<tr>";
    echo "<td>"."-"."</td>"; 
    echo "<td>"."-"."</td>";
    echo "<td colspan=\"3\">"." Продажи <hr> Возвраты <hr> Сумма <hr> Расхождение"."</td>";
// Цену покапателей    
    $summa = $arr_for_sum_table['Продажи']['-'] + $arr_for_sum_table['Возвраты']['-'];
    $rashozhdenie = $arr_summ['Цена для покупателя'] - $summa;
    echo "<td>".$arr_for_sum_table['Продажи']['-']."<hr>" .$arr_for_sum_table['Возвраты']['-']."<hr>". $summa."<hr>". $rashozhdenie."</td>";
   
    echo "<td>"."-"."</td>";
 

   echo "<td>"."Прямая логистика<hr> 
                Обратная логистика<hr>
                Drop-off<hr>
                Выдача товара<hr>
                Доставка до места выдачи<hr>
                Обработка возвратов<hr>
                Сумма<hr>
                Расхождение
                <hr>"

                ."</td>";
 $summa = round(@$arr_for_sum_table['Услуги доставки']['Прямая логистика']+
                @$arr_for_sum_table['Услуги доставки']['Обратная логистика']+
                @$arr_for_sum_table['Услуги доставки']['Обработка отправления Drop-off'] +
                @$arr_for_sum_table['Услуги агентов']['Выдача товара']+
                @$arr_for_sum_table['Услуги агентов']['Доставка до места выдачи']+
                
                @$arr_for_sum_table['Услуги агентов']['Обработка возвратов, отмен и невыкупов партнёрами'] ,0);
$rashozhdenie = round($summa  - $arr_summ['Логистика'],0);

  echo "<td>". @$arr_for_sum_table['Услуги доставки']['Прямая логистика']."<hr>".
                @$arr_for_sum_table['Услуги доставки']['Обратная логистика']."<hr>".
                @$arr_for_sum_table['Услуги доставки']['Обработка отправления Drop-off']."<hr>".
                @$arr_for_sum_table['Услуги агентов']['Выдача товара']."<hr>".
                @$arr_for_sum_table['Услуги агентов']['Доставка до места выдачи']."<hr>".
                @$arr_for_sum_table['Услуги агентов']['Обработка возвратов, отмен и невыкупов партнёрами']."<hr>".
                @$summa."<hr>".
                @$rashozhdenie."<hr>";


///
echo "<td>"."-"."</td>";
echo "<td>"."-"."</td>";
echo "<td>"."-"."</td>";

/// Доп расходы расписываем 

echo "</tr>";
echo "</table>";

print_r($arr_for_sum_table);
print_r($arr_summ);