if(typeof(BX.CrmChannelTracker) === "undefined")
{
	BX.CrmChannelTracker = function()
	{
		this._id = "";
		this._settings = {};
		this._config = {};
		this._container = null;
		this._bodyContainer = null;
		this._toggleButton = null;
		this._helpButton = null;
		this._helpPopup = null;
		this._groups = null;
		this._toggle = null;
		this._isExpanded = true;
	};

	BX.CrmChannelTracker.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			this._config = this.getSetting("config", {});
			this._isExpanded = this._config["expanded"] === "Y";

			this._container = BX(this.getSetting("containerId", ""));
			if(!BX.type.isElementNode(this._container))
			{
				throw "BX.CrmChannelTracker. Could not find container.";
			}

			this._bodyContainer = this._container.querySelector(".startpage-table-data-body");
			if(!BX.type.isElementNode(this._bodyContainer))
			{
				throw "BX.CrmChannelTracker. Could not find body container.";
			}

			this._toggleButton = BX(this.getSetting("toggleButtonId", ""));
			if(BX.type.isElementNode(this._toggleButton))
			{
				BX.bind(this._toggleButton, "click", BX.delegate(this.onToggleButtonClick, this));
			}

			this._helpButton = BX(this.getSetting("helpButtonId", ""));
			if(BX.type.isElementNode(this._helpButton))
			{
				BX.bind(this._helpButton, "click", BX.delegate(this.onHelpButtonClick, this));
			}

			this._serviceUrl = this.getSetting("serviceUrl", "");

			this._groups = {};
			var groupContainers = this._container.querySelectorAll("[data-group]");
			for(var i = 0, l = groupContainers.length; i < l; i++)
			{
				var groupContainer = groupContainers[i];
				var groupId = groupContainer.getAttribute("data-group");
				if(BX.type.isNotEmptyString(groupId))
				{
					this._groups[groupId] = BX.CrmChannelTrackerGroup.create(
						groupId,
						{ "parent": this, "container": groupContainer }
					);
				}
			}

			this._toggle = BX.CrmChannelTrackerToggle.create({ target: this });

			this.ajustFontSize(this._container.querySelectorAll(".startpage-table-header-block-count-wrapper"));
			BX.bind(window, "resize", BX.delegate(this.onWidowResize, this));
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getMessage: function(name)
		{
			var msg = BX.CrmChannelTracker.messages;
			return msg.hasOwnProperty(name) ? msg[name] : name;
		},
		getContainer: function()
		{
			return this._container;
		},
		getBodyContainer: function()
		{
			return this._bodyContainer;
		},
		isExpanded: function()
		{
			return this._isExpanded;
		},
		setExpanded: function(expanded)
		{
			expanded = !!expanded;
			if(this._isExpanded === expanded)
			{
				return;
			}

			this._config["expanded"] = (this._isExpanded = expanded) ? "Y" : "N";
			if(expanded)
			{
				this._toggleButton.innerHTML = this.getMessage("minimize");
			}
			else
			{
				this._toggleButton.innerHTML = this.getMessage("maximize");
			}

			this._toggle.toggle();
			this.saveConfig();
		},
		saveConfig: function()
		{
			var data = { guid: this._id, action: "saveconfig", config: this._config };
			BX.ajax.post(this._serviceUrl, data);
		},
		ajustFontSize: function(nodeList)
		{
			var fontSize = 0;
			var mainFontSize = 0;
			var decrease = true;
			var increase = true;
			var maxFontSize = 31;

			if(!nodeList)
			{
				return;
			}

			var padding = 50;
			for(var i = 0; i< nodeList.length; i++)
			{
				fontSize = parseInt(BX.style(nodeList[i], 'font-size'));

				decrease = nodeList[i].offsetWidth > (nodeList[i].parentNode.offsetWidth - padding);
				increase = nodeList[i].offsetWidth < (nodeList[i].parentNode.offsetWidth - padding);

				while(nodeList[i].offsetWidth > (nodeList[i].parentNode.offsetWidth - padding) && decrease)
				{
					fontSize -=2;
					nodeList[i].style.fontSize = fontSize + 'px';
					nodeList[i].style.lineHeight = (fontSize + 8) + 'px';
					increase = false;
				}

				while(nodeList[i].offsetWidth < (nodeList[i].parentNode.offsetWidth - padding) && fontSize<maxFontSize && increase)
				{
					fontSize +=2;
					nodeList[i].style.fontSize = fontSize + 'px';
					nodeList[i].style.lineHeight = (fontSize + 8) + 'px';
					decrease = false;
				}

				if(!mainFontSize && i > 0)
					mainFontSize = fontSize;

				if(i>0)
					mainFontSize = Math.min(mainFontSize, fontSize)
			}

			for(var b = 0; b < nodeList.length; b++)
			{
				nodeList[b].style.opacity = 1;

				if(b > 0)
				{
					nodeList[b].style.fontSize = mainFontSize + 'px';
					nodeList[b].style.lineHeight = (mainFontSize + 8) + 'px';
				}

			}
		},
		ajustInterlacing: function()
		{
			var rows = this._container.querySelectorAll(".startpage-table-interlacing");
			for(var i = 0, l = rows.length; i < l; i++)
			{
				var row = rows[i];
				if(((i + 1) % 2) === 0)
				{
					if(!BX.hasClass(row, "even"))
					{
						BX.addClass(row, "even");
						BX.removeClass(row, "odd");
					}
				}
				else
				{
					if(!BX.hasClass(row, "odd"))
					{
						BX.addClass(row, "odd");
						BX.removeClass(row, "even");
					}
				}
			}
		},
		onToggleButtonClick: function(e)
		{
			if(!e)
			{
				e = window.event;
			}

			this.setExpanded(!this.isExpanded());
			return BX.PreventDefault(e);
		},
		onToggleStart: function(toggle)
		{
			if(this._isExpanded)
			{
				BX.removeClass(this._container, "collapse");
			}
		},
		onToggleFinish: function(toggle)
		{
			if(!this._isExpanded)
			{
				BX.addClass(this._container, "collapse");
			}
		},
		onHelpButtonClick: function(e)
		{
			if(!e)
			{
				e = window.event;
			}

			if(!this.isHelpPopupOpened())
			{
				this.openHelpPopup();
			}
			else
			{
				this.closeHelpPopup();
			}
			return BX.PreventDefault(e);
		},
		isHelpPopupOpened: function()
		{
			return this._helpPopup !== null && this._helpPopup.isShown();
		},
		openHelpPopup: function()
		{
			if(this._helpPopup === null)
			{
				this._helpPopup = BX.PopupWindowManager.create(this._id + "_help",
					this._helpButton,
					{
						autoHide : true,
						closeByEsc : false,
						offsetTop: -5,
						offsetLeft: 18,
						bindOptions: {position: "bottom"},
						angle: { position: "top" },
						events: { onPopupClose : BX.delegate(this.onHelpPopupClose, this) },
						content : BX.create("DIV",
							{
								attrs: { className: "crm-popup-contents" },
								children:
									[
										BX.create(
											"SPAN",
											{
												attrs: { className: "startpage-help-popup-title" },
												text: this.getMessage("helpTitle")
											}
										),
										BX.create(
											"DIV",
											{
												attrs: { className: "startpage-help-popup-content" },
												html: this.getMessage("helpContent")
											}
										)
									]
							}
						)
					}
				);
			}
			this._helpPopup.show();
		},
		closeHelpPopup: function()
		{
			if(this._helpPopup !== null)
			{
				this._helpPopup.close();
			}
		},
		onHelpPopupClose: function()
		{
			if(this._helpPopup)
			{
				this._helpPopup.destroy();
				this._helpPopup = null;
			}
		},
		onWidowResize: function(e)
		{
			this.ajustFontSize(this._container.querySelectorAll(".startpage-table-header-block-count-wrapper"));
		}
	};

	if(typeof(BX.CrmChannelTracker.messages) === "undefined")
	{
		BX.CrmChannelTracker.messages = {};
	}
	BX.CrmChannelTracker.create = function(id, settings)
	{
		var self = new BX.CrmChannelTracker();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmChannelTrackerGroup) === "undefined")
{
	BX.CrmChannelTrackerGroup = function()
	{
		this._id = "";
		this._settings = {};
		this._parent = null;
		this._container = null;
		this._itemContainer = null;
		this._toggleButton = null;
		this._toggle = null;
		this._isExpanded = false;
	};

	BX.CrmChannelTrackerGroup.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : '';
			this._settings = settings ? settings : {};

			this._parent = this.getSetting("parent", null);
			if(!(this._parent instanceof BX.CrmChannelTracker))
			{
				throw "BX.CrmChannelTrackerGroup. Could not find 'parent' parameter.";
			}

			this._container = this.getSetting("container", null);
			if(!BX.type.isElementNode(this._container))
			{
				this._container = this._parent.getContainer().querySelector("[data-group='"+ this._id +"']");
				if(!BX.type.isElementNode(this._container))
				{
					throw "BX.CrmChannelTrackerGroup. Could not find group container.";
				}
			}

			this._itemContainer = this.getSetting("itemContainer", null);
			if(!BX.type.isElementNode(this._itemContainer))
			{
				this._itemContainer = this._parent.getContainer().querySelector("[data-group-items='"+ this._id +"']");
				if(!BX.type.isElementNode(this._itemContainer))
				{
					throw "BX.CrmChannelTrackerGroup. Could not find group item container.";
				}
			}

			this._toggleButton = this._container.querySelector(".startpage-table-data-children-toggle");
			if(!BX.type.isElementNode(this._toggleButton))
			{
				throw "BX.CrmChannelTrackerGroup. Could not find toggle button.";
			}
			BX.bind(this._toggleButton, "click", BX.delegate(this.onToggleButtonClick, this));

			this._toggle = BX.CrmChannelTrackerGroupToggle.create({ target: this });
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getContainer: function()
		{
			return this._container;
		},
		getItemContainer: function()
		{
			return this._itemContainer;
		},
		isExpanded: function()
		{
			return this._isExpanded;
		},
		ajustInterlacing: function()
		{
			var i, length;
			var itemConainers = this._itemContainer.querySelectorAll(".startpage-table-data");
			if(this._isExpanded)
			{
				for(i = 0, length = itemConainers.length; i < length; i++)
				{
					BX.addClass(itemConainers[i], "startpage-table-interlacing");
				}
			}
			else
			{
				for(i = 0, length = itemConainers.length; i < length; i++)
				{
					BX.removeClass(itemConainers[i], "startpage-table-interlacing");
				}
			}
			this._parent.ajustInterlacing();
		},
		toggle: function()
		{
			this._isExpanded = !this._isExpanded;
			this.ajustInterlacing();
			this._toggle.toggle();
		},
		onToggleButtonClick: function(e)
		{
			this.toggle();
		},
		onToggleStart: function(toggle)
		{
			if(this._isExpanded)
			{
				BX.removeClass(this._container, "collapse");
				BX.removeClass(this._itemContainer, "collapse");
			}
		},
		onToggleFinish: function(toggle)
		{
			if(!this._isExpanded)
			{
				BX.addClass(this._container, "collapse");
				BX.addClass(this._itemContainer, "collapse");
			}
		}
	};
	BX.CrmChannelTrackerGroup.create = function(id, settings)
	{
		var self = new BX.CrmChannelTrackerGroup();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmChannelTrackerToggle) === "undefined")
{
	BX.CrmChannelTrackerToggle = function()
	{
		this._settings = {};
		this._target = null;
	};

	BX.CrmChannelTrackerToggle.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};

			this._target = this.getSetting("target");
			if(!(this._target instanceof BX.CrmChannelTracker))
			{
				throw "BX.CrmChannelTrackerToggle. Could not find 'target' parameter.";
			}
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		easing: function(params)
		{
			this._target.onToggleStart(this);

			(new BX.easing(
				{
					duration: 1000,
					start: { height: params.start },
					finish: { height: params.finish },
					transition: BX.easing.makeEaseOut(BX.easing.transitions.circ),
					step: BX.delegate(this.step, this),
					complete: BX.proxy(this.complete, this)
				}
			)).animate();
		},
		toggle: function()
		{
			var body = this._target.getBodyContainer();
			body.style.overflow = "hidden";

			if(this._target.isExpanded())
			{
				this.easing({ start: 0, finish: body.scrollHeight });
			}
			else
			{
				this.easing({ start : body.offsetHeight, finish : 0 });
			}
		},
		step: function(state)
		{
			this._target.getBodyContainer().style.height = state.height + "px";
		},
		complete: function ()
		{
			this._target.onToggleFinish(this);

			var body = this._target.getBodyContainer();
			body.style.overflow = "";
			body.style.height = "";
		}
	};

	BX.CrmChannelTrackerToggle.create = function(settings)
	{
		var self = new BX.CrmChannelTrackerToggle();
		self.initialize(settings);
		return self;
	};
}

if(typeof(BX.CrmChannelTrackerGroupToggle) === "undefined")
{
	BX.CrmChannelTrackerGroupToggle = function()
	{
		this._settings = {};
		this._target = null;
	};

	BX.CrmChannelTrackerGroupToggle.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : {};

			this._target = this.getSetting("target");
			if(!(this._target instanceof BX.CrmChannelTrackerGroup))
			{
				throw "BX.CrmChannelTrackerGroupToggle. Could not find 'target' parameter.";
			}
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		easing: function(params)
		{
			this._target.onToggleStart(this);

			(new BX.easing(
				{
					duration: 500,
					start: { height: params.start },
					finish: { height: params.finish },
					transition: BX.easing.makeEaseOut(BX.easing.transitions.circ),
					step: BX.delegate(this.step, this),
					complete: BX.proxy(this.complete, this)
				}
			)).animate();
		},
		toggle: function()
		{
			var container = this._target.getItemContainer();

			if(this._target.isExpanded())
			{
				this.easing({ start: 0, finish: container.scrollHeight });
			}
			else
			{
				this.easing({ start: container.offsetHeight, finish : 0 });
			}
		},
		step: function(state)
		{
			this._target.getItemContainer().style.height = state.height + "px";
		},
		complete: function ()
		{
			this._target.onToggleFinish(this);

			var container = this._target.getItemContainer();
			container.style.height = "";
		}
	};

	BX.CrmChannelTrackerGroupToggle.create = function(settings)
	{
		var self = new BX.CrmChannelTrackerGroupToggle();
		self.initialize(settings);
		return self;
	};
}