<?php

/*
 *  Пример получения всех клиентов
 */

require_once '../usage.php'; 

$res = $api->getListCustomers(array(
    'order_field' => 'id',
    'order'       => 'DESC',
    'limit'       => 1000000 //Максимум 
));

file_put_contents('getAll.txt', print_r($res,true));
$res['list'] = count($res['list']);
print_r($res);