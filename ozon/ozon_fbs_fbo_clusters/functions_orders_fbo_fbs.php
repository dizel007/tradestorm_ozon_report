<?php

/*****************************************************************************************************
 *   Формируем массив для вывода все заказанных товаров
 *****************************************************************************************************/

function make_array_with_all_FBS_FBO_orders ($data_array) {
$arr_article=[];
   // формируем массив продаж по ФБО
   foreach ($data_array as $item) {
      $article = mb_strtolower($item['products'][0]['offer_id']);
      $arr_article[$article]['count'] = @$arr_article[$article]['count']  + $item['products'][0]['quantity'];
      $arr_article[$article]['price'] = @$arr_article[$article]['price']  + $item['products'][0]['price']['amount'];
      
   }
return $arr_article;
}
