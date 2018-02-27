<?
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage sale
 * @copyright 2001-2015 Bitrix
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Context;
use \Bitrix\Main\Config\Option;

use \Bitrix\Tasks\Util\Error\Collection;
use \Bitrix\Tasks\Manager;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksTaskListComponent extends TasksBaseComponent
{
	protected $users2Get = 		array();
	protected $groups2Get = 	array();
	protected $exportAs = 		false;

	private $listStateInstance = 		null;
	private $listParameters = 			array();
	private $userFields = 				array();

	const STATE_OP_SET = 		's';
	const STATE_OP_UNSET = 		'u';

	const STATE_TYPE_FILTER = 	'F';
	const STATE_TYPE_VIEW = 	'V';

	/**
	 * Function checks if required modules installed. Also check for available features
	 * @throws Exception
	 * @return void
	 */
	protected static function checkRequiredModules(array $arParams, array &$arResult, Collection $errors, array $auxParams = array())
	{
		if(!Loader::includeModule('socialnetwork'))
		{
			$errors->add('SOCIALNETWORK_MODULE_NOT_INSTALLED', Loc::getMessage("TASKS_TL_SOCIALNETWORK_MODULE_NOT_INSTALLED"));
		}

		if(!Loader::includeModule('forum'))
		{
			$errors->add('FORUM_MODULE_NOT_INSTALLED', Loc::getMessage("TASKS_TL_FORUM_MODULE_NOT_INSTALLED"));
		}

		return $errors->checkNoFatals();
	}

	/**
	 * Check parameters both in plain and ajax modes
	 */
	protected static function checkBasicParameters(array &$arParams, array &$arResult, Collection $errors, array $auxParams = array())
	{
		static::tryParseIntegerParameter($arParams['GROUP_ID'], 0); // GROUP_ID > 0 indicates we display this component inside a socnet group

		return $errors->checkNoFatals();
	}

	/**
	 * Function checks if user have basic permissions to launch the component
	 * @throws Exception
	 * @return void
	 */
	protected static function checkPermissions(array &$arParams, array &$arResult, Collection $errors, array $auxParams = array())
	{
		parent::checkPermissions($arParams, $arResult, $errors, $auxParams);

		// check group access here
		if ($arParams["GROUP_ID"] > 0)
		{
			// can we see all tasks in this group?
			$featurePerms = CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, array($arParams['GROUP_ID']), 'tasks', 'view_all');

			$canViewGroup = is_array($featurePerms) && isset($featurePerms[$arParams['GROUP_ID']]) && $featurePerms[$arParams['GROUP_ID']];

			if(!$canViewGroup)
			{
				// okay, can we see at least our own tasks in this group?
				$featurePerms = CSocNetFeaturesPerms::CurrentUserCanPerformOperation(SONET_ENTITY_GROUP, array($arParams['GROUP_ID']), 'tasks', 'view');
				$canViewGroup = is_array($featurePerms) && isset($featurePerms[$arParams['GROUP_ID']]) && $featurePerms[$arParams['GROUP_ID']];
			}

			if(!$canViewGroup)
			{
				$errors->add('ACCESS_TO_GROUP_DENIED', Loc::getMessage('TASKS_TL_ACCESS_TO_GROUP_DENIED'));
			}
		}

		return $errors->checkNoFatals();
	}

	protected function checkParameters()
	{
		parent::checkParameters();

		$arParams =& $this->arParams;

		static::tryParseIntegerParameter($arParams['FORUM_ID'], 0); // forum id to keep comments in
		if($arParams['FORUM_ID'])
		{
			__checkForum($arParams["FORUM_ID"]);
		}

		static::tryParseIntegerParameter($arParams['USER_ID'], $this->userId); // allows to see other user`s tasks, if have permissions

		$this->exportAs = $this->getRequestParameter('EXPORT_AS');
		if($this->exportAs !== false)
		{
			$arParams['USE_PAGINATION'] = false;
			$arParams['PAGINATION_PAGE_SIZE'] = 0;
		}
		else
		{
			static::tryParseBooleanParameter($arParams['USE_PAGINATION'], true); // enable or disable CDResult-driven page navigation in this component
			static::tryParseNonNegativeIntegerParameter($arParams['PAGINATION_PAGE_SIZE'], 10); // lines-on-page amount
		}

		// low-level parameters to pass to fetchList
		static::tryParseArrayParameter($arParams['FILTER']); // filter paramters that will be used directly, not from query or user option

		static::tryParseArrayParameter($arParams['COLUMNS']); // force columns to display
		static::tryParseArrayParameter($arParams['ORDER']); // set inital order by columns
		static::tryParseBooleanParameter($arParams['DO_PREORDER'], true); // 

		// PATH_* params here?
		// $arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
	}

	protected function doPreActions()
	{
		$this->userFields = static::getUserFields();

		$this->arResult['MODE']['USER_FIELD_SORT_FILTER'] = Option::get('tasks', 'task_list_uf_sort_filter') == 'Y';

		$this->setListState();

		return true;
	}

	// get some data and decide what goes to arResult
	protected function getData()
	{
		$parameters = array(
			'ERRORS' => 			$this->errors
		);
		$mgrResult = Manager\Task::getList($this->userId, $this->listParameters, $parameters);

		if($this->errors->checkHasFatals())
		{
			return;
		}

		$this->arResult['DATA']['TASK'] = $mgrResult['DATA'];
		$this->arResult['CAN']['TASK'] = $mgrResult['CAN'];

		$this->groups2Get = array_merge($this->groups2Get, $mgrResult['AUX']['GROUP_IDS']);

		// doubtful
		if(is_object($mgrResult['AUX']['OBJ_RES']))
		{
			//$this->arResult['COMPONENT_DATA']['NAV_STRING'] = $mgrResult['AUX']['OBJ_RES']->GetPageNavString(Loc::getMessage('TASKS_TL_TITLE_TASKS'), 'arrows');
			$this->arResult['COMPONENT_DATA']['NAV_PARAMS'] = $mgrResult['AUX']['OBJ_RES']->getNavParams();
		}

		$this->arResult['AUX_DATA']['COMPANY_WORKTIME'] = static::getCompanyWorkTime();
		$this->arResult['AUX_DATA']['USER_FIELDS'] = $this->userFields;
	}

	protected function getReferenceData()
	{
		$this->arResult['DATA']['GROUPS'] = static::getGroupsData($this->groups2Get);
	}

	protected function doPostActions()
	{
		parent::doPostActions();

		$this->arResult['COMPONENT_DATA']['STATE'] = $this->getListState();
		$this->arResult['COMPONENT_DATA']['LIST_PARAMS'] = $this->listParameters;

		return true;
	}

	protected function display()
	{
		if($this->exportAs)
		{
			$GLOBALS['APPLICATION']->RestartBuffer();

			$this->IncludeComponentTemplate('export_'.$this->exportAs);

			CMain::FinalActions(); // to make onEpilog() events work
			die();
		}
		elseif ($this->arResult['COMPONENT_DATA']['STATE']['VIEWS']['VIEW_MODE_GANTT']['SELECTED'] == 'Y')
		{
			// gantt promo
			//CUserOptions::SetOption('tasks', 'gant_performed', true);

			$this->IncludeComponentTemplate('gantt');
		}
		else
		{
			$this->IncludeComponentTemplate();
		}
	}

	// private functions that may be removed lately

	private final function setListState()
	{
		$this->listStateInstance = 	CTaskListState::getInstance($this->userId);
		$request = 					Context::getCurrent()->getRequest();

		static::applyViewStateSwitches($this->getRequestParameter('STATE'), $this->listStateInstance);
		$this->listParameters = $this->getListParameters(); // get list parameters: cached, passed in query or defined as default

		$this->listStateInstance->saveState(); // to db
	}

	private final function getListState()
	{
		// gantt promo
//		$ganttPerformed = CUserOptions::GetOption('tasks', 'gant_performed');
//		if(!$ganttPerformed)
//		{
//			$this->listStateInstance->setViewMode(CTaskListState::VIEW_MODE_GANTT);
//			$this->listStateInstance->saveState();
//		}

		$state = $this->listStateInstance->getState();
		$state['COLUMNS'] = $this->getColumns();

		return $state;
	}

	private final function getColumns()
	{
		static $columns;

		if($columns === null)
		{
			$columns = CTaskColumnList::get(array(
				'USER_FIELDS' => $this->userFields
			));

			$columnManagerInstance = new CTaskColumnManager(CTaskColumnPresetManager::getInstance($this->userId, $this->getColumnContextId()));

			if(!empty($this->arParams['COLUMNS']))
			{
				$presetColumns = $this->arParams['COLUMNS'];
			}
			else
			{
				$presetColumns = $columnManagerInstance->getCurrentPresetColumns();
			}

			if(is_array($presetColumns) && !empty($presetColumns))
			{
				$i = 0;
				foreach($presetColumns as $presetColumn)
				{
					$columns[$presetColumn['ID']]['DISPLAY_DATA'] = array(
						'WIDTH' => intval($presetColumn['WIDTH']),
						'ORDER' => $i++
					);
				}
			}
		}

		return $columns;
	}

	private final static function applyViewStateSwitches($states, $listStateInstance)
	{
		if(is_array($states))
		{
			sort($states);

			foreach($states as $state)
			{
				$state = self::decodeState($state);

				if($state['TYPE'] != self::STATE_TYPE_VIEW)
				{
					continue;
				}

				try
				{
					switch ($state['FIELD'])
					{
						case 'V':	// view
							if($state['OP'] == self::STATE_OP_SET)
							{
								$listStateInstance->setViewMode($value);
							}
						break;

						case 'S':	// submode
							if($state['OP'] == self::STATE_OP_SET)
							{
								$listStateInstance->switchOnSubmode($value);
							}
							elseif($state['OP'] == self::STATE_OP_UNSET)
							{
								$listStateInstance->switchOffSubmode($value);
							}
						break;
					}
				}
				catch (TasksException $e)
				{
					// bad input?
				}
			}
		}
	}

	private final static function applyFilterStateSwitches($states, $listStateInstance, $filterInstance)
	{
		if(is_array($states))
		{
			rsort($states);

			foreach($states as $state)
			{
				$state = self::decodeState($state);

				if($state['TYPE'] != self::STATE_TYPE_FILTER)
				{
					continue;
				}

				//_print_r($state);

				try
				{
					switch ($state['FIELD'])
					{
						case 'X':	// reset filter
							$listStateInstance->resetState();
							//filterInstance reset TOO
						break;

						case 'S':	// set section. if the section is "ROLES", set default role and default category
							$listStateInstance->setSection($state['VALUE']);

							if($state['VALUE'] == CTaskListState::VIEW_SECTION_ROLES)
							{
								$listStateInstance->setUserRole(CTaskListState::VIEW_ROLE_RESPONSIBLE);
								$listStateInstance->setTaskCategory(CTaskListState::VIEW_TASK_CATEGORY_IN_PROGRESS);
							}
							else
							{
							}
						break;

						case 'P':	// set filter preset
							$filterInstance->switchFilterPreset($state['VALUE']);
						break;

						case 'R':	// set role and default category
							$listStateInstance->setUserRole($state['VALUE']);
							$listStateInstance->setTaskCategory(CTaskListState::VIEW_TASK_CATEGORY_IN_PROGRESS);
						break;

						case 'C':	// set category
							$listStateInstance->setTaskCategory($state['VALUE']);
						break;
					}
				}
				catch (TasksException $e)
				{
					// bad input?
				}
			}
		}
	}

	public static function makeStateUrl(array $state = array(), $reset = false, $url = '/')
	{
		$params = array();

		if($reset)
		{
			$params['STATE[0]'] = self::encodeState(array(
				'TYPE' => self::STATE_TYPE_FILTER,
				'FIELD' => 'X',
			));
		}

		if((string) $state['SECTION'] != '')
		{
			$params['STATE[1]'] = self::encodeState(array(
				'TYPE' => self::STATE_TYPE_FILTER,
				'FIELD' => 'S',
				'VALUE' => $state['SECTION'],
				'OPERATION' => self::STATE_OP_SET
			));
		}

		if((string) $state['ROLE'] != '')
		{
			$params['STATE[2]'] = self::encodeState(array(
				'TYPE' => self::STATE_TYPE_FILTER,
				'FIELD' => 'R',
				'VALUE' => $state['ROLE'],
				'OPERATION' => self::STATE_OP_SET
			));
		}

		if((string) $state['CATEGORY'] != '')
		{
			$params['STATE[3]'] = self::encodeState(array(
				'TYPE' => self::STATE_TYPE_FILTER,
				'FIELD' => 'C',
				'VALUE' => $state['CATEGORY'],
				'OPERATION' => self::STATE_OP_SET
			));
		}

		if((string) $state['PRESET'] != '')
		{
			$params['STATE[4]'] = self::encodeState(array(
				'TYPE' => self::STATE_TYPE_FILTER,
				'FIELD' => 'P',
				'VALUE' => $state['PRESET'],
				'OPERATION' => self::STATE_OP_SET
			));
		}

		if((string) $state['VIEW'] != '')
		{
			$params['STATE[5]'] = self::encodeState(array(
				'TYPE' => self::STATE_TYPE_VIEW,
				'FIELD' => 'V',
				'VALUE' => $state['VIEW'],
				'OPERATION' => self::STATE_OP_SET
			));
		}

		$i = 6;
		if(is_array($state['SUBMODES']))
		{
			foreach($state['SUBMODES'] as $mode => $flag)
			{
				if((string) $state['VIEW'] != '')
				{
					$params['STATE['.$i.']'] = self::encodeState(array(
						'TYPE' => self::STATE_TYPE_VIEW,
						'FIELD' => 'S',
						'VALUE' => $mode,
						'OPERATION' => $flag ? self::STATE_OP_SET : self::STATE_OP_UNSET
					));
					$i++;
				}
			}
		}

		return CHTTP::urlAddParams($url, $params, array('skip_empty' => true, 'encode' => false));
	}

	/**
	 * Return filter, order and select for an old-style getList. 
	 * This function is used ONLY when you call this component NOT in AJAX mode.
	 */
	private final function getListParameters()
	{
		$parameters = array(
			'LIST_STATE_INSTANCE' => $this->listInstance,
			'USER_ID' => $this->userId,
			'GROUP_ID' => $this->arParams['GROUP_ID']
		);

		$filter = (
			!empty($this->arParams['FILTER']) ?
			$this->arParams['FILTER'] :
			static::getFilterByRequest(
				array(
					'STATE' => $this->getRequestParameter('STATE'),
					'FILTER' => $this->getRequestParameter('FILTER'),
				),
				$parameters
			)
		);

		if(!$this->arResult['MODE']['USER_FIELD_SORT_FILTER'])
		{
			static::stripFilterByUserFields($filter, $this->userFields, $this->errors);
		}

		return array(
			'FILTER' => 		$filter,
			'ORDER' => 			$this->getOrder(),
			'SELECT' => 		$this->getSelect(),
			'NAV_PARAMS' => 	$this->getNavParams(),
		);
	}

	protected static function stripFilterByUserFields(array &$filter, array $userFields, Collection $errors)
	{
		if(!empty($userFields) && !empty($filter))
		{
			$ufNames = array_keys($userFields);
			static::walkFilterStripUserFields($filter, $ufNames, $errors);
		}
	}

	private static function walkFilterStripUserFields(array &$level, $ufNames, Collection $errors)
	{
		foreach($level as $k => &$v)
		{
			if(strpos((string) $k, 'UF_') !== false)
			{
				foreach($ufNames as $uf)
				{
					if(strpos($k, $uf) !== false)
					{
						$errors->add('USER_FIELD_NOT_ALLOWED', 'Condition in filter "'.$k.'" is not allowed', Collection::TYPE_WARNING, array('FIELD_NAME' => $uf));
						unset($level[$k]);
						break;
					}
				}
			}

			if(is_array($v))
			{
				static::walkFilterStripUserFields($v, $ufNames, $errors);
			}
		}
		unset($v);
	}

	/**
	 * Generates an old-style filter based on $_GET query
	 */
	private final static function getFilterByRequest($request, array &$parameters)
	{
		if(!is_object($parameters['LIST_STATE_INSTANCE']))
		{
			$parameters['LIST_STATE_INSTANCE'] = CTaskListState::getInstance($parameters['USER_ID']);
		}

		$listStateInstance = $parameters['LIST_STATE_INSTANCE'];

		$listInstance = CTaskListCtrl::getInstance($parameters['USER_ID']);
		$filterInstance = CTaskFilterCtrl::getInstance($parameters['USER_ID'], $parameters['GROUP_ID'] > 0);

		static::applyFilterStateSwitches($request['STATE'], $listStateInstance, $filterInstance);

		//_print_r('Section: '.CTaskListState::resolveConstantCodename($listStateInstance->getSection()));

		//static::makeAdvancedFilter();

		if($listStateInstance->getSection() == CTaskListState::VIEW_SECTION_ROLES)
		{
			$listInstance->useState($listStateInstance);
			$listInstance->setFilterByGroupId($parameters['GROUP_ID']);

			//_print_r('Role: '.CTaskListState::resolveConstantCodename($listStateInstance->getUserRole()));
			//_print_r('Category: '.CTaskListState::resolveConstantCodename($listStateInstance->getTaskCategory()));
		}
		elseif($listStateInstance->getSection() == CTaskListState::VIEW_SECTION_ADVANCED_FILTER)
		{
			$listInstance->useAdvancedFilterObject($filterInstance);

			//_print_r('Preset: '.$filterInstance->getSelectedFilterPresetId());
		}

		//_print_r('Result filter:');
		//_print_r(array_merge($listInstance->getFilter(), $listInstance->getCommonFilter()));

		// getCommonFilter contains filtering by GROUP_ID and submode-filter conditions (ONLY_ROOT_TASKS and SAME_GROUP_PARENT)
		$filter = array_merge($listInstance->getFilter(), $listInstance->getCommonFilter());

		return $filter;
	}

	private final function getOrder()
	{
		$columnsContextId = $this->getColumnContextId();

		$sortInOptions = CUserOptions::GetOption(
			'tasks:list:sort_multiple',
			'sort' . '_' . $columnsContextId,
			'none',
			$this->userId
		);

		// get clumns that can be sorted
		$sortable = array();
		$columns = $this->getColumns();
		if(is_array($columns))
		{
			foreach($columns as $column)
			{
				if($column['SORTABLE'])
				{
					$sortable[$column['DB_COLUMN']] = true;
				}
			}
		}

		$sort = $this->getRequestParameter('SORT');
		$order = array();
		if(is_array($sort)) // set from request
		{
			$order = static::parseOrder($order, $sortable, $this->errors);

			CUserOptions::SetOption(
				'tasks:list:sort_multiple',
				'sort' . '_' . $columnsContextId,
				$arOrderSerialized,
				false,
				$this->userId
			);
		}
		elseif(!empty($this->arParams["ORDER"]))
		{
			$order = static::parseOrder($this->arParams["ORDER"], $sortable, $this->errors);
		}
		elseif($sortInOptions  != 'none') // set from option
		{
			$order = unserialize($sortInOptions);
		}
		else // set default
		{
			$section = $this->listStateInstance->getSection();
			$category = $this->listStateInstance->getTaskCategory();

			if ($section == CTaskListState::VIEW_SECTION_ROLES && $category == CTaskListState::VIEW_TASK_CATEGORY_COMPLETED)
			{
				$order = array(
					'CLOSED_DATE' => 'DESC'
				);
			}
			else
			{
				$order = array(
					'DEADLINE' => 'ASC,NULLS',
					'STATUS'   => 'ASC',
					'PRIORITY' => 'DESC',
					'ID'       => 'DESC'
				);
			}
		}

		if($this->arParams['DO_PREORDER'])
		{
			// modify sort array to make sort by GROUP_ID and STATUS_COMPLETE first
			return array_merge(
				array('GROUP_ID' => 'ASC', 'STATUS_COMPLETE' => 'ASC'),
				$order
			);
		}
		else
		{
			return $order;
		}
	}

	protected static function parseOrder(array $sort, array $sortable, $errors)
	{
		$newOrder = array();
		foreach($sort as $fld => $way)
		{
			$fld = ToUpper($fld);
			$way = ToUpper($way);

			if($way != 'ASC' && $way != 'DESC' && $way != 'ASC,NULLS' && $way != 'DESC,NULLS')
			{
				$errors->add('UNKNOWN_SORT_DIRECTION', 'Unknown sort direction', Collection::TYPE_WARNING);
				continue;
			}
			if(!isset($sortable[$fld]))
			{
				$errors->add('UNKNOWN_SORT_FIELD', 'Unknown sort field', Collection::TYPE_WARNING);
				continue;
			}

			$newOrder[$fld] = $way;
		}

		return $newOrder;
	}

	private final function getSelect()
	{
		$select = array('*');
		$columns = $this->getColumns();

		// disable selecting UF_ when its not in selected column list
		if(is_array($columns) && is_array($this->userFields) && !empty($this->userFields))
		{
			$selected = array();
			foreach($columns as $column)
			{
				if($column['DISPLAY_DATA']) // means "selected"
				{
					$selected[$column['DB_COLUMN']] = true;
				}
			}

			$ufNames = array_keys($this->userFields);
			foreach($ufNames as $ufName)
			{
				if(isset($selected[$ufName]))
				{
					$select[] = $ufName;
				}
			}
		}

		return $select;
	}

	private final static function makeAdvancedFilter()
	{
		$arResult["ADVANCED_STATUSES"] = array(
			array("TITLE" => GetMessage("TASKS_FILTER_ALL"), "FILTER" => array()),
			array("TITLE" => GetMessage("TASKS_FILTER_ACTIVE"), "FILTER" => array("STATUS" => array(-2, -1, 1, 2, 3))),
			array("TITLE" => GetMessage("TASKS_FILTER_NEW"), "FILTER" => array("STATUS" => array(-2, 1))),
			array("TITLE" => GetMessage("TASKS_FILTER_IN_CONTROL"), "FILTER" => array("STATUS" => array(4, 7))),
			array("TITLE" => GetMessage("TASKS_FILTER_IN_PROGRESS"), "FILTER" => array("STATUS" => 3)),
			array("TITLE" => GetMessage("TASKS_FILTER_ACCEPTED"), "FILTER" => array("STATUS" => 2)),
			array("TITLE" => GetMessage("TASKS_FILTER_OVERDUE"), "FILTER" => array("STATUS" => -1)),
			array("TITLE" => GetMessage("TASKS_FILTER_DELAYED"), "FILTER" => array("STATUS" => 6)),
			array("TITLE" => GetMessage("TASKS_FILTER_DECLINED"), "FILTER" => array("STATUS" => 7)),
			array("TITLE" => GetMessage("TASKS_FILTER_CLOSED"), "FILTER" => array("STATUS" => array(4, 5)))
		);

		if ($taskType == "group" || $arParams["USER_ID"] == $USER->GetID())
		{
			$arResult["ROLE_FILTER_SUFFIX"] = "";
		}
		else
		{
			if ($arResult["USER"]["PERSONAL_GENDER"] == "F")
			{
				$arResult["ROLE_FILTER_SUFFIX"] = "_F";
			}
			else
			{
				$arResult["ROLE_FILTER_SUFFIX"] = "_M";
			}
		}

		$arPreDefindFilters = tasksPredefinedFilters($arParams["USER_ID"], $arResult["ROLE_FILTER_SUFFIX"]);

		$preDefinedFilterRole = &$arPreDefindFilters["ROLE"];
		$preDefinedFilterStatus = &$arPreDefindFilters["STATUS"][0];

		if (isset($arParams['COMMON_FILTER']))
			$arCommonFilter = $arParams['COMMON_FILTER'];
		else
			$arCommonFilter = $oListCtrl->getCommonFilter();

		if ($taskType == "group")
		{
			$preDefinedFilterRole[7]["FILTER"] = array();
		}

		if (isset($_GET["F_SEARCH"]))
		{
			if (is_numeric($_GET["F_SEARCH"]) && intval($_GET["F_SEARCH"]) > 0 && ($rsSearch = CTasks::GetByID(intval($_GET["F_SEARCH"]))) && $rsSearch->Fetch())
			{
				$_GET["F_META::ID_OR_NAME"] = intval($_GET["F_SEARCH"]);
			}
			elseif (strlen(trim($_GET["F_SEARCH"])))
			{
				$_GET["F_TITLE"] = $_GET["F_SEARCH"];
			}
			else
			{
				$_GET["F_ADVANCED"] = "N";
				$_SESSION["FILTER"] = array();
			}
		}

		if (
			(isset($_GET["F_CANCEL"]) && $_GET["F_CANCEL"] == "Y")
			|| ( ! isset($_GET["F_CANCEL"]) )
			|| isset($_GET["FILTERR"])
			|| isset($_GET["FILTERS"])
			|| (isset($_GET["F_ADVANCED"]) && $_GET["F_ADVANCED"] == "Y")
		)
		{
			$_SESSION["FILTER"] = array();
		}

		$arResult["ADV_FILTER"]["F_ADVANCED"] = $_SESSION["FILTER"]["F_ADVANCED"] = "Y";
		$arFilter = array();

		if (intval($fID = tasksGetFilter("F_META::ID_OR_NAME")) > 0)
		{
			$arFilter["META::ID_OR_NAME"] = $fID;
			$arResult["ADV_FILTER"]["F_META::ID_OR_NAME"] = $fID;
		}

		if (intval($fID = tasksGetFilter("F_ID")) > 0)
		{
			$arFilter["ID"] = $fID;
			$arResult["ADV_FILTER"]["F_ID"] = $fID;
		}

		if (strlen($fTitle = tasksGetFilter("F_TITLE")) > 0)
		{
			$arFilter["%TITLE"] = $fTitle;
			$arResult["ADV_FILTER"]["F_TITLE"] = $fTitle;
		}

		if (intval($fResponsible = tasksGetFilter("F_RESPONSIBLE")) > 0)
		{
			$arFilter["RESPONSIBLE_ID"] = $fResponsible;
			$arResult["ADV_FILTER"]["F_RESPONSIBLE"] = $fResponsible;
		}

		if (intval($fCreatedBy = tasksGetFilter("F_CREATED_BY")) > 0)
		{
			$arFilter["CREATED_BY"] = $fCreatedBy;
			$arResult["ADV_FILTER"]["F_CREATED_BY"] = $fCreatedBy;
		}

		if (intval($fAccomplice = tasksGetFilter("F_ACCOMPLICE")) > 0)
		{
			$arFilter["ACCOMPLICE"] = $fAccomplice;
			$arResult["ADV_FILTER"]["F_ACCOMPLICE"] = $fAccomplice;
		}

		if (intval($fAuditor = tasksGetFilter("F_AUDITOR")) > 0)
		{
			$arFilter["AUDITOR"] = $fAuditor;
			$arResult["ADV_FILTER"]["F_AUDITOR"] = $fAuditor;
		}

		if (strlen($fTags = tasksGetFilter("F_TAGS")) > 0)
		{
			$arFilter["TAG"] = array_map("trim", explode(",", $fTags));
			$arResult["ADV_FILTER"]["F_TAGS"] = $fTags;
		}

		if (strlen($fDateFrom = tasksGetFilter("F_DATE_FROM")) > 0)
		{
			$arFilter[">=CREATED_DATE"] = $fDateFrom;
			$arResult["ADV_FILTER"]["F_DATE_FROM"] = $fDateFrom;
		}

		if (strlen($fDateTo = tasksGetFilter("F_DATE_TO")) > 0)
		{
			$arFilter["<=CREATED_DATE"] = $fDateTo;
			$arResult["ADV_FILTER"]["F_DATE_TO"] = $fDateTo;
		}

		if (strlen($fClosedFrom = tasksGetFilter("F_CLOSED_FROM")) > 0)
		{
			$arFilter[">=CLOSED_DATE"] = $fClosedFrom;
			$arResult["ADV_FILTER"]["F_CLOSED_FROM"] = $fClosedFrom;
		}

		if (strlen($fClosedTo = tasksGetFilter("F_CLOSED_TO")) > 0)
		{
			$arFilter["<=CLOSED_DATE"] = $fClosedTo;
			$arResult["ADV_FILTER"]["F_CLOSED_TO"] = $fClosedTo;
		}

		if (strlen($fActiveFrom = tasksGetFilter("F_ACTIVE_FROM")) > 0)
		{
			$arFilter["ACTIVE"]["START"] = $fActiveFrom;
			$arResult["ADV_FILTER"]["F_ACTIVE_FROM"] = $fActiveFrom;
		}

		if (strlen($fActiveTo = tasksGetFilter("F_ACTIVE_TO")) > 0)
		{
			$arFilter["ACTIVE"]["END"] = $fActiveTo;
			$arResult["ADV_FILTER"]["F_ACTIVE_TO"] = $fActiveTo;
		}

		if (($fStatus = tasksGetFilter("F_STATUS")) && array_key_exists($fStatus, $arResult["ADVANCED_STATUSES"]) > 0)
		{
			$arFilter = array_merge($arFilter, $arResult["ADVANCED_STATUSES"][$fStatus]["FILTER"]);
			$arResult["ADV_FILTER"]["F_STATUS"] = $fStatus;
		}

		if ($_GET["F_SUBORDINATE"] == "Y")
		{
			$arResult["ADV_FILTER"]["F_SUBORDINATE"] = "Y";
			$arResult["ADV_FILTER"]["F_ANY_TASK"] = "N";

			// Don't set SUBORDINATE_TASKS for admin, it will cause all tasks to be showed
			if ( ! ($USER->IsAdmin() || CTasksTools::IsPortalB24Admin()) )
				$arFilter["SUBORDINATE_TASKS"] = "Y";
		}
		elseif ($_GET["F_ANY_TASK"] == "Y")
		{
			$arResult["ADV_FILTER"]["F_SUBORDINATE"] = "N";
			$arResult["ADV_FILTER"]["F_ANY_TASK"] = "Y";
		}
		else
		{
			$arFilter["MEMBER"] = $arParams["USER_ID"];
		}

		if ($_GET["F_MARKED"] == "Y")
		{
			$arResult["ADV_FILTER"]["F_MARKED"] = "Y";
			$arFilter["!MARK"] = false;
		}

		if ($_GET["F_OVERDUED"] == "Y")
		{
			$arResult["ADV_FILTER"]["F_OVERDUED"] = "Y";
			$arFilter["OVERDUED"] = "Y";
		}

		if ($_GET["F_IN_REPORT"] == "Y")
		{
			$arResult["ADV_FILTER"]["F_IN_REPORT"] = "Y";
			$arFilter["ADD_IN_REPORT"] = "Y";
		}

		if (intval($fGroupId = tasksGetFilter("F_GROUP_ID")) > 0 && $taskType != "group")
		{
			$arFilter["GROUP_ID"] = $fGroupId;
			$arResult["ADV_FILTER"]["F_GROUP_ID"] = $fGroupId;
		}
	}

	// TMP
	private static function tasksGetFilter($fieldName)
	{
		if (isset($_GET[$fieldName]))
		{
			$_SESSION[TASKS_FILTER_SESSION_INDEX][$fieldName] = $_GET[$fieldName];
		}

		return $_SESSION[TASKS_FILTER_SESSION_INDEX][$fieldName];
	}

	private final function getNavParams()
	{
		$navParams  = array();

		if($this->arParams['USE_PAGINATION'])
		{
			$navParams = array(
				'nPageSize' => $this->arParams['PAGINATION_PAGE_SIZE'],
				'bDescPageNumbering' => false,
				'NavShowAll'         => false,
				'bShowAll'           => false
			);
		}

		return $navParams;
	}



	private final function getColumnContextId()
	{
		$viewState = $this->listStateInstance->getState();

		if (isset($arParams['CONTEXT_ID']))
		{
			$columnsContextId = $arParams['CONTEXT_ID'];
		}
		else
		{
			switch ($viewState['SECTION_SELECTED']['ID'])
			{
				case CTaskListState::VIEW_SECTION_ROLES:
					switch ($viewState['ROLE_SELECTED']['ID'])
					{
						case CTaskListState::VIEW_ROLE_RESPONSIBLE:
							$columnsContextId = CTaskColumnContext::CONTEXT_RESPONSIBLE;
						break;

						case CTaskListState::VIEW_ROLE_ORIGINATOR:
							$columnsContextId = CTaskColumnContext::CONTEXT_ORIGINATOR;
						break;

						case CTaskListState::VIEW_ROLE_ACCOMPLICE:
							$columnsContextId = CTaskColumnContext::CONTEXT_ACCOMPLICE;
						break;

						case CTaskListState::VIEW_ROLE_AUDITOR:
							$columnsContextId = CTaskColumnContext::CONTEXT_AUDITOR;
						break;

						default:
							$columnsContextId = CTaskColumnContext::CONTEXT_ALL;
						break;
					}
				break;

				case CTaskListState::VIEW_SECTION_ADVANCED_FILTER:
				default:
					$columnsContextId = CTaskColumnContext::CONTEXT_ALL;
				break;
			}
		}

		return $columnsContextId;
	}

	////////////////////////////
	// Helper functions
	////////////////////////////

	private final static function decodeState($state)
	{
		$found = array();
		preg_match('#^([a-zA-Z]{1})([a-zA-Z]{1})([a-zA-Z]{1})(-?\d+)$#', $state, $found);

		if(empty($found))
		{
			return false;
		}

		$state = array(
			'TYPE' => $found[2],
			'FIELD' => $found[3],
			'OPERATION' => $found[1],
			'VALUE' => intval($found[4])
		);

		// do not convert preset values, it can contain '-'
		if($state['TYPE'] == self::STATE_TYPE_FILTER && $state['FIELD'] != 'P')
		{
			$state['VALUE'] = CTaskListState::decodeState($state['VALUE']);
		}

		return $state;
	}

	/**
	 * @param mixed[] $state
	 * 		<li> TYPE
	 * 		<li> FIELD
	 * 		<li> VALUE
	 * 		<li> OPERATION
	 */
	private final static function encodeState($state)
	{
		// do not convert preset values, it can contain '-'
		if($state['TYPE'] == self::STATE_TYPE_FILTER && $state['FIELD'] != 'P')
		{
			$state['VALUE'] = CTaskListState::encodeState($state['VALUE']);
		}

		return $state['OPERATION'].$state['TYPE'].$state['FIELD'].intval($state['VALUE']);
	}

	// "ajax-api" methods

	public static function getAllowedMethods()
	{
		return array(
			'setViewState'
		);
	}

	public static function setViewState(array $state)
	{
		$stateInstance = CTaskListState::getInstance(\Bitrix\Tasks\Util\User::getId());
		$stateInstance->setState($state);

		$stateInstance->saveState();

		return array();
	}
}

/*
Filter switching examples:

Roles, Auditor, Completed
/taskstasklist.php?STATE[]=sFS2000&STATE[]=sFRc00&STATE[]=sFX0&STATE[]=sFC40000&STATE[]=sVg0

Advanced
/taskstasklist.php?STATE[]=sFS4000

Advanced + preset
/taskstasklist.php?STATE[]=sFS4000&STATE[]=sFP-3

Roles, preset ignored
/taskstasklist.php?STATE[]=sFS2000&STATE[]=sFP-3
*/