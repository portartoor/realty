<?
define('STOP_STATISTICS', true);
define('BX_SECURITY_SHOW_MESSAGE', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if (!CModule::IncludeModule('crm'))
{
	return;
}

global $USER, $APPLICATION;

/*
 * ONLY 'POST' SUPPORTED
 * SUPPORTED MODES:
 * 'CALC_PRODUCT_PRICES' - product prices calculations
 * 'CONVERT_MONEY' - convert sum to destination currency
 * 'ADD_PRODUCT' - add product (with immediate saving of changes)
 * 'UPDATE_PRODUCT' - update product (with immediate saving of changes)
 * 'REMOVE_PRODUCT' - remove product (with immediate saving of changes)
 */

if (!$USER->IsAuthorized() || !check_bitrix_sessid() || $_SERVER['REQUEST_METHOD'] != 'POST')
{
	return;
}

__IncludeLang(dirname(__FILE__).'/lang/'.LANGUAGE_ID.'/'.basename(__FILE__));

CUtil::JSPostUnescape();
$APPLICATION->RestartBuffer();
Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);

// Recalcutation of product prices after currency change

$mode = isset($_POST['MODE']) ? $_POST['MODE'] : '';
if(!isset($mode[0]))
{
	die();
}

$ownerType = isset($_POST['OWNER_TYPE']) ? $_POST['OWNER_TYPE'] : '';
if(!isset($ownerType[0]))
{
	echo CUtil::PhpToJSObject(array('ERROR'=>'OWNER_TYPE_NOT_FOUND'));
	die();
}

$ownerID = isset($_POST['OWNER_ID']) ? intval($_POST['OWNER_ID']) : 0;

$ownerName = CCrmOwnerTypeAbbr::ResolveName($ownerType);
$perms = new CCrmPerms($USER->GetID());

if($mode === 'CALC_PRODUCT_PRICES')
{
	if ($perms->HavePerm($ownerName, BX_CRM_PERM_NONE, 'READ'))
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'PERMISSION_DENIED'));
		die();
	}

	$data = isset($_POST['DATA']) && is_array($_POST['DATA']) ? $_POST['DATA'] : array();
	if(count($data) == 0)
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'SOURCE_DATA_NOT_FOUND'));
		die();
	}

//{ SRC_CURRENCY_ID:'RUB', SRC_EXCH_RATE:1, DST_CURRENCY_ID:'USD', PRODUCTS:[ { ID:1, PRICE:1.0 }...] }

// National currency is default currency
	$srcCurrencyID = isset($data['SRC_CURRENCY_ID']) && strlen(strval($data['SRC_CURRENCY_ID'])) > 0 ? strval($data['SRC_CURRENCY_ID']) : CCrmCurrency::GetBaseCurrencyID();
//	$srcExchRate = 0.0;
//	if(isset($data['SRC_EXCH_RATE']))
//	{
//		$srcExchRate = doubleval($data['SRC_EXCH_RATE']);
//	}
//
//	if($srcExchRate <= 0.0)
//	{
//		$srcExchRate = ($srcCurrency = CCrmCurrency::GetByID($srcCurrencyID)) ? $srcCurrency['EXCH_RATE'] : 1.0;
//	}

	$dstCurrencyID = isset($data['DST_CURRENCY_ID']) && strlen(strval($data['DST_CURRENCY_ID'])) > 0 ? strval($data['DST_CURRENCY_ID']) : CCrmCurrency::GetBaseCurrencyID();
//	$dstExchRate = ($dstCurrency = CCrmCurrency::GetByID($dstCurrencyID)) ? $dstCurrency['EXCH_RATE'] : 1.0;

	$arProducts = isset($data['PRODUCTS']) && is_array($data['PRODUCTS']) ? $data['PRODUCTS'] : array();
	if(count($arProducts) > 0)
	{
		foreach($arProducts as &$arProduct)
		{
			$arProduct['PRICE'] =
				CCrmCurrency::ConvertMoney(
					isset($arProduct['PRICE']) ? $arProduct['PRICE'] : 1.0,
					$srcCurrencyID,
					$dstCurrencyID
				);
		}
	}

	Header('Content-Type: application/x-javascript; charset='.LANG_CHARSET);
	echo CUtil::PhpToJsObject(
		array(
			'CURRENCY_ID'=> $dstCurrencyID,
			'CURRENCY_FORMAT' => CCrmCurrency::GetCurrencyFormatString($dstCurrencyID),
			//'EXCH_RATE' => $dstExchRate,
			'EXCH_RATE' => CCrmCurrency::GetExchangeRate($dstCurrencyID),
			'PRODUCS'=> $arProducts,
			'PRODUCT_POPUP_ITEMS' => CCrmProductHelper::PreparePopupItems($dstCurrencyID)
		)
	);
}
elseif($mode === 'CONVERT_MONEY')
{
	if ($perms->HavePerm($ownerName, BX_CRM_PERM_NONE, 'READ'))
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'PERMISSION_DENIED'));
		die();
	}

	$data = isset($_POST['DATA']) && is_array($_POST['DATA']) ? $_POST['DATA'] : array();
	if(count($data) == 0)
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'SOURCE_DATA_NOT_FOUND'));
		die();
	}

	$srcSum = isset($data['SRC_SUM']) ? doubleval($data['SRC_SUM']) : 0.0;
	$srcCurrencyID = isset($data['SRC_CURRENCY_ID']) && strlen(strval($data['SRC_CURRENCY_ID'])) > 0 ? strval($data['SRC_CURRENCY_ID']) : CCrmCurrency::GetBaseCurrencyID();
	$dstCurrencyID = isset($data['DST_CURRENCY_ID']) && strlen(strval($data['DST_CURRENCY_ID'])) > 0 ? strval($data['DST_CURRENCY_ID']) : CCrmCurrency::GetBaseCurrencyID();

	echo CUtil::PhpToJSObject(
		array('SUM' => CCrmCurrency::ConvertMoney($srcSum, $srcCurrencyID, $dstCurrencyID))
	);
}
elseif($mode === 'ADD_PRODUCT')
{
// 'OWNER_TYPE':'D', 'OWNER_ID':7 'PRODUCT_ID':100, 'QTY':1, 'CURRENCY_ID':1 'PRICE':100.50

	if ($perms->HavePerm($ownerName, BX_CRM_PERM_NONE, 'WRITE'))
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'PERMISSION_DENIED'));
 		die();
 	}

	if($ownerID <= 0)
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'OWNER_ID_NOT_FOUND'));
		die();
	}

	// 'OWNER_TYPE':'D', 'OWNER_ID':7 'PRODUCT_ID':100, 'QTY':1, 'CURRENCY_ID':1 'PRICE':100.50
	$fields = array(
		'OWNER_TYPE' => $ownerType,
		'OWNER_ID' => $ownerID
	);

	$fields['PRODUCT_ID'] = isset($_POST['PRODUCT_ID']) ? intval($_POST['PRODUCT_ID']) : 0;
	if($fields['PRODUCT_ID'] <= 0)
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'PRODUCT_ID_NOT_FOUND'));
		die();
	}

	$fields['QUANTITY'] = isset($_POST['QUANTITY']) ? intval($_POST['QUANTITY']) : 1;
	if($fields['QUANTITY'] <= 0) // Zero and negative quantity are not allowed
	{
		$fields['QUANTITY'] = 1;
	}

//	$fields['CURRENCY_ID'] = isset($_POST['CURRENCY_ID']) ? intval($_POST['CURRENCY_ID']) : 0;
//	if($fields['CURRENCY_ID'] <= 0)
//	{
//		$fields['CURRENCY_ID'] = CCrmCurrency::GetNationalCurrencyID();
//	}

//	 Zero and negative prices are allowed
	$fields['PRICE'] = isset($_POST['PRICE']) ? doubleval($_POST['PRICE']) : 0.0;

	$ID = CCrmProductRow::Add($fields);
	if(!$ID)
	{
		echo CUtil::PhpToJSObject(array('ERROR' => CCrmProductRow::GetLastError()));
	}
	else
	{
		//$fields = CCrmProductRow::GetByID($ID);
		echo CUtil::PhpToJSObject(
			array(
				'PRODUCT_ROW' => array(
					'ID' => $ID,
					'OWNER_TYPE' => $fields['OWNER_TYPE'],
					'OWNER_ID' => $fields['OWNER_ID'],
					'PRODUCT_ID' => $fields['PRODUCT_ID'],
					'QUANTITY' => $fields['QUANTITY'],
					//'CURRENCY_ID' => $fields['CURRENCY_ID']
					'PRICE' => $fields['PRICE']
				)
			)
		);
	}
}
elseif($mode === 'UPDATE_PRODUCT')
{
	if ($perms->HavePerm($ownerName, BX_CRM_PERM_NONE, 'WRITE'))
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'PERMISSION_DENIED'));
		die();
	}

	//'ID':1 'PRODUCT_ID':100, 'QUANTITY':1, 'CURRENCY_ID':1 'PRICE':100.50
	$fields = array();

	$ID = isset($_POST['ID']) ? intval($_POST['ID']) : 0;
	if($ID <= 0)
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'ID_NOT_FOUND'));
		die();
	}

	$fields['PRODUCT_ID'] = isset($_POST['PRODUCT_ID']) ? intval($_POST['PRODUCT_ID']) : 0;
	if($fields['PRODUCT_ID'] <= 0)
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'PRODUCT_ID_NOT_FOUND'));
		die();
	}

	$fields['QUANTITY'] = isset($_POST['QUANTITY']) ? intval($_POST['QUANTITY']) : 1;
	if($fields['QUANTITY'] <= 0) // Zero and negative quantity are not allowed
	{
		$fields['QUANTITY'] = 1;
	}

//	$fields['CURRENCY_ID'] = isset($_POST['CURRENCY_ID']) ? intval($_POST['CURRENCY_ID']) : 0;
//	if($fields['CURRENCY_ID'] <= 0)
//	{
//		$fields['CURRENCY_ID'] = CCrmCurrency::GetNationalCurrencyID();
//	}

//	 Zero and negative prices are allowed
	$fields['PRICE'] = isset($_POST['PRICE']) ? doubleval($_POST['PRICE']) : 0.0;

	if(!CCrmProductRow::Update($ID, $fields))
	{
		echo CUtil::PhpToJSObject(array('ERROR' => CCrmProductRow::GetLastError()));
	}
	else
	{
		//$fields = CCrmProductRow::GetByID($ID);
		echo CUtil::PhpToJSObject(
			array(
				'PRODUCT_ROW' => array(
					'ID' => $ID,
					'PRODUCT_ID' => $fields['PRODUCT_ID'],
					'QUANTITY' => $fields['QUANTITY'],
					//'CURRENCY_ID' => $fields['CURRENCY_ID']
					'PRICE' => $fields['PRICE']
				)
			)
		);
	}
}
elseif($mode === 'REMOVE_PRODUCT')
{
	if ($perms->HavePerm($ownerName, BX_CRM_PERM_NONE, 'WRITE'))
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'PERMISSION_DENIED'));
		die();
	}

	$ID = isset($_POST['ID']) ? intval($_POST['ID']) : 0;
	if($ID <= 0)
	{
		echo CUtil::PhpToJSObject(array('ERROR'=>'ID_NOT_FOUND'));
		die();
	}

	if(!CCrmProductRow::Delete($ID))
	{
		echo CUtil::PhpToJSObject(array('ERROR' => CCrmProductRow::GetLastError()));
	}
	else
	{
		echo CUtil::PhpToJSObject(array('DELETED_PRODUCT_ID' => $ID));
	}
}
die();
?>