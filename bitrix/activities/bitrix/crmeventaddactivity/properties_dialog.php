<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<tr>
	<td align="right" width="40%"><span style="color:#FF0000;">*</span> <?= GetMessage("BPEAA_PD_TYPE") ?>:</td>
	<td width="60%">
		<select name="event_type">
			<option value=""></option>
			<?
			$fl = false;
			foreach ($arTypes as $key => $value)
			{
				if ($key == $arCurrentValues["event_type"])
					$fl = true;
				?><option value="<?= htmlspecialcharsbx($key) ?>"<?= ($key == $arCurrentValues["event_type"]) ? " selected" : "" ?>><?= $value ?></option><?
			}
			?>
		</select><br />
		<input type="text" id="id_target_state_name" name="target_state_name" value="<?= !$fl ? htmlspecialcharsbx($arCurrentValues["target_state_name"]) : "" ?>">
		<input type="button" value="..." onclick="BPAShowSelector('id_target_state_name', 'string');">
	</td>
</tr>
<tr>
	<td align="right" width="40%"><span style="color:#FF0000;">*</span> <?= GetMessage("BPEAA_PD_MESSAGE") ?>:</td>
	<td width="60%">
		<textarea name="event_text" id="id_event_text" rows="7" cols="40"><?= htmlspecialcharsbx($arCurrentValues["event_text"]) ?></textarea>
		<input type="button" value="..." onclick="BPAShowSelector('id_event_text', 'string');">
	</td>
</tr>