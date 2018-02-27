<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Бизнес-процессы");
?>
<?
if(CModule::IncludeModule("crm")){  
$APPLICATION->IncludeComponent(
	"bitrix:bizproc.wizards",
	"",
	Array(
		"ADMIN_ACCESS" => array("1"),
		"AJAX_MODE" => "Y",
		"AJAX_OPTION_ADDITIONAL" => "",
		"AJAX_OPTION_HISTORY" => "N",
		"AJAX_OPTION_JUMP" => "Y",
		"AJAX_OPTION_STYLE" => "Y",
		"IBLOCK_TYPE" => "bizproc_iblockx",
		"SEF_FOLDER" => "/business/",
		"SEF_MODE" => "Y",
		"SEF_URL_TEMPLATES" => Array("bp"=>"#block_id#/bp.php","edit"=>"#block_id#/edit.php","index"=>"index.php","list"=>"#block_id#/","log"=>"#block_id#/log-#bp_id#.php","new"=>"new.php","setvar"=>"#block_id#/setvar.php","start"=>"#block_id#/start.php","task"=>"#block_id#/task-#task_id#.php","view"=>"#block_id#/view-#bp_id#.php"),
		"SET_NAV_CHAIN" => "Y",
		"SET_TITLE" => "Y",
		"SKIP_BLOCK" => "N"
	)
);}else echo "net";?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>