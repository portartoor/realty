<?
die();
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
set_time_limit(60*10);
$type="hblock";
$file = "kontragents.xml";
$step = (isset($_GET["step"]))?$_GET["step"]:0;
$hblock= 10;
if (file_exists($_SERVER["DOCUMENT_ROOT"]."/upload/1c/B24_Catalogs/".$file)) {
	$request = HlBlockElement::GetList($hblock,array(),array(),array(),1000000);
		WHILE($request_data = $request->Fetch())
		{
			if(!empty($request_data))
			{
				$arr_base_1c[]=$request_data["UF_ID_1C"];
			}
		}
    $xml = simplexml_load_file($_SERVER["DOCUMENT_ROOT"]."/upload/1c/B24_Catalogs/".$file);
	$counter=0;
	foreach ($xml->item as $value) {
		if($counter<$step*100)
		{/*echo "!";*/
			$counter++;
			continue;
		}
		else if($counter>$step*100+100)
		{?> 
			<h1>Шаг <?=$step?></h1>
			<script type="text/javascript">
				var IntervalId = setInterval( function() { 
						window.location.href = "?step=<?=$step+1?>";
						clearInterval(IntervalId);
					}, 
					3000
				);
			</script>
			<?
			die();
		}
		//echo "#";
		$counter++;
		$request_id=false;
		$res_t=Array();
		foreach ($value as $x1=>$y1)
		{
			$res_t[$x1]=trim((string)$y1);
		}
		$arr=Array(
			"UF_ID_1C" => $res_t["XML_ID"],
			"UF_FIO" => $res_t["VALUE"],
			"UF_MAIL" => $res_t["EMAIL"],
			"UF_PHONE" => $res_t["PHONE"],
			"UF_PHONE_1" => $res_t["ADDITIONAL"],
			);
		if($arr["UF_PHONE"]==""&&$res_t["MOBILE"]!="")
		{
			$arr["UF_PHONE"]=$res_t["MOBILE"];
		}
		else if ($arr["UF_PHONE"]!=""&&$res_t["MOBILE"]!="")
		{
			$arr["UF_PHONE_1"]=$res_t["MOBILE"];
		}
		if($arr["UF_PHONE"]==""&&$res_t["WORK"]!="")
		{
			$arr["UF_PHONE"]=$res_t["WORK"];
		}
		else if ($arr["UF_PHONE"]!=""&&$res_t["WORK"]!="")
		{
			$arr["UF_PHONE_1"]=$res_t["WORK"];
		}
		if($arr["UF_PHONE"]==""&&$res_t["HOME"]!="")
		{
			$arr["UF_PHONE"]=$res_t["HOME"];
		}
		else if ($arr["UF_PHONE"]!=""&&$res_t["HOME"]!="")
		{
			$arr["UF_PHONE_1"]=$res_t["HOME"];
		}
		//$request = HlBlockElement::GetList($hblock,array(),array("UF_ID_1C"=>$arr["UF_ID_1C"]),array(),1);
		//$request_data = $request->Fetch();
		if(in_array($arr["UF_ID_1C"],$arr_base_1c))
		{
			$request_id=$request_data["ID"];
			$res = HlBlockElement::Update(10,$request_id,$arr);
			//die("###");
		}
		else
		{
			$arr["UF_CLIENT_STATUS"]="3";
			//echo"<xmp>";print_r($arr);echo"</xmp>";
			$res_1 = HlBlockElement::Add(10,$arr);
			//die("!!!");
		}
   }
} else {
    exit('Не удалось открыть файл '.$file.'.');
}
echo "all ok";
?>