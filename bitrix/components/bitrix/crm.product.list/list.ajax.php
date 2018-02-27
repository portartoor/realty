<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
{
	return;
}

if (!$USER->IsAuthorized() || $_REQUEST['MODE'] != 'SEARCH')
{
	return;
}

__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

CUtil::JSPostUnescape();
$GLOBALS['APPLICATION']->RestartBuffer();

$search = trim($_REQUEST['VALUE']);
$multi = isset($_REQUEST['MULTI']) && $_REQUEST['MULTI'] == 'Y'? true: false;
$arFilter = array();
$arData = array();
if (is_numeric($search))
{
	$arFilter['ID'] = (int) $search;
}
elseif (preg_match('/(.*)\[(\d+?)\]/i'.BX_UTF_PCRE_MODIFIER, $search, $arMatches))
{
	$arFilter['ID'] = intval($arMatches[2]);
	$arFilter['%NAME'] =  trim($arMatches[1]);
	$arFilter['LOGIC'] =  'OR';

	/*$arFilter['ACTIVE'] = 'Y';
	$arFilter['__INNER_FILTER'] = array(
		'LOGIC' => 'OR',
		'ID' => intval($arMatches[2]),
		'%NAME' =>  trim($arMatches[1])
	);*/
}
else
{
	//$arFilter['ACTIVE'] = 'Y';
	$arFilter['%NAME'] = $search;
}

$dstCurrencyID = isset($_REQUEST['CURRENCY_ID']) ? trim($_REQUEST['CURRENCY_ID']) : '';
$dstCurrency = strlen($dstCurrencyID) > 0 ? CCrmCurrency::GetByID($dstCurrencyID) : CCrmCurrency::GetBaseCurrency();

// Default currency exchange rates are used
//$dstExchRate = isset($_REQUEST['EXCH_RATE']) ? (double)$_REQUEST['EXCH_RATE'] : 0;
//if($dstExchRate == 0)
//{
//	$dstExchRate = is_array($dstCurrency) ? $dstCurrency['EXCH_RATE'] : 1.0;
//}

$obRes = CCrmProduct::GetList(
	array('NAME' => 'ASC'),
	$arFilter,
	array('ID', 'NAME', 'PRICE', 'CURRENCY_ID')
);

while ($arRes = $obRes->Fetch())
{
	$srcCurrencyID = isset($arRes['CURRENCY_ID']) ? $arRes['CURRENCY_ID'] : 0;
	if(strlen($dstCurrencyID) > 0 && strlen($srcCurrencyID) > 0  && $dstCurrencyID != $srcCurrencyID)
	{
		//$srcCurrency = CCrmCurrency::GetByID($srcCurrencyID);
		//$arRes['PRICE'] = CCrmCurrency::ConvertMoney($arRes['PRICE'], $srcCurrency ? $srcCurrency['EXCH_RATE'] : 1.0, $dstExchRate);
		$arRes['PRICE'] = CCrmCurrency::ConvertMoney($arRes['PRICE'], $srcCurrencyID, $dstCurrencyID);
		$arRes['CURRENCY_ID'] = $dstCurrencyID;
	}

	$arData[] =
		array(
			'id' => $multi? 'PROD_'.$arRes['ID']: $arRes['ID'],
			'url' => CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_product_show'),
				array('product_id' => $arRes['ID'])
			),
			'title' => $arRes['NAME'],
			'desc' => CCrmProduct::FormatPrice($arRes),
			'type' => 'product',
			'customData' => array('price' => $arRes['PRICE'])
		);
}

Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
echo CUtil::PhpToJsObject($arData);
die();
?>