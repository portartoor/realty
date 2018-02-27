;(function() {
	'use strict';

	BX.namespace('BX.Grid');

	/**
	 * BX.Grid.ColsSortable
	 * @param {BX.Main.grid} parent
	 * @constructor
	 */
	BX.Grid.ColsSortable = function(parent)
	{
		this.parent = null;
		this.dragItem = null;
		this.targetItem = null;
		this.rowsList = null;
		this.colsList = null;
		this.dragRect = null;
		this.offset = null;
		this.startDragOffset = null;
		this.dragColumn = null;
		this.targetColumn = null;
		this.isDrag = null;
		this.init(parent);
	};

	BX.Grid.ColsSortable.prototype = {
		init: function(parent)
		{
			var fixedTable, rows;
			var self = this;

			this.parent = parent;
			this.colsList = this.getColsList();
			this.rowsList = this.parent.getRows().getSourceRows();

			if (this.isPinned && this.parent.getParam('ALLOW_PIN_HEADER'))
			{
				fixedTable = this.parent.getPinHeader().getFixedTable();
				rows = BX.Grid.Utils.getByTag(fixedTable, 'tr');

				(rows || []).forEach(function(current) {
					self.rowsList.push(current);
				});
			}

			this.registerObjects();

			if (!this.inited)
			{
				this.inited = true;
				BX.addCustomEvent('Grid::headerPinned', BX.proxy(this._onPin, this));
				BX.addCustomEvent('Grid::headerUnpinned', BX.proxy(this._onUnpin, this));
			}
		},

		destroy: function()
		{
			BX.removeCustomEvent('Grid::headerPinned', BX.proxy(this._onPin, this));
			BX.removeCustomEvent('Grid::headerUnpinned', BX.proxy(this._onUnpin, this));
			this.unregisterObjects();
		},

		_onPin: function()
		{
			this.isPinned = true;
			this.reinit();
		},

		_onUnpin: function()
		{
			this.isPinned = false;
			this.reinit();
		},

		reinit: function()
		{
			this.unregisterObjects();
			this.reset();
			this.init(this.parent);
		},

		reset: function()
		{
			this.dragItem = null;
			this.targetItem = null;
			this.rowsList = null;
			this.colsList = null;
			this.dragRect = null;
			this.offset = null;
			this.startDragOffset = null;
			this.dragColumn = null;
			this.targetColumn = null;
			this.isDrag = null;
		},

		isActive: function()
		{
			return this.isDrag;
		},

		registerObjects: function(objects)
		{
			var self = this;

			[].forEach.call((objects || this.colsList), function(current) {
				current.onbxdragstart = BX.delegate(self._onDragStart, self);
				current.onbxdrag = BX.delegate(self._onDrag, self);
				current.onbxdragstop = BX.delegate(self._onDragEnd, self);
				jsDD.registerObject(current);
				jsDD.registerDest(current);
			});
		},

		unregisterObjects: function()
		{
			[].forEach.call(this.colsList, function(current) {
				jsDD.unregisterObject(current);
				jsDD.unregisterDest(current);
			});
		},

		getColsList: function()
		{
			var self = this;
			var list = [];
			var table;

			if (this.isPinned && this.parent.getParam('ALLOW_PIN_HEADER'))
			{
				table = this.parent.getPinHeader().getFixedTable();
				list = BX.Grid.Utils.getByTag(table, 'th');
			}
			else
			{
				list = this.parent.getRows().getHeadFirstChild().getCells();
			}

			list = [].filter.call(list, function(current) {
				return !self.isStatic(current);
			});

			return list;
		},

		isStatic: function(item)
		{
			return BX.hasClass(item, this.parent.settings.get('classCellStatic'));
		},

		getDragOffset: function()
		{
			return (jsDD.x - this.startDragOffset - this.dragRect.left);
		},

		getColumn: function(item)
		{
			var column = BX.Grid.Utils.getColumn(this.parent.getTable(), item);

			if (column.indexOf(item) === -1)
			{
				column.push(item);
			}

			return column;
		},

		_onDragStart: function()
		{
			this.isDrag = true;

			this.dragItem = jsDD.current_node;
			this.dragRect = this.dragItem.getBoundingClientRect();
			this.offset = this.dragRect.width;
			this.startDragOffset = jsDD.start_x - this.dragRect.left;
			this.dragColumn = this.getColumn(this.dragItem);
			this.dragIndex = BX.Grid.Utils.getIndex(this.colsList, this.dragItem);
		},

		_onDrag: function()
		{
			var currentRect, currentMiddle;
			var self = this;

			this.dragOffset = this.getDragOffset();
			this.targetItem = this.targetItem || this.dragItem;
			this.targetColumn = this.targetColumn || this.dragColumn;

			BX.Grid.Utils.styleForEach(this.dragColumn, {
				transition: '0ms',
				transform: 'translate3d('+this.dragOffset+'px, 0px, 0px)'
			});

			[].forEach.call(this.colsList, function(current, index) {
				if (current)
				{
					currentRect = current.getBoundingClientRect();
					currentMiddle = currentRect.left + (currentRect.width / 2) + BX.scrollLeft(window);

					if ((index > self.dragIndex && jsDD.x > currentMiddle) &&
						(current.style.transform !== 'translate3d('+(-self.offset)+'px, 0px, 0px)'))
					{
						self.targetColumn = self.getColumn(current);
						BX.Grid.Utils.styleForEach(self.targetColumn, {
							'transition': '300ms',
							'transform': 'translate3d('+(-self.offset)+'px, 0px, 0px)'
						});
					}

					if ((index < self.dragIndex && jsDD.x < currentMiddle) &&
						(current.style.transform !== 'translate3d('+(self.offset)+'px, 0px, 0px)'))
					{
						self.targetColumn = self.getColumn(current);
						BX.Grid.Utils.styleForEach(self.targetColumn, {
							'transition': '300ms',
							'transform': 'translate3d('+(self.offset)+'px, 0px, 0px)'
						});
					}

					if ((index > self.dragIndex && jsDD.x < currentMiddle &&
						current.style.transform !== '' &&
						current.style.transform !== 'translate3d(0px, 0px, 0px)') ||
						current.style.transform !== '' &&
						(index < self.dragIndex && jsDD.x > currentMiddle &&
						current.style.transform !== 'translate3d(0px, 0px, 0px)'))
					{
						self.targetColumn = self.getColumn(current);
						BX.Grid.Utils.styleForEach(self.targetColumn, {
							'transition': '300ms',
							'transform': 'translate3d(0px, 0px, 0px)'
						});
					}
				}
			});
		},

		_onDragEnd: function()
		{
			var self = this;
			var columns = [];

			[].forEach.call(this.dragColumn, function(current, index) {
				BX.Grid.Utils.collectionSort(current, self.targetColumn[index]);
			});

			[].forEach.call(this.rowsList, function(current) {
				BX.Grid.Utils.styleForEach(current.cells, {
					transition: '',
					transform: ''
				});
			});

			this.reinit();

			[].forEach.call(this.colsList, function(current) {
				columns.push(BX.data(current, 'name'));
			});

			this.parent.getUserOptions().setColumns(columns);
			BX.onCustomEvent(this.parent.getContainer(), 'Grid::columnMoved', [this.parent]);
		}
	};
})();