<?

namespace Bitrix\Main\Grid\Editor;


class Types
{
	const DROPDOWN = "DROPDOWN";
	const CHECKBOX = "CHECKBOX";
	const TEXT = "TEXT";
	const DATE = "DATE";
	const NUMBER = "NUMBER";
	const RANGE = "RANGE";
	const TEXTAREA = "TEXTAREA";
	const CUSTOM = "CUSTOM";

	public static function getList()
	{
		$reflection = new \ReflectionClass(__CLASS__);
		return $reflection->getConstants();
	}
}