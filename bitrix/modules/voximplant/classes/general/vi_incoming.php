<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Main\Type as FieldType;
use Bitrix\Voximplant as VI;

class CVoxImplantIncoming
{
	const RULE_WAIT = 'wait';
	const RULE_TALK = 'talk';
	const RULE_HUNGUP = 'hungup';
	const RULE_PSTN = 'pstn';
	const RULE_PSTN_SPECIFIC = 'pstn_specific';
	const RULE_USER = 'user';
	const RULE_VOICEMAIL = 'voicemail';
	const RULE_QUEUE = 'queue';
	const RULE_NEXT_QUEUE = 'next_queue';

	const COMMAND_BUSY = 'busy';

	const TYPE_CONNECT_SIP = 'sip';
	const TYPE_CONNECT_DIRECT = 'direct';
	const TYPE_CONNECT_CRM = 'crm';
	const TYPE_CONNECT_QUEUE = 'queue';
	const TYPE_CONNECT_CONFIG = 'config';
	const TYPE_CONNECT_USER = 'user';
	const TYPE_CONNECT_IVR = 'ivr';

	/**
	 * Returns incoming call scenario configuration.
	 * @param array $params Array of parameters.
	 * 	<li> PHONE_NUMBER - search id of the portal's line.
	 * @return array
	 */
	public static function GetConfig($params)
	{
		$result = CVoxImplantConfig::GetConfigBySearchId($params['PHONE_NUMBER']);

		if(!$result['ID'])
		{
			return $result;
		}

		$result['TYPE_CONNECT'] = self::TYPE_CONNECT_CONFIG;
		$result = CVoxImplantIncoming::RegisterCall($result, $params);

		$isNumberInBlacklist = CVoxImplantIncoming::IsNumberInBlackList($params["CALLER_ID"]);
		$isBlacklistAutoEnable = Bitrix\Main\Config\Option::get("voximplant", "blacklist_auto", "N") == "Y";

		if ($result["WORKTIME_SKIP_CALL"] == "Y" && !$isNumberInBlacklist && $isBlacklistAutoEnable)
		{
			$isNumberInBlacklist = CVoxImplantIncoming::CheckNumberForBlackList($params["CALLER_ID"]);
		}

		if ($isNumberInBlacklist)
		{
			$result["NUMBER_IN_BLACKLIST"] = "Y";
		}

		if (!CVoxImplantAccount::IsPro())
		{
			$result["CRM_SOURCE"] = 'CALL';
			$result["CALL_VOTE"] = 'N';

			if ($result["QUEUE_TYPE"] == CVoxImplantConfig::QUEUE_TYPE_ALL)
			{
				$result["QUEUE_TYPE"] = CVoxImplantConfig::QUEUE_TYPE_EVENLY;
				$result["NO_ANSWER_RULE"] = CVoxImplantIncoming::RULE_VOICEMAIL;
			}
		}

		foreach(GetModuleEvents("voximplant", "onCallInit", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, Array(Array(
				'CALL_ID' => $params['CALL_ID'],
				'CALL_TYPE' => CVoxImplantMain::CALL_INCOMING,
				'ACCOUNT_SEARCH_ID' => $params['ACCOUNT_SEARCH_ID'],
				'PHONE_NUMBER' => $params['PHONE_NUMBER'],
				'CALLER_ID' => $params['CALLER_ID'],
			)));
		}

		return $result;
	}

	public static function Init($params)
	{
		CModule::IncludeModule('pull');

		// TODO check $params
		$result = Array('COMMAND' => CVoxImplantIncoming::RULE_QUEUE);
		$firstUserId = 0;

		$config = CVoxImplantConfig::GetConfigBySearchId($params['SEARCH_ID']);
		$call = VI\CallTable::getByCallId($params['CALL_ID']);
		$routeFound = false;
		if(!$call)
			return false;

		if (!$config)
		{
			$result['COMMAND'] = CVoxImplantIncoming::RULE_HUNGUP;
			return $result;
		}

		if($config['USE_SIP_TO'] == 'Y' && $params['SIP_TO'] != '')
		{
			if(preg_match('/^sip:(\d+)@/', $params['SIP_TO'], $matches))
			{
				$directCode = $matches[1];
				$userData = self::getUserByDirectCode($directCode, ($config['TIMEMAN'] == 'Y'));
				if(is_array($userData) && $userData['AVAILABLE'] == 'Y')
				{
					$result['COMMAND'] = CVoxImplantIncoming::RULE_WAIT;
					$result['TYPE_CONNECT'] = self::TYPE_CONNECT_SIP;
					$result['USER_ID'] = $userData['USER_ID'];
					$result['USER_HAVE_PHONE'] = $userData['USER_HAVE_PHONE'];
					$result['USER_HAVE_MOBILE'] = $userData['USER_HAVE_MOBILE'];
					$routeFound = true;
				}
			}
		}

		if(!$routeFound && $config['DIRECT_CODE'] == 'Y' && (int)$params['DIRECT_CODE'] > 0)
		{
			$directCode = (int)$params['DIRECT_CODE'];
			$userData = self::getUserByDirectCode($directCode, ($config['TIMEMAN'] == 'Y'));
			if(is_array($userData))
			{
				if($userData['AVAILABLE'] == 'Y')
				{
					$result['COMMAND'] = CVoxImplantIncoming::RULE_WAIT;
					$result['TYPE_CONNECT'] = self::TYPE_CONNECT_DIRECT;
					$result['USER_ID'] = $userData['USER_ID'];
					$result['USER_HAVE_PHONE'] = $userData['USER_HAVE_PHONE'];
					$result['USER_HAVE_MOBILE'] = $userData['USER_HAVE_MOBILE'];
					$routeFound = true;
				}
				else
				{
					$result['USER_ID'] = $userData['USER_ID'];
					if($config['DIRECT_CODE_RULE'] == self::RULE_VOICEMAIL)
					{
						$result['COMMAND'] = CVoxImplantIncoming::RULE_VOICEMAIL;
						return $result;
					}
					else if($config['DIRECT_CODE_RULE'] == self::RULE_PSTN)
					{
						$userPhone = CVoxImplantPhone::GetUserPhone($result['USER_ID']);
						if ($userPhone)
						{
							$result['COMMAND'] = CVoxImplantIncoming::RULE_PSTN;
							$result['PHONE_NUMBER'] = $userPhone;
						}
						else
						{
							$result['COMMAND'] = CVoxImplantIncoming::RULE_VOICEMAIL;
						}
						return $result;
					}
					else
					{
						$firstUserId = $result['USER_ID'];
					}
				}
			}
		}

		if(!$routeFound && $config['IVR'] == 'Y' && $config['IVR_ID'] > 0)
		{
			$ivr = new VI\Ivr\Ivr($config['IVR_ID']);
			$result['COMMAND'] = self::TYPE_CONNECT_IVR;
			$result['TYPE_CONNECT'] =  self::TYPE_CONNECT_IVR;
			$result['IVR'] = $ivr->toArray(true);
			$routeFound = true;
		}

		if(!$routeFound && $config['CRM'] == 'Y' && $config['CRM_FORWARD'] == 'Y')
		{
			$userData = self::getCrmResponsible($params['CALLER_ID'], ($result['TIMEMAN'] == 'Y'));
			if(is_array($userData))
			{
				if($userData['AVAILABLE'] == 'Y')
				{
					$result['COMMAND'] = CVoxImplantIncoming::RULE_WAIT;
					$result['TYPE_CONNECT'] = self::TYPE_CONNECT_CRM;
					$result['USER_ID'] = $userData['USER_ID'];
					$result['USER_HAVE_PHONE'] = $userData['USER_HAVE_PHONE'];
					$result['USER_HAVE_MOBILE'] = $userData['USER_HAVE_MOBILE'];
					$routeFound = true;
				}
				else
				{
					$result['USER_ID'] = $userData['USER_ID'];
					if($config['CRM_RULE'] == self::RULE_VOICEMAIL)
					{
						$result['COMMAND'] = CVoxImplantIncoming::RULE_VOICEMAIL;
						return $result;
					}
					else if($config['CRM_RULE'] == self::RULE_PSTN)
					{
						$userPhone = CVoxImplantPhone::GetUserPhone($result['USER_ID']);
						if ($userPhone)
						{
							$result['COMMAND'] = CVoxImplantIncoming::RULE_PSTN;
							$result['PHONE_NUMBER'] = $userPhone;
						}
						else
						{
							$result['COMMAND'] = CVoxImplantIncoming::RULE_VOICEMAIL;
						}
						return $result;
					}
					else
					{
						$firstUserId = $result['USER_ID'];
					}
				}
			}
		}

		$crmData = Array();
		$crmCreate = CVoxImplantConfig::CRM_CREATE_NONE;
		if ($config['CRM'] == 'Y')
		{
			$crmData = CVoxImplantCrmHelper::GetDataForPopup($params['CALL_ID'], $params['CALLER_ID']);

			if ($crmData['FOUND'] == 'N' && $config['CRM_CREATE'] == CVoxImplantConfig::CRM_CREATE_LEAD)
			{
				$crmCreate = CVoxImplantConfig::CRM_CREATE_LEAD;
			}
		}

		if (!$routeFound)
		{
			VI\CallTable::update($call['ID'], array(
				'QUEUE_ID' => $config['QUEUE_ID']
			));
			$call['QUEUE_ID'] = $config['QUEUE_ID'];
			$queueConfig = VI\Model\QueueTable::getById($config['QUEUE_ID'])->fetch();
			if ($queueConfig['TYPE'] == CVoxImplantConfig::QUEUE_TYPE_ALL)
			{
				$result = self::GetQueue(Array(
					'SEARCH_ID' => $params['SEARCH_ID'],
					'CALL_ID' => $params['CALL_ID'],
					'CALLER_ID' => $params['CALLER_ID'],
					'LAST_TYPE_CONNECT' => self::TYPE_CONNECT_QUEUE,
					'SEND_INVITE' => 'N',
					'CONFIG' => $config,
				));
			}
			else
			{
				$result = self::GetNextInQueue(Array(
					'SEARCH_ID' => $params['SEARCH_ID'],
					'CALL_ID' => $params['CALL_ID'],
					'CALLER_ID' => $params['CALLER_ID'],
					'LAST_USER_ID' => 0,
					'LAST_TYPE_CONNECT' => self::TYPE_CONNECT_QUEUE,
					'LAST_ANSWER_USER_ID' => 0,
					'FIRST_EXEC' => 'Y',
					'SEND_INVITE' => 'N',
					'CONFIG' => $config,
				));
			}

			$result['QUEUE'] = $queueConfig;
		}

		if((int)$result['USER_ID'] === 0)
		{
			// if responsible is not defined yet, then activity will be created for the first user of the default queue
			$queue = new VI\Queue($config['QUEUE_ID']);
			$result['USER_ID'] = $queue->getFirstUserId($config['TIMEMAN'] == 'Y');
		}

		if ($result['USER_ID'] > 0)
		{
			if ($crmCreate == CVoxImplantConfig::CRM_CREATE_LEAD)
			{
				$leadId = CVoxImplantCrmHelper::AddLead(Array(
					'USER_ID' => $result['USER_ID'],
					'PHONE_NUMBER' => $params['CALLER_ID'],
					'SEARCH_ID' => $params['SEARCH_ID'],
					'CRM_SOURCE' => $config['CRM_SOURCE'],
					'INCOMING' => true,
				));
				if ($leadId && $call)
				{
					CVoxImplantCrmHelper::attachLeadToCall($call['CALL_ID'], $leadId);
				}
			}

			if ($config['CRM'] == 'Y')
			{
				$activityId = CVoxImplantCrmHelper::AddCall(Array(
					'CALL_ID' => $params['CALL_ID'],
					'CALLER_ID' => $params['CALLER_ID'],
					'INCOMING' => ($params['CALLBACK_MODE'] ? CVoxImplantMain::CALL_CALLBACK : CVoxImplantMain::CALL_INCOMING),
					'USER_ID' => $result['USER_ID'],
					'DATE_CREATE' => new FieldType\DateTime()
				));

				if($activityId !== false && $call)
				{
					VI\CallTable::update(
						$call['ID'],
						array(
							'CRM_ACTIVITY_ID' => $activityId,
						)
					);
				}
			}
		}

		if($config['CRM'] == 'Y')
		{
			CVoxImplantCrmHelper::StartCallTrigger($call['CALL_ID']);
		}

		if ($result['COMMAND'] == CVoxImplantIncoming::RULE_WAIT)
		{
			if ($result['USER_ID'] > 0 || count($result['USERS']) > 0)
			{
				if ($call !== false)
				{
					$queueHistory = is_array($call['QUEUE_HISTORY']) ? $call['QUEUE_HISTORY'] : array();
					$queueHistory[] = $result['USER_ID'];
					VI\CallTable::update(
						$call['ID'],
						array(
							'USER_ID' => $result['USER_ID'],
							'QUEUE_HISTORY' => $queueHistory
						)
					);
				}

				$inviteList = Array();
				if ($result['TYPE_CONNECT'] == CVoxImplantIncoming::RULE_QUEUE && $config['QUEUE_TYPE'] == CVoxImplantConfig::QUEUE_TYPE_ALL )
				{
					$inviteList = $result['USERS'];
				}
				else
				{
					$inviteList[] = Array('USER_ID' => $result['USER_ID']);
				}

				foreach ($inviteList as $queueUser)
				{
					Bitrix\Voximplant\Integration\Pull::sendInvite(
						$queueUser['USER_ID'],
						$call['CALL_ID'],
						array(
							'PHONE_NAME' => $config['PHONE_TITLE'],
							'TYPE_CONNECT' => $result['TYPE_CONNECT'],
							'CONFIG' => Array(
								'RECORDING' => $config['RECORDING'],
								'CRM_CREATE' => $config['CRM_CREATE']
							),
							'CALLBACK_MODE' => $params['CALLBACK_MODE'],
						)
					);
				}
				$pullResult = true;
			}
			else
			{
				$pullResult = false;
			}

			if (!$pullResult)
			{
				$result['COMMAND'] = CVoxImplantIncoming::RULE_HUNGUP;
			}
		}

		if ($firstUserId > 0)
			$result['FIRST_USER_ID'] = $firstUserId;

		return $result;
	}

	public static function routeToQueue($params)
	{
		$callId = $params['CALL_ID'];
		$queueId = $params['QUEUE_ID'];

		$call = VI\CallTable::getByCallId($callId);
		if(!$call)
			return false;

		$queue = VI\Model\QueueTable::getById($queueId)->fetch();
		if(!$queue)
			return false;

		foreach ($call['QUEUE_HISTORY'] as $userId)
		{
			self::SendPullEvent(Array(
				'COMMAND' => 'timeout',
				'USER_ID' => intval($userId),
				'CALL_ID' => $callId,
				'MARK' => 'timeout_inc_5',
			));
		}

		VI\CallTable::update($call['ID'], array(
			'QUEUE_ID' => $queueId,
			'QUEUE_HISTORY' => array()
		));

		if($queue['TYPE'] == CVoxImplantConfig::QUEUE_TYPE_ALL)
		{
			return CVoxImplantIncoming::GetQueue(array(
				'CALL_ID' => $callId,
				'CALLER_ID' => $call['CALLER_ID'],
				'CONFIG' => CVoxImplantConfig::GetConfig($call['CONFIG_ID']),
				'QUEUE_ID' => $queueId
			));
		}
		else
		{
			return CVoxImplantIncoming::GetNextInQueue(array(
				'CALL_ID' => $callId,
				'CALLER_ID' => $call['CALLER_ID'],
				'CONFIG' => CVoxImplantConfig::GetConfig($call['CONFIG_ID']),
				'QUEUE_ID' => $queueId
			));
		}
	}

	public static function routeToUser($params)
	{
		$callId = $params['CALL_ID'];
		$call = VI\CallTable::getByCallId($callId);
		if(!$call)
			return false;

		$config = CVoxImplantConfig::GetConfig($call['CONFIG_ID']);
		if(!$config)
			return false;

		if(isset($params['USER_ID']))
		{
			$userInfo = self::getUserInfo($params['USER_ID']);
		}
		else if (isset($params['DIRECT_CODE']))
		{
			$userInfo = self::getUserByDirectCode($params['DIRECT_CODE']);
		}
		else
		{
			$result['COMMAND'] = CVoxImplantIncoming::RULE_HUNGUP;
			$result['HANGUP_REASON'] = 'Required parameter is not set';
			return $result;
		}

		if(!$userInfo)
		{
			$result['COMMAND'] = CVoxImplantIncoming::RULE_HUNGUP;
			$result['HANGUP_REASON'] = 'User is not found';
			return $result;
		}

		foreach ($call['QUEUE_HISTORY'] as $userId)
		{
			self::SendPullEvent(Array(
				'COMMAND' => 'timeout',
				'USER_ID' => intval($userId),
				'CALL_ID' => $callId,
				'MARK' => 'timeout_inc_6',
			));
		}

		$call['QUEUE_HISTORY'][] = $userInfo['USER_ID'];
		VI\CallTable::update($call['ID'], array(
			'USER_ID' => $userInfo['USER_ID'],
			'QUEUE_ID' => null,
			'QUEUE_HISTORY' => $call['QUEUE_HISTORY']
		));

		if ($call['CRM_LEAD'] > 0)
		{
			CVoxImplantCrmHelper::UpdateLead($call['CRM_LEAD'], Array('ASSIGNED_BY_ID' => $userInfo['USER_ID']));
		}
		CVoxImplantCrmHelper::UpdateCallResponsible($callId, $userInfo['USER_ID']);

		VI\Integration\Pull::sendInvite(
			$userInfo['USER_ID'],
			$call['CALL_ID'],
			array(
				'CALLER_ID' => $params['CALLER_ID'],
				'PHONE_NAME' => $config['PHONE_NAME'],
				'TYPE_CONNECT' => self::TYPE_CONNECT_USER,
				'CONFIG' => Array(
					'RECORDING' => $config['RECORDING']
				),
		));

		$result = array(
			'COMMAND' => CVoxImplantIncoming::RULE_WAIT,
			'TYPE_CONNECT' => self::TYPE_CONNECT_USER,
			'USER_ID' => $userInfo['USER_ID'],
			'USER_HAVE_PHONE' => $userInfo['USER_HAVE_PHONE'],
			'USER_HAVE_MOBILE' => $userInfo['USER_HAVE_MOBILE']
		);
		return $result;
	}

	public static function GetNextAction($params)
	{
		// TODO check $params
		$call = VI\CallTable::getByCallId($params['CALL_ID']);
		if (!$call)
		{
			$result['COMMAND'] =  CVoxImplantIncoming::RULE_HUNGUP;
			return $result;
		}
		else
		{
			$result['COMMAND'] = CVoxImplantIncoming::RULE_QUEUE;
		}

		$config = CVoxImplantConfig::GetConfig($call['CONFIG_ID']);
		$rule = self::TYPE_CONNECT_QUEUE;
		$nextQueueId = $config['QUEUE_ID'];
		if ($params['LAST_TYPE_CONNECT'] == self::TYPE_CONNECT_IVR && $config['CRM'] == 'Y' && $config['CRM_FORWARD'] == 'Y')
		{
			$userData = self::getCrmResponsible($params['CALLER_ID'], ($result['TIMEMAN'] == 'Y'));
			if(is_array($userData))
			{
				if($userData['AVAILABLE'] == 'Y')
				{
					$result['COMMAND'] = CVoxImplantIncoming::RULE_WAIT;
					$result['TYPE_CONNECT'] = self::TYPE_CONNECT_CRM;
					$result['USER_ID'] = $userData['USER_ID'];
					$result['USER_HAVE_PHONE'] = $userData['USER_HAVE_PHONE'];
					$result['USER_HAVE_MOBILE'] = $userData['USER_HAVE_MOBILE'];
				}
				else
				{
					$result['USER_ID'] = $userData['USER_ID'];
					if($config['CRM_RULE'] == self::RULE_VOICEMAIL)
					{
						$result['COMMAND'] = CVoxImplantIncoming::RULE_VOICEMAIL;
						return $result;
					}
					else if($config['CRM_RULE'] == self::RULE_PSTN)
					{
						$userPhone = CVoxImplantPhone::GetUserPhone($result['USER_ID']);
						if ($userPhone)
						{
							$result['COMMAND'] = CVoxImplantIncoming::RULE_PSTN;
							$result['PHONE_NUMBER'] = $userPhone;
						}
						else
						{
							$result['COMMAND'] = CVoxImplantIncoming::RULE_VOICEMAIL;
						}
						return $result;
					}
				}
			}
		}
		else if ($params['LAST_TYPE_CONNECT'] == self::TYPE_CONNECT_DIRECT)
		{
			$rule = $config['DIRECT_CODE_RULE'];
		}
		else if ($params['LAST_TYPE_CONNECT'] == self::TYPE_CONNECT_CRM)
		{
			$rule = $config['CRM_RULE'];
		}
		else if ($params['LAST_TYPE_CONNECT'] == self::TYPE_CONNECT_QUEUE)
		{
			// well.. QueueAll can lead here.
			// todo: get rid of heavy code duplication
			$currentQueue = new VI\Queue($call['QUEUE_ID']);
			$firstInQueue = $currentQueue->getFirstUserId($config['TIMEMAN'] == 'Y');
			if ($currentQueue->getNoAnswerRule() == CVoxImplantIncoming::RULE_PSTN_SPECIFIC)
			{
				if (strlen($currentQueue->getForwardNumber()) > 0)
				{
					$result['COMMAND'] = CVoxImplantIncoming::RULE_PSTN;
					$result['PHONE_NUMBER'] = NormalizePhone($currentQueue->getForwardNumber(), 1);
					$result['USER_ID'] = $firstInQueue;
				}
			}
			else if ($currentQueue->getNoAnswerRule() == CVoxImplantIncoming::RULE_NEXT_QUEUE)
			{
				$result['COMMAND'] = CVoxImplantIncoming::RULE_QUEUE;
				$nextQueueId = $currentQueue->getNextQueueId();
				VI\CallTable::update($call['ID'], array(
					'QUEUE_HISTORY' => array()
				));
			}
			else if ($currentQueue->getNoAnswerRule() != CVoxImplantIncoming::RULE_HUNGUP)
			{
				$result['COMMAND'] = CVoxImplantIncoming::RULE_VOICEMAIL;

				if ($currentQueue->getNoAnswerRule() == CVoxImplantIncoming::RULE_PSTN && $firstInQueue > 0)
				{
					$userPhone = CVoxImplantPhone::GetUserPhone($firstInQueue);
					if ($userPhone)
					{
						$result['COMMAND'] = CVoxImplantIncoming::RULE_PSTN;
						$result['PHONE_NUMBER'] = $userPhone;
						$result['USER_ID'] = $firstInQueue;
					}
				}
			}
			else
			{
				$result['COMMAND'] = CVoxImplantIncoming::RULE_HUNGUP;
			}
		}


		if ($rule == CVoxImplantIncoming::RULE_VOICEMAIL)
		{
			$result['COMMAND'] = CVoxImplantIncoming::RULE_VOICEMAIL;
			$result['USER_ID'] = $params['LAST_USER_ID'];
		}
		else if ($rule == CVoxImplantIncoming::RULE_PSTN)
		{
			$userPhone = CVoxImplantPhone::GetUserPhone($params['LAST_USER_ID']);
			if ($userPhone)
			{
				$result['COMMAND'] = CVoxImplantIncoming::RULE_PSTN;
				$result['PHONE_NUMBER'] = $userPhone;
				$result['USER_ID'] = $params['LAST_USER_ID'];
			}
			else
			{
				$result['COMMAND'] = CVoxImplantIncoming::RULE_VOICEMAIL;
				$result['USER_ID'] = $params['LAST_USER_ID'];
			}
		}

		if($result['COMMAND'] == CVoxImplantIncoming::RULE_WAIT)
		{
			VI\CallTable::update($call['ID'], array(
				'USER_ID' => $result['USER_ID'],
				'QUEUE_ID' => null,
				'QUEUE_HISTORY' => array()
			));

			Bitrix\Voximplant\Integration\Pull::sendInvite(
				$result['USER_ID'],
				$call['CALL_ID'],
				array(
					'PHONE_NAME' => $config['PHONE_TITLE'],
					'TYPE_CONNECT' => $result['TYPE_CONNECT'],
					'CONFIG' => Array(
						'RECORDING' => $config['RECORDING'],
						'CRM_CREATE' => $config['CRM_CREATE']
					),
				)
			);
		}

		if ($result['COMMAND'] == CVoxImplantIncoming::RULE_QUEUE)
		{
			VI\CallTable::update($call['ID'], array('QUEUE_ID' => $nextQueueId));
			$queueConfig = VI\Model\QueueTable::getById($nextQueueId)->fetch();

			if ($queueConfig['TYPE'] == CVoxImplantConfig::QUEUE_TYPE_ALL)
			{
				$result = self::GetQueue(Array(
					'SEARCH_ID' => $params['SEARCH_ID'],
					'CALL_ID' => $params['CALL_ID'],
					'CALLER_ID' => $params['CALLER_ID'],
					'LAST_USER_ID' => $params['LAST_USER_ID'],
					'LAST_TYPE_CONNECT' => self::TYPE_CONNECT_QUEUE,
					'CONFIG' => $config,
					'LOAD_QUEUE' => 'Y'
				));
			}
			else
			{
				$result = self::GetNextInQueue(Array(
					'SEARCH_ID' => $params['SEARCH_ID'],
					'CALL_ID' => $params['CALL_ID'],
					'CALLER_ID' => $params['CALLER_ID'],
					'LAST_USER_ID' => $params['LAST_USER_ID'],
					'LAST_TYPE_CONNECT' => self::TYPE_CONNECT_QUEUE,
					'LAST_ANSWER_USER_ID' => 0,
					'CONFIG' => $config,
				));
			}
		}

		if (isset($params['LAST_USER_ID']) && $params['LAST_USER_ID'] > 0)
		{
			self::SendPullEvent(Array(
				'COMMAND' => 'timeout',
				'USER_ID' => intval($params['LAST_USER_ID']),
				'CALL_ID' => $params['CALL_ID'],
				'MARK' => 'timeout_inc_1',
			));
		}

		return $result;
	}

	public static function GetNextInQueue($params)
	{
		$fistExec = isset($params['FIRST_EXEC']) && $params['FIRST_EXEC'] == 'Y';
		$sendInvite = !isset($params['SEND_INVITE']) || $params['SEND_INVITE'] == 'Y';

		$call = VI\CallTable::getByCallId($params['CALL_ID']);
		if(!$call)
		{
			$result['COMMAND'] = CVoxImplantIncoming::RULE_HUNGUP;
			return $result;
		}

		// TODO check $params
		$result = Array('COMMAND' => CVoxImplantIncoming::RULE_HUNGUP);

		if (!$fistExec)
		{
			if ($call['STATUS'] == VI\CallTable::STATUS_CONNECTED)
			{
				$result['COMMAND'] = CVoxImplantIncoming::RULE_TALK;
				return $result;
			}
		}

		if (isset($params['CONFIG']))
		{
			$config = $params['CONFIG'];
		}
		else
		{
			$config = CVoxImplantConfig::GetConfig($call['CONFIG_ID']);
		}

		$queueId = $call['QUEUE_ID'];
		$queue = new VI\Queue($queueId);

		if (isset($params['LAST_USER_ID']) && $params['LAST_USER_ID'] > 0)
		{
			self::SendPullEvent(array(
				'COMMAND' => 'timeout',
				'USER_ID' => intval($params['LAST_USER_ID']),
				'CALL_ID' => $params['CALL_ID'],
				'MARK' => 'timeout_inc_2',
			));
		}

		$filter = array(
			'=QUEUE_ID' => $queueId,
			'=USER.ACTIVE' => 'Y'
		);
		if (count($call['QUEUE_HISTORY']) > 0)
		{
			$filter['!=USER_ID'] = $call['QUEUE_HISTORY'];
		}
		if ($queue->getType() == CVoxImplantConfig::QUEUE_TYPE_EVENLY)
		{
			$order = Array('LAST_ACTIVITY_DATE' => 'asc');
		}
		else
		{
			$order = Array('ID' => 'asc');
		}
		$res = VI\Model\QueueUserTable::getList(Array(
			'select' => Array('ID', 'USER_ID', 'IS_ONLINE_CUSTOM', 'UF_VI_PHONE' => 'USER.UF_VI_PHONE'),
			'filter' => $filter,
			'order' => $order,
		));

		$findActiveUser = false;
		CModule::IncludeModule('pull');
		while($queueUser = $res->fetch())
		{
			$queueUser['USER_HAVE_MOBILE'] = CVoxImplantUser::hasMobile($queueUser['USER_ID']) ? 'Y': 'N';

			if ($queueUser['IS_ONLINE_CUSTOM'] != 'Y' && $queueUser['UF_VI_PHONE'] != 'Y' && $queueUser['USER_HAVE_MOBILE'] != 'Y')
			{
				continue;
			}

			if ($config['TIMEMAN'] == "Y" && !CVoxImplantUser::GetActiveStatusByTimeman($queueUser['USER_ID']))
			{
				continue;
			}

			$findActiveUser = true;

			$queue->touchUser($queueUser['USER_ID']);
			$call['QUEUE_HISTORY'][] = $queueUser['USER_ID'];
			VI\CallTable::update($call['ID'], array('QUEUE_HISTORY' => $call['QUEUE_HISTORY']));

			$result['COMMAND'] = CVoxImplantIncoming::RULE_WAIT;
			$result['TYPE_CONNECT'] = self::TYPE_CONNECT_QUEUE;
			$result['USER_ID'] = $queueUser['USER_ID'];
			$result['USER_HAVE_PHONE'] = $queueUser['UF_VI_PHONE'] == 'Y'? 'Y': 'N';
			$result['USER_HAVE_MOBILE'] = $queueUser['USER_HAVE_MOBILE'];

			if ($sendInvite)
			{
				$crmData = Array();
				if ($config['CRM'] == 'Y')
				{
					$crmData = CVoxImplantCrmHelper::GetDataForPopup($params['CALL_ID'], $params['CALLER_ID'], $queueUser['USER_ID']);
				}


				Bitrix\Voximplant\Integration\Pull::sendInvite(
					$queueUser['USER_ID'],
					$call['CALL_ID'],
					array(
						'PHONE_NAME' => $config['PHONE_TITLE'],
						'TYPE_CONNECT' => $result['TYPE_CONNECT'],
						'CONFIG' => array(
							'RECORDING' => $config['RECORDING'],
							'CRM_CREATE' => $config['CRM_CREATE']
						),
						'CRM' => $crmData,
					)
				);
			}
			break;
		}

		if ($findActiveUser)
		{
			$result['QUEUE'] = $queue->toArray();
		}
		else if ($queue->getNoAnswerRule() == self::RULE_QUEUE && count($call['QUEUE_HISTORY']) > 0)
		{
			// move to the head of the queue
			VI\CallTable::update($call['ID'], array(
				'QUEUE_HISTORY' => array()
			));

			if ($queue->getType() == CVoxImplantConfig::QUEUE_TYPE_ALL)
			{
				return self::GetQueue($params);
			}
			else
			{
				return self::GetNextInQueue($params);
			}
		}
		else if ($queue->getNoAnswerRule() == self::RULE_NEXT_QUEUE && $queue->getNextQueueId() > 0 && CVoxImplantAccount::IsPro())
		{
			// move call to the next queue, if NEXT_QUEUE_ID is set
			$nextQueueId = $queue->getNextQueueId();
			VI\CallTable::update($call['ID'], array(
				'QUEUE_HISTORY' => array(),
				'QUEUE_ID' => $nextQueueId
			));
			$call['QUEUE_ID'] = $nextQueueId;
			$nextQueue = new VI\Queue($nextQueueId);
			if ($nextQueue->getType() == CVoxImplantConfig::QUEUE_TYPE_ALL)
			{
				return self::GetQueue($params);
			}
			else
			{
				return self::GetNextInQueue($params);
			}

		}
		else
		{
			$userId = intval($params['LAST_ANSWER_USER_ID']) > 0? intval($params['LAST_ANSWER_USER_ID']): intval($params['LAST_USER_ID']);
			if ($userId <= 0)
			{
				$userId = $queue->getFirstUserId($config['TIMEMAN'] == 'Y');
				if ($userId)
				{
					$queue->touchUser($userId);
				}
			}

			if ($queue->getNoAnswerRule() == CVoxImplantIncoming::RULE_PSTN_SPECIFIC)
			{
				if (strlen($queue->getForwardNumber()) > 0)
				{
					$result['COMMAND'] = CVoxImplantIncoming::RULE_PSTN;
					$result['PHONE_NUMBER'] = NormalizePhone($queue->getForwardNumber(), 1);
					$result['USER_ID'] = $userId;
				}
			}
			else if ($queue->getNoAnswerRule() != CVoxImplantIncoming::RULE_HUNGUP)
			{
				$result['COMMAND'] = CVoxImplantIncoming::RULE_VOICEMAIL;
				$result['USER_ID'] = $userId;

				if ($queue->getNoAnswerRule() == CVoxImplantIncoming::RULE_PSTN && $userId > 0)
				{
					$userPhone = CVoxImplantPhone::GetUserPhone($userId);
					if ($userPhone)
					{
						$result['COMMAND'] = CVoxImplantIncoming::RULE_PSTN;
						$result['PHONE_NUMBER'] = $userPhone;
						$result['USER_ID'] = $userId;
					}
				}
			}
			else
			{
				$result['COMMAND'] = CVoxImplantIncoming::RULE_HUNGUP;
			}
		}
		return $result;
	}

	public static function GetQueue($params)
	{
		$loadQueue = !isset($params['LOAD_QUEUE']) || $params['LOAD_QUEUE'] == 'Y';
		$sendInvite = !isset($params['SEND_INVITE']) || $params['SEND_INVITE'] == 'Y';

		// TODO check $params
		$result = Array('COMMAND' => CVoxImplantIncoming::RULE_HUNGUP);

		$call = VI\CallTable::getByCallId($params['CALL_ID']);
		if(!$call)
		{
			$result['COMMAND'] = CVoxImplantIncoming::RULE_HUNGUP;
			return $result;
		}

		if (isset($params['CONFIG']))
		{
			$config = $params['CONFIG'];
		}
		else
		{
			$config = CVoxImplantConfig::GetConfig($call['CONFIG_ID']);
		}

		$queueId = $call['QUEUE_ID'];
		$queue = new VI\Queue($queueId);
		$excludedUsers = is_array($call['QUEUE_HISTORY']) ? $call['QUEUE_HISTORY'] : array();

		if (isset($params['LAST_USER_ID']) && $params['LAST_USER_ID'] > 0)
		{
			self::SendPullEvent(Array(
				'COMMAND' => 'timeout',
				'USER_ID' => intval($params['LAST_USER_ID']),
				'CALL_ID' => $params['CALL_ID'],
				'MARK' => 'timeout_inc_3',
			));
			if(!in_array($params['LAST_USER_ID'], $call['QUEUE_HISTORY']))
			{
				$excludedUsers[] = $params['LAST_USER_ID'];
			}
		}

		$res = VI\Model\QueueUserTable::getList(Array(
			'select' => Array('ID', 'USER_ID', 'IS_ONLINE_CUSTOM', 'UF_VI_PHONE' => 'USER.UF_VI_PHONE', 'ACTIVE' => 'USER.ACTIVE'),
			'filter' => Array('=QUEUE_ID' => $queueId, '=ACTIVE' => 'Y', '!=USER_ID' => $excludedUsers),
			'order' => Array('LAST_ACTIVITY_DATE' => 'asc'),
		));

		$findUserId = 0;
		if ($loadQueue)
		{
			CModule::IncludeModule('pull');
			while($queueUser = $res->fetch())
			{
				$queueUser['USER_HAVE_MOBILE'] = CVoxImplantUser::hasMobile($queueUser['USER_ID']);
				if ($queueUser['IS_ONLINE_CUSTOM'] != 'Y' && $queueUser['UF_VI_PHONE'] != 'Y' && $queueUser['USER_HAVE_MOBILE'] != 'Y')
				{
					continue;
				}

				if ($config['TIMEMAN'] == "Y" && !CVoxImplantUser::GetActiveStatusByTimeman($queueUser['USER_ID']))
				{
					continue;
				}

				if (!$findUserId)
				{
					$queue->touchUser($queueUser['USER_ID']);
					$findUserId = $queueUser['ID'];
					$result['USER_ID'] = $queueUser['USER_ID'];
				}


				$result['COMMAND'] = CVoxImplantIncoming::RULE_WAIT;
				$result['TYPE_CONNECT'] = self::TYPE_CONNECT_QUEUE;
				$result['USERS'][] = Array(
					'USER_ID' => $queueUser['USER_ID'],
					'USER_HAVE_PHONE' => $queueUser['UF_VI_PHONE'] == 'Y'? 'Y': 'N',
					'USER_HAVE_MOBILE' => $queueUser['USER_HAVE_MOBILE']
				);
			}
		}
		else
		{
			while($queueUser = $res->fetch())
			{
				if (intval($params['LAST_USER_ID']) == $queueUser['USER_ID'])
				{
					continue;
				}
				else
				{
					self::SendPullEvent(Array(
						'COMMAND' => 'timeout',
						'USER_ID' => $queueUser['USER_ID'],
						'CALL_ID' => $params['CALL_ID'],
						'MARK' => 'timeout_inc_4',
					));
				}
			}
		}

		if($findUserId)
		{
			if ($sendInvite)
			{
				$crmData = Array();
				$queueHistory = array();
				foreach ($result['USERS'] as $queueUser)
				{
					if ($config['CRM'] == 'Y')
					{
						$crmData = CVoxImplantCrmHelper::GetDataForPopup($params['CALL_ID'], $params['CALLER_ID'], $queueUser['USER_ID']);
					}
					Bitrix\Voximplant\Integration\Pull::sendInvite(
						$queueUser['USER_ID'],
						$call['CALL_ID'],
						array(
							'PHONE_NAME' => $config['PHONE_TITLE'],
							'TYPE_CONNECT' => $result['TYPE_CONNECT'],
							'CONFIG' => Array(
								'RECORDING' => $config['RECORDING']
							),
							'CRM' => $crmData,
						)
					);
					self::SendPullEvent(Array(
					));
					$queueHistory[] = $queueUser['USER_ID'];
				}
				VI\CallTable::update($call['ID'], array('QUEUE_HISTORY' => $queueHistory));
			}
		}
		else
		{
			$userId = intval($params['LAST_USER_ID']);
			if ($userId <= 0)
			{
				$userId = $queue->getFirstUserId($config['TIMEMAN'] == 'Y');
				if ($userId)
				{
					$queue->touchUser($userId);
				}
			}

			if ($queue->getNoAnswerRule() == CVoxImplantIncoming::RULE_PSTN_SPECIFIC)
			{
				if (strlen($config['FORWARD_NUMBER']) > 0)
				{
					$result['COMMAND'] = CVoxImplantIncoming::RULE_PSTN;
					$result['PHONE_NUMBER'] = NormalizePhone($config['FORWARD_NUMBER'], 1);
					$result['USER_ID'] = $userId;
				}
			}
			else if ($queue->getNoAnswerRule() != CVoxImplantIncoming::RULE_HUNGUP)
			{
				$result['COMMAND'] = CVoxImplantIncoming::RULE_VOICEMAIL;
				$result['USER_ID'] = $userId;

				if ($queue->getNoAnswerRule() == CVoxImplantIncoming::RULE_PSTN && $userId > 0)
				{
					$userPhone = CVoxImplantPhone::GetUserPhone($userId);
					if ($userPhone)
					{
						$result['COMMAND'] = CVoxImplantIncoming::RULE_PSTN;
						$result['PHONE_NUMBER'] = $userPhone;
						$result['USER_ID'] = $userId;
					}
				}
			}
			else
			{
				$result['COMMAND'] = CVoxImplantIncoming::RULE_HUNGUP;
			}
		}
		return $result;
	}

	public static function SendPullEvent($params)
	{
		// TODO check $params
		if (!CModule::IncludeModule('pull') || !CPullOptions::GetQueueServerStatus() || $params['USER_ID'] <= 0)
			return false;

		$config = Array();
		$push = Array();
		$callId = $params['CALL_ID'];
		if ($params['COMMAND'] == 'invite')
		{
			$config = Array(
				"callId" => $params['CALL_ID'],
				"callerId" => $params['CALLER_ID'],
				"phoneNumber" => $params['PHONE_NAME'],
				"chatId" => 0,
				"chat" => array(),
				"typeConnect" => $params['TYPE_CONNECT'],
				"portalCall" => $params['PORTAL_CALL'] == 'Y'? true: false,
				"portalCallUserId" => $params['PORTAL_CALL'] == 'Y'? $params['PORTAL_CALL_USER_ID']: 0,
				"portalCallData" => $params['PORTAL_CALL'] == 'Y'? $params['PORTAL_CALL_DATA']: Array(),
				"config" => $params['CONFIG']? $params['CONFIG']: Array(),
				"CRM" => $params['CRM'],
				"isCallback" => $params['CALLBACK_MODE']
			);

			$callName = $params['CALLER_ID'];
			if (isset($params['CRM']['CONTACT']['NAME']) && strlen($params['CRM']['CONTACT']['NAME']) > 0)
			{
				$callName = $params['CRM']['CONTACT']['NAME'];
			}
			if (isset($params['CRM']['COMPANY']) && strlen($params['CRM']['COMPANY']) > 0)
			{
				$callName .= ' ('.$params['CRM']['COMPANY'].')';
			}
			else if (isset($params['CRM']['CONTACT']['POST']) && strlen($params['CRM']['CONTACT']['POST']) > 0)
			{
				$callName .= ' ('.$params['CRM']['CONTACT']['POST'].')';
			}

			$push['sub_tag'] = 'VI_CALL_'.$params['CALL_ID'];
			$push['send_immediately'] = 'Y';
			$push['sound'] = 'call.aif';
			$push['advanced_params'] = Array(
				"notificationsToCancel" => array('VI_CALL_'.$params['CALL_ID']),
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
				'ACTION' => 'VI_CALL_'.$params['CALL_ID'],
				'PARAMS' => $config
			);
		}
		else if ($params['COMMAND'] == 'update_crm')
		{
			$call = VI\CallTable::getByCallId($callId);
			$config = Array(
				"callId" => $params['CALL_ID'],
				"CRM" => $params['CRM'],
			);
			if(is_array($call))
			{
				$config["showCrmCard"] = ($call['CRM'] == 'Y');
				$config["crmEntityType"] = $call['CRM_ENTITY_TYPE'];
				$config["crmEntityId"] = $call['CRM_ENTITY_ID'];
				$config["crmActivityId"] = $call['CRM_ACTIVITY_ID'];
				$config["crmActivityEditUrl"] = CVoxImplantCrmHelper::getActivityEditUrl($call['CRM_ACTIVITY_ID']);
			}
		}
		else if ($params['COMMAND'] == 'timeout' || $params['COMMAND'] == 'answer_self')
		{
			$config = Array(
				"callId" => $params['CALL_ID'],
			);
			$push['send_immediately'] = 'Y';
			$push['advanced_params'] = Array(
				"notificationsToCancel" => array('VI_CALL_'.$params['CALL_ID']),
			);
		}
		if (isset($params['MARK']))
		{
			$config['mark'] = $params['MARK'];
		}
		CPullStack::AddByUser($params['USER_ID'],
			Array(
				'module_id' => 'voximplant',
				'command' => $params['COMMAND'],
				'params' => $config,
				'push' => $push
			)
		);

		return true;
	}

	public static function SendCommand($params)
	{
		// TODO check $params
		$call = VI\CallTable::getByCallId($params['CALL_ID']);
		if (!$call)
			return false;

		global $USER;

		$answer['COMMAND'] = $params['COMMAND'];
		$answer['OPERATOR_ID'] = $params['OPERATOR_ID']? $params['OPERATOR_ID']: $USER->GetId();
		if ($params['COMMAND'] == CVoxImplantIncoming::RULE_WAIT)
		{
		}
		else if ($params['COMMAND'] == CVoxImplantIncoming::RULE_QUEUE)
		{
		}
		else if ($params['COMMAND'] == CVoxImplantIncoming::RULE_PSTN)
		{
			$answer['PHONE_NUMBER'] = '';
		}
		else if ($params['COMMAND'] == CVoxImplantIncoming::RULE_USER)
		{
			$answer['USER_ID'] = intval($params['USER_ID']);
		}
		else if ($params['COMMAND'] == CVoxImplantIncoming::RULE_VOICEMAIL)
		{
			$answer['USER_ID'] = intval($params['USER_ID']);
		}
		else if ($params['COMMAND'] == CVoxImplantIncoming::COMMAND_BUSY)
		{
		}
		else
		{
			$answer['COMMAND'] = CVoxImplantIncoming::RULE_HUNGUP;
		}

		if(isset($params['DEBUG_INFO']))
		{
			$answer['DEBUG_INFO'] = $params['DEBUG_INFO'];
		}

		$http = new \Bitrix\Main\Web\HttpClient(array(
			'waitResponse' => false
		));
		$http->post($call['ACCESS_URL'], json_encode($answer));

		return true;
	}

	public static function Answer($callId)
	{
		$res = VI\CallTable::getList(Array(
			'select' => Array('ID', 'ACCESS_URL'),
			'filter' => Array('=CALL_ID' => $callId),
		));
		$call = $res->fetch();
		if (!$call)
			return false;

		global $USER;

		$ViMain = new CVoxImplantMain($USER->GetId());
		$result = $ViMain->GetDialogInfo($_POST['NUMBER']);

		if ($result)
		{
			echo CUtil::PhpToJsObject(Array(
				'DIALOG_ID' => $result['DIALOG_ID'],
				'HR_PHOTO' => $result['HR_PHOTO'],
				'ERROR' => ''
			));
		}
		else
		{
			echo CUtil::PhpToJsObject(Array(
				'CODE' => $ViMain->GetError()->code,
				'ERROR' => $ViMain->GetError()->msg
			));
		}
	}

	public static function RegisterCall($config, $params)
	{
		$call = VI\CallTable::getByCallId($params['CALL_ID']);
		if($call)
		{
			VI\CallTable::update($call['ID'], array(
				'CONFIG_ID' => $config['ID'],
				'USER_ID' => 0,
				'STATUS' => Bitrix\Voximplant\CallTable::STATUS_CONNECTING,
				'ACCESS_URL' => $params['ACCESS_URL'],
			));
		}
		else
		{
			$call = array(
				'CONFIG_ID' => $config['ID'],
				'CALL_ID' => $params['CALL_ID'],
				'USER_ID' => 0,
				'CALLER_ID' => $params['CALLER_ID'],
				'STATUS' => Bitrix\Voximplant\CallTable::STATUS_CONNECTING,
				'CRM' => $config['CRM'],
				'ACCESS_URL' => $params['ACCESS_URL'],
				'DATE_CREATE' => new Bitrix\Main\Type\DateTime(),
			);
			$insertResult = Bitrix\Voximplant\CallTable::add($call);
			if($insertResult->isSuccess())
			{
				$call['ID'] = $insertResult->getId();
			}
			else
			{
				$call = false;
			}
		}

		if ($call && $config['CRM'] == 'Y')
		{
			$crmData = CVoxImplantCrmHelper::GetCrmEntity($params['CALLER_ID'], 0, false);
			if(is_array($crmData))
			{
				$callCrmFields['CRM_ENTITY_TYPE'] = $crmData['ENTITY_TYPE_NAME'];
				$callCrmFields['CRM_ENTITY_ID'] = $crmData['ENTITY_ID'];
				VI\CallTable::update($call['ID'], $callCrmFields);
			}
		}

		if ($config['WORKTIME_SKIP_CALL'] == 'Y')
		{
			$config['WORKTIME_USER_ID'] = 0;
			if ($config['CRM'] == 'Y')
			{
				$existsLead = false;
				$crmData = CVoxImplantCrmHelper::GetDataForPopup($params['CALL_ID'], $params['CALLER_ID']);
				if (isset($crmData['RESPONSIBILITY']['ID']) && $crmData['RESPONSIBILITY']['ID'] > 0)
				{
					$config['WORKTIME_USER_ID'] = $crmData['RESPONSIBILITY']['ID'];
					$existsLead = true;
				}
				else
				{
					$queue = new VI\Queue($config['QUEUE_ID']);
					$queueUserId = $queue->getFirstUserId($config['TIMEMAN'] == 'Y');
					
					if ($queueUserId)
					{
						$queue->touchUser($queueUserId);
						$config['WORKTIME_USER_ID'] = $queueUserId;
					}

					if ($config['WORKTIME_USER_ID'] > 0 && $crmData['FOUND'] == 'N' && $config['CRM_CREATE'] == CVoxImplantConfig::CRM_CREATE_LEAD)
					{
						$leadId = CVoxImplantCrmHelper::AddLead(Array(
							'USER_ID' => $config['WORKTIME_USER_ID'],
							'PHONE_NUMBER' => $params['CALLER_ID'],
							'SEARCH_ID' => $config['SEARCH_ID'],
							'CRM_SOURCE' => $config['CRM_SOURCE'],
							'INCOMING' => true,
						));
						if ($leadId)
						{
							$existsLead = true;
							if($call['CALL_ID'] != '')
							{
								CVoxImplantCrmHelper::attachLeadToCall($call['CALL_ID'], $leadId);
							}
						}
					}
				}

				if ($existsLead)
				{
					$activityId = CVoxImplantCrmHelper::AddCall(Array(
						'CALL_ID' => $params['CALL_ID'],
						'CALLER_ID' => $params['CALLER_ID'],
						'INCOMING' => CVoxImplantMain::CALL_INCOMING,
						'USER_ID' => $config['WORKTIME_USER_ID'],
						'DATE_CREATE' => new FieldType\DateTime()
					));
					if($activityId)
					{
						if($call['ID'])
						{
							VI\CallTable::update($call['ID'], array(
								'CRM_ACTIVITY_ID' => $activityId
							));
						}
						CVoxImplantCrmHelper::UpdateCall(Array(
							'CALL_ID' => $params['CALL_ID'],
							'CRM_ACTIVITY_ID' => $activityId,
							'PHONE_NUMBER' => $params['CALLER_ID'],
							'INCOMING' => CVoxImplantMain::CALL_INCOMING,
							'USER_ID' => $config['WORKTIME_USER_ID'],
							'DESCRIPTION' => GetMessage("WORKTIME_CALL")
						));
					}
					CVoxImplantCrmHelper::StartCallTrigger($params['CALL_ID']);
				}
			}
			else
			{
				$queue = new VI\Queue($config['QUEUE_ID']);
				$queueUserId = $queue->getFirstUserId($config['TIMEMAN'] == 'Y');
				if($queueUserId)
				{
					$queue->touchUser($queueUserId);
					$config['WORKTIME_USER_ID'] = $queueUserId;
				}
			}
		}

		return $config;
	}

	public static function IsNumberInBlackList($number)
	{
		$dbBlacklist = VI\BlacklistTable::getList(
			array(
				"filter" => array("PHONE_NUMBER" => $number)
			)
		);
		if ($dbBlacklist->fetch())
		{
			return true;
		}

		return false;
	}

	public static function CheckNumberForBlackList($number)
	{
		$blackListTime = Bitrix\Main\Config\Option::get("voximplant", "blacklist_time", 5);
		$blackListCount = Bitrix\Main\Config\Option::get("voximplant", "blacklist_count", 5);

		$minTime = new Bitrix\Main\Type\DateTime();
		$minTime->add('-'.$blackListTime.' minutes');

		$dbData = VI\StatisticTable::getList(array(
			'filter' => array(
				"PHONE_NUMBER" => $number,
				'>CALL_START_DATE' => $minTime,
			),
			'select' => array('ID')
		));

		$callsCount = 0;
		while($dbData->fetch())
		{
			$callsCount++;
			if ($callsCount >= $blackListCount)
			{
				$number = substr($number, 0, 20);
				VI\BlacklistTable::add(array(
					"PHONE_NUMBER" => $number
				));

				$messageUserId = Bitrix\Main\Config\Option::get("voximplant", "blacklist_user_id", "");
				CVoxImplantHistory::SendMessageToChat(
					$messageUserId,
					$number,
					CVoxImplantMain::CALL_INCOMING,
					GetMessage("BLACKLIST_NUMBER")
				);

				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $phoneNumber
	 * @param bool $checkTimeman
	 * @return array|false
	 */
	public static function getCrmResponsible($phoneNumber, $checkTimeman = false)
	{
		$crmEntity = CVoxImplantCrmHelper::GetCrmEntity($phoneNumber, 0, false);
		if(!$crmEntity)
			return false;

		$responsibleId = $crmEntity['ASSIGNED_BY_ID'];

		$result = self::getUserInfo($responsibleId, $checkTimeman);

		$result['CRM_ENTITY_TYPE'] = $crmEntity['ENTITY_TYPE_NAME'];
		$result['CRM_ENTITY_ID'] = $crmEntity['ENTITY_ID'];

		return $result;
	}

	public static function getUserByDirectCode($directCode, $checkTimeman = false)
	{
		$directCode = (int)$directCode;
		$userData = CVoxImplantUser::GetList(Array(
			'select' => Array('ID', 'IS_ONLINE_CUSTOM', 'UF_VI_PHONE', 'ACTIVE'),
			'filter' => Array('=UF_PHONE_INNER' => $directCode, '=ACTIVE' => 'Y'),
		))->fetch();
		if (!$userData)
			return false;

		$userId = $userData['ID'];

		$hasMobile = CVoxImplantUser::hasMobile($userId);
		$skipByTimeman = false;
		if ($checkTimeman)
		{
			$skipByTimeman = !CVoxImplantUser::GetActiveStatusByTimeman($userId);
		}

		$result = array(
			'USER_ID' => $userData['ID'],
			'USER_HAVE_PHONE' => $userData['UF_VI_PHONE'] == 'Y' ? 'Y' : 'N',
			'USER_HAVE_MOBILE' => $hasMobile ? 'Y' : 'N',
			'ONLINE' => $userData['IS_ONLINE_CUSTOM'],
			'AVAILABLE' => (!$skipByTimeman && ($userData['IS_ONLINE_CUSTOM'] == 'Y' || $userData['UF_VI_PHONE'] == 'Y' || $userData['USER_HAVE_MOBILE'] == 'Y')) ? 'Y' : 'N',
		);

		return $result;
	}

	/**
	 * @param $userId
	 * @param bool $checkTimeman
	 * @return array|bool
	 */
	public static function getUserInfo($userId, $checkTimeman = false)
	{
		$userData = CVoxImplantUser::GetList(Array(
			'select' => Array('ID', 'IS_ONLINE_CUSTOM', 'UF_VI_PHONE', 'ACTIVE'),
			'filter' => Array('=ID' => $userId,  '=ACTIVE' => 'Y'),
		))->fetch();

		if (!$userData)
			return false;

		$hasMobile = CVoxImplantUser::hasMobile($userId);
		$skipByTimeman = false;
		if ($checkTimeman)
		{
			$skipByTimeman = !CVoxImplantUser::GetActiveStatusByTimeman($userId);
		}

		$result = array(
			'USER_ID' => $userData['ID'],
			'USER_HAVE_PHONE' => $userData['UF_VI_PHONE'] == 'Y' ? 'Y' : 'N',
			'USER_HAVE_MOBILE' => $hasMobile ? 'Y' : 'N',
			'ONLINE' => $userData['IS_ONLINE_CUSTOM'],
			'AVAILABLE' => (!$skipByTimeman && ($userData['IS_ONLINE_CUSTOM'] == 'Y' || $userData['UF_VI_PHONE'] == 'Y' || $userData['USER_HAVE_MOBILE'] == 'Y')) ? 'Y' : 'N',
		);

		return $result;
	}


}
?>
