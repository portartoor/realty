<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if(!CAllCrmInvoice::installExternalEntities())
	return;
if(!CCrmQuote::LocalComponentCausedUpdater())
	return;

if (!CModule::IncludeModule('currency'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CURRENCY'));
	return;
}
if (!CModule::IncludeModule('catalog'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_CATALOG'));
	return;
}
if (!CModule::IncludeModule('sale'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED_SALE'));
	return;
}

$arDefaultUrlTemplates404 = array(
	'index' => 'index.php',
	'list' => 'list/',
	'category' => 'category/#category_id#/',
	'funnel' => 'funnel/',
	'import' => 'import/',
	'widget' => 'widget/',
	'widgetcategory' => 'widget/category/#category_id#/',
	'kanban' => 'kanban/',
	'kanbancategory' => 'kanban/category/#category_id#/',
	'edit' => 'edit/#deal_id#/',
	'show' => 'show/#deal_id#/'
);

$arDefaultVariableAliases404 = array(

);
$arDefaultVariableAliases = array();
$componentPage = '';
$arComponentVariables = array('deal_id', 'category_id');

$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

if ($arParams['SEF_MODE'] == 'Y')
{
	$arVariables = array();
	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams['SEF_URL_TEMPLATES']);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams['VARIABLE_ALIASES']);
	$componentPage = CComponentEngine::ParseComponentPath($arParams['SEF_FOLDER'], $arUrlTemplates, $arVariables);

	if (empty($componentPage) || (!array_key_exists($componentPage, $arDefaultUrlTemplates404)))
		$componentPage = 'index';
	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);

	foreach ($arUrlTemplates as $url => $value)
	{
		if(strlen($arParams['PATH_TO_DEAL_'.strToUpper($url)]) <= 0)
			$arResult['PATH_TO_DEAL_'.strToUpper($url)] = $arParams['SEF_FOLDER'].$value;
		else
			$arResult['PATH_TO_DEAL_'.strToUpper($url)] = $arParams['PATH_TO_'.strToUpper($url)];
	}
}
else
{
	$arComponentVariables[] = $arParams['VARIABLE_ALIASES']['deal_id'];

	$arVariables = array();
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams['VARIABLE_ALIASES']);
	CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

	$componentPage = 'index';
	if (isset($_REQUEST['edit']))
		$componentPage = 'edit';
	else if (isset($_REQUEST['copy']))
		$componentPage = 'edit';
	else if (isset($_REQUEST['card']))
		$componentPage = 'card';
	else if (isset($_REQUEST['show']))
		$componentPage = 'show';
	else if (isset($_REQUEST['import']))
		$componentPage = 'import';
	else if (isset($_REQUEST['widget']))
		$componentPage = 'widget';
	else if (isset($_REQUEST['category']))
		$componentPage = 'category';
	else if (isset($_REQUEST['widgetcategory']))
		$componentPage = 'widgetcategory';
	else if (isset($_REQUEST['kanban']))
		$componentPage = 'kanban';
	else if (isset($_REQUEST['kanbancategory']))
		$componentPage = 'kanbancategory';

	$arResult['PATH_TO_DEAL_LIST'] = $APPLICATION->GetCurPage();
	$arResult['PATH_TO_DEAL_FUNNEL'] = $APPLICATION->GetCurPage().'&funnel';
	$arResult['PATH_TO_DEAL_SHOW'] = $APPLICATION->GetCurPage()."?$arVariableAliases[deal_id]=#deal_id#&show";
	$arResult['PATH_TO_DEAL_EDIT'] = $APPLICATION->GetCurPage()."?$arVariableAliases[deal_id]=#deal_id#&edit";
	$arResult['PATH_TO_DEAL_IMPORT'] = $APPLICATION->GetCurPage()."?import";
	$arResult['PATH_TO_DEAL_WIDGET'] = $APPLICATION->GetCurPage()."?widget";
	$arResult['PATH_TO_DEAL_KANBAN'] = $APPLICATION->GetCurPage()."?kanban";
	$arResult['PATH_TO_DEAL_CATEGORY'] = $APPLICATION->GetCurPage()."?category=#category_id#";
}

$arResult = array_merge(
	array(
		'VARIABLES' => $arVariables,
		'ALIASES' => $arParams['SEF_MODE'] == 'Y'? array(): $arVariableAliases,
		'ELEMENT_ID' => $arParams['ELEMENT_ID'],
		'PATH_TO_LEAD_CONVERT' => $arParams['PATH_TO_LEAD_CONVERT'],
		'PATH_TO_LEAD_EDIT' => $arParams['PATH_TO_LEAD_EDIT'],
		'PATH_TO_LEAD_SHOW' => $arParams['PATH_TO_LEAD_SHOW'],
		'PATH_TO_CONTACT_EDIT' => $arParams['PATH_TO_CONTACT_EDIT'],
		'PATH_TO_CONTACT_SHOW' => $arParams['PATH_TO_CONTACT_SHOW'],
		'PATH_TO_COMPANY_EDIT' => $arParams['PATH_TO_COMPANY_EDIT'],
		'PATH_TO_COMPANY_SHOW' => $arParams['PATH_TO_COMPANY_SHOW'],
		'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
		'PATH_TO_DEAL_CATEGORY_LIST' => CrmCheckPath('PATH_TO_DEAL_CATEGORY_LIST', $arParams['PATH_TO_DEAL_CATEGORY_LIST'], COption::GetOptionString('crm', 'path_to_deal_category_list')),
		'PATH_TO_DEAL_CATEGORY_EDIT' => CrmCheckPath('PATH_TO_DEAL_CATEGORY_EDIT', $arParams['PATH_TO_DEAL_CATEGORY_EDIT'], COption::GetOptionString('crm', 'path_to_deal_category_edit'))
	),
	$arResult
);

if($componentPage === 'list' || $componentPage === 'category')
{
	$categoryID = isset($arResult['VARIABLES']['category_id']) ? $arResult['VARIABLES']['category_id'] : -1;
	$currentCategoryID = (int)CUserOptions::GetOption('crm', 'current_deal_category', -1);
	if($componentPage === 'list' && $currentCategoryID >= 0)
	{
		CUserOptions::DeleteOption('crm', 'current_deal_category');
	}
	elseif($componentPage === 'category' && $categoryID >= 0 && $categoryID !== $currentCategoryID)
	{
		CUserOptions::SetOption('crm', 'current_deal_category', $categoryID);
	}
}

$arResult['NAVIGATION_CONTEXT_ID'] = 'DEAL';
if($componentPage === 'index' || $componentPage === 'category')
{
	$componentPage = 'list';
}
elseif($componentPage === 'widgetcategory')
{
	$componentPage = 'widget';
}
elseif($componentPage === 'kanbancategory')
{
	$componentPage = 'kanban';
}

$this->IncludeComponentTemplate($componentPage);
?>