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
$typeId = $types['data'][1]['id'];
//Все возможные fields всех типов заявок
$fields = $api->getRequestFields();
//Fields выбранного типа заявок
$myFields = $fields['data'][$typeId]['fields'];
echo "<xmp>";
print_r($myFields);echo "</xmp>";
die();
/*$res = $api->insertRequests(array(
    array(
       'request_type'  => $typeId,
       'customers_id'  => 104,
       'source'        => 'help_manager',
       'employee_id'   => 104,
       'status'        => 'reprocess',
       'fields' => array(
           array(
               'id'    => 1213,
               'value' => 'Be Boss сайт'
           )
       )
    )
));*/
/*$fileds = $api->getCustomerFields();
echo "<xmp>";print_r($fileds);echo "</xmp>";*/
$res = $api->filterCustomers(array(
    'limit'  => 1,
    'search' => "89062398049"
));

/*$res = $api->filterCustomers(array(
    'limit'  => 1,
    'fields' => array(
        array(
            'id' => 61,
            'value' => 178714.00
        )
    )
));*/

echo "<xmp>";
print_r($res);
echo "</xmp>";
/*echo "<xmp>";
$res = $api->getListCustomers(array(
    'order_field' => 'id',
    'order'       => 'DESC',
    'limit'       => 1000000 //Максимум 
));

print_r($res);

echo "</xmp>";*/
