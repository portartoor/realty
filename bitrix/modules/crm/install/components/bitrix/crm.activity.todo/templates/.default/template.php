<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc;

if ($arParams['IS_AJAX'] == 'Y')
{
	echo '<link rel="stylesheet" type="text/css" href="', $this->getFolder(), '/style.css?v3" />';
	echo '<script type="text/javascript" src="', $this->getFolder(), '/script.js?v9"></script>';
}
?>

<script type="text/javascript">
	BX.message({
		CRM_ACTIVITY_TODO_VIEW_TITLE: '<?= CUtil::JSEscape(Loc::getMessage('CRM_ACTIVITY_TODO_VIEW_TITLE'));?>',
		CRM_ACTIVITY_TODO_CLOSE: '<?= CUtil::JSEscape(Loc::getMessage('CRM_ACTIVITY_TODO_CLOSE'));?>'
	});
</script>

<div id="crm-activity-todo-items">
<?foreach ($arResult['ITEMS'] as $item):?>
<div class="crm-activity-todo-item<?= $item['COMPLETED']=='Y' ? ' crm-activity-todo-item-completed' : ''?>"<?
	?> data-id="<?= $item['ID']?>"<?
	?> data-ownerid="<?= $item['OWNER_ID']?>"<?
	?> data-ownertypeid="<?= $item['OWNER_TYPE_ID']?>"<?
	?> data-associatedid="<?= $item['ASSOCIATED_ENTITY_ID']?>"<?
	?> data-icon="<?= $item['ICON']?>">
		<div class="crm-activity-todo-item-left">
			<input type="checkbox" id="check<?= $item['ID']?>" value="1" class="crm-activity-todo-check"<?= $item['COMPLETED']=='Y' ? ' checked="checked" disabled="disabled"' : ''?> />
		</div>
		<label class="crm-activity-todo-item-middle" for="check<?= $item['ID']?>">
			<?if ($item['DEADLINE'] != ''):?>
			<div class="crm-activity-todo-date<?= $item['HIGH']=='Y' ? ' crm-activity-todo-date-alert' : ''?>" <?
				?>title="<?= Loc::getMessage('CRM_ACTIVITY_TODO_DEADLINE')?><?= $item['HIGH']=='Y' ? ' '.Loc::getMessage('CRM_ACTIVITY_TODO_HOT') : ''?>">
				<?= $item['DEADLINE']?>
			</div>
			<?endif;?>
			<a href="#<?= $item['ID']?>" data-id="<?= $item['ID']?>" class="crm-activity-todo-link"><?= $item['SUBJECT']?></a>
			<?if (!empty($item['CONTACTS'])):?>
			<div class="crm-activity-todo-info">
				<?= Loc::getMessage('CRM_ACTIVITY_TODO_CONTACT')?>:
				<?foreach ($item['CONTACTS'] as $contact):?>
					<a href="<?= $contact['URL']?>"><?= $contact['TITLE']?></a>
				<?endforeach;?>
			</div>
			<?endif;?>
		</label>
		<div class="crm-activity-todo-item-right-nopadding<?if (!empty($item['CONTACTS'])):?> crm-activity-todo-item-right<?endif;?>">
			<div class="crm-activity-todo-event crm-activity-todo-event-<?= $item['ICON']?>" title="<?= $item['PROVIDER_TITLE']!='' ? $item['PROVIDER_TITLE'] : $item['TYPE_NAME']?>">
			<?if (!empty($item['PROVIDER_ANCHOR'])):?>
				<?= $item['PROVIDER_TITLE']!='' ? $item['PROVIDER_TITLE'] : $item['TYPE_NAME']?>
				<?if (isset($item['PROVIDER_ANCHOR']['HTML']) && !empty($item['PROVIDER_ANCHOR']['HTML'])):?>
					<br/>
					<?= $item['PROVIDER_ANCHOR']['HTML']?>
				<?elseif (false && isset($item['PROVIDER_ANCHOR']['TEXT']) && !empty($item['PROVIDER_ANCHOR']['URL'])):?>
					<a href="<?= $item['PROVIDER_ANCHOR']['URL']?>"><?= $item['PROVIDER_ANCHOR']['TEXT']?></a>
				<?endif;?>
			<?else:?>
				<?= $item['PROVIDER_TITLE']!='' ? $item['PROVIDER_TITLE'] : $item['TYPE_NAME']?>
			<?endif;?>
			</div>
		</div>
</div>
<?endforeach;?>
</div>

<script type="text/javascript">
	BX.CrmActivityTodo.create({
		container: 'crm-activity-todo-items'
	});
</script>
