<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

use Bitrix\Crm\Counter\EntityCounterType;

$guid = $arResult['GUID'];
$prefix = strtolower($guid);
$caption = $arResult['ENTITY_CAPTION'];
$total = isset($arResult['TOTAL']) ? $arResult['TOTAL'] : '0';
$data = isset($arResult['DATA']) ? $arResult['DATA'] : array();
$containerID = "{$prefix}_container";

$showStub = $arResult['SHOW_STUB'] ? $arResult['SHOW_STUB'] : false;

if($showStub)
{
	?><div class="crm-counter">
		<div class="crm-counter-title"><?=GetMessage('CRM_COUNTER_STUB')?></div>
	</div><?
}
else
{
	?><div id="<?=htmlspecialcharsbx($containerID)?>" class="crm-counter"><?
		if($total > 0)
		{
			?><div class="crm-counter-title">
			<span class="crm-counter-total"><?=$total?></span>
			<span class="crm-page-name"><?=htmlspecialcharsbx($caption)?> - </span><?
			for($i = 0, $l = count($data); $i < $l; $i++)
			{
				$item = $data[$i];
				$typeID = isset($item['TYPE_ID']) ? $item['TYPE_ID'] : 0;
				$typeName = isset($item['TYPE_NAME']) ? $item['TYPE_NAME'] : '';
				$value = isset($item['VALUE']) ? $item['VALUE'] : '';
				$url = isset($item['URL']) ? $item['URL'] : '#';

				$className = 'crm-counter-link';
				if($typeName === EntityCounterType::IDLE_NAME)
				{
					$className = 'crm-counter-nodate';
				}elseif($typeName === EntityCounterType::OVERDUE_NAME)
				{
					$className = 'crm-counter-overdue';
				}elseif($typeName === EntityCounterType::PENDING_NAME)
				{
					$className = 'crm-counter-pending';
				}
				?><a data-type-id="<?=$typeID?>" href="<?=htmlspecialcharsbx($url)?>"
					 class="crm-counter-container <?=$className?>">
				<?=GetMessage("CRM_COUNTER_TYPE_{$typeName}", array('#VALUE#' => $value))?>
				</a><?
			}
			?></div><?
		}
		else
		{
			?><div class="crm-counter-title">
			<div class="crm-page-nocounter"><?=$arResult['STUB_MESSAGE']?></div>
			</div><?
		}
		?>
	</div>

	<script type="text/javascript">
		BX.ready(
			function()
			{
				BX.CrmEntityCounterPanel.create(
					"<?=CUtil::JSEscape($guid)?>",
					{
						containerId: "<?=CUtil::JSEscape($containerID)?>"
					}
				);
			}
		);
	</script><?
}