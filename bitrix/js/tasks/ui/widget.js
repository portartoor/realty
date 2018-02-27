BX.namespace('Tasks.UI');

BX.Tasks.UI.Widget = BX.Tasks.Base.extend({
	options: {
		id: 				false, // instance id, may vary very wide, must be unique on the page if used
		scope: 				false,
		removeTemplates: 	false, // remove template nodes from DOM after initialization. Setting to true MAY and WILL increase page load time when widget is massively used
		registerDispatcher: false
	},
	sys: {
		id: 				false, // instance id
		code: 				'generic-widget', // class code, hardcoded
		instanceCode: 		false, // UNUSED
		scope: 				false, // widget DOM scope
		parent: 			false // parent widget, if any
	},
	methods: {
		construct: function()
		{
			this.vars = {
				cache: {
					control: {}
				}
			};
			this.ctrls =        {};
            this.instances =    {};
			this.tmpls =        false;

			if(!('querySelector' in document))
			{
				throw new Error('Your browser does not support querySelector');
			}

            this.id(this.option('id'));
            this.parent(this.option('parent'));

            if(this.option('removeTemplates'))
            {
                this.findTemplates();
            }

			this.register();
		},

		destruct: function()
		{
			this.vars = null;
			this.sys = null;
			this.ctrls = null;
			this.tmpls = null;
			this.sys = null;

			// unbind events here
		},

		scope: function()
		{
			return this.detectScope();
		},

		// search down the tree, find first match
		control: function(id, scope)
		{
			if(!scope)
			{
				if(typeof this.vars.cache.control[id] == 'undefined')
				{
					var control = this.scope().querySelector(this.getControlSearchQuery(id));
					if(control !== null)
					{
						this.vars.cache.control[id] = control;
					}
				}

				return this.vars.cache.control[id];
			}
			else
			{
				return scope.querySelector(this.getControlSearchQuery(id));
			}
		},

		// search down the tree, find all matches
		controlAll: function(id, scope)
		{
			scope = scope || this.scope();

			return scope.querySelectorAll(this.getControlSearchQuery(id));
		},

		// search up the tree till scope reached, find first match
		controlP: function(id, node, scope)
		{
			scope = scope || this.scope();

			return BX.findParent(node, this.getControlMatchCondition(id), scope);
		},

		detectScope: function()
		{
            if(this.sys.scope === false)
            {
                if(this.opts.scope !== false)
                {
                    if(BX.type.isNotEmptyString(this.opts.scope))
                    {
                        var scope = BX(this.opts.scope);
                        if(BX.type.isElementNode(scope))
                        {
                            this.sys.scope = scope;
                        }
                        else if(this.parent())
                        {
                            this.sys.scope = this.parent().control(this.opts.scope);
                        }
                    }
                    else if(BX.type.isElementNode(this.opts.scope))
                    {
                        this.sys.scope = this.opts.scope;
                    }
                }
                else if(this.id())
                {
                    this.sys.scope = BX('bx-component-scope-'+this.id());
                }

                if(!BX.type.isElementNode(this.sys.scope))
                {
                    throw new Error('Cant find correct scope for '+this.code()+(this.id() ? '.'+this.id() : ''));
                }
            }

            return this.sys.scope;
		},

		getFullBxId: function(id)
		{
			var s = [];
			if(this.code() !== false)
			{
				s.push(this.code());
			}
			if(this.sys.instanceCode !== false)
			{
				s.push(this.sys.instanceCode);
			}
			s.push(id);

			return s.join('-');
		},

		getControlSearchQuery: function(id)
		{
			return '[data-bx-id~="'+this.getFullBxId(id)+'"]';
		},
		getControlMatchCondition: function(id)
		{
			return {attr: {'data-bx-id': new RegExp(this.getFullBxId(id))}}; // search substring, not exact match
		},

		code: function()
		{
            return this.sys.code;
		},

		id: function(id)
		{
            if(typeof id != 'undefined' && BX.type.isNotEmptyString(id))
            {
                this.sys.id = id.toString().toLowerCase();
            }
            else
            {
                return this.sys.id;
            }
		},

		parent: function(widgetInstance)
		{
			if(typeof widgetInstance != 'undefined' && widgetInstance != null)
			{
				this.sys.parent = widgetInstance;
			}
			else
			{
				return this.sys.parent;
			}
		},

        optionP: function(name, value)
        {
            if(typeof value != 'undefined')
            {
                this.callMethod(BX.Tasks.Base, 'option', [name, value]);
            }
            else
            {
                if(typeof this.opts[name] != 'undefined' && this.opts[name] != null)
                {
                    return this.callMethod(BX.Tasks.Base, 'option', [name]);
                }
                if(this.parent() != false)
                {
                    return this.parent().option(name);
                }

                return null;
            }
        },

        // unused
		instanceCode: function(code)
		{
			if(typeof code != 'undefined' && BX.type.isNotEmptyString(code))
			{
				this.sys.instanceCode = code.toString().toLowerCase();
			}
			else
			{
				return this.sys.instanceCode;
			}
		},

		findTemplates: function()
		{
            if(this.tmpls === false)
            {
                this.tmpls = {};

                var templates = this.scope().querySelectorAll('script[type="text/html"]');
                for(var k = 0; k < templates.length; k++)
                {
                    var id = BX.data(templates[k], 'bx-id');

                    if(typeof id == 'string' && id.length > 0)
                    {
                        this.tmpls[id] = templates[k].innerHTML;

                        // todo: remove only own templates!
                        if(this.option('removeTemplates'))
                        {
                            BX.remove(templates[k]);
                        }
                    }
                }
            }
		},

		getNodeByTemplate: function(id, data)
		{
			var template = this.template(id);

			return template.getNode(data, false);
		},

		getHTMLByTemplate: function(id, data)
		{
			var template = this.template(id);

			return template.get(data);
		},

		template: function(id)
		{
			if(typeof BX.Tasks.Util.Template == 'undefined')
			{
				throw new ReferenceError('Template API does not seem to be included');
			}

            this.findTemplates();

			var bxId = this.getFullBxId(id);

			if(typeof this.tmpls[bxId] == 'undefined')
			{
				throw new ReferenceError('No such template: '+id+' ('+bxId+')');
			}

			if(typeof this.tmpls[bxId] == 'string')
			{
				this.tmpls[bxId] = BX.Tasks.Util.Template.compile(this.tmpls[bxId]);
			}

			return this.tmpls[bxId];
		},

		register: function()
		{
			if(this.id() && this.option('registerDispatcher'))
			{
				BX.Tasks.UI.Dispatcher.register(this.id(), this);
			}
		},

        /*
		fireEvent: function(name, args)
		{
			this.callMethod(BX.Tasks.Base, 'fireEvent', arguments);

            if(this.id() && this.option('registerDispatcher'))
            {
                BX.Tasks.UI.Dispatcher.fireEvent(this.id(), name, args);
            }
		},
        */

		// util
		bindControl: function(id, eventName, callback)
		{
			BX.bind(this.control(id), eventName, callback);
		},

        bindDelegateControl: function(eventName, id, callback, scope)
        {
            scope = scope || this.scope();
            BX.bindDelegate(scope, eventName, this.getControlMatchCondition(id), callback);
        },

		// css flags
        setCSSMode: function(mode, value, scope)
        {
            this.dropCSSFlags(mode+'-*', scope);
            this.setCSSFlag(mode+'-'+value, scope);
        },

		dropCSSFlags: function(flagPattern, scope)
		{
			scope = scope || this.scope();

			var cList = scope.classList;
			flagPattern = new RegExp('^'+flagPattern.replace('*', '[a-z0-9-]*')+'$');

			for(var k = 0; k < cList.length; k++)
			{
				if(cList[k].toString().match(flagPattern))
				{
					BX.removeClass(scope, cList[k]);
				}
			}
		},

        setCSSFlag: function(flagName, scope)
        {
            this.changeCSSFlag(flagName, true, scope);
        },

        dropCSSFlag: function(flagName, scope)
        {
            this.changeCSSFlag(flagName, false, scope);
        },

        changeCSSFlag: function(flagName, way, scope)
        {
            scope = scope || this.scope();
            if(typeof flagName != 'string' || flagName.length == 0)
            {
                return;
            }

            BX[way ? 'addClass' : 'removeClass'](scope, flagName);
        },

		toggleCSSMap: function(map, scope)
		{
			scope = scope || this.scope();
			var classes = scope.className.split(' ');
			var result = [];
			for(var k = 0; k < classes.length; k++)
			{
				if(!(classes[k] in map))
				{
					result.push(classes[k]); // left unchanged
				}
			}
			for(k in map)
			{
				if(map[k])
				{
					result.push(k);
				}
			}

			scope.className = result.join(' ');
		}
	}
});

BX.Tasks.UI.Dispatcher = BX.Tasks.Base.extend({
	options: {
	},
	methods: {
		construct: function()
		{
			this.vars = {
				registry: {},
				events: {}
			};
		},
		destruct: function()
		{
			this.vars = null;
		},
		register: function(id, instance)
		{
			if(!BX.type.isNotEmptyString(id))
			{
				throw new ReferenceError('Id must not be empty');
			}

			if(instance == null || instance == false)
			{
				throw new ReferenceError('Bad instance');
			}

			if(typeof this.vars.registry[id] != 'undefined')
			{
				throw new ReferenceError('The id "'+id.toString()+'" is already in use in registry');
			}

			this.vars.registry[id] = instance;
		},
		get: function(id)
		{
			if(typeof this.vars.registry[id] == 'undefined')
			{
				return null;
			}

			return this.vars.registry[id];
		}
	}
});
BX.Tasks.UI.Dispatcher.register = function(id, instance)
{
	BX.Tasks.UI.Dispatcher.getInstance().register(id, instance);
};
BX.Tasks.UI.Dispatcher.get = function(id)
{
	return BX.Tasks.UI.Dispatcher.getInstance().get(id);
};
BX.Tasks.UI.Dispatcher.bindEvent = function(id, name, cb)
{
	if(!BX.type.isNotEmptyString(id))
	{
		throw new TypeError('Bad id: '+id);
	}

	if(!BX.type.isNotEmptyString(name))
	{
		throw new TypeError('Bad event name: '+name);
	}

	if(!BX.type.isFunction(cb))
	{
		throw new TypeError('Callback is not a function to call for: '+id+' '+name);
	}

    var dInst = BX.Tasks.UI.Dispatcher.getInstance();

	if(typeof dInst.vars.events[name] == 'undefined')
	{
		dInst.vars.events[name] = {};
	}

	dInst.vars.events[name][id] = cb;
},
BX.Tasks.UI.Dispatcher.fireEvent = function(id, name, args)
{
    if(!BX.type.isNotEmptyString(id))
    {
        throw new TypeError('Bad id: '+id);
    }

	if(!BX.type.isNotEmptyString(name))
	{
		throw new TypeError('Bad event name: '+name);
	}

	args = args || [];

	var dInst = BX.Tasks.UI.Dispatcher.getInstance();

    var inst = dInst.get(id);
    if(inst)
    {
        inst.fireEvent(name, args);
    }

    /*
	// find instance
	for(var k in dInst.vars.registry)
	{
		if(dInst.vars.registry[k] == ref)
		{
			// find callback
			if(typeof dInst.vars.events[name] != 'undefined' && typeof dInst.vars.events[name][k] != 'undefined')
			{
				dInst.vars.events[name][k].apply(ref, args);
			}

			return;
		}
	}
    */
},
BX.Tasks.UI.Dispatcher.getInstance = function()
{
	if(typeof BX.Tasks.Singletons == 'undefined')
	{
		BX.Tasks.Singletons = {};
	}
	if(typeof BX.Tasks.Singletons.dispatcher == 'undefined')
	{
		BX.Tasks.Singletons.dispatcher = new BX.Tasks.UI.Dispatcher();
	}

    return BX.Tasks.Singletons.dispatcher;
}