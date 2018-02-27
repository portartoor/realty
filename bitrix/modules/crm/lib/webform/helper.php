<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage crm
 * @copyright 2001-2016 Bitrix
 */
namespace Bitrix\Crm\WebForm;

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Helper
{
	const ENUM_TEMPLATE_LIGHT = 'light';
	const ENUM_TEMPLATE_TRANSPARENT = 'transp';
	const ENUM_TEMPLATE_COLORED = 'colored';

	public static function getTemplateList()
	{
		return array(
			static::ENUM_TEMPLATE_LIGHT => Loc::getMessage('CRM_WEBFORM_HELPER_TEMPLATE_LIGHT'),
			static::ENUM_TEMPLATE_TRANSPARENT => Loc::getMessage('CRM_WEBFORM_HELPER_TEMPLATE_TRANSPARENT'),
			static::ENUM_TEMPLATE_COLORED => Loc::getMessage('CRM_WEBFORM_HELPER_TEMPLATE_COLORED'),
		);
	}

	public static function getFieldStringTypes()
	{
		return array(
			'phone' => Loc::getMessage('CRM_WEBFORM_HELPER_STRING_TYPES_PHONE'),
			'email' => Loc::getMessage('CRM_WEBFORM_HELPER_STRING_TYPES_EMAIL'),
			//'int' => Loc::getMessage('CRM_WEBFORM_HELPER_STRING_TYPES_INT'),
		);
	}

	public static function getExternalAnalyticsData($formName = '%name%')
	{
		return array(
			'category' => Loc::getMessage('CRM_WEBFORM_HELPER_EXTERNAL_ANALYTICS_CATEGORY') . ' "' . $formName . '"',
			'template' => array('name' => '%name%', 'code' => 'B24_%code%.html'),
			'eventTemplate' => array('name' => '%name%', 'code' => 'B24_FORM_%form_id%_%code%'),
			'field' => array('name' => Loc::getMessage('CRM_WEBFORM_HELPER_EXTERNAL_ANALYTICS_FIELD') . ' "%name%"', 'code' => '%code%'),
			'view' => array('name' => Loc::getMessage('CRM_WEBFORM_HELPER_EXTERNAL_ANALYTICS_VIEW'), 'code' => 'VIEW'),
			'start' => array('name' => Loc::getMessage('CRM_WEBFORM_HELPER_EXTERNAL_ANALYTICS_START'), 'code' => 'START'),
			'end' => array('name' => Loc::getMessage('CRM_WEBFORM_HELPER_EXTERNAL_ANALYTICS_END'), 'code' => 'END'),
		);
	}
}
