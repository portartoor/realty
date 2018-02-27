;(function() {
	'use strict';

	BX.namespace('BX.Grid');

	/**
	 * Updates grid
	 * @param parent
	 * @constructor
	 */
	BX.Grid.Updater = function(parent)
	{
		BX.Grid.Updater.superclass.constructor.apply(this, [parent]);
		this.reset();
	};

	BX.extend(BX.Grid.Updater, BX.Grid.BaseClass);
	{
		BX.Grid.Updater.prototype = Object.create(BX.Grid.BaseClass.prototype, {});

		BX.Grid.Updater.prototype.reset = function()
		{
			this.head = null;
			this.body = null;
			this.foot = null;
			this.moreButton = null;
			this.pagination = null;
			this.counterDisplayed = null;
			this.counterSelected = null;
			this.counterTotal = null;
			this.limit = null;
			this.actionPanel = null;
		};

		BX.Grid.Updater.prototype.updateHeadRows = function(rows)
		{
			var headers;

			if (BX.type.isArray(rows) && rows.length)
			{
				headers = this.getParent().getHeaders();

				headers.forEach(function(header) {
					header = BX.cleanNode(header);
					rows.forEach(function(row) {
						if (BX.type.isDomNode(row))
						{
							header.appendChild(BX.clone(row));
						}
					});
				});
			}
		};

		BX.Grid.Updater.prototype.appendHeadRows = function(rows)
		{
			var headers;

			if (BX.type.isArray(rows) && rows.length)
			{
				headers = this.getParent().getHeaders();

				headers.forEach(function(header) {
					rows.forEach(function(row) {
						if (BX.type.isDomNode(row))
						{
							header.appendChild(BX.clone(row));
						}
					});
				});
			}
		};

		BX.Grid.Updater.prototype.prependHeadRows = function(rows)
		{
			var headers;

			if (BX.type.isArray(rows) && rows.length)
			{
				headers = this.getParent().getHeaders();

				headers.forEach(function(header) {
					header = BX.cleanNode(header);
					rows.forEach(function(row) {
						if (BX.type.isDomNode(row))
						{
							header.prepend(BX.clone(row));
						}
					});
				});
			}
		};

		BX.Grid.Updater.prototype.updateBodyRows = function(rows)
		{
			var body;

			if (BX.type.isArray(rows))
			{
				body = BX.cleanNode(this.getParent().getBody());
				rows.forEach(function(current) {
					if (BX.type.isDomNode(current))
					{
						body.appendChild(current);
					}
				});
			}
		};

		BX.Grid.Updater.prototype.appendBodyRows = function(rows)
		{
			var body;

			if (BX.type.isArray(rows))
			{
				body = this.getParent().getBody();
				rows.forEach(function(current) {
					if (BX.type.isDomNode(current))
					{
						body.appendChild(current);
					}
				});
			}
		};

		BX.Grid.Updater.prototype.prependHeadRows = function(rows)
		{
			var body;

			if (BX.type.isArray(rows))
			{
				body = this.getParent().getHead();
				rows.forEach(function(current) {
					if (BX.type.isDomNode(current))
					{
						BX.prepend(body, current);
					}
				});
			}
		};

		BX.Grid.Updater.prototype.updateFootRows = function(rows)
		{
			var foot;

			if (BX.type.isArray(rows))
			{
				foot = BX.cleanNode(this.getParent().getFoot());
				rows.forEach(function(current) {
					if (BX.type.isDomNode(current))
					{
						foot.appendChild(current);
					}
				});
			}
		};

		BX.Grid.Updater.prototype.updateCounterTotal = function(counter)
		{
			var counterCell;

			if (BX.type.isDomNode(counter))
			{
				counterCell = BX.cleanNode(this.getParent().getCounterTotal());
				counterCell.appendChild(counter);
			}
		},

			BX.Grid.Updater.prototype.updatePagination = function(pagination)
			{
				var paginationCell;

				if (BX.type.isDomNode(pagination))
				{
					paginationCell = BX.cleanNode(this.getParent().getPagination().getContainer());
					paginationCell.appendChild(pagination);
				}
			};

		BX.Grid.Updater.prototype.updateMoreButton = function(button)
		{
			var buttonParent = BX.Grid.Utils.closestParent(this.getParent().getMoreButton().getNode());

			if (BX.type.isDomNode(button))
			{
				if (BX.isNodeHidden(buttonParent))
				{
					BX.show(buttonParent);
				}

				buttonParent = BX.cleanNode(buttonParent);
				buttonParent.appendChild(button);
			}
			else
			{
				BX.hide(buttonParent);
			}
		};
	}
})();