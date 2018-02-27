<?
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
$arr = Array();
$res=false;
$time_s = date("d.m.Y H:i:s");
if($_GET["REQUEST_ID"]=="")
{
	global $USER;
	
	$arr_q = HlBlockElement::GetList(5,array(),array("UF_BITRIX_USER"=>$USER->GetID()),array(),1);
	if($arr_s_client = $arr_q->Fetch()){
		$user_code = $arr_s_client["UF_AGENT_ID"];
	}
	else {
		$rsUser = CUser::GetByID($USER->GetID());
		$arUser = $rsUser->Fetch();	
		$arr_q = HlBlockElement::GetList(5,array(),array(array( 
															"LOGIC" => "AND",
															"UF_AGENT_NAME" => "%".$arUser["NAME"]."%",
															"UF_AGENT_NAME" => "%".$arUser["LAST_NAME"]."%"
															),
													"UF_BITRIX_USER"=>NULL),
														array(),1);
		if($arUser["NAME"]!=""&&$arUser["LAST_NAME"]!=""&&$arr_s_client = $arr_q->Fetch())
		{
			$res = HlBlockElement::Update(5,$arr_s_client["ID"],Array("UF_BITRIX_USER"=>$USER->GetID()));
			$user_code = $arr_s_client["UF_AGENT_ID"];
		}
		else
		{
			$user_code = "new_user_".$USER->GetID();
			$res = HlBlockElement::Add(5,Array("UF_BITRIX_USER"=>$USER->GetID(),"UF_AGENT_ID"=>$user_code,"UF_AGENT_NAME"=>$arUser["NAME"]." ".$arUser["LAST_NAME"]));
		}
	}
	$res = HlBlockElement::Add(2,Array("UF_AGENT"=>$user_code,"UF_INNER_STATUS"=>0,"UF_UPDATE_DATE"=>$time_s,"UF_ADD_DATE"=>$time_s,"UF_STATUS"=>51));
	$request_id = $res->getid();
}
else
	$request_id = $_GET["REQUEST_ID"];
if(isset($_GET["select"]))
{
	if($_GET["value"]=="")
	{
		$res = HlBlockElement::Update(2,$request_id,Array($_GET["name"]=>"","UF_UPDATE_DATE"=>$time_s));
	}
	else
	{
		$rs = CUserFieldEnum::GetList(array(), array(
				"USER_FIELD_NAME" => $_GET["name"],"XML_ID"=>$_GET["value"]
			));
		if($ar = $rs->Fetch())
		{
			$res = HlBlockElement::Update(2,$request_id,Array($_GET["name"]=>$ar["ID"],"UF_UPDATE_DATE"=>$time_s));
		}	
	}
}
else
{
	if(strpos($_GET["name"],"_AGENTS_")!==false)
	{	
		
		$name = str_replace("_AGENTS","",$_GET["name"]);
				
		if(!isset($_GET["UF_CONTRAGENT"])||$_GET["UF_CONTRAGENT"]=="")
		{
			$res_1 = HlBlockElement::Add(10,Array($name=>$_GET["value"],"UF_CLIENT_STATUS"=>0));
			
			$arr["UF_CONTRAGENT"]=$res_1->getid();
			$res_1 = HlBlockElement::Update(10,$arr["UF_CONTRAGENT"],Array("UF_ID_1C"=>"new_client_".$arr["UF_CONTRAGENT"]));
			$res = HlBlockElement::Update(2,$request_id,Array("UF_CONTRAGENT"=>"new_client_".$arr["UF_CONTRAGENT"],"UF_UPDATE_DATE"=>$time_s));
		}
		else
		{
			$arr_q = HlBlockElement::GetList(10,array(),array("UF_ID_1C"=>$_GET["UF_CONTRAGENT"]),array(),1);
			if($arr_s_client = $arr_q->Fetch()){$status=$arr_s_client["UF_CLIENT_STATUS"];}
			if($status<2||$arr_s_client[$name]=="")
			{
				$res_1 = HlBlockElement::Update(10,$_GET["UF_CONTRAGENT"],Array($name=>$_GET["value"]));	
			}
			else if($name=="UF_PHONE")
			{
				$res_1 = HlBlockElement::Add(10,Array($name=>$_GET["value"],"UF_CLIENT_STATUS"=>0));
				$arr["UF_CONTRAGENT"]=$res_1->getid();
				$res_1 = HlBlockElement::Update(10,$arr["UF_CONTRAGENT"],Array("UF_ID_1C"=>$arr["UF_CONTRAGENT"]));
				$res = HlBlockElement::Update(2,$request_id,Array("UF_CONTRAGENT"=>$arr["UF_CONTRAGENT"],"UF_UPDATE_DATE"=>$time_s));
			}
			else if($name=="UF_PHONE_1")
			{
				$phone_number = $_GET["value"];
			}
			else if($name=="UF_PHONE_1_CODE")
			{
				$full_number = '8'+$phone_number.$_GET["value"];
				
				$res_1 = HlBlockElement::Add(10,Array($name=>$full_number,"UF_CLIENT_STATUS"=>0));
				$arr["UF_CONTRAGENT"]=$res_1->getid();
				$res_1 = HlBlockElement::Update(10,$arr["UF_CONTRAGENT"],Array("UF_ID_1C"=>$arr["UF_CONTRAGENT"]));
				$res = HlBlockElement::Update(2,$request_id,Array("UF_CONTRAGENT"=>$arr["UF_CONTRAGENT"],"UF_UPDATE_DATE"=>$time_s));
			
			}
		}
		if($name=="UF_PHONE"||$name=="UF_PHONE_1")
		{
			$filter = array(array("LOGIC"=>"OR",array("UF_PHONE"=>intval($_GET["value"])),array("UF_PHONE_1"=>intval($_GET["value"]))),"!UF_ID_1C" => array($_GET["UF_CONTRAGENT"],$arr["UF_CONTRAGENT"]),">UF_CLIENT_STATUS"=>1);
			$arr_q = HlBlockElement::GetList(10,array(),$filter,array(),1);
			while($arr_s = $arr_q->Fetch()){
				if($arr_s["UF_CLIENT_STATUS"]!=0)
				{
					$filter = array("UF_CONTRAGENT" => $arr_s["UF_ID_1C"],"!ID" => $request_id);
					$arr_q = HlBlockElement::GetList(2,array(),$filter,array(),10);
					$text="";
					$c_o=1;
					while($arr_s_1 = $arr_q->Fetch()){	
						$text.='<div class="bl_nw">';
						if($c_o==1)
							$text.='<div class="header_desc header_desc_1">Другие заявки этого контрагента:</div>';
						$text.='<div class="sayavka_item"><a href="/realty/view/?REQUEST_ID='.$arr_s_1["ID"].'">Заявка №'.((intval($arr_s_1["UF_ID"])==0)?("*".$arr_s_1["ID"]):$arr_s_1["UF_ID"]).'</a></div><div href="#" class="logo_upload"></div><div class="c_request_info"><b>Агент:</b>';
						
						$arr_q_1 = HlBlockElement::GetList(5,array(),array("UF_AGENT_ID"=>$arr_s_1["UF_AGENT"]),array(),1);
						if($arr_s_client = $arr_q_1->Fetch())
						{
							$text.="<a href=\"/company/personal/user/".$arr_s_client["UF_BITRIX_USER"]."/\">".$arr_s_client["UF_AGENT_NAME"]."</a><br>";
						}
						$text.="<b>Статус:</b>";
							$rs = CUserFieldEnum::GetList(array(), array(
									"USER_FIELD_NAME" => "UF_STATUS","ID" =>$arr_s_1["UF_STATUS"]
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
					$arr_s[$h]=(string)$hh;
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
		$save_arr = Array($_GET["name"]=>$val_end,"UF_UPDATE_DATE"=>$time_s);
		if($_GET["name"]=="UF_CONTRAGENT")
		{
			$request_a = HlBlockElement::GetList(10,array("UF_ID_1C"),array("ID"=>$_GET["value"]),Array(),1);
			$request_data_a = $request_a->Fetch();
			$save_arr["UF_CONTRAGENT"]=$request_data_a["UF_ID_1C"];
			$request = HlBlockElement::GetList(2,array("UF_CONTRAGENT"),array("ID"=>$request_id),Array(),1);
			$request_data = $request->Fetch();
			if($request_data["UF_CONTRAGENT"]!="")
			{
				$request_a = HlBlockElement::GetList(10,array("ID"),array("UF_ID_1C"=>$request_data["UF_CONTRAGENT"],"<UF_CLIENT_STATUS"=>2),Array(),1);
				$request_data_a = $request_a->Fetch();
				if(!empty($request_data_a))
				{
					HlBlockElement::Remove(10,$request_data_a["ID"]);
				}
			}
		}
		if(isset($_GET["UF_CITY_ID"]))$save_arr["UF_CITY_ID"]=$_GET["UF_CITY_ID"];
		if(isset($_GET["UF_ADDR_STREET"]))$save_arr["UF_ADDR_STREET"]=$_GET["UF_ADDR_STREET"];
		if(isset($_GET["UF_ADDR_INDEX"])||$_GET["name"]=="UF_ADDR_HOUSE")
		{
			$save_arr["UF_ADDR_INDEX"]=$_GET["UF_ADDR_INDEX"];
			$request = HlBlockElement::GetList(2,array("UF_ADDR_INDEX","UF_REGION_ID","UF_CITY_ID","UF_ADDR_STREET","UF_CITY_REGION","UF_ADDR_HOUSE"),array("ID"=>$request_id),Array(),1);
			$request_data = $request->Fetch();
			if($save_arr["UF_ADDR_INDEX"]!=$request_data["UF_ADDR_INDEX"]&&$_GET["name"]!="UF_ADDR_HOUSE")
			{
				$request_region = HlBlockElement::GetList(11,array("UF_KLADR_CITY_REGION"),array("UF_KLADR_INDEX"=>$save_arr["UF_ADDR_INDEX"],"!UF_KLADR_CITY_REGION"=>""),Array(),1);
				if($request_region_data = $request_region->Fetch())
					$save_arr["UF_CITY_REGION"]=$request_region_data["UF_KLADR_CITY_REGION"];	
				else 
					$save_arr["UF_CITY_REGION"]="";
				$arr["UF_CITY_REGION"] = $save_arr["UF_CITY_REGION"];
			}
			if($_GET["name"]=="UF_ADDR_HOUSE")
				$save_arr["UF_ADDR_INDEX"]=$request_data["UF_ADDR_INDEX"];
			$save_arr["UF_ADDR_BLOCK"]="Индекс=".$save_arr["UF_ADDR_INDEX"]."\nКодРегиона=39\nРегион=Калининградская обл\n".((isset($save_arr["UF_REGION_ID"])&&$save_arr["UF_REGION_ID"]!="")?("Район=".$save_arr["UF_REGION_ID"]."\n"):((!isset($save_arr["UF_REGION_ID"])&&$request_data["UF_REGION_ID"]!="")?("Район=".$request_data["UF_REGION_ID"]."\n"):"")).
			((isset($save_arr["UF_CITY_REGION"])&&$save_arr["UF_CITY_REGION"]!="")?("РайонГорода=".$save_arr["UF_CITY_REGION"]."\n"):((!isset($save_arr["UF_CITY_REGION"])&&$request_data["UF_CITY_REGION"]!="")?("РайонГорода=".$request_data["UF_CITY_REGION"]."\n"):"")).
			((isset($save_arr["UF_CITY_ID"])&&$save_arr["UF_CITY_ID"]!="")?("Город=".$save_arr["UF_CITY_ID"]."\n"):((!isset($save_arr["UF_CITY_ID"])&&$request_data["UF_CITY_ID"]!="")?("Город=".$request_data["UF_CITY_ID"]."\n"):"")).
((isset($save_arr["UF_ADDR_STREET"])&&$save_arr["UF_ADDR_STREET"]!="")?("Улица=".$save_arr["UF_ADDR_STREET"]."\n"):((!isset($save_arr["UF_ADDR_STREET"])&&$request_data["UF_ADDR_STREET"]!="")?("Улица=".$request_data["UF_ADDR_STREET"]."\n"):"")).
((isset($save_arr["UF_ADDR_HOUSE"])&&$save_arr["UF_ADDR_HOUSE"]!="")?("Дом=".$save_arr["UF_ADDR_HOUSE"]."\n"):((!isset($save_arr["UF_ADDR_HOUSE"])&&$request_data["UF_ADDR_HOUSE"]!="")?("Дом=".$request_data["UF_ADDR_HOUSE"]."\n"):"")).
"Страна=РОССИЯ\nКодСтраны=643";
		}
		
		$res = HlBlockElement::Update(2,$request_id,$save_arr);
		if((isset($_GET["UF_ADDR_INDEX"])&&$_GET["UF_ADDR_INDEX"]!=""||$_GET["name"]=="UF_ADDR_HOUSE"&&$_GET["value"]!="")&&
		(isset($save_arr["UF_ADDR_STREET"])||$request_data["UF_ADDR_STREET"]!="")&&(isset($save_arr["UF_ADDR_HOUSE"])||$request_data["UF_ADDR_HOUSE"]!="")&&(isset($save_arr["UF_CITY_ID"])||$request_data["UF_CITY_ID"]!=""))
		{
			$arr_q = HlBlockElement::GetList(2,array(),array("UF_ADDR_STREET"=>isset($save_arr["UF_ADDR_STREET"])?$save_arr["UF_ADDR_STREET"]:$request_data["UF_ADDR_STREET"],"UF_CITY_ID"=>isset($save_arr["UF_CITY_ID"])?$save_arr["UF_CITY_ID"]:$request_data["UF_CITY_ID"],"UF_ADDR_HOUSE"=>isset($save_arr["UF_ADDR_HOUSE"])?$save_arr["UF_ADDR_HOUSE"]:$request_data["UF_ADDR_HOUSE"],">=UF_INNER_STATUS"=>2,"!ID"=>$request_id),array(),10);
			$text="";
			while($arr_s = $arr_q->Fetch()){
				$text.='<div class="bl_nw">';
				$text.='<div class="sayavka_item"><a href="/realty/view/?REQUEST_ID='.$arr_s["ID"].'">Заявка №'.((intval($arr_s["UF_ID"])==0)?("*".$arr_s["ID"]):$arr_s["UF_ID"]).'</a></div><div href="#" class="logo_upload"></div><div class="c_request_info"><b>Агент:</b>';
				
				$arr_q_1 = HlBlockElement::GetList(5,array(),array("UF_AGENT_ID"=>$arr_s["UF_AGENT"]),array(),1);
				if($arr_s_client = $arr_q_1->Fetch())
				{
					$text.="<a href=\"/company/personal/user/".$arr_s_client["UF_BITRIX_USER"]."/\">".$arr_s_client["UF_AGENT_NAME"]."</a><br>";
				}
				$text.="<b>Статус:</b>";
					$rs = CUserFieldEnum::GetList(array(), array(
							"USER_FIELD_NAME" => "UF_STATUS","ID" =>$arr_s["UF_STATUS"]
							));
				if($st = $rs->GetNext())
				{
					$text.= $st["VALUE"];
				}
				if($arr_s["UF_REALTY_TYPE"]!=0)
				{
					$text.="<br><b>Тип недвижимости:</b>";
					$rr = HlBlockElement::GetList(4,array(),array("UF_REALTY_TYPE_ID"=>$arr_s["UF_REALTY_TYPE"]),array(),1);
					if($rr_i = $rr->Fetch()){
						$text.= $rr_i["UF_REALTY_TYPE_NAME"];
					}
				}	
				$text.="<br><b>Адрес:</b><br>".str_replace("\n","<br>",$arr_s["UF_ADDR_BLOCK"]);				
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