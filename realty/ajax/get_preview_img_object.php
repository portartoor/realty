<?
if(isset($_GET["site_nw"]))
{
	/*
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
	CModule::IncludeModule("iblock");
	
	$IdObject = intval(htmlspecialchars(trim($_REQUEST["Id"])));
	$Src = "";
	if($IdObject > 0){
		$Query = CIBlockElement::GetList(
			array(),
			array(
				"IBLOCK_ID" => 5,
				"=PROPERTY_Id" => $IdObject
			),
			false, 
			array("nPageSize"=>1), 
			array("ID","NAME","PROPERTY_Id","PREVIEW_PICTURE")
		)->Fetch();
		
		if(!empty($Query) && $Query["PREVIEW_PICTURE"] > 0){
			$QueryFile = CFile::GetByID($Query["PREVIEW_PICTURE"])->Fetch();
			if(!empty($QueryFile)){
				$Src = "http://invent-realty.ru";
				$Src .= "/upload/".$QueryFile["SUBDIR"]."/".$QueryFile["FILE_NAME"];
			}	
		}
	}
	echo $Src;
	*/
}
else
{
	if(isset($_GET["Id"]))
		$id = $_GET["Id"];
	else die("/bitrix/templates/realty/images/soon.jpg");
	if(file_exists("../../upload/1c/preview/".$id.".jpg"))
		die("/upload/1c/preview/".$id.".jpg");
	if(is_dir("../../upload/1c/foto/".$id))
	{
		require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
		$file = glob("../../upload/1c/foto/".$id."/*",GLOB_NOSORT);
		$destinationFile = "../../upload/1c/preview/".$id.".jpg";
		if(CFile::ResizeImageFile($file[0], $destinationFile, array("width"=>300,"height"=>300), BX_RESIZE_IMAGE_EXACT))
		{
			die("/upload/1c/preview/".$id.".jpg");
		}
		else die("/bitrix/templates/realty/images/soon.jpg");
	}
}
?>