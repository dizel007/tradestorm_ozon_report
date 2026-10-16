<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TradeStorm - Список магазинов</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="form-wrapper">
        <h2>Аналитическая система TradeStorm</h2>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (isset($_GET['restored']) && $_GET['restored'] == 1): ?>
            <div class="success-message">Магазин успешно восстановлен!</div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted']) && $_GET['deleted'] == 1): ?>
            <div class="success-message">Магазин успешно удален!</div>
        <?php endif; ?>

        <?php if ($addNewShop): ?>
            <div class="toggle-buttons">
                <!-- <button class="toggle-btn active">Добавить новый магазин</button> -->
            </div>
            <div id="addShopForm" class="form-container active">
                <form action="#" method="POST">
                    <input type="hidden" name="action" value="add_shop">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <div class="form-group">
                        <label for="add_client_id">Ozon Client ID:</label>
                        <input type="text" id="add_client_id" name="client_id" required placeholder="123456">
                    </div>
                    <div class="form-group">
                        <label for="add_token">Ozon Token (API-ключ):</label>
                        <input type="text" id="add_token" name="token" required placeholder="токен с правами Admin read-only">
                    </div>
                    <button type="submit">Добавить магазин</button>
                </form>
            </div>
            <a href="index.php" class="action-btn" style="display:inline-block; margin-bottom:20px;">← Вернуться к списку</a>
        <?php else: ?>
            <h3>Список магазинов пользователя: <?= htmlspecialchars($client_email) ?>:</h3>

            <div class="table-responsive">
                <table class="client-table">
                    <thead>
                        <tr>
                            <th>пп</th>
                            <th>Название магазина</th>
                            <th>Фин.отчет</th>
                            <th>Окончание подписки</th>
                            <th>Состояние подписки</th>
                            <th>Действие</th>
                            <th>Продление</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $counter = 1;
                        foreach ($clients as $client):
                            $isDeleted = ($client['deleted'] == 1);
                            $isActive  = $client['is_active'];
                            $rowClass  = $isDeleted ? 'deleted-row' : '';
                        ?>
                            <tr class="<?= $rowClass ?>">
                                <td data-label="пп"><?= $counter++ ?></td>

                                <td data-label="Название магазина">
                                    <?php
                                    if ($isDeleted) {
                                        $nameClass = 'shop-name-deleted';
                                    } elseif ($isActive) {
                                        $nameClass = 'shop-name-active';
                                    } else {
                                        $nameClass = 'shop-name-inactive';
                                    }
                                    ?>
                                    <span class="<?= $nameClass ?>">
                                        <?= htmlspecialchars($client['shop_name']) ?>
                                    </span>
                                </td>

                                <td class="icon_width" data-label="Фин.отчет">
                                    <?php if ($isActive && $client['shop_name'] !== 'Нет данных'): ?>
                                        <a href="../ozon/ozon_report?data=<?= urlencode($client['data_clt']) ?>" class="shop-name-link" title="Финансовый отчёт">
                                            📊
                                        </a>
                                    <?php endif; ?>
                                </td>

                                <td class="small_width" data-label="Окончание подписки">
                                    <?php
                                    $days     = $client['days_left'];
                                    $end_date = $client['end_period'] ?? '—';
                                    if ($days > 7) {
                                        $badge_class = 'badge-success';
                                    } elseif ($days > 0) {
                                        $badge_class = 'badge-warning';
                                    } else {
                                        $badge_class = 'badge-danger';
                                    }
                                    ?>
                                    <span class="badge <?= $badge_class ?>"><?= htmlspecialchars($end_date) ?></span>
                                </td>

                                <td class="small_width status-cell" data-label="Состояние подписки">
                                    <?php if ($isDeleted): ?>
                                        <span class="deleted-badge">Удалён</span>
                                    <?php elseif ($isActive): ?>
                                        <span class="active-badge">Активен</span>
                                    <?php else: ?>
                                        <span class="inactive-badge">Неактивен</span>
                                    <?php endif; ?>
                                </td>

                                <td class="small_width" data-label="Действие">
                                    <?php if ($isDeleted): ?>
                                        <form action="#" method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="restore">
                                            <input type="hidden" name="shop_id" value="<?= $client['data_clt'] ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                                            <button type="submit" class="table-action-btn btn-restore">
                                                🔄 Восстановить
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <a href="edit_shop.php?data=<?= urlencode($client['data_clt']) ?>" class="table-action-btn btn-edit">
                                            ✏️ Редактировать
                                        </a>
                                    <?php endif; ?>
                                </td>

                                <td class="small_width" data-label="Продление">
                                    <?php if (!$isDeleted && (!$isActive || $client['days_left'] < 7)): ?>
                                        <a href="../_ozon_pay/form_pay_order.php?data=<?= urlencode($client['data_clt']) ?>" class="table-action-btn btn-pay">
                                            💳 Продлить
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($clients)): ?>
                            <tr><td colspan="7">Нет магазинов</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="table-actions">
                <a href="index.php?add_shop=1" class="action-btn add-btn">➕ Добавить магазин</a>
                <?php if ($showDeleted): ?>
                    <a href="" class="action-btn" style="background: #6c757d;">🙈 Скрыть удалённые</a>
                <?php else: ?>
                    <a href="?show_deleted=1" class="action-btn" style="background: #ffc107; color: #212529;">👁️ Показать удалённые</a>
                <?php endif; ?>
                <a href="?logout=1" class="action-btn logout-btn">🚪 Выйти</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>