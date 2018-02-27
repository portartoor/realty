<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

global $APPLICATION;
$APPLICATION->AddHeadScript('/bitrix/js/crm/common.js');
$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/crm-entity-show.css");
if(SITE_TEMPLATE_ID === 'bitrix24')
{
	$APPLICATION->SetAdditionalCSS("/bitrix/themes/.default/bitrix24/crm-entity-show.css");
}

if($arResult['ENABLE_CONTROL_PANEL'])
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		array(
			'ID' => 'DEAL_FUNNEL',
			'ACTIVE_ITEM_ID' => 'DEAL_FUNNEL',
			'PATH_TO_COMPANY_LIST' => isset($arParams['PATH_TO_COMPANY_LIST']) ? $arParams['PATH_TO_COMPANY_LIST'] : '',
			'PATH_TO_COMPANY_EDIT' => isset($arParams['PATH_TO_COMPANY_EDIT']) ? $arParams['PATH_TO_COMPANY_EDIT'] : '',
			'PATH_TO_CONTACT_LIST' => isset($arParams['PATH_TO_CONTACT_LIST']) ? $arParams['PATH_TO_CONTACT_LIST'] : '',
			'PATH_TO_CONTACT_EDIT' => isset($arParams['PATH_TO_CONTACT_EDIT']) ? $arParams['PATH_TO_CONTACT_EDIT'] : '',
			'PATH_TO_DEAL_LIST' => isset($arParams['PATH_TO_DEAL_LIST']) ? $arParams['PATH_TO_DEAL_LIST'] : '',
			'PATH_TO_DEAL_EDIT' => isset($arParams['PATH_TO_DEAL_EDIT']) ? $arParams['PATH_TO_DEAL_EDIT'] : '',
			'PATH_TO_LEAD_LIST' => isset($arParams['PATH_TO_LEAD_LIST']) ? $arParams['PATH_TO_LEAD_LIST'] : '',
			'PATH_TO_LEAD_EDIT' => isset($arParams['PATH_TO_LEAD_EDIT']) ? $arParams['PATH_TO_LEAD_EDIT'] : '',
			'PATH_TO_REPORT_LIST' => isset($arParams['PATH_TO_REPORT_LIST']) ? $arParams['PATH_TO_REPORT_LIST'] : '',
			'PATH_TO_DEAL_FUNNEL' => isset($arParams['PATH_TO_DEAL_FUNNEL']) ? $arParams['PATH_TO_DEAL_FUNNEL'] : '',
			'PATH_TO_EVENT_LIST' => isset($arParams['PATH_TO_EVENT_LIST']) ? $arParams['PATH_TO_EVENT_LIST'] : '',
			'PATH_TO_PRODUCT_LIST' => isset($arParams['PATH_TO_PRODUCT_LIST']) ? $arParams['PATH_TO_PRODUCT_LIST'] : ''
		),
		$component
	);
}

for ($i=0, $ic=sizeof($arResult['FILTER']); $i < $ic; $i++)
{
	$filterField = $arResult['FILTER'][$i];
	$filterID = $filterField['id'];
	$filterType = $filterField['type'];
	$enable_settings = $filterField['enable_settings'];

	if ($filterType === 'user')
	{
		$userID = (isset($_REQUEST[$arResult['FILTER'][$i]['id']]))?intval($_REQUEST[$arResult['FILTER'][$i]['id']][0]):0;
		$userName = $userID > 0 ? CCrmViewHelper::GetFormattedUserName($userID) : '';

		ob_start();

		CCrmViewHelper::RenderUserCustomSearch(
			array(
				'ID' => "{$filterID}_SEARCH",
				'SEARCH_INPUT_ID' => "{$filterID}_NAME",
				'SEARCH_INPUT_NAME' => "{$filterID}_name",
				'DATA_INPUT_ID' => $filterID,
				'DATA_INPUT_NAME' => $filterID,
				'COMPONENT_NAME' => "{$filterID}_SEARCH",
				'SITE_ID' => SITE_ID,
				'NAME_FORMAT' => $arParams['NAME_TEMPLATE'],
				'USER' => array('ID' => $userID, 'NAME' => $userName),
				'DELAY' => 100
			)
		);

		$arResult['FILTER'][$i]['value'] = ob_get_clean();
		$arResult['FILTER'][$i]['type'] = 'custom';
	}
}

$arColor = array(
	'#d73434','#df8328','#e8c819','#64c13a','#509979','#c4c777','#226a9d','#bb2ab6','#aaaaaa','#75bcff','#f4a8e7',
	'#d5a8f4','#a8aaf4','#94c8ec','#91e9e8','#91e9c3','#90e76a','#ffe241','#ffb771','#f19292');
$i = 0;
$ic = 0;
$arResult['GRID_DATA'] = array();
$arResult['GRID_DATA_NO'] = array();
$bafterWON = false;
foreach ($arResult['FUNNEL'] as $aData){
	foreach ($arResult['CURRENCY_LIST'] as $k => $v)
		$aData[$k] = CCrmCurrency::MoneyToString($aData['OPPORTUNITY_FUNNEL_'.$k], $k, '<nobr>#</nobr>');

	$str = '';
	if ($i == 0)
		$str = '<div style="margin:auto; width: 250px"></div>';

	$aData['FUNNEL'] = $str.'<div style="margin:auto; width: '.$aData['PROCENT'].'%; height: 20px; background-color: '.$arColor[$ic].'"></div>';
	$aData['PROCENT'] = $aData['PROCENT'].'%';

	$arResult['GRID_DATA'.($bafterWON ? '_NO' : '')][] = array(
		'id' => $i++,
		'data' => $aData
	);

	if ($aData['STAGE_ID'] == 'WON')
	{
		$bafterWON = true;
		$i = 0;
	}
	$ic++;
	if ($ic > 20)
		$ic = 0;
}

$containerID = strtolower($arResult['GRID_ID']).'_container';
$typeSelectContainerID = strtolower($arResult['GRID_ID']).'_type_select_container';
$typeSelectFormID = strtolower($arResult['GRID_ID']).'_type_selector';
$typeInputID = strtolower($arResult['GRID_ID']).'_type_id';
?><div id="<?=htmlspecialcharsbx($containerID)?>" class="crm-deal-funnel-wrapper">
<form method="POST"  action="<?=POST_FORM_ACTION_URI?>" name="<?=htmlspecialcharsbx($typeSelectFormID)?>" id="<?=htmlspecialcharsbx($typeSelectFormID)?>">
<?=bitrix_sessid_post();?>
	<input type="hidden" name="FUNNEL_TYPE" id="<?=htmlspecialcharsbx($typeInputID)?>" value="<?=htmlspecialcharsbx($arResult['FUNNEL_TYPE'])?>"/>
</form>

<div class="crm-deal-funnel-wrapper crm-deal-funnel-wrapper-won">
<div class="crm-deal-funnel-title"><?=htmlspecialcharsbx(GetMessage("DEAL_STAGES_WON"))?></div><?
$toolbarID = strtolower($arResult['GRID_ID']).'_toolbar';
$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.toolbar',
	'',
	array(
		'TOOLBAR_ID' => $toolbarID,
		'BUTTONS' => array(
			array(
				'TEXT' => GetMessage('CRM_DEAL_FUNNEL_SHOW_FILTER_SHORT'),
				'TITLE' => GetMessage('CRM_DEAL_FUNNEL_SHOW_FILTER'),
				'ICON' => 'crm-filter-light-btn',
				'ALIGNMENT' => 'right',
				'ONCLICK' => "BX.InterfaceGridFilterPopup.toggle('{$arResult['GRID_ID']}', this)"
			)
		)
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);
$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.grid',
	'',
	array(
		'GRID_ID' => $arResult['GRID_ID'],
		'HEADERS' => $arResult['HEADERS'],
		'ROWS' => $arResult['GRID_DATA'],
		'EDITABLE' => 'N',
		'ACTION_ALL_ROWS' => false,
		'AJAX_MODE' => 'N',
		'FILTER' => $arResult['FILTER'],
		'FILTER_TEMPLATE' => 'popup',
		'FILTER_PRESETS' => $arResult['FILTER_PRESETS']
	),
	$component
);
?></div>
<div class="crm-deal-funnel-wrapper crm-deal-funnel-wrapper-lose">
<div class="crm-deal-funnel-title"><?=htmlspecialcharsbx(GetMessage("DEAL_STAGES_LOSE"))?></div><?
$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.grid',
	'',
	array(
		'GRID_ID' => $arResult['GRID_ID'].'_NO',
		'HEADERS' => $arResult['HEADERS'],
		'ROWS' => $arResult['GRID_DATA_NO'],
		'EDITABLE' => 'N',
		'ACTION_ALL_ROWS' => false,
		'AJAX_MODE' => 'N'
	),
	$component
);
?></div>
</div>
<?if($arResult['ALLOW_FUNNEL_TYPE_CHANGE'] === 'Y'):?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			var typeSelector = BX.CrmSelector.create(
					'<?= CUtil::JSEscape(strtolower($arResult['GRID_ID']).'_type_selector') ?>',
					{
						'container': BX('<?=CUtil::JSEscape($toolbarID)?>'),
						'title': '<?=GetMessageJS('CRM_DEAL_FUNNEL_TYPE_SELECTOR_TITLE') ?>',
						'selectedValue': '<?= CUtil::JSEscape($arResult['FUNNEL_TYPE'])?>',
						'items': <?=CUtil::PhpToJSObject($arResult['FUNNEL_TYPE_VALUES'])?>,
						'layout': { 'position': 'first' }
					}
			);
			typeSelector.layout();
			typeSelector.addOnSelectListener(
					function(selector, item)
					{
						var input = BX('<?=CUtil::JSEscape($typeInputID)?>');
						var form = BX('<?=CUtil::JSEscape($typeSelectFormID)?>');
						if(item && input && form)
						{
							input.value = item.getValue();
							BX.submit(form);
						}
					}
			);
		}
	);
</script>
<?endif;?>
