<? if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>

<? $activity = $arParams['ACTIVITY']; ?>
<? $actDescr = $arParams['~ACTIVITY']['DESCRIPTION_HTML']; ?>

<div class="crm-task-list-mail">
	<div class="crm-task-list-mail-header">
		<span class="crm-task-list-mail-header-title"><?=$activity['SUBJECT'] ?></span>
		<!-- total -->
	</div>

	<div class="crm-task-list-mail-item-separator">
		<? if (count($arResult['LOG']['A']) >= $arParams['PAGE_SIZE']): ?>
			<a class="crm-task-list-mail-more crm-task-list-mail-more-a" href="#"><?=getMessage('CRM_ACT_EMAIL_HISTORY_MORE') ?></a>
		<? endif ?>
	</div>

	<? if (!empty($arResult['LOG']['A'])): ?>
		<? $separator = false; ?>
		<? foreach ($arResult['LOG']['A'] as $item): ?>
			<? if ($separator): ?>
				<div class="crm-task-list-mail-item-separator" style="display: none; "></div>
			<? endif ?>
			<div class="crm-task-list-mail-item" id="crm-activity-email-log-<?=intval($item['ID']) ?>" data-id="<?=intval($item['ID']) ?>">
				<span class="crm-task-list-mail-item-icon-reply-<?=($item['DIRECTION'] == \CCrmActivityDirection::Incoming ? 'incoming' : 'coming') ?>"></span>
				<span class="crm-task-list-mail-item-icon <? if ($item['COMPLETED'] != 'Y'): ?>active-mail<? endif ?>"></span>
				<span class="crm-task-list-mail-item-user"
					<? if (!empty($item['LOG_IMAGE'])): ?>style="background: url('<?=$item['LOG_IMAGE'] ?>'); background-size: 23px 23px; "<? endif ?>>
					</span>
				<span class="crm-task-list-mail-item-name"><?=$item['LOG_TITLE'] ?></span>
				<span class="crm-task-list-mail-item-description"><?=$item['SUBJECT'] ?></span>
				<span class="crm-task-list-mail-item-date"><?=formatDate('x', makeTimeStamp($item['START_TIME']), time()+\CTimeZone::getOffset()) ?></span>
			</div>
			<div class="crm-task-list-mail-item-inner crm-activity-email-details-<?=intval($item['ID']) ?>"
				style="display: none; text-align: center; " data-empty="1">
				<span class="crm-task-list-mail-item-loading"></span>
			</div>
			<? $separator = true; ?>
		<? endforeach ?>
		<div class="crm-task-list-mail-item-separator"></div>
	<? endif ?>

	<div class="crm-task-list-mail-item-inner" id="crm-activity-email-details-<?=intval($activity['ID']) ?>">
		<div class="crm-task-list-mail-item-inner-header">
			<span class="crm-task-list-mail-item-inner-user"
				<? if (!empty($activity['ITEM_IMAGE'])): ?>style="background: url('<?=$activity['ITEM_IMAGE'] ?>'); background-size: 40px 40px; "<? endif ?>>
				</span>
			<span class="crm-task-list-mail-item-inner-user-container">
				<span class="crm-task-list-mail-item-inner-user-info">
					<span class="crm-task-list-mail-item-inner-user-title"><?=$activity['ITEM_FROM_TITLE'] ?></span>
					<? if (!empty($activity['ITEM_FROM_EMAIL'])): ?>
						<span class="crm-task-list-mail-item-inner-user-mail"><?=$activity['ITEM_FROM_EMAIL'] ?></span>
					<? endif ?>
					<div class="crm-task-list-mail-item-inner-send">
						<span class="crm-task-list-mail-item-inner-send-item"><?=getMessage('CRM_ACT_EMAIL_RCPT') ?>:</span>
						<? foreach ($activity['ITEM_TO'] as $item): ?>
							<span class="crm-task-list-mail-item-inner-send-user"
								<? if (!empty($item['IMAGE'])): ?>style="background: url('<?=$item['IMAGE'] ?>'); background-size: 23px 23px; "<? endif ?>>
								</span>
							<span class="crm-task-list-mail-item-inner-send-mail"><?=$item['TITLE'] ?></span>
						<? endforeach ?>
					</div>
				</span>
			</span>
		</div>
		<div class="crm-task-list-mail-item-inner-body"><?=$actDescr ?></div>
	</div>

	<? if (!empty($arResult['LOG']['B'])): ?>
		<? $separator = false; ?>
		<div class="crm-task-list-mail-item-separator"></div>
		<? foreach ($arResult['LOG']['B'] as $item): ?>
			<? if ($separator): ?>
				<div class="crm-task-list-mail-item-separator" style="display: none; "></div>
			<? endif ?>
			<div class="crm-task-list-mail-item" id="crm-activity-email-log-<?=intval($item['ID']) ?>" data-id="<?=intval($item['ID']) ?>">
				<span class="crm-task-list-mail-item-icon-reply-<?=($item['DIRECTION'] == \CCrmActivityDirection::Incoming ? 'incoming' : 'coming') ?>"></span>
				<span class="crm-task-list-mail-item-icon <? if ($item['COMPLETED'] != 'Y'): ?>active-mail<? endif ?>"></span>
				<span class="crm-task-list-mail-item-user"
					<? if (!empty($item['LOG_IMAGE'])): ?>style="background: url('<?=$item['LOG_IMAGE'] ?>'); background-size: 23px 23px; "<? endif ?>>
					</span>
				<span class="crm-task-list-mail-item-name"><?=$item['LOG_TITLE'] ?></span>
				<span class="crm-task-list-mail-item-description"><?=$item['SUBJECT'] ?></span>
				<span class="crm-task-list-mail-item-date"><?=formatDate('x', makeTimeStamp($item['START_TIME']), time()+\CTimeZone::getOffset()) ?></span>
			</div>
			<div class="crm-task-list-mail-item-inner crm-activity-email-details-<?=intval($item['ID']) ?>"
				style="display: none; text-align: center; " data-empty="1">
				<span class="crm-task-list-mail-item-loading"></span>
			</div>
			<? $separator = true; ?>
		<? endforeach ?>
	<? endif ?>

	<? if (count($arResult['LOG']['B']) >= $arParams['PAGE_SIZE']): ?>
		<div class="crm-task-list-mail-item-separator">
			<a class="crm-task-list-mail-more crm-task-list-mail-more-b" href="#"><?=getMessage('CRM_ACT_EMAIL_HISTORY_MORE') ?></a>
		</div>
	<? endif ?>

</div>

<script type="text/javascript">

	BX.ready(function() {

		new CrmActivityEmailView(
			<?=intval($activity['ID']) ?>,
			{
				'ajaxUrl': '<?=$this->__component->getPath() ?>/ajax.php?site_id=<?=SITE_ID ?>',
				'pageSize': <?=intval($arParams['PAGE_SIZE']) ?>
			}
		);

	});

</script>
