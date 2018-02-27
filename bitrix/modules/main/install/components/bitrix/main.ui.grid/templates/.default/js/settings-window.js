;(function() {
	'use strict';

	BX.namespace('BX.Grid');

	BX.Grid.SettingsWindow = function(parent)
	{
		this.parent = null;
		this.settingsButton = null;
		this.applyBottom = null;
		this.items = null;
		this.popup = null;
		this.sourceContent = null;
		this.lastColumns = null;
		this.init(parent);
	};

	BX.Grid.SettingsWindow.prototype = {
		init: function(parent)
		{
			this.parent = parent;
			BX.bind(this.parent.getContainer(), 'click', BX.proxy(this._onContainerClick, this));
		},

		destroy: function()
		{
			BX.unbind(this.parent.getContainer(), 'click', BX.proxy(this._onContainerClick, this));
			this.getPopup().close();
		},

		_onContainerClick: function(event)
		{
			if (BX.hasClass(event.target, this.parent.settings.get('classSettingsButton')))
			{
				this._onSettingsButtonClick(event);
			}
		},

		_onSettingsButtonClick: function(event)
		{
			this.getPopup().show();
		},

		getSourceContent: function()
		{
			if (!this.sourceContent)
			{
				this.sourceContent = BX.Grid.Utils.getByClass(this.parent.getContainer(), this.parent.settings.get('classSettingsWindow'), true);
			}

			return this.sourceContent;
		},

		getPopupItems: function()
		{
			var popupContainer;

			if (!this.items)
			{
				popupContainer = this.getPopup().contentContainer;
				this.items = BX.Grid.Utils.getByClass(popupContainer, this.parent.settings.get('classSettingsWindowColumn'));
			}

			return this.items;
		},

		getColumns: function()
		{
			var items = this.getPopupItems();
			var columns = [];
			var checkbox;

			items.forEach(function(current) {
				checkbox = BX.Grid.Utils.getByTag(current, 'input', true);
				if (checkbox && checkbox.checked)
				{
					columns.push(BX.data(current, 'name'));
				}
			});

			return columns;
		},

		restoreColumns: function()
		{
			var columns = this.parent.getParam('DEFAULT_COLUMNS');
			this.getPopupItems().forEach(function(current) {
				var name = BX.data(current, 'name');
				var checkbox = BX.Grid.Utils.getByClass(current, this.parent.settings.get('classSettingsWindowColumnCheckbox'), true);
				var input = BX.Grid.Utils.getByClass(current, this.parent.settings.get('classSettingsWindowColumnEditInput'), true);
				var label = BX.Grid.Utils.getByClass(current, this.parent.settings.get('classSettingsWindowColumnLabel'), true);
				var defaultColumn = columns[name];

				checkbox.checked = defaultColumn.default ? true : null;
				input.value = BX.util.htmlspecialchars(defaultColumn.name);
				BX.html(label, BX.util.htmlspecialchars(defaultColumn.name));
			}, this);
		},

		restoreLastColumns: function()
		{
			this.getPopupItems().forEach(function(current) {
				if (this.lastColumns.indexOf(BX.data(current, 'name')) === -1) {
					var checkbox = BX.Grid.Utils.getBySelector(current, 'input[type="checkbox"]', true);

					if (checkbox)
					{
						checkbox.checked = null;
					}
				}
			}, this);
		},

		saveColumns: function(columns, callback)
		{
			this.parent.tableFade();
			this.parent.getUserOptions().setColumns(columns, BX.delegate(function() {
				this.parent.getUserOptions().setColumnsNames(this.getColumnNames(), BX.delegate(function() {
					this.parent.reloadTable(null, null, callback);
				}, this));
			}, this));
		},

		resetSettings: function(button)
		{
			var columns, name;

			this.parent.getActionsPanel().confirmDialog(
				{CONFIRM: true, CONFIRM_MESSAGE: this.parent.arParams.CONFIRM_RESET_MESSAGE},
				BX.delegate(function() {
					this.parent.tableFade();

					BX.addClass(button.buttonNode, 'webform-small-button-wait');
					BX.removeClass(button.buttonNode, 'popup-window-button');
					this.parent.getUserOptions().reset(BX.delegate(function() {
						this.parent.reloadTable(null, null, BX.delegate(function() {
							this.restoreColumns();
							this.lastColumns = this.getColumns();
							BX.removeClass(button.buttonNode, 'webform-small-button-wait');
							BX.addClass(button.buttonNode, 'popup-window-button');
							button.popupWindow.close();
						}, this));
					}, this));
				}, this)
			);
		},

		_onColumnClick: function(event)
		{
			var column = event.currentTarget;

			if (BX.hasClass(event.target, this.parent.settings.get('classSettingsWindowColumnEditButton')))
			{
				if (!BX.hasClass(column, this.parent.settings.get('classSettingsWindowColumnEditState')))
				{
					event.preventDefault();
					event.stopPropagation();

					BX.addClass(column, this.parent.settings.get('classSettingsWindowColumnEditState'));
					var input = BX.Grid.Utils.getByClass(column, this.parent.settings.get('classSettingsWindowColumnEditInput'), true);

					if (input) {
						input.focus();
						input.value = BX.util.htmlspecialcharsback(input.value);
					}
				}
				else
				{
					BX.removeClass(column, this.parent.settings.get('classSettingsWindowColumnEditState'));
					var input = BX.Grid.Utils.getByClass(column, this.parent.settings.get('classSettingsWindowColumnEditInput'), true);
					var label = BX.Grid.Utils.getByClass(column, this.parent.settings.get('classSettingsWindowColumnLabel'), true);

					if (label)
					{
						BX.html(label, BX.util.htmlspecialchars(input.value));
					}
				}
			}
		},

		_onColumnKeydown: function(event)
		{
			if (event.code === 'Enter')
			{
				var column = event.currentTarget;
				BX.removeClass(column, this.parent.settings.get('classSettingsWindowColumnEditState'));
				var input = BX.Grid.Utils.getByClass(column, this.parent.settings.get('classSettingsWindowColumnEditInput'), true);
				var label = BX.Grid.Utils.getByClass(column, this.parent.settings.get('classSettingsWindowColumnLabel'), true);

				if (label)
				{
					BX.html(label, BX.util.htmlspecialchars(input.value));
				}
			}
		},

		getColumnNames: function()
		{
			var items = this.getPopupItems();
			var columns = {};

			items.forEach(function(current) {
				var name = BX.data(current, 'name');
				var input = BX.Grid.Utils.getByClass(current, this.parent.settings.get('classSettingsWindowColumnEditInput'), true);
				columns[name] = BX.util.htmlspecialchars(BX.util.htmlspecialcharsback(input.value));
			}, this);

			return columns;
		},

		getPopup: function()
		{
			var self = this;
			if (!this.popup)
			{
				var tmpDiv = BX.create('div');
				tmpDiv.innerHTML = '<span>' + this.parent.getParam('SETTINGS_TITLE') + ' &laquo;'+BX('pagetitle').innerText+'&raquo;</span>';
				var titleBar = tmpDiv.firstChild;

				this.popup = new BX.PopupWindow(
					this.parent.getContainerId() + '-grid-settings-window',
					null,
					{
						titleBar: titleBar.innerText,
						autoHide: false,
						overlay: 0.6,
						width: 800,
						closeIcon: true,
						closeByEsc: true,
						contentNoPaddings: true,
						events: {
							onPopupClose: BX.delegate(function() {
								this.restoreLastColumns();
							}, this)
						},
						buttons: [
							new BX.PopupWindowButtonLink({
								text: this.parent.getParam('RESET_DEFAULT'),
								id: this.parent.getContainerId() + '-grid-settings-reset-button',
								className: 'main-grid-settings-window-actions-item-reset',
								events: {
									click: function()
									{
										self.resetSettings(this.popupWindow.buttons[1]);
									}
								}
							}),
							new BX.PopupWindowButton({
								text: this.parent.getParam('APPLY_SETTINGS'),
								id: this.parent.getContainerId() + '-grid-settings-apply-button',
								className: 'webform-small-button-blue webform-small-button',
								events: {
									click: function()
									{
										BX.addClass(this.buttonNode, 'webform-small-button-wait');
										BX.removeClass(this.buttonNode, 'popup-window-button');
										self.lastColumns = self.getColumns();

										self.saveColumns(self.lastColumns, BX.delegate(function() {
											this.popupWindow.close();
											BX.removeClass(this.buttonNode, 'webform-small-button-wait');
											BX.addClass(this.buttonNode, 'popup-window-button');
										}, this));
									}
								}
							}),
							new BX.PopupWindowButtonLink({
								text: this.parent.getParam('CANCEL_SETTINGS'),
								id: this.parent.getContainerId() + '-grid-settings-cancel-button',
								events: {
									click: function()
									{
										this.popupWindow.close();
										self.restoreLastColumns();
									}
								}
							})
						]
					}
				);

				this.popup.setContent(this.getSourceContent());
				this.lastColumns = this.getColumns();
				this.getPopupItems().forEach(function(current) {
					BX.bind(current, 'click', BX.delegate(this._onColumnClick, this));
					BX.bind(current, 'keydown', BX.delegate(this._onColumnKeydown, this));
				}, this);
			}

			return this.popup;
		}
	};

})();