<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

$this->SetViewTarget("pagetitle", 100);

$popupName = $this->randString(6);
$APPLICATION->IncludeComponent(
	"bitrix:socialnetwork.group_create.popup",
	".default",
	array(
		"NAME" => $popupName,
		"PATH_TO_GROUP_EDIT" => (strlen($arParams["PATH_TO_GROUP_CREATE"]) > 0 
			? htmlspecialcharsback($arParams["PATH_TO_GROUP_CREATE"])
			: ""
		)
	),
	null,
	array("HIDE_ICONS" => "Y")
);

	?><span class="sonet-groups-title-button-search"><form action="<?=$APPLICATION->GetCurPageParam("", array($arResult["LIST_NAV_ID"]))?>" method="POST" id="sonet_groups_search_form"><?
		?><input type="hidden" name="filter_my" value="<?=$arResult["filter_my"]?>"><?
		?><input type="hidden" name="filter_archive" value="<?=$arResult["filter_archive"]?>"><?
		?><input type="hidden" name="filter_extranet" value="<?=$arResult["filter_extranet"]?>"><?
		?><span class="sonet-groups-title-button-search-textbox"><?
			?><input placeholder="<?=GetMessage('SONET_C36_T_SEARCH_PLACEHOLDER')?>" name="filter_name" value="<?=$arResult["filter_name"]?>" type="text" onblur="BX.removeClass(this.parentNode.parentNode, 'sonet-groups-title-button-search-full'); /* this.value=''; */" onclick="BX.addClass(this.parentNode.parentNode, 'sonet-groups-title-button-search-full')" class="sonet-groups-title-button-search-input"><?
			?><span class="sonet-groups-title-button-search-icon" onclick="var form = BX('sonet_groups_search_form'); BX.submit(form);"></span><?
		?></span><?
	?></form></span><?

	if ($arParams["ALLOW_CREATE_GROUP"] == "Y")
	{
		?><span class="webform-small-button webform-small-button-blue bx24-top-toolbar-add" onclick="if (BX.SGCP) { BX.SGCP.ShowForm('create', '<?=$popupName?>', event); } else { return false; }"><?
			?><span class="webform-small-button-icon"></span><?
			?><span class="webform-small-button-text"><?=GetMessage("SONET_C36_T_CREATE")?></span><?
		?></span><?

		if (isset($_GET["new"]))
		{
			?><script>
				BX.ready(function() {
					BX.SGCP.ShowForm("create", "<?=$popupName?>", {});
				});<?
			?></script><?
		}
	}

$this->EndViewTarget();