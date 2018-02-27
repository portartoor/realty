<?php

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Integration;
use Bitrix\Crm\Activity;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);

class CrmActivityPlannerComponent extends \CBitrixComponent
{
	protected function getActivityId()
	{
		return isset($this->arParams['ELEMENT_ID']) ? (int) $this->arParams['ELEMENT_ID'] : 0;
	}

	protected function getCalendarEventId()
	{
		return isset($this->arParams['CALENDAR_EVENT_ID']) ? (int) $this->arParams['CALENDAR_EVENT_ID'] : 0;
	}

	protected function getOwnerTypeId()
	{
		if (!empty($this->arParams['OWNER_TYPE_ID']))
			return (int) $this->arParams['OWNER_TYPE_ID'];
		if (isset($this->arParams['OWNER_TYPE']))
			return CCrmOwnerType::ResolveID($this->arParams['OWNER_TYPE']);

		return 0;
	}

	protected function getOwnerId()
	{
		return isset($this->arParams['OWNER_ID']) ? (int) $this->arParams['OWNER_ID'] : 0;
	}

	protected function getActivityType()
	{
		return isset($this->arParams['TYPE_ID']) ? (int) $this->arParams['TYPE_ID'] : 0;
	}

	protected function getProviderId()
	{
		return isset($this->arParams['PROVIDER_ID']) ? (string) $this->arParams['PROVIDER_ID'] : '';
	}

	protected function getProviderTypeId()
	{
		return isset($this->arParams['PROVIDER_TYPE_ID']) ? (string) $this->arParams['PROVIDER_TYPE_ID'] : '';
	}

	protected function getAction()
	{
		return isset($this->arParams['ACTION']) ? strtoupper((string) $this->arParams['ACTION']) : '';
	}
	
	protected function getPlannerId()
	{
		return isset($this->arParams['PLANNER_ID']) ? (string) $this->arParams['PLANNER_ID'] : '';
	}

	protected function getFromActivityId()
	{
		return isset($this->arParams['FROM_ACTIVITY_ID']) ? (int) $this->arParams['FROM_ACTIVITY_ID'] : 0;
	}

	protected function getAssociatedEntityId()
	{
		return isset($this->arParams['ASSOCIATED_ENTITY_ID']) ? (int) $this->arParams['ASSOCIATED_ENTITY_ID'] : 0;
	}
	
	protected function getActivityAdditionalData($activityId, &$activity, $provider = null)
	{
		//bindings
		$activity['BINDINGS'] = $activityId? CCrmActivity::GetBindings($activityId) : array();

		//communications
		if (empty($activity['COMMUNICATIONS']))
			$activity['COMMUNICATIONS'] = $activityId? CCrmActivity::GetCommunications($activityId) : array();

		/** @var Activity\Provider\Base $provider */
		if (!$activityId && $provider)
		{
			$activity['COMMUNICATIONS'] = $this->getCrmEntityCommunications(
				$activity['OWNER_TYPE_ID'],
				$activity['OWNER_ID'],
				$provider::getCommunicationType(isset($activity['PROVIDER_TYPE_ID']) ? $activity['PROVIDER_TYPE_ID'] : null)
			);
		}


		//attaches
		$activity['STORAGE_TYPE_ID'] = isset($activity['STORAGE_TYPE_ID']) ? (int) $activity['STORAGE_TYPE_ID'] : Integration\StorageType::Undefined;
		if(!Integration\StorageType::isDefined($activity['STORAGE_TYPE_ID']))
		{
			$activity['STORAGE_TYPE_ID'] = CCrmActivity::GetDefaultStorageTypeID();
		}

		$activity['FILES'] = $activity['WEBDAV_ELEMENTS'] = $activity['DISK_FILES'] = array();

		CCrmActivity::PrepareStorageElementIDs($activity);
		CCrmActivity::PrepareStorageElementInfo($activity);

		//settings
		$activity['SETTINGS'] = (isset($activity['SETTINGS']) && $activity['SETTINGS'] !== '' && is_string($activity['SETTINGS']))
			? unserialize($activity['SETTINGS']) : array();

		//other
		if(isset($activity['DEADLINE']) && CCrmDateTimeHelper::IsMaxDatabaseDate($activity['DEADLINE']))
		{
			$activity['DEADLINE'] = '';
		}
	}

	public function executeComponent()
	{
		if (!Main\Loader::includeModule('crm'))
		{
			ShowError(Loc::getMessage('CRM_MODULE_NOT_INSTALLED'));
			return;
		}

		if (!Main\Loader::includeModule('calendar'))
		{
			ShowError(Loc::getMessage('CALENDAR_MODULE_NOT_INSTALLED'));
			return;
		}

		$action = $this->getAction();

		switch ($action)
		{
			case 'EDIT':
				$this->executeEditAction();
				break;
			default:
				$this->executeViewAction();
				break;
		}
	}

	protected function executeEditAction()
	{
		$activityId = $this->getActivityId();
		$calendarEventId = $this->getCalendarEventId();
		$isNew = false;
		$activity = $error = null;

		if ($activityId > 0)
			$activity = CCrmActivity::GetByID($activityId, false);
		elseif ($calendarEventId > 0)
			$activity = CCrmActivity::GetByCalendarEventId($calendarEventId, false);
		else
		{
			$isNew = true;
			$activity = array(
				'OWNER_ID' => $this->getOwnerId(),
				'OWNER_TYPE_ID' => $this->getOwnerTypeId(),
				'RESPONSIBLE_ID' => CCrmSecurityHelper::GetCurrentUserID(),
				'TYPE_ID' => $this->getActivityType(),
				'PROVIDER_ID' => $this->getProviderId(),
				'PROVIDER_TYPE_ID' => $this->getProviderTypeId(),
			);

			if($this->getAssociatedEntityId() > 0)
				$activity['ASSOCIATED_ENTITY_ID'] = $this->getAssociatedEntityId();
		}

		if (empty($activity))
			$error = Loc::getMessage('CRM_ACTIVITY_PLANNER_NO_ACTIVITY');

		$provider = $activity ? CCrmActivity::GetActivityProvider($activity) : null;
		
		if (!$provider)
			$error = Loc::getMessage('CRM_ACTIVITY_PLANNER_NO_PROVIDER');

		if (!$error && !$isNew && !CCrmActivity::CheckUpdatePermission($activity['OWNER_TYPE_ID'], $activity['OWNER_ID']))
			$error = Loc::getMessage('CRM_ACTIVITY_PLANNER_NO_UPDATE_PERMISSION');

		if ($error)
		{
			$this->arResult['ERROR'] = $error;
			$this->includeComponentTemplate('error');
			return;
		}

		$this->arResult['DURATION_VALUE'] = 1;
		$this->arResult['DURATION_TYPE'] = CCrmActivityNotifyType::Hour;

		if ($isNew)
		{
			$provider::fillDefaultActivityFields($activity);

			$defaults = \CUserOptions::GetOption('crm.activity.planner', 'defaults', array());
			if (isset($defaults['notify']) && isset($defaults['notify'][$provider::getId()]))
			{
				$activity['NOTIFY_VALUE'] = (int)$defaults['notify'][$provider::getId()]['value'];
				$activity['NOTIFY_TYPE'] = (int)$defaults['notify'][$provider::getId()]['type'];
			}

			if (isset($defaults['duration']) && isset($defaults['duration'][$provider::getId()]))
			{
				$this->arResult['DURATION_VALUE'] = (int)$defaults['duration'][$provider::getId()]['value'];
				$this->arResult['DURATION_TYPE'] = (int)$defaults['duration'][$provider::getId()]['type'];
			}

			$fromId = $this->getFromActivityId();
			if ($fromId > 0)
			{
				$fromActivity = CCrmActivity::GetByID($fromId);
				if ($fromActivity)
				{
					$activity['SUBJECT'] = $fromActivity['SUBJECT'];
					$activity['PRIORITY'] = $fromActivity['PRIORITY'];
					if ($activity['TYPE_ID'] == CCrmActivityType::Call || $activity['TYPE_ID'] == CCrmActivityType::Meeting)
					{
						$activity['DESCRIPTION'] = $fromActivity['DESCRIPTION'];
					}
					if ($activity['TYPE_ID'] == CCrmActivityType::Meeting)
						$activity['LOCATION'] = $fromActivity['LOCATION'];

					$fromComm = CCrmActivity::GetCommunications($fromId);
					if (is_array($fromComm))
					{
						$activity['COMMUNICATIONS'] = array();
						$commType = $provider::getCommunicationType($activity['PROVIDER_TYPE_ID']);

						foreach ($fromComm as $comm)
						{
							if ($comm['TYPE'] === $commType)
								$activity['COMMUNICATIONS'][] = $comm;
						}
					}
				}
			}
		}
		$this->getActivityAdditionalData($activityId, $activity, $provider);

		$this->arResult['ACTIVITY'] = $activity;
		$this->arResult['PROVIDER'] = $provider;
		$this->arResult['DESTINATION_ENTITIES'] = $this->getDestinationEntities($activity);
		$this->arResult['COMMUNICATIONS_DATA'] = $this->getCommunicationsData($activity['COMMUNICATIONS']);
		$this->arResult['PLANNER_ID'] = $this->getPlannerId();

		$options = \CUserOptions::GetOption('crm.activity.planner', 'edit', array());
		$this->arResult['DETAIL_MODE'] = (isset($options['view_mode']) && $options['view_mode'] === 'detail');
		$this->arResult['ADDITIONAL_MODE'] = (isset($options['additional_mode']) && $options['additional_mode'] === 'open');
		
		$this->includeComponentTemplate('edit');
	}

	protected function executeViewAction()
	{
		$userId = CCrmSecurityHelper::GetCurrentUserID();

		$activityId = $this->getActivityId();
		$calendarEventId = $this->getCalendarEventId();

		$activity = $error = null;

		if ($activityId > 0)
			$activity = CCrmActivity::GetByID($activityId, false);
		elseif ($calendarEventId > 0)
			$activity = CCrmActivity::GetByCalendarEventId($calendarEventId, false);

		if (empty($activity))
			$error = Loc::getMessage('CRM_ACTIVITY_PLANNER_NO_ACTIVITY');

		$provider = $activity ? CCrmActivity::GetActivityProvider($activity) : null;

		if (!$provider)
			$error = Loc::getMessage('CRM_ACTIVITY_PLANNER_NO_PROVIDER');

		if (!$error
			&& $userId !== (int)$activity['RESPONSIBLE_ID']
			&& !CCrmActivity::CheckReadPermission($activity['OWNER_TYPE_ID'], $activity['OWNER_ID'])
		)
		{
			$error = Loc::getMessage('CRM_ACTIVITY_PLANNER_NO_READ_PERMISSION');
		}

		if ($error)
		{
			$this->arResult['ERROR'] = $error;
			$this->includeComponentTemplate('error');
			return;
		}

		$this->getActivityAdditionalData($activityId, $activity);

		if ($activity['COMPLETED'] === 'N' && $provider::canCompleteOnView($activity['PROVIDER_TYPE_ID']))
		{
			$completeResult = \CCrmActivity::Complete($activity['ID']);
			if ($completeResult)
				$activity['COMPLETED'] = 'Y';
		}

		$activity['DESCRIPTION_HTML'] = $this->makeDescriptionHtml(
			$activity['DESCRIPTION'],
			$activity['DESCRIPTION_TYPE']
		);
		$activity['COMMUNICATIONS'] = $this->prepareCommunicationsForView($activity['COMMUNICATIONS']);;

		$this->arResult['COMMUNICATIONS'] = $activity['COMMUNICATIONS'];
		$this->arResult['PROVIDER'] = $provider;
		$this->arResult['ACTIVITY'] = $activity;

		$this->arResult['TYPE_ICON'] = $this->getTypeIcon($activity);
		$this->arResult['FILES_LIST'] = $this->prepareFilesForView($activity);

		$this->arResult['RESPONSIBLE_NAME'] = CCrmViewHelper::GetFormattedUserName($activity['RESPONSIBLE_ID'], $this->arParams['NAME_TEMPLATE']);
		$this->arResult['RESPONSIBLE_URL'] = CComponentEngine::MakePathFromTemplate(
				'/company/personal/user/#user_id#/',
				array('user_id' => $activity['RESPONSIBLE_ID'])
			);

		$this->arResult['DOC_BINDINGS'] = array();
		foreach ($activity['BINDINGS'] as $binding)
		{
			if ($this->isDocument($binding['OWNER_TYPE_ID']))
			{
				$this->arResult['DOC_BINDINGS'][] = array(
					'DOC_NAME' => CCrmOwnerType::GetDescription($binding['OWNER_TYPE_ID']),
					'CAPTION' => CCrmOwnerType::GetCaption($binding['OWNER_TYPE_ID'], $binding['OWNER_ID']),
					'URL' => CCrmOwnerType::GetShowUrl($binding['OWNER_TYPE_ID'], $binding['OWNER_ID'])
				);
			}
		}

		$ownerID = (int)$activity['OWNER_ID'];
		$ownerTypeID = (int)$activity['OWNER_TYPE_ID'];

		if(!$ownerID && !$ownerTypeID || \CCrmActivity::CheckUpdatePermission($ownerTypeID, $ownerID))
		{
			if ($provider::isTypeEditable($activity['PROVIDER_TYPE_ID'], $activity['DIRECTION']))
			{
				$this->arResult['IS_EDITABLE'] = true;
			}
		}

		$this->includeComponentTemplate('view');
	}

	private function isDocument($entityTypeId)
	{
		$entityTypeId = (int)$entityTypeId;
		return $entityTypeId === CCrmOwnerType::Deal || $entityTypeId === CCrmOwnerType::Invoice || $entityTypeId === CCrmOwnerType::Quote;
	}

	private function getTypeIcon($activity)
	{
		if ($activity['TYPE_ID'] == \CCrmActivityType::Call)
		{
			return $activity['DIRECTION'] == \CCrmActivityDirection::Outgoing ? 'call-outgoing' : 'call';
		}
		if ($activity['TYPE_ID'] == \CCrmActivityType::Meeting)
			return 'meet';
		if ($activity['TYPE_ID'] == \CCrmActivityType::Email)
		{
			return $activity['DIRECTION'] == \CCrmActivityDirection::Outgoing ? 'mail' : 'mail-send';
		}

		if ($activity['PROVIDER_ID'] == 'CRM_EXTERNAL_CHANNEL')
			return 'onec';
		if ($activity['PROVIDER_ID'] == 'CRM_LF_MESSAGE')
			return 'live-feed';

		if ($activity['PROVIDER_ID'] == 'CRM_WEBFORM')
			return 'form';

		if ($activity['PROVIDER_ID'] == 'IMOPENLINES_SESSION')
			return 'chat';

		if ($activity['PROVIDER_ID'] == 'VISIT_TRACKER')
			return 'visit-tracker';

		if ($activity['PROVIDER_ID'] == 'CRM_REQUEST')
			return 'deal-request';

		if ($activity['PROVIDER_ID'] == 'CALL_LIST')
			return 'call-list';

		return '';
	}

	private function prepareFilesForView(array $activity)
	{
		$result = array();

		if(!empty($activity['FILES']))
		{
			foreach($activity['FILES'] as $file)
			{
				$result[] = array(
					'fileName' => $file['fileName'],
					'viewURL' => $file['fileURL']
				);
			}
		}
		elseif(!empty($activity['WEBDAV_ELEMENTS']))
		{
			foreach($activity['WEBDAV_ELEMENTS'] as $element)
			{
				$result[] = array(
					'fileName' => $element['NAME'],
					'viewURL' => $element['VIEW_URL']
				);
			}
		}
		elseif(!empty($activity['DISK_FILES']))
		{
			foreach($activity['DISK_FILES'] as $file)
			{
				$result[] = array(
					'fileName' => $file['NAME'],
					'viewURL' => $file['VIEW_URL']
				);
			}
		}

		return $result;
	}

	private function prepareCommunicationsForView($communications)
	{
		$result = array();
		$companyTypes = CCrmStatus::GetStatusListEx('COMPANY_TYPE');

		foreach($communications as $communication)
		{
			CCrmActivity::PrepareCommunicationInfo($communication);

			$entityTypeId = (int)$communication['ENTITY_TYPE_ID'];
			$entityId = (int)$communication['ENTITY_ID'];

			$communication['VIEW_URL'] = CCrmOwnerType::GetShowUrl($entityTypeId, $entityId);
			$communication['IMAGE_URL'] = '';
			$communication['FM'] = array();

			if ($communication['TYPE'] !== '')
			{
				$communication['FM'][$communication['TYPE']] = array(array(
					'VALUE' => $communication['VALUE'],
					'VALUE_TYPE' => 'WORK'
				));
			}

			if ($entityTypeId === CCrmOwnerType::Contact)
			{
				$iterator = CCrmContact::GetListEx(
					array(),
					array('ID' => $entityId),
					false,
					false,
					array('PHOTO', 'POST')
				);

				$contact = $iterator ? $iterator->fetch() : null;

				if ($contact)
				{
					if ($contact['PHOTO'] > 0)
					{
						$file = new CFile();
						$fileInfo = $file->ResizeImageGet(
							$contact['PHOTO'],
							array('width' => 38, 'height' => 38),
							BX_RESIZE_IMAGE_EXACT
						);
						$communication['IMAGE_URL'] = is_array($fileInfo) && isset($fileInfo['src']) ? $fileInfo['src'] : '';
					}

					if ($contact['POST'])
						$communication['DESCRIPTION'] = $contact['POST'];
				}
			}
			elseif ($entityTypeId === CCrmOwnerType::Company)
			{
				$iterator = CCrmCompany::GetListEx(
					array(),
					array('ID' => $entityId),
					false,
					false,
					array('LOGO', 'COMPANY_TYPE')
				);

				$company = $iterator ? $iterator->fetch() : null;

				if ($company)
				{
					if ($company['LOGO'] > 0)
					{
						$file = new CFile();
						$fileInfo = $file->ResizeImageGet(
							$company['LOGO'],
							array('width' => 38, 'height' => 38),
							BX_RESIZE_IMAGE_EXACT
						);
						$communication['IMAGE_URL'] = is_array($fileInfo) && isset($fileInfo['src']) ? $fileInfo['src'] : '';
					}

					if ($company['COMPANY_TYPE'] && isset($companyTypes[$company['COMPANY_TYPE']]))
					{
						$communication['DESCRIPTION'] = $companyTypes[$company['COMPANY_TYPE']];
					}
				}
			}

			if ($entityId > 0)
			{
				$multiFieldsIterator = CCrmFieldMulti::GetList(
					array('ID' => 'asc'),
					array('ENTITY_ID' => CCrmOwnerType::ResolveName($entityTypeId), 'ELEMENT_ID' => $entityId)
				);
				while($arMultiFields = $multiFieldsIterator->fetch())
				{
					$communication['FM'][$arMultiFields['TYPE_ID']][$arMultiFields['ID']] = array(
						'VALUE' => $arMultiFields['VALUE'],
						'VALUE_TYPE' => $arMultiFields['VALUE_TYPE']
					);
				}
			}

			$result[] = $communication;
		}

		return $result;
	}

	// Helpers
	private function getDestinationEntities($activity)
	{
		$result = array(
			'responsible' => array(
				array(
					'id' => 'U'.$activity['RESPONSIBLE_ID'],
					'entityId' => $activity['RESPONSIBLE_ID'],
					'name' => CCrmViewHelper::GetFormattedUserName($activity['RESPONSIBLE_ID'], $this->arParams['NAME_TEMPLATE']),
					'entityType' => 'users'
				)
			)
		);

		if ((int)$activity['OWNER_TYPE_ID'] === CCrmOwnerType::Deal)
		{
			$result['deal'] = array(
				array(
					'id' => 'D'.$activity['OWNER_ID'],
					'entityId' => $activity['OWNER_ID'],
					'name' => CCrmOwnerType::GetCaption($activity['OWNER_TYPE_ID'], $activity['OWNER_ID']),
					'entityType' => 'deals'
				)
			);
		}

		return $result;
	}

	public static function getDestinationData($params)
	{
		$type = isset($params['type']) ? $params['type'] : 'responsible';
		$result = array('LAST' => array());

		if ($type == 'responsible')
		{
			if (!Main\Loader::includeModule('socialnetwork'))
				return array();

			$arStructure = CSocNetLogDestination::GetStucture(array());
			$result['DEPARTMENT'] = $arStructure['department'];
			$result['DEPARTMENT_RELATION'] = $arStructure['department_relation'];
			$result['DEPARTMENT_RELATION_HEAD'] = $arStructure['department_relation_head'];

			$result['DEST_SORT'] = CSocNetLogDestination::GetDestinationSort(array(
				"DEST_CONTEXT" => "CRM_ACTIVITY",
			));

			CSocNetLogDestination::fillLastDestination(
				$result['DEST_SORT'],
				$result['LAST']
			);

			$destUser = array();
			foreach ($result["LAST"]["USERS"] as $value)
			{
				$destUser[] = str_replace("U", "", $value);
			}

			$result["USERS"] = \CSocNetLogDestination::getUsers(array("id" => $destUser));
		}
		elseif ($type == 'deal')
		{
			if (!Main\Loader::includeModule('crm'))
				return array();
			$deals = static::getDestinationDealEntities(array(), 12, array('ID' => 'DESC'));

			$lastDeals = array();
			foreach ($deals as $deal)
			{
				$lastDeals[$deal['id']] = $deal['id'];
			}

			$result['DEALS'] = $deals;
			$result['LAST']['DEALS'] = $lastDeals;
		}

		return $result;
	}

	public static function searchDestinationDeals($data)
	{
		$result = new Main\Result();

		if (!Main\Loader::includeModule('crm'))
		{
			$result->addError(new Main\Error('module "crm" is not installed.'));
			return $result;
		}

		$search = $data['SEARCH'];
		$searchConverted = (!empty($data['SEARCH_CONVERTED']) ? $data['SEARCH_CONVERTED'] : false);
		$deals = static::getDestinationDealEntities(array('%TITLE' => $search), 20);

		if (
			empty($deals)
			&& $searchConverted
			&& $search != $searchConverted
		)
		{
			$deals = static::getDestinationDealEntities(array('%TITLE' => $searchConverted), 20);
			$searchResults['SEARCH'] = $searchConverted;
		}

		$searchResults['DEALS'] = $deals;
		$searchResults['USERS'] = array();

		return $searchResults;
	}

	private static function getDestinationDealEntities($filter, $limit, $order = array())
	{
		$nameTemplate = CSite::GetNameFormat(false);
		$result = array();
		$iterator = CCrmDeal::GetListEx(
			$arOrder = $order,
			$arFilter = $filter,
			$arGroupBy = false,
			$arNavStartParams = array('nTopCount' => $limit),
			$arSelectFields = array('ID', 'TITLE', 'COMPANY_TITLE', 'CONTACT_NAME', 'CONTACT_SECOND_NAME', 'CONTACT_LAST_NAME')
		);

		while ($iterator && ($arDeal = $iterator->fetch()))
		{
			$arDesc = array();
			if ($arDeal['COMPANY_TITLE'] != '')
				$arDesc[] = $arDeal['COMPANY_TITLE'];
			$arDesc[] = CUser::FormatName(
				$nameTemplate,
				array(
					'LOGIN' => '',
					'NAME' => $arDeal['CONTACT_NAME'],
					'SECOND_NAME' => $arDeal['CONTACT_SECOND_NAME'],
					'LAST_NAME' => $arDeal['CONTACT_LAST_NAME']
				),
				false, false
			);

			$result['D'.$arDeal['ID']] = array(
				'id' => 'D'.$arDeal['ID'],
				'entityId' => $arDeal['ID'],
				'entityType' => 'deals',
				'name' => htmlspecialcharsbx($arDeal['TITLE']),
				'desc' => htmlspecialcharsbx(implode(', ', $arDesc))
			);
		}

		return $result;
	}

	private function getCommunicationsData(array $communications)
	{
		$result = array();

		foreach($communications as $arComm)
		{
			CCrmActivity::PrepareCommunicationInfo($arComm);
			$result[] = array(
				'id' => $arComm['ID'],
				'type' => $arComm['TYPE'],
				'value' => $arComm['VALUE'],
				'entityId' => $arComm['ENTITY_ID'],
				'entityType' => CCrmOwnerType::ResolveName($arComm['ENTITY_TYPE_ID']),
				'entityTitle' => $arComm['TITLE'],
				'entityUrl' => CCrmOwnerType::GetShowUrl($arComm['ENTITY_TYPE_ID'], $arComm['ENTITY_ID'])
			);
		}

		return $result;
	}

	public static function saveActivity($data, $userID, $siteID)
	{
		if (!empty($data['dealId']))
		{
			$data['ownerType'] = 'DEAL';
			$data['ownerId'] = $data['dealId'];
		}

		if (empty($data['ownerType']) && empty($data['ownerId']) && !empty($data['communication']))
		{
			$commData = isset($data['communication']) ? $data['communication'] : array();
			$data['ownerType'] = isset($commData['entityType']) ? strtoupper(strval($commData['entityType'])) : '';
			$data['ownerId'] = isset($commData['entityId']) ? intval($commData['entityId']) : 0;
		}

		$result = new Main\Result();

		if(count($data) == 0)
		{
			$result->addError(new Main\Error('SOURCE DATA ARE NOT FOUND!'));
			return $result;
		}

		$ID = isset($data['id']) ? intval($data['id']) : 0;
		$typeID = isset($data['type']) ? intval($data['type']) : CCrmActivityType::Activity;
		$providerId = isset($data['providerId']) ? strtoupper(strval($data['providerId'])) : '';
		$providerTypeId = isset($data['providerTypeId']) ? strtoupper(strval($data['providerTypeId'])) : '';

		$activity = array(
			'TYPE_ID' => $typeID,
			'PROVIDER_ID' => $providerId,
			'PROVIDER_TYPE_ID' => $providerTypeId
		);

		if($ID > 0)
		{
			$activity = CCrmActivity::GetByID($ID, false);
			if(!$activity)
			{
				$result->addError(new Main\Error('IS NOT EXISTS!'));
				return $result;
			}
		}

		$provider = CCrmActivity::GetActivityProvider($activity);
		if(!$provider)
		{
			$result->addError(new Main\Error('Provider not found!'));
			return $result;
		}

		$ownerTypeName = isset($data['ownerType']) ? strtoupper(strval($data['ownerType'])) : '';
		if($provider::checkOwner() && $ownerTypeName === '')
		{
			$result->addError(new Main\Error('OWNER TYPE IS NOT DEFINED!'));
			return $result;
		}

		$ownerTypeID = CCrmOwnerType::ResolveID($ownerTypeName);
		if($provider::checkOwner() && !CCrmOwnerType::IsDefined($ownerTypeID))
		{
			$result->addError(new Main\Error('OWNER TYPE IS NOT SUPPORTED!'));
			return $result;
		}

		$ownerId = isset($data['ownerId']) ? intval($data['ownerId']) : 0;
		if($provider::checkOwner() && $ownerId <= 0)
		{
			$result->addError(new Main\Error('OWNER ID IS NOT DEFINED!'));
			return $result;
		}

		if($provider::checkOwner() && !CCrmActivity::CheckUpdatePermission($ownerTypeID, $ownerId))
		{
			$result->addError(new Main\Error('Access denied!'));
			return $result;
		}

		$responsibleID = isset($data['responsibleId']) ? intval($data['responsibleId']) : 0;

		if($userID <= 0)
		{
			$userID = CCrmOwnerType::GetResponsibleID($ownerTypeID, $ownerId, false);
			if($userID <= 0)
			{
				$result->addError(new Main\Error('Responsible not found!'));
				return $result;
			}
		}

		$start = isset($data['startTime']) ? strval($data['startTime']) : '';
		$end = isset($data['endTime']) ? strval($data['endTime']) : '';
		if($start === '')
		{
			$start = ConvertTimeStamp(time() + CTimeZone::GetOffset(), 'FULL', $siteID);
		}

		if($end === '')
		{
			$end = $start;
		}

		$descr = isset($data['description']) ? strval($data['description']) : '';
		$priority = isset($data['important']) ? CCrmActivityPriority::High : CCrmActivityPriority::Medium;
		$location = isset($data['location']) ? strval($data['location']) : '';

		$direction = isset($data['direction']) ? intval($data['direction']) : CCrmActivityDirection::Undefined;

		// Communications
		$commData = isset($data['communication']) ? $data['communication'] : array();
		$commID = isset($commData['id']) ? intval($commData['id']) : 0;
		$commEntityType = isset($commData['entityType']) ? strtoupper(strval($commData['entityType'])) : '';
		$commEntityID = isset($commData['entityId']) ? intval($commData['entityId']) : 0;
		$commType = isset($commData['type']) ? strtoupper(strval($commData['type'])) : '';
		$commValue = isset($commData['value']) ? strval($commData['value']) : '';

		$subject = isset($data['subject']) ? (string)$data['subject'] : '';
		if($subject === '')
		{
			$arCommInfo = array(
				'ENTITY_ID' => $commEntityID,
				'ENTITY_TYPE_ID' => CCrmOwnerType::ResolveID($commEntityType)
			);
			CCrmActivity::PrepareCommunicationInfo($arCommInfo);

			$subject = $provider::generateSubject($activity['PROVIDER_ID'], $direction, array(
				'#DATE#'=> $start,
				'#TITLE#' => isset($arCommInfo['TITLE']) ? $arCommInfo['TITLE'] : $commValue,
				'#COMMUNICATION#' => $commValue
			));
		}

		$arFields = array(
			'PROVIDER_ID' => $providerId,
			'PROVIDER_TYPE_ID' => $providerTypeId,
			'TYPE_ID' =>  $typeID,
			'SUBJECT' => $subject,
			'COMPLETED' => isset($data['completed']) && $data['completed'] === 'Y' ? 'Y' : 'N',
			'PRIORITY' => $priority,
			'DESCRIPTION' => $descr,
			'DESCRIPTION_TYPE' => CCrmContentType::PlainText,
			'LOCATION' => $location,
			'DIRECTION' => $direction,
			'NOTIFY_TYPE' => CCrmActivityNotifyType::None,
			'SETTINGS' => array()
		);

		$arBindings = array(
			"{$ownerTypeName}_{$ownerId}" => array(
				'OWNER_TYPE_ID' => $ownerTypeID,
				'OWNER_ID' => $ownerId
			)
		);

		$arFields['NOTIFY_TYPE'] = isset($data['notifyType']) ? (int)$data['notifyType'] : CCrmActivityNotifyType::Min;
		$arFields['NOTIFY_VALUE'] = isset($data['notifyValue']) ? (int)$data['notifyValue'] : 15;

		// Communications
		$arComms = array();
		if($commEntityID <= 0 && $commType === 'PHONE' && $ownerTypeName !== 'DEAL')
		{
			// Communication entity ID is 0 (processing of new communications)
			// Communication type must present it determines TYPE_ID (is only 'PHONE' in current context)
			// Deal does not have multi fields.

			$fieldMulti = new CCrmFieldMulti();
			$arFieldMulti = array(
				'ENTITY_ID' => $ownerTypeName,
				'ELEMENT_ID' => $ownerId,
				'TYPE_ID' => 'PHONE',
				'VALUE_TYPE' => 'WORK',
				'VALUE' => $commValue
			);

			$fieldMultiID = $fieldMulti->Add($arFieldMulti);
			if($fieldMultiID > 0)
			{
				$commEntityType = $ownerTypeName;
				$commEntityID = $ownerId;
			}
		}

		if($commEntityType !== '')
		{
			$arComms[] = array(
				'ID' => $commID,
				'TYPE' => $commType,
				'VALUE' => $commValue,
				'ENTITY_ID' => $commEntityID,
				'ENTITY_TYPE_ID' => CCrmOwnerType::ResolveID($commEntityType)
			);

			$bindingKey = $commEntityID > 0 ? "{$commEntityType}_{$commEntityID}" : uniqid("{$commEntityType}_");
			if(!isset($arBindings[$bindingKey]))
			{
				$arBindings[$bindingKey] = array(
					'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($commEntityType),
					'OWNER_ID' => $commEntityID
				);
			}
		}

		$isNew = $ID <= 0;
		$arPreviousFields = $ID > 0 ? CCrmActivity::GetByID($ID) : array();

		$storageTypeID = isset($data['storageTypeID']) ? intval($data['storageTypeID']) : Integration\StorageType::Undefined;
		if($storageTypeID === Integration\StorageType::Undefined
			|| !Integration\StorageType::IsDefined($storageTypeID))
		{
			if($isNew)
			{
				$storageTypeID = CCrmActivity::GetDefaultStorageTypeID();
			}
			else
			{
				$storageTypeID = CCrmActivity::GetStorageTypeID($ID);
				if($storageTypeID === Integration\StorageType::Undefined)
				{
					$storageTypeID = CCrmActivity::GetDefaultStorageTypeID();
				}
			}
		}

		$arFields['STORAGE_TYPE_ID'] = $storageTypeID;
		$disableStorageEdit = isset($data['disableStorageEdit']) && strtoupper($data['disableStorageEdit']) === 'Y';
		if(!$disableStorageEdit)
		{
			if($storageTypeID === Integration\StorageType::File)
			{
				$arPermittedFiles = array();
				$arUserFiles = isset($data['files']) && is_array($data['files']) ? $data['files'] : array();
				if(!empty($arUserFiles) || !$isNew)
				{
					$arPreviousFiles = array();
					if(!$isNew)
					{
						CCrmActivity::PrepareStorageElementIDs($arPreviousFields);
						$arPreviousFiles = $arPreviousFields['STORAGE_ELEMENT_IDS'];
						if(is_array($arPreviousFiles) && !empty($arPreviousFiles))
						{
							$arPermittedFiles = array_intersect($arUserFiles, $arPreviousFiles);
						}
					}

					$uploadControlCID = isset($data['uploadControlCID']) ? strval($data['uploadControlCID']) : '';
					if($uploadControlCID !== '' && isset($_SESSION["MFI_UPLOADED_FILES_{$uploadControlCID}"]))
					{
						$uploadedFiles = $_SESSION["MFI_UPLOADED_FILES_{$uploadControlCID}"];
						if(!empty($uploadedFiles))
						{
							$arPermittedFiles = array_merge(
								array_intersect($arUserFiles, $uploadedFiles),
								$arPermittedFiles
							);
						}
					}

					$arFields['STORAGE_ELEMENT_IDS'] = $arPermittedFiles;
				}
			}
			elseif($storageTypeID === Integration\StorageType::WebDav || $storageTypeID === Integration\StorageType::Disk)
			{
				$fileKey = $storageTypeID === Integration\StorageType::Disk ? 'diskfiles' : 'webdavelements';
				$arFileIDs = isset($data[$fileKey]) && is_array($data[$fileKey]) ? $data[$fileKey] : array();
				if(!empty($arFileIDs) || !$isNew)
				{
					$arFields['STORAGE_ELEMENT_IDS'] = Bitrix\Crm\Integration\StorageManager::filterFiles($arFileIDs, $storageTypeID, $userID);
				}
			}
		}

		//TIME FIELDS
		$arFields['START_TIME'] = $start;
		$arFields['END_TIME'] = $end;

		if($isNew)
		{
			$arFields['OWNER_ID'] = $ownerId;
			$arFields['OWNER_TYPE_ID'] = $ownerTypeID;
			$arFields['RESPONSIBLE_ID'] = $responsibleID > 0 ? $responsibleID : $userID;

			$arFields['BINDINGS'] = array_values($arBindings);

			$providerResult = $provider::postForm($arFields, $data);
			if(!$providerResult->isSuccess())
			{
				$result->addErrors($providerResult->getErrors());
				return $result;
			}

			$ID = CCrmActivity::Add($arFields, false, true, array('REGISTER_SONET_EVENT' => true));
			if($ID <= 0)
			{
				$result->addError(new Main\Error(CCrmActivity::GetLastErrorMessage()));
				return $result;
			}
			$provider::saveAdditionalData($ID, $arFields);

			//Region automation trigger
			if (
				$arFields['TYPE_ID'] === \CCrmActivityType::Call
				&& $arFields['DIRECTION'] === \CCrmActivityDirection::Incoming
			)
			{
				\Bitrix\Crm\Automation\Trigger\CallTrigger::execute($arFields['BINDINGS'], $arFields);
			}
			//end region
		}
		else
		{
			$dbResult = CCrmActivity::GetList(
				array(),
				array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('OWNER_ID', 'OWNER_TYPE_ID', 'START_TIME', 'END_TIME')
			);
			$presentFields = is_object($dbResult) ? $dbResult->Fetch() : null;
			if(!is_array($presentFields))
			{
				$result->addError(new Main\Error('COULD NOT FIND ACTIVITY!'));
				return $result;
			}

			$presentOwnerTypeID = intval($presentFields['OWNER_TYPE_ID']);
			$presentOwnerID = intval($presentFields['OWNER_ID']);
			$ownerChanged =  ($presentOwnerTypeID !== $ownerTypeID || $presentOwnerID !== $ownerId);

			$arFields['OWNER_ID'] = $ownerId;
			$arFields['OWNER_TYPE_ID'] = $ownerTypeID;

			if($responsibleID > 0)
			{
				$arFields['RESPONSIBLE_ID'] = $responsibleID;
			}

			//Merge new bindings with old bindings
			$presetCommunicationKeys = array();
			$presetCommunications = CCrmActivity::GetCommunications($ID);
			foreach($presetCommunications as $arComm)
			{
				$commEntityTypeName = CCrmOwnerType::ResolveName($arComm['ENTITY_TYPE_ID']);
				$commEntityID = $arComm['ENTITY_ID'];
				$presetCommunicationKeys["{$commEntityTypeName}_{$commEntityID}"] = true;
			}

			$presentBindings = CCrmActivity::GetBindings($ID);
			foreach($presentBindings as &$binding)
			{
				$bindingOwnerID = (int)$binding['OWNER_ID'];
				$bindingOwnerTypeID = (int)$binding['OWNER_TYPE_ID'];
				$bindingOwnerTypeName = CCrmOwnerType::ResolveName($bindingOwnerTypeID);
				$bindingKey = "{$bindingOwnerTypeName}_{$bindingOwnerID}";

				//Skip present present owner if it is changed
				if($ownerChanged && $presentOwnerTypeID === $bindingOwnerTypeID && $presentOwnerID === $bindingOwnerID)
				{
					continue;
				}

				//Skip present communications - new communications already are in bindings
				if(isset($presetCommunicationKeys[$bindingKey]))
				{
					continue;
				}

				$arBindings[$bindingKey] = array(
					'OWNER_TYPE_ID' => $bindingOwnerTypeID,
					'OWNER_ID' => $bindingOwnerID
				);
			}
			unset($binding);
			$arFields['BINDINGS'] = array_values($arBindings);

			$providerResult = $provider::postForm($arFields, $data);
			if (!$providerResult->isSuccess())
			{
				$result->addErrors($providerResult->getErrors());
				return $result;
			}
			if(!CCrmActivity::Update($ID, $arFields, false, true, array('REGISTER_SONET_EVENT' => true)))
			{
				$result->addError(new Main\Error(CCrmActivity::GetLastErrorMessage()));
				return $result;
			}

			$provider::saveAdditionalData($ID, $arFields);
		}

		CCrmActivity::SaveCommunications($ID, $arComms, $arFields, !$isNew, false);

		if($isNew)
		{
			$defaults = \CUserOptions::GetOption('crm.activity.planner', 'defaults', array());

			//save default notify settings
			if (!isset($defaults['notify']))
				$defaults['notify'] = array();

			$defaults['notify'][$provider::getId()] = array(
				'value' => $arFields['NOTIFY_VALUE'],
				'type' => $arFields['NOTIFY_TYPE']
			);

			//save default duration settings
			$durationValue = isset($data['durationValue']) ? (int)$data['durationValue'] : 0;
			$durationType = isset($data['durationType']) ? (int)$data['durationType'] : 0;
			if ($durationValue > 0 && $durationType > 0)
			{
				if (!isset($defaults['duration']))
					$defaults['duration'] = array();

				$defaults['duration'][$provider::getId()] = array(
					'value' => $durationValue,
					'type' => $durationType
				);
			}

			\CUserOptions::SetOption('crm.activity.planner', 'defaults', $defaults);
		}

		$result->setData(array(
			'ACTIVITY' => array(
				'ID' => $ID,
				'EDIT_URL' => CCrmOwnerType::GetEditUrl(CCrmOwnerType::Activity, $ID),
				'VIEW_URL' => CCrmOwnerType::GetShowUrl(CCrmOwnerType::Activity, $ID),
				'NEW' => ($isNew ? 'Y' : 'N')
			)
		));
		return $result;
	}

	private function getCrmEntityCommunications($entityTypeID, $entityID, $communicationType)
	{
		$communications = array();

		if ($entityTypeID === CCrmOwnerType::Lead || $entityTypeID === CCrmOwnerType::Contact)
		{
			$communications = $this->getCommunicationsFromFM($entityTypeID, $entityID, $communicationType);
		}
		elseif ($entityTypeID === CCrmOwnerType::Company)
		{
			$communications = $this->getCommunicationsFromFM($entityTypeID, $entityID, $communicationType);
			if (!$communications)
			{
				$communications = CCrmActivity::GetCompanyCommunications($entityID, $communicationType);
			}
		}
		elseif ($entityTypeID === CCrmOwnerType::Deal)
		{
			$entity = CCrmDeal::GetByID($entityID);
			if(!$entity)
			{
				return array();
			}

			$entityCompanyID =  isset($entity['COMPANY_ID']) ? intval($entity['COMPANY_ID']) : 0;
			//TODO: multiple contacts \Bitrix\Crm\Binding\DealContactTable::getDealBindings
			$entityContactID =  isset($entity['CONTACT_ID']) ? intval($entity['CONTACT_ID']) : 0;

			if($entityCompanyID > 0)
			{
				$communications = CCrmActivity::GetCompanyCommunications($entityCompanyID, $communicationType);
			}

			if (empty($communications) && $entityContactID > 0)
			{
				$communications = $this->getCommunicationsFromFM(CCrmOwnerType::Contact, $entityContactID, $communicationType);
			}

			if (empty($communications))
			{
				$communications = CCrmActivity::GetCommunicationsByOwner('DEAL', $entityID, $communicationType);
			}
		}

		return $communications;
	}

	private function getCommunicationsFromFM($entityTypeId, $entityId, $communicationType)
	{
		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeId);
		$communications = array();

		if ($communicationType !== '')
		{
			$iterator = CCrmFieldMulti::GetList(
				array('ID' => 'asc'),
				array('ENTITY_ID' => $entityTypeName,
					'ELEMENT_ID' => $entityId,
					'TYPE_ID' => $communicationType
				)
			);

			while ($row = $iterator->fetch())
			{
				if (empty($row['VALUE']))
					continue;

				$communications[] = array(
					'ENTITY_ID' => $entityId,
					'ENTITY_TYPE_ID' => $entityTypeId,
					'ENTITY_TYPE' => $entityTypeName,
					'TYPE' => $communicationType,
					'VALUE' => $row['VALUE'],
					'VALUE_TYPE' => $row['VALUE_TYPE']
				);
			}
		}
		else
		{
			$communications[] = array(
				'ENTITY_ID' => $entityId,
				'ENTITY_TYPE_ID' => $entityTypeId,
				'ENTITY_TYPE' => $entityTypeName,
				'TYPE' => $communicationType
			);
		}

		return $communications;
	}
	private function makeDescriptionHtml($description, $type)
	{
		$type = (int)$type;
		if($type === CCrmContentType::BBCode)
		{
			$bbCodeParser = new CTextParser();
			$html = $bbCodeParser->convertText($description);
		}
		elseif($type === CCrmContentType::Html)
		{
			//Already sanitaized
			$html = $description;
		}
		else//CCrmContentType::PlainText and other
		{
			$html = preg_replace("/[\r\n]+/".BX_UTF_PCRE_MODIFIER, "<br>", htmlspecialcharsbx($description));
		}
		
		return $html;
	}
}