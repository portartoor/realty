<?php

namespace Bitrix\Voximplant\Integration;

use Bitrix\Main\Diag\Helper;
use Bitrix\Main\Loader;
use Bitrix\Voximplant\CallTable;

/**
 * Class for integration with Push & Pull
 * @package Bitrix\Voximplant\Integration
 * @internal
 */
class Pull
{
	public static function sendInvite($users, $callId, array $params = array())
	{
		$call = CallTable::getByCallId($callId);
		$config = Array(
			"callId" => $call['CALL_ID'],
			"callerId" => $call['CALLER_ID'],
			"phoneNumber" => $params['PHONE_NAME'],
			"chatId" => 0,
			"chat" => array(),
			"typeConnect" => $params['TYPE_CONNECT'],
			"portalCall" => ($call['PORTAL_USER_ID'] >0),
			"portalCallUserId" => (int)$call['PORTAL_CALL_USER_ID'],
			"portalCallData" => isset($params['PORTAL_CALL_DATA']) ? $params['PORTAL_CALL_DATA'] : array(),
			"config" => $params['CONFIG'] ? $params['CONFIG']: array(),
			"CRM" => ($call['CRM'] == 'Y' ? \CVoxImplantCrmHelper::GetDataForPopup($call['CALL_ID'], $call['CALLER_ID']) : false),
			"showCrmCard" => ($call['CRM'] == 'Y'),
			"crmEntityType" => $call['CRM_ENTITY_TYPE'],
			"crmEntityId" => $call['CRM_ENTITY_ID'],
			"crmActivityId" => $call['CRM_ACTIVITY_ID'],
			"crmActivityEditUrl" => \CVoxImplantCrmHelper::getActivityEditUrl($call['CRM_ACTIVITY_ID']),
			"isCallback" => ($call['INCOMING'] === \CVoxImplantMain::CALL_CALLBACK)
		);

		$callName = $params['CALLER_ID'];
		if (isset($config['CRM']['CONTACT']['NAME']) && strlen($config['CRM']['CONTACT']['NAME']) > 0)
		{
			$callName = $config['CRM']['CONTACT']['NAME'];
		}
		if (isset($config['CRM']['COMPANY']) && strlen($config['CRM']['COMPANY']) > 0)
		{
			$callName .= ' ('.$config['CRM']['COMPANY'].')';
		}
		else if (isset($config['CRM']['CONTACT']['POST']) && strlen($config['CRM']['CONTACT']['POST']) > 0)
		{
			$callName .= ' ('.$config['CRM']['CONTACT']['POST'].')';
		}

		$push['sub_tag'] = 'VI_CALL_'.$call['CALL_ID'];
		$push['send_immediately'] = 'Y';
		$push['sound'] = 'call.aif';
		$push['advanced_params'] = Array(
			"notificationsToCancel" => array('VI_CALL_'.$call['CALL_ID']),
			"androidHighPriority" => true,
		);
		if ($params['PORTAL_CALL'] == 'Y')
		{
			$push['message'] = GetMessage('INCOMING_CALL', Array('#NAME#' => $params['PORTAL_CALL_DATA']['users'][$params['PORTAL_CALL_USER_ID']]['name']));
		}
		else
		{
			$push['message'] = GetMessage('INCOMING_CALL', Array('#NAME#' => $callName));
			$push['message'] = $push['message'].' '.GetMessage('CALL_FOR_NUMBER', Array('#NUMBER#' => $params['PHONE_NAME']));
		}

		$push['params'] = Array(
			'ACTION' => 'VI_CALL_'.$call['CALL_ID'],
			'PARAMS' => $config
		);
		
		return self::send('invite', $users, $config, $push);
	}

	protected static function send($command, $users, $params, $push)
	{
		if(!Loader::includeModule('pull'))
			return false;

		if(!is_array($users))
			$users = array($users);


		\CPullStack::AddByUsers($users,
			Array(
				'module_id' => 'voximplant',
				'command' => $command,
				'params' => $params,
				'push' => $push
			)
		);
	}
}