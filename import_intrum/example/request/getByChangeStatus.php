<?php

/*
 *  Выборка Заявок, статус которых менялся за период "2017-05-01" - "2017-07-19"
 */

require_once '../usage.php'; 
//Выборка списка id заявок, изменивших статус
$res = $api->getRequestChangeStatus(array(
    'date_start' => "2017-05-01",
    'date_end'   => "2017-07-20"
));
//print_r($res);


//Выборка самих заявок
$res = $api->filterRequests(array(
    'by_ids'   => $res['data']['list']
));

print_r($res);

