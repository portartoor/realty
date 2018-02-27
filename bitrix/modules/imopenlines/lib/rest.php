<?php
namespace Bitrix\ImOpenLines;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc;

if(!\Bitrix\Main\Loader::includeModule('rest'))
	return;

Loc::loadMessages(__FILE__);

class Rest extends \IRestService
{
	public static function onRestServiceBuildDescription()
	{
		return array(
			'imopenlines' => array(
				'imopenlines.bot.session.operator' => array(__CLASS__, 'botSessionOperator'),
				'imopenlines.bot.session.send.message' => array(__CLASS__, 'botSessionSendAutoMessage'),
				'imopenlines.bot.session.transfer' => array(__CLASS__, 'botSessionTransfer'),
				'imopenlines.bot.session.finish' => array(__CLASS__, 'botSessionFinish'),

				'imopenlines.network.join' => array(__CLASS__, 'networkJoin'),
				'imopenlines.config.path.get' => array(__CLASS__, 'configGetPath'),
			),
		);
	}
	

	public static function botSessionOperator($arParams, $n, \CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$arParams['CHAT_ID'] = intval($arParams['CHAT_ID']);
		if ($arParams['CHAT_ID'] <= 0)
		{
			throw new \Bitrix\Rest\RestException("Chat ID can't be empty", "CHAT_ID_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$chat = new \Bitrix\Imopenlines\Chat($arParams['CHAT_ID']);
		$result = $chat->endBotSession();
		if (!$result)
		{
			throw new \Bitrix\Rest\RestException("Operator is not a bot", "WRONG_CHAT", \CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}
	
	public static function botSessionSendAutoMessage($arParams, $n, \CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$arParams['CHAT_ID'] = intval($arParams['CHAT_ID']);
		if ($arParams['CHAT_ID'] <= 0)
		{
			throw new \Bitrix\Rest\RestException("Chat ID can't be empty", "CHAT_ID_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$chat = new \Bitrix\Imopenlines\Chat($arParams['CHAT_ID']);
		$chat->sendAutoMessage($arParams['NAME']);

		return true;
	}

	public static function botSessionTransfer($arParams, $n, \CRestServer $server)
	{
		$arParams['CHAT_ID'] = intval($arParams['CHAT_ID']);
		$arParams['USER_ID'] = intval($arParams['USER_ID']);
		$arParams['LEAVE'] = isset($arParams['LEAVE']) && $arParams['LEAVE'] == 'Y'? 'Y': 'N';

		if ($arParams['CHAT_ID'] <= 0)
		{
			throw new \Bitrix\Rest\RestException("Chat ID can't be empty", "CHAT_ID_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
		}

		if ($arParams['USER_ID'] <= 0)
		{
			throw new \Bitrix\Rest\RestException("User ID can't be empty", "USER_ID_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$bots = \Bitrix\Im\Bot::getListCache();
		$botFound = false;
		$botId = 0;
		foreach ($bots as $bot)
		{
			if ($bot['APP_ID'] == $server->getAppId())
			{
				$botFound = true;
				$botId = $bot['BOT_ID'];
				break;
			}
		}
		if (!$botFound)
		{
			throw new \Bitrix\Rest\RestException("Bot not found", "BOT_ID_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$chat = new \Bitrix\Imopenlines\Chat($arParams['CHAT_ID']);
		$result = $chat->transfer(Array(
			'FROM' => $botId,
			'TO' => $arParams['USER_ID'],
			'MODE' => Chat::TRANSFER_MODE_BOT,
			'LEAVE' => $arParams['LEAVE']
		));
		if (!$result)
		{
			throw new \Bitrix\Rest\RestException("You can not redirect to this operator", "OPERATOR_WRONG", \CRestServer::STATUS_WRONG_REQUEST);
		}

		return true;
	}

	public static function botSessionFinish($arParams, $n, \CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$arParams['CHAT_ID'] = intval($arParams['CHAT_ID']);
		if ($arParams['CHAT_ID'] <= 0)
		{
			throw new \Bitrix\Rest\RestException("Chat ID can't be empty", "CHAT_ID_EMPTY", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$bots = \Bitrix\Im\Bot::getListCache();
		$botFound = false;
		$botId = 0;
		foreach ($bots as $bot)
		{
			if ($bot['APP_ID'] == $server->getAppId())
			{
				$botFound = true;
				$botId = $bot['BOT_ID'];
				break;
			}
		}
		if (!$botFound)
		{
			throw new \Bitrix\Rest\RestException("Bot not found", "BOT_ID_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
		}
		
		$chat = new \Bitrix\Imopenlines\Chat($arParams['CHAT_ID']);
		$chat->answer($botId);
		$chat->finish();

		return true;
	}

	public static function configGetPath($arParams, $n, \CRestServer $server)
	{
		return array(
			'SERVER_ADDRESS' => \Bitrix\ImOpenLines\Common::getServerAddress(),
			'PUBLIC_PATH' => \Bitrix\ImOpenLines\Common::getPublicFolder()
		);
	}
	
	public static function networkJoin($arParams, $n, \CRestServer $server)
	{
		$arParams = array_change_key_case($arParams, CASE_UPPER);
		if (!isset($arParams['CODE']) || strlen($arParams['CODE']) != 32)
		{
			throw new \Bitrix\Rest\RestException("You entered an invalid code", "CODE", \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (!\Bitrix\Main\Loader::includeModule('imbot'))
		{
			throw new \Bitrix\Rest\RestException("Module IMBOT is not installed", "IMBOT_ERROR", \CRestServer::STATUS_WRONG_REQUEST);
		}

		if (\Bitrix\ImBot\Bot\Network::isFdcCode($arParams['CODE']))
		{
			throw new \Bitrix\Rest\RestException("Line not found", "NOT_FOUND", \CRestServer::STATUS_WRONG_REQUEST);
		}

		$network = new \Bitrix\ImOpenLines\Network();
		$result = $network->join($arParams['CODE']);
		if (!$result)
		{
			throw new \Bitrix\Rest\RestException($network->getError()->msg, $network->getError()->code, \CRestServer::STATUS_WRONG_REQUEST);
		}

		return $result;
	}
}
