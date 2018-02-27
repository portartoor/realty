<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

global $APPLICATION;

$APPLICATION->IncludeComponent("bitrix:main.site.selector", "menu", Array(
	"SITE_LIST" => array(	// РЎРїРёСЃРѕРє СЃР°Р№С‚РѕРІ
		0 => "*all*",
	),
	"CACHE_TYPE" => "A",	// РўРёРї РєРµС€РёСЂРѕРІР°РЅРёСЏ
	"CACHE_TIME" => "86400",	// Р’СЂРµРјСЏ РєРµС€РёСЂРѕРІР°РЅРёСЏ (СЃРµРє.)
	),
	false,
	Array("HIDE_ICONS" => "Y")
);

$aMenuLinks = array_merge($GLOBALS["arMenuSites"], $aMenuLinks);

?>
