;(function() {
	'use strict';

	BX.namespace('BX.Grid');

	/**
	 * BX.Grid.ActionPanel
	 * @param {BX.Main.grid} parent
	 * @param {Object} actions
	 * @param {Object} types
	 * @constructor
	 */
	BX.Grid.ActionPanel = function(parent, actions, types)
	{
		this.parent = null;
		this.rel = {};
		this.actions = null;
		this.types = null;
		this.lastActivated = [];
		this.init(parent, actions, types);
	};

	BX.Grid.ActionPanel.prototype = {
		init: function(parent, actions, types)
		{
			this.parent = parent;

			try {
				this.actions = eval(actions);
				this.types = eval(types);
			} catch(err) {}

			this.bindOnChange();
			BX.addCustomEvent(window, 'Grid::updated', function() {
				var cancelButton = BX('grid_cancel_button');

				if (cancelButton)
				{
					BX.fireEvent(BX.firstChild(cancelButton), 'click');
				}
			});
		},

		resetForAllCheckbox: function()
		{
			var checkbox = this.getForAllCheckbox();

			if (BX.type.isDomNode(checkbox))
			{
				checkbox.checked = null;
			}
		},

		getForAllCheckbox: function()
		{
			return BX.Grid.Utils.getByClass(this.getPanel(), this.parent.settings.get('classForAllCheckbox'), true);
		},

		getPanel: function()
		{
			return BX.Grid.Utils.getByClass(this.parent.getContainer(), this.parent.settings.get('classActionPanel'), true);
		},

		getApplyButton: function()
		{
			return BX.Grid.Utils.getByClass(this.getPanel(), this.parent.settings.get('classPanelApplyButton'), true);
		},

		bindOnChange: function()
		{
			var self = this;
			var panel = this.getPanel();

			BX.addCustomEvent(window, 'Dropdown::change', function(id, event, item, dataItem, value) {
				if (self.isPanelControl(BX(id)))
				{
					self._dropdownChange(id, event, item, dataItem, value);
				}
			});

			BX.addCustomEvent(window, 'Dropdown::load', function(id, event, item, dataItem, value) {
				if (self.isPanelControl(BX(id)))
				{
					self._dropdownChange(id, event, item, dataItem, value);
				}
			});

			BX.bind(panel, 'change', BX.delegate(this._checkboxChange, this));
			BX.bind(panel, 'click', BX.delegate(this._clickOnButton, this));
		},

		isPanelControl: function(node)
		{
			return BX.hasClass(node, this.parent.settings.get('classPanelControl'));
		},

		getTextInputs: function()
		{
			return BX.Grid.Utils.getBySelector(this.getPanel(), 'input[type="text"]');
		},

		getHiddenInputs: function()
		{
			return BX.Grid.Utils.getBySelector(this.getPanel(), 'input[type="hidden"]');
		},

		getDropdowns: function()
		{
			return BX.Grid.Utils.getByClass(this.getPanel(), this.parent.settings.get('classDropdown'));
		},

		getCheckboxes: function()
		{
			return BX.Grid.Utils.getByClass(this.getPanel(), this.parent.settings.get('classPanelCheckbox'));
		},

		isDropdown: function(node)
		{
			return BX.hasClass(node, this.parent.settings.get('classDropdown'));
		},

		isCheckbox: function(node)
		{
			return BX.hasClass(node, this.parent.settings.get('classPanelCheckbox'));
		},

		isTextInput: function(node)
		{
			return node.type === 'text';
		},

		isHiddenInput: function(node)
		{
			return node.type === 'hidden';
		},

		createDropdown: function(data, relative)
		{
			var container = this.createContainer(data.ID, relative);
			var dropdown = BX.create('div', {
				props: {
					className: 'main-dropdown main-grid-panel-control',
					id: data.ID + '_control'
				},
				attrs: {
					name: data.NAME,
					'data-name': data.NAME,
					'data-items': JSON.stringify(data.ITEMS),
					'data-value': data.ITEMS[0].VALUE
				},
				html: data.ITEMS[0].NAME
			});

			container.appendChild(dropdown);

			return container;
		},

		createCheckbox: function(data, relative)
		{
			var checkbox = this.createContainer(data.ID, relative);

			var inner = BX.create('span', {
				props: {
					className: 'main-grid-checkbox-container'
				}
			});

			var titleSpan = BX.create('span', {
				props: {
					className: 'main-grid-control-panel-content-title'
				}
			});

			var input = BX.create('input', {
				props: {
					type: 'checkbox',
					className: this.parent.settings.get('classPanelCheckbox') + ' main-grid-checkbox',
					id: data.ID + '_control'
				},
				attrs: {
					value: data.VALUE || '',
					title: data.TITLE || '',
					name: data.NAME || '',
					'data-onchange': JSON.stringify(data.ONCHANGE)
				}
			});

			input.checked = data.CHECKED || null;

			checkbox.appendChild(inner);
			checkbox.appendChild(titleSpan);

			inner.appendChild(input);

			inner.appendChild(BX.create('label', {
				props: {
					className: 'main-grid-checkbox'
				},
				attrs: {
					for: data.ID + '_control',
					title: data.TITLE
				}
			}));

			titleSpan.appendChild(BX.create('label', {
				attrs: {
					for: data.ID + '_control',
					title: data.TITLE
				},
				html: data.LABEL
			}));

			return checkbox;
		},

		createText: function(data, relative)
		{
			var container = this.createContainer(data.ID, relative);
			var title = BX.type.isNotEmptyString(data["TITLE"]) ? data["TITLE"] : "";
			if(title !== "")
			{
				container.appendChild(
					BX.create(
						'label',
						{
							attrs: { title: title, for: data.ID + '_control' },
							text: title
						}
					)
				);
			}
			container.appendChild(
				BX.create(
					'input',
					{
						props:
							{
								className: 'main-grid-control-panel-input-text main-grid-panel-control',
								id: data.ID + '_control'
							},
						attrs:
							{
								name: data.NAME,
								title: title,
								placeholder: data.PLACEHOLDER || '',
								value: data.VALUE || '',
								'data-onchange': JSON.stringify(data.ONCHANGE || [])
							}
					}
				)
			);

			return container;
		},

		createHidden: function(data, relative)
		{
			var container = this.createContainer(data.ID, relative);
			container.appendChild(
				BX.create(
					'input',
					{
						props:
							{
								id: data.ID + '_control',
								type: 'hidden'
							},
						attrs:
							{
								name: data.NAME,
								value: data.VALUE || ''
							}
					}
				)
			);

			return container;
		},
		createButton: function(data, relative)
		{
			var container = this.createContainer(data.ID, relative);
			var button = BX.create('button', {
				props: {
					className: 'main-grid-buttons' + (data.CLASS ? ' ' + data.CLASS : ''),
					id: data.id + '_control'
				},
				attrs: {
					name: data.NAME || '',
					'data-onchange': JSON.stringify(data.ONCHANGE || [])
				},
				html: data.TEXT
			});

			container.appendChild(button);

			return container;
		},

		createLink: function(data, relative)
		{
			var container = this.createContainer(data.ID, relative);
			var link = BX.create('a', {
				props: {
					className: 'main-grid-link' + (data.CLASS ? ' ' + data.CLASS : ''),
					id: data.ID + '_control'
				},
				attrs: {
					href: data.HREF || '',
					'data-onchange': JSON.stringify(data.ONCHANGE || [])
				},
				html: data.TEXT
			});

			container.appendChild(link);

			return container;
		},

		createCustom: function(data, relative)
		{

		},

		createContainer: function(id, relative)
		{
			id = id.replace('_control', '');
			relative = relative.replace('_control', '');

			return BX.create('span', {
				props: {
					className: this.parent.settings.get('classPanelControlContainer'),
					id: id
				},
				attrs: {
					'data-relative': relative
				}
			});
		},

		removeItemsRelativeCurrent: function(node)
		{
			var element = node;
			var relative = node.id;
			var result = [];
			var dataRelative;

			while (element) {
				dataRelative = BX.data(element, 'relative');

				if (dataRelative === relative || dataRelative === node.id)
				{
					relative = element.id;
					result.push(element);
				}

				element = element.nextElementSibling;
			}

			result.forEach(function(current) {
				BX.remove(current);
			});
		},


		validateData: function(data)
		{
			return (
				('ONCHANGE' in data) &&
				BX.type.isArray(data.ONCHANGE)
			);
		},

		activateControl: function(id)
		{
			var element = BX(id);

			if (BX.type.isDomNode(element))
			{
				BX.removeClass(element, this.parent.settings.get('classDisable'));
				element.disabled = null;
			}
		},

		deactivateControl: function(id)
		{
			var element = BX(id);

			if (BX.type.isDomNode(element))
			{
				BX.addClass(element, this.parent.settings.get('classDisable'));
				element.disabled = true;
			}
		},

		showControl: function(id)
		{
			var control = BX(id);

			if (BX.type.isDomNode(control))
			{
				BX.show(control);
			}
		},

		hideControl: function(id)
		{
			var control = BX(id);

			if (BX.type.isDomNode(control))
			{
				BX.hide(control);
			}
		},


		validateActionObject: function(action)
		{
			return (
				BX.type.isPlainObject(action) &&
				('ACTION' in action) &&
				BX.type.isNotEmptyString(action.ACTION) &&
				('DATA' in action) &&
				BX.type.isArray(action.DATA)
			);
		},

		validateControlObject: function(controlObject)
		{
			return (
				BX.type.isPlainObject(controlObject) &&
				('TYPE' in controlObject) &&
				('ID' in controlObject)
			);
		},

		createControl: function(controlObject, relativeId)
		{
			var newElement;

			switch (controlObject.TYPE) {
				case this.types.DROPDOWN : {
					newElement = this.createDropdown(controlObject, relativeId);
					break;
				}

				case this.types.CHECKBOX : {
					newElement = this.createCheckbox(controlObject, relativeId);
					break;
				}

				case this.types.TEXT : {
					newElement = this.createText(controlObject, relativeId);
					break;
				}

				case this.types.HIDDEN : {
					newElement = this.createHidden(controlObject, relativeId);
					break;
				}

				case this.types.BUTTON : {
					newElement = this.createButton(controlObject, relativeId);
					break;
				}

				case this.types.LINK : {
					newElement = this.createLink(controlObject, relativeId);
					break;
				}

				case this.types.CUSTOM : {
					newElement = this.createCustom(controlObject, relativeId);
					break;
				}

				default : {
					break;
				}
			}

			return newElement;
		},

		onChangeHandler: function(container, actions, isPseudo)
		{
			var newElement, callback;
			var self = this;

			if (BX.type.isDomNode(container) && BX.type.isArray(actions))
			{
				actions.forEach(function(action) {
					if (self.validateActionObject(action))
					{
						if (action.ACTION === self.actions.CREATE)
						{
							self.removeItemsRelativeCurrent(container);
							action.DATA.reverse();

							action.DATA.forEach(function(controlObject) {
								if (self.validateControlObject(controlObject))
								{
									newElement = self.createControl(controlObject, BX.data(container, 'relative') || container.id);

									if (BX.type.isDomNode(newElement))
									{
										BX.insertAfter(newElement, container);

										if (('ONCHANGE' in controlObject) &&
											controlObject.TYPE === self.types.CHECKBOX &&
											('CHECKED' in controlObject) &&
											controlObject.CHECKED)
										{
											self.onChangeHandler(newElement, controlObject.ONCHANGE);
										}

										if (controlObject.TYPE === self.types.DROPDOWN &&
											BX.type.isArray(controlObject.ITEMS) &&
											controlObject.ITEMS.length &&
											('ONCHANGE' in controlObject.ITEMS[0]) &&
											BX.type.isArray(controlObject.ITEMS[0].ONCHANGE))
										{
											self.onChangeHandler(newElement, controlObject.ITEMS[0].ONCHANGE);
										}
									}
								}
							});
						}

						if (action.ACTION === self.actions.ACTIVATE)
						{
							self.removeItemsRelativeCurrent(container);

							if (BX.type.isArray(action.DATA))
							{
								action.DATA.forEach(function(currentId) {
									self.lastActivated.push(currentId.ID);
									self.activateControl(currentId.ID);
								});
							}
						}

						if (action.ACTION === self.actions.SHOW)
						{
							if (BX.type.isArray(action.DATA))
							{
								action.DATA.forEach(function(showCurrent) {
									self.showControl(showCurrent.ID);
								});
							}
						}

						if (action.ACTION === self.actions.HIDE)
						{
							if (BX.type.isArray(action.DATA))
							{
								action.DATA.forEach(function(hideCurrent) {
									self.hideControl(hideCurrent.ID);
								});
							}
						}

						if (action.ACTION === self.actions.HIDE_ALL_EXPECT)
						{
							if (BX.type.isArray(action.DATA))
							{
								(self.getControls() || []).forEach(function(current) {
									if (!action.DATA.some(function(el) { return el.ID === current.id}))
									{
										self.hideControl(current.id);
									}
								});
							}
						}

						if (action.ACTION === self.actions.SHOW_ALL)
						{
							(self.getControls() || []).forEach(function(current) {
								self.showControl(current.id);
							});
						}

						if (action.ACTION === self.actions.REMOVE)
						{
							if (BX.type.isArray(action.DATA))
							{
								action.DATA.forEach(function(removeCurrent) {
									BX.remove(BX(removeCurrent.ID));
								});
							}
						}

						if (action.ACTION === self.actions.CALLBACK)
						{
							this.confirmDialog(action, BX.delegate(function() {
								if (BX.type.isArray(action.DATA))
								{
									action.DATA.forEach(
										function(currentCallback)
										{
											if (currentCallback.JS.indexOf('Grid.') !== -1)
											{
												callback = currentCallback.JS.replace('Grid', 'self.parent');
												callback = callback.replace('()', '');
												callback += '.apply(self.parent, [container])';
												try
												{
													eval(callback); // jshint ignore:line
												}
												catch(err)
												{
													throw new Error(err);
												}
											}
											else if(BX.type.isNotEmptyString(currentCallback.JS))
											{
												try
												{
													eval(currentCallback.JS);
												}
												catch(err)
												{
													console.log(err);
												}
											}
										}
									);
								}
							}, this));
						}
					}
				}, this);

			}
			else
			{
				if (!isPseudo)
				{
					this.removeItemsRelativeCurrent(container);
				}

				self.lastActivated.forEach(function(current) {
					self.deactivateControl(current);
				});

				self.lastActivated = [];
			}
		},

		confirmDialog: function(action, then, cancel)
		{
			var dialog, popupContainer, applyButton, cancelButton;
			var self = this;

			if ('CONFIRM' in action && action.CONFIRM)
			{
				action.CONFIRM_MESSAGE = action.CONFIRM_MESSAGE || this.parent.arParams.CONFIRM_MESSAGE;
				action.CONFIRM_APPLY_BUTTON = action.CONFIRM_APPLY_BUTTON || this.parent.arParams.CONFIRM_APPLY;
				action.CONFIRM_CANCEL_BUTTON = action.CONFIRM_CANCEL_BUTTON || this.parent.arParams.CONFIRM_CANCEL;

				dialog = new BX.PopupWindow(
					this.parent.getContainerId() + '-confirm-dialog',
					null,
					{
						content: '<div class="main-grid-confirm-content">'+action.CONFIRM_MESSAGE+'</div>',
						titleBar: 'CONFIRM_TITLE' in action ? action.CONFIRM_TITLE : '',
						autoHide: false,
						zIndex: 9999,
						overlay: 0.4,
						offsetTop: -100,
						closeIcon : true,
						closeByEsc : true,
						events: {
							onClose: function()
							{
								BX.unbind(window, 'keydown', hotKey);
							}
						},
						buttons: [
							new BX.PopupWindowButton({
								text: action.CONFIRM_APPLY_BUTTON,
								id: this.parent.getContainerId() + '-confirm-dialog-apply-button',
								events: {
									click: function()
									{
										BX.type.isFunction(then) ? then() : null;
										this.popupWindow.close();
										this.popupWindow.destroy();
										BX.onCustomEvent(window, 'Grid::confirmDialogApply', [this]);
										BX.unbind(window, 'keydown', hotKey);
									}
								}
							}),
							new BX.PopupWindowButtonLink({
								text: action.CONFIRM_CANCEL_BUTTON,
								id: this.parent.getContainerId() + '-confirm-dialog-cancel-button',
								events: {
									click: function()
									{
										BX.type.isFunction(cancel) ? cancel() : null;
										this.popupWindow.close();
										this.popupWindow.destroy();
										BX.onCustomEvent(window, 'Grid::confirmDialogCancel', [this]);
										BX.unbind(window, 'keydown', hotKey);
									}
								}
							})
						]
					}
				);

				if (!dialog.isShown())
				{
					dialog.show();
					popupContainer = dialog.popupContainer;
					BX.removeClass(popupContainer, this.parent.settings.get('classCloseAnimation'));
					BX.addClass(popupContainer, this.parent.settings.get('classShowAnimation'));
					applyButton = BX(this.parent.getContainerId() + '-confirm-dialog-apply-button');
					cancelButton = BX(this.parent.getContainerId() + '-confirm-dialog-cancel-button');

					BX.bind(window, 'keydown', hotKey);
				}
			}
			else
			{
				BX.type.isFunction(then) ? then() : null;
			}

			function hotKey(event)
			{
				if (event.code === 'Enter')
				{
					event.preventDefault();
					event.stopPropagation();
					BX.fireEvent(applyButton, 'click');
				}

				if (event.code === 'Escape')
				{
					event.preventDefault();
					event.stopPropagation();
					BX.fireEvent(cancelButton, 'click');
				}
			}
		},

		_dropdownChange: function(id, event, item, dataItem)
		{
			var dropdown = BX(id);
			var container = dropdown.parentNode;
			var onChange = dataItem && ('ONCHANGE' in dataItem) ? dataItem.ONCHANGE : null;
			var isPseudo = ('PSEUDO' in dataItem && dataItem.PSEUDO != false);

			this.onChangeHandler(container, onChange, isPseudo);
		},

		_checkboxChange: function(event)
		{
			var onChange;

			try {
				onChange = eval(BX.data(event.target, 'onchange'));
			} catch(err) {
				onChange = null;
			}

			this.onChangeHandler(
				BX.findParent(event.target, {
					className: this.parent.settings.get('classPanelContainer')
				}, true, false),
				event.target.checked || event.target.id.indexOf('actallrows_') !== -1 ? onChange : null
			);
		},

		_clickOnButton: function(event)
		{
			var onChange;

			if (this.isButton(event.target))
			{
				event.preventDefault();

				try {
					onChange = eval(BX.data(event.target, 'onchange'));
				} catch(err) {
					onChange = null;
				}

				this.onChangeHandler(
					BX.findParent(event.target, {
						className: this.parent.settings.get('classPanelContainer')
					}, true, false),
					onChange
				);
			}
		},

		isButton: function(node)
		{
			return BX.hasClass(node, this.parent.settings.get('classPanelButton'));
		},

		getSelectedIds: function()
		{
			var rows = this.parent.getRows().getSelected();

			return rows.map(function(current) {
				return current.getId();
			});
		},

		getRequestData: function()
		{
			return {
				panel: this.getValues(),
				selectedRows: this.getSelectedIds()
			};
		},

		getControls: function()
		{
			return BX.findChild(this.getPanel(), {
				className: this.parent.settings.get('classPanelControlContainer')
			}, true, true);
		},

		getValues: function()
		{
			var data = {};
			var self = this;
			var controls = [].concat(
				this.getDropdowns(),
				this.getTextInputs(),
				this.getHiddenInputs(),
				this.getCheckboxes()
			);

			(controls || []).forEach(function(current) {
				if (BX.type.isDomNode(current))
				{
					if (self.isDropdown(current))
					{
						data[BX.data(current, 'name')] = BX.data(current, 'value');
					}

					if (self.isCheckbox(current) && current.checked)
					{
						data[current.getAttribute('name')] = current.value;
					}

					if (self.isTextInput(current) || self.isHiddenInput(current))
					{
						data[current.getAttribute('name')] = current.value;
					}
				}
			});

			return data;
		}

	};
})();