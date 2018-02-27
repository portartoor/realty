<?php

namespace Bitrix\Disk\Uf;

use Bitrix\Disk\Ui;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

final class IblockElementConnector extends StubConnector
{
	public function canRead($userId)
	{
		if(!Loader::includeModule("iblock"))
		{
			return false;
		}

		$elementId = $this->entityId;
		$elementQuery = \CIBlockElement::getList(array(), array('ID' => $elementId), false, false, array('IBLOCK_ID'));
		$element = $elementQuery->fetch();
		if(!$element['IBLOCK_ID'])
		{
			return false;
		}

		return \CIBlockElementRights::userHasRightTo($element['IBLOCK_ID'], $elementId, "element_read");
	}

	public function canUpdate($userId)
	{
		return false;
	}
}
