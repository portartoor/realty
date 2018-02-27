<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
	die();

CCrmComponentHelper::RegisterScriptLink('/bitrix/js/crm/common.js');

if($arResult['ENABLE_CONTROL_PANEL'])
{
	$APPLICATION->IncludeComponent(
		'bitrix:crm.control_panel',
		'',
		array(
			'ID' => 'CONFIG',
			'ACTIVE_ITEM_ID' => '',
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

$arTabs[] = array(
	'id' => 'tab_activity_config',
	'name' => GetMessage('CRM_TAB_ACTIVITY_CONFIG'),
	'title' => GetMessage('CRM_TAB_ACTIVITY_CONFIG_TITLE'),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_activity_config']
);

$arTabs[] = array(
	'id' => 'tab_format',
	'name' => GetMessage('CRM_TAB_FORMAT'),
	'title' => GetMessage('CRM_TAB_FORMAT_TITLE'),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_format']
);

$arTabs[] = array(
	'id' => 'tab_config',
	'name' => GetMessage('CRM_TAB_CONFIG'),
	'title' => GetMessage('CRM_TAB_CONFIG_TITLE'),
	'icon' => '',
	'fields' => $arResult['FIELDS']['tab_config']
);

$APPLICATION->IncludeComponent(
	'bitrix:main.interface.form',
	'',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'TABS' => $arTabs,
		'BUTTONS' => array(
			'standard_buttons' =>  true,
			'back_url' => $arResult['BACK_URL']
		),
		'DATA' => $arResult['ELEMENT'],
		'SHOW_SETTINGS' => 'N'
	),
	$component,
	array('HIDE_ICONS' => 'Y')
);
if(SITE_TEMPLATE_ID === 'bitrix24'):
?><script type="text/javascript">
	BX.ready(
			function()
			{
				BX.CrmInterfaceFormUtil.disableThemeSelection("<?= CUtil::JSEscape($arResult["FORM_ID"])?>");
			}
	);
</script><?
endif;
?><script type="text/javascript">
	BX.ready(
			function()
			{
			var form = BX('form_<?=CUtil::JSEscape($arResult['FORM_ID'])?>');
			if(!form)
			{
				return;
			}

			var customFormatID = <?=CUtil::JSEscape(CCrmCallToUrl::Custom)?>;
			var formatSelector = BX.findChild(form, { 'tag': 'SELECT', 'attribute': { 'name': 'CALLTO_FORMAT' } }, true);
			BX.bind(
				formatSelector,
				'change',
				BX.delegate(
					function()
					{
						var show = formatSelector.value == customFormatID;
						BX.CrmInterfaceFormUtil.showFormRow(show, BX.findChild(form, { 'tag': 'INPUT', 'attribute': { 'name': 'CALLTO_URL_TEMPLATE' } }, true));
						BX.CrmInterfaceFormUtil.showFormRow(show, BX.findChild(form, { 'tag': 'TEXTAREA', 'attribute': { 'name': 'CALLTO_CLICK_HANDLER' } }, true));
					}
				)
			);

			if(formatSelector.value != customFormatID)
			{
				BX.CrmInterfaceFormUtil.showFormRow(false, BX.findChild(form, { 'tag': 'INPUT', 'attribute': { 'name': 'CALLTO_URL_TEMPLATE' } }, true));
				BX.CrmInterfaceFormUtil.showFormRow(false, BX.findChild(form, { 'tag': 'TEXTAREA', 'attribute': { 'name': 'CALLTO_CLICK_HANDLER' } }, true));
			}
			}
	);
</script>