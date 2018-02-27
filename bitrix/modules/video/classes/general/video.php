<?
class CVideo
{
	function CheckRooms($Params)
	{
		global $DB;
		/*
		$Params = Array(
			"regularity",
			"dateFrom",
			"dateTo",
			"iblockId",
			"ID",
		);
		*/
		if ($Params['regularity'] == "NONE")
		{
			$fromDateTime = MakeTimeStamp($Params['dateFrom']);
			$toDateTime = MakeTimeStamp($Params['dateTo']);
			if($toDateTime <= time())
				return 'expire';
			
			$maxUsers = COption::GetOptionInt("video", "video-room-users", 6);
			if(count($Params['members']) > $maxUsers)
				return "max_users_".$maxUsers;
			
			$maxRooms = COption::GetOptionInt("video", "video-room-count", 2);

			$arFilter = array(
				"ACTIVE" => "Y",
				"IBLOCK_ID" => $Params['iblockId'],
				"<DATE_ACTIVE_FROM" => $Params['dateTo'],
				">DATE_ACTIVE_TO" => $Params['dateFrom'],
				"PROPERTY_PERIOD_TYPE" => "NONE",
				"PROPERTY_VIDEOCALL" => false,
			);
			if(IntVal($Params["ID"]) > 0)
				$arFilter["!ID"] = IntVal($Params["ID"]);

			$i = 0;
			$dbElements = CIBlockElement::GetList(array("DATE_ACTIVE_FROM" => "ASC"), $arFilter, false, false, array("ID", "DATE_ACTIVE_FROM", "DATE_ACTIVE_TO"));
			while($arElements = $dbElements->GetNext())
			{
				$i++;
			}

			if($i >= $maxRooms)
				return 'reserved';

			$arPeriodicElements = CVideo::CheckPeriodic($fromDateTime, $toDateTime, $Params['iblockId']);
			if (count($arPeriodicElements) >= $maxRooms)
				return 'reserved';
		}
		return true;
	}
	
	function CheckPeriodic($fromDate, $toDate, $iblockId, $id = 0)
	{
		$iblockId = IntVal($iblockId);
		if ($iblockId <= 0)
			return array();

		$arPeriodElements = array();

		$arWeeklyPeriods = array();
		$arMonthlyPeriods = array();
		$arYearlyPeriods = array();

		$y1 = Date("Y", $fromDate);
		$m1 = Date("n", $fromDate);
		$d1 = Date("j", $fromDate);
		$w1 = Date("w", $fromDate);

		$fromDateOnly = MkTime(0, 0, 0, $m1, $d1, $y1);
		$toDateOnly = MkTime(0, 0, 0, Date("n", $toDate), Date("j", $toDate), Date("Y", $toDate));

		$n = IntVal(Round(($toDateOnly - $fromDateOnly) / 86400));

		$arWeeklyPeriods[0] = array(
			"year" => $y1,
			"monthFrom" => $m1,
			"dayFrom" => $d1,
			"week" => Date("W", $fromDate),
			"weekDayFrom" => ($w1 == 0 ? 6 : $w1 - 1),
			"weekTimeStart" => MkTime(0, 0, 0, $m1, $d1 - ($w1 == 0 ? 7 : $w1) + 1, $y1),
		);

		$arMonthlyPeriods[0] = array(
			"year" => $y1,
			"month" => $m1,
			"dayFrom" => $d1,
		);
		$arYearlyPeriods[0] = array(
			"year" => $y1,
			"monthFrom" => $m1,
			"dayFrom" => $d1,
		);
		if ($n < 1)
		{
			$arWeeklyPeriods[0]["monthTo"] = $arWeeklyPeriods[0]["monthFrom"];
			$arWeeklyPeriods[0]["dayTo"] = $arWeeklyPeriods[0]["dayFrom"];
			$arWeeklyPeriods[0]["weekDayTo"] = $arWeeklyPeriods[0]["weekDayFrom"];
			$arWeeklyPeriods[0]["weekTimeEnd"] = MkTime(0, 0, 0, $m1, $d1 - ($w1 == 0 ? 7 : $w1) + 1 + 7, $y1);

			$arMonthlyPeriods[0]["dayTo"] = $arMonthlyPeriods[0]["dayFrom"];

			$arYearlyPeriods[0]["monthTo"] = $arYearlyPeriods[0]["monthFrom"];
			$arYearlyPeriods[0]["dayTo"] = $arYearlyPeriods[0]["dayFrom"];
		}
		else
		{
			$jY = 0;
			$jM = 0;
			$jW = 0;
			for ($i = 1; $i <= $n; $i++)
			{
				$t = MkTime(0, 0, 0, $m1, $d1 + $i, $y1);

				if (Date("Y", $t) != $arYearlyPeriods[$jY]["year"])
				{
					$t1 = MkTime(0, 0, 0, $m1, $d1 + $i - 1, $y1);
					$arYearlyPeriods[$jY]["monthTo"] = Date("n", $t1);
					$arYearlyPeriods[$jY]["dayTo"] = Date("j", $t1);

					$jY++;

					$arYearlyPeriods[$jY] = array(
						"year" => Date("Y", $t),
						"monthFrom" => Date("n", $t),
						"dayFrom" => Date("j", $t),
					);
				}				
				if (Date("n", $t) != $arMonthlyPeriods[$jM]["month"])
				{
					$t1 = MkTime(0, 0, 0, $m1, $d1 + $i - 1, $y1);
					$arMonthlyPeriods[$jM]["dayTo"] = Date("j", $t1);

					$jM++;

					$arMonthlyPeriods[$jM] = array(
						"year" => Date("Y", $t),
						"month" => Date("n", $t),
						"dayFrom" => Date("j", $t),
					);
				}
				if (Date("W", $t) != $arWeeklyPeriods[$jW]["week"])
				{
					$t1 = MkTime(0, 0, 0, $m1, $d1 + $i - 1, $y1);
					$arWeeklyPeriods[$jW]["monthTo"] = Date("n", $t1);
					$arWeeklyPeriods[$jW]["dayTo"] = Date("j", $t1);
					$arWeeklyPeriods[$jW]["weekDayTo"] = (Date("w", $t1) == 0 ? 6 : Date("w", $t1) - 1);;
					$arWeeklyPeriods[$jW]["weekTimeEnd"] = MkTime(0, 0, 0, Date("n", $t1), Date("j", $t1) - (Date("w", $t1) == 0 ? 7 : Date("w", $t1)) + 1 + 7, Date("Y", $t1));

					$jW++;

					$arWeeklyPeriods[$jW] = array(
						"year" => Date("Y", $t),
						"monthFrom" => Date("n", $t),
						"dayFrom" => Date("j", $t),
						"week" => Date("W", $t),
						"weekDayFrom" => (Date("w", $t) == 0 ? 6 : Date("w", $t) - 1),
						"weekTimeStart" => MkTime(0, 0, 0, Date("n", $t), Date("j", $t) - (Date("w", $t) == 0 ? 7 : Date("w", $t)) + 1, Date("Y", $t)),
					);
				}
			}

			$t1 = MkTime(0, 0, 0, Date("n", $toDate), Date("j", $toDate), Date("Y", $toDate));

			$arWeeklyPeriods[$jW]["monthTo"] = Date("n", $t1);
			$arWeeklyPeriods[$jW]["dayTo"] = Date("j", $t1);
			$arWeeklyPeriods[$jW]["weekDayTo"] = (Date("w", $t1) == 0 ? 6 : Date("w", $t1) - 1);
			$arWeeklyPeriods[$jW]["weekTimeEnd"] = MkTime(0, 0, 0, Date("n", $t1), Date("j", $t1) - (Date("w", $t1) == 0 ? 7 : Date("w", $t1)) + 1 + 7, Date("Y", $t1));

			$arMonthlyPeriods[$jM]["dayTo"] = Date("j", $t1);

			$arYearlyPeriods[$jY]["monthTo"] = Date("n", $t1);
			$arYearlyPeriods[$jY]["dayTo"] = Date("j", $t1);
		}

		$id = IntVal($id);

		$arFilter = array(
			"ACTIVE" => "Y",
			"IBLOCK_ID" => $iblockId,
			"<DATE_ACTIVE_FROM" => Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME), $toDate),
			">DATE_ACTIVE_TO" => Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME), $fromDate),
			"!PROPERTY_PERIOD_TYPE" => "NONE",
			"PROPERTY_VIDEOCALL" => false,
		);

		if ($id > 0)
			$arFilter["!ID"] = $id;

		$dbElements = CIBlockElement::GetList(
			array("DATE_ACTIVE_FROM" => "ASC"),
			$arFilter,
			false,
			false,
			array("ID", "IBLOCK_ID", "NAME", "DATE_ACTIVE_FROM", "IBLOCK_SECTION_ID", "DATE_ACTIVE_TO", "CREATED_BY", "PROPERTY_PERIOD_TYPE", "PROPERTY_PERIOD_COUNT", "PROPERTY_EVENT_LENGTH", "PROPERTY_PERIOD_ADDITIONAL")
		);
		while ($arDbItem = $dbElements->GetNextElement())
		{
			$arElement = $arDbItem->GetFields();
			$arDates = array();

			$dateActiveFrom = MakeTimeStamp($arElement["DATE_ACTIVE_FROM"], FORMAT_DATETIME);
			$dateActiveTo = MakeTimeStamp($arElement["DATE_ACTIVE_TO"], FORMAT_DATETIME);

			$dateActiveFromDateOnly = MkTime(0, 0, 0, Date("n", $dateActiveFrom), Date("j", $dateActiveFrom), Date("Y", $dateActiveFrom));
			$dateActiveToDateOnly = MkTime(0, 0, 0, Date("n", $dateActiveTo), Date("j", $dateActiveTo), Date("Y", $dateActiveTo));

			if ($arElement["PROPERTY_PERIOD_TYPE_VALUE"] == "DAILY")
			{
				$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = IntVal($arElement["PROPERTY_PERIOD_COUNT_VALUE"]);
				if ($arElement["PROPERTY_PERIOD_COUNT_VALUE"] <= 0)
					$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = 1;

				if ($fromDate > $dateActiveFrom || $toDate <= $dateActiveFrom)
				{
					$dayShift = (IntVal(Round(($fromDate - $dateActiveFromDateOnly) / 86400)) % $arElement["PROPERTY_PERIOD_COUNT_VALUE"]);
					if ($dayShift > 0)
						$dayShift = $arElement["PROPERTY_PERIOD_COUNT_VALUE"] - $dayShift;

					$fromTimeTmp = MkTime(Date("H", $dateActiveFrom), Date("i", $dateActiveFrom), Date("s", $dateActiveFrom), Date("n", $fromDate), Date("j", $fromDate) + $dayShift, Date("Y", $fromDate));
				}
				else
				{
					$fromTimeTmp = $dateActiveFrom;
				}

				while ($dateActiveFrom <= $fromTimeTmp && $dateActiveTo > $fromTimeTmp
					&& $fromTimeTmp < $toDate
					&& $fromTimeTmp + $arElement["PROPERTY_EVENT_LENGTH_VALUE"] > $fromDate)
				{
					$toTimeTmp = $fromTimeTmp + $arElement["PROPERTY_EVENT_LENGTH_VALUE"];
					$arDates[] = array(
						"DATE_ACTIVE_FROM" => $fromTimeTmp,
						"DATE_ACTIVE_TO" => $toTimeTmp,
					);

					$fromTimeTmp = $fromTimeTmp + 86400 * $arElement["PROPERTY_PERIOD_COUNT_VALUE"];
				}
			}
			elseif ($arElement["PROPERTY_PERIOD_TYPE_VALUE"] == "WEEKLY")
			{
				$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = IntVal($arElement["PROPERTY_PERIOD_COUNT_VALUE"]);
				if ($arElement["PROPERTY_PERIOD_COUNT_VALUE"] <= 0)
					$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = 1;

				$arPeriodAdditional = array();
				if (StrLen($arElement["PROPERTY_PERIOD_ADDITIONAL_VALUE"]) > 0)
				{
					$arPeriodAdditionalTmp = Explode(",", $arElement["PROPERTY_PERIOD_ADDITIONAL_VALUE"]);
					foreach ($arPeriodAdditionalTmp as $v)
					{
						$v = IntVal($v);
						if ($v >= 0)
							$arPeriodAdditional[] = $v;
					}
				}
				if (Count($arPeriodAdditional) <= 0)
				{
					$w = Date("w", $dateActiveFrom);
					$arPeriodAdditional[] = ($w == 0 ? 6 : $w - 1);
				}

				$wscr = MkTime(0, 0, 0, Date("n", $dateActiveFrom), Date("j", $dateActiveFrom) - (Date("w", $dateActiveFrom) == 0 ? 7 : Date("w", $dateActiveFrom)) + 1, Date("Y", $dateActiveFrom));

				foreach ($arWeeklyPeriods as $arPeriod)
				{
					if ($arElement["PROPERTY_PERIOD_COUNT_VALUE"] > 1)
					{
						$weekShift = IntVal(Round(($arPeriod["weekTimeStart"] - $wscr) / 604800));
						if ($weekShift % $arElement["PROPERTY_PERIOD_COUNT_VALUE"] != 0)
							continue;
					}

					foreach ($arPeriodAdditional as $w)
					{
						if ($w >= $arPeriod["weekDayFrom"] && $w <= $arPeriod["weekDayTo"])
						{
							$fromTimeTmp = MkTime(Date("H", $dateActiveFrom), Date("i", $dateActiveFrom), Date("s", $dateActiveFrom), Date("n", $arPeriod["weekTimeStart"]), Date("j", $arPeriod["weekTimeStart"]) + $w, Date("Y", $arPeriod["weekTimeStart"]));

							if ($dateActiveFrom > $fromTimeTmp || $dateActiveTo <= $fromTimeTmp
								|| $fromTimeTmp >= $toDate
								|| $fromTimeTmp + $arElement["PROPERTY_EVENT_LENGTH_VALUE"] <= $fromDate)
								continue;

							$arDates[] = array(
								"DATE_ACTIVE_FROM" => $fromTimeTmp,
								"DATE_ACTIVE_TO" => $fromTimeTmp + $arElement["PROPERTY_EVENT_LENGTH_VALUE"],
							);
						}
					}
				}
			}
			elseif ($arElement["PROPERTY_PERIOD_TYPE_VALUE"] == "MONTHLY")
			{
				$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = IntVal($arElement["PROPERTY_PERIOD_COUNT_VALUE"]);
				if ($arElement["PROPERTY_PERIOD_COUNT_VALUE"] <= 0)
					$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = 1;

				foreach ($arMonthlyPeriods as $arPeriod)
				{
					$dm = Date("j", $dateActiveFrom);
					if ($arPeriod["dayFrom"] > $dm || $arPeriod["dayTo"] < $dm)
						continue;

					if ($arElement["PROPERTY_PERIOD_COUNT_VALUE"] > 1)
					{
						$nm = 0;
						if ($arPeriod["year"] == Date("Y", $dateActiveFrom))
						{
							$nm += $arPeriod["month"] - Date("n", $dateActiveFrom);
						}
						else
						{
							$nm += 12 - Date("n", $dateActiveFrom);
							if ($arPeriod["year"] != Date("Y", $dateActiveFrom) + 1)
								$nm += ($arPeriod["year"] - Date("Y", $dateActiveFrom) - 1) * 12;
							$nm += $arPeriod["month"];
						}

						if ($nm % $arElement["PROPERTY_PERIOD_COUNT_VALUE"] != 0)
							continue;
					}

					$fromTimeTmp = MkTime(Date("H", $dateActiveFrom), Date("i", $dateActiveFrom), Date("s", $dateActiveFrom), $arPeriod["month"], Date("j", $dateActiveFrom), $arPeriod["year"]);

					if ($dateActiveFrom > $fromTimeTmp || $dateActiveTo <= $fromTimeTmp
						|| $fromTimeTmp >= $toDate
						|| $fromTimeTmp + $arElement["PROPERTY_EVENT_LENGTH_VALUE"] <= $fromDate)
						continue;

					$arDates[] = array(
						"DATE_ACTIVE_FROM" => $fromTimeTmp,
						"DATE_ACTIVE_TO" => $fromTimeTmp + $arElement["PROPERTY_EVENT_LENGTH_VALUE"],
					);
				}
			}
			elseif ($arElement["PROPERTY_PERIOD_TYPE_VALUE"] == "YEARLY")
			{
				$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = IntVal($arElement["PROPERTY_PERIOD_COUNT_VALUE"]);
				if ($arElement["PROPERTY_PERIOD_COUNT_VALUE"] <= 0)
					$arElement["PROPERTY_PERIOD_COUNT_VALUE"] = 1;

				foreach ($arYearlyPeriods as $arPeriod)
				{
					$dm = Date("j", $dateActiveFrom);
					$my = Date("n", $dateActiveFrom);
					if ($my < $arPeriod["monthFrom"] || $my > $arPeriod["monthTo"])
						continue;

					if ($my == $arPeriod["monthFrom"] && $dm < $arPeriod["dayFrom"]
						|| $my == $arPeriod["monthTo"] && $dm > $arPeriod["dayTo"])
						continue;

					if ($arElement["PROPERTY_PERIOD_COUNT_VALUE"] > 1)
					{
						if (($arPeriod["year"] - Date("Y", $dateActiveFrom)) % $arElement["PROPERTY_PERIOD_COUNT_VALUE"] != 0)
							continue;
					}

					$fromTimeTmp = MkTime(Date("H", $dateActiveFrom), Date("i", $dateActiveFrom), Date("s", $dateActiveFrom), Date("n", $dateActiveFrom), Date("j", $dateActiveFrom), $arPeriod["year"]);

					if ($dateActiveFrom > $fromTimeTmp || $dateActiveTo <= $fromTimeTmp
						|| $fromTimeTmp >= $toDate
						|| $fromTimeTmp + $arElement["PROPERTY_EVENT_LENGTH_VALUE"] <= $fromDate)
						continue;

					$arDates[] = array(
						"DATE_ACTIVE_FROM" => $fromTimeTmp,
						"DATE_ACTIVE_TO" => $fromTimeTmp + $arElement["PROPERTY_EVENT_LENGTH_VALUE"],
					);
				}
			}

			if (Is_Array($arDates))
			{
				foreach ($arDates as $d)
				{
					$arElement["DATE_ACTIVE_FROM_TIME"] = $d["DATE_ACTIVE_FROM"];
					$arElement["DATE_ACTIVE_TO_TIME"] = $d["DATE_ACTIVE_TO"];
					$arElement["DATE_ACTIVE_FROM"] = Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME), $d["DATE_ACTIVE_FROM"]);
					$arElement["DATE_ACTIVE_TO"] = Date($GLOBALS["DB"]->DateFormatToPHP(FORMAT_DATETIME), $d["DATE_ACTIVE_TO"]);
					$arPeriodElements[] = $arElement;
				}
			}
		}

		for ($i = 0; $i < Count($arPeriodElements) - 1; $i++)
		{
			for ($j = $i + 1; $j < Count($arPeriodElements); $j++)
			{
				if ($arPeriodElements[$i]["DATE_ACTIVE_FROM_TIME"] > $arPeriodElements[$j]["DATE_ACTIVE_FROM_TIME"])
				{
					$t = $arPeriodElements[$i];
					$arPeriodElements[$i] = $arPeriodElements[$j];
					$arPeriodElements[$j] = $t;
				}
			}
		}

		return $arPeriodElements;
	}
	
	function CanUserMakeCall()
	{
		$arUGroups = $GLOBALS["USER"]->GetUserGroupArray();
		$def_group = COption::GetOptionString("video", "videocall-group", "");
		if(strlen($def_group) > 0)
			$arAvGroups = explode(",", $def_group);
			
		if(empty($arAvGroups) || (!empty($arAvGroups) && count(array_intersect($arUGroups, $arAvGroups)) > 0) || $GLOBALS["USER"]->IsAdmin())
			return true;
		
		return false;

	}
}
?>