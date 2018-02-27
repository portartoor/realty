<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
if (!empty($arResult['BUTTONS']))
{
	$type = $arParams['TYPE'];
	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		$type === 'list' ?  '' : 'type2',
		array('BUTTONS' => $arResult['BUTTONS']),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
}
