<?
	define("NO_KEEP_STATISTIC", true);
	define("NOT_CHECK_PERMISSIONS", true);
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
	require($_SERVER["DOCUMENT_ROOT"]."/libs/rights_class.php");
	$Project = new Rights();
	class Syn1c {
		private $Type = -1;
		private $Hl = -1;
		private $RequestId = -1;

		private $FieldsRequests = Array("uf_source","uf_operation_type","uf_status","uf_realty_type","uf_object_type","uf_agent","uf_addr_block","uf_addr_flat","uf_add_date","uf_update_date","uf_etage","uf_etage_count","uf_total_square","uf_living_square","uf_kitchen_square","uf_rooms","uf_price","uf_remont_status","uf_sanusel_type","uf_water","uf_heating","uf_reklama","uf_www","uf_comment","uf_comment_order","uf_garage_square","uf_cellar_square","uf_lot_square","uf_gas","uf_land_owner_type","uf_naruzhn_reklama","uf_house_type","uf_entrance","uf_construct_perm","uf_owner_rights","uf_goal","uf_goal_land","uf_entry","uf_electricity","uf_distance","uf_latitude","uf_longitude","uf_balcony","uf_phone","uf_internet","uf_furniture","uf_refrigerator","uf_washing_machine","uf_parking","uf_loggia","uf_comment_order");
		
		private $FieldsRequestsClose = array("UF_REQUESTS_ID","UF_AGENT","UF_DATE_CLOSE","UF_CLOSE_REASON","UF_COMMENT");
		
		private $FieldsRequestsStatus = array("UF_REQUESTS_ID","UF_AGENT","UF_DATE_CLOSE","UF_CLOSE_REASON","UF_COMMENT");
		
		private $GoalArr = Array();
		
		public $RealtyType = array();
		public $UserTypeEntity = array();
		
		private $Debug = true;
		
		private $DebugFilePath = "";
		
		function __construct($Args){
			//
			// 0 - Send Requests Xml from 1C
			//
			global $Project;
			$data_res = $Project->get_requests_file();
			$postfix  = $Project->get_postfix();	
			//if($postfix!="")die("temporarily unavailable");
			if(isset($Args["args"][0]) && in_array($Args["args"][0],array(0,1,2))){
				$this->Type = intval($Args["args"][0]);	
			}
			if(isset($Args["args"][1]) && intval($Args["args"][1])>1){
				$this->RequestId = intval($Args["args"][1]);	
			}
			
			switch($this->Type){
				case 0:$this->Hl = $data_res["hblock"];break;
				case 1:$this->Hl = $Project->status_close_hb();break;
				case 2:$this->Hl = $Project->status_change_hb();break;
			}
			//
			// debug
			//
			$map = $Project->get_map();
			if($map!="")$map=$map."/";
			$this->DebugFilePath = $_SERVER["DOCUMENT_ROOT"]."/upload/cron/syn1c/".$map;
			if(!file_exists($this->DebugFilePath) && $this->Debug){
				if(!mkdir($this->DebugFilePath, 0777)){
					die("Error create pathe: ".$this->DebugFilePath);
				}
			}
		}
		
		public function Run(){
			global $Project;
			$data_res = $Project->get_requests_file();
			if($this->Type == 0){
				$this->GetRealtyType();
				$this->GetGoalType();
				$this->GetUserTypeEntity("HLBLOCK_".$data_res["hblock"]);
				$this->SendRequests();
			} elseif($this->Type == 1) {
				$this->GetUserTypeEntity("HLBLOCK_".$Project->status_close_hb());
				$this->SendRequestsClose();
			} elseif($this->Type == 2) {
				$this->SendRequestsStatus();
			}	
		}
		
		private function SendRequestsStatus(){
			global $Project;
			$Sip = new SoapPortalInvent();
			if($Project->s_name=="domofey")
			{
				$Sip->SetUrl($Project->s_name);
				//die("!!!".$Project->s_name);
			}
			$Query = HlBlockElement::GetList(
				$this->Hl,
				array(),
				array(
					"ID" => $Project->status_change_hb()
					//">=UF_DATE_CREATE"=> date("d.m.Y", strtotime("-6 week"))." 00:00:00", 
					//"<=UF_DATE_CREATE"=> date("d.m.Y")." 23:59:59"
				),
				array(),
				10
			);
			while($Answer = $Query->Fetch()){
				foreach ($Answer as $k=>$v)
				{
					$Answer[str_replace($postfix,"",$k)]=$v;
				}
				$XmlObj = $this->NewXmlObj("<?xml version=\"1.0\" encoding=\"UTF-8\"?><REQUESTS_STATUS/>");
				$Item = $XmlObj->addChild("item");
				$Item->addChild("UF_ID",str_pad($Answer["UF_REQUEST_ID"],9,"0",STR_PAD_LEFT));
				$Item->addChild("UF_AGENT",$Answer["UF_AGENT"]);
				$Item->addChild("UF_DATE_CREATE",$Answer["UF_DATE_CREATE"]);
				if($Answer["UF_CATEGORY"] > 0){
					$QueryFieldEnum = CUserFieldEnum::GetList(
						array(),
						array(
							"USER_FIELD_NAME" => "UF_CATEGORY",
							"ID" => $Answer["UF_CATEGORY"]
						)
					)->Fetch();
					
					if(!empty($QueryFieldEnum)){
						$Item->addChild("UF_CATEGORY",$QueryFieldEnum["VALUE"]);
					}
				}
				
				$Item->addChild("UF_COMMENT",$Answer["UF_COMMENT"]);
				$SoapAnswer = null;
				try {
					$Sip->ChangeCategoryApplication(base64_encode($XmlObj->saveXML()));
					HlBlockElement::Remove($this->Hl,$Answer["ID"]);
					if($this->Debug){
						$this->Dbg($XmlObj->saveXML());
					}
				} catch (Exception $e) {
					$Item->addChild("answer_1c_Exception",$e);
					$this->SendMail("Answer soap error Change status ReQuEsT","".$XmlObj->saveXML()."");
					echo "Error soap. Send Info from email";
				}
				$this->DbgFile("status",$Answer["ID"],$XmlObj->saveXML());
			}
		}
		
		private function DbgFile($Type = "undenfined",$Id = "",$Data = ""){
			if($this->Debug){
				file_put_contents($this->DebugFilePath.$Type."_request_".$Id."_".date('Y-m-d_H-i').".xml",$Data);
			}
		}
		
		private function SendRequestsClose(){
			$Sip = new SoapPortalInvent();
			if($Project->s_name=="domofey")
			{
				$Sip->SetUrl($Project->s_name);
				//die("!!!".$Project->s_name);
			}
			$Query = HlBlockElement::GetList(
				$this->Hl,
				array(),
				array(
					">=UF_DATE_CLOSE"=> date("d.m.Y", strtotime("-1 week"))." 00:00:00", 
					"<=UF_DATE_CLOSE"=> date("d.m.Y")." 23:59:59"
				),
				array(),
				10
			);
			
			while($Answer = $Query->Fetch()){
				$XmlObj = $this->NewXmlObj("<?xml version=\"1.0\" encoding=\"UTF-8\"?><REQUESTS_CLOSE/>");
				$Item = $XmlObj->addChild("item");
				foreach($Answer as $key => $value){
					
					if(in_array($key,array("ID","UF_ID","UF_REQUEST_HL_ID"))){continue;}
					
					if($this->UserTypeEntity[$key] == "boolean") {
						$value = ($value == 1 ? "Да" : "Нет");
					} elseif($this->UserTypeEntity[$key] == "enumeration"){
						$QueryFieldEnum = CUserFieldEnum::GetList(
							array(),
							array(
								"USER_FIELD_NAME" => $key,
								"ID" => $value
							)
						)->Fetch();
						
						$value = str_replace(str_replace("UF_","",$key)."_","",$QueryFieldEnum["XML_ID"]);
						$value=$value > 0 ? str_pad($value,9,"0",STR_PAD_LEFT) : "";
					}

					if($key=="UF_REQUEST_ID"){
						$Item->addChild("UF_ID",str_pad($value,9,"0",STR_PAD_LEFT));
						continue;
					}
					if($value == 0&&!in_array($key,Array("UF_AGENT"))){
						$value = "";
					} else {
						$value = str_pad($value,9,"0",STR_PAD_LEFT);
					}
					
					if(in_array(strtolower($key),$this->FieldsRequestsClose)){
						$key = strtolower($key);
					}
					$Item->addChild($key,$value);
				}
				
				$SoapAnswer = null;
				try {
					$Sip->CloseApplication(base64_encode($XmlObj->saveXML()));
					HlBlockElement::Remove($this->Hl,$Answer["ID"]);
					if($this->Debug){
						$this->Dbg($XmlObj->saveXML());
					}
				} catch (Exception $e) {
					$Item->addChild("answer_1c_Exception",$e);
					$this->SendMail("Answer soap error Close ReQuEsT","".$XmlObj->saveXML()."");
				}
				$this->DbgFile("close",$Answer["ID"],$XmlObj->saveXML());
			}
		}
		
		private function GetUserTypeEntity($Arg0 = ""){
			global $Project;
			$postfix  = $Project->get_postfix();
			$Query = CUserTypeEntity::GetList( array(), array("ENTITY_ID"=>$Arg0));
			while($Answer = $Query->Fetch()){
				if($postfix!="")
					$this->UserTypeEntity[str_replace($postfix,"",$Answer["FIELD_NAME"])]=$Answer["USER_TYPE_ID"];
				else
					$this->UserTypeEntity[$Answer["FIELD_NAME"]]=$Answer["USER_TYPE_ID"];
			}
		}
		
		private function GetRealtyType(){
			$Query = HlBlockElement::GetList(4,array());
			while($Answer = $Query->Fetch()){
				$this->RealtyType[$Answer["UF_REALTY_TYPE_ID"]] = $Answer["UF_REALTY_TYPE_NAME"];
			}
		}
		
		private function GetGoalType(){
			$Query = HlBlockElement::GetList(12,array());
			while($Answer = $Query->Fetch()){
				$this->GoalArr[$Answer["ID"]] = $Answer["UF_GOAL_PARENT"];
			}
		}

		private function SendRequests(){
			global $Project;
			$postfix  = $Project->get_postfix();
			$data_res = $Project->get_requests_file();
			$XmlObj = null;		
			if($this->RequestId>0)
			{
				$Query = HlBlockElement::GetList(
					$this->Hl,
					array(),
					array(
						"ID" => $this->RequestId		
					),
					array(),
					1
				);
			}
			else
			{	
				$Sip = new SoapPortalInvent();
				if($Project->s_name=="domofey")
				{
					$Sip->SetUrl($Project->s_name);
					//die("!!!".$Project->s_name);
				}
				if($Project->s_name=="domofey")
				{
						$Query = HlBlockElement::GetList(
						$this->Hl,
						array(),
						array(
							/*array(
								"LOGIC" => "OR",
								array("UF_ID" => false),
								array("=UF_ID" => 0)
							)*/
							"=UF_INNER_STATUS".$postfix => 2,
							"ID"=>521
							/*
							array(
								"LOGIC" => "OR", 
								array(),
								array("UF_ID" => false)
							)*/
						),
						array(),
						10
					);
				}
				else
				$Query = HlBlockElement::GetList(
					$this->Hl,
					array(),
					array(
						/*array(
							"LOGIC" => "OR",
							array("UF_ID" => false),
							array("=UF_ID" => 0)
						)*/
						"=UF_INNER_STATUS".$postfix => 2
						/*
						array(
							"LOGIC" => "OR", 
							array(),
							array("UF_ID" => false)
						)*/
					),
					array(),
					10
				);
			}
			while($Answer = $Query->Fetch()){
				$FlagNewClient = false;
				$ValNewClient = "";
				$XmlObj = $this->NewXmlObj("<?xml version=\"1.0\" encoding=\"UTF-8\"?><REQUESTS/>");
				
				$Item = $XmlObj->addChild("item");
				$ItemFile = false;
				if($postfix!="")
				{
					foreach ($Answer as $k=>$v)
					{
						unset($Answer[$k]);
						if($k=="UF_LAND_O_TYPE".$postfix)
							$k="UF_LAND_OWNER_TYPE";
						if($k=="UF_N_REKLAMA".$postfix)
							$k="UF_NARUZHN_REKLAMA";
						$Answer[str_replace($postfix,"",$k)]=$v;
					}
				}
				$request_id = $Answer["ID"];
				//echo "<xmp>";print_r($Answer);echo "</xmp>";
				foreach($Answer as $key => $value){
					
					if(in_array(
						$key,
						array(
							"UF_ADDR_INDEX",
							"UF_CITY_REGION",
							"UF_REGION_ID",
							"UF_INNER_STATUS",
							"UF_REQUESTS_ID",
							"UF_CITY_ID",
							"UF_ADDR_STREET"
						)
					)){
						continue;
					}
					
					if($this->UserTypeEntity[$key] == "boolean") {
						$value = ($value == 1 ? "Да" : "Нет");
						if(in_array($key,Array("UF_ENTRANCE","UF_CONSTRUCT_PERM"))&&$value=="Нет") 
							$value="";
					} else if($this->UserTypeEntity[$key] == "enumeration"&&$value!=""){
						
						$QueryFieldEnum = CUserFieldEnum::GetList(
							array(),
							array(
								"USER_FIELD_NAME" => $key,
								"ID" => $value
							)
						)->Fetch();
						

						if(
							!empty($QueryFieldEnum)
							&&
							in_array(
								$key,
								array(
									"UF_STATUS",
									"UF_CONSTRUCT_PERM",
									"UF_ENTRANCE",
									"UF_OPERATION_TYPE",
									"UF_LAND_OWNER_TYPE",
									"UF_ENTRY",
									"UF_CATEGORY"
								)
							)
						){
							$value = $QueryFieldEnum["VALUE"];
							if($key=="UF_CATEGORY")
								$value = "Категория ".$value;
						} else {
							$value = str_replace(str_replace("UF_","",$key)."_","",$QueryFieldEnum["XML_ID"]);
							if($key == "UF_SOURCE"){
								$value = "00-".str_pad($value,8,"0",STR_PAD_LEFT);
							} else if(in_array($key,array("UF_GAS","UF_MATERIAL","UF_LOGGIA","UF_HOUSE_TYPE"))){
								$value = $value > 0 ? intval($value) : "";
							} else {
								$value=$value > 0 ? str_pad($value,9,"0",STR_PAD_LEFT) : "";
							}
						}
					} else if($key == "UF_OBJ_TYPE"){
						$value = str_pad($value,9,"0",STR_PAD_LEFT);
					} 
					
					if($key == "UF_REALTY_TYPE"){
						$value = isset($this->RealtyType[$value]) ? $this->RealtyType[$value] : "";
					} /*else if($key == "UF_PHOTO_PREVIEW"){
						$value_str = "http://portal.invent-realty.ru";
						if($value > 0){
							$value = $value_str.CFile::GetPath($value);
						}
					}*/
					else if(in_array($key,array("UF_PHOTO_PREVIEW","UF_PHOTOS","UF_DOCS","UF_PLAN_PHOTOS"))){
						if($key=="UF_PHOTO_PREVIEW")
						{
							if (intval($value)==0)continue;
							$value = Array(0=>$value);
						}
						foreach($value as $keyFile => $valueFile){
							if($ItemFile == false)
								$ItemFile = $Item->addChild("uf_photos");
							$item_img = $ItemFile->addChild("item");
							$file_info = CFile::GetByID($valueFile);
							$arFile = $file_info->Fetch();
							$desc_line = $arFile["DESCRIPTION"];
							$desc_arr = json_decode($desc_line,1);
							$desc_arr["URL"]="http://portal.invent-realty.ru".CFile::GetPath($valueFile);
							if($key=="UF_PHOTO_PREVIEW")
							{
								$desc_arr["ORDER"]=1;
								$desc_arr["PREVIEW"]=1;
								$desc_arr["PHOTO"]=0;
							}
							else if($desc_arr["ORDER"]<2)
							{
								$desc_arr["ORDER"]=2;
							}
							foreach($desc_arr as $k=>$v)
							{
								$item_img->addChild(strtolower($k),$v);
							}
						}
						continue;
					} else if($key == "UF_CONTRAGENT"){
						if(strpos($value,"new_client_")!==FALSE){
							$FlagNewClient = true;
							$ValNewClient = $value;
							$ItemUser = $Item->addChild(strtolower($key));
							$ItemUserPerson = $ItemUser->addChild("new");
							$User = $this->GetClient($value);
							unset($User["ID"]);
							unset($User["UF_CLIENT_STATUS"]);
							foreach($User as $keyUser=>$valueUser){
								if(strpos($keyUser,"~")!==FALSE || $keyUser=="UF_ID_1C"){continue;}
								$ItemUserPerson->addChild($keyUser,$valueUser);
							}
							continue;
						}
					}  else if($key == "UF_GOAL"){
						$first_s=intval(substr(strval($value),0,1));
						$last_s=intval(substr(strval($value),1));
						$value = ($value == 0 ? "" : str_pad($last_s,9,"0",STR_PAD_LEFT));
						if($first_s==1)
						{
							$Item->addChild("uf_goal","");
							$key = "UF_GOAL_LAND";
						}
						else
						{
							$Item->addChild("uf_goal_land","");
						}
						
					} else if($key == "UF_ID" && $value > 0){
						$value = str_pad($value,9,"0",STR_PAD_LEFT);
					}
					
					if($key == "UF_ELECTICITY"){
						$key = "UF_ELECTRICITY";
					} else if($key == "UF_OBJ_TYPE"){
						$key = "UF_OBJECT_TYPE";
					}
					
					if(in_array(strtolower($key),$this->FieldsRequests)){
						$key = strtolower($key);
					}
					/*if($key=="HOUSE_TYPE"){
						$value = $value > 0 ? intval($value) : "";
					}*/
					$Item->addChild($key,$value);
				}
				$interes_update=Array();
				$interes="";
				$interes_to = HlBlockElement::GetList($Project->get_interes(),array(),array("UF_REQUEST_S".$postfix=>$request_id,"UF_ACTIVE".$postfix=>1),array(),100);
				while($interes_to_arr = $interes_to->Fetch())
				{
					if(!in_array($interes_to_arr["UF_REQUEST_F".$postfix],$interes_update));
					{
						$interes_update[] = $interes_to_arr["UF_REQUEST_F".$postfix];				
					}
				}
				$get_request_id_arr = Array();
				$get_request = HlBlockElement::GetList($data_res["hblock"],array("UF_ID".$postfix),array("ID"=>$interes_update),array(),100);
				while($get_request_to = $get_request->Fetch())
				{
					if(intval($get_request_to["UF_ID".$postfix])>0)
						$get_request_id_arr[] = str_pad($get_request_to["UF_ID".$postfix],9,"0",STR_PAD_LEFT);
				}
				$interes = implode(",",$get_request_id_arr);
				$Item->addChild("uf_interes",$interes);
				if($ItemFile == false)
					$ItemFile = $Item->addChild("uf_photos");		
				$SoapAnswer = null;
				try {
					if($this->RequestId>0)
					{
						$this->Dbg($XmlObj->saveXML());
					}
					else
					{
						$XmlObj->asXML();
						//$SoapAnswer = $Sip->NewApplication(base64_encode($XmlObj->saveXML()));

						$Item->addChild("answer_1c",$SoapAnswer->return);
						
						$Data = explode(";",$SoapAnswer->return);
						
						$HlFields = array(
							"UF_ID" => intval($Data[0]),
							"UF_INNER_STATUS" => 3
						);
						if($FlagNewClient){
							if(isset($Data[1]) && strlen($Data[1]) > 0){
								$HlFields["UF_CONTRAGENT"] = $Data[1];
								
								$QueryClient = HlBlockElement::GetList(
									$Project->get_clients_hb_id(),
									array("ID"),
									array("UF_ID_1C".$postfix => $ValNewClient),
									array(),
									1
								);
								/*if($AnswerClient = $QueryClient->Fetch()){
									HlBlockElement::Update(
										$Project->get_clients_hb_id(),
										$AnswerClient["ID"],
										array(
											"UF_ID_1C".$postfix => $HlFields["UF_CONTRAGENT"]
										)
									);
								}*/
							}
						}
						
						//HlBlockElement::Update($this->Hl,$Answer["ID"],$Project->add_postfix_to_fields($HlFields));
					}
				} catch (Exception $e) {
					$Item->addChild("answer_1c_Exception",$e);
					if(!($this->RequestId>0))
					{
						$this->SendMail("Answer soap error New ReQuEsT","".$XmlObj->saveXML()."");
					}
				}
				$this->DbgFile("new",$Answer["ID"],$XmlObj->saveXML());
			}
		}
		
		private function GetClient($Arg0 = ""){
			$Arg0 = trim($Arg0);
			global $Project;
			$postfix  = $Project->get_postfix();
			$ret= Array();
			if($Arg0 != "" )
				$ret=HlBlockElement::GetList($Project->get_clients_hb_id(),array(),Array("UF_ID_1C".$postfix=>$Arg0))->Fetch();
			if($postfix!="")
			{
				foreach($ret as $k=>$v)
				{
					unset($ret[$k]);
					$ret[str_replace($postfix,"",$k)]=$v;
				}
			}
			return $ret;
		}
		
		private function SendMail($Subject = "",$Message = ""){
			$To  = "dmitry.malyshev@mail.ru"; // ,wez@example.com, claud_2008@mail.ru
			
			// Для отправки HTML-письма должен быть установлен заголовок Content-type
			$Headers  = 'MIME-Version: 1.0' . "\r\n";
			$Headers .= 'Content-type: text/xml; charset=utf-8' . "\r\n";

			// Дополнительные заголовки
			//$Headers .= 'To: Mary <mary@example.com>, Kelly <kelly@example.com>' . "\r\n";
			//$Headers .= 'From: Portal Invent Realty <PortalInventRealty@example.com>' . "\r\n";
			//$Headers .= 'Cc: PortalInventRealty@example.com' . "\r\n";
			//$Headers .= 'Bcc: PortalInventRealty@example.com' . "\r\n";

			// Отправляем
			mail($To, $Subject, $Message, $Headers);
			//echo "@@@@@@@@@@@@@";
		}

		public function Dbg($Arg0,$Arg1=""){
			if($Arg1 != ""){header($Arg1);}
			if(preg_match("/xml/i",$Arg1)){
				print_r($Arg0);
			} else {
				echo "<pre>";
				print_r($Arg0);
				echo "</pre>"; 
			}
		}
		private function NewXmlObj($Arg0 = ""){
			return new SimpleXMLElement($Arg0);
		}
	}
	$Syn1c = new Syn1c($_GET);
	$Syn1c->Run();
	$Syn1c = null;
	if($Project->s_name=="invent")
	{
		//LocalRedirect("syn_1c.php?project=domofey");
	}
?>
