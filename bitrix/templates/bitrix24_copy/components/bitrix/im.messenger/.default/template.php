<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$GLOBALS["LEFT_MENU_COUNTERS"] = is_array($arResult["COUNTERS"]) ? $arResult["COUNTERS"] : Array();
?>
<span class="header-informers-wrap" id="im-container">
	<span id="im-informer-messages" title="<?=GetMessage('IM_MESSENGER_OPEN_MESSENGER_CP');?>" class="header-informers header-informer-messages" onclick="B24.showMessagePopup(this)"></span><span onclick="B24.showNotifyPopup(this)" title="<?=GetMessage("IM_MESSENGER_OPEN_NOTIFY");?>" id="im-informer-events" class="header-informers header-informer-events"></span>
	<?if (COption::GetOptionString('bitrix24', 'network', 'N') == 'Y'):
		$networkLink = "https://www.bitrix24.net/?user_lang=".LANGUAGE_ID."&utm_source=b24&utm_medium=itop&utm_campaign=BITRIX24%2FITOP";
	?>
		<span id="b24network-informer-events" class="header-informers header-informer-network" onclick="window.open('<?=$networkLink?>','_blank');"></span>
	<?endif?>
</span>
<?if (!defined('BX_IM_FULLSCREEN')):?>
<?$this->SetViewTarget("im")?>
<div class="bx-im-bar" id="bx-im-bar">
	<div class="bx-im-informer bx-im-border-b">
		<div id="bx-im-bar-notify" class="bx-im-informer-icon" title="<?=GetMessage('IM_MESSENGER_OPEN_NOTIFY');?>">
			<div class="bx-im-informer-num"></div>
		</div>
	</div>
	<div id="bx-im-bar-search" class="bx-im-search bx-im-border-b" title="<?=GetMessage('IM_MESSENGER_OPEN_SEARCH');?>"></div>
	<div class="bx-im-users-wrap">
		<div class="bx-im-scroll-wrap" id="bx-im-external-recent-list">
			<div class="bx-im-btn-wrap bx-im-btn-wrap-no-action">
				<div class="bx-im-btn"><span class="bx-im-btn-loading"></span></div>
			</div>
		</div>
	</div>
	<div class="bx-im-bottom-block">
		<div id="bx-im-online-count-btn" class="bx-im-btn-wrap bx-im-online-count">
			<div class="bx-im-btn" id="bx-im-online-count"><?=$arResult['ONLINE_COUNT']?></div>
			<div class="bx-im-users-title"><?=GetMessage('IM_MESSENGER_ONLINE');?></div>
		</div>
		<div id="bx-im-bar-add" class="bx-im-btn-wrap bx-im-btn-add" title="<?=GetMessage('IM_MESSENGER_CREATE_CHAT');?>">
			<div class="bx-im-btn"></div>
		</div>
		<?if($arResult['PHONE_ENABLED']):?>
		<div id="bx-im-btn-call" class="bx-im-btn-wrap bx-im-btn-call" title="<?=GetMessage('IM_MESSENGER_OPEN_CALL2');?>">
			<div class="bx-im-btn"></div>
		</div>
		<?endif;?>
		<a href="<?=GetMessage("IM_MESSENGER_GO_TO_APPS_LINK")?>" target="_blank" class="bx-im-btn-wrap bx-im-btn-download" title="<?=GetMessage('IM_MESSENGER_GO_TO_APPS');?>">
			<div class="bx-im-btn"></div>
		</a>
	</div>
</div>
<script type="text/javascript">
	function bxImBarRedraw()
	{
		var scrolledY = window.pageYOffset || document.documentElement.scrollTop;
		var scrolledX = window.pageXOffset || document.documentElement.scrollLeft;
		var scrollWidth = document.documentElement.scrollWidth - document.documentElement.clientWidth;
		var barOffset = 63;

		var bar = BX('bx-im-bar');
		var panel = BX('bx-panel');
		if (panel)
		{
			barOffset = barOffset+panel.offsetHeight;
		}

		if(scrolledY <= barOffset)
		{
			bar.style.top = (barOffset - scrolledY) + 'px';
		}
		else if(scrolledY > barOffset)
		{
			if (bar.style.top != "0px")
			{
				bar.style.top = 0;
			}
		}

		if(scrollWidth > 19 && (scrollWidth - scrolledX) > 19)
		{
			if (!BX.isImBarTransparent)
			{
				BX.addClass(bar, 'bx-im-bar-transparent');
				BX.isImBarTransparent = true;
			}
		}
		else
		{
			if (BX.isImBarTransparent)
			{
				BX.removeClass(bar, 'bx-im-bar-transparent');
				BX.isImBarTransparent = false;
			}
		}
	}
	bxImBarRedraw();
</script>
<?$this->EndViewTarget()?>
<?$frame = $this->createFrame("im")->begin("");
	$arResult['EXTERNAL_RECENT_LIST'] = "bx-im-external-recent-list";
?>
<script type="text/javascript">
	<?=CIMMessenger::GetTemplateJS(Array(), $arResult)?>
</script>
<?$frame->end()?>
<?else:?>
	<script type="text/javascript">
		function bxImBarRedraw(){}
	</script>
<?endif;?>
