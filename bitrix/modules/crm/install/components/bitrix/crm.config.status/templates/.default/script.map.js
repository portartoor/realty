{"version":3,"file":"script.min.js","sources":["script.js"],"names":["BX","CrmConfigStatusClass","parameters","this","randomString","tabs","ajaxUrl","data","oldData","clone","max_sort","requestIsRunning","totalNumberFields","checkSubmit","defaultColor","defaultFinalSuccessColor","defaultFinalUnSuccessColor","defaultLineColor","textColorLight","textColorDark","entityId","hasSemantics","jsClass","contentIdPrefix","contentClass","contentActiveClass","fieldNameIdPrefix","fieldEditNameIdPrefix","fieldHiddenNameIdPrefix","spanStoringNameIdPrefix","mainDivStorageFieldIdPrefix","fieldSortHiddenIdPrefix","fieldHiddenNumberIdPrefix","extraStorageFieldIdPrefix","finalSuccessStorageFieldIdPrefix","finalStorageFieldIdPrefix","previouslyScaleIdPrefix","previouslyScaleNumberIdPrefix","previouslyScaleFinalSuccessIdPrefix","previouslyScaleNumberFinalSuccessIdPrefix","previouslyScaleFinalUnSuccessIdPrefix","previouslyScaleNumberFinalUnSuccessIdPrefix","previouslyScaleFinalCellIdPrefix","previouslyScaleNumberFinalCellIdPrefix","funnelSuccessIdPrefix","funnelUnSuccessIdPrefix","successFields","unSuccessFields","initialFields","extraFields","finalFields","extraFinalFields","dataFunnel","colorFunnel","initAmChart","footer","windowSize","scrollPosition","contentPosition","footerPosition","limit","footerFixed","blockFixed","initAmCharts","showError","init","prototype","selectTab","tabId","div","className","i","cnt","length","content","showTab","value","processingFooter","AmCharts","handleLoad","on","sel","statusReset","document","forms","submit","recoveryName","fieldId","name","fieldHiddenNumber","searchElement","fieldName","fieldHiddenName","innerHTML","util","htmlspecialchars","NAME","recalculateSort","editField","domElement","fieldDiv","spanStoring","create","props","children","id","attrs","type","onkeydown","onblur","data-onblur","style","width","setAttribute","appendChild","fieldEditName","focus","selectionStart","openPopupBeforeDeleteField","isNaN","parseInt","deleteField","message","isNotEmptyString","html","modalWindow","modalId","title","overlay","events","onPopupClose","destroy","onAfterPopupShow","popup","findChild","contentContainer","cursor","bind","proxy","_startDrag","buttons","text","click","delegate","e","PopupWindowManager","getCurrentPopup","close","parentNode","fieldHidden","removeChild","params","bindElement","autoHide","closeIcon","right","top","Math","random","withoutContentWrap","contentClassName","contentStyle","withoutWindowManager","contentDialogChildren","push","concat","hasOwnProperty","contentDialog","onPopupShow","firstButtonInModalWindow","_keyPress","proxy_context","closePopup","unbind","_keypress","windowsWithoutManager","PopupWindow","closeByEsc","zIndex","show","saveFieldValue","input","newFieldName","newFieldValue","NAME_INIT","tag","element","findChildren","attribute","addField","color","addCellFinalScale","addCellMainScale","k","ID","SORT","ENTITY_ID","COLOR","insertBefore","createStructureHtml","parentId","structureFields","data-calculate","number","sort","getAttribute","replace","inputFields","fieldSortHidden","data-success","j","changeCellScale","scale","data-scale-type","scaleNumber","scaleFinalSuccess","scaleNumberFinalSuccess","mainCount","scaleCount","deleteCellMainScale","background","getElementsByTagName","scaleFinalUnSuccess","scaleNumberFinalUnSuccess","finalCount","scaleFinalUnSuccessCount","l","deleteCellFinalScale","h","scaleHtml","scaleNumberHtml","quantity","scaleCell","scaleCellNumber","fieldObject","iconClass","blockClass","img","onclick","ondblclick","data-sort","data-space","data-class","data-status-id","getNewStatusId","newStatusId","listInputStatusId","statusId","showPlaceToInsert","replaceableElement","parentElement","spaceId","spaceToInsert","data-place","coords","getCoords","displacementHeight","pageY","middleElement","offsetHeight","deleteSpaceToInsert","insertAfter","putDomElement","beforeElement","spacetoinsert","node","referenceNode","parent","nextSibling","checkChanges","newTotalNumberFields","changes","newSort","oldSort","newName","toLowerCase","oldName","newColor","oldColor","confirmSubmit","fixStatuses","ajax","url","method","dataType","ACTION","onsuccess","window","location","reload","onfailure","correctionColorPicker","event","blockColorPicker","left","pageX","paintElement","objColorPicker","pWnd","fields","span","phasePanel","result","ICON_CLASS","BLOCK_CLASS","hiddenInputColor","isReady","renderAmCharts","ready","charts","getDataForAmCharts","chart","makeChart","theme","titleField","valueField","dataProvider","colors","labelsEnabled","marginRight","marginLeft","labelPosition","funnelAlpha","startX","neckWidth","startAlpha","depth3D","angle","outlineAlpha","outlineColor","outlineThickness","neckHeight","balloonText","export","enabled","chartId","success","error","getParameterByName","regex","RegExp","results","exec","search","decodeURIComponent","addCustomEvent","state","removeClass","addClass","onCustomEvent","GetWindowInnerSize","GetWindowScrollPos","pos","scrollBottom","scrollTop","innerHeight","bottom","height","padding","fixFooter","fixButton","userOptions","save","semanticEntityTypes","entityTypeId","types","isArray"],"mappings":"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"}