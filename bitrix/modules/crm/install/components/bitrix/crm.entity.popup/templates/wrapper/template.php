<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var CCrmEntityPopupComponent $component */


if($arResult['IFRAME'])
{
	$APPLICATION->RestartBuffer();
	?><!DOCTYPE html>
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=LANGUAGE_ID ?>" lang="<?=LANGUAGE_ID ?>">
	<head>
		<script type="text/javascript">
			// Prevent loading page without header and footer
			if(window == window.top)
			{
				window.location = "<?=CUtil::JSEscape($APPLICATION->GetCurPageParam('', array('IFRAME'))); ?>";
			}
		</script>
		<?$APPLICATION->ShowHead();?>
	</head>
	<body class="template-<?= SITE_TEMPLATE_ID ?> <? if(!$arResult['IFRAME_USE_SCROLL']):?>task-iframe-popup-no-scroll<?endif ?> <? $APPLICATION->ShowProperty('BodyClass'); ?>" onload="window.top.BX.onCustomEvent(window.top, 'crmEntityIframeLoad');" onunload="window.top.BX.onCustomEvent(window.top, 'crmEntityIframeUnload');">

	<div class="task-iframe-workarea" id="tasks-content-outer">
	<div class="task-iframe-sidebar"><? $APPLICATION->ShowViewContent("sidebar"); ?></div>
	<div class="task-iframe-content"><?
}
if(!Bitrix\Crm\Integration\Bitrix24Manager::isAccessEnabled($arResult['ENTITY_TYPE_ID']))
{
	$APPLICATION->IncludeComponent('bitrix:bitrix24.business.tools.info', '', array());
}
else
{
	if($arResult['IS_PERMITTED'])
	{
		$typePrefix = strtolower($arResult['ENTITY_TYPE_NAME']);
		$APPLICATION->IncludeComponent(
			"bitrix:crm.{$typePrefix}.show",
			'',
			array('ELEMENT_ID' => $arResult['ENTITY_ID']),
			$component,
			array('HIDE_ICONS' => 'Y')
		);
	}
}


if($arResult['IFRAME'])
{
			?></div>
		</div>
		</body>
	</html><?
	require($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');
	die();
}
