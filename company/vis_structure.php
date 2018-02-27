<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Структура компании");
$APPLICATION->AddChainItem("Структура компании", "vis_structure.php");
?>
<?
$APPLICATION->IncludeComponent("bitrix:intranet.structure.visual", ".default", array(
	"DETAIL_URL" => "/company/structure.php?set_filter_structure=Y&structure_UF_DEPARTMENT=#ID#",
	"PROFILE_URL" => "/company/personal/user/#ID#/",
	"PM_URL" => "/company/personal/messages/chat/#ID#/",
	"NAME_TEMPLATE" => "",
	"USE_USER_LINK" => "Y"
	),
	false
);
?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>