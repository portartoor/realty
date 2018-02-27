<?php

namespace Bitrix\Tasks\Ui\Preview;

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Tasks\Util\User;

Loc::loadLanguageFile(__FILE__);

class Task
{
	public static function buildPreview(array $params)
	{
		global $APPLICATION;

		ob_start();
		$APPLICATION->IncludeComponent(
			'bitrix:tasks.task.preview',
			'',
			$params
		);
		return ob_get_clean();
	}

	public static function checkUserReadAccess(array $params)
	{
		$task = new \CTaskItem($params['taskId'], static::getUser()->GetID());
		$access = $task->checkCanRead();

		return !!$access;
	}

	public static function getImAttach(array $params)
	{
		if(!Loader::includeModule('im'))
			return false;

		$task = \CTasks::getById(
			$params['taskId'],
			false,
			array(
				'returnAsArray'  => true,
				'bSkipExtraData' => false
			)
		);
		if($task === false)
			return false;

		$task['LINK'] = \CTaskNotifications::getNotificationPath(array('ID' => $task['RESPONSIBLE_ID']), $task['ID']);

		$attach = new \CIMMessageParamAttach(1, '#E30000');
		$attach->AddUser(Array(
			'NAME' => $task['TITLE'],
			//'AVATAR' => '', // todo: task icon
			'LINK' => $task['LINK']
		));
		$attach->AddDelimiter(Array('COLOR' => '#c6c6c6'));
		$grid = array();
		if($task['STATUS'] > 0)
		{
			$grid[] = Array(
				"NAME" => Loc::getMessage('TASK_PREVIEW_FIELD_STATUS') . ":",
				"VALUE" => Loc::getMessage('TASKS_TASK_STATUS_'.$task['STATUS']),
				"DISPLAY" => "COLUMN",
				"WIDTH" => 120,
			);
		}

		$grid[] = Array(
			"NAME" => Loc::getMessage('TASK_PREVIEW_FIELD_ASSIGNER') . ":",
			"VALUE" => htmlspecialcharsback(\Bitrix\Im\User::getInstance($task['CREATED_BY'])->getFullName()),
			"USER_ID" => $task['CREATED_BY'],
			"DISPLAY" => "COLUMN",
			"WIDTH" => 120,
		);

		$grid[] = Array(
			"NAME" => Loc::getMessage('TASK_PREVIEW_FIELD_RESPONSIBLE') . ":",
			"VALUE" => htmlspecialcharsback(\Bitrix\Im\User::getInstance($task['RESPONSIBLE_ID'])->getFullName()),
			"USER_ID" => $task['RESPONSIBLE_ID'],
			"DISPLAY" => "COLUMN",
			"WIDTH" => 120,
		);

		if($task['DEADLINE'] != '')
		{
			$grid[] = Array(
				"NAME" => Loc::getMessage('TASK_PREVIEW_FIELD_DEADLINE') . ":",
				"VALUE" => $task['DEADLINE'],
				"DISPLAY" => "COLUMN",
				"WIDTH" => 120,
			);
		}

		if($task['DESCRIPTION'] != '')
		{
			$grid[] = Array(
				"NAME" => Loc::getMessage('TASK_PREVIEW_FIELD_DESCRIPTION') . ":",
				"VALUE" => $task['DESCRIPTION'],
				"DISPLAY" => "COLUMN",
				"WIDTH" => 120,
			);
		}

		$attach->AddGrid($grid);
		return $attach;
	}

	protected function getUser()
	{
		return User::get();
	}
}