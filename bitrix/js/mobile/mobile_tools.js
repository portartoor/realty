(function (window)
{
	if (window.BX.MobileTools) return;

	BX.MobileTools = {
		phoneTo: function (number, params)
		{
			params = typeof(params) == 'object' ? params : {};
			BXMobileApp.onCustomEvent("onPhoneTo", {number: number, params: params}, true);
		},
		callTo: function (userId, video)
		{
			video = typeof(video) == 'undefined' ? false : video;
			BXMobileApp.onCustomEvent("onCallInvite", {userId: userId, video: video}, true);
		},
		getMobileUrlParams: function (url)
		{
			var mobileRegReplace = [
				{
					exp: /\/company\/personal\/user\/(\d+)\/calendar\/\?EVENT_ID=(\d+).*/gi,
					replace: "/mobile/calendar/view_event.php?event_id=$2",
					useNewStyle: false
				},
				{
					exp: /\/company\/personal\/user\/(\d+)\/tasks\/task\/view\/(\d+)\//gi,
					replace: "/mobile/tasks/snmrouter/?routePage=view&USER_ID=$1&GROUP_ID=0&TASK_ID=$2",
					useNewStyle: true
				},
				{
					exp: /\/workgroups\/group\/(\d+)\/tasks\/task\/view\/(\d+)\//gi,
					replace: "/mobile/tasks/snmrouter/?routePage=view&GROUP_ID=$1&TASK_ID=$2",
					useNewStyle: true
				},
				{
					exp: /\/company\/personal\/user\/(\d+)\/blog\/(\d+)\/\?commentId=(\d+)#com(\d+)/gi,
					replace: "/mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=BLOG_POST&ENTITY_ID=$2&commentId=$3#com$4",
					useNewStyle: true
				},
				{
					exp: /\/company\/personal\/user\/(\d+)\/blog\/(\d+)\//gi,
					replace: "/mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=BLOG_POST&ENTITY_ID=$2",
					useNewStyle: true
				},
				{
					exp: /\/company\/personal\/log\/(\d+)\//gi,
					replace: "/mobile/log/?ACTION=CONVERT&ENTITY_TYPE_ID=LOG_ENTRY&ENTITY_ID=$1",
					useNewStyle: true
				},
				{
					exp: /\/company\/personal\/user\/(\d+)\//gi,
					replace: "/mobile/users/?user_id=$1",
					useNewStyle: true
				},
				{
					exp: /\/crm\/(deal|lead|company|contact)\/show\/(\d+)\//gi,
					replace: "/mobile/crm/$1/?page=view&$1_id=$2",
					useNewStyle: true
				}
			];

			var params = null;
			for (var i = 0; i < mobileRegReplace.length; i++)
			{
				var mobileLink = url.replace(mobileRegReplace[i].exp, mobileRegReplace[i].replace);
				if (mobileLink != url)
				{
					params = {
						url: mobileLink,
						bx24ModernStyle: mobileRegReplace[i].useNewStyle
					};
					break;
				}
			}

			return params;
		},
		createCardScanner: function (options)
		{
			return new (function scanner()
			{

				this.onError = function (e)
				{
					console.error("Error", e);
				};

				this.stripEmptyFields = options.stripEmptyFields || false;
				this.options = options;
				this.imageData = null;

				if (options["onResult"])
				{
					this.onResult = options["onResult"];
				}

				if (options["onError"])
				{
					this.onError = options["onError"];
				}
				if (options["onImageGet"])
				{
					this.onImageGet = options["onImageGet"];
				}
				this.open = function ()
				{
					app.exec("openBusinessCardScanner", {
						callback: BX.proxy(function (data)
						{

							if(data.canceled != 1 && data.url.length > 0)
							{
								this.imageData = data;

								if (this.options["onImageGet"])
								{
									this.onImageGet(data);
								}

								this.send();
							}


						}, this)
					});
				};

				this.send = function ()
				{
					if (this.options.url)
					{
						var uploadOptions = new FileUploadOptions();
						uploadOptions.fileKey = "card_file";
						uploadOptions.fileName = "image.jpg";
						uploadOptions.mimeType = "image/jpeg";
						uploadOptions.chunkedMode = false;
						uploadOptions.params = {
							image: "Y"
						};

						var ft = new FileTransfer();

						ft.upload(this.imageData.url, this.options.url, BX.proxy(function (data)
						{
							try {
								var response = JSON.parse(data.response);
								this.UNIQUE_ID = response.UNIQUE_ID;
								if (response.STATUS != "success")
								{
									if (response.ERROR)
									{
										this.onError(response.ERROR);
									}

									return;
								}
								else {
									this.options["onImageUploaded"](response);
								}

								BX.addCustomEvent("onPull-bizcard", this.handler);
							}
							catch (e)
							{
								this.onError(e);
							}
						}, this), BX.proxy(function (data)
						{
							this.onError({
								"code":data.code,
								"message":"Can't upload image"
							});

						}, this), uploadOptions);
					}

				};

				this.handler = BX.proxy(function (recognizeData)
				{
					var result = recognizeData.params.RESULT;

					if (!result.ERROR && result.UNIQUE_ID == this.UNIQUE_ID)
					{
						BX.removeCustomEvent("onPull-bizcard", this.handler);

						if (typeof this.onResult == "function")
						{
							var data = result.DATA;
							var modifiedResult = {
								DATA: {},
								CARD_ID: result.CARD_ID
							};

							if (typeof data == "object")
							{
								if (this.stripEmptyFields)
								{
									var strippedResult = {};

									for (var key in data)
									{
										if (data[key] != "")
											strippedResult[key] = data[key];
									}

									modifiedResult.DATA = strippedResult;
								}
								else {
									modifiedResult.DATA = data;
								}

								this.onResult(modifiedResult)
							}
							else
							{
								this.onError(result);
							}


						}
					}
				}, this);

			})();
		}
	};

})(window);
