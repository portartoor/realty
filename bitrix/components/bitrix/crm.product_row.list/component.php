<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

global $USER, $DB;

//OWNER_ID for new entities is zero
$ownerID = isset($arParams['OWNER_ID']) ? (int)$arParams['OWNER_ID'] : 0;

// Check owner type (DEAL by default)
$ownerType = isset($arParams['OWNER_TYPE']) ? (string)$arParams['OWNER_TYPE'] : 'D';
$ownerName = '';
if($ownerType == 'D')
{
	$ownerName = 'DEAL';
}
elseif($ownerType == 'L')
{
	$ownerName = 'LEAD';
}
else
{
	ShowError(GetMessage('CRM_UNSUPPORTED_OWNER_TYPE', array('#OWNER_TYPE#' => $ownerType)));
	return;
}

// Check permissions (READ by default)
$permissionType = isset($arParams['PERMISSION_TYPE']) ? (string)$arParams['PERMISSION_TYPE'] : 'READ';
$perms = new CCrmPerms($USER->GetID());
if ($perms->HavePerm($ownerName, BX_CRM_PERM_NONE, $permissionType))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['OWNER_TYPE'] = $ownerType;
$arResult['OWNER_ID'] = $ownerID;

$arResult['READ_ONLY'] = $permissionType == 'READ';

// Check currency (national currency by default)
$currencyID =  $arResult['CURRENCY_ID'] =  isset($arParams['CURRENCY_ID']) ? (string)$arParams['CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID();
$currency = CCrmCurrency::GetByID($currencyID);
if(!$currency)
{
	ShowError(GetMessage('CRM_CURRENCY_IS_NOT_FOUND', array('#CURRENCY_ID#' => $currencyID)));
	return;
}

$arResult['CURRENCY_FORMAT'] = CCrmCurrency::GetCurrencyFormatString($currencyID);

//$exchRate = $arResult['EXCH_RATE'] = isset($arParams['EXCH_RATE']) ? (double)$arParams['EXCH_RATE'] : 1.0;
//$arResult['CURRENCY_DISPLAY_NAME'] = $currency['ID']; //ID is ISO 4217

// Prepare source data
if(isset($arParams['PRODUCT_ROWS']) && is_array($arParams['PRODUCT_ROWS']))
{
	$arResult['PRODUCT_ROWS'] = $arParams['PRODUCT_ROWS'];
	foreach($arResult['PRODUCT_ROWS'] as &$arProdRow)
	{
		if(isset($arProdRow['PRODUCT_NAME']))
		{
			continue;
		}

		$dbRes = CCrmProduct::GetList(
			array(),
			array('ID' => intval($arProdRow['PRODUCT_ID'])),
			false,
			false,
			array('NAME')
		);

		$arProdRow['PRODUCT_NAME'] =
			is_array($arRes = $dbRes->Fetch()) ? $arRes['NAME'] : '['.strval($arProdRow['PRODUCT_ID']).']';
	}
}
else
{
	$arResult['PRODUCT_ROWS'] = $ownerID > 0 ? CCrmProductRow::LoadRows($ownerType, $ownerID) : array();
}

// Prepare sum total
$sumTotal = 0.0;
foreach($arResult['PRODUCT_ROWS'] as $row)
{
	if(!isset($row['PRICE']) || !isset($row['QUANTITY']))
	{
		continue;
	}

	$sumTotal += doubleval($row['PRICE']) * intval($row['QUANTITY']);
}

$arResult['SUM_TOTAL'] = round($sumTotal, 2);

//SAVING MODE. ONSUBMIT: SAVE ALL PRODUCT ROWS ON SUBMIT, ONCHANGE: SAVE PRODUCT ROWS AFTER EVERY CHANGE (AJAX)
$arResult['SAVING_MODE'] = isset($arParams['SAVING_MODE']) ? strtoupper($arParams['SAVING_MODE']) : 'ONSUBMIT';
if($arResult['SAVING_MODE'] != 'ONSUBMIT' && $arResult['SAVING_MODE'] != 'ONCHANGE')
{
	$arResult['SAVING_MODE'] = 'ONSUBMIT';
}

$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['PREFIX'] = htmlspecialcharsbx($ownerID > 0 ? strtolower($ownerName).'_'.strval($ownerID) : 'new_'.strtolower($ownerName));
$arResult['CONTAINER_CLASS'] = htmlspecialcharsbx(strtolower($ownerName).'-product-rows');
$arResult['ROW_CLASS'] = '';
$arResult['PRODUCT_DATA_FIELD_NAME'] = isset($arParams['PRODUCT_DATA_FIELD_NAME']) ? $arParams['PRODUCT_DATA_FIELD_NAME'] : 'PRODUCT_ROW_DATA';

$this->IncludeComponentTemplate();
