<?php
namespace Bitrix\Crm\Settings;
use Bitrix\Main;
class QuoteSettings
{
	/** @var QuoteSettings  */
	private static $current = null;
	/** @var BooleanSetting  */
	private $enableViewEvent = null;
	/** @var BooleanSetting  */
	private $isOpened = null;

	function __construct()
	{
		$this->enableViewEvent = new BooleanSetting('quote_enable_view_event', true);
		$this->isOpened = new BooleanSetting('quote_opened_flag', true);
	}
	/**
	 * Get current instance
	 * @return QuoteSettings
	 */
	public static function getCurrent()
	{
		if(self::$current === null)
		{
			self::$current = new QuoteSettings();
		}
		return self::$current;
	}
	/**
	 * Get value of flag 'OPENED'
	 * @return bool
	 */
	public function getOpenedFlag()
	{
		return $this->isOpened->get();
	}
	/**
	 * Set value of flag 'OPENED'
	 * @param bool $opened Opened Flag.
	 * @return void
	 */
	public function setOpenedFlag($opened)
	{
		$this->isOpened->set($opened);
	}
}