<?
global $MESS;
$strPath2Lang = str_replace("\\", "/", __FILE__);
$strPath2Lang = substr($strPath2Lang, 0, strlen($strPath2Lang)-strlen("/install/index.php"));
include(GetLangFileName($strPath2Lang."/lang/", "/install/index.php"));

Class video extends CModule
{
	var $MODULE_ID = "video";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;
	var $MODULE_GROUP_RIGHTS = "Y";

	function video()
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

		$this->MODULE_NAME = GetMessage("VIDEO_INSTALL_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("VIDEO_INSTALL_DESCRIPTION");
	}


	function InstallDB($install_wizard = true)
	{
		RegisterModule("video");
		if(!$install_wizard)
			include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/video/install/iblock/install.php");
		return true;
	}

	function UnInstallDB($arParams = Array())
	{
		UnRegisterModule("video");
		return true;
	}

	function InstallEvents()
	{
	
		global $DB;
		$sIn = "'VIDEO_CALL_USER_INVITE', 'VIDEO_CONF_USER_INVITE'";
		$rs = $DB->Query("SELECT count(*) C FROM b_event_type WHERE EVENT_NAME IN (".$sIn.") ", false, "File: ".__FILE__."<br>Line: ".__LINE__);
		$ar = $rs->Fetch();
		if($ar["C"] <= 0)
		{
			include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/video/install/events/set_events.php");
		}
		return true;
	}

	function UnInstallEvents()
	{
		global $DB;
		include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/video/install/events/del_events.php");
		return true;
	}

	function InstallFiles($arParams = array())
	{
		global $install_public, $public_rewrite, $public_dir;
		if($_ENV["COMPUTERNAME"]!='BX')
		{
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/video/install/themes", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes", true, true);
			CopyDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/video/install/components", $_SERVER["DOCUMENT_ROOT"]."/bitrix/components", true, true);
			
			$bReWriteAdditionalFiles = ($arParams["public_rewrite"] == "Y");
			
			if(array_key_exists("public_dir", $arParams) && strlen($arParams["public_dir"]))
			{
				$iblockID = 0;
				$rsIBlock = CIBlock::GetList(array(), array("CODE" => "video-meeting", "TYPE" => "events"));
				if ($arIBlock = $rsIBlock->Fetch())
					$iblockID = $arIBlock["ID"];
				$arParams["public_dir"] = trim($arParams["public_dir"]);
				if(substr($arParams["public_dir"], 0, 1) == "/")
					$arParams["public_dir"] = substr($arParams["public_dir"], 1);
				if(substr($arParams["public_dir"], -1) == "/")
					$arParams["public_dir"] = substr($arParams["public_dir"], 0, strlen($arParams["public_dir"]) -1);

				$rsSite = CSite::GetList(($by="sort"),($order="asc"));
				while ($site = $rsSite->Fetch())
				{
					$source = $_SERVER['DOCUMENT_ROOT']."/bitrix/modules/video/install/public/".$site["LANGUAGE_ID"]."/video/";
					$target = $site['ABS_DOC_ROOT'].$site["DIR"].$arParams["public_dir"]."/";
					if(file_exists($source))
					{
						CheckDirPath($target);
						$dh = opendir($source);
						while($file = readdir($dh))
						{
							if($file == "." || $file == "..")
								continue;
							if($bReWriteAdditionalFiles || !file_exists($target.$file))
							{
								$fh = fopen($source.$file, "rb");
								$php_source = fread($fh, filesize($source.$file));
								fclose($fh);
								$php_source = str_replace(
									Array("#CALENDAR_RES_VIDEO_IBLOCK_ID#", "#VIDEO_INST_PATH#"),
									Array($iblockID, $site["DIR"].$arParams["public_dir"]),
									$php_source
								);
								$fh = fopen($target.$file, "wb");
								fwrite($fh, $php_source);
								fclose($fh);
							}
						}
					}
					if (CModule::IncludeModule('fileman'))
					{
						IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/video/install/index.php", $site["LANGUAGE_ID"]);

						$menuItem = Array(
								GetMessage("VIDEO_INSTALL_NAME"),
								$site["DIR"].$arParams["public_dir"]."/",
								Array(),
								Array(),
								""
							);
						$slPos = strrpos($arParams["public_dir"],"/");
						if($slPos !== false)
							$pathToMenu = $site["DIR"].substr($arParams["public_dir"], 0, $slPos)."/.left.menu.php";
						else
							$pathToMenu = $site["DIR"].$arParams["public_dir"]."/.left.menu.php";

						$arResultMenu = CFileMan::GetMenuArray($_SERVER["DOCUMENT_ROOT"].$pathToMenu);
						$arMenuItems = $arResultMenu["aMenuLinks"];
						$menuTemplate = $arResultMenu["sMenuTemplate"];

						$bFound = false;
						foreach($arMenuItems as $item)
							if($item[1] == $menuItem[1])
								$bFound = true;

						if(!$bFound)
						{
							$arMenuItems[] = $menuItem;
							CFileMan::SaveMenu(Array($site["ID"], $pathToMenu), $arMenuItems, $menuTemplate);
						}
					}

				}
			}

		}
		
		return true;
	}

	function UnInstallFiles()
	{
		if($_ENV["COMPUTERNAME"]!='BX')
		{
			DeleteDirFiles($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/video/install/themes/.default/", $_SERVER["DOCUMENT_ROOT"]."/bitrix/themes/.default");//css
		}

		return true;
	}

	function DoInstall()
	{
		global $DB, $DOCUMENT_ROOT, $APPLICATION, $step;
		$step = IntVal($step);
		if($step < 2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("VIDEO_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/video/install/step1.php");
		}
		elseif($step == 2)
		{
			if($this->InstallDB())
			{
				$this->InstallEvents();
				$this->InstallFiles(array(
					"public_dir" => $_REQUEST["public_dir"],
					"public_rewrite" => $_REQUEST["public_rewrite"],
				));
			}
			$GLOBALS["errors"] = $this->errors;
			$APPLICATION->IncludeAdminFile(GetMessage("VIDEO_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/video/install/step2.php");
		}
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