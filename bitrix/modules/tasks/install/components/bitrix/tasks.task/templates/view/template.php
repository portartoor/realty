<?
use Bitrix\Main\Localization\Loc;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CBitrixComponent $component */

$templateData = $arResult["TEMPLATE_DATA"];
if (isset($templateData["ERROR"]))
{
	foreach($templateData["ERROR"] as $error)
	{
		?><div class="task-message-label error"><?=htmlspecialcharsbx($error)?></div><?
	}
	return;
}

$taskData = isset($arResult["DATA"]["TASK"]) ? $arResult["DATA"]["TASK"] : array();
$can = isset($arResult["CAN"]["TASK"]["ACTION"]) ? $arResult["CAN"]["TASK"]["ACTION"] : array();
$userFields = isset($arResult["AUX_DATA"]["USER_FIELDS"]) ? $arResult["AUX_DATA"]["USER_FIELDS"] : array();
$diskUfCode = \Bitrix\Tasks\Integration\Disk\UserField::getMainSysUFCode();

$APPLICATION->ShowViewContent("task_menu");

//Menu and Page Title Buttons
if ($arParams["ENABLE_MENU_TOOLBAR"])
{
	$customElements = array(
		"ADD_BUTTON" => array(
			"name" => Loc::getMessage("TASKS_ADD_TASK_SHORT"),
			"id" => "task-detail-create-button"
		),
	);
	if($arParams['TOOLBAR_MODE'] != 'COMPACT')
	{
		$customElements['BACK_BUTTON_ALT'] = array(
			"name" => Loc::getMessage("TASKS_ADD_BACK_TO_TASKS_LIST"),
			"onclick" => null,
			"url" => CComponentEngine::makePathFromTemplate($arParams["PATH_TO_TASKS"], array())
		);
	}

	$APPLICATION->IncludeComponent(
		"bitrix:tasks.list.controls",
		".default",
		array_merge(array(
			"USER_ID" => $arParams["USER_ID"],
			"GROUP_ID" => $arParams["GROUP_ID"],
			"SHOW_TASK_LIST_MODES" => "N",
			"SHOW_HELP_ICON" => "N",
			"SHOW_SEARCH_FIELD" => "N",
			"SHOW_TEMPLATES_TOOLBAR" => "Y",
			"SHOW_QUICK_TASK_ADD" => "N",
			"SHOW_ADD_TASK_BUTTON" => "N",
			"SHOW_FILTER_BUTTON" => "N",
			"SHOW_SECTIONS_BAR" => $arParams['TOOLBAR_MODE'] == 'COMPACT' ? "N" : "Y",
			"SHOW_FILTER_BAR" => "N",
			"SHOW_COUNTERS_BAR" => "N",
			"TEMPLATES" => $arResult["AUX_DATA"]["TEMPLATE"],
			"SHOW_SECTION_PROJECTS" => $templateData["TASK_TYPE"] === "group" ? "N" : "Y",
			"SHOW_SECTION_MANAGE" => "A",
			"SHOW_SECTION_COUNTERS" => $templateData["TASK_TYPE"] === "group" ? "N" : "Y",
			"MARK_ACTIVE_ROLE" => "N",
			"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK"],
			"PATH_TO_TASKS_TEMPLATES" => $arParams["PATH_TO_TASKS_TEMPLATES"],
			"SECTION_URL_PREFIX" => CComponentEngine::makePathFromTemplate($arParams["PATH_TO_TASKS"], array()),
			"PATH_TO_PROJECTS" => CComponentEngine::makePathFromTemplate($arParams["PATH_TO_USER_TASKS_PROJECTS_OVERVIEW"], array("user_id" => $arParams["USER_ID"])),
			"CUSTOM_ELEMENTS" => $customElements
		), is_array($arParams["TOOLBAR_PARAMETERS"]) ? $arParams["TOOLBAR_PARAMETERS"] : array()),
		null,
		array("HIDE_ICONS" => "Y")
	);
}
?>

<div class="task-detail">
	<div class="task-detail-info">
		<div class="task-detail-header">
			<?if($can["EDIT"] || $taskData["PRIORITY"] == CTasks::PRIORITY_HIGH):?>
				<div id="task-detail-important-button" class="task-info-panel-important <?if($taskData["PRIORITY"] != CTasks::PRIORITY_HIGH):?>no<?endif?> <?if($can["EDIT"]):?>mutable<?endif?>" data-priority="<?=intval($taskData["PRIORITY"])?>">
					<span class="if-no"><?=Loc::getMessage("TASKS_TASK_COMPONENT_TEMPLATE_MAKE_IMPORTANT")?></span>
					<span class="if-not-no"><?=Loc::getMessage("TASKS_IMPORTANT_TASK")?></span>
				</div>
			<?endif?>
			<?$expired = $taskData["STATUS"] == CTasks::METASTATE_EXPIRED;?>
			<div class="task-detail-subtitle-status <?if($expired):?>task-detail-subtitle-status-delay-message<?endif?>"><?=Loc::getMessage('TASKS_TTV_SUB_TITLE', array(
					'#ID#' => intval($taskData['ID']),
				))?> - <span id="task-detail-status-below-name"><?=\Bitrix\Tasks\UI::toLowerCaseFirst(Loc::getMessage("TASKS_TASK_STATUS_".$taskData["REAL_STATUS"]))?><?if($expired):?></span>, <?=\Bitrix\Tasks\UI::toLowerCaseFirst(Loc::getMessage('TASKS_STATUS_OVERDUE'))?><?endif?>

			</div>
		</div>
		<div class="task-detail-content">
		<? if (!$arParams["PUBLIC_MODE"]):?>
			<div id="task-detail-favorite"
				 class="task-detail-favorite<?if ($taskData["FAVORITE"] === "Y"):?> task-detail-favorite-active<?endif?>"
				 title="<?=Loc::getMessage("TASKS_TASK_ADD_TO_FAVORITES")?>"><div class="task-detail-favorite-star"></div>
			</div>
		<? endif ?>

		<? if (strlen($taskData["DESCRIPTION"])):
			$extraDesc =
				$can["EDIT"] ||
				!empty($taskData["SE_CHECKLIST"]) ||
				(isset($userFields[$diskUfCode]) && !empty($userFields[$diskUfCode]["VALUE"]))
		?>
			<div class="task-detail-description<?if (!$extraDesc):?> task-detail-description-only<?endif?>"
				 id="task-detail-description"><?=$taskData["DESCRIPTION"]?></div>
		<? endif ?>

		<?if ($can["EDIT"] || $can["CHECKLIST.ADD"] || !empty($taskData["SE_CHECKLIST"])):?>
			<div class="task-detail-checklist">
				<?$APPLICATION->IncludeComponent(
					'bitrix:tasks.widget.checklist',
					'',
					array(
						'DATA' => $taskData['SE_CHECKLIST'],
						'CAN_ADD' => $can['CHECKLIST.ADD'],
						'CAN_REORDER' => $can['CHECKLIST.REORDER'],
						'ENTITY_ID' => $taskData['ID'],
						'ENTITY_ROUTE' => 'task',
						'COMPATIBILITY_MODE' => true,
						'ENABLE_SYNC' => true,
						'CONFIRM_DELETE' => true
					),
					null,
					array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
				);?>
			</div>
		<?endif?>

		<?// files\images ?>
		<?if (isset($userFields[$diskUfCode]) && !empty($userFields[$diskUfCode]["VALUE"])):?>
			<div class="task-detail-files" id="task-detail-files">
				<?\Bitrix\Tasks\Util\UserField\UI::showView($userFields[$diskUfCode], array(
					"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
					"ENABLE_AUTO_BINDING_VIEWER" => false // file viewer cannot work in the iframe (see logic.js)
				));?>
			</div>
		<?endif?>

		<? if (!$arParams["PUBLIC_MODE"]):?>
			<div class="task-detail-extra">
				<div class="task-detail-like">
				<?
					$APPLICATION->IncludeComponent(
						"bitrix:rating.vote",
						$arParams["RATING_TYPE"],
						Array(
							"ENTITY_TYPE_ID" => "TASK",
							"ENTITY_ID" => $taskData["ID"],
							"OWNER_ID" => $taskData["CREATED_BY"],
							"USER_VOTE" => $templateData["RATING"]["USER_VOTE"],
							"USER_HAS_VOTED" => $templateData["RATING"]["USER_HAS_VOTED"],
							"TOTAL_VOTES" => $templateData["RATING"]["TOTAL_VOTES"],
							"TOTAL_POSITIVE_VOTES" => $templateData["RATING"]["TOTAL_POSITIVE_VOTES"],
							"TOTAL_NEGATIVE_VOTES" => $templateData["RATING"]["TOTAL_NEGATIVE_VOTES"],
							"TOTAL_VALUE" => $templateData["RATING"]["TOTAL_VALUE"],
							"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
							"PUBLIC_MODE" => $arParams["PUBLIC_MODE"]
						),
						$component,
						array("HIDE_ICONS" => "Y")
					);
				?>
				</div>

				<?if($can["EDIT"] || !empty($templateData["GROUP"])):?>
					<div class="task-detail-group">
						<span class="task-detail-group-label"><?=Loc::getMessage("TASKS_TTDP_PROJECT_TASK_IN")?>:</span>

						<?$APPLICATION->IncludeComponent(
							'bitrix:tasks.widget.member.selector',
							'projectlink',
							array(
								'TYPES' => array('PROJECT'),
								'DATA' => array(
									$templateData["GROUP"]
								),
								'READ_ONLY' => !$can["EDIT"],
								'ENTITY_ID' => $taskData["ID"],
								'ENTITY_ROUTE' => 'task',
								'PATH_TO_GROUP' => $arParams['PATH_TO_GROUP']
							),
							null,
							array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
						);?>

					</div>
				<?endif?>

				<? if (!empty($templateData["RELATED_TASK"])):?>
				<div class="task-detail-supertask"><?
					?><span class="task-detail-supertask-label"><?=Loc::getMessage("TASKS_PARENT_TASK")?>:</span><?
					?><span class="task-detail-supertask-name"><a href="<?=$templateData["RELATED_TASK"]["URL"]?>"
						class="task-detail-group-link"><?=htmlspecialcharsbx($templateData["RELATED_TASK"]["TITLE"])?></a></span>
				</div>
				<? endif ?>

				<?if($arResult["AUX_DATA"]['MAIL']['FORWARD']):?>
					<div class="task-detail-group"><?
						?><span class="task-detail-group-label"><?=Loc::getMessage("TASKS_MAIL_FORWARD")?>:</span><?
						?><span class="task-detail-group-name">
							<?=htmlspecialcharsbx($arResult["AUX_DATA"]['MAIL']['FORWARD'])?>
						</span>
					</div>
				<?endif?>

			</div>
		<? endif ?>

		<? if ($templateData["SHOW_USER_FIELDS"]): ?>
			<div class="task-detail-properties">
				<table cellspacing="0" class="task-detail-properties-layout">
				<?//todo: uf managing form here (as a component)?>
				<?
				foreach ($userFields as $userField)
				{
					if (empty($userField["VALUE"]) || $userField["FIELD_NAME"] === $diskUfCode || !\Bitrix\Tasks\Util\UserField\UI::isSuitable($userField))
					{
						continue;
					}
					?>
					<tr>
						<td class="task-detail-property-name"><?=htmlspecialcharsbx($userField["EDIT_FORM_LABEL"])?></td>
						<td class="task-detail-property-value">
							<?\Bitrix\Tasks\Util\UserField\UI::showView($userField, array(
								"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
								"ENABLE_AUTO_BINDING_VIEWER" => true,
							));?>
						</td>
					</tr><?
				}?>
				</table>
			</div>
		<?endif?>

		</div>
	</div>

	<div class="task-detail-buttons"><?
		$APPLICATION->IncludeComponent(
			"bitrix:tasks.widget.buttons",
			"task",
			array(
				"GROUP_ID" => $arParams["GROUP_ID"],
				"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK"],
				"PATH_TO_TASKS_TASK_COPY" => \Bitrix\Tasks\UI\Task::makeCopyUrl($arParams["PATH_TO_TASKS_TASK"], $taskData["ID"]),
				"PATH_TO_TASKS_TASK_CREATE_SUBTASK" => \Bitrix\Tasks\UI\Task::makeCreateSubtaskUrl($arParams["PATH_TO_TASKS_TASK"], $taskData["ID"]),
				"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
				"PATH_TO_TASKS" => $arParams["PATH_TO_TASKS"],
				"NAME_TEMPLATE" => $templateData["NAME_TEMPLATE"],
				"CAN" => $can,
				"TASK_ID" => $taskData["ID"],
				"TASK" => $taskData,
				"TIMER_IS_RUNNING_FOR_CURRENT_USER" => !!$templateData["TIMER_IS_RUNNING_FOR_CURRENT_USER"],
				"TIMER" => $templateData["TIMER"],
				"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
				"REDIRECT_TO_LIST_ON_DELETE" => $arParams['REDIRECT_TO_LIST_ON_DELETE'],
			),
			null,
			array("HIDE_ICONS" => "Y")
		);
		?>
	</div>
	
	<? if ($templateData["SUBTASKS_EXIST"]):?>
	<div class="task-detail-list">
		<div class="task-detail-list-title"><?=Loc::getMessage("TASKS_TASK_SUBTASKS")?></div>
		<?
		$APPLICATION->IncludeComponent(
			"bitrix:tasks.list", 
			"",
			array(
				"HIDE_VIEWS" => "Y",
				"HIDE_MENU" => "Y",
				"HIDE_GROUP_ACTIONS" => "Y",
				"FORCE_LIST_MODE" => "Y",
				"PREVENT_PAGE_ONE_COLUMN" => "Y",
				"PREVENT_FLEXIBLE_LAYOUT" => ($arResult["IS_IFRAME"] ? "N" : "Y"),
				"COMMON_FILTER" => array(),
				"ORDER" => array("GROUP_ID"  => "ASC"),
				"PREORDER" => array("STATUS_COMPLETE" => "ASC"),
				"FILTER" => array("PARENT_ID" => $taskData["ID"]),
				"VIEW_STATE" => array(),
				"CONTEXT_ID" => CTaskColumnContext::CONTEXT_TASK_DETAIL,
				"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
				"PATH_TO_USER_TASKS" => $arParams["PATH_TO_USER_TASKS"],
				"PATH_TO_USER_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK"],
				"TASKS_ALWAYS_EXPANDED" => "Y",
				"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
				'SET_TITLE' => 'N',
			),
			null,
			array("HIDE_ICONS" => "Y")
		);
		?>
	</div>
	<? endif ?>

	<? if (count($templateData["PREDECESSORS"])):?>

		<div class="task-detail-list">
			<div class="task-detail-list-title"><?=Loc::getMessage("TASKS_TASK_PREDECESSORS")?></div>
			<?$APPLICATION->IncludeComponent(
				'bitrix:tasks.widget.related.selector',
				'staticgrid',
				array(
					'DATA' => $templateData["PREDECESSORS"],
					'USERS' => $arResult['DATA']['USER'],
					'TYPES' => array('TASK'),
					'PATH_TO_TASKS_TASK' => $arParams['PATH_TO_TASKS_TASK_WO_GROUP'],
					'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
					'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
					'COLUMNS' => array(
						'TITLE', 'START_DATE_PLAN', 'END_DATE_PLAN',
						'DEPENDENCY_TYPE' => array(
							'SOURCE' => 'DEPENDENCY_TYPE',
							'TITLE' => Loc::getMessage('TASKS_DEPENDENCY_TYPE')
						)
					),
				),
				null,
				array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
			);?>
		</div>

	<? endif ?>


	<? if (count($templateData["PREV_TASKS"])):?>

		<div class="task-detail-list">
			<div class="task-detail-list-title"><?=Loc::getMessage("TASKS_TASK_LINKED_TASKS")?></div>
			<?$APPLICATION->IncludeComponent(
				'bitrix:tasks.widget.related.selector',
				'staticgrid',
				array(
					'DATA' => $templateData["PREV_TASKS"],
					'USERS' => $arResult['DATA']['USER'],
					'TYPES' => array('TASK'),
					'PATH_TO_TASKS_TASK' => $arParams['PATH_TO_TASKS_TASK_WO_GROUP'],
					'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
					'NAME_TEMPLATE' => $arParams['NAME_TEMPLATE'],
				),
				null,
				array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
			);?>
		</div>

	<? endif ?>

	<div class="task-detail-comments">
		<div class="task-comments-and-log">
			<div class="task-comments-log-switcher" id="task-switcher">
				<span class="task-switcher task-switcher-selected" id="task-comments-switcher">
					<span class="task-switcher-text">
						<span class="task-switcher-text-inner">
							<?=Loc::getMessage("TASKS_TASK_COMMENTS")?>
							<span class="task-switcher-text-counter"><?=$taskData["COMMENTS_COUNT"]?></span>
						</span>
					</span>
				</span>
				<span class="task-switcher" id="task-log-switcher">
					<span class="task-switcher-text">
						<span class="task-switcher-text-inner">
							<?=Loc::getMessage("TASKS_TASK_LOG_SHORT")?>
							<span class="task-switcher-text-counter" id="task-switcher-text-log-count"><?=count($taskData["SE_LOG"])?></span>
						</span>
					</span>
				</span>
				<span class="task-switcher" id="task-time-switcher">
					<span class="task-switcher-text">
						<span class="task-switcher-text-inner">
							<?=Loc::getMessage("TASKS_ELAPSED_TIME_SHORT")?>
							<span class="task-switcher-text-counter" id="task-switcher-elapsed-time"><?=\Bitrix\Tasks\UI::formatTimeAmount($templateData["ELAPSED"]['TIME'])?></span>
						</span>
					</span>
				</span>
				<? if ($templateData["FILES_IN_COMMENTS"] > 0): ?>
				<span class="task-switcher" id="task-files-switcher">
					<span class="task-switcher-text">
						<span class="task-switcher-text-inner"><?=Loc::getMessage("TASKS_FILES_FROM_COMMENTS")?>
							<span class="task-switcher-text-counter"><?=$templateData["FILES_IN_COMMENTS"]?></span>
						</span>
					</span>
				</span>
				<? endif ?>
			</div>

			<div class="task-switcher-block task-comments-block task-switcher-block-selected" id="task-comments-block"><?
				if (intval($taskData["FORUM_ID"]))
				{
					$APPLICATION->IncludeComponent(
						"bitrix:forum.comments",
						"bitrix24",
						array(
							"FORUM_ID" => $taskData["FORUM_ID"],
							"ENTITY_TYPE" => "TK",
							"ENTITY_ID" => $taskData["ID"],
							"ENTITY_XML_ID" => "TASK_".$taskData["ID"],
							"URL_TEMPLATES_PROFILE_VIEW" => $arParams["PATH_TO_USER_PROFILE"],
							"CACHE_TYPE" => $arParams["CACHE_TYPE"],
							"CACHE_TIME" => $arParams["CACHE_TIME"],
							"IMAGE_HTML_SIZE" => 400,
							"MESSAGES_PER_PAGE" => $arParams["ITEM_DETAIL_COUNT"],
							"PAGE_NAVIGATION_TEMPLATE" => "arrows",
							"DATE_TIME_FORMAT" => \Bitrix\Tasks\UI::getDateTimeFormat(),
							"PATH_TO_SMILE" => $arParams["PATH_TO_FORUM_SMILE"],
							"EDITOR_CODE_DEFAULT" => "N",
							"SHOW_MODERATION" => "Y",
							"SHOW_AVATAR" => "Y",
							"SHOW_RATING" => $arParams["SHOW_RATING"],
							"RATING_TYPE" => $arParams["RATING_TYPE"],
							"SHOW_MINIMIZED" => "N",
							"USE_CAPTCHA" => "N",
							"PREORDER" => "N",
							"SHOW_LINK_TO_FORUM" => "N",
							"SHOW_SUBSCRIBE" => "N",
							"FILES_COUNT" => 10,
							"SHOW_WYSIWYG_EDITOR" => "Y",
							"BIND_VIEWER" => "N", // Viewer cannot work in the iframe (see logic.js)
							"AUTOSAVE" => true,
							"PERMISSION" => "M", //User already have access to task, so user have access to read/create comments
							"NAME_TEMPLATE" => $templateData["NAME_TEMPLATE"],
							"MESSAGE_COUNT" => 3,
							"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
							"ALLOW_MENTION" => $arParams["PUBLIC_MODE"] ? "N" : "Y",
							"USER_FIELDS_SETTINGS" =>
								$arParams["PUBLIC_MODE"]
								? array(
									"UF_FORUM_MESSAGE_DOC" => array(
										"DISABLE_CREATING_FILE_BY_CLOUD" => true,
										"DISABLE_LOCAL_EDIT" => true
									)
								)
								: array()
						),
						($component->__parent ? $component->__parent : $component),
						array("HIDE_ICONS" => "Y")
					);
				}
				?>
			</div>

			<div class="task-switcher-block task-log-block" id="task-log-block"><?
				$APPLICATION->IncludeComponent(
					"bitrix:tasks.task.detail.parts",
					"flat",
					array(
						"MODE" => "VIEW TASK",
						"BLOCKS" => array("log"),
						"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK"],
						"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
						"PATH_TO_GROUP" => $arParams["PATH_TO_GROUP"],
						"NAME_TEMPLATE" => $templateData["NAME_TEMPLATE"],
						"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
						"TEMPLATE_DATA" => array(
							"DATA" => $arResult["DATA"],
						)
					),
					false,
					array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
				);?>
			</div>

			<div class="task-switcher-block task-time-block" id="task-time-block"><?
				$APPLICATION->IncludeComponent(
					"bitrix:tasks.task.detail.parts",
					"flat",
					array(
						"MODE" => "VIEW TASK",
						"BLOCKS" => array("time"),
						"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK"],
						"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
						"PATH_TO_GROUP" => $arParams["PATH_TO_GROUP"],
						"NAME_TEMPLATE" => $templateData["NAME_TEMPLATE"],
						"TASK_ID" => $taskData["ID"],
						"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
						"TEMPLATE_DATA" => array(
							"DATA" => $arResult["DATA"],
							"CAN" => $arResult["CAN"],
						)
					),
					false,
					array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y")
				);?>
			</div>

			<? if ($templateData["FILES_IN_COMMENTS"] > 0): ?>
			<div class="task-switcher-block task-files-block" id="task-files-block"><?
				$APPLICATION->IncludeComponent(
					"bitrix:disk.uf.comments.attached.objects",
					".default",
					array(
						"MAIN_ENTITY" => array(
							"ID" => $taskData["ID"]
						),
						"COMMENTS_MODE" => "forum",
						"ENABLE_AUTO_BINDING_VIEWER" => false, // Viewer cannot work in the iframe (see logic.js)
						"DISABLE_LOCAL_EDIT" => $arParams["PUBLIC_MODE"],
						"COMMENTS_DATA" => array(
							"TOPIC_ID" => $taskData["FORUM_TOPIC_ID"],
							"FORUM_ID" => $taskData["FORUM_ID"],
							"XML_ID" => "TASK_".$taskData["ID"]
						),
						"PUBLIC_MODE" => $arParams["PUBLIC_MODE"]
					),
					false,
					array("HIDE_ICONS" => "Y", "ACTIVE_COMPONENT" => "Y"));
				?>
			</div>
			<? endif ?>
		</div>
	</div>
</div>


<?
$this->SetViewTarget("sidebar", 100);
$APPLICATION->IncludeComponent(
	"bitrix:tasks.task.detail.parts",
	"flat",
	array(
		"MODE" => "VIEW TASK",
		"BLOCKS" => array("sidebar"),
		"GROUP_ID" => $arParams["GROUP_ID"],
		"PATH_TO_TASKS" => $arParams["PATH_TO_TASKS"],
		"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK"],
		"PATH_TO_USER_PROFILE" => $arParams["PATH_TO_USER_PROFILE"],
		"PATH_TO_GROUP" => $arParams["PATH_TO_GROUP"],
		"PATH_TO_TEMPLATES_TEMPLATE" => $arParams["PATH_TO_TEMPLATES_TEMPLATE"],
		"NAME_TEMPLATE" => $templateData["NAME_TEMPLATE"],
		"TASK_ID" => $taskData["ID"],
		"PUBLIC_MODE" => $arParams["PUBLIC_MODE"],
		"USER" => $arResult['DATA']['USER'][\Bitrix\Tasks\Util\User::getId()],
		"TEMPLATE_DATA" => array(
			"DATA" => $arResult["DATA"],
			"AUX_DATA" => $arResult["AUX_DATA"],
			"TIMER_IS_RUNNING_FOR_CURRENT_USER" => $templateData["TIMER_IS_RUNNING_FOR_CURRENT_USER"],
			"TIMER" => $templateData["TIMER"]
		),
		"SHOW_COPY_URL_LINK" => $arParams['SHOW_COPY_URL_LINK'],
	),
	null,
	array("HIDE_ICONS" => "Y")
);

$this->EndViewTarget();
?>

<script>

	<?/*todo: move php function tasksRenderJSON() to javascript, use CUtil::PhpToJSObject() here for EVENT_TASK, and then remove the following code*/?>
	<?if (is_array($arResult["DATA"]["EVENT_TASK"])):?>
		<?CJSCore::Init("CJSTask"); // ONLY to make BX.CJSTask.fixWin() available?>
		var eventTaskUgly = <?tasksRenderJSON(
			$arResult["DATA"]["EVENT_TASK_SAFE"],
			intval($arResult["DATA"]["EVENT_TASK"]["CHILDREN_COUNT"]),
			array(
				"PATH_TO_TASKS_TASK" => $arParams["PATH_TO_TASKS_TASK_ORIGINAL"]
			),
			true,
			true,
			true,
			CSite::GetNameFormat(false)
		)?>;
	<?else:?>
		var eventTaskUgly = null;
	<?endif?>

	new BX.Tasks.Component.TaskView({
		messages: {
			addTask: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_ADD_TASK"))?>",
			addSubTask: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_ADD_SUBTASK_2"))?>",

			//Need for sidebar ajax update
			TASKS_STATUS_1: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_STATUS_1"))?>",
			TASKS_STATUS_2: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_STATUS_2"))?>",
			TASKS_STATUS_3: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_STATUS_3"))?>",
			TASKS_STATUS_4: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_STATUS_4"))?>",
			TASKS_STATUS_5: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_STATUS_5"))?>",
			TASKS_STATUS_6: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_STATUS_6"))?>",
			TASKS_STATUS_7: "<?=CUtil::JSEscape(Loc::getMessage("TASKS_STATUS_7"))?>"
		},
		paths: {
			newTask: "<?=CUtil::JSEscape($templateData["NEW_TASK_PATH"])?>",
			newSubTask: "<?=CUtil::JSEscape($templateData["NEW_SUBTASK_PATH"])?>"
		},
		taskId: <?=$taskData["ID"]?>,
		project: <?=CUtil::PhpToJSObject($taskData["SE_PROJECT"])?>,
		eventTaskUgly: eventTaskUgly,
		componentData: {
			EVENT_TYPE: "<?=CUtil::JSEscape($arResult["COMPONENT_DATA"]["EVENT_TYPE"])?>",
			EVENT_OPTIONS: <?=CUtil::PhpToJsObject($arResult["COMPONENT_DATA"]["EVENT_OPTIONS"])?>
		},
		can: {TASK: <?=CUtil::PhpToJsObject($arResult['CAN']['TASK'])?>}
	});

    if (window.B24) {
        B24.updateCounters({"tasks_total": <?=(int)CUserCounter::GetValue($USER->GetID(), 'tasks_total')?>});
    }
</script>