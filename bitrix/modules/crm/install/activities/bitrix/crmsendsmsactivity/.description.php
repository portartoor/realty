<?
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

$arActivityDescription = array(
	'NAME' => GetMessage('CRM_SSMSA_NAME'),
	'DESCRIPTION' => GetMessage('CRM_SSMSA_DESC'),
	'TYPE' => array('activity', 'robot_activity'),
	'CLASS' => 'CrmSendSmsActivity',
	'JSCLASS' => 'BizProcActivity',
	'CATEGORY' => array(
		'ID' => 'document',
		"OWN_ID" => 'crm',
		"OWN_NAME" => 'CRM',
	),
	'FILTER' => array(
		'INCLUDE' => array(
			array('crm', 'CCrmDocumentDeal'),
			array('crm', 'CCrmDocumentLead')
		),
	),
	'ROBOT_SETTINGS' => array(
		'CATEGORY' => 'client',
		'IS_AUTO' => true
	),
);