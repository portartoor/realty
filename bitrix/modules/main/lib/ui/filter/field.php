<?

namespace Bitrix\Main\UI\Filter;

use Bitrix\Main\Text\HtmlFilter;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;


Loc::loadMessages(__FILE__);


class Field
{
	public static function string($name, $defaultValue = "", $label = "", $placeholder = "")
	{
		$field = array(
			"ID" => uniqid("field_"),
			"TYPE" => Type::STRING,
			"NAME" => $name,
			"VALUE" => $defaultValue,
			"LABEL" => $label,
			"PLACEHOLDER" => $placeholder
		);

		return $field;
	}

	public static function custom($name, $value, $label = "", $placeholder = "", $style = false)
	{
		$field = array(
			"ID" => uniqid("field_"),
			"TYPE" => Type::CUSTOM,
			"NAME" => $name,
			"VALUE" => HtmlFilter::encode($value),
			"PLACEHOLDER" => $placeholder,
			"LABEL" => $label,
			"ENABLE_STYLE" => $style
		);

		return $field;
	}

	public static function customEntity($name, $label = "", $placeholder = "")
	{
		$field = array(
			"ID" => uniqid("field_"),
			"TYPE" => Type::CUSTOM_ENTITY,
			"NAME" => $name,
			"LABEL" => $label,
			"VALUES" => array(
				"_label" => "",
				"_value" => ""
			),
			"PLACEHOLDER" => $placeholder
		);

		return $field;
	}

	public static function date($name, $type = DateType::NONE, $values = array(), $label = "", $placeholder = "", $enableTime = false, $exclude = array())
	{
		if (!is_bool($enableTime))
		{
			$enableTime = false;
		}

		if (!is_array($exclude))
		{
			$exclude = array();
		}

		$selectParams = array("isMulti" => false);

		if (empty($values))
		{
			$values = array(
				"_from" => "",
				"_to" => "",
				"_days" => "",
				"_month" => "",
				"_quarter" => "",
				"_year" => ""
			);
		}

		$sourceMonths = range(1, 12);
		$date = new Date();
		$currentMonthNumber = $date->format("n");
		$months = array();
		$currentMonthType = array();

		foreach($sourceMonths as $key => $month)
		{
			$months[] = array(
				"VALUE" => $month,
				"NAME" => Loc::getMessage("MAIN_UI_FILTER_FIELD_MONTH_".$month)
			);

			if ($currentMonthNumber == $month)
			{
				$currentMonthType = array(
					"VALUE" => $month,
					"NAME" => Loc::getMessage("MAIN_UI_FILTER_FIELD_MONTH_".$month)
				);
			}
		}


		$sourceQuarters = range(1, 4);
		$quarters = array();
		$quarterNumber = Quarter::getCurrent();
		$currentQuarterType = array();

		foreach($sourceQuarters as $key => $quarter)
		{
			$quarters[] = array(
				"VALUE" => $quarter,
				"NAME" => Loc::getMessage("MAIN_UI_FILTER_FIELD_QUARTER_".$quarter)
			);

			if ($quarterNumber == $quarter)
			{
				$currentQuarterType = array(
					"VALUE" => $quarter,
					"NAME" => Loc::getMessage("MAIN_UI_FILTER_FIELD_QUARTER_".$quarter)
				);
			}
		}

		$sourceSubtypes = DateType::getList();
		$subtypes = array();
		$subtypeType = array();

		foreach ($sourceSubtypes as $key => $subtype)
		{
			if (!in_array($subtype, $exclude))
			{
				$subtypes[] = array(
					"NAME" => Loc::getMessage("MAIN_UI_FILTER_FIELD_SUBTYPE_".$subtype),
					"VALUE" => $subtype
				);

				if ($subtype == $type)
				{
					$subtypeType = array(
						"NAME" => Loc::getMessage("MAIN_UI_FILTER_FIELD_SUBTYPE_".$subtype),
						"VALUE" => $subtype
					);
				}
			}
		}

		$currentYear = (int) $date->format("Y");
		$sourceYears = range($currentYear+5, $currentYear-20);
		$years = array();
		$currentYearType = array();

		foreach ($sourceYears as $key => $year)
		{
			$years[] = array(
				"NAME" => $year,
				"VALUE" => $year
			);

			if ($year == $currentYear)
			{
				$currentYearType = array(
					"NAME" => $year,
					"VALUE" => $year
				);
			}
		}

		$field = array(
			"ID" => uniqid("field_"),
			"TYPE" => Type::DATE,
			"NAME" => $name,
			"SUB_TYPE" => $subtypeType,
			"SUB_TYPES" => $subtypes,
			"MONTH" => $currentMonthType,
			"MONTHS" => $months,
			"QUARTER" => $currentQuarterType,
			"QUARTERS" => $quarters,
			"YEAR" => $currentYearType,
			"YEARS" => $years,
			"VALUES" => $values,
			"PLACEHOLDER" => $placeholder,
			"LABEL" => $label,
			"ENABLE_TIME" => $enableTime,
			"SELECT_PARAMS" => $selectParams
		);

		return $field;
	}

	public static function number($name, $type = NumberType::SINGLE, $values = array(), $label = "", $placeholder = "")
	{
		$selectParams = array("isMulti" => false);

		if (empty($values))
		{
			$values = array(
				"_from" => "",
				"_to" => ""
			);
		}

		$field = array(
			"ID" => uniqid("field_"),
			"TYPE" => Type::NUMBER,
			"NAME" => $name,
			"SUB_TYPE" => $type,
			"VALUES" => $values,
			"LABEL" => $label,
			"PLACEHOLDER" => $placeholder,
			"SELECT_PARAMS" => $selectParams
		);

		return $field;
	}

	public static function select($name, $items, Array $defaultValue = array(), $label = "", $placeholder = "")
	{
		if (empty($defaultValue) && count($items))
		{
			$defaultValue["NAME"] = $items[0]["NAME"];
			$defaultValue["VALUE"] = $items[0]["VALUE"];
		}

		$field = array(
			"ID" => uniqid("field_"),
			"TYPE" => Type::SELECT,
			"NAME" => $name,
			"VALUE" => $defaultValue,
			"PLACEHOLDER" => $placeholder,
			"LABEL" => $label,
			"ITEMS" => $items,
			"PARAMS" => array("isMulti" => false)
		);

		return $field;
	}

	public static function multiSelect($name, $items, $defaultValues = array(), $label = "", $placeholder = "")
	{
		$field = array(
			"ID" => uniqid("field_"),
			"TYPE" => Type::MULTI_SELECT,
			"NAME" => $name,
			"VALUE" => $defaultValues,
			"PLACEHOLDER" => $placeholder,
			"LABEL" => $label,
			"ITEMS" => $items,
			"PARAMS" => array("isMulti" => true)
		);

		return $field;
	}
}