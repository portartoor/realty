<?

namespace Bitrix\Main\Grid\Panel;


class Types
{
	const DROPDOWN = "DROPDOWN";
	const CHECKBOX = "CHECKBOX";
	const TEXT = "TEXT";
	const BUTTON = "BUTTON";
	const LINK = "LINK";
	const CUSTOM = "CUSTOM";
	const HIDDEN = "HIDDEN";

	public static function getList()
	{
		$reflection = new \ReflectionClass(__CLASS__);
		return $reflection->getConstants();
	}
}