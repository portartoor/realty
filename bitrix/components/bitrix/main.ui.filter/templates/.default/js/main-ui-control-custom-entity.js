;(function() {
	'use strict';

	BX.namespace('BX.Main.ui');

	BX.Main.ui.CustomEntity = function()
	{
		this.field = null;
		this.labelInput = null;
		this.hiddenInput = null;
		this.popupContainer = null;
		this.inputClass = 'main-ui-control-string';
		this.squareClass = 'main-ui-square';
	};

	//noinspection JSUnusedGlobalSymbols
	BX.Main.ui.CustomEntity.prototype = {
		setField: function(field)
		{
			if (this.field !== field)
			{
				this.field = field;
				this.reset();
			}
		},

		reset: function()
		{
			this.labelInput = null;
			this.hiddenInput = null;
		},

		getField: function()
		{
			return this.field;
		},

		getId: function()
		{
			var hiddenNode = this.getHiddenNode();
			var id = null;

			if (BX.type.isDomNode(hiddenNode))
			{
				id = hiddenNode.name;
			}

			return id;
		},

		getLabelNode: function()
		{
			if (!BX.type.isDomNode(this.labelInput))
			{
				this.labelInput = BX.Filter.Utils.getBySelector(this.getField(), '.'+this.inputClass+'[type="text"]');
			}

			return this.labelInput;
		},

		getHiddenNode: function()
		{
			if (!BX.type.isDomNode(this.hiddenInput))
			{
				this.hiddenInput = BX.Filter.Utils.getBySelector(this.getField(), '.'+this.inputClass+'[type="hidden"]');
			}

			return this.hiddenInput;
		},

		getSquare: function()
		{
			return BX.Filter.Utils.getByClass(this.getField(), this.squareClass);
		},

		removeSquare: function()
		{
			var square = this.getSquare();

			if (BX.type.isDomNode(square) && BX.hasClass(square, this.squareClass))
			{
				BX.remove(square);
			}
		},

		setSquare: function(label, value)
		{
			var field = this.getField();
			var squareData = {
				block: 'main-ui-square',
				name: label,
				item: {
					'_label': label,
					'_value': value
				}
			};
			var square = BX.decl(squareData);

			BX.prepend(square, field);
		},

		getCurrentValues: function()
		{
			var square = this.getSquare();
			var data;

			try {
				data = JSON.parse(BX.data(square, 'item'));
				data = {
					label: data._label,
					value: data._value
				};
			} catch (err) {
				data = {label: '', value: ''};
			}

			return data;
		},

		setData: function(label, value)
		{
			var hiddenNode = this.getHiddenNode();
			this.removeSquare();
			this.setSquare(label, value);

			if (BX.type.isDomNode(hiddenNode))
			{
				hiddenNode.value = value;
				BX.fireEvent(hiddenNode, 'input');
			}
		},

		setPopupContainer: function(container)
		{
			if (BX.type.isDomNode(container))
			{
				this.popupContainer = container;
			}
		},

		getPopupContainer: function()
		{
			return this.popupContainer;
		}
	};
})();