<?php
// Данные о тарифах
$tariffs = [
    'day' => [
        'label' => 'День',
        'price' => 99,
        'duration' => '1 день',
        'features' => ['Базовый доступ', 'Ограниченная поддержка', 'Тестовый режим'],
        'recommended' => false,
    ],
    'month' => [
        'label' => 'Месяц',
        'price' => 1299,
        'duration' => '30 дней',
        'features' => ['Все базовые функции', 'Расширенные возможности', 'Приоритетная поддержка', 'Безлимитные запросы'],
        'recommended' => true,
    ],
    'year' => [
        'label' => 'Год',
        'price' => 12999,
        'duration' => '365 дней',
        'features' => ['Все функции', 'VIP-поддержка 24/7', 'Персональный менеджер', 'Экономия 20%'],
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
    <style>
        /* ---------- Общие сбросы и базовые стили (из pay_form_page.css) ---------- */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .form-wrapper {
            background: white;
            max-width: 80%;
            width: 50%;
            padding: 30px 35px 40px;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
            transition: 0.3s;
        }
        h2 {
            font-size: 28px;
            margin-bottom: 10px;
            color: #222;
            text-align: center;
            font-weight: 600;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }
        .back-link:hover {
            text-decoration: underline;
        }

        /* ---------- Стили для карточек тарифов ---------- */
        .tariff-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 25px 0 20px;
        }
        .tariff-card {
            background: #fff;
            border-radius: 20px;
            padding: 25px 20px 20px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border: 3px solid transparent;
            transition: all 0.25s ease;
            text-align: center;
            cursor: pointer;
            position: relative;
        }
        .tariff-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }
        .tariff-card.active {
            border-color: #007bff;
            background: #f0f7ff;
            box-shadow: 0 8px 24px rgba(0,123,255,0.15);
        }
        .tariff-card.recommended {
            border-color: #ffc107;
        }
        .tariff-card.recommended::after {
            content: "Рекомендуем";
            position: absolute;
            top: -10px;
            right: 10px;
            background: #ffc107;
            color: #222;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 14px;
            border-radius: 40px;
            letter-spacing: 0.3px;
        }
        .tariff-name {
            font-size: 22px;
            font-weight: 700;
            color: #222;
            margin-bottom: 6px;
        }
        .tariff-price {
            font-size: 32px;
            font-weight: 800;
            color: #007bff;
            margin: 10px 0 5px;
        }
        .tariff-price span {
            font-size: 18px;
            font-weight: 400;
            color: #888;
        }
        .tariff-duration {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }
        .tariff-features {
            list-style: none;
            padding: 0;
            margin: 15px 0 10px;
            text-align: left;
            font-size: 14px;
            color: #444;
        }
        .tariff-features li {
            padding: 6px 0 6px 28px;
            background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="%2328a745" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>') left center no-repeat;
            background-size: 18px;
        }

        /* ---------- Кнопка оплаты (уникальный класс, чтобы не конфликтовать) ---------- */
        .btn-submit-pay {
            width: 100%;
            padding: 14px;
            background: #007bff;
            color: #fff;
            border: none;
            border-radius: 40px;
            font-size: 18px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
            margin-top: 10px;
        }
        .btn-submit-pay:hover {
            background: #0056b3;
        }

        /* ---------- Адаптивность ---------- */
        @media (max-width: 600px) {
            .form-wrapper {
                width: 95%;
                padding: 25px 20px;
            }
            .tariff-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="form-wrapper">
        <h2>Выберите тариф</h2>
        <p style="text-align:center; color:#666; margin-bottom:5px;">Подберите оптимальный план для ваших задач</p>

        <!-- Форма отправляется методом GET на страницу оплаты -->
        <form method="GET" action="payment.php" id="tariff-form">
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