<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arResult = array();

$bSkipFiles = false;
$bSkipUpdatesCount = false;
$bSkipGivenUserDataFetch = false;
$bSelectOnlyIdsFromTasks = false;		// flag that in $arSelect only ID of task
$bSkipGroupsDataFetch = false;

$arSelect = array();	// Fields to be selected from tasks

if ($arParams['OPTIMIZATION_MODE'] === 'SELECT_ONLY_FILES_COUNT')
{
	$arSelect = array('ID');	// Select only IDs from tasks
	$bSkipUpdatesCount = true;
	$bSkipGivenUserDataFetch = true;
	$bSelectOnlyIdsFromTasks = true;
	$bSkipGroupsDataFetch = true;
}
elseif ($arParams['OPTIMIZATION_MODE'] === 'SELECT_FULL_DATA_EXCEPT_FILES')
{
	$arSelect = array();
	$bSkipFiles = true;
}
else	// No optimization
{
	$arSelect = array();
}

$environmentCheck = isset($GLOBALS['APPLICATION']) 
	&& is_object($GLOBALS['APPLICATION'])
	&& isset($GLOBALS['USER']) 
	&& is_object($GLOBALS['USER'])
	&& isset($arParams)
	&& is_array($arParams)
	&& CModule::IncludeModule('tasks');

if ( ! $bSkipGroupsDataFetch )
	$environmentCheck = $environmentCheck && CModule::IncludeModule('socialnetwork');

if ( ! $environmentCheck )
	return (false);

unset ($environmentCheck);

if ( ! isset($arParams['AVATAR_SIZE']) )
	$arParams['AVATAR_SIZE'] = array('width' => 58, 'height' => 58);

if ( ! isset($arParams['OPTIMIZATION_MODE']) )
	$arParams['OPTIMIZATION_MODE'] = 'NONE';

if ( ! isset($arParams['DATE_TIME_FORMAT']) || empty($arParams['DATE_TIME_FORMAT']))
	$arParams['DATE_TIME_FORMAT'] = $DB->DateFormatToPHP(CSite::GetDateFormat('FULL'));

$arParams['DATE_TIME_FORMAT'] = trim($arParams['DATE_TIME_FORMAT']);

if ( ! isset($arParams['SHOW_TEMPLATE']) )
	$arParams['SHOW_TEMPLATE'] = 'Y';	// show template by default

if ( ! isset($arParams['RENDER_FORMAT']) )
	$arParams['RENDER_FORMAT'] = 'HTML';	// render as HTML by default

if ( ! isset($arParams['TASKS_PER_PAGE']) )
	$arParams['TASKS_PER_PAGE'] = 10;		// 10 tasks per page by default
else
	$arParams['TASKS_PER_PAGE'] = (int) $arParams['TASKS_PER_PAGE'];

if (isset($arParams['NAME_TEMPLATE']) && (strlen($arParams['NAME_TEMPLATE']) > 0))
{
	$arParams['NAME_TEMPLATE'] = str_replace(
		array('#NOBR#','#/NOBR#'), 
		'', 
		$arParams['NAME_TEMPLATE']
	);
}
else
	$arParams['NAME_TEMPLATE'] = CSite::GetNameFormat(false);

if (isset($arParams['USER_ID']))
{
	if (intval($arParams['USER_ID']) > 0)
		$arParams['USER_ID'] = (int) $arParams['USER_ID'];
}
else
	$arParams['USER_ID'] = (int) $GLOBALS['USER']->GetID();

if (isset($arParams['GROUP_ID'])
	&& (intval($arParams['GROUP_ID']) >= 0)
)
{
	$arParams['GROUP_ID'] = (int) $arParams['GROUP_ID'];
}
else
	$arParams['GROUP_ID'] = false;

// user paths
if ( ! isset($arParams['PATH_TO_USER_TASKS']) )
	$arParams['PATH_TO_USER_TASKS'] = '';

$arParams['PATH_TO_USER_TASKS'] = trim($arParams['PATH_TO_USER_TASKS']);	// Path to tasks list
if (strlen($arParams['PATH_TO_USER_TASKS']) <= 0)
	$arParams['PATH_TO_USER_TASKS'] = COption::GetOptionString('tasks', 'paths_task_user', null, SITE_ID);

if ( ! isset($arParams['PATH_TO_USER_TASKS_TASK']) )
	$arParams['PATH_TO_USER_TASKS_TASK'] = '';

$arParams['PATH_TO_USER_TASKS_TASK'] = trim($arParams['PATH_TO_USER_TASKS_TASK']);		// Path to tasks view
if (strlen($arParams['PATH_TO_USER_TASKS_TASK']) <= 0)
	$arParams['PATH_TO_USER_TASKS_TASK'] = COption::GetOptionString('tasks', 'paths_task_user_action', null, SITE_ID);

if ( ! isset($arParams['PATH_TEMPLATE_TO_USER_PROFILE']) )
	$arParams['PATH_TEMPLATE_TO_USER_PROFILE'] = '';

$arParams['PATH_TEMPLATE_TO_USER_PROFILE'] = trim($arParams['PATH_TEMPLATE_TO_USER_PROFILE']);

if (is_string($arParams['PATH_TO_USER_TASKS']) && is_int($arParams['USER_ID']))
{
	$arParams['PATH_TO_TASKS'] = str_replace(
		array('#user_id#', '#USER_ID#'), 
		$arParams['USER_ID'], 
		$arParams['PATH_TO_USER_TASKS']
	);
}

if (is_string($arParams['PATH_TO_USER_TASKS_TASK']) && is_int($arParams['USER_ID']))
{
	$arParams['PATH_TO_TASKS_TASK'] = str_replace(
		array('#user_id#', '#USER_ID#'), 
		$arParams['USER_ID'], 
		$arParams['PATH_TO_USER_TASKS_TASK']
	);
}

if (is_string($arParams['PATH_TO_USER_TASKS_EDIT']) && is_int($arParams['USER_ID']))
{
	$arParams['PATH_TO_TASKS_EDIT'] = str_replace(
		array('#user_id#', '#USER_ID#'), 
		$arParams['USER_ID'], 
		$arParams['PATH_TO_USER_TASKS_EDIT']
	);
}

if (is_string($arParams['PATH_TO_USER_TASKS_FILTER']) && is_int($arParams['USER_ID']))
{
	$arParams['PATH_TO_TASKS_FILTER'] = str_replace(
		array('#user_id#', '#USER_ID#'), 
		$arParams['USER_ID'], 
		$arParams['PATH_TO_USER_TASKS_FILTER']
	);
}

$paramsCheck = (($arParams['SHOW_TEMPLATE'] === 'Y') || ($arParams['SHOW_TEMPLATE'] === 'N'))
	&& ($arParams['RENDER_FORMAT'] === 'HTML')
	&& is_int($arParams['TASKS_PER_PAGE'])
	&& ($arParams['TASKS_PER_PAGE'] > 0)
	&& is_int($arParams['USER_ID'])
	&& (is_int($arParams['GROUP_ID']) || ($arParams['GROUP_ID'] === false))
	&& is_string($arParams['NAME_TEMPLATE'])
	&& (strlen($arParams['NAME_TEMPLATE']) > 0)
	&& is_string($arParams['DATE_TIME_FORMAT'])
	&& strlen($arParams['DATE_TIME_FORMAT'])
	&& is_string($arParams['PATH_TEMPLATE_TO_USER_PROFILE'])
	&& is_string($arParams['PATH_TO_USER_TASKS'])
	&& is_string($arParams['PATH_TO_USER_TASKS_TASK'])
	&& is_string($arParams['PATH_TO_TASKS'])
	&& is_string($arParams['PATH_TO_TASKS_EDIT'])
	&& is_string($arParams['PATH_TO_TASKS_TASK'])
	&& is_string($arParams['PATH_TO_TASKS_FILTER'])
	&& in_array(
		$arParams['OPTIMIZATION_MODE'], 
		array(
			'NONE',
			'SELECT_ONLY_FILES_COUNT',
			'SELECT_FULL_DATA_EXCEPT_FILES'
		),
		true
	);

if ( ! $paramsCheck )
	return (false);

unset ($paramsCheck);

if ( ! CBXFeatures::IsFeatureEnabled('Tasks') )
{
	if (($arParams['SHOW_TEMPLATE'] === 'Y') && ($arParams['RENDER_FORMAT'] === 'HTML'))
		ShowError(GetMessage('TASKS_MODULE_NOT_AVAILABLE_IN_THIS_EDITION'));

	return (false);
}

require_once(dirname(__FILE__) . '/functions.php');

// EVERYTHING ABOUT FILTERS AND LIST STATES
// almost exact copy-paste from tasks/install/components/bitrix/tasks.list/component.php

$oListState = CTaskListState::getInstance($USER->getId());
$oFilter = CTaskFilterCtrl::GetInstance($arParams['USER_ID'], false);
$oListCtrl = CTaskListCtrl::getInstance($arParams['USER_ID']);

//if($_GET['F_CANCEL'])
//	$oListState

$filterRequestParameters = array();

$presetFromRequest = false;
if (intval($_GET['F_FILTER_SWITCH_PRESET']))
	$presetFromRequest = intval($_GET['F_FILTER_SWITCH_PRESET']);
elseif (intval($_GET['SWITCH_TO_FILTER_PRESET_ID']))
	$presetFromRequest = intval($_GET['SWITCH_TO_FILTER_PRESET_ID']);

// change state
if($presetFromRequest)
{
	$oListState->setSection(CTaskListState::VIEW_SECTION_ADVANCED_FILTER);
	$oListState->saveState();

	$curFilterId = $oFilter->GetSelectedFilterPresetId();

	if ($presetFromRequest !== $curFilterId)
	{
		try
		{
			$oFilter->SwitchFilterPreset($presetFromRequest);
		}
		catch (Exception $e)
		{
			$oFilter->SwitchFilterPreset(CTaskFilterCtrl::STD_PRESET_ALIAS_TO_DEFAULT);
		}
	}

	$oListCtrl->useAdvancedFilterObject($oFilter);

	$filterRequestParameters['F_FILTER_SWITCH_PRESET'] = $presetFromRequest;
}
else
{
	$oListState->setSection(CTaskListState::VIEW_SECTION_ROLES);
	$oListState->saveState();

	if(is_array($_GET['F_STATE']))
		$arSwitchStateTo = $_GET['F_STATE'];
	else
	{
		$currentRole = $oListState->getUserRole();
		$arSwitchStateTo = array(intval($currentRole) ? 'sR'.base_convert($currentRole, 10, 32) : 'sR400');
	}

	// we want to see all subtasks in mobile
	$arSwitchStateTo[] = 'dS'.CTaskListState::VIEW_SUBMODE_WITH_SUBTASKS;

	rsort($arSwitchStateTo); // to make "sCxxxx" go AFTER "sRxxxx", or we`ll get an error

	//_dump_r('Switch state to:');
	//_dump_r($arSwitchStateTo);

	foreach ($arSwitchStateTo as $switchStateTo)
	{
		if ($switchStateTo)
		{
			try
			{
				$symbol = substr($switchStateTo, 0, 2);
				$value = (int) base_convert(substr($switchStateTo, 2), 32, 10);

				switch ($symbol)
				{
					case 'sR':	// set role
						$oListState->setSection(CTaskListState::VIEW_SECTION_ROLES);
						$oListState->setUserRole($value);
						$oListState->setTaskCategory(CTaskListState::VIEW_TASK_CATEGORY_IN_PROGRESS);
					break;

					case 'sV':	// set view
						$oListState->setViewMode($value);
					break;

					case 'sC':	// set category
						$oListState->setTaskCategory($value);
					break;

					case 'eS':	// enable submode
						$oListState->switchOnSubmode($value);
					break;

					case 'dS':	// disable submode
						$oListState->switchOffSubmode($value);
					break;
				}

				$oListState->saveState();
			}
			catch (TasksException $e)
			{
			}
		}
	}

	$filterRequestParameters['F_STATE'] = $arSwitchStateTo;
}
//setFilterByGroupId
// apply current state to it
$oListCtrl->useState($oListState);

$arResult['VIEW_STATE'] = $oListState->getState();

// getting common filter based on selected role and other things
$arCommonFilter = $oListCtrl->getCommonFilter();
// getting filter
$arFilter = $oListCtrl->getFilter();

// setting additionals to the filter
$arFilter = array_merge($arFilter, $arCommonFilter);

if (isset($_GET['F_CREATED_BY']) && $_GET['F_CREATED_BY'])
{
	$createdBy = (int) $_GET['F_CREATED_BY'];
	$arFilter = array_merge($arFilter, array('CREATED_BY' => $createdBy));
	$filterRequestParameters['F_CREATED_BY'] = $createdBy;
}

if (isset($_GET['F_RESPONSIBLE_ID']) && $_GET['F_RESPONSIBLE_ID'])
{
	$responsible = (int) $_GET['F_RESPONSIBLE_ID'];
	$arFilter = array_merge($arFilter, array('RESPONSIBLE_ID' => $responsible));
	$filterRequestParameters['F_RESPONSIBLE_ID'] = $responsible;
}

$inRoles = $arResult['VIEW_STATE']['SECTION_SELECTED']['ID'] == CTaskListState::VIEW_SECTION_ROLES;

// for backward compatibility
if ($inRoles)
{
	$arResult['FILTER_NAME'] = $arResult['VIEW_STATE']['ROLES'][$arResult['VIEW_STATE']['ROLE_SELECTED']['CODENAME']]['TITLE'];
	$arResult['FILTER_ID']   = $arResult['VIEW_STATE']['ROLE_SELECTED']['ID'];

	if((string) $arResult['VIEW_STATE']['TASK_CATEGORY_SELECTED']['CODENAME'] != '')
	{
		$category = $arResult['VIEW_STATE']['TASK_CATEGORY_SELECTED']['CODENAME'];

		$arResult['FILTER_NAME'] .= ': '.$arResult['VIEW_STATE']['TASK_CATEGORIES'][$category]['TITLE'];
		$arResult['FILTER_ID'] .= $arResult['VIEW_STATE']['TASK_CATEGORIES'][$category]['ID'];
	}
}
else
{
	$arResult['FILTER_NAME'] = $oFilter->GetSelectedFilterPresetName();
	$arResult['FILTER_ID']   = $oFilter->GetSelectedFilterPresetId();
}

// If requested tasks for some group only
if (is_int($arParams['GROUP_ID']) && ($arParams['GROUP_ID'] >= 0))
{
	if ($arParams['GROUP_ID'] === 0)
		$arFilter['META:GROUP_ID_IS_NULL_OR_ZERO'] = 'It doesn\'t matter';
	else
		$arFilter['GROUP_ID'] = $arParams['GROUP_ID'];

	$filterRequestParameters['GROUP_ID'] = intval($arParams['GROUP_ID']);
}

$arResult['FILTER'] = $arFilter;
$arResult['FILTER_QUERY'] = $filterRequestParameters;

// EVERYTHING ABOUT SORT ORDER

if (
	is_array($arResult['VIEW_STATE']) 
	&& $inRoles
	&& ($arResult['VIEW_STATE']['TASK_CATEGORY_SELECTED']['ID'] == CTaskListState::VIEW_TASK_CATEGORY_COMPLETED)
)
{
	$arOrder = array(
		'CLOSED_DATE' => 'DESC'
	);
}
else
{
	$arOrder = array(
		'DEADLINE' => 'ASC,NULLS',
		'STATUS'   => 'ASC',
		'PRIORITY' => 'DESC',
		'ID'       => 'DESC'
	);
}
$arResult['ORDER'] = $arOrder;

//$arOrder = array_merge(array("GROUP_ID" => "ASC"), $arParams['PREORDER'], $arOrder);
$arOrder = array_merge(array("GROUP_ID" => "ASC", "STATUS_COMPLETE" => "ASC"), $arOrder);

////////////////////////////////////////////////////////
////////////////////////////////////////////////////////
////////////////////////////////////////////////////////

$groupListModeSwitcher = null;	// nothing will be done, by default
if (
	isset($_REQUEST['GROUP_LIST_MODE']) 
	&& in_array($_REQUEST['GROUP_LIST_MODE'], array('Y', 'N'), true)
)
{
	// group list mode will be switched on or switched off
	$groupListModeSwitcher = $_REQUEST['GROUP_LIST_MODE'];
}

// Switch list if need
$taskListOwnerSwitcher = (int) $GLOBALS['USER']->GetID();
if ($taskListOwnerSwitcher !== (int) $arParams['USER_ID'])
	$taskListOwnerSwitcher = false;

CTasksMobileTasksListNsAbstract::Init(
	$taskListOwnerSwitcher,
	$groupListModeSwitcher
);

if ( ! $bSkipGivenUserDataFetch )
{
	$rsUser = CUser::GetByID($arParams['USER_ID']);

	if ( ! (is_object($rsUser) && ($arUser = $rsUser->GetNext())) )
		return (false);

	$arResult['USER'] = $arUser;
	unset ($rsUser, $arUser);
}

$arGroupsStat = array(
	0 => array(				// pseudo group
		'TASKS_IN_GROUP' => 0,
		'STATUSES'       => array(),
		'PRIORITIES'     => array()
	)
);				// groups with tasks
$arResult['TASKS'] = array();

//_dump_r('================= Mobile');
//_dump_r($arResult['ORDER']);
//_dump_r($arResult['FILTER']);

$rsTasks = CTasks::GetList($arResult['ORDER'], $arResult['FILTER'], $arSelect);
$arViewedDates = array();	// dates when task was last viewed

$arTasksIDs = array();
$arTaskOriginatorsAndResponsibles = array();
$parser = new CTextParser();
while ($task = $rsTasks->Fetch())
{
	$arTasksIDs[] = (int) $task['ID'];
	$arTaskOriginatorsAndResponsibles[] = (int) $task['CREATED_BY'];
	$arTaskOriginatorsAndResponsibles[] = (int) $task['RESPONSIBLE_ID'];

	if ( ! $bSelectOnlyIdsFromTasks )
	{
		// count tasks per group
		if ( isset($arGroupsStat[(int)$task['GROUP_ID']]) )
		{
			$arGroupsStat[(int)$task['GROUP_ID']]['TASKS_IN_GROUP']++;

			if ( ! in_array((int) $task['STATUS'], $arGroupsStat[(int)$task['GROUP_ID']]['STATUSES']) )
				$arGroupsStat[(int)$task['GROUP_ID']]['STATUSES'][] = (int) $task['STATUS'];

			if ( ! in_array((int) $task['PRIORITY'], $arGroupsStat[(int)$task['GROUP_ID']]['PRIORITIES']) )
				$arGroupsStat[(int)$task['GROUP_ID']]['PRIORITIES'][] = (int) $task['PRIORITY'];
		}
		else
		{
			$arGroupsStat[(int)$task['GROUP_ID']]['TASKS_IN_GROUP'] = 1;
			$arGroupsStat[(int)$task['GROUP_ID']]['STATUSES'] = array((int) $task['STATUS']);
			$arGroupsStat[(int)$task['GROUP_ID']]['PRIORITIES'] = array((int) $task['PRIORITY']);
		}

		// last viewed date by given user
		$arViewedDates[$task['ID']] = $task['VIEWED_DATE'] ? $task['VIEWED_DATE'] : $task['CREATED_DATE'];

		$task['META::RESPONSIBLE_FORMATTED_NAME'] = CUser::FormatName(
			$arParams['NAME_TEMPLATE'], 
			array(
				'NAME'        => $task['RESPONSIBLE_NAME'], 
				'LAST_NAME'   => $task['RESPONSIBLE_LAST_NAME'], 
				'SECOND_NAME' => $task['RESPONSIBLE_SECOND_NAME'], 
				'LOGIN'       => $task['RESPONSIBLE_LOGIN']
			),
			true,
			false	// don't use htmlspecialcharsbx
		);

		$task['META::ORIGINATOR_FORMATTED_NAME'] = CUser::FormatName(
			$arParams['NAME_TEMPLATE'], 
			array(
				'NAME'        => $task['CREATED_BY_NAME'], 
				'LAST_NAME'   => $task['CREATED_BY_LAST_NAME'], 
				'SECOND_NAME' => $task['CREATED_BY_SECOND_NAME'], 
				'LOGIN'       => $task['CREATED_BY_LOGIN']
			),
			true,
			false	// don't use htmlspecialcharsbx
		);

		$task['META::STATUS_FORMATTED_NAME'] = '';

		switch ($task['REAL_STATUS'])
		{
			case CTasks::STATE_NEW:
				$task['META::STATUS_FORMATTED_NAME'] = GetMessage('MB_TASKS_TASKS_LIST_STATUS_NEW');
			break;

			case CTasks::STATE_PENDING:
				$task['META::STATUS_FORMATTED_NAME'] = GetMessage('MB_TASKS_TASKS_LIST_STATUS_ACCEPTED');
			break;

			case CTasks::STATE_IN_PROGRESS:
				$task['META::STATUS_FORMATTED_NAME'] = GetMessage('MB_TASKS_TASKS_LIST_STATUS_IN_PROGRESS');
			break;

			case CTasks::STATE_SUPPOSEDLY_COMPLETED:
				$task['META::STATUS_FORMATTED_NAME'] = GetMessage('MB_TASKS_TASKS_LIST_STATUS_WAITING');
			break;

			case CTasks::STATE_COMPLETED:
				$task['META::STATUS_FORMATTED_NAME'] = GetMessage('MB_TASKS_TASKS_LIST_STATUS_COMPLETED');
			break;

			case CTasks::STATE_DEFERRED:
				$task['META::STATUS_FORMATTED_NAME'] = GetMessage('MB_TASKS_TASKS_LIST_STATUS_DELAYED');
			break;

			case CTasks::STATE_DECLINED:
				$task['META::STATUS_FORMATTED_NAME'] = GetMessage('MB_TASKS_TASKS_LIST_STATUS_DECLINED');
			break;

			default:
				$task['META::STATUS_FORMATTED_NAME'] = $task['REAL_STATUS'];
			break;
		}

		$task['META::STATUS_FORMATTED_NAME'] .= ' ' 
			. GetMessage('MB_TASKS_TASKS_LIST_STATUS_DATE_PREPOSITION')
			. ' '
			. CTasksTools::FormatDatetimeBeauty(
				$task['STATUS_CHANGED_DATE'], 
				array(), 		// params
				$arParams['DATE_TIME_FORMAT']
			);

		$task['META:DEADLINE_FORMATTED'] = '';
		if (MakeTimeStamp($task['DEADLINE']) > 86400)
		{
			$task['META:DEADLINE_FORMATTED'] = CTasksTools::FormatDatetimeBeauty(
				$task['DEADLINE'], 
				array(), 		// params
				$arParams['DATE_TIME_FORMAT']
			);
		}

		// HTML-format must be supported in future, because old tasks' data not converted from HTML to BB
		if ($task['DESCRIPTION_IN_BBCODE'] === 'N')
		{
			// HTML detected, sanitize if need
			$task['DESCRIPTION'] = CTasksTools::SanitizeHtmlDescriptionIfNeed($task['DESCRIPTION']);
		}
		else
			$task['DESCRIPTION'] = $parser->convertText($task['DESCRIPTION']);

		// files list will be fetched below
		$task['FILES'] = array();
	}

	$arResult['TASKS'][$task['ID']] = $task;
}

$arResult['TASKS_IDS_AS_INTEGERS'] = $arTasksIDs;
$arTaskOriginatorsAndResponsibles = array_unique($arTaskOriginatorsAndResponsibles);

// Get photos for originators and responsibles
$rsUser = CUser::GetList(
	$passByReference1 = 'id', 	// order by
	$passByReference2 = 'asc', 	// order direction
	$passByReference3 = array(		// filter
		'ID' => implode('|', $arTaskOriginatorsAndResponsibles)
	)
);

$arUsersPhotos = array();
while ($arUser = $rsUser->Fetch())
{
	$arUsersPhotos[$arUser['ID']] = false;

	if (intval($arUser['PERSONAL_PHOTO']) > 0)
	{
		$imageFile = CFile::GetFileArray($arUser['PERSONAL_PHOTO']);
		if ($imageFile !== false)
		{
			$arFileTmp = CFile::ResizeImageGet(
				$imageFile, 
				array(
					"width"  => $arParams['AVATAR_SIZE']['width'], 
					"height" => $arParams['AVATAR_SIZE']['height']
				), 
				BX_RESIZE_IMAGE_EXACT, 
				false
			);

			if ($arFileTmp['src'] && strlen($arFileTmp['src']))
				$arUsersPhotos[$arUser['ID']] = $arFileTmp['src'];
		}
	}
}
unset($rsUser, $arUser);

// Store photos links in task data
foreach ($arResult['TASKS'] as $key => $value)
{
	$arResult['TASKS'][$key]['META::ORIGINATOR_PHOTO_SRC'] = false;
	$arResult['TASKS'][$key]['META::RESPONSIBLE_PHOTO_SRC'] = false;

	if (isset($arUsersPhotos[$value['CREATED_BY']]))
		$arResult['TASKS'][$key]['META::ORIGINATOR_PHOTO_SRC'] = $arUsersPhotos[$value['CREATED_BY']];

	if (isset($arUsersPhotos[$value['RESPONSIBLE_ID']]))
		$arResult['TASKS'][$key]['META::RESPONSIBLE_PHOTO_SRC'] = $arUsersPhotos[$value['RESPONSIBLE_ID']];
}

if ( ! $bSkipFiles )
{
	if (count($arTasksIDs))
	{
		$rsTaskFiles = CTaskFiles::GetList(array(), array('TASK_ID' => $arTasksIDs));
		while ($arTaskFile = $rsTaskFiles->Fetch())
		{
			$rsFile = CFile::GetByID($arTaskFile['FILE_ID']);
			if ($arFile = $rsFile->Fetch())
				$arResult['TASKS'][$arTaskFile['TASK_ID']]['FILES'][] = $arFile;
		}
	}
}

if ( ! $bSkipUpdatesCount )
	$arResult['UPDATES_COUNT'] = CTasks::GetUpdatesCount($arViewedDates);

if ( ! $bSkipGroupsDataFetch )
{
	// collect groups with tasks
	$arResult['GROUPS'] = array(
		// Init groups array with pesudo group, that will be represent tasks without groups
		0 => array(
			'ID' => 0,
			'NAME' => GetMessage('MB_TASKS_TASKS_LIST_PSEUDO_GROUP_NAME'),
			'META:TASKS_IN_GROUP'   => $arGroupsStat[0]['TASKS_IN_GROUP'],
			'META:TASKS_STATUSES'   => $arGroupsStat[0]['STATUSES'],
			'META:TASKS_PRIORITIES' => $arGroupsStat[0]['PRIORITIES']
		)
	);

	// List of groups to be fetched from DB
	$arGroupsIdsToBeFetched = array();

	// Fetch data about all needed groups only if we show list of groups
	if (count($arGroupsStat))
		$arGroupsIdsToBeFetched = array_keys($arGroupsStat);

	if ( ! empty($arGroupsIdsToBeFetched) )
	{
		$rsGroups = CSocNetGroup::GetList(
			array(), 
			array('ID' => $arGroupsIdsToBeFetched)
		);

		while($arGroup = $rsGroups->Fetch())
		{
			$arGroup['META:TASKS_IN_GROUP'] = $arGroupsStat[(int)$arGroup['ID']]['TASKS_IN_GROUP'];
			$arGroup['META:TASKS_STATUSES'] = $arGroupsStat[(int)$arGroup['ID']]['STATUSES'];
			$arGroup['META:TASKS_PRIORITIES'] = $arGroupsStat[(int)$arGroup['ID']]['PRIORITIES'];
			$arResult['GROUPS'][$arGroup['ID']] = $arGroup;
		}
	}

	// Get name for selected group
	$arResult['SELECTED_GROUP_NAME'] = false;
	if ($arParams['GROUP_ID'] !== false)
	{
		if (isset($arResult['GROUPS'][$arParams['GROUP_ID']]['NAME']))
			$arResult['SELECTED_GROUP_NAME'] = $arResult['GROUPS'][$arParams['GROUP_ID']]['NAME'];
		else
			$arResult['SELECTED_GROUP_NAME'] = 'Oops... Programmer mistakes.';
	}
}

if ($arParams['SHOW_TEMPLATE'] === 'Y')
	$this->IncludeComponentTemplate();

return $arResult;
