<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();


if (!CModule::IncludeModule("video"))
{
	ShowError(GetMessage("VIDEO_MODULE_NOT_INSTALL"));
	return;
}

if (!CModule::IncludeModule("iblock"))
{
	ShowError(GetMessage("VIDEO_IB_MODULE_NOT_INSTALL"));
	return;
}

$arParams["IBLOCK_ID"] = IntVal($arParams["IBLOCK_ID"]);
if($arParams["IBLOCK_ID"] <= 0)
	return;

$arParams["PATH_TO_VIDEO_CONF"] = trim($arParams["PATH_TO_VIDEO_CONF"]);
if (strlen($arParams["PATH_TO_VIDEO_CONF"]) <= 0)
	$arParams["PATH_TO_VIDEO_CONF"] = htmlspecialchars("detail.php?ID=#ID#");

if($arParams["SET_TITLE"] == "Y")
	$APPLICATION->SetTitle(GetMessage("VIDEO_PAGE_TITLE"));
	
if (!$GLOBALS["USER"]->IsAuthorized())
{	
	$arResult["NEED_AUTH"] = "Y";
}
else
{
	$arResult["Error"] = "";
	$arResult["UserId"] = $USER->GetID();

	CTimeZone::Disable();
	
	$arAvConf = Array();
	$arFilter = Array(
		"IBLOCK_ID" => $arParams["IBLOCK_ID"],
		"ACTIVE" => "Y",
		"PROPERTY_MEMBERS" => $arResult["UserId"],
		">DATE_ACTIVE_TO" => ConvertTimeStamp(false, "FULL"),
		"<=DATE_ACTIVE_FROM" => ConvertTimeStamp(false, "FULL"),
	);
	
	$arSelect = Array(
		"ID", "NAME", "ACTIVE_FROM", "ACTIVE", "ACTIVE_TO", "IBLOCK_ID", "DETAIL_TEXT", "CREATED_BY",
		//"PROPERTY_CONF_PWD", "PROPERTY_MEMBERS",
	);
	$dbItem = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
	while($arDbItem = $dbItem->GetNextElement())
	{
		$arItem = $arDbItem->GetFields();
		
		if($_REQUEST["action"] == "endconf" && check_bitrix_sessid() && IntVal($_REQUEST["ID"]) > 0 && IntVal($_REQUEST["ID"]) == $arItem["ID"] && $arItem["CREATED_BY"] == $arResult["UserId"])
		{
			$activeTo = ConvertTimeStamp(false, "FULL");
			
			$el = new CIBlockElement;
			$el -> Update($arItem["ID"], Array("ACTIVE_TO" => $activeTo));
			continue;
		}

		$arItem["PROP_MEMBERS"] = $arDbItem->GetProperty("MEMBERS");
		$arItem["Url"] = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_VIDEO_CONF"], array("ID" => $arItem["ID"]));
		
		$lTime = IntVal((MakeTimeStamp($arItem["ACTIVE_TO"], FORMAT_DATETIME) - time())/60);
		$arItem["lTimeH"] = 0;
		$arItem["lTimeM"] = $lTime;
		
		if($lTime >= 60)
		{
			$arItem["lTimeH"] = floor($lTime/60);
			$arItem["lTimeM"] = $lTime - $arItem["lTimeH"]*60;
		}
			
		foreach($arItem["PROP_MEMBERS"]["VALUE"] as $val)
		{
			$rsUser = CUser::GetByID($val);
			if($arUser = $rsUser->Fetch())
			{
				if($arItem["CREATED_BY"] == $val)
					$arUser["OWNER"] = "Y";
				$arItem["MEMBERS"][] = $arUser;
			}
		}
		
		if($arItem["CREATED_BY"] == $arResult["UserId"])
		{
			$arItem["UrlToConfEnd"] = $APPLICATION->GetCurPageParam("action=endconf&ID=".$arItem["ID"]."&".bitrix_sessid_get(), Array("action", "ID", "sessid"));
		}
	
		$arResult["arAvConf"][] = $arItem;
	}
	
	if(CModule::IncludeModule("videomost"))
	{
		class CVideoOut extends CVideoMost
		{
		}
	}
	elseif(CModule::IncludeModule("videoport"))
	{
		class CVideoOut extends CVideoPort
		{
		}
	}
	
	if(class_exists("CVideoOut"))
	{
	
		$video = new CVideoOut;
		$video->init();
		
		if(!$video->isModuleReady())
		{
			$arResult["NoteText"] = $video->getModuleHelp();
		}	
	}
	else
	{
		$arResult["NoteText"] = GetMessage("VCT_NO_CLASS");
	}
		
	CTimeZone::Enable();
}

$this->IncludeComponentTemplate();
?>