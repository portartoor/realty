<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
if(isset($_GET["agent_code"]))
{
	if(strpos($arElement["UF_AGENT"],"new_user")===FALSE)
	{
		$arr_q = HlBlockElement::GetList(5,array("UF_BITRIX_USER"),array("UF_AGENT_ID"=>$_GET["agent_code"]),array(),1);
		if($arr_s_client = $arr_q->Fetch()){
			$user = $arr_s_client["UF_BITRIX_USER"];
			$rsUser = CUser::GetByID($user);
			$arUser = $rsUser->Fetch();
			echo $arUser["PERSONAL_MOBILE"];
		}
	}
	else
	{
		$user = str_replace("new_user_","",$_GET["agent_code"]);
		$rsUser = CUser::GetByID($user);
		$arUser = $rsUser->Fetch();
		echo $arUser["PERSONAL_MOBILE"];
	}
}
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");?>