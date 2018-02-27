<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
} ?>

<script>
	<?if ($APPLICATION->GetPageProperty("LAZY_AUTOLOAD", true) === true):?>
	document.addEventListener("deviceready", function ()
	{
		BitrixMobile.LazyLoad.showImages();
	}, false);
	<?endif?>

	<?if ($APPLICATION->GetPageProperty("LAZY_AUTOSCROLL", true) === true):?>
	document.addEventListener("DOMContentLoaded", function ()
	{
		window.addEventListener("scroll", BitrixMobile.LazyLoad.onScroll);
	}, false);
	<?endif?>


	document.addEventListener('DOMContentLoaded', function ()
	{
		BX.bindDelegate(document.body, 'click', {tagName: 'A'}, function (e)
		{
			var params = BX.MobileTools.getMobileUrlParams(this.href);
			if (params)
			{
				BXMobileApp.PageManager.loadPageBlank(params);
				return BX.PreventDefault(e);
			}
		});

		BX.bindDelegate(document.body, 'click', {tagName: 'A'}, function (e)
		{
			var newStylePagesRegexp = [
				"/mobile/users/\\?user_id=" // user profile link
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