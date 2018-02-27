<?php

namespace Bitrix\Crm;

use Bitrix\Main;
use Bitrix\Main\Entity;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class EntityBankDetail
{
	const ERR_INVALID_ENTITY_TYPE   = 201;
	const ERR_INVALID_ENTITY_ID     = 202;
	const ERR_ON_DELETE             = 203;
	const ERR_NOTHING_TO_DELETE     = 204;

	private static $FIELD_INFOS = null;

	private static $rqFields = array(
		'RQ_BANK_NAME',
		'RQ_BANK_ADDR',
		'RQ_BANK_ROUTE_NUM',
		'RQ_BIK',
		'RQ_MFO',
		'RQ_ACC_NAME',
		'RQ_ACC_NUM',
		'RQ_IIK',
		'RQ_ACC_CURRENCY',
		'RQ_COR_ACC_NUM',
		'RQ_IBAN',
		'RQ_SWIFT',
		'RQ_BIC'
	);
	private static $rqFiltrableFields = null;
	private static $rqFieldMapByCountry = array(
		// RU
		1 => array(
			'RQ_BANK_NAME',
			'RQ_BIK',
			'RQ_ACC_NUM',
			'RQ_COR_ACC_NUM',
			'RQ_ACC_CURRENCY',
			'RQ_BANK_ADDR',
			'RQ_SWIFT'
		),
		// BY
		4 => array(
			'RQ_BANK_NAME',
			'RQ_BIK',
			'RQ_ACC_NUM',
			'RQ_COR_ACC_NUM',
			'RQ_BIC',
			'RQ_ACC_CURRENCY',
			'RQ_SWIFT',
			'RQ_BANK_ADDR'
		),
		// KZ
		6 => array(
			'RQ_BANK_NAME',
			'RQ_BIK',
			'RQ_IIK',
			'RQ_COR_ACC_NUM',
			'RQ_ACC_CURRENCY',
			'RQ_BANK_ADDR',
			'RQ_SWIFT'
		),
		// UA
		14 => array(
			'RQ_BANK_NAME',
			'RQ_MFO',
			'RQ_ACC_NUM'
		),
		// DE
		46 => array(
			'RQ_BANK_NAME',
			'RQ_BANK_ADDR',
			'RQ_BANK_ROUTE_NUM',
			'RQ_ACC_NAME',
			'RQ_ACC_NUM',
			'RQ_IBAN',
			'RQ_SWIFT',
			'RQ_BIC'
		),
		// US
		122 => array(
			'RQ_BANK_NAME',
			'RQ_BANK_ADDR',
			'RQ_BANK_ROUTE_NUM',
			'RQ_ACC_NAME',
			'RQ_ACC_NUM',
			'RQ_IBAN',
			'RQ_SWIFT',
			'RQ_BIC'
		)
	);
	private static $rqFieldCountryMap = null;
	private static $rqFieldTitleMap = null;

	public static function getFieldsInfo()
	{
		if(!self::$FIELD_INFOS)
		{
			self::$FIELD_INFOS = array(
				'ID' => array(
						'TYPE' => 'integer',
						'ATTRIBUTES' => array(\CCrmFieldInfoAttr::ReadOnly)
				),
				'ENTITY_TYPE_ID' => array(
						'TYPE' => 'integer',
						'ATTRIBUTES' => array(
								\CCrmFieldInfoAttr::Required,
								\CCrmFieldInfoAttr::Immutable,
								\CCrmFieldInfoAttr::Hidden
						)
				),
				'ENTITY_ID' => array(
						'TYPE' => 'integer',
						'ATTRIBUTES' => array(
								\CCrmFieldInfoAttr::Required,
								\CCrmFieldInfoAttr::Immutable
						)
				),
				'COUNTRY_ID' => array('TYPE' => 'integer'),
				'DATE_CREATE' => array(
						'TYPE' => 'datetime',
						'ATTRIBUTES' => array(\CCrmFieldInfoAttr::ReadOnly)
				),
				'DATE_MODIFY' => array(
						'TYPE' => 'datetime',
						'ATTRIBUTES' => array(\CCrmFieldInfoAttr::ReadOnly)
				),
				'CREATED_BY_ID' => array(
						'TYPE' => 'user',
						'ATTRIBUTES' => array(\CCrmFieldInfoAttr::ReadOnly)
				),
				'MODIFY_BY_ID' => array(
						'TYPE' => 'user',
						'ATTRIBUTES' => array(\CCrmFieldInfoAttr::ReadOnly)
				),
				'NAME' => array('TYPE' => 'string'),
				'CODE' => array('TYPE' => 'string'),
				'XML_ID' => array('TYPE' => 'string'),
				'ACTIVE' => array('TYPE' => 'char'),
				'SORT' => array('TYPE' => 'integer'),
				'RQ_BANK_NAME' => array('TYPE' => 'string'),
				'RQ_BANK_ADDR' => array('TYPE' => 'string'),
				'RQ_BANK_ROUTE_NUM' => array('TYPE' => 'string'),
				'RQ_BIK' => array('TYPE' => 'string'),
				'RQ_MFO' => array('TYPE' => 'string'),
				'RQ_ACC_NAME' => array('TYPE' => 'string'),
				'RQ_ACC_NUM' => array('TYPE' => 'string'),
				'RQ_IIK' => array('TYPE' => 'string'),
				'RQ_ACC_CURRENCY' => array('TYPE' => 'string'),
				'RQ_COR_ACC_NUM' => array('TYPE' => 'string'),
				'RQ_IBAN' => array('TYPE' => 'string'),
				'RQ_SWIFT' => array('TYPE' => 'string'),
				'RQ_BIC' => array('TYPE' => 'string'),
				'COMMENTS' => array('TYPE' => 'string'),
				'ORIGINATOR_ID' => array('TYPE' => 'string'),
			);
		}
		return self::$FIELD_INFOS;
	}

	public function getList($params)
	{
		return BankDetailTable::getList($params);
	}

	public function getCountByFilter($filter = array())
	{
		return BankDetailTable::getCountByFilter($filter);
	}

	public function getById($id)
	{
		$result = BankDetailTable::getByPrimary($id);
		$row = $result->fetch();

		return (is_array($row)? $row : null);
	}

	public function checkBeforeAdd($fields, $options = array())
	{
		unset($fields['ID'], $fields['DATE_MODIFY'], $fields['MODIFY_BY_ID']);
		$fields['DATE_CREATE'] = new \Bitrix\Main\Type\DateTime();
		$fields['CREATED_BY_ID'] = \CCrmSecurityHelper::GetCurrentUserID();

		global $USER_FIELD_MANAGER, $APPLICATION;

		$result = new Entity\AddResult();
		$entity = BankDetailTable::getEntity();

		try
		{
			// set fields with default values
			foreach ($entity->getFields() as $field)
			{
				if ($field instanceof Entity\ScalarField && !array_key_exists($field->getName(), $fields))
				{
					$defaultValue = $field->getDefaultValue();

					if ($defaultValue !== null)
					{
						$fields[$field->getName()] = $field->getDefaultValue();
					}
				}
			}

			// uf values
			$userFields = array();

			// separate userfields
			if ($entity->getUfId())
			{
				// collect uf data
				$userfields = $USER_FIELD_MANAGER->GetUserFields($entity->getUfId());

				foreach ($userfields as $userfield)
				{
					if (array_key_exists($userfield['FIELD_NAME'], $fields))
					{
						// copy value
						$userFields[$userfield['FIELD_NAME']] = $fields[$userfield['FIELD_NAME']];

						// remove original
						unset($fields[$userfield['FIELD_NAME']]);
					}
				}
			}

			// check data
			BankDetailTable::checkFields($result, null, $fields);

			// check uf data
			if (!empty($userFields))
			{
				if (!$USER_FIELD_MANAGER->CheckFields($entity->getUfId(), false, $userFields))
				{
					if (is_object($APPLICATION) && $APPLICATION->getException())
					{
						$e = $APPLICATION->getException();
						$result->addError(new Entity\EntityError($e->getString()));
						$APPLICATION->resetException();
					}
					else
					{
						$result->addError(new Entity\EntityError("Unknown error while checking userfields"));
					}
				}
			}

			// check if there is still some data
			if (!count($fields + $userFields))
			{
				$result->addError(new Entity\EntityError("There is no data to add."));
			}

			// return if any error
			if (!$result->isSuccess(true))
			{
				return $result;
			}
		}
		catch (\Exception $e)
		{
			// check result to avoid warning
			$result->isSuccess();

			throw $e;
		}

		return $result;
	}
	
	public function add($fields, $options = array())
	{
		unset($fields['ID'], $fields['DATE_MODIFY'], $fields['MODIFY_BY_ID']);
		$fields['DATE_CREATE'] = new \Bitrix\Main\Type\DateTime();
		$fields['CREATED_BY_ID'] = \CCrmSecurityHelper::GetCurrentUserID();

		$result = BankDetailTable::add($fields);
		
		return $result;
	}

	public function checkBeforeUpdate($id, $fields)
	{
		unset($fields['DATE_CREATE'], $fields['CREATED_BY_ID']);
		$fields['DATE_MODIFY'] = new \Bitrix\Main\Type\DateTime();
		$fields['MODIFY_BY_ID'] = \CCrmSecurityHelper::GetCurrentUserID();

		global $USER_FIELD_MANAGER, $APPLICATION;

		$result = new Entity\UpdateResult();
		$entity = BankDetailTable::getEntity();
		$entity_primary = $entity->getPrimaryArray();

		// normalize primary
		if ($id === null)
		{
			$id = array();

			// extract primary from data array
			foreach ($entity_primary as $key)
			{
				/** @var Entity\ScalarField $field  */
				$field = $entity->getField($key);
				if ($field->isAutocomplete())
				{
					continue;
				}

				if (!isset($fields[$key]))
				{
					throw new Main\ArgumentException(sprintf(
						'Primary `%s` was not found when trying to query %s row.', $key, $entity->getName()
					));
				}

				$id[$key] = $fields[$key];
			}
		}
		elseif (is_scalar($id))
		{
			if (count($entity_primary) > 1)
			{
				throw new Main\ArgumentException(sprintf(
					'Require multi primary {`%s`}, but one scalar value "%s" found when trying to query %s row.',
					join('`, `', $entity_primary), $id, $entity->getName()
				));
			}

			$id = array($entity_primary[0] => $id);
		}
		
		// validate primary
		if (is_array($id))
		{
			if(empty($id))
			{
				throw new Main\ArgumentException(sprintf(
					'Empty primary found when trying to query %s row.', $entity->getName()
				));
			}

			foreach (array_keys($id) as $key)
			{
				if (!in_array($key, $entity_primary, true))
				{
					throw new Main\ArgumentException(sprintf(
						'Unknown primary `%s` found when trying to query %s row.',
						$key, $entity->getName()
					));
				}
			}
		}
		else
		{
			throw new Main\ArgumentException(sprintf(
				'Unknown type of primary "%s" found when trying to query %s row.', gettype($id), $entity->getName()
			));
		}
		foreach ($id as $key => $value)
		{
			if (!is_scalar($value) && !($value instanceof Main\Type\Date))
			{
				throw new Main\ArgumentException(sprintf(
					'Unknown value type "%s" for primary "%s" found when trying to query %s row.',
					gettype($value), $key, $entity->getName()
				));
			}
		}

		try
		{
			// uf values
			$ufdata = array();

			// separate userfields
			if ($entity->getUfId())
			{
				// collect uf data
				$userfields = $USER_FIELD_MANAGER->GetUserFields($entity->getUfId());

				foreach ($userfields as $userfield)
				{
					if (array_key_exists($userfield['FIELD_NAME'], $fields))
					{
						// copy value
						$ufdata[$userfield['FIELD_NAME']] = $fields[$userfield['FIELD_NAME']];

						// remove original
						unset($fields[$userfield['FIELD_NAME']]);
					}
				}
			}

			// check data
			BankDetailTable::checkFields($result, $id, $fields);

			// check uf data
			if (!empty($ufdata))
			{
				if (!$USER_FIELD_MANAGER->CheckFields($entity->getUfId(), end($id), $ufdata))
				{
					if (is_object($APPLICATION) && $APPLICATION->getException())
					{
						$e = $APPLICATION->getException();
						$result->addError(new Entity\EntityError($e->getString()));
						$APPLICATION->resetException();
					}
					else
					{
						$result->addError(new Entity\EntityError("Unknown error while checking userfields"));
					}
				}
			}

			// check if there is still some data
			if (!count($fields + $ufdata))
			{
				$result->addError(new Entity\EntityError("There is no data to update."));
			}

			// return if any error
			if (!$result->isSuccess(true))
			{
				return $result;
			}
		}
		catch (\Exception $e)
		{
			// check result to avoid warning
			$result->isSuccess();

			throw $e;
		}

		return $result;
	}

	public function update($id, $fields, $options = array())
	{
		unset($fields['DATE_CREATE'], $fields['CREATED_BY_ID']);
		$fields['DATE_MODIFY'] = new \Bitrix\Main\Type\DateTime();
		$fields['MODIFY_BY_ID'] = \CCrmSecurityHelper::GetCurrentUserID();
		
		$result = BankDetailTable::update($id, $fields);

		return $result;
	}

	public function delete($id, $options = array())
	{
		$result = BankDetailTable::delete($id);

		return $result;
	}

	public function deleteByEntity($entityTypeId, $entityId)
	{
		$result = new \Bitrix\Main\Result();

		$entityTypeId = (int)$entityTypeId;
		$entityId = (int)$entityId;

		if (!self::checkEntityType($entityTypeId))
		{
			$result->addError(
				new Main\Error(
					GetMessage('CRM_BANKDETAIL_ERR_INVALID_ENTITY_TYPE'),
					self::ERR_INVALID_ENTITY_TYPE
				)
			);
			return $result;
		}

		if ($entityId <= 0)
		{
			$result->addError(
				new Main\Error(
					GetMessage('CRM_BANKDETAIL_ERR_INVALID_ENTITY_ID'),
					self::ERR_INVALID_ENTITY_ID
				)
			);
			return $result;
		}

		$res = $this->getList(
			array(
				'filter' => array(
					'=ENTITY_TYPE_ID' => $entityTypeId,
					'=ENTITY_ID' => $entityId
				),
				'select' => array('ID')
			)
		);
		$cnt = 0;
		while ($row = $res->fetch())
		{
			$cnt++;
			$delResult = $this->delete($row['ID']);
			if (!$delResult->isSuccess())
			{
				$result->addError(
					new Main\Error(
						GetMessage('CRM_BANKDETAIL_ERR_ON_DELETE', array('#ID#', $row['ID'])),
						self::ERR_ON_DELETE
					)
				);
			}
		}

		if ($cnt === 0)
		{
			$result->addError(
				new Main\Error(
					GetMessage('CRM_BANKDETAIL_ERR_NOTHING_TO_DELETE'),
					self::ERR_NOTHING_TO_DELETE
				)
			);
		}

		return $result;
	}

	public function getRqFields()
	{
		return self::$rqFields;
	}

	public function getRqFiltrableFields()
	{
		if (self::$rqFiltrableFields === null)
		{
			self::$rqFiltrableFields = array(
				'RQ_BANK_NAME',
				'RQ_BANK_ROUTE_NUM',
				'RQ_BIK',
				'RQ_MFO',
				'RQ_ACC_NAME',
				'RQ_ACC_NUM',
				'RQ_IIK',
				'RQ_COR_ACC_NUM',
				'RQ_IBAN',
				'RQ_SWIFT',
				'RQ_BIC'
			);
		}

		return self::$rqFiltrableFields;
	}

	public static function getAllowedRqFieldCountries()
	{
		return array_keys(self::$rqFieldMapByCountry);
	}

	public function getFieldsTitles($countryId = 0)
	{
		$result = array();

		$countryId = (int)$countryId;
		if (!in_array($countryId, self::getAllowedRqFieldCountries()))
		{
			$countryId = EntityPreset::getCurrentCountryId();
			if ($countryId <= 0)
				$countryId = 122;
		}

		$rqFields = array();
		foreach ($this->getRqFields() as $rqFieldName)
			$rqFields[$rqFieldName] = true;

		$rqFieldTitleMap = $this->getRqFieldTitleMap();

		Loc::loadMessages(Main\Application::getDocumentRoot().'/bitrix/modules/crm/lib/bankdetail.php');

		foreach (BankDetailTable::getMap() as $fieldName => $fieldInfo)
		{
			if (isset($rqFields[$fieldName]))
			{
				$title = '';
				if (isset($rqFieldTitleMap[$fieldName][$countryId]))
				{
					if (empty($rqFieldTitleMap[$fieldName][$countryId]))
						$title = $fieldName;
					else
						$title = $rqFieldTitleMap[$fieldName][$countryId];

				}
				$result[$fieldName] = $title;
			}
			else
			{
				$fieldTitle = (isset($fieldInfo['title']) && !empty($fieldInfo['title'])) ? $fieldInfo['title'] : GetMessage('CRM_BANK_DETAIL_ENTITY_'.$fieldName.'_FIELD');
				$result[$fieldName] = is_string($fieldTitle) ? $fieldTitle : '';
			}
		}

		return $result;
	}

	public function getFormFieldsTypes()
	{
		return array(
			'RQ_BANK_ADDR' => 'textarea',
			'COMMENTS' => 'textarea'
		);
	}

	public function getFormFieldsInfo($countryId = 0)
	{
		$result = array();

		$formTypes = $this->getFormFieldsTypes();
		$rqFields = array();
		foreach ($this->getRqFields() as $rqFieldName)
			$rqFields[$rqFieldName] = true;
		$fieldTitles = $this->getFieldsTitles($countryId);
		foreach (BankDetailTable::getMap() as $fieldName => $fieldInfo)
		{
			if (isset($fieldInfo['reference']))
				continue;

			$fieldTitle = (isset($fieldTitles[$fieldName])) ? $fieldTitles[$fieldName] : '';
			$result[$fieldName] = array(
				'title' => is_string($fieldTitle) ? $fieldTitle : '',
				'type' => $fieldInfo['data_type'],
				'required' => (isset($fieldInfo['required']) && $fieldInfo['required']),
				'formType' => isset($formTypes[$fieldName]) ? $formTypes[$fieldName] : 'text',
				'isRQ' => isset($rqFields[$fieldName]),
				'isUF' => false
			);
		}

		return $result;
	}

	public function getFormFieldsInfoByCountry($countryId)
	{
		$result = array();

		$countryId = (int)$countryId;
		if ($countryId <= 0 || !isset(self::$rqFieldMapByCountry[$countryId]))
			$countryId = 122;    // US by default

		$fieldsInfo = $this->getFormFieldsInfo($countryId);
		$fieldsByCountry = array();
		if (is_array(self::$rqFieldMapByCountry[$countryId]))
			$fieldsByCountry = self::$rqFieldMapByCountry[$countryId];

		$result['NAME'] = $fieldsInfo['NAME'];
		foreach ($fieldsByCountry as $fieldName)
		{
			if (isset($fieldsInfo[$fieldName]))
				$result[$fieldName] = $fieldsInfo[$fieldName];
		}
		$result['COMMENTS'] = $fieldsInfo['COMMENTS'];

		return $result;
	}

	public function getRqFieldByCountry()
	{
		return self::$rqFieldMapByCountry;
	}

	public function getRqFieldsCountryMap()
	{
		if (self::$rqFieldCountryMap === null)
		{
			$map = array();
			foreach (self::$rqFieldMapByCountry as $countryId => $fieldList)
			{
				foreach ($fieldList as $fieldName)
				{
					if (!isset($map[$fieldName]) || !is_array($map[$fieldName]))
						$map[$fieldName] = array();
					if (!in_array($countryId, $map[$fieldName], true))
						$map[$fieldName][] = $countryId;
				}
			}
			self::$rqFieldCountryMap = $map;
		}

		return self::$rqFieldCountryMap;
	}

	public function getRqFieldTitleMap()
	{
		if (self::$rqFieldTitleMap === null)
		{
			$titleMap = array();
			$countryIds = array();
			foreach ($this->getRqFieldsCountryMap() as $fieldName => $fieldCountryIds)
			{
				if (is_array($fieldCountryIds))
				{
					foreach ($fieldCountryIds as $countryId)
					{
						$titleMap[$fieldName][$countryId] = '';
						if (!isset($countryIds[$countryId]))
							$countryIds[$countryId] = true;
					}
				}
			}
			foreach (array_keys($countryIds) as $countryId)
			{
				$langId = '';
				switch ($countryId)
				{
					case 1:                // ru
						$langId = 'ru';
						break;
					case 4:                // by
						$langId = 'by';
						break;
					case 6:                // kz
						$langId = 'kz';
						break;
					case 14:               // ua
						$langId = 'ua';
						break;
					case 46:               // de
						$langId = 'de';
						break;
					case 122:              // us
						$langId = 'en';
						break;
				}

				if (!empty($langId))
				{
					$messages = Loc::loadLanguageFile(
						Main\Application::getDocumentRoot().'/bitrix/modules/crm/lib/bankdetail.php',
						$langId
					);
					foreach ($titleMap as $fieldName => &$titlesByCountry)
					{
						if (isset($titlesByCountry[$countryId]))
						{
							$messageId = 'CRM_BANK_DETAIL_ENTITY_'.$fieldName.'_FIELD';
							$altMessageId = 'CRM_BANK_DETAIL_ENTITY_'.$fieldName.'_'.strtoupper($langId).'_FIELD';
							$title = GetMessage($altMessageId);

							if (isset($messages[$altMessageId]))
							{
								$titlesByCountry[$countryId] = $messages[$altMessageId];
							}
							else if (is_string($title) && !empty($title))
							{
								$titlesByCountry[$countryId] = $title;
							}
							else if (isset($messages[$messageId]))
							{
								$titlesByCountry[$countryId] = $messages[$messageId];
							}
						}
					}
					unset($titlesByCountry);
				}
			}
			self::$rqFieldTitleMap = $titleMap;
		}

		return self::$rqFieldTitleMap;
	}

	public static function checkEntityType($entityTypeId)
	{
		$entityTypeId = intval($entityTypeId);

		if ($entityTypeId !== \CCrmOwnerType::Requisite)
			return false;

		return true;
	}

	public function validateEntityExists($entityTypeId, $entityId)
	{
		$entityTypeId = intval($entityTypeId);
		$entityId = intval($entityId);

		if ($entityTypeId === \CCrmOwnerType::Requisite)
		{
			$requisite = new EntityRequisite();
			if (!$requisite->exists($entityId))
				return false;
		}
		else
		{
			return false;
		}

		return true;
	}

	public function validateEntityReadPermission($entityTypeId, $entityId)
	{
		$entityTypeId = intval($entityTypeId);
		$entityId = intval($entityId);

		if ($entityId <= 0)
			return false;

		if ($entityTypeId === \CCrmOwnerType::Requisite)
		{
			$requisite = new EntityRequisite();
			if (!$requisite->checkReadPermission($entityId))
				return false;
		}
		else
		{
			return false;
		}

		return true;
	}

	public function validateEntityUpdatePermission($entityTypeId, $entityId)
	{
		$entityTypeId = intval($entityTypeId);
		$entityId = intval($entityId);

		if ($entityId <= 0)
			return false;

		if ($entityTypeId === \CCrmOwnerType::Requisite)
		{
			$requisite = new EntityRequisite();
			if (!$requisite->checkUpdatePermission($entityId))
				return false;
		}
		else
		{
			return false;
		}

		return true;
	}

	public function prepareViewData($fields, $fieldsInView = array())
	{
		if (!is_array($fieldsInView))
			$fieldsInView = array();

		$result = array(
			'title' => '',
			'fields' => array()
		);

		$fieldsInfo = $this->getFormFieldsInfo();

		foreach ($fields as $fieldName => $fieldValue)
		{
			if ($fieldValue instanceof Main\Type\DateTime)
				$fieldValue = $fieldValue->toString();

			if ($fieldName === 'NAME')
			{
				$result['title'] = $fieldValue;
			}
			else
			{
				if (isset($fieldsInfo[$fieldName])
					&& (empty($fieldsInView) || in_array($fieldName, $fieldsInView, true)))
				{
					$fieldInfo = $fieldsInfo[$fieldName];
					$textValue = strval($fieldValue);

					$result['fields'][] = array(
						'name' => $fieldName,
						'title' => $fieldInfo['title'],
						'type' => $fieldInfo['type'],
						'formType' => $fieldInfo['formType'],
						'textValue' => $textValue
					);
				}
			}
		}

		return $result;
	}

	public static function checkCreatePermissionOwnerEntity($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if ($entityTypeID === \CCrmOwnerType::Requisite)
		{
			$r = EntityRequisite::getOwnerEntityById($entityID);

			return EntityRequisite::checkCreatePermissionOwnerEntity($r['ENTITY_TYPE_ID']);
		}

		return false;
	}

	public static function checkUpdatePermissionOwnerEntity($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if ($entityTypeID === \CCrmOwnerType::Requisite)
		{
			$r = EntityRequisite::getOwnerEntityById($entityID);

			return EntityRequisite::checkUpdatePermissionOwnerEntity($r['ENTITY_TYPE_ID'], $r['ENTITY_ID']);
		}

		return false;
	}

	public static function checkDeletePermissionOwnerEntity($entityTypeID, $entityID)
	{
		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if ($entityTypeID === \CCrmOwnerType::Requisite)
		{
			$r = EntityRequisite::getOwnerEntityById($entityID);

			return EntityRequisite::checkDeletePermissionOwnerEntity($r['ENTITY_TYPE_ID'], $r['ENTITY_ID']);
		}

		return false;
	}

	public static function checkReadPermissionOwnerEntity($entityTypeID = 0, $entityID = 0)
	{
		if(intval($entityTypeID)<=0 && intval($entityID) <= 0)
		{
			return EntityRequisite::checkReadPermissionOwnerEntity();
		}

		if(!is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if ($entityTypeID === \CCrmOwnerType::Requisite)
		{
			$r = EntityRequisite::getOwnerEntityById($entityID);

			return EntityRequisite::checkReadPermissionOwnerEntity($r['ENTITY_TYPE_ID'], $r['ENTITY_ID']);
		}

		return false;
	}

	public static function getOwnerEntityById($id)
	{
		$result = array();

		if ($id <= 0)
			return array();

		$row = BankDetailTable::getList(array(
				'filter' => array('=ID' => $id),
				'select' => array('ID', 'ENTITY_TYPE_ID', 'ENTITY_ID'),
				'limit' => 1
		));

		$r = $row->fetch();

		$result['ENTITY_TYPE_ID'] = isset($r['ENTITY_TYPE_ID']) ? (int)$r['ENTITY_TYPE_ID'] : 0;
		$result['ENTITY_ID'] = isset($r['ENTITY_ID']) ? (int)$r['ENTITY_ID'] : 0;

		return $result;
	}

	/**
	 * Parse form data from specified source
	 * @param array $formData Data source
	 * @return array
	 */
	public static function parseFormData(array $formData)
	{
		$result = array();

		if (is_array($formData) && !empty($formData))
		{
			foreach ($formData as $pseudoId => $formFields)
			{
				$fields = array();
				$fieldNames = array_merge(
					array('ENTITY_TYPE_ID', 'ENTITY_ID', 'COUNTRY_ID', 'NAME'),
					self::$rqFields,
					array('COMMENTS')
				);
				foreach ($fieldNames as $fieldName)
				{
					if (isset($formData[$fieldName]))
					{
						if ($fieldName === 'ENTITY_TYPE_ID'
							|| $fieldName === 'ENTITY_ID'
							|| $fieldName === 'COUNTRY_ID')
						{
							$fields[$fieldName] = (int)$formData[$fieldName];
						}
						else
						{
							$fields[$fieldName] = trim(strval($formData[$fieldName]));
						}
					}
				}
				foreach ($fields as $fieldName => $fieldValue)
					$result[$fieldName] = $fieldValue;
			}
		}

		return $result;
	}
}
