{"version":3,"file":"script.min.js","sources":["script.js"],"names":["CrmWebFormList","params","this","init","context","BX","canEdit","isFramePopup","nodeHead","querySelector","nodeList","headHideClass","formAttribute","formAttributeIsSystem","forms","viewUserOptionName","detailPageUrlTemplate","actionRequestUrl","mess","viewList","actionList","formNodeList","querySelectorAll","i","length","initItemByNode","item","hideDescBtnNode","bind","addClass","userOptions","delay","save","initSlider","node","buttonId","getAttribute","isSystem","initForm","caller","id","initSliderButtons","onBeforeDeleteForm","form","list","filter","onAfterDeleteForm","index","util","array_search","onRevertDeleteForm","removeClass","CrmWebFormListItem","push","slider","condition","replace","loader","bindOpen","convert","nodeListToArray","forEach","Bitrix24","PageSlider","bindAnchors","rules","stopParameters","element","openHref","e","preventDefault","open","url","reloadAfterClosing","addCustomEvent","iframe","contentWindow","window","location","reload","CrmTiledViewListItemCopier","manager","source","copiedNode","shadowNode","prototype","draw","finishHeight","offsetHeight","cloneNode","style","height","opacity","prepareNode","activeController","CrmWebFormListItemActiveDateController","deactivate","contains","insertBefore","firstChild","startLoadAnimation","easing","duration","start","finish","transition","transitions","quint","step","proxy","state","animate","erase","startHeight","complete","remove","getTitleNode","setAttribute","titleNode","innerText","title","linkNodes","linkNode","href","detailUrl","stopLoadAnimation","position","document","createElement","nodeDelete","nodeCopyToClipboard","nodeCopyToClipboardButton","nodeSettings","nodeViewSettings","nodeView","nodeBtnGetScript","isActiveControlLocked","popupSettings","popupViewSettings","bindControls","showViewSettings","items","currentViewType","getCurrentViewType","code","hasOwnProperty","view","text","className","onclick","onClickViewSettingsItem","createPopup","menuItem","getMenuItem","layout","popupWindow","show","showSettings","popupItem","link","redirectToDetailPage","close","copy","resetCounters","offsetLeft","offsetTop","event","closePopup","changeViewType","firstViewId","viewId","hasClass","viewInfoNode","isAdd","changeClass","showErrorPopup","data","errorAction","popup","PopupWindowManager","create","autoHide","lightShadow","closeByEsc","overlay","backgroundColor","setButtons","PopupWindowButton","dlgBtnClose","events","click","setContent","showConfirmPopup","confirmAction","dlgBtnApply","action","apply","dlgBtnCancel","changeActive","doNotSend","needDeactivate","isActive","activate","sendActionRequest","error","revert","limited","B24","licenseInfoPopup","dlgActiveCountLimitedTitle","dlgActiveCountLimitedText","getDetailPageById","isCopied","copier","copiedId","copiedName","delete","deleteConfirmation","deleteClassName","callbackSuccess","callbackFailure","ajax","method","button_id","sessid","bitrix_sessid","timeout","dataType","processData","onsuccess","onfailure","showScriptPopup","createScriptPopup","scriptPopup","crmCopyScriptContainer","innerHTML","html","popupContentNode","titleBar","dlgGetScriptTitle","content","contentColor","closeIcon","buttons","clipboard","isCopySupported","copyToClipBoardBtn","dlgBtnCopyToClipboard","bindCopyClick","buttonNode","nodeActiveControl","nodeButton","styleDisplay","isShow","displayValue","display","popupId","button","PopupMenu","angle","offset","onPopupClose","delegate","nodeDate","classDateNow","classDateNowState","classOn","classOff","classBtnOn","classBtnOff","classViewInactive","isNowShowedCounter","isRevert","toggle","actualizeButton","actualizeDate","force","forceDeactivate","isNow"],"mappings":"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"}