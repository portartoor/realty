<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
	die();

/** @global CMain $APPLICATION */

if (!CModule::includeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if(!CCrmPerms::IsAccessEnabled())
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['BITRIX24'] = \Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24');
$arResult['IS_BIZPRPOC_ENABLED'] = CModule::IncludeModule('bizproc') && CBPRuntime::isFeatureEnabled();
$arResult['IS_AUTOMATION_LEAD_ENABLED'] = \Bitrix\Crm\Automation\Factory::isAutomationAvailable(\CCrmOwnerType::Lead);
$arResult['IS_AUTOMATION_DEAL_ENABLED'] = \Bitrix\Crm\Automation\Factory::isAutomationAvailable(\CCrmOwnerType::Deal);

$arResult['PERM_CONFIG'] = false;
$arResult['IS_ACCESS_ENABLED'] = false;
/** @var \CCrmPerms $crmPerms */
$crmPerms = CCrmPerms::getCurrentUserPermissions();
if(!$crmPerms->HavePerm('CONFIG', BX_CRM_PERM_NONE))
	$arResult['PERM_CONFIG'] = true;
if($crmPerms->IsAccessEnabled())
	$arResult['IS_ACCESS_ENABLED'] = true;

$arResult['RAND_STRING'] = $this->randString();

$APPLICATION->SetTitle(GetMessage('CRM_TITLE'));
$this->includeComponentTemplate();