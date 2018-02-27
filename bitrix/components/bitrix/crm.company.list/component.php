<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (IsModuleInstalled('bizproc'))
{
	if (!CModule::IncludeModule('bizproc'))
	{
		ShowError(GetMessage('BIZPROC_MODULE_NOT_INSTALLED'));
		return;
	}
}

$CCrmCompany = new CCrmCompany();
if ($CCrmCompany->cPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

global $USER_FIELD_MANAGER, $USER, $APPLICATION, $DB;

$CCrmBizProc = new CCrmBizProc('COMPANY');

$arResult['CURRENT_USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();
$arParams['PATH_TO_COMPANY_LIST'] = CrmCheckPath('PATH_TO_COMPANY_LIST', $arParams['PATH_TO_COMPANY_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_COMPANY_EDIT'] = CrmCheckPath('PATH_TO_COMPANY_EDIT', $arParams['PATH_TO_COMPANY_EDIT'], $APPLICATION->GetCurPage().'?company_id=#company_id#&edit');
$arParams['PATH_TO_DEAL_EDIT']    = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');
$arParams['PATH_TO_CONTACT_EDIT'] = CrmCheckPath('PATH_TO_CONTACT_EDIT', $arParams['PATH_TO_CONTACT_EDIT'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&edit');
$arParams['PATH_TO_USER_BP'] = CrmCheckPath('PATH_TO_USER_BP', $arParams['PATH_TO_USER_BP'], '/company/personal/bizproc/');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

if(!isset($arParams['INTERNAL_CONTEXT']))
{
	$arParams['INTERNAL_CONTEXT'] = array();
}

$arResult['IS_AJAX_CALL'] = isset($_REQUEST['bxajaxid']) || isset($_REQUEST['AJAX_CALL']);

CUtil::InitJSCore(array('ajax', 'tooltip'));

$arResult['GADGET'] = 'N';
if (isset($arParams['GADGET_ID']) && strlen($arParams['GADGET_ID']) > 0)
{
	$arResult['GADGET'] = 'Y';
	$arResult['GADGET_ID'] = $arParams['GADGET_ID'];
}

$arFilter = $arSort = array();
$bInternal = false;
$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';
if (!empty($arParams['INTERNAL_FILTER']) || $arResult['GADGET'] == 'Y')
	$bInternal = true;
$arResult['INTERNAL'] = $bInternal;
if (!empty($arParams['INTERNAL_FILTER']) && is_array($arParams['INTERNAL_FILTER']))
{
	if(empty($arParams['GRID_ID_SUFFIX']))
	{
		$arParams['GRID_ID_SUFFIX'] = $this->GetParent() !== null ? strtoupper($this->GetParent()->GetName()) : '';
	}
	$arFilter = $arParams['INTERNAL_FILTER'];
}

if (!empty($arParams['INTERNAL_SORT']) && is_array($arParams['INTERNAL_SORT']))
	$arSort = $arParams['INTERNAL_SORT'];

$sExportType = '';
if (!empty($_REQUEST['type']))
{
	$sExportType = strtolower(trim($_REQUEST['type']));
	if (!in_array($sExportType, array('csv', 'excel')))
		$sExportType = '';
}
if (!empty($sExportType) && $CCrmCompany->cPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'EXPORT'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmCompany::$sUFEntityID);
$CCrmFieldMulti = new CCrmFieldMulti();

$arResult['GRID_ID'] = 'CRM_COMPANY_LIST_V12'.($bInternal && !empty($arParams['GRID_ID_SUFFIX']) ? '_'.$arParams['GRID_ID_SUFFIX'] : '');

$arResult['COMPANY_TYPE_LIST'] = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
$arResult['EMPLOYEES_LIST'] = CCrmStatus::GetStatusListEx('EMPLOYEES');
$arResult['INDUSTRY_LIST'] = CCrmStatus::GetStatusListEx('INDUSTRY');
$arResult['FILTER'] = array();
$arResult['FILTER2LOGIC'] = array();
$arResult['FILTER_PRESETS'] = array();

if (!$bInternal)
{
	$arResult['FILTER2LOGIC'] = array('TITLE', 'ADDRESS_LEGAL', 'BANKING_DETAILS', 'ADDRESS', 'COMMENTS');

	$originatorID = isset($_REQUEST['ORIGINATOR_ID']) ? $_REQUEST['ORIGINATOR_ID'] : '';
	ob_start();
	?>
	<select name="ORIGINATOR_ID">
		<option value=""><?= GetMessage("CRM_COLUMN_ALL") ?></option>
		<option value="__INTERNAL" <?= $originatorID === '__INTERNAL' ? 'selected' : ''?>><?= GetMessage("CRM_INTERNAL") ?></option>
		<?
		$dbOriginatorsList = CCrmExternalSale::GetList(array("NAME" => "ASC", "SERVER" => "ASC"), array("ACTIVE" => "Y"));
		while ($arOriginator = $dbOriginatorsList->GetNext())
		{
			?><option value="<?= $arOriginator["ID"] ?>"<?= ($originatorID === $arOriginator["ID"]) ? " selected" : "" ?>><?= empty($arOriginator["NAME"]) ? $arOriginator["SERVER"] : $arOriginator["NAME"] ?></option><?
		}
		?>
	</select>
	<?
	$sValOriginator = ob_get_contents();
	ob_end_clean();

	$arResult['FILTER'] = array(
		array('id' => 'FIND', 'name' => GetMessage('CRM_COLUMN_FIND'), 'default' => 'Y', 'type' => 'quick', 'items' => array(
			'title' => GetMessage('CRM_COLUMN_TITLE'),
			'email' => GetMessage('CRM_COLUMN_EMAIL'),
			'phone' => GetMessage('CRM_COLUMN_PHONE'))
		),
		array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID')),
		array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_TITLE')),
		array('id' => 'PHONE', 'name' => GetMessage('CRM_COLUMN_PHONE')),
		array('id' => 'EMAIL', 'name' => GetMessage('CRM_COLUMN_EMAIL')),
		array('id' => 'WEB', 'name' => GetMessage('CRM_COLUMN_WEB')),
		array('id' => 'IM', 'name' => GetMessage('CRM_COLUMN_MESSENGER')),
		array('id' => 'ADDRESS', 'name' => GetMessage('CRM_COLUMN_ADDRESS')),
		array('id' => 'ADDRESS_LEGAL', 'name' => GetMessage('CRM_COLUMN_ADDRESS_LEGAL')),
		array('id' => 'BANKING_DETAILS', 'name' => GetMessage('CRM_COLUMN_BANKING_DETAILS')),
		array('id' => 'COMPANY_TYPE', 'params' => array('multiple' => 'Y'), 'name' => GetMessage('CRM_COLUMN_COMPANY_TYPE'), 'default' => 'Y', 'type' => 'list', 'items' => CCrmStatus::GetStatusList('COMPANY_TYPE')),
		array('id' => 'INDUSTRY', 'params' => array('multiple' => 'Y'), 'name' => GetMessage('CRM_COLUMN_INDUSTRY'), 'default' => 'Y', 'type' => 'list', 'items' => CCrmStatus::GetStatusList('INDUSTRY')),
		array('id' => 'REVENUE', 'name' => GetMessage('CRM_COLUMN_REVENUE')),
		array('id' => 'CURRENCY_ID', 'params' => array('multiple' => 'Y'), 'name' => GetMessage('CRM_COLUMN_CURRENCY_ID'), 'type' => 'list', 'items' => CCrmCurrencyHelper::PrepareListItems()),
		array('id' => 'EMPLOYEES', 'params' => array('multiple' => 'Y'), 'name' => GetMessage('CRM_COLUMN_EMPLOYEES'), 'default' => 'Y', 'type' => 'list', 'items' => CCrmStatus::GetStatusList('EMPLOYEES')),
		array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_COMMENTS')),
		array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'type' => 'date'),
		array('id' => 'CREATED_BY_ID',  'name' => GetMessage('CRM_COLUMN_CREATED_BY'), 'default' => false, 'enable_settings' => false, 'type' => 'user'),
		array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_DATE_MODIFY'), 'default' => 'Y', 'type' => 'date'),
		array('id' => 'MODIFY_BY_ID',  'name' => GetMessage('CRM_COLUMN_MODIFY_BY'), 'default' => false, 'enable_settings' => true, 'type' => 'user'),
		array('id' => 'ASSIGNED_BY_ID',  'name' => GetMessage('CRM_COLUMN_ASSIGNED_BY'), 'default' => false, 'enable_settings' => true, 'type' => 'user'),
		array('id' => 'ORIGINATOR_ID', 'name' => GetMessage('CRM_COLUMN_BINDING'), 'type' => 'custom', 'value' => $sValOriginator),
	);

	$CCrmUserType->ListAddFilterFields($arResult['FILTER'], $arResult['FILTER2LOGIC'], $arResult['GRID_ID']);

	$currentUserID = $arResult['CURRENT_USER_ID'];
	$currentUserName = CCrmViewHelper::GetFormattedUserName($currentUserID, $arParams['NAME_TEMPLATE']);
	$arResult['FILTER_PRESETS'] = array(
		'filter_my' => array('name' => GetMessage('CRM_PRESET_MY'), 'fields' => array('ASSIGNED_BY_ID_name' => $currentUserName, 'ASSIGNED_BY_ID' => $currentUserID)),
		//'filter_change_today' => array('name' => GetMessage('CRM_PRESET_CHANGE_TODAY'), 'fields' => array('DATE_MODIFY_datesel' => 'today')),
		//'filter_change_yesterday' => array('name' => GetMessage('CRM_PRESET_CHANGE_YESTERDAY'), 'fields' => array('DATE_MODIFY_datesel' => 'yesterday')),
		'filter_change_my' => array('name' => GetMessage('CRM_PRESET_CHANGE_MY'), 'fields' => array('MODIFY_BY_ID_name' => $currentUserName, 'MODIFY_BY_ID' => $currentUserID))
	);
}

// Headers initialization -->
$arResult['HEADERS'] = array(
	array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID'), 'sort' => 'id', 'default' => false, 'editable' => false, 'type' => 'int'),
	array('id' => 'COMPANY_SUMMARY', 'name' => GetMessage('CRM_COLUMN_COMPANY'), 'sort' => 'title', 'default' => true, 'editable' => false)
);

// Dont display activities in INTERNAL mode.
if(!$bInternal)
{
	$arResult['HEADERS'][] = array('id' => 'ACTIVITY_ID', 'name' => GetMessage('CRM_COLUMN_ACTIVITY'), 'sort' => 'activity_time', 'default' => true);
}

$arResult['HEADERS'] = array_merge(
	$arResult['HEADERS'],
	array(
		array('id' => 'LOGO', 'name' => GetMessage('CRM_COLUMN_LOGO'), 'sort' => false, 'default' => false, 'editable' => false),
		array('id' => 'TITLE', 'name' => GetMessage('CRM_COLUMN_TITLE'), 'sort' => 'title', 'default' => false, 'editable' => true),
		array('id' => 'COMPANY_TYPE', 'name' => GetMessage('CRM_COLUMN_COMPANY_TYPE'), 'sort' => 'company_type', 'default' => false, 'editable' => array('items' => CCrmStatus::GetStatusList('COMPANY_TYPE')), 'type' => 'list'),
		array('id' => 'EMPLOYEES', 'name' => GetMessage('CRM_COLUMN_EMPLOYEES'), 'sort' => 'employees', 'default' => false, 'editable' => array('items' => CCrmStatus::GetStatusList('EMPLOYEES')), 'type' => 'list')
	)
);

$CCrmFieldMulti->PrepareListHeaders($arResult['HEADERS']);
if($sExportType !== '')
{
	$CCrmFieldMulti->ListAddHeaders($arResult['HEADERS']);
}

$arResult['HEADERS'] = array_merge($arResult['HEADERS'], array(
	array('id' => 'ASSIGNED_BY', 'name' => GetMessage('CRM_COLUMN_ASSIGNED_BY'), 'sort' => 'assigned_by', 'default' => true, 'editable' => false),
	array('id' => 'ADDRESS', 'name' => GetMessage('CRM_COLUMN_ADDRESS'), 'sort' => false, 'default' => false, 'editable' => false),
	array('id' => 'ADDRESS_LEGAL', 'name' => GetMessage('CRM_COLUMN_ADDRESS_LEGAL'), 'sort' => false, 'default' => false, 'editable' => false),
	array('id' => 'BANKING_DETAILS', 'name' => GetMessage('CRM_COLUMN_BANKING_DETAILS'), 'sort' => false, 'default' => false, 'editable' => false),
	array('id' => 'INDUSTRY', 'name' => GetMessage('CRM_COLUMN_INDUSTRY'), 'sort' => 'industry', 'default' => false, 'editable' => array('items' => CCrmStatus::GetStatusList('INDUSTRY')), 'type' => 'list'),
	array('id' => 'REVENUE', 'name' => GetMessage('CRM_COLUMN_REVENUE'), 'sort' => 'revenue', 'default' => false, 'editable' => true),
	array('id' => 'CURRENCY_ID', 'name' => GetMessage('CRM_COLUMN_CURRENCY_ID'), 'sort' => 'currency_id', 'default' => false, 'editable' => array('items' => CCrmCurrencyHelper::PrepareListItems()), 'type' => 'list'),
	array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_COMMENTS'), 'sort' => false, 'default' => false, 'editable' => false),
	array('id' => 'CREATED_BY', 'name' => GetMessage('CRM_COLUMN_CREATED_BY'), 'sort' => 'created_by', 'default' => false, 'editable' => false),
	array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'sort' => 'date_create', 'default' => false, 'editable' => false),
	array('id' => 'MODIFY_BY', 'name' => GetMessage('CRM_COLUMN_MODIFY_BY'), 'sort' => 'modify_by', 'default' => false, 'editable' => false),
	array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_DATE_MODIFY'), 'sort' => 'date_modify', 'default' => false, 'editable' => false)
));
$CCrmUserType->ListAddHeaders($arResult['HEADERS']);
if (IsModuleInstalled('bizproc'))
{
	$arBPData = CBPDocument::GetWorkflowTemplatesForDocumentType(array('crm', 'CCrmDocumentCompany', 'COMPANY'));
	$arDocumentStates = CBPDocument::GetDocumentStates(
		array('crm', 'CCrmDocumentCompany', 'COMPANY'),
		null
	);
	foreach ($arBPData as $arBP)
	{
		if (!CBPDocument::CanUserOperateDocumentType(
			CBPCanUserOperateOperation::ViewWorkflow,
			$USER->GetID(),
			array('crm', 'CCrmDocumentCompany', 'COMPANY'),
			array(
				'UserGroups' => $CCrmBizProc->arCurrentUserGroups,
				'DocumentStates' => $arDocumentStates,
				'WorkflowTemplateId' => $arBP['ID'],
				'UserIsAdmin' => $USER->IsAdmin(),
				'CRMPermission' => $arResult['PERMS']['READ']
			)
		))
		{
			continue;
		}
		$arResult['HEADERS'][] = array('id' => 'BIZPROC_'.$arBP['ID'], 'name' => $arBP['NAME'], 'sort' => false, 'default' => false, 'editable' => false);
	}
}
// <-- Headers initialization

// Try to extract user action data -->
// We have to extract them before call of CGridOptions::GetFilter() or the custom filter will be corrupted.
$actionData = array(
	'METHOD' => $_SERVER['REQUEST_METHOD'],
	'ACTIVE' => false
);
if(check_bitrix_sessid())
{
	$postAction = 'action_button_'.$arResult['GRID_ID'];
	$getAction = 'action_'.$arResult['GRID_ID'];
	if ($actionData['METHOD'] == 'POST' && isset($_POST[$postAction]))
	{
		$actionData['ACTIVE'] = true;

		$actionData['NAME'] = $_POST[$postAction];
		unset($_POST[$postAction], $_REQUEST[$postAction]);

		$allRows = 'action_all_rows_'.$arResult['GRID_ID'];
		$actionData['ALL_ROWS'] = false;
		if(isset($_POST[$allRows]))
		{
			$actionData['ALL_ROWS'] = $_POST[$allRows] == 'Y';
			unset($_POST[$allRows], $_REQUEST[$allRows]);
		}

		if(isset($_POST['ID']))
		{
			$actionData['ID'] = $_POST['ID'];
			unset($_POST['ID'], $_REQUEST['ID']);
		}

		if(isset($_POST['FIELDS']))
		{
			$actionData['FIELDS'] = $_POST['FIELDS'];
			unset($_POST['FIELDS'], $_REQUEST['FIELDS']);
		}

		$actionData['AJAX_CALL'] = false;
		if(isset($_POST['AJAX_CALL']))
		{
			$actionData['AJAX_CALL']  = true;
			// Must be transfered to main.interface.grid
			//unset($_POST['AJAX_CALL'], $_REQUEST['AJAX_CALL']);
		}
	}
	elseif ($actionData['METHOD'] == 'GET' && isset($_GET[$getAction]))
	{
		$actionData['ACTIVE'] = true;

		$actionData['NAME'] = $_GET[$getAction];
		unset($_GET[$getAction], $_REQUEST[$getAction]);

		if(isset($_GET['ID']))
		{
			$actionData['ID'] = $_GET['ID'];
			unset($_GET['ID'], $_REQUEST['ID']);
		}

		$actionData['AJAX_CALL'] = false;
		if(isset($_GET['AJAX_CALL']))
		{
			$actionData['AJAX_CALL']  = true;
			// Must be transfered to main.interface.grid
			//unset($_GET['AJAX_CALL'], $_REQUEST['AJAX_CALL']);
		}
	}
}
// <-- Try to extract user action data

// HACK: for clear filter by CREATED_BY_ID, MODIFY_BY_ID and ASSIGNED_BY_ID
if($_SERVER['REQUEST_METHOD'] === 'GET')
{
	if(isset($_REQUEST['CREATED_BY_ID_name']) && $_REQUEST['CREATED_BY_ID_name'] === '')
	{
		$_REQUEST['CREATED_BY_ID'] = $_GET['CREATED_BY_ID'] = array();
	}

	if(isset($_REQUEST['MODIFY_BY_ID_name']) && $_REQUEST['MODIFY_BY_ID_name'] === '')
	{
		$_REQUEST['MODIFY_BY_ID'] = $_GET['MODIFY_BY_ID'] = array();
	}

	if(isset($_REQUEST['ASSIGNED_BY_ID_name']) && $_REQUEST['ASSIGNED_BY_ID_name'] === '')
	{
		$_REQUEST['ASSIGNED_BY_ID'] = $_GET['ASSIGNED_BY_ID'] = array();
	}
}

if (intval($arParams['COMPANY_COUNT']) <= 0)
	$arParams['COMPANY_COUNT'] = 20;

$arNavParams = array(
	'nPageSize' => $arParams['COMPANY_COUNT']
);

$arNavigation = CDBResult::GetNavParams($arNavParams);
$CGridOptions = new CCrmGridOptions($arResult['GRID_ID']);
$arNavParams = $CGridOptions->GetNavParams($arNavParams);
$arNavParams['bShowAll'] = false;

$arFilter += $CGridOptions->GetFilter($arResult['FILTER']);

$USER_FIELD_MANAGER->AdminListAddFilter(CCrmCompany::$sUFEntityID, $arFilter);

// converts data from filter
if (isset($arFilter['FIND_list']) && !empty($arFilter['FIND']))
{
	$arFilter[strtoupper($arFilter['FIND_list'])] = $arFilter['FIND'];
	unset($arFilter['FIND_list'], $arFilter['FIND']);
}

CCrmEntityHelper::PrepareMultiFieldFilter($arFilter);
$arImmutableFilters = array(
	'FM', 'ID', 'CURRENCY_ID',
	'ASSIGNED_BY_ID', 'CREATED_BY_ID', 'MODIFY_BY_ID',
	'COMPANY_TYPE', 'INDUSTRY', 'EMPLOYEES'
);
foreach ($arFilter as $k => $v)
{
	if(in_array($k, $arImmutableFilters, true))
	{
		continue;
	}

	$arMatch = array();

	if($k === 'ORIGINATOR_ID')
	{
		// HACK: build filter by internal entities
		$arFilter['=ORIGINATOR_ID'] = $v !== '__INTERNAL' ? $v : null;
		unset($arFilter[$k]);
	}
	elseif (preg_match('/(.*)_from$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
	{
		if(strlen($v) > 0)
		{
			$arFilter['>='.$arMatch[1]] = $v;
		}
		unset($arFilter[$k]);
	}
	elseif (preg_match('/(.*)_to$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
	{
		if(strlen($v) > 0)
		{
			if (($arMatch[1] == 'DATE_CREATE' || $arMatch[1] == 'DATE_MODIFY') && !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/'.BX_UTF_PCRE_MODIFIER, $v))
			{
				$v .=  ' 23:59:59';
			}
			$arFilter['<='.$arMatch[1]] = $v;
		}
		unset($arFilter[$k]);
	}
	elseif (in_array($k, $arResult['FILTER2LOGIC']))
	{
		// Bugfix #26956 - skip empty values in logical filter
		$v = trim($v);
		if($v !== '')
		{
			$arFilter['?'.$k] = $v;
		}
		unset($arFilter[$k]);
	}
	elseif ($k != 'ID' && $k != 'LOGIC' && strpos($k, 'UF_') !== 0)
	{
		$arFilter['%'.$k] = $v;
		unset($arFilter[$k]);
	}
}

// POST & GET actions processing -->
if($actionData['ACTIVE'])
{
	if ($actionData['METHOD'] == 'POST')
	{
		if($actionData['NAME'] == 'delete')
		{
			if ((isset($actionData['ID']) && is_array($actionData['ID'])) || $actionData['ALL_ROWS'])
			{
				$arFilterDel = array();
				if (!$actionData['ALL_ROWS'])
				{
					$arFilterDel = array('ID' => $actionData['ID']);
				}
				else
				{
					// Fix for issue #26628
					$arFilterDel += $arFilter;
				}

				$obRes = CCrmCompany::GetList(array(), $arFilterDel, array('ID'));
				while($arCompany = $obRes->Fetch())
				{
					$ID = $arCompany['ID'];
					$arEntityAttr = $CCrmCompany->cPerms->GetEntityAttr('COMPANY', array($ID));
					if (!$CCrmCompany->cPerms->CheckEnityAccess('COMPANY', 'DELETE', $arEntityAttr[$ID]))
					{
						continue ;
					}

					$DB->StartTransaction();

					if ($CCrmBizProc->Delete($ID, $arEntityAttr[$ID])
						&& $CCrmCompany->Delete($ID))
					{
						$DB->Commit();
					}
					else
					{
						$DB->Rollback();
					}
				}
			}
		}
		elseif($actionData['NAME'] == 'edit')
		{
			if(isset($actionData['FIELDS']) && is_array($actionData['FIELDS']))
			{
				foreach($actionData['FIELDS'] as $ID => $arSrcData)
				{
					$arEntityAttr = $CCrmCompany->cPerms->GetEntityAttr('COMPANY', array($ID));
					if (!$CCrmCompany->cPerms->CheckEnityAccess('COMPANY', 'WRITE', $arEntityAttr[$ID]))
					{
						continue ;
					}

					$arUpdateData = array();
					reset($arResult['HEADERS']);
					foreach ($arResult['HEADERS'] as $arHead)
					{
						if (isset($arHead['editable']) && $arHead['editable'] == true && isset($arSrcData[$arHead['id']]))
						{
							$arUpdateData[$arHead['id']] = $arSrcData[$arHead['id']];
						}
					}
					if (!empty($arUpdateData))
					{
						$DB->StartTransaction();

						if($CCrmCompany->Update($ID, $arUpdateData, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
						{
							$DB->Commit();

							$arErrors = array();
							CCrmBizProcHelper::AutoStartWorkflows(
								CCrmOwnerType::Company,
								$ID,
								CCrmBizProcEventType::Edit,
								$arErrors
							);
						}
						else
						{
							$DB->Rollback();
						}
					}
				}
			}
		}
		elseif ($actionData['NAME'] == 'tasks')
		{
			if (isset($actionData['ID']) && is_array($actionData['ID']))
			{
				$arTaskID = array();
				foreach($actionData['ID'] as $ID)
				{
					$arTaskID[] = 'CO_'.$ID;
				}

				$APPLICATION->RestartBuffer();

				$taskUrl = CHTTP::urlAddParams(
					CComponentEngine::MakePathFromTemplate(
						COption::GetOptionString('tasks', 'paths_task_user_edit', ''),
						array(
							'task_id' => 0,
							'user_id' => $USER->GetID()
						)
					),
					array(
						'UF_CRM_TASK' => implode(';', $arTaskID),
						'TITLE' => urlencode(GetMessage('CRM_TASK_TITLE_PREFIX')),
						'TAGS' => urlencode(GetMessage('CRM_TASK_TAG')),
						'back_url' => urlencode($arParams['PATH_TO_COMPANY_LIST'])
					)
				);
				if ($actionData['AJAX_CALL'])
				{
					echo '<script> parent.window.location = "'.CUtil::JSEscape($taskUrl).'";</script>';
					exit();
				}
				else
				{
					LocalRedirect($taskUrl);
				}
			}
		}
		if (!$actionData['AJAX_CALL'])
		{
			LocalRedirect($arParams['PATH_TO_COMPANY_LIST']);
		}
	}
	else//if ($actionData['METHOD'] == 'GET')
	{
		if ($actionData['NAME'] == 'delete' && isset($actionData['ID']))
		{
			$ID = intval($actionData['ID']);

			$arEntityAttr = $CCrmCompany->cPerms->GetEntityAttr('COMPANY', array($ID));
			$attr = $arEntityAttr[$ID];

			if($CCrmCompany->cPerms->CheckEnityAccess('COMPANY', 'DELETE', $attr))
			{
				$DB->StartTransaction();

				if($CCrmBizProc->Delete($ID, $attr)
					&& $CCrmCompany->Delete($ID))
				{
					$DB->Commit();
				}
				else
				{
					$DB->Rollback();
				}
			}
		}

		if (!$actionData['AJAX_CALL'])
		{
			LocalRedirect($bInternal ? '?'.$arParams['FORM_ID'].'_active_tab=tab_company' : $arParams['PATH_TO_COMPANY_LIST']);
		}
	}
}
// <-- POST & GET actions processing

if (!$bInternal && isset($_REQUEST['clear_filter']) && $_REQUEST['clear_filter'] == 'Y')
{
	$urlParams = array();
	foreach($arResult['FILTER'] as $id => $arFilter)
	{
		if ($arFilter['type'] == 'user')
		{
			$urlParams[] = $arFilter['id'];
			$urlParams[] = $arFilter['id'].'_name';
		}
		else
			$urlParams[] = $arFilter['id'];
	}
	$urlParams[] = 'clear_filter';
	$CGridOptions->GetFilter(array());
	LocalRedirect($APPLICATION->GetCurPageParam('', $urlParams));
}

$_arSort = $CGridOptions->GetSorting(array(
	'sort' => array('activity_time' => 'asc'),
	'vars' => array('by' => 'by', 'order' => 'order')
));
$arResult['SORT'] = !empty($arSort) ? $arSort : $_arSort['sort'];
$arResult['SORT_VARS'] = $_arSort['vars'];

// Remove column for deleted UF
$arSelect = $CGridOptions->GetVisibleColumns();
if ($CCrmUserType->NormalizeFields($arSelect))
	$CGridOptions->SetVisibleColumns($arSelect);

// Fill in default values if empty
if (empty($arSelect))
{
	foreach ($arResult['HEADERS'] as $arHeader)
	{
		if ($arHeader['default'])
		{
			$arSelect[] = $arHeader['id'];
		}
	}
}

$arSelectedHeaders = $arSelect;

// Extend select with responsible user data
if(in_array('ASSIGNED_BY', $arSelect, true))
{
	$arSelect[] = 'ASSIGNED_BY_LOGIN';
	$arSelect[] = 'ASSIGNED_BY_NAME';
	$arSelect[] = 'ASSIGNED_BY_LAST_NAME';
	$arSelect[] = 'ASSIGNED_BY_SECOND_NAME';
}

if(in_array('COMPANY_SUMMARY', $arSelect, true))
{
	$arSelect[] = 'LOGO';
	$arSelect[] = 'TITLE';
	$arSelect[] = 'COMPANY_TYPE';
}

if(in_array('ACTIVITY_ID', $arSelect, true))
{
	$arSelect[] = 'ACTIVITY_TIME';
	$arSelect[] = 'ACTIVITY_SUBJECT';
	$arSelect[] = 'C_ACTIVITY_ID';
	$arSelect[] = 'C_ACTIVITY_TIME';
	$arSelect[] = 'C_ACTIVITY_SUBJECT';
	$arSelect[] = 'C_ACTIVITY_RESP_ID';
	$arSelect[] = 'C_ACTIVITY_RESP_LOGIN';
	$arSelect[] = 'C_ACTIVITY_RESP_NAME';
	$arSelect[] = 'C_ACTIVITY_RESP_LAST_NAME';
	$arSelect[] = 'C_ACTIVITY_RESP_SECOND_NAME';
}

// Extend select with creator data
if(in_array('CREATED_BY', $arSelect, true))
{
	$arSelect[] = 'CREATED_BY_LOGIN';
	$arSelect[] = 'CREATED_BY_NAME';
	$arSelect[] = 'CREATED_BY_LAST_NAME';
	$arSelect[] = 'CREATED_BY_SECOND_NAME';
}

// Extend select with editor data
if(in_array('MODIFY_BY', $arSelect, true))
{
	$arSelect[] = 'MODIFY_BY_LOGIN';
	$arSelect[] = 'MODIFY_BY_NAME';
	$arSelect[] = 'MODIFY_BY_LAST_NAME';
	$arSelect[] = 'MODIFY_BY_SECOND_NAME';
}

// ID must present in select
if(!in_array('ID', $arSelect))
{
	$arSelect[] = 'ID';
}

if ($sExportType != '')
{
	CCrmComponentHelper::PrepareExportFieldsList(
		$arSelectedHeaders,
		array(
			'COMPANY_SUMMARY' => array(
				'LOGO',
				'TITLE',
				'COMPANY_TYPE'
			),
			'ACTIVITY_ID' => array()
		)
	);

	if(!in_array('ID', $arSelectedHeaders))
	{
		$arSelectedHeaders[] = 'ID';
	}

	$arResult['SELECTED_HEADERS'] = $arSelectedHeaders;
}

$nTopCount = false;
if ($arResult['GADGET'] == 'Y')
{
	$arSelect = array('DATE_CREATE', 'TITLE', 'COMPANY_TYPE');
	$nTopCount = $arParams['COMPANY_COUNT'];
}

if($nTopCount > 0 && !isset($arFilter['ID']))
{
	$arNavParams['nTopCount'] = $nTopCount;
}

if (!empty($sExportType))
	$arFilter['PERMISSION'] = 'EXPORT';

// HACK: Make custom sort for ASSIGNED_BY
$arSort = $arResult['SORT'];
if(isset($arSort['assigned_by']))
{
	$arSort['assigned_by_last_name'] = $arSort['assigned_by'];
	$arSort['assigned_by_name'] = $arSort['assigned_by'];
	$arSort['assigned_by_login'] = $arSort['assigned_by'];
	unset($arSort['assigned_by']);
}

$arOptions = array('FIELD_OPTIONS' => array('ADDITIONAL_FIELDS' => array()));
if(in_array('ACTIVITY_ID', $arSelect, true))
{
	$arOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'ACTIVITY';
}
if(isset($arSort['activity_time']))
{
	$arOptions['NULLS_LAST'] = true;
}
$arSelect = array_unique($arSelect, SORT_STRING);
$obRes = CCrmCompany::GetListEx($arResult['SORT'], $arFilter, false, ($sExportType == '' ? $arNavParams : false), $arSelect, $arOptions);
if ($arResult['GADGET'] != 'Y' && $sExportType == '')
{
	$obRes->NavStart($arNavParams['nPageSize'], false);
}

$arResult['COMPANY'] = array();
$arResult['COMPANY_ID'] = array();
$arResult['COMPANY_UF'] = array();
$arResult['PERMS']['ADD']    = !$CCrmCompany->cPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'ADD');
$arResult['PERMS']['WRITE']  = !$CCrmCompany->cPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'WRITE');
$arResult['PERMS']['DELETE'] = !$CCrmCompany->cPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'DELETE');

$bDeal = !$CCrmCompany->cPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'ADD');
$arResult['PERM_DEAL'] = $bDeal;
$bContact = !$CCrmCompany->cPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'ADD');
$arResult['PERM_CONTACT'] = $bContact;
$now = time() + CTimeZone::GetOffset();

while($arCompany = $obRes->GetNext())
{
	if (!empty($arCompany['LOGO']))
	{
		if ($sExportType != '')
		{
			if ($arFile = CFile::GetFileArray($arCompany['LOGO']))
			{
				$arCompany['LOGO'] = CHTTP::URN2URI($arFile['SRC']);
			}
		}
		else
		{
			$arFileTmp = CFile::ResizeImageGet(
				$arCompany['LOGO'],
				array('width' => 50, 'height' => 50),
				BX_RESIZE_IMAGE_PROPORTIONAL,
				false
			);
			$arCompany['LOGO'] = CFile::ShowImage($arFileTmp['src'], 50, 50, 'border=0');
		}
	}

	$typeID = isset($arCompany['COMPANY_TYPE']) ? $arCompany['COMPANY_TYPE'] : '';
	$arCompany['COMPANY_TYPE_NAME'] = isset($arResult['COMPANY_TYPE_LIST'][$typeID]) ? $arResult['COMPANY_TYPE_LIST'][$typeID] : $typeID;

	$arCompany['PATH_TO_COMPANY_SHOW'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'],
		array(
			'company_id' => $arCompany['ID']
		)
	);
	$arCompany['PATH_TO_COMPANY_EDIT'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_EDIT'],
		array(
			'company_id' => $arCompany['ID']
		)
	);
	$arCompany['PATH_TO_COMPANY_COPY'] =  CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_EDIT'],
		array(
			'company_id' => $arCompany['ID']
		)),
		array('copy' => 1)
	);
	$arCompany['PATH_TO_COMPANY_DELETE'] =  CHTTP::urlAddParams(
		$bInternal ? $APPLICATION->GetCurPage() : $arParams['PATH_TO_COMPANY_LIST'],
		array('action_'.$arResult['GRID_ID'] => 'delete', 'ID' => $arCompany['ID'], 'sessid' => bitrix_sessid())
	);
	$arCompany['PATH_TO_USER_PROFILE'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
		array(
			'user_id' => $arCompany['ASSIGNED_BY']
		)
	);
	$arCompany['PATH_TO_USER_CREATOR'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
		array(
			'user_id' => $arCompany['CREATED_BY']
		)
	);

	$arCompany['PATH_TO_USER_MODIFIER'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
		array(
			'user_id' => $arCompany['MODIFY_BY']
		)
	);

	$arCompany['CREATED_BY_FORMATTED_NAME'] = CUser::FormatName($arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arCompany['CREATED_BY_LOGIN'],
			'NAME' => $arCompany['CREATED_BY_NAME'],
			'LAST_NAME' => $arCompany['CREATED_BY_LAST_NAME'],
			'SECOND_NAME' => $arCompany['CREATED_BY_SECOND_NAME']
		),
		true, false
	);

	$arCompany['MODIFY_BY_FORMATTED_NAME'] = CUser::FormatName($arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arCompany['MODIFY_BY_LOGIN'],
			'NAME' => $arCompany['MODIFY_BY_NAME'],
			'LAST_NAME' => $arCompany['MODIFY_BY_LAST_NAME'],
			'SECOND_NAME' => $arCompany['MODIFY_BY_SECOND_NAME']
		),
		true, false
	);

	if ($bContact)
		$arCompany['PATH_TO_CONTACT_EDIT'] = CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_CONTACT_EDIT'],
				array(
					'contact_id' => 0
				)
			),
			array('company_id' => $arCompany['ID'])
		);

	if ($bDeal)
	{
		$addParams = array('company_id' => $arCompany['ID']);
		if(isset($arParams['INTERNAL_CONTEXT']['CONTACT_ID']))
		{
			$addParams['contact_id'] = $arParams['INTERNAL_CONTEXT']['CONTACT_ID'];
		}
		$arCompany['PATH_TO_DEAL_EDIT'] = CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_DEAL_EDIT'],
				array(
					'deal_id' => 0
				)
			),
			$addParams
		);
	}

	if(isset($arCompany['~ACTIVITY_TIME']))
	{
		$time = MakeTimeStamp($arCompany['~ACTIVITY_TIME']);
		$arCompany['~ACTIVITY_EXPIRED'] = $time <= $now;
		$arCompany['~ACTIVITY_IS_CURRENT_DAY'] = $arCompany['~ACTIVITY_EXPIRED'] || CCrmActivity::IsCurrentDay($time);
	}

	if (IsModuleInstalled('tasks'))
	{
		$arCompany['PATH_TO_TASK_EDIT'] = CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(COption::GetOptionString('tasks', 'paths_task_user_edit', ''),
				array(
					'task_id' => 0,
					'user_id' => $USER->GetID()
				)
			),
			array(
				'UF_CRM_TASK' => 'CO_'.$arCompany['ID'],
				'TITLE' => urlencode(GetMessage('CRM_TASK_TITLE_PREFIX')),
				'TAGS' => urlencode(GetMessage('CRM_TASK_TAG')),
				'back_url' => urlencode($arParams['PATH_TO_COMPANY_LIST'])
			)
		);
	}

	if (IsModuleInstalled('bizproc'))
	{
		$arCompany['BIZPROC_STATUS'] = '';
		$arCompany['BIZPROC_STATUS_HINT'] = '';
		$arDocumentStates = CBPDocument::GetDocumentStates(
			array('crm', 'CCrmDocumentCompany', 'COMPANY'),
			array('crm', 'CCrmDocumentCompany', 'COMPANY_'.$arCompany['ID'])
		);

		$arCompany['PATH_TO_BIZPROC_LIST'] =  CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'],
			array(
				'company_id' => $arCompany['ID']
			)),
			array('CRM_COMPANY_SHOW_V12_active_tab' => 'tab_bizproc')
		);

		$iBPCountTask = 0;
		$iCntDocStates = count($arDocumentStates);
		foreach ($arDocumentStates as $arDocumentState)
		{
			$paramName = 'BIZPROC_'.$arDocumentState['TEMPLATE_ID'];
			if($sExportType !== '')
			{
				if (strlen($arDocumentState['STATE_TITLE']) > 0)
					$arCompany[$paramName] = $arDocumentState['STATE_TITLE'];
			}
			else
			{
				if (strlen($arDocumentState['STATE_TITLE']) > 0)
					$arCompany[$paramName] = '<a href="'.$arCompany['PATH_TO_BIZPROC_LIST'].'">'.$arDocumentState['STATE_TITLE'].'</a>';
				$arTasksWorkflow = CBPDocument::GetUserTasksForWorkflow($USER->GetID(), $arDocumentState['ID']);
				$iBPCountTask += empty($arTasksWorkflow) ? 0 : count($arTasksWorkflow);
				if (strlen($arDocumentState['ID']) > 0 && strlen($arDocumentState['WORKFLOW_STATUS']) > 0
					&& $arCompany['BIZPROC_STATUS'] != 'attention')
					$arCompany['BIZPROC_STATUS'] = (empty($arTasksWorkflow) ? 'inprogress' : 'attention');
				if ($iCntDocStates == 1)
				{
					$arCompany['BIZPROC_STATUS_HINT'] =
						'<div class=\'bizproc-item-title\'>'.
							(!empty($arDocumentState['TEMPLATE_NAME']) ? htmlspecialcharsbx(htmlspecialcharsbx($arDocumentState['TEMPLATE_NAME'])) : GetMessage('CRM_BPLIST')).': '.
							'<span class=\'bizproc-item-title bizproc-state-title\'>'.
								'<a href=\''.$arCompany['PATH_TO_BIZPROC_LIST'].'\'>'.
									(strlen($arDocumentState['STATE_TITLE']) > 0 ? htmlspecialcharsbx(htmlspecialcharsbx($arDocumentState['STATE_TITLE'])) : htmlspecialcharsbx(htmlspecialcharsbx($arDocumentState['STATE_NAME']))).
								'</a>'.
							'</span>'.
						'</div>';
				}
				else
				{
					$arCompany['BIZPROC_STATUS_HINT'] =
						'<span class=\'bizproc-item-title\'>'.
							GetMessage('CRM_BP_R_P').': <a href=\''.$arCompany['PATH_TO_BIZPROC_LIST'].'\' title=\''.GetMessage('CRM_BP_R_P_TITLE').'\'>'.count($arDocumentStates).'</a>'.
						'</span>'.
						(!empty($iBPCountTask)
							?
								'<br /><span class=\'bizproc-item-title\'>'.
								GetMessage('CRM_TASKS').': <a href=\''.$arCompany['PATH_TO_USER_BP'].'\' title=\''.GetMessage('CRM_TASKS_TITLE').'\'>'.$iBPCountTask.'</a></span>'
							:
								''
						);
				}
			}
		}
		if ($arCompany['BIZPROC_STATUS'] == '')
			$arCompany['BIZPROC_STATUS_HINT'] = '';
	}

	$arCompany['ASSIGNED_BY_ID'] = $arCompany['~ASSIGNED_BY_ID'] = intval($arCompany['ASSIGNED_BY']);
	$arCompany['ASSIGNED_BY'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arCompany['ASSIGNED_BY_LOGIN'],
			'NAME' => $arCompany['ASSIGNED_BY_NAME'],
			'LAST_NAME' => $arCompany['ASSIGNED_BY_LAST_NAME'],
			'SECOND_NAME' => $arCompany['ASSIGNED_BY_SECOND_NAME']
		),
		true, false
	);
	$arResult['COMPANY'][$arCompany['ID']] = $arCompany;
	$arResult['COMPANY_UF'][$arCompany['ID']] = array();
	$arResult['COMPANY_ID'][$arCompany['ID']] = $arCompany['ID'];
}

$arResult['ROWS_COUNT'] = $obRes->SelectedRowsCount();
$arResult['DB_LIST'] = $obRes;
$arResult['DB_FILTER'] = $arFilter;

$CCrmUserType->ListAddEnumFieldsValue($arResult, $arResult['COMPANY'], $arResult['COMPANY_UF'], ($sExportType !== '' ? ', ' : '<br />'), $sExportType !== '');

// adding crm multi field to result array
if (isset($arResult['COMPANY_ID']) && !empty($arResult['COMPANY_ID']))
{
	$arFmList = array();
	$res = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $arResult['COMPANY_ID']));
	while($ar = $res->Fetch())
	{
		if ($sExportType == '')
			$arFmList[$ar['ELEMENT_ID']][$ar['COMPLEX_ID']][] = CCrmFieldMulti::GetTemplateByComplex($ar['COMPLEX_ID'], $ar['VALUE']);
		else
			$arFmList[$ar['ELEMENT_ID']][$ar['COMPLEX_ID']][] = $ar['VALUE'];
		$arResult['COMPANY'][$ar['ELEMENT_ID']]['~'.$ar['COMPLEX_ID']][] = $ar['VALUE'];
	}
	foreach ($arFmList as $elementId => $arFM)
		foreach ($arFM as $complexId => $arComplexName)
			$arResult['COMPANY'][$elementId][$complexId] = implode(', ', $arComplexName);

	// checkig access for operation
	$arCompanyAttr = CCrmPerms::GetEntityAttr('COMPANY', $arResult['COMPANY_ID']);
	foreach ($arResult['COMPANY_ID'] as $iCompanyId)
	{
		$arResult['COMPANY'][$iCompanyId]['EDIT'] = $CCrmCompany->cPerms->CheckEnityAccess('COMPANY', 'WRITE', $arCompanyAttr[$iCompanyId]);
		$arResult['COMPANY'][$iCompanyId]['DELETE'] = $CCrmCompany->cPerms->CheckEnityAccess('COMPANY', 'DELETE', $arCompanyAttr[$iCompanyId]);

		$arResult['COMPANY'][$iCompanyId]['BIZPROC_LIST'] = array();
		foreach ($arBPData as $arBP)
		{
			if (!CBPDocument::CanUserOperateDocument(
				CBPCanUserOperateOperation::StartWorkflow,
				$USER->GetID(),
				array('crm', 'CCrmDocumentCompany', 'COMPANY_'.$arResult['COMPANY'][$iCompanyId]['ID']),
				array(
					'UserGroups' => $CCrmBizProc->arCurrentUserGroups,
					'DocumentStates' => $arDocumentStates,
					'WorkflowTemplateId' => $arBP['ID'],
					'CreatedBy' => $arResult['COMPANY'][$iCompanyId]['~ASSIGNED_BY'],
					'UserIsAdmin' => $USER->IsAdmin(),
					'CRMEntityAttr' =>  $arCompanyAttr[$iCompanyId]
				)
			))
			{
				continue;
			}

			$arBP['PATH_TO_BIZPROC_START'] = CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'],
				array(
					'company_id' => $arResult['COMPANY'][$iCompanyId]['ID']
				)),
				array(
					'workflow_template_id' => $arBP['ID'], 'bizproc_start' => 1,  'sessid' => bitrix_sessid(),
					'CRM_COMPANY_SHOW_V12_active_tab' => 'tab_bizproc', 'backurl' => $arParams['PATH_TO_COMPANY_LIST'])
			);
			$arResult['COMPANY'][$iCompanyId]['BIZPROC_LIST'][] = $arBP;
		}
	}
}

if ($sExportType == '')
{
	$this->IncludeComponentTemplate();

	include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.company/include/nav.php');

	return $arResult['ROWS_COUNT'];
}
else
{
	$APPLICATION->RestartBuffer();
	// hack. any '.default' customized template should contain 'excel' page
	$this->__templateName = '.default';

	if($sExportType === 'carddav')
	{
		Header('Content-Type: text/vcard');
	}
	elseif($sExportType === 'csv')
	{
		Header('Content-Type: text/csv');
		Header('Content-Disposition: attachment;filename=companies.csv');
	}
	elseif($sExportType === 'excel')
	{
		Header('Content-Type: application/vnd.ms-excel');
		Header('Content-Disposition: attachment;filename=companies.xls');
	}
	Header('Content-Type: application/octet-stream');
	Header('Content-Transfer-Encoding: binary');

	// add UTF-8 BOM marker
	if (defined('BX_UTF') && BX_UTF)
		echo chr(239).chr(187).chr(191);

	$this->IncludeComponentTemplate($sExportType);

	die();
}
?>
