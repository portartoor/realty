<?php
namespace Bitrix\Crm\Recurring\DateType;

use Bitrix\Main;
use Bitrix\Main\Type\Date;

class Year
{
	/**
	 * @param array $params
	 * @param Date $startDate
	 * 
	 * @return static
	 */
	public static function calculateDate(array $params, Date $startDate)
	{
		$month = (int)$params['INTERVAL_MONTH'];
		$params['INTERVAL_MONTH'] = (int)$params['INTERVAL_MONTH'] < 12 ? (int)$params['INTERVAL_MONTH'] : 12;
		$yearValue = (int)$startDate->format("Y");
		if ($month < (int)$startDate->format("n"))
		{
			$yearValue++;
		}

		$date = mktime(0, 0, 0, 12, 1, $yearValue - 1);
		$date = Date::createFromTimestamp($date);
		/** @var Date $resultDate */
		$resultDate = Month::calculateDate($params, $date);
		if ($startDate->getTimestamp() > $resultDate->getTimestamp())
			$resultDate = Month::calculateDate($params, $date->add("+1 year"));

		return $resultDate;
	}
}