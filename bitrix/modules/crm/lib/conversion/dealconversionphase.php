<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Main;
class DealConversionPhase
{
	const INTERMEDIATE = 0;
	const INVOICE_CREATION = 1;
	const QUOTE_CREATION = 2;
	const FINALIZATION = 16;
	public static function isDefined($phaseID)
	{
		if(!is_numeric($phaseID))
		{
			return false;
		}

		$phaseID = (int)$phaseID;
		return $phaseID >= self::INVOICE_CREATION && $phaseID <= self::QUOTE_CREATION;
	}
}