<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

/** @var array $arResult */

$siteDir = rtrim(SITE_DIR, '/');

/* Menu items */
$tabs = array();
$tabs['where_to_begin'] = GetMessage("CRM_CONFIGS_TAB_WHERE_TO_BEGIN");
$tabs['settings_forms_and_reports'] = GetMessage("CRM_CONFIGS_TAB_SETTINGS_FORMS_AND_REPORTS");
//$tabs['creation_on_the_basis'] = GetMessage("CRM_CONFIGS_TAB_CREATION_ON_THE_BASIS");
$tabs['printed_forms_of_documents'] = GetMessage("CRM_CONFIGS_TAB_PS_LIST");
$tabs['rights'] = GetMessage("CRM_CONFIGS_TAB_RIGHTS");
$tabs['automation'] = GetMessage("CRM_CONFIGS_TAB_AUTOMATION");
$tabs['work_with_mail'] = GetMessage("CRM_CONFIGS_TAB_WORK_WITH_MAIL");
$tabs['integration'] = GetMessage("CRM_CONFIGS_TAB_INTEGRATION");
if($arResult['BITRIX24'])
	$tabs['apps'] = GetMessage("CRM_CONFIGS_TAB_APPS");
$tabs['other'] = GetMessage("CRM_CONFIGS_TAB_OTHER");

/* Settings items */
$items = array();
if($arResult['PERM_CONFIG'])
{
	$items['tab_content_where_to_begin']['STATUS']['URL'] = $siteDir.'/crm/configs/status/';
	$items['tab_content_where_to_begin']['STATUS']['ICON_CLASS'] = 'img-book';
	$items['tab_content_where_to_begin']['STATUS']['NAME'] = GetMessage("CRM_CONFIGS_STATUS");
	$items['tab_content_where_to_begin']['CURRENCY']['URL'] = $siteDir.'/crm/configs/currency/';
	$items['tab_content_where_to_begin']['CURRENCY']['ICON_CLASS'] = 'img-curr';
	$items['tab_content_where_to_begin']['CURRENCY']['NAME'] = GetMessage("CRM_CONFIGS_CURRENCY");
	$items['tab_content_where_to_begin']['LOCATIONS']['URL'] = $siteDir.'/crm/configs/locations/';
	$items['tab_content_where_to_begin']['LOCATIONS']['ICON_CLASS'] = 'img-location';
	$items['tab_content_where_to_begin']['LOCATIONS']['NAME'] = GetMessage("CRM_CONFIGS_LOCATIONS");
	$items['tab_content_where_to_begin']['TAX']['URL'] = $siteDir.'/crm/configs/tax/';
	$items['tab_content_where_to_begin']['TAX']['ICON_CLASS'] = 'img-taxes';
	$items['tab_content_where_to_begin']['TAX']['NAME'] = GetMessage("CRM_CONFIGS_TAX");
	$items['tab_content_where_to_begin']['MEASURE']['URL'] = $siteDir.'/crm/configs/measure/';
	$items['tab_content_where_to_begin']['MEASURE']['ICON_CLASS'] = 'img-units';
	$items['tab_content_where_to_begin']['MEASURE']['NAME'] = GetMessage("CRM_CONFIGS_MEASURE");
	$items['tab_content_where_to_begin']['PRODUCT_PROPS']['URL'] = $siteDir.'/crm/configs/productprops/';
	$items['tab_content_where_to_begin']['PRODUCT_PROPS']['ICON_CLASS'] = 'img-properties';
	$items['tab_content_where_to_begin']['PRODUCT_PROPS']['NAME'] = GetMessage("CRM_CONFIGS_PRODUCT_PROPS");
	$items['tab_content_where_to_begin']['DEAL_CATEGORY'] = array(
		'URL' => $siteDir.'/crm/configs/deal_category/',
		'NAME' => GetMessage("CRM_CONFIGS_DEAL_CATEGORY"),
		'ICON_CLASS' => 'img-deal-category'
	);
	$items['tab_content_where_to_begin']['PRESET']['URL'] = $siteDir.'/crm/configs/preset/';
	$items['tab_content_where_to_begin']['PRESET']['ICON_CLASS'] = 'img-other';
	$items['tab_content_where_to_begin']['PRESET']['NAME'] = GetMessage("CRM_CONFIGS_PRESET");
	$items['tab_content_where_to_begin']['MYCOMPANY']['URL'] = $siteDir.'/crm/configs/mycompany/';
	$items['tab_content_where_to_begin']['MYCOMPANY']['ICON_CLASS'] = 'img-mycompany';
	$items['tab_content_where_to_begin']['MYCOMPANY']['NAME'] = GetMessage("CRM_CONFIGS_MYCOMPANY");

	$items['tab_content_settings_forms_and_reports']['FIELDS']['URL'] = $siteDir.'/crm/configs/fields/';
	$items['tab_content_settings_forms_and_reports']['FIELDS']['ICON_CLASS'] = 'img-fields';
	$items['tab_content_settings_forms_and_reports']['FIELDS']['NAME'] = GetMessage("CRM_CONFIGS_FIELDS");
	$items['tab_content_settings_forms_and_reports']['SLOT']['URL'] = $siteDir.'/crm/configs/widget/';
	$items['tab_content_settings_forms_and_reports']['SLOT']['ICON_CLASS'] = 'img-reports';
	$items['tab_content_settings_forms_and_reports']['SLOT']['NAME'] = GetMessage("CRM_CONFIGS_SLOT");
	$items['tab_content_printed_forms_of_documents']['PS']['URL'] = $siteDir.'/crm/configs/ps/';
	$items['tab_content_printed_forms_of_documents']['PS']['ICON_CLASS'] = 'img-payment';
	$items['tab_content_printed_forms_of_documents']['PS']['NAME'] = GetMessage("CRM_CONFIGS_PS");
	$items['tab_content_rights']['PERMS']['URL'] = $siteDir.'/crm/configs/perms/';
	$items['tab_content_rights']['PERMS']['ICON_CLASS'] = 'img-permissions';
	$items['tab_content_rights']['PERMS']['NAME'] = GetMessage("CRM_CONFIGS_PERMS");

	if($arResult['IS_BIZPRPOC_ENABLED'])
	{
		$items['tab_content_automation']['BP']['URL'] = $siteDir.'/crm/configs/bp/';
		$items['tab_content_automation']['BP']['ICON_CLASS'] = 'img-bp';
		$items['tab_content_automation']['BP']['NAME'] = GetMessage("CRM_CONFIGS_BP");
	}

	if($arResult['IS_AUTOMATION_LEAD_ENABLED'])
	{
		$items['tab_content_automation']['AUTOMATION_LEAD']['URL'] = $siteDir.'/crm/configs/automation/LEAD/0/';
		$items['tab_content_automation']['AUTOMATION_LEAD']['ICON_CLASS'] = 'img-automation';
		$items['tab_content_automation']['AUTOMATION_LEAD']['NAME'] = GetMessage("CRM_CONFIGS_AUTOMATION_LEAD");
	}

	if($arResult['IS_AUTOMATION_DEAL_ENABLED'])
	{
		$items['tab_content_automation']['AUTOMATION_DEAL']['URL'] = $siteDir.'/crm/configs/automation/DEAL/0/';
		$items['tab_content_automation']['AUTOMATION_DEAL']['ICON_CLASS'] = 'img-automation';
		$items['tab_content_automation']['AUTOMATION_DEAL']['NAME'] = GetMessage("CRM_CONFIGS_AUTOMATION_DEAL");
	}

	$items['tab_content_work_with_mail']['SENDSAVE']['URL'] = $siteDir.'/crm/configs/sendsave/';
	$items['tab_content_work_with_mail']['SENDSAVE']['ICON_CLASS'] = 'img-email-int';
	$items['tab_content_work_with_mail']['SENDSAVE']['NAME'] = GetMessage("CRM_CONFIGS_SENDSAVE");

	$items['tab_content_work_with_mail']['MAIL_TRACKER_PUB']['URL'] = $siteDir.'/crm/configs/emailtracker/';
	$items['tab_content_work_with_mail']['MAIL_TRACKER_PUB']['ICON_CLASS'] = 'img-email-tracker-sh';
	$items['tab_content_work_with_mail']['MAIL_TRACKER_PUB']['NAME'] = getMessage('CRM_CONFIGS_MAIL_TRACKER_SH');

	if(LANGUAGE_ID === 'ru' || LANGUAGE_ID === 'ua')
	{
		$items['tab_content_integration']['EXTERNAL_SALE_BX']['URL'] = $siteDir.'/crm/configs/external_sale/';
		$items['tab_content_integration']['EXTERNAL_SALE_BX']['ICON_CLASS'] = 'img-shop';
		$items['tab_content_integration']['EXTERNAL_SALE_BX']['NAME'] = GetMessage('CRM_CONFIGS_EXTERNAL_SALE_BX');

		/*$items['tab_content_integration']['EXTERNAL_SALE']['URL'] = $siteDir.'/crm/plugins/';
		$items['tab_content_integration']['EXTERNAL_SALE']['ICON_CLASS'] = 'img-shop';
		$items['tab_content_integration']['EXTERNAL_SALE']['NAME'] = GetMessage('CRM_CONFIGS_EXTERNAL_SALE');*/

		$items['tab_content_integration']['EXCH1C']['URL'] = $siteDir.'/crm/configs/exch1c/';
		$items['tab_content_integration']['EXCH1C']['ICON_CLASS'] = 'img-1c';
		$items['tab_content_integration']['EXCH1C']['NAME'] = GetMessage('CRM_CONFIGS_EXCH1C');
	}
	else
	{
		/*$items['tab_content_integration']['EXTERNAL_SALE']['URL'] = $siteDir.'/crm/plugins/';
		$items['tab_content_integration']['EXTERNAL_SALE']['ICON_CLASS'] = 'img-shop';
		$items['tab_content_integration']['EXTERNAL_SALE']['NAME'] = GetMessage('CRM_CONFIGS_EXTERNAL_SALE');*/
	}

	$items['tab_content_integration']['EXTERNALCHANNEL']['URL'] = $siteDir.'/crm/configs/tracker/';
	$items['tab_content_integration']['EXTERNALCHANNEL']['ICON_CLASS'] = 'img-other';
	$items['tab_content_integration']['EXTERNALCHANNEL']['NAME'] = GetMessage("CRM_CONFIGS_TRACKER");

	$items['tab_content_other']['CONFIG']['URL'] = $siteDir.'/crm/configs/config/';
	$items['tab_content_other']['CONFIG']['ICON_CLASS'] = 'img-other';
	$items['tab_content_other']['CONFIG']['NAME'] = GetMessage("CRM_CONFIGS_CONFIG");
	/*
	$items['tab_content_other']['REFERENCE']['URL'] = '#';
	$items['tab_content_other']['REFERENCE']['ICON_CLASS'] = 'img-help';
	$items['tab_content_other']['REFERENCE']['NAME'] = GetMessage("CRM_CONFIGS_REFERENCE");
	*/

	if($arResult['BITRIX24'])
	{
		$items['tab_content_apps']['CRM_APPLICATION']['URL'] = $siteDir.'/marketplace/category/crm/';
		$items['tab_content_apps']['CRM_APPLICATION']['ICON_CLASS'] = 'img-app';
		$items['tab_content_apps']['CRM_APPLICATION']['NAME'] = GetMessage("CRM_CONFIGS_CRM_APPLICATION");
		$items['tab_content_apps']['MIGRATION_OTHER_CRM']['URL'] = $siteDir.'/marketplace/category/migration/';
		$items['tab_content_apps']['MIGRATION_OTHER_CRM']['ICON_CLASS'] = 'img-migration';
		$items['tab_content_apps']['MIGRATION_OTHER_CRM']['NAME'] = GetMessage("CRM_CONFIGS_MIGRATION_OTHER_CRM");
	}
}
if($arResult['IS_ACCESS_ENABLED'])
{
	$items['tab_content_work_with_mail']['MAIL_TEMPLATES']['URL'] = $siteDir.'/crm/configs/mailtemplate/';
	$items['tab_content_work_with_mail']['MAIL_TEMPLATES']['ICON_CLASS'] = 'img-email';
	$items['tab_content_work_with_mail']['MAIL_TEMPLATES']['NAME'] = GetMessage("CRM_CONFIGS_MAIL_TEMPLATES");
	$items['tab_content_work_with_mail']['MAIL_TRACKER']['URL'] = $siteDir.'/company/personal/mail/?config';
	$items['tab_content_work_with_mail']['MAIL_TRACKER']['ICON_CLASS'] = 'img-email-tracker';
	$items['tab_content_work_with_mail']['MAIL_TRACKER']['NAME'] = getMessage('CRM_CONFIGS_MAIL_TRACKER');
}

$mailItemsOrder = array(
	'MAIL_TRACKER_PUB' => 0,
	'MAIL_TRACKER'     => 1,
	'MAIL_TEMPLATES'   => 2,
	'SENDSAVE'         => 3,
);
uksort(
	$items['tab_content_work_with_mail'],
	function ($a, $b) use ($mailItemsOrder)
	{
		if (!isset($mailItemsOrder[$a]))
			return 1;
		if (!isset($mailItemsOrder[$b]))
			return -1;

		return $mailItemsOrder[$a]-$mailItemsOrder[$b];
	}
);

/*
$items['tab_content_creation_on_the_basis']['LEAD']['URL'] = '#';
$items['tab_content_creation_on_the_basis']['LEAD']['ICON_CLASS'] = 'img-leads';
$items['tab_content_creation_on_the_basis']['LEAD']['NAME'] = GetMessage("CRM_CONFIGS_LEAD");
$items['tab_content_creation_on_the_basis']['DEAL']['URL'] = '#';
$items['tab_content_creation_on_the_basis']['DEAL']['ICON_CLASS'] = 'img-deals';
$items['tab_content_creation_on_the_basis']['DEAL']['NAME'] = GetMessage("CRM_CONFIGS_DEAL");
$items['tab_content_creation_on_the_basis']['QOUTE']['URL'] = '#';
$items['tab_content_creation_on_the_basis']['QOUTE']['ICON_CLASS'] = 'img-offers';
$items['tab_content_creation_on_the_basis']['QOUTE']['NAME'] = GetMessage("CRM_CONFIGS_QOUTE");
$items['tab_content_creation_on_the_basis']['CONTACT']['URL'] = '#';
$items['tab_content_creation_on_the_basis']['CONTACT']['ICON_CLASS'] = 'img-contacts';
$items['tab_content_creation_on_the_basis']['CONTACT']['NAME'] = GetMessage("CRM_CONFIGS_CONTACT");
$items['tab_content_creation_on_the_basis']['COMPANY']['URL'] = '#';
$items['tab_content_creation_on_the_basis']['COMPANY']['ICON_CLASS'] = 'img-company';
$items['tab_content_creation_on_the_basis']['COMPANY']['NAME'] = GetMessage("CRM_CONFIGS_COMPANY");
$items['tab_content_creation_on_the_basis']['INVOICE']['URL'] = '#';
$items['tab_content_creation_on_the_basis']['INVOICE']['ICON_CLASS'] = 'img-accounts';
$items['tab_content_creation_on_the_basis']['INVOICE']['NAME'] = GetMessage("CRM_CONFIGS_INVOICE");
*/

/* Content description */
$contentDescription['tab_content_where_to_begin'] = GetMessage("CRM_CONFIGS_DESCRIPTION_WHERE_TO_BEGIN");
$contentDescription['tab_content_settings_forms_and_reports'] = GetMessage("CRM_CONFIGS_DESCRIPTION_SETTINGS_FORMS_AND_REPORTS");
$contentDescription['tab_content_printed_forms_of_documents'] = GetMessage("CRM_CONFIGS_DESCRIPTION_PRINTED_FORMS_OF_DOCUMENTS");
$contentDescription['tab_content_rights'] = GetMessage("CRM_CONFIGS_DESCRIPTION_RIGHTS");
$contentDescription['tab_content_automation'] = GetMessage("CRM_CONFIGS_DESCRIPTION_AUTOMATION");
$contentDescription['tab_content_work_with_mail'] = GetMessage("CRM_CONFIGS_DESCRIPTION_WORK_WITH_MAIL");
if (LANGUAGE_ID === 'ru' || LANGUAGE_ID === 'ua')
{
	$contentDescription['tab_content_integration'] = GetMessage('CRM_CONFIGS_DESCRIPTION_INTEGRATION');
}
$contentDescription['tab_content_other'] = GetMessage("CRM_CONFIGS_DESCRIPTION_OTHER");
//$contentDescription['tab_content_creation_on_the_basis'] = GetMessage("CRM_CONFIGS_DESCRIPTION_CREATION_ON_THE_BASIS");
if($arResult['BITRIX24'])
	$contentDescription['tab_content_apps'] = GetMessage("CRM_CONFIGS_DESCRIPTION_APP");

foreach($tabs as $tabId => $tabName)
{
	if(!array_key_exists('tab_content_'.$tabId, $items))
		unset($tabs[$tabId]);
}
?>

<div class="crm-container">
<div class="view-report-wrapper-container">
<?if(!empty($tabs)):?>
	<div class="view-report-wrapper-wrapp">
	<div class="view-report-wrapper-shell">

		<div class="view-report-sidebar view-report-sidebar-settings">
			<? $counter = 0; ?>
			<? foreach($tabs as $tabId => $tabName): ?>
				<? $class = (!$counter) ? 'sidebar-tab sidebar-tab-active' : 'sidebar-tab'?>
				<a href="javascript:void(0)" class="<?=$class?>" id="tab_<?=$tabId?>"
					onclick="javascript:BX['CrmConfigClass_<?= $arResult['RAND_STRING']?>'].selectTab('<?=$tabId ?>');">
					<?=$tabName?>
				</a>
				<? $counter++; ?>
			<? endforeach; ?>
		</div>

		<div class="view-report-wrapper">
			<? $counter = 0; ?>
			<? foreach($items as $contentId => $contentList): ?>
				<? $class = (!$counter)? 'view-report-wrapper-inner active' : 'view-report-wrapper-inner'?>
				<div class="<?= $class ?>" id="<?=$contentId?>">
					<? foreach($contentList as $itemData): ?>
						<a href="<?=$itemData['URL']?>" class="view-report-wrapper-inner-item">
							<span class="view-report-wrapper-inner-img <?=$itemData['ICON_CLASS']?>"></span>
							<span class="view-report-wrapper-inner-title"><?=$itemData['NAME']?></span>
						</a>
					<? endforeach; ?>
					<div class="view-report-wrapper-inner-clarification">
						<?=$contentDescription[$contentId]?>
					</div>
				</div>
				<? $counter++; ?>
			<? endforeach; ?>
		</div>

	</div>
	</div>
<?else:?>
	<div class="crm-configs-error-container"><?=GetMessage("CRM_CONFIGS_NO_ACCESS_ERROR")?></div>
<?endif;?>
</div>
</div>

<script type="text/javascript">
	BX(function () {
		BX['CrmConfigClass_<?= $arResult['RAND_STRING']?>'] = new BX.CrmConfigClass({
			randomString: '<?= $arResult['RAND_STRING'] ?>',
			tabs: <?=CUtil::PhpToJsObject(array_keys($tabs))?>
		});
	});
</script>