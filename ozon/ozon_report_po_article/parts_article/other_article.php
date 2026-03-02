<?php 


// foreach ($arr_type_items_WITH_POSTING_NUMBER['other'] as $items) {
//         $arr_operation_type_other[$items['operation_type']] = $items['operation_type'];
// }
// print_r($arr_operation_type_other);
// print_r($arr_type_items_WITH_POSTING_NUMBER['other'][0]);


foreach ($arr_type_items_WITH_POSTING_NUMBER['other'] as $items) {
    $i_other++;
    $our_item = $items['items'];
     $new_post_number = make_posting_number ($items['posting']['posting_number']);
      // техническая инфа 
        $arr_article[$new_post_number]['operation_id'][] = $items['operation_id'];
        $new_post_number_full = $items['posting']['posting_number'];
        
// перебираем список товаров в этом заказе (Там где одиночные борды. Остальные отправления мы разбиваем по 1 штуке)
     
        $arr_article[$new_post_number]['type']['ACQUIRING'] = '_ACQUIRING_';
        $arr_article[$new_post_number]['order_date'] = $items['posting']['order_date'];

if ($items['operation_type'] == 'MarketplaceRedistributionOfAcquiringOperation') //Оплата эквайринга
    { 
                $arr_article[$new_post_number]['amount_ecvairing'] = 
                @$arr_article[$new_post_number]['amount_ecvairing']
                + $items['amount'];
    }
// СУмма претензий (ОНа не привязана к артикулу) /Начисления по претензиям
elseif ($items['operation_type'] == 'OperationClaim') 
        {
            $arr_article[$new_post_number]['shraf_po_pretenziam'] = 
            @$arr_article[$new_post_number]['shraf_po_pretenziam']
             +  $items['amount'];
        }
else {
         $arr_nerazobrannoe['other'][]=$items;
    }
    
}


// echo "<pre>";
// print_r($arr_nerazjbrannoe);