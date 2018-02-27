if(typeof(BX.CrmUserSearchPopup) === 'undefined')
{
	BX.CrmUserSearchPopup = function()
	{
		this._id = '';
		this._search_input = null;
		this._data_input = null;
		this._componentName = '';
		this._componentContainer = null;
		this._componentObj = null;
		this._serviceContainer = null;
		this._zIndex = 0;
		this._dlg = null;
		this._dlgDisplayed = false;
		this._currentUser = {};

		this._searchKeyHandler = BX.delegate(this._handleSearchKey, this);
		this._searchFocusHandler = BX.delegate(this._handleSearchFocus, this);
		this._externalClickHandler = BX.delegate(this._handleExternalClick, this);
	};

	BX.CrmUserSearchPopup.prototype =
	{
		//initialize: function(id, search_input, data_input, componentName, user, serviceContainer, zIndex)
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : ('crm_user_search_popup_' + Math.random());

			if(!settings)
			{
				settings = {};
			}

			if(!BX.type.isElementNode(settings['searchInput']))
			{
				throw  "BX.CrmUserSearchPopup: 'search_input' is not defined!";
			}
			this._search_input = settings['searchInput'];

			if(!BX.type.isElementNode(settings['dataInput']))
			{
				throw  "BX.CrmUserSearchPopup: 'data_input' is not defined!";
			}
			this._data_input = settings['dataInput'];

			if(!BX.type.isNotEmptyString(settings['componentName']))
			{
				throw  "BX.CrmUserSearchPopup: 'componentName' is not defined!";
			}

			this._currentUser = settings['user'] ? settings['user'] : {};
			this._componentName = settings['componentName'];
			this._componentContainer = BX(this._componentName + '_selector_content');
			var objName = 'O_' + this._componentName;
			if(window[objName])
			{
				this._componentObj = window[objName];
				this._componentObj.onSelect = BX.delegate(this._handleUserSelect, this);
				this._componentObj.searchInput = this._search_input;

				if(this._currentUser)
				{
					this._componentObj.setSelected([ this._currentUser ]);
				}

				BX.bind(this._search_input, 'keyup', this._searchKeyHandler);
				BX.bind(this._search_input, 'focus', this._searchFocusHandler);
				BX.bind(document, 'click', this._externalClickHandler);
			}

			this._adjustUser();

			this._serviceContainer = settings['serviceContainer'] ? settings['serviceContainer'] : document.body;
			this.setZIndex(settings['zIndex']);
		},
		open: function()
		{
			this._componentContainer.style.display = '';
			this._dlg = new BX.PopupWindow(
				this._id,
				this._search_input,
				{
					autoHide: false,
					draggable: false,
					closeByEsc: true,
					offsetLeft: 0,
					offsetTop: 0,
					zIndex: this._zIndex,
					bindOptions: { forceBindPosition: true },
					content : this._componentContainer,
					events:
					{
						onPopupShow: BX.delegate(
							function()
							{
								this._dlgDisplayed = true;
							},
							this
						),
						onPopupClose: BX.delegate(
							function()
							{
								this._dlgDisplayed = false;
								this._componentContainer.parentNode.removeChild(this._componentContainer);
								this._serviceContainer.appendChild(this._componentContainer);
								this._componentContainer.style.display = 'none';
								this._dlg.destroy();
							},
							this
						),
						onPopupDestroy: BX.delegate(
							function()
							{
								this._dlg = null;
							},
							this
						)
					}
				}
			);

			this._dlg.show();
		},
		_adjustUser: function()
		{
			//var container = BX.findParent(this._search_input, { className: 'webform-field-textbox' });
			if(parseInt(this._currentUser['id']) > 0)
			{
				this._data_input.value = this._currentUser['id'];
				this._search_input.value = this._currentUser['name'] ? this._currentUser.name : this._currentUser['id'];
				//BX.removeClass(container, 'webform-field-textbox-empty');
			}
			else
			{
				this._data_input.value = this._search_input.value = '';
				//BX.addClass(container, 'webform-field-textbox-empty');
			}
		},
		getZIndex: function()
		{
			return this._zIndex;
		},
		setZIndex: function(zIndex)
		{
			if(typeof(zIndex) === 'undefined' || zIndex === null)
			{
				zIndex = 0;
			}

			var i = parseInt(zIndex);
			this._zIndex = !isNaN(i) ? i : 0;
		},
		close: function()
		{
			if(this._dlg)
			{
				this._dlg.close();
			}
		},
		select: function(user)
		{
			this._currentUser = user;
			this._adjustUser();
			if(this._componentObj)
			{
				this._componentObj.setSelected([ user ]);
			}
		},
		_onBeforeDelete: function()
		{
			if(BX.type.isElementNode(this._search_input))
			{
				BX.unbind(this._search_input, 'keyup', this._searchKeyHandler);
				BX.unbind(this._search_input, 'focus', this._searchFocusHandler);
			}
			BX.unbind(document, 'click', this._externalClickHandler);
		},
		_handleExternalClick: function(e)
		{
			if(!e)
			{
				e = window.event;
			}

			if(!this._dlgDisplayed)
			{
				return;
			}

			if(e.target !== this._search_input &&
				!BX.findParent(e.target, { attribute:{ id: this._componentName + '_selector_content' } }))
			{
				this._adjustUser();
				this.close();
			}
		},
		_handleSearchKey: function(e)
		{
			if(!this._dlg || !this._dlgDisplayed)
			{
				this.open();
			}

			this._componentObj.search();
		},
		_handleSearchFocus: function(e)
		{
			if(!this._dlg || !this._dlgDisplayed)
			{
				this.open();
			}

			this._componentObj._onFocus(e);
		},
		_handleUserSelect: function(user)
		{
			this._currentUser = user;
			this._adjustUser();
			this.close();
		}
	};

	BX.CrmUserSearchPopup.items = {};

	BX.CrmUserSearchPopup.create = function(id, settings, delay)
	{
		if(isNaN(delay))
		{
			delay = 0;
		}

		if(delay > 0)
		{
			window.setTimeout(
				function(){ BX.CrmUserSearchPopup.create(id, settings, 0); },
				delay
			);
			return null;
		}

		var self = new BX.CrmUserSearchPopup();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	};

	BX.CrmUserSearchPopup.createIfNotExists = function(id, settings)
	{
		var self = this.items[id];
		if(typeof(self) !== 'undefined')
		{
			self.initialize(id, settings);
		}
		else
		{
			self = new BX.CrmUserSearchPopup();
			self.initialize(id, settings);
			this.items[id] = self;
		}
		return self;
	};

	BX.CrmUserSearchPopup.deletePopup = function(id)
	{
		var item = this.items[id];
		if(typeof(item) === 'undefined')
		{
			return false;
		}

		item._onBeforeDelete();
		delete this.items[id];
		return true;
	}
}

if(typeof(BX.CrmNotifier) === "undefined")
{
	BX.CrmNotifier = function()
	{
		this._sender = null;
		this._listeners = [];
	};

	BX.CrmNotifier.prototype =
	{
		initialize: function(sender)
		{
			this._sender = sender;
		},
		addListener: function(listener)
		{
			if(!BX.type.isFunction(listener))
			{
				return;
			}

			for(var i = 0; i < this._listeners.length; i++)
			{
				if(this._listeners[i] === listener)
				{
					return;
				}
			}

			this._listeners.push(listener);
		},
		removeListener: function(listener)
		{
			if(!BX.type.isFunction(listener))
			{
				return;
			}

			for(var i = 0; i < this._listeners.length; i++)
			{
				if(this._listeners[i] === listener)
				{
					this._listeners.splice(i, 1);
					return;
				}
			}
		},
		notify: function(params)
		{
			//Make copy of listeners to process addListener/removeListener while notification under way.
			var ary = [];
			for(var i = 0; i < this._listeners.length; i++)
			{
				ary.push(this._listeners[i]);
			}

			if(!BX.type.isArray(params))
			{
				params = [];
			}

			params.splice(0, 0, this._sender);

			for(var j = 0; j < ary.length; j++)
			{
				try
				{
					ary[j].apply(this._sender, params);
				}
				catch(ex)
				{
				}
			}
		}
	};

	BX.CrmNotifier.create = function(sender)
	{
		var self = new BX.CrmNotifier();
		self.initialize(sender);
		return self;
	}
}

if(typeof(BX.CmrSelectorItem) === "undefined")
{
	BX.CmrSelectorItem = function()
	{
		this._parent = null;
		this._settings = {};
		this._onSelectNotifier = null;
	};

	BX.CmrSelectorItem.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings;
			this._onSelectNotifier = BX.CrmNotifier.create(this);
			var events = this.getSetting("events");
			if(events && events['select'])
			{
				this._onSelectNotifier.addListener(events['select']);
			}
		},
		getSetting: function(name, defaultval)
		{
			var s = this._settings;
			return typeof(s[name]) != "undefined" ? s[name] : defaultval;
		},
		getValue: function()
		{
			return this.getSetting("value", "");
		},
		getText: function()
		{
			var text = this.getSetting("text");
			return BX.type.isNotEmptyString(text) ? text : this.getValue();
		},
		isEnabled: function()
		{
			return this.getSetting("enabled", true);
		},
		isDefault: function()
		{
			return this.getSetting("default", false);
		},
		createMenuItem: function()
		{
			return(
				{
					"text":  this.getText(),
					"onclick": BX.delegate(this._onClick, this)
				});
		},
		addOnSelectListener: function(listener)
		{
			this._onSelectNotifier.addListener(listener);
		},
		removeOnSelectListener: function(listener)
		{
			this._onSelectNotifier.removeListener(listener);
		},
		_onClick: function()
		{
			this._onSelectNotifier.notify();
		}
	};

	BX.CmrSelectorItem.create = function(settings)
	{
		var self = new BX.CmrSelectorItem();
		self.initialize(settings);
		return self;
	};
}

if(typeof(BX.CrmSelector) === "undefined")
{
	BX.CrmSelector = function()
	{
		this._id = "";
		this._selectedValue = "";
		this._settings = {};
		this._outerWrapper = this._wrapper = this._container = this._view = null;
		this._items = [];
		this._onSelectNotifier = null;
		this._popup = null;
		this._isPopupShown = false;
	};

	BX.CrmSelector.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : ("crm_selector_" + Math.random().toString().substring(2));
			this._settings = settings ? settings : {};
			this._container = this.getSetting("container", null);
			this._selectedValue = this.getSetting("selectedValue", "");

			var itemData = this.getSetting("items");
			itemData = BX.type.isArray(itemData) ? itemData : [];
			this._items = [];
			for(var i = 0; i < itemData.length; i++)
			{
				var item = BX.CmrSelectorItem.create(itemData[i]);
				item.addOnSelectListener(BX.delegate(this._onItemSelect, this));
				this._items.push(item);
			}

			this._onSelectNotifier = BX.CrmNotifier.create(this);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			var s = this._settings;
			return typeof(s[name]) != "undefined" ? s[name] : defaultval;
		},
		isEnabled: function()
		{
			return this.getSetting('enabled', true);
		},
		layout: function(container)
		{
			if(BX.type.isDomNode(container))
			{
				this._container = container;
			}
			else if(this._container)
			{
				container = this._container;
			}

			if(!container)
			{
				return;
			}

			var isEnabled = this.isEnabled();

			var layout = this.getSetting('layout');
			if(!layout)
			{
				layout = {};
			}

			var outerWrapper = this._outerWrapper = BX.create(
				"DIV",
				{
					"attrs":
					{
						"className": "crm-selector-container",
						"id": this._id
					}
				}
			);

			if(layout['position'] === 'first')
			{
				container.insertBefore(outerWrapper, BX.firstChild(container));
			}
			else if(layout['insertBefore'])
			{
				container.insertBefore(outerWrapper, BX.findChild(container, layout['insertBefore']));
			}
			else
			{
				container.appendChild(outerWrapper);
			}

			var title = this.getSetting("title", "");
			if(BX.type.isNotEmptyString(title))
			{
				outerWrapper.appendChild(
					BX.create(
						"SPAN",
						{
							"attrs":
							{
								"className": "crm-selector-title"
							},
							"text": title + ':'
						}
					)
				);
			}

			var wrapper = this._wrapper = BX.create(
				"DIV",
				{
					"attrs":
					{
						"className": "crm-selector-wrapper"
					}
				}
			);
			outerWrapper.appendChild(wrapper);

			var onClickHandler = BX.delegate(this._onClick, this);

			var innerWrapper = BX.create(
				"DIV",
				{
					"attrs":
					{
						"className": "crm-selector-inner-wrapper"
					}
				}
			);
			if(isEnabled)
			{
				BX.bind(innerWrapper, "click", onClickHandler);
			}
			wrapper.appendChild(innerWrapper);

			var selectItem = this._findItemByValue(this._selectedValue);
			if(!selectItem)
			{
				selectItem = this.getDefaultItem();
			}

			var view = this._view = BX.create(
				"SPAN",
				{
					"attrs":
					{
						"className": "crm-selector-view"
					},
					"text": selectItem ? selectItem.getText() : ""
				}
			);
			innerWrapper.appendChild(view);

			if(isEnabled)
			{
				innerWrapper.appendChild(
					BX.create(
						"A",
						{
							"attrs":
							{
								"className": "crm-selector-arrow"
							},
							"events":
							{
								"click": onClickHandler
							},
							"html": "&nbsp;"
						}
					)
				);
			}
		},
		clearLayout: function()
		{
			if(!this._outerWrapper)
			{
				return;
			}

			BX.remove(this._outerWrapper);
			this._outerWrapper = null;
		},
		getItems: function()
		{
			return this._items;
		},
		selectValue: function(value)
		{
			this.selectItem(this._findItemByValue(value));
		},
		selectItem: function(item)
		{
			if(!item)
			{
				return;
			}

			this._selectedValue = item.getValue();
			if(this._view)
			{
				this._view.innerHTML = BX.util.htmlspecialchars(item.getText());
			}
		},
		getSelectedValue: function()
		{
			return this._selectedValue;
		},
		getSelectedItem: function()
		{
			return this._findItemByValue(this._selectedValue);
		},
		getDefaultItem: function()
		{
			var items = this.getItems();
			for(var i = 0; i < items.length; i++)
			{
				var item = items[i];
				if(item.isDefault())
				{
					return item;
				}
			}

			return null;
		},
		showPopup: function()
		{
			if(this._isPopupShown)
			{
				return;
			}

			var menuItems = [];
			for(var i = 0; i < this._items.length; i++)
			{
				var item = this._items[i];
				if(item.isEnabled())
				{
					menuItems.push(item.createMenuItem());
				}
			}

			BX.PopupMenu.show(
				this._id,
				this._wrapper,
				menuItems,
				{
					"offsetTop": 0,
					"offsetLeft": 0,
					"events":
					{
						"onPopupShow": BX.delegate(this._onPopupShow, this),
						"onPopupClose": BX.delegate(this._onPopupClose, this),
						"onPopupDestroy": BX.delegate(this._onPopupDestroy, this)
					}
				}
			);
			this._popup = BX.PopupMenu.currentItem;
		},
		addOnSelectListener: function(listener)
		{
			this._onSelectNotifier.addListener(listener);
		},
		removeOnSelectListener: function(listener)
		{
			this._onSelectNotifier.removeListener(listener);
		},
		_findItemByValue: function(value)
		{
			var items = this.getItems();
			for(var i = 0; i < items.length; i++)
			{
				var item = items[i];
				if(value === item.getValue())
				{
					return item;
				}
			}

			return null;
		},
		_onClick: function(e)
		{
			e = e ? e : window.event;
			BX.PreventDefault(e);
			if(this.isEnabled())
			{
				this.showPopup();
			}
		},
		_onItemSelect: function(item)
		 {
			 this.selectItem(item);

			 if(this._popup)
			 {
				 if(this._popup.popupWindow)
				 {
					this._popup.popupWindow.close();
				 }
			 }

			 this._onSelectNotifier.notify([item]);
		 },
		_onPopupShow: function()
		{
			this._isPopupShown = true;
		},
		_onPopupClose: function()
		{
			if(this._popup)
			{
				if(this._popup.popupWindow)
				{
					this._popup.popupWindow.destroy();
				}
			}
		},
		_onPopupDestroy: function()
		{
			this._isPopupShown = false;
			this._popup = null;

			if(typeof(BX.PopupMenu.Data[this._id]) !== "undefined")
			{
				delete(BX.PopupMenu.Data[this._id]);
			}
		}
	};

	BX.CrmSelector.create = function(id, settings)
	{
		var self = new BX.CrmSelector();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};

	BX.CrmSelector.deleteItem = function(id)
	{
	if(this.items[id])
	{
		this.items[id].clearLayout();
		delete this.items[id];
	}
};

	BX.CrmSelector.items = {};
}

if(typeof(BX.CrmInterfaceFormUtil) === "undefined")
{
	BX.CrmInterfaceFormUtil = function(){};
	BX.CrmInterfaceFormUtil.disableThemeSelection = function(formId)
	{
		var form = window["bxForm_" + formId];
		var menu = form ? form.settingsMenu : null;
		if(!menu)
		{
			return;
		}

		for(var i = 0; i < menu.length; i++)
		{
			if(menu[i] && menu[i].ICONCLASS === "form-themes")
			{
				menu.splice(i, 1);
				break;
			}
		}

		if(menu.length === 0)
		{
			var btn = BX.findChild(BX("form_" + formId), { "tag":"A", "class": "bx-context-button bx-form-menu" }, true);
			if(btn)
			{
				btn.style.display = "none";
			}
		}
	};

	BX.CrmInterfaceFormUtil.showFormRow = function(show, element)
	{
		var row = BX.findParent(element, {'tag': 'TR'});
		if(row)
		{
			row.style.display = !!show ? '' : 'none';
		}
	}
}

if(typeof(BX.CrmParamBag) === "undefined")
{
	BX.CrmParamBag = function()
	{
		this._params = {};
	};

	BX.CrmParamBag.prototype =
	{
		initialize: function(params)
		{
			this._params = params ? params : {};
		},
		getParam: function(name, defaultvalue)
		{
			var p = this._params;
			return typeof(p[name]) != "undefined" ? p[name] : defaultvalue;
		},
		setParam: function(name, value)
		{
			this._params[name] = value;
		},
		clear: function()
		{
			this._params = {};
		}
	};

	BX.CrmParamBag.create = function(params)
	{
		var self = new BX.CrmParamBag();
		self.initialize(params);
		return self;
	}
}

if(typeof(BX.CrmSubscriber) === "undefined")
{
	BX.CrmSubscriber = function()
	{
		this._id = "";
		this._element = null;
		this._eventName = "";
		this._callback = null;
		this._settings = null;
		this._handler = BX.delegate(this._onElementEvent, this);
	};

	BX.CrmSubscriber.prototype =
	{
		initialize: function(id, element, eventName, callback, settings)
		{
			this._id = id;
			this._element = element;
			this._eventName = eventName;
			this._callback = callback;
			this._settings = settings ? settings : BX.CrmParamBag.create(null);
		},
		getSetting: function(name, defaultvalue)
		{
			return this._settings.getParam(name, defaultvalue);
		},
		setSetting: function(name, value)
		{
			return this._settings.setParam(name, value);
		},
		getId: function()
		{
			return this._id;
		},
		getElement: function()
		{
			return this._element;
		},
		getEventName: function()
		{
			return this._eventName;
		},
		getCallback: function()
		{
			return this._callback;
		},
		subscribe: function()
		{
			BX.bind(this.getElement(), this.getEventName(), this._handler);
		},
		unsubscribe: function()
		{
			BX.unbind(this.getElement(), this.getEventName(), this._handler);
		},
		_onElementEvent: function(e)
		{
			var callback = this.getCallback();
			if(BX.type.isFunction(callback))
			{
				callback(this, { "event": e });
			}

			return this.getSetting("preventDefault", false) ? BX.PreventDefault(e) : true;
		}
	};

	BX.CrmSubscriber.items = {};
	BX.CrmSubscriber.create = function(id, element, eventName, callback, settings)
	{
		var self = new BX.CrmSubscriber();
		self.initialize(id, element, eventName, callback, settings);
		this.items[id] = self;
		return self;
	}

	BX.CrmSubscriber.subscribe = function(id, element, eventName, callback, settings)
	{
		var self = this.create(id, element, eventName, callback, settings);
		self.subscribe();
		return self;
	}
}

if(typeof(BX.CrmMultiFieldViewer) === "undefined")
{
	BX.CrmMultiFieldViewer = function()
	{
		this._id = '';
		this._shown = false;
		this._layout = '';
	}
	BX.CrmMultiFieldViewer.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._layout = this.getSetting('layout', 'grid').toLowerCase();
		},
		getSetting: function (name, defaultval)
		{
			return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
		},
		show: function()
		{
			if(this._shown)
			{
				return;
			}

			var tab = BX.create('TABLE');

			tab.cellSpacing = '0';
			tab.cellPadding = '0';
			tab.border = '0';

			tab.className = 'bx-crm-grid-multi-field-viewer';
			var items = this.getSetting('items', []);
			for(var i = 0; i < items.length; i++)
			{
				var item = items[i];

				var r = tab.insertRow(-1);
				var valueCell = r.insertCell(-1);

				valueCell.innerHTML = item['value'];

				var typeCell = r.insertCell(-1);

				typeCell.appendChild(
					BX.create(
						'SPAN',
						{
							attrs: { className: 'crm-multi-field-value-type' },
							text: item['type']
						}
					)
				);
			}

			var dlg = BX.CrmMultiFieldViewer.dialogs[this._id] ? BX.CrmMultiFieldViewer.dialogs[this._id] : null;
			if(!dlg)
			{
				dlg = new BX.PopupWindow(
					this._id,
					BX(this.getSetting('anchorId', '')),
					{
						autoHide: true,
						draggable: false,
						offsetLeft: 0,
						offsetTop: 0,
						bindOptions: { forceBindPosition: true },
						closeByEsc: true,
						events:
						{
							onPopupShow: BX.delegate(
								function()
								{
									this._shown = true;
								},
								this
							),
							onPopupClose: BX.delegate(
								function()
								{
									this._shown = false;
									BX.CrmMultiFieldViewer.dialogs[this._id].destroy();
								},
								this
							),
							onPopupDestroy: BX.delegate(
								function()
								{
									delete(BX.CrmMultiFieldViewer.dialogs[this._id]);
								},
								this
							)
						},
						content: tab
					}
				);
				BX.CrmMultiFieldViewer.dialogs[this._id] = dlg;
			}

			dlg.show();
		}
	};
	BX.CrmMultiFieldViewer.items = {};
	BX.CrmMultiFieldViewer.create = function(id, settings)
	{
		var self = new BX.CrmMultiFieldViewer();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	};
	BX.CrmMultiFieldViewer.ensureCreated = function(id, settings)
	{
		return this.items[id] ? this.items[id] : this.create(id, settings);
	};
	BX.CrmMultiFieldViewer.dialogs = {};
}
