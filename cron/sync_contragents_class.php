<?
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
function xml2array ( $xmlObject, $out = array () )
{
    foreach ( (array) $xmlObject as $index => $node )
        $out[strtoupper($index)] = ( is_object ( $node ) ) ? xml2array ( $node ) : $node;
    return $out;
}

class SyncContragentsClass {
	private $arr_base_1c=Array();
	private $highloadblock;
	function __construct(){
		global $Project;
		$postfix = $Project->get_postfix();
		$this->highloadblock = $Project->get_clients_hb_id();
		$request = HlBlockElement::GetList($this->highloadblock,array("UF_ID_1C".$postfix,"ID"),array(),array(),1000000);
		WHILE($request_data = $request->Fetch())
		{
			if(!empty($request_data))
			{
				$arr_base_1c[$request_data["UF_ID_1C".$postfix]]=$request_data["ID"];
			}
		}

		$this->arr_base_1c = $arr_base_1c;
	}
	function AddNewItem($v)
	{	

		$arr_base_1c = $this->arr_base_1c;

	    $client_id=false;
		$res_t=Array();
		foreach ($v as $x1=>$y1)
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
		$arr["UF_CLIENT_STATUS"]="3";
		global $Project;
		if(array_key_exists($arr["UF_ID_1C"],$arr_base_1c))
		{
			$client_id=$arr_base_1c[$arr["UF_ID_1C"]];
			$arr = $Project->add_postfix_to_fields($arr);
			$res = HlBlockElement::Update($this->highloadblock,$client_id,$arr);
		}
		else
		{
			//echo"<xmp>";print_r($arr);echo"</xmp>";
			$arr = $Project->add_postfix_to_fields($arr);
			$res_1 = HlBlockElement::Add($this->highloadblock,$arr);
			//die($this->highloadblock."add");
		}
   }
}
?>