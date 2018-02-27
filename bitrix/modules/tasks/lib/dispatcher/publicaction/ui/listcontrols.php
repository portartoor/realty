<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2016 Bitrix
 *
 * @access private
 *
 * Each method you put here you`ll be able to call as ENTITY_NAME.METHOD_NAME via AJAX and\or REST, so be careful.
 */

namespace Bitrix\Tasks\Dispatcher\PublicAction\Ui;

use Bitrix\Tasks;
use Bitrix\Tasks\Integration;
use Bitrix\Tasks\Util\User;

final class ListControls extends \Bitrix\Tasks\Dispatcher\RestrictedAction
{
	public function add($data, $parameters = array())
	{
		global $DB;
		$result = array();

		if (!User::isAuthorized())
		{
			throw new Tasks\Exception("Authentication is required.");
		}

		$title = isset($data["title"]) ? trim($data["title"]) : "";

		$userEmail = isset($data["userEmail"]) ? trim($data["userEmail"]) : "";
		$userName = isset($data["userName"]) ? trim($data["userName"]) : "";
		$userLastName = isset($data["userLastName"]) ? trim($data["userLastName"]) : "";

		$responsibleId = 0;
		if (isset($data["responsibleId"]))
		{
			$responsibleId = intval($data["responsibleId"]);
		}
		else if ($userEmail === "")
		{
			$responsibleId = User::getId();
		}

		$deadline = isset($data["deadline"]) && $DB->FormatDate($data["deadline"], \CSite::GetDateFormat("FULL")) ? $data["deadline"] : "";
		$description = isset($data["description"]) ? trim($data["description"]) : "";
		$project = isset($data["project"]) ? intval($data["project"]) : 0;
		$nameTemplate = isset($data["nameTemplate"]) ? trim($data["nameTemplate"]) : "";
		$ganttMode = isset($data["ganttMode"]) && ($data["ganttMode"] === true || $data["ganttMode"] === "1");

		if (strlen($nameTemplate) > 0)
		{
			preg_match_all("/(#NAME#)|(#NOBR#)|(#\\/NOBR#)|(#LAST_NAME#)|(#SECOND_NAME#)|(#NAME_SHORT#)|(#SECOND_NAME_SHORT#)|\\s|\\,/", $nameTemplate, $matches);
			$nameTemplate = implode("", $matches[0]);
		}
		else
		{
			$nameTemplate = \CSite::GetNameFormat(false);
		}

		$fields = array(
			"TITLE" => $title,
			"DESCRIPTION" => $description,
			"SE_RESPONSIBLE" => array(
				$userEmail !== ""
				? array(
					"EMAIL" => $userEmail,
					"NAME" => $userName,
					"LAST_NAME" => $userLastName
				)
				: array(
					"ID" => $responsibleId
				)
			),
			"DEADLINE" => $deadline,
			"SITE_ID" => $data["siteId"],
			"GROUP_ID" => $project,
			"NAME_TEMPLATE" => $nameTemplate,
			"DESCRIPTION_IN_BBCODE" => "Y"
		);

		$taskData = Tasks\Manager\Task::add(User::getId(), $fields);
		$taskItem = \CTaskItem::getInstance($taskData["DATA"]["ID"], User::getId());

		$task = $taskItem->getData();
		$task["GROUP_NAME"] = "";
		if ($task["GROUP_ID"])
		{
			$socGroup = \CSocNetGroup::GetByID($task["GROUP_ID"]);
			if ($socGroup)
			{
				$task["GROUP_NAME"] = $socGroup["NAME"];
			}
		}


		Integration\SocialNetwork::setLogDestinationLast(array(
			"USER" => array($task["RESPONSIBLE_ID"]),
			"SGROUP" => array($task["GROUP_ID"])
	   	));

		$taskId = $taskItem->getId();

		$arPaths = array(
			"PATH_TO_TASKS_TASK" => isset($data["pathToTask"]) ? trim($data["pathToTask"]) : "",
			"PATH_TO_USER_PROFILE" => isset($data["pathToUser"]) ? trim($data["pathToUser"]) : "",
			"PATH_TO_USER_TASKS_TASK" => isset($data["pathToUserTasksTask"]) ? trim($data["pathToUserTasksTask"]) : ""
		);

		$columnsOrder = null;
		if (isset($data["columnsOrder"]) && is_array($data["columnsOrder"]))
		{
			$columnsOrder = array_map("intval", $data["columnsOrder"]);
		}

		$order = $this->unserializeArray("order", $data);
		$filter = $this->unserializeArray("filter", $data);
		$navigation = $this->unserializeArray("navigation", $data);
		$select = $this->unserializeArray("select", $data);

		$result["taskRaw"] = $task;
		$result["taskId"] = $task["ID"];
		$result["taskPath"] = \CComponentEngine::MakePathFromTemplate(
			$arPaths["PATH_TO_TASKS_TASK"],
			array("task_id" => $task["ID"], "action" => "view")
		);

		$result["position"] = $this->getTaskPosition($taskId, $order, $filter, $navigation, $select);

		if ($ganttMode)
		{
			$result["task"] = $this->getJson($task, $arPaths, $nameTemplate);
		}
		else
		{
			$result["html"] = $this->getHtml($task, $arPaths, $nameTemplate, $columnsOrder);
		}

		return $result;
	}

	private function unserializeArray($key, $data)
	{
		$result = array();
		if (isset($data[$key]) && checkSerializedData($data[$key]))
		{
			$result = unserialize($data[$key]);
			if (!is_array($result))
			{
				$result = array();
			}
		}

		return $result;
	}

	private function getJson($task, $arPaths, $nameTemplate)
	{
		ob_start();
		tasksRenderJSON($task, 0, $arPaths, false, true, false, $nameTemplate, array());
		$jsonString = ob_get_clean();

		return $jsonString;
	}

	private function getHtml($task, $arPaths, $nameTemplate, $columnsOrder)
	{
		global $APPLICATION;
		$APPLICATION->RestartBuffer();

		$params = array(
			"PATHS"         => $arPaths,
			"PLAIN"         => false,
			"DEFER"         => true,
			"SITE_ID"       => $task["SITE_ID"],
			"TASK_ADDED"    => true,
			"IFRAME"        => "N",
			"NAME_TEMPLATE" => $nameTemplate,
			"DATA_COLLECTION" => array(
				array(
					"CHILDREN_COUNT"   => 0,
					"DEPTH"            => 0,
					"UPDATES_COUNT"    => 0,
					"PROJECT_EXPANDED" => true,
					"ALLOWED_ACTIONS"  => null,
					"TASK"             => $task
				)
			)
		);

		if ($columnsOrder !== null)
		{
			$params["COLUMNS_IDS"] = $columnsOrder;
		}

		ob_start();
		$APPLICATION->IncludeComponent(
			"bitrix:tasks.list.items",
			".default",
			$params,
			null,
			array("HIDE_ICONS" => "Y")
		);
		$html = ob_get_clean();

		return $html;
	}

	private function getTaskPosition($taskId, array $order = array(), array $filter = array(), array $navigation = array(), array $select = array())
	{
		//Navigation Restrictions
		if (isset($navigation["NAV_PARAMS"]))
		{
			$navigation["NAV_PARAMS"]["NavShowAll"] = false;
			$navigation["NAV_PARAMS"]["bShowAll"] = false;
		}

		$maxPageSize = \Bitrix\Tasks\Manager\Task::LIMIT_PAGE_SIZE;
		if (isset($navigation["NAV_PARAMS"]["nPageTop"]))
		{
			$navigation["NAV_PARAMS"]["nPageTop"] = min(intval($navigation["NAV_PARAMS"]["nPageTop"]), $maxPageSize);
		}

		if (isset($navigation["NAV_PARAMS"]["nPageSize"]))
		{
			$navigation["NAV_PARAMS"]["nPageSize"] = min(intval($navigation["NAV_PARAMS"]["nPageSize"]), $maxPageSize);
		}

		list($items) = \CTaskItem::fetchList(User::getId(), $order, $filter, $navigation, $select);

		$result = array(
			"found" => false,
			"prevTaskId" => 0,
			"nextTaskId" => 0
		);

		for ($i = 0, $l = count($items); $i < $l; $i++)
		{
			$id = $items[$i]->getId();
			if ($id == $taskId)
			{
				$result["found"] = true;
				if (isset($items[$i + 1]))
				{
					$result["nextTaskId"] = $items[$i + 1]->getId();
				}

				break;
			}

			$result["prevTaskId"] = $id;
		}

		return $result;

	}
}