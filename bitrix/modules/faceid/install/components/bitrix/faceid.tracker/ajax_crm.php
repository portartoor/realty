<?php

define("PUBLIC_AJAX_MODE", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Loader::includeModule('faceid');
\Bitrix\Main\Loader::includeModule('crm');

if (!\Bitrix\Faceid\AgreementTable::checkUser($USER->getId()))
{
	die;
}

CUtil::JSPostUnescape();

if (!empty($_POST['action']))
{
	$lead = array(
		'TITLE' => $_POST['lead_title']
	);

	$entity = new CCrmLead(false);
	$id = $entity->Add($lead, true, array('DISABLE_USER_FIELD_CHECK' => true));

	// update visitor
	\Bitrix\Faceid\TrackingVisitorsTable::update($_POST['visitor_id'], array('CRM_ID' => $id));

	echo \Bitrix\Main\Web\Json::encode(array('id' => $id, 'url' => '/crm/lead/show/'.$id.'/', 'name' => $lead['TITLE']));
}

CMain::FinalActions();