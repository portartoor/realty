{"version":3,"file":"interface_grid.min.js","sources":["interface_grid.js"],"names":["BX","CrmInterfaceGridManager","this","_id","_settings","_messages","_enableIterativeDeletion","_toolbarMenu","_applyButtonClickHandler","delegate","_handleFormApplyButtonClick","_setFilterFieldsHandler","_onSetFilterFields","_getFilterFieldsHandler","_onGetFilterFields","_deletionProcessDialog","prototype","initialize","id","settings","_makeBindings","ready","_bindOnGridReload","addCustomEvent","window","_onToolbarMenuShow","_onToolbarMenuClose","_onGridColumnCheck","getSetting","_onGridRowDelete","sender","eventArgs","GetMenuByItemId","gridId","type","isNotEmptyString","getGridId","defer","openDeletionDialog","ids","processAll","getGridJsObject","settingsMenu","SaveColumns","getId","reinitialize","onCustomEvent","form","getForm","unbind","bind","_bindOnSetFilterFields","grid","removeCustomEvent","registerFilter","filter","fields","infos","isArray","isSettingsContext","name","indexOf","count","length","element","paramName","i","info","toUpperCase","params","data","_setElementByFilter","search","elementId","isElementNode","value","_setFilterByElement","defaultval","getMessage","hasOwnProperty","getOwnerType","document","forms","getGrid","getAllRowsCheckBox","getEditor","editorId","CrmActivityEditor","items","reload","isFunction","Reload","getServiceUrl","getListServiceUrl","_loadCommunications","commType","callback","ajax","url","method","dataType","ACTION","COMMUNICATION_TYPE","ENTITY_TYPE","ENTITY_IDS","GRID_ID","onsuccess","onfailure","_onEmailDataLoaded","entityType","comms","item","push","entityTitle","entityId","addEmail","_onCallDataLoaded","addCall","_onMeetingDataLoaded","addMeeting","_onDeletionProcessStateChange","getState","CrmLongRunningProcessState","completed","close","e","selected","elements","allRowsCheckBox","checked","checkboxes","findChildren","tagName","attribute","checkbox","PreventDefault","contextId","util","getRandomString","processParams","CONTEXT_ID","ENTITY_TYPE_NAME","USER_FILTER_HASH","CrmLongRunningProcessDialog","create","serviceUrl","action","title","summary","show","start","editor","namespace","Crm","Activity","Planner","showEdit","TYPE_ID","CrmActivityType","call","OWNER_TYPE","OWNER_ID","meeting","addTask","viewActivity","optopns","self","managerId","showPopup","anchor","PopupMenu","offsetTop","offsetLeft","reloadGrid","applyFilter","filterName","ApplyFilter","clearFilter","ClearFilter","menus","createMenu","menuId","zIndex","parseInt","menu","isNaN","showMenu","ShowMenu","expandEllipsis","ellepsis","isDomNode","cut","findNextSibling","class","removeClass","addClass","style","display","CrmUIGridMenuCommand","undefined","createEvent","createActivity","remove","CrmUIFilterEntitySelector","_fieldId","_control","_entitySelector","onCustomEntitySelectorOpen","onCustomEntitySelectorClose","getSearchInput","getLabelNode","control","fieldId","closeSiblings","open","onSelect","labels","values","typeName","l","setData","join","JSON","stringify","CrmEntitySelector","entityTypeNames","isMultiple","setPopupContainer","getPopup","siblings","k","_entityTypeNames","_isMultiple","_entityInfos","_entitySelectHandler","onEntitySelect","msg","messages","isOpened","obCrm","popup","PopupWindow","isShown","entityTypes","toLowerCase","CRM","contact","CrmEntityType","getCaptionByName","names","company","invoice","quote","lead","deal","ok","cancel","message","wait","noresult","last","Init","AddOnSaveListener","Open","closeIcon","top","right","closeByEsc","autoHide","gainFocus","titleBar","RemoveOnSaveListener","Clear","entityInfos","isPlainObject","key","entityInfo","desc","image","entityTypeName","closeAll","CrmUIGridExtension","_rowCountLoader","_loaderData","initializeRowCountLoader","onGridReload","onGridDataRequest","onApplyCounterFilter","getActivityServiceUrl","getTaskCreateUrl","getOwnerTypeName","gridInfo","Main","gridManager","getById","getActivityEditor","getCheckBoxValue","controlId","getControl","getPanelControl","prepareAction","CrmUserSearchPopup","deletePopup","searchInput","dataInput","componentName","user","processMenuCommand","command","closeActionsMenu","isNumber","createCustomEvent","activityTypeId","activitySettings","pathToRemove","dlg","CDialog","head","content","resizable","draggable","height","width","ClearButtons","SetButtons","WindowManager","Get","Close","btnCancel","Show","processActionChange","actionName","checkBox","disabled","processApplyButtonClick","forAll","selectedIds","getRows","getSelectedIds","openTaskCreateForm","loadCommunications","createEmailFor","createCallList","sendSelected","CrmCallListHelper","entityIds","response","SUCCESS","ERRORS","error","alert","DATA","RESTRICTION","B24","licenseInfoPopup","HEADER","CONTENT","callListId","ID","BXIM","startCallList","PROVIDER_ID","PROVIDER_TYPE_ID","ASSOCIATED_ENTITY_ID","updateCallList","context","addToCallList","content_url","add_url_param","FORM_TYPE","ENTITY_ID","communications","email","typeId","planner","task","keys","prepareEntityKey","replace","mergeRequestParams","target","source","prefix","button","wrapper","CrmHtmlLoader","loader","release","setTimeout","setFilter","ACTIVITY_COUNTER","filterManager","api","getApi","setFields","apply","extensionId","activityId","options","contextMenus","createContextMenu","showContextMenu"],"mappings":"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"}