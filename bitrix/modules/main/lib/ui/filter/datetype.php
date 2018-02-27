<?

namespace Bitrix\Main\UI\Filter;

class DateType
{
	const NONE = "NONE";
	const YESTERDAY = "YESTERDAY";
	const CURRENT_DAY = "CURRENT_DAY";
	const CURRENT_WEEK = "CURRENT_WEEK";
	const CURRENT_MONTH = "CURRENT_MONTH";
	const CURRENT_QUARTER = "CURRENT_QUARTER";
	const LAST_7_DAYS = "LAST_7_DAYS";
	const LAST_30_DAYS = "LAST_30_DAYS";
	const LAST_60_DAYS = "LAST_60_DAYS";
	const LAST_90_DAYS = "LAST_90_DAYS";
	const MONTH = "MONTH";
	const QUARTER = "QUARTER";
	const YEAR = "YEAR";
	const EXACT = "EXACT";
	const LAST_WEEK = "LAST_WEEK";
	const LAST_MONTH = "LAST_MONTH";
	const RANGE = "RANGE";

	public static function getList()
	{
		$reflection = new \ReflectionClass(__CLASS__);
		return $reflection->getConstants();
	}
}