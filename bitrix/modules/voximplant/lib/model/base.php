<?php
namespace Bitrix\Voximplant\Model;

use Bitrix\Main\Application;
use Bitrix\Main\Entity;

abstract class Base extends Entity\DataManager
{
	/**
	 * Deletes all records from the table.
	 * @return null
	 */
	public static function truncate()
	{
		$helper = Application::getConnection()->getSqlHelper();
		$sql = "TRUNCATE ".$helper->quote(static::getTableName());

		return Application::getConnection()->query($sql);
	}
}