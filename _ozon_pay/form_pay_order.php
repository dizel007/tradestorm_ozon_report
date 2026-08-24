<?php
// echo  "ПРОШЛИ КОННЕКТ<br>";

require_once ("../main_info.php");
require_once "../_no_git/secret_info.php";
require_once("../vendor/autoload.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


//*********************************************************************************************************** */
// запрашиваем цены на продление
//*********************************************************************************************************** */

    $stmt = $pdo->prepare("SELECT * FROM `prices` ");
    $stmt->execute([]);
    $prices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ( $prices as $price) {
        $catalog_price[$price['price_name']]= $price['price_count'];
    }

//*********************************************************************************************************** */
// Получаем зашифрованные данные из GET и расшифровываем
//*********************************************************************************************************** */

$data = $_GET['data'] ?? '';

// Данные о тарифах
$tariffs = [
    'day' => [
        'label' => 'День',
        'price' => $catalog_price['day'],
        'duration' => '1 день',
        'features' => ['Базовый доступ', 'Ограниченная поддержка', 'Тестовый режим'],
        'recommended' => false,
    ],
    'month' => [
        'label' => 'Месяц',
        'price' => $catalog_price['month'],
        'duration' => '30 дней',
        'features' => ['Все базовые функции', 'Расширенные возможности', 'Приоритетная поддержка', ],
        'recommended' => true,
    ],
    'year' => [
        'label' => 'Год',
        'price' => $catalog_price['year'],
        'duration' => '365 дней',
        'features' => ['Все функции', 'Поддержка 24/7', 'Персональный менеджер',],
        'recommended' => false,
    ],
];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Выбор тарифа</title>
    <link rel="stylesheet" href="../css/form_pay_order.css">
</head>
<body>
    <div class="form-wrapper">
        <h2>Выберите тариф</h2>
        <p style="text-align:center; color:#666; margin-bottom:5px;">Подберите оптимальный план для ваших задач</p>

        <!-- Форма отправляется методом GET на страницу оплаты -->
        <form method="GET" action="pay_ozon_order.php" id="tariff-form">
            <!-- Передаём data как скрытое поле — браузер закодирует его автоматически -->
            <input type="hidden" name="data" value="<?= $data ?>">
            <input type="hidden" name="period" id="period-input" value="day">

            <div class="tariff-grid" id="tariff-grid">
                <?php foreach ($tariffs as $key => $tariff): ?>
                    <div class="tariff-card <?= ($key === 'day') ? 'active' : '' ?> <?= $tariff['recommended'] ? 'recommended' : '' ?>" data-period="<?= $key ?>">
                        <div class="tariff-name"><?= htmlspecialchars($tariff['label']) ?></div>
                        <div class="tariff-price"><?= $tariff['price'] ?> <span>₽</span></div>
                        <div class="tariff-duration">Срок: <?= htmlspecialchars($tariff['duration']) ?></div>
                        <ul class="tariff-features">
                            <?php foreach ($tariff['features'] as $feature): ?>
                                <li><?= htmlspecialchars($feature) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Кнопка с уникальным классом -->
            <button type="submit" class="btn-submit-pay">Оплатить</button>
        </form>

        <div style="text-align: center; margin-top: 20px;">
            <a href="../dashboard/dashboard.php" class="back-link">← На главную</a>
        </div>
    </div>

    <script>
        (function() {
            const cards = document.querySelectorAll('.tariff-card');
            const periodInput = document.getElementById('period-input');

            function setActive(period) {
                cards.forEach(card => {
                    card.classList.remove('active');
                    if (card.dataset.period === period) {
                        card.classList.add('active');
                    }
                });
                periodInput.value = period;
            }

            cards.forEach(card => {
                card.addEventListener('click', function() {
                    setActive(this.dataset.period);
                });
            });

            // Начальное состояние
            setActive('day');
        })();
    </script>
</body>
</html>