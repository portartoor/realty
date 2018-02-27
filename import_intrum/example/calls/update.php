<?php

	require_once '../usage.php'; //настройте данный конфигурационный файл
	
	/*
	 * Пример обновления звонка
	 */
    
    //Список всех статусов
    //$statuses = $api->callsGetStatuses();
    //print_r($statuses);
    
    //При обновлении, передать только те поля, которые нужно обновить. Ниже пример запроса со всеми доступными полями
	$res = $api->callsUpdate(array(
        'uniqueId'     => '1a6bd050b157dbddb622049084473691',
        'url'          => 'http://you.suite/rec.mp3',
        'callDuration' => 60,
        'isAnswered'   => 1,
        'customStatus' => 3
    ));
    
    print_r($res);
    
?>
