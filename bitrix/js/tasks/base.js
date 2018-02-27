BX.namespace('Tasks');

BX.Tasks.Base = function(options)
{
}

BX.mergeEx(BX.Tasks.Base.prototype, {

	fireEvent: function(name, args)
	{
		BX.onCustomEvent(this, name, args);
	},

	bindEvent: function(name, callback)
	{
		BX.addCustomEvent(this, name, callback);
	},

	callMethod: function(classRef, name, arguments)
	{
		return classRef.prototype[name].apply(this, arguments);
	},

	runPostConstructors: function()
	{
		var stack = [];

		this.walkPrototypeChain(this, function(proto){
			if(typeof proto.construct == 'function')
			{
				stack.unshift(proto.construct);
			}
		});

		for(var k = 0; k < stack.length; k++)
		{
			stack[k].call(this);
		}
	},

	runParentConstructor: function(owner)
	{
		if(typeof owner.superclass == 'object')
		{
			owner.superclass.constructor.apply(this, [null, true]);
		}
	},

	walkPrototypeChain: function(obj, fn)
	{
		var ref = obj.constructor;
		while(typeof ref != 'undefined' && ref != null)
		{
			fn.apply(this, [ref.prototype, ref.superclass]);

			if(typeof ref.superclass == 'undefined')
			{
				return;
			}

			ref = ref.superclass.constructor;
		}
	},

	destroy: function()
	{
		this.walkPrototypeChain(this, function(proto){
			if(typeof proto.destruct == 'function')
			{
				proto.destruct.call(this);
			}
		});
	},

    /*
	getClassByChain: function(chain)
	{
		var scope = window;
		for(var i = 0; i < chain.length; i++)
		{
			if(typeof scope[chain[i]] == 'undefined')
			{
				return null;
			}

			scope = scope[chain[i]];
		}

		return scope;
	},
    */

	option: function(name, value)
	{
		if(typeof value != 'undefined')
		{
			this.opts[name] = value;
		}
		else
		{
			return typeof this.opts[name] != 'undefined' ? this.opts[name] : false;
		}
	},

    initialized: function()
    {
        return this.sys.initialized;
    },

	// util
	passCtx: function(f)
	{
		var this_ = this;
		return function()
		{
			var args = Array.prototype.slice.call(arguments);
			args.unshift(this); // this is a ctx of the node event happened on
			return f.apply(this_, args);
		}
	}
});

BX.Tasks.Base.extend = function(parameters){

	if(typeof parameters == 'undefined' || !BX.type.isPlainObject(parameters)) 
	{
		parameters = {};
	}

	var child = function(opts, middle){

		// inheritance
		this.isUIWidget = true; // prevent BX.merge() from going deeper on a widget instance
		this.runParentConstructor(child); // apply all parent constructors

		if(typeof this.opts == 'undefined')
		{
			this.opts = {};
		}
		if(typeof parameters.options != 'undefined' && BX.type.isPlainObject(parameters.options))
		{
			BX.mergeEx(this.opts, parameters.options);
		}

		if(typeof parameters.sys != 'undefined' && BX.type.isPlainObject(parameters.sys))
		{
			if(typeof this.sys == 'undefined')
			{
				this.sys = {
                    initialized: false
                };
			}
			BX.mergeEx(this.sys, parameters['sys']);
		}

		delete(parameters);
		delete(child);

		// in the last constructor we run this
		if(!middle)
		{
			// final version of opts array should be ready before "post-constructors" are called
			if(typeof opts != 'undefined' && BX.type.isPlainObject(opts))
			{
				BX.mergeEx(this.opts, opts);
			}

			this.runPostConstructors(); // event bind, aux data struct init, etc ...
            if(typeof this.sys != 'undefined')
            {
                this.sys.initialized = true;
            }
		}
	};
	child.extend = BX.Tasks.Base.extend; // just a short-cut to extend() function

	BX.extend(child, this);
    parameters.methods = parameters.methods || {};
    parameters.constants = parameters.constants || {};

	if(typeof parameters.methods != 'undefined' && BX.type.isPlainObject(parameters.methods))
	{
		for(var k in parameters.methods)
		{
			if(parameters.methods.hasOwnProperty(k))
			{
				child.prototype[k] = parameters.methods[k];
			}
		}
	}
	if(typeof parameters.constants != 'undefined' && BX.type.isPlainObject(parameters.constants))
	{
		for(var k in parameters.constants)
		{
			if(parameters.constants.hasOwnProperty(k))
			{
				child.prototype[k] = parameters.constants[k];
			}
		}
	}

	// anonymous construct & destruct to prevent prototype hierarchy fall through the proto-chain walking
	if(typeof parameters.methods.construct != 'function')
	{
		child.prototype.construct = BX.DoNothing();
	}
	if(typeof parameters.methods.destruct != 'function')
	{
		child.prototype.destruct = BX.DoNothing();
	}

	return child;
};