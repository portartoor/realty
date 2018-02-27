<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity;

class CrmKanbanComponent extends \CBitrixComponent
{
	protected $type = '';
	protected $fieldSum = '';
	protected $winPeriodKey = '';
	protected $currency = '';
	protected $statusKey = 'STATUS_ID';
	protected $uid = 0;
	protected $blockPage = 1;
	protected $blockSize = 20;
	protected $maxSortSize = 1000;
	protected $types = array();
	protected $contact = array();
	protected $company = array();
	protected $fmTypes = array();
	protected $modifyUsers = array();
	protected $additionalSelect = array();
	protected $additionalTypes = array();
	protected $items = array();
	protected $avatarSize = array('width' => 38, 'height' => 38);
	protected $allowedFMtypes = array('phone', 'email', 'im');
	protected $allowedUFtypes = array('string', 'enumeration', 'datetime', 'date');
	protected $pathMarkers = array('#lead_id#', '#contact_id#', '#company_id#', '#deal_id#', '#quote_id#', '#invoice_id#');
	protected $selectPresets = array(
								'lead' => array('ID', 'STATUS_ID', 'TITLE', 'DATE_CREATE', 'OPPORTUNITY', 'OPPORTUNITY_ACCOUNT', 'CURRENCY_ID', 'ACCOUNT_CURRENCY_ID', 'CONTACT_ID', 'COMPANY_ID', 'MODIFY_BY_ID'),
								'deal' => array('ID', 'STAGE_ID', 'TITLE', 'DATE_CREATE', 'BEGINDATE', 'OPPORTUNITY', 'OPPORTUNITY_ACCOUNT', 'CURRENCY_ID', 'ACCOUNT_CURRENCY_ID', 'CONTACT_ID', 'COMPANY_ID', 'MODIFY_BY_ID'),
								'quote' => array('ID', 'STATUS_ID', 'TITLE', 'DATE_CREATE', 'BEGINDATE', 'OPPORTUNITY', 'OPPORTUNITY_ACCOUNT', 'CURRENCY_ID', 'ACCOUNT_CURRENCY_ID', 'CONTACT_ID', 'COMPANY_ID', 'MODIFY_BY_ID'),
								'invoice' => array('ID', 'STATUS_ID', 'DATE_INSERT', 'DATE_INSERT_FORMAT', 'PAY_VOUCHER_DATE', 'DATE_BILL', 'ORDER_TOPIC', 'PRICE', 'CURRENCY', 'UF_CONTACT_ID', 'UF_COMPANY_ID'),
						);

	/**
	 * Init class' vars.
	 */
	protected function init()
	{
		Loc::loadMessages(__FILE__);

		$context = \Bitrix\Main\Application::getInstance()->getContext();
		$request = $context->getRequest();

		if (!\Bitrix\Main\Loader::includeModule('crm'))
		{
			ShowError(Loc::getMessage('CRM_KANBAN_CRM_NOT_INSTALLED'));
			return false;
		}
		if (!\CCrmPerms::IsAccessEnabled())
		{
			return false;
		}

		//type and types
		$this->types = array(
			'lead' => \CCrmOwnerType::LeadName,
			'deal' => \CCrmOwnerType::DealName,
			'quote' => \CCrmOwnerType::QuoteName,
			'invoice' => \CCrmOwnerType::InvoiceName
		);
		$this->fmTypes = array(
			'EMAIL_WORK' => Loc::getMessage('CRM_KANBAN_EMAIL_TYPE_WORK'),
			'EMAIL_HOME' => Loc::getMessage('CRM_KANBAN_EMAIL_TYPE_HOME'),
			'EMAIL_OTHER' => Loc::getMessage('CRM_KANBAN_EMAIL_TYPE_OTHER'),
			'PHONE_MOBILE' => Loc::getMessage('CRM_KANBAN_PHONE_TYPE_MOBILE'),
			'PHONE_WORK' => Loc::getMessage('CRM_KANBAN_PHONE_TYPE_WORK'),
			'PHONE_FAX' => Loc::getMessage('CRM_KANBAN_PHONE_TYPE_FAX'),
			'PHONE_HOME' => Loc::getMessage('CRM_KANBAN_PHONE_TYPE_HOME'),
			'PHONE_PAGER' => Loc::getMessage('CRM_KANBAN_PHONE_TYPE_PAGER'),
			'PHONE_OTHER' => Loc::getMessage('CRM_KANBAN_PHONE_TYPE_OTHER'),
		);
		$this->type = strtoupper(isset($this->arParams['ENTITY_TYPE']) ? $this->arParams['ENTITY_TYPE'] : '');
		if (!$this->type || !in_array($this->type, $this->types))
		{
			return false;
		}
		$this->arParams['ENTITY_TYPE_CHR'] = array_flip($this->types);
		$this->arParams['ENTITY_TYPE_CHR'] = strtoupper($this->arParams['ENTITY_TYPE_CHR'][$this->type]);
		//select
		$this->additionalSelect = array_keys((array)\CUserOptions::GetOption('crm', 'kanban_select_more_' . $this->type, array()));
		//redefine price-field
		if ($this->type != $this->types['quote'])
		{
			$slots = \Bitrix\Crm\Statistics\StatisticEntryManager::prepareSlotBingingData($this->type .  '_SUM_STATS');
			if (is_array($slots) && isset($slots['SLOT_BINDINGS']) && is_array($slots['SLOT_BINDINGS']))
			{
				foreach ($slots['SLOT_BINDINGS'] as $slot)
				{
					if ($slot['SLOT'] == 'SUM_TOTAL')
					{
						$this->fieldSum = $slot['FIELD'];
						break;
					}
				}
			}
		}
		//init arParams
		if (!isset($this->arParams['ADDITIONAL_FILTER']) || !is_array($this->arParams['ADDITIONAL_FILTER']))
		{
			$this->arParams['ADDITIONAL_FILTER'] = array();
		}
		if (isset($this->arParams['PAGE']) && $this->arParams['PAGE'] > 1)
		{
			$this->blockPage = intval($this->arParams['PAGE']);
		}
		if (!isset($this->arParams['ONLY_COLUMNS']) || $this->arParams['ONLY_COLUMNS'] != 'Y')
		{
			$this->arParams['ONLY_COLUMNS'] = 'N';
		}
		if (!isset($this->arParams['IS_AJAX']) || $this->arParams['IS_AJAX'] != 'Y')
		{
			$this->arParams['IS_AJAX'] = 'N';
		}
		if (!isset($this->arParams['GET_AVATARS']) || $this->arParams['GET_AVATARS'] != 'Y')
		{
			$this->arParams['GET_AVATARS'] = 'N';
		}
		if (!isset($this->arParams['EXTRA']) || !is_array($this->arParams['EXTRA']))
		{
			$this->arParams['EXTRA'] = array();
		}
		if ($this->type == $this->types['deal'])
		{
			$this->statusKey = 'STAGE_ID';
		}

		$this->winPeriodKey = '>' . ($this->type == $this->types['invoice'] ? 'DATE_UPDATE' : 'DATE_MODIFY');
		$this->uid = \CCrmSecurityHelper::GetCurrentUserID();
		$this->currency = $this->arParams['CURRENCY'] = \CCrmCurrency::GetAccountCurrencyID();

		return true;
	}

	/**
	 * Set error for template.
	 * @param string $error
	 */
	protected function setError($error)
	{
		$this->arResult['ERROR'] = $error;
	}

	/**
	 * Get all CRM statuses, stages, etc.
	 * @return array
	 */
	protected function getStatuses()
	{
		static $statuses = null;

		if ($statuses !== null)
		{
			return $statuses;
		}

		$statuses = array();
		$type = $this->type;
		$types = $this->types;
		$filter = $this->getFilter();
		$semantic = \CCrmStatus::GetEntityTypes();

		//colors
		$colors = array(
			'QUOTE_STATUS' => ($type == $types['quote']) ? (array)unserialize(\Bitrix\Main\Config\Option::get('crm', 'CONFIG_STATUS_QUOTE_STATUS')) : array(),
			'INVOICE_STATUS' => ($type == $types['invoice']) ? (array)unserialize(\Bitrix\Main\Config\Option::get('crm', 'CONFIG_STATUS_INVOICE_STATUS')) : array(),
			'STATUS' => ($type == $types['lead']) ? (array)unserialize(\Bitrix\Main\Config\Option::get('crm', 'CONFIG_STATUS_STATUS')) : array(),
		);
		if ($type == $types['deal'] && isset($filter['CATEGORY_ID']) && $filter['CATEGORY_ID']>0)
		{
			foreach (\Bitrix\Crm\Category\DealCategory::getList(array('filter' => array('ID' => $filter['CATEGORY_ID'])))->fetchAll() as $cat)
			{
				$colors['DEAL_STAGE_' . $cat['ID']] = (array)unserialize(\Bitrix\Main\Config\Option::get('crm', 'CONFIG_STATUS_DEAL_STAGE_' . $cat['ID']));
			}
		}
		elseif ($type == $types['deal'])
		{
			$colors['DEAL_STAGE'] = (array)unserialize(\Bitrix\Main\Config\Option::get('crm', 'CONFIG_STATUS_DEAL_STAGE'));
		}

		//custom statuses
		$custom = array_keys($colors);
		$custom[] = 'DEAL_TYPE';
		$custom[] = 'SOURCE';
		$custom = array_flip($custom);
		foreach ($custom as $code => &$value)
		{
			$res = \GetModuleEvents('crm', 'OnCrmStatusGetList');
			while ($row = $res->fetch())
			{
				$result = executeModuleEventEx($row, array($code));
				if (!empty($result))
				{
					$value = $result;
					break;
				}
			}
			if (empty($value) || !is_array($value))
			{
				unset($custom[$code]);
			}
		}
		unset($value);

		//common get
		$db = array();
		$res = \CCrmStatus::GetList(array('SORT' => 'ASC'));
		while ($row = $res->fetch())
		{
			if (!isset($custom[$row['ENTITY_ID']]))
			{
				$db[] = $row;
			}
		}
		foreach ($custom as $code => $value)
		{
			$db = array_merge($db, $value);
		}
		foreach ($db as $row)
		{
			$row['NAME'] = htmlspecialcharsbx($row['NAME']);
			$row['STATUS_ID'] = htmlspecialcharsbx($row['STATUS_ID']);
			if (in_array($row['ENTITY_ID'], array('DEAL_TYPE', 'SOURCE')))
			{
				if ($row['ENTITY_ID'] == 'DEAL_TYPE')
				{
					$row['ENTITY_ID'] = 'TYPE_ID';
				}
				elseif ($row['ENTITY_ID'] == 'SOURCE')
				{
					$row['ENTITY_ID'] = 'SOURCE_ID';
				}
				if (!isset($this->additionalTypes[$row['ENTITY_ID']]))
				{
					$this->additionalTypes[$row['ENTITY_ID']] = array();
				}
				$this->additionalTypes[$row['ENTITY_ID']][$row['STATUS_ID']] = $row['NAME'];
				continue;
			}
			if (!isset($colors[$row['ENTITY_ID']]) || empty($colors[$row['ENTITY_ID']]))
			{
				continue;
			}
			if (!isset($statuses[$row['ENTITY_ID']]))
			{
				$statuses[$row['ENTITY_ID']] = array();
			}
			$row['COLOR'] = isset($colors[$row['ENTITY_ID']][$row['STATUS_ID']]) &&
							isset($colors[$row['ENTITY_ID']][$row['STATUS_ID']]['COLOR'])
							? htmlspecialcharsbx($colors[$row['ENTITY_ID']][$row['STATUS_ID']]['COLOR'])
							: '';
			if (isset($semantic[$row['ENTITY_ID']]) && isset($semantic[$row['ENTITY_ID']]['SEMANTIC_INFO'])
				&&
				(
					!isset($semantic[$row['ENTITY_ID']]['SEMANTIC_INFO']['FINAL_SORT']) ||
					$semantic[$row['ENTITY_ID']]['SEMANTIC_INFO']['FINAL_SORT'] == 0
				)
				&&
				(
					isset($semantic[$row['ENTITY_ID']]['SEMANTIC_INFO']['FINAL_SUCCESS_FIELD']) ||
					isset($semantic[$row['ENTITY_ID']]['SEMANTIC_INFO']['FINAL_UNSUCCESS_FIELD'])
				)
				&&
				(
					$semantic[$row['ENTITY_ID']]['SEMANTIC_INFO']['FINAL_SUCCESS_FIELD'] == $row['STATUS_ID'] ||
					$semantic[$row['ENTITY_ID']]['SEMANTIC_INFO']['FINAL_UNSUCCESS_FIELD'] == $row['STATUS_ID']
				)
			)
			{
				$semantic[$row['ENTITY_ID']]['SEMANTIC_INFO']['FINAL_SORT'] = $row['SORT'];
			}
			$statuses[$row['ENTITY_ID']][$row['STATUS_ID']] = $row;
		}

		//invoice stored in other arc
		if ($type == $types['invoice'])
		{
			if (isset($custom['INVOICE_STATUS']))
			{
				$invoiceStatuses = $custom['INVOICE_STATUS'];
			}
			else
			{
				$invoiceStatuses = \CCrmStatusInvoice::GetList(array('SORT' => 'ASC'), array());
			}
			foreach ($invoiceStatuses as $row)
			{
				$row['NAME'] = htmlspecialcharsbx($row['NAME']);
				$row['STATUS_ID'] = htmlspecialcharsbx($row['STATUS_ID']);
				if (!isset($statuses[$row['ENTITY_ID']]))
				{
					$statuses[$row['ENTITY_ID']] = array();
				}
				$row['COLOR'] = isset($colors[$row['ENTITY_ID']]) && isset($colors[$row['ENTITY_ID']][$row['STATUS_ID']])
								&& isset($colors[$row['ENTITY_ID']][$row['STATUS_ID']]['COLOR'])
								? htmlspecialcharsbx($colors[$row['ENTITY_ID']][$row['STATUS_ID']]['COLOR'])
								: '';
				if (isset($semantic[$row['ENTITY_ID']]) && isset($semantic[$row['ENTITY_ID']]['SEMANTIC_INFO'])
					&&
					(
						!isset($semantic[$row['ENTITY_ID']]['SEMANTIC_INFO']['FINAL_SORT']) ||
						$semantic[$row['ENTITY_ID']]['SEMANTIC_INFO']['FINAL_SORT'] == 0
					)
					&&
					(
						$semantic[$row['ENTITY_ID']]['SEMANTIC_INFO']['FINAL_SUCCESS_FIELD'] ||
						$semantic[$row['ENTITY_ID']]['SEMANTIC_INFO']['FINAL_UNSUCCESS_FIELD']
					)
					&&
					(
						$semantic[$row['ENTITY_ID']]['SEMANTIC_INFO']['FINAL_SUCCESS_FIELD'] == $row['STATUS_ID'] ||
						$semantic[$row['ENTITY_ID']]['SEMANTIC_INFO']['FINAL_UNSUCCESS_FIELD'] == $row['STATUS_ID']
					)
				)
				{
					$semantic[$row['ENTITY_ID']]['SEMANTIC_INFO']['FINAL_SORT'] = $row['SORT'];
				}
				$statuses[$row['ENTITY_ID']][$row['STATUS_ID']] = $row;
			}
		}

		//range statuses
		foreach ($statuses as $id => &$entity)
		{
			$finalSort = isset($semantic[$id]) && isset($semantic[$id]['SEMANTIC_INFO'])
						&& $semantic[$id]['SEMANTIC_INFO']['FINAL_SORT']
						? $semantic[$id]['SEMANTIC_INFO']['FINAL_SORT']
						: 0;
			foreach ($entity as &$status)
			{
				if ($finalSort == $status['SORT'])
				{
					$status['PROGRESS_TYPE'] = 'WIN';
				}
				elseif ($finalSort < $status['SORT'])
				{
					$status['PROGRESS_TYPE'] = 'LOOSE';
				}
				else
				{
					$status['PROGRESS_TYPE'] = 'PROGRESS';
				}
			}
			unset($status);
		}
		unset($entity);

		foreach ($statuses as $status)
		{
			if (!empty($status))
			{
				$statuses = $status;
			}
		}

		return $statuses;
	}

	/**
	 * Make select from presets.
	 * @return array
	 */
	protected function getSelect()
	{
		static $select = null;

		if ($select === null)
		{
			$types = array_flip($this->types);
			$select = $this->selectPresets[$types[$this->type]];

			if (!empty($this->additionalSelect))
			{
				$select = array_merge($select, $this->additionalSelect);
			}
			if ($this->fieldSum != '')
			{
				$select[] = $this->fieldSum;
			}
		}

		return $select;
	}

	/**
	 * Make filter from env.
	 * @return array
	 */
	protected function getFilter()
	{
		static $filter = null;

		if ($filter === null)
		{
			$filter = array();
			//from main.filter
			$grid = \Bitrix\Crm\Kanban\Helper::getGrid($this->type);
			$gridFilter = \Bitrix\Crm\Kanban\Helper::getFilter($this->type);
			$search = (array)$grid->GetFilter($gridFilter);
			\Bitrix\Crm\UI\Filter\EntityHandler::internalize($gridFilter, $search);
			if (!isset($search['FILTER_APPLIED']))
			{
				$search = array();
			}
			if (!empty($search))
			{
				foreach ($gridFilter as $key => $item)
				{
					//fill filter by type
					if ($item['type'] == 'date')
					{
						if (isset($search[$key . '_from']) && $search[$key . '_from']!='')
						{
							$filter['>='.$key] = $search[$key . '_from'] . ' 00:00:00';
							$filter['FLT_DATE_EXIST'] = 'Y';
						}
						if (isset($search[$key . '_to']) && $search[$key . '_to']!='')
						{
							$filter['<='.$key] = $search[$key . '_to'] . ' 23:59:00';
							$filter['FLT_DATE_EXIST'] = 'Y';
						}
					}
					elseif ($item['type'] == 'number')
					{
						if (isset($search[$key . '_from']) && $search[$key . '_from'] != '')
						{
							$filter['>'.$key] = $search[$key . '_from'];
						}
						if (isset($search[$key . '_to']) && $search[$key . '_to'] != '')
						{
							$filter['<'.$key] = $search[$key . '_to'];
						}
					}
					elseif (isset($search[$key]))
					{
						if (isset($gridFilter[$key]['flt_key']))
						{
							if (strpos($gridFilter[$key]['flt_key'], '#'))
							{
								list($k, $type) = explode('#', $gridFilter[$key]['flt_key']);
								$filter[$k] = array(
									array(
										'TYPE_ID' => $type,
										'=%VALUE' => $search[$key]
									)
								);
							}
							else
							{
								$filter[$gridFilter[$key]['flt_key']] = $search[$key];
							}
						}
						else
						{
							$filter[$key] = $search[$key];
						}
					}
				}
				//search index
				if (isset($search['FIND']) && trim($search['FIND']) != '')
				{
					$search['FIND'] = trim($search['FIND']);
					if ($this->type == $this->types['invoice'])
					{
						$filter['%ORDER_TOPIC'] = $search['FIND'];
					}
					else
					{
						$filter['SEARCH_CONTENT'] = $search['FIND'];
					}
				}
			}
			//has phone or email
			if (isset($filter['HAS_PHONE']) && $filter['HAS_PHONE'] != 'Y')
			{
				unset($filter['HAS_PHONE']);
			}
			if (isset($filter['HAS_EMAIL']) && $filter['HAS_EMAIL'] != 'Y')
			{
				unset($filter['HAS_EMAIL']);
			}
			if (isset($filter['COMMUNICATION_TYPE']))
			{
				if (!is_array($filter['COMMUNICATION_TYPE']))
				{
					$filter['COMMUNICATION_TYPE'] = array($filter['COMMUNICATION_TYPE']);
				}
				if (in_array(\CCrmFieldMulti::PHONE, $filter['COMMUNICATION_TYPE']))
				{
					$filter['HAS_PHONE'] = 'Y';
				}
				if (in_array(\CCrmFieldMulti::EMAIL, $filter['COMMUNICATION_TYPE']))
				{
					$filter['HAS_EMAIL'] = 'Y';
				}
				unset($filter['COMMUNICATION_TYPE']);
			}
			//overdue
			if (
				isset($filter['OVERDUE']) &&
				(
					$this->type == $this->types['quote'] ||
					$this->type == $this->types['invoice']
				)
			)
			{
				$key = $this->type == $this->types['quote'] ? 'CLOSEDATE' : 'DATE_PAY_BEFORE';
				$date = new \Bitrix\Main\Type\Date;
				if ($filter['OVERDUE'] == 'Y')
				{
					$filter['<='.$key] = $date;
				}
				else
				{
					$filter['>='.$key] = $date;
				}
				unset($filter['OVERDUE']);
			}
			//counters
			if (isset($filter['ACTIVITY_COUNTER']) && ($this->type == $this->types['lead'] || $this->type == $this->types['deal']))
			{
				$filter['@ID'] = array();
				$filterCounter = null;
				$counterResolve = \Bitrix\Crm\Counter\EntityCounterType::resolveName($filter['ACTIVITY_COUNTER']);
				$counterTypeID = \Bitrix\Crm\Counter\EntityCounterType::resolveID($counterResolve);
				if (\Bitrix\Crm\Counter\EntityCounterType::isDefined($counterTypeID))
				{
					try
					{
						$counter = \Bitrix\Crm\Counter\EntityCounterFactory::create(
							$this->type == $this->types['lead'] ? \CCrmOwnerType::Lead : \CCrmOwnerType::Deal,
							$counterTypeID,
							$this->uid,
							\Bitrix\Crm\Counter\EntityCounter::internalizeExtras(array('counter' => $counterResolve))
						);
						$filterCounter = $counter->prepareEntityListFilter(
									array(
										'MASTER_ALIAS' => $this->type == $this->types['lead'] ? \CCrmLead::TABLE_ALIAS : \CCrmDeal::TABLE_ALIAS,
										'MASTER_IDENTITY' => 'ID'
									)
								);
					}
					catch(Bitrix\Main\NotSupportedException $e)
					{
					}
					catch(Bitrix\Main\ArgumentException $e)
					{
					}
				}
				if (is_array($filterCounter) && !empty($filterCounter))
				{
					$res = CCrmActivity::GetEntityList(
						$this->type == $this->types['lead'] ? \CCrmOwnerType::Lead : \CCrmOwnerType::Deal,
						$this->uid,
						'asc',
						$filterCounter
					);
					while ($row = $res->fetch())
					{
						$filter['@ID'][] = $row['ID'];
					}
					if (!empty($filter['@ID']))
					{
						$filter['FLT_DATE_EXIST'] = 'Y';
					}
				}
			}
			//deal
			if ($this->type == $this->types['deal'] && isset($this->arParams['EXTRA']['CATEGORY_ID']))
			{
				$filter['CATEGORY_ID'] = $this->arParams['EXTRA']['CATEGORY_ID'];
			}
			//invoice
			if ($this->type == $this->types['invoice'])
			{
				$filter['!IS_RECURRING'] = 'Y';
			}
			//not loose
			$filter['!' . $this->statusKey] = array();
			foreach ($this->getStatuses() as $status)
			{
				if ($status['PROGRESS_TYPE'] == 'LOOSE')
				{
					$filter['!' . $this->statusKey][] = $status['STATUS_ID'];
				}
			}
		}

		return $filter;
	}

	/**
	 * Get path for entity from params or module settings.
	 * @param string $type
	 * @return string
	 */
	protected function getEntityPath($type)
	{
		$params = $this->arParams;

		$pathKey = 'PATH_TO_'.strtoupper($type).'_SHOW';
		$url = !array_key_exists($pathKey, $params) ? \CrmCheckPath($pathKey, '', '') : $params[$pathKey];

		return $url;
	}

	/**
	 * Get multi-fields for entity (phone, email, etc).
	 * @param array $items
	 * @param string $contragent
	 * @return filled array
	 */
	protected function fillFMfields(array $items, $contragent)
	{
		$isOneElement = false;
		$isOLinstalled = IsModuleInstalled('imopenlines');

		if (!empty($items))
		{
			if (isset($items['ID']))
			{
				$isOneElement = true;
				$items = array($items['ID'] => $items);
			}
			$res = \CCrmFieldMulti::GetListEx(array(), array(
															'ENTITY_ID' => $contragent,
															'ELEMENT_ID' => array_keys($items)));
			while ($row = $res->fetch())
			{
				$row['TYPE_ID'] = strtolower($row['TYPE_ID']);
				if (!in_array($row['TYPE_ID'], $this->allowedFMtypes))
				{
					continue;
				}
				if ($row['TYPE_ID'] == 'im' && (strpos($row['VALUE'], 'imol|') !== 0 || !$isOLinstalled))
				{
					continue;
				}
				if (!isset($items[$row['ELEMENT_ID']]['FM']))
				{
					$items[$row['ELEMENT_ID']]['FM'] = array();
					$items[$row['ELEMENT_ID']]['FM_VALUES'] = array();
				}
				if (!isset($items[$row['ELEMENT_ID']]['FM'][$row['TYPE_ID']]))
				{
					$items[$row['ELEMENT_ID']]['FM'][$row['TYPE_ID']] = array();
					$items[$row['ELEMENT_ID']]['FM_VALUES'][$row['TYPE_ID']] = array();
				}
				$items[$row['ELEMENT_ID']]['FM'][$row['TYPE_ID']][] = $row;
				$items[$row['ELEMENT_ID']]['FM_VALUES'][$row['TYPE_ID']][] = array(
					'value' => htmlspecialcharsbx($row['VALUE']),
					'title' => $this->fmTypes[$row['COMPLEX_ID']]
				);
			}
		}

		return $isOneElement ? array_pop($items) : $items;
	}

	/**
	 * Companies or contacts.
	 * @param string $contragent
	 * @return array
	 */
	protected function getContragents($contragent)
	{
		$items = array();

		$path = $this->getEntityPath($contragent);

		$contragent = strtolower(trim($contragent));
		$provider = '\CCrm'.$contragent;
		if (class_exists($provider) && isset($this->{$contragent}) && !empty($this->{$contragent}))
		{
			$select = array('ID', 'NAME', 'LAST_NAME', 'TITLE');
			$res = $provider::getListEx(array(), array('ID' => array_unique($this->{$contragent})), false, false, $select);
			while ($row = $res->fetch())
			{
				if (!array_key_exists('TITLE', $row) && array_key_exists('NAME', $row) && array_key_exists('LAST_NAME', $row))
				{
					$row['TITLE'] = trim($row['NAME'] . ' ' . $row['LAST_NAME']);
				}
				$row['TITLE'] = htmlspecialcharsbx($row['TITLE']);
				$row['URL'] = str_replace($this->pathMarkers, $row['ID'], $path);
				$items[$row['ID']] = $row;
			}
		}

		$items = $this->fillFMfields($items, $contragent);

		return $items;
	}

	/**
	 * Get columns.
	 * @param boolean $clear Clear static var.
	 * @return array
	 */
	protected function getColumns($clear = false)
	{
		static $columns = array();

		if ($clear)
		{
			$columns = array();
		}

		if (empty($columns))
		{
			$baseCurrency = $this->currency;
			$baseFilter = $this->getFilter();
			$filter = $baseFilter;
			$types = $this->types;
			$statusCode = $this->statusKey;
			$sort = 0;

			foreach ($this->getStatuses() as $status)
			{
				$sort += 100;
				if ($status['COLOR'] == '')
				{

					if ($status['PROGRESS_TYPE'] == 'WIN')
					{
						$status['COLOR'] = \CCrmViewHelper::SUCCESS_COLOR;
					}
					elseif ($status['PROGRESS_TYPE'] == 'LOOSE')
					{
						$status['COLOR'] = \CCrmViewHelper::FAILURE_COLOR;
					}
					else
					{
						$status['COLOR'] = \CCrmViewHelper::PROCESS_COLOR;
					}
				}
				$columns[$status['STATUS_ID']] = array(
					'id' => $status['STATUS_ID'],
					'name' => $status['NAME'],
					'color' => strpos($status['COLOR'], '#')===0 ? substr($status['COLOR'], 1) : $status['COLOR'],
					'type' => $status['PROGRESS_TYPE'],
					'sort' => $sort,
					'count' => 0,
					'total' => 0,
					'currency' => $baseCurrency
				);
			}

			//get sums and counts

			if ($this->type == $types['invoice'])
			{
				$baseFilter['!IS_RECURRING'] = 'Y';
				$stats = array();
				$provider = '\CCrmInvoice';
				$res = \CCrmInvoice::GetList(array(), $baseFilter,
											array('STATUS_ID', 'SUM' => 'PRICE'), false,
											array('STATUS_ID', 'PRICE')
						);
				while ($row = $res->fetch())
				{
					$stats[] = $row;
				}
			}
			else
			{
				$provider = '\CCrm'.$this->type;
				if (class_exists($provider))
				{
					$filter[$statusCode] = array_keys($columns);
					if (method_exists($provider, 'getListEx'))
					{
						$res = $provider::GetListEx(array(), $filter,
													array($statusCode, 'SUM' => 'OPPORTUNITY_ACCOUNT'), false,
													array($statusCode, 'OPPORTUNITY_ACCOUNT'));
					}
					else
					{
						$res = $provider::GetList(array(), $filter,
													array($statusCode, 'SUM' => 'OPPORTUNITY_ACCOUNT'), false,
													array($statusCode, 'OPPORTUNITY_ACCOUNT'));
					}
					while ($row = $res->fetch())
					{
						$stats[] = $row;
					}
				}
			}

			if ($stats)
			{
				foreach ($stats as $stat)
				{
					if (isset($columns[$stat[$statusCode]]))
					{
						//change data for win column
						if ($columns[$stat[$statusCode]]['type'] == 'WIN')
						{
							if (!isset($filter['FLT_DATE_EXIST']) || $filter['FLT_DATE_EXIST'] != 'Y')
							{
								$winPeriod = $this->getWinPeriod();
								$filter[$this->winPeriodKey] = $winPeriod;
							}
							$filter[$statusCode] = $columns[$stat[$statusCode]]['id'];
							if (method_exists($provider, 'getListEx'))
							{
								$res = $provider::GetListEx(array(), $filter,
															array($statusCode, 'SUM' => 'OPPORTUNITY_ACCOUNT'), false,
															array($statusCode, 'OPPORTUNITY_ACCOUNT'));
							}
							elseif ($this->type == $types['invoice'])
							{
								$filter['!IS_RECURRING'] = 'Y';
								$res = $provider::GetList(array(), $filter,
															array($statusCode, 'SUM' => 'PRICE'), false,
															array($statusCode, 'CURRENCY'));
							}
							else
							{
								$res = $provider::GetList(array(), $filter,
															array($statusCode, 'SUM' => 'OPPORTUNITY_ACCOUNT'), false,
															array($statusCode, 'OPPORTUNITY_ACCOUNT'));
							}
							if (!($stat = $res->fetch()))
							{
								continue;
							}
						}
						//fill column
						if ($this->type == $types['invoice'])
						{
							$columns[$stat[$statusCode]]['count'] = $stat['CNT'];
							$columns[$stat[$statusCode]]['total'] = $stat['PRICE'];
							$columns[$stat[$statusCode]]['total_format'] = \CCrmCurrency::MoneyToString(round($stat['PRICE']), $baseCurrency);
						}
						else
						{
							$columns[$stat[$statusCode]]['count'] = $stat['CNT'];
							$columns[$stat[$statusCode]]['total'] = $stat['OPPORTUNITY_ACCOUNT'];
							$columns[$stat[$statusCode]]['total_format'] = \CCrmCurrency::MoneyToString(round($stat['OPPORTUNITY_ACCOUNT']), $baseCurrency);
						}
					}
				}
			}
		}

		return $columns;
	}

	/**
	 * Insert new key/value in the array after the key.
	 * @param array $array Array for change.
	 * @param mixed $afterKey Insert after this key. If null then the value insert in beginning of the array.
	 * @param mixed $newKey New key.
	 * @param mixed $newValue New value.
	 * @return array
	 */
	protected function arrayInsertAfter(array $array, $afterKey, $newKey, $newValue)
	{
		if ($afterKey === null)
		{
			return array($newKey => $newValue) + $array;
		}
		if ($afterKey === null || array_key_exists ($afterKey, $array))
		{
			$newArray = array();
			foreach ($array as $k => $value)
			{
				$newArray[$k] = $value;
				if ($k == $afterKey)
				{
					$newArray[$newKey] = $newValue;
				}
			}
			return $newArray;
		}
		else
		{
			return $array;
		}
}

	/**
	 * Sort items by user order.
	 * @param array $items Items for sort.
	 * @return array
	 */
	protected function sort(array $items)
	{
		$sort = \Bitrix\Crm\Kanban\SortTable::getPrevious(array(
			'ENTITY_TYPE_ID' => $this->type,
			'ENTITY_ID' => array_keys($items),
		));
		if (!empty($sort))
		{
			foreach ($sort as $id => $prev)
			{
				if ($prev>0 && !isset($items[$prev]))
				{
					continue;
				}
				$moveItem = $items[$id];
				unset($items[$id]);
				if ($prev == 0)
				{
					$prev = null;
				}
				$items = $this->arrayInsertAfter($items, $prev, $id, $moveItem);
			}
		}
		return $items;
	}

	/**
	 * Base method for getting data.
	 * @param array $addFilter
	 * @return array
	 */
	protected function getItems(array $filter = array(), $pagen = true)
	{
		static $path = null;
		static $currency = null;
		static $columns = null;

		$result = array();
		$type = $this->type;
		$types = $this->types;
		$provider = '\CCrm'.$type;
		$method = method_exists($provider, 'getListEx') ? 'getListEx' : 'getList';
		$select = $this->getSelect();
		$filter = array_merge($this->getFilter(), $filter);
		$addFields = $this->getAdditionalFields();
		$addSelect = $this->additionalSelect;
		$addTypes = $this->additionalTypes;
		$navParams = $pagen ? array('iNumPage' => $this->blockPage, 'nPageSize' => $this->blockSize) : false;
		$statusKey = $this->statusKey;

		if ($path === null)
		{
			$path = $this->getEntityPath($type);
		}
		if ($currency === null)
		{
			$currency = $this->currency;
		}
		if ($columns === null)
		{
			$columns = $this->getColumns();
		}
		if (class_exists($provider))
		{
			//unset date filter for win column, if filter was set
			if (isset($filter['FLT_DATE_EXIST']) && $filter['FLT_DATE_EXIST'] == 'Y' && isset($filter[$this->winPeriodKey]))
			{
				unset($filter[$this->winPeriodKey], $filter['FLT_DATE_EXIST']);
			}
			//user sorting
			if (
				isset($filter[$statusKey]) && isset($columns[$filter[$statusKey]]) &&
				$columns[$filter[$statusKey]]['count']<=$this->maxSortSize
			)
			{
				//get all and sort
				$sorting = true;
				$db = array();
				$res = $provider::$method(array('ID' => 'DESC'), $filter, false, false, $select);
				while ($row = $res->fetch())
				{
					$db[$row['ID']] = $row;
				}
				$db = $this->sort($db);
				//init query
				$res = new CDBResult;
				$res->initFromArray($db);
				$res->navStart($this->blockSize, false, $this->blockPage);
			}
			else
			{
				$sorting = false;
				$res = $provider::$method(array('ID' => 'DESC'), $filter, false, $navParams, $select);
			}
			$sort = 1;
			$timeOffset = time() + \CTimeZone::GetOffset();
			$pageCount = $res->NavPageCount;
			while ($row = $res->fetch())
			{
				$row['FORMAT_TIME'] = true;
				//base
				if (isset($row['MODIFY_BY_ID']))
				{
					$this->modifyUsers[$row['ID']] = $row['MODIFY_BY_ID'];
				}
				if ($type == $types['lead'])
				{
					$row['PRICE'] = $row['OPPORTUNITY'];
					$row['DATE'] = $row['DATE_CREATE'];
				}
				elseif ($type == $types['deal'])
				{
					$row['PRICE'] = $row['OPPORTUNITY'];
					if ($row['BEGINDATE'])
					{
						$row['FORMAT_TIME'] = false;
						$row['DATE'] = $row['BEGINDATE'];
					}
					else
					{
						$row['DATE'] = $row['DATE_CREATE'];
					}
				}
				elseif ($type == $types['quote'])
				{
					$row['PRICE'] = $row['OPPORTUNITY'];
					if ($row['BEGINDATE'])
					{
						$row['FORMAT_TIME'] = false;
						$row['DATE'] = $row['BEGINDATE'];
					}
					else
					{
						$row['DATE'] = $row['DATE_CREATE'];
					}
				}
				elseif ($type == $types['invoice'])
				{
					$row['TITLE'] = $row['ORDER_TOPIC'];
					$row['PRICE'] = $row['PRICE'];
					$row['FORMAT_TIME'] = false;
					$row['DATE'] = $row['PAY_VOUCHER_DATE'] ? $row['PAY_VOUCHER_DATE'] : $row['DATE_BILL'];
					$row['CONTACT_ID'] = $row['UF_CONTACT_ID'];
					$row['COMPANY_ID'] = $row['UF_COMPANY_ID'];
					$row['CURRENCY_ID'] = $row['CURRENCY'];
				}
				//redefine price
				if ($this->fieldSum && array_key_exists($this->fieldSum, $row))
				{
					$row['PRICE'] = $row[$this->fieldSum];
				}
				elseif (isset($row['OPPORTUNITY_ACCOUNT']) && $row['OPPORTUNITY_ACCOUNT']!='')
				{
					$row['PRICE'] = $row['OPPORTUNITY_ACCOUNT'];
				}
				if (isset($row['ACCOUNT_CURRENCY_ID']) && $row['ACCOUNT_CURRENCY_ID']!='')
				{
					$row['CURRENCY_ID'] = $row['ACCOUNT_CURRENCY_ID'];
				}
				//contragent
				if ($row['CONTACT_ID'] > 0)
				{
					$row['CONTACT_TYPE'] = 'CRM_CONTACT';
					$this->contact[$row['ID']] = $row['CONTACT_ID'];
				}
				elseif ($row['COMPANY_ID'] > 0)
				{
					$row['CONTACT_TYPE'] = 'CRM_COMPANY';
					$row['CONTACT_ID'] = $row['COMPANY_ID'];
					$this->company[$row['ID']] = $row['COMPANY_ID'];
				}
				else
				{
					$row['CONTACT_TYPE'] = '';
				}
				//additional fields
				$fields = array();
				foreach ($addSelect as $code)
				{
					if (array_key_exists($code, $row) && array_key_exists($code, $addFields))
					{
						if (isset($addTypes[$code]) && isset($addTypes[$code][$row[$code]]))
						{
							$row[$code] = $addTypes[$code][$row[$code]];
						}
						if (!empty($row[$code]))
						{
							if ($addFields[$code]['type'] == 'enumeration')
							{
								$row[$code] = implode(', ', array_intersect_key($addFields[$code]['enumerations'], array_flip($row[$code])));
							}
							$fields[] = array(
								'code' => $code,
								'title' => $addFields[$code]['title'],
								'value' => htmlspecialcharsbx($row[$code])
							);
						}
					}
				}
				//price converted
				if ($row['CURRENCY_ID']=='' || $row['CURRENCY_ID'] == $currency)
				{
					$row['PRICE'] = doubleval($row['PRICE']);
					$row['PRICE_FORMATTED'] = \CCrmCurrency::MoneyToString($row['PRICE'], $currency);
				}
				else
				{
					$row['PRICE'] = \CCrmCurrency::ConvertMoney($row['PRICE'], $row['CURRENCY_ID'], $currency);
					$row['PRICE_FORMATTED'] = \CCrmCurrency::MoneyToString($row['PRICE'], $currency);
				}
				$row['DATE_UNIX'] = \makeTimeStamp($row['DATE']);
				//add
				$result[$row['ID']] = array(
					'id' =>  $row['ID'],
					'name' => htmlspecialcharsbx($row['TITLE'] != '' ? $row['TITLE'] : '#' . $row['ID']),
					'link' => str_replace($this->pathMarkers, $row['ID'], $path),
					'columnId' => $columnId = htmlspecialcharsbx($row[$this->statusKey]),
					'columnColor' => isset($columns[$columnId]) ? $columns[$columnId]['color'] : '',
					'price' => $row['PRICE'],
					'price_formatted' => $row['PRICE_FORMATTED'],
					'date' => (
								!$row['FORMAT_TIME']
								? \FormatDate('j M Y', $row['DATE_UNIX'], $timeOffset)
								: (
									(time() - $row['DATE_UNIX']) / 3600 > 48
									? \FormatDate('j M Y, H:i', $row['DATE_UNIX'], $timeOffset)
									: \FormatDate('x', $row['DATE_UNIX'], $timeOffset)
								)
							),
					'contactId' => (int)$row['CONTACT_ID'],
					'contactType' => $row['CONTACT_TYPE'],
					'modifyById' => isset($row['MODIFY_BY_ID']) ? $row['MODIFY_BY_ID'] : 0,
					'modifyByAvatar' => '',
					'activityShow' => 1,
					'activityProgress' => 0,
					'activityTotal' => 0,
					'page' => $this->blockPage,
					'pageCount' => $pageCount,
					'fields' => $fields,
				);
			}
			if (!$sorting)
			{
				$result = $this->sort($result);
			}
		}

		return $result;
	}

	/**
	 * Get all activities by id's and type of entity.
	 * @param array $activity id's
	 * @return array
	 */
	protected function getActivityCounters($activity)
	{
		if (empty($activity))
		{
			return array();
		}

		$return = array();
		$activity = array_unique($activity);

		//make filter
		$filter = array(
			'BINDINGS' => array(),
			'RESPONSIBLE_ID' => $this->uid
		);
		$typeId = \CCrmOwnerType::ResolveID($this->type);
		foreach ($activity as $id)
		{
			$filter['BINDINGS'][] = array(
				'OWNER_ID' => $id,
				'OWNER_TYPE_ID' => $typeId,
			);
		}

		$select = array();
		$navParams = false;
		$res = \CCrmActivity::GetList(array(), $filter, array('COMPLETED', 'OWNER_ID'), $navParams, $select);
		while ($row = $res->fetch())
		{
			if (!isset($return[$row['OWNER_ID']]))
			{
				$return[$row['OWNER_ID']] = array();
			}
			$return[$row['OWNER_ID']][$row['COMPLETED']] = $row['CNT'];
		}

		return $return;
	}

	/**
	 * Get additional fields for more-button.
	 * @return array
	 */
	protected function getAdditionalFields()
	{
		static $additional = null;

		if ($additional === null)
		{
			$type = $this->type;
			$types = $this->types;
			$additional = array();
			$exist = $this->additionalSelect;

			//base fields
			if ($type == $types['lead'])
			{
				$startFields = array('SOURCE_ID', 'BIRTHDATE');
			}
			elseif ($type == $types['deal'])
			{
				$startFields = array('TYPE_ID');
			}
			else
			{
				$startFields = array();
			}
			foreach ($startFields as $code)
			{
				$additional[$code] = array(
					'title' => Loc::getMessage('CRM_KANBAN_SELECT_' . $code),
					'new' => !in_array($code, $exist) ? 1 : 0,
					'type' => 'string',
					'code' => $code
				);
			}

			//user fields
			$enumerations = array();
			$res = \CUserTypeEntity::GetList(array('SORT' => 'ASC', 'NAME' => 'ASC'),
											array('ENTITY_ID' => 'CRM_' . $type, 'LANG' => LANGUAGE_ID));
			while ($row = $res->fetch())
			{
				if (in_array($row['USER_TYPE_ID'], $this->allowedUFtypes))
				{
					$additional[$row['FIELD_NAME']] = array(
						'title' => htmlspecialcharsbx($row['EDIT_FORM_LABEL']),
						'new' => !in_array($row['FIELD_NAME'], $exist) ? 1 : 0,
						'type' => $row['USER_TYPE_ID'],
						'code' => $row['FIELD_NAME'],
						'enumerations' => array()
					);
					if ($row['USER_TYPE_ID'] == 'enumeration')
					{
						$enumerations[$row['ID']] = $row['FIELD_NAME'];
					}
				}
			}
			if (!empty($enumerations))
			{
				$enumUF = new CUserFieldEnum;
				$resEnum = $enumUF->GetList(array(), array('USER_FIELD_ID' => array_keys($enumerations)));
				while ($rowEnum = $resEnum->fetch())
				{
					$additional[$enumerations[$rowEnum['USER_FIELD_ID']]]['enumerations'][$rowEnum['ID']] = $rowEnum['VALUE'];
				}
			}
		}

		return $additional;
	}

	/**
	 * Make some actions (set, update, etc).
	 */
	protected function makeAction()
	{
		$context = \Bitrix\Main\Application::getInstance()->getContext();
		$request = $context->getRequest();
		$type = $this->type;
		$types = $this->types;
		$provider = '\CCrm'.$this->type;

		//set/unset more fields
		if (($setField = $request->get('set_field')) || ($delField = $request->get('del_field')))
		{
			$available = $this->getAdditionalFields();
			$selectFields = (array)\CUserOptions::GetOption('crm', 'kanban_select_more_' . $this->type, array());
			if ($setField)
			{
				$selectFields[$setField] = 1;
			}
			elseif (isset($selectFields[$delField]))
			{
				unset($selectFields[$delField]);
			}
			\CUserOptions::SetOption('crm', 'kanban_select_more_' . $this->type, $selectFields);

			if ($this->arParams['IS_AJAX'] != 'Y')
			{
				$uri = new \Bitrix\Main\Web\Uri($request->getRequestUri());
				\LocalRedirect($uri->deleteParams(array('set_field', 'del_field'))->getUri());
			}
		}

		//update fields
		if (($action = $request->get('action')) && ($id = $request->get('entity_id')) && check_bitrix_sessid())
		{
			$statuses = $this->getStatuses();
			//change status / stage
			if ($action == 'status' && ($status = $request->get('status')) && isset($statuses[$status]))
			{
				$entity = new $provider(false);
				$userPerms = \CCrmPerms::GetCurrentUserPermissions();
				if (!\CCrmPerms::IsAuthorized())
				{
					$this->setError(Loc::getMessage('CRM_KANBAN_ERROR_ACCESS_DENIED'));
				}
				elseif (!($row = $entity->getById($id)))
				{
					$this->setError(Loc::getMessage('CRM_KANBAN_ERROR_ACCESS_DENIED'));
				}
				elseif (!$provider::CheckUpdatePermission($id, $userPerms))
				{
					$this->setError(Loc::getMessage('CRM_KANBAN_ERROR_ACCESS_DENIED'));
				}
				else
				{
					$statusKey = $this->statusKey;
					$newStateParams = (array)$request->get('status_params');
					//add one more item for old column
					if ($row[$statusKey] != $status && isset($newStateParams['old_status_lastid']))
					{
						$oneMore = $this->getItems(array(
							$statusKey => $row[$statusKey],
							'<ID' => $newStateParams['old_status_lastid'],
							'!ID' => $id
						));
						if (count($oneMore) > 1)
						{
							$oneMore = array_shift($oneMore);
							$oneMore = array(
								$oneMore['id'] => $oneMore
							);
						}
						$this->items += $oneMore;
					}
					//change state
					$skipUpdate = false;
					if ($type == $types['invoice'])
					{
						$statusParams = array();
						$statusParams['REASON_MARKED'] = isset($newStateParams['comment']) ? $newStateParams['comment'] : '';
						$statusParams[$status == 'P' ? 'PAY_VOUCHER_DATE' : 'DATE_MARKED'] = isset($newStateParams['date']) ? $newStateParams['date'] : '';
						$statusParams['PAY_VOUCHER_NUM'] = isset($newStateParams['docnum']) ? $newStateParams['docnum'] : '';
						$entity->SetStatus($id, $status, $statusParams, array('SYNCHRONIZE_LIVE_FEED' => true));
					}
					else
					{
						//if lead, check status
						if ($type == $types['lead'] && $row[$statusKey] != $status)
						{
							if ($statuses[$row[$statusKey]]['PROGRESS_TYPE'] != 'PROGRESS')
							{
								$skipUpdate = true;
								$this->setError(Loc::getMessage('CRM_KANBAN_ERROR_LEAD_ALREADY_CONVERTED'));
							}
							elseif ($statuses[$status]['PROGRESS_TYPE'] == 'WIN')
							{
								$skipUpdate = true;
							}
						}
						//update
						if ($row[$statusKey] != $status && !$skipUpdate)
						{
							$fields = array($statusKey => $status);
							$entity->Update($id, $fields, true, true, array('DISABLE_USER_FIELD_CHECK' => true, 'REGISTER_SONET_EVENT' => true));
							if (!$entity->LAST_ERROR && ($type == $types['lead'] || $type == $types['deal']))
							{
								$errors = array();
								\CCrmBizProcHelper::AutoStartWorkflows(
									($type == $types['lead']) ? \CCrmOwnerType::Lead : \CCrmOwnerType::Deal,
									$id, \CCrmBizProcEventType::Edit, $errors
								);
								\Bitrix\Crm\Automation\Factory::runOnStatusChanged(
									($type == $types['lead']) ? \CCrmOwnerType::Lead : \CCrmOwnerType::Deal,
									$id
								);
							}
							elseif (!$entity->LAST_ERROR)
							{
								$this->setError($entity->LAST_ERROR);
							}
						}
					}
					if (!$skipUpdate)
					{
						//change sort
						\Bitrix\Crm\Kanban\SortTable::setPrevious(array(
							'ENTITY_TYPE_ID' => $type,
							'ENTITY_ID' => $id,
							'PREV_ENTITY_ID' => $request->get('prev_entity_id')
						));
						//clear columns
						$this->getColumns(true);
					}
				}
			}

			if ($this->arParams['IS_AJAX'] != 'Y')
			{
				$uri = new \Bitrix\Main\Web\Uri($request->getRequestUri());
				\LocalRedirect($uri->deleteParams(array('action', 'entity_id', 'status'))->getUri());
			}
		}

		//subscribe / unsunbscribe
		$supervisor = $request->get('supervisor');
		if ($request->get('apply_filter') == 'Y')
		{
			if (\Bitrix\Crm\Kanban\SupervisorTable::isSupervisor($this->type))
			{
				$supervisor = 'N';
			}
		}
		if ($supervisor)
		{
			\Bitrix\Crm\Kanban\SupervisorTable::set($this->type, $supervisor=='Y');
			$uri = new \Bitrix\Main\Web\Uri($request->getRequestUri());
			\LocalRedirect($uri->deleteParams(array('supervisor', 'clear_filter'))->getUri());
		}
	}

	/**
	 * Get period for win column.
	 * @return \Bitrix\Main\Type\DateTime
	 */
	protected function getWinPeriod()
	{
		static $winPeriod = null;
		if ($winPeriod === null)
		{
			$winPeriod = new \Bitrix\Main\Type\DateTime;
			$winPeriod->add('-'.date('G').' hours')->add('-'.date('i').' minutes');
		}

		return $winPeriod;
	}

	/**
	 * Base executable method.
	 */
	public function executeComponent()
	{
		if (!$this->init())
		{
			return;
		}

		$this->makeAction();
		$type = $this->type;
		$types = $this->types;
		$winPeriod = $this->getWinPeriod();

		//check other perms (additional for lead converting)
		$crmPerms = new \CCrmPerms($this->uid);
		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		$this->arResult['ACCESS_CONFIG_PERMS'] = $crmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
		if ($type == $types['lead'])
		{
			$this->arResult['CAN_CONVERT_TO_CONTACT'] = \CCrmContact::CheckCreatePermission($userPermissions);
			$this->arResult['CAN_CONVERT_TO_COMPANY'] = \CCrmCompany::CheckCreatePermission($userPermissions);
			$this->arResult['CAN_CONVERT_TO_DEAL'] = \CCrmDeal::CheckCreatePermission($userPermissions);
			$this->arResult['CONVERSION_CONFIG'] = \Bitrix\Crm\Conversion\DealConversionConfig::load();
			if ($this->arResult['CONVERSION_CONFIG'] === null)
			{
				$this->arResult['CONVERSION_CONFIG'] = \Bitrix\Crm\Conversion\DealConversionConfig::getDefault();
			}
		}

		//output
		if ($this->arParams['ONLY_COLUMNS'] == 'Y')
		{
			$this->arResult['ITEMS'] = array(
				'items' => array_values($this->items),
				'columns' => array_values($this->getColumns())
			);
		}
		else
		{
			$items = array();
			$columns = $this->getColumns();
			if (!empty($this->arParams['ADDITIONAL_FILTER']))
			{
				$filter = $this->arParams['ADDITIONAL_FILTER'];
				if (isset($filter['COLUMN']))
				{
					if (isset($columns[$filter['COLUMN']]) && $columns[$filter['COLUMN']]['type'] == 'WIN')
					{
						$filter[$this->winPeriodKey] = $winPeriod;
					}
					$filter[$this->statusKey] = $filter['COLUMN'];
					unset($filter['COLUMN']);
				}
				$items = $this->getItems($filter);
			}
			else
			{
				foreach ($columns as $k => $column)
				{
					if ($column['type'] != 'LOOSE')
					{
						$filter = array();
						$filter[$this->statusKey] = $column['id'];
						if ($column['type'] == 'WIN')
						{
							$filter[$this->winPeriodKey] = $winPeriod;
						}
						$items += $this->getItems($filter);
					}
				}
			}
			//get avatars
			if ($this->arParams['GET_AVATARS'] == 'Y' && !empty($this->modifyUsers))
			{
				$users = array();
				$res = \Bitrix\Main\UserTable::getList(array(
					'select' => array('ID', 'PERSONAL_PHOTO'),
					'filter' => array('ID' => array_values($this->modifyUsers))
				));
				while ($row = $res->fetch())
				{
					if ($row['PERSONAL_PHOTO'])
					{
						$row['PERSONAL_PHOTO'] = \CFile::ResizeImageGet($row['PERSONAL_PHOTO'], $this->avatarSize, BX_RESIZE_IMAGE_EXACT);
					}
					$users[$row['ID']] = $row;
				}
				foreach ($items as &$item)
				{
					if ($users[$item['modifyById']]['PERSONAL_PHOTO'])
					{
						$item['modifyByAvatar'] = $users[$item['modifyById']]['PERSONAL_PHOTO']['src'];
					}
				}
				unset($item);
			}

			$this->arResult['ITEMS'] = array(
				'columns' => array_values($columns),
				'items' => $items
			);
		}

		if ($this->arParams['ONLY_COLUMNS'] == 'N')
		{
			$this->arResult['MORE_FIELDS'] = array_values($this->getAdditionalFields());
			$contacts = $this->getContragents('contact');
			$companies = $this->getContragents('company');
			if ($type == $types['lead'])
			{
				$this->arResult['ITEMS']['items'] = $this->fillFMfields($this->arResult['ITEMS']['items'], 'lead');
			}

			//set contragents to items
			if (!empty($this->arResult['ITEMS']['items']))
			{
				foreach ($this->arResult['ITEMS']['items'] as &$item)
				{
					if ($item['contactId'] > 0 && $item['contactType'] == 'CRM_CONTACT' && isset($contacts[$item['contactId']]))
					{
						$contragent = $contacts[$item['contactId']];
					}
					elseif ($item['contactId'] > 0 && $item['contactType'] == 'CRM_COMPANY' && isset($companies[$item['contactId']]))
					{
						$contragent = $companies[$item['contactId']];
					}
					else
					{
						$contragent = array();
					}
					if (!empty($contragent))
					{
						$item['contactName'] = $contragent['TITLE'];
						$item['contactLink'] = $contragent['URL'];
					}
					//phone, email, chat
					if (isset($contragent['FM_VALUES']) && !empty($contragent['FM_VALUES']))
					{
						foreach ($contragent['FM_VALUES'] as $code => $values)
						{
							$item[$code] = ($code == 'im') ? $values[0] : $values;
						}
					}
					//same from leads
					if (isset($item['FM_VALUES']))
					{
						foreach ($item['FM_VALUES'] as $code => $values)
						{
							$item[$code] = ($code == 'im') ? $values[0] : $values;
						}
						unset($item['FM_VALUES'], $item['FM']);
					}
				}
				unset($item);
			}

			//get activity
			if (!empty($this->arResult['ITEMS']['items']))
			{
				if ($type == $types['deal'] || $type == $types['lead'])
				{
					foreach ($this->getActivityCounters(array_keys($this->arResult['ITEMS']['items'])) as $id => $actCC)
					{
						$this->arResult['ITEMS']['items'][$id]['activityProgress'] = isset($actCC['N']) ? $actCC['N'] : 0;
						$this->arResult['ITEMS']['items'][$id]['activityTotal'] = isset($actCC['Y']) ? $actCC['Y'] : 0;
					}
				}

				$this->arResult['ITEMS']['items'] = array_values($this->arResult['ITEMS']['items']);
			}
		}

		if ($this->arParams['IS_AJAX'] == 'Y')
		{
			return $this->arResult;
		}
		else
		{
			$GLOBALS['APPLICATION']->setTitle(Loc::getMessage('CRM_KANBAN_TITLE2_' . $this->type));
			$this->IncludeComponentTemplate();
		}
	}
}