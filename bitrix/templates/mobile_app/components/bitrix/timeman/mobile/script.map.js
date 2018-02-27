{"version":3,"file":"script.min.js","sources":["script.js"],"names":["BX","TMPoint","message","intervals","OPENED","CLOSED","EXPIRED","START","SITE_ID","_worktime_timeman","h","m","s","substring","length","_initPage","menuItems","title","type","isNotEmptyString","isArray","menu","window","BXMobileApp","UI","Menu","items","Page","TopBar","setCallback","delegate","show","setText","initTimestamp","d","node","container","format","this","click","callback","bind","addCustomEvent","proxy","value","parseInt","innerHTML","date","clone","visible","offset","init","prototype","inner","e","eventCancelBubble","PreventDefault","res","start_date","getStrDate","DatePicker","setParams","data","makeTimestamp","onCustomEvent","str","timestamp","timeR","RegExp","test","exec","Date","util","str_pad_left","getHours","toString","getMinutes","formats","DATETIME_FORMAT","convertBitrixFormat","DATE_FORMAT","TIME_FORMAT","substr","trim","indexOf","bitrix","replace","getTime","getTimezoneOffset","initTimePeriod","editable","p","location","ready","app","pullDown","enable","pulltext","downtext","loadtext","action","reload","getCurrentLocation","onsuccess","l","id","timer","registerFormat","query","bForce","bitrix_sessid","coords","latitude","longitude","ajax","prepareData","query_data","method","dataType","url","hidePopupLoader","onfailure","strip_tags","lsId","lsTimeout","lsForce","showPopupLoader","baseObj","obj","DATA","valueOf","Math","round","random","ERROR","FREE_MODE","onUpdate","onPull","onMobileTimeManDailyReportHasBeenChanged","REPORT","REPORT_TS","destroy","status","inited","buttons","nodes","main","setData","collectNodes","check","removeCustomEvent","unbind","ii","j","hasOwnProperty","isPlainObject","findChild","attribute","data-bx-timeman","f","set","pauseTimer","INFO","TIME_LEAKS","stateTimer","checkActions","getMenu","setAttribute","checkQuery","alert","text","checkData","report","command","timeManager","superclass","constructor","apply","arguments","extend","start","resume","pause","stop","edit","workingTimeTimer","stopTimestamp","stopReason","previousSibling","time","startStateTimers","stopStateTimers","startPauseTimers","stopPauseTimers","showStopForm","showStartForm","push","name","icon","getAttribute","i","from","DATE_START","dt","display","setFrom","DATE_FINISH","accuracy","selectedTimestamp","errorReport","focus","request_id","q","PageManager","loadPageModal","bx24ModernStyle","cache","timeManagerEdit","onChange","save","startTimestamp","finishTimestamp","pauseTimestamp","durationTimestamp","editReason","onFocus","onBlur","unbindAll","isFunction","checkF","removeClass","addClass","onFocusInterval","setInterval","clearInterval","finishTime","getSeconds","isNaN","originalValue","closeModalDialog","_callback","timeManagerReport","updateButtons","cancel","bar_type","position","ok","entry_id","ID","report_ts","MTimeMan","div","params","MTimeManEdit","MTimeManReport"],"mappings":"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"}