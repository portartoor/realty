<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
global $APPLICATION;
$APPLICATION->SetAdditionalCSS('/bitrix/js/crm/css/crm.css');
CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/common.js');
CJSCore::Init(array('popup', 'date'));

$ID = $arResult['ID'];
$IDLc = strtolower($ID);
$items = isset($arResult['ITEMS']) ? $arResult['ITEMS'] : array();
$activeItemID =  isset($arResult['ACTIVE_ITEM_ID']) ? $arResult['ACTIVE_ITEM_ID'] : '';
$containerID = "crm_ctrl_panel_{$IDLc}";
$itemContainerPrefix = "crm_ctrl_panel_item_{$IDLc}_";
$itemInfos = array();

$enableSearch = isset($arResult['ENABLE_SEARCH']) ? $arResult['ENABLE_SEARCH'] : true;

?>
<div id="<?=htmlspecialcharsbx($containerID)?>" class="crm-header">
	<span class="crm-menu-wrap">
		<table class="crm-menu-table">
			<tbody>
				<tr><?
				foreach($items as &$item):
					$itemID = isset($item['ID']) ? $item['ID'] : '';
					$isActive = $itemID === $activeItemID;
					$url = isset($item['URL']) ? $item['URL'] : '#';
					$icon = isset($item['ICON']) ? strtolower($item['ICON']) : '';
					$name = isset($item['NAME']) ? $item['NAME'] : $itemID;
					$title = isset($item['TITLE']) ? $item['TITLE'] : '';
					$counter = isset($item['COUNTER']) ? intval($item['COUNTER']) : 0;

					$itemInfo = array(
						'id' => $itemID,
						'isActive' => $isActive,
						'url' => $url,
						'actions' => array(),
						'childItems' => array()
					);

					$actions = isset($item['ACTIONS']) ? $item['ACTIONS'] : array();
					foreach($actions as &$action)
					{
						$actionID = isset($action['ID']) ? $action['ID'] : '';
						if($actionID === '')
						{
							continue;
						}


						$itemInfo['actions'][] = array(
							'id' => $actionID,
							'url' => isset($action['URL']) ? $action['URL'] : '',
							'script' => isset($action['SCRIPT']) ? $action['SCRIPT'] : ''
						);
					}
					unset($action);

					$childItems = isset($item['CHILD_ITEMS']) ? $item['CHILD_ITEMS'] : array();
					foreach($childItems as &$childItem)
					{
						$childItemID = isset($childItem['ID']) ? $childItem['ID'] : '';
						if($childItemID === '')
						{
							continue;
						}

						$itemInfo['childItems'][] = array(
							'id' => $childItemID,
							'name' => isset($childItem['NAME']) ? $childItem['NAME'] : '',
							'icon' => isset($childItem['ICON']) ? $childItem['ICON'] : '',
							'url' => isset($childItem['URL']) ? $childItem['URL'] : ''
						);
					}
					unset($childItem);

					$itemInfos[] = &$itemInfo;
					unset($itemInfo);
					?>
					<td class="crm-menu-cell">
						<div class="crm-menu-item-wrap" id="<?=htmlspecialcharsbx("{$itemContainerPrefix}{$itemID}")?>">
							<a href="<?=htmlspecialcharsbx($url)?>" class="crm-menu-item<?=$icon !== '' ? ' crm-menu-'.htmlspecialcharsbx($icon) : ''?><?=$isActive ? ' crm-menu-item-active' : ''?>" title="<?=htmlspecialcharsbx($title)?>">
								<span class="crm-menu-icon"></span>
								<span class="crm-menu-name"><?=htmlspecialcharsbx($name)?></span><?
								if($counter > 0):
								?><span class="crm-menu-icon-counter"><?=$counter <= 99 ? $counter : '99+' ?></span><?
								endif;
							?></a>
						</div>
					</td>
				<?endforeach;
				unset($item);
				?></tr>
			</tbody>
		</table>
	</span><?
	if($enableSearch):
		$searchContainerID = "crm_ctrl_panel_{$IDLc}_search";
		$searchInputID = "crm_ctrl_panel_{$IDLc}_search_input";
	?><span id="<?=htmlspecialcharsbx($searchContainerID)?>" class="crm-search-block">
		<form class="crm-search" action="<?=htmlspecialcharsbx($arResult['SEARCH_PAGE_URL'])?>" method="get">
			<span class="crm-search-btn"></span>
			<span class="crm-search-inp-wrap"><input id="<?=htmlspecialcharsbx($searchInputID)?>" class="crm-search-inp" name="q" type="text" autocomplete="off" placeholder="<?=htmlspecialcharsbx(GetMessage('CRM_CONTROL_PANEL_SEARCH_PLACEHOLDER'))?>"/></span>
			<input type="hidden" name="where" value="crm" /><?
			$APPLICATION->IncludeComponent(
				'bitrix:search.title',
				'backend',
				array(
					'NUM_CATEGORIES' => 1,
					'CATEGORY_0_TITLE' => 'CRM',
					'CATEGORY_0' => array(0 => 'crm'),
					'PAGE' => $arResult['PATH_TO_SEARCH_PAGE'],
					'CONTAINER_ID' => $searchContainerID,
					'INPUT_ID' => $searchInputID,
					'SHOW_INPUT' => 'N'
				),
				$component,
				array('HIDE_ICONS'=>true)
			);
		?></form>
	</span>
	<?endif;?>
	<span class="crm-menu-shadow">
		<span class="crm-menu-shadow-right">
			<span class="crm-menu-shadow-center"></span>
		</span>
	</span>
</div>

<script type="text/javascript">
	BX.ready(
			function()
			{
				BX.CrmControlPanel.create(
						"<?=CUtil::JSEscape($ID)?>",
						BX.CrmParamBag.create(
							{
								"containerId":"<?=CUtil::JSEscape($containerID)?>",
								"itemContainerPrefix":"<?=CUtil::JSEscape($itemContainerPrefix)?>",
								"itemInfos": <?=CUtil::PhpToJSObject($itemInfos)?>
							}
						)
				);
			}
	);
</script>