<?php
namespace Bitrix\Crm\Recurring\DateType;

use Bitrix\Main;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Loader;

class Day 
{
	/**
	 * @param array $params
	 * @param Date $date
	 *
	 * @return Date
	 */
	public static function calculateDate(array $params, Date $date)
	{
		if ((int)$params['INTERVAL_DAY'] <= 0)
			$params['INTERVAL_DAY'] = 1;
		if ($params['IS_WORKDAY'] === 'Y')
		{
			$weekDays = array('SU' => 0, 'MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6);

			Loader::includeModule('calendar');
			$calendarSettings = \CCalendar::GetSettings();
			$weekHolidays = array_keys(array_intersect(array_flip($weekDays), $calendarSettings['week_holidays']));
			$yearHolidays = explode(',',$calendarSettings['year_holidays']);

			$interval = (int)$params['INTERVAL_DAY'];

			while ($interval > 0)
			{
				if (!in_array($date->format("j.m"), $yearHolidays) && !in_array($date->format("w"), $weekHolidays))
				{
					$interval--;
				}
				if ($interval > 0)
				{
					$date->add('+1 days');
				}
			}
		}
		else
		{
			$date = $date->add(" +" . ((int)$params['INTERVAL_DAY'] - 1). " days");
		}

		return $date;
	}
}