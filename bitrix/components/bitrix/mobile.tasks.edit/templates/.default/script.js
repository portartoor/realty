var taskEditTools = {

	initialDate: false,
	formats: [],

	setFormats: function(formats)
	{
		this.formats = formats;
	},

	setInitialDate: function(deadline)
	{
		var dCurrent = this.getDateFromArray(deadline.TO_CREATE);
		this.initialDate = BX.date.format(this.formats.internal.php, dCurrent);

		if(typeof deadline.CURRENT.year != 'undefined')
		{
			var dDeadline = this.getDateFromArray(deadline.CURRENT);
			BX('DEADLINE').innerHTML = BX.date.format(this.getFormat(dDeadline), dDeadline);
		}
		else
		{
			BX('DEADLINE').innerHTML = BX.message('MB_TASKS_TASK_EDIT_BTN_SELECT');
		}
	},

	getDateFromArray: function(arr)
	{
		return new Date(arr.year, parseInt(arr.month) - 1, arr.day, arr.hour, arr.minute);
	},

	openDatePicker: function()
	{
		var ctx = this;
		try
		{
			app.showDatePicker({
				start_date: this.initialDate,
				format: this.formats.internal.js,
				type: 'datetime',
				callback: function(value)
				{
					var d = new Date(Date.parse(value));
					var format = ctx.getFormat(d);

					BX('DEADLINE').innerHTML = BX.date.format(format, d);
					BX('DEADLINE2SAVE').value = BX.date.format(ctx.formats.submit.php, d);

					ctx.initialDate = BX.date.format(ctx.formats.internal.php, d);
				}
			});
		}
		catch(e)
		{
		}
	},

	getFormat: function(d)
	{
		var dNow = new Date();

		var currentYear = dNow.getFullYear();
		var selectedYear = d.getFullYear();

		if(currentYear == selectedYear)
			return this.formats.display.noYear;
		else
			return this.formats.display.full;
	}
}