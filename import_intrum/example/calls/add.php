<?php

	require_once '../usage.php'; //настройте данный конфигурационный файл
	
	/*
	 * Пример добавления звонка
	 */
    
    //Список всех соединений, trunk_id
    $trunks = $api->callsGetTrunks();
    //print_r($trunks);
    $trunkId = $trunks['data'][0]['trunk_id'];
    
    //Список всех статусов
    //$statuses = $api->callsGetStatuses();
    //print_r($statuses);
    
	$res = $api->callsAdd(array(
        'uniqueId'     => md5(time()),
        'from'         => 880020002,
        'to'           => 102,
        'trunkId'      => $trunkId,
        'timestamp'    => time() * 1000,
        'isIncoming'   => 1, //Входящий
        'url'          => 'http://you.suite/rec.mp3',
        'callDuration' => 30,
        'isAnswered'   => 1,
        'customStatus' => 1
    ));
    
    print_r($res);
    
?>