<? 
	define("NO_KEEP_STATISTIC", true);
	define("NOT_CHECK_PERMISSIONS", true);
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
	set_time_limit(60*10);
	ini_set('memory_limit', '1024M');
	require($_SERVER["DOCUMENT_ROOT"]."/libs/rights_class.php");
	$file = $_SERVER["DOCUMENT_ROOT"]."/upload/1c/B24_Catalogs/dezhurnye_agenty.xml";
	$arr_offices = Array(
		"Московский №2"=>"mosk_2",
		"Колоскова"=>"kolos",
		"Багратиона"=>"bagrat",
		"Соммера"=>"som",
		"Московский"=>"mosk"
	);
	$time_s = date("d.m.Y H:i:s");
	$time_f = date("d.m.Y H:i:s",time()-60*15);
	$Project = new Rights();
	$data_res = $Project->get_requests_file();
	$postfix  = $Project->get_postfix();
	$arr_ids=Array();
	$get_request = HlBlockElement::GetList($Project->get_call_hb_id(),array("ID","UF_REQ_ID".$postfix,"UF_AG_CODE".$postfix),array("UF_ACT".$postfix=>1,"<UF_CALL_TIME".$postfix=>$time_f),array(),100);
	while($get_request_to = $get_request->Fetch())
	{	
		$arr_ids[$get_request_to["UF_REQ_ID".$postfix]]=Array("ID"=>$get_request_to["ID"],"LIST"=>$get_request_to["UF_AG_CODE".$postfix]);	
	}
	$get_request = HlBlockElement::GetList($data_res["hblock"],array("ID"),array("ID"=>array_keys($arr_ids),"UF_COORDINATOR".$postfix=>1,"!=UF_INNER_STATUS"=>3),array(),100);
	$user_id_default =/*685*/752;
	$first=true;
	$deg_agents = Array();
	while($get_request_to = $get_request->Fetch())
	{
		if($first)
		{
			$first=false;
			if (file_exists($file)) {
				$xml = simplexml_load_file($file);
				foreach($xml->item as $k=>$v)
				{
					if(!in_array(strval($v->PODRAZDELENIE),array("Отдел коттеджей","Отдел аренды","Департамент коммерческой недвижимости","Новостройки")))
						$deg_agents[]=Array("a"=>strval($v->AGENT),"o"=>strval($v->PODRAZDELENIE));
				}
			}
		}
		$user_code = ""; 
		$o_code="";
		$found=false;
		foreach ($deg_agents as $k=>$v)
		{
			if(!in_array($arr_offices[$v["o"]],$arr_ids[$get_request_to["ID"]]["LIST"]))
			{
				$o_code=$arr_offices[$v["o"]];
				$user_code = $v["a"];
				$found=true;
				break;
			}
		}
		if(!$found)
		{
			$ar_code_agent_update = Array($arr_offices[$deg_agents[0]["o"]]);
			$user_code = $deg_agents[0]["a"];
			$o_code=$arr_offices[$v["o"]];
		}
		else
		{
			$arr_ids[$get_request_to["ID"]]["LIST"][]=$o_code;
			$ar_code_agent_update = $arr_ids[$get_request_to["ID"]]["LIST"];
			
		}
		//print_r($ar_code_agent_update);die("!!!");
		if($user_code=="")
			die("error 1");
		$arr_user_id=array();
		$res = HlBlockElement::Update($Project->get_call_hb_id(),$arr_ids[$get_request_to["ID"]]["ID"],Array("UF_CALL_TIME".$postfix=>$time_s,"UF_AG_CODE".$postfix=>$ar_code_agent_update,"UF_AGENTCODE_C".$postfix=>$user_code));
		if(!isset($arr_user_id[$user_code]))
		{
			$request_ag = HlBlockElement::GetList($Project->get_agents_hb_id(),array("UF_BITRIX_USER".$postfix),array("UF_AGENT_ID".$postfix=>$user_code),array(),1);
			if($agent = $request_ag->Fetch())
			{
				$arr_user_id[$user_code]=$agent["UF_BITRIX_USER".$postfix];
			}
		}
		$res = HlBlockElement::Update($data_res["hblock"],intval($get_request_to["ID"]),Array("UF_AGENT".$postfix=>$user_code,"UF_INNER_STATUS".$postfix=>2,"UF_UPDATE_DATE".$postfix=>$time_s));
		$user_id_default = $arr_user_id[$user_code];
		if(IsModuleInstalled("im") && CModule::IncludeModule("im")&&isset($arr_user_id[$user_code]))
		{
			$arMessageFields = array(
				"TO_USER_ID" => $user_id_default,
				"FROM_USER_ID" => 0, 
				"NOTIFY_TYPE" => IM_NOTIFY_SYSTEM, 
				"NOTIFY_MODULE" => "im", 
				"NOTIFY_TAG" => "IM_CONFIG_NOTICE", 
				"NOTIFY_MESSAGE" => '[b]<a href="/mobile/realty/view/?REQUEST_ID='.$get_request_to["ID"].'">Новая заявка</a>[/b][br] Новая заявка для вас. Просьба связаться с клиентом в течение 15 минут, иначе заявка будет передана следующему дежурному агенту ([b]тестовый режим[/b])',
			);
			CIMNotify::Add($arMessageFields);
			//echo $user_id_default." ".$get_request_to["ID"]."!!!<br>";
		}
		unset($arr_ids[$get_request_to["ID"]]);
	}
	/*foreach ($arr_ids as $k=>$v)
	{
		//delete from table call	
	}*/
?>