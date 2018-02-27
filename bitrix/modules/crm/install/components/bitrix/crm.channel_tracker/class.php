<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\Date;
use Bitrix\Crm\Integration\Channel\IChannelInfo;
use Bitrix\Crm\Integration\Channel\IChannelGroupInfo;
use Bitrix\Crm\Integration\Channel\ChannelTrackerManager;
use Bitrix\Crm\Integration\Channel\ChannelType;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Crm\Widget\Filter;
use Bitrix\Crm\Widget\FilterPeriodType;
use Bitrix\Crm\Widget\Data\DataSource;
use Bitrix\Crm\Widget\Data\LeadChannelStatistics;
use Bitrix\Crm\Widget\Data\DealChannelStatistics;
use Bitrix\Crm\Widget\Data\Activity\ChannelStatistics as ActivityChannelStatistics;

Loc::loadMessages(__FILE__);

class CCrmStartPageComponent extends CBitrixComponent
{
	/** @var string */
	protected $guid = '';
	/** @var string */
	protected $widgetGuid = '';
	/** @var IChannelInfo[]|null */
	private $channelInfos = null;
	/** @var IChannelGroupInfo[]|null */
	private $channelGroupInfos = null;
	/** @var array|null */
	private $channelData = null;
	/** @var array|null */
	private $totals = null;
	/** @var array|null */
	private $groupTotals = null;
	/** @var array|null */
	private $config = null;
	private $errors = array();
	private $counters = null;
	private $headers = null;
	/** @var Bitrix\Crm\Widget\Filter|null */
	private $commonFilter = null;

	/**
	 * @return array|null
	 * @throws Main\ObjectNotFoundException
	 */
	protected function prepareCounters()
	{
		if($this->counters !== null)
		{
			return $this->counters;
		}

		$this->counters = array();
		//region Activity
		$source = new ActivityChannelStatistics(array());

		$filter = new Filter(array('periodType' => FilterPeriodType::CURRENT_DAY));

		if(!$this->commonFilter->isEmpty())
		{
			Bitrix\Crm\Widget\Filter::merge($this->commonFilter, $filter, array('overridePeriod' => true));
		}
		$results = $source->getList(
			array(
				'filter' => $filter,
				'select' => array(array('name' => 'COUNT', 'aggregate' => 'COUNT')),
				'group' => ActivityChannelStatistics::GROUP_BY_CHANNEL,
				'enableGroupKey' => true
			)
		);

		$this->prepareCounter('ACTIVITY', $results, $source, $filter, 'COUNT', 'int');
		//endregion
		//region Lead
		$source = new LeadChannelStatistics(array());
		$filter = new Filter(
			array(
				'periodType' => FilterPeriodType::BEFORE,
				'end' => new Date(),
				'extras' => array('semanticID' => PhaseSemantics::PROCESS)
			)
		);
		if(!$this->commonFilter->isEmpty())
		{
			Bitrix\Crm\Widget\Filter::merge($this->commonFilter, $filter, array('overridePeriod' => false));
			$filter->setEndFromPeriod($this->commonFilter->getPeriod());
		}
		$results = $source->getList(
			array(
				'filter' => $filter,
				'select' => array(array('name' => 'COUNT', 'aggregate' => 'COUNT')),
				'group' => LeadChannelStatistics::GROUP_BY_CHANNEL,
				'enableGroupKey' => true
			)
		);

		$this->prepareCounter('LEAD', $results, $source, $filter, 'COUNT', 'int');
		//endregion
		//region Deal (process)
		$source = new DealChannelStatistics(array());
		$filter = new Filter(
			array(
				'periodType' => FilterPeriodType::BEFORE,
				'end' => new Date(),
				'extras' => array('semanticID' => PhaseSemantics::PROCESS)
			)
		);
		if(!$this->commonFilter->isEmpty())
		{
			Bitrix\Crm\Widget\Filter::merge($this->commonFilter, $filter, array('overridePeriod' => false));
			$filter->setEndFromPeriod($this->commonFilter->getPeriod());
		}

		$results = $source->getList(
			array(
				'filter' => $filter,
				'select' => array(array('name' => 'COUNT', 'aggregate' => 'COUNT')),
				'group' => DealChannelStatistics::GROUP_BY_CHANNEL,
				'enableGroupKey' => true
			)
		);

		$this->prepareCounter('DEAL_PROCESS', $results, $source, $filter, 'COUNT', 'int');
		//endregion
		//region Deal (success)
		$source = new DealChannelStatistics(array());
		$filter = new Filter(
			array(
				'periodType' => FilterPeriodType::LAST_DAYS_30,
				'extras' => array('semanticID' => PhaseSemantics::SUCCESS)
			)
		);
		if(!$this->commonFilter->isEmpty())
		{
			Bitrix\Crm\Widget\Filter::merge($this->commonFilter, $filter, array('overridePeriod' => true));
		}

		$results = $source->getList(
			array(
				'filter' => $filter,
				'select' => array(array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM')),
				'group' => DealChannelStatistics::GROUP_BY_CHANNEL,
				'enableGroupKey' => true
			)
		);

		$this->prepareCounter('DEAL_SUCCESS', $results, $source, $filter, 'SUM_TOTAL', 'double');
		//endregion
		return $this->counters;
	}
	/**
	 * @param string $ID Channel ID.
	 * @param array $data Raw Data.
	 * @param DataSource $source Source.
	 * @param Filter $filter Filter.
	 * @param string $fieldName Field Name.
	 * @param string $valueType Value Type.
	 * @return void
	 */
	protected function prepareCounter($ID, $data, $source, $filter, $fieldName, $valueType = '')
	{
		$this->counters[$ID] = array();
		foreach($this->channelData as $key => $channelItem)
		{
			if(!isset($this->totals[$ID]))
			{
				$this->totals[$ID] = 0;
			}

			$groupID = $channelItem['GROUP_ID'];
			if($groupID !== '')
			{
				if(!isset($this->groupTotals[$groupID]))
				{
					$this->groupTotals[$groupID] = array();
				}
				if(!isset($this->groupTotals[$groupID][$ID]))
				{
					$this->groupTotals[$groupID][$ID] = 0;
				}
			}

			$params = $channelItem['PARAMS'];
			$this->counters[$ID][$key] = array('VALUE' => 0, 'URL' => '#');

			if(isset($data[$key]) && isset($data[$key][$fieldName]))
			{
				$value = $data[$key][$fieldName];
				if($valueType === 'double')
				{
					$value = (double)$value;
				}
				else
				{
					$value = (int)$value;
				}

				$this->counters[$ID][$key]['VALUE'] = $value;

				$this->totals[$ID] += $value;
				if($groupID !== '')
				{
					$this->groupTotals[$groupID][$ID] += $value;
				}
			}

			foreach($params as $k => $v)
			{
				$filter->setExtraParam($k, $v);
			}
			$this->counters[$ID][$key]['URL'] = $source->getDetailsPageUrl(array('filter' => $filter));
		}
	}
	public function executeComponent()
	{
		if (!Bitrix\Main\Loader::includeModule('crm'))
		{
			$this->errors[] = GetMessage('CRM_MODULE_NOT_INSTALLED');
			return;
		}

		if(isset($this->arParams['GUID']))
		{
			$this->guid = $this->arParams['GUID'];
		}
		if($this->guid === '')
		{
			$this->guid = 'start';
		}

		if(isset($this->arParams['WIDGET_GUID']))
		{
			$this->widgetGuid = $this->arParams['WIDGET_GUID'];
		}
		if($this->widgetGuid === '')
		{
			$this->widgetGuid = 'start_widget';
		}

		$this->headers = array(
			'NAME' => GetMessage('CRM_CH_TRACKER_HEADER_NAME'),
			'ACTIVITY' => GetMessage('CRM_CH_TRACKER_HEADER_ACTIVITY'),
			'LEAD' => GetMessage('CRM_CH_TRACKER_HEADER_LEAD'),
			'DEAL_PROCESS' => GetMessage('CRM_CH_TRACKER_HEADER_DEAL_PROCESS'),
			'DEAL_SUCCESS' => GetMessage('CRM_CH_TRACKER_HEADER_DEAL_SUCCESS')
		);

		$this->config = CUserOptions::GetOption('crm.entity.channeltracker', $this->guid, array());
		if(!isset($this->config['expanded']))
		{
			$this->config['expanded'] = 'Y';
		}

		//region Preparation of common filter
		$gridOptions = new CGridOptions($this->widgetGuid);
		$filterFields = Filter::internalizeParams(
			$gridOptions->GetFilter(
				array(
					array('id' => 'RESPONSIBLE_ID'),
					array('id' => 'PERIOD')
				)
			)
		);
		Filter::sanitizeParams($filterFields);
		$this->commonFilter = new Filter($filterFields);
		//endregion

		ChannelTrackerManager::initializeUserContext();

		$this->totals = array('OVERALL' => 0, 'IN_USE' => 0);
		$this->groupTotals = array();

		$this->channelData = array();
		$this->channelInfos = array();
		foreach(ChannelTrackerManager::getInfos() as $info)
		{
			if(!($info->checkConfigurationPermission() /*&& $info->isEnabled()*/))
			{
				continue;
			}

			$this->channelInfos[] = $info;
			$this->totals['OVERALL']++;
			if($info->isInUse())
			{
				$this->totals['IN_USE']++;
			}
		}

		foreach($this->channelInfos as $info)
		{
			$key = $info->getKey();
			$this->channelData[$key] = array(
				'PARAMS' => array(
					'channelTypeID' => $info->getChannelTypeID(),
					'channelOriginID' => $info->getChannelOrigin(),
					'channelComponentID' => $info->getChannelComponent()
				),
				'GROUP_ID' => $info->getGroupID()
			);
		}

		$counters = $this->prepareCounters();

		$items = array();
		$groupItems = array();

		foreach(ChannelTrackerManager::getGroupInfos() as $groupInfo)
		{
			$groupID = $groupInfo->getID();
			$groupItems[$groupID] = array(
				'ID' => $groupID,
				'CAPTION' => $groupInfo->getCaption(),
				'URL' => $groupInfo->getUrl(),
				'IS_DISPLAYABLE' => $groupInfo->isDisplayable(),
				'IS_IN_USE' => false
			);
		}

		$counterStub = array('VALUE' => 0, 'URL' => '#');
		foreach($this->channelInfos as $info)
		{
			$key = $info->getKey();
			$isInUse = $info->isInUse();
			$groupID = $info->getGroupID();
			$items[] = array(
				'IS_IN_USE' => $isInUse,
				'GROUP_ID' => $groupID,
				'CAPTION' => $info->getCaption(),
				'CONFIG_URL' => $info->getConfigurationUrl(),
				'COUNTERS' => array(
					'ACTIVITY' => isset($counters['ACTIVITY'][$key]) ? $counters['ACTIVITY'][$key] : $counterStub,
					'LEAD' => isset($counters['LEAD'][$key]) ? $counters['LEAD'][$key] : $counterStub,
					'DEAL_PROCESS' => isset($counters['DEAL_PROCESS'][$key]) ? $counters['DEAL_PROCESS'][$key] : $counterStub,
					'DEAL_SUCCESS' => isset($counters['DEAL_SUCCESS'][$key]) ? $counters['DEAL_SUCCESS'][$key] : $counterStub
				)
			);

			if($isInUse && $groupID !== '' && isset($groupItems[$groupID]) && !$groupItems[$groupID]['IS_IN_USE'])
			{
				$groupItems[$groupID]['IS_IN_USE'] = true;
			}
		}

		$this->arResult['GUID'] = $this->guid;
		$this->arResult['WIDGET_GUID'] = $this->widgetGuid;
		$this->arResult['CONFIG'] = $this->config;
		$this->arResult['ITEMS'] = $items;
		$this->arResult['GROUP_ITEMS'] = $groupItems;
		$this->arResult['TOTALS'] = $this->totals;
		$this->arResult['GROUP_TOTALS'] = $this->groupTotals;
		$this->arResult['CURRENCY_ID'] = CCrmCurrency::GetAccountCurrencyID();

		$this->arResult['MESSAGES'] = array(
			'IN_USE' => GetMessage(
				'CRM_CH_TRACKER_IN_USE',
				array('#IN_USE#' => $this->totals['IN_USE'], '#OVERALL#' => $this->totals['OVERALL'])
			)
		);
		$this->arResult['TITLE'] = GetMessage('CRM_CH_TRACKER_TITLE');
		$this->arResult['HEADERS'] = $this->headers;

		$this->includeComponentTemplate();
	}
}
