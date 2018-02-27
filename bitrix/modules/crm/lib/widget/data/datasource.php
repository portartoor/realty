<?php
namespace Bitrix\Crm\Widget\Data;
use Bitrix\Main;
use Bitrix\Crm\Widget\Filter;
use Bitrix\Crm\Integration\Channel\ChannelType;

abstract class DataSource
{
	/** @var array */
	protected $settings = null;
	/** @var int */
	protected $userID = 0;
	/** @var boolean */
	protected $enablePermissionCheck = true;
	/** @var array  */
	protected $filterContextData = null;
	/** @var array */
	protected static $userNames = array();
	public function __construct(array $settings, $userID = 0, $enablePermissionCheck = true)
	{
		$this->settings = $settings;

		if(!is_int($userID))
		{
			$userID = (int)$userID;
		}
		if($userID <= 0)
		{
			$userID = \CCrmSecurityHelper::GetCurrentUserID();
		}
		$this->userID = $userID;
		if(!is_bool($enablePermissionCheck))
		{
			$enablePermissionCheck = (bool)$enablePermissionCheck;
		}
		$this->enablePermissionCheck = $enablePermissionCheck;
		$this->filterContextData = array();
	}
	/**
	 * Prepare user names from user IDs.
	 * @static
	 * @param array $userIDs Source user IDs.
	 * @return array
	 */
	protected static function prepareUserNames(array $userIDs)
	{
		if(empty($userIDs))
		{
			return array();
		}

		$results = array();
		foreach($userIDs as $k => $v)
		{
			if(isset(self::$userNames[$v]))
			{
				$results[$v] = self::$userNames[$v];
				unset($userIDs[$v]);
			}
		}

		if(!empty($userIDs))
		{
			$dbResult = \CUser::GetList(
				($by = 'ID'),
				($order = 'ASC'),
				array('ID' => implode('||', $userIDs)),
				array('FIELDS' => array('ID', 'NAME', 'LAST_NAME', 'SECOND_NAME', 'LOGIN', 'TITLE'))
			);

			$format = \CSite::GetNameFormat(false);
			while($user = $dbResult->Fetch())
			{
				$userID = (int)$user['ID'];
				//using "strip_tags" for sanitize result string
				$results[$userID] = strip_tags(\CUser::FormatName($format, $user, true, false));
			}
		}

		return $results;
	}
	/**
	 * Prepare permission SQL.
	 * @return string|boolean
	 */
	protected abstract function preparePermissionSql();
	/**
	 * Get User ID.
	 * @return int
	 */
	public function getUserID()
	{
		return $this->userID;
	}
	/**
	 * Check if permission control enabled.
	 * @return boolean*/
	public function isPermissionCheckEnabled()
	{
		return $this->enablePermissionCheck;
	}
	/**
	 * Enable or disable permission control
	 * @param boolean $enable New value
	 */
	public function enablePermissionCheck($enable)
	{
		$this->enablePermissionCheck = $enable;
	}
	/**
	 * Get data preset full name (Data source name + preset name)
	 * @return string
	 */
	public function getPresetFullName()
	{
		return isset($this->settings['presetName']) ? strtoupper($this->settings['presetName']) : '';
	}
	/**
	 * Get data preset name
	 * @return string
	 */
	public function getPresetName()
	{
		$name = isset($this->settings['presetName']) ? strtoupper($this->settings['presetName']) : '';
		$parts = explode('::', $name);
		return is_array($parts) && count($parts) >= 2 ? $parts[1] : $name;
	}
	/**
	 * Get Datasource type name.
	 * @return string
	 */
	abstract function getTypeName();
	/**
	 * Prepare filter extra params according to context data.
	 * @param Filter $filter
	 * @return void
	 */
	public function applyFilterContext(Filter $filter)
	{
	}
	/**
	 * Get entity list.
	 * @param array $params List params.
	 * @return array
	 */
	abstract public function getList(array $params);
	/**
	 * Get first entity from list.
	 * @param array $params List params.
	 * @return array
	 */
	public function getFirst(array $params)
	{
		$l = $this->getList($params);
		return !empty($l) ? $l[0] : null;
	}
	/**
	 * Get field value of first entity from list.
	 * @param array $params List params.
	 * @param string $fieldName Field name.
	 * @param string $defaultValue Default field value.
	 * @return array
	 */
	public function getFirstValue(array $params, $fieldName, $defaultValue = '')
	{
		$l = $this->getList($params);
		return !empty($l) && isset($l[0][$fieldName]) ? $l[0][$fieldName] : $defaultValue;
	}
	/**
	 * Get details page URL.
	 * @param array $params Parameters.
	 * @return string
	 */
	public function getDetailsPageUrl(array $params)
	{
		return '';
	}
	/**
	 * Prepare entity list filter.
	 * @param array $filterParams Filter parameters.
	 * @return array
	 */
	public function prepareEntityListFilter(array $filterParams)
	{
		return null;
	}
	/**
	 * Initialize Demo data.
	 * @param array $data Data.
	 * @param array $params Parameters.
	 * @return array
	 */
	public function initializeDemoData(array $data, array $params)
	{
		return $data;
	}
	/**
	 * Get current data context
	 * @return DataContext
	 */
	public function getDataContext()
	{
		return DataContext::UNDEFINED;
	}
	/**
	 * Get filtration context parameters dictionary
	 * @return array
	 */
	public function getFilterContextData()
	{
		return $this->filterContextData;
	}
	/**
	 * Set filtration context parameters dictionary
	 * @return void
	 */
	public function setFilterContextData(array $data)
	{
		$this->filterContextData = $data;
	}
	/**
	 * Externalize filter channel parameters (prepare array for external usage).
	 * @static
	 * @param Filter $filter Source filter.
	 * @return array
	 */
	protected static function externalizeFilterChannel(Filter $filter)
	{
		$params = array();

		$typeID = $filter->getExtraParam('channelTypeID', ChannelType::UNDEFINED);
		if($typeID !== ChannelType::UNDEFINED)
		{
			$params['CHANNEL_TYPE_ID'] = $typeID;
		}

		$originID = $filter->getExtraParam('channelOriginID', '');
		if($originID !== '')
		{
			$params['CHANNEL_ORIGIN_ID'] = $originID;
		}

		$componentID = $filter->getExtraParam('channelComponentID', '');
		if($componentID !== '')
		{
			$params['CHANNEL_COMPONENT_ID'] = $componentID;
		}

		return $params;
	}

	protected static function internalizeFilterChannel(array $params, array &$filterParams)
	{
		if(!isset($params['CHANNEL_TYPE_ID'])
			&& !isset($params['CHANNEL_ORIGIN_ID'])
			&& !isset($params['CHANNEL_COMPONENT_ID'])
		)
		{
			return;
		}

		if(!isset($filterParams['extras']))
		{
			$filterParams['extras'] = array();
		}

		if(isset($params['CHANNEL_TYPE_ID']))
		{
			$filterParams['extras']['channelTypeID'] = (int)$params['CHANNEL_TYPE_ID'];
		}

		if(isset($params['CHANNEL_ORIGIN_ID']))
		{
			$filterParams['extras']['channelOriginID'] = $params['CHANNEL_ORIGIN_ID'];
		}

		if(isset($params['CHANNEL_COMPONENT_ID']))
		{
			$filterParams['extras']['channelComponentID'] = $params['CHANNEL_COMPONENT_ID'];
		}
	}
}