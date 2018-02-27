<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("График отсутствий");
?><?$APPLICATION->IncludeComponent("bitrix:intranet.absence.calendar", ".default", Array(
	"FILTER_NAME"	=>	"absence",
	"FILTER_SECTION_CURONLY"	=>	"N"
	)
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>