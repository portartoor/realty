<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 */

$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/activity.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/interface_grid.js');
Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/crm/autorun_proc.js');
Bitrix\Main\Page\Asset::getInstance()->addCss('/bitrix/js/crm/css/autorun_proc.css');

?><div id="rebuildMessageWrapper"><?

if($arResult['NEED_FOR_REBUILD_SEARCH_CONTENT']):
	?><div id="rebuildDealSearchWrapper"></div><?
endif;

if($arResult['NEED_FOR_REBUILD_DEAL_ATTRS']):
	?><div id="rebuildDealAttrsMsg" class="crm-view-message">
		<?=GetMessage('CRM_DEAL_REBUILD_ACCESS_ATTRS', array('#ID#' => 'rebuildDealAttrsLink', '#URL#' => $arResult['PATH_TO_PRM_LIST']))?>
	</div><?
endif;
?></div><?
$isInternal = $arResult['INTERNAL'];
$allowWrite = $arResult['PERMS']['WRITE'];
$allowDelete = $arResult['PERMS']['DELETE'];
$currentUserID = $arResult['CURRENT_USER_ID'];
$activityEditorID = '';
if(!$isInternal):
	$activityEditorID = "{$arResult['GRID_ID']}_activity_editor";
	$APPLICATION->IncludeComponent(
		'bitrix:crm.activity.editor',
		'',
		array(
			'EDITOR_ID' => $activityEditorID,
			'PREFIX' => $arResult['GRID_ID'],
			'OWNER_TYPE' => 'DEAL',
			'OWNER_ID' => 0,
			'READ_ONLY' => false,
			'ENABLE_UI' => false,
			'ENABLE_TOOLBAR' => false
		),
		null,
		array('HIDE_ICONS' => 'Y')
	);
endif;

$gridManagerID = $arResult['GRID_ID'].'_MANAGER';
$gridManagerCfg = array(
	'ownerType' => 'DEAL',
	'gridId' => $arResult['GRID_ID'],
	'formName' => "form_{$arResult['GRID_ID']}",
	'allRowsCheckBoxId' => "actallrows_{$arResult['GRID_ID']}",
	'activityEditorId' => $activityEditorID,
	'serviceUrl' => '/bitrix/components/bitrix/crm.activity.editor/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
	'filterFields' => array()
);
echo CCrmViewHelper::RenderDealStageSettings();
$prefix = $arResult['GRID_ID'];
$prefixLC = strtolower($arResult['GRID_ID']);

$arResult['GRID_DATA'] = array();
$arColumns = array();
foreach ($arResult['HEADERS'] as $arHead)
	$arColumns[$arHead['id']] = false;
foreach($arResult['DEAL'] as $sKey =>  $arDeal)
{
	$jsTitle = isset($arDeal['~TITLE']) ? CUtil::JSEscape($arDeal['~TITLE']) : '';
	$jsShowUrl = isset($arDeal['PATH_TO_DEAL_SHOW']) ? CUtil::JSEscape($arDeal['PATH_TO_DEAL_SHOW']) : '';

	$arActivityMenuItems = array();
	$arActivitySubMenuItems = array();
	$arActions = array();

	$arActions[] = array(
		'TITLE' => GetMessage('CRM_DEAL_SHOW_TITLE'),
		'TEXT' => GetMessage('CRM_DEAL_SHOW'),
		'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arDeal['PATH_TO_DEAL_SHOW'])."');",
		'DEFAULT' => true
	);

	if($arDeal['EDIT'])
	{
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_DEAL_EDIT_TITLE'),
			'TEXT' => GetMessage('CRM_DEAL_EDIT'),
			'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arDeal['PATH_TO_DEAL_EDIT'])."');"
		);
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_DEAL_COPY_TITLE'),
			'TEXT' => GetMessage('CRM_DEAL_COPY'),
			'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arDeal['PATH_TO_DEAL_COPY'])."');"
		);
	}

	if(!$isInternal && $arDeal['DELETE'])
	{
		$pathToRemove = CUtil::JSEscape($arDeal['PATH_TO_DEAL_DELETE']);
		$arActions[] = array(
			'TITLE' => GetMessage('CRM_DEAL_DELETE_TITLE'),
			'TEXT' => GetMessage('CRM_DEAL_DELETE'),
			'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
				'{$gridManagerID}', 
				BX.CrmUIGridMenuCommand.remove, 
				{ pathToRemove: '{$pathToRemove}' }
			)"
		);
	}

	$arActions[] = array('SEPARATOR' => true);

	if(!$isInternal)
	{
		if($arResult['CAN_CONVERT'])
		{
			if($arResult['CONVERSION_PERMITTED'])
			{
				$arSchemeDescriptions = \Bitrix\Crm\Conversion\DealConversionScheme::getJavaScriptDescriptions(true);
				$arSchemeList = array();
				foreach($arSchemeDescriptions as $name => $description)
				{
					$arSchemeList[] = array(
						'TITLE' => $description,
						'TEXT' => $description,
						'ONCLICK' => "BX.CrmDealConverter.getCurrent().convert({$arDeal['ID']}, BX.CrmDealConversionScheme.createConfig('{$name}'), '".CUtil::JSEscape($APPLICATION->GetCurPage())."');"
					);
				}
				if(!empty($arSchemeList))
				{
					$arActions[] = array('SEPARATOR' => true);
					$arActions[] = array(
						'TITLE' => GetMessage('CRM_DEAL_CREATE_ON_BASIS_TITLE'),
						'TEXT' => GetMessage('CRM_DEAL_CREATE_ON_BASIS'),
						'MENU' => $arSchemeList
					);
				}
			}else
			{
				$arActions[] = array(
					'TITLE' => GetMessage('CRM_DEAL_CREATE_ON_BASIS_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_CREATE_ON_BASIS'),
					'ONCLICK' => isset($arResult['CONVERSION_LOCK_SCRIPT']) ? $arResult['CONVERSION_LOCK_SCRIPT'] : ''
				);
			}

			$arActions[] = array('SEPARATOR' => true);
		}

		if($arDeal['EDIT'])
		{
			$arActions[] = $arActivityMenuItems[] = array(
				'TITLE' => GetMessage('CRM_DEAL_EVENT_TITLE'),
				'TEXT' => GetMessage('CRM_DEAL_EVENT'),
				'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
					'{$gridManagerID}', 
					BX.CrmUIGridMenuCommand.createEvent, 
					{ entityTypeName: BX.CrmEntityType.names.deal, entityId: {$arDeal['ID']} }
				)"
			);

			if(IsModuleInstalled(CRM_MODULE_CALENDAR_ID))
			{
				$arActivityMenuItems[] = array(
					'TITLE' => GetMessage('CRM_DEAL_ADD_CALL_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_ADD_CALL'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}', 
						BX.CrmUIGridMenuCommand.createActivity, 
						{ typeId: BX.CrmActivityType.call, settings: { ownerID: {$arDeal['ID']} } }
					)"
				);

				$arActivityMenuItems[] = array(
					'TITLE' => GetMessage('CRM_DEAL_ADD_MEETING_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_ADD_MEETING'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}', 
						BX.CrmUIGridMenuCommand.createActivity, 
						{ typeId: BX.CrmActivityType.meeting, settings: { ownerID: {$arDeal['ID']} } }
					)"
				);

				$arActivitySubMenuItems[] = array(
					'TITLE' => GetMessage('CRM_DEAL_ADD_CALL_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_ADD_CALL_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}', 
						BX.CrmUIGridMenuCommand.createActivity, 
						{ typeId: BX.CrmActivityType.call, settings: { ownerID: {$arDeal['ID']} } }
					)"
				);

				$arActivitySubMenuItems[] = array(
					'TITLE' => GetMessage('CRM_DEAL_ADD_MEETING_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_ADD_MEETING_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}', 
						BX.CrmUIGridMenuCommand.createActivity, 
						{ typeId: BX.CrmActivityType.meeting, settings: { ownerID: {$arDeal['ID']} } }
					)"
				);
			}

			if(IsModuleInstalled('tasks'))
			{
				$arActivityMenuItems[] = array(
					'TITLE' => GetMessage('CRM_DEAL_TASK_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_TASK'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}', 
						BX.CrmUIGridMenuCommand.createActivity, 
						{ typeId: BX.CrmActivityType.task, settings: { ownerID: {$arDeal['ID']} } }
					)"
				);

				$arActivitySubMenuItems[] = array(
					'TITLE' => GetMessage('CRM_DEAL_TASK_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_TASK_SHORT'),
					'ONCLICK' => "BX.CrmUIGridExtension.processMenuCommand(
						'{$gridManagerID}', 
						BX.CrmUIGridMenuCommand.createActivity, 
						{ typeId: BX.CrmActivityType.task, settings: { ownerID: {$arDeal['ID']} } }
					)"
				);
			}

			if(!empty($arActivitySubMenuItems))
			{
				$arActions[] = array(
					'TITLE' => GetMessage('CRM_DEAL_ADD_ACTIVITY_TITLE'),
					'TEXT' => GetMessage('CRM_DEAL_ADD_ACTIVITY'),
					'MENU' => $arActivitySubMenuItems
				);
			}

			if($arResult['IS_BIZPROC_AVAILABLE'])
			{
				$arActions[] = array('SEPARATOR' => true);
				if(isset($arContact['PATH_TO_BIZPROC_LIST']) && $arContact['PATH_TO_BIZPROC_LIST'] !== '')
					$arActions[] = array(
						'TITLE' => GetMessage('CRM_DEAL_BIZPROC_TITLE'),
						'TEXT' => GetMessage('CRM_DEAL_BIZPROC'),
						'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arDeal['PATH_TO_BIZPROC_LIST'])."');"
					);
				if(!empty($arDeal['BIZPROC_LIST']))
				{
					$arBizprocList = array();
					foreach($arDeal['BIZPROC_LIST'] as $arBizproc)
					{
						$arBizprocList[] = array(
							'TITLE' => $arBizproc['DESCRIPTION'],
							'TEXT' => $arBizproc['NAME'],
							'ONCLICK' => "jsUtils.Redirect([], '".CUtil::JSEscape($arBizproc['PATH_TO_BIZPROC_START'])."');"
						);
					}
					$arActions[] = array(
						'TITLE' => GetMessage('CRM_DEAL_BIZPROC_LIST_TITLE'),
						'TEXT' => GetMessage('CRM_DEAL_BIZPROC_LIST'),
						'MENU' => $arBizprocList
					);
				}
			}
		}
	}

	$eventParam = array(
		'ID' => $arDeal['ID'],
	);
	foreach(GetModuleEvents('crm', 'onCrmDealListItemBuildMenu', true) as $event)
	{
		ExecuteModuleEventEx($event, array('CRM_DEAL_LIST_MENU', $eventParam, &$arActions));
	}

	$resultItem = array(
		'id' => $arDeal['ID'],
		'actions' => $arActions,
		'data' => $arDeal,
		'editable' => !$arDeal['EDIT'] ? ($arResult['INTERNAL'] ? 'N' : $arColumns) : 'Y',
		'columns' => array(
			'DEAL_SUMMARY' => CCrmViewHelper::RenderInfo($arDeal['PATH_TO_DEAL_SHOW'], isset($arDeal['TITLE']) ? $arDeal['TITLE'] : ('['.$arDeal['ID'].']'), $arDeal['DEAL_TYPE_NAME'], '_self'),
			'DEAL_CLIENT' => isset($arDeal['CLIENT_INFO']) ? CCrmViewHelper::PrepareClientInfo($arDeal['CLIENT_INFO']) : '',
			'COMPANY_ID' => isset($arDeal['COMPANY_INFO']) ? CCrmViewHelper::PrepareClientInfo($arDeal['COMPANY_INFO']) : '',
			'CONTACT_ID' => isset($arDeal['CONTACT_INFO']) ? CCrmViewHelper::PrepareClientInfo($arDeal['CONTACT_INFO']) : '',
			'TITLE' => '<a target="_self" href="'.$arDeal['PATH_TO_DEAL_SHOW'].'"
				class="'.($arDeal['BIZPROC_STATUS'] != '' ? 'bizproc bizproc_status_'.$arDeal['BIZPROC_STATUS'] : '').'"
				'.($arDeal['BIZPROC_STATUS_HINT'] != '' ? 'onmouseover="BX.hint(this, \''.CUtil::JSEscape($arDeal['BIZPROC_STATUS_HINT']).'\');"' : '').'>'.$arDeal['TITLE'].'</a>',
			'CLOSED' => $arDeal['CLOSED'] == 'Y' ? GetMessage('MAIN_YES') : GetMessage('MAIN_NO'),
			'ASSIGNED_BY' => $arDeal['~ASSIGNED_BY_ID'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "DEAL_{$arDeal['~ID']}_RESPONSIBLE",
						'USER_ID' => $arDeal['~ASSIGNED_BY_ID'],
						'USER_NAME'=> $arDeal['ASSIGNED_BY'],
						'USER_PROFILE_URL' => $arDeal['PATH_TO_USER_PROFILE']
					)
				) : '',
			'COMMENTS' => htmlspecialcharsback($arDeal['COMMENTS']),
			'SUM' => $arDeal['FORMATTED_OPPORTUNITY'],
			'OPPORTUNITY' => $arDeal['OPPORTUNITY'],
			'PROBABILITY' => "{$arDeal['PROBABILITY']}%",
			'DATE_CREATE' => '<nobr>'.FormatDate('SHORT', MakeTimeStamp($arDeal['DATE_CREATE'])).'</nobr>',
			'DATE_MODIFY' => '<nobr>'.FormatDate('SHORT', MakeTimeStamp($arDeal['DATE_MODIFY'])).'</nobr>',
			'TYPE_ID' => isset($arResult['TYPE_LIST'][$arDeal['TYPE_ID']]) ? $arResult['TYPE_LIST'][$arDeal['TYPE_ID']] : $arDeal['TYPE_ID'],
			'EVENT_ID' => isset($arResult['EVENT_LIST'][$arDeal['EVENT_ID']]) ? $arResult['EVENT_LIST'][$arDeal['EVENT_ID']] : $arDeal['EVENT_ID'],
			'CURRENCY_ID' => CCrmCurrency::GetCurrencyName($arDeal['CURRENCY_ID']),
			'PRODUCT_ID' => isset($arDeal['PRODUCT_ROWS']) ? htmlspecialcharsbx(CCrmProductRow::RowsToString($arDeal['PRODUCT_ROWS'])) : '',
			'STATE_ID' => isset($arResult['STATE_LIST'][$arDeal['STATE_ID']]) ? $arResult['STATE_LIST'][$arDeal['STATE_ID']] : $arDeal['STATE_ID'],
			'WEBFORM_ID' => isset($arResult['WEBFORM_LIST'][$arDeal['WEBFORM_ID']]) ? $arResult['WEBFORM_LIST'][$arDeal['WEBFORM_ID']] : $arDeal['WEBFORM_ID'],
			'STAGE_ID' => CCrmViewHelper::RenderDealStageControl(
				array(
					'PREFIX' => "{$arResult['GRID_ID']}_PROGRESS_BAR_",
					'ENTITY_ID' => $arDeal['~ID'],
					'CURRENT_ID' => $arDeal['~STAGE_ID'],
					'CATEGORY_ID' => $arDeal['~CATEGORY_ID'],
					'SERVICE_URL' => '/bitrix/components/bitrix/crm.deal.list/list.ajax.php',
					'READ_ONLY' => !(isset($arDeal['EDIT']) && $arDeal['EDIT'] === true)
				)
			),
			'CATEGORY_ID' => $arDeal['DEAL_CATEGORY_NAME'],
			'ORIGINATOR_ID' => isset($arDeal['ORIGINATOR_NAME']) ? $arDeal['ORIGINATOR_NAME'] : '',
			'CREATED_BY' => $arDeal['~CREATED_BY'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "DEAL_{$arDeal['~ID']}_CREATOR",
						'USER_ID' => $arDeal['~CREATED_BY'],
						'USER_NAME'=> $arDeal['CREATED_BY_FORMATTED_NAME'],
						'USER_PROFILE_URL' => $arDeal['PATH_TO_USER_CREATOR']
					)
				) : '',
			'MODIFY_BY' => $arDeal['~MODIFY_BY'] > 0
				? CCrmViewHelper::PrepareUserBaloonHtml(
					array(
						'PREFIX' => "DEAL_{$arDeal['~ID']}_MODIFIER",
						'USER_ID' => $arDeal['~MODIFY_BY'],
						'USER_NAME'=> $arDeal['MODIFY_BY_FORMATTED_NAME'],
						'USER_PROFILE_URL' => $arDeal['PATH_TO_USER_MODIFIER']
					)
				) : '',
		) + $arResult['DEAL_UF'][$sKey]
	);

	$userActivityID = isset($arDeal['~ACTIVITY_ID']) ? intval($arDeal['~ACTIVITY_ID']) : 0;
	$commonActivityID = isset($arDeal['~C_ACTIVITY_ID']) ? intval($arDeal['~C_ACTIVITY_ID']) : 0;
	if($userActivityID > 0)
	{
		$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
			array(
				'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Deal),
				'ENTITY_ID' => $arDeal['~ID'],
				'ENTITY_RESPONSIBLE_ID' => $arDeal['~ASSIGNED_BY'],
				'GRID_MANAGER_ID' => $gridManagerID,
				'ACTIVITY_ID' => $userActivityID,
				'ACTIVITY_SUBJECT' => isset($arDeal['~ACTIVITY_SUBJECT']) ? $arDeal['~ACTIVITY_SUBJECT'] : '',
				'ACTIVITY_TIME' => isset($arDeal['~ACTIVITY_TIME']) ? $arDeal['~ACTIVITY_TIME'] : '',
				'ACTIVITY_EXPIRED' => isset($arDeal['~ACTIVITY_EXPIRED']) ? $arDeal['~ACTIVITY_EXPIRED'] : '',
				'ALLOW_EDIT' => $arDeal['EDIT'],
				'MENU_ITEMS' => $arActivityMenuItems,
				'USE_GRID_EXTENSION' => true
			)
		);

		$counterData = array(
			'CURRENT_USER_ID' => $currentUserID,
			'ENTITY' => $arDeal,
			'ACTIVITY' => array(
				'RESPONSIBLE_ID' => $currentUserID,
				'TIME' => isset($arDeal['~ACTIVITY_TIME']) ? $arDeal['~ACTIVITY_TIME'] : '',
				'IS_CURRENT_DAY' => isset($arDeal['~ACTIVITY_IS_CURRENT_DAY']) ? $arDeal['~ACTIVITY_IS_CURRENT_DAY'] : false
			)
		);

		if(CCrmUserCounter::IsReckoned(CCrmUserCounter::CurrentDealActivies, $counterData))
		{
			$resultItem['columnClasses'] = array('ACTIVITY_ID' => 'crm-list-deal-today');
		}
	}
	elseif($commonActivityID > 0)
	{
		$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
			array(
				'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Deal),
				'ENTITY_ID' => $arDeal['~ID'],
				'ENTITY_RESPONSIBLE_ID' => $arDeal['~ASSIGNED_BY'],
				'GRID_MANAGER_ID' => $gridManagerID,
				'ACTIVITY_ID' => $commonActivityID,
				'ACTIVITY_SUBJECT' => isset($arDeal['~C_ACTIVITY_SUBJECT']) ? $arDeal['~C_ACTIVITY_SUBJECT'] : '',
				'ACTIVITY_TIME' => isset($arDeal['~C_ACTIVITY_TIME']) ? $arDeal['~C_ACTIVITY_TIME'] : '',
				'ACTIVITY_RESPONSIBLE_ID' => isset($arDeal['~C_ACTIVITY_RESP_ID']) ? intval($arDeal['~C_ACTIVITY_RESP_ID']) : 0,
				'ACTIVITY_RESPONSIBLE_LOGIN' => isset($arDeal['~C_ACTIVITY_RESP_LOGIN']) ? $arDeal['~C_ACTIVITY_RESP_LOGIN'] : '',
				'ACTIVITY_RESPONSIBLE_NAME' => isset($arDeal['~C_ACTIVITY_RESP_NAME']) ? $arDeal['~C_ACTIVITY_RESP_NAME'] : '',
				'ACTIVITY_RESPONSIBLE_LAST_NAME' => isset($arDeal['~C_ACTIVITY_RESP_LAST_NAME']) ? $arDeal['~C_ACTIVITY_RESP_LAST_NAME'] : '',
				'ACTIVITY_RESPONSIBLE_SECOND_NAME' => isset($arDeal['~C_ACTIVITY_RESP_SECOND_NAME']) ? $arDeal['~C_ACTIVITY_RESP_SECOND_NAME'] : '',
				'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
				'ALLOW_EDIT' => $arDeal['EDIT'],
				'MENU_ITEMS' => $arActivityMenuItems,
				'USE_GRID_EXTENSION' => true
			)
		);
	}
	else
	{
		$resultItem['columns']['ACTIVITY_ID'] = CCrmViewHelper::RenderNearestActivity(
			array(
				'ENTITY_TYPE_NAME' => CCrmOwnerType::ResolveName(CCrmOwnerType::Deal),
				'ENTITY_ID' => $arDeal['~ID'],
				'ENTITY_RESPONSIBLE_ID' => $arDeal['~ASSIGNED_BY'],
				'GRID_MANAGER_ID' => $gridManagerID,
				'ALLOW_EDIT' => $arDeal['EDIT'],
				'MENU_ITEMS' => $arActivityMenuItems,
				'USE_GRID_EXTENSION' => true
			)
		);

		$counterData = array(
			'CURRENT_USER_ID' => $currentUserID,
			'ENTITY' => $arDeal
		);

		if(CCrmUserCounter::IsReckoned(CCrmUserCounter::CurrentDealActivies, $counterData))
		{
			$resultItem['columnClasses'] = array('ACTIVITY_ID' => 'crm-list-enitity-action-need');
		}
	}

	$arResult['GRID_DATA'][] = &$resultItem;
	unset($resultItem);
}
$APPLICATION->IncludeComponent('bitrix:main.user.link',
	'',
	array(
		'AJAX_ONLY' => 'Y',
	),
	false,
	array('HIDE_ICONS' => 'Y')
);

//region Action Panel
$controlPanel = array('GROUPS' => array(array('ITEMS' => array())));

if(!$isInternal
	&& ($allowWrite || $allowDelete))
{
	$snippet = new \Bitrix\Main\Grid\Panel\Snippet();
	$applyButton = $snippet->getApplyButton(
		array(
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processApplyButtonClick('{$gridManagerID}')"))
				)
			)
		)
	);

	$actionList = array(array('NAME' => GetMessage('CRM_DEAL_LIST_CHOOSE_ACTION'), 'VALUE' => 'none'));

	$yesnoList = array(
		array('NAME' => GetMessage('MAIN_YES'), 'VALUE' => 'Y'),
		array('NAME' => GetMessage('MAIN_NO'), 'VALUE' => 'N')
	);

	if($allowWrite)
	{
		//region Add Task
		if (IsModuleInstalled('tasks'))
		{
			$actionList[] = array(
				'NAME' => GetMessage('CRM_DEAL_TASK'),
				'VALUE' => 'tasks',
				'ONCHANGE' => array(
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
						'DATA' => array($applyButton)
					),
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
						'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'tasks')"))
					)
				)
			);
		}
		//endregion

		//region Set Stage
		if($arResult['EFFECTIVE_CATEGORY_ID'] >= 0)
		{
			//TODO: if category not selected show 2 selectors: category and stage
			$stageList = array(array('NAME' => GetMessage('CRM_STAGE_INIT'), 'VALUE' => ''));
			if(isset($arResult['CATEGORY_STAGE_LIST']))
			{
				foreach($arResult['CATEGORY_STAGE_LIST'] as $stageID => $stageName)
				{
					$stageList[] = array('NAME' => $stageName, 'VALUE' => $stageID);
				}
			}
			elseif(isset($arResult['CATEGORY_STAGE_GROUPS']))
			{
				foreach($arResult['CATEGORY_STAGE_GROUPS'] as $group)
				{
					$groupName = isset($group['name']) ? $group['name'] : '';
					if($groupName !== '')
					{
						$stageList[] = array('NAME' => $groupName, 'VALUE' => '', 'IS_GROUP' => true);
					}

					$groupItems = isset($group['items']) && is_array($group['items']) ? $group['items'] : array();
					foreach($groupItems as $itemKey => $itemName)
					{
						$stageList[] = array('NAME' => $itemName, 'VALUE' => $itemKey);
					}
				}
			}

			$actionList[] = array(
				'NAME' => GetMessage('CRM_DEAL_SET_STAGE'),
				'VALUE' => 'set_stage',
				'ONCHANGE' => array(
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
						'DATA' => array(
							array(
								'TYPE' => Bitrix\Main\Grid\Panel\Types::DROPDOWN,
								'ID' => 'action_stage_id',
								'NAME' => 'ACTION_STAGE_ID',
								'ITEMS' => $stageList
							),
							$applyButton
						)
					),
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
						'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'set_stage')"))
					)
				)
			);
		}
		else
		{
			$actionList[] = array(
				'NAME' => GetMessage('CRM_DEAL_SET_STAGE'),
				'VALUE' => 'set_stage',
				'PSEUDO' => true,
				'ONCHANGE' => array(
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
						'DATA' => array($applyButton)
					),
					array(
						'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
						'DATA' => array(array('JS' => "BX.NotificationPopup.show(\"select_category\", { timeout: 5000, align: \"justify\", messages: [\"".GetMessageJS('CRM_DEAL_LIST_SELECT_CATEGORY_FOR_SET_STAGE')."\"] })"))
					)
				)
			);
		}
		//endregion

		//region Assign To
		//region Render User Search control
		if(!Bitrix\Main\Grid\Context::isInternalRequest())
		{
			//action_assigned_by_search + _control
			//Prefix control will be added by main.ui.grid
			$APPLICATION->IncludeComponent(
				'bitrix:intranet.user.selector.new',
				'',
				array(
					'MULTIPLE' => 'N',
					'NAME' => "{$prefix}_ACTION_ASSIGNED_BY",
					'INPUT_NAME' => 'action_assigned_by_search_control',
					'SHOW_EXTRANET_USERS' => 'NONE',
					'POPUP' => 'Y',
					'SITE_ID' => SITE_ID,
					'NAME_TEMPLATE' => $arResult['NAME_TEMPLATE']
				),
				null,
				array('HIDE_ICONS' => 'Y')
			);
		}
		//endregion
		$actionList[] = array(
			'NAME' => GetMessage('CRM_DEAL_ASSIGN_TO'),
			'VALUE' => 'assign_to',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array(
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::TEXT,
							'ID' => 'action_assigned_by_search',
							'NAME' => 'ACTION_ASSIGNED_BY_SEARCH'
						),
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::HIDDEN,
							'ID' => 'action_assigned_by_id',
							'NAME' => 'ACTION_ASSIGNED_BY_ID'
						),
						$applyButton
					)
				),
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(
						array('JS' => "BX.CrmUIGridExtension.prepareAction('{$gridManagerID}', 'assign_to',  { searchInputId: 'action_assigned_by_search_control', dataInputId: 'action_assigned_by_id_control', componentName: '{$prefix}_ACTION_ASSIGNED_BY' })")
					)
				),
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'assign_to')"))
				)
			)
		);
		//endregion

		//region Refresh Accounting Data
		$actionList[] = array(
			'NAME' => GetMessage('CRM_DEAL_REFRESH_ACCOUNT'),
			'VALUE' => 'refresh_account',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array($applyButton)
				)
			)

		);
		//endregion
	}

	if($allowDelete)
	{
		$controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getRemoveButton();
		$actionList[] = $snippet->getRemoveAction();
	}

	if($allowWrite)
	{
		//region Edit Button
		$controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getEditButton();
		$actionList[] = $snippet->getEditAction();
		//endregion

		//region Mark as Opened
		$actionList[] = array(
			'NAME' => GetMessage('CRM_DEAL_MARK_AS_OPENED'),
			'VALUE' => 'mark_as_opened',
			'ONCHANGE' => array(
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CREATE,
					'DATA' => array(
						array(
							'TYPE' => Bitrix\Main\Grid\Panel\Types::DROPDOWN,
							'ID' => 'action_opened',
							'NAME' => 'ACTION_OPENED',
							'ITEMS' => $yesnoList
						),
						$applyButton
					)
				),
				array(
					'ACTION' => Bitrix\Main\Grid\Panel\Actions::CALLBACK,
					'DATA' => array(array('JS' => "BX.CrmUIGridExtension.processActionChange('{$gridManagerID}', 'mark_as_opened')"))
				)
			)
		);
		//endregion
	}

	$controlPanel['GROUPS'][0]['ITEMS'][] = array(
		"TYPE" => \Bitrix\Main\Grid\Panel\Types::DROPDOWN,
		"ID" => "action_button_{$prefix}",
		"NAME" => "action_button_{$prefix}",
		"ITEMS" => $actionList
	);

	$controlPanel['GROUPS'][0]['ITEMS'][] = $snippet->getForAllCheckbox();
}
//endregion

if($arResult['ENABLE_TOOLBAR'])
{
	$addButton =array(
		'TEXT' => GetMessage('CRM_DEAL_LIST_ADD_SHORT'),
		'TITLE' => GetMessage('CRM_DEAL_LIST_ADD'),
		'LINK' => $arResult['PATH_TO_DEAL_ADD'],
		'ICON' => 'btn-new'
	);

	if($arResult['ADD_EVENT_NAME'] !== '')
	{
		$addButton['ONCLICK'] = "BX.onCustomEvent(window, '{$arResult['ADD_EVENT_NAME']}')";
	}

	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		'',
		array(
			'TOOLBAR_ID' => strtolower($arResult['GRID_ID']).'_toolbar',
			'BUTTONS' => array($addButton)
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
}

$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.grid',
	'titleflex',
	array(
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'SORT' => $arResult['SORT'],
		'SORT_VARS' => $arResult['SORT_VARS'],
		'ROWS' => $arResult['GRID_DATA'],
		'FORM_ID' => $arResult['FORM_ID'],
		'TAB_ID' => $arResult['TAB_ID'],
		'AJAX_ID' => $arResult['AJAX_ID'],
		'AJAX_OPTION_JUMP' => $arResult['AJAX_OPTION_JUMP'],
		'AJAX_OPTION_HISTORY' => $arResult['AJAX_OPTION_HISTORY'],
		'AJAX_LOADER' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null,
		'FILTER' => $arResult['FILTER'],
		'FILTER_PRESETS' => $arResult['FILTER_PRESETS'],
		'ENABLE_LIVE_SEARCH' => true,
		'ACTION_PANEL' => $controlPanel,
		'PAGINATION' => isset($arResult['PAGINATION']) && is_array($arResult['PAGINATION'])
			? $arResult['PAGINATION'] : array(),
		'ENABLE_ROW_COUNT_LOADER' => true,
		'PRESERVE_HISTORY' => $arResult['PRESERVE_HISTORY'],
		'NAVIGATION_BAR' => array(
			'ITEMS' => array(
				array(
					//'icon' => 'table',
					'id' => 'list',
					'name' => GetMessage('CRM_DEAL_LIST_FILTER_NAV_BUTTON_LIST'),
					'active' => true,
					'url' => isset($arResult['PATH_TO_DEAL_CATEGORY'])
						? $arResult['PATH_TO_DEAL_CATEGORY']
						: $arResult['PATH_TO_DEAL_LIST']
				),
				array(
					//'icon' => 'kanban',
					'id' => 'kanban',
					'name' => GetMessage('CRM_DEAL_LIST_FILTER_NAV_BUTTON_KANBAN'),
					'active' => false,
					'url' => $arParams['PATH_TO_DEAL_KANBAN']
				),
				array(
					//'icon' => 'chart',
					'id' => 'widget',
					'name' => GetMessage('CRM_DEAL_LIST_FILTER_NAV_BUTTON_WIDGET'),
					'active' => false,
					'url' => isset($arResult['PATH_TO_DEAL_WIDGETCATEGORY'])
						? $arResult['PATH_TO_DEAL_WIDGETCATEGORY'] : $arResult['PATH_TO_DEAL_WIDGET']
				)
			),
			'BINDING' => array(
				'category' => 'crm.navigation',
				'name' => 'index',
				'key' => strtolower($arResult['NAVIGATION_CONTEXT_ID'])
			)
		),
		'IS_EXTERNAL_FILTER' => $arResult['IS_EXTERNAL_FILTER'],
		'EXTENSION' => array(
			'ID' => $gridManagerID,
			'CONFIG' => array(
				'ownerTypeName' => CCrmOwnerType::DealName,
				'gridId' => $arResult['GRID_ID'],
				'activityEditorId' => $activityEditorID,
				'activityServiceUrl' => '/bitrix/components/bitrix/crm.activity.editor/ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'taskCreateUrl'=> isset($arResult['TASK_CREATE_URL']) ? $arResult['TASK_CREATE_URL'] : '',
				'serviceUrl' => '/bitrix/components/bitrix/crm.deal.list/list.ajax.php?siteID='.SITE_ID.'&'.bitrix_sessid_get(),
				'loaderData' => isset($arParams['AJAX_LOADER']) ? $arParams['AJAX_LOADER'] : null
			),
			'MESSAGES' => array(
				'deletionDialogTitle' => GetMessage('CRM_DEAL_DELETE_TITLE'),
				'deletionDialogMessage' => GetMessage('CRM_DEAL_DELETE_CONFIRM'),
				'deletionDialogButtonTitle' => GetMessage('CRM_DEAL_DELETE')
			)
		),
		'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE']
	),
	$component
);
?><script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmLongRunningProcessDialog.messages =
			{
				startButton: "<?=GetMessageJS('CRM_DEAL_LRP_DLG_BTN_START')?>",
				stopButton: "<?=GetMessageJS('CRM_DEAL_LRP_DLG_BTN_STOP')?>",
				closeButton: "<?=GetMessageJS('CRM_DEAL_LRP_DLG_BTN_CLOSE')?>",
				wait: "<?=GetMessageJS('CRM_DEAL_LRP_DLG_WAIT')?>",
				requestError: "<?=GetMessageJS('CRM_DEAL_LRP_DLG_REQUEST_ERR')?>"
			};
		}
	);
</script><?
if(!$isInternal):
?><script type="text/javascript">
	BX.ready(
			function()
			{
				BX.CrmActivityEditor.items['<?= CUtil::JSEscape($activityEditorID)?>'].addActivityChangeHandler(
						function()
						{
							BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
						}
				);
				BX.namespace('BX.Crm.Activity');
				if(typeof BX.Crm.Activity.Planner !== 'undefined')
				{
					BX.Crm.Activity.Planner.Manager.setCallback('onAfterActivitySave', function()
					{
						BX.Main.gridManager.reload('<?= CUtil::JSEscape($arResult['GRID_ID'])?>');
					});
				}
			}
	);
</script>
<?endif;?>
<?if($arResult['CONVERSION_PERMITTED'] && $arResult['CAN_CONVERT'] && isset($arResult['CONVERSION_CONFIG'])):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				BX.CrmDealConversionScheme.messages =
					<?=CUtil::PhpToJSObject(\Bitrix\Crm\Conversion\DealConversionScheme::getJavaScriptDescriptions(false))?>;

				BX.CrmDealConverter.messages =
				{
					accessDenied: "<?=GetMessageJS("CRM_DEAL_CONV_ACCESS_DENIED")?>",
					generalError: "<?=GetMessageJS("CRM_DEAL_CONV_GENERAL_ERROR")?>",
					dialogTitle: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_TITLE")?>",
					syncEditorLegend: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_SYNC_LEGEND")?>",
					syncEditorFieldListTitle: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_SYNC_FILED_LIST_TITLE")?>",
					syncEditorEntityListTitle: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_SYNC_ENTITY_LIST_TITLE")?>",
					continueButton: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_CONTINUE_BTN")?>",
					cancelButton: "<?=GetMessageJS("CRM_DEAL_CONV_DIALOG_CANCEL_BTN")?>"
				};
				BX.CrmDealConverter.permissions =
				{
					invoice: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_INVOICE'])?>,
					quote: <?=CUtil::PhpToJSObject($arResult['CAN_CONVERT_TO_QUOTE'])?>
				};
				BX.CrmDealConverter.settings =
				{
					serviceUrl: "<?='/bitrix/components/bitrix/crm.deal.show/ajax.php?action=convert&'.bitrix_sessid_get()?>",
					config: <?=CUtil::PhpToJSObject($arResult['CONVERSION_CONFIG']->toJavaScript())?>
				};
				BX.CrmEntityType.setCaptions(<?=CUtil::PhpToJSObject(CCrmOwnerType::GetJavascriptDescriptions())?>);
			}
		);
	</script>
<?endif;?>
<?if($arResult['NEED_FOR_REBUILD_SEARCH_CONTENT']):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				if(BX.AutorunProcessPanel.isExists("rebuildDealSearch"))
				{
					return;
				}

				BX.AutorunProcessManager.messages =
					{
						title: "<?=GetMessageJS('CRM_DEAL_REBUILD_SEARCH_CONTENT_DLG_TITLE')?>",
						stateTemplate: "<?=GetMessageJS('CRM_REBUILD_SEARCH_CONTENT_STATE')?>"
					};
				var manager = BX.AutorunProcessManager.create("rebuildDealSearch",
					{
						serviceUrl: "<?='/bitrix/components/bitrix/crm.deal.list/list.ajax.php?'.bitrix_sessid_get()?>",
						actionName: "REBUILD_SEARCH_CONTENT",
						container: "rebuildDealSearchWrapper",
						enableLayout: true
					}
				);
				manager.runAfter(100);
			}
		);
	</script>
<?endif;?>
<?if($arResult['NEED_FOR_REBUILD_DEAL_SEMANTICS']):?>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				var builderPanel = BX.CrmLongRunningProcessPanel.create(
					"rebuildDealSemantics",
					{
						"containerId": "rebuildMessageWrapper",
						"prefix": "",
						"active": true,
						"message": "<?=GetMessageJS('CRM_DEAL_REBUILD_SEMANTICS')?>",
						"manager":
						{
							dialogTitle: "<?=GetMessageJS("CRM_DEAL_REBUILD_SEMANTICS_DLG_TITLE")?>",
							dialogSummary: "<?=GetMessageJS("CRM_DEAL_REBUILD_SEMANTICS_DLG_SUMMARY")?>",
							actionName: "REBUILD_SEMANTICS",
							serviceUrl: "<?='/bitrix/components/bitrix/crm.deal.list/list.ajax.php?'.bitrix_sessid_get()?>"
						}
					}
				);
				builderPanel.layout();
			}
		);
	</script>
<?endif;?>
<?if($arResult['NEED_FOR_REBUILD_DEAL_ATTRS']):?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			var link = BX("rebuildDealAttrsLink");
			if(link)
			{
				BX.bind(
					link,
					"click",
					function(e)
					{
						var msg = BX("rebuildDealAttrsMsg");
						if(msg)
						{
							msg.style.display = "none";
						}
					}
				);
			}
		}
	);
</script>
<?endif;?>