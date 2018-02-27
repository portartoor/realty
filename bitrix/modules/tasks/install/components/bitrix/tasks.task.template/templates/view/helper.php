<?
use Bitrix\Main\Localization\Loc;

use Bitrix\Tasks\Util;
use Bitrix\Tasks\Integration\CRM;
use Bitrix\Tasks\UI;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

// create template controller with js-dependency injections
return new \Bitrix\Tasks\UI\Component\TemplateHelper(null, $this, array(
	'RELATION' => array('tasks_util', /*etc*/),
	'METHODS' => array(
		'formatDateAfter' => function($value)
		{
			$value = intval($value); // in seconds

			if(!($value % 86400))
			{
				$unit = 'DAY';
				$value = floor($value / 86400);
			}
			elseif(!($value % 3600))
			{
				$unit = 'HOUR';
				$value = floor($value / 3600);
			}
			else
			{
				$unit = 'MINUTE';
				$value = floor($value / 60);
			}

			return $value.' '.Loc::getMessage('TASKS_COMMON_'.$unit.'_F'.UI::getPluralForm($value));
		}
	),
));