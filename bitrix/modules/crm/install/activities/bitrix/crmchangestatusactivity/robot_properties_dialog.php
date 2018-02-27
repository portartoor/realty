<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$map = $dialog->getMap();
$status = $map['TargetStatus'];
$selected = $dialog->getCurrentValue($status['FieldName']);
$context = $dialog->getContext();
$currentStatus = isset($context['TEMPLATE_STATUS']) ? (string)$context['TEMPLATE_STATUS'] : null;
$disabled = 'disabled';
?>
<div class="crm-automation-popup-settings">
	<span class="crm-automation-popup-settings-title"><?=htmlspecialcharsbx($status['Name'])?>: </span>
	<select class="crm-automation-popup-settings-dropdown" name="<?=htmlspecialcharsbx($status['FieldName'])?>">
		<?foreach ($status['Options'] as $value => $optionLabel):?>
			<option value="<?=htmlspecialcharsbx($value)?>"
				<?=($value == $selected) ? ' selected' : ''?> <?=$disabled?>
			><?=htmlspecialcharsbx($optionLabel)?></option>
		<?
		if ($value == $currentStatus)
			$disabled = '';
		endforeach;?>
	</select>
</div>