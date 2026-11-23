<?php
require_once("../../_no_git/secret_info.php");
require_once ("../../main_info.php");
require_once("../auth_check.php");
require_once '../../vendor/autoload.php';
require_once "function_report/razbor_type_item.php";
require_once("../ozon_authorization.php");




use App\Ozon\OzonClient;
use App\Ozon\Service\ProductService;
use App\Ozon\Service\FinanceService;


set_time_limit(300);
ini_set('memory_limit', '1024M');
// Вставляем форму для ввода даты
$url_data = urlencode($_GET['data']) ?? '';

//http://127.0.0.1:8080/tradestorm_ozon_report/ozon/ozon_fake_buyer/fake_buyers_report.php?data=j1XNzL/NQfEEA9Fu%20siVwTEOGsJ5DKiSAdf2uh9/VzxVoWhJzrx%20s6mSdd0RpV4N
// echo "<pre>";
// print_r($_SESSION);
// die('ggg');

[$token , $client_id, $seller_id, $shop_id] = ozon_authorization ($pdo , '../../registration');


die();

// SQL-запрос: группируем по покупателю, считаем уникальные заказы, 
// оставляем только тех, у кого count > 4, и сортируем по убыванию
$sql = "SELECT 
            id_fake_user, 
            COUNT(DISTINCT order_number) AS total_orders,
            SUM(CAST(quantity AS UNSIGNED)) AS total_items
        FROM fake_buyers 
        WHERE client_id = $client_id
        GROUP BY id_fake_user 
        HAVING total_orders > 1 
        ORDER BY total_orders DESC, total_items DESC";

$stmt = $pdo->query($sql);
$buyers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отчет: Покупатели с > 1 заказов</title>
    <link rel="stylesheet" href="css/table.css">
    <link rel="stylesheet" href="css/search_form.css">
    <link rel="stylesheet" href="css/buyers_report.css">


</head>
<body>

<h1 class="page-title">ПОКУПАТЕЛИ С БОЛЕЕ ЧЕМ 3 ВОЗРАТАМИ</h1>

<div style="max-width: 1000px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center;">
    <a href="fake_buyer_start.php" class="back-link">← Вернуться на главную</a>
    <a href="search_fake_buyer.php" class="back-link">Найти заказы по id покупателя →</a>
</div>

<?php if (count($buyers) > 0): ?>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>пп</th>
                    <th>ID Покупателя</th>
                    <th>Кол-во отправлений</th>
                    <th>Всего товаров (штук)</th>
                    <th>Действие</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; foreach ($buyers as $buyer): ?>
                    <tr>
                        <td class="num"><?= $i++ ?></td>
                        <td class="group-cell" style="text-align: center;">
                            <?= htmlspecialchars($buyer['id_fake_user'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td class="num" style="font-size: 14px; color: #d93025;">
                            <strong><?= (int)$buyer['total_orders'] ?></strong>
                        </td>
                        <td class="num" style="font-size: 14px;">
                            <?= (int)$buyer['total_items'] ?>
                        </td>
                        <td style="text-align: center;">
                            <!-- Ссылка на детальную страницу поиска, которую мы сделали ранее -->
                            <a href="search_fake_buyer.php?search_id=<?= urlencode($buyer['id_fake_user']) ?>" 
                               class="order-link" style="font-size: 12px;">
                                Посмотреть детали
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <div class="table-wrap" style="text-align: center; padding: 40px;">
        <h3 style="color: #666; margin: 0;">Пока нет покупателей с более чем 4 заказами.</h3>
    </div>
<?php endif; ?>

</body>
</html>