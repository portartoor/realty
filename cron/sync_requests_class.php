<?
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
set_time_limit(60*10);
function xml2array ( $xmlObject, $out = array () )
{
    foreach ( (array) $xmlObject as $index => $node )
        $out[strtoupper($index)] = ( is_object ( $node ) ) ? xml2array ( $node ) : $node;
    return $out;
}
/*$dir = $APPLICATION->GetCurDir();
$dir_main = $dir;*/
class SyncRequest {
	private $enum_arr=Array();
	private $arr_fields=Array();
	private $highloadblock;
	private $postfix = "";
	function __construct(){
		$enum_arr=array();
		global $Project;
		$data_res = $Project->get_requests_file();
		$this->highloadblock = $data_res["hblock"];
		$this->postfix = $Project->get_postfix();
		$rsData = HlBlockElement::GetList(4,array(),array(),array(),100);

		while($arRes = $rsData->Fetch())
		{
			$enum_arr["UF_REALTY_TYPE"][$arRes["UF_REALTY_TYPE_NAME"]]=$arRes["UF_REALTY_TYPE_ID"];
		}
		$enum_arr["UF_REALTY_TYPE"]["Аренда жилья"]=5;
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
		$rsData = CUserTypeEntity::GetList( array(), array("ENTITY_ID"=>"HLBLOCK_".$this->highloadblock,"USER_TYPE_ID"=>"boolean") );
		//die($this->postfix."!!");
		while($arRes = $rsData->Fetch())
		{	
			$arRes["FIELD_NAME"] = str_replace($this->postfix,"",$arRes["FIELD_NAME"]);
			$enum_arr[$arRes["FIELD_NAME"]]["Yes"]=1;
			$enum_arr[$arRes["FIELD_NAME"]]["да"]=1;
			$enum_arr[$arRes["FIELD_NAME"]]["Да"]=1;
			$enum_arr[$arRes["FIELD_NAME"]]["нет"]="";
			$enum_arr[$arRes["FIELD_NAME"]]["Нет"]="";
			$enum_arr[$arRes["FIELD_NAME"]]["No"]="";
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

		if(!empty($request_data))
		{
			
			$request_id=$request_data["ID"];
			if($request_data["UF_UPDATE_DATE"]!=$arr["UF_UPDATE_DATE"]&&false)
				HlBlockElement::Remove($this->highloadblock,$request_id);
		}
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
		if($arr["UF_DIVISION_HEAD"]!="")
		{
			$request_agent = HlBlockElement::GetList($Project->get_agents_hb_id(),array(),array("UF_AGENT_ID".$this->postfix=>$arr["UF_DIVISION_HEAD"],"!UF_HEAD_1C".$this->postfix=>$arr["UF_DIVISION_HEAD"]),array(),1);
			$request_agent_data = $request_agent->Fetch();
			if(!empty($request_agent_data))
			{
				$res = HlBlockElement::Update($Project->get_agents_hb_id(),$request_agent_data["ID"],Array("UF_HEAD_1C".$this->postfix=>$arr["UF_DIVISION_HEAD"]));
			}
		}
		$arr = $Project->add_postfix_to_fields($arr);
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
			
		}
   }
}
?>