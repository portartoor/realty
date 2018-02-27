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
	'CRM_LEAD_SHOW_TITLE',
	array(
		'#ID#' => $arResult['ELEMENT']['ID'],
		'#TITLE#' => $arResult['ELEMENT']['TITLE']
	)
);

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
{
	$arTabs[] = array(
		'id' => 'tab_activity',
		'name' => GetMessage('CRM_TAB_6'),
		'title' => GetMessage('CRM_TAB_6_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_activity']
	);
}
$arTabs[] = array(
	'id' => 'tab_product_rows',
	'name' => GetMessage('CRM_TAB_PRODUCT_ROWS'),
	'title' => GetMessage('CRM_TAB_PRODUCT_ROWS_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_product_rows']
);
if ($arResult['ELEMENT']['STATUS_ID'] == 'CONVERTED'):
	if (!empty($arResult['FIELDS']['tab_contact']))
		$arTabs[] = array(
			'id' => 'tab_contact',
			'name' => GetMessage('CRM_TAB_2')." ($arResult[CONTACT_COUNT])",
			'title' => GetMessage('CRM_TAB_2_TITLE'),
			'icon' => '',
			'fields'=> $arResult['FIELDS']['tab_contact']
		);
	if (!empty($arResult['FIELDS']['tab_company']))
		$arTabs[] = array(
			'id' => 'tab_company',
			'name' => GetMessage('CRM_TAB_3')." ($arResult[COMPANY_COUNT])",
			'title' => GetMessage('CRM_TAB_3_TITLE'),
			'icon' => '',
			'fields'=> $arResult['FIELDS']['tab_company']
		);
	if (!empty($arResult['FIELDS']['tab_deal']))
		$arTabs[] = array(
			'id' => 'tab_deal',
			'name' => GetMessage('CRM_TAB_4')." ($arResult[DEAL_COUNT])",
			'title' => GetMessage('CRM_TAB_4_TITLE'),
			'icon' => '',
			'fields'=> $arResult['FIELDS']['tab_deal']
		);
endif;
if ($arResult['BIZPROC'])
{
	$arTabs[] = array(
		'id' => 'tab_bizproc',
		'name' => GetMessage('CRM_TAB_7'),
		'title' => GetMessage('CRM_TAB_7_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_bizproc']
	);
}
$arTabs[] = array(
	'id' => 'tab_event',
	'name' => GetMessage('CRM_TAB_HISTORY')." ($arResult[EVENT_COUNT])",
	'title' => GetMessage('CRM_TAB_HISTORY_TITLE'),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_event']
);
CCrmGridOptions::SetTabNames($arResult['FORM_ID'], $arTabs);
// Form options housekeeping
CCrmComponentHelper::SynchronizeFormSettings($arResult['FORM_ID'], CCrmLead::GetUserFieldEntityID());
$enableInstantEdit = $arResult['ENABLE_INSTANT_EDIT'];
$instantEditorID = strtolower($arResult['FORM_ID']).'_editor';
$skipFields = array(
	'TITLE', 'STATUS_ID', 'ASSIGNED_BY_ID',
	'POST', 'COMPANY_TITLE', 'COMMENTS',
	'FULL_NAME',
	'EMAIL', 'PHONE', 'IM', 'WEB',
	'OPPORTUNITY', 'CURRENCY_ID', 'SOURCE_ID',
	'DATE_MODIFY', 'DATE_CREATE',
	'CREATED_BY_ID', 'MODIFY_BY_ID', 'OPENED'
);
$element = isset($arResult['ELEMENT']) ? $arResult['ELEMENT'] : null;
if($element)
{
	$title = isset($element['~TITLE']) ? $element['~TITLE'] : '';
	$fullName = isset($element['~FORMATTED_NAME']) ? $element['~FORMATTED_NAME'] : '';
	$post = isset($element['~POST']) ? $element['~POST'] : '';
	$companyTitle = isset($element['~COMPANY_TITLE']) ? $element['~COMPANY_TITLE'] : '';

	$descr = '';
	if($post !== '' && $companyTitle !== '')
	{
		$descr = GetMessage(
			'CRM_LEAD_POST_COMPANY',
			array('#POST#' => $post, '#COMPANY#' => $companyTitle)
		);
	}
	elseif($post !== '')
	{
		$descr = $post;
	}
	elseif($companyTitle !== '')
	{
		$descr = $companyTitle;
	}

	$infoParams = array(
	);
	if($fullName !== '')
	{
		$infoParams['NAME'] = $fullName;
		$infoParams['DESCRIPTION'] = $descr;
	}
	else
	{
		$infoParams['NAME'] = GetMessage('CRM_LEAD_CLIENT_NOT_ASSIGNED');
	}

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

		if(strpos($fieldID, 'FM.IM.') === 0)
		{
			//Skip IM fields
			$skipFields[] = $fieldID;
			continue;
		}

		$mainSection['ITEMS'][] = array(
			'ID' => $fieldID,
			'TITLE' => $field['name'],
			'VALUE' => $field['value'],
			'DISPLAY_IF_EMPTY' => $fieldID === 'COMMENTS'
		);
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

		if(strpos($fieldID, 'FM.IM.') === 0)
		{
			//Skip IM fields
			$skipFields[] = $fieldID;
			continue;
		}

		$detailSection['ITEMS'][] = array(
			'ID' => $fieldID,
			'TITLE' => $field['name'],
			'VALUE' => $field['value']
		);
	}
	unset($field);

	$summaryContainerID = strtolower($arResult['FORM_ID']).'_summary';
	$APPLICATION->IncludeComponent(
		'bitrix:crm.entity.summary',
		'',
		array(
			'ID' => $summaryContainerID,
			'TITLE' => array(
				'VALUE' => $element['~TITLE'],
				'EDITABLE' => in_array('TITLE', $arResult['EDITABLE_FIELDS'], true),
				'FIELD_ID' => 'TITLE'
			),
			'LEGEND' => GetMessage('CRM_LEAD_SHOW_LEGEND', array('#ID#' => $element['~ID'])),
			'LOCK_CONTROL_DATA' => array(
				'ENABLED' => true,
				'EDITABLE' => in_array('OPENED', $arResult['EDITABLE_FIELDS'], true),
				'FIELD_ID' => 'OPENED',
				'IS_LOCKED' => isset($element['~OPENED']) && $element['~OPENED'] !== 'Y',
				'LOCK_LEGEND' => GetMessage('CRM_LEAD_NOT_OPENED'),
				'UNLOCK_LEGEND' => GetMessage('CRM_LEAD_OPENED')
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
									'ID' => 'LEAD_STATUS',
									'TYPE' => 'PROGRESS',
									'TITLE' => GetMessage('CRM_LEAD_SIDEBAR_STATUS'),
									'PARAMS' => array(
										'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Lead),
										'REGISTER_SETTINGS' => true,
										'PREFIX' => "{$arResult['FORM_ID']}_PROGRESS_BAR_",
										'ENTITY_ID' => $element['~ID'],
										'CURRENT_ID' => $element['~STATUS_ID'],
										'SERVICE_URL' => '/bitrix/components/bitrix/crm.lead.list/list.ajax.php'
									)
								),
								array(
									'ID' => 'OPPORTUNITY',
									'TYPE' => 'MONEY',
									'TITLE' => GetMessage('CRM_LEAD_OPPORTUNITY_SHORT'),
									'PARAMS' => array(
										'FIELD_ID' => 'OPPORTUNITY',
										'VALUE' => number_format(isset($element['~OPPORTUNITY']) ? floatval($element['~OPPORTUNITY']) : 0.0, 2, '.', ''),
										'EDITABLE' => in_array('OPPORTUNITY', $arResult['EDITABLE_FIELDS'], true),
										'CURRENCY_ID' => isset($element['~CURRENCY_ID']) ? $element['~CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID()
									)
								),
								array(
									'ID' => '',
									'TYPE' => 'SELECT',
									'TITLE' => GetMessage('CRM_LEAD_SOURCE'),
									'PARAMS' => array(
										'FIELD_ID' => 'SOURCE_ID',
										'VALUE' => isset($element['~SOURCE_ID']) ? $element['~SOURCE_ID'] : '',
										'ITEMS' => $arResult['SOURCE_LIST'],
										'ENCODE_ITEMS' => false,
										'SELECTOR_ID' => 'lead_source',
										'UNDEFINED' => GetMessage('CRM_LEAD_SOURCE_UNDEF'),
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
									'ID' => 'LEAD_INFO',
									'TYPE' => 'CLIENT_INFO',
									'TITLE' => GetMessage('CRM_LEAD_CLIENT'),
									'PARAMS' => $infoParams
								),
								array(
									'ID' => 'LEAD_PHONE',
									'TYPE' => 'MULTIFIELD',
									'TITLE' => GetMessage('CRM_LEAD_PHONE'),
									'PARAMS' => $multiFieldParams['PHONE']
								),
								array(
									'ID' => 'LEAD_EMAIL',
									'TYPE' => 'MULTIFIELD',
									'TITLE' => GetMessage('CRM_LEAD_EMAIL'),
									'PARAMS' => $multiFieldParams['EMAIL']
								),
								array(
									'ID' => 'LEAD_IM',
									'TYPE' => 'MULTIFIELD',
									'TITLE' => GetMessage('CRM_LEAD_IM'),
									'PARAMS' => $multiFieldParams['IM']
								)
							)
						),
						'right' => array(
							'ID' => 'right',
							'ITEMS' => array(
								array(
									'ID' => 'LEAD_RESPONSIBLE',
									'TYPE' => 'RESPONSIBLE',
									'PARAMS' =>	array(
										'PREFIX' => 'crm_lead_summary',
										'EDITABLE' => in_array('ASSIGNED_BY_ID', $arResult['EDITABLE_FIELDS'], true),
										'USER_ID' => isset($element['~ASSIGNED_BY_ID']) ? intval($element['~ASSIGNED_BY_ID']) : 0,
										'NAME' => isset($element['~ASSIGNED_BY_FORMATTED_NAME']) ? $element['~ASSIGNED_BY_FORMATTED_NAME'] : '',
										'PHOTO' => isset($element['~ASSIGNED_BY_PERSONAL_PHOTO']) ? intval($element['~ASSIGNED_BY_PERSONAL_PHOTO']) : 0,
										'WORK_POSITION' => isset($element['~ASSIGNED_BY_WORK_POSITION']) ? $element['~ASSIGNED_BY_WORK_POSITION'] : '',
										'FIELD_ID' => 'ASSIGNED_BY_ID',
										'SERVICE_URL' => '/bitrix/components/bitrix/crm.lead.show/ajax.php?'.bitrix_sessid_get(),
										'USER_PROFILE_URL_TEMPLATE' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'])
									)
								),
								array(
									'ID' => 'LEAD_DATE_MODIFY',
									'TITLE' => GetMessage('CRM_LEAD_DATE_MODIFY'),
									'TYPE' => 'MODIFICATION_INFO',
									'PARAMS' => array(
										'DATE' => isset($element['~DATE_MODIFY']) ? $element['~DATE_MODIFY'] : '',
										'USER_NAME' => $element['~MODIFY_BY_FORMATTED_NAME'],
										'PATH_TO_USER' => $element['PATH_TO_USER_MODIFIER']
									)
								),
								array(
									'ID' => 'LEAD_DATE_CREATE',
									'TITLE' => GetMessage('CRM_LEAD_DATE_CREATE'),
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
						'ID' => 'LEAD_COMMENTS',
						'TITLE' => GetMessage('CRM_LEAD_COMMENT'),
						'PARAMS' => array(
							'TYPE' => 'LHE',
							'FIELD_ID' => 'COMMENTS',
							'VALUE' => isset($element['~COMMENTS']) ? $element['~COMMENTS'] : '',
							'EDITOR_ID' => 'LeadCommentEditor',
							'EDITOR_JS_NAME' => 'oLHELeadComment',
							'WRAPPER_ID' => 'lead_comment_lhe_wrapper',
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
					'SECTIONS' => array(
						array(
							'ITEMS' => array(
								array(
									'ID' => 'LEAD_STATUS_FOLDED',
									'TYPE' => 'PROGRESS',
									'PARAMS' => array(
										'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Lead),
										'REGISTER_SETTINGS' => false,
										'PREFIX' => "{$arResult['FORM_ID']}_PROGRESS_BAR_FOLDED",
										'ENTITY_ID' => $element['~ID'],
										'CURRENT_ID' => $element['~STATUS_ID'],
										'SERVICE_URL' => '/bitrix/components/bitrix/crm.lead.list/list.ajax.php'
									)
								)
							)
						),
						array(
							'ITEMS' => array(
								array(
									'ID' => 'OPPORTUNITY_FOLDED',
									'TYPE' => 'MONEY',
									'TITLE' => GetMessage('CRM_LEAD_OPPORTUNITY_SHORT'),
									'PARAMS' => array(
										'FIELD_ID' => 'OPPORTUNITY',
										'VALUE' => number_format(isset($element['~OPPORTUNITY']) ? floatval($element['~OPPORTUNITY']) : 0.0, 2, '.', ''),
										'EDITABLE' => in_array('OPPORTUNITY', $arResult['EDITABLE_FIELDS'], true),
										'CURRENCY_ID' => isset($element['~CURRENCY_ID']) ? $element['~CURRENCY_ID'] : CCrmCurrency::GetBaseCurrencyID()
									)
								)
							)
						),
						array(
							'ITEMS' => array(
								array(
									'ID' => 'LEAD_INFO_FOLDED',
									'TYPE' => 'CLIENT_INFO',
									'TITLE' => GetMessage('CRM_LEAD_CLIENT'),
									'PARAMS' => $infoParams
								),
							)
						),
						array(
							'ITEMS' => array(
								array(
									'ID' => 'LEAD_RESPONSIBLE_FOLDED',
									'TYPE' => 'RESPONSIBLE',
									'PARAMS' =>	array(
										'PREFIX' => 'crm_lead_summary_fold',
										'EDITABLE' => false,
										'USER_ID' => isset($element['~ASSIGNED_BY_ID']) ? intval($element['~ASSIGNED_BY_ID']) : 0,
										'NAME' => isset($element['~ASSIGNED_BY_FORMATTED_NAME']) ? $element['~ASSIGNED_BY_FORMATTED_NAME'] : '',
										'PHOTO' => isset($element['~ASSIGNED_BY_PERSONAL_PHOTO']) ? intval($element['~ASSIGNED_BY_PERSONAL_PHOTO']) : 0,
										'WORK_POSITION' => isset($element['~ASSIGNED_BY_WORK_POSITION']) ? $element['~ASSIGNED_BY_WORK_POSITION'] : '',
										'FIELD_ID' => 'ASSIGNED_BY_ID',
										'SERVICE_URL' => '/bitrix/components/bitrix/crm.lead.show/ajax.php?'.bitrix_sessid_get(),
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
?>
<?if($arResult['ENABLE_INSTANT_EDIT']):?>
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
					ownerType: 'L',
					ownerID: <?=$arResult['ELEMENT_ID']?>,
					url: '/bitrix/components/bitrix/crm.lead.show/ajax.php?<?= bitrix_sessid_get()?>',
					callToFormat: <?=CCrmCallToUrl::GetFormat(CCrmCallToUrl::Slashless)?>
				}
			);

			var prodEditor = BX.CrmProductEditor.getDefault();

			function handleProductRowChange()
			{
				if(prodEditor)
				{
					instantEditor.setFieldReadOnly('OPPORTUNITY', prodEditor.getProductCount() > 0);
				}
			}

			if(prodEditor)
			{
				BX.addCustomEvent(
					prodEditor,
					'sumTotalChange',
					function(ttl)
					{
						instantEditor.setFieldValue('OPPORTUNITY', ttl);
					}
				);

				handleProductRowChange();

				BX.addCustomEvent(
					prodEditor,
					'productAdd',
					handleProductRowChange
				);

				BX.addCustomEvent(
					prodEditor,
					'productRemove',
					handleProductRowChange
				);
			}
		}
	);
</script>
<?endif;?>
