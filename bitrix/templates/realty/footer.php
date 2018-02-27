</div>
<?
	if ($APPLICATION->GetCurPage(false) === '/realty/') {
		if (CModule::IncludeModule("im") && CBXFeatures::IsFeatureEnabled('WebMessenger'))
		{
			$APPLICATION->IncludeComponent("westpower:im.messenger", "", Array(
				"RECENT" => "Y",
				"PATH_TO_SONET_EXTMAIL" => SITE_DIR."company/personal/mail/"
			), false, Array("HIDE_ICONS" => "Y"));
		}
	}
?>
<script>
	document.addEventListener('DOMContentLoaded', function ()
	{
		BX.bindDelegate(document.body, 'click', {tagName: 'A'}, function (e)
		{
			var mobileRegReplace = [
				{
					exp: /\/company\/personal\/user\/(\d+)\/tasks\/task\/view\/(\d+)\//gi,
					replace: "/mobile/tasks/snmrouter/?routePage=view&USER_ID=$1&GROUP_ID=0&TASK_ID=$2",
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
				}
			];

			var str = this.href;

			for (var i = 0; i < mobileRegReplace.length; i++)
			{
				var mobileLink = str.replace(mobileRegReplace[i].exp, mobileRegReplace[i].replace);

				if (mobileLink != this.href)
				{
					BXMobileApp.PageManager.loadPageBlank({
						url: mobileLink,
						bx24ModernStyle: mobileRegReplace[i].useNewStyle
					});
					return BX.PreventDefault(e);
				}
			}

		});

		BX.bindDelegate(document.body, 'click', {tagName: 'A'}, function (e)
		{
			var newStylePagesRegexp = [
				"/mobile/users/\\?user_id=", // user profile link
				"/relaty/"
			];


			for (var i = 0; i < newStylePagesRegexp.length; i++)
			{
				var urlRegexp = new RegExp(newStylePagesRegexp[i], 'ig');
				var resArray = urlRegexp.exec(this.href);
				if (resArray != null)
				{

					BXMobileApp.PageManager.loadPageBlank({
						url: this.href,
						bx24ModernStyle: true
					});

					return BX.PreventDefault(e);
				}

			}


		});

	}, false);


</script>
</body>
</html>
<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>