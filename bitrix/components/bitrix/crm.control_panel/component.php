<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if(!CCrmPerms::IsAccessEnabled())
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

// Preparing of URL templates -->
$arParams['PATH_TO_COMPANY_LIST'] = CrmCheckPath(
	'PATH_TO_COMPANY_LIST',
	isset($arParams['PATH_TO_COMPANY_LIST']) ? $arParams['PATH_TO_COMPANY_LIST'] : '',
	'#SITE_DIR#crm/company/'
);

$arParams['PATH_TO_COMPANY_EDIT'] = CrmCheckPath(
	'PATH_TO_COMPANY_EDIT',
	isset($arParams['PATH_TO_COMPANY_EDIT']) ? $arParams['PATH_TO_COMPANY_EDIT'] : '',
	'#SITE_DIR#crm/company/edit/#company_id#/'
);

$arParams['PATH_TO_CONTACT_LIST'] = CrmCheckPath(
	'PATH_TO_CONTACT_LIST',
	isset($arParams['PATH_TO_CONTACT_LIST']) ? $arParams['PATH_TO_CONTACT_LIST'] : '',
	'#SITE_DIR#crm/contact/'
);

$arParams['PATH_TO_CONTACT_EDIT'] = CrmCheckPath(
	'PATH_TO_CONTACT_EDIT',
	isset($arParams['PATH_TO_CONTACT_EDIT']) ? $arParams['PATH_TO_CONTACT_EDIT'] : '',
	'#SITE_DIR#crm/contact/edit/#contact_id#/'
);

$arParams['PATH_TO_DEAL_LIST'] = CrmCheckPath(
	'PATH_TO_DEAL_LIST',
	isset($arParams['PATH_TO_DEAL_LIST']) ? $arParams['PATH_TO_DEAL_LIST'] : '',
	'#SITE_DIR#crm/deal/'
);

$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath(
	'PATH_TO_DEAL_EDIT',
	isset($arParams['PATH_TO_DEAL_EDIT']) ? $arParams['PATH_TO_DEAL_EDIT'] : '',
	'#SITE_DIR#crm/deal/edit/#deal_id#/'
);

$arParams['PATH_TO_LEAD_LIST'] = CrmCheckPath(
	'PATH_TO_LEAD_LIST',
	isset($arParams['PATH_TO_LEAD_LIST']) ? $arParams['PATH_TO_LEAD_LIST'] : '',
	'#SITE_DIR#crm/lead/'
);

$arParams['PATH_TO_LEAD_EDIT'] = CrmCheckPath(
	'PATH_TO_LEAD_EDIT',
	isset($arParams['PATH_TO_LEAD_EDIT']) ? $arParams['PATH_TO_LEAD_EDIT'] : '',
	'#SITE_DIR#crm/lead/edit/#lead_id#/'
);

$arParams['PATH_TO_REPORT_LIST'] = CrmCheckPath(
	'PATH_TO_REPORT_LIST',
	isset($arParams['PATH_TO_REPORT_LIST']) ? $arParams['PATH_TO_REPORT_LIST'] : '',
	'#SITE_DIR#crm/reports/report/'
);

$arParams['PATH_TO_DEAL_FUNNEL'] = CrmCheckPath(
	'PATH_TO_DEAL_FUNNEL',
	isset($arParams['PATH_TO_DEAL_FUNNEL']) ? $arParams['PATH_TO_DEAL_FUNNEL'] : '',
	'#SITE_DIR#crm/reports/'
);

$arParams['PATH_TO_EVENT_LIST'] = CrmCheckPath(
	'PATH_TO_EVENT_LIST',
	isset($arParams['PATH_TO_EVENT_LIST']) ? $arParams['PATH_TO_EVENT_LIST'] : '',
	'#SITE_DIR#crm/events/'
);

$arParams['PATH_TO_PRODUCT_LIST'] = CrmCheckPath(
	'PATH_TO_PRODUCT_LIST',
	isset($arParams['PATH_TO_PRODUCT_LIST']) ? $arParams['PATH_TO_PRODUCT_LIST'] : '',
	'#SITE_DIR#crm/product/'
);

$arParams['PATH_TO_SETTINGS'] = CrmCheckPath(
	'PATH_TO_SETTINGS',
	isset($arParams['PATH_TO_SETTINGS']) ? $arParams['PATH_TO_SETTINGS'] : '',
	'#SITE_DIR#crm/configs/'
);

$arParams['PATH_TO_SEARCH_PAGE'] = $arResult['PATH_TO_SEARCH_PAGE'] = CrmCheckPath(
	'PATH_TO_SEARCH_PAGE',
	isset($arParams['PATH_TO_SEARCH_PAGE']) ? $arParams['PATH_TO_SEARCH_PAGE'] : '',
	'#SITE_DIR#search/index.php?where=crm'
);
//<-- Preparing of URL templates

$arResult['ACTIVE_ITEM_ID'] = isset($arParams['ACTIVE_ITEM_ID']) ? $arParams['ACTIVE_ITEM_ID'] : '';
$arResult['ENABLE_SEARCH'] = isset($arParams['ENABLE_SEARCH']) && is_bool($arParams['ENABLE_SEARCH']) ? $arParams['ENABLE_SEARCH'] : true ;
$arResult['SEARCH_PAGE_URL'] = CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_SEARCH_PAGE']);

$arResult['ID'] = isset($arParams['ID']) ? $arParams['ID'] : '';
if($arResult['ID'] === '')
{
	$arResult['ID'] = 'DEFAULT';
}

$isAdmin = CCrmPerms::IsAdmin();
$userPermissions = CCrmPerms::GetCurrentUserPermissions();

// Prepere standard items -->
$counter = new CCrmUserCounter(CCrmPerms::GetCurrentUserID(), CCrmUserCounter::CurrentActivies);
$stdItems = array(
	'MY_ACTIVITY' => array(
		'ID' => 'MY_ACTIVITY',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_MY_ACTIVITY'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_MY_ACTIVITY_TITLE'),
		'URL' => CrmCheckPath(
			'PATH_TO_ACTIVITY_LIST',
			isset($arParams['PATH_TO_ACTIVITY_LIST']) ? $arParams['PATH_TO_ACTIVITY_LIST'] : '',
			'/crm/activity/'
		),
		'COUNTER' => $counter->GetValue(),
		'ICON' => 'activity'
	)
);

if($isAdmin || !$userPermissions->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'READ'))
{
	$counter = new CCrmUserCounter(CCrmPerms::GetCurrentUserID(), CCrmUserCounter::CurrentContactActivies);
	$stdItems['CONTACT'] = array(
		'ID' => 'CONTACT',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_CONTACT'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_CONTACT_TITLE'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_LIST']),
		'ICON' => 'contact',
		'COUNTER' => $counter->GetValue($arResult['ACTIVE_ITEM_ID'] === 'CONTACT'),
		'ACTIONS' => array(
			array(
				'ID' => 'CREATE',
				'URL' => CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_CONTACT_EDIT'],
					array('contact_id' => 0)
				)
			)
		)
	);
}

if($isAdmin || !$userPermissions->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'READ'))
{
	$counter = new CCrmUserCounter(CCrmPerms::GetCurrentUserID(), CCrmUserCounter::CurrentCompanyActivies);
	$stdItems['COMPANY'] = array(
		'ID' => 'COMPANY',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_COMPANY'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_COMPANY_TITLE'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_LIST']),
		'ICON' => 'company',
		'COUNTER' => $counter->GetValue($arResult['ACTIVE_ITEM_ID'] === 'COMPANY'),
		'ACTIONS' => array(
			array(
				'ID' => 'CREATE',
				'URL' => CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_COMPANY_EDIT'],
					array('company_id' => 0)
				)
			)
		)
	);
}


if($isAdmin || !$userPermissions->HavePerm('DEAL', BX_CRM_PERM_NONE, 'READ'))
{
	$counter = new CCrmUserCounter(CCrmPerms::GetCurrentUserID(), CCrmUserCounter::CurrentDealActivies);
	$stdItems['DEAL'] = array(
		'ID' => 'DEAL',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_DEAL'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_DEAL_TITLE'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_LIST']),
		'ICON' => 'deal',
		'COUNTER' => $counter->GetValue($arResult['ACTIVE_ITEM_ID'] === 'DEAL'),
		'ACTIONS' => array(
			array(
				'ID' => 'CREATE',
				'URL' =>  CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_DEAL_EDIT'],
					array('deal_id' => 0)
				)
			)
		)
	);
}

if($isAdmin || !$userPermissions->HavePerm('LEAD', BX_CRM_PERM_NONE, 'READ'))
{
	$counter = new CCrmUserCounter(CCrmPerms::GetCurrentUserID(), CCrmUserCounter::CurrentLeadActivies);
	$stdItems['LEAD'] = array(
		'ID' => 'LEAD',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_LEAD'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_LEAD_TITLE'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_LIST']),
		'ICON' => 'lead',
		'COUNTER' => $counter->GetValue($arResult['ACTIVE_ITEM_ID'] === 'LEAD'),
		'ACTIONS' => array(
			array(
				'ID' => 'CREATE',
				'URL' => CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_LEAD_EDIT'],
					array('lead_id' => 0)
				)
			)
		)
	);
}

if(IsModuleInstalled('report'))
{
	$stdItems['REPORT'] = array(
		'ID' => 'REPORT',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_REPORT'),
		'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_REPORT_TITLE'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_REPORT_LIST']),
		'ICON' => 'report'
	);
}

$stdAdditionalItems = array();
if($isAdmin || !$userPermissions->HavePerm('DEAL', BX_CRM_PERM_NONE, 'READ'))
{
	$stdAdditionalItems[] = array(
		'ID' => 'FUNNEL',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_FUNNEL'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_FUNNEL']),
		'ICON' => 'funnel'
	);
}

$stdAdditionalItems[] = array(
	'ID' => 'EVENT',
	'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_EVENT'),
	'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_EVENT_LIST']),
	'ICON' => 'event'
);

if($isAdmin || !$userPermissions->HavePerm('CONFIG', BX_CRM_PERM_NONE, 'READ'))
{
	$stdAdditionalItems[] =array(
		'ID' => 'CATALOGUE',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_CATALOGUE_2'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_PRODUCT_LIST']),
		'ICON' => 'catalog'
	);
}

if($isAdmin || $userPermissions->IsAccessEnabled())
{
	$stdAdditionalItems[] =array(
		'ID' => 'SETTINGS',
		'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_SETTINGS'),
		'URL' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_SETTINGS']),
		'ICON' => 'settings'
	);
}

$stdItems['MORE'] = array(
	'ID' => 'MORE',
	'NAME' => GetMessage('CRM_CTRL_PANEL_ITEM_MORE'),
	'TITLE' => GetMessage('CRM_CTRL_PANEL_ITEM_MORE_TITLE'),
	'ICON' => 'more',
	'CHILD_ITEMS' => $stdAdditionalItems
);
unset($stdAdditionalItems);
// <-- Prepere standard items

$items = array();
$itemInfos = isset($arParams['ITEMS']) && is_array($arParams['ITEMS']) ? $arParams['ITEMS'] : array();
if(empty($itemInfos))
{
	$items = array_values($stdItems);
}
else
{
	foreach($itemInfos as &$itemInfo)
	{
		$itemID = isset($itemInfo['ID']) ? strtoupper($itemInfo['ID']) : '';
		if(isset($stdItems[$itemID]))
		{
			$item = $stdItems[$itemID];
			$items[] = $item;
		}
		else
		{
			$items[] = array(
				'ID' => $itemID,
				'NAME' => isset($itemInfo['NAME']) ? $itemInfo['NAME'] : $itemID,
				'URL' => isset($itemInfo['URL']) ? $itemInfo['URL'] : '',
				'COUNTER' => isset($itemInfo['COUNTER']) ? intval($itemInfo['COUNTER']) : 0,
				'ICON' => isset($itemInfo['ICON']) ? $itemInfo['ICON'] : ''
			);
		}
	}
	unset($itemInfo);
}

$arResult['ITEMS'] = &$items;
unset($items);

$this->IncludeComponentTemplate();
