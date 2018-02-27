;(function() {
	'use strict';

	BX.namespace('BX.Filter');


	/**
	 * Filter presets class
	 * @param parent
	 * @constructor
	 */
	BX.Filter.Presets = function(parent)
	{
		this.parent = null;
		this.presets = null;
		this.container = null;
		this.init(parent);
	};

	BX.Filter.Presets.prototype = {
		init: function(parent)
		{
			this.parent = parent;
		},

		bindOnPresetClick: function()
		{
			var presets = this.getPresets();
			var self = this;

			(presets || []).forEach(function(current) {
				BX.bind(current, 'click', BX.delegate(self._onPresetClick, self));
			});
		},

		getAddPresetField: function()
		{
			return BX.Filter.Utils.getByClass(this.getContainer(), this.parent.settings.classAddPresetField);
		},

		getAddPresetFieldInput: function()
		{
			return BX.Filter.Utils.getByClass(this.getAddPresetField(), this.parent.settings.classAddPresetFieldInput);
		},

		normalizePreset: function(node)
		{
			if (!BX.hasClass(node, this.parent.settings.classPreset))
			{
				node = BX.findParent(node, {class: this.parent.settings.classPreset}, true, false);
			}

			return node;
		},

		deactivateAllPresets: function()
		{
			var presets = this.getPresets();
			var self = this;

			(presets || []).forEach(function(current) {
				if (BX.hasClass(current, self.parent.settings.classPresetCurrent))
				{
					BX.removeClass(current, self.parent.settings.classPresetCurrent)
				}
			});
		},

		activatePreset: function(preset)
		{
			this.deactivateAllPresets();

			if (BX.type.isNotEmptyString(preset))
			{
				preset = this.getPresetNodeById(preset);
			}

			if (!BX.hasClass(preset, this.parent.settings.classPresetCurrent))
			{
				BX.addClass(preset, this.parent.settings.classPresetCurrent);
			}
		},

		getPresetNodeById: function(id)
		{
			var presets = this.getPresets();
			var result = presets.filter(function(current) {
				return BX.data(current, 'id') === id;
			}, this);

			return result.length > 0 ? result[0] : null;
		},

		getPresetId: function(preset)
		{
			return BX.data(preset, 'id');
		},

		updatePresetName: function(presetNode, name)
		{
			var nameNode;

			if (BX.type.isDomNode(presetNode) && BX.type.isNotEmptyString(name))
			{
				nameNode = this.getPresetNameNode(presetNode);

				if (BX.type.isDomNode(nameNode))
				{
					nameNode.innerText = name;
				}
			}
		},

		removePreset: function(presetNode, presetId, isDefault)
		{
			var currentPresetId = this.getCurrentPresetId();
			var newPresets = [];
			var postData = {
				'preset_id': presetId,
				'is_default': isDefault
			};

			var getData = {
				'FILTER_ID': this.parent.getParam('FILTER_ID'),
				'action': 'REMOVE_FILTER'
			};

			this.parent.saveOptions(postData, getData);
			BX.remove(presetNode);

			if (BX.type.isArray(this.parent.params['PRESETS']))
			{
				newPresets = this.parent.params['PRESETS'].filter(function(current) {
					return current.ID !== presetId;
				}, this);

				this.parent.params['PRESETS'] = newPresets;
			}

			if (BX.type.isArray(this.parent.editablePresets))
			{
				newPresets = this.parent.editablePresets.filter(function(current) {
					return current.ID !== presetId;
				}, this);

				this.parent.editablePresets = newPresets;
			}

			if (presetId === currentPresetId)
			{
				this.parent.getSearch().removePreset();
				this.resetPreset();
			}
		},

		pinPreset: function(presetId)
		{
			if (!BX.type.isNotEmptyString(presetId))
			{
				presetId = 'default_filter';
			}

			var presetNode = this.getPresetNodeById(presetId);

			if (this.parent.getParam('VALUE_REQUIRED_MODE'))
			{
				if (presetId == 'default_filter')
				{
					return;
				}
			}

			var params = {'FILTER_ID': this.parent.getParam('FILTER_ID'), 'GRID_ID': this.parent.getParam('GRID_ID'), 'action': 'PIN_PRESET'};
			var data = {preset_id: presetId};

			this.getPresets().forEach(function(current) {
				BX.removeClass(current, this.parent.settings.classPinnedPreset);
			}, this);

			BX.addClass(presetNode, this.parent.settings.classPinnedPreset);

			this.parent.saveOptions(data, params);
		},

		_onPresetClick: function(event) {
			var presetNode, presetId, presetData, isDefault, target, settings, parent;

			event.preventDefault();

			parent = this.parent;
			settings = parent.settings;
			target = event.target;
			presetNode = event.currentTarget;
			presetId = this.getPresetId(presetNode);
			presetData = this.getPreset(presetId);

			if (BX.hasClass(target, settings.classPinButton))
			{
				if (this.parent.isEditEnabled())
				{
					if (BX.hasClass(presetNode, settings.classPinnedPreset))
					{
						this.pinPreset("default_filter");
					}
					else
					{
						this.pinPreset(presetId)
					}
				}
			}

			if (BX.hasClass(target, settings.classPresetEditButton))
			{
				this.enableEditPresetName(presetNode, target);
			}

			if (BX.hasClass(target, settings.classPresetDeleteButton))
			{
				isDefault = 'IS_DEFAULT' in presetData ? presetData.IS_DEFAULT : false;
				this.removePreset(presetNode, presetId, isDefault);
				return false;
			}

			if (!BX.hasClass(target, settings.classPresetDragButton) &&
				!BX.hasClass(target, settings.classAddPresetFieldInput))
			{
				if (this.parent.isEditEnabled())
				{
					this.updateEditablePreset(this.getCurrentPresetId());
				}

				this.activatePreset(presetNode);
				this.applyPreset(presetId);

				if (!this.parent.isEditEnabled())
				{
					parent.applyFilter(null, true);

					if (event.isTrusted)
					{
						parent.closePopup();
					}

					if (parent.isAddPresetEnabled())
					{
						parent.disableAddPreset();
					}
				}
			}
		},

		updateEditablePreset: function(presetId)
		{
			var fields = this.parent.getFilterFieldsValues();
			var presetRows = this.getFields().map(function(curr) { return BX.data(curr, 'name'); });
			var presetFields = this.parent.preparePresetFields(fields, presetRows);
			var preset = this.getPreset(presetId);

			preset.FIELDS = presetFields;
			preset.TITLE = this.getPresetInput(this.getPresetNodeById(presetId)).value;
			preset.ROWS = presetRows;
		},

		getPresetInput: function(presetNode)
		{
			return BX.Filter.Utils.getByClass(presetNode, this.parent.settings.classPresetEditInput);
		},

		enableEditPresetName: function(presetNode)
		{
			var input = this.getPresetInput(presetNode);

			BX.addClass(presetNode, this.parent.settings.classPresetNameEdit);
			input.focus();
			//noinspection SillyAssignmentJS
			input.value = input.value;
			BX.bind(input, 'input', BX.delegate(this._onPresetNameInput, this));
		},

		_onPresetNameInput: function(event)
		{
			var Search = this.parent.getSearch();
			var inputValue = event.currentTarget.value;
			var presetNode = BX.findParent(event.currentTarget, {className: this.parent.settings.classPreset}, true, false);
			var presetId = this.getPresetId(presetNode);
			var currentPresetId = this.getCurrentPresetId();
			var data = {ID: presetId, TITLE: inputValue};

			if (presetId === currentPresetId)
			{
				Search.updatePreset(data);
			}
		},

		getPresetNameNode: function(presetNode)
		{
			return BX.Filter.Utils.getByClass(presetNode, this.parent.settings.classPresetName);
		},

		disableEditPresetName: function(presetNode)
		{
			var input = this.getPresetInput(presetNode);

			BX.removeClass(presetNode, this.parent.settings.classPresetNameEdit);

			if (BX.type.isDomNode(input))
			{
				input.blur();
				BX.unbind(input, 'input', BX.delegate(this._onPresetNameInput, this));
			}
		},

		getPreset: function(presetId, isDefault)
		{
			var presets = this.parent.getParam(isDefault ? 'DEFAULT_PRESETS' : 'PRESETS', []);

			if (this.parent.isEditEnabled() && !isDefault)
			{
				presets = this.parent.editablePresets;
			}

			var filtered = presets.filter(function(current) {
				return current.ID === presetId;
			});

			return filtered.length !== 0 ? filtered[0] : null;
		},

		getPresetField: function(presetId, fieldName)
		{
			var preset = this.getPreset(presetId);
			var field = null;

			if (BX.type.isPlainObject(preset) && 'FIELDS' in preset && BX.type.isArray(preset.FIELDS))
			{
				field = preset.FIELDS.filter(function(current) {
					return current.NAME === fieldName;
				}, this);

				field = field.length ? field[0] : null;
			}

			return field;
		},

		applyPreset: function(presetId, noValues)
		{
			var preset;

			presetId = noValues ? 'default_filter' : presetId || 'default_filter';
			preset = this.getPreset(presetId);

			if (presetId !== 'default_preset')
			{
				preset = this.extendPreset(preset);
			}

			this.parent.getSearch().updatePreset(preset);
			this.updatePresetFields(preset, noValues);
		},

		extendPreset: function(preset)
		{
			var defaultPreset = BX.clone(this.getPreset('default_filter'));

			if (BX.type.isPlainObject(preset))
			{
				preset = BX.clone(preset);
				preset.FIELDS.forEach(function(curr, i) {
					var index;
					var someField = defaultPreset.FIELDS.some(function(defCurr, defIndex) {
						var result = false;

						if (defCurr.NAME === curr.NAME)
						{
							index = defIndex;
							result = true;
						}

						return result;
					}, this);

					if (someField && index || someField && index === 0)
					{
						defaultPreset.FIELDS[index] = curr;
					}
					else
					{
						if (!this.isEmptyField(curr))
						{
							defaultPreset.FIELDS.push(curr);
						}
					}
				}, this);

				preset.FIELDS = defaultPreset.FIELDS;
			}

			return preset;
		},

		isEmptyField: function(field)
		{
			var result = true;

			if (field.TYPE === this.parent.types.STRING)
			{
				if (field.VALUE && field.VALUE.length)
				{
					result = false;
				}
			}

			if (field.TYPE === this.parent.types.SELECT)
			{
				if (BX.type.isPlainObject(field.VALUE) && 'VALUE' in field.VALUE && field.VALUE.VALUE)
				{
					result = false;
				}
			}

			if (field.TYPE === this.parent.types.MULTI_SELECT)
			{
				if (BX.type.isArray(field.VALUE) && field.VALUE.length)
				{
					result = false;
				}
			}

			if (field.TYPE === this.parent.types.CUSTOM_ENTITY)
			{
				if (BX.type.isPlainObject(field.VALUES) && field.VALUES._label && field.VALUES._value)
				{
					result = false;
				}
			}

			if (field.TYPE === this.parent.types.DATE)
			{
				if (BX.type.isPlainObject(field.VALUES) &&
					(field.VALUES._from ||
					field.VALUES._to ||
					field.VALUES._month ||
					field.VALUES._quarter ||
					field.VALUES._year ||
					field.VALUES._days) ||
					(
						field.VALUES._datesel === this.parent.dateTypes.CURRENT_DAY ||
						field.VALUES._datesel === this.parent.dateTypes.CURRENT_WEEK ||
						field.VALUES._datesel === this.parent.dateTypes.CURRENT_MONTH ||
						field.VALUES._datesel === this.parent.dateTypes.CURRENT_QUARTER ||
						field.VALUES._datesel === this.parent.dateTypes.LAST_7_DAYS ||
						field.VALUES._datesel === this.parent.dateTypes.LAST_30_DAYS ||
						field.VALUES._datesel === this.parent.dateTypes.LAST_60_DAYS ||
						field.VALUES._datesel === this.parent.dateTypes.LAST_90_DAYS ||
						field.VALUES._datesel === this.parent.dateTypes.LAST_WEEK ||
						field.VALUES._datesel === this.parent.dateTypes.LAST_MONTH
					)
				)
				{
					result = false;
				}
			}

			if (field.TYPE === this.parent.types.NUMBER)
			{
				if (BX.type.isPlainObject(field.VALUES) && (field.VALUES._from || field.VALUES._to))
				{
					result = false;
				}
			}

			if (field.TYPE === this.parent.types.CHECKBOX)
			{
				if (BX.type.isPlainObject(field.VALUE) && field.VALUE.VALUE)
				{
					result = false;
				}
			}

			return result;
		},

		resetPreset: function(noValues)
		{
			this.applyPreset(null, noValues);
		},

		getFields: function()
		{
			var container = this.parent.getFieldListContainer();
			var fields = null;

			if (BX.type.isDomNode(container))
			{
				fields = BX.Filter.Utils.getBySelector(container.parentNode, '.'+this.parent.settings.classFileldControlList+' > div', true);
			}

			return fields;
		},

		getField: function(fieldData)
		{
			var fields = this.getFields();
			var field = null;
			var tmpName, filtered;

			if (BX.type.isArray(fields) && fields.length)
			{
				filtered = fields.filter(function(current) {
					if (BX.type.isDomNode(current))
					{
						tmpName = BX.data(current, 'name');
					}
					return tmpName === fieldData.NAME;
				}, this);

				field = filtered.length > 0 ? filtered[0] : null;
			}

			return field;
		},

		removeField: function(field)
		{
			var index, fieldName;

			if (BX.type.isPlainObject(field))
			{
				fieldName = field.NAME;
				field = this.getField(field);

				if (BX.type.isArray(this.parent.fieldsList))
				{
					index = this.parent.fieldsList.indexOf(field);

					if (index !== -1)
					{
						delete this.parent.fieldsList[index];
					}
				}
				this.parent.unregisterDragItem(field);
			}

			if (BX.type.isDomNode(field))
			{
				fieldName = BX.data(field, 'name');
				this.parent.getFields().deleteField(field);
			}

			if (!this.parent.isEditEnabled() && !this.parent.isAddPresetEnabled())
			{
				var currentPresetId = this.getCurrentPresetId();
				var currentPresetField = this.getPresetField(currentPresetId, fieldName);

				if (currentPresetField && !this.isEmptyField(currentPresetField))
				{
					this.deactivateAllPresets();
					this.parent.applyFilter();
				}
			}

			this.parent.saveFieldsSort();
		},

		addField: function(fieldData)
		{
			var container, control, controls;

			if (BX.type.isPlainObject(fieldData))
			{
				container = this.parent.getFieldListContainer();
				controls = this.parent.getControls();
				control = BX.type.isArray(controls) ? controls[controls.length-1] : null;

				if (BX.type.isDomNode(control))
				{
					if (control.nodeName !== 'INPUT')
					{
						control = BX.Filter.Utils.getByTag(control, 'input');
					}

					if (BX.type.isDomNode(control))
					{
						fieldData.TABINDEX = parseInt(control.getAttribute('tabindex')) + 1;
					}
				}
				else
				{
					fieldData.TABINDEX = 2;
				}

				if (BX.type.isDomNode(container))
				{
					control = this.createControl(fieldData);

					if (BX.type.isDomNode(control))
					{
						BX.append(control, container);
						if (BX.type.isArray(this.parent.fieldsList))
						{
							this.parent.fieldsList.push(control);
						}

						this.parent.registerDragItem(control);
					}
				}
			}

			if (!this.parent.isEditEnabled() && !this.parent.isAddPresetEnabled())
			{
				var currentPresetId = this.getCurrentPresetId();
				var currentPresetField = this.getPresetField(currentPresetId, fieldData.NAME);

				if (currentPresetField && !this.isEmptyField(currentPresetField))
				{
					this.parent.updatePreset('tmp_filter');
					this.deactivateAllPresets();
					this.parent.getSearch().updatePreset(this.getPreset('tmp_filter'));
				}
			}

			this.parent.saveFieldsSort();
		},

		createControl: function(fieldData)
		{
			var control;

			switch (fieldData.TYPE)
			{
				case this.parent.types.STRING : {
					control = this.parent.getFields().createInputText(fieldData);
					break;
				}

				case this.parent.types.SELECT : {
					control = this.parent.getFields().createSelect(fieldData);
					break;
				}

				case this.parent.types.MULTI_SELECT : {
					control = this.parent.getFields().createMultiSelect(fieldData);
					break;
				}

				case this.parent.types.NUMBER : {
					control = this.parent.getFields().createNumber(fieldData);
					break;
				}

				case this.parent.types.DATE : {
					control = this.parent.getFields().createDate(fieldData);
					break;
				}

				case this.parent.types.USER : {
					control = this.parent.getFields().createUser(fieldData);
					break;
				}

				case this.parent.types.ENTITY : {
					control = this.parent.getFields().createEntity(fieldData);
					break;
				}

				case this.parent.types.CUSTOM : {
					control = this.parent.getFields().createCustom(fieldData);
					break;
				}

				case this.parent.types.CUSTOM_ENTITY : {
					control = this.parent.getFields().createCustomEntity(fieldData);
					break;
				}

				default : {
					break;
				}
			}

			if (BX.type.isDomNode(control))
			{
				control.dataset.name = fieldData.NAME;
				control.FieldController = new BX.Filter.FieldController(control, this.parent);
			}

			return control;
		},

		isPresetValuesModified: function(presetId)
		{
			var preset = this.getPreset(presetId);
			var rows = this.getFields().map(function(curr) { return BX.data(curr, 'name'); });
			var presetValues = this.parent.preparePresetSettingsFields(preset.FIELDS, rows);
			var filterValues = this.parent.getFilterFieldsValues();
			var presetValuesKeys = Object.keys(presetValues);
			var skipPrefix = ['_from', '_to', '_days', '_month', '_quarter', '_year'];

			if ('FIND' in presetValues)
			{
				delete presetValues['FIND'];
			}

			if ('FIND' in filterValues)
			{
				delete filterValues['FIND'];
			}

			function skip(key)
			{
				return skipPrefix.some(function(prefix) { return key.indexOf(prefix) !== -1; });
			}

			return !presetValuesKeys.every(function(key) {
				var result = false;

				if (skip(key))
				{
					result = true;
				}
				else if (key.indexOf('_datesel') !== -1)
				{
					var dateKey = key.replace('_datesel', '');

					if (presetValues[key] === this.parent.dateTypes.NONE)
					{
						result = true;
					}
					else
					{
						var tmpResult = skipPrefix.some(function(prefix) {
							return presetValues[dateKey + prefix] !== filterValues[dateKey + prefix];
						});

						if ((presetValues[key] === this.parent.dateType.CURRENT_DAY ||
							presetValues[key] === this.parent.dateType.CURRENT_MONTH ||
							presetValues[key] === this.parent.dateType.CURRENT_QUARTER ||
							presetValues[key] === this.parent.dateType.CURRENT_WEEK ||
							presetValues[key] === this.parent.dateType.LAST_7_DAYS ||
							presetValues[key] === this.parent.dateType.LAST_30_DAYS ||
							presetValues[key] === this.parent.dateType.LAST_60_DAYS ||
							presetValues[key] === this.parent.dateType.LAST_90_DAYS ||
							presetValues[key] === this.parent.dateType.LAST_MONTH ||
							presetValues[key] === this.parent.dateType.LAST_WEEK) && (
								presetValues[key] === filterValues[key]))
						{
							result = true;
						}

						if ((presetValues[key] === this.parent.dateType.EXACT ||
							 presetValues[key] === this.parent.dateType.MONTH ||
							 presetValues[key] === this.parent.dateType.QUARTER ||
							 presetValues[key] === this.parent.dateType.RANGE ||
							 presetValues[key] === this.parent.dateType.YEAR) &&
							!tmpResult)
						{
							result = true;
						}
					}
				}
				else if (key.indexOf('_numsel') !== -1)
				{
					var numKey = key.replace('_numsel', '');

					if ((!BX.type.isNotEmptyString(presetValues[numKey + '_from']) || presetValues[numKey + '_from'] == filterValues[numKey + '_from']) ||
						(!BX.type.isNotEmptyString(presetValues[numKey + '_to']) || presetValues[numKey + '_to'] == filterValues[numKey + '_to']))
					{
						result = true;
					}
				}
				else if (!BX.type.isNotEmptyString(presetValues[key]) || presetValues[key] == filterValues[key])
				{
					result = true;
				}

				return result;
			}, this);
		},

		getAdditionalValues: function(presetId)
		{
			var preset = this.getPreset(presetId);
			var rows = this.getFields().map(function(curr) { return BX.data(curr, 'name'); });
			var presetValues = this.parent.preparePresetSettingsFields(preset.FIELDS, rows);
			var filterValues = this.parent.getFilterFieldsValues();
			var result = {};

			if ('FIND' in presetValues)
			{
				delete presetValues['FIND'];
			}

			if ('FIND' in filterValues)
			{
				delete filterValues['FIND'];
			}

			var filterValuesKeys = Object.keys(filterValues);

			filterValuesKeys.forEach(function(key) {
				if (!BX.type.isNotEmptyString(presetValues[key]) || key.indexOf('_numsel') !== -1 || presetValues[key] !== filterValues[key])
				{
					result[key] = filterValues[key];
				}
			}, this);

			return result;
		},

		updatePresetFields: function(preset, noValues)
		{
			var fields, fieldListContainer;
			var fieldNodes = [];

			if (BX.type.isPlainObject(preset) && ('FIELDS' in preset))
			{
				fields = preset.FIELDS;

				(fields || []).forEach(function(fieldData, index) {
					fieldData.TABINDEX = index+1;
					if (noValues)
					{
						switch (fieldData.TYPE)
						{
							case this.parent.types.SELECT : {
								fieldData.VALUE = fieldData.ITEMS[0];
								break;
							}

							case this.parent.types.MULTI_SELECT : {
								fieldData.VALUE = [];
								break;
							}

							case this.parent.types.DATE : {
								fieldData.SUB_TYPE = fieldData.SUB_TYPES[0];
								fieldData.VALUES = {
									'_from': '',
									'_to': '',
									'_days': ''
								};
								break;
							}

							case this.parent.types.NUMBER : {
								fieldData.SUB_TYPE = fieldData.SUB_TYPES[0];
								fieldData.VALUES = {
									'_from': '',
									'_to': ''
								};
								break;
							}

							case this.parent.types.CUSTOM_ENTITY : {
								fieldData.VALUES = {
									'_label': '',
									'_value': ''
								};
								break;
							}

							case this.parent.types.CUSTOM : {
								fieldData._VALUE = '';
								break;
							}

							default : {
								if ('VALUE' in fieldData)
								{
									if (BX.type.isArray(fieldData.VALUE))
									{
										fieldData.VALUE = [];
									}
									else
									{
										fieldData.VALUE = '';
									}
								}
								break;
							}
						}
					}

					fieldNodes.push(this.createControl(fieldData));
				}, this);

				this.parent.disableFieldsDragAndDrop();
				fieldListContainer = this.parent.getFieldListContainer();
				BX.cleanNode(fieldListContainer);

				if (fieldNodes.length)
				{
					fieldNodes.forEach(function(current) {
						if (BX.type.isDomNode(current))
						{
							BX.append(current, fieldListContainer);
						}
					});

					this.parent.enableFieldsDragAndDrop();
				}
			}
		},

		showCurrentPresetFields: function()
		{
			var preset = this.getCurrentPresetData();
			this.updatePresetFields(preset);
		},

		getCurrentPreset: function()
		{
			return BX.Filter.Utils.getByClass(this.getContainer(), this.parent.settings.classPresetCurrent);
		},

		getCurrentPresetId: function()
		{
			var current = this.getCurrentPreset();
			var currentId = null;

			if (BX.type.isDomNode(current))
			{
				currentId = this.getPresetId(current);
			}
			else
			{
				currentId = "default_filter";
			}

			return currentId;
		},

		getCurrentPresetData: function()
		{
			var currentId = this.getCurrentPresetId();
			var currentData = null;

			if (BX.type.isNotEmptyString(currentId))
			{
				currentData = this.getPreset(currentId);
				currentData = this.extendPreset(currentData);
			}

			return currentData;
		},

		getContainer: function()
		{
			return BX.Filter.Utils.getByClass(this.parent.getFilter(), this.parent.settings.classPresetsContainer);
		},


		getPresets: function()
		{
			return BX.Filter.Utils.getByClass(this.getContainer(), this.parent.settings.classPreset, true);
		},

		getDefaultPresets: function()
		{
			return BX.Filter.Utils.getByClass(this.getContainer(), this.parent.settings.classDefaultFilter, true);
		}
	};
})();