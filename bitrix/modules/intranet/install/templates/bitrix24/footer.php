<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
\Bitrix\Main\Localization\Loc::loadMessages($_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/".SITE_TEMPLATE_ID."/footer.php");
$isCompositeMode = defined("USE_HTML_STATIC_CACHE");
$isIndexPage = $APPLICATION->GetCurPage(true) == SITE_DIR."stream/index.php";
?>
											</div>
										</div>
									</div>
								</td>
							</tr>
						</table>
<?
if ($isCompositeMode && !$isIndexPage)
{
	$dynamicArea = \Bitrix\Main\Page\FrameStatic::getCurrentDynamicArea();
	if ($dynamicArea !== null)
	{
		$dynamicArea->finishDynamicArea();
	}
}
?>
					</td>
				</tr>
				<tr>
					<td class="bx-layout-inner-left" id="layout-left-column-bottom"></td>
					<td class="bx-layout-inner-center">
						<div id="footer">
							<span id="copyright">
								<?if (IsModuleInstalled("bitrix24")):?>
									<?include_once($_SERVER["DOCUMENT_ROOT"].SITE_TEMPLATE_PATH."/languages.php");?>
									<span class="bx-lang-btn <?=LANGUAGE_ID?>" id="bx-lang-btn" onclick="B24.openLanguagePopup(this)" data-langs='{"en": "<?=$b24Languages["en"]?>", "de": "<?=$b24Languages["de"]?>", "la": "<?=$b24Languages["la"]?>", "br": "<?=$b24Languages["br"]?>", "fr": "<?=$b24Languages["fr"]?>", "pl": "<?=$b24Languages["pl"]?>", "ru": "<?=$b24Languages["ru"]?>", "ua": "<?=$b24Languages["ua"]?>", "tr": "<?=$b24Languages["tr"]?>", "tc": "<?=$b24Languages["tc"]?>", "sc": "<?=$b24Languages["sc"]?>"}'>
										<span class="bx-lang-btn-icon"></span>
									</span>
									<a id="bitrix24-logo" target="_blank" class="bitrix24-logo-<?=(LANGUAGE_ID == "ua") ? LANGUAGE_ID : \Bitrix\Main\Localization\Loc::getDefaultLang(LANGUAGE_ID)?>" href="<?=GetMessage("BITRIX24_URL")?>"></a>
								<?endif?>
								<span class="bitrix24-copyright"><?=GetMessage("BITRIX24_COPYRIGHT2", array("#CURRENT_YEAR#" => date("Y")))?></span>
								<?if (IsModuleInstalled("bitrix24") && $partnerID = COption::GetOptionString("bitrix24", "partner_id", "")):
									$arParamsPartner = array();
									$arParamsPartner["MESS"] = array(
										"BX24_PARTNER_TITLE" => GetMessage("BX24_SITE_PARTNER"),
										"BX24_CLOSE_BUTTON" => GetMessage("BX24_CLOSE_BUTTON"),
										"BX24_LOADING" => GetMessage("BX24_LOADING"),
									);
								?>
									<a href="javascript:void(0)" onclick="showPartnerForm(<?echo CUtil::PhpToJSObject($arParamsPartner)?>); return false;" class="footer-discuss-link"><?=GetMessage("BITRIX24_PARTNER_CONNECT")?></a><?
								else:?>
									<a href="javascript:void(0)" onclick="BX.Helper.show();" class="footer-discuss-link"><?=GetMessage("BITRIX24_MENU_CLOUDMAN")?></a><?
								endif?>
							</span>
						</div>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<?
$APPLICATION->ShowViewContent("im");
$APPLICATION->ShowBodyScripts();

if (defined("BX24_HOST_NAME")):?>
<script>
var _baLoaded = BX.type.isArray(_ba);
var _ba = _ba || []; _ba.push(["aid", "1682f9867b9ef36eacf05e345db46f3c"]);
(function(alreadyLoaded) {
	if (alreadyLoaded)
	{
		return;
	}
	var ba = document.createElement("script"); ba.type = "text/javascript"; ba.async = true;
	ba.src = document.location.protocol + "//bitrix.info/ba.js";
	var s = document.getElementsByTagName("script")[0];
	s.parentNode.insertBefore(ba, s);
})(_baLoaded);
</script>
<?endif;

$APPLICATION->IncludeComponent("bitrix:pull.request", "", Array(), false, Array("HIDE_ICONS" => "Y"));
$APPLICATION->IncludeComponent("bitrix:intranet.mail.check", "", array(), false, array("HIDE_ICONS" => "Y"));

$dynamicArea = new \Bitrix\Main\Page\FrameStatic("otp-info");
$dynamicArea->setAssetMode(\Bitrix\Main\Page\AssetMode::STANDARD);
$dynamicArea->startDynamicArea();
$APPLICATION->IncludeComponent("bitrix:intranet.otp.info", "", array("PATH_TO_PROFILE_SECURITY" => $profileLink."/user/#user_id#/security/",), false, array("HIDE_ICONS" => "Y"));
$dynamicArea->finishDynamicArea();
?>
<script>
	BX.message({
		"BITRIX24_CS_ONLINE" : "<?=GetMessageJS("BITRIX24_CS_ONLINE")?>",
		"BITRIX24_CS_OFFLINE" : "<?=GetMessageJS("BITRIX24_CS_OFFLINE")?>",
		"BITRIX24_CS_CONNECTING" : "<?=GetMessageJS("BITRIX24_CS_CONNECTING")?>",
		"BITRIX24_CS_RELOAD" : "<?=GetMessageJS("BITRIX24_CS_RELOAD")?>",
		"BITRIX24_SEARCHTITLE_ALL" : "<?=GetMessageJS("BITRIX24_SEARCHTITLE_ALL")?>"
	});
</script>
<script type="text/javascript">BX.onCustomEvent(window, "onScriptsLoaded");</script>
</body>
</html>
