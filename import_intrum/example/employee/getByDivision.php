<?php

	# 
	#	Пример выборки сотрудников из выбранных отделов
	#

	require_once '../usage.php'; //настройте данный конфигурационный файл
	
    //Выборка всех отделов
    $divisions = $api->getDepartment();
    //print_r($divisions);
    
    $divisionId1 = $divisions['data'][0]['id'];
    $divisionId2 = $divisions['data'][1]['id'];
    
	$res = $api->filterEmployee(array(
        'division_id' => array($divisionId1, $divisionId2)
    )); 
	
	print_r($res);
	
?>