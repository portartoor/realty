<?
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once '../usage.php'; 
require($_SERVER["DOCUMENT_ROOT"]."/libs/rights_class.php");

$api_use = 0;

function xml2array ( $xmlObject, $out = array () )
{
    foreach ( (array) $xmlObject as $index => $node )
        $out[strtoupper($index)] = ( is_object ( $node ) ) ? xml2array ( $node ) : $node;
    return $out;
}

class SyncRequestsInClass {
	
	private $enum_arr=Array();
	private $arr_fields=Array();
	private $highloadblock;
	private $postfix = "";
	private $arr_f_names = Array(
		"UF_ID"=>1614,
		"UF_SOURCE"=>1213,
		"UF_AGENT"=>1597,
		"UF_ADDR_BLOCK"=>577,
		"UF_ADD_DATE"=>1903,
		"UF_REALTY_TYPE"=>576,
		"UF_OBJ_TYPE"=>780,
		"UF_OPERATION_TYPE"=>1920,
		"UF_STATUS"=>1921,
		"UF_COMMENT_ORDER"=>1603,
		"UF_ROOMS_FROM"=>1906,
		"UF_ROOMS_TO"=>1907,
		"UF_SQUARE_FROM"=>1908,
		"UF_SQUARE_TO"=>1909,
		"UF_PRICE_FROM"=>1910,
		"UF_PRICE_TO"=>1911,
		"UF_REGION_ID"=>682,
		"UF_CITY"=>693,
		"UF_STREET"=>1265
	);
	
	function __construct(){
		$enum_arr=array();
		global $Project;
		$Project = new Rights();
		$data_res = $Project->get_requests_file();
		$this->highloadblock = $data_res["hblock"];
		$this->postfix = $Project->get_postfix();
		$rsData = HlBlockElement::GetList(4,array(),array(),array(),100);
		while($arRes = $rsData->Fetch())
		{
			$enum_arr["UF_OBJ_TYPE"][$arRes["UF_OBJ_TYPE_ID"]]=$arRes["UF_NAME"];
		}
		
		$rsData = HlBlockElement::GetList(4,array(),array(),array(),100);

		while($arRes = $rsData->Fetch())
		{
			$enum_arr["UF_REALTY_TYPE"][$arRes["UF_REALTY_TYPE_ID"]]=$arRes["UF_REALTY_TYPE_NAME"];
		}
		$enum_arr["UF_REALTY_TYPE"][5]="Аренда жилья";
		
		
		$arr_fields = Array();
		$rsData_all = CUserTypeEntity::GetList( array(), array("ENTITY_ID"=>"HLBLOCK_".$this->highloadblock) );
		while($arRes = $rsData_all->Fetch())
		{	
			if(strlen($this->postfix)>0)
				$arr_fields[]=str_replace($this->postfix,"",$arRes["FIELD_NAME"]);
			else
				$arr_fields[]=$arRes["FIELD_NAME"];
		}
		$arr_fields[]="UF_OBJECT_TYPE";
		if($this->postfix!="")
		{
			$arr_fields[]="UF_LAND_OWNER_TYPE";
			$arr_fields[]="UF_NARUZHN_REKLAMA";
		}
		$rsData = CUserTypeEntity::GetList( array(), array("ENTITY_ID"=>"HLBLOCK_".$this->highloadblock,"USER_TYPE_ID"=>"enumeration") );
		while($arRes = $rsData->Fetch())
		{	
			$rs = CUserFieldEnum::GetList(array(), array(
					"USER_FIELD_NAME" => $arRes["FIELD_NAME"]
				));
			$arRes["FIELD_NAME"] = str_replace($this->postfix,"",$arRes["FIELD_NAME"]);
			while($arRes_1 = $rs->Fetch())
			{
				//$enum_arr[$arRes["FIELD_NAME"]][$arRes_1["VALUE"]]=$arRes_1["ID"];
				$enum_arr[$arRes["FIELD_NAME"]][$arRes_1["ID"]]=$arRes_1["VALUE"];
				switch($arRes_1["VALUE"]){
					case "Да":
						$enum_arr[$arRes["FIELD_NAME"]]["Yes"]=$arRes_1["ID"];
					case "Нет":
						$enum_arr[$arRes["FIELD_NAME"]]["No"]=$arRes_1["ID"];		
				}
			}
		}
		$rsData = CUserTypeEntity::GetList( array(), array("ENTITY_ID"=>"HLBLOCK_".$this->highloadblock,"USER_TYPE_ID"=>"boolean") );
		//die($this->postfix."!!");
		while($arRes = $rsData->Fetch())
		{	
			$arRes["FIELD_NAME"] = str_replace($this->postfix,"",$arRes["FIELD_NAME"]);
			$enum_arr[$arRes["FIELD_NAME"]][1]="Да";
			$enum_arr[$arRes["FIELD_NAME"]][""]="Нет";
		}
		$this->enum_arr = $enum_arr;
		$this->arr_fields = $arr_fields;
		//echo"<xmp>";print_r($this->enum_arr);echo"</xmp>";
	}
	function AddNewItem($v)
	{	
		global $Project;
		$enum_arr = $this->enum_arr;
		$arr_fields = $this->arr_fields;
		$arr_photos = Array();
	    foreach ($v as $x=>$y)
		{
			if(strtoupper($x)=="UF_PHOTOS")
			{
				$arr_photos = $y;
			}
			else
				$arr[strtoupper($x)] = trim((string)$y);
		}
		$arr = Array();
		foreach ($v as $x=>$y)
		{
			if(in_array(strtoupper($x),$arr_fields)||(strtoupper($x)=="UF_ELECTRICITY")||(strtoupper($x)=="UF_GOAL_LAND"))
				$arr[strtoupper($x)] = trim((string)$y);
		}
		if(!empty($arr))
		{
			$arr["UF_ADD_DATE"]=date("d.m.Y H:i:s", strtotime($arr["UF_ADD_DATE"]));
			$arr["UF_UPDATE_DATE"]=date("d.m.Y H:i:s", strtotime($arr["UF_UPDATE_DATE"]));
			$arr["UF_OBJ_TYPE"]=$arr["UF_OBJECT_TYPE"];
			$arr["UF_ELECTICITY"]=$arr["UF_ELECTRICITY"];
			if($this->postfix!="")
			{
				$arr["UF_LAND_O_TYPE"]=$arr["UF_LAND_OWNER_TYPE"];
				$arr["UF_N_REKLAMA"]=$arr["UF_NARUZHN_REKLAMA"];
				unset($arr["UF_LAND_OWNER_TYPE"]);
				unset($arr["UF_NARUZHN_REKLAMA"]);
			}
			if($arr["UF_STATUS"]=="Закрыт в продажу")
			{
				$delete=true;
			}
			unset($arr["UF_OBJECT_TYPE"]);
			unset($arr["UF_ELECTRICITY"]);
			$addr_arr = explode("\n",$arr["UF_ADDR_BLOCK"]);
			$arr_addr_help = Array(
				"Индекс="=>"UF_ADDR_INDEX",
				"Город="=>"UF_CITY_ID",
				"Город/н.пункт="=>"UF_CITY_ID",
				"н.пункт="=>"UF_CITY_ID",
				"НаселенныйПункт="=>"UF_CITY_ID",
				"Улица="=>"UF_ADDR_STREET",
				"Район="=>"UF_REGION_ID",
				"РайонГорода="=>"UF_CITY_REGION",
				"Дом="=>"UF_ADDR_HOUSE"
			);
			foreach($addr_arr as $k_a=>$v_a)
			{//echo "!!!".$v_a."!!!";
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
		$request = HlBlockElement::GetList($this->highloadblock,array(),array("UF_ID".$this->postfix=>intval($arr["UF_ID"])),array(),1);
		$request_data = $request->Fetch();

		/*if(!empty($request_data))
		{
			
			$request_id=$request_data["ID"];
			if($request_data["UF_UPDATE_DATE"]!=$arr["UF_UPDATE_DATE"]&&false)
				HlBlockElement::Remove($this->highloadblock,$request_id);
		}*/
		$arr["UF_INNER_STATUS"]=4;
		
		foreach ($arr as $x=>$y)
		{
			/*echo str_replace("UF_","",$x)."_".str_pad(intval(str_replace("-","",$arr[$x])),3,0,STR_PAD_LEFT); //die();
			echo "<br>";*/
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
				}
				else if($arr[$x]=="Да" && isset($enum_arr[$x]["Есть"]))
				{
					$arr[$x]=$enum_arr[$x]["Есть"];
				}
				else
				{
					echo "///some problem with ".$arr["UF_ID"]." ".$x." ".$arr[$x]." ".str_replace("UF_","",$x)."_".str_pad(intval(str_replace("-","",$arr[$x])),3,0,STR_PAD_LEFT)." ???<br>" ; 
					//$arr["UF_INNER_STATUS"]=3;
				}
		}
		//echo"<xmp>";print_r($arr);echo"</xmp>";
		if($arr["UF_GOAL_LAND"]!="")
		{
			$arr["UF_GOAL"] = intval("1".intval($arr["UF_GOAL_LAND"]));
		}
		else if($arr["UF_GOAL"]!="")
			$arr["UF_GOAL"] = intval("6".intval($arr["UF_GOAL"]));
		unset($arr["UF_GOAL_LAND"]);
		unset($arr["UF_WASHING_MACHINE"]);
	   if(isset($arr_photos)&&sizeof($arr_photos)>0)
	   {
		    $preview_tmp=false;
		    $order_array_photo=Array();
			$order_array_plans=Array();
			$order_array_docs=Array();
			$folder = $Project->get_map();
			if($folder!="")$folder=$folder."/";
			foreach ($arr_photos as $k=>$photo_template_xml)
			{
				$photo_template = xml2array($photo_template_xml);
				$dir = $_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$folder."foto/".$arr["UF_ID"]."/";
				if($photo_template["PREVIEW"]=="1")
				{
					$v=str_replace("\\","/",$photo_template["URL"]);
					if(is_file($dir.$v))
					{
						$photo_template["CHANGED"]=0;
						$arFile = CFile::MakeFileArray($dir.$v);
						$photo_template_1=$photo_template;
						unset($photo_template_1["URL"]);
						$arFile["description"]=json_encode($photo_template_1);
						$fid = CFile::SaveFile($arFile,"realty_files");
						if(intval($fid)>0)
						{
							$arr["UF_PHOTO_PREVIEW"]=$fid;
						}
					}
				}
				if($photo_template["PHOTO"]=="1")
				{	
					if($preview_tmp==false)
					{
						$preview_tmp = $photo_template;
						$arr["UF_PHOTOS"]=Array();
					}
					$v=str_replace("\\","/",$photo_template["URL"]);
					if(is_file($dir.$v))
					{
						$arFile = CFile::MakeFileArray($dir.$v);
						$photo_template["CHANGED"]=0;
						$photo_template_1=$photo_template;
						unset($photo_template_1["URL"]);
						$arFile["description"]=json_encode($photo_template_1);
						$fid = CFile::SaveFile($arFile,"realty_files");
						if(intval($fid)>0)
						{
							$arr["UF_PHOTOS"][]=$fid;
							$order_array_photo[]=Array("fid"=>$fid,"order"=>isset($photo_template["ORDER"])?intval($photo_template["ORDER"]):2);
						}
					}
				}
				if($photo_template["PLAN"]=="1")
				{
					$v=str_replace("\\","/",$photo_template["URL"]);
					if(is_file($dir.$v))
					{
						$arFile = CFile::MakeFileArray($dir.$v);
						$photo_template["CHANGED"]=0;
						$photo_template_1=$photo_template;
						unset($photo_template_1["URL"]);
						$arFile["description"]=json_encode($photo_template_1);
						$fid = CFile::SaveFile($arFile,"realty_files");
						if(intval($fid)>0)
						{
							$arr["UF_PLAN_PHOTOS"][]=$fid;	
							$order_array_plans[]=Array("fid"=>$fid,"order"=>isset($photo_template["ORDER"])?intval($photo_template["ORDER"]):2);
						}
					}
				}
				if($photo_template["DOC"]=="1")
				{
					$v=str_replace("\\","/",$photo_template["URL"]);
					if(is_file($dir.$v))
					{
						$arFile = CFile::MakeFileArray($dir.$v);
						$photo_template["CHANGED"]=0;
						$photo_template_1=$photo_template;
						unset($photo_template_1["URL"]);
						$arFile["description"]=json_encode($photo_template_1);
						$fid = CFile::SaveFile($arFile,"realty_files");
						if(intval($fid)>0)
						{
							$arr["UF_DOCS"][]=$fid;	
							$order_array_docs[]=Array("fid"=>$fid,"order"=>isset($photo_template["ORDER"])?intval($photo_template["ORDER"]):2);
						}
					}
				}
			}
			if(!isset($arr["UF_PHOTO_PREVIEW"])&&$preview_tmp!==false)
			{
				$photo_template = $preview_tmp;
				$v=str_replace("\\","/",$photo_template["URL"]);
				if(is_file($dir.$v))
				{
					$arFile = CFile::MakeFileArray($dir.$v);
					$photo_template["CHANGED"]=0;
					$photo_template_1=$photo_template;
					unset($photo_template_1["URL"]);
					$arFile["description"]=json_encode($photo_template_1);
					$fid = CFile::SaveFile($arFile,"realty_files");
					if(intval($fid)>0)
					{
						$arr["UF_PHOTO_PREVIEW"]=$fid;
					}
				}
			}
			else if(!isset($arr["UF_PHOTO_PREVIEW"]))
			{
				$arr["UF_PHOTO_PREVIEW"]="";
			}	
			if(sizeof($order_array_photo)>0)
			{
				usort($order_array_photo, function($a, $b){
					return ($a['order'] - $b['order']);
				});
				$arr_tmp=Array();
				foreach ($order_array_photo as $k=>$v)
				{
					$arr_tmp[]=$v["fid"];
				}
				$arr["UF_PHOTOS"] = $arr_tmp;
			}
			else
				$arr["UF_PHOTOS"] = Array();
			if(sizeof($order_array_docs)>0)
			{
				usort($order_array_docs, function($a, $b){
					return ($a['order'] - $b['order']);
				});
				$arr_tmp=Array();
				foreach ($order_array_docs as $k=>$v)
				{
					$arr_tmp[]=$v["fid"];
				}
				$arr["UF_DOCS"] = $arr_tmp;
			}
			else
				$arr["UF_DOCS"] = Array();
			if(sizeof($order_array_plans)>0)
			{
				usort($order_array_plans, function($a, $b){
					return ($a['order'] - $b['order']);
				});
				$arr_tmp=Array();
				foreach ($order_array_plans as $k=>$v)
				{
					$arr_tmp[]=$v["fid"];
				}
				$arr["UF_PLAN_PHOTOS"] = $arr_tmp;
			}
			else
				$arr["UF_PLAN_PHOTOS"] = Array();
	   }
	   if(!isset($arr["UF_PHOTO_PREVIEW"]))$arr["UF_PHOTO_PREVIEW"]="";
	   if(!isset($arr["UF_PHOTOS"]))$arr["UF_PHOTOS"]=Array();
	   if(!isset($arr["UF_PLAN_PHOTOS"]))$arr["UF_PLAN_PHOTOS"]=Array();
	   if(!isset($arr["UF_DOCS"]))$arr["UF_DOCS"]=Array();
		//print_r($arr);die();
		/*if($arr["UF_DIVISION_HEAD"]!="")
		{
			$request_agent = HlBlockElement::GetList($Project->get_agents_hb_id(),array(),array("UF_AGENT_ID".$this->postfix=>$arr["UF_DIVISION_HEAD"],"!UF_HEAD_1C".$this->postfix=>$arr["UF_DIVISION_HEAD"]),array(),1);
			$request_agent_data = $request_agent->Fetch();
			if(!empty($request_agent_data))
			{
				$res = HlBlockElement::Update($Project->get_agents_hb_id(),$request_agent_data["ID"],Array("UF_HEAD_1C".$this->postfix=>$arr["UF_DIVISION_HEAD"]));
			}
		}*/
		/*$arr = $Project->add_postfix_to_fields($arr);
		if(empty($request_data)&&!$delete)
		{
			$res = HlBlockElement::Add($this->highloadblock,$arr);
			$request_id = $res->getid();
		}
		else
		{		
			if(!$delete)
			{
				$res = HlBlockElement::Update($this->highloadblock,$request_id,$arr);
			}
			else
				$res = HlBlockElement::Remove($this->highloadblock,$request_id);	
			
		}*/
    }
    function AddNewItemB24($v){
		
		global $Project;
		$enum_arr = $this->enum_arr;
		
		$arr_fields = $this->arr_fields;
		$arr_photos = Array();
		
	    foreach ($v as $x=>$y)
		{
			if(strtoupper($x)=="UF_PHOTOS")
			{
				$arr_photos = $y;
			}
			else
				$arr[strtoupper($x)] = trim((string)$y);
		}
		
		$arr = Array();
		foreach ($v as $x=>$y)
		{
			if(in_array(strtoupper($x),$arr_fields)||(strtoupper($x)=="UF_ELECTRICITY")||(strtoupper($x)=="UF_GOAL_LAND"))
				$arr[strtoupper($x)] = trim((string)$y);
		}
		
		
		if(!empty($arr))
		{
			$arr["UF_ADD_DATE"]=date("d.m.Y H:i:s", strtotime($arr["UF_ADD_DATE"]));
			$arr["UF_UPDATE_DATE"]=date("d.m.Y H:i:s", strtotime($arr["UF_UPDATE_DATE"]));
			$arr["UF_OBJ_TYPE"]=$arr["UF_OBJECT_TYPE"];
			$arr["UF_ELECTICITY"]=$arr["UF_ELECTRICITY"];
			if($this->postfix!="")
			{
				$arr["UF_LAND_O_TYPE"]=$arr["UF_LAND_OWNER_TYPE"];
				$arr["UF_N_REKLAMA"]=$arr["UF_NARUZHN_REKLAMA"];
				unset($arr["UF_LAND_OWNER_TYPE"]);
				unset($arr["UF_NARUZHN_REKLAMA"]);
			}
			if($arr["UF_STATUS"]=="Закрыт в продажу")
			{
				$delete=true;
			}
			unset($arr["UF_OBJECT_TYPE"]);
			unset($arr["UF_ELECTRICITY"]);
			$addr_arr = explode("\n",$arr["UF_ADDR_BLOCK"]);
			$arr_addr_help = Array(
				"Индекс="=>"UF_ADDR_INDEX",
				"Город="=>"UF_CITY_ID",
				"Город/н.пункт="=>"UF_CITY_ID",
				"н.пункт="=>"UF_CITY_ID",
				"НаселенныйПункт="=>"UF_CITY_ID",
				"Улица="=>"UF_ADDR_STREET",
				"Район="=>"UF_REGION_ID",
				"РайонГорода="=>"UF_CITY_REGION",
				"Дом="=>"UF_ADDR_HOUSE"
			);
			foreach($addr_arr as $k_a=>$v_a)
			{//echo "!!!".$v_a."!!!";
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
		$request = HlBlockElement::GetList($this->highloadblock,array(),array("UF_ID".$this->postfix=>intval($arr["UF_ID"])),array(),1);
		$request_data = $request->Fetch();

		/*if(!empty($request_data))
		{
			
			$request_id=$request_data["ID"];
			if($request_data["UF_UPDATE_DATE"]!=$arr["UF_UPDATE_DATE"]&&false)
				HlBlockElement::Remove($this->highloadblock,$request_id);
		}*/
		$arr["UF_INNER_STATUS"]=4;
		
		foreach ($arr as $x=>$y)
		{
			/*echo str_replace("UF_","",$x)."_".str_pad(intval(str_replace("-","",$arr[$x])),3,0,STR_PAD_LEFT); //die();
			echo "<br>";*/
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
				}
				else if($arr[$x]=="Да" && isset($enum_arr[$x]["Есть"]))
				{
					$arr[$x]=$enum_arr[$x]["Есть"];
				}
				else
				{
					//echo "///some problem with ".$arr["UF_ID"]." ".$x." ".$arr[$x]." ".str_replace("UF_","",$x)."_".str_pad(intval(str_replace("-","",$arr[$x])),3,0,STR_PAD_LEFT)." ???<br>" ; 
					//$arr["UF_INNER_STATUS"]=3;
				}

		}
		$datause = date('H:i:s');
		echo "import ".$datause." 1CID: ".$arr["UF_ID"]."<br>";
		
		$fp=fopen("import_log.txt","a");  
		fwrite($fp, "\r\n" .$datause." import 1CID: ".$arr["UF_ID"]."\n");  
		fclose($fp);
		
		//echo"<xmp>";print_r($arr);echo"</xmp>";
		if($arr["UF_GOAL_LAND"]!="")
		{
			$arr["UF_GOAL"] = intval("1".intval($arr["UF_GOAL_LAND"]));
		}
		else if($arr["UF_GOAL"]!="")
			$arr["UF_GOAL"] = intval("6".intval($arr["UF_GOAL"]));
		unset($arr["UF_GOAL_LAND"]);
		unset($arr["UF_WASHING_MACHINE"]);
	   if(isset($arr_photos)&&sizeof($arr_photos)>0)
	   {
		    $preview_tmp=false;
		    $order_array_photo=Array();
			$order_array_plans=Array();
			$order_array_docs=Array();
			$folder = $Project->get_map();
			if($folder!="")$folder=$folder."/";
			foreach ($arr_photos as $k=>$photo_template_xml)
			{
				$photo_template = xml2array($photo_template_xml);
				$dir = $_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$folder."foto/".$arr["UF_ID"]."/";
				if($photo_template["PREVIEW"]=="1")
				{
					$v=str_replace("\\","/",$photo_template["URL"]);
					if(is_file($dir.$v))
					{
						$photo_template["CHANGED"]=0;
						$arFile = CFile::MakeFileArray($dir.$v);
						$photo_template_1=$photo_template;
						unset($photo_template_1["URL"]);
						$arFile["description"]=json_encode($photo_template_1);
						$fid = CFile::SaveFile($arFile,"realty_files");
						if(intval($fid)>0)
						{
							$arr["UF_PHOTO_PREVIEW"]=$fid;
						}
					}
				}
				if($photo_template["PHOTO"]=="1")
				{	
					if($preview_tmp==false)
					{
						$preview_tmp = $photo_template;
						$arr["UF_PHOTOS"]=Array();
					}
					$v=str_replace("\\","/",$photo_template["URL"]);
					if(is_file($dir.$v))
					{
						$arFile = CFile::MakeFileArray($dir.$v);
						$photo_template["CHANGED"]=0;
						$photo_template_1=$photo_template;
						unset($photo_template_1["URL"]);
						$arFile["description"]=json_encode($photo_template_1);
						$fid = CFile::SaveFile($arFile,"realty_files");
						if(intval($fid)>0)
						{
							$arr["UF_PHOTOS"][]=$fid;
							$order_array_photo[]=Array("fid"=>$fid,"order"=>isset($photo_template["ORDER"])?intval($photo_template["ORDER"]):2);
						}
					}
				}
				if($photo_template["PLAN"]=="1")
				{
					$v=str_replace("\\","/",$photo_template["URL"]);
					if(is_file($dir.$v))
					{
						$arFile = CFile::MakeFileArray($dir.$v);
						$photo_template["CHANGED"]=0;
						$photo_template_1=$photo_template;
						unset($photo_template_1["URL"]);
						$arFile["description"]=json_encode($photo_template_1);
						$fid = CFile::SaveFile($arFile,"realty_files");
						if(intval($fid)>0)
						{
							$arr["UF_PLAN_PHOTOS"][]=$fid;	
							$order_array_plans[]=Array("fid"=>$fid,"order"=>isset($photo_template["ORDER"])?intval($photo_template["ORDER"]):2);
						}
					}
				}
				if($photo_template["DOC"]=="1")
				{
					$v=str_replace("\\","/",$photo_template["URL"]);
					if(is_file($dir.$v))
					{
						$arFile = CFile::MakeFileArray($dir.$v);
						$photo_template["CHANGED"]=0;
						$photo_template_1=$photo_template;
						unset($photo_template_1["URL"]);
						$arFile["description"]=json_encode($photo_template_1);
						$fid = CFile::SaveFile($arFile,"realty_files");
						if(intval($fid)>0)
						{
							$arr["UF_DOCS"][]=$fid;	
							$order_array_docs[]=Array("fid"=>$fid,"order"=>isset($photo_template["ORDER"])?intval($photo_template["ORDER"]):2);
						}
					}
				}
			}
			if(!isset($arr["UF_PHOTO_PREVIEW"])&&$preview_tmp!==false)
			{
				$photo_template = $preview_tmp;
				$v=str_replace("\\","/",$photo_template["URL"]);
				if(is_file($dir.$v))
				{
					$arFile = CFile::MakeFileArray($dir.$v);
					$photo_template["CHANGED"]=0;
					$photo_template_1=$photo_template;
					unset($photo_template_1["URL"]);
					$arFile["description"]=json_encode($photo_template_1);
					$fid = CFile::SaveFile($arFile,"realty_files");
					if(intval($fid)>0)
					{
						$arr["UF_PHOTO_PREVIEW"]=$fid;
					}
				}
			}
			else if(!isset($arr["UF_PHOTO_PREVIEW"]))
			{
				$arr["UF_PHOTO_PREVIEW"]="";
			}	
			if(sizeof($order_array_photo)>0)
			{
				usort($order_array_photo, function($a, $b){
					return ($a['order'] - $b['order']);
				});
				$arr_tmp=Array();
				foreach ($order_array_photo as $k=>$v)
				{
					$arr_tmp[]=$v["fid"];
				}
				$arr["UF_PHOTOS"] = $arr_tmp;
			}
			else
				$arr["UF_PHOTOS"] = Array();
			if(sizeof($order_array_docs)>0)
			{
				usort($order_array_docs, function($a, $b){
					return ($a['order'] - $b['order']);
				});
				$arr_tmp=Array();
				foreach ($order_array_docs as $k=>$v)
				{
					$arr_tmp[]=$v["fid"];
				}
				$arr["UF_DOCS"] = $arr_tmp;
			}
			else
				$arr["UF_DOCS"] = Array();
			if(sizeof($order_array_plans)>0)
			{
				usort($order_array_plans, function($a, $b){
					return ($a['order'] - $b['order']);
				});
				$arr_tmp=Array();
				foreach ($order_array_plans as $k=>$v)
				{
					$arr_tmp[]=$v["fid"];
				}
				$arr["UF_PLAN_PHOTOS"] = $arr_tmp;
			}
			else
				$arr["UF_PLAN_PHOTOS"] = Array();
	   }
	   if(!isset($arr["UF_PHOTO_PREVIEW"]))$arr["UF_PHOTO_PREVIEW"]="";
	   if(!isset($arr["UF_PHOTOS"]))$arr["UF_PHOTOS"]=Array();
	   if(!isset($arr["UF_PLAN_PHOTOS"]))$arr["UF_PLAN_PHOTOS"]=Array();
	   if(!isset($arr["UF_DOCS"]))$arr["UF_DOCS"]=Array();
	   $arr_ret = Array();
	   $count_field=0;
	   
		if ($arr['UF_OPERATION_TYPE']=='Аренда недвижимости: снятие') {
			$this->arr_f_names = Array(
				"UF_ID"=>1851,
				"UF_SOURCE"=>1214,
				"UF_AGENT"=>1849,
				"UF_ADDR_BLOCK"=>583,
				"UF_REALTY_TYPE"=>582,
				"UF_OBJ_TYPE"=>783,
				"UF_PRICE_FROM"=>700,
				"UF_SQUARE_FROM"=>782,
				"UF_ROOMS_FROM"=>782,
				"UF_COMMENT_ORDER"=>1930,
				"UF_REGION_ID"=>684,
				"UF_CITY"=>695
			);
		}
		else {
			$this->arr_f_names = Array(
				"UF_ID"=>1614,
				"UF_SOURCE"=>1213,
				"UF_AGENT"=>1597,
				"UF_ADDR_BLOCK"=>577,
				"UF_ADD_DATE"=>1903,
				"UF_REALTY_TYPE"=>576,
				"UF_OBJ_TYPE"=>780,
				"UF_OPERATION_TYPE"=>1920,
				"UF_STATUS"=>1921,
				"UF_COMMENT_ORDER"=>1603,
				"UF_ROOMS_FROM"=>1906,
				"UF_ROOMS_TO"=>1907,
				"UF_SQUARE_FROM"=>1908,
				"UF_SQUARE_TO"=>1909,
				"UF_PRICE_FROM"=>1910,
				"UF_PRICE_TO"=>1911,
				"UF_REGION_ID"=>682,
				"UF_CITY"=>693,
				"UF_STREET"=>1265
			);
		}
		
		foreach ($this->arr_f_names as $k=>$v) {
			if(isset($arr[$k])) {
				$count_field++;
		
				if ($count_field==2) {
					
					if ($arr[$k]=='Be Boss сайт') {
						$arr[$k]='Коммерческая недвижимость';
					}
					else if ($arr[$k]=='Facebook' || $arr[$k]=='Вконтакт' || $arr[$k]=='Инстаграм' || $arr[$k]=='Одноклассники' || $arr[$k]=='Твиттер') {
						$arr[$k]='Социальные сети';
					}
					else if ($arr[$k]=='RedHelper сервис он-лайн') {
						$arr[$k]='Сервис обратного звонка';
					}
					else if ($arr[$k]=='База данных Зайцевой' || $arr[$k]=='Найдидом.com' || $arr[$k]=='сайт betut' || $arr[$k]=='сайт BN' || $arr[$k]=='сайт Domofond' || $arr[$k]=='сайт Domofond' || $arr[$k]=='сайт kvadroom.ru' || $arr[$k]=='сайт novostroy.sy' || $arr[$k]=='сайт Гдеэтотдом' || $arr[$k]=='сайт Мир Квартир' || $arr[$k]=='Циан') {
						$arr[$k]='Агрегатор';
					}
					else if ($arr[$k]=='Балтийский Бродвей' || $arr[$k]=='БизнесБерг' || $arr[$k]=='другие (газеты)' || $arr[$k]=='журнал Шопинг' || $arr[$k]=='Из рук в руки' || $arr[$k]=='Калининградская недвижимость' || $arr[$k]=='Калининградские дома' || $arr[$k]=='Королевские ворота' || $arr[$k]=='Рынок жилья' || $arr[$k]=='Ярмарка') {
						$arr[$k]='Печатное издание';
					}
					else if ($arr[$k]=='Баннер' || $arr[$k]=='Растяжка') {
						$arr[$k]='Наружная реклама';
					}
					else if ($arr[$k]=='буклеты со стоек Аэропорт, Гостиницы' || $arr[$k]=='листовка для военных' || $arr[$k]=='наклейка около/в подъезде'){
						$arr[$k]='POSM Инвент';
					}
					else if ($arr[$k]=='Входящие рассылки' || $arr[$k]=='рассылка' || $arr[$k]=='смс рассылка') {
						$arr[$k]='Абонентская рассылка';
					}
					else if ($arr[$k]=='Выставка') {
						$arr[$k]='Эвент';
					}
					else if ($arr[$k]=='жировки Лучшего дома' || $arr[$k]=='сайт Drom Приморский край' || $arr[$k]=='сайт Наматерик' || $arr[$k]=='сборище' || $arr[$k]=='Щит') {
						$arr[$k]='Иное';
					}
					else if ($arr[$k]=='Застройщик' || $arr[$k]=='листовка ВТБ24' || $arr[$k]=='Рекомендация из Сбербанка') {
						$arr[$k]='Партнер';
					}
					else if ($arr[$k]=='Итак знаю' || $arr[$k]=='Офис' || $arr[$k]=='Повторное обращение' || $arr[$k]=='Рекомендация') {
						$arr[$k]='Рекомендация';
					}
					else if ($arr[$k]=='карта 2ГИС') {
						$arr[$k]='Справочник';
					}
					else if ($arr[$k]=='Поисковые системы') {
						$arr[$k]='Поисковые системы';
					}
					else if ($arr[$k]=='радио в с/м Виктория' || $arr[$k]=='ТВ') {
						$arr[$k]='Медиа';
					}
					else if ($arr[$k]=='сайт 27 Регион Хабаровск' || $arr[$k]=='сайт IRK Иркутска' || $arr[$k]=='сайт KZ' || $arr[$k]=='сайт ИА Дейта Владивосток' || $arr[$k]=='сайт Мой город Хабаровск' || $arr[$k]=='сайт Петропавловск-Камчатский') {
						$arr[$k]='Региональный городской портал';
					}
					else if ($arr[$k]=='сайт Flot.com' || $arr[$k]=='сайт военная.рф') {
						$arr[$k]='Отраслевой портал';
					}
					else if ($arr[$k]=='сайт Klops' || $arr[$k]=='сайт Новый Калининград') {
						$arr[$k]='Городской портал Калининград';
					}
					else if ($arr[$k]=='сайт RBC.Kaliningrad' || $arr[$k]=='сайт Rugrad') {
						$arr[$k]='Деловой портал';
					}
					else if ($arr[$k]=='сайт Авито' || $arr[$k]=='сайт Из рук в руки') {
						$arr[$k]='Сайт объявлений';
					}
					else if ($arr[$k]=='Сайт Инвент') {
						$arr[$k]='Сайт Инвент';
					}
					else if ($arr[$k]=='справка 323' || $arr[$k]=='справка 39') {
						$arr[$k]='Справочная';
					}
					else if ($arr[$k]=='Форма с сайта') {
						$arr[$k]='Сайт Инвент/застройщика';
					}
					
				}
				
				if ($count_field==3) {
					
					$request_agent = HlBlockElement::GetList(5,array(),array("UF_AGENT_ID"=>$arr[$k]),array(),1);
					$request_agent_data = $request_agent->Fetch();
					$agent_name = $request_agent_data['UF_AGENT_NAME'];
					
					if(stristr($agent_name, 'Унгурайте') === TRUE) {
						$agent_name = 'Покладок Ольга';
					}
					else if(stristr($agent_name, 'Качаева') === TRUE) {
						$agent_name = 'Покладок Ольга';
					}
					else if(stristr($agent_name, 'Джанджгава') === TRUE) {
						$agent_name = 'Пронина Наталья';
					}
					
					$arr[$k]=$agent_name;
				}
				
				if ($count_field==4) {
					$param_adres = Array();
					
					$cities = explode(PHP_EOL, $arr[$k]);
					foreach ($cities as $param) {
						$citie_param = explode('=', $param);
						$param_adres[$citie_param[0]]=$citie_param[1];
						
					}
					
					if ($param_adres['РайонГорода']=='Московский р-н') {
						$param_adres['РайонГорода']='Московский';
					}
					elseif ($param_adres['РайонГорода']=='Ленинградский р-н') {
						$param_adres['РайонГорода']='Ленинградский';
					}
					elseif ($param_adres['РайонГорода']=='Центральный р-н') {
						$param_adres['РайонГорода']='Центральный';
					}
					
					if ($arr['UF_OPERATION_TYPE']=='Аренда недвижимости: снятие') {
						$arr_ret[]=Array(
							"id"=>684,
							"value"=>'Не указан'
						);
						$arr_ret[]=Array(
							"id"=>694,
							"value"=>$param_adres['Регион']
						);
						$arr_ret[]=Array(
							"id"=>695,
							"value"=>$param_adres['Город']
						);
						$arr_ret[]=Array(
							"id"=>1156,
							"value"=>$param_adres['РайонГорода']
						);
						$arr_ret[]=Array(
							"id"=>583,
							"value"=>$param_adres['Улица']
						);
					}
					else {
						$arr_ret[]=Array(
							"id"=>682,
							"value"=>'Не указан'
						);
						$arr_ret[]=Array(
							"id"=>692,
							"value"=>$param_adres['Регион']
						);
						$arr_ret[]=Array(
							"id"=>693,
							"value"=>$param_adres['Город']
						);
						$arr_ret[]=Array(
							"id"=>1155,
							"value"=>$param_adres['РайонГорода']
						);
						$arr_ret[]=Array(
							"id"=>577,
							"value"=>$param_adres['Улица']
						);
					}
					
				}
				
				if ($arr['UF_OPERATION_TYPE']=='Аренда недвижимости: снятие') {
					
					if ($count_field==5) {
						
						$request_elem = HlBlockElement::GetList(2,array(),array("UF_ID"=>$arr["UF_ID"]),array(),1);
						$request_elem_data = $request_elem->Fetch();
						$request_category = $request_elem_data['UF_REALTY_TYPE'];
						$request_type = $request_elem_data['UF_OBJ_TYPE'];
						$request_operationtypes = $request_elem_data['UF_OPERATION_TYPE'];
											
						$request_cat = HlBlockElement::GetList(3,array(),array("UF_OBJ_TYPE_ID"=>$request_type),array(),1);
						$request_cat_data = $request_cat->Fetch();
						
						$request_typ = HlBlockElement::GetList(4,array(),array("UF_REALTY_TYPE_ID"=>$request_category),array(),1);
						$request_typ_data = $request_typ->Fetch();
						
						$cat_name = $request_cat_data['UF_NAME'];
						$type_name = $request_typ_data['UF_REALTY_TYPE_NAME'];
						
						if ($cat_name=='Банковское помещение') {
							$cat_name='коммерческая';
							$type_name='свободного назначения';
						}
						else if ($cat_name=='Банковское помещение') {
							$cat_name='коммерческая';
							$type_name='';
						}
						else if ($cat_name=='Земельный участок') {
							$cat_name='коммерческая';
							$type_name='земля коммерческого назначения';
						}
						else if ($cat_name=='Инвестпроект (готовый бизнес)') {
							$cat_name='коммерческая';
							$type_name='свободного назначения';
						}
						else if ($cat_name=='Офисное помещение') {
							$cat_name='коммерческая';
							$type_name='офис';
						}
						else if ($cat_name=='Помещение для оказания услуг') {
							$cat_name='коммерческая';
							$type_name='свободного назначения';
						}
						else if ($cat_name=='Помещение для сферы развлечений') {
							$cat_name='коммерческая';
							$type_name='ресторан';
						}
						else if ($cat_name=='Помещение свободного назначения') {
							$cat_name='коммерческая';
							$type_name='свободного назначения';
						}
						else if ($cat_name=='Помещения под заведения общепита') {
							$cat_name='коммерческая';
							$type_name='ресторан';
						}
						else if ($cat_name=='Производственное помещение') {
							$cat_name='коммерческая';
							$type_name='склад';
						}
						else if ($cat_name=='Склад') {
							$cat_name='коммерческая';
							$type_name='склад';
						}
						else if ($cat_name=='Торговое помещение') {
							$cat_name='коммерческая';
							$type_name='магазин';
						}
						else if ($cat_name=='Дача') {
							$cat_name='дома и участки';
							$type_name='дача';
						}
						else if ($cat_name=='Индивидуальный дом') {
							$cat_name='дома и участки';
							$type_name='дом';
						}
						else if ($cat_name=='Секция') {
							$cat_name='дома и участки';
							$type_name='коттедж';
						}
						else if ($cat_name=='Земля') {
							$cat_name='дома и участки';
							$type_name='участок';
						}
						else if ($cat_name=='Гаражи и стоянки') {
							$cat_name='другая';
							$type_name='гараж';
						}
						else if ($cat_name=='Квартира' && $request_operationtypes==143) {
							$cat_name='квартиры и комнаты';
							$type_name='квартира вторичка';
						}
						else if ($cat_name=='Квартира' && $request_operationtypes==292) {
							$cat_name='квартиры и комнаты';
							$type_name='квартира новостройка';
						}
						else if ($cat_name=='Комната') {
							$cat_name='квартиры и комнаты';
							$type_name='комнаты и доли';
						}
						else if ($cat_name=='Квартира' && $type_name=='Аренда жилая') {
							$cat_name='квартиры и комнаты';
							$type_name='';
						}
						else if ($cat_name=='Комната' && $type_name=='Аренда жилая') {
							$cat_name='квартиры и комнаты';
							$type_name='';
						}
						else {
							$cat_name='коммерческая';
							$type_name='';
						}
						
						$arr_ret[]=Array(
							"id"=>582,
							"value"=>$cat_name
						);
						$arr_ret[]=Array(
							"id"=>783,
							"value"=>$type_name
						);
						
					}
					
					if ($count_field==7) {
						
						$request_price = HlBlockElement::GetList(2,array(),array("UF_ID"=>$arr["UF_ID"]),array(),1);
						$request_price_data = $request_price->Fetch();

						$request_price_from = $request_price_data['UF_PRICE_FROM'];
						$request_price_to = $request_price_data['UF_PRICE_TO'];
						
						$arr_ret[]=Array(
							"id"=>700,
							"value"=>Array($request_price_from,$request_price_to)
						);
						
					}
					
					if ($count_field==8) {
						
						$request_rooms = HlBlockElement::GetList(2,array(),array("UF_ID"=>$arr["UF_ID"]),array(),1);
						$request_rooms_data = $request_rooms->Fetch();

						$request_rooms_from = $request_rooms_data['UF_ROOMS_FROM'];
						$request_rooms_to = $request_rooms_data['UF_ROOMS_TO'];
						
						$arr_ret[]=Array(
							"id"=>775,
							"value"=>Array($request_rooms_from,$request_rooms_to)
						);
						
					}
					
				}
				else {
					if ($count_field==6) {
						
						$request_elem = HlBlockElement::GetList(2,array(),array("UF_ID"=>$arr["UF_ID"]),array(),1);
						$request_elem_data = $request_elem->Fetch();
						$request_category = $request_elem_data['UF_REALTY_TYPE'];
						$request_type = $request_elem_data['UF_OBJ_TYPE'];
						$request_operationtypes = $request_elem_data['UF_OPERATION_TYPE'];
											
						$request_cat = HlBlockElement::GetList(3,array(),array("UF_OBJ_TYPE_ID"=>$request_type),array(),1);
						$request_cat_data = $request_cat->Fetch();
						
						$request_typ = HlBlockElement::GetList(4,array(),array("UF_REALTY_TYPE_ID"=>$request_category),array(),1);
						$request_typ_data = $request_typ->Fetch();
						
						$cat_name = $request_cat_data['UF_NAME'];
						$type_name = $request_typ_data['UF_REALTY_TYPE_NAME'];
						
						if ($cat_name=='Банковское помещение') {
							$cat_name='коммерческая';
							$type_name='свободного назначения';
						}
						else if ($cat_name=='Банковское помещение') {
							$cat_name='коммерческая';
							$type_name='';
						}
						else if ($cat_name=='Земельный участок') {
							$cat_name='коммерческая';
							$type_name='земля коммерческого назначения';
						}
						else if ($cat_name=='Инвестпроект (готовый бизнес)') {
							$cat_name='коммерческая';
							$type_name='свободного назначения';
						}
						else if ($cat_name=='Офисное помещение') {
							$cat_name='коммерческая';
							$type_name='офис';
						}
						else if ($cat_name=='Помещение для оказания услуг') {
							$cat_name='коммерческая';
							$type_name='свободного назначения';
						}
						else if ($cat_name=='Помещение для сферы развлечений') {
							$cat_name='коммерческая';
							$type_name='ресторан';
						}
						else if ($cat_name=='Помещение свободного назначения') {
							$cat_name='коммерческая';
							$type_name='свободного назначения';
						}
						else if ($cat_name=='Помещения под заведения общепита') {
							$cat_name='коммерческая';
							$type_name='ресторан';
						}
						else if ($cat_name=='Производственное помещение') {
							$cat_name='коммерческая';
							$type_name='склад';
						}
						else if ($cat_name=='Склад') {
							$cat_name='коммерческая';
							$type_name='склад';
						}
						else if ($cat_name=='Торговое помещение') {
							$cat_name='коммерческая';
							$type_name='магазин';
						}
						else if ($cat_name=='Дача') {
							$cat_name='дома и участки';
							$type_name='дача';
						}
						else if ($cat_name=='Индивидуальный дом') {
							$cat_name='дома и участки';
							$type_name='дом';
						}
						else if ($cat_name=='Секция') {
							$cat_name='дома и участки';
							$type_name='коттедж';
						}
						else if ($cat_name=='Земля') {
							$cat_name='дома и участки';
							$type_name='участок';
						}
						else if ($cat_name=='Гаражи и стоянки') {
							$cat_name='другая';
							$type_name='гараж';
						}
						else if ($cat_name=='Квартира' && $request_operationtypes==143) {
							$cat_name='квартиры и комнаты';
							$type_name='квартира вторичка';
						}
						else if ($cat_name=='Квартира' && $request_operationtypes==292) {
							$cat_name='квартиры и комнаты';
							$type_name='квартира новостройка';
						}
						else if ($cat_name=='Комната') {
							$cat_name='квартиры и комнаты';
							$type_name='комнаты и доли';
						}
						else {
							$cat_name='коммерческая';
							$type_name='';
						}
						
						$arr_ret[]=Array(
							"id"=>576,
							"value"=>$cat_name
						);
						$arr_ret[]=Array(
							"id"=>780,
							"value"=>$type_name
						);
						
					}
				}
				
				if ($arr['UF_OPERATION_TYPE']=='Аренда недвижимости: снятие') {
					
					if ($v!=583 && $v!=582 && $v!=700 && $v!=775 && $v!=782 && $v!=783) {
						$arr_ret[]=Array(
							"id"=>$v,
							"value"=>$arr[$k]
						);
					}
				}
				else {
					if ($v!=577 && $v!=576 && $v!=780) {
						
						$arr_ret[]=Array(
							"id"=>$v,
							"value"=>$arr[$k]
						);
					}
				}
			}
	   }
	   $phone="";

	   $request_cust = HlBlockElement::GetList(10,array(),array("UF_ID_1C"=>$arr["UF_CONTRAGENT"]),array(),1);
	   $request_data_cust = $request_cust->Fetch();
	   if(!empty( $request_data_cust))
		{
			$phone=  $request_data_cust["UF_PHONE"];
			$fio=  $request_data_cust["UF_FIO"];
		}
		if ($phone=='') {
			$phone='123';
		}
		
	    global $api;
		if(trim($phone)!="")
		{
			$res = $api->filterCustomers(array(
				'limit'  => 1,
				'search' => $phone
			));
			$api_use++;
			
			
			$customerId=0;
			if($res["data"]["count"]>0)
			{
				$customerId=$res["data"]["list"][0]["id"];
			}
			else {
				
				$res_add = $api->insertCustomers(array(array(
					   'name'  => $fio,
					   'phone' => array($phone)
				)));
				$api_use++;
				
				$res_check = $api->filterCustomers(array(
					'limit'  => 1,
					'search' => $phone
				));
				$api_use++;
				
				//echo "<xmp>";print_r($res_add);echo "</xmp>";
				
				if($res_check["data"]["count"]>0)
				{
					$customerId=$res_check["data"]["list"][0]["id"];
				}
				
			}
			
			$types = $api->getRequestTypes();
			$api_use++;
			if ($arr['UF_OPERATION_TYPE']=='Аренда недвижимости: снятие') {
				$typeId = 2;
			}
			else {
				$typeId = 1;
			}
			$fields = $api->getRequestFields();
			$api_use++;
			$myFields = $fields['data'][$typeId]['fields'];
			
			
			/*"<xmp>";print_r($fields);echo "</xmp>";
			echo die();*/
	
			/*$res = $api->filterRequests(array(
				'limit'  => 1,
				'fields' => array(
					array(
						'id' => 1614,
						'value' => $arr["UF_ID"]
					),
	)));*/
			//	print_r($res);
			//Выборка самих заявок
			/*	$res = $api->filterRequests(array(
				'by_ids'   => $res['data']['list']
			));
	
	$request_agent = HlBlockElement::GetList(5,array(),array("UF_AGENT_ID"=>$arr[$k]),array(),1);
	$request_agent_data = $request_agent->Fetch();
	$agent_name = $request_agent_data['UF_AGENT_NAME'];*/
	
	$pieces_agent = explode(" ", $agent_name);
	$res_otvetstvennyi = $api->filterEmployee(array(
        'name' => $pieces_agent[1],
		'surname' => $pieces_agent[0]
    )); 
	$api_use++;
	$first_elem_array = array_shift($res_otvetstvennyi['data']);
	$employee_id = $first_elem_array['id'];
	
	if ($arr['UF_OPERATION_TYPE']=='Аренда недвижимости: снятие') {
		
		$request_square = HlBlockElement::GetList(2,array(),array("UF_ID"=>$arr["UF_ID"]),array(),1);
		$request_square_data = $request_square->Fetch();

		$request_square_from = $request_square_data['UF_SQUARE_FROM'];
		$request_square_to = $request_square_data['UF_SQUARE_TO'];
		
		$arr_ret[]=Array(
			"id"=>782,
			"value"=>Array($request_square_from,$request_square_to)
		);
		
	}
	
			if($customerId>0)
			{
				$res = $api->insertRequests(array(
					array(
					   'request_type'  => $typeId,
					   'customers_id'  => $customerId,
					   'source'        => 'help_manager',
					   'employee_id'   => $employee_id,
					   'status'        => 'reprocess',
					   'fields' => $arr_ret
					)
				));
				$api_use++;
				$comment = $api->addRequestComment(array(
					'enity_id' => $res['data'][0], //ID объекта в Интруме
					'text'     => $arr["UF_COMMENT_ORDER"], //Текст комментария
					'author'   => 0, //ID ползователя в Интруме, от чьего имени будет комментарий, если передать 0, комментарий будет от имени системы,
				));
				$api_use++;
				
				$fp=fopen("import_log.txt","a");  
				fwrite($fp, " - uploaded succesfull with ID: ".$res['data'][0]." and ".$api_use." api use\n");  
				fclose($fp);
				
			}
		}
   }
}
?>