<?php

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/header.php');

$APPLICATION->includeComponent(
	'bitrix:intranet.mail.config',
	'',
	array(
		'SEF_MODE' => 'Y',
		'SEF_FOLDER' => '#SITE_DIR#company/personal/mail/',
	)
);

require($_SERVER['DOCUMENT_ROOT'].'/bitrix/footer.php');

