<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)
	die();

if(!CModule::IncludeModule("iblock"))
	return;

$iblockCode = "video-meeting";
$iblockType = "events";
$xmlFile = "/bitrix/modules/video/install/iblock/lang_".LANGUAGE_ID."/res_video.xml";
$permissions = Array(
		"1" => "X",
		"2" => "R",
	);
	
$dbIblockType = CIBlockType::GetList(Array(), Array("=ID" => $iblockType));
if(!$dbIblockType -> Fetch())
{
	$obBlocktype = new CIBlockType;
	$arFields = Array(
			"ID" => $iblockType,
			"SORT" => 500,
			"IN_RSS" => "N",
			"SECTIONS" => "Y"
		);
		
	$langs = CLanguage::GetList(($b=""), ($o=""));
	while($lang = $langs->Fetch())
	{
		$lid = $lang["LID"];
		IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/video/install/iblock/install.php", $lid);
		$arFields["LANG"][$lid] = Array("NAME" => GetMessage("VI_IBLOCK_NAME"));
	}

	$res = $obBlocktype->Add($arFields);
}

$rsIBlock = CIBlock::GetList(array(), array("CODE" => $iblockCode, "TYPE" => $iblockType));
if ($arIBlock = $rsIBlock->Fetch())
	return false;

$arSite = Array();
$dbSites = CSite::GetList(($b = ""), ($o = ""), Array("ACTIVE" => "Y"));
while ($site = $dbSites->Fetch())
{ 
	$arSite[] = $site["LID"];
}
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/iblock/classes/".strtolower($GLOBALS["DB"]->type)."/cml2.php");
ImportXMLFile($xmlFile, $iblockType, $arSite, $section_action = "N", $element_action = "N");

$iblockID = false;
$rsIBlock = CIBlock::GetList(array(), array("CODE" => $iblockCode, "TYPE" => $iblockType));
if ($arIBlock = $rsIBlock->Fetch())
{
	$iblockID = $arIBlock["ID"];

	if (empty($permissions))
		$permissions = Array(1 => "X", 2 => "R");

	CIBlock::SetPermission($iblockID, $permissions);
}
if ($iblockID < 1)
	return;
return $iblockID;
?>
