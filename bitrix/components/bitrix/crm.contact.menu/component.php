<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule('crm'))
	return;

$CrmPerms = new CCrmPerms($USER->GetID());
if ($CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE))
	return;

$arParams['PATH_TO_CONTACT_LIST'] = CrmCheckPath('PATH_TO_CONTACT_LIST', $arParams['PATH_TO_CONTACT_LIST'], $APPLICATION->GetCurPage());
$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
$arParams['PATH_TO_CONTACT_EDIT'] = CrmCheckPath('PATH_TO_CONTACT_EDIT', $arParams['PATH_TO_CONTACT_EDIT'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&edit');
$arParams['PATH_TO_CONTACT_IMPORT'] = CrmCheckPath('PATH_TO_CONTACT_IMPORT', $arParams['PATH_TO_CONTACT_IMPORT'], $APPLICATION->GetCurPage().'?import');
$arParams['PATH_TO_DEAL_EDIT'] = CrmCheckPath('PATH_TO_DEAL_EDIT', $arParams['PATH_TO_DEAL_EDIT'], $APPLICATION->GetCurPage().'?deal_id=#deal_id#&edit');

if (!isset($arParams['TYPE']))
	$arParams['TYPE'] = 'list';

if (isset($_REQUEST['copy']))
	$arParams['TYPE'] = 'copy';

$arResult['BUTTONS'] = array();
$arFields = array();

if ($arParams['TYPE'] == 'list')
{
	$bRead   = !$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'READ');
	$bExport = !$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'EXPORT');
	$bImport = !$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'IMPORT');
	$bAdd    = !$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'ADD');
	$bWrite  = !$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'WRITE');
	$bDelete = false;
}
else
{
	$arFilter = array(
		'ID' => $arParams['ELEMENT_ID']
	);
	$obFields = CCrmContact::GetList(array(), $arFilter, array('OPENED'));
	$arFields = $obFields->GetNext();

	$arEntityAttr[$arParams['ELEMENT_ID']] = array();
	if ($arFields !== false)
		$arEntityAttr = $CrmPerms->GetEntityAttr('CONTACT', array($arParams['ELEMENT_ID']));

	$bRead   = $arFields !== false;
	$bExport = false;
	$bImport = false;
	$bAdd    = !$CrmPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE, 'ADD');
	$bWrite  = $CrmPerms->CheckEnityAccess('CONTACT', 'WRITE', $arEntityAttr[$arParams['ELEMENT_ID']]);
	$bDelete = $CrmPerms->CheckEnityAccess('CONTACT', 'DELETE', $arEntityAttr[$arParams['ELEMENT_ID']]);
}

if (!$bRead && !$bAdd && !$bWrite)
	return false;

if($arParams['TYPE'] === 'list')
{
	if($bAdd)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_CONTACT_ADD'),
			'TITLE' => GetMessage('CRM_CONTACT_ADD_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_EDIT'],
				array(
					'contact_id' => 0
				)
			),
			//'ICON' => 'btn-new',
			'HIGHLIGHT' => true
		);
	}

	if ($bImport)
	{
		$arResult['BUTTONS'][] = array(
			'TEXT' => GetMessage('CRM_CONTACT_IMPORT'),
			'TITLE' => GetMessage('CRM_CONTACT_IMPORT_TITLE'),
			'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_IMPORT'], array()),
			'ICON' => 'btn-import'
		);
	}

	if ($bExport)
	{
		$arResult['BUTTONS'][] = array(
				'TITLE' => GetMessage('CRM_CONTACT_EXPORT_CSV_TITLE'),
				'TEXT' => GetMessage('CRM_CONTACT_EXPORT_CSV'),
				'LINK' => CHTTP::urlAddParams(
					CComponentEngine::MakePathFromTemplate($APPLICATION->GetCurPage(), array()),
					array('type' => 'csv')
				),
				'ICON' => 'btn-export'
		);

		$arResult['BUTTONS'][] = array(
				'TITLE' => GetMessage('CRM_CONTACT_EXPORT_EXCEL_TITLE'),
				'TEXT' => GetMessage('CRM_CONTACT_EXPORT_EXCEL'),
				'LINK' => CHTTP::urlAddParams(
					CComponentEngine::MakePathFromTemplate($APPLICATION->GetCurPage(), array()),
					array('type' => 'excel')
				),
				'ICON' => 'btn-export'
		);

		if (IsModuleInstalled('webservice') && CModule::IncludeModule('webservice'))
		{
			$GLOBALS['APPLICATION']->AddHeadScript('/bitrix/js/crm/outlook.js');

			$rsSites = CSite::GetByID(SITE_ID);
			$arSite = $rsSites->Fetch();
			if (strlen($arSite['SITE_NAME']) > 0)
				$sPrefix = $arSite['SITE_NAME'];
			else
				$sPrefix = COption::GetOptionString('main', 'site_name', GetMessage('CRM_OUTLOOK_PREFIX_CONTACTS'));

			$GUID = CCrmContactWS::makeGUID(md5($_SERVER['SERVER_NAME'].'|'.$type));
			$arResult['BUTTONS'][] = array(
				'TITLE' => GetMessage('CRM_CONTACT_EXPORT_OUTLOOK_TITLE'),
				'TEXT' => GetMessage('CRM_CONTACT_EXPORT_OUTLOOK'),
				'ONCLICK' => "jsOutlookUtils.Sync('contacts', '/bitrix/tools/ws_contacts_crm/', '".$APPLICATION->GetCurPage()."', '".CUtil::JSEscape($sPrefix)."', '".CUtil::JSEscape(GetMessage('CRM_OUTLOOK_TITLE_CONTACTS'))."', '$GUID')",
				'ICON' => 'btn-export'
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

if (($arParams['TYPE'] == 'show') && $bWrite && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_CONTACT_EDIT'),
		'TITLE' => GetMessage('CRM_CONTACT_EDIT_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_EDIT'],
			array(
				'contact_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-edit'
	);
}

if (($arParams['TYPE'] == 'edit') && $bRead && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_CONTACT_SHOW'),
		'TITLE' => GetMessage('CRM_CONTACT_SHOW_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_SHOW'],
			array(
				'contact_id' => $arParams['ELEMENT_ID']
			)
		),
		'ICON' => 'btn-view'
	);
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $bAdd
	&& !empty($arParams['ELEMENT_ID']) && !isset($_REQUEST['copy']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_CONTACT_COPY'),
		'TITLE' => GetMessage('CRM_CONTACT_COPY_TITLE'),
		'LINK' => CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_EDIT'],
			array(
				'contact_id' => $arParams['ELEMENT_ID']
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

if ($bAdd)
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_CONTACT_ADD'),
		'TITLE' => GetMessage('CRM_CONTACT_ADD_TITLE'),
		'LINK' => CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_CONTACT_EDIT'],
			array('contact_id' => 0)
		),
		'ICON' => 'btn-new'
	);
}

if ($arParams['TYPE'] == 'show' && !$CrmPerms->HavePerm('DEAL', BX_CRM_PERM_NONE, 'ADD'))
{
	$obFields = CCrmContact::GetList(array(), array('ID' => $arParams['ELEMENT_ID']), array());
	$arContact = $obFields->GetNext();
	$arResult['BUTTONS'][]= array(
		'TEXT' => GetMessage('CRM_CONTACT_DEAL_ADD'),
		'TITLE' => GetMessage('CRM_CONTACT_DEAL_ADD_TITLE'),
		'LINK' => CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_DEAL_EDIT'], array('deal_id' => 0)),
			array('contact_id' => $arContact['ID'], 'company_id' => $arContact['COMPANY_ID'])
		),
		'ICONCLASS' => 'btn-add-deal'
	);
}

if (($arParams['TYPE'] == 'edit' || $arParams['TYPE'] == 'show') && $bDelete && !empty($arParams['ELEMENT_ID']))
{
	$arResult['BUTTONS'][] = array(
		'TEXT' => GetMessage('CRM_CONTACT_DELETE'),
		'TITLE' => GetMessage('CRM_CONTACT_DELETE_TITLE'),
		'LINK' => "javascript:contact_delete('".GetMessage('CRM_CONTACT_DELETE_DLG_TITLE')."', '".GetMessage('CRM_CONTACT_DELETE_DLG_MESSAGE')."', '".GetMessage('CRM_CONTACT_DELETE_DLG_BTNTITLE')."', '".CHTTP::urlAddParams(CComponentEngine::MakePathFromTemplate($arParams['PATH_TO_CONTACT_EDIT'],
			array(
				'contact_id' => $arParams['ELEMENT_ID']
			)),
			array('delete' => '', 'sessid' => bitrix_sessid())
		)."')",
		'ICON' => 'btn-delete'
	);
}

$this->IncludeComponentTemplate();

?>
