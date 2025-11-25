<?php


// foreach ($one_sku_in_reestr as $key=>&$one_string_data) {
//     if (!isset($one_string_data['type']['RETURN'])) {
//         unset($one_sku_in_reestr[$key]);
//     }

// }

// echo "<pre>";
// print_r($one_sku_in_reestr);

///////////// выше удлаить ///////////////////

echo "<link rel=\"stylesheet\" href=\"css/main_ozon_reports.css\">";

// Начинаем отрисовывать таблицу 

echo "<table class=\"real_money fl-table\">";

// ШАПКА ТАблицы
echo <<<HTML
<tr>
<th>пп</th>
<th>Опер-я</th>
<th>Артикул</th>
<th>SKU</th>
<th>Дата заказа</th>
<th>Тип<br>отгрузки</th>
<th>Цена в ЛК</th>
<th>Цена продаже</th>
<th>Вознаг озон</th>

<th class = "td_logistika" >Логистика</th>
<th class = "td_logistika">Услуги<br>агентов</th>
<th class = "td_logistika">Сервисы</th>

 <th>Артикул</th>


</tr>

HTML;
$pp = 0; // номер строки в тублице

// echo "<pre>";
foreach ($one_sku_in_reestr as $key => $one_string_data) {

        echo "<tr>";
        $pp++;   
   $dost_sum =0;
 // костыль окончен
    echo "<td>" . $pp . "</td>";
    echo "<td>"; 
        foreach ($one_string_data['type'] as $type_key=>$type) {
                echo "$type_key<br>";
        }
//  
        // if ($one_string_data['delivery_schema'] == 'FBO') {
            $link_for_post_delivery_finans_info = 'https://seller.ozon.ru/app/finances/accruals?search='.$one_string_data['post_number_gruzomesto'].'&tab=ACCRUALS_DETAILS';
        // } elseif ()
            // $link_for_post_delivery_finans_info = 'https://seller.ozon.ru/app/finances/accruals?search='.$one_string_data['post_number_gruzomesto'].'&tab=ACCRUALS_DETAILS';
    echo "</td>";
    // echo "<td>" . $one_string_data['name'] . "</td>";
    echo "<td>" . $one_string_data['sku'] . "</td>";
    echo "<td>" . $one_string_data['sku'] . "</td>";
    echo "<td>"."<a href=\"$link_for_post_delivery_finans_info\" target=\"_blank\">".$one_string_data['post_number_gruzomesto'] . "</a><hr>". $one_string_data['order_date'] . "</td>";
    echo "<td>" . $one_string_data['delivery_schema'] . "</td>";
    echo "<td>" . $one_string_data['accruals_for_sale'] . "</td>";
    $summa_accruals_for_sale = @$summa_accruals_for_sale + $one_string_data['accruals_for_sale'];
    echo "<td>" . $one_string_data['amount'] . "</td>";
    $summa_amount = @$summa_amount + $one_string_data['amount'];
    echo "<td  class= \"bad_desired_price\">" . $one_string_data['sale_commission']." (".$one_string_data['ozon_procent_commition']."%)" . "</td>";
     $summa_sale_commission = @$summa_sale_commission + $one_string_data['sale_commission'];

// выводим логистику
    echo "<td>";
    if (isset($one_string_data['Услуги доставки'])){
        foreach ($one_string_data['Услуги доставки'] as $type_dost=>$summa_dost) {
            echo "$type_dost: $summa_dost<br>";
            $dost_sum = @$dost_sum + $summa_dost;
        }

        // процент стоимости доставки от цены кабинета
        if ($one_string_data['one_procent_accruals_for_sale'] != 0) {
            $procent_stoimosti_logistiki =  round( -$dost_sum / $one_string_data['one_procent_accruals_for_sale'] , 1);
            } 
            else {
              $procent_stoimosti_logistiki = 0;  
            }
        echo "<hr><p class= \"bad_desired_price\" >".$dost_sum." (". $procent_stoimosti_logistiki . "%)"."</p>";
$summa_logistika = @$summa_logistika + $dost_sum;
    } else {
        echo " ----  ";
    }
      echo "</td>";
// выводим усулги агента
    echo "<td>";
    if (isset($one_string_data['Услуги агентов'])){
        foreach ($one_string_data['Услуги агентов'] as $type_agent=>$summa_agent) {
            echo "$type_agent: $summa_agent<br>";
        }
          echo "<hr><p class= \"bad_desired_price\" >".$summa_agent." (". "X" . "%)"."</p>";
    $summa_all_agent = @$summa_all_agent + $summa_agent;
    } else {
        echo " ----  ";
    }
   
      echo "</td>";
// выводим сервисы
    echo "<td>";
    if (isset($one_string_data['сервисы'])){
        foreach ($one_string_data['сервисы'] as $type_service=>$summa_service) {
            echo "$type_service: $summa_service<br>";
        }
  echo "<hr><p class= \"bad_desired_price\" >".$summa_service." (". "Я" . "%)"."</p>";
 $summa_all_service = @$summa_all_service + $summa_service;  
    } else {
        echo " ----  ";
    }
 
      echo "</td>";

echo "</tr>";

}
echo "</table>";


echo $summa_accruals_for_sale."<br>";
echo $summa_amount."<br>";
echo $summa_sale_commission."<br>";
echo "Логистика=".$summa_logistika."<br>";
echo "Агента=".$summa_all_agent."<br>";
$gg = $summa_logistika + $summa_all_agent;
echo "ИТОГО=".$gg."<br>";