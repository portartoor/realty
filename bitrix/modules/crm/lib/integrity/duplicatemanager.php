<?php
namespace Bitrix\Crm\Integrity;
use Bitrix\Main;
use Bitrix\Crm\CommunicationType;

class DuplicateManager
{
	/**
	* @return DuplicateCriterion
	*/
	public static function createCriterion($typeID, array $matches)
	{
		if($typeID === DuplicateIndexType::PERSON)
		{
			return DuplicatePersonCriterion::createFromMatches($matches);
		}
		elseif($typeID === DuplicateIndexType::ORGANIZATION)
		{
			return DuplicateOrganizationCriterion::createFromMatches($matches);
		}
		elseif($typeID === DuplicateIndexType::COMMUNICATION_PHONE
			|| $typeID === DuplicateIndexType::COMMUNICATION_EMAIL
		)
		{
			if(!isset($matches['TYPE']))
			{
				$matches['TYPE'] = $typeID === DuplicateIndexType::COMMUNICATION_PHONE
					? CommunicationType::PHONE_NAME : CommunicationType::EMAIL_NAME;
			}

			return DuplicateCommunicationCriterion::createFromMatches($matches);
		}
		else
		{
			throw new Main\NotSupportedException("Criterion type(s): '".DuplicateIndexType::resolveName($typeID)."' is not supported in current context");
		}
	}
	/**
	* @return Duplicate
	*/
	public static function createDuplicate($typeID, array $matches, $entityTypeID, $rootEntityID, $userID, $enablePermissionCheck, $enableRanking, $limit = 0)
	{
		return self::createCriterion($typeID, $matches)->createDuplicate($entityTypeID, $rootEntityID, $userID, $enablePermissionCheck, $enableRanking, $limit);
	}
	/**
	* @return DuplicateIndexBuilder
	*/
	public static function createIndexBuilder($typeID, $entityTypeID, $userID, $enablePermissionCheck = false)
	{
		return new DuplicateIndexBuilder($typeID, new DedupeParams($entityTypeID, $userID, $enablePermissionCheck));
	}
	public static function removeIndexes(array $typeIDs, $entityTypeID, $userID, $enablePermissionCheck = false)
	{
		$params = new DedupeParams($entityTypeID, $userID, $enablePermissionCheck);
		foreach($typeIDs as $typeID)
		{
			$builder = new DuplicateIndexBuilder($typeID, $params);
			$builder->remove();
		}
	}
	public static function getMatchHash($typeID, array $matches)
	{
		if($typeID === DuplicateIndexType::PERSON)
		{
			return DuplicatePersonCriterion::prepareMatchHash($matches);
		}
		elseif($typeID === DuplicateIndexType::ORGANIZATION)
		{
			return DuplicateOrganizationCriterion::prepareMatchHash($matches);
		}
		elseif($typeID === DuplicateIndexType::COMMUNICATION_EMAIL
			|| $typeID === DuplicateIndexType::COMMUNICATION_PHONE)
		{
			return DuplicateCommunicationCriterion::prepareMatchHash($matches);
		}

		throw new Main\NotSupportedException("Criterion type(s): '".DuplicateIndexType::resolveName($typeID)."' is not supported in current context");
	}
	/**
	 * Get types supported by deduplication system for specified entity type.
	 * @param int $entityTypeID Entity Type ID.
	 * @return array
	 */
	public static function getSupportedDedupeTypes($entityTypeID)
	{
		$entityTypeID = (int)$entityTypeID;

		if($entityTypeID !== \CCrmOwnerType::Lead
			&& $entityTypeID !== \CCrmOwnerType::Contact
			&& $entityTypeID !== \CCrmOwnerType::Company)
		{
			return array();
		}

		$result = array();
		if($entityTypeID === \CCrmOwnerType::Lead || $entityTypeID === \CCrmOwnerType::Contact)
		{
			$result = array_merge($result, DuplicatePersonCriterion::getSupportedDedupeTypes());
		}
		if($entityTypeID === \CCrmOwnerType::Lead || $entityTypeID === \CCrmOwnerType::Company)
		{
			$result = array_merge($result, DuplicateOrganizationCriterion::getSupportedDedupeTypes());
		}
		return array_merge($result, DuplicateCommunicationCriterion::getSupportedDedupeTypes());
	}

	public static function prepareEntityListQueries($entityTypeID, array $comparisonData)
	{
		$queries = array();
		foreach($comparisonData as $data)
		{
			$type = $data['TYPE'];
			$matches = $data['MATCHES'];
			$item = self::createCriterion($type, $matches);
			$item->setStrictComparison(isset($data['ENABLE_STRICT_MODE']) && $data['ENABLE_STRICT_MODE'] == true);
			$query = $item->prepareSearchQuery($entityTypeID, array('ENTITY_ID'))->getQuery();
			$queries[] = "({$query})";
		}

		return $queries;
	}
	public static function prepareEntityListFilter(array &$filter, array $comparisonData, $entityTypeID, $entityAlias = '')
	{
		if($entityAlias === '')
		{
			$entityAlias = 'L';
		}

		$queries = array();
		foreach($comparisonData as $data)
		{
			$type = $data['TYPE'];
			$matches = $data['MATCHES'];
			$item = self::createCriterion($type, $matches);
			$item->setStrictComparison(isset($data['ENABLE_STRICT_MODE']) && $data['ENABLE_STRICT_MODE'] == true);
			$query = $item->prepareSearchQuery($entityTypeID, array('ENTITY_ID'))->getQuery();
			$queries[] = "({$query})";
		}

		if(!isset($filter['__JOINS']))
		{
			$filter['__JOINS'] = array();
		}

		$filter['__JOINS'][] = array(
			'TYPE' => 'INNER',
			'SQL' => 'INNER JOIN('.implode("\nUNION\n", $queries).') DP ON DP.ENTITY_ID = '.$entityAlias.'.ID'
		);
	}
}