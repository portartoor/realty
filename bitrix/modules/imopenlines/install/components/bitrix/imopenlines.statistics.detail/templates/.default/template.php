<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use \Bitrix\Main\Localization\Loc;
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

Loc::loadMessages(__FILE__);

if ($arResult['LINE_NAME'])
{
	$APPLICATION->SetTitle(Loc::getMessage("OL_STAT_TITLE", Array('#LINE_NAME#' => htmlspecialcharsbx($arResult['LINE_NAME']))));
}

$buttons = array();

if ($arResult['LINE_NAME'])
{
	$buttons[] = array(
		"TEXT"=>GetMessage("OL_STAT_BACK"),
		"TITLE"=>GetMessage("OL_STAT_BACK_TITLE"),
		"LINK"=> \Bitrix\ImOpenLines\Common::getPublicFolder(),
		"ICON"=>"go-back",
	);
}
$buttons[] = array(
	"TEXT"=>GetMessage("OL_STAT_EXCEL"),
	"TITLE"=>GetMessage("OL_STAT_EXCEL"),
	'LINK' => $APPLICATION->GetCurPageParam('excel=Y'),
	'ICON' => 'btn-excel',
);

?>
<?$APPLICATION->IncludeComponent(
	"bitrix:main.interface.toolbar",
	"",
	array("BUTTONS"=> $buttons),
	$component
);?>
<div class="tel-stat-grid-wrap">
<?$APPLICATION->IncludeComponent(
	"bitrix:main.interface.grid",
	"",
	array(
		"GRID_ID"=>$arResult["GRID_ID"],
		"HEADERS"=>$arResult["HEADERS"],
		"FILTER"=>$arResult["FILTER"],
		"FILTER_TEMPLATE_NAME"=>"tabbed",
		"ROWS"=>$arResult["ELEMENTS_ROWS"],
		"NAV_OBJECT"=>$arResult["NAV_OBJECT"],
		"FOOTER" => array(
			array("title" => GetMessage("CT_BLL_SELECTED"), "value" => $arResult["ROWS_COUNT"])
		),
		"AJAX_MODE" => "N",
	),
	$component, array("HIDE_ICONS" => "Y")
);?>
</div>


