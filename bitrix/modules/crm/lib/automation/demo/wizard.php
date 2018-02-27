<?php
namespace Bitrix\Crm\Automation\Demo;

use Bitrix\Main;
use Bitrix\Crm\Automation\Factory;
use Bitrix\Crm\Automation\Engine;

class Wizard
{
	public static function addAgent()
	{
		\CAgent::AddAgent('\Bitrix\Crm\Automation\Demo\Wizard::installOnAgent();', 'crm', 'N', 60);
		return true;
	}

	public static function installOnAgent()
	{
		static::installVersion(1);
		return '';
	}

	public static function installOnNewPortal()
	{
		static::installVersion(2);
		return true;
	}

	public static function installSimpleCRM()
	{
		static::installVersion(3);
		return true;
	}

	private static function installVersion($version)
	{
		$version = (int)$version;
		if ($version <= 0)
			$version = 1;

		if (static::isNeedleFor(\CCrmOwnerType::Lead))
			static::installAutomation(\CCrmOwnerType::Lead, $version);

		if (static::isNeedleFor(\CCrmOwnerType::Deal))
			static::installAutomation(\CCrmOwnerType::Deal, $version);
	}

	private static function installAutomation($entityTypeId, $version = 1)
	{
		$robotsRelation = static::getRobots($entityTypeId, $version);
		if ($robotsRelation)
		{
			foreach ($robotsRelation as $status => $robots)
			{
				static::addTemplate($entityTypeId, $status, $robots);
			}
		}
	}

	private static function addTemplate($entityTypeId, $entityStatus, $robots)
	{
		$template = new Engine\Template(array(
			'ENTITY_TYPE_ID' => (int)$entityTypeId,
			'ENTITY_STATUS' => (string)$entityStatus
		));

		return $template->save($robots, 1); // USER_ID = 1, there is no other way to identify system import
	}

	private static function getRobots($entityTypeId, $version = 1)
	{
		if ($entityTypeId === \CCrmOwnerType::Lead)
			return static::loadFromFile('lead_'.$version);
		if ($entityTypeId === \CCrmOwnerType::Deal)
			return static::loadFromFile('deal_'.$version);

		return false;
	}

	private static function loadFromFile($filename)
	{
		$result = array();

		$filePath = __DIR__ . DIRECTORY_SEPARATOR . 'robots' . DIRECTORY_SEPARATOR . $filename . '.php';
		$file = new Main\IO\File($filePath);
		if ($file->isExists() && $file->isReadable())
			$result = include($file->getPhysicalPath());

		return is_array($result) ? $result : false;
	}

	private static function isNeedleFor($entityTypeId)
	{
		//Check automation status
		if (!Factory::isAutomationAvailable($entityTypeId, true))
			return false;

		//Check bizproc autostart workflows
		if (\CCrmBizProcHelper::HasAutoWorkflows($entityTypeId,  \CCrmBizProcEventType::Create)
			|| \CCrmBizProcHelper::HasAutoWorkflows($entityTypeId,  \CCrmBizProcEventType::Edit)
		)
			return false;

		return (static::countTemplates($entityTypeId) === 0);
	}

	private static function countTemplates($entityTypeId)
	{
		return (int)Engine\Entity\TemplateTable::getCount(array(
			'ENTITY_TYPE_ID' => $entityTypeId
		));
	}

	private static function isDefaultStatuses($entityTypeId)
	{
		$current = $default = null;
		if ($entityTypeId === \CCrmOwnerType::Lead)
		{
			$current = array_keys(\CCrmStatus::GetStatusList('STATUS'));
			$default = \CCrmStatus::GetDefaultLeadStatuses();

		}
		elseif ($entityTypeId === \CCrmOwnerType::Deal)
		{
			$current = array_keys(\CCrmStatus::GetStatusList('DEAL_STAGE'));
			$default = \CCrmStatus::GetDefaultDealStages();
		}

		if (is_array($current) && is_array($default) && count($current) === count($default))
		{
			foreach ($current as $i => $statusId)
			{
				if ($default[$i]['STATUS_ID'] !== $statusId)
					return false;
			}
			return true;
		}

		return false;
	}
}