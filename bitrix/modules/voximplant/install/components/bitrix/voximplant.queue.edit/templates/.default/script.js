(function()
{
	if (window.BX.ViGroupEdit)
		return;

	var AJAX_URL = '/bitrix/components/bitrix/voximplant.queue.edit/ajax.php';

	var Rule = {
		wait: 'wait',
		talk: 'talk',
		hungup: 'hungup',
		pstn: 'pstn',
		pstn_specific: 'pstn_specific',
		user: 'user',
		voicemail: 'voicemail',
		queue: 'queue',
		next_queue: 'next_queue'
	};

	var Destination = function(params, type) {
		this.p = (!!params ? params : {});
		if (!!params["SELECTED"])
		{
			var res = {}, tp, j;
			for (tp in params["SELECTED"])
			{
				if (params["SELECTED"].hasOwnProperty(tp) && typeof params["SELECTED"][tp] == "object")
				{
					for (j in params["SELECTED"][tp])
					{
						if (params["SELECTED"][tp].hasOwnProperty(j))
						{
							if (tp == 'USERS')
								res['U' + params["SELECTED"][tp][j]] = 'users';
							else if (tp == 'SG')
								res['SG' + params["SELECTED"][tp][j]] = 'sonetgroups';
							else if (tp == 'DR')
								res['DR' + params["SELECTED"][tp][j]] = 'department';
						}
					}
				}
			}
			this.p["SELECTED"] = res;
		}

		this.nodes = {};
		var makeDepartmentTree = function(id, relation)
			{
				var arRelations = {}, relId, arItems, x;
				if (relation[id])
				{
					for (x in relation[id])
					{
						if (relation[id].hasOwnProperty(x))
						{
							relId = relation[id][x];
							arItems = [];
							if (relation[relId] && relation[relId].length > 0)
								arItems = makeDepartmentTree(relId, relation);
							arRelations[relId] = {
								id: relId,
								type: 'category',
								items: arItems
							};
						}
					}
				}
				return arRelations;
			},
			buildDepartmentRelation = function(department)
			{
				var relation = {}, p;
				for(var iid in department)
				{
					if (department.hasOwnProperty(iid))
					{
						p = department[iid]['parent'];
						if (!relation[p])
							relation[p] = [];
						relation[p][relation[p].length] = iid;
					}
				}
				return makeDepartmentTree('DR0', relation);
			};
		if (true || type == 'users')
		{
			this.params = {
				'name' : null,
				'searchInput' : null,
				'extranetUser' :  (this.p['EXTRANET_USER'] == "Y"),
				'bindMainPopup' : { node : null, 'offsetTop' : '5px', 'offsetLeft': '15px'},
				'bindSearchPopup' : { node : null, 'offsetTop' : '5px', 'offsetLeft': '15px'},
				departmentSelectDisable : true,
				'callback' : {
					'select' : BX.delegate(this.select, this),
					'unSelect' : BX.delegate(this.unSelect, this),
					'openDialog' : BX.delegate(this.openDialog, this),
					'closeDialog' : BX.delegate(this.closeDialog, this),
					'openSearch' : BX.delegate(this.openDialog, this),
					'closeSearch' : BX.delegate(this.closeSearch, this)
				},
				items : {
					users : (!!this.p['USERS'] ? this.p['USERS'] : {}),
					groups : {},
					sonetgroups : {},
					department : (!!this.p['DEPARTMENT'] ? this.p['DEPARTMENT'] : {}),
					departmentRelation : (!!this.p['DEPARTMENT'] ? buildDepartmentRelation(this.p['DEPARTMENT']) : {}),
					contacts : {},
					companies : {},
					leads : {},
					deals : {}
				},
				itemsLast : {
					users : (!!this.p['LAST'] && !!this.p['LAST']['USERS'] ? this.p['LAST']['USERS'] : {}),
					sonetgroups : {},
					department : {},
					groups : {},
					contacts : {},
					companies : {},
					leads : {},
					deals : {},
					crm : []
				},
				itemsSelected : (!!this.p['SELECTED'] ? BX.clone(this.p['SELECTED']) : {}),
				isCrmFeed : false,
				destSort : (!!this.p['DEST_SORT'] ? BX.clone(this.p['DEST_SORT']) : {})
			}
		}
		// TODO Other types for searching
	};

	Destination.prototype = {
		setInput : function(node, inputName)
		{
			if (BX.type.isDomNode(node) && !node.hasAttribute("bx-destination-id"))
			{
				var id = 'destination' + ('' + new Date().getTime()).substr(6), res;
				node.setAttribute('bx-destination-id', id);
				res = new DestinationInput(id, node, inputName);
				this.nodes[id] = node;
				BX.defer_proxy(function(){
					this.params.name = res.id;
					this.params.searchInput = res.nodes.input;
					this.params.bindMainPopup.node = res.nodes.container;
					this.params.bindSearchPopup.node = res.nodes.container;

					BX.SocNetLogDestination.init(this.params);
				}, this)();
			}
		},
		select : function(item, type, search, bUndeleted, id)
		{
			var type1 = type, prefix = 'S';

			if (type == 'groups')
			{
				type1 = 'all-users';
			}
			else if (BX.util.in_array(type, ['contacts', 'companies', 'leads', 'deals']))
			{
				type1 = 'crm';
			}

			if (type == 'sonetgroups')
			{
				prefix = 'SG';
			}
			else if (type == 'groups')
			{
				prefix = 'UA';
			}
			else if (type == 'users')
			{
				prefix = 'U';
			}
			else if (type == 'department')
			{
				prefix = 'DR';
			}
			else if (type == 'contacts')
			{
				prefix = 'CRMCONTACT';
			}
			else if (type == 'companies')
			{
				prefix = 'CRMCOMPANY';
			}
			else if (type == 'leads')
			{
				prefix = 'CRMLEAD';
			}
			else if (type == 'deals')
			{
				prefix = 'CRMDEAL';
			}

			var stl = (bUndeleted ? ' bx-destination-undelete' : '');
			stl += (type == 'sonetgroups' && typeof window['arExtranetGroupID'] != 'undefined' && BX.util.in_array(item.entityId, window['arExtranetGroupID']) ? ' bx-destination-extranet' : '');

			var el = BX.create("span", {
				attrs : {
					'data-id' : item.id
				},
				props : {
					className : "bx-destination bx-destination-"+type1+stl
				},
				children: [
					BX.create("span", {
						props : {
							'className' : "bx-destination-text"
						},
						html : item.name
					})
				]
			});

			if(!bUndeleted)
			{
				el.appendChild(BX.create("span", {
					props : {
						'className' : "bx-destination-del-but"
					},
					events : {
						'click' : function(e){
							BX.SocNetLogDestination.deleteItem(item.id, type, id);
							BX.PreventDefault(e)
						},
						'mouseover' : function(){
							BX.addClass(this.parentNode, 'bx-destination-hover');
						},
						'mouseout' : function(){
							BX.removeClass(this.parentNode, 'bx-destination-hover');
						}
					}
				}));
			}
			BX.onCustomEvent(this.nodes[id], 'select', [item, el, prefix]);
		},
		unSelect : function(item, type, search, id)
		{
			BX.onCustomEvent(this.nodes[id], 'unSelect', [item]);
		},
		openDialog : function(id)
		{
			BX.onCustomEvent(this.nodes[id], 'openDialog', []);
		},
		closeDialog : function(id)
		{
			if (!BX.SocNetLogDestination.isOpenSearch())
			{
				BX.onCustomEvent(this.nodes[id], 'closeDialog', []);
				this.disableBackspace();
			}
		},
		closeSearch : function(id)
		{
			if (!BX.SocNetLogDestination.isOpenSearch())
			{
				BX.onCustomEvent(this.nodes[id], 'closeSearch', []);
				this.disableBackspace();
			}
		},
		disableBackspace : function()
		{
			if (BX.SocNetLogDestination.backspaceDisable || BX.SocNetLogDestination.backspaceDisable !== null)
				BX.unbind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable);

			BX.bind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable = function(event){
				if (event.keyCode == 8)
				{
					BX.PreventDefault(event);
					return false;
				}
				return true;
			});
			setTimeout(function(){
				BX.unbind(window, 'keydown', BX.SocNetLogDestination.backspaceDisable);
				BX.SocNetLogDestination.backspaceDisable = null;
			}, 5000);
		}
	};
	var DestinationInput = function(id, node, inputName)
	{
		this.node = node;
		this.id = id;
		this.inputName = inputName;
		this.node.appendChild(BX.create('SPAN', {
			props : { className : "bx-destination-wrap" },
			html : [
				'<span id="', this.id, '-container"><span class="bx-destination-wrap-item"></span></span>',
				'<span class="bx-destination-input-box" id="', this.id, '-input-box">',
				'<input type="text" value="" class="bx-destination-input" id="', this.id, '-input">',
				'</span>',
				'<a href="#" class="bx-destination-add" id="', this.id, '-add-button"></a>'
			].join('')}));
		BX.defer_proxy(this.bind, this)();
	};
	DestinationInput.prototype = {
		bind : function()
		{
			this.nodes = {
				inputBox : BX(this.id + '-input-box'),
				input : BX(this.id + '-input'),
				container : BX(this.id + '-container'),
				button : BX(this.id + '-add-button')
			};
			BX.bind(this.nodes.input, 'keyup', BX.proxy(this.search, this));
			BX.bind(this.nodes.input, 'keydown', BX.proxy(this.searchBefore, this));
			BX.bind(this.nodes.button, 'click', BX.proxy(function(e){BX.SocNetLogDestination.openDialog(this.id); BX.PreventDefault(e); }, this));
			BX.bind(this.nodes.container, 'click', BX.proxy(function(e){BX.SocNetLogDestination.openDialog(this.id); BX.PreventDefault(e); }, this));
			this.onChangeDestination();
			BX.addCustomEvent(this.node, 'select', BX.proxy(this.select, this));
			BX.addCustomEvent(this.node, 'unSelect', BX.proxy(this.unSelect, this));
			BX.addCustomEvent(this.node, 'delete', BX.proxy(this.delete, this));
			BX.addCustomEvent(this.node, 'openDialog', BX.proxy(this.openDialog, this));
			BX.addCustomEvent(this.node, 'closeDialog', BX.proxy(this.closeDialog, this));
			BX.addCustomEvent(this.node, 'closeSearch', BX.proxy(this.closeSearch, this));
		},
		select : function(item, el, prefix)
		{
			if(!BX.findChild(this.nodes.container, { attr : { 'data-id' : item.id }}, false, false))
			{
				el.appendChild(BX.create("INPUT", { props : {
					type : "hidden",
					name : (this.inputName + '[]'),
					value : item.entityId
				}
				}));
				this.nodes.container.appendChild(el);
			}
			this.onChangeDestination();
		},
		unSelect : function(item)
		{
			var elements = BX.findChildren(this.nodes.container, {attribute: {'data-id': ''+item.id+''}}, true);
			if (elements !== null)
			{
				for (var j = 0; j < elements.length; j++)
					BX.remove(elements[j]);
			}
			this.onChangeDestination();
		},
		onChangeDestination : function()
		{
			this.nodes.input.innerHTML = '';
			this.nodes.button.innerHTML = (BX.SocNetLogDestination.getSelectedCount(this.id) <= 0 ? BX.message("LM_ADD1") : BX.message("LM_ADD2"));
		},
		openDialog : function()
		{
			BX.style(this.nodes.inputBox, 'display', 'inline-block');
			BX.style(this.nodes.button, 'display', 'none');
			BX.focus(this.nodes.input);
		},
		closeDialog : function()
		{
			if (this.nodes.input.value.length <= 0)
			{
				BX.style(this.nodes.inputBox, 'display', 'none');
				BX.style(this.nodes.button, 'display', 'inline-block');
				this.nodes.input.value = '';
			}
		},
		closeSearch : function()
		{
			if (this.nodes.input.value.length > 0)
			{
				BX.style(this.nodes.inputBox, 'display', 'none');
				BX.style(this.nodes.button, 'display', 'inline-block');
				this.nodes.input.value = '';
			}
		},
		searchBefore : function(event)
		{
			if (event.keyCode == 8 && this.nodes.input.value.length <= 0)
			{
				BX.SocNetLogDestination.sendEvent = false;
				BX.SocNetLogDestination.deleteLastItem(this.id);
			}
			return true;
		},
		search : function(event)
		{
			if (event.keyCode == 16 || event.keyCode == 17 || event.keyCode == 18 || event.keyCode == 20 || event.keyCode == 244 || event.keyCode == 224 || event.keyCode == 91)
				return false;

			if (event.keyCode == 13)
			{
				BX.SocNetLogDestination.selectFirstSearchItem(this.id);
				return true;
			}
			if (event.keyCode == 27)
			{
				this.nodes.input.value = '';
				BX.style(this.nodes.button, 'display', 'inline');
			}
			else
			{
				BX.SocNetLogDestination.search(this.nodes.input.value, true, this.id);
			}

			if (!BX.SocNetLogDestination.isOpenDialog() && this.nodes.input.value.length <= 0)
			{
				BX.SocNetLogDestination.openDialog(this.id);
			}
			else if (BX.SocNetLogDestination.sendEvent && BX.SocNetLogDestination.isOpenDialog())
			{
				BX.SocNetLogDestination.closeDialog();
			}
			if (event.keyCode == 8)
			{
				BX.SocNetLogDestination.sendEvent = true;
			}
			return true;
		}
	};

	BX.ViGroupEdit = function(params)
	{
		this.node = params.node;
		this.destinationParams = params.destinationParams;
		this.groupListUrl = params.groupListUrl;
		this.inlineMode = params.inlineMode;
		this.popupTooltip = {};
		this.init();
	};

	BX.ViGroupEdit.prototype.init = function()
	{
		this.bindEvents();

		this.destination = new Destination(this.destinationParams);
		this.destination.setInput(BX('users_for_queue'), 'USERS');
	};

	BX.ViGroupEdit.prototype.bindEvents = function()
	{
		var self = this;
		var contextHelpNodes = BX.findChildrenByClassName(BX('tel-set-main-wrap'), "tel-context-help");
		if(BX.type.isArray(contextHelpNodes))
		{
			contextHelpNodes.forEach(function(helpNode, i)
			{
				helpNode.setAttribute('data-id', i);
				BX.bind(helpNode, 'mouseover', function()
				{
					var id = this.getAttribute('data-id');
					var text = this.getAttribute('data-text');
					self.showTooltip(id, this, text);
				});
				BX.bind(helpNode, 'mouseout', function()
				{
					var id = this.getAttribute('data-id');
					self.hideTooltip(id);
				});
			});
		}

		BX.bind(BX('vi_no_answer_rule'), 'change', function(e)
		{
			BX.PreventDefault(e);

			switch (e.target.value)
			{
				case Rule.pstn_specific:
					BX('vi_forward_number').style.height = '55px';
					BX('vi_next_queue').style.height = '0';
					break;
				case Rule.next_queue:
					BX('vi_forward_number').style.height = '0';
					BX('vi_next_queue').style.height = '55px';
					break;
				default:
					BX('vi_forward_number').style.height = '0';
					BX('vi_next_queue').style.height = '0';
					break;
			}

		});

		var submitNode = this.getNode('vi-group-edit-submit');
		if(submitNode)
			BX.bind(submitNode, 'click', this._onSubmitClick.bind(this));

		var cancelNode = this.getNode('vi-group-edit-cancel');
		if(cancelNode)
			BX.bind(submitNode, 'click', this._onCancelClick.bind(this));
	};

	BX.ViGroupEdit.prototype.getNode = function(role, context)
	{
		if (!context)
			context = this.node;

		return context ? context.querySelector('[data-role="' + role + '"]') : null;
	};

	BX.ViGroupEdit.prototype.showTooltip = function(id, bind, text)
	{
		if (this.popupTooltip[id])
			this.popupTooltip[id].close();


		this.popupTooltip[id] = new BX.PopupWindow('bx-voximplant-tooltip', bind, {
			lightShadow: true,
			autoHide: false,
			darkMode: true,
			offsetLeft: 0,
			offsetTop: 2,
			bindOptions: {position: "top"},
			zIndex: 200,
			events : {
				onPopupClose : function() {this.destroy()}
			},
			content : BX.create("div", { attrs : { style : "padding-right: 5px; width: 250px;" }, html: text})
		});
		this.popupTooltip[id].setAngle({offset:13, position: 'bottom'});
		this.popupTooltip[id].show();

		return true;
	};

	BX.ViGroupEdit.prototype.hideTooltip = function(id)
	{
		this.popupTooltip[id].close();
		this.popupTooltip[id] = null;
	};

	BX.ViGroupEdit.prototype._onSubmitClick = function(e)
	{
		var self = this;
		var formData = new FormData();
		var formElements = this.node.querySelectorAll('input, select');
		var element;
		for (var i = 0; i < formElements.length; i++)
		{
			element = formElements.item(i);
			if(element.tagName.toUpperCase() == 'INPUT')
			{
				switch(element.type.toUpperCase())
				{
					case 'TEXT':
						formData.append(element.name, element.value);
						break;
					case 'HIDDEN':
						formData.append(element.name, element.value);
						break;
					case 'CHECKBOX':
						if(element.checked)
							formData.append(element.name, element.value);
						break;
				}
			}
			else if(element.tagName.toUpperCase() == 'SELECT')
			{
				formData.append(element.name, element.value);
			}
		}

		var saveButton = this.getNode("vi-group-edit-submit");
		var waitNode = BX.create('span', {props : {className : "wait"}});

		BX.addClass(saveButton, "webform-small-button-wait webform-small-button-active");
		saveButton.appendChild(waitNode);

		BX.ajax({
			url: AJAX_URL,
			method: 'POST',
			data: formData,
			preparePost: false,
			onsuccess: function(response)
			{
				BX.removeClass(saveButton, "webform-small-button-wait webform-small-button-active");
				BX.remove(waitNode);
				try
				{
					response = JSON.parse(response)
				}
				catch (e)
				{
					BX.debug('Error decoding server response');
					return false;
				}

				if(response.SUCCESS === true)
				{
					if(self.inlineMode)
					{
						BX.onCustomEvent(window, 'onViGroupSaved', [response.DATA]);
					}
					else
					{
						jsUtils.Redirect([], self.groupListUrl);
					}
				}
				else
				{
					alert(response.ERROR)
				}
			},
			onfailure: function()
			{
				BX.removeClass(saveButton, "webform-small-button-wait webform-small-button-active");
				BX.remove(waitNode);
				BX.debug('Failed to save group');
			}
		})
	};

	BX.ViGroupEdit.prototype._onCancelClick = function(e)
	{
		BX.onCustomEvent(window, 'onViGroupEditCanceled', []);
	}
})();