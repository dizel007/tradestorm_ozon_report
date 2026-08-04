<?php
require_once "config.php";
$mail_path = ""; 
require_once "mailer/send_mail.php";
require_once ("_no_git/secret_info.php");  
session_start();

/// проверяем номер заказа из сессии и из ссылки озона (GET параметр)
/// если все хорошо, то продолжаем работать иначе помираем;
$session_order_number = $_SESSION['order_number'];
$order_number = $_GET['order_number'];
if ($session_order_number != $order_number)  {
    die('Ошибка в сессии');
}



/************************************************************************************************/
    try {  
       $pdo = new PDO('mysql:host='.$host.';dbname='.$db.';charset=utf8', $user, $password);
       $pdo->exec('SET NAMES utf8');
       $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
          print "Has errors: " . $e->getMessage();  die();
        }


try {
$fileName_json_order = 'data/orders/'.$order_number.".json";
$orderData_t = json_decode(file_get_contents($fileName_json_order), true);
$orderData = $orderData_t[0];
} catch (Exception $e) {
    echo "Ошибка: " . $e->getMessage();
}


//**********************************************************************************************
// Удаляем строки в резерве остатков
//**********************************************************************************************/
$reservStockId_data_for_delete = $orderData['reservStockId_data_for_delete'];
foreach ($reservStockId_data_for_delete as $id_for_delete_reserv) {
    $stmt = $pdo->prepare("DELETE FROM reserv_stocks WHERE `id`  = :id_for_delete_reserv");
    $stmt->execute(['id_for_delete_reserv' => $id_for_delete_reserv]);
}
//**********************************************************************************************
// Обновляем остатки в таблице STOCK
//**********************************************************************************************/

if (!isset($orderData_t[0]['Stock_update'])) {
    $stmt = $pdo->prepare("SELECT * FROM stocks");
    $stmt->execute([]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($orderData['cart_items'] as $tovar) {
     $stocks_for_delete[$tovar['article']] = $tovar['quantity'];
   }

foreach ($products as $product) {
    if (isset($stocks_for_delete[$product['article']])) {
    $stocks_for_del_new[$product['article']] = $product['inStock'] - @$stocks_for_delete[$product['article']];
     if ($stocks_for_del_new[$product['article']] < 0) {
        $stocks_for_del_new[$product['article']] = 0;
     }
    }
}
// 
foreach ($stocks_for_del_new as $article => $quantity) {
$stmt = $pdo->prepare("UPDATE stocks SET inStock = :quantity WHERE article = :article");
$stmt->execute([
    'quantity' => $quantity,
    'article' => $article
]);
usleep(1000);
}

/// ставим признак в файл заказа, что остатки уже обновили
$orderData_t[0]['Stock_update'] = true;
file_put_contents($fileName_json_order, json_encode($orderData_t, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
//

// вносим признак оплаты закза
$stmt = $pdo->prepare("UPDATE orders SET paid = 1 WHERE order_number = :extId");
$stmt->execute(['extId' => $orderData_t[0]['order_number']]);



// echo "<pre>";
// print_r($orderData_t);

// print_r($_SESSION);
/********************************************************************************************************
 *  Отправляем письмо о заказе
 *******************************************************************************************************/
//
foreach ($orderData['cart_items'] as &$items_p) {
$items_p['total'] = $items_p['price'] * $items_p['quantity'];
}
// формируем content для письма
require_once "mailer/shablon_order_email.php";

   

send_many_emails($EMAIL_for_letters, 'ПРИШЕЛ ЗАКАЗ',  $content, $mail_for_send_letter, $mail_pass);


// Очищаем корзину из сессии
if (isset($_SESSION['order_data'])) {unset($_SESSION['order_data']);}

require_once "header_styles.php";
?>



<!-- шапка -->
<link rel="stylesheet" href="styles/okpaid.css">
    <!-- Основной контент -->
    <main class="confirmation-container">
        <div class="confirmation-header">
            <div class="confirmation-icon">
                <i class="fas fa-check"></i>
            </div>
            <h1>Заказ успешно оформлен!</h1>
            <p>Спасибо за ваш заказ. Наш менеджер свяжется с вами в ближайшее время для подтверждения.</p>
            <div class="order-number">Номер заказа: <?php echo $orderData['order_number']; ?></div>
        </div>
        
        <div class="confirmation-content">
            <!-- Информация о заказе -->
            <div class="info-section">
                <h2><i class="fas fa-info-circle"></i> Информация о заказе</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Дата заказа</div>
                        <div class="info-value"><?php echo date('d.m.Y H:i', strtotime($orderData['order_date'])); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Статус</div>
                        <div class="info-value" style="color: var(--primary-green); font-weight: 700;">Оплачен</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Способ оплаты</div>
                        <div class="info-value"><?php echo getPaymentMethodName($orderData['payment']['method']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Способ доставки</div>
                        <div class="info-value"><?php echo getDeliveryMethodName($orderData['delivery']['method']); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Информация о покупателе -->
            <div class="info-section">
                <h2><i class="fas fa-user"></i> Информация о покупателе</h2>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">ФИО</div>
                        <div class="info-value"><?php echo $orderData['customer']['full_name']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Телефон</div>
                        <div class="info-value"><?php echo $orderData['customer']['phone']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo $orderData['customer']['email'] ?: 'Не указан'; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Состав заказа -->
            <div class="info-section">
                <h2><i class="fas fa-box"></i> Состав заказа</h2>
                <div class="order-items">
                    <?php foreach ($orderData['cart_items'] as $item): ?>
                        <div class="order-item">
                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div class="item-meta">
                                    <span><?php echo $item['category']; ?></span>
                                    <span><?php echo $item['quantity']; ?> шт. × <?php echo number_format($item['price'], 0, '', ' '); ?> ₽</span>
                                    <span style="font-weight: 700; color: var(--primary-green);">
                                        <?php echo number_format($item['price'] * $item['quantity'], 0, '', ' '); ?> ₽
                                    </span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="order-summary">
                    <div class="summary-item">
                        <span>Товары  :</span>
                        <span><?php echo number_format($orderData['total_amount'], 0, '', ' '); ?> ₽</span>
                    </div>
                    <div class="summary-item">
                        <span>Доставка:</span>
                        <span>
                            <?php 
                            $deliveryCost = 0;
                            if ($orderData['delivery']['method'] === 'courier') {
                                $deliveryCost = 500;
                            } elseif ($orderData['delivery']['method'] === 'russia') {
                                $deliveryCost = 800;
                            } elseif ($orderData['delivery']['method'] === 'ozon') {
                                $deliveryCost = 400;
                            }
                            echo $deliveryCost > 0 ? number_format($deliveryCost, 0, '', ' ') . ' ₽' : 'Бесплатно';
                            ?>
                        </span>
                    </div>
                    <div class="summary-item total">
                        <span>Итого:</span>
                        <span>
                            <?php 
                            echo number_format($orderData['total_amount'] + $deliveryCost, 0, '', ' '); 
                            ?> ₽
                        </span>
                    </div>
                </div>
            </div>
            
            <?php if (!empty($orderData['comment'])): ?>
                <div class="info-section">
                    <h2><i class="fas fa-comment"></i> Комментарий к заказу</h2>
                    <div class="info-item">
                        <div class="info-value"><?php echo nl2br(htmlspecialchars($orderData['comment'])); ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Что дальше -->
        <div class="whats-next">
            <h3><i class="fas fa-clock"></i> Что дальше?</h3>
            <div class="steps">
                <div class="step">
                    <div class="step-number">1</div>
                    <h4>Подтверждение заказа</h4>
                    <p>Наш менеджер свяжется с вами в течение 30 минут для подтверждения заказа</p>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <h4>Подготовка товара</h4>
                    <p>Товар будет собран и подготовлен к отправке в течение 1-2 рабочих дней</p>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <h4>Доставка</h4>
                    <p>Мы доставим ваш заказ в согласованные сроки</p>
                </div>
            </div>
        </div>
        
        <div class="confirmation-actions">
            <a href="index.php" class="btn-home">
                <i class="fas fa-home"></i> Вернуться на главную
            </a>
            <button onclick="window.print()" class="btn-print">
                <i class="fas fa-print"></i> Распечатать заказ
            </button>
        </div>
    </main>

    <script>
        // Очищаем корзину в localStorage после оформления заказа
        document.addEventListener('DOMContentLoaded', function() {
            localStorage.removeItem('cart');
            
            // Обновляем счетчик корзины если он есть на странице
            const cartCounter = document.querySelector('.cart-count');
            if (cartCounter) {
                cartCounter.textContent = '0';
            }
        });
    </script>
</body>
</html>
<?php

// Функция для получения названия метода доставки
function getDeliveryMethodName($method) {
    $methods = [
        'pickup' => 'Самовывоз',
        'courier' => 'Курьерская доставка',
        'russia' => 'Доставка по России',
        'ozon' => 'ОЗОН Логистика'
    ];
    return $methods[$method] ?? $method;
}

// Функция для получения названия метода оплаты
function getPaymentMethodName($method) {
    $methods = [
        'cash' => 'Наличными при получении',
        'card' => 'Банковской картой онлайн'
    ];
    return $methods[$method] ?? $method;
}
?>

