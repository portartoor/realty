<?
class CTimeMan
{
	private static $SECTIONS_SETTINGS_CACHE = null;
	private static $arWasElapedCache = array();

	public static function CanUse($bAdminAction = false)
	{
		global $USER, $USER_FIELD_MANAGER;

		if ($bAdminAction)
		{
			return
				$USER->CanDoOperation('tm_read')
				|| $USER->CanDoOperation('tm_read_subordinate');
		}

		if ($USER->IsAuthorized() && $USER->CanDoOperation('tm_manage'))
		{
			$TMUSER = CTimeManUser::instance();
			$arSettings = $TMUSER->GetSettings();

			return $arSettings['UF_TIMEMAN'];
		}

		return false;
	}

	public static function IsAdmin()
	{
		global $USER;
		return $USER->CanDoOperation('tm_write')||$USER->CanDoOperation('tm_manage_all');
	}

	public static function GetRuntimeInfo($bFull = false)
	{
		global $USER;

		$TMUSER = CTimeManUser::instance();
		$STATE = $TMUSER->State();

		$info = array('ID' => '', 'STATE' => $STATE, 'CAN_EDIT' => 'N');

		if ($STATE == 'CLOSED')
			$info['CAN_OPEN'] = $TMUSER->OpenAction();
		elseif ($STATE == 'EXPIRED')
			$info['EXPIRED_DATE'] = $TMUSER->GetExpiredRecommendedDate();

		$arSettings = $TMUSER->GetSettings();
		$info['REPORT_REQ'] = $arSettings['UF_TM_REPORT_REQ'];
		$info['TM_FREE'] = $arSettings['UF_TM_FREE'];

		if ($arInfo = $TMUSER->GetCurrentInfo())
		{
			$info['ID'] = $arInfo['ID'];

			$info['CAN_EDIT'] = COption::GetOptionString(
				'timeman', 'workday_can_edit_current', 'Y'
			) === 'Y' ? 'Y' : 'N';

			$info['INFO'] = array(
				'DATE_START' => MakeTimeStamp($arInfo['DATE_START']) - CTimeZone::GetOffset(),
				'DATE_FINISH' => $arInfo['DATE_FINISH']
					? (MakeTimeStamp($arInfo['DATE_FINISH']) - CTimeZone::GetOffset())
					: '',
				'TIME_START' => $arInfo['TIME_START'],
				'TIME_FINISH' => $arInfo['TIME_FINISH'],
				'DURATION' => $arInfo['DURATION'],
				'TIME_LEAKS' => $arInfo['TIME_LEAKS'],
				'ACTIVE' => ($arInfo['ACTIVE'] == 'Y'),
				'PAUSED' => ($arInfo['PAUSED'] == 'Y'),
			);

			if ($arInfo['LAST_PAUSE'])
			{
				$info['LAST_PAUSE'] = $arInfo['LAST_PAUSE'];
			}
			elseif ($arInfo['PAUSED'] == 'Y')
			{
				$info['LAST_PAUSE'] = array(
					'DATE_START' => $info['INFO']['DATE_FINISH']
				);
			}

			$info['SOCSERV_ENABLED'] = IsModuleInstalled('socialservices')
				&& (COption::GetOptionString("socialservices", "allow_send_user_activity", "Y") == 'Y');
			if($bFull && $info['SOCSERV_ENABLED'])
			{
				$info['SOCSERV_ENABLED_USER'] = $TMUSER->isSocservEnabledByUser();
			}
		}

		$info['PLANNER'] = CIntranetPlanner::getData(SITE_ID, $bFull);

		$info['OPEN_NOW'] = (
			$STATE == 'EXPIRED' || $STATE == 'CLOSED' && (
				!$_SESSION['TM_FORCED_OPEN'] ||
				CTimeMan::RemoveHoursTS($_SESSION['TM_FORCED_OPEN']) != CTimeMan::RemoveHoursTS(time())
			)
		);

		if ($info['OPEN_NOW'])
		{
			$_SESSION['TM_FORCED_OPEN'] = time();
		}

		$info["FULL"] = $bFull;

		return $info;
	}

	/**
	 * DEPRECATED! Migrated to tasks module.
	 *
	 * @deprecated
	 */
	public static function GetTaskTime($arParams)
	{
		if ($arParams['EXPIRED_DATE'] > 0)
		{
			$arParams['EXPIRED_DATE'] += CTimeMan::RemoveHoursTS($arParams['DATE_START']);
		}

		if (CModule::IncludeModule('tasks'))
		{
			$time = 0;

			$arFilter = array('TASK_ID' => $arParams['TASK_ID'], 'USER_ID' => $arParams['USER_ID'], '>=CREATED_DATE' => ConvertTimeStamp($arParams['DATE_START'], 'FULL'));
			if ($arParams['DATE_FINISH'])
				$arFilter['<CREATED_DATE'] = ConvertTimeStamp($arParams['DATE_FINISH'], 'FULL');
			elseif ($arParams['EXPIRED_DATE'])
				$arFilter['<CREATED_DATE'] = ConvertTimeStamp($arParams['EXPIRED_DATE']);

			$dbRes = CTaskElapsedTime::GetList(array('CREATED_DATE' => 'ASC'), $arFilter);

			while ($arRes = $dbRes->Fetch())
			{
				self::$arWasElapedCache[$arRes['TASK_ID']] = true;
				$time += $arRes['MINUTES'] * 60;
			}

			if ($time == 0)
			{
				$arFilter['FIELD'] = 'STATUS';

				$dbRes = CTaskLog::GetList(array('CREATED_DATE' => 'ASC'), $arFilter);

				$current_time = $arParams['DATE_START'];
				$last_status = $arParams['TASK_STATUS'];
				while ($arRes = $dbRes->Fetch())
				{
					if ($arRes['FROM_VALUE'] == 3)
					{
						$time += MakeTimeStamp($arRes['CREATED_DATE']) - $current_time;
					}
					elseif ($arRes['TO_VALUE'] == 3)
					{
						$current_time = MakeTimeStamp($arRes['CREATED_DATE']);
					}

					$last_status = $arRes['TO_VALUE'];
				}

				if ($last_status == 3)
				{
					if ($arParams['DATE_FINISH'])
					{
						$time += $arParams['DATE_FINISH'] - $current_time;
					}
					elseif ($arParams['EXPIRED_DATE'])
					{
						$time += $arParams['EXPIRED_DATE'] - $current_time;
					}
					else
					{
						$time += time() + CTimeZone::GetOffset() - $current_time;
					}
				}
			}

			return $time;
		}

		return false;
	}

	/**
	 * DEPRECATED! Migrated to tasks module.
	 *
	 * @deprecated
	 */
	public static function SetTaskTime($arParams)
	{
		if (!self::$arWasElapedCache[$arParams['TASK_ID']])
		{
			$ob = new CTaskElapsedTime();
			$ob->Add(array(
				'USER_ID' => $arParams['USER_ID'],
				'TASK_ID' => $arParams['TASK_ID'],
				'MINUTES' => intval($arParams['TIME'] / 60),
				'COMMENT_TEXT' => GetMessage('TIMEMAN_MODULE_NAME')
			));
		}
	}

	public static function GetAccessSettings()
	{
		$r = COption::GetOptionString('timeman', 'SUBORDINATE_ACCESS', '');
		if (strlen($r) > 0)
			$r = unserialize($r);

		if (!is_array($r))
		{
			$r = array(
				'READ' => array('EMPLOYEE' => 0, 'HEAD' => 1),
				'WRITE' => array('HEAD' => 1),
			);
		}

		return $r;
	}

	public static function GetAccess()
	{
		global $USER;

		// simplest caching. is it enough? maybe...
		static $access = null;

		if(!is_array($access))
		{
			$access = array(
				'READ' => array(),
				'WRITE' => array(),
			);

			$arAccessSettings = null;
			$subordinateList = array();

			if($USER->CanDoOperation('tm_read'))
			{
				$access['READ'][] = '*';
			}
			elseif($USER->CanDoOperation('tm_read_subordinate'))
			{
				$arAccessSettings = self::GetAccessSettings();

				if($arAccessSettings['READ']['EMPLOYEE'] >= 2)
				{
					$access['READ'][] = '*';
				}
				else
				{
					// everybody can read his own entries
					$access['READ'][] = $USER->GetID();

					if($arAccessSettings['READ']['EMPLOYEE'] >= 1)
					{
						$dbUsers = CIntranetUtils::GetDepartmentColleagues(null, false, false, 'Y', array('ID'));
						while($arRes = $dbUsers->Fetch())
						{
							$access['READ'][] = $arRes['ID'];
						}
					}

					$dbUsers = CIntranetUtils::GetSubordinateEmployees($USER->GetID(), $arAccessSettings['READ']['HEAD'] == 1, 'Y', array('ID'));
					while($arRes = $dbUsers->Fetch())
					{
						if($arAccessSettings['READ']['HEAD'] == 2)
						{
							$access['READ'] = array('*');
							break;
						}

						if(!isset($subordinateList[intval($arAccessSettings['READ']['HEAD'])]))
						{
							$subordinateList[intval($arAccessSettings['READ']['HEAD'])] = array();
						}

						$subordinateList[intval($arAccessSettings['READ']['HEAD'])][] = $arRes;
						$access['READ'][] = $arRes['ID'];
					}

					$access['READ'] = array_values(array_unique($access['READ']));
				}
			}

			if($USER->CanDoOperation('tm_write'))
			{
				$access['WRITE'][] = '*';
			}
			elseif($USER->CanDoOperation('tm_write_subordinate'))
			{
				if($arAccessSettings['WRITE']['EMPLOYEE'] >= 2)
				{
					$access['WRITE'][] = '*';
				}
				else
				{
					// check if current user is The Boss.
					$arManagers = self::GetUserManagers($USER->GetID());
					if(count($arManagers) == 1 && $arManagers[0] == $USER->GetID())
					{
						$access['WRITE'][] = $USER->GetID();
					}

					if(!is_array($arAccessSettings))
					{
						$arAccessSettings = self::GetAccessSettings();
					}

					if(isset($subordinateList[intval($arAccessSettings['WRITE']['HEAD'])]))
					{
						foreach($subordinateList[intval($arAccessSettings['WRITE']['HEAD'])] as $arRes)
						{
							$access['WRITE'][] = $arRes['ID'];
						}
					}
					else
					{
						$dbUsers = CIntranetUtils::GetSubordinateEmployees($USER->GetID(), $arAccessSettings['WRITE']['HEAD'] == 1, 'Y', array('ID'));
						while($arRes = $dbUsers->Fetch())
						{
							$access['WRITE'][] = $arRes['ID'];
						}
					}

					$access['WRITE'] = array_values(array_unique($access['WRITE']));
				}
			}
		}

		return $access;
	}

	public static function GetDirectAccess($USER_ID = false)
	{
		global $USER;
		$USER_ID = intval($USER_ID);
		if ($USER_ID<=0)
			$USER_ID = $USER->GetID();
		$arSDeps = CIntranetUtils::GetSubordinateDepartments($USER_ID,true);
		$arStruct = CIntranetUtils::GetStructure();
		$arEmployees = Array();
		foreach ($arSDeps as $dpt)
		{
				$arCurDpt = $arStruct['DATA'][$dpt];

				$employee = (($arCurDpt["UF_HEAD"])?$arCurDpt["UF_HEAD"]://have we a manager?
											((count($arCurDpt["EMPLOYEES"])>0)?$arCurDpt["EMPLOYEES"][0]:false//first employee of the dep
											)
				);
				if ($employee && $employee == $USER_ID)//this user is a head manager
				{
					foreach($arCurDpt["EMPLOYEES"] as $empUser)
								$arEmployees[] = $empUser;
				}
				elseif($employee)//no head manager or this user is no head manager
				{

					$headManager = CTimeMan::GetUserManagers($employee);//find head manager of employee
					if ($USER_ID == $headManager[0])//
					{
						if ($arCurDpt["UF_HEAD"])
							$arEmployees[] = $employee;
						else
							foreach($arCurDpt["EMPLOYEES"] as $empUser)
								$arEmployees[] = $empUser;
					}
				}
		}

		return array_unique($arEmployees);
	}

	public static function GetSectionPersonalSettings($section_id, $bHideParentLinks = false, $arNeededSettings = null)
	{
		if (null == self::$SECTIONS_SETTINGS_CACHE)
			self::_GetTreeSettings();

		if (!$bHideParentLinks)
		{
			if (!is_array($arNeededSettings))
				return self::$SECTIONS_SETTINGS_CACHE[$section_id];
			else
			{
				$ar = self::$SECTIONS_SETTINGS_CACHE[$section_id];
				foreach ($ar as $key => $value)
				{
					if (!in_array($key, $arNeededSettings))
						unset($ar[$key]);
				}
				return $ar;
			}
		}
		else
		{
			$res = self::$SECTIONS_SETTINGS_CACHE[$section_id];
			foreach ($res as $key => $value)
			{
				if (is_array($arNeededSettings) && !in_array($key, $arNeededSettings))
					unset($res[$key]);
				elseif (substr($res[$key], 0, 8) == '_PARENT_')
					$res[$key] = null;
			}
			return $res;
		}
	}

	public static function GetModuleSettings($arNeededSettings = false)
	{
		$arOptionsSettings = array(
			'UF_TIMEMAN' => true,
			'UF_TM_MAX_START' => COption::GetOptionInt('timeman', 'workday_max_start', 33300),
			'UF_TM_MIN_FINISH' => COption::GetOptionInt('timeman', 'workday_min_finish', 63900),
			'UF_TM_MIN_DURATION' => COption::GetOptionInt('timeman', 'workday_min_duration', 28800),
			'UF_TM_REPORT_REQ' => COption::GetOptionString('timeman', 'workday_report_required', 'A'),
			'UF_TM_ALLOWED_DELTA' => COption::GetOptionInt('timeman', 'workday_allowed_delta', '900'),
			'UF_TM_REPORT_TPL' => array(),
			'UF_TM_FREE' => false,
		);

		if (!$arNeededSettings)
		{
			return $arOptionsSettings;
		}
		else
		{
			$res = array();
			foreach ($arNeededSettings as $k)
			{
				$res[$k] = $arOptionsSettings[$k];
			}

			return $res;
		}
	}

	public static function GetSectionSettings($section_id, $arNeededSettings = null)
	{
		if (null == self::$SECTIONS_SETTINGS_CACHE)
			self::_GetTreeSettings();

		if ($section_id > 0)
		{
			$res = self::GetSectionPersonalSettings($section_id);

			$arSettings = is_array($arNeededSettings) ? $arNeededSettings : array('UF_TIMEMAN','UF_TM_MAX_START','UF_TM_MIN_FINISH','UF_TM_MIN_DURATION','UF_TM_REPORT_REQ','UF_TM_REPORT_TPL', 'UF_TM_FREE','UF_TM_REPORT_DATE','UF_TM_DAY','UF_REPORT_PERIOD','UF_TM_TIME', 'UF_TM_ALLOWED_DELTA');

			if (is_array($res) && count($arSettings) > 0)
			{
				$parent = 0;
				foreach ($res as $key => $v)
				{
					if (!in_array($key, $arSettings))
						unset($res[$key]);
				}

				foreach ($arSettings as $k => $key)
				{
					if (!is_array($res[$key]) && substr($res[$key], 0, 8) == '_PARENT_')
					{
						$parent = intval(substr($res[$key], 9));
						unset($res[$key]);
					}
					else
					{
						unset($arSettings[$k]);
					}
				}

				if (count($arSettings) > 0 && $parent > 0)
				{
					$res = array_merge($res, self::GetSectionSettings($parent, $arSettings));
				}

				if ($arNeededSettings === null)
				{
					foreach ($res as $key => $value)
					{
						if (!is_array($res[$key]) && substr($res[$key], 0, 8) == '_PARENT_')
						{
							$res[$key] = '';
						}
					}
				}

				if (isset($res['UF_TIMEMAN']) && !$res['UF_TIMEMAN'])
					$res['UF_TIMEMAN'] = 'Y';
				if (isset($res['UF_TM_REPORT_TPL']) && !is_array($res['UF_TM_REPORT_TPL']))
					$res['UF_TM_REPORT_TPL'] = array();

				return $res;
			}
		}

		return array();
	}

	private static function _GetTreeSettings()
	{
		global $USER_FIELD_MANAGER, $CACHE_MANAGER;

		self::$SECTIONS_SETTINGS_CACHE = array();

		$ibDept = COption::GetOptionInt('intranet', 'iblock_structure', false);

		$cache_id = 'timeman|structure_settings|'.$ibDept;

		if (CACHED_timeman_settings !== false
			&& $CACHE_MANAGER->Read(CACHED_timeman_settings, $cache_id, "timeman_structure_".$ibDept))
		{
			self::$SECTIONS_SETTINGS_CACHE = $CACHE_MANAGER->Get($cache_id);
		}
		else
		{
			$arAllFields = $USER_FIELD_MANAGER->GetUserFields('IBLOCK_'.$ibDept.'_SECTION');

			$arUFValues = array();

			$arEnumFields = array('UF_TIMEMAN', 'UF_TM_REPORT_REQ', 'UF_TM_FREE','UF_REPORT_PERIOD');
			foreach ($arEnumFields as $fld)
			{
				$dbRes = CUserFieldEnum::GetList(array(), array(
					'USER_FIELD_ID' => $arAllFields[$fld]['ID'],
				));
				while ($arRes = $dbRes->Fetch())
				{
					$arUFValues[$arRes['ID']] = $arRes['XML_ID'];
				}
			}

			$arSettings = array('UF_TIMEMAN','UF_TM_MAX_START','UF_TM_MIN_FINISH','UF_TM_MIN_DURATION','UF_TM_REPORT_REQ','UF_TM_REPORT_TPL', 'UF_TM_FREE','UF_TM_REPORT_DATE','UF_TM_DAY','UF_REPORT_PERIOD','UF_TM_TIME', 'UF_TM_ALLOWED_DELTA');
			$arReportSettings = array('UF_TM_REPORT_DATE','UF_TM_DAY','UF_TM_TIME');
			$dbRes = CIBlockSection::GetList(
				array("LEFT_MARGIN"=>"ASC"),
				array('IBLOCK_ID' => $ibDept, 'ACTIVE' => 'Y'),
				false,
				array('ID','IBLOCK_SECTION_ID','UF_TIMEMAN','UF_TM_MAX_START','UF_TM_MIN_FINISH','UF_TM_MIN_DURATION','UF_TM_REPORT_REQ','UF_TM_REPORT_TPL', 'UF_TM_FREE','UF_REPORT_PERIOD','UF_TM_REPORT_DATE','UF_TM_DAY','UF_TM_TIME','UF_TM_ALLOWED_DELTA')
			);
			while ($arRes = $dbRes->Fetch())
			{
				$arSectionSettings = array();
				foreach ($arSettings as $key)
				{
					$arSectionSettings[$key] = ($arRes[$key] && $arRes[$key] != '00:00'
						? (
							isset($arUFValues[$arRes[$key]]) && !in_array($key,$arReportSettings)
							? $arUFValues[$arRes[$key]]
							: (
								in_array($key,$arReportSettings)
								? $arRes[$key]
								:(
									is_array($arRes[$key])
									? $arRes[$key]
									: self::MakeShortTS($arRes[$key])
								)

							)
						)
						: (
							$arRes['IBLOCK_SECTION_ID'] > 0
							? '_PARENT_|'.$arRes['IBLOCK_SECTION_ID']
							: ''
						)
					);
				}

				self::$SECTIONS_SETTINGS_CACHE[$arRes['ID']] = $arSectionSettings;
			}

			if (CACHED_timeman_settings !== false)
			{
				$CACHE_MANAGER->Set($cache_id, self::$SECTIONS_SETTINGS_CACHE);
			}
		}
	}

	/* time functions */
	public static function RemoveHoursTS($ts)
	{
		return $ts-self::GetTimeTS($ts, true);
	}

	public static function GetTimeTS($datetime, $bTS = false)
	{
		$ts = $bTS ? $datetime : MakeTimeStamp($datetime);

		if ($ts < 86400) // partial time
			return $ts;
		else
			return ($ts+date('Z')) % 86400;
	}

	public static function FormatTime($ts, $bTS = false)
	{
		$ts = self::GetTimeTS($ts, $bTS);
		return str_pad(intval($ts/3600), 2, '0', STR_PAD_LEFT).':'.str_pad(intval(($ts%3600)/60), 2, '0', STR_PAD_LEFT);
	}

	public static function FormatTimeOut($ts)
	{
		$ts = MakeTimeStamp(ConvertTimeStamp()) + $ts%86400;
		return FormatDate(IsAmPmMode() ? 'h:i a' : 'H:i', $ts);
	}

	public static function MakeShortTS($time)
	{
		static $arCoefs = array(3600, 60, 1);

		if ($time === intval($time))
			return $time % 86400;

		$amPmTime = explode(' ', $time);
		if (count($amPmTime) > 1)
		{
			$time = $amPmTime[0];
			$mt = $amPmTime[1];
		}

		$arValues = explode(':', $time);

		$cnt = count($arValues);
		if ($cnt <= 1)
			return 0;
		elseif ($cnt <= 2)
			$arValues[] = 0;

		// if time as AmPm
		if (!empty($mt) && strcasecmp($mt, 'pm')===0)
		{
			if ($arValues[0] < 12)
				$arValues[0] = $arValues[0] + 12;
		}

		$ts = 0;
		for ($i = 0; $i < 3; $i++)
		{
			$ts += intval($arValues[$i] * $arCoefs[$i]);
		}

		return $ts % 86400;
	}

	public static function ConvertShortTS($ts, $strDate = false)
	{
		if (!$strDate)
			$strDate = ConvertTimeStamp(false, 'SHORT');;

		return MakeTimeStamp($strDate) + $ts % 86400;
	}

	public static function GetUserManagers($USER_ID, $bCheckExistance = true)
	{
		$arStruct = CIntranetUtils::GetStructure();

		$arHeads = array();

		foreach ($arStruct['DATA'] as $dpt => $arDpt)
		{
			if (in_array($USER_ID, $arDpt['EMPLOYEES']))
			{
				$arCurDpt = $arDpt;

				while (
					(
						!$arCurDpt['UF_HEAD']
						|| $arCurDpt['UF_HEAD'] == $USER_ID
						|| (
							$bCheckExistance
							&& (
								!($arUser = CUser::GetByID($arCurDpt['UF_HEAD'])->Fetch())
								|| $arUser['ACTIVE'] == 'N'
							)
						)
					)
					&& $arCurDpt['IBLOCK_SECTION_ID'] > 0
				)
				{
					$arCurDpt = $arStruct['DATA'][$arCurDpt['IBLOCK_SECTION_ID']];
				}

				if ($arCurDpt['UF_HEAD'])
				{
					$arHeads[] = $arCurDpt['UF_HEAD'];
				}
			}
		}

		return array_unique($arHeads);
	}
}

/********************** calendars interface ********************/

abstract class ITimeManCalendar
{
	abstract public function Add($arParams);
	abstract public function Get($arParams);
}

class CTimeManCalendar
{
	private static $cal = null;
	private static function _Init()
	{
		if (COption::GetOptionString("intranet", "calendar_2", "N") == "Y" && CModule::IncludeModule('calendar'))
		{
			self::$cal = new _CTimeManCalendarNew();
		}
		else
		{
			self::$cal = new _CTimeManCalendarOld();
		}
	}

	public static function Add($arParams)
	{
		if (!self::$cal) self::_Init();
		return self::$cal->Add($arParams);
	}

	public static function Get($arParams)
	{
		if (!self::$cal) self::_Init();
		return self::$cal->Get($arParams);
	}
}

class _CTimeManCalendarNew extends ITimeManCalendar
{
	public function Add($arParams)
	{
		global $USER;

		$today = CTimeMan::RemoveHoursTS(time());
		$data = array(
			'CAL_TYPE' => 'user',
			'OWNER_ID' => $USER->GetID(),
			'NAME' => $arParams['name'],
			'DT_FROM' => ConvertTimeStamp($today + CTimeMan::MakeShortTS($arParams['from']), 'FULL'),
			'DT_TO' => ConvertTimeStamp($today + CTimeMan::MakeShortTS($arParams['to']), 'FULL'),
		);
		if ($arParams['absence'] == 'Y')
			$data['ACCESSIBILITY'] = 'absent';

		return CCalendar::SaveEvent(array(
			'arFields' => $data,
			'userId' => $USER->GetID(),
			'autoDetectSection' => true,
			'autoCreateSection' => true
		));
	}

	public function Get($arParams)
	{
		global $USER;

		$arEvents = CCalendarEvent::GetList(
			array(
				'arFilter' => array(
					"ID" => $arParams['ID'],
					"DELETED" => "N"
				),
				'parseRecursion' => true,
				'fetchAttendees' => true,
				'checkPermissions' => true
			)
		);

		if (is_array($arEvents) && count($arEvents) > 0)
		{
			$arEvent = $arEvents[0];
			if ($arEvent['IS_MEETING'])
			{
				$arGuests = $arEvent['~ATTENDEES'];
				$arEvent['GUESTS'] = array();

				foreach ($arGuests as $guest)
				{
					$arEvent['GUESTS'][] = array(
						'id' => $guest['USER_ID'],
						'name' => CUser::FormatName(CSite::GetNameFormat(false), $guest, true),
						'status' => $guest['STATUS'],
						'accessibility' => $guest['ACCESSIBILITY'],
						'bHost' => $guest['USER_ID'] == $arEvent['MEETING_HOST'],

					);

					if ($guest['USER_ID'] == $USER->GetID())
					{
						$arEvent['STATUS'] = $guest['STATUS'];
					}
				}
			}

			$set = CCalendar::GetSettings();
			$url = str_replace(
				'#user_id#', $arEvent['CREATED_BY'], $set['path_to_user_calendar']
			).'?EVENT_ID='.$arEvent['ID'];

			return array(
				'ID' => $arEvent['ID'],
				'NAME' => $arEvent['NAME'],
				'DETAIL_TEXT' => $arEvent['DESCRIPTION'],
				'DATE_FROM' => $arEvent['DATE_FROM'],
				'DATE_TO' => $arEvent['DATE_TO'],
				'ACCESSIBILITY' => $arEvent['ACCESSIBILITY'],
				'IMPORTANCE' => $arEvent['IMPORTANCE'],
				'STATUS' => $arEvent['STATUS'],
				'IS_MEETING' => $arEvent['IS_MEETING'] ? 'Y' : 'N',
				'GUESTS' => $arEvent['GUESTS'],
				'URL' => $url,
			);
		}
	}
}

class _CTimeManCalendarOld extends ITimeManCalendar
{
	public function Add($arParams)
	{
		global $USER;

		$res = null;

		$calendar_id = $arParams['calendar_id'];

		$calIblock = COption::GetOptionInt('intranet', 'iblock_calendar', null, $arParams['site_id']);
		$calIblockSection = CEventCalendar::GetSectionIDByOwnerId($USER->GetID(), 'USER', $calIblock);

		if (!$calendar_id)
			$calendar_id = CUserOptions::GetOption('timeman', 'default_calendar', 0);

		if ($calIblockSection > 0)
		{
			$arCalendars = CEventCalendar::GetCalendarList(array($calIblock, $calIblockSection, 0, 'USER'));

			if (count($arCalendars) == 1)
			{
				if (
					$calendar_id
					&& $calendar_id != $arCalendars[0]['ID']
				)
				{
					CUserOptions::DeleteOption('timeman', 'default_calendar');
				}

				$calendar_id = $arCalendars[0]['ID'];
			}
			else
			{
				$bCalendarFound = false;

				$arCalsList = array();
				foreach ($arCalendars as $cal)
				{
					if ($cal['ID'] == $calendar_id)
					{
						$bCalendarFound = true;
						break;
					}

					$arCalsList[] = array(
						'ID' => $cal['ID'],
						'NAME' => $cal['NAME'],
						'COLOR' => $cal['COLOR']
					);
				}

				if (!$bCalendarFound)
				{
					$bReturnRes = true;
					$res = array('error_id' => 'CHOOSE_CALENDAR', 'error' => array('TEXT' => GetMessage('TM_CALENDAR_CHOOSE'), 'CALENDARS' => $arCalsList));
				}
			}
		}

		if (!$bReturnRes)
		{
			if (!$calIblockSection)
				$calIblockSection = 'none';

			$today = CTimeMan::RemoveHoursTS(time());

			$data = array(
				'DATE_FROM' => $today + CTimeMan::MakeShortTS($arParams['from']),
				'DATE_TO' => $today + CTimeMan::MakeShortTS($arParams['to']),
				'NAME' => $arParams['name'],
				'ABSENCE' => $arParams['absence'] == 'Y'
			);

			$obCalendar = new CEventCalendar();
			$obCalendar->Init(array(
					'ownerType' => 'USER',
					'ownerId' => $USER->GetID(),
					'bOwner' => true,
					'iblockId' => $calIblock,
					'bCache' => false
				));

			$arPermissions = $obCalendar->GetPermissions(
				array(
					'setProperties' => true
				)
			);

			$arRes = array(
				'iblockId' => $obCalendar->iblockId,
				'ownerType' => $obCalendar->ownerType,
				'ownerId' => $obCalendar->ownerId,
				'bNew' => true,
				'fullUrl' => $obCalendar->fullUrl,
				'userId' => $obCalendar->userId,
				'pathToUserCalendar' => $obCalendar->pathToUserCalendar,
				'pathToGroupCalendar' => $obCalendar->pathToGroupCalendar,
				'userIblockId' => $obCalendar->userIblockId,
				'calendarId' => $calendar_id,
				'sectionId' => $calIblockSection,

				'dateFrom' => ConvertTimeStamp($data['DATE_FROM'], 'FULL'),
				'dateTo' => ConvertTimeStamp($data['DATE_TO'], 'FULL'),
				'name' => $data['NAME'],
				'desc' => '',
				'prop' => array(
					'ACCESSIBILITY' => $data['ABSENCE'] ? 'absent' : 'busy',
				),
				'notDisplayCalendar' => true
			);

			if ($GLOBALS['BX_TIMEMAN_RECENTLY_ADDED_EVENT_ID'] = $obCalendar->SaveEvent($arRes))
			{
				if ($_REQUEST['cal_set_default'] == 'Y')
					CUserOptions::SetOption('timeman', 'default_calendar', $calendar_id);
			}
		}

		return $res;
	}

	public function Get($arParams)
	{
		$ID = intval($arParams['ID']);
		$site_id = $arParams['site_id'];

		$calIblock = COption::GetOptionInt('intranet', 'iblock_calendar', null, $site_id);

		$dbRes = CIBlockElement::GetByID($ID);
		if ($arRes = $dbRes->Fetch())
			$calIblockSection = $arRes['IBLOCK_SECTION_ID'];
		else
			return false;

		CModule::IncludeModule('socialnetwork');

		$obCalendar = new CEventCalendar();
		$obCalendar->Init(array(
			'ownerType' => 'USER',
			'ownerId' => $arRes['CREATED_BY'],
			'bOwner' => true,
			'iblockId' => $calIblock,
			'userIblockId' => $calIblock
		));

		$arPermissions = $obCalendar->GetPermissions(
			array(
				'setProperties' => true,
			)
		);

		$arEvents = $obCalendar->GetEvents(array(
			'iblockId' => $calIblock,
			'sectionId' => $calIblockSection,
			'eventId' => $ID,
			'bLoadAll' => true,
			'ownerType' => 'USER'
		));

		return $arEvents[0];
	}
}
?>