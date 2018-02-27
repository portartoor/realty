<?
/**
 * @var array $arResult
 * @var array $arParams
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->SetAdditionalCSS("/bitrix/components/bitrix/voximplant.main/templates/.default/telephony.css");
ShowError($arResult["ERROR_TEXT"]);

if (!$arResult["ENABLE_EXPORT"])
{
	CBitrix24::initLicenseInfoPopupJS();
	?>
	<script type="text/javascript">
		function viOpenTrialPopup(dialogId)
		{
			B24.licenseInfoPopup.show(dialogId, "<?=CUtil::JSEscape($arResult["TRIAL_TEXT"]['TITLE'])?>", "<?=CUtil::JSEscape($arResult["TRIAL_TEXT"]['TEXT'])?>");
		}
	</script>
	<?
}

$APPLICATION->IncludeComponent(
	"bitrix:main.interface.toolbar",
	"",
	array(
		"BUTTONS" => $arResult['BUTTONS']
	),
	$component
);

$APPLICATION->IncludeComponent(
	"bitrix:main.interface.grid",
	"",
	array(
		"GRID_ID" => $arResult["GRID_ID"],
		"HEADERS" => $arResult["HEADERS"],
		"ROWS" => $arResult["ROWS"],
		"NAV_OBJECT" => $arResult["NAV_OBJECT"],
		"SORT" => $arResult["SORT"],
		"FILTER" => $arResult["FILTER"],
		"FILTER_PRESETS" => $arResult["FILTER_PRESETS"],
		"FILTER_TEMPLATE_NAME" => 'tabbed',
		"FOOTER" => array(
			array("title" => GetMessage("CT_BLL_SELECTED"), "value" => $arResult["ROWS_COUNT"])
		),
		"AJAX_MODE" => "N",
	),
	$component, array("HIDE_ICONS" => "Y")
);
