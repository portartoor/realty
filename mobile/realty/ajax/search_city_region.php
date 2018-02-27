<?
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$addrIndex = $_REQUEST["UF_ADDR_INDEX"];
$request_region = HlBlockElement::GetList(11,array("UF_KLADR_CITY_REGION"),array("UF_KLADR_INDEX"=>$addrIndex,"!UF_KLADR_CITY_REGION"=>""),Array(),1);
if($request_region_data = $request_region->Fetch())
	$addrIndex=$request_region_data["UF_KLADR_CITY_REGION"];	
else 
	$addrIndex="";
$arr["UF_CITY_REGION"] = $addrIndex;
echo json_encode($arr);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");?>