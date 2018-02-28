<?
ini_set('display_errors', 1);

global $ROOT = '/home/bitrix/s3/mpa/aplication/';
require_once $ROOT.'core/main.php';

$draw = new Draw('testas');
$draw->head();
$draw->content();
$draw->foot();
?>