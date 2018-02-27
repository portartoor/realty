<?php
namespace Bitrix\Crm;
use Bitrix\Main;

class RequisiteAddress extends EntityAddress
{
	private static $messagesLoaded = false;

	private static $fieldMaps = array();
	private static $invertedFieldMaps = array();

	private static $typeInfos = null;

	/**
	* @return int
	*/
	protected static function getEntityTypeID()
	{
		return \CCrmOwnerType::Requisite;
	}

	/**
	* @param int $typeID type of address
	* @return array
	*/
	protected static function getFieldMap($typeID)
	{
		if(!isset(self::$fieldMaps[$typeID]))
		{
			$requisite = new EntityRequisite();
			self::$fieldMaps[$typeID] = $requisite->getAddressFieldMap($typeID);
		}

		return self::$fieldMaps[$typeID];
	}

	/**
	* @return array
	*/
	protected static function getInvertedFieldMap($typeID)
	{
		if(!isset(self::$invertedFieldMaps[$typeID]))
		{
			self::$invertedFieldMaps[$typeID] = array_flip(self::getFieldMap($typeID));
		}
		return self::$invertedFieldMaps[$typeID];
	}

	/**
	 * @param $fieldName
	 * @param array|null $aliases
	 * @return int
	 */
	public static function resolveEntityFieldTypeID($fieldName, array $aliases = null)
	{
		return EntityAddress::Primary;
	}

	/**
	 * Remove entity addresses
	 * @param array $entityID Entity ID.
	 * @return void
	*/
	public static function deleteByEntityId($entityID)
	{
		EntityAddress::deleteByEntity(\CCrmOwnerType::Requisite, $entityID);
	}

	public static function getTypeInfos()
	{
		if(self::$typeInfos === null)
		{
			self::includeModuleFile();

			self::$typeInfos = parent::getTypeInfos();
			self::$typeInfos[self::Home] = array(
				'ID' => self::Home,
				'DESCRIPTION' => GetMessage('CRM_REQUISITE_ADDRESS_TYPE_HOME')
			);
			self::$typeInfos[self::Beneficiary] = array(
				'ID' => self::Beneficiary,
				'DESCRIPTION' => GetMessage('CRM_REQUISITE_ADDRESS_TYPE_BENEFICIARY')
			);
		}
		return self::$typeInfos;
	}

	public static function getClientTypeInfos()
	{
		self::includeModuleFile();
		return array_merge(
			parent::getClientTypeInfos(),
			array(
				array('id' => self::Home, 'name' => GetMessage('CRM_REQUISITE_ADDRESS_TYPE_HOME')),
				array('id' => self::Beneficiary, 'name' => GetMessage('CRM_REQUISITE_ADDRESS_TYPE_BENEFICIARY'))
			)
		);
	}

	public static function getTypeDescription($typeID)
	{
		if(!is_int($typeID))
		{
			$typeID = (int)$typeID;
		}

		if(!self::isDefined($typeID))
		{
			$typeID = self::Primary;
		}

		$typeInfos = self::getTypeInfos();
		return $typeInfos[$typeID]['DESCRIPTION'];
	}

	protected static function includeModuleFile()
	{
		if(self::$messagesLoaded)
		{
			return;
		}

		Main\Localization\Loc::loadMessages(__FILE__);
		self::$messagesLoaded = true;
	}
}