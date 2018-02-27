<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 * @var CBitrixComponentTemplate $this
 * @global CMain $APPLICATION
 * @global CUser $USER
 */


$APPLICATION->IncludeComponent(
	"bitrix:rest.marketplace.localapp.toolbar",
	".default",
	array(
		"COMPONENT_PAGE" => $arParams["COMPONENT_PAGE"],
		"ADD_URL" => $arParams["ADD_URL"],
		"LIST_URL" => $arParams["LIST_URL"],
	),
	$component
);
?>

<div class="mp-app-add-block">
	<div class="mp-app-add-block-header">
		<div class="pagetitle"><?=GetMessage("MARKETPLACE_PAGE_TITLE")?></div>
		<div class="pagetitle mp-app-add-block-pagetitle-small"><?=GetMessage("MARKETPLACE_PAGE_TITLE2")?></div>
	</div>

	<div class="mp-app-add-block-content">
		<div class="mp-app-add-block-box">
			<div class="mp-app-add-box-header">
				<?=GetMessage("MARKETPLACE_BLOCK1_TITLE")?>
			</div>
			<?=GetMessage("MARKETPLACE_BLOCK1_INFO")?>
			<a href="<?=$arParams['ADD_URL']?>" class="mp-app-add-green-btn"><?=GetMessage("MARKETPLACE_BUTTON_ADD")?></a>
		</div>

		<div class="mp-app-add-block-box mp-app-add-block-box-right">
			<div class="mp-app-add-box-header">
				<?=GetMessage("MARKETPLACE_BLOCK2_TITLE")?>
			</div>
			<?=GetMessage("MARKETPLACE_BLOCK2_INFO")?>
			<a href="<?=GetMessage("MARKETPLACE_BLOCK2_LINK")?>" target="_blank" class="mp-app-add-green-btn"><?=GetMessage("MARKETPLACE_BUTTON")?></a>
		</div>

		<div class="mp-app-add-block-or"><?=GetMessage("MARKETPLACE_OR")?></div>
	</div>
</div>

