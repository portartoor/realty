<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2013 Bitrix
 */

namespace Bitrix\Intranet;

use Bitrix\Main;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlException;
use Bitrix\Main\DB\SqlExpression;
use Bitrix\Main\Entity;
use Bitrix\Main\Type;

class UStat
{
	// if user uses this amount of services, they are involved
	const INVOLVEMENT_SERVICE_COUNT = 4;

	public static function incrementCounter($userId, $section)
	{
		// try to update
		// if no update for DAY table, then:
		//   check if user is absent today, then we need to update ACTIVE_USERS counters for depts and company
		// endthen;

		$currentHour = new Type\DateTime(date('Y-m-d H:00:00'), 'Y-m-d H:00:00');

		// hourly stats
		$updResult = UStatHourTable::update(
			array('USER_ID' => $userId, 'HOUR' => $currentHour),
			array($section => new SqlExpression('?# + 1', $section), 'TOTAL' => new SqlExpression('?# + 1', 'TOTAL'))
		);

		if (!$updResult->getAffectedRowsCount())
		{
			UStatHourTable::add(array('USER_ID' => $userId, 'HOUR' => $currentHour, $section => 1, 'TOTAL' => 1));
		}

		// daily stats
		$updResult = UStatDayTable::update(
			array('USER_ID' => $userId, 'DAY' => $currentHour),
			array($section => new SqlExpression('?# + 1', $section), 'TOTAL' => new SqlExpression('?# + 1', 'TOTAL'))
		);

		if (!$updResult->getAffectedRowsCount())
		{
			UStatDayTable::add(array('USER_ID' => $userId, 'DAY' => $currentHour, $section => 1, 'TOTAL' => 1));

			// check if recounting ACTIVE_USERS is required
			$calendData = \CIntranetUtils::GetAbsenceData(array(
				'DATE_START' => \ConvertTimeStamp(mktime(0, 0, 0), 'FULL'), // current day start
				'DATE_FINISH' => \ConvertTimeStamp(mktime(23, 59, 59), 'FULL'), // current day end
				'USERS' => array($userId),
				'PER_USER' => false
			));

			$userAbsentsToday = static::checkTodayAbsence($calendData);


			if ($userAbsentsToday)
			{
				static::recountDeptartmentsActiveUsers($userId);
				static::recountCompanyActiveUsers();
			}
		}

		// get user departments
		$allUDepts = static::getUsersDepartments();
		$userDepts = $allUDepts[$userId];

		// hourly department stats
		foreach ($userDepts as $userDept)
		{
			$updResult = DStatHourTable::update(
				array('DEPT_ID' => $userDept, 'HOUR' => $currentHour),
				array($section => new SqlExpression('?# + 1', $section), 'TOTAL' => new SqlExpression('?# + 1', 'TOTAL'))
			);

			if (!$updResult->getAffectedRowsCount())
			{
				DStatHourTable::add(array('DEPT_ID' => $userDept, 'HOUR' => $currentHour, $section => 1, 'TOTAL' => 1));
			}
		}


		// daily department stats
		foreach ($userDepts as $userDept)
		{
			$updResult = DStatDayTable::update(
				array('DEPT_ID' => $userDept, 'DAY' => $currentHour),
				array($section => new SqlExpression('?# + 1', $section), 'TOTAL' => new SqlExpression('?# + 1', 'TOTAL'))
			);

			if (!$updResult->getAffectedRowsCount())
			{
				DStatDayTable::add(array('DEPT_ID' => $userDept, 'DAY' => $currentHour, $section => 1, 'TOTAL' => 1));
			}
		}
	}

	/**
	 * Recounts daily statistics: active users, activity and involvement for today and previous active day
	 */
	public static function recount()
	{
		static::recountDeptartmentsActiveUsers();
		static::recountCompanyActiveUsers();
		static::recountDailyInvolvement();
	}

	/**
	 * Recounts hourly company activity
	 */
	public static function recountHourlyCompanyActivity()
	{
		$currentHour = new Type\DateTime(date('Y-m-d H:00:00'), 'Y-m-d H:00:00');

		// last record
		$lastRow = DStatHourTable::getRow(array(
			'filter' => array('=DEPT_ID' => 0, '<=HOUR' => \ConvertTimeStamp($currentHour->getValue()->getTimestamp(), "FULL")),
			'order' => array('HOUR' => 'DESC'),
			'limit' => 1
		));

		if (!empty($lastRow))
		{
			$lastRowDate = is_object($lastRow['HOUR']) ? $lastRow['HOUR'] : new Type\DateTime($lastRow['HOUR'], 'Y-m-d H:00:00');
			$lastActivity = static::getHourlyCompanyActivitySince($lastRowDate);
		}
		else
		{
			// first ever company activity
			$lastActivity = static::getHourlyCompanyActivitySince(null);
		}

		// update db
		foreach ($lastActivity as $activity)
		{
			// skip if nothing changed for last hour
			if ($lastRow['HOUR'] === $activity['HOUR'] && $lastRow['TOTAL'] === $activity['TOTAL'])
			{
				continue;
			}

			$activityHour = is_object($activity['HOUR']) ? $activity['HOUR'] : new Type\DateTime($activity['HOUR'], 'Y-m-d H:00:00');
			unset($activity['HOUR']);

			$updResult = DStatHourTable::update(array('DEPT_ID' => 0, 'HOUR' => $activityHour), $activity);

			if (!$updResult->getAffectedRowsCount())
			{
				try
				{
					DStatHourTable::add(array_merge(array('DEPT_ID' => 0, 'HOUR' => $activityHour), $activity));
				}
				catch (SqlException $e) {}
			}
		}
	}

	public static function getStatusInformation()
	{
		// 1. activity score: emulate last 60 minutes
		$data = array();

		$currentHour = ConvertTimeStamp(mktime(date('G'), 0, 0), 'FULL');
		$previousHour = ConvertTimeStamp(mktime(date('G')-1, 0, 0), 'FULL');

		$result = DStatHourTable::getList(array(
			'select' => array('HOUR', 'TOTAL'),
			'filter' => array('=DEPT_ID' => 0, '=HOUR' => array($currentHour, $previousHour))
		));

		while ($row = $result->fetch())
		{
			$data[ConvertTimeStamp($row['HOUR']->getTimestamp(), 'FULL')] = $row['TOTAL'];
		}

		$currentActivity = isset($data[$currentHour]) ? (int) $data[$currentHour] : 0;

		if (isset($data[$previousHour]))
		{
			// emulation of [60 - CURRENT_MINUTES] of previous hour
			$currentActivity += round($data[$previousHour] * (1 - date('i') / 60));
		}

		// 2. involvement: last 24 hours
		// SELECT COUNT(1) AS `INVOLVED_COUNT` FROM (SELECT CASE WHEN
		//		(CASE WHEN SUM(TASKS) > 0 THEN 1 ELSE 0 END + CASE WHEN SUM(CRM) > 0 THEN 1 ELSE 0 END + ...)  >= 4
		//		THEN 1 ELSE 0 END) AS INVOLVED FROM ... GROUP BY USER_ID)
		// WHERE INVOLVED = 1
		$names = UStatHourTable::getSectionNames();

		$fieldExpressions = array_fill(0, count($names), 'CASE WHEN SUM(%s) > 0 THEN 1 ELSE 0 END');

		// user involved if used 4 or more services for last 24 hours
		$involvedExpression = sprintf('CASE WHEN (%s) >= %d THEN 1 ELSE 0 END',
			join (' + ', $fieldExpressions), static::INVOLVEMENT_SERVICE_COUNT
		);

		// subquery
		$queryByUser = new Entity\Query(UStatHourTable::getEntity());

		$queryByUser->setSelect(array(
			'USER_ID',
			'INVOLVED' => array(
				'data_type' => 'integer',
				'expression' => array_merge(array($involvedExpression), $names)
			)))
			->setFilter(array(
				'><HOUR' => array(
					ConvertTimeStamp(mktime(date('G'), 0, 0, date('n'), date('j')-1), 'FULL'), // prev day, same hour
					ConvertTimeStamp(time(), 'FULL')
				)
			))
			->setGroup('USER_ID');

		// main query
		$query = new Entity\Query($queryByUser);

		$query->setSelect(array(
			'INVOLVED_COUNT' => array(
				'data_type' => 'integer',
				'expression' => array('COUNT(1)')
			)))
			->setFilter(array('=INVOLVED' => 1));

		$data = $query->exec()->fetch();
		$currentInvolvement = (int) $data['INVOLVED_COUNT'];

		// 3. total employees
		$usersDepartments = static::getUsersDepartments();
		$currentTotalUsers = count($usersDepartments);

		// 4. online employees
		$result = Main\UserTable::getList(array(
			'select' => array('ONLINE_COUNT' => array(
				'data_type' => 'integer',
				'expression' => array('COUNT(1)')
			)),
			'filter' => array('=IS_ONLINE' => true)
		));

		$data = $result->fetch();
		$currentUsersOnline = (int) $data['ONLINE_COUNT'];

		// 5. absentees
		$currentUsersAbsent = 0;
		$allUsers = array_keys($usersDepartments);

		$allAbsenceData = \CIntranetUtils::GetAbsenceData(array(
			'DATE_START' => ConvertTimeStamp(mktime(0, 0, 0), 'FULL'), // current day start
			'DATE_FINISH' => ConvertTimeStamp(mktime(23, 59, 59), 'FULL'), // current day end
			'PER_USER' => true
		));

		foreach ($allUsers as $userId)
		{
			if (isset($allAbsenceData[$userId]) && static::checkTodayAbsence($allAbsenceData[$userId]))
			{
				++$currentUsersAbsent;
			}
		}

		// done!
		return array(
			'ACTIVITY' => $currentActivity,
			'INVOLVEMENT' => $currentInvolvement,
			'TOTAL_USERS' => $currentTotalUsers,
			'USERS_ONLINE' => $currentUsersOnline,
			'USERS_ABSENT' => $currentUsersAbsent
		);
	}

	protected static function getHourlyCompanyActivitySince(Type\DateTime $hour = null)
	{
		$query = new Entity\Query('Bitrix\\Intranet\\UStatHourTable');

		// set all activity columns
		$uStatFields = UStatHourTable::getEntity()->getFields();

		foreach ($uStatFields as $uStatField)
		{
			if ($uStatField instanceof Entity\ScalarField && !$uStatField->isPrimary())
			{
				$query->addSelect(array(
					'data_type' => 'integer',
					'expression' => array('SUM(%s)', $uStatField->getName())
				), $uStatField->getName());
			}
		}

		// add & automatically group by hour
		$query->addSelect('HOUR');

		// add filter by date
		if ($hour !== null)
		{
			$query->setFilter(array('>=HOUR' => \ConvertTimeStamp($hour->getValue()->getTimestamp(), 'FULL')));
		}

		// collect activity
		$activity = array();

		$result = $query->exec();

		while ($row = $result->Fetch())
		{
			$activity[] = $row;
		}

		return $activity;
	}

	protected static function recountDeptartmentsActiveUsers($forUserId = null)
	{
		$updates = array();

		list($deptData, $users) = static::getActivityInfo();

		// prepare data
		if (!empty($forUserId))
		{
			foreach ($deptData as $deptId => $department)
			{
				if (in_array($forUserId, $department['EMPLOYEES']))
				{
					$updates[$deptId] = $department['ACTIVE_USERS'];
				}
			}
		}
		else
		{
			foreach ($deptData as $deptId => $department)
			{
				$updates[$deptId] = $department['ACTIVE_USERS'];
			}
		}

		$currentHour = new Type\DateTime(date('Y-m-d H:00:00'), 'Y-m-d H:00:00');

		foreach ($updates as $deptId => $activeUsersCount)
		{
			$updResult = DStatDayTable::update(array('DEPT_ID' => $deptId, 'DAY' => $currentHour), array('ACTIVE_USERS' => $activeUsersCount));

			if (!$updResult->getAffectedRowsCount())
			{
				// if new ACTIVE_USERS value equal one in DB, affectedRows will return 0
				// in this case ignore duplicate entry error while trying to insert same values
				try
				{
					DStatDayTable::add(array('DEPT_ID' => $deptId, 'DAY' => $currentHour, 'ACTIVE_USERS' => $activeUsersCount));
				}
				catch (SqlException $e) {}
			}
		}
	}

	protected static function recountCompanyActiveUsers()
	{
		// if no record for today, then
		//  - update last record involment before today (usually yesterday)
		//  - insert new record for today
		// else
		//  - update record

		$currentDay = new Type\DateTime(date('Y-m-d 00:00:00'), 'Y-m-d 00:00:00');

		list($deptData, $users) = static::getActivityInfo();

		// today active users
		$activeUsers = count(array_filter($users, function($user) {
			return !$user['ABSENT'];
		}));

		// current record
		$todayRow = DStatDayTable::getByPrimary(array('DEPT_ID' => 0, 'DAY' => \ConvertTimeStamp(time(), "SHORT")))->fetch();

		// if no record for today, then
		if (empty($todayRow))
		{
			// update last record involvement before today (usually yesterday)
			$lastRow = DStatDayTable::getRow(array(
				'filter' => array('=DEPT_ID' => 0, '<DAY' => \ConvertTimeStamp(time(), "SHORT")),
				'order' => array('DAY' => 'DESC'),
				'limit' => 1
			));

			if (!empty($lastRow))
			{
				$lastRowDate = is_object($lastRow['DAY']) ? $lastRow['DAY'] : new Type\DateTime($lastRow['DAY'], 'Y-m-d');
				static::recountDailyInvolvement($lastRowDate);
			}

			// insert new record for today
			DStatDayTable::add(array('DEPT_ID' => 0, 'DAY' => $currentDay, 'ACTIVE_USERS' => $activeUsers));
		}
		else
		{
			// update current record
			if ($todayRow['ACTIVE_USERS'] != $activeUsers)
			{
				DStatDayTable::update(array('DEPT_ID' => 0, 'DAY' => $currentDay), array('ACTIVE_USERS' => $activeUsers));
			}
		}
	}

	/**
	 * Recounts involvement and activity score for selected day
	 * @param Type\DateTime $day
	 */
	protected static function recountDailyInvolvement(Type\DateTime $day = null)
	{
		// should be called only after recount*ActiveUsers
		// because we need ACTIVE_USERS already set up

		if ($day === null)
		{
			$day = new Type\DateTime(date('Y-m-d 00:00:00'), 'Y-m-d 00:00:00');
		}

		// users' departments
		$usersDepartments = static::getUsersDepartments();

		// add "company" for each user
		foreach ($usersDepartments as &$_usersDepartments)
		{
			$_usersDepartments[] = 0;
		}

		// count
		$result = UStatDayTable::getList(array('filter' => array(
			'=DAY' => \ConvertTimeStamp($day->getValue()->getTimestamp(), 'SHORT')
		)));

		while ($row = $result->fetch())
		{
			$invCount = 0;

			foreach ($row as $k => $v)
			{
				// skip non-activity fields
				if ($k == 'USER_ID' || $k == 'DAY')
				{
					continue;
				}

				// initialize
				foreach ($usersDepartments[$row['USER_ID']] as $deptId)
				{
					if (!isset($departments[$deptId][$k]))
					{
						$departments[$deptId][$k] = 0;
					}
				}

				// summarize
				foreach ($usersDepartments[$row['USER_ID']] as $deptId)
				{
					$departments[$deptId][$k] += $v;
				}

				// increment involvement count
				if ($k != 'TOTAL' && $v > 0)
				{
					++$invCount;
				}
			}

			// check involvement
			if ($invCount >= static::INVOLVEMENT_SERVICE_COUNT)
			{
				foreach ($usersDepartments[$row['USER_ID']] as $deptId)
				{
					if (!isset($departments[$deptId]['INVOLVED']))
					{
						$departments[$deptId]['INVOLVED'] = 0;
					}

					++$departments[$deptId]['INVOLVED'];
				}
			}

		}

		// normalize involved count
		foreach ($departments as &$_department)
		{
			if (!isset($_department['INVOLVED']))
			{
				$_department['INVOLVED'] = 0;
			}
		}

		// update db
		foreach ($departments as $deptId => $activity)
		{
			$activity['INVOLVEMENT'] = new SqlExpression('ROUND((?i / ?# * 100), 0)', $activity['INVOLVED'], 'ACTIVE_USERS');
			unset($activity['INVOLVED']);

			DStatDayTable::update(array('DEPT_ID' => $deptId, 'DAY' => $day), $activity);
		}
	}

	protected static function getActivityInfo()
	{
		// real active users
		$allTodayActiveUsers = array();
		$result = UStatDayTable::getList(array('filter' => array('=DAY' => \ConvertTimeStamp(time(), "SHORT"))));
		while ($row = $result->fetch())
		{
			$allTodayActiveUsers[$row['USER_ID']] = true;
		}

		// absence data from calendar
		$allAbsenceData = \CIntranetUtils::GetAbsenceData(array(
			'DATE_START' => ConvertTimeStamp(mktime(0, 0, 0), 'FULL'), // current day start
			'DATE_FINISH' => ConvertTimeStamp(mktime(23, 59, 59), 'FULL'), // current day end
			'PER_USER' => true
		));

		// departments and its' employees
		$allDepartments = array();

		// userid -> true (working) | false (absent)
		$allUsers = array();

		$companyStructure = \CIntranetUtils::GetStructure();

		foreach ($companyStructure['DATA'] as $departmentData)
		{
			// base structure
			$department = array(
				'EMPLOYEES' => array_filter(array_unique(array_merge(
					$departmentData['EMPLOYEES'], array($departmentData['UF_HEAD'])
				))),
				'ACTIVE_USERS' => 0
			);

			foreach ($department['EMPLOYEES'] as $employeeId)
			{
				$allUsers[$employeeId]['DEPARTMENTS'][] = $departmentData['ID'];

				// skip absentee
				if (isset($allUsers[$employeeId]['ABSENT']) && $allUsers[$employeeId]['ABSENT'] === true)
				{
					continue;
				}

				if (!isset($allUsers[$employeeId]['ABSENT']) &&
					isset($allAbsenceData[$employeeId]) && static::checkTodayAbsence($allAbsenceData[$employeeId]))
				{
					// but only if they are really not active today
					if (!isset($allTodayActiveUsers[$employeeId]))
					{
						$allUsers[$employeeId]['ABSENT'] = true;
						continue;
					}
				}

				// remember supposed & really active users
				++$department['ACTIVE_USERS'];

				$allUsers[$employeeId]['ABSENT'] = false;

			}

			$allDepartments[$departmentData['ID']] = $department;
		}

		return array($allDepartments, $allUsers);
	}

	protected static function checkTodayAbsence($absenceData)
	{
		$todayTimestamp = mktime(0, 0, 0);
		$tomorrowTimeStamp = mktime(0, 0, 0, date('n'), date('j')+1);

		foreach ($absenceData as $absence)
		{
			if (
				// today is one of absence day
				($absence['DT_FROM_TS'] < $todayTimestamp && $absence['DT_TO_TS'] >= $tomorrowTimeStamp) ||
				// today
				($absence['DT_FROM_TS'] == $todayTimestamp && $absence['DT_TO_TS'] == $todayTimestamp) ||
				// until this day
				($absence['DT_FROM_TS'] < $todayTimestamp && $absence['DT_TO_TS'] == $todayTimestamp) ||
				// since this day
				($absence['DT_FROM_TS'] == $todayTimestamp && $absence['DT_TO_TS'] >= $tomorrowTimeStamp)
			)
			{
				return true;
			}
		}

		return false;
	}

	protected static function getUsersDepartments()
	{
		$companyStructure = \CIntranetUtils::GetStructure();

		$users = array();

		foreach ($companyStructure['DATA'] as $departmentData)
		{
			$employees = array_filter(array_unique(array_merge(
				$departmentData['EMPLOYEES'], array($departmentData['UF_HEAD'])
			)));

			foreach ($employees as $employee)
			{
				if (!isset($users[$employee]))
				{
					$users[$employee] = array();
				}

				$users[$employee][] = $departmentData['ID'];
			}
		}

		return $users;
	}

	public function getGraphData($period)
	{

	}
}