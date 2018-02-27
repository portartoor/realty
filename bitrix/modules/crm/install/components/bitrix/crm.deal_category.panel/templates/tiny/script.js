if(typeof(BX.CrmDealCategoryTinyPanel) === "undefined")
{
	BX.CrmDealCategoryTinyPanel = function()
	{
		this._id = "";
		this._settings = {};
		this._items = null;
		this._container = null;
		this._selectorButton = null;
		this._isCustomized = false;

		this._enableCreation = false;
		this._createUrl = "";
		this._createLockScript = "";

		this._nodes = null;
		this._button = null;
		this._menuId = "";
		this._menu = null;
	};
	BX.CrmDealCategoryTinyPanel.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : {};

			this._items = this.getSetting("items", []);

			var containerId = this.getSetting("containerId", "");
			this._container = BX.type.isNotEmptyString(containerId) ? BX(containerId) : null;
			if(!BX.type.isElementNode(this._container))
			{
				throw "BX.CrmDealCategoryTinyPanel: Container is not found.";
			}

			var selectorButtonId = this.getSetting("selectorButtonId", "");
			this._selectorButton = BX.type.isNotEmptyString(selectorButtonId) ? BX(selectorButtonId) : null;
			if(this._selectorButton)
			{
				BX.bind(this._selectorButton, "click", BX.delegate(this.onSelectorClick, this));
			}

			this._isCustomized = this.getSetting("isCustomized", false);
			this._enableCreation = this.getSetting("enableCreation", false);
			this._createUrl = this.getSetting("createUrl", "");
			this._createLockScript = this.getSetting("createLockScript", "");

			this._menuId = this._id;
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
			var m = BX.CrmDealCategoryTinyPanel.messages;
			return m.hasOwnProperty(name) ? m[name] : name;
		},
		createNewItem: function()
		{
			if(this._enableCreation && this._createUrl !== "")
			{
				window.location.href = this._createUrl;
			}
			else if(this._createLockScript !== "")
			{
				eval(this._createLockScript);
			}
		},
		onSelectorClick: function(e)
		{
			if(this._isCustomized)
			{
				this.openMenu();
			}
			else
			{
				this.createNewItem();
			}
		},
		onCreateButtonClick: function(e)
		{
			this.createNewItem();
		},
		openMenu: function()
		{
			this.closeMenu();

			var menuItems = [];
			for (var i = 0, l = this._items.length; i < l; i++)
			{
				var item = this._items[i];
				menuItems.push({ text: item["NAME"], href: item["URL"] });
			}

			if(this._enableCreation)
			{
				menuItems.push({ delimiter: true });
				menuItems.push({ text: this.getMessage("create"), onclick: BX.delegate(this.onCreateButtonClick, this) });
			}

			this._menu = BX.PopupMenu.create(
				this._menuId,
				this._selectorButton,
				menuItems,
				{ autoHide: true, closeByEsc: true }
			);

			this._menu.popupWindow.show();
		},
		closeMenu: function()
		{
			if(this._menu)
			{
				BX.PopupMenu.destroy(this._menuId);
				this._menu = null;
			}
		}
	};

	if(typeof(BX.CrmDealCategoryTinyPanel.messages) === "undefined")
	{
		BX.CrmDealCategoryTinyPanel.messages = {};
	}

	BX.CrmDealCategoryTinyPanel.create = function(id, settings)
	{
		var self = new BX.CrmDealCategoryTinyPanel();
		self.initialize(id, settings);
		return self;
	};
}
