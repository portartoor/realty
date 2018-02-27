<?
define("NO_KEEP_STATISTIC", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require($_SERVER["DOCUMENT_ROOT"]."/libs/rights_class.php");
$Project = new Rights();
$data_res = $Project->get_requests_file();
$postfix  = $Project->get_postfix();
$agents_hblock = $Project->get_agents_hb_id();
$client_hblock = $Project->get_clients_hb_id();
$arr = Array();
$res=false;
$time_s = date("d.m.Y H:i:s");

if($_POST["REQUEST_ID"]=="")
{
	if($_GET["REQUEST_ID"]=="")
	{
		global $USER;
		$arr_to = Array("UF_AGENT"=>$USER->GetID(),"UF_INNER_STATUS"=>0,"UF_UPDATE_DATE"=>$time_s,"UF_ADD_DATE"=>$time_s);
		$arr_to_view = $Project->add_postfix_to_fields($arr_to);
		$res = HlBlockElement::Add($data_res["hblock"],$arr_to_view);
		$request_id = $res->getid();
	}
	else
		$request_id = $_GET["REQUEST_ID"];
}
else
	$request_id = $_POST["REQUEST_ID"];

$arr["REQUEST_ID"]=($res!=false)?$res->getid():$request_id;
$arr["status"]=1;
$uploaddir = "pictures/".$request_id."/";
if ($REQUEST_METHOD=="POST")
{
	$fild_name = $_POST["name"];
	$fild_name_clean = $_POST["name"];
	if($postfix!=""&&strpos($fild_name,$postfix)===FALSE)
	{
		$fild_name = $fild_name.$postfix;
	}
	else if($postfix!=""&&strpos($fild_name,$postfix)!==FALSE)
	{
		$fild_name_clean = str_replace($postfix,"",$fild_name_clean);
	}
	if($fild_name_clean!="UF_PHOTO_PREVIEW")
	{
		$request = HlBlockElement::GetList($data_res["hblock"],array($fild_name),array("ID"=>$request_id),array(),1);
		$request_data = $request->Fetch();
	}
	$error = false;
	$user_code = "";
	$arr_q = HlBlockElement::GetList($agents_hblock,array(),array("UF_BITRIX_USER".$postfix=>$USER->GetID()),array(),1);
	if($arr_s_client = $arr_q->Fetch()){
		$user_code = $arr_s_client["UF_AGENT_ID".$postfix];
	}
	
    foreach($_FILES as $file)
    {
		$arIMAGE = $file;
		if (strlen($arIMAGE["name"])>0) 
		{
			$photo_template = Array(
				"CHANGED" => 1,
				"USER" => $user_code,
				"PHOTO" => ($fild_name_clean=="UF_PHOTOS")?1:0,
				"PLAN" => ($fild_name_clean=="UF_PLAN_PHOTOS")?1:0,
				"DOC" => ($fild_name_clean=="UF_DOCS")?1:0,
				"PREVIEW" =>($fild_name_clean=="UF_PHOTO_PREVIEW")?1:0,
				"SEND" => 0,
				"ORDER" => 30
			);
			$ext = explode(".",$arIMAGE["name"]);
			
			if($ext[sizeof($ext)-1]!="")
			{
				$ext_f = $ext[sizeof($ext)-1];
				if($ext_f=="jpeg"||$ext_f=="peg")$ext_f="jpg";
				$length=5;
				$arIMAGE["name"]= substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length).".".$ext_f;
			}
			$arIMAGE["description"]=json_encode($photo_template);
			$fid = CFile::SaveFile($arIMAGE, $uploaddir);
			$img_small = CFile::ResizeImageGet($fid, array('width'=>150, 'height'=>150), BX_RESIZE_IMAGE_EXACT, true);                
			if($fild_name_clean=="UF_DOCS")
				$files[] = "<div class=\"img_item\" data-id=\"".$fid."\" data-url=\"".CFile::GetPath($fid)."\"><img src=\"/images/icons/pdf-reader.jpg\"/></div><br><input type=\"checkbox\"><label>выгружать</label>";
			else if($fild_name_clean=="UF_PHOTO_PREVIEW")
				$files[] = '<div class="img_item" data-id="'.$fid.'" data-url="'.CFile::GetPath($fid).'"><img src="'.$img_small['src'].'"/></div><br><input type="checkbox"><label>выгружать</label>';	
			else
				$files[] = '<div class="img_item" data-id="'.$fid.'" data-url="'.CFile::GetPath($fid).'"><img src="'.$img_small['src'].'"/></div><br><input type="checkbox"><label>выгружать</label>';	
			if (intval($fid)>0) 
			{
				if($fild_name_clean!="UF_PHOTO_PREVIEW")
					$request_data[$fild_name][]=$fid;
				$res = HlBlockElement::Update($data_res["hblock"],$request_id,Array($fild_name=>($fild_name_clean=="UF_PHOTO_PREVIEW")?$fid:$request_data[$fild_name],"UF_UPDATE_DATE".$postfix=>$time_s));
			}
		}
    }
    $arr = ($error) ? array('error' => 'There was an error uploading your files') : array('files' => $files);

}
	$fild_name = $_GET["name"];
	$fild_name_clean = $_GET["name"];
	if($postfix!=""&&strpos($fild_name,$postfix)===FALSE)
	{
		$fild_name = $fild_name.$postfix;
	}
	else if($postfix!=""&&strpos($fild_name,$postfix)!==FALSE)
	{
		$fild_name_clean = str_replace($postfix,"",$fild_name_clean);
	}
if($_GET["del"]=="1")
{
	$request = HlBlockElement::GetList($data_res["hblock"],array($fild_name),array("ID"=>$request_id),array(),1);
	$request_data = $request->Fetch();
	if(($fild_name_clean=="UF_PHOTO_PREVIEW" && $_GET["value"]==$request_data[$fild_name])||($key = array_search($_GET["value"], $request_data[$fild_name])) !== false) {
		if($fild_name_clean!="UF_PHOTO_PREVIEW")	
			unset($request_data[$fild_name][$key]);
		$res = HlBlockElement::Update($data_res["hblock"],$request_id,Array($fild_name=>($fild_name_clean=="UF_PHOTO_PREVIEW")?"":$request_data[$fild_name],"UF_UPDATE_DATE".$postfix=>$time_s));
		CFile::Delete($_GET["value"]);
	}
}
if($_GET["move"]=="1")
{
	$arr_images = explode(",",trim($_GET["value"],","));
	foreach ($arr_images as $k=>$v)
	{
		$file_info = CFile::GetByID($v);
		$arFile = $file_info->Fetch();
		$desc_line = $arFile["DESCRIPTION"];
		$desc_arr = json_decode($desc_line,1);
		$desc_arr["CHANGED"]=1;
		$desc_arr["ORDER"]=$k+2;
		CFile::UpdateDesc($v,json_encode($desc_arr));
	}
	$res = HlBlockElement::Update($data_res["hblock"],$request_id,Array($fild_name=>$arr_images,"UF_UPDATE_DATE".$postfix=>$time_s));
}
if(isset($_GET["send"]))
{
	$image = trim($_GET["value"]);
	$file_info = CFile::GetByID($image);
	$arFile = $file_info->Fetch();
	$desc_line = $arFile["DESCRIPTION"];
	$desc_arr = json_decode($desc_line,1);
	$desc_arr["CHANGED"]=1;
	$desc_arr["SEND"]=intval($_GET["send"]);
	CFile::UpdateDesc($image,json_encode($desc_arr));
}
echo json_encode($arr);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>