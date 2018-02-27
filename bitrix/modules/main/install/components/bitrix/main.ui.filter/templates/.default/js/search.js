;(function() {
	'use strict';

	BX.namespace('BX.Filter');

	/**
	 * Filter search block class
	 * @param parent
	 * @constructor
	 */
	BX.Filter.Search = function(parent)
	{
		this.parent = null;
		this.container = null;
		this.input = null;
		this.preset = null;
		this.buttonsContainer = null;
		this.init(parent);
	};
	BX.Filter.Search.prototype = {
		init: function(parent)
		{
			this.parent = parent;
			BX.bind(this.getInput(), 'input', BX.delegate(this._onInputWithoutDebounce, this));

			if (this.parent.getParam('ENABLE_LIVE_SEARCH'))
			{
				BX.bind(this.getInput(), 'input', BX.debounce(this._onInput, 250, this));
			}

			BX.bind(this.getInput(), 'keydown', BX.delegate(this._onKeyDown, this));
			BX.bind(this.getFindButton(), 'click', BX.delegate(this._onSearchClick, this));
			this.firstInit = true;
		},

		getFindButton: function()
		{
			if (!BX.type.isDomNode(this.findButton))
			{
				this.findButton = BX.Filter.Utils.getByClass(this.getContainer(), this.parent.settings.classSearchButton);
			}

			return this.findButton;
		},

		_onSearchClick: function()
		{
			this.parent.applyFilter(null, true);
		},

		getCode: function(event)
		{
			var code = '';

			if ('code' in event && event.code)
			{
				code = event.code;
			}
			else if ('keyCode' in event && event.keyCode)
			{
				var keyCode = event.keyCode;
				var keysMap = {
					27: 'Escape',
					9: 'Tab',
					40: 'ArrowDown',
					38: 'ArrowUp',
					8: 'Backspace',
					13: 'Enter',
					65: 'KeyA'
				};

				code = keyCode in keysMap ? keysMap[keyCode] : '';
			}

			return code;
		},

		_onKeyDown: function(event)
		{
			var square = BX.Filter.Utils.getByClass(event.target.parentNode, this.parent.settings.classSquare, true);
			var deleteButton;

			if (this.getCode(event) === 'Enter')
			{
				this.parent.applyFilter(null, true);

				if (this.parent.getPopup().isShown())
				{
					this.parent.closePopup();
				}

				this.isAllSelected = false;
			}
			else if (this.getCode(event) === 'Tab' || this.getCode(event) === 'ArrowDown')
			{
				if (!this.parent.getPopup().isShown())
				{
					this.parent.showPopup();
					this.parent.adjustFocus();
				}

				if (this.isAllSelected)
				{
					square.forEach(function(current) {
						BX.removeClass(current, this.parent.settings.classSquareSelected);
					}, this);
				}

				this.isAllSelected = false;
			}
			else if (this.getCode(event) === 'ArrowUp')
			{
				if (this.parent.getPopup().isShown())
				{
					this.parent.closePopup();

					if (this.parent.getParam('VALUE_REQUIRED_MODE'))
					{
						this.parent.restoreRemovedPreset();
					}
				}

				this.isAllSelected = false;
			}
			else if (this.getCode(event) === 'KeyA' && event.metaKey || this.getCode(event) === 'KeyA' && event.ctrlKey)
			{
				if (BX.type.isArray(square))
				{
					square.forEach(function(current) {
						BX.addClass(current, this.parent.settings.classSquareSelected)
					}, this);
				}

				this.isAllSelected = true;
			}
			else if (this.getCode(event) === 'Backspace' && event.currentTarget.selectionStart === 0 && this.isAllSelected)
			{
				this.parent.resetFilter();
				this.isAllSelected = false;
			}
			else if (this.getCode(event) === 'Backspace' && event.currentTarget.selectionStart === 0 && event.currentTarget.selectionEnd === 0 && !this.isAllSelected)
			{
				square = BX.type.isArray(square) && square.length ? square[square.length-1] : null;

				if (BX.type.isDomNode(square))
				{
					if (BX.hasClass(square, this.parent.settings.classSquareSelected))
					{
						deleteButton = BX.Filter.Utils.getByClass(square, this.parent.settings.classSquareDelete);

						if (BX.type.isDomNode(deleteButton))
						{
							BX.fireEvent(deleteButton, 'click');
						}
					}
					else
					{
						BX.addClass(square, this.parent.settings.classSquareSelected);
					}
				}
			}
			else if (BX.type.isArray(square) && square.length &&
				event.key !== 'Meta' &&
				event.key !== 'CapsLock' &&
				event.key !== 'Shift' &&
				event.key !== 'Alt' &&
				event.key !== 'Escape' &&
				event.key !== 'F1' &&
				event.key !== 'F2' &&
				event.key !== 'F3' &&
				event.key !== 'F4' &&
				event.key !== 'F5' &&
				event.key !== 'F6' &&
				event.key !== 'F7' &&
				event.key !== 'F8' &&
				event.key !== 'F9' &&
				event.key !== 'F10' &&
				event.key !== 'F11' &&
				event.key !== 'F12')
			{
				square.forEach(function(current) {
					BX.removeClass(current, this.parent.settings.classSquareSelected);
				}, this);
			}
		},

		getSearchString: function(e)
		{
			return e && (e.currentTarget || e.target) ? (e.currentTarget || e.target).value : '';
		},

		getSquares: function()
		{
			return BX.Filter.Utils.getByClass(this.getContainer(), this.parent.settings.classSquare, true);
		},

		adjustPlaceholder: function()
		{
			this.setInputPlaceholder(this.parent.getParam('MAIN_UI_FILTER__PLACEHOLDER' + (this.parent.getParam("DISABLE_SEARCH") ? '' : '_DEFAULT')));
		},

		_onInputWithoutDebounce: function(event)
		{
			var searchString = this.getSearchString(event);

			if (searchString != this.lastSearchString &&
				(!BX.hasClass(document.documentElement, 'bx-ie') || !this.firstInit))
			{
				if (this.parent.getParam('ENABLE_LIVE_SEARCH'))
				{
					this.parent.grid && this.parent.grid.tableFade();
				}

				this.parent.getPopup().isShown() && this.parent.closePopup();
			}

			if (searchString)
			{
				this.showClearButton();
			}
			else
			{
				if (!this.getSquares().length && this.lastSearchString !== searchString)
				{
					this.hideClearButton();
					this.adjustPlaceholder();
				}
			}

			this.lastSearchString = searchString;
		},

		_onInput: function(event)
		{
			var parent = this.parent;
			var searchString = this.getSearchString(event);

			if (!BX.hasClass(document.documentElement, 'bx-ie') || !this.firstInit)
			{
				if (searchString.length && this.lastValue != searchString)
				{
					parent.applyFilter(null, true);
					this.lastValue = searchString;
					this.isClear = false;
				}
				else
				{
					if (!this.isClear)
					{
						this.isClear = true;
						parent.applyFilter(true, true);
					}
					else
					{
						this.parent.grid && this.parent.grid.tableUnfade();
					}
				}
			}

			this.firstInit = false;
		},

		getButtonsContainer: function()
		{
			if (!BX.type.isDomNode(this.buttonsContainer))
			{
				this.buttonsContainer = BX.Filter.Utils.getByClass(this.getContainer(), this.parent.settings.classSearchButtonsContainer);
			}

			return this.buttonsContainer;
		},

		showClearButton: function()
		{
			BX.addClass(this.getButtonsContainer(), this.parent.settings.classShow);
		},

		hideClearButton: function()
		{
			BX.removeClass(this.getButtonsContainer(), this.parent.settings.classShow);
		},

		getInput: function()
		{
			var inputId;

			if (!BX.type.isDomNode(this.input))
			{
				inputId = [this.parent.getParam('FILTER_ID', ''), '_search'].join('');
				this.input = BX(inputId);
			}

			return this.input;
		},

		getContainer: function()
		{
			var containerId;

			if (!BX.type.isDomNode(this.container))
			{
				containerId = [this.parent.getParam('FILTER_ID'), '_search_container'].join('');
				this.container = BX(containerId);
			}

			return this.container;
		},

		setInputPlaceholder: function(text)
		{
			var input = this.getInput();
			input.placeholder = text;
		},

		clearInput: function()
		{
			var form = this.getInput();

			if (BX.type.isDomNode(form))
			{
				form.value = null;
			}
		},

		clearForm: function()
		{
			this.clearInput();
			this.removePreset();
		},

		makeSquares: function(squaresData, depth, additional)
		{
			var square;
			var tmpSquare = null;
			var container = this.getContainer();
			var result = {squares: [], moreSquares: []};

			squaresData.forEach(function(current, index) {
				if (index < depth)
				{
					square = BX.decl(current);
					tmpSquare = tmpSquare || square;

					if (!additional)
					{
						if (index === 0)
						{
							BX.prepend(square, container);
						}
						else
						{
							BX.insertAfter(square, tmpSquare);
						}
					}
					else
					{
						var lastSquare = BX.Filter.Utils.getByClass(this.getContainer(), this.parent.settings.classSquare);
						BX.insertAfter(square, lastSquare);
					}

					tmpSquare = square;
					result.squares.push(square);
				}
				else
				{
					result.moreSquares.push({type: 'control', name: current.value, title: current.title});
				}
			}, this);

			return result;
		},

		squares: function(fields, depth, additional)
		{
			var squaresData, moreSquares, square, squaresWidth, result;
			var squares = BX.Filter.Utils.getByClass(this.getContainer(), this.parent.settings.classSquare, true);

			if (additional)
			{
				squares.forEach(function(current) {
					var item = BX.data(current, 'item');

					if (item)
					{
						BX.remove(current);
					}
				});
			}
			else
			{
				squares.forEach(BX.remove);
			}

			squaresData = this.prepareSquaresData(fields);
			moreSquares = this.makeSquares(squaresData, depth, additional);
			squaresWidth = 0;
			result = {squaresData: squaresData, width: 0};

			if (moreSquares.moreSquares.length)
			{
				square = {
					block: 'main-ui-search-square',
					name: this.parent.getParam('MAIN_UI_FILTER__AND') + ' ' + this.parent.getParam('MAIN_UI_FILTER__MORE') + ' ' + moreSquares.moreSquares.length,
					item: moreSquares.moreSquares,
					title: moreSquares.moreSquares.map(function(curr) { return curr.title; }).join(', \n')
				};

				square = BX.decl(square);
				moreSquares.squares.push(square);
				BX.insertAfter(square, moreSquares.squares[moreSquares.squares.length-2]);

				squaresWidth = moreSquares.squares.reduce(function(prev, curr) {
					return prev + BX.width(curr) + (parseFloat(BX.style(curr, 'margin-right')) || 0);
				}, 0);
			}

			result.width = squaresWidth;

			return result;
		},

		setPreset: function(presetData)
		{
			var container = this.getContainer();
			var square, squares;
			var squaresResult;

			if (BX.type.isPlainObject(presetData))
			{
				squares = BX.Filter.Utils.getByClass(container, this.parent.settings.classSquare, true);
				squares.forEach(BX.remove);

				if (presetData.ID !== 'default_filter' && presetData.ID !== 'tmp_filter')
				{
					square = BX.decl({
						block: 'main-ui-search-square',
						name: presetData.TITLE,
						value: presetData.ID,
						isPreset: true
					});

					BX.prepend(square, container);

					if ('ADDITIONAL' in presetData && BX.type.isArray(presetData.ADDITIONAL) && presetData.ADDITIONAL.length)
					{
						squaresResult = this.squares(presetData.ADDITIONAL, 1, true);

						if ((BX.width(container) - squaresResult.width) < 100)
						{
							squaresResult = this.squares(presetData.ADDITIONAL, 0, true);
						}
					}
				}
				else
				{
					if (BX.type.isArray(presetData.FIELDS) && presetData.FIELDS.length)
					{
						squaresResult = this.squares(presetData.FIELDS, 2);

						if ((BX.width(container) - squaresResult.width) < 100)
						{
							squaresResult = this.squares(presetData.FIELDS, 1);
						}
					}
				}


				if (squaresResult && BX.type.isArray(squaresResult.squaresData) && squaresResult.squaresData.length || (presetData.ID !== 'default_filter' && presetData.ID !== 'tmp_filter'))
				{
					this.setInputPlaceholder(this.parent.getParam('MAIN_UI_FILTER__PLACEHOLDER_WITH_FILTER'));
					this.showClearButton();
				}
				else
				{
					if (this.parent.getParam("DISABLE_SEARCH"))
					{
						this.setInputPlaceholder(this.parent.getParam('MAIN_UI_FILTER__PLACEHOLDER'));
					}
					else
					{
						this.setInputPlaceholder(this.parent.getParam('MAIN_UI_FILTER__PLACEHOLDER_DEFAULT'));
					}
				}

				if (BX.type.isNotEmptyString(this.parent.getSearch().getInput().value))
				{
					this.showClearButton();
				}
			}
		},

		prepareSquaresData: function(fields)
		{
			var value, tmpValues, title, control;
			var result = [];

			fields.map(function(current) {
				value = null;

				switch (current.TYPE)
				{
					case this.parent.types.DATE : {
						value = current.LABEL + ': ' + current.SUB_TYPE.NAME;

						if (current.SUB_TYPE.VALUE === this.parent.dateTypes.QUARTER &&
							BX.type.isNotEmptyString(current.VALUES._quarter))
						{
							var quarter = current.QUARTERS.filter(function(curr) {
								return curr.VALUE == current.VALUES._quarter;
							}).map(function(curr) {
								return curr.NAME;
							});

							quarter = quarter.length ? quarter.join('') : '';

							value = current.LABEL + ': ' + quarter + ' ' +
								this.parent.getParam('MAIN_UI_FILTER__QUARTER').toLocaleLowerCase() + ' ' +
								current.VALUES._year;
						}

						if (current.SUB_TYPE.VALUE === this.parent.dateTypes.YEAR &&
							BX.type.isNotEmptyString(current.VALUES._year))
						{
							value = current.LABEL + ': ' + current.VALUES._year;
						}

						if (current.SUB_TYPE.VALUE === this.parent.dateTypes.MONTH &&
							BX.type.isNotEmptyString(current.VALUES._month))
						{
							var month = current.MONTHS.filter(function(curr) {
								return curr.VALUE == current.VALUES._month;
							}).map(function(curr) {
								return curr.NAME;
							});

							month = month.length ? month.join('') : '';

							value = current.LABEL + ': ' + month + ' ' + current.VALUES._year;
						}

						if (current.SUB_TYPE.VALUE === this.parent.dateTypes.EXACT &&
							BX.type.isNotEmptyString(current.VALUES._from))
						{
							value = current.LABEL + ': ' + current.VALUES._from;
						}

						if (current.SUB_TYPE.VALUE === this.parent.dateTypes.RANGE)
						{
							if (BX.type.isNotEmptyString(current.VALUES._from) && BX.type.isNotEmptyString(current.VALUES._to))
							{
								value = current.LABEL + ': ' + current.VALUES._from + '-' + current.VALUES._to;
							}
							else if (!BX.type.isNotEmptyString(current.VALUES._from) && BX.type.isNotEmptyString(current.VALUES._to))
							{
								value = current.LABEL + ': ' + this.parent.getParam('MAIN_UI_FILTER__BEFORE') + ' ' + current.VALUES._to;
							}
							else if (BX.type.isNotEmptyString(current.VALUES._from) && !BX.type.isNotEmptyString(current.VALUES._to))
							{
								value = current.LABEL + ': ' + this.parent.getParam('MAIN_UI_FILTER__AFTER') + ' ' + current.VALUES._from;
							}
						}

						if (current.SUB_TYPE.VALUE === this.parent.dateTypes.NONE)
						{
							value = null;
						}

						break;
					}

					case this.parent.types.SELECT : {
						if (BX.type.isPlainObject(current.VALUE) && current.VALUE.VALUE)
						{
							value = current.LABEL + ': ' + current.VALUE.NAME;
						}
						break;
					}

					case this.parent.types.MULTI_SELECT : {
						if (BX.type.isArray(current.VALUE) && current.VALUE.length)
						{
							tmpValues = [];
							value = current.LABEL + ': ';
							current.VALUE.forEach(function(val, index) {
								if (index < 2)
								{
									tmpValues.push(val.NAME);
								}
							});

							value += tmpValues.join(', ');

							if (current.VALUE.length > 2)
							{
								title = [];

								current.VALUE.forEach(function(val) {
									title.push(val.NAME);
								});

								value = title.join(', ');
							}
						}
						break;
					}

					case this.parent.types.NUMBER : {
						if (current.SUB_TYPE.VALUE === 'exact')
						{
							if (BX.type.isNotEmptyString(current.VALUES._from))
							{
								value = current.LABEL + ': ' + current.VALUES._from;
							}
							else
							{
								value = null;
							}
						}

						if (current.SUB_TYPE.VALUE === 'range')
						{
							if (BX.type.isNotEmptyString(current.VALUES._from) && BX.type.isNotEmptyString(current.VALUES._to))
							{
								value = current.LABEL + ': ' + current.VALUES._from + '-' + current.VALUES._to;
							}
							else if (!BX.type.isNotEmptyString(current.VALUES._from) && BX.type.isNotEmptyString(current.VALUES._to))
							{
								value = current.LABEL + ': ' + this.parent.getParam('MAIN_UI_FILTER__NUMBER_LESS') + ' ' + current.VALUES._to;
							}
							else if (BX.type.isNotEmptyString(current.VALUES._from) && !BX.type.isNotEmptyString(current.VALUES._to))
							{
								value = current.LABEL + ': ' + this.parent.getParam('MAIN_UI_FILTER__NUMBER_MORE') + ' ' + current.VALUES._from;
							}
							else
							{
								value = null;
							}
						}

						if (current.SUB_TYPE.VALUE === 'more')
						{
							if (BX.type.isNotEmptyString(current.VALUES._from))
							{
								value = current.LABEL + ': > ';
								value += current.VALUES._from;
							}
						}

						if (current.SUB_TYPE.VALUE === 'less')
						{
							if (BX.type.isNotEmptyString(current.VALUES._to))
							{
								value = current.LABEL + ': < ';
								value += current.VALUES._to;
							}
						}
						break;
					}

					case this.parent.types.CUSTOM_ENTITY : {
						if (BX.type.isNotEmptyString(current.VALUES._value) &&
							BX.type.isNotEmptyString(current.VALUES._label))
						{
							value = current.LABEL + ': ';
							value += current.VALUES._label;
						}
						break;
					}

					case this.parent.types.CUSTOM : {
						value = '_VALUE' in current && BX.type.isNotEmptyString(current._VALUE) ? current.LABEL : null;
						break;
					}

					default : {
						if (BX.type.isNotEmptyString(current.VALUE))
						{
							value = current.LABEL + ': ' + current.VALUE;
						}
						break;
					}
				}

				if (value !== null)
				{
					result.push({
						block: 'main-ui-search-square',
						name: value,
						value: current.NAME,
						item: {type: 'control', name: current.NAME},
						title: value
					});
				}
			}, this);

			return result;
		},

		getPreset: function()
		{
			var container = this.getContainer();
			var presetClass = this.parent.settings.classSquare;
			var preset = null;

			if (BX.type.isDomNode(container))
			{
				preset = BX.Filter.Utils.getByClass(container,presetClass);
			}

			return preset;
		},

		removePreset: function()
		{
			var preset = this.getPreset();

			if (BX.type.isDomNode(preset))
			{
				BX.remove(preset);

				if (this.parent.getParam("DISABLE_SEARCH"))
				{
					this.setInputPlaceholder(this.parent.getParam('MAIN_UI_FILTER__PLACEHOLDER'));
				}
				else
				{
					this.setInputPlaceholder(this.parent.getParam('MAIN_UI_FILTER__PLACEHOLDER_DEFAULT'));
				}
			}

			this.hideClearButton();
		},

		updatePreset: function(presetData)
		{
			this.removePreset();
			this.setPreset(presetData);
		}
	};

})();