<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
	return;

$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$CrmPerms = CCrmPerms::GetCurrentUserPermissions();
if ($CrmPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE))
	return;

$arParams['PATH_TO_COMPANY_LIST'] = CrmCheckPath('PATH_TO_COMPANY_LIST', $arParams['PATH_TO_COMPANY_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_COMPANY_EDIT'] = CrmCheckPath('PATH_TO_COMPANY_EDIT', $arParams['PATH_TO_COMPANY_EDIT'], $APPLICATION->GetCurPage().'?company_id=#company_id#&edit');
$arParams['PATH_TO_COMPANY_IMPORT'] = CrmCheckPath('PATH_TO_COMPANY_IMPORT', $arParams['PATH_TO_COMPANY_IMPORT'], $APPLICATION->GetCurPage().'?import');
$arParams['PATH_TO_COMPANY_PORTRAIT'] = CrmCheckPath('PATH_TO_COMPANY_PORTRAIT', $arParams['PATH_TO_COMPANY_PORTRAIT'], $APPLICATION->GetCurPage().'?portrait');
$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');
$arParams['PATH_TO_CONTACT_EDIT'] = CrmCheckPath('PATH_TO_CONTACT_EDIT', $arParams['PATH_TO_CONTACT_EDIT'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&edit');

$arParams['ELEMENT_ID'] = isset($arParams['ELEMENT_ID']) ? intval($arParams['ELEMENT_ID']) : 0;

$arResult['MYCOMPANY_MODE'] = (isset($arParams['MYCOMPANY_MODE']) && $arParams['MYCOMPANY_MODE'] === 'Y') ? 'Y' : 'N';
$isMyCompanyMode = ($arResult['MYCOMPANY_MODE'] === 'Y');

if(!isset($arParams['TYPE']))
	$arParams['TYPE'] = 'list';

if (isset($_REQUEST['copy']))
	$arParams['TYPE'] = 'copy';

$toolbarID = 'toolbar_company_'.$arParams['TYPE'];
if($arParams['ELEMENT_ID'] > 0)
{
	$toolbarID .= '_'.$arParams['ELEMENT_ID'];
}
$arResult['TOOLBAR_ID'] = $toolbarID;

$arResult['BUTTONS'] = array();

if ($arParams['TYPE'] == 'list')
{
	$bRead   = CCrmCompany::CheckReadPermission(0, $CrmPerms);
	$bExport = CCrmCompany::CheckExportPermission($CrmPerms);
	$bImport = CCrmCompany::CheckImportPermission($CrmPerms);
	$bAdd    = CCrmCompany::CheckCreatePermission($CrmPerms);
	$bWrite  = CCrmCompany::CheckUpdatePermission(0, $CrmPerms);
	$bDelete = false;

	$bDedupe = $bRead && $bWrite && CCrmCompany::CheckDeletePermission(0, $CrmPerms);
}
else
{
	$bExport = false;
	$bImport = false;
	$bDedupe = false;

	$bRead   = CCrmCompany::CheckReadPermission($arParams['ELEMENT_ID'], $CrmPerms);
	$bAdd    = CCrmCompany::CheckCreatePermission($CrmPerms);
	$bWrite  = CCrmCompany::CheckUpdatePermission($arParams['ELEMENT_ID'], $CrmPerms);
	$bDelete = CCrmCompany::CheckDeletePermission($arParams['ELEMENT_ID'], $CrmPerms);
}

if($arParams['TYPE'] === 'list')
{
	if($bAdd)
	{
		$link = CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_COMPANY_EDIT'],
			array('company_id' => 0)
		);
		if ($isMyCompanyMode)
			$link = CHTTP::urlAddParams($link, array('mycompany' => 'y'));
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('COMPANY_ADD'),
			'TITLE' => GetMessage('COMPANY_ADD_TITLE'),
			'LINK' => $link,
			//'ICON' => 'btn-new',
			'HIGHLIGHT' => true
		);
		unset($link);
	}

	if ($bImport)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('COMPANY_IMPORT'),
			'TITLE' => GetMessage('COMPANY_IMPORT_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_IMPORT'], array()),
			'ICON' => 'btn-import'
		);
	}

	if ($bExport)
	{
		$arResult['BUTTONS'][] = array(
			'TITLE' => GetMessage('COMPANY_EXPORT_CSV_TITLE'),
			'TEXT' => GetMessage('COMPANY_EXPORT_CSV'),
			'LINK' => CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate($APPLICATION->GetCurPage(), array()),
				array('type' => 'csv', 'ncc' => '1')
			),
			'ICON' => 'btn-export'
		);

		$arResult['BUTTONS'][] = array(
			'TITLE' => GetMessage('COMPANY_EXPORT_EXCEL_TITLE'),
			'TEXT' => GetMessage('COMPANY_EXPORT_EXCEL'),
			'LINK' => CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate($APPLICATION->GetCurPage(), array()),
				array('type' => 'excel', 'ncc' => '1')
			),
			'ICON' => 'btn-export'
		);
	}

	if ($bDedupe)
	{
		$restriction = \Bitrix\Crm\Restriction\RestrictionManager::getDuplicateControlRestriction();
		if($restriction->hasPermission())
		{
			$arResult['BUTTONS'][] = array(
				'TEXT' => GetMessage('COMPANY_DEDUPE'),
				'TITLE' => GetMessage('COMPANY_DEDUPE_TITLE'),
				'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_DEDUPE'], array())
			);
		}
		else
		{
			$arResult['BUTTONS'][] = array(
				'TEXT' => GetMessage('COMPANY_DEDUPE'),
				'TITLE' => GetMessage('COMPANY_DEDUPE_TITLE'),
				'ONCLICK' => $restriction->preparePopupScript(),
				'MENU_ICON' => 'grid-lock'
			);
		}
	}

	if(count($arResult['BUTTONS']) > 1)
	{
		//Force start new bar after first button
		array_splice($arResult['BUTTONS'], 1, 0, array(array('NEWBAR' => true)));
	}

	$this->IncludeComponentTemplate();
	return;
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show' || $arParams['TYPE'] == 'portrait')
	&& !empty($arParams['ELEMENT_ID'])
	&& $bWrite
)
{
	$plannerButton = \Bitrix\Crm\Activity\Planner::getToolbarButton($arParams['ELEMENT_ID'], CCrmOwnerType::Company);
	if($plannerButton)
	{
		CJSCore::Init(array('crm_activity_planner'));
		$arResult['BUTTONS'][] = $plannerButton;
	}
}

if (($arParams['TYPE'] == 'show') && $bRead && $arParams['ELEMENT_ID'] > 0)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_COMPANY_PORTRAIT'),
		'TITLE' => GetMessage('CRM_COMPANY_PORTRAIT_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_PORTRAIT'],
			array(
				'company_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-portrait',
	);

	$subscrTypes = CCrmSonetSubscription::GetRegistationTypes(
		CCrmOwnerType::Company,
		$arParams['ELEMENT_ID'],
		$currentUserID
	);

	$isResponsible = in_array(CCrmSonetSubscriptionType::Responsibility, $subscrTypes, true);
	if(!$isResponsible)
	{
		$subscriptionID = 'company_sl_subscribe';
		$arResult['SONET_SUBSCRIBE'] = array(
			'ID' => $subscriptionID,
			'SERVICE_URL' => CComponentEngine::makePathFromTemplate(
				'#SITE_DIR#bitrix/components/bitrix/crm.company.edit/ajax.php?site_id=#SITE#&sessid=#SID#',
				array('SID' => bitrix_sessid())
			),
			'ACTION_NAME' => 'ENABLE_SONET_SUBSCRIPTION',
			'RELOAD' => true
		);

		$isObserver = in_array(CCrmSonetSubscriptionType::Observation, $subscrTypes, true);
		$arResult['BUTTONS'][] = array(
			'CODE' => 'sl_unsubscribe',
			'TEXT' => GetMessage('CRM_COMPANY_SL_UNSUBSCRIBE'),
			'TITLE' => GetMessage('CRM_COMPANY_SL_UNSUBSCRIBE_TITLE'),
			'ONCLICK' => "BX.CrmSonetSubscription.items['{$subscriptionID}'].unsubscribe({$arParams['ELEMENT_ID']}, function(){ var tb = BX.InterfaceToolBar.items['{$toolbarID}']; tb.setButtonVisible('sl_unsubscribe', false); tb.setButtonVisible('sl_subscribe', true); })",
			'ICON' => 'btn-nofollow',
			'VISIBLE' => $isObserver
		);
		$arResult['BUTTONS'][] = array(
			'CODE' => 'sl_subscribe',
			'TEXT' => GetMessage('CRM_COMPANY_SL_SUBSCRIBE'),
			'TITLE' => GetMessage('CRM_COMPANY_SL_SUBSCRIBE_TITLE'),
			'ONCLICK' => "BX.CrmSonetSubscription.items['{$subscriptionID}'].subscribe({$arParams['ELEMENT_ID']}, function(){ var tb = BX.InterfaceToolBar.items['{$toolbarID}']; tb.setButtonVisible('sl_subscribe', false); tb.setButtonVisible('sl_unsubscribe', true); })",
			'ICON' => 'btn-follow',
			'VISIBLE' => !$isObserver
		);
	}
}

if ($arParams['TYPE'] == 'show' && $bWrite && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('COMPANY_EDIT'),
		'TITLE' => GetMessage('COMPANY_EDIT_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_EDIT'],
			array(
				'company_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-edit'
	);
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'portrait') && $bRead && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('COMPANY_SHOW'),
		'TITLE' => GetMessage('COMPANY_SHOW_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_SHOW'],
			array(
				'company_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-view'
	);
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $bAdd
	&& !empty($arParams['ELEMENT_ID']) && !isset($_REQUEST['copy']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('COMPANY_COPY'),
		'TITLE' => GetMessage('COMPANY_COPY_TITLE'),
		'LINK' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_EDIT'],
			array(
				'company_id' => $arParams['ELEMENT_ID']
			)),
			array('copy' => 1)
		),
		'ICON' => 'btn-copy'
	);
}

$qty = count($arResult['BUTTONS']);

if (!empty($arResult['BUTTONS']) && $arParams['TYPE'] == 'edit' && empty($arParams['ELEMENT_ID']))
	$arResult['BUTTONS'][] = array('SEPARATOR' => true);
else if ($arParams['TYPE'] == 'show' && $qty > 1)
	$arResult['BUTTONS'][] = array('NEWBAR' => true);
else if ($qty >= 3)
	$arResult['BUTTONS'][] = array('NEWBAR' => true);

if ($bAdd && $arParams['TYPE'] !== 'portrait')
{
	$link = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_COMPANY_EDIT'],
		array('company_id' => 0)
	);
	if ($isMyCompanyMode)
		$link = CHTTP::urlAddParams($link, array('mycompany' => 'y'));
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('COMPANY_ADD'),
		'TITLE' => GetMessage('COMPANY_ADD_TITLE'),
		'LINK' => $link,
		'TARGET' => '_blank',
		'ICON' => 'btn-new'
	);
	unset($link);
}

if ($arParams['TYPE'] == 'show')
{
	if (CCrmDeal::CheckCreatePermission($CrmPerms))
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('COMPANY_ADD_DEAL'),
			'TITLE' => GetMessage('COMPANY_ADD_DEAL_TITLE'),
			'LINK' => CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_EDIT'], array('deal_id' => 0)),
				array('company_id' => $arParams['ELEMENT_ID'])
			),
			'ICONCLASS' => 'btn-add-deal'
		);
	}

	if (!$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'ADD'))
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('COMPANY_ADD_CONTACT'),
			'TITLE' => GetMessage('COMPANY_ADD_CONTACT_TITLE'),
			'LINK' => CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_EDIT'], array('contact_id' => 0)),
				array(
					'company_id' => $arParams['ELEMENT_ID'],
					'backurl' => urlencode($APPLICATION->GetCurPage())
				)
			),
			'ICONCLASS' => 'btn-add-contact'
		);
	}
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $bDelete && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('COMPANY_DELETE'),
		'TITLE' => GetMessage('COMPANY_DELETE_TITLE'),
		'LINK' => "javascript:company_delete('".GetMessage('COMPANY_DELETE_DLG_TITLE')."', '".GetMessage('COMPANY_DELETE_DLG_MESSAGE')."', '".GetMessage('COMPANY_DELETE_DLG_BTNTITLE')."', '".CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_EDIT'],
			array(
				'company_id' => $arParams['ELEMENT_ID']
			)),
			array('delete' => '', 'sessid' => bitrix_sessid())
		)."')",
		'ICON' => 'btn-delete'
	);
}

$this->IncludeComponentTemplate();
?>
