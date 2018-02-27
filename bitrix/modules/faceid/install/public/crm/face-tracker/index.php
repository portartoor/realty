<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Face-tracker");

$APPLICATION->includeComponent('bitrix:crm.control_panel', '',
	array(
		'ID' => 'FACETRACKER',
		'ACTIVE_ITEM_ID' => 'FACETRACKER'
	)
);

?><?$APPLICATION->IncludeComponent(
	"bitrix:faceid.tracker",
	"",
	Array(
		"COMPOSITE_FRAME_MODE" => "A",
		"COMPOSITE_FRAME_TYPE" => "AUTO"
	)
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>