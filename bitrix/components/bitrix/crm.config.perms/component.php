<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}


$arDefaultUrlTemplates404 = array(
	'perms_list' => '',
	'role_edit' => '#role_id#/edit/'
);

$arDefaultVariableAliases404 = array();

$arDefaultVariableAliases = array();

$arComponentVariables = array(
	'role_id',
	'mode'
);

if($arParams['SEF_MODE'] == 'Y')
{
	$arVariables = array();

	$arUrlTemplates = CComponentEngine::MakeComponentUrlTemplates($arDefaultUrlTemplates404, $arParams['SEF_URL_TEMPLATES']);
	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases404, $arParams['VARIABLE_ALIASES']);

	$componentPage = CComponentEngine::ParseComponentPath(
		$arParams['SEF_FOLDER'],
		$arUrlTemplates,
		$arVariables
	);

	if(!$componentPage)
		$componentPage = 'perms_list';

	CComponentEngine::InitComponentVariables($componentPage, $arComponentVariables, $arVariableAliases, $arVariables);
	$arResult = array(
		'FOLDER' => $arParams['SEF_FOLDER'],
		'URL_TEMPLATES' => $arUrlTemplates,
		'VARIABLES' => $arVariables,
		'ALIASES' => $arVariableAliases
	);
}
else
{
	$arVariables = array();
	if(!isset($arParams['VARIABLE_ALIASES']['ID']))
		$arParams['VARIABLE_ALIASES']['ID'] = 'ID';

	$arVariableAliases = CComponentEngine::MakeComponentVariableAliases($arDefaultVariableAliases, $arParams['VARIABLE_ALIASES']);
	CComponentEngine::InitComponentVariables(false, $arComponentVariables, $arVariableAliases, $arVariables);

	$componentPage = 'perms_list'; //default page

	if(isset($arVariables['mode']))
	{
		switch($arVariables['mode'])
		{
			case 'edit':
				if(isset($arVariables['role_id']))
					$componentPage = 'role_edit';
			break;
			case 'list':
				$componentPage = 'perms_list';
			break;
		}
	}

	$arResult = array(
		'FOLDER' => '',
		'URL_TEMPLATES' => Array(
			'entity_list' => $APPLICATION->GetCurPage(),
			'role_edit' => $APPLICATION->GetCurPage()
				.'?'.$arVariableAliases['mode'].'=edit'
				.'&'.$arVariableAliases['role_id'].'=#role_id#'
		),
		'VARIABLES' => $arVariables,
		'ALIASES' => $arVariableAliases
	);
}

$this->IncludeComponentTemplate($componentPage);
?>