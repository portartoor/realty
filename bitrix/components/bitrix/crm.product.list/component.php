<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

global $USER, $DB, $APPLICATION;

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['CAN_DELETE'] = $arResult['CAN_EDIT'] = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');

$arParams['PATH_TO_PRODUCT_LIST'] = CrmCheckPath('PATH_TO_PRODUCT_LIST', $arParams['PATH_TO_PRODUCT_LIST'], $APPLICATION->GetCurPage().'?section_id=#section_id#');
$arParams['PATH_TO_PRODUCT_SHOW'] = CrmCheckPath('PATH_TO_PRODUCT_SHOW', $arParams['PATH_TO_PRODUCT_SHOW'], $APPLICATION->GetCurPage().'?product_id=#product_id#&show');
$arParams['PATH_TO_PRODUCT_EDIT'] = CrmCheckPath('PATH_TO_PRODUCT_EDIT', $arParams['PATH_TO_PRODUCT_EDIT'], $APPLICATION->GetCurPage().'?product_id=#product_id#&edit');

$arFilter = $arSort = array();
$bInternal = false;
$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';

if(isset($arResult['PRODUCT_ID']))
{
	unset($arResult['PRODUCT_ID']);
}

if (!empty($arParams['INTERNAL_FILTER']) || $arResult['GADGET'] == 'Y')
{
	$bInternal = true;
}

$arResult['INTERNAL'] = $bInternal;
if (!empty($arParams['INTERNAL_FILTER']) && is_array($arParams['INTERNAL_FILTER']))
{
	$arParams['GRID_ID_SUFFIX'] = $this->GetParent() !== null ? $this->GetParent()->GetName() : '';
	$arFilter = $arParams['INTERNAL_FILTER'];
}

if (!empty($arParams['INTERNAL_SORT']) && is_array($arParams['INTERNAL_SORT']))
{
	$arSort = $arParams['INTERNAL_SORT'];
}

if (!isset($arParams['PRODUCT_COUNT']))
{
	$arParams['PRODUCT_COUNT'] = 20;
}

$sectionID = isset($_GET['LIST_SECTION_ID']) ? intval($_GET['LIST_SECTION_ID']) : (isset($arParams['~SECTION_ID']) ? intval($arParams['~SECTION_ID']) : 0);

$arResult['GRID_ID'] = 'CRM_PRODUCT_LIST'.($bInternal ? '_'.$arParams['GRID_ID_SUFFIX'] : '');
$arResult['FILTER'] = $arResult['FILTER2LOGIC'] = $arResult['FILTER_PRESETS'] = array();

$catalogID = isset($arParams['~CATALOG_ID']) ? intval($arParams['~CATALOG_ID']) : 0;
if($catalogID <= 0)
{
	$catalogID = CCrmCatalog::EnsureDefaultExists();
}

$arResult['SECTIONS'] = array();

//$arCatalogs = array();
//$arCatalogs[''] = GetMessage('CRM_NOT_SELECTED');
//$obRes = CCrmCatalog::GetList(array('NAME'), array(), false, false, array('ID', 'NAME'));
//while($arCatalog = $obRes->GetNext())
//{
//	$arCatalogs[$arCatalog['ID']] = $arCatalog['NAME'];
//}

$arSections = array();
$arSections[''] = GetMessage('CRM_NOT_SELECTED');
$rsSections = CIBlockSection::GetList(
	array('left_margin' => 'asc'),
	array(
		'IBLOCK_ID' => $catalogID,
		'GLOBAL_ACTIVE' => 'Y',
		'CHECK_PERMISSIONS' => 'N'
	)
);

while($arSection = $rsSections->GetNext())
{
	$arResult['SECTIONS'][$arSection['ID']] =
		array(
			'ID' => $arSection['ID'],
			'NAME' => $arSection['~NAME'],
			'LIST_URL' => str_replace(
				'#section_id#',
				$arSection['ID'],
				$arParams['PATH_TO_PRODUCT_LIST']
			)
		);

	$arSections[$arSection['ID']] = str_repeat(' . ', $arSection['DEPTH_LEVEL']).$arSection['~NAME'];
}

$arResult['FILTER'] =
	array(
		array(
			'id' => 'ID',
			'name' => GetMessage('CRM_COLUMN_ID'),
			'type' => 'string',
			'default' => true
		),
		array(
			'id' => 'NAME',
			'name' => GetMessage('CRM_COLUMN_NAME'),
			'type' => 'string',
			'default' => true
		),
// Catalog ID is not supported - section list can not be changed
//		array(
//			'id' => 'CATALOG_ID',
//			'name' => GetMessage('CRM_COLUMN_CATALOG_ID'),
//			'type' => 'list',
//			'items' => $arCatalogs
//		),
		array(
			'id' => 'LIST_SECTION_ID',
			'name' => GetMessage('CRM_COLUMN_SECTION'),
			'type' => 'list',
			'default' => true,
			'items' => $arSections,
			//'value' => $sectionID,
			//'filtered' => $sectionID > 0
		),
		array(
			'id' => 'ACTIVE',
			'name' => GetMessage('CRM_COLUMN_ACTIVE'),
			'type' => 'list',
			'items' => array(
				'' => GetMessage('CRM_NOT_SELECTED'),
				'Y' => GetMessage('MAIN_YES'),
				'N' => GetMessage('MAIN_NO')
			)
		),
		array(
			'id' => 'DESCRIPTION',
			'name' => GetMessage('CRM_COLUMN_DESCRIPTION')
		)
	);

	$arResult['FILTER_PRESETS'] = array();
//}

// Headers initialization -->
$arResult['HEADERS'] = array(
	array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID'), 'sort' => 'id', 'default' => false, 'editable' => false),
	array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_NAME'), 'sort' => 'name', 'default' => true, 'editable' => true, 'params' => array('size' => 45)),
	array('id' => 'PRICE', 'name' => GetMessage('CRM_COLUMN_PRICE'), 'sort' => 'price', 'default' => true, 'editable' => true),
	array('id' => 'SECTION_ID', 'name' => GetMessage('CRM_COLUMN_SECTION'), 'default' => true, 'editable' => array('items'=> CCrmProductHelper::PrepareSectionListItems($catalogID, true)), 'type' => 'list'),
	array('id' => 'SORT', 'name' => GetMessage('CRM_COLUMN_SORT'), 'sort' => 'sort', 'default' => false, 'editable' => true),
	array('id' => 'ACTIVE', 'name' => GetMessage('CRM_COLUMN_ACTIVE'), 'sort' => 'active', 'default' => false, 'editable' => true, 'type' => 'checkbox'),
	array('id' => 'DESCRIPTION', 'name' => GetMessage('CRM_COLUMN_DESCRIPTION'), 'sort' => 'description', 'default' => true, 'editable' => true)
);
// <-- Headers initialization

// Try to extract user action data -->
// We have to extract them before call of CGridOptions::GetFilter() or the custom filter will be corrupted.
$actionData = array(
	'METHOD' => $_SERVER['REQUEST_METHOD'],
	'ACTIVE' => false
);
if(check_bitrix_sessid())
{
	$postAction = 'action_button_'.$arResult['GRID_ID'];
	$getAction = 'action_'.$arResult['GRID_ID'];
	if ($actionData['METHOD'] == 'POST' && isset($_POST[$postAction]))
	{
		$actionData['ACTIVE'] = true;

		$actionData['NAME'] = $_POST[$postAction];
		unset($_POST[$postAction], $_REQUEST[$postAction]);

		$allRows = 'action_all_rows_'.$arResult['GRID_ID'];
		$actionData['ALL_ROWS'] = false;
		if(isset($_POST[$allRows]))
		{
			$actionData['ALL_ROWS'] = $_POST[$allRows] == 'Y';
			unset($_POST[$allRows], $_REQUEST[$allRows]);
		}

		if(isset($_POST['ID']))
		{
			$actionData['ID'] = $_POST['ID'];
			unset($_POST['ID'], $_REQUEST['ID']);
		}

		if(isset($_POST['FIELDS']))
		{
			$actionData['FIELDS'] = $_POST['FIELDS'];
			unset($_POST['FIELDS'], $_REQUEST['FIELDS']);
		}

		$actionData['AJAX_CALL'] = false;
		if(isset($_POST['AJAX_CALL']))
		{
			$actionData['AJAX_CALL']  = true;
			// Must be transfered to main.interface.grid
			//unset($_POST['AJAX_CALL'], $_REQUEST['AJAX_CALL']);
		}
	}
	elseif ($actionData['METHOD'] == 'GET' && isset($_GET[$getAction]))
	{
		$actionData['ACTIVE'] = true;

		$actionData['NAME'] = $_GET[$getAction];
		unset($_GET[$getAction], $_REQUEST[$getAction]);

		if(isset($_GET['ID']))
		{
			$actionData['ID'] = $_GET['ID'];
			unset($_GET['ID'], $_REQUEST['ID']);
		}

		$actionData['AJAX_CALL'] = false;
		if(isset($_GET['AJAX_CALL']))
		{
			$actionData['AJAX_CALL']  = true;
			// Must be transfered to main.interface.grid
			//unset($_GET['AJAX_CALL'], $_REQUEST['AJAX_CALL']);
		}
	}
}
// <-- Try to extract user action data

$arNavParams = array(
	'nPageSize' => $arParams['PRODUCT_COUNT']
);

$arNavigation = CDBResult::GetNavParams($arParams['PRODUCT_COUNT']);
$CGridOptions = new CCrmGridOptions($arResult['GRID_ID']);
$arNavParams = $CGridOptions->GetNavParams($arNavParams);
$arNavParams['bShowAll'] = false;

$arFilter = $CGridOptions->GetFilter($arResult['FILTER']);
$arFilter['CATALOG_ID'] = $catalogID;

if($sectionID > 0
	&& (!isset($arFilter['SECTION_ID']) || $arFilter['SECTION_ID'] !== $sectionID))
{
	$arFilter['SECTION_ID'] = $sectionID;
}

$sectionKey = $arResult['GRID_ID'].'_SECTION_ID';
$lastSectionID = isset($_SESSION[$sectionKey]) ? intval($_SESSION[$sectionKey]) : 0;
if($lastSectionID !== $sectionID)
{
	$_SESSION[$sectionKey] = $sectionID;
}

$arImmutableFilters = array('ID', 'LIST_SECTION_ID', 'CATALOG_ID', 'ACTIVE');
foreach ($arFilter as $k => $v)
{
	if(in_array($k, $arImmutableFilters, true))
	{
		continue;
	}

	if (in_array($k, $arResult['FILTER2LOGIC']))
	{
		// Bugfix #26956 - skip empty values in logical filter
		$v = trim($v);
		if($v !== '')
		{
			$arFilter['?'.$k] = $v;
		}
		unset($arFilter[$k]);
	}
	else if ($k != 'LOGIC')
	{
		$arFilter['%'.$k] = $v;
		unset($arFilter[$k]);
	}
}

// POST & GET actions processing -->
if($actionData['ACTIVE'])
{
	$errorMessage = '';
	if ($actionData['METHOD'] == 'POST')
	{
		if($actionData['NAME'] == 'delete' && $arResult['CAN_DELETE'])
		{
			if ((isset($actionData['ID']) && is_array($actionData['ID'])) || $actionData['ALL_ROWS'])
			{
				$arFilterDel = array();
				if (!$actionData['ALL_ROWS'])
				{
					$arFilterDel = array('ID' => $actionData['ID']);
				}
				else
				{
					// Fix for issue #26628
					$arFilterDel += $arFilter;
				}

				$obRes = CCrmProduct::GetList(array(), $arFilterDel, array('ID'));
				while($arProduct = $obRes->Fetch())
				{
					$DB->StartTransaction();
					if(CCrmProduct::Delete($arProduct['ID']))
					{
						$DB->Commit();
					}
					else
					{
						if($errorMessage !== '')
						{
							$errorMessage.= '<br/>';
						}
						$errorMessage .= CCrmProduct::GetLastError();
						$DB->Rollback();
					}
				}
			}
		}
		elseif($actionData['NAME'] == 'edit' && $arResult['CAN_EDIT'])
		{
			if(isset($actionData['FIELDS']) && is_array($actionData['FIELDS']))
			{
				foreach($actionData['FIELDS'] as $ID => $arSrcData)
				{
					$arUpdateData = array();
					reset($arResult['HEADERS']);
					foreach ($arResult['HEADERS'] as $arHead)
					{
						if (isset($arHead['editable']) && $arHead['editable'] == true && isset($arSrcData[$arHead['id']]))
						{
							$arUpdateData[$arHead['id']] = $arSrcData[$arHead['id']];
						}
					}
					if (!empty($arUpdateData))
					{
						$DB->StartTransaction();
						if(CCrmProduct::Update($ID, $arUpdateData))
						{
							$DB->Commit();
						}
						else
						{
							if($errorMessage !== '')
							{
								$errorMessage.= '<br/>';
							}
							$errorMessage .= CCrmProduct::GetLastError();
						}
					}
				}
			}
		}

		if(strlen($errorMessage) > 0)
		{
			$msgID = uniqid();
			$_SESSION[$msgID] = $errorMessage;
			LocalRedirect('?error='.$msgID);
		}

		if (!$actionData['AJAX_CALL'])
		{
			LocalRedirect(
				CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_PRODUCT_LIST'],
					array('section_id' => $sectionID)
				)
			);
		}
	}
	else//if ($actionData['METHOD'] == 'GET')
	{
		$errorMessage = '';
		if ($actionData['NAME'] == 'delete' && isset($actionData['ID']) && $arResult['CAN_DELETE'])
		{
			$ID = intval($actionData['ID']);

			$DB->StartTransaction();
			if(CCrmProduct::Delete($ID))
			{
				$DB->Commit();
			}
			else
			{
				if($errorMessage !== '')
				{
					$errorMessage.= '<br/>';
				}
				$errorMessage .= CCrmProduct::GetLastError();
				$DB->Rollback();
			}
		}

		if(strlen($errorMessage) > 0)
		{
			$msgID = uniqid();
			$_SESSION[$msgID] = $errorMessage;
			LocalRedirect('?error='.$msgID);
		}

		if (!$actionData['AJAX_CALL'])
		{
			LocalRedirect(
				$bInternal
					? ('?'.$arParams['FORM_ID'].'_active_tab=tab_product')
					: CComponentEngine::MakePathFromTemplate(
						$arParams['PATH_TO_PRODUCT_LIST'], array('section_id' => $sectionID)
					)
			);
		}
	}
}
// <-- POST & GET actions processing

$_arSort = $CGridOptions->GetSorting(
	array(
		'sort' => array('name' => 'asc'),
		'vars' => array('by' => 'by', 'order' => 'order')
	)
);

$arResult['SORT'] = !empty($arSort) ? $arSort : $_arSort['sort'];
$arResult['SORT_VARS'] = $_arSort['vars'];

$arSelect = $CGridOptions->GetVisibleColumns();
if (empty($arSelect))
{
	$arSelect = array();
	foreach ($arResult['HEADERS'] as $arHeader)
	{
		if ($arHeader['default'])
		{
			$arSelect[] = $arHeader['id'];
		}
	}
}

// ID must present in select
if(!in_array('ID', $arSelect))
{
	$arSelect[] = 'ID';
}

//SECTION_ID must present in select
if(!in_array('SECTION_ID', $arSelect))
{
	$arSelect[] = 'SECTION_ID';
}

// Force select currency ID if price selected
if(in_array('PRICE', $arSelect) && !in_array('CURRENCY_ID', $arSelect))
{
	$arSelect[] = 'CURRENCY_ID';
}

$arResult['SELECTED_HEADERS'] = $arSelect;

// PRODUCTS -->
//$obRes = CCrmProduct::GetList($arResult['SORT'], $arFilter, false, $nTopCount > 0 ? array('nTopCount' => $nTopCount) : false, $arSelect);
$obRes = CCrmProduct::GetList($arResult['SORT'], $arFilter, false, $arNavParams, $arSelect);
$obRes->NavStart($arNavParams['nPageSize'], false);


$arResult['PRODUCTS'] = array();
$arResult['PRODUCT_ID_ARY'] = array();

$arResult['PERMS']['ADD']    = true;//!$CCrmProduct->cPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'ADD');
$arResult['PERMS']['WRITE']  = true;//!$CCrmProduct->cPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'WRITE');
$arResult['PERMS']['DELETE'] = true;//!$CCrmProduct->cPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'DELETE');

while($arProduct = $obRes->GetNext())
{
	//$CCrmProduct->cPerms->CheckEnityAccess('PRODUCT', 'WRITE', $arContactAttr[$arProduct['ID']])
	//$CCrmProduct->cPerms->CheckEnityAccess('PRODUCT', 'DELETE', $arContactAttr[$arProduct['ID']])

	$arProduct['DELETE'] = $arProduct['EDIT'] = true;

	$arProduct['PATH_TO_PRODUCT_SHOW'] =
		CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_PRODUCT_SHOW'],
			array('product_id' => $arProduct['ID'])
		);

	$arProduct['PATH_TO_PRODUCT_EDIT'] =
		CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_PRODUCT_EDIT'],
			array('product_id' => $arProduct['ID'])
		);

	$arProduct['PATH_TO_PRODUCT_DELETE'] =
		CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_PRODUCT_LIST'],
				//array('section_id' => isset($arProduct['SECTION_ID']) ? $arProduct['SECTION_ID'] : '0')
				array('section_id' => $sectionID)
			),
			array('action_'.$arResult['GRID_ID'] => 'delete', 'ID' => $arProduct['ID'], 'sessid' => bitrix_sessid())
		);

	$arResult['PRODUCTS'][$arProduct['ID']] = $arProduct;
	$arResult['PRODUCT_ID_ARY'][$arProduct['ID']] = $arProduct['ID'];
}
// <-- PRODUCTS
$arResult['ROWS_COUNT'] = $obRes->SelectedRowsCount();
$arResult['NAV_OBJECT'] = $obRes;

$this->IncludeComponentTemplate();
include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.product/include/nav.php');
return $arResult['ROWS_COUNT'];
