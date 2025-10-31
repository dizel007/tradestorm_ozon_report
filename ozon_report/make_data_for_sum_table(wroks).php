<?php



 $arr_for_sum_table['Продажи']['-'] = $arr_sum_all_data['sum_accruals_for_sale_direct'];
 $arr_for_sum_table['Возвраты']['-'] = $arr_sum_all_data['sum_accruals_for_sale_return'];
 $arr_for_sum_table['Вознаграждение Ozon']['-'] = $arr_sum_all_data['sum_sale_commission'];

 $arr_for_sum_table['Услуги доставки']['delete'] = 0; // костыль, чтобы это строка выше услуг агенотов была

//// разбираем логистику ///////////////
foreach ($arr_article as $sku=>$data_sku ) {
// делмаем массив с логистикой
   if (isset($data_sku['logistika']) ) {
      foreach ($data_sku['logistika'] as $key=>$data_logistik ) {
        if ($key == 'direct' OR $key== 'return' ) {
         foreach ($data_logistik as $key_logistik => $summa_logistik) {
            $arr_razbor_logistiki[$key_logistik]  = @$arr_razbor_logistiki[$key_logistik] + $summa_logistik;
         }
        
       }
     }
  }
// начинаем формировать массив Услуги агентов (эквайринг цепляем если он есть)
    if (isset($data_sku['amount_ecvairing'])) {
    $arr_for_sum_table['Услуги агентов']['Эквайринг']   = @$arr_for_sum_table['Услуги агентов']['Эквайринг'] + $data_sku['amount_ecvairing'];
    }
}

   
foreach ($arr_razbor_logistiki as $key_log=>&$summa_logistiki) {

    if ($key_log == 'MarketplaceServiceItemDirectFlowLogistic') {
            $arr_for_sum_table['Услуги доставки']['Прямая логистика'] = @$arr_for_sum_table['Услуги доставки']['Прямая логистика'] + $summa_logistiki;
            unset($arr_razbor_logistiki['MarketplaceServiceItemDirectFlowLogistic']);
        }
    elseif  ($key_log == 'MarketplaceServiceItemReturnFlowLogistic') {
             $arr_for_sum_table['Услуги доставки']['Обратная логистика'] = @ $arr_for_sum_table['Услуги доставки']['Обратная логистика'] + $summa_logistiki;
            unset($arr_razbor_logistiki['MarketplaceServiceItemReturnFlowLogistic']);
    }
    elseif  ($key_log == 'MarketplaceServiceItemDropoffSC') {
             $arr_for_sum_table['Услуги доставки']['Обработка отправления Drop-off'] = @$arr_for_sum_table['Услуги доставки']['Обработка отправления Drop-off'] + $summa_logistiki;
            unset($arr_razbor_logistiki['MarketplaceServiceItemDropoffSC']);
    }
    elseif  ($key_log == 'MarketplaceServiceItemRedistributionLastMileCourier') {
            $arr_for_sum_table['Услуги агентов']['Доставка до места выдачи'] = @$arr_for_sum_table['Услуги агентов']['Доставка до места выдачи'] + $summa_logistiki;
            unset($arr_razbor_logistiki['MarketplaceServiceItemRedistributionLastMileCourier']);
    }
    elseif  ($key_log == 'MarketplaceServiceItemRedistributionReturnsPVZ') {
            $arr_for_sum_table['Услуги агентов']['Обработка возвратов, отмен и невыкупов партнёрами']  = @$arr_for_sum_table['Услуги агентов']['Обработка возвратов, отмен и невыкупов партнёрами'] + $summa_logistiki;
            unset($arr_razbor_logistiki['MarketplaceServiceItemRedistributionReturnsPVZ']);
    }
    elseif  ($key_log == 'MarketplaceServiceItemDelivToCustomer') {
            $arr_for_sum_table['Услуги агентов']['Выдача товара']  = @$arr_for_sum_table['Услуги агентов']['Выдача товара'] + $summa_logistiki;
            unset($arr_razbor_logistiki['MarketplaceServiceItemDelivToCustomer']);
    }
    elseif  ($key_log == 'MarketplaceServiceItemRedistributionLastMilePVZ') {
            $arr_for_sum_table['Услуги агентов']['Выдача товара']  = @$arr_for_sum_table['Услуги агентов']['Выдача товара'] + $summa_logistiki;
            unset($arr_razbor_logistiki['MarketplaceServiceItemRedistributionLastMilePVZ']);
    } 

 
}

unset($arr_for_sum_table['Услуги доставки']['delete']); // костыль, чтобы это строка выше услуг агенотов была


//*******************************************************************************************   ** */
//// Услуги ФБО
//********************************************************************************************* */
$service_FBO_name_trat = array('Услуга размещения товаров на складе',
                               'Услуга по бронированию места и персонала для поставки с неполным составом в составе ГМ',
                               'Обработкабрака с приемки',
                               'Услуга по обработке опознанных излишков в составе ГМ',
                               'Подготовка товара к вывозу: Брак',
                               'Обработка брака с приемки',
                               'Вывоз товара со Склада силами Ozon: Доставка до ПВЗ',
                               'Кросс-докинг',
                               'Услуга по бронированию места и персонала для поставки с неполным составом');

foreach ($service_FBO_name_trat as $name_service_FBO) {
     if (isset($arr_sum_services_payment[$name_service_FBO])) 
        {
            $arr_for_sum_table['Услуги ФБО'][$name_service_FBO] = $arr_sum_services_payment[$name_service_FBO];
            unset($arr_sum_services_payment[$name_service_FBO]);
        } 
     elseif (isset($arr_sum_services_payment_with_SKU[$name_service_FBO])) 
        {
            $arr_for_sum_table['Услуги ФБО'][$name_service_FBO] = $arr_sum_services_payment_with_SKU[$name_service_FBO];
            unset($arr_sum_services_payment_with_SKU[$name_service_FBO]);
        } 
}

//********************************************************************************************* */
/// ********************************* Продвижение и реклама 
//********************************************************************************************* */
$reklama_name_trat = array('Закрепление отзыва', 
                           'Баллы за отзывы',
                           'Оплата за клик',
                           'Иные электронные услуги',
                           'Приобретение отзывов на платформе',
                           'Вывод в топ',
                           'Трафареты',
                           'Продвижение в поиске',
                           'Подписка Premium',
                           'Подписка Premium Plus');

// реклама в массиве без привязки к СКУ
foreach ($reklama_name_trat as $name_reklama) {
     if (isset($arr_sum_services_payment[$name_reklama])) 
        {
            $arr_for_sum_table['Продвижение и реклама'][$name_reklama] = $arr_sum_services_payment[$name_reklama];
            unset($arr_sum_services_payment[$name_reklama]);
        }   
 }

 // реклама в массиве  с СКУ
 if (isset($arr_sum_services_payment_with_SKU['Продвижение бренда'])) {
        $arr_for_sum_table['Продвижение и реклама']['Продвижение бренда'] = $arr_sum_services_payment_with_SKU['Продвижение бренда'];
        unset($arr_sum_services_payment_with_SKU['Продвижение бренда']);
    }

//********************************************************************************************* */
/// ****************************  Другие услуги **
//********************************************************************************************* */
$drugie_uslugi_name_trat = array('Утилизация товара: Вы не забрали в срок', 
                                 'Временное размещение товара в СЦ/ПВЗ',
                                 'Услуга по дополнительной обработке ОВХ',
                                 'Обеспечение материалами для упаковки товара',
                                 'Начисление за хранение/утилизацию возвратов',
                                 'Обработка операционных ошибок продавца: отгрузка в нерекомендованный слот',
                                 'Обработка операционных ошибок продавца: отгрузка в нерекомендованный слот - отмена начисления',
                                 'Услуга за обработку операционных ошибок продавца: отмена',
                                 'Утилизация товара: Повреждённые из-за упаковки',
                                 'Упаковка товара партнёрами',
                                 'Услуга за обработку операционных ошибок продавца: просроченная отгрузка',
                                 'Услуга за обработку операционных ошибок продавца: просроченная отгрузка - отмена начисления'
                           );


foreach ($drugie_uslugi_name_trat as $name_drugie_uslugi) {
     if (isset($arr_sum_services_payment[$name_drugie_uslugi])) 
        {
            $arr_for_sum_table['Другие услуги'][$name_drugie_uslugi] = $arr_sum_services_payment[$name_drugie_uslugi];
            unset($arr_sum_services_payment[$name_drugie_uslugi]);

     } elseif (isset($arr_sum_services_payment_with_SKU[$name_drugie_uslugi]))    {
            $arr_for_sum_table['Другие услуги'][$name_drugie_uslugi] = $arr_sum_services_payment_with_SKU[$name_drugie_uslugi];
            unset($arr_sum_services_payment_with_SKU[$name_drugie_uslugi]);
     }
}



//********************************************************************************************* */
/// ************* Компенсации и декомпенсации
//********************************************************************************************* */
// print_r($arr_for_compensation);
// $compensation_and_decompensation_name_trat = array('Потеря по вине Ozon в логистике',
//                                                    'Декомпенсации и возвращение товаров на сток',
//                                                    'Брак по вине Ozon на складе',
//                                                    'Потеря по вине Ozon на складе',
//                                                    'Прочие компенсации');



     if (isset($arr_for_compensation)) {
            foreach ($arr_for_compensation as $compensation_and_decompensation =>$summa_compensation) {
            $arr_for_sum_table['Компенсации и декомпенсации'][$compensation_and_decompensation] = $summa_compensation;
            unset($arr_for_compensation[$compensation_and_decompensation]);

     }
    }

//********************************************************************************************* */
/// ************* Компенсации и декомпенсации
//********************************************************************************************* */

$prochie_nachislenia_name_trat = array('Корректировки стоимости услуг');


foreach ($prochie_nachislenia_name_trat as $prochie_nachislenia) {
     if (isset($arr_sum_services_payment[$prochie_nachislenia])) 
        {
            $arr_for_sum_table['Прочие начисления'][$prochie_nachislenia] = $arr_sum_services_payment[$prochie_nachislenia];
            unset($arr_sum_services_payment[$prochie_nachislenia]);

     }
    }




// Находим сумму начисленно
 $summa_k_nachisleniu = 0;
foreach ($arr_for_sum_table as $temme) {
    foreach ($temme as $pemme) {
        $summa_k_nachisleniu += $pemme;
    }
}
$summa_k_nachisleniu = round($summa_k_nachisleniu,0);



print_r($arr_razbor_logistiki);
print_r($arr_for_compensation);
print_r($arr_sum_services_payment_with_SKU);
print_r($arr_sum_services_payment);
