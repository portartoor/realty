<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}
/** @var array $arParams */

CJSCore::RegisterExt('popup_menu', array('js' => array('/bitrix/js/main/popup_menu.js')));

$toolbarId = $arParams['TOOLBAR_ID'];

$items = array();
$moreItems = array();
$enableMoreButton = false;

foreach($arParams['BUTTONS'] as $item)
{
	if(!$enableMoreButton && isset($item['NEWBAR']) && $item['NEWBAR'] === true)
	{
		$enableMoreButton = true;
		continue;
	}

	if($enableMoreButton)
	{
		$moreItems[] = $item;
	}
	else
	{
		$items[] = $item;
	}
}

$this->SetViewTarget('inside_pagetitle', 10000);

?><div id="<?=htmlspecialcharsbx($toolbarId)?>" class="pagetitle-container pagetitle-align-right-container"><?
if(!empty($moreItems))
{
	?><div class="crm-contact-menu-settings">
		<div class="crm-contact-menu-settings-inner">
			<div class="webform-small-button webform-small-button-transparent task-list-toolbar-lightning crm-contact-menu-settings-icon">
				<span class="crm-contact-menu-settings-icon-item"></span>
			</div>
		</div>
	</div>
	<script type="text/javascript">
		BX.ready(
			function ()
			{
				BX.InterfaceToolBar.create(
					"<?=CUtil::JSEscape($toolbarId)?>",
					BX.CrmParamBag.create(
						{
							"containerId": "<?=CUtil::JSEscape($toolbarId)?>",
							"items": <?=CUtil::PhpToJSObject($moreItems)?>,
							"moreButtonClassName": "crm-contact-menu-settings-icon"
						}
					)
				);
			}
		);
	</script><?
}
foreach($items as $item)
{
	$type = isset($item['TYPE']) ? $item['TYPE'] : '';
	$text = isset($item['TEXT']) ? htmlspecialcharsbx($item['TEXT']) : '';
	$title = isset($item['TITLE']) ? htmlspecialcharsbx($item['TITLE']) : '';
	$link = isset($item['LINK']) ? htmlspecialcharsbx($item['LINK']) : '#';
	$icon = isset($item['ICON']) ? htmlspecialcharsbx($item['ICON']) : '';
	$onClick = isset($item['ONCLICK']) ? htmlspecialcharsbx($item['ONCLICK']) : '';

	if($type === 'crm-context-menu')
	{
		$menuItems = isset($item['ITEMS']) && is_array($item['ITEMS']) ? $item['ITEMS'] : array();

		?><div class="webform-small-button webform-small-button-blue webform-button-icon-triangle-down crm-btn-toolbar-menu"<?=$onClick !== '' ? " onclick=\"{$onClick}; return false;\"" : ''?>>
			<span class="webform-small-button-text"><?=$text?></span>
			<span class="webform-button-icon-triangle"></span>
		</div><?

		if(!empty($menuItems))
		{
			?><script type="text/javascript">
				BX.ready(
					function()
					{
						BX.InterfaceToolBar.create(
							"<?=CUtil::JSEscape($toolbarId)?>",
							BX.CrmParamBag.create(
								{
									"containerId": "<?=CUtil::JSEscape($toolbarId)?>",
									"prefix": "",
									"menuButtonClassName": "crm-btn-toolbar-menu",
									"items": <?=CUtil::PhpToJSObject($menuItems)?>
								}
							)
						);
					}
				);
			</script><?
		}
	}
	else
	{
		?><a href="<?=$link?>" title="<?=$title?>"<?=$onClick !== '' ? " onclick=\"{$onClick}; return false;\"" : ''?>>
		<span class="webform-small-button webform-small-button-blue bx24-top-toolbar-add<?=$icon !== '' ? " {$icon}" : ''?>">
			<?=$text?>
		</span>
		</a><?
	}
}
?></div><?
$this->EndViewTarget();