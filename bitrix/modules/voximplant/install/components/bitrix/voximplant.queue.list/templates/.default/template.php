<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();


$APPLICATION->IncludeComponent(
	"bitrix:main.interface.toolbar",
	"",
	array(
		"BUTTONS" => $arResult['BUTTONS']
	),
	$component
);

$APPLICATION->IncludeComponent(
	"bitrix:main.interface.grid",
	"",
	array(
		"GRID_ID" => $arResult["GRID_ID"],
		"HEADERS" => $arResult["HEADERS"],
		"ROWS" => $arResult["ROWS"],
		"NAV_OBJECT" => $arResult["NAV_OBJECT"],
		"SORT" => $arResult["SORT"],
		"FOOTER" => array(
			array("title" => GetMessage("VOX_QUEUE_LIST_SELECTED"), "value" => $arResult["ROWS_COUNT"])
		),
		"AJAX_MODE" => "N",
	),
	$component, array("HIDE_ICONS" => "Y")
);



