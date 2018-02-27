;(function() {
	"use strict";

	BX.namespace('BX.Main');

	/**
	 * @event Grid::ready
	 * @event Grid::columnMoved
	 * @event Grid::rowMoved
	 * @event Grid::pageSizeChanged
	 * @event Grid::optionsUpdated
	 * @event Grid::dataSorted
	 * @event Grid::thereSelectedRows
	 * @event Grid::allRowsSelected
	 * @event Grid::allRowsUnselected
	 * @event Grid::noSelectedRows
	 * @event Grid::updated
	 * @event Grid::headerPinned
	 * @event Grid::headerUnpinned
	 * @event Grid::beforeRequest
	 * @param containerId
	 * @param userOptions
	 * @param userOptionsActions
	 * @param userOptionsHandlerUrl
	 * @returns {Grid}
	 */
	BX.Main.grid = function(
		containerId,
		arParams,
		userOptions,
		userOptionsActions,
		userOptionsHandlerUrl,
		panelActions,
		panelTypes,
		editorTypes
	)
	{
		this.settings = null;
		this.containerId = '';
		this.container = null;
		this.wrapper = null;
		this.fadeContainer = null;
		this.scrollContainer = null;
		this.pagination = null;
		this.moreButton = null;
		this.table = null;
		this.rows = null;
		this.history = false;
		this.userOptions = null;
		this.checkAll = null;
		this.sortable = null;
		this.updater = null;
		this.data = null;
		this.fader = null;
		this.editor = null;
		this.isEditMode = null;
		this.pinHeader = null;
		this.pinPanel = null;
		this.arParams = null;
		this.resize = null;

		this.init(
			containerId,
			arParams,
			userOptions,
			userOptionsActions,
			userOptionsHandlerUrl,
			panelActions,
			panelTypes,
			editorTypes
		);
	};

	BX.Main.grid.prototype = {
		init: function(
			containerId,
			arParams,
			userOptions,
			userOptionsActions,
			userOptionsHandlerUrl,
			panelActions,
			panelTypes,
			editorTypes
		)
		{
			if (!BX.type.isNotEmptyString(containerId))
			{
				throw 'BX.Main.grid.init: parameter containerId is empty';
			}

			if (BX.type.isPlainObject(arParams))
			{
				this.arParams = arParams;
			}
			else
			{
				throw new Error('BX.Main.grid.init: arParams isn\'t object');
			}

			this.settings = new BX.Grid.Settings();
			this.containerId = containerId;
			this.userOptions = new BX.Grid.UserOptions(this, userOptions, userOptionsActions, userOptionsHandlerUrl);
			this.gridSettings = new BX.Grid.SettingsWindow(this);

			if (this.getParam('ALLOW_PIN_HEADER'))
			{
				this.pinHeader = new BX.Grid.PinHeader(this);
				this.bindOnCheckAll();
				BX.addCustomEvent(window, 'Grid::headerPinned', BX.proxy(this.bindOnCheckAll, this));
			}

			if (this.getParam('ALLOW_HORIZONTAL_SCROLL'))
			{
				this.fader = new BX.Grid.Fader(this);
			}

			this.pageSize = new BX.Grid.Pagesize(this);

			if (this.getParam('SHOW_ACTION_PANEL'))
			{
				this.actionPanel = new BX.Grid.ActionPanel(this, panelActions, panelTypes);
			}

			this.editor = new BX.Grid.InlineEditor(this, editorTypes);

			if (this.getParam('SHOW_ACTION_PANEL'))
			{
				this.pinPanel = new BX.Grid.PinPanel(this);
			}

			this.isEditMode = false;

			if (!BX.type.isDomNode(this.getContainer()))
			{
				throw 'BX.Main.grid.init: Failed to find container with id ' + this.getContainerId();
			}

			if (!BX.type.isDomNode(this.getTable()))
			{
				throw 'BX.Main.grid.init: Failed to find table';
			}

			this.bindOnRowEvents();

			if (this.getParam('ALLOW_COLUMNS_RESIZE'))
			{
				this.resize = new BX.Grid.Resize(this);
			}

			this.bindOnMoreButtonEvents();
			this.bindOnClickPaginationLinks();
			this.bindOnClickHeader();

			if (this.getParam('ALLOW_ROWS_SORT'))
			{
				this.initRowsDragAndDrop();
			}

			if (this.getParam('ALLOW_COLUMNS_SORT'))
			{
				this.initColsDragAndDrop();
			}

			this.getRows().initSelected();
			this.adjustEmptyTable(this.getRows().getSourceBodyChild());
			BX.onCustomEvent(this.getContainer(), 'Grid::ready', [this]);
			BX.addCustomEvent(window, 'Grid::unselectRow', BX.proxy(this._onUnselectRows, this));
			BX.addCustomEvent(window, 'Grid::unselectRows', BX.proxy(this._onUnselectRows, this));
			BX.addCustomEvent(window, 'Grid::allRowsUnselected', BX.proxy(this._onUnselectRows, this));
		},

		destroy: function()
		{
			BX.removeCustomEvent(window, 'Grid::unselectRow', BX.proxy(this._onUnselectRows, this));
			BX.removeCustomEvent(window, 'Grid::unselectRows', BX.proxy(this._onUnselectRows, this));
			BX.removeCustomEvent(window, 'Grid::allRowsUnselected', BX.proxy(this._onUnselectRows, this));
			BX.removeCustomEvent(window, 'Grid::headerPinned', BX.proxy(this.bindOnCheckAll, this));
			this.getPinHeader() && this.getPinHeader().destroy();
			this.getFader() && this.getFader().destroy();
			this.getResize() && this.getResize().destroy();
			this.getColsSortable() && this.getColsSortable().destroy();
			this.getRowsSortable() && this.getRowsSortable().destroy();
			this.getSettingsWindow() && this.getSettingsWindow().destroy();
		},

		enableActionsPanel: function()
		{
			var panel = this.getActionsPanel().getPanel();

			if (BX.type.isDomNode(panel))
			{
				BX.removeClass(panel, this.settings.get('classDisable'));
			}
		},

		disableActionsPanel: function()
		{
			var panel = this.getActionsPanel().getPanel();

			if (BX.type.isDomNode(panel))
			{
				BX.addClass(panel, this.settings.get('classDisable'));
			}
		},

		getSettingsWindow: function()
		{
			return this.gridSettings;
		},

		_onUnselectRows: function()
		{
			var panel = this.getActionsPanel();
			var checkbox;

			if (panel instanceof BX.Grid.ActionPanel)
			{
				checkbox = panel.getForAllCheckbox();

				if (BX.type.isDomNode(checkbox))
				{
					checkbox.checked = null;
					this.disableForAllCounter();
				}
			}
		},

		isIE: function()
		{
			if (!BX.type.isBoolean(this.ie))
			{
				this.ie = BX.hasClass(document.documentElement, 'bx-ie');
			}

			return this.ie;
		},

		getParam: function(paramName, defaultValue)
		{
			if(defaultValue === undefined)
			{
				defaultValue = null;
			}
			return (this.arParams.hasOwnProperty(paramName) ? this.arParams[paramName] : defaultValue);
		},

		getCounterTotal: function()
		{
			return BX.Grid.Utils.getByClass(this.getContainer(), this.settings.get('classCounterTotal'), true);
		},

		getActionKey: function()
		{
			return ('action_button_' + this.getId());
		},

		getPinHeader: function()
		{
			if (this.getParam('ALLOW_PIN_HEADER'))
			{
				this.pinHeader = this.pinHeader || new BX.Grid.PinHeader(this);
			}

			return this.pinHeader;
		},

		getResize: function()
		{
			if (!(this.resize instanceof BX.Grid.Resize) && this.getParam('ALLOW_COLUMNS_RESIZE'))
			{
				this.resize = new BX.Grid.Resize(this);
			}

			return this.resize;
		},

		confirmForAll: function(container)
		{
			var checkbox;
			var self = this;

			if (BX.type.isDomNode(container))
			{
				checkbox = BX.Grid.Utils.getByTag(container, 'input', true);
			}

			if (checkbox.checked)
			{
				this.getActionsPanel().confirmDialog(
					{CONFIRM: true, CONFIRM_MESSAGE: this.arParams.CONFIRM_FOR_ALL_MESSAGE},
					function() {
						if (BX.type.isDomNode(checkbox))
						{
							checkbox.checked = true;
						}

						self.selectAllCheckAllCheckboxes();
						self.getRows().selectAll();
						self.enableForAllCounter();
						self.updateCounterDisplayed();
						self.updateCounterSelected();
						self.enableActionsPanel();
						BX.onCustomEvent(window, 'Grid::allRowsSelected', []);
					},
					function() {
						if (BX.type.isDomNode(checkbox))
						{
							checkbox.checked = null;
							self.disableForAllCounter();
							self.updateCounterDisplayed();
							self.updateCounterSelected();
							self.disableActionsPanel();
						}
					}
				);
			}
			else
			{
				this.unselectAllCheckAllCheckboxes();
				this.getRows().unselectAll();
				this.disableForAllCounter();
				this.updateCounterDisplayed();
				this.updateCounterSelected();
				this.disableActionsPanel();
				BX.onCustomEvent(window, 'Grid::allRowsUnselected', []);
			}
		},

		editSelected: function()
		{
			this.getRows().editSelected();
		},

		editSelectedSave: function()
		{
			var data = { 'FIELDS': this.getRows().getEditSelectedValues() };
			data[this.getActionKey()] = 'edit';
			this.reloadTable('POST', data);
		},

		getForAllKey: function()
		{
			return 'action_all_rows_' + this.getId();
		},

		editSelectedCancel: function()
		{
			this.getRows().editSelectedCancel();
		},

		removeSelected: function()
		{
			var data = { 'ID': this.getRows().getSelectedIds() };
			var values = this.getActionsPanel().getValues();
			data[this.getActionKey()] = 'delete';
			data[this.getForAllKey()] = this.getForAllKey() in values ? values[this.getForAllKey()] : 'N';
			this.reloadTable('POST', data);
		},

		sendSelected: function()
		{
			var values = this.getActionsPanel().getValues();
			var selectedRows = this.getRows().getSelectedIds();
			var data = {
				rows: selectedRows,
				controls: values
			};

			this.reloadTable('POST', data);
		},

		getActionsPanel: function()
		{
			this.actionPanel = this.actionPanel || new BX.Grid.ActionPanel(this);
			return this.actionPanel;
		},

		getApplyButton: function()
		{
			return BX.Grid.Utils.getByClass(this.getContainer(), this.settings.get('classPanelButton'), true);
		},

		getEditor: function()
		{
			return this.editor;
		},

		reload: function(url)
		{
			this.reloadTable("GET", {}, null, url);
		},

		getPanels: function()
		{
			return BX.Grid.Utils.getByClass(this.getContainer(), this.settings.get('classPanels'), true);
		},

		getEmptyBlock: function()
		{
			return BX.Grid.Utils.getByClass(this.getContainer(), this.settings.get('classEmptyBlock'), true);
		},

		adjustEmptyTable: function(rows)
		{
			function adjustEmptyBlockPosition(event) {
				var target = event.currentTarget;
				BX.Grid.Utils.requestAnimationFrame(function() {
					BX.style(emptyBlock, 'transform', 'translate3d(' + BX.scrollLeft(target) + 'px, 0px, 0');
				});
			}

			if (!BX.hasClass(document.documentElement, 'bx-ie') &&
				BX.type.isArray(rows) && rows.length === 1 &&
				BX.hasClass(rows[0], this.settings.get('classEmptyRows')))
			{
				var gridRect = BX.pos(this.getContainer());
				var scrollBottom = BX.scrollTop(window) + BX.height(window);
				var diff = gridRect.bottom - scrollBottom;
				var panelsHeight = BX.height(this.getPanels());
				var emptyBlock = this.getEmptyBlock();
				var containerWidth = BX.width(this.getContainer());

				BX.width(emptyBlock, containerWidth);
				BX.style(emptyBlock, 'transform', 'translate3d(' + BX.scrollLeft(this.getScrollContainer()) + 'px, 0px, 0');

				BX.unbind(this.getScrollContainer(), 'scroll', adjustEmptyBlockPosition);
				BX.bind(this.getScrollContainer(), 'scroll', adjustEmptyBlockPosition);

				if (diff > 0)
				{
					BX.style(this.getTable(), 'min-height', (gridRect.height - diff - panelsHeight) + 'px');
				}
				else
				{
					BX.style(this.getTable(), 'min-height', (gridRect.height + Math.abs(diff) - panelsHeight) + 'px');
				}
			}
			else
			{
				BX.style(this.getTable(), 'min-height', '');
			}
		},

		reloadTable: function(method, data, callback, url)
		{
			var bodyRows;

			if(!BX.type.isNotEmptyString(method))
			{
				method = "GET";
			}

			if(!BX.type.isPlainObject(data))
			{
				data = {};
			}

			var self = this;
			this.tableFade();

			if(!BX.type.isString(url))
			{
				url = "";
			}

			this.getData().request(url, method, data, '', function() {
				self.getRows().reset();
				bodyRows = this.getBodyRows();
				self.getUpdater().updateHeadRows(this.getHeadRows());
				self.getUpdater().updateBodyRows(bodyRows);
				self.getUpdater().updateFootRows(this.getFootRows());
				self.getUpdater().updatePagination(this.getPagination());
				self.getUpdater().updateMoreButton(this.getMoreButton());
				self.getUpdater().updateCounterTotal(this.getCounterTotal());

				self.adjustEmptyTable(bodyRows);

				self.bindOnRowEvents();

				self.bindOnMoreButtonEvents();
				self.bindOnClickPaginationLinks();
				self.bindOnClickHeader();
				self.bindOnCheckAll();
				self.updateCounterDisplayed();
				self.updateCounterSelected();
				self.disableActionsPanel();
				self.disableForAllCounter();
				self.getActionsPanel().resetForAllCheckbox();

				if (self.getParam('ALLOW_COLUMNS_SORT'))
				{
					self.colsSortable.reinit();
				}

				if (self.getParam('ALLOW_ROWS_SORT'))
				{
					self.rowsSortable.reinit();
				}

				self.tableUnfade();

				BX.onCustomEvent(window, 'Grid::updated', []);

				if (BX.type.isFunction(callback))
				{
					callback();
				}
			});
		},

		getGroupEditButton: function()
		{
			return BX.Grid.Utils.getByClass(this.getContainer(), this.settings.get('classGroupEditButton'), true);
		},

		getGroupDeleteButton: function()
		{
			return BX.Grid.Utils.getByClass(this.getContainer(), this.settings.get('classGroupDeleteButton'), true);
		},

		enableGroupActions: function()
		{
			var editButton = this.getGroupEditButton();
			var deleteButton = this.getGroupDeleteButton();

			if (BX.type.isDomNode(editButton))
			{
				BX.removeClass(editButton, this.settings.get('classGroupActionsDisabled'));
			}

			if (BX.type.isDomNode(deleteButton))
			{
				BX.removeClass(deleteButton, this.settings.get('classGroupActionsDisabled'));
			}
		},

		disableGroupActions: function()
		{
			var editButton = this.getGroupEditButton();
			var deleteButton = this.getGroupDeleteButton();

			if (BX.type.isDomNode(editButton))
			{
				BX.addClass(editButton, this.settings.get('classGroupActionsDisabled'));
			}

			if (BX.type.isDomNode(deleteButton))
			{
				BX.addClass(deleteButton, this.settings.get('classGroupActionsDisabled'));
			}
		},

		closeActionsMenu: function()
		{
			var rows = this.getRows().getRows();
			for(var i = 0, l = rows.length; i < l; i++)
			{
				rows[i].closeActionsMenu();
			}
		},

		getPageSize: function()
		{
			return this.pageSize;
		},

		getFader: function()
		{
			return this.fader;
		},

		getData: function()
		{
			this.data = this.data || new BX.Grid.Data(this);
			return this.data;
		},

		getUpdater: function()
		{
			this.updater = this.updater || new BX.Grid.Updater(this);
			return this.updater;
		},

		isSortableHeader: function(item)
		{
			return (
				BX.hasClass(item, this.settings.get('classHeaderSortable'))
			);
		},

		bindOnClickHeader: function()
		{
			var self = this;
			var cell;

			BX.bind(this.getContainer(), 'click', function(event) {
				cell = BX.findParent(event.target, {tag: 'th'}, true, false);

				if (cell && self.isSortableHeader(cell))
				{
					self._clickOnSortableHeader(cell, event);
				}
			});
		},

		enableEditMode: function()
		{
			this.isEditMode = true;
		},

		disableEditMode: function()
		{
			this.isEditMode = false;
		},

		isEditMode: function()
		{
			return this.isEditMode;
		},

		_clickOnSortableHeader: function(header, event)
		{
			var self = this;
			event.preventDefault();

			if (!BX.hasClass(header, this.settings.get('classLoad')))
			{
				BX.addClass(header, this.settings.get('classLoad'));
				this.tableFade();

				this.getUserOptions().setSort(BX.data(header, 'sort-by'), BX.data(header, 'sort-order'), function() {
					self.getData().request(BX.data(header, 'sort-url'), null, null, 'sort', function() {
						self.rows = null;
						BX.removeClass(header, self.settings.get('classLoad'));
						self.getUpdater().updateHeadRows(this.getHeadRows());
						self.getUpdater().updateBodyRows(this.getBodyRows());
						self.getUpdater().updatePagination(this.getPagination());
						self.getUpdater().updateMoreButton(this.getMoreButton());

						self.bindOnRowEvents();

						self.bindOnMoreButtonEvents();
						self.bindOnClickPaginationLinks();
						self.bindOnClickHeader();
						self.bindOnCheckAll();
						self.updateCounterDisplayed();
						self.updateCounterSelected();
						self.disableActionsPanel();
						self.disableForAllCounter();
						self.getActionsPanel().resetForAllCheckbox();

						if (self.getParam('ALLOW_ROWS_SORT'))
						{
							self.rowsSortable.reinit();
						}

						if (self.getParam('ALLOW_COLUMNS_SORT'))
						{
							self.colsSortable.reinit();
						}

						BX.onCustomEvent(self.getContainer(), 'Grid::dataSorted', [self.parent]);
						BX.onCustomEvent(window, 'Grid::updated', []);
						self.tableUnfade();
					});
				});
			}
		},

		getObserver: function()
		{
			return BX.Grid.observer;
		},

		initRowsDragAndDrop: function()
		{
			this.rowsSortable = new BX.Grid.RowsSortable(this);
		},

		initColsDragAndDrop: function()
		{
			this.colsSortable = new BX.Grid.ColsSortable(this);
		},

		getRowsSortable: function()
		{
			return this.rowsSortable;
		},

		getColsSortable: function()
		{
			return this.colsSortable;
		},

		getUserOptionsHandlerUrl: function()
		{
			return this.userOptionsHandlerUrl || '';
		},

		getUserOptions: function()
		{
			return this.userOptions;
		},

		getCheckAllCheckboxes: function()
		{
			var checkAllNodes = BX.Grid.Utils.getByClass(this.getContainer(), this.settings.get('classCheckAllCheckboxes'));
			return checkAllNodes.map(function(current) {
				return new BX.Grid.Element(current);
			});
		},

		selectAllCheckAllCheckboxes: function()
		{
			this.getCheckAllCheckboxes().forEach(function(current) {
				current.getNode().checked = true;
			});
		},

		unselectAllCheckAllCheckboxes: function()
		{
			this.getCheckAllCheckboxes().forEach(function(current) {
				current.getNode().checked = false;
			});
		},

		bindOnCheckAll: function()
		{
			var self = this;

			this.getCheckAllCheckboxes().forEach(function(current) {
				current.getObserver().add(
					current.getNode(),
					'change',
					self._clickOnCheckAll,
					self
				);
			});
		},

		_clickOnCheckAll: function(event)
		{
			event.preventDefault();

			if (event.target.checked)
			{
				this.getRows().selectAll();
				this.selectAllCheckAllCheckboxes();
				this.enableActionsPanel();
				BX.onCustomEvent(window, 'Grid::allRowsSelected', []);
			}
			else
			{
				this.getRows().unselectAll();
				this.unselectAllCheckAllCheckboxes();
				this.disableActionsPanel();
				BX.onCustomEvent(window, 'Grid::allRowsUnselected', []);
			}

			this.updateCounterSelected();
		},

		bindOnClickPaginationLinks: function()
		{
			var self = this;

			this.getPagination().getLinks().forEach(function(current) {
				current.getObserver().add(
					current.getNode(),
					'click',
					self._clickOnPaginationLink,
					self
				);
			});
		},

		bindOnMoreButtonEvents: function()
		{
			var self = this;

			this.getMoreButton().getObserver().add(
				this.getMoreButton().getNode(),
				'click',
				self._clickOnMoreButton,
				self
			);
		},

		bindOnRowEvents: function()
		{
			var observer = this.getObserver();
			var showCheckboxes = this.getParam('SHOW_ROW_CHECKBOXES');

			this.getRows().getBodyChild().forEach(function(current) {
				showCheckboxes && observer.add(current.getNode(), 'click', this._onClickOnRow, this);
				current.getDefaultAction() && observer.add(current.getNode(), 'dblclick', this._onRowDblclick, this);
				current.getActionsButton() && observer.add(current.getActionsButton(), 'click', this._clickOnRowActionsButton, this);
			}, this);
		},

		_clickOnRowActionsButton: function(event)
		{
			var row = this.getRows().get(event.target);
			event.preventDefault();

			if (!row.actionsMenuIsShown())
			{
				row.showActionsMenu();
			}
			else
			{
				row.closeActionsMenu();
			}
		},

		_onRowDblclick: function(event)
		{
			event.preventDefault();
			var row = this.getRows().get(event.target);
			var defaultJs = '';

			if (!row.isEdit())
			{
				clearTimeout(this.clickTimer);
				this.clickPrevent = true;

				try {
					defaultJs = row.getDefaultAction();
					eval(defaultJs);
				} catch (err) {
					console.warn(err);
				}
			}
		},

		_onClickOnRow: function(event)
		{
			var clickDelay = 50;
			var selection = window.getSelection();

			if (event.target.nodeName === 'LABEL')
			{
				event.preventDefault();
			}

			if (event.shiftKey || selection.toString().length === 0)
			{
				selection.removeAllRanges();
				this.clickTimer = setTimeout(BX.delegate(function() {
					if (!this.clickPrevent) {
						clickActions.apply(this, [event]);
					}
					this.clickPrevent = false;
				}, this), clickDelay);
			}

			function clickActions(event)
			{
				var rows, row, containsNotSelected, min, max, contentContainer;
				var isPrevent = true;

				if (event.target.nodeName !== 'A' && event.target.nodeName !== 'INPUT')
				{
					row = this.getRows().get(event.target);

					contentContainer = row.getContentContainer(event.target);

					if (BX.type.isDomNode(contentContainer))
					{
						isPrevent = BX.data(contentContainer, 'prevent-default') === 'true';
					}

					if (isPrevent)
					{
						if (row.getCheckbox())
						{
							rows = [];

							this.currentIndex = row.getIndex();
							this.lastIndex = this.lastIndex || this.currentIndex;

							if (!event.shiftKey)
							{
								if (!row.isSelected())
								{
									row.select();
									BX.onCustomEvent(window, 'Grid::selectRow', [row, this]);
								}
								else
								{
									row.unselect();
									this.unselectAllCheckAllCheckboxes();
									BX.onCustomEvent(window, 'Grid::unselectRow', [row, this]);
								}
							}
							else
							{
								min = Math.min(this.currentIndex, this.lastIndex);
								max = Math.max(this.currentIndex, this.lastIndex);

								while (min <= max)
								{
									rows.push(this.getRows().getByIndex(min));
									min++;
								}

								containsNotSelected = rows.some(function(current) {
									return !current.isSelected();
								});

								if (containsNotSelected)
								{
									rows.forEach(function(current) {
										current.select();
									});
									BX.onCustomEvent(window, 'Grid::selectRows', [rows, this]);
								}
								else
								{
									rows.forEach(function(current) {
										current.unselect();
									});
									BX.onCustomEvent(window, 'Grid::unselectRows', [rows, this]);
								}
							}

							this.updateCounterSelected();
							this.lastIndex = this.currentIndex;
						}

						if (this.getRows().isSelected())
						{
							BX.onCustomEvent(window, 'Grid::thereSelectedRows', []);
							this.enableActionsPanel();
						}
						else
						{
							BX.onCustomEvent(window, 'Grid::noSelectedRows', []);
							this.disableActionsPanel();
						}

						if (this.getRows().isAllSelected())
						{
							this.selectAllCheckAllCheckboxes();
						}
					}
				}
			}
		},

		getPagination: function()
		{
			return new BX.Grid.Pagination(this);
		},

		getState: function()
		{
			return window.history.state;
		},

		tableFade: function()
		{
			BX.addClass(this.getTable(), this.settings.get('classTableFade'));
		},

		tableUnfade: function()
		{
			BX.removeClass(this.getTable(), this.settings.get('classTableFade'));
		},

		_clickOnPaginationLink: function(event)
		{
			event.preventDefault();

			var self = this;
			var link = this.getPagination().getLink(event.target);

			if (!link.isLoad())
			{
				link.load();
				this.tableFade();

				this.getData().request(link.getLink(), null, null, 'pagination', function() {
					self.rows = null;
					self.getUpdater().updateBodyRows(this.getBodyRows());
					self.getUpdater().updateHeadRows(this.getHeadRows());
					self.getUpdater().updateMoreButton(this.getMoreButton());
					self.getUpdater().updatePagination(this.getPagination());

					self.bindOnRowEvents();
					self.bindOnMoreButtonEvents();
					self.bindOnClickPaginationLinks();
					self.bindOnClickHeader();
					self.bindOnCheckAll();
					self.updateCounterDisplayed();
					self.updateCounterSelected();
					self.disableActionsPanel();
					self.disableForAllCounter();
					self.getActionsPanel().resetForAllCheckbox();

					if (self.getParam('ALLOW_ROWS_SORT'))
					{
						self.rowsSortable.reinit();
					}

					if (self.getParam('ALLOW_COLUMNS_SORT'))
					{
						self.colsSortable.reinit();
					}

					link.unload();
					self.tableUnfade();

					BX.onCustomEvent(window, 'Grid::updated', []);
				});
			}
		},

		_clickOnMoreButton: function(event)
		{
			event.preventDefault();

			var self = this;
			var moreButton = this.getMoreButton();

			moreButton.load();

			this.getData().request(moreButton.getLink(), null, null, 'more', function() {
				self.getUpdater().appendBodyRows(this.getBodyRows());
				self.getUpdater().updateMoreButton(this.getMoreButton());
				self.getUpdater().updatePagination(this.getPagination());

				self.getRows().reset();
				self.bindOnRowEvents();

				self.bindOnMoreButtonEvents();
				self.bindOnClickPaginationLinks();
				self.bindOnClickHeader();
				self.bindOnCheckAll();
				self.updateCounterDisplayed();
				self.updateCounterSelected();

				if (self.getParam('ALLOW_ROWS_SORT'))
				{
					self.rowsSortable.reinit();
				}

				if (self.getParam('ALLOW_COLUMNS_SORT'))
				{
					self.colsSortable.reinit();
				}

				self.unselectAllCheckAllCheckboxes();
			});
		},

		getAjaxId: function()
		{
			return BX.data(
				this.getContainer(),
				this.settings.get('ajaxIdDataProp')
			);
		},

		update: function(data, action)
		{
			var newRows, newHeadRows, newNavPanel, thisBody, thisHead, thisNavPanel;

			if (!BX.type.isNotEmptyString(data))
			{
				return;
			}

			thisBody = BX.Grid.Utils.getByTag(this.getTable(), 'tbody', true);
			thisHead = BX.Grid.Utils.getByTag(this.getTable(), 'thead', true);
			thisNavPanel = BX.Grid.Utils.getByClass(this.getContainer(), this.settings.get('classNavPanel'), true);

			data = BX.create('div', {html: data});
			newHeadRows = BX.Grid.Utils.getByClass(data, this.settings.get('classHeadRow'));
			newRows = BX.Grid.Utils.getByClass(data, this.settings.get('classDataRows'));
			newNavPanel = BX.Grid.Utils.getByClass(data, this.settings.get('classNavPanel'), true);

			if (action === this.settings.get('updateActionMore'))
			{
				this.getRows().addRows(newRows);
				this.unselectAllCheckAllCheckboxes();
			}

			if (action === this.settings.get('updateActionPagination'))
			{
				BX.cleanNode(thisBody);
				this.getRows().addRows(newRows);
				this.unselectAllCheckAllCheckboxes();
			}

			if (action === this.settings.get('updateActionSort'))
			{
				BX.cleanNode(thisHead);
				BX.cleanNode(thisBody);
				thisHead.appendChild(newHeadRows[0]);
				this.getRows().addRows(newRows);

			}

			thisNavPanel.innerHTML = newNavPanel.innerHTML;

			this.bindOnRowEvents();

			this.bindOnMoreButtonEvents();
			this.bindOnClickPaginationLinks();
			this.bindOnClickHeader();
			this.bindOnCheckAll();
			this.updateCounterDisplayed();
			this.updateCounterSelected();
			this.sortable.reinit();
		},

		getCounterDisplayed: function()
		{
			return BX.Grid.Utils.getByClass(this.getContainer(), this.settings.get('classCounterDisplayed'));
		},

		getCounterSelected: function()
		{
			return BX.Grid.Utils.getByClass(this.getContainer(), this.settings.get('classCounterSelected'));
		},

		updateCounterDisplayed: function()
		{
			var counterDisplayed = this.getCounterDisplayed();
			var rows;

			if (BX.type.isArray(counterDisplayed))
			{
				rows = this.getRows();
				counterDisplayed.forEach(function(current) {
					if (BX.type.isDomNode(current))
					{
						current.innerText = rows.getCountDisplayed();
					}
				}, this);
			}
		},

		updateCounterSelected: function()
		{
			var counterSelected = this.getCounterSelected();
			var rows;

			if (BX.type.isArray(counterSelected))
			{
				rows = this.getRows();
				counterSelected.forEach(function(current) {
					if (BX.type.isDomNode(current))
					{
						current.innerText = rows.getCountSelected();
					}
				}, this);
			}
		},

		getContainerId: function()
		{
			return this.containerId;
		},

		getId: function()
		{
			//ID is equals to container Id
			return this.containerId;
		},

		getContainer: function()
		{
			return BX(this.getContainerId());
		},

		getCounter: function()
		{
			if (!this.counter)
			{
				this.counter = BX.Grid.Utils.getByClass(this.getContainer(), this.settings.get('classCounter'));
			}

			return this.counter;
		},

		enableForAllCounter: function()
		{
			var counter = this.getCounter();

			if (BX.type.isArray(counter))
			{
				counter.forEach(function(current) {
					BX.addClass(current, this.settings.get('classForAllCounterEnabled'));
				}, this);
			}
		},

		disableForAllCounter: function()
		{
			var counter = this.getCounter();

			if (BX.type.isArray(counter))
			{
				counter.forEach(function(current) {
					BX.removeClass(current, this.settings.get('classForAllCounterEnabled'));
				}, this);
			}
		},

		getScrollContainer: function()
		{
			if (!this.scrollContainer)
			{
				this.scrollContainer = BX.Grid.Utils.getByClass(this.getContainer(), this.settings.get('classScrollContainer'), true);
			}

			return this.scrollContainer;
		},

		getWrapper: function()
		{
			if (!this.wrapper)
			{
				this.wrapper = BX.Grid.Utils.getByClass(this.getContainer(), this.settings.get('classWrapper'), true);
			}

			return this.wrapper;
		},

		getFadeContainer: function()
		{
			if (!this.fadeContainer)
			{
				this.fadeContainer = BX.Grid.Utils.getByClass(this.getContainer(), this.settings.get('classFadeContainer'), true);
			}

			return this.fadeContainer;
		},

		getTable: function()
		{
			return BX.Grid.Utils.getByClass(this.getContainer(), this.settings.get('classTable'), true);
		},

		getHeaders: function()
		{
			return BX.Grid.Utils.getBySelector(this.getWrapper(), '.main-grid-header[data-relative="' + this.getContainerId() + '"]');
		},

		getHead: function()
		{
			return BX.Grid.Utils.getByTag(this.getContainer(), 'thead', true);
		},

		getBody: function()
		{
			return BX.Grid.Utils.getByTag(this.getContainer(), 'tbody', true);
		},

		getFoot: function()
		{
			return BX.Grid.Utils.getByTag(this.getContainer(), 'tfoot', true);
		},

		getRows: function()
		{
			if (!(this.rows instanceof BX.Grid.Rows))
			{
				this.rows = new BX.Grid.Rows(this)
			}
			return this.rows;
		},

		getMoreButton: function()
		{
			var node = BX.Grid.Utils.getByClass(this.getContainer(), this.settings.get('classMoreButton'), true);
			return new BX.Grid.Element(node, this);
		}
	};
})();