
; /* Start:"a:4:{s:4:"full";s:63:"/bitrix/templates/bitrix24/slider/slider.min.js?151972746511806";s:6:"source";s:43:"/bitrix/templates/bitrix24/slider/slider.js";s:3:"min";s:47:"/bitrix/templates/bitrix24/slider/slider.min.js";s:3:"map";s:47:"/bitrix/templates/bitrix24/slider/slider.map.js";}"*/
BX.namespace("BX.Bitrix24");BX.Bitrix24.PageSlider={defaultOptions:{panelStylezIndex:3200,imStylezIndex:3300,imBarStylezIndex:3100,headerStylezIndex:3100,maxWidth:"auto",minWidth:"auto",overlayClassName:"slider-panel-overlay",overlayTop:false,containerClassName:"slider-panel-container",closeBtn:"slider-panel-close",contentClassName:"slider-panel-content-container",contentPadding:0,typeLoader:"default-loader",top:"inherit",right:"inherit",bottom:"inherit",height:"inherit",width:"inherit",anchorRules:[]},init:function(){if(this.inited){return}this.panel=BX("bx-panel");this.header=BX("header");this.imBar=BX("bx-im-bar");this.isOpen=false;this.inited=true;this.iframeSrc=null;this.animation=null;this.animationDuration=200;this.startParams={translateX:100,opacity:0};this.endParams={translateX:0,opacity:100};this.currentParams=null;this.createLayout();BX.addCustomEvent("BX.Bitrix24.PageSlider:close",this.close.bind(this));BX.addCustomEvent("BX.Bitrix24.Map:onBeforeOpen",this.close.bind(this,true))},setOptions:function(t){t=BX.type.isPlainObject(t)?t:{};this.defaultOptions=BX.mergeEx(this.defaultOptions,t)},createLayout:function(){this.overlay=BX.create("div",{props:{className:this.options.overlayClassName},events:{click:this.close.bind(this)}});this.container=BX.create("div",{props:{className:this.options.containerClassName}});this.closeBtn=BX.create("span",{props:{className:this.options.closeBtn},children:[BX.create("span",{props:{className:this.options.closeBtn+"-inner"}})],events:{click:this.close.bind(this)}});this.content=BX.create("div",{props:{className:this.options.contentClassName}});this.iframe=BX.create("iframe",{attrs:{src:"about:blank",frameborder:"0"},events:{load:this.onIframeLoad.bind(this)}});this.container.appendChild(this.content);this.container.appendChild(this.closeBtn);this.content.appendChild(this.iframe);document.body.appendChild(this.overlay);document.body.appendChild(this.container)},getLoaderId:function(t){var e=this.options.typeLoader;var i=this.getUrlRule(t);if(i&&BX.type.isNotEmptyString(i.loader)){e=i.loader}return e},createLoader:function(t){BX.remove(this.loader);t=BX.type.isNotEmptyString(t)?t:"default-loader";if(t==="default-loader"){this.loader=BX.create("div",{props:{className:"slider-panel-loader "+t},children:[BX.create("div",{props:{className:"b24-loader b24-loader-show"},children:[BX.create("div",{props:{className:"b24-loader-curtain"}})]})]})}else{this.loader=BX.create("div",{props:{className:"slider-panel-loader "+t},children:[BX.create("img",{attrs:{src:"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAwAAAAMCAMAAABhq6zVAAAAA1BMVEX///+nxBvIAAAAAXRSTlMAQObYZgAAAAtJREFUeAFjGMQAAACcAAG25ruvAAAAAElFTkSuQmCC"},props:{className:"slider-panel-loader-mask left"}}),BX.create("img",{attrs:{src:"data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAwAAAAMCAMAAABhq6zVAAAAA1BMVEX///+nxBvIAAAAAXRSTlMAQObYZgAAAAtJREFUeAFjGMQAAACcAAG25ruvAAAAAElFTkSuQmCC"},props:{className:"slider-panel-loader-mask right"}})]})}this.loader.dataset.loader=t;this.content.appendChild(this.loader)},showLoader:function(t){if(!this.loader||this.loader.dataset.loader!==t){this.createLoader(t)}this.loader.style.opacity=1;this.loader.style.display="block"},closeLoader:function(){this.loader.style.display="none";this.loader.style.opacity=0},onIframeLoad:function(t){var e=this.iframe.contentWindow;var i=e.location;if(i.toString()==="about:blank"){return}this.closeLoader();BX.bind(e,"keyup",this.onDocumentKeyDown.bind(this));if(e.BX){e.BX.onCustomEvent("BX.Bitrix24.PageSlider:onOpen",[this])}var n=i.pathname+i.search+i.hash;this.iframeSrc=BX.util.remove_url_param(n,["IFRAME","IFRAME_TYPE"])},adjustLayout:function(){var t=0;var e=0;var i="100vh";if(this.options.overlayTop===false){var n=BX.pos(this.header);var s=window.pageYOffset||document.documentElement.scrollTop;t=n.bottom-s;if(t<0){t=0}i="calc(100vh - "+t+"px)";if(this.imBar){e=this.imBar.offsetWidth}}else{if(this.imBar){this.imBar.style.removeProperty("z-index")}}var a="100%";var r=this.options.maxWidth+"px";var o=this.options.minWidth>this.options.maxWidth?0:this.options.minWidth;if(this.options.maxWidth==="auto"){var h=240;var l=this.imBar?this.imBar.offsetWidth:0;var d=document.documentElement.clientWidth;var c=window.innerWidth-d;var p=h+l+c;if(d<1160){p-=175}a="calc(100% - "+p+"px)";r="auto"}this.overlay.style.height=i;this.overlay.style.top=t+"px";this.container.style.width=a;this.container.style.height=i;this.container.style.minWidth=o+"px";this.container.style.maxWidth=r+"px";this.container.style.top=t+"px";this.container.style.right=e+"px"},setFrameSrc:function(t){if(BX.type.isNotEmptyString(t)){if(this.iframeSrc!==t){this.iframeSrc=t;this.iframe.src=BX.util.add_url_param(t,{IFRAME:"Y",IFRAME_TYPE:"SIDE_SLIDER"})}}else{this.iframeSrc=null}},open:function(t,e){if(!BX.type.isNotEmptyString(t)){return}e=BX.type.isPlainObject(e)?e:{};this.options=BX.mergeEx({},this.defaultOptions,e);if(!this.isOpen){this.init();this.applyHacks();this.adjustLayout();this.bindEvents()}if(this.iframeSrc!==t){this.setFrameSrc(t);var i=this.getLoaderId(t);this.showLoader(i)}if(BX.type.isDomNode(document.activeElement)){document.activeElement.blur()}this.animateOpening();this.isOpen=true},animateOpening:function(){if(this.isOpen){return}BX.addClass(this.overlay,"slider-panel-overlay-open");BX.addClass(this.container,"slider-panel-container-open");if(this.animation){this.animation.stop()}this.animation=new BX.easing({duration:this.animationDuration,start:this.currentParams?this.currentParams:this.startParams,finish:this.endParams,transition:BX.easing.transitions.linear,step:BX.delegate(function(t){this.currentParams=t;this.animateStep(t)},this),complete:BX.delegate(function(){this.completeAnimation()},this)});this.animation.animate()},close:function(t){if(!this.isOpen){if(this.animation){this.animation.stop(true)}return}this.isOpen=false;this.unbindEvents();if(this.animation){this.animation.stop()}var e=this.iframe.contentWindow;if(e.BX){e.BX.onCustomEvent("BX.Bitrix24.PageSlider:onClose",[this])}if(t===true){this.currentParams=this.startParams;this.completeAnimation()}else{this.animation=new BX.easing({duration:this.animationDuration,start:this.currentParams,finish:this.startParams,transition:BX.easing.transitions.linear,step:BX.delegate(function(t){this.currentParams=t;this.animateStep(t)},this),complete:BX.delegate(function(){this.completeAnimation()},this)});this.animation.animate()}},completeAnimation:function(){this.animation=null;if(this.isOpen){this.currentParams=this.endParams}else{this.currentParams=this.startParams;BX.removeClass(this.overlay,"slider-panel-overlay-open");BX.removeClass(this.container,"slider-panel-container-open");this.container.style.removeProperty("width");this.container.style.removeProperty("right");this.container.style.removeProperty("max-width");this.container.style.removeProperty("min-width");this.closeBtn.style.removeProperty("opacity");this.resetHacks()}},applyHacks:function(){var t=window.innerWidth-document.documentElement.clientWidth;document.body.style.paddingRight=t+"px";document.body.style.overflow="hidden";this.header.style.paddingRight=t+"px";this.header.style.marginRight="-"+t+"px";this.header.style.zIndex=this.options.overlayTop===false?this.options.headerStylezIndex:"inherit";if(this.imBar){this.imBar.style.zIndex=this.options.imBarStylezIndex;this.imBar.style.width=this.imBar.offsetWidth+t-1+"px"}if(this.panel){this.panel.style.zIndex=this.options.panelStylezIndex}},resetHacks:function(){document.body.style.cssText="";if(this.panel){this.panel.style.removeProperty("z-index")}if(this.imBar){this.imBar.style.removeProperty("z-index");this.imBar.style.removeProperty("width")}this.header.style.cssText=""},animateStep:function(t){this.container.style.transform="translateX("+t.translateX+"%)";this.overlay.style.opacity=t.opacity/100;this.closeBtn.style.opacity=t.opacity/100},extractLinkFromEvent:function(t){t=t||window.event;var e=t.target;if(t.which!==1||!BX.type.isDomNode(e)||t.ctrlKey||t.metaKey){return null}var i=e;if(e.nodeName!=="A"){i=BX.findParent(e,{tag:"A"},1)}if(!BX.type.isDomNode(i)){return null}var n=i.getAttribute("href");if(n&&!BX.data(i,"slider-ignore-autobinding")&&!BX.ajax.isCrossDomain(n)){return{url:n,anchor:i,target:i.getAttribute("target")}}return null},handleClick:function(t){var e=this.extractLinkFromEvent(t);if(!e){return}var i=this.getUrlRule(e.url,e);if(!i){return}var n=BX.type.isFunction(i.validate)?i.validate(e):this.isValidLink(e);if(!n){return}if(BX.type.isFunction(i.handler)){i.handler(t,e)}else{t.preventDefault();this.open(e.url)}},getUrlRule:function(t,e){if(!BX.type.isNotEmptyString(t)){return null}var i=this.defaultOptions.anchorRules;var n=null;for(var s=0;s<i.length;s++){n=i[s];if(!BX.type.isArray(n.condition)){continue}for(var a=0;a<n.condition.length;a++){if(BX.type.isString(n.condition[a])){n.condition[a]=new RegExp(n.condition[a],"i")}var r=t.match(n.condition[a]);if(r&&!this.hasStopParams(t,n.stopParameters)){if(e){e.matches=r}return n}}}return null},isValidLink:function(t){return t.target!=="_blank"&&t.target!=="_top"},hasStopParams:function(t,e){if(!e||!BX.type.isArray(e)||!BX.type.isNotEmptyString(t)){return false}var i=t.indexOf("?");if(i===-1){return false}var n=t.substring(i);for(var s=0;s<e.length;s++){var a=e[s];if(n.match(new RegExp("[?&]"+a+"=","i"))){return true}}return false},bindAnchors:function(t){t=t||{};if(BX.type.isArray(t.rules)){this.defaultOptions.anchorRules=this.defaultOptions.anchorRules.concat(t.rules)}if(!this.anchorHandler){this.anchorHandler=this.handleClick.bind(this);window.document.addEventListener("click",this.anchorHandler,true)}},bindEvents:function(){BX.bind(document,"keydown",BX.proxy(this.onDocumentKeyDown,this));setTimeout(function(){BX.bind(document,"click",BX.proxy(this.onDocumentClick,this))}.bind(this),0);BX.bind(window,"resize",BX.throttle(this.onWindowResize,300,this));BX.bind(window,"scroll",BX.proxy(this.adjustLayout,this));if(this.header){this.header.addEventListener("click",BX.proxy(this.onHeaderClick,this),true)}BX.addCustomEvent("OnMessengerWindowShowPopup",BX.proxy(this.onMessengerOpen,this));if(BX.browser.IsMobile()){BX.bind(document.body,"touchmove",BX.proxy(this.disableScroll,this))}},unbindEvents:function(){BX.unbind(document,"keydown",BX.proxy(this.onDocumentKeyDown,this));BX.unbind(document,"click",BX.proxy(this.onDocumentClick,this));BX.unbind(window,"resize",BX.proxy(this.onWindowResize,this));BX.unbind(window,"scroll",BX.proxy(this.adjustLayout,this));if(this.header){this.header.removeEventListener("click",BX.proxy(this.onHeaderClick,this),true)}BX.removeCustomEvent("OnMessengerWindowShowPopup",BX.proxy(this.onMessengerOpen,this));if(BX.browser.IsMobile()){BX.unbind(document.body,"touchmove",BX.proxy(this.disableScroll,this))}},onMessengerOpen:function(){if(this.isOpen){this.close(true);BX.addCustomEvent("OnMessengerWindowClosePopup",BX.proxy(this.onMessengerClose,this))}},onMessengerClose:function(){this.open(this.iframeSrc);BX.removeCustomEvent("OnMessengerWindowClosePopup",BX.proxy(this.onMessengerClose,this))},onDocumentKeyDown:function(t){if(this.isViewerVisible()){return}if(t.keyCode===27){t.preventDefault();this.close()}},onDocumentClick:function(t){if(this.isViewerVisible()){return}if(this.overlay.contains(t.target)||this.container.contains(t.target)){return}if(this.imBar&&this.imBar.contains(t.target)){return}if(BX.findParent(t.target,{className:"popup-window"})){return}this.close()},onHeaderClick:function(t){if(this.isOpen&&t.target.className.match(/help-/)){this.close(true)}},onWindowResize:function(){this.adjustLayout()},isViewerVisible:function(){return BX.CViewer&&BX.CViewer.objNowInShow&&BX.CViewer.objNowInShow.bVisible},disableScroll:function(t){t.preventDefault()}};
/* End */
;
; /* Start:"a:4:{s:4:"full";s:91:"/bitrix/components/bitrix/tasks.iframe.popup/templates/.default/logic.min.js?15197274767131";s:6:"source";s:72:"/bitrix/components/bitrix/tasks.iframe.popup/templates/.default/logic.js";s:3:"min";s:76:"/bitrix/components/bitrix/tasks.iframe.popup/templates/.default/logic.min.js";s:3:"map";s:76:"/bitrix/components/bitrix/tasks.iframe.popup/templates/.default/logic.map.js";}"*/
BX.namespace("Tasks.Component");BX.Tasks.Component.IframePopup=function(t){this.opts=BX.merge({},t);this.vars={skip:true,callbacks:{},resizeInterval:false,resizeLock:true,lastHeight:false};this.sys={scope:null};this.instances={win:false};this.ctrls={iframe:null,wrap:null,close:null};this.setCallbacks(t.callbacks);this.bindEvents()};BX.mergeEx(BX.Tasks.Component.IframePopup.prototype,{add:function(t){this.edit(0,t)},view:function(t){this.open("view",t)},edit:function(t,e){this.open("edit",t,{urlParams:e})},open:function(t,e,n){e=parseInt(e);if(isNaN(e)||e<0){return}n=n||{};var i=this.getPath(t,e,n.urlParams);if(BX.Bitrix24&&"PageSlider"in BX.Bitrix24){BX.Bitrix24.PageSlider.open(i)}else{this.toggleLoading(true);this.getWindow().show();this.getWindow().setBindElement(this.getWindowCoords());this.getWindow().adjustPosition();this.getIframe().src=i}},close:function(){this.getWindow().close()},bindEvents:function(){BX.bind(window,"resize",BX.throttle(this.onWindowResize,100,this));BX.addCustomEvent(window,"tasksIframeLoad",this.onContentLoaded.bind(this));BX.addCustomEvent(window,"tasksIframeUnload",this.onContentUnLoaded.bind(this))},bindInnerDocumentEvents:function(){var t=this.getContentDocument();if(t){BX.bind(t,"keydown",this.onInnerDocumentKeyDown.bind(this))}},getIframe:function(){if(this.ctrls.iframe===null){this.ctrls.iframe=BX.create("iframe",{attrs:{scrolling:"no",frameBorder:"0"}})}return this.ctrls.iframe},getWindow:function(){if(this.instances.win===false){this.instances.win=new BX.PopupWindow("tasks-iframe-popup",{top:0,left:0},{autoHide:false,closeByEsc:true,content:this.getIframeContainer(),overlay:true,lightShadow:false,closeIcon:true,contentNoPaddings:true,draggable:false,titleBar:true,events:{onPopupClose:BX.delegate(this.onPopupClose,this),onPopupShow:BX.delegate(this.onPopupOpen,this)}});this.ctrls.close=BX.create("div",{props:{className:"hidden"},attrs:{id:"tasks-popup-close",title:BX.message("TASKS_TIP_COMPONENT_TEMPLATE_CLOSE_WINDOW")},events:{click:BX.delegate(this.onCloseClicked,this)},children:[BX.create("span")]});BX.insertAfter(this.ctrls.close,BX("popup-window-overlay-tasks-iframe-popup"))}return this.instances.win},setTitle:function(t,e){var n="";if(t!=false){t=t=="view"?"VIEW":"EDIT";e=parseInt(e);if(isNaN(e)||e<=0){e=0}if(t=="EDIT"&&e==0){t="NEW"}n=BX.message("TASKS_TIP_COMPONENT_TEMPLATE_"+t+"_TASK_TITLE");if(e>0){n=n.replace("#TASK_ID#",e)}}this.getWindow().setTitleBar(n)},getPath:function(t,e,n){t=t=="view"?"view":"edit";e=parseInt(e);var i=this.opts.pathToTasks.replace("#task_id#",e);i=i+(i.indexOf("?")==-1?"?":"&")+"IFRAME=Y";if(BX.type.isPlainObject(n)){for(var s in n){i+="&"+s+"="+encodeURIComponent(n[s])}}i=i.replace("#action#",t);return i},getWindowCoords:function(){var t=BX.pos(this.getIframeContainer()).width;var e=BX.GetWindowSize().innerWidth;var n=BX.GetWindowScrollPos().scrollTop;return{left:Math.floor((e-t)/2),top:30+n}},getContentDocument:function(){var t=this.getIframe();var e=null;if(t.contentDocument){e=t.contentDocument}if(t.contentWindow){e=t.contentWindow.document}return e&&e.body?e:null},getIframeContainer:function(){if(this.ctrls.wrap===null){this.ctrls.wrap=this.ctrls.wrap=BX.create("div",{props:{className:"tasks-iframe-wrap loading fixedHeight"},attrs:{id:"tasks-iframe-wrap"},children:[this.getIframe()]})}return this.ctrls.wrap},getContentContainer:function(){var t=this.getContentDocument();if(t){return t.getElementById("tasks-content-outer")}return null},onCloseClicked:function(){this.getWindow().close()},onTaskGlobalEvent:function(t,e){if(BX.type.isNotEmptyString(t)){var n=t.toString().toUpperCase();e=e||{};e.task=e.task||{};e.options=e.options||{};var i=[];var s=parseInt(e.task.ID);if(n=="DELETE"&&!isNaN(s)&&s){i.push(e.task.ID)}else if(n=="ADD"||n=="UPDATE"){if(e.taskUgly){i.push(e.taskUgly)}else{return}}if(!e.options.STAY_AT_PAGE){this.close()}if(typeof this.vars.callbacks[n]!="undefined"&&this.vars.callbacks[n]!==false){var o=this.vars.callbacks[n];if(BX.type.isString(o)){o=BX.Tasks.deReference(o,window)}if(BX.type.isFunction(o)){o.apply(window,i)}}}},onContentLoaded:function(){var t=this.getContentDocument();if(t){var e=this.parseUrl(t.location.pathname);if(e){this.setTitle(e.action,e.taskId)}}this.toggleLoading(false);this.startMonitorContent();this.bindInnerDocumentEvents()},onContentUnLoaded:function(){this.setTitle(false);this.stopMonitorContent()},onPopupOpen:function(){BX.toggleClass(this.ctrls.close,"hidden");this.toggleLoading(true)},onPopupClose:function(){BX.toggleClass(this.ctrls.close,"hidden");this.lockHeight();this.stopMonitorContent();this.toggleLoading(true);this.vars.lastHeight=false;this.getIframe().src="about:blank"},onWindowResize:function(){if(this.getWindow().isShown()){this.getWindow().setBindElement(this.getWindowCoords())}},onContentResize:function(){if(this.getWindow().isShown()&&!this.vars.resizeLock){var t=this.getContentDocument();if(t){var e=this.getContentContainer();if(e){var n=e.offsetHeight;if(n!=this.vars.lastHeight){this.getIframeContainer().style.height=n+"px";this.vars.lastHeight=n;this.unLockHeight()}this.getWindow().popupContainer.style.marginBottom="40px";this.getWindow().resizeOverlay()}}}},onInnerDocumentKeyDown:function(t){if(BX.Tasks.Util.isEsc(t)){this.close()}},lockHeight:function(){this.toggleFixedHeight(true)},unLockHeight:function(){this.toggleFixedHeight(false)},toggleFixedHeight:function(t){BX[t?"addClass":"removeClass"](this.getIframeContainer(),"fixedHeight")},toggleLoading:function(t){BX[t?"addClass":"removeClass"](this.getIframeContainer(),"loading")},stopMonitorContent:function(){this.vars.resizeLock=true},startMonitorContent:function(){this.vars.resizeLock=false;if(this.vars.resizeInterval===false){this.vars.resizeInterval=setInterval(BX.proxy(this.onContentResize,this),300)}},setCallbacks:function(t){if(BX.type.isPlainObject(t)){BX.Tasks.each(t,function(t,e){if(t=="#SHOW_ADDED_TASK_DETAIL#"){return}if(t!==false&&(BX.type.isFunction(t)||BX.type.isNotEmptyString(t))){this.vars.callbacks[e]=t}}.bind(this))}},showCreateForm:function(){this.add()},parseUrl:function(t){var e=this.opts.pathToTasks;if(e){e=e.toLowerCase().replace("#action#","(view|edit){1}").replace("#task_id#","(\\d+)");var n=t.match(new RegExp(e));if(n&&BX.type.isArray(n)){var i=n[1]||false;var s=n[2]||false;if(i&&s){return{action:i,taskId:parseInt(s)}}}}return null},onTaskAdded:function(t,e,n,i,s){BX.onCustomEvent(this,"onTaskAdded",[t,e,n,i,s])},onTaskChanged:function(t,e,n,i,s){BX.onCustomEvent(this,"onTaskChanged",[t,e,n,i,s])},onTaskDeleted:function(t){BX.onCustomEvent(this,"onTaskDeleted",[t])}});BX.Tasks.Component.IframePopup.create=function(t){if(window.top!=window){return}if(typeof BX.Tasks.Singletons=="undefined"){BX.Tasks.Singletons={}}if(typeof BX.Tasks.Singletons.iframePopup=="undefined"){BX.Tasks.Singletons.iframePopup=new BX.Tasks.Component.IframePopup(t);window.taskIFramePopup=BX.Tasks.Singletons.iframePopup;window.BX.TasksIFrameInst=BX.Tasks.Singletons.iframePopup}else{BX.Tasks.Singletons.iframePopup.setCallbacks(t.callbacks)}return BX.Tasks.Singletons.iframePopup};
/* End */
;
; /* Start:"a:4:{s:4:"full";s:67:"/bitrix/components/bitrix/search.title/script.min.js?15197274746110";s:6:"source";s:48:"/bitrix/components/bitrix/search.title/script.js";s:3:"min";s:52:"/bitrix/components/bitrix/search.title/script.min.js";s:3:"map";s:52:"/bitrix/components/bitrix/search.title/script.map.js";}"*/
function JCTitleSearch(t){var e=this;this.arParams={AJAX_PAGE:t.AJAX_PAGE,CONTAINER_ID:t.CONTAINER_ID,INPUT_ID:t.INPUT_ID,MIN_QUERY_LEN:parseInt(t.MIN_QUERY_LEN)};if(t.WAIT_IMAGE)this.arParams.WAIT_IMAGE=t.WAIT_IMAGE;if(t.MIN_QUERY_LEN<=0)t.MIN_QUERY_LEN=1;this.cache=[];this.cache_key=null;this.startText="";this.running=false;this.currentRow=-1;this.RESULT=null;this.CONTAINER=null;this.INPUT=null;this.WAIT=null;this.ShowResult=function(t){if(BX.type.isString(t)){e.RESULT.innerHTML=t}e.RESULT.style.display=e.RESULT.innerHTML!==""?"block":"none";var s=e.adjustResultNode();var i;var r;var n=BX.findChild(e.RESULT,{tag:"table","class":"title-search-result"},true);if(n){r=BX.findChild(n,{tag:"th"},true)}if(r){var a=BX.pos(n);a.width=a.right-a.left;var l=BX.pos(r);l.width=l.right-l.left;r.style.width=l.width+"px";e.RESULT.style.width=s.width+l.width+"px";e.RESULT.style.left=s.left-l.width-1+"px";if(a.width-l.width>s.width)e.RESULT.style.width=s.width+l.width-1+"px";a=BX.pos(n);i=BX.pos(e.RESULT);if(i.right>a.right){e.RESULT.style.width=a.right-a.left+"px"}}var o;if(n)o=BX.findChild(e.RESULT,{"class":"title-search-fader"},true);if(o&&r){i=BX.pos(e.RESULT);o.style.left=i.right-i.left-18+"px";o.style.width=18+"px";o.style.top=0+"px";o.style.height=i.bottom-i.top+"px";o.style.display="block"}};this.onKeyPress=function(t){var s=BX.findChild(e.RESULT,{tag:"table","class":"title-search-result"},true);if(!s)return false;var i;var r=s.rows.length;switch(t){case 27:e.RESULT.style.display="none";e.currentRow=-1;e.UnSelectAll();return true;case 40:if(e.RESULT.style.display=="none")e.RESULT.style.display="block";var n=-1;for(i=0;i<r;i++){if(!BX.findChild(s.rows[i],{"class":"title-search-separator"},true)){if(n==-1)n=i;if(e.currentRow<i){e.currentRow=i;break}else if(s.rows[i].className=="title-search-selected"){s.rows[i].className=""}}}if(i==r&&e.currentRow!=i)e.currentRow=n;s.rows[e.currentRow].className="title-search-selected";return true;case 38:if(e.RESULT.style.display=="none")e.RESULT.style.display="block";var a=-1;for(i=r-1;i>=0;i--){if(!BX.findChild(s.rows[i],{"class":"title-search-separator"},true)){if(a==-1)a=i;if(e.currentRow>i){e.currentRow=i;break}else if(s.rows[i].className=="title-search-selected"){s.rows[i].className=""}}}if(i<0&&e.currentRow!=i)e.currentRow=a;s.rows[e.currentRow].className="title-search-selected";return true;case 13:if(e.RESULT.style.display=="block"){for(i=0;i<r;i++){if(e.currentRow==i){if(!BX.findChild(s.rows[i],{"class":"title-search-separator"},true)){var l=BX.findChild(s.rows[i],{tag:"a"},true);if(l){window.location=l.href;return true}}}}}return false}return false};this.onTimeout=function(){e.onChange(function(){setTimeout(e.onTimeout,500)})};this.onChange=function(t){if(e.running)return;e.running=true;if(e.INPUT.value!=e.oldValue&&e.INPUT.value!=e.startText){e.oldValue=e.INPUT.value;if(e.INPUT.value.length>=e.arParams.MIN_QUERY_LEN){e.cache_key=e.arParams.INPUT_ID+"|"+e.INPUT.value;if(e.cache[e.cache_key]==null){if(e.WAIT){var s=BX.pos(e.INPUT);var i=s.bottom-s.top-2;e.WAIT.style.top=s.top+1+"px";e.WAIT.style.height=i+"px";e.WAIT.style.width=i+"px";e.WAIT.style.left=s.right-i+2+"px";e.WAIT.style.display="block"}BX.ajax.post(e.arParams.AJAX_PAGE,{ajax_call:"y",INPUT_ID:e.arParams.INPUT_ID,q:e.INPUT.value,l:e.arParams.MIN_QUERY_LEN},function(s){e.cache[e.cache_key]=s;e.ShowResult(s);e.currentRow=-1;e.EnableMouseEvents();if(e.WAIT)e.WAIT.style.display="none";if(!!t)t();e.running=false});return}else{e.ShowResult(e.cache[e.cache_key]);e.currentRow=-1;e.EnableMouseEvents()}}else{e.RESULT.style.display="none";e.currentRow=-1;e.UnSelectAll()}}if(!!t)t();e.running=false};this.UnSelectAll=function(){var t=BX.findChild(e.RESULT,{tag:"table","class":"title-search-result"},true);if(t){var s=t.rows.length;for(var i=0;i<s;i++)t.rows[i].className=""}};this.EnableMouseEvents=function(){var t=BX.findChild(e.RESULT,{tag:"table","class":"title-search-result"},true);if(t){var s=t.rows.length;for(var i=0;i<s;i++)if(!BX.findChild(t.rows[i],{"class":"title-search-separator"},true)){t.rows[i].id="row_"+i;t.rows[i].onmouseover=function(t){if(e.currentRow!=this.id.substr(4)){e.UnSelectAll();this.className="title-search-selected";e.currentRow=this.id.substr(4)}};t.rows[i].onmouseout=function(t){this.className="";e.currentRow=-1}}}};this.onFocusLost=function(t){setTimeout(function(){e.RESULT.style.display="none"},250)};this.onFocusGain=function(){if(e.RESULT.innerHTML.length)e.ShowResult()};this.onKeyDown=function(t){if(!t)t=window.event;if(e.RESULT.style.display=="block"){if(e.onKeyPress(t.keyCode))return BX.PreventDefault(t)}};this.adjustResultNode=function(){var t;var s=BX.findParent(e.CONTAINER,BX.is_fixed);if(!!s){e.RESULT.style.position="fixed";e.RESULT.style.zIndex=BX.style(s,"z-index")+2;t=BX.pos(e.CONTAINER,true)}else{e.RESULT.style.position="absolute";t=BX.pos(e.CONTAINER)}t.width=t.right-t.left;e.RESULT.style.top=t.bottom+2+"px";e.RESULT.style.left=t.left+"px";e.RESULT.style.width=t.width+"px";return t};this._onContainerLayoutChange=function(){if(e.RESULT.style.display!=="none"&&e.RESULT.innerHTML!==""){e.adjustResultNode()}};this.Init=function(){this.CONTAINER=document.getElementById(this.arParams.CONTAINER_ID);BX.addCustomEvent(this.CONTAINER,"OnNodeLayoutChange",this._onContainerLayoutChange);this.RESULT=document.body.appendChild(document.createElement("DIV"));this.RESULT.className="title-search-result";this.INPUT=document.getElementById(this.arParams.INPUT_ID);this.startText=this.oldValue=this.INPUT.value;BX.bind(this.INPUT,"focus",function(){e.onFocusGain()});BX.bind(this.INPUT,"blur",function(){e.onFocusLost()});this.INPUT.onkeydown=this.onKeyDown;if(this.arParams.WAIT_IMAGE){this.WAIT=document.body.appendChild(document.createElement("DIV"));this.WAIT.style.backgroundImage="url('"+this.arParams.WAIT_IMAGE+"')";if(!BX.browser.IsIE())this.WAIT.style.backgroundRepeat="none";this.WAIT.style.display="none";this.WAIT.style.position="absolute";this.WAIT.style.zIndex="1100"}BX.bind(this.INPUT,"bxchange",function(){e.onChange()})};BX.ready(function(){e.Init(t)})}
/* End */
;
; /* Start:"a:4:{s:4:"full";s:98:"/bitrix/templates/bitrix24/components/bitrix/system.auth.form/.default/script.min.js?1519727464323";s:6:"source";s:80:"/bitrix/templates/bitrix24/components/bitrix/system.auth.form/.default/script.js";s:3:"min";s:84:"/bitrix/templates/bitrix24/components/bitrix/system.auth.form/.default/script.min.js";s:3:"map";s:84:"/bitrix/templates/bitrix24/components/bitrix/system.auth.form/.default/script.map.js";}"*/
BX.namespace("BX.Bitrix24.SystemAuthForm");BX.Bitrix24.SystemAuthForm={licenseHandler:function(t){if(typeof t!=="object")return;var o=t.COUNTER_URL||"",e=t.LICENSE_PATH||"",n=t.HOST||"";BX.ajax.post(o,{action:"upgradeButton",host:n},BX.proxy(function(){document.location.href=e},this))}};
/* End */
;
; /* Start:"a:4:{s:4:"full";s:90:"/bitrix/templates/bitrix24/components/bitrix/menu/left_vertical/map.min.js?151972746411235";s:6:"source";s:70:"/bitrix/templates/bitrix24/components/bitrix/menu/left_vertical/map.js";s:3:"min";s:74:"/bitrix/templates/bitrix24/components/bitrix/menu/left_vertical/map.min.js";s:3:"map";s:74:"/bitrix/templates/bitrix24/components/bitrix/menu/left_vertical/map.map.js";}"*/
BX.namespace("BX.Bitrix24");BX.Bitrix24.SlidingPanel=function(t){this.containerClassName=this.containerClassName||"sliding-panel-window";this.container=BX.create("div",{props:{className:this.containerClassName}});this.overlayClassName=this.overlayClassName||"sliding-panel-overlay";this.overlay=BX.create("div",{props:{className:this.overlayClassName}});this.isOpen=false;this.header=BX("header");this.imBar=BX("bx-im-bar");this.panel=BX("panel");this.creatorConfirmedPanel=BX("creatorconfirmed");this.animation=null;this.startParams=this.startParams||{};this.endParams=this.endParams||{};this.currentParams=null;BX.bind(this.container,"click",this.onContainerClick.bind(this));BX.addCustomEvent("onTopPanelCollapse",this.onTopPanelCollapse.bind(this))};BX.Bitrix24.SlidingPanel.prototype={animateStep:function(t){},setContent:function(){},open:function(){if(this.isOpen){return}this.isOpen=true;BX.bind(document,"keyup",BX.proxy(this.onDocumentKeyUp,this));BX.bind(document,"click",BX.proxy(this.onDocumentClick,this));this.header.addEventListener("click",BX.proxy(this.onHeaderClick,this),true);if(!this.container.parentNode){this.setContent();document.body.appendChild(this.container)}if(!this.overlay.parentNode){document.body.appendChild(this.overlay)}var t=window.innerWidth-document.documentElement.clientWidth;document.body.style.paddingRight=t+"px";if(this.imBar){this.imBar.style.right=t+"px"}if(this.panel){this.panel.style.zIndex=3001}if(this.creatorConfirmedPanel){this.creatorConfirmedPanel.style.zIndex=3e3}document.body.style.overflow="hidden";this.header.style.zIndex=3e3;this.adjustPosition();if(this.animation){this.animation.stop()}this.animation=new BX.easing({duration:300,start:this.currentParams?this.currentParams:this.startParams,finish:this.endParams,transition:BX.easing.transitions.linear,step:BX.proxy(function(t){this.currentParams=t;this.animateStep(t)},this),complete:BX.proxy(function(){this.onTrasitionEnd()},this)});this.animation.animate()},close:function(t){if(!this.isOpen){if(this.animation){this.animation.stop(true)}return}this.isOpen=false;BX.unbind(document,"keyup",BX.proxy(this.onDocumentKeyUp,this));BX.unbind(document,"click",BX.proxy(this.onDocumentClick,this));this.header.removeEventListener("click",BX.proxy(this.onHeaderClick,this),true);this.container.classList.remove(this.containerClassName+"-open");if(this.animation){this.animation.stop()}if(t===true){this.currentParams=this.startParams;this.onTrasitionEnd()}else{this.animation=new BX.easing({duration:300,start:this.currentParams,finish:this.startParams,transition:BX.easing.transitions.linear,step:BX.proxy(function(t){this.currentParams=t;this.animateStep(t)},this),complete:BX.proxy(function(){this.onTrasitionEnd()},this)});this.animation.animate()}},adjustPosition:function(){var t=BX.pos(this.header);var i=window.pageYOffset||document.documentElement.scrollTop;if(i>0){this.overlay.style.bottom=-i+"px";this.container.style.bottom=-i+"px"}var e=i>t.bottom?i:t.bottom;this.overlay.style.top=e+"px";this.container.style.top=e+"px"},onTrasitionEnd:function(){this.animation=null;if(this.isOpen){this.currentParams=this.endParams;this.container.classList.add(this.containerClassName+"-open")}else{this.currentParams=this.startParams;if(this.overlay.parentNode){this.overlay.parentNode.removeChild(this.overlay)}if(this.imBar){this.imBar.style.right=""}if(this.panel){this.panel.style.cssText=""}if(this.creatorConfirmedPanel){this.creatorConfirmedPanel.style.cssText=""}document.body.style.cssText="";this.container.style.cssText="";this.header.style.cssText="";this.overlay.style.cssText=""}},onContainerClick:function(t){t.stopPropagation()},onDocumentKeyUp:function(t){if(t.keyCode===27){this.close()}},onDocumentClick:function(t){if(BX.isParentForNode(this.container,t.target)){return}this.close()},onHeaderClick:function(t){if(this.isOpen&&t.target.className.match(/help-/)){this.close(true)}},onTopPanelCollapse:function(){if(this.isOpen){this.adjustPosition()}}};BX.Bitrix24.GroupPanel=function(t){this.containerClassName="group-panel-window";this.overlayClassName="group-panel-overlay";this.startParams={translateX:-100,opacity:0};this.endParams={translateX:0,opacity:100};BX.Bitrix24.SlidingPanel.apply(this,arguments);t=t||{};this.ajaxPath=BX.type.isNotEmptyString(t.ajaxPath)?t.ajaxPath:null;this.siteId=BX.type.isNotEmptyString(t.siteId)?t.siteId:BX.message("SITE_ID");this.menu=BX("menu-all-groups-link");this.menuOverlay=document.createElement("div");this.menuOverlay.className="group-panel-menu-overlay";this.leftMenu=BX("bx-left-menu");this.content=BX("group-panel-content");this.items=BX("group-panel-items");this.counter=BX("group-panel-header-filter-counter");var i=this.items.getElementsByClassName("group-panel-item-intranet");var e=this.items.getElementsByClassName("group-panel-item-extranet");if(i.length<=20&&e.length<=20){BX.addClass(this.container,"group-panel-window-one-column")}this.closeLink=BX("group-panel-close-link");this.filters=[].slice.call(this.content.getElementsByClassName("group-panel-header-filter"));for(var n=0;n<this.filters.length;n++){var s=this.filters[n];BX.bind(s,"click",BX.proxy(this.onFilterClick,this))}BX.bind(this.items,"click",this.onItemsClick.bind(this));BX.bind(this.closeLink,"click",this.close.bind(this));BX.bind(this.menu,"click",this.onMenuClick.bind(this));var a=function(){this.close(true)}.bind(this);BX.addCustomEvent("BX.Bitrix24.Map:onBeforeOpen",a);BX.addCustomEvent("BX.Bitrix24.LeftMenuClass:onDragStart",a);BX.addCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuToggle",a)};BX.Bitrix24.GroupPanel.prototype=Object.create(BX.Bitrix24.SlidingPanel.prototype);BX.Bitrix24.GroupPanel.prototype.constructor=BX.Bitrix24.GroupPanel;BX.Bitrix24.GroupPanel.prototype.super=BX.Bitrix24.SlidingPanel.prototype;BX.Bitrix24.GroupPanel.prototype.setContent=function(){this.container.appendChild(this.content)};BX.Bitrix24.GroupPanel.prototype.animateStep=function(t){this.container.style.transform="translateX("+t.translateX+"%)";this.overlay.style.opacity=t.opacity/100};BX.Bitrix24.GroupPanel.prototype.open=function(){BX.onCustomEvent("BX.Bitrix24.GroupPanel:onBeforeOpen",[this]);if(window.pulse_loading&&window.pulse_loading.open){window.pulse_loading.close(true)}this.leftMenu.style.zIndex=3e3;this.container.style.display="block";BX.addClass(this.menu.parentNode,"menu-item-block-hover");this.menu.innerHTML=BX.message("menu_hide");var t=BX.pos(this.leftMenu);this.menuOverlay.style.left=t.left+"px";this.menuOverlay.style.top=t.bottom+"px";this.menuOverlay.style.width=t.width+"px";this.menuOverlay.style.backgroundColor=BX.style(this.leftMenu,"backgroundColor");this.menuOverlay.style.height=document.documentElement.scrollHeight-t.bottom+"px";document.body.appendChild(this.menuOverlay);this.super.open.apply(this,arguments)};BX.Bitrix24.GroupPanel.prototype.close=function(){this.menu.innerHTML=BX.message("menu_show");this.super.close.apply(this,arguments)};BX.Bitrix24.GroupPanel.prototype.onTrasitionEnd=function(){this.super.onTrasitionEnd.apply(this,arguments);if(!this.isOpen){this.leftMenu.style.cssText="";BX.removeClass(this.menu.parentNode,"menu-item-block-hover");this.menuOverlay.parentNode.removeChild(this.menuOverlay)}};BX.Bitrix24.GroupPanel.prototype.onMenuClick=function(t){if(this.isOpen){this.close()}else{this.open()}t.stopPropagation()};BX.Bitrix24.GroupPanel.prototype.onFilterClick=function(t){var i=BX.type.isDomNode(BX.proxy_context)?BX.proxy_context:null;var e=this.content.dataset.filter?this.content.dataset.filter:"all";var n=i.dataset.filter?i.dataset.filter:"all";if(e!==n){this.content.dataset.filter=n;this.saveFilter(n);new BX.easing({duration:50,start:{opacity:1},finish:{opacity:0},transition:BX.easing.transitions.linear,step:BX.delegate(function(t){this.items.style.opacity=t.opacity/100},this),complete:BX.delegate(function(){BX.removeClass(this.content,"group-panel-content-"+e);BX.addClass(this.content,"group-panel-content-"+n);new BX.easing({duration:50,start:{opacity:0},finish:{opacity:1},transition:BX.easing.transitions.linear,step:BX.delegate(function(t){this.items.style.opacity=t.opacity/100},this),complete:BX.delegate(function(){this.items.style.cssText=""},this)}).animate()},this)}).animate()}t.stopPropagation()};BX.Bitrix24.GroupPanel.prototype.onItemsClick=function(t){if(!BX.hasClass(t.target,"group-panel-item-star")){return}var i=t.target;var e=i.parentNode;var n=e.dataset.id;var s=BX.hasClass(e,"group-panel-item-favorite")?"remove_from_favorites":"add_to_favorites";BX.toggleClass(e,"group-panel-item-favorite");this.animateStart(i);this.animateCounter(s==="add_to_favorites");BX.ajax({method:"POST",dataType:"json",url:this.ajaxPath,data:{sessid:BX.bitrix_sessid(),site_id:this.siteId,action:s,groupId:n}});t.preventDefault()};BX.Bitrix24.GroupPanel.prototype.animateStart=function(t){var i=t.cloneNode();i.style.marginLeft="-"+t.offsetWidth+"px";t.parentNode.appendChild(i);new BX.easing({duration:200,start:{opacity:100,scale:100},finish:{opacity:0,scale:300},transition:BX.easing.transitions.linear,step:function(t){i.style.transform="scale("+t.scale/100+")";i.style.opacity=t.opacity/100},complete:function(){i.parentNode.removeChild(i)}}).animate()};BX.Bitrix24.GroupPanel.prototype.animateCounter=function(t){this.counter.innerHTML=t===false?"-1":"+1";new BX.easing({duration:400,start:{opacity:100,top:0},finish:{opacity:0,top:-20},transition:BX.easing.transitions.linear,step:function(t){this.counter.style.top=t.top+"px";this.counter.style.opacity=t.opacity/100}.bind(this),complete:function(){this.counter.style.cssText=""}.bind(this)}).animate()};BX.Bitrix24.GroupPanel.prototype.saveFilter=function(t){if(!this.ajaxPath||!BX.type.isNotEmptyString(t)){return}BX.ajax({method:"POST",dataType:"json",url:this.ajaxPath,data:{sessid:BX.bitrix_sessid(),site_id:this.siteId,action:"set_group_filter",filter:t}})};BX.Bitrix24.Map=function(t){this.containerClassName="sitemap-window";this.overlayClassName="sitemap-window-overlay";this.startParams={translateY:-100,opacity:0};this.endParams={translateY:0,opacity:100};BX.Bitrix24.SlidingPanel.apply(this,arguments);this.menu=BX("sitemap-menu");this.content=BX("sitemap-content");this.closeLink=BX("sitemap-close-link");BX.bind(this.menu,"click",this.onMenuClick.bind(this));BX.bind(this.closeLink,"click",this.close.bind(this))};BX.Bitrix24.Map.prototype=Object.create(BX.Bitrix24.SlidingPanel.prototype);BX.Bitrix24.Map.prototype.constructor=BX.Bitrix24.Map;BX.Bitrix24.Map.prototype.super=BX.Bitrix24.SlidingPanel.prototype;BX.Bitrix24.Map.prototype.setContent=function(){this.container.appendChild(this.content)};BX.Bitrix24.Map.prototype.animateStep=function(t){this.container.style.transform="translateY("+t.translateY+"%)";this.overlay.style.opacity=t.opacity/100};BX.Bitrix24.Map.prototype.open=function(){BX.onCustomEvent("BX.Bitrix24.Map:onBeforeOpen",[this]);this.menu.classList.add("sitemap-menu-open");this.super.open.apply(this,arguments)};BX.Bitrix24.Map.prototype.close=function(){this.menu.classList.remove("sitemap-menu-open");this.super.close.apply(this,arguments)};BX.Bitrix24.Map.prototype.onMenuClick=function(t){if(this.isOpen){this.close()}else{this.open()}t.stopPropagation()};
/* End */
;
; /* Start:"a:4:{s:4:"full";s:89:"/bitrix/templates/bitrix24/components/bitrix/menu/left_vertical/script.js?151972746564997";s:6:"source";s:73:"/bitrix/templates/bitrix24/components/bitrix/menu/left_vertical/script.js";s:3:"min";s:0:"";s:3:"map";s:0:"";}"*/
BX.namespace("BX.Bitrix24");

BX.Bitrix24.LeftMenuClass = (function()
{
	var LeftMenuClass = function(params)
	{
		params = typeof params === "object" ? params : {};

		this.ajaxPath = params.ajaxPath || null;
		this.isAdmin =  params.isAdmin === "Y";
		this.hiddenCounters = params.hiddenCounters || {};
		this.allCounters = params.allCounters || {};
		this.isBitrix24 = params.isBitrix24 === "Y";
		this.siteId = params.siteId || null;
		this.siteDir = params.siteDir || null;
		this.isExtranet = params.isExtranet == "Y";
		this.isCompositeMode = params.isCompositeMode === true;
		this.isCollapsedMode = params.isCollapsedMode === true;
		this.activeItemsId = [];
		this.isCurrentPageInLeftMenu = false;
		this.menuSelectedNode = null;
		this.showPresetPopup = params.showPresetPopup === "Y";
		this.isCurrentPageStandard = false;
		this.topMenuSelectedNode = null;
		this.topItemSelectedObj = null;
		this.isPublicConverted = params.isPublicConverted == "Y";

		if (!this.init())
		{
			BX.ready(function() {
				this.init();
			}.bind(this));
		}
	};

	LeftMenuClass.prototype.init = function()
	{
		this.menuContainer = BX("bx-left-menu");
		this.menuResizer = BX("left-menu-resizer");
		this.menuResizerButton = BX("left-menu-resizer-button");
		this.menuMoreButton = BX("left-menu-more-btn");
		this.menuItemsBlock = BX("left-menu-list");
		this.menuSettingsButton = BX("left-menu-settings");
		this.leftColumnBottom = null;

		if (!this.menuContainer)
		{
			return false;
		}

		this.highlight(document.location.pathname + document.location.search);

		//drag&drop
		jsDD.Enable();
		if (BX.type.isDomNode(this.menuItemsBlock))
		{
			var items = this.menuItemsBlock.getElementsByClassName("menu-item-block");
			for (var i=0; i<items.length; i++)
			{
				items[i].onbxdragstart = BX.proxy(this.menuItemDragStart, this);
				items[i].onbxdrag = BX.proxy(this.menuItemDragMove, this);
				items[i].onbxdragstop = BX.proxy(this.menuItemDragStop, this);
				items[i].onbxdraghover = BX.proxy(this.menuItemDragHover, this);
				jsDD.registerDest(items[i], 100);
				jsDD.registerObject(items[i]);
			}
		}

		BX.bind(this.menuContainer, "dblclick", BX.proxy(this.onMenuDoubleClick, this));
		BX.ready(function() {
			this.leftColumnBottom = BX("layout-left-column-bottom");
			BX.bind(this.leftColumnBottom, "dblclick", BX.proxy(this.onMenuDoubleClick, this));
		}.bind(this));

		BX.bind(this.menuResizer, "mouseover", BX.proxy(this.onResizerMouseOver, this));
		BX.bind(this.menuResizer, "mouseout", BX.proxy(this.onResizerMouseOut, this));
		BX.bind(this.menuResizer, "click", BX.proxy(this.onResizerClick, this));

		BX.bind(BX("left-menu-hidden-separator"), "click", BX.proxy(this.showHideMoreItems, this));
		BX.bind(this.menuMoreButton, "click", BX.proxy(this.showHideMoreItems, this));
		BX.bind(this.menuSettingsButton, "click", BX.proxy(this.showSettingsPopup, this));

		BX.addCustomEvent("BX.Main.InterfaceButtons:onFirstItemChange", BX.proxy(function (firstPageLink, firstNode) {
			this.onTopMenuFirstItemChange(firstPageLink, firstNode);
		}, this));

		BX.addCustomEvent("BX.Main.InterfaceButtons:onHideLastVisibleItem", BX.proxy(function (bindElement) {
			this.showMessage(bindElement, BX.message("MENU_TOP_ITEM_LAST_HIDDEN"));
		}, this));

		BX.addCustomEvent("BX.Main.InterfaceButtons:onBeforeCreateEditMenu", function(contextMenu, dataItem, topMenu) {
			var isItemInLeftMenu = BX.type.isDomNode(BX("bx_left_menu_" + dataItem.DATA_ID));
			contextMenu.addMenuItem({
				text: BX.message(isItemInLeftMenu ? "MENU_DELETE_FROM_LEFT_MENU" : "MENU_ADD_TO_LEFT_MENU"),
				onclick: function(event, item) {
					var itemInfo = {
						id: dataItem.DATA_ID,
						text: BX.util.htmlspecialcharsback(dataItem.TEXT),
						subLink: dataItem.SUB_LINK,
						counterId: dataItem.COUNTER_ID,
						counterValue: dataItem.COUNTER
					};

					var link = document.createElement("a");
					link.href = dataItem.URL;
					itemInfo.link = BX.util.htmlspecialcharsback(link.pathname + link.search);

					if (isItemInLeftMenu)
					{
						this.deleteStandardItem(dataItem.DATA_ID);
					}
					else
					{
						var startX = "",
							startY = "";

						if (BX.type.isDomNode(dataItem.NODE))
						{
							var menuNodeCoord = dataItem.NODE.getBoundingClientRect();
							startX = menuNodeCoord.left;
							startY = menuNodeCoord.top;
						}
						this.addStandardItem(itemInfo, startX, startY);
					}

					BX.PopupMenu.destroy(contextMenu.id);

				}.bind(this)
			});
		}.bind(this));

		BX.addCustomEvent("BX.Main.InterfaceButtons:onBeforeResetMenu", function(promises) {
			promises.push(function() {
				var p = new BX.Promise();

				BX.ajax({
					method: "POST",
					dataType: "json",
					url: this.ajaxPath,
					data: {
						sessid : BX.bitrix_sessid(),
						site_id : this.siteId,
						action: "clear_cache"
					},
					onsuccess: function()
					{
						p.fulfill();
					},
					onfailure: function(error)
					{
						p.reject("Error: " + error);
					}
				});

				return p;
			}.bind(this));
		}.bind(this));

		if (this.showPresetPopup)
		{
			this.showPresetPopupFunction("global");
		}

		this.menuSelectedNode = BX.findChild(this.menuContainer, {className: "menu-item-active"}, true, false);
		if (BX.type.isDomNode(this.menuSelectedNode))
		{
			var leftMenuSelectedUrl = this.menuSelectedNode.getAttribute("data-link");
		}

		var currentPath = document.location.pathname;
		var currentFullPath = document.location.pathname + document.location.search;

		if (leftMenuSelectedUrl == currentPath || leftMenuSelectedUrl == currentFullPath)
		{
			this.isCurrentPageInLeftMenu = true;
		}

		return true;
	};

	LeftMenuClass.prototype.isEditMode = function()
	{
		return BX.hasClass(this.menuContainer, 'menu-favorites-editable');
	};

	LeftMenuClass.prototype.applyEditMode = function()
	{
		var isEditMode = this.isEditMode();

		if (BX.type.isDomNode(this.menuContainer))
		{
			if (isEditMode)
				BX.removeClass(this.menuContainer, "menu-favorites-editable");
			else
				BX.addClass(this.menuContainer, "menu-favorites-editable");
		}

		if (!isEditMode)
		{
			BX.addClass(this.menuSettingsButton, 'menu-favorites-btn-active');

			var allActiveItems = BX.findChildren(this.menuContainer, {className: "menu-item-active"}, true);
			for (var obj in allActiveItems)
			{
				if (allActiveItems.hasOwnProperty(obj))
				{
					BX.removeClass(allActiveItems[obj], 'menu-item-active');
					this.activeItemsId.push(allActiveItems[obj].id);
				}
			}
		}
		else
		{
			BX.removeClass(this.menuSettingsButton, 'menu-favorites-btn-active');
			for (var key in this.activeItemsId)
			{
				BX.addClass(BX(this.activeItemsId[key]), 'menu-item-active');
			}
			this.activeItemsId = [];
		}
	};

	LeftMenuClass.prototype.areMoreItemsShowed = function()
	{
		return BX.hasClass(BX('left-menu-hidden-items-block'), 'menu-item-favorites-more-open') ? true : false;
	};

	LeftMenuClass.prototype.animateShowingHiddenItems = function()
	{
		var hiddenBlock = BX("left-menu-hidden-items-block");

		if (!BX.hasClass(hiddenBlock, "menu-item-favorites-more-open"))
		{
			hiddenBlock.style.height = "0px";
			hiddenBlock.style.opacity = 0;
			animation(true, hiddenBlock, hiddenBlock.scrollHeight);
		}
		else
		{
			animation(false, hiddenBlock, hiddenBlock.offsetHeight);
		}

		function animation(opening, hiddenBlock, maxHeight)
		{
			hiddenBlock.style.overflow = "hidden";
			(new BX.easing({
				duration : 200,
				start : { opacity: opening ? 0 : 100, height: opening ? 0 : maxHeight },
				finish : { opacity: opening ? 100 : 0, height: opening ? maxHeight : 0 },
				transition : BX.easing.transitions.linear,
				step : function(state)
				{
					hiddenBlock.style.opacity = state.opacity/100;
					hiddenBlock.style.height = state.height + "px";

				},
				complete : function()
				{
					BX.toggleClass(BX('left-menu-hidden-items-block'), 'menu-item-favorites-more-open');
					hiddenBlock.style.overflow = "";
					hiddenBlock.style.height = "";
				}

			})).animate();
		}
	};

	LeftMenuClass.prototype.showHideMoreItems = function(animate)
	{
		if (this.isEditMode())
			return;

		if (animate !== false)
		{
			this.animateShowingHiddenItems();
		}
		else
		{
			BX.toggleClass(BX('left-menu-hidden-items-block'), 'menu-item-favorites-more-open');
		}

		BX.toggleClass(this.menuMoreButton, 'menu-favorites-more-btn-open');
		BX.toggleClass(BX('menu-hidden-counter'), 'menu-hidden-counter');
		BX.firstChild(this.menuMoreButton).innerHTML =
			(BX.firstChild(this.menuMoreButton).innerHTML == BX.message('more_items_hide'))
			? BX.message('more_items_show')
			: BX.message('more_items_hide')
		;
	};

	LeftMenuClass.prototype.openMenuPopup = function(bindElement, menuItemId)
	{
		var itemNode  = BX("bx_left_menu_" + menuItemId);

		if (!BX.type.isDomNode(itemNode))
			return;

		var contextMenuItems = [];
		var itemDeletePerm = itemNode.getAttribute("data-delete-perm");
		var itemType = itemNode.getAttribute("data-type");

		//hide item
		if (itemNode.getAttribute("data-status") == "show")
		{
			contextMenuItems.push({
				text : BX.message("hide_item"),
				className : "menu-popup-no-icon",
				onclick : BX.proxy(function() {
					var currentContext = BX.proxy_context;
					currentContext.popupWindow.close();
					this.hideItem(menuItemId);
					BX.PopupMenu.destroy("popup_"+menuItemId);
				}, this)
			});
		}

		//show item
		if (itemNode.getAttribute("data-status") == "hide")
		{
			contextMenuItems.push({
				text : BX.message("show_item"),
				className : "menu-popup-no-icon",
				onclick : BX.proxy(function() {
					var currentContext = BX.proxy_context;
					currentContext.popupWindow.close();
					this.showItem(menuItemId);
					BX.PopupMenu.destroy("popup_"+menuItemId);
				}, this)
			});
		}

		//set main page
		if (!this.isExtranet && itemType !== "self" && BX.previousSibling(itemNode).id != "left-menu-empty-item" && this.isPublicConverted)
		{
			contextMenuItems.push({
				text: BX.message("MENU_SET_MAIN_PAGE"),
				className: "menu-popup-no-icon",
				onclick: BX.proxy(function ()
				{
					var currentContext = BX.proxy_context;
					currentContext.popupWindow.close();
					this.setMainPage(menuItemId);
					BX.PopupMenu.destroy("popup_" + menuItemId);
				}, this)
			});
		}

		if (itemType == "self")
		{
			contextMenuItems.push({
				text : BX.message("MENU_DELETE_SELF_ITEM"),
				className : "menu-popup-no-icon",
				onclick : BX.proxy(function() {
					var currentContext = BX.proxy_context;
					currentContext.popupWindow.close();

					this.showConfirmWindow({
						id: "left-menu-delete-self-item",
						titleBar: BX.message("MENU_DELETE_SELF_ITEM"),
						okButtonText: BX.message("MENU_DELETE"),
						content: BX.message("MENU_DELETE_SELF_ITEM_CONFIRM"),
						onsuccess: BX.proxy(function() {
							BX.proxy_context.popupWindow.close();
							this.deleteSelfItem(menuItemId);
							BX.PopupMenu.destroy("popup_" + menuItemId);
						}, this),
						onfailure: BX.proxy(function() {
							BX.PopupMenu.destroy("popup_" + menuItemId);
						}, this)
					});

				}, this)
			});

			contextMenuItems.push({
				text : BX.message("MENU_EDIT_ITEM"),
				className : "menu-popup-no-icon",
				onclick : BX.proxy(function() {
					var currentContext = BX.proxy_context;
					currentContext.popupWindow.close();
					var linkNode = BX.findChild(itemNode, {tagName: "a"}, true, false);

					var itemInfo = {
						id: menuItemId,
						text: itemNode.querySelector("[data-role='item-text']").innerText,
						link: itemNode.getAttribute("data-link"),
						openInNewPage: linkNode.getAttribute("target") == "_blank"
					};

					this.showSelfItemPopup(bindElement, itemInfo);
					BX.PopupMenu.destroy("popup_"+menuItemId);
				}, this)
			});
		}

		if (itemType == "standard")
		{
			contextMenuItems.push({
				text : BX.message("MENU_RENAME_ITEM"),
				className : "menu-popup-no-icon",
				onclick : BX.proxy(function() {
					var itemInfo = {
						id: menuItemId,
						text: itemNode.querySelector("[data-role='item-text']").innerText
					};
					this.showStandardEditItemPopup(bindElement, itemInfo);
					BX.PopupMenu.destroy("popup_"+menuItemId);
				}, this)
			});

			contextMenuItems.push({
				text : BX.message("MENU_REMOVE_STANDARD_ITEM"),
				className : "menu-popup-no-icon",
				onclick : BX.proxy(function() {
					var currentContext = BX.proxy_context;
					currentContext.popupWindow.close();
					this.deleteStandardItem(menuItemId);
					BX.PopupMenu.destroy("popup_"+menuItemId);
				}, this)
			});
		}

		if (this.isAdmin)
		{
			//add to favorite all
			if (itemDeletePerm == "Y")
			{
				contextMenuItems.push({
					text : BX.message("MENU_ADD_ITEM_TO_ALL"),
					className : "menu-popup-no-icon",
					onclick : BX.proxy(function() {
						this.addItemToAll(menuItemId);
						BX.PopupMenu.destroy("popup_"+menuItemId);
					}, this)
				});
			}

			//delete from favorite all
			if (itemDeletePerm == "A")
			{
				contextMenuItems.push({
					text : BX.message("MENU_DELETE_ITEM_FROM_ALL"),
					className : "menu-popup-no-icon",
					onclick : BX.proxy(function() {
						this.deleteItemFromAll(menuItemId);
						BX.PopupMenu.destroy("popup_"+menuItemId);
					}, this)
				});
			}

			//set rights for apps
			//if (itemNode.getAttribute("data-app-id"))
			//	contextMenuItems.push({text : BX.message("set_rights"), className : "menu-popup-no-icon", onclick : function() {this.popupWindow.close(); self.setRights(menuItemId); BX.PopupMenu.destroy("popup_"+menuItemId);}});
		}

		BX.PopupMenu.show("popup_"+menuItemId, bindElement, contextMenuItems,
		{
			offsetTop:0,
			offsetLeft : 12,
			angle :true,
			events : {
				onPopupClose : function() {
					BX.removeClass(bindElement, 'menu-favorites-btn-active');
					BX.PopupMenu.destroy("popup_"+menuItemId);
				}
			}
		});
		BX.addClass(bindElement, 'menu-favorites-btn-active');
	};

	LeftMenuClass.prototype.showSettingsPopup = function(event)
	{
		var menuId = "leftMenuSettingsPopup";
		if (BX.PopupMenu.getMenuById(menuId))
		{
			BX.PopupMenu.destroy(menuId);
			return;
		}

		var self = this;

		var itemType = "default";
		if (BX.type.isDomNode(this.menuSelectedNode))
		{
			itemType = this.menuSelectedNode.getAttribute("data-type");
		}

		if  (this.isCurrentPageInLeftMenu && itemType == "default")
		{
			itemPageToLeftMenu = {
				text : BX.message(this.isCurrentPageInLeftMenu ? "MENU_DELETE_PAGE_FROM_LEFT_MENU" : "MENU_ADD_PAGE_TO_LEFT_MENU"),
				className : "menu-popup-no-icon menu-popup-disable-text"
			};
		}
		else
		{
			var itemPageToLeftMenu = {
				text : BX.message(this.isCurrentPageInLeftMenu ? "MENU_DELETE_PAGE_FROM_LEFT_MENU" : "MENU_ADD_PAGE_TO_LEFT_MENU"),
				className : "menu-popup-no-icon",
				onclick : BX.proxy(function() {
					BX.proxy_context.popupWindow.close();
					if (this.isCurrentPageInLeftMenu)
					{
						this.deleteStandardItem();
					}
					else
					{
						this.addStandardItem();
					}
				}, this)
			};
		}

		var menuItems = [
			{
				text : BX.message("SORT_ITEMS"),
				className : "menu-popup-no-icon",
				onclick : function() {
					this.popupWindow.close();
					self.applyEditMode();
				}
			},
			{
				text : BX.message("MENU_COLLAPSE"),
				className : "menu-popup-no-icon",
				onclick : BX.proxy(function() {
					BX.proxy_context.popupWindow.close();
					this.toggle(false);
				}, this)
			},
			itemPageToLeftMenu,
			{
				text : BX.message("MENU_ADD_SELF_PAGE"),
				className : "menu-popup-no-icon",
				onclick : BX.proxy(function() {
					BX.proxy_context.popupWindow.close();
					this.showSelfItemPopup(this.menuSettingsButton);
				}, this)
			},
			{
				text : BX.message("MENU_SET_DEFAULT"),
				className : "menu-popup-no-icon",
				onclick : function() {
					this.popupWindow.close();
					self.setDefaultMenu();
				}
			}
		];

		BX.PopupMenu.show(menuId, this.menuSettingsButton, menuItems,
		{
			offsetTop:0,
			offsetLeft : 12,
			angle :true,
			events : {
				onPopupClose : function() {
					BX.PopupMenu.destroy(menuId);
				}
			}
		});
	};

	LeftMenuClass.prototype.showMessage = function(bindElement, message, position)
	{
		var popup = BX.PopupWindowManager.create("left-menu-message", bindElement, {
			content: '<div class="left-menu-message-popup">' + message + '</div>',
			darkMode: true,
			offsetTop: position == "right" ? -45 : 2,
			offsetLeft: position == "right" ? 215 : 0,
			angle: position === "right" ? { position: "left" } : true,
			events : {
				onPopupClose: function() {
					if (popup)
					{
						popup.destroy();
						popup = null;
					}
				}
			},
			autoHide:true
		});

		popup.show();

		setTimeout(function(){
			if (popup)
			{
				popup.destroy();
				popup = null;
			}
		}, 3000);
	};

	LeftMenuClass.prototype.showError = function(bindElement)
	{
		this.showMessage(bindElement, BX.message('edit_error'));
	};

	/*LeftMenuClass.prototype.setRights =  function(menuItemId)
	{
		BX.rest.Marketplace.setRights(BX(menuItemId).getAttribute("data-app-id"), this.siteId);
	};*/

	LeftMenuClass.prototype.onTopMenuFirstItemChange = function(firstPageLink, firstNode)
	{
		if (!firstPageLink)
			return;

		var topMenuId = firstNode.getAttribute("data-top-menu-id");
		var leftMenuNode = this.menuItemsBlock.querySelector("[data-top-menu-id='" + topMenuId + "']");
		if (BX.type.isDomNode(leftMenuNode))
		{
			leftMenuNode.setAttribute("data-link", firstPageLink);

			var leftMenuLink = BX.findChild(leftMenuNode, {tagName: "a", className: "menu-item-link"}, true, false);
			if (leftMenuLink)
			{
				leftMenuLink.setAttribute("href", firstPageLink);
			}
		}

		if (BX.type.isDomNode(firstNode))
		{
			this.showMessage(firstNode, BX.message("MENU_ITEM_MAIN_SECTION_PAGE"));
		}

		if (BX.type.isDomNode(leftMenuNode) && BX.previousSibling(leftMenuNode) == BX("left-menu-empty-item"))
		{
			var ajaxData = {
				sessid : BX.bitrix_sessid(),
				site_id : this.siteId,
				action: "set_first_page",
				firstPageUrl: firstPageLink
			};
		}
		else
		{
			ajaxData = {
				sessid : BX.bitrix_sessid(),
				site_id : this.siteId,
				action: "clear_cache"
			};
		}

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data: ajaxData,
			onsuccess: BX.proxy(function()
			{
			}, this),
			onfailure: function ()
			{
			}
		});
	};

	LeftMenuClass.prototype.showSelfItemPopup = function(bindElement, itemInfo)
	{
		var isEditMode = false;
		if (typeof itemInfo === "object" && itemInfo)
		{
			isEditMode = true;
		}

		var popupContent = BX.create("form", {
			attrs: {
				name: "menuAddToFavoriteForm"
			},
			children: [
				BX.create("label", {
					attrs: {
						for: "menuPageToFavoriteName",
						className: "menu-form-label"
					},
					html: BX.message("MENU_ITEM_NAME")
				}),
				BX.create("input", {
					attrs: {
						value: isEditMode ? itemInfo.text : "",//document.title,
						name: "menuPageToFavoriteName",
						type: "text",
						className: "menu-form-input"
					}
				}),
				BX.create("br"),BX.create("br"),
				BX.create("label", {
					attrs: {
						for: "menuPageToFavoriteLink",
						className: "menu-form-label"
					},
					html: BX.message("MENU_ITEM_LINK")
				}),
				BX.create("input", {
					attrs: {
						value: isEditMode ? itemInfo.link : "",//document.location.pathname,
						name: "menuPageToFavoriteLink",
						type: "text",
						className: "menu-form-input"
					}
				}),
				BX.create("br"),BX.create("br"),
				BX.create("input", {
					attrs: {
						value: "",
						name: "menuOpenInNewPage",
						type: "checkbox",
						checked: !isEditMode || itemInfo.openInNewPage ? "checked" : "",
						id: "menuOpenInNewPage"
					}
				}),
				BX.create("label", {
					attrs: {
						for: "menuOpenInNewPage",
						className: "menu-form-label"
					},
					html: BX.message("MENU_OPEN_IN_NEW_PAGE")
				})
			]
		});

		if (isEditMode)
		{
			popupContent.appendChild(BX.create("input", {
				attrs: {
					name: "menuItemId",
					type: "hidden",
					value: itemInfo.id
				}
			}));
		}

		BX.PopupWindowManager.create("menu-self-item-popup", bindElement, {
			closeIcon : true,
			offsetTop : 1,
			//overlay : { opacity : 20 },
			lightShadow : true,
			draggable : { restrict : true},
			closeByEsc : true,
			titleBar: isEditMode ? BX.message("MENU_EDIT_SELF_PAGE") : BX.message("MENU_ADD_SELF_PAGE"),
			content : popupContent,
			buttons: [
				new BX.PopupWindowButton({
					text : isEditMode ? BX.message("MENU_SAVE_BUTTON") : BX.message("MENU_ADD_BUTTON"),
					className : 'popup-window-button-create',
					events : {
						click : BX.proxy(function()
						{
							var form = document.forms["menuAddToFavoriteForm"];
							var textField = form.elements["menuPageToFavoriteName"];
							var linkField = form.elements["menuPageToFavoriteLink"];
							var openNewTab = form.elements["menuOpenInNewPage"].checked;

							var text = BX.util.trim(textField.value);
							var link = this.refineUrl(linkField.value);

							if (!text || !link)
							{
								if (!link)
								{
									BX.addClass(linkField, "menu-form-input-error");
									linkField.focus();
								}

								if (!text)
								{
									BX.addClass(textField, "menu-form-input-error");
									textField.focus();
								}
							}
							else
							{
								BX.removeClass(textField, "menu-form-input-error");
								BX.removeClass(linkField, "menu-form-input-error");

								var itemNewInfo = {
									text: text,
									link: link,
									openInNewPage: openNewTab ? "Y" : "N"
								};

								if (isEditMode)
								{
									itemNewInfo.id = itemInfo.id;
								}

								this.saveSelfItem(
									isEditMode ? "edit" : "add",
									itemNewInfo,
									this.onSelfItemSave.bind(this)
								);
							}
					}, this)}
				}),
				new BX.PopupWindowButtonLink({
					text: BX.message('MENU_CANCEL'),
					className: "popup-window-button-link-cancel",
					events: { click : function()
					{
						this.popupWindow.close();
					}}
				})
			],
			events : {
				onPopupClose : function() {
					BX.PopupWindowManager.getCurrentPopup().destroy();
				},

				onPopupShow: function() {
					var form = document.forms["menuAddToFavoriteForm"];
					var text = form.elements["menuPageToFavoriteName"];
					text && setTimeout(function() {text.focus();}, 100);
				}
			}
		}).show();
	};

	LeftMenuClass.prototype.onSelfItemSave = function(error)
	{
		if (error)
		{
			this.showConfirmWindow({
				alertMode: true,
				titleBar: BX.message("MENU_ERROR_OCCURRED"),
				content: error
			});
		}
		else
		{
			BX.PopupWindowManager.getCurrentPopup().destroy();
		}
	};

	LeftMenuClass.prototype.saveSelfItem = function(mode, itemData, callback)
	{
		BX.ajax({
			method: "POST",
			dataType: "json",
			url: this.ajaxPath,
			data: {
				sessid : BX.bitrix_sessid(),
				site_id : this.siteId,
				itemData: itemData,
				action: mode == "edit" ? "update_self_item" : "add_self_item"
			},
			onsuccess: BX.proxy(function(json)
			{
				if (json.hasOwnProperty("error"))
				{
					callback(json.error);
				}
				else
				{
					var itemParams = {
						text: itemData.text,
						link: itemData.link,
						type: "self",
						openInNewPage : itemData.openInNewPage == "Y" ? "Y" : "N"
					};

					if (mode == "add" && json.hasOwnProperty("itemId"))
					{
						itemParams.id = json.itemId;
						this.generateItemHtml(itemParams);
					}
					else if (mode == "edit")
					{
						itemParams.id = itemData.id;
						this.updateItemHtml(itemParams);
					}

					callback("");
				}

			}, this),
			onfailure: function ()
			{
				callback();
			}
		});
	};

	LeftMenuClass.prototype.deleteSelfItem = function(itemId)
	{
		var itemNode = BX("bx_left_menu_" + itemId);

		if (!BX.type.isDomNode(itemNode))
			return;

		if (itemNode.getAttribute("data-delete-perm") == "A") //delete from all
		{
			this.deleteItemFromAll(itemId);
		}

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data: {
				sessid : BX.bitrix_sessid(),
				site_id : this.siteId,
				action : "delete_self_item",
				menu_item_id : itemId
			},
			onsuccess: BX.proxy(function(json)
			{
				if (json.error)
				{
					this.showError(itemNode);
				}
				else
				{
					BX.remove(itemNode);
				}
			}, this),
			onfailure: function () {}
		});
	};

	LeftMenuClass.prototype.refineUrl = function(url)
	{
		url = BX.util.trim(url);
		if (!BX.type.isNotEmptyString(url))
		{
			return "";
		}

		if (!url.match(/^https?:\/\//i) && !url.match(/^\//i))
		{
			//for external links like "google.com" (without a protocol)
			url = "http://" + url;
		}
		else
		{
			var link = document.createElement("a");
			link.href = url;

			if (document.location.host === link.host)
			{
				// http://portal.com/path/ => /path/
				url = link.pathname + link.search + link.hash;
			}
		}

		return url;
	};

	LeftMenuClass.prototype.addItemToAll =  function(menuItemId)
	{
		var itemNode = BX("bx_left_menu_" + menuItemId);

		if (!BX.type.isDomNode(itemNode))
			return;

		var itemLink = itemNode.getAttribute("data-link"),
			itemTextNode= itemNode.querySelector("[data-role='item-text']"),
			itemText = itemTextNode.innerText,
			itemCounterId = itemNode.getAttribute("data-counter-id"),
			itemLinkNode = BX.findChild(itemNode, {tagName: "a"}, true, false),
			openInNewPage = BX.type.isDomNode(itemLinkNode) && itemLinkNode.hasAttribute("target") && itemLinkNode.getAttribute("target") == "_blank";

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data: {
				sessid : BX.bitrix_sessid(),
				site_id : this.siteId,
				action : "add_item_to_all",
				itemInfo: {id: menuItemId, link: itemLink, text: itemText, counterId: itemCounterId, openInNewPage: openInNewPage ? "Y" : "N"}
			},
			onsuccess: BX.proxy(function(json)
			{
				if (json.error)
				{
					this.showError(itemNode);
				}
				else
				{
					itemNode.setAttribute("data-delete-perm", "A");
					this.showMessage(itemNode, BX.message("MENU_ITEM_WAS_ADDED_TO_ALL"));
				}
			}, this)
		});
	};

	LeftMenuClass.prototype.deleteItemFromAll = function(menuItemId)
	{
		var itemNode = BX("bx_left_menu_" + menuItemId);

		if (!BX.type.isDomNode(itemNode))
			return;

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data: {
				sessid : BX.bitrix_sessid(),
				site_id : this.siteId,
				action : "delete_item_from_all",
				menu_item_id : menuItemId
			},
			onsuccess: BX.proxy(function(json)
			{
				if (json.error)
				{
					this.showError(itemNode);
				}
				else
				{
					itemNode.setAttribute("data-delete-perm", "Y");
					this.showMessage(itemNode, BX.message("MENU_ITEM_WAS_DELETED_FROM_ALL"));
				}
			}, this)
		});
	};

	LeftMenuClass.prototype.recountHiddenCounters = function()
	{
		var curSumCounters = 0;
		var hiddenItems = BX.findChildren(BX("left-menu-hidden-items-list"), {className: "menu-item-block"}, true);

		if (hiddenItems)
		{
			for(var i = 0, l = hiddenItems.length; i < l; i++)
			{
				var curCounter = hiddenItems[i].getAttribute("data-counter-id");
				if (this.allCounters[curCounter])
				{
					curSumCounters+= Number(this.allCounters[curCounter]);
				}
			}
		}

		BX("menu-hidden-counter").innerHTML = curSumCounters > 50 ? "50+" : curSumCounters;
		BX("menu-hidden-counter").style.display = curSumCounters > 0 ? "inline-block" : "none";
	};

	LeftMenuClass.prototype.checkMoreButton = function(status)
	{
		var btn = BX("left-menu-more-btn");
		if (status === true || status === false)
		{
			if (status)
			{
				BX.removeClass(btn, "menu-favorites-more-btn-hidden");
			}
			else
			{
				BX.addClass(btn, "menu-favorites-more-btn-hidden");
			}

			return status;
		}

		var hiddenItems = BX("left-menu-hidden-items-list").getElementsByClassName("menu-item-block");
		if (hiddenItems.length > 0)
		{
			BX.removeClass(btn, "menu-favorites-more-btn-hidden");
			return true;
		}
		else
		{
			BX.addClass(btn, "menu-favorites-more-btn-hidden");
			return false;
		}
	};

	LeftMenuClass.prototype.hideItem = function(menuItemId)
	{
		var itemNode = BX("bx_left_menu_" + menuItemId);

		if (!BX.type.isDomNode(itemNode))
			return;

		itemNode.setAttribute("data-status", "hide");
		BX("left-menu-hidden-items-list").appendChild(itemNode);

		this.checkMoreButton(true);

		if (itemNode.getAttribute("data-counter-id"))
		{
			this.recountHiddenCounters();
		}

		this.saveItemsSort();
	};

	LeftMenuClass.prototype.showItem = function(menuItemId)
	{
		var itemNode = BX("bx_left_menu_" + menuItemId);

		if (!BX.type.isDomNode(itemNode))
			return;

		if (BX.type.isDomNode(this.menuItemsBlock))
		{
			itemNode.setAttribute("data-status", "show");
			this.menuItemsBlock.insertBefore(itemNode, BX("left-menu-hidden-items-block"));
		}

		this.checkMoreButton();

		if (itemNode.getAttribute("data-counter-id"))
		{
			this.recountHiddenCounters();
		}

		this.saveItemsSort();
	};

	LeftMenuClass.prototype.saveItemsSort = function()
	{
		var showMenuItems = [],
			hideMenuItems = [],
			firstItemLink = "";

		var items = BX.findChildren(this.menuContainer, {className:"menu-item-block"}, true);

		for (var i=0; i<items.length; i++)
		{
			if (i == 0)
			{
				firstItemLink = items[i].getAttribute("data-link");
			}

			if (items[i].getAttribute("data-status") == "show")
			{
				showMenuItems.push(items[i].getAttribute("data-id"));
			}
			else if (items[i].getAttribute("data-status") == "hide")
			{
				hideMenuItems.push(items[i].getAttribute("data-id"));
			}
		}

		var menuItems = {"show" : showMenuItems, "hide" : hideMenuItems};

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data: {
				sessid : BX.bitrix_sessid(),
				site_id : this.siteId,
				action : "save_items_sort",
				items : menuItems,
				firstItemLink: firstItemLink
			},
			onsuccess: function(json) { },
			onfailure: function () { }
		});
	};

	LeftMenuClass.prototype.animateTopItemToLeft = function(itemInfo, startX, startY)
	{
		if (typeof itemInfo !== "object")
			return;

		var topMenuNode = BX.create("div", {
			text: itemInfo.text,
			attrs: {
				style: "position: absolute; z-index: 1000;"
			}
		});
		topMenuNode.style.top = startY + 25 + "px";

		document.body.appendChild(topMenuNode);

		var finishY = BX("left-menu-list").getBoundingClientRect().bottom;
		if (this.areMoreItemsShowed())
		{
			finishY-= BX("left-menu-hidden-items-list").offsetHeight;
		}

		/*(new BX.easing({
			duration : 10000,
			start : {  left: startX, top : startY + 25 },
			finish : { left: 30, top: finishY},
			transition : function(progress) {
				return Math.pow(progress, 2) * ((-1.5 + 1) * progress + 1.5);
			},
			step : function(state){
				topMenuNode.style.top = state.top + "px";
				topMenuNode.style.left = state.left + "px";
			},
			complete : BX.proxy(function()
			{


			}, this)
		})).animate();*/

		(new BX.easing({
			duration : 500,
			start : { left: startX },
			finish : { left: 30 },
			transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
			step : function(state){
				topMenuNode.style.left = state.left + "px";
			},
			complete : BX.proxy(function() {
				(new BX.easing({
					duration : 500,
					start : { top : startY + 25 },
					finish : { top: finishY},
					transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
					step : function(state){
						topMenuNode.style.top = state.top + "px";
					},
					complete : BX.proxy(function()
					{
						BX.remove(topMenuNode);
						itemInfo.type = "standard";
						this.isCurrentPageInLeftMenu = true;
						this.generateItemHtml(itemInfo);
						this.saveItemsSort();

					}, this)
				})).animate();
			}, this)
		})).animate();
	};

	LeftMenuClass.prototype.addStandardItem = function(itemInfo, startX, startY)
	{
		if (typeof itemInfo !== "object")
		{
			if (this.isCurrentPageStandard && BX.type.isDomNode(this.topMenuSelectedNode))
			{
				var menuNodeCoord = this.topMenuSelectedNode.getBoundingClientRect();
				startX = menuNodeCoord.left;
				startY = menuNodeCoord.top;

				itemInfo = {
					id: this.topItemSelectedObj.DATA_ID,
					text: this.topItemSelectedObj.TEXT,
					link: this.topItemSelectedObj.URL,
					counterId: this.topItemSelectedObj.COUNTER_ID,
					counterValue: this.topItemSelectedObj.COUNTER,
					isStandardItem: true,
					subLink: this.topItemSelectedObj.SUB_LINK
				};
			}
			else
			{
				itemInfo = {
					text: BX("pagetitle").innerText,
					link: document.location.pathname + document.location.search,
					isStandardItem: false
				};
			}
		}

		if (!startX || !startY)
		{
			var titleCoord = BX("pagetitle").getBoundingClientRect();
			startX = titleCoord.left;
			startY = titleCoord.top;
		}

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data: {
				sessid : BX.bitrix_sessid(),
				site_id : this.siteId,
				itemData: itemInfo,
				action: "add_standard_item"
			},
			onsuccess: BX.proxy(function(json)
			{
				if (json.hasOwnProperty("error"))
				{
					this.showConfirmWindow({
						alertMode: true,
						titleBar: BX.message("MENU_ERROR_OCCURRED"),
						content: json.error
					});
				}
				else
				{
					if (json.hasOwnProperty("itemId"))
					{
						itemInfo.id = json.itemId;

						BX.onCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuItemAdded", [itemInfo, this]);

						this.animateTopItemToLeft(itemInfo, startX, startY);

						this.showMessage(BX("pagetitle-star"), BX.message("MENU_ITEM_WAS_ADDED_TO_LEFT"));
						BX("pagetitle-star").title = BX.message("MENU_DELETE_PAGE_FROM_LEFT_MENU");
						BX.addClass(BX("pagetitle-star"), "pagetitle-star-active");
						this.isCurrentPageInLeftMenu = true;
					}
				}
			}, this),
			onfailure: function ()
			{
			}
		});
	};

	LeftMenuClass.prototype.deleteStandardItem = function(itemId)
	{
		if (itemId && BX.type.isDomNode(BX("bx_left_menu_" + itemId)))
		{
			var itemData = {
				id: itemId
			};
		}
		else if (this.isCurrentPageStandard && this.topItemSelectedObj.DATA_ID)
		{
			var itemData = {
				id: this.topItemSelectedObj.DATA_ID
			};
		}
		else
		{
			itemData = {
				link: document.location.pathname + document.location.search
			};
		}

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxPath,
			data: {
				sessid : BX.bitrix_sessid(),
				site_id : this.siteId,
				itemData: itemData,
				action: "delete_standard_item"
			},
			onsuccess: BX.proxy(function(json)
			{
				if (json.hasOwnProperty("error"))
				{
					this.showConfirmWindow({
						alertMode: true,
						titleBar: BX.message("MENU_ERROR_OCCURRED"),
						content: json.error
					});
				}
				else
				{
					if (json.hasOwnProperty("itemId"))
					{
						BX.onCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuItemDeleted", [json, this]);

						var itemNode = BX("bx_left_menu_" + json.itemId);
						if (!BX.type.isDomNode(itemNode))
							return;

						if (itemNode.getAttribute("data-delete-perm") == "A") //delete from all
						{
							this.deleteItemFromAll(json.itemId);
						}

						this.showMessage(BX("pagetitle-star"), BX.message("MENU_ITEM_WAS_DELETED_FROM_LEFT"));
						BX("pagetitle-star").title = BX.message("MENU_ADD_PAGE_TO_LEFT_MENU");
						this.animateTopItemFromLeft("bx_left_menu_"+ json.itemId);
						BX.removeClass(BX("pagetitle-star"), "pagetitle-star-active");
						this.isCurrentPageInLeftMenu = false;
					}
				}
			}, this),
			onfailure: function ()
			{
			}
		});
	};

	LeftMenuClass.prototype.updateStandardItem = function(itemInfo)
	{
		BX.ajax({
			method: "POST",
			dataType: "json",
			url: this.ajaxPath,
			data: {
				sessid : BX.bitrix_sessid(),
				site_id : this.siteId,
				itemText: itemInfo.text,
				itemId: itemInfo.id,
				action: "update_standard_item"
			},
			onsuccess: BX.proxy(function(json)
			{
				if (json.hasOwnProperty("error"))
				{
					this.showConfirmWindow({
						alertMode: true,
						titleBar: BX.message("MENU_ERROR_OCCURRED"),
						content: json.error
					});
				}
				else
				{
					this.updateItemHtml(itemInfo);
					BX.PopupWindowManager.getCurrentPopup().destroy();
				}

			}, this),
			onfailure: function ()
			{

			}
		});
	};

	LeftMenuClass.prototype.showStandardEditItemPopup = function(bindElement, itemInfo)
	{
		var isEditMode = false;
		if (typeof itemInfo === "object" && itemInfo)
		{
			isEditMode = true;
		}

		var popupContent = BX.create("form", {
			attrs: {
				name: "menuAddToFavoriteForm"
			},
			children: [
				BX.create("label", {
					attrs: {
						for: "menuPageToFavoriteName",
						className: "menu-form-label"
					},
					html: BX.message("MENU_ITEM_NAME")
				}),
				BX.create("input", {
					attrs: {
						value: isEditMode ? itemInfo.text : "",//document.title,
						name: "menuPageToFavoriteName",
						type: "text",
						className: "menu-form-input"
					}
				}),
				BX.create("input", {
					attrs: {
						name: "menuItemId",
						type: "hidden",
						value: itemInfo.id
					}
				})
			]
		});

		BX.PopupWindowManager.create("menu-standard-item-popup-edit", bindElement, {
			closeIcon : true,
			offsetTop : 1,
			//overlay : { opacity : 20 },
			lightShadow : true,
			draggable : { restrict : true},
			closeByEsc : true,
			titleBar: BX.message("MENU_RENAME_ITEM"),
			content : popupContent,
			buttons: [
				new BX.PopupWindowButton({
					text : BX.message("MENU_SAVE_BUTTON"),
					className : 'popup-window-button-create',
					events : {
						click : BX.proxy(function()
						{
							var form = document.forms["menuAddToFavoriteForm"];
							var textField = form.elements["menuPageToFavoriteName"];
							var text = BX.util.trim(textField.value);
							if (!text)
							{
								BX.addClass(textField, "menu-form-input-error");
								textField.focus();
							}
							else
							{
								BX.removeClass(textField, "menu-form-input-error");

								var itemNewInfo = {
									text: text,
									id: itemInfo.id
								};

								this.updateStandardItem(itemNewInfo/*, this.onSelfItemSave.bind(this)*/);
							}
						}, this)}
				}),
				new BX.PopupWindowButtonLink({
					text: BX.message('MENU_CANCEL'),
					className: "popup-window-button-link-cancel",
					events: { click : function()
					{
						BX.PopupWindowManager.getCurrentPopup().destroy();
					}}
				})
			],
			events : {
				onPopupClose : function() {
					BX.PopupWindowManager.getCurrentPopup().destroy();
				}
			}
		}).show();
	};

	LeftMenuClass.prototype.animateTopItemFromLeft = function(itemId)
	{
		if (!BX.type.isDomNode(BX(itemId)))
			return;

		(new BX.easing({
			duration : 700,
			start : { left: BX(itemId).offsetLeft, opacity: 1 },
			finish : { left: 400,  opacity: 0 },
			transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
			step : function(state){
				BX(itemId).style.paddingLeft = state.left + "px";
				BX(itemId).style.opacity = state.opacity;
			},
			complete : BX.proxy(function() {
				BX.remove(BX(itemId));
				this.isCurrentPageInLeftMenu = false;
				this.saveItemsSort();
			}, this)
		})).animate();
	};

	LeftMenuClass.prototype.generateItemHtml =  function(itemParams)
	{
		if (!(typeof itemParams == "object" && itemParams))
			return;

		var itemChildren = [
			BX.create("span", {
				text: itemParams.text,
				attrs:{
					className: "menu-item-link-text",
					"data-role": "item-text"
				}
			})
		];

		var isCounterExisted = BX.type.isNotEmptyString(itemParams.counterId);
		if (isCounterExisted)
		{
			itemChildren.push(BX.create("span", {
				attrs: {className: "menu-item-index-wrap"},
				children: [
					BX.create("span", {
						attrs: {
							className: "menu-item-index",
							id: "menu-counter-" + itemParams.counterId
						},
						html: itemParams.counterValue
					})
				]
			}));
		}

		var self = this;
		var newItemNode = BX.create("li", {
			attrs: {
				className: "menu-item-block" + (isCounterExisted && itemParams.counterValue ? " menu-item-with-index" : ""),
				id: "bx_left_menu_" + itemParams.id,
				"data-type": itemParams.type == "standard" ? "standard" : "self",
				"data-delete-perm": "Y",
				"data-id": itemParams.id,
				"data-link": itemParams.link,
				"data-status": "show"
			},
			children: [
				BX.create("span", {
					attrs: {className: "menu-fav-editable-btn menu-favorites-btn"},
					children: [
						BX.create("span", {
							attrs: {className: "menu-favorites-btn-icon"}
						})
					],
					events: {
						"click": BX.proxy(function ()
						{
							this.openMenuPopup(BX.proxy_context, itemParams.id);
						}, this)
					}
				}),
				BX.create("span", {
					attrs: {className: "menu-favorites-btn menu-favorites-draggable"},
					children: [
						BX.create("span", {
							attrs: {className: "menu-fav-draggable-icon"}
						})
					],
					events: {
						"onmousedown": function ()
						{
							BX.addClass(this.parentNode, 'menu-item-draggable');
						},
						"onmouseup": function ()
						{
							BX.removeClass(this.parentNode, 'menu-item-draggable');
						}
					}
				}),
				BX.create("a", {
					attrs:{
						href: itemParams.link,
						className: "menu-item-link",
						target: (itemParams.openInNewPage == "Y" ? "_blank" : "")
					},
					children: itemChildren
				})
			]
		});

		if (BX.type.isDomNode(this.menuItemsBlock))
		{
			this.menuItemsBlock.insertBefore(newItemNode, BX('left-menu-hidden-items-block'));
		}

		newItemNode.onbxdragstart = BX.proxy(this.menuItemDragStart, this);
		newItemNode.onbxdrag =  BX.proxy(this.menuItemDragMove, this);
		newItemNode.onbxdragstop =  BX.proxy(this.menuItemDragStop, this);
		newItemNode.onbxdraghover =  BX.proxy(this.menuItemDragHover, this);
		jsDD.registerDest(newItemNode, 100);
		jsDD.registerObject(newItemNode);
	};

	LeftMenuClass.prototype.updateItemHtml =  function(itemParams)
	{
		if (!(typeof itemParams == "object" && itemParams))
			return;

		var itemNode = BX("bx_left_menu_" + itemParams.id);

		if (!BX.type.isDomNode(itemNode))
			return;

		if (itemParams.link)
		{
			itemNode.setAttribute("data-link", itemParams.link);
			var linkNode = BX.findChild(itemNode, {tagName: "a"}, true, false);
			if (BX.type.isDomNode(linkNode))
			{
				linkNode.setAttribute("href", itemParams.link);
				linkNode.setAttribute("title", itemParams.text);
				if (itemParams.hasOwnProperty("openInNewPage"))
				{
					linkNode.setAttribute("target", itemParams.openInNewPage == "Y" ? "_blank" : "");
				}
			}
		}
		if (itemParams.text)
		{
			var textNode = itemNode.querySelector("[data-role='item-text']");
			if (BX.type.isDomNode(textNode))
			{
				textNode.innerText = itemParams.text;
			}
		}
	};

	LeftMenuClass.prototype.setMainPage = function(itemId)
	{
		var itemNode = BX("bx_left_menu_" + itemId);

		if (!BX.type.isDomNode(itemNode))
			return;

		if (BX.type.isDomNode(this.menuItemsBlock))
		{
			var startTop = itemNode.offsetTop;
			var dragElement = BX.create("div", {
				attrs: { className: "menu-draggable-wrap" },
				style: { top: startTop }
			});

			var insertBeforeElement = itemNode.nextElementSibling;
			if (insertBeforeElement)
			{
				itemNode.parentNode.insertBefore(dragElement, insertBeforeElement);
			}
			else
			{
				itemNode.parentNode.appendChild(dragElement);
			}

			dragElement.appendChild(itemNode);

			BX.addClass(itemNode, "menu-item-draggable");

			(new BX.easing({
				duration : 500,
				start : { top : startTop },
				finish : { top: 0 },
				transition : BX.easing.makeEaseOut(BX.easing.transitions.quart),
				step : function(state){
					dragElement.style.top = state.top + "px";
				},
				complete : BX.proxy(function() {
					this.menuItemsBlock.insertBefore(itemNode, BX("left-menu-empty-item").nextSibling);
					BX.removeClass(itemNode, "menu-item-draggable");
					BX.remove(dragElement);

					this.saveItemsSort();
				}, this)
			})).animate();
		}
	};

	LeftMenuClass.prototype.showConfirmWindow = function(options)
	{
		options = options || {};
		var id = BX.type.isNotEmptyString(options.id) ? options.id : BX.util.getRandomString();

		var popup = BX.PopupWindowManager.create(id, null, {
			content:
				'<div class="left-menu-confirm-popup">' +
				(BX.type.isNotEmptyString(options.content) ? options.content : "") +
				'</div>',
			titleBar: BX.type.isNotEmptyString(options.titleBar) ? options.titleBar : false,
			closeByEsc: true,
			closeIcon: true,
			draggable: true,
			buttons: [
				new BX.PopupWindowButton({
					text:
						BX.type.isNotEmptyString(options.okButtonText) ?
							options.okButtonText :
							"OK",
					className: "popup-window-button-create",
					events: {
						click:
							BX.type.isFunction(options.onsuccess) ?
								options.onsuccess :
								function() {
									this.popupWindow.destroy();
								}
					}
				}),

				options.alertMode !== true ?
				new BX.PopupWindowButtonLink({
					text:
						BX.type.isNotEmptyString(options.cancelButtonText) ?
							options.cancelButtonText :
							BX.message("MENU_CANCEL"),
					className: "popup-window-button-link-cancel",
					events: {
						click : function() {
							if (BX.type.isFunction(options.onfailure))
							{
								options.onfailure();
							}

							this.popupWindow.destroy();
						}
					}
				}) : null
			]
		});

		popup.show();
	};

	LeftMenuClass.prototype.setDefaultMenu = function()
	{
		if (this.isExtranet)
		{
			if (!confirm(BX.message("MENU_SET_DEFAULT_CONFIRM")))
				return;

			BX.ajax({
				method: 'POST',
				dataType: 'json',
				url: this.ajaxPath,
				data: {
					sessid : BX.bitrix_sessid(),
					site_id : this.siteId,
					action : "set_default_menu"
				},
				onsuccess: BX.proxy(function() {
					document.location.reload();
				}, this),
				onfailure: function () { }
			});
		}
		else
		{
			this.showPresetPopupFunction("personal");
		}
	};

	/* drag&drop starting*/
	LeftMenuClass.prototype.menuItemDragStart = function()
	{
		BX.onCustomEvent("BX.Bitrix24.LeftMenuClass:onDragStart");
		var dragElement = BX.proxy_context;

		//drag&drop
		if (BX.type.isDomNode(this.menuItemsBlock))
		{
			var items = this.menuItemsBlock.getElementsByClassName("menu-item-block");
			for (var i=0; i<items.length; i++) // hack for few drag&drops on page
			{
				jsDD.registerDest(items[i], 100);
				jsDD.registerObject(items[i]);
			}
		}

		if (dragElement.getAttribute("data-type") == "self")
		{
			jsDD.unregisterDest(BX("left-menu-empty-item"));
		}
		else
		{
			jsDD.registerDest(BX("left-menu-empty-item"), 100);
		}

		jsDD.registerDest(BX("left-menu-hidden-empty-item"), 100);
		jsDD.registerDest(BX("left-menu-hidden-separator"), 100);

		if (!this.isEditMode())
		{
			this.areMoreItemsShowedState = this.areMoreItemsShowed();
			if (!this.areMoreItemsShowedState)
			{
				this.showHideMoreItems();
			}
		}

		this.itemHeight = dragElement.offsetHeight;

		BX.addClass(dragElement, "menu-item-draggable");
		BX.addClass(this.menuContainer, "menu-drag-mode");

		this.itemDomBlank = dragElement.parentNode.insertBefore(BX.create('div', {style: {height: '0px'}}), dragElement); //remember original item place
		this.itemMoveBlank = BX.create('div', {style: {height: this.itemHeight + 'px'}}); //empty div

		this.draggableBlock = BX.create('div', {             //div to move
			attrs: { className: "menu-draggable-wrap" },
			children: [dragElement]
		});

		this.menuItemsBlockCoord = BX.pos(this.menuItemsBlock);
		this.menuItemsBlock.style.position = 'relative';
		this.menuItemsBlock.appendChild(this.draggableBlock);
	};

	LeftMenuClass.prototype.menuItemDragMove = function(x, y)
	{
		y -= this.menuItemsBlockCoord.top;
		var menuItemsBlockHeight = this.menuItemsBlock.offsetHeight;

		if (y < 0)
			y = 0;

		if (y > menuItemsBlockHeight - this.itemHeight)
			y = menuItemsBlockHeight - this.itemHeight;

		this.draggableBlock.style.top = y + 'px';
	};

	LeftMenuClass.prototype.menuItemDragHover = function(dest, x, y)
	{
		var dragElement = BX.proxy_context;

		if (dest == dragElement)
		{
			this.itemDomBlank.parentNode.insertBefore(this.itemMoveBlank, this.itemDomBlank);
		}
		else
		{
			if (BX.findParent(dest, {className: "menu-items"}))  //li is hovered
			{
				if (BX.nextSibling(dest))
					dest.parentNode.insertBefore(this.itemMoveBlank, BX.nextSibling(dest));
				else
					dest.parentNode.appendChild(this.itemMoveBlank);
			}
		}
	};

	LeftMenuClass.prototype.menuItemDragStop = function()
	{
		var dragElement = BX.proxy_context;

		BX.removeClass(this.menuContainer, "menu-drag-mode");
		BX.removeClass(dragElement, "menu-item-draggable");

		var firstItem = BX.findChild(this.menuContainer, {className: "menu-item-block"}, true, false);
		if (BX.type.isDomNode(firstItem) && firstItem.getAttribute("data-type") == "self")
		{
			this.showMessage(firstItem, BX.message("MENU_SELF_ITEM_FIRST_ERROR"), "right");
			this.menuItemsBlock.replaceChild(dragElement, this.itemDomBlank);
		}
		else if (this.itemMoveBlank && BX.findParent(this.itemMoveBlank, {className: "menu-items"}))
		{
			this.itemMoveBlank.parentNode.replaceChild(dragElement, this.itemMoveBlank);

			if (dragElement.parentNode.id == "left-menu-hidden-items-list")
			{
				if (dragElement.getAttribute("data-status") == "show" && dragElement.getAttribute("data-counter-id"))
				{
					this.recountHiddenCounters();
				}

				dragElement.setAttribute("data-status", "hide");
			}
			else
			{
				if (dragElement.getAttribute("data-status") == "hide" && dragElement.getAttribute("data-counter-id"))
				{
					this.recountHiddenCounters();
				}
				dragElement.setAttribute("data-status", "show");
			}

			this.checkMoreButton();
			this.saveItemsSort();

			var prevItem = BX.previousSibling(dragElement);
			if (BX.type.isDomNode(prevItem) && prevItem.id == "left-menu-empty-item" && !this.isExtranet)
			{
				this.showMessage(dragElement, BX.message("MENU_ITEM_MAIN_PAGE"), "right");
			}
		}
		else
		{
			this.menuItemsBlock.replaceChild(dragElement, this.itemDomBlank);
		}

		BX.remove(this.draggableBlock);
		BX.remove(this.itemDomBlank);
		BX.remove(this.itemMoveBlank);

		jsDD.enableDest(dragElement);
		this.menuItemsBlock.style.position = 'static';

		if (!this.isEditMode() && !this.areMoreItemsShowedState)
		{
			this.showHideMoreItems();
		}

		this.draggableBlock = null;
		this.menuItemsBlockCoord = null;
		this.itemDomBlank = null;
		this.itemMoveBlank = null;
		this.areMoreItemsShowedState = null;
		
		jsDD.refreshDestArea();
	};
	/* drag&drop finishing*/

	LeftMenuClass.prototype.clearCompositeCache = function()
	{
		BX.ajax.post(
			this.ajaxPath,
			{
				sessid : BX.bitrix_sessid(),
				action : "clear"
			},
			function(result) {

			}
		);
	};

	LeftMenuClass.prototype.onResizerMouseOver = function()
	{
		if (this.isCollapsedMode)
		{
			return;
		}

		if (this.resizerTimeout)
		{
			clearTimeout(this.resizerTimeout);
		}

		this.resizerTimeout = setTimeout(BX.proxy(function() {
			this.menuResizerButton.style.opacity = 1;
		}, this), 150);
	};

	LeftMenuClass.prototype.onResizerMouseOut = function()
	{
		if (this.isCollapsedMode)
		{
			return;
		}

		if (this.resizerTimeout)
		{
			clearTimeout(this.resizerTimeout);
		}

		this.resizerTimeout = setTimeout(BX.proxy(function() {
			this.menuResizerButton.style.opacity = 0;
		}, this), 200);
	};

	LeftMenuClass.prototype.onResizerClick = function(event) {
		this.toggle();
		event.stopPropagation();
	};

	LeftMenuClass.prototype.onMenuDoubleClick = function(event) {
		if (event.target === this.menuContainer)
		{
			this.toggle(false);
		}
		else if (event.target === this.leftColumnBottom)
		{
			this.toggle();
		}
	};

	LeftMenuClass.prototype.toggle = function(flag)
	{
		var leftColumn = BX("layout-left-column"); /* we have to modify bitrix24 template */
		var table = BX.findParent(leftColumn, { tagName: "table" });
		if (!leftColumn)
		{
			return;
		}

		var isOpened = !BX.hasClass(table, "menu-collapsed-mode");
		if ( (flag === true && isOpened) || (flag === false && !isOpened))
		{
			return;
		}

		BX.onCustomEvent("BX.Bitrix24.LeftMenuClass:onMenuToggle", [flag, this]);

		leftColumn.style.overflow = "hidden";
		if (!isOpened)
		{
			leftColumn.style.opacity = 0;
		}
		this.menuResizer.style.display = "none";
		this.menuResizerButton.style.opacity = 0;
		BX.addClass(leftColumn, "menu-animation-mode");

		(new BX.easing({
			duration: 400,
			start: {
				width: isOpened ? 240 : 40, /* these values are duplicated in style.css as well */
				opacity: isOpened ? 100 : 0
			},
			finish: {
				width: isOpened ? 40 : 240,
				opacity: isOpened ? 0 : 100
			},
			transition: BX.easing.makeEaseOut(BX.easing.transitions.quart),
			step: function (state) {
				leftColumn.style.width = state.width + "px";
				leftColumn.style.opacity = state.opacity / 100;
			},
			complete: BX.proxy(function () {
				leftColumn.style.cssText = "";
				this.menuResizer.style.cssText = "";

				if (isOpened)
				{
					this.isCollapsedMode = true;
					this.menuResizerButton.style.cssText = "";
					BX.addClass(table, "menu-collapsed-mode");
					BX.removeClass(leftColumn, "menu-animation-mode");
				}
				else
				{
					this.isCollapsedMode = false;
					BX.removeClass(leftColumn, "menu-animation-mode");
					BX.removeClass(table, "menu-collapsed-mode");
				}

				BX.ajax({
					method: "POST",
					dataType: "json",
					url: this.ajaxPath,
					data: {
						sessid : BX.bitrix_sessid(),
						site_id : this.siteId,
						action : isOpened ? "collapse_menu" : "expand_menu"
					}
				});

				var event = document.createEvent("Event");
				event.initEvent("resize", true, true);
				window.dispatchEvent(event);

			}, this)
		})).animate();
	};

	LeftMenuClass.prototype.highlight = function(currentUrl)
	{
		if (!BX.type.isNotEmptyString(currentUrl) || !this.menuContainer)
		{
			return false;
		}

		var items = this.menuContainer.getElementsByTagName("li");
		var curSelectedItem = -1;
		var curSelectedLen = -1;
		var curSelectedUrl = null;

		for (var i = 0, length = items.length; i < length; i++)
		{
			var itemLinks = [];
			var dataLink = items[i].getAttribute("data-link");
			if (BX.type.isNotEmptyString(dataLink))
			{
				//Highlighting hack. Custom items have more priority than standard items.
				var itemType = items[i].getAttribute("data-type");
				if (BX.util.in_array(itemType, ["standard", "admin"]))
				{
					var queryPos = dataLink.indexOf("?");
					var path = queryPos === -1 ? dataLink : dataLink.substring(0, queryPos);
					var query = queryPos === -1 ? "" : dataLink.substring(queryPos);

					if (!path.match(/\.php/i))
					{
						if (path.slice(-1) !== "/")
						{
							path += "/";
						}

						dataLink = path + "index.php" + query;
					}
				}

				itemLinks.push(dataLink);
			}

			var dataLinks = items[i].getAttribute("data-all-links");
			if (BX.type.isNotEmptyString(dataLinks))
			{
				itemLinks = itemLinks.concat(dataLinks.split(","));
			}

			for (var j = 0, l = itemLinks.length; j < l; j++)
			{
				var itemLink = itemLinks[j];
				if (!BX.type.isNotEmptyString(itemLink))
				{
					continue;
				}

				var isItemSelected = this.isItemSelected(itemLink, currentUrl);
				if (isItemSelected)
				{
					var newLength = itemLink.length;
					if (newLength > curSelectedLen)
					{
						curSelectedItem = i;
						curSelectedUrl = itemLinks[j];
						curSelectedLen = newLength;
					}
				}
			}
		}

		if (curSelectedItem < 0)
		{
			return;
		}

		var li = items[curSelectedItem];
		BX.addClass(li, "menu-item-active");

		//Show hidden item
		var moreItem = li.parentNode.parentNode;
		if (
			BX.hasClass(moreItem, "menu-item-favorites-more") &&
			!BX.hasClass(moreItem, "menu-item-favorites-more-open")
		)
		{
			this.showHideMoreItems(false);
		}

		return true;
	};

	LeftMenuClass.prototype.getSelectedItem = function(currentUrl, allLinks)
	{
		if (!BX.type.isNotEmptyString(currentUrl) || !this.menuContainer)
		{
			return false;
		}

		var items = this.menuContainer.getElementsByTagName("li");
		var curSelectedItem = -1;
		var curSelectedLen = -1;
		var curSelectedUrl = null;

		for (var i = 0, length = items.length; i < length; i++)
		{
			var itemLinks = [];

			if (allLinks)
			{
				var dataLinks = items[i].getAttribute("data-all-links");
				if (BX.type.isNotEmptyString(dataLinks))
				{
					itemLinks = itemLinks.concat(dataLinks.split(","));
				}
			}
			else
			{
				var dataLink = items[i].getAttribute("data-link");
				if (BX.type.isNotEmptyString(dataLink))
				{
					itemLinks.push(dataLink);
				}
			}

			for (var j = 0, l = itemLinks.length; j < l; j++)
			{
				var itemLink = itemLinks[j];
				if (!BX.type.isNotEmptyString(itemLink))
				{
					continue;
				}

				var isItemSelected = this.isItemSelected(itemLink, currentUrl);
				if (isItemSelected)
				{
					var newLength = itemLink.length;
					if (newLength > curSelectedLen)
					{
						curSelectedItem = i;
						curSelectedUrl = itemLinks[j];
						curSelectedLen = newLength;
					}
				}
			}
		}

		return curSelectedItem >= 0 ? items[curSelectedItem] : null;

	};

	LeftMenuClass.prototype.isItemSelected = function(url, currentUrl)
	{
		var originalCurrentUrl = currentUrl;
		var questionPos = currentUrl.indexOf("?");
		if (questionPos !== -1)
		{
			currentUrl = currentUrl.substring(0, questionPos);
		}

		var currentUrlWithIndex = this.getUrlWithIndex(currentUrl);

		if (currentUrl.indexOf(url) === 0 || currentUrlWithIndex.indexOf(url) === 0)
		{
			return true;
		}

		questionPos = url.indexOf("?");
		if (questionPos === -1)
		{
			return false;
		}

		var refinedUrl = url.substring(0, questionPos);
		if (refinedUrl !== currentUrl && refinedUrl !== currentUrlWithIndex)
		{
			return false;
		}

		var success = true;
		var params = this.getUrlParams(url);
		var globals = this.getUrlParams(originalCurrentUrl);

		for (var varName in params)
		{
			if (!params.hasOwnProperty(varName))
			{
				continue;
			}

			var varValues = params[varName];
			var globalValues = typeof(globals[varName]) !== "undefined" ? globals[varName] : [];
			for (var i = 0; i < varValues.length; i++)
			{
				var varValue = varValues[i];
				if (!BX.util.in_array(varValue, globalValues))
				{
					success = false;
					break;
				}
			}
		}

		return success;
	};

	LeftMenuClass.prototype.getUrlParams = function(url)
	{
		var params = {};
		var questionPos = url.indexOf("?");
		if (questionPos === -1)
		{
			return params;
		}

		var tokens = url.substring(questionPos + 1).split("&");

		for (var i = 0; i < tokens.length; i++)
		{
			var token = tokens[i];
			var eqPos = token.indexOf("=");

			if (eqPos === 0)
			{
				continue;
			}

			var varName = eqPos === -1 ? token : token.substring(0, eqPos);
			varName = varName.replace("[]", "");
			var varValue = eqPos === -1 ? "" : token.substring(eqPos + 1);

			if (params[varName])
			{
				params[varName].push(varValue);
			}
			else
			{
				params[varName] = [varValue];
			}

		}

		return params;
	};

	LeftMenuClass.prototype.getUrlWithIndex = function(url)
	{
		if (!BX.type.isNotEmptyString(url))
		{
			url = "";
		}

		var questionPos = url.indexOf("?");
		var queryString = questionPos >= 0 ? "?" + url.substring(questionPos + 1) : "";
		var path = questionPos >= 0 ? url.substring(0, questionPos) : url;

		if (path.match(/\.php$/))
		{
			return url;
		}

		if (path.slice(-1) !== "/")
		{
			path += "/";
		}

		return path + "index.php" + queryString;
	};

	LeftMenuClass.prototype.checkCurrentPageInTopMenu = function()
	{
		var currentFullPath = document.location.pathname + document.location.search;

		if (BX.Main && BX.Main.interfaceButtonsManager)
		{
			var menuCollection = BX.Main.interfaceButtonsManager.getObjects();
			var menuIds = Object.keys(menuCollection);

			if (menuIds[0])
			{
				var menu = menuCollection[menuIds[0]];
				this.topItemSelectedObj = menu.getActive();

				if (typeof this.topItemSelectedObj === "object" && this.topItemSelectedObj)
				{
					if (this.topItemSelectedObj.hasOwnProperty("NODE"))
					{
						this.topMenuSelectedNode = this.topItemSelectedObj.NODE;
					}

					var link = document.createElement("a");
					link.href = this.topItemSelectedObj.URL;
					this.topItemSelectedObj.URL = BX.util.htmlspecialcharsback(link.pathname + link.search);
					this.topItemSelectedObj.TEXT = BX.util.htmlspecialcharsback(this.topItemSelectedObj.TEXT);

					this.isCurrentPageStandard = (this.topItemSelectedObj.URL == currentFullPath);
				}
			}
		}

		return this.isCurrentPageStandard;
	};

	LeftMenuClass.prototype.checkLinkInMenu = function(link)
	{
		if (!BX.type.isNotEmptyString(link))
			return;

		if (BX.type.isDomNode(this.menuItemsBlock))
		{
			var items = this.menuItemsBlock.getElementsByClassName("menu-item-block");
			for (var i=0; i<items.length; i++)
			{
				if (items[i].getAttribute("data-link") == link)
					return items[i];
			}
		}

		return false;
	};

	/**
	 *
	 * @returns {boolean}
	 */
	LeftMenuClass.prototype.initPagetitleStar = function()
	{
		this.checkCurrentPageInTopMenu();

		var starContNode = BX("pagetitle-star");
		if (!starContNode)
		{
			return false;
		}

		var currentFullPath = document.location.pathname + document.location.search;

		var currentPageInMenu = this.checkLinkInMenu(currentFullPath);
		if (typeof currentPageInMenu == "object")
		{
			BX.addClass(starContNode, "pagetitle-star-active");
		}
		starContNode.title = BX.message(BX.hasClass(starContNode, "pagetitle-star-active") ? "MENU_DELETE_PAGE_FROM_LEFT_MENU" : "MENU_ADD_PAGE_TO_LEFT_MENU");

		//default page
		if (typeof currentPageInMenu == "object" && currentPageInMenu.getAttribute("data-type") == "default")
		{
			starContNode.title = BX.message("MENU_STAR_TITLE_DEFAULT_PAGE");
			BX.bind(starContNode, "click", BX.proxy(function() {
				this.showMessage(BX.proxy_context, BX.message("MENU_STAR_TITLE_DEFAULT_PAGE_DELETE_ERROR"));
			}, this));

			return true;
		}

		//any page
		BX.bind(starContNode, "click", BX.proxy(function ()
		{
			if (BX.hasClass(starContNode, "pagetitle-star-active"))
			{
				this.deleteStandardItem();
			}
			else
			{
				this.addStandardItem();
			}
		}, this));

		return true;
	};

	LeftMenuClass.prototype.initPreset = function()
	{
		var container = BX("left-menu-preset-popup");

		if (!BX.type.isDomNode(container))
			return;

		this.presetItems = container.getElementsByClassName("js-left-menu-preset-item");
		if (typeof this.presetItems == "object")
		{
			for (var i=0; i<3; i++)
			{
				BX.bind(this.presetItems[i], "click", BX.proxy(function () {
					this.selectPreset(BX.proxy_context);
				}, this));
			}

		}
	};

	LeftMenuClass.prototype.selectPreset = function(selectedNode)
	{
		for (var i=0; i<3; i++)
		{
			BX.removeClass(this.presetItems[i], "left-menu-popup-selected");
		}

		if (BX.type.isDomNode(selectedNode))
		{
			BX.addClass(selectedNode, "left-menu-popup-selected");
		}
	};

	LeftMenuClass.prototype.showPresetPopupFunction = function(mode)
	{
		BX.ready(function ()
		{
			var button = null;
			BX.PopupWindowManager.create("menu-preset-popup", null, {
				closeIcon : false,
				offsetTop : 1,
				overlay : true,
				lightShadow : true,
				contentColor: "white",
				draggable : { restrict : true},
				closeByEsc : true,
				content : BX("left-menu-preset-popup"),
				buttons: [
					(button = new BX.PopupWindowButton({
						text : BX.message("MENU_CONFIRM_BUTTON"),
						className : "popup-window-button-create",
						events : {
							click : BX.proxy(function()
							{
								if (BX.hasClass(button.buttonNode, "popup-window-button-wait"))
								{
									return;
								}

								var form = document.forms["left-menu-preset-form"];
								BX.addClass(button.buttonNode, "popup-window-button-wait");

								BX.ajax({
									method: 'POST',
									dataType: 'json',
									url: this.ajaxPath,
									data: {
										sessid : BX.bitrix_sessid(),
										site_id : this.siteId,
										siteDir: this.siteDir,
										action: "set_preset",
										preset: form && form.elements["presetType"] ? form.elements["presetType"].value : "",
										mode: mode == "global" ? "global" : "personal"
									},
									onsuccess: BX.proxy(function(json)
									{
										if (json.hasOwnProperty("url"))
										{
											document.location.href = json.url;
										}
										else
										{
											document.location.reload();
										}
									}, this),
									onfailure: function ()
									{
										document.location.reload();
									}
								});
							}, this)}
					})),
					new BX.PopupWindowButton({
						text: BX.message('MENU_DELAY_BUTTON'),
						// className: "popup-window-button-link-cancel",
						events: { click : function()
						{
							this.popupWindow.close();
						}}
					})
				],
				events : {
					onPopupClose : function() {
						if(mode == 'global' && !!BX.Bitrix24 && !!BX.Bitrix24.renamePortal)
						{
							BX.Bitrix24.renamePortal.showNotify();
						}
					}
				}
			}).show();

			this.initPreset();
		}.bind(this));
	};

	return LeftMenuClass;

})();






/* End */
;
; /* Start:"a:4:{s:4:"full";s:95:"/bitrix/templates/bitrix24/components/bitrix/im.messenger/.default/script.min.js?15197274654531";s:6:"source";s:76:"/bitrix/templates/bitrix24/components/bitrix/im.messenger/.default/script.js";s:3:"min";s:80:"/bitrix/templates/bitrix24/components/bitrix/im.messenger/.default/script.min.js";s:3:"map";s:80:"/bitrix/templates/bitrix24/components/bitrix/im.messenger/.default/script.map.js";}"*/
function bxImBarInit(){BX.isImBarTransparent=false;BX.bind(window,"scroll",bxImBarRedraw);BX.bind(window,"resize",bxImBarRedraw);BX.addCustomEvent("onTopPanelCollapse",bxImBarRedraw);bxImBarRedraw();BX.bind(BX("bx-im-bar-notify"),"click",function(){if(typeof BXIM=="undefined")return false;BXIM.openNotify()});BX.bind(BX("bx-im-bar-search"),"click",function(){if(typeof BXIM=="undefined")return false;BXIM.openMessenger(0,"im")});BX.bind(BX("bx-im-bar-ol"),"click",function(){if(typeof BXIM=="undefined")return false;BXIM.openMessenger(0,"im-ol")});BX.bind(BX("bx-im-btn-call"),"click",function(e){if(typeof BXIM=="undefined")return false;BXIM.webrtc.openKeyPad(e)});BX.bind(window,"scroll",function(){if(typeof BXIM=="undefined"||!BXIM.messenger.popupPopupMenu)return true;if(BX.util.in_array(BXIM.messenger.popupPopupMenu.uniquePopupId.replace("bx-messenger-popup-",""),["createChat","contactList"])){BXIM.messenger.popupPopupMenu.close()}});BX.bindDelegate(BX("bx-im-external-recent-list"),"contextmenu",{className:"bx-messenger-cl-item"},function(e){if(typeof BXIM=="undefined")return false;BXIM.messenger.openPopupMenu(this,"contactList",false);return BX.PreventDefault(e)});BX.bindDelegate(BX("bx-im-external-recent-list"),"click",{className:"bx-messenger-cl-item"},function(e){if(typeof BXIM=="undefined")return false;BXIM.openMessenger(this.getAttribute("data-userId"));return BX.PreventDefault(e)});BX.addCustomEvent("onMessengerWindowBodyOverflow",function(e,n){var t=BX.findChildrenByClassName(BX("im-workarea-popup"),"bx-im-fullscreen-popup-td",true);for(var i=0;i<t.length;i++){var r=getComputedStyle(t[i],null);r=r?parseInt(r.getPropertyValue("padding-left").toString().replace("px","")):85;t[i].style.paddingRight=r+n+"px"}document.body.style.paddingRight=n+"px";BX("bx-im-bar").style.right=n+"px"});BX.addCustomEvent("onImUpdateCounterNotify",function(e){var n=BX.findChildByClassName(BX("bx-im-bar-notify"),"bx-im-informer-num");if(!n)return false;if(e>0){n.innerHTML='<div class="bx-im-informer-num-digit">'+(e>99?"99+":e)+"</div>"}else{n.innerHTML=""}});BX.addCustomEvent("onImUpdateCounterMessage",function(e,n){var t=null;if(n=="LINES"){t=BX("bx-im-bar-ol")}else{return false}var i=t&&BX.findChildByClassName(t,"bx-im-informer-num");if(!i)return false;if(e>0){i.innerHTML='<div class="bx-im-informer-num-digit">'+(e>99?"99+":e)+"</div>"}else{i.innerHTML=""}});BX.addCustomEvent("onPullOnlineEvent",BX.delegate(function(e,n){if(e=="user_online"){if(typeof BXIM.messenger.online=="undefined")return false;if(BXIM.messenger.online[n.USER_ID]!="Y"){BXIM.messenger.online[n.USER_ID]="Y";bxImBarRecount()}}else if(e=="user_offline"){if(typeof BXIM.messenger.online=="undefined")return false;if(BXIM.messenger.online[n.USER_ID]=="Y"){BXIM.messenger.online[n.USER_ID]="N";bxImBarRecount()}}else if(e=="online_list"){BXIM.messenger.online={};for(var t in n.USERS){BXIM.messenger.online[t]="Y"}}},this));BX.bind(BX("im-workarea-backgound-selector"),"change",function(){BX("im-workarea-backgound-selector-title").innerHTML=this.options[this.selectedIndex].text});BX.addCustomEvent("onMessengerWindowInit",function(){BX("im-workarea-backgound-selector-title").innerHTML=BX("im-workarea-backgound-selector").options[BX("im-workarea-backgound-selector").selectedIndex].text});BX.addCustomEvent("onImInit",function(e){e.notify.panelButtonCall=BX("bx-im-btn-call");e.notify.panelButtonCallAnlgePosition="bottom";e.notify.panelButtonCallAnlgeOffset=131;BX.MessengerCommon.recentListRedraw()})}function bxImBarRedraw(){var e=BX("bx-im-bar");if(!e){return}var n=window.pageYOffset||document.documentElement.scrollTop;var t=window.pageXOffset||document.documentElement.scrollLeft;var i=document.documentElement.scrollWidth-document.documentElement.clientWidth;var r=63;var o=BX("bx-panel");if(o){r=r+o.offsetHeight}var s=BX("creatorconfirmed");if(s){r=r+s.offsetHeight}if(n<=r){e.style.top=r-n+"px"}else if(n>r){if(e.style.top!="0px"){e.style.top=0}}if(i>19&&i-t>19){if(!BX.isImBarTransparent){BX.addClass(e,"bx-im-bar-transparent");BX.isImBarTransparent=true}}else{if(BX.isImBarTransparent){BX.removeClass(e,"bx-im-bar-transparent");BX.isImBarTransparent=false}}}function bxImBarRecount(){if(typeof BXIM.messenger.online=="undefined"||!BX("bx-im-online-count"))return false;var e=0;for(var n in BXIM.messenger.online){if(BXIM.messenger.online[n]=="Y"){e++}}e=e<=0?1:e;e=e>9999?9999:e;BX("bx-im-online-count").innerHTML=e;return true}function bxFullscreenClose(){BX.MessengerWindow.closePopup()}
/* End */
;
; /* Start:"a:4:{s:4:"full";s:58:"/bitrix/templates/bitrix24/bitrix24.min.js?151972746537574";s:6:"source";s:38:"/bitrix/templates/bitrix24/bitrix24.js";s:3:"min";s:42:"/bitrix/templates/bitrix24/bitrix24.min.js";s:3:"map";s:42:"/bitrix/templates/bitrix24/bitrix24.map.js";}"*/
(function(){var t=window!==window.top;var e=window.location.search;var n=e.indexOf("IFRAME=")!==-1||e.indexOf("IFRAME%3D")!==-1;if(t&&n){return}else if(t){window.top.location=window.location.href;return}BX.Bitrix24.PageSlider.bindAnchors({rules:[{condition:["/company/personal/user/(\\d+)/tasks/task/view/(\\d+)/","/workgroups/group/(\\d+)/tasks/task/view/(\\d+)/","/extranet/contacts/personal/user/(\\d+)/tasks/task/view/(\\d+)/"],loader:"task-view-loader",stopParameters:["PAGEN_(\\d+)","MID"]},{condition:["/company/personal/user/(\\d+)/tasks/task/edit/0/","/workgroups/group/(\\d+)/tasks/task/edit/0/","/extranet/contacts/personal/user/(\\d+)/tasks/task/edit/0/"],loader:"task-new-loader"},{condition:["/company/personal/user/(\\d+)/tasks/task/edit/(\\d+)/","/workgroups/group/(\\d+)/tasks/task/edit/(\\d+)/","/extranet/contacts/personal/user/(\\d+)/tasks/task/edit/(\\d+)/"],loader:"task-edit-loader"},{condition:[/\/online\/\?(IM_DIALOG|IM_HISTORY)=([a-zA-Z0-9_|]+)/i],handler:function(t,e){if(!window.BXIM){return}var n=e.matches[1];var i=e.matches[2];if(n==="IM_HISTORY"){BXIM.openHistory(i)}else{BXIM.openMessenger(i)}t.preventDefault()},validate:function(t){return!BX.type.isNotEmptyString(t.target)||t.target==="_blank"}}]});BX.addCustomEvent("onFrameDataRequestFail",function(t){top.location="/auth/?backurl="+B24.getBackUrl()});BX.addCustomEvent("onAjaxFailure",function(t){var e="/auth/?backurl="+B24.getBackUrl();if(t=="auth"&&typeof window.frameRequestStart!=="undefined"){top.location=e}});BX.addCustomEvent("onPopupWindowInit",function(t,e,n){if(t=="bx_log_filter_popup"){n.lightShadow=true;n.className=""}else if(t=="task-legend-popup"){n.lightShadow=true;n.offsetTop=-15;n.offsetLeft=-670;n.angle={offset:740}}else if(t=="task-gantt-filter"||t=="task-list-filter"){n.lightShadow=true;n.className=""}else if(t.indexOf("sonet_iframe_popup_")>-1){n.lightShadow=true}});BX.addCustomEvent("onJCClockInit",function(t){JCClock.setOptions({centerXInline:83,centerX:83,centerYInline:67,centerY:79,minuteLength:31,hourLength:26,popupHeight:229,inaccuracy:15,cancelCheckClick:true})});BX.addCustomEvent("onPullEvent-main",function(t,e){if(t=="user_counter"&&e[BX.message("SITE_ID")]){var n=BX.clone(e[BX.message("SITE_ID")]);B24.updateCounters(n)}});BX.addCustomEvent(window,"onImUpdateCounter",function(t){if(!t)return;B24.updateCounters(BX.clone(t))});BX.addCustomEvent("onCounterDecrement",function(t){var e=BX("menu-counter-live-feed",true);if(!e)return;t=parseInt(t);var n=parseInt(e.innerHTML);var i=n-t;if(i>0)e.innerHTML=i;else BX.removeClass(e.parentNode.parentNode.parentNode,"menu-item-with-index")});BX.addCustomEvent("onImUpdateCounterNotify",function(t){B24.updateInformer(BX("im-informer-events",true),t)});BX.addCustomEvent("onImUpdateCounterMessage",function(t){B24.updateInformer(BX("im-informer-messages",true),t);B24.updateCounters({"im-message":t})});BX.addCustomEvent("onImUpdateCounterNetwork",function(t){B24.updateInformer(BX("b24network-informer-events",true),t)});BX.addCustomEvent("onPullError",BX.delegate(function(t,e){if(t=="AUTHORIZE_ERROR"){B24.connectionStatus("offline")}else if(t=="RECONNECT"&&(e==1008||e==1006)){B24.connectionStatus("connecting")}},this));BX.addCustomEvent("onImError",BX.delegate(function(t,e){if(t=="AUTHORIZE_ERROR"||t=="SEND_ERROR"&&e=="AUTHORIZE_ERROR"){B24.connectionStatus("offline")}else if(t=="CONNECT_ERROR"){B24.connectionStatus("offline")}},this));BX.addCustomEvent("onPullStatus",BX.delegate(function(t){if(t=="offline")B24.connectionStatus("offline");else B24.connectionStatus("online")},this));BX.bind(window,"online",BX.delegate(function(){B24.connectionStatus("online")},this));BX.bind(window,"offline",BX.delegate(function(){B24.connectionStatus("offline")},this));if(BX.browser.SupportLocalStorage()){BX.addCustomEvent(window,"onLocalStorageSet",function(t){if(t.key.substring(0,4)=="lmc-"){var e={};e[t.key.substring(4)]=t.value;B24.updateCounters(e,false)}})}BX.ready(function(){BX.bind(window,"scroll",BX.throttle(B24.onScroll,150,B24))})})();var B24={b24ConnectionStatusState:"online",b24ConnectionStatus:null,b24ConnectionStatusText:null,b24ConnectionStatusTimeout:null,formateDate:function(t){return BX.util.str_pad(t.getHours(),2,"0","left")+":"+BX.util.str_pad(t.getMinutes(),2,"0","left")},openLanguagePopup:function(t){var e=JSON.parse(t.getAttribute("data-langs"));var n=[];for(var i in e){(function(t){n.push({text:e[t],className:"language-icon "+t,onclick:function(e,n){B24.changeLanguage(t)}})})(i)}BX.PopupMenu.show("language-popup",t,n,{offsetTop:10,offsetLeft:0})},changeLanguage:function(t){window.location.href="/auth/?user_lang="+t+"&backurl="+B24.getBackUrl()},getBackUrl:function(){var t=window.location.pathname;var e=B24.getQueryString(["logout","login","back_url_pub","user_lang"]);return t+(e.length>0?"?"+e:"")},getQueryString:function(t){var e=window.location.search.substring(1);if(!BX.type.isNotEmptyString(e)){return""}var n=e.split("&");t=BX.type.isArray(t)?t:[];var i="";for(var a=0;a<n.length;a++){var s=n[a].split("=");var o=n[a].indexOf("=");var r=s[0];var l=BX.type.isNotEmptyString(s[1])?s[1]:false;if(!BX.util.in_array(r,t)){if(i!==""){i+="&"}i+=r+(o!==-1?"=":"")+(l!==false?l:"")}}return i},updateInformer:function(t,e){if(!t)return false;if(e>0){t.innerHTML=e;BX.addClass(t,"header-informer-act")}else{t.innerHTML="";BX.removeClass(t,"header-informer-act")}},updateCounters:function(t,e){e=e==false?false:true;for(var n in t){if(window.B24menuItemsObj)window.B24menuItemsObj.allCounters[n]=t[n];if(n=="**"){oCounter={iCommentsMenuRead:0};BX.onCustomEvent(window,"onMenuUpdateCounter",[oCounter]);t[n]-=oCounter.iCommentsMenuRead}var i=BX(n=="**"?"menu-counter-live-feed":"menu-counter-"+n.toLowerCase(),true);if(i){if(t[n]>0){i.innerHTML=n=="mail_unseen"?t[n]>99?"99+":t[n]:t[n]>50?"50+":t[n];BX.addClass(i.parentNode.parentNode.parentNode,"menu-item-with-index")}else{BX.removeClass(i.parentNode.parentNode.parentNode,"menu-item-with-index");if(t[n]<0){var a=BX("menu-counter-warning-"+n.toLowerCase());if(a){a.style.display="inline-block"}}}if(e){BX.localStorage.set("lmc-"+n,t[n],5)}}}if(window.B24menuItemsObj){var s=0;for(var o=0,r=window.B24menuItemsObj.hiddenCounters.length;o<r;o++){if(window.B24menuItemsObj.allCounters[window.B24menuItemsObj.hiddenCounters[o]]){s+=+window.B24menuItemsObj.allCounters[window.B24menuItemsObj.hiddenCounters[o]]}}if(BX.type.isDomNode(BX("menu-hidden-counter"))){BX("menu-hidden-counter").style.display=s>0?"inline-block":"none";BX("menu-hidden-counter").innerHTML=s>50?"50+":s}}},showNotifyPopup:function(t){if(BX.hasClass(t,"header-informer-press")){BX.removeClass(t,"header-informer-press");BXIM.closeNotify()}else{BXIM.openNotify()}},showMessagePopup:function(t){if(typeof BXIM=="undefined")return false;BXIM.toggleMessenger()},closeBanner:function(t){BX.userOptions.save("bitrix24","banners",t,"Y");var e=BX("sidebar-banner-"+t);if(e){e.style.minHeight="auto";e.style.overflow="hidden";e.style.border="none";new BX.easing({duration:500,start:{height:e.offsetHeight,opacity:100},finish:{height:0,opacity:0},transition:BX.easing.makeEaseOut(BX.easing.transitions.quart),step:function(t){if(t.height>=0){e.style.height=t.height+"px";e.style.opacity=t.opacity/100}if(t.height<=17){e.style.marginBottom=t.height+"px"}},complete:function(){e.style.display="none"}}).animate()}},showLoading:function(t){t=t||500;function e(){var t=BX("b24-loader");if(t){BX.addClass(t,"b24-loader-show");return true}return false}setTimeout(function(){if(!e()&&!BX.isReady){BX.ready(e)}},t)}};B24.onScroll=function(){var t=BX.GetWindowScrollPos();if(B24.b24ConnectionStatus){if(B24.b24ConnectionStatus.getAttribute("data-float")=="true"){if(t.scrollTop<60){BX.removeClass(B24.b24ConnectionStatus,"bx24-connection-status-float");B24.b24ConnectionStatus.setAttribute("data-float","false")}}else{if(t.scrollTop>60){BX.addClass(B24.b24ConnectionStatus,"bx24-connection-status-float");B24.b24ConnectionStatus.setAttribute("data-float","true")}}}var e=BX("menu-favorites-settings-btn",true);if(!e){return}var n=e.offsetHeight?BX.pos(e).bottom:BX.GetWindowInnerSize().innerHeight/2;var i=BX("feed-up-btn-wrap",true);i.style.left="-"+t.scrollLeft+"px";if(t.scrollTop>n){B24.showUpButton(true,i)}else{B24.showUpButton(false,i)}};B24.showUpButton=function(t,e){if(!e)return;if(!!t)BX.addClass(e,"feed-up-btn-wrap-anim");else BX.removeClass(e,"feed-up-btn-wrap-anim")};B24.goUp=function(){var t=BX("feed-up-btn-wrap",true);if(t){t.style.display="none";BX.removeClass(t,"feed-up-btn-wrap-anim")}var e=BX.GetWindowScrollPos();new BX.easing({duration:500,start:{scroll:e.scrollTop},finish:{scroll:0},transition:BX.easing.makeEaseOut(BX.easing.transitions.quart),step:function(t){window.scrollTo(0,t.scroll)},complete:function(){if(t)t.style.display="block";BX.onCustomEvent(window,"onGoUp")}}).animate()};B24.SearchTitle=function(t){var e=this;this.arParams={AJAX_PAGE:t.AJAX_PAGE,CONTAINER_ID:t.CONTAINER_ID,INPUT_ID:t.INPUT_ID,MIN_QUERY_LEN:parseInt(t.MIN_QUERY_LEN),FORMAT:typeof t.FORMAT!="undefined"&&t.FORMAT=="json"?"json":"html",CATEGORIES_ALL:typeof t.CATEGORIES_ALL!="undefined"?t.CATEGORIES_ALL:[],USER_URL:typeof t.USER_URL!="undefined"?t.USER_URL:"",GROUP_URL:typeof t.GROUP_URL!="undefined"?t.GROUP_URL:"",WAITER_TEXT:typeof t.WAITER_TEXT!="undefined"?t.WAITER_TEXT:"",CURRENT_TS:parseInt(t.CURRENT_TS),SEARCH_PAGE:typeof t.SEARCH_PAGE!="undefined"?t.SEARCH_PAGE:""};if(t.MIN_QUERY_LEN<=0)t.MIN_QUERY_LEN=1;this.cache=[];this.cache_key=null;this.startText="";this.currentRow=-1;this.RESULT=null;this.CONTAINER=null;this.INPUT=null;this.xhr=null;this.searchStarted=false;this.ITEMS={obClientDb:null,obClientDbData:{},obClientDbDataSearchIndex:{},bMenuInitialized:false,initialized:{sonetgroups:false,menuitems:false},oDbUserSearchResult:{}};this.CreateResultWrap=function(){if(e.RESULT==null){this.RESULT=document.body.appendChild(document.createElement("DIV"));this.RESULT.className="title-search-result title-search-result-header"}};this.MakeResultFromClientDB=function(t,n){var i=null;var a,s,o,r,l=null;for(a=0;a<t.length;a++){searchString=t[a].toLowerCase();if(typeof e.ITEMS.oDbUserSearchResult[searchString]!="undefined"&&e.ITEMS.oDbUserSearchResult[searchString].length>0){for(s=0;s<e.ITEMS.oDbUserSearchResult[searchString].length;s++){r=e.ITEMS.oDbUserSearchResult[searchString][s];l=r.substr(0,1);for(o=0;o<e.arParams.CATEGORIES_ALL.length;o++){if(typeof e.arParams.CATEGORIES_ALL[o].CLIENTDB_PREFIX!="undefined"&&e.arParams.CATEGORIES_ALL[o].CLIENTDB_PREFIX==l){if(i==null){i={}}if(typeof i.CATEGORIES=="undefined"){i.CATEGORIES={}}if(typeof i.CATEGORIES[o]=="undefined"){i.CATEGORIES[o]={ITEMS:[],TITLE:e.arParams.CATEGORIES_ALL[o].TITLE}}if(l=="U"){i.CATEGORIES[o].ITEMS.push({ICON:typeof e.ITEMS.obClientDbData.users[r].avatar!="undefined"?e.ITEMS.obClientDbData.users[r].avatar:"",ITEM_ID:r,MODULE_ID:"",NAME:e.ITEMS.obClientDbData.users[r].name,PARAM1:"",URL:e.arParams.USER_URL.replace("#user_id#",e.ITEMS.obClientDbData.users[r].entityId),TYPE:"users"})}else if(l=="G"){if(typeof e.ITEMS.obClientDbData.sonetgroups[r].site!="undefined"&&e.ITEMS.obClientDbData.sonetgroups[r].site==BX.message("SITE_ID")){i.CATEGORIES[o].ITEMS.push({ICON:typeof e.ITEMS.obClientDbData.sonetgroups[r].avatar!="undefined"?e.ITEMS.obClientDbData.sonetgroups[r].avatar:"",ITEM_ID:r,MODULE_ID:"",NAME:e.ITEMS.obClientDbData.sonetgroups[r].name,PARAM1:"",URL:e.arParams.GROUP_URL.replace("#group_id#",e.ITEMS.obClientDbData.sonetgroups[r].entityId),TYPE:"sonetgroups",IS_MEMBER:typeof e.ITEMS.obClientDbData.sonetgroups[r].isMember!="undefined"&&e.ITEMS.obClientDbData.sonetgroups[r].isMember=="Y"?1:0})}}else if(l=="M"){i.CATEGORIES[o].ITEMS.push({ICON:"",ITEM_ID:r,MODULE_ID:"",NAME:e.ITEMS.obClientDbData.menuitems[r].name,PARAM1:"",URL:e.ITEMS.obClientDbData.menuitems[r].entityId})}break}}}}}if(i!==null){for(var u in i.CATEGORIES){if(i.CATEGORIES.hasOwnProperty(u)){i.CATEGORIES[u].ITEMS.sort(e.resultCmp)}}i.CATEGORIES["all"]={ITEMS:[{NAME:BX.message("BITRIX24_SEARCHTITLE_ALL"),URL:BX.util.add_url_param(e.arParams.SEARCH_PAGE,{q:n})}]}}return i};this.resultCmp=function(t,e){if(typeof t.TYPE!="undefined"&&typeof e.TYPE!="undefined"&&t.TYPE=="sonetgroups"&&e.TYPE=="sonetgroups"&&typeof t.IS_MEMBER!="undefined"&&typeof e.IS_MEMBER!="undefined"){if(t.IS_MEMBER==e.IS_MEMBER){if(t.NAME==e.NAME){return 0}return t.NAME<e.NAME?-1:1}return t.IS_MEMBER>e.IS_MEMBER?-1:1}else{if(t.NAME==e.NAME){return 0}return t.NAME<e.NAME?-1:1}};this.BuildResult=function(t,n){var i=null;var a=[];var s=currentItem=tdClassName=itemBlock=null;var o=0;if(typeof t.CATEGORIES!="undefined"){for(var r in t.CATEGORIES){if(t.CATEGORIES.hasOwnProperty(r)){s=t.CATEGORIES[r];a.push(BX.create("tr",{children:[BX.create("th",{props:{className:"title-search-separator"}}),BX.create("td",{props:{className:"title-search-separator"}})]}));if(typeof s.ITEMS!="undefined"){o=0;for(var l in s.ITEMS){if(s.ITEMS.hasOwnProperty(l)){if(o>=7){break}o++;currentItem=s.ITEMS[l];if(r==="all"){tdClassName="title-search-all"}else if(typeof currentItem.ICON!="undefined"){tdClassName="title-search-item"}else{tdClassName="title-search-more"}if(typeof currentItem.TYPE!="undefined"&&currentItem.TYPE.length>0){itemBlock=BX.create("a",{attrs:{href:currentItem.URL},children:[BX.create("span",{attrs:{style:typeof currentItem.ICON!="undefined"&&currentItem.ICON.length>0?"background-image: url('"+currentItem.ICON+"')":""},props:{className:"title-search-item-img title-search-item-img-"+currentItem.TYPE}}),BX.create("span",{props:{className:"title-search-item-text"},html:currentItem.NAME})]})}else{itemBlock=BX.create("a",{attrs:{href:currentItem.URL},html:currentItem.NAME})}a.push(BX.create("tr",{children:[BX.create("th",{html:l==0?s.TITLE:""}),BX.create("td",{props:{className:tdClassName},children:[itemBlock]})]}))}}}}}if(!!n){a.push(BX.create("tr",{children:[BX.create("th",{}),BX.create("td",{props:{className:"title-search-waiter"},children:[BX.create("span",{props:{className:"title-search-waiter-img"}}),BX.create("span",{props:{className:"title-search-waiter-text"},html:e.arParams.WAITER_TEXT})]})]}))}a.push(BX.create("tr",{children:[BX.create("th",{props:{className:"title-search-separator"}}),BX.create("td",{props:{className:"title-search-separator"}})]}));i=BX.create("table",{props:{className:"title-search-result"},children:[BX.create("colgroup",{children:[BX.create("col",{attrs:{width:"150px"}}),BX.create("col",{attrs:{width:"*"}})]}),BX.create("tbody",{children:a})]})}return i};this.ShowResult=function(t,n){e.CreateResultWrap();var i=0;var a=0;var s=0;if(BX.browser.IsIE()){i=0;a=1;s=-1;if(/MSIE 7/i.test(navigator.userAgent)){i=-1;a=-1;s=-2}}var o=BX.pos(e.CONTAINER);o.width=o.right-o.left;e.RESULT.style.position="absolute";e.RESULT.style.top=o.bottom+i-1+"px";e.RESULT.style.left=o.left+a+"px";e.RESULT.style.width=o.width+s+"px";if(t!=null){if(typeof e.arParams.FORMAT!="undefined"&&e.arParams.FORMAT=="json"){t=e.BuildResult(t,!!n);BX.cleanNode(e.RESULT);e.RESULT.appendChild(t)}else{e.RESULT.innerHTML=t}}else{e.RESULT.innerHTML=""}e.RESULT.style.display=e.RESULT.innerHTML.length>0?"block":"none"};this.SyncResult=function(t){var n=null;for(i=0;i<e.arParams.CATEGORIES_ALL.length;i++){if(typeof e.arParams.CATEGORIES_ALL[i].CODE!="undefined"&&typeof t.CATEGORIES[i]!="undefined"){if(e.arParams.CATEGORIES_ALL[i].CODE=="custom_menuitems"){n={};for(j=0;j<t.CATEGORIES[i].ITEMS.length;j++){n[t.CATEGORIES[i].ITEMS[j].ITEM_ID]=e.ConvertAjaxToClientDB(t.CATEGORIES[i].ITEMS[j],"menuitems")}BX.onCustomEvent(e,"onFinderAjaxSuccess",[n,e.ITEMS,"menuitems"])}else if(e.arParams.CATEGORIES_ALL[i].CODE=="custom_sonetgroups"){n={};for(j=0;j<t.CATEGORIES[i].ITEMS.length;j++){n[t.CATEGORIES[i].ITEMS[j].ITEM_ID]=e.ConvertAjaxToClientDB(t.CATEGORIES[i].ITEMS[j],"sonetgroups")}BX.onCustomEvent(e,"onFinderAjaxSuccess",[n,e.ITEMS,"sonetgroups"])}else if(e.arParams.CATEGORIES_ALL[i].CODE=="custom_users"){n={};for(j=0;j<t.CATEGORIES[i].ITEMS.length;j++){n[t.CATEGORIES[i].ITEMS[j].ITEM_ID]=e.ConvertAjaxToClientDB(t.CATEGORIES[i].ITEMS[j],"users")}BX.onCustomEvent(e,"onFinderAjaxSuccess",[n,e.ITEMS,"users"])}}}};this.ConvertAjaxToClientDB=function(t,e){var n=null;if(e=="sonetgroups"){n={id:"G"+t.ID,entityId:t.ID,name:t.NAME,avatar:t.ICON,desc:"",isExtranet:t.IS_EXTRANET?"Y":"N",site:t.SITE,checksum:t.CHECKSUM,isMember:typeof t.IS_MEMBER!="undefined"&&t.IS_MEMBER?"Y":"N"}}else if(e=="menuitems"){n={id:"M"+t.URL,entityId:t.URL,name:t.NAME,checksum:t.CHECKSUM}}else if(e=="users"){n={id:"U"+t.ID,entityId:t.ID,name:t.NAME,login:t.LOGIN,active:t.ACTIVE,avatar:t.ICON,desc:t.DESCRIPTION,isExtranet:"N",isEmail:"N",checksum:t.CHECKSUM}}return n};this.onKeyPress=function(t){e.CreateResultWrap();var n=BX.findChild(e.RESULT,{tag:"table","class":"title-search-result"},true);if(!n)return false;var i=n.rows.length,a=0;switch(t){case 27:e.RESULT.style.display="none";e.currentRow=-1;e.UnSelectAll();return true;case 40:if(e.RESULT.style.display=="none")e.RESULT.style.display="block";var s=-1;for(a=0;a<i;a++){if(!BX.findChild(n.rows[a],{"class":"title-search-separator"},true)&&!BX.findChild(n.rows[a],{"class":"title-search-waiter"},true)){if(s==-1)s=a;if(e.currentRow<a){e.currentRow=a;break}else{e.UnSelectItem(n,a)}}}if(a==i&&e.currentRow!=a)e.currentRow=s;e.SelectItem(n,e.currentRow);return true;case 38:if(e.RESULT.style.display=="none")e.RESULT.style.display="block";var o=-1;for(a=i-1;a>=0;a--){if(!BX.findChild(n.rows[a],{"class":"title-search-separator"},true)&&!BX.findChild(n.rows[a],{"class":"title-search-waiter"},true)){if(o==-1)o=a;if(e.currentRow>a){e.currentRow=a;break}else{e.UnSelectItem(n,a)}}}if(a<0&&e.currentRow!=a)e.currentRow=o;e.SelectItem(n,e.currentRow);return true;case 13:if(e.RESULT.style.display=="block"){for(a=0;a<i;a++){if(e.currentRow==a){if(!BX.findChild(n.rows[a],{"class":"title-search-separator"},true)){var r=BX.findChild(n.rows[a],{tag:"a"},true);if(r){window.location=r.href;return true}}}}}return false}return false};this.UnSelectAll=function(){var t=BX.findChild(e.RESULT,{tag:"table","class":"title-search-result"},true);if(t){var n=t.rows.length;for(var i=0;i<n;i++)t.rows[i].className=""}};this.SelectItem=function(t,e){t.rows[e].className="title-search-selected"};this.UnSelectItem=function(t,e){if(t.rows[e].className=="title-search-selected"){t.rows[e].className=""}};this.EnableMouseEvents=function(){var t=BX.findChild(e.RESULT,{tag:"table","class":"title-search-result"},true);if(t){var n=t.rows.length;if(n>0){e.currentRow=1;e.SelectItem(t,e.currentRow)}for(var i=0;i<n;i++){if(!BX.findChild(t.rows[i],{"class":"title-search-separator"},true)){t.rows[i].id="row_"+i;t.rows[i].onmouseover=function(n){if(e.currentRow!=this.id.substr(4)){e.UnSelectAll();e.currentRow=this.id.substr(4);e.SelectItem(t,e.currentRow)}};t.rows[i].onmouseout=function(t){this.className="";e.currentRow=-1}}}}};this.onFocusLost=function(t){if(e.RESULT!=null){setTimeout(function(){e.RESULT.style.display="none"},250)}};this.onFocusGain=function(){e.CreateResultWrap();if(e.RESULT&&e.RESULT.innerHTML.length){e.ShowResult()}BX.bind(e.INPUT,"keyup",e.onKeyUp);BX.bind(e.INPUT,"paste",e.onPaste)};this.onKeyUp=function(t){if(!e.searchStarted){return false}var n=BX.util.trim(e.INPUT.value);if(n==e.oldValue||n==e.oldClientValue||n==e.startText){return}if(e.xhr){e.xhr.abort()}if(n.length>=1){e.cache_key=e.arParams.INPUT_ID+"|"+n;if(e.cache[e.cache_key]==null){var i=[n];e.oldClientValue=n;var a={searchString:n};BX.onCustomEvent("findEntityByName",[e.ITEMS,a,{},e.ITEMS.oDbUserSearchResult]);if(a.searchString!=n){i.push(a.searchString)}var s=e.MakeResultFromClientDB(i,n);e.ShowResult(s,n.length>=e.arParams.MIN_QUERY_LEN);e.EnableMouseEvents();if(n.length>=e.arParams.MIN_QUERY_LEN){e.oldValue=n;e.SendAjax(n)}}else{e.ShowResult(e.cache[e.cache_key]);e.currentRow=-1;e.EnableMouseEvents()}}else{e.currentRow=-1;e.UnSelectAll()}};this.SendAjax=BX.debounce(function(t){e.xhr=BX.ajax({method:"POST",dataType:e.arParams.FORMAT,url:e.arParams.AJAX_PAGE,data:{ajax_call:"y",INPUT_ID:e.arParams.INPUT_ID,FORMAT:e.arParams.FORMAT,q:t},preparePost:true,onsuccess:function(t){if(typeof t!="undefined"){for(var n in t.CATEGORIES){if(t.CATEGORIES.hasOwnProperty(n)){t.CATEGORIES[n].ITEMS.sort(e.resultCmp)}}e.cache[e.cache_key]=t;e.ShowResult(t);e.SyncResult(t);e.currentRow=-1;e.EnableMouseEvents()}}})},1e3);this.onPaste=function(t){};this.onWindowResize=function(){if(e.RESULT!=null){e.ShowResult()}};this.onKeyDown=function(t){t=t||window.event;e.searchStarted=!(t.keyCode==27||t.keyCode==40||t.keyCode==38||t.keyCode==13);if(e.RESULT&&e.RESULT.style.display=="block"){if(e.onKeyPress(t.keyCode))return BX.PreventDefault(t)}};this.Init=function(){this.CONTAINER=BX(this.arParams.CONTAINER_ID);this.INPUT=BX(this.arParams.INPUT_ID);this.startText=this.oldValue=this.INPUT.value;BX.bind(this.INPUT,"focus",BX.proxy(this.onFocusGain,this));BX.bind(window,"resize",BX.proxy(this.onWindowResize,this));BX.bind(this.INPUT,"blur",BX.proxy(this.onFocusLost));this.INPUT.onkeydown=this.onKeyDown;BX.Finder(false,"searchTitle",[],{},e);BX.onCustomEvent(e,"initFinderDb",[this.ITEMS,"searchTitle",7,["users","sonetgroups","menuitems"],e]);setTimeout(function(){e.CheckOldStorage(e.ITEMS.obClientDbData)},5e3);if(!this.ITEMS.bLoadAllInitialized){BX.addCustomEvent("loadAllFinderDb",BX.delegate(function(t){this.ItemsLoadAll(t)},this));this.ITEMS.bLoadAllInitialized=true}};this.CheckOldStorage=function(t){if(!e.ITEMS.obClientDb){return}var n=null;var i=60*60*24*30;var a=null;for(var s in t){if(t.hasOwnProperty(s)){if(s=="sonetgroups"||s=="menuitems"){a=false;for(var o in t[s]){if(t[s].hasOwnProperty(o)){n=t[s][o];if(typeof n.timestamp!="undefined"&&parseInt(n.timestamp)>0&&e.arParams.CURRENT_TS>parseInt(n.timestamp)+i){a=true}break}}if(a){BX.Finder.clearEntityDb(e.ITEMS.obClientDb,s)}}}}};this.ItemsLoadAll=function(t){if(typeof t.entity!="undefined"&&typeof this.ITEMS.initialized[t.entity]!="undefined"&&!this.ITEMS.initialized[t.entity]&&typeof t.callback=="function"){if(t.entity=="sonetgroups"||t.entity=="menuitems"){BX.ajax({url:this.arParams.AJAX_PAGE,method:"POST",dataType:"json",data:{ajax_call:"y",sessid:BX.bitrix_sessid(),FORMAT:"json",q:"empty",get_all:t.entity},onsuccess:BX.delegate(function(e){if(typeof e.ALLENTITIES!="undefined"){BX.onCustomEvent("onFinderAjaxLoadAll",[e.ALLENTITIES,this.ITEMS,t.entity])}t.callback()},this),onfailure:function(t){}})}this.ITEMS.initialized[t.entity]=true}};BX.ready(function(){e.Init(t)})};B24.toggleMenu=function(t,e,n){var i=BX.findChild(t.parentNode,{tagName:"ul"},false,false);var a=BX.findChildren(i,{tagName:"li"},false);if(!a)return;var s=BX.findChild(t,{className:"menu-toggle-text"},true,false);if(!s)return;if(BX.hasClass(i,"menu-items-close")){i.style.height="0px";BX.removeClass(i,"menu-items-close");BX.removeClass(BX.nextSibling(BX.nextSibling(t)),"menu-items-close");i.style.opacity=0;o(true,i,i.scrollHeight);s.innerHTML=n;BX.userOptions.save("bitrix24",t.id,"hide","N")}else{o(false,i,i.offsetHeight);s.innerHTML=e;BX.userOptions.save("bitrix24",t.id,"hide","Y")}function o(e,n,i){n.style.overflow="hidden";new BX.easing({duration:200,start:{opacity:e?0:100,height:e?0:i},finish:{opacity:e?100:0,height:e?i:0},transition:BX.easing.transitions.linear,step:function(t){n.style.opacity=t.opacity/100;n.style.height=t.height+"px"},complete:function(){if(!e){BX.addClass(n,"menu-items-close");BX.addClass(BX.nextSibling(BX.nextSibling(t)),"menu-items-close")}n.style.cssText=""}}).animate()}};B24.licenseInfoPopup={licenseButtonText:"",trialButtonText:"",showFullDemoButton:"N",hostName:"",ajaxUrl:"",licenseUrl:"",demoUrl:"",featureGroupName:"",ajaxActionsUrl:"",init:function(t){if(typeof t=="object"){this.licenseButtonText=t.B24_LICENSE_BUTTON_TEXT||"";this.trialButtonText=t.B24_TRIAL_BUTTON_TEXT||"";this.showFullDemoButton=t.IS_FULL_DEMO_EXISTS=="Y"?"Y":"N";this.hostName=t.HOST_NAME;this.ajaxUrl=t.AJAX_URL;this.licenseUrl=t.LICENSE_ALL_PATH;this.demoUrl=t.LICENSE_DEMO_PATH;this.featureGroupName=t.FEATURE_GROUP_NAME||"";this.ajaxActionsUrl=t.AJAX_ACTIONS_URL||"";this.featureTrialSuccessText=t.B24_FEATURE_TRIAL_SUCCESS_TEXT||""}},show:function(t,e,n){if(!t)return;e=e||"";n=n||"";var i=[new BX.PopupWindowButton({text:this.licenseButtonText,className:"popup-window-button-create",events:{click:BX.proxy(function(){BX.ajax.post(this.ajaxUrl,{popupId:t,action:"tariff",host:this.hostName},BX.proxy(function(){document.location.href=this.licenseUrl},this))},this)}})];if(this.showFullDemoButton=="Y"){i.push(new BX.PopupWindowButtonLink({text:this.trialButtonText,className:"popup-window-button-link-cancel",events:{click:BX.proxy(function(){BX.ajax.post(this.ajaxUrl,{popupId:t,action:"demo",host:this.hostName},BX.proxy(function(){document.location.href=this.demoUrl},this))},this)}}))}else if(this.featureGroupName){i.push(new BX.PopupWindowButtonLink({text:this.trialButtonText,className:"popup-window-button-link-cancel",events:{click:BX.proxy(function(){BX.ajax({method:"POST",dataType:"json",url:this.ajaxActionsUrl,data:{action:"setFeatureTrial",sessid:BX.bitrix_sessid(),featureGroupName:this.featureGroupName},onsuccess:BX.proxy(function(t){if(t.error)var e=t.error;else if(t.success)e=this.featureTrialSuccessText;if(e){BX.PopupWindowManager.create("b24InfoPopupFeature",null,{content:BX.create("div",{html:e,attrs:{style:"padding:10px"}}),closeIcon:true}).show()}},this)});BX.ajax.post(this.ajaxUrl,{popupId:t,action:"demoFeature",host:this.hostName},function(){})},this)}}))}BX.PopupWindowManager.create("b24InfoPopup"+t,null,{content:BX.create("div",{props:{className:"hide-features-popup-wrap"},children:[BX.create("div",{props:{className:"hide-features-popup-title"},html:e}),BX.create("div",{props:{className:"hide-features-popup"},children:[BX.create("div",{props:{className:"hide-features-pic"},children:[BX.create("div",{props:{className:"hide-features-pic-round"}})]}),BX.create("div",{props:{className:"hide-features-text"},html:n})]})]}),closeIcon:true,lightShadow:true,offsetLeft:100,overlay:true,buttons:i,events:{onPopupClose:BX.proxy(function(){BX.ajax.post(this.ajaxUrl,{popupId:t,action:"close",host:this.hostName},function(){})},this)}}).show()}};function showPartnerForm(t){BX=window.BX;BX.Bitrix24PartnerForm={bInit:false,popup:null,arParams:{}};BX.Bitrix24PartnerForm.arParams=t;BX.message(t["MESS"]);BX.Bitrix24PartnerForm.popup=BX.PopupWindowManager.create("BXPartner",null,{autoHide:false,zIndex:0,offsetLeft:0,offsetTop:0,overlay:true,draggable:{restrict:true},closeByEsc:true,titleBar:BX.message("BX24_PARTNER_TITLE"),closeIcon:{right:"12px",top:"10px"},buttons:[new BX.PopupWindowButtonLink({text:BX.message("BX24_CLOSE_BUTTON"),className:"popup-window-button-link-cancel",events:{click:function(){this.popupWindow.close()}}})],content:'<div style="width:450px;height:230px"></div>',events:{onAfterPopupShow:function(){this.setContent('<div style="width:450px;height:230px">'+BX.message("BX24_LOADING")+"</div>");BX.ajax.post("/bitrix/tools/b24_site_partner.php",{lang:BX.message("LANGUAGE_ID"),site_id:BX.message("SITE_ID")||"",arParams:BX.Bitrix24PartnerForm.arParams},BX.delegate(function(t){this.setContent(t)},this))}}});BX.Bitrix24PartnerForm.popup.show()}B24.Timemanager={inited:false,layout:{block:null,timer:null,info:null,event:null,tasks:null,status:null},data:null,timer:null,clock:null,formatTime:function(t,e){return BX.util.str_pad(parseInt(t/3600),2,"0","left")+":"+BX.util.str_pad(parseInt(t%3600/60),2,"0","left")+(!!e?":"+BX.util.str_pad(t%60,2,"0","left"):"")},formatWorkTime:function(t,e,n){return'<span class="tm-popup-notice-time-hours"><span class="tm-popup-notice-time-number">'+t+'</span></span><span class="tm-popup-notice-time-minutes"><span class="tm-popup-notice-time-number">'+BX.util.str_pad(e,2,"0","left")+'</span></span><span class="tm-popup-notice-time-seconds"><span class="tm-popup-notice-time-number">'+BX.util.str_pad(n,2,"0","left")+"</span></span>"},formatCurrentTime:function(t,e,n){var i="";if(BX.isAmPmMode()){i="AM";if(t>12){t=t-12;i="PM"}else if(t==0){t=12;i="AM"}else if(t==12){i="PM"}i='<span class="time-am-pm">'+i+"</span>"}else t=BX.util.str_pad(t,2,"0","left");return'<span class="time-hours">'+t+"</span>"+'<span class="time-semicolon">:</span>'+'<span class="time-minutes">'+BX.util.str_pad(e,2,"0","left")+"</span>"+i},init:function(t){BX.addCustomEvent("onTimeManDataRecieved",BX.proxy(this.onDataRecieved,this));BX.addCustomEvent("onTimeManNeedRebuild",BX.proxy(this.onDataRecieved,this));BX.addCustomEvent("onPlannerDataRecieved",BX.proxy(this.onPlannerDataRecieved,this));BX.addCustomEvent("onPlannerQueryResult",BX.proxy(this.onPlannerQueryResult,this));BX.addCustomEvent("onTaskTimerChange",BX.proxy(this.onTaskTimerChange,this));BX.timer.registerFormat("worktime_notice_timeman",BX.proxy(this.formatWorkTime,this));BX.timer.registerFormat("bitrix24_time",BX.proxy(this.formatCurrentTime,this));BX.addCustomEvent(window,"onTimemanInit",BX.proxy(function(){this.inited=true;this.layout.block=BX("timeman-block");this.layout.timer=BX("timeman-timer");this.layout.info=BX("timeman-info");this.layout.event=BX("timeman-event");this.layout.tasks=BX("timeman-tasks");this.layout.status=BX("timeman-status");this.layout.statusBlock=BX("timeman-status-block");this.layout.taskTime=BX("timeman-task-time");this.layout.taskTimer=BX("timeman-task-timer");window.BXTIMEMAN.ShowFormWeekly(t);BX.bind(this.layout.block,"click",BX.proxy(this.onTimemanClick,this));BXTIMEMAN.setBindOptions({node:this.layout.block,mode:"popup",popupOptions:{angle:{position:"top",offset:130},offsetTop:10,autoHide:true,offsetLeft:-60,zIndex:-1,events:{onPopupClose:BX.proxy(function(){BX.removeClass(this.layout.block,"timeman-block-active")},this)}}});this.redraw()},this))},onTimemanClick:function(){BX.addClass(this.layout.block,"timeman-block-active");BXTIMEMAN.Open()},onTaskTimerChange:function(t){if(t.action==="refresh_daemon_event"){if(!!this.taskTimerSwitch){this.layout.taskTime.style.display="";if(this.layout.info.style.display!="none"){this.layout.statusBlock.style.display="none"}this.taskTimerSwitch=false}var e="";e+=this.formatTime(parseInt(t.data.TIMER.RUN_TIME||0)+parseInt(t.data.TASK.TIME_SPENT_IN_LOGS||0),true);if(!!t.data.TASK.TIME_ESTIMATE&&t.data.TASK.TIME_ESTIMATE>0){e+=" / "+this.formatTime(parseInt(t.data.TASK.TIME_ESTIMATE))}this.layout.taskTimer.innerHTML=e}else if(t.action==="start_timer"){this.taskTimerSwitch=true}else if(t.action==="stop_timer"){this.layout.taskTime.style.display="none";this.layout.statusBlock.style.display=""}},setTimer:function(){if(this.timer){this.timer.setFrom(new Date(this.data.INFO.DATE_START*1e3));this.timer.dt=-this.data.INFO.TIME_LEAKS*1e3}else{this.timer=BX.timer(this.layout.timer,{from:new Date(this.data.INFO.DATE_START*1e3),dt:-this.data.INFO.TIME_LEAKS*1e3,display:"simple"})}},stopTimer:function(){if(this.timer!=null){BX.timer.stop(this.timer);this.timer=null}},redraw_planner:function(t){if(!!t.TASKS_ENABLED){t.TASKS_COUNT=!t.TASKS_COUNT?0:t.TASKS_COUNT;this.layout.tasks.innerHTML=t.TASKS_COUNT;this.layout.tasks.style.display=t.TASKS_COUNT==0?"none":"inline-block"}if(!!t.CALENDAR_ENABLED){this.layout.event.innerHTML=t.EVENT_TIME;this.layout.event.style.display=t.EVENT_TIME==""?"none":"inline-block"}this.layout.info.style.display=BX.style(this.layout.tasks,"display")=="none"&&BX.style(this.layout.event,"display")=="none"?"none":"block"},redraw:function(){this.redraw_planner(this.data.PLANNER);if(this.data.STATE=="CLOSED"&&(this.data.CAN_OPEN=="REOPEN"||!this.data.CAN_OPEN))this.layout.status.innerHTML=this.getStatusName("COMPLETED");else this.layout.status.innerHTML=this.getStatusName(this.data.STATE);if(!this.timer)this.timer=BX.timer({container:this.layout.timer,display:"bitrix24_time"});var t="";if(this.data.STATE=="CLOSED"){if(this.data.CAN_OPEN=="REOPEN"||!this.data.CAN_OPEN)t="timeman-completed";else t="timeman-start"}else if(this.data.STATE=="PAUSED")t="timeman-paused";else if(this.data.STATE=="EXPIRED")t="timeman-expired";

BX.removeClass(this.layout.block,"timeman-completed timeman-start timeman-paused timeman-expired");BX.addClass(this.layout.block,t);if(t=="timeman-start"||t=="timeman-paused"){this.startAnimation()}else{this.endAnimation()}},getStatusName:function(t){return BX.message("TM_STATUS_"+t)},onDataRecieved:function(t){t.OPEN_NOW=false;this.data=t;if(this.inited)this.redraw()},onPlannerQueryResult:function(t,e){if(this.inited)this.redraw_planner(t)},onPlannerDataRecieved:function(t,e){if(this.inited)this.redraw_planner(e)},animation:null,animationTimeout:3e4,blinkAnimation:null,blinkLimit:10,blinkTimeout:750,startAnimation:function(){if(this.animation!==null){this.endAnimation()}this.startBlink();this.animation=setInterval(BX.proxy(this.startBlink,this),this.animationTimeout)},endAnimation:function(){this.endBlink();if(this.animation){clearInterval(this.animation)}this.animation=null},startBlink:function(){if(this.blinkAnimation!==null){this.endBlink()}var t=0;this.blinkAnimation=setInterval(BX.proxy(function(){if(++t>=this.blinkLimit){clearInterval(this.blinkAnimation);BX.show(BX("timeman-background",true))}else{BX.toggle(BX("timeman-background",true))}},this),this.blinkTimeout)},endBlink:function(){if(this.blinkAnimation){clearInterval(this.blinkAnimation)}BX("timeman-background",true).style.cssText="";this.blinkAnimation=null}};B24.Bitrix24InviteDialog={bInit:false,popup:null,arParams:{}};B24.Bitrix24InviteDialog.Init=function(t){if(t)B24.Bitrix24InviteDialog.arParams=t;if(B24.Bitrix24InviteDialog.bInit)return;BX.message(t["MESS"]);B24.Bitrix24InviteDialog.bInit=true;BX.ready(BX.delegate(function(){B24.Bitrix24InviteDialog.popup=BX.PopupWindowManager.create("B24InviteDialog",null,{autoHide:false,zIndex:0,offsetLeft:0,offsetTop:0,overlay:true,draggable:{restrict:true},closeByEsc:true,titleBar:BX.message("BX24_INVITE_TITLE_INVITE"),contentColor:"white",contentNoPaddings:true,closeIcon:{right:"12px",top:"10px"},buttons:[],content:'<div style="width:500px;height:550px; background: url(/bitrix/templates/bitrix24/images/loader.gif) no-repeat center;"></div>',events:{onAfterPopupShow:function(){this.setContent('<div style="width:500px;height:550px; background: url(/bitrix/templates/bitrix24/images/loader.gif) no-repeat center;"></div>');BX.ajax.post("/bitrix/tools/intranet_invite_dialog.php",{lang:BX.message("LANGUAGE_ID"),site_id:BX.message("SITE_ID")||"",arParams:B24.Bitrix24InviteDialog.arParams},BX.delegate(function(t){this.setContent(t)},this))},onPopupClose:function(){BX.InviteDialog.onInviteDialogClose()}}})},this))};B24.Bitrix24InviteDialog.ShowForm=function(t){B24.Bitrix24InviteDialog.Init(t);B24.Bitrix24InviteDialog.popup.params.zIndex=BX.WindowManager?BX.WindowManager.GetZIndex():0;B24.Bitrix24InviteDialog.popup.show()};B24.Bitrix24InviteDialog.ReInvite=function(t){BX.ajax.post("/bitrix/tools/intranet_invite_dialog.php",{lang:BX.message("LANGUAGE_ID"),site_id:BX.message("SITE_ID")||"",reinvite:t,sessid:BX.bitrix_sessid()},BX.delegate(function(t){},this))};B24.connectionStatus=function(t){if(!(t=="online"||t=="connecting"||t=="offline"))return false;if(this.b24ConnectionStatusState==t)return false;this.b24ConnectionStatusState=t;var e="";if(t=="offline"){b24ConnectionStatusStateText=BX.message("BITRIX24_CS_OFFLINE");e="bx24-connection-status-offline"}else if(t=="connecting"){b24ConnectionStatusStateText=BX.message("BITRIX24_CS_CONNECTING");e="bx24-connection-status-connecting"}else if(t=="online"){b24ConnectionStatusStateText=BX.message("BITRIX24_CS_ONLINE");e="bx24-connection-status-online"}clearTimeout(this.b24ConnectionStatusTimeout);var n=document.querySelector('[data-role="b24-connection-status"]');if(!n){var i=BX.GetWindowScrollPos();var a=i.scrollTop>60;this.b24ConnectionStatus=BX.create("div",{attrs:{className:"bx24-connection-status "+(this.b24ConnectionStatusState=="online"?"bx24-connection-status-hide":"bx24-connection-status-show bx24-connection-status-"+this.b24ConnectionStatusState)+(a?" bx24-connection-status-float":""),"data-role":"b24-connection-status","data-float":a?"true":"false"},children:[BX.create("div",{props:{className:"bx24-connection-status-wrap"},children:[this.b24ConnectionStatusText=BX.create("span",{props:{className:"bx24-connection-status-text"},html:b24ConnectionStatusStateText}),BX.create("span",{props:{className:"bx24-connection-status-text-reload"},children:[BX.create("span",{props:{className:"bx24-connection-status-text-reload-title"},html:BX.message("BITRIX24_CS_RELOAD")}),BX.create("span",{props:{className:"bx24-connection-status-text-reload-hotkey"},html:BX.browser.IsMac()?"&#8984;+R":"Ctrl+R"})],events:{click:function(){location.reload()}}})]})]})}else{this.b24ConnectionStatus=n}if(!this.b24ConnectionStatus)return false;if(t=="online"){clearTimeout(this.b24ConnectionStatusTimeout);this.b24ConnectionStatusTimeout=setTimeout(BX.delegate(function(){BX.removeClass(this.b24ConnectionStatus,"bx24-connection-status-show");this.b24ConnectionStatusTimeout=setTimeout(BX.delegate(function(){BX.removeClass(this.b24ConnectionStatus,"bx24-connection-status-hide")},this),1e3)},this),4e3)}this.b24ConnectionStatus.className="bx24-connection-status bx24-connection-status-show "+e+" "+(this.b24ConnectionStatus.getAttribute("data-float")=="true"?"bx24-connection-status-float":"");this.b24ConnectionStatusText.innerHTML=b24ConnectionStatusStateText;if(!n){var s=BX.findChild(document.body,{className:"bx-layout-inner-table"},true,false);s.parentNode.insertBefore(this.b24ConnectionStatus,s)}return true};
/* End */
;; /* /bitrix/templates/bitrix24/slider/slider.min.js?151972746511806*/
; /* /bitrix/components/bitrix/tasks.iframe.popup/templates/.default/logic.min.js?15197274767131*/
; /* /bitrix/components/bitrix/search.title/script.min.js?15197274746110*/
; /* /bitrix/templates/bitrix24/components/bitrix/system.auth.form/.default/script.min.js?1519727464323*/
; /* /bitrix/templates/bitrix24/components/bitrix/menu/left_vertical/map.min.js?151972746411235*/
; /* /bitrix/templates/bitrix24/components/bitrix/menu/left_vertical/script.js?151972746564997*/
; /* /bitrix/templates/bitrix24/components/bitrix/im.messenger/.default/script.min.js?15197274654531*/
; /* /bitrix/templates/bitrix24/bitrix24.min.js?151972746537574*/

//# sourceMappingURL=template_bx24.map.js