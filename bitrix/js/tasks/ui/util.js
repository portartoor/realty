BX.namespace('Tasks.UI');

BX.Tasks.UI.Util = {

	formatTimeAmount : function(time)
	{
		var pad = '00';
		var hours = '' + Math.floor(time / 3600);
		var minutes = '' + (Math.floor(time / 60) % 60);

		result = pad.substring(0, 2 - hours.length) + hours
			+ ':' + pad.substring(0, 2 - minutes.length) + minutes;

		seconds = '' + time % 60;
		result = result + ':' + pad.substring(0, 2 - seconds.length) + seconds;

		return (result);
	},

	delay: function(action, delay, ctx)
	{
		action = action || BX.DoNothing;
		delay = delay || 300;
		ctx = ctx || this;

		var timer = null;

		var f = function()
		{
			var args = arguments;
			timer = setTimeout(function(){
				action.apply(ctx, args);
			}, delay);
		};
		f.cancel = function()
		{
			clearTimeout(timer);
		};

		return f;
	},

	/*
	 Function assumes presence of the following css definition:
	 .invisible{height:0;}
	 */
	fadeToggle: function(node, way, duration, onComplete)
	{
		BX.Tasks.UI.Util.animateShowHide({
			node: node,
			duration: duration,
			way: way,
			toShow: {opacity: 100},
			toHide: {opacity: 0},
			complete: onComplete
		})
	},

	/*
	 Function assumes presence of the following css definition:
	 .invisible{height:0;opacity:0;}
	 */
	fadeSlideToggle: function(node, way, duration, onComplete)
	{
		BX.Tasks.UI.Util.animateShowHide({
			node: node,
			duration: duration,
			way: way,
			toShow: {opacity: 100, height: BX.Tasks.UI.Util.getInvisibleSize(node).height},
			toHide: {opacity: 0, height: 0},
			complete: onComplete
		});
	},

	fadeSlideHToggle: function(node, way, duration, onComplete)
	{
		BX.Tasks.UI.Util.animateShowHide({
			node: node,
			duration: duration,
			way: way,
			toShow: {opacity: 100, width: BX.Tasks.UI.Util.getInvisibleSize(node).width},
			toHide: {opacity: 0, width: 0},
			complete: onComplete
		});
	},

	getInvisibleSize: function(node)
	{
		var invisible = BX.hasClass(node, 'invisible');

		if(invisible)
		{
			BX.removeClass(node, 'invisible');
		}
		var p = BX.pos(node);
		if(invisible)
		{
			BX.addClass(node, 'invisible');
		}

		return p;
	},

	/*
	 Launching multiple animations on the same node at the same time is not supported.
	 */
	animate: function(params)
	{
		var params = params || {};
		var node = params.node || null;

		if(!BX.type.isElementNode(node))
		{
			return;
		}

		var duration = params.duration || 300;

		var rt = BX.Tasks.UI.Util.Runtime;

		if(typeof rt.animations == 'undefined')
		{
			rt.animations = [];
		}

		// add or get animation
		var anim = null;
		for(var k in rt.animations)
		{
			if(rt.animations[k].node == node)
			{
				anim = rt.animations[k];
				break;
			}
		}

		if(anim === null)
		{
			var easing = new BX.easing({
				duration : duration,
				start: params.start,
				finish: params.finish,
				transition: BX.easing.transitions.linear,
				step : params.step,
				complete: function()
				{
					params.complete.call(this);

					// cleanup animation
					for(var k in rt.animations)
					{
						if(rt.animations[k].node == node)
						{
							rt.animations[k].easing = null;
							rt.animations[k].node = null;

							rt.animations.splice(k, 1);

							break;
						}
					}

					node = null;
					anim = null;
				}
			});
			anim = {node: node, easing: easing};

			rt.animations.push(anim);
		}
		else
		{
			anim.easing.stop();
		}

		anim.easing.animate();
	},

	animateShowHide: function(params)
	{
		var params = params || {};
		var node = params.node || null;

		if(!BX.type.isElementNode(node))
		{
			return;
		}

		var invisible = BX.hasClass(node, 'invisible');
		var way = (typeof params.way == 'undefined' || params.way === null) ? invisible : !!params.way;

		if(invisible != way)
		{
			return;
		}

		var toShow = params.toShow || {};
		var toHide = params.toHide || {};

		BX.Tasks.UI.Util.animate({
			node: node,
			duration: params.duration,
			start: !way ? toShow : toHide,
			finish: way ? toShow : toHide,
			complete: function(){
				BX[!way ? 'addClass' : 'removeClass'](node, 'invisible');
				node.style.cssText = '';

				if(BX.type.isFunction(params.complete))
				{
					params.complete.call(this);
				}
			},
			step: function(state){

				if(typeof state.opacity != 'undefined')
				{
					node.style.opacity = state.opacity/100;
				}
				if(typeof state.height != 'undefined')
				{
					node.style.height = state.height+'px';
				}
				if(typeof state.width != 'undefined')
				{
					node.style.width = state.width+'px';
				}
			}
		});
	},

	isEnter: function(e)
	{
		e = e || window.event;

		return e.keyCode == 13;
	},

	filterFocusBlur: function(node, cbFocus, cbBlur, timeout)
	{
		if(!BX.type.isElementNode(node))
		{
			return false;
		}

		var timer = false;

		cbFocus = cbFocus || BX.DoNothing;
		cbBlur = cbBlur || BX.DoNothing;
		timeout = timeout || 50;

		var f = function(focus, eventArgs)
		{
			if(focus)
			{
				if(timer != false)
				{
					clearTimeout(timer);
					timer = false;
				}
				else
				{
					cbFocus.apply(this, eventArgs);
				}
			}
			else
			{
				timer = setTimeout(function(){
					timer = false;
					cbBlur.apply(this, eventArgs);
				}, timeout);
			}
		}

		BX.bind(node, 'blur', function(){f.apply(this, [false, arguments])});
		BX.bind(node, 'focus', function(){f.apply(this, [true, arguments])});

		return true;
	},

	bindInstantChange: function(node, cb, ctx)
	{
		if(!BX.type.isElementNode(node))
		{
			return BX.DoNothing;
		}

		ctx = ctx || node;

		var value = node.value;

		var f = BX.debounce(function(e){

			if(node.value.toString() != value.toString())
			{
				cb.apply(ctx, arguments);

				value = node.value;
			}
		}, 3, ctx);

		BX.bind(node, 'input', f);
		BX.bind(node, 'keyup', f);
		BX.bind(node, 'change', f);
	},

	// todo: remove this method
	fireChange: function(node)
	{
		if(!BX.type.isElementNode(node))
		{
			return;
		}

		if ("createEvent" in document)
		{
			var e = document.createEvent("HTMLEvents");
			e.initEvent("change", false, true);
			node.dispatchEvent(e);
		}
		else
		{
			node.fireEvent("onchange");
		}
	},

	disable: function(node)
	{
		if(BX.type.isElementNode(node))
		{
			node.setAttribute('disabled', 'disabled');
		}
	},

	enable: function(node)
	{
		if(BX.type.isElementNode(node))
		{
			node.removeAttribute('disabled');
		}
	},

	getMessagePlural: function(n, msgId)
	{
		var pluralForm, langId;

		langId = BX.message('LANGUAGE_ID');
		n = parseInt(n);

		if (n < 0)
		{
			n = (-1) * n;
		}

		if (langId)
		{
			switch (langId)
			{
				case 'de':
				case 'en':
					pluralForm = ((n !== 1) ? 1 : 0);
					break;

				case 'ru':
				case 'ua':
					pluralForm = ( ((n%10 === 1) && (n%100 !== 11)) ? 0 : (((n%10 >= 2) && (n%10 <= 4) && ((n%100 < 10) || (n%100 >= 20))) ? 1 : 2) );
					break;

				default:
					pluralForm = 1;
					break;
			}
		}
		else
		{
			pluralForm = 1;
		}

		if(BX.type.isArray(msgId))
		{
			return msgId[pluralForm];
		}

		return (BX.message(msgId + '_PLURAL_' + pluralForm));
	},

	showErrorPopup: function(errors, fn)
	{
		if(BX.Tasks.UI.Util.Runtime.errorPopup == null)
		{
			BX.Tasks.UI.Util.Runtime.errorPopup = new BX.PopupWindow("task-error-popup", null, { lightShadow: true });
		}

		var errorPopup = BX.Tasks.UI.Util.Runtime.errorPopup;

		if (errorPopup === null)
		{
			errorPopup = new BX.PopupWindow("task-error-popup", null, { lightShadow: true });
		}

		errorPopup.setButtons([
			new BX.PopupWindowButton({
				text: BX.message("JS_CORE_WINDOW_CLOSE"),
				className: "",
				events: {
					click: function() {
						if (BX.type.isFunction(fn))
						{
							fn();
						}
						else
						{
							BX.reload();
						}

						this.popupWindow.close();
					}
				}
			})
		]);

		var popupContent = "";
		for (var i = 0; i < errors.length; i++)
		{
			popupContent += (typeof(errors[i].MESSAGE) !== "undefined" ? errors[i].MESSAGE : errors[i]) + "<br>";
		}

		errorPopup.setContent(
			"<div style='width: 350px;padding: 10px; font-size: 12px; color: red;'>" +
			popupContent +
			"</div>"
		);

		if (window.console && window.console.dir)
		{
			window.console.dir(errors);
		}

		errorPopup.show();
	},

	showConfirmPopup: function(title, body, callback)
	{
		if(BX.Tasks.UI.Util.Runtime.confirmPopup == null)
		{
			BX.Tasks.UI.Util.Runtime.confirmPopup = new BX.PopupWindow(
				"task-confirm-popup",
				null,
				{
					zIndex : 22000,
					overlay : { opacity: 50 },
					titleBar : {},
					content : '',
					autoHide   : false,
					closeByEsc : false,
					buttons : [
						new BX.PopupWindowButton({
							text: BX.message('JS_CORE_WINDOW_CONTINUE'),
							className: "popup-window-button-accept",
							events : {
								click : function(){
									callback.apply(this, [true]);
									this.popupWindow.close();
								}
							}
						}),
						new BX.PopupWindowButton({
							text: BX.message('JS_CORE_WINDOW_CANCEL'),
							events : {
								click : function(){
									callback.apply(this, [false]);
									this.popupWindow.close();
								}
							}
						})
					]
				}
			);
		}
		BX.Tasks.UI.Util.Runtime.confirmPopup.setTitleBar({content: title});
		BX.Tasks.UI.Util.Runtime.confirmPopup.setContent(body.outerHTML);
		BX.Tasks.UI.Util.Runtime.confirmPopup.show();
	},

	fireGlobalTaskEvent: function(type, taskData, options, taskDataUgly)
	{
		if(!type)
		{
			return false;
		}

		type = type.toString();
		options = options || {};

		if(type != 'ADD' && type != 'UPDATE' && type != 'DELETE'&& type != 'NOOP')
		{
			return false;
		}

		var eventArgs = [type, {task: taskData, taskUgly: taskDataUgly, options: options}];

		BX.onCustomEvent(window, 'tasksTaskEvent', eventArgs);
		if(window != window.top) // if we are inside iframe, translate event to the parent window also
		{
			window.top.BX.onCustomEvent(window.top, 'tasksTaskEvent', eventArgs);
		}

		return true;
	}
};

BX.Tasks.UI.Util.hintManager = {
	show: function(node, body, callback, id, parameters)
	{
		id = id || BX.util.hashCode((Math.random()*100).toString()).toString();
		parameters = parameters || {};

		var rt = BX.Tasks.UI.Util.Runtime;

		rt.hintPopup = rt.hintPopup || {};

		if(typeof rt.hintPopup[id] == 'undefined')
		{
			rt.hintPopup[id] = {
				popup: null,
				disable: false
			};
		}

		if(rt.hintPopup[id].disable)
		{
			return;
		}

		if(rt.hintPopup[id].popup == null)
		{
			var content = [];
			if(BX.type.isNotEmptyString(parameters.title))
			{
				content.push(BX.create("SPAN",
					{attrs: {className: "task-hint-popup-title"}, text: parameters.title}
				));
			}
			content.push(BX.create("P", {text: body, style: {margin: '10px 20px 10px 5px'}}));

			if(BX.type.isNotEmptyString(parameters.closeLabel))
			{
				content.push(BX.create("P",
					{
						style: {margin: '10px 20px 10px 5px'},
						children: [
							BX.create("A",
								{
									props: {href: "javascript:void(0)"},
									text: parameters.closeLabel,
									events: {"click": function(){
										BX.Tasks.UI.Util.hintManager.disable(id);
										BX.Tasks.UI.Util.hintManager.hide(id);
									}}
								}
							)
						]
					}
				));
			}

			rt.hintPopup[id].popup = BX.PopupWindowManager.create(id,
				node,
				{
					closeByEsc: false,
					closeIcon: true,
					angle: {},
					autoHide: parameters.autoHide === true,
					offsetLeft: 50,
					offsetTop : 5,
					events: {onPopupClose: BX.delegate(this.onViewModeHintClose, this)},
					content: BX.create("DIV",
						{
							attrs: {className: "task-hint-popup-contents"},
							children: content
						}
					)
				}
			)
		}

		rt.hintPopup[id].popup.show();
	},

	hide: function(id)
	{
		try
		{
			BX.Tasks.UI.Util.Runtime.hintPopup[id].popup.close();
		}
		catch(e)
		{
		}
	},

	disable:  function(hintId)
	{
		BX.Tasks.UI.Util.Runtime.hintPopup[hintId].disable = true;
		BX.userOptions.save(
			"tasks",
			"task_hints",
			hintId,
			"N",
			false
		);
	},

	disableSeveral: function(pack)
	{
		if(BX.type.isPlainObject(pack))
		{
			var rt = BX.Tasks.UI.Util.Runtime;
			rt.hintPopup = rt.hintPopup || {};

			for(var id in pack)
			{
				rt.hintPopup[id] = rt.hintPopup[id] || {};
				rt.hintPopup[id].disable = !pack[id];
			}
		}
	}
};

if(typeof BX.Tasks.UI.Util.Runtime == 'undefined')
{
	BX.Tasks.UI.Util.Runtime = {
		errorPopup: null,
		confirmPopup: null
	};
}