<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
if (!empty($arResult['BUTTONS']))
{
	$type = $arParams['TYPE'];
	$APPLICATION->IncludeComponent(
		'bitrix:crm.interface.toolbar',
		$type === 'list' ?  (SITE_TEMPLATE_ID === 'bitrix24' ? 'title' : '') : 'type2',
		array(
			'TOOLBAR_ID' => $arResult['TOOLBAR_ID'],
			'BUTTONS' => $arResult['BUTTONS']
		),
		$component,
		array('HIDE_ICONS' => 'Y')
	);
}

if(isset($arResult['SONET_SUBSCRIBE']) && is_array($arResult['SONET_SUBSCRIBE'])):
	$subscribe = $arResult['SONET_SUBSCRIBE'];
?><script type="text/javascript">
BX.ready(
	function()
	{
		BX.CrmSonetSubscription.create(
			"<?=CUtil::JSEscape($subscribe['ID'])?>",
			{
				"entityType": "<?=CCrmOwnerType::DealName?>",
				"serviceUrl": "<?=CUtil::JSEscape($subscribe['SERVICE_URL'])?>",
				"actionName": "<?=CUtil::JSEscape($subscribe['ACTION_NAME'])?>"
			}
		);
	}
);
</script><?
endif;

if(isset($arResult['CATEGORY_SELECTOR']) && is_array($arResult['CATEGORY_SELECTOR'])):
	$categorySelector = $arResult['CATEGORY_SELECTOR'];
?><script type="text/javascript">
	BX.ready(
		function()
		{
			BX.CrmDealCategory.infos = <?=CUtil::PhpToJSObject($categorySelector['INFOS'])?>;
			BX.CrmDealCategorySelector.messages =
			{
				"create": "<?=CUtil::JSEscape($categorySelector['MESSAGES']['CREATE'])?>"
			};

			BX.CrmDealCategorySelector.create(
				"<?=CUtil::JSEscape($categorySelector['ID'])?>",
				{
					"createUrl": "<?=CUtil::JSEscape($categorySelector['CREATE_URL'])?>",
					"categoryListUrl": "<?=CUtil::JSEscape($categorySelector['CATEGORY_LIST_URL'])?>",
					"categoryCreateUrl": "<?=CUtil::JSEscape($categorySelector['CATEGORY_CREATE_URL'])?>",
					"canCreateCategory": <?=$categorySelector['CAN_CREATE_CATEGORY'] ? 'true' : 'false'?>
				}
			);
		}
	);
</script><?
endif;
