<?php

namespace Bitrix\ImOpenLines;

use Bitrix\Main,
	Bitrix\Main\Localization\Loc,
	Bitrix\Crm\Automation\Trigger\OpenLineTrigger;

Loc::loadMessages(__FILE__);

class Crm
{
	const FIND_BY_CODE = 'IMOL';
	const FIND_BY_NAME = 'NAME';
	const FIND_BY_EMAIL = 'EMAIL';
	const FIND_BY_PHONE = 'PHONE';

	const ENTITY_NONE = 'NONE';
	const ENTITY_LEAD = 'LEAD';
	const ENTITY_COMPANY = 'COMPANY';
	const ENTITY_CONTACT = 'CONTACT';

	private $error = null;
	public function __construct()
	{
		$this->error = new Error(null, '', '');
		\Bitrix\Main\Loader::includeModule("crm");
	}

	public static function getSourceName($userCode, $lineTitle = '')
	{
		list($messengerType) = explode('|', $userCode);
		$linename = Loc::getMessage('IMOL_CRM_LINE_TYPE_'.strtoupper($messengerType));
		if (!$linename && \Bitrix\Main\Loader::includeModule("imconnector"))
		{
			$linename = \Bitrix\ImConnector\Connector::getNameConnector($messengerType);
		}

		return ($linename? $linename: $messengerType).($lineTitle? ' - '.$lineTitle: '');
	}

	public static function getCommunicationType($userCode)
	{
		list($messengerType) = explode('|', $userCode);

		if ($messengerType == 'telegrambot')
		{
			$communicationType = 'TELEGRAM';
		}
		else if ($messengerType == 'facebook')
		{
			$communicationType = 'FACEBOOK';
		}
		else if ($messengerType == 'vkgroup')
		{
			$communicationType = 'VK';
		}
		else if ($messengerType == 'network')
		{
			$communicationType = 'BITRIX24';
		}
		else if ($messengerType == 'livechat')
		{
			$communicationType = 'OPENLINE';
		}
		else if ($messengerType == 'viber')
		{
			$communicationType = 'VIBER';
		}
		else if ($messengerType == 'instagram')
		{
			$communicationType = 'INSTAGRAM';
		}
		else
		{
			$communicationType = 'IMOL';
		}
		return $communicationType;
	}

	public static function hasAccessToEntity($entityType, $entityId)
	{
		if (!$entityType || !$entityId || $entityType == 'NONE')
			return true;

		return \CCrmAuthorizationHelper::CheckReadPermission($entityType, $entityId);
	}

	public function find($type = self::FIND_BY_CODE, $params = Array())
	{
		if (!\IsModuleInstalled('crm') || empty($params))
		{
			return false;
		}

		if ($type == self::FIND_BY_CODE)
		{
			$communicationType = self::getCommunicationType($params['CODE']);
			$criterion = new \Bitrix\Crm\Integrity\DuplicateCommunicationCriterion($communicationType, 'imol|'.$params['CODE']);
		}
		else if ($type == self::FIND_BY_NAME)
		{
			if (empty($params['LAST_NAME']) || empty($params['NAME']))
				return false;

			$criterion = new \Bitrix\Crm\Integrity\DuplicatePersonCriterion($params['LAST_NAME'], $params['NAME']);
		}
		else if ($type == self::FIND_BY_EMAIL || $type == self::FIND_BY_PHONE)
		{
			if (empty($params[$type]))
				return false;

			$criterion = new \Bitrix\Crm\Integrity\DuplicateCommunicationCriterion($type, $params[$type]);
		}
		else
		{
			return false;
		}

		$entityTypes = array(
			\CCrmOwnerType::Contact,
			\CCrmOwnerType::Company,
			\CCrmOwnerType::Lead
		);
		$crm = Array();
		$result = Array();
		foreach($entityTypes as $entityType)
		{
			$duplicate = $criterion->find($entityType, 1);
			if($duplicate !== null)
			{
				$mnemonic = '';
				if ($entityType == \CCrmOwnerType::Contact)
					$mnemonic = \CCrmOwnerType::ContactName;
				elseif ($entityType == \CCrmOwnerType::Lead)
					$mnemonic = \CCrmOwnerType::LeadName;
				elseif ($entityType == \CCrmOwnerType::Company)
					$mnemonic = \CCrmOwnerType::CompanyName;

				$crm[$mnemonic] = $duplicate->getEntityIDs();
			}
		}
		if ($crm)
		{
			if (isset($crm['CONTACT']))
			{
				$result['ENTITY_TYPE'] = \CCrmOwnerType::ContactName;
				$result['ENTITY_ID'] = $crm['CONTACT'][0];
			}
			else if (isset($crm['LEAD']))
			{
				$result['ENTITY_TYPE'] = \CCrmOwnerType::LeadName;
				$result['ENTITY_ID'] = $crm['LEAD'][0];
			}
			else if (isset($crm['COMPANY']))
			{
				$result['ENTITY_TYPE'] = \CCrmOwnerType::CompanyName;
				$result['ENTITY_ID'] = $crm['COMPANY'][0];
			}

			if (isset($crm['CONTACT']) || isset($crm['COMPANY']))
			{
				if (isset($crm['CONTACT'][0]))
				{
					$result['BINDINGS'][] = array(
						'OWNER_ID' => $crm['CONTACT'][0],
						'OWNER_TYPE_ID' => \CCrmOwnerType::Contact
					);
				}
				if (isset($crm['COMPANY'][0]))
				{
					$result['BINDINGS'][] = array(
						'OWNER_ID' => $crm['COMPANY'][0],
						'OWNER_TYPE_ID' => \CCrmOwnerType::Company
					);
				}

				$deals = array();
				if ($deals)
				{
					$result['BINDINGS'][] = array(
						'OWNER_ID' => $deals[0]['ID'],
						'OWNER_TYPE_ID' => \CCrmOwnerType::Deal
					);
				}
			}
			else if (isset($crm['LEAD'][0]))
			{
				$result['BINDINGS'][] = array(
					'OWNER_ID' => $crm['LEAD'][0],
					'OWNER_TYPE_ID' => \CCrmOwnerType::Lead
				);
			}
		}

		return !empty($result)? $result: false;
	}

	public function addLead($params)
	{
		if (!\IsModuleInstalled('crm'))
		{
			return false;
		}

		$configManager = new Config();
		$config = $configManager->get($params['CONFIG_ID']);

		$communicationType = self::getCommunicationType($params['USER_CODE']);

		$user = \Bitrix\Im\User::getInstance($params['USER_ID']);
		$comments = Loc::getMessage('IMOL_CRM_CREATE_LEAD_COMMENTS', Array(
			'#LINE_NAME#' => $config['LINE_NAME'],
			'#CONNECTOR_NAME#' => self::getSourceName($params['USER_CODE'])
		));

		$userName = '';
		if (!$user->getLastName() && !$user->getName())
		{
			$userName = $user->getFullName(false);
		}
		else
		{
			$userName = $user->getName(false);
		}
		
		$fields = array(
			'TITLE' => $params['TITLE'],
			'LAST_NAME' => $user->getLastName(false),
			'NAME' => $userName,
			'OPENED' => 'Y',
			'COMMENTS' => $comments,
			'EMAIL_WORK' => $user->getEmail(),
			'WEB_HOME' => $user->getWebsite(),
			'PHONE_MOBILE' => $user->getPhone(),
			'IM_'.$communicationType => 'imol|'.$params['USER_CODE'],
		);

		// Get CRM source
		$statuses = \CCrmStatus::GetStatusList("SOURCE");
		if (
			$config['CRM_SOURCE'] == Config::CRM_SOURCE_AUTO_CREATE ||
			!isset($statuses[$config['CRM_SOURCE']])
		)
		{
			$params['CRM_SOURCE'] = $params['CONFIG_ID'].'|'.$communicationType;

			if (!isset($statuses[$config['CRM_SOURCE']]))
			{
				$entity = new \CCrmStatus("SOURCE");
				$entity->Add(array(
					'NAME' => self::getSourceName($params['USER_CODE'], $config['LINE_NAME']),
					'STATUS_ID' => $params['CRM_SOURCE'],
					'SORT' => 115,
					'SYSTEM' => 'N'
				));
			}
			$fields['SOURCE_ID'] = $params['CRM_SOURCE'];
		}
		else
		{
			$fields['SOURCE_ID'] = $config['CRM_SOURCE'];
		}

		$fields['FM'] = \CCrmFieldMulti::PrepareFields($fields);

		$leadManager = new \CCrmLead(false);
		$id = $leadManager->Add($fields, true, Array(
			'CURRENT_USER' => $params['OPERATOR_ID'],
			'DISABLE_USER_FIELD_CHECK' => true
		));

		list($connectorId, $lineId) = explode('|', $params['USER_CODE']);
		\Bitrix\Crm\Integration\Channel\IMOpenLineTracker::getInstance()->registerLead($id, array('ORIGIN_ID' => $lineId, 'COMPONENT_ID' => $connectorId));
		
		$errors = array();
		\CCrmBizProcHelper::AutoStartWorkflows(
			\CCrmOwnerType::Lead,
			$id,
			\CCrmBizProcEventType::Create,
			$errors
		);
		Log::write($fields, 'LEAD CREATED');

		//Region automation
		if (class_exists('\Bitrix\Crm\Automation\Factory'))
		{
			\Bitrix\Crm\Automation\Factory::runOnAdd(\CCrmOwnerType::Lead, $id);
		}
		//end region

		return $id;
	}

	public function get($type, $id, $withMultiFields = false)
	{
		if (!\IsModuleInstalled('crm'))
		{
			return false;
		}

		if ($type == self::ENTITY_LEAD)
		{
			$entity = new \CCrmLead(false);
		}
		else if ($type == self::ENTITY_COMPANY)
		{
			$entity = new \CCrmCompany(false);
		}
		else if ($type == self::ENTITY_CONTACT)
		{
			$entity = new \CCrmContact(false);
		}
		else
		{
			return false;
		}
		$data = $entity->GetByID($id, false);
		
		if ($withMultiFields)
		{
			$multiFields = new \CCrmFieldMulti();
			$res = $multiFields->GetList(Array(), Array(
				'ENTITY_ID' => $type,
				'ELEMENT_ID' => $id
			));
			while ($row = $res->Fetch())
			{
				$data['FM'][$row['TYPE_ID']][$row['VALUE_TYPE']][] = $row['VALUE'];
			}
		}

		return $data;
	}

	public function getLink($type, $id)
	{
		if (!\IsModuleInstalled('crm'))
		{
			return false;
		}

		$link = \CCrmOwnerType::GetShowUrl(\CCrmOwnerType::ResolveID($type), $id, false);
		return $link;
	}

	public function update($type, $id, $updateFields)
	{
		if (!\IsModuleInstalled('crm'))
		{
			return false;
		}

		if ($type == self::ENTITY_LEAD)
		{
			$entity = new \CCrmLead(false);
		}
		else if ($type == self::ENTITY_COMPANY)
		{
			$entity = new \CCrmCompany(false);
		}
		else if ($type == self::ENTITY_CONTACT)
		{
			$entity = new \CCrmContact(false);
		}
		else
		{
			return false;
		}

		$updateFields['FM'] = \CCrmFieldMulti::PrepareFields($updateFields);

		$entity->Update($id, $updateFields);

		return true;
	}
	
	public function delete($type, $id)
	{
		if (!\IsModuleInstalled('crm'))
		{
			return false;
		}

		if ($type == self::ENTITY_LEAD)
		{
			$entity = new \CCrmLead(false);
		}
		else if ($type == self::ENTITY_COMPANY)
		{
			$entity = new \CCrmCompany(false);
		}
		else if ($type == self::ENTITY_CONTACT)
		{
			$entity = new \CCrmContact(false);
		}
		else
		{
			return false;
		}
	
		$entity->Delete($id);

		return true;
	}
	
	public function deleteMultiField($type, $id, $fieldType, $fieldValue)
	{
		if (!\IsModuleInstalled('crm'))
		{
			return false;
		}
		
		$crmFieldMulti = new \CCrmFieldMulti();
		$ar = \CCrmFieldMulti::GetList(Array(), Array(
			'TYPE_ID' => $fieldType,
			'RAW_VALUE' => $fieldValue,
			'ENTITY_ID' => $type,
			'ELEMENT_ID' => $id,
		));
		if ($row = $ar->Fetch())
		{
			$crmFieldMulti->Delete($row['ID']);
		}
		
		return true;
	}

	public function addActivity($params)
	{
		if (!\IsModuleInstalled('crm'))
		{
			return false;
		}

		Log::write($params, 'CRM ADD ACTIVITY');

		list($connectorId, $lineId) = explode('|', $params['USER_CODE']);

		$direction = $params['MODE'] == Session::MODE_INPUT? \CCrmActivityDirection::Incoming : \CCrmActivityDirection::Outgoing;
		$arFields = array(
			'TYPE_ID' => \CCrmActivityType::Provider,
			'PROVIDER_ID' => \Bitrix\Crm\Activity\Provider\OpenLine::getId(),
			'PROVIDER_TYPE_ID' => $lineId,
			'SUBJECT' => Loc::getMessage('IMOL_CRM_CREATE_ACTIVITY', Array('#LEAD_NAME#' => $params['TITLE'])),
			'ASSOCIATED_ENTITY_ID' => $params['SESSION_ID'],
			'START_TIME' => $params['DATE_CREATE'],
			'COMPLETED' => isset($params['COMPLETED']) && $params['COMPLETED'] == 'Y'? 'Y': 'N',
			'DIRECTION' => $direction,
			'NOTIFY_TYPE' => \CCrmActivityNotifyType::None,
			'BINDINGS' => $params['CRM_BINDINGS'],
			'SETTINGS' => array(),
			'AUTHOR_ID' => isset($params['AUTHOR_ID'])? $params['AUTHOR_ID']: $params['RESPONSIBLE_ID'],
			'RESPONSIBLE_ID' => $params['RESPONSIBLE_ID'],
			'PROVIDER_PARAMS' => Array('USER_CODE' => $params['USER_CODE']),
			'ORIGIN_ID' => 'IMOL_'.$params['SESSION_ID'],

			'RESULT_STATUS' => isset($params['ANSWERED']) && $params['ANSWERED'] == 'Y'? \Bitrix\Crm\Activity\StatisticsStatus::Answered: \Bitrix\Crm\Activity\StatisticsStatus::Unanswered,
			'RESULT_MARK' => \Bitrix\Crm\Activity\StatisticsMark::None,
			'RESULT_SOURCE_ID' => $connectorId,
		);

		if (isset($params['DATE_CLOSE']))
		{
			$params['END_TIME'] = $params['DATE_CLOSE'];
		}
		$ID = \CCrmActivity::Add($arFields, false, true, array('REGISTER_SONET_EVENT' => true));
		
		if ($ID)
		{
			\Bitrix\Crm\Integration\Channel\IMOpenLineTracker::getInstance()->registerActivity($ID, array('ORIGIN_ID' => $lineId, 'COMPONENT_ID' => $connectorId));
			
			if(isset($params['CRM_ENTITY_ID']) && isset($params['CRM_ENTITY_TYPE']))
			{
				$communication = array(
					array(
						'ID' => 0,
						'TYPE' => 'IM',
						'VALUE' => 'imol|'.$params['USER_CODE'],
						'ENTITY_ID' => $params['CRM_ENTITY_ID'],
						'ENTITY_TYPE_ID' => \CCrmOwnerType::ResolveId($params['CRM_ENTITY_TYPE'])
					)
				);
				\CCrmActivity::SaveCommunications($ID, $communication, $arFields, true, false);
			}
			Log::write($ID, 'CRM ACTIVITY CREATED');
		}
		else
		{
			if ($error = $GLOBALS["APPLICATION"]->GetException())
			{
				Log::write($error->GetString(), 'CRM ACTIVITY ERROR');
			}
		}

		return $ID;
	}

	public function executeAutomationTrigger(array $bindings, array $data)
	{
		return OpenLineTrigger::execute($bindings, $data);
	}

	public function updateActivity($params)
	{
		if (!\IsModuleInstalled('crm'))
		{
			return false;
		}

		Log::write($params, 'CRM UPDATE ACTIVITY');

		if (!isset($params['UPDATE']) || !is_array($params['UPDATE']))
		{
			return false;
		}


		if (isset($params['ID']))
		{
			$activity = \CCrmActivity::GetByID($params['ID'], false);
		}
		else if (isset($params['SESSION_ID']))
		{
			$activity = \CCrmActivity::GetByOriginID('IMOL_'.$params['SESSION_ID'], false);
		}
		else
		{
			return false;
		}

		if (!$activity)
		{
			return false;
		}

		if (isset($params['UPDATE']['ANSWERED']))
		{
			$params['UPDATE']['RESULT_STATUS'] = $params['UPDATE']['ANSWERED'] == 'Y'? \Bitrix\Crm\Activity\StatisticsStatus::Answered: \Bitrix\Crm\Activity\StatisticsStatus::Unanswered;
			unset($params['UPDATE']['ANSWERED']);
		}

		\CCrmActivity::Update($activity['ID'], $params['UPDATE'], false, true, Array('REGISTER_SONET_EVENT' => true));

		return true;
	}

	public function getEntityData($entityType, $entityId)
	{
		if (!\IsModuleInstalled('crm'))
		{
			return false;
		}

		$entityId = intval($entityId);
		if ($entityId <= 0)
			return false;

		if ($entityType == \CCrmOwnerType::ContactName)
		{
			$dbEntity = \CCrmContact::GetListEx(
				array(),
				array('=ID' => $entityId, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array(
					'ID',
					'HONORIFIC',
					'NAME',
					'SECOND_NAME',
					'LAST_NAME',
					'PHOTO',
					'POST',
					'COMPANY_ID',
					'COMPANY_TITLE',
					'ASSIGNED_BY_ID'
				)
			);
		}
		elseif ($entityType == \CCrmOwnerType::LeadName)
		{
			$dbEntity = \CCrmLead::GetListEx(
				array(),
				array('=ID' => $entityId, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array(
					'ID',
					'TITLE',
					'HONORIFIC',
					'NAME',
					'SECOND_NAME',
					'LAST_NAME',
					'POST',
					'COMPANY_TITLE',
					'ASSIGNED_BY_ID'
				)
			);
		}
		elseif ($entityType == \CCrmOwnerType::CompanyName)
		{
			$dbEntity = \CCrmCompany::GetListEx(
				array(),
				array('=ID' => $entityId, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array(
					'ID',
					'TITLE',
					'LOGO',
					'ASSIGNED_BY_ID'
				)
			);
		}
		else
		{
			return false;
		}

		$entity = $dbEntity->Fetch();
		if (!$entity)
		{
			return false;
		}

		return Array('ASSIGNED_BY_ID' => intval($entity['ASSIGNED_BY_ID']));
	}

	public function getEntityCard($entityType, $entityId)
	{
		if (!\Bitrix\Main\Loader::includeModule('im'))
		{
			return null;
		}
		
		if (!in_array($entityType, Array(self::ENTITY_LEAD, self::ENTITY_CONTACT, self::ENTITY_COMPANY)))
		{
			return null;	
		}
		
		$entityData = $this->get($entityType, $entityId, true);
		
		$attach = new \CIMMessageParamAttach();
		
		$entityGrid = Array();
		if ($entityType == self::ENTITY_LEAD)
		{
			if (isset($entityData['TITLE']))
			{
				$attach->AddLink(Array(
					'NAME' => $entityData['TITLE'],
					'LINK' => $this->getLink($entityType, $entityData['ID']),
				));
			}
			
			if (isset($entityData['FULL_NAME']) && strpos($entityData['TITLE'], $entityData['FULL_NAME']) === false)
			{
				$entityGrid[] = Array('DISPLAY' => 'COLUMN', 'NAME' => Loc::getMessage('IMOL_CRM_CARD_FULL_NAME'), 'VALUE' => $entityData['FULL_NAME']);
			}
			if (!empty($entityData['COMPANY_TITLE']))
			{
				$entityGrid[] = Array('DISPLAY' => 'COLUMN', 'NAME' => Loc::getMessage('IMOL_CRM_CARD_COMPANY_TITLE'), 'VALUE' => $entityData['COMPANY_TITLE']);
			}
			if (!empty($entityData['POST']))
			{
				$entityGrid[] = Array('DISPLAY' => 'COLUMN', 'NAME' => Loc::getMessage('IMOL_CRM_CARD_POST'), 'VALUE' => $entityData['POST']);
			}
			
		}
		else if ($entityType == self::ENTITY_CONTACT)
		{
			if (isset($entityData['FULL_NAME']))
			{
				$attach->AddLink(Array(
					'NAME' => $entityData['FULL_NAME'],
					'LINK' => $this->getLink($entityType, $entityData['ID']),
				));
			}
			
			if (!empty($entityData['POST']))
			{
				$entityGrid[] = Array('DISPLAY' => 'COLUMN', 'NAME' => Loc::getMessage('IMOL_CRM_CARD_POST'), 'VALUE' => $entityData['POST']);
			}
		}
		else if ($entityType == self::ENTITY_COMPANY)
		{
			if (isset($entityData['TITLE']))
			{
				$attach->AddLink(Array(
					'NAME' => $entityData['TITLE'],
					'LINK' => $this->getLink($entityType, $entityData['ID']),
				));
			}
		}
		
		if ($entityData['HAS_PHONE'] == 'Y' && isset($entityData['FM']['PHONE']))
		{
			$fields = Array(); 
			foreach ($entityData['FM']['PHONE'] as $phones)
			{
				foreach ($phones as $phone)
				{
					$fields[] = $phone;
				}
			}
			$entityGrid[] = Array('DISPLAY' => 'LINE', 'NAME' => Loc::getMessage('IMOL_CRM_CARD_PHONE'), 'VALUE' => implode('[br]', $fields), 'HEIGHT' => '20');
		}
		if ($entityData['HAS_EMAIL'] == 'Y' && $entityData['FM']['EMAIL'])
		{
			$fields = Array(); 
			foreach ($entityData['FM']['EMAIL'] as $emails)
			{
				foreach ($emails as $email)
				{
					$fields[] = $email;
				}
			}
			$entityGrid[] = Array('DISPLAY' => 'LINE', 'NAME' => Loc::getMessage('IMOL_CRM_CARD_EMAIL'), 'VALUE' => implode('[br]', $fields), 'HEIGHT' => '20');
		}
		$attach->AddGrid($entityGrid);
		
		return $attach;
	}

	public function getError()
	{
		return $this->error;
	}
}
