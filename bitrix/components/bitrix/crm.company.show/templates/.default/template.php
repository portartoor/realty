<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

global $APPLICATION;
$APPLICATION->AddHeadScript('/bitrix/js/crm/instant_editor.js');
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

$arResult['CRM_CUSTOM_PAGE_TITLE'] = GetMessage(
	'CRM_COMPANY_SHOW_TITLE',
	array(
		'#ID#' => $arResult['ELEMENT']['ID'],
		'#TITLE#' => $arResult['ELEMENT']['TITLE']
	)
);

$arTabs = array();
$arTabsExt = array();
$arTabs[] = array(
	'id' => 'tab_1',
	'name' => GetMessage('CRM_TAB_1'),
	'title' => GetMessage('CRM_TAB_1_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_1'],
	'display' => false
);
$arTabs[] = array(
	'id' => 'tab_details',
	'name' => GetMessage('CRM_TAB_DETAILS'),
	'title' => GetMessage('CRM_TAB_DETAILS_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_details'],
	'display' => false
);
if (!empty($arResult['FIELDS']['tab_activity']))
	$arTabs[] = array(
		'id' => 'tab_activity',
		'name' => GetMessage('CRM_TAB_6'),
		'title' => GetMessage('CRM_TAB_6_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_activity']
	);
if (!empty($arResult['FIELDS']['tab_deal'])):
	$arTabs[] = array(
		'id' => 'tab_deal',
		'name' => GetMessage('CRM_TAB_3'),
		'title' => GetMessage('CRM_TAB_3_TITLE'),
		'icon' => '',
		'fields'=> $arResult['FIELDS']['tab_deal']
	);
	$arTabsExt['tab_deal'] = array('SUFFIX' => " ({$arResult['DEAL_COUNT']})");
endif;

if (!empty($arResult['FIELDS']['tab_contact'])):
	$arTabs[] = array(
		'id' => 'tab_contact',
		'name' => GetMessage('CRM_TAB_2'),
		'title' => GetMessage('CRM_TAB_2_TITLE'),
		'icon' => '',
		'fields'=> $arResult['FIELDS']['tab_contact']
	);
	$arTabsExt['tab_contact'] = array('SUFFIX' => " ({$arResult['CONTACT_COUNT']})");
endif;
if (!empty($arResult['FIELDS']['tab_lead'])):
	$arTabs[] = array(
		'id' => 'tab_lead',
		'name' => GetMessage('CRM_TAB_LEAD'),
		'title' => GetMessage('CRM_TAB_LEAD_TITLE'),
		'icon' => '',
		'fields'=> $arResult['FIELDS']['tab_lead']
	);
	$arTabsExt['tab_lead'] = array('SUFFIX' => " ({$arResult['LEAD_COUNT']})");
endif;
if ($arResult['BIZPROC'])
	$arTabs[] = array(
		'id' => 'tab_bizproc',
		'name' => GetMessage('CRM_TAB_7'),
		'title' => GetMessage('CRM_TAB_7_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_bizproc']
	);

$arTabs[] = array(
	'id' => 'tab_event',
	'name' => GetMessage('CRM_TAB_HISTORY'),
	'title' => GetMessage('CRM_TAB_HISTORY_TITLE'),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_event']
);
$arTabsExt['tab_event'] = array('SUFFIX' => " ({$arResult['EVENT_COUNT']})");

CCrmGridOptions::SetTabNames($arResult['FORM_ID'], $arTabs);
// Form options housekeeping
$syncOptions = null;
if(COption::GetOptionString('crm', '~crm_11_0_6_convertion', 'N'))
{
	$syncOptions = 	array(
		'NORMALIZE_TABS' => array('tab_deal', 'tab_contact', 'tab_lead', 'tab_event')
	);
}

CCrmComponentHelper::SynchronizeFormSettings(
	$arResult['FORM_ID'],
	CCrmCompany::GetUserFieldEntityID(),
	$syncOptions
);

$enableInstantEdit = $arResult['ENABLE_INSTANT_EDIT'];
$instantEditorID = strtolower($arResult['FORM_ID']).'_editor';
$skipFields = array(
	'TITLE', 'COMPANY_TYPE',
	'INDUSTRY', 'REVENUE', 'EMPLOYEES',
	'CREATED_BY_ID', 'MODIFY_BY_ID',
	'DATE_CREATE', 'DATE_MODIFY',
	'PHONE', 'EMAIL', 'WEB',
	'COMMENTS', 'ASSIGNED_BY_ID', 'OPENED'
);
$element = isset($arResult['ELEMENT']) ? $arResult['ELEMENT'] : null;

if($element)
{
	$arEntityTypes = CCrmFieldMulti::GetEntityTypes();
	$multiFieldParams = array(
		'PHONE' => array(
			'DISPLAY_IF_EMPTY' => false,
			'TYPE'=> 'PHONE',
			'VALUE_TYPES' => isset($arEntityTypes['PHONE']) ? $arEntityTypes['PHONE'] : array(),
			'VALUES' => array(),
			'VALUE_COUNT' => 0
		),
		'EMAIL' => array(
			'DISPLAY_IF_EMPTY' => false,
			'TYPE'=> 'EMAIL',
			'VALUE_TYPES' => isset($arEntityTypes['EMAIL']) ? $arEntityTypes['EMAIL'] : array(),
			'VALUES' => array(),
			'VALUE_COUNT' => 0
		),
		'IM' => array(
			'DISPLAY_IF_EMPTY' => false,
			'TYPE'=> 'IM',
			'VALUE_TYPES' => isset($arEntityTypes['IM']) ? $arEntityTypes['IM'] : array(),
			'VALUES' => array(),
			'VALUE_COUNT' => 0
		),
		'WEB' => array(
			'DISPLAY_IF_EMPTY' => false,
			'TYPE'=> 'WEB',
			'VALUE_TYPES' => isset($arEntityTypes['WEB']) ? $arEntityTypes['WEB'] : array(),
			'VALUES' => array(),
			'VALUE_COUNT' => 0
		)
	);

	$multiFieldData = isset($element['FM']) ? $element['FM'] : null;
	if($multiFieldData)
	{
		foreach($multiFieldData as $typeID => &$multiFields)
		{
			foreach($multiFields as &$multiField)
			{
				$valueType = $multiField['VALUE_TYPE'];
				$value = $multiField['VALUE'];

				if(!isset($multiFieldParams[$typeID]['VALUES'][$valueType]))
				{
					$multiFieldParams[$typeID]['VALUES'][$valueType] = array();
				}
				$multiFieldParams[$typeID]['VALUES'][$valueType][] = $value;

				$multiFieldParams[$typeID]['VALUE_COUNT']++;
			}
			unset($multiField);
		}
		unset($multiFields);
	}

	$mainSection = array(
		'ID'=> 'main',
		'ITEMS' => array()
	);
	$tab1 = $arResult['FIELDS']['tab_1'];
	foreach($tab1 as &$field)
	{
		if($field['type'] === 'section') continue;
		$fieldID = isset($field['id']) ? $field['id'] : '';

		if(in_array($fieldID, $skipFields, true))
		{
			continue;
		}

		$value = isset($field['value']) ? $field['value'] : '';
		if($fieldID === 'WEB' || $fieldID === 'IM')
		{
			if($value === '')
			{
				continue;
			}

			$mainSection['ITEMS'][] = array(
				'ID' => $fieldID,
				'VALUE' => $value,
				'ENABLE_TITLE' => false
			);
		}
		else
		{
			$mainSection['ITEMS'][] = array(
				'ID' => $fieldID,
				'TITLE' => $field['name'],
				'VALUE' => $value
			);
		}
	}
	unset($field);

	$detailSection = array(
		'ID'=> 'details',
		'ITEMS' => array()
	);
	$tabDetails = $arResult['FIELDS']['tab_details'];
	foreach($tabDetails as &$field)
	{
		if($field['type'] === 'section') continue;
		$fieldID = isset($field['id']) ? $field['id'] : '';

		if(in_array($fieldID, $skipFields, true))
		{
			continue;
		}

		if($fieldID === 'WEB' || $fieldID === 'IM')
		{
			$detailSection['ITEMS'][] = array(
				'ID' => $fieldID,
				'VALUE' => $field['value'],
				'ENABLE_TITLE' => false
			);
		}
		else
		{
			$detailSection['ITEMS'][] = array(
				'ID' => $fieldID,
				'TITLE' => $field['name'],
				'VALUE' => $field['value']
			);
		}
	}
	unset($field);

	$summaryContainerID = strtolower($arResult['FORM_ID']).'_summary';
	$APPLICATION->IncludeComponent(
		'bitrix:crm.entity.summary',
		'',
		array(
			'ID' => $summaryContainerID,
			'TITLE' => array(
				'VALUE' => isset($element['~TITLE']) ? $element['~TITLE'] : '',
				'LOGO_ID' => isset($element['~LOGO']) ? $element['~LOGO'] : 0,
				'EDITABLE' => true
			),
			'LEGEND' => GetMessage('CRM_COMPANY_SHOW_LEGEND', array('#ID#' => $element['~ID'])),
			'LOCK_CONTROL_DATA' => array(
				'ENABLED' => true,
				'EDITABLE' => in_array('OPENED', $arResult['EDITABLE_FIELDS'], true),
				'FIELD_ID' => 'OPENED',
				'IS_LOCKED' => isset($element['~OPENED']) && $element['~OPENED'] !== 'Y',
				'LOCK_LEGEND' => GetMessage('CRM_COMPANY_NOT_OPENED'),
				'UNLOCK_LEGEND' => GetMessage('CRM_COMPANY_OPENED')
			),
			'EDITOR_ID' => $instantEditorID,
			'BLOCKS' => array(
				'header' => array(
					'ID' => 'header',
					'LAYOUT' => 'HORIZONTAL',
					'SECTIONS' => array(
						'left' => array(
							'ID' => 'left',
							'ITEMS' => array(
								array(
									'ID' => 'COMPANY_TYPE',
									'TYPE' => 'SELECT',
									'TITLE' => GetMessage('CRM_COMPANY_TYPE'),
									'PARAMS' => array(
										'FIELD_ID' => 'COMPANY_TYPE',
										'VALUE' => isset($element['~COMPANY_TYPE']) ? $element['~COMPANY_TYPE'] : '',
										'ITEMS' => $arResult['COMPANY_TYPE_LIST'],
										'ENCODE_ITEMS' => false,
										'SELECTOR_ID' => 'company_type',
										'UNDEFINED' => GetMessage('CRM_COMPANY_TYPE_UNDEF'),
										'EDITABLE' => in_array('COMPANY_TYPE', $arResult['EDITABLE_FIELDS'], true)
										//, 'CONTAINER_ID' => $summaryContainerID
									)
								),
								array(
									'ID' => 'COMPANY_INDUSTRY',
									'TYPE' => 'SELECT',
									'TITLE' => GetMessage('CRM_COMPANY_INDUSTRY'),
									'PARAMS' => array(
										'FIELD_ID' => 'INDUSTRY',
										'VALUE' => isset($element['~INDUSTRY']) ? $element['~INDUSTRY'] : '',
										'ITEMS' => $arResult['INDUSTRY_LIST'],
										'ENCODE_ITEMS' => false,
										'SELECTOR_ID' => 'company_industry',
										'UNDEFINED' => GetMessage('CRM_COMPANY_INDUSTRY_UNDEF'),
										'EDITABLE' => in_array('INDUSTRY', $arResult['EDITABLE_FIELDS'], true)
										//, 'CONTAINER_ID' => $summaryContainerID
									)
								),
								array(
									'ID' => 'COMPANY_REVENUE',
									'TYPE' => 'MONEY',
									'TITLE' => GetMessage('CRM_COMPANY_REVENUE'),
									'PARAMS' => array(
										'FIELD_ID' => 'REVENUE',
										'VALUE' => number_format(isset($element['~REVENUE']) ? floatval($element['~REVENUE']) : 0.0, 2, '.', ''),
										'EDITABLE' => in_array('REVENUE', $arResult['EDITABLE_FIELDS'], true),
										'CURRENCY_ID' => isset($element['~CURRENCY_ID']) ? $element['~CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID()
									)
								),
								array(
									'ID' => 'COMPANY_EMPLOYEES',
									'TYPE' => 'SELECT',
									'TITLE' => GetMessage('CRM_COMPANY_EMPLOYEES'),
									'PARAMS' => array(
										'FIELD_ID' => 'EMPLOYEES',
										'VALUE' => isset($element['~EMPLOYEES']) ? $element['~EMPLOYEES'] : '',
										'ITEMS' => $arResult['EMPLOYEES_LIST'],
										'ENCODE_ITEMS' => false,
										'SELECTOR_ID' => 'company_employees',
										'UNDEFINED' => GetMessage('CRM_COMPANY_EMPLOYEES_UNDEF'),
										'EDITABLE' => in_array('EMPLOYEES', $arResult['EDITABLE_FIELDS'], true)
										//, 'CONTAINER_ID' => $summaryContainerID
									)
								)
							)
						),
						'center' => array(
							'ID' => 'center',
							'ITEMS' => array(
								array(
									'ID' => 'COMPANY_PHONE',
									'TYPE' => 'MULTIFIELD',
									'TITLE' => GetMessage('CRM_COMPANY_PHONE'),
									'PARAMS' => array_merge($multiFieldParams['PHONE'], array('PREFIX' => 'crm_company_summury'))
								),
								array(
									'ID' => 'COMPANY_EMAIL',
									'TYPE' => 'MULTIFIELD',
									'TITLE' => GetMessage('CRM_COMPANY_EMAIL'),
									'PARAMS' => array_merge($multiFieldParams['EMAIL'], array('PREFIX' => 'crm_company_summury'))
								),
								array(
									'ID' => 'COMPANY_WEB',
									'TYPE' => 'MULTIFIELD',
									'TITLE' => GetMessage('CRM_COMPANY_WEB'),
									'PARAMS' => $multiFieldParams['WEB']
								)
							)
						),
						'right' => array(
							'ID' => 'right',
							'ITEMS' => array(
								array(
									'ID' => 'COMPANY_RESPONSIBLE',
									'TYPE' => 'RESPONSIBLE',
									'PARAMS' =>	array(
										'PREFIX' => 'crm_company_summary',
										'EDITABLE' => in_array('ASSIGNED_BY_ID', $arResult['EDITABLE_FIELDS'], true),
										'USER_ID' => isset($element['~ASSIGNED_BY_ID']) ? intval($element['~ASSIGNED_BY_ID']) : 0,
										'NAME' => isset($element['~ASSIGNED_BY_FORMATTED_NAME']) ? $element['~ASSIGNED_BY_FORMATTED_NAME'] : '',
										'PHOTO' => isset($element['~ASSIGNED_BY_PERSONAL_PHOTO']) ? intval($element['~ASSIGNED_BY_PERSONAL_PHOTO']) : 0,
										'WORK_POSITION' => isset($element['~ASSIGNED_BY_WORK_POSITION']) ? $element['~ASSIGNED_BY_WORK_POSITION'] : '',
										'FIELD_ID' => 'ASSIGNED_BY_ID',
										'SERVICE_URL' => '/bitrix/components/bitrix/crm.company.show/ajax.php?'.bitrix_sessid_get(),
										'USER_PROFILE_URL_TEMPLATE' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'])
									)
								),
								array(
									'ID' => 'COMPANY_DATE_MODIFY',
									'TITLE' => GetMessage('CRM_COMPANY_DATE_MODIFY'),
									'TYPE' => 'MODIFICATION_INFO',
									'PARAMS' => array(
										'DATE' => isset($element['~DATE_MODIFY']) ? $element['~DATE_MODIFY'] : '',
										'USER_NAME' => $element['~MODIFY_BY_FORMATTED_NAME'],
										'PATH_TO_USER' => $element['PATH_TO_USER_MODIFIER']
									)
								),
								array(
									'ID' => 'COMPANY_DATE_CREATE',
									'TITLE' => GetMessage('CRM_COMPANY_DATE_CREATE'),
									'TYPE' => 'MODIFICATION_INFO',
									'PARAMS' => array(
										'DATE' => isset($element['~DATE_CREATE']) ? $element['~DATE_CREATE'] : '',
										'USER_NAME' => $element['~CREATED_BY_FORMATTED_NAME'],
										'PATH_TO_USER' => $element['PATH_TO_USER_CREATOR']
									)
								)
							)
						)
					)
				),
				'comments' => array(
					'ID' => 'comments',
					'LAYOUT' => 'SINGLE',
					'ITEM' => array(
						'ID' => 'COMPANY_COMMENTS',
						'TITLE' => GetMessage('CRM_COMPANY_COMMENT'),
						'PARAMS' => array(
							'TYPE' => 'LHE',
							'FIELD_ID' => 'COMMENTS',
							'VALUE' => isset($element['~COMMENTS']) ? $element['~COMMENTS'] : '',
							'EDITOR_ID' => 'LeadCommentEditor',
							'EDITOR_JS_NAME' => 'oLHELeadComment',
							'WRAPPER_ID' => 'contact_comment_lhe_wrapper',
							'TOOLBAR_CONFIG' => array(
								'Bold', 'Italic', 'Underline', 'Strike',
								'CreateLink', 'DeleteLink'
							)
						)
					)
				),
				'footer' => array(
					'ID' => 'footer',
					'LAYOUT' => 'VERTICAL',
					'SECTIONS' => array(
						'main' => $mainSection,
						'details' => $detailSection
					)
				),
				'fold' => array(
					'ID' => 'fold',
					'LAYOUT' => 'HORIZONTAL',
					'IS_FOLD' => true,
					'AUTO_WIDTH' => true,
					'SECTIONS' => array(
						array(
							'ITEMS' => array(
								array(
									'ID' => 'COMPANY_TYPE_FOLDED',
									'TYPE' => 'SELECT',
									'TITLE' => GetMessage('CRM_COMPANY_TYPE'),
									'PARAMS' => array(
										'FIELD_ID' => 'COMPANY_TYPE',
										'VALUE' => isset($element['~COMPANY_TYPE']) ? $element['~COMPANY_TYPE'] : '',
										'ITEMS' => $arResult['COMPANY_TYPE_LIST'],
										'ENCODE_ITEMS' => false,
										'SELECTOR_ID' => 'company_type_folded',
										'UNDEFINED' => GetMessage('CRM_COMPANY_TYPE_UNDEF'),
										'EDITABLE' => in_array('COMPANY_TYPE', $arResult['EDITABLE_FIELDS'], true)
										//, 'CONTAINER_ID' => $summaryContainerID
									)
								)
							)
						),
						array(
							'ITEMS' => array(
								array(
									'ID' => 'COMPANY_PHONE_FOLDED',
									'TYPE' => 'MULTIFIELD',
									'TITLE' => GetMessage('CRM_COMPANY_PHONE'),
									'PARAMS' => array_merge($multiFieldParams['PHONE'], array('PREFIX' => 'crm_company_summury_folded'))
								)
							)
						),
						array(
							'ITEMS' => array(
								array(
									'ID' => 'COMPANY_EMAIL_FOLDED',
									'TYPE' => 'MULTIFIELD',
									'TITLE' => GetMessage('CRM_COMPANY_EMAIL'),
									'PARAMS' => array_merge($multiFieldParams['EMAIL'], array('PREFIX' => 'crm_company_summury_folded'))
								)
							)
						),
						array(
							'ITEMS' => array(
								array(
									'ID' => 'COMPANY_RESPONSIBLE_FOLDED',
									'TYPE' => 'RESPONSIBLE',
									'PARAMS' =>	array(
										'PREFIX' => 'crm_company_summary_folded',
										'EDITABLE' => false,
										'USER_ID' => isset($element['~ASSIGNED_BY_ID']) ? intval($element['~ASSIGNED_BY_ID']) : 0,
										'NAME' => isset($element['~ASSIGNED_BY_FORMATTED_NAME']) ? $element['~ASSIGNED_BY_FORMATTED_NAME'] : '',
										'PHOTO' => isset($element['~ASSIGNED_BY_PERSONAL_PHOTO']) ? intval($element['~ASSIGNED_BY_PERSONAL_PHOTO']) : 0,
										'WORK_POSITION' => isset($element['~ASSIGNED_BY_WORK_POSITION']) ? $element['~ASSIGNED_BY_WORK_POSITION'] : '',
										'FIELD_ID' => 'ASSIGNED_BY_ID',
										'SERVICE_URL' => '/bitrix/components/bitrix/crm.company.show/ajax.php?'.bitrix_sessid_get(),
										'USER_PROFILE_URL_TEMPLATE' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'])
									)
								)
							)
						)
					)
				)
			)
		),
		$component, array('HIDE_ICONS' => 'Y')
	);
}

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.form',
	'show',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'GRID_ID' => $arResult['GRID_ID'],
		'TABS' => $arTabs,
		'TABS_EXT' => $arTabsExt,
		'DATA' => $arResult['ELEMENT'],
		'SHOW_SETTINGS' => 'Y'
	),
	$component, array('HIDE_ICONS' => 'Y')
);
if($arResult['ENABLE_INSTANT_EDIT']):?>
<script type="text/javascript">
	BX.ready(
			function()
			{
				BX.CrmInstantEditorMessages =
								{
					editButtonTitle: '<?= CUtil::JSEscape(GetMessage('CRM_EDIT_BTN_TTL'))?>',
					lockButtonTitle: '<?= CUtil::JSEscape(GetMessage('CRM_LOCK_BTN_TTL'))?>'
				};

				var instantEditor = BX.CrmInstantEditor.create(
						'<?=CUtil::JSEscape($instantEditorID)?>',
						{
							containerID: ['<?=CUtil::JSEscape($summaryContainerID)?>'],
							ownerType: 'CO',
							ownerID: <?=$arResult['ELEMENT_ID']?>,
							url: '/bitrix/components/bitrix/crm.company.show/ajax.php?<?=bitrix_sessid_get()?>',
							callToFormat: <?=CCrmCallToUrl::GetFormat(CCrmCallToUrl::Slashless)?>
						}
				);
			}
	);
</script>
<?endif;?>
