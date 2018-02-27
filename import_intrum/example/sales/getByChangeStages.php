<?php

/*
 *  Выборка сделок, стадия которых менялась за период "2017-05-01" - "2017-07-19"
 */

require_once '../usage.php'; 

$res = $api->getSalesChangeStage(array(
    'date_start' => "2017-05-01",
    'date_end'   => "2017-07-19"
));

//print_r($res);

$res = $api->filterSales(array(
    'by_ids'   => $res['data']['list']
));

print_r($res);

