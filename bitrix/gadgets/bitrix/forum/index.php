<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$arGadgetParams["SORT_BY"] = ($arGadgetParams["SORT_BY"] ? $arGadgetParams["SORT_BY"] : "LAST_POST_DATE");
$arGadgetParams["SORT_ORDER"] = ($arGadgetParams["SORT_ORDER"] ? $arGadgetParams["SORT_ORDER"] : "DESC");
$arGadgetParams["URL_TEMPLATES_MESSAGE"] = ($arGadgetParams["URL_TEMPLATES_MESSAGE"] ? $arGadgetParams["URL_TEMPLATES_MESSAGE"] : "/community/forum/messages/forum#FID#/topic#TID#/message#MID#/");
$arGadgetParams["CACHE_TYPE"] = ($arGadgetParams["CACHE_TYPE"] ? $arGadgetParams["CACHE_TYPE"] : "A");
$arGadgetParams["CACHE_TIME"] = ($arGadgetParams["CACHE_TIME"] ? $arGadgetParams["CACHE_TIME"] : "180");

$arGadgetParams["TOPICS_PER_PAGE"] = ($arGadgetParams["TOPICS_PER_PAGE"] ? $arGadgetParams["TOPICS_PER_PAGE"] : 6);
$arGadgetParams["DATE_TIME_FORMAT"] = ($arGadgetParams["DATE_TIME_FORMAT"] ? $arGadgetParams["DATE_TIME_FORMAT"] : $arParams["DATE_TIME_FORMAT"]);
?>
<?$APPLICATION->IncludeComponent(
	"bitrix:forum.topic.last",
	"main_page",
	Array(
		"FID" => $arGadgetParams["FID"],
		"SORT_BY" => $arGadgetParams["SORT_BY"],
		"SORT_ORDER" => $arGadgetParams["SORT_ORDER"],
		"URL_TEMPLATES_MESSAGE" => $arGadgetParams["URL_TEMPLATES_MESSAGE"],
		"CACHE_TYPE" => $arGadgetParams["CACHE_TYPE"],
		"CACHE_TIME" => $arGadgetParams["CACHE_TIME"],
		"TOPICS_PER_PAGE" => $arGadgetParams["TOPICS_PER_PAGE"],
		"DATE_TIME_FORMAT" => $arGadgetParams["DATE_TIME_FORMAT"],
		"SHOW_FORUM_ANOTHER_SITE" => "N",
		"SET_NAVIGATION" => "N",
		"DISPLAY_PANEL" => "N",
		"SET_TITLE" => "N",
		"PAGER_DESC_NUMBERING" => "N",
		"PAGER_SHOW_ALWAYS" => "N",
		"PAGER_TITLE" => " ",
		"PAGER_TEMPLATE" => "",
		"SHOW_NAV" => array(),
		"SHOW_COLUMNS" => array(),
		"SHOW_SORTING" => "N",
	),
	false,
	Array("HIDE_ICONS"=>"Y")
);?>
