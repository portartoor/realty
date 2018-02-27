<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
//$APPLICATION->SetPageProperty("BodyClass", "newpost-page");
$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH."/components/bitrix/main.post.form/mobile/script_attached.js");
$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH."/log_mobile.js");
if (
	is_array($_SESSION["MFU_UPLOADED_FILES_".$GLOBALS["USER"]->GetId().($post_id ? "_".$post_id : "")]) 
	&& count($_SESSION["MFU_UPLOADED_FILES_".$GLOBALS["USER"]->GetId().($post_id ? "_".$post_id : "")]) > 0
)
{
	$iFiles = count($_SESSION["MFU_UPLOADED_FILES_".$GLOBALS["USER"]->GetId().($post_id ? "_".$post_id : "")]);
}
elseif (
	is_array($_SESSION["MFU_UPLOADED_DOCS_".$GLOBALS["USER"]->GetId().($post_id ? "_".$post_id : "")]) 
	&& count($_SESSION["MFU_UPLOADED_DOCS_".$GLOBALS["USER"]->GetId().($post_id ? "_".$post_id : "")]) > 0
)
{
	$iDocs = count($_SESSION["MFU_UPLOADED_DOCS_".$GLOBALS["USER"]->GetId().($post_id ? "_".$post_id : "")]);
}

if (
	is_array($_SESSION["MFU_UPLOADED_IMAGES_".$GLOBALS["USER"]->GetId().($post_id ? "_".$post_id : "")]) 
	&& count($_SESSION["MFU_UPLOADED_IMAGES_".$GLOBALS["USER"]->GetId().($post_id ? "_".$post_id : "")]) > 0
)
{
	$iFiles += count($_SESSION["MFU_UPLOADED_IMAGES_".$GLOBALS["USER"]->GetId().($post_id ? "_".$post_id : "")]);
}

$bFilesUploaded = ($iFiles || $iDocs);
?>
	<input type="hidden" name="newpost_photo_counter" id="newpost_photo_counter" value="<?
		?><?=($bFilesUploaded ? ($iFiles ? $iFiles : $iDocs) : 0)?><?
	?>" />
	<script type="text/javascript">

		BX.bind(BX('feed-add-post-image'), 'click', function(e)
		{
			if (app.enableInVersion(10))
			{
				var action = new BXMobileApp.UI.ActionSheet({
					buttons: [
						{
							title: '<?=GetMessageJS("MPF_PHOTO_CAMERA")?>',
							callback: function()
							{
								oMPF.takePhoto({type: 'camera'});
							}
						},
						{
							title: '<?=GetMessageJS("MPF_PHOTO_GALLERY")?>',
							callback: function()
							{
								oMPF.takePhoto({type: 'gallery'});
							}
						}
					]
					},
					"imageSheet"
				);
				action.show();
			}
			else
			{
				oMPF.takePhoto({type: 'gallery'});
			}
		});

		BX.addCustomEvent('onAfterMFLDeleteFile', __MPFonAfterMFLDeleteFile);
		BX.addCustomEvent('onAfterMFLDeleteElement', __MPFonAfterMFLDeleteElement);
	</script>
<div id="newpost_progressbar_cont" class="newpost-progress" style="display: none;"><?
	?><div id="newpost_progressbar_label" class="newpost-progress-label"></div><?
	?><div id="newpost_progressbar_ind" class="newpost-progress-indicator"></div><?
?></div><?
?><div onclick="app.loadPageBlank({url: '<?=SITE_DIR?>mobile/log/new_post_images.php<?=(isset($arResult["Post"]) && isset($arResult["Post"]["ID"]) && intval($arResult["Post"]["ID"]) > 0 ? "?post_id=".intval($arResult["Post"]["ID"]) : "")?>', cache: false });" style="display: <?=($bFilesUploaded ? "block" : "none")?>;" class="newpost-info newpost-grey-button" id="newpost_photo_counter_title" ontouchstart="BX.toggleClass(this, 'newpost-info-pressed');" ontouchend="BX.toggleClass(this, 'newpost-info-pressed');"><?
	?><span><?=($bFilesUploaded ? ($iFiles ? $iFiles : $iDocs) : 0)?></span><?
	?><span>&nbsp;<?=GetMessage("MPF_PHOTO")?></span><?
?></div>