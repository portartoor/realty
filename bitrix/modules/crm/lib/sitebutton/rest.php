<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2016 Bitrix
 */

namespace Bitrix\Crm\SiteButton;

/**
 * Class Rest
 * @package Bitrix\Crm\SiteButton
 */
class Rest
{
	public static function onRestServiceBuildDescription()
	{
		return array(
			'crm' => array(
				'crm.button.list' => array(__CLASS__, 'getButtonList'),
			)
		);
	}

	public static function getButtonList()
	{
		if (Preset::checkVersion())
		{
			$preset = new Preset();
			$preset->install();
		}

		$result = array();
		$buttonList = Manager::getList(array(
			'filter' => array('=ACTIVE' => 'Y'),
			'order' => array('ID' => 'DESC')
		));
		foreach ($buttonList as $button)
		{
			$button['DATE_CREATE'] = \CRestUtil::ConvertDateTime($button['DATE_CREATE']);
			$button['ACTIVE_CHANGE_DATE'] = \CRestUtil::ConvertDateTime($button['ACTIVE_CHANGE_DATE']);
			$result[] = $button;
		}

		return $result;
	}

}
