<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */
CUtil::InitJSCore(array("popup", "ajax"));

$this->addExternalCss(SITE_TEMPLATE_PATH."/css/profile_menu.css");
$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "")."profile-menu-mode");

$this->SetViewTarget("above_pagetitle", 100);
?>
<div class="profile-menu profile-menu-group">
	<div class="profile-menu-inner">
		<a
			href="<?=$arResult["Urls"]["View"]?>"
			class="profile-menu-avatar group-default-avatar"
			<?if (strlen($arResult["Group"]["IMAGE_FILE"]["src"]) > 0):?>
				style="background:url('<?=$arResult["Group"]["IMAGE_FILE"]["src"]?>') no-repeat center center; background-size: cover"
			<?endif;?>
		></a>
		<div class="profile-menu-right">
			<div class="profile-menu-info<?=($arResult["Group"]["IS_EXTRANET"] == "Y" ? " profile-menu-group-info-extranet" : "")?>">
				<a href="<?=$arResult["Urls"]["View"]?>" class="profile-menu-name"><?=$arResult["Group"]["NAME"]?></a>
				<?if($arResult["Group"]["CLOSED"] == "Y"):?>
					<span class="profile-menu-description"><?=GetMessage("SONET_UM_ARCHIVE_GROUP")?></span>
				<?endif?>
			</div>
			<div class="profile-menu-items"><?
				?><a
					href="<?=$arResult["Urls"]["View"]?>"
					class="profile-menu-item
					<?if ($arParams["PAGE_ID"] == "group"):?>
						profile-menu-item-active
					<?endif?>"><?=GetMessage("SONET_UM_GENERAL")?><?
				?></a><?

				foreach ($arResult["CanView"] as $key => $val)
				{
					if (!$val || $key == "content_search")
					{
						continue;
					}

					if (
						!empty($arResult["OnClicks"])
						&& !empty($arResult["OnClicks"][$key])
					)
					{
						?><a href="javascript:void(0);" onclick="<?=$arResult["OnClicks"][$key]?>" class="profile-menu-item"><?=$arResult["Title"][$key]?></a><?
					}
					else
					{
						?><a href="<?=$arResult["Urls"][$key]?>" class="profile-menu-item<?if ($arParams["PAGE_ID"] == "group_".$key):?> profile-menu-item-active<?endif?>"><?=$arResult["Title"][$key]?></a><?
					}
				}
			?></div>
		</div>
	</div>
</div>
<?$this->EndViewTarget();?>