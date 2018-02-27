<?
$module_id = "video";
$RIGHTS = $APPLICATION->GetGroupRight($module_id);
if ($RIGHTS>="R") :

global $MESS;
include(GetLangFileName($GLOBALS["DOCUMENT_ROOT"]."/bitrix/modules/main/lang/", "/options.php"));
include(GetLangFileName($GLOBALS["DOCUMENT_ROOT"]."/bitrix/modules/video/lang/", "/options.php"));

$groups = array();
$z = CGroup::GetList($v1, $v2, array("ACTIVE"=>"Y", "ADMIN"=>"N", "ANONYMOUS"=>"N"));
while($zr = $z->Fetch())
{
	$groups[$zr["ID"]] = "[".$zr["ID"]."] ".htmlspecialchars($zr["NAME"]);
}

$arAllOptions = array(
	array("video-room-count", GetMessage("VIDEO_ROOM_COUNT"), "1", Array("text", 10)),
	array("video-room-users", GetMessage("VIDEO_ROOM_USERS"), "6", Array("text", 10)),
	Array("videocall-group", GetMessage("VIDEO_VIDEOCALL_GROUP"), "", Array("multiselectbox", 5), $groups),
);

if ($REQUEST_METHOD=="GET" && strlen($RestoreDefaults)>0 && $RIGHTS=="W" && check_bitrix_sessid())
{
	COption::RemoveOption("video");
	$z = CGroup::GetList($v1="id",$v2="asc", array("ACTIVE" => "Y", "ADMIN" => "N"));
	while($zr = $z->Fetch())
		$APPLICATION->DelGroupRight($module_id, array($zr["ID"]));
}
if ($REQUEST_METHOD=="POST" && strlen($Update)>0 && $RIGHTS=="W" && check_bitrix_sessid())
{
	for ($i = 0; $i < count($arAllOptions); $i++)
	{
		$name = $arAllOptions[$i][0];
		$val = $$name;
		if ($arAllOptions[$i][3][0] == "checkbox" && $val != "Y")
			$val = "N";
		elseif($arAllOptions[$i][3][0] == "multiselectbox" && !empty($val))
			$val = implode(",", $val);
		COption::SetOptionString("video", $name, $val, $arAllOptions[$i][1]);
	}
}
if(CModule::IncludeModule("videoport"))
{
	include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/videoport/options_settings_set.php");
}
if(CModule::IncludeModule("videomost"))
{
	include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/videomost/options_settings_set.php");
}

$aTabs = array(array("DIV" => "edit1", "TAB" => GetMessage("VIDE_TAB_SET"), "ICON" => "video_settings", "TITLE" => GetMessage("VIDEO_TAB_SET_ALT")));

if(CModule::IncludeModule("videoport"))
	$aTabs[] = array("DIV" => "edit2", "TAB" => GetMessage("VIDEO_VP_TAB_SET"), "ICON" => "video_settings", "TITLE" => GetMessage("VIDEO_VP_TAB_SET_ALT"));
if(CModule::IncludeModule("videomost"))
	$aTabs[] = array("DIV" => "edit3", "TAB" => GetMessage("VIDEO_VM_TAB_SET"), "ICON" => "video_settings", "TITLE" => GetMessage("VIDEO_VM_TAB_SET_ALT"));

$aTabs[] = array("DIV" => "edit4", "TAB" => GetMessage("MAIN_TAB_RIGHTS"), "ICON" => "video_settings", "TITLE" => GetMessage("MAIN_TAB_RIGHTS"));
	
$tabControl = new CAdminTabControl("tabControl", $aTabs);
?>
<?
$tabControl->Begin();
?><form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialchars($mid)?>&lang=<?=LANGUAGE_ID?>"><?
bitrix_sessid_post();
$tabControl->BeginNextTab();

	for ($i = 0; $i < count($arAllOptions); $i++):
		$Option = $arAllOptions[$i];
		$val = COption::GetOptionString("video", $Option[0], $Option[2]);
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
				<?elseif($type[0]=="multiselectbox"):?>
					<select name="<?echo htmlspecialchars($Option[0])?>[]" id="<?echo htmlspecialchars($Option[0])?>" multiple size="<?=$type[1]?>">
						<?
						$val = explode(",", $val);
						foreach($Option[4] as $v => $k)
						{
							?><option value="<?=$v?>"<?if(is_array($val) && in_array($v, $val))echo" selected";?>><?=$k?></option><?
						}
						?>
					</select
				<?endif?>
			</td>
		</tr>
	<?endfor;?>

<?
if(CModule::IncludeModule("videoport"))
{
	$tabControl->BeginNextTab();
	include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/videoport/options_settings.php");
}
if(CModule::IncludeModule("videomost"))
{
	$tabControl->BeginNextTab();
	include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/videomost/options_settings.php");
}
?>
<?$tabControl->BeginNextTab();?>
<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/group_rights.php");?>
<?$tabControl->Buttons();?>
<script language="JavaScript">
function RestoreDefaults()
{
	if (confirm('<?echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>'))
		window.location = "<?echo $APPLICATION->GetCurPage()?>?RestoreDefaults=Y&lang=<?echo LANG?>&mid=<?echo urlencode($mid)."&".bitrix_sessid_get();?>";
}
</script>

<input type="submit" <?if ($RIGHTS<"W") echo "disabled" ?> name="Update" value="<?echo GetMessage("MAIN_SAVE")?>">
<input type="hidden" name="Update" value="Y">
<input type="reset" name="reset" value="<?echo GetMessage("MAIN_RESET")?>">
<input type="button" <?if ($RIGHTS<"W") echo "disabled" ?> title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="RestoreDefaults();" value="<?echo GetMessage("MAIN_RESTORE_DEFAULTS")?>">
<?$tabControl->End();?>
</form>
<?endif;?>
