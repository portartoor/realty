<?php

	# 
	#	Пример выборки сотрудников из выбранных CRM групп
	#

	require_once '../usage.php'; //настройте данный конфигурационный файл
	
    //Выборка всех отделов
    $groups = $api->getAvailGroups();
    //print_r($groups);
    
    $groupId = $groups['data'][0]['id'];
    
	$res = $api->filterEmployee(array(
        'group' => $groupId
    )); 
	
	print_r($res);
	
?>