
;(function() {

	if (window.CrmActivityEmailView)
		return;

	CrmActivityEmailView = function(id, options)
	{
		var self = this;

		self.id = id;
		self.options = options;

		if (self.options.pageSize < 1 || self.options.pageSize > 100)
			self.options.pageSize = 5;

		self.primaryNode = BX('crm-activity-email-details-'+self.id);
		self.wrapper = self.primaryNode.parentNode;

		self.log = {'a': 1, 'b': 1};

		self.scrollWrapper(self.primaryNode.offsetTop-self.wrapper.offsetTop);

		var moreA = BX.findChildByClassName(self.wrapper, 'crm-task-list-mail-more-a', true);
		if (moreA)
		{
			BX.bind(moreA, 'click', function(event)
			{
				self.loadLog(event, this, 'a');
				return false;
			});
		}

		var moreB = BX.findChildByClassName(self.wrapper, 'crm-task-list-mail-more-b', true);
		if (moreB)
		{
			BX.bind(moreB, 'click', function(event)
			{
				self.loadLog(event, this, 'b');
				return false;
			});
		}

		var items = BX.findChildrenByClassName(self.wrapper, 'crm-task-list-mail-item', true);
		for (var i in items)
		{
			BX.bind(items[i], 'click', function(event) {
				self.toggleLogItem(event, this);
			});
		}
	};

	CrmActivityEmailView.prototype.scrollWrapper = function(pos)
	{
		var self = this;

		if (self.wrapper.animation)
		{
			clearInterval(self.wrapper.animation);
			self.wrapper.animation = null;
		}

		var start = self.wrapper.scrollTop;
		var delta = pos - start;
		var step = 0;
		self.wrapper.animation = setInterval(function()
		{
			step++;
			self.wrapper.scrollTop = start + delta * step/8;

			if (step >= 8)
			{
				clearInterval(self.wrapper.animation);
				self.wrapper.animation = null;
			}
		}, 20);
	};

	CrmActivityEmailView.prototype.loadLog = function(event, el, log)
	{
		var self = this;

		event = event || window.event;
		event.preventDefault();

		var separator = el.parentNode;

		BX.ajax({
			method: 'POST',
			url: self.options.ajaxUrl,
			data: {
				act: 'log',
				id: self.id,
				log: log + (1+self.log[log]),
				size: self.options.pageSize
			},
			dataType: 'json',
			onsuccess: function(json)
			{
				if (json.result != 'error')
				{
					self.log[log]++;

					var dummy = document.createElement('DIV');
					dummy.innerHTML = json.html;

					var marker = log == 'a' ? BX.findNextSibling(separator, {'tag': 'div'}) : separator;
					while (dummy.childNodes.length > 0)
					{
						var item = separator.parentNode.insertBefore(dummy.childNodes[0], marker);
						if (item.nodeType == 1 && BX.hasClass(item, 'crm-task-list-mail-item'))
						{
							BX.bind(item, 'click', function(event) {
								self.toggleLogItem(event, this);
							});
						}
					}

					var items = BX.findChildrenByClassName(self.wrapper, 'crm-task-list-mail-item', true);
					for (var i in items)
					{
						if (BX.hasClass(items[i], 'crm-task-list-mail-item-open'))
						{
							var logId   = items[i].getAttribute('data-id');
							var details = BX.findNextSibling(items[i], {'class': 'crm-activity-email-details-'+logId});

							var prev = BX.findPreviousSibling(items[i], {'tag': 'div'});
							if (prev && BX.hasClass(prev, 'crm-task-list-mail-item-separator'))
								BX.show(prev, 'block');

							var next = BX.findNextSibling(details, {'tag': 'div'});
							if (next && BX.hasClass(next, 'crm-task-list-mail-item-separator'))
								BX.show(next, 'block');
						}
					}

					if (json.count < self.options.pageSize)
					{
						if (log == 'a')
							separator.removeChild(el);
						else
							separator.parentNode.removeChild(separator);
					}

					if (log == 'b')
						self.scrollWrapper(self.wrapper.scrollHeight);

					dummy.innerHTML = '';
				}
			}
		});
	};

	CrmActivityEmailView.prototype.toggleLogItem = function(event, logItem)
	{
		var self = this;

		if (window.getSelection)
		{
			if (window.getSelection().toString().trim() != '')
				return;
		}
		else if (document.selection)
		{
			if (document.selection.createRange().htmlText.trim() != '')
				return;
		}

		event = event || window.event;
		event.preventDefault();

		var logId   = logItem.getAttribute('data-id');
		var details = BX.findNextSibling(logItem, {'class': 'crm-activity-email-details-'+logId});

		var opened  = BX.hasClass(logItem, 'crm-task-list-mail-item-open');

		if (details)
		{
			BX.toggleClass(logItem, 'crm-task-list-mail-item-open');

			if (opened)
			{
				var prev = BX.findPreviousSibling(logItem, {'tag': 'div'});
				if (prev && BX.hasClass(prev, 'crm-task-list-mail-item-separator'))
				{
					var pprev = BX.findPreviousSibling(prev, {'tag': 'div'});
					if (pprev && BX.hasClass(pprev, 'crm-task-list-mail-item-inner') && pprev.offsetHeight == 0)
						BX.hide(prev, 'block');
				}

				var next = BX.findNextSibling(details, {'tag': 'div'});
				if (next && BX.hasClass(next, 'crm-task-list-mail-item-separator'))
				{
					var nnext = BX.findNextSibling(next, {'tag': 'div'});
					if (nnext && BX.hasClass(nnext, 'crm-task-list-mail-item') && nnext.offsetHeight > 0)
						BX.hide(next, 'block');
				}

				details.style.display = 'none';
				logItem.style.display = '';
			}
			else
			{
				var prev = BX.findPreviousSibling(logItem, {'tag': 'div'});
				if (prev && BX.hasClass(prev, 'crm-task-list-mail-item-separator'))
					BX.show(prev, 'block');

				var next = BX.findNextSibling(details, {'tag': 'div'});
				if (next && BX.hasClass(next, 'crm-task-list-mail-item-separator'))
					BX.show(next, 'block');

				details.style.display = '';

				if (details.getAttribute('data-empty'))
				{
					if (details.offsetTop+details.offsetHeight-self.wrapper.offsetTop-self.wrapper.scrollTop > self.wrapper.offsetHeight)
						self.scrollWrapper(details.offsetTop+details.offsetHeight-self.wrapper.offsetTop-self.wrapper.offsetHeight);

					BX.ajax({
						method: 'POST',
						url: self.options.ajaxUrl,
						data: {
							act: 'logitem',
							id: logId
						},
						dataType: 'json',
						onsuccess: function(json)
						{
							if (json.result != 'error')
							{
								details.style.textAlign = '';
								details.innerHTML = json.html;
								logItem.style.display = 'none';

								var button = BX.findChildByClassName(details, 'crm-task-list-mail-item-inner-header', true);
								BX.bind(button, 'click', function(event) {
									self.toggleLogItem(event, logItem);
								});

								details.removeAttribute('data-empty');

								//self.scrollWrapper(details.offsetTop-self.wrapper.offsetTop);
							}
							else
							{
								details.innerHTML = json.error;
							}
						}
					});
				}
				else
				{
					logItem.style.display = 'none';
					//self.scrollWrapper(details.offsetTop-self.wrapper.offsetTop);
				}
			}
		}
	};

	window.CrmActivityEmailView = CrmActivityEmailView;

})();
