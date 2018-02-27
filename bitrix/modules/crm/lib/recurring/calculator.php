<?php
namespace Bitrix\Crm\Recurring;

use Bitrix\Main;
use Bitrix\Main\Type\Date;

class Calculator
{
	const SALE_TYPE_DAY_OFFSET = 1;
	const SALE_TYPE_WEEK_OFFSET = 2;
	const SALE_TYPE_MONTH_OFFSET = 3;
	const SALE_TYPE_YEAR_OFFSET = 4;

	/**
	 * @param array $params
	 * @param Date $startDate
	 *
	 * @return Date
	 */
	public static function getNextDate(array $params, Date $startDate = null)
	{
		if (empty($params) || empty($params['PERIOD']))
			return null;
		if (is_null($startDate))
		{
			$startDate = new Date();
		}

		$params = static::prepareCalculationDate($params);

		switch($params['PERIOD'])
		{
			case static::SALE_TYPE_DAY_OFFSET:
				return DateType\Day::calculateDate($params, $startDate);
			case static::SALE_TYPE_WEEK_OFFSET:
				return DateType\Week::calculateDate($params, $startDate);
			case static::SALE_TYPE_MONTH_OFFSET:
				return DateType\Month::calculateDate($params, $startDate);
			case static::SALE_TYPE_YEAR_OFFSET:
				return DateType\Year::calculateDate($params, $startDate);
			default:
				return null;
		}
	}

	/**
	 * @param array $params
	 *
	 * @return array
	 */
	public static function prepareCalculationDate(array $params)
	{
		$result = array(
			"PERIOD" => (int)$params['PERIOD'] ? (int)$params['PERIOD'] : null
		);

		switch($result['PERIOD'])
		{
			case static::SALE_TYPE_DAY_OFFSET:
				$result['INTERVAL_DAY'] = $params['DAILY_INTERVAL_DAY'];
				$result['IS_WORKDAY'] = $params['DAILY_WORKDAY_ONLY'];
				break;
			case static::SALE_TYPE_WEEK_OFFSET:
				
				$result['WEEKDAYS'] = $params['WEEKLY_WEEK_DAYS'];
				$result['INTERVAL_WEEK'] = $params['WEEKLY_INTERVAL_WEEK'];
				break;
			case static::SALE_TYPE_MONTH_OFFSET:
				$result['INTERVAL_DAY'] = $params['MONTHLY_INTERVAL_DAY'];
				if ((int)$params['MONTHLY_TYPE'] === 1)
				{
					$result['INTERVAL_MONTH'] = $params['MONTHLY_MONTH_NUM_1'] - 1;
					$result['IS_WORKDAY'] = $params['MONTHLY_WORKDAY_ONLY'];
				}
				elseif ((int)$params['MONTHLY_TYPE'] === 2)
				{
					$result['INTERVAL_WEEK'] = $params['MONTHLY_WEEKDAY_NUM'];
					$result['INTERVAL_MONTH'] = $params['MONTHLY_MONTH_NUM_2'] - 1;
					$result['WEEKDAY'] = $params['MONTHLY_WEEK_DAY'];
				}
				$result['TYPE'] = $params['MONTHLY_TYPE'];
				break;
			case static::SALE_TYPE_YEAR_OFFSET:
				$result['INTERVAL_DAY'] = $params['YEARLY_INTERVAL_DAY'];

				if ((int)$params['YEARLY_TYPE'] === 1)
				{
					$result['INTERVAL_DAY'] = $params['YEARLY_INTERVAL_DAY'];
					$result['INTERVAL_MONTH'] = $params['YEARLY_MONTH_NUM_1'];
					$result['IS_WORKDAY'] = $params['YEARLY_WORKDAY_ONLY'];
				}
				elseif ((int)$params['YEARLY_TYPE'] === 2)
				{
					$result['INTERVAL_WEEK'] = $params['YEARLY_WEEK_DAY_NUM'];
					$result['INTERVAL_MONTH'] = $params['YEARLY_MONTH_NUM_2'];
					$result['WEEKDAY'] = $params['YEARLY_WEEK_DAY'];
				}
				$result['TYPE'] = $params['YEARLY_TYPE'];
		}
		
		return $result;
	}
}