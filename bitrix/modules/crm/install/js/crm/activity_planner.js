BX.namespace('BX.Crm.Activity');

(function(BX)
{
	'use strict';

	if(typeof BX.Crm.Activity.Planner !== 'undefined')
		return;

	var CRM_ACTIVITY_PLANNER_ID_ATTRIBUTE = 'data-crm-act-planner';
	var DEFAULT_AJAX_URL = '/bitrix/components/bitrix/crm.activity.planner/ajax.php?site_id=' + BX.message('SITE_ID');
	var COMMUNICATIONS_AJAX_URL = '/bitrix/components/bitrix/crm.activity.editor/ajax.php?siteID='+BX.message('SITE_ID')+'&sessid='+BX.bitrix_sessid();

	var Planner = function(config)
	{
		if (typeof config === 'undefined')
			config = {};

		this.ajaxUrl = config.ajaxUrl || DEFAULT_AJAX_URL;
		this.loadOffsetLeft = 2;
		this.loadOffsetRight = 2;

		Planner.Manager.put(this);
	};

	Planner.Manager = {
		instances: {},
		listeners: {},
		lastId: '',
		/**
		 * @param {Planner} activityPlanner
		 * @returns {Planner.Manager}
		 */
		put: function(activityPlanner)
		{
			if (!(activityPlanner instanceof Planner))
				throw 'activityPlanner is not instanceof Planner';

			var id = activityPlanner.getPlannerId();

			this.instances[id] = activityPlanner;
			this.lastId = id;
			return this;
		},
		/**
		 * @param {string} plannerId
		 * @returns {Planner | null}
		 */
		get: function (plannerId)
		{
			plannerId = plannerId.toString();
			if (typeof this.instances[plannerId] === 'undefined')
				return null;
			return this.instances[plannerId];
		},
		/**
		 * @returns {Planner | null}
		 */
		getLast: function()
		{
			return this.get(this.lastId);
		},
		/**
		 * @param {string} plannerId
		 * @returns {Planner.Manager}
		 */
		pop: function (plannerId)
		{
			delete this.instances[plannerId.toString()];
			return this;
		},
		/**
		 * @param {Element} child
		 * @returns {Planner | null}
		 */
		findByChild: function(child)
		{
			var wrapper = BX.findParent(child, {attr: CRM_ACTIVITY_PLANNER_ID_ATTRIBUTE});
			if (wrapper)
			{
				return this.get(wrapper.getAttribute(CRM_ACTIVITY_PLANNER_ID_ATTRIBUTE));
			}
			return null;
		},
		/**
		 * @param {string} eventName
		 * @param {function} listener
		 * @returns {Planner.Manager}
		 */
		setCallback: function(eventName, listener)
		{
			this.listeners[eventName] = listener;
			return this;
		},
		/**
		 * @param {string} eventName
		 * @param {Object} params
		 * @param context
		 */
		fireEvent: function(eventName, params, context)
		{
			var listener = this.listeners[eventName];
			if (typeof listener === 'function')
			{
				listener.call(context, params);
			}
		}
	};

	Planner.util = {
		unFormatTime: function(time)
		{
			var q = time.split(/[\s:]+/);
			if (q.length == 3)
			{
				var mt = q[2];
				if (mt == 'pm' && q[0] < 12)
					q[0] = parseInt(q[0], 10) + 12;

				if (mt == 'am' && q[0] == 12)
					q[0] = 0;

			}
			return parseInt(q[0], 10) * 3600 + parseInt(q[1], 10) * 60;
		},
		formatTime: function(date)
		{
			var dateFormat = BX.date.convertBitrixFormat(BX.message('FORMAT_DATE')).replace(/:?\s*s/, ''),
				timeFormat = BX.date.convertBitrixFormat(BX.message('FORMAT_DATETIME')).replace(/:?\s*s/, ''),
				str1 = BX.date.format(dateFormat, date),
				str2 = BX.date.format(timeFormat, date);
			return BX.util.trim(str2.replace(str1, ''));
		},
		convertDateTime: function(date)
		{
			var f = BX.message('FORMAT_DATETIME');
			var format = BX.date.convertBitrixFormat(BX.type.isNotEmptyString(f) ? f : 'DD.MM.YYYY HH:MI:SS');
			return BX.date.format(format, date)
		},
		storageType: {
			File: 1,
			Webdav: 2,
			Disk: 3
		},
		periodType: {
			min: 1,
			hour: 2,
			day: 3
		},
		getPeriodLabels: function(period)
		{
			var labels = [];
			if (period === 1)
				labels = [
					BX.message('CRM_ACTIVITY_PLANNER_MIN1'),
					BX.message('CRM_ACTIVITY_PLANNER_MIN2'),
					BX.message('CRM_ACTIVITY_PLANNER_MIN3')
				];
			else if (period === 2)
				labels = [
					BX.message('CRM_ACTIVITY_PLANNER_HOUR1'),
					BX.message('CRM_ACTIVITY_PLANNER_HOUR2'),
					BX.message('CRM_ACTIVITY_PLANNER_HOUR3')
				];
			else if (period === 3)
				labels = [
					BX.message('CRM_ACTIVITY_PLANNER_DAY1'),
					BX.message('CRM_ACTIVITY_PLANNER_DAY2'),
					BX.message('CRM_ACTIVITY_PLANNER_DAY3')
				];

			return labels;
		}
	};

	Planner.prototype.getPlannerId = function()
	{
		if (typeof this.plannerId === 'undefined')
			this.plannerId = 'crm-act-planner-' + Math.round(Math.random() * 100000);

		return this.plannerId;
	};

	Planner.prototype.setPopup = function(popup)
	{
		this.popup = popup;
		return this;
	};

	/**
	 * @returns {BX.PopupWindow}
	 */
	Planner.prototype.getPopup = function()
	{
		return this.popup;
	};

	Planner.prototype.setPlannerNode = function(node)
	{
		this.scopeNode = node;
		return this;
	};

	Planner.prototype.getPlannerNode = function()
	{
		return this.scopeNode;
	};

	Planner.prototype.getNode = function(name, scope)
	{
		if (!scope)
			scope = this.getPlannerNode();

		return scope ? scope.querySelector('[data-role="'+name+'"]') : null;
	};

	Planner.prototype.getNodeValue = function (nodeName, scope)
	{
		var node = this.getNode(nodeName, scope);
		return node ? node.value : null;
	};

	Planner.prototype._createAjaxPopup = function(params, next)
	{
		params['sessid'] = BX.bitrix_sessid();
		params['PLANNER_ID'] = this.getPlannerId();

		var me = this;

		BX.ajax({
			method: 'POST',
			dataType: 'html',
			url: this.ajaxUrl,
			data: params,
			onsuccess: function (HTML)
			{
				var wrapper = BX.create('div', {
					style: {"min-width": '660px'}
				});
				wrapper.innerHTML = HTML;
				wrapper.setAttribute(CRM_ACTIVITY_PLANNER_ID_ATTRIBUTE, me.getPlannerId());

				var title = '', wrapperContainer = me.getNode('wrapper-container', wrapper);
				if (wrapperContainer)
				{
					title = wrapperContainer.getAttribute('data-title');
				}

				if (!title)
					title = BX.message('CRM_ACTIVITY_PLANNER_PLANNING_TITLE');

				var buttons = [];
				if (wrapperContainer)
				{
					buttons.push(new BX.PopupWindowButton({
						text : BX.message('CRM_ACTIVITY_PLANNER_SAVE'),
						className : "popup-window-button-accept",
						events : {
							click: function() {
								me.saveActivity();
							}
						}
					}));
				}

				buttons.push(new BX.PopupWindowButtonLink({
					text : BX.message('CRM_ACTIVITY_PLANNER_CANCEL'),
					className : "popup-window-button-link-cancel",
					events : {
						click: function(){this.popupWindow.close()}
					}
				}));


				var popup = new BX.PopupWindow(me.getPlannerId(), null, {
					titleBar: title,
					content: wrapper,
					closeIcon: true,
					contentNoPaddings: true,
					zIndex: -100,
					offsetLeft: 0,
					offsetTop: 0,
					closeByEsc: true,
					draggable: {restrict: false},
					overlay: {backgroundColor: 'black', opacity: 30},
					events: {
						onPopupClose: function (popup)
						{
							me.onPopupClose();
							popup.destroy();
							BX.onCustomEvent(window, 'onActivityEditorClose', []);
						}
					},
					buttons: buttons
				});

				me.setPlannerNode(wrapper);
				me.setPopup(popup);
				next(wrapperContainer);
			}
		});
	};

	Planner.prototype.onNotifyActivatorChange = function()
	{
		var win = Planner.Manager.findByChild(this);
		var state = this.checked;
		var switcher = win.getNode('notify-switcher');
		var label = win.getNode('notify-activator-label');

		BX[state ? 'addClass' : 'removeClass'](switcher, 'crm-activity-popup-container-open');
		label.innerHTML = label.getAttribute('data-label-'+ (state ? 'y' : 'n'));
		win.setNotify(state? 15 : 0, state ? 1 : 0);
	};

	Planner.prototype.onNotifyChangeClick = function()
	{
		var win = Planner.Manager.findByChild(this);
		var content = BX.clone(win.getNode('template-notify'));
		var nodeValue = win.getNode('notify-value', content);
		var nodeValueType = win.getNode('notify-value-type', content);
		var fieldValue = win.getNode('field-notify-value');
		var fieldValueType = win.getNode('field-notify-type');


		nodeValue.value = fieldValue.value;
		nodeValueType.value = fieldValueType.value;

		var popup = new BX.PopupWindow('crm-act-win-notify-'+Math.round(Math.random() * 100000), this, {
			lightShadow : true,
			autoHide: true,
			closeByEsc: true,
			bindOptions: {position: "bottom"},
			angle: {position:'top'},
			closeIcon: false,
			events: {
				onPopupClose: function (popup)
				{
					popup.destroy();
				}
			},
			content : content
		});

		var saveBtn = win.getNode('notify-menu-save', content);
		BX.bind(saveBtn, 'click', function()
		{
			win.setNotify(nodeValue.value, nodeValueType.value);
			popup.close();
		});

		popup.show();
		return false;
	};

	Planner.prototype.synchronizeViewModeState = function()
	{
		var node = this.getNode('view-mode-switcher');
		if (node.getAttribute('data-state') === 'open')
		{
			var dn = this.getNode('detail-container');
			node.innerHTML = node.getAttribute('data-label-short');
			dn.style.maxHeight = '60px';
			dn.style.marginTop = '30px';
			dn.style.marginBottom = '15px';
			dn.style.opacity = 1;
		}
	};

	Planner.prototype.onViewModeClick = function()
	{
		var win = Planner.Manager.findByChild(this);
		var delta = this.getAttribute('data-state') == 'open' ? 1 : 0;
		var duration = parseInt(this.getAttribute('data-animation-duration')) || 250;

		/* TODO: add day calendar events sidebar.
		* "main-container" animation currently is off*/

		// var mn = win.getNode('main-container');
		var dn = win.getNode('detail-container');

		var start = {
			// mainMarginLeft: delta * 340,
			dateMaxHeight: delta * 60,
			dateMarginTop: delta * 30,
			dateMarginBottom: delta * 15,
			dateOpacity: delta * 100

		};
		var end = {
			// mainMarginLeft: 340 - delta * 340,
			dateMaxHeight: 60 - delta * 60,
			dateMarginTop: 30 - delta * 30,
			dateMarginBottom: 15 - delta * 15,
			dateOpacity: 100 - delta * 100
		};

		(new BX.easing({
			duration: duration,
			start : start,
			finish : end,
			transition: BX.easing.makeEaseInOut(BX.easing.transitions.quad),
			step: BX.delegate(function (state)
			{
				// mn.style.marginLeft = state.mainMarginLeft + 'px';
				dn.style.maxHeight = state.dateMaxHeight + 'px';
				dn.style.marginTop = state.dateMarginTop + 'px';
				dn.style.marginBottom = state.dateMarginBottom + 'px';
				dn.style.opacity = state.dateOpacity / 100;

				// win.getPopup().adjustPosition();
			}, this)
		})).animate();

		this.innerHTML = this.getAttribute('data-label-' + (delta == 0 ? 'short' : 'detail'));
		this.setAttribute('data-state', delta == 0 ? 'open' : '');

		BX.userOptions.save(
			'crm.activity.planner',
			'edit',
			'view_mode',
			(delta == 1 ? 'short' : 'detail'),
			false
		);

		return false;
	};

	Planner.prototype.onAdditionalModeClick = function()
	{
		var me = Planner.Manager.findByChild(this);
		var container = me.getNode('additional-container');

		BX.toggleClass(container, 'crm-activity-person-detail-open');
		BX.toggleClass(this, 'crm-activity-popup-info-person-link-triangle-up');

		BX.userOptions.save(
			'crm.activity.planner',
			'edit',
			'additional_mode',
			BX.hasClass(container, 'crm-activity-person-detail-open') ? 'open' : '',
			false
		);

		return false;
	};

	Planner.prototype.onDaySwitchClick = function()
	{
		var win = Planner.Manager.findByChild(this);

		var dt = new Date();
		var days = parseInt(this.getAttribute('data-day'));
		if (days > 0)
			dt.setTime(dt.getTime() + days * 86400000);

		var dateInput = win.getNode('calendar-start-time');
		dateInput.value = BX.formatDate(dt, BX.message('FORMAT_DATE'));
		BX.fireEvent(dateInput, 'change');
	};

	Planner.prototype.selectDayFromDate = function(date)
	{
		var d1 = (new Date(date.getTime())); d1.setHours(0, 0, 0, 0);
		var d2 = (new Date()); d2.setHours(0, 0, 0, 0);
		var activeDay = (d1.getTime() - d2.getTime()) / 86400000;

		var dayNodes = BX.findChildren(this.getNode('day-switcher'), {attr: 'data-day'});

		for (var i = 0, s = dayNodes.length; i < s; ++i)
		{
			var day = parseInt(dayNodes[i].getAttribute('data-day'));
			if (day == activeDay)
				BX.addClass(dayNodes[i], 'select-date-active');
			else
				BX.removeClass(dayNodes[i], 'select-date-active');
		}
	};

	Planner.prototype.onTimeSwitchClick = function()
	{
		var me = this, win = Planner.Manager.findByChild(this);

		if (!win.clockInstance)
		{
			win.clockInstance = new BX.CClockSelector({
				start_time: Planner.util.unFormatTime(this.value),
				node: this,
				callback: BX.doNothing
			});
		}

		win.clockInstance.setNode(this);
		win.clockInstance.setTime(Planner.util.unFormatTime(this.value));
		win.clockInstance.setCallback(function (v)
			{
				me.value = v;
				BX.fireEvent(me, 'change');
				win.clockInstance.closeWnd();
			});
		win.clockInstance.Show();
	};

	Planner.prototype.setNotify = function(value, type)
	{
		value = parseInt(value);
		type = parseInt(type);

		var switcher = this.getNode('notify-switcher');
		var fieldValue = this.getNode('field-notify-value');
		var fieldValueType = this.getNode('field-notify-type');

		if (value > 0)
			switcher.innerHTML = this.getFormattedPeriodLabel(value, type);

		fieldValue.value = value;
		fieldValueType.value = type;
	};

	Planner.prototype.getFormattedPeriodLabel = function(value, type)
	{
		var label = value + ' ';
		var labelIndex = 0;
		if (value > 20)
			value = (value % 10);

		if (value == 1)
			labelIndex = 0;
		else if (value > 1 && value < 5)
			labelIndex = 1;
		else
			labelIndex = 2;

		var labels = Planner.util.getPeriodLabels(type);
		return label + (labels ? labels[labelIndex] : '');
	};

	Planner.prototype.showEdit = function (params)
	{
		var me = this;
		params['ajax_action'] = 'activity_edit';
		this._createAjaxPopup(params, function(editorNode)
		{
			if (!editorNode) //show error
			{
				me.getPopup().show();
				return;
			}

			me.synchronizeViewModeState();
			BX.bind(me.getNode('view-mode-switcher'), 'click', me.onViewModeClick);
			BX.bind(me.getNode('additional-mode-switcher'), 'click', me.onAdditionalModeClick);

			// TODO: add repeat activity support
			// BX.bind(me.getNode('repeat-mode-switcher'), 'click', function(){
			// 	BX.toggleClass(me.getNode('repeat-container'), 'crm-activity-checkbox-open');
			// 	return false;
			// });
			// BX.bind(me.getNode('field-repeat-until'), 'click', onDateFieldClick);

			BX.bind(me.getNode('priority-switcher'), 'click', function(){
				BX.toggleClass(me.getNode('priority-flame'), 'crm-activity-popup-container-open');
				return false;
			});

			var i, s, daySwitcher = me.getNode('day-switcher');
			for (i = 0, s = daySwitcher.childNodes.length; i < s; ++i)
			{
				BX.bind(daySwitcher.childNodes[i], 'click', me.onDaySwitchClick);
			}

			BX.bind(me.getNode('notify-activator'), 'change', me.onNotifyActivatorChange);
			BX.bind(me.getNode('notify-switcher'), 'click', me.onNotifyChangeClick);

			me.setNotify(me.getNode('field-notify-value').value, me.getNode('field-notify-type').value);

			var onDateFieldClick = function() {
				BX.calendar({ node: this, field: this, bTime: false});
				return false;
			};

			BX.bind(me.getNode('calendar-start-time'), 'click', onDateFieldClick);
			BX.bind(me.getNode('calendar-end-time'), 'click', onDateFieldClick);

			BX.bind(me.getNode('clock-start-time'), 'click', me.onTimeSwitchClick);
			BX.bind(me.getNode('clock-end-time'), 'click', me.onTimeSwitchClick);

			BX.bind(me.getNode('calendar-start-time'), 'change', function(){me.updateStartTime();});
			BX.bind(me.getNode('clock-start-time'), 'change', function(){me.updateStartTime();});
			BX.bind(me.getNode('calendar-end-time'), 'change', function(){me.updateEndTime();});
			BX.bind(me.getNode('clock-end-time'), 'change', function(){me.updateEndTime();});

			BX.bind(me.getNode('duration-value'), 'change', function(){me.recalculateEndTime();});
			BX.bind(me.getNode('duration-type'), 'change', function(){me.recalculateEndTime();});

			var storageSwitcher = me.getNode('storage-switcher');
			if (storageSwitcher)
			{
				var storageType = parseInt(storageSwitcher.getAttribute('data-storage-type'));
				var storageValues = JSON.parse(storageSwitcher.getAttribute('data-values'));
				var storageProps = JSON.parse(storageSwitcher.getAttribute('data-props'));

				//TODO: webdav & files
				if (storageType === Planner.util.storageType.Disk)
					me.createDiskUploader(storageValues, me.getNode('storage-container'));
			}

			var destinationEntities = JSON.parse(me.getNode('destination-entities').value);

			var destinationContainerTpl = me.getNode('template-destination-container');
			var destinationItemTpl = me.getNode('template-destination-item');

			var dealContainerNode = me.getNode('deal-container');
			if (dealContainerNode)
			{
				me.dealDestination = new Destination(
					dealContainerNode,
					'deal',
					{
						containerTpl: destinationContainerTpl,
						itemTpl: destinationItemTpl,
						valueInputName: 'dealId',
						selected: destinationEntities.deal,
						selectOne: true
					}
				);
			}

			var responsibleContainernode = me.getNode('responsible-container');
			if (responsibleContainernode)
			{
				me.responsibleDestination = new Destination(
					me.getNode('responsible-container'),
					'responsible',
					{
						containerTpl: destinationContainerTpl,
						itemTpl: destinationItemTpl,
						valueInputName: 'responsibleId',
						selected: destinationEntities.responsible,
						selectOne: true,
						required: true,
						events: {
							select: function(params)
							{
								me.checkPlannerState(params);
							}
						}
					}
				);
			}

			var communicationsNode = me.getNode('communications-container');
			if (communicationsNode)
			{
				me.communications = new Communications(
					communicationsNode,
					{
						entityType: me.getNodeValue('field-owner-type'),
						entityId: me.getNodeValue('field-owner-id'),
						containerTpl: destinationContainerTpl,
						itemTpl: destinationItemTpl,
						selected: JSON.parse(me.getNode('communications-data').value),
						selectOne: true,
						communicationType: me.getNode('communications-container').getAttribute('data-communication-type')
					}
				);
			}

			me.getPopup().show();

			//after show
			var focusInput = me.getNode('focus-on-show');
			if (focusInput)
				BX.defer(BX.focus)(focusInput);

			me.refreshDateTimeView();
		});

		// planner events
		BX.addCustomEvent('OnCalendarPlannerSelectorChanged', function(params)
		{
			me.skipPlannerRefresh = true;
			me.setStartTime(params.dateFrom);
			params.dateTo ? me.setEndTime(params.dateTo) : me.recalculateEndTime(true);
			me.refreshDateTimeView();
			me.skipPlannerRefresh = false;
		});

		BX.addCustomEvent('OnCalendarPlannerScaleChanged', function(params)
		{
			me.updatePlanner({
				users: params.entrieIds,
				entries: params.entries,
				from: params.from,
				to: params.to,
				focusSelector: params.focusSelector === true
			});
		});


		return false;
	};

	Planner.prototype.onPopupClose = function()
	{
		if (this.communications)
		{
			this.communications.onPlannerClose();
		}
		if (this.dealDestination)
		{
			this.dealDestination.onPlannerClose();
		}
		if (this.responsibleDestination)
		{
			this.responsibleDestination.onPlannerClose();
		}
	};

	Planner.prototype.saveActivity = function()
	{
		var me = this;

		var startTime = me.getStartTime();
		var endTime = me.getEndTime();
		var providerType = this.getNodeValue('field-provider-type-id');

		if (startTime && endTime && startTime.getTime() > endTime.getTime())
		{
			alert(BX.message('CRM_ACTIVITY_PLANNER_DATES_ERR'));
			return;
		}

		if (this.saveInProgress)
			return;

		this.saveInProgress = true;

		var activityData = BX.ajax.prepareForm(this.getNode('form')).data;

		var storageSwitcher = me.getNode('storage-switcher');
		if (storageSwitcher)
		{
			var storageType = parseInt(storageSwitcher.getAttribute('data-storage-type'));

			if (storageType === Planner.util.storageType.Disk && this.diskUploader)
			{
				activityData['diskfiles'] = this.diskUploader.getFileIds();
			}
		}

		if(me.communications && me.communications.items.length > 0)
		{
			activityData['communication'] = me.communications.items[0];
		}

		var hasOwner = activityData['dealId'] || activityData['ownerId'] && activityData['ownerType'];
		if (!hasOwner && activityData['communication'] && activityData['communication']['entityId'] > 0)
		{
			hasOwner = true;
		}

		if (!hasOwner && providerType !== 'CALL_LIST')
		{
			alert(BX.message('CRM_ACTIVITY_PLANNER_NO_OWNER'));
			me.saveInProgress = false;
			return;
		}

		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: this.ajaxUrl,
			data: {
				ajax_action: 'ACTIVITY_SAVE',
				data: activityData,
				sessid: BX.bitrix_sessid()
			},
			onsuccess: function (response)
			{
				me.saveInProgress = false;
				if (response.SUCCESS)
				{
					me.getPopup().close();
					BX.onCustomEvent(me, 'onAfterActivitySave', [response.DATA.ACTIVITY]);
					Planner.Manager.fireEvent('onAfterActivitySave', {}, me);
				}
				else
				{
					alert(response.ERRORS[0]);
				}
			}
		});
	};

	Planner.prototype.getDurationValue = function()
	{
		var value = parseInt(this.getNode('duration-value').value);
		var type = parseInt(this.getNode('duration-type').value);

		if (isNaN(value))
			value = 0;
		if (isNaN(type))
			type = 1; //minutes

		if (type === 2) //hours
			value *= 60;
		if (type === 3) //days
			value *= 60*24;

		return value * 60 * 1000;
	};

	/**
	 * @param {Date} date
	 * @param {bool} [silent]
	 */
	Planner.prototype.setStartTime = function(date, silent)
	{
		date.setSeconds(0); //ignore seconds
		var fieldNode = this.getNode('field-start-time');
		fieldNode.value = Planner.util.convertDateTime(date);
		BX.fireEvent(fieldNode, 'change');

		if (!silent)
		{
			this.recalculateEndTime();
		}
	};

	Planner.prototype.getStartTime = function()
	{
		return BX.parseDate(this.getNode('field-start-time').value);
	};

	Planner.prototype.updateStartTime = function()
	{
		var dt = BX.parseDate(this.getNode('calendar-start-time').value);
		dt.setTime(dt.getTime() + Planner.util.unFormatTime(this.getNode('clock-start-time').value) * 1000);
		this.setStartTime(dt);
		this.selectDayFromDate(dt);
	};

	/**
	 * @param {Date} date
	 * @param {bool} [silent]
	 */
	Planner.prototype.setEndTime = function(date, silent)
	{
		date.setSeconds(0); //ignore seconds
		var fieldNode = this.getNode('field-end-time');
		fieldNode.value = Planner.util.convertDateTime(date);
		BX.fireEvent(fieldNode, 'change');

		if (!silent)
		{
			this.recalculateDuration();
		}
	};

	Planner.prototype.getEndTime = function()
	{
		return BX.parseDate(this.getNode('field-end-time').value);
	};

	Planner.prototype.updateEndTime = function()
	{
		var dt = BX.parseDate(this.getNode('calendar-end-time').value);
		dt.setTime(dt.getTime() + Planner.util.unFormatTime(this.getNode('clock-end-time').value) * 1000);
		this.setEndTime(dt);
	};

	Planner.prototype.recalculateEndTime = function(silent)
	{
		var dt = this.getStartTime();
		dt.setTime(dt.getTime() + this.getDurationValue());
		this.setEndTime(dt, true);

		if (!silent)
		{
			this.refreshEndTimeView();
			this.refreshPlannerState({refreshIfShown: true});
		}

		return dt;
	};

	Planner.prototype.recalculateDuration = function()
	{
		var d1 = this.getEndTime();
		var d2 = this.getStartTime();
		var value = Math.floor((d1.getTime() - d2.getTime()) / 60000); //min
		var type = 1; //min

		if (value % 1440 == 0) //60 * 24
		{
			value = value / 1440;
			type = 3;
		}
		else if (value % 60 === 0)
		{
			value = value / 60;
			type = 2;
		}

		this.getNode('duration-value').value = value > 0 ? value : '';
		this.getNode('duration-type').value = type;
	};

	Planner.prototype.refreshStartTimeView = function()
	{
		var dt = this.getStartTime();
		if (!dt)
		{
			dt = new Date();
			var minutes = dt.getMinutes(),
				mod = minutes % 5;

			if (mod > 0)
			{
				dt.setMinutes(minutes - mod + (mod > 2 ? 5 : 0));
			}
			this.setStartTime(dt);
		}
		var dateInput = this.getNode('calendar-start-time');
		dateInput.value = BX.formatDate(dt, BX.message('FORMAT_DATE'));
		var timeInput = this.getNode('clock-start-time');
		timeInput.value = Planner.util.formatTime(dt);

		this.selectDayFromDate(dt);
	};

	Planner.prototype.refreshEndTimeView = function()
	{
		var dt = this.getEndTime();
		if (!dt)
		{
			dt = this.recalculateEndTime(true);
		}
		var dateInput = this.getNode('calendar-end-time');
		dateInput.value = BX.formatDate(dt, BX.message('FORMAT_DATE'));
		var timeInput = this.getNode('clock-end-time');
		timeInput.value = Planner.util.formatTime(dt);
	};

	Planner.prototype.refreshDateTimeView = function()
	{
		this.refreshStartTimeView();
		this.refreshEndTimeView();
		this.recalculateDuration();
	};

	Planner.prototype.getPlannerContainer = function (params)
	{
		return BX('calendar-planner-outer' + this.getPlannerId(), true);
	};

	Planner.prototype.checkPlannerState = function (params)
	{
		var
			me = this,
			dayLength = 86400000,
			updateParams = {users: []},
			fromDate = this.getStartTime(),
			toDate = this.getEndTime();

		if (this.checkPlannerTimeout)
		{
			this.checkPlannerTimeout = !!clearTimeout(this.checkPlannerTimeout);
		}

		if (!fromDate && !toDate)
		{
			this.checkPlannerTimeout = setTimeout(function(){me.checkPlannerState(params);}, 100);
			return;
		}

		if (params.item && params.item.entityId > 0 && params.item.entityType == 'users')
			updateParams.users.push(params.item.entityId);

		if (
			fromDate && toDate &&
			updateParams.users.length > 0
		)
		{
			updateParams.from = BX.formatDate(new Date((fromDate.getTime() - dayLength * this.loadOffsetLeft)), BX.message('FORMAT_DATE'));
			updateParams.to = BX.formatDate(new Date((fromDate.getTime() + dayLength * this.loadOffsetRight)), BX.message('FORMAT_DATE'));
			this.updatePlanner(updateParams);
		}
	};

	Planner.prototype.updatePlanner = function(params)
	{
		var me = this;

		me.ajaxProgress = true;
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: me.ajaxUrl,
			data: {
				sessid: BX.bitrix_sessid(),
				ajax_action: 'PLANNER_UPDATE',
				from: params.from,
				to: params.to,
				entries: params.users
			},
			onsuccess: function (response)
			{
				var data = response.DATA || {};

				var
					showPlanner = true,
					plannerShown = BX.hasClass(me.getPlannerContainer(), 'crm-activity-popup-calendar-planner-wrap-shown');

				if (showPlanner)
				{
					var refreshParams = {
						show: showPlanner && !plannerShown
					};

					if (params.entries)
					{
						data.entries = params.entries;
						refreshParams.scaleFrom = params.from;
						refreshParams.scaleTo = params.to;
					}

					refreshParams.loadedDataFrom = params.from;
					refreshParams.loadedDataTo = params.to;
					refreshParams.data = data;
					refreshParams.focusSelector = refreshParams.show ? true : (params.focusSelector == undefined ? false : params.focusSelector);
					me.refreshPlannerState(refreshParams);
				}
				me.ajaxProgress = false;
			}
		});
	};

	Planner.prototype.refreshPlannerState = function(params)
	{
		var me = this;

		if (!window.CalendarPlanner)
		{
			if (this.refreshPlannerStateTimeout)
				this.refreshPlannerStateTimeout = !!clearTimeout(this.refreshPlannerStateTimeout);

			this.refreshPlannerStateTimeout = setTimeout(function(){me.refreshPlannerState(params);}, 200);
		}

		if (!params || typeof params !== 'object')
			params = {};

		var
			plannerId = 'calendar_planner_' + this.getPlannerId(),
			fromDate, toDate,
			config = {
				scaleType: '1hour',
				showTimelineDayTitle: true,
				compactMode: true,
				width: 600,
				minWidth: 600,
				minHeight: 104,
				height: 104
			},
			plannerShown = BX.hasClass(me.getPlannerContainer(), 'crm-activity-popup-calendar-planner-wrap-shown');

		if(!plannerShown && params.refreshIfShown || this.skipPlannerRefresh === true)
		{
			return;
		}

		if (!plannerShown && params.show)
		{
			BX.addClass(me.getPlannerContainer(), 'crm-activity-popup-calendar-planner-wrap-shown');
		}

		if (params.focusSelector == undefined)
			params.focusSelector = true;

		fromDate = this.getStartTime();
		toDate = this.getEndTime();

		if (fromDate && toDate &&
			fromDate.getTime && toDate.getTime &&
			fromDate.getTime() <= toDate.getTime())
		{
			if (!plannerShown && !params.data)
			{
				this.checkPlannerState();
			}
			else
			{
				BX.onCustomEvent('OnCalendarPlannerDoUpdate', [
					{
						plannerId: plannerId,
						config: config,
						focusSelector: params.focusSelector,
						selector: {
							from: fromDate,
							to: toDate,
							fullDay: false,
							animation: true,
							updateScaleLimits: true
						},
						data: params.data || false,
						loadedDataFrom: params.loadedDataFrom,
						loadedDataTo: params.loadedDataTo,
						show: !!params.show
					}
				]);
			}
		}
	};

	Planner.prototype.createDiskUploader = function(values, layout)
	{
		var me = this;

		if (!BX.CrmDiskUploader)
		{
			if (this.diskUploaderTimeout)
				this.diskUploaderTimeout = !!clearTimeout(this.diskUploaderTimeout);

			this.diskUploaderTimeout = setTimeout(function(){me.createDiskUploader(values, layout);}, 100);
			return;
		}

		me.diskUploader = BX.CrmDiskUploader.create(
			'',
			{
				msg:
				{
					'diskAttachFiles' : BX.message('CRM_ACTIVITY_PLANNER_DISK_ATTACH_FILE'),
					'diskAttachedFiles' : BX.message('CRM_ACTIVITY_PLANNER_DISK_ATTACHED_FILES'),
					'diskSelectFile' : BX.message('CRM_ACTIVITY_PLANNER_DISK_SELECT_FILE'),
					'diskSelectFileLegend' : BX.message('CRM_ACTIVITY_PLANNER_DISK_SELECT_FILE_LEGEND'),
					'diskUploadFile' : BX.message('CRM_ACTIVITY_PLANNER_DISK_UPLOAD_FILE'),
					'diskUploadFileLegend' : BX.message('CRM_ACTIVITY_PLANNER_DISK_UPLOAD_FILE_LEGEND')
				}
			}
		);

		me.diskUploader.setMode(1); //edit
		me.diskUploader.setValues(values);
		me.diskUploader.layout(layout);
	};

	var PlannerToolbar = {
		menuId: 'crm-act-pltlb',
		actions: [],
		actionNode: null,
		openerNode: null,

		setActions: function(actions)
		{
			if (actions && actions instanceof Array)
			{
				this.actions = actions;
			}
		},
		bindActionNode: function(node)
		{
			this.actionNode = node;
			BX.bind(node, 'click', BX.delegate(this.onDefaultActionClick, this));
		},
		bindOpenerNode: function(node)
		{
			this.openerNode = node;
			BX.bind(node, 'click', BX.delegate(this.onOpenerClick, this));
		},
		bindNodes: function(config)
		{
			if (!config)
				config = {};

			if (config.action)
				this.bindActionNode(config.action);
			if (config.opener)
				this.bindOpenerNode(config.opener)
		},
		onDefaultActionClick: function(e)
		{
			this.executeActionById(
				this.getDefaultActionId()
			);

			return BX.PreventDefault(e);
		},
		onOpenerClick: function(e)
		{
			var me = this, i, menuItems = [];
			for (i = 0; i < this.actions.length; ++i)
			{
				menuItems.push({
					text: this.actions[i].text,
					actionId: this.actions[i].id,
					onclick: function(e, item)
					{
						me.executeActionById(item.actionId);
						if (me.actionNode && BX.type.isString(item.text))
							me.actionNode.innerHTML = BX.util.htmlspecialchars(item.text);
						return BX.PreventDefault(e);
					}
				});
			}

			BX.PopupMenu.show(
				this.menuId,
				this.openerNode,
				menuItems,
				{
					autoHide: true,
					offsetLeft: (BX.pos(this.openerNode)['width'] / 2),
					angle: { position: 'top', offset: 0 }
				}
			);

			return BX.PreventDefault(e);
		},

		executeActionById: function(id)
		{
			var i, action;
			id = id.toString();

			for (i = 0; i < this.actions.length; ++i)
			{
				if (this.actions[i].id === id)
				{
					action = this.actions[i];
					break;
				}
			}

			if (action)
			{
				(new BX.Crm.Activity.Planner()).showEdit(action.params);
				BX.PopupMenu.destroy(this.menuId);
				this.setDefaultActionId(id);
			}
		},
		getDefaultActionId: function()
		{
			var id = '';
			if (this.actionNode)
			{
				id = this.actionNode.getAttribute('data-action-id').toString();
			}
			return id;
		},
		setDefaultActionId: function(id)
		{
			id = id.toString();
			var needToSave = false;
			if (this.actionNode)
			{
				var oldId = this.actionNode.getAttribute('data-action-id');
				this.actionNode.setAttribute('data-action-id', id);
				if (oldId !== id)
					needToSave = true;
			}

			if (needToSave)
			{
				BX.userOptions.save(
					'crm.interface.toolbar',
					'activity_planner',
					'default_action_id',
					id,
					false
				);
			}

			return this;
		}
	};

	BX.Crm.Activity.Planner = Planner;
	BX.Crm.Activity.PlannerToolbar = PlannerToolbar;

	// -> Destination
	var Destination = function(container, type, config)
	{
		var me = this, tagNode;
		if (!config)
			config = {};

		this.bindContainer = container;
		this.ajaxUrl = config.ajaxUrl || DEFAULT_AJAX_URL;
		this.itemTpl = config.itemTpl;

		this.data = null;
		this.type = type;
		this.dialogId = 'crm-aw-dest-' + type + ('' + new Date().getTime()).substr(6);
		this.valueInputName = config.valueInputName || '';
		this.selected = config.selected ? BX.clone(config.selected) : [];
		this.crmTypes = config.crmTypes;
		this.selectOne = config.selectOne || false;
		this.required = config.required || false;
		this.events = config.events || {};

		this.bindContainer.appendChild(BX.clone(config.containerTpl));
		tagNode = this.getNode('destination-tag');

		BX.bind(tagNode, 'focus', function(e) {
			me.openDialog({bByFocusEvent: true});
			return BX.PreventDefault(e);
		});
		BX.bind(this.bindContainer, 'click', function(e) {
			me.openDialog();
			return BX.PreventDefault(e);
		});

		this.addItems(this.selected);

		tagNode.innerHTML = (
			this.selected.length <= 0
				? BX.message('CRM_ACTIVITY_PLANNER_DEST_1')
				: BX.message('CRM_ACTIVITY_PLANNER_DEST_2')
		);
	};

	Destination.prototype.getNode = function(name, scope)
	{
		if (!scope)
			scope = this.bindContainer;

		return scope ? scope.querySelector('[data-role="'+name+'"]') : null;
	};

	Destination.prototype.getData = function(next)
	{
		var me = this;

		if (me.ajaxProgress)
			return;

		me.ajaxProgress = true;
		BX.ajax({
			method: 'POST',
			dataType: 'json',
			url: me.ajaxUrl,
			data: {
				sessid: BX.bitrix_sessid(),
				ajax_action: 'get_destination_data',
				type: me.type
			},
			onsuccess: function (response)
			{
				me.data = response.DATA || {};
				me.ajaxProgress = false;
				me.initDialog(next);
			}
		});
	};

	Destination.prototype.initDialog = function(next)
	{
		var me = this, data = this.data;

		if (!data)
		{
			me.getData(next);
			return;
		}

		var itemsSelected = {};
		for (var i = 0; i < me.selected.length; ++i)
		{
			itemsSelected[me.selected[i].id] = me.selected[i].entityType
		}

		var items = {}, itemsLast = {}, destSort =  data.DEST_SORT || {};

		if (this.type === 'responsible')
		{
			items = {
				users : data.USERS || {},
				department : data.DEPARTMENT || {},
				departmentRelation : data.DEPARTMENT_RELATION || {}
			};
			itemsLast =  {
				users: data.LAST.USERS || {}
			};

			if (!items["departmentRelation"])
			{
				items["departmentRelation"] = BX.SocNetLogDestination.buildDepartmentRelation(items["department"]);
			}
		}

		var isCrmFeed = false;
		var searchUrl = null;

		if (this.type === 'deal')
		{
			isCrmFeed = true;
			items = {
				deals : data.DEALS || {}
			};
			itemsLast =  {
				deals: data.LAST.DEALS || {},
				crm: []
			};
			searchUrl = DEFAULT_AJAX_URL + '&ajax_action=SEARCH_DESTINATION_DEALS';
		}

		if (!me.inited)
		{
			me.inited = true;
			var destinationInput = me.getNode('destination-input');
			destinationInput.id = me.dialogId + 'input';

			var destinationInputBox = me.getNode('destination-input-box');
			destinationInputBox.id = me.dialogId + 'input-box';

			var tagNode = this.getNode('destination-tag');
			tagNode.id = this.dialogId + 'tag';

			var itemsNode = me.getNode('destination-items');

			BX.SocNetLogDestination.init({
				pathToAjax: searchUrl,
				name : me.dialogId,
				searchInput : me.getNode('destination-input'),
				extranetUser :  false,
				bindMainPopup : {node: me.bindContainer, offsetTop: '5px', offsetLeft: '15px'},
				bindSearchPopup : {node: me.bindContainer, offsetTop : '5px', offsetLeft: '15px'},
				departmentSelectDisable: true,
				sendAjaxSearch: true,
				callback : {
					select : function(item, type, search, bUndeleted)
					{
						me.addItem(item, type);
						if (me.selectOne)
							BX.SocNetLogDestination.closeDialog();
					},
					unSelect : BX.delegate(BX.SocNetLogDestination.BXfpUnSelectCallback, {
						formName: me.dialogId,
						inputContainerName: itemsNode,
						inputName: destinationInput.id,
						tagInputName: tagNode.id,
						tagLink1: BX.message('CRM_ACTIVITY_PLANNER_DEST_1'),
						tagLink2: BX.message('CRM_ACTIVITY_PLANNER_DEST_2')
					}),
					openDialog : BX.delegate(BX.SocNetLogDestination.BXfpOpenDialogCallback, {
						inputBoxName: destinationInputBox.id,
						inputName: destinationInput.id,
						tagInputName: tagNode.id
					}),
					closeDialog : BX.delegate(BX.SocNetLogDestination.BXfpCloseDialogCallback, {
						inputBoxName: destinationInputBox.id,
						inputName: destinationInput.id,
						tagInputName: tagNode.id
					}),
					openSearch : BX.delegate(BX.SocNetLogDestination.BXfpOpenDialogCallback, {
						inputBoxName: destinationInputBox.id,
						inputName: destinationInput.id,
						tagInputName: tagNode.id
					}),
					closeSearch : BX.delegate(BX.SocNetLogDestination.BXfpCloseSearchCallback, {
						inputBoxName: destinationInputBox.id,
						inputName: destinationInput.id,
						tagInputName: tagNode.id
					})
				},
				items : items,
				itemsLast : itemsLast,
				itemsSelected : itemsSelected,
				isCrmFeed : isCrmFeed,
				useClientDatabase: false,
				destSort: destSort,
				allowAddUser: false
			});

			BX.bind(destinationInput, 'keyup', BX.delegate(BX.SocNetLogDestination.BXfpSearch, {
				formName: me.dialogId,
				inputName: destinationInput.id,
				tagInputName: tagNode.id
			}));
			BX.bind(destinationInput, 'keydown', BX.delegate(BX.SocNetLogDestination.BXfpSearchBefore, {
				formName: me.dialogId,
				inputName: destinationInput.id
			}));

			BX.SocNetLogDestination.BXfpSetLinkName({
				formName: me.dialogId,
				tagInputName: tagNode.id,
				tagLink1: BX.message('CRM_ACTIVITY_PLANNER_DEST_1'),
				tagLink2: BX.message('CRM_ACTIVITY_PLANNER_DEST_2')
			});
		}
		next();
	};

	Destination.prototype.addItem = function(item, type)
	{
		var me = this;
		var destinationInput = this.getNode('destination-input');
		var tagNode = this.getNode('destination-tag');
		var items = this.getNode('destination-items');
		var container = BX.clone(this.itemTpl);

		if (!BX.findChild(items, { attr : { 'data-id' : item.id }}, false, false))
		{
			if (me.selectOne && me.inited)
			{
				var toRemove = [];
				for (var i = 0; i < items.childNodes.length; ++i)
				{
					toRemove.push({
						itemId: items.childNodes[i].getAttribute('data-id'),
						itemType: items.childNodes[i].getAttribute('data-type')
					})
				}

				me.initDialog(function() {
					for (var i = 0; i < toRemove.length; ++i)
					{
						BX.SocNetLogDestination.deleteItem(toRemove[i].itemId, toRemove[i].itemType, me.dialogId);
					}
				});

				BX.cleanNode(items);
			}

			container.setAttribute('data-id', item.id);
			container.setAttribute('data-type', type);
			BX.addClass(container, container.getAttribute('data-class-prefix') + (me.type == 'responsible' ? 'users' : 'crm'));

			var containerText = this.getNode('text', container);
			var containerDelete = this.getNode('delete', container);
			var containerValue = this.getNode('value', container);

			containerText.innerHTML = BX.type.isString(item.name) ? BX.util.htmlspecialchars(item.name) : '';

			BX.bind(containerDelete, 'click', function(e) {
				if (me.selectOne && me.required)
				{
					me.openDialog();
				}
				else
				{
					me.initDialog(function() {
						BX.SocNetLogDestination.deleteItem(item.id, type, me.dialogId);
						BX.remove(container);
					});
				}
				BX.PreventDefault(e);
			});

			BX.bind(containerDelete, 'mouseover', function(){
				BX.addClass(this.parentNode, this.getAttribute('data-hover-class'));
			});

			BX.bind(containerDelete, 'mouseout', function(){
				BX.removeClass(this.parentNode, this.getAttribute('data-hover-class'));
			});

			containerValue.name = me.valueInputName;
			containerValue.value = item.entityId;

			items.appendChild(container);

			if (!item.entityType)
				item.entityType = type;

			this.fireEvent('select', {item: item});
		}

		destinationInput.value = '';
		tagNode.innerHTML = BX.message('CRM_ACTIVITY_PLANNER_DEST_2');
	};

	Destination.prototype.addItems = function(items)
	{
		for(var i = 0; i < items.length; ++i)
		{
			this.addItem(items[i], items[i].entityType)
		}
	};

	Destination.prototype.openDialog = function(params)
	{
		var me = this;
		this.initDialog(function()
		{
			BX.SocNetLogDestination.openDialog(me.dialogId, params);
		})
	};
	Destination.prototype.fireEvent = function(eventName, params)
	{
		if (typeof this.events[eventName] === 'function')
		{
			this.events[eventName].call(this, params);
		}
	};
	Destination.prototype.onPlannerClose = function()
	{
		if (this.inited)
		{
			if (BX.SocNetLogDestination.isOpenDialog())
			{
				BX.SocNetLogDestination.closeDialog();
			}
			BX.SocNetLogDestination.closeSearch();
		}
	};
	// <- Destination

	// Communications ->
	var Communications = function(container, config)
	{
		this.id = 'crm-actpl-comm-' + ('' + new Date().getTime()).substr(6);
		this.items = [];

		var me = this;
		if (!config)
			config = {};

		this.bindContainer = container;
		this.ajaxUrl = config.ajaxUrl || COMMUNICATIONS_AJAX_URL;
		this.itemTpl = config.itemTpl;

		this.selectOne = config.selectOne || false;

		this.bindContainer.appendChild(BX.clone(config.containerTpl));
		var tagNode = this.getNode('destination-tag');

		BX.bind(tagNode, 'focus', function(e) {
			me.openDialog();
			return BX.PreventDefault(e);
		});
		BX.bind(this.bindContainer, 'click', function(e) {
			me.openDialog();
			return BX.PreventDefault(e);
		});

		var communicationType = BX.CrmCommunicationType.undefined;
		if (config.communicationType === 'PHONE')
			communicationType = BX.CrmCommunicationType.phone;
		if (config.communicationType === 'EMAIL')
			communicationType = BX.CrmCommunicationType.email;

		if(typeof(BX.CrmCommunicationSearch.messages) === 'undefined')
		{
			BX.CrmCommunicationSearch.messages =
			{
				SearchTab: BX.message('CRM_ACTIVITY_PLANNER_COMMUNICATION_SEARCH_TAB'),
				NoData: BX.message('CRM_ACTIVITY_PLANNER_COMMUNICATION_SEARCH_NO_DATA')
			}
		}

		this._communicationSearch = BX.CrmCommunicationSearch.create(this.id, {
			entityType : config.entityType,
			entityId: config.entityId,
			serviceUrl: me.ajaxUrl,
			communicationType:  communicationType,
			selectCallback: BX.delegate(this.selectCommunication, this),
			enableSearch: true,
			enableDataLoading: true,
			dialogAutoHide: true
		});

		if (communicationType === BX.CrmCommunicationType.phone)
		{
			var input = this.getNode('destination-input');
			BX.bind(input, 'keypress', BX.delegate(this.inputKeypress, this));
		}

		this.addItems(config.selected ? BX.clone(config.selected) : []);
	};
	Communications.prototype.inputKeypress = function(e)
	{
		if(!e)
			e = window.event;

		if(e.keyCode !== 13)
			return;

		var input = this.getNode('destination-input');

		if(BX.type.isNotEmptyString(input.value))
		{
			var rx = /^\s*\+?[\d-\s\(\)]+\s*$/;
			if (rx.test(input.value))
			{
				this.addItem(
					{
						entityId: '0',
						entityTitle: '',
						entityType: 'CONTACT',
						type: 'PHONE',
						value: input.value
					},
					true
				);
			}
		}
	};

	Communications.prototype.getNode = function(name, scope)
	{
		if (!scope)
			scope = this.bindContainer;

		return scope ? scope.querySelector('[data-role="'+name+'"]') : null;
	};

	Communications.prototype.selectCommunication = function(communication)
	{
		this.addItem(communication.getSettings(), true);
	};

	Communications.prototype.addItem = function(item, closeDialog)
	{
		if (item.type === null)
			item.type = '';

		if (item.type === '' && item.value === null)
			item.value = '';

		for(var i = 0; i < this.items.length; ++i)
		{
			if (
				this.items[i].type === item.type
				&& this.items[i].value === item.value
				&& this.items[i].entityId === item.entityId
				&& this.items[i].entityType === item.entityType
			)
				return;
		}

		var me = this, itemsNode = this.getNode('destination-items');

		if (this.selectOne)
		{
			this.items = [];
			BX.cleanNode(itemsNode);
		}

		this.items.push(item);

		var container = BX.clone(this.itemTpl);
		BX.addClass(container, container.getAttribute('data-class-prefix') + 'crm');

		var containerText = this.getNode('text', container);
		var containerDelete = this.getNode('delete', container);

		containerText.innerHTML = [
			BX.type.isString(item.entityTitle) ? BX.util.htmlspecialchars(item.entityTitle) : '',
			BX.type.isString(item.value) ? BX.util.htmlspecialchars(item.value) : ''
		].join(' ');

		BX.bind(containerDelete, 'click', function(e) {
			me.deleteItem(item);
			BX.remove(container);
			BX.PreventDefault(e)
		});

		BX.bind(containerDelete, 'mouseover', function(){
			BX.addClass(this.parentNode, this.getAttribute('data-hover-class'));
		});

		BX.bind(containerDelete, 'mouseout', function(){
			BX.removeClass(this.parentNode, this.getAttribute('data-hover-class'));
		});

		itemsNode.appendChild(container);

		var tagNode = this.getNode('destination-tag');
		tagNode.innerHTML = BX.message('CRM_ACTIVITY_PLANNER_DEST_2');
		if (closeDialog)
			this._communicationSearch.closeDialog();
	};

	Communications.prototype.addItems = function(items)
	{
		for(var i = 0; i < items.length; ++i)
		{
			this.addItem(items[i], items[i].entityType)
		}
		var tagNode = this.getNode('destination-tag');

		tagNode.innerHTML = (
			items.length <= 0
				? BX.message('CRM_ACTIVITY_PLANNER_DEST_1')
				: BX.message('CRM_ACTIVITY_PLANNER_DEST_2')
		);
	};

	Communications.prototype.deleteItem = function(item)
	{
		for(var i = 0; i < this.items.length; ++i)
		{
			if (this.items[i] === item)
				this.items.splice(i, 1);
		}
		return this;
	};

	Communications.prototype.openDialog = function()
	{
		var inputBox = this.getNode('destination-input-box');
		var input = this.getNode('destination-input');
		var tagNode = this.getNode('destination-tag');

		BX.style(inputBox, 'display', 'inline-block');
		BX.style(tagNode, 'display', 'none');

		this._communicationSearchController = BX.CrmCommunicationSearchController.create(this._communicationSearch, input);

		this._communicationSearchController.start();
		this._communicationSearch.openDialog(this.bindContainer, BX.delegate(this.closeDialog, this));

		BX.defer(BX.focus)(input);
	};
	Communications.prototype.closeDialog = function()
	{
		var inputBox = this.getNode('destination-input-box');
		var input = this.getNode('destination-input');
		var tagNode = this.getNode('destination-tag');

		if(this._communicationSearchController)
		{
			this._communicationSearchController.stop();
			this._communicationSearchController = null;
		}

		BX.style(tagNode, 'display', 'inline-block');
		BX.style(inputBox, 'display', 'none');
		input.value = '';
	};

	Communications.prototype.onPlannerClose = function()
	{
		this._communicationSearch.closeDialog();
	};
	// <- Communications

	// Temporary use BX.CrmActivityProvider as proxy for old editor.
	if(typeof(BX.CrmActivityProvider) == 'undefined')
	{
		BX.CrmActivityProvider = function ()
		{
			this._settings = {};
			this._options = {};
			this._ttlWrapper = null;
			this._dlg = null;
			this._dlgMode = BX.CrmDialogMode.view;
			this._dlgCfg = {};
			this._onSaveHandlers = [];
			this._onDlgCloseHandlers = [];
			this._editor = null;
			this._isChanged = false;
			this._buttonId = BX.CrmActivityDialogButton.undefined;
			this._owner = null;
			this._salt = '';
			this._callCreationHandler = BX.delegate(this._handleCallCreation, this);
			this._meetingCreationHandler = BX.delegate(this._handleMeetingCreation, this);
			this._emailCreationHandler = BX.delegate(this._handleEmailCreation, this);
			this._taskCreationHandler = BX.delegate(this._handleTaskCreation, this);
			this._titleMenu = null;
			this._contentNode = null;
			this._activityOptions = {};
			this._parentActivity = null;
		};

		BX.CrmActivityProvider.prototype =
		{
			initialize: function (settings, editor, options, parentActivity)
			{
				this._settings = settings ? settings : {};
				this._editor = editor;
				this._options = options ? options : {};

				this._isChanged = this.getOption('markChanged', false);

				var ownerType = this.getSetting('ownerType', '');
				var ownerID = this.getSetting('ownerID', '');
				this._salt = Math.random().toString().substring(2);

				this._parentActivity = parentActivity;
			},
			getMode: function ()
			{
				return this._dlgMode;
			},
			getSetting: function (name, defaultval)
			{
				return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
			},
			setSetting: function (name, val)
			{
				this._settings[name] = val;
			},
			getOption: function (name, defaultval)
			{
				return typeof(this._options[name]) != 'undefined' ? this._options[name] : defaultval;
			},
			getMessage: function (name)
			{
				return BX.CrmActivityProvider.messages && BX.CrmActivityProvider.messages[name] ? BX.CrmActivityProvider.messages[name] : '';
			},
			getType: function ()
			{
				return this.getSetting('typeID', BX.CrmActivityType.provider);
			},
			getId: function ()
			{
				return parseInt(this.getSetting('ID', '0'));
			},
			getMessageType: function ()
			{
				return this.getSetting('messageType', '');
			},
			getOwnerType: function ()
			{
				return this.getSetting('ownerType', '');
			},
			getOwnerId: function ()
			{
				return this.getSetting('ownerID', '');
			},
			openDialog: function (mode)
			{
				var id = this.getId();

				if (id <= 0)
					throw 'empty activity ID';

				if (mode === BX.CrmDialogMode.edit)
				{
					(new BX.Crm.Activity.Planner()).showEdit({ID:id});
					return true;
				}

				this._dlgMode = mode;

				var dlgId = 'CrmActivityProviderView' + id;

				if (BX.CrmActivityProvider.dialogs[dlgId])
				{
					return;
				}

				var params = {
					sessid: BX.bitrix_sessid(),
					ajax_action: 'ACTIVITY_VIEW',
					activity_id: id
				};

				var self = this;

				BX.ajax({
					method: 'POST',
					dataType: 'html',
					url: '/bitrix/components/bitrix/crm.activity.planner/ajax.php?site_id=' + BX.message('SITE_ID'),
					data: params,
					onsuccess: function (HTML)
					{
						var wrapper = BX.create('div');
						wrapper.innerHTML = HTML;
						self._contentNode = wrapper;

						var optionsNode = self._getNode('options');
						if (optionsNode)
						{
							self._activityOptions = JSON.parse(optionsNode.getAttribute('data-options'));
							if (!self._activityOptions || typeof(self._activityOptions) !== 'object')
								self._activityOptions = {};
						}

						self._dlg = new BX.PopupWindow(
							dlgId,
							null,
							{
								autoHide: false,
								draggable: true,
								offsetLeft: 0,
								offsetTop: 0,
								bindOptions: {forceBindPosition: false},
								closeByEsc: true,
								closeIcon: true,
								zIndex: -12, //HACK: for tasks popup
								contentNoPaddings: true,
								titleBar: {
									content: self._prepareViewDlgTitle()
								},
								events: {
									onPopupClose: BX.delegate(
										function ()
										{
											BX.CrmActivityEditor.hideUploader(self.getSetting('uploadID', ''), self.getSetting('uploadControlID', ''));
											BX.CrmActivityEditor.hideLhe(self.getSetting('lheContainerID', ''));

											self._dlg.destroy();
											BX.onCustomEvent(window, 'onActivityEditorClose', []);
										},
										self
									),
									onPopupDestroy: BX.proxy(
										function ()
										{
											self._dlg = null;
											self._wrapper = null;
											self._ttlWrapper = null;
											delete(BX.CrmActivityProvider.dialogs[dlgId]);
										},
										self
									)
								},
								content: wrapper,
								buttons: self._prepareViewDlgButtons()
							}
						);

						self._prepareDialogContent();
						BX.CrmActivityProvider.dialogs[dlgId] = self._dlg;
						self._dlg.show();
					}
				});
			},

			_getNode: function(name)
			{
				return this._contentNode ? this._contentNode.querySelector('[data-role="'+name+'"]') : null;
			},

			_prepareDialogContent: function()
			{
				var me = this;
				var additionalSwitcher = this._getNode('additional-switcher');
				var additionalFields = this._getNode('additional-fields');

				if (additionalSwitcher && additionalFields)
				{
					BX.bind(additionalSwitcher, 'click', function()
					{
						BX.toggleClass(additionalFields, 'active')
					});
				}

				var comSliderLeft = this._getNode('com-slider-left');
				if (comSliderLeft)
				{
					BX.bind(comSliderLeft, 'click', function()
					{
						me._changeCommunicationSlide(-1);
					});
				}

				var comSliderRight = this._getNode('com-slider-right');
				if (comSliderRight)
				{
					BX.bind(comSliderRight, 'click', function()
					{
						me._changeCommunicationSlide(1);
					});
				}

				var fieldCompleted = this._getNode('field-completed');
				if (fieldCompleted)
				{
					var enableInstantEdit = this.getOption('enableInstantEdit', true);
					if (enableInstantEdit)
					{
						BX.bind(fieldCompleted, 'click', function()
						{
							fieldCompleted.setAttribute('disabled', 'disabled');

							me._editor.setActivityCompleted(
								me.getId(),
								fieldCompleted.checked,
								function(result)
								{
									me._settings['completed'] = !!result['COMPLETED'];
									fieldCompleted.removeAttribute('disabled');
								}
							);
						});
					}
					else
					{
						fieldCompleted.setAttribute('disabled', 'disabled');
					}
				}
			},

			_changeCommunicationSlide: function(direction)
			{
				var navigator = this._getNode('com-slider-nav');
				var slides = this._getNode('com-slider-slides');
				if (!navigator || !slides)
					return false;

				var currentIndex = parseInt(navigator.getAttribute('data-current'));
				var cnt = parseInt(navigator.getAttribute('data-cnt'));

				if (isNaN(cnt) || cnt < 1)
					return false;

				if (isNaN(currentIndex) || currentIndex < 1)
					currentIndex = 1;

				currentIndex += direction < 0 ? -1 : 1;

				if (currentIndex > cnt)
					currentIndex = cnt;
				if (currentIndex < 1)
					currentIndex = 1;

				navigator.setAttribute('data-current', currentIndex.toString());
				navigator.innerHTML = currentIndex.toString() + ' / ' + cnt.toString();

				slides.style.marginLeft = ((currentIndex - 1) * -269).toString() + 'px';
			},

			closeDialog: function ()
			{
				if (this._titleMenu)
				{
					this._titleMenu.removeCreateTaskListener(this._taskCreationHandler);
					this._titleMenu.removeCreateCallListener(this._callCreationHandler);
					this._titleMenu.removeCreateMeetingListener(this._meetingCreationHandler);

					this._titleMenu.cleanLayout();
				}

				if (!this._dlg)
				{
					return;
				}

				this._notifyDialogClose();
				this._dlg.close();
			},
			addOnSave: function (handler)
			{
				if (!BX.type.isFunction(handler))
				{
					return;
				}

				for (var i = 0; i < this._onSaveHandlers.length; i++)
				{
					if (this._onSaveHandlers[i] == handler)
					{
						return;
					}
				}

				this._onSaveHandlers.push(handler);

			},
			removeOnSave: function (handler)
			{
				if (!BX.type.isFunction(handler))
				{
					return;
				}

				for (var i = 0; i < this._onSaveHandlers.length; i++)
				{
					if (this._onSaveHandlers[i] == handler)
					{
						this._onSaveHandlers.splice(i, 1);
						return;
					}
				}

			},
			addOnDialogClose: function (handler)
			{
				if (!BX.type.isFunction(handler))
				{
					return;
				}

				for (var i = 0; i < this._onDlgCloseHandlers.length; i++)
				{
					if (this._onDlgCloseHandlers[i] == handler)
					{
						return;
					}
				}

				this._onDlgCloseHandlers.push(handler);

			},
			removeOnDialogClose: function (handler)
			{
				if (!BX.type.isFunction(handler))
				{
					return;
				}

				for (var i = 0; i < this._onDlgCloseHandlers.length; i++)
				{
					if (this._onDlgCloseHandlers[i] == handler)
					{
						this._onDlgCloseHandlers.splice(i, 1);
						return;
					}
				}

			},
			isChanged: function ()
			{
				return this._isChanged;
			},
			getButtonId: function ()
			{
				return this._buttonId;
			},
			_prepareViewDlgTitle: function ()
			{
				var text = this._activityOptions.title || this.getSetting('subject', '');

				this._titleMenu = BX.CrmActivityMenu.create('',
					{
						'enableTasks': this._editor.isTasksEnabled(),
						'enableCalendarEvents': this._editor.isCalendarEventsEnabled(),
						'enableEmails': this._editor.isEmailsEnabled() && this.getType() !== BX.CrmActivityType.email
					},
					{
						'createTask': this._taskCreationHandler,
						'createCall': this._callCreationHandler,
						'createMeeting': this._meetingCreationHandler,
						'createEmail': this._emailCreationHandler
					}
				);

				var wrapper = BX.create(
					'DIV',
					{
						attrs: { className: 'crm-task-list-head' },
						children:
							[
								BX.create(
									'SPAN',
									{
										attrs: { className: 'crm-task-list-head-item-left' },
										children:
											[
												BX.create(
													'SPAN',
													{
														text: text,
														props: { className: 'crm-task-list-head-item-left-element' }
													}
												)
											]
									}
								)
							]
					}
				);

				this._titleMenu.layout(wrapper);

				if (this._activityOptions.important)
				{
					wrapper.appendChild(
						BX.create(
							'SPAN',
							{
								attrs: { className: 'crm-task-list-head-item-right-wrap' },
								children:
									[
										BX.create(
											'SPAN',
											{
												attrs: { className: 'crm-task-list-head-item-right' },
												text: BX.message('CRM_ACTIVITY_PLANNER_IMPORTANT')
											}),
										BX.create(
											'SPAN',
											{
												attrs: { className: 'crm-task-list-head-item-right-icon' }
											})
									]
							}
						)
					);
				}

				return wrapper;
			},
			_notifyDialogClose: function ()
			{
				for (var i = 0; i < this._onDlgCloseHandlers.length; i++)
				{
					try
					{
						this._onDlgCloseHandlers[i](this);
					}
					catch (ex)
					{
					}
				}
			},
			_prepareViewDlgButtons: function ()
			{
				var result = [], me = this;

				if (this.getType() === BX.CrmActivityType.email && this._parentActivity)
				{
					var direction = parseInt(this.getSetting('direction', BX.CrmActivityDirection.outgoing));
					if(direction === BX.CrmActivityDirection.incoming)
					{
						result.push(
							{
								type: 'button',
								settings:
								{
									text: BX.CrmActivityEditor.getMessage('replyDlgButton'),
									className: 'popup-window-button-accept',
									events:
									{
										click: function()
										{
											me.closeDialog();
											me._parentActivity._handleReplyBtnClick()
										}
									}
								}
							}
						);
					}

					result.push(
						{
							type: 'button',
							settings:
							{
								text: BX.CrmActivityEditor.getMessage('forwardDlgButton'),
								className: 'popup-window-button-accept',
								events:
								{
									click: function()
									{
										me.closeDialog();
										me._parentActivity._handleForwardBtnClick()
									}
								}
							}
						}
					);

					result.push(
						{
							type: 'link',
							settings:
							{
								text: BX.CrmActivityEditor.getMessage('closeDlgButton'),
								className: 'popup-window-button-link-cancel',
								events:
								{
									click: BX.delegate(this._handleCloseBtnClick, this)
								}
							}
						}
					);
				}
				else
				{
					result.push(
						{
							type: 'button',
							settings: {
								text: BX.CrmActivityEditor.getMessage('closeDlgButton'),
								className: 'popup-window-button-accept',
								events: {
									click: BX.delegate(this._handleCloseBtnClick, this)
								}
							}
						}
					);

					if(
						this.getOption('enableEditButton', true)
						&& (
							this.getType() ===  BX.CrmActivityType.call
							|| this.getType() === BX.CrmActivityType.meeting
							|| this._activityOptions.isEditable === true
						)
					)
					{
						result.push(
							{
								type: 'link',
								settings:
								{
									text: BX.CrmActivityEditor.getMessage('editDlgButton'),
									className: "popup-window-button-link-cancel",
									events:
									{
										click : function()
										{
											(new BX.Crm.Activity.Planner()).showEdit({ID: me.getId()});
											me.closeDialog();
										}
									}
								}
							}
						);
					}
				}

				return BX.CrmActivityEditor.prepareDialogButtons(result);
			},
			_handleCallCreation: function (sender)
			{
				var ownerType = this.getSetting('ownerType', '');
				var ownerID = parseInt(this.getSetting('ownerID', 0));

				if(typeof BX.Crm.Activity.Planner !== 'undefined')
				{
					(new BX.Crm.Activity.Planner()).showEdit({
						TYPE_ID: BX.CrmActivityType.call,
						OWNER_TYPE: ownerType,
						OWNER_ID: ownerID,
						FROM_ACTIVITY_ID: this.getId()
					});
				}
			},
			_handleMeetingCreation: function (sender)
			{
				var ownerType = this.getSetting('ownerType', '');
				var ownerID = parseInt(this.getSetting('ownerID', 0));

				if(typeof BX.Crm.Activity.Planner !== 'undefined')
				{
					(new BX.Crm.Activity.Planner()).showEdit({
						TYPE_ID: BX.CrmActivityType.meeting,
						OWNER_TYPE: ownerType,
						OWNER_ID: ownerID,
						FROM_ACTIVITY_ID: this.getId()
					});
				}
			},
			_handleEmailCreation: function(sender)
			{
				var settings = {};
				var ownerType = this.getSetting('ownerType', '');
				var ownerID = parseInt(this.getSetting('ownerID', 0));
				if(ownerType !== '' && ownerID > 0)
				{
					settings['ownerType'] = ownerType;
					settings['ownerID'] = ownerID;
					settings['ownerTitle'] = this.getSetting('ownerTitle', '');
					settings['ownerUrl'] = this.getSetting('ownerUrl', '');
				}

				if(this.getSetting('ownerType', '') === 'DEAL')
				{
					// Need for custom logic when owner is DEAL (that doesnt have communications)
					var commData = this.getSetting('communications', []);
					var comm = BX.type.isArray(commData) && commData.length > 0 ? commData[0] : null;
					if(comm)
					{
						var commEntityType =  comm['entityType'];
						if(!BX.type.isNotEmptyString(commEntityType))
						{
							commEntityType = ownerType;
						}

						var commEntityId =  parseInt(comm['entityId']);
						if(isNaN(commEntityId) || commEntityId <= 0)
						{
							commEntityId = ownerID;
						}

						var defaultComm = BX.CrmActivityEditor.getDefaultCommunication(
							commEntityType,
							commEntityId,
							BX.CrmCommunicationType.email,
							this.getSetting('serviceUrl', '')
						);

						if(defaultComm)
						{
							settings['communications'] = [defaultComm.getSettings()];
						}
					}
				}

				this._editor.addEmail(settings);
			},
			_handleTaskCreation: function (sender)
			{
				var settings = {};
				var ownerType = this.getSetting('ownerType', '');
				var ownerID = parseInt(this.getSetting('ownerID', 0));
				if (ownerType !== '' && ownerID > 0)
				{
					settings['ownerType'] = ownerType;
					settings['ownerID'] = ownerID;
				}

				this._editor.addTask(settings);
			},
			_handleCloseBtnClick: function (e)
			{
				this._buttonId = BX.CrmActivityDialogButton.cancel;
				this.closeDialog();
			}
		};
		BX.CrmActivityProvider.dialogs = {};
		BX.CrmActivityProvider.create = function (settings, editor, options, parentActivity)
		{
			var self = new BX.CrmActivityProvider();
			self.initialize(settings, editor, options, parentActivity);
			return self;
		};
	}

	//region BX.CrmCustomActivityType
	if(typeof(BX.CrmCustomActivityType) == "undefined")
	{
		BX.CrmCustomActivityType = function()
		{
		};

		BX.CrmCustomActivityType.getListItems = function(infos)
		{
			if(!BX.type.isArray(infos))
			{
				infos = BX.CrmCustomActivityType.infos;
			}

			var results = [];
			for(var i = 0, l = infos.length; i < l; i++)
			{
				var info = infos[i];
				results.push({ value: info["id"], text: info["name"] });
			}
			return results;
		};

		BX.CrmCustomActivityType.prepareEditorParams = function(id, params)
		{
			var info = this.getInfo(id);
			if(info !== null)
			{
				params["PROVIDER_ID"] = "CUST";
				params["PROVIDER_TYPE_ID"] = info["id"];
				params["NAME"] = info["name"];
				params["TYPE_ID"] = 6;
			}
		};

		BX.CrmCustomActivityType.getInfo = function(id)
		{
			for(var i = 0, l = BX.CrmCustomActivityType.infos.length; i < l; i++)
			{
				var info = BX.CrmCustomActivityType.infos[i];
				if(info["id"] == id)
				{
					return info;
				}
			}
			return null;
		};

		if(typeof(BX.CrmCustomActivityType.infos) === "undefined")
		{
			BX.CrmCustomActivityType.infos = [];
		}
	}
	//endregion
	//region BX.CrmCustomActivityTypeSelector
	if(typeof(BX.CrmCustomActivityTypeSelector) == "undefined")
	{
		BX.CrmCustomActivityTypeSelector = function()
		{
			this._id = "";
			this._settings = {};
			this._ownerTypeId = 0;
			this._ownerId = 0;

			this._selectorMenu = null;
			this._menuItemSelectHandler = BX.delegate(this.onMenuItemSelect, this);
			//this._canCreateType = false;
			//this._createUrl = "";
			//this._typeListUrl = "";
			//this._typeCreateUrl = "";
		};

		BX.CrmCustomActivityTypeSelector.prototype =
		{
			initialize: function(id, settings)
			{
				this._id = id;
				this._settings = settings ? settings : {};
				this._ownerTypeId = this.getSetting("ownerTypeId", 0);
				this._ownerId = this.getSetting("ownerId", 0);
				//this._canCreateType = !!this.getSetting("canCreateType", false);
				//this._createUrl = this.getSetting("createUrl", "");
				//this._typeListUrl = this.getSetting("typeListUrl", "");
				//this._typeCreateUrl = this.getSetting("typeCreateUrl", "");
			},
			getId: function()
			{
				return this._id;
			},
			getSetting: function (name, defaultval)
			{
				return this._settings.hasOwnProperty(name) ? this._settings[name] : defaultval;
			},
			getMessage: function(name)
			{
				var m = BX.CrmCustomActivityTypeSelector.messages;
				return m.hasOwnProperty(name) ? m[name] : name;
			},
			openCreationDialog: function(typeId)
			{
				var params =
				{
					"OWNER_TYPE_ID": this._ownerTypeId,
					"OWNER_ID": this._ownerId
				};
				BX.CrmCustomActivityType.prepareEditorParams(typeId, params);
				var planner = new BX.Crm.Activity.Planner();
				planner.showEdit(params);
			},
			openMenu: function(anchor)
			{
				if(!this._selectorMenu)
				{
					var items = BX.CrmCustomActivityType.getListItems();
					//if(this._canCreateType)
					//{
					//	items.push({ text: this.getMessage("create"), value: "new" });
					//}
					this._selectorMenu = BX.CmrSelectorMenu.create(this._id, { items: items });
					this._selectorMenu.addOnSelectListener(this._menuItemSelectHandler);
				}

				if(!this._selectorMenu.isOpened())
				{
					this._selectorMenu.open(anchor);
				}
			},
			onMenuItemSelect: function(sender, selectedItem)
			{
				var selectedValue = selectedItem.getValue();
				if(this._selectorMenu.isOpened())
				{
					this._selectorMenu.close();
				}

				this.openCreationDialog(parseInt(selectedValue));
				//if(selectedValue === "new")
				//{
				//	window.location = this._categoryCreateUrl;
				//}
				//else
				//{
				//	this.openCreationDialog(parseInt(selectedValue));
				//}
			}
		};

		if(typeof(BX.CrmCustomActivityTypeSelector.messages) === "undefined")
		{
			BX.CrmCustomActivityTypeSelector.messages = {};
		}
		BX.CrmCustomActivityTypeSelector.items = {};
		BX.CrmCustomActivityTypeSelector.create = function(id, settings)
		{
			var self = new BX.CrmCustomActivityTypeSelector();
			self.initialize(id, settings);
			this.items[self.getId()] = self;
			return self;
		};
	}
	//endregion

	if(typeof(BX.CrmCallListHelper) == "undefined")
	{
		BX.CrmCallListHelper = function(){};

		BX.CrmCallListHelper.createCallList = function(params, successCallback)
		{
			BX.ajax({
				url: DEFAULT_AJAX_URL,
				method: 'POST',
				dataType: 'json',
				'data':
				{
					'ajax_action' : 'CREATE_CALL_LIST',
					'sessid': BX.bitrix_sessid(),
					'ENTITY_TYPE': params.entityType,
					'ENTITY_IDS': params.entityIds,
					'GRID_ID': params.gridId,
					'CREATE_ACTIVITY': (params.createActivity ? 'Y' : 'N')
				},
				onsuccess: function(data)
				{
					if(data && successCallback)
					{
						successCallback(data);
					}
				},
				onfailure: function(data)
				{
				}
			});
		};

		BX.CrmCallListHelper.addToCallList = function(params)
		{
			var context = params.context || '';
			var callListId = params.callListId || 0;
			var entityIds = (params.entityIds ? params.entityIds : [params.id]);

			if(context == '' || callListId == 0)
				return;

			BX.ajax({
				url: DEFAULT_AJAX_URL,
				method: 'POST',
				dataType: 'json',
				'data':
				{
					'ajax_action' : 'ADD_TO_CALL_LIST',
					'sessid': BX.bitrix_sessid(),
					'CALL_LIST_ID': callListId,
					'ENTITY_TYPE': params.entityType,
					'ENTITY_IDS': entityIds,
					'GRID_ID': params.gridId
				},
				onsuccess: function(response)
				{
					if(response && !response.SUCCESS && response.ERRORS)
					{
						var error = response.ERRORS.join('. \n');
						window.alert(error);
					}
					else if(response && response.SUCCESS && response.DATA)
					{
						var callListId = response.DATA.ID;
						if(response.DATA && response.DATA.MESSAGE)
						{
							window.alert(response.DATA.MESSAGE);
						}
						BX.localStorage.set(
							"onCrmCallListUpdate",
							{
								callListId: callListId,
								context: context
							},
							10

						)
					}
				}
			});
		}
	}
})(window.BX || window.top.BX);