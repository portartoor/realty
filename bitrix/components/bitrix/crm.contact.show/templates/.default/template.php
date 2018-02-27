<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

global $APPLICATION;
$APPLICATION->AddHeadScript('/bitrix/js/crm/instant_editor.js');
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

$arTabs = array();
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
if (!empty($arResult['FIELDS']['tab_deal']))
	$arTabs[] = array(
		'id' => 'tab_deal',
		'name' => GetMessage('CRM_TAB_4')." ($arResult[DEAL_COUNT])",
		'title' => GetMessage('CRM_TAB_4_TITLE'),
		'icon' => '',
		'fields'=> $arResult['FIELDS']['tab_deal']
	);
if (!empty($arResult['FIELDS']['tab_company']))
	$arTabs[] = array(
		'id' => 'tab_company',
		'name' => GetMessage('CRM_TAB_3')." ($arResult[COMPANY_COUNT])",
		'title' => GetMessage('CRM_TAB_3_TITLE'),
		'icon' => '',
		'fields'=> $arResult['FIELDS']['tab_company']
	);
if (!empty($arResult['FIELDS']['tab_lead']))
	$arTabs[] = array(
		'id' => 'tab_lead',
		'name' => GetMessage('CRM_TAB_2')." ($arResult[LEAD_COUNT])",
		'title' => GetMessage('CRM_TAB_2_TITLE'),
		'icon' => '',
		'fields'=> $arResult['FIELDS']['tab_lead']
	);
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
	'name' => GetMessage('CRM_TAB_HISTORY')." ($arResult[EVENT_COUNT])",
	'title' => GetMessage('CRM_TAB_HISTORY_TITLE'),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_event']
);
CCrmGridOptions::SetTabNames($arResult['FORM_ID'], $arTabs);
// Form options housekeeping
CCrmComponentHelper::SynchronizeFormSettings($arResult['FORM_ID'], CCrmContact::GetUserFieldEntityID());
$enableInstantEdit = $arResult['ENABLE_INSTANT_EDIT'];
$instantEditorID = strtolower($arResult['FORM_ID']).'_editor';
$skipFields = array(
	'FULL_NAME', 'POST', 'PHOTO',
	'COMPANY_TITLE', 'ASSIGNED_BY_ID', 'COMMENTS',
	'PHONE', 'EMAIL', 'IM',
	'CREATED_BY_ID', 'MODIFY_BY_ID',
	'DATE_CREATE', 'DATE_MODIFY',
	'TYPE_ID', 'SOURCE_ID', 'OPENED'
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


		if($fieldID === 'WEB')
		{
			$mainSection['ITEMS'][] = array(
				'ID' => $fieldID,
				'VALUE' => $field['value'],
				'ENABLE_TITLE' => false
			);
		}
		else
		{
			$mainSection['ITEMS'][] = array(
				'ID' => $fieldID,
				'TITLE' => $field['name'],
				'VALUE' => $field['value']
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

		if($fieldID === 'WEB')
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
				'FIELD_ID' => 'FULL_NAME',
				'VALUE' => isset($element['~FORMATTED_NAME']) ? $element['~FORMATTED_NAME'] : '',
				'LOGO_ID' => isset($element['~PHOTO']) ? $element['~PHOTO'] : 0,
				'EDITABLE' => $enableInstantEdit
			),
			'LEGEND' => GetMessage('CRM_CONTACT_SHOW_LEGEND', array('#ID#' => $element['~ID'])),
			'LOCK_CONTROL_DATA' => array(
				'ENABLED' => true,
				'EDITABLE' => in_array('OPENED', $arResult['EDITABLE_FIELDS'], true),
				'FIELD_ID' => 'OPENED',
				'IS_LOCKED' => isset($element['~OPENED']) && $element['~OPENED'] !== 'Y',
				'LOCK_LEGEND' => GetMessage('CRM_CONTACT_NOT_OPENED'),
				'UNLOCK_LEGEND' => GetMessage('CRM_CONTACT_OPENED')
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
									'ID' => 'CONTACT_COMPANY',
									'TYPE' => 'CLIENT_BALLOON',
									'PARAMS' => isset($arResult['COMPANY_FIELD']) ? $arResult['COMPANY_FIELD'] : array()
								),
								array(
									'ID' => 'CONTACT_POST',
									'TYPE' => 'TEXT',
									'TITLE' => GetMessage('CRM_CONTACT_POST'),
									'PARAMS' => array(
										'FIELD_ID' => 'POST',
										'VALUE' => isset($element['~POST']) ? $element['~POST'] : '',
										'EDITABLE' => in_array('POST', $arResult['EDITABLE_FIELDS'], true),
										'WIDTH' => 185
									)
								),
								array(
									'ID' => 'CONTACT_TYPE',
									'TYPE' => 'SELECT',
									'TITLE' => GetMessage('CRM_CONTACT_TYPE'),
									'PARAMS' => array(
										'FIELD_ID' => 'TYPE_ID',
										'VALUE' => isset($element['~TYPE_ID']) ? $element['~TYPE_ID'] : '',
										'ITEMS' => $arResult['TYPE_LIST'],
										'ENCODE_ITEMS' => false,
										'SELECTOR_ID' => 'contact_type',
										'UNDEFINED' => GetMessage('CRM_CONTACT_TYPE_UNDEF'),
										'EDITABLE' => in_array('TYPE_ID', $arResult['EDITABLE_FIELDS'], true)
										//, 'CONTAINER_ID' => $summaryContainerID
									)
								),
								array(
									'ID' => 'CONTACT_SOURCE',
									'TYPE' => 'SELECT',
									'TITLE' => GetMessage('CRM_CONTACT_SOURCE'),
									'PARAMS' => array(
										'FIELD_ID' => 'SOURCE_ID',
										'VALUE' => isset($element['~SOURCE_ID']) ? $element['~SOURCE_ID'] : '',
										'ITEMS' => $arResult['SOURCE_LIST'],
										'ENCODE_ITEMS' => false,
										'SELECTOR_ID' => 'contact_source',
										'UNDEFINED' => GetMessage('CRM_CONTACT_SOURCE_UNDEF'),
										'EDITABLE' => in_array('SOURCE_ID', $arResult['EDITABLE_FIELDS'], true)
										//, 'CONTAINER_ID' => $summaryContainerID
									)
								)
							)
						),
						'center' => array(
							'ID' => 'center',
							'ITEMS' => array(
								array(
									'ID' => 'CONTACT_PHONE',
									'TYPE' => 'MULTIFIELD',
									'TITLE' => GetMessage('CRM_CONTACT_PHONE'),
									'PARAMS' => array_merge($multiFieldParams['PHONE'], array('PREFIX' => 'crm_contact_summury'))
								),
								array(
									'ID' => 'CONTACT_EMAIL',
									'TYPE' => 'MULTIFIELD',
									'TITLE' => GetMessage('CRM_CONTACT_EMAIL'),
									'PARAMS' => array_merge($multiFieldParams['EMAIL'], array('PREFIX' => 'crm_contact_summury'))
								),
								array(
									'ID' => 'CONTACT_IM',
									'TYPE' => 'MULTIFIELD',
									'TITLE' => GetMessage('CRM_CONTACT_IM'),
									'PARAMS' => $multiFieldParams['IM']
								)
							)
						),
						'right' => array(
							'ID' => 'right',
							'ITEMS' => array(
								array(
									'ID' => 'CONTACT_RESPONSIBLE',
									'TYPE' => 'RESPONSIBLE',
									'PARAMS' =>	array(
										'PREFIX' => 'crm_contact_summary',
										'EDITABLE' => in_array('ASSIGNED_BY_ID', $arResult['EDITABLE_FIELDS'], true),
										'USER_ID' => isset($element['~ASSIGNED_BY_ID']) ? intval($element['~ASSIGNED_BY_ID']) : 0,
										'NAME' => isset($element['~ASSIGNED_BY_FORMATTED_NAME']) ? $element['~ASSIGNED_BY_FORMATTED_NAME'] : '',
										'PHOTO' => isset($element['~ASSIGNED_BY_PERSONAL_PHOTO']) ? intval($element['~ASSIGNED_BY_PERSONAL_PHOTO']) : 0,
										'WORK_POSITION' => isset($element['~ASSIGNED_BY_WORK_POSITION']) ? $element['~ASSIGNED_BY_WORK_POSITION'] : '',
										'FIELD_ID' => 'ASSIGNED_BY_ID',
										'SERVICE_URL' => '/bitrix/components/bitrix/crm.contact.show/ajax.php?'.bitrix_sessid_get(),
										'USER_PROFILE_URL_TEMPLATE' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'])
									)
								),
								array(
									'ID' => 'CONTACT_DATE_MODIFY',
									'TITLE' => GetMessage('CRM_CONTACT_DATE_MODIFY'),
									'TYPE' => 'MODIFICATION_INFO',
									'PARAMS' => array(
										'DATE' => isset($element['~DATE_MODIFY']) ? $element['~DATE_MODIFY'] : '',
										'USER_NAME' => $element['~MODIFY_BY_FORMATTED_NAME'],
										'PATH_TO_USER' => $element['PATH_TO_USER_MODIFIER']
									)
								),
								array(
									'ID' => 'CONTACT_DATE_CREATE',
									'TITLE' => GetMessage('CRM_CONTACT_DATE_CREATE'),
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
						'ID' => 'CONCACT_COMMENTS',
						'TITLE' => GetMessage('CRM_CONTACT_COMMENT'),
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
									'ID' => 'CONTACT_COMPANY_FOLDED',
									'TYPE' => 'CLIENT_BALLOON',
									'TITLE' => GetMessage('CRM_CONTACT_COMPANY'),
									'PARAMS' => array(
										'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
										'ENTITY_ID' => isset($element['~COMPANY_ID']) ? $element['~COMPANY_ID'] : 0,
										'TITLE' => isset($element['~COMPANY_TITLE']) ? $element['~COMPANY_TITLE'] : '',
										'PREFIX' => 'crm_contact_summary_fold'
									)
								)
							)
						),
						array(
							'ITEMS' => array(
								array(
									'ID' => 'CONTACT_PHONE_FOLDED',
									'TYPE' => 'MULTIFIELD',
									'TITLE' => GetMessage('CRM_CONTACT_PHONE'),
									'PARAMS' => array_merge($multiFieldParams['PHONE'], array('PREFIX' => 'crm_contact_summury_fold'))
								)
							)
						),
						array(
							'ITEMS' => array(
								array(
									'ID' => 'CONTACT_EMAIL_FOLDED',
									'TYPE' => 'MULTIFIELD',
									'TITLE' => GetMessage('CRM_CONTACT_EMAIL'),
									'PARAMS' => array_merge($multiFieldParams['EMAIL'], array('PREFIX' => 'crm_contact_summury_fold'))
								)
							)
						),
						array(
							'ITEMS' => array(
								array(
									'ID' => 'CONTACT_RESPONSIBLE_FOLDED',
									'TYPE' => 'RESPONSIBLE',
									'PARAMS' =>	array(
										'PREFIX' => 'crm_contact_summary_fold',
										'EDITABLE' => false,
										'USER_ID' => isset($element['~ASSIGNED_BY_ID']) ? intval($element['~ASSIGNED_BY_ID']) : 0,
										'NAME' => isset($element['~ASSIGNED_BY_FORMATTED_NAME']) ? $element['~ASSIGNED_BY_FORMATTED_NAME'] : '',
										'PHOTO' => isset($element['~ASSIGNED_BY_PERSONAL_PHOTO']) ? intval($element['~ASSIGNED_BY_PERSONAL_PHOTO']) : 0,
										'WORK_POSITION' => isset($element['~ASSIGNED_BY_WORK_POSITION']) ? $element['~ASSIGNED_BY_WORK_POSITION'] : '',
										'FIELD_ID' => 'ASSIGNED_BY_ID',
										'SERVICE_URL' => '/bitrix/components/bitrix/crm.contact.show/ajax.php?'.bitrix_sessid_get(),
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
		'DATA' => $arResult['ELEMENT'],
		'SHOW_SETTINGS' => 'Y'
	),
	$component, array('HIDE_ICONS' => 'Y')
);

if($element && $arResult['ENABLE_INSTANT_EDIT']):
?><script type="text/javascript">
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
							ownerType: 'C',
							ownerID: <?=$arResult['ELEMENT_ID']?>,
							url: '/bitrix/components/bitrix/crm.contact.show/ajax.php?<?=bitrix_sessid_get()?>',
							callToFormat: <?=CCrmCallToUrl::GetFormat(CCrmCallToUrl::Slashless)?>
						}
				);

				var fullNameField = instantEditor.getField('FULL_NAME');
				if(fullNameField)
				{
					var fullNameEditor = BX.CrmContactEditor.create(
							'fullNameEditor',
							{
								'serviceUrl': '<?='/bitrix/components/bitrix/crm.contact.edit/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get()?>',
								'actionName': 'SAVE_CONTACT',
								'nameTemplate': '<?=CUtil::JSEscape($arParams['NAME_TEMPLATE'])?>',
								'data':
								{
									'id': <?=$arResult['ELEMENT_ID']?>,
									'name': '<?=isset($element['~NAME']) ? CUtil::JSEscape($element['~NAME']) : ''?>',
									'secondName': '<?=isset($element['~SECOND_NAME']) ? CUtil::JSEscape($element['~SECOND_NAME']) : ''?>',
									'lastName': '<?=isset($element['~LAST_NAME']) ? CUtil::JSEscape($element['~LAST_NAME']) : ''?>'
								},
								'dialog': <?=CUtil::PhpToJSObject(
									array(
										'addButtonName' => GetMessageJS('CRM_CONTACT_EDIT_DLG_BTN_SAVE'),
										'cancelButtonName' => GetMessageJS('CRM_CONTACT_EDIT_DLG_BTN_CANCEL'),
										'title' => GetMessageJS('CRM_CONTACT_EDIT_DLG_TITLE'),
										'lastNameTitle' => GetMessageJS('CRM_CONTACT_EDIT_DLG_FIELD_LAST_NAME'),
										'nameTitle' => GetMessageJS('CRM_CONTACT_EDIT_DLG_FIELD_NAME'),
										'secondNameTitle' => GetMessageJS('CRM_CONTACT_EDIT_DLG_FIELD_SECOND_NAME'),
										'enableEmail' => false,
										'enablePhone' => false
									)
								)?>
							}
					);

					fullNameField.setExternalEditor(fullNameEditor);
				}
			}
	);
</script>
<?endif;?>
