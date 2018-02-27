<?php

/*
 *  Пример изменения статуса заявки
 */

require_once '../usage.php'; 

//Возможные статусы заявки
//$res = $api->getRequeststatuses();
//print_r($res);


$res = $api->updateRequests(array(
    array(
        'id'     => 18633,
        'status' => 'cancelled'
    )
));

print_r($res);

