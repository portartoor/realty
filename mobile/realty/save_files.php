<?
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
$arr = Array();
$res=false;
$time_s = date("d.m.Y H:i:s");

if($_POST["REQUEST_ID"]=="")
{
	if($_GET["REQUEST_ID"]=="")
	{
		global $USER;
		$res = HlBlockElement::Add(2,Array("UF_AGENT"=>$USER->GetID(),"UF_INNER_STATUS"=>0,"UF_UPDATE_DATE"=>$time_s,"UF_ADD_DATE"=>$time_s));
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
	if($_POST["name"]!="UF_PHOTO_PREVIEW")
	{
		$request = HlBlockElement::GetList(2,array($_POST["name"]),array("ID"=>$request_id),array(),1);
		$request_data = $request->Fetch();
	}
	$error = false;
    foreach($_FILES as $file)
    {
		$arIMAGE = $file;
		if (strlen($arIMAGE["name"])>0) 
		{
			$fid = CFile::SaveFile($arIMAGE, $uploaddir);
			$img_small = CFile::ResizeImageGet($fid, array('width'=>80, 'height'=>80), BX_RESIZE_IMAGE_EXACT, true);                
			if($_POST["name"]=="UF_DOCS")
				$files[] = "<div class=\"img_item\" data-id=\"".$fid."\" data-url=\"".CFile::GetPath($fid)."\"><img src=\"/images/icons/pdf-reader.jpg\"/></div>";
			else 
				$files[] = '<div class="img_item" data-id="'.$fid.'" data-url="'.CFile::GetPath($fid).'"><img src="'.$img_small['src'].'"/></div>';	
			if (intval($fid)>0) 
			{
				if($_POST["name"]!="UF_PHOTO_PREVIEW")
					$request_data[$_POST["name"]][]=$fid;
				$res = HlBlockElement::Update(2,$request_id,Array($_POST["name"]=>($_POST["name"]=="UF_PHOTO_PREVIEW")?$fid:$request_data[$_POST["name"]],"UF_UPDATE_DATE"=>$time_s));
			}
		}
    }
    $arr = ($error) ? array('error' => 'There was an error uploading your files') : array('files' => $files);

}
if($_GET["del"]=="1")
{
	$request = HlBlockElement::GetList(2,array($_GET["name"]),array("ID"=>$request_id),array(),1);
	$request_data = $request->Fetch();
	if(($_GET["name"]=="UF_PHOTO_PREVIEW" && $_GET["value"]==$request_data[$_GET["name"]])||($key = array_search($_GET["value"], $request_data[$_GET["name"]])) !== false) {
		if($_GET["name"]!="UF_PHOTO_PREVIEW")	
			unset($request_data[$_GET["name"]][$key]);
		$res = HlBlockElement::Update(2,$request_id,Array($_GET["name"]=>($_GET["name"]=="UF_PHOTO_PREVIEW")?"":$request_data[$_GET["name"]],"UF_UPDATE_DATE"=>$time_s));
		CFile::Delete($_GET["value"]);
	}
}
echo json_encode($arr);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
?>