<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

CModule::IncludeModule('fileman');

if (IsModuleInstalled('bizproc'))
{
	if (!CModule::IncludeModule('bizproc'))
	{
		ShowError(GetMessage('BIZPROC_MODULE_NOT_INSTALLED'));
		return;
	}
}

$CCrmCompany = new CCrmCompany();
if ($CCrmCompany->cPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'WRITE')
	&& $CCrmCompany->cPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'ADD'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$CCrmBizProc = new CCrmBizProc('COMPANY');

$arParams['PATH_TO_COMPANY_LIST'] = CrmCheckPath('PATH_TO_COMPANY_LIST', $arParams['PATH_TO_COMPANY_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_COMPANY_EDIT'] = CrmCheckPath('PATH_TO_COMPANY_EDIT', $arParams['PATH_TO_COMPANY_EDIT'], $APPLICATION->GetCurPage().'?company_id=#company_id#&edit');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$bInternal = false;
if (isset($arParams['INTERNAL_FILTER']) && !empty($arParams['INTERNAL_FILTER']))
	$bInternal = true;
$arResult['INTERNAL'] = $bInternal;

global $USER_FIELD_MANAGER, $DB, $USER;

$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmCompany::$sUFEntityID);

$bEdit = false;
$bCopy = false;
$bVarsFromForm = false;
$arParams['ELEMENT_ID'] = (int) $arParams['ELEMENT_ID'];
if (!empty($arParams['ELEMENT_ID']))
	$bEdit = true;
if (!empty($_REQUEST['copy']))
{
	$bCopy = true;
	$bEdit = false;
}

$bConvert = isset($arParams['CONVERT']) && $arParams['CONVERT'];

if ($bEdit || $bCopy)
{
	$arFilter = array(
		'ID' => $arParams['ELEMENT_ID'],
		'PERMISSION' => 'WRITE'
	);
	$obFields = CCrmCompany::GetListEx(array(), $arFilter);
	$arFields = $obFields->GetNext();

	if ($arFields === false)
	{
		$bEdit = false;
		$bCopy = false;
	}
	else
		$arEntityAttr = $CCrmCompany->cPerms->GetEntityAttr('COMPANY', array($arParams['ELEMENT_ID']));
	if ($bCopy)
	{
		$res = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $arParams['ELEMENT_ID'])
		);
		$arResult['ELEMENT']['FM'] = array();
		while($ar = $res->Fetch())
		{
			$arFields['FM'][$ar['TYPE_ID']]['n0'.$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);
			$arFields['FM'][$ar['TYPE_ID']]['n0'.$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);
		}
		unset($arFields['LOGO']);
	}
}
else
{
	$arFields = array(
		'ID' => 0
	);

	if (isset($arParams['~VALUES']) && is_array($arParams['~VALUES']))
	{
		$arFields = array_merge($arFields, $arParams['~VALUES']);
		$arFields = CCrmComponentHelper::PrepareEntityFields(
			$arFields,
			CCrmCompany::GetFields()
		);

		// hack for UF
		$_REQUEST = $_REQUEST + $arParams['~VALUES'];
	}

	if (isset($_GET['contact_id']))
		$arResult['CONTACT_ID'] = array((int) $_GET['contact_id']);
}

if (($bEdit && !$CCrmCompany->cPerms->CheckEnityAccess('COMPANY', 'WRITE', $arEntityAttr[$arParams['ELEMENT_ID']]) ||
	(!$bEdit && $CCrmCompany->cPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'ADD'))))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['ELEMENT'] = $arFields;
unset($arFields);
if($bConvert)
{
	$bVarsFromForm = true;
}
else
{
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
	{
		$bVarsFromForm = true;
		if(isset($_POST['save']) || isset($_POST['saveAndView']) || isset($_POST['saveAndAdd']) || isset($_POST['apply']))
		{
			$arFields = array(
				'TITLE' => trim($_POST['TITLE']),
				'ADDRESS' => trim($_POST['ADDRESS']),
				'ADDRESS_LEGAL' => trim($_POST['ADDRESS_LEGAL']),
				'BANKING_DETAILS' => trim($_POST['BANKING_DETAILS']),
				'COMMENTS' => trim($_POST['COMMENTS']),
				'COMPANY_TYPE' => trim($_POST['COMPANY_TYPE']),
				'INDUSTRY' => trim($_POST['INDUSTRY']),
				'REVENUE' => trim($_POST['REVENUE']),
				'CURRENCY_ID' => trim($_POST['CURRENCY_ID']),
				'EMPLOYEES' => trim($_POST['EMPLOYEES']),
				'CONTACT_ID' => is_array($_POST['CONTACT_ID'])? $_POST['CONTACT_ID']: array(),
				'LOGO' => $_FILES['LOGO'],
				'LOGO_del' => $_POST['LOGO_del'],
				'OPENED' => isset($_POST['OPENED']) && $_POST['OPENED'] == 'Y' ? 'Y' : 'N',
				'ASSIGNED_BY_ID' => intval(is_array($_POST['ASSIGNED_BY_ID']) ? $_POST['ASSIGNED_BY_ID'][0] : $_POST['ASSIGNED_BY_ID']),
				'FM' => $_POST['COMFM']
			);
			$USER_FIELD_MANAGER->EditFormAddFields(CCrmCompany::$sUFEntityID, $arFields);
			$arResult['ERROR_MESSAGE'] = '';

			if (!$CCrmCompany->CheckFields($arFields, $bEdit ? $arResult['ELEMENT']['ID'] : false))
			{
				if (!empty($CCrmCompany->LAST_ERROR))
					$arResult['ERROR_MESSAGE'] .= $CCrmCompany->LAST_ERROR;
				else
					$arResult['ERROR_MESSAGE'] .= GetMessage('UNKNOWN_ERROR');
			}

			if (($arBizProcParametersValues = $CCrmBizProc->CheckFields($bEdit ? $arResult['ELEMENT']['ID']: false, false, $arResult['ELEMENT']['ASSIGNED_BY'], $bEdit ? $arEntityAttr[$arResult['ELEMENT']['ID']] : array())) === false)
				$arResult['ERROR_MESSAGE'] .= $CCrmBizProc->LAST_ERROR;

			if (empty($arResult['ERROR_MESSAGE']))
			{
				$ID = isset($arResult['ELEMENT']['ID']) ? $arResult['ELEMENT']['ID'] : 0;
				$DB->StartTransaction();
				$bSuccess = false;
				if ($bEdit)
				{
					$bSuccess = $CCrmCompany->Update($ID, $arFields);
				}
				else
				{
					$ID = $CCrmCompany->Add($arFields);
					$bSuccess = $ID !== false;
					if($bSuccess)
					{
						$arResult['ELEMENT']['ID'] = $ID;
					}
				}

				if($bSuccess)
				{
					$DB->Commit();
				}
				else
				{
					$DB->Rollback();
					$arResult['ERROR_MESSAGE'] = !empty($arFields['RESULT_MESSAGE']) ? $arFields['RESULT_MESSAGE'] : GetMessage('UNKNOWN_ERROR');
				}
			}

			if (empty($arResult['ERROR_MESSAGE'])
				&& !$CCrmBizProc->StartWorkflow($arResult['ELEMENT']['ID'], $arBizProcParametersValues))
			{
				$arResult['ERROR_MESSAGE'] = $CCrmBizProc->LAST_ERROR;
			}

			$ID = isset($arResult['ELEMENT']['ID']) ? $arResult['ELEMENT']['ID'] : 0;

			if (!empty($arResult['ERROR_MESSAGE']))
			{
				ShowError($arResult['ERROR_MESSAGE']);
				$arResult['ELEMENT'] = CCrmComponentHelper::PrepareEntityFields(
					array_merge(array('ID' => $ID), $arFields),
					CCrmCompany::GetFields()
				);
			}
			else
			{
				if (isset($_POST['apply']))
				{
					if (CCrmCompany::CheckUpdatePermission($ID))
					{
						LocalRedirect(
							CComponentEngine::MakePathFromTemplate(
								$arParams['PATH_TO_COMPANY_EDIT'],
								array('company_id' => $ID)
							)
						);
					}
				}
				elseif (isset($_POST['saveAndAdd']))
				{
					LocalRedirect(
						CComponentEngine::MakePathFromTemplate(
							$arParams['PATH_TO_COMPANY_EDIT'],
							array('company_id' => 0)
						)
					);
				}
				elseif (isset($_POST['saveAndView']))
				{
					if(CCrmCompany::CheckReadPermission($ID))
					{
						LocalRedirect(
							CComponentEngine::MakePathFromTemplate(
								$arParams['PATH_TO_COMPANY_SHOW'],
								array('company_id' => $ID)
							)
						);
					}
				}

				//save
				LocalRedirect(
					isset($_REQUEST['backurl']) && $_REQUEST['backurl'] !== ''
						? $_REQUEST['backurl']
						: CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_LIST'], array())
				);
			}
		}
	}
	else if (isset($_GET['delete']) && check_bitrix_sessid())
	{
		if ($bEdit)
		{
			$arResult['ERROR_MESSAGE'] = '';
			if (!$CCrmCompany->cPerms->CheckEnityAccess('COMPANY', 'DELETE', $arEntityAttr[$arParams['ELEMENT_ID']]))
				$arResult['ERROR_MESSAGE'] .= GetMessage('CRM_PERMISSION_DENIED').'<br/>';
			$bDeleteError = !$CCrmBizProc->Delete($arResult['ELEMENT']['ID'], $arEntityAttr[$arParams['ELEMENT_ID']]);
			if ($bDeleteError)
				$arResult['ERROR_MESSAGE'] .= $CCrmBizProc->LAST_ERROR;

			if (empty($arResult['ERROR_MESSAGE']) && !$CCrmCompany->Delete($arResult['ELEMENT']['ID']))
				$arResult['ERROR_MESSAGE'] = GetMessage('CRM_DELETE_ERROR');

			if (empty($arResult['ERROR_MESSAGE']))
				LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_LIST']));
			else
			{
				ShowError($arResult['ERROR_MESSAGE']);
				return;
			}
		}
		else
		{
			ShowError(GetMessage('CRM_DELETE_ERROR'));
			return;
		}
	}
}

$arResult['FORM_ID'] = !empty($arParams['FORM_ID']) ? $arParams['FORM_ID'] : 'CRM_COMPANY_EDIT_V12';
$arResult['GRID_ID'] = 'CRM_COMPANY_LIST_V12';
$arResult['BACK_URL'] = $arParams['PATH_TO_COMPANY_LIST'];
$arResult['COMPANY_TYPE_LIST'] = CCrmStatus::GetStatusList('COMPANY_TYPE');
$arResult['INDUSTRY_LIST'] = CCrmStatus::GetStatusList('INDUSTRY');
$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();
$arResult['EMPLOYEES_LIST'] = CCrmStatus::GetStatusList('EMPLOYEES');

// Fix for #26945. Suppress binding of contacts to new compnany. Contacts will be binded to source company.
if(!$bCopy)
{
	$dbRes = CCrmContact::GetContactByCompanyId($arResult['ELEMENT']['ID']);
	if(!isset($arResult['CONTACT_ID']))
	{
		$arResult['CONTACT_ID'] = array();
	}
	while($arContact = $dbRes->Fetch())
	{
		$arResult['CONTACT_ID'][] = $arContact['ID'];
	}
}

$arResult['FIELDS'] = array();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_company_info',
	'name' => GetMessage('CRM_SECTION_COMPANY_INFO2'),
	'type' => 'section'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'TITLE',
	'name' => GetMessage('CRM_FIELD_TITLE'),
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['~TITLE']) ? $arResult['ELEMENT']['~TITLE'] : '',
	'type' => 'text',
	'required' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ASSIGNED_BY_ID',
	'componentParams' => array(
		'NAME' => 'crm_company_edit_resonsible',
		'INPUT_NAME' => 'ASSIGNED_BY_ID',
		'SEARCH_INPUT_NAME' => 'ASSIGNED_BY_NAME',
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
	),
	'name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID'),
	'type' => 'intranet_user_search',
	'value' => isset($arResult['ELEMENT']['~ASSIGNED_BY_ID']) ? $arResult['ELEMENT']['~ASSIGNED_BY_ID'] : $USER->GetID()
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'LOGO',
	'name' => GetMessage('CRM_FIELD_LOGO'),
	'params' => array(),
	'type' => 'file',
	'value' => isset($arResult['ELEMENT']['LOGO']) ? $arResult['ELEMENT']['LOGO'] : '',
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMPANY_TYPE',
	'name' => GetMessage('CRM_FIELD_COMPANY_TYPE'),
	'type' => 'list',
	'items' => $arResult['COMPANY_TYPE_LIST'],
	'value' => isset($arResult['ELEMENT']['COMPANY_TYPE']) ? $arResult['ELEMENT']['COMPANY_TYPE'] : ''
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'INDUSTRY',
	'name' => GetMessage('CRM_FIELD_INDUSTRY'),
	'type' => 'list',
	'items' => $arResult['INDUSTRY_LIST'],
	'value' => isset($arResult['ELEMENT']['INDUSTRY']) ? $arResult['ELEMENT']['INDUSTRY'] : ''
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'EMPLOYEES',
	'name' => GetMessage('CRM_FIELD_EMPLOYEES'),
	'type' => 'list',
	'items' => $arResult['EMPLOYEES_LIST'],
	'value' => isset($arResult['ELEMENT']['EMPLOYEES']) ? $arResult['ELEMENT']['EMPLOYEES'] : ''
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'REVENUE',
	'name' => GetMessage('CRM_FIELD_REVENUE'),
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['REVENUE']) ? $arResult['ELEMENT']['REVENUE'] : '',
	'type' => 'text',
	'required' => false
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CURRENCY_ID',
	'name' => GetMessage('CRM_FIELD_CURRENCY_ID'),
	'items' => $arResult['CURRENCY_LIST'],
	'type' => 'list',
	'value' => isset($arResult['ELEMENT']['CURRENCY_ID']) ? $arResult['ELEMENT']['CURRENCY_ID'] : ''
);
ob_start();
$ar = array(
	'inputName' => 'COMMENTS',
	'inputId' => 'COMMENTS',
	'height' => '180',
	'content' => isset($arResult['ELEMENT']['~COMMENTS']) ? $arResult['ELEMENT']['~COMMENTS'] : '',
	'bUseFileDialogs' => false,
	'bFloatingToolbar' => false,
	'bArisingToolbar' => false,
	'bResizable' => true,
	'bSaveOnBlur' => true,
	'toolbarConfig' => array(
		'Bold', 'Italic', 'Underline', 'Strike',
		'BackColor', 'ForeColor',
		'CreateLink', 'DeleteLink',
		'InsertOrderedList', 'InsertUnorderedList', 'Outdent', 'Indent'
	)
);
$LHE = new CLightHTMLEditor;
$LHE->Show($ar);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMMENTS',
	'name' => GetMessage('CRM_FIELD_COMMENTS'),
	'params' => array(),
	'type' => 'vertical_container',
	'value' => $sVal
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'OPENED',
	'name' => GetMessage('CRM_FIELD_OPENED'),
	'type' => 'vertical_checkbox',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['OPENED']) ? $arResult['ELEMENT']['OPENED'] : true,
	'title' => GetMessage('CRM_FIELD_OPENED_TITLE')
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_contact_info',
	'name' => GetMessage('CRM_SECTION_CONTACT_INFO'),
	'type' => 'section'
);

ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.edit',
	'new',
	array(
		'FM_MNEMONIC' => 'COMFM',
		'ENTITY_ID' => 'COMPANY',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'EMAIL',
		'VALUES' => isset($arResult['ELEMENT']['FM'])? $arResult['ELEMENT']['FM']: array(),
		'SKIP_VALUES' => array('HOME')
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'EMAIL',
	'name' => GetMessage('CRM_FIELD_EMAIL'),
	'type' => 'custom',
	'value' => $sVal
);

ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.edit',
	'new',
	array(
		'FM_MNEMONIC' => 'COMFM',
		'ENTITY_ID' => 'COMPANY',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'PHONE',
		'VALUES' => isset($arResult['ELEMENT']['FM'])? $arResult['ELEMENT']['FM']: array(),
		'SKIP_VALUES' => array('HOME')
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'PHONE',
	'name' => GetMessage('CRM_FIELD_PHONE'),
	'type' => 'custom',
	'value' => $sVal
);

ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.edit',
	'new',
	array(
		'FM_MNEMONIC' => 'COMFM',
		'ENTITY_ID' => 'COMPANY',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'WEB',
		'VALUES' => isset($arResult['ELEMENT']['FM'])? $arResult['ELEMENT']['FM']: array(),
		'SKIP_VALUES' => array('HOME')
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'WEB',
	'name' => GetMessage('CRM_FIELD_WEB'),
	'type' => 'custom',
	'value' => $sVal
);

ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.edit',
	'new',
	array(
		'FM_MNEMONIC' => 'COMFM',
		'ENTITY_ID' => 'COMPANY',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'IM',
		'VALUES' => isset($arResult['ELEMENT']['FM'])? $arResult['ELEMENT']['FM']: array()
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'IM',
	'name' => GetMessage('CRM_FIELD_MESSENGER'),
	'type' => 'custom',
	'value' => $sVal
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ADDRESS',
	'name' => GetMessage('CRM_FIELD_ADDRESS'),
	'type' => 'textarea',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['ADDRESS']) ? $arResult['ELEMENT']['ADDRESS'] : ''
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ADDRESS_LEGAL',
	'name' => GetMessage('CRM_FIELD_ADDRESS_LEGAL'),
	'type' => 'textarea',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['ADDRESS_LEGAL']) ? $arResult['ELEMENT']['ADDRESS_LEGAL'] : ''
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'BANKING_DETAILS',
	'name' => GetMessage('CRM_FIELD_BANKING_DETAILS'),
	'type' => 'textarea',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['BANKING_DETAILS']) ? $arResult['ELEMENT']['BANKING_DETAILS'] : ''
);


// Contacts selector
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_contacts',
	'name' => GetMessage('CRM_SECTION_CONTACTS'),
	'type' => 'section'
);
if (!$CCrmCompany->cPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'READ'))
{
	ob_start();
	$GLOBALS['APPLICATION']->IncludeComponent('bitrix:crm.entity.selector',
		'',
		array(
			'ENTITY_TYPE' => 'CONTACT',
			'INPUT_NAME' => 'CONTACT_ID',
			'INPUT_VALUE' => isset($arResult['CONTACT_ID']) ? $arResult['CONTACT_ID'] : '',
			'FORM_NAME' => $arResult['FORM_ID'],
			'MULTIPLE' => 'Y'
		),
		false,
		array('HIDE_ICONS' => 'Y')
	);
	$sVal = ob_get_contents();
	ob_end_clean();

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'CONTACT_ID',
		'name' => GetMessage('CRM_FIELD_CONTACT_ID'),
		'type' => 'custom',
		'wrap' => true,
		'value' => $sVal
	);
}

$CCrmUserType->AddFields(
	$arResult['FIELDS']['tab_1'],
	$arResult['ELEMENT']['ID'],
	$arResult['FORM_ID'],
	$bVarsFromForm || !empty($arParams['VALUES']),
	false,
	false,
	array(
		'FILE_URL_TEMPLATE' =>
			"/bitrix/components/bitrix/crm.company.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#"
	)
);

if (IsModuleInstalled('bizproc'))
{
	CBPDocument::AddShowParameterInit('crm', 'only_users', 'COMPANY');

	$bizProcIndex = 0;
	if (!isset($arDocumentStates))
	{
		$arDocumentStates = CBPDocument::GetDocumentStates(
			array('crm', 'CCrmDocumentCompany', 'COMPANY'),
			$bEdit ? array('crm', 'CCrmDocumentCompany', 'COMPANY_'.$arResult['ELEMENT']['ID']) : null
		);
	}

	foreach ($arDocumentStates as $arDocumentState)
	{
		$bizProcIndex++;
		$canViewWorkflow = CBPDocument::CanUserOperateDocument(
			CBPCanUserOperateOperation::ViewWorkflow,
			$USER->GetID(),
			array('crm', 'CCrmDocumentCompany', $bEdit ? 'COMPANY_'.$arResult['ELEMENT']['ID'] : 'COMPANY_0'),
			array(
				'UserGroups' => $CCrmBizProc->arCurrentUserGroups,
				'DocumentStates' => $arDocumentStates,
				'WorkflowId' => $arDocumentState['ID'] > 0 ? $arDocumentState['ID'] : $arDocumentState['TEMPLATE_ID'],
				'CreatedBy' => $arResult['ELEMENT']['ASSIGNED_BY'],
				'UserIsAdmin' => $USER->IsAdmin()
			)
		);

		if (!$canViewWorkflow)
			continue;

		$arResult['FIELDS']['tab_1'][] = array(
			'id' => 'section_bp_name_'.$bizProcIndex,
			'name' => $arDocumentState['TEMPLATE_NAME'],
			'type' => 'section'
		);
		if ($arDocumentState['TEMPLATE_DESCRIPTION'] != '')
		{
			$arResult['FIELDS']['tab_1'][] = array(
				'id' => 'BP_DESC_'.$bizProcIndex,
				'name' => GetMessage('CRM_FIELD_BP_TEMPLATE_DESC'),
				'type' => 'label',
				'colspan' => true,
				'value' => $arDocumentState['TEMPLATE_DESCRIPTION']
			);
		}
		if (!empty($arDocumentState['STATE_MODIFIED']))
		{
			$arResult['FIELDS']['tab_1'][] = array(
				'id' => 'BP_STATE_MODIFIED_'.$bizProcIndex,
				'name' => GetMessage('CRM_FIELD_BP_STATE_MODIFIED'),
				'type' => 'label',
				'value' => $arDocumentState['STATE_MODIFIED']
			);
		}
		if (!empty($arDocumentState['STATE_NAME']))
		{
			$arResult['FIELDS']['tab_1'][] = array(
				'id' => 'BP_STATE_NAME_'.$bizProcIndex,
				'name' => GetMessage('CRM_FIELD_BP_STATE_NAME'),
				'type' => 'label',
				'value' => strlen($arDocumentState['STATE_TITLE']) > 0 ? $arDocumentState['STATE_TITLE'] : $arDocumentState['STATE_NAME']
			);
		}
		if (strlen($arDocumentState['ID']) <= 0)
		{
			ob_start();
			CBPDocument::StartWorkflowParametersShow(
				$arDocumentState['TEMPLATE_ID'],
				$arDocumentState['TEMPLATE_PARAMETERS'],
				'form_'.$arResult['FORM_ID'],
				$bVarsFromForm
			);
			$sVal = ob_get_contents();
			ob_end_clean();
			$arResult['FIELDS']['tab_1'][] = array(
				'id' => 'BP_PARAMETERS',
				'name' => GetMessage('CRM_FIELD_BP_PARAMETERS'),
				'colspan' => true,
				'type' => 'custom',
				'value' => "<table>$sVal</table>"
			);
		}

		$_arEvents = CBPDocument::GetAllowableEvents($USER->GetID(), $CCrmBizProc->arCurrentUserGroups, $arDocumentState);
		if (count($_arEvents) > 0)
		{
			$arEvent = array('' => GetMessage('CRM_FIELD_BP_EMPTY_EVENT'));
			foreach ($_arEvents as $_arEvent)
				$arEvent[$_arEvent['NAME']] = $_arEvent['TITLE'];

			$arResult['FIELDS']['tab_1'][] = array(
				'id' => 'BP_EVENTS',
				'name' => GetMessage('CRM_FIELD_BP_EVENTS'),
				'params' => array(),
				'items' => $arEvent,
				'type' => 'list',
				'value' => (isset($_REQUEST['bizproc_event_'.$bizProcIndex]) ? $_REQUEST['bizproc_event_'.$bizProcIndex] : '')
			);

			$arResult['FORM_CUSTOM_HTML'] = '
					<input type="hidden" name="bizproc_id_'.$bizProcIndex.'" value="'.$arDocumentState["ID"].'">
					<input type="hidden" name="bizproc_template_id_'.$bizProcIndex.'" value="'.$arDocumentState["TEMPLATE_ID"].'">
			';
		}

	}

	if ($bizProcIndex > 0)
		$arResult['BIZPROC'] = true;
}


if ($bCopy)
{
	$arParams['ELEMENT_ID'] = 0;
	$arFields['ID'] = 0;
	$arResult['ELEMENT']['ID'] = 0;
}

$this->IncludeComponentTemplate();

include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.company/include/nav.php');

?>