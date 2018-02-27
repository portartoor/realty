if(typeof(BX.CrmDealStageManager) === "undefined")
{
	BX.CrmDealStageManager = function() {};

	BX.CrmDealStageManager.prototype =
	{
		getInfos: function() { return BX.CrmDealStageManager.infos; },
		getMessage: function(name)
		{
			var msgs = BX.CrmDealStageManager.messages;
			return BX.type.isNotEmptyString(msgs[name]) ? msgs[name] : "";
		}
	};

	BX.CrmDealStageManager.current = new BX.CrmDealStageManager();
	BX.CrmDealStageManager.infos =
	[
		{ "id": "NEW", "name": "In Progress", "sort": 10, "semantics": "process" },
		{ "id": "WON", "name": "Is Won", "sort": 20, "semantics": "success" },
		{ "id": "LOSE", "name": "Is Lost", "sort": 30, "semantics": "failure" }
	];

	BX.CrmDealStageManager.messages = {}
}

if(typeof(BX.CrmLeadStatusManager) === "undefined")
{
	BX.CrmLeadStatusManager = function() {};

	BX.CrmLeadStatusManager.prototype =
	{
		getInfos: function() { return BX.CrmLeadStatusManager.infos; },
		getMessage: function(name)
		{
			var msgs = BX.CrmLeadStatusManager.messages;
			return BX.type.isNotEmptyString(msgs[name]) ? msgs[name] : "";
		}
	};

	BX.CrmLeadStatusManager.current = new BX.CrmLeadStatusManager();
	BX.CrmLeadStatusManager.infos =
	[
		{ "id": "NEW", "name": "Not Processed", "sort": 10, "semantics": "process" },
		{ "id": "CONVERTED", "name": "Converted", "sort": 20, "semantics": "success" },
		{ "id": "JUNK", "name": "Junk", "sort": 30, "semantics": "failure" }
	];

	BX.CrmLeadStatusManager.messages = {}
}

if(typeof(BX.CrmProgressControl) === "undefined")
{
	BX.CrmProgressControl = function()
	{
		this._id = "";
		this._settings = null;
		this._container = null;
		this._entityId = 0;
		this._entityType = null;
		this._currentStepId = "";
		this._manager = null;
		this._stepInfos = null;
		this._steps = [];
		this._terminationDlg = null;
		this._isFrozen = false;
	};

	BX.CrmProgressControl.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : BX.CrmParamBag.create(null);
			this._container = BX(this.getSetting("containerId"));
			this._entityId = parseInt(this.getSetting("entityId", 0));
			this._entityType = this.getSetting("entityType");
			this._currentStepId = this.getSetting("currentStepId");

			if(this._entityType === 'DEAL')
			{
				this._manager = BX.CrmDealStageManager.current;
			}
			else if(this._entityType === 'LEAD')
			{
				this._manager = BX.CrmLeadStatusManager.current;
			}

			var stepInfos = this._stepInfos = this._manager.getInfos();
			var currentStepIndex = this._findStepInfoIndex(this._currentStepId);
			var currentStepInfo = currentStepIndex >= 0 ? stepInfos[currentStepIndex] : null;

			this._isFrozen = currentStepInfo
				&& BX.type.isBoolean(currentStepInfo["isFrozen"]) ? currentStepInfo["isFrozen"] : false;

			for(var i = 0; i < stepInfos.length; i++)
			{
				var info = stepInfos[i];
				var stepContainer = this.getStepContainer(info["id"]);
				if(!stepContainer)
				{
					continue;
				}

				var sort = parseInt(info["sort"]);
				this._steps.push(
					BX.CrmProgressStep.create(
						info["id"],
						BX.CrmParamBag.create(
							{
								"name": info["name"],
								"hint": BX.type.isNotEmptyString(info["hint"]) ? info["hint"] : '',
								"sort": sort,
								"isPassed": i <= currentStepIndex,
								"control": this
							}
						)
					)
				);
			}
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.getParam(name, defaultval);
		},
		getId: function()
		{
			return this._id;
		},
		getEntityType: function()
		{
			return this._entityType;
		},
		getEntityId: function()
		{
			return this._entityId;
		},
		getCurrentStepId: function()
		{
			return this._currentStepId;
		},
		isFrozen: function()
		{
			return this._isFrozen;
		},
		getStepContainer: function(id)
		{
			return BX.type.isNotEmptyString(id)
				? BX.findChild(this._container, { "tag": "DIV", "class": "crm-stage-" + id.toLowerCase() }, true)
				: null;
		},
		setCurrentStep: function(step)
		{
			this._closeDialog();

			if(this._isFrozen)
			{
				return;
			}

			var stepIndex = this._findStepInfoIndex(step.getId());
			if(stepIndex < 0)
			{
				return;
			}

			if(stepIndex === (this._steps.length - 1)
				&& this._findStepInfoBySemantics("success")
				&& this._findStepInfoBySemantics("failure"))
			{
				//User have to make choice
				this._openDialog();
				return;
			}

			if(this._currentStepId !== step.getId())
			{
				this._currentStepId = step.getId();
				this._layout();
				this._save();
			}
		},
		setCurrentStepId: function(stepId)
		{
			if(this._currentStepId !== stepId)
			{
				this._currentStepId = stepId;
				this._layout();
			}
		},
		_layout: function()
		{
			var stepIndex = this._findStepInfoIndex(this._currentStepId);
			if(stepIndex < 0)
			{
				return;
			}

			for(var i = 0; i < this._steps.length; i++)
			{
				this._steps[i].setPassed(i <= stepIndex);
			}

			var stepInfo = this._stepInfos[stepIndex];

			this._isFrozen = BX.type.isBoolean(stepInfo["isFrozen"]) ? stepInfo["isFrozen"] : false;
			var semantics = BX.type.isNotEmptyString(stepInfo["semantics"]) ? stepInfo["semantics"] : "";
			
			if(semantics === "success")
			{
				BX.addClass(this._container, "crm-list-stage-end-good");
				BX.removeClass(this._container, "crm-list-stage-end-bad");
			}
			else if(semantics === "failure" || semantics === "apology")
			{
				BX.removeClass(this._container, "crm-list-stage-end-good");
				BX.addClass(this._container, "crm-list-stage-end-bad");
			}
			else
			{
				BX.removeClass(this._container, "crm-list-stage-end-good");
				BX.removeClass(this._container, "crm-list-stage-end-bad");
			}
		},
		_openDialog: function()
		{
			this._enableStepHints(false);

			if(this._terminationDlg)
			{
				this._terminationDlg.close();
				this._terminationDlg = null;
			}

			this._terminationDlg = BX.CrmProcessTerminationDialog.create(
				this.getId(),
				BX.CrmParamBag.create(
					{
						"title": this._manager.getMessage("dialogTitle"),
						"apologyTitle": this._manager.getMessage("apologyTitle"),
						"anchor": this._container,
						"success": this._findStepInfoBySemantics("success"),
						"failure": this._findStepInfoBySemantics("failure"),
						"aplogies": this._findAllStepInfoBySemantics("apology"),
						"callback": BX.delegate(this._onDialogClose, this)
					}
				)
			);
			this._terminationDlg.open();
		},
		_closeDialog: function()
		{
			if(!this._terminationDlg)
			{
				return;
			}

			this._terminationDlg.close();
			this._terminationDlg = null;
			this._enableStepHints(true);
		},
		_onDialogClose: function(dialog, params)
		{
			if(this._terminationDlg !== dialog)
			{
				return;
			}

			this._closeDialog();
			var id = BX.type.isNotEmptyString(params["result"]) ? params["result"] : "";
			var index = this._findStepInfoIndex(id);
			if(index >= 0)
			{
				var info = this._stepInfos[index];
				if(info["semantics"] === "success")
				{
					var finalUrl = this.getSetting("finalUrl", "");
					if(finalUrl !== "")
					{
						window.location = finalUrl;
						return;
					}
				}
				this._currentStepId = info["id"];
				this._layout();
				this._save();
			}
		},
		_save: function()
		{
			var serviceUrl = this.getSetting("serviceUrl");
			var value = this.getCurrentStepId();
			var type = this.getEntityType();
			var id = this.getEntityId();

			if(serviceUrl === "" || value === "" || type === "" || id <= 0)
			{
				return;
			}

			var self = this;
			BX.ajax(
				{
					"url": serviceUrl,
					"method": "POST",
					"dataType": 'json',
					"data":
					{
						"ACTION" : "SAVE_PROGRESS",
						"VALUE": value,
						"TYPE": type,
						"ID": id
					},
					"onsuccess": function(data)
					{
						BX.CrmProgressControl._synchronize(self);
					},
					"onfailure": function(data)
					{
					}
				}
			);
		},
		_findStepInfoBySemantics: function(semantics)
		{
			var infos = this._stepInfos;
			for(var i = 0; i < infos.length; i++)
			{
				var info = infos[i];
				var s = BX.type.isNotEmptyString(info["semantics"]) ? info["semantics"] : '';
				if(semantics === s)
				{
					return info;
				}
			}

			return null;
		},
		_findAllStepInfoBySemantics: function(semantics)
		{
			var result = [];
			var infos = this._stepInfos;
			for(var i = 0; i < infos.length; i++)
			{
				var info = infos[i];
				var s = BX.type.isNotEmptyString(info["semantics"]) ? info["semantics"] : '';
				if(semantics === s)
				{
					result.push(info);
				}
			}

			return result;
		},
		_findStepInfoIndex: function(id)
		{
			var infos = this._stepInfos;
			for(var i = 0; i < infos.length; i++)
			{
				if(infos[i]["id"] === id)
				{
					return i;
				}
			}

			return -1;
		},
		_enableStepHints: function(enable)
		{
			for(var i = 0; i < this._steps.length; i++)
			{
				this._steps[i].enableHint(enable);
			}
		}
	};

	BX.CrmProgressControl.items = {};
	BX.CrmProgressControl.create = function(id, settings)
	{
		var self = new BX.CrmProgressControl();
		self.initialize(id, settings);
		this.items[id] = self;
		return self;
	};
	BX.CrmProgressControl._synchronize = function(item)
	{
		var type = item.getEntityType();
		var id = item.getEntityId();

		for(var itemId in this.items)
		{
			if(!this.items.hasOwnProperty(itemId))
			{
				continue;
			}

			var curItem = this.items[itemId];
			if(curItem === item)
			{
				continue;
			}

			if(curItem.getEntityType() === type && curItem.getEntityId() === id)
			{
				curItem.setCurrentStepId(item.getCurrentStepId());
			}
		}
	}
}

if(typeof(BX.CrmProgressStep) === "undefined")
{
	BX.CrmProgressStep = function()
	{
		this._id = "";
		this._settings = null;
		this._control = null;
		this._container = null;
		this._name = "";
		this._hint = "";
		this._isPassed = false;
		this._enableHint = true;
		this._hintPopup = null;
		this._hintPopupTimeoutId = null;
	};

	BX.CrmProgressStep.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : BX.CrmParamBag.create(null);
			this._control = this.getSetting("control");
			this._container = this._control.getStepContainer(this._id);
			this._name = this.getSetting("name");
			this._hint = this.getSetting("hint", "");
			this._isPassed = this.getSetting("isPassed", false);

			BX.bind(this._container, "mouseover", BX.delegate(this._onMouseOver, this));
			BX.bind(this._container, "mouseout", BX.delegate(this._onMouseOut, this));
			BX.bind(this._container, "click", BX.delegate(this._onClick, this));
		},
		getId: function()
		{
			return this._id;
		},
		getName: function()
		{
			return this._name;
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.getParam(name, defaultval);
		},
		isPassed: function()
		{
			return this._isPassed;
		},
		setPassed: function(passed)
		{
			passed = !!passed;
			if(this._isPassed === passed)
			{
				return;
			}

			this._isPassed = passed;

			var wrapper = BX.findParent(this._container, { "class": "crm-list-stage-bar-part" });
			if(passed)
			{
				BX.addClass(wrapper, "crm-list-stage-passed");
			}
			else
			{
				BX.removeClass(wrapper, "crm-list-stage-passed");
			}
		},
		isHintEnabled: function()
		{
			return this._enableHint;
		},
		enableHint: function(enable)
		{
			enable = !!enable;
			if(this._enableHint === enable)
			{
				return;
			}

			this._enableHint = enable;
			if(!enable)
			{
				this.hideStepHint();
			}
		},
		displayStepHint: function(step)
		{
			if(!this._enableHint || this._hintPopup)
			{
				return;
			}

			var pos = BX.pos(this._container);
			this._hintPopup = BX.PopupWindowManager.create(
				"step-hint-" + this._id,
				step,
				{
					"angle": {
						"position": "bottom",
						"offset": 0
					},
					"offsetLeft": pos["width"] / 2,
					"offsetTop": 5,
					"content": BX.create(
						"SPAN",
						{
							"attrs": { "class": "crm-list-bar-popup-text" },
							"text": this._hint !== '' ? this._hint : this._name
						}
					),
					"className": "crm-list-bar-popup-table"
				}
			);
			this._hintPopup.show();
		},
		hideStepHint: function()
		{
			if(!this._hintPopup)
			{
				return;
			}

			this._hintPopup.close();
			this._hintPopup.destroy();
			this._hintPopup = null;
		},
		_onClick: function(e)
		{
			this._control.setCurrentStep(this);
		},
		_onMouseOver: function(e)
		{
			if(this._hintPopupTimeoutId !== null)
			{
				window.clearTimeout(this._hintPopupTimeoutId);
			}

			e = e || window.event;
			var target = e.target || e.srcElement;
			var self = this;
			this._hintPopupTimeoutId = window.setTimeout(function(){ self._hintPopupTimeoutId = null; self.displayStepHint(target); }, 300 );
		},
		_onMouseOut: function(e)
		{
			if(this._hintPopupTimeoutId !== null)
			{
				window.clearTimeout(this._hintPopupTimeoutId);
			}

			if(!this._enableHint)
			{
				return;
			}

			var self = this;
			this._hintPopupTimeoutId = window.setTimeout(function(){ self._hintPopupTimeoutId = null; self.hideStepHint(); }, 300 );
		}
	};

	BX.CrmProgressStep.create = function(id, settings)
	{
		var self = new BX.CrmProgressStep();
		self.initialize(id, settings);
		return self;
	};
}

if(typeof(BX.CrmProcessTerminationDialog) === "undefined")
{
	BX.CrmProcessTerminationDialog = function()
	{
		this._id = "";
		this._settings = null;
		this._popup = null;
		this._wrapper = null;
		this._result = "";
	};

	BX.CrmProcessTerminationDialog.prototype =
	{
		initialize: function(id, settings)
		{
			this._id = id;
			this._settings = settings ? settings : BX.CrmParamBag.create(null);
		},
		getSetting: function(name, defaultval)
		{
			return this._settings.getParam(name, defaultval);
		},
		getId: function()
		{
			return this._id;
		},
		getResult: function()
		{
			return this._result;
		},
		open: function()
		{
			if(!this._popup)
			{
				this._popup = BX.PopupWindowManager.create(
					this._id,
					this.getSetting("anchor"),
					{
						"closeByEsc": true,
						"autoHide": true,
						"offsetLeft": -50,
						"closeIcon": true,
						"className": "crm-list-end-deal",
						"content": this._prepareContent(),
						"events": { "onPopupClose": BX.delegate(this._onPopupClose, this) }
					}
				);
			}
			this._popup.show();
		},
		close: function()
		{
			if(this._popup)
			{
				this._popup.close();
			}
		},
		_onPopupClose: function()
		{
			if(this._popup)
			{
				this._popup.destroy();
				this._popup = null;
			}

			var callback = this.getSetting("callback");
			if(BX.type.isFunction(callback))
			{
				callback(this, { "result": this._result });
			}
		},
		_prepareContent: function()
		{
			var wrapper = this._wrapper = BX.create(
				"DIV",
				{ "attrs": { "class": "crm-list-end-deal-block" } }
			);

			var title = this.getSetting("title", "");
			wrapper.appendChild(
				BX.create(
					"DIV",
					{
						"attrs":
						{
							"class": "crm-list-end-deal-text"
						},
						"text": title
					}
				)
			);

			var buttonBlock = BX.create(
				"DIV",
				{
					"attrs":
					{
						"class": "crm-list-end-deal-buttons-block"
					}
				}
			);

			var success = this.getSetting("success");
			if(success)
			{
				var successText = BX.type.isNotEmptyString(success["name"]) ? success["name"] : "Success";
				var successButton = BX.create(
					"A",
					{
						"attrs":
						{
							"class": "webform-button webform-button-accept",
							"href": "#"
						},
						"children":
						[
							BX.create("SPAN", { "attrs": { "class": "webform-button-left" } }),
							BX.create("SPAN", { "attrs": { "class": "webform-button-text" }, "text": successText }),
							BX.create("SPAN", { "attrs": { "class": "webform-button-right" } })
						]
					}
				);

				buttonBlock.appendChild(successButton);
				var successId = BX.type.isNotEmptyString(success["id"]) ? success["id"] : "success";
				BX.CrmSubscriber.subscribe(
					this.getId() + "_" + successId,
					successButton, "click", BX.delegate(this._onButtonClick, this),
					BX.CrmParamBag.create({ "id": successId, "preventDefault": true })
				);
			}

			var failure = this.getSetting("failure");
			if(failure)
			{
				var failureText = BX.type.isNotEmptyString(failure["name"]) ? failure["name"] : "Failure";
				var failureButton = BX.create(
					"A",
					{
						"attrs":
						{
							"class": "webform-button webform-button-decline",
							"href": "#"
						},
						"children":
						[
							BX.create("SPAN", { "attrs": { "class": "webform-button-left" } }),
							BX.create("SPAN", { "attrs": { "class": "webform-button-text" }, "text": failureText }),
							BX.create("SPAN", { "attrs": { "class": "webform-button-right" } })
						]
					}
				);

				buttonBlock.appendChild(failureButton);
				var failureId = BX.type.isNotEmptyString(failure["id"]) ? failure["id"] : "failure";
				BX.CrmSubscriber.subscribe(
					this.getId() + '_' + failureId,
					failureButton, "click", BX.delegate(this._onButtonClick, this),
					BX.CrmParamBag.create({ "id": failureId, "preventDefault": true })
				);
			}
			wrapper.appendChild(buttonBlock);

			var apologies = this.getSetting("aplogies");
			if(BX.type.isArray(apologies) && apologies.length >0)
			{
				var footerBlock = BX.create(
					"DIV",
					{
						"attrs":
						{
							"class": "crm-list-end-deal-footer-block"
						}
					}
				);

				var apologyTitle = this.getSetting("apologyTitle", "All Apologies");
				footerBlock.appendChild(
					BX.create(
						"DIV",
						{
							"attrs":
							{
								"class": "crm-list-end-deal-option"
							},
							"events":
							{
								"click": BX.delegate(this._onShowAppologies, this)
							},
							"text": apologyTitle
						}
					)
				);

				for(var i = 0; i < apologies.length; i++)
				{
					var apology = apologies[i];
					var apologyText = BX.type.isNotEmptyString(apology["name"]) ? apology["name"] : "Apology #" + i.toString();
					var apologyButton = BX.create(
						"A",
						{
							"attrs":
							{
								"class": "webform-button",
								"style": "display:none;",
								"href": "#"
							},
							"children":
							[
								BX.create("SPAN", { "attrs": { "class": "webform-button-left" } }),
								BX.create("SPAN", { "attrs": { "class": "webform-button-text" }, "text": apologyText }),
								BX.create("SPAN", { "attrs": { "class": "webform-button-right" } })
							]
						}
					);
					footerBlock.appendChild(apologyButton);
					var apologyId = BX.type.isNotEmptyString(apology["id"]) ? apology["id"] : "apology" + i.toString();
					BX.CrmSubscriber.subscribe(
						this.getId() + "_" + apologyId,
						apologyButton, "click", BX.delegate(this._onButtonClick, this),
						BX.CrmParamBag.create({ "id": apologyId, "preventDefault": true })
					);
				}

				wrapper.appendChild(footerBlock);
			}

			return wrapper;
		},
		_onShowAppologies: function(e)
		{
			var footer = BX.findChild(this._wrapper, { "tag": "DIV", "class": "crm-list-end-deal-footer-block" }, true);
			var button = BX.findChild(footer, { "tag": "DIV", "class": "crm-list-end-deal-option" }, true);
			if(button)
			{
				button.style.display = "none";
			}

			var buttons = BX.findChildren(footer, { "tag": "A", "class": "webform-button" }, true);
			if(buttons)
			{
				for(var i = 0; i < buttons.length; i++)
				{
					buttons[i].style.display = "";
				}
			}
		},
		_onButtonClick: function(subscriber, params)
		{
			this._result = subscriber.getSetting("id", "");

			var callback = this.getSetting("callback");
			if(BX.type.isFunction(callback))
			{
				callback(this, { "result": this._result });
			}
		}
	};

	BX.CrmProcessTerminationDialog.create = function(id, settings)
	{
		var self = new BX.CrmProcessTerminationDialog();
		self.initialize(id, settings);
		return self;
	}
}
