{"version":3, "file":"kernel_rest.js", "sections": [{"offset": { "line": 3, "column": 0 }, "map": {"version":3,"file":"/bitrix/js/rest/applayout.min.js","sources":["/bitrix/js/rest/applayout.js"],"names":["BX","namespace","rest","AppLayout","params","this","firstRun","appHost","appProto","authId","authExpires","refreshId","placement","formName","frameName","loaderName","layoutName","ajaxUrl","controlUrl","isAdmin","staticHtml","id","appId","appV","appI","appSid","memberId","restPath","proto","userOptions","appOptions","placementOptions","userSelectorControl","userSelectorControlCallback","bAccessLoaded","_appOptionsStack","expandPopup","expandPopupContent","_inited","_destroyed","deniedInterface","selectUserCallback_1_value","messageInterface","initializePlacement","bind","window","proxy","receiveMessage","prototype","init","loader","addClass","removeClass","setTimeout","remove","src","document","forms","action","submit","destroy","unbind","parentNode","removeChild","e","event","origin","cmd","split","data","args","JSON","parse","util","in_array","cb","_cb","DoNothing","delegate","res","f","contentWindow","postMessage","stringify","apply","denyInterface","deniedList","array_merge","sendAppOptions","length","stack","opts","i","push","name","value","sessid","bitrix_sessid","options","ajax","loadJSON","loadControl","control","method","url","processScriptsConsecutive","onsuccess","reInstall","setInstallFinish","selectUserCallback_0","v","array_values","defer","close","selectUserCallback_1","hideUpdate","version","save","adjustPopup","isShown","node","wnd","GetWindowInnerSize","style","height","innerHeight","width","innerWidth","adjustPosition","initizalizePlacementInterface","parent","extend","events","clone","superclass","toUpperCase","placementInterface","MessageInterface","MessageInterfacePlacement","initializePlacementByEvent","addCustomEvent","PlacementInterface","hasOwnProperty","getInitData","LANG","message","DOMAIN","location","host","PROTOCOL","PATH","AUTH_ID","AUTH_EXPIRES","REFRESH_ID","MEMBER_ID","FIRST_RUN","IS_ADMIN","INSTALL","USER_OPTIONS","APP_OPTIONS","PLACEMENT","PLACEMENT_OPTIONS","getInterface","result","command","refreshAuth","alert","resizeWindow","parseInt","p","pos","setTitle","setTitleBar","title","UpdatePageTitle","setScroll","scroll","scrollTo","GetWindowScrollPos","scrollLeft","setUserOption","setAppOption","setInstall","h","href","replace","indexOf","selectUser","mult","show","onchange","Math","random","site_id","PopupWindowManager","create","autoHide","content","setButtons","PopupWindowButton","text","className","click","display","selectAccess","Access","Init","groups","disabled","startValue","SetSelected","ShowForm","callback","arRights","provider","selectCRM","loaded","entityType","multiple","obCrm","Open","AddOnSaveListener","Clear","reloadWindow","reload","imCallTo","BXIM","callTo","userId","video","imPhoneTo","phoneTo","phone","imOpenMessenger","openMessenger","dialogId","imOpenHistory","openHistory","openApplication","PopupWindow","closeByEsc","closeIcon","titleBar","onPopupClose","get","overlay","opacity","props","html","setContent","post","popup","param","parentsid","innerHTML","closeApplication","placementBindEvent","arguments","removeCustomEvent","layoutList","placementList","set","sid","getPlacement","setPlacement","ob","initialize","layout","s","ss","r","slice","join"],"mappings":"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"}}]}