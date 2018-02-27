<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
CModule::IncludeModule("crm");
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CCrmEntityPopupComponent extends CBitrixComponent
{
	/** @var int */
	private $entityTypeID = CCrmOwnerType::Undefined;
	/** @var int */
	private $entityID = 0;
	/** @var bool */
	private $isPermitted = false;

	/** @var HttpRequest  */
	protected $request = null;
	public function executeComponent()
	{
		$this->request = Main\Context::getCurrent()->getRequest();
		$this->entityTypeID = isset($this->request['ENTITY_TYPE_ID']) ? (int)$this->request['ENTITY_TYPE_ID'] : CCrmOwnerType::Undefined;
		$this->entityID = isset($this->request['ENTITY_ID']) ? (int)$this->request['ENTITY_ID'] : 0;

		$this->isPermitted = \CCrmOwnerType::CheckReadPermission($this->entityTypeID, $this->entityID);

		$this->arResult['IFRAME'] = true;
		//$this->arResult['IFRAME'] = isset($this->request['IFRAME']) && $this->request['IFRAME'] === 'Y';
		$this->arResult['IFRAME_USE_SCROLL'] = $this->request['IFRAME_USE_SCROLL'] == 'Y';

		$this->arResult['ENTITY_TYPE_ID'] = $this->entityTypeID;
		$this->arResult['ENTITY_TYPE_NAME'] = CCrmOwnerType::ResolveName($this->entityTypeID);
		$this->arResult['ENTITY_ID'] = $this->entityID;
		$this->arResult['IS_PERMITTED'] = $this->isPermitted;

		echo "<pre>", mydump($this->arResult), "</pre>";

		$this->includeComponentTemplate();
	}
}