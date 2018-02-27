<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
	return;

$CrmPerms = new CCrmPerms($USER->GetID());
if ($CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE))
	return;

$arParams['PATH_TO_DEAL_LIST'] = CrmCheckPath('PATH_TO_DEAL_LIST', $arParams['PATH_TO_DEAL_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_DEAL_SHOW'] = CrmCheckPath('PATH_TO_DEAL_SHOW', $arParams['PATH_TO_DEAL_SHOW'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&show');
$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');
$arParams['PATH_TO_DEAL_IMPORT'] = CrmCheckPath('PATH_TO_DEAL_IMPORT', $arParams['PATH_TO_DEAL_IMPORT'], $APPLICATION->GetCurPage().'?import');

if (!isset($arParams['TYPE']))
	$arParams['TYPE'] = 'list';

if (isset($_REQUEST['copy']))
	$arParams['TYPE'] = 'copy';

$arResult['TYPE'] = $arParams['TYPE'];

$arResult['BUTTONS'] = array();
$arFields = array();

if ($arParams['TYPE'] == 'list')
{
	$bRead   = !$CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'READ');
	$bExport = !$CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'EXPORT');
	$bImport = !$CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'IMPORT');
	$bAdd    = !$CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'ADD');
	$bWrite  = !$CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'WRITE');
	$bDelete = false;
}
else
{
	$arFilter = array(
		'ID' => $arParams['ELEMENT_ID']
	);
	$obFields = CCrmDeal::GetList(array(), $arFilter, array('OPENED'));
	$arFields = $obFields->GetNext();

	$arEntityAttr[$arParams['ELEMENT_ID']] = array();
	if ($arFields !== false)
		$arEntityAttr = $CrmPerms->GetEntityAttr('DEAL', array($arParams['ELEMENT_ID']));

	$bRead   = $arFields !== false;
	$bExport = false;
	$bImport = false;
	$bAdd    = !$CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'ADD');
	$bWrite  = $CrmPerms->CheckEnityAccess('DEAL', 'WRITE', $arEntityAttr[$arParams['ELEMENT_ID']]);
	$bDelete = $CrmPerms->CheckEnityAccess('DEAL', 'DELETE', $arEntityAttr[$arParams['ELEMENT_ID']]);
}

if (!$bRead && !$bAdd && !$bWrite)
	return false;

if($arParams['TYPE'] === 'list')
{
	if ($bAdd)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('DEAL_ADD'),
			'TITLE' => GetMessage('DEAL_ADD_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_EDIT'],
				array(
					'deal_id' => 0
				)
			),
			//'ICON' => 'btn-new',
			'HIGHLIGHT' => true
		);
	}

	if ($bImport)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('DEAL_IMPORT'),
			'TITLE' => GetMessage('DEAL_IMPORT_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_IMPORT'], array()),
			'ICON' => 'btn-import'
		);
	}

	if ($bExport)
	{
		$arResult['BUTTONS'][] = array(
			'TITLE' => GetMessage('DEAL_EXPORT_CSV_TITLE'),
			'TEXT' => GetMessage('DEAL_EXPORT_CSV'),
			'LINK' => CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate($APPLICATION->GetCurPage(), array()),
				array('type' => 'csv')
			),
			'ICON' => 'btn-export'
		);

		$arResult['BUTTONS'][] = array(
			'TITLE' => GetMessage('DEAL_EXPORT_EXCEL_TITLE'),
			'TEXT' => GetMessage('DEAL_EXPORT_EXCEL'),
			'LINK' => CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate($APPLICATION->GetCurPage(), array()),
				array('type' => 'excel')
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
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_EDIT'],
			array(
				'deal_id' => 0
			)
		),
		'ICON' => 'btn-new'
	);
}

$this->IncludeComponentTemplate();
?>
