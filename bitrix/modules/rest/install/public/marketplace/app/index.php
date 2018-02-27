<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");?>

<?$APPLICATION->IncludeComponent(
	"bitrix:app.layout", 
	".default", 
	array(
		"ID" => $_GET["id"],
		"COMPONENT_TEMPLATE" => ".default",
		"DETAIL_URL" => "/marketplace/detail/#code#/",
		"SEF_MODE" => "Y",
		"SEF_FOLDER" => "/marketplace/app/",
		"SEF_URL_TEMPLATES" => array(
			"application" => "#id#/",
		)
	),
	false
);?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>