<?
namespace Bitrix\Main\Update;
use \Bitrix\Main\EventManager;
use \Bitrix\Main\Web\Json;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Context;
abstract class Stepper
{
	protected static $moduleId = "main";
	protected $deleteFile = false;
	private $showParams = array(
		"status" => "done",
		"progress" => 100,
		"title" => "Title update stepper"
	);
	private static $filesToUnlink = array();
	private static $countId = 0;
	/**
	 * Returns HTML to show updates.
	 * @param string $moduleId
	 * @param array $ids
	 * @return string
	 */
	public static function getHtml($moduleId = null, array $ids = array())
	{
		$result = array();
		$option = Option::get("main", "stepper", "");
		if ($option !== "" )
			$option = unserialize($option);
		$option = is_array($option) ? $option : array();
		if ($moduleId === null)
		{
			$result = $option;
		}
		else if (array_key_exists($moduleId, $option))
		{
			$result[$moduleId] = $option[$moduleId];
			if (!empty($ids))
				$result[$moduleId] = array_intersect_key($result[$moduleId], array_flip($ids));
		}
		$return = array();
		foreach ($result as $m => $classes)
		{
			foreach ($classes as $c => $r)
			{
				$return[] = array_merge($r, array("moduleId" => $m, "class" => $c, "id" => self::$countId++));
			}
		}
		$result = '';
		if (!empty($return))
		{
			\CJSCore::Init(array('update_stepper'));
			foreach ($return as $agent)
			{
				$result .= <<<HTML
<div class="main-stepper main-stepper-show" id="{$agent["id"]}-container">
	<div class="main-stepper-info" id="{$agent["id"]}-title">{$agent["title"]}</div>
	<div class="main-stepper-inner">
		<div class="main-stepper-bar">
			<div class="main-stepper-bar-line" id="{$agent["id"]}-bar" style="width:{$agent["progress"]}%;"></div>
		</div>
		<div class="main-stepper-steps" id="{$agent["id"]}-steps">{$agent["steps"]}</div>
	</div>
</div>
HTML;
			}
			$return = \CUtil::PhpToJSObject($return);
			$result = <<<HTML
<div class="main-stepper-block">{$result}
<script>BX.ready(function(){ if (BX && BX["UpdateStepperRegister"]) { BX.UpdateStepperRegister({$return}); }});</script>
</div>
HTML;
		}
		return $result;
	}
	/**
	 * Execute an agent
	 * @return string
	 */
	public static function execAgent()
	{
		$updater = self::createInstance();
		$className = get_class($updater);
		try
		{
			$result = array();

			$option = Option::get("main", "stepper", "");
			if ($option !== "" )
				$option = unserialize($option);
			$option = is_array($option) ? $option : array();
			if (!array_key_exists($updater->getModuleId(), $option))
				$option[$updater->getModuleId()] = array();
			if ($updater->execute($result) === true)
			{
				$updater->showParams["status"] = "continue";
				$updater->showParams["progress"] = $result["progress"];
				$updater->showParams["title"] = $result["title"];
				$updater->showParams["steps"] = $result["steps"];
				$option[$updater->getModuleId()][$className] = $updater->showParams;
				Option::set("main", "stepper", serialize($option));
				return $className . '::execAgent();';
			}
			if ($updater->deleteFile === true && \Bitrix\Main\ModuleManager::isModuleInstalled("bitrix24") !== true)
			{
				$res = new \ReflectionClass($updater);
				self::$filesToUnlink[] = $res->getFileName();
			}
			unset($option[$updater->getModuleId()][$className]);
			if (count($option[$updater->getModuleId()]) <= 0)
				unset($option[$updater->getModuleId()]);
			if (count($option) <= 0)
				Option::delete("main", array("name" => "stepper"));
			else
				Option::set("main", "stepper", serialize($option));

			return '';
		}
		catch(\Exception $e)
		{
			return $className . '::execAgent();';
		}
	}

	public function __destruct()
	{
		if (!empty(self::$filesToUnlink))
		{
			while ($file = array_pop(self::$filesToUnlink))
			{
				$file = \CBXVirtualIo::GetInstance()->GetFile($file);

				$langDir = $fileName = "";
				$filePath = $file->GetPathWithName();
				while(($slashPos = strrpos($filePath, "/")) !== false)
				{
					$filePath = substr($filePath, 0, $slashPos);
					$langPath = $filePath."/lang";
					if(is_dir($langPath))
					{
						$langDir = $langPath;
						$fileName = substr($file->GetPathWithName(), $slashPos);
						break;
					}
				}
				if ($langDir <> "" && ($langDir = \CBXVirtualIo::GetInstance()->GetDirectory($langDir)) &&
					$langDir->IsExists())
				{
					$languages = $langDir->GetChildren();
					foreach ($languages as $language)
					{
						if ($language->IsDirectory() &&
							($f = \CBXVirtualIo::GetInstance()->GetFile($language->GetPathWithName().$fileName)) &&
							$f->IsExists())
						{
							$f->unlink();
						}
					}
					unset($f);
				}
				$file->unlink();
			}
			unset($file);
		}
	}
	/**
	 * Executes some action.
	 * @param array $result
	 * @return boolean
	 */
	abstract function execute(array &$result);
	/**
	 * Just fabric method.
	 * @return Stepper
	 */
	public static function createInstance()
	{
		return new static;
	}
	/**
	 * Wrap-function to get moduleId.
	 * @return string
	 */
	public static function getModuleId()
	{
		return static::$moduleId;
	}
	/**
	 * Adds agent for current class.
	 * @return void
	 */
	public static function bind()
	{
		$c = get_called_class();
		\CAgent::AddAgent($c.'::execAgent();', $c::getModuleId(), "Y", 5, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::GetOffset() + 60, "FULL"));
	}
	/**
	 * Just method to check request.
	 * @return void
	 */
	public static function checkRequest()
	{
		$result = array();
		$data = Context::getCurrent()->getRequest()->getPost("stepper");
		if (is_array($data))
		{
			$option = Option::get("main", "stepper", "");
			if ($option !== "" )
				$option = unserialize($option);
			$option = is_array($option) ? $option : array();

			foreach ($data as $stepper)
			{
				if (is_array($stepper) && array_key_exists("moduleId", $stepper))
				{
					$r = array(
						"moduleId" => $stepper["moduleId"],
						"class" => $stepper["class"],
						"status" => "done"
					);
					if (array_key_exists($stepper["moduleId"], $option) && array_key_exists($stepper["class"], $option[$stepper["moduleId"]]))
						$r = array_merge($r, $option[$stepper["moduleId"]][$stepper["class"]]);

					$result[] = $r;
				}
			}
		}
		self::sendJson($result);
	}
	/**
	 * Sends json.
	 * @param $result
	 * @return void
	 */
	private static function sendJson($result)
	{
		global $APPLICATION;
		$APPLICATION->RestartBuffer();
		while(ob_end_clean());

		$version = IsIE();
		if ( !(0 < $version && $version < 10) )
			header('Content-Type:application/json; charset=UTF-8');

		echo Json::encode($result);
		\CMain::finalActions();
		die;
	}
	/**
	 * Returns $USER.
	 * @return \CAllUser|\CUser
	 */
	public function getUser()
	{
		global $USER;
		return $USER;
	}
}
?>