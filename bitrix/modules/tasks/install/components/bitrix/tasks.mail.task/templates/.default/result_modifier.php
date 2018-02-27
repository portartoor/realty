<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

//Description
// todo: remove this when you got array access in $arResult['DATA']['TASK']
if((string) $arResult['DATA']['TASK']['DESCRIPTION'] != '')
{
	if($arResult['DATA']['TASK']['DESCRIPTION_IN_BBCODE'] == 'Y')
	{
		// convert to bbcode to html to show inside a document body
		$arResult['DATA']['TASK']['DESCRIPTION'] = \Bitrix\Tasks\Util\UI::convertBBCodeToHtml($arResult['DATA']['TASK']['DESCRIPTION'], array(
			'PATH_TO_USER_PROFILE' => $arParams['PATH_TO_USER_PROFILE'],
			'USER_FIELDS' => $arResult['AUX_DATA']['USER_FIELDS']
		));
	}
	else
	{
		// make our description safe to display
		$arResult['DATA']['TASK']['DESCRIPTION'] = \Bitrix\Tasks\Util\UI::convertHtmlToSafeHtml($arResult['DATA']['TASK']['DESCRIPTION']);
	}
}

// checklist pre-format
// todo: remove this when use object with array access instead of ['ITEMS']['DATA']
$code = \Bitrix\Tasks\Manager\Task\CheckList::getCode(true);
if(is_array($arResult['DATA']['TASK'][$code]))
{
	foreach($arResult['DATA']['TASK'][$code] as &$item)
	{
		$item['TITLE_HTML'] = \Bitrix\Tasks\Util\UI::convertBBCodeToHtmlSimple($item['TITLE']);
	}

	$limit = 3;
	$arResult['CHECKLIST_LIMIT'] = count($arResult['DATA']['TASK'][$code]) - $limit > 2 ? $limit : count($arResult['DATA']['TASK'][$code]);
	$arResult['CHECKLIST_MORE'] = count($arResult['DATA']['TASK'][$code]) - $arResult['CHECKLIST_LIMIT'];
}

$arResult['SERVER_NAME'] = Bitrix\Tasks\Util\Site::getServerName(); // need for absolute urls, kz it is a email
$arParams["PATH_TO_TASKS_TASK"] = 'http://'.$arResult['SERVER_NAME'].'/pub/task.php?task_id=#task_id#';

$arResult['S_NEEDED'] = $arResult['DATA']['TASK']["REAL_STATUS"] != 4 && $arResult['DATA']['TASK']["REAL_STATUS"] != 5;
$arResult['TEMPLATE_FOLDER'] = $this->__component->__template->__folder;

$originator =& $arResult['DATA']['TASK'][\Bitrix\Tasks\Manager\Task\Originator::getCode(true)];
$originator['AVATAR'] = \Bitrix\Tasks\Util\UI::getUserAvatar($originator['PERSONAL_PHOTO']);
if(!$originator['AVATAR'])
{
	$originator['AVATAR'] = $arResult['TEMPLATE_FOLDER'].'/img/noavatar.gif';
}
$originator['AVATAR'] = 'http://'.$arResult['SERVER_NAME'].$originator['AVATAR'];

$originator['NAME_FORMATTED'] = CUser::FormatName(\Bitrix\Tasks\Util\Site::getNameFormat(), $originator, true, false);
$originator['PERSONAL_GENDER'] = $originator['PERSONAL_GENDER'] == 'F' ? 'F' : 'M';