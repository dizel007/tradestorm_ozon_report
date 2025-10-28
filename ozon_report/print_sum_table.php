<?php

// CSS цепляем
echo "<link rel=\"stylesheet\" href=\"css/sum_table..css\">";


// Начинаем отрисовывать таблицу 
echo "<table class=\"real_money fl-table\">";
echo "<tr>";
    echo "<th>пп</th>";
    echo "<th>Продажи</th>";
    echo "<th>Статья списания</th>";
    echo "<th>Сумма(руб)</th>";
    echo "<th>Итого (руб)</th>";
echo "</tr>";
$string_munber=0;
foreach ($arr_for_sum_table as $key=>$data_for_print){

    // если сумма по статье НЕ РАВНО нулю то делаем строку
    if (array_sum($arr_for_sum_table[$key])!=0 ) {
    
        $string_munber++;
    $count_string = count($data_for_print);
    echo <<<HTML
            <tr>
            <td rowspan="{$count_string}">$string_munber</td>
            <td rowspan="{$count_string}">$key</td>
           
HTML;
$i=0;
    foreach ($data_for_print as $name_trata => $summa_trat)
        {
     $i++;       
    echo "<td>$name_trata</td>";
    echo "<td>".number_format(round($summa_trat,0),0,'.',',')."</td>";
    if ( $i == 1 ) { 
        echo     "<td rowspan=\"$count_string\">".number_format(round(array_sum($arr_for_sum_table[$key]),0),0,'.',',')."</td>";
    }
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

die();



