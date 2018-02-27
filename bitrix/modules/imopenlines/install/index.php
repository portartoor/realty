<?
global $MESS;
$PathInstall = str_replace("\\", "/", __FILE__);
$PathInstall = substr($PathInstall, 0, strlen($PathInstall)-strlen("/index.php"));

IncludeModuleLangFile($PathInstall."/install.php");

if(class_exists("imopenlines")) return;

Class imopenlines extends CModule
{
	var $MODULE_ID = "imopenlines";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_GROUP_RIGHTS = "Y";

	function imopenlines()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}
		else
		{
			$this->MODULE_VERSION = IMOPENLINES_VERSION;
			$this->MODULE_VERSION_DATE = IMOPENLINES_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("IMOPENLINES_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("IMOPENLINES_MODULE_DESCRIPTION");
	}

	public function GetPath($notDocumentRoot=false)
	{
		if($notDocumentRoot)
			return str_replace($_SERVER["DOCUMENT_ROOT"],'',dirname(__DIR__));
		else
			return dirname(__DIR__);
	}

	function DoInstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION, $step;
		$step = IntVal($step);
		if($step < 2)
		{
			$this->CheckModules();
			$APPLICATION->IncludeAdminFile(GetMessage("IMOPENLINES_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/step1.php");
		}
		elseif($step == 2)
		{
			if ($this->CheckModules())
			{
				$this->InstallDB(Array(
					'PUBLIC_URL' => $_REQUEST["PUBLIC_URL"]
				));
				$this->InstallFiles();
			}
			$APPLICATION->IncludeAdminFile(GetMessage("IMOPENLINES_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/step2.php");
		}
		return true;
	}

	function InstallEvents()
	{
		$orm = \Bitrix\Main\Mail\Internal\EventTypeTable::getList(array(
			'select' => array('ID'),
			'filter' => Array(
				'=EVENT_NAME' => Array('IMOL_HISTORY_LOG', 'IMOL_OPERATOR_ANSWER')
			)
		));
		
		if(!$orm->fetch())
		{
			include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/events/set_events.php");
		}

		return true;
	}

	function CheckModules()
	{
		global $APPLICATION;

		if (!CModule::IncludeModule('pull') || !CPullOptions::GetQueueServerStatus())
		{
			$this->errors[] = GetMessage('IMOPENLINES_CHECK_PULL');
		}

		if (!IsModuleInstalled('imconnector'))
		{
			$this->errors[] = GetMessage('IMOPENLINES_CHECK_CONNECTOR');
		}

		if (!IsModuleInstalled('im'))
		{
			$this->errors[] = GetMessage('IMOPENLINES_CHECK_IM');
		}
		else
		{
			$imVersion = \Bitrix\Main\ModuleManager::getVersion('im');
			if (version_compare("16.5.0", $imVersion) == 1)
			{
				$this->errors[] = GetMessage('IMOPENLINES_CHECK_IM_VERSION');
			}
		}

		if(is_array($this->errors) && !empty($this->errors))
		{
			$APPLICATION->ThrowException(implode("<br>", $this->errors));
			return false;
		}
		else
		{
			return true;
		}
	}

	function InstallDB($params = Array())
	{
		global $DB, $APPLICATION;

		$this->errors = false;

		if(strtolower($DB->type) !== 'mysql')
		{
			$this->errors = array(
				GetMessage('IMOPENLINES_DB_NOT_SUPPORTED'),
			);
		}

		if (strlen($params['PUBLIC_URL']) > 0 && strlen($params['PUBLIC_URL']) < 12)
		{
			if (!$this->errors)
			{
				$this->errors = Array();
			}
			$this->errors[] = GetMessage('IMOPENLINES_CHECK_PUBLIC_PATH');
		}

		if(!$this->errors && !$DB->Query("SELECT 'x' FROM b_imopenlines_config", true))
			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/imopenlines/install/db/".strtolower($DB->type)."/install.sql");

		if($this->errors !== false)
		{
			$APPLICATION->ThrowException(implode("", $this->errors));
			return false;
		}

		RegisterModule("imopenlines");
		
		COption::SetOptionString("imopenlines", "portal_url", $params['PUBLIC_URL']);

		RegisterModuleDependences('im', 'OnBeforeChatMessageAdd', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onBeforeMessageSend');
		RegisterModuleDependences('im', 'OnAfterMessagesAdd', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onMessageSend');
		RegisterModuleDependences('im', 'OnAfterMessagesAdd', 'imopenlines', '\Bitrix\ImOpenLines\LiveChat', 'onMessageSend');
		RegisterModuleDependences('im', 'OnAfterChatRead', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onChatRead');
		RegisterModuleDependences('im', 'OnStartWriting', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onStartWriting');
		RegisterModuleDependences('im', 'OnLoadLastMessage', 'imopenlines', '\Bitrix\ImOpenLines\Session', 'onSessionProlongLastMessage');
		RegisterModuleDependences('im', 'OnStartWriting', 'imopenlines', '\Bitrix\ImOpenLines\Session', 'onSessionProlongWriting');
		RegisterModuleDependences('im', 'OnChatRename', 'imopenlines', '\Bitrix\ImOpenLines\Session', 'onSessionProlongChatRename');
		RegisterModuleDependences('im', 'OnAfterMessagesUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onMessageUpdate');
		RegisterModuleDependences('im', 'OnAfterMessagesDelete', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onMessageDelete');
		RegisterModuleDependences('im', 'OnGetNotifySchema', 'imopenlines', '\Bitrix\ImOpenLines\Chat', 'onGetNotifySchema');
		
		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->registerEventHandler('imconnector', 'OnReceivedPost', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedPost');
		$eventManager->registerEventHandler('imconnector', 'OnReceivedMessageUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedPostUpdate');
		$eventManager->registerEventHandler('imconnector', 'OnReceivedMessage', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedMessage');
		$eventManager->registerEventHandler('imconnector', 'OnReceivedMessageUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedMessageUpdate');
		$eventManager->registerEventHandler('imconnector', 'OnReceivedMessageDel', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedMessageDelete');
		$eventManager->registerEventHandler('imconnector', 'OnReceivedStatusDelivery', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedStatusDelivery');
		$eventManager->registerEventHandler('imconnector', 'OnReceivedStatusReading', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedStatusReading');
		$eventManager->registerEventHandler('imconnector', 'OnReceivedStatusWrites', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedStatusWrites');
		$eventManager->registerEventHandler('main', 'OnAfterSetOption_~controller_group_name', 'imopenlines', '\Bitrix\ImOpenLines\Limit', 'onBitrix24LicenseChange');
		$eventManager->registerEventHandler('rest', 'OnRestServiceBuildDescription', 'imopenlines', '\Bitrix\ImOpenLines\Rest', 'onRestServiceBuildDescription');

		CAgent::AddAgent('\Bitrix\ImOpenLines\Session::transferToNextInQueueAgent(0);', "imopenlines", "N", 60);
		CAgent::AddAgent('\Bitrix\ImOpenLines\Session::closeByTimeAgent(0);', "imopenlines", "N", 60);
		CAgent::AddAgent('\Bitrix\ImOpenLines\Session::mailByTimeAgent(0);', "imopenlines", "N", 60);
		CAgent::AddAgent('\Bitrix\ImOpenLines\Common::deleteBrokenSession();', "imopenlines", "N", 86400, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+86400, "FULL"));
		
		if (!IsModuleInstalled('bitrix24'))
		{
			CAgent::AddAgent('\Bitrix\ImOpenLines\Security\Helper::installRolesAgent();', "imopenlines", "N", 60, "", "Y", \ConvertTimeStamp(time()+\CTimeZone::GetOffset()+60, "FULL"));
		}
		
		if(strtolower($DB->type) == 'mysql' && $DB->Query("CREATE fulltext index IXF_IMOL_S_1 on b_imopenlines_session (SEARCH_CONTENT)", true))
		{
			\CModule::IncludeModule("imopenlines");
			\Bitrix\Imopenlines\Model\SessionTable::getEntity()->enableFullTextIndex("SEARCH_CONTENT");
		}
		
		$this->InstallEvents();

		return true;
	}

	function InstallFiles()
	{
		\CopyDirFiles($this->GetPath()."/install/js", $_SERVER["DOCUMENT_ROOT"]."/bitrix/js", true, true);
		\CopyDirFiles($this->GetPath()."/install/components/bitrix", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix", true, true);
		\CopyDirFiles($this->GetPath()."/install/activities", $_SERVER["DOCUMENT_ROOT"]."/bitrix/activities", true, true);

		return true;
	}

	function UnInstallEvents()
	{
		global $DB;
		
		include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/imopenlines/install/events/del_events.php");
		
		return true;
	}

	function DoUninstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION, $step;
		$step = IntVal($step);
		if($step<2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("IMOPENLINES_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/imopenlines/install/unstep1.php");
		}
		elseif($step==2)
		{
			$this->UnInstallDB(array("savedata" => $_REQUEST["savedata"]));
			
			if(!isset($_REQUEST["saveemails"]) || $_REQUEST["saveemails"] != "Y")
				$this->UnInstallEvents();
			
			$this->UnInstallFiles();

			$APPLICATION->IncludeAdminFile(GetMessage("IMOPENLINES_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/imopenlines/install/unstep2.php");
		}
	}

	function UnInstallDB($arParams = Array())
	{
		global $APPLICATION, $DB, $errors;

		$this->errors = false;

		if (!$arParams['savedata'])
			$this->errors = $DB->RunSQLBatch($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/imopenlines/install/db/".strtolower($DB->type)."/uninstall.sql");

		if(is_array($this->errors))
			$arSQLErrors = $this->errors;

		if(!empty($arSQLErrors))
		{
			$this->errors = $arSQLErrors;
			$APPLICATION->ThrowException(implode("", $arSQLErrors));
			return false;
		}

		UnRegisterModuleDependences('im', 'OnBeforeChatMessageAdd', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onBeforeMessageSend');
		UnRegisterModuleDependences('im', 'OnAfterMessagesAdd', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onMessageSend');
		UnRegisterModuleDependences('im', 'OnAfterMessagesAdd', 'imopenlines', '\Bitrix\ImOpenLines\LiveChat', 'onMessageSend');
		UnRegisterModuleDependences('im', 'OnAfterChatRead', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onChatRead');
		UnRegisterModuleDependences('im', 'OnAfterMessagesUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onMessageUpdate');
		UnRegisterModuleDependences('im', 'OnAfterMessagesDelete', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onMessageDelete');
		UnRegisterModuleDependences('im', 'OnStartWriting', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onStartWriting');
		UnRegisterModuleDependences('im', 'OnLoadLastMessage', 'imopenlines', '\Bitrix\ImOpenLines\Session', 'onSessionProlongLastMessage');
		UnRegisterModuleDependences('im', 'OnStartWriting', 'imopenlines', '\Bitrix\ImOpenLines\Session', 'onSessionProlongWriting');
		UnRegisterModuleDependences('im', 'OnChatRename', 'imopenlines', '\Bitrix\ImOpenLines\Session', 'onSessionProlongChatRename');
		UnRegisterModuleDependences('im', 'OnGetNotifySchema', 'imopenlines', '\Bitrix\ImOpenLines\Chat', 'onGetNotifySchema');
		
		$eventManager = \Bitrix\Main\EventManager::getInstance();
		$eventManager->unRegisterEventHandler('imconnector', 'OnReceivedPost', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedPost');
		$eventManager->unRegisterEventHandler('imconnector', 'OnReceivedPostUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'OnReceivedPostUpdate');
		$eventManager->unRegisterEventHandler('imconnector', 'OnReceivedMessage', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedMessage');
		$eventManager->unRegisterEventHandler('imconnector', 'OnReceivedMessageUpdate', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'OnReceivedMessageUpdate');
		$eventManager->unRegisterEventHandler('imconnector', 'OnReceivedMessageDel', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedMessageDelete');
		$eventManager->unRegisterEventHandler('imconnector', 'OnReceivedStatusDelivery', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedStatusDelivery');
		$eventManager->unRegisterEventHandler('imconnector', 'OnReceivedStatusReading', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedStatusReading');
		$eventManager->unRegisterEventHandler('imconnector', 'OnReceivedStatusWrites', 'imopenlines', '\Bitrix\ImOpenLines\Connector', 'onReceivedStatusWrites');
		$eventManager->unRegisterEventHandler('main', 'OnAfterSetOption_~controller_group_name', 'imopenlines', '\Bitrix\ImOpenLines\Limit', 'onBitrix24LicenseChange');
		$eventManager->unRegisterEventHandler('rest', 'OnRestServiceBuildDescription', 'imopenlines', '\Bitrix\ImOpenLines\Rest', 'onRestServiceBuildDescription');

		UnRegisterModule("imopenlines");

		return true;
	}

	function UnInstallFiles($arParams = array())
	{
		return true;
	}
}
?>
