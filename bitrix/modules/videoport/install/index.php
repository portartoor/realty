<?
global $MESS;
$strPath2Lang = str_replace("\\", "/", __FILE__);
$strPath2Lang = substr($strPath2Lang, 0, strlen($strPath2Lang)-strlen("/install/index.php"));
include(GetLangFileName($strPath2Lang."/lang/", "/install/index.php"));

Class videoport extends CModule
{
	var $MODULE_ID = "videoport";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "Y";

	function videoport()
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

		$this->MODULE_NAME = GetMessage("VIDEO_VP_INSTALL_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("VIDEO_VP_INSTALL_DESCRIPTION");
	}


	function InstallDB($install_wizard = true)
	{
		RegisterModule("videoport");
	
		return true;
	}

	function UnInstallDB($arParams = Array())
	{
		UnRegisterModule("videoport");

		return true;
	}

	function InstallEvents()
	{
	
		return true;
	}

	function UnInstallEvents()
	{
		return true;
	}

	function InstallFiles()
	{
		if($_ENV["COMPUTERNAME"]!='BX')
		{
			CheckDirPath($_SERVER["DOCUMENT_ROOT"]."/bitrix/video/");
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/videoport/install/bitrix/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/video/", true, true);
		}
		
		return true;
	}

	function UnInstallFiles()
	{
		if($_ENV["COMPUTERNAME"]!='BX')
		{
			DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/videoport/install/bitrix", $_SERVER["DOCUMENT_ROOT"]."/bitrix/video");
		}

		return true;
	}

	function DoInstall()
	{
		global $APPLICATION, $step;
		$this->InstallFiles();
		$this->InstallDB(false);
		$this->InstallEvents();
		$GLOBALS["errors"] = $this->errors;
	}

	function DoUninstall()
	{
		global $APPLICATION, $step;
		$this->UnInstallDB();
		$this->UnInstallFiles();
		$this->UnInstallEvents();
		$GLOBALS["errors"] = $this->errors;
	}
}
?>