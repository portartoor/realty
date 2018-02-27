<style>
	html, body, div, span, object, iframe, h1, h2, h3, h4, h5, h6, p, blockquote, pre, abbr, address, cite, code, del, dfn, em, img, ins, kbd, q, samp, small, strong, sub, sup, var, b, i, dl, dt, dd, ol, ul, li, fieldset, form, label, legend, table, caption, tbody, tfoot, thead, tr, th, td, article, aside, canvas, details, figcaption, figure, footer, header, hgroup, menu, nav, section, summary, time, mark, audio, video{
		font-size:100% !important;
		vertical-align:top !important;
	}
</style>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
global $USER;
$page = "/realty/mail/interes.php";
$request_id_from = $_GET["from"];
$request_id_to   = $_GET["to"];
if(!$request_id_from||!$request_id_to)die();
global $DB;
$results = $DB->Query("SELECT `UF_BITRIX_USER` from `requests` INNER JOIN `agent` ON `requests`.`UF_AGENT`=`agent`.`UF_AGENT_ID` WHERE `requests`.`ID`=".$request_id_to);
if($row = $results->Fetch())
{
	$user_id = $row["UF_BITRIX_USER"];
	if($user_id==$USER->GetID())
	{
		$results = $DB->Query("SELECT `UF_BITRIX_USER` from `requests` INNER JOIN `agent` ON `requests`.`UF_AGENT`=`agent`.`UF_AGENT_ID` WHERE `requests`.`ID`=".$request_id_from);
		if($row = $results->Fetch())
		{
			$user_id = $row["UF_BITRIX_USER"];
		}
	}
	echo $user_id."!!!".$USER->GetID();
	?>
	<div style="font-size:1500%;">
	<?
	/*$APPLICATION->SetTitle("Переписка по интересам");?><?$APPLICATION->IncludeComponent(
		"westpower:socialnetwork.messages_chat",
		"",
		Array(
			"PAGE_VAR" => "page",
			"PATH_TO_MESSAGES_USERS_MESSAGES" => "",
			"PATH_TO_SMILE" => "/bitrix/images/socialnetwork/smile/",
			"PATH_TO_USER" => "/company/personal/user/".$user_id."/",
			"SET_NAV_CHAIN" => "Y",
			"USER_ID" => $user_id,
			"USER_VAR" => ""
		)
	);*/
	if (CModule::IncludeModule("im") && CBXFeatures::IsFeatureEnabled('WebMessenger'))
	{
		$APPLICATION->IncludeComponent("bitrix:im.messenger", "", Array(
			"RECENT" => "Y",
			"PATH_TO_SONET_EXTMAIL" => SITE_DIR."company/personal/mail/"
		), false, Array("HIDE_ICONS" => "Y"));
	} 
	?><br>
	</div>
<?}?>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>