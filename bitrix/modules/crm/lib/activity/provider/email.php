<?php
namespace Bitrix\Crm\Activity\Provider;

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Activity;
use Bitrix\Crm\Activity\CommunicationStatistics;

Loc::loadMessages(__FILE__);

class Email extends Activity\Provider\Base
{
	public static function getId()
	{
		return 'CRM_EMAIL';
	}

	public static function getTypeId(array $activity)
	{
		return 'EMAIL';
	}

	public static function getTypes()
	{
		return array(
			array(
				'NAME' => 'E-mail',
				'PROVIDER_ID' => static::getId(),
				'PROVIDER_TYPE_ID' => 'EMAIL'
			)
		);
	}

	public static function getName()
	{
		return 'E-mail';
	}

	public static function getCommunicationType($providerTypeId = null)
	{
		return static::COMMUNICATION_TYPE_EMAIL;
	}

	/**
	 * @param null|string $providerTypeId Provider type id.
	 * @return bool
	 */
	public static function canUseLiveFeedEvents($providerTypeId = null)
	{
		return true;
	}

	/**
	 * @param null|string $providerTypeId Provider type id.
	 * @param int $direction Activity direction.
	 * @return bool
	 */
	public static function isTypeEditable($providerTypeId = null, $direction = \CCrmActivityDirection::Undefined)
	{
		return false;
	}

	public static function getSupportedCommunicationStatistics()
	{
		return array(
			CommunicationStatistics::STATISTICS_QUANTITY
		);
	}

	public static function renderView(array $activity)
	{
		global $APPLICATION;

		ob_start();

		$APPLICATION->IncludeComponent(
			'bitrix:crm.activity.email', '',
			array(
				'ACTIVITY' => $activity,
				//'PAGE_SIZE' => 2,
			)
		);

		return ob_get_clean();
	}

}
