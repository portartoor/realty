<?php

	require_once '../usage.php'; //настройте данный конфигурационный файл
	
	/*
	 * Пример редактирования клиента
	 */

	return $api->updateCustomers(
		// массив обновляемых записей
		array(
			// отдельная запись
			array(
				'id' => 277068,
				'surname' => 'Иванов',
				'name' => 'Владимир',
				'fields' => array(
					array(
						'id' => 945,
						'value' => 1
					)
				)
			)
		)
	);
?>