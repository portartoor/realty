<?php
if(!(\Bitrix\Main\Loader::includeModule('im') && \Bitrix\Main\Loader::includeModule('imbot')))
	return false;

if (is_object($APPLICATION))
	$APPLICATION->RestartBuffer();

\Bitrix\ImBot\Log::write($_POST, 'PORTAL HIT');

$params = $_POST;
$hash = $params["BX_HASH"];
unset($params["BX_HASH"]);

// BOT CLOUD HITS

if(
	$params['BX_TYPE'] == \Bitrix\ImBot\Http::TYPE_BITRIX24 && \Bitrix\ImBot\Http::requestSign($params['BX_TYPE'], md5(implode("|", $params)."|".BX24_HOST_NAME)) === $hash 
	|| $params['BX_TYPE'] == \Bitrix\ImBot\Http::TYPE_CP && \Bitrix\ImBot\Http::requestSign($params['BX_TYPE'], md5(implode("|", $params))) === $hash
)
{
	$params = \Bitrix\Main\Text\Encoding::convertEncoding($params, 'UTF-8', SITE_CHARSET);

	if (isset($params['BX_SERVICE_NAME']) && !empty($params['BX_SERVICE_NAME']))
	{
		$result = \Bitrix\ImBot\Controller::sendToService($params['BX_SERVICE_NAME'], $params['BX_COMMAND'], $params);
	}
	else
	{
		$result = \Bitrix\ImBot\Controller::sendToBot($params['BX_BOT_NAME'], $params['BX_COMMAND'], $params);
	}
	if (is_null($result))
	{
		echo "You don't have access to this page.";
	}
	else
	{
		echo \Bitrix\Main\Web\Json::encode($result);
	}
}
else
{
	echo "You don't have access to this page.";
}

CMain::FinalActions();
die();