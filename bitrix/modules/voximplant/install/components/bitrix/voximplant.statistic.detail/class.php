<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Voximplant;
use Bitrix\Voximplant\Security\Permissions;
use Bitrix\Voximplant\Security\Helper;

Loc::loadMessages(__FILE__);

class CVoximplantStatisticDetailComponent extends \CBitrixComponent
{
	const LOCK_OPTION = 'export_statistic_detail_lock';
	const MODULE = 'voximplant';

	protected $gridId = "voximplant_statistic_detail";
	/** @var  CGridOptions */
	protected $gridOptions;
	protected $userIds = array();
	protected $userData = array();
	protected $showCallCost = true;
	protected $excelMode = false;
	protected $enableExport = true;
	/** @var Permissions */
	protected $userPermissions;

	protected function init()
	{
		$this->enableExport = CVoxImplantAccount::IsPro();
		$this->gridOptions = new CGridOptions($this->gridId);
		
		$this->userPermissions = Permissions::createWithCurrentUser();

		$account = new CVoxImplantAccount();
		if (in_array($account->GetAccountLang(), array('ua', 'kz')))
		{
			$this->showCallCost = false;
		}

		if ($_REQUEST['excel'] === 'Y' && $this->enableExport)
		{
			if($this->getLock())
			{
				$this->excelMode = true;
			}
			else
			{
				$this->arResult['ERROR_TEXT'] = Loc::getMessage("TEL_STAT_EXPORT_LOCK_ERROR");
			}
		}
	}

	protected function checkAccess()
	{
		return $this->userPermissions->canPerform(Permissions::ENTITY_CALL_DETAIL, Permissions::ACTION_VIEW);
	}

	protected function getFilterDefinition()
	{
		$result = array(
			"START_DATE" => array(
				"id" => "START_DATE",
				"name" => Loc::getMessage("TELEPHONY_HEADER_START_DATE"),
				"type" => "date",
				"default" => true
			),
			"PORTAL_USER_ID" => array(
				"id" => "PORTAL_USER_ID",
				"name" => Loc::getMessage("TELEPHONY_HEADER_USER"),
				"type" => "custom",
				"default" => true,
			),
			'PORTAL_NUMBER' => array(
				"id" => "PORTAL_NUMBER",
				"name" => Loc::getMessage("TELEPHONY_HEADER_PORTAL_PHONE"),
				"type" => "list",
				"items" => CVoxImplantConfig::GetPortalNumbers(),
				"default" => false,
				"params" => array(
					"multiple" => true
				)
			),
			"PHONE_NUMBER" => array(
				"id" => "PHONE_NUMBER",
				"name" => Loc::getMessage("TELEPHONY_HEADER_PHONE"),
				"default" => false
			),
			"INCOMING" => array(
				"id" => "INCOMING",
				"name" => Loc::getMessage("TELEPHONY_HEADER_INCOMING"),
				"type" => "list",
				"items" => array("" => Loc::getMessage("TELEPHONY_FILTER_STATUS_UNSET")) + CVoxImplantHistory::GetCallTypes(),
				"default" => false
			),
			"STATUS" => array(
				"id" => "STATUS",
				"name" => Loc::getMessage("TELEPHONY_HEADER_STATUS"),
				"type" => "list",
				"items" => array(
					"" => Loc::getMessage("TELEPHONY_FILTER_STATUS_UNSET"),
					"1" => Loc::getMessage("TELEPHONY_FILTER_STATUS_SUCCESSFUL"),
					"0" => Loc::getMessage("TELEPHONY_FILTER_STATUS_FAILED")
				)
			),
			"CALL_DURATION" => array(
				"id" => "CALL_DURATION",
				"name" => Loc::getMessage("TELEPHONY_HEADER_DURATION"),
				"default" => false,
				"type" => "number"
			),
			"COST" => array(
				"id" => "COST",
				"name" => Loc::getMessage("TELEPHONY_HEADER_COST"),
				"default" => false,
				"type" => "number"
			),
		);

		$filterValues = $this->gridOptions->GetFilter($result);
		$portalUserId = isset($filterValues['PORTAL_USER_ID']) ? (int)$filterValues['PORTAL_USER_ID'] : 0;

		$result["PORTAL_USER_ID"]["value"] = Voximplant\Ui\Helper::renderUserSelector(
			"PORTAL_USER_SELECT",
			"PORTAL_USER_ID_name",
			"PORTAL_USER_ID",
			"PORTAL_USER_SELECT_COMPONENT",
			$portalUserId
		);

		return $result;
	}

	protected function getButtons()
	{
		global $APPLICATION;

		if($this->enableExport)
		{
			$result = array(
				array(
					'TEXT' => Loc::getMessage('TEL_STAT_EXPORT_TO_EXCEL'),
					'TITLE' => Loc::getMessage('TEL_STAT_EXPORT_TO_EXCEL'),
					'LINK' => $APPLICATION->GetCurPageParam('excel=Y'),
					'ICON' => 'btn-excel'
				)
			);
		}
		else
		{
			$result = array(
				array(
					'TEXT' => Loc::getMessage('TEL_STAT_EXPORT_TO_EXCEL'),
					'TITLE' => Loc::getMessage('TEL_STAT_EXPORT_TO_EXCEL'),
					'LINK' => 'javascript: viOpenTrialPopup(\'excel-export\');',
					'ICON' => 'btn-lock'
				)
			);
		}
		return $result;
	}

	protected function getFilter(array $gridFilter)
	{
		$filter = $this->gridOptions->GetFilter($gridFilter);
		$result = array();

		foreach ($filter as $k => $v)
		{
			if (strpos($k, "datesel") !== false)
			{
				unset($filter[$k]);
			}
		}

		$allowedUserIds = Helper::getAllowedUserIds(
			Helper::getCurrentUserId(),
			$this->userPermissions->getPermission(Permissions::ENTITY_CALL_DETAIL, Permissions::ACTION_VIEW)
		);

		if(isset($filter["PORTAL_USER_ID"]))
		{
			$filter["PORTAL_USER_ID"] = (int)$filter["PORTAL_USER_ID"];
			if(is_array($allowedUserIds))
			{
				$result["=PORTAL_USER_ID"] = array_intersect($allowedUserIds, array($filter["PORTAL_USER_ID"]));
			}
			else
			{
				$result["=PORTAL_USER_ID"] = $filter["PORTAL_USER_ID"];
			}
		}
		else
		{
			if(is_array($allowedUserIds))
			{
				$result["=PORTAL_USER_ID"] = $allowedUserIds;
			}
		}

		if (strlen($filter["PHONE_NUMBER"]) > 0)
		{
			$result["PHONE_NUMBER"] = $filter["PHONE_NUMBER"];
		}

		if (strlen($filter["START_DATE_from"]) > 0)
		{
			try
			{
				$result[">=CALL_START_DATE"] = new \Bitrix\Main\Type\Date($filter["START_DATE_from"]);
			} catch (Exception $e)
			{
			}
		}

		if (strlen($filter["START_DATE_to"]) > 0)
		{
			try
			{
				$result["<=CALL_START_DATE"] = new \Bitrix\Main\Type\Date($filter["START_DATE_to"]);
				$result["<=CALL_START_DATE"]->add("1 day");
			} catch (Exception $e)
			{
			}
		}

		if (intval($filter['CALL_DURATION_from']) > 0)
		{
			$result[">=CALL_DURATION"] = (int)$filter['CALL_DURATION_from'];
		}

		if (intval($filter['CALL_DURATION_to']) > 0)
		{
			$result["<=CALL_DURATION"] = (int)$filter['CALL_DURATION_to'];
		}

		if (floatval($filter['COST_from']) > 0)
		{
			$result[">=COST"] = (float)$filter['COST_from'];
		}

		if (floatval($filter['COST_to']) > 0)
		{
			$result["<=COST"] = (float)$filter['COST_to'];
		}

		if (isset($filter['PORTAL_NUMBER']))
		{
			$result["=PORTAL_NUMBER"] = $filter["PORTAL_NUMBER"];
		}

		if (isset($filter['STATUS']))
		{
			if ($filter['STATUS'] === '0')
			{
				$result['!=CALL_FAILED_CODE'] = '200';
			}
			else
			{
				if ($filter['STATUS'] === '1')
				{
					$result['=CALL_FAILED_CODE'] = '200';
				}
			}
		}

		if (isset($filter['INCOMING']))
		{
			$result['=INCOMING'] = $filter['INCOMING'];
		}

		return $result;
	}

	protected function prepareData()
	{
		$this->arResult["ENABLE_EXPORT"] = $this->enableExport;
		$this->arResult["TRIAL_TEXT"] = CVoxImplantMain::GetTrialText();

		$this->arResult["GRID_ID"] = $this->gridId;
		$this->arResult["FILTER"] = $this->getFilterDefinition();
		$this->arResult["BUTTONS"] = $this->getButtons();

		$sorting = $this->gridOptions->GetSorting(array("sort" => array("ID" => "DESC")));
		$navParams = $this->gridOptions->GetNavParams();
		$pageSize = $navParams['nPageSize'];

		$nav = new \Bitrix\Main\UI\PageNavigation("page");
		$nav->allowAllRecords(false)
			->setPageSize($pageSize)
			->initFromUri();

		$cursor = Voximplant\StatisticTable::getList(array(
			"filter" => $this->getFilter($this->arResult['FILTER']),
			"order" => $sorting["sort"],
			"select" => array('*'),
			"count_total" => true,
			"offset" => ($this->excelMode ? 0 : $nav->getOffset()),
			"limit" => ($this->excelMode ? 0 : $nav->getLimit())
		));

		$rows = array();
		$portalNumbers = CVoxImplantConfig::GetPortalNumbers();
		while ($row = $cursor->fetch())
		{
			if ($row["PORTAL_USER_ID"] > 0 && !in_array($row["PORTAL_USER_ID"], $this->userIds))
			{
				$this->userIds[] = $row["PORTAL_USER_ID"];
			}

			$row = CVoxImplantHistory::PrepereData($row);
			if (!$this->showCallCost)
			{
				$row['COST_TEXT'] = '-';
			}

			if (in_array($row["CALL_FAILED_CODE"], Array(1, 2, 3, 409)))
			{
				$row["CALL_FAILED_REASON"] = Loc::getMessage("TELEPHONY_STATUS_".$row["CALL_FAILED_CODE"]);
			}

			if (isset($portalNumbers[$row["PORTAL_NUMBER"]]))
			{
				$row["PORTAL_NUMBER"] = $portalNumbers[$row["PORTAL_NUMBER"]];
			}
			else
			{
				if (substr($row["PORTAL_NUMBER"], 0, 3) == 'sip')
				{
					$row["PORTAL_NUMBER"] = Loc::getMessage("TELEPHONY_PORTAL_PHONE_SIP_OFFICE", Array('#ID#' => substr($row["PORTAL_NUMBER"], 3)));
				}
				else
				{
					if (substr($row["PORTAL_NUMBER"], 0, 3) == 'reg')
					{
						$row["PORTAL_NUMBER"] = Loc::getMessage("TELEPHONY_PORTAL_PHONE_SIP_CLOUD", Array('#ID#' => substr($row["PORTAL_NUMBER"], 3)));
					}
					else
					{
						if (strlen($row["PORTAL_NUMBER"]) <= 0)
						{
							$row["PORTAL_NUMBER"] = Loc::getMessage("TELEPHONY_PORTAL_PHONE_EMPTY");
						}
					}
				}
			}

			if ($row["PORTAL_USER_ID"] == 0 && strlen($row["PHONE_NUMBER"]) <= 0)
			{
				$row["CALL_DURATION_TEXT"] = '';
				$row["INCOMING_TEXT"] = '';
			}

			if (intval($row["CALL_VOTE"]) == 0)
			{
				$row["CALL_VOTE"] = '-';
			}

			$t_row = array(
				"data" => $row,
				"columns" => array(),
				"editable" => false,
				"actions" => array(),
			);
			$rows[] = $t_row;
		}

		$this->userData = $this->getUserData($this->userIds);

		$this->arResult["ROWS"] = $this->addCustomColumns($rows);

		$this->arResult["ROWS_COUNT"] = $cursor->getCount();
		$nav->setRecordCount($cursor->getCount());

		$this->arResult["SORT"] = $sorting["sort"];
		$this->arResult["SORT_VARS"] = $sorting["vars"];
		$this->arResult["NAV_OBJECT"] = $nav;

		$this->arResult["HEADERS"] = array(
			array("id" => "USER_NAME", "name" => GetMessage("TELEPHONY_HEADER_USER"), "default" => true, "editable" => false),
			array("id" => "PORTAL_NUMBER", "name" => GetMessage("TELEPHONY_HEADER_PORTAL_PHONE"), "default" => false, "editable" => false),
			array("id" => "PHONE_NUMBER", "name" => GetMessage("TELEPHONY_HEADER_PHONE"), "default" => true, "editable" => false),
			array("id" => "INCOMING_TEXT", "name" => GetMessage("TELEPHONY_HEADER_INCOMING"), "default" => true, "editable" => false),
			array("id" => "CALL_DURATION_TEXT", "name" => GetMessage("TELEPHONY_HEADER_DURATION"), "default" => true, "editable" => false),
			array("id" => "CALL_START_DATE", "name" => GetMessage("TELEPHONY_HEADER_START_DATE"), "default" => true, "editable" => false),
			array("id" => "CALL_FAILED_REASON", "name" => GetMessage("TELEPHONY_HEADER_STATUS"), "default" => true, "editable" => false),
			array("id" => "COST_TEXT", "name" => GetMessage("TELEPHONY_HEADER_COST"), "default" => true, "editable" => false),
			array("id" => "CALL_VOTE", "name" => GetMessage("TELEPHONY_HEADER_VOTE"), "default" => CVoxImplantAccount::IsPro(), "editable" => false),
			array("id" => "RECORD", "name" => GetMessage("TELEPHONY_HEADER_RECORD"), "default" => true, "editable" => false),
			array("id" => "LOG", "name" => GetMessage("TELEPHONY_HEADER_LOG"), "default" => true, "editable" => false),
		);
	}

	function getUserData(array $userIds)
	{
		$arUsers = array();
		if (!empty($userIds))
		{
			$dbUser = CUser::GetList($by = "", $order = "", array("ID" => implode($userIds, " | ")), array("FIELDS" => array("ID", "NAME", "LAST_NAME", "SECOND_NAME", "LOGIN", "PERSONAL_PHOTO")));
			while ($arUser = $dbUser->Fetch())
			{
				$arUsers[$arUser["ID"]]["FIO"] = CUser::FormatName("#NAME# #LAST_NAME#", array(
					"NAME" => $arUser["NAME"],
					"LAST_NAME" => $arUser["LAST_NAME"],
					"SECOND_NAME" => $arUser["SECOND_NAME"],
					"LOGIN" => $arUser["LOGIN"]
				));

				if (intval($arUser["PERSONAL_PHOTO"]) > 0)
				{
					$imageFile = CFile::GetFileArray($arUser["PERSONAL_PHOTO"]);
					if ($imageFile !== false)
					{
						$arFileTmp = CFile::ResizeImageGet(
							$imageFile,
							array("width" => "30", "height" => "30"),
							BX_RESIZE_IMAGE_EXACT,
							false
						);
						$arUsers[$arUser["ID"]]["PHOTO"] = $arFileTmp["src"];
					}
				}
			}
		}

		return $arUsers;
	}

	function addCustomColumns(array $data)
	{
		$allowedUserIdsToViewRecord = Helper::getAllowedUserIds(
			Helper::getCurrentUserId(),
			$this->userPermissions->getPermission(Permissions::ENTITY_CALL_RECORD, Permissions::ACTION_LISTEN)
		);

		$result = array();
		foreach ($data as $key => $row)
		{
			$recordHtml = '-';
			if (
				!is_array($allowedUserIdsToViewRecord)
				|| in_array($row['data']['PORTAL_USER_ID'], $allowedUserIdsToViewRecord))
			{
				$recordHtml = $this->getRecordHtml(
					$row['data']['ID'],
					$row['data']['CALL_RECORD_HREF'],
					$row['data']['CALL_RECORD_DOWNLOAD_URL']
				);
			}

			$row["columns"] = array(
				"USER_NAME" => $this->getUserHtml($row['data']['PORTAL_USER_ID'], $row["data"]["PHONE_NUMBER"], $row['data']['CALL_ICON']),
				"LOG" => $row["data"]["CALL_LOG"] ? '<a href="'.$row["data"]["CALL_LOG"].'" target="_blank" class="tel-player-download"></a>' : '-',
				"RECORD" => $recordHtml,
			);
			$result[$key] = $row;
		}

		return $result;
	}

	protected function getUserHtml($userId, $phoneNumber, $callIcon)
	{
		if ($userId > 0)
		{
			$userHtml = "<span class='tel-stat-user-img user-avatar'";
			if ($this->userData[$userId]["PHOTO"])
			{
				$userHtml .= "style=\"background: url('".$this->userData[$userId]["PHOTO"]."') no-repeat center;\"";
			}
			$userHtml .= "></span>".$this->userData[$userId]["FIO"];
		}
		else
		{
			$userHtml = "<span class='tel-stat-user-img user-avatar'></span> &mdash;";
		}

		if (strlen($phoneNumber) <= 0)
		{
			$userHtml = Loc::getMessage('TELEPHONY_BILLING');
		}
		else
		{
			$userHtml = '<span class="tel-stat-icon tel-stat-icon-'.$callIcon.'"></span><span style="white-space: nowrap">'.$userHtml.'</span>';
		}

		return $userHtml;
	}

	protected function getRecordHtml($id, $recordHref, $recordDownloadUrl)
	{
		global $APPLICATION;
		if (strlen($recordHref) > 0)
		{
			ob_start();
			$APPLICATION->IncludeComponent(
				"bitrix:player",
				"",
				Array(
					"PLAYER_TYPE" => "flv",
					"CHECK_FILE" => "N",
					"USE_PLAYLIST" => "N",
					"PATH" => $recordHref,
					"WIDTH" => 250,
					"HEIGHT" => 24,
					"PREVIEW" => false,
					"LOGO" => false,
					"FULLSCREEN" => "N",
					"SKIN_PATH" => "/bitrix/components/bitrix/player/mediaplayer/skins",
					"SKIN" => "",
					"CONTROLBAR" => "bottom",
					"WMODE" => "transparent",
					"WMODE_WMV" => "windowless",
					"HIDE_MENU" => "N",
					"SHOW_CONTROLS" => "N",
					"SHOW_STOP" => "Y",
					"SHOW_DIGITS" => "Y",
					"CONTROLS_BGCOLOR" => "FFFFFF",
					"CONTROLS_COLOR" => "000000",
					"CONTROLS_OVER_COLOR" => "000000",
					"SCREEN_COLOR" => "000000",
					"AUTOSTART" => "N",
					"REPEAT" => "N",
					"VOLUME" => "90",
					"DISPLAY_CLICK" => "play",
					"MUTE" => "N",
					"HIGH_QUALITY" => "N",
					"ADVANCED_MODE_SETTINGS" => "Y",
					"BUFFER_LENGTH" => "10",
					"DOWNLOAD_LINK" => false,
					"DOWNLOAD_LINK_TARGET" => "_self",
					"ALLOW_SWF" => "N",
					"ADDITIONAL_PARAMS" => array(
						'LOGO' => false,
						'NUM' => false,
						'HEIGHT_CORRECT' => false,
					),
					"PLAYER_ID" => "bitrix_vi_record_".$id
				),
				false,
				Array("HIDE_ICONS" => "Y")
			);
			$recordHtml = '<div class="tel-player">'.ob_get_contents().'</div>';
			ob_end_clean();

			if ($recordDownloadUrl != '')
			{
				$recordHtml .= '<a href="'.$recordDownloadUrl.'" target="_blank" class="tel-player-download"></a>';
			}
			else
			{
				$recordHtml .= '<a href="'.$recordHref.'" target="_blank" class="tel-player-download"></a>';
			}
			$result = '<span style="white-space: nowrap">'.$recordHtml.'</span>';
		}
		else
		{
			$result = '-';
		}
		return $result;
	}

	protected function getLock()
	{
		if(!\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24'))
			return true;

		$currentTimestamp = time();
		$lockTimestamp = (int)\Bitrix\Main\Config\Option::get(self::MODULE, self::LOCK_OPTION);

		if($lockTimestamp > 0)
		{
			if($currentTimestamp - $lockTimestamp > 60)
			{
				\Bitrix\Main\Config\Option::set(self::MODULE, self::LOCK_OPTION, $currentTimestamp);
				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			\Bitrix\Main\Config\Option::set(self::MODULE, self::LOCK_OPTION, $currentTimestamp);
			return true;
		}
	}

	protected function releaseLock()
	{
		if(!\Bitrix\Main\ModuleManager::isModuleInstalled('bitrix24'))
			return;

		\Bitrix\Main\Config\Option::set(self::MODULE, self::LOCK_OPTION);
	}

	/**
	 * Executes component
	 */
	public function executeComponent()
	{
		global $APPLICATION;

		if (!Loader::includeModule(self::MODULE))
			return false;

		$this->init();

		if(!$this->checkAccess())
			return false;

		$this->prepareData();

		if($this->excelMode)
		{
			$this->releaseLock();
			$now = new \Bitrix\Main\Type\Date();
			$filename = 'call_details_'.$now->format('Y_m_d').'.xls';
			$APPLICATION->RestartBuffer();
			header("Content-Type: application/vnd.ms-excel");
			header("Content-Disposition: filename=".$filename);
			$this->includeComponentTemplate('excel');
			CMain::FinalActions();
			die();
		}
		else
		{
			$this->includeComponentTemplate();
			return $this->arResult;
		}
	}
}