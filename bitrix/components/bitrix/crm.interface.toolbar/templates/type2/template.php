<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)die();
global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
$toolbarID =  $arParams['TOOLBAR_ID'];
?><div class="bx-crm-view-menu" id="<?=htmlspecialcharsbx($toolbarID)?>"><?

$moreItems = array();
$enableMoreButton = false;
foreach($arParams['BUTTONS'] as $item):
	if(!$enableMoreButton && isset($item['NEWBAR']) && $item['NEWBAR'] === true):
		$enableMoreButton = true;
		continue;
	endif;

	if($enableMoreButton):
		$moreItems[] = $item;
		continue;
	endif;

	$link = isset($item['LINK']) ? $item['LINK'] : '#';
	$text = isset($item['TEXT']) ? $item['TEXT'] : '';
	$title = isset($item['TITLE']) ? $item['TITLE'] : '';

	$iconClassName = 'bx-context-button';
	if(isset($item['ICON']))
	{
		$iconClassName .= ' crm-'.$item['ICON'];
	}

	$onclick = isset($item['ONCLICK']) ? $item['ONCLICK'] : '';
	?><a class="<?=$iconClassName !== '' ? htmlspecialcharsbx($iconClassName) : ''?>" href="<?=htmlspecialcharsbx($link)?>" title="<?=htmlspecialcharsbx($title)?>" <?=$onclick !== '' ? ' onclick="'.htmlspecialcharsbx($onclick).'; return false;"' : ''?>><span class="bx-context-button-icon"></span><span><?=htmlspecialcharsbx($text)?></span></a><?
endforeach;
if(!empty($moreItems)):
	?><a class="bx-context-button crm-btn-more">
		<span class="bx-context-button-icon"></span>
		<span><?=htmlspecialcharsbx(GetMessage('CRM_INTERFACE_TOOLBAR_BTN_MORE'))?></span>
	</a>
	<script type="text/javascript">
		BX.ready(
			function()
			{
				BX.InterfaceToolBar.create(
					"<?=CUtil::JSEscape($toolbarID)?>",
					BX.CrmParamBag.create(
						{
							"containerId": "<?=CUtil::JSEscape($toolbarID)?>",
							"moreButtonClassName": "crm-btn-more",
							"items": <?=CUtil::PhpToJSObject($moreItems)?>
						}
					)
				);
			}
		);
	</script>
<?
endif;
?></div>
