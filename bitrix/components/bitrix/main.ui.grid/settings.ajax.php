<?

define("NO_KEEP_STATISTIC", true);
define("NO_AGENT_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

use Bitrix\Main\Grid\Actions;
use Bitrix\Main\Web;

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$response = new \Bitrix\Main\HttpResponse(\Bitrix\Main\Application::getInstance()->getContext());
$response->addHeader("Content-Type", "application/json");

global $USER;
if (!$USER->IsAuthorized())
{
	$response->flush(Web\Json::encode(array(
			"error" => "Not authorized"
	)));

	die();
}

$request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$request->addFilter(new Web\PostDecodeFilter);

if (!$request->isAjaxRequest())
{
	$response->flush(Web\Json::encode(array(
		"error" => "Request is not XHR"
	)));

	die();
}

if (!$request->isPost())
{
	$response->flush(Web\Json::encode(array(
		"error" => "Request is not POST"
	)));

	die();
}


$options = new \Bitrix\Main\Grid\Options($request->get("GRID_ID"));
$error = false;

switch ($request->get("action"))
{
	case Actions::GRID_RESET:
		$options->deleteView("default");
		break;

	case Actions::GRID_SET_COLUMNS:
		$options->setColumns($request->getPost("columns"));
		break;

	case Actions::GRID_SET_THEME:
		$options->setTheme($request->getPost("theme"));
		break;

	case Actions::GRID_SAVE_SETTINGS:
		$options->setViewSettings($request->getPost("view_id"), $request->getPostList()->toArray());

		if ($request->get("set_default_settings") === "Y" &&
			$USER->canDoOperation("edit_other_settings"))
		{
			$options->setDefaultView($request->getPost("view_settings"));

			if ($request->get("delete_user_settings") === "Y")
			{
				$options->resetDefaultView();
			}
		}
		break;

	case Actions::SET_CUSTOM_NAMES:
		$options->setCustomNames($request->getPost("custom_names"));
		break;

	case Actions::GRID_DELETE_VIEW:
		$options->deleteView($request->getPost("view_id"));
		break;

	case Actions::GRID_SET_VIEW:
		$options->setView($request->getPost("view_id"));
		break;

	case Actions::GRID_SET_SORT:
		$options->setSorting($request->getPost("by"), $request->getPost("order"));
		break;

	case Actions::GRID_SET_COLUMN_SIZES:
		$options->setColumnsSizes($request->getPost("expand"), $request->getPost('sizes'));
		break;

	case Actions::GRID_SET_PAGE_SIZE:
		$options->setPageSize($request->getPost('pageSize'));
		break;

	default:
		$error = true;
}

if (!$error)
{
	$options->save();
	$response->flush(Web\Json::encode($options->GetOptions()));
}
else
{
	$response->flush(Web\Json::encode(array(
		"error" => "Unknown action",
		"action" => $request->get("action")
	)));
}