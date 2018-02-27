{"version":3,"file":"visit.min.js","sources":["visit.js"],"names":["configCamera","AJAX_URL","BX","message","COMMUNICATIONS_AJAX_URL","bitrix_sessid","crmSelectorLoaded","consentGiven","recognizeConsentGiven","callbacks","onVisitCreated","nop","CrmActivityVisit","config","self","this","type","isPlainObject","buttons","createLead","createContact","selectEntity","addDeal","addInvoice","owner","id","createdDeals","createdInvoices","Math","round","random","ajaxUrl","recorder","mainNode","popup","hasFaceId","hasConsent","HAS_CONSENT","hasRecognizeConsent","HAS_RECOGNIZE_CONSENT","faceIdInstalled","FACEID_INSTALLED","hasPhoto","faceId","communicationSearch","faceSearch","recordLength","timestamp","Date","getTime","timerInterval","externalRequests","_externalEventHandler","_onExternalEvent","bind","_selectEntityHandler","_onSelectEntity","entityType","OWNER_TYPE","entityId","OWNER_ID","failed","vkProfile","vkProfileChanged","init","create","prototype","addCustomEvent","window","getId","setId","getMainNode","setMainNode","getPopup","setPopup","getNode","name","scope","querySelector","showEdit","params","ajax_action","_checkConsent","_checkRecognizeConsent","_createAjaxPopup","value","linksContainer","removeClass","dealId","parseInt","push","addEventListener","_onCreateButtonClick","_onSelectorButtonClick","_onAddButtonClick","recorderNode","finishButton","_onFinishButtonClick","CrmRecorder","isSupported","element","_onRecorderDeviceFailure","_onRecorderDeviceReady","start","setInterval","oldTimestamp","newTimestamp","difference","_updateTimer","addClass","faceidNode","FaceSearch","visitView","onSelect","_onFaceSelected","onReset","_onFaceReset","onSocialProfileSelected","_onFaceSocialProfileSelected","style","minWidth","profileNode","isNotEmptyString","setVkProfile","setTimeout","adjustPosition","saveActivity","next","finishLoader","clearInterval","stop","record","formData","FormData","append","getImageBlob","savePhoto","ajax","method","dataType","url","data","preparePost","onsuccess","response","onprogress","p","dispose","removeCustomEvent","obCrm","visitCrmSelector","RemoveOnSaveListener","content","PopupWindow","titleBar","closeIcon","closeByEsc","events","onPopupClose","destroy","PopupWindowButton","text","className","click","userOptions","save","close","PopupWindowButtonLink","show","max-width","html","_saveRecognizeConsent","sessid","wrapper","min-width","min-height","background-color","noAllPaddings","zIndex","draggable","restrict","overlay","backgroundColor","opacity","_onPopupClose","HTML","innerHTML","setOwner","entity","_reloadOwnerCard","selectorContainer","__renderSavePhoto","e","target","dataset","context","open","key","entityTypeName","isCanceled","isBoolean","entityInfo","role","util","add_url_param","contact_id","company_id","contact","company","runCallback","requestData","ENTITY_TYPE","ENTITY_ID","cardContainer","lengthElement","minutes","floor","toString","seconds","length","String","concat","innerText","_openCrmSelector","Open","AddOnSaveListener","found","lead","hasOwnProperty","substr","profile","error","timerNode","errorNode","setCallback","eventName","callback","isFunction","event","CommunicationSearch","node","communicationType","CrmCommunicationType","undefined","entityTitle","callBacks","setEntity","CrmCommunicationSearch","messages","SearchTab","NoData","selectNode","inputNode","openDialog","PreventDefault","_communicationSearch","serviceUrl","selectCallback","delegate","selectCommunication","enableSearch","enableDataLoading","dialogAutoHide","_communicationSearchController","titleNode","CrmCommunicationSearchController","onCloseDialog","defer","focus","result","getSettings","closeDialog","mediaStream","defaulCamera","__getDefaultCamera","state","cameraList","imageBlob","elements","loader","social","picture","settings","seachSocial","settingsMenu","socialSelector","__bindEvents","hide","setImageBlob","__getMediaStream","changeCamera","cameraId","__setDefaultCamera","stopMediaStream","__onSearchButtonClick","__onSettingsButtonClick","__onSearchSocialButtonClick","__onSavePhotoButtonClick","navigator","mediaDevices","getUserMedia","__getConstraints","then","stream","videoNode","volume","src","URL","createObjectURL","play","__getCameraList","catch","console","log","audio","video","browser","IsChrome","mandatory","sourceId","deviceId","exact","enumerateDevices","deviceList","forEach","deviceInfo","kind","label","localStorage","getItem","setItem","pictureContainer","videoContainer","createPicture","__setState","__showLoader","searchFace","__hideLoader","ERRORS","SUCCESS","DATA","ENTITY_TITLE","FACE_ID","alert","menuItems","popupWindow","cameraInfo","menuItem","onclick","PopupMenu","autoHide","offsetTop","offsetLeft","offsetWidth","angle","position","onPopupDestroy","SocialSelector","__onSocialProfileSelected","onDispose","__onSocialSelectorClosed","newState","pictureButton","canvas","getContext","width","videoWidth","height","videoHeight","drawImage","toBlob","profileContainer","profileLink","searchButton","htmlspecialchars","href","_bindEvents","selectButtons","document","querySelectorAll","i","item","_onSelectButtonClick","OnPopupClose","OnPopupDestroy","startSearch","MediaStream","getTracks","track"],"mappings":"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"}