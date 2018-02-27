BX.namespace("BX.Bitrix24");

BX.Bitrix24.PageSlider = {

	defaultOptions: {
		panelStylezIndex: 3200,
		imStylezIndex: 3300,
		imBarStylezIndex: 3100,
		headerStylezIndex: 3100,
		maxWidth: "auto",
		minWidth: "auto",
		overlayClassName: "slider-panel-overlay",
		overlayTop: false,
		containerClassName: "slider-panel-container",
		closeBtn: "slider-panel-close",
		contentClassName: "slider-panel-content-container",
		contentPadding: 0,
		typeLoader: "default-loader",
		top: "inherit",
		right: "inherit",
		bottom: "inherit",
		height: "inherit",
		width: "inherit",

		anchorRules: []
	},

	init: function()
	{
		if (this.inited)
		{
			return;
		}

		this.panel = BX("bx-panel");
		this.header= BX("header");
		this.imBar = BX("bx-im-bar");

		this.isOpen = false;
		this.inited = true;
		this.iframeSrc = null;

		this.animation = null;
		this.animationDuration = 200;
		this.startParams = { translateX: 100, opacity: 0 };
		this.endParams = { translateX: 0, opacity: 100 };
		this.currentParams = null;

		this.createLayout();

		BX.addCustomEvent("BX.Bitrix24.PageSlider:close", this.close.bind(this));
		BX.addCustomEvent("BX.Bitrix24.Map:onBeforeOpen", this.close.bind(this, true));
	},

	setOptions: function(options)
	{

		options = BX.type.isPlainObject(options) ? options : {};

		this.defaultOptions = BX.mergeEx(this.defaultOptions, options);
	},

	createLayout: function()
	{
		this.overlay = BX.create("div", {
			props: {
				className: this.options.overlayClassName
			},
			events: {
				click: this.close.bind(this)
			}
		});

		this.container = BX.create("div", {
			props: {
				className: this.options.containerClassName
			}
		});

		this.closeBtn = BX.create("span", {
			props: {
				className: this.options.closeBtn
			},
			children : [
				BX.create("span", {
					props: {
						className: this.options.closeBtn+"-inner"
					}
				})
			],
			events: {
				click: this.close.bind(this)
			}
		});

		this.content = BX.create("div", {
			props: {
				className: this.options.contentClassName
			}
		});

		this.iframe = BX.create("iframe", {
			attrs: {
				"src": "about:blank",
				"frameborder": "0"
			},
			events: {
				load: this.onIframeLoad.bind(this)
			}
		});

		this.container.appendChild(this.content);
		this.container.appendChild(this.closeBtn);
		this.content.appendChild(this.iframe);

		document.body.appendChild(this.overlay);
		document.body.appendChild(this.container);
	},

	getLoaderId: function(url)
	{
		var loader = this.options.typeLoader;
		var rule = this.getUrlRule(url);
		if (rule && BX.type.isNotEmptyString(rule.loader))
		{
			loader = rule.loader;
		}

		return loader;
	},

	createLoader: function(loader)
	{
		BX.remove(this.loader);

		loader = BX.type.isNotEmptyString(loader) ? loader : "default-loader";

		if (loader === "default-loader")
		{
			this.loader = BX.create("div", {
				props: {
					className: "slider-panel-loader " + loader
				},
				children:[
					BX.create("div",{
						props:{
							className: "b24-loader b24-loader-show"
						},
						children: [
							BX.create("div",{
								props:{
									className: "b24-loader-curtain"
								}
							})
						]
					})
				]
			});
		}
		else
		{
			this.loader = BX.create("div", {
				props: {
					className: "slider-panel-loader " + loader
				},
				children: [
					BX.create("img", {
						attrs: {
							src: "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAwAAAAMCAMAAABhq6zVAAAAA1BMVEX///+nxBvIAAAAAXRSTlMAQObYZgAAAAtJREFUeAFjGMQAAACcAAG25ruvAAAAAElFTkSuQmCC"
						},
						props: {
							className: "slider-panel-loader-mask left"
						}
					}),
					BX.create("img", {
						attrs: {
							src: "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAwAAAAMCAMAAABhq6zVAAAAA1BMVEX///+nxBvIAAAAAXRSTlMAQObYZgAAAAtJREFUeAFjGMQAAACcAAG25ruvAAAAAElFTkSuQmCC"
						},
						props: {
							className: "slider-panel-loader-mask right"
						}
					})
				]
			});
		}

		this.loader.dataset.loader = loader;
		this.content.appendChild(this.loader);
	},

	showLoader: function(loader)
	{
		if (!this.loader || this.loader.dataset.loader !== loader)
		{
			this.createLoader(loader);
		}

		this.loader.style.opacity = 1;
		this.loader.style.display = "block";
	},

	closeLoader: function()
	{
		this.loader.style.display = "none";
		this.loader.style.opacity = 0;
	},

	onIframeLoad: function(event)
	{
		var iframeWindow = this.iframe.contentWindow;
		var iframeLocation = iframeWindow.location;

		if (iframeLocation.toString() === "about:blank")
		{
			return;
		}

		this.closeLoader();

		BX.bind(iframeWindow, "keyup", this.onDocumentKeyDown.bind(this));

		if (iframeWindow.BX)
		{
			iframeWindow.BX.onCustomEvent("BX.Bitrix24.PageSlider:onOpen", [this]);
		}

		var iframeUrl = iframeLocation.pathname + iframeLocation.search + iframeLocation.hash;
		this.iframeSrc = BX.util.remove_url_param(iframeUrl, ["IFRAME", "IFRAME_TYPE"]);
	},

	adjustLayout: function()
	{
		var top = 0;
		var right = 0;
		var height = "100vh";
		if (this.options.overlayTop === false)
		{
			var headerPosition = BX.pos(this.header);
			var scrollTop = window.pageYOffset || document.documentElement.scrollTop;

			top = headerPosition.bottom - scrollTop;
			if (top < 0)
			{
				top = 0;
			}

			height = "calc(100vh - " + top + "px)";

			if (this.imBar)
			{
				right = this.imBar.offsetWidth;
			}
		}
		else
		{
			if (this.imBar)
			{
				this.imBar.style.removeProperty("z-index");
			}
		}

		var width = "100%";
		var maxWidth = this.options.maxWidth + "px";
		var minWidth = this.options.minWidth > this.options.maxWidth ? 0 : this.options.minWidth;

		if (this.options.maxWidth === "auto")
		{
			var leftMenuWidth = 240;
			var imbarWidth = this.imBar ? this.imBar.offsetWidth : 0;
			var pageWidth = document.documentElement.clientWidth;
			var scrollWidth = window.innerWidth - pageWidth;
			var delta = leftMenuWidth + imbarWidth + scrollWidth;

			if (pageWidth < 1160)
			{
				delta -= 175;
			}

			width = "calc(100% - " + delta + "px)";
			maxWidth = "auto";
		}

		this.overlay.style.height = height;
		this.overlay.style.top = top + "px";

		this.container.style.width = width;
		this.container.style.height = height;
		this.container.style.minWidth = minWidth + "px";
		this.container.style.maxWidth = maxWidth + "px";
		this.container.style.top = top + "px";
		this.container.style.right = right + "px";
	},

	setFrameSrc: function(url)
	{
		if (BX.type.isNotEmptyString(url))
		{
			if (this.iframeSrc !== url)
			{
				this.iframeSrc = url;
				this.iframe.src =
					BX.util.add_url_param(url, {
						IFRAME: "Y",
						IFRAME_TYPE: "SIDE_SLIDER"
					});
			}
		}
		else
		{
			this.iframeSrc = null;
		}
	},

	open: function(url, options)
	{
		if (!BX.type.isNotEmptyString(url))
		{
			return;
		}

		options = BX.type.isPlainObject(options) ? options : {};
		this.options = BX.mergeEx({}, this.defaultOptions, options);

		if (!this.isOpen)
		{
			this.init();
			this.applyHacks();
			this.adjustLayout();
			this.bindEvents();
		}

		if (this.iframeSrc !== url)
		{
			this.setFrameSrc(url);
			var loader = this.getLoaderId(url);
			this.showLoader(loader);
		}

		if (BX.type.isDomNode(document.activeElement))
		{
			document.activeElement.blur();
		}

		this.animateOpening();

		this.isOpen = true;
	},

	animateOpening: function()
	{
		if (this.isOpen)
		{
			return;
		}

		BX.addClass(this.overlay, "slider-panel-overlay-open");
		BX.addClass(this.container, "slider-panel-container-open");

		if (this.animation)
		{
			this.animation.stop();
		}

		this.animation = new BX.easing({
			duration : this.animationDuration,
			start: this.currentParams ? this.currentParams : this.startParams,
			finish: this.endParams,
			transition : BX.easing.transitions.linear,
			step: BX.delegate(function(state) {
				this.currentParams = state;
				this.animateStep(state);
			}, this),
			complete: BX.delegate(function() {
				this.completeAnimation();
			}, this)
		});

		this.animation.animate();
	},

	close: function(immediately)
	{
		if (!this.isOpen)
		{
			if (this.animation)
			{
				this.animation.stop(true);
			}

			return;
		}

		this.isOpen = false;

		this.unbindEvents();

		if (this.animation)
		{
			this.animation.stop();
		}

		var iframeWindow = this.iframe.contentWindow;
		if (iframeWindow.BX)
		{
			iframeWindow.BX.onCustomEvent("BX.Bitrix24.PageSlider:onClose", [this]);
		}

		if (immediately === true)
		{
			this.currentParams = this.startParams;
			this.completeAnimation();
		}
		else
		{
			this.animation = new BX.easing({
				duration : this.animationDuration,
				start: this.currentParams,
				finish: this.startParams,
				transition : BX.easing.transitions.linear,
				step: BX.delegate(function(state) {
					this.currentParams = state;
					this.animateStep(state);
				}, this),
				complete: BX.delegate(function() {
					this.completeAnimation();
				}, this)
			});

			this.animation.animate();
		}
	},

	completeAnimation: function()
	{
		this.animation = null;
		if (this.isOpen)
		{
			this.currentParams = this.endParams;
		}
		else
		{
			this.currentParams = this.startParams;

			BX.removeClass(this.overlay, "slider-panel-overlay-open");
			BX.removeClass(this.container, "slider-panel-container-open");

			this.container.style.removeProperty("width");
			this.container.style.removeProperty("right");
			this.container.style.removeProperty("max-width");
			this.container.style.removeProperty("min-width");
			this.closeBtn.style.removeProperty("opacity");

			this.resetHacks();
		}
	},

	applyHacks: function()
	{
		var scrollWidth = window.innerWidth - document.documentElement.clientWidth;
		document.body.style.paddingRight = scrollWidth + "px";
		document.body.style.overflow = "hidden";

		this.header.style.paddingRight = scrollWidth + "px";
		this.header.style.marginRight = "-" + scrollWidth + "px";
		this.header.style.zIndex = this.options.overlayTop === false ? this.options.headerStylezIndex: "inherit";

		if (this.imBar)
		{
			this.imBar.style.zIndex = this.options.imBarStylezIndex;
			this.imBar.style.width = this.imBar.offsetWidth + scrollWidth - 1 + "px";
		}

		if (this.panel)
		{
			this.panel.style.zIndex = this.options.panelStylezIndex;
		}
	},

	resetHacks: function()
	{
		document.body.style.cssText = "";

		if (this.panel)
		{
			this.panel.style.removeProperty("z-index");
		}

		if (this.imBar)
		{
			this.imBar.style.removeProperty("z-index");
			this.imBar.style.removeProperty("width");
		}

		this.header.style.cssText = "";
	},

	animateStep: function(state)
	{
		this.container.style.transform = "translateX(" + state.translateX + "%)";
		this.overlay.style.opacity = state.opacity / 100;
		this.closeBtn.style.opacity = state.opacity / 100;
	},

	/**
	 *
	 * @param {MouseEvent} event
	 * @returns {string} [link.href]
	 * @returns {string} [link.target]
	 * @returns {Node} [link.anchor]
	 * @returns {object|null} link
	 */
	extractLinkFromEvent: function(event)
	{
		event = event || window.event;
		var target = event.target;

		if (event.which !== 1 || !BX.type.isDomNode(target) || event.ctrlKey || event.metaKey)
		{
			return null;
		}

		var a = target;
		if (target.nodeName !== "A")
		{
			a = BX.findParent(target, { tag: "A" }, 1);
		}

		if (!BX.type.isDomNode(a))
		{
			return null;
		}

		// do not use a.href here, the code will fail on links like <a href="#SG13"></a>
		var href = a.getAttribute("href");
		if (href && !BX.data(a, "slider-ignore-autobinding") && !BX.ajax.isCrossDomain(href))
		{
			return {
				url: href,
				anchor: a,
				target: a.getAttribute("target")
			};
		}

		return null;
	},

	handleClick: function(event)
	{
		var link = this.extractLinkFromEvent(event);
		if (!link)
		{
			return;
		}

		var rule = this.getUrlRule(link.url, link);
		if (!rule)
		{
			return;
		}

		var isValidLink = BX.type.isFunction(rule.validate) ? rule.validate(link) : this.isValidLink(link);
		if (!isValidLink)
		{
			return;
		}

		if (BX.type.isFunction(rule.handler))
		{
			rule.handler(event, link);
		}
		else
		{
			event.preventDefault();
			this.open(link.url);
		}
	},

	getUrlRule: function(href, link)
	{
		if (!BX.type.isNotEmptyString(href))
		{
			return null;
		}

		var ar = this.defaultOptions.anchorRules;
		var rule = null;

		for (var k = 0; k < ar.length; k++)
		{
			rule = ar[k];

			if (!BX.type.isArray(rule.condition))
			{
				continue;
			}

			for (var m = 0; m < rule.condition.length; m++)
			{
				if (BX.type.isString(rule.condition[m]))
				{
					rule.condition[m] = new RegExp(rule.condition[m], "i");
				}

				var matches = href.match(rule.condition[m]);
				if (matches && !this.hasStopParams(href, rule.stopParameters))
				{
					if (link)
					{
						link.matches = matches;
					}

					return rule;
				}
			}
		}

		return null;
	},
	
	isValidLink: function(link)
	{
		return link.target !== "_blank" && link.target !== "_top";
	},

	hasStopParams: function(url, params)
	{
		if (!params || !BX.type.isArray(params) || !BX.type.isNotEmptyString(url))
		{
			return false;
		}

		var questionPos = url.indexOf("?");
		if (questionPos === -1)
		{
			return false;
		}

		var query = url.substring(questionPos);
		for (var i = 0; i < params.length; i++)
		{
			var param = params[i];
			if (query.match(new RegExp("[?&]" + param + "=", "i")))
			{
				return true;
			}
		}

		return false;
	},

	bindAnchors: function(parameters)
	{
		parameters = parameters || {};

		if (BX.type.isArray(parameters.rules))
		{
			this.defaultOptions.anchorRules = this.defaultOptions.anchorRules.concat(parameters.rules);
		}

		if (!this.anchorHandler)
		{
			this.anchorHandler = this.handleClick.bind(this);
			window.document.addEventListener("click", this.anchorHandler, true);
		}
	},

	bindEvents: function()
	{
		BX.bind(document, "keydown", BX.proxy(this.onDocumentKeyDown, this));

		setTimeout(function() {
			BX.bind(document, "click", BX.proxy(this.onDocumentClick, this));
		}.bind(this), 0);

		BX.bind(window, "resize", BX.throttle(this.onWindowResize, 300, this));
		BX.bind(window, "scroll", BX.proxy(this.adjustLayout, this)); //Live Comments can change scrollTop

		if (this.header)
		{
			this.header.addEventListener("click", BX.proxy(this.onHeaderClick, this), true);
		}

		BX.addCustomEvent("OnMessengerWindowShowPopup", BX.proxy(this.onMessengerOpen, this));

		if (BX.browser.IsMobile())
		{
			BX.bind(document.body, "touchmove", BX.proxy(this.disableScroll, this));
		}
	},

	unbindEvents: function()
	{
		BX.unbind(document, "keydown", BX.proxy(this.onDocumentKeyDown, this));
		BX.unbind(document, "click", BX.proxy(this.onDocumentClick, this));
		BX.unbind(window, "resize", BX.proxy(this.onWindowResize, this));
		BX.unbind(window, "scroll", BX.proxy(this.adjustLayout, this));

		if (this.header)
		{
			this.header.removeEventListener("click", BX.proxy(this.onHeaderClick, this), true);
		}

		BX.removeCustomEvent("OnMessengerWindowShowPopup", BX.proxy(this.onMessengerOpen, this));

		if (BX.browser.IsMobile())
		{
			BX.unbind(document.body, "touchmove", BX.proxy(this.disableScroll, this));
		}
	},

	onMessengerOpen: function()
	{
		if (this.isOpen)
		{
			this.close(true);
			BX.addCustomEvent("OnMessengerWindowClosePopup", BX.proxy(this.onMessengerClose, this));
		}
	},

	onMessengerClose: function()
	{
		this.open(this.iframeSrc);
		BX.removeCustomEvent("OnMessengerWindowClosePopup", BX.proxy(this.onMessengerClose, this));
	},

	onDocumentKeyDown: function(event)
	{
		if (this.isViewerVisible())
		{
			return;
		}

		if (event.keyCode === 27)
		{
			event.preventDefault(); //otherwise an iframe loading can be cancelled by a browser
			this.close();
		}
	},

	onDocumentClick: function(event)
	{
		if (this.isViewerVisible())
		{
			return;
		}

		if (this.overlay.contains(event.target) || this.container.contains(event.target))
		{
			return;
		}

		if (this.imBar && this.imBar.contains(event.target))
		{
			return;
		}

		if (BX.findParent(event.target, { className: "popup-window" }))
		{
			return;
		}

		this.close();

	},

	onHeaderClick: function(event)
	{
		//we are trying to resolve a conflict with the help popup.
		if (this.isOpen && event.target.className.match(/help-/))
		{
			this.close(true);
		}
	},

	onWindowResize: function()
	{
		this.adjustLayout();
	},

	isViewerVisible: function()
	{
		return BX.CViewer && BX.CViewer.objNowInShow && BX.CViewer.objNowInShow.bVisible;
	},

	disableScroll: function(event)
	{
		event.preventDefault();
	}
};
