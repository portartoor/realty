<?php
    /* Вариант использования без composer*/
	require_once  '../../Intrum/Api.php';
	require_once  '../../Intrum/Cache.php';
	
	/*Intrum\Cache::getInstance()->setup(
		array(
			"folder" => __DIR__ . "/cache",
			"expire" => 600
		)
	);*/
	
	$api = Intrum\Api::getInstance()
	->setup(
		array(
			"host"   => "inventrealty.intrumnet.com",//"yourdomain.intrumnet.com",
			"apikey" => "82cfe2fe5872e5f0fc7c9f5075d44061",
			"cache"  => false,
			"port"   => 80
		)
	);
?>