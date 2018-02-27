<?php

namespace Bitrix\Voximplant\Ui;

use Bitrix\Main\Context;
use Bitrix\Main\Page\Asset;

class Helper
{
	public static function renderUserSelector($popupId, $searchInputId, $dataInputId, $componentName, $value, $siteId = '', $nameFormat = '', $delay = 0)
	{
		self::addScript('/bitrix/js/voximplant/common.js');
		Asset::getInstance()->addCss('/bitrix/components/bitrix/voximplant.main/templates/.default/telephony.css');

		$popupId = strval($popupId);
		$searchInputId = strval($searchInputId);
		$dataInputId = strval($dataInputId);
		$componentName = strval($componentName);

		$siteId = strval($siteId);
		if($siteId === '')
		{
			$siteId = SITE_ID;
		}

		$nameFormat = strval($nameFormat);
		if($nameFormat === '')
		{
			$nameFormat = \CSite::GetNameFormat(false);
		}

		$delay = intval($delay);
		if($delay < 0)
		{
			$delay = 0;
		}

		$value = intval($value);
		$userName = '';
		if($value > 0)
		{
			$dbResUser = \CUser::GetByID($value);
			$user = $dbResUser->Fetch();
			if(is_array($user))
			{
				$userName = \CUser::FormatName($nameFormat, $user, true, false);
			}
		}

		$result = '
			<span class="tel-filter-name-clean"></span>
			<input type="text" id="'.htmlspecialcharsbx($searchInputId).'" name="'.htmlspecialcharsbx($searchInputId).'" style="width:200px;">
			<input type="hidden" id="'.htmlspecialcharsbx($dataInputId).'" name="'.htmlspecialcharsbx($dataInputId).'" value="">
			<script>
				BX.ready(function(){
					BX.Voximplant.UserSelector.deletePopup("'.$popupId.'");
					BX.Voximplant.UserSelector.create(
						"'.$popupId.'", 
						{ 
							searchInput: BX("'.\CUtil::JSEscape($searchInputId).'"), 
							dataInput: BX("'.\CUtil::JSEscape($dataInputId).'"), 
							componentName: "'.\CUtil::JSEscape($componentName).'",
							user: '.($value > 0 ? '{id: '.$value.', name: "'.\CUtil::JSEscape($userName).'"}' : '{}').' 
						}, 
						'.$delay.'
					);
				});
			</script>
		';

		ob_start();
		$GLOBALS['APPLICATION']->IncludeComponent(
			'bitrix:intranet.user.selector.new',
			'',
			array(
				'MULTIPLE' => 'N',
				'NAME' => $componentName,
				'INPUT_NAME' => $searchInputId,
				'SHOW_EXTRANET_USERS' => 'NONE',
				'POPUP' => 'Y',
				'SITE_ID' => $siteId,
				'NAME_TEMPLATE' => $nameFormat
			),
			null,
			array('HIDE_ICONS' => 'Y')
		);
		$result .= ob_get_clean();
		return $result;
	}

	public static function addScript($link)
	{
		$url = trim(strtolower(strval($link)));
		if($url === '')
		{
			return false;
		}

		Asset::getInstance()->addJs($link);
		return true;
	}
}