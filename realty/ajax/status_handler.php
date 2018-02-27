<?
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include.php");
require($_SERVER["DOCUMENT_ROOT"] . "/libs/realty_class.php");
require($_SERVER["DOCUMENT_ROOT"]."/libs/rights_class.php");
$Project = new Rights();
$data_res = $Project->get_requests_file();
$postfix  = $Project->get_postfix();
$sayavka_id = isset($_POST["REQUEST_ID"])?$_POST["REQUEST_ID"]:"";
global $USER;
$request = HlBlockElement::GetList($data_res["hblock"],array(),array("ID"=>$sayavka_id),array(),1);
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
	HlBlockElement::Add($Project->status_change_hb(), $Project->add_postfix_to_fields($arFields));
}
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/footer.php");
?>