<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
global $USER;
if(isset($_GET["agent_code"]))
{
	$dbResultObj = HlBlockElement::GetList(13, array("UF_INTERES_CHAT"), array(array(array("UF_REQUEST_S"=>$_GET["r_f"],"UF_REQUEST_F"=>$_GET["r_t"]),array("UF_REQUEST_S"=>$_GET["r_t"],"UF_REQUEST_F"=>$_GET["r_f"]),"LOGIC" => "OR"),"!UF_INTERES_CHAT" => ""), array(), 1);
	if($arElement = $dbResultObj->Fetch())
	{
		echo "chat".$arElement["UF_INTERES_CHAT"];
	}
	else
	{
		if(strpos($_GET["agent_code"],"new_user")===FALSE)
		{
			$arr_q = HlBlockElement::GetList(5,array("UF_BITRIX_USER"),array("UF_AGENT_ID"=>$_GET["agent_code"]),array(),1);
			if($arr_s_client = $arr_q->Fetch()){
				$user_to = $arr_s_client["UF_BITRIX_USER"];
			}
		}
		else
		{
			$user_to = str_replace("new_user_","",$_GET["agent_code"]);
		}
		$r_t_f="";
		$arr_q = HlBlockElement::GetList(2,array("UF_ID"),array("ID"=>$_GET["r_f"]),array(),1);
		if($arr_s_r = $arr_q->Fetch()){
			$r_f_c = ($arr_s_r["UF_ID"]!="")?$arr_s_r["UF_ID"]:"*".$_GET["r_f"];
		}
		$arParams = Array(
			"TITLE" => "Интерес к заявке ".$_GET["r_t_c"]." от ".$r_f_c,
			"USERS" => Array($user_to,$USER->GetID()),
			"AVATAR_ID" => $_GET["a"],
			"COLOR" => "GREEN"
		);
		if(CModule::IncludeModule("im")):
			$CIMChat = new CIMChat();
			$chat_id = $CIMChat->Add($arParams);
			$dbResultObj = HlBlockElement::GetList(13, array("ID"), array(array(array("UF_REQUEST_S"=>$_GET["r_f"],"UF_REQUEST_F"=>$_GET["r_t"]),array("UF_REQUEST_S"=>$_GET["r_t"],"UF_REQUEST_F"=>$_GET["r_f"]),"LOGIC" => "OR")), array(), 2);
			while($arElement = $dbResultObj->Fetch())
			{
				$res = HlBlockElement::Update(13,$arElement["ID"],Array("UF_INTERES_CHAT"=>$chat_id));
			}
			echo "chat".$chat_id;
		endif;
	}
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");?>