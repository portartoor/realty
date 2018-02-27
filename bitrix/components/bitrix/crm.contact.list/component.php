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

global $USER_FIELD_MANAGER, $USER, $APPLICATION, $DB;

$CCrmPerms = CCrmPerms::GetCurrentUserPermissions();
if ($CCrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$CCrmContact = new CCrmContact(false);
$CCrmBizProc = new CCrmBizProc('CONTACT');

$arResult['CURRENT_USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();
$arParams['PATH_TO_CONTACT_LIST'] = CrmCheckPath('PATH_TO_CONTACT_LIST', $arParams['PATH_TO_CONTACT_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
$arParams['PATH_TO_CONTACT_EDIT'] = CrmCheckPath('PATH_TO_CONTACT_EDIT', $arParams['PATH_TO_CONTACT_EDIT'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&edit');
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['PATH_TO_USER_BP'] = CrmCheckPath('PATH_TO_USER_BP', $arParams['PATH_TO_USER_BP'], '/company/personal/bizproc/');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

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
if (!empty($sExportType) && $CCrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'EXPORT'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmContact::$sUFEntityID);
$CCrmFieldMulti = new CCrmFieldMulti();

$arResult['GRID_ID'] = 'CRM_CONTACT_LIST_V12'.($bInternal && !empty($arParams['GRID_ID_SUFFIX']) ? '_'.$arParams['GRID_ID_SUFFIX'] : '');

$arResult['TYPE_LIST'] = CCrmStatus::GetStatusListEx('CONTACT_TYPE');
$arResult['SOURCE_LIST'] = CCrmStatus::GetStatusListEx('SOURCE');
$arResult['EXPORT_LIST'] = array('Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'));
$arResult['FILTER'] = array();
$arResult['FILTER2LOGIC'] = array();
$arResult['FILTER_PRESETS'] = array();

if (!$bInternal)
{
	$arResult['FILTER2LOGIC'] = array('TITLE', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'POST', 'ADDRESS', 'COMMENTS');
	ob_start();
	$GLOBALS["APPLICATION"]->IncludeComponent('bitrix:crm.entity.selector',
		'',
		array(
			'ENTITY_TYPE' => 'COMPANY',
			'INPUT_NAME' => 'COMPANY_ID',
			'INPUT_VALUE' => isset($_REQUEST['COMPANY_ID']) ? intval($_REQUEST['COMPANY_ID']) : '',
			'FORM_NAME' => $arResult['GRID_ID'],
			'MULTIPLE' => 'N',
			'FILTER' => true,
		),
		false,
		array('HIDE_ICONS' => 'Y')
	);
	$sValCompany = ob_get_contents();
	ob_end_clean();

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
			'full_name' => GetMessage('CRM_COLUMN_TITLE_NAME_LAST_NAME'),
			'email' => GetMessage('CRM_COLUMN_EMAIL'),
			'phone' => GetMessage('CRM_COLUMN_PHONE'))
		),
		array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID')),
		array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_NAME')),
		array('id' => 'LAST_NAME', 'name' => GetMessage('CRM_COLUMN_LAST_NAME')),
		array('id' => 'SECOND_NAME', 'name' => GetMessage('CRM_COLUMN_SECOND_NAME')),
		array('id' => 'COMPANY_ID', 'default' => 'Y', 'name' => GetMessage('CRM_COLUMN_COMPANY_LIST'), 'type' => 'custom', 'value' => $sValCompany),
		array('id' => 'COMPANY_TITLE', 'default' => false, 'name' => GetMessage('CRM_COLUMN_COMPANY_TITLE')),
		array('id' => 'PHONE', 'name' => GetMessage('CRM_COLUMN_PHONE')),
		array('id' => 'EMAIL', 'name' => GetMessage('CRM_COLUMN_EMAIL')),
		array('id' => 'WEB', 'name' => GetMessage('CRM_COLUMN_WEB')),
		array('id' => 'IM', 'name' => GetMessage('CRM_COLUMN_MESSENGER')),
		//array('id' => 'BIRTHDATE', 'name' => GetMessage('CRM_COLUMN_BIRTHDATE'), 'type' => 'date'),
		array('id' => 'POST', 'name' => GetMessage('CRM_COLUMN_POST')),
		array('id' => 'ADDRESS', 'name' => GetMessage('CRM_COLUMN_ADDRESS')),
		array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_COMMENTS')),
		array('id' => 'TYPE_ID', 'params' => array('multiple' => 'Y'), 'name' => GetMessage('CRM_COLUMN_TYPE'), 'default' => 'Y', 'type' => 'list', 'items' => CCrmStatus::GetStatusList('CONTACT_TYPE')),
		array('id' => 'SOURCE_ID', 'params' => array('multiple' => 'Y'), 'name' => GetMessage('CRM_COLUMN_SOURCE'), 'type' => 'list', 'items' => CCrmStatus::GetStatusList('SOURCE')),
		array('id' => 'EXPORT', 'name' => GetMessage('CRM_COLUMN_EXPORT'), 'type' => 'list', 'items' => array('' => '', 'Y' => GetMessage('MAIN_YES'), 'N' => GetMessage('MAIN_NO'))),
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
	array('id' => 'CONTACT_SUMMARY', 'name' => GetMessage('CRM_COLUMN_CONTACT'), 'sort' => 'full_name', 'default' => true, 'editable' => false),
);

// Dont display activities in INTERNAL mode.
if(!$bInternal)
{
	$arResult['HEADERS'][] = array('id' => 'ACTIVITY_ID', 'name' => GetMessage('CRM_COLUMN_ACTIVITY'), 'sort' => 'activity_time', 'default' => true);
}

$arResult['HEADERS'] = array_merge(
	$arResult['HEADERS'],
	array(
		array('id' => 'CONTACT_COMPANY', 'name' => GetMessage('CRM_COLUMN_COMPANY_ID'), 'sort' => 'company_title', 'default' => true, 'editable' => false),
		array('id' => 'PHOTO', 'name' => GetMessage('CRM_COLUMN_PHOTO'), 'sort' => false, 'default' => false, 'editable' => false),
		array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_NAME'), 'sort' => 'name', 'default' => false, 'editable' => true),
		array('id' => 'LAST_NAME', 'name' => GetMessage('CRM_COLUMN_LAST_NAME'), 'sort' => 'last_name', 'default' => false, 'editable' => true),
		array('id' => 'SECOND_NAME', 'name' => GetMessage('CRM_COLUMN_SECOND_NAME'), 'sort' => 'second_name', 'default' => false, 'editable' => true),
		//array('id' => 'BIRTHDATE', 'name' => GetMessage('CRM_COLUMN_BIRTHDATE'), 'sort' => 'birthdate', 'default' => false, 'editable' => true, 'type' => 'date'),
		array('id' => 'POST', 'name' => GetMessage('CRM_COLUMN_POST'), 'sort' => 'post', 'default' => false, 'editable' => true),
		array('id' => 'COMPANY_ID', 'name' => GetMessage('CRM_COLUMN_COMPANY_ID'), 'sort' => 'company_title', 'default' => false, 'editable' => false),
		array('id' => 'TYPE_ID', 'name' => GetMessage('CRM_COLUMN_TYPE'), 'sort' => 'type_id', 'default' => false, 'editable' => array('items' => CCrmStatus::GetStatusList('CONTACT_TYPE')), 'type' => 'list')
	)
);

$CCrmFieldMulti->PrepareListHeaders($arResult['HEADERS']);
if($sExportType !== '')
{
	$CCrmFieldMulti->ListAddHeaders($arResult['HEADERS']);
}

$arResult['HEADERS'] = array_merge(
	$arResult['HEADERS'],
	array(
		array('id' => 'ASSIGNED_BY', 'name' => GetMessage('CRM_COLUMN_ASSIGNED_BY'), 'sort' => 'assigned_by', 'default' => true, 'editable' => false),
		array('id' => 'ADDRESS', 'name' => GetMessage('CRM_COLUMN_ADDRESS'), 'sort' => false /**because of MSSQL**/, 'default' => false, 'editable' => false),
		array('id' => 'COMMENTS', 'name' => GetMessage('CRM_COLUMN_COMMENTS'), 'sort' => false /**because of MSSQL**/, 'default' => false, 'editable' => false),
		array('id' => 'SOURCE_ID', 'name' => GetMessage('CRM_COLUMN_SOURCE'), 'sort' => 'source_id', 'default' => false, 'editable' => array('items' => CCrmStatus::GetStatusList('SOURCE')), 'type' => 'list'),
		array('id' => 'SOURCE_DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_SOURCE_DESCRIPTION'), 'sort' => false /**because of MSSQL**/, 'default' => false, 'editable' => false),
		array('id' => 'EXPORT', 'name' => GetMessage('CRM_COLUMN_EXPORT'), 'type' => 'checkbox', 'default' => false, 'editable' => true),
		array('id' => 'CREATED_BY', 'name' => GetMessage('CRM_COLUMN_CREATED_BY'), 'sort' => 'created_by', 'default' => false, 'editable' => false),
		array('id' => 'DATE_CREATE', 'name' => GetMessage('CRM_COLUMN_DATE_CREATE'), 'sort' => 'date_create', 'default' => false),
		array('id' => 'MODIFY_BY', 'name' => GetMessage('CRM_COLUMN_MODIFY_BY'), 'sort' => 'modify_by', 'default' => false, 'editable' => false),
		array('id' => 'DATE_MODIFY', 'name' => GetMessage('CRM_COLUMN_DATE_MODIFY'), 'sort' => 'date_modify', 'default' => false)
	)
);

$CCrmUserType->ListAddHeaders($arResult['HEADERS']);
if (IsModuleInstalled('bizproc'))
{
	$arBPData = CBPDocument::GetWorkflowTemplatesForDocumentType(array('crm', 'CCrmDocumentContact', 'CONTACT'));
	$arDocumentStates = CBPDocument::GetDocumentStates(
		array('crm', 'CCrmDocumentContact', 'CONTACT'),
		null
	);
	foreach ($arBPData as $arBP)
	{
		if (!CBPDocument::CanUserOperateDocumentType(
			CBPCanUserOperateOperation::StartWorkflow,
			$USER->GetID(),
			array('crm', 'CCrmDocumentContact', 'CONTACT'),
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
unset($arHeader);
// <-- Headers initialization

// Try to extract user action data -->
// We have to extract them before call of CGridOptions::GetFilter() overvise the custom filter will be corrupted.
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

		if(isset($_POST['ACTION_ASSIGNED_BY_ID']))
		{
			$assignedByID = 0;
			if(!is_array($_POST['ACTION_ASSIGNED_BY_ID']))
			{
				$assignedByID = intval($_POST['ACTION_ASSIGNED_BY_ID']);
			}
			elseif(count($_POST['ACTION_ASSIGNED_BY_ID']) > 0)
			{
				$assignedByID = intval($_POST['ACTION_ASSIGNED_BY_ID'][0]);
			}

			$actionData['ASSIGNED_BY_ID'] = $assignedByID;
			unset($_POST['ACTION_ASSIGNED_BY_ID'], $_REQUEST['ACTION_ASSIGNED_BY_ID']);
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

if (intval($arParams['CONTACT_COUNT']) <= 0)
	$arParams['CONTACT_COUNT'] = 20;

$arNavParams = array(
	'nPageSize' => $arParams['CONTACT_COUNT']
);

$arNavigation = CDBResult::GetNavParams($arNavParams);
$CGridOptions = new CCrmGridOptions($arResult['GRID_ID']);
$arNavParams = $CGridOptions->GetNavParams($arNavParams);
$arNavParams['bShowAll'] = false;
$arFilter += $CGridOptions->GetFilter($arResult['FILTER']);
$USER_FIELD_MANAGER->AdminListAddFilter(CCrmContact::$sUFEntityID, $arFilter);
// converts data from filter
if (isset($arFilter['FIND_list']) && !empty($arFilter['FIND']))
{
	$arFilter[strtoupper($arFilter['FIND_list'])] = $arFilter['FIND'];
	unset($arFilter['FIND_list'], $arFilter['FIND']);
}

CCrmEntityHelper::PrepareMultiFieldFilter($arFilter);
$arImmutableFilters = array(
	'FM', 'ID', 'COMPANY_ID',
	'ASSIGNED_BY_ID', 'CREATED_BY_ID', 'MODIFY_BY_ID',
	'TYPE_ID', 'SOURCE_ID'
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
	elseif ($k != 'LOGIC' && strpos($k, 'UF_') !== 0)
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

				$obRes = CCrmContact::GetList(array(), $arFilterDel, array('ID'));
				while($arContact = $obRes->Fetch())
				{
					$ID = $arContact['ID'];
					$arEntityAttr = $CCrmPerms->GetEntityAttr('CONTACT', array($ID));
					if (!$CCrmPerms->CheckEnityAccess('CONTACT', 'DELETE', $arEntityAttr[$ID]))
					{
						continue ;
					}

					$DB->StartTransaction();

					if ($CCrmBizProc->Delete($ID, $arEntityAttr[$ID])
						&& $CCrmContact->Delete($ID))
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
					$arEntityAttr = $CCrmPerms->GetEntityAttr('CONTACT', array($ID));
					if (!$CCrmPerms->CheckEnityAccess('CONTACT', 'WRITE', $arEntityAttr[$ID]))
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

						if($CCrmContact->Update($ID, $arUpdateData, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
						{
							$DB->Commit();

							$arErrors = array();
							CCrmBizProcHelper::AutoStartWorkflows(
								CCrmOwnerType::Contact,
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
					$arTaskID[] = 'C_'.$ID;
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
						'back_url' => urlencode($arParams['PATH_TO_CONTACT_LIST'])
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
		elseif ($actionData['NAME'] == 'assign_to')
		{
			if(isset($actionData['ASSIGNED_BY_ID']))
			{
				$arIDs = array();
				if ($actionData['ALL_ROWS'])
				{
					$arActionFilter = $arFilter;
					$arActionFilter['CHECK_PERMISSIONS'] = 'N'; // Ignore 'WRITE' permission - we will check it before update.
					$dbRes = CCrmContact::GetListEx(array(), $arActionFilter, false, false, array('ID'));
					while($arContact = $dbRes->Fetch())
					{
						$arIDs[] = $arContact['ID'];
					}
				}
				elseif (isset($actionData['ID']) && is_array($actionData['ID']))
				{
					$arIDs = $actionData['ID'];
				}

				$arEntityAttr = $CCrmPerms->GetEntityAttr('CONTACT', $arIDs);


				foreach($arIDs as $ID)
				{
					if (!$CCrmPerms->CheckEnityAccess('CONTACT', 'WRITE', $arEntityAttr[$ID]))
					{
						continue;
					}

					$DB->StartTransaction();

					$arUpdateData = array(
						'ASSIGNED_BY_ID' => $actionData['ASSIGNED_BY_ID']
					);

					if($CCrmContact->Update($ID, $arUpdateData, true, true, array('DISABLE_USER_FIELD_CHECK' => true)))
					{
						$DB->Commit();

						$arErrors = array();
						CCrmBizProcHelper::AutoStartWorkflows(
							CCrmOwnerType::Contact,
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

		if (!$actionData['AJAX_CALL'])
		{
			LocalRedirect($arParams['PATH_TO_CONTACT_LIST']);
		}
	}
	else//if ($actionData['METHOD'] == 'GET')
	{
		if ($actionData['NAME'] == 'delete' && isset($actionData['ID']))
		{
			$ID = intval($actionData['ID']);

			$arEntityAttr = $CCrmPerms->GetEntityAttr('CONTACT', array($ID));
			$attr = $arEntityAttr[$ID];

			if($CCrmPerms->CheckEnityAccess('CONTACT', 'DELETE', $attr))
			{
				$DB->StartTransaction();

				if($CCrmBizProc->Delete($ID, $attr)
					&& $CCrmContact->Delete($ID))
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
			LocalRedirect($bInternal ? '?'.$arParams['FORM_ID'].'_active_tab=tab_contact' : $arParams['PATH_TO_CONTACT_LIST']);
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

if ($sExportType != '')
{
	$arFilter['EXPORT'] = 'Y';
}
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

if(in_array('CONTACT_SUMMARY', $arSelect, true))
{
	$arSelect[] = 'PHOTO';
	$arSelect[] = 'NAME';
	$arSelect[] = 'LAST_NAME';
	$arSelect[] = 'SECOND_NAME';
	$arSelect[] = 'TYPE_ID';
}

if(in_array('ASSIGNED_BY', $arSelect, true))
{
	$arSelect[] = 'ASSIGNED_BY_LOGIN';
	$arSelect[] = 'ASSIGNED_BY_NAME';
	$arSelect[] = 'ASSIGNED_BY_LAST_NAME';
	$arSelect[] = 'ASSIGNED_BY_SECOND_NAME';
}

if(in_array('CONTACT_COMPANY', $arSelect, true))
{
	$arSelect[] = 'COMPANY_TITLE';
	$arSelect[] = 'POST';
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

if(in_array('CREATED_BY', $arSelect, true))
{
	$arSelect[] = 'CREATED_BY_LOGIN';
	$arSelect[] = 'CREATED_BY_NAME';
	$arSelect[] = 'CREATED_BY_LAST_NAME';
	$arSelect[] = 'CREATED_BY_SECOND_NAME';
}

if(in_array('MODIFY_BY', $arSelect, true))
{
	$arSelect[] = 'MODIFY_BY_LOGIN';
	$arSelect[] = 'MODIFY_BY_NAME';
	$arSelect[] = 'MODIFY_BY_LAST_NAME';
	$arSelect[] = 'MODIFY_BY_SECOND_NAME';
}

if(in_array('COMPANY_ID', $arSelect, true))
{
	$arSelect[] = 'COMPANY_TITLE';
}
else
{
	// Required for construction of URLs
	$arSelect[] = 'COMPANY_ID';
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
			'CONTACT_SUMMARY' => array(
				'NAME',
				'SECOND_NAME',
				'LAST_NAME',
				'PHOTO',
				'TYPE_ID'
			),
			'CONTACT_COMPANY' => array(
				'COMPANY_ID',
				'POST'
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
	$arSelect = array('DATE_CREATE', 'NAME', 'LAST_NAME', 'LOGIN', 'TYPE_ID');
	$nTopCount = $arParams['CONTACT_COUNT'];
}

if($nTopCount > 0 && !isset($arFilter['ID']))
{
	$arNavParams['nTopCount'] = $nTopCount;
}

if (!empty($sExportType))
	$arFilter['PERMISSION'] = 'EXPORT';

// HACK: Make custom sort for ASSIGNED_BY and FULL_NAME field
$arSort = $arResult['SORT'];
if(isset($arSort['assigned_by']))
{
	$arSort['assigned_by_last_name'] = $arSort['assigned_by'];
	$arSort['assigned_by_name'] = $arSort['assigned_by'];
	$arSort['assigned_by_login'] = $arSort['assigned_by'];
	unset($arSort['assigned_by']);
}
if(isset($arSort['full_name']))
{
	$arSort['last_name'] = $arSort['full_name'];
	$arSort['name'] = $arSort['full_name'];
	unset($arSort['full_name']);
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
$obRes = CCrmContact::GetListEx($arSort, $arFilter, false, ($sExportType == '' ? $arNavParams : false), $arSelect, $arOptions);
if ($arResult['GADGET'] != 'Y' && $sExportType == '')
{
	$obRes->NavStart($arNavParams['nPageSize'], false);
}

$arResult['CONTACT'] = array();
$arResult['CONTACT_ID'] = array();
$arResult['CONTACT_UF'] = array();
$arResult['PERMS']['ADD']    = !$CCrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'ADD');
$arResult['PERMS']['WRITE']  = !$CCrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'WRITE');
$arResult['PERMS']['DELETE'] = !$CCrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'DELETE');

$bDeal = !$CCrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'WRITE');
$arResult['PERM_DEAL'] = $bDeal;
$now = time() + CTimeZone::GetOffset();
while($arContact = $obRes->GetNext())
{
	if (!empty($arContact['PHOTO']))
	{
		if ($sExportType != '')
		{
			if ($arFile = CFile::GetFileArray($arContact['PHOTO']))
				$arContact['PHOTO'] = CHTTP::URN2URI($arFile["SRC"]);
		}
		else
		{
			$arFileTmp = CFile::ResizeImageGet(
				$arContact['PHOTO'],
				array('width' => 50, 'height' => 50),
				BX_RESIZE_IMAGE_PROPORTIONAL,
				false
			);
			$arContact['PHOTO'] = CFile::ShowImage($arFileTmp['src'], 50, 50, 'border=0');
		}
	}
	$arContact['PATH_TO_COMPANY_SHOW'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'],
		array(
			'company_id' => $arContact['COMPANY_ID']
		)
	);
	if ($bDeal)
		$arContact['PATH_TO_DEAL_EDIT'] = CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_DEAL_EDIT'],
				array(
					'deal_id' => 0
				)
			),
			array('contact_id' => $arContact['ID'], 'company_id' => $arContact['COMPANY_ID'])
		);
	$arContact['PATH_TO_CONTACT_SHOW'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'],
		array(
			'contact_id' => $arContact['ID']
		)
	);
	$arContact['PATH_TO_CONTACT_EDIT'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_EDIT'],
		array(
			'contact_id' => $arContact['ID']
		)
	);
	$arContact['PATH_TO_CONTACT_COPY'] =  CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_EDIT'],
		array(
			'contact_id' => $arContact['ID']
		)),
		array('copy' => 1)
	);
	$arContact['PATH_TO_CONTACT_DELETE'] =  CHTTP::urlAddParams(
		$bInternal ? $APPLICATION->GetCurPage() : $arParams['PATH_TO_CONTACT_LIST'],
		array('action_'.$arResult['GRID_ID'] => 'delete', 'ID' => $arContact['ID'], 'sessid' => bitrix_sessid())
	);
	$arContact['PATH_TO_USER_PROFILE'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
		array(
			'user_id' => $arContact['ASSIGNED_BY']
		)
	);
	$arContact['CONTACT_FORMATTED_NAME'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => '',
			'NAME' => $arContact['NAME'],
			'LAST_NAME' => $arContact['LAST_NAME'],
			'SECOND_NAME' => $arContact['SECOND_NAME']
		),
		false, false
	);

	$typeID = isset($arContact['TYPE_ID']) ? $arContact['TYPE_ID'] : '';
	$arContact['CONTACT_TYPE_NAME'] = isset($arResult['TYPE_LIST'][$typeID]) ? $arResult['TYPE_LIST'][$typeID] : $typeID;

	$arContact['PATH_TO_USER_CREATOR'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
		array(
			'user_id' => $arContact['CREATED_BY']
		)
	);

	$arContact['PATH_TO_USER_MODIFIER'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
		array(
			'user_id' => $arContact['MODIFY_BY']
		)
	);

	$arContact['CREATED_BY_FORMATTED_NAME'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arContact['CREATED_BY_LOGIN'],
			'NAME' => $arContact['CREATED_BY_NAME'],
			'LAST_NAME' => $arContact['CREATED_BY_LAST_NAME'],
			'SECOND_NAME' => $arContact['CREATED_BY_SECOND_NAME']
		),
		true, false
	);

	$arContact['MODIFY_BY_FORMATTED_NAME'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arContact['MODIFY_BY_LOGIN'],
			'NAME' => $arContact['MODIFY_BY_NAME'],
			'LAST_NAME' => $arContact['MODIFY_BY_LAST_NAME'],
			'SECOND_NAME' => $arContact['MODIFY_BY_SECOND_NAME']
		),
		true, false
	);

	if(isset($arContact['~ACTIVITY_TIME']))
	{
		$time = MakeTimeStamp($arContact['~ACTIVITY_TIME']);
		$arContact['~ACTIVITY_EXPIRED'] = $time <= $now;
		$arContact['~ACTIVITY_IS_CURRENT_DAY'] = $arContact['~ACTIVITY_EXPIRED'] || CCrmActivity::IsCurrentDay($time);
	}

	if (IsModuleInstalled('tasks'))
	{
		$arContact['PATH_TO_TASK_EDIT'] = CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(COption::GetOptionString('tasks', 'paths_task_user_edit', ''),
				array(
					'task_id' => 0,
					'user_id' => $USER->GetID()
				)
			),
			array(
				'UF_CRM_TASK' => 'C_'.$arContact['ID'],
				'TITLE' => urlencode(GetMessage('CRM_TASK_TITLE_PREFIX')),
				'TAGS' => urlencode(GetMessage('CRM_TASK_TAG')),
				'back_url' => urlencode($arParams['PATH_TO_CONTACT_LIST'])
			)
		);
	}

	if (IsModuleInstalled('bizproc'))
	{
		$arContact['BIZPROC_STATUS'] = '';
		$arContact['BIZPROC_STATUS_HINT'] = '';
		$arDocumentStates = CBPDocument::GetDocumentStates(
			array('crm', 'CCrmDocumentContact', 'CONTACT'),
			array('crm', 'CCrmDocumentContact', 'CONTACT_'.$arDeal['ID'])
		);

		$arContact['PATH_TO_BIZPROC_LIST'] =  CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'],
			array(
				'contact_id' => $arContact['ID']
			)),
			array('CRM_CONTACT_SHOW_V12_active_tab' => 'tab_bizproc')
		);

		$iBPCountTask = 0;
		$iCntDocStates = count($arDocumentStates);
		foreach ($arDocumentStates as $arDocumentState)
		{
			$paramName = 'BIZPROC_'.$arDocumentState['TEMPLATE_ID'];
			if($sExportType !== '')
			{
				if (strlen($arDocumentState['STATE_TITLE']) > 0)
					$arContact[$paramName] = $arDocumentState['STATE_TITLE'];
			}
			else
			{
				if (strlen($arDocumentState['STATE_TITLE']) > 0)
					$arContact[$paramName] = '<a href="'.$arContact['PATH_TO_BIZPROC_LIST'].'">'.$arDocumentState['STATE_TITLE'].'</a>';
				$arTasksWorkflow = CBPDocument::GetUserTasksForWorkflow($USER->GetID(), $arDocumentState['ID']);

				$iBPCountTask += empty($arTasksWorkflow) ? 0 : count($arTasksWorkflow);
				if (strlen($arDocumentState['ID']) > 0 && strlen($arDocumentState['WORKFLOW_STATUS']) > 0
					&& $arContact['BIZPROC_STATUS'] != 'attention')
					$arContact['BIZPROC_STATUS'] = (empty($arTasksWorkflow) ? 'inprogress' : 'attention');
				if ($iCntDocStates == 1)
				{
					$arContact['BIZPROC_STATUS_HINT'] =
						'<div class=\'bizproc-item-title\'>'.
							(!empty($arDocumentState['TEMPLATE_NAME']) ? htmlspecialcharsbx(htmlspecialcharsbx($arDocumentState['TEMPLATE_NAME'])) : GetMessage('CRM_BPLIST')).': '.
							'<span class=\'bizproc-item-title bizproc-state-title\'>'.
								'<a href=\''.$arContact['PATH_TO_BIZPROC_LIST'].'\'>'.
									(strlen($arDocumentState['STATE_TITLE']) > 0 ? htmlspecialcharsbx(htmlspecialcharsbx($arDocumentState['STATE_TITLE'])) : htmlspecialcharsbx(htmlspecialcharsbx($arDocumentState['STATE_NAME']))).
								'</a>'.
							'</span>'.
						'</div>';
				}
				else
				{
					$arContact['BIZPROC_STATUS_HINT'] =
							'<span class=\'bizproc-item-title\'>'.
								GetMessage('CRM_BP_R_P').': <a href=\''.$arContact['PATH_TO_BIZPROC_LIST'].'\' title=\''.GetMessage('CRM_BP_R_P_TITLE').'\'>'.count($arDocumentStates).'</a>'.
							'</span>'.
							(!empty($iBPCountTask)
								?
									'<br /><span class=\'bizproc-item-title\'>'.
									GetMessage('CRM_TASKS').': <a href=\''.$arContact['PATH_TO_USER_BP'].'\' title=\''.GetMessage('CRM_TASKS_TITLE').'\'>'.$iBPCountTask.'</a></span>'
								:
									''
							);
				}
			}
		}
		if ($arContact['BIZPROC_STATUS'] == '')
			$arContact['BIZPROC_STATUS_HINT'] = '';
	}

	$arContact['ASSIGNED_BY_ID'] = $arContact['~ASSIGNED_BY_ID'] = intval($arContact['ASSIGNED_BY']);
	$arContact['ASSIGNED_BY'] = CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => $arContact['ASSIGNED_BY_LOGIN'],
			'NAME' => $arContact['ASSIGNED_BY_NAME'],
			'LAST_NAME' => $arContact['ASSIGNED_BY_LAST_NAME'],
			'SECOND_NAME' => $arContact['ASSIGNED_BY_SECOND_NAME']
		),
		true, false
	);

	$arResult['CONTACT'][$arContact['ID']] = $arContact;
	$arResult['CONTACT_UF'][$arContact['ID']] = array();
	$arResult['CONTACT_ID'][$arContact['ID']] = $arContact['ID'];
}

$arResult['ROWS_COUNT'] = $obRes->SelectedRowsCount();
$arResult['DB_LIST'] = $obRes;
$arResult['DB_FILTER'] = $arFilter;

$CCrmUserType->ListAddEnumFieldsValue($arResult, $arResult['CONTACT'], $arResult['CONTACT_UF'], ($sExportType !== '' ? ', ' : '<br />'), $sExportType !== '');


// adding crm multi field to result array
if (isset($arResult['CONTACT_ID']) && !empty($arResult['CONTACT_ID']))
{
	$arFmList = array();
	$res = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => $arResult['CONTACT_ID']));
	while($ar = $res->Fetch())
	{
		if ($sExportType == '')
			$arFmList[$ar['ELEMENT_ID']][$ar['COMPLEX_ID']][] = CCrmFieldMulti::GetTemplateByComplex($ar['COMPLEX_ID'], $ar['VALUE']);
		else
			$arFmList[$ar['ELEMENT_ID']][$ar['COMPLEX_ID']][] = $ar['VALUE'];
		$arResult['CONTACT'][$ar['ELEMENT_ID']]['~'.$ar['COMPLEX_ID']][] = $ar['VALUE'];
	}

	foreach ($arFmList as $elementId => $arFM)
		foreach ($arFM as $complexId => $arComplexName)
			$arResult['CONTACT'][$elementId][$complexId] = implode(', ', $arComplexName);

	// checkig access for operation
	$arContactAttr = CCrmPerms::GetEntityAttr('CONTACT', $arResult['CONTACT_ID']);
	foreach ($arResult['CONTACT_ID'] as $iContactId)
	{
		$arResult['CONTACT'][$iContactId]['EDIT'] = $CCrmPerms->CheckEnityAccess('CONTACT', 'WRITE', $arContactAttr[$iContactId]);
		$arResult['CONTACT'][$iContactId]['DELETE'] = $CCrmPerms->CheckEnityAccess('CONTACT', 'DELETE', $arContactAttr[$iContactId]);

		$arResult['CONTACT'][$iContactId]['BIZPROC_LIST'] = array();
		foreach ($arBPData as $arBP)
		{
			if (!CBPDocument::CanUserOperateDocument(
				CBPCanUserOperateOperation::StartWorkflow,
				$USER->GetID(),
				array('crm', 'CCrmDocumentContact', 'CONTACT_'.$arResult['CONTACT'][$iContactId]['ID']),
				array(
					'UserGroups' => $CCrmBizProc->arCurrentUserGroups,
					'DocumentStates' => $arDocumentStates,
					'WorkflowTemplateId' => $arBP['ID'],
					'CreatedBy' => $arResult['CONTACT'][$iContactId]['ASSIGNED_BY'],
					'UserIsAdmin' => $USER->IsAdmin(),
					'CRMEntityAttr' =>  $arContactAttr[$iContactId]
				)
			))
			{
				continue;
			}

			$arBP['PATH_TO_BIZPROC_START'] = CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'],
				array(
					'contact_id' => $arResult['CONTACT'][$iContactId]['ID']
				)),
				array(
					'workflow_template_id' => $arBP['ID'], 'bizproc_start' => 1,  'sessid' => bitrix_sessid(),
					'CRM_CONTACT_SHOW_V12_active_tab' => 'tab_bizproc', 'backurl' => $arParams['PATH_TO_CONTACT_LIST'])
			);
			$arResult['CONTACT'][$iContactId]['BIZPROC_LIST'][] = $arBP;
		}
	}
}

if ($sExportType == '')
{
	$this->IncludeComponentTemplate();

	include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.contact/include/nav.php');
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
		Header('Content-Disposition: attachment;filename=contacts.csv');
	}
	elseif($sExportType === 'excel')
	{
		Header('Content-Type: application/vnd.ms-excel');
		Header('Content-Disposition: attachment;filename=contacts.xls');
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
