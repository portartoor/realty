<?php
namespace Bitrix\Rest\Api;


use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Rest\AccessException;
use Bitrix\Rest\AppTable;
use Bitrix\Rest\PlacementTable;
use Bitrix\Rest\RestException;

class Placement extends \IRestService
{
	const SCOPE_PLACEMENT = 'placement';

	public static function onRestServiceBuildDescription()
	{
		return array(
			static::SCOPE_PLACEMENT => array(
				'placement.list' => array(
					'callback' => array(__CLASS__, 'getList'),
					'options' => array()
				),
				'placement.bind' => array(
					'callback' => array(__CLASS__, 'bind'),
					'options' => array()
				),
				'placement.unbind' => array(
					'callback' => array(__CLASS__, 'unbind'),
					'options' => array()
				),
				'placement.get' => array(
					'callback' => array(__CLASS__, 'get'),
					'options' => array()
				)
			),
		);
	}


	public static function getList($query, $n, \CRestServer $server)
	{
		if(!$server->getClientId())
		{
			throw new AccessException("Application context required");
		}

		$result = array();

		$serviceDescription = $server->getServiceDescription();

		$scopeList = array(\CRestUtil::GLOBAL_SCOPE);

		$query = array_change_key_case($query, CASE_UPPER);

		if(isset($query['SCOPE']))
		{
			if($query['SCOPE'] != '')
			{
				$scopeList = array($query['SCOPE']);
			}
		}
		elseif($query['FULL'] == true)
		{
			$scopeList = array_keys($serviceDescription);
		}
		else
		{
			$scopeList = self::getScope($server);
			$scopeList[] = \CRestUtil::GLOBAL_SCOPE;
		}

		foreach($serviceDescription as $scope => $scopeDescription)
		{
			if(in_array($scope, $scopeList) && isset($scopeDescription[\CRestUtil::PLACEMENTS]))
			{
				$result = array_merge($result, array_keys($scopeDescription[\CRestUtil::PLACEMENTS]));
			}
		}

		return $result;
	}


	public static function bind($params, $n, \CRestServer $server)
	{
		global $USER;

		if(!$server->getClientId())
		{
			throw new AccessException("Application context required");
		}

		if(!$USER->canDoOperation('bitrix24_config'))
		{
			throw new AccessException();
		}

		$params = array_change_key_case($params, CASE_UPPER);

		$placement = toUpper($params['PLACEMENT']);
		$placementHandler = $params['HANDLER'];

		if(strlen($placement) <= 0)
		{
			throw new ArgumentNullException("PLACEMENT");
		}

		if($placement == PlacementTable::PLACEMENT_DEFAULT)
		{
			throw new ArgumentException("Wrong value", "PLACEMENT");
		}

		if(strlen($placementHandler) <= 0)
		{
			throw new ArgumentNullException("HANDLER");
		}

		$appInfo = self::getApplicationInfo($server);

		\Bitrix\Rest\HandlerHelper::checkCallback($placementHandler, $appInfo);

		$scopeList = self::getScope($server);
		$scopeList[] = \CRestUtil::GLOBAL_SCOPE;

		$serviceDescription = $server->getServiceDescription();

		foreach($scopeList as $scope)
		{
			if(
				isset($serviceDescription[$scope])
				&& is_array($serviceDescription[$scope][\CRestUtil::PLACEMENTS])
				&& array_key_exists($placement, $serviceDescription[$scope][\CRestUtil::PLACEMENTS])
			)
			{
				$placementInfo = $serviceDescription[$scope][\CRestUtil::PLACEMENTS][$placement];
				if(is_array($placementInfo))
				{
					$placementBind = array(
						'APP_ID' => $appInfo['ID'],
						'PLACEMENT' => $placement,
						'PLACEMENT_HANDLER' => $placementHandler,
					);

					if(!empty($params['TITLE']))
					{
						$placementBind['TITLE'] = trim($params['TITLE']);
					}

					if(!empty($params['DESCRIPTION']))
					{
						$placementBind['COMMENT'] = trim($params['DESCRIPTION']);
					}

					$result = PlacementTable::add($placementBind);
					if(!$result->isSuccess())
					{
						$errorMessage = $result->getErrorMessages();
						throw new RestException(
							'Unable to set placement handler: '.implode('. ', $errorMessage),
							RestException::ERROR_CORE
						);
					}
				}

				return true;
			}
		}

		throw new RestException(
			'Placement not found',
			PlacementTable::ERROR_PLACEMENT_NOT_FOUND
		);
	}


	public static function unbind($params, $n, \CRestServer $server)
	{
		global $USER;

		if(!$server->getClientId())
		{
			throw new AccessException("Application context required");
		}

		if(!$USER->canDoOperation('bitrix24_config'))
		{
			throw new AccessException();
		}

		$params = array_change_key_case($params, CASE_UPPER);

		$placement = toUpper($params['PLACEMENT']);
		$placementHandler = $params['HANDLER'];

		if(strlen($placement) <= 0)
		{
			throw new ArgumentNullException("PLACEMENT");
		}

		$appInfo = self::getApplicationInfo($server);

		$filter = array(
			'=APP_ID' => $appInfo["ID"],
			'=PLACEMENT' => $placement,
		);

		if(strlen($placementHandler) > 0)
		{
			$filter['=PLACEMENT_HANDLER'] = $placementHandler;
		}

		$dbRes = PlacementTable::getList(array(
			'filter' => $filter
		));

		$cnt = 0;
		while($placementHandler = $dbRes->fetch())
		{
			$result = PlacementTable::delete($placementHandler["ID"]);
			if($result->isSuccess())
			{
				$cnt++;
			}
		}

		return array('count' => $cnt);
	}


	public static function get($params, $n, \CRestServer $server)
	{
		global $USER;

		if(!$server->getClientId())
		{
			throw new AccessException("Application context required");
		}

		if(!$USER->canDoOperation('bitrix24_config'))
		{
			throw new AccessException();
		}

		$placementList = array();

		$appInfo = self::getApplicationInfo($server);

		$dbRes = PlacementTable::getList(array(
			"filter" => array(
				"=APP_ID" => $appInfo["ID"],
			),
			'order' => array(
				"ID" => "ASC",
			)
		));
		while($placement = $dbRes->fetch())
		{
			$placementList[] = array(
				"placement" => $placement['PLACEMENT'],
				"handler" => $placement['PLACEMENT_HANDLER'],
				"title" => $placement['TITLE'],
				"description" => $placement['COMMENT'],
			);
		}

		return $placementList;
	}

	protected static function getScope(\CRestServer $server)
	{
		$result = array();

		$authData = $server->getAuthData();

		$scopeList = explode(',', $authData['scope']);

		$serviceDescription = $server->getServiceDescription();
		foreach($scopeList as $scope)
		{
			if(array_key_exists($scope, $serviceDescription))
			{
				$result[] = $scope;
			}
		}

		return $result;
	}

	protected static function getApplicationInfo(\CRestServer $server)
	{
		if($server->getClientId())
		{
			return AppTable::getByClientId($server->getClientId());
		}

		throw new AccessException("Application context required");
	}
}