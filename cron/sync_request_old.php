<?
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

set_time_limit(60*10);
$i = isset($_GET["i"])?intval($_GET["i"]):0;
$all_upd = Array (
	0 => Array(
		"type" => "hblock",
		"file" => "old/2016-10-06_14-06_new_orders.xml",
		"name" => "Requests",
		"hblock" => 2),
	1 => Array(
		"type" => "hblock",
		"file" => "orders.xml",
		"name" => "Requests",
		"hblock" => 2)
);
$step = (isset($_GET["step"]))?intval($_GET["step"]):0;
//$i=0;
$type=$all_upd[$i]["type"];
$file = $all_upd[$i]["file"];
$name= $all_upd[$i]["name"];
$hblock= $all_upd[$i]["hblock"];

$dir = $APPLICATION->GetCurDir();

$enum_arr=array();

$rsData = HlBlockElement::GetList(4,array(),array(),array(),100);

while($arRes = $rsData->Fetch())
{
	$enum_arr["UF_REALTY_TYPE"][$arRes["UF_REALTY_TYPE_NAME"]]=$arRes["UF_REALTY_TYPE_ID"];
}
$enum_arr["UF_REALTY_TYPE"]["Аренда жилья"]=5;
$arr_fields = Array();
$rsData_all = CUserTypeEntity::GetList( array(), array("ENTITY_ID"=>"HLBLOCK_2") );
while($arRes = $rsData_all->Fetch())
{	
	$arr_fields[]=$arRes["FIELD_NAME"];
}
$arr_fields[]="UF_OBJECT_TYPE";
$rsData = CUserTypeEntity::GetList( array(), array("ENTITY_ID"=>"HLBLOCK_2","USER_TYPE_ID"=>"enumeration") );
while($arRes = $rsData->Fetch())
{	
	$rs = CUserFieldEnum::GetList(array(), array(
			"USER_FIELD_NAME" => $arRes["FIELD_NAME"]
		));
	while($arRes_1 = $rs->Fetch())
	{
		$enum_arr[$arRes["FIELD_NAME"]][$arRes_1["VALUE"]]=$arRes_1["ID"];
		$enum_arr[$arRes["FIELD_NAME"]][$arRes_1["XML_ID"]]=$arRes_1["ID"];
		switch($arRes_1["VALUE"]){
			case "Да":
				$enum_arr[$arRes["FIELD_NAME"]]["Yes"]=$arRes_1["ID"];
			case "Нет":
				$enum_arr[$arRes["FIELD_NAME"]]["No"]=$arRes_1["ID"];		
		}
	}
}
if (file_exists($_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$file)) {
   $xml = simplexml_load_file($_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$file);
   $shag=0;
   foreach($xml->item as $k=>$v)
   {
	   foreach ($v as $x=>$y)
		{
			$arr[strtoupper($x)] = trim((string)$y);
		}
		if($shag<$step*1000)
		{
			$shag++;
			continue;  			
		}
		if($shag>$step*1000+1000)
		{
			$step=$step+1;
			header("Location: ".$dir."sync_requests.php?nw=1&step=".$step);
			die();
		}
		$arr = Array();
		foreach ($v as $x=>$y)
		{
			if(in_array(strtoupper($x),$arr_fields))
				$arr[strtoupper($x)] = trim((string)$y);
		}
		if(!empty($arr))
		{print_r($arr);
			$arr["UF_ADD_DATE"]=date("d.m.Y H:i:s", strtotime($arr["UF_ADD_DATE"]));
			$arr["UF_UPDATE_DATE"]=date("d.m.Y H:i:s", strtotime($arr["UF_UPDATE_DATE"]));
			$arr["UF_OBJ_TYPE"]=$arr["UF_OBJECT_TYPE"];
			echo $arr["UF_OBJ_TYPE"]."!!";
			$arr["UF_ELECTICITY"]=$arr["UF_ELECTRICITY"];
			/*if($arr["UF_OPERATION_TYPE"]==""&&$arr["UF_STATUS"]=="Открыт в продажу")
				$arr["UF_OPERATION_TYPE"]="Продажа вторичной недвижимости";*/
			unset($arr["UF_OBJECT_TYPE"]);
			unset($arr["UF_ELECTRICITY"]);
			$addr_arr = explode("\n",$arr["UF_ADDR_BLOCK"]);
			$arr_addr_help = Array(
				"Индекс="=>"UF_ADDR_INDEX",
				"Город="=>"UF_CITY_ID",
				"Улица="=>"UF_ADDR_STREET",
				"Район="=>"UF_REGION_ID",
				"РайонГорода="=>"UF_CITY_REGION"
			);
			foreach($addr_arr as $k_a=>$v_a)
			{
				foreach($arr_addr_help as $t=>$u)
					if(strpos($v_a,$t)!==FALSE)
					{
						$arr[$arr_addr_help[$t]]=str_replace($t,"",$v_a);
						unset($arr_addr_help[$t]);
						break;
					}
				if(empty($arr_addr_help))break;
			}
		}
		$request = HlBlockElement::GetList(2,array(),array("UF_ID"=>intval($arr["UF_ID"])),array(),1);
		$request_data = $request->Fetch();
		//echo "?".$request_data["UF_UPDATE_DATE"]." !  ".$arr["UF_UPDATE_DATE"]."<br>";

		if(!empty($request_data))
		{
			
			$request_id=$request_data["ID"];
			if($request_data["UF_UPDATE_DATE"]!=$arr["UF_UPDATE_DATE"]&&false)
				HlBlockElement::Remove(2,$request_id);
		}
		$arr["UF_INNER_STATUS"]=4;
		//echo"<xmp>";print_r($arr);echo"</xmp>";
		foreach ($arr as $x=>$y)
		{
			if(isset($enum_arr[$x])&&$arr[$x]!=""&&$arr[$x]!="0")
				if(isset($enum_arr[$x][$arr[$x]]))
				{
					$arr[$x]=$enum_arr[$x][$arr[$x]];
				}
				else if($x=="UF_OPERATION_TYPE")
				{
					$arr_help = explode(" ",$arr[$x]);
					foreach ($enum_arr[$x] as $k1 => $v1)
					{
						$flag=1;
						foreach ($arr_help as $k2 => $v2)
						{
							if(strpos(strtolower($k1),strtolower($v2))===FALSE)
							{
								$flag=0;
								break;
							}
						}
						if($flag==1)
						{
							$arr[$x]=$v1;
							break;
						}
					}
				}
				else if(isset($enum_arr[$x][str_replace("UF_","",$x)."_".str_pad(intval(str_replace("-","",$arr[$x])),3,0,STR_PAD_LEFT)]))
				{
					$arr[$x]= $enum_arr[$x][str_replace("UF_","",$x)."_".str_pad(intval(str_replace("-","",$arr[$x])),3,0,STR_PAD_LEFT)];
					//echo "-------------".$enum_arr[$x][str_replace("UF_","",$x)."_".str_pad(intval(str_replace("-","",$arr[$x])),3,0,STR_PAD_LEFT)]."<br>";
				}
				else if($arr[$x]=="Да" && isset($enum_arr[$x]["Есть"]))
				{
					$arr[$x]=$enum_arr[$x]["Есть"];
				}
				else
				{
					echo "///some problem with ".$arr["UF_ID"]." ".$x." ".$arr[$x]." ".str_replace("UF_","",$x)."_".str_pad(intval(str_replace("-","",$arr[$x])),3,0,STR_PAD_LEFT)." ???<br>" ; 
					$arr["UF_INNER_STATUS"]=3;
				}
			//echo $x." (".str_replace("UF_","",$x)."_".str_pad(intval(str_replace("-","",$arr[$x])),3,0,STR_PAD_LEFT).")<br>";
		}
		unset($arr["UF_WASHING_MACHINE"]);
		if(/*$request_data["UF_UPDATE_DATE"]!=$arr["UF_UPDATE_DATE"]&&false*/empty($request_data))
		{
			//echo "<xmp>";print_r($arr);echo "</xmp>"; 
			/*$arr_1["UF_ID"]=$arr["UF_ID"];
			$arr_1["UF_SOURCE"]=$arr["UF_SOURCE"];
			$arr_1["UF_ADDR_BLOCK"]=$arr["UF_ADDR_BLOCK"];
			$arr_1["UF_ADD_DATE"]=date("d.m.Y H:i:s", strtotime($arr["UF_ADD_DATE"]));*/
			$res = HlBlockElement::Add(2,$arr);
			$request_id = $res->getid();
			//die("all ok ".$request_id);
		}
		else
		{
			/*echo"<xmp>";print_r($enum_arr);echo"</xmp>";*/
			//echo"<xmp>";print_r($arr);echo"</xmp>";
			$res = HlBlockElement::Update(2,$request_id,$arr);
			//die("all ok ".$request_id);
		}
		$shag++;
		//die($request_id);
   }
   //if (copy($_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$file, $_SERVER["DOCUMENT_ROOT"]."/upload/1c/old/".date('Y-m-d_H-i')."_".$file)) {
	//   unlink($_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$file);
	//}
} else {
    echo('Не удалось открыть файл '.$file.'.');
}
/*if($i==0)
{
	if (file_exists($_SERVER["DOCUMENT_ROOT"]."/upload/1c/new_orders.zip")) 
	{
		$zip = new ZipArchive(); 
		if($zip->open($_SERVER["DOCUMENT_ROOT"]."/upload/1c/new_orders.zip") === true) {
			$flag = $zip->extractTo($_SERVER["DOCUMENT_ROOT"]."/upload/1c/foto/"); 
			$zip->close(); 
			if($flag===true)
			{
				if (copy($_SERVER["DOCUMENT_ROOT"]."/upload/1c/new_orders.zip", $_SERVER["DOCUMENT_ROOT"]."/upload/1c/old/".date('Y-m-d_H-i')."_new_orders.zip")) {
				   unlink($_SERVER["DOCUMENT_ROOT"]."/upload/1c/new_orders.zip");
				}
			}
		}
	}
}*/
?>