<?
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
function write_select_kladr($name){
	$filter=Array();
	if($name=="UF_REGION_ID")
	{
		$filter=Array("UF_KLADR_TYPE"=>2,"UF_KLADR_CODE"=>substr($_GET["CODE"],0,8)."%");
	}
	else if($name=="UF_CITY_ID") {
		if($_GET["CODE"]==0)
			$filter=Array("UF_KLADR_TYPE"=>3,"UF_KLADR_CODE"=>"390 000%");
		else
			$filter=Array("UF_KLADR_TYPE"=>Array(3,4),"UF_KLADR_CODE"=>substr($_GET["CODE"],0,8)."%");
	}
	else if($name=="UF_ADDR_STREET") {
		//echo substr($_GET["CODE"],0,12);
		$filter=Array("UF_KLADR_TYPE"=>5,"UF_KLADR_CODE"=>substr($_GET["CODE"],0,12)."%");
	}
	$export_arr = HlBlockElement::GetList(11,array("UF_KLADR_NAME","UF_KLADR_CODE","UF_KLADR_SOKR"),$filter,array(),5000);
	while($arr[] = $export_arr->Fetch()){}
	unset($arr[sizeof($arr)-1]);
	echo json_encode($arr);
}
write_select_kladr($_GET["t"]);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");?>