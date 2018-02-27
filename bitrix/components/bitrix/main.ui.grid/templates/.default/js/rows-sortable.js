;(function() {
	'use strict';

	BX.namespace('BX.Grid');


	BX.Grid.RowsSortable = function(parent)
	{
		this.parent = null;
		this.list = null;
		this.setDefaultProps();
		this.init(parent);
	};

	BX.Grid.RowsSortable.prototype = {
		init: function(parent)
		{
			this.parent = parent;
			this.list = this.getList();
			this.prepareListItems();
			jsDD.Enable();

			if (!this.inited)
			{
				this.inited = true;
				BX.addCustomEvent('Grid::thereEditedRows', BX.proxy(this.disable, this));
				BX.addCustomEvent('Grid::noEditedRows', BX.proxy(this.enable, this));
			}
		},

		destroy: function()
		{
			BX.removeCustomEvent('Grid::thereEditedRows', BX.proxy(this.disable, this));
			BX.removeCustomEvent('Grid::noEditedRows', BX.proxy(this.enable, this));
			this.unregisterObjects();
		},

		disable: function()
		{
			this.unregisterObjects();
		},

		enable: function()
		{
			this.reinit();
		},

		reinit: function()
		{
			this.unregisterObjects();
			this.setDefaultProps();
			this.init(this.parent);
		},

		getList: function()
		{
			return this.parent.getRows().getSourceBodyChild();
		},

		unregisterObjects: function()
		{
			this.list = this.list.map(function(current) {
				jsDD.unregisterObject(current);
				jsDD.unregisterDest(current);
				return current;
			});
		},

		prepareListItems: function()
		{
			var self = this;
			this.list = this.list.map(function(current) {
				current.onbxdragstart = BX.delegate(self._onDragStart, self);
				current.onbxdrag = BX.delegate(self._onDrag, self);
				current.onbxdragstop = BX.delegate(self._onDragEnd, self);
				jsDD.registerObject(current);
				jsDD.registerDest(current);
				return current;
			});
		},

		getIndex: function(item)
		{
			return BX.Grid.Utils.getIndex(this.list, item);
		},

		_onDragStart: function()
		{
			this.dragItem = jsDD.current_node;
			this.dragIndex = this.getIndex(this.dragItem);
			this.dragRect = this.dragItem.getBoundingClientRect();
			this.offset = this.dragRect.height;
			this.dragStartOffset = (jsDD.start_y - (this.dragRect.top + BX.scrollTop(window)));

			BX.Grid.Utils.styleForEach(this.list, {'transition': +this.parent.settings.get('animationDuration') + 'ms'});
			BX.bind(document, 'mousemove', BX.delegate(this._onMouseMove, this));
			BX.addClass(this.parent.getContainer(), this.parent.settings.get('classOnDrag'));
			BX.addClass(this.dragItem, this.parent.settings.get('classDragActive'));
		},

		_onMouseMove: function(event)
		{
			this.realX = event.clientX;
			this.realY = event.clientY;
		},

		_onDrag: function()
		{
			var self = this;
			var currentRect, currentMiddle;

			this.dragOffset = (this.realY - this.dragRect.top - this.dragStartOffset);
			this.sortOffset = self.realY + BX.scrollTop(window);

			BX.Grid.Utils.styleForEach([this.dragItem], {
				'transition': '0ms',
				'transform': 'translate3d(0px, '+this.dragOffset+'px, 0px)'
			});

			this.list.forEach(function(current, index) {
				if (current)
				{
					currentRect = current.getBoundingClientRect();
					currentMiddle = currentRect.top + BX.scrollTop(window) + (currentRect.height / 2);

					if (index > self.dragIndex && self.sortOffset > currentMiddle &&
						current.style.transform !== 'translate3d(0px, '+(-self.offset)+'px, 0px)' &&
						current.style.transform !== '')
					{
						self.targetItem = current;
						BX.style(current, 'transform', 'translate3d(0px, '+(-self.offset)+'px, 0px)');
					}

					if (index < self.dragIndex && self.sortOffset < currentMiddle &&
						current.style.transform !== 'translate3d(0px, '+(self.offset)+'px, 0px)' &&
						current.style.transform !== '')
					{
						self.targetItem = current;
						BX.style(current, 'transform', 'translate3d(0px, '+(self.offset)+'px, 0px)');
					}

					if (((index < self.dragIndex && self.sortOffset > currentMiddle) ||
						(index > self.dragIndex && self.sortOffset < currentMiddle)) &&
						current.style.transform !== 'translate3d(0px, 0px, 0px)')
					{
						if (current.style.transform !== '')
						{
							self.targetItem = current;
						}

						BX.style(current, 'transform', 'translate3d(0px, 0px, 0px)');
					}
				}
			});
		},

		_onDragOver: function() {},

		_onDragLeave: function() {},

		_onDragEnd: function()
		{
			BX.unbind(document, 'mousemove', BX.delegate(this._onMouseMove, this));
			BX.removeClass(this.parent.getContainer(), this.parent.settings.get('classOnDrag'));
			BX.removeClass(this.dragItem, this.parent.settings.get('classDragActive'));

			BX.Grid.Utils.styleForEach(this.list, {'transition': '', 'transform': ''});
			BX.Grid.Utils.collectionSort(this.dragItem, this.targetItem);

			this.list = this.getList();
			this.setDefaultProps();
			BX.onCustomEvent(this.parent.getContainer(), 'Grid::rowMoved', [this.parent]);
		},

		setDefaultProps: function()
		{
			this.dragItem = null;
			this.targetItem = null;
			this.dragRect = null;
			this.dragIndex = null;
			this.offset = null;
			this.sortOffset = null;
			this.realX = null;
			this.realY = null;
			this.dragStartOffset = null;
			this.sortOffset = null;
		}
	};
})();