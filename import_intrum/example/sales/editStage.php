<?php

/*
 *  Пример добавления сделки
 */

require_once '../usage.php'; 

$sale = $api->filterSales(array(
    'byid' => 274
));
$sale = $sale['data']['list'][0];
print_r($sale);

//Выборка всех возможных типов
$types = $api->getSaleTypes();
//($types);

//Ищем тип сделки
$mytype = null;
foreach($types['data'] as $type){
    if($type['id'] == $sale['sale_type_id']){
        $mytype = $type;
        break;
    }
}
//Выбираем последнюю стадию данной сделки
$stage = end($mytype['stages']);
$stageId = $stage['id'];

$res = $api->updateSales(array(
    array(
        'id'              => 274,
        'sales_status_id' => $stageId,
    )   
));

print_r($res);

