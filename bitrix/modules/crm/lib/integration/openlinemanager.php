<?php
namespace Bitrix\Crm\Integration;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class OpenLineManager
{
	/** @var bool|null  */
	private static $isEnabled = null;
	private static $supportedTypes = array(
		'IM' => array(
			'IMOL' => true,
			'OPENLINE' => true,
			'BITRIX24' => true,
			'FACEBOOK' => true,
			'TELEGRAM' => true,
			'VK' => true,
			'VIBER' => true,
			'INSTAGRAM' => true
		)
	);

	/**
	 * Check if current manager enabled.
	 * @return bool
	 */
	public static function isEnabled()
	{
		if(self::$isEnabled === null)
		{
			self::$isEnabled = ModuleManager::isModuleInstalled('imopenlines')
				&& Loader::includeModule('imopenlines');
		}
		return self::$isEnabled;
	}

	public static function prepareMultiFieldLinkAttributes($typeName, $valueTypeID, $value)
	{
		if(!(isset(self::$supportedTypes[$typeName]) && isset(self::$supportedTypes[$typeName][$valueTypeID])))
		{
			return null;
		}

		$items = explode('|', $value);
		if(!(is_array($items) && count($items) > 2 && $items[0] === 'imol'))
		{
			return null;
		}

		$typeID = $items[1];
		$suffix = strtoupper(preg_replace('/[^a-z0-9]/i', '', $typeID));
		$text = Loc::getMessage("CRM_OPEN_LINE_{$suffix}");
		if($text === null)
		{
			$text = Loc::getMessage('CRM_OPEN_LINE_SEND_MESSAGE');
		}

		return array(
			'HREF' => '#',
			'ONCLICK' => "if(typeof(BXIM)!=='undefined') BXIM.openMessenger('{$value}'); return BX.PreventDefault(event);",
			'TEXT' => $text,
			'TITLE' => $text
		);
	}
}