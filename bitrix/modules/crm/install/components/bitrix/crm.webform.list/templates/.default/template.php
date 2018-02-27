<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Web\Json;
use Bitrix\Main\Localization\Loc;

/** @var CBitrixComponentTemplate $this */
/** @var array $arParams */
/** @var array $arResult */

CJSCore::Init(array('clipboard'));
if(\Bitrix\Main\Loader::includeModule("bitrix24"))
{
	CBitrix24::initLicenseInfoPopupJS();
}
?>

<div class="crm-webform-list-wrapper">

	<?if(!$arResult['HIDE_DESC']):?>
	<div id="CRM_LIST_DESC_CONT" class="crm-webform-list-info">
		<h2 class="crm-webform-list-info-title"><?=Loc::getMessage('CRM_WEBFORM_LIST_INFO_TITLE')?></h2>
		<div class="crm-webform-list-info-visual">
			<span class="crm-webform-list-info-visual-item" style="height: 225px;">
				<img src="<?=$this->GetFolder()?>/images/demo_main_<?=(in_array(LANGUAGE_ID, array('ru', 'ua', 'kz', 'by')) ? 'ru' : 'en')?>.png">
			</span>
		</div>
		<div class="crm-webform-list-info-inner">
			<div class="crm-webform-list-info-list-container">
				<ul class="crm-webform-list-info-list">
					<li class="crm-webform-list-info-list-item">
						<?=Loc::getMessage('CRM_WEBFORM_LIST_DESC1')?>
					</li>
					<li class="crm-webform-list-info-list-item">
						<?=Loc::getMessage('CRM_WEBFORM_LIST_DESC2')?>
					</li>
				</ul>
			</div>
		</div><!--crm-webform-list-info-inner-->
		<span id="CRM_LIST_DESC_BTN_HIDE" class="crm-webform-list-info-btn-hide" title="<?=Loc::getMessage('CRM_WEBFORM_LIST_HIDE_DESC')?>"></span>
	</div>
	<?endif;?>

	<div id="crm_web_form_list_container">

		<?if(empty($arResult['ITEMS_BY_IS_SYSTEM']) && $arResult['PERM_CAN_EDIT']):?>
			<a href="<?=htmlspecialcharsbx($arResult['PATH_TO_BUTTON_NEW'])?>">
				<div class="crm-webform-list-createform-container">
					<div class="crm-webform-list-createform-element"><?=Loc::getMessage('CRM_WEBFORM_LIST_ADD_CAPTION')?></div>
					<span class="crm-webform-list-createform-description"><?=Loc::getMessage('CRM_WEBFORM_LIST_ADD_DESC1')?></span>
				</div>
			</a>
		<?endif;?>

<?foreach($arResult['ITEMS_BY_IS_SYSTEM'] as $isSystem => $system):?>
	<div class="crm-webform-list-header-container">
		<h3 data-bx-list-head="" class="crm-webform-list-header">
			<?=htmlspecialcharsbx($system['NAME'] . ($arResult['FILTER_ACTIVE_CURRENT'] != 'ALL' ? ': ' . $arResult['FILTER_ACTIVE_CURRENT_NAME'] : ''))?>
		</h3>
	</div>
	<div data-bx-list-items="">
<?foreach($system['ITEMS'] as $item):?>
	<div class="crm-webform-list-widget-row"
		data-bx-crm-webform-item="<?=intval($item['ID'])?>"
		data-bx-crm-webform-item-is-system="<?=$isSystem?>"
	>
		<div class="crm-webform-list-buttons-container">
			<div class="crm-webform-list-buttons">
				<span class="crm-webform-list-hamburger" data-bx-crm-webform-item-settings=""></span>
				<?if($arResult['PERM_CAN_EDIT'] && $item['IS_SYSTEM'] != 'Y'):?>
					<span class="crm-webform-list-close" data-bx-crm-webform-item-delete="" title="<?=Loc::getMessage('CRM_WEBFORM_LIST_ACTIONS_REMOVE')?>"></span>
				<?endif;?>
			</div><!--crm-webform-list-buttons-->
		</div><!--crm-webform-list-button-container-->
		<div class="crm-webform-list-widget-container crm-webform-list-widget-left">
			<div class="crm-webform-list-widget crm-webform-list-widget-number <?=$item['viewClassName']?> <?=($item['ACTIVE'] == 'Y' ? '' : 'crm-webform-list-widget-inactive')?>" data-bx-crm-webform-item-view="">
				<div class="crm-webform-list-widget-head">
					<span class="crm-widget-settings" data-bx-crm-webform-item-view-settings=""></span>
					<span class="crm-webform-list-widget-title-container">
						<span class="crm-webform-list-widget-title-inner">
							<a data-bx-slider-opener="" data-bx-edit-link="" href="<?=htmlspecialcharsbx($item['PATH_TO_WEB_FORM_EDIT'])?>">
								<span data-bx-title="" class="crm-webform-list-widget-title"><?=htmlspecialcharsbx($item['NAME'])?></span>
							</a>
						</span>
					</span>
				</div><!--crm-webform-list-widget-head-->
				<div class="crm-webform-list-widget-content">
					<div class="crm-webform-list-widget-content-amt">
						<span class="crm-webform-list-widget-content-image"></span>
						<div style="position: relative;left: 118px;">
							<?foreach($item['itemViewList'] as $viewType => $view):?>
							<div class="crm-webform-list-widget-content-item <?=($view['SELECTED'] ? 'crm-webform-list-widget-content-item-show' : '')?>" data-bx-crm-webform-view-info="<?=$viewType?>">
								<div class="crm-crm-webform-list-widget-content-title"><?=$view['TEXT']?></div>
								<div class="crm-webform-list-widget-content-number">
									<?=$item['SUMMARY_' . $viewType . '_DISPLAY']?>
								</div>
							</div>
							<?endforeach;?>
						</div>
						<div class="crm-webform-list-widget-content-attempt" style="top: 67px; position: relative;">
							<span class="crm-webform-list-widget-content-attempt-total">
								<span class="crm-webform-list-widget-content-attempt-total-element"><?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_FILL_START')?></span>
								<div class="crm-webform-list-widget-content-attempt-total-number">
									<?=intval($item['COUNT_START_FILL'])?>
								</div>
							</span>
							<span class="crm-webform-list-widget-content-attempt-success">
								<span class="crm-webform-list-widget-content-attempt-success-element"><?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_FILL_END')?></span>
								<div class="crm-webform-list-widget-content-attempt-success-number">
									<?=intval($item['COUNT_END_FILL'])?>
								</div>
							</span>
						</div>
					</div>
				</div><!--crm-webform-list-widget-content-->
			</div><!--crm-webform-list-widget crm-webform-list-widget-number-->
		</div><!--crm-webform-list-widget-container crm-webform-list-widget-left-->
		<div class="crm-webform-list-widget-container crm-webform-list-widget-right">

			<div class="crm-webform-list-inner-info-container">
				<div class="crm-webform-list-creation-date-container">
					<div class="crm-webform-list-creation-date-element">
						<span class="crm-webform-list-text"><?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_DATE_CREATE')?>:</span>
						<span class="crm-webform-list-date"><?=htmlspecialcharsbx($item['DATE_CREATE_DISPLAY'])?></span>
					</div>
				</div><!--crm-webform-list-creation-date-container-->
				<div class="crm-webform-list-active-info-container">
					<div data-bx-crm-webform-item-active-date="" class="crm-webform-list-active-info">
						<div class="crm-webform-list-active-info-def">
							<span class="crm-webform-list-text">
								<?=($item['ACTIVE'] == 'Y' ? Loc::getMessage('CRM_WEBFORM_LIST_ITEM_ACTIVE_ACTIVATED') : Loc::getMessage('CRM_WEBFORM_LIST_ITEM_ACTIVE_DEACTIVATED'))?>
								<?=$item['DATE_CREATE_DISPLAY_DATE']?> <?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_ACTIVE_ACT_ON')?> <?=$item['DATE_CREATE_DISPLAY_TIME']?>:
							</span>
							<span class="crm-webform-list-date">
								<?
								if($item['ACTIVE_CHANGE_BY_DISPLAY']['ICON'])
								{
									$userIconStyle = 'background-image: url(\'' . htmlspecialcharsbx($item['ACTIVE_CHANGE_BY_DISPLAY']['ICON']) .'\');';
									$userIconClass = '';
								}
								else
								{
									$userIconStyle = '';
									$userIconClass = 'user-default-icon';
								}
								?>
								<span class="crm-webform-list-activate-user-icon <?=$userIconClass?>" style="<?=$userIconStyle?>"></span>
								<span class="crm-webform-list-activate-user-inner">
									<a href="<?=htmlspecialcharsbx($item['ACTIVE_CHANGE_BY_DISPLAY']['LINK'])?>" class="crm-webform-list-activate-user-element">
										<?=htmlspecialcharsbx($item['ACTIVE_CHANGE_BY_DISPLAY']['NAME'])?>
									</a>
								</span>
							</span>
						</div>
						<div class="crm-webform-list-active-info-now">
							<span class="crm-webform-list-text">
								<span class="crm-webform-list-activate-comments-act"><?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_ACTIVE_ON_NOW')?>:</span>
								<span class="crm-webform-list-activate-comments-deact"><?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_ACTIVE_OFF_NOW')?>:</span>
							</span>
							<span class="crm-webform-list-date">
								<?
								if($item['ACTIVE_CHANGE_BY_NOW_DISPLAY']['ICON'])
								{
									$userIconStyle = 'background-image: url(\'' . htmlspecialcharsbx($item['ACTIVE_CHANGE_BY_NOW_DISPLAY']['ICON']) .'\');';
									$userIconClass = '';
								}
								else
								{
									$userIconStyle = '';
									$userIconClass = 'user-default-icon';
								}
								?>
								<span class="crm-webform-list-activate-user-icon <?=$userIconClass?>" style="<?=$userIconStyle?>"></span>
								<span class="crm-webform-list-activate-user-inner">
									<a href="<?=htmlspecialcharsbx($item['ACTIVE_CHANGE_BY_NOW_DISPLAY']['LINK'])?>" class="crm-webform-list-activate-user-element">
										<?=htmlspecialcharsbx($item['ACTIVE_CHANGE_BY_NOW_DISPLAY']['NAME'])?>
									</a>
								</span>
							</span>
						</div>
					</div>
				</div><!--crm-webform-list-creation-date-container-->
				<div class="crm-webform-list-url-container">
					<div class="crm-webform-list-url-element">
						<span class="crm-webform-list-url-inner-wrap">
							<span class="crm-webform-list-url-text"><?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_PUBLIC_LINK')?>:</span>
							<a href="<?=htmlspecialcharsbx($item['PATH_TO_WEB_FORM_FILL'])?>" target="_blank" class="copy-to-clipboard-node crm-webform-list-url-link">
								<?=htmlspecialcharsbx($item['PATH_TO_WEB_FORM_FILL'])?>
							</a>
						</span>
						<span class="copy-to-clipboard-button crm-webform-list-url-link-icon" title="<?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_PUBLIC_LINK_COPY')?>"></span>
					</div>
				</div><!--crm-webform-list-url-container-->
				<div class="crm-webform-list-deal-container">
					<div class="crm-webform-list-deal-element">
						<span class="crm-webform-deal-text"><?=$item['ENTITY_COUNTERS_DISPLAY']?></span>
					</div>
				</div><!--crm-webform-list-deal-container-->
				<div class="crm-webform-list-fill-started-container">
					<div class="crm-webform-list-fill-started-element">
						<span class="crm-webform-fill-started-text">
							<?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_START_EDIT_BUT_STOPPED')?> - <?=intval($item['COUNT_QUIT_FILL'])?>
						</span>
					</div>
				</div><!--crm-webform-list-fill-started-container-->
			</div><!--crm-webform-list-inner-info-container-->

			<div class="crm-webform-list-button-settings-container">
				<span data-bx-crm-webform-item-btn-getscript="" class="webform-small-button webform-small-button-blue crm-webform-list-button-settings">
				<?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_BTN_GET_SCRIPT')?>
			</span>
				<a data-bx-slider-opener="" data-bx-edit-link="" href="<?=htmlspecialcharsbx($item['PATH_TO_WEB_FORM_EDIT'])?>" class="webform-small-button webform-small-button-transparent crm-webform-list-button-settings">
					<?if($item['IS_SYSTEM'] == 'Y' || !$arResult['PERM_CAN_EDIT']):?>
						<?=Loc::getMessage('CRM_WEBFORM_LIST_ACTIONS_VIEW')?>
					<?else:?>
						<?=Loc::getMessage('CRM_WEBFORM_LIST_ACTIONS_EDIT')?>
					<?endif;?>
				</a>

				<span data-bx-crm-webform-item-active-btn=""
					data-bx-text-on="<?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_ACTIVE_BTN_ON')?>"
					data-bx-text-off="<?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_ACTIVE_BTN_OFF')?>"
					class="webform-small-button <?=($item['ACTIVE'] <> 'Y' ? 'webform-small-button-accept' : 'webform-small-button-transparent')?> crm-webform-list-button-settings"
				>
				<?if($item['ACTIVE'] == 'Y'):?>
					<?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_ACTIVE_BTN_OFF')?>
				<?else:?>
					<?=Loc::getMessage('CRM_WEBFORM_LIST_ITEM_ACTIVE_BTN_ON')?>
				<?endif;?>
			</span>
			</div><!--intranet-button-list-button-settings-container-->

		</div><!--crm-webform-list-widget-container crm-webform-list-widget-right-->
	</div><!--crm-webform-list-widget-row-->

<?endforeach;?>
	</div>
<?endforeach;?>
</div><!--crm-webform-list-wrapper-->

	<?if($arResult['SHOW_PLUGINS']):?>
	<div class="crm-webform-list-header-container">
		<h3 class="crm-webform-list-header"><?=Loc::getMessage('CRM_WEBFORM_LIST_PLUGIN_TITLE')?></h3>
	</div><!--crm-webform-list-header-container-->

	<div class="crm-webform-list-widget-row">
		<div class="crm-webform-list-widget-plugin">
			<span class="crm-webform-list-widget-plugin-item crm-webform-list-widget-plugin-logo-1"></span>
			<span class="crm-webform-list-widget-plugin-item crm-webform-list-widget-plugin-logo-2"></span>
			<span class="crm-webform-list-widget-plugin-item crm-webform-list-widget-plugin-logo-3"></span>
			<span class="crm-webform-list-widget-plugin-item crm-webform-list-widget-plugin-logo-4"></span>
			<span class="crm-webform-list-widget-plugin-item crm-webform-list-widget-plugin-more">
					<span class="crm-webform-list-widget-plugin-item-text">
						<?=Loc::getMessage('CRM_WEBFORM_LIST_PLUGIN_BTN_MORE')?>...
					</span>
				</span>
		</div><!--crm-webform-list-widget-plugin-->
		<div class="crm-webform-list-widget-plugin-description">
			<span class="crm-webform-list-widget-plugin-description-item">
				<?=Loc::getMessage('CRM_WEBFORM_LIST_PLUGIN_DESC')?>
			</span>
		</div>
	</div>
	<?endif;?>

</div>

<script>
	BX.ready(function(){
		(new CrmWebFormList(<?=Json::encode(
			array(
				'context' => 'crm_web_form_list_container',
				'canEdit' => $arResult['PERM_CAN_EDIT'],
				'isFramePopup' => $arParams['IFRAME'],
				'viewUserOptionName' => $arResult['userOptionViewType'],
				'viewList' => $arResult['viewList'],
				'actionList' => $arResult['actionList'],
				'listPageUrl' => $arParams['PATH_TO_WEB_FORM_LIST'],
				'detailPageUrlTemplate' => $arParams['PATH_TO_WEB_FORM_EDIT'],
				'actionRequestUrl' => $this->getComponent()->getPath() . '/ajax.php',
				'filterList' => $arResult['FILTER'],
				'mess' => array(
					'errorAction' => Loc::getMessage('CRM_WEBFORM_LIST_ERROR_ACTION'),
					'deleteConfirmation' => Loc::getMessage('CRM_WEBFORM_LIST_DELETE_CONFIRM'),
					'dlgBtnClose' => Loc::getMessage('CRM_WEBFORM_LIST_CLOSE'),
					'dlgBtnApply' => Loc::getMessage('CRM_WEBFORM_LIST_APPLY'),
					'dlgBtnCancel' => Loc::getMessage('CRM_WEBFORM_LIST_CANCEL'),
					'dlgBtnCopy' => Loc::getMessage('CRM_WEBFORM_LIST_ITEM_PUBLIC_LINK_COPY'),
					'dlgActiveCountLimitedTitle' => Loc::getMessage('CRM_WEBFORM_LIST_POPUP_LIMITED_TITLE'),
					'dlgActiveCountLimitedText' => Loc::getMessage('CRM_WEBFORM_LIST_POPUP_LIMITED_TEXT'),
					'dlgGetScriptTitle' => Loc::getMessage('CRM_WEBFORM_LIST_ITEM_BTN_GET_SCRIPT'),
				)
			))?>
		));
	});
</script>

<?
$this->SetViewTarget("pagetitle", 10);
?>
	<span id="webform_filter_active" class="webform-small-button webform-small-button-transparent bx-filter-button">
		<span class="webform-small-button-text" data-bx-text="<?=Loc::getMessage('CRM_WEBFORM_LIST_FILTER_SHOW')?>">
			<?=Loc::getMessage('CRM_WEBFORM_LIST_FILTER_SHOW')?>: <?=$arResult['FILTER_ACTIVE_CURRENT_NAME']?>
		</span>
		<span class="webform-small-button-icon"></span>
	</span>

	<?if ($arResult['PERM_CAN_EDIT']):?>
	<a id="CRM_WEBFORM_LIST_ADD" href="<?=htmlspecialcharsbx($arResult['PATH_TO_WEB_FORM_NEW'])?>" class="webform-small-button webform-small-button-blue bx24-top-toolbar-add">
		<?=Loc::getMessage('CRM_WEBFORM_LIST_ADD_CAPTION')?>
	</a>
	<?endif;?>
<?
$this->EndViewTarget();

ob_start();
	global $APPLICATION;
	$APPLICATION->IncludeComponent(
		'bitrix:crm.webform.script',
		'',
		array(
			'FORM' => array(),
			'PATH_TO_WEB_FORM_FILL' => $arParams['PATH_TO_WEB_FORM_FILL']
		),
		null,
		array('HIDE_ICONS'=>true, 'ACTIVE_COMPONENT'=>'Y')
	);
ob_end_clean();
?>