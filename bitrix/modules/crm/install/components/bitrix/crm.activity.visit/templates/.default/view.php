<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();?>
<div class="crm-task-list-call">
	<?foreach($arResult['RECORDS'] as $k => $record):?>
		<div class="crm-task-list-call-walkman">
			<span class="crm-task-list-call-walkman-item" style="height: 24px; overflow: hidden;">
				<?
				$APPLICATION->IncludeComponent(
					"bitrix:player",
					"",
					Array(
						"PLAYER_TYPE" => "flv",
						"PROVIDER" => "video",
						"CHECK_FILE" => "N",
						"USE_PLAYLIST" => "N",
						"PATH" => $record['URL'],
						"WIDTH" => 250,
						"HEIGHT" => 24,
						"PREVIEW" => false,
						"LOGO" => false,
						"FULLSCREEN" => "N",
						"SKIN_PATH" => "/bitrix/components/bitrix/player/mediaplayer/skins",
						"SKIN" => "",
						"CONTROLBAR" => "bottom",
						"WMODE" => "transparent",
						"WMODE_WMV" => "windowless",
						"HIDE_MENU" => "N",
						"SHOW_CONTROLS" => "Y",
						"SHOW_STOP" => "Y",
						"SHOW_DIGITS" => "Y",
						"CONTROLS_BGCOLOR" => "FFFFFF",
						"CONTROLS_COLOR" => "000000",
						"CONTROLS_OVER_COLOR" => "000000",
						"SCREEN_COLOR" => "000000",
						"AUTOSTART" => "N",
						"REPEAT" => "N",
						"VOLUME" => "90",
						"DISPLAY_CLICK" => "play",
						"MUTE" => "N",
						"HIGH_QUALITY" => "N",
						"ADVANCED_MODE_SETTINGS" => "Y",
						"BUFFER_LENGTH" => "10",
						"DOWNLOAD_LINK" => false,
						"DOWNLOAD_LINK_TARGET" => "_self",
						"ALLOW_SWF" => "N",
						"ADDITIONAL_PARAMS" => array(
							'LOGO' => false,
							'NUM' => false,
							'HEIGHT_CORRECT' => false,
						),
						"PLAYER_ID" => "bitrix_vi_record_".$arResult['CALL']["ID"]."_".$k
					),
					false,
					Array("HIDE_ICONS" => "Y")
				);
				?>
			</span>
			<span class="crm-task-list-call-walkman-link-container">
				<a href="<?=$record["URL"]?>" class="crm-task-list-call-walkman-link" target="_blank">
					<?=GetMessage('CRM_ACTIVITY_VISIT_DOWNLOAD_RECORD')?>
				</a>
			</span>
		</div>
	<?endforeach?>
	<div class="crm-task-list-call-info">
		<div class="crm-task-list-call-info-container">
			<span class="crm-task-list-call-info-name">
				<?=GetMessage('CRM_ACTIVITY_VISIT_DESCRIPTION')?>:
			</span>
		</div>
		<span>
			<?=$arResult['ACTIVITY']['DESCRIPTION_HTML']?>
		</span>
	</div>
</div>