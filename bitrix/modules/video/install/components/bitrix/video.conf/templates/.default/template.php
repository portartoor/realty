<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<?
if ($arResult["NEED_AUTH"] == "Y")
{
	$APPLICATION->AuthForm(GetMessage("VCT_NEED_AUTH"));
}
elseif (strlen($arResult["FatalError"])>0)
{
	?>
	<div class="video-conf-warning">
		<span class='errortext'><?= $arResult["FatalError"] ?></span><br /><br />
	</div>
	<?
}
elseif (strlen($arResult["NoteError"])>0)
{
	?>
	<div class="video-conf-note">
		<span><?= $arResult["NoteError"] ?></span><br /><br />
	</div>
	<?
}
elseif(IntVal($arResult["conferenceId"]) > 0)
{
			$APPLICATION->RestartBuffer();
			?>
			<html>
			<head>
			<?$APPLICATION->ShowHead();?>
			<title><?$APPLICATION->ShowTitle()?></title>
			<?
	
			$js1 = '/bitrix/js/main/utils.js';
			$js2 = '/bitrix/js/main/popup_menu.js';
			?>
			<script type="text/javascript" src="<?=$js1?>?v=<?=filemtime($_SERVER['DOCUMENT_ROOT'].$js1)?>"></script>
			<script type="text/javascript" src="<?=$js2?>?v=<?=filemtime($_SERVER['DOCUMENT_ROOT'].$js2)?>"></script>
			<script>
				var WMVideo;
				var WMVideoView;
				phpVars.messLoading = '<?=GetMessage("VCT_LOADING")?>';
				function prolongVideo(time)
				{
					url = '<?=CUtil::JSEscape($arResult["UrlToProlong"])?>';
					url = url.replace('#time#', time);
					jsAjaxUtil.InsertDataToNode(url, 'video-time-remain-info', false);
				}

				function updateInfo()
				{
					url = '<?=CUtil::JSEscape($arResult["UrlToUpdate"])?>';
					jsAjaxUtil.InsertDataToNode(url, 'video-updates-info', false);
					setTimeout('updateInfo()', 20000);
				}

				function checkUpdates()
				{
					if(val = document.getElementById('video-updates-info'))
					{
						tmp = val.innerHTML;
						if(tmp.length > 0)
						{
							var pos = tmp.indexOf('TIME');
							var posOnl = tmp.indexOf('ONLINEUSR');
							var posUsr = tmp.indexOf('USERS');
							var posMem = tmp.indexOf('MEMBERS');
							if (pos != -1 && posOnl != -1 && posUsr != -1 && posMem != -1)
							{
								Tim = tmp.substr(pos+4, posOnl-5);
								Onl = tmp.substr(posOnl+9, posUsr-posOnl-10);
								Usr = tmp.substr(posUsr+5, posMem-posUsr-6);
								Mem = tmp.substr(posMem+7, tmp.length-posMem-7);
								
								document.getElementById('video-time-remain-info').innerHTML = 'OK'+Tim;

								arOnl = Onl.split(',');
								arMem = Mem.split(';');
								if(el = document.getElementById('video-online-members'))
									el.innerHTML = arOnl.length;
								i = 0;
								var arUsr = {};
								for (i in arMem)
								{ 
									usr = arMem[i].split(":");
									arUsr[i] = [usr[0], usr[1]];
								}
								if(el = document.getElementById('video-members-count'))
									el.innerHTML = i;
								
								AddUsers2Member(arUsr, arOnl);
								if(document.getElementById("inviteUserBlock"))
								{
									if(i >= <?=IntVal($arResult["maxUsers"])?>)
										document.getElementById("inviteUserBlock").style.display = "none";
									else
										document.getElementById("inviteUserBlock").style.display = "block";
								}
							}
							else	
								window.location.href='<?=CUtil::JSEscape($APPLICATION->GetCurPageParam())?>';
							val.innerHTML = '';
						}
					}
					setTimeout('checkUpdates()', 20000);
				}

				function ShowCounter(time)
				{
					if(val = document.getElementById('video-time-remain-info'))
					{
						tmp = val.innerHTML;
						if(tmp.length > 0)
						{
							var pos = tmp.indexOf('OK');
							if (pos != -1)
							{
								tmp = tmp.substr(2);
								time = tmp;
								val.innerHTML = '';
							}
							else
							{
								tmp = tmp.substr(5);
								erDiv = document.getElementById('video-time-remain-error');
								erDiv.innerHTML = tmp;
								erDiv.style.display = "block";
								val.innerHTML = '';
								document.getElementById('video-time-prolong').style.display = "none";
							}
						}
					}
					
					h = Math.floor(time/3600);
					m = Math.floor((time - h*3600)/60);
					s = Math.floor(time - h*3600 - m*60);
					time--;
					
					s1 = s;
					if(s < 10)
						s1 = '0'+s;
					m1 = m;
					if(m < 10)
						m1 = '0'+m;
					
					if(h > 0)
						v = h+":"+m1+":"+s1;
					else
						v = m1+":"+s1;
					
					if(document.getElementById("video-time-remain"))
						document.getElementById("video-time-remain").innerHTML = v;
					
					if(m == 0 && s == 0 && h == 0)
						window.location.href='<?=CUtil::JSEscape($APPLICATION->GetCurPageParam())?>';
					if(s == 0 && m == 0)
					{
						h--;
						m = 59;
						s = 59;
					}
					else if(s == 0)
					{
						m--;
						s = 59;
					}
					else
					{
						s--;
					}

					setTimeout('ShowCounter('+time+')',1000);
				}
				
				<?
				if(!$arResult["isVideoCall"] && $arResult["video"]->arParams["windows"]["info"])
				{
					?>ShowCounter(<?=$arResult["counterActiveTo"]?>);<?
				}
				?>
				setTimeout('updateInfo()', 20000);
				setTimeout('checkUpdates()', 22000); 
				<?if($arResult["CAN_INVITE"] != "Y" || !$arResult["video"]->arParams["windows"]["show_invite"])
				{
					?>
					if(document.getElementById("inviteUserBlock"))
						document.getElementById("inviteUserBlock").style.display = "none";
					<?
				}
				?>

				
				function inviteUser(arU)
				{
					if(arU)
					{
						var arUsr = {};
						for(j in arU)
						{
							uId = arU[j]['ID'];
							uName = arU[j]['NAME'];
							arUsr[j] = [uId, uName];
							
							url = '<?=CUtil::JSEscape($arResult["UrlToInviteUser"])?>';
							url1 = url.replace('#user_id#', uId);
							jsAjaxUtil.LoadData(url1, nothing);
						}
						
						AddUsers2Member(arUsr);
					}
				}
				
				function expelUser(userId)
				{
					if(userId > 0)
					{
						url = '<?=CUtil::JSEscape($arResult["UrlToExpelUser"])?>';
						url1 = url.replace('#user_id#', userId);
						jsAjaxUtil.LoadData(url1, nothing);
						document.getElementById('video-usr-' + userId).style.display = "none";
					}
				}

				function Logout()
				{
					url = '<?=CUtil::JSEscape($arResult["UrlToLogout"])?>';
					jsAjaxUtil.LoadData(url, nothing);
					window.close();
					window.location.href='<?=CUtil::JSEscape($arResult["UrlToList"])?>';
				}

				function nothing()
				{
				}

				function ShowInvite()
				{
					inviteU.SetValue([]);
					inviteU.Show();
				}

				function AddUsers2Member(arUser, arOnl)
				{
					for(i in arUser)
					{
						usr = arUser[i];
						if(usr[0] > 0 && usr[1].length > 0)
						{
							if(!document.getElementById('video-usr-'+usr[0]))
							{
								
								document.getElementById('video-usr-members').appendChild(jsUtils.CreateElement('DIV', {id : 'video-usr-'+usr[0]}));
								document.getElementById('video-usr-'+usr[0]).innerHTML = usr[1];
								document.getElementById('video-usr-'+usr[0]).className = 'video-usr';
								userId = usr[0];
								document.getElementById('video-usr-'+usr[0]).onclick= function () {ShowAuthMenu(userId);};
							}
							else
								document.getElementById('video-usr-'+usr[0]).style.display = "block";
							
							if(arOnl)
							{
								bOnl = false;
								for(j in arOnl)
								{
									if(arOnl[j] == usr[0])
										bOnl = true;
								}
								clN = document.getElementById('video-usr-'+usr[0]).className;
								if(bOnl)
								{
									if(clN.indexOf('video-usr-online') == -1)
										document.getElementById('video-usr-'+usr[0]).className = clN + ' video-usr-online';
								}
								else
								{
									document.getElementById('video-usr-'+usr[0]).className = clN.replace('video-usr-online', '');
								}
							}
						}
					}
				}
				
				function writeLog(str)
				{
					document.getElementById('video-call-log').innerHTML = str;
				}
				
				var items = [];
				
				<?if(strlen($arResult["urlToUserMessage"]) > 0):?>
					items[items.length] = {'ICONCLASS': 'vuser-button-message', 'TEXT': '<?=GetMessage("VCT_LINK_WRITE_MESSAGE")?>', 'ONCLICK1': 'window.open(\'<?=CUtil::JSEscape($arResult["urlToUserMessage"])?>\', \'\', \'location=yes,status=no,scrollbars=yes,resizable=yes,width=700,height=550,top='+Math.floor((screen.height - 550)/2-14)+',left='+Math.floor((screen.width - 700)/2-5)+'\'); return false;', 'TITLE': ''};
				<?endif;?>
				<?if(strlen($arResult["urlToUserProfile"]) > 0):?>
					items[items.length] = {'ICONCLASS': 'vuser-button-page', 'TEXT': '<?=GetMessage("VCT_LINK_VIEW_PROFILE")?>', 'ONCLICK1': 'window.open(\'<?=CUtil::JSEscape($arResult["urlToUserProfile"])?>\');', 'TITLE': ''};
				<?endif;?>
				<?if($arResult["IsOwner"] == "Y" && !$arResult["isVideoCall"]):?>
					items[items.length] = {'ICONCLASS': 'vuser-button-expel', 'TEXT': '<?=GetMessage("VCT_LINK_EXPEL_USER")?>', 'ONCLICK1': 'expelUser(\'#user_id#\')', 'TITLE': ''};
				<?endif;?>
				
				var xx_menu = new PopupMenu('xx_menu');
				function ShowAuthMenu(userId)
				{
					if(items.length > 0)
					{
						var itemsU = items;
						for(i in itemsU)
						{
							itemsU[i]['ONCLICK'] = itemsU[i]['ONCLICK1'].replace('#user_id#', userId);
						}
						el = document.getElementById('video-usr-'+userId);
						xx_menu.ShowMenu(el, itemsU);
					}
				}
			</script>
			</head>
			<body class="video-conf">
			<?
			echo $arResult["video"]->prolog();
			if (strlen($arResult["ErrorMessage"]) > 0)
			{
				?>
				<div class="video-conf-warning">
					<span class='errortext'><?= $arResult["ErrorMessage"] ?></span><br /><br />
				</div>
				<?
			}
			?>
			
			<table>
			<tr>
				<td width="80%" valign="top">
				<?if($arResult["video"]->arParams["windows"]["main"]):?>
					<table class="video-main-window video-window">
					<thead>
						<td class="video-window-left"></td>
						<td>
							<?$APPLICATION->ShowTitle()?>
						</td>
						<td align="right" valign="bottom">
							<a href="javascript:Logout();" title="<?=GetMessage("VCT_LOGOUT")?>" style="text-decoration: none;"><div class="video-call-logout"><?=GetMessage("VCT_LOGOUT")?></div></a>
						</td>
						<td class="video-window-right"></td>
					</thead>
					<tbody>
					<tr>
						<td></td>
						<td colspan="2" class="video-window-content">
							<span id="video-time-remain-error" style="display:none;"></span> 
							<?echo $arResult["video"]->getMainWindow()?>
						</td>
						<td></td>
					</tr>
					<tr>
					
					</tbody>
					<tfoot>
						<td class="video-window-left-bottom"></td>
						<td colspan="2"><div id="video-call-log"></div></td>
						<td class="video-window-right-bottom"></td>
					</tfoot>
					</table>
				<?endif;?>
				<?if($arResult["video"]->arParams["windows"]["chat"]):?>
					<table class="video-chat video-window">
					<thead>
						<td class="video-window-left"></td>
						<td><?=GetMessage("VCT_CHAT")?></td>
						<td class="video-window-right"></td>
					</thead>
					<tbody>
					<tr>
						<td></td>
						<td class="video-window-content">
							<div id="video-chat"></div>
						</td>
						<td></td>
					</tr>
					</tbody>
					<tfoot>
						<td class="video-window-left-bottom"></td>
						<td></td>
						<td class="video-window-right-bottom"></td>
					</tfoot>
					</table>
				<?endif;?>
				</td>
				<td width="20%" valign="top">
				<?if($arResult["video"]->arParams["windows"]["members"]):?>
					<table class="video-members video-window">
					<thead>
						<td class="video-window-left"></td>
						<td><?=GetMessage("VCT_MEMBERS")?></td>
						<td class="video-window-right"></td>
					</thead>
					<tbody>
					<tr>
						<td></td>
						<td class="video-window-content">
							<div id="video-usr-members">
							<?
							foreach($arResult["MEMBERS"] as $val)
							{
								?>
								<div id="video-usr-<?=$val["ID"]?>" class="video-usr<?if($val["ONLINE"] == "Y") echo " video-usr-online"; if($val["OWNER"] == "Y") echo " video-usr-owner";?>" onclick="ShowAuthMenu('<?=$val["ID"]?>');">
									<?
									echo $val["LAST_NAME"]." ".$val["NAME"];
									//echo $val["VIDEO_PARAM"];
									
									?>
								</div>
								<?
							}
							?>
							</div>
							<?if($arResult["CAN_INVITE"] == "Y" && $arResult["video"]->arParams["windows"]["show_invite"]):?>
								<span id="inviteUserBlock">
										<a href="javascript:ShowInvite();" title="<?=GetMessage("VCT_TITLE_JOIN")?>"><div class="add-usr-link"><?=GetMessage("VCT_TITLE_JOIN")?></div></a>
								</span>
								<?
								$APPLICATION->IncludeComponent("bitrix:intranet.user.search", ".default", array(
											'SHOW_INPUT' => 'N',
											'SHOW_BUTTON'=>'N',
											'NAME' => 'inviteU',
											'ONSELECT' => 'inviteUser',
											'GET_FULL_INFO' => 'Y',
											'MULTIPLE' => 'Y',
											'SITE_ID' => SITE_ID,
											"IS_EXTRANET" => (CModule::IncludeModule('extranet') && CExtranet::IsExtranetSite()) ? "Y" : "N",
									),
									false
								);
								?>
							<?endif;?>
							<?if($arResult["isVideoCall"] && $arResult["CanMakeConf"])
							{
								?>
								<a href="<?=$arResult["UrlToMakeConf"]?>" title="<?=GetMessage("VCT_CALL_TO_CONF")?>"><?=GetMessage("VCT_CALL_TO_CONF")?></a>
								<?
							}
							?>
						</td>
						<td></td>
					</tr>
					</tbody>
					<tfoot>
						<td class="video-window-left-bottom"></td>
						<td></td>
						<td class="video-window-right-bottom"></td>
					</tfoot>
					</table>
				<?endif;?>
				
				<span id="video-time-remain-info" style="display:none;"></span> 
				<span id="video-updates-info" style="display:none;"></span> 
				<?if($arResult["video"]->arParams["windows"]["self_camera"]):?>
					<table class="video-self-video video-window">
					<thead>
						<td class="video-window-left"></td>
						<td><?=GetMessage("VCT_CAMERA")?></td>
						<td class="video-window-right"></td>
					</thead>
					<tbody>
					<tr>
						<td></td>
						<td class="video-window-content">
							<?=$arResult["video"]->getYourselfWindow()?>
						</td>
						<td></td>
					</tr>
					</tbody>
					<tfoot>
						<td class="video-window-left-bottom"></td>
						<td></td>
						<td class="video-window-right-bottom"></td>
					</tfoot>
					</table>					
				<?endif;?>
				
				<?if($arResult["video"]->arParams["windows"]["info"] && !$arResult["isVideoCall"]):?>
					<table class="video-info video-window">
					<thead>
						<td class="video-window-left"></td>
						<td><?=GetMessage("VCT_INFORMATION")?></td>
						<td class="video-window-right"></td>
					</thead>
					<tbody>
					<tr>
						<td></td>
						<td class="video-window-content">
							<?=GetMessage("VCT_REMAIN")?>&nbsp;<span id="video-time-remain"></span>
							<?if($arResult["IsOwner"] == "Y" && $arResult["canProlong"] == "Y")
							{
								?>
								<span id="video-time-prolong"><br /><a href="javascript:prolongVideo('15');"><?=GetMessage("VCT_PROLONG")?>&nbsp;15&nbsp;<?=GetMessage("VCT_PROLONG_MIN")?></a></span>
								<?
							}
							?>
							<br />
							<?=GetMessage("VCT_COUNT_MEMBERS")?>&nbsp;<span id="video-members-count"><?=count($arResult["MEMBERS"])?></span><br />
							<?=GetMessage("VCT_COUNT_ACTIVE_MEMBERS")?>&nbsp;<span id="video-online-members"><?=count($arResult["activeMembers"])?></span><br />
							<?=GetMessage("VCT_COUNT_MAX_USERS")?>&nbsp;<?=$arResult["maxUsers"]?><br />
							<?if($arResult["IsOwner"] == "Y")
							{
								?>
								<a href="<?=$arResult["UrlToConfEnd"]?>" title="<?=GetMessage("VCT_END_CONF")?>"><?=GetMessage("VCT_END_CONF")?></a>
								<?
							}
							?>
						</td>
						<td></td>
					</tr>
					</tbody>
					<tfoot>
						<td class="video-window-left-bottom"></td>
						<td></td>
						<td class="video-window-right-bottom"></td>
					</tfoot>
					</table>
				<?endif;?>
				<?if($arResult["video"]->arParams["windows"]["settings"]):?>
					<table class="video-settings video-window">
					<thead>
						<td class="video-window-left"></td>
						<td><?=GetMessage("VCT_SETTINGS")?></td>
						<td class="video-window-right"></td>
					</thead>
					<tbody>
					<tr>
						<td></td>
						<td class="video-window-content">
							<?echo $arResult["video"]->getSettingsWindow()?>
						</td>
						<td></td>
					</tr>
					</tbody>
					<tfoot>
						<td class="video-window-left-bottom"></td>
						<td></td>
						<td class="video-window-right-bottom"></td>
					</tfoot>
					</table>
				<?endif;?>

				</td>
			</tr>
			</table>
			<?
			echo $arResult["video"]->epilog();
			?>
			</body>
			</html>
			<?
			die();
}
else
{
	?>
	<div class="video-conf-warning">
		<span class='errortext'><?=GetMessage("VCT_CONF_NOT_FOUND")?></span><br />
	</div>
	<?
}