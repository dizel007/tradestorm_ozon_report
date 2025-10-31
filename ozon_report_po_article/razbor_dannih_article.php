<?PHP

// $ozon_sebest = get_catalog_tovarov_v_mp($ozon_shop, $pdo, 'all');
// print_r($arr_type_items_WITH_POSTING_NUMBER);
// die();
/**************************************************************************************************************
 **************************************  ЗАКАЗЫ ************************************************************
 *************************************************************************************************************/
$i_orders = 0;
if (isset($arr_type_items_WITH_POSTING_NUMBER['orders'])) {require_once "parts_article/orders_article.php";}
echo "i_orders = $i_orders. <br>";
/**************************************************************************************************************
 **************************************  ВОЗВРАТЫ
 *************************************************************************************************************/
//06675399-0372-4
$i_returns = 0;
if (isset($arr_type_items_WITH_POSTING_NUMBER['returns'])) {
    require_once "parts_article/returns_article.php";
}
echo "i_returns = $i_returns. <br>";
/**************************************************************************************************************
 **************************************  Эквайринг 
 *************************************************************************************************************/
$i_other = 0;
if (isset($arr_type_items_WITH_POSTING_NUMBER['other'])) {
require_once "parts_article/other_article.php";
}
echo "i_i_other = $i_other. <br>";


 /***********************  Сервисы ******************************************************
 *************************************************************************************************************/
$i_services  = 0;
if (isset($arr_type_items_WITH_POSTING_NUMBER['services'])) {
require_once "parts_article/servici_article.php";
}
echo "i_services = $i_services. <br>";

/**************************************************************************************************************
 ************************************** Удержание за недовложение товара
 *************************************************************************************************************/
$i_compensation  = 0;
if (isset($arr_type_items_WITH_POSTING_NUMBER['compensation'])) {
require_once "parts_article/uderzhania_article.php";
}
echo "i_compensation = $i_compensation. <br>";
echo "количество элементов = ". count($arr_article). "<br>";







// ОСтавляем только те заказы, по которым есть движение 
// их пометили тегом WORK
//  ******* убираем все заказы, где нет ПРОДАЖИ И ВОЗВРАТОВ () 



// die();
foreach ($arr_article as $key=>$items_x) {
    if (isset($items_x['post_info'])) {

        foreach ($items_x['post_info'] as $key_x=>$item) {
                // print_R($item);
                // echo "<br>*****************";
            if (isset($item['WORK'])) 
                {$arr_article_WORK[$key] = $items_x;
                } 
            else {
                $arr_article_NOT_WORK[$key] = $items_x;
            } 

            }
    
} else {

    $arr_without_post_info[$key] = $items_x;
}
}
unset ($item); // 
unset ($items_x);

echo "количество элементов WORK = ". count($arr_article_WORK). "<br>";
echo "количество элементов NOT_WORK = ". count($arr_article_NOT_WORK). "<br>";
echo "количество элементов BEZ post INfo = ". count($arr_without_post_info). "<br>";

// print_r($arr_article_WORK);

// die();
// 0139816056-0030
// 05949135-0273-21 
/*******************************************************************************/

// print_r($arr_article_WORK);

// Оставляем только нужный SKU
foreach ($arr_article_WORK as $key => &$item) {
   if (isset($item['post_info'])) 
        {
            foreach ($item['post_info'] as $key_item_d => &$find_article) 
                {
                  if ((isset($find_article['sku'])) AND ($find_article['sku'] == $need_SKU) ) {
                       $arr_article_WORK_need_article[$key_item_d] = $find_article;

                       foreach ($arr_article_WORK[$key] as $key_x=>$data_x) {
                        if ($key_x == 'post_info') {continue;}
                        if ($key_x == 'type') {continue;}
                        if ($key_x == 'operation_id') {continue;}
                         $arr_article_WORK_need_article[$key_item_d][$key_x] = $data_x;
                       }
                    //    $arr_article_WORK_need_article[$key_item_d]['delivery_schema'] = $item['delivery_schema'];
                    //    $arr_article_WORK_need_article[$key_item_d]['order_date'] = $item['order_date'];
                    //    $arr_article_WORK_need_article[$key_item_d]['warehouse_id'] = $item['warehouse_id'];
                    //    $arr_article_WORK_need_article[$key_item_d]['operation_id'] = $item['operation_id'];
                    //    $arr_article_WORK_need_article[$key_item_d]['count'] = $item['count'];
                    //    $arr_article_WORK_need_article[$key_item_d]['amount_ecvairing'] = $item['amount_ecvairing'];

                 }
              }         
      } 
}

// print_r($arr_article_WORK_need_article);
if (!isset($arr_article_WORK_need_article)) { echo "<br>ДАННЫХ ДЛЯ ВЫВОДА"; die();}

// die();
echo "количество элементов WORK(нужного артикула) = ". count($arr_article_WORK_need_article). "<br>";

//  Нцжно удалить все элементы массива, которые оказались пустые 
foreach ($arr_article_WORK as $key => &$item) {
   if ((isset($item['post_info'])) AND (count($item['post_info']) == 0)) {
     unset($arr_article_WORK[$key]); 
    }
}
// 
echo "количество элементов WORK(нужного актикула) = ". count($arr_article_WORK). "<br>";

// die();



//*****************************************************************************************************
// перебираем все заказы, и перебираем все проданные товары по штукчно и
// и будем формировать все расходы по каждому товару в заказе 
//*****************************************************************************************************
// print_r($arr_article_WORK);
require_once "make_array_for_print.php";



///  Выводим таблицу с дополнительными сервисами, которые не смогли привязать к заказам
// require_once "print_article/table_services_without_postnumbers.php";

// Выводим таблицу со всеми заказами 
require_once "print_article/real_money_article.php";

die();
// 0153247992-0053