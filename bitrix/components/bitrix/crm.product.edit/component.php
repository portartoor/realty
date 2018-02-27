<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

if (!CModule::IncludeModule('crm'))
{
	ShowError(GetMessage('CRM_MODULE_NOT_INSTALLED'));
	return;
}

global $USER, $APPLICATION;

$CrmPerms = new CCrmPerms($USER->GetID());
if (!$CrmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
{
	ShowError(GetMessage('CRM_PERMISSION_DENIED'));
	return;
}

$arParams['PATH_TO_PRODUCT_LIST'] = CrmCheckPath('PATH_TO_PRODUCT_LIST', $arParams['PATH_TO_PRODUCT_LIST'], '');
$arParams['PATH_TO_PRODUCT_SHOW'] = CrmCheckPath('PATH_TO_PRODUCT_SHOW', $arParams['PATH_TO_PRODUCT_SHOW'], '?product_id=#product_id#&show');
$arParams['PATH_TO_PRODUCT_EDIT'] = CrmCheckPath('PATH_TO_PRODUCT_EDIT', $arParams['PATH_TO_PRODUCT_EDIT'], '?product_id=#product_id#&edit');

$catalogID = isset($arParams['CATALOG_ID']) ? intval($arParams['CATALOG_ID']) : 0;

$productFields = array();
if(check_bitrix_sessid())
{
	if($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['save']) || isset($_POST['apply'])))
	{
		$productID = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
		if(isset($_POST['NAME']))
		{
			$productFields['NAME'] = trim($_POST['NAME']);
		}

		if(isset($_POST['DESCRIPTION']))
		{
			$productFields['DESCRIPTION'] = $_POST['DESCRIPTION'];
		}

		if(isset($_POST['ACTIVE']))
		{
			$productFields['ACTIVE'] = $_POST['ACTIVE'] == 'Y' ? 'Y' : 'N';
		}

		if(isset($_POST['CURRENCY']))
		{
			$productFields['CURRENCY_ID'] = strval($_POST['CURRENCY']);
		}

		if(isset($_POST['PRICE']))
		{
			$productFields['PRICE'] = round(doubleval($_POST['PRICE']), 2);
		}

		$sectionID = $productFields['SECTION_ID'] = isset($_POST['SECTION']) ? intval($_POST['SECTION']) : 0;

		if(isset($_POST['SORT']))
		{
			$productFields['SORT'] = intval($_POST['SORT']);
		}

		if($productID <= 0)
		{
			// Setup catalog ID for new product
			$productFields['CATALOG_ID'] = $catalogID > 0 ? $catalogID : CCrmCatalog::EnsureDefaultExists();
		}

		$err = '';
		if($productID > 0)
		{
			if(!CCrmProduct::Update($productID, $productFields))
			{
				$err = CCrmProduct::GetLastError();
				if(!isset($err[0]))
				{
					$err = GetMessage('CRM_PRODUCT_UPDATE_UNKNOWN_ERROR');
				}
			}
		}
		else
		{
			$productID = CCrmProduct::Add($productFields);
			if(!$productID)
			{
				$err = CCrmProduct::GetLastError();
				if(!isset($err[0]))
				{
					$err = GetMessage('CRM_PRODUCT_ADD_UNKNOWN_ERROR');
				}
			}
		}

		if(isset($err[0]))
		{
			ShowError($err);
		}
		else
		{
			LocalRedirect(
				isset($_POST['apply'])
				? CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_PRODUCT_EDIT'],
					array('product_id' => $productID)
				)
				: CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_PRODUCT_LIST'],
					array('section_id' => isset($_SESSION['CRM_PRODUCT_LIST_SECTION_ID']) ? intval($_SESSION['CRM_PRODUCT_LIST_SECTION_ID']) : 0)
				)
			);
		}
	}
	elseif ($_SERVER['REQUEST_METHOD'] == 'GET' &&  isset($_GET['delete']))
	{
		$err = '';
		$productID = isset($arParams['PRODUCT_ID']) ? intval($arParams['PRODUCT_ID']) : 0;
		$product = $productID > 0 ? CCrmProduct::GetByID($productID) : null;
		if($product)
		{
			if(!CCrmProduct::Delete($productID))
			{
				$err = CCrmProduct::GetLastError();
				if(!isset($err[0]))
				{
					$err = GetMessage('CRM_PRODUCT_DELETE_UNKNOWN_ERROR');
				}
			}
		}

		if(isset($err[0]))
		{
			ShowError($err);
		}
		else
		{
			LocalRedirect(
				CComponentEngine::MakePathFromTemplate(
					$arParams['PATH_TO_PRODUCT_LIST'],
					array('section_id' => isset($product['SECTION_ID']) ? $product['SECTION_ID'] : 0)
				)
			);
		}
	}
}

$productID = isset($arParams['PRODUCT_ID']) ? intval($arParams['PRODUCT_ID']) : 0;
if($productID <= 0)
{
	$productIDParName = isset($arParams['PRODUCT_ID_PAR_NAME']) ? strval($arParams['PRODUCT_ID_PAR_NAME']) : '';
	if(strlen($productIDParName) == 0)
	{
		$productIDParName = 'product_id';
	}

	$productID = isset($_REQUEST[$productIDParName]) ? intval($_REQUEST[$productIDParName]) : 0;
}

$product = array();
if($productID > 0)
{
	if(!($product = CCrmProduct::GetByID($productID)))
	{
		ShowError(GetMessage('CRM_PRODUCT_NOT_FOUND'));
		@define('ERROR_404', 'Y');
		if($arParams['SET_STATUS_404'] === 'Y')
		{
			CHTTP::SetStatus("404 Not Found");
		}
		return;
	}
}

if(isset($productFields['NAME']))
{
	$product['NAME'] = $productFields['NAME'];
}

if(isset($productFields['DESCRIPTION']))
{
	$product['DESCRIPTION'] = $productFields['DESCRIPTION'];
}

if(isset($productFields['ACTIVE']))
{
	$product['ACTIVE'] = $productFields['ACTIVE'];
}

if(isset($productFields['CURRENCY_ID']))
{
	$product['CURRENCY_ID'] = $productFields['CURRENCY_ID'];
}

if(isset($productFields['PRICE']))
{
	$product['PRICE'] = $productFields['PRICE'];
}

if(isset($productFields['SECTION_ID']))
{
	$product['SECTION_ID'] = $productFields['SECTION_ID'];
}

if(isset($productFields['SORT']))
{
	$product['SORT'] = $productFields['SORT'];
}

$arResult['PRODUCT_ID'] = $productID;
$arResult['PRODUCT'] = $product;
$isEditMode = $productID > 0;

$arResult['CATALOG_ID'] = $catalogID =
	isset($product['CATALOG_ID'])
		? intval($product['CATALOG_ID'])
		: CCrmCatalog::EnsureDefaultExists();

$arResult['FORM_ID'] = 'CRM_PRODUCT_EDIT';
$arResult['GRID_ID'] = 'CRM_PRODUCT_LIST';
$arResult['BACK_URL'] = CComponentEngine::MakePathFromTemplate(
	$arParams['PATH_TO_PRODUCT_LIST'],
	array('section_id' => isset($product['SECTION_ID']) ? intval($product['SECTION_ID']) : 0)
	);

$arResult['FIELDS'] = array();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'product_info',
	//'name' => GetMessage('CRM_SECTION_PRODUCT_INFO'),
	'type' => 'section'
);

if($isEditMode)
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'ID',
		'name' => 'ID',
		'params' => array('size' => 50),
		'value' => $product['ID'],
		'type' => 'label'
	);
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'NAME',
	'name' => GetMessage('CRM_FIELD_NAME'),
	'params' => array('size' => 50),
	'type' => 'text',
	'value' => isset($product['NAME']) ? $product['NAME'] : '',
	'required' => true
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'DESCRIPTION',
	'name' => GetMessage('CRM_FIELD_DESCRIPTION'),
	'params' => array('size' => 50),
	'type' => 'textarea',
	'value' => isset($product['DESCRIPTION']) ? $product['DESCRIPTION'] : ''
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ACTIVE',
	'name' => GetMessage('CRM_FIELD_ACTIVE'),
	'type' => 'checkbox',
	'params' => array(),
	'value' => isset($product['ACTIVE']) ? $product['ACTIVE'] : ($isEditMode ? 'N' : 'Y')
);


$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'CURRENCY',
	'name' => GetMessage('CRM_FIELD_CURRENCY'),
	'type' => 'list',
	'items' => CCrmCurrencyHelper::PrepareListItems(),
	'value' => isset($product['CURRENCY_ID']) ? $product['CURRENCY_ID'] : ''
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'PRICE',
	'name' => GetMessage('CRM_FIELD_PRICE'),
	'type' => 'text',
	'params' => array(),
	'value' => isset($product['PRICE']) ? strval(round(doubleval($product['PRICE']), 2)) : ''
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SECTION',
	'name' => GetMessage('CRM_FIELD_SECTION'),
	'type' => 'list',
	'items' => CCrmProductHelper::PrepareSectionListItems($catalogID, true),
	'value' => isset($product['SECTION_ID']) ? $product['SECTION_ID'] : ''
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SORT',
	'name' => GetMessage('CRM_FIELD_SORT'),
	'type' => 'text',
	'params' => array(),
	'value' => isset($product['SORT']) ? $product['SORT'] : '100'
);

$this->IncludeComponentTemplate();
include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.product/include/nav.php');
?>