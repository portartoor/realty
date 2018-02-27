; /* /bitrix/js/tasks/tasks.min.js?15197274653975*/
; /* /bitrix/js/tasks/util.min.js?15197274658536*/
; /* /bitrix/js/tasks/cjstask.min.js?151972746511284*/
; /* /bitrix/js/tasks/task-quick-popups.min.js?151972746518794*/
; /* /bitrix/js/tasks/core_planner_handler.min.js?151972746512534*/
; /* /bitrix/js/tasks/task-iframe-popup.min.js?151972746517646*/
; /* /bitrix/js/main/dd.js?151972746714772*/
; /* /bitrix/js/tasks/util/base.min.js?15197274657590*/
; /* /bitrix/js/tasks/util/query.min.js?15197274659539*/

; /* Start:"a:4:{s:4:"full";s:44:"/bitrix/js/tasks/tasks.min.js?15197274653975";s:6:"source";s:25:"/bitrix/js/tasks/tasks.js";s:3:"min";s:29:"/bitrix/js/tasks/tasks.min.js";s:3:"map";s:29:"/bitrix/js/tasks/tasks.map.js";}"*/
BX.namespace("Tasks");BX.mergeEx(BX.Tasks,{alert:function(e,t){var n=new BX.Promise;if(BX.Tasks.Runtime.errorPopup==null){BX.Tasks.Runtime.errorPopup=new BX.PopupWindow("task-error-popup",null,{lightShadow:true})}var i=BX.Tasks.Runtime.errorPopup;if(i===null){i=new BX.PopupWindow("task-error-popup",null,{lightShadow:true})}i.setButtons([new BX.PopupWindowButton({text:BX.message("JS_CORE_WINDOW_CLOSE"),className:"",events:{click:function(){if(BX.type.isFunction(t)){t()}this.popupWindow.close();n.resolve()}}})]);var o="";for(var s=0;s<e.length;s++){o+=BX.util.htmlspecialchars(typeof e[s].MESSAGE!=="undefined"?e[s].MESSAGE:e[s])+"<br>"}var r=BX.message("TASKS_COMMON_ERROR_OCCURRED");if(BX.type.isPlainObject(t)&&typeof t.title!="undefined"){r=t.title}i.setTitleBar({content:BX.type.isElementNode(r)?r:BX.create("div",{html:r})});i.setContent("<div style='width: 350px;padding: 10px; font-size: 12px; color: red;'>"+o+"</div>");if(window.console&&window.console.dir){window.console.dir(e)}i.show();return n},confirm:function(e,t,n){if(!BX.type.isFunction(t)){t=BX.DoNothing}n=n||{};n.ctx=n.ctx||this;var i=new BX.Promise(null,n.ctx);if(BX.Tasks.Runtime.confirmPopup==null){BX.Tasks.Runtime.confirmPopup=new BX.PopupWindow("task-confirm-popup",null,{zIndex:22e3,overlay:{opacity:50},content:"",autoHide:false,closeByEsc:false})}var o=n.isDisposable&&n.id&&"hintManager"in BX.Tasks.Util;var s=null;var r=n.buttonSet||[{text:BX.message("JS_CORE_WINDOW_CONTINUE"),type:"green",code:"continue","default":true}];if(o){if(BX.Tasks.Util.hintManager.wasDisposed(n.id)){var p=r.filter(function(e){return e.default});p=p[0]||r[0];i.fulfill(p.code);return i}else{s=BX.create("P",{style:{margin:"10px 20px 0 0"},children:[BX.create("LABEL",{children:[BX.create("INPUT",{props:{type:"checkbox",id:"bx-tasks-disposable-"+n.id}}),BX.create("SPAN",{style:{color:"gray"},text:BX.message("TASKS_COMMON_DONT_ASK_AGAIN")})]})]})}}var l=[];BX.Tasks.each(r,function(e){(function(e,t,n,o,s){e.push(new BX.PopupWindowButton({text:t.text,className:t.type=="red"?"popup-window-button-decline":"popup-window-button-accept",events:{click:function(){s.apply(n.ctx,[true]);this.popupWindow.close();if(o&&BX("bx-tasks-disposable-"+n.id).checked){BX.Tasks.Util.hintManager.disable(n.id)}i.fulfill(t.code);delete n}}}))})(l,e,n,o,t)});l.push(new BX.PopupWindowButtonLink({text:BX.message("JS_CORE_WINDOW_CANCEL"),events:{click:function(){t.apply(n.ctx,[false]);this.popupWindow.close();i.reject();delete n}}}));BX.Tasks.Runtime.confirmPopup.setButtons(l);if(typeof n.title!="undefined"){BX.Tasks.Runtime.confirmPopup.setTitleBar(BX.type.isElementNode(n.title)?n.title:BX.create("div",{html:n.title}))}e=BX.create("div",{style:{padding:"16px 12px",maxWidth:"400px",maxHeight:"400px",overflow:"hidden"},html:BX.type.isElementNode(e)?e.outerHTML:e.toString()});if(s){BX.append(s,e)}BX.Tasks.Runtime.confirmPopup.setContent(e.outerHTML);BX.Tasks.Runtime.confirmPopup.show();return i},confirmDelete:function(e){e=(e||"").toString();e=e.substr(0,1).toLowerCase()+e.substr(1);return this.confirm(BX.message("TASKS_COMMON_CONFIRM_DELETE").replace("#ENTITY_NAME#",e))},passCtx:function(e,t){return function(){var n=Array.prototype.slice.call(arguments);n.unshift(this);return e.apply(t,n)}},each:function(e,t,n){var i;n=n||this;if(BX.type.isArray(e)||e instanceof Object&&"length"in e){for(i=0;i<e.length;i++){if(e.hasOwnProperty(i)){if(t.apply(n,[e[i],i])===false){break}}}}else if(BX.type.isPlainObject(e)){for(i in e){if(e.hasOwnProperty(i)){if(t.apply(n,[e[i],i])===false){break}}}}},deReference:function(e,t){if(!BX.type.isNotEmptyString(e)){return null}e=e.split(".");var n=e.length;var i=t;for(var o=0;o<n;o++){if(e.hasOwnProperty(o)){if(typeof i=="undefined"||i===null){return null}if(!BX.type.isNotEmptyString(e[o])){return null}i=i[e[o].trim()]}else{return null}}return i}});if(typeof BX.Tasks.Runtime=="undefined"){BX.Tasks.Runtime={errorPopup:null,confirmPopup:null}}
/* End */
;
; /* Start:"a:4:{s:4:"full";s:43:"/bitrix/js/tasks/util.min.js?15197274658536";s:6:"source";s:24:"/bitrix/js/tasks/util.js";s:3:"min";s:28:"/bitrix/js/tasks/util.min.js";s:3:"map";s:28:"/bitrix/js/tasks/util.map.js";}"*/
BX.namespace("Tasks.Util");BX.mergeEx(BX.Tasks.Util,{formatTimeAmount:function(e,t){e=parseInt(e);if(isNaN(e)){return""}var i=e<0?"-":"";e=Math.abs(e);var n=""+Math.floor(e/3600);var s=""+Math.floor(e/60)%60;var a=""+e%60;var o=function(e){return"00".substring(0,2-e.length)+e};var u=o(n)+":"+o(s);if(!t||t=="HH:MI:SS"){u+=":"+o(a)}return i+u},delay:function(e,t,i,n){e=e||BX.DoNothing;t=t||BX.DoNothing;i=i||300;n=n||this;var s=null;var a=function(){var t=arguments;s=setTimeout(function(){e.apply(n,t)},i)};a.cancel=function(){t.apply(n,[]);clearTimeout(s)};return a},showByClass:function(e){if(BX.hasClass(e,"invisible")){BX.removeClass(e,"invisible")}},hideByClass:function(e){if(!BX.hasClass(e,"invisible")){BX.addClass(e,"invisible")}},fadeToggleByClass:function(e,t,i){return BX.Tasks.Util.animateShowHide({node:e,duration:t,toShow:{opacity:100},toHide:{opacity:0},complete:i})},fadeSlideToggleByClass:function(e,t,i){return BX.Tasks.Util.animateShowHide({node:e,duration:t,toShow:{opacity:100,height:BX.Tasks.Util.getInvisibleSize(e).height},toHide:{opacity:0,height:0},complete:i})},fadeSlideHToggleByClass:function(e,t,i){return BX.Tasks.Util.animateShowHide({node:e,duration:t,toShow:{opacity:100,width:BX.Tasks.Util.getInvisibleSize(e).width},toHide:{opacity:0,width:0},complete:i})},animateShowHide:function(e){e=e||{};var t=e.node||null;if(!BX.type.isElementNode(t)){var i=new BX.Promise;i.reject();return i}var n=BX.hasClass(t,"invisible");var s=typeof e.way=="undefined"||e.way===null?n:!!e.way;if(n!=s){var i=new BX.Promise;i.resolve();return i}var a=e.toShow||{};var o=e.toHide||{};return BX.Tasks.Util.animate({node:t,duration:e.duration,start:!s?a:o,finish:s?a:o,complete:function(){BX[!s?"addClass":"removeClass"](t,"invisible");t.style.cssText="";if(BX.type.isFunction(e.complete)){e.complete.call(this)}},step:function(e){if(typeof e.opacity!="undefined"){t.style.opacity=e.opacity/100}if(typeof e.height!="undefined"){t.style.height=e.height+"px"}if(typeof e.width!="undefined"){t.style.width=e.width+"px"}}})},animate:function(e){e=e||{};var t=e.node||null;var i=new BX.Promise;if(!BX.type.isElementNode(t)){i.reject();return i}var n=e.duration||300;var s=BX.Tasks.Runtime;if(typeof s.animations=="undefined"){s.animations=[]}var a=null;for(var o in s.animations){if(s.animations[o].node==t){a=s.animations[o];break}}if(a===null){var u=new BX.easing({duration:n,start:e.start,finish:e.finish,transition:BX.easing.transitions.linear,step:e.step,complete:function(){for(var n in s.animations){if(s.animations[n].node==t){s.animations[n].easing=null;s.animations[n].node=null;s.animations.splice(n,1);break}}t=null;a=null;e.complete.call(this);if(i){i.fulfill()}}});a={node:t,easing:u};s.animations.push(a)}else{a.easing.stop();if(i){i.reject()}}a.easing.animate();return i},getInvisibleSize:function(e){var t=BX.hasClass(e,"invisible");if(t){BX.removeClass(e,"invisible")}var i=BX.pos(e);if(t){BX.addClass(e,"invisible")}return i},isEnter:function(e){return this.getKeyFromEvent(e)==13},isEsc:function(e){return this.getKeyFromEvent(e)==27},getKeyFromEvent:function(e){e=e||window.event;return e.keyCode||e.which},filterFocusBlur:function(e,t,i,n){if(!BX.type.isElementNode(e)){return false}var s=false;t=t||BX.DoNothing;i=i||BX.DoNothing;n=n||50;var a=function(e,a){if(e){if(s!=false){clearTimeout(s);s=false}else{t.apply(this,a)}}else{s=setTimeout(function(){s=false;i.apply(this,a)},n)}};BX.bind(e,"blur",function(){a.apply(this,[false,arguments])});BX.bind(e,"focus",function(){a.apply(this,[true,arguments])});return true},bindInstantChange:function(e,t,i){if(!BX.type.isElementNode(e)){return BX.DoNothing}i=i||e;var n=e.value;var s=BX.debounce(function(s){if(e.value.toString()!=n.toString()){t.apply(i,arguments);n=e.value}},3,i);BX.bind(e,"input",s);BX.bind(e,"keyup",s);BX.bind(e,"change",s)},disable:function(e){if(BX.type.isElementNode(e)){e.setAttribute("disabled","disabled")}},enable:function(e){if(BX.type.isElementNode(e)){e.removeAttribute("disabled")}},getMessagePlural:function(e,t){var i,n;n=BX.message("LANGUAGE_ID");e=parseInt(e);if(e<0){e=-1*e}if(n){switch(n){case"de":case"en":i=e!==1?1:0;break;case"ru":case"ua":i=e%10===1&&e%100!==11?0:e%10>=2&&e%10<=4&&(e%100<10||e%100>=20)?1:2;break;default:i=1;break}}else{i=1}if(BX.type.isArray(t)){return t[i]}return BX.message(t+"_PLURAL_"+i)},fireGlobalTaskEvent:function(e,t,i,n){if(!e){return false}e=e.toString();i=i||{};if(e!="ADD"&&e!="UPDATE"&&e!="DELETE"&&e!="NOOP"){return false}var s=[e,{task:t,taskUgly:n,options:i}];BX.onCustomEvent(window,"tasksTaskEvent",s);if(window!=window.top){window.top.BX.onCustomEvent(window.top,"tasksTaskEvent",s)}return true}});BX.Tasks.Util.hintManager={bindHelp:function(e){var t={className:"js-id-hint-help"};BX.bindDelegate(e,"mouseover",t,BX.Tasks.passCtx(this.onHelpShow,this));BX.bindDelegate(e,"mouseout",t,BX.Tasks.passCtx(this.onHelpHide,this))},showDisposable:function(e,t,i,n){if(!BX.type.isPlainObject(n)){n={}}if(!("closeLabel"in n)){n.closeLabel=BX.message("TASKS_COMMON_DONT_SHOW_AGAIN")}if(!("autoHide"in n)){n.autoHide=true}this.show(e,t,false,i,n)},show:function(e,t,i,n,s){n=n||BX.util.hashCode((Math.random()*100).toString()).toString();s=s||{};var a=BX.Tasks.Runtime;a.hintPopup=a.hintPopup||{};if(typeof a.hintPopup[n]=="undefined"){a.hintPopup[n]={popup:null,disable:false}}if(this.wasDisposed(n)){return}if(a.hintPopup[n].popup==null){var o=[];if(BX.type.isNotEmptyString(s.title)){o.push(BX.create("SPAN",{attrs:{className:"task-hint-popup-title"},text:s.title}))}if(!BX.type.isNotEmptyString(t)){t=""}t=BX.util.htmlspecialchars(t).replace(/#BR#/g,"<br />");o.push(BX.create("P",{html:t,style:{margin:"10px 20px 10px 5px"}}));if(BX.type.isNotEmptyString(s.closeLabel)){o.push(BX.create("P",{style:{margin:"10px 20px 10px 5px"},children:[BX.create("A",{props:{href:"javascript:void(0)"},text:s.closeLabel,events:{click:function(){BX.Tasks.Util.hintManager.disable(n);BX.Tasks.Util.hintManager.hide(n)}}})]}))}a.hintPopup[n].popup=BX.PopupWindowManager.create(n,e,{closeByEsc:false,closeIcon:true,angle:{},autoHide:s.autoHide===true,offsetLeft:50,offsetTop:5,events:{onPopupClose:BX.delegate(this.onViewModeHintClose,this)},content:BX.create("DIV",{attrs:{className:"task-hint-popup-contents"},children:o})})}a.hintPopup[n].popup.show()},wasDisposed:function(e){BX.Tasks.Runtime.hintPopup=BX.Tasks.Runtime.hintPopup||{};BX.Tasks.Runtime.hintPopup[e]=BX.Tasks.Runtime.hintPopup[e]||{};return BX.Tasks.Runtime.hintPopup[e].disable},hide:function(e){try{BX.Tasks.Runtime.hintPopup[e].popup.close()}catch(t){}},disable:function(e){BX.Tasks.Runtime.hintPopup=BX.Tasks.Runtime.hintPopup||{};BX.Tasks.Runtime.hintPopup[e]=BX.Tasks.Runtime.hintPopup[e]||{};BX.Tasks.Runtime.hintPopup[e].disable=true;BX.userOptions.save("tasks","task_hints",e,"N",false)},disableSeveral:function(e){if(BX.type.isPlainObject(e)){var t=BX.Tasks.Runtime;t.hintPopup=t.hintPopup||{};for(var i in e){t.hintPopup[i]=t.hintPopup[i]||{};t.hintPopup[i].disable=!e[i]}}},onHelpShow:function(e){var t=BX.data(e,"hint-enabled");if(t!==null&&typeof t!="undefined"&&t!="1"){return}var i=BX.data(e,"hint-text");if(!i){i=e.innerHTML}if(BX.type.isNotEmptyString(i)){this.onHelpHide();var n=new BX.PopupWindow("tasks-generic-help-popup",e,{lightShadow:true,autoHide:false,darkMode:true,offsetLeft:0,offsetTop:2,bindOptions:{position:"top"},zIndex:200,events:{onPopupClose:function(){this.destroy();BX.Tasks.Runtime.helpWindow=null}},content:BX.create("div",{attrs:{style:"padding-right: 5px; width: 250px;"},html:i})});n.setAngle({offset:13,position:"bottom"});n.show();BX.Tasks.Runtime.helpWindow=n}},onHelpHide:function(){if(BX.Tasks.Runtime.helpWindow){BX.Tasks.Runtime.helpWindow.close()}}};BX.Tasks.Util.MouseTracker=function(){this.coords={x:0,y:0};BX.bind(document,"mousemove",BX.delegate(function(e){this.coords={x:e.pageX?e.pageX:e.clientX?e.clientX+(document.documentElement.scrollLeft||document.body.scrollLeft)-document.documentElement.clientLeft:0,y:e.pageY?e.pageY:e.clientY?e.clientY+(document.documentElement.scrollTop||document.body.scrollTop)-document.documentElement.clientTop:0}},this))};BX.Tasks.Util.MouseTracker.getCoordinates=function(){return BX.clone(BX.Tasks.Util.MouseTracker.getInstance().coords)};BX.Tasks.Util.MouseTracker.getInstance=function(){if(typeof BX.Tasks.Runtime.mouseTracker=="undefined"){BX.Tasks.Runtime.mouseTracker=new BX.Tasks.Util.MouseTracker}return BX.Tasks.Runtime.mouseTracker};if(typeof BX.Tasks.Runtime=="undefined"){BX.Tasks.Runtime={}}
/* End */
;
; /* Start:"a:4:{s:4:"full";s:47:"/bitrix/js/tasks/cjstask.min.js?151972746511284";s:6:"source";s:27:"/bitrix/js/tasks/cjstask.js";s:3:"min";s:31:"/bitrix/js/tasks/cjstask.min.js";s:3:"map";s:31:"/bitrix/js/tasks/cjstask.map.js";}"*/
(function(){if(BX.CJSTask)return;BX.CJSTask={ajaxUrl:"/bitrix/components/bitrix/tasks.iframe.popup/ajax.php?SITE_ID="+BX.message("SITE_ID"),sequenceId:0,timers:{}};BX.CJSTask.addTimeToDate=function(a,t,e){if(typeof a=="undefined"||typeof t=="undefined")return a;if(typeof e=="undefined")e={onlyIfEmpty:true};if(e.onlyIfEmpty&&(parseInt(a.getHours())!=0||parseInt(a.getMinutes())!=0))return a;if(typeof t.h!="undefined")a.setHours(parseInt(t.h));if(typeof t.m!="undefined")a.setMinutes(t.m);if(typeof t.s!="undefined")a.setSeconds(t.s);return a};BX.CJSTask.addTimeToDateTime=function(a,t,e){if(typeof a=="undefined"||typeof t=="undefined")return a;a=a.toString();if(a.length>0){var s=BX.CJSTask.addTimeToDate(BX.parseDate(a),t,e);a=BX.date.format(BX.date.convertBitrixFormat(BX.message("FORMAT_DATETIME")),s)}return a};BX.CJSTask.ui={};BX.CJSTask.ui.extractDefaultTimeFromDataAttribute=function(a){defaultTime={h:19,m:0,s:0};if(BX.type.isDomNode(a)){var t=BX.data(a,"default-hour");var e=BX.data(a,"default-minute");if(typeof t!="undefined"&&typeof e!="undefined"){defaultTime.h=parseInt(t);defaultTime.m=parseInt(e)}}return defaultTime};BX.CJSTask.ui.getInputDateTimeValue=function(a){var t=BX.CJSTask.ui.extractDefaultTimeFromDataAttribute(a);var e=new Date;curDayEveningTime=new Date(e.getFullYear(),e.getMonth(),e.getDate(),t.h,t.m,t.s);if(!!a.value)var s=a.value;else var s=BX.date.convertToUTC(curDayEveningTime);return s};BX.CJSTask.getMessagePlural=function(a,t){var e,s;s=BX.message("LANGUAGE_ID");a=parseInt(a);if(a<0)a=-1*a;if(s){switch(s){case"de":case"en":e=a!==1?1:0;break;case"ru":case"ua":e=a%10===1&&a%100!==11?0:a%10>=2&&a%10<=4&&(a%100<10||a%100>=20)?1:2;break;default:e=1;break}}else e=1;return BX.message(t+"_PLURAL_"+e)};BX.CJSTask.fixWindow=function(a){var t=window.top;var e=window;return function(){var s=Array.prototype.slice.call(arguments);s.unshift(e,t);a.apply(this,s)}};BX.CJSTask.createItem=function(a,t){var t=t||null;var e=null;if(t.columnsIds)e=t.columnsIds;var s={sessid:BX.message("bitrix_sessid"),batch:[{operation:"CTaskItem::add()",taskData:a},{operation:"CTaskItem::getTaskData()",taskData:{ID:"#RC#$arOperationsResults#-1#justCreatedTaskId"}},{operation:"CTaskItem::getAllowedTaskActions()",taskData:{ID:"#RC#$arOperationsResults#-1#returnValue#ID"}},{operation:"NOOP"},{operation:"CTaskItem::getAllowedTaskActionsAsStrings()",taskData:{ID:"#RC#$arOperationsResults#-3#returnValue#ID"}},{operation:"tasksRenderJSON() && tasksRenderListItem()",taskData:{ID:"#RC#$arOperationsResults#-4#returnValue#ID"},columnsIds:e}]};BX.ajax({method:"POST",dataType:"json",url:BX.CJSTask.ajaxUrl,data:s,processData:true,onsuccess:function(a){var t=false;var e=false;if(a){if(a.callback)t=a.callback;if(a.callbackOnFailure)e=a.callbackOnFailure}return function(a){if(a.status==="success"&&!!t){var s={taskData:a["data"][1]["returnValue"],allowedTaskActions:a["data"][2]["returnValue"],allowedTaskActionsAsStrings:a["data"][4]["returnValue"]};var r=new BX.CJSTask.Item(a["data"][1]["returnValue"]["ID"],s);var n=BX.parseJSON(a["data"][5]["returnValue"]["tasksRenderJSON"]);var i=a["data"][5]["returnValue"]["tasksRenderListItem"];t(r,s,n,i)}else if(a.status!=="success"&&!!e){var o=[];var u=0;if(a.repliesCount>0&&a.data[a.repliesCount-1].hasOwnProperty("errors")){u=a.data[a.repliesCount-1].errors.length;for(var l=0;l<u;l++)o.push(a.data[a.repliesCount-1].errors[l]["text"])}e({rawReply:a,status:a.status,errMessages:o})}}}(t)})};BX.CJSTask.Item=function(a,t){if(!a)throw"taskId must be set";if(!(a>=1))throw"taskId must be >= 1";this.taskId=a;this.cachedData={taskData:false,allowedTaskActions:false,allowedTaskActionsAsStrings:false};if(t){if(t.taskData)this.cachedData.taskData=t.taskData;if(t.allowedTaskActions)this.cachedData.allowedTaskActions=t.allowedTaskActions;if(t.allowedTaskActionsAsStrings)this.cachedData.allowedTaskActionsAsStrings=t.allowedTaskActionsAsStrings}this.getCachedData=function(){return this.cachedData};this.refreshCache=function(a){var a=a||null;var t={sessid:BX.message("bitrix_sessid"),batch:[{operation:"CTaskItem::getTaskData()",taskData:{ID:this.taskId}},{operation:"CTaskItem::getAllowedTaskActions()",taskData:{ID:this.taskId}},{operation:"CTaskItem::getAllowedTaskActionsAsStrings()",taskData:{ID:this.taskId}}]};BX.ajax({method:"POST",dataType:"json",url:BX.CJSTask.ajaxUrl,data:t,processData:true,onsuccess:function(a,t){var e=false;if(a&&a.callback)e=a.callback;return function(a){t.cachedData={taskData:a["data"][0]["returnValue"],allowedTaskActions:a["data"][1]["returnValue"],allowedTaskActionsAsStrings:a["data"][2]["returnValue"]};if(!!e)e(t.cachedData)}}(a,this)})};this.complete=function(a){var t={sessid:BX.message("bitrix_sessid"),batch:[{operation:"CTaskItem::complete()",taskData:{ID:this.taskId}},{operation:"CTaskItem::getTaskData()",taskData:{ID:this.taskId}},{operation:"CTaskItem::getAllowedTaskActions()",taskData:{ID:"#RC#$arOperationsResults#-1#returnValue#ID"}},{operation:"CTaskItem::getAllowedTaskActionsAsStrings()",taskData:{ID:"#RC#$arOperationsResults#-2#returnValue#ID"}}]};BX.ajax({method:"POST",dataType:"json",url:BX.CJSTask.ajaxUrl,data:t,processData:true,onsuccess:function(a){var t=false;var e=false;if(a){if(a.callbackOnSuccess)t=a.callbackOnSuccess;if(a.callbackOnFailure)e=a.callbackOnFailure}return function(a){if(a.status==="success"&&!!t){var s={taskData:a["data"][1]["returnValue"],allowedTaskActions:a["data"][2]["returnValue"],allowedTaskActionsAsStrings:a["data"][3]["returnValue"]};var r=new BX.CJSTask.Item(a["data"][1]["returnValue"]["ID"],s);t(r)}else if(a.status!=="success"&&!!e){var n=[];var i=0;if(a.repliesCount>0&&a.data[a.repliesCount-1].hasOwnProperty("errors")){i=a.data[a.repliesCount-1].errors.length;for(var o=0;o<i;o++)n.push(a.data[a.repliesCount-1].errors[o]["text"])}e({rawReply:a,status:a.status,errMessages:n})}}}(a)})};this.startExecutionOrRenewAndStart=function(a){var t={sessid:BX.message("bitrix_sessid"),batch:[{operation:"CTaskItem::startExecutionOrRenewAndStart",taskData:{ID:this.taskId}},{operation:"CTaskItem::getTaskData()",taskData:{ID:this.taskId}},{operation:"CTaskItem::getAllowedTaskActions()",taskData:{ID:"#RC#$arOperationsResults#-1#returnValue#ID"}},{operation:"CTaskItem::getAllowedTaskActionsAsStrings()",taskData:{ID:"#RC#$arOperationsResults#-2#returnValue#ID"}}]};BX.ajax({method:"POST",dataType:"json",url:BX.CJSTask.ajaxUrl,data:t,processData:true,onsuccess:function(a){var t=false;var e=false;if(a){if(a.callbackOnSuccess)t=a.callbackOnSuccess;if(a.callbackOnFailure)e=a.callbackOnFailure}return function(a){if(a.status==="success"&&!!t){var s={taskData:a["data"][1]["returnValue"],allowedTaskActions:a["data"][2]["returnValue"],allowedTaskActionsAsStrings:a["data"][3]["returnValue"]};var r=new BX.CJSTask.Item(a["data"][1]["returnValue"]["ID"],s);t(r)}else if(a.status!=="success"&&!!e){var n=[];var i=0;if(a.repliesCount>0&&a.data[a.repliesCount-1].hasOwnProperty("errors")){i=a.data[a.repliesCount-1].errors.length;for(var o=0;o<i;o++)n.push(a.data[a.repliesCount-1].errors[o]["text"])}e({rawReply:a,status:a.status,errMessages:n})}}}(a)})};this.addElapsedTime=function(a,t){var e={TASK_ID:this.taskId,MINUTES:a.MINUTES,COMMENT_TEXT:a.COMMENT_TEXT};var s=BX.CJSTask.batchOperations([{operation:"CTaskItem::addElapsedTime()",elapsedTimeData:e}],t);return s};this.checklistAddItem=function(a,t){var e={TITLE:a};var s=BX.CJSTask.batchOperations([{operation:"CTaskCheckListItem::add()",checklistData:e,taskId:this.taskId}],t);return s};this.checklistRename=function(a,t,e){var s={TITLE:t};var r=BX.CJSTask.batchOperations([{operation:"CTaskCheckListItem::update()",checklistData:s,itemId:a,taskId:this.taskId}],e);return r};this.checklistComplete=function(a,t){var e=BX.CJSTask.batchOperations([{operation:"CTaskCheckListItem::complete()",itemId:a,taskId:this.taskId}],t);return e};this.checklistRenew=function(a,t){var e=BX.CJSTask.batchOperations([{operation:"CTaskCheckListItem::renew()",itemId:a,taskId:this.taskId}],t);return e};this.checklistDelete=function(a,t){var e=BX.CJSTask.batchOperations([{operation:"CTaskCheckListItem::delete()",itemId:a,taskId:this.taskId}],t);return e};this.checklistMoveAfterItem=function(a,t,e){var s=BX.CJSTask.batchOperations([{operation:"CTaskCheckListItem::moveAfterItem()",itemId:a,taskId:this.taskId,insertAfterItemId:t}],e);return s};this.stopWatch=function(a){var t=BX.CJSTask.batchOperations([{operation:"CTaskItem::stopWatch()",taskData:{ID:this.taskId}}],a);return t};this.startWatch=function(a){var t=BX.CJSTask.batchOperations([{operation:"CTaskItem::startWatch()",taskData:{ID:this.taskId}}],a);return t}};BX.CJSTask.TimerManager=function(a){if(!a)throw"taskId must be set";if(!(a>=1))throw"taskId must be >= 1";this.taskId=a;this.__private={startOrStop:function(a,t,e){var s=BX.CJSTask.batchOperations([{operation:a,taskData:{ID:t}},{operation:"CTaskItem::getTaskData()",taskData:{ID:"#RC#$arOperationsResults#-1#requestedTaskId"}},{operation:"CTaskTimerManager::getLastTimer()"}],e);return s}};this.start=function(a){var t=this.__private.startOrStop("CTaskTimerManager::start()",this.taskId,a);return t};this.stop=function(a){var t=this.__private.startOrStop("CTaskTimerManager::stop()",this.taskId,a);return t}};BX.CJSTask.setTimerCallback=function(a,t,e){if(BX.CJSTask[a]){window.clearInterval(BX.CJSTask[a]);BX.CJSTask[a]=null}if(t!==null)BX.CJSTask[a]=window.setInterval(t,e)};BX.CJSTask.formatUsersNames=function(a,t){var t=t||null;var e=null;var s=[];for(var r in a){e=a[r];s.push({operation:"CUser::FormatName()",userData:{ID:e}})}var n={sessid:BX.message("bitrix_sessid"),batch:s};BX.ajax({method:"POST",dataType:"json",url:BX.CJSTask.ajaxUrl,data:n,processData:true,onsuccess:function(a){var t=false;if(a&&a.callback)t=a.callback;return function(a){if(!!t){var e=null;var s={};var r=a["repliesCount"];for(var n=0;n<r;n++){e=a["data"][n];s["u"+e["requestedUserId"]]=e["returnValue"]}t(s)}}}(t)})};BX.CJSTask.getGroupsData=function(a,t){var t=t||null;var e=null;var s=[];for(var r in a){e=a[r];s.push({operation:"CSocNetGroup::GetByID()",groupData:{ID:e}})}var n={sessid:BX.message("bitrix_sessid"),batch:s};BX.ajax({method:"POST",dataType:"json",url:BX.CJSTask.ajaxUrl,data:n,processData:true,onsuccess:function(a){var t=false;if(a&&a.callback)t=a.callback;return function(a){if(!!t){var e=null;var s={};var r=a["repliesCount"];for(var n=0;n<r;n++){e=a["data"][n];s[e["requestedGroupId"]]=e["returnValue"]}t(s)}}}(t)})};BX.CJSTask.batchOperations=function(a,t,e){var t=t||null;var e=e||false;var s="batch_sequence_No_"+ ++BX.CJSTask.sequenceId;var r={sessid:BX.message("bitrix_sessid"),batch:a,batchId:s};BX.ajax({method:"POST",dataType:"json",url:BX.CJSTask.ajaxUrl,data:r,async:!e,processData:true,onsuccess:function(a){var t=false;var e=false;if(a){if(a.callbackOnSuccess)t=a.callbackOnSuccess;if(a.callbackOnFailure)e=a.callbackOnFailure}return function(a){if(a.status==="success"&&!!t){t({rawReply:a,status:a.status})}else if(a.status!=="success"&&!!e){var s=[];var r=0;if(a.repliesCount>0&&a.data[a.repliesCount-1].hasOwnProperty("errors")){r=a.data[a.repliesCount-1].errors.length;for(var n=0;n<r;n++)s.push(a.data[a.repliesCount-1].errors[n]["text"])}e({rawReply:a,status:a.status,errMessages:s})}}}(t)});return s}})();
/* End */
;
; /* Start:"a:4:{s:4:"full";s:57:"/bitrix/js/tasks/task-quick-popups.min.js?151972746518794";s:6:"source";s:37:"/bitrix/js/tasks/task-quick-popups.js";s:3:"min";s:41:"/bitrix/js/tasks/task-quick-popups.min.js";s:3:"map";s:41:"/bitrix/js/tasks/task-quick-popups.map.js";}"*/
(function(){if(!BX.Tasks)BX.Tasks={};if(BX.Tasks.lwPopup)return;BX.Tasks.lwPopup={ajaxUrl:"/bitrix/components/bitrix/tasks.list/ajax.php",onTaskAdded:null,onTaskAddedMultiple:null,loggedInUserId:null,loggedInUserFormattedName:null,garbageAreaId:"garbageAreaId_id",functions:{},functionsCount:0,firstRunDone:false,createForm:{objPopup:null,objTemplate:null,callbacks:{onAfterPopupCreated:null,onBeforePopupShow:null,onAfterPopupShow:null,onAfterEditorInited:null,onPopupClose:null}},anyForm:[],anyFormsCount:0,registerForm:function(e){e=e||{callbacks:{}};var t=this.anyFormsCount++;this.anyForm[t]={formIndex:t,objPopup:null,objTemplate:null,callbacks:e.callbacks};return this.anyForm[t]},__runAnyFormCallback:function(e,t,a){a=a||[];if(!this.anyForm[e])throw Error("Form with index "+e+" not exists");if(BX.Tasks.lwPopup.anyForm[e].callbacks.hasOwnProperty(t)&&BX.Tasks.lwPopup.anyForm[e].callbacks[t]!==null){BX.Tasks.lwPopup.anyForm[e].callbacks[t].apply(BX.Tasks.lwPopup.anyForm[e].objTemplate,a)}},showForm:function(e,t){t=typeof t!=="undefined"?t:{};if(!this.anyForm[e])throw Error("Form with index "+e+" not exists");var a=this.anyForm[e];BX.Tasks.lwPopup.__firstRun();var n=false;if(a.objPopup===null){this.buildForm(e,t);n=true}this.__runAnyFormCallback(e,"onBeforePopupShow",[t,{isPopupJustCreated:n}]);a.objPopup.show()},buildForm:function(e,t,a){var n=-110;t=typeof t!=="undefined"?t:{};if(typeof a!=="undefined")n=a;if(!this.anyForm[e])throw Error("Form with index "+e+" not exists");var s=this.anyForm[e];BX.Tasks.lwPopup.__firstRun();s.objPopup=new BX.PopupWindow("bx-tasks-quick-popup-anyForm-"+e,null,{zIndex:n,autoHide:false,buttons:s.objTemplate.prepareButtons(),closeByEsc:false,overlay:true,draggable:true,bindOnResize:false,titleBar:s.objTemplate.prepareTitleBar(),closeIcon:{right:"12px",top:"10px"},events:{onPopupClose:function(){BX.Tasks.lwPopup.__runAnyFormCallback(e,"onPopupClose",[])},onPopupFirstShow:function(){BX.Tasks.lwPopup.__runAnyFormCallback(e,"onPopupFirstShow",[])},onPopupShow:function(){BX.Tasks.lwPopup.__runAnyFormCallback(e,"onPopupShow",[])},onAfterPopupShow:function(){BX.Tasks.lwPopup.__runAnyFormCallback(e,"onAfterPopupShow",[])}},content:s.objTemplate.prepareContent(t)});this.__runAnyFormCallback(e,"onAfterPopupCreated",[t])},__runCreateFormCallback:function(e,t){t=t||[];if(BX.Tasks.lwPopup.createForm.callbacks.hasOwnProperty(e)&&BX.Tasks.lwPopup.createForm.callbacks[e]!==null){BX.Tasks.lwPopup.createForm.callbacks[e].apply(BX.Tasks.lwPopup.createForm.objTemplate,t)}},showCreateForm:function(e){e=typeof e!=="undefined"?e:{};BX.Tasks.lwPopup.__firstRun();if(!e.RESPONSIBLE_ID){e.RESPONSIBLE_ID=BX.Tasks.lwPopup.loggedInUserId;e["META:RESPONSIBLE_FORMATTED_NAME"]=BX.Tasks.lwPopup.loggedInUserFormattedName}else if(e.RESPONSIBLE_ID==BX.Tasks.lwPopup.loggedInUserId&&!e.hasOwnProperty("META:RESPONSIBLE_FORMATTED_NAME")){e["META:RESPONSIBLE_FORMATTED_NAME"]=BX.Tasks.lwPopup.loggedInUserFormattedName}var t=false;if(BX.Tasks.lwPopup.createForm.objPopup===null){BX.Tasks.lwPopup.createForm.objPopup=new BX.PopupWindow("bx-tasks-quick-popup-create-new-task",null,{zIndex:-110,autoHide:false,buttons:BX.Tasks.lwPopup.createForm.objTemplate.prepareButtons(),closeByEsc:false,overlay:true,draggable:true,bindOnResize:false,titleBar:BX.Tasks.lwPopup.createForm.objTemplate.prepareTitleBar(),closeIcon:{right:"12px",top:"10px"},events:{onPopupClose:function(){BX.Tasks.lwPopup.__runCreateFormCallback("onPopupClose",[])},onPopupFirstShow:function(){},onPopupShow:function(){},onAfterPopupShow:function(){if(BX("bx-panel")&&parseInt(BX.Tasks.lwPopup.createForm.objPopup.popupContainer.style.top)<147){BX.Tasks.lwPopup.createForm.objPopup.popupContainer.style.top=147+"px"}BX.Tasks.lwPopup.__runCreateFormCallback("onAfterPopupShow",[])}},content:BX.Tasks.lwPopup.createForm.objTemplate.prepareContent(e)});BX.Tasks.lwPopup.__runCreateFormCallback("onAfterPopupCreated",[e]);t=true}BX.Tasks.lwPopup.__runCreateFormCallback("onBeforePopupShow",[e,{isPopupJustCreated:t}]);BX.Tasks.lwPopup.createForm.objPopup.show()},_createTask:function(e){e=e||{};var t=false;var a=null;var n=null;var s=null;var o={};if(e.hasOwnProperty("taskData"))o=e.taskData;if(e.hasOwnProperty("onceMore"))t=e.onceMore;if(e.hasOwnProperty("columnsIds"))s=e.columnsIds;if(e.hasOwnProperty("callbackOnSuccess"))a=e.callbackOnSuccess;if(e.hasOwnProperty("callbackOnFailure"))n=e.callbackOnFailure;if(!o.hasOwnProperty("TITLE"))o.TITLE="";if(!o.hasOwnProperty("RESPONSIBLE_ID"))o.RESPONSIBLE_ID=this.loggedInUserId;BX.CJSTask.createItem(o,{columnsIds:s,callback:function(e,t){return function(a,n,s,o){var r={oTask:a,taskData:n.taskData,allowedTaskActions:n.allowedTaskActions,allowedTaskActionsAsStrings:n.allowedTaskActionsAsStrings,params:{onceMore:e}};if(t)t(r);if(BX.Tasks.lwPopup.onTaskAdded&&e===false)BX.Tasks.lwPopup.onTaskAdded(s,null,null,r,o);else if(BX.Tasks.lwPopup.onTaskAddedMultiple&&e===true)BX.Tasks.lwPopup.onTaskAddedMultiple(s,null,null,r,o)}}(t,a),callbackOnFailure:function(e){return function(t){if(e)e(t)}}(n)})},__initSelectors:function(e){var t=e.length;var a=false;BX.Tasks.lwPopup.__firstRun();for(var n=0;n<t;n++){if(e[n]["requestedObject"]==="intranet.user.selector.new"){a=true;break}}var s=null;if(a){var o=BX.Tasks.lwPopup.functionsCount++;BX.Tasks.lwPopup.functions["f"+o]=function(){};s=BX.Tasks.lwPopup.garbageAreaId+"__userSelectors_"+o+"_loadedHtml";BX(BX.Tasks.lwPopup.garbageAreaId).appendChild(BX.create("DIV",{props:{id:s}}))}var r={sessid:BX.message("bitrix_sessid"),requestsCount:t};var i=[];var l=[];for(var n=0;n<t;n++){if(e[n]["requestedObject"]==="intranet.user.selector.new")i[n]=this.__prepareUserSelectorsData(e[n]);else if(e[n]["requestedObject"]==="socialnetwork.group.selector")i[n]=this.__prepareGroupsSelectorsData(e[n]);else if(e[n]["requestedObject"]==="LHEditor")i[n]=this.__prepareLheData(e[n]);else if(e[n]["requestedObject"]==="system.field.edit::CRM"){i[n]=this.__prepareUserFieldData(e[n]);for(var u in i[n]["postData"])r[u]=i[n]["postData"][u]}else if(e[n]["requestedObject"]==="system.field.edit::WEBDAV"){i[n]=this.__prepareUserFieldDataWebdav(e[n]);for(var u in i[n]["postData"])r[u]=i[n]["postData"][u]}r["data_"+n]=i[n]["ajaxParams"];l[n]=i[n]["object"]}BX.ajax({method:"POST",dataType:"html",url:"/bitrix/components/bitrix/tasks.iframe.popup/ajax_loader.php?SITE_ID="+BX.message("SITE_ID"),data:r,processData:true,autoAuth:true,onsuccess:function(e,t,a){return function(n){if(a)BX(t).innerHTML=n;var s=e.length;for(var o=0;o<s;o++){if(e[o].hasOwnProperty("onLoadedViaAjax"))e[o].onLoadedViaAjax()}}}(l,s,a)});return l},__prepareUserFieldData:function(e){var t=BX.Tasks.lwPopup.functionsCount++;var a="OBJ_TASKS_CONTAINER_NAME_ID_"+t;var n="OBJ_TASKS_CONTAINER_DATA_ID_"+t;var s={requestedObject:"system.field.edit::CRM",userFieldName:e["userFieldName"],taskId:e["taskId"],nameContainerId:a,dataContainerId:n,values:e["value"]};var o=[];o.push.apply(o,e["value"]);BX.Tasks.lwPopup.functions["f"+t]={allParams:e,ajaxParams:s,ready:false,available:null,timeoutId:null,valuesBuffer:o,nameContainerId:a,dataContainerId:n,onLoadedViaAjax:function(){if(BX(this.nameContainerId))this.available=true;else this.available=false;if(!this.available)return false;var e=BX(this.nameContainerId).innerHTML;BX.remove(BX(this.nameContainerId));this.allParams.callbackOnRedraw(e,this.dataContainerId);this.ready=true},getValue:function(){var e=[];if(this.ready===true){var t=document.getElementsByName("UF_CRM_TASK[]");if(t){var a=t.length;for(var n=0;n<a;n++)e.push(t[n].value)}}else{e=this.valuesBuffer}return e},setValue:function(e){if(this.valuesBuffer.length===e.length){var t=this.valuesBuffer.slice().sort().join(";");var a=e.slice().sort().join(";");if(t===a)return}this.valuesBuffer=[];this.valuesBuffer.push.apply(this.valuesBuffer,e);this.__delayedSetContent(30)},__delayedSetContent:function(e){if(this.available===false)return false;if(this.ready===false){if(this.timeoutId!==null)window.clearTimeout(this.timeoutId);this.timeoutId=window.setTimeout(function(){var a=e+100;if(e<30)a=30;else if(e>500)a=500;BX.Tasks.lwPopup.functions["f"+t].__delayedSetContent(a)},e)}else{if(BX(this.nameContainerId))BX.remove(BX(this.nameContainerId));if(BX(this.dataContainerId))BX.remove(BX(this.dataContainerId));var a="";var n=this.valuesBuffer.length;for(var s=0;s<n;s++)a=a+"&UF_CRM_TASK[]="+this.valuesBuffer[s];var o={sessid:BX.message("bitrix_sessid"),requestsCount:1,data_0:this.ajaxParams};BX.ajax({method:"POST",dataType:"html",url:"/bitrix/components/bitrix/tasks.iframe.popup/ajax_loader.php?SITE_ID="+BX.message("SITE_ID")+a,data:o,processData:true,autoAuth:true,onsuccess:function(e){return function(t){BX(BX.Tasks.lwPopup.garbageAreaId).appendChild(BX.create("div",{html:t}));e.ready=true;var a=BX(e.nameContainerId).innerHTML;BX.remove(BX(e.nameContainerId));e.allParams.callbackOnRedraw(a,e.dataContainerId)}}(this)})}}};var r={object:BX.Tasks.lwPopup.functions["f"+t],ajaxParams:s,postData:{UF_CRM_TASK:e["value"]}};return r},__prepareUserFieldDataWebdav:function(e){var t=BX.Tasks.lwPopup.functionsCount++;var a="OBJ_TASKS_CONTAINER_NAME_ID_"+t;var n="OBJ_TASKS_CONTAINER_DATA_ID_"+t;var s={requestedObject:"system.field.edit::WEBDAV",userFieldName:e["userFieldName"],taskId:e["taskId"],nameContainerId:a,dataContainerId:n,values:e["value"]};var o=[];o.push.apply(o,e["value"]);BX.Tasks.lwPopup.functions["f"+t]={allParams:e,ajaxParams:s,ready:false,available:null,timeoutId:null,valuesBuffer:o,nameContainerId:a,dataContainerId:n,onLoadedViaAjax:function(){if(BX(this.nameContainerId))this.available=true;else this.available=false;if(!this.available)return false;var e=BX(this.nameContainerId).innerHTML;BX.remove(BX(this.nameContainerId));this.allParams.callbackOnRedraw(e,this.dataContainerId);this.ready=true},getValue:function(){var e=[];if(this.ready===true){var t=document.getElementsByName("UF_TASK_WEBDAV_FILES[]");if(t){var a=t.length;for(var n=0;n<a;n++)e.push(t[n].value)}}else{e=this.valuesBuffer}return e},setValue:function(e){if(this.valuesBuffer.length===e.length){var t=this.valuesBuffer.slice().sort().join(";");var a=e.slice().sort().join(";");if(t===a)return}this.valuesBuffer=[];this.valuesBuffer.push.apply(this.valuesBuffer,e);this.__delayedSetContent(30)},__delayedSetContent:function(e){if(this.available===false)return false;if(this.ready===false){if(this.timeoutId!==null)window.clearTimeout(this.timeoutId);this.timeoutId=window.setTimeout(function(){var a=e+100;if(e<30)a=30;else if(e>500)a=500;BX.Tasks.lwPopup.functions["f"+t].__delayedSetContent(a)},e)}else{if(BX(this.nameContainerId))BX.remove(BX(this.nameContainerId));if(BX(this.dataContainerId))BX.remove(BX(this.dataContainerId));var a="";var n=this.valuesBuffer.length;for(var s=0;s<n;s++)a=a+"&UF_TASK_WEBDAV_FILES[]="+this.valuesBuffer[s];var o={sessid:BX.message("bitrix_sessid"),requestsCount:1,data_0:this.ajaxParams};BX.ajax({method:"POST",dataType:"html",url:"/bitrix/components/bitrix/tasks.iframe.popup/ajax_loader.php?SITE_ID="+BX.message("SITE_ID")+a,data:o,processData:true,autoAuth:true,onsuccess:function(e){return function(t){BX(BX.Tasks.lwPopup.garbageAreaId).appendChild(BX.create("div",{html:t}));e.ready=true;var a=BX(e.nameContainerId).innerHTML;BX.remove(BX(e.nameContainerId));e.allParams.callbackOnRedraw(a,e.dataContainerId)}}(this)})}}};var r={object:BX.Tasks.lwPopup.functions["f"+t],ajaxParams:s,postData:{UF_TASK_WEBDAV_FILES:e["value"]}};return r},__prepareLheData:function(e){var t=BX.Tasks.lwPopup.functionsCount++;var a="OBJ_TASKS_LHEDITOR_NS_"+t;var n="OBJ_TASKS_ELEMENT_ID_NS_"+t;var s="OBJ_TASKS_INPUT_ID_NS_"+t;BX.Tasks.lwPopup.functions["f"+t]={allParams:e,jsObjectName:a,elementId:n,editor:null,inputId:s,content:"",getContent:function(){if(this.editor!==null){this.editor.SaveContent();return this.editor.GetContent()}else{if(BX(this.inputId))return BX(this.inputId).value;else return""}},setContent:function(e){this["content"]=e;this.__delayedSetContent(30)},__delayedSetContent:function(e){if(this.editor===null){window.setTimeout(function(){var a=e+100;if(e<30)a=30;else if(e>500)a=500;BX.Tasks.lwPopup.functions["f"+t].__delayedSetContent(a)},e)}else{if(BX.type.isString(this["content"])){this.editor.SetContent(this["content"]);if(this["content"].length==0)this.editor.ResizeSceleton(false,200);if(BX.browser.IsChrome()||BX.browser.IsIE11()||BX.browser.IsIE()){var a=BX("lwPopup-task-title");if(BX.type.isElementNode(a)){this.editor.Focus(false);a.focus()}}}}}};BX.addCustomEvent(window,"OnEditorInitedAfter",function(e,t){var a=false;return function(n){if(!a&&n.id==e.elementId){e.editor=n;var s=BX(t);s.innerHTML="";s.appendChild(n.dom.cont);a=true;setTimeout(function(){n.CheckAndReInit();n.SetContent(e["content"]);if(BX.browser.IsChrome()||BX.browser.IsIE11()||BX.browser.IsIE()){var t=BX("lwPopup-task-title");if(BX.type.isElementNode(t)){n.Focus(false);t.focus()}}},500);BX.Tasks.lwPopup.__runCreateFormCallback("onAfterEditorInited",[])}}}(BX.Tasks.lwPopup.functions["f"+t],e.attachTo));var o={object:BX.Tasks.lwPopup.functions["f"+t],ajaxParams:{requestedObject:"LHEditor",jsObjectName:a,elementId:n,inputId:s}};return o},__prepareGroupsSelectorsData:function(e){var t=BX.Tasks.lwPopup.functionsCount++;var a="OBJ_TASKS_GROUP_SELECTOR_NS_"+t;BX.Tasks.lwPopup.functions["f"+t]={allParams:e,jsObjectName:a,bindElement:e.bindElement,onLoadedViaAjax:function(){BX.bind(BX(this.bindElement),"click",function(e){return function(t){if(!t)t=window.event;var a=window[e.jsObjectName];if(a){a.popupWindow.params.zIndex=1400;a.show()}BX.PreventDefault(t)}}(this));if(this.allParams.onLoadedViaAjax)this.allParams.onLoadedViaAjax(this.jsObjectName)},setSelected:function(e){if(!window[this.jsObjectName])return;if(e.id==0){var t=null;if(window[this.jsObjectName].selected[0]){t=window[this.jsObjectName].selected[0];window[this.jsObjectName].deselect(t.id)}}else window[this.jsObjectName].select(e)},deselect:function(e){window[this.jsObjectName].deselect(e)}};var n="FUNC_TASKS_GROUP_SELECTOR_NS_"+t;window[n]=function(e){return function(t){if(e)e(t)}}(e.callbackOnSelect);var s={object:BX.Tasks.lwPopup.functions["f"+t],ajaxParams:{requestedObject:"socialnetwork.group.selector",jsObjectName:a,bindElement:e.bindElement,onSelectFuncName:n}};return s},__prepareUserSelectorsData:function(e){var t=null;var a=null;var n=0;if(e.hasOwnProperty("userInputId"))t=e.userInputId;if(e.hasOwnProperty("bindClickTo"))a=e.bindClickTo;else a=t;var s=e.callbackOnSelect;var o=e.selectedUsersIds;var r=e.anchorId;var l=e["multiple"];var u=BX.Tasks.lwPopup.functionsCount++;var p="OBJ_TASKS_USER_SELECTOR_NS_"+u;if(e.GROUP_ID_FOR_SITE)n=e.GROUP_ID_FOR_SITE;BX.Tasks.lwPopup.functions["f"+u]={allParams:e,multiple:l,popupId:p+"_popupId",bindClickTo:a,userInputId:t,anchorId:r,userPopupWindow:null,nsObjectName:p,onLoadedViaAjax:function(){var e=this;if(this.userInputId){BX.bind(BX(this.userInputId),"focus",function(t){e.showUserSelector(t)});if(BX(this.bindClickTo)){BX.bind(BX(this.bindClickTo),"click",function(t){if(!t)t=window.event;BX(e.userInputId).focus();BX.PreventDefault(t)})}}if(this.allParams.onLoadedViaAjax)this.allParams.onLoadedViaAjax();if(this.allParams.onReady){(function(e,t){var a=function(t,n,s){if(typeof window[e]==="undefined"){if(n>0){window.setTimeout(function(){a(t,n-t,s)},t)}}else{s(window[e])}};a(100,15e3,t)})("O_"+this.nsObjectName,this.allParams.onReady)}},onPopupClose:function(e){var t=window["O_"+e.nsObjectName];var a=t.arSelected.pop();if(a){t.arSelected.push(a);t.searchInput.value=a.name}},setSelectedUsers:function(e,t){var t=t||1;if(t>100)return;if(!window["O_"+this.nsObjectName]){window.setTimeout(function(e,t,a){return function(){e.setSelectedUsers(a,t+1)}}(this,t,e),50);return}var a=window["O_"+this.nsObjectName];a.setSelected(e)},showUserSelector:function(e){if(!e)e=window.event;if(this.userPopupWindow!==null&&this.userPopupWindow.popupContainer.style.display=="block"){return}var t=BX(this.anchorId);var a=null;var n=this;if(this["multiple"]==="Y"){a=[new BX.PopupWindowButton({text:this.allParams.btnSelectText,className:"popup-window-button-accept",events:{click:function(e){n.btnSelectClick(e);n.userPopupWindow.close()}}}),new BX.PopupWindowButtonLink({text:this.allParams.btnCancelText,className:"popup-window-button-link-cancel",events:{click:function(e){if(!e)e=window.event;n.userPopupWindow.close();if(e)BX.PreventDefault(e)}}})]}this.userPopupWindow=BX.PopupWindowManager.create(this.popupId,t,{offsetTop:1,autoHide:true,closeByEsc:true,content:BX(this.nsObjectName+"_selector_content"),buttons:a});if(this["multiple"]==="N"){BX.addCustomEvent(this.userPopupWindow,"onPopupClose",function(){n.onPopupClose(n)})}else{BX.addCustomEvent(this.userPopupWindow,"onAfterPopupShow",function(e){setTimeout(function(){window["O_"+n.nsObjectName].searchInput.focus()},100)})}this.userPopupWindow.show();BX(this.userPopupWindow.uniquePopupId).style.zIndex=1400;BX.focus(t);BX.PreventDefault(e)}};if(l==="N"){BX.Tasks.lwPopup.functions["f"+u].onUserSelect=function(e){var t=BX.Tasks.lwPopup.functions["f"+u];return function(a){if(t.userPopupWindow)t.userPopupWindow.close();e(a)}}(s);BX.Tasks.lwPopup.functions["f"+u].btnSelectClick=function(){}}else{BX.Tasks.lwPopup.functions["f"+u].onUserSelect=function(){};BX.Tasks.lwPopup.functions["f"+u].btnSelectClick=function(e){return function(t){if(!t)t=window.event;var a=window["O_"+this.nsObjectName].arSelected;var n=a.length;var s=[];for(i=0;i<n;i++){if(a[i])s.push(a[i])}e(s)}}(s)}var d={requestedObject:"intranet.user.selector.new",multiple:l,namespace:p,inputId:t,onSelectFunctionName:"BX.Tasks.lwPopup.functions.f"+u+".onUserSelect",GROUP_ID_FOR_SITE:n,selectedUsersIds:o};if(e.callbackOnChange){BX.Tasks.lwPopup.functions["f"+u].onUsersChange=e.callbackOnChange;d.onChangeFunctionName="BX.Tasks.lwPopup.functions.f"+u+".onUsersChange"}var c={object:BX.Tasks.lwPopup.functions["f"+u],ajaxParams:d};return c},_getDefaultTimeForInput:function(e){if(BX.type.isDomNode(e)){var t=BX.data(e,"default-time");if(typeof t!="undefined"){var a=t.toString().split(":");t={h:+a[0],m:+a[1],s:+a[2]}}else{t={h:19,m:0,s:0}}}return t},_showCalendar:function(e,t,a){if(typeof a==="undefined")var a={};var n=true;if(a.hasOwnProperty("bTime"))n=a.bTime;var s=false;if(a.hasOwnProperty("bHideTime"))s=a.bHideTime;var o=null;if(a.hasOwnProperty("callback_after"))o=a.callback_after;BX.calendar({node:e,field:t,bTime:n,value:BX.CJSTask.ui.getInputDateTimeValue(t),bHideTime:s,callback_after:o})},__firstRun:function(){if(BX.Tasks.lwPopup.firstRunDone)return;BX.Tasks.lwPopup.firstRunDone=true;var e=document.getElementsByTagName("body")[0];if(!BX(BX.Tasks.lwPopup.garbageAreaId)){e.appendChild(BX.create("DIV",{props:{id:BX.Tasks.lwPopup.garbageAreaId}}))}}}})();
/* End */
;
; /* Start:"a:4:{s:4:"full";s:60:"/bitrix/js/tasks/core_planner_handler.min.js?151972746512534";s:6:"source";s:40:"/bitrix/js/tasks/core_planner_handler.js";s:3:"min";s:44:"/bitrix/js/tasks/core_planner_handler.min.js";s:3:"map";s:44:"/bitrix/js/tasks/core_planner_handler.map.js";}"*/
(function(){if(!!window.BX.CTasksPlannerHandler)return;var t=window.BX,e={"-1":"overdue","-2":"new",1:"new",2:"accepted",3:"in-progress",4:"waiting",5:"completed",6:"delayed",7:"declined"},s=null;t.addTaskToPlanner=function(t){s.addTask({id:t})};t.CTasksPlannerHandler=function(){this.TASKS=null;this.TASKS_LIST=null;this.ADDITIONAL={};this.MANDATORY_UFS=null;this.TASK_CHANGES={add:[],remove:[]};this.TASK_CHANGES_TIMEOUT=null;this.TASKS_WND=null;this.DATA_TASKS=null;this.PLANNER=null;this.taskTimerSwitch=false;this.timerTaskId=0;this.onTimeManDataRecievedEventDetected=false;t.addCustomEvent("onPlannerDataRecieved",t.proxy(this.draw,this));t.addCustomEvent("onTaskTimerChange",t.proxy(this.onTaskTimerChange,this))};t.CTasksPlannerHandler.prototype.formatTime=function(t,e){var s=Math.floor(t/3600);var a=Math.floor(t/60)%60;var i=null;var r=(s<10?"0":"")+s.toString()+(a<10?":0":":")+a.toString();if(e){i=t%60;r=r+(i<10?":0":":")+i.toString()}return r};t.CTasksPlannerHandler.prototype.draw=function(e,s){if(typeof s.MANDATORY_UFS!=="undefined")this.MANDATORY_UFS=s.MANDATORY_UFS;if(!s.TASKS_ENABLED)return;this.PLANNER=e;if(null==this.TASKS){this.TASKS=t.create("DIV");this.TASKS.appendChild(t.create("DIV",{props:{className:"tm-popup-section tm-popup-section-tasks"},children:[t.create("SPAN",{props:{className:"tm-popup-section-text"},text:t.message("JS_CORE_PL_TASKS")}),t.create("span",{props:{className:"tm-popup-section-right-link"},events:{click:t.proxy(this.showTasks,this)},text:t.message("JS_CORE_PL_TASKS_CHOOSE")})]}));this.TASKS.appendChild(t.create("DIV",{props:{className:"tm-popup-tasks"},children:[this.TASKS_LIST=t.create("div",{props:{className:"tm-task-list"}}),this.drawTasksForm(t.proxy(this.addTask,this))]}))}else{t.cleanNode(this.TASKS_LIST)}if(s.TASKS&&s.TASKS.length>0){var a=null;var i="";var r=[];var n=0;var o=0;var T="";var l=null;t.removeClass(this.TASKS,"tm-popup-tasks-empty");for(var d=0,S=s.TASKS.length;d<S;d++){l=s.TASKS[d].STATUS==4||s.TASKS[d].STATUS==5;if(l)i=" tm-task-item-done";else i="";r=[];r.push(t.create("input",{props:{className:"tm-task-checkbox",type:"checkbox",checked:l},events:{click:function(e){return function(){var s=new t.CJSTask.Item(e.ID);if(this.checked){s.complete({callbackOnSuccess:function(){if(t.TasksTimerManager)t.TasksTimerManager.reLoadInitTimerDataFromServer()}})}else{s.startExecutionOrRenewAndStart({callbackOnSuccess:function(){if(t.TasksTimerManager)t.TasksTimerManager.reLoadInitTimerDataFromServer()}})}}}(s.TASKS[d])}}));var p=s.TASKS[d];if(p.ALLOW_TIME_TRACKING=="Y"&&(s.TASKS[d].TIME_SPENT_IN_LOGS>0||s.TASKS[d].TIME_ESTIMATE>0)){n=parseInt(s.TASKS[d].TIME_SPENT_IN_LOGS);o=parseInt(s.TASKS[d].TIME_ESTIMATE);if(isNaN(n))n=0;if(isNaN(o))o=0;T=this.formatTime(n,true);if(o>0)T=T+" / "+this.formatTime(o)}else T="";r.push(t.create("a",{attrs:{href:"javascript:void(0)"},props:{className:"tm-task-name"+(T===""?" tm-task-no-timer":"")},text:s.TASKS[d].TITLE,events:{click:t.proxy(this.showTask,this)}}));if(T!==""){r.push(t.create("SPAN",{props:{className:"tm-task-time",id:"tm-task-time-"+s.TASKS[d].ID},text:T}))}r.push(t.create("SPAN",{props:{className:"tm-task-item-menu"},events:{click:function(e,s,a){return function(){var i=[];var r="task-tm-item-entry-menu-"+e.ID;if(s&&s.TASK_ID==e.ID&&s.TIMER_STARTED_AT>0){i.push({text:t.message("JS_CORE_PL_TASKS_STOP_TIMER"),className:"menu-popup-item-hold",onclick:function(s){t.TasksTimerManager.stop(e.ID);this.popupWindow.close()}})}else{if(e.ALLOW_TIME_TRACKING==="Y"){i.push({text:t.message("JS_CORE_PL_TASKS_START_TIMER"),className:"menu-popup-item-begin",onclick:function(s){t.TasksTimerManager.start(e.ID);this.popupWindow.close()}})}}i.push({text:t.message("JS_CORE_PL_TASKS_MENU_REMOVE_FROM_PLAN"),className:"menu-popup-item-decline",onclick:function(t){a.removeTask(t,e.ID);this.popupWindow.close()}});var n=t.PopupMenu.getMenuById(r);if(n!==null){t.PopupMenu.destroy(r)}else{n=t.PopupMenu.show("task-tm-item-entry-menu-"+e.ID,this,i,{autoHide:true,offsetTop:4,events:{onPopupClose:function(t){}}})}}}(s.TASKS[d],s.TASKS_TIMER,this)}}));var m=this.TASKS_LIST.appendChild(t.create("div",{props:{id:"tm-task-item-"+s.TASKS[d].ID,className:"tm-task-item "+i,bx_task_id:s.TASKS[d].ID},children:r}));if(s.TASK_LAST_ID&&s.TASKS[d].ID==s.TASK_LAST_ID){a=m}}if(a){setTimeout(t.delegate(function(){if(a.offsetTop<this.TASKS_LIST.scrollTop||a.offsetTop+a.offsetHeight>this.TASKS_LIST.scrollTop+this.TASKS_LIST.offsetHeight){this.TASKS_LIST.scrollTop=a.offsetTop-parseInt(this.TASKS_LIST.offsetHeight/2)}},this),10)}}else{t.addClass(this.TASKS,"tm-popup-tasks-empty")}this.DATA_TASKS=t.clone(s.TASKS);e.addBlock(this.TASKS,200);e.addAdditional(this.drawAdditional())};t.CTasksPlannerHandler.prototype.drawAdditional=function(){if(!this.TASK_ADDITIONAL){this.ADDITIONAL.TASK_TEXT=t.create("SPAN",{props:{className:"tm-info-bar-text-inner"}});this.ADDITIONAL.TASK_TIMER=t.create("SPAN",{props:{className:"tm-info-bar-time"}});this.TASK_ADDITIONAL=t.create("DIV",{props:{className:"tm-info-bar"},children:[t.create("SPAN",{props:{title:t.message("JS_CORE_PL_TASKS_START_TIMER"),className:"tm-info-bar-btn tm-info-bar-btn-play"},events:{click:t.proxy(this.timerStart,this)}}),t.create("SPAN",{props:{title:t.message("JS_CORE_PL_TASKS_STOP_TIMER"),className:"tm-info-bar-btn tm-info-bar-btn-pause"},events:{click:t.proxy(this.timerStop,this)}}),t.create("SPAN",{props:{title:t.message("JS_CORE_PL_TASKS_FINISH"),className:"tm-info-bar-btn tm-info-bar-btn-flag"},events:{click:t.proxy(this.timerFinish,this)}}),this.ADDITIONAL.TASK_TIMER,t.create("SPAN",{props:{className:"tm-info-bar-text"},children:[this.ADDITIONAL.TASK_TEXT]})]});t.hide(this.TASK_ADDITIONAL)}return this.TASK_ADDITIONAL};t.CTasksPlannerHandler.prototype.timerStart=function(){if(this.timerTaskId>0){t.TasksTimerManager.start(this.timerTaskId)}};t.CTasksPlannerHandler.prototype.timerStop=function(){if(this.timerTaskId>0){t.TasksTimerManager.stop(this.timerTaskId)}};t.CTasksPlannerHandler.prototype.timerFinish=function(){if(this.timerTaskId>0){var e=new t.CJSTask.Item(this.timerTaskId);e.complete({callbackOnSuccess:function(){if(t.TasksTimerManager)t.TasksTimerManager.reLoadInitTimerDataFromServer()}})}};t.CTasksPlannerHandler.prototype.onTaskTimerChange=function(e){if(e.action==="refresh_daemon_event"){this.timerTaskId=e.taskId;if(this.PLANNER&&!!this.PLANNER.WND&&this.PLANNER.WND.isShown()&&e.taskId>0){var s=this.drawAdditional();if(!!this.taskTimerSwitch){s.style.display="";this.taskTimerSwitch=false}var a=parseInt(e.data.TIMER.RUN_TIME||0)+parseInt(e.data.TASK.TIME_SPENT_IN_LOGS||0),i=parseInt(e.data.TASK.TIME_ESTIMATE||0);if(i>0&&a>i){t.addClass(s,"tm-info-bar-overdue")}else{t.removeClass(s,"tm-info-bar-overdue")}var r="";r+=this.formatTime(a,true);if(i>0){r+=" / "+this.formatTime(i)}this.ADDITIONAL.TASK_TIMER.innerHTML=r;this.ADDITIONAL.TASK_TEXT.innerHTML=t.util.htmlspecialchars(e.data.TASK.TITLE);var n=t("tm-task-time-"+this.timerTaskId);if(n)n.innerHTML=r}}else if(e.action==="start_timer"){if(this.isClosed(e.taskData)){t.addClass(this.drawAdditional(),"tm-info-bar-closed")}else{t.removeClass(this.drawAdditional(),"tm-info-bar-closed")}this.timerTaskId=e.taskData.ID;this.taskTimerSwitch=true;t.addClass(this.drawAdditional(),"tm-info-bar-active");t.removeClass(this.drawAdditional(),"tm-info-bar-pause")}else if(e.action==="stop_timer"){this.timerTaskId=e.taskData.ID;if(this.isClosed(e.taskData)){t.hide(this.drawAdditional())}else{t.addClass(this.drawAdditional(),"tm-info-bar-pause");t.removeClass(this.drawAdditional(),"tm-info-bar-active")}}else if(e.action==="init_timer_data"){if(e.data.TIMER&&e.data.TASK.ID>0&&e.data.TIMER.TASK_ID==e.data.TASK.ID){this.timerTaskId=e.data.TASK.ID;if(this.isClosed(e.data.TASK)){t.addClass(this.drawAdditional(),"tm-info-bar-closed")}else{t.removeClass(this.drawAdditional(),"tm-info-bar-closed")}if(e.data.TIMER.TIMER_STARTED_AT==0){if(this.isClosed(e.data.TASK)){t.hide(this.drawAdditional())}else{this.taskTimerSwitch=true;t.addClass(this.drawAdditional(),"tm-info-bar-pause");t.removeClass(this.drawAdditional(),"tm-info-bar-active")}}else{this.taskTimerSwitch=true;t.addClass(this.drawAdditional(),"tm-info-bar-active");t.removeClass(this.drawAdditional(),"tm-info-bar-pause")}}else{t.hide(this.drawAdditional())}this.onTaskTimerChange({action:"refresh_daemon_event",taskId:+e.data.TASK.ID,data:e.data})}};t.CTasksPlannerHandler.prototype.isClosed=function(t){return t.STATUS==5||t.STATUS==4};t.CTasksPlannerHandler.prototype.addTask=function(e){if(!!this.TASKS_LIST){this.TASKS_LIST.appendChild(t.create("LI",{props:{className:"tm-popup-task"},text:e.name}));t.removeClass(this.TASKS,"tm-popup-tasks-empty")}var s={action:"add"};if(typeof e.id!="undefined")s.id=e.id;if(typeof e.name!="undefined")s.name=e.name;this.query(s)};t.CTasksPlannerHandler.prototype.removeTask=function(e,s){this.query({action:"remove",id:s});t.cleanNode(t("tm-task-item-"+s),true);if(!this.TASKS_LIST.firstChild){t.addClass(this.TASKS,"tm-popup-tasks-empty")}};t.CTasksPlannerHandler.prototype.showTasks=function(){if(!this.TASKS_WND){this.TASKS_WND=new t.CTasksPlannerSelector({node:t.proxy_context,onselect:t.proxy(this.addTask,this)})}else{this.TASKS_WND.setNode(t.proxy_context)}this.TASKS_WND.Show()};t.CTasksPlannerHandler.prototype.showTask=function(e){var s=t.proxy_context.parentNode.bx_task_id,a=this.DATA_TASKS,i=[];if(a.length>0){for(var r=0;r<a.length;r++){i.push(a[r].ID)}taskIFramePopup.tasksList=i;taskIFramePopup.view(s)}return false};t.CTasksPlannerHandler.prototype.drawTasksForm=function(e){var s=null;var a=null;var i=null;if(this.MANDATORY_UFS!=="Y"){s=t.delegate(function(s,i){a.value=t.util.trim(a.value);if(a.value&&a.value!=t.message("JS_CORE_PL_TASKS_ADD")){e({name:a.value});if(!i){t.addClass(a.parentNode,"tm-popup-task-form-disabled");a.value=t.message("JS_CORE_PL_TASKS_ADD")}else{a.value=""}}return t.PreventDefault(s)},this);var a=t.create("INPUT",{props:{type:"text",className:"tm-popup-task-form-textbox",value:t.message("JS_CORE_PL_TASKS_ADD")},events:{keypress:function(t){return t.keyCode==13?s(t,true):true},blur:function(){if(this.value==""){t.addClass(this.parentNode,"tm-popup-task-form-disabled");this.value=t.message("JS_CORE_PL_TASKS_ADD")}},focus:function(){t.removeClass(this.parentNode,"tm-popup-task-form-disabled");if(this.value==t.message("JS_CORE_PL_TASKS_ADD"))this.value=""}}});t.focusEvents(a);i=[a,t.create("SPAN",{props:{className:"tm-popup-task-form-submit"},events:{click:s}})]}else{i=[t.create("A",{text:t.message("JS_CORE_PL_TASKS_CREATE"),attrs:{href:"javascript:void(0)"},events:{click:function(){window["taskIFramePopup"].add({ADD_TO_TIMEMAN:"Y"})}}})]}return t.create("DIV",{props:{className:"tm-popup-task-form tm-popup-task-form-disabled"},children:i})};t.CTasksPlannerHandler.prototype.query=function(e,s){if(this.TASK_CHANGES_TIMEOUT){clearTimeout(this.TASK_CHANGES_TIMEOUT)}if(typeof e=="object"){if(!!e.id){this.TASK_CHANGES[e.action].push(e.id)}if(e.action=="add"){if(!e.id){this.TASK_CHANGES.name=e.name}this.query()}else{this.TASK_CHANGES_TIMEOUT=setTimeout(t.proxy(this.query,this),1e3)}}else{if(!!this.PLANNER){this.DATA_TASKS=[];this.PLANNER.query("task",this.TASK_CHANGES)}else{window.top.BX.CPlanner.query("task",this.TASK_CHANGES)}this.TASK_CHANGES={add:[],remove:[]}}};t.CTasksPlannerSelector=function(e){this.params=e;this.isReady=false;this.WND=t.PopupWindowManager.create("planner_tasks_selector_"+parseInt(Math.random()*1e4),this.params.node,{autoHide:true,closeByEsc:true,content:this.content=t.create("DIV"),buttons:[new t.PopupWindowButtonLink({text:t.message("JS_CORE_WINDOW_CLOSE"),className:"popup-window-button-link-cancel",events:{click:function(e){this.popupWindow.close();return t.PreventDefault(e)}}})]})};t.CTasksPlannerSelector.prototype.Show=function(){if(!this.isReady){var e=parseInt(Math.random()*1e4);window["PLANNER_ADD_TASK_"+e]=t.proxy(this.setValue,this);return t.ajax.get("/bitrix/tools/tasks_planner.php",{action:"list",suffix:e,sessid:t.bitrix_sessid(),site_id:t.message("SITE_ID")},t.proxy(this.Ready,this))}return this.WND.show()};t.CTasksPlannerSelector.prototype.Hide=function(){this.WND.close()};t.CTasksPlannerSelector.prototype.Ready=function(t){this.content.innerHTML=t;this.isReady=true;this.Show()};t.CTasksPlannerSelector.prototype.setValue=function(t){this.params.onselect(t);this.WND.close()};t.CTasksPlannerSelector.prototype.setNode=function(t){this.WND.setBindElement(t)};s=new t.CTasksPlannerHandler})();
/* End */
;
; /* Start:"a:4:{s:4:"full";s:57:"/bitrix/js/tasks/task-iframe-popup.min.js?151972746517646";s:6:"source";s:37:"/bitrix/js/tasks/task-iframe-popup.js";s:3:"min";s:41:"/bitrix/js/tasks/task-iframe-popup.min.js";s:3:"map";s:41:"/bitrix/js/tasks/task-iframe-popup.map.js";}"*/
(function(t){var e,i;var a=0;BX.TasksIFramePopup={create:function(e){if(!t.top.BX.TasksIFrameInst)t.top.BX.TasksIFrameInst=new s(e);if(e.events){for(var i in e.events){BX.removeCustomEvent(t.top.BX.TasksIFrameInst,i,e.events[i]);BX.addCustomEvent(t.top.BX.TasksIFrameInst,i,e.events[i])}}return t.top.BX.TasksIFrameInst}};var s=function(e){this.inited=false;this.pathToEdit="";this.pathToView="";this.iframeWidth=900;this.iframeHeight=400;this.topBottomMargin=15;this.leftRightMargin=50;this.tasksList=[];this.currentURL=t.location.href;this.currentTaskId=0;this.lastAction=null;this.loading=false;this.isEditMode=false;this.prevIframeSrc="";this.descriptionBuffered=null;if(e){if(e.pathToEdit){this.pathToEdit=e.pathToEdit+(e.pathToEdit.indexOf("?")==-1?"?":"&")+"IFRAME=Y"}if(e.pathToView){this.pathToView=e.pathToView+(e.pathToView.indexOf("?")==-1?"?":"&")+"IFRAME=Y"}if(e.tasksList){for(var i=0,a=e.tasksList.length;i<a;i++){this.tasksList[i]=parseInt(e.tasksList[i])}}}};s.prototype.init=function(){if(this.inited)return;this.inited=true;this.header=BX.create("div",{props:{className:"popup-window-titlebar"},html:'<table width="877" border="0" cellspacing="0" cellpadding="0"><tbody><tr><td align="left">&nbsp;</td><td width="13" style="padding-top: 2px;"><div class="tasks-iframe-close-icon">&nbsp;</div></td></tr></tbody></table>',style:{background:"#e8e8e8",height:"20px",padding:"5px 0px 5px 15px",borderRadius:"4px 4px 0px 0px"}});this.title=this.header.firstChild.tBodies[0].rows[0].cells[0];this.closeIcon=this.header.firstChild.tBodies[0].rows[0].cells[1].firstChild;this.closeIcon.onclick=BX.proxy(this.close,this);this.iframe=BX.create("iframe",{props:{scrolling:"no",frameBorder:"0"},style:{width:this.iframeWidth+"px",height:this.iframeHeight+"px",overflow:"hidden",border:"1px solid #fff",borderTop:"0px",borderRadius:"4px"}});this.prevTaskLink=BX.create("a",{props:{href:"javascript: void(0)",className:"tasks-popup-prev-slide"},html:"<span></span>"});this.closeLink=BX.create("a",{props:{href:"javascript: void(0)",className:"tasks-popup-close"},html:"<span></span>"});this.nextTaskLink=BX.create("a",{props:{href:"javascript: void(0)",className:"tasks-popup-next-slide"},html:"<span></span>"});this.prevTaskLink.onclick=BX.proxy(this.previous,this);this.nextTaskLink.onclick=BX.proxy(this.next,this);this.closeLink.onclick=BX.proxy(this.close,this);this.table=BX.create("table",{props:{className:"tasks-popup-main-table"},style:{top:this.topBottomMargin+"px"},children:[BX.create("tbody",{children:[BX.create("tr",{children:[this.prevTaskArea=BX.create("td",{props:{className:"tasks-popup-prev-slide-wrap"},children:[this.prevTaskLink]}),BX.create("td",{props:{id:"tasks-crazy-heavy-cpu-usage-item",className:"tasks-popup-main-block-wrap tasks-popup-main-block-wrap-bg"},children:[BX.create("div",{props:{className:"tasks-popup-main-block-inner"},children:[this.header,this.iframe]})]}),this.nextTaskArea=BX.create("td",{props:{className:"tasks-popup-next-slide-wrap"},children:[this.closeLink,this.nextTaskLink]})]})]})]});this.overlay=document.body.appendChild(BX.create("div",{props:{className:"tasks-fixed-overlay"},children:[BX.create("div",{props:{className:"bx-task-dialog-overlay"}}),this.table]}));this.__adjustControls();BX.bind(t.top,"resize",BX.proxy(this.__onWindowResize,this))};s.prototype.view=function(t,e){this.init();if(e){this.currentList=[];for(var i=0,a=e.length;i<a;i++){this.currentList[i]=parseInt(e[i])}}else{this.currentList=null}BX.adjust(this.title,{text:BX.message("TASKS_TASK_NUM").replace("#TASK_NUM#",t)});this.currentTaskId=t;this.lastAction="view";var s=true;this.load(this.pathToView.replace("#task_id#",t),s);this.show()};s.prototype.edit=function(t){this.init();BX.adjust(this.title,{text:BX.message("TASKS_TITLE_EDIT_TASK").replace("#TASK_ID#",t)});this.currentTaskId=t;this.lastAction="edit";this.load(this.pathToEdit.replace("#task_id#",t));this.show()};s.prototype.add=function(t){this.init();BX.adjust(this.title,{text:BX.message("TASKS_TITLE_CREATE_TASK")});this.currentTaskId=0;this.lastAction="add";var e=this.pathToEdit.replace("#task_id#",0)+"&UTF8encoded=1";this.descriptionBuffered=null;for(var i in t){if(i==="DESCRIPTION"&&t[i].length>1e3)this.descriptionBuffered=t[i];else e+="&"+i+"="+encodeURIComponent(t[i])}this.load(e);this.show()};s.prototype.show=function(){BX.onCustomEvent(this,"onBeforeShow",[]);this.overlay.style.display="block";BX.addClass(document.body,"tasks-body-overlay");this.closeLink.style.display="none";this.__onWindowResize();this.closeLink.style.display="block";BX.bind(this.iframe.contentDocument?this.iframe.contentDocument:this.iframe.contentWindow.document,"keypress",BX.proxy(this.__onKeyPress,this));BX.onCustomEvent(this,"onAfterShow",[])};s.prototype.close=function(){BX.onCustomEvent(this,"onBeforeHide",[]);this.overlay.style.display="none";BX.removeClass(document.body,"tasks-body-overlay");BX.unbind(this.iframe.contentDocument?this.iframe.contentDocument:this.iframe.contentWindow.document,"keypress",BX.proxy(this.__onKeyPress,this));BX("tasks-crazy-heavy-cpu-usage-item").className="tasks-popup-main-block-wrap tasks-popup-main-block-wrap-bg";BX.onCustomEvent(this,"onAfterHide",[])};s.prototype.previous=function(){var t=this.currentList?this.currentList:this.tasksList;if(this.currentTaskId&&t.length>1){var e=this.__indexOf(this.currentTaskId,t);if(e!=-1){if(e==0){var i=t.length-1}else{var i=e-1}this.view(t[i],t)}}};s.prototype.next=function(){var t=this.currentList?this.currentList:this.tasksList;if(this.currentTaskId&&t.length>1){var e=this.__indexOf(this.currentTaskId,t);if(e!=-1){if(e==t.length-1){var i=0}else{var i=e+1}this.view(t[i],t)}}};s.prototype.load=function(t,e){this.isEditMode=true;if(e===true)this.isEditMode=false;var i=this.iframe.contentWindow?this.iframe.contentWindow.location:"";this.__onUnload();this.iframe.src=t};s.prototype.isOpened=function(){this.init();return this.overlay.style.display=="block"};s.prototype.isEmpty=function(){this.init();return this.iframe.contentWindow.location=="about:blank"};s.prototype.isLeftClick=function(t){if(!t.which&&t.button!==undefined){if(t.button&1)t.which=1;else if(t.button&4)t.which=2;else if(t.button&2)t.which=3;else t.which=0}return t.which==1||t.which==0&&BX.browser.IsIE()};s.prototype.onTaskLoaded=function(){this.__onLoad()};s.prototype.onTaskAdded=function(t,e,i,a,s){this.tasksList.push(t.id);BX.onCustomEvent(this,"onTaskAdded",[t,e,i,a,s])};s.prototype.onTaskChanged=function(t,e,i,a,s){BX.onCustomEvent(this,"onTaskChanged",[t,e,i,a,s])};s.prototype.onTaskDeleted=function(t){BX.onCustomEvent(this,"onTaskDeleted",[t])};s.prototype.__onKeyPress=function(e){if(!e)e=t.event;if(e.keyCode==27){if(this.lastAction==="view"||confirm(BX.message("TASKS_CONFIRM_CLOSE_CREATE_DIALOG"))){this.close()}}};s.prototype.__indexOf=function(t,e){for(var i=0,a=e.length;i<a;i++){if(t==e[i]){return i}}return-1};s.prototype.__onMouseMove=function(t){if(!t)t=this.iframe.contentWindow.event;var e=this.iframe.contentDocument?this.iframe.contentDocument:this.iframe.contentWindow.document;if(e&&e.body){e.body.onbeforeunload=BX.proxy(this.__onUnload,this);if(this.iframe.contentDocument)this.iframe.contentDocument.body.onbeforeunload=BX.proxy(this.__onBeforeUnload,this);e.body.onunload=BX.proxy(this.__onUnload,this);var i=t.target||t.srcElement;if(i){eTargetA=false;if(i&&i.tagName=="SPAN"){var a=BX.findParent(i);if(a!==null&&a.tagName=="A")eTargetA=a}else eTargetA=i;if(eTargetA.tagName=="A"&&eTargetA.href){if(eTargetA.href.substr(0,11)=="javascript:"){e.body.onbeforeunload=null;e.body.onunload=null}else if(eTargetA.href.indexOf("IFRAME=Y")==-1&&eTargetA.href.indexOf("/show_file.php?fid=")==-1&&eTargetA.target!=="_blank"){eTargetA.target="_top"}}}}};s.prototype.__onLoad=function(){if(!this.isEmpty()){var a=this.iframe.contentDocument?this.iframe.contentDocument:this.iframe.contentWindow.document;if(a&&a.body){if(BX("tasks-crazy-heavy-cpu-usage-item"))BX("tasks-crazy-heavy-cpu-usage-item").className="tasks-popup-main-block-wrap";this.loading=false;a.body.onmousemove=BX.proxy(this.__onMouseMove,this);if(!a.getElementById("task-reminder-link")){t.top.location=a.location.href.replace("?IFRAME=Y","").replace("&IFRAME=Y","").replace("&CALLBACK=CHANGED","").replace("&CALLBACK=ADDED","")}i=this.iframe.contentWindow.location.href;BX.bind(a,"keyup",BX.proxy(this.__onKeyPress,this));this.iframe.style.height=a.getElementById("tasks-content-outer").offsetHeight+"px";this.iframe.style.visibility="visible";this.iframe.contentWindow.focus();this.__onWindowResize()}if(e)clearInterval(e);e=setInterval(BX.proxy(this.__onContentResize,this),300)}};s.prototype.__onBeforeUnload=function(t){};s.prototype.__onUnload=function(i){if(!i)i=t.event;if(!this.loading){this.loading=true;this.iframe.style.visibility="hidden";clearInterval(e)}};s.prototype.__onContentResize=function(){if(this.isOpened()){var t=this.iframe.contentDocument?this.iframe.contentDocument:this.iframe.contentWindow.document;if(t&&t.body){var e=t.getElementById("tasks-content-outer");if(e){var i=this.__getWindowScrollHeight(t);var s=BX.GetWindowInnerSize(t);var n=0;if(i>s.innerHeight)n=i-1;else n=e.offsetHeight;var r=this.iframe.contentWindow?this.iframe.contentWindow.location:"";if(r.toString)r=r.toString();if(n!=a||this.prevIframeSrc!=r){a=n;this.prevIframeSrc=r;this.iframe.style.height=n+"px";this.__onWindowResize()}}}}};s.prototype.__getWindowScrollHeight=function(t){var e;if(!t)t=document;if(t.compatMode&&t.compatMode=="CSS1Compat"&&!BX.browser.IsSafari()){e=t.documentElement.scrollHeight}else{if(t.body.scrollHeight>t.body.offsetHeight)e=t.body.scrollHeight;else e=t.body.offsetHeight}return e};s.prototype.__onWindowResize=function(){var t=BX.GetWindowInnerSize();this.overlay.style.height=t.innerHeight+"px";this.overlay.style.width=t.innerWidth+"px";var e=BX.GetWindowScrollPos();this.overlay.style.top=e.scrollTop+"px";if(BX.browser.IsIE()&&!BX.browser.IsIE9()){this.table.style.width=t.innerWidth-20+"px"}this.overlay.firstChild.style.height=Math.max(this.iframe.offsetHeight+this.topBottomMargin*2+31,this.overlay.clientHeight)+"px";this.overlay.firstChild.style.width=Math.max(1024,this.overlay.clientWidth)+"px";this.prevTaskArea.style.width=Math.max(0,Math.max(1024,this.overlay.clientWidth)/2)+"px";this.nextTaskArea.style.width=this.prevTaskArea.style.width;this.__adjustControls()};s.prototype.__adjustControls=function(){if(this.lastAction!="view"||(!this.currentList||this.currentList.length<=1||this.__indexOf(this.currentTaskId,this.currentList)==-1)&&(this.tasksList.length<=1||this.__indexOf(this.currentTaskId,this.tasksList)==-1)){this.nextTaskLink.style.display=this.prevTaskLink.style.display="none"}else{if(!BX.browser.IsDoctype()&&BX.browser.IsIE()){this.nextTaskLink.style.height=this.prevTaskLink.style.height=document.documentElement.offsetHeight+"px";this.prevTaskLink.style.width=this.prevTaskLink.parentNode.clientWidth-1+"px";this.nextTaskLink.style.width=this.nextTaskLink.parentNode.clientWidth-1+"px"}else{this.nextTaskLink.style.height=this.prevTaskLink.style.height=document.documentElement.clientHeight+"px";this.prevTaskLink.style.width=this.prevTaskLink.parentNode.clientWidth+"px";this.nextTaskLink.style.width=this.nextTaskLink.parentNode.clientWidth+"px"}this.prevTaskLink.firstChild.style.left=this.prevTaskLink.parentNode.clientWidth*4/10+"px";this.nextTaskLink.firstChild.style.right=this.nextTaskLink.parentNode.clientWidth*4/10+"px";this.nextTaskLink.style.display=this.prevTaskLink.style.display=""}this.closeLink.style.width=this.closeLink.parentNode.clientWidth+"px"}})(window);(function(){if(BX.TasksTimerManager)return;BX.TasksTimerManager={popup:null,onTimeManDataRecievedEventDetected:false};BX.TasksTimerManager.reLoadInitTimerDataFromServer=function(){var t=true;if(window.BXTIMEMAN)window.BXTIMEMAN.Update(true);else if(window.BXPLANNER&&window.BXPLANNER.update)window.BXPLANNER.update();else t=false;if(window.top!==window){if(window.top.BXTIMEMAN)window.top.BXTIMEMAN.Update(true);else if(window.top.BXPLANNER&&window.top.BXPLANNER.update)window.top.BXPLANNER.update()}return t};BX.TasksTimerManager.start=function(t){BX.CJSTask.batchOperations([{operation:"CTaskTimerManager::getLastTimer()"}],{callbackOnSuccess:function(t){return function(e){if(e.rawReply.data[0].returnValue&&e.rawReply.data[0].returnValue.TASK_ID>0&&e.rawReply.data[0].returnValue.TIMER_STARTED_AT>0&&t!=e.rawReply.data[0].returnValue.TASK_ID){BX.CJSTask.batchOperations([{operation:"CTaskItem::getTaskData()",taskData:{ID:e.rawReply.data[0].returnValue.TASK_ID}}],{callbackOnSuccess:function(t){return function(e){if(e.rawReply.data[0].returnValue.ID&&t!=e.rawReply.data[0].returnValue.ID){BX.TasksTimerManager.__showConfirmPopup(e.rawReply.data[0].returnValue.ID,e.rawReply.data[0].returnValue.TITLE,function(t){return function(e){if(e)BX.TasksTimerManager.__doStart(t)}}(t))}}}(t),callbackOnFailure:function(t){return function(e){BX.TasksTimerManager.__doStart(t)}}(t)},true)}else BX.TasksTimerManager.__doStart(t)}}(t)},true)};BX.TasksTimerManager.stop=function(t){var e=new BX.CJSTask.TimerManager(t);e.stop({callbackOnSuccess:function(t){if(t.status==="success"){BX.onCustomEvent(window,"onTaskTimerChange",[{module:"tasks",action:"stop_timer",taskId:t.rawReply.data[0].requestedTaskId,taskData:t.rawReply.data[1].returnValue,timerData:t.rawReply.data[2].returnValue}])}}})};BX.TasksTimerManager.__doStart=function(t){var e=new BX.CJSTask.TimerManager(t);e.start({callbackOnSuccess:function(t){if(t.status==="success"){BX.onCustomEvent(window,"onTaskTimerChange",[{module:"tasks",action:"start_timer",taskId:t.rawReply.data[0].requestedTaskId,taskData:t.rawReply.data[1].returnValue,timerData:t.rawReply.data[2].returnValue}])}}})};BX.TasksTimerManager.__showConfirmPopup=function(t,e,i){if(this.popup){this.popup.close();this.popup.destroy()}var a=BX.message("TASKS_TASK_CONFIRM_START_TIMER");a=a.replace("{{TITLE}}",BX.util.htmlspecialchars(e));var s=BX.create("span",{html:BX.message("TASKS_TASK_CONFIRM_START_TIMER_TITLE")});BX.Tasks.confirm(a,BX.delegate(function(t){i(t)},this),{title:s})};BX.TasksTimerManager.refreshDaemon=new function(){this.data=null;this.onTick=function(){if(this.data!==null){var t=Math.round((new Date).getTime()/1e3);this.data.TIMER.RUN_TIME=t-this.data.TIMER.TIMER_STARTED_AT-this.data.UNIX_TIMESTAMP_DELTA;BX.onCustomEvent(window,"onTaskTimerChange",[{action:"refresh_daemon_event",taskId:this.data.TIMER.TASK_ID,data:this.data}])}};BX.ready(function(t){return function(){BX.CJSTask.setTimerCallback("tasks_timer_refresh_daemon_event",function(t){return function(){t.onTick()}}(t),1024)}}(this));this.catchTimerChange=function(t){if(t.module!=="tasks")return;if(t.action==="refresh_daemon_event"){return}else if(t.action==="stop_timer"){this.data=null;BX.TasksTimerManager.reLoadInitTimerDataFromServer()}else if(t.action==="start_timer"){if(!(t.timerData&&t.timerData.USER_ID)||t.timerData.TASK_ID!=t.taskData.ID){this.data=null;return}if(t.timerData.TIMER_STARTED_AT==0){this.data=null;return}var e=0;var i=Math.round((new Date).getTime()/1e3);var a=parseInt(t.timerData.RUN_TIME);var s=parseInt(t.taskData.TIME_SPENT_IN_LOGS);var n=parseInt(t.timerData.TIMER_STARTED_AT);if(isNaN(a))a=0;if(isNaN(s))s=0;if(n>0)e=i-n-a;this.data={TIMER:{TASK_ID:parseInt(t.timerData.TASK_ID),USER_ID:parseInt(t.timerData.USER_ID),TIMER_STARTED_AT:n,RUN_TIME:a},TASK:{ID:t.taskData.ID,TITLE:t.taskData.TITLE,TIME_SPENT_IN_LOGS:s,TIME_ESTIMATE:parseInt(t.taskData.TIME_ESTIMATE),ALLOW_TIME_TRACKING:t.taskData.ALLOW_TIME_TRACKING},UNIX_TIMESTAMP_DELTA:e};BX.TasksTimerManager.reLoadInitTimerDataFromServer()}else if(t.action==="init_timer_data"){if(!(t.data.TIMER&&t.data.TIMER.USER_ID)||t.data.TIMER.TASK_ID!=t.data.TASK.ID){this.data=null;return}if(t.data.TIMER.TIMER_STARTED_AT==0){this.data=null;return}var e=0;var i=Math.round((new Date).getTime()/1e3);var a=parseInt(t.data.TIMER.RUN_TIME);var s=parseInt(t.data.TASK.TIME_SPENT_IN_LOGS);var n=parseInt(t.data.TIMER.TIMER_STARTED_AT);if(isNaN(a))a=0;if(isNaN(s))s=0;if(n>0)e=i-n-a;this.data={TIMER:{TASK_ID:parseInt(t.data.TIMER.TASK_ID),USER_ID:parseInt(t.data.TIMER.USER_ID),TIMER_STARTED_AT:n,RUN_TIME:a},TASK:{ID:t.data.TASK.ID,TITLE:t.data.TASK.TITLE,TIME_SPENT_IN_LOGS:s,TIME_ESTIMATE:parseInt(t.data.TASK.TIME_ESTIMATE),ALLOW_TIME_TRACKING:t.data.TASK.ALLOW_TIME_TRACKING},UNIX_TIMESTAMP_DELTA:e}}};BX.addCustomEvent(window,"onTaskTimerChange",function(t){return function(e){t.catchTimerChange(e)}}(this))};BX.TasksTimerManager.onDataRecieved=function(t){var e=0;var i={TIMER:false,TASK:false};if(!t)return;if(t.TASKS_TIMER){if(parseInt(t.TASKS_TIMER.TIMER_STARTED_AT)>0)e=Math.round((new Date).getTime()/1e3)-parseInt(t.TASKS_TIMER.TIMER_STARTED_AT);if(e<0)e=0;i.TIMER={TASK_ID:t.TASKS_TIMER.TASK_ID,USER_ID:t.TASKS_TIMER.USER_ID,TIMER_STARTED_AT:t.TASKS_TIMER.TIMER_STARTED_AT,RUN_TIME:e}}if(t.TASK_ON_TIMER){i.TASK={ID:t.TASK_ON_TIMER.ID,TITLE:t.TASK_ON_TIMER.TITLE,STATUS:t.TASK_ON_TIMER.STATUS,TIME_SPENT_IN_LOGS:t.TASK_ON_TIMER.TIME_SPENT_IN_LOGS,TIME_ESTIMATE:t.TASK_ON_TIMER.TIME_ESTIMATE,ALLOW_TIME_TRACKING:t.TASK_ON_TIMER.ALLOW_TIME_TRACKING}}BX.onCustomEvent(window,"onTaskTimerChange",[{action:"init_timer_data",module:"tasks",data:i}])};BX.addCustomEvent(window,"onTimeManDataRecieved",function(t){BX.TasksTimerManager.onTimeManDataRecievedEventDetected=true;if(t.PLANNER)BX.TasksTimerManager.onDataRecieved(t.PLANNER)});BX.addCustomEvent(window,"onPlannerDataRecieved",function(t,e){if(BX.TasksTimerManager.onTimeManDataRecievedEventDetected===false)BX.TasksTimerManager.onDataRecieved(e)})})();
/* End */
;
; /* Start:"a:4:{s:4:"full";s:37:"/bitrix/js/main/dd.js?151972746714772";s:6:"source";s:21:"/bitrix/js/main/dd.js";s:3:"min";s:0:"";s:3:"map";s:0:"";}"*/
;(function(){

if (window.jsDD)
	return;

jsDD = {
	arObjects: [],
	arDestinations: [],
	arDestinationsPriority: [],

	arContainers: [],
	arContainersPos: [],

	current_dest_index: false,
	current_node: null,

	wndSize: null,

	bStarted: false,
	bDisable: false,
	bDisableDestRefresh: false,

	bEscPressed: false,

	bScrollWindow: false,
	scrollViewTimer: null,
	scrollViewConfig: {
		checkerTimeout: 30,
		scrollZone: 25,
		scrollBy: 25,
		scrollContainer: null,
		bScrollH: true,
		bScrollV: true,
		pos: null
	},

	setScrollWindow: function(val)
	{
		jsDD.bScrollWindow = !!val;
		if (BX.type.isDomNode(val))
		{
			jsDD.scrollViewConfig.scrollContainer = val;
			jsDD.scrollViewConfig.pos = BX.pos(val);

			var s = BX.style(val, 'overflow') || 'visible',
				s1 = BX.style(val, 'overflow-x') || 'visible',
				s2 = BX.style(val, 'overflow-y') || 'visible';

			jsDD.scrollViewConfig.bScrollH = s != 'visible' || s1 != 'visible';
			jsDD.scrollViewConfig.bScrollV = s != 'visible' || s2 != 'visible';
		}
	},

	Reset: function()
	{
		jsDD.arObjects = [];
		jsDD.arDestinations = [];
		arDestinationsPriority = [];
		jsDD.bStarted = false;
		jsDD.current_node = null;
		jsDD.current_dest_index = false;
		jsDD.bDisableDestRefresh = false;
		jsDD.bDisable = false;
		jsDD.x = null;
		jsDD.y = null;
		jsDD.start_x = null;
		jsDD.start_y = null;
		jsDD.wndSize = null;

		jsDD.bEscPressed = false;

		clearInterval(jsDD.scrollViewTimer)
		jsDD.bScrollWindow = false;
		jsDD.scrollViewTimer = null;
		jsDD.scrollViewConfig.scrollContainer = null;
	},

	registerObject: function (obNode)
	{
		BX.bind(obNode, 'mousedown', jsDD.startDrag);
		BX.bind(obNode, 'touchstart', jsDD.startDrag);

		obNode.__bxddid = jsDD.arObjects.length;

		jsDD.arObjects[obNode.__bxddid] = obNode;
	},
	unregisterObject: function(obNode)
	{
		if(typeof(obNode["__bxddid"]) === "undefined")
		{
			return;
		}

		delete jsDD.arObjects[obNode.__bxddid];
		delete obNode.__bxddid;
		BX.unbind(obNode, 'mousedown', jsDD.startDrag);
		BX.unbind(obNode, 'touchstart', jsDD.startDrag);
	},
	registerDest: function (obDest, priority)
	{
		if (!priority)
			priority = 100;

		obDest.__bxddeid = jsDD.arDestinations.length;
		obDest.__bxddpriority = priority;

		jsDD.arDestinations[obDest.__bxddeid] = obDest;
		if (!jsDD.arDestinationsPriority[priority])
			jsDD.arDestinationsPriority[priority] = [obDest.__bxddeid]
		else
			jsDD.arDestinationsPriority[priority].push(obDest.__bxddeid);

		jsDD.refreshDestArea(obDest.__bxddeid);
	},
	unregisterDest: function(obDest)
	{
		if(typeof(obDest["__bxddeid"]) === "undefined")
		{
			return;
		}

		delete jsDD.arDestinations[obDest.__bxddeid];
		delete obDest.__bxddeid;
		delete obDest.__bxddpriority;

		jsDD.refreshDestArea();
	},
	disableDest: function(obDest)
	{
		if (typeof(obDest.__bxddeid) !== "undefined")
		{
			obDest.__bxdddisabled = true;
		}
	},

	enableDest: function(obDest)
	{
		if (typeof(obDest.__bxddeid) !== "undefined")
		{
			obDest.__bxdddisabled = false;
		}
	},

	registerContainer: function (obCont)
	{
		jsDD.arContainers[jsDD.arContainers.length] = obCont;
	},

	getContainersScrollPos: function(x, y)
	{
		var pos = {'left':0, 'top':0};
		for(var i=0, n=jsDD.arContainers.length; i<n; i++)
		{
			if(jsDD.arContainers[i] && x >= jsDD.arContainersPos[i]["left"] && x <= jsDD.arContainersPos[i]["right"] && y >= jsDD.arContainersPos[i]["top"] && y <= jsDD.arContainersPos[i]["bottom"])
			{
				pos.left = jsDD.arContainers[i].scrollLeft;
				pos.top = jsDD.arContainers[i].scrollTop;
			}
		}
		return pos;
	},

	setContainersPos: function()
	{
		for(var i=0, n=jsDD.arContainers.length; i<n; i++)
		{
			if(jsDD.arContainers[i])
				jsDD.arContainersPos[i] = BX.pos(jsDD.arContainers[i]);
		}
	},

	refreshDestArea: function(id)
	{
		if (id && typeof (id) == "object" && typeof (id.__bxddeid) != 'undefined')
		{
			id = id.__bxddeid;
		}

		if (typeof id == 'undefined')
		{
			for (var i = 0, cnt = jsDD.arDestinations.length; i < cnt; i++)
			{
				jsDD.refreshDestArea(i);
			}
		}
		else
		{
			if (null == jsDD.arDestinations[id])
				return;

			var arPos = BX.pos(jsDD.arDestinations[id]);
			jsDD.arDestinations[id].__bxpos = [arPos.left, arPos.top, arPos.right, arPos.bottom];
		}
	},

	_checkEsc: function(e)
	{
		e = e||window.event;
		if (jsDD.bStarted && e.keyCode == 27)
		{
			jsDD.stopCurrentDrag();
		}
	},

	stopCurrentDrag: function()
	{
		if (jsDD.bStarted)
		{
			jsDD.bEscPressed = true;
			jsDD.stopDrag();
		}
	},

	/* scroll checkers */

	_onscroll: function() {
		jsDD.wndSize = BX.GetWindowSize();
	},

	_checkScroll: function()
	{
		if (jsDD.bScrollWindow)
		{
			var pseudo_e = {
					clientX: jsDD.x - jsDD.wndSize.scrollLeft,
					clientY: jsDD.y - jsDD.wndSize.scrollTop
				},
				bChange = false,
				d = jsDD.scrollViewConfig.scrollZone;

			// check whether window scroll needed
			if (pseudo_e.clientY < d && jsDD.wndSize.scrollTop > 0)
			{
				window.scrollBy(0, -jsDD.scrollViewConfig.scrollBy);
				bChange = true;
			}

			if (pseudo_e.clientY > jsDD.wndSize.innerHeight - d && jsDD.wndSize.scrollTop < jsDD.wndSize.scrollHeight - jsDD.wndSize.innerHeight)
			{
				window.scrollBy(0, jsDD.scrollViewConfig.scrollBy);
				bChange = true;
			}

			if (pseudo_e.clientX < d && jsDD.wndSize.scrollLeft > 0)
			{
				window.scrollBy(-jsDD.scrollViewConfig.scrollBy, 0);
				bChange = true;
			}

			if (pseudo_e.clientX > jsDD.wndSize.innerWidth - d && jsDD.wndSize.scrollLeft < jsDD.wndSize.scrollWidth - jsDD.wndSize.innerWidth)
			{
				window.scrollBy(jsDD.scrollViewConfig.scrollBy, 0);
				bChange = true;
			}

			// check whether container scroll needed

			if (jsDD.scrollViewConfig.scrollContainer)
			{
				var c = jsDD.scrollViewConfig.scrollContainer;

				if (jsDD.scrollViewConfig.bScrollH)
				{
					if (pseudo_e.clientX + jsDD.wndSize.scrollLeft < jsDD.scrollViewConfig.pos.left + d && c.scrollLeft > 0)
					{
						c.scrollLeft -= jsDD.scrollViewConfig.scrollBy;
						bChange = true;
					}

					if (pseudo_e.clientX + jsDD.wndSize.scrollLeft > jsDD.scrollViewConfig.pos.right - d
						&& c.scrollLeft < c.scrollWidth - c.offsetWidth)
					{
						c.scrollLeft += jsDD.scrollViewConfig.scrollBy;
						bChange = true;
					}
				}

				if (jsDD.scrollViewConfig.bScrollV)
				{
					if (pseudo_e.clientY + jsDD.wndSize.scrollTop < jsDD.scrollViewConfig.pos.top + d && c.scrollTop > 0)
					{
						c.scrollTop -= jsDD.scrollViewConfig.scrollBy;
						bChange = true;
					}

					if (pseudo_e.clientY + jsDD.wndSize.scrollTop > jsDD.scrollViewConfig.pos.bottom - d
						&& c.scrollTop < c.scrollHeight - c.offsetHeight)
					{
						c.scrollTop += jsDD.scrollViewConfig.scrollBy;
						bChange = true;
					}
				}
			}

			if (bChange)
			{
				jsDD._onscroll();
				jsDD.drag(pseudo_e);
			}
		}
	},

	/* DD process */

	startDrag: function(e)
	{
		if (jsDD.bDisable)
			return true;

		e = e || window.event;

		if (!(BX.getEventButton(e)&BX.MSLEFT))
			return true;

		jsDD.current_node = null;
		if (e.currentTarget)
		{
			jsDD.current_node = e.currentTarget;
			if (null == jsDD.current_node || null == jsDD.current_node.__bxddid)
			{
				jsDD.current_node = null;
				return;
			}
		}
		else
		{
			jsDD.current_node = e.srcElement;
			if (null == jsDD.current_node)
				return;

			while (null == jsDD.current_node.__bxddid)
			{
				jsDD.current_node = jsDD.current_node.parentNode;
				if (jsDD.current_node.tagName == 'BODY')
					return;
			}
		}

		jsDD.bStarted = false;
		jsDD.bPreStarted = true;

		jsDD.wndSize = BX.GetWindowSize();

		jsDD.start_x = e.clientX + jsDD.wndSize.scrollLeft;
		jsDD.start_y = e.clientY + jsDD.wndSize.scrollTop;

		BX.bind(document, "mouseup", jsDD.stopDrag);
		BX.bind(document, "touchend", jsDD.stopDrag);
		BX.bind(document, "mousemove", jsDD.drag);
		BX.bind(document, "touchmove", jsDD.drag);
		BX.bind(window, 'scroll', jsDD._onscroll);

		if(document.body.setCapture)
			document.body.setCapture();

		if (!jsDD.bDisableDestRefresh)
			jsDD.refreshDestArea();

		jsDD.setContainersPos();

		if(e.type !== "touchstart")
		{
			jsDD.denySelection();
			return BX.PreventDefault(e);
		}
		else
		{
			return true;
		}
	},

	start: function()
	{
		if (jsDD.bDisable)
			return true;

		document.body.style.cursor = 'move';

		if (jsDD.current_node.onbxdragstart)
			jsDD.current_node.onbxdragstart();

		for (var i = 0, cnt = jsDD.arDestinations.length; i < cnt; i++)
		{
			if (jsDD.arDestinations[i] && jsDD.arDestinations[i].onbxdestdragstart)
				jsDD.arDestinations[i].onbxdestdragstart(jsDD.current_node);
		}

		jsDD.bStarted = true;
		jsDD.bPreStarted = false;

		if (jsDD.bScrollWindow)
		{
			if (jsDD.scrollViewTimer)
				clearInterval(jsDD.scrollViewTimer);

			jsDD.scrollViewTimer = setInterval(jsDD._checkScroll, jsDD.scrollViewConfig.checkerTimeout);
		}

		BX.bind(document, 'keypress', this._checkEsc);
	},

	drag: function(e)
	{
		if (jsDD.bDisable)
			return true;

		e = e || window.event;

		jsDD.x = e.clientX + jsDD.wndSize.scrollLeft;
		jsDD.y = e.clientY + jsDD.wndSize.scrollTop;

		if (!jsDD.bStarted)
		{
			var delta = 5;
			if(jsDD.x >= jsDD.start_x-delta && jsDD.x <= jsDD.start_x+delta && jsDD.y >= jsDD.start_y-delta && jsDD.y <= jsDD.start_y+delta)
				return true;

			jsDD.start();
		}

		if (jsDD.current_node.onbxdrag)
		{
			jsDD.current_node.onbxdrag(jsDD.x, jsDD.y);
		}

		var containersScroll = jsDD.getContainersScrollPos(jsDD.x, jsDD.y);
		var current_dest_index = jsDD.searchDest(jsDD.x+containersScroll.left, jsDD.y+containersScroll.top);

		if (current_dest_index !== jsDD.current_dest_index)
		{
			if (jsDD.current_dest_index !== false)
			{
				if (jsDD.current_node.onbxdraghout)
					jsDD.current_node.onbxdraghout(jsDD.arDestinations[jsDD.current_dest_index], jsDD.x, jsDD.y);

				if (jsDD.arDestinations[jsDD.current_dest_index].onbxdestdraghout)
					jsDD.arDestinations[jsDD.current_dest_index].onbxdestdraghout(jsDD.current_node, jsDD.x, jsDD.y);
			}

			if (current_dest_index !== false)
			{
				if (jsDD.current_node.onbxdraghover)
					jsDD.current_node.onbxdraghover(jsDD.arDestinations[current_dest_index], jsDD.x, jsDD.y);

				if (jsDD.arDestinations[current_dest_index].onbxdestdraghover)
					jsDD.arDestinations[current_dest_index].onbxdestdraghover(jsDD.current_node, jsDD.x, jsDD.y);
			}
		}

		jsDD.current_dest_index = current_dest_index;
	},

	stopDrag: function(e)
	{
		BX.unbind(document, 'keypress', jsDD._checkEsc);

		e = e || window.event;

		jsDD.bPreStarted = false;

		if (jsDD.bStarted)
		{
			if (!jsDD.bEscPressed)
			{
				jsDD.x = e.clientX + jsDD.wndSize.scrollLeft;
				jsDD.y = e.clientY + jsDD.wndSize.scrollTop;
			}

			if (null != jsDD.current_node.onbxdragstop)
				jsDD.current_node.onbxdragstop(jsDD.x, jsDD.y);

			var containersScroll = jsDD.getContainersScrollPos(jsDD.x, jsDD.y);
			var dest_index = jsDD.searchDest(jsDD.x+containersScroll.left, jsDD.y+containersScroll.top);

			if (false !== dest_index)
			{
				if (jsDD.bEscPressed)
				{
					if (null != jsDD.arDestinations[dest_index].onbxdestdraghout)
					{
						if (!jsDD.arDestinations[dest_index].onbxdestdraghout(jsDD.current_node, jsDD.x, jsDD.y))
							dest_index = false;
						else
						{
							if (null != jsDD.current_node.onbxdragfinish)
								jsDD.current_node.onbxdragfinish(jsDD.arDestinations[dest_index], jsDD.x, jsDD.y);
						}
					}

				}
				else
				{
					if (null != jsDD.arDestinations[dest_index].onbxdestdragfinish)
					{
						if (!jsDD.arDestinations[dest_index].onbxdestdragfinish(jsDD.current_node, jsDD.x, jsDD.y, e))
							dest_index = false;
						else
						{
							if (null != jsDD.current_node.onbxdragfinish)
								jsDD.current_node.onbxdragfinish(jsDD.arDestinations[dest_index], jsDD.x, jsDD.y);
						}
					}
				}
			}

			if (false === dest_index)
			{
				if (null != jsDD.current_node.onbxdragrelease)
					jsDD.current_node.onbxdragrelease(jsDD.x, jsDD.y);
			}
			else
			{
				for (var i = 0, cnt = jsDD.arDestinations.length; i < cnt; i++)
				{
					if (i != dest_index && jsDD.arDestinations[i] && null != jsDD.arDestinations[i].onbxdestdragrelease)
						jsDD.arDestinations[i].onbxdestdragrelease(jsDD.current_node, jsDD.x, jsDD.y);
				}
			}

			for (var i = 0, cnt = jsDD.arDestinations.length; i < cnt; i++)
			{
				if (jsDD.arDestinations[i] && null != jsDD.arDestinations[i].onbxdestdragstop)
					jsDD.arDestinations[i].onbxdestdragstop(jsDD.current_node, jsDD.x, jsDD.y);
			}
		}

		if(document.body.releaseCapture)
			document.body.releaseCapture();

		BX.unbind(window, 'scroll', jsDD._onscroll);
		BX.unbind(document, "mousemove", jsDD.drag);
		BX.unbind(document, "touchmove", jsDD.drag);
		BX.unbind(document, "keypress", jsDD._checkEsc);
		BX.unbind(document, "mouseup", jsDD.stopDrag);
		BX.unbind(document, "touchend", jsDD.stopDrag);

		jsDD.allowSelection();
		document.body.style.cursor = '';

		jsDD.current_node = null;
		jsDD.current_dest_index = false;

		if (jsDD.bScrollWindow)
		{
			if (jsDD.scrollViewTimer)
				clearInterval(jsDD.scrollViewTimer);
		}

		if (jsDD.bStarted && !jsDD.bDisableDestRefresh)
			jsDD.refreshDestArea();

		jsDD.bStarted = false;
		jsDD.bEscPressed = false;
	},

	searchDest: function(x, y)
	{
		var p, len, p1, len1, i;
		for (p = 0, len = jsDD.arDestinationsPriority.length; p < len; p++)
		{
			if (jsDD.arDestinationsPriority[p] && BX.type.isArray(jsDD.arDestinationsPriority[p]))
			{
				for (p1 = 0, len1 = jsDD.arDestinationsPriority[p].length; p1 < len; p1++)
				{
					i = jsDD.arDestinationsPriority[p][p1];
					if (jsDD.arDestinations[i] && !jsDD.arDestinations[i].__bxdddisabled)
					{
						if (
							jsDD.arDestinations[i].__bxpos[0] <= x &&
							jsDD.arDestinations[i].__bxpos[2] >= x &&

							jsDD.arDestinations[i].__bxpos[1] <= y &&
							jsDD.arDestinations[i].__bxpos[3] >= y
							)
						{
							return i;
						}
					}
				}
			}
		}

		return false;
	},

	allowSelection: function()
	{
		document.onmousedown = document.ontouchstart = null;
		var b = document.body;
		b.ondrag = null;
		b.onselectstart = null;
		b.style.MozUserSelect = '';

		if (jsDD.current_node)
		{
			jsDD.current_node.ondrag = null;
			jsDD.current_node.onselectstart = null;
			jsDD.current_node.style.MozUserSelect = '';
		}
	},

	denySelection: function()
	{
		document.onmousedown = document.ontouchstart = BX.False;
		var b = document.body;
		b.ondrag = BX.False;
		b.onselectstart = BX.False;
		b.style.MozUserSelect = 'none';
		if (jsDD.current_node)
		{
			jsDD.current_node.ondrag = BX.False;
			jsDD.current_node.onselectstart = BX.False;
			jsDD.current_node.style.MozUserSelect = 'none';
		}
	},

	Disable: function() {jsDD.bDisable = true;},
	Enable: function() {jsDD.bDisable = false;}
}

})();

/* End */
;
; /* Start:"a:4:{s:4:"full";s:48:"/bitrix/js/tasks/util/base.min.js?15197274657590";s:6:"source";s:29:"/bitrix/js/tasks/util/base.js";s:3:"min";s:33:"/bitrix/js/tasks/util/base.min.js";s:3:"map";s:33:"/bitrix/js/tasks/util/base.map.js";}"*/
BX.namespace("Tasks.Util");BX.Tasks.Util.Base=function(t){};BX.mergeEx(BX.Tasks.Util.Base.prototype,{construct:function(){var t=this.option("earlyBind");if(t){for(var e in t){if(t.hasOwnProperty(e)&&BX.type.isFunction(t[e])){this.bindEvent(e,t[e])}}}},fireEvent:function(t,e){BX.onCustomEvent(this,t,e)},bindEvent:function(t,e,i){if(i){e=BX.delegate(e,i)}BX.addCustomEvent(this,t,e)},callMethod:function(t,e,i){if(!BX.type.isNotEmptyString(e)){throw new Error("Illegal method name: "+e)}if(!BX.type.isFunction(t.prototype[e])){throw new Error("No such method in class: "+e)}return t.prototype[e].apply(this,i)},callConstruct:function(t){this.callMethod(t,"construct")},runParentConstructor:function(t){if(typeof t.superclass=="object"){t.superclass.constructor.apply(this,[null,true])}},walkPrototypeChain:function(t,e){var i=t.constructor;while(typeof i!="undefined"&&i!=null){e.apply(this,[i.prototype,i.superclass]);if(typeof i.superclass=="undefined"){return}i=i.superclass.constructor}},destroy:function(){this.walkPrototypeChain(this,function(t){if(typeof t.destruct=="function"){t.destruct.call(this)}})},option:function(t,e){if(typeof e!="undefined"){this.opts[t]=e}else{return typeof this.opts[t]!="undefined"?this.opts[t]:false}},optionInteger:function(t){var e=parseInt(this.option(t));return isNaN(e)?0:e},subInstance:function(t,e){this.instances=this.instances||{};if(e){if(BX.type.isFunction(e)){if(typeof this.instances[t]=="undefined"){var i=e.call(this);if(i instanceof BX.Tasks.Util.Widget){i.parent(this)}this.instances[t]=i}}else{this.instances[t]=e}return this.instances[t]}else{if(typeof t!="undefined"&&BX.type.isNotEmptyString(t)){return this.instances[t]?this.instances[t]:null}return null}},initialized:function(){return this.sys.initialized},passCtx:function(t){var e=this;return function(){var i=Array.prototype.slice.call(arguments);i.unshift(this);return t.apply(e,i)}},id:function(t){if(typeof t!="undefined"&&BX.type.isNotEmptyString(t)){this.sys.id=t.toString().toLowerCase()}else{return this.sys.id}},register:function(){if(this.option("registerDispatcher")){var t=this.id();if(t){BX.Tasks.Util.Dispatcher.register(t,this)}}}});BX.Tasks.Util.Base.extend=function(t){if(typeof t=="undefined"||!BX.type.isPlainObject(t)){t={}}var e=function(i,s){if(!("runParentConstructor"in this)){throw new TypeError('Did you miss "new" when creating an instance?')}this.runParentConstructor(e);if(typeof this.opts=="undefined"){this.opts={registerDispatcher:false}}if(typeof t.options!="undefined"&&BX.type.isPlainObject(t.options)){BX.mergeEx(this.opts,t.options)}if(typeof this.sys=="undefined"){this.sys={id:false,initialized:false}}if(typeof t.sys!="undefined"&&BX.type.isPlainObject(t.sys)){BX.mergeEx(this.sys,t["sys"])}delete t;delete e;if(!s){if(typeof i!="undefined"&&BX.type.isPlainObject(i)){BX.mergeEx(this.opts,i)}this.id(this.option("id"));this.register();this.construct();this.sys.initialized=true}};e.extend=BX.Tasks.Util.Base.extend;BX.extend(e,this);t.methods=t.methods||{};t.constants=t.constants||{};if(typeof t.methods!="undefined"&&BX.type.isPlainObject(t.methods)){for(var i in t.methods){if(t.methods.hasOwnProperty(i)){e.prototype[i]=t.methods[i]}}}if(BX.type.isPlainObject(t.methodsStatic)){for(var s in t.methodsStatic){if(t.methodsStatic.hasOwnProperty(s)){e[s]=t.methodsStatic[s]}}}if(typeof t.constants!="undefined"&&BX.type.isPlainObject(t.constants)){for(var n in t.constants){if(t.constants.hasOwnProperty(n)){e.prototype[n]=t.constants[n]}}}if(typeof t.methods.construct!="function"){var r=this;e.prototype.construct=function(){this.callConstruct(r);delete r}}if(typeof t.methods.destruct!="function"){e.prototype.destruct=BX.DoNothing()}return e};BX.Tasks.Util.Dispatcher=BX.Tasks.Util.Base.extend({methods:{construct:function(){this.callConstruct(BX.Tasks.Util.Base);this.vars={registry:{},pend:{bind:{},call:{},find:{}}}},destruct:function(){this.vars=null},registerInstance:function(t,e){if(!BX.type.isNotEmptyString(t)){throw new ReferenceError("Trying to register while id is empty")}if(e==null||e==false){throw new ReferenceError("Bad instance")}if(typeof this.vars.registry[t]!="undefined"){throw new ReferenceError('The id "'+t.toString()+'" is already in use in registry')}this.vars.registry[t]=e;if(typeof this.vars.pend.bind[t]!="undefined"){for(var i in this.vars.pend.bind[t]){this.vars.registry[t].bindEvent(this.vars.pend.bind[t][i].event,this.vars.pend.bind[t][i].cb)}delete this.vars.pend.bind[t]}if(typeof this.vars.pend.call[t]!="undefined"){BX.Tasks.Util.each(this.vars.pend.call[t],function(t){if(!(t.method in e)){t.pr.reject()}else{t.pr.resolve(e[t.method].call(e,t.args))}});delete this.vars.pend.call[t]}if(typeof this.vars.pend.find[t]!="undefined"){BX.Tasks.Util.each(this.vars.pend.find[t],function(t){t.pr.resolve(e)});delete this.vars.pend.find[t]}},getRegistry:function(){var t={};BX.Tasks.each(this.vars.registry,function(e,i){t[i]=e});return t},get:function(t){if(typeof this.vars.registry[t]=="undefined"){return null}return this.vars.registry[t]},find:function(t){var e=new BX.Promise;t=this.castToLiteralString(t);if(!t){e.reject();return e}var i=this.get(t);if(i){e.resolve(i)}else{if(typeof this.vars.pend.find[t]=="undefined"){this.vars.pend.find[t]=[]}this.vars.pend.find[t].push({pr:e})}return e},call:function(t,e,i){var s=new BX.Promise;t=this.castToLiteralString(t);e=this.castToLiteralString(e);if(!t||!e){s.reject();return s}var n=this.get(t);if(n!==null){if(!(e in n)){s.reject();return s}else{s.resolve(n[e].call(n,i||[]))}}else{this.vars.pend.call[t].push({method:e,args:i||[],pr:s})}return s},addDeferredBind:function(t,e,i){if(!BX.type.isNotEmptyString(t)){throw new TypeError("Bad id: "+t)}if(!BX.type.isNotEmptyString(e)){throw new TypeError("Bad event name: "+e)}if(!BX.type.isFunction(i)){throw new TypeError("Callback is not a function to call for: "+t+" "+e)}if(typeof this.vars.registry[t]!="undefined"){this.vars.registry[t].bindEvent(e,i)}else{if(typeof this.vars.pend.bind[t]=="undefined"){this.vars.pend.bind[t]=[]}this.vars.pend.bind[t].push({event:e,cb:i})}},addDeferredFire:function(t,e,i,s){if(!BX.type.isNotEmptyString(t)){throw new TypeError("Bad id: "+t)}if(!BX.type.isNotEmptyString(e)){throw new TypeError("Bad event name: "+e)}i=i||[];if(typeof this.vars.registry[t]!="undefined"){this.vars.registry[t].fireEvent(e,i)}else{}},castToLiteralString:function(t){if(typeof t=="undefined"||t===null){return null}t=t.toString().trim();if(!BX.type.isNotEmptyString(t)){return null}return t}}});BX.Tasks.Util.Dispatcher.register=function(t,e){BX.Tasks.Util.Dispatcher.getInstance().registerInstance(t,e)};BX.Tasks.Util.Dispatcher.getRegistry=function(){return BX.Tasks.Util.Dispatcher.getInstance().getRegistry()};BX.Tasks.Util.Dispatcher.call=function(t,e,i){return BX.Tasks.Util.Dispatcher.getInstance().call(t,e,i)};BX.Tasks.Util.Dispatcher.find=function(t){return BX.Tasks.Util.Dispatcher.getInstance().find(t)};BX.Tasks.Util.Dispatcher.getInstance=function(){if(typeof BX.Tasks.Singletons=="undefined"){BX.Tasks.Singletons={}}if(typeof BX.Tasks.Singletons.dispatcher=="undefined"){BX.Tasks.Singletons.dispatcher=new BX.Tasks.Util.Dispatcher({registerDispatcher:false})}return BX.Tasks.Singletons.dispatcher};BX.Tasks.Util.Dispatcher.bindEvent=function(t,e,i){BX.Tasks.Util.Dispatcher.getInstance().addDeferredBind(t,e,i)};BX.Tasks.Util.Dispatcher.fireEvent=function(t,e,i,s){BX.Tasks.Util.Dispatcher.getInstance().addDeferredFire(t,e,i,s)};BX.Tasks.Util.Dispatcher.get=function(t){return BX.Tasks.Util.Dispatcher.getInstance().get(t)};
/* End */
;
; /* Start:"a:4:{s:4:"full";s:49:"/bitrix/js/tasks/util/query.min.js?15197274659539";s:6:"source";s:30:"/bitrix/js/tasks/util/query.js";s:3:"min";s:34:"/bitrix/js/tasks/util/query.min.js";s:3:"map";s:34:"/bitrix/js/tasks/util/query.map.js";}"*/
"use strict";BX.namespace("Tasks.Util");BX.Tasks.Util.Query=BX.Tasks.Util.Base.extend({options:{url:"/bitrix/components/bitrix/tasks.base/ajax.php",autoExec:false,replaceDuplicateCode:true,autoExecDelay:100,translateBooleanToZeroOne:true,emitter:""},methods:{construct:function(){this.callConstruct(BX.Tasks.Util.Base);this.vars={batch:[],local:{},prevLocal:{}};this.autoExecute=BX.debounce(this.autoExecute,this.option("autoExecDelay"),this)},destruct:function(){this.vars=null;this.opts=null},autoExecute:function(){if(this.option("autoExec")){this.execute()}},add:function(t,e,s,r){if(typeof t=="undefined"){throw new ReferenceError("Method name was not provided")}t=t.toString();if(t.length==0){throw new ReferenceError("Method name must not be empty")}var i;if(typeof e=="undefined"||!BX.type.isPlainObject(e)){e={}}for(i in e){if(e.hasOwnProperty(i)){e[i]=this.processArguments(BX.clone(e[i]))}}if(typeof s=="undefined"||!BX.type.isPlainObject(s)){s={}}s.code=this.pickCode(s);if(this.option("replaceDuplicateCode")){for(i=0;i<this.vars.batch.length;i++){if(this.vars.batch[i].PARAMETERS.code==s.code){this.vars.batch.splice(i,1);break}}}this.vars.batch.push({OPERATION:t,ARGUMENTS:e,PARAMETERS:s});if(BX.type.isFunction(r)){r={onExecuted:r}}else{r=r||{}}r.pr=new BX.Promise(null,r.promiseCtx);this.vars.local[s.code]=r;this.autoExecute();return this},run:function(t,e,s,r,i){s=BX.type.isPlainObject(s)?s:{};s.code=this.pickCode(s);this.add(t,e,s,r);r=BX.type.isPlainObject(r)?BX.clone(r):{};r.promiseCtx=i;this.add(t,e,s,r);return this.vars.local[s.code].pr},pickCode:function(t){var e="";if(BX.type.isPlainObject(t)){e=t.code}if(!BX.type.isNotEmptyString(e)){e="op_"+this.vars.batch.length}return e},processArguments:function(t){var e=typeof t;if(e=="array"){if(t.length==0){return""}for(var s=0;s<e.length;s++){t[s]=this.processArguments(t[s])}}if(e=="object"){var r=0;for(var s in t){t[s]=this.processArguments(t[s]);r++}if(r==0){return""}}if(e=="boolean"&&this.option("translateBooleanToZeroOne")){return t===true?"1":"0"}return t},load:function(t){if(BX.type.isArray(t)){this.clear();for(var e=0;e<t.length;e++){this.add(t[e].m,t[e].args,t[e].rp)}}return this},deleteAll:function(){this.vars.batch=[];this.vars.local={};return this},clear:function(){return this.deleteAll()},execute:function(t){if(this.opts.url===false){throw new ReferenceError("URL was not provided")}if(typeof t=="undefined"){t={}}var e=new BX.Promise;t.pr=e;if(this.vars.batch.length>0){this.vars.prevLocal=null;this.vars.prevLocal=this.vars.local;this.vars.local=null;var s=this.vars.batch;this.clear();BX.ajax({url:this.opts.url,method:"post",dataType:"json",async:true,processData:true,emulateOnload:true,start:true,data:{sessid:BX.bitrix_sessid(),SITE_ID:BX.message("SITE_ID"),EMITTER:this.option("emitter"),ACTION:s},cache:false,onsuccess:function(e){try{if(!e){e={SUCCESS:false,ERROR:[{CODE:"INTERNAL_ERROR",MESSAGE:BX.message("TASKS_ASSET_QUERY_EMPTY_RESPONSE"),TYPE:"FATAL"}],ASSET:[],DATA:{}}}var s="";if(BX.type.isArray(e.ASSET)){s=e.ASSET.join("")}BX.html(null,s).then(function(){this.processResult({success:e.SUCCESS,clientProcessErrors:[],serverProcessErrors:e.ERROR,data:e.DATA||{},response:e},t)}.bind(this))}catch(r){BX.debug(r);this.processResult({success:false,clientProcessErrors:[{CODE:"INTERNAL_ERROR",MESSAGE:BX.message("TASKS_ASSET_QUERY_QUERY_FAILED_EXCEPTION"),TYPE:"FATAL"}],serverProcessErrors:[],data:{}},t)}}.bind(this),onfailure:function(e,s){console.dir(e);console.dir(s);var r=BX.message("TASKS_ASSET_QUERY_QUERY_FAILED");if(e=="processing"){r=BX.message("TASKS_ASSET_QUERY_ILLEGAL_RESPONSE")}else if(e=="status"){r=BX.message("TASKS_ASSET_QUERY_QUERY_FAILED_STATUS").replace("#HTTP_STATUS#",s)}this.processResult({success:false,clientProcessErrors:[{CODE:"INTERNAL_ERROR",MESSAGE:r,TYPE:"FATAL",ajaxExtra:{code:e,status:s}}],serverProcessErrors:[],data:{}},t)}.bind(this)})}return e},processResult:function(t,e){this.executeDone(t,e.done,e.pr);this.fireEvent("executed",[t])},executeDone:function(t,e,s){var r=this.getErrorCollectionClass();var i=new r;var n;var o;n=t.serverProcessErrors||[];for(o=0;o<n.length;o++){i.add(n[o],"C")}n=t.clientProcessErrors||[];for(o=0;o<n.length;o++){i.add(n[o],"C")}var a=BX.clone(t.data);var u=new r(i);var l;var c=new BX.Tasks.Util.Query.Result(i,a);if(t.success){for(var h in a){if(a.hasOwnProperty(h)){l=null;l=new r(u);n=t.data[h].ERRORS||[];for(o=0;o<n.length;o++){l.add(n[o])}delete a[h].ERRORS;delete a[h].SUCCESS;if(BX.type.isFunction(this.vars.prevLocal[h].onExecuted)){this.vars.prevLocal[h].onExecuted.apply(this,[l,a[h]])}this.vars.prevLocal[h].pr.fulfill(new BX.Tasks.Util.Query.Result(l,a[h].RESULT));l.deleteByMark("C");i.load(l)}}if(s instanceof BX.Promise){s.fulfill(c)}}else{BX.Tasks.each(this.vars.prevLocal,function(t){t.pr.reject(new BX.Tasks.Util.Query.Result(u,null))});if(s instanceof BX.Promise){s.reject(c)}}if(BX.type.isFunction(e)){e.apply(this,[i,t])}if(i.checkHasErrors()){BX.onCustomEvent("TaskAjaxError",[i])}},getErrorCollectionClass:function(){return BX.Tasks.Util.Query.ErrorCollection}}});BX.Tasks.Util.Query.runOnce=function(t,e){return new this({autoExec:true}).run(t,e)};BX.Tasks.Util.Query.Result=function(t,e){this.errors=t?t:new BX.Tasks.Util.Query.ErrorCollection;this.data=e?e:{}};BX.mergeEx(BX.Tasks.Util.Query.Result.prototype,{isSuccess:function(){return this.errors.filter({TYPE:"FATAL"}).isEmpty()},getData:function(){return this.data},getErrors:function(){return this.errors}});BX.Tasks.Util.Query.ErrorCollection=function(t){this.length=0;if(typeof t!="undefined"){this.load(t)}};BX.mergeEx(BX.Tasks.Util.Query.ErrorCollection.prototype,{add:function(t,e){this[this.length++]=new BX.Tasks.Util.Query.Error(BX.clone(t),e)},load:function(t){for(var e=0;e<t.length;e++){this.add(t[e],false)}},isEmpty:function(){return!this.length},filter:function(t){var e=new this.constructor;for(var s=0;s<this.length;s++){if(this.hasOwnProperty(s)){var r=true;if(BX.type.isPlainObject(t)){if("TYPE"in t){if(this[s].getType()!=t.TYPE){r=false}}}if(r){e.add(this[s])}}}return e},getMessages:function(t){var e=[];for(var s=0;s<this.length;s++){if(this.hasOwnProperty(s)){var r=this[s].getMessage();e.push(t?BX.util.htmlspecialchars(r):r)}}return e},getByCode:function(t){if(!BX.type.isNotEmptyString(t)){return false}for(var e=0;e<this.length;e++){if(this[e].checkIsOfCode(t)){return BX.clone(this[e])}}return null},deleteByCodeAll:function(t){if(!BX.type.isNotEmptyString(t)){return}this.deleteByCondition(function(e){return e.checkIsOfCode(t)})},deleteByMark:function(t){if(!BX.type.isNotEmptyString(t)){return}this.deleteByCondition(function(e){return e.mark()==t})},deleteByCondition:function(t){var e=[];for(var s=0;s<this.length;s++){if(!t.apply(this,[this[s]])){e.push(this[s])}}this.deleteAll(false);this.load(e)},deleteAll:function(t){for(var e=0;e<this.length;e++){if(t!==false){this[e]=null}delete this[e]}this.length=0},checkHasErrors:function(){return!!this.length}});BX.Tasks.Util.Query.Error=function(t,e){for(var s in t){if(t.hasOwnProperty(s)){this[s]=BX.clone(t[s])}}this.vars={mark:e}};BX.mergeEx(BX.Tasks.Util.Query.Error.prototype,{getCode:function(){return this.CODE},getType:function(){return this.TYPE},getMessage:function(){return this.MESSAGE},checkIsOfCode:function(t){return this.CODE==t||BX.util.in_array(t,this.CODE.toString().split("."))},code:function(){return this.getCode()},mark:function(){return this.vars.mark},data:function(){if(BX.type.isPlainObject(this.DATA)){return this.DATA}return{}}});BX.Tasks.Util.Query.Iterator=BX.Tasks.Util.Base.extend({options:{url:"",timeout:500},methods:{construct:function(){this.callConstruct(BX.Tasks.Util.Base);this.reset()},getQuery:function(){return this.subInstance("query",function(){return new BX.Tasks.Util.Query({url:this.option("url"),autoExec:true,autoExecDelay:1})})},reset:function(){this.vars=this.vars||{};this.vars.running=false;this.vars.step=0;this.vars.timer=null;this.vars.ajaxRun=false;this.vars.ajaxAbort=false},setStopped:function(t){this.vars.running=false;this.fireEvent("stop",[t])},start:function(){if(this.vars.running){return}this.reset();this.vars.running=true;this.fireEvent("start");this.hit()},stop:function(){if(!this.vars.running){return}clearInterval(this.vars.timer);if(this.vars.ajaxRun){this.vars.ajaxAbort=true}else{this.setStopped()}},hit:function(){this.vars.ajaxRun=true;this.getQuery().run(this.option("handler"),{parameters:{step:this.vars.step++}}).then(BX.delegate(function(t){this.vars.ajaxRun=false;if(this.vars.ajaxAbort){this.setStopped()}else{if(t.isSuccess()){var e=new BX.Promise(null,this);this.fireEvent("hit",[e,t.getData(),t]);e.then(function(){this.vars.timer=setTimeout(BX.delegate(this.hit,this),this.optionInteger("timeout"))},function(){this.setStopped()})}else{this.fireEvent("error",[t.getErrors(),t]);this.setStopped(t.getErrors())}}},this),BX.delegate(function(t){this.fireEvent("error",[t.getErrors(),t]);this.setStopped(t.getErrors())},this))}}});BX.Tasks.Util.InputGrabber=function(){};BX.Tasks.Util.InputGrabber.grabFrom=function(t,e){var s={};if(t&&BX.type.isElementNode(t)&&t.nodeName=="FORM"){var r=0;for(var i=0;i<t.length;i++){if(t[i].name!=""&&!t[i].disabled){if(t[i].nodeName=="INPUT"&&t[i].getAttribute("type")=="checkbox"&&!t[i].checked){continue}var n=t[i].name;if(e){var o=t[i].name.toString().replace(/\]/g,"").split("[");var a=s;for(r=0;r<o.length;r++){if(typeof a[o[r]]=="undefined"){a[o[r]]=r==o.length-1?t[i].value:{}}a=a[o[r]]}}else{s[n]=t[i].value}}}return s}};
/* End */
;
//# sourceMappingURL=kernel_tasks.map.js