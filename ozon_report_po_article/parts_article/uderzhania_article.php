<?php

// print_r($arr_type_items_WITH_POSTING_NUMBER['compensation']);


foreach ($arr_type_items_WITH_POSTING_NUMBER['compensation'] as $items) {
        $i_compensation++;
            
    $new_post_number = make_posting_number ($items['posting']['posting_number']);
      // техническая инфа 
    $arr_article[$new_post_number]['operation_id'][] = $items['operation_id'];
    $arr_article[$new_post_number]['type']['UDERZHANIA'] = '_UDERZHANIA_';
    $arr_article[$new_post_number]['order_date'] = $items['posting']['order_date'];

      // количество товаров в заказе, которые вернули
    $arr_article[$new_post_number]['count_compensation'] = @$arr_article[$new_post_number]['count_compensation'] + count($our_item);
    // Суммируем суммы операции, которые возвраты
    $arr_article[$new_post_number]['compensation'][$items['operation_type_name']] = 
    @$arr_article[$new_post_number]['compensation'][$items['operation_type_name']] + $items['amount']; 

} 
