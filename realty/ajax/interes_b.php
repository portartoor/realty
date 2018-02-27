<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
global $USER;
if(isset($_GET["REQUEST_ID"])&&isset($_GET["obj"])&&isset($_GET["interes"]))
{
	$request_id = $_GET["REQUEST_ID"];
	$r_f = $_GET["obj"];
	$r_f_c = $_GET["r_f_c"];
	$r_t_c = $_GET["r_t_c"];
	$int = intval($_GET["interes"]);
	$n_int = ($int==1)?0:1;
	$time_s = date("d.m.Y H:i:s");
	$arr_q = HlBlockElement::GetList(13,array(),array(Array("UF_REQUEST_F"=>$request_id,"UF_REQUEST_S"=>$r_f,"UF_ACTIVE"=>$n_int),Array("UF_REQUEST_F"=>$r_f,"UF_REQUEST_S"=>$request_id,"UF_ACTIVE"=>$n_int),"LOGIC" => "OR"),array(),1);
	if($arr_s_f = $arr_q->Fetch()){
		$res = HlBlockElement::Update(13,$arr_s_f["ID"],Array("UF_INTERES_TIME"=>$time_s,"UF_ACTIVE"=>$int));
		if(strpos($_GET["agent_code"],"new_user")===FALSE)
		{
			$arr_q = HlBlockElement::GetList(5,array("UF_BITRIX_USER"),array("UF_AGENT_ID"=>$_GET["agent_code"]),array(),1);
			if($arr_s_client = $arr_q->Fetch()){
				$user_id_to = $arr_s_client["UF_BITRIX_USER"];	
			}
		}
		else
		{
			$user_id_to = str_replace("new_user_","",$_GET["agent_code"]);
		}
		if(CModule::IncludeModule("im")):
			if($arr_s_f["UF_INTERES_CHAT"])
			{
				$chat_id = $arr_s_f["UF_INTERES_CHAT"];
				$params = Array(
					"CHAT_ID"=>$chat_id,
					"USER_ID"=>$USER->GetID(),
					"MESSAGE"=>(($int==1)?"Интерес":"Отменён интерес").' к заявке '.$r_t_c.' от '.$r_f_c
				);
				CIMChat::AddSystemMessage($params);
			}
			$arFields = array(
				"MESSAGE_TYPE" => "S", # P - private chat, G - group chat, S - notification
				"TO_USER_ID" => $user_id_to,
				"FROM_USER_ID" => $USER->GetID(),
				"MESSAGE" => (($int==1)?"Интерес":"Отменён интерес").' к заявке <a target="_self" href="/realty/view/?REQUEST_ID='.$request_id.'" >'.$r_t_c.'</a> от <a target="_self" href="/realty/view/?REQUEST_ID='.$r_f.'" >'.$r_f_c."</a>",
				"AUTHOR_ID" => $USER->GetID(),
				"EMAIL_TEMPLATE" => "some", 
				"NOTIFY_TYPE" => 2,  # 1 - confirm, 2 - notify single from, 4 - notify single
				"NOTIFY_MODULE" => "main", # module id sender (ex: xmpp, main, etc)
				"NOTIFY_EVENT" => "IM_NEW_NOTIFY", # module event id for search (ex, IM_GROUP_INVITE)
				"NOTIFY_TITLE" => (($int==1)?"Интерес":"Отменён интерес").' к заявке <a target="_self" href="/realty/view/?REQUEST_ID='.$request_id.'" >'.$r_t_c.'</a> от <a target="_self" href="/realty/view/?REQUEST_ID='.$r_f.'" >'.$r_f_c."</a>", # notify title to send email
				);
			if(CIMMessenger::Add($arFields))
				echo "success";
			else
				echo "error";
		endif;
	}
	else
		$res = HlBlockElement::Add(13,Array("UF_REQUEST_S"=>$request_id,"UF_REQUEST_F"=>$r_f,"UF_INTERES_TIME"=>$time_s,"UF_REQUEST_STATUS"=>179,"UF_ACTIVE"=>$int));
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");?>