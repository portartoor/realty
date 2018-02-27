<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}


/**
 * Bitrix vars
 *
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 * @var CBitrixComponentTemplate $this
 * @global CMain $APPLICATION
 * @global CUser $USER
 */

if($arResult['APP_STATUS']['PAYMENT_NOTIFY'] == 'Y')
{
	if($arResult['IS_ADMIN'])
	{
		$arResult['APP_STATUS']['MESSAGE_SUFFIX'] .= '_A';
	}

	if(isset($arResult['APP_STATUS']['MESSAGE_REPLACE']['#DAYS#']))
	{
		$arResult['APP_STATUS']['MESSAGE_REPLACE']['#DAYS#']++;
	}
?>
<div class="app-update-avail"><?=GetMessage('PAYMENT_MESSAGE'.$arResult['APP_STATUS']['MESSAGE_SUFFIX'], $arResult['APP_STATUS']['MESSAGE_REPLACE'])?></div>
<?
	if($arResult['APP_STATUS']['PAYMENT_ALLOW'] == 'N')
	{
		return;
	}
}

if($arResult['APP_NEED_REINSTALL'])
{
?>
	<div class="app-update-avail"><?=$arResult['IS_ADMIN'] ? GetMessage('REST_ALT_NEED_REINSTALL_ADMIN', array('#DETAIL_URL#' => $arResult['DETAIL_URL'])) : GetMessage('REST_ALT_NEED_REINSTALL');?></div>
<?
}

if(array_key_exists('UPDATE_VERSION', $arResult)):
?>
<div id="update_note" class="app-update-avail"><?=$arResult['IS_ADMIN'] ? GetMessage('REST_ALT_UPDATE_AVAIL_ADMIN', array('#DETAIL_URL#' => $arResult['DETAIL_URL'])) : GetMessage('REST_ALT_UPDATE_AVAIL');?><span class="app-update-close" onclick="BX.rest.AppLayout.get('<?=$arResult['APP_SID']?>').hideUpdate('<?=$arResult['UPDATE_VERSION']?>',function(){BX.hide(BX('update_note'))});"></span></div>
<?
endif;

$frameName = $arResult['CURRENT_HOST'].'|'.($arResult['CURRENT_HOST_SECURE']?1:0).'|'.$arResult['APP_SID'];

$url = $arResult['APP_URL'];
$url .= (strpos($url, '?') === false ? '?' : '&');
/*
?>
<a href="javascript:void(0)" onclick="BX.rest.AppLayout.get('<?=$arResult['APP_SID']?>').reInstall();">Reinstall app</a>
*/

$frameStyle = array();

if(is_array($arParams['PARAM']))
{
	if(isset($arParams['PARAM']['FRAME_HEIGHT']))
	{
		$frameStyle[] = 'height:' . $arParams['PARAM']['FRAME_HEIGHT'];
	}

	if(isset($arParams['PARAM']['FRAME_WIDTH']))
	{
		$frameStyle[] = 'width:' . $arParams['PARAM']['FRAME_WIDTH'];
	}
}
elseif($arParams['MOBILE'] == 'Y')
{
	$frameStyle[] = 'width: 100%';
	//$frameStyle[] = 'height: 100%';
}
elseif($arParams['POPUP'])
{
	$frameStyle[] = 'height: 100%';
}
?>

<form name="appform_<?=$arResult['APP_SID']?>" action="<?=htmlspecialcharsbx($url)?>DOMAIN=<?=$arResult['CURRENT_HOST']?>&amp;PROTOCOL=<?=$arResult['CURRENT_HOST_SECURE']?1:0?>&amp;LANG=<?=LANGUAGE_ID?>&amp;APP_SID=<?=$arResult['APP_SID']?>" method="POST" target="<?=htmlspecialcharsbx($frameName)?>">
	<input type="hidden" name="AUTH_ID" value="<?=$arResult['AUTH']['access_token']?>" />
	<input type="hidden" name="AUTH_EXPIRES" value="<?=$arResult['AUTH']['expires_in']?>" />
	<input type="hidden" name="REFRESH_ID" value="<?=$arResult['AUTH']['refresh_token']?>" />
	<input type="hidden" name="member_id" value="<?=htmlspecialcharsbx($arResult['MEMBER_ID'])?>">
	<input type="hidden" name="status" value="<?=htmlspecialcharsbx($arResult['APP_STATUS']['STATUS'])?>">
	<input type="hidden" name="PLACEMENT" value="<?=htmlspecialcharsbx($arParams["PLACEMENT"])?>">
<?
if($arParams['PLACEMENT_OPTIONS']):
?>
	<input type="hidden" name="PLACEMENT_OPTIONS" value="<?=htmlspecialcharsbx(\Bitrix\Main\Web\Json::encode($arParams['PLACEMENT_OPTIONS']))?>">
<?
endif;
?>
</form>
<div id="appframe_layout_<?=$arResult['APP_SID']?>" <? if(!empty($frameStyle)) echo ' style="'.implode(';', $frameStyle).'"' ?> class="app-frame-layout">
	<iframe id="appframe_<?=$arResult['APP_SID']?>" name="<?=htmlspecialcharsbx($frameName)?>" frameborder="0" class="app-frame app-loading" style="height: 100%; width: 100%;"></iframe>
	<div id="appframe_loading_<?=$arResult['APP_SID']?>" class="app-loading-msg">
		<?=GetMessage('REST_LOADING', array('#APP_NAME#' =>  htmlspecialcharsbx($arResult['APP_NAME'])))?>
	</div>
</div>
<script type="text/javascript">
BX.rest.AppLayout.set(
	'<?=\CUtil::JSEscape($arParams['PLACEMENT'])?>',
	'<?=$arResult['APP_SID']?>',
	{
		<?=$arResult['FIRST_RUN'] ? 'firstRun:true,':''?>

		formName: 'appform_<?=$arResult['APP_SID']?>',
		frameName: 'appframe_<?=$arResult['APP_SID']?>',
		loaderName: 'appframe_loading_<?=$arResult['APP_SID']?>',
		layoutName: 'appframe_layout_<?=$arResult['APP_SID']?>',
		ajaxUrl: '<?=\CUtil::JSEscape($APPLICATION->GetCurPageParam('', array_merge(array(
			'action', 'control', 'install_finished',
		), \Bitrix\Main\HttpRequest::getSystemParameters())))?>',
		controlUrl: '/bitrix/tools/rest_control.php',
		appHost: '<?=$arResult['APP_HOST']?>',
		appProto: '<?=$arResult['APP_PROTO']?>',
		proto: <?=$arResult['CURRENT_HOST_SECURE']?1:0?>,
		restPath: '<?=$arResult['REST_PATH']?>',
		id: '<?=$arResult['ID']?>',
		appId: '<?=$arResult['APP_ID']?>',
		appV: '<?=$arResult['APP_VERSION']?>',
		appI: <?=$arResult['INSTALL'] ? 'true' : 'false'?>,
		memberId: '<?=$arResult['MEMBER_ID']?>',
		authId: '<?=$arResult['AUTH']['access_token']?>',
		authExpires: '<?=$arResult['AUTH']['expires_in']?>',
		refreshId: '<?=$arResult['AUTH']['refresh_token']?>',
		isAdmin: <?=$arResult['IS_ADMIN'] ? 'true' : 'false'?>,
		staticHtml: <?=$arResult['APP_STATIC'] ? 'true' : 'false'?>,
		appOptions: <?=\CUtil::PhpToJsObject($arResult['APP_OPTIONS'])?>,
		userOptions: <?=\CUtil::PhpToJsObject($arResult['USER_OPTIONS'])?>,
		placementOptions: <?=\CUtil::PhpToJsObject($arParams['PLACEMENT_OPTIONS'])?>

	}
);
<?
if($arParams['POPUP']):
?>
BX.rest.AppLayout.get('<?=$arResult['APP_SID']?>').denyInterface(['openApplication', 'resizeWindow', 'setScroll', 'reloadWindow']);
<?
	if($arParams['PARENT_SID']):
?>
BX.rest.AppLayout.get('<?=$arResult['APP_SID']?>').expandPopup = BX.rest.AppLayout.get('<?=\CUtil::JSEscape($arParams['PARENT_SID'])?>').expandPopup;
BX.rest.AppLayout.get('<?=$arResult['APP_SID']?>').expandPopup.setTitleBar('<?=\CUtil::JSEscape($arResult['POPUP_TITLE'])?>');
<?
	endif;
elseif($arParams['PLACEMENT'] !== \Bitrix\Rest\PlacementTable::PLACEMENT_DEFAULT):
?>
BX.rest.AppLayout.get('<?=$arResult['APP_SID']?>').denyInterface(['setTitle', 'resizeWindow', 'setScroll', 'reloadWindow']);
<?
elseif($arParams['MOBILE'] == 'Y'):
?>
//BX.rest.AppLayout.get('<?=$arResult['APP_SID']?>').denyInterface(['resizeWindow']);
<?
else:
?>
//BX.rest.AppLayout.get('<?=$arResult['APP_SID']?>').denyInterface(['openApplication', 'closeApplication']);
<?
endif;
?>

<?
if($arParams['INITIALIZE'] !== 'N'):
?>
BX.ready(function(){
	BX.rest.AppLayout.initialize('<?=\CUtil::JSEscape($arParams['PLACEMENT'])?>', '<?=$arResult['APP_SID']?>');
});
<?
endif;
?>
</script>