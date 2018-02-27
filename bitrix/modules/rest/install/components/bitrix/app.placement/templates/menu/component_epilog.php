<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @global CMain $APPLICATION
 */
\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

if(!function_exists('restMenuBuildEventHandler'))
{
	function restMenuBuildEventHandler($placement, $eventParam, &$menu)
	{
		$appList = \Bitrix\Rest\HandlerHelper::getApplicationList($placement);
		if(count($appList) > 0)
		{
			$placementParam = array(
				'ID' => intval($eventParam['ID']),
			);

			$appMenu = array();
			foreach($appList as $app)
			{
				$itemText = strlen($app['TITLE']) > 0
					? $app['TITLE']
					: $app['APP_NAME'];

				$appMenu[] = array(
					'TITLE' => $app['APP_NAME'],
					'TEXT' => $itemText,
					'ONCLICK' => "BX.rest.AppLayout.getPlacement('".\CUtil::JSEscape($placement)."').load('".intval($app['ID'])."', ".\CUtil::PhpToJSObject($placementParam).");"
				);
			}

			$menu[] = array(
				'TITLE' => \Bitrix\Main\Localization\Loc::getMessage('REST_AP_MENU_ITEM_TITLE'),
				'TEXT' => \Bitrix\Main\Localization\Loc::getMessage('REST_AP_MENU_ITEM_TEXT'),
				'MENU' => $appMenu,
			);
		}
	}
}

AddEventHandler($arParams['MENU_EVENT_MODULE'], $arParams['MENU_EVENT'], 'restMenuBuildEventHandler');