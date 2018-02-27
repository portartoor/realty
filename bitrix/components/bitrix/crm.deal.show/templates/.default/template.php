<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!empty($arResult['ERROR_MESSAGE']))
{
	ShowError($arResult['ERROR_MESSAGE']);
}

global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}
$arResult['CRM_CUSTOM_PAGE_TITLE'] = GetMessage(
	'CRM_DEAL_SHOW_TITLE',
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
	'fields' => $arResult['FIELDS']['tab_1'],
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
if (!empty($arResult['FIELDS']['tab_contact']))
{
	$contactCount = intval($arResult[CONTACT_COUNT]);
	$arTabs[] = array(
		'id' => 'tab_contact',
		'name' => GetMessage('CRM_TAB_2')." ($contactCount)",
		'title' => GetMessage('CRM_TAB_2_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_contact']
	);
}
if (!empty($arResult['FIELDS']['tab_company']))
{
	$companyCount = intval($arResult[COMPANY_COUNT]);
	$arTabs[] = array(
		'id' => 'tab_company',
		'name' => GetMessage('CRM_TAB_3')." ($companyCount)",
		'title' => GetMessage('CRM_TAB_3_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_company']
	);
}
if (!empty($arResult['FIELDS']['tab_lead']))
{
	$leadCount = intval($arResult[LEAD_COUNT]);
	$arTabs[] = array(
		'id' => 'tab_lead',
		'name' => GetMessage('CRM_TAB_4')." ($leadCount)",
		'title' => GetMessage('CRM_TAB_4_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_lead']
	);
}
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
if(!empty($arResult['FIELDS']['tab_event']))
{
	$eventCount = intval($arResult[EVENT_COUNT]);
	$arTabs[] = array(
		'id' => 'tab_event',
		'name' => GetMessage('CRM_TAB_HISTORY')." ($eventCount)",
		'title' => GetMessage('CRM_TAB_HISTORY_TITLE'),
		'icon' => '',
		'fields' => $arResult['FIELDS']['tab_event']
	);
}

CCrmGridOptions::SetTabNames($arResult['FORM_ID'], $arTabs);
// Form options housekeeping
CCrmComponentHelper::SynchronizeFormSettings($arResult['FORM_ID'], CCrmDeal::GetUserFieldEntityID());

$enableInstantEdit = $arResult['ENABLE_INSTANT_EDIT'];
$instantEditorID = strtolower($arResult['FORM_ID']).'_editor';
$skipFields = array(
	'ID', 'TITLE', 'STAGE_ID', 'OPPORTUNITY', 'TYPE_ID', 'ASSIGNED_BY_ID',
	'PROBABILITY', 'BEGINDATE', 'CLOSEDATE', 'CURRENCY_ID',
	'CREATED_BY_ID', 'DATE_CREATE', 'MODIFY_BY_ID', 'DATE_MODIFY',
	'CONTACT_TITLE', 'CONTACT_POST',
	'CONTACT_PHONE', 'CONTACT_EMAIL', 'CONTACT_IM',
	'CONTACT_ADDRESS', 'CONTACT_TYPE', 'CONTACT_SOURCE',
	'COMPANY_TITLE', 'COMPANY_INDUSTRY', 'COMPANY_PHONE',
	'COMPANY_EMAIL', 'COMPANY_EMPLOYEES', 'COMPANY_REVENUE',
	'COMPANY_TYPE', 'COMPANY_WEB', 'COMPANY_ADDRESS_LEGAL',
	'COMPANY_ADDRESS', 'COMPANY_BANKING_DETAILS',
	'COMMENTS', 'OPENED'
);
$element = isset($arResult['ELEMENT']) ? $arResult['ELEMENT'] : null;
if($element)
{
	$contactID = isset($element['~CONTACT_ID']) ? intval($element['~CONTACT_ID']) : 0;
	$companyID = isset($element['~COMPANY_ID']) ? intval($element['~COMPANY_ID']) : 0;
	$companyField = $companyID > 0 && isset($arResult['COMPANY_FIELD']) ? $arResult['COMPANY_FIELD'] : null;
	$arEntityTypes = CCrmFieldMulti::GetEntityTypes();
	$arMultiFields = array();

	$contactFields = array();
	if($contactID > 0)
	{
		$res = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'CONTACT', 'ELEMENT_ID' => $contactID));
		while($arMultiField = $res->Fetch())
		{
			$typeID = $arMultiField['TYPE_ID'];
			$valueType = $arMultiField['VALUE_TYPE'];

			if(!isset($contactFields[$typeID]))
			{
				$contactFields[$typeID] = array();
			}
			if(!isset($contactFields[$typeID][$valueType]))
			{
				$contactFields[$typeID][$valueType] = array();
			}
			$contactFields[$typeID][$valueType][] = $arMultiField['VALUE'];
			$countKey = "{$typeID}_COUNT";
			if(!isset($contactFields[$countKey]))
			{
				$contactFields[$countKey] = 1;
			}
			else
			{
				$contactFields[$countKey]++;
			}
		}
		$arMultiFields["CONTACT_{$contactID}"] = $contactFields;
	}

	$companyFields = array();
	if($companyID > 0)
	{
		$res = CCrmFieldMulti::GetList(array('ID' => 'asc'), array('ENTITY_ID' => 'COMPANY', 'ELEMENT_ID' => $companyID));
		while($arMultiField = $res->Fetch())
		{
			$typeID = $arMultiField['TYPE_ID'];
			$valueType = $arMultiField['VALUE_TYPE'];

			if(!isset($companyFields[$typeID]))
			{
				$companyFields[$typeID] = array();
			}
			if(!isset($companyFields[$typeID][$valueType]))
			{
				$companyFields[$typeID][$valueType] = array();
			}
			$companyFields[$typeID][$valueType][] = $arMultiField['VALUE'];
			$countKey = "{$typeID}_COUNT";
			if(!isset($companyFields[$countKey]))
			{
				$companyFields[$countKey] = 1;
			}
			else
			{
				$companyFields[$countKey]++;
			}
		}
		$arMultiFields["COMPANY_{$companyID}"] = $companyFields;
	}

	$clientInfoParams = array();
	$clientPhoneParams = array(
		'DISPLAY_IF_EMPTY' => false,
		'TYPE'=> 'PHONE',
		'VALUE_TYPES' => isset($arEntityTypes['PHONE']) ? $arEntityTypes['PHONE'] : array()
	);

	$clientEmailParams = array(
		'DISPLAY_IF_EMPTY' => false,
		'TYPE'=> 'EMAIL',
		'VALUE_TYPES' => isset($arEntityTypes['EMAIL']) ? $arEntityTypes['EMAIL'] : array()
	);

	if($contactID > 0)
	{
		$clientInfoParams['ENTITY_TYPE_ID'] = CCrmOwnerType::Contact;
		$clientInfoParams['ENTITY_ID'] = $contactID;
		$clientInfoParams['NAME'] = isset($element['~CONTACT_FORMATTED_NAME']) ? $element['~CONTACT_FORMATTED_NAME'] : '';
		$clientInfoParams['DESCRIPTION'] = isset($element['~COMPANY_TITLE']) ? $element['~COMPANY_TITLE'] : '';
		$clientInfoParams['SHOW_URL'] = isset($arResult['PATH_TO_CONTACT_SHOW']) ? $arResult['PATH_TO_CONTACT_SHOW'] : '';
		$clientInfoParams['PHOTO_ID'] = isset($element['~CONTACT_PHOTO']) ? intval($element['~CONTACT_PHOTO']) : 0;

		$clientPhoneParams['VALUES'] = isset($contactFields['PHONE']) ? $contactFields['PHONE'] : array();
		$clientPhoneParams['VALUE_COUNT'] = isset($contactFields['PHONE_COUNT']) ? $contactFields['PHONE_COUNT'] : 0;
		$clientPhoneParams['PREFIX'] = strtolower($arResult['FORM_ID']).'_contact';

		$clientEmailParams['VALUES'] = isset($contactFields['EMAIL']) ? $contactFields['EMAIL'] : array();
		$clientEmailParams['VALUE_COUNT'] = isset($contactFields['EMAIL_COUNT']) ? $contactFields['EMAIL_COUNT'] : 0;
		$clientPhoneParams['PREFIX'] = strtolower($arResult['FORM_ID']).'_contact';
	}
	elseif($companyID > 0)
	{
		$clientInfoParams['ENTITY_TYPE_ID'] = CCrmOwnerType::Company;
		$clientInfoParams['ENTITY_ID'] = $companyID;
		$clientInfoParams['NAME'] = isset($element['~COMPANY_TITLE']) ? $element['~COMPANY_TITLE'] : '';
		$clientInfoParams['DESCRIPTION'] = isset($element['COMPANY_TYPE_TEXT']) ? $element['COMPANY_TYPE_TEXT'] : '';
		$clientInfoParams['SHOW_URL'] = isset($arResult['PATH_TO_COMPANY_SHOW']) ? $arResult['PATH_TO_COMPANY_SHOW'] : '';
		$clientInfoParams['PHOTO_ID'] = isset($element['~COMPANY_LOGO']) ? intval($element['~COMPANY_LOGO']) : 0;
		
		$clientPhoneParams['VALUES'] = isset($companyFields['PHONE']) ? $companyFields['PHONE'] : array();
		$clientPhoneParams['VALUE_COUNT'] = isset($companyFields['PHONE_COUNT']) ? $companyFields['PHONE_COUNT'] : 0;
		$clientPhoneParams['PREFIX'] = strtolower($arResult['FORM_ID']).'_company';

		$clientEmailParams['VALUES'] = isset($companyFields['EMAIL']) ? $companyFields['EMAIL'] : array();
		$clientEmailParams['VALUE_COUNT'] = isset($companyFields['EMAIL_COUNT']) ? $companyFields['EMAIL_COUNT'] : 0;
		$clientPhoneParams['PREFIX'] = strtolower($arResult['FORM_ID']).'_company';
	}
	else
	{
		$clientInfoParams['NAME'] = GetMessage('CRM_DEAL_CLIENT_NOT_ASSIGNED');
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
		if(!in_array($fieldID, $skipFields, true))
		{
			$mainSection['ITEMS'][] = array(
				'ID' => $fieldID,
				'TITLE' => $field['name'],
				'VALUE' => $field['value'],
				'DISPLAY_IF_EMPTY' => $fieldID === 'COMMENTS'
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
		if(!in_array($fieldID, $skipFields, true))
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
				'VALUE' => $element['~TITLE'],
				'EDITABLE' => in_array('TITLE', $arResult['EDITABLE_FIELDS'], true),
				'FIELD_ID' => 'TITLE'
			),
			'LEGEND' => GetMessage('CRM_DEAL_SHOW_LEGEND', array('#ID#' => $element['~ID'])),
			'LOCK_CONTROL_DATA' => array(
				'ENABLED' => true,
				'EDITABLE' => in_array('OPENED', $arResult['EDITABLE_FIELDS'], true),
				'FIELD_ID' => 'OPENED',
				'IS_LOCKED' => isset($element['~OPENED']) && $element['~OPENED'] !== 'Y',
				'LOCK_LEGEND' => GetMessage('CRM_DEAL_NOT_OPENED'),
				'UNLOCK_LEGEND' => GetMessage('CRM_DEAL_OPENED')
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
									'ID' => 'DEAL_STAGE',
									'TYPE' => 'PROGRESS',
									'TITLE' => GetMessage('CRM_DEAL_SIDEBAR_STAGE'),
									'PARAMS' => array(
										'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Deal),
										'REGISTER_SETTINGS' => true,
										'PREFIX' => "{$arResult['FORM_ID']}_PROGRESS_BAR_",
										'ENTITY_ID' => $element['~ID'],
										'CURRENT_ID' => $element['~STAGE_ID'],
										'SERVICE_URL' => '/bitrix/components/bitrix/crm.deal.list/list.ajax.php'
									)
								),
								array(
									'ID' => 'OPPORTUNITY',
									'TYPE' => 'MONEY',
									'TITLE' => GetMessage('CRM_DEAL_OPPORTUNITY_SHORT'),
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
									'TITLE' => GetMessage('CRM_DEAL_TYPE'),
									'PARAMS' => array(
										'FIELD_ID' => 'TYPE_ID',
										'VALUE' => isset($element['~TYPE_ID']) ? $element['~TYPE_ID'] : '',
										'ITEMS' => $arResult['TYPE_LIST'],
										'ENCODE_ITEMS' => false,
										'SELECTOR_ID' => 'deal_type',
										'UNDEFINED' => GetMessage('CRM_DEAL_TYPE_UNDEF'),
										'EDITABLE' => in_array('TYPE_ID', $arResult['EDITABLE_FIELDS'], true)
										//, 'CLASS' => 'crm-entity-info-field-deal-type'
										//, 'CONTAINER_ID' => $summaryContainerID
									)
								),
								array(
									'ID' => 'PROBABILITY',
									'TYPE' => 'PERCENT',
									'TITLE' => GetMessage('CRM_DEAL_PROBABILITY'),
									'PARAMS' => array(
										'FIELD_ID' => 'PROBABILITY',
										'VALUE' => isset($element['~PROBABILITY']) ? intval($element['~PROBABILITY']) : 0,
										'EDITABLE' => in_array('PROBABILITY', $arResult['EDITABLE_FIELDS'], true)
									)
								)
							)
						),
						'center' => array(
							'ID' => 'center',
							'ITEMS' => array(
								array(
									'ID' => 'DEAL_DURATION',
									'TYPE' => 'DURATION',
									'TITLE' => GetMessage('CRM_DEAL_DURATION'),
									'PARAMS' => array(
										'FROM' => isset($element['~BEGINDATE']) ? $element['~BEGINDATE'] : '',
										'TO' => isset($element['~CLOSEDATE']) ? $element['~CLOSEDATE'] : ''
									)
								),
								array(
									'ID' => 'DEAL_CLIENT_INFO',
									'TYPE' => 'CLIENT_INFO',
									'TITLE' => GetMessage('CRM_DEAL_CLIENT'),
									'PARAMS' => $clientInfoParams
								),
								array(
									'ID' => 'DEAL_CLIENT_PHONE',
									'TYPE' => 'MULTIFIELD',
									'TITLE' => GetMessage('CRM_DEAL_CLIENT_PHONE'),
									'PARAMS' => $clientPhoneParams
								),
								array(
									'ID' => 'DEAL_CLIENT_EMAIL',
									'TYPE' => 'MULTIFIELD',
									'TITLE' => GetMessage('CRM_DEAL_CLIENT_EMAIL'),
									'PARAMS' => $clientEmailParams
								)
							)
						),
						'right' => array(
							'ID' => 'right',
							'ITEMS' => array(
								array(
									'ID' => 'DEAL_RESPONSIBLE',
									'TYPE' => 'RESPONSIBLE',
									'PARAMS' =>	array(
										'PREFIX' => 'crm_deal_summary',
										'EDITABLE' => in_array('ASSIGNED_BY_ID', $arResult['EDITABLE_FIELDS'], true),
										'USER_ID' => isset($element['~ASSIGNED_BY_ID']) ? intval($element['~ASSIGNED_BY_ID']) : 0,
										'NAME' => isset($element['~ASSIGNED_BY_FORMATTED_NAME']) ? $element['~ASSIGNED_BY_FORMATTED_NAME'] : '',
										'PHOTO' => isset($element['~ASSIGNED_BY_PERSONAL_PHOTO']) ? intval($element['~ASSIGNED_BY_PERSONAL_PHOTO']) : 0,
										'WORK_POSITION' => isset($element['~ASSIGNED_BY_WORK_POSITION']) ? $element['~ASSIGNED_BY_WORK_POSITION'] : '',
										'FIELD_ID' => 'ASSIGNED_BY_ID',
										'SERVICE_URL' => '/bitrix/components/bitrix/crm.deal.show/ajax.php?'.bitrix_sessid_get(),
										'USER_PROFILE_URL_TEMPLATE' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_USER_PROFILE'])
									)
								),
								array(
									'ID' => 'DEAL_DATE_MODIFY',
									'TITLE' => GetMessage('CRM_DEAL_DATE_MODIFY'),
									'TYPE' => 'MODIFICATION_INFO',
									'PARAMS' => array(
										'DATE' => isset($arResult['ELEMENT']['~DATE_MODIFY']) ? $arResult['ELEMENT']['~DATE_MODIFY'] : '',
										'USER_NAME' => $arResult['ELEMENT']['~MODIFY_BY_FORMATTED_NAME'],
										'PATH_TO_USER' => $arResult['ELEMENT']['PATH_TO_USER_MODIFIER']
									)
								),
								array(
									'ID' => 'DEAL_DATE_CREATE',
									'TITLE' => GetMessage('CRM_DEAL_DATE_CREATE'),
									'TYPE' => 'MODIFICATION_INFO',
									'PARAMS' => array(
										'DATE' => isset($arResult['ELEMENT']['~DATE_CREATE']) ? $arResult['ELEMENT']['~DATE_CREATE'] : '',
										'USER_NAME' => $arResult['ELEMENT']['~CREATED_BY_FORMATTED_NAME'],
										'PATH_TO_USER' => $arResult['ELEMENT']['PATH_TO_USER_CREATOR']
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
						'ID' => 'DEAL_COMMENTS',
						'TITLE' => GetMessage('CRM_DEAL_COMMENT'),
						'PARAMS' => array(
							'TYPE' => 'LHE',
							'FIELD_ID' => 'COMMENTS',
							'VALUE' => isset($element['~COMMENTS']) ? $element['~COMMENTS'] : '',
							'EDITOR_ID' => 'DealCommentEditor',
							'EDITOR_JS_NAME' => 'oLHEDealComment',
							'WRAPPER_ID' => 'deal_comment_lhe_wrapper',
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
									'ID' => 'DEAL_STAGE_FOLDED',
									'TYPE' => 'PROGRESS',
									'PARAMS' => array(
										'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Deal),
										'REGISTER_SETTINGS' => false,
										'PREFIX' => "{$arResult['FORM_ID']}_PROGRESS_BAR_FOLDED",
										'ENTITY_ID' => $element['~ID'],
										'CURRENT_ID' => $element['~STAGE_ID'],
										'SERVICE_URL' => '/bitrix/components/bitrix/crm.deal.list/list.ajax.php'
									)
								)
							)
						),
						array(
							'ITEMS' => array(
								array(
									'ID' => 'OPPORTUNITY_FOLDED',
									'TYPE' => 'MONEY',
									'TITLE' => GetMessage('CRM_DEAL_OPPORTUNITY_SHORT'),
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
									'ID' => 'DEAL_CLIENT_INFO_FOLDED',
									'TYPE' => 'CLIENT_INFO',
									'TITLE' => GetMessage('CRM_DEAL_CLIENT'),
									'PARAMS' => $clientInfoParams
								),
							)
						),
						array(
							'ITEMS' => array(
								array(
									'ID' => 'DEAL_RESPONSIBLE_FOLDED',
									'TYPE' => 'RESPONSIBLE',
									'PARAMS' =>	array(
										'PREFIX' => 'crm_deal_summary_fold',
										'EDITABLE' => false,
										'USER_ID' => isset($element['~ASSIGNED_BY_ID']) ? intval($element['~ASSIGNED_BY_ID']) : 0,
										'NAME' => isset($element['~ASSIGNED_BY_FORMATTED_NAME']) ? $element['~ASSIGNED_BY_FORMATTED_NAME'] : '',
										'PHOTO' => isset($element['~ASSIGNED_BY_PERSONAL_PHOTO']) ? intval($element['~ASSIGNED_BY_PERSONAL_PHOTO']) : 0,
										'WORK_POSITION' => isset($element['~ASSIGNED_BY_WORK_POSITION']) ? $element['~ASSIGNED_BY_WORK_POSITION'] : '',
										'FIELD_ID' => 'ASSIGNED_BY_ID',
										'SERVICE_URL' => '/bitrix/components/bitrix/crm.deal.show/ajax.php?'.bitrix_sessid_get(),
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
$APPLICATION->AddHeadScript('/bitrix/js/crm/instant_editor.js');
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
					ownerType: 'D',
					ownerID: <?=$arResult['ELEMENT_ID']?>,
					url: '/bitrix/components/bitrix/crm.deal.show/ajax.php?<?=bitrix_sessid_get()?>',
					callToFormat: <?=CCrmCallToUrl::GetFormat(CCrmCallToUrl::Slashless)?>
				}
			);

			var prodEditor = typeof(BX.CrmProductEditor) !== 'undefined' ? BX.CrmProductEditor.getDefault() : null;

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
