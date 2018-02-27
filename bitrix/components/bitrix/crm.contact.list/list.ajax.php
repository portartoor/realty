<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
	return ;

$CCrmCompany = new CCrmContact();
if (!$USER->IsAuthorized() || $CCrmCompany->cPerms->HavePerm('CONTACT', BX_CRM_PERM_NONE))
	return ;

__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

if ($_REQUEST['MODE'] == 'SEARCH')
{
	CUtil::JSPostUnescape();
	$APPLICATION->RestartBuffer();

	// Limit count of items to be found
	$nPageTop = 50;		// 50 items by default
	if (isset($_REQUEST['LIMIT_COUNT']) && ($_REQUEST['LIMIT_COUNT'] >= 0))
	{
		$rawNPageTop = (int) $_REQUEST['LIMIT_COUNT'];
		if ($rawNPageTop === 0)
			$nPageTop = false;		// don't limit
		elseif ($rawNPageTop > 0)
			$nPageTop = $rawNPageTop;
	}

	$search = trim($_REQUEST['VALUE']);
	$multi = isset($_REQUEST['MULTI']) && $_REQUEST['MULTI'] == 'Y'? true: false;
	$arFilter = array();
	if (is_numeric($search))
		$arFilter['ID'] = (int) $search;
	else if (preg_match('/(.*)\[(\d+?)\]/i'.BX_UTF_PCRE_MODIFIER, $search, $arMatches))
	{
		$arFilter['ID'] = (int) $arMatches[2];
		$arFilter['%FULL_NAME'] = trim($arMatches[1]);
		$arFilter['LOGIC'] = 'OR';
	}
	else
	{
		$arFilter['%FULL_NAME'] = trim($search);
		$arFilter['%COMPANY_TITLE'] = trim($search);
		$arFilter['LOGIC'] = 'OR';
	}
	$arContactTypeList = CCrmStatus::GetStatusListEx('CONTACT_TYPE');
	$arSelect = array('ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'COMPANY_TITLE', 'PHOTO');
	$arOrder = array('LAST_NAME' => 'ASC', 'NAME' => 'ASC');
	$arData = array();
	$obRes = CCrmContact::GetList($arOrder, $arFilter, $arSelect, $nPageTop);
	$arFiles = array();
	while ($arRes = $obRes->Fetch())
	{
		$photoID = intval($arRes['PHOTO']);
		if ($photoID > 0 && !isset($arFiles[$photoID]))
		{
			$arFiles[$photoID] = CFile::ResizeImageGet($photoID, array('width' => 25, 'height' => 25), BX_RESIZE_IMAGE_EXACT);
		}

		$arData[] =
			array(
				'id' => $multi? 'C_'.$arRes['ID']: $arRes['ID'],
				'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_contact_show'),
					array(
						'contact_id' => $arRes['ID']
					)
				),
				'title' => CUser::FormatName(
					CSite::GetNameFormat(false),
					array(
						'LOGIN' => '',
						'NAME' => isset($arRes['NAME']) ? $arRes['NAME'] : '',
						'SECOND_NAME' => isset($arRes['SECOND_NAME']) ? $arRes['SECOND_NAME'] : '',
						'LAST_NAME' => isset($arRes['LAST_NAME']) ? $arRes['LAST_NAME'] : ''
					),
					false,
					false
				),
				'desc' => empty($arRes['COMPANY_TITLE'])? "": $arRes['COMPANY_TITLE'],
				'image' => isset($arFiles[$photoID]['src']) ? $arFiles[$photoID]['src'] : '',
				'type' => 'contact'
			)
		;
	}

	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject($arData);
	die();
}
?>