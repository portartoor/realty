<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$APPLICATION->ShowViewContent("task_menu");
$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$bodyClass = $bodyClass ? $bodyClass." page-one-column" : "page-one-column";
$APPLICATION->SetPageProperty("BodyClass", $bodyClass);

$arComponentParams = array(
	'USER_ID'                => $arResult['USER_ID'],
	'GROUP_ID'               =>  0,
	'SHOW_TASK_LIST_MODES'   => 'N',
	'SHOW_HELP_ICON'         => 'N',
	'SHOW_SEARCH_FIELD'      => 'N',
	'SHOW_TEMPLATES_TOOLBAR' => 'N',
	'SHOW_QUICK_TASK_ADD'    => 'N',
	'SHOW_ADD_TASK_BUTTON'   => 'N',
	'SHOW_FILTER_BUTTON'     => 'N',
	'SHOW_SECTIONS_BAR'      => 'Y',
	'SHOW_FILTER_BAR'        => 'N',
	'SHOW_COUNTERS_BAR'      => 'N',
	'SHOW_SECTION_PROJECTS'  => 'Y',
	'SHOW_SECTION_MANAGE'    => 'A',	// auto
	'SHOW_SECTION_COUNTERS'  => 'Y',
	'MARK_ACTIVE_ROLE'       => 'N',
	'MARK_SECTION_PROJECTS'  => 'N',
	'MARK_SECTION_REPORTS'   => 'Y',
	'SECTION_URL_PREFIX'     =>  CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_TASKS"], array())
);

if ($arParams['GROUP_ID'])
	$arComponentParams['GROUP_ID'] = $arParams['GROUP_ID'];

if ($arResult['USER_ID'] > 0)
{
	$arComponentParams['PATH_TO_PROJECTS'] = CComponentEngine::MakePathFromTemplate(
		$arParams['PATH_TO_USER_TASKS_PROJECTS_OVERVIEW'],
		array('user_id' => $arResult['USER_ID'])
	);
}

$arComponentParams['USE_TITLE_TARGET'] = 'N';

$APPLICATION->IncludeComponent(
	'bitrix:tasks.list.controls',
	'.default',
	$arComponentParams,
	null,
	array('HIDE_ICONS' => 'Y')
);

$APPLICATION->IncludeComponent(
	"bitrix:report.list",
	"",
	array(
		"USER_ID" => $arResult["USER_ID"],
		"GROUP_ID" => $arParams["GROUP_ID"],
		"PATH_TO_REPORT_LIST" => $arParams["PATH_TO_TASKS_REPORT"],
		"PATH_TO_REPORT_CONSTRUCT" => $arParams["PATH_TO_TASKS_REPORT_CONSTRUCT"],
		"PATH_TO_REPORT_VIEW" => $arParams["PATH_TO_TASKS_REPORT_VIEW"],
		"REPORT_HELPER_CLASS" => "CTasksReportHelper"
	),
	false
);

?>