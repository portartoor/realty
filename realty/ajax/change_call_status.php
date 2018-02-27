<?
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
	require($_SERVER["DOCUMENT_ROOT"]."/libs/rights_class.php");
	$Project = new Rights();
	$data_res = $Project->get_requests_file();
	$postfix  = $Project->get_postfix();
	global $USER;
	$call = HlBlockElement::GetList($Project->get_call_hb_id(),array(),array("UF_REQ_ID".$postfix=>intval($_GET["REQUEST_ID"]),"UF_ACT".$postfix=>1),array(),1);
	if($arr_call = $call->Fetch()){
		$request_ag = HlBlockElement::GetList($Project->get_agents_hb_id(),array(),array("UF_BITRIX_USER".$postfix=>$USER->GetID()),array(),1);
		if($agent_me = $request_ag->Fetch())
		{
			if($agent_me["UF_AGENT_ID".$postfix]==$arr_call["UF_AGENTCODE_C".$postfix])
			{
				$time_s = date("d.m.Y H:i:s");
				$res = HlBlockElement::Update($Project->get_call_hb_id(),$arr_call["ID"],Array("UF_ACT".$postfix=>"","UF_CALL_TIME".$postfix=>$time_s));
				$res = HlBlockElement::Update($data_res["hblock"],intval($_GET["REQUEST_ID"]),Array("UF_AGENT".$postfix=>$agent_me["UF_AGENT_ID".$postfix],"UF_UPDATE_DATE".$postfix=>$time_s,"UF_INNER_STATUS".$postfix=>2));
				echo "1";
			}
		}
	}
?>