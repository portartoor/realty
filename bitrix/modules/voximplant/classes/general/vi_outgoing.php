<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Bitrix\Main\Type as FieldType;
use Bitrix\Voximplant as VI;
use Bitrix\Voximplant\Tts;
use Bitrix\Voximplant\CallTable;

class CVoxImplantOutgoing
{
	const INFOCALL_MODE_TEXT = 'text';
	const INFOCALL_MODE_URL = 'url';

	public static function Init($params)
	{
		$result['STATUS'] = 'OK';
		$result['PORTAL_CALL'] = 'N';

		if (strlen($params['PHONE_NUMBER']) > 0 && strlen($params['PHONE_NUMBER']) <= 4)
		{
			$res = CVoxImplantUser::GetList(Array(
				'select' => Array('ID', 'IS_ONLINE_CUSTOM', 'UF_VI_PHONE', 'ACTIVE'),
				'filter' => Array('=UF_PHONE_INNER' => intval($params['PHONE_NUMBER']), '=ACTIVE' => 'Y'),
			));
			if ($userData = $res->fetch())
			{
				$result['PORTAL_CALL'] = 'Y';
				$result['USER_ID'] = $userData['ID'];
				$result['COMMAND'] = CVoxImplantIncoming::RULE_HUNGUP;

				if (CModule::IncludeModule('pull'))
				{
					$orm = \Bitrix\Pull\PushTable::getList(Array(
						'select' => Array('ID'),
						'filter' => Array('=USER_ID' => $userData['ID']),
					));
					$userData['USER_HAVE_MOBILE'] = $orm->fetch()? 'Y': 'N';
				}
				else
				{
					$userData['USER_HAVE_MOBILE'] = 'N';
				}

				if ($userData['ID'] == $params['USER_ID'])
				{
					$result['COMMAND'] = CVoxImplantIncoming::RULE_HUNGUP;
				}
				else if ($userData['IS_ONLINE_CUSTOM'] == 'Y' || $userData['UF_VI_PHONE'] == 'Y' || $userData['USER_HAVE_MOBILE'] == 'Y')
				{
					$result['COMMAND'] = CVoxImplantIncoming::RULE_WAIT;
					$result['TYPE_CONNECT'] = CVoxImplantIncoming::TYPE_CONNECT_DIRECT;
					$result['USER_HAVE_PHONE'] = $userData['UF_VI_PHONE'] == 'Y'? 'Y': 'N';
					$result['USER_HAVE_MOBILE'] = $userData['USER_HAVE_MOBILE'];
					$result['USER_SHORT_NAME'] = '';
				}
			}
		}

		if($result['PORTAL_CALL'] === 'N')
		{
			$callAllowed = VI\Security\Helper::canUserCallNumber(
				$params['USER_ID'],
				$params['PHONE_NUMBER']
			);

			if(!$callAllowed)
			{
				$result['STATUS'] = 'ERROR';
				$result['error'] = new CVoxImplantError(
					__METHOD__,
					'CALL_IS_NOT_ALLOWED',
					'User is not allowed to call specified number'
				);
				return $result;
			}
		}

		$callAdd = true;
		if ($params['CALL_ID_TMP'])
		{
			$call = VI\CallTable::getByCallId($params['CALL_ID_TMP']);
			if($call)
			{
				$callFields = Array(
					'CONFIG_ID' => $params['CONFIG_ID'],
					'CALL_ID' => $params['CALL_ID'],
					'CRM' => $params['CRM'],
					'USER_ID' => $params['USER_ID'],
					'CALLER_ID' => $params['PHONE_NUMBER'],
					'STATUS' => VI\CallTable::STATUS_CONNECTING,
					'ACCESS_URL' => $params['ACCESS_URL'],
					'PORTAL_USER_ID' => $result['PORTAL_CALL'] == 'Y' ? $result['USER_ID'] : 0,
				);

				if($params['CRM_ENTITY_TYPE'] && $params['CRM_ENTITY_ID'])
				{
					$callFields['CRM_ENTITY_TYPE'] = $params['CRM_ENTITY_TYPE'];
					$callFields['CRM_ENTITY_ID'] = $params['CRM_ENTITY_ID'];
				}

				if($params['CRM_ACTIVITY_ID'])
					$callFields['CRM_ACTIVITY_ID'] = $params['CRM_ACTIVITY_ID'];

				if($params['CRM_CALL_LIST'])
					$callFields['CRM_CALL_LIST'] = $params['CRM_CALL_LIST'];

				$updateResult = VI\CallTable::update($call['ID'], $callFields);
				if ($updateResult->isSuccess())
				{
					$callAdd = false;
				}
			}
		}
		if ($callAdd)
		{
			$call = array(
				'INCOMING' => CVoxImplantMain::CALL_OUTGOING,
				'CONFIG_ID' => $params['CONFIG_ID'],
				'CALL_ID' => $params['CALL_ID'],
				'CRM' => $params['CRM'],
				'CRM_ENTITY_TYPE' => ($params['CRM_ENTITY_TYPE'] ? $params['CRM_ENTITY_TYPE'] : null),
				'CRM_ENTITY_ID' => ($params['CRM_ENTITY_ID'] ? $params['CRM_ENTITY_ID'] : null),
				'CRM_ACTIVITY_ID' => ($params['CRM_ACTIVITY_ID'] ? $params['CRM_ACTIVITY_ID'] : null),
				'CRM_CALL_LIST' => ($params['CRM_CALL_LIST'] ? $params['CRM_CALL_LIST'] : null),
				'USER_ID' => $params['USER_ID'],
				'CALLER_ID' => $params['PHONE_NUMBER'],
				'STATUS' => VI\CallTable::STATUS_CONNECTING,
				'ACCESS_URL' => $params['ACCESS_URL'],
				'PORTAL_USER_ID' => $result['PORTAL_CALL'] == 'Y'? $result['USER_ID']: 0,
				'DATE_CREATE' => new FieldType\DateTime(),
			);
			$addResult = VI\CallTable::add($call);
			$call['ID'] = $addResult->getId();
		}

		$config = self::GetConfigByUserId($params['USER_ID']);

		if ($params['CRM'] == 'Y' && $result['PORTAL_CALL'] == 'N')
		{
			$crmEntitySet = false;
			if($params['CRM_ENTITY_TYPE'] && $params['CRM_ENTITY_ID'])
			{
				$crmEntitySet = true;
			}
			else
			{
				$crmData = CVoxImplantCrmHelper::GetCrmEntity($params['PHONE_NUMBER'], 0, false);
				if(is_array($crmData))
				{
					$callCrmFields['CRM_ENTITY_TYPE'] = $crmData['ENTITY_TYPE_NAME'];
					$callCrmFields['CRM_ENTITY_ID'] = $crmData['ENTITY_ID'];
					VI\CallTable::updateWithCallId($params['CALL_ID'], $callCrmFields);
					$crmEntitySet = true;
				}
			}

			if (!$crmEntitySet && $config['CRM_CREATE'] == CVoxImplantConfig::CRM_CREATE_LEAD)
			{
				$leadId = CVoxImplantCrmHelper::AddLead(Array(
					'USER_ID' => $params['USER_ID'],
					'PHONE_NUMBER' => $params['PHONE_NUMBER'],
					'SEARCH_ID' => $config['SEARCH_ID'],
					'CRM_SOURCE' => $config['CRM_SOURCE'],
					'INCOMING' => false,
				));

				if($leadId)
				{
					CVoxImplantCrmHelper::attachLeadToCall($params['CALL_ID'], $leadId);
				}
			}

			if((int)$params['CRM_ACTIVITY_ID'] === 0 || CVoxImplantCrmHelper::GetCallIdByActivityId($params['CRM_ACTIVITY_ID']) !== false)
			{
				$activityId = CVoxImplantCrmHelper::AddCall(Array(
					'CALL_ID' => $params['CALL_ID'],
					'CALLER_ID' => $params['PHONE_NUMBER'],
					'INCOMING' => CVoxImplantMain::CALL_OUTGOING,
					'USER_ID' => $params['USER_ID'],
					'DATE_CREATE' => new FieldType\DateTime()
				));

				VI\CallTable::updateWithCallId(
					$params['CALL_ID'],
					array(
						'CRM_ACTIVITY_ID' => $activityId
					)
				);
				$params['CRM_ACTIVITY_ID'] = $activityId;
			}

			$crmData = CVoxImplantCrmHelper::GetDataForPopup($params['CALL_ID'], $params['PHONE_NUMBER'], $params['USER_ID']);
		}
		else
		{
			$crmData = Array();
		}

		CVoxImplantHistory::WriteToLog(Array(
			'COMMAND' => 'outgoing',
			'USER_ID' => $params['USER_ID'],
			'CALL_ID' => $params['CALL_ID'],
			'CALL_ID_TMP' => $params['CALL_ID_TMP'],
			'CALL_DEVICE' => $params['CALL_DEVICE'],
			'PHONE_NUMBER' => $params['PHONE_NUMBER'],
			'EXTERNAL' => $params['CALL_ID_TMP']? true: false,
			'PORTAL_CALL' => $result['PORTAL_CALL'],
			'PORTAL_CALL_USER_ID' => $params['USER_ID'],
			'CRM' => $crmData,
			'CRM_ENTITY_TYPE' => $params['CRM_ENTITY_TYPE'],
			'CRM_ENTITY_ID' => $params['CRM_ENTITY_ID'],
			'CRM_ACTIVITY_ID' => $params['CRM_ACTIVITY_ID'],
		));

		$portalUser = Array();
		if ($result['PORTAL_CALL'] == 'Y')
		{
			if (CModule::IncludeModule('im'))
			{
				$portalUser = CIMContactList::GetUserData(Array('ID' => Array($params['USER_ID'], $result['USER_ID']), 'DEPARTMENT' => 'N', 'HR_PHOTO' => 'Y'));
			}
			else
			{
				$portalUser = Array();
			}
		}

		self::SendPullEvent(Array(
			'COMMAND' => 'outgoing',
			'USER_ID' => $params['USER_ID'],
			'CALL_ID' => $params['CALL_ID'],
			'CALL_ID_TMP' => $params['CALL_ID_TMP'],
			'CALL_DEVICE' => $params['CALL_DEVICE'],
			'PHONE_NUMBER' => $params['PHONE_NUMBER'],
			'EXTERNAL' => $params['CALL_ID_TMP']? true: false,
			'PORTAL_CALL' => $result['PORTAL_CALL'],
			'PORTAL_CALL_USER_ID' => $result['USER_ID'],
			'PORTAL_CALL_DATA' => $portalUser,
			'CONFIG' => Array(
				'RECORDING' => $config['RECORDING'],
				'CRM_CREATE' => $config['CRM_CREATE']
			),
			'CRM' => $crmData,
		));

		if ($result['PORTAL_CALL'] == 'Y' && $result['USER_ID'] > 0)
		{
			CVoxImplantIncoming::SendPullEvent(Array(
				'COMMAND' => 'invite',
				'USER_ID' => $result['USER_ID'],
				'CALL_ID' => $params['CALL_ID'],
				'CALLER_ID' => $params['USER_DIRECT_CODE'],
				'PHONE_NAME' => $params['CALLER_ID'],
				'PORTAL_CALL' => 'Y',
				'PORTAL_CALL_USER_ID' => $params['USER_ID'],
				'PORTAL_CALL_DATA' => $portalUser,
				'CONFIG' => Array(
					'RECORDING' => $config['RECORDING'],
					'CRM_CREATE' => $config['CRM_CREATE']
				),
			));
		}

		return $result;
	}

	public static function GetConfigByUserId($userId)
	{
		$userId = intval($userId);
		if ($userId > 0)
		{
			$viUser = new CVoxImplantUser();
			$userInfo = $viUser->GetUserInfo($userId);
			if ($userInfo['user_backphone'] == '')
			{
				$userInfo['user_backphone'] = CVoxImplantConfig::LINK_BASE_NUMBER;
			}
		}
		else
		{
			$userInfo = Array();
			$userInfo['user_backphone'] = CVoxImplantConfig::GetPortalNumber();
			$userInfo['user_extranet'] = false;
			$userInfo['user_innerphone'] = CVoxImplantConfig::GetPortalNumber();
		}

		if ($userInfo['user_extranet'])
		{
			$result = Array('error' => Array('code' => 'EXTRANAET', 'msg' => 'Extranet user (or user hasnt department) cannot use telephony'));
		}
		else
		{
			$result = CVoxImplantConfig::GetConfigBySearchId($userInfo['user_backphone']);
		}

		$result['USER_ID'] = $userId;
		$result['USER_DIRECT_CODE'] = $userInfo['user_innerphone'];

		return $result;
	}

	public static function SendPullEvent($params)
	{
		// TODO check params

		if (!CModule::IncludeModule('pull') || !CPullOptions::GetQueueServerStatus() || $params['USER_ID'] <= 0)
			return false;

		$config = Array();
		$push = Array();
		if ($params['COMMAND'] == 'outgoing')
		{
			$call = CallTable::getByCallId($params['CALL_ID']);
			
			$config = Array(
				"callId" => $params['CALL_ID'],
				"callIdTmp" => $params['CALL_ID_TMP']? $params['CALL_ID_TMP']: '',
				"callDevice" => $params['CALL_DEVICE'] == 'PHONE'? 'PHONE': 'WEBRTC',
				"phoneNumber" => $params['PHONE_NUMBER'],
				"external" => $params['EXTERNAL']? true: false,
				"portalCall" => $params['PORTAL_CALL'] == 'Y'? true: false,
				"portalCallUserId" => $params['PORTAL_CALL'] == 'Y'? $params['PORTAL_CALL_USER_ID']: 0,
				"portalCallData" => $params['PORTAL_CALL'] == 'Y'? $params['PORTAL_CALL_DATA']: Array(),
				"config" => $params['CONFIG']? $params['CONFIG']: Array(),
				"CRM" => $params['CRM']? $params['CRM']: Array(),
			);

			if(!$config['portalCall'])
			{
				$config["showCrmCard"] = ($call['CRM'] == 'Y');
				$config["crmEntityType"] = $call['CRM_ENTITY_TYPE'];
				$config["crmEntityId"] = $call['CRM_ENTITY_ID'];
				$config["crmActivityId"] = $call['CRM_ACTIVITY_ID'];
				$config["crmActivityEditUrl"] = CVoxImplantCrmHelper::getActivityEditUrl($call['CRM_ACTIVITY_ID']);
			}

			$push['send_immediately'] = 'Y';
			$push['advanced_params'] = Array(
				"notificationsToCancel" => array('VI_CALL_'.$params['CALL_ID']),
			);
		}
		else if ($params['COMMAND'] == 'timeout')
		{
			$config = Array(
				"callId" => $params['CALL_ID'],
				"failedCode" => intval($params['FAILED_CODE']),
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

	public static function StartCall($userId, $phoneNumber, $params)
	{
		$phoneNormalized = CVoxImplantPhone::Normalize($phoneNumber);
		if (!$phoneNormalized)
		{
			$phoneNormalized = preg_replace("/[^0-9\#\*]/i", "", $phoneNumber);
		}

		$userId = intval($userId);
		if ($userId <= 0 || !$phoneNormalized)
			return false;

		$callFields = array(
			'CALL_ID' => 'temp.'.md5($userId.$phoneNumber).time(),
			'USER_ID' => $userId,
			'CALLER_ID' => $phoneNormalized,
			'STATUS' => VI\CallTable::STATUS_CONNECTING,
			'DATE_CREATE' => new FieldType\DateTime(),
			'INCOMING' => CVoxImplantMain::CALL_OUTGOING,
		);

		if(isset($params['ENTITY_TYPE']) && isset($params['ENTITY_ID']) && strpos($params['ENTITY_TYPE'], 'CRM_') === 0)
		{
			$callFields['CRM_ENTITY_TYPE'] = substr($params['ENTITY_TYPE'], 4);
			$callFields['CRM_ENTITY_ID'] = $params['ENTITY_ID'];
		}

		if(isset($params['SRC_ACTIVITY_ID']))
		{
			$callFields['CRM_ACTIVITY_ID'] = $params['SRC_ACTIVITY_ID'];
		}

		if(isset($params['CALL_LIST_ID']))
		{
			$callFields['CRM_CALL_LIST'] = $params['CALL_LIST_ID'];
		}

		$insertResult = VI\CallTable::add($callFields);
		if(!$insertResult->isSuccess())
		{
			return false;
		}

		$viHttp = new CVoxImplantHttp();
		$result = $viHttp->StartOutgoingCall($userId, $phoneNumber);

		VI\CallTable::update($insertResult->getId(), Array(
			'CALL_ID' => $result->call_id,
			'ACCESS_URL' => $result->access_url,
			'DATE_CREATE' => new FieldType\DateTime(),
		));

		$config = self::GetConfigByUserId($userId);

		return array(
			'USER_ID' => $userId,
			'PHONE_NUMBER' => $phoneNormalized,
			'CALL_ID' => $result->call_id,
			'CALL_DEVICE' => 'PHONE',
			'EXTERNAL' => true,
			'CONFIG' => array(
				'RECORDING' => $config['RECORDING'],
				'CRM_CREATE' => $config['CRM_CREATE']
			),
		);
	}

	/**
	 * Initiates infocall with a text to say.
	 * @param string $outputNumber Id of the line to perform outgoing call.
	 * @param string $number Number to be called.
	 * @param string $text Text to say.
	 * @param string $voiceLanguage TTS voice (@see: Tts\Language).
	 * @param string $voiceSpeed TTS voice speed (@see Tts\Speed).
	 * @param string $voiceVolume TTS voice volume (@see Tts\Volume).
	 * @return Result Returns array with CALL_ID or error.
	 */
	public static function StartInfoCallWithText($outputNumber, $number, $text, $voiceLanguage = '', $voiceSpeed = '', $voiceVolume = '')
	{
		$result = new Result();
		CVoxImplantHistory::WriteToLog(Array($outputNumber, $number, $text, $voiceLanguage, $voiceSpeed, $voiceVolume), 'StartInfoCallWithText');

		if ($outputNumber === CVoxImplantConfig::LINK_BASE_NUMBER)
		{
			$result->addError(new Error('Making infocall using LINK_BASE_NUMBER is not allowed'));
			return $result;
		}

		$numberConfig = CVoxImplantConfig::GetConfigBySearchId($outputNumber);
		if (isset($numberConfig['ERROR']))
		{
			$result->addError(new Error('Could not find config for number '.$outputNumber));
			return $result;
		}

		$limitRemainder = VI\Limits::getInfocallsLimitRemainder($numberConfig['PORTAL_MODE']);
		if($limitRemainder === 0)
		{
			$result->addError(new Error('Infocall limit for this month is exceeded'));
			return $result;
		}

		if($numberConfig['PORTAL_MODE'] === CVoxImplantConfig::MODE_SIP)
			$phoneNormalized = $number;
		else
			$phoneNormalized = CVoxImplantPhone::Normalize($number);

		if (!$phoneNormalized)
		{
			$result->addError(new Error('Phone number is not correct'));
			return $result;
		}

		$voiceLanguage = $voiceLanguage ?: Tts\Language::getDefaultVoice(\Bitrix\Main\Context::getCurrent()->getLanguage());
		$voiceSpeed = $voiceSpeed ?: Tts\Speed::getDefault();
		$voiceVolume = $voiceVolume ?: Tts\Volume::getDefault();

		$options = array(
			'MODE' => self::INFOCALL_MODE_TEXT,
			'VOICE_LANGUAGE' => $voiceLanguage,
			'VOICE_SPEED' => $voiceSpeed,
			'VOICE_VOLUME' => $voiceVolume
		);

		$httpClient = new CVoxImplantHttp();
		$infoCallResult = $httpClient->StartInfoCall($phoneNormalized, $text, $options, $numberConfig);

		if($infoCallResult === false)
		{
			$result->addError(new Error('Infocall failure'));
			return $result;
		}

		CVoxImplantHistory::WriteToLog($result, 'Infocall started');
		if($limitRemainder > 0)
		{
			VI\Limits::addInfocall($numberConfig['PORTAL_MODE']);
		}
		$result->setData(array(
			'CALL_ID' => $infoCallResult->call_id
		));

		return $result;
	}

	/**
	 * Initiates infocall with mp3 to play
	 * @param string $outputNumber Id of the line to perform outgoing call.
	 * @param string $number Number to be called.
	 * @param string $url Url of the mp3 to play.
	 * @return Result Returns array with CALL_ID or error.
	 */
	public static function StartInfoCallWithSound($outputNumber, $number, $url)
	{
		$result = new Result();
		CVoxImplantHistory::WriteToLog(Array($outputNumber, $number, $url), 'StartInfoCallWithSound');

		if($outputNumber === CVoxImplantConfig::LINK_BASE_NUMBER)
		{
			$result->addError(new Error('Making infocall using LINK_BASE_NUMBER is not allowed'));
			return $result;
		}

		$numberConfig = CVoxImplantConfig::GetConfigBySearchId($outputNumber);
		if(isset($numberConfig['ERROR']))
		{
			$result->addError(new Error('Could not find config for number ' . $outputNumber));
			return $result;
		}

		$limitRemainder = VI\Limits::getInfocallsLimitRemainder($numberConfig['PORTAL_MODE']);
		if ($limitRemainder === 0)
		{
			$result->addError(new Error('Infocall limit for this month is exceeded'));
			return $result;
		}

		if($numberConfig['PORTAL_MODE'] === CVoxImplantConfig::MODE_SIP)
			$phoneNormalized = $number;
		else
			$phoneNormalized = CVoxImplantPhone::Normalize($number);

		if (!$phoneNormalized)
		{
			$result->addError(new Error('Phone number is not correct'));
			return $result;
		}

		$options = array(
			'MODE' => self::INFOCALL_MODE_URL,
		);

		$httpClient = new CVoxImplantHttp();
		$infocallResult = $httpClient->StartInfoCall($phoneNormalized, $url, $options, $numberConfig);

		if($infocallResult === false)
		{
			$result->addError(new Error('Infocall failure'));
			return $result;
		}

		CVoxImplantHistory::WriteToLog($result, 'Infocall started');
		if($limitRemainder > 0)
		{
			VI\Limits::addInfocall($numberConfig['PORTAL_MODE']);
		}
		$result->setData(array(
			'CALL_ID' => $infocallResult->call_id
		));
		return $result;
	}

	/**
	 * Initiates 'callback' call
	 * @param string $callbackFromLine SearchId of the line to perform outgoing call.
	 * @param string $callbackToNumber Number to be called to.
	 * @param string $textToPronounce Entry text to be pronounced to the manager.
	 * @param string $voice Id of the voice to pronounce entry text. @see Language::getList.
	 * @param array $customData Additional fields to be passed to the scenario
	 * @return Result Returns array with CALL_ID in case of success or error.
	 */
	public static function startCallBack($callbackFromLine, $callbackToNumber, $textToPronounce, $voice = '', array $customData = array())
	{
		$result = new Result();
		CVoxImplantHistory::WriteToLog(Array($callbackFromLine, $callbackToNumber, $textToPronounce, $voice), 'startCallBack');

		$restAppId = CVoxImplantConfig::getRestAppId($callbackFromLine);
		if($restAppId > 0)
		{
			$restAppParams = $customData;
			$restAppParams['APP_ID'] = $restAppId;
			$restAppParams['PHONE_NUMBER'] = $callbackToNumber;
			$restAppParams['TEXT'] = $textToPronounce;
			$restAppParams['VOICE'] = $voice;
			VI\Rest\Helper::startCallBack($restAppParams);
			return $result;
		}

		$numberConfig = CVoxImplantConfig::GetConfigBySearchId($callbackFromLine);
		if (isset($numberConfig['ERROR']))
		{
			$result->addError(new Error('Could not find config for number '.$callbackFromLine));
			return $result;
		}

		$phoneNormalized = CVoxImplantPhone::Normalize($callbackToNumber);
		if (!$phoneNormalized)
		{
			$result->addError(new Error('Phone number is not correct'));
			return $result;
		}

		$call = array(
			'CALLER_ID' => $phoneNormalized,
			'STATUS' => VI\CallTable::STATUS_CONNECTING,
			'DATE_CREATE' => new FieldType\DateTime(),
		);

		if(isset($customData['CRM_ENTITY_TYPE']) && isset($customData['CRM_ENTITY_ID']))
		{
			$call['CRM_ENTITY_TYPE'] = $customData['CRM_ENTITY_TYPE'];
			$call['CRM_ENTITY_ID'] = $customData['CRM_ENTITY_ID'];
		}

		$voice = $voice ?: Tts\Language::getDefaultVoice(\Bitrix\Main\Context::getCurrent()->getLanguage());
		$viHttp = new CVoxImplantHttp();
		$callBackResult = $viHttp->StartCallBack($callbackFromLine, $phoneNormalized, $textToPronounce, $voice);

		if($callBackResult === false)
		{
			$result->addError(new Error($viHttp->GetError()->msg, $viHttp->GetError()->code));
		}
		else
		{
			$callId = $callBackResult->call_id;
			$call['CALL_ID'] = $callId;
			$insertResult = VI\CallTable::add($call);
			if(!$insertResult->isSuccess())
			{
				$result->addErrors($insertResult->getErrors());
				return $result;
			}

			$result->setData(array(
				'ID' => $insertResult->getId(),
				'CALL_ID' => $callId
			));
		}

		return $result;
	}
}
