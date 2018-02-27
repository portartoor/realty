<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>
<div class="video-call">
<?
if ($arResult["NEED_AUTH"] == "Y")
{
	$APPLICATION->AuthForm(GetMessage("VCCT_NEED_AUTH"));
}
elseif (strlen($arResult["FatalError"])>0)
{
	?>
	<div class="video-call-warning">
	<span class='errortext'><?= $arResult["FatalError"] ?></span>
	</div>
	<?
}
else
{
	?>
	<div class="video-call-warning">
	<span class='errortext'><?= GetMessage("VCCT_VIDEOCALL_ERROR") ?></span>
	</div>
	<?
}
?>
</div>