;(function() {
	'use strict';

	BX.namespace('BX.Main.ui');

	BX.Main.ui.factory = function()
	{
		if (BX.Main.ui.factory instanceof BX.Main.ui.factory)
		{
			return BX.Main.ui.factory;
		}

		this.data = [];
		this.classControl = 'main-ui-control';
		this.classSelect = 'main-ui-select';
		this.classMultiSelect = 'main-ui-multi-select';
		this.classDate = 'main-ui-date';
		this.init();
	};

	BX.Main.ui.factory.prototype = {
		init: function()
		{
			BX.bind(document, 'click', BX.delegate(this._onClick, this));
			document.addEventListener('focus', BX.delegate(this._onClick, this), true);
		},

		_onClick: function(event)
		{
			var result;
			var self = this;
			var maxDepth = 5;

			if (!('path' in event) || !event.path.length)
			{
				event.path = [event.target];
				var i = 0;
				var x;
				while ((x = event.path[i++].parentNode) != null) event.path.push(x);
			}

			event.path.forEach(function(current, index) {
				if (index > maxDepth || !BX.type.isDomNode(current))
				{
					return false;
				}

				if (BX.hasClass(current, self.classControl))
				{
					if (BX.hasClass(current, self.classSelect) || BX.hasClass(current, self.classMultiSelect))
					{
						result = self.get(current);

						if (!result)
						{
							result = {node: current, instance: new BX.Main.ui.select(current)};
							self.data.push(result);
							result.instance._onControlClick(event);
						}
					}

					if (BX.hasClass(current, self.classDate))
					{
						result = self.get(current);

						if (!result)
						{
							result = {node: current, instance: new BX.Main.ui.date(current)};
							self.data.push(result);
						}

						return false;
					}
				}
			});
		},

		get: function(node)
		{
			var filtered = this.data.filter(function(current) {
				return node === current.node;
			});

			return filtered.length > 0 ? filtered[0] : null;
		}
	};

	BX.Main.ui.factory = new BX.Main.ui.factory();
})();