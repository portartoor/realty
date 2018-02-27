<?
define("NO_KEEP_STATISTIC", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require($_SERVER["DOCUMENT_ROOT"]."/libs/realty_class.php");
require($_SERVER["DOCUMENT_ROOT"]."/libs/rights_class.php");
$Project = new Rights();
$data_res = $Project->get_requests_file();
$postfix  = $Project->get_postfix();			
$request = HlBlockElement::GetList($data_res["hblock"],array(),array("ID"=>$_GET["REQUEST_ID"]),array(),1);
$request_data = $request->Fetch();
foreach($request_data as $k=>$v)
{
	$request_data[str_replace($postfix,"",$k)]=$v;
}
$ty = Helper_realty::get_array_for_filter(1);
$filter = Array();
foreach($ty as $k4=>$v4)
{
	if(in_array($v4,Array("UF_CLIENT_PRICE","UF_PRICE","UF_NARUZHN_REKLAMA","UF_N_REKLAMA","UF_WWW","UF_REKLAMA","UF_ADDR_STREET","UF_COMMENT_ORDER","UF_ADDR_HOUSE","UF_ADDR_FLAT","UF_CONTRAGENT","UF_SOURCE","UF_STATUS","UF_ETAGE","UF_ETAGE_COUNT","UF_TOTAL_SQUARE","UF_LIVING_SQUARE",
	"UF_KITCHEN_SQUARE","UF_SANUSEL_TYPE","UF_MATERIAL","UF_WATER","UF_HEATING")))continue;
	{
		//echo $v4."<br>";
		if($Project->s_name=="domofey"&&$v4=="UF_LAND_OWNER_TYPE")
		{
			$v4="UF_LAND_O_TYPE";
		}
		$filter[$v4]=$request_data[$v4];
	}
}
$filter["!ID"] = $request_data["ID"];
$filter["UF_CITY_REGION"] = $request_data["UF_CITY_REGION"];

$filter = $Project->add_postfix_to_fields($filter);
$dbResult = HlBlockElement::GetList($data_res["hblock"],array("UF_CLIENT_PRICE".$postfix,"UF_TOTAL_SQUARE".$postfix),$filter,array(),100);
$count = 0;
$sum = 0;
while($arElement = $dbResult->Fetch()) {
	if($arElement["UF_CLIENT_PRICE".$postfix]>0)
	{
		$sum = $sum + $arElement["UF_CLIENT_PRICE".$postfix]/$arElement["UF_TOTAL_SQUARE".$postfix];
		$count++;
	}
}
if($count>0)
	$sum = round($sum*$request_data["UF_TOTAL_SQUARE".$postfix]/$count);
die(number_format ($sum, 0 , "."," "));
?>