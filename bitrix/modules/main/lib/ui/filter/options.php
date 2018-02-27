<?

namespace Bitrix\Main\UI\Filter;


use Bitrix\Main\Context;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;


class Options
{
	protected $filterId;
	protected $allOptions;
	protected $options;
	protected $filter;
	protected $filterPresets;
	protected $request;

	public function __construct($filterId, $filterPresets = array())
	{
		$this->filterId = $filterId;
		$this->options = array();
		$this->filter = array();

		$aOptions = \CUserOptions::getOption("main.ui.filter", $this->filterId, array());
		$needSave = false;

		if (!isset($aOptions["use_pin_preset"]))
		{
			$aOptions["use_pin_preset"] = true;
		}

		if (!is_array($aOptions))
		{
			$aOptions = array();
		}

		if (!is_array($aOptions["deleted_presets"]))
		{
			$aOptions["deleted_presets"] = array();
		}

		if (!empty($filterPresets) && is_array($filterPresets))
		{
			$aOptions["default_presets"] = $filterPresets;
			$needSave = true;
		}
		else
		{
			$aOptions["default_presets"] = array();
		}

		if (!isset($aOptions["default"]) || empty($aOptions["default"]) ||
			($aOptions["default"] === "default_filter" && $aOptions["use_pin_preset"]))
		{
			$aOptions["default"] = self::getDefaultPresetId($aOptions["default_presets"]);
			$needSave = true;
		}

		if (!isset($aOptions["filter"]) || empty($aOptions["filter"]) || !is_string($aOptions["filter"]))
		{
			$aOptions["filter"] = $aOptions["default"];
			$needSave = true;
		}

		if (!is_array($aOptions["filters"]))
		{
			$aOptions["filters"] = $aOptions["default_presets"];
			$needSave = true;
		}

		if (isset($aOptions["update_default_presets"]) &&
			$aOptions["update_default_presets"] == true &&
			!empty($filterPresets) &&
			is_array($filterPresets))
		{
			$aOptions["update_default_presets"] = false;
			$sort = 0;

			foreach ($filterPresets as $key => $defaultPreset)
			{
				$aOptions["filters"][$key] = $defaultPreset;
				$aOptions["filters"][$key]["sort"] = $sort;
				$sort++;
			}

			foreach ($aOptions["filters"] as $key => $preset)
			{
				if (!array_key_exists($key, $filterPresets))
				{
					$aOptions["filters"][$key]["sort"] = $sort;
					$sort++;
				}
			}
		}

		$this->allOptions = $aOptions;

		if ($needSave)
		{
			$this->save();
		}
	}

	protected function getRequest()
	{
		return Context::getCurrent()->getRequest();
	}

	protected static function getDefaultPresetId($presets = array())
	{
		$result = "default_filter";

		if (!empty($presets) && is_array($presets))
		{
			foreach ($presets as $presetId => $preset)
			{
				if (isset($preset["default"]) && $preset["default"])
				{
					$result = $presetId;
				}
			}
		}

		return $result;
	}

	public function getOptions()
	{
		return $this->allOptions;
	}

	public function pinPreset($presetId = 'default_filter')
	{
		if ($presetId === 'default_filter')
		{
			$this->allOptions["use_pin_preset"] = false;
		}
		else
		{
			$this->allOptions["use_pin_preset"] = true;
		}

		$this->allOptions["default"] = $presetId;
	}

	public function getFilter($fields = array())
	{
		$request = $this->getRequest();
		$result = array();
		$data = array();
		$rows = array();
		$applyFilter = $request->get("apply_filter");
		$isAjaxRequest = $request->get("ajax_request");

		if (!empty($fields) && $applyFilter !== null && $isAjaxRequest === null && !$request->isAjaxRequest())
		{
			foreach ($fields as $key => $field)
			{
				$id = $field["id"];
				$fromId = $id."_from";
				$toId = $id."_to";
				$quarterId = $id."_quarter";
				$yearId = $id."_year";
				$monthId = $id."_month";
				$nameId = $id."_name";
				$labelId = $id."_label";
				$valueId = $id."_value";
				$dateselId = $id."_datesel";
				$numselId = $id."_numsel";
				$type = $field["type"];

				if ($type == "date")
				{
					if ($request[$dateselId] !== null && ($request[$fromId] !== null ||
						$request[$toId] !== null ||
						$request[$quarterId] !== null ||
						$request[$yearId] !== null ||
						$request[$monthId] !== null))
					{
						$data[$dateselId] = $request[$dateselId];
						$data[$fromId] = $request[$fromId] !== null ? $request[$fromId] : "";
						$data[$toId] = $request[$toId] !== null ? $request[$toId] : "";
						$data[$yearId] = $request[$yearId] !== null ? $request[$yearId] : "";
						$data[$monthId] = $request[$monthId] !== null ? $request[$monthId] : "";
						$rows[] = $id;
 					}
				}
				else if ($type == "number")
				{
					if ($request[$numselId] !== null && ($request[$fromId] !== null || $request[$toId]))
					{
						$data[$numselId] = $request[$numselId];
						$data[$fromId] = $request[$fromId] !== null ? $request[$fromId] : "";
						$data[$toId] = $request[$toId] !== null ? $request[$toId] : "";
						$rows[] = $id;
					}
				}
				else if ($type == "custom_entity")
				{
					if ($request[$valueId] !== null && ($request[$nameId] !== null || $request[$labelId]))
					{
						$data[$valueId] = $request[$valueId];
						$data[$labelId] = $request[$nameId] !== null ? $request[$nameId] : $request[$labelId];
						$rows[] = $id;
					}
				}
				else
				{
					if ($request[$id] !== null)
					{
						$data[$id] = $request[$id];
						$rows[] = $id;
					}
				}
			}

			if (!empty($data))
			{
				$settings = array("fields" => $data, "rows" => $rows);
				$this->setFilterSettings("tmp_filter", $settings);
				$this->save();
			}
		}

		if (isset($_SESSION["main.ui.filter"][$this->filterId]["filter"]) &&
			!empty($_SESSION["main.ui.filter"][$this->filterId]["filter"]))
		{
			$this->allOptions["filter"] = $_SESSION["main.ui.filter"][$this->filterId]["filter"];
		}
		else
		{
			$this->allOptions["filter"] = $this->allOptions["default"];
		}

		if (!empty($this->allOptions["filter"]))
		{
			$presetId = $this->allOptions["filter"];

			if (isset($this->allOptions["filters"][$presetId]))
			{
				$preset = $this->allOptions["filters"][$presetId];

				if (isset($preset["fields"]) && is_array($preset["fields"]))
				{
					$fields = array();

					foreach ($preset["fields"] as $key => $field)
					{
						if ($field !== "" && stripos($key, "_label") === false)
						{
							if (stripos($key, "_datesel") !== false)
							{
								$key = str_replace("_datesel", "", $key);
								self::calcDates($key, $preset["fields"], $fields);

								if (!isset($fields[$key."_from"]) && !isset($fields[$key."_to"]))
								{
									unset($fields[$key."_datesel"]);
									unset($fields[$key."_to"]);
									unset($fields[$key."_from"]);
								}
							}
							elseif (stripos($key, "_numsel") !== false)
							{
								$newKey = str_replace("_numsel", "", $key);

								if (array_key_exists($newKey."_from", $preset["fields"]))
								{
									$fields[$newKey."_from"] = $preset["fields"][$newKey."_from"];
								}

								if (array_key_exists($newKey."_to", $preset["fields"]))
								{
									$fields[$newKey."_to"] = $preset["fields"][$newKey."_to"];
								}

								if (empty($fields[$newKey."_from"]) && empty($fields[$newKey."_to"]))
								{
									unset($fields[$newKey."_from"]);
									unset($fields[$newKey."_to"]);
									unset($fields[$newKey."_numsel"]);
								}
							}
							elseif (!stripos($key, "_from") && !stripos($key, "_to"))
							{
								$fields[$key] = $field;
							}
						}
					}

					$result = $fields;

					if (!empty($result) || $presetId !== "default_filter")
					{
						$result["PRESET_ID"] = $presetId;
						$result["FILTER_ID"] = $presetId;
						$result["FILTER_APPLIED"] = true;
						$result["FIND"] = $_SESSION["main.ui.filter"][$this->filterId]["filter_search"];
					}
				}
			}
		}

		return $result;
	}

	public function save()
	{
		\CUserOptions::setOption("main.ui.filter", $this->filterId, $this->allOptions);
	}

	public function saveForAll()
	{
		global $USER;
		if ($USER->CanDoOperation("edit_other_settings"))
		{
			$allSettings = \CUserOptions::getList(null, array("CATEGORY" => "main.ui.filter", "NAME" => $this->filterId));

			if ($allSettings)
			{
				while ($setting = $allSettings->fetch())
				{
					$options = $this->allOptions;

					if ($setting["USER_ID"] != 0)
					{
						$options["default_presets"] = $options["filters"];
					}

					\CUserOptions::SetOption("main.ui.filter", $this->filterId, $options, null, $setting["USER_ID"]);
				}
			}

			\CUserOptions::setOption("main.ui.filter.common", $this->filterId, $this->allOptions, true);
		}
	}

	public function setFilterRows($presetId='', $rows)
	{
		$aColsTmp = explode(",", $rows);
		$aCols = array();
		foreach($aColsTmp as $col)
			if(($col = trim($col)) <> "")
				$aCols[] = $col;
		if($presetId <> '')
			$this->allOptions["filters"][$presetId]["filter_rows"] = implode(",", $aCols);
		else
			$this->allOptions["filter_rows"] = implode(",", $aCols);
	}

	public function restore($settings = array())
	{
		if (!empty($settings))
		{
			foreach ($settings as $key => $preset)
			{
				$this->setFilterSettings($key, $preset, false);

				if (array_key_exists($key, $this->allOptions["deleted_presets"]))
				{
					unset($this->allOptions["deleted_presets"][$key]);
				}
			}

			$this->allOptions["default"] = self::getDefaultPresetId($this->allOptions["default_presets"]);
			$this->allOptions["use_pin_preset"] = true;
			$this->allOptions["filter"] = $this->allOptions["default"];
			unset($_SESSION["main.ui.filter"][$this->filterId]["filter"]);
		}
	}

	public function setFilterSettingsArray($settings = array())
	{
		if (!empty($settings))
		{
			foreach ($settings as $key => $preset)
			{
				if ($key !== "current_preset")
				{
					$this->setFilterSettings($key, $preset, false);
				}
			}

			$this->allOptions["filter"] = $settings["current_preset"];
			$request = $this->getRequest();

			if (isset($request["for_all"]))
			{
				$this->saveForAll();
			}
		}
	}

	public function setFilterSettings($presetId = "", $settings, $currentPreset = true)
	{
		$request = $this->getRequest();

		if (!empty($presetId))
		{
			if ($currentPreset && ($request->get("apply_filter") == "Y" || $request->get("clear_filter") == "Y"))
			{
				$_SESSION["main.ui.filter"][$this->filterId]["filter"] = $presetId;
			}

			if (!is_array($this->allOptions["filters"][$presetId]))
			{
				$this->allOptions["filters"][$presetId] = array();
			}

			if (isset($settings["name"]) && !empty($settings["name"]))
			{
				$this->allOptions["filters"][$presetId]["name"] = $settings["name"];
			}

			if (isset($settings["sort"]) && is_numeric($settings["sort"]))
			{
				$this->allOptions["filters"][$presetId]["sort"] = $settings["sort"];
			}

			if (isset($settings["fields"]))
			{
				if (array_key_exists("FIND", $settings["fields"]))
				{
					$_SESSION["main.ui.filter"][$this->filterId]["filter_search"] = $settings["fields"]["FIND"];
					unset($settings["fields"]["FIND"]);
				}

				if ($presetId == "default_filter")
				{
					$this->allOptions["filters"][$presetId]["fields"] = array();
				}
				else
				{
					$this->allOptions["filters"][$presetId]["fields"] = $settings["fields"];
				}
			}

			if (!isset($settings["fields"]) && isset($settings["clear_filter"]) && $settings["clear_filter"] === 'Y')
			{
				$this->allOptions["filters"][$presetId]["fields"] = array();
			}

			if (isset($settings["name"]) && !empty($settings["name"]))
			{
				$this->allOptions["filters"][$presetId]["name"] = $settings["name"];
			}

			if (isset($settings["rows"]))
			{
				$rows = $settings["rows"];
				if (is_array($rows))
				{
					$result = array();
					foreach($rows as $id)
					{
						$id = trim($id);
						if($id !== "")
						{
							$result[] = $id;
						}
					}
					$this->allOptions["filters"][$presetId]["filter_rows"] = implode(",", $result);
				}
				elseif (is_string($settings["rows"]))
				{
					$this->allOptions["filters"][$presetId]["filter_rows"] = $settings["rows"];
				}
			}
		}
	}

	public function deleteFilter($filter_id, $isDefault = false)
	{
		if ($isDefault)
		{
			$this->allOptions["deleted_presets"][$filter_id] = true;
		}

		unset($this->allOptions["filters"][$filter_id]);
	}

	public function isDeletedPreset($presetId)
	{
		return array_key_exists($presetId, $this->allOptions["deleted_presets"]);
	}

	/**
	 * Setup Default Filter Settings
	 * @param array $fields Default Filter Fields.
	 * @param array $rows Default Filter Rows.
	 */
	public function setupDefaultFilter(array $fields, array $rows)
	{
		$this->setFilterSettings("default_filter", array("fields" => $fields, "rows" => $rows));
		$this->save();
	}

	public static function calcDates($fieldId, $aInput, &$arRes)
	{
		switch($aInput[$fieldId."_datesel"])
		{
			case DateType::YESTERDAY :
			{
				$date = new Date();
				$dateTime = DateTime::createFromTimestamp($date->getTimestamp());
				$quarter = Quarter::getCurrent();
				$year = $dateTime->format("Y");
				$month = $dateTime->format("n");

				$arRes[$fieldId."_month"] = $month;
				$arRes[$fieldId."_quarter"] = $quarter;
				$arRes[$fieldId."_year"] = $year;
				$arRes[$fieldId."_from"] = $dateTime->add("- 1 days")->toString();
				$arRes[$fieldId."_to"] = $dateTime->add("+ 1 days - 1 second")->toString();
				break;
			}

			case DateType::CURRENT_DAY :
			{
				$date = new Date();
				$dateTime = DateTime::createFromTimestamp($date->getTimestamp());
				$month = $dateTime->format("n");
				$year = $dateTime->format("Y");

				$arRes[$fieldId."_month"] = $month;
				$arRes[$fieldId."_year"] = $year;
				$arRes[$fieldId."_quarter"] = Quarter::get($dateTime);
				$arRes[$fieldId."_from"] = $dateTime->toString();
				$arRes[$fieldId."_to"] = $dateTime->add("+ 1 days - 1 second")->toString();
				break;
			}

			case DateType::CURRENT_WEEK :
			{
				$date = Date::createFromTimestamp(strtotime("monday this week"));
				$dateTime = DateTime::createFromTimestamp($date->getTimestamp());
				$month = $dateTime->format("n");
				$year = $dateTime->format("Y");

				$arRes[$fieldId."_month"] = $month;
				$arRes[$fieldId."_year"] = $year;
				$arRes[$fieldId."_quarter"] = Quarter::get($dateTime);
				$arRes[$fieldId."_from"] = $dateTime->toString();
				$arRes[$fieldId."_to"] = $dateTime->add("7 days - 1 second")->toString();
				break;
			}

			case DateType::CURRENT_MONTH :
			{
				$date = Date::createFromTimestamp(strtotime("first day of this month"));
				$dateTime = DateTime::createFromTimestamp($date->getTimestamp());
				$month = $dateTime->format("n");
				$year = $dateTime->format("Y");

				$arRes[$fieldId."_month"] = $month;
				$arRes[$fieldId."_year"] = $year;
				$arRes[$fieldId."_quarter"] = Quarter::get($dateTime);
				$arRes[$fieldId."_from"] = $dateTime->toString();
				$arRes[$fieldId."_to"] = $dateTime->add("1 month - 1 second")->toString();
				break;
			}

			case DateType::CURRENT_QUARTER :
			{
				$dateTime = new DateTime();
				$quarter = Quarter::getCurrent();
				$year = $dateTime->format("Y");
				$month = $dateTime->format("n");
				$startDateTime = DateTime::createFromTimestamp(strtotime(Quarter::getStartDate($quarter, $year)));
				$endDateTime = DateTime::createFromTimestamp(strtotime(Quarter::getEndDate($quarter, $year)));

				$arRes[$fieldId."_datesel"] = DateType::QUARTER;
				$arRes[$fieldId."_month"] = $month;
				$arRes[$fieldId."_quarter"] = $quarter;
				$arRes[$fieldId."_year"] = $year;
				$arRes[$fieldId."_from"] = $startDateTime->toString();
				$arRes[$fieldId."_to"] = $endDateTime->add("1 days - 1 second")->toString();
				break;
			}

			case DateType::LAST_7_DAYS :
			{
				$date = new Date();
				$dateTime = DateTime::createFromTimestamp($date->getTimestamp());
				$quarter = Quarter::getCurrent();
				$year = $date->format("Y");
				$month = $date->format("n");

				$arRes[$fieldId."_month"] = $month;
				$arRes[$fieldId."_quarter"] = $quarter;
				$arRes[$fieldId."_year"] = $year;
				$arRes[$fieldId."_from"] = $dateTime->add("- 7 days")->toString();
				$arRes[$fieldId."_to"] = $dateTime->add("8 days - 1 second")->toString();
				break;
			}

			case DateType::LAST_30_DAYS :
			{
				$date = new Date();
				$dateTime = DateTime::createFromTimestamp($date->getTimestamp());
				$quarter = Quarter::getCurrent();
				$year = $date->format("Y");
				$month = $date->format("n");

				$arRes[$fieldId."_month"] = $month;
				$arRes[$fieldId."_quarter"] = $quarter;
				$arRes[$fieldId."_year"] = $year;
				$arRes[$fieldId."_from"] = $dateTime->add("- 29 days")->toString();
				$arRes[$fieldId."_to"] = $dateTime->add("31 days - 1 second")->toString();
				break;
			}

			case DateType::LAST_60_DAYS :
			{
				$date = new Date();
				$dateTime = DateTime::createFromTimestamp($date->getTimestamp());
				$quarter = Quarter::getCurrent();
				$year = $dateTime->format("Y");
				$month = $dateTime->format("n");

				$arRes[$fieldId."_month"] = $month;
				$arRes[$fieldId."_quarter"] = $quarter;
				$arRes[$fieldId."_year"] = $year;
				$arRes[$fieldId."_from"] = $dateTime->add("- 60 days")->toString();
				$arRes[$fieldId."_to"] = $dateTime->add("61 days - 1 second")->toString();
				break;
			}

			case DateType::LAST_90_DAYS :
			{
				$date = new Date();
				$dateTime = DateTime::createFromTimestamp($date->getTimestamp());
				$quarter = Quarter::getCurrent();
				$year = $date->format("Y");
				$month = $date->format("n");

				$arRes[$fieldId."_month"] = $month;
				$arRes[$fieldId."_quarter"] = $quarter;
				$arRes[$fieldId."_year"] = $year;
				$arRes[$fieldId."_from"] = $dateTime->add("- 90 days")->toString();
				$arRes[$fieldId."_to"] = $dateTime->add("91 days - 1 second")->toString();
				break;
			}

			case DateType::MONTH :
			{
				$month = $aInput[$fieldId."_month"];
				$year = $aInput[$fieldId."_year"];

				if (!empty($month) && !empty($year))
				{
					$date = DateTime::createFromTimestamp(mktime(0, 0, 0, $month, 1, $year));

					$arRes[$fieldId."_month"] = $month;
					$arRes[$fieldId."_quarter"] = Quarter::get($date);
					$arRes[$fieldId."_year"] = $year;
					$arRes[$fieldId."_from"] = $date->toString();
					$arRes[$fieldId."_to"] = $date->add("1 month - 1 second")->toString();
				}

				break;
			}

			case DateType::QUARTER :
			{
				$quarter = $aInput[$fieldId."_quarter"];
				$year = $aInput[$fieldId."_year"];
				$startDateTime = DateTime::createFromTimestamp(strtotime(Quarter::getStartDate($quarter, $year)));
				$endDateTime = DateTime::createFromTimestamp(strtotime(Quarter::getEndDate($quarter, $year)));

				if (!empty($quarter) && !empty($year))
				{
					$arRes[$fieldId."_quarter"] = $quarter;
					$arRes[$fieldId."_year"] = $year;
					$arRes[$fieldId."_from"] = $startDateTime->toString();
					$arRes[$fieldId."_to"] = $endDateTime->add("1 days - 1 second")->toString();
				}

				break;
			}

			case DateType::YEAR :
			{
				$year = $aInput[$fieldId."_year"];

				if (!empty($year))
				{
					$date = DateTime::createFromTimestamp(mktime(0, 0, 0, 1, 1, $year));

					$arRes[$fieldId."_year"] = $year;
					$arRes[$fieldId."_from"] = $date->toString();
					$arRes[$fieldId."_to"] = $date->add("1 year - 1 second")->toString();
				}

				break;
			}

			case DateType::EXACT :
			{
				$sourceDate = $aInput[$fieldId."_from"];

				if (!empty($sourceDate))
				{
					$date = Date::createFromTimestamp(strtotime($sourceDate));
					$dateTime = DateTime::createFromTimestamp(strtotime($sourceDate));

					if ($dateTime->getTimestamp() > $date->getTimestamp())
					{
						$arRes[$fieldId."_from"] = $dateTime->toString();
						$arRes[$fieldId."_to"] = $dateTime->toString();
					}
					else
					{
						$arRes[$fieldId."_from"] = $dateTime->toString();
						$arRes[$fieldId."_to"] = $dateTime->add("1 days - 1 second")->toString();
					}
				}

				break;
			}

			case DateType::LAST_WEEK :
			{
				$date = Date::createFromTimestamp(strtotime("monday previous week"));
				$dateTime = DateTime::createFromTimestamp($date->getTimestamp());
				$arRes[$fieldId."_from"] = $dateTime->toString();
				$arRes[$fieldId."_to"] = $dateTime->add("7 days - 1 second")->toString();
				break;
			}

			case DateType::LAST_MONTH :
			{
				$date = Date::createFromTimestamp(strtotime("first day of previous month"));
				$dateTime = DateTime::createFromTimestamp($date->getTimestamp());

				$arRes[$fieldId."_year"] = $dateTime->format("Y");
				$arRes[$fieldId."_month"] = $dateTime->format("n");
				$arRes[$fieldId."_from"] = $dateTime->toString();
				$arRes[$fieldId."_to"] = $dateTime->add("1 month - 1 second")->toString();
				break;
			}

			case DateType::RANGE :
			{
				$startSourceDate = $aInput[$fieldId."_from"];
				$endSourceDate = $aInput[$fieldId."_to"];

				if (!empty($startSourceDate))
				{
					$startDateTime = DateTime::createFromTimestamp(strtotime($startSourceDate));
					$arRes[$fieldId."_from"] = $startDateTime->toString();
				}

				if (!empty($endSourceDate))
				{
					$endDate = Date::createFromTimestamp(strtotime($endSourceDate));
					$endDateTime = DateTime::createFromTimestamp(strtotime($endSourceDate));

					if ($endDateTime->getTimestamp() > $endDate->getTimestamp())
					{
						$arRes[$fieldId."_to"] = $endDateTime->toString();
					}
					else
					{
						$arRes[$fieldId."_to"] = $endDateTime->add("1 days - 1 second")->toString();
					}
				}

				break;
			}
		}
	}

	public function reset()
	{
		$filterId = $this->filterId;
		$filterPresets = $this->allOptions["default_presets"];
		$this->allOptions = array();
		$this->save();
		$this->__construct($filterId, $filterPresets);
		$_SESSION["main.ui.filter"][$this->filterId] = null;
	}
}