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

$CCrmLead = new CCrmLead();
if ($CCrmLead->cPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['CAN_EDIT'] = !$CCrmLead->cPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'WRITE');
$arResult['EDITABLE_FIELDS'] = array();

$arParams['PATH_TO_LEAD_LIST'] = CrmCheckPath('PATH_TO_LEAD_LIST', $arParams['PATH_TO_LEAD_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_LEAD_EDIT'] = CrmCheckPath('PATH_TO_LEAD_EDIT', $arParams['PATH_TO_LEAD_EDIT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&edit');
$arParams['PATH_TO_LEAD_SHOW'] = CrmCheckPath('PATH_TO_LEAD_SHOW', $arParams['PATH_TO_LEAD_SHOW'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&show');
$arParams['PATH_TO_LEAD_CONVERT'] = CrmCheckPath('PATH_TO_LEAD_CONVERT', $arParams['PATH_TO_LEAD_CONVERT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&convert');
$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
$arParams['PATH_TO_CONTACT_EDIT'] = CrmCheckPath('PATH_TO_CONTACT_EDIT', $arParams['PATH_TO_CONTACT_EDIT'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&edit');
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_COMPANY_EDIT'] = CrmCheckPath('PATH_TO_COMPANY_EDIT', $arParams['PATH_TO_COMPANY_EDIT'], $APPLICATION->GetCurPage().'?company_id=#company_id#&edit');
$arParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath('PATH_TO_DEAL_SHOW', $arParams['PATH_TO_DEAL_SHOW'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&show');
$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

CUtil::InitJSCore(array('ajax', 'tooltip'));

global $USER_FIELD_MANAGER;

$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmLead::$sUFEntityID);

$arResult['ELEMENT_ID'] = $arParams['ELEMENT_ID'] = (int) $arParams['ELEMENT_ID'];

$obFields = CCrmLead::GetListEx(
	array(),
	array('ID' => $arParams['ELEMENT_ID'])
);
$arFields = $obFields->GetNext();

$dbResMultiFields = CCrmFieldMulti::GetList(
	array('ID' => 'asc'),
	array('ENTITY_ID' => 'LEAD', 'ELEMENT_ID' => $arParams['ELEMENT_ID'])
);
$arFields['FM'] = array();
while($arMultiFields = $dbResMultiFields->Fetch())
{
	$arFields['FM'][$arMultiFields['TYPE_ID']][$arMultiFields['ID']] = array('VALUE' => $arMultiFields['VALUE'], 'VALUE_TYPE' => $arMultiFields['VALUE_TYPE']);
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

$name = isset($arFields['~NAME']) ? $arFields['~NAME'] : '';
$secondName = isset($arFields['~SECOND_NAME']) ? $arFields['~SECOND_NAME'] : '';
$lastName = isset($arFields['~LAST_NAME']) ? $arFields['~LAST_NAME'] : '';

$arFields['~FORMATTED_NAME'] = ($name !== '' || $secondName !== '' || $lastName !== '')
	? CUser::FormatName(
	$arParams['NAME_TEMPLATE'],
	array(
		'LOGIN' => '',
		'NAME' => $name,
		'SECOND_NAME' => $secondName,
		'LAST_NAME' => $lastName
	),
	false,
	false
	) : '';

$arFields['FORMATTED_NAME'] = htmlspecialcharsbx($arFields['~FORMATTED_NAME']);

$arResult['ELEMENT'] = $arFields;
unset($arFields);

$arResult['TOP_EVENT_NAMES'] = array();

$dbEvents = CCrmEvent::GetList(array('DATE_CREATE' => 'DESC'), array('ENTITY_TYPE'=>'LEAD', 'ENTITY_ID'=>$arResult['ELEMENT']['ID']), 10);

$eventInfoTemplate = GetMessage('CRM_LEAD_EVENT_INFO');
while ($arEvent = $dbEvents->Fetch())
{
	$eventText1 = isset($arEvent['EVENT_TEXT_1']) ? $arEvent['EVENT_TEXT_1'] : '';
	$eventText2 = isset($arEvent['EVENT_TEXT_2']) ? $arEvent['EVENT_TEXT_2'] : '';
	$arResult['TOP_EVENT_NAMES'][] = str_replace(array('#EVENT_NAME#', '#NEW_VALUE#'), array(htmlspecialcharsbx($arEvent['EVENT_NAME']), htmlspecialcharsbx($eventText2 !== '' ? $eventText2 : $eventText1)), $eventInfoTemplate);
}

$arResult['BIZPROC_NAMES'] = CCrmBizProcHelper::GetDocumentNames(CCrmOwnerType::Lead, $arResult['ELEMENT']['ID']);

$isExternal = $arResult['IS_EXTERNAL'] = isset($arResult['ELEMENT']['ORIGINATOR_ID']) && isset($arResult['ELEMENT']['ORIGIN_ID']) && intval($arResult['ELEMENT']['ORIGINATOR_ID']) > 0 && intval($arResult['ELEMENT']['ORIGIN_ID']) > 0;
// Instant edit disallowed for leads in 'CONVERTED' status
$enableInstantEdit = $arResult['ENABLE_INSTANT_EDIT'] = $arResult['CAN_EDIT'] && $arResult['ELEMENT']['STATUS_ID'] !== 'CONVERTED';

//CURRENCY HACK (RUR is obsolete)
if(isset($arResult['ELEMENT']['CURRENCY_ID']) && $arResult['ELEMENT']['CURRENCY_ID'] === 'RUR')
{
	$arResult['ELEMENT']['CURRENCY_ID'] = 'RUB';
}

if (empty($arResult['ELEMENT']['ID']))
	LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_LIST'], array()));

$arResult['FORM_ID'] = 'CRM_LEAD_SHOW_V12';
$arResult['GRID_ID'] = 'CRM_LEAD_LIST_V12';
$arResult['BACK_URL'] = $arParams['PATH_TO_LEAD_LIST'];
$arResult['ALL_STATUS_LIST'] = $arResult['STATUS_LIST'] = CCrmStatus::GetStatusListEx('STATUS');
$arResult['SOURCE_LIST'] = CCrmStatus::GetStatusListEx('SOURCE');
$arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();

$arResult['FIELDS'] = array();

$readOnlyMode = !$enableInstantEdit || $isExternal;

// CONTACT INFO SECTION -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_contact_info',
	'name' => GetMessage('CRM_SECTION_CONTACT_INFO_2'),
	'type' => 'section'
);

// STATUS -->
// STATUS_ID is displayed in sidebar. The field is added for COMPATIBILITY ONLY

if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'STATUS_ID';
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'STATUS_ID',
	'name' => GetMessage('CRM_FIELD_STATUS_ID'),
	'type' => 'label',
	'value' => htmlspecialcharsbx($arResult['STATUS_LIST'][$arResult['ELEMENT']['STATUS_ID']]).(!empty($arResult['ELEMENT']['STATUS_DESCRIPTION']) ? " ({$arResult['ELEMENT']['STATUS_DESCRIPTION']})" : '')
);

// Prevent selection of 'CONVERTED' status in GUI
unset($arResult['STATUS_LIST']['CONVERTED']);
//<-- STATUS

// FULL NAME -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'FULL_NAME',
	'name' => GetMessage('CRM_FIELD_FULL_NAME'),
	'type' => 'label',
	'value' => CUser::FormatName(
		$arParams['NAME_TEMPLATE'],
		array(
			'LOGIN' => '',
			'NAME' => isset($arResult['ELEMENT']['~NAME']) ? $arResult['ELEMENT']['~NAME'] : '',
			'LAST_NAME' => isset($arResult['ELEMENT']['~LAST_NAME']) ? $arResult['ELEMENT']['~LAST_NAME'] : '',
			'SECOND_NAME' => isset($arResult['ELEMENT']['~SECOND_NAME']) ? $arResult['ELEMENT']['~SECOND_NAME'] : ''
		),
		false,
		true
	)
);
//<-- FULL NAME
// POST -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'POST',
	'name' => GetMessage('CRM_FIELD_POST'),
	'type' => 'label',
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['POST']) ? $arResult['ELEMENT']['POST'] : ''
);
//<-- POST
// COMPANY TITLE -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMPANY_TITLE',
	'name' => GetMessage('CRM_FIELD_COMPANY_TITLE_2'),
	'type' => 'label',
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['COMPANY_TITLE']) ? $arResult['ELEMENT']['COMPANY_TITLE'] : ''
);
// <-- COMPANY TITLE
// PHONE -->
ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.view',
	'instant_editor',
	array(
		'TYPE_ID' => 'PHONE',
		'ENTITY_ID' => 'LEAD',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'VALUES' =>$arResult['ELEMENT']['FM'],
		'READ_ONLY' => true,
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
	'colspan' => true,
	'value' => $sVal
);
//<-- PHONE

// EMAIL -->
//if(isset($arResult['ELEMENT']['FM']['EMAIL']))
//{
//	CCrmInstantEditorHelper::CreateMultiFields(
//		'EMAIL',
//		$arResult['ELEMENT']['FM']['EMAIL'],
//		$arResult['FIELDS']['tab_1'],
//		array(),
//		$readOnlyMode
//	);
//}
ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.view',
	'instant_editor',
	array(
		'TYPE_ID' => 'EMAIL',
		'ENTITY_ID' => 'LEAD',
		'ELEMENT_ID' => $arResult['ELEMENT']['ID'],
		'VALUES' =>$arResult['ELEMENT']['FM'],
		'READ_ONLY' => true,
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
	'colspan' => true,
	'value' => $sVal
);
//<-- EMAIL

$onDemandFields = array();

// WEB -->
if(isset($arResult['ELEMENT']['FM']['WEB']))
{
	CCrmInstantEditorHelper::CreateMultiFields(
		'WEB',
		$arResult['ELEMENT']['FM']['WEB'],
		$onDemandFields,
		array(),
		true
	);
}
//<-- WEB

// IM -->
if(isset($arResult['ELEMENT']['FM']['IM']))
{
	CCrmInstantEditorHelper::CreateMultiFields(
		'IM',
		$arResult['ELEMENT']['FM']['IM'],
		$onDemandFields,
		array(),
		true
	);
}
//<-- IM

$onDemandFieldQty = count($onDemandFields);
for($n = 0; $n < $onDemandFieldQty; $n++)
{
	$onDemandField = &$onDemandFields[$n];
	$onDemandField['displayOnDemand'] = true;
	if($n === $onDemandFieldQty - 1)
	{
		$onDemandField['addShowMore'] = true;
	}
	$arResult['FIELDS']['tab_1'][] = $onDemandField;
}
unset($onDemandField);

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
// CURRENCY_ID is displayed in sidebar. The field is added for COMPATIBILITY ONLY
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CURRENCY_ID',
	'name' => GetMessage('CRM_FIELD_CURRENCY_ID'),
	'params' => array('size' => 50),
	'type' => 'label',
	'value' => isset($arResult['CURRENCY_LIST'][$currencyID]) ? htmlspecialcharsbx($arResult['CURRENCY_LIST'][$currencyID]) : $currencyID
);
// <-- CURRENCY

// SOURCE_ID -->
// SOURCE_ID is displayed in sidebar. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit && !$isExternal)
{
	$arResult['EDITABLE_FIELDS'][] = 'SOURCE_ID';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SOURCE_ID',
	'name' => GetMessage('CRM_FIELD_SOURCE_ID'),
	'type' => 'label',
	'items' => $arResult['SOURCE_LIST'],
	'value' => htmlspecialcharsbx($arResult['SOURCE_LIST'][$arResult['ELEMENT']['SOURCE_ID']]).(!empty($arResult['ELEMENT']['SOURCE_DESCRIPTION']) ? " ({$arResult['ELEMENT']['SOURCE_DESCRIPTION']})" : '')
);
// <-- SOURCE_ID

// COMMENTS -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMMENTS',
	'name' => GetMessage('CRM_FIELD_COMMENTS'),
	'type' => 'label',
	'value' => isset($arResult['ELEMENT']['COMMENTS']) ? $arResult['ELEMENT']['COMMENTS'] : '',
	'params' => array()
);
// <-- COMMENTS

//<-- CONTACT INFO SECTION

// LEAD SECTION -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_lead_info',
	'name' => GetMessage('CRM_SECTION_LEAD'),
	'type' => 'section'
);

// ID -->
// ID is displayed in header. The field is added for COMPATIBILITY ONLY
/*$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ID',
	'name' => 'ID',
	'params' => array('size' => 50),
	'value' => $arResult['ELEMENT']['ID'],
	'type' => 'label'
);*/
//<-- ID

// TITLE -->
// TITLE is displayed in header. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'TITLE';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'TITLE',
	'name' => GetMessage('CRM_FIELD_TITLE'),
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['TITLE']) ? $arResult['ELEMENT']['TITLE'] : '',
	'type' => 'label'
);
// <-- TITLE
// OPENED -->
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'OPENED';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'OPENED',
	'name' => GetMessage('CRM_FIELD_OPENED'),
	'type' => 'label',
	'params' => array(),
	'value' =>  htmlspecialcharsbx($arResult['ELEMENT']['OPENED'] == 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'))
);
// <-- OPENED
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
//<-- LEAD SECTION

if ($arResult['ELEMENT']['STATUS_ID'] == 'CONVERTED')
{
	if (!$CCrmLead->cPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'READ'))
	{
		ob_start();
		$arResult['CONTACT_COUNT'] = $APPLICATION->IncludeComponent(
			'bitrix:crm.contact.list',
			'',
			array(
				'CONTACT_COUNT' => '20',
				'PATH_TO_CONTACT_SHOW' => $arParams['PATH_TO_CONTACT_SHOW'],
				'PATH_TO_CONTACT_EDIT' => $arParams['PATH_TO_CONTACT_EDIT'],
				'PATH_TO_COMPANY_SHOW' => $arParams['PATH_TO_COMPANY_SHOW'],
				'PATH_TO_DEAL_EDIT' => $arParams['PATH_TO_DEAL_EDIT'],
				'INTERNAL_FILTER' => array('ID' => $arResult['ELEMENT']['CONTACT_ID']),
				'GRID_ID_SUFFIX' => 'LEAD_SHOW',
				'FORM_ID' => $arResult['FORM_ID'],
				'TAB_ID' => 'tab_contact'
			),
			false
		);
		$sVal = ob_get_contents();
		ob_end_clean();

		$arResult['FIELDS']['tab_contact'][] = array(
			'id' => 'LEAD_CONTACTS',
			'name' => GetMessage('CRM_FIELD_LEAD_CONTACTS'),
			'colspan' => true,
			'type' => 'custom',
			'value' => $sVal
		);
	}
	if (!$CCrmLead->cPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'READ'))
	{
		ob_start();
		$arResult['COMPANY_COUNT'] = $APPLICATION->IncludeComponent(
			'bitrix:crm.company.list',
			'',
			array(
				'COMPANY_COUNT' => '20',
				'PATH_TO_COMPANY_SHOW' => $arParams['PATH_TO_COMPANY_SHOW'],
				'PATH_TO_COMPANY_EDIT' => $arParams['PATH_TO_COMPANY_EDIT'],
				'PATH_TO_CONTACT_EDIT' => $arParams['PATH_TO_CONTACT_EDIT'],
				'PATH_TO_DEAL_EDIT' => $arParams['PATH_TO_DEAL_EDIT'],
				'INTERNAL_FILTER' => array('ID' => $arResult['ELEMENT']['COMPANY_ID']),
				'GRID_ID_SUFFIX' => 'LEAD_SHOW',
				'FORM_ID' => $arResult['FORM_ID'],
				'TAB_ID' => 'tab_company',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
			),
			false
		);
		$sVal = ob_get_contents();
		ob_end_clean();

		$arResult['FIELDS']['tab_company'][] = array(
			'id' => 'LEAD_COMPANY',
			'name' => GetMessage('CRM_FIELD_LEAD_COMPANY'),
			'colspan' => true,
			'type' => 'custom',
			'value' => $sVal
		);
	}
	if (!$CCrmLead->cPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'READ'))
	{
		ob_start();
		$arResult['DEAL_COUNT'] = $APPLICATION->IncludeComponent(
			'bitrix:crm.deal.list',
			'',
			array(
				'DEAL_COUNT' => '20',
				'PATH_TO_DEAL_SHOW' => $arParams['PATH_TO_DEAL_SHOW'],
				'PATH_TO_DEAL_EDIT' => $arParams['PATH_TO_DEAL_EDIT'],
				'INTERNAL_FILTER' => array('LEAD_ID' => $arParams['ELEMENT_ID']),
				'GRID_ID_SUFFIX' => 'LEAD_SHOW',
				'FORM_ID' => $arResult['FORM_ID'],
				'TAB_ID' => 'tab_deal',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
			),
			false
		);
		$sVal = ob_get_contents();
		ob_end_clean();

		$arResult['FIELDS']['tab_deal'][] = array(
			'id' => 'LEAD_DEAL',
			'name' => GetMessage('CRM_FIELD_LEAD_DEAL'),
			'colspan' => true,
			'type' => 'custom',
			'value' => $sVal
		);
	}
}

// ADDITIONAL SECTION -->
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_additional',
	'name' => GetMessage('CRM_SECTION_ADDITIONAL'),
	'type' => 'section'
);


// CREATED_BY_ID -->
// CREATED_BY_ID is displayed in sidebar. The field is added for COMPATIBILITY ONLY
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
// <-- CREATED_BY_ID

// DATE_CREATE -->
// DATE_CREATE is displayed in sidebar. The field is added for COMPATIBILITY ONLY
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'DATE_CREATE',
	'name' => GetMessage('CRM_FIELD_DATE_CREATE'),
	'params' => array('size' => 50),
	'type' => 'label',
	'value' => isset($arResult['ELEMENT']['DATE_CREATE']) ? FormatDate('x', MakeTimeStamp($arResult['ELEMENT']['DATE_CREATE'])) : ''
);
// <-- DATE_CREATE

// MODIFY_BY_ID, DATE_MODIFY -->
// MODIFY_BY_ID, DATE_MODIFY are displayed in sidebar. The field is added for COMPATIBILITY ONLY
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

// ADDRESS -->
$arResult['FIELDS']['tab_details'][] = array(
	'id' => 'ADDRESS',
	'name' => GetMessage('CRM_FIELD_ADDRESS'),
	'type' => 'label',
	'value' => isset($arResult['ELEMENT']['ADDRESS']) ? nl2br($arResult['ELEMENT']['ADDRESS']) : ''
);
//<-- ADDRESS
$CCrmUserType->AddFields(
	$arResult['FIELDS']['tab_details'],
	$arResult['ELEMENT']['ID'],
	$arResult['FORM_ID'],
	false,
	true,
	false,
	array(
		'FILE_URL_TEMPLATE' =>
			"/bitrix/components/bitrix/crm.lead.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#"
	)
);

$arResult['FIELDS']['tab_activity'][] = array(
	'id' => 'section_activity_grid',
	'name' => GetMessage('CRM_SECTION_ACTIVITY_MAIN'),
	'type' => 'section'
);

global $DB;
$arResult['FIELDS']['tab_activity'][] = array(
	'id' => 'LEAD_ACTIVITY_GRID',
	'name' => GetMessage('CRM_FIELD_LEAD_ACTIVITY'),
	'colspan' => true,
	'type' => 'crm_activity_list',
	'componentData' => array(
		'template' => 'grid',
		'params' => array(
			'BINDINGS' => array(
				array(
					'TYPE_NAME' => 'LEAD',
					'ID' => $arParams['ELEMENT_ID']
				)
			),
			'PREFIX' => 'LEAD_ACTIONS_GRID',
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

// PRODUCT ROW SECTION -->
$arResult['FIELDS']['tab_product_rows'][] = array(
	'id' => 'section_product_rows',
	'name' => GetMessage('CRM_SECTION_PRODUCT_ROWS'),
	'type' => 'section'
);

$sProductsHtml = '';
if($arParams['ELEMENT_ID'] > 0)
{
	ob_start();
	$APPLICATION->IncludeComponent('bitrix:crm.product_row.list',
		'',
		array(
			'OWNER_ID' => $arParams['ELEMENT_ID'],
			'OWNER_TYPE' => 'L',
			'PERMISSION_TYPE' => $enableInstantEdit && !$isExternal ? 'WRITE' : 'READ',
			'SAVING_MODE' => 'ONCHANGE',
			'CURRENCY_ID' => $currencyID
		),
		false,
		array('HIDE_ICONS' => 'Y', 'ACTIVE_COMPONENT'=>'Y')
	);
	$sProductsHtml = ob_get_contents();
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
				'DOCUMENT_URL' =>  CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'],
					array(
						'lead_id' => $arResult['ELEMENT']['ID']
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
				'ENTITY' => 'CCrmDocumentLead',
				'DOCUMENT_TYPE' => 'LEAD',
				'COMPONENT_VERSION' => 2,
				'DOCUMENT_ID' => 'LEAD_'.$arResult['ELEMENT']['ID'],
				'ID' => $_REQUEST['bizproc_log'],
				'SET_TITLE'	=>	'Y',
				'INLINE_MODE' => 'Y',
				'AJAX_MODE' => 'N'
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
				'ENTITY' => 'CCrmDocumentLead',
				'DOCUMENT_TYPE' => 'LEAD',
				'DOCUMENT_ID' => 'LEAD_'.$arResult['ELEMENT']['ID'],
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
				'ENTITY' => 'CCrmDocumentLead',
				'DOCUMENT_TYPE' => 'LEAD',
				'DOCUMENT_ID' => 'LEAD_'.$arResult['ELEMENT']['ID'],
				'TASK_EDIT_URL' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'],
					array(
						'lead_id' => $arResult['ELEMENT']['ID']
					)),
					array('bizproc_task' => '#ID#', $formTabKey => 'tab_bizproc')
				),
				'WORKFLOW_LOG_URL' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'],
					array(
						'lead_id' => $arResult['ELEMENT']['ID']
					)),
					array('bizproc_log' => '#ID#', $formTabKey => 'tab_bizproc')
				),
				'WORKFLOW_START_URL' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'],
					array(
						'lead_id' => $arResult['ELEMENT']['ID']
					)),
					array('bizproc_start' => 1, $formTabKey => 'tab_bizproc')
				),
				'back_url' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'],
					array(
						'lead_id' => $arResult['ELEMENT']['ID']
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
		'id' => 'LEAD_BIZPROC',
		'name' => GetMessage('CRM_FIELD_LEAD_BIZPROC'),
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
		'ENTITY_TYPE' => 'LEAD',
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
	'id' => 'LEAD_EVENT',
	'name' => GetMessage('CRM_FIELD_LEAD_EVENT'),
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
include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.lead/include/nav.php');
?>
