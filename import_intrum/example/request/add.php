<?php

/*
 *  Пример добавления заявки
 */

require_once '../usage.php'; 

//Возможные статусы заявки
//$res = $api->getRequeststatuses();
//print_r($res);


//Выборка все возможных типов заявок
$types = $api->getRequestTypes();
//print_r($types);
$typeId = $types['data'][0]['id'];

//Все возможные fields всех типов заявок
$fields = $api->getRequestFields();
//Fields выбранного типа заявок
$myFields = $fields['data'][$typeId]['fields'];
print_r($myFields);
die("sss");
$res = $api->insertRequests(array(
    array(
       'request_type'  => $typeId,
       'customers_id'  => 98328,
       'source'        => 'help_manager',
       'employee_id'   => 79,
       'status'        => 'reprocess',
       'fields' => array(
           array(
               'id'    => 731,
               'value' => 'неверный телефон'
           ),
           array(
               'id'    => 732,
               'value' => "2017-07-20 14:00:00"
           )
       )
    )
));

print_r($res);

