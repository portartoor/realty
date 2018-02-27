<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
	return;

$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$CrmPerms = CCrmPerms::GetCurrentUserPermissions();
if (!CCrmDeal::CheckReadPermission(0, $CrmPerms))
	return;

use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Restriction\RestrictionManager;

$arParams['PATH_TO_DEAL_LIST'] = CrmCheckPath('PATH_TO_DEAL_LIST', $arParams['PATH_TO_DEAL_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath('PATH_TO_DEAL_SHOW', $arParams['PATH_TO_DEAL_SHOW'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&show');
$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');
$arParams['PATH_TO_DEAL_IMPORT'] = CrmCheckPath('PATH_TO_DEAL_IMPORT', $arParams['PATH_TO_DEAL_IMPORT'], $APPLICATION->GetCurPage().'?import');
$arResult['PATH_TO_DEAL_CATEGORY_LIST'] = CrmCheckPath('PATH_TO_DEAL_CATEGORY_LIST', $arParams['PATH_TO_DEAL_CATEGORY_LIST'], COption::GetOptionString('crm', 'path_to_deal_category_list'));
$arResult['PATH_TO_DEAL_CATEGORY_EDIT'] = CrmCheckPath('PATH_TO_DEAL_CATEGORY_EDIT', $arParams['PATH_TO_DEAL_CATEGORY_EDIT'], COption::GetOptionString('crm', 'path_to_deal_category_edit'));

$arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? (int)$arParams['ELEMENT_ID'] : 0;
if($arParams['ELEMENT_ID'] > 0)
{
	$arResult['CATEGORY_ID'] = CCrmDeal::GetCategoryID($arParams['ELEMENT_ID']);
}
else
{
	$arResult['CATEGORY_ID'] = isset($arParams['CATEGORY_ID']) ? (int)$arParams['CATEGORY_ID'] : -1;
}

if (!isset($arParams['TYPE']))
	$arParams['TYPE'] = 'list';

if (isset($_REQUEST['copy']))
	$arParams['TYPE'] = 'copy';

$toolbarID = 'toolbar_deal_'.$arParams['TYPE'];
if($arParams['ELEMENT_ID'] > 0)
{
	$toolbarID .= '_'.$arParams['ELEMENT_ID'];
}
$arResult['TOOLBAR_ID'] = $toolbarID;

$arResult['BUTTONS'] = array();

if ($arParams['TYPE'] == 'list')
{
	$bRead   = CCrmDeal::CheckReadPermission(0, $CrmPerms);
	$bExport = CCrmDeal::CheckExportPermission($CrmPerms);
	$bImport = CCrmDeal::CheckImportPermission($CrmPerms);
	$bAdd    = CCrmDeal::CheckCreatePermission($CrmPerms);
	$bWrite  = CCrmDeal::CheckUpdatePermission(0, $CrmPerms);
	$bDelete = false;
}
else
{
	$bExport = false;
	$bImport = false;

	$bRead   = CCrmDeal::CheckReadPermission($arParams['ELEMENT_ID'], $CrmPerms, $arResult['CATEGORY_ID']);
	$bAdd    = CCrmDeal::CheckCreatePermission($CrmPerms, $arResult['CATEGORY_ID']);
	$bWrite  = CCrmDeal::CheckUpdatePermission($arParams['ELEMENT_ID'], $CrmPerms, $arResult['CATEGORY_ID']);
	$bDelete = CCrmDeal::CheckDeletePermission($arParams['ELEMENT_ID'], $CrmPerms, $arResult['CATEGORY_ID']);
}

if (isset($arParams['DISABLE_EXPORT']) && $arParams['DISABLE_EXPORT'] == 'Y')
{
	$bExport = false;
}

if (!$bRead && !$bAdd && !$bWrite)
	return false;

if($arParams['TYPE'] === 'list')
{
	if ($bAdd)
	{
		$categoryIDs = $arResult['CATEGORY_ID'] >= 0
			? array($arResult['CATEGORY_ID'])
			: CCrmDeal::GetPermittedToCreateCategoryIDs($CrmPerms);

		$categoryCount = count($categoryIDs);
		if($categoryCount > 1)
		{
			$categorySelectorID = 'deal_category';
			$canCreateCategory = CCrmPerms::IsAdmin();
			$categoryCreateUrl = '';
			if($canCreateCategory)
			{
				$restriction = RestrictionManager::getDealCategoryLimitRestriction();
				$limit = $restriction->getQuantityLimit();
				$canCreateCategory = $limit <= 0 || ($limit > DealCategory::getCount());

				if($canCreateCategory)
				{
					$categoryCreateUrl = CComponentEngine::MakePathFromTemplate(
						$arResult['PATH_TO_DEAL_CATEGORY_EDIT'],
						array('category_id' => 0)
					);
				}
			}

			$arResult['CATEGORY_SELECTOR'] = array(
				'ID' => $categorySelectorID,
				'CREATE_URL' => CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_DEAL_EDIT'], array('deal_id' => 0)
				),
				'CAN_CREATE_CATEGORY' => $canCreateCategory,
				'CATEGORY_LIST_URL' => $arResult['PATH_TO_DEAL_CATEGORY_LIST'],
				'CATEGORY_CREATE_URL' => $categoryCreateUrl,
				'INFOS' => DealCategory::getJavaScriptInfos($categoryIDs),
				'MESSAGES' => array('CREATE' => GetMessage('DEAL_ADD_CATEGOTY'))
			);
			$arResult['BUTTONS'][] = array(
				'TEXT' => GetMessage('DEAL_ADD'),
				'TYPE' => 'crm-context-menu',
				'TITLE' => GetMessage('DEAL_ADD_TITLE'),
				'ONCLICK' => "BX.CrmDealCategorySelector.items['{$categorySelectorID}'].openMenu(this)",
				'HIGHLIGHT' => true
			);
		}
		elseif($categoryCount === 1)
		{
			$arResult['BUTTONS'][] = array(
				'TEXT' => GetMessage('DEAL_ADD'),
				'TITLE' => GetMessage('DEAL_ADD_TITLE'),
				'LINK' => CCrmUrlUtil::AddUrlParams(
					CComponentEngine::MakePathFromTemplate(
						$arParams['PATH_TO_DEAL_EDIT'],
						array('deal_id' => 0)
					),
					array('category_id' => $categoryIDs[0])
				),
				'HIGHLIGHT' => true
			);
		}
	}

	if ($bImport)
	{
		$importUrl = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_IMPORT'], array());
		if($arResult['CATEGORY_ID'] >= 0)
		{
			$importUrl = CCrmUrlUtil::AddUrlParams($importUrl, array('category_id' => $arResult['CATEGORY_ID']));
		}

		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('DEAL_IMPORT'),
			'TITLE' => GetMessage('DEAL_IMPORT_TITLE'),
			'LINK' => $importUrl,
			'ICON' => 'btn-import'
		);
	}

	if ($bExport)
	{
		$filterParams = Bitrix\Crm\Widget\Data\DealDataSource::extractDetailsPageUrlParams($_REQUEST);
		$arResult['BUTTONS'][] = array(
			'TITLE' => GetMessage('DEAL_EXPORT_CSV_TITLE'),
			'TEXT' => GetMessage('DEAL_EXPORT_CSV'),
			'LINK' => CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate($APPLICATION->GetCurPage(), array()),
				array_merge(array('type' => 'csv', 'ncc' => '1'), $filterParams)
			),
			'ICON' => 'btn-export'
		);

		$arResult['BUTTONS'][] = array(
			'TITLE' => GetMessage('DEAL_EXPORT_EXCEL_TITLE'),
			'TEXT' => GetMessage('DEAL_EXPORT_EXCEL'),
			'LINK' => CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate($APPLICATION->GetCurPage(), array()),
				array_merge(array('type' => 'excel', 'ncc' => '1'), $filterParams)
			),
			'ICON' => 'btn-export'
		);
	}

	if(count($arResult['BUTTONS']) > 1)
	{
		//Force start new bar after first button
		array_splice($arResult['BUTTONS'], 1, 0, array(array('NEWBAR' => true)));
	}

	$this->IncludeComponentTemplate();
	return;
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show')
	&& !empty($arParams['ELEMENT_ID'])
	&& $bWrite
)
{
	$plannerButton = \Bitrix\Crm\Activity\Planner::getToolbarButton($arParams['ELEMENT_ID'], CCrmOwnerType::Deal);
	if($plannerButton)
	{
		CJSCore::Init(array('crm_activity_planner'));
		$arResult['BUTTONS'][] = $plannerButton;
	}
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show')
	&& !empty($arParams['ELEMENT_ID'])
	&& CCrmDeal::CheckConvertPermission($arParams['ELEMENT_ID'], CCrmOwnerType::Undefined, $CrmPerms))
{
	$schemeID = \Bitrix\Crm\Conversion\DealConversionConfig::getCurrentSchemeID();
	$arResult['BUTTONS'][] = array(
		'TYPE' => 'toolbar-conv-scheme',
		'PARAMS' => array(
			'NAME' => 'deal_converter',
			'ENTITY_TYPE_ID' => CCrmOwnerType::Deal,
			'ENTITY_TYPE_NAME' => CCrmOwnerType::DealName,
			'ENTITY_ID' => $arParams['ELEMENT_ID'],
			'SCHEME_ID' => $schemeID,
			'SCHEME_NAME' => \Bitrix\Crm\Conversion\DealConversionScheme::resolveName($schemeID),
			'SCHEME_DESCRIPTION' => \Bitrix\Crm\Conversion\DealConversionScheme::getDescription($schemeID),
			'IS_PERMITTED' => true,
			'HINT' => array(
				'title' => GetMessage('DEAL_CREATE_ON_BASIS_HINT_TITLE'),
				'content' => GetMessage('DEAL_CREATE_ON_BASIS_HINT_CONTENT'),
				'disabling' => GetMessage('DEAL_CREATE_ON_BASIS_DISABLE_HINT')
			)
		),
		'CODE' => 'convert',
		'TEXT' => GetMessage('DEAL_CREATE_ON_BASIS'),
		'TITLE' => GetMessage('DEAL_CREATE_ON_BASIS_TITLE'),
		'ICON' => $isPermitted ? 'btn-convert' : 'btn-convert-blocked'
	);
}

if (($arParams['TYPE'] == 'show') && $bRead && $arParams['ELEMENT_ID'] > 0)
{
	$subscrTypes = CCrmSonetSubscription::GetRegistationTypes(
		CCrmOwnerType::Deal,
		$arParams['ELEMENT_ID'],
		$currentUserID
	);

	$isResponsible = in_array(CCrmSonetSubscriptionType::Responsibility, $subscrTypes, true);
	if(!$isResponsible)
	{
		$subscriptionID = 'deal_sl_subscribe';
		$arResult['SONET_SUBSCRIBE'] = array(
			'ID' => $subscriptionID,
			'SERVICE_URL' => CComponentEngine::makePathFromTemplate(
				'/bitrix/components/bitrix/crm.deal.edit/ajax.php?site_id=#SITE#&sessid=#SID#',
				array('SID' => bitrix_sessid())
			),
			'ACTION_NAME' => 'ENABLE_SONET_SUBSCRIPTION',
			'RELOAD' => true
		);

		$isObserver = in_array(CCrmSonetSubscriptionType::Observation, $subscrTypes, true);
		$arResult['BUTTONS'][] = array(
			'CODE' => 'sl_unsubscribe',
			'TEXT' => GetMessage('CRM_DEAL_SL_UNSUBSCRIBE'),
			'TITLE' => GetMessage('CRM_DEAL_SL_UNSUBSCRIBE_TITLE'),
			'ONCLICK' => "BX.CrmSonetSubscription.items['{$subscriptionID}'].unsubscribe({$arParams['ELEMENT_ID']}, function(){ var tb = BX.InterfaceToolBar.items['{$toolbarID}']; tb.setButtonVisible('sl_unsubscribe', false); tb.setButtonVisible('sl_subscribe', true); })",
			'ICON' => 'btn-nofollow',
			'VISIBLE' => $isObserver
		);
		$arResult['BUTTONS'][] = array(
			'CODE' => 'sl_subscribe',
			'TEXT' => GetMessage('CRM_DEAL_SL_SUBSCRIBE'),
			'TITLE' => GetMessage('CRM_DEAL_SL_SUBSCRIBE_TITLE'),
			'ONCLICK' => "BX.CrmSonetSubscription.items['{$subscriptionID}'].subscribe({$arParams['ELEMENT_ID']}, function(){ var tb = BX.InterfaceToolBar.items['{$toolbarID}']; tb.setButtonVisible('sl_subscribe', false); tb.setButtonVisible('sl_unsubscribe', true); })",
			'ICON' => 'btn-follow',
			'VISIBLE' => !$isObserver
		);
	}
}

if ($arParams['TYPE'] == 'show' && !empty($arParams['ELEMENT_ID']))
{
	if($bWrite)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('DEAL_EDIT'),
			'TITLE' => GetMessage('DEAL_EDIT_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_EDIT'],
				array(
					'deal_id' => $arParams['ELEMENT_ID']
				)
			),
			'ICON' => 'btn-edit'
		);
	}
}

if ($arParams['TYPE'] == 'edit' && $bRead && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('DEAL_SHOW'),
		'TITLE' => GetMessage('DEAL_SHOW_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_SHOW'],
			array(
				'deal_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-view'
	);
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $bAdd
	&& !empty($arParams['ELEMENT_ID']) && !isset($_REQUEST['copy']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('DEAL_COPY'),
		'TITLE' => GetMessage('DEAL_COPY_TITLE'),
		'LINK' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_EDIT'],
			array(
				'deal_id' => $arParams['ELEMENT_ID']
			)),
			array('copy' => 1)
		),
		'ICON' => 'btn-copy'
	);
}

$qty = count($arResult['BUTTONS']);

if (!empty($arResult['BUTTONS']) && $arParams['TYPE'] == 'edit' && empty($arParams['ELEMENT_ID']))
	$arResult['BUTTONS'][] = array('SEPARATOR' => true);
elseif ($arParams['TYPE'] == 'show' && $qty > 1)
	$arResult['BUTTONS'][] = array('NEWBAR' => true);
elseif ($qty >= 3)
	$arResult['BUTTONS'][] = array('NEWBAR' => true);

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $bDelete && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('DEAL_DELETE'),
		'TITLE' => GetMessage('DEAL_DELETE_TITLE'),
		'LINK' => "javascript:deal_delete('".GetMessage('DEAL_DELETE_DLG_TITLE')."', '".GetMessage('DEAL_DELETE_DLG_MESSAGE')."', '".GetMessage('DEAL_DELETE_DLG_BTNTITLE')."', '".CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_EDIT'],
			array(
				'deal_id' => $arParams['ELEMENT_ID']
			)),
			array('delete' => '', 'sessid' => bitrix_sessid())
		)."')",
		'ICON' => 'btn-delete'
	);
}

if ($bAdd)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('DEAL_ADD'),
		'TITLE' => GetMessage('DEAL_ADD_TITLE'),
		'LINK' => CCrmUrlUtil::AddUrlParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_DEAL_EDIT'],
				array('deal_id' => 0)
			),
			array('category_id' => $arResult['CATEGORY_ID'])
		),
		'TARGET' => '_blank',
		'ICON' => 'btn-new'
	);
}

$this->IncludeComponentTemplate();
?>
