<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("");
?><?
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require($_SERVER["DOCUMENT_ROOT"]."/libs/realty_class.php");
$rsData = CUserTypeEntity::GetList( array(), array("ENTITY_ID"=>"HLBLOCK_2") );
$ar_help=Array();
while($arRes = $rsData->Fetch())
{
	array_push($ar_help,$arRes["ID"]);
}
$op_type = array();
$arr_all = Array();
foreach($ar_help as $field_id)
{
	$rrr = CUserTypeEntity::GetByID($field_id);
	$arr_all[$rrr["FIELD_NAME"]] =  $rrr["EDIT_FORM_LABEL"]["ru"];
	if($rrr["FIELD_NAME"]=="UF_OPERATION_TYPE"){
		
		$rs = CUserFieldEnum::GetList(array(), array("USER_FIELD_NAME" => $rrr["FIELD_NAME"]));
			while($ar = $rs->GetNext())
			{
				$op_type[$ar["ID"]]=$ar["VALUE"];
			}
	}
}
$ob_type=Array();
$name = "OBJ_TYPE";
$export_arr = HlBlockElement::GetList(3,array(),array(),array(),100);
$old_value = 0;
$ins = 0;
while($arr = $export_arr->Fetch()){
	$ob_type[$arr["UF_".$name."_ID"]]=$arr["UF_".$name."_NAME"];
}
		
$re_type = Array(0=>"Не коммерческая недвижимость",6=>"Коммерческая недвижимость");
?>
<div style="font-size:18px;">
<?
foreach($re_type as $k1=>$v1)
{
	$request_data["UF_REALTY_TYPE"]=$k1;	
	foreach($op_type as $k2=>$v2)
	{
		$request_data["UF_OPERATION_TYPE"]=$k2;	
		
		foreach($ob_type as $k3=>$v3)
		{
			echo "<h3>".$v1."</h3><br>";
			echo "<h3>".$v2."</h3><br>";
			echo "<h3>".$v3."</h3><br>";
			$request_data["UF_OBJ_TYPE"]=$k3;
			$ty = Helper_realty::get_array_for_filter(1);
			foreach($ty as $k4=>$v4)
			{
	if(in_array($v4,Array("UF_CLIENT_PRICE","UF_PRICE","UF_NARUZHN_REKLAMA","UF_WWW","UF_REKLAMA","UF_ADDR_STREET","UF_COMMENT_ORDER","UF_OBJ_TYPE","UF_REALTY_TYPE","UF_OPERATION_TYPE","UF_ADDR_HOUSE","UF_ADDR_FLAT","UF_CONTRAGENT","UF_SOURCE","UF_STATUS")))continue;
				echo $v4." ".$arr_all[$v4]."<br>";
			}
			echo "<br>______________________________________________________<br>";
		}
	}
}		
//print_r($arr_all);
/*$ty = Helper_realty::get_array_for_filter(1);*/
?>
</div><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>