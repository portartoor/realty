<?

/**
 * @var $arParams
 * @var $arResult
 */

use \Bitrix\Main\Text;
use \Bitrix\Main\Grid;
use \Bitrix\Main\Localization\Loc;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

CJSCore::Init(array('popup'));

\Bitrix\Main\Page\Asset::getInstance()->addJs('/bitrix/js/main/dd.js');

global $APPLICATION;
$bodyClass = $APPLICATION->GetPageProperty("BodyClass");
$APPLICATION->SetPageProperty("BodyClass", ($bodyClass ? $bodyClass." " : "")."grid-mode");

if ($arParams['FLEXIBLE_LAYOUT'])
{
	$bodyClass = $APPLICATION->getPageProperty('BodyClass', false);
	$APPLICATION->setPageProperty('BodyClass', trim(sprintf('%s %s', $bodyClass, 'flexible-layout')));
}

?>

<div class="main-grid <?=!$arParams["ALLOW_HORIZONTAL_SCROLL"] ? "main-grid-full" : "" && $arParams["ALLOW_ROWS_SORT"] ? " main-grid-rows-sort-enable" : ""?>" id="<?=$arParams["GRID_ID"]?>" data-ajaxid="<?=$arParams["AJAX_ID"]?>"><?
	?><form name="form_<?=$arParams["GRID_ID"]?>" action="<?=POST_FORM_ACTION_URI; ?>" method="POST"><?
		?><?=bitrix_sessid_post() ?><?
		?><div class="main-grid-settings-window"><?
			?><div class="main-grid-settings-window-list"><?
				foreach ($arResult["COLUMNS_ALL"] as $key => $column) : ?><?
					?><div data-name="<?=Text\HtmlFilter::encode($column["id"])?>" class="main-grid-settings-window-list-item"><?
						?><input id="<?=Text\HtmlFilter::encode($column["id"])?>-checkbox" type="checkbox" class="main-grid-settings-window-list-item-checkbox" <?=array_key_exists($column["id"], $arResult["COLUMNS"]) ? " checked" : ""?>><?
						?><label for="<?=Text\HtmlFilter::encode($column["id"])?>-checkbox" class="main-grid-settings-window-list-item-label"><?=Text\HtmlFilter::encode($column["name"])?></label><?
						?><input type="text" name="<?=Text\HtmlFilter::encode($column["id"])?>" value="<?=Text\HtmlFilter::encode($column["name"])?>" class="main-grid-settings-window-list-item-edit-input"><?
						?><span class="main-grid-settings-window-list-item-edit-button"></span><?
					?></div><?
				endforeach;
			?></div><?
		?></div><?
		?><div class="main-grid-wrapper<?=!$arParams["ALLOW_HORIZONTAL_SCROLL"] ? " main-grid-full" : "" ?>"><?
			?><div class="<?=$arParams["ALLOW_HORIZONTAL_SCROLL"] ? "main-grid-fade" : "" ?>"><?
				if ($arParams["ALLOW_HORIZONTAL_SCROLL"]) : ?><?
					?><div class="main-grid-ear main-grid-ear-left"></div><?
					?><div class="main-grid-ear main-grid-ear-right"></div><?
				endif; ?><?
				?><div class="main-grid-container"><?
					?><table class="main-grid-table" id="<?=$arParams["GRID_ID"]?>_table"><?
						if (!$arResult['BX_isGridAjax']): ?><?
							?><thead class="main-grid-header" data-relative="<?=$arParams["GRID_ID"]?>"><?
								?><tr class="main-grid-row-head"><?
									if ($arParams["SHOW_ROW_CHECKBOXES"]): ?><?
										?><th class="main-grid-cell-head main-grid-cell-static main-grid-cell-checkbox"><?
											if ($arParams["SHOW_CHECK_ALL_CHECKBOXES"]): ?><?
												?><span class="main-grid-cell-head-container"><?
													?><span class="main-grid-checkbox-container main-grid-head-checkbox-container"><?
														?><input class="main-grid-checkbox main-grid-row-checkbox main-grid-check-all" id="<?=$arParams["GRID_ID"]?>_check_all" type="checkbox" title="<?=getMessage('interface_grid_check_all') ?>"<? if (!$arResult['ALLOW_EDIT']): ?> disabled<? endif ?>><?
														?><label class="main-grid-checkbox" for="<?=$arParams["GRID_ID"]?>_check_all"></label><?
												?></span><?
											?></span><?
										endif; ?><?
										?></th><?
									endif ?><?
									if ($arParams["SHOW_GRID_SETTINGS_MENU"] || $arParams["SHOW_ROW_ACTIONS_MENU"]) : ?><?
										?><th class="main-grid-cell-head main-grid-cell-static main-grid-cell-action"><?
											if ($arParams["SHOW_GRID_SETTINGS_MENU"]) : ?><?
												?><span class="main-grid-interface-settings-icon"></span><?
											endif; ?><?
										?></th><?
									endif; ?><?
									foreach ($arResult['COLUMNS'] as $id => $header) : ?><?
									$isHidden = !array_key_exists($id, $arResult['COLUMNS']); ?><?
										?><th class="main-grid-cell-head <?=$header["class"]?> <?=$arParams["ALLOW_COLUMNS_SORT"] ? " main-grid-draggable" : ""?>" data-edit="(<?=Text\HtmlFilter::encode(CUtil::PhpToJSObject($header["editable"]))?>)" data-name="<?=Text\HtmlFilter::encode($id)?>" data-sort-url="<?=$header["sort_url"]?>" data-sort-by="<?=$header["sort"]?>" data-sort-order="<?=$header["next_sort_order"]?>" <? if($header["width"] <> ''): ?> style="width: <?=$header["width"]?>px"<? endif ?>><?
											?><span class="main-grid-cell-head-container" <? if($header["width"] <> ''): ?>style="width: <?=$header["width"]?>px"<? endif ?>><?
												?><span class="main-grid-head-title"><?=Text\HtmlFilter::encode($header["showname"] ? $header["name"] : "&nbsp;"); ?></span><?
												if ($arParams["ALLOW_COLUMNS_RESIZE"] && $header["resizeable"] !== false) : ?><?
													?><span class="main-grid-resize-button" onclick="event.stopPropagation(); " title=""></span><?
												endif; ?><?
												if ($header["sort"] && $arParams["ALLOW_SORT"]) : ?><?
													?><span class="main-grid-control-sort main-grid-control-sort-<?=$header["sort_state"] ? $header["sort_state"] : "hover-".$header["order"]?>"></span><?
												endif;
											?></span><?
										?></th><?
									endforeach ?><?
									?><th class="main-grid-cell-head main-grid-cell-static main-grid-special-empty"></th><?
								?></tr><?
							?></thead><?
						endif ?><?
						?><tbody><?
							if (empty($arParams['ROWS'])): ?><?
								?><tr class="main-grid-row main-grid-row-empty main-grid-row-body"><?
									?><td class="main-grid-cell main-grid-cell-center" colspan="<?=count($arParams['COLUMNS']) + 2 ?>"><?
										if (!isset($_REQUEST["apply_filter"])) :
											?><div class="main-grid-empty-block"><?
												?><div class="main-grid-empty-inner"><?
													?><div class="main-grid-empty-image"></div><?
													?><div class="main-grid-empty-text"><?=getMessage('interface_grid_no_data') ?></div><?
												?></div><?
											?></div><?
										else :
											?><div class="main-grid-empty-block"><?
												?><div class="main-grid-empty-inner"><?
													?><div class="main-grid-empty-image"></div><?
													?><div class="main-grid-empty-text"><?=getMessage('interface_grid_filter_no_data') ?></div><?
												?></div><?
											?></div><?
										endif; ?><?
									?></td><?
								?></tr><?
							else:
							 	foreach($arParams['ROWS'] as $arRow):
								$rowClasses = isset($arRow['columnClasses']) && is_array($arRow['columnClasses'])
										? $arRow['columnClasses'] : array();
								if (!empty($arRow["custom"])) : ?><?
									?><tr class="main-grid-row main-grid-row-body main-grid-row-custom"><?
										?><td colspan="<?=count($arResult["COLUMNS"]) + 2?>" class="main-grid-cell main-grid-cell-center"><?
											?><div class="main-grid-cell-content"><?=$arRow["custom"]?></div><?
										?></td><?
									?></tr><?
								else :
									$data_id = $arRow["id"];
									$actions = Text\HtmlFilter::encode(CUtil::PhpToJSObject($arRow["actions"]));
									$sDefAction = $arRow["default_action"]; ?><?
								?><tr class="main-grid-row main-grid-row-body" data-id="<?=$data_id ?>" <?if(!empty($sDefAction["js"])):?> data-default-action="<?=Text\HtmlFilter::encode($sDefAction["js"])?>" title="<?=GetMessage("interface_grid_dblclick")?><?=$sDefAction["title"]?>"<?endif;?>><?
									if ($arParams["SHOW_ROW_CHECKBOXES"]): ?><?
										?><td class="main-grid-cell main-grid-cell-checkbox"><?
											?><span class="main-grid-checkbox-container"><?
												?><input type="checkbox" class="main-grid-row-checkbox main-grid-checkbox" name="ID[]" value="<?=$data_id ?>" <? if ($arRow['editable'] !== false): ?> title="<?=getMessage('interface_grid_check') ?>" id="checkbox_<?=$arParams["GRID_ID"]?>_<?=$data_id ?>"<? endif ?> <? if (!$arResult['ALLOW_EDIT'] || $arRow['editable'] === false): ?> data-disabled="1" disabled<? endif ?>><?
												?><label class="main-grid-checkbox" for="checkbox_<?=$arParams["GRID_ID"]?>_<?=$data_id ?>"></label><?
											?></span><?
										?></td><?
									endif ?><?
										if ($arParams["SHOW_ROW_ACTIONS_MENU"] || $arParams["SHOW_GRID_SETTINGS_MENU"]) :
											?><td class="main-grid-cell main-grid-cell-action"><?
												if (!empty($arRow["actions"]) && $arParams["SHOW_ROW_ACTIONS_MENU"]) : ?><?
													?><span class="main-grid-action-container"><?
														?><a href="#" class="main-grid-row-action-button" data-actions="<?=$actions?>"></a><?
													?></span><?
												endif
											?></td><?
										endif; ?><?
											foreach ($arResult['COLUMNS'] as $id => $header):
												$preventDefault = $header["prevent_default"] ? "true" : "false";

												if (!(is_array($arRow["editable"]) && $arRow["editable"][$header["id"]] === false) && is_array($header["editable"]) && $arRow["editable"] !== false && is_array($arRow["data"]))
												{
													$header["editable"]["VALUE"] = $arRow["data"][$header["id"]];
												}
												else
												{
													$header["editable"] = false;
												}

												$className = "main-grid-cell";
												if($header['align'])
												{
													$className .= " main-grid-cell-{$header['align']}";
												}
												if(isset($rowClasses[$id]))
												{
													$className .= " {$rowClasses[$id]}";
												}
											?><td class="<?=$className?>"><?
												?><span class="main-grid-cell-content" data-prevent-default="<?=$preventDefault?>"><?
													if($header["type"] == "checkbox" && ($arRow["columns"][$header["id"]] == 'Y' || $arRow["columns"][$header["id"]] == 'N'))
													{
														echo ($arRow["columns"][$header["id"]] == 'Y'? GetMessage("interface_grid_yes"):GetMessage("interface_grid_no"));
													}
													else
													{
														echo $arRow["columns"][$header["id"]];
													}
												?></span><?
											?></td><?
										endforeach ?><?
									?><td class="main-grid-cell"></td><?
								?></tr><?
							endif; ?>
						<? endforeach ?><?
						if (!empty($arResult['AGGREGATE'])): ?><?
						?><tr class="main-grid-row-foot main-grid-aggr-row" id="datarow_<?=$arParams["GRID_ID"]?>_bxaggr"><?
							if ($arResult['ALLOW_GROUP_ACTIONS']): ?><td class="main-grid-cell-foot"></td><? endif ?><?
								if ($arParams['ALLOW_ROW_ACTIONS']): ?><td class="main-grid-cell-foot"></td><? endif ?><?
									foreach ($arResult['COLUMNS'] as $id => $header): ?><?
										$isHidden = !array_key_exists($id, $arResult['COLUMNS']);
											?><td class="main-grid-cell-foot <? if ($header['align']) echo 'main-grid-cell-', $header['align']; ?>" <? if ($isHidden): ?> style="display: none; "<? endif ?>><?
													?><span class="main-grid-cell-content main-grid-cell-text-line"><?
														if (!$isHidden && !empty($arResult['AGGREGATE'][$id])): ?><?
															foreach ($arResult['AGGREGATE'][$id] as $item): ?><?
																?><?=$item; ?><br><?
															endforeach; ?><?
														endif; ?><?
													?></span><?
											?></td><?
									endforeach; ?><?
								?><td class="main-grid-cell-foot"></td><?
							?></tr><?
						endif ?><?
					endif ?><?
				?></tbody><?
			?></table><?
		?></div><?
	?></div><?
?></div><?
	?><div class="main-grid-bottom-panels" id="<?=$arParams["GRID_ID"]?>_bottom_panels"><?
		?><div class="main-grid-nav-panel"><?
			?><div class="main-grid-more" id="<?=$arParams["GRID_ID"]?>_nav_more"><?
				?><a href="<?=$arResult["NEXT_PAGE_URL"]?>" class="main-grid-more-btn" <? if (!$arResult["SHOW_MORE_BUTTON"] || !$arParams["SHOW_MORE_BUTTON"] || !count($arResult["ROWS"])): ?>style="display: none; "<? endif ?>><?
					?><span class="main-grid-more-text"><?=getMessage('interface_grid_nav_more') ?></span><?
					?><span class="main-grid-more-load-text"><?=getMessage('interface_grid_load') ?></span><?
					?><span class="main-grid-more-icon"></span><?
				?></a><?
			?></div><?
		if ($arParams["SHOW_NAVIGATION_PANEL"]) : ?><?
			?><div class="main-grid-panel-wrap"><?
				?><table class="main-grid-panel-table"><?
					?><tr><?
						if ($arParams["SHOW_SELECTED_COUNTER"]) : ?><?
							?><td class="main-grid-panel-cell main-grid-panel-counter main-grid-cell-left"><?
								?><div class="main-grid-panel-content main-grid-panel-counter-for-selected"><?
									?><span class="main-grid-panel-content-title"><?=getMessage('interface_grid_checked') ?></span>&nbsp;<?
									?><span class="main-grid-panel-content-text"><?
										?><span class="main-grid-counter-selected">0</span><?
										?>&nbsp;/&nbsp;<?
										?><span class="main-grid-counter-displayed"><?=count($arResult["ROWS"])?></span><?
									?></span><?
								?></div><?
								?><div class="main-grid-panel-content main-grid-panel-counter-for-all"><?
									?><span class="main-grid-panel-content-text"><?=Loc::getMessage("interface_grid_all_selected")?></span><?
								?></div><?
							?></td><?
							endif; ?><?
							if ($arParams["SHOW_TOTAL_COUNTER"] && (isset($arResult["TOTAL_ROWS_COUNT"]) || !empty($arParams["TOTAL_ROWS_COUNT_HTML"]))) : ?><?
							?><td class="main-grid-panel-total main-grid-panel-cell main-grid-cell-left"><?
								?><div class="main-grid-panel-content"><?
									if (empty($arParams["TOTAL_ROWS_COUNT_HTML"])) : ?><?
										?><span class="main-grid-panel-content-title"><?=GetMessage("interface_grid_total")?>:</span><?
										?>&nbsp;<span class="main-grid-panel-content-text"><?=count($arResult["ROWS"]) ? $arResult["TOTAL_ROWS_COUNT"] : 0?></span><?
									else : ?><?
										?><?=Text\HtmlConverter::getHtmlConverter()->decode($arParams["TOTAL_ROWS_COUNT_HTML"])?><?
									endif; ?><?
								?></div><?
							?></td><?
						endif; ?><?
						?><td class="main-grid-panel-cell main-grid-panel-cell-pagination main-grid-cell-left"><?
							if ($arParams["SHOW_PAGINATION"]) : ?><?
								?><?=Bitrix\Main\Text\Converter::getHtmlConverter()->decode($arResult["NAV_STRING"]);?><?
							endif; ?><?
						?></td><?
						?><td class="main-grid-panel-cell main-grid-panel-limit main-grid-cell-right"><?
							if ($arParams["SHOW_PAGESIZE"] && is_array($arParams["PAGE_SIZES"]) && count($arParams["PAGE_SIZES"]) > 0) :
									$pageSize = $arResult['OPTIONS']['views'][$arResult['OPTIONS']['current_view']]['page_size'] ?: 20; ?><?
								?><span class="main-grid-panel-content"><?
									?><span class="main-grid-panel-content-title"><?=getMessage('interface_grid_page_size') ?></span> <?
										?><span class="main-dropdown main-grid-popup-control main-grid-panel-select-pagesize" id="<?=$arParams["GRID_ID"]?>_grid_page_size" data-value="<?=$pageSize;?>" data-items="<?=$arResult["PAGE_SIZES_JSON"]?>">
											<?=$pageSize; ?><?
										?></span><?
									?></span><?
								endif; ?><?
							?></td><?
						?></tr><?
					?></table><?
				?></div><?
			endif; ?><?
		?></div>
		<? if ($arParams["SHOW_ACTION_PANEL"] && isset($arParams["ACTION_PANEL"]) && !empty($arParams["ACTION_PANEL"]) && is_array($arParams["ACTION_PANEL"]["GROUPS"])) : ?><?
			?><div class="main-grid-action-panel main-grid-disable"><?
				?><div class="main-grid-control-panel-wrap"><?
					?><table class="main-grid-control-panel-table"><?
						?><tr class="main-grid-control-panel-row"><?
							foreach ($arParams["ACTION_PANEL"]["GROUPS"] as $groupKey => $group) : ?><?
								?><td class="main-grid-control-panel-cell<?=$group["CLASS"] ? " ".$group["CLASS"] : "" ?>"><?
									foreach ($group["ITEMS"] as $itemKey => $item) : ?><?
										if ($item["TYPE"] === "CHECKBOX") :
											?><span class="main-grid-panel-control-container<?=$item["DISABLED"] ? " main-grid-disable" : "";?>" id="<?=Text\HtmlFilter::encode($item["ID"])?>"><?
												if ($item["NAME"] === Grid\Panel\DefaultValue::FOR_ALL_CHECKBOX_NAME) : ?><?
													?><span class="main-grid-checkbox-container main-grid-control-panel-checkbox-container"><?
															?><input class="main-grid-panel-checkbox main-grid-checkbox main-grid-panel-control <?=$item["CLASS"]?>" id="<?=Text\HtmlFilter::encode($item["ID"])?><?=$arParams["GRID_ID"]?>" name="<?=Text\HtmlFilter::encode($item["NAME"])?><?=$arParams["GRID_ID"]?>" type="checkbox" value="<?=Text\HtmlFilter::encode($item["VALUE"])?>" title="<?=Text\HtmlFilter::encode($item["TITLE"])?>" data-onchange="<?=Text\HtmlFilter::encode(CUtil::PhpToJSObject($item["ONCHANGE"]))?>"<?=$item["CHECKED"] ? " checked" : ""?>> <?
															?> <label class="main-grid-checkbox" for="<?=Text\HtmlFilter::encode($item["ID"])?><?=$arParams["GRID_ID"]?>"></label><?
														?></span><?
														?><span class="main-grid-control-panel-content-title"><?
														?> <label for="<?=Text\HtmlFilter::encode($item["ID"])?><?=$arParams["GRID_ID"]?>" title="<?=Loc::getMessage("interface_grid_for_all")?>"><?=Loc::getMessage("interface_grid_for_all_box")?></label><?
													?></span><?
												else : ?><?
													?><span class="main-grid-checkbox-container main-grid-control-panel-checkbox-container"><?
														?><input class="main-grid-panel-checkbox main-grid-checkbox main-grid-panel-control" id="<?=Text\HtmlFilter::encode($item["ID"])?>_control" name="<?=Text\HtmlFilter::encode($item["NAME"])?>" type="checkbox" value="<?=Text\HtmlFilter::encode($item["VALUE"])?>" title="<?=Text\HtmlFilter::encode($item["TITLE"])?>" data-onchange="<?=Text\HtmlFilter::encode(CUtil::PhpToJSObject($item["ONCHANGE"]))?>"<?=$item["CHECKED"] ? " checked" : ""?>><?
														?> <label class="main-grid-checkbox" for="<?=Text\HtmlFilter::encode($item["ID"])?>_control"></label><?
													?></span><?
													?><span class="main-grid-control-panel-content-title"><?
														?> <label for="<?=Text\HtmlFilter::encode($item["ID"])?>_control" title="<?=Text\HtmlFilter::encode($item["TITLE"])?>"><?=Text\HtmlFilter::encode($item["LABEL"])?></label><?
													?></span><?
												endif;
											?></span><?
										endif; ?><?
										if ($item["TYPE"] === "DROPDOWN") :
											?><span class="main-grid-panel-control-container<?=$item["DISABLED"] ? " main-grid-disable" : "";?>" id="<?=Text\HtmlFilter::encode($item["ID"])?>"><?
												?><span class="main-dropdown main-grid-panel-control" id="<?=Text\HtmlFilter::encode($item["ID"])?>_control" data-name="<?=Text\HtmlFilter::encode($item["NAME"])?>" data-value="<?=Text\HtmlFilter::encode(CUtil::PhpToJSObject($item["ITEMS"][0]["VALUE"]))?>" data-items="<?=Text\HtmlFilter::encode(CUtil::PhpToJSObject($item["ITEMS"]))?>"><?
													?><?=$item["ITEMS"][0]["NAME"]?><?
												?></span><?
											?></span><?
										endif; ?><?
										if ($item["TYPE"] === "TEXT") : ?><?
										?><span class="main-grid-panel-control-container<?=$item["DISABLED"] ? " main-grid-disable" : "";?>" id="<?=Text\HtmlFilter::encode($item["ID"])?>"><?
											if ($item["LABEL"]) : ?><?
												?><label for="<?=Text\HtmlFilter::encode($item["ID"])?>_control"><?=Text\HtmlFilter::encode($item["LABEL"])?></label><?
											endif;
											?> <input type="text" class="main-grid-control-panel-input-text main-grid-panel-control" name="<?=Text\HtmlFilter::encode($item["NAME"])?>" id="<?=Text\HtmlFilter::encode($item["ID"])?>_control" placeholder="<?=Text\HtmlFilter::encode($item["PLACEHOLDER"])?>" value="<?=Text\HtmlFilter::encode($item["VALUE"])?>" title="<?=Text\HtmlFilter::encode($item["TITLE"])?>"><?
										?></span><?
										endif; ?><?
										if ($item["TYPE"] === "BUTTON") : ?><?
										?><span class="main-grid-panel-control-container<?=$item["DISABLED"] ? " main-grid-disable" : "";?>" id="<?=Text\HtmlFilter::encode($item["ID"])?>"><?
											?><button class="main-grid-buttons <?=Text\HtmlFilter::encode($item["CLASS"])?>" id="<?=Text\HtmlFilter::encode($item["ID"])?>_control" data-onchange="<?=Text\HtmlFilter::encode(CUtil::PhpToJSObject($item["ONCHANGE"]))?>" title="<?=Text\HtmlFilter::encode($item["TITLE"])?>"><?
												?><?=$item["TEXT"]
											?></button><?
										?></span><?
										endif; ?><?
										if ($item["TYPE"] === "LINK") :
											?><span class="main-grid-panel-control-container<?=$item["DISABLED"] ? " main-grid-disable" : "";?>" id="<?=Text\HtmlFilter::encode($item["ID"])?>" data-onchange="<?=Text\HtmlFilter::encode(CUtil::PhpToJSObject($item["ONCHANGE"]))?>" title="<?=Text\HtmlFilter::encode($item["TITLE"])?>"><?
												?><a href="<?=Text\HtmlFilter::encode($item["HREF"])?>" class="main-grid-link<?=$item["CLASS"] ? " ".Text\HtmlFilter::encode($item["CLASS"]) : ""?>" id="<?=Text\HtmlFilter::encode($item["ID"])?>_control"><?=$item["TEXT"]?></a><?
											?></span><?
										endif; ?><?
									endforeach; ?><?
								?></td><?
							endforeach; ?><?
							if ($arParams["SHOW_SELECTED_COUNTER"]) : ?><?
								?><td class="main-grid-panel-cell main-grid-panel-counter main-grid-cell-left"><?
									?><div class="main-grid-panel-content main-grid-panel-counter-for-selected"><?
										?><span class="main-grid-panel-content-title"><?=getMessage('interface_grid_checked') ?></span>&nbsp;<?
										?><span class="main-grid-panel-content-text"><?
											?><span class="main-grid-counter-selected">0</span><?
											?>&nbsp;/&nbsp;<?
											?><span class="main-grid-counter-displayed"><?=count($arResult["ROWS"])?></span><?
										?></span><?
									?></div><?
									?><div class="main-grid-panel-content main-grid-panel-counter-for-all"><?
										?><span class="main-grid-panel-content-text"><?=Loc::getMessage("interface_grid_all_selected")?></span><?
									?></div><?
								?></td><?
							endif; ?><?
						?></tr><?
					?></table><?
				?></div><?
			?></div><?
		endif; ?><?
	?></div><?
?></form><?
?></div>

<?
$request = \Bitrix\Main\Context::getCurrent()->getRequest();
if (\Bitrix\Main\Grid\Context::isInternalRequest()) :
?><script>
	(function() {
		var action = '<?=$request->get("grid_action")?>';
		var editableData = eval(<?=CUtil::phpToJSObject($arResult["DATA_FOR_EDIT"])?>);
		var Grid = BX.Main.gridManager.getById('<?=$arParams["GRID_ID"]?>');

		Grid = Grid ? Grid.instance : null;

		if (Grid)
		{
			if (action != 'more')
			{
				Grid.arParams.EDITABLE_DATA = editableData;
			}
			else
			{
				var editableDataKeys = Object.keys(editableData);
				editableDataKeys.forEach(function(key) {
					Grid.arParams.EDITABLE_DATA[key] = editableData[key];
				});
			}
		}
	})();
</script><?
endif; ?>

<? if (!$arResult['IS_AJAX'] || !$arResult['IS_INTERNAL']) : ?><?
?><script>
		BX(function() { BX.Main.dropdownManager.init(); });
		BX.Main.gridManager.push(
			'<?=$arParams["GRID_ID"]?>',
			new BX.Main.grid(
				'<?=$arParams["GRID_ID"]?>',
				<?=CUtil::PhpToJSObject(
					array(
						"ALLOW_COLUMNS_SORT" => $arParams["ALLOW_COLUMNS_SORT"],
						"ALLOW_ROWS_SORT" => $arParams["ALLOW_ROWS_SORT"],
						"ALLOW_COLUMNS_RESIZE" => $arParams["ALLOW_COLUMNS_RESIZE"],
						"SHOW_ROW_CHECKBOXES" => $arParams["SHOW_ROW_CHECKBOXES"],
						"ALLOW_HORIZONTAL_SCROLL" => $arParams["ALLOW_HORIZONTAL_SCROLL"],
						"ALLOW_PIN_HEADER" => $arParams["ALLOW_PIN_HEADER"],
						"SHOW_ACTION_PANEL" => $arParams["SHOW_ACTION_PANEL"],
						"PRESERVE_HISTORY" => $arParams["PRESERVE_HISTORY"],
						"BACKEND_URL" => $arResult["BACKEND_URL"],
						"ALLOW_CONTEXT_MENU" => $arResult["ALLOW_CONTEXT_MENU"],
						"DEFAULT_COLUMNS" => $arResult["DEFAULT_COLUMNS"],
						"EDITABLE_DATA" => $arResult["DATA_FOR_EDIT"],
						"SETTINGS_TITLE" => Loc::getMessage("interface_grid_settings_title"),
						"APPLY_SETTINGS" => Loc::getMessage("interface_grid_apply_settings"),
						"CANCEL_SETTINGS" => Loc::getMessage("interface_grid_cancel_settings"),
						"CONFIRM_APPLY" => Loc::getMessage("interface_grid_confirm_apply"),
						"CONFIRM_CANCEL" => Loc::getMessage("interface_grid_confirm_cancel"),
						"CONFIRM_MESSAGE" => Loc::getMessage("interface_grid_confirm_message"),
						"CONFIRM_FOR_ALL_MESSAGE" => Loc::getMessage("interface_grid_confirm_for_all_message"),
						"CONFIRM_RESET_MESSAGE" => Loc::getMessage("interface_grid_settings_confirm_message"),
						"RESET_DEFAULT" => Loc::getMessage("interface_grid_restore_to_default")
					)
				)?>,
				<?=CUtil::PhpToJSObject($arResult["OPTIONS"])?>,
				<?=CUtil::PhpToJSObject($arResult["OPTIONS_ACTIONS"])?>,
				'<?=$arResult["OPTIONS_HANDLER_URL"]?>',
				<?=CUtil::PhpToJSObject($arResult["PANEL_ACTIONS"])?>,
				<?=CUtil::PhpToJSObject($arResult["PANEL_TYPES"])?>,
				<?=CUtil::PhpToJSObject($arResult["EDITOR_TYPES"])?>
			)
		);
	</script>
<? endif; ?>