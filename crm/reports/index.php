<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Воронка продаж");
?><?$APPLICATION->IncludeComponent(
	"bitrix:crm.deal.funnel",
	"",
	Array(
	),
false
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>