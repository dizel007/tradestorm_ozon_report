<?php
/**********************************************************************************************************
 *     ***************    Получаем массив всех транзакций
*********************************************************************************************************/
require_once("../../_no_git/secret_info.php");
require_once ("../../main_info.php");
require_once("../../_no_git/secret_info.php");
require_once("../auth_check.php");
require_once '../../vendor/autoload.php';

// require_once "functions_orders_fbo_fbs.php";


/**********************************************************************************************************
 *     ***************    Получаем массив всех транзакций
 *********************************************************************************************************/
set_time_limit(300);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $pdo = new PDO('mysql:host=' . $host . ';dbname=' . $db . ';charset=utf8', $user, $password);
    $pdo->exec('SET NAMES utf8');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// echo "<pre>";
// print_r($_GET);
parse_str(decryptData($_GET['data']), $params);
// print_r($params);
// die();


if (isset($_GET['data'])) {
    $url_data = ($_GET['data']);

    $article = mb_strtolower($params['article']);
    $shop_id = $params['shop_id'];
    $shop_name = $params['shop_name'];




    $stmt = $pdo->prepare("SELECT * FROM `shops` WHERE `id` = :id");
    $stmt->execute(['id' => $params['shop_id']]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($shop) {
        $autoLogin = true;
        $token = $shop['ozon_token'];
        $client_id = $shop['client_id'];
        $seller_id = $shop['seller_id'];
    } else {
        session_destroy();
    }
} else {
        header('Location: ../../registration/index.php');
        exit();
}




//// Проверяем и есть нет папки, то создаем ее, для каждого пользователя своя папка в !cache
 
$dir_for_cache = "../../_cache/seller_".$seller_id."/ozon_fbs_fbo_clusters/";
$filename_fbs_orders = $dir_for_cache."json_fbs_Orders_".$client_id.".json";
$filename_fbo_orders = $dir_for_cache."json_fbo_Orders_".$client_id.".json";








$delivery_schema = $_GET['delivery_schema'] ?? '';
$schemaLower = strtolower($delivery_schema);


// print_r($delivery_schema);

if ($schemaLower === 'fbs') {
    // FBS
    $ordersFBS = json_decode(file_get_contents($filename_fbs_orders),true);
    // выбиираем товары только с нашим артикулом из ФБС
foreach ($ordersFBS as $itemFbs) {
   foreach ($itemFbs['products'] as $product) {
         $offer_id = mb_strtolower($product['offer_id']);
         if ($article == $offer_id) {
            $work_article_array[] = $itemFbs;
            break;
         }
   }
}
unset($itemFbs);
unset($product);


} elseif ($schemaLower === 'fbo') {
       // FBO
    $ordersFB0 = json_decode(file_get_contents($filename_fbo_orders),true);
// выбиираем товары только с нашим артикулом из ФБO
foreach ($ordersFB0 as $itemFbo) {
   foreach ($itemFbo['products'] as $product) {
         $offer_id = mb_strtolower($product['offer_id']);
         if ($article == $offer_id) {
            $work_article_array[] = $itemFbo;
            break;
         }
   }
}
unset($itemFbo);
unset($product);



} else {
    // Нет схемы
$ordersFBS = json_decode(file_get_contents($filename_fbs_orders),true);
$ordersFB0 = json_decode(file_get_contents($filename_fbo_orders),true);
// выбиираем товары только с нашим артикулом из ФБС
foreach ($ordersFBS as $itemFbs) {
   foreach ($itemFbs['products'] as $product) {
         $offer_id = mb_strtolower($product['offer_id']);
         if ($article == $offer_id) {
            $work_article_array[] = $itemFbs;
            break;
         }
   }
}
unset($itemFbs);
unset($product);

// print_r($ordersFBS);

// выбиираем товары только с нашим артикулом из ФБO
foreach ($ordersFB0 as $itemFbo) {
   foreach ($itemFbo['products'] as $product) {
         $offer_id = mb_strtolower($product['offer_id']);
         if ($article == $offer_id) {
            $work_article_array[] = $itemFbo;
            break;
         }
   }
}
unset($itemFbo);
unset($product);

}







////////////////////////////////////////
// ищем все статусы отправления
////////////////////////////////////////////////////////////
// foreach ($work_article_array as $t_item) {
//   $statuses[$t_item['substatus']]  = $t_item['substatus'];
// }


/******************************************************************************************************************
 * формируем массив для вывода на экран
 *******************************************************************************************************************/
// print_r($work_article_array);
$i = 0;
if (isset($work_article_array)) {
foreach ($work_article_array as $items) {

$array_for_print[$i]['posting_number'] = $items['posting_number'];
$array_for_print[$i]['in_process_at'] = $items['in_process_at'];

$array_for_print[$i]['order_number'] = $items['order_number'];
$array_for_print[$i]['status'] = $items['status'];
$array_for_print[$i]['substatus'] = $items['substatus'];

if (isset($items['delivery_schema'])) {
   $array_for_print[$i]['delivery_schema'] = $items['delivery_schema'];
   $array_for_print[$i]['customer_price'] = $items['financial_data']['products'][0]['customer_price']['amount'];
   
} else {
   $array_for_print[$i]['delivery_schema'] = 'fbo';
   $array_for_print[$i]['customer_price'] = $items['financial_data']['products'][0]['payout'];
}
$array_for_print[$i]['offer_id'] = $items['products'][0]['offer_id'];
$array_for_print[$i]['name'] = $items['products'][0]['name'];
$array_for_print[$i]['sku'] = $items['products'][0]['sku'];
$array_for_print[$i]['quantity'] = $items['products'][0]['quantity'];
$array_for_print[$i]['price'] = $items['products'][0]['price']['amount'];

$array_for_print[$i]['cluster_from'] = $items['financial_data']['cluster_from'];
$array_for_print[$i]['cluster_to'] = $items['financial_data']['cluster_to'];




$i++;
}
}
if (isset($array_for_print)) {
// сортировка массива по дате заказа
   usort($array_for_print, function($a, $b) {
    return strcmp($a['in_process_at'], $b['in_process_at']);
});

// ============================================================
// 1. Собираем все уникальные статусы (не зависят от фильтров по кластерам)
// ============================================================
$all_statuses = [];
foreach ($array_for_print as $item) {
    $status = $item['substatus'] ?: $item['status'];
    if ($status) {
        $all_statuses[$status] = $status;
    }
}
ksort($all_statuses);

// ============================================================
// 2. Читаем все фильтры из GET
// ============================================================
$status_filter = $_GET['status_filter'] ?? '';

// Накопительный фильтр по "Откуда"
$cluster_from_filter = $_GET['cluster_from'] ?? [];
if (!is_array($cluster_from_filter)) {
    $cluster_from_filter = [$cluster_from_filter];
}
$cluster_from_filter = array_values(array_filter($cluster_from_filter, 'strlen'));

// Накопительный фильтр по "Куда"
$cluster_to_filter = $_GET['cluster_to'] ?? [];
if (!is_array($cluster_to_filter)) {
    $cluster_to_filter = [$cluster_to_filter];
}
$cluster_to_filter = array_values(array_filter($cluster_to_filter, 'strlen'));

// ============================================================
// 3. Собираем доступные варианты для каждого фильтра
//    с учётом ВСЕХ ДРУГИХ активных фильтров (каскадное сужение)
// ============================================================

// 3a. Доступные "Откуда" — из строк, прошедших ВСЕ фильтры, КРОМЕ "Откуда"
$all_clusters_from = [];
foreach ($array_for_print as $item) {
    // проверяем статус
    if (!empty($status_filter)) {
        $status = $item['substatus'] ?: $item['status'];
        if ($status !== $status_filter) continue;
    }
    // проверяем "Куда"
    if (!empty($cluster_to_filter)) {
        if (!in_array($item['cluster_to'] ?? '', $cluster_to_filter, true)) continue;
    }
    if (!empty($item['cluster_from'])) {
        $all_clusters_from[$item['cluster_from']] = $item['cluster_from'];
    }
}
ksort($all_clusters_from);

// 3b. Доступные "Куда" — из строк, прошедших ВСЕ фильтры, КРОМЕ "Куда"
$all_clusters_to = [];
foreach ($array_for_print as $item) {
    // проверяем статус
    if (!empty($status_filter)) {
        $status = $item['substatus'] ?: $item['status'];
        if ($status !== $status_filter) continue;
    }
    // проверяем "Откуда"
    if (!empty($cluster_from_filter)) {
        if (!in_array($item['cluster_from'] ?? '', $cluster_from_filter, true)) continue;
    }
    if (!empty($item['cluster_to'])) {
        $all_clusters_to[$item['cluster_to']] = $item['cluster_to'];
    }
}
ksort($all_clusters_to);

// ============================================================
// 4. Применяем фильтры к $array_for_print (для вывода таблицы)
// ============================================================
if (!empty($status_filter)) {
    $array_for_print = array_filter($array_for_print, function($item) use ($status_filter) {
        $status = $item['substatus'] ?: $item['status'];
        return $status === $status_filter;
    });
}

if (!empty($cluster_from_filter)) {
    $array_for_print = array_filter($array_for_print, function($item) use ($cluster_from_filter) {
        return in_array($item['cluster_from'] ?? '', $cluster_from_filter, true);
    });
}

if (!empty($cluster_to_filter)) {
    $array_for_print = array_filter($array_for_print, function($item) use ($cluster_to_filter) {
        return in_array($item['cluster_to'] ?? '', $cluster_to_filter, true);
    });
}

}
// echo "<pre>";
// print_r($array_for_print);

// die();



?>

<!-- <link rel="stylesheet" href="css/sell_fbo_fbs_table_article.css"> -->
<link rel="stylesheet" href="css/sell_fbo_fbs_table.css">


<!-- Блок фильтрации по схеме доставки -->
<!-- Блок фильтрации по схеме доставки -->
<div class="filter-buttons">
    <?php
    $current_schema = $_GET['delivery_schema'] ?? '';

    // Формируем data ОДИН раз — он одинаков для всех трёх ссылок
    $base_data = encryptData(http_build_query([
        'shop_id'   => $shop_id,
        'shop_name' => $shop_name,
        'article'   => $article,
    ]));

    // Хвост с фильтрами (открытые GET-параметры)
    $filters_qs = '';
    if (!empty($status_filter)) {
        $filters_qs .= "&status_filter=" . urlencode($status_filter);
    }
    foreach ($cluster_from_filter as $cf) {
        $filters_qs .= "&cluster_from[]=" . urlencode($cf);
    }
    foreach ($cluster_to_filter as $ct) {
        $filters_qs .= "&cluster_to[]=" . urlencode($ct);
    }

    $base_url = "?data=" . urlencode($base_data) . $filters_qs;
    ?>
    <a href="<?= $base_url ?>&delivery_schema=fbs"
       class="btn-filter <?= ($current_schema === 'fbs') ? 'active' : '' ?>">FBS</a>
    <a href="<?= $base_url ?>&delivery_schema=fbo"
       class="btn-filter <?= ($current_schema === 'fbo') ? 'active' : '' ?>">FBO</a>
    <a href="<?= $base_url ?>"
       class="btn-filter <?= ($current_schema === '') ? 'active' : '' ?>">ВСЕ</a>
</div>

<?php if (!isset($array_for_print)) {

echo "<div class=\"filter-buttons\">";
    
    echo "<p>нет данных для вывода</p>";
echo "</div>";

   die('');
}
 ?>


<!-- Блок фильтрации по статусу, Откуда и Куда -->
<div class="filter-status">
    <form method="get" action="" style="display:flex; align-items:center; gap:10px; flex-wrap:wrap; background:transparent; border:none; padding:0; margin:0;">
        <!-- скрытые параметры, чтобы сохранить текущие фильтры -->
        <input type="hidden" name="data" value="<?= htmlspecialchars($url_data) ?>">
        <input type="hidden" name="delivery_schema" value="<?= htmlspecialchars($delivery_schema) ?>">
        
        <label for="status_filter"><strong>Статус:</strong></label>
        <select name="status_filter" id="status_filter" onchange="this.form.submit()" style="padding:8px 14px; border-radius:8px; border:1px solid #d0d5dd; font-size:14px; background:#fafbfc;">
            <option value="">Все статусы</option>
            <?php foreach ($all_statuses as $status_value): ?>
                <option value="<?= htmlspecialchars($status_value) ?>" <?= ($status_filter === $status_value) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($status_value) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <!-- Фильтр Откуда (накопительный, только доступные варианты) -->
        <label for="cluster_from"><strong>Откуда:</strong></label>
        <select name="cluster_from[]" id="cluster_from" multiple size="1"
                onchange="this.form.submit()"
                title="Ctrl/Cmd + клик — выбрать несколько"
                style="min-width:180px; padding:8px 14px; border-radius:8px; border:1px solid #d0d5dd; font-size:14px; background:#fafbfc;">
            <?php if (empty($all_clusters_from)): ?>
                <option value="" disabled>нет доступных</option>
            <?php else: ?>
                <?php foreach ($all_clusters_from as $cf): ?>
                    <option value="<?= htmlspecialchars($cf) ?>"
                        <?= in_array($cf, $cluster_from_filter, true) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cf) ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>

        <!-- Фильтр Куда (накопительный, только доступные варианты) -->
        <label for="cluster_to"><strong>Куда:</strong></label>
        <select name="cluster_to[]" id="cluster_to" multiple size="1"
                onchange="this.form.submit()"
                title="Ctrl/Cmd + клик — выбрать несколько"
                style="min-width:180px; padding:8px 14px; border-radius:8px; border:1px solid #d0d5dd; font-size:14px; background:#fafbfc;">
            <?php if (empty($all_clusters_to)): ?>
                <option value="" disabled>нет доступных</option>
            <?php else: ?>
                <?php foreach ($all_clusters_to as $ct): ?>
                    <option value="<?= htmlspecialchars($ct) ?>"
                        <?= in_array($ct, $cluster_to_filter, true) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ct) ?>
                    </option>
                <?php endforeach; ?>
            <?php endif; ?>
        </select>

        <!-- Сброс всех фильтров, кроме схемы/магазина/артикула -->
        <a href="?data=<?= urlencode($url_data) ?>"
           class="btn-filter" style="padding:8px 14px; border-radius:8px; border:1px solid #d0d5dd; text-decoration:none; color:#333; background:#fff;">
            Сбросить
        </a>
    </form>
</div>




<table class="sell_mp_table w90">
  <thead>
    <tr>
       <th>пп</th>
      <th>Артикул</th>
      <th>№ заказа</th>
      <th>Ссылка на ozon</th>
      <th>Дата</th>
      <th>Статус</th>
      <th>Схема</th>
      <th>Кол-во</th>
      <th>Цена</th>
      <th>Цена покупателя</th>
      <th>Откуда</th>
      <th>Куда</th>
    </tr>
  </thead>
  <tbody>
    <?php 
     $i =0;

foreach ($array_for_print as $item): 
         $i++;
// делаем ссылку на заказ в зависимости от типа отправки
if ($item['delivery_schema'] == 'fbs') {
   $link_for_ozon_seller = 'https://seller.ozon.ru/app/postings/fbs?postingDetails='.$item['posting_number'];
} else {
   $link_for_ozon_seller = 'https://seller.ozon.ru/app/postings/fbo/'.$item['posting_number'];
}
        // Определяем класс для строки
        $statusClass = '';
        $status = $item['status'] ?? '';
        $substatus = $item['substatus'] ?? '';
        if ($status === 'cancelled' || $substatus === 'posting_canceled') {
            $statusClass = 'status-cancelled';
        } elseif ($status === 'delivered' || $substatus === 'posting_received') {
            $statusClass = 'status-delivered';
        } elseif ($status === 'delivering' || strpos($substatus, 'on_way') !== false) {
            $statusClass = 'status-delivering';
        }
        // Дополнительно можно добавить другие статусы

 $data_for_posting_link = urlencode($url_data)."&delivery_schema=".$item['delivery_schema']."&posting_number=".$item['posting_number'];
 $linkForPostingNumber = "get_data_posting_number.php?data=".$data_for_posting_link;





    ?>
    <tr class="<?= $statusClass ?>">
      <td><?= htmlspecialchars($i) ?></td>
      <td><?= htmlspecialchars($item['offer_id'] ?? '') ?></td>
      <td> <a href="<?=  $linkForPostingNumber ?>" target="_blank"><?= htmlspecialchars($item['posting_number'] ?? '') ?></a></td>
      <td> <a href = "<?= $link_for_ozon_seller; ?>" target="_blank"  >OZON</a></td>
      <td><?= date('d.m.Y H:i', strtotime($item['in_process_at'] ?? '')) ?></td>
      <td><?= htmlspecialchars($substatus ?: $status) ?></td>
      <td><?= htmlspecialchars($item['delivery_schema'] ?? '') ?></td>
     
      
      <td><?= (int)($item['quantity'] ?? 0) ?></td>
      <td><?= number_format($item['price'] ?? 0, 0, '.', ' ') ?></td>
        <td><?= number_format($item['customer_price'] ?? 0, 0, '.', ' ') ?></td>

      <td><?= htmlspecialchars(mb_strimwidth($item['cluster_from'], 0, 14, '..')  ?? '') ?></td>
      <td><?= htmlspecialchars(mb_strimwidth($item['cluster_to'], 0, 15, '..')  ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>