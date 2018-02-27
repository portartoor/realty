<?

namespace Bitrix\Main\UI\Filter;


class Type
{
	const STRING = "STRING";
	const NUMBER = "NUMBER";
	const DATE = "DATE";
	const SELECT = "SELECT";
	const MULTI_SELECT = "MULTI_SELECT";
	const USER = "USER";
	const ENTITY = "ENTITY";
	const CUSTOM = "CUSTOM";
	const CUSTOM_ENTITY = "CUSTOM_ENTITY";

	public static function getList()
	{
		$reflection = new \ReflectionClass(__CLASS__);
		return $reflection->getConstants();
	}
}