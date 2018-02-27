<?php
namespace Bitrix\ImBot\Bot;

if (\CModule::IncludeModule('bitrix24'))
{
	class Support extends \Bitrix\Bitrix24\SupportBot
	{
	}
}
else
{
	class Support extends Base
	{
		const BOT_CODE = "support";
		const INSTALL_WITH_MODULE = false;
		public static function register(array $params = array())
		{ return false; }
		public static function unRegister($code = '', $serverRequest = true)
		{ return true; }
		public static function isEnabled()
		{ return false; }
	}
}
