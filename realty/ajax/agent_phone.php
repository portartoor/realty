<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require($_SERVER["DOCUMENT_ROOT"]."/libs/rights_class.php");
$Project = new Rights();
$agent_hblock = $Project->get_agents_hb_id();
$postfix  = $Project->get_postfix();
if(isset($_GET["agent_code"]))
{
	if(strpos($_GET["agent_code"],"new_user")===FALSE)
	{
		$arr_q = HlBlockElement::GetList($agent_hblock,array("UF_BITRIX_USER".$postfix),array("UF_AGENT_ID".$postfix=>$_GET["agent_code"]),array(),1);
		if($arr_s_client = $arr_q->Fetch()){
			$user = $arr_s_client["UF_BITRIX_USER".$postfix];
			$rsUser = CUser::GetByID($user);
			$arUser = $rsUser->Fetch();
			echo $arUser["PERSONAL_MOBILE".$postfix];
		}
	}
	else
	{
		$user = str_replace("new_user_","",$_GET["agent_code"]);
		$rsUser = CUser::GetByID($user);
		$arUser = $rsUser->Fetch();
		echo $arUser["PERSONAL_MOBILE".$postfix];
	}
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");?>