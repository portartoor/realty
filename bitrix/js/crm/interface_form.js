function BxCrmInterfaceForm(name, aTabs)
{
	var _this = this;
	this.name = name; // is form ID
	this.aTabs = aTabs;
	this.bExpandTabs = false;
	this.vars = {};
	this.oTabsMeta = {};
	this.aTabsEdit = [];
	this.oFields = {};
	this.menu = new PopupMenu('bxFormMenu_'+this.name, 1010);
	this.settingsMenu = [];
	this.tabSettingsWnd = null;
	this.fieldSettingsWnd = null;
	this.activeTabClass = 'bx-crm-view-tab-active';


	this.GetTabs = function()
	{
		var tabs = BX.findChildren(
			BX(this.name + '_tab_block'),
			{ "tagName": "a", "className": "bx-crm-view-tab" },
			false
		);
		return tabs ? tabs : [];
	};

	this.GetActiveTabId = function()
	{
		var tabs = this.GetTabs();
		for(var i = 0; i < tabs.length; i++)
		{
			var tab = tabs[i];
			if(BX.hasClass(tab, this.activeTabClass))
			{
				return tab.id.substring((this.name + '_tab_').length);
			}
		}

		return '';
	};

	this.ShowOnDemand = function(caller)
	{
		var sectionContainer = BX.findParent(caller, { 'tagName':'DIV', 'className':'bx-crm-view-fieldset' });
		var rows = BX.findChildren(sectionContainer, { 'tagName':'tr', 'className':'bx-crm-view-on-demand' }, true);

		if(!BX.type.isArray(rows))
		{
			return;
		}

		for(var i = 0; i < rows.length; i++)
		{
			rows[i].style.display = '';
		}

		if(caller)
		{
			BX.findParent(caller, { 'tagName':'tr', 'className':'bx-crm-view-show-more' }).style.display='none';
		}
	};

	this.SelectTab = function(tab_id)
	{
		var div = BX('inner_tab_' + tab_id);

		if(!div || div.style.display != 'none')
			return;

		for (var i = 0, cnt = this.aTabs.length; i < cnt; i++)
		{
			var tab = BX('inner_tab_'+this.aTabs[i]);
			if(!tab)
				continue;

			if(tab.style.display != 'none')
			{
				this.ShowTab(this.aTabs[i], false);
				tab.style.display = 'none';
				break;
			}
		}

		this.ShowTab(tab_id, true);
		div.style.display = 'block';

		var hidden = BX(this.name+'_active_tab');
		if(hidden)
			hidden.value = tab_id;
	};

	this.ShowTab = function(tab_id, on)
	{
		var id = this.name + '_tab_' + tab_id;
		var tabs = this.GetTabs();
		for(var i = 0; i < tabs.length; i++)
		{
			var tab = tabs[i];
			if(id !== tab.id)
			{
				continue;
			}

			if(on)
			{
				BX.addClass(tab, 'bx-crm-view-tab-active');
				BX.onCustomEvent(this, 'OnTabShow', [ tab_id ]);
			}
			else
			{
				BX.removeClass(tab, 'bx-crm-view-tab-active');
				BX.onCustomEvent(this, 'OnTabHide', [ tab_id ]);
			}

			break;
		}
	};

	this.HoverTab = function(tab_id, on)
	{
		var tab = document.getElementById('tab_'+tab_id);
		if(tab.className == 'bx-tab-selected')
			return;

		document.getElementById('tab_left_'+tab_id).className = (on? 'bx-tab-left-hover':'bx-tab-left');
		tab.className = (on? 'bx-tab-hover':'bx-tab');
		var tab_right = document.getElementById('tab_right_'+tab_id);
		tab_right.className = (on? 'bx-tab-right-hover':'bx-tab-right');
	};

	this.ShowDisabledTab = function(tab_id, disabled)
	{
		var tab = document.getElementById('tab_cont_'+tab_id);
		if(disabled)
		{
			tab.className = 'bx-tab-container-disabled';
			tab.onclick = null;
			tab.onmouseover = null;
			tab.onmouseout = null;
		}
		else
		{
			tab.className = 'bx-tab-container';
			tab.onclick = function(){_this.SelectTab(tab_id);};
			tab.onmouseover = function(){_this.HoverTab(tab_id, true);};
			tab.onmouseout = function(){_this.HoverTab(tab_id, false);};
		}
	};

	this.ToggleTabs = function(bSkipSave)
	{
		this.bExpandTabs = !this.bExpandTabs;

		var a = document.getElementById('bxForm_'+this.name+'_expand_link');
		a.title = (this.bExpandTabs? this.vars.mess.collapseTabs : this.vars.mess.expandTabs);
		a.className = (this.bExpandTabs? a.className.replace(/\s*bx-down/ig, ' bx-up') : a.className.replace(/\s*bx-up/ig, ' bx-down'));

		for(var i in this.aTabs)
		{
			var tab_id = this.aTabs[i];
			this.ShowTab(tab_id, false);
			this.ShowDisabledTab(tab_id, this.bExpandTabs);
			var div = document.getElementById('inner_tab_'+tab_id);
			div.style.display = (this.bExpandTabs? 'block':'none');
		}
		if(!this.bExpandTabs)
		{
			this.ShowTab(this.aTabs[0], true);
			var div = document.getElementById('inner_tab_'+this.aTabs[0]);
			div.style.display = 'block';
		}
		if(bSkipSave !== true)
			BX.ajax.get('/bitrix/components'+this.vars.component_path+'/settings.php?FORM_ID='+this.name+'&action=expand&expand='+(this.bExpandTabs? 'Y':'N')+'&sessid='+this.vars.sessid);
	};

	this.SetTheme = function(menuItem, theme)
	{
		BX.loadCSS(this.vars.template_path+'/themes/'+theme+'/style.css');

		var themeMenu = this.menu.GetMenuByItemId(menuItem.id);
		themeMenu.SetAllItemsIcon('');
		themeMenu.SetItemIcon(menuItem, 'checked');

		BX.ajax.get('/bitrix/components'+_this.vars.component_path+'/settings.php?FORM_ID='+this.name+'&GRID_ID='+this.vars.GRID_ID+'&action=settheme&theme='+theme+'&sessid='+this.vars.sessid);
	};

	this.ShowSettings = function()
	{
		var bCreated = false;
		if(!window['formSettingsDialog'+this.name])
		{
			window['formSettingsDialog'+this.name] = new BX.CDialog({
				'content':'<form name="form_settings_'+this.name+'"></form>',
				'title': this.vars.mess.settingsTitle,
				'width': this.vars.settingWndSize.width,
				'height': this.vars.settingWndSize.height,
				'resize_id': 'InterfaceFormSettingWnd'
			});
			bCreated = true;
		}

		window['formSettingsDialog'+this.name].ClearButtons();
		window['formSettingsDialog'+this.name].SetButtons([
			{
				'title': this.vars.mess.settingsSave,
				'action': function()
				{
					_this.SaveSettings();
					this.parentWindow.Close();
				}
			},
			BX.CDialog.prototype.btnCancel
		]);

		window['formSettingsDialog'+this.name].Show();

		var form = document['form_settings_'+this.name];

		if(bCreated)
			form.appendChild(BX('form_settings_'+this.name));

		//editable data
		this.aTabsEdit = [];
		for(var i in this.oTabsMeta)
		{
			var fields = [];
			for(var j in this.oTabsMeta[i].fields)
				fields[fields.length] = BX.clone(this.oTabsMeta[i].fields[j]);
			this.aTabsEdit[this.aTabsEdit.length] = BX.clone(this.oTabsMeta[i]);
			this.aTabsEdit[this.aTabsEdit.length-1].fields = fields;
		}

		//tabs
		jsSelectUtils.deleteAllOptions(form.tabs);
		for(var i in this.aTabsEdit)
			form.tabs.options[form.tabs.length] = new Option(this.aTabsEdit[i].name, this.aTabsEdit[i].id, false, false);

		//fields
		form.tabs.selectedIndex = 0;
		this.OnSettingsChangeTab();

		//available fields
		this.aAvailableFields = BX.clone(this.oFields);
		jsSelectUtils.deleteAllOptions(form.all_fields);
		for(var i in this.aAvailableFields)
			form.all_fields.options[form.all_fields.length] = new Option(this.aAvailableFields[i].name, this.aAvailableFields[i].id, false, false);

		jsSelectUtils.sortSelect(form.all_fields);

		this.HighlightSections(form.all_fields);

		this.ProcessButtons();

		form.tabs.focus();
	};

	this.OnSettingsChangeTab = function()
	{
		var form = document['form_settings_'+this.name];
		var index = form.tabs.selectedIndex;

		jsSelectUtils.deleteAllOptions(form.fields);
		for(var i in this.aTabsEdit[index].fields)
		{
			var opt = new Option(this.aTabsEdit[index].fields[i].name, this.aTabsEdit[index].fields[i].id, false, false);
			if(this.aTabsEdit[index].fields[i].type == 'section')
				opt.className = 'bx-section';
			form.fields.options[form.fields.length] = opt;
		}

		this.ProcessButtons();
	};

	this.TabMoveUp = function()
	{
		var form = document['form_settings_'+this.name];
		var index = form.tabs.selectedIndex;

		if(index > 0)
		{
			var tab1 = BX.clone(this.aTabsEdit[index]);
			var tab2 = BX.clone(this.aTabsEdit[index-1]);
			this.aTabsEdit[index] = tab2;
			this.aTabsEdit[index-1] = tab1;
		}
		jsSelectUtils.moveOptionsUp(form.tabs);
	};

	this.TabMoveDown = function()
	{
		var form = document['form_settings_'+this.name];
		var index = form.tabs.selectedIndex;

		if(index < form.tabs.length-1)
		{
			var tab1 = BX.clone(this.aTabsEdit[index]);
			var tab2 = BX.clone(this.aTabsEdit[index+1]);
			this.aTabsEdit[index] = tab2;
			this.aTabsEdit[index+1] = tab1;
		}
		jsSelectUtils.moveOptionsDown(form.tabs);
	};

	this.TabEdit = function()
	{
		var form = document['form_settings_'+this.name];
		var tabIndex = form.tabs.selectedIndex;

		if(tabIndex < 0)
			return;

		this.ShowTabSettings(this.aTabsEdit[tabIndex],
			function()
			{
				var frm = document['tab_settings_'+_this.name];
				_this.aTabsEdit[tabIndex].name = frm.tab_name.value;
				_this.aTabsEdit[tabIndex].title = frm.tab_title.value;

				form.tabs[tabIndex].text = frm.tab_name.value;
			}
		);
	};

	this.TabAdd = function()
	{
		this.ShowTabSettings({'name':'', 'title':''},
			function()
			{
				var tab_id = 'tab_'+Math.round(Math.random()*1000000);

				var frm = document['tab_settings_'+_this.name];
				_this.aTabsEdit[_this.aTabsEdit.length] = {
					'id': tab_id,
					'name': frm.tab_name.value,
					'title': frm.tab_title.value,
					'fields': []
				};

				var form = document['form_settings_'+_this.name];
				form.tabs[form.tabs.length] = new Option(frm.tab_name.value, tab_id, true, true);
				_this.OnSettingsChangeTab();
			}
		);
	};

	this.TabDelete = function()
	{
		var form = document['form_settings_'+this.name];
		var tabIndex = form.tabs.selectedIndex;

		if(tabIndex < 0)
			return;

		//place to available fields before delete
		for(var i in this.aTabsEdit[tabIndex].fields)
		{
			this.aAvailableFields[this.aTabsEdit[tabIndex].fields[i].id] = this.aTabsEdit[tabIndex].fields[i];
			jsSelectUtils.addNewOption(form.all_fields, this.aTabsEdit[tabIndex].fields[i].id, this.aTabsEdit[tabIndex].fields[i].name, true, false);
		}

		this.HighlightSections(form.all_fields);

		this.aTabsEdit = BX.util.deleteFromArray(this.aTabsEdit, tabIndex);
		form.tabs.remove(tabIndex);

		if(form.tabs.length > 0)
		{
			var i = (tabIndex < form.tabs.length? tabIndex : form.tabs.length-1);
			form.tabs[i].selected = true;
			this.OnSettingsChangeTab();
		}
		else
		{
			jsSelectUtils.deleteAllOptions(form.fields);
			this.ProcessButtons();
		}
	};

	this.ShowTabSettings = function(data, action)
	{
		var wnd = this.tabSettingsWnd;
		if(!wnd)
		{
			this.tabSettingsWnd = wnd = new BX.CDialog({
				'content':'<form name="tab_settings_'+this.name+'">'+
					'<table width="100%">'+
					'<tr>'+
					'<td width="50%" align="right">'+this.vars.mess.tabSettingsName+'</td>'+
					'<td><input type="text" name="tab_name" size="30" value="" style="width:90%"></td>'+
					'</tr>'+
					'<tr>'+
					'<td align="right">'+this.vars.mess.tabSettingsCaption+'</td>'+
					'<td><input type="text" name="tab_title" size="30" value="" style="width:90%"></td>'+
					'</tr>'+
					'</table>'+
					'</form>',
				'title': this.vars.mess.tabSettingsTitle,
				'width': this.vars.tabSettingWndSize.width,
				'height': this.vars.tabSettingWndSize.height,
				'resize_id': 'InterfaceFormTabSettingWnd'
			});
		}
		wnd.ClearButtons();
		wnd.SetButtons([
			{
				'title': this.vars.mess.tabSettingsSave,
				'action': function(){
					action();
					this.parentWindow.Close();
				}
			},
			BX.CDialog.prototype.btnCancel
		]);
		wnd.Show();

		var form = document['tab_settings_'+this.name];
		form.tab_name.value = data.name;
		form.tab_title.value = data.title;
		form.tab_name.focus();
	};

	this.ShowFieldSettings = function(data, action)
	{
		var wnd = this.fieldSettingsWnd;
		if(!wnd)
		{
			this.fieldSettingsWnd = wnd = new BX.CDialog({
				'content':'<form name="field_settings_'+this.name+'">'+
					'<table width="100%">'+
					'<tr>'+
					'<td width="50%" align="right" id="field_name_'+this.name+'"></td>'+
					'<td><input type="text" name="field_name" size="30" value="" style="width:90%"></td>'+
					'</tr>'+
					'</table>'+
					'</form>',
				'width': this.vars.fieldSettingWndSize.width,
				'height': this.vars.fieldSettingWndSize.height,
				'resize_id': 'InterfaceFormFieldSettingWnd'
			});
		}

		wnd.SetTitle(data.type && data.type == 'section'? this.vars.mess.sectSettingsTitle : this.vars.mess.fieldSettingsTitle);
		BX('field_name_'+this.name).innerHTML = (data.type && data.type == 'section'? this.vars.mess.sectSettingsName : this.vars.mess.fieldSettingsName);

		wnd.ClearButtons();
		wnd.SetButtons([
			{
				'title': this.vars.mess.tabSettingsSave,
				'action': function(){
					action();
					this.parentWindow.Close();
				}
			},
			BX.CDialog.prototype.btnCancel
		]);
		wnd.Show();

		var form = document['field_settings_'+this.name];
		form.field_name.value = data.name;
		form.field_name.focus();
	};

	this.FieldEdit = function()
	{
		var form = document['form_settings_'+this.name];
		var tabIndex = form.tabs.selectedIndex;
		var fieldIndex = form.fields.selectedIndex;

		if(tabIndex < 0 || fieldIndex < 0)
			return;

		this.ShowFieldSettings(this.aTabsEdit[tabIndex].fields[fieldIndex],
			function()
			{
				var frm = document['field_settings_'+_this.name];
				_this.aTabsEdit[tabIndex].fields[fieldIndex].name = frm.field_name.value;

				form.fields[fieldIndex].text = frm.field_name.value;
			}
		);
	};

	this.FieldAdd = function()
	{
		var form = document['form_settings_'+this.name];
		var tabIndex = form.tabs.selectedIndex;

		if(tabIndex < 0)
			return;

		this.ShowFieldSettings({'name':'', 'type':'section'},
			function()
			{
				var field_id = 'field_'+Math.round(Math.random()*1000000);
				var frm = document['field_settings_'+_this.name];
				_this.aTabsEdit[tabIndex].fields[_this.aTabsEdit[tabIndex].fields.length] = {
					'id': field_id,
					'name': frm.field_name.value,
					'type': 'section'
				};
				var opt = new Option(frm.field_name.value, field_id, true, true);
				opt.className = 'bx-section';
				form.fields[form.fields.length] = opt;
				_this.ProcessButtons();
			}
		);
	};

	this.FieldsMoveUp = function()
	{
		var form = document['form_settings_'+this.name];
		var tabIndex = form.tabs.selectedIndex;

		var n = form.fields.length;
		for(var i=0; i<n; i++)
		{
			if(form.fields[i].selected && i>0 && form.fields[i-1].selected == false)
			{
				var field1 = BX.clone(this.aTabsEdit[tabIndex].fields[i]);
				var field2 = BX.clone(this.aTabsEdit[tabIndex].fields[i-1]);
				this.aTabsEdit[tabIndex].fields[i] = field2;
				this.aTabsEdit[tabIndex].fields[i-1] = field1;

				var option1 = new Option(form.fields[i].text, form.fields[i].value);
				var option2 = new Option(form.fields[i-1].text, form.fields[i-1].value);
				option1.className = form.fields[i].className;
				option2.className = form.fields[i-1].className;
				form.fields[i] = option2;
				form.fields[i].selected = false;
				form.fields[i-1] = option1;
				form.fields[i-1].selected = true;
			}
		}
	};

	this.FieldsMoveDown = function()
	{
		var form = document['form_settings_'+this.name];
		var tabIndex = form.tabs.selectedIndex;

		var n = form.fields.length;
		for(var i=n-1; i>=0; i--)
		{
			if(form.fields[i].selected && i<n-1 && form.fields[i+1].selected == false)
			{
				var field1 = BX.clone(this.aTabsEdit[tabIndex].fields[i]);
				var field2 = BX.clone(this.aTabsEdit[tabIndex].fields[i+1]);
				this.aTabsEdit[tabIndex].fields[i] = field2;
				this.aTabsEdit[tabIndex].fields[i+1] = field1;

				var option1 = new Option(form.fields[i].text, form.fields[i].value);
				var option2 = new Option(form.fields[i+1].text, form.fields[i+1].value);
				option1.className = form.fields[i].className;
				option2.className = form.fields[i+1].className;
				form.fields[i] = option2;
				form.fields[i].selected = false;
				form.fields[i+1] = option1;
				form.fields[i+1].selected = true;
			}
		}
	};

	this.FieldsAdd = function()
	{
		var form = document['form_settings_'+this.name];
		var tabIndex = form.tabs.selectedIndex;

		if(tabIndex == -1)
			return;

		var fields = this.aTabsEdit[tabIndex].fields;

		var n = form.all_fields.length;
		for(var i=0; i<n; i++)
			if(form.all_fields[i].selected)
				fields[fields.length] = {
					'id': form.all_fields[i].value,
					'name': form.all_fields[i].text,
					'type': this.aAvailableFields[form.all_fields[i].value].type
				};

		jsSelectUtils.addSelectedOptions(form.all_fields, form.fields, false, false);
		jsSelectUtils.deleteSelectedOptions(form.all_fields);

		for(var i=0, n=form.fields.length; i<n; i++)
			if(fields[i].type == 'section')
				form.fields[i].className = 'bx-section';

		this.ProcessButtons();
	};

	this.FieldsDelete = function()
	{
		var form = document['form_settings_'+this.name];
		var tabIndex = form.tabs.selectedIndex;

		if(tabIndex == -1)
			return;

		var n = form.fields.length;
		var delta = 0;
		for(var i=0; i<n; i++)
		{
			if(form.fields[i].selected)
			{
				this.aAvailableFields[form.fields[i].value] = this.aTabsEdit[tabIndex].fields[i-delta];
				this.aTabsEdit[tabIndex].fields = BX.util.deleteFromArray(this.aTabsEdit[tabIndex].fields, i-delta);
				delta++;
			}
		}

		jsSelectUtils.addSelectedOptions(form.fields, form.all_fields, false, true);
		jsSelectUtils.deleteSelectedOptions(form.fields);

		this.HighlightSections(form.all_fields);

		this.ProcessButtons();
	};

	this.ProcessButtons = function()
	{
		var form = document['form_settings_'+this.name];

		form.add_btn.disabled = (form.all_fields.selectedIndex == -1 || form.tabs.selectedIndex == -1);
		form.del_btn.disabled = form.up_btn.disabled = form.down_btn.disabled = form.field_edit_btn.disabled = (form.fields.selectedIndex == -1);
		form.tab_up_btn.disabled = form.tab_down_btn.disabled = form.tab_edit_btn.disabled = form.tab_del_btn.disabled = form.field_add_btn.disabled = (form.tabs.selectedIndex == -1);
	};

	this.HighlightSections = function(el)
	{
		for(var i=0, n=el.length; i<n; i++)
			if(this.aAvailableFields[el[i].value].type == 'section')
				el[i].className = 'bx-section';
	};

	this.SaveSettings = function()
	{
		var data = {
			'FORM_ID': this.name,
			'action': 'savesettings',
			'sessid': this.vars.sessid,
			'tabs': this.aTabsEdit
		};
		BX.ajax.post('/bitrix/components'+_this.vars.component_path+'/settings.php', data, function(){_this.Reload()});
	};

	this.EnableSettings = function(enabled)
	{
		BX.ajax.get('/bitrix/components'+this.vars.component_path+'/settings.php?FORM_ID='+this.name+'&action=enable&enabled='+(enabled? 'Y':'N')+'&sessid='+this.vars.sessid, function(){_this.Reload()});
	};

	this.Reload = function()
	{
		if(this.vars.ajax.AJAX_ID != '')
			BX.ajax.inserToNode(this.vars.current_url+(this.vars.current_url.indexOf('?') == -1? '?':'&')+'bxajaxid='+this.vars.ajax.AJAX_ID, 'comp_'+this.vars.ajax.AJAX_ID);
		//jsAjaxUtil.InsertDataToNode(this.vars.current_url+(this.vars.current_url.indexOf('?') == -1? '?':'&')+'bxajaxid='+this.vars.ajax.AJAX_ID, 'comp_'+this.vars.ajax.AJAX_ID, this.vars.ajax.AJAX_OPTION_SHADOW);
		else
			window.location = window.location.href;
	}
}

BX.CmrSidebarFieldSelector = function()
{
	this._id = '';
	this._fieldId = '';
	this._currentItem = null;
	this._elem = null;
	this._settings = {};
	this._items = {};
	this._popupMenu = null;
};

BX.CmrSidebarFieldSelector.prototype =
{
	initialize: function(id, fieldId, elem, settings)
	{
		this._id = id;
		this._fieldId = fieldId;
		this._elem = elem;
		this._settings = settings;

		this._items = {};
		var opts = this.getSettings('options', null);
		if(opts)
		{
			for(var i = 0; i < opts.length; i++)
			{
				var opt = opts[i];
				if(BX.type.isNotEmptyString(opt['id']))
				{
					var optId = opt['id'];
					this._items[optId] = BX.CmrSidebarFieldSelectorItem.create(optId, this, { "text": BX.type.isNotEmptyString(opt['caption']) ? opt['caption'] : optId });
				}
			}
		}

		BX.bind(this._elem, 'click', BX.proxy(this._onElementClick, this));

		var button = BX(this.getSettings('buttonId', ''));
		if(button)
		{
			BX.bind(button, 'click', BX.proxy(this._onElementClick, this));
		}
	},
	getSettings: function(name, defaultval)
	{
		var s = this._settings;
		return  s[name] ? s[name] : defaultval;
	},
	getFieldId: function()
	{
		return this._fieldId;
	},
	getCurrentItem: function()
	{
		return this._currentItem;
	},
	setCurrentItemId: function(itemId, save)
	{
		var item = null;
		for(var key in this._items)
		{
			if(!this._items.hasOwnProperty(key))
			{
				continue;
			}

			if(this._items[key].getId() === itemId)
			{
				item = this._items[key];
			}
		}

		if(!item)
		{
			return;
		}

		this._currentItem = item;
		if(this._elem)
		{
			this._elem.innerHTML = item.getTitle();
		}

		save = !!save;
		if(save)
		{
			var editor = BX.CrmInstantEditor.getDefault();
			if(editor)
			{
				editor.saveFieldValue(this._fieldId, item.getId());
			}

			BX.CmrSidebarFieldSelector._synchronize(this);
		}

	},
	_onElementClick: function(e)
	{
		var menuItems = [];
		for(var key in this._items)
		{
			if(!this._items.hasOwnProperty(key))
			{
				continue;
			}

			var item = this._items[key].createMenuItem();
			if(item)
			{
				menuItems.push(item);
			}
		}

		BX.PopupMenu.show(
			this._id,
			this._elem,
			menuItems,
			{ "offsetTop": 0, "offsetLeft": 0 }
		);

		this._popupMenu = BX.PopupMenu.currentItem;
	},
	handleItemChange: function(item)
	{
		if(this._popupMenu && this._popupMenu.popupWindow)
		{
			this._popupMenu.popupWindow.close();
		}

		this.setCurrentItemId(item.getId(), true);
	}
};
BX.CmrSidebarFieldSelector.items = {};
BX.CmrSidebarFieldSelector.create = function(id, fieldId, elem, settings)
{
	var self = new BX.CmrSidebarFieldSelector();
	self.initialize(id, fieldId, elem, settings);
	this.items[id] = self;
	return self;
};

BX.CmrSidebarFieldSelector._synchronize = function(item)
{
	//var type = item.getEntityType();
	//var id = item.getEntityId();

	var selectedItem = item.getCurrentItem();
	if(!selectedItem)
	{
		return;
	}

	var fieldId = item.getFieldId();
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

		if(fieldId === curItem.getFieldId())
		{
			curItem.setCurrentItemId(selectedItem.getId(), false);
		}
	}
};

BX.CmrSidebarFieldSelectorItem = function()
{
	this._id = '';
	this._parent = null;
	this._settings = {};
};

BX.CmrSidebarFieldSelectorItem.prototype =
{
	initialize: function(id, parent, settings)
	{
		this._id = id;
		this._parent = parent;
		this._settings = settings;
	},
	getSettings: function(name, defaultval)
	{
		var s = this._settings;
		return  s[name] ? s[name] : defaultval;

	},
	getId: function()
	{
		return this._id;
	},
	getTitle: function()
	{
		return this.getSettings('text', this._id);
	},
	createMenuItem: function()
	{
		return {
			"text":  this.getTitle(),
			"onclick": BX.proxy(this._onMenuItemClick, this)
		};
	},
	_onMenuItemClick: function()
	{
		if(this._parent)
		{
			this._parent.handleItemChange(this);
		}
	}
};

BX.CmrSidebarFieldSelectorItem.create = function(id, parent, settings)
{
	var self = new BX.CmrSidebarFieldSelectorItem();
	self.initialize(id, parent, settings);
	return self;
};

BX.CrmSidebarUserSelector = function()
{
	this._id = '';
	this._settings = {};
	this._button = null;
	this._container = null;
	this.componentName = '';
	this._componentContainer = null;
	this._componentObj = null;
	this._fieldId = '';
	this._ajaxUrl = '';
	this._messages = {};
	this._dlg = null;
	this._dlgDisplayed = false;
	this._options = {};
};

BX.CrmSidebarUserSelector.prototype =
{
	initialize: function(id, button, container, componentName, fieldId, ajaxUrl, messages, options)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : ('crm_sidebar_user_sel_' + Math.random());
		if(!BX.type.isElementNode(button))
		{
			throw 'BX.CrmSidebarUserSelector: button is not defined';
		}

		this._button = button;

		if(!BX.type.isElementNode(container))
		{
			throw 'BX.CrmSidebarUserSelector: container is not defined';
		}

		this._container = container;

		if(!BX.type.isNotEmptyString(componentName))
		{
			throw 'BX.CrmSidebarUserSelector: componentName is not defined';
		}

		this.componentName = componentName;
		this._componentContainer = BX(componentName + '_selector_content');

		var objName = 'O_' + componentName;

		if(window[objName])
		{
			this._componentObj = window[objName];
			this._componentObj.onSelect = BX.delegate(this._handleUserSelect, this);
		}

		this._fieldId = BX.type.isNotEmptyString(fieldId) ? fieldId : '';
		this._ajaxUrl = BX.type.isNotEmptyString(ajaxUrl) ? ajaxUrl : '';
		this._messages = messages ? messages : {};

		BX.bind(this._button, 'click', BX.delegate(this._handleButtonClick, this));

		this._options = options ? options : {};
	},
	openDialog: function()
	{
		this._dlg = new BX.PopupWindow(
			this._id,
			this._button,
			{
				autoHide: true,
				draggable: false,
				closeByEsc: true,
				offsetLeft: 0,
				offsetTop: 0,
				bindOptions: { forceBindPosition: true },
				content : this._componentContainer,
				events:
				{
					onPopupShow: BX.delegate(
						function()
						{
							this._dlgDisplayed = true;
						},
						this
					),
					onPopupClose: BX.delegate(
						function()
						{
							this._dlgDisplayed = false;
							this._dlg.destroy();
						},
						this
					),
					onPopupDestroy: BX.delegate(
						function()
						{
							this._dlg = null;
						},
						this
					)
				}
			}
		);

		this._dlg.show();
	},
	closeDialog: function()
	{
		if(this._dlg)
		{
			this._dlg.close();
		}
	},
	getSetting: function(name, defaultval)
	{
		return this._settings[name] ? this._settings[name] : defaultval;
	},
	layout: function()
	{
		this._container.href = this.getSetting('USER_PROFILE', '#');
		var nameElem = BX.findChild(this._container, { "className": "crm-detail-info-resp-name" }, true, false);
		if(nameElem)
		{
			nameElem.innerHTML = BX.util.htmlspecialchars(this.getSetting('FULL_NAME', ''));
		}

		var postElem = BX.findChild(this._container, { "className": "crm-detail-info-resp-descr" }, true, false);
		if(postElem)
		{
			postElem.innerHTML = BX.util.htmlspecialchars(this.getSetting('WORK_POSITION', ''));
		}

		var photoElem = BX.findChild(this._container, { "className": "crm-detail-info-resp-img" }, true, false);
		if(photoElem)
		{
			BX.cleanNode(photoElem, false);
			photoElem.appendChild(
				BX.create("IMG", { "attrs": { "src": this.getSetting('PERSONAL_PHOTO', '') } })
			);
		}
	},
	_showError: function(errorID)
	{
		if(this._messages[errorID])
		{
			alert(this._messages[errorID]);
		}
	},
	_handleButtonClick: function()
	{
		if(this._dlg && this._dlgDisplayed)
		{
			this.closeDialog();
		}
		else
		{
			this.openDialog();
		}
	},
	_handleUserSelect: function(user)
	{
		this.closeDialog();
		BX.ajax(
			{
				'url': this._ajaxUrl,
				'method': 'POST',
				'dataType': 'json',
				'data':
				{
					'MODE': 'GET_USER_INFO',
					'USER_ID': user.id,
					'USER_PROFILE_URL_TEMPLATE': BX.type.isNotEmptyString(this._options['userProfileUrlTemplate'])
						? this._options['userProfileUrlTemplate'] : ''
				},
				onsuccess: BX.delegate(
					function(data)
					{
						if(data['USER_INFO'])
						{
							this._settings = data['USER_INFO'];
							this.layout();

							if(this._fieldId.length > 0)
							{
								var editor = BX.CrmInstantEditor.getDefault();
								if(editor)
								{
									editor.saveFieldValue(this._fieldId, this._settings['ID']);
								}
							}
						}
					},
					this
				),
				onfailure: function(data)
				{
					this._showError('GET_USER_INFO_GENERAL_ERROR');
				}
			}
		);
	}
};

BX.CrmSidebarUserSelector.create = function(id, button, container, componentName, fieldId, ajaxUrl, messages, options)
{
	var self = new BX.CrmSidebarUserSelector();
	self.initialize(id, button, container, componentName, fieldId, ajaxUrl, messages, options);
	return self;
};

BX.CrmUserSearchField = function()
{
	this._id = '';
	this._search_input = null;
	this._data_input = null;
	this._componentName = '';
	this._componentContainer = null;
	this._componentObj = null;
	this._dlg = null;
	this._dlgDisplayed = false;
	this._currentUser = {};
};

BX.CrmUserSearchField.prototype =
{
	initialize: function(id, search_input, data_input, componentName, user)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : ('crm_user_search_field_' + Math.random());

		if(!BX.type.isElementNode(search_input))
		{
			throw  "BX.CrmUserSearchField: 'search_input' is not defined!";
		}
		this._search_input = search_input;

		if(!BX.type.isElementNode(data_input))
		{
			throw  "BX.CrmUserSearchField: 'data_input' is not defined!";
		}
		this._data_input = data_input;

		if(!BX.type.isNotEmptyString(componentName))
		{
			throw  "BX.CrmUserSearchField: 'componentName' is not defined!";
		}
		this._componentName = componentName;

		this._componentContainer = BX(componentName + '_selector_content');
		var objName = 'O_' + componentName;
		if(window[objName])
		{
			this._componentObj = window[objName];
			this._componentObj.onSelect = BX.delegate(this._handleUserSelect, this);
			this._componentObj.searchInput = search_input;

			BX.bind(search_input, 'keyup', BX.proxy(this._handleSearchKey, this));
			BX.bind(search_input, 'focus', BX.proxy(this._handleSearchFocus, this));
			BX.bind(document, 'click', BX.delegate(this._handleExternalClick, this));
		}

		this._currentUser = user ? user : {};
		this._adjustUser();
	},
	openDialog: function()
	{
		this._dlg = new BX.PopupWindow(
			this._id,
			this._search_input,
			{
				autoHide: false,
				draggable: false,
				//closeByEsc: true,
				offsetLeft: 0,
				offsetTop: 0,
				bindOptions: { forceBindPosition: true },
				content : this._componentContainer,
				events:
				{
					onPopupShow: BX.delegate(
						function()
						{
							this._dlgDisplayed = true;
						},
						this
					),
					onPopupClose: BX.delegate(
						function()
						{
							this._dlgDisplayed = false;
							this._dlg.destroy();
						},
						this
					),
					onPopupDestroy: BX.delegate(
						function()
						{
							this._dlg = null;
						},
						this
					)
				}
			}
		);

		this._dlg.show();
	},
	_adjustUser: function()
	{
		this._search_input.value = this._currentUser['name'] ? this._currentUser.name : '';
		this._data_input.value = this._currentUser['id'] ? this._currentUser.id : 0;
	},
	closeDialog: function()
	{
		if(this._dlg)
		{
			this._dlg.close();
		}
	},
	_handleExternalClick: function(e)
	{
		if(!e)
		{
			e = window.event;
		}

		if(e.target !== this._search_input &&
			!BX.findParent(e.target, { attribute:{ id: this._componentName + '_selector_content' } }))
		{
			this._adjustUser();
			this.closeDialog();
		}
	},
	_handleSearchKey: function(e)
	{
		if(!this._dlg || !this._dlgDisplayed)
		{
			this.openDialog();
		}

		this._componentObj.search();
	},
	_handleSearchFocus: function(e)
	{
		if(!this._dlg || !this._dlgDisplayed)
		{
			this.openDialog();
		}

		this._componentObj._onFocus(e);
	},
	_handleUserSelect: function(user)
	{
		this._currentUser = user;
		this._adjustUser();
		this.closeDialog();
	}
};

BX.CrmUserSearchField.items = {};

BX.CrmUserSearchField.create = function(id, search_input, data_input, componentName, user)
{
	var self = new BX.CrmUserSearchField();
	self.initialize(id, search_input, data_input, componentName, user);
	this.items[id] = self;
	return self;
};

BX.CrmDateLinkField = function()
{
	this._dataElem = null;
	this._viewElem = null;
	this._settings = {};
};

BX.CrmDateLinkField.prototype =
{
	initialize: function(dataElem, viewElem, settings)
	{
		if(!BX.type.isElementNode(dataElem))
		{
			throw "BX.CrmDateLinkField: 'dataElem' is not defined!";
		}
		this._dataElem = dataElem;

		if(!BX.type.isElementNode(viewElem))
		{
			throw "BX.CrmDateLinkField: 'viewElem' is not defined!";
		}
		this._viewElem = viewElem;

		this._settings = settings ? settings : {};
		BX.bind(viewElem, 'click', BX.delegate(this._onViewClick, this));
	},
	getSetting: function (name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	//layout: function(){},
	_onViewClick: function(e)
	{
		BX.calendar({ node: this._viewElem, bTime: this.getSetting('showTime', true), callback: BX.delegate(this._onCalendarSaveValue, this) });
	},
	_onCalendarSaveValue: function(value)
	{
		var s = BX.calendar.ValueToString(value, this.getSetting('showTime', true), true);
		this._viewElem.innerHTML = s;
		this._dataElem.value = s;
	}
};

BX.CrmDateLinkField.create = function(dataElem, viewElem, settings)
{
	var self = new BX.CrmDateLinkField();
	self.initialize(dataElem, viewElem, settings);
	return self;
}

BX.CrmEntityEditor = function()
{
	this._id = '';
	this._settings = {};
	this._readonly = false;
	this._dlg = null;
	this._data = null;
	this._info = null;
	this._container = null;
	this._selector = null;
};

BX.CrmEntityEditor.prototype =
{
	initialize: function(id, settings, data, info)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : 'CRM_ENTITY_EDITOR' + Math.random();
		this._settings = settings ? settings : {};

		if(!data)
		{
			data = this._prepareData();
		}

		if(!data)
		{
			throw "BX.CrmEntityEditor: Could not find data!";
		}

		this._data = data;

		this._info = info ? info : BX.CrmEntityInfo.create();

		var selectorId = this.getSetting('entitySelectorId', '');
		if(obCrm && obCrm[selectorId])
		{
			var selector = this._selector = obCrm[selectorId];
			selector.AddOnSaveListener(BX.delegate(this._onEntitySelect, this));
		    //selector.AddOnBeforeSearchListener();
		}

		var c = this._container = BX(this.getSetting('containerId', ''));
		if(!c)
		{
			throw "BX.CrmEntityEditor: Could not find field container!";
		}

		BX.bind(BX.findChild(c, { className: 'crm-element-item-delete'}, true, false), 'click', BX.delegate(this._onDeleteButtonClick, this));
		BX.bind(BX.findChild(c, { className: 'bx-crm-edit-crm-entity-change'}, true, false), 'click', BX.delegate(this._onChangeButtonClick, this));
		BX.bind(BX.findChild(c, { className: 'bx-crm-edit-crm-entity-add'}, true, false), 'click', BX.delegate(this._onAddButtonClick, this));
	},
	getId: function()
	{
		return this._id;
	},
	getTypeName: function()
	{
		return this.getSetting('typeName', '');
	},
	getSetting: function (name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	getData: function()
	{
		return this._data;
	},
	getMessage: function(name)
	{
		var msgs = BX.CrmEntityEditor.messages;
		return BX.type.isNotEmptyString(msgs[name]) ? msgs[name] : '';
	},
	openDialog: function(anchor)
	{
		if(this._dlg)
		{
			this._dlg.setData(this._data);
			this._dlg.open(anchor);
			return;
		}

		switch(this.getTypeName())
		{
			case 'CONTACT':
				this._dlg = BX.CrmContactEditDialog.create(
					this._id,
					this.getSetting('dialog', {}),
					this._data,
					BX.delegate(this._onSaveDialogData, this));
				break;
			case 'COMPANY':
				this._dlg = BX.CrmCompanyEditDialog.create(
					this._id,
					this.getSetting('dialog', {}),
					this._data,
					BX.delegate(this._onSaveDialogData, this));
				break;
		}

		if(this._dlg)
		{
			this._dlg.open(anchor);
		}
	},
	closeDialog: function()
	{
		if(this._dlg)
		{
			this._dlg.close();
		}
	},
	isReadOnly: function()
	{
		return this._readonly;
	},
	setReadOnly: function(readonly)
	{
		readonly = !!readonly;
		if(this._readonly === readonly)
		{
			return;
		}

		this._readonly = readonly;

		var deleteButton = BX.findChild(this._container, { className: 'crm-element-item-delete'}, true, false);
		if(deleteButton)
		{
			deleteButton.style.display = readonly ? 'none' : '';
		}

		var buttonsWrapper = BX.findChild(this._container, { className: 'bx-crm-entity-buttons-wrapper'}, true, false);
		if(buttonsWrapper)
		{
			buttonsWrapper.style.display = readonly ? 'none' : '';
		}
	},
	_prepareData: function(settings)
	{
		var typeName = this.getTypeName();
		if(typeName === 'CONTACT')
		{
			return BX.CrmContactData.create(settings);
		}
		if(typeName === 'COMPANY')
		{
			return BX.CrmCompanyData.create(settings);
		}
		return null;
	},
	_onDeleteButtonClick: function(e)
	{
		if(this._readonly)
		{
			return;
		}

		var dataInput = BX(this.getSetting('dataInputId', ''));
		if(dataInput)
		{
			dataInput.value = 0;
		}

		BX.cleanNode(BX.findChild(this._container, { className: 'bx-crm-entity-info-wrapper'}, true, false));
	},
	_onChangeButtonClick: function(e)
	{
		if(this._readonly)
		{
			return;
		}

		var selector = this._selector;
		if(selector)
		{
			selector.Open();
		}
	},
	_onAddButtonClick: function(e)
	{
		if(this._readonly)
		{
			return;
		}

		this._data.reset();

		this.openDialog(
			BX.findChild(this._container, { className: 'bx-crm-edit-crm-entity-add'}, true, false)
		);
	},
	_onSaveDialogData: function(dialog)
	{
		this._data = this._dlg.getData();

		var url = this.getSetting('serviceUrl', '');
		var action = this.getSetting('actionName', '');

		if(!(BX.type.isNotEmptyString(url) && BX.type.isNotEmptyString(action)))
		{
			return;
		}

		var self = this;
		BX.ajax(
			{
				'url': url,
				'method': 'POST',
				'dataType': 'json',
				'data':
				{
					'ACTION' : action,
					'DATA': this._data.toJSON()
				},
				onsuccess: function(data)
				{

					if(data['ERROR'])
					{
						self._showDialogError(data['ERROR']);
					}
					else if(!data['DATA'])
					{
						self._showDialogError('BX.CrmEntityEditor: Could not find contact data!');
					}
					else
					{
						self._data = self._prepareData(data['DATA']);
						self._info = BX.CrmEntityInfo.create(data['INFO'] ? data['INFO'] : {});

						var newDataInput = BX(self.getSetting('newDataInputId', ''));
						if(newDataInput)
						{
							newDataInput.value = self._data.getId();
						}

						self.layout();
						self.closeDialog();
					}
				},
				onfailure: function(data)
				{
					self._showDialogError(data['ERROR'] ? data['ERROR'] : self.getMessage('unknownError'));
				}
			}
		);
	},
	layout: function()
	{
		var dataInput = BX(this.getSetting('dataInputId', ''));
		if(dataInput)
		{
			dataInput.value = this._data.getId();
		}

		var view = BX.findChild(this._container, { className: 'bx-crm-entity-info-wrapper'}, true, false);
		if(!view)
		{
			return;
		}

		BX.cleanNode(view);
		view.appendChild(
			BX.create(
				'A',
				{
					attrs:
					{
						className: 'bx-crm-entity-info-link',
						href: this._info.getSetting('url', ''),
						target: '_blank'
					},
					text: this._info.getSetting('title', this._data.getId())
				}
			)
		);

		view.appendChild(
			BX.create(
				'SPAN',
				{
					attrs:
					{
						className: 'crm-element-item-delete'
					},
					events:
					{
						click: BX.delegate(this._onDeleteButtonClick, this)
					}
				}
			)
		);
	},
	_onEntitySelect: function(settings)
	{
		var typeName = this.getTypeName().toLowerCase();
		var item = settings[typeName] && settings[typeName][0] ? settings[typeName][0] : null;
		if(!item)
		{
			return;
		}

		this._data.setId(item['id']);
		this._info = BX.CrmEntityInfo.create(item);
		this.layout();
	},
	_showDialogError: function(msg)
	{
		if(this._dlg)
		{
			this._dlg.showError(msg);
		}
	}
};


if(typeof(BX.CrmEntityEditor.messages) === 'undefined')
{
	BX.CrmEntityEditor.messages = {};
}

BX.CrmEntityEditor.items = {};

BX.CrmEntityEditor.create = function(id, settings, data, info)
{
	var self = new BX.CrmEntityEditor();
	self.initialize(id, settings, data, info);
	this.items[id] = self;
	return self;
};

BX.CrmContactEditDialog = function()
{
	this._id = '';
	this._settings = {};
	this._dlg = null;
	this._dlgCfg = {};
	this._data = null;
	this._onSaveCallback = null;
};

BX.CrmContactEditDialog.prototype =
{
	initialize: function(id, settings, data, onSaveCallback)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : 'CRM_CONTACT_EDIT_DIALOG_' + Math.random();
		this._settings = settings ? settings : {};
		this._data = data ? data : BX.CrmContactData.create();
		this._onSaveCallback = BX.type.isFunction(onSaveCallback) ? onSaveCallback : null;
	},
	getSetting: function (name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	getData: function()
	{
		return this._data;
	},
	setData: function(data)
	{
		this._data = data ? data : BX.CrmContactData.create();
	},
	isOpened: function()
	{
		return this._dlg && this._dlg.isShown();
	},
	open: function(anchor)
	{
		if(this._dlg)
		{
			if(!this._dlg.isShown())
			{
				this._dlg.show();
			}
			return;
		}

		var cfg = this._dlgCfg = {};
		cfg['id'] = this._id;
		this._dlg = new BX.PopupWindow(
			cfg['id'],
			anchor,
			{
				autoHide: false,
				draggable: true,
				offsetLeft: 0,
				offsetTop: 0,
				bindOptions: { forceBindPosition: false },
				closeByEsc: true,
				closeIcon: { top: '10px', right: '15px'},
				titleBar:
				{
					content: BX.CrmPopupWindowHelper.prepareTitle(this.getSetting('title', 'New contact'))
				},
				events:
				{
					//onPopupShow: function(){},
					onPopupClose: BX.delegate(this._onPopupClose, this),
					onPopupDestroy: BX.delegate(this._onPopupDestroy, this)
				},
				content: this._prepareContent(),
				buttons: this._prepareButtons()
			}
		);

		this._dlg.show();
	},
	close: function()
	{
		if(this._dlg)
		{
			this._dlg.close();
		}
	},
	showError: function(msg)
	{
		var errorWrap = BX(this._getElementId('errors'));
		if(errorWrap)
		{
			errorWrap.innerHTML = msg;
			errorWrap.style.display = '';
		}
	},
	_onPopupClose: function()
	{
		this._dlg.destroy();
	},
	_onPopupDestroy: function()
	{
		this._dlg = null;
	},
	_prepareContent: function()
	{
		var wrapper = BX.create(
			'DIV',
			{
				attrs: { className: 'bx-crm-dialog-quick-create-popup' }
			}
		);

		var data = this._data;
		wrapper.appendChild(
			BX.create(
				'DIV',
				{
					attrs:
					{
						className: 'bx-crm-dialog-quick-create-error-wrap',
						style: 'display:none'
					},
					props: { id: this._getElementId('errors') }
				}
			)
		);
		wrapper.appendChild(BX.CrmPopupWindowHelper.prepareTextField({ id: this._getElementId('lastName'), title: this.getSetting('lastNameTitle', 'Last Name'), value: data.getLastName() }));
		wrapper.appendChild(BX.CrmPopupWindowHelper.prepareTextField({ id: this._getElementId('name'), title: this.getSetting('nameTitle', 'Name'), value: data.getName() }));
		wrapper.appendChild(BX.CrmPopupWindowHelper.prepareTextField({ id: this._getElementId('secondName'), title: this.getSetting('secondNameTitle', 'Second Name'), value: data.getSecondName() }));
		if(this.getSetting('enableEmail', true))
		{
			wrapper.appendChild(BX.CrmPopupWindowHelper.prepareTextField({ id: this._getElementId('email'), title: this.getSetting('emailTitle', 'E-mail'), value: data.getEmail() }));
		}
		if(this.getSetting('enablePhone', true))
		{
			wrapper.appendChild(BX.CrmPopupWindowHelper.prepareTextField({ id: this._getElementId('phone'), title: this.getSetting('phoneTitle', 'Phone'), value: data.getPhone() }));
		}
		return wrapper;
	},
	_prepareButtons: function()
	{
		return BX.CrmPopupWindowHelper.prepareButtons(
			[
				{
					type: 'button',
					settings:
					{
						text: this.getSetting('addButtonName', 'Add'),
						className: 'popup-window-button-accept',
						events:
						{
							click : BX.delegate(this._onSaveButtonClick, this)
						}
					}
				},
				{
					type: 'link',
					settings:
					{
						text: this.getSetting('cancelButtonName', 'Cancel'),
						className: 'popup-window-button-link-cancel',
						events:
						{
							click :
								function()
								{
									this.popupWindow.close();
								}
						}
					}
				}
			]
		);
	},
	_getElementId: function(code)
	{
		return this._dlgCfg['id'] + '_' + code;
	},
	_onSaveButtonClick: function()
	{
		this._data.setLastName(BX(this._getElementId('lastName')).value);
		this._data.setName(BX(this._getElementId('name')).value);
		this._data.setSecondName(BX(this._getElementId('secondName')).value);
		if(this.getSetting('enableEmail', true))
		{
			this._data.setEmail(BX(this._getElementId('email')).value);
		}
		if(this.getSetting('enablePhone', true))
		{
			this._data.setPhone(BX(this._getElementId('phone')).value);
		}

		if(this._onSaveCallback)
		{
			this._onSaveCallback(this);
		}
	}
};

BX.CrmContactEditDialog.create = function(id, settings, data, onSaveCallback)
{
	var self = new BX.CrmContactEditDialog();
	self.initialize(id, settings, data, onSaveCallback);
	return self;
};

BX.CrmCompanyEditDialog = function()
{
	this._id = '';
	this._settings = {};
	this._dlg = null;
	this._dlgCfg = {};
	this._data = null;
	this._onSaveCallback = null;
};

BX.CrmCompanyEditDialog.prototype =
{
	initialize: function(id, settings, data, onSaveCallback)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : 'CRM_COMPANY_EDIT_DIALOG_' + Math.random();
		this._settings = settings ? settings : {};
		this._data = data ? data : BX.CrmCompanyData.create();
		this._onSaveCallback = BX.type.isFunction(onSaveCallback) ? onSaveCallback : null;
	},
	getSetting: function (name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	getData: function()
	{
		return this._data;
	},
	setData: function(data)
	{
		this._data = data ? data : BX.CrmCompanyData.create();
	},
	isOpened: function()
	{
		return this._dlg && this._dlg.isShown();
	},
	open: function(anchor)
	{
		if(this._dlg)
		{
			this._dlg.setContent(this._prepareContent());
			if(!this._dlg.isShown())
			{
				this._dlg.show();
			}
			return;
		}

		var cfg = this._dlgCfg = {};
		cfg['id'] = this._id;
		this._dlg = new BX.PopupWindow(
			cfg['id'],
			anchor,
			{
				autoHide: false,
				draggable: true,
				offsetLeft: 0,
				offsetTop: 0,
				bindOptions: { forceBindPosition: false },
				closeByEsc: true,
				closeIcon: { top: '10px', right: '15px'},
				titleBar:
				{
					content: BX.CrmPopupWindowHelper.prepareTitle(this.getSetting('title', 'New contact'))
				},
				events:
				{
					//onPopupShow: function(){},
					onPopupClose: BX.delegate(this._onPopupClose, this),
					onPopupDestroy: BX.delegate(this._onPopupDestroy, this)
				},
				content: this._prepareContent(),
				buttons: this._prepareButtons()
			}
		);

		this._dlg.show();
	},
	close: function()
	{
		if(this._dlg)
		{
			this._dlg.close();
		}
	},
	showError: function(msg)
	{
		var errorWrap = BX(this._getElementId('errors'));
		if(errorWrap)
		{
			errorWrap.innerHTML = msg;
			errorWrap.style.display = '';
		}
	},
	_onPopupClose: function()
	{
		this._dlg.destroy();
	},
	_onPopupDestroy: function()
	{
		this._dlg = null;
	},
	_prepareContent: function()
	{
		var wrapper = BX.create(
			'DIV',
			{
				attrs: { className: 'bx-crm-dialog-quick-create-popup' }
			}
		);

		var data = this._data;
		wrapper.appendChild(
			BX.create(
				'DIV',
				{
					attrs:
					{
						className: 'bx-crm-dialog-quick-create-error-wrap',
						style: 'display:none'
					},
					props: { id: this._getElementId('errors') }
				}
			)
		);
		wrapper.appendChild(BX.CrmPopupWindowHelper.prepareTextField({ id: this._getElementId('title'), title: this.getSetting('titleTitle', 'Title'), value: data.getTitle() }));
		wrapper.appendChild(BX.CrmPopupWindowHelper.prepareSelectField({ id: this._getElementId('companyType'), title: this.getSetting('companyTypeTitle', 'Company Type'), value: data.getCompanyType(), items: this.getSetting('companyTypeItems', null) } ));
		wrapper.appendChild(BX.CrmPopupWindowHelper.prepareSelectField({ id: this._getElementId('industry'), title: this.getSetting('industryTitle', 'Industry'), value: data.getIndustry(), items: this.getSetting('industryItems', null) }));
		wrapper.appendChild(BX.CrmPopupWindowHelper.prepareTextField({ id: this._getElementId('email'), title: this.getSetting('emailTitle', 'E-mail'), value: data.getEmail() }));
		wrapper.appendChild(BX.CrmPopupWindowHelper.prepareTextField({ id: this._getElementId('phone'), title: this.getSetting('phoneTitle', 'Phone'), value: data.getPhone() }));
		wrapper.appendChild(BX.CrmPopupWindowHelper.prepareTextAreaField({ id: this._getElementId('addressLegal'), title: this.getSetting('addressLegalTitle', 'Address Legal'), value: data.getAddressLegal() }));
		return wrapper;
	},
	_prepareButtons: function()
	{
		return BX.CrmPopupWindowHelper.prepareButtons(
			[
				{
					type: 'button',
					settings:
					{
						text: this.getSetting('addButtonName', 'Add'),
						className: 'popup-window-button-accept',
						events:
						{
							click : BX.delegate(this._onSaveButtonClick, this)
						}
					}
				},
				{
					type: 'link',
					settings:
					{
						text: this.getSetting('cancelButtonName', 'Cancel'),
						className: 'popup-window-button-link-cancel',
						events:
						{
							click :
								function()
								{
									this.popupWindow.close();
								}
						}
					}
				}
			]
		);
	},
	_getElementId: function(code)
	{
		return this._dlgCfg['id'] + '_' + code;
	},
	_onSaveButtonClick: function()
	{
		this._data.setTitle(BX(this._getElementId('title')).value);
		this._data.setCompanyType(BX(this._getElementId('companyType')).value);
		this._data.setIndustry(BX(this._getElementId('industry')).value);
		this._data.setEmail(BX(this._getElementId('email')).value);
		this._data.setPhone(BX(this._getElementId('phone')).value);
		this._data.setAddressLegal(BX(this._getElementId('addressLegal')).value);

		if(this._onSaveCallback)
		{
			this._onSaveCallback(this);
		}
	}
};

BX.CrmCompanyEditDialog.create = function(id, settings, data, onSaveCallback)
{
	var self = new BX.CrmCompanyEditDialog();
	self.initialize(id, settings, data, onSaveCallback);
	return self;
};

BX.CrmContactData = function()
{
	this._id = 0;
	this._name = this._secondName = this._lastName = this._email = this._phone = '';
};

BX.CrmContactData.prototype =
{
	initialize: function(settings)
	{
		if(!settings)
		{
			return;
		}

		if(settings['id'])
		{
			this.setId(settings['id']);
		}

		if(settings['name'])
		{
			this.setName(settings['name']);
		}

		if(settings['secondName'])
		{
			this.setSecondName(settings['secondName']);
		}

		if(settings['lastName'])
		{
			this.setLastName(settings['lastName']);
		}

		if(settings['email'])
		{
			this.setEmail(settings['email']);
		}

		if(settings['phone'])
		{
			this.setPhone(settings['phone']);
		}
	},
	reset: function()
	{
		this._id = 0;
		this._name = this._secondName = this._lastName = this._email = this._phone = '';
	},
	getId: function()
	{
		return this._id;
	},
	setId: function(val)
	{
		this._id = val;
	},
	getName: function()
	{
		return this._name;
	},
	setName: function(val)
	{
		this._name = BX.type.isNotEmptyString(val) ? val : '';
	},
	getSecondName: function()
	{
		return this._secondName;
	},
	setSecondName: function(val)
	{
		this._secondName = BX.type.isNotEmptyString(val) ? val : '';
	},
	getLastName: function()
	{
		return this._lastName;
	},
	setLastName: function(val)
	{
		this._lastName = BX.type.isNotEmptyString(val) ? val : '';
	},
	getEmail: function()
	{
		return this._email;
	},
	setEmail: function(val)
	{
		this._email = BX.type.isNotEmptyString(val) ? val : '';
	},
	getPhone: function()
	{
		return this._phone;
	},
	setPhone: function(val)
	{
		this._phone = BX.type.isNotEmptyString(val) ? val : '';
	},
	toJSON: function()
	{
		return(
			{
				id: this._id,
				name: this._name,
				secondName: this._secondName,
				lastName: this._lastName,
				email: this._email,
				phone: this._phone
			}
		);
	}
};

BX.CrmContactData.create = function(settings)
{
	var self = new BX.CrmContactData();
	self.initialize(settings);
	return self;
};

BX.CrmCompanyData = function()
{
	this._id = 0;
	this._title = this._companyType = this._industry = this._email = this._phone = this._addressLegal = '';
};

BX.CrmCompanyData.prototype =
{
	initialize: function(settings)
	{
		if(!settings)
		{
			return;
		}

		if(settings['id'])
		{
			this.setId(settings['id']);
		}

		if(settings['title'])
		{
			this.setTitle(settings['title']);
		}

		if(settings['companyType'])
		{
			this.setCompanyType(settings['companyType']);
		}

		if(settings['industry'])
		{
			this.setIndustry(settings['industry']);
		}

		if(settings['email'])
		{
			this.setEmail(settings['email']);
		}

		if(settings['phone'])
		{
			this.setPhone(settings['phone']);
		}

		if(settings['addressLegal'])
		{
			this.setAddressLegal(settings['addressLegal']);
		}
	},
	reset: function()
	{
		this._id = 0;
		this._title = this._companyType = this._industry = this._email = this._phone = this._addressLegal = '';
	},
	getId: function()
	{
		return this._id;
	},
	setId: function(val)
	{
		this._id = val;
	},
	getTitle: function()
	{
		return this._title;
	},
	setTitle: function(val)
	{
		this._title = BX.type.isNotEmptyString(val) ? val : '';
	},
	getCompanyType: function()
	{
		return this._companyType;
	},
	setCompanyType: function(val)
	{
		this._companyType = BX.type.isNotEmptyString(val) ? val : '';
	},
	getIndustry: function()
	{
		return this._industry;
	},
	setIndustry: function(val)
	{
		this._industry = BX.type.isNotEmptyString(val) ? val : '';
	},
	getEmail: function()
	{
		return this._email;
	},
	setEmail: function(val)
	{
		this._email = BX.type.isNotEmptyString(val) ? val : '';
	},
	getPhone: function()
	{
		return this._phone;
	},
	setPhone: function(val)
	{
		this._phone = BX.type.isNotEmptyString(val) ? val : '';
	},
	getAddressLegal: function()
	{
		return this._addressLegal;
	},
	setAddressLegal: function(val)
	{
		this._addressLegal = BX.type.isNotEmptyString(val) ? val : '';
	},
	toJSON: function()
	{
		return(
			{
				id: this.id,
				title: this._title,
				companyType: this._companyType,
				industry: this._industry,
				email: this._email,
				phone: this._phone,
				addressLegal: this._addressLegal
			}
		);
	}
};

BX.CrmCompanyData.create = function(settings)
{
	var self = new BX.CrmCompanyData();
	self.initialize(settings);
	return self;
};

BX.CrmEntityInfo = function()
{
	this._settings = {};
};

BX.CrmEntityInfo.prototype =
{
	initialize: function(settings)
	{
		this._settings = settings ? settings : {};
	},
	getSetting: function(name, defaultval)
	{
		return this._settings[name] ? this._settings[name] : defaultval;
	}
};

BX.CrmEntityInfo.create = function(settings)
{
	var self = new BX.CrmEntityInfo();
	self.initialize(settings);
	return self;
}

BX.CrmPopupWindowHelper = {};
BX.CrmPopupWindowHelper.prepareButtons = function(data)
{
	var result = [];
	for(var i = 0; i < data.length; i++)
	{
		var datum = data[i];
		result.push(
			datum['type'] === 'link'
				? new BX.PopupWindowButtonLink(datum['settings'])
				: new BX.PopupWindowButton(datum['settings']));
	}

	return result;
};

BX.CrmPopupWindowHelper.prepareTextField = function(settings)
{
	return BX.create(
		'DIV',
		{
			attrs: { className: 'bx-crm-dialog-quick-create-field' },
			children:
				[
					BX.create(
						'SPAN',
						{
							attrs: { className: 'bx-crm-dialog-quick-create-field-title' },
							text: settings['title'] + ':'
						}
					),
					BX.create(
						'INPUT',
						{
							attrs: { className: 'bx-crm-dialog-quick-create-field-text-input' },
							props: { id: settings['id'], value: settings['value'] }
						}
					)
				]
		}
	);
};

BX.CrmPopupWindowHelper.prepareSelectField = function(settings)
{
	var select = BX.create(
		'SELECT',
		{
		 	attrs: { className: 'bx-crm-dialog-quick-create-field-select' },
			props: { id: settings['id'] }
		}
	);

	var value = settings['value'] ? settings['value'] : '';

	if(settings['items'])
	{
		for(var i = 0; i < settings['items'].length; i++)
		{
			var item = settings['items'][i];
			var v = item['value'] ? item['value'] : i.toString();

			var option = BX.create(
				'OPTION',
				{
					text: item['text'] ? item['text'] : v,
					props: { value : v }
				}
			);

			if(!BX.browser.isIE)
			{
				select.add(option, null);
			}
			else
			{
				try
				{
					// for IE earlier than version 8
					select.add(option, select.options[null]);
				}
				catch (e)
				{
					select.add(option, null);
				}
			}

			if(v === value)
			{
				option.selected = true;
			}
		}
	}

	return BX.create(
		'DIV',
		{
			attrs: { className: 'bx-crm-dialog-quick-create-field' },
			children:
				[
					BX.create(
						'SPAN',
						{
							attrs: { className: 'bx-crm-dialog-quick-create-field-title' },
							text: settings['title'] + ':'
						}
					),
					select
				]
		}
	);
};

BX.CrmPopupWindowHelper.prepareTextAreaField = function(settings)
{
	return BX.create(
		'DIV',
		{
			attrs: { className: 'bx-crm-dialog-quick-create-field' },
			children:
				[
					BX.create(
						'SPAN',
						{
							attrs: { className: 'bx-crm-dialog-quick-create-field-title' },
							text: settings['title'] + ':'
						}
					),
					BX.create(
						'TEXTAREA',
						{
							attrs: { className: 'bx-crm-dialog-quick-create-field-text-input' },
							props: { id: settings['id'] },
							text: settings['value']
						}
					)
				]
		}
	);
};

BX.CrmPopupWindowHelper.prepareTitle = function(text)
{
	return BX.create(
		'DIV',
		{
			attrs: { className: 'bx-crm-dialog-tittle-wrap' },
			children:
				[
					BX.create(
						'SPAN',
						{
							text: text,
							props: { className: 'bx-crm-dialog-title-text' }
						}
					)
				]
		}
	);
};

BX.CrmEntityDetailViewDialog = function()
{
	this._id = '';
	this._dlg = null;
	this._settings = {};
};

BX.CrmEntityDetailViewDialog.prototype =
{
	initialize: function(id, settings)
	{
		this._id = BX.type.isNotEmptyString(id) ? id : 'CRM_ENTITY_DETAIL_VIEW_DIALOG_' + Math.random();
		this._settings = settings ? settings : {};
	},
	getId: function()
	{
		return this._id;
	},
	getSetting: function (name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	isOpened: function()
	{
		return this._dlg && this._dlg.isShown();
	},
	open: function()
	{
		if(this._dlg)
		{
			if(!this._dlg.isShown())
			{
				this._dlg.show();
			}
			return;
		}

		var container = BX(this.getSetting('containerId'));
		if(!container)
		{
			container = BX.findChild(BX('sidebar'), { 'class': 'crm-entity-info-details-container' }, true, false);
		}

		this._dlg = new BX.PopupWindow(
			this._id,
			null,
			{
				autoHide: false,
				draggable: true,
				offsetLeft: 0,
				offsetTop: 0,
				bindOptions: { forceBindPosition: false },
				closeByEsc: true,
				closeIcon: { top: '10px', right: '15px'},
				titleBar:
				{
					content: BX.CrmPopupWindowHelper.prepareTitle(this.getSetting('title', 'Details'))
				},
				events:
				{
					onAfterPopupShow:  BX.delegate(this._onAfterPopupShow, this),
					onPopupDestroy: BX.delegate(this._onPopupDestroy, this)
				},
				content: container
			}
		);

		this._dlg.show();
	},
	close: function()
	{
		if(this._dlg && this._dlg.isShown())
		{
			this._dlg.close();
		}
	},
	toggle: function()
	{
		this.isOpened() ? this.close() : this.open();
	},
	_onAfterPopupShow: function()
	{
		var sidebarContainer = BX.findChild(BX('sidebar'), { 'class': 'sidebar-block' }, true, false);
		if(!sidebarContainer)
		{
			return;
		}
		var sidebarPos = BX.pos(sidebarContainer);

		var dialogContainer = this._dlg.popupContainer;
		if(!dialogContainer)
		{
			return;
		}
		var dialogPos = BX.pos(dialogContainer);

		dialogContainer.style.top = sidebarPos.top.toString() + 'px';
		dialogContainer.style.left = (sidebarPos.left - dialogPos.width - 1).toString() + 'px';
	},
	_onPopupDestroy: function()
	{
		this._dlg = null;
	}
};

BX.CrmEntityDetailViewDialog.items = {};
BX.CrmEntityDetailViewDialog.create = function(id, settings)
{
	var self = new BX.CrmEntityDetailViewDialog();
	self.initialize(id, settings);
	this.items[self.getId()] = self;
	return self;
}

BX.CrmEntityDetailViewDialog.ensureCreated = function(id, settings)
{
	return typeof(this.items[id]) !== 'undefined' ? this.items[id] : this.create(id, settings);
}

BX.CrmContactEditor = function()
{
	this._id = '';
	this._settings = {};
	this._dlg = null;
	this._clientField = null;
};

BX.CrmContactEditor.prototype =
{
	initialize: function(id, settings)
	{
		this._id = id;
		this._settings = settings ? settings : {};
		this._data = BX.CrmContactData.create(this.getSetting('data', {}));
	},
	getSetting: function (name, defaultval)
	{
		return typeof(this._settings[name]) != 'undefined' ? this._settings[name] : defaultval;
	},
	openDialog: function(anchor)
	{
		if(this._dlg)
		{
			this._dlg.setData(this._data);
			this._dlg.open(anchor);
			return;
		}

		this._dlg = BX.CrmContactEditDialog.create(
			this._id,
			this.getSetting('dialog', {}),
			this._data,
			BX.delegate(this._onSaveDialogData, this));

		if(this._dlg)
		{
			this._dlg.open(anchor);
		}
	},
	closeDialog: function()
	{
		if(this._dlg)
		{
			this._dlg.close();
		}
	},
	openExternalFieldEditor: function(field)
	{
		this._clientField = field;
		this.openDialog();
	},
	_onSaveDialogData: function(dialog)
	{
		this._data = this._dlg.getData();

		var url = this.getSetting('serviceUrl', '');
		var action = this.getSetting('actionName', '');

		if(!(BX.type.isNotEmptyString(url) && BX.type.isNotEmptyString(action)))
		{
			return;
		}

		var self = this;
		BX.ajax(
			{
				'url': url,
				'method': 'POST',
				'dataType': 'json',
				'data':
				{
					'ACTION' : action,
					'DATA': this._data.toJSON(),
					'NAME_TEMPLATE': this.getSetting('nameTemplate', '')
				},
				onsuccess: function(data)
				{

					if(data['ERROR'])
					{
						self._showDialogError(data['ERROR']);
					}
					else if(!data['DATA'])
					{
						self._showDialogError('BX.CrmContactEditor: Could not find contact data!');
					}
					else
					{
						self._data = BX.CrmContactData.create(data['DATA']);
						var info = data['INFO'] ? data['INFO'] : {};
						self._clientField.setFieldValue(
							BX.type.isNotEmptyString(info['title'])
								? BX.util.htmlspecialchars(info['title']) : ''
						);
						self.closeDialog();
					}
				},
				onfailure: function(data)
				{
					self._showDialogError(data['ERROR'] ? data['ERROR'] : self.getMessage('unknownError'));
				}
			}
		);
	},
	_showDialogError: function(msg)
	{
		if(this._dlg)
		{
			this._dlg.showError(msg);
		}
	}
};

BX.CrmContactEditor.create = function(id, settings)
{
	var self = new BX.CrmContactEditor();
	self.initialize(id, settings);
	return self;
};
