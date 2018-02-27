<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */
$component = $this->getComponent();

if (strlen($arResult["FatalError"]) > 0)
{
	?><span class='errortext'><?=$arResult["FatalError"]?></span><br/><br/><?
	return;
}

CUtil::InitJSCore(array("ajax", "tooltip"));

if (strlen($arResult["ErrorMessage"]) > 0)
{
	?><span class='errortext'><?=$arResult["ErrorMessage"]?></span><br/><br/><?
}

$APPLICATION->IncludeComponent(
	"bitrix:socialnetwork.group.iframe.popup",
	".default",
	array(
		"PATH_TO_GROUP" => $arParams["PATH_TO_GROUP"],
		"PATH_TO_GROUP_EDIT" => htmlspecialcharsback($arResult["Urls"]["Edit"]).(strpos($arResult["Urls"]["Edit"], "?") === false ? "?" : "&")."tab=edit",
		"PATH_TO_GROUP_INVITE" => htmlspecialcharsback($arResult["Urls"]["Edit"]).(strpos($arResult["Urls"]["Edit"], "?") === false ? "?" : "&")."tab=invite",
		"ON_GROUP_ADDED" => "BX.DoNothing",
		"ON_GROUP_CHANGED" => "BX.DoNothing",
		"ON_GROUP_DELETED" => "BX.DoNothing"
	),
	null,
	array("HIDE_ICONS" => "Y")
);

$popupName = randString(6);
$APPLICATION->IncludeComponent(
	"bitrix:socialnetwork.group_create.popup",
	".default",
	array(
		"NAME" => $popupName,
		"PATH_TO_GROUP_EDIT" => (strlen($arResult["Urls"]["Edit"]) > 0
			? htmlspecialcharsback($arResult["Urls"]["Edit"])
			: ""
		),
		"GROUP_NAME" => $arResult["Group"]["NAME"]
	),
	null,
	array("HIDE_ICONS" => "Y")
);
?>

<? if ($arResult["bShowRequestSentMessage"] == "U"): ?>
	<div class="socialnetwork-group-join-request-sent">
		<?=GetMessage("SONET_C6_ACT_JOIN_REQUEST_SENT")?>
	</div>
<? elseif ($arResult["bShowRequestSentMessage"] == "G"):

	global $USER;
	$url = str_replace("#user_id#", $USER->GetID(), COption::GetOptionString("socialnetwork", "user_request_page",
		(IsModuleInstalled("intranet")) ? "/company/personal/user/#user_id#/requests/" : "/club/user/#user_id#/requests/", SITE_ID));
	?>
	<div class="socialnetwork-group-join-request-sent">
		<?=str_replace("#LINK#", $url, GetMessage("SONET_C6_ACT_JOIN_REQUEST_SENT_BY_GROUP"))?>
	</div>
<? elseif ($arResult["bUserCanRequestGroup"]): ?>
	<table width="100%" cellspacing="0" id="bx_group_description" class="socialnetwork-group-description-table<?if ($arResult["bDescriptionOpen"] != "Y"):?> socialnetwork-group-description-hide-table<?endif?>">
		<tr>
			<td valign="top">
				<table width="100%" cellspacing="0">
					<? if($arResult["Group"]["CLOSED"] == "Y"):
						?><tr>
							<td colspan="2" class="socialnetwork-group-description"><b><?=GetMessage("SONET_C39_ARCHIVE_GROUP")?></b></td>
						</tr><?
					endif;

					if(strlen($arResult["Group"]["DESCRIPTION"]) > 0):
						?><tr class="ext-header-center-row">
							<td class="socialnetwork-group-description-left-col"><?=GetMessage("SONET_C6_DESCR")?>:</td>
							<td class="socialnetwork-group-description"><?=nl2br($arResult["Group"]["DESCRIPTION"])?></td>
						</tr><?
					endif;

					if ($arResult["GroupProperties"]["SHOW"] == "Y"):
						foreach ($arResult["GroupProperties"]["DATA"] as $fieldName => $arUserField):
							if (is_array($arUserField["VALUE"]) && count($arUserField["VALUE"]) > 0 || !is_array($arUserField["VALUE"]) && StrLen($arUserField["VALUE"]) > 0):
								?><tr class="ext-header-center-row">
									<td class="socialnetwork-group-description-left-col"><?=$arUserField["EDIT_FORM_LABEL"]?>:</td>
									<td class="socialnetwork-group-description"><?
									$GLOBALS["APPLICATION"]->IncludeComponent(
										"bitrix:system.field.view",
										$arUserField["USER_TYPE"]["USER_TYPE_ID"],
										array("arUserField" => $arUserField),
										null,
										array("HIDE_ICONS"=>"Y")
									);
									?></td>
								</tr><?
							endif;
						endforeach;
					endif;
					?><tr>
						<td class="socialnetwork-group-description-left-col"><?=GetMessage("SONET_C6_TYPE")?>:</td>
						<td class="socialnetwork-group-description">
							<?=($arResult["Group"]["OPENED"] == "Y" ? GetMessage("SONET_C6_TYPE_O1") : GetMessage("SONET_C6_TYPE_O2"))?><br \>
							<?=($arResult["Group"]["VISIBLE"] == "Y" ? GetMessage("SONET_C6_TYPE_V1") : GetMessage("SONET_C6_TYPE_V2"))?><?
							if ($arResult["bUserCanRequestGroup"]):
								?><div style="margin-top: 10px;"><a title="<?=GetMessage("SONET_C6_ACT_JOIN")?>" class="webform-small-button webform-small-button-accept" href="<?=$arResult["Urls"]["UserRequestGroup"]?>">
									<span class="webform-small-button-left"></span><span class="webform-small-button-text"><?=GetMessage("SONET_C6_ACT_JOIN")?></span><span class="webform-small-button-right"></span>
								</a></div><?
							endif;
							?></td>
					</tr><?
				?></table>
			</td>
			<!--<td valign="top" class="socialnetwork-group-photo"><?//if (is_array($arResult["Group"]["IMAGE_ID_FILE"])):?><?//=$arResult["Group"]["IMAGE_ID_IMG"]?><?//else:?><?//=$arResult["Group"]["IMAGE_ID_IMG"]?><?//endif?></td>-->
		</tr>
	</table>
<? endif; ?>

	<div class="sonet-group-log">
		<div id="log_external_container"></div>
		<?
		if (
			!empty($arResult["ActiveFeatures"])
			&& array_key_exists('blog', $arResult["ActiveFeatures"])
		)
		{
			$APPLICATION->IncludeComponent(
				"bitrix:socialnetwork.log.ex",
				"",
				Array(
					"ENTITY_TYPE" => "",
					"GROUP_ID" => $arParams["GROUP_ID"],
					"USER_VAR" => $arParams["VARIABLE_ALIASES"]["user_id"],
					"GROUP_VAR" => $arParams["VARIABLE_ALIASES"]["group_id"],
					"PATH_TO_USER" => $arParams["PATH_TO_USER"],
					"PATH_TO_GROUP" => $arParams["PATH_TO_GROUP"],
					"SET_TITLE" => "N",
					"AUTH" => "Y",
					"SET_NAV_CHAIN" => "N",
					"PATH_TO_MESSAGES_CHAT" => $arParams["PM_URL"],
					"PATH_TO_VIDEO_CALL" => $arParams["PATH_TO_VIDEO_CALL"],
					"PATH_TO_USER_BLOG_POST_IMPORTANT" => $arParams["PATH_TO_USER_BLOG_POST_IMPORTANT"],
					"PATH_TO_CONPANY_DEPARTMENT" => $arParams["PATH_TO_CONPANY_DEPARTMENT"],
					"PATH_TO_GROUP_PHOTO_SECTION" => $arParams["PARENT_COMPONENT_RESULT"]["PATH_TO_GROUP_PHOTO_SECTION"],
					"PATH_TO_SEARCH_TAG" => $arParams["PATH_TO_SEARCH_TAG"],
					"DATE_TIME_FORMAT" => $arParams["DATE_TIME_FORMAT"],
					"SHOW_YEAR" => $arParams["SHOW_YEAR"],
					"NAME_TEMPLATE" => $arParams["NAME_TEMPLATE"],
					"SHOW_LOGIN" => $arParams["SHOW_LOGIN"],
					"SUBSCRIBE_ONLY" => "N",
					"SHOW_EVENT_ID_FILTER" => "Y",
					"SHOW_FOLLOW_FILTER" => "N",
					"USE_COMMENTS" => "Y",
					"PHOTO_THUMBNAIL_SIZE" => "48",
					"PAGE_ISDESC" => "N",
					"AJAX_MODE" => "N",
					"AJAX_OPTION_SHADOW" => "N",
					"AJAX_OPTION_HISTORY" => "N",
					"AJAX_OPTION_JUMP" => "N",
					"AJAX_OPTION_STYLE" => "Y",
					"CONTAINER_ID" => "log_external_container",
					"PAGE_SIZE" => 10,
					"SHOW_RATING" => $arParams["SHOW_RATING"],
					"RATING_TYPE" => $arParams["RATING_TYPE"],
					"SHOW_SETTINGS_LINK" => "Y",
					"AVATAR_SIZE" => $arParams["LOG_THUMBNAIL_SIZE"],
					"AVATAR_SIZE_COMMENT" => $arParams["LOG_COMMENT_THUMBNAIL_SIZE"],
					"NEW_TEMPLATE" => $arParams["LOG_NEW_TEMPLATE"],
					"SET_LOG_CACHE" => "Y",
				),
				$component,
				array("HIDE_ICONS"=>"Y")
			);
		}
		?>
	</div>

<?
$this->SetViewTarget("sidebar", 50);
include("sidebar.php");
$this->EndViewTarget();

$this->SetViewTarget("pagetitle", 1000);
include("title_buttons.php");
$this->EndViewTarget();