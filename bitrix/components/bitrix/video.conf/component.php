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

$arParams["ID"] = IntVal($arParams["ID"]);
$arParams["IBLOCK_ID"] = IntVal($arParams["IBLOCK_ID"]);
if($arParams["IBLOCK_ID"] <= 0)
	return;

$arParams["PATH_TO_VIDEO_CONF"] = trim($arParams["PATH_TO_VIDEO_CONF"]);
if (strlen($arParams["PATH_TO_VIDEO_CONF"]) <= 0)
	$arParams["PATH_TO_VIDEO_CONF"] = htmlspecialchars("/services/video/detail.php?ID=#ID#");
	
$arParams["PATH_TO_VIDEO_LIST"] = trim($arParams["PATH_TO_VIDEO_LIST"]);
if (strlen($arParams["PATH_TO_VIDEO_LIST"]) <= 0)
	$arParams["PATH_TO_VIDEO_LIST"] = "/services/video/";
	
$arParams["PATH_TO_USER"] = trim($arParams["PATH_TO_USER"]);
if (strlen($arParams["PATH_TO_USER"]) <= 0)
	$arParams["PATH_TO_USER"] = "/company/personal/user/#user_id#/";
	
$arParams["PATH_TO_MESSAGES_CHAT"] = trim($arParams["PATH_TO_MESSAGES_CHAT"]);
if (strlen($arParams["PATH_TO_MESSAGES_CHAT"]) <= 0)
	$arParams["PATH_TO_MESSAGES_CHAT"] = "/company/personal/messages/chat/#user_id#/";

$arParams["AJAX_CALL"] = ($_REQUEST["AJAX_CALL"] == "Y" ? true : false);
CAjax::Init();

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
	$arResult["isVideoCall"] = false;

	if($arParams["ID"] > 0)
	{
		CTimeZone::Disable();

		$arFilter = Array(
			"IBLOCK_ID" => $arParams["IBLOCK_ID"],
			"ID" => $arParams["ID"],
			"ACTIVE" => "Y",
		);
		
		$arSelect = Array(
			"ID", "NAME", "ACTIVE_FROM", "ACTIVE", "ACTIVE_TO", "IBLOCK_ID", "CREATED_BY",
			//"PROPERTY_CONF_PWD", "PROPERTY_MEMBERS", "PROPERTY_ACTIVE_MEMBERS",
		);
		$dbItem = CIBlockElement::GetList(Array(), $arFilter, false, Array("nTopCount" => 1), $arSelect);
		if($arDbItem = $dbItem->GetNextElement())
		{
			$arItem = $arDbItem->GetFields();
			$arItem["PROP_MEMBERS"] = $arDbItem->GetProperty("MEMBERS");
			$arItem["PROP_ACTIVE_MEMBERS"] = $arDbItem->GetProperty("ACTIVE_MEMBERS");
			$arItem["PROP_CONF_PWD"] = $arDbItem->GetProperty("CONF_PWD");
			$arItem["PROP_VIDEOCALL"] = $arDbItem->GetProperty("VIDEOCALL");

			if($arItem["PROP_VIDEOCALL"]["VALUE"] == "Y")
				$arResult["isVideoCall"] = true;

			if(is_array($arItem["PROP_MEMBERS"]["VALUE"]) && in_array($arResult["UserId"], $arItem["PROP_MEMBERS"]["VALUE"]))
			{
				$arResult["currentConf"] = $arItem;
					
				$arResult["counterActiveTo"] = MakeTimeStamp($arItem["ACTIVE_TO"]) - time();
				
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
					$video->arParams["isVideoCall"] = $arResult["isVideoCall"];
					
					if($video->isModuleReady())
					{
						if((time() >= MakeTimeStamp($arItem["ACTIVE_FROM"]) && time() < MakeTimeStamp($arItem["ACTIVE_TO"])) || $arResult["isVideoCall"])
						{
							if($arParams["SET_TITLE"] == "Y" && $arResult["isVideoCall"])
								$APPLICATION->SetTitle($arResult["currentConf"]["NAME"]);

							$arResult["conferenceId"] = $arResult["currentConf"]["ID"];
							$arResult["conferencePwd"] = $arResult["currentConf"]["PROP_CONF_PWD"]["VALUE"];
							$arResult["maxUsers"] = COption::GetOptionInt("video", "video-room-users", 6);

							$video->arParams["conferenceId"] = $arResult["conferenceId"];
							$video->arParams["conferencePwd"] = $arResult["conferencePwd"];
							$video->arParams["conferenceName"] = $arResult["currentConf"]["NAME"];
							$video->arParams["dateFrom"] = $arResult["currentConf"]["ACTIVE_FROM"];
							$video->arParams["dateTo"] = $arResult["currentConf"]["ACTIVE_TO"];
							$video->arParams["userFullName"] = $USER->GetFullName();
							$video->arParams["userLogin"] = $USER->GetLogin();

							
							if($_REQUEST["action"] == "endconf" && $arItem["CREATED_BY"] == $arResult["UserId"] && check_bitrix_sessid())
							{
								$activeTo = ConvertTimeStamp(false, "FULL");
								
								$el = new CIBlockElement;
								$el -> Update($arItem["ID"], Array("ACTIVE_TO" => $activeTo));
								LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_VIDEO_CONF"], array("ID" => $arItem["ID"])));
							}
							elseif($_REQUEST["action"] == "makeconf" && $arItem["CREATED_BY"] == $arResult["UserId"] && check_bitrix_sessid())
							{
								$el = new CIBlockElement;
								$el -> Update($arItem["ID"], Array("ACTIVE_TO" => ConvertTimeStamp(AddToTimeStamp(Array("MI" => 15)), "FULL"), "ACTIVE_FROM" => ConvertTimeStamp(false, "FULL")));

								CIBlockElement::SetPropertyValueCode($arItem["ID"], "VIDEOCALL", "");
								LocalRedirect(CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_VIDEO_CONF"], array("ID" => $arItem["ID"])));
							}

							if(!$arResult["isVideoCall"] && strlen($arResult["conferencePwd"]) <= 0)
							{
								$arResult["conferencePwd"] = "";
								$allChars = 'ABCDEFGHIJKLNMOPQRSTUVWXYZabcdefghijklnmopqrstuvwxyz0123456789';
								mt_srand((double) microtime() * 1000000);
								for ($i = 0; $i < 10; $i++)
									$arResult["conferencePwd"] .= $allChars[mt_rand(0, (strlen($allChars)-1))];
								CIBlockElement::SetPropertyValueCode($arItem["ID"], "CONF_PWD", $arResult["conferencePwd"]);
								
								$video->arParams["conferencePwd"] = $arResult["conferencePwd"];
								$video->createConference();
							}
							$video->startConference();
							
							if(strlen($video->arParams["conferencePwd"]) <= 0)
								$video->arParams["conferencePwd"] = $arResult["conferencePwd"];

							$arResult["activeMembers"] = Array();
							$arResult["activeMembers2Update"] = Array();
							$curTime = ConvertTimeStamp(false, "FULL");
							if(is_array($arResult["currentConf"]["PROP_ACTIVE_MEMBERS"]["VALUE"]))
							{
								foreach($arResult["currentConf"]["PROP_ACTIVE_MEMBERS"]["VALUE"] as $key => $value)
								{
									if(IntVal($value) > 0)
									{
										if(MakeTimeStamp($arResult["currentConf"]["PROP_ACTIVE_MEMBERS"]["DESCRIPTION"][$key]) > (time() - 60))
										{
											$arResult["activeMembers"][$value] = $arResult["currentConf"]["PROP_ACTIVE_MEMBERS"]["DESCRIPTION"][$key];
											if($value == $arResult["UserId"])
												$curUTime = $curTime;
											else
												$curUTime = $arResult["currentConf"]["PROP_ACTIVE_MEMBERS"]["DESCRIPTION"][$key];
											
											$arResult["activeMembers2Update"][] = Array(
													"VALUE" => $value,
													"DESCRIPTION" => $curUTime,
												);
										}
									}
								}
							}
							
							if(strlen($arResult["activeMembers"][$arResult["UserId"]]) <= 0)
							{
								$arResult["activeMembers"][$arResult["UserId"]] = $curTime;
								$arResult["activeMembers2Update"][]  = Array(
										"VALUE" => $arResult["UserId"],
										"DESCRIPTION" => $curTime,
									);
								$video->joinConferense($arResult["UserId"]);
							}
							CIBlockElement::SetPropertyValueCode($arItem["ID"], "ACTIVE_MEMBERS", $arResult["activeMembers2Update"]);

							$arResult["Members2Update"] = Array();
							foreach($arItem["PROP_MEMBERS"]["VALUE"] as $key => $val)
							{
								$rsUser = CUser::GetByID($val);
								if($arUser = $rsUser->Fetch())
								{
									if($arItem["CREATED_BY"] == $val)
										$arUser["OWNER"] = "Y";
									if(strlen($arResult["activeMembers"][$val]) > 0)	
										$arUser["ONLINE"] = "Y";

									$curParam = $arResult["currentConf"]["PROP_MEMBERS"]["DESCRIPTION"][$key];
									
									$arUser["VIDEO_PARAM"] = $curParam;
									$arResult["MEMBERS"][] = $arUser;
								}
							}
							$video->arParams["members"] = $arResult["MEMBERS"];

							if($arParams["AJAX_CALL"] && check_bitrix_sessid())
							{
								$APPLICATION->RestartBuffer();
								if($_REQUEST["action"] == "logout")
								{
									foreach($arResult["activeMembers2Update"] as $k => $v)
									{
										if($v["VALUE"] == $arResult["UserId"])
										{
											unset($arResult["activeMembers2Update"][$k]);
											unset($arResult["activeMembers"][$arResult["UserId"]]);
										}
									}
									CIBlockElement::SetPropertyValueCode($arItem["ID"], "ACTIVE_MEMBERS", $arResult["activeMembers2Update"]);
									$video->leaveConference($arResult["UserId"]);
								}
								elseif($_REQUEST["action"] == "prolong" && $arItem["CREATED_BY"] == $arResult["UserId"] && IntVal($_REQUEST["time"]) > 0 && !$arResult["isVideoCall"])
								{
									$time = IntVal($_REQUEST["time"]);
									$activeTo = ConvertTimeStamp(AddToTimeStamp(Array("MI" => $time), MakeTimeStamp($arItem["ACTIVE_TO"])), "FULL");
									
									//check if videorooms is avaible
									$Params = Array(
										"dateFrom" => $arItem["ACTIVE_FROM"],
										"dateTo" => $activeTo,
										"iblockId" => $arItem["IBLOCK_ID"],
										"ID" => $arItem["ID"],
										"regularity" => "NONE",
									);
									
									$check = CVideo::CheckRooms($Params);
									if ($check === true)
									{
										$el = new CIBlockElement;
										$el -> Update($arItem["ID"], Array("ACTIVE_TO" => $activeTo));
										echo "OK".($time*60 + $arResult["counterActiveTo"]);
										$video->prolongConference($time*60);
									}
									else
										echo "ERROR".GetMessage("VC_VIDEO_ROOM_BUSY");
								}
								elseif($_REQUEST["action"] == "update")
								{
									if(!$arResult["isVideoCall"] && $_REQUEST["isvideocall"] == "Y")
									{
										echo "ERROR";
									}
									else
									{
										$arAct = Array();
										foreach($arResult["activeMembers"] as $k => $v)
											$arAct[] = $k;
										echo "TIME".$arResult["counterActiveTo"].";";
										echo "ONLINEUSR".implode(",", $arAct).";";
										echo "USERS".count($arResult["currentConf"]["PROP_MEMBERS"]["VALUE"]).";";
										echo "MEMBERS";
										foreach($arResult["MEMBERS"] as $val)
											echo $val["ID"].":".$val["LAST_NAME"]." ".$val["NAME"].";";
									}
								}
								elseif($_REQUEST["action"] == "inviteuser" && $arItem["CREATED_BY"] == $arResult["UserId"] && IntVal($_REQUEST["user_id"]) > 0 && !$arResult["isVideoCall"])
								{
									$userId = IntVal($_REQUEST["user_id"]);
									if(!in_array($userId, $arResult["currentConf"]["PROP_MEMBERS"]["VALUE"]))
									{
										if(count($arResult["currentConf"]["PROP_MEMBERS"]["VALUE"]) < $arResult["maxUsers"])
										{
											$arResult["currentConf"]["PROP_MEMBERS"]["VALUE"][] = $userId;
											CIBlockElement::SetPropertyValueCode($arItem["ID"], "MEMBERS", $arResult["currentConf"]["PROP_MEMBERS"]["VALUE"]);
											if(CModule::IncludeModule("socialnetwork"))
											{
												$Url_ = CComponentEngine::MakePathFromTemplate($arParams["PATH_TO_VIDEO_CONF"], array("ID" => $arItem["ID"], "user_id" => $arResult["UserId"]));
												$Url = (CMain::IsHTTPS() ? "https://" : "http://").$_SERVER['HTTP_HOST'].$Url_;
												
												$mess = GetMessage('VC_MESS_INVITE', array('#OWNER_NAME#' => $USER->GetFullName(), '#TITLE#' => $arItem["NAME"]));
												$mess .= "\n\n".GetMessage('VC_MESS_INVITE_CONF', array('#LINK#' => $Url));
												$title = GetMessage('VC_INVITE_TITLE', array('#OWNER_NAME#' => $USER->GetFullName(), '#TITLE#' => $arItem["NAME"]));
												
												//notification to user
												$arMessageFields = array(
													"=DATE_CREATE" => $GLOBALS["DB"]->CurrentTimeFunction(),
													"MESSAGE_TYPE" => SONET_MESSAGE_SYSTEM,
													"FROM_USER_ID" => $arResult["UserId"],
													"TITLE" => $title,
													"TO_USER_ID" => $userId,
													"MESSAGE" => $mess,
													"EMAIL_TEMPLATE" => "VIDEO_CONF_USER_INVITE",
												);

												$res = CSocNetMessages::Add($arMessageFields);
											}
										}
									}
								}
								elseif($_REQUEST["action"] == "expeluser" && $arItem["CREATED_BY"] == $arResult["UserId"] && IntVal($_REQUEST["user_id"]) > 0 && !$arResult["isVideoCall"])
								{
									$userId = IntVal($_REQUEST["user_id"]);
									if(in_array($userId, $arResult["currentConf"]["PROP_MEMBERS"]["VALUE"]))
									{
										foreach($arResult["currentConf"]["PROP_MEMBERS"]["VALUE"] as $key => $val)
										{
											if($val == $userId)
												unset($arResult["currentConf"]["PROP_MEMBERS"]["VALUE"][$key]);
										}
										CIBlockElement::SetPropertyValueCode($arItem["ID"], "MEMBERS", $arResult["currentConf"]["PROP_MEMBERS"]["VALUE"]);
									}
								}								
								elseif($_REQUEST["action"] == "param" && strlen($_REQUEST["param"]) > 0 && $arResult["isVideoCall"])
								{
									$param = trim($_REQUEST["param"]);
									foreach($arResult["currentConf"]["PROP_MEMBERS"]["VALUE"] as $key => $val)
									{
										$rsUser = CUser::GetByID($val);
										if($arUser = $rsUser->Fetch())
										{
											if($val == $arResult["UserId"])
												$curParam = $param;
											else
												$curParam = $arResult["currentConf"]["PROP_MEMBERS"]["DESCRIPTION"][$key];
											
											$arResult["Members2Update"][]  = Array(
													"VALUE" => $val,
													"DESCRIPTION" => $curParam,
												);
										}
									}
									CIBlockElement::SetPropertyValueCode($arItem["ID"], "MEMBERS", $arResult["Members2Update"]);
								}
								die();
							}
						
							if($arItem["CREATED_BY"] == $arResult["UserId"])
							{
								$arResult["IsOwner"] = "Y";
								if(!$arResult["isVideoCall"])
								{
									$Params = Array(
										"dateFrom" => $arItem["ACTIVE_FROM"],
										"dateTo" => ConvertTimeStamp(AddToTimeStamp(Array("MI" => 15), MakeTimeStamp($arItem["ACTIVE_TO"])), "FULL"),
										"iblockId" => $arItem["IBLOCK_ID"],
										"ID" => $arItem["ID"],
										"regularity" => "NONE",
									);

									$check = CVideo::CheckRooms($Params);
									if ($check === true)
									{
										$arResult["UrlToProlong"] = $APPLICATION->GetCurPageParam("action=prolong&time=#time#&AJAX_CALL=Y&".bitrix_sessid_get(), Array("action", "time", "AJAX_CALL", "sessid"));
										$arResult["canProlong"] = "Y";
									}
									
									$arResult["UrlToInviteUser"] = $APPLICATION->GetCurPageParam("action=inviteuser&AJAX_CALL=Y&user_id=#user_id#&".bitrix_sessid_get(), Array("action", "time", "AJAX_CALL", "sessid"));
									$arResult["UrlToExpelUser"] = $APPLICATION->GetCurPageParam("action=expeluser&AJAX_CALL=Y&user_id=#user_id#&".bitrix_sessid_get(), Array("action", "time", "AJAX_CALL", "sessid"));
									if(count($arResult["MEMBERS"]) < $arResult["maxUsers"])
										$arResult["CAN_INVITE"] = "Y";
								}
								else
								{
									$arResult["CanMakeConf"] = true;
									$arResult["UrlToMakeConf"] = $APPLICATION->GetCurPageParam("action=makeconf&".bitrix_sessid_get(), Array("action", "time", "AJAX_CALL", "sessid"));
								}
								$arResult["UrlToConfEnd"] = $APPLICATION->GetCurPageParam("action=endconf&".bitrix_sessid_get(), Array("action", "time", "AJAX_CALL", "sessid"));
							}
							
							$arResult["UrlToUpdate"] = $APPLICATION->GetCurPageParam("action=update&AJAX_CALL=Y&isvideocall=".(($arResult["isVideoCall"]) ? "Y" : "N")."&".bitrix_sessid_get(), Array("action", "time", "AJAX_CALL", "sessid"));
							$arResult["UrlToUpdateOnline"] = $APPLICATION->GetCurPageParam("action=online&AJAX_CALL=Y&".bitrix_sessid_get(), Array("action", "time", "AJAX_CALL", "sessid"));
							$arResult["UrlToUpdateOnlineUsers"] = $APPLICATION->GetCurPageParam("action=onlineusers&AJAX_CALL=Y&".bitrix_sessid_get(), Array("action", "time", "AJAX_CALL", "sessid"));
							$arResult["UrlToUpdateOnlineTime"] = $APPLICATION->GetCurPageParam("action=onlinetime&AJAX_CALL=Y&".bitrix_sessid_get(), Array("action", "time", "AJAX_CALL", "sessid"));
							$arResult["UrlToLogout"] = $APPLICATION->GetCurPageParam("action=logout&AJAX_CALL=Y&".bitrix_sessid_get(), Array("action", "time", "AJAX_CALL", "sessid"));
							$arResult["UrlToAddParam"] = $APPLICATION->GetCurPageParam("action=param&AJAX_CALL=Y&param=#param#&".bitrix_sessid_get(), Array("action", "time", "AJAX_CALL", "sessid"));
							$arResult["UrlToList"] = $arParams["PATH_TO_VIDEO_LIST"];

							if(strlen($arParams["PATH_TO_USER"]) > 0)
								$arResult["urlToUserProfile"] = $arParams["PATH_TO_USER"];
							if(strlen($arParams["PATH_TO_MESSAGES_CHAT"]) > 0)
								$arResult["urlToUserMessage"] = $arParams["PATH_TO_MESSAGES_CHAT"];
							
							$video->arParams["UrlToAddParam"] = $arResult["UrlToAddParam"];
							$arResult["video"] = $video;
						}
						elseif(time() < MakeTimeStamp($arItem["ACTIVE_FROM"]))
							$arResult["FatalError"] = GetMessage("VC_NOT_START", Array("#TIME#" => $arItem["ACTIVE_FROM"]));
						else
							$arResult["FatalError"] = GetMessage("VC_ENDED", Array("#TIME#" => $arItem["ACTIVE_TO"]));
					
					}
					else
						$arResult["NoteError"] = $video->getModuleHelp();
				}
				else
					$arResult["FatalError"] = GetMessage("VCT_NO_CLASS");
			}
			else
				$arResult["FatalError"] = GetMessage("VC_NOT_MEMBER");
		}
		else
			$arResult["FatalError"] = GetMessage("VC_NOT_FOUND");
			
		CTimeZone::Enable();
	}
	else
		$arResult["FatalError"] = GetMessage("VC_NO_ID");
}

$this->IncludeComponentTemplate();
?>