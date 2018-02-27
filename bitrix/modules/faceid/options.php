<?php
if(!$USER->IsAdmin())
	return;

IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/main/options.php');
IncludeModuleLangFile($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/modules/faceid/options.php');

CModule::IncludeModule('faceid');

$errorMessage = '';

$aTabs = array(
	array(
		"DIV" => "edit1", "TAB" => GetMessage("FACEID_TAB_SETTINGS"), "ICON" => "faceid_config", "TITLE" => GetMessage("FACEID_TAB_TITLE_SETTINGS_2"),
	),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);

if(strlen($_POST['Update'])>0 && check_bitrix_sessid())
{
	if (strlen($_POST['PUBLIC_URL']) > 0 && strlen($_POST['PUBLIC_URL']) < 12)
	{
		$errorMessage = GetMessage('FACEID_ACCOUNT_ERROR_PUBLIC');
	}
	else if(strlen($_POST['Update'])>0)
	{
		COption::SetOptionString("faceid", "portal_url", $_POST['PUBLIC_URL']);
		COption::SetOptionString("faceid", "debug", isset($_POST['DEBUG_MODE']));
		if (isset($_POST['DEBUG_MODE']))
		{
			COption::SetOptionString("faceid", "wait_response", isset($_POST['WAIT_RESPONSE']));
		}

		if(strlen($Update)>0 && strlen($_REQUEST["back_url_settings"])>0)
		{
			LocalRedirect($_REQUEST["back_url_settings"]);
		}
		else
		{
			LocalRedirect($APPLICATION->GetCurPage()."?mid=".urlencode($mid)."&lang=".urlencode(LANGUAGE_ID)."&back_url_settings=".urlencode($_REQUEST["back_url_settings"])."&".$tabControl->ActiveTabParam());
		}
	}
}
?>
<form method="post" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&lang=<?echo LANG?>">
<?php echo bitrix_sessid_post()?>
<?php
$tabControl->Begin();
$tabControl->BeginNextTab();
if ($errorMessage):?>
<tr>
	<td colspan="2" align="center"><b style="color:red"><?=$errorMessage?></b></td>
</tr>
<?endif;?>
<tr>
	<td width="40%"><?=GetMessage("FACEID_PUBLIC_URL")?>:</td>
	<td width="60%"><input type="text" name="PUBLIC_URL" value="<?=htmlspecialcharsbx(\Bitrix\FaceId\Http::getServerAddress())?>" /></td>
</tr>
<?if (COption::GetOptionInt("faceid", "debug")):?>
<tr>
	<td width="40%" valign="top"><?=GetMessage("FACEID_WAIT_RESPONSE")?>:</td>
	<td width="60%">
		<input type="checkbox" name="WAIT_RESPONSE" value="Y" <?=(COption::GetOptionInt("faceid", "wait_response")? 'checked':'')?> /><br>
		<?=GetMessage("FACEID_WAIT_RESPONSE_DESC")?>
	</td>
</tr>
<?endif;?>
<tr>
	<td width="40%"><?=GetMessage("FACEID_ACCOUNT_DEBUG")?>:</td>
	<td width="60%"><input type="checkbox" name="DEBUG_MODE" value="Y" <?=(COption::GetOptionInt("faceid", "debug")? 'checked':'')?> /></td>
</tr>
<?$tabControl->Buttons();?>
<input type="submit" name="Update" value="<?echo GetMessage('MAIN_SAVE')?>">
<input type="reset" name="reset" value="<?echo GetMessage('MAIN_RESET')?>">
<?$tabControl->End();?>
</form>