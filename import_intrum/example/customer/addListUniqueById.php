<?php

	require_once '../usage.php'; //настройте данный конфигурационный файл
	
	/*
	 * Пример добавления списка клиентов, с проверкой : добавление новых, обновление уже добавленных
	 */
    
    //Список всех fields
    //$fileds = $api->getCustomerFields();
    //print_r($fileds);
    //die();
    
    //Тут проверка на уникальность по id в вашей системе, для этого предварительно надо создать поле синхронизации в интруме,
    // и при добавлении клиентов передвать это поле
    
    //В примере это поле 123
    
    $customers = array(
        array(
            'name' => 'Дмитрий',
            'surname' => 'Дмитриченко',
            'secondname' => 'Дмитриевич',
            'email' => array(
                "test1@test.ru"
            ),
            'phone' => array(
                "880020002"
            ),
            'fields' => array(
                // Акаунт в соцсети // text
                array(
                    'id' => 123,
                    'value' => 1
                )
            )
        ),
        array(
            'name' => 'Андрей',
            'surname' => 'Лященко',
            'secondname' => '',
            'fields' => array(
                array(
                    'id' => 123,
                    'value' => 2
                )
            )
        ),
        array(
            'name' => 'CRM',
            'surname' => 'Интрум',
            'secondname' => '',
            'fields' => array(
                array(
                    'id' => 123,
                    'value' => 3
                )
            )
        )
    );
    
    //Ищем уже добавленные, их складываем в отдельный массив, по нему найденных клиентов обновим
    $update = array();
    foreach($customers as $num=>$customer){
        $res = $api->filterCustomers(array(
            'fields' => array(
                array(
                    'id'    => 123,
                    'value' => $customer['fields'][0]['value']
                )
            )
        ));
        //Значит дубль
        if($res['data']['list']){
            unset($customers[$num]);
            $customer['id'] = $res['data']['list'][0]['id'];
            $update[] = $customer;
        }
    }
    
    //Добавление всех типов fileds представлено в примере example/stock/add.php
	$res = $api->insertCustomers($customers);
    
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
    
    if($update){
        $api->updateCustomers($update);
    }
    
?>