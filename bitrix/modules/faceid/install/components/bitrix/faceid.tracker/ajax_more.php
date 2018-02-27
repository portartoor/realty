<?php

define("PUBLIC_AJAX_MODE", true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Loader::includeModule('faceid');

if (!\Bitrix\Faceid\AgreementTable::checkUser($USER->getId()))
{
	die;
}

if (!empty($_POST['last']))
{
	$lastDate = new \Bitrix\Main\Type\DateTime(date('Y-m-d H:i:s', $_POST['last']), 'Y-m-d H:i:s');

	$visitors = \Bitrix\Faceid\TrackingVisitorsTable::getList(array(
		'order' => array('LAST_VISIT' => 'DESC'),
		'filter' => array('<LAST_VISIT' => $lastDate),
		'limit' => 20
	))->fetchAll();

	$result = array();
	foreach ($visitors as $visitor)
	{
		$result[] = \Bitrix\Faceid\TrackingVisitorsTable::toJson($visitor, 0, true);
	}

	echo \Bitrix\Main\Web\Json::encode($result);
}

CMain::FinalActions();