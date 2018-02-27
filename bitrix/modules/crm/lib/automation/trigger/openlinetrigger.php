<?php
namespace Bitrix\Crm\Automation\Trigger;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Integration;

Loc::loadMessages(__FILE__);

class OpenLineTrigger extends BaseTrigger
{
	public static function isEnabled()
	{
		return (Integration\OpenLineManager::isEnabled()
			&& class_exists('\Bitrix\ImOpenLines\Crm')
			&& method_exists('\Bitrix\ImOpenLines\Crm', 'executeAutomationTrigger')
		);
	}

	public static function getCode()
	{
		return 'OPENLINE';
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_AUTOMATION_TRIGGER_OPENLINE_NAME');
	}

	public function checkApplyRules(array $trigger)
	{
		if (
			is_array($trigger['APPLY_RULES'])
			&& isset($trigger['APPLY_RULES']['config_id'])
			&& $trigger['APPLY_RULES']['config_id'] > 0
		)
		{
			return (int)$trigger['APPLY_RULES']['config_id'] === (int)$this->getInputData('CONFIG_ID');
		}
		return true;
	}
}