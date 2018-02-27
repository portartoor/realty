<?php

namespace Bitrix\Sale\Cashbox;

use Bitrix\Main\Localization;
use Bitrix\Main;

Localization\Loc::loadMessages(__FILE__);

class FirstOfd extends Ofd
{
	const ACTIVE_URL = 'http://consumer.1-ofd.ru/v1?';
	const TEST_URL = 'http://test-consumer.1-ofd.ru/v1?';

	/**
	 * @param string $data
	 * @return string
	 */
	public function generateCheckLink($data)
	{
		$url = $this->testMode ? static::TEST_URL : static::ACTIVE_URL;

		return $url.$data;
	}

	/**
	 * @throws Main\NotImplementedException
	 * @return string
	 */
	public static function getName()
	{
		return Localization\Loc::getMessage('SALE_CASHBOX_FIRST_OFD_NAME');
	}

}