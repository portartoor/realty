<?
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
$_SERVER["DOCUMENT_ROOT"] = realpath(dirname(__FILE__)."/..");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
set_time_limit(60*10);
require($_SERVER["DOCUMENT_ROOT"]."/libs/rights_class.php");
$Project = new Rights();
$data_res = $Project->get_requests_file();
$file = "agents.xml";
$hblock = $Project->get_agents_hb_id();
$postfix = $Project->get_postfix();
if (file_exists($_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$data_res["file"]."/".$file)) {
    $xml = simplexml_load_file($_SERVER["DOCUMENT_ROOT"]."/upload/1c/".$data_res["file"]."/".$file);
	foreach ($xml->item as $value) {
		$request_id=false;
		$res_t=Array();
		foreach ($value as $x1=>$y1)
		{
			$res_t[$x1]=trim((string)$y1);
		}
		$arr=Array(
			"UF_AGENT_NAME".$postfix => $res_t["VALUE"],
			"UF_AGENT_ID".$postfix => $res_t["XML_ID"]);
		$request = HlBlockElement::GetList($hblock,array(),array("UF_AGENT_ID".$postfix=>$arr["UF_AGENT_ID".$postfix]),array(),1);
		$request_data = $request->Fetch();

		if(!empty($request_data))
		{
			$request_id=$request_data["ID"];
		}
		if($request_id==false)
		{	
			$request = HlBlockElement::GetList($hblock,array(),array("UF_AGENT_ID".$postfix=>"","UF_AGENT_NAME".$postfix=>$res_t["VALUE"]),array(),1);
			$request_data = $request->Fetch();
	
			if(!empty($request_data))
			{
				$request_id=$request_data["ID"];
			}
			if($request_id==false)
			{
				HlBlockElement::Add(
					$hblock,
					$arr
				);
			}
			else if($arr["UF_AGENT_ID"]!=$request_data["UF_AGENT_ID"]||$arr["UF_AGENT_NAME"]!=$request_data["UF_AGENT_NAME"]) 
			{
				//print_r($request_data);print_r($arr);echo "<br><br>";
				/*HlBlockElement::Update(
					$hblock,
					$request_id,
					$arr
				);*/
			}
			else
			{
				echo "<xmp>";print_r($arr);echo "</xmp>";
			}
		}
	}
}
$rsUsers = CUser::GetList();
while($rsUser = $rsUsers->Fetch())
{
	if(strlen($rsUser["NAME"])<3||strlen($rsUser["LAST_NAME"])<3)continue;
	$arr_q = HlBlockElement::GetList($hblock,array(),array(array( 
														"LOGIC" => "AND",
														array("UF_AGENT_NAME".$postfix => "%".$rsUser["NAME"]."%"),
														array("UF_AGENT_NAME".$postfix => "%".$rsUser["LAST_NAME"]."%")
														),
													"UF_BITRIX_USER".$postfix=>NULL),
													array(),1);
	if($rsUser["NAME"]!=""&&$rsUser["LAST_NAME"]!=""&&$arr_s_client = $arr_q->Fetch())
	{
		$res = HlBlockElement::Update($hblock,$arr_s_client["ID"],Array("UF_BITRIX_USER".$postfix=>$rsUser["ID"]));
	}
}
?>
<h2>Агенты синхронизированы</h2>
<?
file_put_contents($_SERVER["DOCUMENT_ROOT"]."/upload/cron/syn_agents_".$Project->s_name.".txt","fin".date("Y-m-d H:i:s"));
if($Project->s_name=="invent")
{
	LocalRedirect("sync_agents.php?project=domofey");
}
/*
if($Project->s_name=="domofey")
{
	LocalRedirect("interes_clean.php");
}*/
?>