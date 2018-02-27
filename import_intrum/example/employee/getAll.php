<?php

	# 
	#	Пример выборки всех сотрудников
	#

	require_once '../usage.php'; //настройте данный конфигурационный файл
	
	$res = $api->filterEmployee(array(
        //Все работающие сотрудники
        'status' => array("onstate", "outstate")
        //Вообще все сотрудники, включая уволенных, неработающих  и не прошедших модерацию
        //['new', 'onstate', 'outstate', 'notworking', 'fired']
    )); 
	
	print_r($res);
	
?>
