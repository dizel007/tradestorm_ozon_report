<?php

$query_data_for_token = '{
    "client_id":"43209969-1729849782881@advertising.performance.ozon.ru", 
    "client_secret":"6k6SRW1S5lhUAN7GzcIhug8FFnU0tTGTNP_8mpqn2KN4ojFZYvn04nq2lfg99kfwt0XJ6lsiFfss6VytrA", 
    "grant_type":"client_credentials"}';

$token = get_token_for_reklama_report('0a2679cf-74cb-43eb-b042-94997de5f748', '1724451', $query_data_for_token);
$token_Reklama = $token['access_token'];
// print_r($token);


$OzonLink = 'https://api-performance.ozon.ru:443/api/client/campaign';

// $Spisok_reklamnih_companii = GetQueryForAoi_Ozon($token_Reklama, $OzonLink, '') ;


echo "<pre>";
//  print_r($Spisok_reklamnih_companii);



 // Запрос на подготовку отчета в JSON формате
$OzonLink = 'https://api-performance.ozon.ru:443/api/client/statistics/json';
 $send_data = '{
    "campaigns":["16095477"],
    "dateFrom":"2025-10-01",
    "dateTo":"2025-10-05",
    "groupBy":"DATE"
}';

// $Zapros_na_report = GetQueryForAoi_Ozon($token_Reklama, $OzonLink,  $send_data) ;
// print_r($Zapros_na_report);

// Проверяем статус отчета 
// $OzonLink = 'https://api-performance.ozon.ru:443/api/client/statistics/'.$Zapros_na_report['UUID'];

$OzonLink = 'https://api-performance.ozon.ru:443/api/client/statistics/c232fb47-e3de-4f3e-adaa-e6be4126c5ac';

$i = 0;
// do {
$i++;
// sleep (7);
// $rezult_oprosa = GetQueryForAoi_Ozon($token_Reklama, $OzonLink, '') ;
// if ($rezult_oprosa['state'] == 'OK') {break;}	
// } while ($i <= 4);


// print_r($rezult_oprosa );

// die();

// sleep(10);

// проверить статус отчета
// c232fb47-e3de-4f3e-adaa-e6be4126c5ac


// /api/client/statistics/report?UUID=c232fb47-e3de-4f3e-adaa-e6be4126c5ac

// запрос на скачивание отчетв
// $OzonLink = 'https://api-performance.ozon.ru:443/api/client/statistics/report';

$OzonLink = 'https://api-performance.ozon.ru:443/api/client/statistics/report?UUID=c232fb47-e3de-4f3e-adaa-e6be4126c5ac';

$ggg = GetQueryForAoi_Ozon($token_Reklama, $OzonLink, '');
 print_r($ggg);   



/***************************************************************************************************************
*********  Функция получения access_token для работы с Performance API
************************************************************************************************************* */

function get_token_for_reklama_report($token, $client_id, $send_data ) {
	$ch = curl_init('https://api-performance.ozon.ru/api/client/token');
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Api-Key:' . $token,
		'Client-Id:' . $client_id,
        'Accept: application/json',
		'Content-Type:application/json'
	));
    // curl_setopt($ch, CURLOPT_POST,true); 
    curl_setopt($ch, CURLOPT_POSTFIELDS, $send_data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_HEADER, false);
	$res = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Получаем HTTP-код
  	curl_close($ch);
	
	$res = json_decode($res, true);

  
   if (intdiv($http_code,100) > 2) {
        echo     'Результат обмена : '.$http_code. "<br>";
        echo "<pre>";
        print_r($res);
	}
   
    return($res);	

}




/***************************************************************************************************************
*********  Функция получения access_token для работы с Performance API
************************************************************************************************************* */


function GetQueryForAoi_Ozon($token_Reklama, $OzonLink, $send_data) {
	$ch = curl_init($OzonLink);
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Authorization: Bearer ' . $token_Reklama,
        'Accept: application/json',
		'Content-Type:application/json'
	));
    // curl_setopt($ch, CURLOPT_POST,true); 
    if ($send_data !='') { curl_setopt($ch, CURLOPT_POSTFIELDS, $send_data); }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
	$res = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Получаем HTTP-код
  	curl_close($ch);
	
	$res = json_decode($res, true);
  echo "Результат обмена : ".$http_code. "<br>";
  
   if (intdiv($http_code,100) > 2) {
        echo "Результат обмена : ".$http_code. "<br>";
        echo "<pre>";
        print_r($res);
	}
   
    return($res);	

}