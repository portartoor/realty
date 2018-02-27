<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if (!isset($arResult['INTERNAL']) || !$arResult['INTERNAL'])
{
	if (isset($arResult['ELEMENT']['ID']))
	{
		$APPLICATION->AddChainItem(GetMessage('CRM_CONTACT_NAV_TITLE_LIST'), $arParams['PATH_TO_CONTACT_LIST']);
		if (!empty($arResult['ELEMENT']['ID']))
			$APPLICATION->SetTitle(GetMessage('CRM_CONTACT_NAV_TITLE_EDIT', array('#NAME#' => CUser::FormatName($arParams["NAME_TEMPLATE"], $arResult['ELEMENT'], true, false))));
		else
			$APPLICATION->SetTitle(GetMessage('CRM_CONTACT_NAV_TITLE_ADD')); 
	}
	else 
		$APPLICATION->SetTitle(GetMessage('CRM_CONTACT_NAV_TITLE_LIST'));
}
?>