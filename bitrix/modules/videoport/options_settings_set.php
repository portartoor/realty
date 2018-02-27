<?
global $MESS;
include(GetLangFileName($GLOBALS["DOCUMENT_ROOT"]."/bitrix/modules/videoport/lang/", "/options.php"));

$arAllOptionsVP = array(
	array("ServerHost", GetMessage("VIDEOPORT_ServerHost"), "", Array("text", 20)),
	array("ServerPort", GetMessage("VIDEOPORT_ServerPort"), "", Array("text", 10)),
	array("SecretCode", GetMessage("VIDEOPORT_SecretCode"), "", Array("text", 20)),
	array("window_main", GetMessage("VIDEOPORT_window_main"), "Y", Array("checkbox")),
	array("window_chat", GetMessage("VIDEOPORT_window_chat"), "N", Array("checkbox")),
	array("window_members", GetMessage("VIDEOPORT_window_members"), "Y", Array("checkbox")),
	array("window_settings", GetMessage("VIDEOPORT_window_settings"), "Y", Array("checkbox")),
	array("window_info", GetMessage("VIDEOPORT_window_info"), "Y", Array("checkbox")),
	array("window_self_camera", GetMessage("VIDEOPORT_window_self_camera"), "N", Array("checkbox")),
	array("window_show_invite", GetMessage("VIDEOPORT_window_show_invite"), "Y", Array("checkbox")),
);

if ($REQUEST_METHOD=="POST" && strlen($Update)>0 && check_bitrix_sessid())
{
	for ($i = 0; $i < count($arAllOptionsVP); $i++)
	{
		$name = $arAllOptionsVP[$i][0];
		$val = $$name;
		if ($arAllOptionsVP[$i][3][0] == "checkbox" && $val != "Y")
			$val = "N";
		COption::SetOptionString("videoport", $name, $val, $arAllOptionsVP[$i][1]);
	}
}
if ($REQUEST_METHOD=="GET" && strlen($RestoreDefaults)>0 && $RIGHTS=="W" && check_bitrix_sessid())
{
	COption::RemoveOption("videoport");
}