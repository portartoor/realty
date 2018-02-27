<?
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include.php");
require($_SERVER["DOCUMENT_ROOT"] . "/libs/realty_class.php");
$sayavka_id = isset($_POST["REQUEST_ID"])?$_POST["REQUEST_ID"]:"";
global $USER;
$request = HlBlockElement::GetList(2,array(),array("ID"=>$sayavka_id),array(),1);
$request_data = $request->Fetch();
if(!empty($request_data))
{
	$sayavka_id=$request_data["ID"];
}
else die("Нет такого объекта");
if ($_POST["REQUEST_ID"] != "")
{
	$arFields = $_POST;
	$arFields["UF_REQUEST_HL_ID"] = $arFields["REQUEST_ID"];
	unset($arFields["REQUEST_ID"]);
	unset($arFields["web_form_submit"]);	
	HlBlockElement::Add(16, $arFields);
}
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>