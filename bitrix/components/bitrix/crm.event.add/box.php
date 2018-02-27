<?
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');


require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

if(!CModule::IncludeModule('crm'))
	return false;
?>
<script>
BX.loadCSS('/bitrix/components/bitrix/crm.event.add/templates/.default/style.css');
</script>
<?
$APPLICATION->IncludeComponent(
	'bitrix:crm.event.add',
	'',
	array(
		'ENTITY_TYPE' => $_REQUEST['ENTITY_TYPE'],
		'ENTITY_ID' => intval($_REQUEST['ENTITY_ID']),
		'FORM_TYPE' => $_REQUEST['FORM_TYPE'],
		'FORM_ID' => isset($_REQUEST['FORM_ID']) ? $_REQUEST['FORM_ID'] : '',
		'EVENT_TYPE' => isset($_REQUEST['EVENT_TYPE']) ? $_REQUEST['EVENT_TYPE'] : '',
		'FREEZE_EVENT_ID' => isset($_REQUEST['FREEZE_EVENT_ID']) ? $_REQUEST['FREEZE_EVENT_ID'] : ''
	),
	false
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');
?>
