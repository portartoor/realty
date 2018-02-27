<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Почтовые шаблоны");
$APPLICATION->IncludeComponent(
	'bitrix:crm.mail_template', 
	'', 
	array(
		"SEF_MODE" => "Y",
		"SEF_FOLDER" => "/crm/configs/mailtemplate/",
	),
	false
); 
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");
?>