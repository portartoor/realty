<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Доска почета");
?>

<?$APPLICATION->IncludeComponent("bitrix:intranet.structure.honour", ".default", Array(
	"STRUCTURE_PAGE"	=>	"/company/structure.php",
	"PM_URL"	=>	"/company/personal/messages/chat/#USER_ID#/",
	"PATH_TO_VIDEO_CALL" => "/company/personal/video/#USER_ID#/",
	"PATH_TO_CONPANY_DEPARTMENT" => "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#",
	"STRUCTURE_FILTER"	=>	"structure",
	"NUM_USERS"	=>	"25",
	"USER_PROPERTY"	=>	array(
		0	=>	"PERSONAL_PHONE",
		1	=>	"UF_DEPARTMENT",
		2	=>	"UF_PHONE_INNER",
		3	=>	"UF_SKYPE",
	)
	)
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>