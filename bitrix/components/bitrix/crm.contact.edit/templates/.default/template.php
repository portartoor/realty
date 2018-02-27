<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

$arTabs = array();
$arTabs[] = array(
	'id' => 'tab_1',
	'name' => GetMessage('CRM_TAB_1'),
	'title' => GetMessage('CRM_TAB_1_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_1']
);

// Form options housekeeping
CCrmComponentHelper::SynchronizeFormSettings($arResult['FORM_ID'], CCrmContact::GetUserFieldEntityID());

$elementID = isset($arResult['ELEMENT']['ID']) ? $arResult['ELEMENT']['ID'] : 0;
$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.form',
	'edit',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'GRID_ID' => $arResult['GRID_ID'],
		'TABS' => $arTabs,
		'EMPHASIZED_HEADERS' => array('NAME', 'SECOND_NAME', 'LAST_NAME'),
		'BUTTONS' => array(
			'standard_buttons' => true,
			'back_url' => $arResult['BACK_URL'],
			'custom_html' => '<input type="hidden" name="contact_id" value="'.$elementID.'"/>'
		),
		'IS_NEW' => $elementID <= 0,
		'DATA' => $arResult['ELEMENT'],
		'SHOW_SETTINGS' => 'Y'
	)
);

$crmEmail = strtolower(COption::GetOptionString('crm', 'mail', ''));
if ($arResult['ELEMENT']['ID'] == 0 && $crmEmail != ''):
?><div class="crm_notice_message"><?=GetMessage('CRM_IMPORT_SNS', Array('%EMAIL%' => $crmEmail, '%ARROW%' => '<span class="crm_notice_arrow"></span>'));?></div><?
endif;
?>