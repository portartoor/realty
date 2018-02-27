<?php

namespace Bitrix\Crm\Ads;

use \Bitrix\Seo\Retargeting\Audience;
use \Bitrix\Seo\Retargeting\Service;

/*
class AdsAudienceConfig
{
	public $type;
	public $accountId;
	public $audienceId;
	public $contactType = null;
}
*/

class AdsAudience
{
	protected static $errors = array();
	protected static $logs = array();
	protected static $isLogsEnabled = false;
	protected static $isQueueUsed = false;

	public static function enableLogs()
	{
		self::$logs = array();
		self::$isLogsEnabled = true;
	}

	public static function useQueue()
	{
		self::$isQueueUsed = true;
	}

	protected static function log($message)
	{
		if (self::$isLogsEnabled)
		{
			self::$logs[] = $message;
		}
	}

	public static function getLogs()
	{
		return self::$logs;
	}

	public static function getErrors()
	{
		return self::$errors;
	}

	public static function resetErrors()
	{
		self::$errors = array();
	}

	public static function hasErrors()
	{
		return count(self::$errors) > 0;
	}

	public static function removeAuth($type)
	{
		Service::getAuthAdapter($type)->removeAuth();
	}

	public static function getAccounts($type)
	{
		$result = array();

		$account = Service::getAccount($type);
		$accountsResult = $account->getList();
		if ($accountsResult->isSuccess())
		{
			while ($accountData = $accountsResult->fetch())
			{
				$accountData = $account->normalizeListRow($accountData);
				if ($accountData['ID'])
				{
					$result[] = array(
						'id' => $accountData['ID'],
						'name' => $accountData['NAME'] ? $accountData['NAME'] : $accountData['ID']
					);
				}
			}
		}
		else
		{
			self::$errors = $accountsResult->getErrorMessages();
		}

		return $result;
	}

	public static function getAudiences($type, $accountId = null)
	{
		$result = array();

		$audience = Service::getAudience($type);

		$audience->setAccountId($accountId);
		$audiencesResult = $audience->getList();
		if ($audiencesResult->isSuccess())
		{
			while ($audienceData = $audiencesResult->fetch())
			{
				$audienceData = $audience->normalizeListRow($audienceData);
				if ($audienceData['ID'])
				{
					$result[] = array(
						'id' => $audienceData['ID'],
						'isSupportMultiTypeContacts' => $audience->isSupportMultiTypeContacts(),
						'supportedContactTypes' => $audienceData['SUPPORTED_CONTACT_TYPES'],
						'name' =>
							$audienceData['NAME']
								?
								$audienceData['NAME'] . (
								$audienceData['COUNT_VALID'] ?
									' (' . $audienceData['COUNT_VALID'] . ')'
									:
									''
								)
								:
								$audienceData['ID']
					);
				}
			}
		}
		else
		{
			self::$errors = $audiencesResult->getErrorMessages();
		}

		return $result;
	}

	public static function getProviders(array $types = null)
	{
		$typeList = Service::getTypes();

		$providers = array();
		foreach ($typeList as $type)
		{
			if ($types && !in_array($type, $types))
			{
				continue;
			}

			$audience = Service::getAudience($type);
			$account = Service::getAccount($type);
			$authAdapter = Service::getAuthAdapter($type);

			$providers[$type] = array(
				'TYPE' => $type,
				'HAS_AUTH' => $authAdapter->hasAuth(),
				'AUTH_URL' => $authAdapter->getAuthUrl(),
				'IS_SUPPORT_ACCOUNT' => $audience->isSupportAccount(),
				'IS_SUPPORT_REMOVE_CONTACTS' => $audience->isSupportRemoveContacts(),
				'IS_SUPPORT_MULTI_TYPE_CONTACTS' => $audience->isSupportMultiTypeContacts(),
				'URL_AUDIENCE_LIST' => $audience->getUrlAudienceList(),
				'PROFILE' => $account->getProfileCached(),
			);

			// check if no profile, then may be auth was removed in service
			if ($providers[$type]['HAS_AUTH'] && empty($providers[$type]['PROFILE']))
			{
				static::removeAuth($type);
				$providers[$type]['HAS_AUTH'] = false;
			}
		}

		return $providers;
	}

	public static function addFromEntity($entityTypeId, $entityId, \stdClass $config)
	{
		$authAdapter = Service::getAuthAdapter($config->type);
		if (!$authAdapter->hasAuth())
		{
			return false;
		}

		$addresses = self::getAddresses($entityTypeId, $entityId);
		return self::addToAudience($config, $addresses);
	}

	protected static function addToAudience(\stdClass $config, $contacts)
	{
		$audience = Service::getAudience($config->type);
		$audience->setAccountId($config->accountId);
		static::$isQueueUsed ? $audience->enableQueueMode() : $audience->disableQueueMode();
		if ($config->autoRemoveDayNumber)
		{
			$audience->enableQueueAutoRemove($config->autoRemoveDayNumber);
		}
		else
		{
			$audience->disableQueueAutoRemove();
		}

		$audienceImportResult = $audience->addContacts(
			$config->audienceId,
			$contacts,
			array(
				'type' => $config->contactType
			)
		);

		self::$errors = $audienceImportResult->getErrorMessages();
		return $audienceImportResult->isSuccess();
	}

	protected static function getAddresses($entityTypeId, $entityId)
	{
		$result = array();

		$multiFieldTypeToAudienceContactTypeMap = array(
			\CCrmFieldMulti::EMAIL => Audience::ENUM_CONTACT_TYPE_EMAIL,
			\CCrmFieldMulti::PHONE => Audience::ENUM_CONTACT_TYPE_PHONE,
		);

		$entityFilterList = array();
		if (in_array($entityTypeId, array(\CCrmOwnerType::Deal, \CCrmOwnerType::Quote, \CCrmOwnerType::Invoice)))
		{
			$companyFieldCode = 'COMPANY_ID';
			$contactFieldCode = 'CONTACT_ID';
			$subFilter = array('=ID' => $entityId, 'CHECK_PERMISSIONS' => 'N');
			switch ($entityTypeId)
			{
				case \CCrmOwnerType::Deal:
					$entityDb = \CCrmDeal::getListEx(array(), $subFilter);
					break;

				case \CCrmOwnerType::Quote:
					$entityDb = \CCrmQuote::getList(array(), $subFilter);
					break;

				case \CCrmOwnerType::Invoice:
					$companyFieldCode = 'UF_COMPANY_ID';
					$contactFieldCode = 'UF_CONTACT_ID';
					$entityDb = \CCrmInvoice::getList(array(), $subFilter);
					break;

				default:
					return $result;
			}

			$entityData = $entityDb->fetch();
			if (isset($entityData[$contactFieldCode]) && $entityData[$contactFieldCode])
			{
				$entityFilterList[\CCrmOwnerType::Contact] = $entityData[$contactFieldCode];
			}
			if (isset($entityData[$companyFieldCode]) && $entityData[$companyFieldCode])
			{
				$entityFilterList[\CCrmOwnerType::Company] = $entityData[$companyFieldCode];
			}
		}
		else
		{
			$entityFilterList[$entityTypeId] = $entityId;
		}

		foreach ($entityFilterList as $entityTypeId => $entityId)
		{
			$entityTypeName = \CCrmOwnerType::ResolveName($entityTypeId);
			if (!$entityTypeName)
			{
				continue;
			}
			if (!$entityId)
			{
				continue;
			}

			$multiFieldDb = \CCrmFieldMulti::GetListEx(
				null,
				array(
					'ENTITY_ID' => $entityTypeName,
					'ELEMENT_ID' => $entityId,
					'TYPE_ID' => array(
						\CCrmFieldMulti::EMAIL,
						\CCrmFieldMulti::PHONE
					)
				)
			);
			while($multiField = $multiFieldDb->Fetch())
			{
				if (!isset($multiFieldTypeToAudienceContactTypeMap[$multiField['TYPE_ID']]))
				{
					continue;
				}

				$contactType = $multiFieldTypeToAudienceContactTypeMap[$multiField['TYPE_ID']];
				if (!is_array($result[$contactType]))
				{
					$result[$contactType] = array();
				}

				$result[$contactType][] = $multiField['VALUE'];
			}
		}

		return $result;
	}
}