<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/rest/install/public/marketplace/.left.menu_ext.php");

$arMenu = array();

if(SITE_TEMPLATE_ID == 'bitrix24' || \Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24'))
{
	$arMenu[] = Array(
		GetMessage("MENU_MARKETPLACE_ALL"),
		"/marketplace/",
		Array(),
		Array("menu_item_id" => "menu_marketplace"),
		""
	);
}

if(CModule::IncludeModule("rest"))
{
	if(\CRestUtil::isAdmin())
	{
		$arMenu[] = Array(
			GetMessage("MENU_MARKETPLACE_ADD"),
			"/marketplace/local/",
			Array(),
			Array("menu_item_id" => "menu_marketplace_add"),
			""
		);
	}

	$arUserGroupCode = $USER->GetAccessCodes();
	$numLocalApps = 0;

	$arMenuApps = array();
	$dbApps = \Bitrix\Rest\AppTable::getList(array(
		'order' => array("ID" => "ASC"),
		'filter' => array("=ACTIVE" => \Bitrix\Rest\AppTable::ACTIVE),
		'select' => array(
			'ID', 'STATUS', 'ACCESS', 'MENU_NAME' => 'LANG.MENU_NAME', 'MENU_NAME_DEFAULT' => 'LANG_DEFAULT.MENU_NAME'
		)
	));
	while($arApp = $dbApps->fetch())
	{
		if($arApp["STATUS"] == \Bitrix\Rest\AppTable::STATUS_LOCAL)
		{
			$numLocalApps++;
		}

		$lang = in_array(LANGUAGE_ID, array("ru", "en", "de")) ? LANGUAGE_ID : LangSubst(LANGUAGE_ID);
		if(strlen($arApp["MENU_NAME"]) > 0 || strlen($arApp['MENU_NAME_DEFAULT']) > 0)
		{
			$appRightAvailable = false;
			if(\CRestUtil::isAdmin())
			{
				$appRightAvailable = true;
			}
			elseif(!empty($arApp["ACCESS"]))
			{
				$rights = explode(",", $arApp["ACCESS"]);
				foreach($rights as $rightID)
				{
					if(in_array($rightID, $arUserGroupCode))
					{
						$appRightAvailable = true;
						break;
					}
				}
			}
			else
			{
				$appRightAvailable = true;
			}

			if($appRightAvailable)
			{
				$appName = $arApp["MENU_NAME"];

				if(strlen($appName) <= 0)
				{
					$appName = $arApp['MENU_NAME_DEFAULT'];
				}

				$arMenuApps[] = Array(
					htmlspecialcharsbx($appName),
					"/marketplace/app/".$arApp["ID"]."/",
					Array(),
					Array("is_application" => "Y", "app_id" => $arApp["ID"]),
					""
				);
			}
		}
	}
	if(\CRestUtil::isAdmin() && $numLocalApps > 0)
	{
		$arMenu[] = Array(
			GetMessage("MENU_MARKETPLACE_LOCAL"),
			"/marketplace/local/list/",
			Array(),
			Array("menu_item_id" => "menu_marketplace_local"),
			""
		);
	}

	$arMenu[] = Array(
		GetMessage("MENU_MARKETPLACE_HOOK"),
		"/marketplace/hook/",
		Array(),
		Array("menu_item_id" => "menu_marketplace_hook"),
		""
	);

	$arMenu = array_merge($arMenu, $arMenuApps);
}


$aMenuLinks = array_merge($arMenu, $aMenuLinks);