<?php
define('NO_KEEP_STATISTIC', 'Y');
define('NO_AGENT_STATISTIC','Y');
define('NO_AGENT_CHECK', true);
define('PUBLIC_AJAX_MODE', true);
define('DisableEventsCheck', true);

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/prolog_before.php');

$context = \Bitrix\Main\Application::getInstance()->getContext();
$request = $context->getRequest();

$action = $request->get('action');
$id = $request->get('entity_id');
$minId = $request->get('min_entity_id');
$type = $request->get('entity_type');
$page = $request->get('page');
$column = $request->get('column');
$newState = $request->get('status');
$extra = $request->get('extra');
$result = null;

if (empty($type))
{
	$result = array('ERROR' => 'Unknown type');
}
//get one or more items
elseif ($action == 'get' && (!empty($id) || $minId))
{
	$result = $APPLICATION->IncludeComponent('bitrix:crm.kanban', '', array(
		'IS_AJAX' => 'Y',
		'ENTITY_TYPE' => $type,
		'GET_AVATARS' => 'Y',
		'ADDITIONAL_FILTER' =>
			!empty($id)
			? array('ID' => $id)
			: array('>ID' => $minId),
		'EXTRA' => $extra
	));
}
//refresh Kanban
elseif ($action == 'get')
{
	$result = $APPLICATION->IncludeComponent('bitrix:crm.kanban', '', array(
		'IS_AJAX' => 'Y',
		'ENTITY_TYPE' => $type,
		'EXTRA' => $extra
	));
}
//get next page
elseif ($action == 'page' && !empty($column))
{
	$result = $APPLICATION->IncludeComponent('bitrix:crm.kanban', '', array(
		'IS_AJAX' => 'Y',
		'ENTITY_TYPE' => $type,
		'ADDITIONAL_FILTER' => array('COLUMN' => $column),
		'PAGE' => $page,
		'EXTRA' => $extra
	));
}
//change stage
elseif ($action == 'status' && !empty($id) && !empty($newState))
{
	$result = $APPLICATION->IncludeComponent('bitrix:crm.kanban', '', array(
		'IS_AJAX' => 'Y',
		'ENTITY_TYPE' => $type,
		'ONLY_COLUMNS' => 'Y',
		'EXTRA' => $extra
	));
}
//activity items
elseif ($action == 'activities' && !empty($id))
{
	$APPLICATION->IncludeComponent('bitrix:crm.activity.todo', '', array(
		'OWNER_TYPE_ID' => $type,
		'OWNER_ID' => $id,
		'IS_AJAX' => 'Y',
		'COMPLETED' => 'N'
	));
}
else
{
	$result = array('ERROR' => 'Unknown action or params');
}

//output
if (is_array($result) && (isset($result['ITEMS']) || isset($result['ERROR'])))
{
	$GLOBALS['APPLICATION']->RestartBuffer();
	if (SITE_CHARSET != 'UTF-8')
	{
		$result = $GLOBALS['APPLICATION']->ConvertCharsetArray($result, SITE_CHARSET, 'UTF-8');
	}

	header('Content-Type: application/json');

	if (isset($result['ERROR']) && $result['ERROR']!='')
	{
		echo CUtil::PhpToJSObject(array('error' => $result['ERROR']));
	}
	else
	{
		echo CUtil::PhpToJSObject($result['ITEMS']);
	}
}

require_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/main/include/epilog_after.php');