<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
Loc::loadMessages(__FILE__);

$actionList = array(
	'SYSTEM' => array(
		array(
			'popup' => true,
			'id' => 'edit',
			'text' => Loc::getMessage('CRM_WEBFORM_LIST_ACTIONS_VIEW'),
			'url_template' => $arParams['PATH_TO_WEB_FORM_EDIT'],
			'url_replace' => $arParams['PATH_TO_WEB_FORM_EDIT'],
		),
	),
	'USER' => array(
		array(
			'popup' => true,
			'id' => 'edit',
			'text' => $arResult['PERM_CAN_EDIT'] ? Loc::getMessage('CRM_WEBFORM_LIST_ACTIONS_EDIT') : Loc::getMessage('CRM_WEBFORM_LIST_ACTIONS_VIEW'),
			'url_template' => $arParams['PATH_TO_WEB_FORM_EDIT'],
			'url_replace' => $arParams['PATH_TO_WEB_FORM_EDIT'],
		),
	)
);

if($arResult['PERM_CAN_EDIT'])
{
	$actionCopyAs = array(
		'popup' => true,
		'id' => 'copy',
		'text' => Loc::getMessage('CRM_WEBFORM_LIST_ACTIONS_COPY'),
		'url' => $arParams['PATH_TO_WEB_FORM_EDIT']
	);
	$actionResetCounters = array(
		'popup' => true,
		'id' => 'reset_counters',
		'text' => Loc::getMessage('CRM_WEBFORM_LIST_ACTIONS_RESET_COUNTERS1'),
	);
	$actionList['SYSTEM'][] = $actionCopyAs;
	$actionList['SYSTEM'][] = $actionResetCounters;
	$actionList['USER'][] = $actionCopyAs;
	$actionList['USER'][] = $actionResetCounters;
}

$viewList = array(
	'CONVERSION' => array(
		'NAME' => Loc::getMessage('CRM_WEBFORM_LIST_VIEWS_CONVERSION_MENU'),
		'TEXT' => Loc::getMessage('CRM_WEBFORM_LIST_VIEWS_CONVERSION'),
		'CLASS_NAME' => ''
	),
	'NUMBER' => array(
		'NAME' => Loc::getMessage('CRM_WEBFORM_LIST_VIEWS_NUMBER_MENU'),
		'TEXT' => Loc::getMessage('CRM_WEBFORM_LIST_VIEWS_NUMBER'),
		'CLASS_NAME' => 'crm-webform-list-widget-orange-color'
	),
	'PRICE' => array(
		'NAME' => Loc::getMessage('CRM_WEBFORM_LIST_VIEWS_PRICE_MENU'),
		'TEXT' => Loc::getMessage('CRM_WEBFORM_LIST_VIEWS_PRICE'),
		'CLASS_NAME' => 'crm-webform-list-widget-green-color'
	)
);
$viewTypeList = array_keys($viewList);
$userOptionViewType = 'webform_list_view';
$userViewTypes = \CUserOptions::GetOption('crm', $userOptionViewType, array());

$debugVarOneItemAsSystemInited = false;
$arResult['ITEMS_BY_IS_SYSTEM'] = array(
	'N' => array(
		'NAME' => Loc::getMessage('CRM_WEBFORM_LIST_FORMS_MINE'),
		'ITEMS' => array()
	),
	'Y' => array(
		'NAME' => Loc::getMessage('CRM_WEBFORM_LIST_FORMS_PRESET'),
		'ITEMS' => array()
	)
);
foreach($arResult['ITEMS'] as $item)
{
	$item['IS_SYSTEM'] = $item['IS_SYSTEM'] == 'Y' ? 'Y' : 'N';

	$viewClassName = '';
	$itemViewList = $viewList;
	$item['VIEW_TYPE'] = isset($userViewTypes[$item['ID']]) ? $userViewTypes[$item['ID']] : null;
	$item['VIEW_TYPE'] = in_array($item['VIEW_TYPE'], $viewTypeList) ? $item['VIEW_TYPE'] : $viewTypeList[0];
	foreach($itemViewList as $viewType => $view)
	{
		$itemViewList[$viewType]['VALUE'] = $item['SUMMARY_CONVERSION_' . $viewType];
		$itemViewList[$viewType]['SELECTED'] = $item['VIEW_TYPE'] == $viewType;
		if($viewType != $item['VIEW_TYPE'])
		{
			continue;
		}
		$itemViewList[$viewType]['SELECTED'] = true;
		$viewClassName = $view['CLASS_NAME'];
	}

	$item['viewClassName'] = $viewClassName;
	$item['itemViewList'] = $itemViewList;

	$item['ENTITY_COUNTERS_DISPLAY'] = array();
	foreach($item['ENTITY_COUNTERS'] as $entityCounter)
	{
		$entityListPath = \Bitrix\Main\Config\Option::get(
			'crm',
			'path_to_' . strtolower($entityCounter['ENTITY_NAME']) . '_list',
			''
		);
		$entityCountDisplay = (int) $entityCounter['VALUE'];
		if ($entityListPath && $entityCountDisplay > 0)
		{
			if ($entityCounter['ENTITY_NAME'] != 'INVOICE')
			{
				$entityListPath .= strpos($entityListPath, '?') === false ? '?' : '&';
				$entityListPath .= 'WEBFORM_ID[]=' . $item['ID'] . '&apply_filter=Y';
			}

			$entityListPath =  htmlspecialcharsbx($entityListPath);
			$entityCountDisplay = '<a href="' . $entityListPath . '">' . $entityCountDisplay . '</a>';
		}
		$item['ENTITY_COUNTERS_DISPLAY'][] = htmlspecialcharsbx($entityCounter['ENTITY_CAPTION'])
			. ' - '
			. $entityCountDisplay;
	}
	$item['ENTITY_COUNTERS_DISPLAY'] = implode(' / ', $item['ENTITY_COUNTERS_DISPLAY']);

	$arResult['ITEMS_BY_IS_SYSTEM'][$item['IS_SYSTEM']]['ITEMS'][] = $item;
}
if(count($arResult['ITEMS_BY_IS_SYSTEM']['N']['ITEMS']) == 0)
{
	unset($arResult['ITEMS_BY_IS_SYSTEM']['N']);
}
if(count($arResult['ITEMS_BY_IS_SYSTEM']['Y']['ITEMS']) == 0)
{
	unset($arResult['ITEMS_BY_IS_SYSTEM']['Y']);
}

$arResult['HIDE_DESC'] = isset($userViewTypes['hide-desc']) && $userViewTypes['hide-desc'] == 'Y';

$arResult['actionList'] = $actionList;
$arResult['viewList'] = $viewList;
$arResult['userOptionViewType'] = $userOptionViewType;