<?
/**
 * Class implements all further interactions with "rest" module considering userfields for "task item" entity.
 * 
 * This class is for internal use only, not a part of public API.
 * It can be changed at any time without notification.
 * 
 * @access private
 */

namespace Bitrix\Tasks\Integration\Rest\Task;

use \Bitrix\Tasks\Util\UserField\Restriction;

final class UserField extends \Bitrix\Tasks\Integration\Rest\UserField
{
	public static function getTargetEntityId()
	{
		return 'TASKS_TASK';
	}

	public static function runRestMethod($executiveUserId, $methodName, $args)
	{
		if(!Restriction::canManage(static::getTargetEntityId(), $executiveUserId))
		{
			// todo: raising an exception is bad, but still we got no error collection to return here
			throw new \Bitrix\Main\SystemException('Action not allowed');
		}

		return parent::runRestMethod($executiveUserId, $methodName, $args);
	}
}