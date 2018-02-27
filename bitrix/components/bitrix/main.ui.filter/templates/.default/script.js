;(function() {
	'use strict';

	BX.namespace('BX.Main');


	/**
	 * General filter class
	 * @param arParams
	 * @param options
	 * @param types
	 * @param dateTypes
	 * @param numberTypes
	 */
	BX.Main.Filter = function(arParams, options, types, dateTypes, numberTypes)
	{
		this.params = null;
		this.search = null;
		this.popup = null;
		this.presets = null;
		this.fields = null;
		this.types = null;
		this.dateTypes = null;
		this.settings = null;
		this.filter = null;
		this.api = null;
		this.isAddPresetModeState = false;
		this.init(arParams, options, types, dateTypes, numberTypes);
	};

	//noinspection JSUnusedGlobalSymbols
	BX.Main.Filter.prototype = {
		init: function(arParams, options, types, dateTypes, numberTypes)
		{
			try {
				this.params = arParams;
				/**
				 * @type {Object}
				 * @property STRING
				 * @property SELECT
				 * @property DATE
				 * @property MULTI_SELECT
				 * @property NUMBER
				 * @property CUSTOM_ENTITY
				 * @property CHECKBOX
				 * @property CUSTOM
				 * @property ENTITY
				 */
				this.types = types;
				this.dateTypes = dateTypes;
				this.numberTypes = numberTypes;
				this.settings = new BX.Filter.Settings(options, this);

				BX.bind(document, 'mousedown', BX.delegate(this._onDocumentClick, this));
				BX.bind(this.getSearch().getContainer(), 'click', BX.delegate(this._onSearchContainerClick, this));

				if (this.getParam('GRID_ID'))
				{
					BX.addCustomEvent('Grid::ready', BX.delegate(this._onGridReady, this));
				}
			} catch (err) {
				throw err;
			}

			BX.bind(document, 'keydown', BX.delegate(this._onDocumentKeydown, this));

			this.getPopup();
			this.getSearch().updatePreset(this.getPreset().getCurrentPresetData());
			this.getPreset().applyPreset(this.getPreset().getCurrentPresetId());

			if (this.getParam('CLEAR_GET'))
			{
				this.clearGet();
			}

			BX.bind(window, 'load', BX.delegate(function() {
				this.adjustFocus();
			}, this));

			this.firstInit = true;
		},

		clearGet: function()
		{
			if ('history' in window)
			{
				var url = window.location.toString();
				var clearUrl = BX.util.remove_url_param(url, 'apply_filter');
				window.history.replaceState(null, '', clearUrl);
			}
		},

		adjustFocus: function()
		{
			var searchInput = this.getSearch().getInput();

			if (BX.type.isDomNode(searchInput))
			{
				if (window.scrollY < BX.pos(searchInput).top)
				{
					searchInput.value = searchInput.value;
					searchInput.blur();
					searchInput.focus();
				}
			}
		},

		_onAddPresetKeydown: function(event)
		{
			if (event.code === 'Enter')
			{
				this._onSaveButtonClick();
			}
		},

		_onDocumentKeydown: function(event)
		{
			if (event.code === 'Escape' || event.keyCode == 27)
			{
				if (this.getPopup().isShown())
				{
					this.closePopup();

					if (this.getParam('VALUE_REQUIRED_MODE'))
					{
						this.restoreRemovedPreset();
					}
				}
			}
		},

		getApi: function()
		{
			if (!(this.api instanceof BX.Filter.Api))
			{
				this.api = new BX.Filter.Api(this);
			}

			return this.api;
		},

		addSidebarItem: function(id, name, pinned)
		{
			var Presets = this.getPreset();
			var sidebarItem = BX.decl({
				block: 'sidebar-item',
				text: name,
				id: id,
				pinned: pinned,
				noEditPinTitle: this.getParam('MAIN_UI_FILTER__IS_SET_AS_DEFAULT_PRESET'),
				editNameTitle: this.getParam('MAIN_UI_FILTER__EDIT_PRESET_TITLE'),
				removeTitle: this.getParam('MAIN_UI_FILTER__REMOVE_PRESET'),
				editPinTitle: this.getParam('MAIN_UI_FILTER__SET_AS_DEFAULT_PRESET'),
				dragTitle: this.getParam('MAIN_UI_FILTER__DRAG_TITLE')
			});

			var presetsContainer = Presets.getContainer();
			var preset = Presets.getPresetNodeById(id);

			if (BX.type.isDomNode(preset))
			{
				BX.insertAfter(sidebarItem, preset);
				BX.remove(preset);
			}
			else
			{
				presetsContainer.insertBefore(sidebarItem, this.getPreset().getAddPresetField());
			}

			BX.bind(sidebarItem, 'click', BX.delegate(Presets._onPresetClick, this.getPreset()));
		},

		saveUserSettings: function(forAll)
		{
			var optionsParams = {'FILTER_ID': this.getParam('FILTER_ID'), 'GRID_ID': this.getParam('GRID_ID'), 'action': 'SET_FILTER_ARRAY'};
			var Presets = this.getPreset();
			var currentPresetId = Presets.getCurrentPresetId();
			var presetsSettings = {};

			this.params['PRESETS'] = BX.clone(this.editablePresets);
			presetsSettings.current_preset = currentPresetId;

			Presets.getPresets().forEach(function(current, index) {
				var presetId = Presets.getPresetId(current);

				if (presetId && presetId !== 'tmp_filter')
				{
					var presetData = Presets.getPreset(presetId);
					var presetRows = presetData.FIELDS.map(function(curr) { return curr.NAME; });

					presetData.TITLE = BX.util.htmlspecialchars(presetData.TITLE);
					presetData.SORT = index;
					Presets.updatePresetName(current, presetData.TITLE);

					presetsSettings[presetId] = {
						sort: index,
						name: presetData.TITLE,
						fields: this.preparePresetSettingsFields(presetData.FIELDS, presetRows)
					}
				}
			}, this);

			this.saveOptions(presetsSettings, optionsParams, null, forAll);
		},

		isForAll: function()
		{
			var checkbox = this.getForAllCheckbox();
			return (checkbox && checkbox.checked);
		},

		getForAllCheckbox: function()
		{
			if (!this.forAllCheckbox)
			{
				this.forAllCheckbox = BX.Filter.Utils.getByClass(this.getFilter(), this.settings.classForAllCheckbox);
			}

			return this.forAllCheckbox;
		},

		preparePresetSettingsFields: function(fields)
		{
			var result = {};
			var valuesKeys;

			(fields || []).forEach(function(current) {
				switch (current.TYPE)
				{
					case this.types.STRING : {
						result[current.NAME] = current.VALUE;
						break;
					}

					case this.types.SELECT : {
						result[current.NAME] = 'VALUE' in current.VALUE ? current.VALUE.VALUE : '';
						break;
					}

					case this.types.MULTI_SELECT : {
						if (BX.type.isArray(current.VALUE) && current.VALUE.length)
						{
							current.VALUE.forEach(function(curr, index) {
								result[current.NAME] = BX.type.isPlainObject(result[current.NAME]) ? result[current.NAME] : {};
								result[current.NAME][index] = curr.VALUE;
							}, this);
						}
						break;
					}

					case this.types.CHECKBOX : {
						if (BX.type.isArray(current.VALUE) && current.VALUE.length)
						{
							current.VALUE.forEach(function(curr, index) {
								result[current.NAME] = BX.type.isPlainObject(result[current.NAME]) ? result[current.NAME] : {};
								result[current.NAME][index] = curr.VALUE;
							}, this);
						}
						break;
					}

					case this.types.DATE : {
						if (BX.type.isPlainObject(current.VALUES))
						{
							valuesKeys = Object.keys(current.VALUES);
							result[current.NAME + '_datesel'] = current.SUB_TYPE.VALUE;
							valuesKeys.forEach(function(curr) {
								result[current.NAME + curr] = current.VALUES[curr];
							}, this);
						}
						break;
					}

					case this.types.NUMBER : {
						if (BX.type.isPlainObject(current.VALUES))
						{
							valuesKeys = Object.keys(current.VALUES);
							result[current.NAME + '_numsel'] = current.SUB_TYPE.VALUE;
							valuesKeys.forEach(function(curr) {
								result[current.NAME + curr] = current.VALUES[curr];
							}, this);
						}
						break;
					}

					case this.types.CUSTOM_ENTITY : {
						if (BX.type.isPlainObject(current.VALUES))
						{
							result[current.NAME] = current.VALUES._value;
							result[current.NAME + '_label'] = current.VALUES._label;
						}
						break;
					}

					default : {
						break;
					}
				}
			}, this);

			return result;
		},

		savePreset: function()
		{
			var presetId = 'filter_' + (+new Date());
			var presetName = this.getPreset().getAddPresetFieldInput().value;

			presetName = BX.util.htmlspecialchars(presetName);

			this.updatePreset(presetId, presetName, null, true);
			this.addSidebarItem(presetId, presetName);
			this.getPreset().applyPreset(presetId);
			this.getPreset().activatePreset(presetId);
			this.applyFilter();
		},

		updatePreset: function(presetId, presetName, reset, sort, beforeLoad, afterLoad)
		{
			var fields = this.getFilterFieldsValues();
			var sourceFields = this.getPreset().getFields().map(function(curr) { return BX.data(curr, 'name'); });
			var preset = this.getPreset().getCurrentPresetData();
			var params = {'FILTER_ID': this.getParam('FILTER_ID'), 'GRID_ID': this.getParam('GRID_ID'), 'action': 'SET_FILTER'};
			var rows, value, tmpPresetNode, tmpPresetInput, presets;
			var data = {};

			rows = Object.keys(fields);

			if (!reset)
			{
				data.apply_filter = 'Y';
			}
			else
			{
				data.clear_filter = 'Y';
			}

			data.save = 'Y';
			data.fields = fields;
			data.rows = sourceFields.join(',');

			data.preset_id = presetId || preset.ID;

			if (BX.type.isNotEmptyString(presetName))
			{
				data.name = BX.util.htmlspecialchars(presetName);
			}
			else
			{
				tmpPresetNode = this.getPreset().getPresetNodeById(data.preset_id);
				tmpPresetInput = this.getPreset().getPresetInput(tmpPresetNode);

				if (BX.type.isDomNode(tmpPresetInput) && BX.type.isNotEmptyString(tmpPresetInput.value))
				{
					data.name = tmpPresetInput.value;
				}
				else
				{
					data.name = preset.TITLE;
				}
			}

			if ((!('sort' in data) || !BX.type.isNumber(data.sort)) && sort)
			{
				presets = this.getParam('PRESETS');
				data.sort = presets.length + 2;
			}

			if (!reset)
			{
				rows.forEach(function(key) {
					if (BX.type.isArray(data.fields[key]))
					{
						value = data.fields[key].length ? {} : '';

						data.fields[key].forEach(function(val, index) {
							value[index] = val;
						}, this);

						if (value || BX.type.isNumber(value) || BX.type.isBoolean(value))
						{
							data.fields[key] = value;
						}
					}
				}, this);
			}

			if (data.preset_id === 'tmp_filter' || this.isAddPresetEnabled() || reset)
			{
				this.updateParams(data);
			}

			if (BX.type.isFunction(beforeLoad))
			{
				beforeLoad();
			}

			this.saveOptions(data, params, afterLoad);

		},

		saveFieldsSort: function()
		{
			var params = {'FILTER_ID': this.getParam('FILTER_ID'), 'GRID_ID': this.getParam('GRID_ID'), 'action': 'SET_FILTER'};
			var fields = this.getPreset().getFields();
			var data = {};

			data.preset_id = 'default_filter';

			if (BX.type.isArray(fields))
			{
				data.rows = fields.map(function(current) {
					return BX.data(current, 'name');
				});
				data.rows = data.rows.join(',');
			}

			this.updateParams(data);
			this.saveOptions(data, params);
		},

		updateParams: function(data)
		{
			var preset, presets;
			var fields = [];

			if (BX.type.isPlainObject(data) && 'preset_id' in data)
			{
				preset = this.getPreset().getPreset(data.preset_id);

				if (BX.type.isPlainObject(preset))
				{
					if ('name' in data && BX.type.isNotEmptyString(data.name))
					{
						preset.TITLE = data.name;
					}

					if ('rows' in data && !('fields' in data))
					{
						data.fields = {};

						data.rows.split(',').forEach(function(curr) {
							data.fields[curr] = '';
						});
					}

					if ('fields' in data)
					{
						preset.FIELDS = this.preparePresetFields(data.fields, data.rows);
					}
				}
				else
				{
					presets = this.getParam('PRESETS');
					preset = {
						ID: data.preset_id,
						TITLE: data.name,
						SORT: (presets.length + 2),
						FIELDS: this.preparePresetFields(data.fields, data.rows)
					};

					presets.push(preset);
				}
			}
		},

		preparePresetFields: function(dataFields, rows)
		{
			var fieldKeys, field;
			var fields = [];

			if (BX.type.isPlainObject(dataFields))
			{
				rows = BX.type.isNotEmptyString(rows) ? rows.split(',') : [];
				fieldKeys = rows.length ? rows : Object.keys(dataFields);
				fieldKeys.forEach(function(current) {
					current = current.replace('_datesel', '').replace('_numsel', '');
					field = BX.clone(this.getFieldByName(current));

					if (BX.type.isPlainObject(field))
					{
						if (field.TYPE === this.types.STRING)
						{
							field.VALUE = dataFields[current];
						}

						if (field.TYPE === this.types.MULTI_SELECT)
						{
							field.VALUE = this.prepareMultiSelectValue(dataFields[current], field.ITEMS);
						}

						if (field.TYPE === this.types.SELECT || field.TYPE === this.types.CHECKBOX)
						{
							field.VALUE = this.prepareSelectValue(dataFields[current], field.ITEMS);
						}

						if (field.TYPE === this.types.DATE)
						{
							field.SUB_TYPE = this.prepareSelectValue(dataFields[current + '_datesel'], field.SUB_TYPES);

							field.VALUES = {
								'_from': dataFields[current + '_from'],
								'_to': dataFields[current + '_to'],
								'_days': dataFields[current + '_days'],
								'_month': dataFields[current + '_days'],
								'_quarter': dataFields[current + '_quarter'],
								'_year': dataFields[current + '_year']
							};
						}

						if (field.TYPE === this.types.NUMBER)
						{
							field.SUB_TYPE = this.prepareSelectValue(dataFields[current + '_numsel'], field.SUB_TYPES);
							field.VALUES = {
								'_from': dataFields[current + '_from'],
								'_to': dataFields[current + '_to']
							};
						}

						if (field.TYPE === this.types.CUSTOM_ENTITY)
						{
							field.VALUES._label = dataFields[current + '_label'];
							field.VALUES._value = dataFields[current];
						}

						if (field.TYPE === this.types.CUSTOM)
						{
							field._VALUE = dataFields[current];
						}

						fields.push(field);
					}
				}, this);
			}

			return fields;
		},

		prepareSelectValue: function(value, items)
		{
			var result = {};
			var tmpResult;

			if (BX.type.isNotEmptyString(value) && BX.type.isArray(items))
			{
				tmpResult = this.prepareMultiSelectValue({0: value}, items);
				result = tmpResult.length > 0 ? tmpResult[0] : {};
			}
			else
			{
				result = items[0];
			}

			return result;
		},

		prepareMultiSelectValue: function(values, items)
		{
			var result = [];

			if (BX.type.isPlainObject(values) && BX.type.isArray(items))
			{
				var valuesKeys = Object.keys(values);
				var valuesValues = valuesKeys.map(function(curr) { return values[curr]; });

				result = items.filter(function(current) {
					return valuesValues.some(function(val) { return val === current.VALUE});
				}, this);
			}

			return result;
		},

		getFieldByName: function(name)
		{
			var fields = this.getParam('FIELDS');

			fields = fields.filter(function(current) {
				return current.NAME === name;
			}, this);

			return fields.length > 0 ? fields[0] : null;
		},

		saveOptions: function(postData, getData, callback, forAll)
		{
			var url = BX.util.add_url_param(this.getParam('SETTINGS_URL'), getData || {});

			if (forAll && ('action' in getData && getData.action === 'SET_FILTER_ARRAY'))
			{
				var action = {
					CONFIRM: true,
					CONFIRM_MESSAGE: this.getParam('MAIN_UI_FILTER__CONFIRM_MESSAGE_FOR_ALL'),
					CONFIRM_APPLY_BUTTON: this.getParam('MAIN_UI_FILTER__CONFIRM_APPLY_FOR_ALL'),
					CONFIRM_CANCEL_BUTTON: this.getParam('CONFIRM_CANCEL')
				};

				this.confirmDialog(
					action,
					BX.delegate(function() {
						url = BX.util.add_url_param(url, {'for_all': 'true'});
						this.disableEdit();
						this.disableAddPreset();
						save(url, this, true);
					}, this),
					BX.delegate(function() {
						BX.removeClass(this.getSaveForAllButton(), this.settings.classWaitButtonClass);
					}, this)
				);
			}
			else
			{
				save(url, this);
			}

			function save(url, ctx, forAll)
			{
				BX.ajax.post(url, postData, BX.delegate(function() {
					BX.removeClass(this.getFindButton(), this.settings.classWaitButtonClass);
					forAll && BX.removeClass(this.getSaveForAllButton(), this.settings.classWaitButtonClass);
					BX.type.isFunction(callback) && callback();
				}, ctx));
			}
		},

		prepareEvent: function(event)
		{
			var i, x;

			if (!('path' in event) || !event.path.length)
			{
				event.path = [event.target];
				i = 0;

				while ((x = event.path[i++].parentNode) != null)
				{
					event.path.push(x);
				}
			}

			return event;
		},

		restoreRemovedPreset: function()
		{
			if (this.getParam('VALUE_REQUIRED_MODE'))
			{
				var currentPreset = this.getParam('CURRENT_PRESET');
				if (BX.type.isPlainObject(currentPreset))
				{
					var currentPresetId = currentPreset.ID;
					var presetNode = this.getPreset().getPresetNodeById(currentPresetId);
					this.getPreset().applyPreset(currentPresetId);
					this.getPreset().activatePreset(presetNode);
				}
			}
		},

		hasScrollClick: function(event)
		{
			var x = 'screenX' in event ? event.screenX : 'x' in event ? event.x : 0;
			return x >= document.documentElement.offsetWidth;
		},

		_onDocumentClick: function(event)
		{
			var isFilterInsideClick, condition, isInsideFieldsPopupClick;
			var popup = this.getPopup();
			var fieldsPopup = this.getFieldsPopup();

			if (popup && popup.isShown())
			{
				event = this.prepareEvent(event);

				isFilterInsideClick = (event.path || []).some(function(current) {
					condition = false;

					if (BX.type.isDomNode(current))
					{
						condition = (
							BX.hasClass(current, this.settings.classFilterContainer) ||
							BX.hasClass(current, this.settings.classSearchContainer) ||
							BX.hasClass(current, this.settings.classDefaultPopup) ||
							BX.hasClass(current, this.settings.classPopupOverlay)
						);
					}

					return condition;
				}, this);

				if (!isFilterInsideClick && !this.hasScrollClick(event))
				{
					this.closePopup();

					if (this.getParam('VALUE_REQUIRED_MODE'))
					{
						this.restoreRemovedPreset();
					}
				}
			}

			if (fieldsPopup && fieldsPopup.isShown())
			{
				isInsideFieldsPopupClick = (event.path || []).some(function(current) {
					condition = false;

					if (BX.type.isDomNode(current))
					{
						condition = (
							BX.hasClass(current, this.settings.classFieldListItem) ||
							BX.hasClass(current, this.settings.classPopupFieldList)
						);
					}

					return condition;
				}, this);

				if (!isInsideFieldsPopupClick && !BX.hasClass(event.target, this.settings.classAddField) && !this.hasScrollClick(event))
				{
					this.closeFieldListPopup();
				}
			}
		},

		_onAddFieldClick: function(event)
		{
			var popup = this.getFieldsPopup();
			event.stopPropagation();
			event.preventDefault();

			if (popup && !popup.isShown())
			{
				this.showFieldsPopup();
				this.syncFields();
			}
			else
			{
				this.closeFieldListPopup();
			}
		},

		syncFields: function()
		{
			var fields = this.getPreset().getFields();
			var items = this.getFieldsPopupItems();
			var currentId, isNeedCheck;

			if (BX.type.isArray(items) && items.length)
			{
				items.forEach(function(current) {
					currentId = BX.data(current, 'name').replace('_datesel', '').replace('_numsel', '');
					isNeedCheck = fields.some(function(field) {
						return BX.data(field, 'name') === currentId;
					});
					if (isNeedCheck)
					{
						BX.addClass(current, this.settings.classMenuItemChecked);
					}
					else
					{
						BX.removeClass(current, this.settings.classMenuItemChecked);
					}
				}, this);
			}
		},

		getFieldsPopupItems: function()
		{
			var popup;

			if (!BX.type.isArray(this.fieldsPopupItems))
			{
				popup = this.getFieldsPopup();

				if ('contentContainer' in popup && BX.type.isDomNode(popup.contentContainer))
				{
					this.fieldsPopupItems = BX.Filter.Utils.getByClass(popup.contentContainer, this.settings.classMenuItem, true);
				}
			}

			return this.fieldsPopupItems;
		},

		getFieldListPopupItems: function()
		{
			var itemText, itemContainer;
			var data = this.getParam('FIELDS');
			var itemsContainer = BX.create('div', {props: {className: this.settings.classPopupFieldList}});

			if (BX.type.isArray(data) && data.length)
			{
				if (data.length < 6)
				{
					BX.addClass(itemsContainer, this.settings.classPopupFieldList1Column);
				}

				if (data.length > 6 && data.length < 12)
				{
					BX.addClass(itemsContainer, this.settings.classPopupFieldList2Column);
				}

				if (data.length > 12)
				{
					BX.addClass(itemsContainer, this.settings.classPopupFieldList3Column);
				}

				data.forEach(function(itemData) {
					itemContainer = BX.create('div', {
						props: {
							className: [this.settings.classMenuItem, this.settings.classFieldListItem].join(' ')
						},
						attrs: {
							'data-id': itemData.ID,
							'data-name': itemData.NAME,
							'data-item': JSON.stringify(itemData)
						}
					});

					itemText = BX.create('div', {props: {
						className: this.settings.classMenuMultiItemText
					}, text: itemData.LABEL});
					BX.append(itemText, itemContainer);
					BX.append(itemContainer, itemsContainer);
					BX.bind(itemContainer, 'click', BX.delegate(this._clickOnFieldListItem, this));
				}, this);
			}

			return itemsContainer;
		},

		_clickOnFieldListItem: function(event)
		{
			var target = event.target;
			var data;

			if (!BX.hasClass(target, this.settings.classFieldListItem))
			{
				target = BX.findParent(target, {class: this.settings.classFieldListItem}, true, false);
			}

			if (BX.type.isDomNode(target))
			{
				try {
					data = JSON.parse(BX.data(target, 'item'));
				} catch (err) {}

				if (BX.hasClass(target, this.settings.classMenuItemChecked))
				{
					BX.removeClass(target, this.settings.classMenuItemChecked);
					this.getPreset().removeField(data);
				}
				else
				{
					if (BX.type.isPlainObject(data))
					{
						this.getPreset().addField(data);
						BX.addClass(target, this.settings.classMenuItemChecked);
					}
				}

				this.syncFields();
			}
		},

		showFieldsPopup: function()
		{
			var popup = this.getFieldsPopup();
			this.adjustFieldListPopupPosition();
			popup.show();
		},

		closeFieldListPopup: function()
		{
			var popup = this.getFieldsPopup();
			popup.close();
		},

		adjustFieldListPopupPosition: function()
		{
			var popup = this.getFieldsPopup();
			var pos = BX.pos(this.getAddField());
			pos.forceBindPosition = true;
			popup.adjustPosition(pos);
		},

		getFieldsPopup: function()
		{
			var addFiledButton = this.getAddField();

			if (!this.fieldsPopup)
			{
				this.fieldsPopup = new BX.PopupWindow(
					this.getParam('FILTER_ID') + '_fields_popup',
					addFiledButton,
					{
						autoHide : false,
						offsetTop : 4,
						offsetLeft : 0,
						lightShadow : true,
						closeIcon : false,
						closeByEsc : false,
						noAllPaddings: true,
						zIndex: 13
					}
				);

				this.fieldsPopup.setContent(this.getFieldListPopupItems());
			}

			return this.fieldsPopup;
		},

		_onAddPresetClick: function()
		{
			this.enableAddPreset();
		},

		_onSaveButtonClick: function()
		{
			BX.addClass(this.getFindButton(), this.settings.classWaitButtonClass);

			if (this.isAddPresetEnabled())
			{
				this.savePreset();
				this.disableAddPreset();
			}

			if (this.isEditEnabled())
			{
				this.getPreset().updateEditablePreset(this.getPreset().getCurrentPresetId());
				this.saveUserSettings();
				this.disableEdit();

				if (this.isAddPresetEnabled())
				{
					this.savePreset();
					this.disableAddPreset();
				}
			}

			this.getPreset().getDefaultPresets().forEach(function(current) {
				current.style = null;
			}, this);
		},

		_onCancelButtonClick: function()
		{
			this.disableAddPreset();
			this.getPreset().getAddPresetFieldInput().value = '';
			this.disableEdit();

			this.getPreset().getDefaultPresets().forEach(function(current) {
				current.style = null;
			}, this);
		},

		_onSaveForAllButtonClick: function()
		{
			BX.addClass(this.getSaveForAllButton(), this.settings.classWaitButtonClass);
			this.saveUserSettings(true);
		},

		_onGridReady: function(grid)
		{
			if (!this.grid && grid.getContainerId() === this.getParam('GRID_ID'))
			{
				this.grid = grid;
			}
		},

		_onFilterClick: function(event)
		{
			var i, l, squares, controls, field, control;
			var Fields = this.getFields();
			var Presets = this.getPreset();
			var target = event.target;

			if (Fields.isFieldDelete(target))
			{
				field = Fields.getField(target);
				Presets.removeField(field);
			}

			if (BX.hasClass(target, this.settings.classValueDelete) ||
				BX.hasClass(target.parentNode, this.settings.classValueDelete))
			{
				field = Fields.getField(target);

				if (BX.type.isDomNode(field))
				{
					controls = BX.Filter.Utils.getByClass(field, this.settings.classControl, true);
					squares = BX.Filter.Utils.getByClass(field, this.settings.classSquare, true);

					if (BX.type.isArray(squares))
					{
						squares.forEach(function(current) {
							BX.remove(current);
						});
					}

					if(BX.type.isArray(controls))
					{
						for(i = 0, l = controls.length; i < l; i++)
						{
							control = controls[i];
							if (BX.type.isDomNode(control)
									&& control.tagName === 'INPUT'
									&& (control.type === 'text' || control.type === 'hidden')
							)
							{
								control.value = '';
							}
						}
					}
				}
			}
		},

		getButtonsContainer: function()
		{
			return BX.Filter.Utils.getByClass(this.getFilter(), this.settings.classButtonsContainer);
		},

		getSaveButton: function()
		{
			return BX.Filter.Utils.getByClass(this.getFilter(), this.settings.classSaveButton);
		},

		getCancelButton: function()
		{
			return BX.Filter.Utils.getByClass(this.getFilter(), this.settings.classCancelButton);
		},

		getFindButton: function()
		{
			return BX.Filter.Utils.getByClass(this.getFilter(), this.settings.classFindButton);
		},

		getResetButton: function()
		{
			return BX.Filter.Utils.getByClass(this.getFilter(), this.settings.classResetButton);
		},

		getAddPresetButton: function()
		{
			return BX.Filter.Utils.getByClass(this.getFilter(), this.settings.classAddPresetButton);
		},

		isAddPresetEnabled: function()
		{
			return this.isAddPresetModeState;
		},

		enableAddPreset: function()
		{
			var Preset = this.getPreset();
			var addPresetField = Preset.getAddPresetField();
			var addPresetFieldInput = Preset.getAddPresetFieldInput();
			var buttonsContainer = this.getButtonsContainer();

			BX.show(addPresetField);
			BX.show(buttonsContainer);
			BX.hide(this.getPresetButtonsContainer());
			this.getSaveForAllButton() && BX.hide(this.getSaveForAllButton());

			if (BX.type.isDomNode(addPresetFieldInput))
			{
				addPresetFieldInput.focus();
			}

			BX.addClass(this.getSidebarControlsContainer(), this.settings.classDisabled);

			this.isAddPresetModeState = true;
		},

		disableAddPreset: function()
		{
			var Preset = this.getPreset();
			var addPresetField = Preset.getAddPresetField();
			var buttonsContainer = this.getButtonsContainer();
			var presetId = Preset.getCurrentPresetId();

			BX.hide(addPresetField);
			BX.hide(buttonsContainer);
			BX.show(this.getPresetButtonsContainer());
			this.getSaveForAllButton() && BX.show(this.getSaveForAllButton());

			if (BX.type.isNotEmptyString(presetId))
			{
				Preset.applyPreset(presetId);
			}

			Preset.getAddPresetFieldInput().value = '';

			BX.removeClass(this.getSidebarControlsContainer(), this.settings.classDisabled);

			this.isAddPresetModeState = false;
		},

		getControls: function()
		{
			var container = this.getFieldListContainer();
			var controls = null;

			if (BX.type.isDomNode(container))
			{
				controls = BX.Filter.Utils.getByClass(container, this.settings.classControl, true);
			}

			return controls;
		},

		getFilterFields: function()
		{
			var container = this.getFieldListContainer();
			var fields = [];
			var groups = [];

			if (BX.type.isDomNode(container))
			{
				fields = BX.Filter.Utils.getByClass(container, this.settings.classField, true);
				groups = BX.Filter.Utils.getByClass(container, this.settings.classFieldGroup, true);

				if (!BX.type.isArray(fields))
				{
					fields = [];
				}

				if (BX.type.isArray(groups))
				{
					groups.forEach(function(current) {
						fields.push(current);
					});
				}
			}

			return fields;
		},

		getFilterFieldsValues: function()
		{
			var fields = this.getPreset().getFields();
			var Search = this.getSearch();
			var values = {};
			var type, name;

			values['FIND'] = Search.getInput().value;

			if (BX.type.isArray(fields) && fields.length)
			{
				fields.forEach(function(current) {
					type = BX.data(current, 'type');
					name = BX.data(current, 'name');

					switch (type) {
						case this.types.STRING : {
							this.prepareControlStringValue(values, current);
							break;
						}

						case this.types.NUMBER : {
							this.prepareControlNumberValue(values, name, current);
							break;
						}

						case this.types.DATE : {
							this.prepareControlDateValue(values, name, current);
							break;
						}

						case this.types.SELECT : {
							this.prepareControlSelectValue(values, name, current);
							break;
						}

						case this.types.MULTI_SELECT : {
							this.prepareControlMultiselectValue(values, name, current);
							break;
						}

						case this.types.CUSTOM : {
							this.prepareControlCustomValue(values, name, current);
							break;
						}

						case this.types.CUSTOM_ENTITY : {
							this.prepareControlCustomEntityValue(values, name, current);
							break;
						}

						default : {
							break;
						}
					}
				}, this);
			}

			return values;
		},

		prepareControlCustomEntityValue: function(values, name, field)
		{
			var square = BX.Filter.Utils.getByClass(field, this.settings.classSquare);
			var squareValue;

			values[name] = '';
			values[name + '_label'] = '';

			if (BX.type.isDomNode(square))
			{
				try {
					squareValue = JSON.parse(BX.data(square, 'item'));

					if (BX.type.isPlainObject(squareValue))
					{
						values[name] = squareValue._value;
						values[name + '_label'] = squareValue._label;
					}
				} catch (err) {}
			}
		},

		prepareControlCustomValue: function(values, name, field)
		{
			var stringFields = BX.Filter.Utils.getByTag(field, 'input', true);

			values[name] = '';

			if (BX.type.isArray(stringFields))
			{
				stringFields.forEach(function(current) {
					if (BX.type.isNotEmptyString(current.name))
					{
						values[current.name] = current.value;
					}
				});
			}
		},

		prepareControlMultiselectValue: function(values, name, field)
		{
			var select = BX.Filter.Utils.getByClass(field, this.settings.classMultiSelect);
			var value = JSON.parse(BX.data(select, 'value'));

			values[name] = '';

			if (BX.type.isArray(value) && value.length)
			{
				values[name] = {};
				value.forEach(function(current, index) {
					values[name][index] = current.VALUE;
				});
			}
		},

		prepareControlSelectValue: function(values, name, field)
		{
			var select = BX.Filter.Utils.getByClass(field, this.settings.classSelect);
			var value = JSON.parse(BX.data(select, 'value'));

			values[name] = value.VALUE;
		},

		prepareControlDateValue: function(values, name, field)
		{
			var select = BX.Filter.Utils.getByClass(field, this.settings.classSelect);
			var selectName = name + this.settings.datePostfix;
			var fromName = name + this.settings.fromPostfix;
			var toName = name + this.settings.toPostfix;
			var daysName = name + this.settings.daysPostfix;
			var monthName = name + this.settings.monthPostfix;
			var quarterName = name + this.settings.quarterPostfix;
			var yearName = name + this.settings.yearPostfix;
			var selectValue, stringFields, controls, controlName;

			values[selectName] = '';
			values[fromName] = '';
			values[toName] = '';
			values[daysName] = '';
			values[monthName] = '';
			values[quarterName] = '';
			values[yearName] = '';

			selectValue = JSON.parse(BX.data(select, 'value'));

			values[selectName] = selectValue.VALUE;

			switch (selectValue.VALUE) {
				case this.dateTypes.EXACT : {
					stringFields = BX.Filter.Utils.getByClass(field, this.settings.classDateInput);
					values[fromName] = stringFields.value;
					values[toName] = stringFields.value;
					break;
				}

				case this.dateTypes.QUARTER : {
					controls = BX.Filter.Utils.getByClass(field, this.settings.classControl, true);

					if (BX.type.isArray(controls))
					{
						controls.forEach(function(current) {
							controlName = BX.data(current, 'name');

							if (controlName && controlName.indexOf('_quarter') !== -1)
							{
								values[quarterName] = JSON.parse(BX.data(current, 'value')).VALUE;
							}

							if (controlName && controlName.indexOf('_year') !== -1)
							{
								values[yearName] = JSON.parse(BX.data(current, 'value')).VALUE;
							}
						}, this);
					}
					break;
				}

				case this.dateTypes.YEAR : {
					controls = BX.Filter.Utils.getByClass(field, this.settings.classControl, true);

					if (BX.type.isArray(controls))
					{
						controls.forEach(function(current) {
							controlName = BX.data(current, 'name');

							if (controlName && controlName.indexOf('_year') !== -1)
							{
								values[yearName] = JSON.parse(BX.data(current, 'value')).VALUE;
							}
						}, this);
					}
					break;
				}

				case this.dateTypes.MONTH : {
					controls = BX.Filter.Utils.getByClass(field, this.settings.classControl, true);

					if (BX.type.isArray(controls))
					{
						controls.forEach(function(current) {
							controlName = BX.data(current, 'name');

							if (controlName && controlName.indexOf('_month') !== -1)
							{
								values[monthName] = JSON.parse(BX.data(current, 'value')).VALUE;
							}

							if (controlName && controlName.indexOf('_year') !== -1)
							{
								values[yearName] = JSON.parse(BX.data(current, 'value')).VALUE;
							}
						}, this);
					}
					break;
				}

				case this.dateTypes.RANGE : {
					stringFields = BX.Filter.Utils.getByClass(field, this.settings.classDateInput, true);
					stringFields.forEach(function(current) {
						if (current.name === fromName)
						{
							values[fromName] = current.value;
						}
						else if (current.name === toName)
						{
							values[toName] = current.value;
						}
					}, this);
					break;
				}

				default : {
					break;
				}
			}
		},

		prepareControlNumberValue: function(values, name, field)
		{
			var stringFields = BX.Filter.Utils.getByClass(field, this.settings.classNumberInput, true);
			var select = BX.Filter.Utils.getByClass(field, this.settings.classSelect);
			var selectName = name + this.settings.numberPostfix;
			var fromName = name + this.settings.fromPostfix;
			var toName = name + this.settings.toPostfix;
			var selectValue;

			values[fromName] = '';
			values[toName] = '';

			selectValue = JSON.parse(BX.data(select, 'value'));
			values[selectName] = selectValue.VALUE;

			stringFields.forEach(function(current) {
				if (current.name.indexOf(this.settings.fromPostfix) !== -1)
				{
					values[fromName] = current.value || '';

					if (values[selectName] === 'exact')
					{
						values[toName] = current.value || '';
					}
				}
				else if (current.name.indexOf(this.settings.toPostfix) !== -1)
				{
					values[toName] = current.value || '';
				}
			}, this);
		},

		prepareControlStringValue: function(values, field)
		{
			var control = BX.Filter.Utils.getByClass(field, this.settings.classStringInput);
			var name;

			if (BX.type.isDomNode(control))
			{
				name = control.name;
				values[name] = control.value;
			}
		},


		applyFilter: function(clear, applyPreset)
		{
			var values = this.getFilterFieldsValues();
			var filterId = this.getParam('FILTER_ID');
			var presetId = this.getPreset().getCurrentPresetId();
			var actionKey = clear ? 'clear_filter' : 'apply_filter';
			var valuesKeys;

			if ((!this.isEditEnabled() && !this.isAddPresetEnabled() && !applyPreset) ||
				(presetId === 'default_filter' && !clear))
			{
				presetId = 'tmp_filter';
			}

			values[actionKey] = 'Y';
			values.save = 'Y';
			values.preset_id = presetId;
			values.ajax_request = 'Y';

			if (this.grid)
			{
				this.grid.tableFade();
			}

			this.updatePreset(presetId, null, clear, null,
				BX.delegate(function() {
					this.getSearch().updatePreset(this.getPreset().getPreset(presetId));
				}, this),
				BX.delegate(function() {
					valuesKeys = Object.keys(values);
					valuesKeys.forEach(function(key) {
						if (key.indexOf('_label') !== -1)
						{
							delete values[key];
						}
					});

					values["FIND"] = BX.util.htmlspecialchars(this.getSearch().getInput().value);

					if (this.grid)
					{
						this.grid.reloadTable('POST', {apply_filter: 'Y', clear_nav: 'Y'});
					}

					BX.onCustomEvent(window, 'BX.Main.Filter:apply', [filterId, values, this]);
				}, this)
			);
		},

		getAddField: function()
		{
			return BX.Filter.Utils.getByClass(this.getFilter(), this.settings.classAddField);
		},

		getFieldListContainer: function()
		{
			return BX.Filter.Utils.getByClass(this.getFilter(), this.settings.classFileldControlList);
		},

		getFields: function()
		{
			if (!(this.fields instanceof BX.Filter.Fields))
			{
				this.fields = new BX.Filter.Fields(this);
			}

			return this.fields;
		},

		getPreset: function()
		{
			if (!(this.presets instanceof BX.Filter.Presets))
			{
				this.presets = new BX.Filter.Presets(this);
			}

			return this.presets;
		},

		resetControlData: function(controlData)
		{
			if (BX.type.isPlainObject(controlData))
			{
				switch (controlData.TYPE)
				{
					case this.types.MULTI_SELECT : {
						controlData.VALUE = [];
						break;
					}

					case this.types.SELECT : {
						controlData.VALUE = controlData.ITEMS[0];
						break;
					}

					case this.types.DATE : {
						controlData.SUB_TYPE = controlData.SUB_TYPES[0];
						controlData.VALUES = {
							'_from': '',
							'_to': '',
							'_days': '',
							'_quarter': '',
							'_year': ''
						};
						break;
					}

					case this.types.NUMBER : {
						controlData.SUB_TYPE = controlData.SUB_TYPES[0];
						controlData.VALUES = {
							'_from': '',
							'_to': ''
						};
						break;
					}

					case this.types.CUSTOM_ENTITY : {
						controlData.VALUES = {
							'_label': '',
							'_value': ''
						};
						break;
					}

					case this.types.CUSTOM : {
						controlData._VALUE = '';
						break;
					}

					default : {
						controlData.VALUE = '';
					}
				}
			}

			return controlData;
		},

		clearControl: function(name)
		{
			var control = this.getPreset().getField({NAME: name});
			var controlData, newControl;

			if (BX.type.isDomNode(control))
			{
				controlData = this.getFieldByName(name);
				controlData = this.resetControlData(controlData);

				newControl = this.getPreset().createControl(controlData);
				BX.insertAfter(newControl, control);
				BX.remove(control);
			}
		},

		_onSearchContainerClick: function(event)
		{
			var search = this.getSearch();
			var preset, searchValue, square, isControl, squareData, squares;

			if (!BX.hasClass(event.target, this.settings.classSquareDelete) &&
				!BX.hasClass(event.target, this.settings.classClearSearchValueButton) &&
				!BX.hasClass(event.target, this.settings.classSearchButton))
			{
				if (!this.getPopup().isShown())
				{
					this.showPopup();
				}
				else
				{
					this.closePopup();

					if (this.getParam('VALUE_REQUIRED_MODE'))
					{
						this.restoreRemovedPreset();
					}
				}
			}
			else
			{
				if (BX.hasClass(event.target, this.settings.classSquareDelete))
				{
					square = BX.findParent(event.target, {className: this.settings.classSquare}, true, false);
					isControl = false;

					try {
						squareData = JSON.parse(BX.data(square, 'item'));
						isControl = squareData.type === 'control' || (BX.type.isArray(squareData) && squareData.length);
					} catch (err) {}

					if (!isControl)
					{
						if (!this.getParam('VALUE_REQUIRED_MODE'))
						{
							this.resetFilter(true);
						}
						else
						{
							if (square)
							{
								BX.remove(square);
								this.getPreset().deactivateAllPresets();
							}
						}
					}
					else
					{
						if (BX.type.isArray(squareData) && squareData.length)
						{
							squareData.forEach(function(item) {
								this.clearControl(item.name);
							}, this);
						}
						else
						{
							this.clearControl(squareData.name);
						}

						BX.remove(square);
						squares = BX.Filter.Utils.getByClass(this.getSearch().getContainer(), this.settings.classSquare, true);

						if (!this.getParam('VALUE_REQUIRED_MODE'))
						{
							if (!squares.length)
							{
								this.resetFilter(true);
							}
							else
							{
								this.applyFilter();
							}
						}
					}

					search.getInput().focus();

					squares = BX.Filter.Utils.getByClass(this.getSearch().getContainer(), this.settings.classSquare, true);

					if (!squares.length && !search.getInput().value.length)
					{
						search.hideClearButton();

						if (this.getParam("DISABLE_SEARCH"))
						{
							this.getSearch().setInputPlaceholder(this.getParam('MAIN_UI_FILTER__PLACEHOLDER'));
						}
						else
						{
							this.getSearch().setInputPlaceholder(this.getParam('MAIN_UI_FILTER__PLACEHOLDER_DEFAULT'));
						}
					}

					if (this.getPopup().isShown() && !this.getParam('VALUE_REQUIRED_MODE'))
					{
						this.closePopup();
					}

					if (this.getParam('VALUE_REQUIRED_MODE'))
					{
						this.showPopup();
					}
				}

				if (BX.hasClass(event.target, this.settings.classClearSearchValueButton))
				{
					preset = search.getPreset();
					searchValue = search.getInput().value;

					search.clearForm();
					search.getInput().focus();
					this.getPreset().deactivateAllPresets();
					this.getPreset().resetPreset();

					if (!this.getParam('VALUE_REQUIRED_MODE'))
					{
						this.resetFilter();
					}
					else
					{
						var squares = BX.Filter.Utils.getByClass(this.getSearch().getContainer(), this.settings.classSquare, true);

						if (BX.type.isArray(squares))
						{
							squares.forEach(BX.remove);
						}
					}

					if (this.getPopup().isShown() && !this.getParam('VALUE_REQUIRED_MODE'))
					{
						this.closePopup();
					}

					if (this.getParam('VALUE_REQUIRED_MODE'))
					{
						this.showPopup();
					}

					if (this.getParam("DISABLE_SEARCH"))
					{
						this.getSearch().setInputPlaceholder(this.getParam('MAIN_UI_FILTER__PLACEHOLDER'));
					}
					else
					{
						this.getSearch().setInputPlaceholder(this.getParam('MAIN_UI_FILTER__PLACEHOLDER_DEFAULT'));
					}

					search.hideClearButton();
				}

				if (BX.hasClass(event.target, this.settings.classSearchButton))
				{
					preset = search.getPreset();
					searchValue = search.getInput().value;

					if (!preset || !searchValue)
					{
						search.getInput().focus();
					}

					if (this.getPopup().isShown())
					{
						this.closePopup();
					}
				}
			}

		},

		getTemplate: function()
		{
			return BX.html(BX(this.settings.generalTemplateId));
		},

		closePopup: function()
		{
			var popup = this.getPopup();
			var popupContainer = popup.popupContainer;
			var closeDelay;

			if (!BX.hasClass(document.documentElement, 'bx-ie'))
			{
				BX.removeClass(popupContainer, this.settings.classAnimationShow);
				BX.addClass(popupContainer, this.settings.classAnimationClose);

				closeDelay = parseFloat(BX.style(popupContainer, 'animation-duration'));

				if (BX.type.isNumber(closeDelay))
				{
					closeDelay = closeDelay * 1000;
				}

				setTimeout(function() {
					popup.close();
				}, closeDelay);
			}
			else
			{
				popup.close();
			}

			this.closeFieldListPopup();
			this.adjustFocus();
		},

		showPopup: function()
		{
			var popup = this.getPopup();
			var popupContainer;

			if (!popup.isShown())
			{
				popup.show();

				if (!BX.hasClass(document.documentElement, 'bx-ie'))
				{
					popupContainer = popup.popupContainer;
					BX.removeClass(popupContainer, this.settings.classAnimationClose);
					BX.addClass(popupContainer, this.settings.classAnimationShow);
				}
			}
		},

		getSaveForAllButton: function()
		{
			if (!this.saveForAllButton)
			{
				this.saveForAllButton = BX.Filter.Utils.getByClass(this.getFilter(), this.settings.classForAllButton);
			}

			return this.saveForAllButton;
		},

		getPopup: function()
		{
			if (!(this.popup instanceof BX.PopupWindow))
			{
				this.popup =  new BX.PopupWindow(
					this.getParam('FILTER_ID') + this.settings.searchContainerPostfix,
					this.getSearch().getContainer(),
					{
						autoHide : false,
						offsetTop : 4,
						offsetLeft : 0,
						lightShadow : true,
						closeIcon : false,
						closeByEsc : false,
						noAllPaddings: true,
						zIndex: 12
					}
				);

				this.popup.setContent(this.getTemplate());
				BX.bind(this.getFieldListContainer(), 'keydown', BX.delegate(this._onFieldsContainerKeydown, this));
				BX.bind(this.getFilter(), 'click', BX.delegate(this._onFilterClick, this));
				BX.bind(this.getAddPresetButton(), 'click', BX.delegate(this._onAddPresetClick, this));
				BX.bind(this.getPreset().getAddPresetFieldInput(), 'keydown', BX.delegate(this._onAddPresetKeydown, this));
				BX.bind(this.getPreset().getContainer(), 'keydown', BX.delegate(this._onPresetInputKeydown, this));
				BX.bind(this.getSaveButton(), 'click', BX.delegate(this._onSaveButtonClick, this));
				if (this.getSaveForAllButton())
				{
					BX.bind(this.getSaveForAllButton(), 'click', BX.delegate(this._onSaveForAllButtonClick, this));
				}
				BX.bind(this.getCancelButton(), 'click', BX.delegate(this._onCancelButtonClick, this));
				BX.bind(this.getFindButton(), 'click', BX.delegate(this._onFindButtonClick, this));
				BX.bind(this.getResetButton(), 'click', BX.delegate(this._onResetButtonClick, this));
				BX.bind(this.getAddField(), 'click', BX.delegate(this._onAddFieldClick, this));
				BX.bind(this.getEditButton(), 'click', BX.delegate(this._onEditButtonClick, this));
				BX.bind(this.getRestoreButton(), 'click', BX.delegate(this._onRestoreButtonClick, this));
				BX.bind(this.getRestoreFieldsButton(), 'click', BX.delegate(this._onRestoreFieldsButtonClick, this));
				this.getPreset().showCurrentPresetFields();
				this.getPreset().bindOnPresetClick();
			}

			return this.popup;
		},

		_onRestoreFieldsButtonClick: function()
		{
			this.restoreDefaultFields();
		},

		restoreDefaultFields: function()
		{
			var defaultPreset = this.getPreset().getPreset('default_filter', true);
			var presets = this.getParam('PRESETS');
			var currentPresetId = this.getPreset().getCurrentPresetId();
			var params = {'FILTER_ID': this.getParam('FILTER_ID'), 'GRID_ID': this.getParam('GRID_ID'), 'action': 'SET_FILTER'};
			var fields = defaultPreset.FIELDS.map(function(curr) { return curr.NAME; });
			var rows = fields.join(',');

			presets.forEach(function(current, index) {
				if (current.ID === 'default_filter')
				{
					presets[index] = BX.clone(defaultPreset);
				}
			}, this);

			if (BX.type.isArray(this.editablePresets))
			{
				this.editablePresets.forEach(function(current, index) {
					if (current.ID === 'default_filter')
					{
						this.editablePresets[index] = BX.clone(defaultPreset);
					}
				}, this);
			}

			this.getPreset().applyPreset(currentPresetId);
			this.updatePreset(currentPresetId);
			this.saveOptions({preset_id: "default_filter", rows: rows, save: "Y", apply_filter: "N"}, params);
		},

		getRestoreFieldsButton: function()
		{
			if (!this.restoreFieldsButton)
			{
				this.restoreFieldsButton = BX.Filter.Utils.getByClass(this.getFilter(), this.settings.classRestoreFieldsButton);
			}

			return this.restoreFieldsButton;
		},

		restoreFilter: function()
		{
			var defaultPresets = this.getParam('DEFAULT_PRESETS');
			var allPresets = this.getParam('PRESETS');
			var isReplace = false;
			var replaceIndex, applyPresetId, presetNode;

			if (BX.type.isArray(defaultPresets))
			{
				defaultPresets.forEach(function(defPreset) {
					isReplace = allPresets.some(function(current, index) {
						if (current.ID === defPreset.ID)
						{
							replaceIndex = index;
							return true;
						}
					});

					if (isReplace)
					{
						allPresets[replaceIndex] = BX.clone(defPreset);
					}
					else
					{
						allPresets.push(BX.clone(defPreset));
					}

					if (defPreset.ID !== 'default_filter')
					{
						this.addSidebarItem(defPreset.ID, defPreset.TITLE, defPreset.PINNED);

						if (defPreset.PINNED)
						{
							applyPresetId = defPreset.ID;
						}
					}
				}, this);
			}

			this.saveRestoreFilter();
			this.disableAddPreset();
			this.disableEdit();

			if (!applyPresetId)
			{
				applyPresetId = "default_filter";
			}

			presetNode = this.getPreset().getPresetNodeById(applyPresetId);

			if (presetNode)
			{
				BX.fireEvent(presetNode, 'click');
			}
		},

		saveRestoreFilter: function()
		{
			var params = {'FILTER_ID': this.getParam('FILTER_ID'), 'GRID_ID': this.getParam('GRID_ID'), 'action': 'RESTORE_FILTER'};
			var presets = this.getParam('PRESETS');
			var data = {};
			var rows;

			if (BX.type.isArray(presets))
			{
				presets.forEach(function(current, index) {
					rows = current.FIELDS.map(function(field) {
						return field.NAME;
					});
					rows = rows.join(',');
					data[current.ID] = {
						name: current.TITLE || null,
						sort: index,
						preset_id: current.ID,
						fields:  this.prepareFields(current.FIELDS),
						rows: rows
					};
				}, this);

				this.saveOptions(data, params);
			}
		},

		prepareFields: function(fields)
		{
			var result = {};
			var valuesKeys;

			if (BX.type.isArray(fields))
			{
				fields.forEach(function(current) {
					if (current.TYPE === this.types.SELECT)
					{
						result[current.NAME] = 'VALUE' in current.VALUE ? current.VALUE.VALUE : '';
					}

					if (current.TYPE === this.types.MULTI_SELECT)
					{
						current.VALUE.forEach(function(val, i) {
							result[current.NAME] = result[current.NAME] || {};
							result[current.NAME][i] = val.VALUE;
						});

						result[current.NAME] = result[current.NAME] || '';
					}

					if (current.TYPE === this.types.DATE ||
						current.TYPE === this.types.NUMBER)
					{
						valuesKeys = Object.keys(current.VALUES);

						valuesKeys.forEach(function(key) {
							result[current.NAME + key] = current.VALUES[key];
						});

						if (current.TYPE === this.types.DATE)
						{
							result[current.NAME + '_datesel'] = 'VALUE' in current.SUB_TYPE ?
								current.SUB_TYPE.VALUE : current.SUB_TYPES[0].VALUE;
						}

						if (current.TYPE === this.types.NUMBER)
						{
							result[current.NAME + '_numsel'] = 'VALUE' in current.SUB_TYPE ?
								current.SUB_TYPE.VALUE : current.SUB_TYPES[0].VALUE;
						}
					}

					if (current.TYPE === this.types.CUSTOM_ENTITY)
					{
						result[current.NAME + '_label'] = current.VALUES._label;
						result[current.NAME + '_value'] = current.VALUES._value;
					}
				}, this);
			}

			return result;
		},

		getRestoreButton: function()
		{
			if (!BX.type.isDomNode(this.restoreButton))
			{
				this.restoreButton = BX.Filter.Utils.getByClass(this.getFilter(), this.settings.classRestoreButton);
			}

			return this.restoreButton;
		},

		_onPresetInputKeydown: function(event)
		{
			if (event.code === 'Enter' && event.target.tagName === 'INPUT')
			{
				BX.fireEvent(this.getSaveButton(), 'click');
			}
		},

		_onFieldsContainerKeydown: function(event)
		{
			if (event.code === 'Enter' && event.target.tagName === 'INPUT')
			{
				this.applyFilter();
				this.closePopup();
			}
		},

		_onFindButtonClick: function()
		{
			//var currentPresetId = this.getPreset().getCurrentPresetId();
			//
			//if (currentPresetId !== 'tmp_filter' && !this.getPreset().isPresetValuesModified(currentPresetId))
			//{
			//	var preset = this.getPreset().getPreset(currentPresetId);
			//	var additional = this.getPreset().getAdditionalValues(currentPresetId);
			//	var rows = this.getPreset().getFields().map(function(current) { return BX.data(current, 'name'); });
			//	preset.ADDITIONAL = this.preparePresetFields(additional, rows);
			//	this.getPreset().applyPreset(currentPresetId);
			//}
			//else
			//{
				this.getPreset().deactivateAllPresets();
				this.applyFilter();
				this.closePopup();
			//}
		},

		_onResetButtonClick: function()
		{
			this.resetFilter();
			this.getSearch().getInput().value = '';
			this.getSearch().hideClearButton();
			if (this.getParam("DISABLE_SEARCH"))
			{
				this.getSearch().setInputPlaceholder(this.getParam('MAIN_UI_FILTER__PLACEHOLDER'));
			}
			else
			{
				this.getSearch().setInputPlaceholder(this.getParam('MAIN_UI_FILTER__PLACEHOLDER_DEFAULT'));
			}
			this.closePopup();
		},

		resetFilter: function(withoutSearch)
		{
			var Search = this.getSearch();
			var Presets = this.getPreset();

			if (!withoutSearch)
			{
				Search.getInput().value = '';
			}

			Search.removePreset();
			Presets.deactivateAllPresets();
			Presets.resetPreset(true);
			this.applyFilter(true, true);

		},

		_onEditButtonClick: function()
		{
			if (!this.isEditEnabled())
			{
				this.enableEdit();
			}
			else
			{
				this.disableEdit();
			}
		},

		enableFieldsDragAndDrop: function()
		{
			var fields = this.getPreset().getFields();

			this.fieldsList = [];

			if (BX.type.isArray(fields))
			{
				this.fieldsList = fields.map(this.registerDragItem, this);
			}
		},

		registerDragItem: function(item)
		{
			var dragButton = this.getDragButton(item);

			dragButton.onbxdragstart = BX.delegate(this._onFieldDragStart, this);
			dragButton.onbxdragstop = BX.delegate(this._onFieldDragStop, this);
			dragButton.onbxdrag = BX.delegate(this._onFieldDrag, this);
			jsDD.registerObject(dragButton);
			jsDD.registerDest(dragButton);
			return item;
		},

		unregisterDragItem: function(item)
		{
			var dragButton = this.getDragButton(item);
			jsDD.unregisterObject(dragButton);
			jsDD.unregisterDest(dragButton);
		},

		_onFieldDragStart: function()
		{
			this.dragItem = this.getFields().getField(jsDD.current_node);
			this.dragIndex = BX.Filter.Utils.getIndex(this.fieldsList, this.dragItem);
			this.dragRect = this.dragItem.getBoundingClientRect();
			this.offset = this.dragRect.height;
			this.dragStartOffset = (jsDD.start_y - (this.dragRect.top + BX.scrollTop(window)));

			BX.Filter.Utils.styleForEach(this.fieldsList, {'transition': '100ms'});
			BX.addClass(this.dragItem, this.settings.classPresetOndrag);
			BX.bind(document, 'mousemove', BX.delegate(this._onMouseMove, this));
		},

		_onFieldDragStop: function()
		{
			BX.unbind(document, 'mousemove', BX.delegate(this._onMouseMove, this));
			BX.removeClass(this.dragItem, this.settings.classPresetOndrag);

			BX.Filter.Utils.styleForEach(this.fieldsList, {'transition': '', 'transform': ''});
			BX.Filter.Utils.collectionSort(this.dragItem, this.targetItem);

			this.fieldsList = this.getPreset().getFields();

			this.saveFieldsSort();
		},

		_onFieldDrag: function()
		{
			var self = this;
			var currentRect, currentMiddle;

			this.dragOffset = (this.realY - this.dragRect.top - this.dragStartOffset);
			this.sortOffset = self.realY + BX.scrollTop(window);

			BX.Filter.Utils.styleForEach([this.dragItem], {
				'transition': '0ms',
				'transform': 'translate3d(0px, '+this.dragOffset+'px, 0px)'
			});

			this.fieldsList.forEach(function(current, index) {
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
						BX.style(current, 'transition', '300ms');
					}

					if (index < self.dragIndex && self.sortOffset < currentMiddle &&
						current.style.transform !== 'translate3d(0px, '+(self.offset)+'px, 0px)' &&
						current.style.transform !== '')
					{
						self.targetItem = current;
						BX.style(current, 'transform', 'translate3d(0px, '+(self.offset)+'px, 0px)');
						BX.style(current, 'transition', '300ms');
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
						BX.style(current, 'transition', '300ms');
					}
				}
			});
		},

		disableFieldsDragAndDrop: function()
		{
			if (BX.type.isArray(this.fieldsList) && this.fieldsList.length)
			{
				this.fieldsList.map(this.unregisterDragItem, this);
			}
		},

		enablePresetsDragAndDrop: function()
		{
			var Preset, presets, dragButton, presetId;

			Preset = this.getPreset();
			presets = Preset.getPresets();
			this.presetsList = [];

			if (BX.type.isArray(presets) && presets.length)
			{
				presets.forEach(function(current) {
					presetId = Preset.getPresetId(current);

					if (!BX.hasClass(current, this.settings.classAddPresetField) &&
						presetId !== 'default_filter' &&
						!BX.hasClass(current, this.settings.classDefaultFilter))
					{
						dragButton = this.getDragButton(current);
						dragButton.onbxdragstart = BX.delegate(this._onDragStart, this);
						dragButton.onbxdragstop = BX.delegate(this._onDragStop, this);
						dragButton.onbxdrag = BX.delegate(this._onDrag, this);
						jsDD.registerObject(dragButton);
						jsDD.registerDest(dragButton);
						this.presetsList.push(current);
					}
				}, this);
			}
		},

		getDragButton: function(presetNode)
		{
			return BX.Filter.Utils.getByClass(presetNode, this.settings.classPresetDragButton);
		},

		disablePresetsDragAndDrop: function()
		{
			if (BX.type.isArray(this.presetsList) && this.presetsList.length)
			{
				this.presetsList.forEach(function(current) {
					if (!BX.hasClass(current, this.settings.classAddPresetField))
					{
						jsDD.unregisterObject(current);
						jsDD.unregisterDest(current);
					}
				}, this);
			}
		},

		_onDragStart: function()
		{
			this.dragItem = this.getPreset().normalizePreset(jsDD.current_node);
			this.dragIndex = BX.Filter.Utils.getIndex(this.presetsList, this.dragItem);
			this.dragRect = this.dragItem.getBoundingClientRect();
			this.offset = this.dragRect.height;
			this.dragStartOffset = (jsDD.start_y - (this.dragRect.top + BX.scrollTop(window)));

			BX.Filter.Utils.styleForEach(this.list, {'transition': '100ms'});
			BX.addClass(this.dragItem, this.settings.classPresetOndrag);
			BX.bind(document, 'mousemove', BX.delegate(this._onMouseMove, this));
		},

		_onMouseMove: function(event)
		{
			this.realX = event.clientX;
			this.realY = event.clientY;
		},

		getDragOffset: function()
		{
			return (jsDD.x - this.startDragOffset - this.dragRect.left);
		},

		_onDragStop: function()
		{
			var Preset, presets;

			BX.unbind(document, 'mousemove', BX.delegate(this._onMouseMove, this));
			BX.removeClass(this.dragItem, this.settings.classPresetOndrag);

			BX.Filter.Utils.styleForEach(this.presetsList, {'transition': '', 'transform': ''});
			BX.Filter.Utils.collectionSort(this.dragItem, this.targetItem);

			Preset = this.getPreset();
			presets = Preset.getPresets();
			this.presetsList = [];

			if (BX.type.isArray(presets) && presets.length)
			{
				presets.forEach(function(current) {
					if (!BX.hasClass(current, this.settings.classAddPresetField) &&
						!BX.hasClass(current, this.settings.classDefaultFilter))
					{
						this.presetsList.push(current);
					}
				}, this);
			}

		},

		_onDrag: function()
		{
			var self = this;
			var currentRect, currentMiddle;

			this.dragOffset = (this.realY - this.dragRect.top - this.dragStartOffset);
			this.sortOffset = self.realY + BX.scrollTop(window);

			BX.Filter.Utils.styleForEach([this.dragItem], {
				'transition': '0ms',
				'transform': 'translate3d(0px, '+this.dragOffset+'px, 0px)'
			});

			this.presetsList.forEach(function(current, index) {
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
						BX.style(current, 'transition', '300ms');
					}

					if (index < self.dragIndex && self.sortOffset < currentMiddle &&
						current.style.transform !== 'translate3d(0px, '+(self.offset)+'px, 0px)' &&
						current.style.transform !== '')
					{
						self.targetItem = current;
						BX.style(current, 'transform', 'translate3d(0px, '+(self.offset)+'px, 0px)');
						BX.style(current, 'transition', '300ms');
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
						BX.style(current, 'transition', '300ms');
					}
				}
			});
		},


		getSidebarControlsContainer: function()
		{
			if (!BX.type.isDomNode(this.sidebarControlsContainer))
			{
				this.sidebarControlsContainer = BX.Filter.Utils.getByClass(this.getFilter(), this.settings.classSidebarControlsContainer);
			}

			return this.sidebarControlsContainer;
		},

		enableEdit: function()
		{
			var Preset = this.getPreset();
			var presets = Preset.getPresets();
			var presetId;

			if (BX.type.isArray(presets) && presets.length)
			{
				presets.forEach(function(current) {
					presetId = Preset.getPresetId(current);
					if (!BX.hasClass(current, this.settings.classAddPresetField) && presetId !== 'default_filter')
					{
						BX.addClass(current, this.settings.classPresetEdit);
					}
				}, this);
			}

			this.getSaveForAllButton() && BX.removeClass(this.getSaveForAllButton(), this.settings.classWaitButtonClass);

			this.enablePresetsDragAndDrop();
			BX.show(this.getButtonsContainer());
			BX.hide(this.getPresetButtonsContainer());
			BX.addClass(this.getSidebarControlsContainer(), this.settings.classDisabled);
			this.editablePresets = BX.clone(this.getParam('PRESETS'));
			this.isEditEnabledState = true;
		},

		disableEdit: function()
		{
			var Preset = this.getPreset();
			var presets = Preset.getPresets();

			if (BX.type.isArray(presets) && presets.length)
			{
				presets.forEach(function(current) {
					if (!BX.hasClass(current, this.settings.classAddPresetField))
					{
						BX.removeClass(current, this.settings.classPresetEdit);
						this.getPreset().disableEditPresetName(current);
					}
				}, this);
			}

			this.disablePresetsDragAndDrop();

			if (!this.isAddPresetEnabled())
			{
				BX.style(this.getButtonsContainer(), 'display', '');
			}

			BX.show(this.getPresetButtonsContainer());
			BX.removeClass(this.getSidebarControlsContainer(), this.settings.classDisabled);
			this.editablePresets = null;
			this.isEditEnabledState = false;
			this.applyFilter(null, true);
		},

		getPresetButtonsContainer: function()
		{
			if (!BX.type.isDomNode(this.presetButtonsContainer))
			{
				this.presetButtonsContainer = BX.Filter.Utils.getByClass(this.getFilter(), this.settings.classPresetButtonsContainer);
			}

			return this.presetButtonsContainer;
		},

		isEditEnabled: function()
		{
			return this.isEditEnabledState;
		},

		getEditButton: function()
		{
			return BX.Filter.Utils.getByClass(this.getFilter(), this.settings.classEditButton);
		},

		getParam: function(paramName, defaultValue)
		{
			return this.params[paramName] ? this.params[paramName] : defaultValue;
		},

		getFilter: function()
		{
			return BX.Filter.Utils.getByClass(this.popup.contentContainer, this.settings.classFilterContainer);
		},

		getSearch: function()
		{
			if (!(this.search instanceof BX.Filter.Search))
			{
				this.search = new BX.Filter.Search(this);
			}

			return this.search;
		},

		_onRestoreButtonClick: function()
		{
			var action = {
				CONFIRM: true,
				CONFIRM_MESSAGE: this.getParam('CONFIRM_MESSAGE'),
				CONFIRM_APPLY_BUTTON: this.getParam('CONFIRM_APPLY'),
				CONFIRM_CANCEL_BUTTON: this.getParam('CONFIRM_CANCEL')
			};

			this.confirmDialog(action, BX.delegate(this.restoreFilter, this));
		},


		confirmDialog: function(action, then, cancel)
		{
			var dialog, popupContainer;

			if ('CONFIRM' in action && action.CONFIRM)
			{
				dialog = new BX.PopupWindow(
					this.getParam('FILTER_ID') + '-confirm-dialog',
					null,
					{
						content: '<div class="main-ui-filter-confirm-content">'+action.CONFIRM_MESSAGE+'</div>',
						titleBar: 'CONFIRM_TITLE' in action ? action.CONFIRM_TITLE : '',
						autoHide: false,
						zIndex: 9999,
						overlay: 0.4,
						offsetTop: -100,
						closeIcon : true,
						closeByEsc : true,
						buttons: [
							new BX.PopupWindowButton({
								text: action.CONFIRM_APPLY_BUTTON,
								events: {
									click: function()
									{
										BX.type.isFunction(then) ? then() : null;
										this.popupWindow.close();
										this.popupWindow.destroy();
									}
								}
							}),
							new BX.PopupWindowButtonLink({
								text: action.CONFIRM_CANCEL_BUTTON,
								events: {
									click: function()
									{
										BX.type.isFunction(cancel) ? cancel() : null;
										this.popupWindow.close();
										this.popupWindow.destroy();
									}
								}
							})
						]
					}
				);

				BX.addCustomEvent(dialog, 'onPopupClose', BX.delegate(function() {
					this.getSaveForAllButton() && BX.removeClass(this.getSaveForAllButton(), this.settings.classWaitButtonClass);
				}, this));

				if (!dialog.isShown())
				{
					dialog.show();
					popupContainer = dialog.popupContainer;
					BX.removeClass(popupContainer, this.settings.classAnimationShow);
					BX.addClass(popupContainer, this.settings.classAnimationShow);
				}
			}
			else
			{
				BX.type.isFunction(then) ? then() : null;
			}
		}
	};
})();


(function() {
	BX.Main.filterManager = {
		data: {},

		push: function(id, instance)
		{
			if (BX.type.isNotEmptyString(id) && instance)
			{
				this.data[id] = instance;
			}
		},

		getById: function(id)
		{
			var result = null;

			if (id in this.data)
			{
				result = this.data[id];
			}

			return result;
		}
	};
})();
