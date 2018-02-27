<?php

namespace Bitrix\Voximplant;

use Bitrix\Main\Config\Option;
use Bitrix\Main\ModuleManager;

class Limits
{
	const OPTION_INFOCALLS_LIMIT = "infocalls_limit";
	const OPTION_LAST_INFOCALLS_COUNTER_UPDATE = "infocalls_month";
	const INFOCALLS_COUNTER = "vi_infocalls";

	/**
	 * @param string $lineMode Line mode (\CVoxImplantConfig::MODE_SIP or \CVoxImplantConfig::MODE_RENT)
	 * @return int|false
	 */
	public static function getInfocallsLimit($lineMode = '')
	{
		if(!ModuleManager::isModuleInstalled('bitrix24'))
			return false;

		if(\CVoxImplantAccount::IsPro())
		{
			return false;
		}

		if($lineMode = \CVoxImplantConfig::MODE_SIP && \CVoxImplantSip::isActive())
		{
			return false;
		}
		
		return (int)Option::get('voximplant', self::OPTION_INFOCALLS_LIMIT);
	}

	/**
	 * @param string $lineMode Line mode (\CVoxImplantConfig::MODE_SIP or \CVoxImplantConfig::MODE_RENT)
	 * @return int|false
	 */
	public static function getInfocallsLimitRemainder($lineMode = '')
	{
		$limit = self::getInfocallsLimit($lineMode);
		if($limit === false)
			return false;

		$month = (int)date('Ym');
		$previousMonth = (int)Option::get('voximplant', self::OPTION_LAST_INFOCALLS_COUNTER_UPDATE);

		if($previousMonth !== $month)
		{
			Option::set('voximplant', self::OPTION_LAST_INFOCALLS_COUNTER_UPDATE, $month);
			$counter = 0;
			\CGlobalCounter::Set(self::INFOCALLS_COUNTER, $counter, \CGlobalCounter::ALL_SITES, '',false);
		}
		else
		{
			$counter = \CGlobalCounter::GetValue(self::INFOCALLS_COUNTER, \CGlobalCounter::ALL_SITES);
		}

		$result = $limit - $counter;
		return ($result > 0 ? $result : 0);
	}

	/**
	 * @param string $lineMode Line mode (\CVoxImplantConfig::MODE_SIP or \CVoxImplantConfig::MODE_RENT)
	 * @return bool
	 */
	public static function addInfocall($lineMode = '')
	{
		$limit = self::getInfocallsLimit($lineMode);
		if($limit === false)
			return false;

		\CGlobalCounter::Increment(self::INFOCALLS_COUNTER, \CGlobalCounter::ALL_SITES);
		return true;
	}

	/**
	 * Returns maximum IVR depth according to the portal's tariff
	 * @return int|string
	 */
	public static function getIvrDepth()
	{
		if (!\CModule::IncludeModule('bitrix24'))
			return 0;

		if(\CBitrix24::getLicenseType() === 'team')
			return 2;
		else
			return 0;
	}
}