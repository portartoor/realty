<?php

namespace Bitrix\Sale\Cashbox;

use Bitrix\Main\NotImplementedException;

abstract class Ofd
{
	protected $testMode = false;

	/**
	 * @return array
	 */
	public static function getHandlerList()
	{
		return array(
			'\Bitrix\Sale\Cashbox\FirstOfd' => FirstOfd::getName(),
			'\Bitrix\Sale\Cashbox\PlatformaOfd' => PlatformaOfd::getName(),
			'\Bitrix\Sale\Cashbox\YarusOfd' => YarusOfd::getName(),
			'\Bitrix\Sale\Cashbox\TaxcomOfd' => TaxcomOfd::getName(),
			'\Bitrix\Sale\Cashbox\OfdruOfd' => OfdruOfd::getName(),
		);
	}

	/**
	 * @param $handler
	 * @param bool $testMode
	 * @return null
	 */
	public static function create($handler, $testMode = false)
	{
		if (class_exists($handler))
			return new $handler($testMode);

		return null;
	}

	/**
	 * Ofd constructor.
	 * @param $testMode
	 */
	private function __construct($testMode)
	{
		$this->testMode = $testMode;
	}

	/**
	 * @param $data
	 * @return string
	 */
	abstract public function generateCheckLink($data);

	/**
	 * @throws NotImplementedException
	 * @return string
	 */
	public static function getName()
	{
		throw new NotImplementedException();
	}
}