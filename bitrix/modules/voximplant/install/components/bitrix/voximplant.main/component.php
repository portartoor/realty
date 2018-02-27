<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (isset($_REQUEST['AJAX_CALL']) && $_REQUEST['AJAX_CALL'] == 'Y')
	return;

if (!CModule::IncludeModule('voximplant'))
	return;

$permissions = \Bitrix\Voximplant\Security\Permissions::createWithCurrentUser();
$arResult['SHOW_LINES'] = $permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_LINE, \Bitrix\Voximplant\Security\Permissions::ACTION_MODIFY);
$arResult['SHOW_STATISTICS'] = $permissions->canPerform(\Bitrix\Voximplant\Security\Permissions::ENTITY_CALL_DETAIL, \Bitrix\Voximplant\Security\Permissions::ACTION_VIEW);

$ViAccount = new CVoxImplantAccount();

$arResult['LANG'] = $ViAccount->GetAccountLang();
$arResult['CURRENCY'] = $ViAccount->GetAccountCurrency();

if ( in_array($arResult['LANG'], Array('ua', 'kz')) && !isset($_GET['REFRESH']))
{
	$arResult['AMOUNT'] = 0;
}
else
{
	$arResult['AMOUNT'] = $ViAccount->GetAccountBalance(true);
}

$arResult['ERROR_MESSAGE'] = '';

if ($ViAccount->GetError()->error)
{
	$arResult['AMOUNT'] = '';
	$arResult['CURRENCY'] = '';
	if ($ViAccount->GetError()->code == 'LICENCE_ERROR')
	{
		$arResult['ERROR_MESSAGE'] = GetMessage('VI_ERROR_LICENSE');
	}
	else
	{
		$arResult['ERROR_MESSAGE'] = GetMessage('VI_ERROR');
	}
}

if (LANGUAGE_ID == "kz")
{
	$arResult['LANG'] = "kz";
}

$arResult['LINK_TO_BUY'] = CVoxImplantMain::GetBuyLink();
$arResult['RECORD_LIMIT'] = \CVoxImplantAccount::GetRecordLimit();

if (!(isset($arParams['TEMPLATE_HIDE']) && $arParams['TEMPLATE_HIDE'] == 'Y'))
	$this->IncludeComponentTemplate();

return $arResult;

?>