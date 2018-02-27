<?php

use Bitrix\Main\Localization\Loc;

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/intranet/public/crm/configs/emailtracker/index.php');

$APPLICATION->setTitle(Loc::getMessage('CRM_TITLE'));

$APPLICATION->includeComponent(
	'bitrix:crm.config.emailtracker',
	'',
	array(),
	false
);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
