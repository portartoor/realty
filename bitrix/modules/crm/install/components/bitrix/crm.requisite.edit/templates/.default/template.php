<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/** @var \CCrmRequisiteFormEditorComponent $component */

global $APPLICATION;

$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");

if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

CJSCore::Init(array('date', 'popup', 'ajax'));

$elementID = isset($arResult['ELEMENT_ID']) ? (int)$arResult['ELEMENT_ID'] : 0;
$enableFieldMasquerading = isset($arResult['ENABLE_FIELD_MASQUERADING']) && $arResult['ENABLE_FIELD_MASQUERADING'] === 'Y';
$fieldNameTemplate = isset($arResult['FIELD_NAME_TEMPLATE']) ? $arResult['FIELD_NAME_TEMPLATE'] : '';
if ($arResult['ENTITY_TYPE_MNEMO'] === 'COMPANY')
{
	$arResult['CRM_CUSTOM_PAGE_TITLE'] = GetMessage(($elementID > 0) ? 'CRM_REQUISITE_SHOW_TITLE_COMPANY' : 'CRM_REQUISITE_SHOW_NEW_TITLE_COMPANY');
}
else
{
	$arResult['CRM_CUSTOM_PAGE_TITLE'] = GetMessage(($elementID > 0) ? 'CRM_REQUISITE_SHOW_TITLE_CONTACT' : 'CRM_REQUISITE_SHOW_NEW_TITLE_CONTACT');
}

$row = array();
foreach ($arResult['ELEMENT'] as $fName => $fValue)
{
	if(is_array($fValue))
		$row[$fName] = htmlspecialcharsEx($fValue);
	elseif(preg_match("/[;&<>\"]/", $fValue))
		$row[$fName] = htmlspecialcharsEx($fValue);
	else
		$row[$fName] = $fValue;
	$row['~'.$fName] = $fValue;
}
$arResult['ELEMENT'] = &$row;
unset($row);

$arTabs = array();
$arTabs[] = array(
	'id' => 'tab_1',
	'name' => ($arResult['ENTITY_TYPE_MNEMO'] === 'COMPANY') ? GetMessage('CRM_TAB_1_COMPANY') : GetMessage('CRM_TAB_1_CONTACT'),
	'title' => ($arResult['ENTITY_TYPE_MNEMO'] === 'COMPANY') ? GetMessage('CRM_TAB_1_TITLE_COMPANY') : GetMessage('CRM_TAB_1_TITLE_CONTACT'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_1']
);

$canEditPreset = (isset($arResult['CAN_EDIT_PRESET']) && $arResult['CAN_EDIT_PRESET'] === 'Y');

$tabsMeta = array();
foreach($arTabs as $tab)
{
	$tabId = $tab['id'];
	$tabsMeta[$tabId] = array('id' => $tabId, 'name' => $tab['name'], 'title' => $tab['title']);
	foreach($tab['fields'] as $field)
	{
		$fieldInfo = array(
			'id' => $field['id'],
			'name' => $field['name'],
			'type' => $field['type']
		);

		if($enableFieldMasquerading)
		{
			$fieldInfo['rawId'] = isset($field['rawId']) ? $field['rawId'] : $field['id'];
		}

		if(isset($field['associatedField']))
		{
			$fieldInfo['associatedField'] = $field['associatedField'];
		}

		if(isset($field['required']))
		{
			$fieldInfo['required'] = $field['required'];
		}
		if(isset($field['persistent']))
		{
			$fieldInfo['persistent'] = $field['persistent'];
		}
		if(isset($field['isRQ']) && $field['isRQ'] === true && $canEditPreset)
		{
			$fieldInfo['isRQ'] = true;
		}
		if(isset($field['inShortList']) && $field['inShortList'] === true && $canEditPreset)
		{
			$fieldInfo['inShortList'] = true;
		}

		$tabsMeta[$tabId]['fields'][$field['id']] = $fieldInfo;
	}
}

$standardButtonsTitles = array();
if (!empty($arResult['REQUISITE_REFERER']))
	$standardButtonsTitles['saveAndView']['title'] = GetMessage('CRM_REQUISITE_CUSTOM_SAVE_BUTTON_TITLE');

$buttons = null;
if ($arResult['POPUP_MODE'] === 'N' && $arResult['INNER_FORM_MODE'] === 'N')
{
	$buttons = array(
		'standard_buttons' => true,
		'standard_buttons_titles' => $standardButtonsTitles
	);
}

if($arResult['INNER_FORM_MODE'] === 'Y')
{
	?><div class="crm-offer-requisite-form-wrap"><?
}


if($arResult['INNER_FORM_MODE'] == 'Y')
{
	$tactileSettings = array('ENABLE_FIELD_DRAG' => 'N', 'ENABLE_SECTION_DRAG' => 'N');
}
else
{
	$tactileSettings = array('DRAG_PRIORITY' => 50, 'ENABLE_SECTION_DRAG' => 'N');
}

$formId = $arResult['FORM_ID'];
$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.form',
	'edit',
	array(
		'FORM_ID' => $formId,
		'GRID_ID' => $arResult['GRID_ID'],
		'TABS' => $arTabs,
		'TABS_META' => $tabsMeta,
		'AVAILABLE_FIELDS' => $arResult['AVAILABLE_FIELDS'],
		'USER_FIELD_ENTITY_ID' => isset($arResult['USER_FIELD_ENTITY_ID']) ? $arResult['USER_FIELD_ENTITY_ID'] : '',
		'USER_FIELD_SERVICE_URL' => '/bitrix/components/bitrix/crm.requisite.edit/uf.ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
		'BUTTONS' => $buttons,
		'IS_NEW' => $elementID <= 0,
		'TITLE' => (($arResult['POPUP_MODE'] === 'Y' || $arResult['INNER_FORM_MODE'] == 'Y') ? '' : $arResult['CRM_CUSTOM_PAGE_TITLE']),
		'ENABLE_TACTILE_INTERFACE' => 'Y',
		'TACTILE_INTERFACE_SETTINGS' => $tactileSettings,
		'DATA' => $arResult['ELEMENT'],
		'SHOW_SETTINGS' => 'Y',
		'SHOW_FORM_TAG' => $arResult['INNER_FORM_MODE'] === 'N' ? 'Y' : 'N',
		'ENABLE_SECTION_CREATION' => 'N',
		'ENABLE_SECTION_EDIT' => $arResult['POPUP_MODE'] === 'N' ? 'Y' : 'N',
		'ENABLE_SECTION_DELETE' => 'N',
		'CUSTOM_FORM_SETTINGS_COMPONENT_PATH'=> $component->getRelativePath(),
		'ENABLE_IN_SHORT_LIST_OPTION' => 'Y',
		'IS_MODAL' => $arResult['POPUP_MODE'],
		'PREFIX' => $arResult['PREFIX'],
	)
);

if($arResult['INNER_FORM_MODE'] === 'Y')
{
	?></div><?
}
?><script type="text/javascript">
	BX.ready(function()
	{
		BX.Crm.ExternalRequisiteDialog.messages =
		{
			searchResultNotFound: "<?=GetMessageJS('CRM_REQUISITE_SERCH_RESULT_NOT_FOUND')?>"
		};

		var formId = "<?=CUtil::JSEscape($formId)?>";
		var containerId = "container_" + formId.toLowerCase();
		BX.onCustomEvent(window,
			"CrmRequisiteEditFormCreate",
			[
				{
					formId: formId,
					containerId: containerId,
					elementId: <?=$elementID?>,
					countryId: <?=CUtil::JSEscape($arResult['COUNTRY_ID'])?>,
					enableClientResolution: <?=$arResult['ENABLE_CLIENT_RESOLUTION'] ? 'true' : 'false'?>,
					enableFieldMasquerading: <?=$enableFieldMasquerading ? 'true' : 'false'?>,
					fieldNameTemplate: "<?=CUtil::JSEscape($fieldNameTemplate)?>"
				}
			]
		);
	});
</script>
