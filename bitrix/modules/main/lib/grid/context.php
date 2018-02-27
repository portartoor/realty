<?

namespace Bitrix\Main\Grid;

class Context
{
	protected static function getRequest()
	{
		return \Bitrix\Main\Context::getCurrent()->getRequest();
	}

	public static function isInternalRequest()
	{
		$request = self::getRequest();
		return (
			($request->get("internal") == true && $request->get("grid_id")) ||
			($request->getPost("internal") == true && $request->getPost("grid_id"))
		);
	}
}