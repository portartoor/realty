<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Title");

$xmlstr = 'upload/1c/orders.xml';

$xml = simplexml_load_file($xmlstr);

$i=0;
foreach ( $xml->item as $element ) {
$i++;
echo $i.'<br>';
}
?>


<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>