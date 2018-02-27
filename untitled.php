<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

$arr_q = HlBlockElement::GetList(5,array(),array("UF_BITRIX_USER"=>755),array(),100);
	while($arr = $arr_q->Fetch())
	{
		echo $arr["ID"]."!<br>";
		//$res = HlBlockElement::Update(5,$arr_s_client["ID"],Array("UF_BITRIX_USER"=>$rsUser["ID"]));
	}

?>


<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>