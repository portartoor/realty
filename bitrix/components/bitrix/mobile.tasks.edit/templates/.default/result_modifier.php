<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$arResult['DATETIME_FORMAT'] = array(
	'DISPLAY' => array( // format for displaying dates in the user interface
		'full' => Loc::getMessage('MB_TASKS_DATETIME_FORMAT_DISPLAY_YAGO'),
		'noYear' => Loc::getMessage('MB_TASKS_DATETIME_FORMAT_DISPLAY')
	),
	'INTERNAL' => array(
		// do not use '-' as a separator in this format: it will be foolishly parsed by ios6
		'PHP' => 'm/d/Y H:i', // format is used in FormatDate() inside js logic
		'JS' => 'M/d/y H:m' // is used in app.showDatePicker()
	),
	'SUBMIT' => array(
		'PHP' => 'Y-m-d H:i:s' // format for passing data in ajax query
	)
);

$arResult['TASK']['META:FORMATTED_DATA']['DATETIME_SEXY'] = '';

// we dont use unix timestamps here because of different timezones between server and client

if((string) $arResult['TASK']['DEADLINE'] != '')
{
	$date = getdate(MakeTimeStamp($arResult['TASK']['DEADLINE']));
	$date = array(
		'minute' => $date['minutes'],
		'hour' => $date['hours'],
		'second' => $date['seconds'],
		'year' => $date['year'],
		'day' => $date['mday'],
		'month' => $date['mon'],
	);

	$arResult['TASK']['DEADLINE_PARSED'] = array(
		'CURRENT' => $date,
		'TO_CREATE' => $date
	);
}
else
{
	$date = getdate(time());
	$date = array(
		'minute' => $arResult['COMPANY_WORKTIME']['END']['M'],
		'hour' => $arResult['COMPANY_WORKTIME']['END']['H'],
		'second' => 0,
		'year' => $date['year'],
		'day' => $date['mday'],
		'month' => $date['mon'],
	);

	$arResult['TASK']['DEADLINE_PARSED'] = array(
		'CURRENT' => array(),
		'TO_CREATE' => $date
	);
}
