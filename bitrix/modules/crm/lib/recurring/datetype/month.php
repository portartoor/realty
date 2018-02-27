<?php
namespace Bitrix\Crm\Recurring\DateType;

use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;

class Month
{
	/**
	 * @param array $params
	 * @param Date $startDate
	 *
	 * @return static
	 */
	public static function calculateDate(array $params, Date $startDate)
	{
		if ((int)$params['INTERVAL_MONTH'] < 0)
		{
			$params['INTERVAL_MONTH'] = 0;
		}
		$month = (int)$startDate->format("n") + (int)$params['INTERVAL_MONTH'];

		$yearValue = (int)$startDate->format("Y");
		if ($month > 12)
		{
			$month = $month - 12;
			$yearValue++;
		}

		if ((int)$params['TYPE'] === 1)
		{
			$monthDays = date('t', mktime(0, 0, 0, $month, 1, $yearValue));
			if ((int)$params['INTERVAL_DAY'] > $monthDays)
			{
				$day = 0;
			}
			elseif ((int)$params['INTERVAL_DAY'] <= 0 || $params['IS_WORKDAY'] === 'Y')
			{
				$day = 1;
			}
			else
			{
				$day = (int)$params['INTERVAL_DAY'];
			}
			$timestamp = mktime(0, 0, 0, $month, $day, $yearValue);
			$date = Date::createFromTimestamp($timestamp);

			if ($params['IS_WORKDAY'] === 'Y')
			{
				if ($day === 0)
				{
					/** First working day of next month */
					$params['INTERVAL_DAY'] = 1;
				}
				$cloneDate = clone($date);
				$date = Day::calculateDate($params,	$date);
				if ($startDate->getTimestamp() > $date->getTimestamp() && $params['INTERVAL_MONTH'] === 0)
				{
					$date = Day::calculateDate($params,	$cloneDate->add("+1 months"));
				}
			}
		}
		elseif ((int)$params['TYPE'] === 2)
		{
			$date = mktime(0, 0, 0, $month, 1, $yearValue);
			$date = Date::createFromTimestamp($date);

			$numWeekDay = (int)$date->format('N');

			if ($numWeekDay <= $params['WEEKDAY'])
			{
				$offset = $params['WEEKDAY'] - $numWeekDay;
			}
			else
			{
				$offset = 7 + $params['WEEKDAY'] - $numWeekDay;
			}

			$date->add("+ ".$offset."days");

			if ((int)$params['INTERVAL_WEEK'] <= 3)
			{
				$date->add("+".(int)$params['INTERVAL_WEEK']." weeks");
			}
			else
			{
				$date->add("+3 weeks");
				$restDays = (int)(date('t', mktime(0, 0, 0, $month, 1, $yearValue))) - (int)($date->format('j'));
				if ($restDays >= 7)
				{
					$date->add("+1 weeks");
				}
			}
		}

		if ($startDate->getTimestamp() > $date->getTimestamp() && $params['INTERVAL_MONTH'] === 0)
			$date->add("+1 months");

		return $date;
	}
}