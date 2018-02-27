<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

global $APPLICATION;

$APPLICATION->IncludeComponent("bitrix:main.site.selector", "menu", Array(
	"SITE_LIST" => array(	// Список сайтов
		0 => "*all*",
	),
	"CACHE_TYPE" => "A",	// Тип кеширования
	"CACHE_TIME" => "86400",	// Время кеширования (сек.)
	),
	false,
	Array("HIDE_ICONS" => "Y")
);

$aMenuLinks = array_merge($GLOBALS["arMenuSites"], $aMenuLinks);

?>
