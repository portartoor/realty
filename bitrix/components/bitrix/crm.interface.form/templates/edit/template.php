<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;
$APPLICATION->IncludeComponent(
	'bitrix:main.interface.form',
	'crm.edit',
	array(
		'FORM_ID' => $arParams['~FORM_ID'],
		'THEME_GRID_ID' => $arParams['~GRID_ID'],
		'TABS' => $arParams['~TABS'],
		'EMPHASIZED_HEADERS' => $arParams['~EMPHASIZED_HEADERS'],
		'FIELD_SETS' => isset($arParams['~FIELD_SETS']) ? $arParams['~FIELD_SETS'] : array(),
		'BUTTONS' => $arParams['~BUTTONS'],
		'DATA' => $arParams['~DATA'],
		'IS_NEW' => isset($arParams['~IS_NEW']) ? $arParams['~IS_NEW'] : 'Y',
		'SHOW_SETTINGS' => isset($arParams['~SHOW_SETTINGS']) ? $arParams['~SHOW_SETTINGS'] : 'Y'
	),
	$component, array('HIDE_ICONS' => 'Y')
);
?>