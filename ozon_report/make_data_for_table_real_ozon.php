<?php 

echo "<pre>";
print_r($arr_article[1861485446]);
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
    $arr_real_ozon_data[$sku_ozon]['link_for_report_article'] = "../ozon_report_po_article/index_ozon_razbor_article.php?file_name_ozon=$file_name_ozon&article=".$print_item['sku']."&clt=$client_id";
    $arr_real_ozon_data[$sku_ozon]['link_for_site_ozon'] = "https://www.ozon.ru/product/".$print_item['sku'];
/**************************************************************************************/ 
///    Количество продаж 
/**************************************************************************************/
 $arr_real_ozon_data[$sku_ozon]['count_direct'] =  get_data_sell_tovar(@$print_item['count']['direct']);
 $arr_real_ozon_data[$sku_ozon]['count_return'] =  get_data_sell_tovar(@$print_item['count']['return']);
 $arr_real_ozon_data[$sku_ozon]['count_summa']  =  get_data_sell_tovar(@$print_item['count']['summa']);

/**************************************************************************************/
//  Данные по стоимости продаж (Цена товара в ЛК)
/**************************************************************************************/
 $arr_real_ozon_data[$sku_ozon]['accruals_for_sale_summa']  =  get_data_sell_tovar(@$print_item['accruals_for_sale']['summa']);
 $arr_real_ozon_data[$sku_ozon]['accruals_for_sale_one_item']  =  get_data_sell_tovar(@$print_item['accruals_for_sale']['one_item']);

if (isset($print_item['accruals_for_sale']['summa'])) {
    $arr_real_ozon_data[$sku_ozon]['one_procent_from_accruals_for_sale'] = round($print_item['accruals_for_sale']['summa']/100,4);
    $arr_summ['Цена для покупателя'] = @$arr_summ['Цена для покупателя'] + $print_item['accruals_for_sale']['summa'];
} else {
    $arr_real_ozon_data[$sku_ozon]['one_procent_from_accruals_for_sale'] = 0;
}



/**************************************************************************************/
/// *******************   Комиссия озона   **************************
/**************************************************************************************/
 $arr_real_ozon_data[$sku_ozon]['sale_commission_summa']  =  get_data_sell_tovar(@$print_item['sale_commission']['summa']);
 $arr_real_ozon_data[$sku_ozon]['sale_commission_one_item']  =  get_data_sell_tovar(@$print_item['sale_commission']['one_item']);
 if ($arr_real_ozon_data[$sku_ozon]['one_procent_from_accruals_for_sale'] !=0) {
 $arr_real_ozon_data[$sku_ozon]['one_procent_from_sale_commission'] = abs(round((@$print_item['sale_commission']['summa']/$arr_real_ozon_data[$sku_ozon]['one_procent_from_accruals_for_sale'] ),1));
 } else {
  $arr_real_ozon_data[$sku_ozon]['one_procent_from_sale_commission'] = 0;  
 }
 $arr_summ['Комиссия озона'] = $arr_summ['Комиссия озона'] + @$print_item['sale_commission']['summa'];
 
 /**************************************************************************************/
/// *******************   логистка  **************************
/**************************************************************************************/
 $arr_real_ozon_data[$sku_ozon]['logistika_summa']  =  get_data_sell_tovar(@$print_item['logistika']['summa']);
 if ($arr_real_ozon_data[$sku_ozon]['count_summa'] !=0) {
    $arr_real_ozon_data[$sku_ozon]['logistika_one_item']  =  round (@$print_item['logistika']['summa']/$arr_real_ozon_data[$sku_ozon]['count_summa'],0);
    $arr_real_ozon_data[$sku_ozon]['one_procent_from_logistika'] = abs(round((@$print_item['logistika']['summa']/$arr_real_ozon_data[$sku_ozon]['one_procent_from_accruals_for_sale'] ),1));

 } else {
    $arr_real_ozon_data[$sku_ozon]['logistika_one_item'] =0;  
    $arr_real_ozon_data[$sku_ozon]['one_procent_from_logistika'] = 0;
 }

 $arr_summ['Логистика'] = $arr_summ['Логистика'] + @$print_item['logistika']['summa'];

/**************************************************************************************/
// ************************** Цена продажи *******************************************************************
/**************************************************************************************/

 $arr_real_ozon_data[$sku_ozon]['amount_summa']  =  get_data_sell_tovar(@$print_item['amount']['summa']);
if ($arr_real_ozon_data[$sku_ozon]['count_summa'] !=0) {
 $arr_real_ozon_data[$sku_ozon]['amount_one_item']  =  round(@$print_item['amount']['summa']/ $arr_real_ozon_data[$sku_ozon]['count_summa'],0);
 } else {
  $arr_real_ozon_data[$sku_ozon]['amount_one_item']  = 0;   
 } 
$arr_summ['Сумма продаж'] = @$arr_summ['Сумма продаж'] + @$print_item['amount']['summa'];

/**************************************************************************************/
/// *******************  Сервисы  **************************
/**************************************************************************************/
 $arr_real_ozon_data[$sku_ozon]['services_summa']  =  get_data_sell_tovar(@$print_item['services']['summa']);
 if ($arr_real_ozon_data[$sku_ozon]['count_summa'] !=0) {
 $arr_real_ozon_data[$sku_ozon]['services_one_item']  =  round($arr_real_ozon_data[$sku_ozon]['services_summa']/$arr_real_ozon_data[$sku_ozon]['count_summa'],0);
 } else {
   $arr_real_ozon_data[$sku_ozon]['services_one_item']  = 0;  
 }
 if ($arr_real_ozon_data[$sku_ozon]['one_procent_from_accruals_for_sale'] !=0) {
    $arr_real_ozon_data[$sku_ozon]['one_procent_from_services'] = abs(round(( $arr_real_ozon_data[$sku_ozon]['services_summa']/$arr_real_ozon_data[$sku_ozon]['one_procent_from_accruals_for_sale'] ),1));
 } else {
    $arr_real_ozon_data[$sku_ozon]['one_procent_from_services'] = 0; 
 }
 $arr_summ['Сервисы'] = $arr_summ['Сервисы'] +  $arr_real_ozon_data[$sku_ozon]['services_summa'];

/**************************************************************************************/
/// ******************* Эквайринг  **************************
/**************************************************************************************/

$arr_real_ozon_data[$sku_ozon]['amount_ecvairing']  =  get_data_sell_tovar(@$print_item['amount_ecvairing']);
$arr_real_ozon_data[$sku_ozon]['ecvairing_one_item']  =  round($print_item['amount_ecvairing']/$arr_real_ozon_data[$sku_ozon]['count_summa'],0);
$arr_real_ozon_data[$sku_ozon]['one_procent_from_ecvairing'] = abs(round(($print_item['amount_ecvairing']/$arr_real_ozon_data[$sku_ozon]['one_procent_from_accruals_for_sale'] ),1));

$arr_summ['Эквайринг'] = $arr_summ['Эквайринг'] + $print_item['amount_ecvairing'];
/**************************************************************************************/
 // Цена за вычетом всего где есть артикул
/**************************************************************************************/
$arr_real_ozon_data[$sku_ozon]['summa_bez_vsego_gde_est_artikul']  =  get_data_sell_tovar(@$print_item['summa_bez_vsego_gde_est_artikul']);
$arr_real_ozon_data[$sku_ozon]['one_procent_from_summa_bez_vsego_gde_est_artikul']  =  round ($print_item['summa_bez_vsego_gde_est_artikul']/$print_item['count']['summa'],0);

$arr_summ['Цена за вычетом с арктикулом'] = $arr_summ['Цена за вычетом с арктикулом'] + $print_item['summa_bez_vsego_gde_est_artikul'];

/**************************************************************************************/
// **************** Процент распределения стоимости *****************
/**************************************************************************************/
$arr_real_ozon_data[$sku_ozon]['proc_item_ot_vsey_summi']  =  get_data_sell_tovar(@$print_item['proc_item_ot_vsey_summi']);

$arr_summ['Процент распределения стоимости'] = $arr_summ['Процент распределения стоимости'] + $print_item['proc_item_ot_vsey_summi'];

/**************************************************************************************/
// ****************Дополнительные услуги   
/**************************************************************************************/
$arr_real_ozon_data[$sku_ozon]['dop_uslugi']  =  get_data_sell_tovar(@$print_item['dop_uslugi']);
$arr_real_ozon_data[$sku_ozon]['dop_uslugi_one_item']  =  round($print_item['dop_uslugi']/$print_item['count']['summa'],0);
$arr_real_ozon_data[$sku_ozon]['one_procent_from_dop_uslugi'] = abs(round(($print_item['dop_uslugi']/$arr_real_ozon_data[$sku_ozon]['one_procent_from_accruals_for_sale'] ),1));

   $arr_summ['Сумма распределения доп.услуг'] = $arr_summ['Сумма распределения доп.услуг'] + $print_item['dop_uslugi'];


/**************************************************************************************/
// Цена за вычетом всех расходов 
/**************************************************************************************/
$arr_real_ozon_data[$sku_ozon]['summa_bez_vsego']  =  get_data_sell_tovar(@$print_item['summa_bez_vsego']);
$arr_real_ozon_data[$sku_ozon]['summa_bez_vsego_one_item']  =  round($print_item['summa_bez_vsego']/$print_item['count']['summa'],0);

$arr_summ['Сумма без всего'] = @$arr_summ['Сумма без всего'] + $print_item['summa_bez_vsego'];


/**************************************************************************************/
// ***************  Данные по себестоимости
/**************************************************************************************/
$arr_real_ozon_data[$sku_ozon]['mp_article']   =  @$arr_sebestoimost[$sku_ozon]['mp_article'];
$arr_real_ozon_data[$sku_ozon]['min_price']    =  get_data_sell_tovar(@$arr_sebestoimost[$sku_ozon]['min_price']);
$arr_real_ozon_data[$sku_ozon]['diff_min_price']   =  $arr_real_ozon_data[$sku_ozon]['summa_bez_vsego_one_item'] - $arr_real_ozon_data[$sku_ozon]['min_price'] ;

 
$arr_real_ozon_data[$sku_ozon]['main_price']   =  get_data_sell_tovar(@$arr_sebestoimost[$tovar['sku']]['main_price']);
$arr_real_ozon_data[$sku_ozon]['diff_main_price']   =  $arr_real_ozon_data[$sku_ozon]['summa_bez_vsego_one_item'] - $arr_real_ozon_data[$sku_ozon]['main_price'] ;

$arr_summ['Сумма себестоимость'] = @$arr_summ['Сумма себестоимость'] + $arr_real_ozon_data[$sku_ozon]['min_price']*@ $arr_real_ozon_data[$sku_ozon]['count_summa']; 
$arr_summ['Сумма прибыль'] = @$arr_summ['Сумма прибыль'] + $arr_real_ozon_data[$sku_ozon]['summa_bez_vsego'] -
                           $arr_real_ozon_data[$sku_ozon]['min_price']*@ $arr_real_ozon_data[$sku_ozon]['count_summa'];

}



print_r($arr_real_ozon_data);