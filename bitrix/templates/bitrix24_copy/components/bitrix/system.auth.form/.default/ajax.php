<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if($_SERVER["REQUEST_METHOD"]=="POST" && strlen($_POST["action"])>0 && check_bitrix_sessid())
{
	switch ($_POST["action"])
	{
		case "setView":
		{
			if (isset($_POST["currentStepId"]))
			{
				$currentStepId = $_POST["currentStepId"];

				$arViewedSteps = CUserOptions::GetOption("bitrix24", "new_helper_views", array());
				if (!in_array($currentStepId, $arViewedSteps))
				{
					$arViewedSteps[] = $currentStepId;
					CUserOptions::SetOption("bitrix24", "new_helper_views", $arViewedSteps);
				}
			}
			break;
		}
		case "setNotify":
		{
			$notify = CUserOptions::GetOption("bitrix24", "new_helper_notify", array());
			if (isset($_POST["time"]) && $_POST["time"] == "hour")
			{
				$notify["time"] = time() + 60*60;
			}
			elseif (isset($_POST["num"]))
			{
				$notify["num"] = intval($_POST["num"]);
				$notify["time"] = time() + 24*60*60;
			}

			CUserOptions::SetOption("bitrix24", "new_helper_notify", $notify);
			break;
		}
	}
	CMain::FinalActions();
	die();
}
?>