<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;

$gridId = strlen($arParams['GRID_ID']) > 0 ? strtolower($arParams['GRID_ID']) : 'invoice';
$navContainerId = "{$gridId}_list_nav";
$activeNavBarItemElementId = '';
$activeNavBarItemName = '';
$barItemQty = 0;
$items = array();
foreach($arParams['NAVIGATION_ITEMS'] as $barItem)
{
	$barItemQty++;
	$barItemId = isset($barItem['id']) ? $barItem['id'] : $barItemQty;
	$barItemElementId = strtolower("{$gridId}_{$barItemId}");
	$barItemName = isset($barItem['name']) ? $barItem['name'] : $barItemId;
	$barItemUrl = isset($barItem['url']) ? $barItem['url'] : '';

	$barItemConfig = array(
		'name' => $barItemName,
		'id' => $barItemElementId,
		'url' => $barItemUrl
	);

	if(isset($barItem['active']) && $barItem['active'])
	{
		$barItemConfig['active'] = true;
		$activeNavBarItemElementId = $barItemElementId;
		$activeNavBarItemName = $barItemName;
	}
	$items[] = $barItemConfig;
}

if (empty($items))
	return;
?>

<div class="pagetitle-container pagetitle-flexible-space pagetitle-align-left-container">
	<div id="<?=htmlspecialcharsbx($navContainerId)?>" class="crm-interface-toolbar-button-button-container">
		<div id="<?=htmlspecialcharsbx($activeNavBarItemElementId)?>" class="crm-interface-toolbar-button" style="margin:18px 0; float: right">
			<span class="crm-interface-toolbar-button-item">
				<?=htmlspecialcharsbx($activeNavBarItemName)?>
			</span>
			<span class="crm-interface-toolbar-button-icon crm-interface-toolbar-button-icon-arrow-down"></span>
		</div>
	</div>

	<script type="text/javascript">
		BX.ready(
			function()
			{
				BX.CrmInvoiceListSwitcher.create(
					"<?=CUtil::JSEscape($navContainerId)?>",
					{
						items: <?=CUtil::PhpToJSObject($items)?>,
						containerId: "<?=CUtil::JSEscape($navContainerId)?>",
						selectorButtonId: "<?=CUtil::JSEscape($activeNavBarItemElementId)?>"
					}
				);
			}
		);
	</script>
</div>




