<?php

	# API Example_bills_add
	# 
	#	Пример добавления счётов
	#

	require_once '../usage.php'; //настройте данный конфигурационный файл
	
	//Массив счетов
	$list = array(
		1=> array(
			'act_id'=> "",						//ID связанного акта
			'date_create'=> "07.10.2015",		//Дата  создания
			'product'=>array(					//Массив продуктов
				1=>array(
					'count'=> "10",				//Кол-во продукта				
					'name'=> "Карандаши",		//Название продукта
					'price'=> "5",				//Цена продукта
				)
			),
			'client_company_id'=> "90",			//ID реквизитов клиента
			'client_id'=> "10481",				//ID клиента
			'my_company_id'=> "2",				//ID реквизитов фирмы
			'nds'=> "1",						//Признак НДС
			'type'=> "out"						//Тип счёта
		),
		2=> array(
			'date_create'=> "07.10.2015",
			'client_company_id'=> "90",
			'client_id'=> "10481",
			'my_company_id'=> "2",
			'nds'=> "1",
			'type'=> "out"
		)
	);
	//Обращаемся к api, добавляем счета
	$res = $api->billsAdd($list); 
	//Если успешно выводим статистику
	if($res['status']=='success'){
		echo "<p> Успешный запрос </p>";
		$add = 0;
		$error = 0;
		//Api возвращает массив обьектов, содержащие статус добавления счёта, 
		//ID нового счёта при успехе и список ошибок, если вставка не удалась
		foreach($res['data'] as $item){
			if($item['stat']==1){
				$add ++;
				echo "<p> Добавлен счёт, id {$item['id']} </p>";
			}else{
				$error ++;
				echo "<p> <div>Ошибка добавления счёта</div> <pre>".print_r($item['error'],true)."</pre></p>";
			}
		}
		echo "<div> Добавлено: $add; Ошибок добавления: $error; </div>";
	}else{
		echo "<p> Ошибка выполнения запроса </p>";
	}
	
	
?>