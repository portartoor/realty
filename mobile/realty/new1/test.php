<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Тестовая");
?><?$APPLICATION->IncludeComponent(
	"primepix:kladr.address",
	".default",
	Array(
		"BUILDING_INPUT" => "N",
		"COMPONENT_TEMPLATE" => ".default",
		"DELETE_NOT_IN_KLADR_VALUES" => "Y",
		"DISTRICT_INPUT" => "Y",
		"HIDDEN_KLADR_ID" => "Y",
		"HIDDEN_LABEL" => "Y",
		"HIDDEN_LABEL_MIN" => "Y",
		"HIDDEN_Z_INDEX" => "Y",
		"INCLUDE_JQUERY" => "N",
		"INCLUDE_JQUERY_UI" => "Y",
		"INCLUDE_JQUERY_UI_THEME" => "Y",
		"KEY" => "1111",
		"LOCATION_INPUT" => "Y",
		"REGION_INPUT" => "Y",
		"STREET_INPUT" => "Y",
		"TOKEN" => "56f95beb0a69dec4488b45a9",
		"UPDATE_LABELS" => "Y",
		"USE_PAID_KLADR" => "N"
	)
);?><br>
<?$APPLICATION->IncludeComponent(
	"bitrix:main.calendar",
	"",
	Array(
		"COMPONENT_TEMPLATE" => ".default",
		"FORM_NAME" => "new_object",
		"HIDE_TIMEBAR" => "N",
		"INPUT_NAME" => "UF_ADD_DATE",
		"INPUT_NAME_FINISH" => "",
		"INPUT_VALUE" => "",
		"INPUT_VALUE_FINISH" => "",
		"SHOW_INPUT" => "Y",
		"SHOW_TIME" => "Y"
	)
);?><br>
<br><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>