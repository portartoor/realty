<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
CUtil::InitJSCore(array('socnetlogdest', 'date', 'file_dialog'));
/** @var array $arResult */

$titleView = $arResult['ENTITY'] ? htmlspecialcharsbx(GetMessage('CRM_AUTOMATION_CMP_TITLE_'.$arResult['ENTITY_TYPE_NAME'].'_VIEW', array(
		'#TITLE#' => $arResult['ENTITY']['TITLE']
))) : '&nbsp;';
$titleEdit = htmlspecialcharsbx(GetMessage('CRM_AUTOMATION_CMP_TITLE_'.$arResult['ENTITY_TYPE_NAME'].'_EDIT'));

?>
<div class="automation-base" data-role="automation-base-node">
		<div class="automation-base-node-top">
			<div class="automation-base-node-title"
				data-role="automation-title"
				data-title-view="<?=$titleView?>"
				data-title-edit="<?=$titleEdit?>">
			</div>
			<?if ($arResult['CAN_EDIT']):?>
			<span class="crm-automation-edit-robots" data-role="automation-btn-change-view"
				data-label-view="<?=GetMessage('CRM_AUTOMATION_CMP_VIEW')?>" data-label-edit="<?=GetMessage('CRM_AUTOMATION_CMP_AUTOMATION_EDIT')?>">
			</span>
			<?endif?>
		</div>
	<div class="automation-base-node">
		<div class="crm-automation-status">
			<div class="crm-automation-status-list">
				<? foreach ($arResult['STATUSES'] as $statusId => $status):
					$color = htmlspecialcharsbx($status['COLOR'] ? str_replace('#','',$status['COLOR']) : 'acf2fa');
				?>
				<div class="crm-automation-status-list-item">
					<div class="crm-automation-status-title" data-role="automation-status-title" data-bgcolor="<?=$color?>">
						<?=htmlspecialcharsbx($status['NAME'])?>
					</div>
					<div class="crm-automation-status-bg" style="background-color: <?='#'.$color?>">
						<span class="crm-automation-status-title-right" style="background-image: url(data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%20width%3D%2213%22%20height%3D%2232%22%20viewBox%3D%220%200%2013%2032%22%3E%3Cpath%20fill%3D%22%23<?=$color?>%22%20fill-rule%3D%22evenodd%22%20d%3D%22M0%200h3c2.8%200%204%203%204%203l6%2013-6%2013s-1.06%203-4%203H0V0z%22/%3E%3C/svg%3E)"></span>
					</div>
				</div>
				<?endforeach;?>
				<a href="<?=htmlspecialcharsbx($arResult['STATUSES_EDIT_URL'])?>"
					class="crm-automation-status-list-config"
				   <?if ($arResult['FRAME_MODE']):?>target="_blank"<?endif;?>
				></a>
			</div>
		</div>

		<!-- triggers -->
		<div class="crm-automation-status">
			<div class="crm-automation-status-name">
				<span class="crm-automation-status-name-bg"><?=GetMessage('CRM_AUTOMATION_CMP_TRIGGER_LIST')?>
					<span class="crm-automation-status-help" data-role="automation-help-tips" data-text="<?=GetMessage('CRM_AUTOMATION_CMP_TRIGGER_HELP')?>">?</span>
				</span>
			</div>
			<div class="crm-automation-status-list">
			<? foreach ($arResult['STATUSES'] as $statusId => $statusName):?>
				<div class="crm-automation-status-list-item" data-type="column-trigger">
					<div data-role="trigger-list" class="crm-automation-trigger-list" data-status-id="<?=htmlspecialcharsbx($statusId)?>"></div>
					<div data-role="trigger-buttons" data-status-id="<?=htmlspecialcharsbx($statusId)?>" class="crm-automation-robot-btn-block"></div>
				</div>
			<?endforeach;?>
			</div>
		</div>

		<!-- robots -->
		<div class="crm-automation-status">
			<div class="crm-automation-status-name">
				<span class="crm-automation-status-name-bg"><?=GetMessage('CRM_AUTOMATION_CMP_ROBOT_LIST')?>
					<span class="crm-automation-status-help" data-role="automation-help-tips" data-text="<?=GetMessage('CRM_AUTOMATION_CMP_ROBOT_HELP')?>">?</span>
				</span>
			</div>
			<div class="crm-automation-status-list">
				<? foreach ($arResult['STATUSES'] as $statusId => $statusName):?>
					<div class="crm-automation-status-list-item" data-type="column-robot" data-role="automation-template" data-status-id="<?=htmlspecialcharsbx($statusId)?>">
						<div data-role="robot-list" class="crm-automation-robot-list" data-status-id="<?=htmlspecialcharsbx($statusId)?>"></div>
						<div data-role="buttons" class="crm-automation-robot-btn-block"></div>
					</div>
				<?endforeach;?>
			</div>
		</div>
	</div>

	<div class="crm-automation-buttons crm-automation-buttons-fixed" data-role="automation-buttons">
		<span class="webform-small-button webform-small-button-accept" data-role="automation-btn-save">
			<?=GetMessage('CRM_AUTOMATION_CMP_SAVE')?>
		</span>
		<span class="webform-small-button webform-small-button-cancel" data-role="automation-btn-cancel">
			<?=GetMessage('CRM_AUTOMATION_CMP_CANCEL')?>
		</span>
	</div>
	<div hidden style="display: none"><?php //init html editor
		$htmlEditor = new CHTMLEditor;
		$htmlEditor->show(array());
	?>
</div>

<script>
	BX.ready(function()
	{
		BX.namespace('BX.Crm.Automation');
		if (typeof BX.Crm.Automation.Component === 'undefined')
			return;

		var baseNode = document.querySelector('[data-role="automation-base-node"]');
		if (baseNode)
		{
			BX.message({
				CRM_AUTOMATION_CMP_ADD: '<?=GetMessageJS('CRM_AUTOMATION_CMP_ADD')?>',
				CRM_AUTOMATION_CMP_EXTERNAL_EDIT: '<?=GetMessageJS('CRM_AUTOMATION_CMP_EXTERNAL_EDIT')?>',
				CRM_AUTOMATION_CMP_TO_EXECUTE: '<?=GetMessageJS('CRM_AUTOMATION_CMP_TO_EXECUTE')?>',
				CRM_AUTOMATION_CMP_AT_ONCE: '<?=GetMessageJS('CRM_AUTOMATION_CMP_AT_ONCE')?>',
				CRM_AUTOMATION_CMP_TO: '<?=GetMessageJS('CRM_AUTOMATION_CMP_TO')?>',
				CRM_AUTOMATION_CMP_AUTOMATICALLY: '<?=GetMessageJS('CRM_AUTOMATION_CMP_AUTOMATICALLY')?>',
				CRM_AUTOMATION_CMP_RESPONSIBLE: '<?=GetMessageJS('CRM_AUTOMATION_CMP_RESPONSIBLE')?>',
				CRM_AUTOMATION_CMP_THROUGH: '<?=GetMessageJS('CRM_AUTOMATION_CMP_THROUGH')?>',
				CRM_AUTOMATION_CMP_AFTER_PREVIOUS: '<?=GetMessageJS('CRM_AUTOMATION_CMP_AFTER_PREVIOUS')?>',
				CRM_AUTOMATION_CMP_AFTER_PREVIOUS_WIDE: '<?=GetMessageJS('CRM_AUTOMATION_CMP_AFTER_PREVIOUS_WIDE')?>',
				CRM_AUTOMATION_CMP_EDIT: '<?=GetMessageJS('CRM_AUTOMATION_CMP_EDIT')?>',
				CRM_AUTOMATION_CMP_CHOOSE_TIME: '<?=GetMessageJS('CRM_AUTOMATION_CMP_CHOOSE_TIME')?>',
				CRM_AUTOMATION_CMP_INTERVAL_M: '<?=GetMessageJS('CRM_AUTOMATION_CMP_INTERVAL_M')?>',
				CRM_AUTOMATION_CMP_INTERVAL_H: '<?=GetMessageJS('CRM_AUTOMATION_CMP_INTERVAL_H')?>',
				CRM_AUTOMATION_CMP_INTERVAL_D: '<?=GetMessageJS('CRM_AUTOMATION_CMP_INTERVAL_D')?>',
				CRM_AUTOMATION_CMP_BEFORE_DATE: '<?=GetMessageJS('CRM_AUTOMATION_CMP_BEFORE_DATE')?>',
				CRM_AUTOMATION_CMP_BEFORE: '<?=GetMessageJS('CRM_AUTOMATION_CMP_BEFORE')?>',
				CRM_AUTOMATION_CMP_CHOOSE_DATE_FIELD: '<?=GetMessageJS('CRM_AUTOMATION_CMP_CHOOSE_DATE_FIELD')?>',
				CRM_AUTOMATION_CMP_FOR_TIME: '<?=GetMessageJS('CRM_AUTOMATION_CMP_FOR_TIME')?>',
				CRM_AUTOMATION_CMP_CHOOSE: '<?=GetMessageJS('CRM_AUTOMATION_CMP_CHOOSE')?>',
				CRM_AUTOMATION_CMP_EXTERNAL_EDIT_TEXT: '<?=GetMessageJS('CRM_AUTOMATION_CMP_EXTERNAL_EDIT_TEXT')?>',
				CRM_AUTOMATION_CMP_NEED_SAVE: '<?=GetMessageJS('CRM_AUTOMATION_CMP_NEED_SAVE')?>',
				CRM_AUTOMATION_CMP_DAY1: '<?=GetMessageJS('CRM_AUTOMATION_CMP_DAY1')?>',
				CRM_AUTOMATION_CMP_DAY2: '<?=GetMessageJS('CRM_AUTOMATION_CMP_DAY2')?>',
				CRM_AUTOMATION_CMP_DAY3: '<?=GetMessageJS('CRM_AUTOMATION_CMP_DAY3')?>',
				CRM_AUTOMATION_CMP_HOUR1: '<?=GetMessageJS('CRM_AUTOMATION_CMP_HOUR1')?>',
				CRM_AUTOMATION_CMP_HOUR2: '<?=GetMessageJS('CRM_AUTOMATION_CMP_HOUR2')?>',
				CRM_AUTOMATION_CMP_HOUR3: '<?=GetMessageJS('CRM_AUTOMATION_CMP_HOUR3')?>',
				CRM_AUTOMATION_CMP_MIN1: '<?=GetMessageJS('CRM_AUTOMATION_CMP_MIN1')?>',
				CRM_AUTOMATION_CMP_MIN2: '<?=GetMessageJS('CRM_AUTOMATION_CMP_MIN2')?>',
				CRM_AUTOMATION_CMP_MIN3: '<?=GetMessageJS('CRM_AUTOMATION_CMP_MIN3')?>',
				CRM_AUTOMATION_CMP_TRIGGER_NAME: '<?=GetMessageJS('CRM_AUTOMATION_CMP_TRIGGER_NAME')?>',
				CRM_AUTOMATION_CMP_AUTOMATION_EDIT: '<?=GetMessageJS('CRM_AUTOMATION_CMP_AUTOMATION_EDIT')?>',
				CRM_AUTOMATION_CMP_USER_SELECTOR_TAB: '<?=GetMessageJS('CRM_AUTOMATION_CMP_USER_SELECTOR_TAB')?>',
				CRM_AUTOMATION_CMP_WEBHOOK_ID: '<?=GetMessageJS('CRM_AUTOMATION_CMP_WEBHOOK_ID')?>',
				CRM_AUTOMATION_CMP_DELAY_AFTER_HELP: '<?=GetMessageJS('CRM_AUTOMATION_CMP_DELAY_AFTER_HELP')?>',
				CRM_AUTOMATION_CMP_DELAY_BEFORE_HELP: '<?=GetMessageJS('CRM_AUTOMATION_CMP_DELAY_BEFORE_HELP')?>',
				CRM_AUTOMATION_CMP_IS_DEMO: '<?=GetMessageJS('CRM_AUTOMATION_CMP_IS_DEMO')?>',
				CRM_AUTOMATION_TRIGGER_WEBFORM_LABEL: '<?=GetMessageJS('CRM_AUTOMATION_TRIGGER_WEBFORM_LABEL')?>',
				CRM_AUTOMATION_TRIGGER_WEBFORM_ANY: '<?=GetMessageJS('CRM_AUTOMATION_TRIGGER_WEBFORM_ANY')?>',
				CRM_AUTOMATION_TRIGGER_OPENLINE_LABEL: '<?=GetMessageJS('CRM_AUTOMATION_TRIGGER_OPENLINE_LABEL')?>',
				CRM_AUTOMATION_ROBOT_CATEGORY_EMPLOYEE: '<?=GetMessageJS('CRM_AUTOMATION_ROBOT_CATEGORY_EMPLOYEE')?>',
				CRM_AUTOMATION_ROBOT_CATEGORY_CLIENT: '<?=GetMessageJS('CRM_AUTOMATION_ROBOT_CATEGORY_CLIENT')?>',
				CRM_AUTOMATION_ROBOT_CATEGORY_ADS: '<?=GetMessageJS('CRM_AUTOMATION_ROBOT_CATEGORY_ADS')?>',
				CRM_AUTOMATION_ROBOT_CATEGORY_OTHER: '<?=GetMessageJS('CRM_AUTOMATION_ROBOT_CATEGORY_OTHER')?>',
				CRM_AUTOMATION_ROBOT_CATEGORY_OTHER_MARKETPLACE: '<?=GetMessageJS('CRM_AUTOMATION_ROBOT_CATEGORY_OTHER_MARKETPLACE')?>',
				CRM_AUTOMATION_ROBOT_CONDITION: '<?=GetMessageJS('CRM_AUTOMATION_ROBOT_CONDITION')?>',
				CRM_AUTOMATION_ROBOT_CONDITION_EMPTY: '<?=GetMessageJS('CRM_AUTOMATION_ROBOT_CONDITION_EMPTY')?>',
				CRM_AUTOMATION_ROBOT_CONDITION_EQ: '<?=GetMessageJS('CRM_AUTOMATION_ROBOT_CONDITION_EQ')?>',
				CRM_AUTOMATION_ROBOT_CONDITION_GT: '<?=GetMessageJS('CRM_AUTOMATION_ROBOT_CONDITION_GT')?>',
				CRM_AUTOMATION_ROBOT_CONDITION_GTE: '<?=GetMessageJS('CRM_AUTOMATION_ROBOT_CONDITION_GTE')?>',
				CRM_AUTOMATION_ROBOT_CONDITION_LT: '<?=GetMessageJS('CRM_AUTOMATION_ROBOT_CONDITION_LT')?>',
				CRM_AUTOMATION_ROBOT_CONDITION_LTE: '<?=GetMessageJS('CRM_AUTOMATION_ROBOT_CONDITION_LTE')?>',
				CRM_AUTOMATION_ROBOT_CONDITION_NE: '<?=GetMessageJS('CRM_AUTOMATION_ROBOT_CONDITION_NE')?>',
				CRM_AUTOMATION_ROBOT_CONDITION_IN: '<?=GetMessageJS('CRM_AUTOMATION_ROBOT_CONDITION_IN')?>',
				CRM_AUTOMATION_ROBOT_CONDITION_CONTAIN: '<?=GetMessageJS('CRM_AUTOMATION_ROBOT_CONDITION_CONTAIN')?>',
				CRM_AUTOMATION_CMP_BY_CONDITION: '<?=GetMessageJS('CRM_AUTOMATION_CMP_BY_CONDITION')?>',
				CRM_AUTOMATION_YES: '<?=GetMessageJS('MAIN_YES')?>',
				CRM_AUTOMATION_NO: '<?=GetMessageJS('MAIN_NO')?>',
				CRM_AUTOMATION_CMP_SELECT_DISK_FILE: '<?=GetMessageJS('CRM_AUTOMATION_CMP_SELECT_DISK_FILE')?>'
			});

			var viewMode = BX.Crm.Automation.Component.ViewMode.View;
			if (window.location.hash === '#edit')
			{
				viewMode = BX.Crm.Automation.Component.ViewMode.Edit;
			}

			(new BX.Crm.Automation.Component(baseNode))
				.init(<?=\Bitrix\Main\Web\Json::encode(array(
					'CAN_EDIT' => $arResult['CAN_EDIT'],
					'ENTITY_TYPE_ID' => $arResult['ENTITY_TYPE_ID'],
					'ENTITY_ID' => $arResult['ENTITY_ID'],
					'ENTITY_CATEGORY_ID' => $arResult['ENTITY_CATEGORY_ID'],
					'ENTITY_STATUS' => $arResult['ENTITY_STATUS'],
					'ENTITY_STATUSES' => array_values($arResult['STATUSES']),
					'ENTITY_FIELDS' => $arResult['ENTITY_FIELDS'],
					'LOG' => $arResult['LOG'],
					'AJAX_URL' => '/bitrix/components/bitrix/crm.automation/ajax.php',
					'BIZPROC_EDITOR_URL' => $arResult['BIZPROC_EDITOR_URL'],
					'TRIGGERS' => $arResult['TRIGGERS'],
					'TEMPLATES' => $arResult['TEMPLATES'],
					'AVAILABLE_ROBOTS' => $arResult['AVAILABLE_ROBOTS'],
					'AVAILABLE_TRIGGERS' => $arResult['AVAILABLE_TRIGGERS'],
					'B24_TARIF_ZONE' => $arResult['B24_TARIF_ZONE'],
					'USER_OPTIONS' => $arResult['USER_OPTIONS'],
					'FRAME_MODE' => $arResult['FRAME_MODE']
				))?>, viewMode);
		}
	});
</script>