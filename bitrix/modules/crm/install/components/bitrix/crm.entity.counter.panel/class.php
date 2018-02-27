<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Counter\EntityCounterType;
use \Bitrix\Crm\Counter\EntityCounterFactory;

Loc::loadMessages(__FILE__);

class CCrmEntityCounterPanelComponent extends CBitrixComponent
{
	/** @var int */
	protected $userID = 0;
	/** @var string */
	protected $guid = '';
	/** @var string */
	protected $entityTypeName = '';
	/** @var int */
	protected $entityTypeID = \CCrmOwnerType::Undefined;
	/** @var array */
	protected $extras = array();
	/** @var string  */
	protected $entityListUrl = '';
	/** @var array */
	protected $errors = array();
	/** @var bool */
	protected $isVisible = true;
	/** @var bool  */
	protected $recalculate = false;

	public function executeComponent()
	{
		$this->initialize();
		if($this->isVisible)
		{
			foreach($this->errors as $message)
			{
				ShowError($message);
			}
			$this->includeComponentTemplate();
		}
	}
	protected function initialize()
	{
		if(isset($this->arParams['SHOW_STUB']) && $this->arParams['SHOW_STUB'] === 'Y')
		{
			$this->arResult['SHOW_STUB'] = true;
			return;
		}

		if (!Bitrix\Main\Loader::includeModule('crm'))
		{
			$this->errors[] = GetMessage('CRM_MODULE_NOT_INSTALLED');
			return;
		}

		$this->userID = CCrmSecurityHelper::GetCurrentUserID();
		$this->guid = $this->arResult['GUID'] = isset($this->arParams['GUID']) ? $this->arParams['GUID'] : 'counter_panel';
		if(isset($this->arParams['ENTITY_TYPE_NAME']))
		{
			$this->entityTypeName = $this->arParams['ENTITY_TYPE_NAME'];
		}
		$this->entityTypeID = CCrmOwnerType::ResolveID($this->entityTypeName);
		if(!CCrmOwnerType::IsDefined($this->entityTypeID))
		{
			$this->errors[] = GetMessage('CRM_COUNTER_ENTITY_TYPE_NOT_DEFINED');
			return;
		}

		if(!EntityCounterFactory::isEntityTypeSupported($this->entityTypeID))
		{
			$this->arResult['SHOW_STUB'] = true;
			return;
		}

		if(isset($this->arParams['EXTRAS']) && is_array($this->arParams['EXTRAS']))
		{
			$this->extras = $this->arParams['EXTRAS'];
		}

		if(isset($this->arParams['PATH_TO_ENTITY_LIST']))
		{
			$this->entityListUrl = $this->arParams['PATH_TO_ENTITY_LIST'];
		}

		$this->recalculate = isset($_REQUEST['recalc']) && strtoupper($_REQUEST['recalc']) === 'Y';

		$data = array();
		$total = 0;
		foreach(EntityCounterType::getAll() as $typeID)
		{
			$counter = EntityCounterFactory::create($this->entityTypeID, $typeID, $this->userID, $this->extras);
			$value = $counter->getValue($this->recalculate);
			if($value > 0)
			{
				$data[] = array(
					'TYPE_ID' => $typeID,
					'TYPE_NAME' => EntityCounterType::resolveName($typeID),
					'VALUE' => $value,
					'URL' => $counter->prepareDetailsPageUrl($this->entityListUrl)
				);
				$total += $value;
			}
		}

		$this->arResult['TOTAL'] = $total;
		$this->arResult['DATA'] = $data;
		if($total > 0)
		{
			$this->arResult['ENTITY_CAPTION'] = \Bitrix\Crm\MessageHelper::prepareEntityNumberDeclension($this->entityTypeID, $total);
		}
		else
		{
			/*
			 * Messages are used:
			 * CRM_COUNTER_DEAL_CAPTION
			 * CRM_COUNTER_LEAD_CAPTION
			 * CRM_COUNTER_CONTACT_CAPTION
			 * CRM_COUNTER_COMPANY_CAPTION
			 */
			$this->arResult['ENTITY_CAPTION'] = GetMessage("CRM_COUNTER_{$this->entityTypeName}_CAPTION");
			/*
			 * Messages are used:
			 * CRM_COUNTER_DEAL_STUB
			 * CRM_COUNTER_LEAD_STUB
			 * CRM_COUNTER_CONTACT_STUB
			 * CRM_COUNTER_COMPANY_STUB
			 */
			$this->arResult['STUB_MESSAGE'] = GetMessage("CRM_COUNTER_{$this->entityTypeName}_STUB");
		}
	}
}