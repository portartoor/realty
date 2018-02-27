<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

//groups for bitrix24
if (isset($arResult['User']['GROUP_ID']))
{
	$arResult["GROUPS_LIST_HIDDEN"] = array();
	foreach($arResult['User']['GROUP_ID'] as $key => $group_id)
	{
		if($group_id != "1")
			$arResult["GROUPS_LIST_HIDDEN"][] = $group_id;

		$arResult["User"]["IS_ADMIN"] = in_array(1, $arResult['User']['GROUP_ID']) ? true : false;
	}
}

$arResult["IsMyProfile"] =  ($arResult["User"]["ID"] == $USER->GetID()) ? true: false;
$arResult["User"]["IS_EXTRANET"] = (
	empty($arResult["User"]['UF_DEPARTMENT'][0])
	&& \Bitrix\Main\Loader::includeModule('extranet')
	&& \Bitrix\Extranet\Util::checkExternalAuthId($arResult["User"]['EXTERNAL_AUTH_ID'])
		? true
		: false
);

$arResult['USER_PROP'] = array();

$arRes = $GLOBALS["USER_FIELD_MANAGER"]->GetUserFields("USER", 0, LANGUAGE_ID);
if (!empty($arRes))
{
	foreach ($arRes as $key => $val)
	{
		$arResult['USER_PROP'][$val["FIELD_NAME"]] = (strLen($val["EDIT_FORM_LABEL"]) > 0 ? htmlspecialcharsbx($val["EDIT_FORM_LABEL"]) : $val["FIELD_NAME"]);

		$val['ENTITY_VALUE_ID'] = $arResult['User']['ID'];
		
		$val['VALUE'] = $arResult['User']["~".$val['FIELD_NAME']];
		$arResult['USER_PROPERTY_ALL'][$val['FIELD_NAME']] = $val;
	}
}

$arPolicy = CUser::GetGroupPolicy($arResult['User']['ID']);
$arResult["User"]["PASSWORD_REQUIREMENTS"] = is_array($arPolicy) && isset($arPolicy["PASSWORD_REQUIREMENTS"]) ? $arPolicy["PASSWORD_REQUIREMENTS"] : "";
?>