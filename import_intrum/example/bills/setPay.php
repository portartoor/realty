<?php

	# 
	#	Пример оплаты счёта
	#

	require_once '../usage.php'; //настройте данный конфигурационный файл
	
	$res = $api->billsSetPay(64,2000); // Частичная оплата
    
    $res = $api->billsSetPay(63); // Полная оплата
	
	print_r($res);
	
?>