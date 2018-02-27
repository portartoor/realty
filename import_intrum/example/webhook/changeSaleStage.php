<?php

require_once '../usage.php'; //настройте данный конфигурационный файл
//Получаем событие
$res = $api->webhookPut();
//Проверяем событие, если от сделок и о смене статуса, выполняем свою логику
if($res['object_type']=='sale' && $res['event']=='stage'){
    // ....
    file_put_contents('changeSaleStage.log', print_r($res,true));
}


