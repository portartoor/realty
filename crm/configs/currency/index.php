<? 
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php"); 
global $APPLICATION;

$APPLICATION->SetTitle("Валюты");
$APPLICATION->IncludeComponent(
	"bitrix:crm.currency", 
	".default", 
	array(
		"SEF_MODE" => "Y",
		"SEF_FOLDER" => "/crm/configs/currency/",
	),
	false
);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php"); 
?>
