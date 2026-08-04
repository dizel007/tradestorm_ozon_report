<?php
// ---------------------------------------------------------------------
// Генерация и установка remember-токена
// ---------------------------------------------------------------------
function setRememberToken($pdo, $userId) {
    $token = bin2hex(random_bytes(32));
    $expiry = date('Y-m-d H:i:s', time() + 72000); // 20 часов

    $update = $pdo->prepare("UPDATE `sellers` SET `remember_token` = :token, `remember_token_expiry` = :expiry WHERE `id` = :id");
    $update->execute(['token' => $token, 'expiry' => $expiry, 'id' => $userId]);

    setcookie('remember_token', $token, time() + 72000, '/', '', false, true);
    return $token;
}

// ---------------------------------------------------------------------
// Отправка запроса к Ozon API (возвращает ответ или false)
// ---------------------------------------------------------------------
function post_with_data_ozon($token, $client_id, $data, $url) {
    $ch = curl_init('https://api-seller.ozon.ru/' . $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Api-Key: ' . $token,
        'Client-Id: ' . $client_id,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if (intdiv($http_code, 100) !== 2) {
        return false;
    }
    return json_decode($response, true);
}

// ---------------------------------------------------------------------
// Проверка токена Ozon (нужны права Report и Product)
// ---------------------------------------------------------------------
function check_ozon_token($token, $client_id, $pdo) {
    $priznak = 0;
    $today = date('Y-m-d');

    // Проверка Report
    $data = json_encode([
        "filter" => [
            "date" => ["from" => $today . "T00:00:00.000Z", "to" => $today . "T00:00:00.000Z"],
            "operation_type" => [],
            "posting_number" => "",
            "transaction_type" => "all"
        ],
        "page" => 1,
        "page_size" => 1
    ]);
    $http = send_query_on_ozon($token, $client_id, $data, "v3/finance/transaction/list");
    if (intdiv($http, 100) <= 2) $priznak++;

    // Проверка Product
    $data = json_encode([
        "cursor" => "",
        "filter" => ["visibility" => "ALL"],
        "limit" => 1
    ]);
    $http = send_query_on_ozon($token, $client_id, $data, "v5/product/info/prices");
    if (intdiv($http, 100) <= 2) $priznak++;

    return $priznak; // 2 – успех
}

// ---------------------------------------------------------------------
// Вспомогательная функция для отправки запроса (возвращает HTTP-код)
// ---------------------------------------------------------------------
function send_query_on_ozon($token, $client_id, $data, $url) {
    $ch = curl_init('https://api-seller.ozon.ru/' . $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Api-Key: ' . $token,
        'Client-Id: ' . $client_id,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HEADER, false);

    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $http_code;
}