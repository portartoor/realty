BX.ready(function(){

	BX.isImBarTransparent = false;
	BX.bind(window, 'scroll', bxImBarRedraw);
	BX.bind(window, 'resize', bxImBarRedraw);
	BX.addCustomEvent("onTopPanelCollapse", bxImBarRedraw);

	bxImBarRedraw();

	BX.bind(BX("bx-im-bar-notify"), "click", function(){
		if (typeof(BXIM) == 'undefined') return false;

		BX.desktopUtils.runningCheck(function() {
			BX.desktopUtils.goToBx("bx://notify");
		},function() {
			BXIM.openNotify();
		}, true);
	});
	BX.bind(BX("bx-im-online-count-btn"), "click", function(){
		if (typeof(BXIM) == 'undefined') return false;

		BX.desktopUtils.runningCheck(function() {
			BX.desktopUtils.goToBx("bx://messenger");
		},function() {
			BXIM.toggleMessenger();
		}, true);
	});
	BX.bind(BX("bx-im-bar-add"), "click", function(){
		if (typeof(BXIM) == 'undefined') return false;

		BX.desktopUtils.runningCheck(function() {
			BXIM.messenger.openPopupMenu(BX("bx-im-bar-add"), 'createChat', null, {'offsetTop': -44, 'offsetLeft': -160, 'anglePosition': 'right', 'openDesktop': true});
		},function() {
			BXIM.messenger.openPopupMenu(BX("bx-im-bar-add"), 'createChat', null, {'offsetTop': -44, 'offsetLeft': -160, 'anglePosition': 'right', 'openMessenger': true});
		}, true);

	});
	BX.bind(BX("bx-im-bar-search"), "click", function(){
		if (typeof(BXIM) == 'undefined') return false;

		BX.desktopUtils.runningCheck(function() {
			BX.desktopUtils.goToBx("bx://messenger/dialog/0");
		},function() {
			BXIM.openMessenger(0);
		}, true);
	});
	BX.bind(BX("bx-im-btn-call"), "click", function(e){
		if (typeof(BXIM) == 'undefined') return false;
		BXIM.webrtc.openKeyPad(e);
	});
	BX.bind(window, "scroll", function(){
		if (typeof(BXIM) == 'undefined' || !BXIM.messenger.popupPopupMenu) return true;
		if (BX.util.in_array(BXIM.messenger.popupPopupMenu.uniquePopupId.replace('bx-messenger-popup-',''), ["createChat", "contactList"]))
		{
			BXIM.messenger.popupPopupMenu.close();
		}
	});
	BX.bindDelegate(BX("bx-im-external-recent-list"), "contextmenu", {className: 'bx-messenger-cl-item'}, function(e) {
		if (typeof(BXIM) == 'undefined') return false;

		var currentElement = this;
		BX.desktopUtils.runningCheck(function() {
			BX.desktopUtils.goToBx("bx://messenger/dialog/"+currentElement.getAttribute('data-userId'))
		},function() {
			BXIM.messenger.openPopupMenu(currentElement, 'contactList', false);
		}, true);
		return BX.PreventDefault(e);
	});

	BX.bindDelegate(BX("bx-im-external-recent-list"), "click", {className: 'bx-messenger-cl-item'}, function(e){
		if (typeof(BXIM) == 'undefined') return false;

		var currentElement = this;
		BX.desktopUtils.runningCheck(function() {
			BX.desktopUtils.goToBx("bx://messenger/dialog/"+currentElement.getAttribute('data-userId'))
		},function() {
			BXIM.openMessenger(currentElement.getAttribute('data-userId'))
		}, true);
	});

	BX.addCustomEvent("onImUpdateCounterNotify", function(counter) {
		var notifyCounter = BX.findChildByClassName(BX("bx-im-bar-notify"), "bx-im-informer-num");
		if (!notifyCounter)
			return false;

		if (counter > 0)
		{
			notifyCounter.innerHTML = '<div class="bx-im-informer-num-digit">'+(counter > 99? "99+": counter)+'</div>';
		}
		else
		{
			notifyCounter.innerHTML = "";
		}
	});

	BX.addCustomEvent("onPullOnlineEvent", BX.delegate(function(command,params)
	{
		if (command == 'user_online')
		{
			if (typeof(BXIM.messenger.online) == 'undefined')
				return false;

			if (BXIM.messenger.online[params.USER_ID] != 'Y')
			{
				BXIM.messenger.online[params.USER_ID] = 'Y';
				bxImBarRecount();
			}
		}
		else if (command == 'user_offline')
		{
			if (typeof(BXIM.messenger.online) == 'undefined')
				return false;

			if (BXIM.messenger.online[params.USER_ID] == 'Y')
			{
				BXIM.messenger.online[params.USER_ID] = 'N';
				bxImBarRecount();
			}
		}
		else if (command == 'online_list')
		{
			BXIM.messenger.online = {};
			for (var i in params.USERS)
			{
				BXIM.messenger.online[i] = 'Y';
			}
			bxImBarRecount();
		}
	}, this));

	BX.addCustomEvent("onImInit", function(initObj) {
		initObj.notify.panelButtonCall = BX("bx-im-btn-call");
		initObj.notify.panelButtonCallOffsetTop = -100;
		initObj.notify.panelButtonCallOffsetLeft = -240;
		initObj.notify.panelButtonCallAnlgePosition = "right";
		initObj.notify.panelButtonCallAnlgeOffset = 221;
		BX.MessengerCommon.recentListRedraw();
	});
});

function bxImBarRecount()
{
	if (typeof(BXIM.messenger.online) == 'undefined')
		return false;

	var count = 0;
	for (var i in BXIM.messenger.online)
	{
		if (BXIM.messenger.online[i] == 'Y')
		{
			count++;
		}
	}
	count = count <= 0? 1: count;
	count = count > 9999? 9999: count;

	BX('bx-im-online-count').innerHTML = count;

	return true;
}