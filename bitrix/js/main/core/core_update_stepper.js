;(function() {
var BX = window.BX;
if (BX["UpdateStepperRegister"])
	return;
var queue = new ((function(){
		var d = function() {
			this.length = 0;
			this.items = {};
			this.order = [];
			this.state = "ready";
			this.finish = BX.delegate(this.finish, this);
			this.send = BX.delegate(this.send, this);
			this.onSuccess = BX.delegate(this.onSuccess, this);
			this.onFailure = BX.delegate(this.onFailure, this);
		};
		d.prototype = {
			getQueue : function(id) {
				id += '';
				return BX.util.array_search(id, this.order);
			},
			removeItem : function(in_key) {
				in_key += '';
				var tmp_value, number;
				if (typeof(this.items[in_key]) !== 'undefined') {
					tmp_value = this.items[in_key];
					number = this.getQueue(in_key);
					this.pointer -= (this.pointer >= number ? 1 : 0);
					delete this.items[in_key];
					this.order = BX.util.deleteFromArray(this.order, number);
					this.length = this.order.length;

				}
				return tmp_value;
			},
			getItem : function(in_key) {
				in_key += '';
				return this.items[in_key];
			},
			hasItem : function(in_key) {
				in_key += '';
				return typeof(this.items[in_key]) !== 'undefined';
			},
			setItem : function(in_key, in_value) {
				in_key += '';
				if (typeof(in_value) !== 'undefined')
				{
					if (typeof(this.items[in_key]) === 'undefined')
					{
						this.order.push(in_key);
						this.length = this.order.length;
					}
					this.items[in_key] = in_value;
					BX.addCustomEvent(in_value, "onFinish", this.finish);
				}
				this.exec();
				return in_value;
			},
			getFirst : function() {
				var in_key, item = null;
				for (var ii = 0; ii < this.order.length; ii++)
				{
					in_key = this.order[ii];
					if (!!in_key && this.hasItem(in_key))
					{
						item = this.getItem(in_key);
						break;
					}
				}
				return item;
			},
			timeout : 0,
			exec : function() {
				if (this.timeout > 0)
					clearTimeout(this.timeout);
				if (this.length > 0)
					this.timeout = setTimeout(BX.proxy(this.send, this), 5000);
			},
			send : function() {
				var data = [], d;
				if (this.length > 0)
				{
					for (var ii in this.items)
					{
						if (this.items.hasOwnProperty(ii))
						{
							BX.onCustomEvent(this, "onPrepare", [data, this]);
						}
					}
				}
				if (data.length > 0)
				{
					BX.ajax({
						method: 'POST',
						dataType: 'json',
						data : { stepper : data },
						url: '/bitrix/tools/update.php?action=stepper',
						onsuccess: this.onSuccess,
						onfailure: this.onFailure
					});
				}
			},
			onSuccess : function(data) {
				BX.onCustomEvent(this, "onSuccess", [data, this]);
				this.exec();
			},
			onFailure : function(message) {
				if (message === "processing")
					message = "Incorrect server response.";
				BX.onCustomEvent(this, "onFailure", [message, this]);
			},
			finish : function(id) {
				this.removeItem(id);
			}
		};
		return d;
	})()),
UpdateStepper = (function() {
	var d = function(data) {
		this.id = data["id"];
		this.moduleId = data["moduleId"];
		this["class"] = data["class"];

		this.onPrepare = BX.delegate(this.onPrepare, this);
		this.onSuccess = BX.delegate(this.onSuccess, this);
		this.onFailure = BX.delegate(this.onFailure, this);

		BX.defer_proxy(this.bind, this)();
		queue.setItem(this.id, this);

		BX.addCustomEvent(queue, "onPrepare", this.onPrepare);
		BX.addCustomEvent(queue, "onSuccess", this.onSuccess);
		BX.addCustomEvent(queue, "onFailure", this.onFailure);
	};
	d.prototype = {
		show : function(data) {
			if (data["title"] && BX(this.nodes.title))
				this.nodes.title = data["title"];
			if (data["progress"] && BX(this.nodes.bar))
				this.nodes.bar.style.width = data["progress"] + "%";
			if (data["steps"] && BX(this.nodes.steps))
				this.nodes.steps.innerHTML = data["steps"];
		},
		bindSteps : 0,
		nodes : {
			container : null,
			bar : null,
			steps : null,
			title : null
		},
		bind : function() {
			if (this.bindSteps > 100)
				return;
			this.bindSteps++;
			if (BX(this.id + '-steps'))
			{
				this.nodes.container = BX(this.id + '-container');
				this.nodes.bar = BX(this.id + '-bar');
				this.nodes.steps = BX(this.id + '-steps');
				this.nodes.title = BX(this.id + '-title');
			}
			else
			{
				BX.defer_proxy(this.bind, this)();
			}
		},
		hide : function() {
			if (BX(this.nodes.container))
			{
				BX.addClass(this.nodes.container, "main-stepper-hide");
				BX.removeClass(this.nodes.container, "main-stepper-show");
			}
		},
		finish : function(){
			BX.removeCustomEvent(queue, "onPrepare", this.onPrepare);
			BX.removeCustomEvent(queue, "onSuccess", this.onSuccess);
			BX.removeCustomEvent(queue, "onFailure", this.onFailure);
			BX.onCustomEvent(this, "onFinish", [this.id, this]);
		},
		onPrepare : function(data) {
			data.push(
				{
					moduleId : this.moduleId,
					"class" : this["class"]
				}
			);
		},
		onSuccess : function(data) {

			var res = {};
			if (BX.type.isArray(data))
			{
				for (var ii = 0; ii < data.length; ii++)
				{
					if (data[ii]["moduleId"] === this.moduleId && data[ii]["class"] === this["class"])
					{
						res = data[ii];
						break;
					}
				}
			}
			if (res['status'] === "continue")
				this.show(res);
			else if (res['status'] === "error")
				this.onFailure(res['message']);
			else
			{
				this.hide();
				this.finish();
			}
		},
		onFailure : function(message) {
			this.showError(message);
			this.finish();
		},
		showError : function(message) {
			if (BX(this.nodes.title))
				this.nodes.title.innerHTML = message;
			if (this.nodes.container)
				BX.addClass(this.nodes.container, "main-stepper-error");
		}
	};
	return d;
})();
BX.UpdateStepperRegister = function(data) {
	for (var i = 0; i < data.length; i++)
	{
		new UpdateStepper(data[i]);
	}
};
})();
