;(function(){
	BX.namespace('BX.rest');

	if(!!BX.rest.AppLayout)
	{
		return;
	}

	BX.rest.AppLayout = function(params)
	{
		this.params = {
			firstRun: !!params.firstRun,
			appHost: params.appHost,
			appProto: params.appProto,
			authId: params.authId,
			authExpires: params.authExpires,
			refreshId: params.refreshId,
			placement: params.placement,
			formName: params.formName,
			frameName: params.frameName,
			loaderName: params.loaderName,
			layoutName: params.layoutName,
			ajaxUrl: params.ajaxUrl,
			controlUrl: params.controlUrl,
			isAdmin: !!params.isAdmin,
			staticHtml: !!params.staticHtml,
			id: params.id,
			appId: params.appId,
			appV: params.appV,
			appI: params.appI,
			appSid: params.appSid,
			memberId: params.memberId,
			restPath: params.restPath,
			proto: params.proto,
			userOptions: params.userOptions,
			appOptions: params.appOptions,
			placementOptions: params.placementOptions
		};

		this.userSelectorControl = [null, null];
		this.userSelectorControlCallback = null;
		this.bAccessLoaded = false;
		this._appOptionsStack = [];

		this.expandPopup = null;
		this.expandPopupContent = null;

		this._inited = false;
		this._destroyed = false;

		this.deniedInterface = [];

		this.selectUserCallback_1_value = [];

		this.messageInterface = new (BX.rest.AppLayout.initializePlacement(this.params.placement))();

		BX.bind(window, 'message', BX.proxy(this.receiveMessage, this));
	};

	BX.rest.AppLayout.prototype = {
		init: function()
		{
			if(!this._inited)
			{
				var loader = BX(this.params.loaderName);
				BX.bind(BX(this.params.frameName), 'load', function()
				{
					BX.addClass(loader, 'app-loading-msg-loaded');
					BX.removeClass(this, 'app-loading');

					setTimeout(function()
					{
						BX.remove(loader);
					}, 300);
				});

				if(this.params.staticHtml)
				{
					BX(this.params.frameName).src = document.forms[this.params.formName].action;
				}
				else
				{
					document.forms[this.params.formName].submit();
				}

				this._inited = true;
			}
		},

		destroy: function()
		{
			BX.unbind(window, 'message', BX.proxy(this.receiveMessage, this));
			BX(this.params.frameName).parentNode.removeChild(BX(this.params.frameName));
			this._destroyed = true;
		},

		receiveMessage: function(e)
		{
			e = e || window.event;

			if(e.origin != this.params.appProto + '://' + this.params.appHost)
			{
				return;
			}

			var cmd = split(e.data, ':'), args = [];

			if(cmd[3] != this.params.appSid)
			{
				return;
			}

			if(cmd[1])
			{
				args = JSON.parse(cmd[1]);
			}

			if(!!this.messageInterface[cmd[0]] && !BX.util.in_array(cmd[0], this.deniedInterface))
			{
				var cb = cmd[2];
				var _cb = !cb ? BX.DoNothing : BX.delegate(function(res)
				{
					var f = BX(this.params.frameName);
					if(!!f && !!f.contentWindow)
					{
						f.contentWindow.postMessage(
							cb + ':' + (typeof res == 'undefined' ? '' : JSON.stringify(res)),
							this.params.appProto + '://' + this.params.appHost
						);
					}
				}, this);

				this.messageInterface[cmd[0]].apply(this, [args, _cb]);
			}
		},

		denyInterface: function(deniedList)
		{
			this.deniedInterface = BX.util.array_merge(this.deniedInterface, deniedList);
		},

		sendAppOptions: function()
		{
			if(this._appOptionsStack.length > 0)
			{
				var stack = this._appOptionsStack;
				this._appOptionsStack = [];

				var opts = [];
				for(var i = 0; i < stack.length; i++)
				{
					opts.push({name: stack[i][0], value: stack[i][1]});
				}

				var params = {
					action: 'set_option',
					id: this.params.id,
					sessid: BX.bitrix_sessid(),
					options: opts
				};

				BX.ajax.loadJSON(this.params.ajaxUrl, params, function(data)
				{
					for(var i = 0; i < stack.length; i++)
					{
						stack[i][2](data);
					}
				});
			}
		},

		loadControl: function(name, params, cb)
		{
			if(!params)
			{
				params = {};
			}

			params.control = name;
			params.sessid = BX.bitrix_sessid();

			BX.ajax({
				method: 'POST',
				url: this.params.controlUrl,
				data: params,
				processScriptsConsecutive: true,
				onsuccess: cb
			});
		},

		reInstall: function()
		{
			BX.proxy(this.messageInterface.setInstallFinish, this)({value: false});
		},

		selectUserCallback_0: function(v)
		{
			var value = BX.util.array_values(v);
			if(!!value && value.length > 0)
			{
				BX.defer(this.userSelectorControl[0].close, this.userSelectorControl[0])();

				if(!!this.userSelectorControlCallback)
				{
					this.userSelectorControlCallback.apply(this, [value[0]]);
				}
			}
		},

		selectUserCallback_1: function(v)
		{
			if(v === true)
			{
				var value = BX.util.array_values(this.selectUserCallback_1_value);

				BX.defer(this.userSelectorControl[1].close, this.userSelectorControl[1])();

				if(!!this.userSelectorControlCallback)
				{
					this.userSelectorControlCallback.apply(this, [value]);
				}
			}
			else
			{
				this.selectUserCallback_1_value = v;
			}
		},

		hideUpdate: function(version, cb)
		{
			BX.userOptions.save('app_options', 'params_' + this.params.appId + '_' + this.params.appV, 'skip_update_' + version, 1);
			cb();
		},

		adjustPopup: function()
		{
			if(!!this.expandPopup && this.expandPopup.isShown())
			{
				var node = this.expandPopupContent;
				var wnd = BX.GetWindowInnerSize();
				node.style.height = (wnd.innerHeight - 180) + 'px';
				node.style.width = (wnd.innerWidth - 140) + 'px';

				this.expandPopup.adjustPosition();
			}
			else
			{
				BX.unbind(window, 'resize', BX.proxy(this.adjustPopup, this));
			}
		}

	};


	BX.rest.AppLayout.initizalizePlacementInterface = function(parent)
	{
		var f = function(){};
		BX.extend(f, parent);

		f.prototype.events = BX.clone(f.superclass.events);

		return f;
	};

	BX.rest.AppLayout.initializePlacement = function(placement)
	{
		placement = (placement + '').toUpperCase();

		if(!BX.rest.AppLayout.placementInterface[placement])
		{
			BX.rest.AppLayout.placementInterface[placement] = BX.rest.AppLayout.initizalizePlacementInterface(
				placement === 'DEFAULT'
					? BX.rest.AppLayout.MessageInterface
					: BX.rest.AppLayout.MessageInterfacePlacement
			);
		}

		return BX.rest.AppLayout.placementInterface[placement];
	};

	BX.rest.AppLayout.initializePlacementByEvent = function(placement, event)
	{
		BX.addCustomEvent(event, function(PlacementInterface){
			var MessageInterface = BX.rest.AppLayout.initializePlacement(placement);
			if(!!PlacementInterface.events)
			{
				for(var i = 0; i < PlacementInterface.events.length; i++)
				{
					MessageInterface.prototype.events.push(PlacementInterface.events[i]);
				}
			}

			for(var method in PlacementInterface)
			{
				if(method !== 'events' && PlacementInterface.hasOwnProperty(method))
				{
					MessageInterface.prototype[method] = PlacementInterface[method];
				}
			}
		});
	};

	BX.rest.AppLayout.MessageInterface = function(){};
	BX.rest.AppLayout.MessageInterface.prototype = {

		events: [],

		getInitData: function(params, cb)
		{
			cb({
				LANG: BX.message('LANGUAGE_ID'),
				DOMAIN: location.host,
				PROTOCOL: this.params.proto,
				PATH: this.params.restPath,
				AUTH_ID: this.params.authId,
				AUTH_EXPIRES: this.params.authExpires,
				REFRESH_ID: this.params.refreshId,
				MEMBER_ID: this.params.memberId,
				FIRST_RUN: this.params.firstRun,
				IS_ADMIN: this.params.isAdmin,
				INSTALL: this.params.appI,
				USER_OPTIONS: this.params.userOptions,
				APP_OPTIONS: this.params.appOptions,
				PLACEMENT: this.params.placement,
				PLACEMENT_OPTIONS: this.params.placementOptions
			});
			this.params.firstRun = false;
		},

		getInterface: function(params, cb)
		{
			var result = {command: [], event: []};

			for(var cmd in this.messageInterface)
			{
				// no hasOwnProperty check here!
				if(
					cmd !== 'events'
					&& cmd !== 'constructor'
					&& !BX.rest.AppLayout.MessageInterfacePlacement.prototype[cmd]
					&& !BX.util.in_array(cmd, this.deniedInterface)
				)
				{
					result.command.push(cmd);
				}
			}

			for(var i = 0; i < this.messageInterface.events.length; i++)
			{
				result.event.push(this.messageInterface.events[i]);
			}

			cb(result);
		},

		refreshAuth: function(params, cb)
		{
			params = {action: 'access_refresh', id: this.params.id, sessid: BX.bitrix_sessid()};
			BX.ajax.loadJSON(this.params.ajaxUrl, params, BX.delegate(function(data)
			{
				if(!!data['access_token'])
				{
					this.params.authId = data['access_token'];
					this.params.authExpires = data['expires_in'];
					this.params.refreshId = data['refresh_token'];
					cb({
						AUTH_ID: this.params.authId,
						AUTH_EXPIRES: this.params.authExpires,
						REFRESH_ID: this.params.refreshId
					});
				}
				else
				{
					alert('Unable to get new token! Reload page, please!');
				}
			}, this));
		},

		resizeWindow: function(params, cb)
		{
			var f = BX(this.params.layoutName);
			params.width = params.width == '100%' ? params.width : ((parseInt(params.width) || 100) + 'px');
			params.height = parseInt(params.height);

			if(!!params.width)
			{
				f.style.width = params.width;
			}
			if(!!params.height)
			{
				f.style.height = params.height + 'px';
			}

			var p = BX.pos(f);
			cb({width: p.width, height: p.height});
		},

		setTitle: function(params, cb)
		{
			if(!!this.expandPopup && this.expandPopup.isShown())
			{
				this.expandPopup.setTitleBar(params.title);
			}
			else
			{
				BX.ajax.UpdatePageTitle(params.title);
			}

			cb(params);
		},

		setScroll: function(params, cb)
		{
			if(!!params && typeof params.scroll != 'undefined' && params.scroll >= 0)
			{
				window.scrollTo(BX.GetWindowScrollPos().scrollLeft, parseInt(params.scroll));
			}
			cb(params);
		},

		setUserOption: function(params, cb)
		{
			this.params.userOptions[params.name] = params.value;
			BX.userOptions.save('app_options', 'options_' + this.params.appId, params.name, params.value);
			cb(params);
		},

		setAppOption: function(params, cb)
		{
			if(this.params.isAdmin)
			{
				this._appOptionsStack.push([params.name, params.value, cb]);
				BX.defer(this.sendAppOptions, this)();
			}
		},

		setInstall: function(params, cb)
		{
			BX.userOptions.save('app_options', 'params_' + this.params.appId + '_' + this.params.appV, 'install', !!params['install'] ? 1 : 0);
			cb(params);
		},

		setInstallFinish: function(params, cb)
		{
			var p = {
				action: 'set_installed',
				id: this.params.id,
				v: typeof params.value == 'undefined' || params.value !== false ? 'Y' : 'N',
				sessid: BX.bitrix_sessid()
			};
			BX.ajax.loadJSON(this.params.ajaxUrl, p, function(data)
			{
				var h = window.location.href.replace(/(\?|&)install_finished=[^&]*/ig, '$1');
				window.location = (h + (h.indexOf('?') == -1 ? '?' : '&') + 'install_finished=' + (!!data.result ? 'Y' : 'N')).replace('&&', '&').replace('?&', '?');
			});
		},

		selectUser: function(params, cb)
		{
			this.userSelectorControlCallback = cb;

			var mult = parseInt(params.mult + 0);

			if(mult)
			{
				// fully reinitialize multiple selector
				if(this.userSelectorControl[mult])
				{
					this.userSelectorControl[mult].close();
					this.userSelectorControl[mult].destroy();
					this.userSelectorControl[mult] = null;
				}
			}
			else if(!!this.userSelectorControl[mult])
			{
				// reuse single selector if already loaded
				this.userSelectorControl[mult].show();
				return;
			}

			var p = {
				name: 'USER_' + mult,
				onchange: "user_selector_cb_" + (parseInt(Math.random() * 100000)),
				site_id: BX.message('SITE_ID')
			};

			if(mult)
			{
				p.mult = true;
			}

			window[p.onchange] = BX.delegate(this['selectUserCallback_' + mult], this);

			this.loadControl('user_selector', p, BX.delegate(function(result)
			{
				this.userSelectorControl[mult] = BX.PopupWindowManager.create(
					"app-user-popup-" + mult,
					null,
					{
						autoHide: true,
						content: result
					}
				);
				if(mult)
				{
					this.userSelectorControl[mult].setButtons([
						new BX.PopupWindowButton({
							text: BX.message('REST_ALT_USER_SELECT'),
							className: "popup-window-button-accept",
							events: {
								click: function() {
									window[p.onchange](true);
								}
							}
						})
					]);
				}

				this.userSelectorControl[parseInt(params.mult + 0)].show();
				BX('USER_' + mult + '_selector_content').style.display = 'block';

			}, this));

		},

		selectAccess: function(params, cb)
		{
			if(!this.bAccessLoaded)
			{
				this.loadControl('access_selector', {}, BX.defer(function()
				{
					this.bAccessLoaded = true;
					BX.defer(this.messageInterface.selectAccess, this)(params, cb);
				}, this));
			}
			else
			{
				BX.Access.Init({
					groups: {disabled: true}
				});

				params.value = params.value || [];
				var startValue = {};
				for(var i = 0; i < params.value.length; i++)
				{
					startValue[params.value[i]] = true;
				}

				BX.Access.SetSelected(startValue);
				BX.Access.ShowForm({
					callback: function(arRights)
					{
						var res = [];

						for(var provider in arRights)
						{
							if(arRights.hasOwnProperty(provider))
							{
								for(var id in arRights[provider])
								{
									if(arRights[provider].hasOwnProperty(id))
									{
										res.push(arRights[provider][id]);
									}
								}
							}
						}

						cb(res);
					}
				});
			}
		},

		selectCRM: function(params, cb, loaded)
		{
			if(!loaded)
			{
				this.loadControl(
					'crm_selector',
					{
						entityType: params.entityType,
						multiple: !!params.multiple ? 'Y' : 'N',
						value: params.value
					},
					BX.delegate(function()
					{
						BX.defer(this.messageInterface.selectCRM, this)(params, cb, true);
					}, this)
				);

				return;
			}

			if(!window.obCrm)
			{
				setTimeout(BX.delegate(function()
				{
					BX.proxy(this.messageInterface.selectCRM, this)(params, cb, true);
				}, this), 500);
			}
			else
			{
				obCrm['restCrmSelector'].Open();
				obCrm['restCrmSelector'].AddOnSaveListener(function(result)
				{
					cb(result);
					obCrm['restCrmSelector'].Clear();
				});
			}
		},

		reloadWindow: function()
		{
			window.location.reload();
		},

		imCallTo: function(params)
		{
			BXIM.callTo(params.userId, !!params.video)
		},

		imPhoneTo: function(params)
		{
			BXIM.phoneTo(params.phone)
		},

		imOpenMessenger: function(params)
		{
			BXIM.openMessenger(params.dialogId)
		},

		imOpenHistory: function(params)
		{
			BXIM.openHistory(params.dialogId)
		},


		openApplication: function(params, cb)
		{
			if(this.expandPopup && this.expandPopup.isShown())
			{
				return;
			}

			if(!!this.expandPopup)
			{
				this.expandPopup.destroy();
				this.expandPopup = null;
			}

			this.expandPopup = new BX.PopupWindow(
				'rest_app_' + this.params.appSid,
				null,
				{
					closeByEsc: false,
					closeIcon: true,
					titleBar: BX.message('JS_CORE_LOADING'),
					events: {
						onPopupClose: function()
						{
							BX.rest.AppLayout.get('DEFAULT').destroy();
							cb();
						}
					},
					overlay: {opacity: 50}
				}
			);

			this.expandPopupContent = BX.create('DIV', {
				props: {className: 'app-expand-popup'},
				html: '<div class="app-loading-popup"></div>'
			});
			this.expandPopup.setContent(this.expandPopupContent);
			this.expandPopup.show();

			BX.ajax.post(
				BX.message('REST_APPLICATION_URL').replace('#id#', parseInt(this.params.id)),
				{
					sessid: BX.bitrix_sessid(),
					popup: 1,
					param: params,
					parentsid: this.params.appSid
				},
				BX.delegate(function(result)
				{
					this.expandPopupContent.innerHTML = result;

					BX.bind(window, 'resize', BX.proxy(this.adjustPopup, this));
					this.adjustPopup();

				}, this)
			);
		},

		closeApplication: function(params, cb)
		{
			if(!!this.expandPopup)
			{
				this.expandPopup.close();
			}
		}
	};

	BX.rest.AppLayout.MessageInterfacePlacement = BX.rest.AppLayout.initizalizePlacementInterface(BX.rest.AppLayout.MessageInterface);

	BX.rest.AppLayout.MessageInterfacePlacement.prototype.placementBindEvent = function(param, cb)
	{
		if(!!param.event && BX.util.in_array(param.event, this.messageInterface.events))
		{
			var f = BX.delegate(function()
			{
				if(!this._destroyed)
				{
					cb.apply(this, arguments);
				}
				else
				{
					BX.removeCustomEvent(param.event, f);
				}
			}, this);

			BX.addCustomEvent(param.event, f);
		}
	};

	BX.rest.layoutList = {};
	BX.rest.placementList = {};
	BX.rest.AppLayout.placementInterface = {};

	BX.rest.AppLayout.get = function(id)
	{
		return BX.rest.layoutList[id];
	};

	BX.rest.AppLayout.set = function(placement, sid, params)
	{
		placement = (placement + '').toUpperCase();

		params.appSid = sid;
		params.placement = placement;

		BX.rest.layoutList[sid] = new BX.rest.AppLayout(params);

		return BX.rest.layoutList[sid];
	};

	BX.rest.AppLayout.getPlacement = function(placement)
	{
		return BX.rest.placementList[(placement + '').toUpperCase()];
	};

	BX.rest.AppLayout.setPlacement = function(placement, ob)
	{
		BX.rest.placementList[(placement + '').toUpperCase()] = ob;
	};

	BX.rest.AppLayout.initialize = function(placement, sid)
	{
		placement = (placement + '').toUpperCase();

		BX.rest.layoutList[placement] = BX.rest.layoutList[sid];
		BX.rest.layoutList[placement].init();
	};

	BX.rest.AppLayout.destroy = function(id)
	{
		var layout = BX.rest.AppLayout.get(id);
		if(!!layout)
		{
			layout.destroy();
		}

		BX.rest.layoutList[layout.params.appSid] = null;

		if(!!BX.rest.AppLayout.placementInterface[id])
		{
			BX.rest.layoutList[id] = null;
		}
	};

	function split(s, ss)
	{
		var r = s.split(ss);
		return [r[0], r.slice(1, r.length - 2).join(ss), r[r.length - 2], r[r.length - 1]];
	}

})();