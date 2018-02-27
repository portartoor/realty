if(typeof(BX.HtmlHelper) === "undefined")
{
	BX.HtmlHelper = function(){};
	BX.HtmlHelper.setupSelectOptions = function(select, settings)
	{
		while (select.options.length > 0)
		{
			select.remove(0);
		}

		var currentGroup = null;
		var currentGroupName = "";

		for(var i = 0; i < settings.length; i++)
		{
			var setting = settings[i];

			var groupName = BX.type.isNotEmptyString(setting["group"]) ? setting["group"] : "";
			if(groupName !== "" && groupName !== currentGroupName)
			{
				currentGroupName = groupName;
				currentGroup = document.createElement("OPTGROUP");
				currentGroup.label = groupName;
				select.appendChild(currentGroup);
			}

			var value = BX.type.isNotEmptyString(setting['value']) ? setting['value'] : '';
			var text = BX.type.isNotEmptyString(setting['text']) ? setting['text'] : setting['value'];

			var option = new Option(text, value, false, false);

			var attrs = BX.type.isPlainObject(setting['attrs']) ? setting['attrs'] : null;
			if(attrs)
			{
				for(var k in attrs)
				{
					if(!attrs.hasOwnProperty(k))
					{
						continue;
					}

					option.setAttribute("data-" + k, attrs[k]);
				}
			}

			if(currentGroup)
			{
				currentGroup.appendChild(option);
			}
			else
			{
				if(!BX.browser.IsIE())
				{
					select.add(option, null);
				}
				else
				{
					try
					{
						// for IE earlier than version 8
						select.add(option, select.options[null]);
					}
					catch (e)
					{
						select.add(option, null);
					}
				}
			}
		}
	};
}

if(typeof(BX.CrmUserSearchPopup) === "undefined")
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
		this._clearButtonClickHandler = BX.delegate(this._hadleClearButtonClick, this);

		this._userSelectorInitCounter = 0;
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

			this._clearButton = BX.findPreviousSibling(this._search_input, { className: "crm-filter-name-clean" });

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

			this._initializeUserSelector();
			this._adjustUser();

			this._serviceContainer = settings['serviceContainer'] ? settings['serviceContainer'] : document.body;
			this.setZIndex(settings['zIndex']);
		},
		_initializeUserSelector: function()
		{
			var objName = 'O_' + this._componentName;
			if(!window[objName])
			{
				if(this._userSelectorInitCounter === 10)
				{
					throw "BX.CrmUserSearchPopup: Could not find '"+ objName +"' user selector!";
				}

				this._userSelectorInitCounter++;
				window.setTimeout(BX.delegate(this._initializeUserSelector, this), 200);
				return;
			}

			this._componentObj = window[objName];
			this._componentObj.onSelect = BX.delegate(this._handleUserSelect, this);
			this._componentObj.searchInput = this._search_input;

			if(this._currentUser)
			{
				this._componentObj.setSelected([ this._currentUser ]);
			}

			BX.bind(this._search_input, 'keyup', this._searchKeyHandler);
			BX.bind(this._search_input, 'focus', this._searchFocusHandler);

			if(BX.type.isElementNode(this._clearButton))
			{
				BX.bind(this._clearButton, 'click', this._clearButtonClickHandler);
			}

			BX.bind(document, 'click', this._externalClickHandler);
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

			if(BX.type.isElementNode(this._clearButton))
			{
				BX.bind(this._clearButton, 'click', this._clearButtonClickHandler);
			}

			BX.unbind(document, 'click', this._externalClickHandler);

			if(this._componentContainer)
			{
				BX.remove(this._componentContainer);
				this._componentContainer = null;
			}
		},
		_hadleClearButtonClick: function(e)
		{
			this._data_input.value = this._search_input.value = '';
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

			var target = null;
			if(e)
			{
				if(e.target)
				{
					target = e.target;
				}
				else if(e.srcElement)
				{
					target = e.srcElement;
				}
			}

			if(target !== this._search_input &&
				!BX.findParent(target, { attribute:{ id: this._componentName + '_selector_content' } }))
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
		resetListeners: function()
		{
			this._listeners = [];
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
		},
		getListenerCount: function()
		{
			return this._listeners.length;
		}
	};

	BX.CrmNotifier.create = function(sender)
	{
		var self = new BX.CrmNotifier();
		self.initialize(sender);
		return self;
	}
}

//region BX.CmrSelectorMenuItem
if(typeof(BX.CmrSelectorMenuItem) === "undefined")
{
	BX.CmrSelectorMenuItem = function()
	{
		this._parent = null;
		this._settings = {};
		this._onSelectNotifier = null;
	};
	BX.CmrSelectorMenuItem.prototype =
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
		createMenuItem: function(encode)
		{
			encode = !!encode;
			var text = this.getText();
			if(!!encode)
			{
				text = BX.util.htmlspecialchars(text);
			}
			return(
			{
				"text":  text,
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
	BX.CmrSelectorMenuItem.create = function(settings)
	{
		var self = new BX.CmrSelectorMenuItem();
		self.initialize(settings);
		return self;
	};
}
//endregion
//region BX.CmrSelectorMenu
if(typeof(BX.CmrSelectorMenu) === "undefined")
{
	BX.CmrSelectorMenu = function()
	{
		this._id = "";
		this._settings = {};
		this._items = [];
		this._encodeItems = true;
		this._onSelectNotifier = null;
		this._popup = null;
		this._isOpened = false;
		this._itemSelectHandler = BX.delegate(this.onItemSelect, this);
	};
	BX.CmrSelectorMenu.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : ("crm_selector_menu_" + Math.random().toString().substring(2));
			this._settings = settings ? settings : {};

			this._encodeItems = !!this.getSetting("encodeItems", true);
			var itemData = this.getSetting("items");
			itemData = BX.type.isArray(itemData) ? itemData : [];
			this._items = [];
			for(var i = 0; i < itemData.length; i++)
			{
				var item = BX.CmrSelectorMenuItem.create(itemData[i]);
				item.addOnSelectListener(this._itemSelectHandler);
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
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		isOpened: function()
		{
			return this._isOpened;
		},
		open: function(anchor)
		{
			if(this._isOpened)
			{
				return;
			}

			var menuItems = [];
			for(var i = 0; i < this._items.length; i++)
			{
				var item = this._items[i];
				if(item.isEnabled())
				{
					menuItems.push(item.createMenuItem(this._encodeItems));
				}
			}

			BX.PopupMenu.show(
				this._id,
				anchor,
				menuItems,
				{
					"offsetTop": 0,
					"offsetLeft": 0,
					"events":
					{
						"onPopupShow": BX.delegate(this.onPopupShow, this),
						"onPopupClose": BX.delegate(this.onPopupClose, this),
						"onPopupDestroy": BX.delegate(this.onPopupDestroy, this)
					}
				}
			);
			this._popup = BX.PopupMenu.currentItem;
		},
		close: function()
		{
			if (this._popup && this._popup.popupWindow)
			{
				this._popup.popupWindow.close();
			}
		},
		addOnSelectListener: function(listener)
		{
			this._onSelectNotifier.addListener(listener);
		},
		removeOnSelectListener: function(listener)
		{
			this._onSelectNotifier.removeListener(listener);
		},
		onItemSelect: function(item)
		{
			this.close();
			this._onSelectNotifier.notify([item]);
		},
		onPopupShow: function()
		{
			this._isOpened = true;
		},
		onPopupClose: function()
		{
			if(this._popup)
			{
				if(this._popup.popupWindow)
				{
					this._popup.popupWindow.destroy();
				}
			}
		},
		onPopupDestroy: function()
		{
			this._isOpened = false;
			this._popup = null;

			if(typeof(BX.PopupMenu.Data[this._id]) !== "undefined")
			{
				delete(BX.PopupMenu.Data[this._id]);
			}
		}
	};
	BX.CmrSelectorMenu.create = function(id, settings)
	{
		var self = new BX.CmrSelectorMenu();
		self.initialize(id, settings);
		return self;
	};
}
//endregion

if(typeof(BX.CrmSelector) === "undefined")
{
	BX.CrmSelector = function()
	{
		this._id = "";
		this._selectedValue = "";
		this._settings = {};
		this._outerWrapper = this._wrapper = this._container = this._view = null;
		this._items = [];
		this._encodeItems = true;
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

			this._encodeItems = !!this.getSetting("encodeItems", true);
			var itemData = this.getSetting("items");
			itemData = BX.type.isArray(itemData) ? itemData : [];
			this._items = [];
			for(var i = 0; i < itemData.length; i++)
			{
				var item = BX.CmrSelectorMenuItem.create(itemData[i]);
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

			var offset = BX.type.isPlainObject(layout['offset']) ? layout['offset'] : {};
			if(BX.type.isNotEmptyString(offset['left']))
			{
				outerWrapper.style.marginLeft = offset['left'];
			}
			if(BX.type.isNotEmptyString(offset['right']))
			{
				outerWrapper.style.marginRight = offset['right'];
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

			var text = selectItem ? selectItem.getText() : "";
			if(this._encodeItems)
			{
				text = BX.util.htmlspecialchars(text);
			}

			var view = this._view = BX.create(
				"SPAN",
				{
					"attrs":
					{
						"className": "crm-selector-view"
					},
					"html": text
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
				var text = item.getText();
				if(this._encodeItems)
				{
					text = BX.util.htmlspecialchars(text);
				}
				this._view.innerHTML = text;
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
					menuItems.push(item.createMenuItem(this._encodeItems));
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
		_onItemSelect: function (item)
		{
			this.selectItem(item);

			if (this._popup)
			{
				if (this._popup.popupWindow)
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
		getIntParam: function(name, defaultvalue)
		{
			if(typeof(defaultvalue) === "undefined")
			{
				defaultvalue = 0;
			}
			var p = this._params;
			return typeof(p[name]) != "undefined" ? parseInt(p[name]) : defaultvalue;
		},
		getBooleanParam: function(name, defaultvalue)
		{
			if(typeof(defaultvalue) === "undefined")
			{
				defaultvalue = 0;
			}
			var p = this._params;
			return typeof(p[name]) != "undefined" ? !!p[name] : defaultvalue;
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
	};

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
		this._typeName = '';
	};

	BX.CrmMultiFieldViewer.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._layout = this.getSetting('layout', 'grid').toLowerCase();
			this._typeName = this.getSetting('typeName', '');

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


			var className = 'bx-crm-grid-multi-field-viewer';
			var enableSip = false;
			var items = this.getSetting('items', []);
			for(var i = 0; i < items.length; i++)
			{
				var item = items[i];

				var r = tab.insertRow(-1);
				var valueCell = r.insertCell(-1);

				var itemHtml = item['value'];
				var itemClassName = "crm-client-contacts-block-text";
				if(this._typeName === "PHONE" && BX.type.isNotEmptyString(item['sipCallHtml']))
				{
					if(!enableSip)
					{
						enableSip = true;
					}
					itemHtml += item['sipCallHtml'];
				}
				valueCell.appendChild(BX.create('SPAN', { attrs: { className: itemClassName }, html: itemHtml }));
				var typeCell = r.insertCell(-1);
				typeCell.appendChild(
					BX.create(
						'SPAN',
						{
							attrs: { className: 'crm-multi-field-value-type' },
							text: BX.type.isNotEmptyString(item['type']) ? item['type'] : ''
						}
					)
				);
			}

			if(enableSip)
			{
				className += ' bx-crm-grid-multi-field-viewer-tel-sip';
			}

			tab.className = className;

			var dlg = BX.CrmMultiFieldViewer.dialogs[this._id] ? BX.CrmMultiFieldViewer.dialogs[this._id] : null;
			if(!dlg)
			{
				var anchor = this.getSetting('anchor');
				if(!BX.type.isElementNode(anchor))
				{
					anchor = BX(this.getSetting('anchorId', ''));
				}

				var topmost = !!this.getSetting('topmost', false);
				dlg = new BX.PopupWindow(
					this._id,
					anchor,
					{
						autoHide: true,
						draggable: false,
						offsetLeft: 0,
						offsetTop: 0,
						bindOptions: { forceBindPosition: true },
						closeByEsc: true,
						zIndex: topmost ? -10 : -14,
						className: 'crm-item-popup-num-block',
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
		},
		close: function()
		{
			if(this._shown && typeof(BX.CrmMultiFieldViewer.dialogs[this._id]) !== 'undefined')
			{
				BX.CrmMultiFieldViewer.dialogs[this._id].close();
			}
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

if(typeof(BX.CrmSipManager) === "undefined")
{
	BX.CrmSipManager = function()
	{
		this._id = "";
		this._settings = null;
		this._serviceUrls = {};
		this._recipientInfos = {};
	};

	BX.CrmSipManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : BX.CrmParamBag.create(null);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultvalue)
		{
			return this._settings.getParam(name, defaultvalue);
		},
		setSetting: function(name, value)
		{
			return this._settings.setParam(name, value);
		},
		openPreCallDialog: function(recipient, params, anchor, callback)
		{
			if(!recipient || typeof(recipient) !== "object")
			{
				return;
			}

			if(!params || typeof(params) !== "object")
			{
				params = {};
			}

			var entityType = BX.type.isNotEmptyString(params["ENTITY_TYPE"]) ? params["ENTITY_TYPE"] : "";
			var entityId = BX.type.isNotEmptyString(params["ENTITY_ID"]) ? params["ENTITY_ID"] : "";
			var dlgId = entityType + '_' + entityId.toString();

			var dlg = BX.CrmPreCallDialog.create(dlgId,
				BX.CrmParamBag.create(
					{
						recipient: recipient,
						params: params,
						anchor: anchor,
						closeCallback: callback
					}
				)
			);
			dlg.show();
		},
		setServiceUrl: function(entityTypeName, serviceUrl)
		{
			if(BX.type.isNotEmptyString(entityTypeName) && BX.type.isNotEmptyString(serviceUrl))
			{
				this._serviceUrls[entityTypeName] = serviceUrl;
			}
		},
		getServiceUrl: function(entityTypeName)
		{
			return BX.type.isNotEmptyString(entityTypeName)
				&& this._serviceUrls.hasOwnProperty(entityTypeName)
				? this._serviceUrls[entityTypeName] : "";
		},
		makeCall: function(recipient, params)
		{
			var number = BX.type.isNotEmptyString(recipient["number"]) ? recipient["number"] : "";
			if(number == "")
			{
				return;
			}

			var entityTypeName = BX.type.isNotEmptyString(params["ENTITY_TYPE"]) ? params["ENTITY_TYPE"] : "";
			var entityId = BX.type.isNotEmptyString(params["ENTITY_ID"]) ? parseInt(params["ENTITY_ID"]) : 0;
			if(!(entityTypeName !== "" && entityId > 0))
			{
				entityTypeName = BX.type.isNotEmptyString(recipient["entityTypeName"]) ? recipient["entityTypeName"] : "";
				if(entityTypeName !== "")
				{
					entityTypeName = "CRM_" + entityTypeName.toUpperCase();
				}
				params["ENTITY_TYPE"] = entityTypeName;
				params["ENTITY_ID"] = typeof(recipient["entityId"]) !== "undefined" ? parseInt(recipient["entityId"]) : 0;
			}

			var handlers = [];
			BX.onCustomEvent(
				window,
				'CRM_SIP_MANAGER_MAKE_CALL',
				[this, recipient, params, handlers]
			);

			if(BX.type.isArray(handlers) && handlers.length > 0)
			{
				for(var i = 0; i < handlers.length; i++)
				{
					var handler = handlers[i];
					if(BX.type.isFunction(handler))
					{
						try
						{
							handler(recipient, params);
						}
						catch(ex)
						{
						}
					}
				}
			}
			else if(typeof(window["BXIM"]) !== "undefined")
			{
				window["BXIM"].phoneTo(number, params);
			}
		},
		startCall: function(recipient, params, enablePreCallDialog, anchor)
		{
			enablePreCallDialog = !!enablePreCallDialog;
			if(enablePreCallDialog)
			{
				var enableInfoLoading = typeof(recipient["enableInfoLoading"]) ? recipient["enableInfoLoading"] : false;
				if(enableInfoLoading)
				{
					var entityType = BX.type.isNotEmptyString(params["ENTITY_TYPE"]) ? params["ENTITY_TYPE"] : "";
					var entityId = "";
					if(BX.type.isNotEmptyString(params["ENTITY_ID"]) || BX.type.isNumber(params["ENTITY_ID"]))
					{
						entityId = params["ENTITY_ID"];
					}

					var key = entityType + '_' + entityId.toString();
					if(this._recipientInfos.hasOwnProperty(key))
					{
						var info = this._recipientInfos[key];
						recipient["title"] = BX.type.isNotEmptyString(info["title"]) ? info["title"] : "";
						recipient["legend"] = BX.type.isNotEmptyString(info["legend"]) ? info["legend"] : "";
						recipient["imageUrl"] = BX.type.isNotEmptyString(info["imageUrl"]) ? info["imageUrl"] : "";
						recipient["showUrl"] = BX.type.isNotEmptyString(info["showUrl"]) ? info["showUrl"] : "";
					}
					else
					{
						var serviceUrl = this.getServiceUrl(
							BX.type.isNotEmptyString(params["ENTITY_TYPE"]) ? params["ENTITY_TYPE"] : ""
						);

						if(serviceUrl !== "")
						{
							var loader = BX.CrmSipRecipientInfoLoader.create(
								BX.CrmParamBag.create(
									{
										serviceUrl: serviceUrl,
										recipient: recipient,
										params: params,
										anchor: anchor,
										callback: BX.delegate(this._onRecipientInfoLoad, this)
									}
								)
							);
							loader.process();
							return;
						}
					}
				}

				this.openPreCallDialog(recipient, params, anchor, BX.delegate(this._onPreCallDialogClose, this));
			}
			else
			{
				this.makeCall(recipient, params);
			}
		},
		getMessage: function(name)
		{
			return BX.CrmSipManager.messages && BX.CrmSipManager.messages.hasOwnProperty(name) ? BX.CrmSipManager.messages[name] : "";
		},
		_onPreCallDialogClose: function(dlg, recipient, params, settings)
		{
			if(!params || typeof(params) !== "object")
			{
				params = {};
			}
			this.makeCall(recipient, params);
		},
		_onRecipientInfoLoad: function(loader, recipient, params, anchor, info)
		{
			var entityType = BX.type.isNotEmptyString(params["ENTITY_TYPE"]) ? params["ENTITY_TYPE"] : "";
			var entityId = BX.type.isNotEmptyString(params["ENTITY_ID"]) ? params["ENTITY_ID"] : "";
			var key = entityType + '_' + entityId.toString();
			this._recipientInfos[key] = info;

			recipient["title"] = BX.type.isNotEmptyString(info["title"]) ? info["title"] : "";
			recipient["legend"] = BX.type.isNotEmptyString(info["legend"]) ? info["legend"] : "";
			recipient["imageUrl"] = BX.type.isNotEmptyString(info["imageUrl"]) ? info["imageUrl"] : "";
			recipient["showUrl"] = BX.type.isNotEmptyString(info["showUrl"]) ? info["showUrl"] : "";

			this.openPreCallDialog(recipient, params, anchor, BX.delegate(this._onPreCallDialogClose, this));
		}
	};

	BX.CrmSipManager.items = {};
	BX.CrmSipManager.create = function(id, settings)
	{
		var self = new BX.CrmSipManager();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	};
	BX.CrmSipManager.current = null;
	BX.CrmSipManager.getCurrent = function()
	{
		if(!this._current)
		{
			this._current = this.create("_CURRENT", null);
		}

		return this._current;
	};
	BX.CrmSipManager.startCall = function(recipient, params, enablePreCallDialog, anchor)
	{
		this.getCurrent().startCall(recipient, params, enablePreCallDialog, anchor);
	};
	BX.CrmSipManager.resolveSipEntityTypeName = function(typeName)
	{
		return BX.type.isNotEmptyString(typeName) ? ("CRM_" + typeName.toUpperCase()) : "";
	}
}

if(typeof(BX.CrmSipRecipientInfoLoader) === "undefined")
{
	BX.CrmSipRecipientInfoLoader = function()
	{
		this._settings = null;
		this._serviceUrl = null;
		this._recipient = null;
		this._params = null;
		this._anchor = null;
		this._callBack = null;
	};

	BX.CrmSipRecipientInfoLoader.prototype =
	{
		initialize: function(settings)
		{
			this._settings = settings ? settings : BX.CrmParamBag.create(null);

			this._serviceUrl = this.getSetting("serviceUrl", "");

			this._recipient = this.getSetting("recipient");
			if(!this._recipient)
			{
				this._recipient = {};
			}

			this._params = this.getSetting("params");
			if(!this._params)
			{
				this._params = {};
			}

			this._anchor = this.getSetting("anchor", null);

			this._callBack = this.getSetting("callback");
			if(!BX.type.isFunction(this._callBack))
			{
				this._callBack = null;
			}
		},
		getSetting: function(name, defaultvalue)
		{
			return this._settings.getParam(name, defaultvalue);
		},
		setSetting: function(name, value)
		{
			return this._settings.setParam(name, value);
		},
		process: function()
		{
			var params = this._params;
			var entityTypeName = BX.type.isNotEmptyString(params["ENTITY_TYPE"]) ? params["ENTITY_TYPE"] : "";
			var entityId = typeof(params["ENTITY_ID"]) !== "undefined" ? parseInt(params["ENTITY_ID"]) : 0;
			var serviceUrl = this._serviceUrl;
			var callBack = this._callBack;

			if(entityTypeName  === "" || entityId <= 0 || serviceUrl === "")
			{
				if(BX.type.isFunction(this._callBack))
				{
					callBack(this, this._recipient, this._params, this._anchor, {});
				}
				return;
			}

			BX.ajax(
				{
					url: serviceUrl,
					method: "POST",
					dataType: "json",
					data:
					{
						"MODE" : "GET_ENTITY_SIP_INFO",
						"ENITY_TYPE" : entityTypeName,
						"ENITY_ID" : entityId
					},
					onsuccess: BX.delegate(this._onSuccess, this)
					//onfailure: function(data){}
				}
			);
		},
		_onSuccess: function(result)
		{
			var callBack = this._callBack;
			if(!BX.type.isFunction(callBack))
			{
				return;
			}

			var data = typeof(result["DATA"]) !== "undefined" ? result["DATA"] : {};
			var title = BX.type.isNotEmptyString(data["TITLE"]) ? data["TITLE"] : "";
			var legend = BX.type.isNotEmptyString(data["LEGEND"]) ? data["LEGEND"] : "";
			var imageUrl = BX.type.isNotEmptyString(data["IMAGE_URL"]) ? data["IMAGE_URL"] : "";
			var showUrl = BX.type.isNotEmptyString(data["SHOW_URL"]) ? data["SHOW_URL"] : "";

			try
			{
				callBack(
					this,
					this._recipient,
					this._params,
					this._anchor,
					{ title: title, legend: legend, showUrl: showUrl, imageUrl: imageUrl }
				);
			}
			catch(ex)
			{
			}
		}
	};

	BX.CrmSipRecipientInfoLoader.create = function(settings)
	{
		var self = new BX.CrmSipRecipientInfoLoader();
		self.initialize(settings);
		return self;
	};
}

if(typeof(BX.CrmPreCallDialog) === "undefined")
{
	BX.CrmPreCallDialog = function()
	{
		this._id = "";
		this._settings = null;
		this._recipient = null;
		this._params = null;
		this._anchor = null;
		this._dlg = null;
		this._isShown = false;
		this._makeCallButton = null;
		this._closeCallBack = null;
		this._onMakeCallButtonClickHandler = BX.delegate(this._onMakeCallButtonClick, this);
	};

	BX.CrmPreCallDialog.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : BX.CrmParamBag.create(null);

			this._recipient = this.getSetting("recipient");
			if(!this._recipient)
			{
				this._recipient = {};
			}

			this._params = this.getSetting("params");
			if(!this._params)
			{
				this._params = {};
			}

			this._anchor = this.getSetting("anchor", null);

			this._closeCallBack = this.getSetting("closeCallback");
			if(!BX.type.isFunction(this._closeCallBack))
			{
				this._closeCallBack = null;
			}
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
		getMessage: function(name)
		{
			return BX.CrmSipManager.messages && BX.CrmSipManager.messages.hasOwnProperty(name) ? BX.CrmSipManager.messages[name] : "";
		},
		show: function()
		{
			if(this._isShown)
			{
				return;
			}

			this._dlg = BX.PopupWindowManager.create(
				this._id.toLowerCase() + "-pre-call",
				this._anchor,
				{
					content: this._preparePreCallDialogContent(),
					closeIcon: true,
					closeByEsc: true,
					lightShadow: true,
					angle:{ offset: 5 },
					zIndex: 200, //For balloons
					events:
					{
						onPopupClose: BX.delegate(this._onDialogClose, this)
					}
				}
			);

			if(!this._dlg.isShown())
			{
				this._dlg.show();
			}
			this._isShown = this._dlg.isShown();
		},
		close: function()
		{
			if(!this._isShown)
			{
				return;
			}

			if(this._dlg)
			{
				this._dlg.close();
				this._isShown = this._dlg.isShown();
			}
			else
			{
				this._isShown = false;
			}
		},
		_preparePreCallDialogContent: function()
		{
			var recipient = this._recipient;

			var container = BX.create(
				"DIV",
				{ attrs: { className: "crm-tel-popup" } }
			);

			var userWrapper = BX.create(
				"DIV",
				{ attrs: { className: "crm-tel-popup-user" } }
			);
			container.appendChild(userWrapper);

			var userAvatar = BX.create(
					"DIV",
					{ attrs: { className: "crm-tel-avatar" } }
			);
			var imageUrl = BX.type.isNotEmptyString(recipient["imageUrl"]) ? recipient["imageUrl"] : "";
			if(imageUrl !== "")
			{
				userAvatar.style.background = "url(" + imageUrl + ") no-repeat 3px 3px";
			}

			userWrapper.appendChild(userAvatar);
			userWrapper.appendChild(
				BX.create("DIV", { attrs: { className: "crm-tel-user-alignment" } })
			);

			var title = BX.type.isNotEmptyString(recipient["title"]) ? recipient["title"] : this.getMessage("unknownRecipient");
			var legend = BX.type.isNotEmptyString(recipient["legend"]) ? recipient["legend"] : "";
			var showUrl = BX.type.isNotEmptyString(recipient["showUrl"]) ? recipient["showUrl"] : "#";
			userWrapper.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-tel-user-data" },
						children:
						[
							BX.create("A",
								{
									attrs: { className: "crm-tel-user-name", target: "_blank", href: showUrl },
									text: title
								}
							),
							BX.create("DIV",
								{
									attrs: { className: "crm-tel-user-organ" },
									text: legend
								}
							)
						]
					}
				)
			);

			var number = BX.type.isNotEmptyString(recipient["number"]) ? recipient["number"] : "-";
			var chkBxId = this._id.toLowerCase() + "_enable_recordind";

			var settingsWrapper = BX.create(
				"DIV",
				{
					attrs: { className: "crm-tel-popup-num-block" },
					children:
					[
						BX.create("DIV",
							{
								attrs: { className: "crm-tel-popup-num" },
								text: number
							}
						)
					]
				}
			);
			container.appendChild(settingsWrapper);

			var buttonWrapper = BX.create(
				"DIV",
				{ attrs: { className: "crm-tel-popup-footer" } }
			);
			container.appendChild(buttonWrapper);

			this._makeCallButton = BX.create("SPAN",
				{
					attrs: { className: "crm-tel-popup-call-btn" },
					text: this.getMessage("makeCall")
				}
			);
			BX.bind(this._makeCallButton, "click", this._onMakeCallButtonClickHandler);
			buttonWrapper.appendChild(this._makeCallButton);

			return container;
		},
		_onMakeCallButtonClick: function(e)
		{
			if(!this._isShown)
			{
				return;
			}

			if(this._dlg)
			{
				this._dlg.close();
			}
			this._isShown = this._dlg ? this._dlg.isShown() : false;

			BX.unbind(this._makeCallButton, "click", this._onMakeCallButtonClickHandler);

			if(this._closeCallBack)
			{
				try
				{
					this._closeCallBack(this, this._recipient, this._params, {});
				}
				catch(ex)
				{
				}
			}
		},
		_onDialogClose: function(e)
		{
			if(this._dlg)
			{
				this._dlg.destroy();
				this._dlg = null;
			}

			this._isShown = false;
		}
	};

	BX.CrmPreCallDialog.create = function(id, settings)
	{
		var self = new BX.CrmPreCallDialog();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmBizprocDispatcher) == "undefined")
{
	BX.CrmBizprocDispatcher = function()
	{
		this._id = "";
		this._settings = {};
		this._container = null;
		this._wrapper = null;
		this._serviceUrl = "";
		this._entityTypeName = "";
		this._entityId = 0;
		this._formId = "";
		this._tabId = "tab_bizproc";
		this._currentPage = "";
		this._formManager = null;

		this._isRequestRunning = false;
		this._isLoaded = false;

		this._waiter = null;
		this._scrollHandler = BX.delegate(this._onWindowScroll, this);
		this._formManagerHandler = BX.delegate(this._onFormManagerCreate, this);
	};

	BX.CrmBizprocDispatcher.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_bp_disp_" + Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			this._container = BX(this.getSetting("containerID", ""));
			if(!this._container)
			{
				throw "BX.CrmBizprocDispatcher. Could not find container.";
			}
			this._wrapper = BX.findParent(this._container, { "tagName": "DIV", "className": "bx-edit-tab-inner" });

			this._serviceUrl = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw "BX.CrmBizprocDispatcher. Could not find service url.";
			}

			this._entityTypeName = this.getSetting("entityTypeName", "");
			if(!BX.type.isNotEmptyString(this._entityTypeName))
			{
				throw "BX.CrmBizprocDispatcher. Could not find entity type name.";
			}

			this._entityId = parseInt(this.getSetting("entityID", 0));
			if(!BX.type.isNumber(this._entityId) || this._entityId <= 0)
			{
				throw "BX.CrmBizprocDispatcher. Could not find entity id.";
			}

			this._formId = this.getSetting("formID", "");
			if(!BX.type.isNotEmptyString(this._formId))
			{
				throw "BX.CrmBizprocDispatcher. Could not find form id.";
			}

			var formManager = window["bxForm_" + this._formId];
			if(formManager)
			{
				this.setFormManager(formManager);
			}
			else
			{
				BX.addCustomEvent(window, "CrmInterfaceFormCreated", this._formManagerHandler);
			}

			this._currentPage = this.getSetting("currentPage", "");
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		getContainerRect: function()
		{
			var r = this._container.getBoundingClientRect();
			return(
				{
					top: r.top, bottom: r.bottom, left: r.left, right: r.right,
					width: typeof(r.width) !== "undefined" ? r.width : (r.right - r.left),
					height: typeof(r.height) !== "undefined" ? r.height : (r.bottom - r.top)
				}
			);
		},
		isContanerInClientRect: function()
		{
			return this.getContainerRect().top <= document.documentElement.clientHeight;
		},
		setFormManager: function(formManager)
		{
			if(this._formManager === formManager)
			{
				return;
			}

			this._formManager = formManager;
			if(!this._formManager)
			{
				return;
			}

			if(this._formManager.GetActiveTabId() !== this._tabId)
			{
				BX.addCustomEvent(window, 'BX_CRM_INTERFACE_FORM_TAB_SELECTED', BX.delegate(this._onFormTabSelect, this));
			}
			else
			{
				if(this.isContanerInClientRect())
				{
					this.loadIndex();
				}
				else
				{
					BX.bind(window, "scroll", this._scrollHandler);
				}
			}
		},
		loadIndex: function()
		{
			if(this._isLoaded)
			{
				return;
			}

			if(this._currentPage === "index")
			{
				return;
			}

			var result = this._startRequest(
				"INDEX",
				{
					"FORM_ID": this.getSetting("formID", ""),
					"PATH_TO_ENTITY_SHOW": this.getSetting("pathToEntityShow", "")
				}
			);

			if(result)
			{
				this._currentPage = "index";
			}
		},
		_startRequest: function(action, params)
		{
			if(this._isRequestRunning)
			{
				return false;
			}

			this._isRequestRunning = true;
			this._waiter = BX.showWait(this._container);
			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "html",
					data:
					{
						"ACTION" : action,
						"ENTITY_TYPE_NAME": this._entityTypeName,
						"ENTITY_ID": this._entityId,
						"PARAMS": params
					},
					onsuccess: BX.delegate(this._onRequestSuccess, this),
					onfailure: BX.delegate(this._onRequestFailure, this)
				}
			);

			return true;
		},
		_onRequestSuccess: function(data)
		{
			this._isRequestRunning = false;

			if(this._waiter)
			{
				BX.closeWait(this._container, this._waiter);
				this._waiter = null;
			}

			this._container.innerHTML = data;
			this._isLoaded = true;
		},
		_onRequestFailure: function(data)
		{
			this._isRequestRunning = false;

			if(this._waiter)
			{
				BX.closeWait(this._container, this._waiter);
				this._waiter = null;
			}
			this._isLoaded = true;
		},
		_onFormManagerCreate: function(formManager)
		{
			if(formManager["name"] === this._formId)
			{
				BX.removeCustomEvent(window, "CrmInterfaceFormCreated", this._formManagerHandler);
				this.setFormManager(formManager);
			}
		},
		_onFormTabSelect: function(sender, formId, tabId, tabContainer)
		{
			if(this._formId === formId && (tabId === this._tabId || this._wrapper === tabContainer))
			{
				this.loadIndex();
			}
		},
		_onWindowScroll: function(e)
		{
			if(!this._isLoaded && !this._isRequestRunning && this.isContanerInClientRect())
			{
				BX.unbind(window, "scroll", this._scrollHandler);
				this.loadIndex();
			}
		}
	};

	BX.CrmBizprocDispatcher.items = {};
	BX.CrmBizprocDispatcher.create = function(id, settings)
	{
		var self = new BX.CrmBizprocDispatcher();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}

if(typeof(BX.CrmEntityTreeDispatcher) == 'undefined')
{
	BX.CrmEntityTreeDispatcher = function()
	{
		this._id = '';
		this._settings = {};
		this._container = null;
		this._subContainer = null;
		this._wrapper = null;
		this._serviceUrl = '';
		this._entityTypeName = '';
		this._entityId = 0;
		this._formId = '';
		this._tabId = 'tab_tree';
		this._formManager = null;

		this._isRequestRunning = false;
		this._isLoaded = false;

		this._waiter = null;
		this._scrollHandler = BX.delegate(this._onWindowScroll, this);
		this._formManagerHandler = BX.delegate(this._onFormManagerCreate, this);
	};

	BX.CrmEntityTreeDispatcher.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : 'crm_tree_disp_' + Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			this._container = BX(this.getSetting('containerID', ''));
			if(!this._container)
			{
				throw 'BX.CrmEntityTreeDispatcher. Could not find container.';
			}
			this._wrapper = BX.findParent(this._container, { 'tagName': 'DIV', 'className': 'bx-edit-tab-inner' });

			this._serviceUrl = this.getSetting('serviceUrl', '');
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw 'BX.CrmEntityTreeDispatcher. Could not find service url.';
			}

			this._entityTypeName = this.getSetting('entityTypeName', '');
			if(!BX.type.isNotEmptyString(this._entityTypeName))
			{
				throw 'BX.CrmEntityTreeDispatcher. Could not find entity type name.';
			}

			this._entityId = parseInt(this.getSetting('entityID', 0));
			if(!BX.type.isNumber(this._entityId) || this._entityId <= 0)
			{
				throw 'BX.CrmEntityTreeDispatcher. Could not find entity id.';
			}

			this._formId = this.getSetting('formID', '');
			if(!BX.type.isNotEmptyString(this._formId))
			{
				throw 'BX.CrmEntityTreeDispatcher. Could not find form id.';
			}

			var formManager = window['bxForm_' + this._formId];
			if(formManager)
			{
				this.setFormManager(formManager);
				if (settings.selected === true)
				{
					formManager.SelectTab(this._tabId);
				}
			}
			else
			{
				BX.addCustomEvent(window, 'CrmInterfaceFormCreated', this._formManagerHandler);
			}

			this._moreButtonClickHandler = BX.delegate(this._handleMoreButtonClickHandler, this);
			this._entityButtonClickHandler = BX.delegate(this._handleEntityButtonClickHandler, this);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		getContainerRect: function()
		{
			var r = this._container.getBoundingClientRect();
			return(
				{
					top: r.top, bottom: r.bottom, left: r.left, right: r.right,
					width: typeof(r.width) !== 'undefined' ? r.width : (r.right - r.left),
					height: typeof(r.height) !== 'undefined' ? r.height : (r.bottom - r.top)
				}
			);
		},
		isContanerInClientRect: function()
		{
			return this.getContainerRect().top <= document.documentElement.clientHeight;
		},
		setFormManager: function(formManager)
		{
			if(this._formManager === formManager)
			{
				return;
			}

			this._formManager = formManager;
			if(!this._formManager)
			{
				return;
			}

			if(this._formManager.GetActiveTabId() !== this._tabId)
			{
				BX.addCustomEvent(window, 'BX_CRM_INTERFACE_FORM_TAB_SELECTED', BX.delegate(this._onFormTabSelect, this));
			}
			else
			{
				if(this.isContanerInClientRect())
				{
					this.loadIndex();
				}
				else
				{
					BX.bind(window, 'scroll', this._scrollHandler);
				}
			}
		},
		_startRequest: function(addParams)
		{
			if(this._isRequestRunning)
			{
				return false;
			}

			var params = {
				FORM_ID: this.getSetting('formID', ''),
				PATH_TO_LEAD_SHOW: this.getSetting('pathToLeadShow', ''),
				PATH_TO_CONTACT_SHOW: this.getSetting('pathToContactShow', ''),
				PATH_TO_COMPANY_SHOW: this.getSetting('pathToCompanyShow', ''),
				PATH_TO_DEAL_SHOW: this.getSetting('pathToDealShow', ''),
				PATH_TO_QUOTE_SHOW: this.getSetting('pathToQuoteShow', ''),
				PATH_TO_INVOICE_SHOW: this.getSetting('pathToInvoiceShow', ''),
				PATH_TO_USER_PROFILE: this.getSetting('pathToUserProfile', '')
			};

			params = BX.mergeEx(params, addParams);

			this._isRequestRunning = true;
			this._waiter = BX.showWait(this._container);
			BX.ajax(
				{
					url: this._serviceUrl,
					method: 'POST',
					dataType: 'html',
					data:
					{
						ADDITIONAL_PARAMS : 'active_tab=' + this._tabId,
						ENTITY_TYPE_NAME: params.ENTITY_TYPE_NAME ? params.ENTITY_TYPE_NAME : this._entityTypeName,
						ENTITY_ID: params.ENTITY_ID ? params.ENTITY_ID : this._entityId,
						PARAMS: params
					},
					onsuccess: BX.delegate(this._onRequestSuccess, this),
					onfailure: BX.delegate(this._onRequestFailure, this)
				}
			);

			return true;
		},
		_onRequestSuccess: function(data)
		{
			this._isRequestRunning = false;

			if(this._waiter)
			{
				BX.closeWait(this._container, this._waiter);
				this._waiter = null;
			}

			if (this._subContainer !== null)
			{
				BX.insertAfter(BX.create('DIV', {html: data}), this._subContainer);
			}
			else
			{
				this._container.innerHTML = data;
			}

			this._isLoaded = true;

			var _this = this;
			var moreButton = BX.findChild(this._container, {class: 'crm-entity-more'}, true, true);
			var entityButton = false;//BX.findChild(this._container, {class: 'crm-tree-link'}, true, true);
			if (moreButton)
			{
				for(var i = 0; i < moreButton.length; i++)
				{
					BX.bind(moreButton[i], 'click', this._moreButtonClickHandler);
				}
			}
			if (entityButton)
			{
				for(var i = 0; i < entityButton.length; i++)
				{
					BX.bind(entityButton[i], 'click', this._entityButtonClickHandler);
				}
			}
		},
		_onRequestFailure: function(data)
		{
			this._isRequestRunning = false;

			if(this._waiter)
			{
				BX.closeWait(this._container, this._waiter);
				this._waiter = null;
			}
			this._isLoaded = true;
		},
		_handleMoreButtonClickHandler: function()
		{
			var target = BX.proxy_context;

			this._subContainer = BX.findParent(target);

			BX.remove(target);

			var page = parseInt(BX.data(target, 'page')) + 1;
			BX.data(target, 'page', page);

			this._startRequest({
				BLOCK: BX.data(target, 'block'),
				BLOCK_PAGE: page
			});
		},
		_handleEntityButtonClickHandler: function(e)
		{
			var target = BX.proxy_context;
			this._subContainer = null;
			this._startRequest({
				ENTITY_ID: BX.data(target, 'id'),
				ENTITY_TYPE_NAME: BX.data(target, 'type')
			});
			e.preventDefault();
		},
		_onFormManagerCreate: function(formManager)
		{
			if(formManager['name'] === this._formId)
			{
				BX.removeCustomEvent(window, 'CrmInterfaceFormCreated', this._formManagerHandler);
				this.setFormManager(formManager);
			}
		},
		_onFormTabSelect: function(sender, formId, tabId, tabContainer)
		{
			if(this._formId === formId && (tabId === this._tabId || this._wrapper === tabContainer))
			{
				this._startRequest();
			}
		},
		_onWindowScroll: function(e)
		{
			if(!this._isLoaded && !this._isRequestRunning && this.isContanerInClientRect())
			{
				BX.unbind(window, 'scroll', this._scrollHandler);
				this._startRequest();
			}
		}
	};
	BX.CrmEntityTreeDispatcher.items = {};
	BX.CrmEntityTreeDispatcher.create = function(id, settings)
	{
		var self = new BX.CrmEntityTreeDispatcher();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}

if(typeof(BX.CrmLongRunningProcessState) == "undefined")
{
	BX.CrmLongRunningProcessState =
	{
		intermediate: 0,
		running: 1,
		completed: 2,
		stoped: 3,
		error: 4
	};
}

if(typeof(BX.CrmLongRunningProcessDialog) == "undefined")
{
	BX.CrmLongRunningProcessDialog = function()
	{
		this._id = "";
		this._settings = {};
		this._serviceUrl = "";
		this._params = {};
		this._dlg = null;
		this._buttons = {};
		this._summary = null;
		this._isShown = false;
		this._state = BX.CrmLongRunningProcessState.intermediate;
		this._cancelRequest = false;
		this._requestIsRunning = false;
	};
	BX.CrmLongRunningProcessDialog.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_long_run_proc_" + Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			this._serviceUrl = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw "BX.CrmLongRunningProcess. Could not find service url.";
			}

			this._action = this.getSetting("action", "");
			if(!BX.type.isNotEmptyString(this._action))
			{
				throw "BX.CrmLongRunningProcess. Could not find action.";
			}

			this._params = this.getSetting("params");
			if(!this._params)
			{
				this._params = {};
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
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		getMessage: function(name)
		{
			return BX.CrmLongRunningProcessDialog.messages && BX.CrmLongRunningProcessDialog.messages.hasOwnProperty(name) ? BX.CrmLongRunningProcessDialog.messages[name] : "";
		},
		getState: function()
		{
			return this._state;
		},
		getServiceUrl: function()
		{
			return this._serviceUrl;
		},
		getAction: function()
		{
			return this._action;
		},
		getParams: function()
		{
			return this._params;
		},
		show: function()
		{
			if(this._isShown)
			{
				return;
			}

			this._dlg = BX.PopupWindowManager.create(
				this._id.toLowerCase(),
				this._anchor,
				{
					className: "bx-crm-dialog-wrap bx-crm-dialog-long-run-proc",
					autoHide: false,
					bindOptions: { forceBindPosition: false },
					buttons: this._prepareDialogButtons(),
					//className: "",
					closeByEsc: false,
					closeIcon: false,
					content: this._prepareDialogContent(),
					draggable: true,
					events: { onPopupClose: BX.delegate(this._onDialogClose, this) },
					offsetLeft: 0,
					offsetTop: 0,
					titleBar: this.getSetting("title", "")
				}
			);
			if(!this._dlg.isShown())
			{
				this._dlg.show();
			}
			this._isShown = this._dlg.isShown();
		},
		close: function()
		{
			if(!this._isShown)
			{
				return;
			}

			if(this._dlg)
			{
				this._dlg.close();
			}
			this._isShown = false;
		},
		start: function()
		{
			if(this._state === BX.CrmLongRunningProcessState.intermediate || this._state === BX.CrmLongRunningProcessState.stoped)
			{
				this._startRequest();
			}
		},
		stop: function()
		{
			if(this._state === BX.CrmLongRunningProcessState.running)
			{
				this._cancelRequest = true;
			}
		},
		_prepareDialogContent: function()
		{
			this._summary = BX.create(
				"DIV",
				{
					attrs: { className: "bx-crm-dialog-long-run-proc-summary" },
					text: this.getSetting("summary", "")
				}
			);
			return BX.create(
				"DIV",
				{
					attrs: { className: "bx-crm-dialog-long-run-proc-popup" },
					children: [ this._summary ]
				}
			);
		},
		_prepareDialogButtons: function()
		{
			this._buttons = {};

			var startButtonText = this.getMessage("startButton");
			this._buttons["start"] = new BX.PopupWindowButton(
				{
					text: startButtonText !== "" ? startButtonText : "Start",
					className: "popup-window-button-accept",
					events:
					{
						click : BX.delegate(this._handleStartButtonClick, this)
					}
				}
			);

			var stopButtonText = this.getMessage("stopButton");
			this._buttons["stop"] = new BX.PopupWindowButton(
				{
					text: stopButtonText !== "" ? stopButtonText : "Stop",
					className: "popup-window-button-accept-disabled",
					events:
					{
						click : BX.delegate(this._handleStopButtonClick, this)
					}
				}
			);

			var closeButtonText = this.getMessage("closeButton");
			this._buttons["close"] = new BX.PopupWindowButtonLink(
				{
					text: closeButtonText !== "" ? closeButtonText : "Close",
					className: "popup-window-button-link-cancel",
					events:
					{
						click : BX.delegate(this._handleCloseButtonClick, this)
					}
				}
			);

			return [ this._buttons["start"], this._buttons["stop"], this._buttons["close"] ];
		},
		_onDialogClose: function(e)
		{
			if(this._dlg)
			{
				this._dlg.destroy();
				this._dlg = null;
			}

			this._setState(BX.CrmLongRunningProcessState.intermediate);
			this._buttons = {};
			this._summary = null;

			this._isShown = false;

			BX.onCustomEvent(this, 'ON_CLOSE', [this]);
		},
		_handleStartButtonClick: function()
		{
			this.start();
		},
		_handleStopButtonClick: function()
		{
			this.stop();
		},
		_handleCloseButtonClick: function()
		{
			if(this._state !== BX.CrmLongRunningProcessState.running)
			{
				this._dlg.close();
			}
		},
		_lockButton: function(bid, lock)
		{
			var btn = typeof(this._buttons[bid]) !== "undefined" ? this._buttons[bid] : null;
			if(!btn)
			{
				return;
			}

			if(!!lock)
			{
				BX.removeClass(btn.buttonNode, "popup-window-button-accept");
				BX.addClass(btn.buttonNode, "popup-window-button-accept-disabled");
			}
			else
			{
				BX.removeClass(btn.buttonNode, "popup-window-button-accept-disabled");
				BX.addClass(btn.buttonNode, "popup-window-button-accept");
			}
		},
		_showButton: function(bid, show)
		{
			var btn = typeof(this._buttons[bid]) !== "undefined" ? this._buttons[bid] : null;
			if(btn)
			{
				btn.buttonNode.style.display = !!show ? "" : "none";
			}
		},
		_setSummary: function(text)
		{
			if(this._summary)
			{
				this._summary.innerHTML = BX.util.htmlspecialchars(text);
			}
		},
		_setState: function(state)
		{
			if(this._state === state)
			{
				return;
			}

			this._state = state;
			if(state === BX.CrmLongRunningProcessState.intermediate || state === BX.CrmLongRunningProcessState.stoped)
			{
				this._lockButton("start", false);
				this._lockButton("stop", true);
				this._showButton("close", true);
			}
			else if(state === BX.CrmLongRunningProcessState.running)
			{
				this._lockButton("start", true);
				this._lockButton("stop", false);
				this._showButton("close", false);
			}
			else if(state === BX.CrmLongRunningProcessState.completed || state === BX.CrmLongRunningProcessState.error)
			{
				this._lockButton("start", true);
				this._lockButton("stop", true);
				this._showButton("close", true);
			}

			BX.onCustomEvent(this, 'ON_STATE_CHANGE', [this]);
		},
		_startRequest: function()
		{
			if(this._requestIsRunning)
			{
				return;
			}
			this._requestIsRunning = true;

			this._setState(BX.CrmLongRunningProcessState.running);
			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data:
					{
						"ACTION" : this._action,
						"PARAMS": this._params
					},
					onsuccess: BX.delegate(this._onRequestSuccsess, this),
					onfailure: BX.delegate(this._onRequestFailure, this)
				}
			);
		},
		_onRequestSuccsess: function(result)
		{
			this._requestIsRunning = false;

			if(!result)
			{
				this._setSummary(this.getMessage("requestError"));
				this._setState(BX.CrmLongRunningProcessState.error);
				return;
			}

			if(BX.type.isNotEmptyString(result["ERROR"]))
			{
				this._setState(BX.CrmLongRunningProcessState.error);
				this._setSummary(result["ERROR"]);
				return;
			}

			var status = BX.type.isNotEmptyString(result["STATUS"]) ? result["STATUS"] : "";
			var summary = BX.type.isNotEmptyString(result["SUMMARY"]) ? result["SUMMARY"] : "";
			if(status === "PROGRESS")
			{
				if(summary !== "")
				{
					this._setSummary(summary);
				}

				if(this._cancelRequest)
				{
					this._setState(BX.CrmLongRunningProcessState.stoped);
					this._cancelRequest = false;
				}
				else
				{
					window.setTimeout(
						BX.delegate(this._startRequest, this),
						100
					);
				}
				return;
			}

			if(status === "NOT_REQUIRED" || status === "COMPLETED")
			{
				this._setState(BX.CrmLongRunningProcessState.completed);
				if(summary !== "")
				{
					this._setSummary(summary);
				}
			}
			else
			{
				this._setSummary(this.getMessage("requestError"));
				this._setState(BX.CrmLongRunningProcessState.error);
			}

			if(this._cancelRequest)
			{
				this._cancelRequest = false;
			}
		},
		_onRequestFailure: function(result)
		{
			this._requestIsRunning = false;

			this._setSummary(this.getMessage("requestError"));
			this._setState(BX.CrmLongRunningProcessState.error);
		}
	};
	if(typeof(BX.CrmLongRunningProcessDialog.messages) == "undefined")
	{
		BX.CrmLongRunningProcessDialog.messages = {};
	}
	BX.CrmLongRunningProcessDialog.items = {};
	BX.CrmLongRunningProcessDialog.create = function(id, settings)
	{
		var self = new BX.CrmLongRunningProcessDialog();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}
if(typeof(BX.CrmEntityType) == "undefined")
{
	BX.CrmEntityType = function()
	{
	};

	BX.CrmEntityType.enumeration =
	{
		undefined: 0,
		lead: 1,
		deal: 2,
		contact: 3,
		company: 4,
		invoice: 5
	};
	BX.CrmEntityType.names =
	{
		undefined: "",
		lead: "LEAD",
		deal: "DEAL",
		contact: "CONTACT",
		company: "COMPANY",
		invoice: "INVOICE",
		quote: "QUOTE"
	};
	BX.CrmEntityType.abbreviations =
	{
		undefined: "",
		lead: "L",
		deal: "D",
		contact: "C",
		company: "CO",
		invoice: "I",
		quote: "Q"
	};
	BX.CrmEntityType.isDefined = function(typeId)
	{
		if(!BX.type.isNumber(typeId))
		{
			typeId = parseInt(typeId);
			if(isNaN(typeId))
			{
				typeId = 0;
			}
		}

		return typeId >= 0 && typeId <= 5;
	};
	BX.CrmEntityType.resolveName = function(typeId)
	{
		if(!BX.type.isNumber(typeId))
		{
			typeId = parseInt(typeId);
			if(isNaN(typeId))
			{
				typeId = 0;
			}
		}

		if(typeId === BX.CrmEntityType.enumeration.lead)
		{
			return BX.CrmEntityType.names.lead;
		}
		else if(typeId === BX.CrmEntityType.enumeration.deal)
		{
			return BX.CrmEntityType.names.deal;
		}
		else if(typeId === BX.CrmEntityType.enumeration.contact)
		{
			return BX.CrmEntityType.names.contact;
		}
		else if(typeId === BX.CrmEntityType.enumeration.company)
		{
			return BX.CrmEntityType.names.company;
		}
		else if(typeId === BX.CrmEntityType.enumeration.invoice)
		{
			return BX.CrmEntityType.names.invoice;
		}
		else
		{
			return "";
		}
	};
	BX.CrmEntityType.resolveId = function(name)
	{
		name = name.toUpperCase();
		if(name === BX.CrmEntityType.names.lead)
		{
			return this.enumeration.lead;
		}
		else if(name === BX.CrmEntityType.names.deal)
		{
			return this.enumeration.deal;
		}
		else if(name === BX.CrmEntityType.names.contact)
		{
			return this.enumeration.contact;
		}
		else if(name === BX.CrmEntityType.names.company)
		{
			return this.enumeration.company;
		}
		else if(name === BX.CrmEntityType.names.invoice)
		{
			return this.enumeration.invoice;
		}
		else
		{
			return this.enumeration.undefined;
		}
	};
	BX.CrmEntityType.resolveAbbreviation = function(name)
	{
		name = name.toUpperCase();
		if(name === BX.CrmEntityType.names.lead)
		{
			return this.abbreviations.lead;
		}
		else if(name === BX.CrmEntityType.names.deal)
		{
			return this.abbreviations.deal;
		}
		else if(name === BX.CrmEntityType.names.contact)
		{
			return this.abbreviations.contact;
		}
		else if(name === BX.CrmEntityType.names.company)
		{
			return this.abbreviations.company;
		}
		else if(name === BX.CrmEntityType.names.invoice)
		{
			return this.abbreviations.invoice;
		}
		else
		{
			return this.abbreviations.undefined;
		}
	};
	BX.CrmEntityType.verifyName = function(name)
	{
		if(!BX.type.isNotEmptyString(name))
		{
			return "";
		}

		name = name.toUpperCase();
		return (this.resolveId(name) !== this.enumeration.undefined ? name : "");
	};

	BX.CrmEntityType.setCaptions = function(captions)
	{
		if(BX.type.isPlainObject(captions))
		{
			this.captions = captions;
		}
	};

	BX.CrmEntityType.getCaption = function(typeId)
	{
		var name = this.resolveName(typeId);
		return (this.captions.hasOwnProperty(name) ? this.captions[name] : name);
	};
	BX.CrmEntityType.getCaptionByName = function(name)
	{
		if(!BX.type.isNotEmptyString(name))
		{
			return "";
		}

		name = name.toUpperCase();
		return (this.captions.hasOwnProperty(name) ? this.captions[name] : name);
	};
	BX.CrmEntityType.prepareEntityKey = function(entityTypeName, entityId)
	{
		var abbr = this.resolveAbbreviation(entityTypeName);
		return abbr !== "" ? (abbr + "_" + entityId.toString()) : "";
	};

	if(typeof(BX.CrmEntityType.captions) === "undefined")
	{
		BX.CrmEntityType.captions = {};
	}
	if(typeof(BX.CrmEntityType.categoryCaptions) === "undefined")
	{
		BX.CrmEntityType.categoryCaptions = {};
	}
}
if(typeof(BX.CrmDuplicateManager) == "undefined")
{
	BX.CrmDuplicateManager = function()
	{
		this._id = "";
		this._settings = {};
		this._entityTypeName = "";
		this._processDialogs = {};
	};
	BX.CrmDuplicateManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_dp_mgr_" + Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			this._entityTypeName = this.getSetting("entityTypeName", "");
			if(!BX.type.isNotEmptyString(this._entityTypeName))
			{
				throw "BX.CrmDuplicateManager. Could not find entity type name.";
			}

			this._entityTypeName = this._entityTypeName.toUpperCase();
		},
		getId: function()
		{
			return this._id;
		},
		getEntityTypeName: function()
		{
			return this._entityTypeName;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		getMessage: function(name)
		{
			return BX.CrmDuplicateManager.messages && BX.CrmDuplicateManager.messages.hasOwnProperty(name) ? BX.CrmDuplicateManager.messages[name] : "";
		},
		rebuildIndex: function()
		{
			var serviceUrl = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(serviceUrl))
			{
				throw "BX.CrmDuplicateManager. Could not find service url.";
			}

			var entityTypeNameC = this._entityTypeName.toLowerCase().replace(/(?:^)\S/, function(c){ return c.toUpperCase(); });
			var key = "rebuild" + entityTypeNameC + "Index";

			var processDlg = null;
			if(typeof(this._processDialogs[key]) !== "undefined")
			{
				processDlg = this._processDialogs[key];
			}
			else
			{
				processDlg = BX.CrmLongRunningProcessDialog.create(
					key,
					{
						serviceUrl: serviceUrl,
						action:"REBUILD_DUPLICATE_INDEX",
						params:{ "ENTITY_TYPE_NAME": this._entityTypeName },
						title: this.getMessage(key + "DlgTitle"),
						summary: this.getMessage(key + "DlgSummary")
					}
				);

				this._processDialogs[key] = processDlg;
				BX.addCustomEvent(processDlg, 'ON_STATE_CHANGE', BX.delegate(this._onProcessStateChange, this));
			}
			processDlg.show();
		},
		_onProcessStateChange: function(sender)
		{
			var key = sender.getId();
			if(typeof(this._processDialogs[key]) !== "undefined")
			{
				var processDlg = this._processDialogs[key];
				if(processDlg.getState() === BX.CrmLongRunningProcessState.completed)
				{
					//ON_LEAD_INDEX_REBUILD_COMPLETE, ON_COMPANY_INDEX_REBUILD_COMPLETE, ON_CONTACT_INDEX_REBUILD_COMPLETE
					BX.onCustomEvent(this, "ON_" + this._entityTypeName + "_INDEX_REBUILD_COMPLETE", [this]);
				}
			}
		}
	};
	if(typeof(BX.CrmDuplicateManager.messages) == "undefined")
	{
		BX.CrmDuplicateManager.messages = {};
	}
	BX.CrmDuplicateManager.items = {};
	BX.CrmDuplicateManager.create = function(id, settings)
	{
		var self = new BX.CrmDuplicateManager();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}
if(typeof(BX.CrmDupController) == "undefined")
{
	BX.CrmDupController = function()
	{
		this._id = "";
		this._settings = {};
		this._entityTypeName = "";
		this._enable = true;
		this._groups = {};
		this._requestIsRunning = false;
		this._request = null;
		this._searchData = {};
		this._searchSummary = null;
		this._warningDialog = null;
		this._submits = [];
		this._lastSummaryGroupId = "";
		this._lastSummaryFieldId = "";
		this._lastSubmit = null;
		this._onFormSubmitHandler = BX.delegate(this._onFormSubmit, this);
	};
	BX.CrmDupController.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_dp_ctrl_" + Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			this._serviceUrl = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw "BX.CrmDupController. Could not find service url.";
			}

			this._bind();

			this._entityTypeName = this.getSetting("entityTypeName", "");
			var groups = this.getSetting("groups", null);
			var group = null;
			if(groups)
			{
				for(var key in groups)
				{
					if(!groups.hasOwnProperty(key))
					{
						continue;
					}

					group = groups[key];
					var type = BX.type.isNotEmptyString(group["groupType"]) ? group["groupType"] : "";
					var ctrl = null;
					try
					{
						if(type === "single")
						{
							ctrl = BX.CrmDupCtrlSingleField.create(key, group);
						}
						else if(type === "fullName")
						{
							ctrl = BX.CrmDupCtrlFullName.create(key, group);
						}
						else if(type === "communication")
						{
							ctrl = BX.CrmDupCtrlCommunication.create(key, group);
						}
					}
					catch(ex)
					{
					}

					if(ctrl)
					{
						this.addGroup(ctrl);
					}
				}
			}

			this._afterInitialize();

			var groupParams = [];
			for(var groupId in this._groups)
			{
				if(!this._groups.hasOwnProperty(groupId))
				{
					continue;
				}

				group = this._groups[groupId];
				var params = group.prepareSearchParams();
				if(!params)
				{
					continue;
				}

				params["GROUP_ID"] = groupId;
				params["HASH_CODE"] = group.getSearchHashCode();
				params["FIELD_ID"] = group.getDefaultSearchSummaryFieldId();

				groupParams.push(params);
			}

			if(groupParams.length > 0)
			{
				this._search({ "GROUPS": groupParams });
			}
		},
		_afterInitialize: function()
		{
		},
		getId: function()
		{
			return this._id;
		},
		getEntityTypeName: function()
		{
			return this._entityTypeName;
		},
		isEnabled: function()
		{
			return this._enable;
		},
		enable: function(enable)
		{
			this._enable = !!enable;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		addGroup: function(group)
		{
			this._groups[group.getId()] = group;
			group.setController(this);
			return group;
		},
		getGroup: function(groupId)
		{
			return this._groups.hasOwnProperty(groupId) ? this._groups[groupId] : null;
		},
		getDuplicateData: function()
		{
			return this._searchData;
		},
		hasDuplicates: function()
		{
			for(var key in this._searchData)
			{
				if(!this._searchData.hasOwnProperty(key))
				{
					continue;
				}

				var data = this._searchData[key];
				if(data.hasOwnProperty("items") && data["items"].length > 0)
				{
					return true;
				}
			}
			return false;
		},
		processGroupChange: function(group, field)
		{
			var groupId =  group.getId();

			var params = group.prepareSearchParams();
			if(!params)
			{
				if(typeof(this._searchData[groupId]) !== "undefined")
				{
					delete this._searchData[groupId];
					this._refreshSearchSummary(groupId, field.getId());
				}
				return;
			}

			var hashCode = group.getSearchHashCode();
			if(hashCode !== this._getGroupSearchHashCode(groupId))
			{
				params["GROUP_ID"] = groupId;
				if(field)
				{
					params["FIELD_ID"] = field.getId();
				}

				params["HASH_CODE"] = hashCode;
				this._search({ "GROUPS": [ params ] });
			}
		},
		_bind: function()
		{
			var submits = this.getSetting("submits", []);
			if(BX.type.isArray(submits))
			{
				for(var i = 0; i < submits.length; i++)
				{
					var submit = BX(submits[i]);
					if(BX.type.isElementNode(submit))
					{
						this._submits.push(submit);
						BX.bind(submit, "click", this._onFormSubmitHandler);
					}
				}
			}
		},
		_unbind: function()
		{
			for(var i = 0; i < this._submits.length; i++)
			{
				BX.unbind(this._submits[i], "click", this._onFormSubmitHandler);
			}
		},
		_search: function(params)
		{
			if(this._requestIsRunning)
			{
				this._stopSearchRequest();
			}
			params["ENTITY_TYPE_NAME"] = this._entityTypeName;
			this._startSearchRequest(params);
		},
		_startSearchRequest: function(params)
		{
			if(this._requestIsRunning)
			{
				return;
			}

			BX.showWait();
			this._requestIsRunning = true;
			this._request = BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data:
						{
							"ACTION" : "FIND_DUPLICATES",
							"PARAMS": params
						},
					onsuccess: BX.delegate(this._onSearchRequestSuccsess, this),
					onfailure: BX.delegate(this._onSearchRequestFailure, this)
				}
			);
		},
		_stopSearchRequest: function()
		{
			if(!this._requestIsRunning)
			{
				return;
			}
			this._requestIsRunning = false;
			if(this._request)
			{
				this._request.abort();
				this._request = null;
			}

			BX.closeWait();
		},
		_onSearchRequestSuccsess: function(result)
		{
			BX.closeWait();
			this._requestIsRunning = false;

			if(!result)
			{
				//var error = getMessage("generalError");
				//Show error
				return;
			}

			if(BX.type.isNotEmptyString(result["ERROR"]))
			{
				//var error = result["ERROR"];
				//Show error
				return;
			}

			var lastGroupId = "";
			var lastFieldId = "";
			var groupResults = BX.type.isArray(result["GROUP_RESULTS"]) ? result["GROUP_RESULTS"] : [];
			for(var i = 0; i < groupResults.length; i++)
			{
				var groupResult = groupResults[i];
				var groupId = typeof(groupResult["GROUP_ID"]) !== "undefined" ? groupResult["GROUP_ID"] : "";
				if(!BX.type.isNotEmptyString(groupId))
				{
					return;
				}

				var group = this.getGroup(groupId);
				if(!group)
				{
					return;
				}

				if(typeof(this._searchData[groupId]) === "undefined")
				{
					this._searchData[groupId] = {};
				}

				var items = BX.type.isArray(groupResult["DUPLICATES"]) ? groupResult["DUPLICATES"] : [];
				if(items.length > 0)
				{
					this._searchData[groupId]["items"] = BX.type.isArray(groupResult["DUPLICATES"]) ? groupResult["DUPLICATES"] : [];

					this._searchData[groupId]["totalText"] =
						BX.type.isNotEmptyString(groupResult["ENTITY_TOTAL_TEXT"]) ? groupResult["ENTITY_TOTAL_TEXT"] : "";

					var hash = 0;
					if(typeof(groupResult["HASH_CODE"]) !== "undefined")
					{
						hash = parseInt(groupResult["HASH_CODE"]);
						if(isNaN(hash))
						{
							hash = 0;
						}
					}
					this._searchData[groupId]["hash"] = hash;

					if(BX.type.isNotEmptyString(groupResult["FIELD_ID"]))
					{
						lastGroupId = groupId;
						lastFieldId = groupResult["FIELD_ID"];
					}
				}
				else
				{
					delete this._searchData[groupId];
				}
			}
			this._refreshSearchSummary(lastGroupId, lastFieldId);
		},
		_refreshSearchSummary: function(groupId, fieldId)
		{
			if(!BX.type.isNotEmptyString(groupId))
			{
				groupId = "";
			}

			if(!BX.type.isNotEmptyString(fieldId))
			{
				fieldId = "";
			}

			if(this.hasDuplicates())
			{
				var anchorField = null;
				if(groupId === "" || fieldId === "")
				{
					groupId = this._lastSummaryGroupId;
					fieldId = this._lastSummaryFieldId;
				}
				if(groupId !== "" && fieldId !== "")
				{
					var group = this.getGroup(groupId);
					if(group)
					{
						anchorField = group.getField(fieldId);
					}

					this._lastSummaryGroupId = groupId;
					this._lastSummaryFieldId = fieldId;
				}
				this._showSearchSummary(anchorField);
			}
			else
			{
				this._closeSearchSummary();
			}
		},
		_onSearchRequestFailure: function(result)
		{
			BX.closeWait();
			this._requestIsRunning = false;
			//var error = getMessage("generalError");
			//Show error
		},
		_onFormSubmit: function(e)
		{
			if(!this.hasDuplicates())
			{
				return true;
			}

			var submit = null;
			if(e)
			{
				if(e.target)
				{
					submit = e.target;
				}
				else if(e.srcElement)
				{
					submit = e.srcElement;
				}
			}

			if(BX.type.isElementNode(submit))
			{
				this._lastSubmit = submit;
			}

			window.setTimeout(BX.delegate(this._openWarningDialog, this), 100);
			return BX.PreventDefault(e);
		},
		_openWarningDialog: function()
		{
			if(!this.hasDuplicates())
			{
				this._unbind();
				this._submitForm();
			}
			else
			{
				this._warningDialog = BX.CrmDuplicateWarningDialog.create(
					this._id + "_warn",
					{
						"controller": this,
						"onClose": BX.delegate(this._onWarningDialogClose, this),
						"onCancel": BX.delegate(this._onWarningDialogCancel, this),
						"onAccept": BX.delegate(this._onWarningDialogAccept, this)
					}
				);
				this._warningDialog.show();
			}
		},
		_getGroupSearchData: function(groupId)
		{
			return this._searchData.hasOwnProperty(groupId) ? this._searchData[groupId] : null;
		},
		_getGroupSearchHashCode: function(groupId)
		{
			var data = this._getGroupSearchData(groupId);
			return (data && data.hasOwnProperty("hash")) ? data["hash"] : 0;
		},
		_showSearchSummary: function(anchorField)
		{
			this._closeSearchSummary();

			var anchor = null;
			if(anchorField)
			{
				anchor = anchorField ? anchorField.getElementTitle() : null;
				if(!anchor)
				{
					anchor = anchorField.getElement();
				}
			}

			this._searchSummary = BX.CrmDuplicateSummaryPopup.create(
				this._id + "_summary",
				{
					"controller": this,
					"anchor": anchor
				}
			);
			this._searchSummary.show();
		},
		_isSearchSummaryShown: function()
		{
			return this._searchSummary && this._searchSummary.isShown();
		},
		_closeSearchSummary: function()
		{
			if(this._searchSummary)
			{
				this._searchSummary.close();
				this._searchSummary = null;
			}
		},
		_onWarningDialogClose: function(dlg)
		{
			if(this._warningDialog === dlg)
			{
				this._warningDialog = null;
			}
		},
		_onWarningDialogCancel: function(dlg)
		{
			if(this._warningDialog === dlg)
			{
				this._warningDialog.close();
			}
		},
		_onWarningDialogAccept: function(dlg)
		{
			if(this._warningDialog === dlg)
			{
				this._warningDialog.close();
				this._unbind();
				this._submitForm();
			}
		},
		_submitForm: function()
		{
			if(BX.type.isElementNode(this._lastSubmit))
			{
				this._lastSubmit.click();
			}
			else
			{
				var form = BX(this.getSetting("form", ""));
				if(BX.type.isElementNode(form))
				{
					form.submit();
				}
			}
		}
	};
	BX.CrmDupController.create = function(id, settings)
	{
		var self = new BX.CrmDupController();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmDupCtrlField) == "undefined")
{
	BX.CrmDupCtrlField = function()
	{
		this._id = "";
		this._group = null;
		this._element = null;
		this._elementTitle = null;
		this._value = "";
		this._hasFosus = false;
		this._elementTimeoutId = 0;
		this._elementTimeoutHandler = BX.delegate(this._onElementTimeout, this);
		this._elementKeyUpHandler = BX.delegate(this._onElementKeyUp, this);
		this._elementFocusHandler = BX.delegate(this._onElementFocus, this);
		this._elementBlurHandler = BX.delegate(this._onElementBlur, this);
		this._initialized = false;
	};
	BX.CrmDupCtrlField.prototype =
	{
		initialize: function(id, element, elementTitle)
		{
			if(!BX.type.isNotEmptyString(id))
			{
				throw "BX.CrmDupCtrlField. Invalid parameter 'id': is not defined.";
			}
			this._id = id;

			if(!BX.type.isElementNode(element))
			{
				throw "BX.CrmDupCtrlField. Invalid parameter 'element': is not defined.";
			}
			this._element = element;
			this._value = element.value;

			BX.bind(this._element, "keyup", this._elementKeyUpHandler);
			BX.bind(this._element, "focus", this._elementFocusHandler);
			BX.bind(this._element, "blur", this._elementBlurHandler);

			if(BX.type.isElementNode(elementTitle))
			{
				this._elementTitle = elementTitle;
			}

			this._initialized = true;
		},
		release: function()
		{
			BX.unbind(this._element, "keyup", this._elementKeyUpHandler);
			BX.unbind(this._element, "focus", this._elementFocusHandler);
			BX.unbind(this._element, "blur", this._elementBlurHandler);
			this._element = null;

			this._initialized = false;
		},
		getId: function()
		{
			return this._id;
		},
		getGroup: function()
		{
			return this._group;
		},
		setGroup: function(group)
		{
			this._group = group;
		},
		hasFocus: function()
		{
			return this._hasFosus;
		},
		getElement: function()
		{
			return this._element;
		},
		getElementTitle: function()
		{
			return this._elementTitle;
		},
		getValue: function()
		{
			return this._element.value;
		},
		_onElementKeyUp: function(e)
		{
			var c = e.keyCode;
			if(c === 13 || c === 27 || (c >=37 && c <= 40) || (c >=112 && c <= 123))
			{
				return;
			}

			if(this._value === this._element.value)
			{
				return;
			}
			this._value = this._element.value;

			if(this._elementTimeoutId > 0)
			{
				window.clearTimeout(this._elementTimeoutId);
				this._elementTimeoutId = 0;
			}
			this._elementTimeoutId = window.setTimeout(this._elementTimeoutHandler, 1500);

			if(!this._hasFosus)
			{
				this._hasFosus = true;
			}
		},
		_onElementFocus: function(e)
		{
			this._hasFosus = true;
			if(this._group)
			{
				this._group.processFieldFocusGain(this);
			}
		},
		_onElementBlur: function(e)
		{
			if(this._elementTimeoutId > 0)
			{
				window.clearTimeout(this._elementTimeoutId);
				this._elementTimeoutId = 0;
			}

			this._hasFosus = false;
			if(this._group)
			{
				this._group.processFieldFocusLoss(this);
			}
		},
		_onElementTimeout: function()
		{
			if(this._elementTimeoutId <= 0)
			{
				return;
			}

			this._elementTimeoutId = 0;
			if(this._group)
			{
				this._group.processFieldDelay(this);
			}
		}
	};
	BX.CrmDupCtrlField.create = function(id, element, elementTitle)
	{
		var self = new BX.CrmDupCtrlField();
		self.initialize(id, element, elementTitle);
		return self;
	}
}
if(typeof(BX.CrmDupCtrlFieldGroup) == "undefined")
{
	BX.CrmDupCtrlFieldGroup = function()
	{
		this._id = "";
		this._settings = {};
		this._controller = null;
		this._fields = {};
	};
	BX.CrmDupCtrlFieldGroup.prototype =
	{
		initialize: function(id, settings)
		{
			if(!BX.type.isNotEmptyString(id))
			{
				throw "BX.CrmDupCtrlFieldGroup. Invalid parameter 'id': is not defined.";
			}
			this._id = id;

			this._settings = settings ? settings : {};
			this._afterInitialize();
		},
		_afterInitialize: function()
		{
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		getController: function()
		{
			return this._controller;
		},
		setController: function(controller)
		{
			this._controller = controller;
		},
		addField: function(field)
		{
			this._fields[field.getId()] = field;
			field.setGroup(this);
			return field;
		},
		getField: function(fieldId)
		{
			return this._fields.hasOwnProperty(fieldId) ? this._fields[fieldId] : null;
		},
		getFieldValues: function()
		{
			var result = [];
			for(var key in this._fields)
			{
				if(this._fields.hasOwnProperty(key))
				{
					var value = BX.util.trim(this._fields[key].getValue());
					if(value !== "")
					{
						result.push(value);
					}
				}
			}
			return result;
		},
		clearFields: function()
		{
			for(var key in this._fields)
			{
				if(this._fields.hasOwnProperty(key))
				{
					this._fields[key].release();
				}
			}
			this._fields = {};
		},
		getSummaryTitle: function()
		{
			return this.getSetting("groupSummaryTitle", "");
		},
		prepareSearchParams: function()
		{
			return null;
		},
		getSearchHashCode: function()
		{
			return 0;
		},
		getDefaultSearchSummaryFieldId: function()
		{
			return "";
		},
		processFieldDelay: function(field)
		{
		},
		processFieldFocusGain: function(field)
		{
		},
		processFieldFocusLoss: function(field)
		{
		}
	};
}
if(typeof(BX.CrmDupCtrlSingleField) == "undefined")
{
	BX.CrmDupCtrlSingleField = function()
	{
		BX.CrmDupCtrlSingleField.superclass.constructor.apply(this);
		this._paramName = "";
		this._field = null;
	};
	BX.extend(BX.CrmDupCtrlSingleField, BX.CrmDupCtrlFieldGroup);
	BX.CrmDupCtrlSingleField.prototype._afterInitialize = function()
	{
		this._paramName = this.getSetting("parameterName", "");
		if(!BX.type.isNotEmptyString(this._paramName))
		{
			throw "BX.CrmDupCtrlSingleField. Could not find parameter name.";
		}

		var element = BX(this.getSetting("element", null));
		if(BX.type.isDomNode(element))
		{
			this._field = this.addField(BX.CrmDupCtrlField.create(this._paramName, element, BX(this.getSetting("elementCaption", null))));
		}
	};
	BX.CrmDupCtrlSingleField.prototype.getValue = function()
	{
		return this._field ? BX.util.trim(this._field.getValue()) : "";
	};
	BX.CrmDupCtrlSingleField.prototype.prepareSearchParams = function()
	{
		var value = this.getValue();
		if(value === "")
		{
			return null;
		}

		var result = {};
		result[this._paramName] = value;
		return result;
	};
	BX.CrmDupCtrlSingleField.prototype.getSearchHashCode = function()
	{
		var value = this.getValue();
		if(value === "")
		{
			return 0;
		}
		return BX.util.hashCode(value);
	};
	BX.CrmDupCtrlSingleField.prototype.getDefaultSearchSummaryFieldId = function()
	{
		return this._field ? this._field.getId() : ""
	};
	BX.CrmDupCtrlSingleField.prototype.processFieldDelay = function(field)
	{
		this._fireChangeEvent(field);
	};
	BX.CrmDupCtrlSingleField.prototype.processFieldFocusLoss = function(field)
	{
		this._fireChangeEvent(field);
	};
	BX.CrmDupCtrlSingleField.prototype._fireChangeEvent = function(field)
	{
		if(this._controller)
		{
			this._controller.processGroupChange(this, field);
		}
	};
	BX.CrmDupCtrlSingleField.create = function(id, settings)
	{
		var self = new BX.CrmDupCtrlSingleField();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmDupCtrlFullName) == "undefined")
{
	BX.CrmDupCtrlFullName = function()
	{
		BX.CrmDupCtrlFullName.superclass.constructor.apply(this);
		this._nameField = null;
		this._secondNameField = null;
		this._lastNameField = null;
	};

	BX.extend(BX.CrmDupCtrlFullName, BX.CrmDupCtrlFieldGroup);
	BX.CrmDupCtrlFullName.prototype._afterInitialize = function()
	{
		var element = BX(this.getSetting("name", null));
		if(BX.type.isDomNode(element))
		{
			this._nameField = this.addField(BX.CrmDupCtrlField.create("NAME", element, BX(this.getSetting("nameCaption", null))));
		}
		element = BX(this.getSetting("secondName", null));
		if(BX.type.isDomNode(element))
		{
			this._secondNameField = this.addField(BX.CrmDupCtrlField.create("SECOND_NAME", element, BX(this.getSetting("secondNameCaption", null))));
		}
		element = BX(this.getSetting("lastName", null));
		if(BX.type.isDomNode(element))
		{
			this._lastNameField = this.addField(BX.CrmDupCtrlField.create("LAST_NAME", element, BX(this.getSetting("lastNameCaption", null))));
		}
	};
	BX.CrmDupCtrlFullName.prototype.getName = function()
	{
		return this._nameField ? BX.util.trim(this._nameField.getValue()) : "";
	};
	BX.CrmDupCtrlFullName.prototype.getSecondName = function()
	{
		return this._secondNameField ? BX.util.trim(this._secondNameField.getValue()) : "";
	};
	BX.CrmDupCtrlFullName.prototype.getLastName = function()
	{
		return this._lastNameField ? BX.util.trim(this._lastNameField.getValue()) : "";
	};
	BX.CrmDupCtrlFullName.prototype.prepareSearchParams = function()
	{
		var lastName = this.getLastName();
		if(lastName === "")
		{
			return null;
		}

		var result = { "LAST_NAME": lastName };
		var name = this.getName();
		if(name !== "")
		{
			result["NAME"] = name;
		}
		var secondName = this.getSecondName();
		if(secondName !== "")
		{
			result["SECOND_NAME"] = secondName;
		}

		return result;
	};
	BX.CrmDupCtrlFullName.prototype.getSearchHashCode = function()
	{
		var lastName = this.getLastName();
		if(lastName === "")
		{
			return 0;
		}

		var key = lastName.toLowerCase();
		var name = this.getName();
		if(name !== "")
		{
			key += "$" + name.toLowerCase();
		}

		var secondName = this.getSecondName();
		if(secondName !== "")
		{
			key += "$" + secondName.toLowerCase();
		}

		return BX.util.hashCode(key);
	};
	BX.CrmDupCtrlFullName.prototype.getDefaultSearchSummaryFieldId = function()
	{
		return this._lastNameField ? this._lastNameField.getId() : ""
	};
	BX.CrmDupCtrlFullName.prototype.processFieldDelay = function(field)
	{
		this._fireChangeEvent(field);
	};
	BX.CrmDupCtrlFullName.prototype.processFieldFocusLoss = function(field)
	{
		this._fireChangeEvent(field);
	};
	BX.CrmDupCtrlFullName.prototype._fireChangeEvent = function(field)
	{
		if(this._controller)
		{
			this._controller.processGroupChange(this, field);
		}
	};
	BX.CrmDupCtrlFullName.create = function(id, settings)
	{
		var self = new BX.CrmDupCtrlFullName();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmDupCtrlCommunication) == "undefined")
{
	BX.CrmDupCtrlCommunication = function()
	{
		this._communicationType = "";
		this._container = null;
		this._editorCreateItemHandler = BX.delegate(this.onCommunicaionEditorItemCreate, this);
		this._editorDeleteItemHandler = BX.delegate(this.onCommunicaionEditorItemDelete, this);
		this._firstField = null;
		this._lastField = null;

		BX.CrmDupCtrlCommunication.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmDupCtrlCommunication, BX.CrmDupCtrlFieldGroup);
	BX.CrmDupCtrlCommunication.prototype._afterInitialize = function()
	{
		this._communicationType = this.getSetting("communicationType", "");
		if(!BX.type.isNotEmptyString(this._communicationType))
		{
			throw "BX.CrmDupCtrlCommunication. Could not find communication type.";
		}

		this._editorId = this.getSetting("editorId", "");
		if(!BX.type.isNotEmptyString(this._editorId))
		{
			throw "BX.CrmDupCtrlCommunication. Could not find editor Id.";
		}

		this._container = this.getSetting("container", null);
		if(BX.type.isNotEmptyString(this._container))
		{
			this._container = BX(this._container);
		}
		if(!BX.type.isElementNode(this._container))
		{
			this._container = BX(this._editorId);
		}
		if(!BX.type.isElementNode(this._container))
		{
			throw "BX.CrmDupCtrlCommunication. Could not find container.";
		}

		BX.addCustomEvent(window, "CrmFieldMultiEditorItemCreated", this._editorCreateItemHandler);
		BX.addCustomEvent(window, "CrmFieldMultiEditorItemDeleted", this._editorDeleteItemHandler);

		this._initializeFields();
	};
	BX.CrmDupCtrlCommunication.prototype._initializeFields = function()
	{
		this.clearFields();

		var caption = BX(this.getSetting("editorCaption", null));
		var inputs = BX.findChildren(this._container, { tagName: "input", className: "bx-crm-edit-input" }, true);
		var length = inputs.length;
		for(var i = 0; i < length; i++)
		{
			var field = this.addField(BX.CrmDupCtrlField.create("VALUE_" + (i + 1).toString(), inputs[i], caption));
			if(i === 0)
			{
				this._firstField = field;
			}
			if(i === (length - 1))
			{
				this._lastField = field;
			}
		}
	};
	BX.CrmDupCtrlCommunication.prototype.prepareSearchParams = function()
	{
		var rawValues = this.getFieldValues();
		var length = rawValues.length;
		if(length === 0)
		{
			return null;
		}

		var result = {};
		if(this._communicationType !== "PHONE")
		{
			result[this._communicationType] = rawValues;
			return result;
		}

		var values = [];
		for(var i = 0; i < length; i++)
		{
			var value = rawValues[i];
			if(value.length >= 5)
			{
				values.push(value);
			}
		}

		if(values.length === 0)
		{
			return null;
		}

		result["PHONE"] = values;
		return result;
	};
	BX.CrmDupCtrlCommunication.prototype.getSearchHashCode = function()
	{
		var values = this.getFieldValues();
		return (values.length > 0 ? BX.util.hashCode(values.join("$")) : 0);
	};
	BX.CrmDupCtrlCommunication.prototype.getDefaultSearchSummaryFieldId = function()
	{
		return this._firstField ? this._firstField.getId() : ""
	};
	BX.CrmDupCtrlCommunication.prototype.processFieldDelay = function(field)
	{
		if(this._controller)
		{
			this._controller.processGroupChange(this, field);
		}
	};
	BX.CrmDupCtrlCommunication.prototype.processFieldFocusLoss = function(field)
	{
		if(this._controller)
		{
			this._controller.processGroupChange(this, field);
		}
	};
	BX.CrmDupCtrlCommunication.prototype.onCommunicaionEditorItemCreate = function(sender, editorId)
	{
		if(this._editorId !== editorId)
		{
			return;
		}

		this._initializeFields();

		//if(this._controller)
		//{
		//	this._controller.processGroupChange(this, field);
		//}
	};
	BX.CrmDupCtrlCommunication.prototype.onCommunicaionEditorItemDelete = function(sender, editorId)
	{
		if(this._editorId !== editorId)
		{
			return;
		}

		this._initializeFields();

		if(this._controller)
		{
			this._controller.processGroupChange(this, this._lastField);
		}
	};
	BX.CrmDupCtrlCommunication.create = function(id, settings)
	{
		var self = new BX.CrmDupCtrlCommunication();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmDuplicateSummaryItem) == "undefined")
{
	BX.CrmDuplicateSummaryItem = function()
	{
		this._id = "";
		this._settings = {};
		this._groupId = "";
		this._controller = null;
		this._container = null;
		//this._popup = null;
	};
	BX.CrmDuplicateSummaryItem.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._controller = this.getSetting("controller", null);
			if(!this._controller)
			{
				throw "BX.CrmDuplicateListPopup. Parameter 'controller' is not found.";
			}

			this._container = this.getSetting("container", null);
			if(!this._controller)
			{
				throw "BX.CrmDuplicateSummaryItem. Parameter 'container' is not found.";
			}

			this._link = this.getSetting("link", null);
			if(!this._link)
			{
				throw "BX.CrmDuplicateSummaryItem. Parameter 'link' is not found.";
			}
			BX.bind(this._link, "click", BX.delegate(this._onLinkClick, this));

			this._groupId = this.getSetting("groupId", null);
		},
		getSetting: function (name, defaultval)
		{
			return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		_onLinkClick: function(e)
		{
			if(this._groupId !== "")
			{
				var popup = BX.CrmDuplicateListPopup.create(
					this._id,
					{
						controller: this._controller,
						groupId: this._groupId
					}
				);
				popup.show();
			}
		}
	};
	BX.CrmDuplicateSummaryItem.create = function(id, settings)
	{
		var self = new BX.CrmDuplicateSummaryItem();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmDuplicateSummaryPopup) == "undefined")
{
	BX.CrmDuplicateSummaryPopup = function()
	{
		this._id = "";
		this._settings = {};
		this._controller = null;
		this._items = {};
		this._popup = null;
	};
	BX.CrmDuplicateSummaryPopup.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._controller = this.getSetting("controller", null);
			if(!this._controller)
			{
				throw "BX.CrmDuplicateSummaryPopup. Parameter 'controller' is not found.";
			}
		},
		getSetting: function (name, defaultval)
		{
			return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		getId: function()
		{
			return this._id;
		},
		show: function()
		{
			if(this.isShown())
			{
				return;
			}

			var id = this.getId();
			if(BX.CrmDuplicateSummaryPopup.windows[id])
			{
				BX.CrmDuplicateSummaryPopup.windows[id].destroy();
			}

			var anchor = this.getSetting("anchor", null);
			this._popup = new BX.PopupWindow(
				id,
				anchor,
				{
					autoHide: false,
					draggable: true,
					bindOptions: { forceBindPosition: false },
					closeByEsc: false,
					events:
					{
						//onPopupShow: BX.delegate(this._onPopupShow, this),
						onPopupClose: BX.delegate(this._onPopupClose, this),
						onPopupDestroy: BX.delegate(this._onPopupDestroy, this)
					},
					content: this._prepareContent(),
					className : "crm-tip-popup",
					angle: { position: "right" },
					lightShadow : true
				}
			);

			BX.CrmDuplicateSummaryPopup.windows[id] = this._popup;
			this._popup.show();

			//move to left
			var anchorPos = BX.pos(anchor);
			var anglePos = BX.pos(this._popup.angle.element);
			var popupPos = BX.pos(this._popup.popupContainer);

			var offsetX = this._popup.popupContainer.offsetWidth + anglePos.width + 5;
			var offsetY = anchorPos.height + (anglePos.height + this._popup.angle.element.offsetTop) / 2;

			if(offsetX < popupPos.left && offsetY < popupPos.top)
			{
				this._popup.move(-offsetX, -offsetY);
			}
		},
		close: function()
		{
			if(!(this._popup && this._popup.isShown()))
			{
				return;
			}

			this._popup.close();
		},
		isShown: function()
		{
			return this._popup && this._popup.isShown();
		},
		getMessage: function(name)
		{
			return BX.CrmDuplicateSummaryPopup.messages && BX.CrmDuplicateSummaryPopup.messages.hasOwnProperty(name) ? BX.CrmDuplicateSummaryPopup.messages[name] : "";
		},
		_prepareContent: function()
		{
			this._items = {};
			var infos = {};
			var data = this._controller.getDuplicateData();
			var groupId;
			for(groupId in data)
			{
				if(!data.hasOwnProperty(groupId))
				{
					continue;
				}

				var groupData = data[groupId];
				if(BX.type.isNotEmptyString(groupData["totalText"]))
				{
					infos[groupId] = { total: groupData["totalText"] };
				}
			}

			//crm-tip-popup-cont
			var wrapper = BX.create(
				"DIV",
				{
					attrs: { className: "crm-tip-popup-cont" }
				}
			);

			var titleIsAdded = false;
			for(groupId in infos)
			{
				if(!infos.hasOwnProperty(groupId))
				{
					continue;
				}

				var group = this._controller.getGroup(groupId);
				if(!group)
				{
					continue;
				}

				var itemLink = BX.create(
					"SPAN",
					{
						attrs: { className: "crm-tip-popup-link" },
						text: infos[groupId]["total"]
					}
				);

				var itemContainer =
					BX.create("DIV",
						{
							attrs: { className: "crm-tip-popup-item" }
						}
					);

				if(!titleIsAdded)
				{
					itemContainer.appendChild(
						BX.create("SPAN",
							{
								text: this.getMessage("title") + " "
							}
						)
					);
					titleIsAdded = true;
				}

				itemContainer.appendChild(itemLink);
				itemContainer.appendChild(
					BX.create("SPAN",
						{
							text: " " + group.getSummaryTitle()
						}
					)
				);
				wrapper.appendChild(itemContainer);

				this._items[groupId] = BX.CrmDuplicateSummaryItem.create(
					groupId,
					{
						controller: this._controller,
						container: itemContainer,
						link: itemLink,
						groupId: groupId
					}
				);
			}
			return wrapper;
		},
		_onPopupClose: function()
		{
			if(this._popup)
			{
				this._popup.destroy();
			}
		},
		_onPopupDestroy: function()
		{
			if(this._popup)
			{
				this._popup = null;
			}
		}
	};
	BX.CrmDuplicateSummaryPopup.windows = {};
	if(typeof(BX.CrmDuplicateSummaryPopup.messages) == "undefined")
	{
		BX.CrmDuplicateSummaryPopup.messages = {};
	}
	BX.CrmDuplicateSummaryPopup.create = function(id, settings)
	{
		var self = new BX.CrmDuplicateSummaryPopup();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmDuplicateWarningDialog) == "undefined")
{
	BX.CrmDuplicateWarningDialog = function()
	{
		this._id = "";
		this._settings = {};
		this._controller = null;
		this._popup = null;
		this._contentWrapper = null;
	};
	BX.CrmDuplicateWarningDialog.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._controller = this.getSetting("controller", null);
			if(!this._controller)
			{
				throw "BX.CrmDuplicateWarningDialog. Parameter 'controller' is not found.";
			}
		},
		getSetting: function (name, defaultval)
		{
			return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		getId: function()
		{
			return this._id;
		},
		show: function()
		{
			if(this.isShown())
			{
				return;
			}

			var id = this.getId();
			if(BX.CrmDuplicateWarningDialog.windows[id])
			{
				BX.CrmDuplicateWarningDialog.windows[id].destroy();
			}

			var anchor = this.getSetting("anchor", null);
			this._popup = new BX.PopupWindow(
				id,
				anchor,
				{
					autoHide: false,
					draggable: true,
					bindOptions: { forceBindPosition: false },
					closeByEsc: true,
					closeIcon : {
						marginRight:"4px",
						marginTop:"9px"
					},
					titleBar: this.getMessage("title"),
					events:
					{
						onPopupShow: BX.delegate(this._onPopupShow, this),
						onPopupClose: BX.delegate(this._onPopupClose, this),
						onPopupDestroy: BX.delegate(this._onPopupDestroy, this)
					},
					content: this._prepareContent(),
					className : "crm-tip-popup",
					lightShadow : true,
					buttons: [
						new BX.PopupWindowButton(
							{
								text : this.getMessage("acceptButtonTitle"),
								className : "popup-window-button-create",
								events:
								{
									click: BX.delegate(this._onAcceptButtonClick, this)
								}
							}
						),
						new BX.PopupWindowButtonLink(
							{
								text : this.getMessage("cancelButtonTitle"),
								className : "webform-button-link-cancel",
								events:
								{
									click: BX.delegate(this._onCancelButtonClick, this)
								}
							}
						)
					]
				}
			);

			BX.CrmDuplicateWarningDialog.windows[id] = this._popup;
			this._popup.show();
			this._contentWrapper.tabIndex = "1";
			this._contentWrapper.focus();
		},
		close: function()
		{
			if(!(this._popup && this._popup.isShown()))
			{
				return;
			}

			this._popup.close();
		},
		isShown: function()
		{
			return this._popup && this._popup.isShown();
		},
		getMessage: function(name)
		{
			return BX.CrmDuplicateWarningDialog.messages && BX.CrmDuplicateWarningDialog.messages.hasOwnProperty(name) ? BX.CrmDuplicateWarningDialog.messages[name] : "";
		},
		_prepareContent: function()
		{
			this._contentWrapper = BX.CrmDuplicateRenderer.prepareListContent(this._controller.getDuplicateData());
			return this._contentWrapper;
		},
		_onCancelButtonClick: function()
		{
			var handler = this.getSetting("onCancel", null);
			if(BX.type.isFunction(handler))
			{
				handler(this);
			}
		},
		_onAcceptButtonClick: function()
		{
			var handler = this.getSetting("onAccept", null);
			if(BX.type.isFunction(handler))
			{
				handler(this);
			}
		},
		_onPopupShow: function()
		{
			if(!this._contentWrapper)
			{
				return;
			}

			var userWrappers = BX.findChildren(
				this._contentWrapper,
				{ className: "crm-info-popup-user"  },
				true
			);
			if(userWrappers)
			{
				for(var i = 0; i < userWrappers.length; i++)
				{
					var element = userWrappers[i];
					BX.tooltip(element.getAttribute("data-userid"), element, "");
				}
			}

			BX.bind(this._contentWrapper, "keyup", BX.delegate(this._onKeyUp, this))
		},
		_onPopupClose: function()
		{
			var handler = this.getSetting("onClose", null);
			if(BX.type.isFunction(handler))
			{
				handler(this);
			}

			if(this._popup)
			{
				this._popup.destroy();
			}
		},
		_onPopupDestroy: function()
		{
			if(this._popup)
			{
				this._popup = null;
			}
		},
		_onKeyUp: function(e)
		{
			var c = e.keyCode;
			if(c === 13)
			{
				var handler = this.getSetting("onAccept", null);
				if(BX.type.isFunction(handler))
				{
					handler(this);
				}
			}
		}
	};
	BX.CrmDuplicateWarningDialog.windows = {};
	if(typeof(BX.CrmDuplicateWarningDialog.messages) === "undefined")
	{
		BX.CrmDuplicateWarningDialog.messages = {};
	}
	BX.CrmDuplicateWarningDialog.create = function(id, settings)
	{
		var self = new BX.CrmDuplicateWarningDialog();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmDuplicateListPopup) === "undefined")
{
	BX.CrmDuplicateListPopup = function()
	{
		this._id = "";
		this._settings = {};
		this._controller = null;
		this._groupId = "";
		this._popup = null;
		this._contentWrapper = null;
	};
	BX.CrmDuplicateListPopup.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._controller = this.getSetting("controller", null);
			if(!this._controller)
			{
				throw "BX.CrmDuplicateListPopup. Parameter 'controller' is not found.";
			}

			this._groupId = this.getSetting("groupId", null);
		},
		getSetting: function (name, defaultval)
		{
			return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		getId: function()
		{
			return this._id;
		},
		show: function()
		{
			if(this.isShown())
			{
				return;
			}

			var id = this.getId();
			if(BX.CrmDuplicateListPopup.windows[id])
			{
				BX.CrmDuplicateListPopup.windows[id].destroy();
			}

			var anchor = this.getSetting("anchor", null);
			this._popup = new BX.PopupWindow(
				id,
				anchor,
				{
					autoHide: true,
					draggable: false,
					bindOptions: { forceBindPosition: false },
					closeByEsc: true,
					closeIcon :
					{
						marginRight:"-2px",
						marginTop:"3px"
					},
					events:
					{
						onPopupShow: BX.delegate(this._onPopupShow, this),
						onPopupClose: BX.delegate(this._onPopupClose, this),
						onPopupDestroy: BX.delegate(this._onPopupDestroy, this)
					},
					content: this._prepareContent(),
					lightShadow : true,
					className : "crm-tip-popup"
				}
			);

			BX.CrmDuplicateListPopup.windows[id] = this._popup;
			this._popup.show();
		},
		close: function()
		{
			if(!(this._popup && this._popup.isShown()))
			{
				return;
			}

			this._popup.close();
		},
		isShown: function()
		{
			return this._popup && this._popup.isShown();
		},
		getMessage: function(name)
		{
			return BX.CrmDuplicateListPopup.messages && BX.CrmDuplicateListPopup.messages.hasOwnProperty(name) ? BX.CrmDuplicateListPopup.messages[name] : "";
		},
		_prepareContent: function()
		{
			this._contentWrapper = BX.CrmDuplicateRenderer.prepareListContent(
				this._controller.getDuplicateData(),
				{
					groupId: this._groupId,
					classes: [ "crm-cont-info-popup-light" ]
				}
			);
			return this._contentWrapper;
		},
		_onPopupShow: function()
		{
			if(!this._contentWrapper)
			{
				return;
			}

			var userWrappers = BX.findChildren(
				this._contentWrapper,
				{ className: "crm-info-popup-user"  },
				true
			);
			if(userWrappers)
			{
				for(var i = 0; i < userWrappers.length; i++)
				{
					var element = userWrappers[i];
					BX.tooltip(element.getAttribute("data-userid"), element, "");
				}
			}
		},
		_onPopupClose: function()
		{
			var handler = this.getSetting("onClose", null);
			if(BX.type.isFunction(handler))
			{
				handler(this);
			}

			if(this._popup)
			{
				this._popup.destroy();
			}
		},
		_onPopupDestroy: function()
		{
			if(this._popup)
			{
				this._popup = null;
			}
		}
	};
	BX.CrmDuplicateListPopup.windows = {};
	if(typeof(BX.CrmDuplicateListPopup.messages) == "undefined")
	{
		BX.CrmDuplicateListPopup.messages = {};
	}
	BX.CrmDuplicateListPopup.create = function(id, settings)
	{
		var self = new BX.CrmDuplicateListPopup();
		self.initialize(id, settings);
		return self;
	};
}
if(typeof(BX.CrmDuplicateRenderer) === "undefined")
{
	BX.CrmDuplicateRenderer = function()
	{
	};
	BX.CrmDuplicateRenderer._onCommunicationBlockClick = function(e)
	{
		var element = null;
		if(e)
		{
			if(e.target)
			{
				element = e.target;
			}
			else if(e.srcElement)
			{
				element = e.srcElement;
			}
		}

		if(BX.type.isElementNode(element))
		{
			if(BX.hasClass(element, "crm-info-popup-block-main"))
			{
				BX.removeClass(element, "crm-info-popup-block-main");
			}

			var wrapper = BX.findParent(element, { className:"crm-info-popup-block" });
			if(BX.type.isElementNode(wrapper) && !BX.hasClass(wrapper, "crm-info-popup-block-open"))
			{
				BX.addClass(wrapper, "crm-info-popup-block-open");
			}

			BX.unbind(element, "click", BX.CrmDuplicateRenderer._onCommunicationBlockClickHandler);
		}
	};
	BX.CrmDuplicateRenderer._onCommunicationBlockClickHandler = BX.delegate(BX.CrmDuplicateRenderer._onCommunicationBlockClick, BX.CrmDuplicateRenderer);
	BX.CrmDuplicateRenderer._prepareCommunications = function(comms)
	{
		if(!BX.type.isArray(comms) || comms.length === 0)
		{
			return null;
		}

		var qty = comms.length;
		if(qty === 1)
		{
			return BX.util.htmlspecialchars(comms[0]);
		}

		var wrapper = BX.create(
			"DIV",
			{
				attrs: { className: "crm-info-popup-block" }
			}
		);

		var first = BX.create(
			"DIV",
			{
				attrs: { className: "crm-info-popup-block-main" },
				text: comms[0]
			}
		);

		wrapper.appendChild(first);
		BX.bind(first, "click", this._onCommunicationBlockClickHandler);

		var innerWrapper = BX.create(
			"DIV",
			{
				attrs: { className: "crm-info-popup-block-inner" }
			}
		);

		for(var i = 1; i < qty; i++)
		{
			innerWrapper.appendChild(
				BX.create(
					"DIV",
					{
						text: comms[i]
					}
				)
			);
		}
		wrapper.appendChild(innerWrapper);
		return wrapper;
	};
	BX.CrmDuplicateRenderer.prepareListContent = function(data, params)
	{
		if(!params)
		{
			params = {};
		}
		var targetGroupId = BX.type.isNotEmptyString(params["groupId"]) ? params["groupId"] : "";

		var infoByType = {};
		for(var groupId in data)
		{
			if(!data.hasOwnProperty(groupId))
			{
				continue;
			}

			if(targetGroupId !== "" && targetGroupId !== groupId)
			{
				continue;
			}

			var groupData = data[groupId];
			var items = BX.type.isArray(groupData["items"]) ? groupData["items"] : [];
			var itemQty = items.length;
			for(var i = 0; i < itemQty; i++)
			{
				var item = items[i];
				var entities = BX.type.isArray(item["ENTITIES"]) ? item["ENTITIES"] : [];
				var entityQty = entities.length;
				for(var j = 0; j < entityQty; j++)
				{
					var entity = entities[j];
					var entityTypeID = BX.type.isNotEmptyString(entity["ENTITY_TYPE_ID"]) ? parseInt(entity["ENTITY_TYPE_ID"]) : 0;
					if(!BX.CrmEntityType.isDefined(entityTypeID))
					{
						continue;
					}

					var entityTypeName = BX.CrmEntityType.resolveName(entityTypeID);
					if(typeof(infoByType[entityTypeName]) === "undefined")
					{
						infoByType[entityTypeName] = [entity];
					}
					else
					{
						var entityID = BX.type.isNotEmptyString(entity["ENTITY_ID"]) ? parseInt(entity["ENTITY_ID"]) : 0;
						var isExists = false;
						for(var n = 0; n < infoByType[entityTypeName].length; n++)
						{
							var curEntity = infoByType[entityTypeName][n];
							var curEntityID = BX.type.isNotEmptyString(curEntity["ENTITY_ID"]) ? parseInt(curEntity["ENTITY_ID"]) : 0;
							if(curEntityID === entityID)
							{
								isExists = true;
								break;
							}
						}

						if(!isExists)
						{
							infoByType[entityTypeName].push(entity);
						}
					}
				}
			}
		}

		var wrapper = BX.create(
			"DIV",
			{
				attrs: { className: "crm-cont-info-popup"}
			}
		);

		var wrapperClasses = typeof(params["classes"]) !== "undefined" ? params["classes"] : null;
		if(BX.type.isArray(wrapperClasses))
		{
			for(var m = 0; m < wrapperClasses.length; m++)
			{
				BX.addClass(wrapper, wrapperClasses[m]);
			}
		}

		var table = BX.create(
			"TABLE",
			{
				attrs: { className: "crm-cont-info-table" }
			}
		);
		wrapper.appendChild(table);

		var hasNotCompleted = false;
		var hasCompleted = false;

		for(var key in infoByType)
		{
			if(!infoByType.hasOwnProperty(key))
			{
				continue;
			}

			var ttleRow = table.insertRow(-1);
			ttleRow.className = "crm-cont-info-table-title";
			var ttlCell = ttleRow.insertCell(-1);
			ttlCell.colspan = 4;
			ttlCell.innerHTML = BX.util.htmlspecialchars(BX.CrmEntityType.categoryCaptions[key]);

			var infos = infoByType[key];
			var infoQty = infos.length;
			for(var k = 0; k < infoQty; k++)
			{
				var info = infos[k];
				var infoRow = table.insertRow(-1);
				var captionRow = infoRow.insertCell(-1);

				if(BX.type.isNotEmptyString(info["URL"]))
				{
					captionRow.appendChild(
						BX.create(
							"A",
							{
								attrs: { href: info["URL"], target: "_blank" },
								text: BX.type.isNotEmptyString(info["TITLE"]) ? info["TITLE"] : "[Untitled]"
							}
						)
					);
				}
				else
				{
					captionRow.innerHTML = BX.type.isNotEmptyString(info["TITLE"])
						? BX.util.htmlspecialchars(info["TITLE"]) : "[Untitled]";
				}

				//Emails
				var hasEmails = false;
				var emailCell = infoRow.insertCell(-1);
				var emails = BX.type.isArray(info["EMAIL"]) ? this._prepareCommunications(info["EMAIL"]) : null;
				if(BX.type.isElementNode(emails))
				{
					emailCell.appendChild(emails);
					hasEmails = true;
				}
				else if(BX.type.isNotEmptyString(emails))
				{
					emailCell.innerHTML = emails;
					hasEmails = true;
				}
				else if(!hasNotCompleted)
				{
					hasNotCompleted = true;
				}

				//Phones
				var hasPhones = false;
				var phoneCell = infoRow.insertCell(-1);
				phoneCell.className = "crm-cont-info-table-tel";
				var phones = BX.type.isArray(info["PHONE"]) ? this._prepareCommunications(info["PHONE"]) : null;
				if(BX.type.isElementNode(phones))
				{
					phoneCell.appendChild(phones);
					hasPhones = true;
				}
				else if(BX.type.isNotEmptyString(phones))
				{
					phoneCell.innerHTML = phones;
					hasPhones = true;
				}
				else if(!hasNotCompleted)
				{
					hasNotCompleted = true;
				}

				if(hasEmails && hasPhones && !hasCompleted)
				{
					hasCompleted = true;
				}

				var responsibleCell = infoRow.insertCell(-1);
				var responsibleID = BX.type.isNotEmptyString(info["RESPONSIBLE_ID"]) ? parseInt(info["RESPONSIBLE_ID"]) : 0;
				if(responsibleID > 0)
				{
					var userWrapper = BX.create(
						"DIV",
						{
							attrs: { className: "crm-info-popup-user" }
						}
					);
					responsibleCell.appendChild(userWrapper);
					userWrapper.className = "crm-info-popup-user";
					userWrapper.setAttribute("data-userid", responsibleID.toString());

					var styles = {};
					if(BX.type.isNotEmptyString(info["RESPONSIBLE_PHOTO_URL"]))
					{
						styles["background"] = "url(" + info["RESPONSIBLE_PHOTO_URL"] + ") repeat scroll center center";
					}

					userWrapper.appendChild(
						BX.create(
							"SPAN",
							{
								attrs: { className: "crm-info-popup-user-img" },
								style: styles
							}
						)
					);

					userWrapper.appendChild(
						BX.create(
							"A",
							{
								attrs:
								{
									target: "_blank",
									href: BX.type.isNotEmptyString(info["RESPONSIBLE_URL"]) ? info["RESPONSIBLE_URL"] : "#",
									className: "crm-info-popup-user-name"
								},
								text: BX.type.isNotEmptyString(info["RESPONSIBLE_FULL_NAME"]) ? info["RESPONSIBLE_FULL_NAME"] : ("[" + responsibleID + "]")
							}
						)
					);
				}
			}
		}

		if(!hasCompleted)
		{
			BX.addClass(table, "crm-cont-info-table-empty");
		}
		return wrapper;
	}
}

if(typeof(BX.NotificationPopup) == "undefined")
{
	BX.NotificationPopup = function()
	{
		this._id = "";
		this._settings = {};
		this._popup = null;
		this._contentWrapper = null;
		this._title = "";
		this._timeout = 3000;
		this._timeoutId = null;
		this._messages = [];
	};
	BX.NotificationPopup.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._messages = this.getSetting("messages", null);
			if(!BX.type.isArray(this._messages) || this._messages.length === 0)
			{
				throw "BX.NotificationPopup. Parameter 'messages' is not defined or empty.";
			}

			var timeout = parseInt(this.getSetting("timeout", 3000));
			if(isNaN(timeout) || timeout <= 0)
			{
				timeout = 3000;
			}
			this._timeout = timeout;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		getId: function()
		{
			return this._id;
		},
		show: function()
		{
			if(this.isShown())
			{
				return;
			}

			var id = this.getId();
			if(BX.NotificationPopup.windows[id])
			{
				BX.NotificationPopup.windows[id].destroy();
			}

			this._popup = new BX.PopupWindow(
				id,
				null,
				{
					autoHide: true,
					draggable: false,
					zIndex: 10200,
					className: "bx-notification-popup",
					closeByEsc: true,
					events:
					{
						onPopupClose: BX.delegate(this.onPopupClose, this),
						onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
					},
					content: this.prepareContent()
				}
			);

			BX.NotificationPopup.windows[id] = this._popup;
			this._popup.show();

			this._timeoutId = setTimeout(BX.delegate(this.close, this), this._timeout);

			BX.bind(this._contentWrapper, "mouseover", BX.delegate(this._onMouseOver, this));
			BX.bind(this._contentWrapper, "mouseout", BX.delegate(this._onMouseOut, this));
		},
		_onMouseOver: function(e)
		{
			if(this._timeoutId !== null)
			{
				clearTimeout(this._timeoutId);
			}
		},
		_onMouseOut: function(e)
		{
			this._timeoutId = setTimeout(BX.delegate(this.close, this), this._timeout);
		},
		close: function()
		{
			if(!(this._popup && this._popup.isShown()))
			{
				return;
			}

			this._popup.close();
		},
		isShown: function()
		{
			return this._popup && this._popup.isShown();
		},
		prepareContent: function()
		{
			this._contentWrapper = BX.create("DIV", { attrs: { className: "bx-notification" } });

			var align = this.getSetting("align", "");
			if(align === "justify")
			{
				BX.addClass(this._contentWrapper, "bx-notification-content-justify");
			}

			this._contentWrapper.appendChild(BX.create("SPAN", { attrs: { className: "bx-notification-aligner" } }));
			for(var i = 0; i < this._messages.length; i++)
			{
				this._contentWrapper.appendChild(
					BX.create("SPAN", { props: { className: "bx-notification-text" }, text: this._messages[i] })
				);
			}
			this._contentWrapper.appendChild(BX.create("DIV", { props: { className: "bx-notification-footer" } }));
			return this._contentWrapper;
		},
		onPopupClose: function()
		{
			if(this._popup)
			{
				this._popup.destroy();
			}
		},
		onPopupDestroy: function()
		{
			if(this._popup)
			{
				this._popup = null;
			}

			if(this._contentWrapper)
			{
				this._contentWrapper = null;
			}
		}
	};
	BX.NotificationPopup.windows = {};
	BX.NotificationPopup.create = function(id, settings)
	{
		var self = new BX.NotificationPopup();
		self.initialize(id, settings);
		return self;
	};
	BX.NotificationPopup.show = function(id, settings)
	{
		this.create(id, settings).show();
	}
}

if(typeof(BX.CrmInterfaceMode) === "undefined")
{
	BX.CrmInterfaceMode = { edit: 1, view: 2 };
}

if(typeof(BX.GridAjaxLoader) === "undefined")
{
	BX.GridAjaxLoader = function()
	{
		this._id = "";
		this._settings = {};
		this._url = "";
		this._method = "";
		this._data = {};
		this._dataType = "html";
		this._ajaxId = "";
		this._ajaxInsertHandler = BX.delegate(this._onAjaxInsert, this);
	};

	BX.GridAjaxLoader.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._url = this.getSetting("url", "");
			this._method = this.getSetting("method", "GET");
			this._data = this.getSetting("data", {});
			this._dataType = this.getSetting("dataType", "html");
			this._ajaxId = this.getSetting("ajaxId", "");
			this._urlAjaxIdRegex = /bxajaxid\s*=\s*([a-z0-9]+)/i;
			//Page number expression : first param is url-parameter name and second param is page number.
			this._urlPageNumRegexes =
				[
					/(PAGEN_[0-9]+)\s*=\s*([0-9]+)/i, //Standard page navigation
					/(page)\s*=\s*(-?[0-9]+)/i //Optimized CRM page navigation
				];

			BX.addCustomEvent(window, "onAjaxInsertToNode", this._ajaxInsertHandler);
		},
		release: function()
		{
			BX.removeCustomEvent(window, "onAjaxInsertToNode", this._ajaxInsertHandler);
		},
		getSetting: function(name, defaultvalue)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultvalue;
		},
		getId: function()
		{
			return this._id;
		},
		reload: function(url, callback)
		{
			if(!BX.type.isNotEmptyString(url))
			{
				url = this._url;
			}
			url = BX.util.add_url_param(url, { "bxajaxid": this._ajaxId });

			var cfg = { url: url, dataType: this._dataType };
			if(this._method === "POST")
			{
				cfg["method"] = "POST";
				cfg["data"] = this._data;
			}
			else
			{
				cfg["method"] = "GET";
			}

			if(BX.type.isFunction(callback))
			{
				cfg["onsuccess"] = callback;
			}

			BX.ajax(cfg);
		},
		loadPage: function(pageParam, pageNumber)
		{
			var urlParams = { "bxajaxid": this._ajaxId };
			urlParams[pageParam] = pageNumber;
			var cfg =
				{
					url: BX.util.add_url_param(this._url, urlParams),
					dataType: this._dataType
				};

			if(this._method === "POST")
			{
				cfg["method"] = "POST";
				cfg["data"] = this._data;
			}
			else
			{
				cfg["method"] = "GET";
			}

			cfg["onsuccess"] = BX.delegate(this._onPageLoadSuccess, this);
			BX.ajax(cfg);
		},
		setupFormAction: function(form, url)
		{
			if(!BX.type.isNotEmptyString(url))
			{
				url = this._url;
			}

			url = BX.util.add_url_param(url, { "bxajaxid": this._ajaxId });
			form.action = url;
		},
		setupForm: function(form, url)
		{
			this.setupFormAction(form, url);
			BX.util.addObjectToForm(this._data, form);
		},
		_onAjaxInsert: function(params)
		{
			if(typeof(params.eventArgs) === "undefined")
			{
				return;
			}

			var m = this._urlAjaxIdRegex.exec(params.url);
			if(BX.type.isArray(m) && m.length > 1 && m[1] === this._ajaxId)
			{
				var l = this._urlPageNumRegexes.length;
				for(var i = 0; i < l; i++)
				{
					m = this._urlPageNumRegexes[i].exec(params.url);
					if(!(BX.type.isArray(m) && m.length > 2))
					{
						continue;
					}

					this.loadPage(m[1], m[2]);

					params.eventArgs.cancel = true;
					return;
				}
			}
		},
		_onPageLoadSuccess: function(data)
		{
			var node = BX('comp_' + this._ajaxId);
			if(node)
			{
				node.innerHTML = data;
			}
		}
	};

	BX.GridAjaxLoader.items = {};
	BX.GridAjaxLoader.create = function(id, settings)
	{
		var self = new BX.GridAjaxLoader();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	};
	BX.GridAjaxLoader.remove = function(id)
	{
		if(typeof(this.items[id]) === "undefined")
		{
			return;
		}

		this.items[id].release();
		delete this.items[id];
	};
}

if(typeof(BX.AddressFormatSelector) === "undefined")
{
	BX.AddressFormatSelector = function()
	{
		this._id = "";
		this._settings = {};
		this._controlPrefix = "";
		this._descrContainer = null;
		this._typeInfos = {};
	};

	BX.AddressFormatSelector.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._controlPrefix = this.getSetting("controlPrefix");
			this._typeInfos = this.getSetting("typeInfos", {});
			for(var key in this._typeInfos)
			{
				if(!this._typeInfos.hasOwnProperty(key))
				{
					continue;
				}

				var element = BX(this._controlPrefix + key.toLowerCase());
				if(element)
				{
					BX.bind(element, "change", BX.delegate(this._onControlChange, this));
				}
			}
			this._descrContainer = BX(this.getSetting("descrContainerId"));
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultvalue)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultvalue;
		},
		_onControlChange: function(e)
		{
			if(!e)
			{
				e = window.event;
			}

			var target = BX.getEventTarget(e);
			if(target && BX.type.isNotEmptyString(this._typeInfos[target.value]) && this._descrContainer)
			{
				this._descrContainer.innerHTML = this._typeInfos[target.value];
			}
		}
	};

	BX.AddressFormatSelector.create = function(id, settings)
	{
		var self = new BX.AddressFormatSelector();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmLongRunningProcessManager) == "undefined")
{
	BX.CrmLongRunningProcessManager = function()
	{
		this._id = "";
		this._settings = {};
		this._serviceUrl = "";
		this._actionName = "";
		this._dialog = null;
	};
	BX.CrmLongRunningProcessManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_lrp_mgr_" + Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			this._serviceUrl = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw "BX.CrmLongRunningProcessManager. Could not find 'serviceUrl' parameter in settings.";
			}

			this._actionName = this.getSetting("actionName", "");
			if(!BX.type.isNotEmptyString(this._actionName))
			{
				throw "BX.CrmLongRunningProcessManager. Could not find 'actionName' parameter in settings.";
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
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		getMessage: function(name)
		{
			var m = BX.CrmLongRunningProcessManager.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		getServiceUrl: function()
		{
			return this._serviceUrl;
		},
		getActionName: function()
		{
			return this._actionName;
		},
		run: function()
		{
			if(!this._dialog)
			{
				var title = this.getSetting("dialogTitle", this.getMessage("dialogTitle"));
				var summary = this.getSetting("dialogSummary", this.getMessage("dialogSummary"));
				this._dialog = BX.CrmLongRunningProcessDialog.create(
					this.getId(),
					{
						serviceUrl: this.getServiceUrl(),
						action: this.getActionName(),
						title: title,
						summary: summary
					}
				);
			}

			BX.addCustomEvent(this._dialog, "ON_STATE_CHANGE", BX.delegate(this._onProcessStateChange, this));
			this._dialog.show();
		},
		_onProcessStateChange: function(sender)
		{
			if(sender === this._dialog)
			{
				if(this._dialog.getState() === BX.CrmLongRunningProcessState.completed)
				{
					BX.onCustomEvent(this, "ON_LONG_RUNNING_PROCESS_COMPLETE", [this]);
				}
			}
		}
	};
	if(typeof(BX.CrmLongRunningProcessManager.messages) == "undefined")
	{
		BX.CrmLongRunningProcessManager.messages = {};
	}
	BX.CrmLongRunningProcessManager.items = {};
	BX.CrmLongRunningProcessManager.create = function(id, settings)
	{
		var self = new BX.CrmLongRunningProcessManager();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}

if(typeof(BX.CrmLongRunningProcessPanel) === "undefined")
{
	BX.CrmLongRunningProcessPanel = function()
	{
		this._id = "";
		this._settings = {};
		this._prefix = "";
		this._hasLayout = false;
		this._active = false;
		this._container = null;
		this._wrapper = null;
		this._link = null;
		this._manager = null;
		this._clickHandler = BX.delegate(this.onClick, this);
		this._processCompleteHandler = BX.delegate(this.onProcessComplete, this);
	};

	BX.CrmLongRunningProcessPanel.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._container = BX(this.getSetting("containerId"));
			if(!this._container)
			{
				throw "CrmLongRunningProcessPanel: Could not find container.";
			}

			this._active = !!this.getSetting("active", false);
			this._prefix = this.getSetting("prefix");
			this._message = this.getSetting("message");

			this._manager = BX.CrmLongRunningProcessManager.create(this._id, this.getSetting("manager"));
		},
		layout: function()
		{
			if(this._hasLayout)
			{
				return;
			}

			this._wrapper = BX.create("DIV", { props: { className: "crm-view-message" } });
			this._container.appendChild(this._wrapper);

			if(!this._active)
			{
				this._wrapper.style.display = "none";
			}

			var linkId = (this._prefix !== "" ? this._prefix : this._id) + "_link";
			var html = this._message.replace(/#ID#/gi, linkId).replace(/#URL#/gi, "#");
			this._wrapper.appendChild(BX.create("SPAN", { html: html }));

			this._link = BX(linkId);
			if(this._link)
			{
				BX.bind(this._link, "click", this._clickHandler);
			}

			this._hasLayout = true;
		},
		cleanLayout: function()
		{
			if(!this._hasLayout)
			{
				return;
			}

			if(this._link)
			{
				BX.unbind(this._link, "click", this._clickHandler);
				this._link = null;
			}

			BX.cleanNode(this._wrapper, true);
			this._wrapper = null;

			this._hasLayout = false;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		isActive: function()
		{
			return this._active;
		},
		setActive: function(active)
		{
			active = !!active;
			if(this._active === active)
			{
				return;
			}

			this._active = active;
			this._wrapper.style.display = active ? "" : "none";
		},
		onClick: function(e)
		{
			BX.addCustomEvent(this._manager, "ON_LONG_RUNNING_PROCESS_COMPLETE", this._processCompleteHandler);
			this._manager.run();
			return BX.PreventDefault(e);
		},
		onProcessComplete: function(mgr)
		{
			if(mgr !== this._manager)
			{
				return;
			}

			BX.removeCustomEvent(this._manager, "ON_LONG_RUNNING_PROCESS_COMPLETE", this._processCompleteHandler);
			this.setActive(false);
		}
	};

	BX.CrmLongRunningProcessPanel.create = function(id, settings)
	{
		var self = new BX.CrmLongRunningProcessPanel();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.InterfaceFilterFieldInfoProvider) === "undefined")
{
	BX.InterfaceFilterFieldInfoProvider = function()
	{
		this._id = "";
		this._settings = {};
		this._infos = null;
		this._setFildsHandler = BX.delegate(this.onSetFilterFields, this);
		this._getFildsHandler = BX.delegate(this.onGetFilterFields, this);
	};

	BX.InterfaceFilterFieldInfoProvider.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._infos = this.getSetting("infos", null);

			BX.onCustomEvent(window, "InterfaceFilterFieldInfoProviderCreate", [this]);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return typeof(this._settings[name]) != "undefined" ? this._settings[name] : defaultval;
		},
		registerFilter: function(filter)
		{
			BX.addCustomEvent(filter, "AFTER_SET_FILTER_FIELDS", this._setFildsHandler);
			BX.addCustomEvent(filter, "AFTER_GET_FILTER_FIELDS", this._getFildsHandler);
		},
		getFieldInfos: function()
		{
			return this._infos;
		},
		onSetFilterFields: function(sender, form, fields)
		{
			var infos = this._infos;
			if(!BX.type.isArray(infos))
			{
				return;
			}

			var isSettingsContext = form.name.indexOf('flt_settings') === 0;

			var count = infos.length;
			var paramName = '';
			for(var i = 0; i < count; i++)
			{
				var info = infos[i];
				var id = BX.type.isNotEmptyString(info['id']) ? info['id'] : '';
				var type = BX.type.isNotEmptyString(info['typeName']) ? info['typeName'].toUpperCase() : '';
				var params = info['params'] ? info['params'] : {};

				if(type === 'USER')
				{
					var data = params['data'] ? params['data'] : {};
					this.setElementByFilter(
						data[isSettingsContext ? 'settingsElementId' : 'elementId'],
						data['paramName'],
						fields
					);

					var search = params['search'] ? params['search'] : {};
					this.setElementByFilter(
						search[isSettingsContext ? 'settingsElementId' : 'elementId'],
						search['paramName'],
						fields
					);
				}
			}
		},
		onGetFilterFields: function(sender, form, fields)
		{
			var infos = this._infos;
			if(!BX.type.isArray(infos))
			{
				return;
			}

			var isSettingsContext = form.name.indexOf('flt_settings') === 0;
			var count = infos.length;
			for(var i = 0; i < count; i++)
			{
				var info = infos[i];
				var id = BX.type.isNotEmptyString(info['id']) ? info['id'] : '';
				var type = BX.type.isNotEmptyString(info['typeName']) ? info['typeName'].toUpperCase() : '';
				var params = info['params'] ? info['params'] : {};

				if(type === 'USER')
				{
					var data = params['data'] ? params['data'] : {};
					this.setFilterByElement(
						data[isSettingsContext ? 'settingsElementId' : 'elementId'],
						data['paramName'],
						fields
					);

					var search = params['search'] ? params['search'] : {};
					this.setFilterByElement(
						search[isSettingsContext ? 'settingsElementId' : 'elementId'],
						search['paramName'],
						fields
					);
				}
			}
		},
		setElementByFilter: function(elementId, paramName, filter)
		{
			var element = BX.type.isNotEmptyString(elementId) ? BX(elementId) : null;
			if(BX.type.isElementNode(element))
			{
				element.value = BX.type.isNotEmptyString(paramName) && filter[paramName] ? filter[paramName] : '';
			}
		},
		setFilterByElement: function(elementId, paramName, filter)
		{
			var element = BX.type.isNotEmptyString(elementId) ? BX(elementId) : null;
			if(BX.type.isElementNode(element) && BX.type.isNotEmptyString(paramName))
			{
				filter[paramName] = element.value;
			}
		}
	};
	BX.InterfaceFilterFieldInfoProvider.items = {};
	BX.InterfaceFilterFieldInfoProvider.create = function(id, settings)
	{
		var self = new BX.InterfaceFilterFieldInfoProvider();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}

if(typeof(BX.CrmConversionSchemeSelector) === "undefined")
{
	BX.CrmConversionSchemeSelector = function()
	{
		this._id = "";
		this._settings = {};
		this._entityId = 0;
		this._scheme = "";

		this._isMenuShown = false;
		this._menuId = "";
		this._container = null;
		this._containerClickHandler = BX.delegate(this.onContainerClick, this);
		this._label = null;
		this._button = null;
		this._buttonClickHandler = BX.delegate(this.onButtonClick, this);
		this._menuIiemClickHandler = BX.delegate(this.onMenuItemClick, this);
		this._menuCloseHandler = BX.delegate(this.onMenuClose, this);
		this._hint = null;
	};
	BX.CrmConversionSchemeSelector.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._entityId = parseInt(this.getSetting("entityId", 0));
			if(!BX.type.isNumber(this._entityId))
			{
				throw "BX.CrmConversionSchemeSelector: entity id is not found!";
			}

			this._scheme = this.getSetting("scheme", "");

			this._container = BX(this.getSetting("containerId"));
			if(!BX.type.isElementNode(this._container))
			{
				throw "BX.CrmConversionSchemeSelector: container element is not found!";
			}
			BX.bind(this._container, "click", this._containerClickHandler);

			this._menuId = 'crm_menu_popup_' + this._id.toLowerCase();
			this._button = BX(this.getSetting("buttonId"));
			if(!BX.type.isElementNode(this._button))
			{
				throw "BX.CrmConversionSchemeSelector: button element is not found!";
			}
			BX.bind(this._button, "click", this._buttonClickHandler);

			var labelId = this.getSetting("labelId", "");
			if(BX.type.isNotEmptyString(labelId))
			{
				this._label = BX(labelId);
			}

			if(this.getSetting("enableHint", false))
			{
				this.createHint(this.getSetting("hintMessages", null));
			}

			this.doInitialize();
		},
		doInitialize: function()
		{
		},
		release: function()
		{
			this.closeMenu();

			BX.unbind(this._container, "click", this._containerClickHandler);
			BX.unbind(this._button, "click", this._buttonClickHandler);
		},
		getSetting: function (name, defaultval)
		{
			return typeof(this._settings[name]) != "undefined" ? this._settings[name] : defaultval;
		},
		getId: function()
		{
			return this._id;
		},
		getScheme: function()
		{
			return this._scheme;
		},
		setScheme: function(scheme)
		{
			this._scheme = scheme;
			this.processSchemeChange();
		},
		processSchemeChange: function()
		{
			if(this._label)
			{
				this._label.innerHTML = BX.util.htmlspecialchars(this.getSchemeDescription(this._scheme));
			}

			window.setTimeout(BX.delegate(this.convert, this), 250);
		},
		getSchemeDescription: function(scheme)
		{
			return "[" + scheme + "]";
		},
		showMenu: function()
		{
			if(this._isMenuShown)
			{
				return;
			}

			var menuItems = [];
			var items = this.prepareItems();
			for(var i = 0; i < items.length; i++)
			{
				var item = items[i];

				menuItems.push(
					{
						text: item["text"],
						value: item["value"],
						href: "#",
						className: "crm-convert-item",
						onclick: this._menuIiemClickHandler
					}
				);
			}

			if(typeof(BX.PopupMenu.Data[this._menuId]) !== "undefined")
			{
				BX.PopupMenu.Data[this._menuId].popupWindow.destroy();
				delete BX.PopupMenu.Data[this._menuId];
			}

			var anchor = this._button;
			var anchorPos = BX.pos(anchor);

			BX.PopupMenu.show(
				this._menuId,
				anchor,
				menuItems,
				{
					autoHide: true,
					offsetLeft: (anchorPos["width"] / 2),
					angle: { position: "top", offset: 0 },
					events: { onPopupClose: this._menuCloseHandler }
				}
			);

			this._isMenuShown = true;
		},
		closeMenu: function()
		{
			if(!this._isMenuShown)
			{
				return;
			}

			BX.PopupMenu.destroy(this._menuId);
			this._isMenuShown = false;
		},
		prepareItems: function()
		{
		},
		prepareConfig: function()
		{
		},
		processContainerClick: function()
		{
			this.convert();
		},
		processMenuItemClick: function(item)
		{
			this.setScheme(item["value"]);
			this.closeMenu();
		},
		createHint: function(messages)
		{
			if(!messages)
			{
				return;
			}

			this._hint = BX.PopupWindowManager.create(this._id + "_hint",
				this._container,
				{
					offsetTop : -8,
					autoHide : true,
					closeByEsc : false,
					angle: { position: "bottom", offset: 42 },
					events: { onPopupClose : BX.delegate(this.onHintClose, this) },
					content : BX.create("DIV",
						{
							attrs: { className: "crm-conv-selector-popup-contents" },
							children:
							[
								BX.create("SPAN",
									{ attrs: { className: "crm-popup-title" }, text: messages["title"]  }
								),
								BX.create("P", { text: messages["content"] }),
								BX.create("P",
									{
										children:
										[
											BX.create("A",
												{
													props: { href: "#" },
													text: messages["disabling"],
													events: { "click": BX.delegate(this.onDisableHint, this)  }
												}
											)
										]
									}
								)
							]
						}
					)
				}
			);
			this._hint.show();
		},
		onDisableHint: function(e)
		{
			if(this._hint)
			{
				this._hint.close();
				BX.userOptions.save(
					"crm.interface.toobar",
					"conv_scheme_selector",
					"enable_" + this.getId().toLowerCase() + "_hint",
					"N",
					false
				);
			}
			return BX.PreventDefault(e);
		},
		onHintClose: function()
		{
			if(this._hint)
			{
				this._hint.destroy();
				this._hint = null;
			}
		},
		onButtonClick: function(e)
		{
			this.showMenu();
		},
		onContainerClick: function(e)
		{
			this.processContainerClick();
		},
		onMenuItemClick: function(e, item)
		{
			this.processMenuItemClick(item);
			return BX.PreventDefault(e);
		},
		onMenuClose: function()
		{
			this._isMenuShown = false;
		},
		convert: function()
		{
		}
	};
}

if(typeof(BX.CrmEntityConversionScheme) === "undefined")
{
	BX.CrmEntityConversionScheme = function()
	{
	};
	BX.CrmEntityConversionScheme.mergeConfigs = function(source, target)
	{
		this.mergeEntityConfigs(BX.CrmEntityType.names.deal, source, target);
		this.mergeEntityConfigs(BX.CrmEntityType.names.contact, source, target);
		this.mergeEntityConfigs(BX.CrmEntityType.names.company, source, target);
		this.mergeEntityConfigs(BX.CrmEntityType.names.invoice, source, target);
		this.mergeEntityConfigs(BX.CrmEntityType.names.quote, source, target);
	};
	BX.CrmEntityConversionScheme.mergeEntityConfigs = function(entityTypeName, source, target)
	{
		var key = entityTypeName.toLowerCase();
		if(typeof(source[key]) === "undefined")
		{
			return;
		}

		if(typeof(target[key]) === "undefined")
		{
			target[key] = {};
		}

		if(BX.type.isNotEmptyString(source[key]["active"]))
		{
			target[key]["active"] = source[key]["active"];
		}
		if(BX.type.isNotEmptyString(source[key]["enableSync"]))
		{
			target[key]["enableSync"] = source[key]["enableSync"];
		}
		if(BX.type.isPlainObject(source[key]["initData"]))
		{
			target[key]["initData"] = source[key]["initData"];
		}
	};

	BX.CrmEntityConversionScheme.removeEntityConfigs = function(entityTypeName, config)
	{
		var key = entityTypeName.toLowerCase();
		if(typeof(config[key]) !== "undefined")
		{
			delete config[key];
		}
	};
}

if(typeof(BX.CrmLeadConversionScheme) === "undefined")
{
	BX.CrmLeadConversionScheme =
	{
		undefined: "",
		dealcontactcompany: "DEAL_CONTACT_COMPANY",
		dealcontact: "DEAL_CONTACT",
		dealcompany: "DEAL_COMPANY",
		deal: "DEAL",
		contactcompany: "CONTACT_COMPANY",
		contact: "CONTACT",
		company: "COMPANY",

		getListItems: function(ids)
		{
			var results = [];
			for(var i = 0; i < ids.length; i++)
			{
				var id = ids[i];
				results.push({ value: id, text: this.getDescription(id) });
			}

			return results;
		},
		getDescription: function(id)
		{
			var m = this.messages;
			return m.hasOwnProperty(id) ? m[id] : id;
		},
		fromConfig: function(config)
		{
			var scheme = this.undefined;
			var isDealActive = this.isEntityActive(config, "deal");
			var isContactActive = this.isEntityActive(config, "contact");
			var isCompanyActive = this.isEntityActive(config, "company");

			if(isDealActive)
			{
				if(isContactActive && isCompanyActive)
				{
					scheme = this.dealcontactcompany;
				}
				else if(isContactActive)
				{
					scheme = this.dealcontact;
				}
				else if(isCompanyActive)
				{
					scheme = this.dealcompany;
				}
				else
				{
					scheme = this.deal;
				}
			}
			else if(isContactActive && isCompanyActive)
			{
				scheme = this.contactcompany;
			}
			else if(isContactActive)
			{
				scheme = this.contact;
			}
			else if(isCompanyActive)
			{
				scheme = this.company;
			}

			return scheme;
		},
		toConfig: function(scheme, config)
		{
			this.markEntityAsActive(
				config,
				BX.CrmEntityType.names.deal,
				scheme === this.dealcontactcompany || scheme === this.dealcontact || scheme === this.dealcompany || scheme === this.deal
			);

			this.markEntityAsActive(
				config,
				BX.CrmEntityType.names.contact,
				scheme === this.dealcontactcompany || scheme === this.dealcontact || scheme === this.contactcompany || scheme === this.contact
			);

			this.markEntityAsActive(
				config,
				BX.CrmEntityType.names.company,
				scheme === this.dealcontactcompany || scheme === this.dealcompany || scheme === this.contactcompany || scheme === this.company
			);
		},
		createConfig: function(scheme)
		{
			var config = {};
			this.toConfig(scheme, config);
			return config;
		},
		isEntityActive: function(config, entityTypeName)
		{
			var key = entityTypeName.toLowerCase();
			var params = typeof(config[key]) !== "undefined" ? config[key] : {};
			return BX.type.isNotEmptyString(params["active"]) && params["active"] === "Y"
		},
		markEntityAsActive: function(config, entityTypeName, active)
		{
			var key = entityTypeName.toLowerCase();
			if(typeof(config[key]) === "undefined")
			{
				config[key] = {};
			}
			config[key]["active"] = active ? "Y" : "N";
		},
		mergeConfigs: function(source, target)
		{
			BX.CrmEntityConversionScheme.mergeEntityConfigs(BX.CrmEntityType.names.deal, source, target);
			BX.CrmEntityConversionScheme.mergeEntityConfigs(BX.CrmEntityType.names.contact, source, target);
			BX.CrmEntityConversionScheme.mergeEntityConfigs(BX.CrmEntityType.names.company, source, target);

			BX.CrmEntityConversionScheme.removeEntityConfigs(BX.CrmEntityType.names.invoice, target);
			BX.CrmEntityConversionScheme.removeEntityConfigs(BX.CrmEntityType.names.quote, target);
		}
	};

	if(typeof(BX.CrmLeadConversionScheme.messages) === "undefined")
	{
		BX.CrmLeadConversionScheme.messages = {};
	}
}

if(typeof(BX.CrmDealConversionScheme) === "undefined")
{
	BX.CrmDealConversionScheme =
	{
		undefined: "",
		invoice: "INVOICE",
		quote: "QUOTE",
		getListItems: function(ids)
		{
			var results = [];
			for(var i = 0; i < ids.length; i++)
			{
				var id = ids[i];
				results.push({ value: id, text: this.getDescription(id) });
			}

			return results;
		},
		getDescription: function(id)
		{
			var m = this.messages;
			return m.hasOwnProperty(id) ? m[id] : id;
		},
		fromConfig: function(config)
		{
			var scheme = this.undefined;
			if(this.isEntityActive(config, "invoice"))
			{
				scheme = this.invoice;
			}
			else if(this.isEntityActive(config, "quote"))
			{
				scheme = this.quote;
			}
			return scheme;
		},
		toConfig: function(scheme, config)
		{
			this.markEntityAsActive(config, "invoice", scheme === this.invoice);
			this.markEntityAsActive(config, "quote", scheme === this.quote);
		},
		createConfig: function(scheme)
		{
			var config = {};
			this.toConfig(scheme, config);
			return config;
		},
		isEntityActive: function(config, entityTypeName)
		{
			var params = typeof(config[entityTypeName]) !== "undefined" ? config[entityTypeName] : {};
			return BX.type.isNotEmptyString(params["active"]) && params["active"] === "Y"
		},
		markEntityAsActive: function(config, entityTypeName, active)
		{
			if(typeof(config[entityTypeName]) === "undefined")
			{
				config[entityTypeName] = {};
			}
			config[entityTypeName]["active"] = active ? "Y" : "N";
		},
		mergeConfigs: function(source, target)
		{
			BX.CrmEntityConversionScheme.mergeEntityConfigs(BX.CrmEntityType.names.invoice, source, target);
			BX.CrmEntityConversionScheme.mergeEntityConfigs(BX.CrmEntityType.names.quote, source, target);

			BX.CrmEntityConversionScheme.removeEntityConfigs(BX.CrmEntityType.names.deal, target);
			BX.CrmEntityConversionScheme.removeEntityConfigs(BX.CrmEntityType.names.contact, target);
			BX.CrmEntityConversionScheme.removeEntityConfigs(BX.CrmEntityType.names.company, target);
		}
	};
	if(typeof(BX.CrmDealConversionScheme.messages) === "undefined")
	{
		BX.CrmDealConversionScheme.messages = {};
	}
}

if(typeof(BX.CrmQuoteConversionScheme) === "undefined")
{
	BX.CrmQuoteConversionScheme =
	{
		undefined: "",
		deal: "DEAL",
		invoice: "INVOICE",
		getListItems: function(ids)
		{
			var results = [];
			for(var i = 0; i < ids.length; i++)
			{
				var id = ids[i];
				results.push({ value: id, text: this.getDescription(id) });
			}

			return results;
		},
		getDescription: function(id)
		{
			var m = this.messages;
			return m.hasOwnProperty(id) ? m[id] : id;
		},
		fromConfig: function(config)
		{
			var scheme = this.undefined;
			if(this.isEntityActive(config, "deal"))
			{
				scheme = this.deal;
			}
			else if(this.isEntityActive(config, "invoice"))
			{
				scheme = this.invoice;
			}
			return scheme;
		},
		toConfig: function(scheme, config)
		{
			this.markEntityAsActive(config, "deal", scheme === this.deal);
			this.markEntityAsActive(config, "invoice", scheme === this.invoice);
		},
		createConfig: function(scheme)
		{
			var config = {};
			this.toConfig(scheme, config);
			return config;
		},
		isEntityActive: function(config, entityTypeName)
		{
			var params = typeof(config[entityTypeName]) !== "undefined" ? config[entityTypeName] : {};
			return BX.type.isNotEmptyString(params["active"]) && params["active"] === "Y"
		},
		markEntityAsActive: function(config, entityTypeName, active)
		{
			if(typeof(config[entityTypeName]) === "undefined")
			{
				config[entityTypeName] = {};
			}
			config[entityTypeName]["active"] = active ? "Y" : "N";
		},
		mergeConfigs: function(source, target)
		{
			BX.CrmEntityConversionScheme.mergeEntityConfigs(BX.CrmEntityType.names.deal, source, target);
			BX.CrmEntityConversionScheme.mergeEntityConfigs(BX.CrmEntityType.names.invoice, source, target);

			BX.CrmEntityConversionScheme.removeEntityConfigs(BX.CrmEntityType.names.quote, target);
			BX.CrmEntityConversionScheme.removeEntityConfigs(BX.CrmEntityType.names.contact, target);
			BX.CrmEntityConversionScheme.removeEntityConfigs(BX.CrmEntityType.names.company, target);
		}
	};
	if(typeof(BX.CrmQuoteConversionScheme.messages) === "undefined")
	{
		BX.CrmQuoteConversionScheme.messages = {};
	}
}

if(typeof(BX.CrmEntityConverterMode) === "undefined")
{
	BX.CrmEntityConverterMode =
	{
		intermediate: 0,
		schemeSetup: 1,
		syncSetup: 2,
		request: 3
	}
}

if(typeof(BX.CrmEntityConverter) === "undefined")
{
	BX.CrmEntityConverter = function()
	{
		this._id = "";
		this._settings = {};
		this._config = {};
		this._contextData = null;
		this._mode = BX.CrmEntityConverterMode.intermediate;
		this._entityId = 0;
		this._originUrl = "";
		this._syncEditor = null;
		this._syncEditorClosingListener = BX.delegate(this.onSyncEditorClose, this);
		this._enableSync = false;
		this._enablePageRefresh = true;
		this._enableRedirectToShowPage = true;
		this._requestIsRunning = false;
		this._dealCategorySelectDialog = null;
		this._dealCategorySelectListener = BX.delegate(this.onDealCategorySelect, this);
	};
	BX.CrmEntityConverter.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._config = this.getSetting("config", {});
			this._serviceUrl = this.getSetting("serviceUrl", "");

			this._enablePageRefresh = this.getSetting("enablePageRefresh", true);
			this._enableRedirectToShowPage = this.getSetting("enableRedirectToShowPage", true);
		},
		getSetting: function(name, defaultval)
		{
			return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
		},
		setSetting: function(name, val)
		{
			this._settings[name] = val;
		},
		getId: function()
		{
			return this._id;
		},
		getMessage: function(name)
		{
			return name;
		},
		getConfig: function()
		{
			return this._config;
		},
		setupSynchronization: function(fieldNames)
		{
			this._mode = BX.CrmEntityConverterMode.syncSetup;
			if(this._syncEditor)
			{
				this._syncEditor.setConfig(this._config);
				this._syncEditor.setFieldNames(fieldNames);
			}
			else
			{
				this._syncEditor = BX.CrmEntityFieldSynchronizationEditor.create(
					this._id + "_config",
					{
						converter: this,
						config: this._config,
						title: this.getMessage("dialogTitle"),
						fieldNames: fieldNames,
						legend: this.getMessage("syncEditorLegend"),
						fieldListTitle: this.getMessage("syncEditorFieldListTitle"),
						entityListTitle: this.getMessage("syncEditorEntityListTitle"),
						continueButton: this.getMessage("continueButton"),
						cancelButton: this.getMessage("cancelButton")
					}
				);
				this._syncEditor.addClosingListener(this._syncEditorClosingListener);
			}
			this._syncEditor.show();
		},
		convert: function(entityId, config, originUrl, contextData)
		{
			if(!BX.type.isPlainObject(config))
			{
				return;
			}

			this._entityId = entityId;
			this._contextData = BX.type.isPlainObject(contextData) ? contextData : null;
			this._originUrl = originUrl;

			this.registerConfig(config);
			
			if(!BX.CrmLeadConversionScheme.isEntityActive(this._config, BX.CrmEntityType.names.deal))
			{
				this.startRequest();
			}
			else
			{
				var categoryId = BX.type.isPlainObject(this._config["deal"]["initData"]) ?
					this._config["deal"]["initData"]["categoryId"] : 0;
				if(!this._dealCategorySelectDialog)
				{
					this._dealCategorySelectDialog = BX.CrmDealCategorySelectDialog.create(
						this._id, { value: categoryId }
					);
					this._dealCategorySelectDialog.addCloseListener(this._dealCategorySelectListener);
				}
				this._dealCategorySelectDialog.open();
			}
		},
		registerConfig: function(config)
		{
			BX.CrmEntityConversionScheme.mergeConfigs(config, this._config);
		},
		onDealCategorySelect: function(sender, args)
		{
			if(!(BX.type.isBoolean(args["isCanceled"]) && args["isCanceled"] === false))
			{
				return;
			}

			if(!BX.type.isPlainObject(this._config["deal"]["initData"]))
			{
				this._config["deal"]["initData"] = {};
			}
			this._config["deal"]["initData"]["categoryId"] = sender.getValue();
			this.startRequest();
		},
		onSyncEditorClose: function(sender, args)
		{
			this._mode = BX.CrmEntityConverterMode.intermediate;

			if(!(BX.type.isBoolean(args["isCanceled"]) && args["isCanceled"] === false))
			{
				return;
			}

			this._enableSync = true;
			this._config = this._syncEditor.getConfig();
			this._contextData = null;

			this.startRequest();
		},
		startRequest: function()
		{
			if(this._requestIsRunning)
			{
				return;
			}
			this._requestIsRunning = true;
			BX.showWait();

			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data: {
						"MODE": "CONVERT",
						"ENTITY_ID": this._entityId,
						"ENABLE_SYNCHRONIZATION": this._enableSync ? "Y" : "N",
						"ENABLE_REDIRECT_TO_SHOW": this._enableRedirectToShowPage ? "Y" : "N",
						"CONFIG": this._config,
						"CONTEXT": this._contextData,
						"ORIGIN_URL": this._originUrl
					},
					onsuccess: BX.delegate(this.onRequestSuccsess, this),
					onfailure: BX.delegate(this.onRequestFailure, this)
				}
			);
			this._mode = BX.CrmEntityConverterMode.request;
		},
		onRequestSuccsess: function(result)
		{
			BX.closeWait();
			this._requestIsRunning = false;
			this._mode = BX.CrmEntityConverterMode.intermediate;

			if(BX.type.isPlainObject(result["ERROR"]))
			{
				this.showError(result["ERROR"]);
				return;
			}

			var data;
			if(BX.type.isPlainObject(result["REQUIRED_ACTION"]))
			{
				var action = result["REQUIRED_ACTION"];
				var name = BX.type.isNotEmptyString(action["NAME"]) ? action["NAME"] : "";
				data = BX.type.isPlainObject(action["DATA"]) ? action["DATA"] : {};
				if(name === "SYNCHRONIZE")
				{
					if(BX.type.isPlainObject(data["CONFIG"]))
					{
						this._config = data["CONFIG"];
					}

					this.setupSynchronization(BX.type.isArray(data["FIELD_NAMES"]) ? data["FIELD_NAMES"] : []);
				}
				return;
			}

			if(BX.type.isPlainObject(result["DATA"]))
			{
				data = result["DATA"];
				if(BX.type.isNotEmptyString(data["URL"]))
				{
					window.location.href = data["URL"];
				}
				else if(this._enablePageRefresh)
				{
					window.location.reload();
				}
			}
		},
		onRequestFailure: function(result)
		{
			BX.closeWait();
			this._requestIsRunning = false;
			this._mode = BX.CrmEntityConverterMode.intermediate;
		},
		showError: function(error)
		{
			if(BX.type.isPlainObject(error))
			{
				alert(BX.type.isNotEmptyString(error["MESSAGE"]) ? error["MESSAGE"] : this.getMessage("generalError"));
			}
		}
	};
	BX.CrmEntityConverter.create = function(id, settings)
	{
		var self = new BX.CrmEntityConverter();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmLeadConverter) === "undefined")
{
	BX.CrmLeadConverter = function()
	{
		BX.CrmLeadConverter.superclass.constructor.apply(this);
		this._entitySelectorId = "lead_converter";
		this._entitySelectHandler = BX.delegate(this.onEntitySelect, this);
		this._entitySelectCallback = null;
	};
	BX.extend(BX.CrmLeadConverter, BX.CrmEntityConverter);
	BX.CrmLeadConverter.prototype.registerConfig = function(config)
	{
		BX.CrmLeadConversionScheme.mergeConfigs(config, this._config);
	};
	BX.CrmLeadConverter.prototype.getMessage = function(name)
	{
		var m = BX.CrmLeadConverter.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	if(typeof(BX.CrmLeadConverter.messages) === "undefined")
	{
		BX.CrmLeadConverter.messages = {};
	}
	BX.CrmLeadConverter.prototype.openEntitySelector = function(callback)
	{
		this._entitySelectCallback = BX.type.isFunction(callback) ? callback : null;

		var selectorId = this._entitySelectorId;
		if(typeof(obCrm[selectorId]) === "undefined")
		{
			obCrm[selectorId] = new CRM(
				selectorId,
				null,
				null,
				selectorId,
				[],
				false,
				true,
				[ "contact", "company" ],
				{
					"contact": this.getMessage("contact"),
					"company": this.getMessage("company"),
					"ok": this.getMessage("selectButton"),
					"cancel": BX.message("JS_CORE_WINDOW_CANCEL"),
					"close": BX.message("JS_CORE_WINDOW_CLOSE"),
					"wait": BX.message("JS_CORE_LOADING"),
					"noresult": this.getMessage("noresult"),
					"search" : this.getMessage("search"),
					"last" : this.getMessage("last")
				},
				true
			);
			obCrm[selectorId].Init();
			obCrm[selectorId].AddOnSaveListener(this._entitySelectHandler);
		}

		obCrm[selectorId].Open(
			{
				closeIcon: { top: "10px", right: "15px" },
				closeByEsc: true,
				titleBar: this.getMessage("entitySelectorTitle")
			}
		);
	};
	BX.CrmLeadConverter.prototype.onEntitySelect = function(settings)
	{
		var selectorId = this._entitySelectorId;
		obCrm[selectorId].RemoveOnSaveListener(this._entitySelectHandler);
		obCrm[selectorId].Clear();
		delete obCrm[selectorId];

		if(!this._entitySelectCallback)
		{
			return;
		}

		var type;
		var data = null;
		for(type in settings)
		{
			if(settings.hasOwnProperty(type)
				&& BX.type.isPlainObject(settings[type])
				&& BX.type.isPlainObject(settings[type][0]))
			{
				var setting = settings[type][0];
				var entityId = typeof(setting["id"]) ? parseInt(setting["id"]) : 0;
				if(entityId > 0)
				{
					if(data === null)
					{
						data = {};
					}
					data[type] = entityId;
				}
			}
		}

		if(data === null)
		{
			this._entitySelectCallback({ config: null, data: null });
		}
		else
		{
			var config = { deal: { active: "N" }, contact: { active: "N" }, company: { active: "N" } };
			for(type in data)
			{
				if(data.hasOwnProperty(type) && typeof(config[type]) !== "undefined")
				{
					config[type]["active"] = "Y";
				}
			}
			this._entitySelectCallback({ config: config, data: data });
		}
	};
	BX.CrmLeadConverter.create = function(id, settings)
	{
		var self = new BX.CrmLeadConverter();
		self.initialize(id, settings);
		return self;
	};
	BX.CrmLeadConverter.current = null;
	if(typeof(BX.CrmLeadConverter.settings === "undefined"))
	{
		BX.CrmLeadConverter.settings = {};
	}
	if(typeof(BX.CrmLeadConverter.permissions === "undefined"))
	{
		BX.CrmLeadConverter.permissions = { contact: false, company: false, deal: false };
	}
	BX.CrmLeadConverter.getCurrent = function()
	{
		if(!this.current)
		{
			this.current = BX.CrmLeadConverter.create("current", this.settings);
		}
		return this.current;
	};
}

if(typeof(BX.CrmDealConverter) === "undefined")
{
	BX.CrmDealConverter = function()
	{
		BX.CrmDealConverter.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmDealConverter, BX.CrmEntityConverter);
	BX.CrmDealConverter.prototype.registerConfig = function(config)
	{
		BX.CrmDealConversionScheme.mergeConfigs(config, this._config);
	};
	BX.CrmDealConverter.prototype.getMessage = function(name)
	{
		var m = BX.CrmDealConverter.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	if(typeof(BX.CrmDealConverter.messages) === "undefined")
	{
		BX.CrmDealConverter.messages = {};
	}
	BX.CrmDealConverter.create = function(id, settings)
	{
		var self = new BX.CrmDealConverter();
		self.initialize(id, settings);
		return self;
	};
	BX.CrmDealConverter.current = null;
	if(typeof(BX.CrmDealConverter.settings === "undefined"))
	{
		BX.CrmDealConverter.settings = {};
	}
	if(typeof(BX.CrmDealConverter.permissions === "undefined"))
	{
		BX.CrmDealConverter.permissions = { invoice: false, quote: false };
	}
	BX.CrmDealConverter.getCurrent = function()
	{
		if(!this.current)
		{
			this.current = BX.CrmDealConverter.create("current", this.settings);
		}
		return this.current;
	};
}

if(typeof(BX.CrmQuoteConverter) === "undefined")
{
	BX.CrmQuoteConverter = function()
	{
		BX.CrmQuoteConverter.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmQuoteConverter, BX.CrmEntityConverter);
	BX.CrmQuoteConverter.prototype.registerConfig = function(config)
	{
		BX.CrmQuoteConversionScheme.mergeConfigs(config, this._config);
	};
	BX.CrmQuoteConverter.prototype.getMessage = function(name)
	{
		var m = BX.CrmQuoteConverter.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	};
	if(typeof(BX.CrmQuoteConverter.messages) === "undefined")
	{
		BX.CrmQuoteConverter.messages = {};
	}
	BX.CrmQuoteConverter.create = function(id, settings)
	{
		var self = new BX.CrmQuoteConverter();
		self.initialize(id, settings);
		return self;
	};
	BX.CrmQuoteConverter.current = null;
	if(typeof(BX.CrmQuoteConverter.settings === "undefined"))
	{
		BX.CrmQuoteConverter.settings = {};
	}
	if(typeof(BX.CrmQuoteConverter.permissions === "undefined"))
	{
		BX.CrmQuoteConverter.permissions = { invoice: false, quote: false };
	}
	BX.CrmQuoteConverter.getCurrent = function()
	{
		if(!this.current)
		{
			this.current = BX.CrmQuoteConverter.create("current", this.settings);
		}
		return this.current;
	};
}

if(typeof(BX.CrmEntityFieldSynchronizationEditor) === "undefined")
{
	BX.CrmEntityFieldSynchronizationEditor = function()
	{
		this._id = "";
		this._settings = {};
		this._converter = null;
		this._config = {};
		this._fieldNames = [];
		this._closingNotifier = null;
		this._contentWrapper = null;
		this._fieldWrapper = null;
		this._foldButton = null;
		this._foldButtonClickHandler = BX.delegate(this.onFoldButtonClick, this);
		this._checkBoxes = {};
		this._resizer = null;
		this._popup = null;
	};
	BX.CrmEntityFieldSynchronizationEditor.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._converter = this.getSetting("converter");

			this._config = this.getSetting("config", {});
			this._fieldNames = this.getSetting("fieldNames", []);
			this._closingNotifier = BX.CrmNotifier.create(this);
		},
		getSetting: function (name, defaultval)
		{
			return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		getId: function()
		{
			return this._id;
		},
		getConfig: function()
		{
			return this._config;
		},
		setConfig: function(config)
		{
			this._config = config;
		},
		getFieldNames: function()
		{
			return this._fieldNames;
		},
		setFieldNames: function(fieldNames)
		{
			this._fieldNames = fieldNames;
		},
		show: function()
		{
			if(this.isShown())
			{
				return;
			}

			var id = this.getId();
			if(BX.CrmEntityFieldSynchronizationEditor.windows[id])
			{
				BX.CrmEntityFieldSynchronizationEditor.windows[id].destroy();
				delete BX.CrmEntityFieldSynchronizationEditor.windows[id];
			}

			var anchor = this.getSetting("anchor", null);
			this._popup = new BX.PopupWindow(
				id,
				anchor,
				{
					autoHide: false,
					draggable: true,
					zIndex: 100,
					bindOptions: { forceBindPosition: false },
					closeByEsc: true,
					closeIcon :
					{
						marginRight:"-2px",
						marginTop:"3px"
					},
					events:
					{
						onPopupShow: BX.delegate(this.onPopupShow, this),
						onPopupClose: BX.delegate(this.onPopupClose, this),
						onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
					},
					titleBar: this.getSetting("title"),
					content: this.prepareContent(),
					buttons: this.prepareButtons(),
					lightShadow : true,
					className : "crm-tip-popup"
				}
			);

			BX.CrmEntityFieldSynchronizationEditor.windows[id] = this._popup;
			this._popup.show();
		},
		close: function()
		{
			if(!(this._popup && this._popup.isShown()))
			{
				return;
			}

			this._popup.close();
		},
		isShown: function()
		{
			return this._popup && this._popup.isShown();
		},
		addClosingListener: function(listener)
		{
			this._closingNotifier.addListener(listener);
		},
		removeClosingListener: function(listener)
		{
			this._closingNotifier.removeListener(listener);
		},
		getMessage: function(name)
		{
			var m = BX.CrmEntityFieldSynchronizationEditor.messages;
			return m.hasOwnProperty(name) ? m.messages[name] : name;
		},
		prepareButtons: function()
		{
			return(
				[
					new BX.PopupWindowButton(
						{
							text: this.getSetting("continueButton"),
							className: "popup-window-button-accept",
							events: { click: BX.delegate(this.onContinueBtnClick, this) }
						}
					),
					new BX.PopupWindowButtonLink(
						{
							text: this.getSetting("cancelButton"),
							className: "popup-window-button-link-cancel",
							events: { click: BX.delegate(this.onCancelBtnClick, this) }
						}
					)
				]
			);
		},
		prepareContent: function()
		{
			this._contentWrapper = BX.create("DIV", { attrs: { className: "crm-popup-setting-fields" } });

			var fieldList = BX.create("UL", { attrs: { className: "crm-p-s-f-items-list" } });
			for(var i = 0; i < this._fieldNames.length; i++)
			{
				fieldList.appendChild(
					BX.create("LI", { attrs: { className: "crm-p-s-f-item" }, text: this._fieldNames[i] })
				);
			}

			var fieldWrapper = this._fieldWrapper = BX.create("DIV", { attrs: { className: "crm-p-s-f-block-wrap crm-p-s-f-block-hide" } });
			this._contentWrapper.appendChild(fieldWrapper);

			var fieldContainer = BX.create("DIV",
				{
					attrs: { className: "crm-p-s-f-top-block" },
					children:
					[
						BX.create("DIV",
							{
								attrs: { className: "crm-p-s-f-title" },
								text: this.getSetting("fieldListTitle") + ":"
							}
						),
						fieldList
					]
				}
			);

			var foldButton = this._foldButton = BX.create("DIV", { attrs: { className: "crm-p-s-f-open-btn" } });
			if(fieldList.children.length > 6)
			{
				BX.bind(foldButton, "click", this._foldButtonClickHandler);
			}
			else
			{
				fieldWrapper.classList.toggle('crm-p-s-f-block-open');
			}

			var innerFieldWrapper = BX.create("DIV",
				{
					attrs: { className: "crm-p-s-f-block-hide-inner" },
					children:
					[
						BX.create("DIV", { attrs: { className: "crm-p-s-f-text" }, text: this.getSetting("legend") }),
						fieldContainer,
						foldButton
					]
				}
			);

			fieldWrapper.appendChild(innerFieldWrapper);
			this._resizer = BX.AnimatedResize.create(innerFieldWrapper, fieldWrapper);

			var entityWrapper = BX.create("DIV", { attrs: { className: "crm-p-s-f-block-wrap" } });
			this._contentWrapper.appendChild(entityWrapper);
			entityWrapper.appendChild(
				BX.create("DIV",
					{
						attrs: { className: "crm-p-s-f-title" },
						text: this.getSetting("entityListTitle") + ":"
					}
				)
			);

			var id = this.getId();
			this._checkBoxes = {};
			var entityList = BX.create("UL", { attrs: { className: "crm-p-s-f-checkbox-items-list" } });
			for(var entityTypeName in this._config)
			{
				if(!this._config.hasOwnProperty(entityTypeName))
				{
					continue;
				}

				var entityConfig = this._config[entityTypeName];
				var enableSync = BX.type.isNotEmptyString(entityConfig["enableSync"]) && entityConfig["enableSync"] === "Y";
				if(!enableSync)
				{
					continue;
				}

				var inputId = id + "_" + entityTypeName;
				var checkbox = BX.create("INPUT", { props: { id: inputId, type: "checkbox", checked: true } });
				this._checkBoxes[entityTypeName] = checkbox;

				var label = BX.create("LABEL",
					{
						props: { htmlFor: inputId },
						text: BX.CrmEntityType.getCaptionByName(entityTypeName)
					}
				);

				entityList.appendChild(
					BX.create("LI",
						{ attrs: { className: "crm-p-s-f-checkbox-item" }, children: [ checkbox, label ] }
					)
				);
			}
			entityWrapper.appendChild(entityList);
			return this._contentWrapper;
		},
		saveConfig: function()
		{
			for(var entityTypeName in this._checkBoxes)
			{
				if(this._checkBoxes.hasOwnProperty(entityTypeName) && this._config.hasOwnProperty(entityTypeName))
				{
					this._config[entityTypeName]["enableSync"] = this._checkBoxes[entityTypeName].checked ? "Y" : "N";
				}
			}
		},
		onFoldButtonClick: function()
		{
			this._fieldWrapper.classList.toggle("crm-p-s-f-block-open");
			this._resizer.run();
		},
		onContinueBtnClick: function()
		{
			this.saveConfig();
			this._closingNotifier.notify([{ isCanceled: false }]);
			this.close();
		},
		onCancelBtnClick: function()
		{
			this._closingNotifier.notify([{ isCanceled: true }]);
			this.close();
		},
		onPopupShow: function()
		{
		},
		onPopupClose: function()
		{
			if(this._popup)
			{
				this._contentWrapper = null;
				this._popup.destroy();
			}
		},
		onPopupDestroy: function()
		{
			if(!this._popup)
			{
				return;
			}

			this._fieldWrapper = null;
			this._foldButton = null;
			this._contentWrapper = null;
			this._checkBoxes = {};
			this._resizer = null;
			this._popup = null;
			delete BX.CrmEntityFieldSynchronizationEditor.windows[this.getId()];
		}
	};
	BX.CrmEntityFieldSynchronizationEditor.windows = {};
	if(typeof(BX.CrmEntityFieldSynchronizationEditor.messages) == "undefined")
	{
		BX.CrmEntityFieldSynchronizationEditor.messages = {};
	}
	BX.CrmEntityFieldSynchronizationEditor.create = function(id, settings)
	{
		var self = new BX.CrmEntityFieldSynchronizationEditor();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.AnimatedResize) === "undefined")
{
	BX.AnimatedResize = function()
	{
		this._innerBlock = null;
		this._mainBlock = null;
		this._isOpen = false;
	};

	BX.AnimatedResize.prototype =
	{
		initialize: function(innerBlock, mainBlock)
		{
			this._innerBlock = innerBlock;
			this._mainBlock = mainBlock;
		},
		run: function()
		{
			this._isOpen = this._mainBlock.offsetHeight == this._innerBlock.offsetHeight;
			this.ease(this._isOpen
				? { start : this._innerBlock.offsetHeight, finish : 0 }
				: { start: this._mainBlock.offsetHeight, finish: this._innerBlock.offsetHeight }
			);
			this._isOpen = !this._isOpen;
		},
		step: function(state)
		{
			this._mainBlock.style.height = state.height + "px";
		},
		complete: function()
		{
			if(this._isOpen)
			{
				this._mainBlock.style.height = "auto";
			}
		},
		ease: function (params)
		{
			(new BX.easing(
				{
					duration : 300,
					start : { height : params["start"] },
					finish : { height : params["finish"] },
					transition : BX.easing.makeEaseOut(BX.easing.transitions.circ),
					step : BX.delegate(this.step, this),
					complete :BX.delegate(this.complete, this)
				}
			)).animate();
		}
	};
	BX.AnimatedResize.create = function(innerBlock, mainBlock)
	{
		var self = new BX.AnimatedResize();
		self.initialize(innerBlock, mainBlock);
		return self;
	}
}

if(typeof(BX.CrmLeadConversionSchemeSelector) === "undefined")
{
	BX.CrmLeadConversionSchemeSelector = function()
	{
		BX.CrmLeadConversionSchemeSelector.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmLeadConversionSchemeSelector, BX.CrmConversionSchemeSelector);
	BX.CrmLeadConversionSchemeSelector.prototype.prepareItems = function()
	{
		var isDealPermitted = BX.CrmLeadConverter.permissions["deal"];
		var isContactPermitted = BX.CrmLeadConverter.permissions["contact"];
		var isCompanyPermitted = BX.CrmLeadConverter.permissions["company"];

		var schemes = [];
		if(isDealPermitted)
		{
			if(isContactPermitted && isCompanyPermitted)
			{
				schemes.push(BX.CrmLeadConversionScheme.dealcontactcompany);
			}
			if(isContactPermitted)
			{
				schemes.push(BX.CrmLeadConversionScheme.dealcontact);
			}
			if(isCompanyPermitted)
			{
				schemes.push(BX.CrmLeadConversionScheme.dealcompany);
			}

			schemes.push(BX.CrmLeadConversionScheme.deal);
		}
		if(isContactPermitted && isCompanyPermitted)
		{
			schemes.push(BX.CrmLeadConversionScheme.contactcompany);
		}
		if(isContactPermitted)
		{
			schemes.push(BX.CrmLeadConversionScheme.contact);
		}
		if(isCompanyPermitted)
		{
			schemes.push(BX.CrmLeadConversionScheme.company);
		}

		var items = BX.CrmLeadConversionScheme.getListItems(schemes);
		if(isContactPermitted || isCompanyPermitted)
		{
			items.push({
				value: "CUSTOM",
				text: BX.CrmLeadConverter.getCurrent().getMessage("openEntitySelector")
			});
		}

		return items;
	};
	BX.CrmLeadConversionSchemeSelector.prototype.prepareConfig = function()
	{
		return BX.CrmLeadConversionScheme.createConfig(this._scheme);
	};
	BX.CrmLeadConversionSchemeSelector.prototype.getSchemeDescription = function(scheme)
	{
		return BX.CrmLeadConversionScheme.getDescription(scheme);
	};
	BX.CrmLeadConversionSchemeSelector.prototype.processMenuItemClick = function(item)
	{
		var value = item["value"];
		if(value === "CUSTOM")
		{
			BX.CrmLeadConverter.getCurrent().openEntitySelector(BX.delegate(this.onEntitySelect, this));
		}
		else
		{
			this.setScheme(value);
		}
		this.closeMenu();
	};
	BX.CrmLeadConversionSchemeSelector.prototype.onEntitySelect = function(result)
	{
		if(!BX.type.isPlainObject(result))
		{
			return;
		}

		BX.CrmLeadConverter.getCurrent().convert(
			this._entityId,
			result["config"],
			this.getSetting("originUrl"),
			result["data"]
		);
	};
	BX.CrmLeadConversionSchemeSelector.prototype.convert = function()
	{
		BX.CrmLeadConverter.getCurrent().convert(
			this._entityId,
			this.prepareConfig(),
			this.getSetting("originUrl")
		);
	};
	BX.CrmLeadConversionSchemeSelector.create = function(id, settings)
	{
		var self = new BX.CrmLeadConversionSchemeSelector();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmDealConversionSchemeSelector) === "undefined")
{
	BX.CrmDealConversionSchemeSelector = function()
	{
		BX.CrmDealConversionSchemeSelector.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmDealConversionSchemeSelector, BX.CrmConversionSchemeSelector);
	BX.CrmDealConversionSchemeSelector.prototype.prepareItems = function()
	{
		var schemes = [];
		if(BX.CrmDealConverter.permissions["invoice"])
		{
			schemes.push(BX.CrmDealConversionScheme.invoice);
		}
		if(BX.CrmDealConverter.permissions["quote"])
		{
			schemes.push(BX.CrmDealConversionScheme.quote);
		}
		return BX.CrmDealConversionScheme.getListItems(schemes);
	};
	BX.CrmDealConversionSchemeSelector.prototype.prepareConfig = function()
	{
		return BX.CrmDealConversionScheme.createConfig(this._scheme);
	};
	BX.CrmDealConversionSchemeSelector.prototype.getSchemeDescription = function(scheme)
	{
		return BX.CrmDealConversionScheme.getDescription(scheme);
	};
	BX.CrmDealConversionSchemeSelector.prototype.convert = function()
	{
		BX.CrmDealConverter.getCurrent().convert(
			this._entityId,
			this.prepareConfig(),
			this.getSetting("originUrl", "")
		);
	};
	BX.CrmDealConversionSchemeSelector.create = function(id, settings)
	{
		var self = new BX.CrmDealConversionSchemeSelector();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmQuoteConversionSchemeSelector) === "undefined")
{
	BX.CrmQuoteConversionSchemeSelector = function()
	{
		BX.CrmQuoteConversionSchemeSelector.superclass.constructor.apply(this);
	};
	BX.extend(BX.CrmQuoteConversionSchemeSelector, BX.CrmConversionSchemeSelector);
	BX.CrmQuoteConversionSchemeSelector.prototype.prepareItems = function()
	{
		var schemes = [];
		if(BX.CrmQuoteConverter.permissions["deal"])
		{
			schemes.push(BX.CrmQuoteConversionScheme.deal);
		}
		if(BX.CrmQuoteConverter.permissions["invoice"])
		{
			schemes.push(BX.CrmQuoteConversionScheme.invoice);
		}
		return BX.CrmQuoteConversionScheme.getListItems(schemes);
	};
	BX.CrmQuoteConversionSchemeSelector.prototype.prepareConfig = function()
	{
		return BX.CrmQuoteConversionScheme.createConfig(this._scheme);
	};
	BX.CrmQuoteConversionSchemeSelector.prototype.getSchemeDescription = function(scheme)
	{
		return BX.CrmQuoteConversionScheme.getDescription(scheme);
	};
	BX.CrmQuoteConversionSchemeSelector.prototype.convert = function()
	{
		BX.CrmQuoteConverter.getCurrent().convert(
			this._entityId,
			this.prepareConfig(),
			this.getSetting("originUrl", "")
		);
	};
	BX.CrmQuoteConversionSchemeSelector.items = {};
	BX.CrmQuoteConversionSchemeSelector.create = function(id, settings)
	{
		var self = new BX.CrmQuoteConversionSchemeSelector();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}

//region BX.CrmRequisitePresetListLoader
BX.CrmRequisitePresetListLoader = function()
{
	this._id = "";
	this._settings = {};
	this._entityTypeName = "";
	this._serviceUrl = "";
	this._callback = null;
	this._isRequestRunning = false;
	this._waiter = null;
	this._resultData = null;
};
BX.CrmRequisitePresetListLoader.prototype =
{
	initialize: function(id, settings)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : "crm_rq_prest_loader" + Math.random().toString().substring(2);
		this._settings = settings ? settings : {};

		this._entityTypeName = this.getSetting("entityTypeName", "");
		if(!BX.type.isNotEmptyString(this._entityTypeName))
		{
			throw "BX.CrmRequisitePresetListLoader. Could not find 'entityTypeName' parameter.";
		}

		this._entityTypeName = this._entityTypeName.toUpperCase();

		this._serviceUrl = this.getSetting("serviceUrl", "");
		if(!BX.type.isNotEmptyString(this._serviceUrl))
		{
			throw "BX.CrmRequisitePresetListLoader. Could not find 'serviceUrl' parameter.";
		}

		var callback = this.getSetting("callback");
		if(BX.type.isFunction(callback))
		{
			this._callback = callback;
		}
	},
	getId: function()
	{
		return this._id;
	},
	getSetting: function (name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	getResultData: function()
	{
		return this._resultData;
	},
	start: function()
	{
		if(this._isRequestRunning)
		{
			return false;
		}

		this._isRequestRunning = true;
		this._waiter = BX.showWait();
		BX.ajax(
			{
				url: this._serviceUrl,
				method: "POST",
				dataType: "json",
				data: { "ENTITY_TYPE_NAME": this._entityTypeName, "ACTION" : "GET_REQUISITE_PRESETS" },
				onsuccess: BX.delegate(this.onRequestSuccess, this),
				onfailure: BX.delegate(this.onRequestFailure, this)
			}
		);

		return true;
	},
	onRequestSuccess: function(data)
	{
		this._isRequestRunning = false;

		if(this._waiter)
		{
			BX.closeWait(null, this._waiter);
			this._waiter = null;
		}

		var result = BX.type.isPlainObject(data["RESULT"]) ? data["RESULT"] : {};
		this._resultData = BX.type.isArray(result["ITEMS"]) ? result["ITEMS"] : [];
		if(this._callback)
		{
			this._callback(this, { isSuccessed: true, resultData: this._resultData });
		}
	},
	onRequestFailure: function(data)
	{
		this._isRequestRunning = false;

		if(this._waiter)
		{
			BX.closeWait(null, this._waiter);
			this._waiter = null;
		}

		this._resultData = [];
		if(this._callback)
		{
			this._callback(this, { isSuccessed: false, resultData: this._resultData });
		}
	}
};
BX.CrmRequisitePresetListLoader.create = function(id, settings)
{
	var self = new BX.CrmRequisitePresetListLoader();
	self.initialize(id, settings);
	return self;
};
//endregion
//region BX.CrmRequisitePresetSelectDialog
BX.CrmRequisitePresetSelectDialog = function()
{
	this._id = "";
	this._settings = {};
	this._popup = null;
	this._contentWrapper = null;
	this._list = null;
	this._selector = null;
	this._callback = null;
};
BX.CrmRequisitePresetSelectDialog.prototype =
{
	initialize: function(id, settings)
	{
		this._id = id;
		this._settings = settings ? settings : {};

		this._list = this.getSetting("list");
		if(!BX.type.isArray(this._list))
		{
			throw "BX.CrmRequisitePresetSelectDialog. Could not find 'list' parameter.";
		}

		var callback = this.getSetting("callback");
		if(BX.type.isFunction(callback))
		{
			this._callback = callback;
		}
	},
	getId: function()
	{
		return this._id;
	},
	getSetting: function (name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	getMessage:function(name)
	{
		var m = BX.CrmRequisitePresetSelectDialog.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	},
	show: function()
	{
		if(this.isShown())
		{
			return;
		}

		var id = this.getId();
		if(BX.CrmRequisitePresetSelectDialog.windows[id])
		{
			BX.CrmRequisitePresetSelectDialog.windows[id].destroy();
		}

		this._popup = new BX.PopupWindow(
			id,
			this.getSetting("anchor", null),
			{
				autoHide: false,
				draggable: true,
				bindOptions: { forceBindPosition: false },
				closeByEsc: true,
				closeIcon: { top: "10px", right: "15px" },
				zIndex: 0,
				titleBar: this.getMessage("title"),
				content: this.prepareContent(),
				className : "crm-tip-popup",
				lightShadow : true,
				buttons:
				[
					new BX.PopupWindowButton(
						{
							text : BX.message("JS_CORE_WINDOW_CONTINUE"),
							className : "popup-window-button-accept",
							events: { click: BX.delegate(this.onAcceptButtonClick, this) }
						}
					),
					new BX.PopupWindowButtonLink(
						{
							text : BX.message("JS_CORE_WINDOW_CANCEL"),
							className : "popup-window-button-link-cancel",
							events: { click: BX.delegate(this.onCancelButtonClick, this) }
						}
					)
				],
				events:
				{
					onPopupShow: BX.delegate(this.onPopupShow, this),
					onPopupClose: BX.delegate(this.onPopupClose, this),
					onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
				}
			}
		);
		(BX.CrmRequisitePresetSelectDialog.windows[id] = this._popup).show();
	},
	close: function()
	{
		if(!(this._popup && this._popup.isShown()))
		{
			return;
		}

		this._popup.close();
	},
	isShown: function()
	{
		return this._popup && this._popup.isShown();
	},
	getSelectedValue: function()
	{
		return this._selector ? this._selector.value : "";
	},
	prepareContent: function()
	{
		var wrapper = this._contentWrapper = BX.create("DIV", { attrs: { className: "bx-requisite-dialog" } });
		var container = BX.create("DIV", { attrs: { className: "container-item" } });
		wrapper.appendChild(container);

		var selector = this._selector = BX.create('SELECT', {});
		var options = [];
		for(var i = 0; i < this._list.length; i++)
		{
			var item = this._list[i];
			options.push({ "value": item["ID"], "text": item["NAME"] });
		}
		BX.HtmlHelper.setupSelectOptions(selector, options);
		container.appendChild(
			BX.create("DIV",
				{
					attrs: { className: "field-container field-container-left" },
					children:
					[
						BX.create("LABEL",
							{
								attrs: { className: "field-container-title" },
								text: this.getMessage("presetField") + ":"
							}
						),
						BX.create("SPAN", { attrs: { className: "select-container" }, children: [ selector ] })
					]
				}
			)
		);
		return this._contentWrapper;
	},
	onCancelButtonClick: function()
	{
		if(this._callback)
		{
			this._callback(this, { isAccepted: false, selectedValue: this.getSelectedValue() });
		}
	},
	onAcceptButtonClick: function()
	{
		if(this._callback)
		{
			this._callback(this, { isAccepted: true, selectedValue: this.getSelectedValue() });
		}
	},
	onPopupShow: function()
	{
	},
	onPopupClose: function()
	{
		if(this._popup)
		{
			this._popup.destroy();
		}

		if(this._callback)
		{
			this._callback(this, { isAccepted: false, selectedValue: this.getSelectedValue() });
		}
	},
	onPopupDestroy: function()
	{
		if(this._popup)
		{
			this._popup = null;
		}
	}
};
if(typeof(BX.CrmRequisitePresetSelectDialog.messages) === "undefined")
{
	BX.CrmRequisitePresetSelectDialog.messages = {};
}
BX.CrmRequisitePresetSelectDialog.windows = {};
BX.CrmRequisitePresetSelectDialog.create = function(id, settings)
{
	var self = new BX.CrmRequisitePresetSelectDialog();
	self.initialize(id, settings);
	return self;
};
//endregion
//region BX.CrmRequisiteConverter
BX.CrmRequisiteConverter = function()
{
	this._id = "";
	this._settings = {};
	this._entityTypeName = "";
	this._serviceUrl = "";
	this._presetId = 0;
	this._presetList = null;

	this._presetListLoader = null;
	this._presetListLoadHandler = BX.delegate(this.onPresetListLoad, this);

	this._presetSelector = null;
	this._presetSelectHandler = BX.delegate(this.onPresetSelect, this);

	this._processDialog = null;
	this._processStateChangeHandler = BX.delegate(this.onProcessStateChange, this);
};
BX.CrmRequisiteConverter.prototype =
{
	initialize: function(id, settings)
	{
		this._id = id;
		this._settings = settings ? settings : {};

		this._entityTypeName = this.getSetting("entityTypeName", "");
		if(!BX.type.isNotEmptyString(this._entityTypeName))
		{
			throw "BX.CrmRequisiteConverter. Could not find 'entityTypeName' parameter.";
		}
		this._entityTypeName = this._entityTypeName.toUpperCase();

		this._serviceUrl = this.getSetting("serviceUrl", "");
		if(!BX.type.isNotEmptyString(this._serviceUrl))
		{
			throw "BX.CrmRequisiteConverter. Could not find 'serviceUrl' parameter.";
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
	getMessage:function(name)
	{
		var m = BX.CrmRequisiteConverter.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	},
	convert: function()
	{
		if(this._presetId > 0)
		{
			this.openProcessDialog();
		}
		else
		{
			if(this._presetList === null)
			{
				this.openPresetListLoader();
			}
			else
			{
				this.openPresetSelector();
			}
		}
	},
	skip: function()
	{
		BX.ajax(
			{
				url: this._serviceUrl,
				method: "POST",
				dataType: "json",
				data:
				{
					"ACTION" : "SKIP_CONVERT_REQUISITES",
					"PARAMS": {}
				},
				onsuccess: BX.delegate(this._onRequestSuccsess, this),
				onfailure: BX.delegate(this._onRequestFailure, this)
			}
		);
	},
	openPresetListLoader: function()
	{
		if(!this._presetListLoader)
		{
			this._presetListLoader = BX.CrmRequisitePresetListLoader.create(
				this._id,
				{
					entityTypeName: this._entityTypeName,
					serviceUrl: this._serviceUrl,
					callback: this._presetListLoadHandler
				}
			);
		}
		this._presetListLoader.start();
	},
	onPresetListLoad: function(sender, params)
	{
		this._presetList = params["isSuccessed"] ? params["resultData"] : [];
		this.openPresetSelector();
	},
	openPresetSelector: function()
	{
		if(!this._presetSelector)
		{
			this._presetSelector = BX.CrmRequisitePresetSelectDialog.create(
				this._id,
				{
					list: this._presetList,
					callback: this._presetSelectHandler
				}
			);
		}
		this._presetSelector.show();
	},
	onPresetSelect: function(sender, params)
	{
		if(this._presetSelector)
		{
			if(params["isAccepted"])
			{
				this._presetId = parseInt(params["selectedValue"]);
				this.openProcessDialog();
			}
			this._presetSelector.close();
		}
	},
	openProcessDialog: function()
	{
		if(!this._processDialog)
		{
			var entityTypeNameC = this._entityTypeName.toLowerCase().replace(/(?:^)\S/, function(c){ return c.toUpperCase(); });
			var key = "convert" + entityTypeNameC + "Requisites";

			this._processDialog = BX.CrmLongRunningProcessDialog.create(
				key,
				{
					serviceUrl: this._serviceUrl,
					action: "CONVERT_REQUISITES",
					params:
					{
						"ENTITY_TYPE_NAME": this._entityTypeName,
						"PRESET_ID": this._presetId
					},
					title: this.getMessage("processDialogTitle"),
					summary: this.getMessage("processDialogSummary")
				}
			);

			BX.addCustomEvent(this._processDialog, "ON_STATE_CHANGE", this._processStateChangeHandler);
		}

		this._processDialog.show();
	},
	closeProcessDialog: function()
	{
		if(this._processDialog)
		{
			this._processDialog.close();
			this._processDialog = null;
		}
	},
	onProcessStateChange: function(sender)
	{
		if(sender.getState() === BX.CrmLongRunningProcessState.completed)
		{
			//ON_CONTACT_REQUISITE_TRANFER_COMPLETE, ON_COMPANY_REQUISITE_TRANFER_COMPLETE
			BX.onCustomEvent(this, "ON_" + this._entityTypeName + "_REQUISITE_TRANFER_COMPLETE", [this]);
		}
	}
};
if(typeof(BX.CrmRequisiteConverter.messages) === "undefined")
{
	BX.CrmRequisiteConverter.messages = {};
}
BX.CrmRequisiteConverter.create = function(id, settings)
{
	var self = new BX.CrmRequisiteConverter();
	self.initialize(id, settings);
	return self;
};
//endregion
//region BX.CrmPSRequisiteConverter
BX.CrmPSRequisiteConverter = function()
{
	this._id = "";
	this._settings = {};
	this._serviceUrl = "";

	this._processDialog = null;
	this._processStateChangeHandler = BX.delegate(this.onProcessStateChange, this);
};
BX.CrmPSRequisiteConverter.prototype =
{
	initialize: function(id, settings)
	{
		this._id = id;
		this._settings = settings ? settings : {};

		this._serviceUrl = this.getSetting("serviceUrl", "");
		if(!BX.type.isNotEmptyString(this._serviceUrl))
		{
			throw "BX.CrmPSRequisiteConverter. Could not find 'serviceUrl' parameter.";
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
	getMessage:function(name)
	{
		var m = BX.CrmPSRequisiteConverter.messages;
		return m.hasOwnProperty(name) ? m[name] : name;
	},
	convert: function()
	{
		this.openProcessDialog();
	},
	skip: function()
	{
		BX.ajax(
			{
				url: this._serviceUrl,
				method: "POST",
				dataType: "json",
				data:
				{
					"ACTION" : "SKIP_CONVERT_PS_REQUISITES",
					"PARAMS": {}
				},
				onsuccess: BX.delegate(this._onRequestSuccsess, this),
				onfailure: BX.delegate(this._onRequestFailure, this)
			}
		);
	},
	openProcessDialog: function()
	{
		if(!this._processDialog)
		{
			this._processDialog = BX.CrmLongRunningProcessDialog.create(
				"convertPSRequisites",
				{
					serviceUrl: this._serviceUrl,
					action: "CONVERT_PS_REQUISITES",
					params:
					{
						"ENTITY_TYPE_NAME": this._entityTypeName,
						"PRESET_ID": this._presetId
					},
					title: this.getMessage("processDialogTitle"),
					summary: this.getMessage("processDialogSummary")
				}
			);

			BX.addCustomEvent(this._processDialog, "ON_STATE_CHANGE", this._processStateChangeHandler);
		}

		this._processDialog.show();
	},
	closeProcessDialog: function()
	{
		if(this._processDialog)
		{
			this._processDialog.close();
			this._processDialog = null;
		}
	},
	onProcessStateChange: function(sender)
	{
		if(sender.getState() === BX.CrmLongRunningProcessState.completed)
		{
			BX.onCustomEvent(this, "ON_PS_REQUISITE_TRANFER_COMPLETE", [this]);
		}
	}
};
if(typeof(BX.CrmPSRequisiteConverter.messages) === "undefined")
{
	BX.CrmPSRequisiteConverter.messages = {};
}
BX.CrmPSRequisiteConverter.create = function(id, settings)
{
	var self = new BX.CrmPSRequisiteConverter();
	self.initialize(id, settings);
	return self;
};
//endregion
//region BX.CrmDealCategory
if(typeof(BX.CrmDealCategory) == "undefined")
{
	BX.CrmDealCategory = function()
	{
	};

	BX.CrmDealCategory.getDefaultValue = function()
	{
		return "0";
	};
	BX.CrmDealCategory.getListItems = function(infos)
	{
		if(!BX.type.isArray(infos))
		{
			infos = BX.CrmDealCategory.infos;
		}

		var results = [];
		for(var i = 0, l = infos.length; i < l; i++)
		{
			var info = infos[i];
			results.push({ value: info["id"], text: info["name"] });
		}
		return results;
	};

	if(typeof(BX.CrmDealCategory.infos) === "undefined")
	{
		BX.CrmDealCategory.infos = [];
	}
}
//endregion
//region BX.CrmDealCategorySelector
if(typeof(BX.CrmDealCategorySelector) == "undefined")
{
	BX.CrmDealCategorySelector = function()
	{
		this._id = "";
		this._settings = {};
		this._selectorMenu = null;
		this._menuItemSelectHandler = BX.delegate(this.onMenuItemSelect, this);
		this._canCreateCategory = false;
		this._createUrl = "";
		this._categoryListUrl = "";
		this._categoryCreateUrl = "";
	};

	BX.CrmDealCategorySelector.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._canCreateCategory = !!this.getSetting("canCreateCategory", false);
			this._createUrl = this.getSetting("createUrl", "");
			this._categoryListUrl = this.getSetting("categoryListUrl", "");
			this._categoryCreateUrl = this.getSetting("categoryCreateUrl", "");
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
			var m = BX.CrmDealCategorySelector.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		redirectToCreateUrl: function(categoryId)
		{
			if(this._createUrl === "")
			{
				return;
			}

			window.location = categoryId > 0
				? BX.util.add_url_param(this._createUrl, { "category_id": categoryId })
				: this._createUrl;
		},
		openMenu: function(anchor)
		{
			if(!this._selectorMenu)
			{
				var items = BX.CrmDealCategory.getListItems();
				if(this._canCreateCategory)
				{
					items.push({ text: this.getMessage("create"), value: "new" });
				}
				this._selectorMenu = BX.CmrSelectorMenu.create(this._id, { items: items });
				this._selectorMenu.addOnSelectListener(this._menuItemSelectHandler);
			}

			if(!this._selectorMenu.isOpened())
			{
				this._selectorMenu.open(anchor);
			}
		},
		onMenuItemSelect: function(sender, selectedItem)
		{
			var selectedValue = selectedItem.getValue();
			if(this._selectorMenu.isOpened())
			{
				this._selectorMenu.close();
			}

			if(selectedValue === "new")
			{
				window.location = this._categoryCreateUrl;
			}
			else
			{
				this.redirectToCreateUrl(parseInt(selectedValue));
			}
		}
	};

	if(typeof(BX.CrmDealCategorySelector.messages) === "undefined")
	{
		BX.CrmDealCategorySelector.messages = {};
	}
	BX.CrmDealCategorySelector.items = {};
	BX.CrmDealCategorySelector.create = function(id, settings)
	{
		var self = new BX.CrmDealCategorySelector();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}
//endregion
//region BX.CrmDealCategorySelectDialog
if(typeof(BX.CrmDealCategorySelectDialog) === "undefined")
{
	BX.CrmDealCategorySelectDialog = function()
	{
		this._id = "";
		this._settings = {};
		this._popup = null;
		this._selector = null;
		this._value = "";
		this._isOpened = false;
		this._closeNotifier = null;
	};
	BX.CrmDealCategorySelectDialog.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._value = parseInt(this.getSetting("value", 0));
			if(isNaN(this._value))
			{
				this._value = 0;
			}
			this._closeNotifier = BX.CrmNotifier.create(this);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getMessage: function(name)
		{
			var m = BX.CrmDealCategorySelectDialog.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		isOpened: function()
		{
			return this._isOpened;
		},
		open: function()
		{
			if(this._isOpened)
			{
				return;
			}

			this._popup = new BX.PopupWindow(
				this._id,
				null,
				{
					autoHide: false,
					draggable: true,
					offsetLeft: 0,
					offsetTop: 0,
					bindOptions: { forceBindPosition: true },
					closeByEsc: true,
					closeIcon: { top: "10px", right: "15px" },
					titleBar: this.getMessage("title"),
					content: this.prepareContent(),
					events:
					{
						onPopupShow: BX.delegate(this.onPopupShow, this),
						onPopupClose: BX.delegate(this.onPopupClose, this),
						onPopupDestroy: BX.delegate(this.onPopupDestroy, this)
					},
					buttons: this.prepareButtons()
				}
			);
			this._popup.show();
		},
		close: function()
		{
			if (this._popup)
			{
				this._popup.close();
			}
		},
		addCloseListener: function(listener)
		{
			this._closeNotifier.addListener(listener);
		},
		removeCloseListener: function(listener)
		{
			this._closeNotifier.removeListener(listener);
		},
		prepareContent: function()
		{
			var table = BX.create("TABLE",
				{
					attrs:
					{
						className: "bx-crm-deal-category-selector-dialog",
						cellspacing: "2"
					}
				}
			);
			var r, c;
			r = table.insertRow(-1);
			c = r.insertCell(-1);
			c.appendChild(BX.create("LABEL", { text: this.getMessage("field") + ":" }));
			c = r.insertCell(-1);
			this._selector = BX.create("SELECT", {});
			BX.HtmlHelper.setupSelectOptions(this._selector, BX.CrmDealCategory.getListItems());
			this._selector.value = this._value;
			c.appendChild(this._selector);

			return table;
		},
		prepareButtons: function()
		{
			return(
				[
					new BX.PopupWindowButton(
						{
							text: this.getMessage("saveButton"),
							className: "popup-window-button-accept",
							events: { click: BX.delegate(this.processSave, this) }
						}
					),
					new BX.PopupWindowButtonLink(
						{
							text: this.getMessage("cancelButton"),
							className: "popup-window-button-link-cancel",
							events: { click: BX.delegate(this.processCancel, this) }
						}
					)
				]);
		},
		getValue: function()
		{
			return this._value;
		},
		setValue: function(value)
		{
			value = parseInt(value);
			if(isNaN(value))
			{
				value = 0;
			}
			this._value = value;
		},
		processSave: function()
		{
			this._value = parseInt(this._selector.value);
			if(isNaN(this._value))
			{
				this._value = 0;
			}

			this._closeNotifier.notify([{ isCanceled: false }]);
			this.close();
		},
		processCancel: function()
		{
			this._closeNotifier.notify([{ isCanceled: true }]);
			this.close();
		},
		onPopupShow: function()
		{
			this._isOpened = true;
		},
		onPopupClose: function()
		{
			if(this._popup)
			{
				this._popup.destroy();
			}
		},
		onPopupDestroy: function()
		{
			this._isOpened = false;
			this._popup = null;
		}
	};

	if(typeof(BX.CrmDealCategorySelectDialog.messages) === "undefined")
	{
		BX.CrmDealCategorySelectDialog.messages = {};
	}
	BX.CrmDealCategorySelectDialog.create = function(id, settings)
	{
		var self = new BX.CrmDealCategorySelectDialog();
		self.initialize(id, settings);
		return self;
	};
}
//endregion

if(typeof(BX.CrmHtmlLoader) == "undefined")
{
	BX.CrmHtmlLoader = function()
	{
		this._id = "";
		this._settings = {};
		this._params = {};
		this._serviceUrl = "";
		this._requestIsRunning = false;
		this._button = null;
		this._wrapper = null;
		this._buttonClickHandler = BX.delegate(this.onButtonClick, this);
	};
	BX.CrmHtmlLoader.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._serviceUrl = this.getSetting("serviceUrl");
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw "BX.CrmHtmlLoader: service url not found!";
			}

			this._action = this.getSetting("action");
			if(!BX.type.isNotEmptyString(this._action))
			{
				throw "BX.CrmHtmlLoader: action not found!";
			}

			this._params = this.getSetting("params", {});

			this._button = BX(this.getSetting("button"));
			if(!BX.type.isElementNode(this._button))
			{
				throw "BX.CrmHtmlLoader: button element not found!";
			}
			BX.bind(this._button, "click", this._buttonClickHandler);

			this._wrapper = BX(this.getSetting("wrapper"));
			if(!BX.type.isElementNode(this._wrapper))
			{
				throw "BX.CrmHtmlLoader: wrapper element not found!";
			}

		},
		release: function()
		{
			BX.unbind(this._button, "click", this._buttonClickHandler);
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		onButtonClick: function(e)
		{
			this.startRequest();
			return BX.PreventDefault(e);
		},
		startRequest: function()
		{
			if(this._requestIsRunning)
			{
				return;
			}
			this._requestIsRunning = true;
			BX.showWait();

			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data:
					{
						"ACTION": this._action,
						"PARAMS": this._params
					},
					onsuccess: BX.delegate(this.onRequestSuccsess, this),
					onfailure: BX.delegate(this.onRequestFailure, this)
				}
			);
		},
		onRequestSuccsess: function(result)
		{
			BX.closeWait();
			this._requestIsRunning = false;

			if(BX.type.isPlainObject(result["ERROR"]))
			{
				this.showError(result["ERROR"]);
				return;
			}

			if(BX.type.isPlainObject(result["DATA"]))
			{
				var data = result["DATA"];
				if(BX.type.isNotEmptyString(data["HTML"]))
				{
					this._wrapper.innerHTML = data["HTML"];
				}
				else if(BX.type.isNotEmptyString(data["TEXT"]))
				{
					this._wrapper.innerHTML = BX.util.htmlspecialchars(data["TEXT"]);
				}
			}
		},
		onRequestFailure: function(result)
		{
			BX.closeWait();
			this._requestIsRunning = false;
		},
		showError: function(error)
		{
			if(BX.type.isPlainObject(error) && BX.type.isNotEmptyString(error["MESSAGE"]))
			{
				alert(error["MESSAGE"]);
			}
		}
	};
	BX.CrmHtmlLoader.create = function(id, settings)
	{
		var self = new BX.CrmHtmlLoader();
		self.initialize(id, settings);
		return self;
	}
}
if(typeof(BX.CrmDataLoader) == "undefined")
{
	BX.CrmDataLoader = function()
	{
		this._id = "";
		this._settings = {};
		this._params = {};
		this._serviceUrl = "";
		this._requestIsRunning = false;
		this._notifier = null;
		this._result = null;
	};
	BX.CrmDataLoader.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._serviceUrl = this.getSetting("serviceUrl");
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw "BX.CrmDataLoader: service url not found!";
			}

			this._action = this.getSetting("action");
			if(!BX.type.isNotEmptyString(this._action))
			{
				throw "BX.CrmDataLoader: action not found!";
			}

			this._params = this.getSetting("params", {});

			this._notifier = BX.CrmNotifier.create(this);
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getResult: function()
		{
			return this._result;
		},
		isRequestRunning: function()
		{
			return this._requestIsRunning;
		},
		addCallBack: function(callback)
		{
			if(!BX.type.isFunction(callback))
			{
				return;
			}

			for(var i = 0; this._callbacks.length; i++)
			{
				if(this._callbacks[i] === callback)
				{
					return;
				}
			}

			this._callbacks.push(callback);
		},
		load: function(callback)
		{
			if(!BX.type.isFunction(callback))
			{
				callback = null;
			}

			if(this._result === null)
			{
				this._notifier.addListener(callback);
				this.startRequest();
			}
			else if(callback !== null)
			{
				callback(this._result);
			}
		},
		startRequest: function()
		{
			if(this._requestIsRunning)
			{
				return;
			}

			this._requestIsRunning = true;
			BX.showWait();

			BX.ajax(
				{
					url: this._serviceUrl,
					method: "POST",
					dataType: "json",
					data: { "ACTION": this._action, "PARAMS": this._params },
					onsuccess: BX.delegate(this.onRequestSuccsess, this),
					onfailure: BX.delegate(this.onRequestFailure, this)
				}
			);
		},
		onRequestSuccsess: function(result)
		{
			BX.closeWait();
			this._requestIsRunning = false;

			this._result = BX.type.isPlainObject(result) ? result : {};

			this._notifier.notify([ this._result ]);
			this._notifier.resetListeners();
		},
		onRequestFailure: function(result)
		{
			BX.closeWait();
			this._requestIsRunning = false;

			this._result = BX.type.isPlainObject(result) ? result : {};
			this._notifier.notify([ this._result ]);
			this._notifier.resetListeners();
		}
	};
	BX.CrmDataLoader.create = function(id, settings)
	{
		var self = new BX.CrmDataLoader();
		self.initialize(id, settings);
		return self;
	}
}
if(typeof(BX.CrmRemoteAction))
{
	BX.CrmRemoteAction = function()
	{
		this._id = "";
		this._settings = {};
		this._serviceUrl = "";
		this._redirectUrl = "";
	};
	BX.CrmRemoteAction.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._serviceUrl = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(this._serviceUrl))
			{
				throw "BX.CrmRemoteAction: service url not found!";
			}

			this._redirectUrl = this.getSetting("redirectUrl", "");
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		execute: function(redirectUrl)
		{
			if(BX.type.isNotEmptyString(redirectUrl))
			{
				this._redirectUrl = redirectUrl;
			}

			BX.ajax(
				{
					method: "POST",
					dataType: "html",
					url: this._serviceUrl,
					data: this.getSetting("data", {}),
					onsuccess: BX.delegate(this.onActionSuccess, this)
				}
			);
		},
		onActionSuccess: function(data)
		{
			if(BX.type.isNotEmptyString(this._redirectUrl))
			{
				document.location.href = this._redirectUrl;
			}
		}
	};
	BX.CrmRemoteAction.items = {};
	BX.CrmRemoteAction.create = function(id, settings)
	{
		var self = new BX.CrmRemoteAction();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	}
}

if(typeof(BX.CrmDeletionConfirmDialog) === "undefined")
{
	BX.CrmDeletionConfirmDialog = function()
	{
		this._id = "";
		this._settings = {};
		this._name = "";
		this._path = "";
		this._messages = {};
		this._dlg = null;
		this._closeNotifier = null;
	};
	BX.CrmDeletionConfirmDialog.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._name = this.getSetting("name", "");
			this._path = this.getSetting("path", "");
			if(!BX.type.isNotEmptyString(this._path))
			{
				throw "BX.CrmDeletionConfirmDialog: Could not find parameter 'path'.";
			}

			this._messages = this.getSetting("messages", {});
			this._closeNotifier = BX.CrmNotifier.create(this);
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getMessage: function(name)
		{
			return this._messages.hasOwnProperty(name) ? this._messages[name] : name;
		},
		open: function()
		{
			this._dlg = new BX.CDialog(
				{
					title: this.getMessage("title"),
					head: "",
					content: this.getMessage("confirm").replace(/#NAME#/gi, this._name),
					resizable: false,
					draggable: true,
					height: 70,
					width: 300
				}
			);

			this._dlg.SetButtons(
				[
					{
						title: this.getMessage("deleteButton"),
						id: "delete",
						action: BX.delegate(this.onAction, this)
					},
					BX.CDialog.btnClose
				]
			);
			this._dlg.Show();
		},
		close: function()
		{
			if(this._dlg)
			{
				this._dlg.Close();
			}
		},
		onAction: function()
		{
			this.close();
			window.location.href = this._path;
		}
	};
	BX.CrmDeletionConfirmDialog.create = function(id, settings)
	{
		var self = new BX.CrmDeletionConfirmDialog();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.FilterUserSelector) == "undefined")
{
	BX.FilterUserSelector = function()
	{
		this._id = "";
		this._settings = {};
		this._fieldId = "";
		this._control = null;

		this._currentUser = null;
		this._componentName = null;
		this._componentObj = null;
		this._componentContainer = null;
		this._serviceContainer = null;

		this._zIndex = 1100;
		this._isDialogDisplayed = false;
		this._dialog = null;

		this._inputKeyPressHandler = BX.delegate(this.onInputKeyPress, this);
		//this._externalClickHandler = BX.delegate(this.onExternalClick, this);
	};

	BX.FilterUserSelector.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._fieldId = this.getSetting("fieldId", "");
			this._componentName = this.getSetting("componentName", "");
			this._componentContainer = BX(this._componentName + "_selector_content");

			this._serviceContainer = this.getSetting("serviceContainer", null);
			if(!BX.type.isDomNode(this._serviceContainer))
			{
				this._serviceContainer = document.body;
			}

			BX.addCustomEvent(window, "BX.Main.Filter:customEntityFocus", BX.delegate(this.onCustomEntitySelectorOpen, this));
			BX.addCustomEvent(window, "BX.Main.Filter:customEntityBlur", BX.delegate(this.onCustomEntitySelectorClose, this));
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name)  ? this._settings[name] : defaultval;
		},
		getSearchInput: function()
		{
			return this._control ? this._control.getLabelNode() : null;
		},
		isOpened: function()
		{
			return this._isDialogDisplayed;
		},
		open: function()
		{
			if(this._componentObj === null)
			{
				var objName = "O_" + this._componentName;
				if(!window[objName])
				{
					throw "BX.FilterUserSelector: Could not find '"+ objName +"' user selector.";
				}
				this._componentObj = window[objName];
			}

			var searchInput = this.getSearchInput();
			if(this._componentObj.searchInput)
			{
				BX.unbind(this._componentObj.searchInput, "keyup", BX.proxy(this._componentObj.search, this._componentObj));
			}
			this._componentObj.searchInput = searchInput;
			BX.bind(this._componentObj.searchInput, "keyup", BX.proxy(this._componentObj.search, this._componentObj));
			this._componentObj.onSelect = BX.delegate(this.onSelect, this);
			BX.bind(searchInput, "keyup", this._inputKeyPressHandler);
			//BX.bind(document, "click", this._externalClickHandler);

			if(this._currentUser)
			{
				this._componentObj.setSelected([ this._currentUser ]);
			}
			else
			{
				var selected = this._componentObj.getSelected();
				if(selected)
				{
					for(var key in selected)
					{
						if(selected.hasOwnProperty(key))
						{
							this._componentObj.unselect(key);
						}
					}
				}
				//this._componentObj.displayTab("last");
			}

			if(this._dialog === null)
			{
				this._componentContainer.style.display = "";
				this._dialog = new BX.PopupWindow(
					this._id,
					this.getSearchInput(),
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
								onPopupShow: BX.delegate(this.onDialogShow, this),
								onPopupClose: BX.delegate(this.onDialogClose, this),
								onPopupDestroy: BX.delegate(this.onDialogDestroy, this)
							}
					}
				);
			}

			this._dialog.show();
			this._componentObj._onFocus();

			if(this._control)
			{
				this._control.setPopupContainer(this._componentContainer);
			}
		},
		close: function()
		{
			var searchInput = this.getSearchInput();
			if(searchInput)
			{
				BX.unbind(searchInput, "keyup", this._inputKeyPressHandler);
			}

			if(this._dialog)
			{
				this._dialog.close();
			}

			if(this._control)
			{
				this._control.setPopupContainer(null);
			}

		},
		closeSiblings: function()
		{
			var siblings = BX.FilterUserSelector.items;
			for(var k in siblings)
			{
				if(siblings.hasOwnProperty(k) && siblings[k] !== this)
				{
					siblings[k].close();
				}
			}
		},
		onCustomEntitySelectorOpen: function(control)
		{
			var fieldId = control.getId();
			if(this._fieldId !== fieldId)
			{
				this._control = null;
				this.close();
			}
			else
			{
				this._control = control;
				if(this._control)
				{
					var current = this._control.getCurrentValues();
					this._currentUser = { "id": current["value"] };
				}
				this.closeSiblings();
				this.open();
			}
		},
		onCustomEntitySelectorClose: function(control)
		{
			if(this._fieldId === control.getId())
			{
				this._control = null;
				this.close();
			}
		},
		onDialogShow: function()
		{
			this._isDialogDisplayed = true;
		},
		onDialogClose: function()
		{
			this._componentContainer.parentNode.removeChild(this._componentContainer);
			this._serviceContainer.appendChild(this._componentContainer);
			this._componentContainer.style.display = "none";

			this._dialog.destroy();
			this._isDialogDisplayed = false;
		},
		onDialogDestroy: function()
		{
			this._dialog = null;
		},
		onInputKeyPress: function(e)
		{
			if(!this._dialog || !this._isDialogDisplayed)
			{
				this.open();
			}

			if(this._componentObj)
			{
				this._componentObj.search();
			}
		},
		/*
		 onExternalClick: function(e)
		 {
		 if(!e)
		 {
		 e = window.event;
		 }

		 if(!this._isDialogDisplayed)
		 {
		 return;
		 }

		 if(BX.getEventTarget(e) !== this.getSearchInput())
		 {
		 this.close();
		 }
		 },
		 */
		onSelect: function(user)
		{
			this._currentUser = user;
			if(this._control)
			{
				//CRUTCH: Intranet User Selector already setup input value.
				var node = this._control.getLabelNode();
				node.value = "";
				this._control.setData(user["name"], user["id"]);
			}
			this.close();
		}
	};
	BX.FilterUserSelector.closeAll = function()
	{
		for(var k in this.items)
		{
			if(this.items.hasOwnProperty(k))
			{
				this.items[k].close();
			}
		}
	};
	BX.FilterUserSelector.items = {};
	BX.FilterUserSelector.create = function(id, settings)
	{
		var self = new BX.FilterUserSelector(id, settings);
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	}
}

if(typeof(BX.FilterUserSelector2) == "undefined")
{
	BX.FilterUserSelector2 = function()
	{
		this._id = "";
		this._settings = {};
		this._fieldId = "";
		this._control = null;

		this._currentUser = null;
		this._componentName = null;
		this._componentObj = null;
		this._componentContainer = null;
		this._serviceContainer = null;

		this._zIndex = 1100;
		this._isDialogDisplayed = false;
		this._dialog = null;

		this._mainWindow = null;

		this._inputKeyPressHandler = BX.delegate(this.onInputKeyPress, this);
	};

	BX.FilterUserSelector2.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};
			this._fieldId = this.getSetting("fieldId", "");
			this._componentName = this.getSetting("componentName", "");
			this._componentContainer = BX(this._componentName + "_selector_content");

			//this._serviceContainer = this.getSetting("serviceContainer", null);
			//if(!BX.type.isDomNode(this._serviceContainer))
			//{
			//	this._serviceContainer = document.body;
			//}

			BX.addCustomEvent(window, "BX.Main.Filter:customEntityFocus", BX.delegate(this.onCustomEntitySelectorOpen, this));
			BX.addCustomEvent(window, "BX.Main.Filter:customEntityBlur", BX.delegate(this.onCustomEntitySelectorClose, this));
		},
		getId: function()
		{
			return this._id;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		getSearchInput: function()
		{
			return this._control ? this._control.getLabelNode() : null;
		},
		onCustomEntitySelectorOpen: function(control)
		{
			var fieldId = control.getId();
			if(this._fieldId !== fieldId)
			{
				this._control = null;
				this.close();
			}
			else
			{
				this._control = control;
				if(this._control)
				{
					var current = this._control.getCurrentValues();
					this._currentUser = { "entityId": current["value"] };
				}
				//this.closeSiblings();
				this.open();
			}
		},
		onCustomEntitySelectorClose: function(control)
		{
			if(this._fieldId === control.getId())
			{
				this._control = null;
				this.close();
			}
		},
		open: function()
		{
			if(this._mainWindow && this._mainWindow === BX.SocNetLogDestination.popupWindow)
			{
				return;
			}

			var name = this._id;
			var input = this.getSearchInput();
			input.id = input.name;

			var users = this.getSetting("users", {});
			var last = this.getSetting("last", {});
			var department = this.getSetting("department", {});
			var departmentRelation = BX.SocNetLogDestination.buildDepartmentRelation(department);

			BX.SocNetLogDestination.init(
				{
					name : name,
					searchInput : input,
					extranetUser :  false,
					bindMainPopup : {
						node: input,
						offsetTop: '5px',
						offsetLeft: '15px'
					},
					bindSearchPopup : {
						node : input,
						offsetTop : '5px',
						offsetLeft: '15px'
					},
					callback : {
						select : BX.delegate(this.onSelect, this)
						/*
						unSelect : BX.delegate(BX.SocNetLogDestination.BXfpUnSelectCallback, {
							formName: name,
							inputContainerName: 'feed-add-post-where-item',
							inputName: 'feed-add-post-where-input',
							tagInputName: 'bx-where-tag',
							tagLink1: BX.message('CRM_SL_EVENT_EDIT_MPF_WHERE_1'),
							tagLink2: BX.message('CRM_SL_EVENT_EDIT_MPF_WHERE_2')
						}),
						openDialog : BX.delegate(BX.SocNetLogDestination.BXfpOpenDialogCallback, {
							inputBoxName: 'feed-add-post-where-input-box',
							inputName: 'feed-add-post-where-input',
							tagInputName: 'bx-where-tag'
						}),
						closeDialog : BX.delegate(BX.SocNetLogDestination.BXfpCloseDialogCallback, {
							inputBoxName: 'feed-add-post-where-input-box',
							inputName: 'feed-add-post-where-input',
							tagInputName: 'bx-where-tag'
						}),
						*/
						//openSearch : BX.delegate(this.onSearchOpen, this)
					},
					showSearchInput: false,
					departmentSelectDisable: true,
					items:
					{
						users : users,
						groups : {},
						sonetgroups : {},
						department : department,
						departmentRelation : departmentRelation
					},
					itemsLast: this.getSetting("last", {}),
					itemsSelected : {},
					isCrmFeed : false,
					useClientDatabase: false,
					destSort: {},
					allowAddUser: false,
					allowSearchCrmEmailUsers: false,
					allowUserSearch: true
					//userNameTemplate: (typeof params.userNameTemplate != 'undefined' ? params.userNameTemplate : '')
				}
			);

			BX.bind(
				input,
				"keyup",
				BX.delegate(
					BX.SocNetLogDestination.BXfpSearch,
					{
						formName: name,
						inputName: input.id
					}
				)
			);

			BX.bind(
				input,
				"keydown",
				BX.delegate(
					BX.SocNetLogDestination.BXfpSearchBefore,
					{
						formName: name,
						inputName: input.id
					}
				)
			);

			BX.SocNetLogDestination.openDialog(name);

			this._mainWindow = BX.SocNetLogDestination.popupWindow;
			if(this._control && this._mainWindow)
			{
				this._control.setPopupContainer(this._mainWindow.contentContainer);
			}
		},
		close: function()
		{
			if(this._mainWindow && this._mainWindow === BX.SocNetLogDestination.popupWindow)
			{
				BX.SocNetLogDestination.closeDialog();
				this._mainWindow = null;
			}
		},
		onSelect: function(item, type, search, bUndeleted)
		{
			if(type !== "users")
			{
				return;
			}

			this._currentUser = item;
			if(this._control)
			{
				this._control.setData(item["name"], item["entityId"]);
			}
			this.close();
		}
	};

	BX.FilterUserSelector2.items = {};
	BX.FilterUserSelector2.create = function(id, settings)
	{
		var self = new BX.FilterUserSelector2(id, settings);
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	}
}

if(typeof(BX.CrmSearchContentManager) == "undefined")
{
	BX.CrmSearchContentManager = function()
	{
		this._id = "";
		this._settings = {};
		this._entityTypeName = "";
		this._processDialogs = {};
	};
	BX.CrmSearchContentManager.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = BX.type.isNotEmptyString(id) ? id : "crm_search_content_mgr_" + Math.random().toString().substring(2);
			this._settings = settings ? settings : {};

			this._entityTypeName = this.getSetting("entityTypeName", "");
			if(!BX.type.isNotEmptyString(this._entityTypeName))
			{
				throw "BX.CrmSearchContentManager. Could not find entity type name.";
			}

			this._entityTypeName = this._entityTypeName.toUpperCase();
		},
		getId: function()
		{
			return this._id;
		},
		getEntityTypeName: function()
		{
			return this._entityTypeName;
		},
		getSetting: function (name, defaultval)
		{
			return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
		},
		setSetting: function (name, val)
		{
			this._settings[name] = val;
		},
		getMessage: function(name)
		{
			var m = BX.CrmSearchContentManager.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		rebuildIndex: function()
		{
			var serviceUrl = this.getSetting("serviceUrl", "");
			if(!BX.type.isNotEmptyString(serviceUrl))
			{
				throw "BX.CrmSearchContentManager. Could not find service url.";
			}

			var entityTypeNameC = this._entityTypeName.toLowerCase().replace(/(?:^)\S/, function(c){ return c.toUpperCase(); });
			var key = "rebuild" + entityTypeNameC;

			var processDlg = null;
			if(typeof(this._processDialogs[key]) !== "undefined")
			{
				processDlg = this._processDialogs[key];
			}
			else
			{
				processDlg = BX.CrmLongRunningProcessDialog.create(
					key,
					{
						serviceUrl: serviceUrl,
						action:"REBUILD_SEARCH_CONTENT",
						params:{ "ENTITY_TYPE_NAME": this._entityTypeName },
						title: this.getMessage(key + "DlgTitle"),
						summary: this.getMessage(key + "DlgSummary")
					}
				);

				this._processDialogs[key] = processDlg;
				BX.addCustomEvent(processDlg, 'ON_STATE_CHANGE', BX.delegate(this._onProcessStateChange, this));
			}
			processDlg.show();
		},
		_onProcessStateChange: function(sender)
		{
			var key = sender.getId();
			if(typeof(this._processDialogs[key]) !== "undefined")
			{
				var processDlg = this._processDialogs[key];
				if(processDlg.getState() === BX.CrmLongRunningProcessState.completed)
				{
					//ON_CONTACT_SEARCH_CONTENT_REBUILD_COMPLETE
					BX.onCustomEvent(this, "ON_" + this._entityTypeName + "_SEARCH_CONTENT_REBUILD_COMPLETE", [this]);
				}
			}
		}
	};
	if(typeof(BX.CrmSearchContentManager.messages) == "undefined")
	{
		BX.CrmSearchContentManager.messages = {};
	}
	BX.CrmSearchContentManager.items = {};
	BX.CrmSearchContentManager.create = function(id, settings)
	{
		var self = new BX.CrmSearchContentManager();
		self.initialize(id, settings);
		this.items[self.getId()] = self;
		return self;
	};
}

if(typeof(cssQuery) !== "function")
{
	eval(function(p,a,c,k,e,d){e=function(c){return(c<a?'':e(parseInt(c/a)))+((c=c%a)>35?String.fromCharCode(c+29):c.toString(36))};if(!''.replace(/^/,String)){while(c--)d[e(c)]=k[c]||e(c);k=[function(e){return d[e]}];e=function(){return'\\w+'};c=1};while(c--)if(k[c])p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c]);return p}('7 x=6(){7 1D="2.0.2";7 C=/\\s*,\\s*/;7 x=6(s,A){33{7 m=[];7 u=1z.32.2c&&!A;7 b=(A)?(A.31==22)?A:[A]:[1g];7 1E=18(s).1l(C),i;9(i=0;i<1E.y;i++){s=1y(1E[i]);8(U&&s.Z(0,3).2b("")==" *#"){s=s.Z(2);A=24([],b,s[1])}1A A=b;7 j=0,t,f,a,c="";H(j<s.y){t=s[j++];f=s[j++];c+=t+f;a="";8(s[j]=="("){H(s[j++]!=")")a+=s[j];a=a.Z(0,-1);c+="("+a+")"}A=(u&&V[c])?V[c]:21(A,t,f,a);8(u)V[c]=A}m=m.30(A)}2a x.2d;5 m}2Z(e){x.2d=e;5[]}};x.1Z=6(){5"6 x() {\\n  [1D "+1D+"]\\n}"};7 V={};x.2c=L;x.2Y=6(s){8(s){s=1y(s).2b("");2a V[s]}1A V={}};7 29={};7 19=L;x.15=6(n,s){8(19)1i("s="+1U(s));29[n]=12 s()};x.2X=6(c){5 c?1i(c):o};7 D={};7 h={};7 q={P:/\\[([\\w-]+(\\|[\\w-]+)?)\\s*(\\W?=)?\\s*([^\\]]*)\\]/};7 T=[];D[" "]=6(r,f,t,n){7 e,i,j;9(i=0;i<f.y;i++){7 s=X(f[i],t,n);9(j=0;(e=s[j]);j++){8(M(e)&&14(e,n))r.z(e)}}};D["#"]=6(r,f,i){7 e,j;9(j=0;(e=f[j]);j++)8(e.B==i)r.z(e)};D["."]=6(r,f,c){c=12 1t("(^|\\\\s)"+c+"(\\\\s|$)");7 e,i;9(i=0;(e=f[i]);i++)8(c.l(e.1V))r.z(e)};D[":"]=6(r,f,p,a){7 t=h[p],e,i;8(t)9(i=0;(e=f[i]);i++)8(t(e,a))r.z(e)};h["2W"]=6(e){7 d=Q(e);8(d.1C)9(7 i=0;i<d.1C.y;i++){8(d.1C[i]==e)5 K}};h["2V"]=6(e){};7 M=6(e){5(e&&e.1c==1&&e.1f!="!")?e:23};7 16=6(e){H(e&&(e=e.2U)&&!M(e))28;5 e};7 G=6(e){H(e&&(e=e.2T)&&!M(e))28;5 e};7 1r=6(e){5 M(e.27)||G(e.27)};7 1P=6(e){5 M(e.26)||16(e.26)};7 1o=6(e){7 c=[];e=1r(e);H(e){c.z(e);e=G(e)}5 c};7 U=K;7 1h=6(e){7 d=Q(e);5(2S d.25=="2R")?/\\.1J$/i.l(d.2Q):2P(d.25=="2O 2N")};7 Q=6(e){5 e.2M||e.1g};7 X=6(e,t){5(t=="*"&&e.1B)?e.1B:e.X(t)};7 17=6(e,t,n){8(t=="*")5 M(e);8(!14(e,n))5 L;8(!1h(e))t=t.2L();5 e.1f==t};7 14=6(e,n){5!n||(n=="*")||(e.2K==n)};7 1e=6(e){5 e.1G};6 24(r,f,B){7 m,i,j;9(i=0;i<f.y;i++){8(m=f[i].1B.2J(B)){8(m.B==B)r.z(m);1A 8(m.y!=23){9(j=0;j<m.y;j++){8(m[j].B==B)r.z(m[j])}}}}5 r};8(![].z)22.2I.z=6(){9(7 i=0;i<1z.y;i++){o[o.y]=1z[i]}5 o.y};7 N=/\\|/;6 21(A,t,f,a){8(N.l(f)){f=f.1l(N);a=f[0];f=f[1]}7 r=[];8(D[t]){D[t](r,A,f,a)}5 r};7 S=/^[^\\s>+~]/;7 20=/[\\s#.:>+~()@]|[^\\s#.:>+~()@]+/g;6 1y(s){8(S.l(s))s=" "+s;5 s.P(20)||[]};7 W=/\\s*([\\s>+~(),]|^|$)\\s*/g;7 I=/([\\s>+~,]|[^(]\\+|^)([#.:@])/g;7 18=6(s){5 s.O(W,"$1").O(I,"$1*$2")};7 1u={1Z:6(){5"\'"},P:/^(\'[^\']*\')|("[^"]*")$/,l:6(s){5 o.P.l(s)},1S:6(s){5 o.l(s)?s:o+s+o},1Y:6(s){5 o.l(s)?s.Z(1,-1):s}};7 1s=6(t){5 1u.1Y(t)};7 E=/([\\/()[\\]?{}|*+-])/g;6 R(s){5 s.O(E,"\\\\$1")};x.15("1j-2H",6(){D[">"]=6(r,f,t,n){7 e,i,j;9(i=0;i<f.y;i++){7 s=1o(f[i]);9(j=0;(e=s[j]);j++)8(17(e,t,n))r.z(e)}};D["+"]=6(r,f,t,n){9(7 i=0;i<f.y;i++){7 e=G(f[i]);8(e&&17(e,t,n))r.z(e)}};D["@"]=6(r,f,a){7 t=T[a].l;7 e,i;9(i=0;(e=f[i]);i++)8(t(e))r.z(e)};h["2G-10"]=6(e){5!16(e)};h["1x"]=6(e,c){c=12 1t("^"+c,"i");H(e&&!e.13("1x"))e=e.1n;5 e&&c.l(e.13("1x"))};q.1X=/\\\\:/g;q.1w="@";q.J={};q.O=6(m,a,n,c,v){7 k=o.1w+m;8(!T[k]){a=o.1W(a,c||"",v||"");T[k]=a;T.z(a)}5 T[k].B};q.1Q=6(s){s=s.O(o.1X,"|");7 m;H(m=s.P(o.P)){7 r=o.O(m[0],m[1],m[2],m[3],m[4]);s=s.O(o.P,r)}5 s};q.1W=6(p,t,v){7 a={};a.B=o.1w+T.y;a.2F=p;t=o.J[t];t=t?t(o.13(p),1s(v)):L;a.l=12 2E("e","5 "+t);5 a};q.13=6(n){1d(n.2D()){F"B":5"e.B";F"2C":5"e.1V";F"9":5"e.2B";F"1T":8(U){5"1U((e.2A.P(/1T=\\\\1v?([^\\\\s\\\\1v]*)\\\\1v?/)||[])[1]||\'\')"}}5"e.13(\'"+n.O(N,":")+"\')"};q.J[""]=6(a){5 a};q.J["="]=6(a,v){5 a+"=="+1u.1S(v)};q.J["~="]=6(a,v){5"/(^| )"+R(v)+"( |$)/.l("+a+")"};q.J["|="]=6(a,v){5"/^"+R(v)+"(-|$)/.l("+a+")"};7 1R=18;18=6(s){5 1R(q.1Q(s))}});x.15("1j-2z",6(){D["~"]=6(r,f,t,n){7 e,i;9(i=0;(e=f[i]);i++){H(e=G(e)){8(17(e,t,n))r.z(e)}}};h["2y"]=6(e,t){t=12 1t(R(1s(t)));5 t.l(1e(e))};h["2x"]=6(e){5 e==Q(e).1H};h["2w"]=6(e){7 n,i;9(i=0;(n=e.1F[i]);i++){8(M(n)||n.1c==3)5 L}5 K};h["1N-10"]=6(e){5!G(e)};h["2v-10"]=6(e){e=e.1n;5 1r(e)==1P(e)};h["2u"]=6(e,s){7 n=x(s,Q(e));9(7 i=0;i<n.y;i++){8(n[i]==e)5 L}5 K};h["1O-10"]=6(e,a){5 1p(e,a,16)};h["1O-1N-10"]=6(e,a){5 1p(e,a,G)};h["2t"]=6(e){5 e.B==2s.2r.Z(1)};h["1M"]=6(e){5 e.1M};h["2q"]=6(e){5 e.1q===L};h["1q"]=6(e){5 e.1q};h["1L"]=6(e){5 e.1L};q.J["^="]=6(a,v){5"/^"+R(v)+"/.l("+a+")"};q.J["$="]=6(a,v){5"/"+R(v)+"$/.l("+a+")"};q.J["*="]=6(a,v){5"/"+R(v)+"/.l("+a+")"};6 1p(e,a,t){1d(a){F"n":5 K;F"2p":a="2n";1a;F"2o":a="2n+1"}7 1m=1o(e.1n);6 1k(i){7 i=(t==G)?1m.y-i:i-1;5 1m[i]==e};8(!Y(a))5 1k(a);a=a.1l("n");7 m=1K(a[0]);7 s=1K(a[1]);8((Y(m)||m==1)&&s==0)5 K;8(m==0&&!Y(s))5 1k(s);8(Y(s))s=0;7 c=1;H(e=t(e))c++;8(Y(m)||m==1)5(t==G)?(c<=s):(s>=c);5(c%m)==s}});x.15("1j-2m",6(){U=1i("L;/*@2l@8(@\\2k)U=K@2j@*/");8(!U){X=6(e,t,n){5 n?e.2i("*",t):e.X(t)};14=6(e,n){5!n||(n=="*")||(e.2h==n)};1h=1g.1I?6(e){5/1J/i.l(Q(e).1I)}:6(e){5 Q(e).1H.1f!="2g"};1e=6(e){5 e.2f||e.1G||1b(e)};6 1b(e){7 t="",n,i;9(i=0;(n=e.1F[i]);i++){1d(n.1c){F 11:F 1:t+=1b(n);1a;F 3:t+=n.2e;1a}}5 t}}});19=K;5 x}();',62,190,'|||||return|function|var|if|for||||||||pseudoClasses||||test|||this||AttributeSelector|||||||cssQuery|length|push|fr|id||selectors||case|nextElementSibling|while||tests|true|false|thisElement||replace|match|getDocument|regEscape||attributeSelectors|isMSIE|cache||getElementsByTagName|isNaN|slice|child||new|getAttribute|compareNamespace|addModule|previousElementSibling|compareTagName|parseSelector|loaded|break|_0|nodeType|switch|getTextContent|tagName|document|isXML|eval|css|_1|split|ch|parentNode|childElements|nthChild|disabled|firstElementChild|getText|RegExp|Quote|x22|PREFIX|lang|_2|arguments|else|all|links|version|se|childNodes|innerText|documentElement|contentType|xml|parseInt|indeterminate|checked|last|nth|lastElementChild|parse|_3|add|href|String|className|create|NS_IE|remove|toString|ST|select|Array|null|_4|mimeType|lastChild|firstChild|continue|modules|delete|join|caching|error|nodeValue|textContent|HTML|prefix|getElementsByTagNameNS|end|x5fwin32|cc_on|standard||odd|even|enabled|hash|location|target|not|only|empty|root|contains|level3|outerHTML|htmlFor|class|toLowerCase|Function|name|first|level2|prototype|item|scopeName|toUpperCase|ownerDocument|Document|XML|Boolean|URL|unknown|typeof|nextSibling|previousSibling|visited|link|valueOf|clearCache|catch|concat|constructor|callee|try'.split('|'),0,{}));
}