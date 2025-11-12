<?php 

// echo "<pre>";
// print_r($arr_real_ozon_data[1861485446]);

// CSS цепляем
echo "<link rel=\"stylesheet\" href=\"css/main_ozon_reports.css\">";

// Начинаем отрисовывать таблицу 
echo "<table class=\"real_money fl-table\">";

// ШАПКА ТАблицы
echo <<<HTML
<tr>
    <th class ="name_row">Наименование</th>
    <th>SKU<br>Артикул</th>
    
    <th>К-во<br>Заказ<br>(шт)</th>
    <th>К-во<br>Возвр<br>(шт)</th>
    <th>К-во<br>продн<br>(шт)</th>
   
    <th>Сумма<br>продаж<br>(руб)</th>
    <th>Комиссия <br>озон<br>(руб)</th>
    <th>Стоимость<br>логистики<br>(руб)</th>

    <th>Сумма<br>продаж без<br>комис и логис<br>(руб)</th>

    <th>Стоимость<br>сервисов<br>(руб)</th>
    <th>Эквайринг<br>(руб)<br>(9)</th>
    <th>Цена за<br>вычетом<br>всего где<br> есть<br>арктикул<br>(руб)</th>
    <th>% от общей<br>суммы<br>продаж<br>(руб)</th>
    <th>доп.услуги<br>(руб)</th>
    <th>К начисле<br>нию<br>(руб)</th>
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
    <td>(16)15*3</td>
    
</tr>

HTML;

 
foreach ($arr_real_ozon_data as $sku_ozon=>$item_for_print) {   

echo "<tr>";
// 

// Название товара
   echo "<td>". " <a href =\"".$item_for_print['link_for_site_ozon']."\" target=\"_blank\">". $item_for_print['name']."</a>". "</td>";
// 
   echo "<td>"." <a href =\"".$item_for_print['link_for_report_article']."\" target=\"_blank\">". $item_for_print['sku']."</a><hr>". 
    $item_for_print['mp_article']." </td>";


// Количество заказаыын товаров
print_one_string_in_table($item_for_print['count'],  'direct'); 
// Количество возвратов товаров
print_one_string_in_table($item_for_print['count'],  'return');
// Количетво выкупленных товаров
print_one_string_in_table($item_for_print['count'],  'summa'); 


/**************************************************************************************/
// Цена для покупателя (стоимость товара в личном кабинете)
/**************************************************************************************/
 print_two_strings_in_table_two_parametrs($item_for_print['summa']['accruals_for_sale'],
                                          $item_for_print['one_item']['accruals_for_sale'], 
                                          $color_class = '');
/**************************************************************************************/
/// *******************   Комиссия озона   **************************
/**************************************************************************************/
print_three_strings_for_table($item_for_print['summa']['sale_commission'],
                                $item_for_print['one_procent']['sale_commission']."%", 
                                $item_for_print['one_item']['sale_commission']);
 /**************************************************************************************/
/// *******************   логистка  **************************
/**************************************************************************************/
print_three_strings_for_table($item_for_print['summa']['logistika'],
                                $item_for_print['one_procent']['logistika']."%", 
                                $item_for_print['one_item']['logistika']);

// ************************** Цена продажи *******************************************************************
print_two_strings_in_table_two_parametrs($item_for_print['summa']['amount'],
                                           $item_for_print['one_item']['amount'], 
                                           $color_class = '');

  /// *******************  Сервисы  **************************
print_three_strings_for_table($item_for_print['summa']['services'],
                                $item_for_print['one_procent']['services']."%", 
                                $item_for_print['one_item']['services']);

  /// ******************* Эквайринг  **************************

print_three_strings_for_table($item_for_print['summa']['amount_ecvairing'],
                                 $item_for_print['one_procent']['ecvairing']."%", 
                                 $item_for_print['one_item']['ecvairing']);

 // Цена за вычетом всего где есть артикул

print_two_strings_in_table_two_parametrs($item_for_print['summa']['bez_vsego_gde_est_artikul'],
                                            $item_for_print['one_item']['bez_vsego_gde_est_artikul'], 
                                            $color_class = '');
 

// **************** Процент распределения стоимости *****************
print_one_string_in_table($item_for_print,  'proc_item_ot_vsey_summi');

 // Дополнительные услуги   
print_three_strings_for_table($item_for_print['summa']['dop_uslugi'],
                              $item_for_print['one_procent']['dop_uslugi']."%", 
                              $item_for_print['one_item']['dop_uslugi']);

////////////////////////////////////////////////////////////////////////////////////////
// Цена за вычетом всех расходов 
////////////////////////////////////////////////////////////////////////////////////////
print_two_strings_in_table_two_parametrs($item_for_print['summa']['bez_vsego'],
                                            $item_for_print['one_item']['bez_vsego'], 
                                            $color_class = '');

/////////////////////////////////////////////////////////////////////////////////////////////
// Хорошая цена и разница от цена продажи после всех вычетов
/////////////////////////////////////////////////////////////////////////////////////////////
  $item_for_print['diff_min_price'] >=0? $color_class = 'green_color':$color_class = 'red_color';
  echo "<td>
        <p class=\"big_font $color_class\">". "&#x200b" ."</p>
        <p class = \"small_font\">" .  $item_for_print['main_price']." руб".  "</p>
        <p class = \"small_font $color_class\">" .  number_format($item_for_print['diff_main_price'],0 ,',','') ." руб". "</p>
        </td>";

/************************************************************************************/
// Себестоимость и разница от цена продажи после всех вычетов
/************************************************************************************/

    $item_for_print['diff_min_price'] >=0? $color_class = 'green_color':$color_class = 'red_color';
  echo "<td>
        <p class=\"big_font $color_class\">". number_format($item_for_print['summa']['sebestoimost'],0 ,',',' ') ."</p>
        <p class = \"small_font\">" .  $item_for_print['min_price']." руб"."</p>
        <p class = \"small_font $color_class\">" .  number_format($item_for_print['diff_min_price'],0 ,',','') ." руб". "</p>
        </td>";
/************************************************************************************/
// Прибыль считается от себестоимости
/************************************************************************************/

 $item_for_print['summa']['pribil'] >=0? $color_class = 'green_color':$color_class = 'red_color';
 echo "<td>
        <p class=\"big_font $color_class\">". number_format($item_for_print['summa']['pribil'],0 ,',',' ') ."</p>
      </td>";



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

