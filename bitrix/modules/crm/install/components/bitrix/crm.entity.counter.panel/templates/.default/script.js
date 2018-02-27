if(typeof(BX.CrmEntityCounterPanel) === "undefined")
{
	BX.CrmEntityCounterPanel = function()
	{
		this._id = "";
		this._settings = {};
		this._container = null;
		this._itemClickHandler = BX.delegate(this.onItemClick, this);
	};

	BX.CrmEntityCounterPanel.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = BX.type.isNotEmptyString(id) ? id : BX.util.getRandomString(4);
				this._settings = settings ? settings : {};

				this._container = BX(this.getSetting("containerId", ""));
				if(!BX.type.isElementNode(this._container))
				{
					throw "BX.CrmEntityCounterPanel: Could not find container.";
				}

				var itemNodes = this._container.querySelectorAll("a.crm-counter-container");
				for(var i = 0, l = itemNodes.length; i < l; i++)
				{
					BX.bind(itemNodes[i], "click", this._itemClickHandler);
				}
			},
			getId: function()
			{
				return this._id;
			},
			getSetting: function (name, defaultval)
			{
				return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
			},
			onItemClick: function(e)
			{
				var itemNode = BX.findParent(BX.getEventTarget(e), { tagName: "A", className: "crm-counter-container" });
				if(itemNode)
				{
					var typeId = itemNode.getAttribute("data-type-id");
					if(BX.type.isNotEmptyString(typeId))
					{
						var eventArgs = { counterTypeId: typeId, cancel: false };
						BX.onCustomEvent(window, "BX.CrmEntityCounterPanel:applyFilter", [this, eventArgs]);
						if(eventArgs.cancel)
						{
							return BX.PreventDefault(e);
						}
					}
				}
				return true;
			}
		};

	BX.CrmEntityCounterPanel.create = function(id, settings)
	{
		var self = new BX.CrmEntityCounterPanel();
		self.initialize(id, settings);
		return self;
	};
}
