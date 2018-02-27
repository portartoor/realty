<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

if (!CModule::IncludeModule('iblock'))
{
	ShowError(GetMessage('IBLOCK_MODULE_NOT_INSTALLED'));
	return;
}

global $USER, $APPLICATION;

$arParams['PATH_TO_PRODUCT_LIST'] = CrmCheckPath('PATH_TO_PRODUCT_LIST', $arParams['PATH_TO_PRODUCT_LIST'], '?#section_id#');
$arParams['PATH_TO_PRODUCT_SHOW'] = CrmCheckPath('PATH_TO_PRODUCT_SHOW', $arParams['PATH_TO_PRODUCT_SHOW'], '?product_id=#product_id#&show');
$arParams['PATH_TO_PRODUCT_EDIT'] = CrmCheckPath('PATH_TO_PRODUCT_EDIT', $arParams['PATH_TO_PRODUCT_EDIT'], '?product_id=#product_id#&edit');
$arParams['PATH_TO_SECTION_LIST'] = CrmCheckPath('PATH_TO_SECTION_LIST', $arParams['PATH_TO_SECTION_LIST'], '?#section_id#&sections');

if (!isset($arParams['TYPE']))
{
	$arParams['TYPE'] = 'list';
}

$arResult['BUTTONS'] = array();

$sectionID = isset($arParams['SECTION_ID']) ? intval($arParams['SECTION_ID']) : 0;
$productID = isset($arParams['PRODUCT_ID']) ? intval($arParams['PRODUCT_ID']) : 0;

$CrmPerms = new CCrmPerms($USER->GetID());

$productAdd = $sectionAdd = $productEdit = $productDelete = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
$productShow = $sectionShow = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ');

$exists = $productID > 0 && CCrmProduct::Exists($productID);

$arSection = false;

if($sectionShow && $sectionID > 0)
{
	$rsSection = CIBlockSection::GetList(
		array(),
		array(
			//'IBLOCK_ID' => ,
			'ID' => $sectionID,
			'GLOBAL_ACTIVE'=>'Y',
			'CHECK_PERMISSIONS' => 'N'
		),
		false,
		array('ID', 'IBLOCK_SECTION_ID')
	);

	$arSection = $rsSection->Fetch();
}

if($arParams['TYPE'] === 'sections')
{
	if($arSection)
	{
		$parentSectionID = isset($arSection['IBLOCK_SECTION_ID']) ? intval($arSection['IBLOCK_SECTION_ID']) : 0;

		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_MOVE_UP'),
			'TITLE' => GetMessage('CRM_MOVE_UP_TITLE'),
			'LINK' =>  CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_SECTION_LIST'],
				array('section_id' => $parentSectionID)
			),
			'ICON' => 'btn-parent-section',
		);
	}

	if($sectionAdd)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_ADD_PRODUCT_SECTION'),
			'TITLE' => GetMessage('CRM_ADD_PRODUCT_SECTION_TITLE'),
			'LINK' => "javascript:BX.CrmProductSectionManager.getDefault().addSection();",
			'ICON' => 'btn-add-section',
		);
	}

	if($productShow)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_PRODUCT_LIST'),
			'TITLE' => GetMessage('CRM_PRODUCT_LIST_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_PRODUCT_LIST'],
				array('section_id' => $sectionID)
			),
			'ICON' => 'btn-list'
		);
	}
}
else
{
//	if ($arParams['TYPE'] === 'list')
//	{
//		if($arSection)
//		{
//			$parentSectionID = isset($arSection['IBLOCK_SECTION_ID']) ? intval($arSection['IBLOCK_SECTION_ID']) : 0;
//
//			$arResult['BUTTONS'][] = array(
//				'TEXT' => GetMessage('CRM_MOVE_UP'),
//				'TITLE' => GetMessage('CRM_MOVE_UP_TITLE'),
//				'LINK' =>  CComponentEngine::MakePathFromTemplate(
//					$arParams['PATH_TO_PRODUCT_LIST'],
//					array('section_id' => $parentSectionID)
//				),
//				'ICON' => 'btn-parent-section',
//			);
//		}
//	}

	if ($arParams['TYPE'] !== 'list')
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_PRODUCT_LIST'),
			'TITLE' => GetMessage('CRM_PRODUCT_LIST_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_PRODUCT_LIST'],
				array('section_id' => $sectionID)
			),
			'ICON' => 'btn-list'
		);
	}

	if ($productAdd)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_PRODUCT_ADD'),
			'TITLE' => GetMessage('CRM_PRODUCT_ADD_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_PRODUCT_EDIT'],
				array('product_id' => 0)
			),
			'ICON' => 'btn-new'
		);
	}

	if ($productEdit && $arParams['TYPE'] == 'show' && $exists)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_PRODUCT_EDIT'),
			'TITLE' => GetMessage('CRM_PRODUCT_EDIT_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_PRODUCT_EDIT'],
				array('product_id' => $productID)
			),
			'ICON' => 'btn-edit'
		);
	}

	if ($productShow && $arParams['TYPE'] == 'edit' && $exists)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_PRODUCT_SHOW'),
			'TITLE' => GetMessage('CRM_PRODUCT_SHOW_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_PRODUCT_SHOW'],
				array('product_id' => $productID)
			),
			'ICON' => 'btn-view'
		);
	}

	if ($productDelete && ($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $exists)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_PRODUCT_DELETE'),
			'TITLE' => GetMessage('CRM_PRODUCT_DELETE_TITLE'),
			'LINK' => "javascript:product_delete('".GetMessage('CRM_PRODUCT_DELETE_DLG_TITLE')."', '".GetMessage('CRM_PRODUCT_DELETE_DLG_MESSAGE')."', '".GetMessage('CRM_PRODUCT_DELETE_DLG_BTNTITLE')."', '".CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_PRODUCT_EDIT'],
					array(
						'product_id' => $productID
					)),
				array('delete' => '', 'sessid' => bitrix_sessid())
			)."')",
			'ICON' => 'btn-delete'
		);
	}

	if($sectionShow)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_PRODUCT_SECTIONS'),
			'TITLE' => GetMessage('CRM_PRODUCT_SECTIONS_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_SECTION_LIST'],
				array('section_id' => $sectionID)
			),
			'ICON' => 'btn-edit-sections'
		);
	}
}

$this->IncludeComponentTemplate();
?>