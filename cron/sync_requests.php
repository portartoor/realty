<?
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
set_time_limit(60*10);
ini_set('memory_limit', '1024M');
require($_SERVER["DOCUMENT_ROOT"]."/libs/rights_class.php");
$Project = new Rights();
$data_res = $Project->get_requests_file();
$postfix  = $Project->get_postfix();
$postfix_d = "";
if($Project->s_name=="domofey")
	$postfix_d="d_";
function xml2array ( $xmlObject, $out = array () )
{
    foreach ( (array) $xmlObject as $index => $node )
        $out[strtoupper($index)] = ( is_object ( $node ) ) ? xml2array ( $node ) : $node;
    return $out;
}
$i = isset($_GET["i"])?intval($_GET["i"]):0;
$all_upd = Array (
	0 => Array(
		"type" => "hblock",
		"file" => "new_orders.xml",
		"name" => "Requests",
		"hblock" => $data_res["hblock"]),
	1 => Array(
		"type" => "hblock",
		"file" => "orders.xml",
		"name" => "Requests",
		"hblock" => $data_res["hblock"])
);
$step = (isset($_GET["step"]))?intval($_GET["step"]):0;

$type=$all_upd[$i]["type"];
$file = $all_upd[$i]["file"];
$name= $all_upd[$i]["name"];
$hblock= $all_upd[$i]["hblock"];
$project_map = $Project->get_map();
if($project_map!="")$project_map=$project_map."/".$postfix_d;
echo ($_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$project_map."new_orders.zip");
if($i==0)
{
	if (file_exists($_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$project_map."new_orders.zip")) 
	{
		$zip = new ZipArchive(); 
		if($zip->open($_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$project_map."new_orders.zip") === true) {
			$flag = $zip->extractTo($_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$project_map."foto/"); 
			$zip->close(); 
			if($flag===true)
			{
				if (copy($_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$project_map."new_orders.zip", $_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$project_map."old/".date('Y-m-d_H-i')."_new_orders.zip")) {
				   unlink($_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$project_map."new_orders.zip");
				}
			}
		}
	}
}

$dir = $APPLICATION->GetCurDir();
$dir_main = $dir;
$enum_arr=array();

$rsData = HlBlockElement::GetList(4,array(),array(),array(),100);

while($arRes = $rsData->Fetch())
{
	$enum_arr["UF_REALTY_TYPE"][$arRes["UF_REALTY_TYPE_NAME"]]=$arRes["UF_REALTY_TYPE_ID"];
}
$enum_arr["UF_REALTY_TYPE"]["Аренда жилья"]=5;
$arr_fields = Array();
$rsData_all = CUserTypeEntity::GetList( array(), array("ENTITY_ID"=>"HLBLOCK_".$data_res["hblock"]) );
while($arRes = $rsData_all->Fetch())
{	
	if(strlen($postfix)>0)
		$arr_fields[]=str_replace($postfix,"",$arRes["FIELD_NAME"]);
	else
		$arr_fields[]=$arRes["FIELD_NAME"];
}
$arr_fields[]="UF_OBJECT_TYPE";
if($postfix!="")
{
	$arr_fields[]="UF_LAND_OWNER_TYPE";
	$arr_fields[]="UF_NARUZHN_REKLAMA";
}
$rsData = CUserTypeEntity::GetList( array(), array("ENTITY_ID"=>"HLBLOCK_".$data_res["hblock"],"USER_TYPE_ID"=>"enumeration") );
while($arRes = $rsData->Fetch())
{	
	$rs = CUserFieldEnum::GetList(array(), array(
			"USER_FIELD_NAME" => $arRes["FIELD_NAME"]
		));
	$arRes["FIELD_NAME"] = str_replace($postfix,"",$arRes["FIELD_NAME"]);
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
$rsData = CUserTypeEntity::GetList( array(), array("ENTITY_ID"=>"HLBLOCK_".$data_res["hblock"],"USER_TYPE_ID"=>"boolean") );
while($arRes = $rsData->Fetch())
{	
	$enum_arr[$arRes["FIELD_NAME"]]["Yes"]=1;
	$enum_arr[$arRes["FIELD_NAME"]]["да"]=1;
	$enum_arr[$arRes["FIELD_NAME"]]["Да"]=1;
	$enum_arr[$arRes["FIELD_NAME"]]["нет"]="";
	$enum_arr[$arRes["FIELD_NAME"]]["Нет"]="";
	$enum_arr[$arRes["FIELD_NAME"]]["No"]="";
}
//echo "<xmp>";print_r($enum_arr);echo "</xmp>";

if (file_exists($_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$project_map.$file)) {
    $xml = simplexml_load_file($_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$project_map.$file);
	foreach($xml->item as $value)
	{
		$delete=false;
		$arr_photos = Array();
	    foreach ($value as $x=>$y)
		{
			if(strtoupper($x)=="UF_PHOTOS")
			{
				$arr_photos = $y;
			}
			else
				$arr[strtoupper($x)] = trim((string)$y);
		}
		$division_head = $arr["UF_DIVISION_HEAD"];
		$str_photos ="";
		if(isset($arr["UF_ENUMERATE_PHOTO"]))
			$str_photos = $arr["UF_ENUMERATE_PHOTO"];
		$arr = Array();
		$interes=Array();
		foreach ($value as $x=>$y)
		{
			if($x=="uf_interes")
			{
				$interes = explode(",",trim((string)$y));
			}
			if(in_array(strtoupper($x),$arr_fields)||strtoupper($x)=="UF_ELECTRICITY"||strtoupper($x)=="UF_GOAL_LAND")
				$arr[strtoupper($x)] = trim((string)$y);
		}
		if(!empty($arr))
		{//echo "<xmp>";print_r($arr);echo "</xmp>";
			$arr["UF_ADD_DATE"]=date("d.m.Y H:i:s", strtotime($arr["UF_ADD_DATE"]));
			$arr["UF_UPDATE_DATE"]=date("d.m.Y H:i:s", strtotime($arr["UF_UPDATE_DATE"]));
			$arr["UF_OBJ_TYPE"]=$arr["UF_OBJECT_TYPE"];
			$arr["UF_ELECTICITY"]=$arr["UF_ELECTRICITY"];
			if($postfix!="")
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
		//echo "<xmp>";print_r($arr);echo "</xmp>";
		$request = HlBlockElement::GetList($data_res["hblock"],array(),array("UF_ID".$postfix=>intval($arr["UF_ID"])),array(),1);
		$request_data = $request->Fetch();
		$id_photos_arr = Array();
		if(!empty($request_data))
		{
			$request_id=$request_data["ID"];
			if($request_data["UF_UPDATE_DATE"]!=$arr["UF_UPDATE_DATE"]&&false)
				HlBlockElement::Remove($data_res["hblock"],$request_id);
			
			foreach (Array("UF_PHOTOS","UF_PLAN_PHOTOS","UF_DOCS") as $ph_name)
			{
				foreach ($request_data[$ph_name] as $g1=>$h1)
				{
					$file_info = CFile::GetByID($h1);
					$arFile = $file_info->Fetch();
					$desc_line = $arFile["DESCRIPTION"];
					$desc_arr = json_decode($desc_line,1);
					$id_photos_arr[$ph_name][$desc_arr["ID"]]=$h1;
				}
			}
		}
		$arr["UF_INNER_STATUS"]=4;
		//echo "<xmp>";print_r($arr);echo "</xmp>";
		foreach ($arr as $x=>$y)
		{
			if(isset($enum_arr[$x])&&$arr[$x]!=""&&$arr[$x]!="0")
			{
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
		}
		
		if($arr["UF_GOAL_LAND"]!="")
		{
			$arr["UF_GOAL"] = intval("1".intval($arr["UF_GOAL_LAND"]));
		}
		else if($arr["UF_GOAL"]!="")
			$arr["UF_GOAL"] = intval("6".intval($arr["UF_GOAL"]));
		unset($arr["UF_GOAL_LAND"]);
		unset($arr["UF_WASHING_MACHINE"]);
		
		//echo "<xmp>";print_r($arr);echo "</xmp>";
		
		/*if($str_photos!="")
		{
			$arr_photos = explode(";",$str_photos);
			$uploaddir = "1c/foto/".$arr["UF_ID"]."/";
			$dir = $_SERVER["DOCUMENT_ROOT"]."/upload/".$uploaddir;
			if(file_exists($dir))
			{
				foreach($arr_photos as $k=>$v)
				{
					if($v!=""&&is_file($dir.$v))
					{
						$arFile = CFile::MakeFileArray($dir.$v);
						$fid = CFile::SaveFile($arFile,"realty_files");
						if(intval($fid)>0)
						{
							if($k==0)
								$arr["UF_PHOTO_PREVIEW"]=$fid;
							else
								$arr["UF_PHOTOS"][]=$fid;
						}
					}
				}
			}
			$uploaddir = "1c/foto/P".$arr["UF_ID"]."/";
			$dir = $_SERVER["DOCUMENT_ROOT"]."/upload/".$uploaddir;
			if(file_exists($dir))
			{
				$files = scandir($dir);
				foreach($files as $file_i) 
				{	
					if(is_file($dir.$file_i))
					{
						$arFile = CFile::MakeFileArray($dir.$file_i);
						$fid = CFile::SaveFile($arFile,"realty_files");
						if(intval($fid)>0)
							$arr["UF_PLAN_PHOTOS"][]=$fid;	
					}
				}
			}
			$uploaddir = "1c/foto/D".$arr["UF_ID"]."/";
			$dir = $_SERVER["DOCUMENT_ROOT"]."/upload/".$uploaddir;
			if(file_exists($dir))
			{
				$files = scandir($dir);
				foreach($files as $file_i) {
					if(is_file($dir.$file_i))
					{
						$arFile = CFile::MakeFileArray($dir.$file_i);
						$fid = CFile::SaveFile($arFile,"realty_files");
						if(intval($fid)>0)
							$arr["UF_DOCS"][]=$fid;	
					}
				}
			}
		}*/
		if(isset($arr_photos)&&sizeof($arr_photos)>0)
	   {
		    $preview_tmp=false;
		    $order_array_photo=Array();
			$order_array_plans=Array();
			$order_array_docs=Array();
			foreach ($arr_photos as $k=>$photo_template_xml)
			{	
				$photo_template = xml2array($photo_template_xml);
				//print_r();
				$dir = $_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$project_map."foto/".$arr["UF_ID"]."/";
				if($photo_template["PREVIEW"]=="1")
				{
					$v=str_replace("\\","/",$photo_template["URL"]);
					if(is_file($dir.$v))
					{
						$photo_template["CHANGED"]=0;
						$arFile = CFile::MakeFileArray($dir.$v);
						$s_ph=$photo_template;
						unset($s_ph["URL"]);
						$arFile["description"]=json_encode($s_ph);
						$fid = CFile::SaveFile($arFile,"realty_files");
						if(intval($fid)>0)
						{
							$arr["UF_PHOTO_PREVIEW"]=$fid;
						}
					}
					else
					{
						if($request_data["UF_PHOTO_PREVIEW"]>0)
						{
							$file_info = CFile::GetByID($request_data["UF_PHOTO_PREVIEW"]);
							$arFile = $file_info->Fetch();
							$desc_line = $arFile["DESCRIPTION"];
							$desc_arr = json_decode($desc_line,1);
							if($desc_arr["ID"]==$photo_template["ID"])
							{
								$desc_arr["CHANGED"]=0;
								$s_ph=$desc_arr;
								unset($s_ph["URL"]);
								CFile::UpdateDesc($v,json_encode($s_ph));
							}
						}
					}
				}
				else if($photo_template["PHOTO"]=="1")
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
						$s_ph=$photo_template;
						unset($s_ph["URL"]);
						$arFile["description"]=json_encode($s_ph);
						$fid = CFile::SaveFile($arFile,"realty_files");
						if(intval($fid)>0)
						{
							$arr["UF_PHOTOS"][]=$fid;
							$order_array_photo[]=Array("fid"=>$fid,"order"=>isset($photo_template["ORDER"])?intval($photo_template["ORDER"]):2);
						}
					}
					else
					{
						if(isset($id_photos_arr["UF_PHOTOS"][$photo_template["ID"]]))
						{
							$s_ph=$photo_template;
							unset($s_ph["URL"]);
							$s_ph["CHANGED"]=0;
							CFile::UpdateDesc($id_photos_arr["UF_PHOTOS"][$photo_template["ID"]],json_encode($s_ph));
							$arr["UF_PHOTOS"][]=$id_photos_arr["UF_PHOTOS"][$photo_template["ID"]];
							$order_array_photo[]=Array("fid"=>$id_photos_arr["UF_PHOTOS"][$photo_template["ID"]],"order"=>isset($photo_template["ORDER"])?intval($photo_template["ORDER"]):2);
						}
					}
				}
				else if($photo_template["PLAN"]=="1")
				{
					$v=str_replace("\\","/",$photo_template["URL"]);
					if(is_file($dir.$v))
					{
						$arFile = CFile::MakeFileArray($dir.$v);
						$photo_template["CHANGED"]=0;
						$s_ph=$photo_template;
						unset($s_ph["URL"]);
						$arFile["description"]=json_encode($s_ph);
						$fid = CFile::SaveFile($arFile,"realty_files");
						if(intval($fid)>0)
						{
							$arr["UF_PLAN_PHOTOS"][]=$fid;
							$order_array_plans[]=Array("fid"=>$fid,"order"=>isset($photo_template["ORDER"])?intval($photo_template["ORDER"]):2);
						}
					}
					else
					{
						if(isset($id_photos_arr["UF_PLAN_PHOTOS"][$photo_template["ID"]]))
						{
							$s_ph=$photo_template;
							unset($s_ph["URL"]);
							$s_ph["CHANGED"]=0;
							CFile::UpdateDesc($id_photos_arr["UF_PLAN_PHOTOS"][$photo_template["ID"]],json_encode($s_ph));
							$arr["UF_PLAN_PHOTOS"][]=$id_photos_arr["UF_PLAN_PHOTOS"][$photo_template["ID"]];
							$order_array_plans[]=Array("fid"=>$id_photos_arr["UF_PLAN_PHOTOS"][$photo_template["ID"]],"order"=>isset($photo_template["ORDER"])?intval($photo_template["ORDER"]):2);
						}
					}
				}
				else if($photo_template["DOC"]=="1")
				{
					$v=str_replace("\\","/",$photo_template["URL"]);
					if(is_file($dir.$v))
					{
						$arFile = CFile::MakeFileArray($dir.$v);
						$photo_template["CHANGED"]=0;
						$s_ph=$photo_template;
						unset($s_ph["URL"]);
						$arFile["description"]=json_encode($s_ph);
						$fid = CFile::SaveFile($arFile,"realty_files");
						if(intval($fid)>0)
						{
							$arr["UF_DOCS"][]=$fid;	
							$order_array_docs[]=Array("fid"=>$fid,"order"=>isset($photo_template["ORDER"])?intval($photo_template["ORDER"]):2);
						}
					}
					else
					{
						if(isset($id_photos_arr["UF_DOCS"][$photo_template["ID"]]))
						{
							$s_ph=$photo_template;
							unset($s_ph["URL"]);
							$s_ph["CHANGED"]=0;
							CFile::UpdateDesc($id_photos_arr["UF_DOCS"][$photo_template["ID"]],json_encode($s_ph));
							$arr["UF_DOCS"][]=$id_photos_arr["UF_DOCS"][$photo_template["ID"]];
							$order_array_docs[]=Array("fid"=>$id_photos_arr["UF_DOCS"][$photo_template["ID"]],"order"=>isset($photo_template["ORDER"])?intval($photo_template["ORDER"]):2);
						}
					}
				}
			}
			if(!isset($arr["UF_PHOTO_PREVIEW"])&&$preview_tmp!=false)
			{//print_r($preview_tmp);
				$photo_template = $preview_tmp;
				$v=str_replace("\\","/",$photo_template["URL"]);
				if(is_file($dir.$v))
				{
					$arFile = CFile::MakeFileArray($dir.$v);
					$photo_template["CHANGED"]=0;
					$s_ph=$photo_template;
					unset($s_ph["URL"]);
					$arFile["description"]=json_encode($s_ph);
					$fid = CFile::SaveFile($arFile,"realty_files");
					if(intval($fid)>0)
					{
						$arr["UF_PHOTO_PREVIEW"]=$fid;
					}
				}
			}
			else if(!isset($arr["UF_PHOTO_PREVIEW"]))
			{
				$arr["UF_PHOTO_PREVIEW"]="";//echo "!!!";
			}//else echo "???";
			
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
	   if($division_head!="")
		{
			$request_agent = HlBlockElement::GetList($Project->get_agents_hb_id(),array(),array("UF_AGENT_ID".$postfix=>$arr["UF_AGENT"],"!=UF_HEAD_1C".$postfix=>$division_head),array(),1);
			$request_agent_data = $request_agent->Fetch();
			if(!empty($request_agent_data))
			{
				$res = HlBlockElement::Update($Project->get_agents_hb_id(),$request_agent_data["ID"],Array("UF_HEAD_1C".$postfix=>$division_head));
			}
		}
	   $arr = $Project->add_postfix_to_fields($arr);
	   //print_r($arr);die();
		if(empty($request_data)&&!$delete)
		{
			$res = HlBlockElement::Add($data_res["hblock"],$arr);
			$request_id = $res->getid();
			if($arr["UF_COORDINATOR"]==1&&$request_id>0&&$arr["UF_ID"]==99836)
			{
				$res = HlBlockElement::Add($Project->get_call_hb_id(),Array("UF_CALL_TIME".$postfix=>date("d.m.Y H:i:s")/*,"UF_AG_CODE".$postfix=>Array($arr["UF_AGENT".$postfix])*/,"UF_REQ_ID".$postfix=>$request_id,"UF_ACT".$postfix=>1,"UF_AGENTCODE_C".$postfix=>$arr["UF_AGENT".$postfix]));
				$request_agent = HlBlockElement::GetList($Project->get_agents_hb_id(),array("UF_BITRIX_USER".$postfix),array("UF_AGENT_ID".$postfix=>$arr["UF_AGENT".$postfix]),array(),1);
				if($request_agent_data = $request_agent->Fetch())
				{
					$user_id=$request_agent_data["UF_BITRIX_USER".$postfix];
					//$user_id=752;
					if(IsModuleInstalled("im") && CModule::IncludeModule("im"))
					{
						$arMessageFields = array(
							"TO_USER_ID" => $user_id,
							"FROM_USER_ID" => 0, 
							"NOTIFY_TYPE" => IM_NOTIFY_SYSTEM, 
							"NOTIFY_MODULE" => "im", 
							"NOTIFY_TAG" => "IM_CONFIG_NOTICE", 
							"NOTIFY_MESSAGE" => '[b]<a href="/mobile/realty/view/?REQUEST_ID='.$request_id.'">Новая заявка</a>[/b][br] Новая заявка для вас. Просьба связаться с клиентом в течение 15 минут, иначе заявка будет передана следующему дежурному агенту ([b]тестовый режим[/b])',
						);
						CIMNotify::Add($arMessageFields);
					}
				}
			}
		}
		else
		{	
			if(!$delete)
			{
				$res = HlBlockElement::Update($data_res["hblock"],$request_id,$arr);
			}
			else if(!empty($request_data))
			{
				$res = HlBlockElement::Remove($data_res["hblock"],$request_id);	
			}
			/*$get_request = HlBlockElement::GetList($Project->get_call_hb_id(),array("ID"),array("UF_REQ_ID".$postfix=>$request_id),array(),1);
			while($get_request_to = $get_request->Fetch())
			{
				$res = HlBlockElement::Remove($Project->get_call_hb_id(),$get_request_to["ID"]);	
			}*/
		}
		if(sizeof($interes)>0&&intval($interes[0])>0)
		{
			$get_request_id_arr = Array();
			$get_request = HlBlockElement::GetList($data_res["hblock"],array("ID"),array("UF_ID".$postfix=>$interes),array(),100);
			while($get_request_to = $get_request->Fetch())
			{
				$get_request_id_arr[] = $get_request_to["ID"];
			}
			$interes = $get_request_id_arr;
			$interes_to = HlBlockElement::GetList($Project->get_interes(),array(),array("UF_REQUEST_S".$postfix=>$request_id),array(),100);
			while($interes_to_arr = $interes_to->Fetch())
			{
				if($key = array_search($interes_to_arr["UF_REQUEST_F".$postfix],$interes));
				{
					if($interes_to_arr["UF_ACTIVE".$postfix]!=1)
					{
						$interes_update[] = $interes_to_arr["ID"];
					}
					unset($interes[$key]);
				}
			}		
			foreach($interes as $k=>$v)
			{
				$arr_interes = Array("UF_REQUEST_S"=>$request_id,"UF_REQUEST_F"=>$v,"UF_INTERES_TIME"=>date("d.m.Y H:i:s"),"UF_ACTIVE"=>1,"UF_REQUEST_STATUS"=>179);
				$res = HlBlockElement::Add($Project->get_interes(),$Project->add_postfix_to_fields($arr_interes));
			}
			foreach($interes_update as $k=>$v)
			{
				$arr_interes = Array("UF_ACTIVE".$postfix=>1);
				$res = HlBlockElement::Update($Project->get_interes(),$v,$Project->add_postfix_to_fields($arr_interes));
			}
		}
   }
	if (copy($_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$project_map.$file, $_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$project_map."old/".date('Y-m-d_H-i')."_".$file)) {
		unlink($_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$project_map.$file);
	}
	//else echo "?????";
} else {
    echo('Не удалось открыть файл '.$file.'.');
}
/*if($Project->s_name=="invent")
{
	LocalRedirect("sync_requests.php?project=domofey");
}*/
if($Project->s_name=="invent")
{
	LocalRedirect("queue_agent.php");
}
?>