;(function(window){
	if (!!window.BX.VoxImplantConfigEdit)
		return;

	var destination = function(params, type) {
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
	}, destinationInstance = null;
	destination.prototype = {
		setInput : function(node, inputName)
		{
			node = BX(node);
			if (!!node && !node.hasAttribute("bx-destination-id"))
			{
				var id = 'destination' + ('' + new Date().getTime()).substr(6), res;
				node.setAttribute('bx-destination-id', id);
				res = new destInput(id, node, inputName);
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
	var destInput = function(id, node, inputName)
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
	destInput.prototype = {
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
						name : (this.inputName + '[' + prefix + '][]'),
						value : item.id
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

	window.BX.VoxImplantConfigEdit = {
		ajaxUrl: '/bitrix/components/bitrix/voximplant.config.edit/ajax.php',
		popupTooltip: {},
		settingsOpened: false,
		bindEvents : function()
		{
			BX.bind(BX('vi_crm_forward'), 'change', function(e)
			{
				if (BX('vi_crm_forward').checked)
					BX('vi_crm_rule').style.height = '40px';
				else
					BX('vi_crm_rule').style.height = '0';
			});

			var inputDirectCode = BX('input-direct_code');
			if (inputDirectCode)
			{
				BX.bind(BX('input-direct_code'), 'change', function(e)
				{
					if (BX('input-direct_code').checked)
						BX('vi-direct-code-rule').style.height = '40px';
					else
						BX('vi-direct-code-rule').style.height = '0';
				});
			}

			BX.bind(BX('vi-group-show-config'), 'click', BX.VoxImplantConfigEdit.onShowSettingsClick);
			BX.bind(BX('vi-group-id-select'), 'change',  BX.VoxImplantConfigEdit._onGroupIdChanged);
			BX.addCustomEvent(window, 'onViGroupSaved', BX.VoxImplantConfigEdit._onGroupSaved);
			BX.addCustomEvent(window, 'onViGroupEditCanceled', BX.VoxImplantConfigEdit._onViGroupEditCanceled);
		},
		initDestination : function(node, inputName, params)
		{
			if (destinationInstance === null)
				destinationInstance = new destination(params);
			destinationInstance.setInput(BX(node), inputName);

		},
		loadMelody : function(curId, params)
		{
			if (typeof params !== "object")
				return;

			var inputName = params.INPUT_NAME || "",
				defaultMelody = params.DEFAULT_MELODY || "",
				mfi = BX["MFInput"] ? BX.MFInput.get(curId) : null;
			BX.bind(BX("config_edit_form").elements["MELODY_LANG"], "change", function() {
				if (!(!!BX("config_edit_form").elements[inputName] && !!BX("config_edit_form").elements[inputName]))
					window.jwplayer(curId+"player_div").load( [ { file : defaultMelody.replace("#LANG_ID#", this.value) } ] );
			});
			BX(curId+'span').appendChild(BX('file_input_'+curId));
			if (mfi)
			{
				BX.bind(BX(curId+'default'), "click", function() {
					mfi.clear();
				});
				BX.addCustomEvent(mfi, "onDeleteFile", function(){
					BX.hide(BX(curId+'default'));
					BX.show(BX(curId+'notice'));
					window.jwplayer(curId+"player_div").load( [ { file : defaultMelody.replace("#LANG_ID#", BX("config_edit_form").elements["MELODY_LANG"].value) } ] );
				});
				BX.addCustomEvent(mfi, "onUploadDone", function(file, item){
					BX.show(BX(curId+'default'));
					BX.hide(BX(curId+'notice'));
					if (!!window["jwplayer"])
					{
						window.jwplayer(curId+"player_div").load( [ { file : file["url"] + (file["url"].indexOf(".mp3") > 0 ? "" : "&/melody.mp3" ) } ] );
					}
				});
			}
			else
			{
				BX.bind(BX(curId+'default'), "click", function() {
					window["FILE_INPUT_"+curId]._deleteFile(BX('config_edit_form').elements[inputName]);
				});
				BX.addCustomEvent(window["FILE_INPUT_"+curId], 'onSubmit', function() {
					BX(curId+'span').appendChild(
						BX.create('SPAN', {attrs: {id : curId+'waiter'}, props : {className : "webform-field-upload-list"}, html : '<i></i>'})
					);
				});
				BX.addCustomEvent(window["FILE_INPUT_"+curId], 'onFileUploaderChange', function() {
					window["FILE_INPUT_"+curId].INPUT.disabled = false;
				});
				BX.addCustomEvent(window["FILE_INPUT_"+curId], 'onDeleteFile', function(id) {
					BX.hide(BX(curId+'default'));
					BX(curId+'notice').innerHTML = BX.message("VI_CONFIG_EDIT_DOWNLOAD_TUNE_TIP");
					window.jwplayer(curId+"player_div").load( [ { file : defaultMelody.replace("#LANG_ID#", BX("config_edit_form").elements["MELODY_LANG"].value) } ] );
					window["FILE_INPUT_"+curId].INPUT.disabled = false;
				});

				BX.addCustomEvent(window["FILE_INPUT_"+curId], 'onDone', function(files, id, err) {
					BX.remove(BX(curId+'waiter'));
					if (!!files && files.length > 0)
					{
						var n = BX(curId+'notice');
						if (err === false && !!files[0])
						{
							if (id != 'init')
							{
								n.innerHTML = BX.message('VI_CONFIG_EDIT_UPLOAD_SUCCESS');
								if (!!window["jwplayer"])
								{
									window.jwplayer(curId+"player_div").load( [ { file : files[0]["fileURL"] } ] );
								}
								BX(curId+'default').style.display = '';
							}
						}
						else if (!!files[0] && files[0]["error"])
						{
							n.innerHTML = files[0]["error"];
						}
					}
				});
			}
		},
		showTooltip : function(id, bind, text)
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
		},
		hideTooltip : function(id)
		{
			this.popupTooltip[id].close();
			this.popupTooltip[id] = null;
		},
		onShowSettingsClick: function(e)
		{
			if(BX.VoxImplantConfigEdit.settingsOpened)
				BX.VoxImplantConfigEdit.hideGroupSetting();
			else
				BX.VoxImplantConfigEdit.showGroupSetting({
					groupId: BX('vi-group-id-select').value
				});
		},
		showGroupSetting: function (params)
		{
			BX.VoxImplantConfigEdit.settingsOpened = true;
			var placeholder = BX('vi-group-settings-placeholder');
			var groupId = params.groupId;
			var button = BX('vi-group-show-config');
			button.innerText = BX.message('VI_CONFIG_GROUP_SETTINGS_HIDE');
			var groupSelect = BX('vi-group-id-select');
			groupSelect.disabled = true;
			var data = {
				'sessid': BX.bitrix_sessid(),
				'GROUP_SETTINGS': 'Y',
				'ID': groupId
			};

			BX.ajax({
				url: BX.VoxImplantConfigEdit.ajaxUrl,
				method: 'POST',
				dataType: 'html',
				data: data,
				onsuccess: function(HTML)
				{
					placeholder.innerHTML = HTML;
					placeholder.style.maxHeight = '800px';
				}
			});
		},
		hideGroupSetting: function ()
		{
			var placeholder = BX('vi-group-settings-placeholder');
			var button = BX('vi-group-show-config');
			button.innerText = BX.message('VI_CONFIG_GROUP_SETTINGS');
			var groupSelect = BX('vi-group-id-select');
			groupSelect.disabled = false;
			
			placeholder.style.maxHeight = 0;
			setTimeout(function()
			{
				BX.VoxImplantConfigEdit.settingsOpened = false;
				placeholder.innerHTML = '';
			}, 300)
		},
		_onGroupIdChanged: function(e)
		{
			var groupId = e.target.value;
			if(groupId === 'new')
			{
				BX.VoxImplantConfigEdit.showGroupSetting({
					groupId: 0
				})
			}
			BX.PreventDefault(e);
		},
		_onGroupSaved: function(params)
		{
			BX.VoxImplantConfigEdit.hideGroupSetting();
			var groupFields = params.GROUP;
			if(!groupFields.ID)
				return;

			var groupSelect = BX('vi-group-id-select');
			var optionFound = false;
			var optionNode;
			for(var i = 0; i < groupSelect.options.length; i++)
			{
				optionNode = groupSelect.options.item(i);
				if(optionNode.value == groupFields.ID)
				{
					optionNode.innerText = BX.util.htmlspecialchars(groupFields.NAME);
					optionFound = true;
					break;
				}
			}
			if(!optionFound)
			{
				groupSelect.add(BX.create('option', {attrs: {value: groupFields.ID}, text: BX.util.htmlspecialchars(groupFields.NAME)}));
			}
			groupSelect.value = groupFields.ID;
		},
		_onViGroupEditCanceled: function()
		{
			BX.VoxImplantConfigEdit.hideGroupSetting();
		}
	};
	BX.ready(function(){
		var arNodes = BX.findChildrenByClassName(BX('tel-set-main-wrap'), "tel-context-help");
		for (var i = 0; i < arNodes.length; i++)
		{
			arNodes[i].setAttribute('data-id', i)
			BX.bind(arNodes[i], 'mouseover', function(){
				var id = this.getAttribute('data-id');
				var text = this.getAttribute('data-text');

				BX.VoxImplantConfigEdit.showTooltip(id, this, text);
			});
			BX.bind(arNodes[i], 'mouseout', function(){
				var id = this.getAttribute('data-id');

				BX.VoxImplantConfigEdit.hideTooltip(id);
			});
		}
	});


	BX.ViCallerId = {
		ajaxUrl: '/bitrix/components/bitrix/voximplant.config.edit/ajax.php'
	};

	BX.ViCallerId.init = function(params)
	{
		BX.ViCallerId.inputNumber = params.number;

		BX.ViCallerId.placeholder = params.placeholder;
		BX.ViCallerId.number = params.number;
		BX.ViCallerId.verified = params.verified;
		BX.ViCallerId.verifiedUntil = params.verifiedUntil;

		BX.ViCallerId.phoneInput = null;
		BX.ViCallerId.codeInput = null;
		BX.ViCallerId.codeError = null;
		BX.ViCallerId.phoneNotice = null;
		BX.ViCallerId.blockAjax = false;
		BX.ViCallerId.blockVerify = false;

		BX.ViCallerId.drawState();

		BX.bind(BX('vi_link_options'), 'click', function(e)
		{
			if (BX('vi_link_options_div').style.display == 'none')
			{
				BX.removeClass(BX(this), 'webform-button-create');
				BX('vi_link_options_div').style.display = 'block';
			}
			else
			{
				BX.addClass(BX(this), 'webform-button-create');
				BX('vi_link_options_div').style.display = 'none';
			}
			BX.PreventDefault(e);
		});
	};

	BX.ViCallerId.drawState = function(params)
	{
		var inputNode = null;
		var codeNode = null;
		var buttonNode = null;
		var noticeNode = null;

		params = typeof (params) == 'object'? params: {};
		params.state = params.state? parseInt(params.state): 1;

		if (params.state == 1)
		{
			inputNode = BX.create("div", {props : { className : "tel-new-num-form" }, children: [
				BX.create("span", { props : { className : "tel-balance-phone-icon" }}),
				BX.create("a", {
					props : { attrs: { href: '#put-phone'},
						className : "tel-balance-phone-url" },
					events:
					{
						click : function(e)
						{
							BX.ViCallerId.drawState({
								state: BX.ViCallerId.number.length <= 0? 2: 3
							});
							return BX.PreventDefault(e);
						}
					},
					html: BX.ViCallerId.number.length <= 0? BX.message('TELEPHONY_PUT_PHONE'): BX.message('TELEPHONY_VERIFY_PHONE')
				}),
				BX.ViCallerId.number.length <= 0? null: BX.create("span", { props : { className : "tel-num-change-text"}, html: ' '+BX.message('TELEPHONY_OR')}),
				BX.ViCallerId.number.length <= 0? null: BX.create("a", {
					props : { attrs: { href: '#change-phone'},
						className : "tel-balance-phone-url" },
					events:
					{
						click : function(e)
						{
							BX.ViCallerId.removePhone();
							return BX.PreventDefault(e);
						}
					},
					html: BX.message('TELEPHONY_PUT_PHONE_AGAING')
				})
			]})
		}
		else if (params.state == 2 || params.state == 3)
		{
			inputNode = BX.create("div", {attrs: {id: 'tel-new-num-form'}, props : { className : "tel-new-num-form "+(params.state == 3? 'tel-new-num-form-disable': '')  }, children: [
				BX.create("span", { props : { className : "tel-balance-phone-icon" }}),
				BX.create("a", {
					props : { attrs: { href: '#put-phone'},
						className : "webform-small-button"},
					events:
					{
						click : params.state == 3? null: function(e)
						{
							BX.ViCallerId.connectPhone(BX.ViCallerId.phoneInput.value);
							return BX.PreventDefault(e);
						}
					},
					children: [
						BX.create("span", { props : { className : "webform-small-button-left" }}),
						BX.create("span", { props : { className : "webform-small-button-text" }, html: BX.message('TELEPHONY_CONFIRM')}),
						BX.create("span", { props : { className : "webform-small-button-right" }})
					]
				}),
				BX.create("div", { props : { className : "tel-new-num-inp-wrap" }, children: [
					BX.ViCallerId.phoneInput = BX.create("input", { props : { className : "tel-new-num-inp"}, attrs: { placeholder: BX.message('TELEPHONY_EXAMPLE'), type: 'text', value: BX.ViCallerId.inputNumber, disabled: params.state == 3}})
				]})
			]});
			if (params.state == 2)
			{
				BX.ViCallerId.phoneNotice = noticeNode = BX.create("div", { props : { className : "tel-new-num-notice" }, html: BX.message('TELEPHONY_VERIFY_CODE')+'<br>'+BX.message('TELEPHONY_VERIFY_CODE_4')+'<br><br>'+BX.message('TELEPHONY_VERIFY_CODE_3')});
			}
			else if (params.state == 3)
			{
				BX.ViCallerId.verifyPhone();
				BX.ViCallerId.phoneNotice = noticeNode = BX.create("div", { props : { className : "tel-new-num-notice" }, html: BX.message('TELEPHONY_VERIFY_CODE_2')+'<br>'+BX.message('TELEPHONY_VERIFY_CODE_4')+'<br><br>'+BX.message('TELEPHONY_VERIFY_CODE_3')});
				codeNode = BX.create("div", {props : { className : "tel-new-num-pass" }, children: [
					BX.create("span", { props : { className : "tel-new-num-pass-title" }, html: BX.message('TELEPHONY_PUT_CODE')}),
					BX.create("br"),
					BX.ViCallerId.codeInput = BX.create("input", { props : { className : "tel-new-num-inp"}, attrs: { type: 'text' }}),
					BX.ViCallerId.codeError = BX.create("span", { props : { className : "tel-new-num-pass-error" }, html: ''}),
					BX.create("a", {
						props : { attrs: { href: '#put-code'},
							className : "webform-small-button webform-small-button-accept" },
						events:
						{
							click : function(e)
							{
								BX.ViCallerId.activatePhone(BX.ViCallerId.codeInput.value);
								return BX.PreventDefault(e);
							}
						},
						children: [
							BX.create("span", { props : { className : "webform-small-button-left" }}),
							BX.create("span", { props : { className : "webform-small-button-text" }, html: BX.message('TELEPHONY_JOIN')}),
							BX.create("span", { props : { className : "webform-small-button-right" }})
						]
					}),
					BX.create("br"),
					BX.create("br"),
					BX.create("br"),
					BX.create("a", {
						attrs: { href: '#put-code', style: 'margin-left: 3px; margin-right: 7px;'},
						props : { className : "webform-small-button" },
						events:
						{
							click : function(e)
							{
								BX.ViCallerId.verifyPhone();
								return BX.PreventDefault(e);
							}
						},
						children: [
							BX.create("span", { props : { className : "webform-small-button-left" }}),
							BX.create("span", { props : { className : "webform-small-button-text" }, html: BX.message('TELEPHONY_RECALL')}),
							BX.create("span", { props : { className : "webform-small-button-right" }})
						]
					}),
					BX.create("a", {
						props : { attrs: { href: '#put-code'},
							className : "webform-small-button" },
						events:
						{
							click : function(e)
							{
								BX.ViCallerId.removePhone();
								return BX.PreventDefault(e);
							}
						},
						children: [
							BX.create("span", { props : { className : "webform-small-button-left" }}),
							BX.create("span", { props : { className : "webform-small-button-text" }, html: BX.message('TELEPHONY_PUT_PHONE_AGAING')}),
							BX.create("span", { props : { className : "webform-small-button-right" }})
						]
					})
				]});
				buttonNode = null;
			}
		}

		var nodes = [];
		if (BX.ViCallerId.number.length <= 0 || !BX.ViCallerId.verified)
		{
			var inputText = null;
			if (BX.ViCallerId.number.length <= 0)
			{
				inputText = BX.create("div", { props : { className : "tel-balance-text" }, children: [
					BX.create("span", { props : { className : "tel-balance-text-bold"}, html: BX.message('TELEPHONY_EMPTY_PHONE') }),
					BX.create("span", { html: BX.message('TELEPHONY_EMPTY_PHONE_DESC')})
				]});
			}
			else
			{
				inputText = BX.create("div", { children: [
					BX.create("div", { props : { className : "tel-num-not-conf-text"}, html: BX.message('TELEPHONY_CONFIRM_PHONE') }),
					BX.create("div", { props : { className : "tel-num-not-conf-block tel-num-block"}, html: '+'+BX.ViCallerId.number }),
					BX.create("div", { props : { className : "tel-balance-text" }, children: [
						BX.create("strong", { html: BX.message('TELEPHONY_EMPTY_PHONE_DESC')})
					]})
				]});
			}

			nodes = [
				BX.create("div", { props : { className : "tel-new-num-block" }, children : [
					inputText,
					inputNode,
					noticeNode,
					codeNode,
					buttonNode
				]})
			];
		}
		else
		{
			nodes = [
				BX.create("div", { props : { className : "tel-balance-text" }, children: [
					BX.create("strong", { props : { className : "tel-balance-text-bold"}, html: BX.message('TELEPHONY_PHONE') })
				]}),
				BX.create("div", { props : { className : "tel-num-block"}, html: '+'+BX.ViCallerId.number }),
				BX.create("div", { props : { className : "tel-num-change-block" }, children: [
					BX.create("span", { props : { className : "tel-num-change-text"}, html: BX.message('TELEPHONY_JOIN_TEXT')+" "}),
					BX.create("a", {
						props : { attrs: { href: '#change-phone'},
							className : "tel-num-change-link" },
						events:
						{
							click : function(e)
							{
								if (confirm(BX.message('TELEPHONY_DELETE_CONFIRM')))
								{
									BX.ViCallerId.removePhone();
								}
								return BX.PreventDefault(e);
							}
						},
						html: BX.message('TELEPHONY_REJOIN')
					})
				]}),
				BX.create("div", { props : { className : "tel-set-item-block" }, children: [
					BX.create("span", { props : { className : "tel-num-alert-text"}, html: BX.message('TELEPHONY_CONFIRM_DATE').replace('#DATE#', '<b>'+BX.ViCallerId.verifiedUntil+'</b>') })
				]})
			];
		}

		BX.ViCallerId.drawOnPlaceholder(nodes);
	};

	BX.ViCallerId.connectPhone = function(number)
	{
		if (BX.ViCallerId.blockAjax)
			return false;

		BX.showWait();
		BX.ViCallerId.blockAjax = true;

		if (BX('tel-new-num-form'))
		{
			BX.addClass(BX('tel-new-num-form'), 'tel-new-num-form-disable');
		}

		BX.ajax({
			url: BX.ViCallerId.ajaxUrl,
			method: 'POST',
			dataType: 'json',
			timeout: 30,
			data: {'VI_CONNECT': 'Y', 'NUMBER': number, 'VI_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
			onsuccess: BX.delegate(function(data)
			{
				BX.closeWait();
				BX.ViCallerId.blockAjax = false;
				if (data.ERROR == '')
				{
					BX.ViCallerId.inputNumber = number;
					if (data.VERIFIED)
					{
						BX.ViCallerId.number = data.NUMBER;
						BX.ViCallerId.verified = true;
						BX.ViCallerId.verifiedUntil = data.VERIFIED_UNTIL;
						BX.ViCallerId.drawState({state: 1});
					}
					else
					{
						BX.ViCallerId.verified = false;
						BX.ViCallerId.drawState({state: 3});
					}
				}
				else
				{
					BX.addClass(BX.ViCallerId.phoneNotice, 'tel-new-num-notice-err');
					BX.ViCallerId.phoneNotice.innerHTML = data.ERROR == 'MONEY_LOW'? BX.message('TELEPHONY_ERROR_MONEY_LOW'): BX.message('TELEPHONY_ERROR_PHONE');

					BX.addClass(BX.ViCallerId.phoneInput, 'tel-new-num-inp-err');
					BX.removeClass(BX('tel-new-num-form'), 'tel-new-num-form-disable');
				}
			}, this),
			onfailure: function(){
				BX.closeWait();
				BX.ViCallerId.blockAjax = false;
			}

		});
	};

	BX.ViCallerId.verifyPhone = function()
	{
		if (BX.ViCallerId.blockAjax)
			return false;

		if (BX.ViCallerId.blockVerify)
		{
			alert(BX.message('TELEPHONY_VERIFY_ALERT'));
			return true;
		}
		setTimeout(function(){
			BX.ViCallerId.blockVerify = false;
		}, 60000);
		BX.ViCallerId.blockVerify = true;

		BX.showWait();
		BX.ViCallerId.blockAjax = true;
		BX.ajax({
			url: BX.ViCallerId.ajaxUrl,
			method: 'POST',
			dataType: 'json',
			timeout: 30,
			data: {'VI_VERIFY': 'Y', 'VI_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
			onsuccess: function(data){
				BX.closeWait();
				BX.ViCallerId.blockAjax = false;
				if (data.ERROR == '185' || data.ERROR == '183')
				{
					alert(BX.message('TELEPHONY_ERROR_BLOCK'));
					BX.ViCallerId.removePhone();
				}
			},
			onfailure: function(){
				BX.closeWait();
				BX.ViCallerId.blockVerify = false;
				BX.ViCallerId.blockAjax = false;
			}
		});
	};

	BX.ViCallerId.activatePhone = function(code)
	{
		if (BX.ViCallerId.blockAjax)
			return false;

		BX.showWait();
		BX.ViCallerId.blockAjax = true;
		BX.ajax({
			url: BX.ViCallerId.ajaxUrl,
			method: 'POST',
			dataType: 'json',
			timeout: 30,
			data: {'VI_ACTIVATE': 'Y', 'CODE': code, 'VI_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
			onsuccess: BX.delegate(function(data)
			{
				BX.ViCallerId.blockVerify = false;
				BX.closeWait();
				BX.ViCallerId.blockAjax = false;
				if (data.ERROR == '')
				{
					BX.ViCallerId.number = data.NUMBER;
					BX.ViCallerId.verified = true;
					BX.ViCallerId.verifiedUntil = data.VERIFIED_UNTIL;
					BX.ViCallerId.drawState({state: 1});
				}
				else
				{
					BX.ViCallerId.codeError.innerHTML = BX.message('TELEPHONY_WRONG_CODE');
					BX.addClass(BX.ViCallerId.codeInput, 'tel-new-num-inp-err');
				}
			}, this),
			onfailure: function(){
				BX.closeWait();
				BX.ViCallerId.blockVerify = false;
				BX.ViCallerId.blockAjax = false;
			}
		});
	};

	BX.ViCallerId.removePhone = function()
	{
		if (BX.ViCallerId.blockAjax)
			return false;

		BX.ViCallerId.blockVerify = false;

		BX.showWait();
		BX.ViCallerId.blockAjax = true;
		BX.ajax({
			url: BX.ViCallerId.ajaxUrl,
			method: 'POST',
			dataType: 'json',
			timeout: 30,
			data: {'VI_REMOVE': 'Y', 'VI_AJAX_CALL' : 'Y', 'sessid': BX.bitrix_sessid()},
			onsuccess: BX.delegate(function(data)
			{
				BX.closeWait();
				BX.ViCallerId.blockAjax = false;
				if (data.ERROR == '')
				{
					BX.ViCallerId.number = '';
					BX.ViCallerId.verified = false;
					BX.ViCallerId.drawState({state: 1});
				}
				else
				{
					alert(BX.message('TELEPHONY_ERROR_REMOVE'));
				}
			}, this),
			onfailure: function(){
				BX.closeWait();
				BX.ViCallerId.blockAjax = false;
			}
		});
	};

	BX.ViCallerId.drawOnPlaceholder = function(children)
	{
		BX.ViCallerId.placeholder.innerHTML = '';
		BX.adjust(BX.ViCallerId.placeholder, {children: children});
	};

})(window);