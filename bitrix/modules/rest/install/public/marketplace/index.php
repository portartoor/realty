<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("");
?><?$APPLICATION->IncludeComponent(
	"bitrix:rest.marketplace", 
	".default", 
	array(
		"SEF_FOLDER" => "/marketplace/",
		"SEF_MODE" => "Y",
		"COMPONENT_TEMPLATE" => ".default",
		"APPLICATION_URL" => "/marketplace/app/#id#/",
		"SEF_URL_TEMPLATES" => array(
			"top" => "",
			"category" => "category/#category#/",
			"detail" => "detail/#app#/",
			"search" => "search/",
			"buy" => "buy/",
			"updates" => "updates/",
		)
	),
	false
);?><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>