;(function()
{
	BX.namespace('BX.rest');

	if(!!BX.rest.PlacementMenu)
	{
		return;
	}

	BX.rest.PlacementMenu = function(param)
	{
		BX.rest.PlacementMenu.superclass.constructor.apply(this, arguments);

		this.applicationPopup = null;
		this.applicationPopupContent = null;
	};

	BX.extend(BX.rest.PlacementMenu, BX.rest.Placement);

	BX.rest.PlacementMenu.prototype.getAppNode = function(appId)
	{
		return this.applicationPopupContent;
	};

	BX.rest.PlacementMenu.prototype.showApp = function(appId)
	{
		if(this.applicationPopup)
		{
			this.applicationPopup.close();
			this.applicationPopup.destroy();
			this.applicationPopup = null;
		}

		this.applicationPopup = new BX.PopupWindow(
			'rest_placement_' + this.param.placement + '_' + appId,
			null,
			{
				closeByEsc: false,
				closeIcon: true,
				titleBar: BX.message('JS_CORE_LOADING'),
				events: {
					onPopupClose: BX.delegate(function()
					{
						this.unload(appId);
					}, this)
				},
				overlay: {opacity: 50}
			}
		);

		this.applicationPopupContent = BX.create('DIV', {
			props: {
				className: 'app-expand-popup'
			},
			html: '<div class="app-loading-popup"></div>'
		});

		this.applicationPopup.setContent(this.applicationPopupContent);
		this.applicationPopup.show();

		BX.rest.PlacementMenu.superclass.showApp.apply(this, arguments);
	};

	BX.rest.PlacementMenu.prototype.appLoaded = function(appId)
	{
		BX.defer(function()
		{
			var layout = BX.rest.AppLayout.get(this.param.placement);
			layout.expandPopup = this.applicationPopup;
			layout.expandPopupContent = this.applicationPopupContent;

			BX.bind(window, 'resize', BX.proxy(layout.adjustPopup, layout));
			layout.adjustPopup();
		}, this)();

		BX.rest.PlacementMenu.superclass.appLoaded.apply(this, arguments);
	};

	BX.rest.PlacementMenu.prototype.unload = function(appId)
	{
		BX.defer(this.applicationPopup.destroy, this.applicationPopup)();
		BX.rest.PlacementMenu.superclass.unload.apply(this, arguments);
	};

})();