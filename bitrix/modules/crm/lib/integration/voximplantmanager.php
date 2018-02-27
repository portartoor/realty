<?php
namespace Bitrix\Crm\Integration;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Voximplant;

class VoxImplantManager
{
	/** @var bool|null  */
	private static $isEnabled = null;
	/**
	 * Check if current manager enabled.
	 * @return bool
	 */
	public static function isEnabled()
	{
		if(self::$isEnabled === null)
		{
			self::$isEnabled = ModuleManager::isModuleInstalled('voximplant')
				&& Loader::includeModule('voximplant');
		}
		return self::$isEnabled;
	}
	/**
	 * Check if telephony in use.
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function isInUse()
	{
		return self::isEnabled() && \CVoxImplantMain::hasCalls();
	}
	/**
	 * Get service URL.
	 * @return string
	 * @throws Main\LoaderException
	 */
	public static function getUrl()
	{
		return self::isEnabled() ? \CVoxImplantMain::GetPublicFolder() : '';
	}
	/**
	 * Check if current user has permission to configure telephony.
	 * @return bool
	 * @throws Main\LoaderException
	 */
	public static function checkConfigurationPermission()
	{
		return self::isEnabled() && Voximplant\Security\Permissions::createWithCurrentUser()->canModifyLines();
	}
}