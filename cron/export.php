<?
//require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Экспорт");

$Sip = new SoapPortalInvent();

$order = Array("uf_source","uf_operation_type","uf_status","uf_realty_type","uf_object_type","uf_contragent","uf_agent",
	  "uf_addr_block","uf_addr_flat","uf_add_date","uf_update_date","uf_etage","uf_etage_count","uf_total_square",
	  "uf_living_square","uf_kitchen_square","uf_rooms","uf_price","uf_remont_status","uf_sanusel_type",
	  "uf_water","uf_heating","uf_reklama","uf_www","uf_comment","uf_garage_square","uf_cellar_square","uf_lot_square",
	  "uf_gas","uf_land_owner_type","uf_naruzhn_reklama","uf_house_type","uf_entrance","uf_construct_perm","uf_owner_rights",
	  "uf_goal","uf_entry","uf_electricity","uf_distance","uf_latitude","uf_longitude","uf_balcony","uf_phone","uf_internet",
      "uf_furniture","uf_refrigerator","uf_washing_machine","uf_parking","uf_loggia");
 
$all_upd = Array (
	0 => Array(
		"type" => "uf",
		"file" => "uf_source.xls",
		"name" => "SOURCE",
		"hblock" => 2),
	1 => Array(
		"type" => "uf",
		"file" => "uf_status.xls",
		"name" => "STATUS",
		"hblock" => 2),
	2 => Array(
		"type" => "uf",
		"file" => "uf_operation_type.xls",
		"name" => "OPERATION_TYPE",
		"hblock" => 2),
	3 => Array(
		"type" => "hblock",
		"file" => "uf_realty_type.xls",
		"name" => "REALTY_TYPE",
		"hblock" => 4),
	4 => Array(
		"type" => "hblock",
		"file" => "objtype.xls",
		"name" => "OBJ_TYPE",
		"hblock" => 3),
	5 => Array(
		"type" => "hblock",
		"file" => "",
		"name" => "REQUESTS",
		"hblock" => 2),
	6 => Array(
		"type" => "hblock",
		"file" => "goal.xls",
		"name" => "GOAL",
		"hblock" => 12),
);

$i=5;
if(isset($_GET["REQUEST_ID"]))
{
	$i=5;
}
$data=0;
if(isset($_GET["data"]))
{
	$i=5;
	$data= $_GET["data"];
}
$structure = 0;
$type=$all_upd[$i]["type"];
$file = $all_upd[$i]["file"];
$name= $all_upd[$i]["name"];
$hblock= $all_upd[$i]["hblock"];
$xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><$name/>");
if($type=="hblock")
{
	if($structure==1&&$i==5)
	{
		$rsData = CUserTypeEntity::GetList( array(), array("ENTITY_ID"=>"HLBLOCK_".$hblock) );
		while($arRes = $rsData->Fetch())
		{
			if($arRes["USER_TYPE_ID"]=="enumeration")
			{
				$tmp_name = str_replace("UF_","",$arRes["FIELD_NAME"]);
				$xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?><".$tmp_name."/>");
				$rs = CUserFieldEnum::GetList(array(), array(
					"USER_FIELD_NAME" => $arRes["NAME"],
					"USER_FIELD_ID" => $arRes["ID"]
				));
				while($ar = $rs->GetNext())
				{
					$r = $xml->addChild('item');
					echo"<xmp>";print_r($ar);echo"</xmp><br>";
					$r->addChild("XML_ID",intval(str_replace($tmp_name."_","",$ar["XML_ID"])));
					$r->addChild("VALUE",$ar["VALUE"]);
				}
				file_put_contents('../upload/'.$tmp_name.'.xml',$xml->saveXML());
			}
		}
	}
	else if($i==6)
	{
		$realty_type_arr = Array();
		$realty_type_a = HlBlockElement::GetList(4,array());
		while($arr = $realty_type_a->Fetch()){
			$realty_type_arr[$arr["UF_REALTY_TYPE_ID"]]=$arr["UF_REALTY_TYPE_NAME"];
		}
		$export_arr = HlBlockElement::GetList($hblock,array(),Array(),Array(),10000);
		while($ar = $export_arr->Fetch()){
			print_R($ar);
			$r = $xml->addChild('item');
			$r->addChild("XML_ID",str_pad(intval(str_replace($tmp_name."_","",$ar["UF_GOAL_ID"])),9,"0",STR_PAD_LEFT));
			$r->addChild("VALUE",$ar["UF_GOAL_NAME"]);
			$r->addChild("PARENT",str_pad($ar["UF_GOAL_PARENT"],9,"0",STR_PAD_LEFT));
		}
	}
	else if($i==5)
	{
		$rsData = CUserTypeEntity::GetList( array(), array("ENTITY_ID"=>"HLBLOCK_2") );
		$arrType_h2=Array();
		while($arRes = $rsData->Fetch())
		{
			$arrType_h2[$arRes["FIELD_NAME"]]=$arRes["USER_TYPE_ID"];
		}
		
		
		$realty_type_arr = Array();
		$realty_type_a = HlBlockElement::GetList(4,array());
		while($arr = $realty_type_a->Fetch()){
			$realty_type_arr[$arr["UF_REALTY_TYPE_ID"]]=$arr["UF_REALTY_TYPE_NAME"];
		}
		$arr_o = Array();
		if($data!=0&&!isset($_GET["REQUEST_ID"]))
		{
			$arr_o = Array(">=UF_UPDATE_DATE"=>$data." 00:00:00",
			"<=UF_UPDATE_DATE"=>$data." 23:59:59",">UF_INNER_STATUS"=>1,"!UF_AGENT"=>"new_user_%");
		}
		else if(isset($_GET["REQUEST_ID"]))
		{
			$arr_o = Array("ID"=>$_GET["REQUEST_ID"]);
		}
		else {
			$arr_o = Array(/*"ID"=>274*/);
		}
		$export_arr = HlBlockElement::GetList($hblock,array(),$arr_o,array(),1000);
		while($arr = $export_arr->Fetch()){
			$arr_ex_res=Array();
			//print_r($arr);
			$r = $xml->addChild('item');
			foreach($arr as $k=>$v)
			{
				
				if($arrType_h2[$k]=="enumeration"&&$v!="")
				{
					$rs = CUserFieldEnum::GetList(array(), array(
						"USER_FIELD_NAME" => $k,
						"ID" => $v
					));

					if($ar = $rs->GetNext())
					{
						if(in_array($k,array("UF_STATUS","UF_REALTY_TYPE","UF_CONSTRUCT_PERM","UF_ENTRANCE","UF_OPERATION_TYPE","UF_LAND_OWNER_TYPE","UF_ENTRY")))
							$v=$ar["VALUE"];
						else
						{	echo $k." ";
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
					else
					{
						$v="";
					}
				}
				
				if(in_array($k,array("ID","UF_ID"/*,"UF_ADDR_HOUSE"*/,"UF_ADDR_INDEX","UF_CITY_REGION","UF_REGION_ID","UF_INNER_STATUS","UF_REQUESTS_ID","UF_CITY_ID","UF_ADDR_STREET")))continue;

				if($k=="UF_PHOTO_PREVIEW")
				{
					$v="http://portal.invent-realty.ru".CFile::GetPath($v);
				}
				if($k=="UF_PHOTOS"||$k=="UF_DOCS"||$k=="UF_PLAN_PHOTOS")
				{
					$l = $r->addChild($k);
					$f=0;
					$str="";
					foreach($v as $x=>$y)
					{
						$f++;
						$l->addChild("item","http://portal.invent-realty.ru".CFile::GetPath($y));
					}
				}
				else if($k=="UF_CONTRAGENT")
				{
					if(strpos($v,"new_client_")!==FALSE)
					{
						$l = $r->addChild(strtolower($k));
						$ls = $l->addChild("new");
						$client = HlBlockElement::GetList(10,array(),Array("UF_ID_1C"=>$v));
						$client_arr = $client->GetNext();
						unset($client_arr["ID"]);
						unset($client_arr["UF_CLIENT_STATUS"]);
						foreach($client_arr as  $b=>$m)
						{
							if(strpos($b,"~")!==FALSE||$b=="UF_ID_1C")continue;
							$ls->addChild($b,$m);
						}
					}
					else
					{
						if(isset($order[strtolower($k)]))
							$r->addChild(strtolower($k),$v);
						else
							$r->addChild($k,$v);
					}
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
					if($arrType_h2[$k]=="boolean")
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
	}
}
if($type=="uf")
{
	$arFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields("HLBLOCK_".$hblock);
	if(array_key_exists("UF_".$name, $arFields))
	{
		$FIELD_ID = $arFields["UF_".$name]["ID"];
		$rs = CUserFieldEnum::GetList(array(), array(
            "USER_FIELD_NAME" => "UF_".$name,
        ));
        while($ar = $rs->GetNext())
        {
			$r = $xml->addChild('item');
			$r->addChild("XML_ID",intval(str_replace($name."_","",$ar["XML_ID"])));
			$r->addChild("VALUE",$ar["VALUE"]);
			//echo $ar["XML_ID"]." ".$ar["VALUE"];
		}
	}
}
//print $xml->asXML();	
/*
$dom = new DOMDocument('1.0');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($xml->asXML());
echo $dom->saveXML();*/
/*$xml->preserveWhiteSpace = false;
$xml->formatOutput = true;*/
if($data!=0)
{
	file_put_contents('../upload/'.$name."_".$data.'.xml',$xml->saveXML());
	echo "<a target=\"_blank\" href=\"/upload/".$name."_".$data.".xml\">Файл выгрузки</a>";
}
else
{
	file_put_contents('../upload/'.$name."_".$_GET["REQUEST_ID"].'.xml',$xml->saveXML());

	if($i == 5){
	/*	echo "<pre>";
		print_r($Sip->NewApplication(base64_encode($xml->saveXML())));
		echo "</pre>";
		echo "SOAP OK";*/
	}
	echo "<a target=\"_blank\" href=\"/upload/".$name."_".$_GET["REQUEST_ID"].".xml\">Файл выгрузки</a>";
}	
	?>
	
<?
/*$PHPExcel_file = PHPExcel_IOFactory::load("load/".$file);
foreach ($PHPExcel_file->getWorksheetIterator() as $worksheet) {
	$rows_count = $worksheet->getHighestRow();
	$columns_count=1; 
	
	if($type=="hblock")
	{
		for ($row = 2; $row <= $rows_count; $row++) {
			$value_str = "";
			for ($column = 0; $column < $columns_count; $column++) {
				$cell = $worksheet->getCellByColumnAndRow($column, $row);
				if($cell->getCalculatedValue()=="")break;
				HlBlockElement::Add(
					3,
					array(
						"UF_OBJ_TYPE_NAME" => $cell->getCalculatedValue())
					);
			}
		}
	}
	else
	{
		for ($row = 2; $row <= $rows_count; $row++) {
			$value_str = "";
			for ($column = 0; $column < $columns_count; $column++) {
				$cell = $worksheet->getCellByColumnAndRow($column, $row);
				if($cell->getCalculatedValue()=="")break;
				$arr_1["n".($row-2)] = Array("VALUE" =>$cell->getCalculatedValue(),"XML_ID"=>$name."_".str_pad($row-2,3,"0",STR_PAD_LEFT)); 
			}
		}
	}
}*/
//echo"<xmp>";print_r($arr_1);echo"</xmp>";
/*if($type=="uf")
{
	$arFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields("HLBLOCK_".$hblock);
	if(array_key_exists("UF_".$name, $arFields))
	{
		$FIELD_ID = $arFields["UF_".$name]["ID"];
		$obEnum = new CUserFieldEnum;
		$obEnum->SetEnumValues($FIELD_ID, $arr_1);
	}
}*/
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>