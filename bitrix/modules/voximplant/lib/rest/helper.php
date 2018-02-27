<?php

namespace Bitrix\Voximplant\Rest;

use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Rest\AppTable;
use Bitrix\Rest\EventTable;
use Bitrix\Voximplant\CallTable;
use Bitrix\Voximplant\PhoneTable;
use Bitrix\Voximplant\StatisticTable;

class Helper
{
	const EVENT_START_EXTERNAL_CALL = 'OnExternalCallStart';
	const EVENT_START_EXTERNAL_CALLBACK = 'OnExternalCallBackStart';
	const PLACEMENT_CALL_CARD = 'CALL_CARD';

	/**
	 * Returns user id of the user with given inner phone number, or false if user is not found.
	 * @param string $phoneNumber Inner phone number.
	 * @return int|false
	 */
	public static function getUserByPhone($phoneNumber)
	{
		$row = PhoneTable::getList(array(
			'select' => array('USER_ID'),
			'filter' => array(
				'PHONE_NUMBER' => $phoneNumber,
				'PHONE_MNEMONIC' => 'UF_PHONE_INNER'
			)
		))->fetch();

		return is_array($row) ? (int)$row['USER_ID'] : false;
	}

	/**
	 * Register call, started to perform in external PBX. Auto creates
	 * @param array $fields
	 * <li> USER_ID int
	 * <li> PHONE_NUMBER string
	 * <li> TYPE int
	 * <li> CALL_START_DATE date
	 * <li> CRM bool
	 * <li> CRM_CREATE bool
	 * <li> CRM_SOURCE
	 * <li> CRM_ENTITY_TYPE
	 * <li> CRM_ENTITY_ID
	 * <li> REST_APP_ID
	 * <li> SHOW
	 * <li> CALL_LIST_ID
	 * @return Result
	 */
	public static function registerExternalCall(array $fields)
	{
		$result = new Result();
		$callId = 'externalCall.'.md5(uniqid($fields['REST_APP_ID'].$fields['USER_ID'].$fields['PHONE_NUMBER'])).'.'.time();
		$isCrmAvailable = Loader::includeModule('crm');

		$phoneNumber = \CVoxImplantPhone::stripLetters($fields['PHONE_NUMBER']);
		if(!$phoneNumber)
		{
			$result->addError(new Error('Unsupported phone number format'));
			return $result;
		}
		
		$crmCreate = $fields['CRM'] || $fields['CRM_CREATE'];
		$newCall = array(
			'USER_ID' => $fields['USER_ID'],
			'CALL_ID' => $callId,
			'INCOMING' => $fields['TYPE'],
			'DATE_CREATE' => ($fields['CALL_START_DATE'] ?: new DateTime()),
			'CALLER_ID' => $phoneNumber,
			'CRM' => 'Y',
			'REST_APP_ID' => $fields['REST_APP_ID'],
		);

		if(isset($fields['CRM_ENTITY_TYPE']) && isset($fields['CRM_ENTITY_ID']))
		{
			$crmEntity = array(
				'ENTITY_TYPE_NAME' => $fields['CRM_ENTITY_TYPE'],
				'ENTITY_ID' => $fields['CRM_ENTITY_ID']
			);
		}
		else
		{
			$crmEntity = \CVoxImplantCrmHelper::GetCrmEntity($fields['PHONE_NUMBER'], \Bitrix\Voximplant\Security\Helper::getCurrentUserId());
			if (is_array($crmEntity))
			{
				// nothing here
			}
			else if($crmCreate && $isCrmAvailable)
			{
				// no crm entity found, creating new lead
				$leadId = \CVoxImplantCrmHelper::AddLead(array(
					'PHONE_NUMBER' => $phoneNumber,
					'USER_ID' => $fields['USER_ID'],
					'INCOMING' => ($fields['TYPE'] == \CVoxImplantMain::CALL_INCOMING),
					'CRM_SOURCE' => $fields['CRM_SOURCE']
				));
				if($leadId > 0)
				{
					$crmEntity = array(
						'ENTITY_TYPE_NAME' => \CCrmOwnerType::LeadName,
						'ENTITY_ID' => $leadId
					);
					$newCall['CRM_LEAD'] = $leadId;
					if(\CVoxImplantConfig::GetLeadWorkflowExecution() == \CVoxImplantConfig::WORKFLOW_START_IMMEDIATE)
					{
						\CVoxImplantCrmHelper::StartLeadWorkflow($leadId);
					}

				}
			}
		}

		if(is_array($crmEntity))
		{
			$newCall['CRM_ENTITY_TYPE'] = $crmEntity['ENTITY_TYPE_NAME'];
			$newCall['CRM_ENTITY_ID'] = $crmEntity['ENTITY_ID'];
			$newCall['CRM'] = 'Y';
		}

		if($fields['CRM_ACTIVITY_ID'])
		{
			$newCall['CRM_ACTIVITY_ID'] = $fields['CRM_ACTIVITY_ID'];
		}
		else if($crmCreate && $isCrmAvailable)
		{
			// creating new crm activity
			$newCall['CRM_ACTIVITY_ID'] = \CVoxImplantCrmHelper::AddCall($newCall);
		}

		if($fields['CALL_LIST_ID'] > 0)
		{
			$newCall['CRM_CALL_LIST'] = $fields['CALL_LIST_ID'];
		}

		$insertResult = CallTable::add($newCall);

		if($fields['SHOW'])
		{
			self::showExternalCall(array(
				'CALL_ID' => $callId
			));
		}

		if(!$insertResult->isSuccess())
		{
			$result->addError(new Error('Database error'));
			return $result;
		}

		\CVoxImplantCrmHelper::StartCallTrigger($callId);
		$result->setData(array(
			'CALL_ID' => $newCall['CALL_ID'],
			'CRM_CREATED_LEAD' => $newCall['CRM_LEAD'],
			'CRM_ENTITY_TYPE' => $newCall['CRM_ENTITY_TYPE'],
			'CRM_ENTITY_ID' => $newCall['CRM_ENTITY_ID'],
			'CRM_ACTIVITY_ID' => $newCall['CRM_ACTIVITY_ID']
		));
		return $result;
	}

	/**
	 * Finishes call, initiated externally and updates crm lead and activity
	 * @param array $fields
	 * <li> CALL_ID
	 * <li> USER_ID
	 * <li> DURATION - call duration in seconds
	 * <li> COST - call's cost
	 * <li> COST_CURRENCY
	 * <li> STATUS_CODE
	 * <li> FAILED_REASON
	 * <li> RECORD_URL
	 * <li> VOTE
	 * <li> ADD_TO_CHAT
	 * @return Result
	 */
	public static function finishExternalCall(array $fields)
	{
		$result = new Result();
		$call = CallTable::getByCallId($fields['CALL_ID']);
		if($call === false)
		{
			$result->addError(new Error('Call is not found (call should be registered prior to finishing'));
			return $result;
		}
		
		self::hideExternalCall(array(
			'CALL_ID' => $call['CALL_ID'],
			'USER_ID' => isset($fields['USER_ID']) ? (int)$fields['USER_ID'] : $call['USER_ID']
		));

		$fields['DURATION'] = (int)$fields['DURATION'];
		if(!isset($fields['STATUS_CODE']))
			$fields['STATUS_CODE'] = $fields['DURATION'] > 0 ? '200' : '304';

		$fields['ADD_TO_CHAT'] = isset($fields['ADD_TO_CHAT']) ? (bool)$fields['ADD_TO_CHAT'] : true;


		$statisticRecord = array(
			'CALL_ID' => $call['CALL_ID'],
			'PORTAL_USER_ID' => isset($fields['USER_ID']) ? (int)$fields['USER_ID'] : $call['USER_ID'],
			'PHONE_NUMBER' => $call['CALLER_ID'],
			'INCOMING' => $call['INCOMING'],
			'CALL_DURATION' => $fields['DURATION'] ?: 0,
			'CALL_START_DATE' => $call['DATE_CREATE'],
			'CALL_STATUS' => $fields['DURATION'] > 0 ? 1 : 0,
			'CALL_VOTE' => $fields['VOTE'],
			'COST' => $fields['COST'],
			'COST_CURRENCY' => $fields['COST_CURRENCY'],
			'CALL_FAILED_CODE' => $fields['STATUS_CODE'],
			'CALL_FAILED_REASON' => $fields['FAILED_REASON'],
			'REST_APP_ID' => $call['REST_APP_ID'],
			'REST_APP_NAME' => self::getRestAppName($call['REST_APP_ID']),
			'CRM_ACTIVITY_ID' => $call['CRM_ACTIVITY_ID'],
		);

		if($call['CRM_ENTITY_TYPE'] && $call['CRM_ENTITY_ID'])
		{
			$statisticRecord['CRM_ENTITY_TYPE'] = $call['CRM_ENTITY_TYPE'];
			$statisticRecord['CRM_ENTITY_ID'] = $call['CRM_ENTITY_ID'];
		}
		else
		{
			$crmData = \CVoxImplantCrmHelper::GetCrmEntity($statisticRecord['PHONE_NUMBER'], $statisticRecord['PORTAL_USER_ID']);
			if(is_array($crmData))
			{
				$statisticRecord['CRM_ENTITY_TYPE'] = $crmData['ENTITY_TYPE_NAME'];
				$statisticRecord['CRM_ENTITY_ID'] = $crmData['ENTITY_ID'];
			}
		}

		$insertResult = StatisticTable::add($statisticRecord);
		if(!$insertResult->isSuccess())
		{
			$result->addError(new Error('Unexpected database error'));
			$result->addErrors($insertResult->getErrors());
			return $result;
		}
		$statisticRecord['ID'] = $insertResult->getId();

		CallTable::delete($call['ID']);

		if($call['CRM_LEAD'] > 0)
		{
			\CVoxImplantCrmHelper::UpdateLead(
				$call['CRM_LEAD'],
				array('ASSIGNED_BY_ID' => $statisticRecord['PORTAL_USER_ID'])
			);
		}

		if ($call['CRM'] == 'Y')
		{
			\CVoxImplantCrmHelper::UpdateCall($statisticRecord);
			if(isset($statisticRecord['CRM_ENTITY_TYPE']) && isset($statisticRecord['CRM_ENTITY_ID']))
			{
				$viMain = new \CVoxImplantMain($statisticRecord["PORTAL_USER_ID"]);
				$dialogData = $viMain->GetDialogInfo($statisticRecord['PHONE_NUMBER'], '', false);
				\CVoxImplantMain::UpdateChatInfo(
					$dialogData['DIALOG_ID'],
					array(
						'CRM' => $call['CRM'],
						'CRM_ENTITY_TYPE' => $statisticRecord['CRM_ENTITY_TYPE'],
						'CRM_ENTITY_ID' => $statisticRecord['CRM_ENTITY_ID']
					)
				);
			}
		}

		$hasRecord = ($fields['RECORD_URL'] != '');
		if($hasRecord)
			\CVoxImplantHistory::DownloadAgent($insertResult->getId(), $fields['RECORD_URL'], ($call['CRM'] === 'Y'));

		if($fields['ADD_TO_CHAT'])
		{
			$chatMessage = \CVoxImplantHistory::GetMessageForChat($statisticRecord, $hasRecord, false);
			if($chatMessage != '')
			{
				\CVoxImplantHistory::SendMessageToChat($statisticRecord["PORTAL_USER_ID"], $statisticRecord["PHONE_NUMBER"], $statisticRecord["INCOMING"], $chatMessage);
			}
		}

		if($call['CRM_LEAD'] > 0 && \CVoxImplantConfig::GetLeadWorkflowExecution() == \CVoxImplantConfig::WORKFLOW_START_DEFERRED)
		{
			\CVoxImplantCrmHelper::StartLeadWorkflow($call['CRM_LEAD']);
		}

		$result->setData($statisticRecord);
		return $result;
	}

	/**
	 * Shows card with CRM info on a call to the user.
	 * @param array $params Function parameters:
	 * <li> CALL_ID
	 * <li> USER_ID
	 * @return bool
	 */
	public static function showExternalCall(array $params)
	{
		$callId = $params['CALL_ID'];
		$call = CallTable::getByCallId($callId);
		if(!$call)
			return false;

		$userId = isset($params['USER_ID']) ? (int)$params['USER_ID'] : $call['USER_ID'];
		\CVoxImplantMain::SendPullEvent(array(
			'COMMAND' => 'showExternalCall',
			'CALL_ID' => $callId,
			'USER_ID' => $userId,
			'PHONE_NUMBER' => (string)$call['CALLER_ID'],
			'INCOMING' => $call['INCOMING'],
			'SHOW_CRM_CARD' => true, //($call['CRM'] == 'Y'),
			'CRM_ENTITY_TYPE' => $call['CRM_ENTITY_TYPE'],
			'CRM_ENTITY_ID' => $call['CRM_ENTITY_ID'],
			'CRM_ACTIVITY_ID' => $call['CRM_ACTIVITY_ID'],
			'CRM_ACTIVITY_EDIT_URL' => \CVoxImplantCrmHelper::getActivityEditUrl($call['CRM_ACTIVITY_ID']),
			'CONFIG' => array(
				'CRM_CREATE' => 'none'
			)
		));
		return true;
	}

	/**
	 * Hides card with CRM info on a call.
	 * @param array $params Function parameters:
	 * <li> CALL_ID
	 * <li> USER_ID
	 * @return bool
	 */
	public static function hideExternalCall(array $params)
	{
		$callId = $params['CALL_ID'];
		$call = CallTable::getByCallId($callId);
		if(!$call)
			return false;

		$userId = isset($params['USER_ID']) ? (int)$params['USER_ID'] : $call['USER_ID'];

		\CVoxImplantMain::SendPullEvent(array(
			'COMMAND' => 'hideExternalCall',
			'USER_ID' => $userId,
			'CALL_ID' => $callId
		));
		return true;
	}

	/**
	 * Returns rest application name by its client id.
	 * @param string $clientId Application's client id.
	 * @return string|false
	 */
	public static function getRestAppName($clientId)
	{
		if(!Loader::includeModule('rest'))
			return false;

		$row = AppTable::getByClientId($clientId);

		if(!is_array($row))
			return false;

		if ($row['MENU_NAME'] != '')
			$result = $row['MENU_NAME'];
		else if ($row['MENU_NAME_DEFAULT'] != '')
			$result = $row['MENU_NAME_DEFAULT'];
		else
			$result = $row['APP_NAME'];

		return $result;
	}

	/**
	 * Returns array of applications, capable of creating externally initiated calls
	 */
	public static function getExternalCallHandlers()
	{
		return static::getEventSubscribers(self::EVENT_START_EXTERNAL_CALL);
	}

	/**
	 * Returns array of applications, capable of starting callback
	 */
	public static function getExternalCallbackHandlers()
	{
		return static::getEventSubscribers(self::EVENT_START_EXTERNAL_CALLBACK);
	}

	protected static function getEventSubscribers($eventName)
	{
		$result = array();
		if(!Loader::includeModule('rest'))
			return $result;

		$cursor = EventTable::getList(array(
			'select' => array(
				'APP_ID' => 'APP_ID',
				'APP_NAME' => 'REST_APP.APP_NAME',
				'MENU_NAME' => 'REST_APP.LANG.MENU_NAME',
				'DEFAULT_MENU_NAME' => 'REST_APP.LANG_DEFAULT.MENU_NAME'
			),
			'filter' => array(
				'EVENT_NAME' => $eventName
			)
		));

		while($row = $cursor->fetch())
		{
			$appId = $row['APP_ID'];
			if ($row['MENU_NAME'] != '')
				$appName = $row['MENU_NAME'];
			else if ($row['DEFAULT_MENU_NAME'] != '')
				$appName = $row['DEFAULT_MENU_NAME'];
			else
				$appName = $row['APP_NAME'];

			$result[$appId] = $appName;
		}

		return $result;


	}

	/**
	 * Returns id of the rest application, set as external call handler, or false if the external call handler is not set.
	 * @param int $userId Id of the user.
	 * @return string|false
	 */
	public static function getExternalCallHandler($userId)
	{
		$userDefaultLine = \CVoxImplantUser::getUserOutgoingLine($userId);
		$numberParameters = explode(':', $userDefaultLine);
		if($numberParameters[0] === \CVoxImplantConfig::MODE_REST_APP)
			return $numberParameters[1];
		else
			return false;
	}

	/**
	 * Sends event to start call to the configured rest application
	 * @param string $number Phone number to call.
	 * @param int $userId User id of the user, initiated the call.
	 * @param array $parameters Additional parameters.
	 * @return void
	 */
	public static function startCall($number, $userId, array $parameters)
	{
		$entityType = $parameters['ENTITY_TYPE'];
		$entityId = $parameters['ENTITY_ID'];
		if(strpos($entityType, 'CRM_') === 0)
		{
			$entityType = substr($entityType, 4);
		}
		else
		{
			$entityType = '';
			$entityId = null;
		}

		$eventFields = array(
			'PHONE_NUMBER' => $number,
			'USER_ID' => $userId,
			'CRM_ENTITY_TYPE' => $entityType,
			'CRM_ENTITY_ID' => $entityId,
			'CALL_LIST_ID' => (int)$parameters['CALL_LIST_ID'],
			'APP_ID' => self::getExternalCallHandler($userId)
		);

		$event = new Event(
			'voximplant',
			'onExternalCallStart',
			$eventFields
		);
		$event->send();
	}

	/**
	 * Send event to start callback to the rest application.
	 * @param array $parameters Array of parameters.
	 * @return void
	 */
	public static function startCallBack(array $parameters)
	{
		$eventFields = array(
			'PHONE_NUMBER' => $parameters['PHONE_NUMBER'],
			'TEXT' => $parameters['TEXT'],
			'VOICE' => $parameters['VOICE'],
			'CRM_ENTITY_TYPE' => $parameters['CRM_ENTITY_TYPE'],
			'CRM_ENTITY_ID' => $parameters['CRM_ENTITY_ID'],
			'APP_ID' => $parameters['APP_ID']
		);

		$event = new Event(
			'voximplant',
			'onExternalCallBackStart',
			$eventFields
		);
		$event->send();
	}
}