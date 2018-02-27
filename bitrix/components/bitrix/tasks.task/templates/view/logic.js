'use strict';

BX.namespace('Tasks.Component');

(function(){

	if (typeof BX.Tasks.Component.TaskView != 'undefined')
	{
		return;
	}

	BX.Tasks.Component.TaskView = function(parameters)
	{
		this.parameters = parameters || {};
		this.taskId = this.parameters.taskId;
		this.layout = {
			favorite: BX("task-detail-favorite"),
			switcher: BX("task-switcher"),
			switcherTabs: [],
			elapsedTime: BX("task-switcher-elapsed-time"),
			createButton: BX("task-detail-create-button"),
			importantButton: BX("task-detail-important-button")
		};

		this.messages = this.parameters.messages || {};
		for (var key in this.messages)
		{
			BX.message[key] = this.messages[key];
		}

		this.paths = this.parameters.paths || {};
		this.createButtonMenu = [];

		this.query = new BX.Tasks.Util.Query();
		this.query.bindEvent("executed", BX.proxy(this.onQueryExecuted, this));

		BX.addCustomEvent(window, "tasksTaskEvent", this.onTaskEvent.bind(this));

		this.initFavorite();
		this.initCreateButton();
		this.initSwitcher();
		this.initViewer();
		this.initAjaxErrorHandler();
		this.initImportantButton();

		this.fireTaskEvent();

		this.temporalCommentFix();
	};

	// todo: remove when forum stops calling the same page for comment.add()
	BX.Tasks.Component.TaskView.prototype.temporalCommentFix = function()
	{
		BX.addCustomEvent(window, 'OnUCFormResponse', function(id, id1, obj){
			if (BX.type.isNotEmptyString(id) && id.indexOf("TASK_") === 0 && BX.proxy_context && BX.proxy_context.jsonFailure === true)
			{
				if (obj && obj["handler"] && obj.handler["oEditor"] && obj.handler.oEditor["DenyBeforeUnloadHandler"])
				{
					obj.handler.oEditor.DenyBeforeUnloadHandler();
				}
				BX.reload();
			}
		});
	};

	BX.Tasks.Component.TaskView.prototype.fireTaskEvent = function()
	{
		if(this.parameters.eventTaskUgly != null)
		{
			BX.Tasks.Util.fireGlobalTaskEvent(this.parameters.componentData.EVENT_TYPE, {ID: this.parameters.eventTaskUgly.id}, {STAY_AT_PAGE: true}, this.parameters.eventTaskUgly);
		}
	};

	BX.Tasks.Component.TaskView.prototype.initImportantButton = function()
	{
		if(this.parameters.can.TASK.ACTION.EDIT)
		{
			BX.bind(this.layout.importantButton, "click", BX.Tasks.passCtx(this.onImportantButtonClick, this));
		}
	};

	BX.Tasks.Component.TaskView.prototype.initCreateButton = function()
	{
		BX.bind(this.layout.createButton, "click", this.onCreateButtonClick.bind(this));

		this.createButtonMenu = [
			{
				text : this.messages.addTask,
				className : "menu-popup-item-create",
				href: this.paths.newTask
			},
			{
				text : this.messages.addSubTask,
				className : "menu-popup-item-create",
				href: this.paths.newSubTask
			}
		];
	};

	BX.Tasks.Component.TaskView.prototype.onImportantButtonClick = function(node)
	{
		var priority = BX.data(node, 'priority');
		var newPriority = priority == 2 ? 1 : 2;

		this.query.run('task.update', {id: this.parameters.taskId, data: {
			PRIORITY: newPriority
		}}).then(function(result){
			if(result.isSuccess())
			{
				BX.data(node, 'priority', newPriority);
				BX.toggleClass(node, 'no');
			}
		}.bind(this));
		this.query.execute();
	};

	BX.Tasks.Component.TaskView.prototype.onCreateButtonClick = function()
	{
		BX.PopupMenu.show(
			"task-detail-create-button",
			this.layout.createButton,
			this.createButtonMenu,
			{
			}
		);
	};

	BX.Tasks.Component.TaskView.prototype.onTaskEvent = function(type, parameters)
	{
		parameters = parameters || {};
		var data = parameters.task || {};

		if(type == 'UPDATE' && data.ID == this.parameters.taskId)
		{
			if(BX.type.isNotEmptyString(data.REAL_STATUS))
			{
				this.setStatus(data.REAL_STATUS);
			}
		}
	};

	BX.Tasks.Component.TaskView.prototype.setStatus = function(status)
	{
		var statusContainer = BX("task-detail-status-below-name");
		if(statusContainer)
		{
			var statusName = BX.message("TASKS_STATUS_" + status);
			statusContainer.innerHTML = statusName.substr(0, 1).toLowerCase()+statusName.substr(1);
		}
	};

	BX.Tasks.Component.TaskView.prototype.initFavorite = function()
	{
		BX.bind(this.layout.favorite, "click", BX.proxy(this.onFavoriteClick, this));
	};

	BX.Tasks.Component.TaskView.prototype.onFavoriteClick = function()
	{
		var action = BX.hasClass(this.layout.favorite, "task-detail-favorite-active") ? "task.favorite.delete" : "task.favorite.add";

		this.query.deleteAll();
		this.query.add(
			action,
			{
				taskId: this.taskId
			},
			{
				code: action
			}
		);

		this.query.execute();

		BX.toggleClass(this.layout.favorite, "task-detail-favorite-active");
	};

	BX.Tasks.Component.TaskView.prototype.initSwitcher = function()
	{
		if (!this.layout.switcher)
		{
			return;
		}

		var tabs = this.layout.switcher.getElementsByClassName("task-switcher");
		var blocks = this.layout.switcher.parentNode.getElementsByClassName("task-switcher-block");
		for (var i = 0; i < tabs.length; i++)
		{
			var tab = tabs[i];
			var block = blocks[i];
			BX.bind(tab, "click", BX.proxy(this.onSwitch, this));
			this.layout.switcherTabs.push({
				title: tab,
				block: block
			});
		}

		BX.addCustomEvent("TaskElapsedTimeUpdated", BX.proxy(function(a, b, c, totalTime) {
			this.layout.elapsedTime.innerText = BX.Tasks.Util.formatTimeAmount(totalTime.time);
		}, this));
	};

	BX.Tasks.Component.TaskView.prototype.onSwitch = function()
	{
		var currentTitle = BX.proxy_context;
		if (BX.hasClass(currentTitle, "task-switcher-selected"))
		{
			return false;
		}

		for (var i = 0; i < this.layout.switcherTabs.length; i++)
		{
			var title = this.layout.switcherTabs[i].title;
			var block = this.layout.switcherTabs[i].block;

			if (title === currentTitle)
			{
				BX.addClass(title, "task-switcher-selected");
				BX.addClass(block, "task-switcher-block-selected");
			}
			else
			{
				BX.removeClass(title, "task-switcher-selected");
				BX.removeClass(block, "task-switcher-block-selected");
			}
		}

		return false;
	};

	BX.Tasks.Component.TaskView.prototype.onQueryExecuted = function(response)
	{
	};

	BX.Tasks.Component.TaskView.prototype.initViewer = function()
	{
		var fileAreas = ["task-detail-description", "task-detail-files", "task-comments-block", "task-files-block"];

		for (var i = 0; i < fileAreas.length; i++)
		{
			var area = BX(fileAreas[i]);
			if (area)
			{
				top.BX.viewElementBind(
					area,
					{},
					function(node){
						return BX.type.isElementNode(node) &&
							(node.getAttribute("data-bx-viewer") || node.getAttribute("data-bx-image"));
					}
				);
			}
		}
	};

	BX.Tasks.Component.TaskView.prototype.initAjaxErrorHandler = function()
	{
		BX.addCustomEvent("TaskAjaxError", function(errors) {
			BX.Tasks.alert(errors).then(function(){
				BX.reload();
			});
		});
	};

}).call(this);