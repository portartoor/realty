<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Интеграция c почтой");
?><?$APPLICATION->IncludeComponent(
	"bitrix:crm.config.sendsave",
	"",
	Array(
	),
false
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>