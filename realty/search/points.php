<?
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

function get_pm_array ($arElement) 
{	
	if ($arElement["UF_LATITUDE"] > 0 && $arElement["UF_LONGITUDE"] > 0)
	{
		$ID         = $arElement["ID"];
		$arPoint    = array($arElement["UF_LATITUDE"], $arElement["UF_LONGITUDE"]);
		$headerInfo = "<a href='/realty/view/?REQUEST_ID=".$arElement["ID"]."'>".
			$arElement["UF_OBJ_TYPE"]["UF_OBJ_TYPE_NAME"]." ".$arElement["UF_ADDR_STREET"]." &#8470;".
			$arElement["UF_ID"]."</a>";
		$bodyInfo   = "<strong>".$arElement["UF_CITY_ID"].", ".$arElement["UF_ADDR_STREET"]."</strong><br>".
			preg_replace('/\s+/', ' ', $arElement["UF_REKLAMA"]);
		$footerInfo = "Сумма: ".$arElement["UF_PRICE"].", Общая площадь: ".$arElement["UF_TOTAL_SQUARE"];		
		return array($ID, $arPoint, $headerInfo, $bodyInfo, $footerInfo);
	}
	else
		return array();
}
$arRequest = $_POST;
$arSort = array();
$arReturn = array();
$arTimedReturn = array();
$endSearch = "false";
$arSelect = array("ID", "UF_ID", "UF_ADDR_STREET", "UF_CITY_REGION", "UF_CITY_ID", "UF_ADDR_STREET", "UF_PRICE", "UF_OBJ_TYPE",
	"UF_ROOMS", "UF_ETAGE", "UF_ETAGE_COUNT", "UF_TOTAL_SQUARE", "UF_REKLAMA", "UF_LATITUDE", "UF_LONGITUDE");
	
foreach ($arRequest as $key => $requestField)
{
	if (in_array($key, array("update_labels", "delete_not_in_kladr_values", "web_form_submit", "PAGEN_2")))
	{
		unset($arRequest[$key]);
	}
	else
	{
		if (empty($requestField))
		{
			unset($arRequest[$key]);
		}	
	}
}
	
if(isset($arRequest["UF_ID"]) && $arRequest["UF_ID"] > 0)
{
	$dbResult = HlBlockElement::GetList(2, $arSelect, array("UF_ID" => $arRequest["UF_ID"]), array(), 1);
	$endSearch = "true";
}
else
{
	$dbResult = HlBlockElement::GetList(3, array(), array(), array(), 100);
	while($arElement = $dbResult->Fetch()) {
		$obj_type_arr[$arElement["UF_OBJ_TYPE_ID"]] = $arElement["UF_OBJ_TYPE_NAME"];
	}
	foreach ($arRequest as $key => $requestField) 
	{
		if ($key == "sort_field")
		{
			foreach ($requestField as $sortKey => $sortType)
			{
				$arSort[$sortType] = $arRequest["sort_direction"][$sortKey];
			}
			unset($arRequest["sort_field"]);
			unset($arRequest["sort_direction"]);
		}
		else if ($key == "location")
		{
			$arRequest["UF_CITY_ID"] = "%".$requestField."%";	
			unset($arRequest[$key]);
		}
		else if ($key == "street")
		{
			$arRequest["UF_ADDR_STREET"] = "%".$requestField."%";	
			unset($arRequest[$key]);
		}
		else if ((strpos($key, "_FROM") || strpos($key, "_TO")))
		{
			$fieldName = str_replace(array("_FROM", "_TO"), "", $key);
			if ($fieldName == "UF_SQUARE") 
				$fieldName = "UF_TOTAL_SQUARE";
			$fieldName = (strpos($key, "_FROM")) ? (">=" . $fieldName) : ("<=" . $fieldName);
			$arRequest[$fieldName] = $requestField;
			unset($arRequest[$key]);
		}
		else if(strpos($requestField,str_replace("UF_","",$key)."_") !== FALSE)
		{
			$rs = CUserFieldEnum::GetList(array(), array(
				"USER_FIELD_NAME" => $key,"XML_ID"=>$requestField
			));
			if($ar = $rs->Fetch())
			{
				$arRequest[$key]=$ar["ID"];
			}
		}
	}
	$arRequest[">=UF_INNER_STATUS"] = 0;
	$dbResult = HlBlockElement::GetList(2, $arSelect, $arRequest, $arSort, 15);
	$whole_count = $dbResult->SelectedRowsCount();
	if(isset($_POST["PAGEN_2"]) && $whole_count <= 10 * (intval($_POST["PAGEN_2"]))) $endSearch = "true";
}

while($arElement = $dbResult->Fetch())
{
	$dbResultObj = HlBlockElement::GetList(3, array("UF_OBJ_TYPE_NAME", "UF_OBJ_TYPE_CLASS"), array("UF_OBJ_TYPE_ID" => $arElement["UF_OBJ_TYPE"]), array(), 1);
	$arResultObj = $dbResultObj->Fetch();
	$arElement["UF_OBJ_TYPE"] = $arResultObj;
	$arElement["UF_OBJ_TYPE"]["UF_OBJ_TYPE_NAME"] = mb_convert_case($arElement["UF_OBJ_TYPE"]["UF_OBJ_TYPE_NAME"], MB_CASE_LOWER, "UTF-8");
	$arResult[] = $arElement;
}
if (is_array($arResult) && $arResult[0]["ID"] != "")
{
	foreach ($arResult as $arElement)
	{
		$arTimedReturn = get_pm_array($arElement);
		if ($arTimedReturn[0] != "")
			$arReturn[] = $arTimedReturn;
		$arTimedReturn = array();
	}
}
else if ($arResult["ID"] != "")
{
	$arReturn = get_pm_array($arResult);
}
else
	$arReturn = array();
$arReturn["END_SEARCH"] = $endSearch;
echo json_encode($arReturn);
unset($arSelect);
unset($arResult);
unset($arElement);
unset($arTimedReturn);
unset($arReturn);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");?>