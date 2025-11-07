<?php


// echo  "ПРОШЛИ КОННЕКТ<br>";
require_once ("../main_info.php");
require_once '../pdo_functions/pdo_functions.php'; // подключаем функции  взаимодейцстя  с БД

      try {  
        $pdo = new PDO('mysql:host='.$host.';dbname='.$db.';charset=utf8', $user, $password);
        $pdo->exec('SET NAMES utf8');

        } catch (PDOException $e) {
          print "Has errors: " . $e->getMessage();  die();
        }

    $arr_sebes_temp = select_all_nomenklaturu($pdo);
    $arr_tovar_in_MP = get_catalog_tovarov_v_mp('ozon_ip_zel', $pdo, 'active') ;

// echo "<pre>";
//  print_r($arr_tovar_in_MP);
foreach ($arr_tovar_in_MP as $tovar) {
   
  foreach ($arr_sebes_temp  as $nomenklatura) {
  
    if (mb_strtolower($tovar['mp_article'] == mb_strtolower($nomenklatura['main_article_1c']))) {
      $arr_sebestoimost[$tovar['sku']]['mp_article'] = mb_strtolower($nomenklatura['main_article_1c']);
      $arr_sebestoimost[$tovar['sku']]['min_price'] = $nomenklatura['min_price'];
      $arr_sebestoimost[$tovar['sku']]['main_price'] = $nomenklatura['main_price'];
      break 1;
    }

  }
}

    
    