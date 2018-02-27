<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;
$APPLICATION->IncludeComponent(
	'bitrix:main.interface.form',
	'crm.view',
	array(
		'FORM_ID' => $arParams['~FORM_ID'],
		'THEME_GRID_ID' => $arParams['~GRID_ID'],
		'TABS' => $arParams['~TABS'],
		'TABS_EXT' => $arParams['~TABS_EXT'],
		'BUTTONS' => array('standard_buttons' =>  false),
		'DATA' => $arParams['~DATA'],
		'SIDEBAR_DATA' => $arParams['~SIDEBAR_DATA'],
		'FIELD_LIMIT' => isset($arParams['~FIELD_LIMIT']) ? $arParams['~FIELD_LIMIT'] : 5,
		'SHOW_SETTINGS' => isset($arParams['~SHOW_SETTINGS']) ? $arParams['~SHOW_SETTINGS'] : 'Y',
		'SHOW_FORM_TAG' => 'N'
	),
	$component, array('HIDE_ICONS' => 'Y')
);
?>