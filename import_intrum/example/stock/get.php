<?php
//описание и документация по API INTRUM http://www.intrumnet.com/api/
	
	# API Example
	# 
	#	Пример выгрузки списка продуктов
	#	из определенной категории с указанным
	#	ценовым диапазоном
	#

	require_once '../usage.php'; //настройте данный конфигурационный файл

	// число записей получаемых за один запрос, макс. 50
	$limit = 20;
	
	// параметры фильтрации
	$filter = array(
        'type' => 7, // id типа продуктов (справочник типов по запросу https://www.intrumnet.ru/api/#stock-types или в админпанели см. скрин https://yadi.sk/i/9P5ojFLY3LQrFx)
		'category' => 131, // id категории продуктов  (справочник категорий по запросу https://www.intrumnet.ru/api/#stock-category или в админпанели см. скрин https://yadi.sk/i/whL4AwPl3LR6k5)
		'fields' => array( // список фильтров по полям
			array(	
				'id' => 81, //id поля (справочник по полям через запрос https://www.intrumnet.ru/api/#stock-fields или в админпанели см. скрин https://yadi.sk/i/v-lUA90y3LPrhq
				'value' => '20000 & 21000' // критерий цены между 20 и 21 тыс.
			)
		),
		'page' => 1, // начало с первой страницы
		'limit' => $limit
    );
	
	//чтение всех доступных страниц
	while(true){
		$response = $api->getStockByFilter($filter);
		// при неудачном ответе прерываем выборку
		if('success' != $response['status'])
			break;
			
		/* переменная $list содержит массив выбранных продуктов,
			выполнить необходимые действия */
		$list = $response['data']['list'];
		
		// общее число найденных продуктов
		$count = (int)$response['data']['count'];
		
		// если выборка пустая или прочитан весь список
		if(0 == $count or (($filter['page'] * $limit) >= $count))
			break;
		// переход к следующей странице
		++$filter['page'];
	}
?>
