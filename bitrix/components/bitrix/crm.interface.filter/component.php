<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$gridID = $arParams['GRID_ID'];
$gridContext = CCrmGridContext::Get($gridID);
if(empty($gridContext) && isset($arParams['FILTER_FIELDS']))
{
	$gridContext = CCrmGridContext::Parse($arParams['FILTER_FIELDS']);
}
$arResult['FILTER_INFO'] = isset($gridContext['FILTER_INFO']) ? $gridContext['FILTER_INFO'] : array();
$this->IncludeComponentTemplate();
