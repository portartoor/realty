<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
	return;

$CrmPerms = new CCrmPerms($USER->GetID());
if ($CrmPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE))
	return;

$arParams['PATH_TO_COMPANY_LIST'] = CrmCheckPath('PATH_TO_COMPANY_LIST', $arParams['PATH_TO_COMPANY_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
$arParams['PATH_TO_COMPANY_EDIT'] = CrmCheckPath('PATH_TO_COMPANY_EDIT', $arParams['PATH_TO_COMPANY_EDIT'], $APPLICATION->GetCurPage().'?company_id=#company_id#&edit');
$arParams['PATH_TO_COMPANY_IMPORT'] = CrmCheckPath('PATH_TO_COMPANY_IMPORT', $arParams['PATH_TO_COMPANY_IMPORT'], $APPLICATION->GetCurPage().'?import');
$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');
$arParams['PATH_TO_CONTACT_EDIT'] = CrmCheckPath('PATH_TO_CONTACT_EDIT', $arParams['PATH_TO_CONTACT_EDIT'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&edit');

if(!isset($arParams['TYPE']))
	$arParams['TYPE'] = 'list';

if (isset($_REQUEST['copy']))
	$arParams['TYPE'] = 'copy';

$arResult['BUTTONS'] = array();
$arFields = array();

if ($arParams['TYPE'] == 'list')
{
	$bRead   = !$CrmPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'READ');
	$bExport = !$CrmPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'EXPORT');
	$bImport = !$CrmPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'IMPORT');
	$bAdd    = !$CrmPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'ADD');
	$bWrite  = !$CrmPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'WRITE');
	$bDelete = false;
}
else
{
	$arFilter = array(
		'ID' => $arParams['ELEMENT_ID']
	);
	$obFields = CCrmCompany::GetList(array(), $arFilter, array('OPENED'));
	$arFields = $obFields->GetNext();

	$arEntityAttr[$arParams['ELEMENT_ID']] = array();
	if ($arFields !== false)
		$arEntityAttr = $CrmPerms->GetEntityAttr('COMPANY', array($arParams['ELEMENT_ID']));

	$bRead   = $arFields !== false;
	$bExport = false;
	$bImport = false;
	$bAdd    = !$CrmPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE, 'ADD');
	$bWrite  = $CrmPerms->CheckEnityAccess('COMPANY', 'WRITE', $arEntityAttr[$arParams['ELEMENT_ID']]);
	$bDelete = $CrmPerms->CheckEnityAccess('COMPANY', 'DELETE', $arEntityAttr[$arParams['ELEMENT_ID']]);
}

if($arParams['TYPE'] === 'list')
{
	if($bAdd)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('COMPANY_ADD'),
			'TITLE' => GetMessage('COMPANY_ADD_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_COMPANY_EDIT'],
				array(
					'company_id' => 0
				)
			),
			//'ICON' => 'btn-new',
			'HIGHLIGHT' => true
		);
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
				array('type' => 'csv')
			),
			'ICON' => 'btn-export'
		);

		$arResult['BUTTONS'][] = array(
			'TITLE' => GetMessage('COMPANY_EXPORT_EXCEL_TITLE'),
			'TEXT' => GetMessage('COMPANY_EXPORT_EXCEL'),
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

if ($arParams['TYPE'] == 'edit' && $bRead && !empty($arParams['ELEMENT_ID']))
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

if ($bAdd)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('COMPANY_ADD'),
		'TITLE' => GetMessage('COMPANY_ADD_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_COMPANY_EDIT'],
			array('company_id' => 0)
		),
		'ICON' => 'btn-new'
	);
}

if ($arParams['TYPE'] == 'show')
{
	if (!$CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'ADD'))
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
