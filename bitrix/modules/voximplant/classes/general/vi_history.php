<?
IncludeModuleLangFile(__FILE__);

use Bitrix\Voximplant as VI;
use Bitrix\Main\IO;
use Bitrix\Main\Event;
use Bitrix\Main\EventManager;

class CVoxImplantHistory
{
	public static function Add($params)
	{
		$call = false;
		if (strlen($params["CALL_ID"]) > 0)
		{
			if ($call = VI\CallTable::getByCallId($params['CALL_ID']))
			{
				VI\CallTable::delete($call['ID']);
			}
		}

		$config = false;
		if(is_array($call) && $call['CONFIG_ID'] > 0)
		{
			$config = CVoxImplantConfig::GetConfig($call['CONFIG_ID']);
		}
		else if(isset($params['ACCOUNT_SEARCH_ID']))
		{
			$config = CVoxImplantConfig::GetConfigBySearchId($params['ACCOUNT_SEARCH_ID']);
		}

		$arFields = array(
			"ACCOUNT_ID" =>			$params["ACCOUNT_ID"],
			"APPLICATION_ID" =>		$params["APPLICATION_ID"],
			"APPLICATION_NAME" =>	isset($params["APPLICATION_NAME"])?$params["APPLICATION_NAME"]: '-',
			"INCOMING" =>			$params["INCOMING"],
			"CALL_START_DATE" =>	$call? $call['DATE_CREATE']: new Bitrix\Main\Type\DateTime(),
			"CALL_DURATION" =>		isset($params["CALL_DURATION"])? $params["CALL_DURATION"]: $params["DURATION"],
			"CALL_STATUS" =>		$params["CALL_STATUS"],
			"CALL_FAILED_CODE" =>	$params["CALL_FAILED_CODE"],
			"CALL_FAILED_REASON" =>	$params["CALL_FAILED_REASON"],
			"COST" =>				$params["COST_FINAL"],
			"COST_CURRENCY" =>		$params["COST_CURRENCY"],
			"CALL_VOTE" =>			intval($params["CALL_VOTE"]),
			"CALL_ID" =>			$params["CALL_ID"],
			"CALL_CATEGORY" =>		$params["CALL_CATEGORY"],
		);

		if (strlen($params["PHONE_NUMBER"]) > 0)
			$arFields["PHONE_NUMBER"] = $params["PHONE_NUMBER"];

		if (strlen($params["CALL_DIRECTION"]) > 0)
			$arFields["CALL_DIRECTION"] = $params["CALL_DIRECTION"];

		if (strlen($params["PORTAL_NUMBER"]) > 0)
			$arFields["PORTAL_NUMBER"] = $params["PORTAL_NUMBER"];

		if (strlen($params["ACCOUNT_SEARCH_ID"]) > 0)
			$arFields["PORTAL_NUMBER"] = $params["ACCOUNT_SEARCH_ID"];

		if($arFields['CALL_VOTE'] < 1 || $arFields['CALL_VOTE'] > 5)
			$arFields['CALL_VOTE'] = null;

		if (strlen($params["CALL_LOG"]) > 0)
			$arFields["CALL_LOG"] = $params["CALL_LOG"];

		if($call && intval($call['USER_ID']) > 0)
		{
			$arFields["PORTAL_USER_ID"] = $call["USER_ID"];
		}
		else if (intval($params["PORTAL_USER_ID"]) > 0)
		{
			$arFields["PORTAL_USER_ID"] = intval($params["PORTAL_USER_ID"]);
		}
		else
		{
			$arFields["PORTAL_USER_ID"] = intval(self::detectResponsible($call, $config, $params['PHONE_NUMBER']));
		}

		if($call && $call['CRM_ACTIVITY_ID'])
		{
			$arFields['CRM_ACTIVITY_ID'] = $call['CRM_ACTIVITY_ID'];
		}
		else if($params['CRM_ACTIVITY_ID'])
		{
			$arFields['CRM_ACTIVITY_ID'] = $params['CRM_ACTIVITY_ID'];
		}

		if($call && $call['CRM_ENTITY_TYPE'] && $call['CRM_ENTITY_ID'])
		{
			$arFields['CRM_ENTITY_TYPE'] = $call['CRM_ENTITY_TYPE'];
			$arFields['CRM_ENTITY_ID'] = $call['CRM_ENTITY_ID'];
		}
		else if($params['CRM_ENTITY_TYPE'] && $params['CRM_ENTITY_ID'])
		{
			$arFields['CRM_ENTITY_TYPE'] = $params['CRM_ENTITY_TYPE'];
			$arFields['CRM_ENTITY_ID'] = $params['CRM_ENTITY_ID'];
		}
		else
		{
			$crmData = CVoxImplantCrmHelper::GetCrmEntity($params['PHONE_NUMBER'], $call['USER_ID']);
			if(is_array($crmData))
			{
				$arFields['CRM_ENTITY_TYPE'] = $crmData['ENTITY_TYPE_NAME'];
				$arFields['CRM_ENTITY_ID'] = $crmData['ENTITY_ID'];
			}
		}


		$orm = Bitrix\VoxImplant\StatisticTable::add($arFields);
		if (!$orm)
			return false;

		$arFields['ID'] = $orm->getId();
		$chatMessage = self::GetMessageForChat($arFields, $params['URL'] != '');
		if($chatMessage != '')
		{
			self::SendMessageToChat($arFields["PORTAL_USER_ID"], $arFields["PHONE_NUMBER"], $arFields["INCOMING"], $chatMessage);
		}

		if (($call && $call['CRM'] == 'Y') || $params['CRM'])
		{
			CVoxImplantCrmHelper::UpdateCall($arFields);
			if(isset($arFields['CRM_ENTITY_TYPE']) && isset($arFields['CRM_ENTITY_ID']))
			{
				$viMain = new CVoxImplantMain($arFields["PORTAL_USER_ID"]);
				$dialogData = $viMain->GetDialogInfo($arFields['PHONE_NUMBER'], '', false);
				CVoxImplantMain::UpdateChatInfo(
					$dialogData['DIALOG_ID'],
					array(
						'CRM' => $call['CRM'],
						'CRM_ENTITY_TYPE' => $arFields['CRM_ENTITY_TYPE'],
						'CRM_ENTITY_ID' => $arFields['CRM_ENTITY_ID']
					)
				);
			}
		}

		if (strlen($params['URL']) > 0)
		{
			$attachToCrm = $call['CRM'] == 'Y';

			$startDownloadAgent = false;

			$recordLimit = COption::GetOptionInt("voximplant", "record_limit");
			if ($recordLimit > 0 && !CVoxImplantAccount::IsPro())
			{
				$sipConnectorActive = CVoxImplantConfig::GetModeStatus(CVoxImplantConfig::MODE_SIP);
				if ($params['PORTAL_TYPE'] == CVoxImplantConfig::MODE_SIP && $sipConnectorActive)
				{
					$startDownloadAgent = true;
				}
				else
				{
					$recordMonth = COption::GetOptionInt("voximplant", "record_month");
					if (!$recordMonth)
					{
						$recordMonth = date('Ym');
						COption::SetOptionInt("voximplant", "record_month", $recordMonth);
					}
					$recordCount = CGlobalCounter::GetValue('vi_records', CGlobalCounter::ALL_SITES);
					if ($recordCount < $recordLimit)
					{
						CGlobalCounter::Increment('vi_records', CGlobalCounter::ALL_SITES, false);
						$startDownloadAgent = true;
					}
					else
					{
						if ($recordMonth < date('Ym'))
						{
							COption::SetOptionInt("voximplant", "record_month", date('Ym'));
							CGlobalCounter::Set('vi_records', 1, CGlobalCounter::ALL_SITES, '', false);
							CGlobalCounter::Set('vi_records_skipped', 0, CGlobalCounter::ALL_SITES, '', false);
							$startDownloadAgent = true;
						}
						else
						{
							CGlobalCounter::Increment('vi_records_skipped', CGlobalCounter::ALL_SITES, false);
						}
					}
					CVoxImplantHistory::WriteToLog(Array(
						'limit' => $recordLimit,
						'saved' => CGlobalCounter::GetValue('vi_records', CGlobalCounter::ALL_SITES),
						'skipped' => CGlobalCounter::GetValue('vi_records_skipped', CGlobalCounter::ALL_SITES),
						'save to portal' => $startDownloadAgent? 'Y':'N',
					), 'STATUS OF RECORD LIMIT');
				}
			}
			else
			{
				$startDownloadAgent = true;
			}

			if ($startDownloadAgent)
			{
				self::DownloadAgent($orm->getId(), $params['URL'], $attachToCrm);
			}
		}

		if (strlen($params["ACCOUNT_PAYED"]) > 0 && in_array($params["ACCOUNT_PAYED"], Array('Y', 'N')))
		{
			CVoxImplantAccount::SetPayedFlag($params["ACCOUNT_PAYED"]);
		}

		if($call && $call['CRM_LEAD'] > 0 && CVoxImplantConfig::GetLeadWorkflowExecution() == CVoxImplantConfig::WORKFLOW_START_DEFERRED)
		{
			CVoxImplantCrmHelper::StartLeadWorkflow($call['CRM_LEAD']);
		}

		if($call && $call['CRM_CALL_LIST'])
		{
			CVoxImplantCrmHelper::attachCallToCallList($call['CRM_CALL_LIST'], $arFields);
		}

		foreach(GetModuleEvents("voximplant", "onCallEnd", true) as $arEvent)
		{
			ExecuteModuleEventEx($arEvent, Array(Array(
				'CALL_ID' => $arFields['CALL_ID'],
				'CALL_TYPE' => $arFields['INCOMING'],
				'PHONE_NUMBER' => $arFields['PHONE_NUMBER'],
				'PORTAL_NUMBER' => $arFields['PORTAL_NUMBER'],
				'PORTAL_USER_ID' => $arFields['PORTAL_USER_ID'],
				'CALL_DURATION' => $arFields['CALL_DURATION'],
				'CALL_START_DATE' => $arFields['CALL_START_DATE'],
				'COST' => $arFields['COST'],
				'COST_CURRENCY' => $arFields['COST_CURRENCY'],
				'CALL_FAILED_CODE' => $arFields['CALL_FAILED_CODE'],
				'CALL_FAILED_REASON' => $arFields['CALL_FAILED_REASON'],
				'CRM_ACTIVITY_ID' => $arFields['CRM_ACTIVITY_ID'],
			)));
		}

		if($arFields['INCOMING'] == CVoxImplantMain::CALL_INFO)
		{
			$callEvent = new Event(
				'voximplant',
				'OnInfoCallResult',
				array(
					$arFields['CALL_ID'],
					array(
						'RESULT' => ($arFields['CALL_FAILED_CODE'] == '200'),
						'CODE' => $arFields['CALL_FAILED_CODE'],
						'REASON' => $arFields['CALL_FAILED_REASON']
					)
				)
			);
			EventManager::getInstance()->send($callEvent);
		}
		return true;
	}

	public static function DownloadAgent($historyID, $recordUrl, $attachToCrm = true, $retryOnFailure = true)
	{
		self::WriteToLog('Downloading record ' . $recordUrl);
		$historyID = intval($historyID);
		$attachToCrm = ($attachToCrm == true);
		if (strlen($recordUrl) <= 0 || $historyID <= 0)
		{
			return false;
		}

		$http = new \Bitrix\Main\Web\HttpClient(array(
			"disableSslVerification" => true
		));
		$http->query('GET', $recordUrl);
		if ($http->getStatus() != 200)
		{
			if($retryOnFailure)
			{
				CAgent::AddAgent(
					"CVoxImplantHistory::DownloadAgent('{$historyID}','".EscapePHPString($recordUrl, "'")."','{$attachToCrm}', false);",
					'voximplant', 'N', 60, '', 'Y', ConvertTimeStamp(time() + CTimeZone::GetOffset() + 60, 'FULL')
				);
			}

			return false;
		}

		$history = VI\StatisticTable::getById($historyID);
		$arHistory = $history->fetch();

		try
		{
			$fileName = $http->getHeaders()->getFilename();
			$urlComponents = parse_url($recordUrl);
			if($fileName != '')
			{
				$tempPath = \CFile::GetTempName('', bx_basename($fileName));
			}
			else if ($urlComponents && strlen($urlComponents["path"]) > 0)
			{
				$tempPath = \CFile::GetTempName('', bx_basename($urlComponents["path"]));
			}
			else
			{
				$tempPath = \CFile::GetTempName('', bx_basename($recordUrl));
			}

			IO\Directory::createDirectory(IO\Path::getDirectory($tempPath));
			if(IO\Directory::isDirectoryExists(IO\Path::getDirectory($tempPath)) === false)
			{
				self::WriteToLog('Error creating temporary directory ' . $tempPath);
				return false;
			}

			self::WriteToLog('Downloading to temporary file ' . $tempPath);
			$file = new IO\File($tempPath);
			$handler = $file->open("w+");
			if($handler === false)
			{
				self::WriteToLog('Error opening temporary file ' . $tempPath);
				return false;
			}

			$http->setOutputStream($handler);
			$http->getResult();
			$file->close();

			$recordFile = CFile::MakeFileArray($tempPath);
			if (is_array($recordFile) && $recordFile['size'] && $recordFile['size'] > 0)
			{
				if(strpos($recordFile['name'], '.') === false)
					$recordFile['name'] = $recordFile['name'] . '.mp3';

				$recordFile['MODULE_ID'] = 'voximplant';
				$fileID = CFile::SaveFile($recordFile, 'voximplant', true);
				if(is_int($fileID) && $fileID > 0)
				{
					$elementID = CVoxImplantDiskHelper::SaveFile(
						$arHistory,
						CFile::GetFileArray($fileID),
						CSite::GetDefSite()
					);
					$elementID = intval($elementID);
					if($attachToCrm && $elementID> 0)
					{
						CVoxImplantCrmHelper::AttachRecordToCall(Array(
							'CALL_ID' => $arHistory['CALL_ID'],
							'CALL_RECORD_ID' => $fileID,
							'CALL_WEBDAV_ID' => $elementID,
						));
					}
					VI\StatisticTable::update($historyID, Array('CALL_RECORD_ID' => $fileID, 'CALL_WEBDAV_ID' => $elementID));
				}
			}
		}
		catch (Exception $ex)
		{
			self::WriteToLog('Error caught during downloading record: ' . PHP_EOL . print_r($ex, true));
		}

		return false;
	}

	public static function GetForPopup($id)
	{
		$id = intval($id);
		if ($id <= 0)
			return false;

		$history = VI\StatisticTable::getById($id);
		$params = $history->fetch();
		if (!$params)
			return false;

		$params = self::PrepereData($params);

		$arResult = Array(
			'PORTAL_USER_ID' => $params['PORTAL_USER_ID'],
			'PHONE_NUMBER' => $params['PHONE_NUMBER'],
			'INCOMING_TEXT' => $params['INCOMING_TEXT'],
			'CALL_ICON' => $params['CALL_ICON'],
			'CALL_FAILED_CODE' => $params['CALL_FAILED_CODE'],
			'CALL_FAILED_REASON' => $params['CALL_FAILED_REASON'],
			'CALL_DURATION_TEXT' => $params['CALL_DURATION_TEXT'],
			'COST_TEXT' => $params['COST_TEXT'],
			'CALL_RECORD_HREF' => $params['CALL_RECORD_HREF'],
		);

		return $arResult;
	}

	public static function PrepereData($params)
	{
		if ($params["INCOMING"] == "N")
		{
			$params["INCOMING"] = CVoxImplantMain::CALL_OUTGOING;
		}
		else if ($params["INCOMING"] == "N")
		{
			$params["INCOMING"] = CVoxImplantMain::CALL_INCOMING;
		}
		if ($params["PHONE_NUMBER"] == "hidden")
		{
			$params["PHONE_NUMBER"] = GetMessage("IM_PHONE_NUMBER_HIDDEN");
		}

		$params["CALL_FAILED_REASON"] = in_array($params["CALL_FAILED_CODE"], array("200","304","603-S","603","403","404","486","484","503","480","402","423")) ? GetMessage("VI_STATUS_".$params["CALL_FAILED_CODE"]) : GetMessage("VI_STATUS_OTHER");

		if ($params["INCOMING"] == CVoxImplantMain::CALL_OUTGOING)
		{
			$params["INCOMING_TEXT"] = GetMessage("VI_OUTGOING");
			if ($params["CALL_FAILED_CODE"] == 200)
				$params["CALL_ICON"] = 'outgoing';
		}
		else if ($params["INCOMING"] == CVoxImplantMain::CALL_INCOMING)
		{
			$params["INCOMING_TEXT"] = GetMessage("VI_INCOMING");
			if ($params["CALL_FAILED_CODE"] == 200)
				$params["CALL_ICON"] = 'incoming';
		}
		else if ($params["INCOMING"] == CVoxImplantMain::CALL_INCOMING_REDIRECT)
		{
			$params["INCOMING_TEXT"] = GetMessage("VI_INCOMING_REDIRECT");
			if ($params["CALL_FAILED_CODE"] == 200)
				$params["CALL_ICON"] = 'incoming-redirect';
		}
		else if($params["INCOMING"] == CVoxImplantMain::CALL_CALLBACK)
		{
			$params["INCOMING_TEXT"] = GetMessage("VI_CALLBACK");
			if ($params["CALL_FAILED_CODE"] == 200)
				$params["CALL_ICON"] = 'incoming'; //todo: icon?
		}
		else if($params["INCOMING"] == CVoxImplantMain::CALL_INFO)
		{
			$params["INCOMING_TEXT"] = GetMessage("VI_INFOCALL");
			if ($params["CALL_FAILED_CODE"] == 200)
				$params["CALL_ICON"] = 'outgoing';
		}

		if ($params["CALL_FAILED_CODE"] == 304)
		{
			$params["CALL_ICON"] = 'skipped';
		}
		else if ($params["CALL_FAILED_CODE"] != 200)
		{
			$params["CALL_ICON"] = 'decline';
		}

		$params["CALL_DURATION_TEXT"] = static::convertDurationToText($params['CALL_DURATION']);

		if (CModule::IncludeModule("catalog"))
		{
			$params["COST_TEXT"] = FormatCurrency($params["COST"], ($params["COST_CURRENCY"] == "RUR" ? "RUB" : $params["COST_CURRENCY"]));
		}
		else
		{
			$params["COST_TEXT"] = $params["COST"]." ".GetMessage("VI_CURRENCY_".$params["COST_CURRENCY"]);
		}

		if (!$params["COST_TEXT"])
		{
			$params["COST_TEXT"] = '-';
		}

		if (intval($params["CALL_RECORD_ID"]) > 0)
		{
			$recordFile = CFile::GetFileArray($params["CALL_RECORD_ID"]);
			if ($recordFile !== false)
			{
				$params["CALL_RECORD_HREF"] = $recordFile['SRC'];
			}
		}

		$params["CALL_WEBDAV_ID"] = (int)$params["CALL_WEBDAV_ID"];
		if($params["CALL_WEBDAV_ID"] > 0 && \Bitrix\Main\Loader::includeModule('disk'))
		{
			$fileId = $params["CALL_WEBDAV_ID"];
			$file = \Bitrix\Disk\File::loadById($fileId);
			if(!is_null($file))
				$params['CALL_RECORD_DOWNLOAD_URL'] = \Bitrix\Disk\Driver::getInstance()->getUrlManager()->getUrlForDownloadFile($file, true);
		}

		return $params;
	}

	public static function TransferMessage($userId, $transferUserId, $phoneNumber, $transferPhone = '')
	{
		$userName = '';
		$arSelect = Array("ID", "LAST_NAME", "NAME", "LOGIN", "SECOND_NAME", "PERSONAL_GENDER");
		$dbUsers = CUser::GetList(($sort_by = false), ($dummy=''), array('ID' => $transferUserId), array('FIELDS' => $arSelect));
		if ($arUser = $dbUsers->Fetch())
			$userName = CUser::FormatName(CSite::GetNameFormat(false), $arUser, true, false);

		self::SendMessageToChat(
			$userId,
			$phoneNumber,
			CVoxImplantMain::CALL_INCOMING_REDIRECT,
			GetMessage('VI_CALL_TRANSFER', Array('#USER#' => $userName)).($transferPhone != '' ? ' ('.$transferPhone.')' : '')
		);

		return true;
	}

	public static function SendMessageToChat($userId, $phoneNumber, $incomingType, $message)
	{
		$ViMain = new CVoxImplantMain($userId);
		$dialogInfo = $ViMain->GetDialogInfo($phoneNumber, "", false);
		$ViMain->SendChatMessage($dialogInfo['DIALOG_ID'], $incomingType, $message);

		return true;
	}

	/**
	 * Creates message for the chat associated with phone number.
	 * @param array $callFields
	 * @param bool $hasRecord
	 * @return string
	 */
	public static function GetMessageForChat($callFields, $hasRecord = false, $prependPlus = true)
	{
		$result = '';
		if (strlen($callFields["PHONE_NUMBER"]) > 0 && $callFields["PORTAL_USER_ID"] > 0 && $callFields["CALL_FAILED_CODE"] != 423)
		{
			$plusSymbol =  $prependPlus && strlen($callFields["PHONE_NUMBER"]) >= 10 && substr($callFields["PHONE_NUMBER"], 0, 1) != '+' ? '+' : '';
			if ($callFields["INCOMING"] == CVoxImplantMain::CALL_OUTGOING)
			{
				if ($callFields['CALL_FAILED_CODE'] == '603-S')
				{
					$result = GetMessage('VI_OUT_CALL_DECLINE_SELF', Array('#NUMBER#' => $plusSymbol.$callFields["PHONE_NUMBER"]));
				}
				else if ($callFields['CALL_FAILED_CODE'] == 603)
				{
					$result = GetMessage('VI_OUT_CALL_DECLINE', Array('#NUMBER#' => $plusSymbol.$callFields["PHONE_NUMBER"]));
				}
				else if ($callFields['CALL_FAILED_CODE'] == 486)
				{
					$result = GetMessage('VI_OUT_CALL_BUSY', Array('#NUMBER#' => $plusSymbol.$callFields["PHONE_NUMBER"]));
				}
				else if ($callFields['CALL_FAILED_CODE'] == 480)
				{
					$result = GetMessage('VI_OUT_CALL_UNAVAILABLE', Array('#NUMBER#' => $plusSymbol.$callFields["PHONE_NUMBER"]));
				}
				else if ($callFields['CALL_FAILED_CODE'] == 404 || $callFields['CALL_FAILED_CODE'] == 484)
				{
					$result = GetMessage('VI_OUT_CALL_ERROR_NUMBER', Array('#NUMBER#' => $plusSymbol.$callFields["PHONE_NUMBER"]));
				}
				else if ($callFields['CALL_FAILED_CODE'] == 402)
				{
					$result = GetMessage('VI_OUT_CALL_NO_MONEY', Array('#NUMBER#' => $plusSymbol.$callFields["PHONE_NUMBER"]));
				}
				else
				{
					$result = GetMessage('VI_OUT_CALL_END', Array(
						'#NUMBER#' => $plusSymbol.$callFields["PHONE_NUMBER"],
						'#INFO#' => '[PCH='.$callFields['ID'].']'.GetMessage('VI_CALL_INFO').'[/PCH]',
					));
				}
			}
			else if ($callFields['INCOMING'] == CVoxImplantMain::CALL_CALLBACK)
			{
				if ($callFields['CALL_FAILED_CODE'] == '603-S')
				{
					$result = GetMessage('VI_CALLBACK_DECLINE_SELF', Array('#NUMBER#' => $plusSymbol.$callFields["PHONE_NUMBER"]));
				}
				else if ($callFields['CALL_FAILED_CODE'] == 603)
				{
					$result = GetMessage('VI_CALLBACK_DECLINE', Array('#NUMBER#' => $plusSymbol.$callFields["PHONE_NUMBER"]));
				}
				else if ($callFields['CALL_FAILED_CODE'] == 486)
				{
					$result = GetMessage('VI_CALLBACK_BUSY', Array('#NUMBER#' => $plusSymbol.$callFields["PHONE_NUMBER"]));
				}
				else if ($callFields['CALL_FAILED_CODE'] == 480)
				{
					$result = GetMessage('VI_CALLBACK_UNAVAILABLE', Array('#NUMBER#' => $plusSymbol.$callFields["PHONE_NUMBER"]));
				}
				else if ($callFields['CALL_FAILED_CODE'] == 404 || $callFields['CALL_FAILED_CODE'] == 484)
				{
					$result = GetMessage('VVI_CALLBACK_ERROR_NUMBER', Array('#NUMBER#' => $plusSymbol.$callFields["PHONE_NUMBER"]));
				}
				else if ($callFields['CALL_FAILED_CODE'] == 402)
				{
					$result = GetMessage('VI_CALLBACK_NO_MONEY', Array('#NUMBER#' => $plusSymbol.$callFields["PHONE_NUMBER"]));
				}
				else if ($callFields['CALL_FAILED_CODE'] == 304)
				{
					$subMessage = '[PCH='.$callFields['ID'].']'.GetMessage('VI_CALL_INFO').'[/PCH]';
					$result = GetMessage('VI_CALLBACK_SKIP', Array('#NUMBER#' => $plusSymbol.$callFields["PHONE_NUMBER"], '#INFO#' => $subMessage));
				}
				else
				{
					$result = GetMessage('VI_CALLBACK_END', Array(
						'#NUMBER#' => $plusSymbol.$callFields["PHONE_NUMBER"],
						'#INFO#' => '[PCH='.$callFields['ID'].']'.GetMessage('VI_CALL_INFO').'[/PCH]',
					));
				}
			}
			else
			{
				if ($callFields['CALL_FAILED_CODE'] == 304)
				{
					if ($hasRecord)
						$subMessage = GetMessage('VI_CALL_VOICEMAIL', Array('#LINK_START#' => '[PCH='.$callFields['ID'].']', '#LINK_END#' => '[/PCH]',));
					else
						$subMessage = '[PCH='.$callFields['ID'].']'.GetMessage('VI_CALL_INFO').'[/PCH]';

					$result = GetMessage('VI_IN_CALL_SKIP', Array(
						'#NUMBER#' => $plusSymbol.$callFields["PHONE_NUMBER"],
						'#INFO#' => $subMessage,
					));
				}
				else
				{
					$result = GetMessage('VI_IN_CALL_END', Array(
						'#NUMBER#' => $plusSymbol.$callFields["PHONE_NUMBER"],
						'#INFO#' => '[PCH='.$callFields['ID'].']'.GetMessage('VI_CALL_INFO').'[/PCH]',
					));
				}
			}
		}
		return $result;
	}

	public static function GetCallTypes()
	{
		return array(
			CVoxImplantMain::CALL_OUTGOING => GetMessage("VI_OUTGOING"),
			CVoxImplantMain::CALL_INCOMING => GetMessage("VI_INCOMING"),
			CVoxImplantMain::CALL_INCOMING_REDIRECT => GetMessage("VI_INCOMING_REDIRECT"),
			CVoxImplantMain::CALL_CALLBACK => GetMessage("VI_CALLBACK"),
			CVoxImplantMain::CALL_INFO => GetMessage("VI_INFOCALL"),
		);
	}


	/**
	 * Returns brief call details for CRM or false if call is not found.
	 * @param string $callId Id of the call.
	 * @return array(STATUS_CODE, STATUS_TEXT, SUCCESSFUL) | false
	 */
	public static function getBriefDetails($callId)
	{
		$call = VI\StatisticTable::getList(array('filter' => array('CALL_ID' => $callId)))->fetch();
		if(!$call)
			return false;

		return array(
			'STATUS_CODE '=> $call['CALL_FAILED_CODE'],
			'STATUS_TEXT' => in_array($call["CALL_FAILED_CODE"], array("200","304","603-S","603","403","404","486","484","503","480","402","423")) ? GetMessage("VI_STATUS_".$call["CALL_FAILED_CODE"]) : GetMessage("VI_STATUS_OTHER"),
			'SUCCESSFUL' => $call['CALL_FAILED_CODE'] == '200',
			'DURATION' => (int)$call['CALL_DURATION'],
			'DURATION_TEXT' => static::convertDurationToText($call['CALL_DURATION'])
		);
	}

	public static function WriteToLog($data, $title = '')
	{
		if (!COption::GetOptionInt("voximplant", "debug"))
			return false;

		if (is_array($data))
		{
			unset($data['HASH']);
			unset($data['BX_HASH']);
		}
		else if (is_object($data))
		{
			if ($data->HASH)
			{
				$data->HASH = '';
			}
			if ($data->BX_HASH)
			{
				$data->BX_HASH = '';
			}
		}
		$f=fopen($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/voximplant.log", "a+t");
		$w=fwrite($f, "\n------------------------\n".date("Y.m.d G:i:s")."\n".(strlen($title)>0? $title: 'DEBUG')."\n".print_r($data, 1)."\n------------------------\n");
		fclose($f);

		return true;
	}

	/**
	 * @param int $duration Duration in seconds.
	 * @return string Text form of duration.
	 */
	public static function convertDurationToText($duration)
	{
		$duration = (int)$duration;
		if($duration < 60)
			return $duration . " " .GetMessage("VI_SEC");

		$minutes = floor($duration / 60);
		$seconds = $duration % 60;
		return $minutes." ".GetMessage("VI_MIN") . ($seconds > 0 ? ", " . $seconds . " " . GetMessage("VI_SEC") : '');
	}

	/**
	 * This function guesses responsible person to assign missed call.
	 * @param array $call Call fields, as selected from the Bitrix\Voximplant\CallTable.
	 * @param array $config Line config, as selected from the Bitrix\Voximplant\ConfigTable
	 * @return int|false Id of the responsible, or false if responsible is not found.
	 */
	public static function detectResponsible($call, $config, $phoneNumber)
	{
		if(is_array($call) && $call['QUEUE_ID'] > 0)
		{
			$queue = new VI\Queue($call['QUEUE_ID']);
			$queueUser = $queue->getFirstUserId($config['TIMEMAN'] == 'Y');
			if ($queueUser > 0)
			{
				$queue->touchUser($queueUser);
				return $queueUser;
			}
		}

		if(is_array($config) && $config['CRM'] == 'Y' && $config['CRM_FORWARD'] == 'Y')
		{
			if(is_array($call) && $call['CRM_ENTITY_TYPE'] != '' && $call['CRM_ENTITY_ID'] > 0)
			{
				$responsibleId = CVoxImplantCrmHelper::getResponsible($call['CRM_ENTITY_TYPE'], $call['CRM_ENTITY_ID']);
				if($responsibleId > 0)
				{
					return $responsibleId;
				}
			}
			else
			{
				$responsibleInfo = CVoxImplantIncoming::getCrmResponsible($phoneNumber, $config['TIMEMAN'] == 'Y');
				if($responsibleInfo && $responsibleInfo['AVAILABLE'] == 'Y')
				{
					return $responsibleInfo['USER_ID'];
				}
			}
		}

		if(is_array($config) && $config['QUEUE_ID'] > 0)
		{
			$queue = new VI\Queue($config['QUEUE_ID']);
			$queueUser = $queue->getFirstUserId($config['TIMEMAN'] == 'Y');
			if ($queueUser > 0)
			{
				$queue->touchUser($queueUser);
				return $queueUser;
			}
		}

		return false;
	}
}