<?php

namespace Bitrix\Rest\Event;

use Bitrix\Main\Context;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Rest\AppTable;
use Bitrix\Rest\OAuth\Auth;
use Bitrix\Rest\OAuthService;
use Bitrix\Rest\Sqs;

/**
 * Class Sender
 *
 * Transport and utility for REST events.
 *
 * @package Bitrix\Rest
 **/
class Sender
{
	protected static $forkSet = false;
	protected static $queryData = array();

	protected static $defaultEventParams = array(
		"category" => Sqs::CATEGORY_DEFAULT,
		"sendAuth" => true,
		"sendRefreshToken" => false,
	);

	/**
	 * Utility function to parse pseudo-method name
	 *
	 * @param string $name Pseudo-method name.
	 *
	 * @return array
	 */
	public static function parseEventName($name)
	{
		$res = array();
		list($res['MODULE_ID'], $res['EVENT']) = explode('__', $name);
		return $res;
	}

	/**
	 * Binds REST event handler on PHP event.
	 *
	 * @param string $moduleId Event owner module.
	 * @param string $eventName Event name.
	 */
	public static function bind($moduleId, $eventName)
	{
		$eventManager = EventManager::getInstance();
		$eventManager->registerEventHandler($moduleId, $eventName, "rest", "\\Bitrix\\Rest\\Event\\Callback", $moduleId.'__'.ToUpper($eventName));
	}

	/**
	 * Unbinds REST event handler on PHP event.
	 *
	 * @param string $moduleId Event owner module.
	 * @param string $eventName Event name.
	 */
	public static function unbind($moduleId, $eventName)
	{
		$eventManager = EventManager::getInstance();
		$eventManager->unRegisterEventHandler($moduleId, $eventName, "rest", "\\Bitrix\\Rest\\Event\\Callback", $moduleId.'__'.ToUpper($eventName));

		/* compatibility */
		$eventManager->unRegisterEventHandler($moduleId, $eventName, "rest", "CRestEventCallback", $moduleId.'__'.ToUpper($eventName));
	}

	/**
	 * Getter for default event params array.
	 *
	 * @return array
	 */
	public static function getDefaultEventParams()
	{
		return static::$defaultEventParams;
	}

	/**
	 * Returns authorization array for event handlers and BP activities.
	 *
	 * @param string|int $appId Application ID or CODE.
	 * @param int $userId User ID which will be the owner of access_token.
	 * @param array $additionalData Additional data which will be stored with access_token.
	 * @param array $additional Event parameters. Keys sendAuth and sendRefreshToken supported.
	 *
	 * @return array|bool|null
	 *
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function getAuth($appId, $userId, array $additionalData = array(), array $additional = array())
	{
		$auth = null;

		$application = AppTable::getByClientId($appId);
		if($application)
		{
			if($userId > 0 && $additional["sendAuth"])
			{
				if(OAuthService::getEngine()->isRegistered())
				{
					$auth = Auth::get($application['CLIENT_ID'], $application['SCOPE'], $additionalData, $userId);

					if(is_array($auth) && !$additional["sendRefreshToken"])
					{
						unset($auth['refresh_token']);
					}
				}
			}

			if(!is_array($auth))
			{
				$auth = array(
					"domain" => Context::getCurrent()->getRequest()->getHttpHost(),
					"member_id" => \CRestUtil::getMemberId()
				);
			}

			$auth["application_token"] = \CRestUtil::getApplicationToken($application);
		}

		return $auth;
	}

	/**
	 * Calls or schedules the query to SQS.
	 *
	 * @param array $handlersList Event handlers to call.
	 */
	public static function call($handlersList)
	{
		global $USER;

		foreach($handlersList as $handlerInfo)
		{
			$handler = $handlerInfo[0];
			$data = $handlerInfo[1];
			$additional = $handlerInfo[2];

			foreach(static::$defaultEventParams as $key => $value)
			{
				if(!isset($additional[$key]))
				{
					$additional[$key] = $value;
				}
			}

			$session = Session::get();
			if(!$session)
			{
				// ttl exceeded, kill session
				return;
			}

			$userId = $handler['USER_ID'] > 0
				? $handler['USER_ID']
				: (
					// USER object can be null if event runs in BP or agent
					is_object($USER) && $USER->isAuthorized()
						? $USER->getId()
						: 0
				);

			$authData = null;
			if($handler['APP_ID'] > 0)
			{
				$dbRes = AppTable::getById($handler['APP_ID']);
				$application = $dbRes->fetch();

				$appStatus = \Bitrix\Rest\AppTable::getAppStatusInfo($application, '');
				if($appStatus['PAYMENT_ALLOW'] === 'Y')
				{
					$authData = array(
						Session::PARAM_SESSION => $session,
						Auth::PARAM_LOCAL_USER => $userId,
						"application_token" => \CRestUtil::getApplicationToken($application),
					);
				}
			}
			else
			{
				$application = array('CLIENT_ID' => null);

				$authData = array(
					Session::PARAM_SESSION => $session,
					Auth::PARAM_LOCAL_USER => $userId,
					'application_token' => $handler['APPLICATION_TOKEN'],
				);
			}

			if($authData)
			{
				self::$queryData[] = Sqs::queryItem(
					$application['CLIENT_ID'],
					$handler['EVENT_HANDLER'],
					array(
						'event' => $handler['EVENT_NAME'],
						'data' => $data,
						'ts' => time(),
					),
					$authData,
					$additional
				);
			}
		}

		if(count(static::$queryData) > 0 && !static::$forkSet)
		{
			if(\CMain::forkActions(array(__CLASS__, "send"), array()))
			{
				static::$forkSet = true;
			}
			else
			{
				static::send();
			}
		}
	}

	/**
	 * Sends all scheduled handlers to SQS.
	 */
	public static function send()
	{
		if(count(self::$queryData) > 0)
		{
			if(OAuthService::getEngine()->isRegistered())
			{
				OAuthService::getEngine()->getClient()->sendEvent(self::$queryData);
			}

			self::$queryData = array();
		}
	}
}
