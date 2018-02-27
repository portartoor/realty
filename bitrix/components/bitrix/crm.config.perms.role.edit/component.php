<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

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

$arParams['PATH_TO_ROLE_EDIT'] = CrmCheckPath('PATH_TO_ROLE_EDIT', $arParams['PATH_TO_ROLE_EDIT'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_ENTITY_LIST'] = CrmCheckPath('PATH_TO_ENTITY_LIST', $arParams['PATH_TO_ENTITY_LIST'], $APPLICATION->GetCurPage());

$arParams['ROLE_ID'] = (int) $arParams['ROLE_ID'];
$bVarsFromForm = false;

$arResult['PATH_TO_ROLE_EDIT'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_ROLE_EDIT'],
	array(
		'role_id' => $arParams['ROLE_ID']
	)
);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['save']) || isset($_POST['apply'])) && check_bitrix_sessid())
{
	$bVarsFromForm = true;
	$arFields = array(
		'NAME' => $_POST['NAME'],
		'RELATION' => isset($_POST['ROLE_PERMS'])? $_POST['ROLE_PERMS']: Array()
	);

	$CCrmRole = new CcrmRole();
	if ($arParams['ROLE_ID'] > 0)
	{
		if (!$CCrmRole->Update($arParams['ROLE_ID'], $arFields))
			$arResult['ERROR_MESSAGE'] = $arFields['RESULT_MESSAGE'];
	}
	else
	{
		$arParams['ROLE_ID'] = $CCrmRole->Add($arFields);
		if ($arParams['ROLE_ID'] === false)
			$arResult['ERROR_MESSAGE'] = $arFields['RESULT_MESSAGE'];
	}

	if (empty($arResult['ERROR_MESSAGE']))
	{
		if (isset($_POST['apply']))
			LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_ROLE_EDIT'],
				array(
					'role_id' => $arParams['ROLE_ID']
				)
			));
		else
			LocalRedirect($arParams['PATH_TO_ENTITY_LIST']);
	}
	else
		ShowError($arResult['ERROR_MESSAGE']);

	$arResult['ROLE'] = array(
		'ID' => $arParams['ROLE_ID'],
		'NAME' => $arFields['NAME']
	);
	$arResult['ROLE_PERMS'] = $arFields['RELATION'];
}
else if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['delete']) && check_bitrix_sessid() && $arParams['ROLE_ID'] > 0)
{
	$CCrmRole = new CCrmRole();
	$CCrmRole->Delete($arParams['ROLE_ID']);
	LocalRedirect($arParams['PATH_TO_ENTITY_LIST']);
}

if (!$bVarsFromForm)
{
	if ($arParams['ROLE_ID'] > 0)
	{
		$obRes = CCrmRole::GetList(array(), array('ID' => $arParams['ROLE_ID']));
		$arResult['ROLE'] = $obRes->Fetch();
		if ($arResult['ROLE'] == false)
			$arParams['ROLE_ID'] = 0;
	}

	if ($arParams['ROLE_ID'] <= 0)
	{
		$arResult['ROLE']['ID'] = 0;
		$arResult['ROLE']['NAME'] = '';
	}

	$arResult['ROLE_PERMS'] = array();

}
if ($arParams['ROLE_ID'] > 0 && !$bVarsFromForm)
	$arResult['~ROLE_PERMS'] = CCrmRole::GetRolePerms($arParams['ROLE_ID']);
if (!$bVarsFromForm)
	$arResult['ROLE_PERMS'] = $arResult['~ROLE_PERMS'];

$arResult['ENTITY'] = array(
	'CONTACT' => GetMessage('CRM_ENTITY_TYPE_CONTACT'),
	'COMPANY' => GetMessage('CRM_ENTITY_TYPE_COMPANY'),
	'DEAL' => GetMessage('CRM_ENTITY_TYPE_DEAL'),
	'LEAD' => GetMessage('CRM_ENTITY_TYPE_LEAD')
);

$arPerms = array(
	'READ', 'ADD', 'WRITE', 'DELETE', 'EXPORT', 'IMPORT'
);

$arResult['ENTITY_FIELDS'] = array(
	'DEAL' => array('STAGE_ID' => CCrmStatus::GetStatusListEx('DEAL_STAGE')),
	'LEAD' => array('STATUS_ID' => CCrmStatus::GetStatusListEx('STATUS'))
);

$arResult['ROLE_PERM']['LEAD'] =
$arResult['ROLE_PERM']['DEAL'] =
$arResult['ROLE_PERM']['CONTACT'] = array(
	BX_CRM_PERM_NONE => GetMessage('CRM_PERMS_TYPE_'.BX_CRM_PERM_NONE),
	BX_CRM_PERM_SELF => GetMessage('CRM_PERMS_TYPE_'.BX_CRM_PERM_SELF),
	BX_CRM_PERM_DEPARTMENT => GetMessage('CRM_PERMS_TYPE_'.BX_CRM_PERM_DEPARTMENT),
	BX_CRM_PERM_SUBDEPARTMENT => GetMessage('CRM_PERMS_TYPE_'.BX_CRM_PERM_SUBDEPARTMENT),
	BX_CRM_PERM_OPEN => GetMessage('CRM_PERMS_TYPE_'.BX_CRM_PERM_OPEN),
	BX_CRM_PERM_ALL => GetMessage('CRM_PERMS_TYPE_'.BX_CRM_PERM_ALL)
);
$arResult['ROLE_PERM']['COMPANY'] = array(
	BX_CRM_PERM_NONE => GetMessage('CRM_PERMS_TYPE_'.BX_CRM_PERM_NONE),
	BX_CRM_PERM_OPEN => GetMessage('CRM_PERMS_TYPE_'.BX_CRM_PERM_OPEN),
	BX_CRM_PERM_ALL => GetMessage('CRM_PERMS_TYPE_'.BX_CRM_PERM_ALL)
);


$arResult['PATH_TO_ROLE_DELETE'] =  CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_ROLE_EDIT'],
	array(
		'role_id' => $arResult['ROLE']['ID']
	)),
	array('delete' => '1', 'sessid' => bitrix_sessid())
);

foreach ($arPerms as $perm)
{
	foreach ($arResult['ENTITY'] as $entityType => $entityName)
	{
		if (isset($arResult['ENTITY_FIELDS'][$entityType]))
		{
			foreach ($arResult['ENTITY_FIELDS'][$entityType] as $fieldID => $arFieldValue)
			{
				foreach ($arFieldValue as $fieldValueID => $fieldValue)
				{
					if (!isset($arResult['ROLE_PERMS'][$entityType][$perm][$fieldID][$fieldValueID]) || $arResult['ROLE_PERMS'][$entityType][$perm][$fieldID][$fieldValueID] == '-')
						$arResult['ROLE_PERMS'][$entityType][$perm][$fieldID][$fieldValueID] = $arResult['ROLE_PERMS'][$entityType][$perm]['-'];
				}
			}
		}
	}
}

$this->IncludeComponentTemplate();

$APPLICATION->SetTitle(GetMessage('CRM_PERMS_ROLE_EDIT'));
$APPLICATION->AddChainItem(GetMessage('CRM_PERMS_ENTITY_LIST'), $arParams['PATH_TO_ENTITY_LIST']);
$APPLICATION->AddChainItem(GetMessage('CRM_PERMS_ROLE_EDIT'), $arResult['PATH_TO_ROLE_EDIT']);

?>