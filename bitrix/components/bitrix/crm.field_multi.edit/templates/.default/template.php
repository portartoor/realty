<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>

<table id="tblLIST-<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>-<?=CUtil::JSEscape($arResult['TYPE_ID'])?>" class="crm_fm" cellspacing="0">
<?foreach($arResult['VALUES'] as $arValue):?>
<tr>
	<td class="crm_fm_td_value">
		<input type="text" size="35" name="<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>[<?=CUtil::JSEscape($arResult['TYPE_ID'])?>][<?=$arValue['ID']?>][VALUE]" id="field-<?=$arStatus['ID']?>" value="<?=htmlspecialcharsbx($arValue['VALUE'])?>" class="value-input">
	</td>
	<td class="crm_fm_td_select">
		<?=SelectBoxFromArray(CUtil::JSEscape($arResult['FM_MNEMONIC']).'['.htmlspecialcharsbx($arResult['TYPE_ID']).']['.$arValue['ID'].'][VALUE_TYPE]', $arResult['TYPE_BOX'], $arValue['VALUE_TYPE'])?>
	</td>
	<td class="crm_fm_td_delete"><div class="delete-action" onclick="delete_item(this, '<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>-<?=CUtil::JSEscape($arResult['TYPE_ID'])?>', /<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>\[<?=CUtil::JSEscape($arResult['TYPE_ID'])?>\]\[(n)([0-9]*)\]/g, 2);" title="<?=GetMessage('CRM_STATUS_LIST_DELETE')?>"></div></td>
</tr>
<?endforeach;?>
<?if (empty($arResult['VALUES'])):?>
<tr>
	<td class="crm_fm_td_value">
		<input type="text" size="35" name="<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>[<?=CUtil::JSEscape($arResult['TYPE_ID'])?>][n1][VALUE]" id="field-<?=$arStatus['ID']?>" value="<?=$arStatus['NAME']?>" class="value-input">
	</td>
	<td class="crm_fm_td_select">
		<?=SelectBoxFromArray(CUtil::JSEscape($arResult['FM_MNEMONIC']).'['.htmlspecialcharsbx($arResult['TYPE_ID']).'][n1][VALUE_TYPE]', $arResult['TYPE_BOX'])?>
	</td>
	<td class="crm_fm_td_delete"><div class="delete-action" onclick="delete_item(this, '<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>-<?=CUtil::JSEscape($arResult['TYPE_ID'])?>', /<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>\[<?=CUtil::JSEscape($arResult['TYPE_ID'])?>\]\[(n)([0-9]*)\]/g, 2);" title="<?=GetMessage('CRM_STATUS_LIST_DELETE')?>"></div></td>
</tr>
	<?if ($arResult['TYPE_ID'] == 'WEB'):?>
	<tr>
		<td class="crm_fm_td_value">
			<input type="text" size="35" name="<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>[<?=CUtil::JSEscape($arResult['TYPE_ID'])?>][n2][VALUE]" id="field-<?=$arStatus['ID']?>" value="<?=$arStatus['NAME']?>" class="value-input">
		</td>
		<td class="crm_fm_td_select">
			<?=SelectBoxFromArray(CUtil::JSEscape($arResult['FM_MNEMONIC']).'['.htmlspecialcharsbx($arResult['TYPE_ID']).'][n2][VALUE_TYPE]', $arResult['TYPE_BOX'], 'FACEBOOK')?>
		</td>
		<td class="crm_fm_td_delete"><div class="delete-action" onclick="delete_item(this, '<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>-<?=CUtil::JSEscape($arResult['TYPE_ID'])?>', /<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>\[<?=CUtil::JSEscape($arResult['TYPE_ID'])?>\]\[(n)([0-9]*)\]/g, 2);" title="<?=GetMessage('CRM_STATUS_LIST_DELETE')?>"></div></td>
	</tr>
	<tr>
		<td class="crm_fm_td_value">
			<input type="text" size="35" name="<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>[<?=CUtil::JSEscape($arResult['TYPE_ID'])?>][n3][VALUE]" id="field-<?=$arStatus['ID']?>" value="<?=$arStatus['NAME']?>" class="value-input">
		</td>
		<td class="crm_fm_td_select">
			<?=SelectBoxFromArray(CUtil::JSEscape($arResult['FM_MNEMONIC']).'['.htmlspecialcharsbx($arResult['TYPE_ID']).'][n3][VALUE_TYPE]', $arResult['TYPE_BOX'], 'TWITTER')?>
		</td>
		<td class="crm_fm_td_delete"><div class="delete-action" onclick="delete_item(this, '<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>-<?=CUtil::JSEscape($arResult['TYPE_ID'])?>', /<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>\[<?=CUtil::JSEscape($arResult['TYPE_ID'])?>\]\[(n)([0-9]*)\]/g, 2);" title="<?=GetMessage('CRM_STATUS_LIST_DELETE')?>"></div></td>
	</tr>
	<?endif;?>
<?endif;?>
</table>
<a href="#add" class="status-field-add" onclick="addNewTableRow('<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>-<?=CUtil::JSEscape($arResult['TYPE_ID'])?>', /<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>\[<?=CUtil::JSEscape($arResult['TYPE_ID'])?>\]\[(n)([0-9]*)\]/g, 2)"><?=GetMessage('CRM_STATUS_LIST_ADD')?></a>
<table width="300" id="tblSAMPLE-<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>-<?=CUtil::JSEscape($arResult['TYPE_ID'])?>" style="display:none">
<tr>
	<td class="crm_fm_td_value">
		<input type="text" size="35" name="<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>[<?=CUtil::JSEscape($arResult['TYPE_ID'])?>][n0][VALUE]" id="field-<?=$arStatus['ID']?>" value="<?=$arStatus['NAME']?>" class="value-input">
	</td>
	<td class="crm_fm_td_select">
		<?=SelectBoxFromArray(CUtil::JSEscape($arResult['FM_MNEMONIC']).'['.htmlspecialcharsbx($arResult['TYPE_ID']).'][n0][VALUE_TYPE]', $arResult['TYPE_BOX'])?>
	</td>
	<td class="crm_fm_td_delete"><div class="delete-action" onclick="delete_item(this, '<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>-<?=CUtil::JSEscape($arResult['TYPE_ID'])?>', /<?=CUtil::JSEscape($arResult['FM_MNEMONIC'])?>\[<?=CUtil::JSEscape($arResult['TYPE_ID'])?>\]\[(n)([0-9]*)\]/g, 2);" title="<?=GetMessage('CRM_STATUS_LIST_DELETE')?>"></div></td>
</tr>
</table>