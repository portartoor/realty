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

$CCrmContact = new CCrmContact();
if ($CCrmContact->cPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'WRITE')
	&& $CCrmContact->cPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'ADD'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$CCrmBizProc = new CCrmBizProc('CONTACT');

$arParams['PATH_TO_CONTACT_LIST'] = CrmCheckPath('PATH_TO_CONTACT_LIST', $arParams['PATH_TO_CONTACT_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
$arParams['PATH_TO_CONTACT_EDIT'] = CrmCheckPath('PATH_TO_CONTACT_EDIT', $arParams['PATH_TO_CONTACT_EDIT'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&edit');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

$bInternal = false;
if (isset($arParams['INTERNAL_FILTER']) && !empty($arParams['INTERNAL_FILTER']))
	$bInternal = true;
$arResult['INTERNAL'] = $bInternal;

global $USER_FIELD_MANAGER, $DB, $USER;

$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmContact::$sUFEntityID);

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
	$obFields = CCrmContact::GetList(array(), $arFilter, array());
	$arFields = $obFields->GetNext();
	if ($arFields === false)
	{
		$bEdit = false;
		$bCopy = false;
	}
	else
		$arEntityAttr = $CCrmContact->cPerms->GetEntityAttr('CONTACT', array($arParams['ELEMENT_ID']));
	if ($bCopy)
	{
		$res = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => $arParams['ELEMENT_ID'])
		);
		$arResult['ELEMENT']['FM'] = array();
		while($ar = $res->Fetch())
		{
			$arFields['FM'][$ar['TYPE_ID']]['n0'.$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);
			$arFields['FM'][$ar['TYPE_ID']]['n0'.$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);
		}
		unset($arFields['PHOTO']);
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
			CCrmContact::GetFields()
		);
		// hack for UF
		$_REQUEST = $_REQUEST + $arParams['~VALUES'];
	}

	if (isset($_GET['company_id']))
		$arFields['COMPANY_ID'] = (int) $_GET['company_id'];
}

if (($bEdit && !$CCrmContact->cPerms->CheckEnityAccess('CONTACT', 'WRITE', $arEntityAttr[$arParams['ELEMENT_ID']]) ||
	(!$bEdit && $CCrmContact->cPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'ADD'))))
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
	if($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
	{
		$bVarsFromForm = true;
		if(isset($_POST['save']) || isset($_POST['saveAndView']) || isset($_POST['saveAndAdd']) || isset($_POST['apply']))
		{
			$arFields = array(
				'NAME' => trim($_POST['NAME']),
				'LAST_NAME' => trim($_POST['LAST_NAME']),
				'SECOND_NAME' => trim($_POST['SECOND_NAME']),
				//'BIRTHDATE' => trim($_POST['BIRTHDATE']),
				'POST' => trim($_POST['POST']),
				'ADDRESS' => trim($_POST['ADDRESS']),
				'COMMENTS' => trim($_POST['COMMENTS']),
				'SOURCE_DESCRIPTION' => trim($_POST['SOURCE_DESCRIPTION']),
				'SOURCE_ID' => trim($_POST['SOURCE_ID']),
				'TYPE_ID' => trim($_POST['TYPE_ID']),
				'COMPANY_ID' => $_POST['COMPANY_ID'],
				'PHOTO' => $_FILES['PHOTO'],
				'PHOTO_del' => $_POST['PHOTO_del'],
				'EXPORT' => isset($_POST['EXPORT']) && $_POST['EXPORT'] == 'Y' ? 'Y' : 'N',
				'OPENED' => isset($_POST['OPENED']) && $_POST['OPENED'] == 'Y' ? 'Y' : 'N',
				'ASSIGNED_BY_ID' => intval(is_array($_POST['ASSIGNED_BY_ID']) ? $_POST['ASSIGNED_BY_ID'][0] : $_POST['ASSIGNED_BY_ID']),
				'FM' => $_POST['CONFM']
			);

			$USER_FIELD_MANAGER->EditFormAddFields(CCrmContact::$sUFEntityID, $arFields);
			$arResult['ERROR_MESSAGE'] = '';

			if (!$CCrmContact->CheckFields($arFields, $bEdit ? $arResult['ELEMENT']['ID'] : false))
			{
				if (!empty($CCrmContact->LAST_ERROR))
					$arResult['ERROR_MESSAGE'] .= $CCrmContact->LAST_ERROR;
				else
					$arResult['ERROR_MESSAGE'] .= GetMessage('UNKNOWN_ERROR');
			}

			if (($arBizProcParametersValues = $CCrmBizProc->CheckFields($bEdit ? $arResult['ELEMENT']['ID']: false, false, $arResult['ELEMENT']['ASSIGNED_BY'], $bEdit ? $arEntityAttr[$arResult['ELEMENT']['ID']] : array()) === false))
				$arResult['ERROR_MESSAGE'] .= $CCrmBizProc->LAST_ERROR;

			if (empty($arResult['ERROR_MESSAGE']))
			{
				$DB->StartTransaction();

				$bSuccess = false;
				if ($bEdit)
				{
					$bSuccess = $CCrmContact->Update($arResult['ELEMENT']['ID'], $arFields);
				}
				else
				{
					$ID = $CCrmContact->Add($arFields);
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
					CCrmContact::GetFields()
				);
			}
			else
			{
				if (!isset($_POST['COMPANY_ID']) && isset($_POST['COMPANY_NAME']))
				{
					if (CCrmCompany::CheckCreatePermission())
					{
						$arFields = array(
							'TITLE' => trim($_POST['COMPANY_NAME']),
							'CONTACT_ID' => array($ID),
						);
						$CCrmCompany = new CCrmCompany();
						$companyId = $CCrmCompany->Add($arFields);
						$CCrmContact->UpdateCompanyId($ID, $companyId);
					}
				}

				if (isset($_POST['apply']))
				{
					if (CCrmContact::CheckUpdatePermission($ID))
					{
						LocalRedirect(
							CComponentEngine::MakePathFromTemplate(
								$arParams['PATH_TO_CONTACT_EDIT'],
								array('contact_id' => $ID)
							)
						);
					}
				}
				elseif (isset($_POST['saveAndAdd']))
				{
					LocalRedirect(
						CComponentEngine::MakePathFromTemplate(
							$arParams['PATH_TO_CONTACT_EDIT'],
							array('contact_id' => 0)
						)
					);
				}
				elseif (isset($_POST['saveAndView']))
				{
					if(CCrmContact::CheckReadPermission($ID))
					{
						LocalRedirect(
							CComponentEngine::MakePathFromTemplate(
								$arParams['PATH_TO_CONTACT_SHOW'],
								array('contact_id' => $ID)
							)
						);
					}
				}

				//save
				LocalRedirect(
					isset($_REQUEST['backurl']) && $_REQUEST['backurl'] !== ''
						? $_REQUEST['backurl']
						: CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_LIST'], array())
				);
			}
		}
	}
	else if (isset($_GET['delete']) && check_bitrix_sessid())
	{
		if ($bEdit)
		{
			$arResult['ERROR_MESSAGE'] = '';
			if (!$CCrmContact->cPerms->CheckEnityAccess('CONTACT', 'DELETE', $arEntityAttr[$arParams['ELEMENT_ID']]))
				$arResult['ERROR_MESSAGE'] .= GetMessage('CRM_PERMISSION_DENIED').'<br/>';
			$bDeleteError = !$CCrmBizProc->Delete($arResult['ELEMENT']['ID'], $arEntityAttr[$arParams['ELEMENT_ID']]);
			if ($bDeleteError)
				$arResult['ERROR_MESSAGE'] .= $CCrmBizProc->LAST_ERROR;

			if (empty($arResult['ERROR_MESSAGE']) && !$CCrmContact->Delete($arResult['ELEMENT']['ID']))
				$arResult['ERROR_MESSAGE'] = GetMessage('CRM_DELETE_ERROR');

			if (empty($arResult['ERROR_MESSAGE']))
				LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_LIST']));
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

$arResult['FORM_ID'] = !empty($arParams['FORM_ID']) ? $arParams['FORM_ID'] : 'CRM_CONTACT_EDIT_V12';
$arResult['GRID_ID'] = 'CRM_CONTACT_LIST_V12';
$arResult['BACK_URL'] = $arParams['PATH_TO_CONTACT_LIST'];
$arResult['SOURCE_LIST'] = CCrmStatus::GetStatusList('SOURCE');
$arResult['TYPE_LIST'] = CCrmStatus::GetStatusList('CONTACT_TYPE');
$arResult['EDIT'] = $bEdit;

$arResult['FIELDS'] = array();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_contact_info',
	'name' => GetMessage('CRM_SECTION_CONTACT_INFO2'),
	'type' => 'section'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'LAST_NAME',
	'name' => GetMessage('CRM_FIELD_LAST_NAME'),
	'params' => array('size' => 50),
	'type' => 'text',
	'value' => isset($arResult['ELEMENT']['~LAST_NAME']) ? $arResult['ELEMENT']['~LAST_NAME'] : '',
	'required' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'NAME',
	'name' => GetMessage('CRM_FIELD_NAME'),
	'params' => array('size' => 50, 'id' => 'contact_name_id'),
	'type' => 'text',
	'value' => isset($arResult['ELEMENT']['~NAME']) ? $arResult['ELEMENT']['~NAME'] : '',
	'required' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SECOND_NAME',
	'name' => GetMessage('CRM_FIELD_SECOND_NAME'),
	'params' => array('size' => 50),
	'type' => 'text',
	'value' => isset($arResult['ELEMENT']['~SECOND_NAME']) ? $arResult['ELEMENT']['~SECOND_NAME'] : '',
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'PHOTO',
	'name' => GetMessage('CRM_FIELD_PHOTO'),
	'params' => array(),
	'type' => 'file',
	'value' => isset($arResult['ELEMENT']['PHOTO']) ? $arResult['ELEMENT']['PHOTO'] : '',
);
ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.edit',
	'new',
	array(
		'FM_MNEMONIC' => 'CONFM',
		'ENTITY_ID' => 'CONTACT',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'EMAIL',
		'VALUES' => isset($arResult['ELEMENT']['FM']) ? $arResult['ELEMENT']['FM'] : ''
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
		'FM_MNEMONIC' => 'CONFM',
		'ENTITY_ID' => 'CONTACT',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'PHONE',
		'VALUES' => isset($arResult['ELEMENT']['FM']) ? $arResult['ELEMENT']['FM'] : ''
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
		'FM_MNEMONIC' => 'CONFM',
		'ENTITY_ID' => 'CONTACT',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'WEB',
		'VALUES' => isset($arResult['ELEMENT']['FM']) ? $arResult['ELEMENT']['FM'] : ''
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
		'FM_MNEMONIC' => 'CONFM',
		'ENTITY_ID' => 'CONTACT',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'IM',
		'VALUES' => isset($arResult['ELEMENT']['FM']) ? $arResult['ELEMENT']['FM'] : ''
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
if (CCrmCompany::CheckReadPermission())
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'COMPANY_ID',
		'name' => GetMessage('CRM_FIELD_COMPANY_ID'),
		'type' => 'crm_entity_selector',
		'componentParams' => array(
			'ENTITY_TYPE' => 'COMPANY',
			'INPUT_NAME' => 'COMPANY_ID',
			'NEW_INPUT_NAME' => CCrmCompany::CheckCreatePermission() ? 'NEW_COMPANY_ID' : '',
			'INPUT_VALUE' => isset($arResult['ELEMENT']['COMPANY_ID']) ? $arResult['ELEMENT']['COMPANY_ID'] : '',
			'FORM_NAME' => $arResult['FORM_ID'],
			'MULTIPLE' => 'N',
			'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
		)
	);
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'POST',
	'name' => GetMessage('CRM_FIELD_POST'),
	'params' => array('size' => 50),
	'type' => 'text',
	'value' => isset($arResult['ELEMENT']['~POST']) ? $arResult['ELEMENT']['~POST'] : ''
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ADDRESS',
	'name' => GetMessage('CRM_FIELD_ADDRESS'),
	'type' => 'textarea',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['ADDRESS']) ? $arResult['ELEMENT']['ADDRESS'] : ''
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
	'id' => 'EXPORT',
	'name' => GetMessage('CRM_FIELD_EXPORT'),
	'type' => 'vertical_checkbox',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['EXPORT']) ? $arResult['ELEMENT']['EXPORT'] : 'Y'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_additional',
	'name' => GetMessage('CRM_SECTION_ADDITIONAL'),
	'type' => 'section'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'TYPE_ID',
	'name' => GetMessage('CRM_FIELD_TYPE_ID'),
	'type' => 'list',
	'items' => $arResult['TYPE_LIST'],
	'value' => (isset($arResult['ELEMENT']['TYPE_ID']) ? $arResult['ELEMENT']['TYPE_ID'] : '')
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ASSIGNED_BY_ID',
	'componentParams' => array(
		'NAME' => 'crm_contact_edit_resonsible',
		'INPUT_NAME' => 'ASSIGNED_BY_ID',
		'SEARCH_INPUT_NAME' => 'ASSIGNED_BY_NAME',
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
	),
	'name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID'),
	'type' => 'intranet_user_search',
	'value' => isset($arResult['ELEMENT']['ASSIGNED_BY_ID']) ? $arResult['ELEMENT']['ASSIGNED_BY_ID'] : $USER->GetID()
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SOURCE_ID',
	'name' => GetMessage('CRM_FIELD_SOURCE_ID'),
	'type' => 'list',
	'items' => $arResult['SOURCE_LIST'],
	'value' => isset($arResult['ELEMENT']['~SOURCE_ID']) ? $arResult['ELEMENT']['~SOURCE_ID'] : ''
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SOURCE_DESCRIPTION',
	'name' => GetMessage('CRM_FIELD_SOURCE_DESCRIPTION'),
	'type' => 'textarea',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['SOURCE_DESCRIPTION']) ? $arResult['ELEMENT']['SOURCE_DESCRIPTION'] : ''
);

$CCrmUserType->AddFields(
	$arResult['FIELDS']['tab_1'],
	$arResult['ELEMENT']['ID'],
	$arResult['FORM_ID'],
	$bVarsFromForm,
	false,
	false,
	array(
		'FILE_URL_TEMPLATE' =>
			"/bitrix/components/bitrix/crm.contact.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#"
	)
);

if (IsModuleInstalled('bizproc'))
{
	CBPDocument::AddShowParameterInit('crm', 'only_users', 'CONTACT');

	$bizProcIndex = 0;
	if (!isset($arDocumentStates))
	{
		$arDocumentStates = CBPDocument::GetDocumentStates(
			array('crm', 'CCrmDocumentContact', 'CONTACT'),
			$bEdit ? array('crm', 'CCrmDocumentContact', 'CONTACT_'.$arResult['ELEMENT']['ID']) : null
		);
	}

	foreach ($arDocumentStates as $arDocumentState)
	{
		$bizProcIndex++;
		$canViewWorkflow = CBPDocument::CanUserOperateDocument(
			CBPCanUserOperateOperation::ViewWorkflow,
			$USER->GetID(),
			array('crm', 'CCrmDocumentContact', $bEdit ? 'CONTACT_'.$arResult['ELEMENT']['ID'] : 'CONTACT_0'),
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

include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.contact/include/nav.php');

?>