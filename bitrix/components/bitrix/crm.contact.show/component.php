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

$CCrmContact = new CCrmContact();
if ($CCrmContact->cPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arParams['PATH_TO_CONTACT_LIST'] = CrmCheckPath('PATH_TO_CONTACT_LIST', $arParams['PATH_TO_CONTACT_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
$arParams['PATH_TO_CONTACT_EDIT'] = CrmCheckPath('PATH_TO_CONTACT_EDIT', $arParams['PATH_TO_CONTACT_EDIT'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&edit');
$arParams['PATH_TO_LEAD_SHOW'] = CrmCheckPath('PATH_TO_LEAD_SHOW', $arParams['PATH_TO_LEAD_SHOW'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&show');
$arParams['PATH_TO_LEAD_EDIT'] = CrmCheckPath('PATH_TO_LEAD_EDIT', $arParams['PATH_TO_LEAD_EDIT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&edit');
$arParams['PATH_TO_LEAD_CONVERT'] = CrmCheckPath('PATH_TO_LEAD_CONVERT', $arParams['PATH_TO_LEAD_CONVERT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&convert');
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_COMPANY_EDIT'] = CrmCheckPath('PATH_TO_COMPANY_EDIT', $arParams['PATH_TO_COMPANY_EDIT'], $APPLICATION->GetCurPage().'?company_id=#company_id#&edit');
$arParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath('PATH_TO_DEAL_SHOW', $arParams['PATH_TO_DEAL_SHOW'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&show');
$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');
$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

CUtil::InitJSCore(array('ajax', 'tooltip'));
global $USER_FIELD_MANAGER;
$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, CCrmContact::$sUFEntityID);

$bEdit = false;
$arResult['ELEMENT_ID'] = $arParams['ELEMENT_ID'] = (int) $arParams['ELEMENT_ID'];

$obFields = CCrmContact::GetListEx(
	array(),
	array('ID' => $arParams['ELEMENT_ID'])
);
$arFields = $obFields->GetNext();
if(!is_array($arFields))
{
	LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_LIST'], array()));
}

$arFields['FM'] = array();
$dbResMultiFields = CCrmFieldMulti::GetList(
	array('ID' => 'asc'),
	array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => $arResult['ELEMENT_ID'])
);
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
		'user_id' => $arFields['~CREATED_BY']
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
		'user_id' => $arFields['~MODIFY_BY']
	)
);

$arFields['~FORMATTED_NAME'] = CUser::FormatName(
	$arParams['NAME_TEMPLATE'],
	array(
		'LOGIN' => '',
		'NAME' => isset($arFields['~NAME']) ? $arFields['~NAME'] : '',
		'LAST_NAME' => isset($arFields['~LAST_NAME']) ? $arFields['~LAST_NAME'] : '',
		'SECOND_NAME' => isset($arFields['~SECOND_NAME']) ? $arFields['~SECOND_NAME'] : ''
	),
	false,
	false
);

$arFields['FORMATTED_NAME'] = htmlspecialcharsbx($arFields['~FORMATTED_NAME']);

$arResult['ELEMENT'] = $arFields;
unset($arFields);

$arResult['TOP_EVENT_NAMES'] = array();
$dbEvents = CCrmEvent::GetList(array('DATE_CREATE' => 'DESC'), array('ENTITY_TYPE'=>'CONTACT', 'ENTITY_ID'=>$arResult['ELEMENT']['ID']), 10);
$eventInfoTemplate = GetMessage('CRM_CONTACT_EVENT_INFO');
while ($arEvent = $dbEvents->Fetch())
{
	$eventText1 = isset($arEvent['EVENT_TEXT_1']) ? $arEvent['EVENT_TEXT_1'] : '';
	$eventText2 = isset($arEvent['EVENT_TEXT_2']) ? $arEvent['EVENT_TEXT_2'] : '';
	$arResult['TOP_EVENT_NAMES'][] = str_replace(array('#EVENT_NAME#', '#NEW_VALUE#'), array(htmlspecialcharsbx($arEvent['EVENT_NAME']), htmlspecialcharsbx($eventText2 !== '' ? $eventText2 : $eventText1)), $eventInfoTemplate);
}

$arResult['BIZPROC_NAMES'] = CCrmBizProcHelper::GetDocumentNames(CCrmOwnerType::Contact, $arResult['ELEMENT']['ID']);

$arResult['FORM_ID'] = 'CRM_CONTACT_SHOW_V12';
$arResult['GRID_ID'] = 'CRM_CONTACT_LIST_V12';
$arResult['BACK_URL'] = $arParams['PATH_TO_CONTACT_LIST'];
$arResult['SOURCE_LIST'] = CCrmStatus::GetStatusListEx('SOURCE');
$arResult['TYPE_LIST'] = CCrmStatus::GetStatusListEx('CONTACT_TYPE');

$arResult['PATH_TO_COMPANY_SHOW'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'],
	array(
		'company_id' => $arResult['ELEMENT']['COMPANY_ID']
	)
);

$arResult['CAN_EDIT'] = !$CCrmContact->cPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'WRITE');
$arResult['EDITABLE_FIELDS'] = array();

$enableInstantEdit = $arResult['ENABLE_INSTANT_EDIT'] = $arResult['CAN_EDIT'];
$readOnlyMode = !$enableInstantEdit;

$arResult['FIELDS'] = array();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_contact_info',
	'name' => GetMessage('CRM_SECTION_CONTACT_INFO_2'),
	'type' => 'section'
);

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
			'LAST_NAME' => isset($arResult['ELEMENT']['~NAME']) ? $arResult['ELEMENT']['~NAME'] : '',
			'SECOND_NAME' => isset($arResult['ELEMENT']['~NAME']) ? $arResult['ELEMENT']['~NAME'] : ''
		),
		false,
		true
	)
);
//<-- FULL NAME

// COMPANY -->
if (!$CCrmContact->cPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'READ'))
{
	$companyField =  array(
		'id' => 'COMPANY_TITLE',
		'name' => GetMessage('CRM_FIELD_COMPANY_TITLE'),
		'params' => array('size' => 50),
		'type' => 'label',
		'value' => isset($arResult['ELEMENT']['COMPANY_TITLE']) ?
			'<a href="'.$arResult['PATH_TO_COMPANY_SHOW'].'" id="balloon_'.$arResult['GRID_ID'].'_'.$arResult['ELEMENT']['ID'].'">'.$arResult['ELEMENT']['COMPANY_TITLE'].'</a>'.
				'<script type="text/javascript">BX.tooltip("COMPANY_'.$arResult['ELEMENT']['~COMPANY_ID'].'", "balloon_'.$arResult['GRID_ID'].'_'.$arResult['ELEMENT']['ID'].'", "/bitrix/components/bitrix/crm.company.show/card.ajax.php", "crm_balloon_company", true);</script>'
			: ''
	);

	$arResult['FIELDS']['tab_1'][] = $arResult['COMPANY_FIELD'] = $companyField;
}
// <-- COMPANY

// POST -->
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'POST';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'POST',
	'name' => GetMessage('CRM_FIELD_POST'),
	'type' => 'label',
	'params' => array('size' => 50),
	'value' => isset($arResult['ELEMENT']['~POST']) ? $arResult['ELEMENT']['~POST'] : ''
);
//<-- POST

// PHONE -->
ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.view',
	'instant_editor',
	array(
		'TYPE_ID' => 'PHONE',
		'ENTITY_ID' => 'CONTACT',
		'ELEMENT_ID' => $arResult['ELEMENT_ID'],
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
ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.view',
	'instant_editor',
	array(
		'TYPE_ID' => 'EMAIL',
		'ENTITY_ID' => 'CONTACT',
		'ELEMENT_ID' => $arResult['ELEMENT_ID'],
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

// WEB -->
ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.view',
	'instant_editor',
	array(
		'TYPE_ID' => 'WEB',
		'ENTITY_ID' => 'CONTACT',
		'ELEMENT_ID' => $arResult['ELEMENT_ID'],
		'VALUES' =>$arResult['ELEMENT']['FM'],
		'READ_ONLY' => true,
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
	'colspan' => true,
	'value' => $sVal
);
// <-- WEB
// IM -->
ob_start();
$APPLICATION->IncludeComponent('bitrix:crm.field_multi.view',
	'instant_editor',
	array(
		'TYPE_ID' => 'IM',
		'ENTITY_ID' => 'CONTACT',
		'ELEMENT_ID' => $arResult['ELEMENT_ID'],
		'VALUES' =>$arResult['ELEMENT']['FM'],
		'READ_ONLY' => true,
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
	'colspan' => true,
	'value' => $sVal
);
// <-- IM

// TYPE_ID is displayed in sidebar. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'TYPE_ID';
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'TYPE_ID',
	'name' => GetMessage('CRM_FIELD_TYPE_ID'),
	'type' => 'label',
	'items' => $arResult['TYPE_LIST'],
	'value' => $arResult['TYPE_LIST'][$arResult['ELEMENT']['TYPE_ID']]
);
// <-- TYPE_ID

// SOURCE_ID is displayed in sidebar. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'SOURCE_ID';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SOURCE_ID',
	'name' => GetMessage('CRM_FIELD_SOURCE_ID'),
	'type' => 'label',
	'items' => $arResult['SOURCE_LIST'],
	'value' => $arResult['SOURCE_LIST'][$arResult['ELEMENT']['SOURCE_ID']]
);
// <-- SOURCE_ID

// SOURCE_DESCRIPTION is displayed in sidebar. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'SOURCE_DESCRIPTION';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SOURCE_DESCRIPTION',
	'name' => GetMessage('CRM_FIELD_SOURCE_DESCRIPTION'),
	'type' => 'label',
	'value' => !empty($arResult['ELEMENT']['SOURCE_DESCRIPTION']) ? $arResult['ELEMENT']['SOURCE_DESCRIPTION'] : ''
);
// <-- SOURCE_ID

// PHOTO -->
if(!isset($arResult['ELEMENT']['~PHOTO']))
{
	$arResult['PHOTO_HTML']  = '';
}
else
{
	$arPhoto = CFile::ResizeImageGet(
		$arResult['ELEMENT']['~PHOTO'],
		array('width' => 50, 'height' => 50),
		BX_RESIZE_IMAGE_PROPORTIONAL,
		false
	);
	$arResult['PHOTO_HTML'] = CFile::ShowImage($arPhoto['src'], 50, 50, 'border=0');
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'PHOTO',
	'name' => GetMessage('CRM_FIELD_PHOTO'),
	'params' => array(),
	'type' => 'custom',
	'value' => $arResult['PHOTO_HTML']
);
// <-- PHOTO

// COMMENTS -->
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'COMMENTS';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'COMMENTS',
	'name' => GetMessage('CRM_FIELD_COMMENTS'),
	'type' => 'label',
	'value' => isset($arResult['ELEMENT']['COMMENTS']) ? strval($arResult['ELEMENT']['COMMENTS']) : '',
	'params' => array()
);
// <-- COMMENTS

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_additional',
	'name' => GetMessage('CRM_SECTION_ADDITIONAL'),
	'type' => 'section'
);
/*$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'BIRTHDATE',
	'name' => GetMessage('CRM_FIELD_BIRTHDATE'),
	'params' => array('size' => 50),
	'type' => 'label',
	'value' => isset($arResult['ELEMENT']['BIRTHDATE']) ? ConvertTimeStamp(strtotime($arResult['ELEMENT']['BIRTHDATE'])) : ''
);*/

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

// EXPORT -->
// EXPORT is displayed in sidebar. The field is added for COMPATIBILITY ONLY
if($enableInstantEdit)
{
	$arResult['EDITABLE_FIELDS'][] = 'EXPORT';
}
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'EXPORT',
	'name' => GetMessage('CRM_FIELD_EXPORT'),
	'type' => 'label',
	'params' => array(),
	'value' => isset($arResult['ELEMENT']['EXPORT']) && $arResult['ELEMENT']['EXPORT'] == 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO')
);
// <-- EXPORT

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

// DATE_CREATE is displayed in sidebar. The field is added for COMPATIBILITY ONLY
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'DATE_CREATE',
	'name' => GetMessage('CRM_FIELD_DATE_CREATE'),
	'params' => array('size' => 50),
	'type' => 'label',
	'value' => isset($arResult['ELEMENT']['DATE_CREATE']) ? FormatDate('x', MakeTimeStamp($arResult['ELEMENT']['DATE_CREATE'])) : ''
);
// <-- DATE_CREATE

if ($arResult['ELEMENT']['DATE_CREATE'] != $arResult['ELEMENT']['DATE_MODIFY'])
{
	// MODIFY_BY_ID is displayed in sidebar. The field is added for COMPATIBILITY ONLY
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
	// <-- MODIFY_BY_ID

	// DATE_MODIFY is displayed in sidebar. The field is added for COMPATIBILITY ONLY
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'DATE_MODIFY',
		'name' => GetMessage('CRM_FIELD_DATE_MODIFY'),
		'params' => array('size' => 50),
		'type' => 'label',
		'value' => isset($arResult['ELEMENT']['DATE_MODIFY']) ? FormatDate('x', MakeTimeStamp($arResult['ELEMENT']['DATE_MODIFY'])) : ''
	);
	// <-- DATE_MODIFY
}

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
			"/bitrix/components/bitrix/crm.contact.show/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#"
	)
);

if (IsModuleInstalled('bizproc'))
{
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
				'DOCUMENT_URL' =>  CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'],
					array(
						'contact_id' => $arResult['ELEMENT']['ID']
					)
				),
				'SET_TITLE' => 'Y',
				'SET_NAV_CHAIN' => 'Y',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
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
				'ENTITY' => 'CCrmDocumentContact',
				'DOCUMENT_TYPE' => 'CONTACT',
				'COMPONENT_VERSION' => 2,
				'DOCUMENT_ID' => 'CONTACT_'.$arResult['ELEMENT']['ID'],
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
				'ENTITY' => 'CCrmDocumentContact',
				'DOCUMENT_TYPE' => 'CONTACT',
				'DOCUMENT_ID' => 'CONTACT_'.$arResult['ELEMENT']['ID'],
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
				'ENTITY' => 'CCrmDocumentContact',
				'DOCUMENT_TYPE' => 'CONTACT',
				'DOCUMENT_ID' => 'CONTACT_'.$arResult['ELEMENT']['ID'],
				'TASK_EDIT_URL' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'],
					array(
						'contact_id' => $arResult['ELEMENT']['ID']
					)),
					array('bizproc_task' => '#ID#', $formTabKey => 'tab_bizproc')
				),
				'WORKFLOW_LOG_URL' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'],
					array(
						'contact_id' => $arResult['ELEMENT']['ID']
					)),
					array('bizproc_log' => '#ID#', $formTabKey => 'tab_bizproc')
				),
				'WORKFLOW_START_URL' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'],
					array(
						'contact_id' => $arResult['ELEMENT']['ID']
					)),
					array('bizproc_start' => 1, $formTabKey => 'tab_bizproc')
				),
				'back_url' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'],
					array(
						'contact_id' => $arResult['ELEMENT']['ID']
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
		'id' => 'CONTACT_BIZPROC',
		'name' => GetMessage('CRM_FIELD_CONTACT_BIZPROC'),
		'colspan' => true,
		'type' => 'custom',
		'value' => $sVal
	);
}

if (!$CCrmContact->cPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'READ'))
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
			'INTERNAL_FILTER' => array('CONTACT_ID' => $arResult['ELEMENT']['ID']),
			'GRID_ID_SUFFIX' => 'CONTACT_SHOW',
			'FORM_ID' => $arResult['FORM_ID'],
			'TAB_ID' => 'tab_lead',
			'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
		),
		false
	);
	$sVal = ob_get_contents();
	ob_end_clean();
	if (intval($arResult['LEAD_COUNT']) > 0)
	{
		$arResult['FIELDS']['tab_lead'][] = array(
			'id' => 'LEAD_CONTACTS',
			'name' => GetMessage('CRM_FIELD_CONTACT_LEAD'),
			'colspan' => true,
			'type' => 'custom',
			'value' => $sVal
		);
	}
}

if (!$CCrmContact->cPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'READ'))
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
			'INTERNAL_ID' => $arResult['ELEMENT']['COMPANY_ID'],
			'INTERNAL_CONTEXT' => array('CONTACT_ID' => $arResult['ELEMENT']['ID']),
			'GRID_ID_SUFFIX' => 'CONTACT_SHOW',
			'FORM_ID' => $arResult['FORM_ID'],
			'TAB_ID' => 'tab_company',
			'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
		),
		false
	);
	$sVal = ob_get_contents();
	ob_end_clean();
	$arResult['FIELDS']['tab_company'][] = array(
		'id' => 'CONTACT_COMPANY',
		'name' => GetMessage('CRM_FIELD_CONTACT_COMPANY'),
		'colspan' => true,
		'type' => 'custom',
		'value' => $sVal
	);
}
if (!$CCrmContact->cPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'READ'))
{
	ob_start();
	$arResult['DEAL_COUNT'] = $APPLICATION->IncludeComponent(
		'bitrix:crm.deal.list',
		'',
		array(
			'DEAL_COUNT' => '20',
			'PATH_TO_DEAL_SHOW' => $arParams['PATH_TO_DEAL_SHOW'],
			'PATH_TO_DEAL_EDIT' => $arParams['PATH_TO_DEAL_EDIT'],
			'INTERNAL_FILTER' => array('CONTACT_ID' => $arParams['ELEMENT_ID']),
			'GRID_ID_SUFFIX' => 'CONTACT_SHOW',
			'FORM_ID' => $arResult['FORM_ID'],
			'TAB_ID' => 'tab_deal',
			'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
		),
		false
	);
	$sVal = ob_get_contents();
	ob_end_clean();

	$arResult['FIELDS']['tab_deal'][] = array(
		'id' => 'CONTACT_DEAL',
		'name' => GetMessage('CRM_FIELD_CONTACT_DEAL'),
		'colspan' => true,
		'type' => 'custom',
		'value' => $sVal
	);
}

$arResult['FIELDS']['tab_event'][] = array(
	'id' => 'section_event_contact',
	'name' => GetMessage('CRM_SECTION_EVENT'),
	'type' => 'section'
);

ob_start();
$arResult['EVENT_COUNT'] = $APPLICATION->IncludeComponent(
	'bitrix:crm.event.view',
	'',
	array(
		'ENTITY_TYPE' => 'CONTACT',
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
	'id' => 'CONTACT_EVENT',
	'name' => GetMessage('CRM_FIELD_CONTACT_EVENT'),
	'colspan' => true,
	'type' => 'custom',
	'value' => $sVal
);

$arResult['FIELDS']['tab_activity'][] = array(
	'id' => 'section_activity_grid',
	'name' => GetMessage('CRM_SECTION_ACTIVITY_GRID'),
	'type' => 'section'
);

$arResult['FIELDS']['tab_activity'][] = array(
	'id' => 'CONTACT_ACTIVITY_GRID',
	'name' => GetMessage('CRM_FIELD_LEAD_ACTIVITY'),
	'colspan' => true,
	'type' => 'crm_activity_list',
	'componentData' => array(
		'template' => 'grid',
		'params' => array(
			'BINDINGS' => array(
				array(
					'TYPE_NAME' => 'CONTACT',
					'ID' => $arParams['ELEMENT_ID']
				)
			),
			'PREFIX' => 'CONTACT_ACTIONS_GRID',
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

$this->IncludeComponentTemplate();

include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.contact/include/nav.php');

?>
