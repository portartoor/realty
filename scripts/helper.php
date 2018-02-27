<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Title");
?><?/*
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$rsData = HlBlockElement::GetList(2,array(),array(">UF_ID"=>91660),array(),10000);

while($arRes = $rsData->Fetch())
{
	$request_id =$arRes["ID"];
	echo $arRes["UF_ID"]." ".$arRes["ID"]."<br>";
	HlBlockElement::Remove(2,$request_id);
}
*/
require($_SERVER["DOCUMENT_ROOT"]."/libs/rights_class.php");
$Project = new Rights();
$data_res = $Project->get_requests_file();
$postfix  = $Project->get_postfix();
$postfix_d = "";
$arr["UF_AGENT"]="259eec45-6ae1-11e2-b6b7-b74deef9eed9";
$division_head="259eec4c-6ae1-11e2-b6b7-b74deef9eed9";
if($Project->s_name=="domofey")
	$postfix_d="d_";
echo "sdf".$Project->get_agents_hb_id()."!".$division_head;
$request_agent = HlBlockElement::GetList($Project->get_agents_hb_id(),array(),array("UF_AGENT_ID".$postfix=>$arr["UF_AGENT"],"!=UF_HEAD_1C".$postfix=>$division_head),array(),1);
			$request_agent_data = $request_agent->Fetch();
			if(!empty($request_agent_data))
			{echo "tuuuut";
				//$res = HlBlockElement::Update($Project->get_agents_hb_id(),$request_agent_data["ID"],Array("UF_HEAD_1C".$postfix=>$division_head));
			}
?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>