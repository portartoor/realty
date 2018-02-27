;(function() {
	'use strict';

	BX.namespace('BX.Grid');


	/**
	 * BX.Grid.Rows
	 * @param {BX.Main.grid} parent
	 * @constructor
	 */
	BX.Grid.Rows = function(parent)
	{
		this.parent = null;
		this.rows = null;
		this.headChild = null;
		this.bodyChild = null;
		this.footChild = null;
		this.init(parent);
	};

	BX.Grid.Rows.prototype = {
		init: function(parent)
		{
			this.parent = parent;
		},

		reset: function()
		{
			this.rows = null;
			this.headChild = null;
			this.bodyChild = null;
			this.footChild = null;
		},

		getFootLastChild: function()
		{
			return this.getLast(this.getFootChild());
		},

		getFootFirstChild: function()
		{
			return this.getFirst(this.getFootChild());
		},

		getBodyLastChild: function()
		{
			return this.getLast(this.getBodyChild());
		},

		getBodyFirstChild: function()
		{
			return this.getFirst(this.getBodyChild());
		},

		getHeadLastChild: function()
		{
			return this.getLast(this.getHeadChild());
		},

		getHeadFirstChild: function()
		{
			return this.getFirst(this.getHeadChild());
		},

		getEditSelectedValues: function()
		{
			var selectedRows = this.getSelected();
			var values = {};

			selectedRows.forEach(
				function(current)
				{
					values[current.getId()] = current.editGetValues();
				}
			);

			return values;
		},

		getSelectedIds: function()
		{
			return this.getSelected().map(function(current) {
				return current.getId();
			});
		},

		initSelected: function()
		{
			var selected = this.getSelected();

			if (BX.type.isArray(selected) && selected.length)
			{
				selected.forEach(function(row) {
					row.initSelect();
				});

				this.parent.enableActionsPanel();
			}
		},

		editSelected: function()
		{
			this.getSelected().forEach(function(current) {
				current.edit();
			});

			BX.onCustomEvent(window, 'Grid::thereEditedRows', []);
		},

		editSelectedCancel: function()
		{
			this.getSelected().forEach(function(current) {
				current.editCancel();
			});

			BX.onCustomEvent(window, 'Grid::noEditedRows', []);
		},

		isSelected: function()
		{
			return this.getBodyChild().some(function(current) {
				return current.isSelected();
			});
		},

		isAllSelected: function()
		{
			return !this.getBodyChild().some(function(current) {
				return !current.isSelected();
			});
		},

		getParent: function()
		{
			return this.parent;
		},

		getCountSelected: function()
		{
			var result;

			try {
				result = this.getSelected().length;
			} catch(err) {
				result = 0;
			}

			return result;
		},

		getCountDisplayed: function()
		{
			var result;

			try {
				result = this.getBodyChild().length;
			} catch(err) {
				result = 0;
			}

			return result;
		},

		addRows: function(rows)
		{
			var body = BX.findChild(
				this.getParent().getTable(),
				{tag: 'TBODY'},
				true,
				false
			);

			rows.forEach(function(current) {
				body.appendChild(current);
			});
		},

		getRows: function()
		{
			var result;
			var self = this;

			if (!this.rows)
			{
				result = BX.Grid.Utils.getByTag(this.getParent().getTable(), 'tr');

				this.rows = result.map(function(current) {
					return new BX.Grid.Row(self.parent, current);
				});
			}

			return this.rows;
		},

		getSelected: function()
		{
			return this.getBodyChild().filter(function(current) {
				return current.isSelected();
			});
		},

		normalizeNode: function(node)
		{
			return BX.findParent(node, {class: this.getParent().settings.get('classBodyRow')}, true, false);
		},

		getById: function(id)
		{
			var rows = this.getBodyChild();

			var row = rows.filter(function(current) {
				return current.getId() === id;
			});

			return row.length === 1 ? row[0] : null;
		},

		get: function(node)
		{
			var result = null;
			var filter;

			if (BX.type.isDomNode(node))
			{
				node = this.normalizeNode(node);

				filter = this.getRows().filter(function(current) {
					return node === current.getNode();
				});

				if (filter.length)
				{
					result = filter[0];
				}
			}

			return result;
		},

		/** @static @method getLast */
		getLast: function(array)
		{
			var result;

			try {
				result = array[array.length-1];
			} catch (err) {
				result = null;
			}

			return result;
		},

		/** @static @method getFirst */
		getFirst: function(array)
		{
			var result;

			try {
				result = array[0];
			} catch (err) {
				result = null;
			}

			return result;
		},

		getHeadChild: function()
		{
			this.headChild = this.headChild || this.getRows().filter(function(current) {
					return current.isHeadChild();
				});

			return this.headChild;
		},

		getBodyChild: function()
		{
			this.bodyChild = this.bodyChild || this.getRows().filter(function(current) {
					return current.isBodyChild();
				});

			return this.bodyChild;
		},

		getFootChild: function()
		{
			this.footChild = this.footChild || this.getRows().filter(function(current) {
					return current.isFootChild();
				});

			return this.footChild;
		},

		selectAll: function()
		{
			this.getRows().map(function(current) {
				current.select();
			});
		},

		unselectAll: function()
		{
			this.getRows().map(function(current) {
				current.unselect();
			});
		},

		getByIndex: function(rowIndex)
		{
			var filter = this.getBodyChild().filter(function(item) {
				return item.getNode().rowIndex === rowIndex;
			});

			return filter.length ? filter[0] : null;
		},

		getSourceRows: function()
		{
			return BX.Grid.Utils.getByTag(this.getParent().getTable(), 'tr');
		},

		getSourceBodyChild: function()
		{
			return this.getSourceRows().filter(function(current) {
				return BX.Grid.Utils.closestParent(current).nodeName === 'TBODY';
			});
		},

		getSourceHeadChild: function()
		{
			return this.getSourceRows().filter(function(current) {
				return BX.Grid.Utils.closestParent(current).nodeName === 'THEAD';
			});
		},

		getSourceFootChild: function()
		{
			return this.getSourceRows().filter(function(current) {
				return BX.Grid.Utils.closestParent(current).nodeName === 'TFOOT';
			});
		}
	};
})();
