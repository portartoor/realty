<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/**
 * @var array $arParams
 * @var array $arResult
 */
$arResult['ITEMS'] = null;
if (($arParams['SHOW_SECTIONS_BAR'] === 'Y') || ($arParams['SHOW_FILTER_BAR'] === 'Y') || ($arParams['SHOW_COUNTERS_BAR'] === 'Y'))
{
	$arResult['PATH_TEMPLATE'] = str_replace(array('#USER_ID#', '#user_id#'), '%USER_ID%', $arParams['PATH_TO_USER_TASKS']);

	$data = array(
		'ITEMS' => array()
	);

	$counterToRole = array();
	if(is_array($arResult['VIEW_STATE']))
	{
		foreach($arResult['VIEW_STATE']['ROLES'] as $roleCode => $item)
		{
			$parameters = array(
				'F_STATE[0]' => 'sR'.base_convert($item['ID'], 10, 32),
				'F_STATE[1]' => 'sC'.base_convert(CTaskListState::VIEW_TASK_CATEGORY_IN_PROGRESS, 10, 32),
			);
			if ($arResult['F_CREATED_BY'])
				$parameters["F_CREATED_BY"] = (int) $arResult['F_CREATED_BY'];
			if ($arResult['F_RESPONSIBLE_ID'])
				$parameters["F_RESPONSIBLE_ID"] = (int) $arResult['F_RESPONSIBLE_ID'];

			$data['ITEMS'][$roleCode] = array(
				'ID' => $item['ID'],
				'CODE' => $roleCode,
				'TITLE' => $item['TITLE'],
				'URL' => CHTTP::urlAddParams($arResult['PATH_TEMPLATE'], $parameters),
				'COUNTER' => (is_array($arResult['VIEW_COUNTERS']['ROLES'][$roleCode]['TOTAL']) ?
						$arResult['VIEW_COUNTERS']['ROLES'][$roleCode] :
						array(
							'TOTAL' => array(
								'VALUE' => 0,
								'PLURAL' => 2
							)
						))
				);

/*
 * VIEW_ROLE_RESPONSIBLE ->
 *  VIEW_TASK_CATEGORY_NEW
 *  VIEW_TASK_CATEGORY_WO_DEADLINE
 *  VIEW_TASK_CATEGORY_EXPIRED
 *  VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES
 * VIEW_ROLE_ACCOMPLICE
 *  VIEW_TASK_CATEGORY_NEW
 *  VIEW_TASK_CATEGORY_EXPIRED
 *  VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES
 * VIEW_ROLE_AUDITOR
 *  VIEW_TASK_CATEGORY_EXPIRED
 * VIEW_ROLE_ORIGINATOR
 *  VIEW_TASK_CATEGORY_EXPIRED
 *  VIEW_TASK_CATEGORY_WO_DEADLINE
 *  VIEW_TASK_CATEGORY_WAIT_CTRL

*/
			if (is_object($arResult["LIST_CTRL"]) &&
				/** @var \CTaskListCtrl $oListCtrl **/
				($oListCtrl = $arResult["LIST_CTRL"]))
			{
				switch($roleCode)
				{
					case 'VIEW_ROLE_RESPONSIBLE':
						$data['ITEMS'][$roleCode]["COUNTER"]['VIEW_TASK_CATEGORY_WO_DEADLINE'] = array(
							"ID" => CTaskListState::VIEW_TASK_CATEGORY_WO_DEADLINE,
							"VALUE" => $oListCtrl->getCounter(
								$item["ID"],
								CTaskListState::VIEW_TASK_CATEGORY_WO_DEADLINE
							)
						);
					case 'VIEW_ROLE_ACCOMPLICE':
						$data['ITEMS'][$roleCode]["COUNTER"]['VIEW_TASK_CATEGORY_NEW'] = array(
							"ID" => CTaskListState::VIEW_TASK_CATEGORY_NEW,
							"VALUE" => $oListCtrl->getCounter(
								$item["ID"],
								CTaskListState::VIEW_TASK_CATEGORY_NEW
							)
						);
						$data['ITEMS'][$roleCode]["COUNTER"]['VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES'] = array(
							"ID" => CTaskListState::VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES,
							"VALUE" => $oListCtrl->getCounter(
								$item["ID"],
								CTaskListState::VIEW_TASK_CATEGORY_EXPIRED_CANDIDATES
							)
						);
					case 'VIEW_ROLE_AUDITOR':
						$data['ITEMS'][$roleCode]["COUNTER"]['VIEW_TASK_CATEGORY_EXPIRED'] = array(
							"ID" => CTaskListState::VIEW_TASK_CATEGORY_EXPIRED,
							"VALUE" => $oListCtrl->getCounter(
								$item["ID"],
								CTaskListState::VIEW_TASK_CATEGORY_EXPIRED
							)
						);
					break;
					case 'VIEW_ROLE_ORIGINATOR':
						$data['ITEMS'][$roleCode]["COUNTER"]['VIEW_TASK_CATEGORY_WO_DEADLINE'] = array(
							"ID" => CTaskListState::VIEW_TASK_CATEGORY_WO_DEADLINE,
							"VALUE" => $oListCtrl->getCounter(
								$item["ID"],
								CTaskListState::VIEW_TASK_CATEGORY_WO_DEADLINE
							)
						);
						$data['ITEMS'][$roleCode]["COUNTER"]['VIEW_TASK_CATEGORY_WAIT_CTRL'] = array(
							"ID" => CTaskListState::VIEW_TASK_CATEGORY_WAIT_CTRL,
							"VALUE" => $oListCtrl->getCounter(
								$item["ID"],
								CTaskListState::VIEW_TASK_CATEGORY_WAIT_CTRL
							)
						);
						$data['ITEMS'][$roleCode]["COUNTER"]['VIEW_TASK_CATEGORY_EXPIRED'] = array(
							"ID" => CTaskListState::VIEW_TASK_CATEGORY_EXPIRED,
							"VALUE" => $oListCtrl->getCounter(
								$item["ID"],
								CTaskListState::VIEW_TASK_CATEGORY_EXPIRED
							)
						);
					break;
				}
			}

			foreach ($data['ITEMS'][$roleCode]["COUNTER"] as &$c)
			{
				if ((string) $c['COUNTER_ID'] != '')
					$counterToRole[$c['COUNTER_ID']] = $roleCode;
				if (array_key_exists("COUNTER", $c))
				{
					$c["VALUE"] = $c["COUNTER"];
					unset($c["COUNTER"]);
				}
				$c["URL"] = CHTTP::urlAddParams($arResult['PATH_TEMPLATE'], array_merge($parameters, array("F_STATE[1]" => 'sC'.base_convert($c["ID"], 10, 32))));
			}
		}
	}
	// special presets, like "favorite"
	if(is_array($arResult['VIEW_STATE']['SPECIAL_PRESETS']))
	{
		foreach($arResult['VIEW_STATE']['SPECIAL_PRESETS'] as $presetId => $presetData)
		{
			$parameters = array(
				'F_FILTER_SWITCH_PRESET' => $presetId,
				'F_STATE[0]' => 'sC'.base_convert(CTaskListState::VIEW_TASK_CATEGORY_ALL, 10, 32),
			);

			$data['ITEMS'][$presetId] = array(
				'ID' => $presetId,
				'CODE' => $presetData["CODE"],
				'TITLE' => $presetData['TITLE'],
				'URL' => CHTTP::urlAddParams($arResult['PATH_TEMPLATE'], $parameters),
				'COUNTER' => array(
					'TOTAL' => array(
						'VALUE' => 0,
						'PLURAL' => 2
					)
				)
			);
		}
	}

	// "all" link
	$parameters = array(
		'F_FILTER_SWITCH_PRESET' => CTaskFilterCtrl::STD_PRESET_ALL_MY_TASKS
	);
	$data['ITEMS']['ALL'] = array(
		'ID' => 'ALL',
		'CODE' => 'ALL',
		'TITLE' => GetMessage('MB_TASKS_PANEL_TAB_ALL'),
		'URL' => CHTTP::urlAddParams($arResult['PATH_TEMPLATE'], $parameters),
		'COUNTER' => array(
			'TOTAL' => array(
				'VALUE' => 0,
				'PLURAL' => 2
			)
		)
	);

	// "projects" link
	$parameters = array(
		'F_FILTER_SWITCH_PRESET' => CTaskFilterCtrl::STD_PRESET_ALL_MY_TASKS
	);
	$data['ITEMS']['PROJECTS'] = array(
		'ID' => 'PROJECTS',
		'CODE' => 'PROJECTS',
		'TITLE' => GetMessage('MB_TASKS_PANEL_TAB_PROJECTS'),
		'URL' =>  CComponentEngine::MakePathFromTemplate(
			$arParams['PATH_TO_USER_TASKS_PROJECTS'],
			array('USER_ID' => $arParams["USER_ID"])
		),
		'COUNTER' => array(
			'TOTAL' => array(
				'VALUE' => 0,
				'PLURAL' => 2
			)
		)
	);
	$arResult['ITEMS'] = $data['ITEMS'];
	$arResult['counterToRole'] = $counterToRole;
}