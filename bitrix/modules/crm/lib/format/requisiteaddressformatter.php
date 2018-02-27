<?php
namespace Bitrix\Crm\Format;
use Bitrix\Main;
use Bitrix\Crm\RequisiteAddress;

class RequisiteAddressFormatter extends EntityAddressFormatter
{
	public static function prepareLines(array $fields, array $options = null)
	{
		return parent::prepareLines(RequisiteAddress::mapEntityFields($fields, $options), $options);
	}
	public static function format(array $fields, array $options = null)
	{
		return parent::formatLines(self::prepareLines($fields, $options), $options);
	}
}