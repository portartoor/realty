<?
/**
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 * 
 * @access private
 */

namespace Bitrix\Tasks\Integration;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Tasks\Util\User;

Loc::loadMessages(__FILE__);

abstract class SocialNetwork extends \Bitrix\Tasks\Integration
{
	const MODULE_NAME = 'socialnetwork';

	private static $enabled = true;

	public static function enable()
	{
		static::$enabled = true;
	}
	public static function disable()
	{
		static::$enabled = false;
	}
	public static function isEnabled()
	{
		return static::$enabled;
	}

	public static function getUserEntityPrefix()
	{
		return 'U';
	}
	public static function getGroupEntityPrefix()
	{
		return 'SG';
	}
	public static function getDepartmentEntityPrefix()
	{
		return 'DR';
	}

    /**
     * Get data for user selector dialog
     *
     * @param string $context
     * @param array $parameters
     * @return array
     * @throws \Bitrix\Main\LoaderException
     * @throws \Bitrix\Main\SystemException
     */
	public static function getLogDestination($context = 'TASKS', array $parameters = array())
	{
		if(!static::includeModule())
		{
			return array();
		}

		$destinationParams = array();
		if(intval($parameters['AVATAR_HEIGHT']) && intval($parameters['AVATAR_WIDTH']))
		{
			$destinationParams['THUMBNAIL_SIZE_WIDTH'] = intval($parameters['AVATAR_WIDTH']);
			$destinationParams['THUMBNAIL_SIZE_HEIGHT'] = intval($parameters['AVATAR_HEIGHT']);
		}

		if(!is_object(User::get()))
		{
			throw new \Bitrix\Main\SystemException('Global user is not defined');
		}

		$userId = User::getId();

		$structure = \CSocNetLogDestination::GetStucture(array());
		$destination = array(
			"DEST_SORT" => \CSocNetLogDestination::GetDestinationSort(array(
				"DEST_CONTEXT" => $context,
				"ALLOW_EMAIL_INVITATION" => \Bitrix\Main\ModuleManager::isModuleInstalled("mail"),
			)),
			"LAST" => array("USERS" => array(), "SONETGROUPS" => array(), "DEPARTMENT" => array()),
			"DEPARTMENT" => $structure["department"],
			"DEPARTMENT_RELATION" => $structure["department_relation"],
			"DEPARTMENT_RELATION_HEAD" => $structure["department_relation_head"],
			/*
			"SELECTED" => array(
				"USERS" => array(User::getId())
			)
			*/
		);

		\CSocNetLogDestination::fillLastDestination($destination["DEST_SORT"], $destination["LAST"]);

		if (\Bitrix\Tasks\Integration\Extranet\User::isExtranet())
		{
			$destination["EXTRANET_USER"] = "Y";
			$destination["USERS"] = \CSocNetLogDestination::getExtranetUser($destinationParams);
		}
		else
		{
			$destUser = array();
			foreach ($destination["LAST"]["USERS"] as $value)
			{
				$destUser[] = str_replace("U", "", $value);
			}

			$destination["EXTRANET_USER"] = "N";
			$destination["USERS"] = \CSocNetLogDestination::getUsers(array_merge($destinationParams, array("id" => $destUser)));
			\CSocNetLogDestination::fillEmails($destination);
		}

		$cacheTtl = defined("BX_COMP_MANAGED_CACHE") ? 3153600 : 3600*4;
		$cacheId = "dest_project_".$userId.md5(serialize($parameters)).SITE_ID;
		$cacheDir = "/tasks/dest/".$userId;
		$cache = new \CPHPCache;
		if($cache->initCache($cacheTtl, $cacheId, $cacheDir))
		{
			$destination["SONETGROUPS"] = $cache->getVars();
		}
		else
		{
			$cache->startDataCache();
			$destination["SONETGROUPS"] = \CSocNetLogDestination::getSocnetGroup(array_merge($destinationParams, array("ALL" => "Y", "GROUP_CLOSED" => "N", "features" => array("tasks", array("create_tasks")))));
			if(defined("BX_COMP_MANAGED_CACHE"))
			{
				global $CACHE_MANAGER;
				$CACHE_MANAGER->startTagCache($cacheDir);
				foreach($destination["SONETGROUPS"] as $val)
				{
					$CACHE_MANAGER->registerTag("sonet_features_G_".$val["entityId"]);
					$CACHE_MANAGER->registerTag("sonet_group_".$val["entityId"]);
				}
				$CACHE_MANAGER->registerTag("sonet_user2group_U".$userId);
				$CACHE_MANAGER->endTagCache();
			}
			$cache->endDataCache($destination["SONETGROUPS"]);
		}

		// add virtual department: extranet
		if (\Bitrix\Tasks\Integration\Extranet::isConfigured())
		{
			$destination['DEPARTMENT']['EX'] = array(
				'id' => 'EX',
				'entityId' => 'EX',
				'name' => Loc::getMessage("TASKS_INTEGRATION_EXTRANET_ROOT"),
				'parent' => 'DR0',
			);
			$destination['DEPARTMENT_RELATION']['EX'] = array(
				'id' => 'EX',
				'type' => 'category',
				'items' => array(),
			);
		}

		$destination['NETWORK_ENABLED'] = Option::get('tasks', 'network_enabled') == 'Y';

		$destination['NETWORK_ENABLED'] = \Bitrix\Main\Config\Option::get('tasks', 'network_enabled') == 'Y';

		return $destination;
	}

    /**
     * Save last selected items in user selector dialog
     *
     * @param array $items
     * @param string $context
     */
	public static function setLogDestinationLast(array $items = array(), $context = 'TASKS')
	{
		if(!static::includeModule())
		{
			return;
		}

		$result = array();

		static::reformatLastItems($result, 'U', 'U', $items);
		static::reformatLastItems($result, 'SG', 'SG', $items);
		static::reformatLastItems($result, 'DR', 'DR', $items);

		// for compatibility
		static::reformatLastItems($result, 'USER', 'U', $items);
		static::reformatLastItems($result, 'SGROUP', 'SG', $items);

		\Bitrix\Main\FinderDestTable::merge(array(
			"CONTEXT" => $context,
			"CODE" => $result
		));
	}

	public static function getParser(array $parameters = array())
	{
		if(!static::includeModule())
		{
			return null;
		}

		static $parser;
		if($parser == null)
		{
			$parser = new \logTextParser(false, $parameters["PATH_TO_SMILE"]);
		}

		return $parser;
	}

	public static function formatDateTimeToGMT($time, $userId)
	{
		if(!static::includeModule())
		{
			return $time;
		}

		return \Bitrix\Socialnetwork\ComponentHelper::formatDateTimeToGMT($time, $userId);
	}

	private static function reformatLastItems(&$result, $from, $to, $items)
	{
		if(is_array($items[$from]))
		{
			$items[$from] = array_unique($items[$from]);
			foreach($items[$from] as $userId)
			{
				if(intval($userId))
				{
					$result[] = $to.$userId;
				}
			}
		}
	}
}