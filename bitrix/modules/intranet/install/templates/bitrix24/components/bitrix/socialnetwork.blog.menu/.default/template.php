<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

if ($arResult["showAll"] == "Y")
{
	$menuItems = array();

	if($arResult["show4MeAll"] == "Y" || $arResult["showAll"] == "Y")
	{
		$menuItems[] = array(
			"TEXT" => GetMessage("BLOG_MENU_4ME_ALL"),
			"TITLE" => GetMessage("BLOG_MENU_4ME_ALL_TITLE"),
			"URL" => $arResult["PATH_TO_4ME_ALL"],
			"ID" => "view_4me_all",
			"IS_ACTIVE" => $arResult["page"] == "all",
		);
	}
	if(strlen($arResult["PATH_TO_MINE"]) > 0)
	{
		$menuItems[] = array(
			"TEXT" => GetMessage("BLOG_MENU_MINE"),
			"TITLE" => GetMessage("BLOG_MENU_MINE_TITLE"),
			"URL" => $arResult["PATH_TO_MINE"],
			"ID" => "view_mine",
			"IS_ACTIVE" => $arResult["page"] == "mine",
		);
	}
	if($arResult["show4Me"] == "Y")
	{
		$menuItems[] = array(
			"TEXT" => GetMessage("BLOG_MENU_4ME"),
			"TITLE" => GetMessage("BLOG_MENU_4ME_TITLE"),
			"URL" => $arResult["PATH_TO_4ME"],
			"ID" => "view_for_me",
			"IS_ACTIVE" => $arResult["page"] == "forme",
		);
	}
	if (strlen($arResult["urlToDraft"]) > 0 && IntVal($arResult["CntToDraft"]) > 0)
	{
		$menuItems[] = array(
			"TEXT" => GetMessage("BLOG_MENU_DRAFT_MESSAGES"),
			"URL" => $arResult["urlToDraft"],
			"ID" => "view_draft",
			"IS_ACTIVE" => $arResult["page"] == "draft",
		);
	}
	if (strlen($arResult["urlToModeration"]) > 0 && IntVal($arResult["CntToModerate"]) > 0)
	{
		$menuItems[] = array(
			"TEXT" => GetMessage("BLOG_MENU_MODERATION_MESSAGES"),
			"URL" => $arResult["urlToModeration"],
			"ID" => "view_moderation",
			"IS_ACTIVE" => $arResult["page"] == "moderation",
		);
	}
	if (strlen($arResult["urlToTags"]) > 0 && IntVal($arResult["CntTags"]) > 0)
	{
		$menuItems[] = array(
			"TEXT" => GetMessage("BLOG_MENU_TAGS"),
			"URL" => $arResult["urlToTags"],
			"ID" => "view_tags",
			"IS_ACTIVE" => $arResult["page"] == "tags",
		);
	}

	$this->SetViewTarget("above_pagetitle", 50);

	$menuId = "blog_messages_panel_menu";
	$APPLICATION->IncludeComponent(
		"bitrix:main.interface.buttons",
		"",
		array(
			"ID" => $menuId,
			"ITEMS" => $menuItems,
		)
	);

	$this->EndViewTarget();
}