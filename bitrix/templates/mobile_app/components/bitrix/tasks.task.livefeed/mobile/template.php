<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$avatarId = "lenta-task-avatar-".randString(5);
?>
<div class="lenta-info-block info-block-blue">
	<div class="lenta-info-block-l">
		<div class="lenta-info-block-l-text"><?=GetMessage("TASKS_SONET_LOG_RESPONSIBLE_ID")?>:</div>
	</div>
	<div class="lenta-info-block-r">
		<div class="lenta-info-block-data">
			<div class="lenta-info-avatar avatar" id="<?=$avatarId?>"<?if ($arResult["PHOTO"]):?> data-src="<?=$arResult["PHOTO"]["CACHE"]["src"]?>"<?endif?>></div>
			<div class="lenta-info-name">
				<a href="<?=$arResult["PATH_TO_USER"]?>" class="lenta-info-name-text"><?=CUser::FormatName($arParams["NAME_TEMPLATE"], $arResult["USER"])?></a>
				<div class="lenta-info-name-description"><?=htmlspecialcharsbx($arResult["USER"]["WORK_POSITION"])?></div>
			</div>
		</div>
	</div>
	<span class="lenta-block-angle"></span>
</div><?
if ($arResult["PHOTO"]):
	?><script>BitrixMobile.LazyLoad.registerImage("<?=$avatarId?>");</script><?
endif;

if ($arParams["TYPE"] !== 'comment')
{
	if ($arParams["TYPE"] == "status")
	{
		?><div class="lenta-info-block-description">
			<div class="lenta-info-block-description-text"><?=htmlspecialcharsbx($arParams["~MESSAGE_24_2"])?></div>
		</div><?
	}
	elseif (strlen($arParams["MESSAGE_24_2"]) > 0 && strlen($arParams["CHANGES_24"]) > 0)
	{
		?><div class="lenta-info-block-description">
			<div class="lenta-info-block-description-title"><?=htmlspecialcharsbx($arParams["MESSAGE_24_2"])?>:</div>
			<div class="lenta-info-block-description-text"><?=htmlspecialcharsbx($arParams["CHANGES_24"])?></div>
		</div><?
	}
}
