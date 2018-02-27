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

$CCrmLead = new CCrmLead();
if ($CCrmLead->cPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'WRITE')
	&& $CCrmLead->cPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'ADD'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$CCrmBizProc = new CCrmBizProc('LEAD');

$arParams['PATH_TO_LEAD_LIST'] = CrmCheckPath('PATH_TO_LEAD_LIST', $arParams['PATH_TO_LEAD_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_LEAD_EDIT'] = CrmCheckPath('PATH_TO_LEAD_EDIT', $arParams['PATH_TO_LEAD_EDIT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&edit');
$arParams['PATH_TO_LEAD_SHOW'] = CrmCheckPath('PATH_TO_LEAD_SHOW', $arParams['PATH_TO_LEAD_SHOW'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&show');
$arParams['PATH_TO_LEAD_CONVERT'] = CrmCheckPath('PATH_TO_LEAD_CONVERT', $arParams['PATH_TO_LEAD_CONVERT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&convert');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

global $USER_FIELD_MANAGER, $DB, $USER;

$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmLead::$sUFEntityID);

$arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;
$bCopy = !empty($_REQUEST['copy']);
$bEdit = !$bCopy && $arParams['ELEMENT_ID'] > 0;
$bVarsFromForm = false;
$arEntityAttr = array();

//Show error message if required
if($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['error']))
{
	$errorID = strtolower($_GET['error']);
	if(preg_match('/^crm_err_/', $errorID) === 1)
	{
		if(!isset($_SESSION[$errorID]))
		{
			LocalRedirect(
				CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_LEAD_EDIT'],
					array('lead_id' => $arParams['ELEMENT_ID'])
				)
			);
		}

		$errorMessage = strval($_SESSION[$errorID]);
		unset($_SESSION[$errorID]);
		if($errorMessage !== '')
		{
			ShowError(htmlspecialcharsbx($errorMessage));
			return;
		}
	}
}

if ($bEdit || $bCopy)
{
	$arFilter = array(
		'ID' => $arParams['ELEMENT_ID'],
		'PERMISSION' => 'WRITE'
	);
	$obFields = CCrmLead::GetList(array(), $arFilter, array());
	$arFields = $obFields->GetNext();
	if ($arFields === false)
	{
		$bEdit = false;
		$bCopy = false;
	}
	else
		$arEntityAttr = $CCrmLead->cPerms->GetEntityAttr('LEAD', array($arParams['ELEMENT_ID']));
	if ($bCopy)
	{
		$res = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array('ENTITY_ID' => 'LEAD', 'ELEMENT_ID' => $arParams['ELEMENT_ID'])
		);
		$arResult['ELEMENT']['FM'] = array();
		while($ar = $res->Fetch())
		{
			$arFields['FM'][$ar['TYPE_ID']]['n0'.$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);
			$arFields['FM'][$ar['TYPE_ID']]['n0'.$ar['ID']] = array('VALUE' => $ar['VALUE'], 'VALUE_TYPE' => $ar['VALUE_TYPE']);
		}
	}

	//HACK: MSSQL returns '.00' for zero value
	if(isset($arFields['~OPPORTUNITY']))
	{
		$arFields['~OPPORTUNITY'] = $arFields['OPPORTUNITY'] = floatval($arFields['~OPPORTUNITY']);
	}

	if(isset($arFields['~OPPORTUNITY_ACCOUNT']))
	{
		$arFields['~OPPORTUNITY_ACCOUNT'] = $arFields['OPPORTUNITY_ACCOUNT'] = floatval($arFields['~OPPORTUNITY_ACCOUNT']);
	}
}
else
{
	$arFields = array(
		'ID' => 0
	);
}

if (($bEdit && !$CCrmLead->cPerms->CheckEnityAccess('LEAD', 'WRITE', $arEntityAttr[$arParams['ELEMENT_ID']]) ||
	(!$bEdit && $CCrmLead->cPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'ADD'))))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['ELEMENT'] = $arFields;
unset($arFields);

//CURRENCY HACK (RUR is obsolete)
if(isset($arResult['ELEMENT']['CURRENCY_ID']) && $arResult['ELEMENT']['CURRENCY_ID'] === 'RUR')
{
	$arResult['ELEMENT']['CURRENCY_ID'] = 'RUB';
}

$productDataFieldName = 'LEAD_PRODUCT_DATA';

if($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
{
	$bVarsFromForm = true;
	if(isset($_POST['save']) || isset($_POST['saveAndView']) || isset($_POST['saveAndAdd']) || isset($_POST['apply']))
	{
		$currencyID =  trim($_POST['CURRENCY_ID']);
		$exchRate =  CCrmCurrency::GetExchangeRate($currencyID);
		$arFields = array(
			'TITLE' => trim($_POST['TITLE']),
			'COMPANY_TITLE' => trim($_POST['COMPANY_TITLE']),
			'NAME' => trim($_POST['NAME']),
			'LAST_NAME' => trim($_POST['LAST_NAME']),
			'SECOND_NAME' => trim($_POST['SECOND_NAME']),
			'POST' => trim($_POST['POST']),
			'ADDRESS' => trim($_POST['ADDRESS']),
			'COMMENTS' => trim($_POST['COMMENTS']),
			'SOURCE_DESCRIPTION' => trim($_POST['SOURCE_DESCRIPTION']),
			'STATUS_DESCRIPTION' => trim($_POST['STATUS_DESCRIPTION']),
			'OPPORTUNITY' => trim($_POST['OPPORTUNITY']),
			'CURRENCY_ID' => $currencyID,
			'EXCH_RATE' => $exchRate,
			//'PRODUCT_ID' => trim($_POST['PRODUCT_ID']),
			'SOURCE_ID' => trim($_POST['SOURCE_ID']),
			'STATUS_ID' => trim($_POST['STATUS_ID']),
			'ASSIGNED_BY_ID' => (int)(is_array($_POST['ASSIGNED_BY_ID']) ? $_POST['ASSIGNED_BY_ID'][0] : $_POST['ASSIGNED_BY_ID']),
			'OPENED' => isset($_POST['OPENED']) && $_POST['OPENED'] == 'Y' ? 'Y' : 'N',
			'FM' => $_POST['LFM']
		);

		$processProductRows = array_key_exists($productDataFieldName, $_POST);
		$arProd = array();
		if($processProductRows)
		{
			$prodJson = isset($_POST[$productDataFieldName]) ? strval($_POST[$productDataFieldName]) : '';
			$arProd = $arResult['PRODUCT_ROWS'] = strlen($prodJson) > 0 ? CUtil::JsObjectToPhp($prodJson, true) : array();

			if(!empty($arProd))
			{
				// SYNC OPPORTUNITY WITH PRODUCT ROW SUM TOTAL
				$arFields['OPPORTUNITY'] = CCrmProductRow::GetTotalSum($arProd);
			}
		}

		$USER_FIELD_MANAGER->EditFormAddFields(CCrmLead::$sUFEntityID, $arFields);
		$arResult['ERROR_MESSAGE'] = '';

		if (!$CCrmLead->CheckFields($arFields, $bEdit ? $arResult['ELEMENT']['ID'] : false))
		{
			if (!empty($CCrmLead->LAST_ERROR))
				$arResult['ERROR_MESSAGE'] .= $CCrmLead->LAST_ERROR;
			else
				$arResult['ERROR_MESSAGE'] .= GetMessage('UNKNOWN_ERROR');
		}

		if (($arBizProcParametersValues = $CCrmBizProc->CheckFields($bEdit ? $arResult['ELEMENT']['ID']: false, false, $arResult['ELEMENT']['ASSIGNED_BY'], $bEdit ? $arEntityAttr[$arResult['ELEMENT']['ID']] : array())) === false)
			$arResult['ERROR_MESSAGE'] .= $CCrmBizProc->LAST_ERROR;

		if (empty($arResult['ERROR_MESSAGE']))
		{
			$DB->StartTransaction();

			$bSuccess = false;
			if ($bEdit)
			{
				$bSuccess = $CCrmLead->Update($arResult['ELEMENT']['ID'], $arFields);
			}
			else
			{
				$ID = $CCrmLead->Add($arFields);
				$bSuccess = $ID !== false;
				if($bSuccess)
				{
					$arResult['ELEMENT']['ID'] = $ID;
				}
			}

			if($bSuccess
				&& $processProductRows
				&& ($bEdit || !empty($arProd)))
			{
				$bSuccess = CCrmLead::SaveProductRows($arResult['ELEMENT']['ID'], $arProd);
				if(!$bSuccess)
				{
					$arResult['ERROR_MESSAGE'] = GetMessage('PRODUCT_ROWS_SAVING_ERROR');
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

		if (empty($arResult['ERROR_MESSAGE']))
		{
			if (!$CCrmBizProc->StartWorkflow($arResult['ELEMENT']['ID'], $arBizProcParametersValues))
				$arResult['ERROR_MESSAGE'] = $CCrmBizProc->LAST_ERROR;
		}

		$ID = isset($arResult['ELEMENT']['ID']) ? $arResult['ELEMENT']['ID'] : 0;

		if (!empty($arResult['ERROR_MESSAGE']))
		{
			ShowError($arResult['ERROR_MESSAGE']);
			$arResult['ELEMENT'] = CCrmComponentHelper::PrepareEntityFields(
				array_merge(array('ID' => $ID), $arFields),
				CCrmLead::GetFields()
			);
		}
		else
		{
			if (isset($_POST['apply']))
			{
				if (CCrmLead::CheckUpdatePermission($ID))
				{
					LocalRedirect(
						CComponentEngine::MakePathFromTemplate(
							$arParams['PATH_TO_LEAD_EDIT'],
							array('lead_id' => $ID)
						)
					);
				}
			}
			elseif (isset($_POST['saveAndAdd']))
			{
				LocalRedirect(
					CComponentEngine::MakePathFromTemplate(
						$arParams['PATH_TO_LEAD_EDIT'],
						array('lead_id' => 0)
					)
				);
			}
			elseif (isset($_POST['saveAndView']))
			{
				if(CCrmLead::CheckReadPermission($ID))
				{
					LocalRedirect(
						CComponentEngine::MakePathFromTemplate(
							$arParams['PATH_TO_LEAD_SHOW'],
							array('lead_id' => $ID)
						)
					);
				}
			}

			// save
			LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_LIST'], array()));
		}
	}
}
else if (isset($_GET['delete']) && check_bitrix_sessid())
{
	if ($bEdit)
	{
		$arResult['ERROR_MESSAGE'] = '';
		if (!$CCrmLead->cPerms->CheckEnityAccess('LEAD', 'DELETE', $arEntityAttr[$arParams['ELEMENT_ID']]))
			$arResult['ERROR_MESSAGE'] .= GetMessage('CRM_PERMISSION_DENIED').'<br/>';
		$bDeleteError = !$CCrmBizProc->Delete($arResult['ELEMENT']['ID'], $arEntityAttr[$arParams['ELEMENT_ID']]);
		if ($bDeleteError)
			$arResult['ERROR_MESSAGE'] .= $CCrmBizProc->LAST_ERROR;

		if ($arResult['ERROR_MESSAGE'] === ''
			&& !$CCrmLead->Delete(
				$arResult['ELEMENT']['ID'],
				array('CHECK_DEPENDENCIES' => true)))
		{
			$arResult['ERROR_MESSAGE'] = $CCrmLead->LAST_ERROR !== '' ? $CCrmLead->LAST_ERROR : GetMessage('CRM_DELETE_ERROR');
		}
	}
	else
	{
		$arResult['ERROR_MESSAGE'] = GetMessage('CRM_DELETE_ERROR');
	}

	if ($arResult['ERROR_MESSAGE'] === '')
	{
		LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_LIST']));
	}
	else
	{
		$errorID = uniqid('crm_err_');
		$_SESSION[$errorID] = $arResult['ERROR_MESSAGE'];

		LocalRedirect(
			CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_LEAD_EDIT'],
					array('lead_id' => $arResult['ELEMENT']['ID'])
				),
				array('error' => $errorID)
			)
		);
	}
}

if ($bEdit && $arResult['ELEMENT']['STATUS_ID'] == 'CONVERTED')
	LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_CONVERT'],
		array(
			'lead_id' => $arResult['ELEMENT']['ID']
		))
	);
else if ($bCopy)
	$arResult['ELEMENT']['STATUS_ID'] = 'NEW';

$arResult['FORM_ID'] = 'CRM_LEAD_EDIT_V12';
$arResult['GRID_ID'] = 'CRM_LEAD_LIST_V12';
$arResult['BACK_URL'] = $arParams['PATH_TO_LEAD_LIST'];
$arResult['STATUS_LIST'] = array();
$arResult['~STATUS_LIST'] = CCrmStatus::GetStatusList('STATUS');
unset($arResult['~STATUS_LIST']['CONVERTED']);
foreach ($arResult['~STATUS_LIST'] as $sStatusId => $sStatusTitle)
{
	if ($CCrmLead->cPerms->GetPermType('LEAD', $bEdit ? 'WRITE' : 'ADD', array('STATUS_ID'.$sStatusId)) > BX_CRM_PERM_NONE)
		$arResult['STATUS_LIST'][$sStatusId] = $sStatusTitle;
}
$arResult['SOURCE_LIST'] = CCrmStatus::GetStatusList('SOURCE');
$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();
$arResult['EDIT'] = $bEdit;

$arResult['FIELDS'] = array();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_lead_info',
	'name' => GetMessage('CRM_SECTION_LEAD2'),
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
	'id' => 'STATUS_ID',
	'name' => GetMessage('CRM_FIELD_STATUS_ID'),
	'params' => array(),
	'items' => $arResult['STATUS_LIST'],
	'type' => 'list',
	'value' => (isset($arResult['ELEMENT']['~STATUS_ID']) ? $arResult['ELEMENT']['~STATUS_ID'] : '')
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'STATUS_DESCRIPTION',
	'name' => GetMessage('CRM_FIELD_STATUS_DESCRIPTION'),
	'type' => 'textarea',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['STATUS_DESCRIPTION']) ? $arResult['ELEMENT']['STATUS_DESCRIPTION'] : ''
);
$currencyID = CCrmCurrency::GetBaseCurrencyID();
if(($bEdit || $bCopy) && isset($arResult['ELEMENT']['CURRENCY_ID']) && $arResult['ELEMENT']['CURRENCY_ID'] !== '')
{
	$currencyID = $arResult['ELEMENT']['CURRENCY_ID'];
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CURRENCY_ID',
	'name' => GetMessage('CRM_FIELD_CURRENCY_ID'),
	'items' => $arResult['CURRENCY_LIST'],
	'type' => 'list',
	'value' => $currencyID
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'OPPORTUNITY',
	'name' => GetMessage('CRM_FIELD_OPPORTUNITY'),
	'params' => array('size' => 21),
	'type' => 'text',
	'value' => isset($arResult['ELEMENT']['OPPORTUNITY']) ? $arResult['ELEMENT']['OPPORTUNITY'] : ''
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SOURCE_ID',
	'name' => GetMessage('CRM_FIELD_SOURCE_ID'),
	'type' => 'list',
	'items' => $arResult['SOURCE_LIST'],
	'value' => (isset($arResult['ELEMENT']['SOURCE_ID']) ? $arResult['ELEMENT']['SOURCE_ID'] : '')
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SOURCE_DESCRIPTION',
	'name' => GetMessage('CRM_FIELD_SOURCE_DESCRIPTION'),
	'type' => 'textarea',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['SOURCE_DESCRIPTION']) ? $arResult['ELEMENT']['SOURCE_DESCRIPTION'] : ''
);
$arResult['RESPONSIBLE_SELECTOR_PARAMS'] = array(
	'NAME' => 'crm_lead_edit_resonsible',
	'INPUT_NAME' => 'ASSIGNED_BY_ID',
	'SEARCH_INPUT_NAME' => 'ASSIGNED_BY_NAME',
	'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']

);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ASSIGNED_BY_ID',
	'componentParams' => $arResult['RESPONSIBLE_SELECTOR_PARAMS'],
	'name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID'),
	'type' => 'intranet_user_search',
	'value' => isset($arResult['ELEMENT']['ASSIGNED_BY_ID']) ? $arResult['ELEMENT']['ASSIGNED_BY_ID'] : $USER->GetID()
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'OPENED',
	'name' => GetMessage('CRM_FIELD_OPENED'),
	'type' => 'vertical_checkbox',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['OPENED']) ? $arResult['ELEMENT']['OPENED'] : true,
	'title' => GetMessage('CRM_FIELD_OPENED_TITLE')
);
// PRODUCT_ROWS
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_product_rows',
	'name' => GetMessage('CRM_SECTION_PRODUCT_ROWS2'),
	'type' => 'section'
);


$sProductsHtml = '';
ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.product_row.list',
	'',
	array(
		'OWNER_ID' => $arParams['ELEMENT_ID'],
		'OWNER_TYPE' => 'L',
		'PERMISSION_TYPE' => 'WRITE',
		'CURRENCY_ID' => $currencyID,
		//'EXCH_RATE' => $exchRate,
		'PRODUCT_ROWS' => isset($arResult['PRODUCT_ROWS']) ? $arResult['PRODUCT_ROWS'] : null,
		'FORM_ID' => $arResult['FORM_ID'],
		'PRODUCT_DATA_FIELD_NAME' => $productDataFieldName
	),
	false,
	array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
);
$sProductsHtml = ob_get_contents();
ob_end_clean();

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'PRODUCT_ROWS',
	'name' => GetMessage('CRM_FIELD_PRODUCT_ROWS'),
	'colspan' => true,
	'type' => 'custom',
	'value' => $sProductsHtml
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_contact_info',
	'name' => GetMessage('CRM_SECTION_CONTACT_INFO2'),
	'type' => 'section'
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'NAME',
	'name' => GetMessage('CRM_FIELD_NAME'),
	'params' => array('size' => 50),
	'type' => 'text',
	'value' => isset($arResult['ELEMENT']['~NAME']) ? $arResult['ELEMENT']['~NAME'] : '',
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'LAST_NAME',
	'name' => GetMessage('CRM_FIELD_LAST_NAME'),
	'params' => array('size' => 50),
	'type' => 'text',
	'value' => isset($arResult['ELEMENT']['~LAST_NAME']) ? $arResult['ELEMENT']['~LAST_NAME'] : '',
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SECOND_NAME',
	'name' => GetMessage('CRM_FIELD_SECOND_NAME'),
	'params' => array('size' => 50),
	'type' => 'text',
	'value' => isset($arResult['ELEMENT']['~SECOND_NAME']) ? $arResult['ELEMENT']['~SECOND_NAME'] : '',
);

ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.edit', 'new',
	array(
		'FM_MNEMONIC' => 'LFM',
		'ENTITY_ID' => 'LEAD',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'EMAIL',
		'VALUES' => isset($arResult['ELEMENT']['FM'])? $arResult['ELEMENT']['FM']: array()
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
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.edit', 'new',
	array(
		'FM_MNEMONIC' => 'LFM',
		'ENTITY_ID' => 'LEAD',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'PHONE',
		'VALUES' => isset($arResult['ELEMENT']['FM'])? $arResult['ELEMENT']['FM']: array()
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
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.edit', 'new',
	array(
		'FM_MNEMONIC' => 'LFM',
		'ENTITY_ID' => 'LEAD',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'TYPE_ID' => 'WEB',
		'VALUES' => isset($arResult['ELEMENT']['FM'])? $arResult['ELEMENT']['FM']: array()
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
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.edit', 'new',
	array(
		'FM_MNEMONIC' => 'LFM',
		'ENTITY_ID' => 'LEAD',
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
	'id' => 'COMPANY_TITLE',
	'name' => GetMessage('CRM_FIELD_COMPANY_TITLE'),
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['~COMPANY_TITLE']) ?  $arResult['ELEMENT']['~COMPANY_TITLE'] : '',
	'type' => 'text'
);
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'POST',
	'name' => GetMessage('CRM_FIELD_POST'),
	'params' => array('size' => 50),
	'type' => 'text',
	'value' => isset($arResult['ELEMENT']['POST']) ? $arResult['ELEMENT']['~POST'] : ''
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
	'id' => 'section_additional',
	'name' => GetMessage('CRM_SECTION_ADDITIONAL'),
	'type' => 'section'
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
			"/bitrix/components/bitrix/crm.lead.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#"
	)
);

if (IsModuleInstalled('bizproc'))
{
	CBPDocument::AddShowParameterInit('crm', 'only_users', 'LEAD');

	$bizProcIndex = 0;
	if (!isset($arDocumentStates))
	{
		$arDocumentStates = CBPDocument::GetDocumentStates(
			array('crm', 'CCrmDocumentLead', 'LEAD'),
			$bEdit ? array('crm', 'CCrmDocumentLead', 'LEAD_'.$arResult['ELEMENT']['ID']) : null
		);
	}

	foreach ($arDocumentStates as $arDocumentState)
	{
		$bizProcIndex++;
		$canViewWorkflow = CBPDocument::CanUserOperateDocument(
			CBPCanUserOperateOperation::ViewWorkflow,
			$USER->GetID(),
			array('crm', 'CCrmDocumentLead', $bEdit ? 'LEAD_'.$arResult['ELEMENT']['ID'] : 'LEAD_0'),
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

include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.lead/include/nav.php');

?>