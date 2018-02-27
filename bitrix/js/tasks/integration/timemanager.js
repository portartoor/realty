BX.namespace('BX.Tasks.Integration');

/**
 * This class provides front-end integration with 'timeman' module and
 * the time management widget (currently BX.CTasksPlannerHandler) and\or allows
 * to perform start/stop task timer.
 *
 * If you intend to refactor BX.CTasksPlannerHandler, please do smth like the following:
 *
 *      BX.Tasks.PlannerHandler = BX.Tasks.Integration.TimeManager.extend(...);
 *
 * and re-use an existing code there.
 *
 */

BX.Tasks.Integration.TimeManager = BX.Tasks.Base.extend({
	options: {
		query: false
	},
	methods: {

		construct: function()
		{
			if(typeof this.vars == 'undefined')
			{
				this.vars = {};
			}
			this.vars.state = {};

			this.onPlannerUpdate = BX.debounce(this.onPlannerUpdate, 100, this);

			this.bindWidgetEvents(window != window.top ? window.top : window); // we could be in iframe
		},

		bindWidgetEvents: function(windowObj)
		{
			windowObj.BX.addCustomEvent(
				windowObj,
				'onTimeManDataRecieved',
				BX.delegate(function(data){
					this.onPlannerUpdate(data.PLANNER);
				}, this)
			);
			windowObj.BX.addCustomEvent(
				windowObj,
				'onPlannerDataRecieved',
				BX.delegate(function(obPlanner, data){
					this.onPlannerUpdate(data);
				}, this)
			);

			windowObj.BX.addCustomEvent(
				windowObj,
				'onTaskTimerChange',
				BX.delegate(function(data){
					this.onTimerUpdate(data);
				}, this)
			);
		},

		// any changes considering planner task set
		onPlannerUpdate: function(data)
		{
			data = data || {};
			data.TASKS = data.TASKS || [];
			data.TASK_ON_TIMER = data.TASK_ON_TIMER || {};

			var taskId;
			var k;
			var planCame = {};

			for(k = 0; k < data.TASKS.length; k++)
			{
				taskId = parseInt(data.TASKS[k].ID);
				if(isNaN(taskId))
				{
					continue;
				}
				planCame[taskId] = true;

				if(typeof this.vars.state[taskId] == 'undefined')
				{
					this.vars.state[taskId] = {timer: false, plan: false};
				}
				this.vars.state[taskId].plan = true;
			}

			for(k in this.vars.state)
			{
				if(typeof planCame[k] == 'undefined')
				{
					this.fireEvent('task-plan-toggle', [k, false]);
					this.vars.state[k].plan = false;
				}
			}

			taskId = parseInt(data.TASK_ON_TIMER.ID);

			if(!isNaN(taskId))
			{
				if(typeof this.vars.state[taskId] == 'undefined')
				{
					this.vars.state[taskId] = {timer: false, plan: false};
				}

				for(k in this.vars.state)
				{
					if(this.vars.state[k].timer && k != taskId)
					{
						this.fireEvent('task-timer-toggle', [false, k]);
					}

					this.vars.state[k].timer = false;
				}
				this.vars.state[taskId].timer = true;
			}
		},

		// spikes from the previous version ...
		updatePlanner: function()
		{
			var updated = true;

			// This will run onTimeManDataRecieved/onPlannerDataRecieved
			// and after it init_timer_data event
			if (window.BXTIMEMAN)
				window.BXTIMEMAN.Update(true);
			else if (window.BXPLANNER && window.BXPLANNER.update)
				window.BXPLANNER.update();
			else
				updated = false;

			if (window.top !== window)
			{
				if (window.top.BXTIMEMAN)
					window.top.BXTIMEMAN.Update(true);
				else if (window.top.BXPLANNER && window.top.BXPLANNER.update)
					window.top.BXPLANNER.update();
			}

			return (updated);
		},

		hasPlanner: function()
		{
			return !!(window.top.BXPLANNER || window.top.BXTIMEMAN);
		},

		// any changes considering task timing
		onTimerUpdate: function(data)
		{
			data = data || {};
			data.taskData = data.taskData || {};

			if(data.action == 'refresh_daemon_event') // timer tick actually
			{
				var inLog = parseInt(data.data.TASK.TIME_SPENT_IN_LOGS);
				if(isNaN(inLog))
				{
					inLog = 0;
				}
				var inTimer = parseInt(data.data.TIMER.RUN_TIME);
				if(isNaN(inTimer))
				{
					inTimer = 0;
				}

				this.fireEvent('task-timer-tick', [data.taskId, inLog + inTimer, data.data.TASK]);
			}
			else if(data.action == 'stop_timer')
			{
				data.taskData.TIMER_IS_RUNNING_FOR_CURRENT_USER = false;
				this.fireEvent('task-timer-toggle', [data.taskId, false, data.taskData]);
			}
			else if(data.action == 'start_timer')
			{
				data.taskData.TIMER_IS_RUNNING_FOR_CURRENT_USER = true;
				this.fireEvent('task-timer-toggle', [data.taskId, true, data.taskData]);
			}
		},

		addToPlan: function(taskId)
		{
			if(BX.addTaskToPlanner)
			{
				BX.addTaskToPlanner(taskId);
				return true;
			}
			else if(window.top.BX.addTaskToPlanner)
			{
				window.top.BX.addTaskToPlanner(taskId);
				return true;
			}
			return false;
		},
		start: function(taskId, sync, stopPrevious)
		{
			if(!taskId)
			{
				return;
			}

			if(sync)
			{
				this.getQuery().add('integration.timemanager.task.start', {taskId: taskId, stopPrevious: stopPrevious || false}, {}, BX.delegate(function(errors){

					var error = errors.getByCode('OTHER_TASK_ON_TIMER');

					if(error)
					{
						var data = error.data();
						var args = [taskId, []];
						if(data.TASK && data.TASK.TITLE && data.TASK.ID)
						{
							args[1] = {id: data.TASK.ID, title: data.TASK.TITLE};
						}

						this.fireEvent('other-task-on-timer', args);

						errors.deleteByCodeAll('OTHER_TASK_ON_TIMER');
					}
					else
					{
						this.fireEvent('task-timer-toggle', [taskId, true]);
						this.updatePlanner();
					}
				}, this));
			}
			else
			{
				this.fireEvent('task-timer-toggle', [taskId, true]);
				this.updatePlanner();
			}
		},
		stop: function(taskId, sync)
		{
			if(!taskId)
			{
				return;
			}

			if(sync)
			{
				this.getQuery().add('integration.timemanager.task.stop', {taskId: taskId}, {}, BX.delegate(function(){
					this.fireEvent('task-timer-toggle', [taskId, false]);
					this.updatePlanner();
				}, this));
			}
			else
			{
				this.fireEvent('task-timer-toggle', [taskId, false]);
				this.updatePlanner();
			}
		},

		getQuery: function()
		{
			if(typeof this.query == 'undefined')
			{
				if(this.option('query'))
				{
					this.query = this.option('query');
				}
				else
				{
					this.query = new BX.Tasks.Util.Query({
						autoExec: true
					});
				}
			}

			return this.query;
		}
	}
});