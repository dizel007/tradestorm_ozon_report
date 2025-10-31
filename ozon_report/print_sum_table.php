<?php
// print_r($arr_for_sum_table);
// CSS цепляем
echo "<link rel=\"stylesheet\" href=\"css/sum_table..css\">";

echo "<h1><a href=\"https://seller.ozon.ru/app/finances/balance?tab=IncomesExpenses\" target=\"_blank\"> Ссылка на выплаты озона </a></h1>";
// Начинаем отрисовывать таблицу 
echo "<table class=\"sum-table\">";
echo "<tr>";
    echo "<th>пп</th>";
    echo "<th>Продажи</th>";
    echo "<th>Итого (руб)</th>";
    echo "<th>Статья списания</th>";
    echo "<th>Сумма(руб)</th>";
 
echo "</tr>";
$string_munber=0;
foreach ($arr_for_sum_table as $key=>$data_for_print){

    // если сумма по статье НЕ РАВНО нулю то делаем строку
    if (array_sum($arr_for_sum_table[$key])!=0 ) {
    
        $string_munber++;
    $count_string = count($data_for_print);
    $summ_info = number_format(round(array_sum($arr_for_sum_table[$key]),0),0,'.',',');
    echo <<<HTML
            <tr>
            <td rowspan="{$count_string}">$string_munber</td>
            <td rowspan="{$count_string}">$key</td>
            <td rowspan="{$count_string}">$summ_info</td>
           
HTML;
// $i=0;
    foreach ($data_for_print as $name_trata => $summa_trat)
        {
    //  $i++;       
    echo "<td class=\"statia_rashodov\">$name_trata</td>";
    echo "<td>".number_format(round($summa_trat,0),0,'.',',')."</td>";
    // if ( $i == 1 ) { 
    //     echo     "<td rowspan=\"$count_string\">".number_format(round(array_sum($arr_for_sum_table[$key]),0),0,'.',',')."</td>";
    // }
        echo "</tr>";
    
  
   }
 }
}

echo "<tr>";
echo "<td></td>";
echo "<td></td>";
echo "<td></td>";
echo "<td>К начислению </td>";
echo "<td>". number_format(round($summa_k_nachisleniu,0),0,'.',',')."</td>";
echo "</tr>";

echo "</table>";

// die();



