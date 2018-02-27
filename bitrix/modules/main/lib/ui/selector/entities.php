<?

namespace Bitrix\Main\UI\Selector;

use Bitrix\Main\Event;
use Bitrix\Main\EventResult;

class Entities
{
	public static function getList($params)
	{
		$result = array();

		$event = new Event("main", "OnUISelectorEntitiesGetList", $params);
		$event->send();
		$eventResultList = $event->getResults();

		if (is_array($eventResultList) && !empty($eventResultList))
		{
			foreach ($eventResultList as $eventResult)
			{
				if ($eventResult->getType() == EventResult::SUCCESS)
				{
					$resultParams = $eventResult->getParameters();
					$result = $resultParams['result'];
					break;
				}
			}
		}

		return $result;
	}
}