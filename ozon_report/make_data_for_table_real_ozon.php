<?php 

// echo "<pre>";
// print_r($arr_article[1861485446]);
// die();
function get_data_sell_tovar($data) { 
    if (isset($data)) {return round($data,0);} else {return 0;}
}
/****************************************************************************
******************** формируем массив сумм 
/****************************************************************************/
    $arr_summ['Цена для покупателя'] = 0;
    $arr_summ['Сумма продаж'] = 0;
    $arr_summ['Комиссия озона'] = 0;
    $arr_summ['Логистика'] = 0;
    $arr_summ['Сервисы'] = 0;
    $arr_summ['Эквайринг'] = 0;
    $arr_summ['Цена за вычетом с арктикулом'] = 0;
    $arr_summ['Процент распределения стоимости'] = 0;
    $arr_summ['Сумма распределения доп.услуг'] = 0;
    $arr_summ['Сумма без всего'] = 0;


$arr_real_ozon_data = [];
foreach ($arr_article as $sku_ozon=>$print_item) {   
/**************************************************************************************/
// 
/**************************************************************************************/
    $arr_real_ozon_data[$sku_ozon]['name'] = $print_item['name'];
    $arr_real_ozon_data[$sku_ozon]['sku'] = $print_item['sku'];
// Формируем квери параметры для разбора по артикулу
  $query_data = [
    'file_name_ozon_small' => $file_name_ozon_small,
    'article' => $sku_ozon,
    'clt' => $client_id,
    ];
    $query_string = http_build_query($query_data);
    $secret_query_string =  base64_encode($query_string);
    
    $arr_real_ozon_data[$sku_ozon]['link_for_report_article'] = "../ozon_report_po_article/index_ozon_razbor_article.php?data=$secret_query_string";
    $arr_real_ozon_data[$sku_ozon]['link_for_site_ozon'] = "https://www.ozon.ru/product/".$print_item['sku'];
/**************************************************************************************/ 
///    Количество продаж 
/**************************************************************************************/
 $arr_real_ozon_data[$sku_ozon]['count']['direct'] =  get_data_sell_tovar(@$print_item['count']['direct']);
 $arr_real_ozon_data[$sku_ozon]['count']['return'] =  get_data_sell_tovar(@$print_item['count']['return']);
 $arr_real_ozon_data[$sku_ozon]['count']['summa']  =  get_data_sell_tovar(@$print_item['count']['summa']);

/**************************************************************************************/
//  Данные по стоимости продаж (Цена товара в ЛК)
/**************************************************************************************/
 $arr_real_ozon_data[$sku_ozon]['summa']['accruals_for_sale']  =  get_data_sell_tovar(@$print_item['accruals_for_sale']['summa']);
 $arr_real_ozon_data[$sku_ozon]['one_item']['accruals_for_sale']  =  get_data_sell_tovar(@$print_item['accruals_for_sale']['one_item']);

if (isset($print_item['accruals_for_sale']['summa'])) {
    $arr_real_ozon_data[$sku_ozon]['one_procent_from_accruals_for_sale'] = round( $arr_real_ozon_data[$sku_ozon]['summa']['accruals_for_sale']/100,4);
    $arr_summ['Цена для покупателя'] = @$arr_summ['Цена для покупателя'] +  $arr_real_ozon_data[$sku_ozon]['summa']['accruals_for_sale'];
} else {
    $arr_real_ozon_data[$sku_ozon]['one_procent_from_accruals_for_sale'] = 0;
}



/**************************************************************************************/
/// *******************   Комиссия озона   **************************
/**************************************************************************************/
 $arr_real_ozon_data[$sku_ozon]['summa']['sale_commission']  =  get_data_sell_tovar(@$print_item['sale_commission']['summa']);
 $arr_real_ozon_data[$sku_ozon]['one_item']['sale_commission']  =  get_data_sell_tovar(@$print_item['sale_commission']['one_item']);

 $arr_summ['Комиссия озона'] = $arr_summ['Комиссия озона'] +  $arr_real_ozon_data[$sku_ozon]['summa']['sale_commission'];
 
 /**************************************************************************************/
/// *******************   логистка  **************************
/**************************************************************************************/
 $arr_real_ozon_data[$sku_ozon]['summa']['logistika']  =  get_data_sell_tovar(@$print_item['logistika']['summa']);

 $arr_summ['Логистика'] = $arr_summ['Логистика'] + $arr_real_ozon_data[$sku_ozon]['summa']['logistika'];

/**************************************************************************************/
// ************************** Цена продажи *******************************************************************
/**************************************************************************************/
$arr_real_ozon_data[$sku_ozon]['summa']['amount']  =  get_data_sell_tovar(@$print_item['amount']['summa']);

$arr_summ['Сумма продаж'] = @$arr_summ['Сумма продаж'] + $arr_real_ozon_data[$sku_ozon]['summa']['amount'];

/**************************************************************************************/
/// *******************  Сервисы  **************************
/**************************************************************************************/
 $arr_real_ozon_data[$sku_ozon]['summa']['services']  =  get_data_sell_tovar(@$print_item['services']['summa']);
 
 $arr_summ['Сервисы'] = $arr_summ['Сервисы'] +   $arr_real_ozon_data[$sku_ozon]['summa']['services'];

/**************************************************************************************/
/// ******************* Эквайринг  **************************
/**************************************************************************************/
$arr_real_ozon_data[$sku_ozon]['summa']['amount_ecvairing']  =  get_data_sell_tovar(@$print_item['amount_ecvairing']);

$arr_summ['Эквайринг'] = $arr_summ['Эквайринг'] + $print_item['amount_ecvairing'];
/**************************************************************************************/
 // Цена за вычетом всего где есть артикул
/**************************************************************************************/
$arr_real_ozon_data[$sku_ozon]['summa']['bez_vsego_gde_est_artikul']  =  get_data_sell_tovar(@$print_item['summa_bez_vsego_gde_est_artikul']);

$arr_summ['Цена за вычетом с арктикулом'] = $arr_summ['Цена за вычетом с арктикулом'] + $print_item['summa_bez_vsego_gde_est_artikul'];

/**************************************************************************************/
// **************** Процент распределения стоимости *****************
/**************************************************************************************/
if (isset($print_item['proc_item_ot_vsey_summi'])) {
    $arr_real_ozon_data[$sku_ozon]['proc_item_ot_vsey_summi'] =  $print_item['proc_item_ot_vsey_summi'];
} else {
    $arr_real_ozon_data[$sku_ozon]['proc_item_ot_vsey_summi'] = 0;   
}
$arr_summ['Процент распределения стоимости'] = $arr_summ['Процент распределения стоимости'] + $print_item['proc_item_ot_vsey_summi'];

/**************************************************************************************/
// ****************Дополнительные услуги   
/**************************************************************************************/
$arr_real_ozon_data[$sku_ozon]['summa']['dop_uslugi']  =  get_data_sell_tovar(@$print_item['dop_uslugi']);

$arr_summ['Сумма распределения доп.услуг'] = $arr_summ['Сумма распределения доп.услуг'] + $print_item['dop_uslugi'];


/**************************************************************************************/
// Цена за вычетом всех расходов 
/**************************************************************************************/
$arr_real_ozon_data[$sku_ozon]['summa']['bez_vsego']  =  get_data_sell_tovar(@$print_item['summa_bez_vsego']);

$arr_summ['Сумма без всего'] = @$arr_summ['Сумма без всего'] + $print_item['summa_bez_vsego'];

 /**************************************************************************************/
// ***************  Данные по себестоимости
/**************************************************************************************/
$arr_real_ozon_data[$sku_ozon]['mp_article']   =  @$arr_sebestoimost[$sku_ozon]['mp_article'];
$arr_real_ozon_data[$sku_ozon]['min_price']    =  get_data_sell_tovar(@$arr_sebestoimost[$sku_ozon]['min_price']);
$arr_real_ozon_data[$sku_ozon]['main_price']   =  get_data_sell_tovar(@$arr_sebestoimost[$sku_ozon]['main_price']);




/**************************************************************************************/
/// Считаем все для одного товара 
/**************************************************************************************/

if ($arr_real_ozon_data[$sku_ozon]['count']['summa'] !=0) {
    $arr_real_ozon_data[$sku_ozon]['one_item']['logistika']  =  round ($arr_real_ozon_data[$sku_ozon]['summa']['logistika']/$arr_real_ozon_data[$sku_ozon]['count']['summa'],0);
    $arr_real_ozon_data[$sku_ozon]['one_item']['amount']  =  round($arr_real_ozon_data[$sku_ozon]['summa']['amount']/ $arr_real_ozon_data[$sku_ozon]['count']['summa'],0);
    $arr_real_ozon_data[$sku_ozon]['one_item']['services']  =  round($arr_real_ozon_data[$sku_ozon]['summa']['services']/$arr_real_ozon_data[$sku_ozon]['count']['summa'],0);
    $arr_real_ozon_data[$sku_ozon]['one_item']['ecvairing']  =  round($arr_real_ozon_data[$sku_ozon]['summa']['amount_ecvairing']/$arr_real_ozon_data[$sku_ozon]['count']['summa'],0);
    $arr_real_ozon_data[$sku_ozon]['one_item']['bez_vsego_gde_est_artikul']  =  round ($arr_real_ozon_data[$sku_ozon]['summa']['bez_vsego_gde_est_artikul']/$arr_real_ozon_data[$sku_ozon]['count']['summa'],0);
    $arr_real_ozon_data[$sku_ozon]['one_item']['dop_uslugi']  =  round($arr_real_ozon_data[$sku_ozon]['summa']['dop_uslugi']/$arr_real_ozon_data[$sku_ozon]['count']['summa'],0);
    $arr_real_ozon_data[$sku_ozon]['one_item']['bez_vsego']  =  round($arr_real_ozon_data[$sku_ozon]['summa']['bez_vsego']/$arr_real_ozon_data[$sku_ozon]['count']['summa'],0);
 
    $arr_real_ozon_data[$sku_ozon]['diff_min_price']   =   $arr_real_ozon_data[$sku_ozon]['one_item']['bez_vsego'] - $arr_real_ozon_data[$sku_ozon]['min_price'] ;
    $arr_real_ozon_data[$sku_ozon]['diff_main_price']   =  $arr_real_ozon_data[$sku_ozon]['one_item']['bez_vsego'] - $arr_real_ozon_data[$sku_ozon]['main_price'] ;

 } else {
    $arr_real_ozon_data[$sku_ozon]['one_item']['logistika']  =  0;
    $arr_real_ozon_data[$sku_ozon]['one_item']['amount']  =  0;
    $arr_real_ozon_data[$sku_ozon]['one_item']['services']  =  0;
    $arr_real_ozon_data[$sku_ozon]['one_item']['ecvairing']  =  0;
    $arr_real_ozon_data[$sku_ozon]['one_item']['bez_vsego_gde_est_artikul']  = 0;
    $arr_real_ozon_data[$sku_ozon]['one_item']['dop_uslugi']  =  0;
    $arr_real_ozon_data[$sku_ozon]['one_item']['bez_vsego']  =  0;
 
    $arr_real_ozon_data[$sku_ozon]['diff_min_price']   = 0;
    $arr_real_ozon_data[$sku_ozon]['diff_main_price']  = 0;

 


 }

/**************************************************************************************/
/// Считаем проценты затрат
/**************************************************************************************/

if ($arr_real_ozon_data[$sku_ozon]['one_procent_from_accruals_for_sale'] !=0) {

   $arr_real_ozon_data[$sku_ozon]['one_procent']['logistika'] = abs(round(($arr_real_ozon_data[$sku_ozon]['summa']['logistika']/$arr_real_ozon_data[$sku_ozon]['one_procent_from_accruals_for_sale'] ),1));
   $arr_real_ozon_data[$sku_ozon]['one_procent']['services'] =  abs(round(($arr_real_ozon_data[$sku_ozon]['summa']['services']/$arr_real_ozon_data[$sku_ozon]['one_procent_from_accruals_for_sale'] ),1));
   $arr_real_ozon_data[$sku_ozon]['one_procent']['ecvairing'] = abs(round(($arr_real_ozon_data[$sku_ozon]['summa']['amount_ecvairing']/$arr_real_ozon_data[$sku_ozon]['one_procent_from_accruals_for_sale'] ),1));
   $arr_real_ozon_data[$sku_ozon]['one_procent']['sale_commission'] = abs(round(($arr_real_ozon_data[$sku_ozon]['summa']['sale_commission']/$arr_real_ozon_data[$sku_ozon]['one_procent_from_accruals_for_sale'] ),1));
   $arr_real_ozon_data[$sku_ozon]['one_procent']['dop_uslugi'] = abs(round(($arr_real_ozon_data[$sku_ozon]['summa']['dop_uslugi']/$arr_real_ozon_data[$sku_ozon]['one_procent_from_accruals_for_sale'] ),1));

  } else {
   $arr_real_ozon_data[$sku_ozon]['one_procent']['logistika'] = 0;
   $arr_real_ozon_data[$sku_ozon]['one_procent']['services'] = 0;
   $arr_real_ozon_data[$sku_ozon]['one_procent']['ecvairing'] = 0;
   $arr_real_ozon_data[$sku_ozon]['one_procent']['sale_commission'] = 0;
   $arr_real_ozon_data[$sku_ozon]['one_procent']['dop_uslugi'] = 0;

  }



/**************************************************************************************/
// чистая прибыль ///
/**************************************************************************************/

$arr_real_ozon_data[$sku_ozon]['summa']['sebestoimost'] = $arr_real_ozon_data[$sku_ozon]['min_price'] * $arr_real_ozon_data[$sku_ozon]['count']['summa']; 
$arr_real_ozon_data[$sku_ozon]['summa']['pribil'] = $arr_real_ozon_data[$sku_ozon]['summa']['bez_vsego'] - $arr_real_ozon_data[$sku_ozon]['summa']['sebestoimost']; 

$arr_summ['Сумма себестоимость'] = @$arr_summ['Сумма себестоимость'] + $arr_real_ozon_data[$sku_ozon]['summa']['sebestoimost'] ;
$arr_summ['Сумма прибыль'] = @$arr_summ['Сумма прибыль'] + $arr_real_ozon_data[$sku_ozon]['summa']['pribil'];

                           
}



uasort($arr_real_ozon_data, function($a, $b) {
    return  $b['summa']['bez_vsego'] - $a['summa']['bez_vsego'] ;
});

