; /* /bitrix/js/pull/pull.min.js?151972746630882*/

; /* Start:"a:4:{s:4:"full";s:43:"/bitrix/js/pull/pull.min.js?151972746630882";s:6:"source";s:23:"/bitrix/js/pull/pull.js";s:3:"min";s:27:"/bitrix/js/pull/pull.min.js";s:3:"map";s:27:"/bitrix/js/pull/pull.map.js";}"*/
(function(window){if(!window.BX){if(typeof console=="object")console.log("PULL notice: bitrix core not loaded");return}if(window.BX.PULL){if(typeof console=="object")console.log("PULL notice: script is already loaded");return}var BX=window.BX,_revision=14,_updateStateVeryFastCount=0,_updateStateFastCount=0,_updateStateStep=60,_updateStateTimeout=null,_updateStateStatusTimeout=null,_updateStateSend=false,_pullTryAfterBxLink=false,_pullTryConnect=false,_pullPath=null,_pullMethod="PULL",_pullWithHeaders=true,_pullCapturePullEvent=false,_pullCapturePullEventStatus=false,_pullGetPullEventFunctionStatus=false,_pullTimeConfig=0,_pullTimeConfigShared=0,_pullTimeConst=new Date(2022,2,19).toUTCString(),_pullTime=_pullTimeConst,_pullTag=1,_pullTimeout=60,_pullMid=null,_watchTag={},_watchTimeout=null,_channelID=null,_channelClearReason=0,_channelClear=null,_channelLastID=0,_channelStack={},_WS=null,_wsPath="",_wsSupport=false,_wsConnected=false,_wsTryReconnect=0,_wsError1006Count=0,_mobileMode=false,_lsSupport=false,_escStatus=false,_sendAjaxTry=0,_confirm=null,_beforeUnload=false,_pathToAjax="/bitrix/components/bitrix/pull.request/ajax.php?",_onBeforeUnload=BX.proxy(function(){_beforeUnload=true;_pullTryConnect=false;if(_WS)_WS.close(1e3,"onbeforeunload");if(BX.PULL.returnPrivateVar("_pullTryAfterBxLink")){BX.PULL.tryConnectDelay()}},this);BX.PULL=function(){};BX.PULL.start=function(e){if(typeof e!="object"){e={}}_pullTryConnect=true;_mobileMode=false;if(e.MOBILE=="Y")_mobileMode=true;_lsSupport=true;if(e.LOCAL_STORAGE=="N")_lsSupport=false;if(e.HEADERS=="N")_pullWithHeaders=false;if(_lsSupport&&BX.localStorage.get("prs")!==null){_pullTryConnect=false}_wsSupport=true;if(e.WEBSOCKET=="N")_wsSupport=false;_userId=BX.message("USER_ID");if(typeof e.USER_ID!="undefined")_userId=e.USER_ID;BX.bind(window,"offline",function(){_pullTryConnect=false;if(_WS)_WS.close(1e3,"offline")});BX.bind(window,"online",function(){if(!BX.PULL.tryConnect())BX.PULL.updateState("10",true)});if(BX.browser.IsFirefox()){BX.bind(window,"keypress",function(e){if(e.keyCode==27)_escStatus=true})}if(_wsSupport&&!BX.PULL.supportWebSocket())_wsSupport=false;if(e.PATH_COMMAND){BX.PULL.setAjaxPath(e.PATH_COMMAND)}if(e.CHANNEL_ID){_channelID=e.CHANNEL_ID;_pullPath=BX.PULL.getModernPath(e);_wsPath=e.PATH_WS;_pullMethod=e.METHOD;e.CHANNEL_DT=e.CHANNEL_DT.toString().split("/");_pullTimeConfig=e.CHANNEL_DT[0];_pullTimeConfigShared=e.CHANNEL_DT[1]?e.CHANNEL_DT[1]:e.CHANNEL_DT[0];_pullTimeConfig=parseInt(_pullTimeConfig)+parseInt(BX.message("SERVER_TZ_OFFSET"))+parseInt(BX.message("USER_TZ_OFFSET"));_pullTimeConfigShared=parseInt(_pullTimeConfigShared)+parseInt(BX.message("SERVER_TZ_OFFSET"))+parseInt(BX.message("USER_TZ_OFFSET"));_channelLastID=parseInt(e.LAST_ID)}if(!BX.browser.SupportLocalStorage())_lsSupport=false;if(_lsSupport){BX.addCustomEvent(window,"onLocalStorageSet",BX.PULL.storageSet);BX.localStorage.set("pset",{CHANNEL_ID:_channelID,LAST_ID:_channelLastID,PATH:_pullPath,PATH_WS:_wsPath,TIME_LAST_GET:_pullTimeConfig,TIME_LAST_GET_SHARED:_pullTimeConfigShared,METHOD:_pullMethod},5)}BX.addCustomEvent("onImError",function(e){if(e=="AUTHORIZE_ERROR")_sendAjaxTry++});BX.addCustomEvent("onPullError",BX.delegate(function(e){if(e=="AUTHORIZE_ERROR"){_pullTryConnect=false}},this));if(BX.desktop){BX.desktop.addCustomEvent("BXLoginSuccess",function(){if(_WS)_WS.close(1e3,"desktop_login_success")})}BX.PULL.initBeforeUnload();BX.onCustomEvent(window,"onPullInit",[]);BX.PULL.expireConfig();BX.PULL.init()};BX.PULL.init=function(){BX.PULL.updateState("init");BX.PULL.updateWatch()};BX.PULL.getNowDate=function(e){var t=new Date;if(e==true)t=new Date(t.getFullYear(),t.getMonth(),t.getDate(),0,0,0);return Math.round(+t/1e3)+parseInt(BX.message("USER_TZ_OFFSET"))};BX.PULL.getDateDiff=function(e){var t=BX.message("USER_TZ_OFFSET");if(t==="")return 0;var n=BX.PULL.getNowDate()+parseInt(BX.message("SERVER_TZ_OFFSET"));var a=parseInt(e)+parseInt(BX.message("SERVER_TZ_OFFSET"));return n-a};BX.PULL.setTryAfterBxLink=function(e){_pullTryAfterBxLink=e?true:false};BX.PULL.initBeforeUnload=function(){BX.unbind(window,"beforeunload",_onBeforeUnload);BX.bind(window,"beforeunload",_onBeforeUnload)};BX.PULL.tryConnectDelay=function(){setTimeout(function(){BX.PULL.setPrivateVar("_pullTryConnect",false);BX.PULL.tryConnect();BX.PULL.setPrivateVar("_pullTryAfterBxLink",false)},1e3)};BX.PULL.expireConfig=function(){if(!_channelID)return false;clearTimeout(_channelClear);_channelClear=setTimeout(BX.PULL.expireConfig,6e4);if(_channelID&&_pullMethod!="PULL"&&_pullTimeConfig+43200<Math.round(+new Date/1e3)+parseInt(BX.message("SERVER_TZ_OFFSET"))+parseInt(BX.message("USER_TZ_OFFSET"))){_channelClearReason=1;_channelID=null;if(_WS)_WS.close(1e3,"expire_config_1")}else if(_channelID&&_pullMethod!="PULL"&&_pullTimeConfigShared+43200+(Math.floor(Math.random()*61)+10)*1e3<Math.round(+new Date/1e3)+parseInt(BX.message("SERVER_TZ_OFFSET"))+parseInt(BX.message("USER_TZ_OFFSET"))){_channelClearReason=1;_channelID=null;if(_WS)_WS.close(1e3,"expire_config_2")}};BX.PULL.tryConnect=function(){if(_pullTryConnect)return false;_pullTryConnect=true;BX.PULL.init();return true};BX.PULL.getChannelID=function(e,t,n){if(!_pullTryConnect)return false;n=n!=false;t=t==true;e=typeof e=="undefined"?"0":e;BX.ajax({url:_pathToAjax+"GET_CHANNEL&V="+_revision+"&CR="+_channelClearReason+"&CODE="+e.toUpperCase()+(_mobileMode?"&MOBILE":""),method:"POST",skipAuthCheck:true,dataType:"json",lsId:"PULL_GET_CHANNEL",lsTimeout:1,timeout:30,data:{PULL_GET_CHANNEL:"Y",SITE_ID:BX.message.SITE_ID?BX.message("SITE_ID"):"",MOBILE:_mobileMode?"Y":"N",CACHE:t?"N":"Y",PULL_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(e){_channelClearReason=0;if(n&&BX.localStorage.get("pgc")===null)BX.localStorage.set("pgc",t,1);if(typeof e=="object"&&e.ERROR==""){if(e.REVISION&&!BX.PULL.checkRevision(e.REVISION))return false;_channelID=e.CHANNEL_ID;_pullPath=BX.PULL.getModernPath(e);_wsPath=e.PATH_WS;_pullMethod=e.METHOD;var a=e.CHANNEL_DT.toString().split("/");_pullTimeConfig=a[0];_pullTimeConfigShared=a[1]?a[1]:a[0];_pullTimeConfig=parseInt(_pullTimeConfig)+parseInt(BX.message("SERVER_TZ_OFFSET"))+parseInt(BX.message("USER_TZ_OFFSET"));_pullTimeConfigShared=parseInt(_pullTimeConfigShared)+parseInt(BX.message("SERVER_TZ_OFFSET"))+parseInt(BX.message("USER_TZ_OFFSET"));_channelLastID=_pullMethod=="PULL"?e.LAST_ID:_channelLastID;e.TIME_LAST_GET=_pullTimeConfig;e.TIME_LAST_GET_SHARED=_pullTimeConfigShared;BX.PULL.updateState("11");BX.PULL.expireConfig();if(_lsSupport)BX.localStorage.set("pset",e,600)}else{_sendAjaxTry++;_channelClearReason=2;_channelID=null;clearTimeout(_updateStateStatusTimeout);BX.onCustomEvent(window,"onPullStatus",["offline"]);if(typeof e=="object"&&e.BITRIX_SESSID){BX.message({bitrix_sessid:e.BITRIX_SESSID})}if(typeof e=="object"&&e.ERROR=="SESSION_ERROR"){clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.updateState("12",true)},_sendAjaxTry<2?2e3:BX.PULL.tryConnectTimeout());BX.onCustomEvent(window,"onPullError",[e.ERROR,e.BITRIX_SESSID])}else if(typeof e=="object"&&e.ERROR=="AUTHORIZE_ERROR"){BX.onCustomEvent(window,"onPullError",[e.ERROR])}else{clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.updateState("31",true)},BX.PULL.tryConnectTimeout());BX.onCustomEvent(window,"onPullError",["NO_DATA"])}if(n&&typeof console=="object"){var l="\n========= PULL ERROR ===========\n"+"Error type: getChannel error\n"+"Error: "+e.ERROR+"\n"+"\n"+"Data array: "+JSON.stringify(e)+"\n"+"================================\n\n";console.log(l)}}},this),onfailure:BX.delegate(function(e){_sendAjaxTry++;_channelClearReason=3;_channelID=null;clearTimeout(_updateStateStatusTimeout);BX.onCustomEvent(window,"onPullStatus",["offline"]);if(e=="timeout"){clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.updateState("1")},1e4)}else{if(typeof e=="object"&&e.ERROR=="auth"){BX.onCustomEvent(window,"onPullError",["AUTHORIZE_ERROR"])}if(typeof console=="object"){var t="\n========= PULL ERROR ===========\n"+"Error type: getChannel onfailure\n"+"Error: "+e.ERROR+"\n"+"\n"+"Data array: "+JSON.stringify(e)+"\n"+"================================\n\n";console.log(t)}clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.updateState("14",true)},BX.PULL.tryConnectTimeout())}},this)})};BX.PULL.updateState=function(e,t){if(!_pullTryConnect||_updateStateSend)return false;e=typeof e=="undefined"?"":e;if(_channelID==null||_pullPath==null||_wsSupport&&_wsPath===null){clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){if(e.length>0)BX.PULL.getChannelID(e+(_channelID==null?"-02":"-03"));else BX.PULL.getChannelID(_channelID==null?"2":"3")},Math.floor(Math.random()*151)+50)}else{if(_wsSupport&&_wsPath&&_wsPath.length>1&&_pullMethod!="PULL")BX.PULL.connectWebSocket();else BX.PULL.connectPull(t)}};BX.PULL.connectWebSocket=function(){if(!_wsSupport)return false;_updateStateSend=true;var e=_wsPath.replace("#DOMAIN#",location.hostname);var t=e+(_pullTag!=null?"&tag="+_pullTag:"")+(_pullTime!=null?"&time="+_pullTime:"")+(_pullMid!==null?"&mid="+_pullMid:"");try{BX.onCustomEvent(window,"onPullStatus",["connect"]);_WS=new WebSocket(t)}catch(n){_wsPath=null;_updateStateSend=false;clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.updateState("33")},BX.PULL.tryConnectTimeout());return false}_WS.onopen=function(){_wsConnected=true;clearTimeout(_updateStateStatusTimeout);BX.onCustomEvent(window,"onPullStatus",["online"])};_WS.onclose=function(e){var t=typeof e.code!="undefined"?e.code:"NA";var n="";if(e.reason){try{n=JSON.parse(e.reason)}catch(a){n={reason:e.reason}}}var l=false;_updateStateSend=false;var o=true;if(!_wsConnected){l=true;_channelID=null;if(_wsTryReconnect==1){BX.PULL.updateState("ws-"+t+"-1")}else if(_wsTryReconnect<=3){clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.updateState("ws-"+t+"-2")},1e4)}else{if(t==1006||t==1008){BX.localStorage.set("pbws",true,600);_wsSupport=false}clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.updateState("ws-"+t+"-3")},BX.PULL.tryConnectTimeout())}if(t==1006){if(_wsError1006Count>=5){BX.localStorage.set("pbws",true,300);_wsSupport=false}_wsError1006Count++}}else{_wsConnected=false;if(e.wasClean&&n&&n.http_status==403){_sendAjaxTry++;_channelID=null;_channelLastID=0;_channelStack={};if(_sendAjaxTry>=5){BX.localStorage.set("pbws",true,86400);_wsSupport=false}clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.getChannelID("ws-"+t+"-6",true)},_sendAjaxTry<2?1e3:BX.PULL.tryConnectTimeout())}else{clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.updateState("ws-"+t+"-5-"+e.wasClean)},_sendAjaxTry<2&&e.wasClean===true?1e3:BX.PULL.tryConnectTimeout())}}if(_beforeUnload){_beforeUnload=false}else{BX.onCustomEvent(window,"onPullError",["RECONNECT",t]);if(typeof console=="object"){var u="\n========= PULL INFO ===========\n"+"time: "+new Date+"\n"+"type: websocket close\n"+"code: "+t+"\n"+"clean: "+(e.wasClean?"Y":"N")+"\n"+"never connect: "+(l?"Y":"N")+"\n"+"send connect request: "+(o?"Y":"N")+"\n"+(n?"reason: "+JSON.stringify(n)+"\n":"")+"\n"+"Data array: "+JSON.stringify(e)+"\n"+"================================\n\n";console.log(u)}}};_WS.onmessage=function(e){var t=0;var n=e.data.match(/#!NGINXNMS!#(.*?)#!NGINXNME!#/gm);if(n!=null){_wsTryReconnect=0;_sendAjaxTry=0;for(var a=0;a<n.length;a++){n[a]=n[a].substring(12,n[a].length-12);if(n[a].length<=0)continue;var l=BX.parseJSON(n[a]);var o=null;if(l&&l.text)o=l.text;if(o!==null&&typeof o=="object"){if(o&&o.ERROR==""){if(l.id){l.id=parseInt(l.id);l.channel=l.channel?l.channel:o.CHANNEL_ID?o.CHANNEL_ID:l.time;if(!_channelStack[""+l.channel+l.id]){_channelStack[""+l.channel+l.id]=l.id;if(_channelLastID<l.id)_channelLastID=l.id;BX.PULL.executeMessages(o.MESSAGE,{SERVER_TIME:l.time,SERVER_TIME_WEB:o.SERVER_TIME_WEB})}}}else{BX.onCustomEvent(window,"onPullStatus",["offline"]);if(typeof console=="object"){var u="\n========= PULL ERROR ===========\n"+"Error type: updateState fetch\n"+"Error: "+(o&&o.ERROR?o.ERROR:"unknown")+"\n"+"\n"+"Connect CHANNEL_ID: "+_channelID+"\n"+"Connect WS_PATH: "+_wsPath+"\n"+"\n"+"Data array: "+JSON.stringify(o)+"\n"+"================================\n\n";console.log(u)}_channelClearReason=4;_channelID=null}}if(l.tag)_pullTag=l.tag;if(l.time)_pullTime=l.time;if(l.mid)_pullMid=l.mid;t++}}if(_channelID==null){if(_WS)_WS.close(1e3,"onmessage")}};_WS.onerror=function(){_updateStateSend=false;_wsTryReconnect++}};BX.PULL.connectPull=function(e){e=e==true;clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){if(!_pullPath||typeof _pullPath!="string"||_pullPath.length<=32){_pullPath=null;clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.updateState("17")},1e4);return false}_updateStateStatusTimeout=setTimeout(function(){BX.onCustomEvent(window,"onPullStatus",["online"])},5e3);_updateStateSend=true;var e=[];if(_pullWithHeaders){e=[{name:"If-Modified-Since",value:_pullTime},{name:"If-None-Match",value:_pullTag}]}BX.onCustomEvent(window,"onPullStatus",["connect"]);var t=_pullPath.replace("#DOMAIN#",location.hostname);var n=BX.ajax({url:_pullMethod=="PULL"?t:t+(_pullTag!=null?"&tag="+_pullTag:"")+(_pullTime!=null?"&time="+_pullTime:"")+(_pullMid!==null?"&mid="+_pullMid:"")+"&rnd="+ +new Date,skipAuthCheck:true,skipBxHeader:_pullMethod!="PULL",method:_pullMethod=="PULL"?"POST":"GET",dataType:_pullMethod=="PULL"?"json":"html",timeout:_pullTimeout,headers:e,data:_pullMethod=="PULL"?{PULL_UPDATE_STATE:"Y",CHANNEL_ID:_channelID,CHANNEL_LAST_ID:_channelLastID,SITE_ID:BX.message.SITE_ID?BX.message("SITE_ID"):"",PULL_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()}:{},onsuccess:function(e){clearTimeout(_updateStateStatusTimeout);_updateStateSend=false;if(_WS)_WS.close(1e3,"ajax_onsuccess");if(_pullMethod=="PULL"&&typeof e=="object"){if(e.ERROR==""){BX.onCustomEvent(window,"onPullStatus",["online"]);_sendAjaxTry=0;BX.PULL.executeMessages(e.MESSAGE,{SERVER_TIME:(new Date).toUTCString(),SERVER_TIME_WEB:Math.round(+new Date/1e3)});if(_lsSupport)BX.localStorage.set("pus",{MESSAGE:e.MESSAGE},5)}else{clearTimeout(_updateStateStatusTimeout);BX.onCustomEvent(window,"onPullStatus",["offline"]);if(e&&e.BITRIX_SESSID){BX.message({bitrix_sessid:e.BITRIX_SESSID})}if(e.ERROR=="SESSION_ERROR"){BX.onCustomEvent(window,"onPullError",[e.ERROR,e.BITRIX_SESSID])}else{BX.onCustomEvent(window,"onPullError",[e.ERROR])}if(typeof console=="object"){var t="\n========= PULL ERROR ===========\n"+"Error type: updateState error\n"+"Error: "+(e&&e.ERROR?e.ERROR:"unknown")+"\n"+"\n"+"Connect CHANNEL_ID: "+_channelID+"\n"+"Connect PULL_PATH: "+_pullPath+"\n"+"\n"+"Data array: "+JSON.stringify(e)+"\n"+"================================\n\n";console.log(t)}_channelClearReason=5;_channelID=null}if(_channelID!=null&&_lsSupport)BX.localStorage.set("pset",{CHANNEL_ID:_channelID,LAST_ID:_channelLastID,PATH:_pullPath,PATH_WS:_wsPath,TAG:_pullTag,MID:_pullMid,TIME:_pullTime,TIME_LAST_GET:_pullTimeConfig,TIME_LAST_GET_SHARED:_pullTimeConfigShared,METHOD:_pullMethod},600);BX.PULL.setUpdateStateStep()}else{if(e.length>0){var a=0;_sendAjaxTry=0;var l=e.match(/#!NGINXNMS!#(.*?)#!NGINXNME!#/gm);if(l!=null){for(var o=0;o<l.length;o++){l[o]=l[o].substring(12,l[o].length-12);if(l[o].length<=0)continue;var u=BX.parseJSON(l[o]);var e=null;if(u&&u.text)e=u.text;if(e!==null&&typeof e=="object"){if(e&&e.ERROR==""){if(u.id){u.id=parseInt(u.id);u.channel=u.channel?u.channel:e.CHANNEL_ID?e.CHANNEL_ID:u.time;if(!_channelStack[""+u.channel+u.id]){_channelStack[""+u.channel+u.id]=u.id;if(_channelLastID<u.id)_channelLastID=u.id;BX.PULL.executeMessages(e.MESSAGE,{SERVER_TIME:u.time,SERVER_TIME_WEB:e.SERVER_TIME_WEB})}}}else{if(typeof console=="object"){var t="\n========= PULL ERROR ===========\n"+"Error type: updateState fetch\n"+"Error: "+(e&&e.ERROR?e.ERROR:"unknown")+"\n"+"\n"+"Connect CHANNEL_ID: "+_channelID+"\n"+"Connect PULL_PATH: "+_pullPath+"\n"+"\n"+"Data array: "+JSON.stringify(e)+"\n"+"================================\n\n";console.log(t)}_channelClearReason=6;_channelID=null;clearTimeout(_updateStateStatusTimeout);BX.onCustomEvent(window,"onPullStatus",["offline"])}}else{if(typeof console=="object"){var t="\n========= PULL ERROR ===========\n"+"Error type: updateState parse\n"+"\n"+"Connect CHANNEL_ID: "+_channelID+"\n"+"Connect PULL_PATH: "+_pullPath+"\n"+"\n"+"Data string: "+l[o]+"\n"+"================================\n\n";console.log(t)}_channelClearReason=7;_channelID=null;clearTimeout(_updateStateStatusTimeout);BX.onCustomEvent(window,"onPullStatus",["offline"])}if(u.tag)_pullTag=u.tag;if(u.time)_pullTime=u.time;if(u.mid)_pullMid=u.mid;a++}}else{if(typeof console=="object"){var t="\n========= PULL ERROR ===========\n"+"Error type: updateState error getting message\n"+"\n"+"Connect CHANNEL_ID: "+_channelID+"\n"+"Connect PULL_PATH: "+_pullPath+"\n"+"\n"+"Data string: "+e+"\n"+"================================\n\n";console.log(t)}_channelClearReason=8;_channelID=null;clearTimeout(_updateStateStatusTimeout);BX.onCustomEvent(window,"onPullStatus",["offline"])}if(a>0||n&&n.status==0){BX.PULL.updateState(a>0?"19":"20")}else{_channelClearReason=9;_channelID=null;clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.updateState("21")},1e4)}}else{if(n&&(n.status==304||n.status==0)){if(n.status==0){if(_escStatus){_escStatus=false;BX.PULL.updateState("22-3")}else{_updateStateTimeout=setTimeout(function(){BX.PULL.updateState("22-2")},3e4)}}else{try{var i=n.getResponseHeader("Expires");if(i==="Thu, 01 Jan 1973 11:11:01 GMT"){var s=n.getResponseHeader("Last-Message-Id");if(_pullMid===null&&s&&s.length>0){_pullMid=s}}}catch(r){}BX.PULL.updateState("22-1")}}else if(n&&(n.status==502||n.status==500)){clearTimeout(_updateStateStatusTimeout);BX.onCustomEvent(window,"onPullStatus",["offline"]);_sendAjaxTry++;_channelClearReason=10;_channelID=null;clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.updateState("23")},BX.PULL.tryConnectTimeout())}else{clearTimeout(_updateStateStatusTimeout);BX.onCustomEvent(window,"onPullStatus",["offline"]);_sendAjaxTry++;_channelClearReason=11;_channelID=null;var _=BX.PULL.tryConnectTimeout();var p=n&&typeof n.status!="undefined"?n.status:"NaN";clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.updateState("24-"+p+"-"+_/1e3)},_)}}}},onfailure:function(e){clearTimeout(_updateStateStatusTimeout);BX.onCustomEvent(window,"onPullStatus",["offline"]);_updateStateSend=false;_sendAjaxTry++;if(_WS)_WS.close(1e3,"ajax_onfailure");if(e=="timeout"){if(_pullMethod=="PULL")BX.PULL.setUpdateStateStep();else BX.PULL.updateState("25")}else if(n&&(n.status==403||n.status==404||n.status==400)){if(n.status==403){_channelLastID=0;_channelStack={}}_channelClearReason=12;_channelID=null;clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.getChannelID("7-"+n.status,n.status==403)},_sendAjaxTry<2?50:BX.PULL.tryConnectTimeout())}else if(n&&(n.status==500||n.status==502)){_channelClearReason=13;_channelID=null;clearTimeout(_updateStateTimeout);_updateStateTimeout=setTimeout(function(){BX.PULL.getChannelID("8-"+n.status)},_sendAjaxTry<2?50:BX.PULL.tryConnectTimeout())}else{if(typeof console=="object"){var t="\n========= PULL ERROR ===========\n"+"Error type: updateState onfailure\n"+"\n"+"Connect CHANNEL_ID: "+_channelID+"\n"+"Connect PULL_PATH: "+_pullPath+"\n"+"\n"+"Data array: "+JSON.stringify(e)+"\n"+"================================\n\n";console.log(t)}clearTimeout(_updateStateTimeout);if(_pullMethod=="PULL")_updateStateTimeout=setTimeout(BX.PULL.setUpdateStateStep,1e4);else _updateStateTimeout=setTimeout(function(){BX.PULL.updateState("26")},1e4)}}})},e?150:(_pullMethod=="PULL"?_updateStateStep:.3)*1e3)};BX.PULL.extendWatch=function(e,t){if(!e||e.length<=0)return false;_watchTag[e]=true;if(t===true)BX.PULL.updateWatch(true)};BX.PULL.clearWatch=function(e){if(e=="undefined")_watchTag={};else if(_watchTag[e])delete _watchTag[e]};BX.PULL.updateWatch=function(e){if(!_pullTryConnect)return false;e=e==true;clearTimeout(_watchTimeout);_watchTimeout=setTimeout(function(){var e=[];for(var t in _watchTag){if(_watchTag.hasOwnProperty(t)){e.push(t)}}if(e.length>0){BX.ajax({url:_pathToAjax+"UPDATE_WATCH&V="+_revision+"",method:"POST",dataType:"json",timeout:30,lsId:"PULL_WATCH_"+location.pathname,lsTimeout:5,data:{PULL_UPDATE_WATCH:"Y",WATCH:e,SITE_ID:BX.message.SITE_ID?BX.message("SITE_ID"):"",PULL_AJAX_CALL:"Y",sessid:BX.bitrix_sessid()},onsuccess:BX.delegate(function(e){BX.onCustomEvent(window,"onAfterUpdateWatch",[e.RESULT]);for(var t in e.RESULT){if(!e.RESULT[t]){delete _watchTag[t]}}BX.localStorage.set("puw",location.pathname,5)},this)})}BX.PULL.updateWatch()},e?5e3:174e4)};BX.PULL.executeMessages=function(e,t,n){t=t===null?{SERVER_TIME:(new Date).toUTCString(),SERVER_TIME_WEB:Math.round(+new Date/1e3)}:t;n=n!==false;for(var a=0;a<e.length;a++){e[a].module_id=e[a].module_id.toLowerCase();if(e[a].id){e[a].id=parseInt(e[a].id);if(_channelStack[""+_channelID+e[a].id])continue;else _channelStack[""+_channelID+e[a].id]=e[a].id;if(_channelLastID<e[a].id)_channelLastID=e[a].id}e[a].params["SERVER_TIME_WEB"]=parseInt(t.SERVER_TIME_WEB);e[a].params["SERVER_TIME"]=t.SERVER_TIME;if(e[a].module_id=="pull"){if(n){if(e[a].command=="channel_die"&&typeof e[a].params.replace=="object"){BX.PULL.updateChannelID({METHOD:_pullMethod,LAST_ID:_channelLastID,CHANNEL_ID:_channelID,CHANNEL_DT:_pullTimeConfig+"/"+e[a].params.replace.CHANNEL_DIE,PATH:_pullPath.replace(e[a].params.replace.PREV_CHANNEL_ID,e[a].params.replace.CHANNEL_ID),PATH_WS:_wsPath?_wsPath.replace(e[a].params.replace.PREV_CHANNEL_ID,e[a].params.replace.CHANNEL_ID):_wsPath})}else if(e[a].command=="channel_die"||e[a].command=="config_die"){_channelClearReason=14;_channelID=null;_pullPath=null;if(_wsPath)_wsPath=null;if(_WS)_WS.close(1e3,"config_die")}else if(e[a].command=="server_restart"){BX.PULL.tryConnectSet(0,false);BX.localStorage.set("prs",true,600);if(_WS)_WS.close(1e3,"server_restart");setTimeout(function(){BX.PULL.tryConnect()},(Math.floor(Math.random()*61)+60)*1e3+6e5)}}}else{if(!(e[a].module_id=="main"&&e[a].command=="user_counter"))BX.PULL.setUpdateStateStepCount(1,4);try{e[a].params["PULL_TIME_AGO"]=BX.PULL.getDateDiff(e[a].params["SERVER_TIME_WEB"]+parseInt(BX.message("USER_TZ_OFFSET")));if(e[a].module_id=="online"){if(e[a].params["PULL_TIME_AGO"]<120)BX.onCustomEvent(window,"onPullOnlineEvent",[e[a].command,e[a].params],true)}else{BX.onCustomEvent(window,"onPullEvent-"+e[a].module_id,[e[a].command,e[a].params],true);BX.onCustomEvent(window,"onPullEvent",[e[a].module_id,e[a].command,e[a].params],true)}}catch(l){if(typeof console=="object"){console.log("\n========= PULL ERROR ===========\n"+"Error type: onPullEvent onfailure\n"+"Error event: ",l,"\n"+"Message: ",e[a],"\n"+"================================\n");BX.debug(l)}}}}};BX.PULL.setUpdateStateStep=function(e){var e=e!=false;var t=60;if(_updateStateVeryFastCount>0){t=10;_updateStateVeryFastCount--}else if(_updateStateFastCount>0){t=20;_updateStateFastCount--}_updateStateStep=parseInt(t);BX.PULL.updateState("27");if(e&&_lsSupport)BX.localStorage.set("puss",_updateStateStep,5)};BX.PULL.setUpdateStateStepCount=function(e,t){_updateStateVeryFastCount=parseInt(e);_updateStateFastCount=parseInt(t)};BX.PULL.storageSet=function(e){if(e.key=="pus"){BX.PULL.executeMessages(e.value.MESSAGE,null,false)}else if(e.key=="pgc"){BX.PULL.getChannelID("9",e.value,false)}else if(e.key=="puss"){_updateStateStep=70;BX.PULL.updateState("28")}else if(e.key=="pset"){_channelID=e.value.CHANNEL_ID;_channelLastID=e.value.LAST_ID;_pullPath=e.value.PATH;_wsPath=e.value.PATH_WS;_pullMethod=e.value.METHOD;if(e.value.TIME)_pullTime=e.value.TIME;if(e.value.TAG)_pullTag=e.value.TAG;if(e.value.MID)_pullMid=e.value.MID;if(e.value.TIME_LAST_GET)_pullTimeConfig=e.value.TIME_LAST_GET;if(e.value.TIME_LAST_GET_SHARED)_pullTimeConfigShared=e.value.TIME_LAST_GET_SHARED;if(_channelID!=null){if(!BX.PULL.tryConnect())BX.PULL.updateState("29",true)}}else if(e.key=="puw"){if(e.value==location.pathname)BX.PULL.updateWatch()}};BX.PULL.setAjaxPath=function(e){_pathToAjax=e.indexOf("?")==-1?e+"?":e+"&"};BX.PULL.updateChannelID=function(e){if(typeof e!="object")return false;var t=e.METHOD;var n=e.CHANNEL_ID;var a=BX.PULL.getModernPath(e);var l=e.LAST_ID;var o=e.PATH_WS;if(typeof n=="undefined"||typeof a=="undefined")return false;if(n==_channelID&&a==_pullPath&&o==_wsPath)return false;_channelID=n;e.CHANNEL_DT=e.CHANNEL_DT.toString().split("/");_pullTimeConfig=e.CHANNEL_DT[0];_pullTimeConfigShared=e.CHANNEL_DT[1]?e.CHANNEL_DT[1]:e.CHANNEL_DT[0];_pullTimeConfig=parseInt(_pullTimeConfig)+parseInt(BX.message("SERVER_TZ_OFFSET"))+parseInt(BX.message("USER_TZ_OFFSET"));_pullTimeConfigShared=parseInt(_pullTimeConfigShared)+parseInt(BX.message("SERVER_TZ_OFFSET"))+parseInt(BX.message("USER_TZ_OFFSET"));_pullPath=a;_wsPath=o;_channelLastID=_pullMethod=="PULL"&&typeof l=="number"?l:_channelLastID;if(typeof t=="string")_pullMethod=t;if(_lsSupport)BX.localStorage.set("pset",{CHANNEL_ID:_channelID,LAST_ID:_channelLastID,PATH:_pullPath,PATH_WS:_wsPath,TAG:_pullTag,MID:_pullMid,TIME:_pullTime,TIME_LAST_GET:_pullTimeConfig,TIME_LAST_GET_SHARED:_pullTimeConfigShared,METHOD:_pullMethod},600);if(_WS)_WS.close(1e3,"channel_die");return true};BX.PULL.tryConnectTimeout=function(){var e=0;if(_sendAjaxTry<=2)e=15e3;else if(_sendAjaxTry>2&&_sendAjaxTry<=5)e=45e3;else if(_sendAjaxTry>5&&_sendAjaxTry<=10)e=6e5;else if(_sendAjaxTry>10){_pullTryConnect=false;e=36e5}return e};BX.PULL.tryConnectSet=function(e,t){if(typeof e=="number")_sendAjaxTry=parseInt(e);if(typeof t=="boolean")_pullTryConnect=t};BX.PULL.getPullServerStatus=function(){return _pullMethod!="PULL"};BX.PULL.capturePullEvent=function(e){e=typeof e=="boolean"?e:true;if(!_pullCapturePullEvent&&e){_pullCapturePullEvent=true;_pullCapturePullEventStatus=true;BX.addCustomEvent("onPullOnlineEvent",function(e,t){if(_pullCapturePullEventStatus){console.log("onPullOnlineEvent",e,t)}});BX.addCustomEvent("onPullEvent",function(e,t,n){if(_pullCapturePullEventStatus){console.log("onPullEvent",e,t,n)}if(_pullGetPullEventFunctionStatus){console.log('BX.onCustomEvent(window, "onPullEvent-'+e+'", ["'+t+'", '+JSON.stringify(n)+"]);")}});return'Capture "Pull Event" started.'}else{_pullCapturePullEventStatus=e;return'Capture "Pull Event" is '+(e?"ON":"OFF")}};BX.PULL.getPullEventFunction=function(e){_pullGetPullEventFunctionStatus=typeof e=="boolean"?e:true;BX.PULL.capturePullEvent(_pullGetPullEventFunctionStatus);return'Get "Pull Event" function is '+(_pullGetPullEventFunctionStatus?"ON":"OFF")};BX.PULL.getDebugInfo=function(){if(!console||!console.log||!JSON||!JSON.stringify)return false;var e=JSON.stringify(_watchTag);var t="\n========= PULL DEBUG ===========\n"+"UserId: "+_userId+" "+(_userId>0?"":"(guest)")+"\n"+"Connect: "+(_updateStateSend?"Y":"N")+"\n"+"WebSocket connect: "+(_wsConnected?"Y":"N")+"\n"+"LocalStorage status: "+(_lsSupport?"Y":"N")+"\n"+"WebSocket support: "+(_wsSupport&&_wsPath.length>0?"Y":"N")+"\n"+"Queue Server: "+(_pullMethod=="PULL"?"N":"Y")+"\n"+"Try connect: "+(_pullTryConnect?"Y":"N")+"\n"+"Try number: "+_sendAjaxTry+"\n"+"\n"+"Path: "+_pullPath+"\n"+(_wsPath.length>0?"WebSocket Path: "+_wsPath+"\n":"")+"ChannelID: "+_channelID+"\n"+"ChannelDie: "+parseInt(_pullTimeConfig)+"\n"+"ChannelDieShared: "+parseInt(_pullTimeConfigShared)+"\n"+"\n"+"Last message: "+(_channelLastID>0?_channelLastID:"-")+"\n"+"Time init connect: "+_pullTimeConst+"\n"+"Time last connect: "+(_pullTime==_pullTimeConst?"-":_pullTime)+"\n"+"Watch tags: "+(e=="{}"?"-":e)+"\n"+"================================\n";return console.log(t)};BX.PULL.clearChannelId=function(e){e=e!=false;_channelClearReason=15;_channelID=null;_pullPath=null;if(_wsPath)_wsPath=null;if(_WS)_WS.close(1e3,"clear_channel_id");_updateStateSend=false;clearTimeout(_updateStateTimeout);if(e)BX.PULL.updateState("30")};BX.PULL.isWebSoketConnected=function(){return _wsConnected===true};BX.PULL.supportWebSocket=function(){var e=false;if(typeof WebSocket!="undefined"&&!BX.localStorage.get("pbws")){if(BX.browser.IsFirefox()||BX.browser.IsChrome()||BX.browser.IsOpera()||BX.browser.IsSafari()){if(BX.browser.IsFirefox()&&navigator.userAgent.substr(navigator.userAgent.indexOf("Firefox/")+8,2)>=25)e=true;else if(BX.browser.IsChrome()&&navigator.appVersion.substr(navigator.appVersion.indexOf("Chrome/")+7,2)>=28)e=true;else if(!BX.browser.IsChrome()&&BX.browser.IsSafari())e=true}else if(BX.browser.DetectIeVersion()>=10&&!BX.browser.IsAndroid()){e=true}}return e};BX.PULL.getModernPath=function(e){if(typeof e!="object")return"";var t="";if(typeof e.PATH!="undefined"&&typeof e.PATH_MOD!="undefined"&&e.PATH_MOD!=""){if(BX.browser.IsIE()||BX.browser.IsOpera()){t=e.PATH}else{t=e.PATH_MOD}}else if(typeof e.PATH!="undefined"){return e.PATH}return t};BX.PULL.getRevision=function(){return _revision};BX.PULL.getDebugInfoArray=function(){return{connected:_updateStateSend,websocket:_wsConnected,path:_pullPath}};BX.PULL.checkRevision=function(e){e=parseInt(e);if(typeof e=="number"&&_revision<e){BX.PULL.openConfirm(BX.message("PULL_OLD_REVISION"));_pullTryConnect=false;if(_WS)_WS.close(1e3,"check_revision");BX.onCustomEvent(window,"onPullRevisionUp",[e,this.revision]);return false}return true};BX.PULL.returnPrivateVar=function(v){return eval(v)};BX.PULL.setPrivateVar=function(va,ve){return eval(va+" = "+ve)};BX.PULL.openConfirm=function(e,t,n){if(_confirm!=null)_confirm.destroy();n=n!==false;if(typeof t=="undefined"||typeof t=="object"&&t.length<=0){t=[new BX.PopupWindowButton({text:BX.message("IM_NOTIFY_CONFIRM_CLOSE"),className:"popup-window-button-decline",events:{click:function(e){this.popupWindow.close();BX.PreventDefault(e)}}})]}_confirm=new BX.PopupWindow("bx-notifier-popup-confirm",null,{zIndex:200,autoHide:t===false,buttons:t,closeByEsc:t===false,overlay:n,events:{onPopupClose:function(){this.destroy()},onPopupDestroy:BX.delegate(function(){_confirm=null},this)},content:BX.create("div",{props:{className:t===false?" bx-messenger-confirm-without-buttons":"bx-messenger-confirm"},html:e})});_confirm.show();BX.bind(_confirm.popupContainer,"click",BX.PULL.preventDefault);BX.bind(_confirm.contentContainer,"click",BX.PreventDefault);BX.bind(_confirm.overlay.element,"click",BX.PreventDefault)};BX.PULL.closeConfirm=function(){if(_confirm!=null)_confirm.destroy()};BX.PULL.preventDefault=function(e){e=e||window.event;if(e.stopPropagation)e.stopPropagation();else e.cancelBubble=true};BX.PULL()})(window);
/* End */
;
//# sourceMappingURL=kernel_pull.map.js