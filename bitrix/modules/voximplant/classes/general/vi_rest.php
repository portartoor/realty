<?
if(!CModule::IncludeModule('rest'))
	return;

use Bitrix\Voximplant\Rest\Helper;
use Bitrix\Voximplant\Security;
use Bitrix\Voximplant\Rest;

\Bitrix\Main\Localization\Loc::loadMessages(__FILE__);

class CVoxImplantRestService extends IRestService
{
	public static function OnRestServiceBuildDescription()
	{
		return array(
			'telephony' => array(
				'voximplant.url.get' => array('CVoxImplantRestService', 'urlGet'),
				'voximplant.sip.get' => array('CVoxImplantRestService', 'sipGet'),
				'voximplant.sip.add' => array('CVoxImplantRestService', 'sipAdd'),
				'voximplant.sip.update' => array('CVoxImplantRestService', 'sipUpdate'),
				'voximplant.sip.delete' => array('CVoxImplantRestService', 'sipDelete'),
				'voximplant.sip.status' => array('CVoxImplantRestService', 'sipStatus'),
				'voximplant.sip.connector.status' => array('CVoxImplantRestService', 'sipConnectorStatus'),
				'voximplant.statistic.get' => array('CVoxImplantRestService', 'statisticGet'),
				'voximplant.line.outgoing.set' => array('CVoxImplantRestService', 'lineOutgoingSet'),
				'voximplant.line.outgoing.get' => array('CVoxImplantRestService', 'lineOutgoingGet'),
				'voximplant.line.outgoing.sip.set' => array('CVoxImplantRestService', 'lineOutgoingSipSet'),
				'voximplant.line.get' => array('CVoxImplantRestService', 'lineGet'),
				'voximplant.tts.voices.get' => array('CVoxImplantRestService', 'getVoiceList'),
				'voximplant.user.get' => array('CVoxImplantRestService', 'getUser'),
				'voximplant.user.activatePhone' => array('CVoxImplantRestService', 'activatePhone'),
				'voximplant.user.deactivatePhone' => array('CVoxImplantRestService', 'deactivatePhone'),
				'telephony.externalCall.register' => array('CVoxImplantRestService', 'registerExternalCall'),
				'telephony.externalCall.finish' => array('CVoxImplantRestService', 'finishExternalCall'),
				'telephony.externalCall.show' => array('CVoxImplantRestService', 'showExternalCall'),
				'telephony.externalCall.hide' => array('CVoxImplantRestService', 'hideExternalCall'),

				CRestUtil::EVENTS => array(
					'OnVoximplantCallInit' => array('voximplant', 'onCallInit', array(__CLASS__, 'onCallInit')),
					'OnVoximplantCallStart' => array('voximplant', 'onCallStart', array(__CLASS__, 'onCallStart')),
					'OnVoximplantCallEnd' => array('voximplant', 'onCallEnd', array(__CLASS__, 'onCallEnd')),
					Helper::EVENT_START_EXTERNAL_CALL => array('voximplant', 'onExternalCallStart', array(__CLASS__, 'filterApp')),
					Helper::EVENT_START_EXTERNAL_CALLBACK => array('voximplant', 'onExternalCallBackStart', array(__CLASS__, 'filterApp')),
				),
				CRestUtil::PLACEMENTS => array(
					Helper::PLACEMENT_CALL_CARD => array()
				)
			),
			'call' => array(
				'voximplant.callback.start' => array('CVoxImplantRestService', 'startCallback'),
				'voximplant.infocall.startwithtext' => array('CVoxImplantRestService', 'startInfoCallWithText'),
				'voximplant.infocall.startwithsound' => array('CVoxImplantRestService', 'startInfoCallWithSound'),
			)
		);
	}

	public static function urlGet()
	{
		return Array(
			'detail_statistics' => CVoxImplantHttp::GetServerAddress().CVoxImplantMain::GetPublicFolder().'detail.php',
			'buy_connector' => CVoxImplantHttp::GetServerAddress().'/settings/license_phone_sip.php',
			'edit_config' => CVoxImplantHttp::GetServerAddress().CVoxImplantMain::GetPublicFolder().'edit.php?ID=#CONFIG_ID#',
			'lines' => CVoxImplantHttp::GetServerAddress().CVoxImplantMain::GetPublicFolder().'lines.php',
		);
	}

	public static function sipGet($arParams, $nav, $server)
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(Security\Permissions::ENTITY_LINE, Security\Permissions::ACTION_MODIFY))
		{
			throw new \Bitrix\Rest\AccessException();
		}

		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$sort = $arParams['SORT'];
		$order = $arParams['ORDER'];

		if(isset($arParams['FILTER']) && is_array($arParams['FILTER']))
		{
			$arFilter = array_change_key_case($arParams['FILTER'], CASE_UPPER);
		}
		else
		{
			$arFilter = array();
		}
		$arFilter['APP_ID'] = $server->getAppId();

		$arReturn = array();

		$dbResCnt = \Bitrix\Voximplant\SipTable::getList(array(
			'filter' => $arFilter,
			'select' => array("CNT" => new Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(1)')),
		));
		$arResCnt = $dbResCnt->fetch();
		if ($arResCnt && $arResCnt["CNT"] > 0)
		{
			$arNavParams = self::getNavData($nav, true);

			$arSort = array();
			if($sort && $order)
			{
				$arSort[$sort] = $order;
			}

			$dbRes = \Bitrix\Voximplant\SipTable::getList(array(
				'order' => $arSort,
				'select' => Array('*', 'TITLE'),
				'filter' => $arFilter,
				'limit' => $arNavParams['limit'],
				'offset' => $arNavParams['offset'],
			));

			$result = array();
			while($arData = $dbRes->fetch())
			{
				unset($arData['ID']);
				unset($arData['APP_ID']);
				if ($arData['TYPE'] == CVoxImplantSip::TYPE_CLOUD)
				{
					unset($arData['INCOMING_SERVER']);
					unset($arData['INCOMING_LOGIN']);
					unset($arData['INCOMING_PASSWORD']);
				}
				else
				{
					unset($arData['REG_ID']);
				}
				$result[] = $arData;
			}

			return self::setNavData(
				$result,
				array(
					"count" => $arResCnt['CNT'],
					"offset" => $arNavParams['offset']
				)
			);
		}

		return $arReturn;
	}

	public static function sipAdd($arParams, $nav, $server)
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(Security\Permissions::ENTITY_LINE, Security\Permissions::ACTION_MODIFY))
		{
			throw new \Bitrix\Rest\AccessException();
		}

		$arParams = array_change_key_case($arParams, CASE_UPPER);
		if (!isset($arParams['TYPE']))
		{
			$arParams['TYPE'] = CVoxImplantSip::TYPE_CLOUD;
		}

		$viSip = new CVoxImplantSip();
		$configId = $viSip->Add(Array(
			'TYPE' => strtolower($arParams['TYPE']),
			'PHONE_NAME' => $arParams['TITLE'],
			'SERVER' => $arParams['SERVER'],
			'LOGIN' => $arParams['LOGIN'],
			'PASSWORD' => $arParams['PASSWORD'],
			'APP_ID' => $server->getAppId()
		));
		if (!$configId || $viSip->GetError()->error)
		{
			throw new Bitrix\Rest\RestException($viSip->GetError()->msg, $viSip->GetError()->code, CRestServer::STATUS_WRONG_REQUEST);
		}

		$result = $viSip->Get($configId, Array('WITH_TITLE' => true));
		unset($result['APP_ID']);
		unset($result['REG_STATUS']);

		return $result;
	}

	public static function sipUpdate($arParams, $nav, $server)
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(Security\Permissions::ENTITY_LINE, Security\Permissions::ACTION_MODIFY))
		{
			throw new \Bitrix\Rest\AccessException();
		}

		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$dbResCnt = \Bitrix\Voximplant\SipTable::getList(array(
			'filter' => Array(
				'CONFIG_ID' => $arParams["CONFIG_ID"],
				'APP_ID' => $server->getAppId()
			),
			'select' => array("CNT" => new Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(1)')),
		));
		$arResCnt = $dbResCnt->fetch();
		if (!$arResCnt || $arResCnt["CNT"] <= 0)
		{
			throw new Bitrix\Rest\RestException("Specified CONFIG_ID is not found", Bitrix\Rest\RestException::ERROR_NOT_FOUND, CRestServer::STATUS_NOT_FOUND);
		}

		if (!isset($arParams['TYPE']))
		{
			$arParams['TYPE'] = CVoxImplantSip::TYPE_CLOUD;
		}

		$arUpdate = Array(
			'TYPE' => $arParams['TYPE'],
			'NEED_UPDATE' => "Y",
		);
		if (isset($arParams['TITLE']))
			$arUpdate['TITLE'] = $arParams['TITLE'];
		if (isset($arParams['SERVER']))
			$arUpdate['SERVER'] = $arParams['SERVER'];
		if (isset($arParams['LOGIN']))
			$arUpdate['LOGIN'] = $arParams['LOGIN'];
		if (isset($arParams['PASSWORD']))
			$arUpdate['PASSWORD'] = $arParams['PASSWORD'];

		if (count($arUpdate) == 2)
		{
			return 1;
		}

		$viSip = new CVoxImplantSip();
		$result = $viSip->Update($arParams["CONFIG_ID"], $arUpdate);
		if (!$result || $viSip->GetError()->error)
		{
			throw new Bitrix\Rest\RestException($viSip->GetError()->msg, $viSip->GetError()->code, CRestServer::STATUS_WRONG_REQUEST);
		}

		return 1;
	}

	public static function sipDelete($arParams, $nav, $server)
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(Security\Permissions::ENTITY_LINE, Security\Permissions::ACTION_MODIFY))
		{
			throw new \Bitrix\Rest\AccessException();
		}

		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$dbResCnt = \Bitrix\Voximplant\SipTable::getList(array(
			'filter' => Array(
				'CONFIG_ID' => $arParams["CONFIG_ID"],
				'APP_ID' => $server->getAppId()
			),
			'select' => array("CNT" => new Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(1)')),
		));
		$arResCnt = $dbResCnt->fetch();
		if (!$arResCnt || $arResCnt["CNT"] <= 0)
		{
			throw new Bitrix\Rest\RestException("Specified CONFIG_ID is not found", Bitrix\Rest\RestException::ERROR_NOT_FOUND, CRestServer::STATUS_WRONG_REQUEST);
		}

		$viSip = new CVoxImplantSip();
		$result = $viSip->Delete($arParams['CONFIG_ID']);
		if (!$result || $viSip->GetError()->error)
		{
			throw new Bitrix\Rest\RestException($viSip->GetError()->msg, $viSip->GetError()->code, CRestServer::STATUS_WRONG_REQUEST);
		}

		return 1;
	}

	public static function sipStatus($arParams)
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(Security\Permissions::ENTITY_LINE, Security\Permissions::ACTION_MODIFY))
		{
			throw new \Bitrix\Rest\AccessException();
		}

		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$viSip = new CVoxImplantSip();
		$result = $viSip->GetSipRegistrations($arParams['REG_ID']);

		if (!$result)
		{
			throw new Bitrix\Rest\RestException($viSip->GetError()->msg, $viSip->GetError()->code, CRestServer::STATUS_WRONG_REQUEST);
		}

		return Array(
			'REG_ID' => $result->reg_id,
			'LAST_UPDATED' => $result->last_updated,
			'ERROR_MESSAGE' => $result->error_message,
			'STATUS_CODE' => $result->status_code,
			'STATUS_RESULT' => $result->status_result,
		);
	}

	public static function sipConnectorStatus()
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(Security\Permissions::ENTITY_LINE, Security\Permissions::ACTION_MODIFY))
		{
			throw new \Bitrix\Rest\AccessException();
		}

		$ViHttp = new CVoxImplantHttp();
		$info = $ViHttp->GetSipInfo();
		if (!$info || $ViHttp->GetError()->error)
		{
			throw new Bitrix\Rest\RestException($ViHttp->GetError()->msg, $ViHttp->GetError()->code, CRestServer::STATUS_WRONG_REQUEST);
		}

		$result = array(
			'FREE_MINUTES' => intval($info->FREE),
			'PAID' => $info->ACTIVE,
		);

		if ($info->ACTIVE)
		{
			$result['PAID_DATE_END'] = CRestUtil::ConvertDate($info->DATE_END);
		}

		return $result;
	}

	public static function statisticGet($arParams, $nav, $server)
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(Security\Permissions::ENTITY_CALL_DETAIL, Security\Permissions::ACTION_VIEW))
		{
			throw new \Bitrix\Rest\AccessException();
		}

		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$sort = $arParams['SORT'];
		$order = $arParams['ORDER'];

		if(isset($arParams['FILTER']) && is_array($arParams['FILTER']))
		{
			$arFilter = array_change_key_case($arParams['FILTER'], CASE_UPPER);

			if (isset($arFilter['CALL_START_DATE']))
			{
				$arFilter['CALL_START_DATE'] = CRestUtil::unConvertDateTime($arFilter['CALL_START_DATE']);
			}
			if (isset($arFilter['CALL_TYPE']))
			{
				$arFilter['INCOMING'] = $arFilter['CALL_TYPE'];
				unset($arFilter['CALL_TYPE']);
			}
		}
		else
		{
			$arFilter = array();
		}

		$allowedUserIds = Security\Helper::getAllowedUserIds(
			$permissions->getUserId(),
			$permissions->getPermission(Security\Permissions::ENTITY_CALL_DETAIL, Security\Permissions::ACTION_VIEW)
		);
		if (is_array($allowedUserIds))
		{
			$arFilter['PORTAL_USER_ID'] = $allowedUserIds;
		}

		$arReturn = array();

		$dbResCnt = \Bitrix\Voximplant\StatisticTable::getList(array(
			'filter' => $arFilter,
			'select' => array("CNT" => new Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(1)')),
		));
		$arResCnt = $dbResCnt->fetch();
		if ($arResCnt && $arResCnt["CNT"] > 0)
		{
			$arNavParams = self::getNavData($nav, true);

			$arSort = array();
			if($sort && $order)
			{
				$arSort[$sort] = $order;
			}

			$dbRes = \Bitrix\Voximplant\StatisticTable::getList(array(
				'order' => $arSort,
				'filter' => $arFilter,
				'limit' => $arNavParams['limit'],
				'offset' => $arNavParams['offset'],
			));

			$result = array();
			while($arData = $dbRes->fetch())
			{
				unset($arData['ACCOUNT_ID']);
				unset($arData['APPLICATION_ID']);
				unset($arData['APPLICATION_NAME']);
				unset($arData['CALL_LOG']);
				unset($arData['CALL_RECORD_ID']);
				unset($arData['CALL_WEBDAV_ID']);
				unset($arData['CALL_STATUS']);
				unset($arData['CALL_DIRECTION']);
				$arData['CALL_TYPE'] = $arData['INCOMING'];
				unset($arData['INCOMING']);
				$arData['CALL_START_DATE'] = CRestUtil::ConvertDateTime($arData['CALL_START_DATE']);
				if($arData['CALL_WEBDAV_ID'] > 0 && \Bitrix\Main\Loader::includeModule('disk'))
				{
					$fileId = (int)$arData['CALL_WEBDAV_ID'];
					$file = \Bitrix\Disk\File::loadById($fileId);
					if(!is_null($file))
						$arData['CALL_RECORD_URL'] = \Bitrix\Disk\Driver::getInstance()->getUrlManager()->getUrlForDownloadFile($file, true);
				}
				$result[] = $arData;
			}

			return self::setNavData(
				$result,
				array(
					"count" => $arResCnt['CNT'],
					"offset" => $arNavParams['offset']
				)
			);
		}

		return $arReturn;
	}

	public static function lineGet()
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(Security\Permissions::ENTITY_LINE, Security\Permissions::ACTION_MODIFY))
		{
			throw new \Bitrix\Rest\AccessException();
		}

		return CVoxImplantConfig::GetPortalNumbers(false);
	}

	public static function lineOutgoingSipSet($arParams)
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(Security\Permissions::ENTITY_LINE, Security\Permissions::ACTION_MODIFY))
		{
			throw new \Bitrix\Rest\AccessException();
		}

		$arParams = array_change_key_case($arParams, CASE_UPPER);

		$result = CVoxImplantConfig::SetPortalNumberByConfigId($arParams['CONFIG_ID']);
		if (!$result)
		{
			throw new Bitrix\Rest\RestException('Specified CONFIG_ID is not found', Bitrix\Rest\RestException::ERROR_ARGUMENT, CRestServer::STATUS_WRONG_REQUEST);
		}

		return 1;
	}

	public static function lineOutgoingSet($arParams)
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(Security\Permissions::ENTITY_LINE, Security\Permissions::ACTION_MODIFY))
		{
			throw new \Bitrix\Rest\AccessException();
		}

		$arParams = array_change_key_case($arParams, CASE_UPPER);

		CVoxImplantConfig::SetPortalNumber($arParams['LINE_ID']);

		return 1;
	}

	public static function lineOutgoingGet()
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(Security\Permissions::ENTITY_LINE, Security\Permissions::ACTION_MODIFY))
		{
			throw new \Bitrix\Rest\AccessException();
		}
		return CVoxImplantConfig::GetPortalNumber();
	}

	public static function getVoiceList()
	{
		return \Bitrix\Voximplant\Tts\Language::getList();
	}

	/**
	 * @param array $params
	 * @param ? $n
	 * @param \CRestServer $server
	 * @return array
	 */
	public static function getUser($params, $n, $server)
	{
		if(!isset($params['USER_ID']))
		{
			throw new \Bitrix\Rest\RestException('Parameter USER_ID is not set');
		}

		if(is_array($params['USER_ID']))
			$userIds = array_map('intval', $params['USER_ID']);
		else
			$userIds = array((int)$params['USER_ID']);

		$permissions = Security\Permissions::createWithCurrentUser();
		$allowedUserIds = Security\Helper::getAllowedUserIds(
			Security\Helper::getCurrentUserId(),
			$permissions->getPermission(Security\Permissions::ENTITY_USER, Security\Permissions::ACTION_MODIFY)
		);

		if(is_array($allowedUserIds))
			$userIds = array_intersect($userIds, $allowedUserIds);

		if(count($userIds) == 0)
			throw new \Bitrix\Rest\AccessException('You have no permission to query selected users');

		if(\Bitrix\Voximplant\Integration\Bitrix24::isInstalled())
		{
			$admins = \Bitrix\Voximplant\Integration\Bitrix24::getAdmins();
		}
		else
		{
			$admins = array();
			$cursor = \CAllGroup::GetGroupUserEx(1);
			while($row = $cursor->fetch())
			{
				$admins[] = (int)$row['USER_ID'];
			}
		}

		if(isset($admins[Security\Helper::getCurrentUserId()]))
			$admins = array(Security\Helper::getCurrentUserId());

		$server->requestConfirmation(
			$admins,
			GetMessage(
				'VI_REST_GET_USERS_CONFIRM',
				array('#APPLICATION_NAME#' => \Bitrix\Voximplant\Rest\Helper::getRestAppName($server->getClientId()))
			)
		);

		 $arExtParams = array(
			 'FIELDS' => array('ID'),
			 'SELECT' => array(
				 'UF_VI_PASSWORD',
				 'UF_VI_BACKPHONE',
				 'UF_VI_PHONE',
				 'UF_VI_PHONE_PASSWORD',
				 'UF_PHONE_INNER',
			 )
		 );

		$cursor = CUser::GetList(
			($sort_by = ''), 
			($dummy=''), 
			array('ID' => join(' | ', $userIds)), 
			$arExtParams
		);
		$result = array();

		$account = new CVoxImplantAccount();
		while($row = $cursor->Fetch())
		{
			$result[] = array(
				'ID' => $row['ID'],
				'DEFAULT_LINE' => $row['UF_VI_BACKPHONE'],
				'PHONE_ENABLED' => $row['UF_VI_PHONE'],
				'SIP_SERVER' => str_replace('voximplant.com', 'bitrixphone.com', $account->GetCallServer()),
				'SIP_LOGIN' => 'phone'.$row['ID'],
				'SIP_PASSWORD' => $row['UF_VI_PHONE_PASSWORD'],
				'INNER_NUMBER' => $row['UF_PHONE_INNER'],
			);
		}
		return $result;
	}

	/**
	 * @param array $params
	 * @param ? $n
	 * @param \CRestServer $server
	 */
	public static function activatePhone($params, $n, $server)
	{
		$userId = (int)$params['USER_ID'];
		if($userId === 0)
			throw new \Bitrix\Rest\RestException('Parameter USER_ID is not set');

		$permissions = Security\Permissions::createWithCurrentUser();
		if(!CVoxImplantUser::canModify($userId, $permissions))
			throw new \Bitrix\Rest\RestException('You are not allowed to modify user\'s settings');

		$user = new CVoxImplantUser();
		$user->SetPhoneActive($userId, true);
		return 1;
	}

	/**
	 * @param array $params
	 * @param ? $n
	 * @param \CRestServer $server
	 */
	public static function deactivatePhone($params, $n, $server)
	{
		$userId = (int)$params['USER_ID'];
		if($userId === 0)
			throw new \Bitrix\Rest\RestException('Parameter USER_ID is not set');
		
		$permissions = Security\Permissions::createWithCurrentUser();
		if(!CVoxImplantUser::canModify($userId, $permissions))
			throw new \Bitrix\Rest\RestException('You are not allowed to modify user\'s settings');

		$user = new CVoxImplantUser();
		$user->SetPhoneActive($userId, true);
		return 1;
	}

	public static function startCallback($params, $n, $server)
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(Security\Permissions::ENTITY_CALL, Security\Permissions::ACTION_PERFORM, Security\Permissions::PERMISSION_ANY))
			throw new \Bitrix\Rest\AccessException();

		$fromLine = $params['FROM_LINE'];
		$toNumber = $params['TO_NUMBER'];
		$textToPronounce = $params['TEXT_TO_PRONOUNCE'];
		$voice = $params['VOICE'];

		$callbackResult = CVoxImplantOutgoing::startCallBack($fromLine, $toNumber, $textToPronounce, $voice);
		if(!$callbackResult->isSuccess())
			throw new \Bitrix\Rest\RestException(implode('; ', $callbackResult->getErrorMessages()));

		$callbackData = $callbackResult->getData();
		$result = array (
			'RESULT' => true,
			'CALL_ID' => $callbackData['CALL_ID']
		);

		return $result;
	}

	public static function startInfoCallWithText($params, $n, $server)
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(Security\Permissions::ENTITY_CALL, Security\Permissions::ACTION_PERFORM, Security\Permissions::PERMISSION_ANY))
		{
			throw new \Bitrix\Rest\AccessException();
		}

		$fromLine = $params['FROM_LINE'];
		$toNumber = $params['TO_NUMBER'];
		$textToPronounce = $params['TEXT_TO_PRONOUNCE'];
		$voice = $params['VOICE'];

		$infoCallResult = CVoxImplantOutgoing::StartInfoCallWithText($fromLine, $toNumber, $textToPronounce, $voice);
		if(!$infoCallResult->isSuccess())
			throw new \Bitrix\Rest\RestException(implode('; ', $infoCallResult->getErrorMessages()));

		$infoCallData = $infoCallResult->getData();
		$result = array (
			'RESULT' => true,
			'CALL_ID' => $infoCallData['CALL_ID']
		);

		return $result;
	}

	public static function startInfoCallWithSound($params, $n, $server)
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(Security\Permissions::ENTITY_CALL, Security\Permissions::ACTION_PERFORM, Security\Permissions::PERMISSION_ANY))
		{
			throw new \Bitrix\Rest\AccessException();
		}

		$fromLine = $params['FROM_LINE'];
		$toNumber = $params['TO_NUMBER'];
		$soundUrl = $params['URL'];

		$infoCallResult = CVoxImplantOutgoing::StartInfoCallWithSound($fromLine, $toNumber, $soundUrl);
		if(!$infoCallResult->isSuccess())
			throw new \Bitrix\Rest\RestException(implode('; ', $infoCallResult->getErrorMessages()));

		$infoCallData = $infoCallResult->getData();
		$result = array (
			'RESULT' => true,
			'CALL_ID' => $infoCallData['CALL_ID']
		);

		return $result;
	}

	/**
	 * @param array $params
	 * @param $n
	 * @param CRestServer $server
	 */
	public static function registerExternalCall($params, $n, $server)
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(Security\Permissions::ENTITY_CALL_DETAIL, Security\Permissions::ACTION_MODIFY, Security\Permissions::PERMISSION_ANY))
		{
			throw new \Bitrix\Rest\AccessException();
		}
		$appId = $server->getClientId();

		$userId = (int)$params['USER_ID'];
		if($userId == 0)
			$userId = Rest\Helper::getUserByPhone($params['USER_PHONE_INNER']);

		if(!$userId)
			throw new \Bitrix\Rest\RestException('USER_ID or USER_PHONE_INNER should be set');

		if($params['TYPE'] != '1' && $params['TYPE'] != '2')
			throw new \Bitrix\Rest\RestException('Unknown TYPE');

		if(isset($params['CALL_START_DATE']))
			$startDate = new \Bitrix\Main\Type\DateTime(CRestUtil::unConvertDateTime($params['CALL_START_DATE']));
		else
			$startDate = new \Bitrix\Main\Type\DateTime();

		$result = Rest\Helper::registerExternalCall(array(
			'USER_ID' => $userId,
			'PHONE_NUMBER' => $params['PHONE_NUMBER'],
			'TYPE' => $params['TYPE'],
			'CALL_START_DATE' => $startDate,
			'CRM' => $params['CRM'],
			'CRM_CREATE' => $params['CRM_CREATE'],
			'CRM_SOURCE' => $params['CRM_SOURCE'],
			'CRM_ENTITY_TYPE' => $params['CRM_ENTITY_TYPE'],
			'CRM_ENTITY_ID' => $params['CRM_ENTITY_ID'],
			'REST_APP_ID' => $appId,
			'ADD_TO_CHAT' => $params['ADD_TO_CHAT'],
			'SHOW' => isset($params['SHOW']) ? (bool)$params['SHOW'] : true
		));

		if(!$result->isSuccess())
			throw new \Bitrix\Rest\RestException(implode('; ', $result->getErrorMessages()));

		return $result->getData();
	}

	/**
	 * @param array $params
	 * @param $n
	 * @param CRestServer $server
	 */
	public static function finishExternalCall($params, $n, $server)
	{
		$permissions = Security\Permissions::createWithCurrentUser();
		if(!$permissions->canPerform(Security\Permissions::ENTITY_CALL_DETAIL, Security\Permissions::ACTION_MODIFY, Security\Permissions::PERMISSION_ANY))
		{
			throw new \Bitrix\Rest\AccessException();
		}

		$userId = (int)$params['USER_ID'];
		if($userId == 0)
			$userId = Rest\Helper::getUserByPhone($params['USER_PHONE_INNER']);

		if(!$userId)
			throw new \Bitrix\Rest\RestException('USER_ID or USER_PHONE_INNER should be set');

		$result = Rest\Helper::finishExternalCall(array(
			'CALL_ID' => $params['CALL_ID'],
			'USER_ID' => $userId,
			'DURATION' => (int)$params['DURATION'],
			'COST' => (double)$params['COST'],
			'COST_CURRENCY' => (string)$params['COST_CURRENCY'],
			'STATUS_CODE' => (string)$params['STATUS_CODE'],
			'FAILED_REASON' => (string)$params['FAILED_REASON'],
			'RECORD_URL' => (string)$params['RECORD_URL'],
			'VOTE' => (int)$params['VOTE'],
			'ADD_TO_CHAT' => $params['ADD_TO_CHAT'] != false,
		));

		if(!$result->isSuccess())
			throw new \Bitrix\Rest\RestException(implode('; ', $result->getErrorMessages()));

		return $result->getData();
	}

	/**
	 * @param array $params
	 * @param $n
	 * @param CRestServer $server
	 */
	public static function showExternalCall($params, $n, $server)
	{
		return Rest\Helper::showExternalCall(array(
			'CALL_ID' => (string)$params['CALL_ID'],
			'USER_ID' => (int)$params['USER_ID'],
		));
	}

	/**
	 * @param array $params
	 * @param $n
	 * @param CRestServer $server
	 */
	public static function hideExternalCall($params, $n, $server)
	{
		return Rest\Helper::hideExternalCall(array(
			'CALL_ID' => (string)$params['CALL_ID'],
			'USER_ID' => (int)$params['USER_ID']
		));
	}


	public static function onCallInit($arParams)
	{
		$arResult = $arParams[0];
		return $arResult;
	}

	public static function onCallStart($arParams)
	{
		$arResult = $arParams[0];
		return $arResult;
	}

	public static function onCallEnd($arParams)
	{
		$arResult = $arParams[0];
		$arResult['CALL_START_DATE'] = CRestUtil::ConvertDateTime($arResult['CALL_START_DATE']);

		return $arResult;
	}

	public static function filterApp($arParams, $arHandler)
	{
		/** @var \Bitrix\Main\Event $event */
		$event = $arParams[0];
		$eventData = $event->getParameters();

		if($eventData['APP_ID'] == $arHandler['APP_ID'])
		{
			unset($eventData['APP_ID']);
			return $eventData;
		}
		else
		{
			throw new Exception('Wrong app!');
		}
	}
}
?>