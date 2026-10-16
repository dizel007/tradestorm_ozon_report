<?php


/**********************************************************************************
 * Находим дату на которую нужно будет обновить дейтсвие магазина
 *  *********************************************************************************/
function extendSubscription($expiryDateTime, $update_period): string
{
    if (is_string($expiryDateTime)) {
        $expiry = new DateTime($expiryDateTime);
    } else {
        $expiry = $expiryDateTime;
    }

    $now = new DateTime();

    if ($expiry >= $now) {
        $expiry->modify("+".(int)$update_period." days");
    } else {
        $expiry = clone $now;
        $expiry->modify("+".(int)$update_period." days");
    }

    // Возвращаем строку в нужном формате
    return $expiry->format('Y-m-d H:i:s');
}


/******************************************************************************************************
 * Обновляем дату окончания срока действия оплаты магазина в БД 
 ********************************************************************************************************/
function update_Paid_Preiod_In_Shops ($pdo, $shop_id, $subscription_end) {
    $result = '';
     // сохраняем ссылку на оплату в заказ и обновляем статус заказа
    $update = $pdo->prepare("UPDATE `shops` 
                         SET `subscription_end` = :subscription_end 
                         WHERE `id` = :id");

            try {
                $update->execute([
                    'subscription_end' =>  $subscription_end,
                    'id' => $shop_id
                ]);

                if ($update->rowCount() > 0) {
                    $result = "Дата окончания срока оплаты обновлена.<br>";
                } else {
                     $result =  "Сбой при обновлении даты окончания срока действия заказа.<br>";
                }
            } catch (PDOException $e) {
                error_log("Ошибка: " . $e->getMessage());
                echo "Ошибка обновления даты окончания срока действия заказа. <br>";
            }
return $result;
}

/******************************************************************************************************
 * Обновляем дату окончания срока действия оплаты магазина в БД 
 ********************************************************************************************************/
function update_Status_In_Shops ($pdo, $shop_id, $subscription_status) {
    $result = '';
     // сохраняем ссылку на оплату в заказ и обновляем статус заказа
    $update = $pdo->prepare("UPDATE `shops` 
                         SET `subscription_status` = :subscription_status 
                         WHERE `id` = :id");

            try {
                $update->execute([
                    'subscription_status' =>  $subscription_status,
                    'id' => $shop_id
                ]);

                if ($update->rowCount() > 0) {
                    $result = "Дата окончания срока оплаты обновлена.<br>";
                } else {
                     $result =  "Сбой при обновлении даты окончания срока действия заказа.<br>";
                }
            } catch (PDOException $e) {
                error_log("Ошибка: " . $e->getMessage());
                echo "Ошибка обновления даты окончания срока действия заказа. <br>";
            }
return $result;
}



/******************************************************************************************************
 * Обновляем статус Заказа в БД 
 ********************************************************************************************************/
function updateStatusOrderPay ($pdo, $order_id, $status) {
    $result = '';
     // сохраняем ссылку на оплату в заказ и обновляем статус заказа
    $update = $pdo->prepare("UPDATE `paid_orders` 
                         SET `status` = :status 
                         WHERE `id` = :id");

            try {
                $update->execute([
                    'status' =>  $status,
                    'id' => $order_id
                ]);

                if ($update->rowCount() > 0) {
                    $result = "Статус заказа обновлён.";
                } else {
                     $result =  "Заказ не найден.";
                }
            } catch (PDOException $e) {
                error_log("Ошибка: " . $e->getMessage());
                echo "Ошибка обновления.";
            }
return $result;
}

/******************************************************************************************************
 * Обновляем ссылку на оплату Заказа в БД 
 ********************************************************************************************************/
function updatePayLinkOrderPay ($pdo, $order_id, $payLink) {
    $result = '';
     // сохраняем ссылку на оплату в заказ и обновляем статус заказа
    $update = $pdo->prepare("UPDATE `paid_orders` 
                         SET `link_ozon_pay` = :link_ozon_pay
                         WHERE `id` = :id");

            try {
                $update->execute([
                    'link_ozon_pay' =>  $payLink,
                    'id' => $order_id
                ]);

                if ($update->rowCount() > 0) {
                    $result = "Ссылка на оплату обновлена";
                } else {
                     $result =  "Заказ не найден.";
                }
            } catch (PDOException $e) {
                error_log("Ошибка: " . $e->getMessage());
                echo "Ошибка обновления.";
            }
return $result;
}

/* **************************************************************************************************************
*********  Функция обновляния данных на ОЗОН
************************************************************************************************************** */

function ozonFinancePaycreateOrder($send_data) {
$ozon_link = 'https://payapi.ozon.ru/v1/createOrder';
	$ch = curl_init($ozon_link);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		// 'Api-Key:' . '',
		// 'Client-Id:' . $client_id_ozon, 
		'Content-Type:application/json'
	));
	curl_setopt($ch, CURLOPT_POSTFIELDS, $send_data); 
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_HEADER, false);
	$res = curl_exec($ch);

    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Получаем HTTP-код

	// curl_close($ch);
	
	$res = json_decode($res, true);

    if (intdiv($http_code,100) > 2) {
        echo     '<br>Результат обмена ozonPay (с данными POST): '.$http_code. "<br>";
		echo "<pre>";
        print_r($res);
        }

    return($res);	
    }

