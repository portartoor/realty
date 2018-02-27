<?php

/*
 *  Пример добавления сделки
 */

require_once '../usage.php'; 

//Выборка всех возможных типов
$types = $api->getSaleTypes();
//print_r($types);
//Выбираем второй тип
$type    = $types['data'][1];
$typeId  = $type['id'];
//Запоминаем первую стадию данного типа сделки
$stageId = $type['stages'][0]['id'];

$res = $api->insertSales(array(
    array(
        'customers_id'    => 98328,
        'employee_id'     => 79,
        'sales_type_id'   => $typeId,
        'sales_status_id' => $stageId,
        'sale_name'       => "Новая сделка"
    )   
));

print_r($res);

