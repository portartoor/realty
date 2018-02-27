<?php
namespace Bitrix\Crm\Recurring\DateType;

use Bitrix\Main;
use Bitrix\Main\Type\Date;

class Week
{
	/**
	 * @param array $params
	 * @param Date $date
	 *
	 * @return Date
	 */
	public static function calculateDate(array $params, Date $date)
	{
		$days = is_array($params["WEEKDAYS"]) ? $params["WEEKDAYS"] : array(1);
		sort($days);
		$currentDay = (int)($date->format("N"));
		$nextDay = null;

		foreach ($days as $day)
		{
			if ($day >= $currentDay)
			{
				$nextDay = $day;
				break;
			}
		}

		if ($nextDay)
		{
			$dataText = "+" . ($nextDay - $currentDay) . " days";
			if ((int)$params["INTERVAL_WEEK"] > 1)
			{
				$dataText = " +" . (int)$params["INTERVAL_WEEK"] - 1 . " weeks ".$dataText;
			}
		}
		else
		{
			$dataText = " +" . (int)$params["INTERVAL_WEEK"] . " weeks +" . ($days[0] - $currentDay) . " days";
		}

		return $date->add($dataText);
	}
}