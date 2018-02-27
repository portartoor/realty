<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if (!CModule::IncludeModule("socialnetwork"))
{
	ShowError(GetMessage("VC_MODULE_NOT_INSTALL"));
	return;
}
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

$arParams["USER_ID"] = IntVal($arParams["USER_ID"]);

$arParams["PATH_TO_VIDEO_MEETING_DETAIL"] = trim($arParams["PATH_TO_VIDEO_MEETING_DETAIL"]);
if (strlen($arParams["PATH_TO_VIDEO_MEETING_DETAIL"]) <= 0)
	$arParams["PATH_TO_VIDEO_MEETING_DETAIL"] = "/services/video/detail.php?ID=#ID#";

if (!$GLOBALS["USER"]->IsAuthorized())
{	
	$arResult["NEED_AUTH"] = "Y";
}
else
{
	$arResult["FatalError"] = "";
	$arResult["Users"] = false;

	$dbUser = CUser::GetByID($USER->GetID());
	$arResult["UserSelf"] = $dbUser->GetNext();

	if($arResult["UserSelf"]["ID"] == $arParams["USER_ID"])
		$arResult["FatalError"] = GetMessage("VC_USER_YOURSELF");

	$dbUser = CUser::GetByID($arParams["USER_ID"]);
	$arResult["User"] = $dbUser->GetNext();
	if (!is_array($arResult["User"]))
		$arResult["FatalError"] = GetMessage("VC_USER_NO_USER");
	
	if (StrLen($arResult["FatalError"]) <= 0)
	{
		if ($arParams["SET_TITLE"]=="Y")
			$APPLICATION->SetTitle($arResult["User"]["NAME"]." ".$arResult["User"]["LAST_NAME"].": ".GetMessage("VC_PAGE_TITLE"));
	}

	if (StrLen($arResult["FatalError"]) <= 0)
	{
		$arFilter = Array(
			"IBLOCK_ID" => $arParams["IBLOCK_ID"],
			"ACTIVE" => "Y",
			"PROPERTY_MEMBERS" => Array($arResult["UserSelf"]["ID"], $arResult["User"]["ID"]),
			//">DATE_ACTIVE_TO" => ConvertTimeStamp(false, "FULL"),
			//"<=DATE_ACTIVE_FROM" => ConvertTimeStamp(false, "FULL"),
			"PROPERTY_VIDEOCALL" => "Y",
			);
		
		$arResult["conf"] = Array();
		$arSelect = Array(
			"ID", "NAME", "ACTIVE_FROM", "ACTIVE", "ACTIVE_TO", "IBLOCK_ID", "DETAIL_TEXT", "CREATED_BY",
			//"PROPERTY_CONF_PWD", "PROPERTY_MEMBERS",
			);
		$dbItem = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
		$bEnded = false;
		while($arDbItem = $dbItem->GetNextElement())
		{
			$arItem = $arDbItem->GetFields();
			$arItem["PROP_MEMBERS"] = $arDbItem->GetProperty("MEMBERS");
			
			if(is_array($arItem["PROP_MEMBERS"]["VALUE"]) && in_array($arResult["UserSelf"]["ID"], $arItem["PROP_MEMBERS"]["VALUE"]) && in_array($arResult["User"]["ID"], $arItem["PROP_MEMBERS"]["VALUE"]))
			{
				$arResult["conf"] = $arItem;
				continue;
			}
		}
		
		if(StrLen($arResult["FatalError"]) <= 0)
		{
			if(empty($arResult["conf"]))
			{
				if(CVideo::CanUserMakeCall())
				{
					$arFields = Array(
						"NAME" => GetMessage("VCC_MESS_CONF_TITLE", Array("#USER1#" => $arResult["UserSelf"]["~NAME"]." ".$arResult["UserSelf"]["~LAST_NAME"], "#USER2#" => $arResult["User"]["~NAME"]." ".$arResult["User"]["~LAST_NAME"])),
						"IBLOCK_ID" => $arParams["IBLOCK_ID"],
						//"ACTIVE_FROM" => ConvertTimeStamp(false, "FULL"),
						//"ACTIVE_TO" => ConvertTimeStamp(AddToTimeStamp(Array("MI" => 15)), "FULL"),
						"DETAIL_TEXT" => GetMessage("VCC_MESS_CONF_TITLE", Array("#USER1#" => $arResult["UserSelf"]["~NAME"]." ".$arResult["UserSelf"]["~LAST_NAME"], "#USER2#" => $arResult["User"]["~NAME"]." ".$arResult["User"]["~LAST_NAME"])),
						"PROPERTY_VALUES" => 
							Array(
								"MEMBERS" => Array($arResult["UserSelf"]["ID"], $arResult["User"]["ID"]),
								"EVENT_LENGTH" => "0.25",
								"PERIOD_TYPE" => "NONE",
								"UF_PERSONS" => 2,
								"VIDEOCALL" => "Y",
							),
						);
					/*
					$Params = Array(
						"iblockId" => $arFields["IBLOCK_ID"],
						"dateFrom" => $arFields["ACTIVE_FROM"],
						"dateTo" => $arFields["ACTIVE_TO"],
						"regularity" => "NONE",
					);

					$res = CVideo::CheckRooms($Params);
					if($res === true)
					{
					*/
						$arFilter = array("IBLOCK_ID" => $arFields["IBLOCK_ID"], "ACTIVE" => "Y");
						$arSelectFields = array("ID", "NAME", "DESCRIPTION", "IBLOCK_ID");
						$res = CIBlockSection::GetList(Array(), $arFilter, false, $arSelectFields);
						if($arMeeting = $res->GetNext())
							$arFields["IBLOCK_SECTION_ID"] = $arMeeting["ID"];

						$el = new CIBlockElement;
						$confID = $el -> Add($arFields);
						if(IntVal($confID) > 0)
						{
							$arFields["ID"] = $confID;
							$arResult["conf"] = $arFields;
						}
						else
						{
							if($e = $APPLICATION->GetException())
								$arResult["FatalError"] = GetMessage("VCC_MESS_CONF_ERROR_ADD").": ".$e->GetString();
							else
								$arResult["FatalError"] = GetMessage("VCC_MESS_CONF_ERROR_ADD");
						}

					/*
					}
					else
						$arResult["FatalError"] = GetMessage("VCC_MESS_CONF_BUSY");
					*/
				}
				else
					$arResult["FatalError"] = GetMessage("VCC_MESS_NO_RIGHTS");
			}
			
			if(!empty($arResult["conf"]))
			{
				if(CVideo::CanUserMakeCall())
				{
					$Url_ = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_VIDEO_MEETING_DETAIL"], array("ID" => $arResult["conf"]["ID"]));
					//notification to user
					if(CModule::IncludeModule("socialnetwork"))
					{
						$Url = (CMain::IsHTTPS() ? "https://" : "http://").$_SERVER['HTTP_HOST'].$Url_;

						$mess = GetMessage('VCC_MESS_INVITE', array('#OWNER_NAME#' => $USER->GetFullName()));
						$mess .= "\n\n".GetMessage('VCC_MESS_INVITE_CONF', array('#LINK#' => $Url));
						
						$title = GetMessage('VCC_INVITE_TITLE', array('#OWNER_NAME#' => $USER->GetFullName(), '#TITLE#' => $arResult["conf"]["NAME"]));
						$arMessageFields = array(
							"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
							"MESSAGE_TYPE" => SONET_MESSAGE_SYSTEM,
							"FROM_USER_ID" => $arResult["UserSelf"]["ID"],
							"TITLE" => $title,
							"TO_USER_ID" => $arResult["User"]["ID"],
							"MESSAGE" => $mess,
							"EMAIL_TEMPLATE" => "VIDEO_CALL_USER_INVITE",
						);

						$res = CSocNetMessages::Add($arMessageFields);
					}

					LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_VIDEO_MEETING_DETAIL"], array("ID" => $arResult["conf"]["ID"])));
				}
				else
					$arResult["FatalError"] = GetMessage("VCC_MESS_NO_RIGHTS");
			}
		}
	}
}

$this->IncludeComponentTemplate();
?>