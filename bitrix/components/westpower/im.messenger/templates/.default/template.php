<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<style>
	#bx-notifier-panel {
		top:0px !important; 
		right:0px !important; 
		left: auto !important;
		height: 27px !important;
		width: 63px !important;
	}
	#bx-messenger-popup-messenger {
		top:50px !important;
		left: 11px !important;
		right: 11px !important;
	}
	.bx-messenger-box-contact {
		width:100% !important;
		position:relative !important;
	}
	.bx-messenger-box {
		width:100% !important;
		margin:0 !important;
	}
	.bx-messenger-box-dialog{
		margin-left: 0px !important;
	}
	.bx-messenger-box-extra {
		margin-left: 0px !important;
	}
	.bx-messenger-cl {
		height:auto !important;
	}
	/*.bx-messenger-panel {
		height:auto;
	}*/
	a.bx-messenger-panel-avatar {
		margin-top: 66px !important;
	}
	#bx-messenger-popup-settings {
		top:50px !important;
		left: 11px !important;
		right: 11px !important;
	}
	.bx-messenger-settings {
		width:auto;
	}
	.bx-messenger-recent-wrap .bx-messenger-cl-user-desc {
		max-width:300px;
	}
	.bx-notifier-drag {
		display:none !important;
	}
	.bx-notifier-indicator {
		display:block;
	}
	.bx-notifyManager-animation {
		left:0 !important;
		right:0 !important;
	}
	.im-desktop-popup .bx-notifier-item{
		width:100% !important;
	}
	table.popup-window {
		width:100% !important;
	}
	.bx-messenger-panel-title {
		overflow:visible;
	}
	.bx-messenger-panel-title-chat {
		padding-top: 3px !important;
	}
	.bx-messenger-textarea-cntr-enter {
		display:none;
	}
	.bx-notifier-panel .bx-notifier-panel-center {
	    display: inline-block;
	    height: 25px;
	    width: 30px;
	    vertical-align: top;
	    padding-top: 2px;
	}
	.bx-notifier-indicators {
	    width: 108px;
    	height: 25px;
    	display: block;
	}
	.bx-notifier-message, .bx-notifier-notify {
		float: left;
		isplay: block;
	    margin-right: 0;
    	padding-right: 0;
    	margin-left: 2px;
	}
</style>
<div id="bx-notifier-panel" class="bx-notifier-panel">
	<span class="bx-notifier-panel-left"></span><span class="bx-notifier-panel-center"><span class="bx-notifier-drag">
	</span><span class="bx-notifier-indicators"><a href="javascript:void(0)" class="bx-notifier-indicator bx-notifier-call" title="<?=GetMessage('IM_MESSENGER_OPEN_CALL')?>"><span class="bx-notifier-indicator-text"></span><span class="bx-notifier-indicator-icon"></span><span class="bx-notifier-indicator-count"></span>
		</a><a href="javascript:scroll_to_0()" class="bx-notifier-indicator bx-notifier-message" title="<?=GetMessage('IM_MESSENGER_OPEN_MESSENGER_2');?>"><span class="bx-notifier-indicator-text"></span><span class="bx-notifier-indicator-icon"></span><span class="bx-notifier-indicator-count"></span>
		</a><a href="javascript:scroll_to_0()" class="bx-notifier-indicator bx-notifier-notify" title="<?=GetMessage('IM_MESSENGER_OPEN_NOTIFY');?>"><span class="bx-notifier-indicator-text"></span><span class="bx-notifier-indicator-icon"></span><span class="bx-notifier-indicator-count"></span>
		</a></span>
	</span><span class="bx-notifier-panel-right"></span>
</div>

<script type="text/javascript">
<?=CIMMessenger::GetTemplateJS(Array(), $arResult)?>
</script>