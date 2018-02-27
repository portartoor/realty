<?
$langs = CLanguage::GetList(($b=""), ($o=""));
while($lang = $langs->Fetch())
{
	$lid = $lang["LID"];
	IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/video/install/events.php", $lid);

	$et = new CEventType;
	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "VIDEO_CONF_USER_INVITE",
		"NAME" => GetMessage("VIDEO_CONF_USER_INVITE_NAME"),
		"DESCRIPTION" => GetMessage("VIDEO_CONF_USER_INVITE_DESC"),
	));

	$et = new CEventType;
	$et->Add(array(
		"LID" => $lid,
		"EVENT_NAME" => "VIDEO_CALL_USER_INVITE",
		"NAME" => GetMessage("VIDEO_CALL_USER_INVITE_NAME"),
		"DESCRIPTION" => GetMessage("VIDEO_CALL_USER_INVITE_DESC"),
	));

	$arSites = array();
	$sites = CSite::GetList(($b=""), ($o=""), Array("LANGUAGE_ID"=>$lid));
	while ($site = $sites->Fetch())
		$arSites[] = $site["LID"];

	if(count($arSites) > 0)
	{
		$emess = new CEventMessage;
		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "VIDEO_CONF_USER_INVITE",
			"LID" => $arSites,
			"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"SUBJECT" => GetMessage("VIDEO_CONF_USER_INVITE_SUBJECT"),
			"MESSAGE" => GetMessage("VIDEO_CONF_USER_INVITE_MESSAGE"),
			"BODY_TYPE" => "text",
		));

		$emess = new CEventMessage;
		$emess->Add(array(
			"ACTIVE" => "Y",
			"EVENT_NAME" => "VIDEO_CALL_USER_INVITE",
			"LID" => $arSites,
			"EMAIL_FROM" => "#DEFAULT_EMAIL_FROM#",
			"EMAIL_TO" => "#EMAIL_TO#",
			"SUBJECT" => GetMessage("VIDEO_CALL_USER_INVITE_SUBJECT"),
			"MESSAGE" => GetMessage("VIDEO_CALL_USER_INVITE_MESSAGE"),
			"BODY_TYPE" => "text",
		));
	}
}
?>
