<?php

  
    $arr_type_find_servives['Услуги доставки'] = array('MarketplaceServiceItemDirectFlowLogistic' => 'Прямая логистика',
                                'MarketplaceServiceItemReturnFlowLogistic' => 'Обратная логистика',
                                'MarketplaceServiceItemDropoffSC' => 'Обработка отправления Drop-off',
                                );


    $arr_type_find_servives['Услуги агентов'] = array('MarketplaceServiceItemRedistributionLastMileCourier' => 'Доставка до места выдачи',
                                     'MarketplaceServiceItemRedistributionReturnsPVZ' => 'Обработка возвратов, отмен и невыкупов партнёрами',
                                     'MarketplaceServiceItemDelivToCustomer' => 'Выдача товара',
                                     'MarketplaceServiceItemRedistributionLastMilePVZ' => 'Выдача товара_'
                                ) ;

  // добавляем эту трату вручную, потому что в озон она в типе операуии и в типах сервисах разные  
  // MarketplaceReturnStorageServiceAtThePickupPointFbsItem -> OperationMarketplaceReturnStorageServiceAtThePickupPointFbs  
    $arr_services_types['MarketplaceReturnStorageServiceAtThePickupPointFbsItem'] = 'Начисление за хранение/утилизацию возвратов';       
    $arr_type_find_servives['сервисы'] =  $arr_services_types;
     
                                
// print_r($arr_type_find_servives);
// die();

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
             $one_tovar['services_history'] =  $one_tovar['services']; // Делаем копию сервисов // для отладки
            raspredelenie_servicnih_rashodov ($one_tovar, $arr_type_find_servives);
            // find_type_logistika($one_tovar);
            
            // find_type_services($one_tovar, $arr_services_types);
        }

        if (isset($one_tovar['services']) AND (count($one_tovar['services']) > 0)) {
            print_r($one_tovar);
        }
        $one_sku_in_reestr[] =  $one_tovar;
             // конец перебора всех отправлений в item_buy        
    }

// print_r($one_sku_in_reestr[0]);


/*****************************************************************************
 * функция ищет в массиве сервисов к какому типа расходов она относится,
 * затем в массив заносит русское название расходов и его сумму
 * затем удаляет разнесенные сервисы
 *****************************************************************************/
function raspredelenie_servicnih_rashodov (&$one_tovar, $arr_type_find_servives) {
    $nashi_tovar = 0;
    foreach ($one_tovar['services'] as $service=>$service_summa) {
         // Удаляем нулевые статьи затрат
    if ($service_summa == 0) {
         unset($one_tovar['services'][$service]);
         continue; 
    }   
        foreach ($arr_type_find_servives as $category=>$service_type_spisok){
            foreach ($service_type_spisok as $ozon_type_service=>$rus_name_service){
                if ($ozon_type_service == $service ) {
                   $one_tovar[$category][$rus_name_service] = $service_summa;
                   unset($one_tovar['services'][$service]);
                   $nashi_tovar =1;
                }
            
            }

 
     }
     if ($nashi_tovar == 0) {echo "$service = $service_summa<br>";}
    }


return $one_tovar;
}