<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

$map = $dialog->getMap();
$messageText = $map['MessageText'];
$providerId = $map['ProviderId'];
$selectedProviderId = (string)$dialog->getCurrentValue($providerId['FieldName'], '');
?>
<div class="crm-automation-popup-settings">
	<textarea name="<?=htmlspecialcharsbx($messageText['FieldName'])?>"
			class="crm-automation-popup-textarea"
			placeholder="<?=htmlspecialcharsbx($messageText['Name'])?>"
			data-role="inline-selector-target"
	><?=htmlspecialcharsbx($dialog->getCurrentValue($messageText['FieldName'], ''))?></textarea>
</div>
<div class="crm-automation-popup-settings">
	<span class="crm-automation-popup-settings-title"><?=htmlspecialcharsbx($providerId['Name'])?>: </span>
	<select class="crm-automation-popup-settings-dropdown" name="<?=htmlspecialcharsbx($providerId['FieldName'])?>">
		<option value=""><?=GetMessage('CRM_SSMSA_RPD_SELECT_PROVIDER')?></option>
		<?foreach ($providerId['Options'] as $value => $optionLabel):?>
			<option value="<?=htmlspecialcharsbx($value)?>"
				<?=($value === $selectedProviderId) ? ' selected' : ''?>
			><?=htmlspecialcharsbx($optionLabel)?></option>
		<?endforeach;?>
	</select>
</div>
<div class="crm-automation-popup-settings crm-automation-popup-settings-text">
	<?=GetMessage('CRM_SSMSA_RPD_MARKETPLACE', array(
		'#A1#' => '<a href="/marketplace/category/crm_robot_sms/" target="_blank">',
		'#A2#' => '</a>'
	))?>
</div>