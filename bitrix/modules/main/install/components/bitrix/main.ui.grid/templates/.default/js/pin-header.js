;(function() {
	'use strict';

	BX.namespace('BX.Grid');


	/**
	 * BX.Grid.PinHeader
	 * @param {BX.Main.grid} parent
	 * @constructor
	 */
	BX.Grid.PinHeader = function(parent)
	{
		this.parent = null;
		this.fixedTable = null;
		this.header = null;
		this.init(parent);
	};

	BX.Grid.PinHeader.prototype = {
		init: function(parent)
		{
			this.parent = parent;
			this.fixedTable = this.getFixedTable();
			this.header = this.getHeader();
			this.headerTop = BX.pos(this.header).top;

			BX.bind(window, 'resize', BX.proxy(this.adjustFixedTablePosition, this));
			BX.bind(window, 'scroll', BX.proxy(this._onScroll, this));
		},

		destroy: function()
		{
			BX.unbind(window, 'resize', BX.proxy(this.adjustFixedTablePosition, this));
			BX.unbind(window, 'scroll', BX.proxy(this._onScroll, this));
			this.unpinHeader();
		},

		getFixedTable: function()
		{
			var container;

			if (!this.fixedTable)
			{
				container = BX.create('div', {
					props: {className: 'main-grid-fixed-bar main-grid-fixed-top'}
				});

				this.fixedTable = BX.create('table', {props: {className: 'main-grid-table'}});
				container.appendChild(this.fixedTable);
				this.parent.getScrollContainer().parentNode.appendChild(container);
			}

			return this.fixedTable;
		},

		checkHeaderPosition: function()
		{
			return this.headerTop <= window.scrollY;
		},

		getHeader: function()
		{
			this.header = this.header || this.parent.getHead();
			return this.header;
		},

		pinHeader: function()
		{
			if (!this.isPinned())
			{
				var fixedTable = this.getFixedTable();
				var head = this.parent.getHead();
				var cells = BX.Grid.Utils.getByTag(head, 'th');

				cells.forEach(function(cell) {
					var cellContainer = cell.firstElementChild;
					if (cellContainer)
					{
						cellContainer.style.width = cell.clientWidth + 'px';
						cell.style.width = cell.clientWidth + 'px';
					}
				});

				var clone = BX.clone(this.header);
				fixedTable.appendChild(clone);
				fixedTable.parentNode.style.width = fixedTable.parentNode.parentNode.clientWidth + 'px';
				BX.onCustomEvent(window, 'Grid::headerPinned', []);
			}
		},

		unpinHeader: function()
		{
			if (this.isPinned())
			{
				BX.html(this.getFixedTable(), '');
				BX.onCustomEvent(window, 'Grid::headerUnpinned', []);
			}
		},

		isPinned: function()
		{
			return this.getFixedTable().children.length;
		},

		adjustFixedTablePosition: function()
		{
			if (this.getFixedTable())
			{
				var container = this.parent.getContainer();

				if (container)
				{
					var containerRect = container.getBoundingClientRect();
					var leftPos = containerRect.left;
					var containerWidth = containerRect.width;

					if (leftPos !== this.lastLeftPos)
					{
						this.getFixedTable().parentNode.style.left = leftPos + 'px';
					}

					if (containerWidth !== this.lastContainerWidth)
					{
						this.getFixedTable().parentNode.style.width = containerWidth + 'px';
					}

					this.lastLeftPos = leftPos;
					this.lastContainerWidth = containerWidth;
				}
			}
		},

		_onScroll: function()
		{
			this.adjustFixedTablePosition();

			if (this.checkHeaderPosition())
			{
				this.pinHeader();
			}
			else
			{
				this.unpinHeader();
			}
		}
	};

})();