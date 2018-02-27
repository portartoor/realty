<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
$APPLICATION->SetAdditionalCSS("/bitrix/components/bitrix/voximplant.main/templates/.default/telephony.css");

use Bitrix\Voximplant as VI;

function getBalance($amount)
{
	$amount = round(floatval($amount), 2);
	$amount = $amount.'';
	$str = '';
	$amountCount = strlen($amount);
	for ($i = 0; $i < $amountCount; $i++)
	{
		if ($amount[$i] == '.')
			$str .= '<span class="tel-num tel-num-point">.</span>';
		else
			$str .= '<span class="tel-num tel-num-'.$amount[$i].'">'.$amount[$i].'</span>';
	}

	return $str;
}
?>

<div class="tel-title"></div>
<div class="tel-inner">
	<div class="tel-inner-left">
		<div class="tel-balance">
			<table class="tel-balance-table">
				<tr>
					<td class="tel-balance-left">
						<?if(in_array($arResult['LANG'], Array('ua', 'kz'))):?>
						<div class="tel-balance-title"><?=GetMessage('TELEPHONY_BALANCE_2')?></div>
						<div class="tel-balance-sum-wrap">

						</div>
						<?else:?>
						<div class="tel-balance-title"><?=GetMessage('TELEPHONY_BALANCE')?></div>
						<div class="tel-balance-sum-wrap">
							<span class="tel-balance-box">
								<span class="tel-balance-box-inner">
									<?=getBalance($arResult['AMOUNT']);?>
								</span>
								<span class="tel-balance-box-line"></span>
							</span>
							<span class="tel-balance-sum-currency sum-currency-<?=strtoupper($arResult['CURRENCY']);?>"></span>
						</div>
						<?endif;?>
					</td>
					<td class="tel-balance-right">
						<div class="tel-balance-btn-wrap">
							<a href="?REFRESH" class="tel-balance-update-btn">
								<img class="tel-balance-update-loader" src="/bitrix/images/1.gif"/>
								<span class="tel-balance-update-btn-icon"></span>
								<span class="tel-balance-update-btn-text"><?=GetMessage('TELEPHONY_REFRESH')?></span>
							</a>
						</div>
						<div class="tel-balance-btn-wrap">
							<?if (in_array($arResult['LANG'], Array('ua', 'kz'))):?>
							<a href="<?=$arResult['LINK_TO_BUY']?>" target="_blank" class="tel-balance-update-btn tel-balance-update-btn2">
								<span class="tel-balance-update-btn-text"><?=GetMessage('TELEPHONY_TARIFFS')?></span>
							</a>
							<?elseif($arResult['LINK_TO_BUY']):?>
							<a href="<?=GetMessage('TELEPHONY_TARIFFS_LINK')?>" target="_blank" class="tel-balance-update-btn tel-balance-update-btn2">
								<span class="tel-balance-update-btn-text"><?=GetMessage('TELEPHONY_TARIFFS')?></span>
							</a>
							<?endif;?>
						</div>
						<div class="tel-balance-btn-wrap">
							<?if ($arResult['LINK_TO_BUY']):?>
								<a href="<?=$arResult['LINK_TO_BUY']?>" class="tel-balance-blue-btn"><?=GetMessage('TELEPHONY_PAY')?></a>
							<?else:?>
								<span onclick="alert('<?=CUtil::JSEscape(GetMessage('TELEPHONY_PAY_DISABLE'))?>')" class="tel-balance-update-btn tel-balance-update-btn2">
									<span class="tel-balance-update-btn-text"><?=GetMessage('TELEPHONY_PAY')?></span>
								</span>
							<?endif;?>
						</div>
					</td>
				</tr>
			</table>
		</div>
		<?
		if($arResult['SHOW_LINES'])
		{
		
			$APPLICATION->IncludeComponent(
				"bitrix:voximplant.regular_payments", 
				"", 
				Array(
					'AMOUNT' => $arResult['AMOUNT'], 
					'CURRENCY' => $arResult['CURRENCY'], 
					'LANG' => $arResult['LANG']
				)
			);
			$APPLICATION->IncludeComponent("bitrix:voximplant.sip_payments", "", array());
		}
		?>
	</div>

<!-- statistic-->
<?
if ($arResult['SHOW_STATISTICS'] && CModule::IncludeModule("currency"))
{
	$curPortalCurrency = "";

	$lastDay = ConvertTimeStamp(mktime(0, 0, 0, date("m"), date("d")+1, date("Y")));
	$firstDay = ConvertTimeStamp(MakeTimeStamp("01.".date("m").".".date("Y"), "DD.MM.YYYY"));

	$parameters = array(
		'order' => array('CALL_START_DATE'=>'DESC'),
		'filter' => array(
			'CALL_CATEGORY' => 'external',
			array(
				'LOGIC' => 'AND',
				'>CALL_START_DATE' => $firstDay,
				'<CALL_START_DATE' => $lastDay
			)
		),
		'select' => array('COST', 'COST_CURRENCY', 'CALL_DURATION'),
	);

	$costLastMonth = 0;
	$durationLastMonth = 0;
	$data = VI\StatisticTable::getList($parameters);
	while($arData = $data->fetch())
	{
		$arData["COST_CURRENCY"] = ($arData["COST_CURRENCY"] == "RUR" ? "RUB" : $arData["COST_CURRENCY"]);

		if (!$curPortalCurrency)
			$curPortalCurrency = $arData["COST_CURRENCY"];

		$costLastMonth += $arData["COST"];
		$durationLastMonth += $arData["CALL_DURATION"];
	}
	if ($durationLastMonth > 60)
	{
		$formatTimeMin = floor($durationLastMonth/60);
		$formatTimeSec = $durationLastMonth - $formatTimeMin*60;
		$durationLastMonth = $formatTimeMin." ".GetMessage("TELEPHONY_MIN");
		if ($formatTimeSec > 0)
			$durationLastMonth = $durationLastMonth." ".$formatTimeSec." ".GetMessage("TELEPHONY_SEC");
	}
	else
	{
		$durationLastMonth = $durationLastMonth." ".GetMessage("TELEPHONY_SEC");
	}

	if (!in_array($arResult['LANG'], Array('ua', 'kz')))
	{
		$costLastMonth = CCurrencyLang::CurrencyFormat($costLastMonth, $curPortalCurrency, true);
	}
	else
	{
		$costLastMonth = '';
	}

	$monthlyStat = CVoxImplantMain::GetTelephonyStatistic();

	$monthCount = 0;
	?>
	<div class="tel-inner-right">
		<div class="tel-history-block">
			<div class="tel-history-title"><?=GetMessage(!in_array($arResult['LANG'], Array('ua', 'kz'))? 'TELEPHONY_HISTORY_2': 'TELEPHONY_HISTORY_3')?></div>
			<div class="tel-history-block-info tel-history-block-info-current ">
				<strong><?=FormatDate("f", time());?> <?=date("Y")?></strong> &mdash; <?=$durationLastMonth?> <span class="tel-history-text-right"><?=$costLastMonth?></span>
			</div>
			<?if ($monthlyStat):
				foreach($monthlyStat as $year => $arYear)
				{
					if ($monthCount > 2)
						break;

					foreach($arYear as $month => $arMonth)
					{
						if ($monthCount > 2)
							break;


						if ($arMonth["CALL_DURATION"] > 60)
						{
							$formatTimeMin = floor($arMonth["CALL_DURATION"]/60);
							$formatTimeSec = $arMonth["CALL_DURATION"] - $formatTimeMin*60;
							$arMonth["CALL_DURATION"] = $formatTimeMin." ".GetMessage("TELEPHONY_MIN");
							if ($formatTimeSec > 0)
								$arMonth["CALL_DURATION"] = $arMonth["CALL_DURATION"]." ".$formatTimeSec." ".GetMessage("TELEPHONY_SEC");
						}
						else
						{
							$arMonth["CALL_DURATION"] = $arMonth["CALL_DURATION"]." ".GetMessage("TELEPHONY_SEC");
						}
						if (!in_array($arResult['LANG'], Array('ua', 'kz')))
						{
							$arMonth["COST_CURRENCY"] = ($arMonth["COST_CURRENCY"] == "RUR" ? "RUB" : $arMonth["COST_CURRENCY"]);

							if (!$curPortalCurrency)
								$curPortalCurrency = $arMonth["COST_CURRENCY"];

							$formatPrice = CCurrencyLang::CurrencyFormat($arMonth["COST"], $curPortalCurrency, true);
						}
						else
						{
							$formatPrice = '';
						}
					?>
						<div class="tel-history-block-info">
							<strong><?=GetMessage('TELEPHONY_MONTH_'.$month)?> <?=$year?></strong> &mdash; <?=$arMonth["CALL_DURATION"]?> <span class="tel-history-text-right"><?=$formatPrice?></span>
						</div>
					<?
						$monthCount++;
					}
				}
				?>
			<?endif?>

			<div class="tel-history-more">
				<a href="<?=CVoxImplantMain::GetPublicFolder()?>detail.php" class="tel-history-more-link"><?=GetMessage('TELEPHONY_DETAIL')?></a>
			</div>
		</div>
		<?if ($arResult['RECORD_LIMIT']['ENABLE'] && CModule::IncludeModule('bitrix24')):?>
		<?
			CBitrix24::initLicenseInfoPopupJS();
			$arResult["TRIAL_TEXT"] = CVoxImplantMain::GetTrialText();
		?>
		<div class="tel-history-block">
			<div class="tel-history-title"><?=GetMessage("VI_LOCK_RECORD_TITLE")?></div>
      		<?=GetMessage("VI_LOCK_RECORD_TEXT", Array("#LIMIT#" => '<b>'.$arResult['RECORD_LIMIT']['LIMIT'].'</b>', '#REMAINING#' => '<b>'.$arResult['RECORD_LIMIT']['REMAINING'].'</b>'))?>
			<div class="tel-history-more">
				<span class="tel-history-more-link" onclick="viOpenTrialPopup('vi_record')"><?=GetMessage("VI_LOCK_RECORD_LINK")?></span>
			</div>
		</div>
		<script type="text/javascript">
			function viOpenTrialPopup(dialogId)
			{
				B24.licenseInfoPopup.show(dialogId, "<?=CUtil::JSEscape($arResult["TRIAL_TEXT"]['TITLE'])?>", "<?=CUtil::JSEscape($arResult["TRIAL_TEXT"]['TEXT'])?>");
			}
		</script>
		<?endif?>
	</div><?
}

if (!empty($arResult['ERROR_MESSAGE']))
{
	?><script type="text/javascript">alert('<?=$arResult['ERROR_MESSAGE'];?>');</script><?
}
?>
</div>