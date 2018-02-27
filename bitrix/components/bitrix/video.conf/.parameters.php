<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if (!CModule::IncludeModule("video"))
	return false;
if (!CModule::IncludeModule("iblock"))
	return false;

$arIBlockType = array();
$rsIBlockType = CIBlockType::GetList(array("sort"=>"asc"), array("ACTIVE"=>"Y"));
while ($arr=$rsIBlockType->Fetch())
{
	if($ar=CIBlockType::GetByIDLang($arr["ID"], LANGUAGE_ID))
		$arIBlockType[$arr["ID"]] = "[".$arr["ID"]."] ".$ar["NAME"];
}

$arIBlock=array();
$rsIBlock = CIBlock::GetList(Array("sort" => "asc"), Array("TYPE" => $arCurrentValues["IBLOCK_TYPE"], "ACTIVE"=>"Y"));
while($arr=$rsIBlock->Fetch())
	$arIBlock[$arr["ID"]] = "[".$arr["ID"]."] ".$arr["NAME"];


$arComponentParameters = Array(
	"PARAMETERS" => Array(
		"IBLOCK_TYPE" => Array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("VIDEO_IBLOCK_TYPE"),
			"TYPE" => "LIST",
			"VALUES" => $arIBlockType,
			"REFRESH" => "Y",
		),
		"IBLOCK_ID" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("VIDEO_IBLOCK"),
			"TYPE" => "LIST",
			"VALUES" => $arIBlock,
			"REFRESH" => "Y",
		),
		"ID" => array(
			"NAME" => GetMessage("VIDEO_ID"), 
			"TYPE" => "STRING",
			"PARENT" => "BASE",
			"DEFAULT" => "={\$_REQUEST[\"ID\"]}"
		),
		"PATH_TO_VIDEO_CONF" => Array(
			"NAME" => GetMessage("VIDEO_PATH_TO_VIDEO_CONF"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "/services/video/detail.php?ID=#ID#",
			"COLS" => 25,
			"PARENT" => "URL_TEMPLATES",
		),
		"PATH_TO_VIDEO_LIST" => Array(
			"NAME" => GetMessage("VIDEO_PATH_TO_VIDEO_LIST"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "/services/video/",
			"COLS" => 25,
			"PARENT" => "URL_TEMPLATES",
		),
		"PATH_TO_USER" => Array(
			"NAME" => GetMessage("VIDEO_PATH_TO_USER"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "/company/personal/user/#user_id#/",
			"COLS" => 25,
			"PARENT" => "URL_TEMPLATES",
		),
		"PATH_TO_MESSAGES_CHAT" => Array(
			"NAME" => GetMessage("VIDEO_PATH_TO_MESSAGES_CHAT"),
			"TYPE" => "STRING",
			"MULTIPLE" => "N",
			"DEFAULT" => "/company/personal/messages/chat/#user_id#/",
			"COLS" => 25,
			"PARENT" => "URL_TEMPLATES",
		),
		"SET_TITLE" => Array(),
		
	)
);
?>