<?
IncludeModuleLangFile(__FILE__);

class CVideoPort
{
	var $arParams = Array(
		"ScreenSize" => Array(
				Array("x" => 480, "y" => 409),
				Array("x" => 600, "y" => 500),
				Array("x" => 720, "y" => 590),
			),
		"windows" => Array(
			),
	);

	function init()
	{
		$this->arParams["ServerHost"] = COption::GetOptionString("videoport", "ServerHost", "");
		$this->arParams["ServerPort"] = COption::GetOptionString("videoport", "ServerPort", "");
		$this->arParams["SecretCode"] = COption::GetOptionString("videoport", "SecretCode", "");
		$this->arParams["windows"]["main"] = ((COption::GetOptionString("videoport", "window_main") == "Y") ? true : false);
		$this->arParams["windows"]["chat"] = ((COption::GetOptionString("videoport", "window_chat") == "Y") ? true : false);
		$this->arParams["windows"]["members"] = ((COption::GetOptionString("videoport", "window_members") == "Y") ? true : false);
		$this->arParams["windows"]["settings"] = ((COption::GetOptionString("videoport", "window_settings") == "Y") ? true : false);
		$this->arParams["windows"]["info"] = ((COption::GetOptionString("videoport", "window_info") == "Y") ? true : false);
		$this->arParams["windows"]["self_camera"] = ((COption::GetOptionString("videoport", "window_self_camera") == "Y") ? true : false);
		$this->arParams["windows"]["show_invite"] = ((COption::GetOptionString("videoport", "window_show_invite") == "Y") ? true : false);
	}
	
	function isModuleReady()
	{
		if(strlen($this->arParams["ServerHost"]) > 0)
			return true;
		return false;
	}
	
	function getModuleHelp()
	{
		return GetMessage("VP_INSTALL_HINTS");
	}
	
	function getMainWindow()
	{
		$dateTo = AddToTimeStamp(Array("HH" => 2), MakeTimeStamp($this->arParams["dateTo"]));
		
		if(!$this->arParams["isVideoCall"])
		{
			$hash = md5($this->arParams["conferencePwd"].$dateTo.$this->arParams["SecretCode"]);
			$p = '$2'.$this->arParams["conferencePwd"].'*'.$dateTo.'*'.$hash;
		}
		else
		{
			$hash = md5($this->arParams["conferencePwd"].$dateTo.$this->arParams["SecretCode"]);
			$p = '$2'.$this->arParams["conferencePwd"].'*'.$dateTo.'*'.$hash;
		}
		
		$videoWidth = $this->arParams["ScreenSize"][0]["x"];
		$videoHeight = $this->arParams["ScreenSize"][0]["y"];

		$screenSizeId = IntVal($_REQUEST["ScreenSizeId"]);
		if($screenSizeId > 0)
		{
			$videoWidth = $this->arParams["ScreenSize"][$screenSizeId]["x"];
			$videoHeight = $this->arParams["ScreenSize"][$screenSizeId]["y"];
		}
		
		$pathToIcon = (CMain::IsHTTPS() ? "https://" : "http://").$_SERVER["HTTP_HOST"]."/bitrix/video/videoport/images/user.png";
		
		$langID = "ru";
		if(LANGUAGE_ID == "de")
			$langID = "de";
		elseif(LANGUAGE_ID != "ru")
			$langID = "en";
			
		$GLOBALS["APPLICATION"]->SetAdditionalCSS("/bitrix/video/videoport/styles/wx.css");

		$res = '
		<script type="text/javascript" src="/bitrix/video/videoport/js/jquery.js"></script>
		<script type="text/javascript" src="/bitrix/video/videoport/js/run.js"></script>
		<script type="text/javascript" src="/bitrix/video/videoport/js/'.LANGUAGE_ID.'/run.js"></script>

		<script type="text/javascript">
			var wxInit = {
					ServerHost: "'.$this->arParams["ServerHost"].'",
					ServerPort: "'.$this->arParams["ServerPort"].'",
					LoginName: "*guest*'.CUtil::JSEscape(/*$GLOBALS["APPLICATION"]->ConvertCharset(*/$this->arParams["userFullName"]/*, SITE_CHARSET, "utf-8")*/).'",
					Password: "",
					SessionKey: "'.$p.'",';
		if(!$this->arParams["isVideoCall"])
		{
		
			$res .= '
					ConfName: "$c50_'.$this->arParams["conferenceId"].'",
					ConfPassword: "",';
		
		}
		else
		{
			$res .= '
					
					onIncomingCall: function(_userId) { c.wx.AcceptCall();},
					';
		}
		$res .= '					
					Width: '.$videoWidth.',
					Height: '.$videoHeight.',
					ServerUrl: "'.(CMain::IsHTTPS() ? 'https://' : 'http://').$_SERVER["HTTP_HOST"].'/bitrix/video/videoport",
					Language: "'.$langID.'",    
					ShowParticipantsPanel: false,   
					//onChatButtonClick: function(status) { writeLog(status)},					

					isCustomButtonEnabled: true,
					CustomButtonCaption: "'.GetMessage("VP_WRITE_USER").'",
					CustomButtonIconUrl: "'.$pathToIcon.'",
					onCustomButtonClick: function(UserID) {},

					//onHangUp: function(reason) {alert("'.GetMessage("VP_HANG_UP").'")},
					onLoad: function() {writeLog("'.GetMessage("VP_ONLOAD").'")},
					onLogin: function() {writeLog("'.GetMessage("VP_ONLOGIN").'")},
					onReady: function(userId) {onReadyCallback(userId)},
					onJoin: function() {writeLog("'.GetMessage("VP_ONJOIN").'");}
			};
			
			function onReadyCallback(userId)
			{
				writeLog("'.GetMessage("VP_READY").'");';
				if($this->arParams["isVideoCall"])
				{
					$res .= '
						url = "'.$this->arParams["UrlToAddParam"].'";
						url1 = url.replace("#param#", userId);
						jsAjaxUtil.LoadData(url1, nothing);
						';
					foreach($this->arParams["members"] as $val)
					{
						if($val["ID"] != $GLOBALS["USER"]->GetID())
							$this->arParams["calledUserIP"] = $val["VIDEO_PARAM"];
					}
					
					if(strlen($this->arParams["calledUserIP"]) > 0 && strpos($this->arParams["calledUserIP"], "@") !== false)
					{
						$res .= 'c.wx.StartPrivateCall("'.CUtil::JSEscape($this->arParams["calledUserIP"]).'");';
					}
				}
		
				$res .= '
			}
			
			var c = new vp.Controller(wxInit);
			c.Run();  	     
		</script>
		';
		return $res;
	}	

	function getSettingsWindow()
	{
		$res = "";
		
		$res = GetMessage("VP_SCREEN_SIZE")."<br />";
		$screenSizeId = IntVal($_REQUEST["ScreenSizeId"]);

		foreach($this->arParams["ScreenSize"] as $k => $v)
		{
			if(IntVal($screenSizeId) == IntVal($k))
				$res .= '<b>';
			$res .= '<a href="'.$GLOBALS["APPLICATION"]->GetCurPageParam("ScreenSizeId=".$k, Array("ScreenSizeId")).'">'.$v["x"]."x".$v["y"]."</a><br />";
			if(IntVal($screenSizeId) == IntVal($k))
				$res .= '</b>';
		}
		return $res;
	}	

	function getYourselfWindow()
	{
		
	}	

	function startConference()
	{

	}	

	function joinConferense($userId)
	{

	}	

	function leaveConference($userId)
	{
		
	}
	
	function prolongConference($time)
	{
		
	}

	function prolog()
	{
	
	}

	function epilog()
	{
	
	}
	function createConference()
	{

	}

}
?>