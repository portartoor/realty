<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('subscribe'))
{
	ShowError(GetMessage('SUBSCRIBE_MODULE_NOT_INSTALLED'));
	return;
}

// 'Fileman' module always installed
CModule::IncludeModule('fileman');

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arParams['PATH_TO_SM_CONFIG'] = CrmCheckPath('PATH_TO_SM_CONFIG', $arParams['PATH_TO_SM_CONFIG'], $APPLICATION->GetCurPage());
$arResult['ENABLE_CONTROL_PANEL'] = isset($arParams['ENABLE_CONTROL_PANEL']) ? $arParams['ENABLE_CONTROL_PANEL'] : true;

CUtil::InitJSCore();
$bVarsFromForm = false;
$sMailTemplate = COption::GetOptionString('crm', 'email_template');
$sMailFrom = COption::GetOptionString('crm', 'email_from');

if (empty($sMailFrom))
{
	$sMailFrom = COption::GetOptionString('crm', 'mail', '');
}

//Disable fake address generation for Bitrix24
if (empty($sMailFrom) && !IsModuleInstalled('bitrix24'))
{
	$sHost = $_SERVER['HTTP_HOST'];
	if (strpos($sHost, ':') !== false)
		$sHost = substr($sHost, 0, strpos($sHost, ':'));

	$sMailFrom = 'crm@'.$sHost;
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && check_bitrix_sessid())
{
	$bVarsFromForm = true;
	if(isset($_POST['save']) || isset($_POST['apply']))
	{
		if (!check_email($_POST['MAIL_FROM']))
			$sError = GetMessage('CRM_ERROR_MAIL_FROM');

		if (strlen($sError) > 0)
			ShowError($sError);
		else
		{
			$sMailFrom = $_POST['MAIL_FROM'];
			$sMailTemplate = $_POST['MAIL_TEMPLATE'];
			COption::SetOptionString('crm', 'email_template', $sMailTemplate);

			COption::SetOptionString('crm', 'email_from', $sMailFrom);

			$sMailTemplate = $_POST['CALENDAR_DISPLAY_COMPLETED_CALLS'];

			CCrmActivityCalendarSettings::SetValue(
				CCrmActivityCalendarSettings::DisplayCompletedCalls,
				isset($_POST['CALENDAR_DISPLAY_COMPLETED_CALLS']) && strtoupper($_POST['CALENDAR_DISPLAY_COMPLETED_CALLS']) !== 'N'
			);

			CCrmActivityCalendarSettings::SetValue(
				CCrmActivityCalendarSettings::DisplayCompletedMeetings,
				isset($_POST['CALENDAR_DISPLAY_COMPLETED_MEETINGS']) && strtoupper($_POST['CALENDAR_DISPLAY_COMPLETED_MEETINGS']) !== 'N'
			);

			CCrmUserCounterSettings::SetValue(
				CCrmUserCounterSettings::ReckonActivitylessItems,
				isset($_POST['RECKON_ACTIVITYLESS_ITEMS_IN_COUNTERS']) && strtoupper($_POST['RECKON_ACTIVITYLESS_ITEMS_IN_COUNTERS']) !== 'N'
			);

			$calltoFormat = isset($_POST['CALLTO_FORMAT']) ? intval($_POST['CALLTO_FORMAT']) : CCrmCallToUrl::Slashless;
			CCrmCallToUrl::SetFormat($calltoFormat);

			$calltoSettings = CCrmCallToUrl::GetCustomSettings();
			if($calltoFormat === CCrmCallToUrl::Custom)
			{
				$calltoSettings['URL_TEMPLATE'] = isset($_POST['CALLTO_URL_TEMPLATE']) ? $_POST['CALLTO_URL_TEMPLATE'] : '';
				$calltoSettings['CLICK_HANDLER'] = isset($_POST['CALLTO_CLICK_HANDLER']) ? $_POST['CALLTO_CLICK_HANDLER'] : '';
			}
			$calltoSettings['NORMALIZE_NUMBER'] = isset($_POST['CALLTO_NORMALIZE_NUMBER']) && strtoupper($_POST['CALLTO_NORMALIZE_NUMBER']) === 'N' ? 'N' : 'Y';
			CCrmCallToUrl::SetCustomSettings($calltoSettings);

			LocalRedirect(
				CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_SM_CONFIG'],	array()
				)
			);
		}
	}
}

$arResult['FORM_ID'] = 'CRM_SM_CONFIG';
$arResult['BACK_URL'] = $arParams['PATH_TO_SM_CONFIG'];

$arResult['FIELDS'] = array();

$arResult['FIELDS']['tab_config'][] = array(
	'id' => 'MAIL_FROM',
	'name' => GetMessage('CRM_FIELD_MAIL_FROM'),
	'params' => array('size' => 50),
	'type' => 'text',
	'value' => isset($arMailboxFields['NAME']) ? ($bVarsFromForm ? htmlspecialcharsbx($sMailFrom) : $sMailFrom) : $sMailFrom,
	'required' => true
);

ob_start();
$ar = array(
	'width' => '100%',
	'height' => '250px',
	'inputName' => 'MAIL_TEMPLATE',
	'inputId' => 'MAIL_TEMPLATE',
	'content' => isset($sMailTemplate) ? ($bVarsFromForm ? htmlspecialcharsback($sMailTemplate) : $sMailTemplate) : $sMailTemplate,
	'bUseFileDialogs' => false,
	'bFloatingToolbar' => false,
	'bArisingToolbar' => false,
	'bResizable' => false,
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
$arResult['FIELDS']['tab_config'][] = array(
	'id' => 'MAIL_TEMPLATE',
	'name' => GetMessage('CRM_FIELD_MAIL_TEMPLATE'),
	'type' => 'custom',
	'value' => $sVal
);

$arResult['FIELDS']['tab_activity_config'][] = array(
	'id' => 'CALENDAR_DISPLAY_COMPLETED_CALLS',
	'name' => GetMessage('CRM_FIELD_DISPLAY_COMPLETED_CALLS_IN_CALENDAR'),
	'type' => 'checkbox',
	'value' => CCrmActivityCalendarSettings::GetValue(CCrmActivityCalendarSettings::DisplayCompletedCalls, true),
	'required' => false
);

$arResult['FIELDS']['tab_activity_config'][] = array(
	'id' => 'CALENDAR_DISPLAY_COMPLETED_MEETINGS',
	'name' => GetMessage('CRM_FIELD_DISPLAY_COMPLETED_MEETINGS_IN_CALENDAR'),
	'type' => 'checkbox',
	'value' => CCrmActivityCalendarSettings::GetValue(CCrmActivityCalendarSettings::DisplayCompletedMeetings, true),
	'required' => false
);

$arResult['FIELDS']['tab_activity_config'][] = array(
	'id' => 'RECKON_ACTIVITYLESS_ITEMS_IN_COUNTERS',
	'name' => GetMessage('CRM_FIELD_RECKON_ACTIVITYLESS_ITEMS_IN_COUNTERS'),
	'type' => 'checkbox',
	'value' => CCrmUserCounterSettings::GetValue(CCrmUserCounterSettings::ReckonActivitylessItems, true),
	'required' => false
);

$arResult['FIELDS']['tab_format'][] = array(
	'id' => 'CALLTO_FORMAT',
	'name' => GetMessage('CRM_FIELD_CALLTO_FORMAT'),
	'type' => 'list',
	'items' => CCrmCallToUrl::GetAllDescriptions(),
	'value' => CCrmCallToUrl::GetFormat(CCrmCallToUrl::Slashless),
	'required' => false
);

$calltoSettings = CCrmCallToUrl::GetCustomSettings();

$arResult['FIELDS']['tab_format'][] = array(
	'id' => 'CALLTO_URL_TEMPLATE',
	'name' => GetMessage('CRM_FIELD_CALLTO_URL_TEMPLATE'),
	'type' => 'text',
	'value' => isset($calltoSettings['URL_TEMPLATE']) ? $calltoSettings['URL_TEMPLATE'] : 'callto:[phone]',
	'required' => false
);

$arResult['FIELDS']['tab_format'][] = array(
	'id' => 'CALLTO_CLICK_HANDLER',
	'name' => GetMessage('CRM_FIELD_CALLTO_CLICK_HANDLER'),
	'type' => 'textarea',
	'value' => isset($calltoSettings['CLICK_HANDLER']) ? $calltoSettings['CLICK_HANDLER'] : '',
	'required' => false
);

$arResult['FIELDS']['tab_format'][] = array(
	'id' => 'CALLTO_NORMALIZE_NUMBER',
	'name' => GetMessage('CRM_FIELD_CALLTO_NORMALIZE_NUMBER'),
	'type' => 'checkbox',
	'value' => isset($calltoSettings['NORMALIZE_NUMBER']) ? $calltoSettings['NORMALIZE_NUMBER'] === 'Y' : true,
	'required' => false
);

$this->IncludeComponentTemplate();

$APPLICATION->AddChainItem(GetMessage('CRM_SM_LIST'), $arParams['PATH_TO_SM_CONFIG']);
?>