<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 * @internal
 */

namespace Bitrix\Tasks\Item\Task\Template\Field\Legacy;

final class Responsible extends \Bitrix\Tasks\Item\Field\Integer
{
	public function getValue($key, $item, array $parameters = array())
	{
		return $item['RESPONSIBLES']->first();
	}

	public function setValue($value, $key, $item, array $parameters = array())
	{
		$resp = $item['RESPONSIBLES']->toArray();
		array_shift($resp);

		if($value)
		{
			array_unshift($resp, $value);
		}

		$item['RESPONSIBLES']->set($resp);
	}
}