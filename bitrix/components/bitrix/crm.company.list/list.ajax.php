<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
	return ;

$CCrmCompany = new CCrmCompany();
if (!$USER->IsAuthorized() || $CCrmCompany->cPerms->HavePerm('COMPANY', BX_CRM_PERM_NONE))
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
		$arFilter['%TITLE'] = trim($arMatches[1]);
		$arFilter['LOGIC'] = 'OR';
	}
	else
	{
		$arFilter['%TITLE'] = $search;
		$arFilter['LOGIC'] = 'OR';
	}

	$arCompanyTypeList = CCrmStatus::GetStatusListEx('COMPANY_TYPE');
	$arCompanyIndustryList = CCrmStatus::GetStatusListEx('INDUSTRY');

	foreach($arCompanyTypeList as $key => $value)
	if (strpos($value, $search) !== false)
		$arFilter['COMPANY_TYPE'][] = $key;

	foreach($arCompanyIndustryList as $key => $value)
	if (strpos($value, $search) !== false)
		$arFilter['INDUSTRY'][] = $key;


	$arSelect = array('ID', 'TITLE', 'COMPANY_TYPE', 'INDUSTRY', 'LOGO');
	$arOrder = array('TITLE' => 'ASC', 'LAST_NAME' => 'ASC', 'NAME' => 'ASC');
	$arData = array();
	$obRes = CCrmCompany::GetList($arOrder, $arFilter, $arSelect, $nPageTop);
	$arFiles = array();
	while ($arRes = $obRes->Fetch())
	{
		$logoID = intval($arRes['LOGO']);
		if ($logoID > 0 && !isset($arFiles[$logoID]))
		{
			$arFiles[$logoID] = CFile::ResizeImageGet($logoID, array('width' => 25, 'height' => 25), BX_RESIZE_IMAGE_EXACT);
		}

		$arDesc = Array();
		if (isset($arCompanyTypeList[$arRes['COMPANY_TYPE']]))
			$arDesc[] = $arCompanyTypeList[$arRes['COMPANY_TYPE']];
		if (isset($arCompanyIndustryList[$arRes['INDUSTRY']]))
			$arDesc[] = $arCompanyIndustryList[$arRes['INDUSTRY']];
		$arData[] =
			array(
				'id' => $multi? 'CO_'.$arRes['ID']: $arRes['ID'],
				'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_company_show'),
					array(
						'company_id' => $arRes['ID']
					)
				),
				'title' => (str_replace(array(';', ','), ' ', $arRes['TITLE'])),
				'desc' => implode(', ', $arDesc),
				'image' => isset($arFiles[$logoID]['src']) ? $arFiles[$logoID]['src'] : '',
				'type' => 'company'
			)
		;
	}

	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject($arData);
	die();
}
?>