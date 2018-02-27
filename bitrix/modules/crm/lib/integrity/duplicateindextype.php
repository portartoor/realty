<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;
use Bitrix\Crm\CommunicationType;

class DuplicateIndexType
{
	const UNDEFINED = 0;
	const PERSON = 1;
	const ORGANIZATION = 2;
	const COMMUNICATION_PHONE = 4;
	const COMMUNICATION_EMAIL = 8;
	const COMMUNICATION_FACEBOOK = 16;
	const COMMUNICATION_TELEGRAM = 32;
	const COMMUNICATION_VK = 64;
	const COMMUNICATION_SKYPE = 128;
	const COMMUNICATION_BITRIX24 = 256;
	const COMMUNICATION_OPENLINE = 512;

	const COMMUNICATION = 1020; /*COMMUNICATION_PHONE|COMMUNICATION_EMAIL|COMMUNICATION_FACEBOOK|COMMUNICATION_TELEGRAM|COMMUNICATION_VK|COMMUNICATION_SKYPE|COMMUNICATION_BITRIX24|COMMUNICATION_OPENLINE*/
	const DENOMINATION = 3; /*PERSON|ORGANIZATION*/
	const ALL = 1023; /*PERSON|ORGANIZATION|COMMUNICATION_PHONE|COMMUNICATION_EMAIL|COMMUNICATION_FACEBOOK|COMMUNICATION_TELEGRAM|COMMUNICATION_VK|COMMUNICATION_SKYPE|COMMUNICATION_BITRIX24|COMMUNICATION_OPENLINE*/

	const PERSON_NAME = 'PERSON';
	const ORGANIZATION_NAME = 'ORGANIZATION';
	const COMMUNICATION_PHONE_NAME = 'COMMUNICATION_PHONE';
	const COMMUNICATION_EMAIL_NAME = 'COMMUNICATION_EMAIL';
	const COMMUNICATION_FACEBOOK_NAME = 'COMMUNICATION_FACEBOOK';
	const COMMUNICATION_TELEGRAM_NAME = 'COMMUNICATION_TELEGRAM';
	const COMMUNICATION_VK_NAME = 'COMMUNICATION_VK';
	const COMMUNICATION_SKYPE_NAME = 'COMMUNICATION_SKYPE';
	const COMMUNICATION_BITRIX24_NAME = 'COMMUNICATION_BITRIX24';
	const COMMUNICATION_OPENLINE_NAME = 'COMMUNICATION_OPENLINE';

	private static $allDescriptions = array();

	/**
	 * Check if type defined
	 * @param int $typeID Type ID.
	 * @return bool
	 */
	public static function isDefined($typeID)
	{
		if(!is_numeric($typeID))
		{
			return false;
		}

		$typeID = (int)$typeID;
		return $typeID === self::PERSON
			|| $typeID === self::ORGANIZATION
			|| $typeID === self::COMMUNICATION_PHONE
			|| $typeID === self::COMMUNICATION_EMAIL
			|| $typeID === self::COMMUNICATION_FACEBOOK
			|| $typeID === self::COMMUNICATION_TELEGRAM
			|| $typeID === self::COMMUNICATION_VK
			|| $typeID === self::COMMUNICATION_SKYPE
			|| $typeID === self::COMMUNICATION_BITRIX24
			|| $typeID === self::COMMUNICATION_OPENLINE
			|| $typeID === self::DENOMINATION
			|| $typeID === self::COMMUNICATION
			|| $typeID === self::ALL;
	}
	/**
	 * Resolve type name by ID.
	 * @param int $typeID Type ID.
	 * @return string
	 */
	public static function resolveName($typeID)
	{
		if(!is_numeric($typeID))
		{
			return '';
		}

		$typeID = (int)$typeID;
		if($typeID <= 0)
		{
			return '';
		}

		$results = array();
		if(($typeID & self::PERSON) !== 0)
		{
			$results[] = self::PERSON_NAME;
		}
		if(($typeID & self::ORGANIZATION) !== 0)
		{
			$results[] = self::ORGANIZATION_NAME;
		}
		if(($typeID & self::COMMUNICATION_PHONE) !== 0)
		{
			$results[] = self::COMMUNICATION_PHONE_NAME;
		}
		if(($typeID & self::COMMUNICATION_EMAIL) !== 0)
		{
			$results[] = self::COMMUNICATION_EMAIL_NAME;
		}
		if(($typeID & self::COMMUNICATION_FACEBOOK) !== 0)
		{
			$results[] = self::COMMUNICATION_FACEBOOK_NAME;
		}
		if(($typeID & self::COMMUNICATION_TELEGRAM) !== 0)
		{
			$results[] = self::COMMUNICATION_TELEGRAM_NAME;
		}
		if(($typeID & self::COMMUNICATION_VK) !== 0)
		{
			$results[] = self::COMMUNICATION_VK_NAME;
		}
		if(($typeID & self::COMMUNICATION_SKYPE) !== 0)
		{
			$results[] = self::COMMUNICATION_SKYPE_NAME;
		}
		if(($typeID & self::COMMUNICATION_BITRIX24) !== 0)
		{
			$results[] = self::COMMUNICATION_BITRIX24_NAME;
		}
		if(($typeID & self::COMMUNICATION_OPENLINE) !== 0)
		{
			$results[] = self::COMMUNICATION_OPENLINE_NAME;
		}
		return implode('|', $results);
	}
	/**
	 * Resolve type ID by name.
	 * @param string $typeName Type name (single or multiple).
	 * @return int
	 */
	public static function resolveID($typeName)
	{
		$typeID = self::innerResolveID($typeName);
		if($typeID !== self::UNDEFINED)
		{
			return $typeID;
		}

		if(strpos($typeName, '|') >= 0)
		{
			$typeNames = explode('|', $typeName);
			foreach($typeNames as $name)
			{
				$typeID |= self::innerResolveID(trim($name));
			}
		}
		return $typeID;
	}
	/**
	 * Resolve type ID by name.
	 * @param string $typeName Type name (only single names are accepted).
	 * @return int
	 */
	private static function innerResolveID($typeName)
	{
		if(!is_string($typeName))
		{
			return self::UNDEFINED;
		}

		$typeName = strtoupper(trim($typeName));
		if($typeName === '')
		{
			return self::UNDEFINED;
		}

		if($typeName === self::PERSON_NAME)
		{
			return self::PERSON;
		}
		if($typeName === self::ORGANIZATION_NAME)
		{
			return self::ORGANIZATION;
		}
		if($typeName === self::COMMUNICATION_PHONE_NAME)
		{
			return self::COMMUNICATION_PHONE;
		}
		if($typeName ===  self::COMMUNICATION_EMAIL_NAME)
		{
			return self::COMMUNICATION_EMAIL;
		}
		if($typeName ===  self::COMMUNICATION_FACEBOOK_NAME)
		{
			return self::COMMUNICATION_FACEBOOK;
		}
		if($typeName ===  self::COMMUNICATION_TELEGRAM_NAME)
		{
			return self::COMMUNICATION_TELEGRAM;
		}
		if($typeName ===  self::COMMUNICATION_VK_NAME)
		{
			return self::COMMUNICATION_VK;
		}
		if($typeName ===  self::COMMUNICATION_SKYPE_NAME)
		{
			return self::COMMUNICATION_SKYPE;
		}
		if($typeName ===  self::COMMUNICATION_BITRIX24_NAME)
		{
			return self::COMMUNICATION_BITRIX24;
		}
		if($typeName ===  self::COMMUNICATION_OPENLINE_NAME)
		{
			return self::COMMUNICATION_OPENLINE;
		}

		return self::UNDEFINED;
	}
	/**
	 * Get all type descriptions
	 * @return array
	 */
	public static function getAllDescriptions()
	{
		if(!self::$allDescriptions[LANGUAGE_ID])
		{
			Main\Localization\Loc::loadMessages(__FILE__);
			self::$allDescriptions[LANGUAGE_ID] = array(
				self::PERSON => GetMessage('CRM_DUP_INDEX_TYPE_PERSON'),
				self::ORGANIZATION => GetMessage('CRM_DUP_INDEX_TYPE_ORGANIZATION'),
				self::COMMUNICATION_PHONE => GetMessage('CRM_DUP_INDEX_TYPE_COMM_PHONE'),
				self::COMMUNICATION_EMAIL => GetMessage('CRM_DUP_INDEX_TYPE_COMM_EMAIL'),
				self::COMMUNICATION_FACEBOOK => GetMessage('CRM_DUP_INDEX_TYPE_COMM_FACEBOOK'),
				self::COMMUNICATION_TELEGRAM => GetMessage('CRM_DUP_INDEX_TYPE_COMM_TELEGRAM'),
				self::COMMUNICATION_VK => GetMessage('CRM_DUP_INDEX_TYPE_COMM_VK'),
				self::COMMUNICATION_SKYPE => GetMessage('CRM_DUP_INDEX_TYPE_COMM_SKYPE'),
				self::COMMUNICATION_BITRIX24 => GetMessage('CRM_DUP_INDEX_TYPE_COMM_BITRIX24'),
				self::COMMUNICATION_OPENLINE => GetMessage('CRM_DUP_INDEX_TYPE_COMM_OPENLINE')
			);
		}

		return self::$allDescriptions[LANGUAGE_ID];
	}
	/**
	 * Check if name is not multiple.
	 * @param int $typeID Type ID.
	 * @return bool
	 */
	public static function isSigle($typeID)
	{
		if(!is_numeric($typeID))
		{
			return false;
		}

		$typeID = (int)$typeID;
		return ($typeID === self::PERSON
			|| $typeID === self::ORGANIZATION
			|| $typeID === self::COMMUNICATION_PHONE
			|| $typeID === self::COMMUNICATION_EMAIL
			|| $typeID === self::COMMUNICATION_FACEBOOK
			|| $typeID === self::COMMUNICATION_TELEGRAM
			|| $typeID === self::COMMUNICATION_VK
			|| $typeID === self::COMMUNICATION_SKYPE
			|| $typeID === self::COMMUNICATION_BITRIX24
			|| $typeID === self::COMMUNICATION_OPENLINE
		);
	}
	/**
	 * Convert type list to multiple type ID.
	 * @param array $typeIDs Type ID list.
	 * @return int
	 */
	public static function joinType(array $typeIDs)
	{
		$result = 0;
		foreach($typeIDs as $typeID)
		{
			$result |= $typeID;
		}
		return $result;
	}
	/**
	 * Convert multiple type ID to type list.
	 * @param int $typeID Type ID.
	 * @return array
	 */
	public static function splitType($typeID)
	{
		$typeID = intval($typeID);

		$result = array();
		if(($typeID & self::PERSON) !== 0)
		{
			$result[] = self::PERSON;
		}
		if(($typeID & self::ORGANIZATION) !== 0)
		{
			$result[] = self::ORGANIZATION;
		}
		if(($typeID & self::COMMUNICATION_PHONE) !== 0)
		{
			$result[] = self::COMMUNICATION_PHONE;
		}
		if(($typeID & self::COMMUNICATION_EMAIL) !== 0)
		{
			$result[] = self::COMMUNICATION_EMAIL;
		}
		if(($typeID & self::COMMUNICATION_FACEBOOK) !== 0)
		{
			$result[] = self::COMMUNICATION_FACEBOOK;
		}
		if(($typeID & self::COMMUNICATION_TELEGRAM) !== 0)
		{
			$result[] = self::COMMUNICATION_TELEGRAM;
		}
		if(($typeID & self::COMMUNICATION_VK) !== 0)
		{
			$result[] = self::COMMUNICATION_VK;
		}
		if(($typeID & self::COMMUNICATION_SKYPE) !== 0)
		{
			$result[] = self::COMMUNICATION_SKYPE;
		}
		if(($typeID & self::COMMUNICATION_BITRIX24) !== 0)
		{
			$result[] = self::COMMUNICATION_BITRIX24;
		}
		if(($typeID & self::COMMUNICATION_OPENLINE) !== 0)
		{
			$result[] = self::COMMUNICATION_OPENLINE;
		}
		return $result;
	}
	/**
	 * Get supported types for specified entity type.
	 * @param int $entityTypeID Entity Type ID.
	 * @return array
	 * @deprecated since 16.2.0
	 * @see: DuplicateManager::getSupportedDedupeTypes
	 */
	public static function getSupportedTypes($entityTypeID)
	{
		return DuplicateManager::getSupportedDedupeTypes($entityTypeID);
	}
	/**
	 * Try to convert communication type into duplicate index type
	 * @param CommunicationType $commTypeID Source communication type.
	 * @return DuplicateIndexType
	 */
	public static function convertFromCommunicationType($commTypeID)
	{
		$commTypeID = (int)$commTypeID;
		if($commTypeID === CommunicationType::PHONE)
		{
			return self::COMMUNICATION_PHONE;
		}
		elseif($commTypeID === CommunicationType::EMAIL)
		{
			return self::COMMUNICATION_EMAIL;
		}
		elseif($commTypeID === CommunicationType::FACEBOOK)
		{
			return self::COMMUNICATION_FACEBOOK;
		}
		elseif($commTypeID === CommunicationType::TELEGRAM)
		{
			return self::COMMUNICATION_TELEGRAM;
		}
		elseif($commTypeID === CommunicationType::VK)
		{
			return self::COMMUNICATION_VK;
		}
		elseif($commTypeID === CommunicationType::SKYPE)
		{
			return self::COMMUNICATION_SKYPE;
		}
		elseif($commTypeID === CommunicationType::BITRIX24)
		{
			return self::COMMUNICATION_BITRIX24;
		}
		elseif($commTypeID === CommunicationType::OPENLINE)
		{
			return self::COMMUNICATION_OPENLINE;
		}
		return self::UNDEFINED;
	}
}