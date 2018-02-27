<?php if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use \Bitrix\Crm\Kanban\Helper;

\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_grid.js');

$filter = Helper::getFilter($arParams['ENTITY_TYPE']);
$presets = Helper::getPresets($arParams['ENTITY_TYPE']);
$grid = Helper::getGrid($arParams['ENTITY_TYPE']);
$gridId = Helper::getGridId($arParams['ENTITY_TYPE']);
$gridFilter = (array)$grid->GetFilter($filter);

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.filter',
	'title',
	array(
		'GRID_ID' => $gridId,
		'FILTER_ID' => $gridId,
		'FILTER' => $filter,
		'FILTER_FIELDS' => $gridFilter,
		'FILTER_PRESETS' => $presets,
		'ENABLE_LIVE_SEARCH' => true,
		'NAVIGATION_BAR' => $arParams['NAVIGATION_BAR']
	),
	$component,
	array('HIDE_ICONS' => true)
);