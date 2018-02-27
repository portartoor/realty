<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
	return;

$CrmPerms = new CCrmPerms($USER->GetID());

$arParams['PATH_TO_LEAD_LIST'] = CrmCheckPath('PATH_TO_LEAD_LIST', $arParams['PATH_TO_LEAD_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_LEAD_EDIT'] = CrmCheckPath('PATH_TO_LEAD_EDIT', $arParams['PATH_TO_LEAD_EDIT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&edit');
$arParams['PATH_TO_LEAD_SHOW'] = CrmCheckPath('PATH_TO_LEAD_SHOW', $arParams['PATH_TO_LEAD_SHOW'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&show');
$arParams['PATH_TO_LEAD_CONVERT'] = CrmCheckPath('PATH_TO_LEAD_CONVERT', $arParams['PATH_TO_LEAD_CONVERT'], $APPLICATION->GetCurPage().'?lead_id=#lead_id#&convert');
$arParams['PATH_TO_LEAD_IMPORT'] = CrmCheckPath('PATH_TO_LEAD_IMPORT', $arParams['PATH_TO_LEAD_IMPORT'], $APPLICATION->GetCurPage().'?import');

if (!isset($arParams['TYPE']))
	$arParams['TYPE'] = 'list';

if (isset($_REQUEST['copy']))
	$arParams['TYPE'] = 'copy';

$arResult['TYPE'] = $arParams['TYPE'];

global $USER;

$arResult['BUTTONS'] = array();

$arParams['ELEMENT_ID'] = (int) $arParams['ELEMENT_ID'];
$arFields = array();

if ($arParams['TYPE'] == 'list')
{
	$bRead   = !$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'READ');
	$bExport = !$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'EXPORT');
	$bImport = !$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'IMPORT');
	$bAdd    = !$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'ADD');
	$bWrite  = !$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'WRITE');
	$bDelete = false;
}
else
{
	$arFilter = array(
		'ID' => $arParams['ELEMENT_ID']
	);
	$obFields = CCrmLead::GetList(array(), $arFilter, array('STATUS_ID', 'OPENED'));
	$arFields = $obFields->GetNext();

	$arEntityAttr[$arParams['ELEMENT_ID']] = array();
	if ($arFields !== false)
		$arEntityAttr = $CrmPerms->GetEntityAttr('LEAD', array($arParams['ELEMENT_ID']));

	$bRead   = $arFields !== false;
	$bExport = false;
	$bImport = false;
	$bAdd    = !$CrmPerms->HavePerm('LEAD', BX_CRM_PERM_NONE, 'ADD');
	$bWrite  = $CrmPerms->CheckEnityAccess('LEAD', 'WRITE', $arEntityAttr[$arParams['ELEMENT_ID']]);
	$bDelete = $CrmPerms->CheckEnityAccess('LEAD', 'DELETE', $arEntityAttr[$arParams['ELEMENT_ID']]);
}

if (!$bRead && !$bAdd && !$bWrite)
	return false;

if($arParams['TYPE'] === 'list')
{
	if ($bAdd)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('LEAD_ADD'),
			'TITLE' => GetMessage('LEAD_ADD_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_EDIT'],
				array(
					'lead_id' => 0
				)
			),
			//'ICON' => 'btn-new',
			'HIGHLIGHT' => true
		);
	}

	if ($bImport)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('LEAD_IMPORT'),
			'TITLE' => GetMessage('LEAD_IMPORT_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_IMPORT'], array()),
			'ICON' => 'btn-import'
		);
	}

	if ($bExport)
	{
		$arResult['BUTTONS'][] = array(
			'TITLE' => GetMessage('LEAD_EXPORT_CSV_TITLE'),
			'TEXT' => GetMessage('LEAD_EXPORT_CSV'),
			'LINK' => CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate($APPLICATION->GetCurPage(), array()),
				array('type' => 'csv')
			),
			'ICON' => 'btn-export'
		);

		$arResult['BUTTONS'][] = array(
			'TITLE' => GetMessage('LEAD_EXPORT_EXCEL_TITLE'),
			'TEXT' => GetMessage('LEAD_EXPORT_EXCEL'),
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

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show')
	&& $bWrite && !$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'ADD')
	&& !empty($arParams['ELEMENT_ID']) && $arFields['STATUS_ID'] != 'CONVERTED')
{
	if (!$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'WRITE'))
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('LEAD_CONVERT'),
			'TITLE' => GetMessage('LEAD_CONVERT_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_CONVERT'],
				array(
					'lead_id' => $arParams['ELEMENT_ID']
				)
			),
			'ICON' => 'btn-copy'
		);
	}
}

if (($arParams['TYPE'] == 'show' || $arParams['TYPE'] == 'convert') && $bWrite
	&& !empty($arParams['ELEMENT_ID']) && $arFields['STATUS_ID'] != 'CONVERTED')
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('LEAD_EDIT'),
		'TITLE' => GetMessage('LEAD_EDIT_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_EDIT'],
			array(
				'lead_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-edit'
	);
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'convert') && $bRead && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('LEAD_SHOW'),
		'TITLE' => GetMessage('LEAD_SHOW_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_SHOW'],
			array(
				'lead_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-view'
	);
}

$qty = count($arResult['BUTTONS']);

if (!empty($arResult['BUTTONS']) && ($arParams['TYPE'] == 'list' ||
	($arParams['TYPE'] == 'edit' && empty($arParams['ELEMENT_ID']))))
	$arResult['BUTTONS'][] = array('SEPARATOR' => true);
elseif ($arParams['TYPE'] == 'show' && $qty > 1)
	$arResult['BUTTONS'][] = array('NEWBAR' => true);
elseif ($qty >= 3 || ($arFields['STATUS_ID'] == 'CONVERTED' && $qty >= 2))
	$arResult['BUTTONS'][] = array('NEWBAR' => true);

if ($bAdd && ($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show' || $arParams['TYPE'] == 'convert')
	&& !empty($arParams['ELEMENT_ID']) && !isset($_REQUEST['copy']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('LEAD_COPY'),
		'TITLE' => GetMessage('LEAD_COPY_TITLE'),
		'LINK' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_EDIT'],
			array(
				'lead_id' => $arParams['ELEMENT_ID']
			)),
			array('copy' => 1)
		),
		'ICON' => 'btn-copy'
	);
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show' || $arParams['TYPE'] == 'convert') && $bDelete
	&& !empty($arParams['ELEMENT_ID']) && ($arFields['STATUS_ID'] != 'CONVERTED' || CCrmPerms::IsAdmin()))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('LEAD_DELETE'),
		'TITLE' => GetMessage('LEAD_DELETE_TITLE'),
		'LINK' => "javascript:lead_delete('".GetMessage('LEAD_DELETE_DLG_TITLE')."', '".GetMessage('LEAD_DELETE_DLG_MESSAGE')."', '".GetMessage('LEAD_DELETE_DLG_BTNTITLE')."', '".CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_EDIT'],
				array(
					'lead_id' => $arParams['ELEMENT_ID']
				)),
			array('delete' => '', 'sessid' => bitrix_sessid())
		)."')",
		'ICON' => 'btn-delete'
	);
}

if ($bAdd && $arParams['TYPE'] != 'list')
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('LEAD_ADD'),
		'TITLE' => GetMessage('LEAD_ADD_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_LEAD_EDIT'],
			array(
				'lead_id' => 0
			)
		),
		'ICON' => 'btn-new'
	);
}

$this->IncludeComponentTemplate();
?>
