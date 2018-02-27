<?php

/*
 *  Пример получения списка клиентов по фильтру
 */

require_once '../usage.php'; 

//Список всех fields
$fileds = $api->getCustomerFields();
//print_r($fileds);

$res = $api->filterCustomers(array(
    'limit'  => 1,
    'search' => "Иван",
    'fields' => array(
        array(
            'id' => 656,
            'value' => "1"
        ),
         //Клиенты с бюджетом больше или равному 30000
        array(
            'id' => 333,
            'value' => ">=30000"
        ),
    )
));

//Выборка всех страниц результата, аналогично example/customer/getAll.php

print_r($res,true);

