<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
CUtil::InitJSCore();

$arTabs = array();
$arTabs[] = array(
	'id' => 'tab_1',
	'name' => GetMessage('CRM_TAB_1'),
	'title' => GetMessage('CRM_TAB_1_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_1']
);
$arTabs[] = array(
	'id' => 'tab_2',
	'name' => GetMessage('CRM_TAB_2'),
	'title' => GetMessage('CRM_TAB_2_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_2'],
);
$arTabs[] = array(
	'id' => 'tab_3',
	'name' => GetMessage('CRM_TAB_3'),
	'title' => GetMessage('CRM_TAB_3_TITLE'),
	'icon' => '',
	'fields'=> $arResult['FIELDS']['tab_3'],
);

$customButtons = '';

if ($arResult['STEP'] == 2)
	$customButtons .= '<input type="submit" name="previous" value="'.GetMessage("CRM_IMPORT_PREVIOUS_STEP").'" title="'.GetMessage("CRM_IMPORT_PREVIOUS_STEP_TITLE").'" />';
if ($arResult['STEP'] == 3)
{
	$customButtons .= '<input type="submit" name="next" value="'.GetMessage("CRM_IMPORT_DONE").'" title="'.GetMessage("CRM_IMPORT_DONE_TITLE").'" disabled="true" id="crm_import_done"/>';
	$customButtons .= '<input type="submit" name="previous" value="'.GetMessage("CRM_IMPORT_AGAIN").'" title="'.GetMessage("CRM_IMPORT_AGAIN_TITLE").'" hidden="true" id="crm_import_again" style="margin-left: 10px"/>';
}
else
	$customButtons .= '<input type="submit" name="next" value="'.GetMessage("CRM_IMPORT_NEXT_STEP").'" title="'.GetMessage("CRM_IMPORT_NEXT_STEP_TITLE").'" />';
if ($arResult['STEP'] < 3)
	$customButtons .= '&nbsp;&nbsp;<input type="submit" name="cancel" value="'.GetMessage("CRM_IMPORT_CANCEL").'" title="'.GetMessage("CRM_IMPORT_CANCEL_TITLE").'" />';

$customButtons .= '<input type="hidden" name="step" value="'.$arResult['STEP'].'"  />';
	


$APPLICATION->IncludeComponent(
	'bitrix:main.interface.form',
	'',
	array(
		'FORM_ID' => $arResult['FORM_ID'],
		'TABS' => $arTabs,
		'BUTTONS' => array(
			'standard_buttons' =>  false,
			'custom_html' => $customButtons,
		),
		'DATA' => array(),
		'SHOW_SETTINGS' => 'N'
	),
	$component, array('HIDE_ICONS' => 'Y')
);
?>
<script type="text/javascript">
	crmImportStep(<?=$arResult['STEP']?>, '<?=$arResult['FORM_ID']?>');
	BX.remove(BX('bxForm_<?=$arResult['FORM_ID']?>_expand_link'));
</script>
<?
$crmEmail = strtolower(COption::GetOptionString('crm', 'mail', ''));
if ($crmEmail != ''):?>
<div class="crm_notice_message"><?=GetMessage('CRM_IMPORT_SNS', Array('%EMAIL%' => $crmEmail, '%ARROW%' => '<span class="crm_notice_arrow"></span>'));?></div>
<?endif;?>