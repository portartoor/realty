{"version":3,"file":"logic.min.js","sources":["logic.js"],"names":["BX","namespace","Tasks","Component","TasksWidgetRights","TasksWidgetAccess","extend","sys","code","methods","construct","this","callConstruct","firstLevel","each","option","level","vars","getManager","subInstance","constructor","ItemManager","scope","preRendered","data","parent","Util","ItemSet","options","controlBind","useSmartCodeNaming","bindItemActions","callMethod","bindOnItemEx","onItemOperationClick","bind","item","node","menu","optionP","push","enabled","text","TITLE","onclick","passCtx","doMenuAction","itemRef","levelId","ID","levelTitle","PopupMenu","show","id","value","angle","position","offsetLeft","offsetTop","e","menuItem","popupWindow","close","setItemOperation","TASK_ID","control","innerHTML","extractItemValue","VALUE","getRandomHash","prepareData","first","setField","util","htmlspecialcharsback","nameFormatted","nameTemplate","formatted","formatName","LOGIN","login","ITEM_SET_INVISIBLE","openAddForm","getSelector","open","selector","Integration","Socialnetwork","NetworkSelector","mode","query","getQuery","useSearch","useAdd","popupOffsetTop","popupOffsetLeft","lastSelectedContext","bindEvent","onSelectorItemSelected","MEMBER_ID","addItem","deselectItem","call"],"mappings":"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"}