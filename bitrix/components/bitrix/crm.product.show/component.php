<?if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true ) die();

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

$arParams['PATH_TO_PRODUCT_LIST'] = CrmCheckPath('PATH_TO_PRODUCT_LIST', $arParams['PATH_TO_PRODUCT_LIST'], '');

//CUtil::InitJSCore(array('ajax', 'tooltip'));

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

$arResult['PRODUCT_ID'] = $productID;
$product = $productID > 0 ? CCrmProduct::GetByID($productID) : false;

if(!$product)
{
	ShowError(GetMessage('CRM_PRODUCT_NOT_FOUND'));
	@define('ERROR_404', 'Y');
	if($arParams['SET_STATUS_404'] === 'Y')
	{
		CHTTP::SetStatus("404 Not Found");
	}
	return;
}

$product = CCrmProduct::GetByID($productID);
$arResult['PRODUCT'] = $product;

$arResult['FORM_ID'] = 'CRM_PRODUCT_SHOW';
$arResult['GRID_ID'] = 'CRM_PRODUCT_LIST';

$arResult['FIELDS'] = array();
$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'section_product_info',
	'name' => GetMessage('CRM_SECTION_PRODUCT_INFO'),
	'type' => 'section'
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ID',
	'name' => 'ID',
	'type' => 'label',
	'params' => array('size' => 50),
	'value' => $product['ID']
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'NAME',
	'name' => GetMessage('CRM_FIELD_NAME'),
	'params' => array('size' => 50),
	'type' => 'label',
	'value' => isset($product['NAME']) ? $product['NAME'] : ''
);

if(isset($product['DESCRIPTION']) && strlen($product['DESCRIPTION']) > 0)
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'DESCRIPTION',
		'name' => GetMessage('CRM_FIELD_DESCRIPTION'),
		'params' => array('size' => 50),
		'type' => 'label',
		'value' => $product['DESCRIPTION']
	);
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'ACTIVE',
	'name' => GetMessage('CRM_FIELD_ACTIVE'),
	'type' => 'label',
	'params' => array(),
	'value' => GetMessage(isset($product['ACTIVE']) && $product['ACTIVE'] == 'Y' ? 'MAIN_YES' : 'MAIN_NO')
);

$price = CCrmProduct::FormatPrice($product);
if(strlen($price) > 0)
{
	$arResult['FIELDS']['tab_1'][] = array(
		'id' => 'PRICE',
		'name' => GetMessage('CRM_FIELD_PRICE'),
		'type' => 'label',
		'params' => array(),
		'value' => $price
	);
}

$productSectionID = isset($product['SECTION_ID']) ? $product['SECTION_ID'] : 0;
$productSectionName = '';
if($productSectionID > 0)
{
	$sectionListItems = array();
	$rsSection = CIBlockSection::GetByID($productSectionID);
	if($arSection = $rsSection->Fetch())
	{
		$productSectionName = $arSection['NAME'];
	}
}

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SECTION',
	'name' => GetMessage('CRM_FIELD_SECTION'),
	'type' => 'label',
	'value' => htmlspecialcharsbx($productSectionName)
);

$arResult['FIELDS']['tab_1'][] = array(
	'id' => 'SORT',
	'name' => GetMessage('CRM_FIELD_SORT'),
	'type' => 'label',
	'params' => array(),
	'value' => isset($product['SORT']) ? $product['SORT'] : ''
);

$this->IncludeComponentTemplate();
include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.product/include/nav.php');
?>