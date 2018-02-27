<?php

namespace Bitrix\Voximplant\Integration;

use Bitrix\Main\Loader;
use Bitrix\Voximplant\ConfigTable;

/**
 * Class Im
 * Integration with bitrix im module.
 * @package Bitrix\Voximplant\Integration
 * @internal
 */
class Im
{
	/**
	 * Creates notification for portal admins of telephony events.
	 * @param string $notification Notification message. May contain BBCode.
	 * @return void.
	 */
	public static function notifyAdmins($notification, array $buttons = array())
	{
		if(!Loader::includeModule('im'))
			return;

		$admins = array();
		$cursor = \CAllGroup::GetGroupUserEx(1);
		while($user = $cursor->fetch())
		{
			$admins[] = $user["USER_ID"];
		}

		$messageFields = array(
			"FROM_USER_ID" => 0,
			"NOTIFY_TYPE" => IM_NOTIFY_SYSTEM,
			"NOTIFY_MODULE" => "voximplant",
			"NOTIFY_EVENT" => "notifications",
			"NOTIFY_TAG" => "TELEPHONY_NOTIFICATION",
			"NOTIFY_MESSAGE" => $notification,
			"NOTIFY_MESSAGE_OUT" => strip_tags($notification)
		);

		$attach = new \CIMMessageParamAttach();
		if(count($buttons) > 0)
		{
			foreach ($buttons as $button)
			{
				$attach->AddLink(array(
					"NAME" => $button['TEXT'],
					"LINK" => static::replaceLinkMacros($button['LINK'])
				));
			}

		}
		$messageFields['ATTACH'] = $attach;

		foreach ($admins as $adminId)
		{
			$message = $messageFields;
			$message['TO_USER_ID'] = $adminId;
			\CIMNotify::Add($message);
		}
	}

	protected static function replaceLinkMacros($link)
	{
		$replacements = array(
			'#BALANCE_TOP_UP#' =>  \CVoxImplantMain::GetBuyLink()
		);

		if(strpos($link, '#BASE_NUMBER_EDIT#') !== false)
		{
			$row = ConfigTable::getList(array(
				'select' => array('ID'),
				'filter' => array(
					'SEARCH_ID' =>  \CVoxImplantConfig::LINK_BASE_NUMBER
				)
			))->fetch();

			if($row != false)
			{
				$replacements['#BASE_NUMBER_EDIT#'] = \CVoxImplantMain::GetPublicFolder().'edit.php?ID='.$row['ID'];
			}
		}

		foreach($replacements as $search => $replacement)
		{
			$link = str_replace($search, $replacement, $link);
		}

		return $link;
	}
}