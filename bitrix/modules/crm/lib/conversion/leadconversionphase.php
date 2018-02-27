<?php
namespace Bitrix\Crm\Conversion;
use Bitrix\Main;
class LeadConversionPhase
{
	const INTERMEDIATE = 0;
	const COMPANY_CREATION = 1;
	const CONTACT_CREATION = 2;
	const DEAL_CREATION = 3;
	const FINALIZATION = 16;

	public static function isDefined($phaseID)
	{
		if(!is_numeric($phaseID))
		{
			return false;
		}

		$phaseID = (int)$phaseID;
		return $phaseID >= self::COMPANY_CREATION && $phaseID <= self::DEAL_CREATION;
	}
}