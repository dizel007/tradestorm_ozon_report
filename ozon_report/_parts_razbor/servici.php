<?php
// echo "</pre>";
// print_r($arr_services);


if (isset ($arr_services)) {
    foreach ($arr_services as $items) {
        $i++;
        $service_obrabotan = 0;
        $our_item = $items['items'];
    // перебираем список товаров в этом заказе (Там где одиночные борды. Остальные отправления мы разбиваем по 1 штуке)
            foreach ($our_item as $item) {
    ///// ТУТ мы меняет SKU ФБО на СКУ ФБС, чтобы в таблице вывести их в одной строке
                $new_sku = $item['sku'];
                $arr_article[$new_sku]['name'] = $item['name'];
                $arr_article[$new_sku]['sku'] = $new_sku;
                // если есть СКУ то привязываем трату к СКУ
            if (count($our_item) != 0)
                {
                   $arr_article[$new_sku]['services'][$items['operation_type_name']] = @$arr_article[$new_sku]['services'][$items['operation_type_name']] + 
                   $items['amount'];
                    $arr_sum_services_payment_with_SKU[$items['operation_type_name']] = @$arr_sum_services_payment_with_SKU[$items['operation_type_name']] + $items['amount'];            
                    $service_obrabotan = 1;
                }
            }
    if ($service_obrabotan == 1) { // если в сервисе есть артикул, то цепляем сервис к артикулу, сли нет, то в массив сервисов
        continue;
    }
    // print_r($items);
    // СУмма по сервисами
    $arr_sum_services_payment[$items['operation_type_name']] = @$arr_sum_services_payment[$items['operation_type_name']] + $items['amount'];
        
    }

}
