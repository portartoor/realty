<?php

namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Faceid\AgreementTable;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class Visit extends Base
{
	const PROVIDER_ID = 'VISIT_TRACKER';
	CONST TYPE_VISIT = 'VISIT';

	public static function getId()
	{
		return self::PROVIDER_ID;
	}

	public static function getName()
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_VISIT_TITLE');
	}

	/**
	 * @param array $activity
	 * @return string
	 */
	public static function getPlannerTitle(array $activity)
	{
		return Loc::getMessage('CRM_ACTIVITY_PROVIDER_VISIT_PLANNER_TITLE');
	}

	/**
	 * Returns supported provider's types
	 * @return array
	 */
	public static function getTypes()
	{
		return array(
			array(
				'NAME' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_VISIT_TITLE'),
				'PROVIDER_ID' => static::PROVIDER_ID,
				'PROVIDER_TYPE_ID' => static::TYPE_VISIT,
				'DIRECTIONS' => array(
					\CCrmActivityDirection::Incoming => Loc::getMessage('CRM_ACTIVITY_PROVIDER_VISIT_TITLE'),
				),
			),
		);
	}

	/**
	 * @return int
	 */
	public static function prepareToolbarButtons(array &$buttons, array $params = null)
	{
		$ownerTypeId = isset($params['OWNER_TYPE_ID']) ? (int)$params['OWNER_TYPE_ID'] : \CCrmOwnerType::Undefined;
		$ownerId = isset($params['OWNER_ID']) ? (int)$params['OWNER_ID'] : 0;
		
		$visitParams = self::getPopupParameters();
		if($ownerTypeId && $ownerId)
		{
			$visitParams['OWNER_TYPE'] = \CCrmOwnerType::ResolveName($ownerTypeId);
			$visitParams['OWNER_ID'] = $ownerId;
		}

		$buttons[] = array(
			'TEXT' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_VISIT_BUTTON'),
			'TITLE' => Loc::getMessage('CRM_ACTIVITY_PROVIDER_VISIT_BUTTON_TITLE'),
			'ONCLICK' => "BX.CrmActivityVisit.create(".\CUtil::PhpToJSObject($visitParams).").showEdit()",
			'ICON' => "btn-new"
		);
		return 1;
	}

	/**
	 * @inheritdoc
	 */
	public static function renderView(array $activity)
	{
		global $APPLICATION;

		ob_start();
		$APPLICATION->IncludeComponent(
			'bitrix:crm.activity.visit',
			'',
			array(
				'ACTIVITY' => $activity
			)
		);
		return ob_get_clean();
	}

	/**
	 * Returns array of parameters required to create instance of BX.CrmActivityVisit.
	 * @return array
	 */
	public static function getPopupParameters()
	{
		return array(
			'HAS_CONSENT' => (self::hasConsent() ? 'Y' : 'N'),
			'FACEID_INSTALLED' => ModuleManager::isModuleInstalled('faceid') ? 'Y' : 'N',
			'HAS_RECOGNIZE_CONSENT' => (self::hasRecognizeConsent() ? 'Y' : 'N'),
		);
	}

	protected function hasConsent()
	{
		$consent = (array)\CUserOptions::GetOption('crm.activity.visit', 'consent', array());
		return ($consent['timestamp'] > 0);
	}

	protected function hasRecognizeConsent()
	{
		global $USER;
		$userId = $USER->getId();

		if(!Loader::includeModule('faceid'))
			return false;

		$row = AgreementTable::getList(array(
			'select' => array('ID'),
			'filter' => array(
				'=USER_ID' => $userId
			)
		))->fetch();

		return (bool)$row;
	}
}