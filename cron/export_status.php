<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Экспорт");
$Sip = new SoapPortalInvent();
$order = Array("UF_REQUESTS_ID","UF_AGENT","UF_DATE_CLOSE","UF_CLOSE_REASON","UF_COMMENT");
 
$name = "REQUESTS_STATUS";
$hblock = 16;
$date = date("d.m.Y");
$dateWeekAgo = date("d.m.Y", strtotime("-6 week"));

$xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><$name/>");
$rsData = CUserTypeEntity::GetList( array(), array("ENTITY_ID"=>"HLBLOCK_16") );
$arrType_h16=Array();
while($arRes = $rsData->Fetch())
{
	$arrType_h16[$arRes["FIELD_NAME"]]=$arRes["USER_TYPE_ID"];
}
$arr_o = Array(">=UF_DATE_CREATE"=>$dateWeekAgo." 00:00:00", "<=UF_DATE_CREATE"=>$date." 23:59:59");	
if(isset($_GET["REQUEST_ID"]))
{
	$REQUEST_ID=$_GET["REQUEST_ID"];
	$arr_o = Array("UF_REQUEST_HL_ID"=>$REQUEST_ID);	
}
else $REQUEST_ID=0;
$export_arr = HlBlockElement::GetList($hblock,array(),$arr_o,array("ID"=>"DESC"),($REQUEST_ID==0)?100:1);
while($arr = $export_arr->Fetch()){
	$arr_ex_res=Array();
	//print_r($arr);
	$r = $xml->addChild('item');
	foreach($arr as $k=>$v)
	{
		if($arrType_h16[$k]=="enumeration")
		{
			$rs = CUserFieldEnum::GetList(array(), array(
				"USER_FIELD_NAME" => $k,
				"ID" => $v
			));
			if($ar = $rs->GetNext())
			{
				if(in_array($k,array("UF_CATEGORY")))
					$v=$ar["VALUE"];
				else
				{
					$v=$ar["XML_ID"];
					$s=str_replace("UF_","",$k);
					$v=str_replace($s."_","",$v);
					if($k=="UF_SOURCE")
					{
						$v="00-".str_pad($v,8,"0",STR_PAD_LEFT);
						//echo $v;
					}
					else if($k=="UF_GAS" || $k=="UF_MATERIAL" || $k=="UF_LOGGIA")
						$v=intval($v);
					else
						$v=str_pad($v,9,"0",STR_PAD_LEFT);
				}
			}
		}
		
		if(in_array($k,array("ID","UF_ID","UF_REQUEST_HL_ID")))continue;

		if($k=="UF_REQUEST_ID")
		{
			$v=str_pad($v,9,"0",STR_PAD_LEFT);
			$r->addChild("UF_ID",$v);
		}
		else
		{
			if($k=="UF_REALTY_TYPE")
				$v=$realty_type_arr[$v];
			else if($k=="UF_OBJ_TYPE")
				$v=str_pad($v,9,"0",STR_PAD_LEFT);
			else if($k=="UF_GOAL")
			{
				if($v==0)
					$v="";
				else
					$v=str_pad($v,9,"0",STR_PAD_LEFT);
			}	
			if($arrType_h16[$k]=="boolean")
			{
				$v=($v==1)?"Да":"Нет";
			}
			if($k=="UF_ELECTICITY")$k="UF_ELECTRICITY";
			if($k=="UF_OBJ_TYPE")$k="UF_OBJECT_TYPE";
			if(in_array(strtolower($k),$order))
					$r->addChild(strtolower($k),$v);
			else
				$r->addChild($k,$v);
		}
	}
}
file_put_contents('../upload/'.$name.'.xml',$xml->saveXML());
echo "<pre>";
print_r($Sip->ChangeCategoryApplication(base64_encode($xml->saveXML())));
echo "</pre>";
echo "<a target=\"_blank\" href=\"/upload/".$name.".xml\">Файл выгрузки</a>";
	?>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>