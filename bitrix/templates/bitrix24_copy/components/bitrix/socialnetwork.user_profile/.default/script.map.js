{"version":3,"file":"script.min.js","sources":["script.js"],"names":["BX","namespace","Socialnetwork","User","Profile","arParams","this","ajaxPath","siteId","languageId","otpDays","showOtpPopup","otpRecoveryCodes","profileUrl","passwordsUrl","popupHint","codesUrl","init","prototype","buttons","push","PopupWindowButton","text","message","className","events","click","proxy","location","href","PopupWindowButtonLink","PopupWindowManager","create","autoHide","offsetLeft","offsetTop","overlay","draggable","restrict","closeByEsc","content","show","ready","delegate","initHint","confirm","changeUserActivity","userId","userActive","parseInt","ajax","post","user_id","active","sessid","bitrix_sessid","site_id","result","window","reload","DeleteErrorPopup","showExtranet2IntranetForm","email","Bitrix24Extranet2IntranetForm","zIndex","titleBar","html","closeIcon","right","top","popup","form","submit","popupWindow","setContent","close","onAfterPopupShow","lang","USER_ID","IS_EMAIL","reinvite","isExtranet","bindObj","InviteAccessPopup","deactivateUserOtp","numDays","method","dataType","url","data","action","onsuccess","json","error","deferUserOtp","activateUserOtp","showOtpDaysPopup","bind","handler","self","daysObj","i","onclick","event","item","PopupMenu","showLink","btn","wrapper","parentNode","input","querySelector","link","inpWidth","linkWidth","style","width","addClass","offsetWidth","display","setTimeout","opacity","select","nodeId","node","setAttribute","id","proxy_context","getAttribute","showHint","hideHint","PopupWindow","lightShadow","darkMode","bindOptions","position","onPopupClose","destroy","attrs","setAngle","offset"],"mappings":"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"}