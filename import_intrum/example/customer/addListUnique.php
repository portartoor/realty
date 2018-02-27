<?php

	require_once '../usage.php'; //настройте данный конфигурационный файл
	
	/*
	 * Пример добавления списка клиентов, с проверкой ( добавление только уникальных )
	 */
    
    //Список всех fields
    //$fileds = $api->getCustomerFields();
    //print_r($fileds);
    //die();
    
    //Тут проверка на уникальность по адресу аккаунта в вк
    
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
                    'id' => 663,
                    'value' => 'https://vk.com/id353123398'
                )
            )
        ),
        array(
            'name' => 'Андрей',
            'surname' => 'Лященко',
            'secondname' => '',
            'fields' => array(
                array(
                    'id' => 663,
                    'value' => 'https://vk.com/id14034522'
                )
            )
        ),
        array(
            'name' => 'CRM',
            'surname' => 'Интрум',
            'secondname' => '',
            'fields' => array(
                array(
                    'id' => 663,
                    'value' => 'https://vk.com/intrum'
                )
            )
        )
    );
    
    
    foreach($customers as $num=>$customer){
        $res = $api->filterCustomers(array(
            'fields' => array(
                array(
                    'id'    => 663,
                    'value' => $customer['fields'][0]['value']
                )
            )
        ));
        //Значит дубль
        if($res['data']['list']){
            unset($customers[$num]);
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
    
?>