<?php

	require_once '../usage.php'; //настройте данный конфигурационный файл
	
	/*
	 * Пример добавления контактов (клиентов)
	 */
    
    //Список всех fields
    //$fileds = $api->getCustomerFields();
    //print_r($fileds);
    //die();
    
    //Добавление все типов fileds представлено в примере example/stock/add.php
    
	$res = $api->insertCustomers(
		// массив Добавляемых записей
		array(
			// отдельная запись
			array(
			   'name'       => 'Иван',
               'surname'    => 'Иванов',
               'secondname' => 'Иванович',
               'manager_id' => 0,
               'marktype'   => 0,
               'email'      => array(
                   //Поддерживается 2 формата, с комментарием
                   array(
                      "mail"    => "test@test.ru",
                      "comment" => "Рабочий email"
                   ),
                   //И без
                   "test1@test.ru"
               ),
               'phone' => array(
                   //Поддерживается 2 формата, без комментария
                   "880020002",
                   //С комментарием
                   array(
                       "phone"   => "880020002",
                       "comment" => "Домашний номер"
                   )
               ),
               'fields' => array(
                    // Вариант select
                    array(
                        'id' => 467,
                        'value' => 'Самара'
                    ),
                   // radio
                   array(
                       'id' => 786,
                       'value' => 1 // 1-да, 0 - нет
                   ),
                   // text
                   array(
                       'id' => 437,
                       'value' => "Описание клиента"
                   ),
                   // price
                    array(
                        'id' => 339,
                        'value' => 3765000
                    )
                )
			)
		)
	);
    
    if($res['data']){
        
        $list = array();
        foreach($res['data'] as $id){
            $list[ $id ] = array(
                realpath('../upload.jpg'),
                realpath('../upload1.jpg')
            );
        }
        //                            Тип объекта   ID поля   Двумерный массив, ключи - ID объектов в Intrum, значение массив путей к картинкам
        $re = $api->addFilesToObjects('purchaser',  862,      $list);
        
    }
    
?>
