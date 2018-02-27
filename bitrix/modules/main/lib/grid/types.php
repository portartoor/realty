<?

namespace Bitrix\Main\Grid;

class Types
{
	const GRID_CHECKBOX = "checkbox";
	const GRID_TEXT = "text";
	const GRID_INT = "int";
	const GRID_CUSTOM = "custom";
	const GRID_LIST = "list";
	const GRID_GRID = "grid";

	public static function getList()
	{
		$reflection = new \ReflectionClass(__CLASS__);
		return $reflection->getConstants();
	}
}