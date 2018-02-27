<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Voximplant as VI;
use Bitrix\Crm\Activity\Provider;
use Bitrix\Main\Localization\Loc;

class CVoxImplantCrmHelper
{
	public static function GetCrmEntity($phone, $userId = 0, $checkPermission = true)
	{
		$userId = (int)$userId;
		if (!CModule::IncludeModule('crm') || strlen($phone) <= 0 || ($checkPermission && $userId == 0))
		{
			return false;
		}

		$arResult = false;

		if($checkPermission)
		{
			$searchParams = array(
				'USER_ID'=> $userId
			);
		}
		else
		{
			$searchParams = array();
		}

		$crm = CCrmSipHelper::findByPhoneNumber($phone, $searchParams);
		if ($crm)
		{
			if (isset($crm['CONTACT']))
			{
				$arResult['ENTITY_TYPE_NAME'] = CCrmOwnerType::ContactName;
				$arResult['ENTITY_TYPE'] = CCrmOwnerType::Contact;
				$arResult['ENTITY_ID'] = $crm['CONTACT'][0]['ID'];
				$arResult['ASSIGNED_BY_ID'] = $crm['CONTACT'][0]['ASSIGNED_BY_ID'];
			}
			else if (isset($crm['LEAD']))
			{
				$arResult['ENTITY_TYPE_NAME'] = CCrmOwnerType::LeadName;
				$arResult['ENTITY_TYPE'] = CCrmOwnerType::Lead;
				$arResult['ENTITY_ID'] = $crm['LEAD'][0]['ID'];
				$arResult['ASSIGNED_BY_ID'] = $crm['LEAD'][0]['ASSIGNED_BY_ID'];
			}
			else if (isset($crm['COMPANY']))
			{
				$arResult['ENTITY_TYPE_NAME'] = CCrmOwnerType::CompanyName;
				$arResult['ENTITY_TYPE'] = CCrmOwnerType::Company;
				$arResult['ENTITY_ID'] = $crm['COMPANY'][0]['ID'];
				$arResult['ASSIGNED_BY_ID'] = $crm['COMPANY'][0]['ASSIGNED_BY_ID'];
			}

			$arResult['BINDINGS'] = Array();
			if (isset($crm['CONTACT']) || isset($crm['COMPANY']))
			{
				if (isset($crm['CONTACT'][0]))
				{
					$arResult['BINDINGS'][] = array(
						'OWNER_ID' => $crm['CONTACT'][0]['ID'],
						'OWNER_TYPE_ID' => CCrmOwnerType::Contact
					);
				}
				if (isset($crm['COMPANY'][0]))
				{
					$arResult['BINDINGS'][] = array(
						'OWNER_ID' => $crm['COMPANY'][0]['ID'],
						'OWNER_TYPE_ID' => CCrmOwnerType::Company
					);
				}

				$deals = self::findDealsByPhone($phone);
				if ($deals)
				{
					$arResult['DEALS'] = $deals;

					$arResult['BINDINGS'][] = array(
						'OWNER_ID' => $deals[0]['ID'],
						'OWNER_TYPE_ID' => CCrmOwnerType::Deal
					);
				}
			}
			else if (isset($crm['LEAD'][0]))
			{
				$arResult['BINDINGS'][] = array(
					'OWNER_ID' => $crm['LEAD'][0]['ID'],
					'OWNER_TYPE_ID' => CCrmOwnerType::Lead
				);
			}
		}

		return $arResult;
	}
	
	public static function GetCrmEntities($phone, $userId = 0, $checkPermission = true)
	{
		$userId = (int)$userId;
		$result = array();

		if (!CModule::IncludeModule('crm') || strlen($phone) <= 0 || ($checkPermission && $userId == 0))
		{
			return $result;
		}

		if($checkPermission)
		{
			$searchParams = array(
				'USER_ID'=> $userId
			);
		}
		else
		{
			$searchParams = array();
		}


		$crm = CCrmSipHelper::findByPhoneNumber($phone, $searchParams);
		$types = array(CCrmOwnerType::ContactName, CCrmOwnerType::CompanyName, CCrmOwnerType::LeadName);
		if ($crm)
		{
			foreach ($types as $type)
			{
				if(is_array($crm[$type]))
				{
					foreach ($crm[$type] as $entity)
					{
						$result[] = array(
							'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($type),
							'OWNER_ID' => $entity['ID']
						);
					}
				}
			}
		}
		return $result;
	}

	public static function getCrmCard($entityType, $entityId)
	{
		global $APPLICATION;
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return false;

		ob_start();
		$APPLICATION->IncludeComponent('bitrix:crm.card.show',
			'',
			array(
				'ENTITY_TYPE' => $entityType,
				'ENTITY_ID' => (int)$entityId,
			)
		);
		return ob_get_clean();
	}

	public static function GetDataForPopup($callId, $phone, $userId = 0)
	{
		if (strlen($phone) <= 0 || !CModule::IncludeModule('crm'))
		{
			return false;
		}

		$dealStatuses = CCrmViewHelper::GetDealStageInfos();

		if ($userId > 0)
		{
			$findParams = array('USER_ID'=> $userId);
		}
		else
		{
			$findParams = array('ENABLE_EXTENDED_MODE'=> false);
		}

		$call = VI\CallTable::getByCallId($callId);
		$arResult = Array('FOUND' => 'N');
		$found = false;
		$entity = '';
		$entityData = Array();
		$entities = Array();

		if(isset($call['CRM_ENTITY_TYPE']) && isset($call['CRM_ENTITY_ID']))
		{
			$entityTypeId = CCrmOwnerType::ResolveID($call['CRM_ENTITY_TYPE']);
			$entityId = (int)$call['CRM_ENTITY_ID'];

			$entityFields = CCrmSipHelper::getEntityFields($entityTypeId, $entityId, $findParams);

			if(is_array($entityFields))
			{
				$found = true;
				$entity = $call['CRM_ENTITY_TYPE'];
				$entityData = $entityFields;
				$arResult = self::convertEntityFields($call['CRM_ENTITY_TYPE'], $entityData);
				$entities = array($entity);
				$crm = array(
					$entity => array(
						0 => $entityData
					)
				);
			}
		}

		if (!$found && $crm = CCrmSipHelper::findByPhoneNumber((string)$phone, $findParams))
		{
			if (isset($crm['CONTACT']))
			{
				$found = true;
				$entity = 'CONTACT';
				$entityData = $crm[$entity][0];
				$arResult = self::convertEntityFields($entity, $entityData);
			}
			else if (isset($crm['LEAD']))
			{
				$found = true;
				$entity = 'LEAD';
				$entityData = $crm[$entity][0];
				$arResult = self::convertEntityFields($entity, $entityData);
			}
			else if (isset($crm['COMPANY']))
			{
				$found = true;
				$entity = 'COMPANY';
				$entityData = $crm[$entity][0];
				$arResult = self::convertEntityFields($entity, $entityData);
			}

			if (isset($crm['CONTACT']) && isset($crm['COMPANY']))
			{
				$entities = array('CONTACT', 'COMPANY', 'LEAD');
			}
			else if (isset($crm['CONTACT']) && isset($crm['LEAD']) && !isset($crm['COMPANY']))
			{
				$entities = array('CONTACT', 'LEAD');
			}
			else if (isset($crm['LEAD']) && isset($crm['COMPANY']) && !isset($crm['CONTACT']))
			{
				$entities = array('LEAD', 'COMPANY');
			}
			else
			{
				$entities = array($entity);
			}
		}

		if(isset($call['CRM_ACTIVITY_ID']))
			$activityId = (int)$call['CRM_ACTIVITY_ID'];
		else
			$activityId = CCrmActivity::GetIDByOrigin('VI_'.$callId);

		if ($activityId)
		{
			$arResult['CURRENT_CALL_URL'] = CCrmOwnerType::GetEditUrl(CCrmOwnerType::Activity, $activityId);
			if($arResult['CURRENT_CALL_URL'] !== '')
			{
				$arResult['CURRENT_CALL_URL'] = CCrmUrlUtil::AddUrlParams($arResult['CURRENT_CALL_URL'], array("disable_storage_edit" => 'Y'));
			}
		}

		foreach ($entities as $entity)
		{
			if (isset($crm[$entity][0]['ACTIVITIES']))
			{
				foreach ($crm[$entity][0]['ACTIVITIES'] as $activity)
				{
					if ($activity['ID'] == $activityId)
						continue;

					$overdue = 'N';
					if (strlen($activity['DEADLINE']) > 0 && MakeTimeStamp($activity['DEADLINE']) < time())
					{
						$overdue = 'Y';
					}

					$arResult['ACTIVITIES'][$activity['ID']] = Array(
						'TITLE' => $activity['SUBJECT'],
						'DATE' => strlen($activity['DEADLINE']) > 0? $activity['DEADLINE']: $activity['END_TIME'],
						'OVERDUE' => $overdue,
						'URL' => $activity['SHOW_URL'],
					);
				}
				if (!empty($arResult['ACTIVITIES']))
				{
					$arResult['ACTIVITIES'] = array_values($arResult['ACTIVITIES']);
				}
			}

			if (isset($crm[$entity][0]['DEALS']))
			{
				foreach ($crm[$entity][0]['DEALS'] as $deal)
				{
					$opportunity = CCrmCurrency::MoneyToString($deal['OPPORTUNITY'], $deal['CURRENCY_ID']);
					if (strpos('&', $opportunity))
					{
						$opportunity = CCrmCurrency::MoneyToString($deal['OPPORTUNITY'], $deal['CURRENCY_ID'], '#').' '.$deal['CURRENCY_ID'];
					}
					$opportunity = str_replace('.00', '', $opportunity);

					$arResult['DEALS'][$deal['ID']] = Array(
						'ID' => $deal['ID'],
						'TITLE' => $deal['TITLE'],
						'STAGE' => $dealStatuses[$deal['STAGE_ID']]['NAME'],
						'STAGE_COLOR' => $dealStatuses[$deal['STAGE_ID']]['COLOR']? $dealStatuses[$deal['STAGE_ID']]['COLOR']: "#5fa0ce",
						'OPPORTUNITY' => $opportunity,
						'URL' => $deal['SHOW_URL'],
					);
				}
				if (!empty($arResult['DEALS']))
				{
					$arResult['DEALS'] = array_values($arResult['DEALS']);
				}
			}
		}

		if(!$found)
		{
			$arResult = array('FOUND' => 'N');
			$userPermissions = CCrmPerms::GetUserPermissions($userId);
			if (CCrmLead::CheckCreatePermission($userPermissions))
			{
				$arResult['LEAD_URL'] = CCrmOwnerType::GetEditUrl(CCrmOwnerType::Lead, 0);
				if($arResult['LEAD_URL'] !== '')
				{
					$arResult['LEAD_URL'] = CCrmUrlUtil::AddUrlParams($arResult['LEAD_URL'], array("phone" => (string)$phone, 'origin_id' => 'VI_'.$callId));
				}
			}
			if (CCrmContact::CheckCreatePermission($userPermissions))
			{
				$arResult['CONTACT_URL'] = CCrmOwnerType::GetEditUrl(CCrmOwnerType::Contact, 0);
				if($arResult['CONTACT_URL'] !== '')
				{
					$arResult['CONTACT_URL'] = CCrmUrlUtil::AddUrlParams($arResult['CONTACT_URL'], array("phone" => (string)$phone, 'origin_id' => 'VI_'.$callId));
				}
			}
		}
		return $arResult;
	}

	/**
	 * Creates activity and returns id of the created activity.
	 * @param array $callFields Fields of the call, taken from the b_voximplant_call table.
	 *	<li>CALL_ID string
	 *  <li>CRM_ENTITY_TYPE string
	 *  <li>CRM_ENTITY_ID int
	 *  <li>CALLER_ID string
	 *  <li>USER_ID int
	 *  <li>INCOMING
	 *  <li>DATE_CREATE
	 * @return int|bool Id of the created activity or false in case of error.
	 */
	public static function AddCall(array $callFields)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return false;
		}
		CVoxImplantHistory::WriteToLog($callFields, 'CRM ADD CALL');

		if(isset($callFields['CRM_ENTITY_TYPE']) && isset($callFields['CRM_ENTITY_ID']))
		{
			$crmEntity = array(
				'ENTITY_TYPE_NAME' => $callFields['CRM_ENTITY_TYPE'],
				'ENTITY_TYPE' => CCrmOwnerType::ResolveID($callFields['CRM_ENTITY_TYPE']),
				'ENTITY_ID' => $callFields['CRM_ENTITY_ID'],
				'BINDINGS' => array(
					0 => array(
						'OWNER_ID' => $callFields['CRM_ENTITY_ID'],
						'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($callFields['CRM_ENTITY_TYPE'])
					)
				)
			);

			$deals = self::findDealsByEntity($callFields['CRM_ENTITY_TYPE'], $callFields['CRM_ENTITY_ID']);
			if(is_array($deals) && count($deals) > 0)
			{
				$crmEntity['BINDINGS'][] = array(
					'OWNER_ID' => $deals[0]['ID'],
					'OWNER_TYPE_ID' => CCrmOwnerType::Deal
				);
			}
		}
		else
		{
			$crmEntity = self::GetCrmEntity($callFields['CALLER_ID'], $callFields['USER_ID']);
		}

		if (!$crmEntity)
		{
			return false;
		}

		$direction = isset($callFields['INCOMING']) && intval($callFields['INCOMING']) === CVoxImplantMain::CALL_INCOMING
			? CCrmActivityDirection::Incoming
			: CCrmActivityDirection::Outgoing;

		$activityFields = array(
			'TYPE_ID' =>  CCrmActivityType::Call,
			'PROVIDER_ID' => Provider\Call::ACTIVITY_PROVIDER_ID,
			//'ASSOCIATED_ENTITY_ID' => $params['ID'],
			'START_TIME' => $callFields['DATE_CREATE'],
			'COMPLETED' => 'N',
			'PRIORITY' => CCrmActivityPriority::Medium,
			'DESCRIPTION' => '',
			'DESCRIPTION_TYPE' => CCrmContentType::PlainText,
			'LOCATION' => '',
			'NOTIFY_TYPE' => CCrmActivityNotifyType::None,
			'BINDINGS' => array(),
			'SETTINGS' => array(),
			'AUTHOR_ID' => $callFields['USER_ID']
		);

		if($callFields['INCOMING'] === CVoxImplantMain::CALL_CALLBACK)
		{
			$activityFields['PROVIDER_TYPE_ID'] = Provider\Call::ACTIVITY_PROVIDER_TYPE_CALLBACK;
			$activityFields['SUBJECT'] = Loc::getMessage('VI_CRM_CALLBACK_TITLE');
		}
		else
		{
			$activityFields['PROVIDER_TYPE_ID'] = Provider\Call::ACTIVITY_PROVIDER_TYPE_CALL;
			$activityFields['SUBJECT'] = Loc::getMessage('VI_CRM_CALL_TITLE');
			$activityFields['DIRECTION'] = $direction;
		}

		$activityFields['RESPONSIBLE_ID'] = $callFields['USER_ID'];
		$activityFields['ORIGIN_ID'] = 'VI_'.$callFields['CALL_ID'];

		if (isset($crmEntity['BINDINGS']))
		{
			$activityFields['BINDINGS'] = $crmEntity['BINDINGS'];
		}
		else
		{
			$activityFields['BINDINGS'][] = array(
				'OWNER_ID' => $crmEntity['ENTITY_ID'],
				'OWNER_TYPE_ID' => $crmEntity['ENTITY_TYPE']
			);
		}

		$communications = array(
			array(
				'ID' => 0,
				'TYPE' => 'PHONE',
				'VALUE' => $callFields['CALLER_ID'],
				'ENTITY_ID' => $crmEntity['ENTITY_ID'],
				'ENTITY_TYPE_ID' => $crmEntity['ENTITY_TYPE']
			)
		);

		$activityId = CCrmActivity::Add($activityFields, false, true, array('REGISTER_SONET_EVENT' => true));


		if($activityId > 0)
		{
			CCrmActivity::SaveCommunications($activityId, $communications, $activityFields, true, false);
			\Bitrix\Crm\Integration\Channel\VoxImplantTracker::getInstance()->registerActivity($activityId);
			CVoxImplantHistory::WriteToLog($activityFields, 'CREATED CRM ACTIVITY '.$activityId);
			return $activityId;
		}
		else
		{
			CVoxImplantHistory::WriteToLog(array(), 'UNKNOWN ERROR DURING CREATING CRM ACTIVITY');
			return false;
		}
		//CCrmActivity::SaveBindings($ID, $arFields['BINDINGS'])
	}

	public static function UpdateCall($params)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return false;
		}
		CVoxImplantHistory::WriteToLog($params, 'CRM UPDATE TO CALL');

		if($params['CRM_ACTIVITY_ID'])
		{
			$activity = CCrmActivity::GetByID($params['CRM_ACTIVITY_ID'], false);
		}
		else
		{
			$activity = CCrmActivity::GetByOriginID('VI_'.$params['CALL_ID'], false);
		}

		if ($activity)
		{
			$params = CVoxImplantHistory::PrepereData($params);
			if (isset($params['DESCRIPTION']) && strlen($params['DESCRIPTION']) > 0)
			{
				$description = $params['DESCRIPTION'];
			}
			else
			{
				if($params['CALL_DURATION'] > 0)
				{
					$description = GetMessage('VI_CRM_CALL_DURATION', array('#DURATION#' => $params['CALL_DURATION_TEXT']));
				}
				else
				{
					$description = GetMessage('VI_CRM_CALL_STATUS').' '.$params['CALL_FAILED_REASON'];
				}
			}

			if ($params['INCOMING'] == CVoxImplantMain::CALL_INCOMING)
			{
				$portalNumbers = CVoxImplantConfig::GetPortalNumbers();
				$portalNumber = isset($portalNumbers[$params['PORTAL_NUMBER']])? $portalNumbers[$params['PORTAL_NUMBER']]: '';
				if ($portalNumber)
				{
					$description = $description."\n".GetMessage('VI_CRM_CALL_TO_PORTAL_NUMBER', array('#PORTAL_NUMBER#' => $portalNumber));
				}
			}

			$arFields = array(
				'DESCRIPTION' => (strlen($activity['DESCRIPTION'])>0? $activity['DESCRIPTION']."\n":'').$description,
			);

			if($params['INCOMING'] === CVoxImplantMain::CALL_INCOMING || $params['INCOMING'] === CVoxImplantMain::CALL_CALLBACK)
			{
				$arFields['COMPLETED'] = $params['CALL_FAILED_CODE'] !== '304';
			}
			else
			{
				$arFields['COMPLETED'] = 'Y';
			}

			if (isset($params['PORTAL_USER_ID']))
			{
				$arFields['RESPONSIBLE_ID'] = $params['PORTAL_USER_ID'];
			}

			if($params['CALL_FAILED_CODE'] == '200')
			{
				if($params['INCOMING'] == CVoxImplantMain::CALL_INCOMING)
					$arFields['RESULT_STREAM'] = \Bitrix\Crm\Activity\StatisticsStream::Incoming;
				else if($params['INCOMING'] == CVoxImplantMain::CALL_OUTGOING)
					$arFields['RESULT_STREAM'] = \Bitrix\Crm\Activity\StatisticsStream::Outgoing;
				else if($params['INCOMING'] == CVoxImplantMain::CALL_CALLBACK)
					$arFields['RESULT_STREAM'] = \Bitrix\Crm\Activity\StatisticsStream::Reversing;
			}
			else
			{
				$arFields['RESULT_STREAM'] = \Bitrix\Crm\Activity\StatisticsStream::Missing;
			}

			if($params['CALL_VOTE'] > 3)
				$arFields['RESULT_MARK'] = \Bitrix\Crm\Activity\StatisticsMark::Positive;
			else if ($params['CALL_VOTE'] > 0)
				$arFields['RESULT_MARK'] = \Bitrix\Crm\Activity\StatisticsMark::Negative;
			else
				$arFields['RESULT_MARK'] = \Bitrix\Crm\Activity\StatisticsMark::None;

			if(!$activity['ORIGIN_ID'])
				$arFields['ORIGIN_ID'] = 'VI_'.$params['CALL_ID'];

			CCrmActivity::Update($activity['ID'], $arFields, false, true, Array('REGISTER_SONET_EVENT' => true));
		}

		return true;
	}

	public static function UpdateCallResponsible($callId, $userId)
	{
		if(CModule::IncludeModule('crm'))
			return false;

		$call = VI\CallTable::getByCallId($callId);
		if(!$call)
			return false;

		$activityId = (int)$call['CRM_ACTIVITY_ID'];
		if($activityId == 0)
			return false;

		$activityFields = array(
			'RESPONSIBLE_ID' => $userId
		);

		CCrmActivity::Update($activityId, $activityFields, false, true, Array('REGISTER_SONET_EVENT' => true));
	}

	/**
	 * Returns CALL_ID associated with CRM activity.
	 * @param int $activityId Id of the activity.
	 * @return string|false CALL_ID if found or false otherwise.
	 */
	public static function GetCallIdByActivityId($activityId)
	{
		if (!CModule::IncludeModule('crm'))
			return false;

		$activityId = (int)$activityId;
		if($activityId === 0)
			return false;

		$activity = CCrmActivity::GetByID($activityId, false);
		if(!$activity)
			return false;
		
		if($activity['PROVIDER_ID'] !== Bitrix\Crm\Activity\Provider\Call::ACTIVITY_PROVIDER_ID)
			return false;

		$callId = $activity['ORIGIN_ID'];

		if(strpos($callId, 'VI_') !== 0)
			return false;

		$callId = substr($callId, 3);

		return $callId;
	}

	public static function AttachRecordToCall($params)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return false;
		}

		CVoxImplantHistory::WriteToLog($params, 'CRM ATTACH RECORD TO CALL');
		if ($params['CALL_WEBDAV_ID'] > 0)
		{
			$activityId = CCrmActivity::GetIDByOrigin('VI_'.$params['CALL_ID']);
			if ($activityId)
			{
				$arFields['STORAGE_TYPE_ID'] = CCrmActivity::GetDefaultStorageTypeID();
				$arFields['STORAGE_ELEMENT_IDS'] = array($params['CALL_WEBDAV_ID']);
				CCrmActivity::Update($activityId, $arFields, false);
			}
		}
		
		return true;
	}

	public static function RegisterEntity($params)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return false;
		}

		$callId = $params['ORIGIN_ID'];
		$callerId = '';
		if (substr($callId, 0, 3) == 'VI_')
			$callId = substr($callId, 3);


		$res = VI\CallTable::getList(Array(
			'filter' => Array('=CALL_ID' => $callId),
		));
		if ($call = $res->fetch())
		{
			$callerId = $call['CALLER_ID'];
			$crmData = CVoxImplantCrmHelper::GetCrmEntity($call['CALLER_ID'], 0, false);
			if(is_array($crmData))
			{
				$call['CRM_ENTITY_TYPE'] = $crmData['ENTITY_TYPE_NAME'];
				$call['CRM_ENTITY_ID'] = $crmData['ENTITY_ID'];
				$callCrmFields = array(
					'CRM_ENTITY_TYPE' => $crmData['ENTITY_TYPE_NAME'],
					'CRM_ENTITY_ID' => $crmData['ENTITY_ID'],
				);

				VI\CallTable::update($call['ID'], $callCrmFields);
			}

			$activityId = CVoxImplantCrmHelper::AddCall(Array(
				'CALL_ID' => $call['CALL_ID'],
				'PHONE_NUMBER' => $call['CALLER_ID'],
				'INCOMING' => $call['INCOMING'],
				'USER_ID' => $call['USER_ID'],
				'DATE_CREATE' => $call['DATE_CREATE'],
				'CRM_ENTITY_TYPE' => $call['CRM_ENTITY_TYPE'],
				'CRM_ENTITY_ID' => $call['CRM_ENTITY_ID'],
			));

			if($activityId > 0)
			{
				$call['CRM_ACTIVITY_ID'] = $activityId;
				VI\CallTable::update($call['ID'], array(
					'CRM_ACTIVITY_ID' => $activityId,
				));
			}

			if ($call['USER_ID'] > 0)
			{
				$crmData = CVoxImplantCrmHelper::GetDataForPopup($callId, $call['CALLER_ID'], $call['USER_ID']);

				$pullResult = CVoxImplantIncoming::SendPullEvent(Array(
					'COMMAND' => 'update_crm',
					'USER_ID' => $call['USER_ID'],
					'CALL_ID' => $callId,
					'CALLER_ID' => $callerId,
					'CRM' => $crmData,
				));
			}

			CVoxImplantHistory::WriteToLog(Array($callId, $call), 'CRM ATTACH INIT CALL');
		}
		else
		{
			$res = VI\StatisticTable::getList(Array(
				'filter' => Array('=CALL_ID' => $callId),
			));
			if ($history = $res->fetch())
			{
				$history['USER_ID'] = $history['PORTAL_USER_ID'];
				$history['DATE_CREATE'] = $history['CALL_START_DATE'];

				CVoxImplantCrmHelper::AddCall(Array(
					'CALL_ID' => $history['CALL_ID'],
					'CALLER_ID' => $history['PHONE_NUMBER'],
					'INCOMING' => $history['INCOMING'],
					'USER_ID' => $history['USER_ID'],
					'DATE_CREATE' => $history['DATE_CREATE']
				));

				CVoxImplantCrmHelper::UpdateCall($history);

				CVoxImplantCrmHelper::AttachRecordToCall(Array(
					'CALL_ID' => $history['CALL_ID'],
					'CALL_WEBDAV_ID' => $history['CALL_WEBDAV_ID'],
					'CALL_RECORD_ID' => $history['CALL_RECORD_ID'],
				));

				CVoxImplantHistory::WriteToLog(Array($callId), 'CRM ATTACH FULL CALL');
			}
		}

		return true;
	}

	public static function AddLead($params)
	{
		if (!CModule::IncludeModule('crm'))
		{
			return false;
		}

		if (strlen($params['PHONE_NUMBER']) <= 0 || intval($params['USER_ID']) <= 0)
		{
			return false;
		}
		
		$result = VI\PhoneTable::getList(Array(
			'select' => Array('USER_ID', 'PHONE_MNEMONIC'),
			'filter' => Array('=PHONE_NUMBER' => $params['PHONE_NUMBER'], '=USER.ACTIVE' => 'Y')
		));
		if ($row = $result->fetch())
		{
			return false;
		}

		$title = GetMessage($params['INCOMING']? 'VI_CRM_CALL_INCOMING': 'VI_CRM_CALL_OUTGOING');

		$arFields = array(
			'TITLE' => $params['PHONE_NUMBER'].' - '.$title,
			'OPENED' => 'Y',
			'PHONE_WORK' => $params['PHONE_NUMBER'],
		);

		$statuses = CCrmStatus::GetStatusList("SOURCE");
		if (isset($statuses[$params['CRM_SOURCE']]))
		{
			$arFields['SOURCE_ID'] = $params['CRM_SOURCE'];
		}
		else if (isset($statuses['CALL']))
		{
			$arFields['SOURCE_ID'] = 'CALL';
		}
		else if (isset($statuses['OTHER']))
		{
			$arFields['SOURCE_ID'] = 'OTHER';
		}

		$portalNumbers = CVoxImplantConfig::GetPortalNumbers();
		$portalNumber = isset($portalNumbers[$params['SEARCH_ID']])? $portalNumbers[$params['SEARCH_ID']]: '';
		if ($portalNumber)
		{
			$arFields['SOURCE_DESCRIPTION'] = GetMessage('VI_CRM_CALL_TO_PORTAL_NUMBER', array('#PORTAL_NUMBER#' => $portalNumber));
		}

		$arFields['FM'] = CCrmFieldMulti::PrepareFields($arFields);

		$CCrmLead = new CCrmLead(false);
		$ID = $CCrmLead->Add($arFields, true, Array(
			'CURRENT_USER' => $params['USER_ID'],
			'DISABLE_USER_FIELD_CHECK' => true
		));

		$arErrors = array();

		if($ID)
		{
			CVoxImplantHistory::WriteToLog($arFields, 'LEAD '.$ID.' CREATED');
			if(CVoxImplantConfig::GetLeadWorkflowExecution() == CVoxImplantConfig::WORKFLOW_START_IMMEDIATE)
			{
				self::StartLeadWorkflow($ID);
			}
			Bitrix\Crm\Integration\Channel\VoxImplantTracker::getInstance()->registerLead($ID);
		}
		else
		{
			CVoxImplantHistory::WriteToLog($CCrmLead->LAST_ERROR, 'ERROR CREATING LEAD');
		}
		return $ID;
	}

	public static function UpdateLead($id, $params)
	{
		if (!isset($params['ASSIGNED_BY_ID']))
			return false;

		if (!CModule::IncludeModule('crm'))
		{
			return false;
		}

		$update = Array('ASSIGNED_BY_ID' => $params['ASSIGNED_BY_ID']);

		$CCrmLead = new CCrmLead(false);
		$CCrmLead->Update($id, $update);

		return true;
	}
	
	public static function StartLeadWorkflow($leadId)
	{
		CCrmBizProcHelper::AutoStartWorkflows(
			CCrmOwnerType::Lead,
			$leadId,
			CCrmBizProcEventType::Create,
			$arErrors
		);

		//Region automation
		if (class_exists('\Bitrix\Crm\Automation\Factory'))
		{
			\Bitrix\Crm\Automation\Factory::runOnAdd(\CCrmOwnerType::Lead, $leadId);
		}
		//end region
	}

	public static function StartCallTrigger($callId)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return;

		if (!class_exists('\Bitrix\Crm\Automation\Trigger\CallTrigger'))
			return;

		$call = VI\CallTable::getByCallId($callId);
		if(!$call)
			return;

		if($call['CRM_ENTITY_TYPE'] != '' && $call['CRM_ENTITY_ID'] > 0)
		{
			$bindings = array(
				array(
					'OWNER_TYPE_ID' => CCrmOwnerType::ResolveID($call['CRM_ENTITY_TYPE']),
					'OWNER_ID' => $call['CRM_ENTITY_ID']
				)
			);
		}
		else
		{
			$bindings = CVoxImplantCrmHelper::GetCrmEntities($call['CALLER_ID'], 0, false);
		}
		$additionalBindings = array();

		if(is_array($bindings))
		{
			foreach ($bindings as $binding)
			{
				$deals = self::findDealsByEntity(CCrmOwnerType::ResolveName($binding['OWNER_TYPE_ID']), $binding['OWNER_ID']);

				if(is_array($deals))
				{
					foreach ($deals as $deal)
					{
						$additionalBindings[] = array(
							'OWNER_TYPE_ID' => CCrmOwnerType::Deal,
							'OWNER_ID' => $deal['ID']
						);
					}
				}
			}

			$bindings = array_merge($bindings, $additionalBindings);
			\Bitrix\Crm\Automation\Trigger\CallTrigger::execute($bindings);
		}
	}

	public static function findDealsByPhone($phone)
	{
		if (strlen($phone) <= 0)
		{
			return false;
		}

		if (!CModule::IncludeModule('crm'))
		{
			return false;
		}

		$deals = array();

		$entityTypeIDs = array(CCrmOwnerType::Contact, CCrmOwnerType::Company);
		foreach($entityTypeIDs as $entityTypeID)
		{
			$results = CCrmDeal::FindByCommunication($entityTypeID, 'PHONE', $phone, false, array('ID', 'TITLE', 'STAGE_ID', 'CATEGORY_ID', 'ASSIGNED_BY_ID', 'COMPANY_ID', 'CONTACT_ID', 'DATE_MODIFY'));
			foreach($results as $fields)
			{
				$semanticID = \CCrmDeal::GetSemanticID(
					$fields['STAGE_ID'],
					(isset($fields['CATEGORY_ID']) ? $fields['CATEGORY_ID'] : 0)
				);

				if(Bitrix\Crm\PhaseSemantics::isFinal($semanticID))
				{
					continue;
				}

				$entityID = (int)($entityTypeID === CCrmOwnerType::Company ? $fields['COMPANY_ID'] : $fields['CONTACT_ID']);
				if($entityID <= 0)
				{
					continue;
				}

				$deals[$fields['ID']] = $fields;
			}
		}

		sortByColumn($deals, array('DATE_MODIFY' => array(SORT_DESC)));

		return $deals;
	}

	public static function OnCrmCallbackFormSubmitted($params)
	{
		if($params['STOP_CALLBACK'])
		{
			self::addMissedCall(array(
				'INCOMING' => CVoxImplantMain::CALL_CALLBACK,
				'CONFIG_SEARCH_ID' => $params['CALL_FROM'],
				'PHONE_NUMBER' => $params['CALL_TO'],
				'CRM_ENTITY_TYPE' => $params['CRM_ENTITY_TYPE'],
				'CRM_ENTITY_ID' => $params['CRM_ENTITY_ID']
			));
		}
		else
		{
			$startResult = CVoxImplantOutgoing::startCallBack(
				$params['CALL_FROM'],
				$params['CALL_TO'],
				$params['TEXT'],
				Bitrix\Voximplant\Tts\Language::getDefaultVoice(),
				array(
					'CRM_ENTITY_TYPE' => $params['CRM_ENTITY_TYPE'],
					'CRM_ENTITY_ID' => $params['CRM_ENTITY_ID'],
				)
			);
			if($startResult->isSuccess())
			{
				$callData = $startResult->getData();
				$callId = $callData['CALL_ID'];
				//todo: store associated crm entities
			}
		}
	}

	/**
	 * Creates fake missed call in the statistics table and all the crm stuff.
	 * @param array $params Call record parameters.
	 * @return bool.
	 */
	public static function addMissedCall(array $params)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return false;

		$config = CVoxImplantConfig::GetConfigBySearchId($params['CONFIG_SEARCH_ID']);
		if(!$config)
			return false;

		$callId = uniqid('call.', true);
		$entityFields = CCrmSipHelper::getEntityFields(
			CCrmOwnerType::ResolveID($params['CRM_ENTITY_TYPE']),
			$params['CRM_ENTITY_ID']
		);
		if(!is_array($entityFields))
			return false;

		$responsibleUserId = $entityFields['ASSIGNED_BY_ID'];
		$statisticsRecord = array(
			'INCOMING' => $params['INCOMING'] ?: CVoxImplantMain::CALL_INCOMING,
			'PORTAL_USER_ID' => $responsibleUserId,
			'PORTAL_NUMBER' => $params['CONFIG_SEARCH_ID'],
			'PHONE_NUMBER' => $params['PHONE_NUMBER'],
			'CALL_ID' => $callId,
			'CALL_DURATION' => 0,
			'CALL_START_DATE' => new \Bitrix\Main\Type\DateTime(),
			'CALL_FAILED_CODE' => '304',
			'CALL_FAILED_REASON' => 'Missed call',
			'CRM_ENTITY_TYPE' => $params['CRM_ENTITY_TYPE'],
			'CRM_ENTITY_ID' => $params['CRM_ENTITY_ID']
		);

		$insertResult = VI\StatisticTable::add($statisticsRecord);
		if(!$insertResult->isSuccess())
			return false;

		$statisticsRecord['ID'] =  $insertResult->getId();
		if($config['CRM'] == 'Y')
		{
			$activityId = self::AddCall(array(
				'INCOMING' => $statisticsRecord['INCOMING'],
				'USER_ID' => $statisticsRecord['PORTAL_USER_ID'],
				'CALL_ID' => $statisticsRecord['CALL_ID'],
				'CALLER_ID' => $statisticsRecord['PHONE_NUMBER'],
				'DATE_CREATE' => $statisticsRecord['CALL_START_DATE'],
				'CRM_ENTITY_TYPE' => $statisticsRecord['CRM_ENTITY_TYPE'],
				'CRM_ENTITY_ID' => $statisticsRecord['CRM_ENTITY_ID'],
			));

			if($activityId > 0)
			{
				VI\StatisticTable::update($statisticsRecord['ID'], array(
					'CRM_ACTIVITY_ID' => $activityId
				));

				self::UpdateCall(Array(
					'CRM_ACTIVITY_ID' => $activityId,
					'CALL_ID' => $statisticsRecord['CALL_ID'],
					'PHONE_NUMBER' => $statisticsRecord['CALLER_ID'],
					'INCOMING' => $statisticsRecord['INCOMING'],
					'DESCRIPTION' => GetMessage('VI_CRM_CALL_MISSED'),
					'CALL_FAILED_CODE' => '304',
				));

				$chatMessage = \CVoxImplantHistory::GetMessageForChat($statisticsRecord, false);
				if($chatMessage != '')
				{
					\CVoxImplantHistory::SendMessageToChat($statisticsRecord["PORTAL_USER_ID"], $statisticsRecord["PHONE_NUMBER"], $statisticsRecord["INCOMING"], $chatMessage);
				}
			}
		}
	}

	private static function findDealsByEntity($entityType, $entityId)
	{
		if(!CModule::IncludeModule('crm'))
			return false;

		switch ($entityType)
		{
			case CCrmOwnerType::ContactName:
				$cursor = CCrmDeal::GetListEx(
					array(),
					array('=CONTACT_ID' => $entityId, 'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					array('ID', 'TITLE', 'STAGE_ID', 'CATEGORY_ID', 'ASSIGNED_BY_ID', 'COMPANY_ID', 'CONTACT_ID', 'DATE_MODIFY')
				);
				break;
			case CCrmOwnerType::CompanyName:
				$cursor = CCrmDeal::GetListEx(
					array(),
					array('=COMPANY_ID' => $entityId, 'CHECK_PERMISSIONS' => 'N'),
					false,
					false,
					array('ID', 'TITLE', 'STAGE_ID', 'CATEGORY_ID', 'ASSIGNED_BY_ID', 'COMPANY_ID', 'CONTACT_ID', 'DATE_MODIFY')
				);
				break;
		}

		if(!is_object($cursor))
			return false;

		$result = array();
		while($row = $cursor->Fetch())
		{
			$semanticId = \CCrmDeal::GetSemanticID(
				$row['STAGE_ID'],
				(isset($row['CATEGORY_ID']) ? $row['CATEGORY_ID'] : 0)
			);

			if(Bitrix\Crm\PhaseSemantics::isFinal($semanticId))
			{
				continue;
			}

			$result[] = $row;
		}

		sortByColumn($result, array('DATE_MODIFY' => array(SORT_DESC)));
		return $result;
	}

	private static function convertEntityFields($entityType, $entityData)
	{
		if(!CModule::IncludeModule('crm'))
			return false;

		$result = array(
			'FOUND' => 'N',
			'CONTACT' => array(),
			'COMPANY' => array(),
			'ACTIVITIES' => array(),
			'DEALS' => array(),
			'RESPONSIBILITY' => array()
		);

		switch ($entityType)
		{
			case CCrmOwnerType::ContactName:
				$result['FOUND'] = 'Y';
				$result['CONTACT'] = array(
					'NAME' => $entityData['FORMATTED_NAME'],
					'POST' => $entityData['POST'],
					'PHOTO' => '',
				);
				if (intval($entityData['PHOTO']) > 0)
				{
					$photo = CFile::ResizeImageGet(
						$entityData['PHOTO'],
						array('width' => 370, 'height' => 370),
						BX_RESIZE_IMAGE_EXACT,
						false,
						false,
						true
					);
					$result['CONTACT']['PHOTO'] = $photo['src'];
				}

				$result['COMPANY'] = $entityData['COMPANY_TITLE'];

				$result['CONTACT_DATA'] = array(
					'ID' => $entityData['ID'],
				);
				break;
			case CCrmOwnerType::LeadName:
				$result['FOUND'] = 'Y';
				$result['CONTACT'] = array(
					'ID' => 0,
					'NAME' => !empty($entityData['FORMATTED_NAME'])? $entityData['FORMATTED_NAME']: $entityData['TITLE'],
					'POST' => $entityData['POST'],
					'PHOTO' => '',
				);

				$result['COMPANY'] = $entityData['COMPANY_TITLE'];

				$result['LEAD_DATA'] = array(
					'ID' => $entityData['ID'],
					'ASSIGNED_BY_ID' => $entityData['ASSIGNED_BY_ID']
				);
				break;
			case CCrmOwnerType::CompanyName:
				$result['FOUND'] = 'Y';
				$result['COMPANY'] = $entityData['TITLE'];
				$result['COMPANY_DATA'] = array(
					'ID' => $entityData['ID'],
				);
				break;
		}

		if ($entityData['ASSIGNED_BY_ID'] > 0)
		{
			if ($user = Bitrix\Main\UserTable::getById($entityData['ASSIGNED_BY_ID'])->fetch())
			{
				$userPhoto = CFile::ResizeImageGet(
					$user['PERSONAL_PHOTO'],
					array('width' => 37, 'height' => 37),
					BX_RESIZE_IMAGE_EXACT,
					false,
					false,
					true
				);

				$result['RESPONSIBILITY'] = array(
					'ID' => $user['ID'],
					'NAME' => CUser::FormatName(CSite::GetNameFormat(false), $user, true, false),
					'PHOTO' => $userPhoto ? $userPhoto['src']: '',
					'POST' => $user['WORK_POSITION'],
				);
			}
		}

		if (isset($entityData['SHOW_URL']))
			$result['SHOW_URL'] = $entityData['SHOW_URL'];

		if (isset($entityData['ACTIVITY_LIST_URL']))
			$result['ACTIVITY_URL'] = $entityData['ACTIVITY_LIST_URL'];

		if (isset($entityData['INVOICE_LIST_URL']))
			$result['INVOICE_URL'] = $entityData['INVOICE_LIST_URL'];

		if (isset($entityData['DEAL_LIST_URL']))
			$result['DEAL_URL'] = $entityData['DEAL_LIST_URL'];

		return $result;
	}

	public static function attachCallToCallList($callListId, array $call)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return;

		$callListId = (int)$callListId;
		$crmEntityId = (int)$call['CRM_ENTITY_ID'];

		if($callListId == 0)
			throw new \Bitrix\Main\ArgumentException('Call List id is empty');

		if($crmEntityId == 0)
			throw new \Bitrix\Main\ArgumentException('Crm entity id is empty');

		\Bitrix\Crm\CallList\Internals\CallListItemTable::update(
			array(
				'LIST_ID' => $callListId,
				'ELEMENT_ID' => $crmEntityId
			),
			array(
				'CALL_ID' => $call['ID']
			)
		);
	}

	/**
	 * Returns id of the crm responsible or false if entity is not found
	 * @param string $entityType String name of the entity type.
	 * @param int $entityId Entity id.
	 * @return bool|int
	 */
	public static function getResponsible($entityType, $entityId)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return false;

		return CCrmOwnerType::GetResponsibleID(CCrmOwnerType::ResolveID($entityType), $entityId, false);
	}

	public static function attachLeadToCall($callId, $leadId)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return false;
		
		VI\CallTable::updateWithCallId($callId, array(
			'CRM_ENTITY_TYPE' => \CCrmOwnerType::LeadName,
			'CRM_ENTITY_ID' => $leadId,
			'CRM_LEAD' => $leadId
		));
	}

	public static function getActivityEditUrl($activityId)
	{
		if(!\Bitrix\Main\Loader::includeModule('crm'))
			return false;

		return \CCrmOwnerType::GetEditUrl(\CCrmOwnerType::Activity, $activityId, false);
	}
}
