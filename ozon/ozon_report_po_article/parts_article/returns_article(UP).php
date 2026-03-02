<?php


// print_r($arr_type_items_WITH_POSTING_NUMBER['returns']);


// foreach ($arr_type_items_WITH_POSTING_NUMBER['returns'] as $items) {
//         $arr_operation_type_returns[$items['operation_type']] = $items['operation_type'];
// }
// print_r($arr_operation_type_returns);
// foreach ($arr_type_items_WITH_POSTING_NUMBER['returns'] as $items) {
//       if ($items['operation_type'] == 'ClientReturnAgentOperation') {
//         // if (count($items['services']) == 1 )
//          print_r($items);
// //       }
// }
// }




// die();
$i_returns = 0;
foreach ($arr_type_items_WITH_POSTING_NUMBER['returns'] as $big_key => $items) {

        $i_returns++;

        $our_item = $items['items'];
        $new_post_number = make_posting_number($items['posting']['posting_number']);
        // техническая инфа 
        $arr_article[$new_post_number]['operation_id'][] = $items['operation_id'];
        $new_post_number_full = $items['posting']['posting_number'];
        $arr_article[$new_post_number]['post_number'] = $new_post_number;
        $arr_article[$new_post_number]['type']['RETURN'] = '_RETURN_';
        $arr_article[$new_post_number]['WORK'] = 'WORK';
        $arr_article[$new_post_number]['order_date'] = $items['posting']['order_date'];
        $arr_article[$new_post_number]['delivery_schema'] = $items['posting']['delivery_schema'];

        foreach ($our_item as $item) {

                $arr_number_tovar[$new_post_number] = @$arr_number_tovar[$new_post_number] + 1; // порядковый номер товара в заказе
                $new_sku = $item['sku'];
                $c_1c_article = $new_sku;

                $arr_article[$new_post_number]['items_returns'][$new_post_number_full]['sku'] = $new_sku;
                $arr_article[$new_post_number]['items_returns'][$new_post_number_full]['name'] =  $item['name'];
                $arr_article[$new_post_number]['items_returns'][$new_post_number_full]['c_1c_article'] = $c_1c_article;

// **************************************************************************
                // Доставка и обработка возврата, отмены, невыкупа
                if (($items['operation_type'] == 'OperationReturnGoodsFBSofRMS') || ($items['operation_type'] == 'OperationItemReturn')
                        || ($items['operation_type'] == 'OperationAgentStornoDeliveredToCustomer')) { 
                   foreach ($items['services'] as $return_dop_obrabotka) 
                        {
                        $arr_article[$new_post_number]['items_returns'][$new_post_number_full]['services']['return'][$return_dop_obrabotka['name']] =
                        @$arr_article[$new_post_number]['items_returns'][$new_post_number_full]['services']['return'][$return_dop_obrabotka['name']]
                        + $return_dop_obrabotka['price'];
                          }
                      } 
                elseif ($items['operation_type'] == 'ClientReturnAgentOperation') {
        //************* Получение возврата, отмены, невыкупа от покупателя      */
                        // Деньги и комиссии которые возвращаются
                        $arr_article[$new_post_number]['items_returns'][$new_post_number_full]['amount'] = $items['amount'];
                        $arr_article[$new_post_number]['items_returns'][$new_post_number_full]['sale_commission'] = $items['sale_commission'];
                        $arr_article[$new_post_number]['items_returns'][$new_post_number_full]['accruals_for_sale'] = $items['accruals_for_sale'];
                // количество возвратов в заказке
                        $arr_article[$new_post_number]['items_returns'][$new_post_number_full]['count_return'] = 
                        @$arr_article[$new_post_number]['items_returns'][$new_post_number_full]['count_return'] + 1;
                                } else {
                $arr_nerazobrannoe['returns'][] = $items;
                }
 }
}

// print_r($arr_article);