<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Веб сервис");
?><?$APPLICATION->IncludeComponent(
	"bitrix:crm.contact.webservice",
	"",
	Array(
	),
false
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>