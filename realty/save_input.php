<?
define("NO_KEEP_STATISTIC", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require($_SERVER["DOCUMENT_ROOT"]."/libs/rights_class.php");
$Project = new Rights();
$data_res = $Project->get_requests_file();
$postfix  = $Project->get_postfix();
$arr = Array();
$res=false;
$time_s = date("d.m.Y H:i:s");
$agents_hblock = $Project->get_agents_hb_id();
$client_hblock = $Project->get_clients_hb_id();
if($_GET["REQUEST_ID"]=="")
{
	global $USER;
	
	$arr_q = HlBlockElement::GetList($agents_hblock,array(),array("UF_BITRIX_USER".$postfix=>$USER->GetID(),"!UF_AGENT_ID".$postfix=>"new_user_%"),array(),1);
	if($arr_s_client = $arr_q->Fetch()){
		$user_code = $arr_s_client["UF_AGENT_ID".$postfix];
	}
	else {
		$rsUser = CUser::GetByID($USER->GetID());
		$arUser = $rsUser->Fetch();	
		$arr_q = HlBlockElement::GetList($agents_hblock,array(),array(array( 
															"LOGIC" => "AND",
															"UF_AGENT_NAME".$postfix => "%".$arUser["NAME"]."%",
															"UF_AGENT_NAME".$postfix => "%".$arUser["LAST_NAME"]."%"
															),
													"UF_BITRIX_USER".$postfix=>NULL),
														array(),1);
		if(strlen($arUser["NAME"])>3&&strlen($arUser["LAST_NAME"])>3&&$arr_s_client = $arr_q->Fetch())
		{
			$res = HlBlockElement::Update($agents_hblock,$arr_s_client["ID"],Array("UF_BITRIX_USER".$postfix=>$USER->GetID()));
			$user_code = $arr_s_client["UF_AGENT_ID".$postfix];
		}
		else
		{
			$user_code = "new_user_".$USER->GetID();
			$arr_to_agents =Array("UF_BITRIX_USER"=>$USER->GetID(),"UF_AGENT_ID"=>$user_code,"UF_AGENT_NAME"=>$arUser["NAME"]." ".$arUser["LAST_NAME"]);
			$arr_to_view_agents = $Project->add_postfix_to_fields($arr_to_agents);
			$res = HlBlockElement::Add($agents_hblock,$arr_to_view_agents);
		}
	}
	$arr_to=Array("UF_AGENT"=>$user_code,"UF_INNER_STATUS"=>0,"UF_UPDATE_DATE"=>$time_s,"UF_ADD_DATE"=>$time_s,"UF_STATUS"=>($Project->s_name=="invent")?51:457);
	$arr_to_view = $Project->add_postfix_to_fields($arr_to);
	$res = HlBlockElement::Add($data_res["hblock"],$arr_to_view);
	$request_id = $res->getid();
}
else
{
	$request_id = $_GET["REQUEST_ID"];
}
if(isset($_GET["select"]))
{
	$fild_name = $_GET["name"];
	if($postfix!=""&&strpos($fild_name,$postfix)===FALSE)
		$fild_name = $fild_name.$postfix;
	if($_GET["value"]=="")
	{
		$res = HlBlockElement::Update($data_res["hblock"],$request_id,Array($fild_name=>"","UF_UPDATE_DATE".$postfix=>$time_s));
	}
	else
	{
		$rs = CUserFieldEnum::GetList(array(), array(
				"USER_FIELD_NAME" => $fild_name,"XML_ID"=>$_GET["value"]
			));
			
		if($ar = $rs->Fetch())
		{/*print_r(Array($fild_name=>$ar["ID"],"UF_UPDATE_DATE".$postfix=>$time_s));*/
			$res = HlBlockElement::Update($data_res["hblock"],$request_id,Array($fild_name=>$ar["ID"],"UF_UPDATE_DATE".$postfix=>$time_s));
		}	
	}
}
else
{
	if(strpos($_GET["name"],"_AGENTS_")!==false)
	{	
		$name = str_replace("_AGENTS","",$_GET["name"]).$postfix;
		$name_clean = str_replace($postfix,"",$name);
		if(!isset($_GET["UF_CONTRAGENT"])||$_GET["UF_CONTRAGENT"]=="")
		{
			$res_1 = HlBlockElement::Add($client_hblock,Array($name=>$_GET["value"],"UF_CLIENT_STATUS".$postfix=>0));
			
			$arr["UF_CONTRAGENT"]=$res_1->getid();
			$res_1 = HlBlockElement::Update($client_hblock,$arr["UF_CONTRAGENT"],Array("UF_ID_1C".$postfix=>"new_client_".$arr["UF_CONTRAGENT"]));
			$res = HlBlockElement::Update($data_res["hblock"],$request_id,Array("UF_CONTRAGENT".$postfix=>"new_client_".$arr["UF_CONTRAGENT"],"UF_UPDATE_DATE".$postfix=>$time_s));
		}
		else
		{
			$arr_q = HlBlockElement::GetList($client_hblock,array(),array("UF_ID_1C".$postfix=>$_GET["UF_CONTRAGENT"]),array(),1);
			if($arr_s_client = $arr_q->Fetch()){$status=$arr_s_client["UF_CLIENT_STATUS".$postfix];}
			if($status<2||$arr_s_client[$name]=="")
			{
				$res_1 = HlBlockElement::Update($client_hblock,$_GET["UF_CONTRAGENT"],Array($name=>$_GET["value"]));	
			}
			else if($name_clean=="UF_PHONE")
			{
				$res_1 = HlBlockElement::Add($client_hblock,Array($name=>$_GET["value"],"UF_CLIENT_STATUS".$postfix=>0));
				$arr["UF_CONTRAGENT"]=$res_1->getid();
				$res_1 = HlBlockElement::Update($client_hblock,$arr["UF_CONTRAGENT"],Array("UF_ID_1C".$postfix=>$arr["UF_CONTRAGENT"]));
				$res = HlBlockElement::Update($data_res["hblock"],$request_id,Array("UF_CONTRAGENT".$postfix=>$arr["UF_CONTRAGENT"],"UF_UPDATE_DATE".$postfix=>$time_s));
			}
			else if($name_clean=="UF_PHONE_1")
			{
				$phone_number = $_GET["value"];
			}
			else if($name_clean=="UF_PHONE_1_CODE")
			{
				$full_number = '8'+$phone_number.$_GET["value"];
				
				$res_1 = HlBlockElement::Add($client_hblock,Array($name=>$full_number,"UF_CLIENT_STATUS".$postfix=>0));
				$arr["UF_CONTRAGENT"]=$res_1->getid();
				$res_1 = HlBlockElement::Update($client_hblock,$arr["UF_CONTRAGENT"],Array("UF_ID_1C".$postfix=>$arr["UF_CONTRAGENT"]));
				$res = HlBlockElement::Update($data_res["hblock"],$request_id,Array("UF_CONTRAGENT".$postfix=>$arr["UF_CONTRAGENT"],"UF_UPDATE_DATE".$postfix=>$time_s));		
			}
		}
		if($name_clean=="UF_PHONE"||$name_clean=="UF_PHONE_1")
		{
			$filter = array(array("LOGIC"=>"OR",array("UF_PHONE".$postfix=>intval($_GET["value"])),array("UF_PHONE_1".$postfix=>intval($_GET["value"]))),"!UF_ID_1C".$postfix => array($_GET["UF_CONTRAGENT"],$arr["UF_CONTRAGENT"]),">UF_CLIENT_STATUS".$postfix=>1);
			$arr_q = HlBlockElement::GetList($client_hblock,array(),$filter,array(),1);
			while($arr_s = $arr_q->Fetch()){
				if($arr_s["UF_CLIENT_STATUS".$postfix]!=0)
				{
					$filter = array("UF_CONTRAGENT".$postfix => $arr_s["UF_ID_1C".$postfix],"!ID" => $request_id);
					$arr_q = HlBlockElement::GetList($data_res["hblock"],array(),$filter,array(),10);
					$text="";
					$c_o=1;
					while($arr_s_1 = $arr_q->Fetch()){	
						foreach ($arr_s_1 as $k=>$v)
						{
							$arr_s_1[str_replace($postfix,"",$k)]=$v;
						}
							$text.='<div class="bl_nw">';
						if($c_o==1)
							$text.='<div class="header_desc header_desc_1">Другие заявки этого контрагента:</div>';
						$text.='<div class="sayavka_item"><a href="/realty/view/?REQUEST_ID='.$arr_s_1["ID"].'">Заявка №'.((intval($arr_s_1["UF_ID".$postfix])==0)?("*".$arr_s_1["ID"]):$arr_s_1["UF_ID"]).'</a></div><div href="#" class="logo_upload"></div><div class="c_request_info"><b>Агент:</b>';
						
						$arr_q_1 = HlBlockElement::GetList($agents_hblock,array(),array("UF_AGENT_ID".$postfix=>$arr_s_1["UF_AGENT"]),array(),1);
						if($arr_s_client = $arr_q_1->Fetch())
						{
							$text.="<a href=\"/company/personal/user/".$arr_s_client["UF_BITRIX_USER".$postfix]."/\">".$arr_s_client["UF_AGENT_NAME".$postfix]."</a><br>";
						}
						$text.="<b>Статус:</b>";
							$rs = CUserFieldEnum::GetList(array(), array(
									"USER_FIELD_NAME".$postfix => "UF_STATUS","ID" =>$arr_s_1["UF_STATUS"]
									));
						if($st = $rs->GetNext())
						{
							$text.= $st["VALUE"];
						}
						if($arr_s_1["UF_REALTY_TYPE"]!=0)
						{
							$text.="<br><b>Тип недвижимости:</b>";
							$rr = HlBlockElement::GetList(4,array(),array("UF_REALTY_TYPE_ID"=>$arr_s_1["UF_REALTY_TYPE"]),array(),1);
							if($rr_i = $rr->Fetch()){
								$text.= $rr_i["UF_REALTY_TYPE_NAME"];
							}
						}		
						$text.="</div></div>";
						$c_o++;
					}		
				}
				foreach($arr_s as $h=>$hh)
				{
					$arr_s[str_replace($postfix,"",$h)]=(string)$hh;
				}
				$arr_s["text"]=$text;
				$arr["client"][]=$arr_s;
			}
		}
	}
	else
	{
		$val_end = $_GET["value"];
		if(strpos($_GET["name"],"PRICE")!==FALSE)$val_end=str_replace(' ', '',$val_end);
		$fild_name = $_GET["name"];
		$find_name = $_GET["name"];
		if($postfix!=""&&strpos($fild_name,$postfix)===FALSE)
		{
			$fild_name = $fild_name.$postfix;
		}
		else if($postfix!=""){
			$find_name = str_replace($postfix,"",$find_name);
		}
		$save_arr = Array($fild_name=>$val_end,"UF_UPDATE_DATE".$postfix=>$time_s);
		if($find_name=="UF_CONTRAGENT")
		{
			$request_a = HlBlockElement::GetList($client_hblock,array("UF_ID_1C".$postfix),array("ID"=>$_GET["value"]),Array(),1);
			$request_data_a = $request_a->Fetch();
			$save_arr["UF_CONTRAGENT"]=$request_data_a["UF_ID_1C".$postfix];
			$request = HlBlockElement::GetList($data_res["hblock"],array("UF_CONTRAGENT".$postfix),array("ID"=>$request_id),Array(),1);
			$request_data = $request->Fetch();
			if($request_data["UF_CONTRAGENT".$postfix]!="")
			{
				$request_a = HlBlockElement::GetList($client_hblock,array("ID"),array("UF_ID_1C".$postfix=>$request_data["UF_CONTRAGENT"],"<UF_CLIENT_STATUS".$postfix=>2),Array(),1);
				$request_data_a = $request_a->Fetch();
				if(!empty($request_data_a))
				{
					HlBlockElement::Remove($client_hblock,$request_data_a["ID"]);
				}
			}
		}
		if(isset($_GET["UF_CITY_ID"]))$save_arr["UF_CITY_ID"]=$_GET["UF_CITY_ID"];
		if(isset($_GET["UF_ADDR_STREET"]))$save_arr["UF_ADDR_STREET"]=$_GET["UF_ADDR_STREET"];
		if(isset($_GET["UF_ADDR_INDEX"])||$find_name=="UF_ADDR_HOUSE"||isset($_GET["UF_CITY_REGION"])||$find_name=="UF_CITY_REGION")
		{
			$save_arr["UF_ADDR_INDEX"]=$_GET["UF_ADDR_INDEX"];
			$request = HlBlockElement::GetList($data_res["hblock"],$Project->add_postfix_to_fields_1(array("UF_ADDR_INDEX","UF_REGION_ID","UF_CITY_ID","UF_ADDR_STREET","UF_CITY_REGION","UF_ADDR_HOUSE")),array("ID"=>$request_id),Array(),1);
			$request_data = $request->Fetch();
			if($postfix!="")
			{
				foreach($request_data as $k=>$v)
				{
					$request_data[str_replace($postfix,"",$k)]=$v;
				}
			}
			if($find_name=="UF_CITY_REGION")
			{
				$save_arr["UF_CITY_REGION"]=$val_end;
			}
			else
			{		
				if($save_arr["UF_ADDR_INDEX"]!=$request_data["UF_ADDR_INDEX"]&&$find_name!="UF_ADDR_HOUSE")
				{
					$request_region = HlBlockElement::GetList(11,array("UF_KLADR_CITY_REGION"),array("UF_KLADR_INDEX"=>$save_arr["UF_ADDR_INDEX"],"!UF_KLADR_CITY_REGION"=>""),Array(),1);
					if($request_region_data = $request_region->Fetch())
						$save_arr["UF_CITY_REGION"]=$request_region_data["UF_KLADR_CITY_REGION"];	
					else 
						$save_arr["UF_CITY_REGION"]="";
					$arr["UF_CITY_REGION"] = $save_arr["UF_CITY_REGION"];
				}
			}
			if($find_name=="UF_ADDR_HOUSE")
				$save_arr["UF_ADDR_INDEX"]=$request_data["UF_ADDR_INDEX"];
			//print_r($save_arr);
			$save_arr["UF_ADDR_BLOCK"]="Индекс=".$save_arr["UF_ADDR_INDEX"]."\nКодРегиона=39\nРегион=Калининградская обл\n".((isset($save_arr["UF_REGION_ID"])&&$save_arr["UF_REGION_ID"]!="")?("Район=".$save_arr["UF_REGION_ID"]."\n"):((!isset($save_arr["UF_REGION_ID"])&&$request_data["UF_REGION_ID"]!="")?("Район=".$request_data["UF_REGION_ID"]."\n"):"")).
			((isset($save_arr["UF_CITY_REGION"])&&$save_arr["UF_CITY_REGION"]!="")?("РайонГорода=".$save_arr["UF_CITY_REGION"]."\n"):((!isset($save_arr["UF_CITY_REGION"])&&$request_data["UF_CITY_REGION"]!="")?("РайонГорода=".$request_data["UF_CITY_REGION"]."\n"):"")).
			((isset($save_arr["UF_CITY_ID"])&&$save_arr["UF_CITY_ID"]!="")?("Город=".$save_arr["UF_CITY_ID"]."\n"):((!isset($save_arr["UF_CITY_ID"])&&$request_data["UF_CITY_ID"]!="")?("Город=".$request_data["UF_CITY_ID"]."\n"):"")).
			((isset($save_arr["UF_ADDR_STREET"])&&$save_arr["UF_ADDR_STREET"]!="")?("Улица=".$save_arr["UF_ADDR_STREET"]."\n"):((!isset($save_arr["UF_ADDR_STREET"])&&$request_data["UF_ADDR_STREET"]!="")?("Улица=".$request_data["UF_ADDR_STREET"]."\n"):"")).
			((isset($save_arr["UF_ADDR_HOUSE".$postfix])&&$save_arr["UF_ADDR_HOUSE".$postfix]!="")?("Дом=".$save_arr["UF_ADDR_HOUSE".$postfix]."\n"):((!isset($save_arr["UF_ADDR_HOUSE".$postfix])&&$request_data["UF_ADDR_HOUSE"]!="")?("Дом=".$request_data["UF_ADDR_HOUSE".$postfix]."\n"):"")).
			"Страна=РОССИЯ\nКодСтраны=643";
		}
		$res = HlBlockElement::Update($data_res["hblock"],$request_id,$Project->add_postfix_to_fields($save_arr));
		if((isset($_GET["UF_ADDR_INDEX"])&&$_GET["UF_ADDR_INDEX"]!=""||$find_name=="UF_ADDR_HOUSE"&&$_GET["value"]!="")&&
		(isset($save_arr["UF_ADDR_STREET"])||$request_data["UF_ADDR_STREET"]!="")&&(isset($save_arr["UF_ADDR_HOUSE"])||$request_data["UF_ADDR_HOUSE"]!="")&&(isset($save_arr["UF_CITY_ID"])||$request_data["UF_CITY_ID"]!=""))
		{
			$arr_q = HlBlockElement::GetList($data_res["hblock"],array(),$Project->add_postfix_to_fields(array("UF_ADDR_STREET"=>isset($save_arr["UF_ADDR_STREET"])?$save_arr["UF_ADDR_STREET"]:$request_data["UF_ADDR_STREET"],"UF_CITY_ID"=>isset($save_arr["UF_CITY_ID"])?$save_arr["UF_CITY_ID"]:$request_data["UF_CITY_ID"],"UF_ADDR_HOUSE"=>isset($save_arr["UF_ADDR_HOUSE"])?$save_arr["UF_ADDR_HOUSE"]:$request_data["UF_ADDR_HOUSE"],">=UF_INNER_STATUS"=>2,"!ID"=>$request_id)),array(),10);
			$text="";
			while($arr_s = $arr_q->Fetch()){
				$text.='<div class="bl_nw">';
				$text.='<div class="sayavka_item"><a href="/realty/view/?REQUEST_ID='.$arr_s["ID"].'">Заявка №'.((intval($arr_s["UF_ID"])==0)?("*".$arr_s["ID"]):$arr_s["UF_ID"]).'</a></div><div href="#" class="logo_upload"></div><div class="c_request_info"><b>Агент:</b>';
				
				$arr_q_1 = HlBlockElement::GetList($agents_hblock,array(),array("UF_AGENT_ID".$postfix=>$arr_s["UF_AGENT".$postfix]),array(),1);
				if($arr_s_client = $arr_q_1->Fetch())
				{
					$text.="<a href=\"/company/personal/user/".$arr_s_client["UF_BITRIX_USER".$postfix]."/\">".$arr_s_client["UF_AGENT_NAME".$postfix]."</a><br>";
				}
				$text.="<b>Статус:</b>";
					$rs = CUserFieldEnum::GetList(array(), array(
							"USER_FIELD_NAME" => "UF_STATUS".$postfix,"ID" =>$arr_s["UF_STATUS".$postfix]
							));
				if($st = $rs->GetNext())
				{
					$text.= $st["VALUE"];
				}
				if($arr_s["UF_REALTY_TYPE".$postfix]!=0)
				{
					$text.="<br><b>Тип недвижимости:</b>";
					$rr = HlBlockElement::GetList(4,array(),array("UF_REALTY_TYPE_ID"=>$arr_s["UF_REALTY_TYPE".$postfix]),array(),1);
					if($rr_i = $rr->Fetch()){
						$text.= $rr_i["UF_REALTY_TYPE_NAME"];
					}
				}	
				$text.="<br><b>Адрес:</b><br>".str_replace("\n","<br>",$arr_s["UF_ADDR_BLOCK".$postfix]);				
				$text.="</div></div>";	
			}
			if($text!="")$arr["client_addr"]=$text;
		}
	}
}
$arr["REQUEST_ID"]=($res!=false)?$res->getid():$request_id;
$arr["status"]=1;
echo json_encode($arr);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");?>