<?
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
//require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
//$APPLICATION->SetTitle("Синхронизация полей типа список в heighload-блоке Заявки");
//die("Для выполнения скрипта обновления полей заявок типа список убрать die()");
//require_once dirname(__FILE__) . '/../libs/PHPExcel.php';
set_time_limit(60*10);

$all_upd = Array (
	5 => Array(
		"type" => "hblock",
		"file" => "B24_Catalogs",
		"name" => "REQUESTS",
		"hblock" => 2,
		"postfix" => ""),
	6 => Array(
		"type" => "hblock",
		"file" => "realty_type.xls",
		"name" => "REALTY_TYPE",
		"hblock" => 4,
		"postfix" => ""),
	20 => Array(
		"type" => "hblock",
		"file" => "domofey/d_B24_Catalogs",
		"name" => "REQUESTS",
		"hblock" => 20,
		"postfix" => "_DF")
);
$make=0;
$i = isset($_GET["i"])?intval($_GET["i"]):5;

$type =$all_upd[$i]["type"];
$file = $all_upd[$i]["file"];
$name = $all_upd[$i]["name"];
$hblock = $all_upd[$i]["hblock"];
$postfix = $all_upd[$i]["postfix"];

if($i==5||$i==20)
{
	if (file_exists($_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$file.".zip")) 
	{
		$make=1;
		$zip = new ZipArchive(); 
		if($zip->open($_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$file.".zip") === true) {
			$flag = $zip->extractTo($_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$file."/"); 
			$zip->close(); 
			if($flag===true)
			{
				//if (copy($_SERVER["DOCUMENT_ROOT"]."/upload/1c/B24_Catalogs.zip", $_SERVER["DOCUMENT_ROOT"]."/upload/1c/old/".date('Y-m-d_H-i')."_B24_Catalogs.zip")) {
				   unlink($_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$file.".zip");
				//}
			}
		}
	}
}
$make=1;
if ($make==1)
{	
	$rsData = CUserTypeEntity::GetList( array(), array("ENTITY_ID"=>"HLBLOCK_".$hblock) );
	while($arRes = $rsData->Fetch())
	{
		if($arRes["USER_TYPE_ID"]=="enumeration")
		{//echo $arRes["FIELD_NAME"]."<br>";
			$tmp_name = str_replace("UF_","",$arRes["FIELD_NAME"]);
			$tmp_name = str_replace("_DF","",$tmp_name);
			if($tmp_name=="ELECTICITY")$tmp_name="ELECTRICITY";
			//echo $tmp_name."<br>";
			if($tmp_name!="ELECTRICITY"&&$tmp_name!="HEATING"&&$tmp_name!="HOUSE_TYPE"&&
			$tmp_name!="MATERIAL"&&$tmp_name!="OWNER_RIGHTS"&&$tmp_name!="REMONT_STATUS"
			&&$tmp_name!="SANUSEL_TYPE"&&$tmp_name!="SOURCE"&&$tmp_name!="WATER"&&$tmp_name!="LOGGIA"&&
			$tmp_name!="BALCONY"&&$tmp_name!="SEWERAGE"
			)continue;
			$xml_file = $_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$file."/".strtolower($tmp_name).".xml";
			//echo $xml_file."<br>";
			if (file_exists($xml_file)) 
			{	
				if($tmp_name=="ELECTRICITY")$tmp_name="ELECTICITY";
				$rs = CUserFieldEnum::GetList(array(), array(
					"USER_FIELD_NAME" => $arRes["NAME"],
					"USER_FIELD_ID" => $arRes["ID"]
				));
				$ar_work = Array();
				$add_arr = Array();
				$lj=0;
				while($ar = $rs->GetNext())
				{
					$ar_work[$ar["XML_ID"]]=Array("ID"=>$ar["ID"],"VALUE"=>$ar["VALUE"]);
					$lj++;
				}
				$xml = simplexml_load_file($xml_file);	
				foreach($xml->item as $l=>$lv)
				{
					$lv->XML_ID = str_replace("-","",$lv->XML_ID);
					$str_xml_id = $tmp_name."_".str_pad(intval($lv->XML_ID),3,"0",STR_PAD_LEFT);
					if(isset($ar_work[$str_xml_id]))
					{
						if($ar_work[$str_xml_id]["VALUE"]!=$lv->VALUE)
						{
							$obEnum = new CUserFieldEnum;
							$obEnum->SetEnumValues($arRes["ID"], array(
								$ar_work[$str_xml_id]["ID"] => array(
									"VALUE" => (string)$lv->VALUE,
									"XML_ID"=> $tmp_name."_".str_pad(intval($lv->XML_ID),3,"0",STR_PAD_LEFT)
								)
							));	 
						}
						unset($ar_work[$str_xml_id]);
					}
					else
					{
						$add_arr[] = Array("XML_ID"=>(string)$lv->XML_ID,"VALUE" => (string)$lv->VALUE);
					}
				}
				foreach ($ar_work as $j=>$jx)
				{
					$obEnum = new CUserFieldEnum;
					$obEnum->SetEnumValues($arRes["ID"], array(
						$jx["ID"] => array(
							"DEL" => "Y",
						),
					));
					$lj--;
				}
				if(!empty($add_arr))
				{
					$arr_f=Array();
					foreach($add_arr as $j=>$jx)
					{
						$arr_f[	"n".$j] = array(
								"XML_ID"=> $tmp_name."_".str_pad(intval($jx["XML_ID"]),3,"0",STR_PAD_LEFT),
								"VALUE" => $jx["VALUE"]
							);
					}
					$obEnum = new CUserFieldEnum;
					$obEnum->SetEnumValues($arRes["ID"], $arr_f);
				}
			}
		}
	}
	$xml_file = $_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$file."/delete.xml";
	if(file_exists($xml_file)) 
	{
		$xml = simplexml_load_file($xml_file);	
		$uf_id_del=Array();
		foreach($xml->item as $l=>$lv)
		{
			if(!in_array((string)$lv->Number,$uf_id_del))
				$uf_id_del[]=(string)$lv->Number;
		}
		if(sizeof($uf_id_del)>0)
		{
			$request = HlBlockElement::GetList($hblock,array("ID"),array("UF_ID".$postfix=>$uf_id_del),array(),100);
			while($request_data = $request->Fetch())
			{
				HlBlockElement::Remove($hblock,$request_data["ID"]);
			}
		}
		if (copy($xml_file,$_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$file."/old/".date('Y-m-d_H-i')."_delete.xml"))
			unlink($xml_file);
	}
}
if($i==5)
	{
		LocalRedirect("update_enumeration.php?i=20");
	}