<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

global $APPLICATION;

$componentPage = '';
$arDefaultUrlTemplates404 = array(
	'index' => 'index.php',
	'product_list' => 'list/#section_id#/',
	'product_edit' => 'edit/#product_id#/',
	'product_show' => 'show/#product_id#/',
	'section_list' => 'section_list/#section_id#/'
);

if ($arParams['SEF_MODE'] === 'Y')
{
	$arDefaultVariableAliases404 = array();
	$arComponentVariables = array('product_id', 'section_id');
	$arVariables = array();
	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams['SEF_URL_TEMPLATES']);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams['VARIABLE_ALIASES']);
	$componentPage = CComponentEngine::ParseComponentPath($arParams['SEF_FOLDER'], $arUrlTemplates, $arVariables);

	if (!(isset($componentPage[0]) && isset($arDefaultUrlTemplates404[$componentPage])))
	{
		$componentPage = 'index';
	}

	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);

	foreach ($arUrlTemplates as $url => $value)
	{
		$key = 'PATH_TO_'.strtoupper($url);
		$arResult[$key] = isset($arParams[$key][0]) ? $arParams[$key] : $arParams['SEF_FOLDER'].$value;
	}
}
else
{
	$arComponentVariables = array(
	    isset($arParams['VARIABLE_ALIASES']['product_id']) ? $arParams['VARIABLE_ALIASES']['product_id'] : 'product_id',
	    isset($arParams['VARIABLE_ALIASES']['section_id']) ? $arParams['VARIABLE_ALIASES']['section_id'] : 'section_id'
	);

	$arDefaultVariableAliases = array(
	'product_id' => 'product_id',
		'section_id' => 'section_id'
	);
	$arVariables = array();
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams['VARIABLE_ALIASES']);
	CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

	$componentPage = 'index';
	if (isset($_REQUEST['edit']))
	{
		$componentPage = 'product_edit';
	}
	elseif (isset($_REQUEST['show']))
	{
		$componentPage = 'product_show';
	}
	elseif (isset($_REQUEST['sections']))
	{
		$componentPage = 'section_list';
	}

	$curPage = $APPLICATION->GetCurPage();

	$arResult['PATH_TO_INDEX'] = $curPage;
	$arResult['PATH_TO_PRODUCT_LIST'] = $curPage.'?'.$arVariableAliases['section_id'].'=#section_id#';
	$arResult['PATH_TO_PRODUCT_EDIT'] = $curPage.'?'.$arVariableAliases['product_id'].'=#product_id#&edit';
	$arResult['PATH_TO_PRODUCT_SHOW'] = $curPage.'?'.$arVariableAliases['product_id'].'=#product_id#&show';
	$arResult['PATH_TO_SECTION_LIST'] = $curPage.'?'.$arVariableAliases['section_id'].'=#section_id#&sections';
}

$catalogID = isset($arParams['CATALOG_ID']) ? intval($arParams['CATALOG_ID']) : 0;
if($catalogID <= 0)
{
	$catalogID = CCrmCatalog::EnsureDefaultExists();
}

$arResult =
	array_merge(
		array(
			'VARIABLES' => $arVariables,
			'ALIASES' => $arParams['SEF_MODE'] == 'Y' ? array(): $arVariableAliases,
			'CATALOG_ID' => $catalogID,
			'PRODUCT_ID' => isset($arVariables['product_id']) ? intval($arVariables['product_id']) : 0,
			'SECTION_ID' => isset($arVariables['section_id']) ? intval($arVariables['section_id']) : 0
		),
		$arResult
	);
$this->IncludeComponentTemplate($componentPage);
?>