<?
global $MESS;
include(GetLangFileName($GLOBALS["DOCUMENT_ROOT"]."/bitrix/modules/videoport/lang/", "/options.php"));

for ($i = 0; $i < count($arAllOptionsVP); $i++)
{
	$Option = $arAllOptionsVP[$i];
	$val = COption::GetOptionString("videoport", $Option[0], $Option[2]);
	$type = $Option[3];
	?>
	<tr>
		<td valign="top" width="50%"><?
			if ($type[0]=="checkbox")
				echo "<label for=\"".htmlspecialchars($Option[0])."\">".$Option[1]."</label>";
			else
				echo $Option[1];
		?>:</td>
		<td valign="middle" width="50%">
			<?if($type[0]=="checkbox"):?>
				<input type="checkbox" name="<?echo htmlspecialchars($Option[0])?>" id="<?echo htmlspecialchars($Option[0])?>" value="Y"<?if($val=="Y")echo" checked";?>>
			<?elseif($type[0]=="text"):?>
				<input type="text" size="<?echo $type[1]?>" value="<?echo htmlspecialchars($val)?>" name="<?echo htmlspecialchars($Option[0])?>">
			<?elseif($type[0]=="textarea"):?>
				<textarea rows="<?echo $type[1]?>" cols="<?echo $type[2]?>" name="<?echo htmlspecialchars($Option[0])?>"><?echo htmlspecialchars($val)?></textarea>
			<?elseif($type[0]=="selectbox"):?>
				<select name="<?echo htmlspecialchars($Option[0])?>" id="<?echo htmlspecialchars($Option[0])?>">
					<?foreach($Option[4] as $v => $k)
					{
						?><option value="<?=$v?>"<?if($val==$v)echo" selected";?>><?=$k?></option><?
					}
					?>
				</select
			<?endif?>
		</td>
	</tr>
<?}?>