<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Video Conference");
?>
<?
$APPLICATION->IncludeComponent("bitrix:video", ".default", array(
	"IBLOCK_TYPE" => "events",
	"IBLOCK_ID" => "#CALENDAR_RES_VIDEO_IBLOCK_ID#",
	"PATH_TO_VIDEO_CONF" => "#VIDEO_INST_PATH#/detail.php?ID=#ID#",
	"SET_TITLE" => "Y",
	),
	false
);
?>
<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>
