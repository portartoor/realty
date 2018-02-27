<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var \Bitrix\Bizproc\Activity\PropertiesDialog $dialog */

CJSCore::Init('file_dialog');

$map = $dialog->getMap();
$subject = $map['Subject'];
$messageText = $map['MessageText'];
$attachmentType = $map['AttachmentType'];
$attachment = $map['Attachment'];
?>
<tr>
	<td align="right" width="40%"><span class="adm-required-field"><?=htmlspecialcharsbx($subject['Name'])?>:</span></td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("string", $subject['FieldName'], $dialog->getCurrentValue($subject['FieldName']), Array('size'=> 50))?>
	</td>
</tr>
<tr>
	<td align="right" width="40%" valign="top"><span class="adm-required-field"><?=htmlspecialcharsbx($messageText['Name'])?>:</span></td>
	<td width="60%">
		<?=CBPDocument::ShowParameterField("text", $messageText['FieldName'], $dialog->getCurrentValue($messageText['FieldName']), Array('rows'=> 7))?>
	</td>
</tr>
<tr>
	<td align="right" width="40%"><?=htmlspecialcharsbx($attachmentType['Name'])?>:</td>
	<td width="60%">
		<select name="<?=htmlspecialcharsbx($attachmentType['FieldName'])?>" onchange="BPCSMA_changeAttachmentType(this.value)">
			<?
			$currentType = $dialog->getCurrentValue($attachmentType['FieldName']);
			foreach ($attachmentType['Options'] as $key => $value):?>
				<option value="<?=htmlspecialcharsbx($key)?>"<?= $currentType == $key ? " selected" : "" ?>>
					<?=htmlspecialcharsbx($value)?>
				</option>
			<?endforeach;?>
		</select>
	</td>
</tr>
<tr>
	<td align="right" width="40%"><?=htmlspecialcharsbx($attachment['Name'])?>:</td>
	<td width="60%">
		<?
		$attachmentValues = array_values(array_filter((array)$dialog->getCurrentValue($attachment['FieldName'])));
		$fileValues = $diskValues = array();

		if ($currentType == 'disk' && !CModule::IncludeModule('disk'))
		{
			$currentType = 'file';
		}

		if ($currentType != 'disk')
		{
			$currentType = 'file';
			$fileValues = $attachmentValues;
		}
		else
		{
			$diskValues = $attachmentValues;
		}
		?>
		<div id="BPCSMA-disk-control" style="<?=($currentType != 'disk')?'display:none':''?>">
			<div id="BPCSMA-disk-control-items"><?
		foreach ($diskValues as $fileId)
		{
			$object = \Bitrix\Disk\File::loadById($fileId);
			if ($object)
			{
				$objectId = $object->getId();
				$objectName = $object->getName();
				?>
		<div>
			<input type="hidden" name="<?=htmlspecialcharsbx($attachment['FieldName'])?>[]" value="<?=(int)$objectId?>"/>
			<span style="color: grey">
				<?=htmlspecialcharsbx($objectName)?>
			</span>
			<a onclick="BX.cleanNode(this.parentNode, true); return false" style="color: red; text-decoration: none; border-bottom: 1px dotted">x</a>
		</div>
					<?
				}
			}
		?>
			</div>
			<a href="#" onclick="return BPDCM_showDiskFileDialog('source_id')" style="color: black; text-decoration: none; border-bottom: 1px dotted"><?=GetMessage('CRM_SEMA_PD_CHOOSE_FILE')?></a>
		</div>
		<div id="BPCSMA-file-control" style="<?=($currentType != 'file')?'display:none':''?>">
		<?
			$filedType = $dialog->getFieldTypeObject($attachment);
			echo $filedType->renderControl(array(
				'Form' => $dialog->getFormName(),
				'Field' => $attachment['FieldName']
			), $fileValues, true, \Bitrix\Bizproc\FieldType::RENDER_MODE_DESIGNER);
		?>
		</div>
	</td>
</tr>
<script>
	var BPCSMA_changeAttachmentType = function(type)
	{
		BX.style(BX('BPCSMA-disk-control'), 'display', type==='disk' ? '' : 'none');
		BX.style(BX('BPCSMA-file-control'), 'display', type==='file' ? '' : 'none');

		var i, oldType = type==='disk' ? 'file' : 'disk';
		var disableInputs = BX('BPCSMA-'+oldType+'-control').querySelectorAll('input');
		for (i = 0; i < disableInputs.length; ++i)
			disableInputs[i].setAttribute('disabled', 'disabled');

		var enableInputs = BX('BPCSMA-'+type+'-control').querySelectorAll('input');
		for (i = 0; i < enableInputs.length; ++i)
			enableInputs[i].removeAttribute('disabled');
	};

	var BPDCM_showDiskFileDialog = function(field)
	{
		var urlSelect = '/bitrix/tools/disk/uf.php?action=selectFile&dialog2=Y&SITE_ID=' + BX.message('SITE_ID');
		var dialogName = 'BPCSMA';

		BX.ajax.get(urlSelect, 'multiselect=Y&dialogName='+dialogName,
			BX.delegate(function() {
				setTimeout(BX.delegate(function() {
					BX.DiskFileDialog.obCallback[dialogName] = {'saveButton' :function(tab, path, selected)
					{
						var i;
						for (i in selected)
						{
							if (selected.hasOwnProperty(i))
							{
								if (selected[i].type == 'file')
								{
									var div = BX.create('div',{
										html: '<input type="hidden" name="<?=htmlspecialcharsbx(CUtil::JSEscape($attachment['FieldName']))?>[]" value="'
											+(selected[i].id).toString().substr(1)+'"/>'
										+ '<span style="color: grey">'+BX.util.htmlspecialchars(selected[i].name)+'</span>'
										+ '<a onclick="BX.cleanNode(this.parentNode, true); return false" style="color: red; text-decoration: none; border-bottom: 1px dotted">x</a>'
									});

									BX('BPCSMA-disk-control-items').appendChild(div);
								}
							}
						}
					}};
					BX.DiskFileDialog.openDialog(dialogName);
				}, this), 10);
			}, this)
		);
		return false;
	};
</script>