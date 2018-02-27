<?

namespace Bitrix\Main\UI\Filter;

class NumberType
{
	const SINGLE = "exact";
	const RANGE = "range";
	const MORE = "more";
	const LESS = "less";

	public static function getList()
	{
		$reflection = new \ReflectionClass(__CLASS__);
		return $reflection->getConstants();
	}
}