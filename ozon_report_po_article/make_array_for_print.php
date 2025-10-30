<?php

  
    $arr_type_logistik = array('MarketplaceServiceItemDirectFlowLogistic' => 'Услуги доставки Прямая логистика',
                                'MarketplaceServiceItemReturnFlowLogistic' => 'Услуги доставки Обратная логистика',
                                'MarketplaceServiceItemDropoffSC' => 'Услуги доставки Обработка отправления Drop-off',
                                'MarketplaceServiceItemRedistributionLastMileCourier' => 'Услуги агентов Доставка до места выдачи',
                                'MarketplaceServiceItemRedistributionReturnsPVZ' => 'Услуги агентов Обработка возвратов, отмен и невыкупов партнёрами',
                                'MarketplaceServiceItemDelivToCustomer' => 'Услуги агентов Выдача товара',
                                'MarketplaceServiceItemRedistributionLastMilePVZ' => 'Услуги агентов Выдача товара_'
                                ) ;
            

// print_r($arr_services_types);

foreach ($arr_article_WORK_need_article as $key=>&$one_tovar) {
   // если есть проданные товары 
       
           $one_tovar['post_number_gruzomesto'] = $key;
        if (!isset($one_tovar['accruals_for_sale'])) {
            $one_tovar['accruals_for_sale'] = 0;
            $one_tovar['one_procent_accruals_for_sale'] = 0;

        } else {
            $one_tovar['accruals_for_sale'] = $one_tovar['accruals_for_sale']; // Цена в личном кабинете
            $one_tovar['one_procent_accruals_for_sale'] = round($one_tovar['accruals_for_sale']/100,2);

        }
        
        if (!isset($one_tovar['amount'])) {
            $one_tovar['amount'] = 0;
        } else {
            $one_tovar['amount'] = $one_tovar['amount']; //
        }

        if (!isset($one_tovar['sale_commission'])) {
            $one_tovar['sale_commission'] = 0;
            $one_tovar['ozon_procent_commition'] = 0;
        } else {
            $one_tovar['sale_commission'] = $one_tovar['sale_commission']; //
            if ($one_tovar['one_procent_accruals_for_sale']  !=0) {
            $one_tovar['ozon_procent_commition'] = round(-$one_tovar['sale_commission']/$one_tovar['one_procent_accruals_for_sale'] ,2);
            } else {
              $one_tovar['ozon_procent_commition'] = 0;  
            }

        }
        
        
        
        


       // Эквайригн
        //  if (isset($items_in_order['amount_ecvairing'])){
        //     $one_string_for_table['ecvairing'] = round($items_in_order['amount_ecvairing']/$items_in_order['count'],2); // эквайринг
        //  }
        // $array_services=[];
        // Если есть дополнительные сервисы в промой доставки, то цепляем их
               

        if (isset($one_tovar['services'])){
            
            find_type_logistika($one_tovar);
            
            find_type_services($one_tovar, $arr_services_types);
        }

if (count($one_tovar['services']) > 0) {
    print_r($one_tovar);
}
        $one_sku_in_reestr[] =  $one_tovar;
             // конец перебора всех отправлений в item_buy        
    }

print_r($one_sku_in_reestr[0]);

// 31706044-0231-1



function find_type_logistika(&$one_tovar) {
// print_r($one_tovar);
foreach ($one_tovar['services'] as $key_log=>$summa_logistiki) {
    // Удаляем нулевые статьи затрат
    if ($summa_logistiki == 0) {
         unset($one_tovar['services'][$key_log]);  
    }
    // остальные разбираем
    if ($key_log == 'MarketplaceServiceItemDirectFlowLogistic') {
            @$one_tovar['логистика']['Услуги доставки']['Прямая логистика'] = $summa_logistiki;
             unset($one_tovar['services'][$key_log]);
        }
    elseif  ($key_log == 'MarketplaceServiceItemReturnFlowLogistic') {
             @$one_tovar['логистика']['Услуги доставки']['Обратная логистика'] + $summa_logistiki;
             unset($one_tovar['services'][$key_log]);
      }
    elseif  ($key_log == 'MarketplaceServiceItemDropoffSC') {
             @$one_tovar['логистика']['Услуги доставки']['Обработка отправления Drop-off'] = $summa_logistiki;
             unset($one_tovar['services'][$key_log]);
           
    }
    elseif  ($key_log == 'MarketplaceServiceItemRedistributionLastMileCourier') {
            @$one_tovar['логистика']['Услуги агентов']['Доставка до места выдачи'] = $summa_logistiki;
            unset($one_tovar['services'][$key_log]);
           
    }
    elseif  ($key_log == 'MarketplaceServiceItemRedistributionReturnsPVZ') {
            @$one_tovar['логистика']['Услуги агентов']['Обработка возвратов, отмен и невыкупов партнёрами']  =  $summa_logistiki;
            unset($one_tovar['services'][$key_log]);
          
    }
    elseif  ($key_log == 'MarketplaceServiceItemDelivToCustomer') {
            @$one_tovar['логистика']['Услуги агентов']['Выдача товара']  =  $summa_logistiki;
            unset($one_tovar['services'][$key_log]);
           
    }
    elseif  ($key_log == 'MarketplaceServiceItemRedistributionLastMilePVZ') {
            @$one_tovar['логистика']['Услуги агентов']['Выдача товара_']  = $summa_logistiki;
            unset($one_tovar['services'][$key_log]);
    } 

 
}

return $one_tovar;
}

function find_type_services(&$one_tovar, $arr_services_types) {

    foreach ($one_tovar['services'] as $our_type_service=>$summa_service) {
        foreach ($arr_services_types as $docs_type_service=>$docs_name_service) {
            if ($our_type_service == $docs_type_service) {
              @$one_tovar['сервисы'][$docs_name_service] =  $summa_service;
               unset($one_tovar['services'][$our_type_service]);
            }
        }
    }
return $one_tovar;

}
