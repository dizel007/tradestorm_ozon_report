<?php


// print_r($arr_type_items_WITH_POSTING_NUMBER['services']);

// die();
// НАчитаем перебирать массивы с сервисами, где есть нормер заказа
foreach ($arr_type_items_WITH_POSTING_NUMBER['services'] as $checks) {
    // Создаем массив всех названий сервисов 
    $arr_services_types[$checks['operation_type']] = $checks['operation_type_name'];
    
        $service_obrabotan = 0;
        $our_item = $checks['items'];
    // Начисление за хранение/утилизацию возвратов
          $new_post_number = make_posting_number($checks['posting']['posting_number']);
           // техническая инфа 
            $arr_article[$new_post_number]['operation_id'][] = $checks['operation_id'];
            $arr_article[$new_post_number]['order_date'] = $checks['posting']['order_date'];
            $arr_article[$new_post_number]['type']['SERVICES'] = '_SERVICES_';  
            $new_post_number_full = $checks['posting']['posting_number'];
 /// РАЗБИВАЕМ СЕРВИСЫ НА ТЕ ГДЕ ЕСТЬ SERVICES и где этот массив ПУСТОЙ
    if (count($checks['services']) >0) {
        $i_services++;
          foreach ($checks['services'] as $services) { 
            // логистика + сервисы в прямом направлении
            // если есть проданные товары, то цепляем сервисы к проданным, если нет, то к возвратным товарам
               $arr_article[$new_post_number]['post_info'][$new_post_number_full]['type']['SERVICES'] = '_SERVICES_'; // тип траты
          
               $arr_article[$new_post_number]['post_info'][$new_post_number_full]['services'][$services['name']] = 
               @$arr_article[$new_post_number]['post_info'][$new_post_number_full]['services'][$services['name']] + $services['price']; 
           
       }
   } else {
    // цепляем сервисы к заказу, где
      $arr_article[$new_post_number]['post_info'][$new_post_number_full]['type']['SERVICES'] = '_SERVICES_'; // тип траты
      $arr_article[$new_post_number]['post_info'][$new_post_number_full]['services'][$checks['operation_type']] = $checks['amount']; // тип траты
   }
}
// print_r($arr_services_types);

// die();
// 0189793399-0035 MarketplaceServiceBrandCommission