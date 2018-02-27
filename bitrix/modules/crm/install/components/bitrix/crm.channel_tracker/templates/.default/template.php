<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

/**
 * Bitrix vars
 * @global CUser $USER
 * @global CMain $APPLICATION
 * @global CDatabase $DB
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 */

$APPLICATION->IncludeComponent(
	'bitrix:crm.control_panel',
	'',
	array(
		'ID' => 'START',
		'ACTIVE_ITEM_ID' => 'START',
		'PATH_TO_COMPANY_LIST' => isset($arParams['PATH_TO_COMPANY_LIST']) ? $arParams['PATH_TO_COMPANY_LIST'] : '',
		'PATH_TO_COMPANY_EDIT' => isset($arParams['PATH_TO_COMPANY_EDIT']) ? $arParams['PATH_TO_COMPANY_EDIT'] : '',
		'PATH_TO_CONTACT_LIST' => isset($arParams['PATH_TO_CONTACT_LIST']) ? $arParams['PATH_TO_CONTACT_LIST'] : '',
		'PATH_TO_CONTACT_EDIT' => isset($arParams['PATH_TO_CONTACT_EDIT']) ? $arParams['PATH_TO_CONTACT_EDIT'] : '',
		'PATH_TO_DEAL_LIST' => isset($arParams['PATH_TO_DEAL_LIST']) ? $arParams['PATH_TO_DEAL_LIST'] : '',
		'PATH_TO_DEAL_EDIT' => isset($arParams['PATH_TO_DEAL_EDIT']) ? $arParams['PATH_TO_DEAL_EDIT'] : '',
		'PATH_TO_LEAD_LIST' => isset($arParams['PATH_TO_LEAD_LIST']) ? $arParams['PATH_TO_LEAD_LIST'] : '',
		'PATH_TO_LEAD_EDIT' => isset($arParams['PATH_TO_LEAD_EDIT']) ? $arParams['PATH_TO_LEAD_EDIT'] : '',
		'PATH_TO_QUOTE_LIST' => isset($arResult['PATH_TO_QUOTE_LIST']) ? $arResult['PATH_TO_QUOTE_LIST'] : '',
		'PATH_TO_QUOTE_EDIT' => isset($arResult['PATH_TO_QUOTE_EDIT']) ? $arResult['PATH_TO_QUOTE_EDIT'] : '',
		'PATH_TO_INVOICE_LIST' => isset($arResult['PATH_TO_INVOICE_LIST']) ? $arResult['PATH_TO_INVOICE_LIST'] : '',
		'PATH_TO_INVOICE_EDIT' => isset($arResult['PATH_TO_INVOICE_EDIT']) ? $arResult['PATH_TO_INVOICE_EDIT'] : '',
		'PATH_TO_REPORT_LIST' => isset($arParams['PATH_TO_REPORT_LIST']) ? $arParams['PATH_TO_REPORT_LIST'] : '',
		'PATH_TO_DEAL_FUNNEL' => isset($arParams['PATH_TO_DEAL_FUNNEL']) ? $arParams['PATH_TO_DEAL_FUNNEL'] : '',
		'PATH_TO_EVENT_LIST' => isset($arParams['PATH_TO_EVENT_LIST']) ? $arParams['PATH_TO_EVENT_LIST'] : '',
		'PATH_TO_PRODUCT_LIST' => isset($arParams['PATH_TO_PRODUCT_LIST']) ? $arParams['PATH_TO_PRODUCT_LIST'] : ''
	),
	$component
);

$guid = $arResult['GUID'];
$config = $arResult['CONFIG'];
$items = $arResult['ITEMS'];
$groupItems = $arResult['GROUP_ITEMS'];
$totals = $arResult['TOTALS'];
$groupTotals = $arResult['GROUP_TOTALS'];
$messages =  $arResult['MESSAGES'];
$containerID = "{$guid}_container";
$toggleButtonID = "{$guid}_toggle_btn";
$helpButtonID = "{$guid}_help_btn";
$isExpanded = $config['expanded'] === 'Y';

$APPLICATION->ShowViewContent('widget_panel_header');

?><div class="startpage-table-wrap">
	<div class="startpage-table-wrap-head">
		<!--<span class="startpage-table-wrap-settings"></span>-->
		<span id="<?=htmlspecialcharsbx($helpButtonID)?>" class="startpage-table-wrap-help"></span>
		<span class="startpage-table-wrap-title-container">
			<span class="startpage-table-wrap-title-inner">
				<span class="startpage-table-wrap-title"><?=htmlspecialcharsbx($arResult['TITLE'])?></span>
			</span>
		</span>
	</div>
	<div class="startpage-table-wrap-content">
		<div class="startpage-table-container<?= !$isExpanded ? ' collapse' : ''?>" id="<?=htmlspecialcharsbx($containerID)?>">
			<div class="startpage-table">
				<div class="startpage-table-header">
					<div>
						<div class="startpage-table-header-container">
							<div class="startpage-table-header-block col1">
								<div class="startpage-table-header-block-title">
									<span><?=$arResult['HEADERS']['NAME']?></span>
								</div>
								<div class="startpage-table-header-block-count">
									<span><?=$messages['IN_USE']?></span>
								</div>
							</div>
						</div>
					</div>
					<div>
						<div class="startpage-table-header-container">
							<div class="startpage-table-header-block col2">
								<div class="startpage-table-header-block-title">
									<span><?=$arResult['HEADERS']['ACTIVITY']?></span>
								</div>
								<div class="startpage-table-header-block-count">
									<span><?=$totals['ACTIVITY']?></span>
								</div>
							</div>
						</div>
					</div>
					<div>
						<div class="startpage-table-header-container">
							<div class="startpage-table-header-block col3">
								<div class="startpage-table-header-block-title">
									<span><?=$arResult['HEADERS']['LEAD']?></span>
								</div>
								<div class="startpage-table-header-block-count">
									<span><?=$totals['LEAD']?></span>
								</div>
							</div>
						</div>
					</div>
					<div>
						<div class="startpage-table-header-container">
							<div class="startpage-table-header-block col4">
								<div class="startpage-table-header-block-title">
									<span><?=$arResult['HEADERS']['DEAL_PROCESS']?></span>
								</div>
								<div class="startpage-table-header-block-count">
									<span><?=$totals['DEAL_PROCESS']?></span>
								</div>
							</div>
						</div>
					</div>
					<div>
						<div class="startpage-table-header-container">
							<div class="startpage-table-header-block col5">
								<div class="startpage-table-header-block-title">
									<span><?=$arResult['HEADERS']['DEAL_SUCCESS']?></span>
								</div>
								<div class="startpage-table-header-block-count">
										<span>
											<strong class="startpage-table-header-block-count-wrapper">
												<?=CCrmCurrency::MoneyToString($totals['DEAL_SUCCESS'], $arResult['CURRENCY_ID'])?>
											</strong>
										</span>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="startpage-table-data-body"><?
					$counter = 0;
					$currentGroupID = '';
					foreach($items as $item)
					{
						$groupID = $item['GROUP_ID'];

						if($groupID !== '')
						{
							if($groupID !== $currentGroupID)
							{
								$group = isset($groupItems[$groupID]) ? $groupItems[$groupID] : null;
								if($currentGroupID !== '')
								{
									//End of group items container
									?></div><?
								}

								$currentGroupID = is_array($group) && $group['IS_DISPLAYABLE'] ? $group['ID'] : '';
								if($currentGroupID !== '')
								{
									$counter++;
									$currentGroupTotals = isset($groupTotals[$currentGroupID])
										? $groupTotals[$currentGroupID] : array();

									//Group header
									$classNames = array(($counter % 2) === 0 ? 'even' : 'odd');
									if(isset($group['IS_IN_USE']) && $group['IS_IN_USE'])
									{
										$classNames[] = 'connected';
									}

									?><div class="startpage-table-data startpage-table-interlacing <?=implode(' ', $classNames)?> collapse" data-group="<?=htmlspecialcharsbx($currentGroupID)?>"><?
										?><div class="startpage-table-column">
											<div class="startpage-table-column-content">
												<div class="startpage-table-data-children-toggle"></div>
												<?if($group['URL'] !== '')
												{
													?><a target="_blank" href="<?=htmlspecialcharsbx($group['URL'])?>">
														<?=htmlspecialcharsbx($group['CAPTION'])?>
													</a><?
												}
												else
												{
													?><span><?=htmlspecialcharsbx($group['CAPTION'])?></span><?
												}?>
											</div>
										</div>
										<div class="startpage-table-column">
											<div class="startpage-table-column-content">
												<?if(isset($currentGroupTotals['ACTIVITY']) && $currentGroupTotals['ACTIVITY'] > 0)
												{
													?><span><?=$currentGroupTotals['ACTIVITY']?></span><?
												}
												else
												{
													?>-<?
												}?>
											</div>
										</div>
										<div class="startpage-table-column">
											<div class="startpage-table-column-content">
												<?if(isset($currentGroupTotals['LEAD']) && $currentGroupTotals['LEAD'] > 0)
												{
													?><span><?=$currentGroupTotals['LEAD']?></span><?
												}
												else
												{
													?>-<?
												}?>
											</div>
										</div>
										<div class="startpage-table-column">
											<div class="startpage-table-column-content">
												<?if(isset($currentGroupTotals['DEAL_PROCESS']) && $currentGroupTotals['DEAL_PROCESS'] > 0)
												{
													?><span><?=$currentGroupTotals['DEAL_PROCESS']?></span><?
												}
												else
												{
													?>-<?
												}?>
											</div>
										</div>
										<div class="startpage-table-column">
											<div class="startpage-table-column-content">
												<?if(isset($currentGroupTotals['DEAL_SUCCESS']) && $currentGroupTotals['DEAL_SUCCESS'] > 0)
												{
													?><span><?=$currentGroupTotals['DEAL_SUCCESS']?></span><?
												}
												else
												{
													?>-<?
												}?>
											</div>
										</div>
									</div><?
									//Start of group items container
									?><div class="startpage-table-data-collapse collapse" data-group-items="<?=htmlspecialcharsbx($currentGroupID)?>"><?
								}
							}
						}

						$classNames = array();
						if($currentGroupID === '')
						{
							$counter++;
							$classNames[] = 'startpage-table-interlacing';
							$classNames[] = ($counter % 2) === 0 ? 'even' : 'odd';
						}

						$isInUse = $item['IS_IN_USE'];
						if($isInUse)
						{
							$classNames[] = 'connected';
						}

						$counters = $item['COUNTERS'];

					?><div class="startpage-table-data <?=implode(' ', $classNames)?>">
							<div class="startpage-table-column">
								<div class="startpage-table-column-content">
									<a target="_blank" href="<?=htmlspecialcharsbx($item['CONFIG_URL'])?>">
										<?=htmlspecialcharsbx($item['CAPTION'])?>
									</a><?
									if(!$isInUse)
									{
										?><a target="_blank" href="<?=htmlspecialcharsbx($item['CONFIG_URL'])?>" class="startpage-service-connect-btn">
											<?=GetMessage('CRM_CH_TRACKER_CONNECT')?>
										</a><?
									}
								?></div>
							</div>
							<div class="startpage-table-column">
								<div class="startpage-table-column-content"><?
									if($counters['ACTIVITY']['VALUE'] > 0)
									{
										?><a target="_blank" href="<?=htmlspecialcharsbx($counters['ACTIVITY']['URL'])?>">
											<?=$counters['ACTIVITY']['VALUE']?>
										</a><?
									}
									else
									{
										?>-<?
									}
								?></div>
							</div>
							<div class="startpage-table-column">
								<div class="startpage-table-column-content"><?
									if($counters['LEAD']['VALUE'] > 0)
									{
										?><a target="_blank" href="<?=htmlspecialcharsbx($counters['LEAD']['URL'])?>">
										<?=$counters['LEAD']['VALUE']?>
										</a><?
									}
									else
									{
										?>-<?
									}
								?></div>
							</div>
							<div class="startpage-table-column">
								<div class="startpage-table-column-content"><?
									if($counters['DEAL_PROCESS']['VALUE'] > 0)
									{
										?><a target="_blank" href="<?=htmlspecialcharsbx($counters['DEAL_PROCESS']['URL'])?>">
										<?=$counters['DEAL_PROCESS']['VALUE']?>
										</a><?
									}
									else
									{
										?>-<?
									}
								?></div>
							</div>
							<div class="startpage-table-column">
								<div class="startpage-table-column-content"><?
									if($counters['DEAL_SUCCESS']['VALUE'] > 0)
									{
										?><a target="_blank" href="<?=htmlspecialcharsbx($counters['DEAL_SUCCESS']['URL'])?>">
										<?=CCrmCurrency::MoneyToString($counters['DEAL_SUCCESS']['VALUE'], $arResult['CURRENCY_ID'])?>
										</a><?
									}
									else
									{
										?>-<?
									}
								?></div>
							</div>
					</div><?
					}
				?></div>
			</div>
		</div>
		<div class="startpage-collapse-btn-container">
			<span class="startpage-collapse-btn" id="<?=htmlspecialcharsbx($toggleButtonID)?>"><?=GetMessage($isExpanded ? 'CRM_CH_TRACKER_MINIMIZE' : 'CRM_CH_TRACKER_MAXIMIZE')?></span>
		</div>
	</div>
</div>
<script type="text/javascript">
	BX.CrmChannelTracker.messages =
	{
		minimize: "<?=GetMessageJS('CRM_CH_TRACKER_MINIMIZE')?>",
		maximize: "<?=GetMessageJS('CRM_CH_TRACKER_MAXIMIZE')?>",
		helpTitle: "<?=GetMessageJS('CRM_CH_TRACKER_HELP_POPUP_TITLE')?>",
		helpContent: "<?=GetMessageJS('CRM_CH_TRACKER_HELP_POPUP_CONTENT')?>"
	};

	BX.CrmChannelTracker.create(
		"<?=CUtil::JSEscape($guid)?>",
		{
			config: <?=CUtil::PhpToJSObject($config)?>,
			containerId: "<?=CUtil::JSEscape($containerID)?>",
			toggleButtonId: "<?=CUtil::JSEscape($toggleButtonID)?>",
			helpButtonId: "<?=CUtil::JSEscape($helpButtonID)?>",
			serviceUrl: "<?='/bitrix/components/bitrix/crm.channel_tracker/settings.php?'.bitrix_sessid_get()?>",
		}
	);

</script><?
$currentUserID = CCrmSecurityHelper::GetCurrentUserID();
$isSupervisor = CCrmPerms::IsAdmin($currentUserID)
	|| Bitrix\Crm\Integration\IntranetManager::isSupervisor($currentUserID);

$rowData = array(
	array(
		'height' => 380,
		'cells' => array(
			array(
				'controls' => array(
					array(
						'entityTypeName' => CCrmOwnerType::ActivityName,
						'typeName' => 'pie',
						'title' => GetMessage('CRM_CH_TRACKER_WGT_ACTIVITY_QUANTITY'),
						'group' => 'CHANNEL',
						'configs' => array(
							array(
								'name' => 'activity_qty',
								'dataPreset' => 'ACTIVITY_CHANNEL_STATS::OVERALL_COUNT',
								'dataSource' => 'ACTIVITY_CHANNEL_STATS',
								'select' => array('name' => 'COUNT', 'aggregate' => 'COUNT')
							)
						)
					)
				)
			),
			array(
				'controls' => array(
					array(
						'entityTypeName' => CCrmOwnerType::DealName,
						'typeName' => 'number',
						'configs' => array(
							array(
								'name' => 'deal_success',
								'title' => GetMessage('CRM_CH_TRACKER_WGT_DEAL_SUCCESS_SUM'),
								'dataPreset' => 'DEAL_SUM_STATS::OVERALL_SUM',
								'dataSource' => 'DEAL_SUM_STATS',
								'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
								'filter' => array('semanticID' => 'S'),
								'display' => array('colorScheme' => 'green'),
								'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N')
							)
						)
					),
					array(
						'entityTypeName' => CCrmOwnerType::DealName,
						'typeName' => 'number',
						'format' => array('isCurrency' => 'Y'),
						'configs' => array(
							array(
								'name' => 'deal_process',
								'title' => GetMessage('CRM_CH_TRACKER_WGT_DEAL_PROCESS_SUM'),
								'dataPreset' => 'DEAL_SUM_STATS::OVERALL_SUM',
								'dataSource' => 'DEAL_SUM_STATS',
								'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
								'filter' => array('semanticID' => 'P'),
								'display' => array('colorScheme' => 'blue'),
								'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N')
							)
						)
					)
				)
			)
		)
	),
	array(
		'height' => 380,
		'cells' => array(
			array(
				'controls' => array(
					array(
						'entityTypeName' => CCrmOwnerType::DealName,
						'typeName' => 'graph',
						'title' => GetMessage('CRM_CH_TRACKER_WGT_AMOUNT_OF_SALE'),
						'group' => 'DATE',
						'context' => 'F',
						'combineData' => 'Y',
						'format' => array('isCurrency' => 'Y'),
						'configs' => array(
							array(
								'name' => 'deal_success',
								'title' => GetMessage('CRM_CH_TRACKER_WGT_DEAL_SUCCESS_SUM'),
								'dataPreset' => 'DEAL_SUM_STATS::OVERALL_SUM',
								'dataSource' => 'DEAL_SUM_STATS',
								'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
								'filter' => array('semanticID' => 'S')
							)
						)
					)
				)
			)
		)
	)
);

if($isSupervisor)
{
	$rowData[] = array(
		'height' => 380,
		'cells' => array(
			array(
				'controls' => array(
					array(
						'entityTypeName' => CCrmOwnerType::DealName,
						'typeName' => 'bar',
						'title' => GetMessage('CRM_CH_TRACKER_WGT_DEAL_IN_WORK_BY_EMPLOYEE'),
						'group' => 'USER',
						'context' => 'F',
						'combineData' => 'Y',
						'enableStack' => 'N',
						'format' => array('isCurrency' => 'Y'),
						'configs' => array(
							array(
								'name' => 'deal_process',
								'title' => GetMessage('CRM_CH_TRACKER_WGT_DEAL_PROCESS_SUM'),
								'dataPreset' => 'DEAL_SUM_STATS::OVERALL_SUM',
								'dataSource' => 'DEAL_SUM_STATS',
								'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
								'filter' =>  array('semanticID' => 'P'),
								'display' => array('colorScheme' => 'blue')
							),
							array(
								'name' => 'deal_success',
								'title' => GetMessage('CRM_CH_TRACKER_WGT_DEAL_SUCCESS_SUM'),
								'dataPreset' => 'DEAL_SUM_STATS::OVERALL_SUM',
								'dataSource' => 'DEAL_SUM_STATS',
								'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
								'filter' => array('semanticID' => 'S'),
								'display' => array('colorScheme' => 'green')
							)
						)
					)
				)
			)
		)
	);
}
else
{
	$rowData[] = array(
		'height' => 380,
		'cells' => array(
			array(
				'controls' => array(
					array(
						'entityTypeName' => CCrmOwnerType::DealName,
						'typeName' => 'rating',
						'title' => GetMessage('CRM_CH_TRACKER_WGT_RATING_BY_SUCCESSFUL_DEALS'),
						'group' => 'USER',
						'nominee' => $currentUserID,
						'configs' => array(
							array(
								'name' => 'deal_success',
								'dataPreset' => 'DEAL_SUM_STATS::OVERALL_SUM',
								'dataSource' => 'DEAL_SUM_STATS',
								'select' => array('name' => 'SUM_TOTAL', 'aggregate' => 'SUM'),
								'filter' => array('semanticID' => 'S'),
								'format' => array('isCurrency' => 'Y', 'enableDecimals' => 'N')
							),
						)
					)
				)
			)
		)
	);
}

?><div class="bx-crm-view"><?
	$APPLICATION->IncludeComponent(
		'bitrix:crm.widget_panel',
		'',
		array(
			'GUID' => $arResult['WIDGET_GUID'],
			'LAYOUT' => 'L50R50',
			'ENABLE_NAVIGATION' => false,
			'ENTITY_TYPES' => array(
				CCrmOwnerType::ActivityName,
				CCrmOwnerType::LeadName,
				CCrmOwnerType::DealName,
				CCrmOwnerType::ContactName,
				CCrmOwnerType::CompanyName,
				CCrmOwnerType::InvoiceName
			),
			'DEFAULT_ENTITY_TYPE' => CCrmOwnerType::ActivityName,
			'PATH_TO_WIDGET' => isset($arResult['PATH_TO_LEAD_WIDGET']) ? $arResult['PATH_TO_LEAD_WIDGET'] : '',
			'PATH_TO_LIST' => isset($arResult['PATH_TO_LEAD_LIST']) ? $arResult['PATH_TO_LEAD_LIST'] : '',
			'PATH_TO_DEMO_DATA' => $_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.channel_tracker/templates/.default/widget',
			'IS_SUPERVISOR' => $isSupervisor,
			'ROWS' => $rowData,
			'DEMO_TITLE' => GetMessage('CRM_CH_TRACKER_WGT_DEMO_TITLE'),
			'DEMO_CONTENT' => '',
			'RENDER_HEAD_INTO_VIEW' => 'widget_panel_header',
		)
	);
?></div>
