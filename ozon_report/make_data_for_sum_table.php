<?php
// echo "***********************<br>";
// print_r($arr_sum_services_payment);
// echo "***********************<br>";

require_once "../spravochnik_zatrat.php";

 $arr_for_sum_table['Продажи']['-'] = $arr_sum_all_data['sum_accruals_for_sale_direct'];
 $arr_for_sum_table['Возвраты']['-'] = $arr_sum_all_data['sum_accruals_for_sale_return'];
 $arr_for_sum_table['Вознаграждение Ozon']['-'] = $arr_sum_all_data['sum_sale_commission'];

 $arr_for_sum_table['Услуги доставки']['delete'] = 0; // костыль, чтобы это строка выше услуг агенотов была
 $arr_for_sum_table['Услуги агентов']['delete'] = 0;
 $arr_for_sum_table['Услуги ФБО']['delete'] = 0;
 $arr_for_sum_table['Продвижение и реклама']['delete'] = 0;
 $arr_for_sum_table['Другие услуги']['delete'] = 0;
 $arr_for_sum_table['Компенсации и декомпенсации']['delete'] = 0;
 $arr_for_sum_table['Прочие начисления']['delete'] = 0;

//// разбираем логистику ///////////////
foreach ($arr_article as $sku=>$data_sku ) {
// делмаем массив с логистикой
   if (isset($data_sku['logistika']) ) {
      foreach ($data_sku['logistika'] as $key=>$data_logistik ) {
        if ($key == 'direct' OR $key== 'return' ) {
         foreach ($data_logistik as $type_logistik => $summa_logistik) {
            $arr_razbor_logistiki[$type_logistik]  = @$arr_razbor_logistiki[$type_logistik] + $summa_logistik;
         }
       }
     }
    raspredelenie_servicnih_rashodov ($arr_for_sum_table, $arr_razbor_logistiki ,  $arr_type_find_servives);

unset($arr_for_sum_table['Услуги доставки']['delete']); // костыль, чтобы это строка выше услуг агенотов была 
  }
  
// начинаем формировать массив Услуги агентов (эквайринг цепляем если он есть)
    if (isset($data_sku['amount_ecvairing'])) {
    $arr_for_sum_table['Услуги агентов']['Эквайринг']   = @$arr_for_sum_table['Услуги агентов']['Эквайринг'] + $data_sku['amount_ecvairing'];
    }
}




//********************************************************************************************* */
/// ********************************* Продвижение и реклама, Услуги ФБО, Другие услуги 
//********************************************************************************************* */
// реклама в массиве без привязки к СКУ
 if (isset($arr_sum_services_payment)) {
        raspredelenie_servicnih_rashodov ($arr_for_sum_table, $arr_sum_services_payment ,  $arr_type_find_servives);
    }   
//  реклама в массиве  с СКУ
 if (isset($arr_sum_services_payment_with_SKU)) {
      raspredelenie_servicnih_rashodov ($arr_for_sum_table, $arr_sum_services_payment_with_SKU ,  $arr_type_find_servives);
   }
// echo "***********************<br>";
// print_r($arr_sum_services_payment);
// echo "***********************<br>";
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
/// ************* Прочие начисления
//********************************************************************************************* */

// $prochie_nachislenia_name_trat = array('Корректировки стоимости услуг');


// foreach ($prochie_nachislenia_name_trat as $prochie_nachislenia) {
//      if (isset($arr_sum_services_payment[$prochie_nachislenia])) 
//         {
//             $arr_for_sum_table['Прочие начисления'][$prochie_nachislenia] = $arr_sum_services_payment[$prochie_nachislenia];
//             unset($arr_sum_services_payment[$prochie_nachislenia]);

//      }
//     }
// Находим сумму начисленно
 $summa_k_nachisleniu = 0;
foreach ($arr_for_sum_table as $temme) {
    foreach ($temme as $pemme) {
        $summa_k_nachisleniu += $pemme;
    }
}
$summa_k_nachisleniu = round($summa_k_nachisleniu,0);

// print_r($arr_razbor_logistiki);
// print_r($arr_for_compensation);
// print_r($arr_sum_services_payment_with_SKU);
// print_r($arr_sum_services_payment);

 unset($arr_for_sum_table['Услуги доставки']['delete']); // костыль, чтобы это строка выше услуг агенотов)
 unset($arr_for_sum_table['Услуги агентов']['delete']);
 unset($arr_for_sum_table['Услуги ФБО']['delete']);
 unset($arr_for_sum_table['Продвижение и реклама']['delete']);
 unset($arr_for_sum_table['Другие услуги']['delete']);
 unset($arr_for_sum_table['Компенсации и декомпенсации']['delete']);
 unset($arr_for_sum_table['Прочие начисления']['delete']);