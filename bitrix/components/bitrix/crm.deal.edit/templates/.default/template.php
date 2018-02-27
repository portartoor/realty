<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

global $APPLICATION;

$arTabs = array();
$arTabs[] = array(
	'id' => 'tab_1',
	'name' => GetMessage('CRM_TAB_1'),
	'title' => GetMessage('CRM_TAB_1_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_1']
);

$productFieldset = array();
foreach($arTabs[0]['fields'] as $k => &$field):
	if($field['id'] === 'section_product_rows'):
		$productFieldset['NAME'] = $field['name'];
		unset($arTabs[0]['fields'][$k]);
	endif;

	if($field['id'] === 'PRODUCT_ROWS'):
		$productFieldset['HTML'] = $field['value'];
		unset($arTabs[0]['fields'][$k]);
		break;
	endif;

endforeach;
unset($field);

// Form options housekeeping
CCrmComponentHelper::SynchronizeFormSettings($arResult['FORM_ID'], CCrmDeal::GetUserFieldEntityID());

$elementID = isset($arResult['ELEMENT']['ID']) ? $arResult['ELEMENT']['ID'] : 0;
$APPLICATION->IncludeComponent(
	'bitrix:crm.interface.form',
	'edit',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'GRID_ID' => $arResult['GRID_ID'],
		'TABS' => $arTabs,
		'EMPHASIZED_HEADERS' => array('TITLE'),
		'FIELD_SETS' => array($productFieldset),
		'BUTTONS' => array(
			'standard_buttons' => true,
			'back_url' => $arResult['BACK_URL'],
			'custom_html' => '<input type="hidden" name="deal_id" value="'.$elementID.'"/>'.$arResult['FORM_CUSTOM_HTML']
		),
		'IS_NEW' => $elementID <= 0,
		'DATA' => $arResult['ELEMENT'],
		'SHOW_SETTINGS' => 'Y'
	)
);
?>
<script type="text/javascript">
	BX.ready(
		function()
		{
			var formID = 'form_' + '<?= $arResult['FORM_ID'] ?>';
			var prodEditor = BX.CrmProductEditor.getDefault();

			BX.addCustomEvent(
				prodEditor,
				'sumTotalChange',
				function(ttl)
				{
					var el = BX.findChild(BX(formID), { 'tag':'input', 'attr':{ 'name': 'OPPORTUNITY' } }, true, false);
					if(el)
					{
						el.value = ttl;
					}
				}
			);

			BX.bind(
				BX.findChild(BX(formID), { 'tag':'select', 'attr':{ 'name': 'CURRENCY_ID' } }, true, false),
				'change',
				function()
				{
					var currencyEl = BX.findChild(BX(formID), { 'tag':'select', 'attr':{ 'name': 'CURRENCY_ID' } }, true, false);
					var opportunityEl = BX.findChild(BX(formID), { 'tag':'input', 'attr':{ 'name': 'OPPORTUNITY' } }, true, false);

					var currencyId = currencyEl.value;
					var prevCurrencyId = prodEditor.getCurrencyId();

					prodEditor.setCurrencyId(currencyId);

					var oportunity = opportunityEl.value.length > 0 ? parseFloat(opportunityEl.value) : 0;
					if(isNaN(oportunity))
					{
						oportunity = 0;
					}

					if(prodEditor.getProductCount() == 0 && oportunity !== 0)
					{
						prodEditor.convertMoney(
							parseFloat(opportunityEl.value),
							prevCurrencyId,
							currencyId,
							function(sum)
							{
								opportunityEl.value = sum;
							}
						);
					}
				}
			);
		}
	);
</script>