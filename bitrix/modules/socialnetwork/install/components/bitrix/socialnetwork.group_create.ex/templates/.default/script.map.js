{"version":3,"file":"script.min.js","sources":["script.js"],"names":["BXOnInviteListChange","window","arInvitationUsersList","arguments","BX","onCustomEvent","util","array_values","BXSwitchExtranet","isChecked","style","display","checked","disabled","addClass","removeClass","selected","BXSwitchNotVisible","BXDeleteImage","visibility","value","tmpNode","findChild","tagName","attr","name","BXGCESwitchTabs","tabs","findChildren","className","blockList","bind","event","target","srcElement","blockOld","blockNew","i","length","posOld","pos","tabsContainer","height","overflow","hasClass","parentNode","id","posNew","easing","start","finish","duration","transition","makeEaseOut","transitions","quart","step","state","this","complete","animate","BXGCESwitchFeatures","servBlock","servList","inputList","toggleClass","BXGCESubmitForm","e","textarea","message","BXGCE","lastAction","actionURL","action","disableSubmitButton","ajax","submitAjax","document","forms","sonet_group_create_popup_form","url","method","dataType","onsuccess","obResponsedata","undefined","showError","SocNetLogDestination","obItems","type","isArray","selectedUsersOld","selectedUsers","strUserCodeTmp","j","arUserSelector","getAttribute","deleteItem","obItemsSelected","reInit","top","location","href","reload","onfailure","PreventDefault","onCancelClick","__addExtranetEmail","inputMail","emailPattern","test","BXExtranetMailList","background","setTimeout","backgroundColor","link","create","props","children","events","click","__deleteExtranetEmail","appendChild","push","browser","IsIE","focus","item","flag","isDomNode","removeChild","num","parseInt","substring","BXGCEEmailKeyDown","keyCode","userSelector","setSelector","selectorName","disableBackspace","backspaceDisable","unbind","selectCallback","search","bUndeleted","data-id","attrs","html","mouseover","mouseout","BXfpSetLinkName","formName","tagInputName","tagLink1","tagLink2","openDialogCallback","PopupWindow","setOptions","popupZindex","BXfpOpenDialogCallback","apply","bindActionLink","oBlock","PopupMenu","destroy","arItems","text","onclick","onActionSelect","arParams","offsetLeft","offsetTop","zIndex","lightShadow","angle","position","offset","onPopupShow","ob","show","innerHTML","errorText","showMessage","bDisable","oButton","cursor"],"mappings":"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"}