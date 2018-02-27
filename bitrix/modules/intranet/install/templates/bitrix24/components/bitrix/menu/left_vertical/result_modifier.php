<?
use Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

Loc::loadMessages(__FILE__);

$APPLICATION->SetAdditionalCSS($this->getFolder()."/map.css");
$APPLICATION->SetAdditionalCSS($this->getFolder()."/groups.css");
$APPLICATION->AddHeadScript($this->getFolder()."/map.js");

if (!function_exists("recalculateCrmCounter"))
{
	function recalculateCrmCounter($counterId)
	{
		if (preg_match("~^crm_~i", $counterId) && \Bitrix\Main\Loader::includeModule("crm"))
		{
			\Bitrix\Crm\Counter\EntityCounterManager::prepareValue($counterId);
		}
	}
}

/*
 * CUserOptions::GetOption("intranet", "left_menu_self_items_s1") - array of self-added items (contains TEXT, LINK, ID)
 * CUserOptions::GetOption("intranet", "left_menu_sorted_items_s1") - 2-dimensional array of items id (show/hide)
 *
 * Delete permissions:
 * 	N - can't delete;
 * 	Y - can delete;
 * 	A - only admin can delete
 */
$defaultItems = $arResult;
$arResult = array();

$arResult["IS_ADMIN"] = "N";
if (
	IsModuleInstalled("bitrix24")
	&& $GLOBALS['USER']->CanDoOperation('bitrix24_config')
	|| !IsModuleInstalled("bitrix24")
	&& $GLOBALS['USER']->IsAdmin()
)
{
	$arResult["IS_ADMIN"] = "Y";
}

$arResult["IS_EXTRANET"] = isModuleInstalled("extranet") && SITE_ID == COption::GetOptionString("extranet", "extranet_site");

$arResult["SHOW_PRESET_POPUP"] = COption::GetOptionString("intranet", "show_menu_preset_popup", "N") == "Y";
COption::SetOptionString("intranet", "show_menu_preset_popup", "N");

$newItems = array();
$mapItems = array();
$myToolsItems = array();

//default items
foreach ($defaultItems as $itemIndex => $item)
{
	if (!isset($item["PARAMS"]) || !is_array($item["PARAMS"]))
	{
		$item["PARAMS"] = array();
	}

	//id to item
	if (!isset($item["PARAMS"]["menu_item_id"]))
	{
		$item["PARAMS"]["menu_item_id"] = ($item["PARAMS"]["name"] == "live_feed") ? "menu_live_feed" : crc32($item["LINK"]);
	}

	if (isset($item["PARAMS"]["my_tools_section"]) && $item["PARAMS"]["my_tools_section"] === true)
	{
		$myToolsItems[] = $item;
		$myToolsItems[count($myToolsItems) - 1]["DEPTH_LEVEL"] = 2;

		//Skip empty root items
		if (
			$item["DEPTH_LEVEL"] !== 1 ||
			!isset($defaultItems[$itemIndex + 1]) ||
			$defaultItems[$itemIndex + 1]["DEPTH_LEVEL"] !== 1)
		{
			$mapItems[] = $item;
		}
	}
	else
	{
		$mapItems[] = $item;
	}

	if ($item["DEPTH_LEVEL"] !== 1 || (isset($item["PARAMS"]["hidden"]) && $item["PARAMS"]["hidden"] === true))
	{
		continue;
	}

	$item["ITEM_TYPE"] = "default";
	$item["DELETE_PERM"] = "N";

	if (isset($item["PARAMS"]["counter_id"]))
	{
		recalculateCrmCounter($item["PARAMS"]["counter_id"]);
	}

	$newItems[$item["PARAMS"]["menu_item_id"]] = $item;
}

if (!empty($myToolsItems))
{
	array_unshift($myToolsItems, array(
		"TEXT" => Loc::getMessage("MENU_MY_WORKSPACE"),
		"LINK" => SITE_DIR,
		"SELECTED" => false,
		"PERMISSION" => "X",
		"PARAMS" => array(
			"menu_item_item" => "my_instruments"
		),
		"DEPTH_LEVEL" => 1,
		"IS_PARENT" => true,
		"ADDITIONAL_LINKS" => array()
	));
}

$arResult["MAP_ITEMS"] = $arResult["IS_EXTRANET"] ? array() : array_merge($myToolsItems, $mapItems);

if (CUserOptions::GetOption("intranet", "left_menu_converted_".SITE_ID, "N") !== "Y")
{
	include($_SERVER["DOCUMENT_ROOT"].$this->GetFolder()."/convertor.php");
}

if (!$arResult["IS_EXTRANET"] && CUserOptions::GetOption("intranet", "left_menu_group_converted_".SITE_ID, "N") !== "Y")
{
	include($_SERVER["DOCUMENT_ROOT"].$this->GetFolder()."/group_converter.php");
}

//user standard items
$standardItems = CUserOptions::GetOption("intranet", "left_menu_standard_items_".SITE_ID);
if (is_array($standardItems) && !empty($standardItems))
{
	foreach ($standardItems as $item)
	{
		$counterId = isset($item["COUNTER_ID"]) ? $item["COUNTER_ID"] : "";
		$newItems[$item["ID"]] = array(
			"TEXT" => htmlspecialcharsbx($item["TEXT"]),
			"LINK" => htmlspecialcharsbx($item["LINK"]),
			"PERMISSION" => "X",
			"PARAMS" => array(
				"menu_item_id" => $item["ID"],
				"counter_id" => $counterId
			),
			"ITEM_TYPE" => "standard",
			"DELETE_PERM" => "Y"
		);

		if (isset($item["SUB_LINK"]) && !empty($item["SUB_LINK"]))
		{
			$newItems[$item["ID"]]["PARAMS"]["sub_link"] = $item["SUB_LINK"];
		}

		recalculateCrmCounter($counterId);
	}
}

//user self-added items
$userItems = CUserOptions::GetOption("intranet", "left_menu_self_items_".SITE_ID);
if (is_array($userItems) && !empty($userItems))
{
	foreach ($userItems as $item)
	{
		$newItems[$item["ID"]] = array(
			"TEXT" => htmlspecialcharsbx($item["TEXT"]),
			"LINK" => htmlspecialcharsbx($item["LINK"]),
			"PERMISSION" => "X",
			"PARAMS" => array(
				"menu_item_id" => $item["ID"]
			),
			"ITEM_TYPE" => "self",
			"DELETE_PERM" => "Y",
			"OPEN_IN_NEW_PAGE" => isset($item["NEW_PAGE"]) && $item["NEW_PAGE"] == "Y" ? true : false
		);
	}
}

//admin items to all
$adminItems = COption::GetOptionString("intranet", "left_menu_items_to_all_".SITE_ID);
if (!empty($adminItems))
{
	$adminItems = unserialize($adminItems);
	foreach ($adminItems as $item)
	{
		$counterId = isset($item["COUNTER_ID"]) ? $item["COUNTER_ID"] : "";
		$newItems[$item["ID"]] = array(
			"TEXT" => htmlspecialcharsbx($item["TEXT"]),
			"LINK" => htmlspecialcharsbx($item["LINK"]),
			"PERMISSION" => "X",
			"PARAMS" => array(
				"menu_item_id" => $item["ID"],
				"counter_id" => $counterId
			),
			"ITEM_TYPE" => ($arResult["IS_ADMIN"] && isset($newItems[$item["ID"]])) ? $newItems[$item["ID"]]["ITEM_TYPE"] : "admin",
			"DELETE_PERM" => "A",
			"OPEN_IN_NEW_PAGE" => isset($item["NEW_PAGE"]) && $item["NEW_PAGE"] == "Y" ? true : false
		);

		recalculateCrmCounter($counterId);
	}
}

//sorted items
$sortedItems = array();
$arResult["ITEMS"]["show"] = array();

$presets = array(
	"social" => array(
		"show" => array(
			"menu_live_feed",
			"menu_tasks",
			"menu_im_messenger",
			"menu_all_groups",
			"menu_calendar",
			"menu_files",
			"menu_external_mail",
			"menu_crm_favorite",
			"menu_company",
			"menu_timeman_sect",
			"menu_marketplace_sect",
		),
		"hide" => array(
			"menu_openlines",
			"menu_telephony",
			"menu_bizproc_sect",
			"menu_tariff",
			"menu_configs_sect",
		)
	),
	"crm" => array(
		"show" => array(
			"menu_crm_favorite",
			"menu_tasks",
			"menu_calendar",
			"menu_live_feed",
			"menu_im_messenger",
			"menu_files",
			"menu_company",
			"menu_timeman_sect",
			"menu_marketplace_sect",
			"menu_openlines",
		),
		"hide" => array(
			"menu_all_groups",
			"menu_external_mail",
			"menu_telephony",
			"menu_bizproc_sect",
			"menu_tariff",
			"menu_configs_sect",
		)
	),
	"tasks" => array(
		"show" => array(
			"menu_tasks",
			"menu_all_groups",
			"menu_live_feed",
			"menu_im_messenger",
			"menu_calendar",
			"menu_files",
			"menu_crm_favorite",
			"menu_company",
			"menu_timeman_sect",
			"menu_marketplace_sect",
		),
		"hide" => array(
			"menu_external_mail",
			"menu_openlines",
			"menu_telephony",
			"menu_bizproc_sect",
			"menu_tariff",
			"menu_configs_sect",
		)
	)
);

$arResult["CURRENT_PRESET_ID"] = "social";
$sortedItemsId = CUserOptions::GetOption("intranet", "left_menu_sorted_items_".SITE_ID);
if (!is_array($sortedItemsId) || empty($sortedItemsId))
{
	//personal
	$presetId = CUserOptions::GetOption("intranet", "left_menu_preset");

	//global
	if (!in_array($presetId, array("social", "tasks", "crm")))
	{
		$presetId = COption::GetOptionString("intranet", "left_menu_preset", "");
	}

	if (in_array($presetId, array("social", "tasks", "crm")))
	{
		$arResult["CURRENT_PRESET_ID"] = $presetId;
		$sortedItemsId = $presets[$presetId];
	}
	else
	{
		$sortedItemsId = $presets["social"];
	}
}

foreach (array("show", "hide") as $status)
{
	if (!(isset($sortedItemsId[$status]) && is_array($sortedItemsId[$status]) && !empty($sortedItemsId[$status])))
		continue;

	foreach ($sortedItemsId[$status] as $itemId)
	{
		if (!isset($newItems[$itemId]))
			continue;

		$arResult["ITEMS"][$status][] = $newItems[$itemId];
		unset($newItems[$itemId]);
	}
}

//add unsorted items to the end
if (!empty($newItems))
{
	$arResult["ITEMS"]["show"] = array_merge($arResult["ITEMS"]["show"], $newItems);
}

$arResult["COUNTERS"] = \CUserCounter::GetValues($USER->GetID(), SITE_ID);

$arResult["GROUPS"] = array();
if (!$arResult["IS_EXTRANET"] && $GLOBALS["USER"]->isAuthorized())
{
	$userId = $GLOBALS["USER"]->getId();
	$cacheTtl = defined("BX_COMP_MANAGED_CACHE") ? 2592000 : 600;
	$cacheId = "bitrix24_group_list_".$userId."_".isModuleInstalled("extranet");
	$cacheDir = "/bx/bitrix24_group_list/".$userId;
	$cache = new CPHPCache;

	if ($cache->initCache($cacheTtl, $cacheId, $cacheDir))
	{
		$arResult["GROUPS"] = $cache->getVars();
	}
	else
	{
		$cache->startDataCache();

		if (defined("BX_COMP_MANAGED_CACHE"))
		{
			$GLOBALS["CACHE_MANAGER"]->startTagCache($cacheDir);
			$GLOBALS["CACHE_MANAGER"]->registerTag("sonet_user2group_U".$userId);
			$GLOBALS["CACHE_MANAGER"]->registerTag("sonet_group");
			$GLOBALS["CACHE_MANAGER"]->registerTag("sonet_group_favorites_U".$userId);
			$GLOBALS["CACHE_MANAGER"]->endTagCache();
		}

		$arResult["GROUPS"] = include("groups.php");
		$cache->endDataCache($arResult["GROUPS"]);
	}
}

$arResult["IS_PUBLIC_CONVERTED"] = file_exists($_SERVER["DOCUMENT_ROOT"].SITE_DIR."stream/");
