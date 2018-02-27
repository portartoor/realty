<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

// 'Fileman' module always installed
CModule::IncludeModule('fileman');

if (IsModuleInstalled('bizproc'))
{
	if (!CModule::IncludeModule('bizproc'))
	{
		ShowError(GetMessage('BIZPROC_MODULE_NOT_INSTALLED'));
		return;
	}
}
$CCrmDeal = new CCrmDeal();
if ($CCrmDeal->cPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

CUtil::InitJSCore(array('ajax', 'tooltip'));

$arResult['CAN_EDIT'] = !$CCrmDeal->cPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'WRITE');
$arResult['EDITABLE_FIELDS'] = array();

$arParams['PATH_TO_DEAL_LIST'] = CrmCheckPath('PATH_TO_DEAL_LIST', $arParams['PATH_TO_DEAL_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath('PATH_TO_DEAL_SHOW', $arParams['PATH_TO_DEAL_SHOW'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&show');
$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');
$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
$arParams['PATH_TO_CONTACT_EDIT'] = CrmCheckPath('PATH_TO_CONTACT_EDIT', $arParams['PATH_TO_CONTACT_EDIT'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&edit');
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_COMPANY_EDIT'] = CrmCheckPath('PATH_TO_COMPANY_EDIT', $arParams['PATH_TO_COMPANY_EDIT'], $APPLICATION->GetCurPage().'?company_id=#company_id#&edit');
$arParams['PATH_TO_LEAD_SHOW'] = CrmCheckPath('PATH_TO_LEAD_SHOW', $arParams['PATH_TO_LEAD_SHOW'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&show');
$arParams['PATH_TO_LEAD_EDIT'] = CrmCheckPath('PATH_TO_LEAD_EDIT', $arParams['PATH_TO_LEAD_EDIT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&edit');
$arParams['PATH_TO_LEAD_CONVERT'] = CrmCheckPath('PATH_TO_LEAD_CONVERT', $arParams['PATH_TO_LEAD_CONVERT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&convert');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

global $USER_FIELD_MANAGER;

$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmDeal::$sUFEntityID);

$arResult['ELEMENT_ID'] = $arParams['ELEMENT_ID'] = (int) $arParams['ELEMENT_ID'];

$obFields = CCrmDeal::GetListEx(
	array(),
	array(
	'ID' => $arParams['ELEMENT_ID']
	)
);
$arFields = $obFields->GetNext();

$arFields['CONTACT_FM'] = array();
if(isset($arFields['CONTACT_ID']) && intval($arFields['CONTACT_ID']) > 0)
{
	$dbResMultiFields = CCrmFieldMulti::GetList(
		array('ID' => 'asc'),
		array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => $arFields['CONTACT_ID'])
	);
	while($arMultiFields = $dbResMultiFields->Fetch())
	{
		$arFields['CONTACT_FM'][$arMultiFields['TYPE_ID']][$arMultiFields['ID']] = array('VALUE' => $arMultiFields['VALUE'], 'VALUE_TYPE' => $arMultiFields['VALUE_TYPE']);
	}
}

$arFields['COMPANY_FM'] = array();
if(isset($arFields['COMPANY_ID']) && intval($arFields['COMPANY_ID']) > 0)
{
	$dbResMultiFields = CCrmFieldMulti::GetList(
		array('ID' => 'asc'),
		array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $arFields['COMPANY_ID'])
	);
	while($arMultiFields = $dbResMultiFields->Fetch())
	{
		$arFields['COMPANY_FM'][$arMultiFields['TYPE_ID']][$arMultiFields['ID']] = array('VALUE' => $arMultiFields['VALUE'], 'VALUE_TYPE' => $arMultiFields['VALUE_TYPE']);
	}
}

$arResult['STAGE_LIST'] = CCrmStatus::GetStatusListEx('DEAL_STAGE');
$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();
$arResult['STATE_LIST'] = CCrmStatus::GetStatusListEx('DEAL_STATE');
$arResult['TYPE_LIST'] = CCrmStatus::GetStatusListEx('DEAL_TYPE');
$arResult['EVENT_LIST'] = CCrmStatus::GetStatusListEx('EVENT_TYPE');
//$arResult['PRODUCT_ROWS'] = CCrmDeal::LoadProductRows($arParams['ELEMENT_ID']);

$arFields['TYPE_TEXT'] = isset($arFields['TYPE_ID'])
	&& isset($arResult['TYPE_LIST'][$arFields['TYPE_ID']])
	? $arResult['TYPE_LIST'][$arFields['TYPE_ID']] : '';

$arFields['~STAGE_TEXT'] = isset($arFields['STAGE_ID'])
	&& isset($arResult['STAGE_LIST'][$arFields['STAGE_ID']])
	? $arResult['STAGE_LIST'][$arFields['STAGE_ID']] : '';

$arFields['STAGE_TEXT'] = htmlspecialcharsbx($arFields['~STAGE_TEXT']);

$arContactType = CCrmStatus::GetStatusListEx('CONTACT_TYPE');
$arFields['CONTACT_TYPE_TEXT'] = isset($arFields['CONTACT_TYPE_ID'])
	&& isset($arContactType[$arFields['CONTACT_TYPE_ID']])
	? $arContactType[$arFields['CONTACT_TYPE_ID']] : '';

$arContactSource = CCrmStatus::GetStatusListEx('SOURCE');
$arFields['CONTACT_SOURCE_TEXT'] = isset($arFields['CONTACT_SOURCE_ID'])
	&& isset($arContactSource[$arFields['CONTACT_SOURCE_ID']])
	? $arContactSource[$arFields['CONTACT_SOURCE_ID']] : '';

$arFields['~CONTACT_FORMATTED_NAME'] = CUser::FormatName(
	$arParams['NAME_TEMPLATE'],
	array(
		'LOGIN' => '',
		'NAME' => isset($arFields['~CONTACT_NAME']) ? $arFields['~CONTACT_NAME'] : '',
		'LAST_NAME' => isset($arFields['~CONTACT_LAST_NAME']) ? $arFields['~CONTACT_LAST_NAME'] : '',
		'SECOND_NAME' => isset($arFields['~CONTACT_SECOND_NAME']) ? $arFields['~CONTACT_SECOND_NAME'] : ''
	),
	false,
	false
);

$arFields['CONTACT_FORMATTED_NAME'] = htmlspecialcharsbx($arFields['~CONTACT_FORMATTED_NAME']);

$arCompanyIndustry = CCrmStatus::GetStatusListEx('INDUSTRY');
$arFields['COMPANY_INDUSTRY_TEXT'] = isset($arFields['COMPANY_INDUSTRY'])
	&& isset($arCompanyIndustry[$arFields['COMPANY_INDUSTRY']])
	? $arCompanyIndustry[$arFields['COMPANY_INDUSTRY']] : '';

$arCompanyEmployees = CCrmStatus::GetStatusListEx('EMPLOYEES');
$arFields['COMPANY_EMPLOYEES_TEXT'] = isset($arFields['COMPANY_EMPLOYEES'])
	&& isset($arCompanyEmployees[$arFields['COMPANY_EMPLOYEES']])
	? $arCompanyEmployees[$arFields['COMPANY_EMPLOYEES']] : '';

$arCompanyType = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
$arFields['COMPANY_TYPE_TEXT'] = isset($arFields['COMPANY_TYPE'])
	&& isset($arCompanyType[$arFields['COMPANY_TYPE']])
	? $arCompanyType[$arFields['COMPANY_TYPE']] : '';

$companyLogoID = isset($arFields['~COMPANY_LOGO']) ? intval($arFields['~COMPANY_LOGO']) : 0;
if($companyLogoID <= 0)
{
	$arFields['COMPANY_LOGO_HTML'] = '';
}
else
{
	$arPhoto = CFile::ResizeImageGet(
		$companyLogoID,
		array('width' => 50, 'height' => 50),
		BX_RESIZE_IMAGE_PROPORTIONAL,
		false
	);
	$arFields['COMPANY_LOGO_HTML'] = CFile::ShowImage($arPhoto['src'], 50, 50, 'border=0');
}

$fullNameFormat = $arParams['NAME_TEMPLATE'];

$arFields['~ASSIGNED_BY_FORMATTED_NAME'] = intval($arFields['~ASSIGNED_BY_ID']) > 0
	? CUser::FormatName(
		$fullNameFormat,
		array(
			'LOGIN' => $arFields['~ASSIGNED_BY_LOGIN'],
			'NAME' => $arFields['~ASSIGNED_BY_NAME'],
			'LAST_NAME' => $arFields['~ASSIGNED_BY_LAST_NAME'],
			'SECOND_NAME' => $arFields['~ASSIGNED_BY_SECOND_NAME']
		),
		true, false
	) : GetMessage('RESPONSIBLE_NOT_ASSIGNED');

$arFields['ASSIGNED_BY_FORMATTED_NAME'] = htmlspecialcharsbx($arFields['~ASSIGNED_BY_FORMATTED_NAME']);

$arFields['~CREATED_BY_FORMATTED_NAME'] = CUser::FormatName($fullNameFormat,
	array(
		'LOGIN' => $arFields['~CREATED_BY_LOGIN'],
		'NAME' => $arFields['~CREATED_BY_NAME'],
		'LAST_NAME' => $arFields['~CREATED_BY_LAST_NAME'],
		'SECOND_NAME' => $arFields['~CREATED_BY_SECOND_NAME']
	),
	true, false
);


$arFields['CREATED_BY_FORMATTED_NAME'] = htmlspecialcharsbx($arFields['~CREATED_BY_FORMATTED_NAME']);

$arFields['PATH_TO_USER_CREATOR'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
	array(
		'user_id' => $arFields['ASSIGNED_BY']
	)
);

$arFields['~MODIFY_BY_FORMATTED_NAME'] = CUser::FormatName($fullNameFormat,
	array(
		'LOGIN' => $arFields['~MODIFY_BY_LOGIN'],
		'NAME' => $arFields['~MODIFY_BY_NAME'],
		'LAST_NAME' => $arFields['~MODIFY_BY_LAST_NAME'],
		'SECOND_NAME' => $arFields['~MODIFY_BY_SECOND_NAME']
	),
	true, false
);

$arFields['MODIFY_BY_FORMATTED_NAME'] = htmlspecialcharsbx($arFields['~MODIFY_BY_FORMATTED_NAME']);

$arFields['PATH_TO_USER_MODIFIER'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'],
	array(
		'user_id' => $arFields['MODIFY_BY']
	)
);

$arResult['ELEMENT'] = $arFields;
unset($arFields);

if (empty($arResult['ELEMENT']['ID']))
{
	LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_LIST'], array()));
}

$arResult['ERROR_MESSAGE'] = '';

//CURRENCY HACK (RUR is obsolete)
if(isset($arResult['ELEMENT']['CURRENCY_ID']) && $arResult['ELEMENT']['CURRENCY_ID'] === 'RUR')
{
	$arResult['ELEMENT']['CURRENCY_ID'] = 'RUB';
}

if (intval($_REQUEST["SYNC_ORDER_ID"]) > 0)
{
	$imp = new CCrmExternalSaleImport($arResult['ELEMENT']["ORIGINATOR_ID"]);
	if ($imp->IsInitialized())
	{
		$r = $imp->GetOrderData($arResult['ELEMENT']["ORIGIN_ID"], false);
		if ($r != CCrmExternalSaleImport::SyncStatusError)
		{
			LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'], array('deal_id' => $arResult['ELEMENT']['ID'])));
		}
		else
		{
			$arErrors = $imp->GetErrors();
			foreach ($arErrors as $err)
				$arResult['ERROR_MESSAGE'] .= $err[1]."<br />";
		}
	}
}

$arResult['TOP_EVENT_NAMES'] = array();

$dbEvents = CCrmEvent::GetList(array('DATE_CREATE' => 'DESC'), array('ENTITY_TYPE'=>'DEAL', 'ENTITY_ID'=>$arResult['ELEMENT']['ID']), 10);

$eventInfoTemplate = GetMessage('CRM_DEAL_EVENT_INFO');
while ($arEvent = $dbEvents->Fetch())
{
	$eventText1 = isset($arEvent['EVENT_TEXT_1']) ? $arEvent['EVENT_TEXT_1'] : '';
	$eventText2 = isset($arEvent['EVENT_TEXT_2']) ? $arEvent['EVENT_TEXT_2'] : '';
	$arResult['TOP_EVENT_NAMES'][] = str_replace(array('#EVENT_NAME#', '#NEW_VALUE#'), array(htmlspecialcharsbx($arEvent['EVENT_NAME']), htmlspecialcharsbx($eventText2 !== '' ? $eventText2 : $eventText1)), $eventInfoTemplate);
}

$arResult['BIZPROC_NAMES'] = CCrmBizProcHelper::GetDocumentNames(CCrmOwnerType::Deal, $arResult['ELEMENT']['ID']);

$isExternal = $arResult['IS_EXTERNAL'] = isset($arResult['ELEMENT']['ORIGINATOR_ID']) && isset($arResult['ELEMENT']['ORIGIN_ID']) && intval($arResult['ELEMENT']['ORIGINATOR_ID']) > 0 && intval($arResult['ELEMENT']['ORIGIN_ID']) > 0;

/*
$nextElementID = CCrmDeal::GetRightSiblingID($arResult['ELEMENT_ID']);
if($nextElementID > 0)
{
	$arResult['NEXT_ELEMENT_URL'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'],
		array('deal_id' =>  $nextElementID)
	);
}

$prevElementID = CCrmDeal::GetLeftSiblingID($arResult['ELEMENT_ID']);
if($prevElementID > 0)
{
	$arResult['PREV_ELEMENT_URL'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'],
		array('deal_id' =>  $prevElementID)
	);
}
*/

$arResult['FORM_ID'] = 'CRM_DEAL_SHOW_V12'.($isExternal ? "_E" : "");
$arResult['GRID_ID'] = 'CRM_DEAL_LIST_V12'.($isExternal ? "_E" : "");
$arResult['BACK_URL'] = $arParams['PATH_TO_DEAL_LIST'];

$arResult['PATH_TO_COMPANY_SHOW'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'],
	array(
		'company_id' => $arResult['ELEMENT']['COMPANY_ID']
	)
);
$arResult['PATH_TO_CONTACT_SHOW'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'],
	array(
		'contact_id' => $arResult['ELEMENT']['CONTACT_ID']
	)
);
$enableInstantEdit = $arResult['ENABLE_INSTANT_EDIT'] = $arResult['CAN_EDIT'];
$arResult['FIELDS'] = array();

$readOnlyMode = !$enableInstantEdit || $isExternal;

$arResult['FIELDS']['tab_1'] = array();

// CLIENT INFO SECTION -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_client_info',
	'name' => GetMessage('CRM_SECTION_CLIENT_INFO'),
	'type' => 'section'
);

// CONTACT TITLE
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CONTACT_TITLE',
	'name' => GetMessage('CRM_FIELD_CONTACT_TITLE'),
	'value' => isset($arResult['ELEMENT']['CONTACT_FULL_NAME'])
		? ($CCrmDeal->cPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'READ')
			? $arResult['ELEMENT']['CONTACT_FULL_NAME'] :
			'<a href="'.$arResult['PATH_TO_CONTACT_SHOW'].'" id="balloon_'.$arResult['GRID_ID'].'_C_'.$arResult['ELEMENT']['CONTACT_ID'].'">'.$arResult['ELEMENT']['CONTACT_FULL_NAME'].'</a>'.
				'<script type="text/javascript">BX.tooltip("CONTACT_'.$arResult['ELEMENT']['~CONTACT_ID'].'", "balloon_'.$arResult['GRID_ID'].'_C_'.$arResult['ELEMENT']['CONTACT_ID'].'", "/bitrix/components/bitrix/crm.contact.show/card.ajax.php", "crm_balloon_contact", true);</script>'
		) : '',
	'type' => 'label'
);

// CONTACT POST
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CONTACT_POST',
	'name' => GetMessage('CRM_FIELD_CONTACT_POST'),
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['CONTACT_POST']) ? $arResult['ELEMENT']['CONTACT_POST'] : '',
	'type' => 'label'
);

// CONTACT PHONE
ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.view',
	'instant_editor',
	array(
		'TYPE_ID' => 'PHONE',
		'ENTITY_ID' => 'CONTACT',
		//'ELEMENT_ID' => $arResult['ELEMENT']['CONTACT_ID'],
		'ELEMENT_ID' => 0, // To suppress reading of values if empty
		'VALUES' => $arResult['ELEMENT']['CONTACT_FM'],
		'READ_ONLY' => true,
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$sVal = ob_get_contents();
ob_end_clean();

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CONTACT_PHONE',
	'name' => GetMessage('CRM_FIELD_CONTACT_PHONE'),
	'type' => 'custom',
	'colspan' => true,
	'value' => $sVal
);

// CONTACT EMAIL
ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.view',
	'instant_editor',
	array(
		'TYPE_ID' => 'EMAIL',
		'ENTITY_ID' => 'CONTACT',
		//'ELEMENT_ID' => $arResult['ELEMENT']['CONTACT_ID'],
		'ELEMENT_ID' => 0, // To suppress reading of values if empty
		'VALUES' => $arResult['ELEMENT']['CONTACT_FM'],
		'READ_ONLY' => true,
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CONTACT_EMAIL',
	'name' => GetMessage('CRM_FIELD_CONTACT_EMAIL'),
	'type' => 'custom',
	'colspan' => true,
	'value' => $sVal
);

// CONTACT IM
ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.view',
	'instant_editor',
	array(
		'TYPE_ID' => 'IM',
		'ENTITY_ID' => 'CONTACT',
		//'ELEMENT_ID' => $arResult['ELEMENT']['CONTACT_ID'],
		'ELEMENT_ID' => 0, // To suppress reading of values if empty
		'VALUES' => $arResult['ELEMENT']['CONTACT_FM'],
		'READ_ONLY' => true,
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CONTACT_IM',
	'name' => GetMessage('CRM_FIELD_CONTACT_IM'),
	'type' => 'custom',
	'colspan' => true,
	'value' => $sVal
);

// CONTACT ADDRESS
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CONTACT_ADDRESS',
	'name' => GetMessage('CRM_FIELD_CONTACT_ADDRESS'),
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['CONTACT_ADDRESS']) ? $arResult['ELEMENT']['CONTACT_ADDRESS'] : '',
	'type' => 'label'
);

// CONTACT TYPE
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CONTACT_TYPE',
	'name' => GetMessage('CRM_FIELD_CONTACT_TYPE'),
	'params' => array('size' => 50),
	'value' => $arResult['ELEMENT']['CONTACT_TYPE_TEXT'],
	'type' => 'label'
);

// CONTACT SOURCE
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CONTACT_SOURCE',
	'name' => GetMessage('CRM_FIELD_CONTACT_SOURCE'),
	'params' => array('size' => 50),
	'value' => $arResult['ELEMENT']['CONTACT_SOURCE_TEXT'],
	'type' => 'label'
);

// COMPANY TITLE
$companyField = array(
	'id' => 'COMPANY_TITLE',
	'name' => GetMessage('CRM_FIELD_COMPANY_TITLE'),
	'value' => isset($arResult['ELEMENT']['COMPANY_TITLE'])
		? ($CCrmDeal->cPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'READ')
			? $arResult['ELEMENT']['COMPANY_TITLE'] :
			'<a href="'.$arResult['PATH_TO_COMPANY_SHOW'].'" id="balloon_'.$arResult['GRID_ID'].'_CO_'.$arResult['ELEMENT']['COMPANY_ID'].'">'.$arResult['ELEMENT']['COMPANY_TITLE'].'</a>'.
				'<script type="text/javascript">BX.tooltip("COMPANY_'.$arResult['ELEMENT']['~COMPANY_ID'].'", "balloon_'.$arResult['GRID_ID'].'_CO_'.$arResult['ELEMENT']['COMPANY_ID'].'", "/bitrix/components/bitrix/crm.company.show/card.ajax.php", "crm_balloon_company", true);</script>'
		) : '',
	'type' => 'label'
);

$arResult['FIELDS']['tab_1'][] = $arResult['COMPANY_FIELD'] = $companyField;

// COMPANY INDUSTRY
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMPANY_INDUSTRY',
	'name' => GetMessage('CRM_FIELD_COMPANY_INDUSTRY'),
	'params' => array('size' => 50),
	'value' => $arResult['ELEMENT']['COMPANY_INDUSTRY_TEXT'],
	'type' => 'label'
);

// COMPANY PHONE
ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.view',
	'instant_editor',
	array(
		'TYPE_ID' => 'PHONE',
		'ENTITY_ID' => 'COMPANY',
		//'ELEMENT_ID' => $arResult['ELEMENT']['COMPANY_ID'],
		'ELEMENT_ID' => 0, // To suppress reading of values if empty
		'VALUES' => isset($arResult['ELEMENT']['COMPANY_FM']['PHONE']) ? $arResult['ELEMENT']['COMPANY_FM'] : array(),
		'READ_ONLY' => true,
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMPANY_PHONE',
	'name' => GetMessage('CRM_FIELD_COMPANY_PHONE'),
	'type' => 'custom',
	'colspan' => true,
	'value' => $sVal
);

// COMPANY EMAIL
ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.view',
	'instant_editor',
	array(
		'TYPE_ID' => 'EMAIL',
		'ENTITY_ID' => 'COMPANY',
		//'ELEMENT_ID' => $arResult['ELEMENT']['COMPANY_ID'],
		'ELEMENT_ID' => 0, // To suppress reading of values if empty
		'VALUES' => isset($arResult['ELEMENT']['COMPANY_FM']['EMAIL']) ? $arResult['ELEMENT']['COMPANY_FM'] : array(),
		'READ_ONLY' => true,
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMPANY_EMAIL',
	'name' => GetMessage('CRM_FIELD_COMPANY_EMAIL'),
	'type' => 'custom',
	'colspan' => true,
	'value' => $sVal
);

// COMPANY EMPLOYEES
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMPANY_EMPLOYEES',
	'name' => GetMessage('CRM_FIELD_COMPANY_EMPLOYEES'),
	'params' => array('size' => 50),
	'value' => $arResult['ELEMENT']['COMPANY_EMPLOYEES_TEXT'],
	'type' => 'label'
);

// COMPANY REVENUE
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMPANY_REVENUE',
	'name' => GetMessage('CRM_FIELD_COMPANY_REVENUE'),
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['COMPANY_REVENUE'])
		? CCrmCurrency::MoneyToString(
			$arResult['ELEMENT']['COMPANY_REVENUE'],
			isset($arResult['ELEMENT']['COMPANY_CURRENCY_ID'])
				? $arResult['ELEMENT']['COMPANY_CURRENCY_ID']
				: CCrmCurrency::GetBaseCurrencyID()
		)
		: '',
	'type' => 'label'
);

// COMPANY TYPE
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMPANY_TYPE',
	'name' => GetMessage('CRM_FIELD_COMPANY_TYPE'),
	'params' => array('size' => 50),
	'value' => $arResult['ELEMENT']['COMPANY_TYPE_TEXT'],
	'type' => 'label'
);

// COMPANY WEB
ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.view',
	'instant_editor',
	array(
		'TYPE_ID' => 'WEB',
		'ENTITY_ID' => 'COMPANY',
		//'ELEMENT_ID' => $arResult['ELEMENT']['COMPANY_ID'],
		'ELEMENT_ID' => 0, // To suppress reading of values if empty
		'VALUES' => isset($arResult['ELEMENT']['COMPANY_FM']['WEB']) ? $arResult['ELEMENT']['COMPANY_FM']['WEB'] : array(),
		'READ_ONLY' => true,
	),
	null,
	array('HIDE_ICONS' => 'Y')
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMPANY_WEB',
	'name' => GetMessage('CRM_FIELD_COMPANY_WEB'),
	'type' => 'custom',
	'colspan' => true,
	'value' => $sVal
);

// COMPANY ADDRESS LEGAL
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMPANY_ADDRESS_LEGAL',
	'name' => GetMessage('CRM_FIELD_COMPANY_ADDRESS_LEGAL'),
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['COMPANY_ADDRESS_LEGAL']) ? $arResult['ELEMENT']['COMPANY_ADDRESS_LEGAL'] : '',
	'type' => 'label'
);

// COMPANY ADDRESS
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMPANY_ADDRESS',
	'name' => GetMessage('CRM_FIELD_COMPANY_ADDRESS'),
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['COMPANY_ADDRESS']) ? $arResult['ELEMENT']['COMPANY_ADDRESS'] : '',
	'type' => 'label'
);

// COMPANY BANKING DETAILS
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMPANY_BANKING_DETAILS',
	'name' => GetMessage('CRM_FIELD_COMPANY_BANKING_DETAILS'),
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['COMPANY_BANKING_DETAILS']) ? $arResult['ELEMENT']['COMPANY_BANKING_DETAILS'] : '',
	'type' => 'label'
);
// <-- CLIENT INFO SECTION

// DEAL SECTION -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_deal_info',
	'name' => GetMessage('CRM_SECTION_DEAL'),
	'type' => 'section'
);

// ID -->
// ID is displayed in header. The field is added for COMPATIBILITY ONLY
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ID',
	'name' => 'ID',
	'params' => array('size' => 50),
	'value' => $arResult['ELEMENT']['ID'],
	'type' => 'label'
);
// <-- ID

// TITLE -->
// TITLE is displayed in summary panel. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'TITLE';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'TITLE',
	'name' => GetMessage('CRM_FIELD_TITLE_DEAL'),
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['TITLE']) ? $arResult['ELEMENT']['TITLE'] : '',
	'type' => 'label'
);
// <-- TITLE

// STAGE -->
// STAGE is displayed in summary panel. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'STAGE_ID';
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'STAGE_ID',
	'name' => GetMessage('CRM_FIELD_STAGE_ID'),
	'type' => 'label',
	'value' => $arResult['ELEMENT']['STAGE_TEXT']
);
// <-- STAGE
// TYPE -->
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'TYPE_ID';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'TYPE_ID',
	'name' => GetMessage('CRM_FIELD_TYPE_ID'),
	'type' => 'label',
	'items' => $arResult['TYPE_LIST'],
	'value' => $arResult['ELEMENT']['TYPE_TEXT']
);
// <-- TYPE

// OPPORTUNITY -->
// OPPORTUNITY is displayed in sidebar. The field is added for COMPATIBILITY ONLY
$currencyID = CCrmCurrency::GetBaseCurrencyID();
if(isset($arResult['ELEMENT']['CURRENCY_ID']) && $arResult['ELEMENT']['CURRENCY_ID'] !== '')
{
	$currencyID = $arResult['ELEMENT']['CURRENCY_ID'];
}

if($enableInstantEdit && !$isExternal)
{
	$arResult['EDITABLE_FIELDS'][] = 'OPPORTUNITY';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'OPPORTUNITY',
	'name' => GetMessage('CRM_FIELD_OPPORTUNITY'),
	'type' => 'label',
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['OPPORTUNITY']) ? CCrmCurrency::MoneyToString($arResult['ELEMENT']['OPPORTUNITY'], $currencyID, '#') : ''
);
// <-- OPPORTUNITY

// CURRENCY -->
// CURRENCY is displayed in sidebar. The field is added for COMPATIBILITY ONLY
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CURRENCY_ID',
	'name' => GetMessage('CRM_FIELD_CURRENCY_ID'),
	'params' => array('size' => 50),
	'type' => 'label',
	'value' => isset($arResult['CURRENCY_LIST'][$currencyID]) ? $arResult['CURRENCY_LIST'][$currencyID] : $currencyID
);
// <-- CURRENCY

// PROBABILITY -->
// PROBABILITY is displayed in sidebar. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit && !$isExternal)
{
	$arResult['EDITABLE_FIELDS'][] = 'PROBABILITY';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'PROBABILITY',
	'name' => GetMessage('CRM_FIELD_PROBABILITY'),
	'type' => 'label',
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['PROBABILITY']) ? $arResult['ELEMENT']['PROBABILITY'] : ''
);
// <-- PROBABILITY
// COMMENTS -->
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'COMMENTS';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMMENTS',
	'name' => GetMessage('CRM_FIELD_COMMENTS'),
	'type' => 'label',
	'value' => isset($arResult['ELEMENT']['COMMENTS']) ? $arResult['ELEMENT']['COMMENTS'] : '',
	'params' => array()
);
// <-- COMMENTS

// OPENED -->
// OPENED is displayed in sidebar. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'OPENED';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'OPENED',
	'name' => GetMessage('CRM_FIELD_OPENED'),
	'type' => 'label',
	'params' => array(),
	'value' => $arResult['ELEMENT']['OPENED'] == 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO')
);
// <-- OPENED

// CLOSED -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CLOSED',
	'name' => GetMessage('CRM_FIELD_CLOSED'),
	'type' => 'label',
	'value' => (isset($arResult['ELEMENT']['CLOSED']) ? ($arResult['ELEMENT']['CLOSED'] == 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO')) : GetMessage('MAIN_NO'))
);
// <-- CLOSED

// ASSIGNED_BY_ID -->
// ASSIGNED_BY_ID is displayed in sidebar. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'ASSIGNED_BY_ID';
}
ob_start();
$APPLICATION->IncludeComponent('bitrix:main.user.link',
		'',
		array(
		'ID' => $arResult['ELEMENT']['ASSIGNED_BY'],
		'HTML_ID' => 'crm_assigned_by',
		'USE_THUMBNAIL_LIST' => 'Y',
		'SHOW_YEAR' => 'M',
		'CACHE_TYPE' => 'A',
		'CACHE_TIME' => '3600',
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
		'SHOW_LOGIN' => 'Y',
		),
		false,
		array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ASSIGNED_BY_ID',
	'name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID'),
	'type' => 'custom',
	'value' => $sVal
);
// <-- ASSIGNED_BY_ID

// <-- DEAL SECTION

// ADDITIONAL SECTION -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_additional',
	'name' => GetMessage('CRM_SECTION_ADDITIONAL'),
	'type' => 'section'
);

ob_start();
$APPLICATION->IncludeComponent('bitrix:main.user.link',
	'',
	array(
		'ID' => $arResult['ELEMENT']['CREATED_BY'],
		'HTML_ID' => 'crm_created_by',
		'USE_THUMBNAIL_LIST' => 'Y',
		'SHOW_YEAR' => 'M',
		'CACHE_TYPE' => 'A',
		'CACHE_TIME' => '3600',
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
		'SHOW_LOGIN' => 'Y',
	),
	false,
	array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CREATED_BY_ID',
	'name' => GetMessage('CRM_FIELD_CREATED_BY_ID'),
	'type' => 'custom',
	'value' => $sVal
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'DATE_CREATE',
	'name' => GetMessage('CRM_FIELD_DATE_CREATE'),
	'params' => array('size' => 50),
	'type' => 'label',
	'value' => isset($arResult['ELEMENT']['DATE_CREATE']) ? FormatDate('x', MakeTimeStamp($arResult['ELEMENT']['DATE_CREATE'])) : ''
);

if ($arResult['ELEMENT']['DATE_CREATE'] != $arResult['ELEMENT']['DATE_MODIFY'])
{
	ob_start();
	$APPLICATION->IncludeComponent('bitrix:main.user.link',
		'',
		array(
			'ID' => $arResult['ELEMENT']['MODIFY_BY'],
			'HTML_ID' => 'crm_modify_by',
			'USE_THUMBNAIL_LIST' => 'Y',
			'SHOW_YEAR' => 'M',
			'CACHE_TYPE' => 'A',
			'CACHE_TIME' => '3600',
			'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
			'SHOW_LOGIN' => 'Y',
		),
		false,
		array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
	);
	$sVal = ob_get_contents();
	ob_end_clean();
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'MODIFY_BY_ID',
		'name' => GetMessage('CRM_FIELD_MODIFY_BY_ID'),
		'type' => 'custom',
		'value' => $sVal
	);
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'DATE_MODIFY',
		'name' => GetMessage('CRM_FIELD_DATE_MODIFY'),
		'params' => array('size' => 50),
		'type' => 'label',
		'value' => isset($arResult['ELEMENT']['DATE_MODIFY']) ? FormatDate('x', MakeTimeStamp($arResult['ELEMENT']['DATE_MODIFY'])) : ''
	);
}
// <-- ADDITIONAL SECTION

$arResult['FIELDS']['tab_details'][] = array(
	'id' => 'section_details',
	'name' => GetMessage('CRM_SECTION_DETAILS'),
	'type' => 'section'
);

// DATE SECTION -->
$arResult['FIELDS']['tab_details'][] = array(
	'id' => 'section_date',
	'name' => GetMessage('CRM_SECTION_DATE'),
	'type' => 'section'
);


if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'BEGINDATE';
}

$arResult['FIELDS']['tab_details'][] = array(
	'id' => 'BEGINDATE',
	'name' => GetMessage('CRM_FIELD_BEGINDATE'),
	'params' => array('size' => 20),
	'type' => 'label',
	'value' => !empty($arResult['ELEMENT']['BEGINDATE']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['BEGINDATE']), 'SHORT', SITE_ID)) : ''
);

if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'CLOSEDATE';
}

$arResult['FIELDS']['tab_details'][] = array(
	'id' => 'CLOSEDATE',
	'name' => GetMessage('CRM_FIELD_CLOSEDATE'),
	'params' => array('size' => 20),
	'type' => 'label',
	'value' => !empty($arResult['ELEMENT']['CLOSEDATE']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arResult['ELEMENT']['CLOSEDATE']), 'SHORT', SITE_ID)) : ''
);

// <-- DATE SECTION

// WEB-STORE SECTION -->
$enableWebStore = true;
$strEditOrderHtml = '';
if($isExternal)
{
	$dbSalesList = CCrmExternalSale::GetList(
		array("NAME" => "ASC", "SERVER" => "ASC"),
		array("ID" => $arResult['ELEMENT']['ORIGINATOR_ID'])
	);
	if ($arSale = $dbSalesList->GetNext())
		$strEditOrderHtml .= ($arSale["NAME"] != "" ? $arSale["NAME"] : $arSale["SERVER"]);
}
else
{
	$dbSalesList = CCrmExternalSale::GetList(
		array(),
		array("ACTIVE" => "Y")
	);

	$enableWebStore = $dbSalesList->Fetch() !== false;
}

if($enableWebStore)
{
	$arResult['FIELDS']['tab_details'][] = array(
		'id' => 'section_web_store',
		'name' => GetMessage('CRM_SECTION_WEB_STORE'),
		'type' => 'section'
	);

	$arResult['FIELDS']['tab_details'][] = array(
		'id' => 'SALE_ORDER',
		'name' => GetMessage('CRM_FIELD_SALE_ORDER1'),
		'type' => 'custom',
		'value' => isset($strEditOrderHtml[0]) ? $strEditOrderHtml : htmlspecialcharsbx(GetMessage('MAIN_NO'))
	);
}
// <-- WEB-STORE SECTION
$arResult['USER_FIELD_COUNT'] = $CCrmUserType->AddFields(
	$arResult['FIELDS']['tab_details'],
	$arResult['ELEMENT']['ID'],
	$arResult['FORM_ID'],
	false,
	true,
	false,
	array(
		'FILE_URL_TEMPLATE' =>
			"/bitrix/components/bitrix/crm.deal.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#"
	)
);

if($enableWebStore)
{
	$strAdditionalInfoHtml = '';
	if ($isExternal &&  isset($arResult['ELEMENT']['ADDITIONAL_INFO']))
	{
		$arAdditionalInfo = unserialize($arResult['ELEMENT']['~ADDITIONAL_INFO']);
		if (is_array($arAdditionalInfo) && count($arAdditionalInfo) > 0)
		{
			foreach ($arAdditionalInfo as $k => $v)
			{
				$msgID =  'CRM_SALE_'.$k;
				$k1 = HasMessage($msgID) ? GetMessage($msgID) : $k;
				if (is_bool($v))
					$v = $v ? GetMessage('CRM_SALE_YES') : GetMessage('CRM_SALE_NO');
				$strAdditionalInfoHtml .= '<span>'.htmlspecialcharsbx($k1).'</span>: <span>'.htmlspecialcharsbx($v).'</span><br/>';
			}
		}
	}

	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'ADDITIONAL_INFO',
		'name' => GetMessage('CRM_FIELD_ADDITIONAL_INFO'),
		'type' => 'custom',
		'value' => isset($strAdditionalInfoHtml[0]) ? $strAdditionalInfoHtml : htmlspecialcharsbx(GetMessage('MAIN_NO'))
	);
}

// PRODUCT ROW SECTION -->
$arResult['FIELDS']['tab_product_rows'][] = array(
	'id' => 'section_product_rows',
	'name' => GetMessage('CRM_SECTION_PRODUCT_ROWS'),
	'type' => 'section'
);
$APPLICATION->AddHeadScript($this->GetPath().'/sale.js');

$sProductsHtml = '<script type="text/javascript">var extSaleGetRemoteFormLocal = {"PRINT":"'.GetMessage("CRM_EXT_SALE_DEJ_PRINT").'","SAVE":"'.GetMessage("CRM_EXT_SALE_DEJ_SAVE").'","ORDER":"'.GetMessage("CRM_EXT_SALE_DEJ_ORDER").'","CLOSE":"'.GetMessage("CRM_EXT_SALE_DEJ_CLOSE").'"};</script>';

if (intval($arResult['ELEMENT']['ORIGINATOR_ID']) > 0 && intval($arResult['ELEMENT']['ORIGIN_ID']) > 0)
{
	$sProductsHtml .= '<input type="button" value="'.GetMessage("CRM_EXT_SALE_CD_EDIT").'" onclick="ExtSaleGetRemoteForm('.$arResult['ELEMENT']['ORIGINATOR_ID'].', \'EDIT\', '.$arResult['ELEMENT']['ORIGIN_ID'].')">
	<input type="button" value="'.GetMessage("CRM_EXT_SALE_CD_VIEW").'" onclick="ExtSaleGetRemoteForm('.$arResult['ELEMENT']['ORIGINATOR_ID'].', \'VIEW\', '.$arResult['ELEMENT']['ORIGIN_ID'].')">
	<input type="button" value="'.GetMessage("CRM_EXT_SALE_CD_PRINT").'" onclick="ExtSaleGetRemoteForm('.$arResult['ELEMENT']['ORIGINATOR_ID'].', \'PRINT\', '.$arResult['ELEMENT']['ORIGIN_ID'].')"><br /><br />';
}

if($arParams['ELEMENT_ID'] > 0)
{
	ob_start();
	$APPLICATION->IncludeComponent('bitrix:crm.product_row.list',
		'',
		array(
			'OWNER_ID' => $arParams['ELEMENT_ID'],
			'OWNER_TYPE' => 'D',
			'PERMISSION_TYPE' => $enableInstantEdit && !$isExternal ? 'WRITE' : 'READ',
			'SAVING_MODE' => 'ONCHANGE',
			'CURRENCY_ID' => $currencyID
		),
		false,
		array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
	);
	$sProductsHtml .= ob_get_contents();
	ob_end_clean();
}

$arResult['FIELDS']['tab_product_rows'][] = array(
	'id' => 'PRODUCT_ROWS',
	'name' => GetMessage('CRM_FIELD_PRODUCT_ROWS'),
	'colspan' => true,
	'type' => 'custom',
	'value' => $sProductsHtml
);
// <-- PRODUCT ROW SECTION

$arResult['FIELDS']['tab_activity'][] = array(
	'id' => 'section_activity_grid',
	'name' => GetMessage('CRM_SECTION_ACTIVITY_MAIN'),
	'type' => 'section'
);

$arResult['FIELDS']['tab_activity'][] = array(
	'id' => 'DEAL_ACTIVITY_GRID',
	'name' => GetMessage('CRM_FIELD_DEAL_ACTIVITY'),
	'colspan' => true,
	'type' => 'crm_activity_list',
	'componentData' => array(
		'template' => 'grid',
		'params' => array(
			'BINDINGS' => array(
				array(
					'TYPE_NAME' => 'DEAL',
					'ID' => $arParams['ELEMENT_ID']
				)
			),
			'PREFIX' => 'DEAL_ACTIONS_GRID',
			'PERMISSION_TYPE' => 'WRITE',
			//'SHOW_MODE' => 'NOT_COMPLETED',
			'ENABLE_NAVIGATION' => 'Y',
			'AJAX_MODE' => 'Y',
			'AJAX_OPTION_JUMP' => 'N',
			'AJAX_OPTION_HISTORY' => 'N',
			'AJAX_ID' => isset($arParams['AJAX_ID']) ? $arParams['AJAX_ID'] : '',
			//'AJAX_INIT_EVENT' => 'BXInterfaceGridAfterReload',
			'FORM_TYPE' => 'show',
			'FORM_ID' => $arResult['FORM_ID'],
			'TAB_ID' => 'tab_activity',
			'USE_QUICK_FILTER' => true
		)
	)
);

if (!$CCrmDeal->cPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'READ'))
{
	ob_start();
	$arResult['CONTACT_COUNT'] = $APPLICATION->IncludeComponent(
		'bitrix:crm.contact.list',
		'',
		array(
			'CONTACT_COUNT' => '20',
			'PATH_TO_CONTACT_SHOW' => $arParams['PATH_TO_CONTACT_SHOW'],
			'PATH_TO_CONTACT_EDIT' => $arParams['PATH_TO_CONTACT_EDIT'],
			'PATH_TO_DEAL_EDIT' => $arParams['PATH_TO_DEAL_EDIT'],
			'INTERNAL_FILTER' => array('ID' => $arResult['ELEMENT']['CONTACT_ID']),
			'GRID_ID_SUFFIX' => 'DEAL_SHOW',
			'FORM_ID' => $arResult['FORM_ID'],
			'TAB_ID' => 'tab_contact'
		),
		false
	);
	$sVal = ob_get_contents();
	ob_end_clean();
	$arResult['FIELDS']['tab_contact'][] = array(
		'id' => 'DEAL_CONTACTS',
		'name' => GetMessage('CRM_FIELD_DEAL_CONTACTS'),
		'colspan' => true,
		'type' => 'custom',
		'value' => $sVal
	);
}

if (!$CCrmDeal->cPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'READ'))
{
	ob_start();
	$arResult['COMPANY_COUNT'] = $APPLICATION->IncludeComponent(
		'bitrix:crm.company.list',
		'',
		array(
			'CONTACT_COUNT' => '20',
			'PATH_TO_COMPANY_SHOW' => $arParams['PATH_TO_COMPANY_SHOW'],
			'PATH_TO_COMPANY_EDIT' => $arParams['PATH_TO_COMPANY_EDIT'],
			'PATH_TO_CONTACT_EDIT' => $arParams['PATH_TO_CONTACT_EDIT'],
			'PATH_TO_DEAL_EDIT' => $arParams['PATH_TO_DEAL_EDIT'],
			'INTERNAL_FILTER' => array('ID' => $arResult['ELEMENT']['COMPANY_ID']),
			'GRID_ID_SUFFIX' => 'DEAL_SHOW',
			'FORM_ID' => $arResult['FORM_ID'],
			'TAB_ID' => 'tab_company',
			'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
		),
		false
	);
	$sVal = ob_get_contents();
	ob_end_clean();

	$arResult['FIELDS']['tab_company'][] = array(
		'id' => 'DEAL_COMPANY',
		'name' => GetMessage('CRM_FIELD_DEAL_COMPANY'),
		'colspan' => true,
		'type' => 'custom',
		'value' => $sVal
	);
}

if (IsModuleInstalled('bizproc'))
{
	$arResult['FIELDS']['tab_bizproc'][] = array(
		'id' => 'section_bizproc',
		'name' => GetMessage('CRM_SECTION_BIZPROC_MAIN'),
		'type' => 'section'
	);

	$arResult['BIZPROC'] = 'Y';
	ob_start();

	if ((isset($_REQUEST['bizproc_task']) && strlen($_REQUEST['bizproc_task']) > 0))
	{
		$APPLICATION->IncludeComponent(
			'bitrix:bizproc.task',
			'',
			Array(
				'TASK_ID' => (int)$_REQUEST['bizproc_task'],
				'USER_ID' => 0,
				'WORKFLOW_ID' => '',
				'DOCUMENT_URL' =>  CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'],
					array(
						'deal_id' => $arResult['ELEMENT']['ID']
					)
				),
				'SET_TITLE' => 'Y',
				'SET_NAV_CHAIN' => 'Y'
			),
			'',
			array('HIDE_ICONS' => 'Y')
		);
	}
	elseif (isset($_REQUEST['bizproc_log']) && strlen($_REQUEST['bizproc_log']) > 0)
	{
		$APPLICATION->IncludeComponent('bitrix:bizproc.log',
			'',
			Array(
				'MODULE_ID' => 'crm',
				'ENTITY' => 'CCrmDocumentDeal',
				'DOCUMENT_TYPE' => 'DEAL',
				'COMPONENT_VERSION' => 2,
				'DOCUMENT_ID' => 'DEAL_'.$arResult['ELEMENT']['ID'],
				'ID' => $_REQUEST['bizproc_log'],
				'SET_TITLE'	=>	'Y',
				'INLINE_MODE' => 'Y',
				'AJAX_MODE' => 'N',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
			),
			'',
			array("HIDE_ICONS" => "Y")
		);
	}
	else if (isset($_REQUEST['bizproc_start']) && strlen($_REQUEST['bizproc_start']) > 0)
	{
		$APPLICATION->IncludeComponent('bitrix:bizproc.workflow.start',
			'',
			Array(
				'MODULE_ID' => 'crm',
				'ENTITY' => 'CCrmDocumentDeal',
				'DOCUMENT_TYPE' => 'DEAL',
				'DOCUMENT_ID' => 'DEAL_'.$arResult['ELEMENT']['ID'],
				'TEMPLATE_ID' => $_REQUEST['workflow_template_id'],
				'SET_TITLE'	=>	'Y'
			),
			'',
			array('HIDE_ICONS' => 'Y')
		);
	}
	else
	{
		$formTabKey = $arResult['FORM_ID'].'_active_tab';
		$APPLICATION->IncludeComponent('bitrix:bizproc.document',
			'',
			Array(
				'MODULE_ID' => 'crm',
				'ENTITY' => 'CCrmDocumentDeal',
				'DOCUMENT_TYPE' => 'DEAL',
				'DOCUMENT_ID' => 'DEAL_'.$arResult['ELEMENT']['ID'],
				'TASK_EDIT_URL' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'],
						array(
							'deal_id' => $arResult['ELEMENT']['ID']
						)),
					array('bizproc_task' => '#ID#', $formTabKey => 'tab_bizproc')
				),
				'WORKFLOW_LOG_URL' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'],
						array(
							'deal_id' => $arResult['ELEMENT']['ID']
						)),
					array('bizproc_log' => '#ID#', $formTabKey => 'tab_bizproc')
				),
				'WORKFLOW_START_URL' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'],
						array(
							'deal_id' => $arResult['ELEMENT']['ID']
						)),
					array('bizproc_start' => 1, $formTabKey => 'tab_bizproc')
				),
				'back_url' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'],
						array(
							'deal_id' => $arResult['ELEMENT']['ID']
						)),
					array($formTabKey => 'tab_bizproc')
				),
				'SET_TITLE'	=>	'Y'
			),
			'',
			array('HIDE_ICONS' => 'Y')
		);
	}
	$sVal = ob_get_contents();
	ob_end_clean();
	$arResult['FIELDS']['tab_bizproc'][] = array(
		'id' => 'DEAL_BIZPROC',
		'name' => GetMessage('CRM_FIELD_DEAL_BIZPROC'),
		'colspan' => true,
		'type' => 'custom',
		'value' => $sVal
	);
}

if (intval($arResult['ELEMENT']['LEAD_ID']) > 0 && !$CCrmDeal->cPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'READ'))
{
	ob_start();
	$arResult['LEAD_COUNT'] = $APPLICATION->IncludeComponent(
		'bitrix:crm.lead.list',
		'',
		array(
			'LEAD_COUNT' => '20',
			'PATH_TO_LEAD_SHOW' => $arParams['PATH_TO_LEAD_SHOW'],
			'PATH_TO_LEAD_EDIT' => $arParams['PATH_TO_LEAD_EDIT'],
			'PATH_TO_LEAD_CONVERT' => $arParams['PATH_TO_LEAD_CONVERT'],
			'INTERNAL_FILTER' => array('ID' => $arResult['ELEMENT']['LEAD_ID']),
			'GRID_ID_SUFFIX' => 'DEAL_SHOW',
			'FORM_ID' => $arResult['FORM_ID'],
			'TAB_ID' => 'tab_lead'
		),
		false
	);
	$sVal = ob_get_contents();
	ob_end_clean();
	$arResult['FIELDS']['tab_lead'][] = array(
		'id' => 'DEAL_LEAD',
		'name' => GetMessage('CRM_FIELD_DEAL_LEAD'),
		'colspan' => true,
		'type' => 'custom',
		'value' => $sVal
	);
}

$arResult['FIELDS']['tab_event'][] = array(
	'id' => 'section_event_grid',
	'name' => GetMessage('CRM_SECTION_EVENT_MAIN'),
	'type' => 'section'
);

ob_start();
$arResult['EVENT_COUNT'] = $APPLICATION->IncludeComponent(
	'bitrix:crm.event.view',
	'',
	array(
		'ENTITY_TYPE' => 'DEAL',
		'ENTITY_ID' => $arResult['ELEMENT']['ID'],
		'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => 'tab_event',
		'INTERNAL' => 'Y',
		'SHOW_INTERNAL_FILTER' => 'Y',
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
	),
	false
);
$sVal = ob_get_contents();
ob_end_clean();
$arResult['FIELDS']['tab_event'][] = array(
	'id' => 'DEAL_EVENT',
	'name' => GetMessage('CRM_FIELD_DEAL_EVENT'),
	'colspan' => true,
	'type' => 'custom',
	'value' => $sVal
);

// HACK: for to prevent title overwrite after AJAX call.
if(isset($_REQUEST['bxajaxid']))
{
	$APPLICATION->SetTitle('');
}
$this->IncludeComponentTemplate();
include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.deal/include/nav.php');
?>
