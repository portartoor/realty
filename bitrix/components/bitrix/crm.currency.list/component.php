<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

global $USER, $APPLICATION;

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arResult['CAN_DELETE'] = $arResult['CAN_EDIT'] = $CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');

$arParams['PATH_TO_CURRENCY_LIST'] = CrmCheckPath('PATH_TO_CURRENCY_LIST', $arParams['PATH_TO_CURRENCY_LIST'], '');
$arParams['PATH_TO_CURRENCY_SHOW'] = CrmCheckPath('PATH_TO_CURRENCY_SHOW', $arParams['PATH_TO_CURRENCY_SHOW'], '?currency_id=#currency_id#&show');
$arParams['PATH_TO_CURRENCY_ADD'] = CrmCheckPath('PATH_TO_CURRENCY_ADD', $arParams['PATH_TO_CURRENCY_ADD'], '?add');
$arParams['PATH_TO_CURRENCY_EDIT'] = CrmCheckPath('PATH_TO_CURRENCY_EDIT', $arParams['PATH_TO_CURRENCY_EDIT'], '?currency_id=#currency_id#&edit');

$arResult['GRID_ID'] = 'CRM_CURRENCY_LIST';
$arResult['FORM_ID'] = isset($arParams['FORM_ID']) ? $arParams['FORM_ID'] : '';
$arResult['TAB_ID'] = isset($arParams['TAB_ID']) ? $arParams['TAB_ID'] : '';

$arResult['HEADERS'] = array(
	array('id' => 'ID', 'name' => GetMessage('CRM_COLUMN_ID'), 'sort' => 'ID', 'default' => true, 'editable' => false),
	array('id' => 'NAME', 'name' => GetMessage('CRM_COLUMN_NAME'), 'sort' => 'NAME', 'default' => true, 'editable' => false, 'params' => array('size' => 60)),
	array('id' => 'SORT', 'name' => GetMessage('CRM_COLUMN_SORT'), 'sort' => 'SORT', 'default' => true, 'editable' => true),
	array('id' => 'EXCH_RATE', 'name' => GetMessage('CRM_COLUMN_EXCH_RATE'), 'sort' => false, 'default' => true, 'editable' => true),
	array('id' => 'ACCOUNTING', 'name' => GetMessage('CRM_COLUMN_ACCOUNTING'), 'sort' => false, 'default' => true, 'editable' => true, 'type'=>'checkbox')
);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && check_bitrix_sessid() && isset($_POST['action_button_'.$arResult['GRID_ID']]))
{
	$action = $_POST['action_button_'.$arResult['GRID_ID']];
	if($arResult['CAN_DELETE'] && $action === 'delete')
	{
		$deleteAll = $_POST['action_all_rows_'.$arResult['GRID_ID']] == 'Y';
		$IDs = !$deleteAll ? $_POST['ID'] : array();
		$allCurrencies = CCrmCurrency::GetAll();
		foreach($allCurrencies as $arCurrency)
		{
			$currencyID = $arCurrency['CURRENCY'];
			if(!$deleteAll && !in_array($currencyID, $IDs, true))
			{
				continue;
			}

			if(!CCrmCurrency::Delete($currencyID))
			{
				$error = CCrmCurrency::GetLastError();
				ShowError(isset($error[0]) ? $error : GetMessage('CRM_CURRENCY_DELETION_GENERAL_ERROR'));
			}
		}

		unset($_POST['ID'], $_REQUEST['ID']); // otherwise the filter will work
	}
	elseif($arResult['CAN_EDIT'] && $action === 'edit' && isset($_POST['FIELDS']) && is_array($_POST['FIELDS']))
	{
		foreach($_POST['FIELDS'] as $ID => $arField)
		{
			$arFields = array();
			if(isset($arField['EXCH_RATE']))
			{
				$arFields['AMOUNT'] = $arField['EXCH_RATE'];
			}

			if(isset($arField['SORT']))
			{
				$arFields['SORT'] = $arField['SORT'];
			}

			if (count($arFields) > 0)
			{
				if(!CCrmCurrency::Update($ID, $arFields))
				{
					$error = CCrmCurrency::GetLastError();
					ShowError(isset($error[0]) ? $error : GetMessage('CRM_CURRENCY_UPDATE_GENERAL_ERROR'));
					return;
				}
			}

			if(isset($arField['ACCOUNTING']))
			{
				$baseCurrencyID = CCrmCurrency::GetBaseCurrencyID();
				$accountCurrencyID = CCrmCurrency::GetAccountCurrencyID();
				if($ID === $accountCurrencyID)
				{
					if($arField['ACCOUNTING'] === 'N' && $ID !== $baseCurrencyID)
					{
						CCrmCurrency::SetAccountCurrencyID($baseCurrencyID);
					}
				}
				elseif($arField['ACCOUNTING'] === 'Y')
				{
					CCrmCurrency::SetAccountCurrencyID($ID);
				}
			}
		}
	}

	if(!isset($_POST['AJAX_CALL']))
	{
		LocalRedirect($APPLICATION->GetCurPage());
	}
}
elseif ($_SERVER['REQUEST_METHOD'] == 'GET' && check_bitrix_sessid() && isset($_GET['action_'.$arResult['GRID_ID']]))
{
	if ($arResult['CAN_DELETE'] && $_GET['action_'.$arResult['GRID_ID']] === 'delete')
	{
		$currencyID = isset($_GET['ID']) ? $_GET['ID'] : '';
		if(isset($currencyID[0]))
		{
			if(!CCrmCurrency::Delete($currencyID))
			{
				$error = CCrmCurrency::GetLastError();
				ShowError(isset($error[0]) ? $error : GetMessage('CRM_CURRENCY_DELETION_GENERAL_ERROR'));
				return;
			}
		}
		unset($_GET['ID'], $_REQUEST['ID']); // otherwise the filter will work
	}

	if (!isset($_GET['AJAX_CALL']))
	{
		LocalRedirect($bInternal ? '?'.$arParams['FORM_ID'].'_active_tab=tab_product' : '');
	}
}

$gridOptions = new CCrmGridOptions($arResult['GRID_ID']);

$gridSorting = $gridOptions->GetSorting(
	array(
		'sort' => array('SORT' => 'asc'),
		'vars' => array('by' => 'by', 'order' => 'order')
	)
);

$sort = $arResult['SORT'] = $gridSorting['sort'];
$arResult['SORT_VARS'] = $gridSorting['vars'];

//if (!isset($arParams['CURRENCY_COUNT']))
//{
//	$arParams['CURRENCY_COUNT'] = 20;
//}

$accountCurrencyID = CCrmCurrency::GetAccountCurrencyID();
$currencies = array();

$allCurrencies = CCrmCurrency::GetAll();
foreach($allCurrencies as $k => $v)
{
	$currency = array();
	$currency['ID'] = $k; // Key is Currency ID

	$currency['NAME'] = $v['FULL_NAME'];
	$currency['SORT'] = $v['SORT'];
	$currency['EXCH_RATE'] = $v['AMOUNT']; //Default Exchange Rate
	$currency['ACCOUNTING'] = $k === $accountCurrencyID ? 'Y' : 'N';

	$currency['PATH_TO_CURRENCY_SHOW'] =
		CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_CURRENCY_SHOW'],
			array('currency_id' => $k)
		);

	$currency['PATH_TO_CURRENCY_EDIT'] =
		CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_CURRENCY_EDIT'],
			array('currency_id' => $k)
		);

	$currency['PATH_TO_CURRENCY_DELETE'] =
		CHTTP::urlAddParams(
			CComponentEngine::MakePathFromTemplate(
				$arParams['PATH_TO_CURRENCY_LIST'],
				array('currency_id' => $k)
			),
			array('action_'.$arResult['GRID_ID'] => 'delete', 'ID' => $k, 'sessid' => bitrix_sessid())
		);

	$currency['~ID'] = $k;
	$currency['~NAME'] = htmlspecialcharsBack($currency['NAME']);
	$currency['~SORT'] = $currency['SORT'];
	$currency['~EXCH_RATE'] = $currency['EXCH_RATE'];
	$currency['~ACCOUNTING'] = $currency['ACCOUNTING'];

	$currencies[] = $currency;
}


if(is_array($sort) && count($sort) > 0)
{
	// Process only first expression
	reset($sort);
	$by = key($sort);
	$order = $sort[$by];

	// Sort 'int' type fields (SORT)
	if($by === 'SORT')
	{
		usort(
			$currencies,
			create_function(
				'$a,$b',
				$order === 'asc'
					? "return \$a['SORT'] > \$b['SORT'];"
					: "return \$b['SORT'] > \$a['SORT'];"
			)
		);
	}
	else
	{
		// Sort 'string' type fields (ID, NAME)
		usort(
			$currencies,
			create_function(
				'$a,$b',
				$order === 'asc'
					? "return strcmp(\$a['$by'],\$b['$by']);"
					: "return strcmp(\$b['$by'],\$a['$by']);"
			)
		);
	}
}

$arResult['CURRENCIES'] = array();
$rowCount = $arResult['ROWS_COUNT'] = count($currencies);
for($i = 0; $i < $rowCount; $i++)
{
	$currency = $currencies[$i];
	$arResult['CURRENCIES'][$currency['ID']] = $currency;
}

//$arResult['FILTER'] =
//	array(
//		array(
//			'id' => 'ID',
//			'name' => GetMessage('CRM_COLUMN_ID')
//		),
//		array(
//			'id' => 'NAME',
//			'name' => GetMessage('CRM_COLUMN_NAME')
//		)
//	);



$this->IncludeComponentTemplate();