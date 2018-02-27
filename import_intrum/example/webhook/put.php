<?php

require_once '../usage.php'; //настройте данный конфигурационный файл

$res = $api->webhookPut();

print_r($res);

