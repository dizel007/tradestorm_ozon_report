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

print_r($url_data );


// Получаем ID покупателя из формы (если форма отправлена)
$search_id = isset($_GET['search_id']) ? trim($_GET['search_id']) : '';
$rows = [];
$total_sum = 0;

// Если ID введен — ищем в базе
if ($search_id !== '') {
    // Ищем все заказы этого покупателя, сортируем по номеру заказа
    $stmt = $pdo->prepare("SELECT * FROM fake_buyers WHERE id_fake_user = :id ORDER BY order_number ASC");
    $stmt->execute([':id' => $search_id]);
    $db_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);


    // Группируем данные так же, как в вашем рабочем скрипте
    $array_by_fake_users = [];
    foreach ($db_rows as $row) {
        $id_fake_user = $row['id_fake_user'];
        $number_order =  explode('-', $row['order_number']);;
        $order_number = $row['order_number'];
        $sku          = $row['sku'];
        $client_id    = isset($row['client_id']) ? $row['client_id'] : ''; // Берем client_id из базы
        $quantity     = (int)$row['quantity'];
        $delivery_shema = $row['delivery_shema'];
        if (mb_strtolower($delivery_shema) == 'fbo') {
            $link_for_ozon = 'https://seller.ozon.ru/app/postings/fbo/details?posting_number='.$order_number;
        } else {
            $link_for_ozon = 'https://seller.ozon.ru/app/postings/fbs?tab=delivering&postingDetails='.$order_number;
        }
                          
        // Сохраняем client_id вместе с количеством
        $array_by_fake_users[$number_order[0].'-'.$number_order[1]][$order_number][$sku] = [
            'quantity'  => $quantity,
            'client_id' => $client_id,
            'link_for_ozon' => $link_for_ozon
        ];
    }

//     echo "<pre>";
// print_r($db_rows);
// die();


    // Формируем плоский массив для вывода
    foreach ($array_by_fake_users as $group => $codes) {
        $groupRows = [];
        $groupSum = 0;

        foreach ($codes as $code => $items) {
            foreach ($items as $sku => $data) {
                $groupRows[] = [
                    'group'          => $group,
                    'order_number'   => $code,
                    'client_id'      => $data['client_id'], // Передаем client_id в строку
                    'sku'            => $sku,
                    'value'          => $data['quantity'],
                    'link_for_ozon'  => $data['link_for_ozon'],

                ];
                $groupSum += $data['quantity'];
            }
        }

        $count = count($groupRows);
        foreach ($groupRows as $idx => $row) {
            $row['rowspan'] = ($idx === 0) ? $count : 0;
            $row['sum']     = $groupSum;
            $rows[] = $row;
        }
        $total_sum += $groupSum;
    }
}



?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Поиск фейковых покупателей</title>
    <!-- Подключаем ваши общие стили -->
    <link rel="stylesheet" href="css/table.css">
    <link rel="stylesheet" href="css/search_form.css">
</head>
<body>

<!-- Верхняя панель с кнопкой отчета -->
<div class="top-panel">
    <h1 class="page-title" style="margin: 0; text-align: left;">ПОИСК ПОКУПАТЕЛЯ</h1>
    
    <a href="fake_buyers_report.php?data=<?= urlencode($url_data) ?>" class="btn-report">
        Полный список ID покупателей с возвратами
    </a>
</div>

<!-- Форма поиска в стиле дашборда -->
<div class="search-panel">
    <form method="GET" action="" class="search-form">
        <label for="search_id">Введите ID покупателя:</label>
        <input type="text" id="search_id" name="search_id" 
               value="<?= htmlspecialchars($search_id, ENT_QUOTES, 'UTF-8') ?>" 
               placeholder="Например: 59743540" required>
        <input type="submit" value="НАЙТИ">
    </form>
</div>

<?php if ($search_id !== ''): ?>
    <h2 class="page-title" style="margin-top: 20px;">РЕЗУЛЬТАТЫ ПОИСКА</h2>
    
    <?php if (count($rows) > 0): ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>пп</th>
                        <th>ID покупателя</th>
                        <th>Номер заказа</th>
                        <th>ID Клиента</th>
                        <th>СКУ</th>
                        <th>Колво у покупателя</th>
                        <th>ИТОГО</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($rows as $row): ?>
                        <tr data-group="<?= htmlspecialchars($row['group'], ENT_QUOTES, 'UTF-8') ?>">
                            <td class="num"><?= $i++ ?></td>

                            <?php if ($row['rowspan'] > 0): ?>
                                <td class="group-cell" rowspan="<?= $row['rowspan'] ?>">
                                    <?= htmlspecialchars($row['group'], ENT_QUOTES, 'UTF-8') ?>
                                </td>
                            <?php endif; ?>

                            <td>
                                <!-- Ссылки на Ozon Seller для FBS/FBO -->
                                <a href="<?= htmlspecialchars($row['link_for_ozon']) ?>" 
                                   target="_blank" class="order-link">
                                    <?= htmlspecialchars($row['order_number'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </td>
                            
                            <!-- Столбец с ID Клиента -->
                            <td class="num"><?= htmlspecialchars($row['client_id'], ENT_QUOTES, 'UTF-8') ?></td>

                            <td class="num">
                                <a href="https://www.ozon.ru/product/<?= htmlspecialchars($row['sku']) ?>" 
                                   target="_blank" class="sku-link">
                                    <?= htmlspecialchars($row['sku'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </td>
                            <td class="num"><?= (int)$row['value'] ?></td>

                            <?php if ($row['rowspan'] > 0): ?>
                                <td class="num total-cell" rowspan="<?= $row['rowspan'] ?>">
                                    <?= (int)$row['sum'] ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="table-wrap" style="text-align: center; padding: 40px;">
            <h3 style="color: #666; margin: 0;">По запросу "<?= htmlspecialchars($search_id) ?>" ничего не найдено.</h3>
        </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Скрипт для подсветки строк -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const rows = document.querySelectorAll('.data-table tbody tr');
    rows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            const groupId = this.getAttribute('data-group');
            if (!groupId) return;
            document.querySelectorAll(`.data-table tbody tr[data-group="${groupId}"]`).forEach(r => {
                r.classList.add('highlight-group');
            });
        });
        row.addEventListener('mouseleave', function() {
            const groupId = this.getAttribute('data-group');
            if (!groupId) return;
            document.querySelectorAll(`.data-table tbody tr[data-group="${groupId}"]`).forEach(r => {
                r.classList.remove('highlight-group');
            });
        });
    });
});
</script>

</body>
</html>