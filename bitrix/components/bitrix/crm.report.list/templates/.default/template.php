<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<h3><?= htmlspecialcharsbx(GetMessage('CRM_REPORT_LIST_DEAL'))?></h3>
<? $APPLICATION->IncludeComponent(
	'bitrix:report.list',
	'',
	array(
		'PATH_TO_REPORT_LIST' => $arParams['PATH_TO_REPORT_REPORT'],
		'PATH_TO_REPORT_CONSTRUCT' => $arParams['PATH_TO_REPORT_CONSTRUCT'],
		'PATH_TO_REPORT_VIEW' => $arParams['PATH_TO_REPORT_VIEW'],
		'REPORT_HELPER_CLASS' => 'CCrmReportHelper'
	),
	false
);?>
<h3><?= htmlspecialcharsbx(GetMessage('CRM_REPORT_LIST_PRODUCT'))?></h3>
<?$APPLICATION->IncludeComponent(
	'bitrix:report.list',
	'',
	array(
		'PATH_TO_REPORT_LIST' => $arParams['PATH_TO_REPORT_REPORT'],
		'PATH_TO_REPORT_CONSTRUCT' => $arParams['PATH_TO_REPORT_CONSTRUCT'],
		'PATH_TO_REPORT_VIEW' => $arParams['PATH_TO_REPORT_VIEW'],
		'REPORT_HELPER_CLASS' => 'CCrmProductReportHelper'
	),
	false
);?>

