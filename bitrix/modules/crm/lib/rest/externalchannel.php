<?php

namespace Bitrix\Crm\Rest;
use Bitrix\Crm;
use Bitrix\Crm\Activity\Provider;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Integration\Channel\ExternalTracker;
use Bitrix\Crm\Integration\Channel\ChannelType;

Loc::loadMessages(__FILE__);

class CCrmExternalChannelImportActivity extends \CCrmExternalChannelRestProxy
{
	private static $ENTITY = null;
	protected $class = null;
	protected $ownerId = -1;
	public $import = null;
	protected $activityType = CCrmExternalChannelActivityType::Undefined;

    public static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new \CCrmActivityRestProxy();
		}

		return self::$ENTITY;
	}

	public function setOwnerEntity($class)
	{
		$this->class = $class;
	}

	protected function getOwnerEntity()
	{
		return $this->class;
	}

    public function setOwnerEntityId($id)
	{
		$this->ownerId = (int)$id;
	}

	protected function getOwnerEntityId()
	{
		return $this->ownerId;
	}

    public function setTypeActivity($type)
	{
		$this->activityType = $type;
	}

	protected function getTypeActivity()
	{
		return $this->activityType;
	}

	protected function checkFields(&$fields, &$errors)
	{
		if(!is_set($fields, 'SUBJECT') || !is_string($fields['SUBJECT']))
		{
			$errors[] = "SUBJECT is not defined or is invalid";
		}
		elseif(!is_set($fields, 'DESCRIPTION') || !is_string($fields['DESCRIPTION']))
		{
			$errors[] = "DESCRIPTION is not defined or is invalid";
		}
		elseif(!is_set($fields, 'RESULT_VALUE') || !is_numeric($fields['RESULT_VALUE']))
		{
			$errors[] = "RESULT_VALUE is not defined or is invalid";
		}
		elseif(!is_set($fields, 'RESULT_SUM') || $fields['RESULT_SUM']=='')
		{
			$errors[] = "RESULT_SUM is not defined";
		}
		elseif(!is_set($fields, 'RESULT_CURRENCY_ID') || $fields['RESULT_CURRENCY_ID']=='' || !\CCrmCurrency::IsExists($fields['RESULT_CURRENCY_ID']))
		{
			$errors[] = "RESULT_CURRENCY_ID not defined or is invalid";
		}
		elseif(!is_set($fields, 'START_TIME') || !is_string(\CRestUtil::unConvertDateTime($fields['START_TIME'])))
		{
			$errors[] = "START_TIME is not defined or is invalid";
		}
		elseif(!is_set($fields, 'ORIGIN_ID') || $fields['ORIGIN_ID']=='')
		{
			$errors[] = "ORIGIN_ID is not defined";
		}
	}

    public function fillEmptyFields(&$fields, $params=array())
	{
		/** @var \CCrmCompanyRestProxy|\CCrmContactRestProxy $ownerEntity */
		$ownerEntity = $this->getOwnerEntity();

		if($ownerEntity->getOwnerTypeID() ===  \CCrmOwnerType::Company)
		{
			$title = is_set($params[CCrmExternalChannelImport::FIELDS], 'TITLE')? $params[CCrmExternalChannelImport::FIELDS]['TITLE']:'';

			$fields['SUBJECT'] =  Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_IMPORT_COMPANY_ACTIVITY_SUBJECT')." ".$title;
		}
		elseif($ownerEntity->getOwnerTypeID() ===  \CCrmOwnerType::Contact)
		{
			$name[] = is_set($params[CCrmExternalChannelImport::FIELDS], 'LAST_NAME')? $params[CCrmExternalChannelImport::FIELDS]['LAST_NAME']:'';
			$name[] = is_set($params[CCrmExternalChannelImport::FIELDS], 'NAME')? $params[CCrmExternalChannelImport::FIELDS]['NAME']:'';
			$name[] = is_set($params[CCrmExternalChannelImport::FIELDS], 'SECOND_NAME')? $params[CCrmExternalChannelImport::FIELDS]['SECOND_NAME']:'';

			$fields['SUBJECT'] = Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_IMPORT_CONTACT_ACTIVITY_SUBJECT')." ".implode(' ', $name);
		}

		$fields['START_TIME'] = ConvertTimeStamp((time() + \CTimeZone::GetOffset()), 'FULL', SITE_ID);
	}

    public function fillFields(&$fields, $params=array())
	{
		/** @var \CCrmCompanyRestProxy|\CCrmContactRestProxy $ownerEntity */
		$ownerEntity = $this->getOwnerEntity();

		/** @var CCrmExternalChannelImport $import */
		$import = $this->import;

		/** @var CCrmExternalChannelConnector $connector */
		$connector = $import->getConnector();

		$curUserId = \CCrmSecurityHelper::GetCurrentUserID();

		$fields['DIRECTION'] = \CCrmActivityDirection::Incoming;
		$fields['COMPLETED'] = 'Y';
		$fields['START_TIME'] = \CRestUtil::unConvertDateTime($fields['START_TIME']);
		$fields['RESPONSIBLE_ID'] = $curUserId;
		$fields['AUTHOR_ID'] = $curUserId;
		$fields['PROVIDER_ID'] = Provider\ExternalChannel::PROVIDER_ID;
		$fields['PROVIDER_TYPE_ID'] = $this->getTypeActivity();
		$fields['PROVIDER_GROUP_ID'] = $connector->getTypeId();
		$fields['OWNER_ID'] = $this->getOwnerEntityId();
		$fields['OWNER_TYPE_ID'] = $ownerEntity->getOwnerTypeID();
		$fields['PROVIDER_PARAMS'] = $import->getRawData();
		$fields['ORIGINATOR_ID'] = $connector->getOriginatorId();
	}

	protected function innerAdd($activity, &$resultList)
	{
		$error = array();
		$resultList = array(
				'id'=> -1,
				'process' => array(
						'add' => false,
						'error' => array()
				)
		);

		if(($fields = $activity[self::FIELDS]) && count($fields)>0)
		{
			$errors = array();
			$this->checkFields($fields, $errors);
			if(count($errors)>0)
				$error[] = implode('; ', $errors);

			if(count($error)<=0)
			{
				$errors = array();

				$this->getEntity()->internalizeFields($fields, $this->getEntity()->getFieldsInfo());

				$this->fillFields($fields, $activity);

				$id = $this->getEntity()->innerAdd($fields, $errors);
				if($this->isValidID($id))
				{
					$resultList['id'] = $id;
					$resultList['process']['add'] = true;
				}

				if(count($errors)>0)
					$error[] = implode('; ', $errors);
			}
		}
		else
			$error[] = "Activity fields is not defined.";

		if(count($error)>0)
			$resultList['process']['error'] = $error;
	}

    public function import($activity, &$resultList)
	{
		$error = array();
		$resultList = array(
				'id'=> -1,
				'process' => array(
						'add' => false,
						'upd' => false,
						'error' => array()
				)
		);

		/** @var CCrmExternalChannelImport $import */
		$import = $this->import;

		/** @var CCrmExternalChannelConnector $connector */
		$connector = $import->getConnector();

		if(($fields = $activity[CCrmExternalChannelImport::FIELDS]) && count($fields)>0)
		{
			$errors = array();

			$this->checkFields($fields, $errors);

			if(count($errors)>0)
				$error[] = implode('; ', $errors);

			if(count($error)<=0)
			{
				$errors = array();

				$result = $this->getEntity()->innerGetList(
						array(),
						array(
								'ORIGIN_ID'=>$fields['ORIGIN_ID'],
								'ORIGINATOR_ID' => $connector->getOriginatorId()
						),
						array('*'),
						false,
						$errors
				);
				if(!$result)
				{
					if(count($errors)>0)
						$error[] = implode('; ', $errors);
				}
				else
				{
					$errors = array();

					$this->getEntity()->internalizeFields($fields, $this->getEntity()->getFieldsInfo());

					$this->fillFields($fields, $activity);

					if($r = $result->Fetch())
					{
						$resultUpdate = $this->getEntity()->innerUpdate($r['ID'], $fields, $errors);
						if($resultUpdate !== false)
						{
							$resultList['id'] = (int)$r['ID'];
							$resultList['process']['upd'] = true;
						}
					}
					else
					{
						$id = $this->getEntity()->innerAdd($fields, $errors);
						if($this->isValidID($id))
						{
							$this->registerActivityInChannel($id, $connector);

							$resultList['id'] = $id;
							$resultList['process']['add'] = true;
						}
					}

					if(count($errors)>0)
						$error[] = implode('; ', $errors);
				}
			}
		}
		else
			$error[] = "Activity fields is not defined.";

		if(count($error)>0)
			$resultList['process']['error'] = $error;
	}

	/**
	 * @param $id
	 * @param CCrmExternalChannelConnector $connector
     */
	public function registerActivityInChannel($id, CCrmExternalChannelConnector $connector)
	{
		$instanceExternalTracker = '';
		switch($connector->getTypeId())
		{
			case CCrmExternalChannelType::CustomName:
				$instanceExternalTracker = ExternalTracker::getInstance(ChannelType::EXTERNAL_CUSTOM);
				break;
			case CCrmExternalChannelType::BitrixName:
				$instanceExternalTracker = ExternalTracker::getInstance(ChannelType::EXTERNAL_BITRIX);
				break;
			case CCrmExternalChannelType::OneCName:
				$instanceExternalTracker = ExternalTracker::getInstance(ChannelType::EXTERNAL_ONE_C);
				break;
			case CCrmExternalChannelType::WordpressName:
				$instanceExternalTracker = ExternalTracker::getInstance(ChannelType::EXTERNAL_WORDPRESS);
				break;
			case CCrmExternalChannelType::DrupalName:
				$instanceExternalTracker = ExternalTracker::getInstance(ChannelType::EXTERNAL_DRUPAL);
				break;
			case CCrmExternalChannelType::JoomlaName:
				$instanceExternalTracker = ExternalTracker::getInstance(ChannelType::EXTERNAL_JOOMLA);
				break;
			case CCrmExternalChannelType::MagentoName:
				$instanceExternalTracker = ExternalTracker::getInstance(ChannelType::EXTERNAL_MAGENTO);
				break;
		}

		if($instanceExternalTracker instanceof Crm\Integration\Channel\ExternalTracker)
		{
			$typeId = $this->getTypeActivity();
			$originatorId = $connector->getOriginatorId();
			$instanceExternalTracker->registerActivity($id, array('ORIGIN_ID' => $originatorId, 'COMPONENT_ID' => $typeId));
		}
	}
}

class CCrmExternalChannelImportAgent extends \CCrmExternalChannelRestProxy
{
	const CUSTOM_FIELDS = 'CUSTOM';

	protected $entityId = -1;

	public $import = null;

	private static $ENTITY = null;

	protected static function getEntity()
	{
		return self::$ENTITY;
	}

	public static function setEntity($class)
	{
		self::$ENTITY = $class;
	}

	private function getCustomFieldsFieldName()
	{
		return self::CUSTOM_FIELDS;
	}

	protected function sanitizeFields(&$fields, &$error)
	{
		/** @var \CCrmCompanyRestProxy|\CCrmContactRestProxy $entity */
		$entity = $this->getEntity();

		$originFields = $fields;
		$fieldsInfo = $entity->getFieldsInfo();
		if(is_array($fieldsInfo) && count($fieldsInfo)>0)
		{
			$sanitize = array();
			foreach($fieldsInfo as $fieldName => $fieldEntity)
			{
				$sanitize[$fieldName] = is_set($fields, $fieldName) ? $fields[$fieldName]:'';
			}

			$custom =  array_diff_assoc($originFields, $sanitize);
			if(!empty($custom))
			{
				$sanitize[$this->getCustomFieldsFieldName()] = $custom;
			}

			$fields[$this->getCustomFieldsFieldName()] = $sanitize[$this->getCustomFieldsFieldName()];
		}
	}

	protected static function getNameUserFieldExternalUrl()
	{
		return 'UF_CRM_EXTERNAL_URL';
	}

	protected function convertExternalFieldsToFields(&$agent, &$errors)
	{
		/** @var \CCrmCompanyRestProxy|\CCrmContactRestProxy $entity */

		$entity = $this->getEntity();

		/** @var CCrmExternalChannelImport $import */
		$import = $this->import;

		/** @var CCrmExternalChannelConnector $connector */
		$connector = $import->getConnector();

		if(is_set($agent, CCrmExternalChannelImport::EXTERNAL_FIELDS))
		{
			$externalFields = $agent[CCrmExternalChannelImport::EXTERNAL_FIELDS];

			if($entity->getOwnerTypeID() ===  \CCrmOwnerType::Contact && is_set($externalFields, 'COMPANY_ORIGIN_ID'))
			{
				if($this->isValidOriginId($externalFields['COMPANY_ORIGIN_ID']))
				{
					$error = array();
					$result = \CCrmCompanyRestProxy::innerGetList(
							array(),
							array(
									'ORIGIN_ID' => $externalFields['COMPANY_ORIGIN_ID'],
									'ORIGINATOR_ID' => $connector->getOriginatorId()
							),
							array('ID'),
							false,
							$error
					);

					if(!$result)
					{
						$errors[] = implode("\n", $error);
					}
					else
					{
						if ($r = $result->Fetch())
							$agent[CCrmExternalChannelImport::FIELDS]['COMPANY_ID'] = $r['ID'];
						else
							$errors[] = "Company not found. Field COMPANY_ORIGIN_ID - '".$externalFields['COMPANY_ORIGIN_ID']."' is invalid";
					}
				}
				else
					$errors[] = "Field COMPANY_ORIGIN_ID is empty";
			}
			if(is_set($externalFields, 'EXTERNAL_URL') && strlen($externalFields['EXTERNAL_URL'])>0)
			{
				$error = array();
				$userFieldsFieldName = $this->prepareUserField(self::getNameUserFieldExternalUrl(), $error);
				if(count($error)>0)
					$errors[] = implode('; ', $error);
				else
				{
					$agent[CCrmExternalChannelImport::FIELDS][$userFieldsFieldName] = $externalFields['EXTERNAL_URL'];
				}
			}
		}
	}

	protected function prepareUserField($ufName, &$error)
	{
		/** @var \CCrmCompanyRestProxy|\CCrmContactRestProxy $entity */
		$entity = $this->getEntity();

		$ownerTypeID = $entity->getOwnerTypeID();
		$ufProxy = new \CCrmUserFieldRestProxy($ownerTypeID);
		$result = $ufProxy->getList(array(), array('FIELD_NAME'=>$ufName));
		if($result['total']>0)
		{
			$ufFields = $result[0];
			$id = $ufFields['ID'];
		}
		else
		{
			$ufFields['USER_TYPE_ID'] = 'string';
			$ufFields['FIELD_NAME'] = $ufName;

			$langDbResult = \CLanguage::GetList($by = '', $order = '');
			while($lang = $langDbResult->Fetch())
			{
				$lid = $lang['LID'];
				$ufFields['EDIT_FORM_LABEL'][$lid] = $ufFields['LIST_COLUMN_LABEL'][$lid] = $ufFields['LIST_FILTER_LABEL'][$lid] = Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_IMPORT_UF_EXTERNAL_URL');
			}

			$id = $ufProxy->add($ufFields);
		}

		$fieldName = '';
		if($this->isValidID((int)$id))
		{
			$fieldName = $ufFields['FIELD_NAME'];
		}
		else
		{
			$error[] = $ufName.' not created ';
		}

		return $fieldName;
	}

	protected function prepareMultiFields($entityId, &$fields)
	{
		$fmDeleteListFieldId = array();

		$fmResult = $this->innerGetListFieldsMulti($entityId);

		while($fm = $fmResult->Fetch())
		{
			$fmTypeID = $fm['TYPE_ID'];

			$fmDeleteListFieldId[$fmTypeID][] = $fm['ID'];

			if(is_set($fields, $fmTypeID))
			{
				foreach($fields[$fmTypeID] as &$fieldsType)
				{
					$valueType = isset($fieldsType['VALUE_TYPE']) ? trim($fieldsType['VALUE_TYPE']) : '';
					if($valueType === '')
						$fieldsType['VALUE_TYPE'] = \CCrmFieldMulti::GetDefaultValueType($fmTypeID);

					if($fieldsType['VALUE_TYPE'] ==  $fm['VALUE_TYPE']
							&& $fieldsType['VALUE'] == $fm['VALUE']
							&& !is_set($fieldsType, 'ID')
					)
					{
						$fieldsType['ID'] = $fm['ID'];

						$key = array_search($fm['ID'], $fmDeleteListFieldId[$fmTypeID]);
						if($key!==false && $key!==null)
						{
							unset($fmDeleteListFieldId[$fmTypeID][$key]);
						}
					}
				}
				unset($fieldsType);
			}
		}

		if(count($fmDeleteListFieldId)>0)
		{
			foreach($fmDeleteListFieldId as $typeId => $listId)
			{
				if(count($listId) > 0)
				{
					foreach($listId as $id)
						$fields[$typeId][] = array('ID'=>$id, 'DELETE'=>'Y');
				}
			}
		}
	}

	protected function innerGetListFieldsMulti($entityId)
	{
		/** @var \CCrmCompanyRestProxy|\CCrmContactRestProxy $entity */

		$entity = $this->getEntity();

		return \CCrmFieldMulti::GetList(
				array('ID' => 'asc'),
				array(
						'ENTITY_ID' => \CCrmOwnerType::ResolveName($entity->getOwnerTypeID()),
						'ELEMENT_ID' => $entityId
				)
		);
	}

	protected function isValidOriginId($OriginId)
	{
		return $OriginId !== '';
	}

	public function checkFields(&$fields, &$errors)
	{
		if(!is_set($fields,'ORIGIN_ID') || $fields['ORIGIN_ID']=='')
		{
			$errors[] = "ORIGIN_ID is not defined";
		}
		elseif(!is_set($fields,'ORIGIN_VERSION') || $fields['ORIGIN_VERSION']=='')
		{
			$errors[] = "VERSION is not defined";
		}
	}

    public function checkExternalFields(&$fields, &$errors) {}

	protected function fillFields(&$fields, $params=array())
	{
		/** @var CCrmExternalChannelImport $import */
		$import = $this->import;

		/** @var CCrmExternalChannelConnector $connector */
		$connector = $import->getConnector();

		$fields['ORIGINATOR_ID'] = $connector->getOriginatorId();
	}

    public function tryGetOwnerInfos($fieldsAgent, &$error)
	{
		/** @var \CCrmCompanyRestProxy|\CCrmContactRestProxy $entity */
		$entity = $this->getEntity();

		/** @var CCrmExternalChannelImport $import */
		$import = $this->import;

		/** @var CCrmExternalChannelConnector $connector */
		$connector = $import->getConnector();

		$errors = array();
		$result = array(
				'id' => -1,
				'version' => ''
		);

		$entityList = $entity->innerGetList(
				array(),
				array(
						'ORIGIN_ID' => $fieldsAgent['ORIGIN_ID'],
						'ORIGINATOR_ID' => $connector->getOriginatorId()
				),
				array('*'),
				false,
				$errors
		);
		if(!$entityList)
		{
			if(count($errors)>0)
				$error[] = implode('; ', $errors);
		}
		elseif($entityEntity = $entityList->Fetch())
		{
			$result['id'] = $entityEntity['ID'];
			$result['version'] = $entityEntity['ORIGIN_VERSION'];
		}
		return $result;
	}

    public function modify($entityId, $agent, &$resultList)
	{
		/** @var \CCrmCompanyRestProxy|\CCrmContactRestProxy $entity */
		$entity = $this->getEntity();

		$error = array();
		$resultList = array(
				'id'=> -1,
				'process' => array(
						'add' => false,
						'upd' => false,
						'error' => array()
				)
		);

		$errors = array();

		$this->convertExternalFieldsToFields($agent, $errors);

		$this->sanitizeFields($agent[CCrmExternalChannelImport::FIELDS], $errors);

		if(count($errors)>0)
			$error[] = implode('; ', $errors);
		else
		{
			$requisite = new CCrmExternalChannelImportRequisite();
			$requisite->setOwnerEntity($entity);
			$requisite->import = $this->import;

			$fieldsAgent = $agent[CCrmExternalChannelImport::FIELDS];

			$this->fillFields($fieldsAgent);

			$id = 0;
			if(intval($entityId)>0)
			{
				$this->prepareMultiFields($entityId, $fieldsAgent);

				$entity->internalizeFields($fieldsAgent, $entity->getFieldsInfo());

				$errors = array();
				$result = $entity->innerUpdate($entityId, $fieldsAgent, $errors);
				if($result !== false)
				{
					$id = (int)$entityId;
					$resultList['process']['upd'] = true;
				}
			}
			else
			{
				$entity->internalizeFields($fieldsAgent, $entity->getFieldsInfo());

				$errors = array();
				$resultId = $entity->innerAdd($fieldsAgent, $errors);
				if($entity->isValidID($resultId))
				{
					$id = (int)$resultId;
					$resultList['process']['add'] = true;
				}
			}

			if($id>0)
			{
				$resultList['id'] = $id;
				$requisite->setOwnerEntityId($id);
				$requisite->import($agent, $errors);
			}

			if(count($errors)>0)
				$error[] = implode('; ', $errors);

		}

		if(count($error)>0)
			$resultList['process']['error'] = $error;
	}
}

class CCrmExternalChannelImportRequisite extends CCrmExternalChannelImportAgent
{
	private static $ENTITY = null;

	protected $ownerId = -1;
	protected $class = null;

	public function setOwnerEntity($class)
	{
		$this->class = $class;
	}

	protected function getOwnerEntity()
	{
		return $this->class;
	}

	public function setOwnerEntityId($id)
	{
		$this->ownerId = (int)$id;
	}

	protected function getOwnerEntityId()
	{
		return $this->ownerId;
	}

	public function getOwnerTypeID()
	{
		return \CCrmOwnerType::Requisite;
	}

	protected static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new \Bitrix\Crm\EntityRequisite();
		}

		return self::$ENTITY;
	}

	protected function innerList($filter=array())
	{
		/** @var \CCrmCompanyRestProxy|\CCrmContactRestProxy $ownerEntity */
		$ownerEntity = $this->getOwnerEntity();

		/** @var CCrmExternalChannelImport $import */
		$import = $this->import;

		/** @var CCrmExternalChannelConnector $connector */
		$connector = $import->getConnector();


		return $this->getEntity()->getList(
				array(
						'order' => array('ID'),
						'filter' => array_merge(
								$filter,
								array(
										'ENTITY_TYPE_ID' => $ownerEntity->getOwnerTypeID(),
										'ORIGINATOR_ID' => $connector->getOriginatorId()
								)
						),
						'select' => array('*')
				)
		);
	}
/*
	protected function innerGetEntity($entityId, $originalId, &$errors)
	{
		return $this->innerList(array(
				'=ENTITY_ID' => $entityId,
				'=XML_ID' => $originalId,
		));
	}
*/
	protected function innerUpdate($id, $fields, &$errors)
	{
		$entity = $this->getEntity();

		if(!$this->isValidID((int)$id))
		{
			$errors[] = "ID is not defined or invalid";
			return false;
		}

		$result = $entity->update($id, $fields);
		if(!$result->isSuccess())
		{
			$error = "";
			foreach($result->getErrorMessages() as $message)
				$error .= $message."\n";
			$errors[] = $error;

			return false;
		}

		return $id;
	}

	protected function innerAdd($fields, &$errors)
	{
		$entity = $this->getEntity();

		$result = $entity->add($fields);
		if($result->isSuccess())
		{
			$id = $result->getId();
		}
		else
		{
			$error = "";
			foreach($result->getErrorMessages() as $message)
				$error .= $message."\n";
			$errors[] = $error;

			return false;
		}

		return $id;
	}

	protected function innerDelete($id, &$errors)
	{
		$entity = $this->getEntity();

		$r = $entity->delete($id);
		if(!$r->isSuccess())
			$errors[] = array_shift($r->getErrors())->getMessage();
	}

	protected function getFieldsInfo()
	{
		$result = array();

		$fieldsInfo = array_flip(
				array_merge(
						array('NAME', 'XML_ID'),
						\Bitrix\Crm\EntityRequisite::getRqFields()
				)
		);

		foreach($fieldsInfo as $name=>$key)
		{
			$result[$name] = array('TYPE' => '', 'ATTRIBUTES'=>'');
		}

		return $result;
	}

	protected function fillFields(&$requisite)
	{
		/** @var \CCrmCompanyRestProxy|\CCrmContactRestProxy $ownerEntity */
		$ownerEntity = $this->getOwnerEntity();

		/** @var CCrmExternalChannelImport $import */
		$import = $this->import;

		/** @var CCrmExternalChannelConnector $connector */
		$connector = $import->getConnector();

		/** @var CCrmExternalChannelImportPreset $preset */
		$preset = $import->getPreset();

		$curDateTime = new \Bitrix\Main\Type\DateTime();
		$curUserId = \CCrmSecurityHelper::GetCurrentUserID();

		$requisite['DATE_CREATE'] = $curDateTime;
		$requisite['DATE_MODIFY'] = $curDateTime;
		$requisite['CREATED_BY_ID'] = $curUserId;
		$requisite['MODIFY_BY_ID'] = $curUserId;
		$requisite['NAME'] = !is_set($requisite, 'NAME') || $requisite['NAME']=='' ? Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_IMPORT_REQUISITE_NAME'):$requisite['NAME'];
		$requisite['PRESET_ID'] = $preset->getPresetId();
		$requisite['ENTITY_TYPE_ID'] = $ownerEntity->getOwnerTypeID();
		$requisite['ENTITY_ID'] = $this->getOwnerEntityId();
		$requisite['ACTIVE'] = 'Y';
		$requisite['ORIGINATOR_ID'] = $connector->getOriginatorId();

	}

	protected function sanitizeFields(&$requisite, &$error)
	{
		if(is_array($requisite) && count($requisite)>0)
		{
			$fieldsInfo = CCrmExternalChannelImportAddress::getFieldsInfo();

			if(is_set($requisite, CCrmExternalChannelImport::FIELDS_ADDRESS))
			{
				foreach($requisite[CCrmExternalChannelImport::FIELDS_ADDRESS] as $addresTypeId=>$addresFields)
				{
					unset($requisite[CCrmExternalChannelImport::FIELDS_ADDRESS][$addresTypeId]);

					if(!is_numeric($addresTypeId))
						$addresTypeId = \Bitrix\Crm\EntityAddressType::resolveID($addresTypeId);

					if(is_set($fieldsInfo, $addresTypeId))
						$requisite[CCrmExternalChannelImport::FIELDS_ADDRESS][$addresTypeId] = $addresFields;
				}
			}
		}
	}

	protected function prepareFieldsAddress(&$requisite)
	{
		foreach( CCrmExternalChannelImportAddress::getFieldsInfo() as $typeId=>$typeInfo)
		{
			if(!is_set($requisite, CCrmExternalChannelImport::FIELDS_ADDRESS) ||
					!is_set($requisite[CCrmExternalChannelImport::FIELDS_ADDRESS], $typeId)
			)
				$requisite[CCrmExternalChannelImport::FIELDS_ADDRESS][$typeId]['DELETED'] = 'Y';
		}
	}

    public function checkFields(&$fields, &$error)
	{
		if(is_set($fields, CCrmExternalChannelImport::FIELDS_REQUISITE) && count($fields[CCrmExternalChannelImport::FIELDS_REQUISITE])>0)
		{
			foreach($fields[CCrmExternalChannelImport::FIELDS_REQUISITE] as $requisiteKey=>$requisite)
			{
				if(is_array($requisite) && count($requisite)>0)
				{
					if(!is_set($requisite, 'XML_ID') || $requisite['XML_ID'] == '')
					{
						$error[] = " requisite.$requisiteKey. xml_id is not defined";
					}

					if(is_set($fields, CCrmExternalChannelImport::FIELDS_BANK))
						CCrmExternalChannelImportBank::checkFields($fields, $error);
				}
				else
				{
					$error[] = " field requisite is invalid";
					unset($fields[CCrmExternalChannelImport::FIELDS_REQUISITE][$requisiteKey]);
					break;
				}
			}
		}
	}

    public function import(&$fields, &$error)
	{
		if(!$this->isValidID($this->getOwnerEntityId()))
			$error[] = "EntityId is not defined or invalid";

		$this->checkFields($fields, $error);

		if(count($error)<=0)
		{
			$proccessList = array();

			if(is_set($fields, CCrmExternalChannelImport::FIELDS_REQUISITE) && count($fields[CCrmExternalChannelImport::FIELDS_REQUISITE])>0)
			{
				$bank = new CCrmExternalChannelImportBank();
				$bank->setOwnerEntity($this);
				$bank->import = $this->import;

				foreach($fields[CCrmExternalChannelImport::FIELDS_REQUISITE] as $requisite)
				{
					$fields = $requisite;
					$this->sanitizeFields($requisite, $errors);
					$this->internalizeFields($requisite, $this->getFieldsInfo());
					$this->fillFields($requisite);

					$result = $this->innerList(array(
							'=ENTITY_ID' => $this->getOwnerEntityId(),
							'=XML_ID' => $requisite['XML_ID'],
					));

					if($r = $result->fetch())
					{
						$this->prepareFieldsAddress($requisite);
						$id = $this->innerUpdate($r['ID'], $requisite, $error);
					}
					else
						$id = $this->innerAdd($requisite, $error);

					if($this->isValidID((int)$id) && count($error)<=0)
					{
						$proccessList[] = $id;
						$bank->setOwnerEntityId($id);
						$bank->import($fields, $error);
					}
				}

				$resultList = $this->innerList(array('=ENTITY_ID' => $this->getOwnerEntityId()));
				while($listRequisite = $resultList->fetch())
				{
					if(!in_array($listRequisite['ID'], $proccessList))
						$this->innerDelete($listRequisite['ID'], $error);
				}
			}
			else
			{
				$resultList = $this->innerList(array('=ENTITY_ID' => $this->getOwnerEntityId()));
				while($listRequisite = $resultList->fetch())
					$this->innerDelete($listRequisite['ID'], $error);
			}
		}
	}

}

class CCrmExternalChannelImportBank extends CCrmExternalChannelImportRequisite
{
	private static $ENTITY = null;

	protected static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new \Bitrix\Crm\EntityBankDetail();
		}

		return self::$ENTITY;
	}

	protected function getFieldsInfo()
	{
		$result = array();

		$fieldsInfo = array_flip(
				array_merge(
						array('NAME', 'XML_ID'),
						\Bitrix\Crm\EntityBankDetail::getRqFields(),
						array('COMMENTS')
				)
		);

		foreach($fieldsInfo as $name=>$key)
		{
			$result[$name] = array('TYPE' => '', 'ATTRIBUTES'=>'');
		}

		return $result;
	}

    public function checkFields(&$fields, &$error)
	{
		if(is_set($fields, CCrmExternalChannelImport::FIELDS_BANK) && count($fields[CCrmExternalChannelImport::FIELDS_BANK])>0)
		{
			foreach($fields[CCrmExternalChannelImport::FIELDS_BANK] as $bankKey=>$bank)
			{
				if(is_array($bank) && count($bank)>0)
				{
					if(!is_set($bank, 'XML_ID') || $bank['XML_ID']=='')
					{
						$error[] = " requisite.$fields:bank.$bankKey xml_id is not defined";
					}
				}
				else
				{
					$error[] = " field requisite.$fields:bank is invalid";
					unset($fields[CCrmExternalChannelImport::FIELDS_BANK][$bankKey]);
					break;
				}
			}
		}
	}

	protected function fillFields(&$requisite)
	{
		/** @var \CCrmCompanyRestProxy|\CCrmContactRestProxy $ownerEntity */
		$ownerEntity = $this->getOwnerEntity();

		/** @var CCrmExternalChannelImport $import */
		$import = $this->import;

		/** @var CCrmExternalChannelConnector $connector */
		$connector = $import->getConnector();

		if(isset($requisite['COMMENTS']))
		{
			$requisite['COMMENTS'] = $this->sanitizeHtml($requisite['COMMENTS']);
		}

		$curUserId = \CCrmSecurityHelper::GetCurrentUserID();

		$requisite['ENTITY_ID'] = $this->getOwnerEntityId();
		$requisite['ENTITY_TYPE_ID'] = $ownerEntity->getOwnerTypeID();
		$requisite['NAME'] = !is_set($requisite, 'NAME') || $requisite['NAME']=='' ? Loc::getMessage('CRM_REST_EXTERNAL_CHANNEL_IMPORT_BANK_REQUISITE_NAME'):$requisite['NAME'];
		$requisite['CREATED_BY_ID'] = $curUserId;
		$requisite['MODIFY_BY_ID'] = $curUserId;
		$requisite['COUNTRY_ID'] = \Bitrix\Crm\EntityPreset::getCurrentCountryId();
		$requisite['ACTIVE'] = 'Y';
		$requisite['ORIGINATOR_ID'] = $connector->getOriginatorId();
	}

    public function import(&$requisite, &$error)
	{
		$proccessList = array();

		if(is_array($requisite[CCrmExternalChannelImport::FIELDS_BANK]) && !empty($requisite[CCrmExternalChannelImport::FIELDS_BANK]))
		{
			foreach($requisite[CCrmExternalChannelImport::FIELDS_BANK] as &$bank)
			{
				$this->internalizeFields($bank, $this->getFieldsInfo());

				$this->fillFields($bank);

				$result = $this->innerList(array(
						'=ENTITY_ID' => $this->getOwnerEntityId(),
						'=XML_ID' => $bank['XML_ID'],
				));

				if($r = $result->fetch())
				{
					$id = $this->innerUpdate($r['ID'], $bank, $error);
					if ($id !== false)
					{
						$proccessList[] = $id;
					}
				}
				else
				{
					$id = $this->innerAdd($bank, $error);
					if ($id !== false)
					{
						$proccessList[] = $id;
					}
				}
			}
			unset($bank);

			$resultList = $this->innerList(array('=ENTITY_ID' => $this->getOwnerEntityId()));
			while($listBank = $resultList->fetch())
			{
				if(!in_array($listBank['ID'], $proccessList))
					$this->innerDelete($listBank['ID'], $error);
			}
		}
		else
		{
			$resultList = $this->innerList(array('=ENTITY_ID' => $this->getOwnerEntityId()));
			while($listBank = $resultList->fetch())
				$this->innerDelete($listBank['ID'], $error);
		}
	}
}

class CCrmExternalChannelImportAddress extends CCrmExternalChannelImportRequisite
{
	protected function getFieldsInfo()
	{
		$result = array();

		foreach(\Bitrix\Crm\RequisiteAddress::getClientTypeInfos() as $typeInfo)
		{
			$result[$typeInfo['id']] = $typeInfo;
		}

		return $result;
	}
}