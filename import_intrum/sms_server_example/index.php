<?php
	//описание и документация по API INTRUM http://www.intrumnet.com/api/
	
	require_once('settings.php');
	require_once('IntrumSmsApi.php');
	require_once('CustomSmsService.php');
	$api = new CustomSmsService($API_KEY, $CRM_URL);
	
	// Обрабатываем входящие запросы
	if (isset($_POST['hash'])) {
		echo json_encode($api->routeApiRequest($_POST));
	
	// Генерируем тестовые смс
	} else if (isset($_GET['send'])) {
		$smsId = time();
		$sender = isset($_GET['sender']) ? $_GET['sender'] : '54321';
		$destination = isset($_GET['destination']) ? $_GET['destination'] : '12345';
		$message = $_GET['send'];
		$count = isset($_GET['count']) ? $_GET['count'] : 1;
		
		echo json_encode($api->smsRecieved($smsId, $sender, $destination, $message, $count));
	
	} else {
		echo '<meta charset="utf-8"></meta>Мой SMS Server';
	}
