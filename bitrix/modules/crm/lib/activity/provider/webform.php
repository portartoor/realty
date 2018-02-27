<?php
namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Crm\Activity\CommunicationStatistics;
use Bitrix\Crm\WebForm\Internals\FormTable;
use Bitrix\Crm\WebForm\Result as WebFormResult;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class WebForm extends Base
{
	const PROVIDER_ID = 'CRM_WEBFORM';

	public static function getId()
	{
		return static::PROVIDER_ID;
	}

	/**
	 * Get Provider Name
	 * @return string
	 */
	public static function getName()
	{
		return Loc::getMessage('CRM_ACTIVITY_WEBFORM_NAME');
	}
	
	/**
	 * Is type editable
	 * @param null|string $providerId Provider id.
	 * @param int $direction Activity direction.
	 * @return bool
	 */
	public static function isTypeEditable($providerId = null, $direction = \CCrmActivityDirection::Undefined)
	{
		return false;
	}

	/**
	 * Checks provider status.
	 * @return bool
	 */
	public static function isActive()
	{
		static $formCount = null;
		if($formCount === null)
		{
			$formCount = FormTable::getCount();
		}

		return $formCount > 0;
	}

	/**
	 * Provider status anchor (active, inactive, settings URL etc.)
	 * @return array
	 */
	public static function getStatusAnchor()
	{
		return array(
			'TEXT' => static::isActive() ? Loc::getMessage('CRM_ACTIVITY_WEBFORM_STATUS_ACT') : Loc::getMessage('CRM_ACTIVITY_WEBFORM_STATUS_INACT'),
			'URL' => Option::get('crm', 'path_to_webform_list', '/crm/webform/list/'),
		);
	}

	/**
	 * Return type list
	 * @return array
	 */
	public static function getTypes()
	{
		$types = array();
		$formDb = FormTable::getList(array(
			'select' => array('ID', 'NAME'),
			'order' => array('NAME' => 'ASC', 'ID' => 'ASC'),
		));
		while($form = $formDb->fetch())
		{
			$types[] = array(
				'PROVIDER_ID' => self::PROVIDER_ID,
				'PROVIDER_TYPE_ID' => $form['ID'],
				'NAME' => $form['NAME'],
				'DIRECTIONS' => array(
					\CCrmActivityDirection::Incoming => $form['NAME']
				)
			);
		}

		return $types;
	}

	/**
	 * @return array
	 */
	public static function getTypesFilterPresets()
	{
		return array(
			array(
				'NAME' => Loc::getMessage('CRM_ACTIVITY_WEBFORM_NAME')
			)
		);
	}

	/**
	 * Render View
	 * @param array $activity Activity data.
	 * @return string Rendered html view for specified mode.
	 */
	public static function renderView(array $activity)
	{
		$fieldTemplate = '
			<label class="crm-task-list-form-container" for="">
				<span class="crm-task-list-form-name">%caption%%required%:</span>
				<span class="crm-task-list-form-field">%values%</span>
			</label>
		';
		$fieldsString = WebFormResult::formatFieldsByTemplate($activity['PROVIDER_PARAMS']['FIELDS'], $fieldTemplate, '%value%<br>');
		$link = htmlspecialcharsbx($activity['PROVIDER_PARAMS']['FORM']['LINK']);

		return '
			<div class="crm-task-list-form">
				<div class="crm-task-list-form-inner">
					' . $fieldsString . '
				</div><!--crm-task-list-form-inner-->
				<div class="crm-task-list-form-adress">
					<div class="crm-task-list-form-adress-name">' . Loc::getMessage('CRM_ACTIVITY_WEBFORM_FIELDS_LINK') . ':</div>
					<a href="' . $link . '" class="crm-task-list-form-adress-link">' . $link . '</a>
				</div><!--crm-task-list-form-adress-->
			</div><!--crm-task-list-form-->
		';
	}

	public static function getSupportedCommunicationStatistics()
	{
		return array(
			CommunicationStatistics::STATISTICS_QUANTITY,
			CommunicationStatistics::STATISTICS_MONEY
		);
	}

	public static function canCompleteOnView($providerTypeId = null)
	{
		return true;
	}

}