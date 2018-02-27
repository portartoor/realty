<?php

namespace Bitrix\Sale\Cashbox;

use Bitrix\Main\Localization;
use Bitrix\Main;

Localization\Loc::loadMessages(__FILE__);

class TaxcomOfd extends Ofd
{
	/**
	 * @param string $data
	 * @return string
	 */
	public function generateCheckLink($data)
	{
		return '';
	}

	/**
	 * @throws Main\NotImplementedException
	 * @return string
	 */
	public static function getName()
	{
		return Localization\Loc::getMessage('SALE_CASHBOX_TAXCOM_OFD_NAME');
	}

}