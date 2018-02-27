<?php
if(!CModule::IncludeModule('rest'))
{
	return;
}


use Bitrix\Main;
use Bitrix\Rest\AccessException;
use Bitrix\Rest\RestException;
use Bitrix\Rest\UserFieldProxy;
use Bitrix\Crm\Integration\StorageFileType;
use Bitrix\Crm\Integration\StorageType;
use Bitrix\Crm\Integration\DiskManager;
use Bitrix\Crm\Integration\Bitrix24Manager;
use Bitrix\Crm\Color\PhaseColorSchemeElement;
use Bitrix\Crm\Color\DealStageColorScheme;
use Bitrix\Crm\Color\LeadStatusColorScheme;
use Bitrix\Crm\Color\QuoteStatusColorScheme;
use Bitrix\Crm\Category\DealCategory;
use Bitrix\Crm\Rest;
use Bitrix\Crm\Settings\RestSettings;
use Bitrix\Crm\EntityPreset;
use Bitrix\Crm\EntityRequisite;
use Bitrix\Crm\EntityBankDetail;
use Bitrix\Crm\EntityAddress;
use Bitrix\Crm\EntityAddressType;
use Bitrix\Crm\Binding\EntityBinding;
use Bitrix\Crm\Binding\DealContactTable;
use Bitrix\Crm\Binding\QuoteContactTable;
use Bitrix\Crm\Binding\ContactCompanyTable;

final class CCrmRestService extends IRestService
{
	const SCOPE_NAME = 'crm';
	private static $METHOD_NAMES = array(
		//region Status
		'crm.status.fields',
		'crm.status.add',
		'crm.status.get',
		'crm.status.list',
		'crm.status.update',
		'crm.status.delete',
		'crm.status.entity.types',
		'crm.status.entity.items',
		'crm.status.extra.fields',

		'crm.invoice.status.fields',
		'crm.invoice.status.add',
		'crm.invoice.status.get',
		'crm.invoice.status.list',
		'crm.invoice.status.update',
		'crm.invoice.status.delete',
		//endregion
		//region Enumeration
		'crm.enum.fields',
		'crm.enum.ownertype',
		'crm.enum.addresstype',
		'crm.enum.contenttype',
		'crm.enum.activitytype',
		'crm.enum.activitypriority',
		'crm.enum.activitydirection',
		'crm.enum.activitynotifytype',
		'crm.enum.activitystatus',
		//endregion
		//region Lead
		'crm.lead.fields',
		'crm.lead.add',
		'crm.lead.get',
		'crm.lead.list',
		'crm.lead.update',
		'crm.lead.delete',
		'crm.lead.productrows.set',
		'crm.lead.productrows.get',
		//endregion
		//region Deal
		'crm.deal.fields',
		'crm.deal.add',
		'crm.deal.get',
		'crm.deal.list',
		'crm.deal.update',
		'crm.deal.delete',
		'crm.deal.productrows.set',
		'crm.deal.productrows.get',
		'crm.deal.contact.fields',
		'crm.deal.contact.add',
		'crm.deal.contact.delete',
		'crm.deal.contact.items.get',
		'crm.deal.contact.items.set',
		'crm.deal.contact.items.delete',
		//endregion
		//region Deal Category
		'crm.dealcategory.fields',
		'crm.dealcategory.list',
		'crm.dealcategory.add',
		'crm.dealcategory.get',
		'crm.dealcategory.update',
		'crm.dealcategory.delete',
		'crm.dealcategory.status',
		'crm.dealcategory.stage.list',
		//endregion
		//region Company
		'crm.company.fields',
		'crm.company.add',
		'crm.company.get',
		'crm.company.list',
		'crm.company.update',
		'crm.company.delete',
		//endregion
		//region Contact
		'crm.contact.fields',
		'crm.contact.add',
		'crm.contact.get',
		'crm.contact.list',
		'crm.contact.update',
		'crm.contact.delete',
		'crm.contact.company.fields',
		'crm.contact.company.add',
		'crm.contact.company.delete',
		'crm.contact.company.items.get',
		'crm.contact.company.items.set',
		'crm.contact.company.items.delete',
		//endregion
		//region Currency
		'crm.currency.fields',
		'crm.currency.add',
		'crm.currency.get',
		'crm.currency.list',
		'crm.currency.update',
		'crm.currency.delete',
		'crm.currency.localizations.fields',
		'crm.currency.localizations.get',
		'crm.currency.localizations.set',
		'crm.currency.localizations.delete',
		//endregion
		//region Catalog
		'crm.catalog.fields',
		'crm.catalog.get',
		'crm.catalog.list',
		//endregion
		//region Product
		'crm.product.fields',
		'crm.product.add',
		'crm.product.get',
		'crm.product.list',
		'crm.product.update',
		'crm.product.delete',
		//endregion
		//region Product Property
		'crm.product.property.types',
		'crm.product.property.fields',
		'crm.product.property.settings.fields',
		'crm.product.property.enumeration.fields',
		'crm.product.property.add',
		'crm.product.property.get',
		'crm.product.property.list',
		'crm.product.property.update',
		'crm.product.property.delete',
		//endregion
		//region Product Section
		'crm.productsection.fields',
		'crm.productsection.add',
		'crm.productsection.get',
		'crm.productsection.list',
		'crm.productsection.update',
		'crm.productsection.delete',
		//endregion
		//region Product Row
		'crm.productrow.fields',
		'crm.productrow.add',
		'crm.productrow.get',
		'crm.productrow.list',
		'crm.productrow.update',
		'crm.productrow.delete',
		//endregion
		//region Activity
		'crm.activity.fields',
		'crm.activity.add',
		'crm.activity.get',
		'crm.activity.list',
		'crm.activity.update',
		'crm.activity.delete',
		'crm.activity.communication.fields',
		//endregion
		//region Quote
		'crm.quote.fields',
		'crm.quote.add',
		'crm.quote.get',
		'crm.quote.list',
		'crm.quote.update',
		'crm.quote.delete',
		'crm.quote.productrows.set',
		'crm.quote.productrows.get',
		'crm.quote.contact.fields',
		'crm.quote.contact.add',
		'crm.quote.contact.delete',
		'crm.quote.contact.items.get',
		'crm.quote.contact.items.set',
		'crm.quote.contact.items.delete',
		//endregion
		//region Requisite
		'crm.requisite.fields',
		'crm.requisite.add',
		'crm.requisite.get',
		'crm.requisite.list',
		'crm.requisite.update',
		'crm.requisite.delete',
		//
		'crm.requisite.userfield.add',
		'crm.requisite.userfield.get',
		'crm.requisite.userfield.list',
		'crm.requisite.userfield.update',
		'crm.requisite.userfield.delete',
		//
		'crm.requisite.preset.fields',
		'crm.requisite.preset.add',
		'crm.requisite.preset.get',
		'crm.requisite.preset.list',
		'crm.requisite.preset.update',
		'crm.requisite.preset.delete',
		'crm.requisite.preset.countries',
		//
		'crm.requisite.preset.field.fields',
		'crm.requisite.preset.field.availabletoadd',
		'crm.requisite.preset.field.add',
		'crm.requisite.preset.field.get',
		'crm.requisite.preset.field.list',
		'crm.requisite.preset.field.update',
		'crm.requisite.preset.field.delete',
		//
		'crm.requisite.bankdetail.fields',
		'crm.requisite.bankdetail.add',
		'crm.requisite.bankdetail.get',
		'crm.requisite.bankdetail.list',
		'crm.requisite.bankdetail.update',
		'crm.requisite.bankdetail.delete',
		//
		'crm.requisite.link.fields',
		'crm.requisite.link.list',
		'crm.requisite.link.get',
		'crm.requisite.link.register',
		'crm.requisite.link.unregister',
		//
		'crm.address.fields',
		'crm.address.add',
		'crm.address.update',
		'crm.address.list',
		'crm.address.delete',
		//endregion Requisite
		//region Measures
		'crm.measure.fields',
		'crm.measure.add',
		'crm.measure.get',
		'crm.measure.list',
		'crm.measure.update',
		'crm.measure.delete',
		//endregion Measures

		//region User Field
		'crm.lead.userfield.add',
		'crm.lead.userfield.get',
		'crm.lead.userfield.list',
		'crm.lead.userfield.update',
		'crm.lead.userfield.delete',

		'crm.deal.userfield.add',
		'crm.deal.userfield.get',
		'crm.deal.userfield.list',
		'crm.deal.userfield.update',
		'crm.deal.userfield.delete',

		'crm.company.userfield.add',
		'crm.company.userfield.get',
		'crm.company.userfield.list',
		'crm.company.userfield.update',
		'crm.company.userfield.delete',

		'crm.contact.userfield.add',
		'crm.contact.userfield.get',
		'crm.contact.userfield.list',
		'crm.contact.userfield.update',
		'crm.contact.userfield.delete',

		'crm.quote.userfield.add',
		'crm.quote.userfield.get',
		'crm.quote.userfield.list',
		'crm.quote.userfield.update',
		'crm.quote.userfield.delete',

		'crm.invoice.userfield.add',
		'crm.invoice.userfield.get',
		'crm.invoice.userfield.list',
		'crm.invoice.userfield.update',
		'crm.invoice.userfield.delete',

		'crm.userfield.fields',
		'crm.userfield.types',
		'crm.userfield.enumeration.fields',
		'crm.userfield.settings.fields',
		//endregion

		//region Externalchannel connector.
		'crm.externalchannel.connector.fields',
		'crm.externalchannel.connector.list',
		'crm.externalchannel.connector.register',
		'crm.externalchannel.connector.unregister',
		//endregion

		//region Misc.
		'crm.multifield.fields',
		'crm.duplicate.findbycomm',
		'crm.livefeedmessage.add',
		'crm.externalchannel.company',
		'crm.externalchannel.contact',
		'crm.externalchannel.activity.company',
		'crm.externalchannel.activity.contact',
		'crm.webform.configuration.get',
		'crm.sitebutton.configuration.get',
		'crm.persontype.fields',
		'crm.persontype.list',
		'crm.paysystem.fields',
		'crm.paysystem.list',
		//endregion
		//region Automation
		'crm.automation.trigger'
		//endregion
	);
	private static $PLACEMENT_NAMES = array(
		'CRM_LEAD_LIST_MENU',
		'CRM_DEAL_LIST_MENU',
		'CRM_INVOICE_LIST_MENU',
		'CRM_QUOTE_LIST_MENU',
		'CRM_CONTACT_LIST_MENU',
		'CRM_COMPANY_LIST_MENU',
		'CRM_ACTIVITY_LIST_MENU',
	);
	private static $DESCRIPTION = null;
	private static $PROXIES = array();

	public static function onRestServiceBuildDescription()
	{
		if(!self::$DESCRIPTION)
		{
			$bindings = array();
			// There is one entry point
			$callback = array('CCrmRestService', 'onRestServiceMethod');
			foreach(self::$METHOD_NAMES as $name)
			{
				$bindings[$name] = $callback;
			}

			$bindings[\CRestUtil::PLACEMENTS] = array();
			foreach(self::$PLACEMENT_NAMES as $name)
			{
				$bindings[\CRestUtil::PLACEMENTS][$name] = array();
			}

			CCrmLeadRestProxy::registerEventBindings($bindings);
			CCrmDealRestProxy::registerEventBindings($bindings);
			CCrmCompanyRestProxy::registerEventBindings($bindings);
			CCrmContactRestProxy::registerEventBindings($bindings);
			CCrmQuoteRestProxy::registerEventBindings($bindings);
			CCrmCurrencyRestProxy::registerEventBindings($bindings);
			CCrmProductRestProxy::registerEventBindings($bindings);
			CCrmActivityRestProxy::registerEventBindings($bindings);

			self::$DESCRIPTION = array('crm' => $bindings);
		}

		return self::$DESCRIPTION;
	}
	public static function onRestServiceMethod($arParams, $nav, CRestServer $server)
	{
		if(!CCrmPerms::IsAccessEnabled())
		{
			throw new RestException('Access denied.');
		}

		$methodName = $server->getMethod();

		$parts = explode('.', $methodName);
		$partCount = count($parts);
		if($partCount < 3 || $parts[0] !== 'crm')
		{
			throw new RestException("Method '{$methodName}' is not supported in current context.");
		}

		$typeName = strtoupper($parts[1]);
		$proxy = null;

		if(isset(self::$PROXIES[$typeName]))
		{
			$proxy = self::$PROXIES[$typeName];
		}

		if(!$proxy)
		{
			if($typeName === 'ENUM')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmEnumerationRestProxy();
			}
			elseif($typeName === 'MULTIFIELD')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmMultiFieldRestProxy();
			}
			elseif($typeName === 'CURRENCY')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmCurrencyRestProxy();
			}
			elseif($typeName === 'CATALOG')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmCatalogRestProxy();
			}
			elseif($typeName === 'PRODUCT' && strtoupper($parts[2]) === 'PROPERTY')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmProductPropertyRestProxy();
			}
			elseif($typeName === 'PRODUCT')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmProductRestProxy();
			}
			elseif($typeName === 'PRODUCTSECTION')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmProductSectionRestProxy();
			}
			elseif($typeName === 'PRODUCTROW')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmProductRowRestProxy();
			}
			elseif($typeName === 'STATUS')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmStatusRestProxy();
			}
			elseif($typeName === 'LEAD')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmLeadRestProxy();
			}
			elseif($typeName === 'DEAL')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmDealRestProxy();
			}
			elseif($typeName === 'DEALCATEGORY')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmDealCategoryProxy();
			}
			elseif($typeName === 'COMPANY')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmCompanyRestProxy();
			}
			elseif($typeName === 'CONTACT')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmContactRestProxy();
			}
			elseif($typeName === 'QUOTE')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmQuoteRestProxy();
			}
			elseif($typeName === 'INVOICE' && strtoupper($parts[2]) === 'STATUS')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmStatusInvoiceRestProxy();
			}
			elseif($typeName === 'INVOICE')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmInvoiceRestProxy();
			}
			elseif($typeName === 'REQUISITE')
			{
				if(strtoupper($parts[2]) === 'PRESET')
				{
					if(strtoupper($parts[3]) === 'FIELD')
					{
						$proxy = self::$PROXIES[$typeName] = new CCrmRequisitePresetFieldRestProxy();
					}
					else
					{
						$proxy = self::$PROXIES[$typeName] = new CCrmRequisitePresetRestProxy();
					}
				}
				elseif(strtoupper($parts[2]) === 'BANKDETAIL')
				{
					$proxy = self::$PROXIES[$typeName] = new CCrmRequisiteBankDetailRestProxy();
				}
				elseif(strtoupper($parts[2]) === 'LINK')
				{
					$proxy = self::$PROXIES[$typeName] = new CCrmRequisiteLinkRestProxy();
				}
				else
				{
					$proxy = self::$PROXIES[$typeName] = new CCrmRequisiteRestProxy();
				}

			}
			elseif($typeName === 'ADDRESS')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmAddressRestProxy();
			}
			elseif($typeName === 'ACTIVITY')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmActivityRestProxy();
			}
			elseif($typeName === 'DUPLICATE')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmDuplicateRestProxy();
			}
			elseif($typeName === 'LIVEFEEDMESSAGE')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmLiveFeedMessageRestProxy();
			}
			elseif($typeName === 'USERFIELD')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmUserFieldRestProxy(CCrmOwnerType::Undefined);
			}
			elseif($typeName === 'EXTERNALCHANNEL')
			{
				if(strtoupper($parts[2]) === 'CONNECTOR')
				{
					$proxy = self::$PROXIES[$typeName] = new CCrmExternalChannelConnectorRestProxy();
				}
				else
				{
					$proxy = self::$PROXIES[$typeName] = new CCrmExternalChannelRestProxy();
				}
			}
			elseif($typeName === 'WEBFORM')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmWebformRestProxy();
			}
			elseif($typeName === 'SITEBUTTON')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmSiteButtonRestProxy();
			}
			elseif($typeName === 'PERSONTYPE')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmPersonTypeRestProxy();
			}
			elseif($typeName === 'PAYSYSTEM')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmPaySystemRestProxy();
			}
			elseif($typeName === 'MEASURE')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmMeasureRestProxy();
			}
			elseif($typeName === 'AUTOMATION')
			{
				$proxy = self::$PROXIES[$typeName] = new CCrmAutomationRestProxy();
			}
			else
			{
				throw new RestException("Could not find proxy for method '{$methodName}'.");
			}
			$proxy->setServer($server);
		}
		return $proxy->processMethodRequest(
			$parts[2],
			$partCount > 3 ? array_slice($parts, 3) : array(),
			$arParams,
			$nav,
			$server
		);
	}
	public static function getNavData($start, $isOrm = false)
	{
		return parent::getNavData($start, $isOrm);
	}
	public static function setNavData($result, $dbRes)
	{
		return parent::setNavData($result, $dbRes);
	}
}

class CCrmRestHelper
{
	public static function resolveEntityID(array &$arParams)
	{
		return isset($arParams['ID']) ? (int)$arParams['ID'] : (isset($arParams['id']) ? (int)$arParams['id'] : 0);
	}
	public static function resolveArrayParam(array &$arParams, $name, array $default = null)
	{
		if(isset($arParams[$name]))
		{
			return $arParams[$name];
		}

		// Check for upper case notation (FILTER, SORT, SELECT, etc)
		$upper = strtoupper($name);
		if(isset($arParams[$upper]))
		{
			return $arParams[$upper];
		}

		// Check for lower case notation (filter, sort, select, etc)
		$lower = strtolower($name);
		if(isset($arParams[$lower]))
		{
			return $arParams[$lower];
		}

		// Check for capitalized notation (Filter, Sort, Select, etc)
		$capitalized = ucfirst($lower);
		if(isset($arParams[$capitalized]))
		{
			return $arParams[$capitalized];
		}

		// Check for hungary notation (arFilter, arSort, arSelect, etc)
		$hungary = "ar{$capitalized}";
		if(isset($arParams[$hungary]))
		{
			return $arParams[$hungary];
		}

		return $default;
	}
	public static function resolveParam(array &$arParams, $name, $default = null)
	{
		if(isset($arParams[$name]))
		{
			return $arParams[$name];
		}

		// Check for lower case notation (type, etc)
		$lower = strtolower($name);
		if(isset($arParams[$lower]))
		{
			return $arParams[$lower];
		}

		// Check for upper case notation (TYPE, etc)
		$upper = strtoupper($name);
		if(isset($arParams[$upper]))
		{
			return $arParams[$upper];
		}

		// Check for capitalized notation (Type, etc)
		$capitalized = ucfirst($lower);
		if(isset($arParams[$capitalized]))
		{
			return $arParams[$capitalized];
		}

		return $default;
	}
	public static function prepareFieldInfos(array &$fieldsInfo)
	{
		$result = array();

		foreach($fieldsInfo as $fieldID => &$fieldInfo)
		{
			$attrs = isset($fieldInfo['ATTRIBUTES']) ? $fieldInfo['ATTRIBUTES'] : array();
			// Skip hidden fields
			if(in_array(CCrmFieldInfoAttr::Hidden, $attrs, true))
			{
				continue;
			}

			$fieldType = $fieldInfo['TYPE'];
			$field = array(
				'type' => $fieldType,
				'isRequired' => in_array(CCrmFieldInfoAttr::Required, $attrs, true),
				'isReadOnly' => in_array(CCrmFieldInfoAttr::ReadOnly, $attrs, true),
				'isImmutable' => in_array(CCrmFieldInfoAttr::Immutable, $attrs, true),
				'isMultiple' => in_array(CCrmFieldInfoAttr::Multiple, $attrs, true),
				'isDynamic' => in_array(CCrmFieldInfoAttr::Dynamic, $attrs, true)
			);

			if(in_array(CCrmFieldInfoAttr::Deprecated, $attrs, true))
			{
				$field['isDeprecated'] = true;
			}

			if($fieldType === 'enumeration')
			{
				$field['items'] = isset($fieldInfo['ITEMS']) ? $fieldInfo['ITEMS'] : array();
			}
			elseif($fieldType === 'crm_status')
			{
				$field['statusType'] = isset($fieldInfo['CRM_STATUS_TYPE']) ? $fieldInfo['CRM_STATUS_TYPE'] : '';
			}
			elseif ($fieldType === 'product_property')
			{
				$field['propertyType'] = isset($fieldInfo['PROPERTY_TYPE']) ? $fieldInfo['PROPERTY_TYPE'] : '';
				$field['userType'] = isset($fieldInfo['USER_TYPE']) ? $fieldInfo['USER_TYPE'] : '';
				$field['title'] = isset($fieldInfo['NAME']) ? $fieldInfo['NAME'] : '';
				if ($field['propertyType'] === 'L')
					$field['values'] = isset($fieldInfo['VALUES']) ? $fieldInfo['VALUES'] : array();
			}

			if(isset($fieldInfo['LABELS']) && is_array($fieldInfo['LABELS']))
			{
				$labels = $fieldInfo['LABELS'];
				if(isset($labels['LIST']))
				{
					$field['listLabel'] = $labels['LIST'];
				}
				if(isset($labels['FORM']))
				{
					$field['formLabel'] = $labels['FORM'];
				}
				if(isset($labels['FILTER']))
				{
					$field['filterLabel'] = $labels['FILTER'];
				}
			}

			$result[$fieldID] = &$field;
			unset($field);
		}
		unset($fieldInfo);

		return $result;
	}
}

interface ICrmRestProxy
{
	/**
	 * Set REST-server
	 * @param CRestServer $server
	 */
	public function setServer(CRestServer $server);
	/**
	 * Get REST-server
	 * @return CRestServer
	 */
	public function getServer();
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
}

abstract class CCrmRestProxyBase implements ICrmRestProxy
{
	private $currentUser = null;
	private $webdavSettings = null;
	private $webdavIBlock = null;
	/** @var CRestServer  */
	private $server = null;
	private $sanitizer = null;
	private static $MULTIFIELD_TYPE_IDS = null;
	public function getFields()
	{
		$fildsInfo = $this->getFieldsInfo();
		return self::prepareFields($fildsInfo);
	}
	public function isValidID($ID)
	{
		return is_int($ID) && $ID > 0;
	}
	public function add(&$fields, array $params = null)
	{
		$this->internalizeFields($fields, $this->getFieldsInfo(), array());

		$errors = array();
		$result = $this->innerAdd($fields, $errors, $params);
		if(!$this->isValidID($result))
		{
			throw new RestException(implode("\n", $errors));
		}

		return $result;
	}
	public function get($ID)
	{
		if(!$this->checkEntityID($ID))
		{
			throw new RestException('ID is not defined or invalid.');
		}


		$errors = array();
		$result = $this->innerGet($ID, $errors);
		if(!is_array($result))
		{
			throw new RestException(implode("\n", $errors));
		}
		$this->externalizeFields($result, $this->getFieldsInfo());
		return $result;

	}
	public function getList($order, $filter, $select, $start)
	{
		$this->prepareListParams($order, $filter, $select);

		$navigation = CCrmRestService::getNavData($start);

		$enableMultiFields = false;
		$selectedFmTypeIDs = array();
		if(is_array($select) && !empty($select))
		{
			$supportedFmTypeIDs = $this->getSupportedMultiFieldTypeIDs();

			if(is_array($supportedFmTypeIDs) && !empty($supportedFmTypeIDs))
			{
				foreach($supportedFmTypeIDs as $fmTypeID)
				{
					if(in_array($fmTypeID, $select, true))
					{
						$selectedFmTypeIDs[] = $fmTypeID;
					}
				}
			}
			$enableMultiFields = !empty($selectedFmTypeIDs);
			if($enableMultiFields)
			{
				$identityFieldName = $this->getIdentityFieldName();
				if($identityFieldName === '')
				{
					throw new RestException('Could not find identity field name.');
				}

				if(!in_array($identityFieldName, $select, true))
				{
					$select[] = $identityFieldName;
				}
			}
		}

		$this->internalizeFilterFields($filter, $this->getFieldsInfo());
		$errors = array();
		$result = $this->innerGetList($order, $filter, $select, $navigation, $errors);

		if($result instanceOf CDBResult)
		{
			return $this->prepareListFromDbResult(
				$result,
				array('SELECTED_FM_TYPES' => $selectedFmTypeIDs)
			);
		}
		elseif(is_array($result))
		{
			return $this->prepareListFromArray(
				$result,
				array('SELECTED_FM_TYPES' => $selectedFmTypeIDs)
			);
		}

		if(empty($errors))
		{
			$errors[] = "Failed to get list. General error.";
		}

		throw new RestException(implode("\n", $errors));
	}
	protected function prepareListFromDbResult(CDBResult $dbResult, array $options)
	{
		$result = array();
		$fieldsInfo = $this->getFieldsInfo();

		$selectedFmTypeIDs = isset($options['SELECTED_FM_TYPES']) ? $options['SELECTED_FM_TYPES'] : array();
		if(empty($selectedFmTypeIDs))
		{
			while($fields = $dbResult->Fetch())
			{
				$this->prepareListItemFields($fields);

				$this->externalizeFields($fields, $fieldsInfo);
				$result[] = $fields;
			}
		}
		else
		{
			$entityMap = array();
			while($fields = $dbResult->Fetch())
			{
				$this->prepareListItemFields($fields);

				$entityID = intval($this->getIdentity($fields));
				if($entityID <= 0)
				{
					throw new RestException('Could not find entity ID.');
				}
				$entityMap[$entityID] = $fields;
			}

			$this->prepareListItemMultiFields($entityMap, $this->getOwnerTypeID(), $selectedFmTypeIDs);

			foreach($entityMap as &$fields)
			{
				$this->externalizeFields($fields, $fieldsInfo);
				$result[] = $fields;
			}
			unset($fields);
		}

		return CCrmRestService::setNavData($result, $dbResult);
	}
	protected function prepareListFromArray(array $list, array $options)
	{
		$result = array();
		$fieldsInfo = $this->getFieldsInfo();

		$selectedFmTypeIDs = isset($options['SELECTED_FM_TYPES']) ? $options['SELECTED_FM_TYPES'] : array();
		if(empty($selectedFmTypeIDs))
		{
			foreach($list as $fields)
			{
				$this->prepareListItemFields($fields);

				$this->externalizeFields($fields, $fieldsInfo);
				$result[] = $fields;
			}
		}
		else
		{
			$entityMap = array();
			foreach($list as $fields)
			{
				$this->prepareListItemFields($fields);

				$entityID = intval($this->getIdentity($fields));
				if($entityID <= 0)
				{
					throw new RestException('Could not find entity ID.');
				}
				$entityMap[$entityID] = $fields;
			}

			$this->prepareListItemMultiFields($entityMap, $this->getOwnerTypeID(), $selectedFmTypeIDs);

			foreach($entityMap as &$fields)
			{
				$this->externalizeFields($fields, $fieldsInfo);
				$result[] = $fields;
			}
			unset($fields);
		}

		return CCrmRestService::setNavData($result, array('offset' => 0, 'count' => count($result)));
	}
	public function update($ID, &$fields, array $params = null)
	{
		if(!$this->checkEntityID($ID))
		{
			throw new RestException('ID is not defined or invalid.');
		}

		$this->internalizeFields(
			$fields,
			$this->getFieldsInfo(),
			array(
				'IGNORED_ATTRS' => array(
					CCrmFieldInfoAttr::Immutable,
					CCrmFieldInfoAttr::UserPKey
				)
			)
		);

		$errors = array();
		$result = $this->innerUpdate($ID, $fields, $errors, $params);
		if($result !== true)
		{
			throw new RestException(implode("\n", $errors));
		}

		return $result;
	}
	public function delete($ID, array $params = null)
	{
		if(!$this->checkEntityID($ID))
		{
			throw new RestException('ID is not defined or invalid.');
		}

		$errors = array();
		$result = $this->innerDelete($ID, $errors, $params);
		if($result !== true)
		{
			throw new RestException(implode("\n", $errors));
		}

		return $result;
	}
	protected function prepareListParams(&$order, &$filter, &$select)
	{
	}
	protected function prepareListItemFields(&$fields)
	{
	}
	protected function getCurrentUser()
	{
		return $this->currentUser !== null
			? $this->currentUser
			: ($this->currentUser = CCrmSecurityHelper::GetCurrentUser());
	}
	protected function getCurrentUserID()
	{
		return $this->getCurrentUser()->GetID();
	}
	public function getServer()
	{
		return $this->server;
	}
	public function setServer(CRestServer $server)
	{
		$this->server = $server;
	}
	public function getOwnerTypeID()
	{
		return CCrmOwnerType::Undefined;
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$ownerTypeID = $this->getOwnerTypeID();

		$name = strtoupper($name);
		if($name === 'FIELDS')
		{
			return $this->getFields();
		}
		elseif($name === 'ADD')
		{
			$fields = $this->resolveArrayParam($arParams, 'fields');
			if(!is_array($fields))
			{
				throw new RestException("Parameter 'fields' must be array.");
			}

			$methodParams = $this->resolveArrayParam($arParams, 'params');
			if(!is_array($methodParams))
			{
				throw new RestException("Parameter 'params' must be array.");
			}

			return $this->add($fields, $methodParams);
		}
		elseif($name === 'GET')
		{
			return $this->get($this->resolveEntityID($arParams));
		}
		elseif($name === 'LIST')
		{
			$order = $this->resolveArrayParam($arParams, 'order');
			if(!is_array($order))
			{
				throw new RestException("Parameter 'order' must be array.");
			}

			$filter = $this->resolveArrayParam($arParams, 'filter');
			if(!is_array($filter))
			{
				throw new RestException("Parameter 'filter' must be array.");
			}

			$select = $this->resolveArrayParam($arParams, 'select');
			return $this->getList($order, $filter, $select, $nav);
		}
		elseif($name === 'UPDATE')
		{
			$ID = $this->resolveEntityID($arParams);

			$fields = $fields = $this->resolveArrayParam($arParams, 'fields');
			if(!is_array($fields))
			{
				throw new RestException("Parameter 'fields' must be array.");
			}

			$methodParams = $this->resolveArrayParam($arParams, 'params');
			if(!is_array($methodParams))
			{
				throw new RestException("Parameter 'params' must be array.");
			}

			return $this->update($ID, $fields, $methodParams);
		}
		elseif($name === 'DELETE')
		{
			$ID = $this->resolveEntityID($arParams);
			$methodParams = $this->resolveArrayParam($arParams, 'params');
			return $this->delete($ID, $methodParams);
		}
		elseif($name === 'USERFIELD' && $ownerTypeID !== CCrmOwnerType::Undefined)
		{
			$ufProxy = new CCrmUserFieldRestProxy($ownerTypeID);

			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if($nameSuffix === 'ADD')
			{
				$fields = $this->resolveArrayParam($arParams, 'fields', null);
				return $ufProxy->add(is_array($fields) ? $fields : $arParams);
			}
			elseif($nameSuffix === 'GET')
			{
				return $ufProxy->get($this->resolveParam($arParams, 'id', ''));
			}
			elseif($nameSuffix === 'LIST')
			{
				$order = $this->resolveArrayParam($arParams, 'order', array());
				if(!is_array($order))
				{
					throw new RestException("Parameter 'order' must be array.");
				}

				$filter = $this->resolveArrayParam($arParams, 'filter', array());
				if(!is_array($filter))
				{
					throw new RestException("Parameter 'filter' must be array.");
				}

				return $ufProxy->getList($order, $filter);
			}
			elseif($nameSuffix === 'UPDATE')
			{
				return $ufProxy->update(
					$this->resolveParam($arParams, 'id'),
					$this->resolveArrayParam($arParams, 'fields')
				);
			}
			elseif($nameSuffix === 'DELETE')
			{
				return $ufProxy->delete($this->resolveParam($arParams, 'id', ''));
			}
		}

		throw new RestException("Resource '{$name}' is not supported in current context.");
	}
	protected function resolveParam(&$arParams, $name)
	{
		return CCrmRestHelper::resolveParam($arParams, $name, '');
	}
	protected function resolveMultiPartParam(&$arParams, array $nameParts, $default = '')
	{
		if(empty($nameParts))
		{
			return $default;
		}

		$upperUnderscoreName = strtoupper(implode('_', $nameParts));
		if(isset($arParams[$upperUnderscoreName]))
		{
			return $arParams[$upperUnderscoreName];
		}

		$lowerUnderscoreName = strtolower($upperUnderscoreName);
		if(isset($arParams[$lowerUnderscoreName]))
		{
			return $arParams[$lowerUnderscoreName];
		}

		$hungaryName = '';
		foreach($nameParts as $namePart)
		{
			$hungaryName .= ucfirst($namePart);
		}

		if(isset($arParams[$hungaryName]))
		{
			return $arParams[$hungaryName];
		}

		$hungaryName = "ar{$hungaryName}";
		if(isset($arParams[$hungaryName]))
		{
			return $arParams[$hungaryName];
		}

		return $default;
	}
	protected function resolveArrayParam(&$arParams, $name, $default = array())
	{
		return CCrmRestHelper::resolveArrayParam($arParams, $name, $default);
	}
	protected function resolveEntityID(&$arParams)
	{
		return CCrmRestHelper::resolveEntityID($arParams);
	}
	protected function resolveRelationID(&$arParams, $relationName)
	{
		$nameLowerCase = strtolower($relationName);
		// Check for camel case (entityId or entityID)
		$camel = "{$nameLowerCase}Id";
		if(isset($arParams[$camel]))
		{
			return $arParams[$camel];
		}

		$camel = "{$nameLowerCase}ID";
		if(isset($arParams[$camel]))
		{
			return $arParams[$camel];
		}

		// Check for lower case (entity_id)
		$lower = "{$nameLowerCase}_id";
		if(isset($arParams[$lower]))
		{
			return $arParams[$lower];
		}

		// Check for upper case (ENTITY_ID)
		$upper = strtoupper($lower);
		if(isset($arParams[$upper]))
		{
			return $arParams[$upper];
		}

		return '';
	}
	protected function checkEntityID($ID)
	{
		return is_int($ID) && $ID > 0;
	}

	protected function getAuthToken()
	{
		if(!$this->server)
		{
			return '';
		}

		$auth = $this->server->getAuth();
		return is_array($auth) && isset($auth['auth']) ? $auth['auth'] : '';
	}

	protected static function prepareMultiFieldsInfo(&$fieldsInfo)
	{
		$typesID = array_keys(CCrmFieldMulti::GetEntityTypeInfos());
		foreach($typesID as $typeID)
		{
			$fieldsInfo[$typeID] = array(
				'TYPE' => 'crm_multifield',
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple)
			);
		}
	}
	protected static function prepareUserFieldsInfo(&$fieldsInfo, $entityTypeID)
	{
		$userType = new CCrmUserType($GLOBALS['USER_FIELD_MANAGER'], $entityTypeID);
		$userType->PrepareFieldsInfo($fieldsInfo);
	}
	protected static function prepareFields(array &$fieldsInfo)
	{
		return CCrmRestHelper::prepareFieldInfos($fieldsInfo);
	}
	protected function internalizeFields(&$fields, &$fieldsInfo, $options = array())
	{

		if(!is_array($fields))
		{
			return;
		}

		if(!is_array($options))
		{
			$options = array();
		}

		$ignoredAttrs = isset($options['IGNORED_ATTRS']) ? $options['IGNORED_ATTRS'] : array();
		if(!in_array(CCrmFieldInfoAttr::Hidden, $ignoredAttrs, true))
		{
			$ignoredAttrs[] = CCrmFieldInfoAttr::Hidden;
		}
		if(!in_array(CCrmFieldInfoAttr::ReadOnly, $ignoredAttrs, true))
		{
			$ignoredAttrs[] = CCrmFieldInfoAttr::ReadOnly;
		}

		$multifields = array();
		foreach($fields as $k => $v)
		{
			$info = isset($fieldsInfo[$k]) ? $fieldsInfo[$k] : null;
			if(!$info)
			{
				unset($fields[$k]);
				continue;
			}

			$attrs = isset($info['ATTRIBUTES']) ? $info['ATTRIBUTES'] : array();
			$isMultiple = in_array(CCrmFieldInfoAttr::Multiple, $attrs, true);

			$ary = array_intersect($ignoredAttrs, $attrs);
			if(!empty($ary))
			{
				unset($fields[$k]);
				continue;
			}

			$fieldType = isset($info['TYPE']) ? $info['TYPE'] : '';
			if($fieldType === 'date' || $fieldType === 'datetime')
			{
				if($v === '')
				{
					$date = '';
				}
				else
				{
					$date = $fieldType === 'date'
						? CRestUtil::unConvertDate($v) : CRestUtil::unConvertDateTime($v, true);
				}

				if($isMultiple)
				{
					if(!is_array($date))
					{
						$date = array($date);
					}

					$dates = array();
					foreach($date as $item)
					{
						if(is_string($item))
						{
							$dates[] = $item;
						}
					}

					if(!empty($dates))
					{
						$fields[$k] = $dates;
					}
					else
					{
						unset($fields[$k]);
					}
				}
				elseif(is_string($date))
				{
					$fields[$k] = $date;
				}
				else
				{
					unset($fields[$k]);
				}
			}
			elseif($fieldType === 'file')
			{
				$this->tryInternalizeFileField($fields, $k, $isMultiple);
			}
			elseif($fieldType === 'webdav')
			{
				$this->tryInternalizeWebDavElementField($fields, $k, $isMultiple);
			}
			elseif($fieldType === 'diskfile')
			{
				$this->tryInternalizeDiskFileField($fields, $k, $isMultiple);
			}
			elseif($fieldType === 'crm_multifield')
			{
				$this->tryInternalizeMultiFields($fields, $k, $multifields);
			}
			elseif($fieldType === 'product_file')
			{
				$this->tryInternalizeProductFileField($fields, $k);
			}
			elseif($fieldType === 'product_property')
			{
				$this->tryInternalizeProductPropertyField($fields, $fieldsInfo, $k);
			}
		}

		if(!empty($multifields))
		{
			$fields['FM'] = $multifields;
		}
	}
	protected function tryInternalizeMultiFields(array &$fields, $fieldName, array &$data)
	{
		if(!isset($fields[$fieldName]) && is_array($fields[$fieldName]))
		{
			return false;
		}

		$qty = 0;
		$result = array();
		$values = $fields[$fieldName];
		foreach($values as &$v)
		{
			$ID = isset($v['ID']) ? $v['ID'] : 0;
			$value = isset($v['VALUE']) ? trim($v['VALUE']) : '';
			//Allow empty values for persistent fields for support deletion operation.
			if($ID <= 0 && $value === '')
			{
				continue;
			}

			if($ID > 0 && isset($v['DELETE']) && strtoupper($v['DELETE']) === 'Y')
			{
				//Empty fields will be deleted.
				$value = '';
			}

			$valueType = isset($v['VALUE_TYPE']) ? trim($v['VALUE_TYPE']) : '';
			if($valueType === '')
			{
				$valueType = CCrmFieldMulti::GetDefaultValueType($fieldName);
			}

			$key = $ID > 0 ? $ID : 'n'.(++$qty);
			$result[$key] = array('VALUE_TYPE' => $valueType, 'VALUE' => $value);
		}
		unset($v, $fields[$fieldName]);

		if(empty($result))
		{
			return false;
		}

		$data[$fieldName] = $result;
		return true;
	}
	protected function tryInternalizeFileField(&$fields, $fieldName, $multiple = false)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		$result = array();

		$values = $multiple && self::isIndexedArray($fields[$fieldName]) ? $fields[$fieldName] : array($fields[$fieldName]);
		foreach($values as &$v)
		{
			if(!self::isAssociativeArray($v))
			{
				continue;
			}

			$fileID = isset($v['id']) ? intval($v['id']) : 0;
			$removeFile = isset($v['remove']) && is_string($v['remove']) && strtoupper($v['remove']) === 'Y';
			$fileData = isset($v['fileData']) ? $v['fileData'] : '';

			if(!self::isIndexedArray($fileData))
			{
				$fileName = '';
				$fileContent = $fileData;
			}
			else
			{
				$fileDataLength = count($fileData);

				if($fileDataLength > 1)
				{
					$fileName = $fileData[0];
					$fileContent = $fileData[1];
				}
				elseif($fileDataLength === 1)
				{
					$fileName = '';
					$fileContent = $fileData[0];
				}
				else
				{
					$fileName = '';
					$fileContent = '';
				}
			}

			if(is_string($fileContent) && $fileContent !== '')
			{
				// Add/replace file
				$fileInfo = CRestUtil::saveFile($fileContent, $fileName);
				if(is_array($fileInfo))
				{
					if($fileID > 0)
					{
						$fileInfo['old_id'] = $fileID;
					}

					//In this case 'del' flag does not make sense - old file will be replaced by new one.
					/*if($removeFile)
					{
						$fileInfo['del'] = true;
					}*/

					$result[] = &$fileInfo;
					unset($fileInfo);
				}
			}
			elseif($fileID > 0 && $removeFile)
			{
				// Remove file
				$result[] = array(
					'old_id' => $fileID,
					'del' => true
				);
			}
		}
		unset($v);

		if($multiple)
		{
			$fields[$fieldName] = $result;
			return true;
		}
		elseif(!empty($result))
		{
			$fields[$fieldName] = $result[0];
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}
	protected function tryInternalizeProductFileField(&$fields, $fieldName)
	{
		if(!(isset($fields[$fieldName]) && self::isAssociativeArray($fields[$fieldName])))
			return false;

		$result = array();

		//$fileID = isset($fields[$fieldName]['id']) ? intval($fields[$fieldName]['id']) : 0;
		$removeFile = isset($fields[$fieldName]['remove']) && is_string($fields[$fieldName]['remove'])
			&& strtoupper($fields[$fieldName]['remove']) === 'Y';
		$fileData = isset($fields[$fieldName]['fileData']) ? $fields[$fieldName]['fileData'] : '';

		if(!self::isIndexedArray($fileData))
		{
			$fileName = '';
			$fileContent = $fileData;
		}
		else
		{
			$fileDataLength = count($fileData);

			if($fileDataLength > 1)
			{
				$fileName = $fileData[0];
				$fileContent = $fileData[1];
			}
			elseif($fileDataLength === 1)
			{
				$fileName = '';
				$fileContent = $fileData[0];
			}
			else
			{
				$fileName = '';
				$fileContent = '';
			}
		}

		if(is_string($fileContent) && $fileContent !== '')
		{
			// Add/replace file
			$fileInfo = CRestUtil::saveFile($fileContent, $fileName);
			if(is_array($fileInfo))
			{
				$result = &$fileInfo;
				unset($fileInfo);
			}
		}
		elseif($removeFile)
		{
			// Remove file
			$result = array(
				'del' => 'Y'
			);
		}

		if(!empty($result))
		{
			$fields[$fieldName] = $result;
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}
	protected function tryInternalizeWebDavElementField(&$fields, $fieldName, $multiple = false)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		$result = array();

		$values = $multiple && self::isIndexedArray($fields[$fieldName]) ? $fields[$fieldName] : array($fields[$fieldName]);
		foreach($values as &$v)
		{
			if(!self::isAssociativeArray($v))
			{
				continue;
			}

			$elementID = isset($v['id']) ? intval($v['id']) : 0;
			$removeElement = isset($v['remove']) && is_string($v['remove']) && strtoupper($v['remove']) === 'Y';
			$fileData = isset($v['fileData']) ? $v['fileData'] : '';

			if(!self::isIndexedArray($fileData))
			{
				continue;
			}

			$fileDataLength = count($fileData);
			if($fileDataLength === 0)
			{
				continue;
			}

			if($fileDataLength === 1)
			{
				$fileName = '';
				$fileContent = $fileData[0];
			}
			else
			{
				$fileName = $fileData[0];
				$fileContent = $fileData[1];
			}

			if(is_string($fileContent) && $fileContent !== '')
			{
				$fileInfo = CRestUtil::saveFile($fileContent, $fileName);

				$settings = $this->getWebDavSettings();
				$iblock = $this->prepareWebDavIBlock($settings);
				$fileName = $iblock->CorrectName($fileName);

				$filePath = $fileInfo['tmp_name'];
				$options = array(
					'new' => true,
					'dropped' => false,
					'arDocumentStates' => array(),
					'arUserGroups' => $iblock->USER['GROUPS'],
					'TMP_FILE' => $filePath,
					'FILE_NAME' => $fileName,
					'IBLOCK_ID' => $settings['IBLOCK_ID'],
					'IBLOCK_SECTION_ID' => $settings['IBLOCK_SECTION_ID'],
					'WF_STATUS_ID' => 1
				);
				$options['arUserGroups'][] = 'Author';

				global $DB;
				$DB->StartTransaction();
				if (!$iblock->put_commit($options))
				{
					$DB->Rollback();
					unlink($filePath);
					throw new RestException($iblock->LAST_ERROR);
				}
				$DB->Commit();
				unlink($filePath);

				if(!isset($options['ELEMENT_ID']))
				{
					throw new RestException('Could not save webdav element.');
				}

				$elementData = array(
					'ELEMENT_ID' => $options['ELEMENT_ID']
				);

				if($elementID > 0)
				{
					$elementData['OLD_ELEMENT_ID'] = $elementID;
				}

				$result[] = &$elementData;
				unset($elementData);
			}
			elseif($elementID > 0 && $removeElement)
			{
				$result[] = array(
					'OLD_ELEMENT_ID' => $elementID,
					'DELETE' => true
				);
			}
		}
		unset($v);

		if($multiple)
		{
			$fields[$fieldName] = $result;
			return true;
		}
		elseif(!empty($result))
		{
			$fields[$fieldName] = $result[0];
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}
	protected function tryInternalizeDiskFileField(&$fields, $fieldName, $multiple = false)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		$result = array();

		$values = $multiple && self::isIndexedArray($fields[$fieldName]) ? $fields[$fieldName] : array($fields[$fieldName]);
		foreach($values as &$v)
		{
			if(!self::isAssociativeArray($v))
			{
				continue;
			}

			$fileID = isset($v['id']) ? intval($v['id']) : 0;
			$removeElement = isset($v['remove']) && is_string($v['remove']) && strtoupper($v['remove']) === 'Y';
			$fileData = isset($v['fileData']) ? $v['fileData'] : '';

			if(!self::isIndexedArray($fileData))
			{
				continue;
			}

			$fileDataLength = count($fileData);
			if($fileDataLength === 0)
			{
				continue;
			}

			if($fileDataLength === 1)
			{
				$fileName = '';
				$fileContent = $fileData[0];
			}
			else
			{
				$fileName = $fileData[0];
				$fileContent = $fileData[1];
			}

			if(is_string($fileContent) && $fileContent !== '')
			{
				$folder = DiskManager::ensureFolderCreated(StorageFileType::Rest);
				if(!$folder)
				{
					throw new RestException('Could not create disk folder for rest files.');
				}

				$fileInfo = CRestUtil::saveFile($fileContent, $fileName);
				if(is_array($fileInfo))
				{
					if($fileName === '' && isset($fileInfo['name']))
					{
						$fileName = $fileInfo['name'];
					}

					$file = $folder->uploadFile(
						$fileInfo,
						array('NAME' => $fileName, 'CREATED_BY' => $this->getCurrentUserID()),
						array(),
						true
					);
					unlink($fileInfo['tmp_name']);

					if(!$file)
					{
						throw new RestException('Could not create disk file.');
					}

					$result[] = array('FILE_ID' => $file->getId());
				}
			}
			elseif($fileID > 0 && $removeElement)
			{
				$result[] = array('OLD_FILE_ID' => $fileID, 'DELETE' => true);
			}
		}
		unset($v);

		if($multiple)
		{
			$fields[$fieldName] = $result;
			return true;
		}
		elseif(!empty($result))
		{
			$fields[$fieldName] = $result[0];
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}
	protected function tryInternalizeProductPropertyField(&$fields, &$fieldsInfo, $fieldName)
	{
		static $sanitizer = null;

		if(!is_array($fields) || !isset($fields[$fieldName]))
		{
			return;
		}

		$info = isset($fieldsInfo[$fieldName]) ? $fieldsInfo[$fieldName] : null;
		$rawValue = isset($fields[$fieldName]) ? $fields[$fieldName] : null;

		if(!$info)
		{
			unset($fields[$fieldName]);
			return;
		}

		$attrs = isset($info['ATTRIBUTES']) ? $info['ATTRIBUTES'] : array();

		$fieldType = isset($info['TYPE']) ? $info['TYPE'] : '';
		$propertyType = isset($info['PROPERTY_TYPE']) ? $info['PROPERTY_TYPE'] : '';
		$userType = isset($info['USER_TYPE']) ? $info['USER_TYPE'] : '';

		if ($fieldType === 'product_property')
		{
			$value = array();
			$newIndex = 0;
			$valueId = 'n'.$newIndex;
			if (!self::isIndexedArray($rawValue))
				$rawValue = array($rawValue);
			foreach ($rawValue as &$valueElement)
			{
				if (is_array($valueElement) && isset($valueElement['value']))
				{
					$valueId = (isset($valueElement['valueId']) && intval($valueElement['valueId']) > 0) ?
						intval($valueElement['valueId']) : 'n'.$newIndex++;
					$value[$valueId] = &$valueElement['value'];
				}
				else
				{
					$valueId = 'n'.$newIndex++;
					$value[$valueId] = &$valueElement;
				}
			}
			unset($newIndex, $valueElement);
			foreach ($value as $valueId => $v)
			{
				if($propertyType === 'S' && $userType === 'Date')
				{
					$date = CRestUtil::unConvertDate($v);
					if(is_string($date))
						$value[$valueId] = $date;
					else
						unset($value[$valueId]);
				}
				elseif($propertyType === 'S' && $userType === 'DateTime')
				{
					$datetime = CRestUtil::unConvertDateTime($v, true);
					if(is_string($datetime))
						$value[$valueId] = $datetime;
					else
						unset($value[$valueId]);
				}
				elseif($propertyType === 'F' && empty($userType))
				{
					$this->tryInternalizeProductFileField($value, $valueId);
				}
				elseif($propertyType === 'S' && $userType === 'HTML')
				{
					if (is_array($v) && isset($v['TYPE']) && isset($v['TEXT'])
						&& strtolower($v['TYPE']) === 'html' && !empty($v['TEXT']))
					{
						if ($sanitizer === null)
						{
							$sanitizer = new CBXSanitizer();
							$sanitizer->ApplyDoubleEncode(false);
							$sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_LOW);
						}
						$value[$valueId]['TEXT'] = $sanitizer->SanitizeHtml($v['TEXT']);
					}
				}
			}
			$fields[$fieldName] = $value;
		}
		else
		{
			unset($fields[$fieldName]);
		}
	}

	protected function externalizeFields(&$fields, &$fieldsInfo)
	{
		if(!is_array($fields))
		{
			return;
		}

		//Multi fields processing
		if(isset($fields['FM']))
		{
			foreach($fields['FM'] as $fmTypeID => &$fmItems)
			{
				foreach($fmItems as &$fmItem)
				{
					$fmItem['TYPE_ID'] = $fmTypeID;
					unset($fmItem['ENTITY_ID'], $fmItem['ELEMENT_ID']);
				}
				unset($fmItem);
				$fields[$fmTypeID] = $fmItems;
			}
			unset($fmItems);
			unset($fields['FM']);
		}

		foreach($fields as $k => $v)
		{
			$info = isset($fieldsInfo[$k]) ? $fieldsInfo[$k] : null;
			if(!$info)
			{
				unset($fields[$k]);
				continue;
			}

			$attrs = isset($info['ATTRIBUTES']) ? $info['ATTRIBUTES'] : array();
			$isMultiple = in_array(CCrmFieldInfoAttr::Multiple, $attrs, true);
			$isHidden = in_array(CCrmFieldInfoAttr::Hidden, $attrs, true);
			$isDynamic = in_array(CCrmFieldInfoAttr::Dynamic, $attrs, true);

			if($isHidden)
			{
				unset($fields[$k]);
				continue;
			}

			$fieldType = isset($info['TYPE']) ? $info['TYPE'] : '';
			if($fieldType === 'date')
			{
				if(!is_array($v))
				{
					$fields[$k] = CRestUtil::ConvertDate($v);
				}
				else
				{
					$fields[$k] = array();
					foreach($v as &$value)
					{
						$fields[$k][] = CRestUtil::ConvertDate($value);
					}
					unset($value);
				}
			}
			elseif($fieldType === 'datetime')
			{
				if(!is_array($v))
				{
					$fields[$k] = CRestUtil::ConvertDateTime($v);
				}
				else
				{
					$fields[$k] = array();
					foreach($v as &$value)
					{
						$fields[$k][] = CRestUtil::ConvertDateTime($value);
					}
					unset($value);
				}
			}
			elseif($fieldType === 'file')
			{
				$this->tryExternalizeFileField($fields, $k, $isMultiple, $isDynamic);
			}
			elseif($fieldType === 'webdav')
			{
				$this->tryExternalizeWebDavElementField($fields, $k, $isMultiple);
			}
			elseif($fieldType === 'diskfile')
			{
				$this->tryExternalizeDiskFileField($fields, $k, $isMultiple);
			}
			elseif($fieldType === 'product_file')
			{
				$this->tryExternalizeProductFileField($fields, $k, false, false);
			}
			elseif($fieldType === 'product_property')
			{
				$this->tryExternalizeProductPropertyField($fields, $fieldsInfo, $k);
			}
		}
	}
	protected function tryExternalizeFileField(&$fields, $fieldName, $multiple = false, $dynamic = true)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		$ownerTypeID = $this->getOwnerTypeID();
		$ownerID = isset($fields['ID']) ? intval($fields['ID']) : 0;
		if(!$multiple)
		{
			$fileID = intval($fields[$fieldName]);
			if($fileID <= 0)
			{
				unset($fields[$fieldName]);
				return false;
			}

			$fields[$fieldName] = $this->externalizeFile($ownerTypeID, $ownerID, $fieldName, $fileID, $dynamic);
		}
		else
		{
			$result = array();
			$filesID = $fields[$fieldName];
			if(!is_array($filesID))
			{
				$filesID = array($filesID);
			}

			foreach($filesID as $fileID)
			{
				$fileID = intval($fileID);
				if($fileID > 0)
				{
					$result[] = $this->externalizeFile($ownerTypeID, $ownerID, $fieldName, $fileID, $dynamic);
				}
			}
			$fields[$fieldName] = &$result;
			unset($result);
		}

		return true;
	}
	protected function tryExternalizeProductFileField(&$fields, $fieldName, $multiple = false, $dynamic = true)
	{
		if(!isset($fields[$fieldName]))
			return false;

		$productID = isset($fields['ID']) ? intval($fields['ID']) : 0;
		if(!$multiple)
		{
			if (!$dynamic)
			{
				$fileID = intval($fields[$fieldName]);
				if($fileID <= 0)
				{
					unset($fields[$fieldName]);
					return false;
				}

				$fields[$fieldName] = $this->externalizeProductFile($productID, $fieldName, 0, $fileID, $dynamic);
			}
			else
			{
				if (!(is_array(isset($fields[$fieldName]) && isset($fields[$fieldName]['VALUE_ID'])
					&& isset($fields[$fieldName]['VALUE']))))
				{
					unset($fields[$fieldName]);
					return false;
				}

				$valueID = intval($fields[$fieldName]['VALUE_ID']);
				$fileID = intval($fields[$fieldName]['VALUE']);
				if($fileID <= 0)
				{
					unset($fields[$fieldName]);
					return false;
				}

				$fields[$fieldName] = $this->externalizeProductFile($productID, $fieldName, $valueID, $fileID, $dynamic);
			}
		}
		else
		{
			if (!self::isIndexedArray($fields[$fieldName]))
			{
				unset($fields[$fieldName]);
				return false;
			}

			$result = array();
			foreach($fields[$fieldName] as $element)
			{
				if (!(isset($element['VALUE_ID']) && isset($element['VALUE'])))
					continue;

				$valueID = intval($element['VALUE_ID']);
				$fileID = intval($element['VALUE']);
				if($fileID > 0)
				{
					$result[] = $this->externalizeProductFile($productID, $fieldName, $valueID, $fileID, $dynamic);
				}
			}
			$fields[$fieldName] = &$result;
			unset($result);
		}

		return true;
	}
	protected function tryExternalizeWebDavElementField(&$fields, $fieldName, $multiple = false)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		if(!$multiple)
		{
			$elementID = intval($fields[$fieldName]);
			$info = CCrmWebDavHelper::GetElementInfo($elementID, false);
			if(empty($info))
			{
				unset($fields[$fieldName]);
				return false;
			}
			else
			{
				$fields[$fieldName] = array(
					'id' => $elementID,
					'url' => isset($info['SHOW_URL']) ? $info['SHOW_URL'] : ''
				);

				return true;
			}
		}

		$result = array();
		$elementsID = $fields[$fieldName];
		if(is_array($elementsID))
		{
			foreach($elementsID as $elementID)
			{
				$elementID = intval($elementID);
				$info = CCrmWebDavHelper::GetElementInfo($elementID, false);
				if(empty($info))
				{
					continue;
				}

				$result[] = array(
					'id' => $elementID,
					'url' => isset($info['SHOW_URL']) ? $info['SHOW_URL'] : ''
				);
			}
		}

		if(!empty($result))
		{
			$fields[$fieldName] = &$result;
			unset($result);
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}
	protected function tryExternalizeDiskFileField(&$fields, $fieldName, $multiple = false)
	{
		if(!isset($fields[$fieldName]))
		{
			return false;
		}

		$options = array(
			'OWNER_TYPE_ID' => $this->getOwnerTypeID(),
			'OWNER_ID' => $fields['ID'],
			'VIEW_PARAMS' => array('auth' => $this->getAuthToken()),
			'USE_ABSOLUTE_PATH' => true
		);

		if(!$multiple)
		{
			$fileID = intval($fields[$fieldName]);
			$info = DiskManager::getFileInfo($fileID, false, $options);
			if(empty($info))
			{
				unset($fields[$fieldName]);
				return false;
			}
			else
			{
				$fields[$fieldName] = array(
					'id' => $fileID,
					'url' => isset($info['VIEW_URL']) ? $info['VIEW_URL'] : ''
				);

				return true;
			}
		}

		$result = array();
		$fileIDs = $fields[$fieldName];
		if(is_array($fileIDs))
		{
			foreach($fileIDs as $fileID)
			{
				$info = DiskManager::getFileInfo($fileID, false, $options);
				if(empty($info))
				{
					continue;
				}

				$result[] = array(
					'id' => $fileID,
					'url' => isset($info['VIEW_URL']) ? $info['VIEW_URL'] : ''
				);
			}
		}

		if(!empty($result))
		{
			$fields[$fieldName] = &$result;
			unset($result);
			return true;
		}

		unset($fields[$fieldName]);
		return false;
	}
	protected function tryExternalizeProductPropertyField(&$fields, &$fieldsInfo, $fieldName)
	{
		if(!is_array($fields) || !isset($fields[$fieldName]))
		{
			return;
		}

		$info = isset($fieldsInfo[$fieldName]) ? $fieldsInfo[$fieldName] : null;
		$value = isset($fields[$fieldName]) ? $fields[$fieldName] : null;

		if(!$info)
		{
			unset($fields[$fieldName]);
			return;
		}

		$attrs = isset($info['ATTRIBUTES']) ? $info['ATTRIBUTES'] : array();
		$isMultiple = in_array(CCrmFieldInfoAttr::Multiple, $attrs, true);
		$isDynamic = in_array(CCrmFieldInfoAttr::Dynamic, $attrs, true);

		$fieldType = isset($info['TYPE']) ? $info['TYPE'] : '';
		$propertyType = isset($info['PROPERTY_TYPE']) ? $info['PROPERTY_TYPE'] : '';
		$userType = isset($info['USER_TYPE']) ? $info['USER_TYPE'] : '';
		if($fieldType === 'product_property' && $propertyType === 'S' && $userType === 'Date')
		{
			if (self::isIndexedArray($value))
			{
				$fields[$fieldName] = array();
				foreach($value as $valueElement)
				{
					if (isset($valueElement['VALUE_ID']) && isset($valueElement['VALUE']))
					{
						$fields[$fieldName][] = array(
							'valueId' => $valueElement['VALUE_ID'],
							'value' => CRestUtil::ConvertDate($valueElement['VALUE'])
						);
					}
				}
			}
			else
			{
				if (isset($value['VALUE_ID']) && isset($value['VALUE']))
				{
					$fields[$fieldName] = array(
						'valueId' => $value['VALUE_ID'],
						'value' => CRestUtil::ConvertDate($value['VALUE'])
					);
				}
				else
				{
					$fields[$fieldName] = null;
				}
			}
		}
		elseif($fieldType === 'product_property' && $propertyType === 'S' && $userType === 'DateTime')
		{
			if (self::isIndexedArray($value))
			{
				$fields[$fieldName] = array();
				foreach($value as $valueElement)
				{
					if (isset($valueElement['VALUE_ID']) && isset($valueElement['VALUE']))
					{
						$fields[$fieldName][] = array(
							'valueId' => $valueElement['VALUE_ID'],
							'value' => CRestUtil::ConvertDateTime($valueElement['VALUE'])
						);
					}
				}
			}
			else
			{
				if (isset($value['VALUE_ID']) && isset($value['VALUE']))
				{
					$fields[$fieldName] = array(
						'valueId' => $value['VALUE_ID'],
						'value' => CRestUtil::ConvertDateTime($value['VALUE'])
					);
				}
				else
				{
					$fields[$fieldName] = null;
				}
			}
		}
		elseif($fieldType === 'product_property' && $propertyType === 'F' && empty($userType))
		{
			$this->tryExternalizeProductFileField($fields, $fieldName, $isMultiple, $isDynamic);
		}
		else
		{
			if (self::isIndexedArray($value))
			{
				$fields[$fieldName] = array();
				foreach($value as $valueElement)
				{
					if (isset($valueElement['VALUE_ID']) && isset($valueElement['VALUE']))
					{
						$fields[$fieldName][] = array(
							'valueId' => $valueElement['VALUE_ID'],
							'value' => $valueElement['VALUE']
						);
					}
				}
			}
			else
			{
				if (isset($value['VALUE_ID']) && isset($value['VALUE']))
				{
					$fields[$fieldName] = array(
						'valueId' => $value['VALUE_ID'],
						'value' => $value['VALUE']
					);
				}
				else
				{
					$fields[$fieldName] = null;
				}
			}
		}
	}
	protected function internalizeFilterFields(&$filter, &$fieldsInfo)
	{
		if(!is_array($filter))
		{
			return;
		}

		foreach($filter as $k => $v)
		{
			$operationInfo =  CSqlUtil::GetFilterOperation($k);
			$fieldName = $operationInfo['FIELD'];

			$info = isset($fieldsInfo[$fieldName]) ? $fieldsInfo[$fieldName] : null;
			if(!$info)
			{
				unset($filter[$k]);
				continue;
			}

			$operation = substr($k, 0, strlen($k) - strlen($fieldName));
			if(isset($info['FORBIDDEN_FILTERS'])
				&& is_array($info['FORBIDDEN_FILTERS'])
				&& in_array($operation, $info['FORBIDDEN_FILTERS'], true))
			{
				unset($filter[$k]);
				continue;
			}

			$fieldType = isset($info['TYPE']) ? $info['TYPE'] : '';
			if(($fieldType === 'crm_status' || $fieldType === 'crm_company' || $fieldType === 'crm_contact')
				&& ($operation === '%' || $operation === '%=' || $operation === '=%'))
			{
				//Prevent filtration by LIKE due to performance considerations
				$filter["={$fieldName}"] = $v;
				unset($filter[$k]);
				continue;
			}

			if($fieldType === 'datetime')
			{
				$filter[$k] = CRestUtil::unConvertDateTime($v, true);
			}
			elseif($fieldType === 'date')
			{
				$filter[$k] = CRestUtil::unConvertDate($v);
			}
		}

		CCrmEntityHelper::PrepareMultiFieldFilter($filter, array(), '=%', true);
	}
	protected static function isAssociativeArray($ary)
	{
		if(!is_array($ary))
		{
			return false;
		}

		$keys = array_keys($ary);
		foreach($keys as $k)
		{
			if (!is_int($k))
			{
				return true;
			}
		}
		return false;
	}
	protected static function isIndexedArray($ary)
	{
		if(!is_array($ary))
		{
			return false;
		}

		$keys = array_keys($ary);
		foreach($keys as $k)
		{
			if (!is_int($k))
			{
				return false;
			}
		}
		return true;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		$errors[] = 'The operation "ADD" is not supported by this entity.';
		return false;
	}
	protected function innerGet($ID, &$errors)
	{
		$errors[] = 'The operation "GET" is not supported by this entity.';
		return false;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		$errors[] = 'The operation "LIST" is not supported by this entity.';
		return null;
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		$errors[] = 'The operation "UPDATE" is not supported by this entity.';
		return false;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		$errors[] = 'The operation "DELETE" is not supported by this entity.';;
		return false;
	}
	protected function externalizeFile($ownerTypeID, $ownerID, $fieldName, $fileID, $dynamic = true)
	{
		$ownerTypeName = strtolower(CCrmOwnerType::ResolveName($ownerTypeID));
		if($ownerTypeName === '')
		{
			return '';
		}

		$handlerUrl = "/bitrix/components/bitrix/crm.{$ownerTypeName}.show/show_file.php";
		$showUrl = CComponentEngine::MakePathFromTemplate(
			"{$handlerUrl}?ownerId=#owner_id#&fieldName=#field_name#&dynamic=#dynamic#&fileId=#file_id#",
			array(
				'field_name' => $fieldName,
				'file_id' => $fileID,
				'owner_id' => $ownerID,
				'dynamic' => $dynamic ? 'Y' : 'N'
			)
		);

		$downloadUrl = CComponentEngine::MakePathFromTemplate(
			"{$handlerUrl}?auth=#auth#&ownerId=#owner_id#&fieldName=#field_name#&dynamic=#dynamic#&fileId=#file_id#",
			array(
				'auth' => $this->getAuthToken(),
				'field_name' => $fieldName,
				'file_id' => $fileID,
				'owner_id' => $ownerID,
				'dynamic' => $dynamic ? 'Y' : 'N'
			)
		);

		return array(
			'id' => $fileID,
			'showUrl' => $showUrl,
			'downloadUrl' => $downloadUrl
		);
	}
	protected function externalizeProductFile($productID, $fieldName, $valueID, $fileID, $dynamic = true)
	{
		$handlerUrl = "/bitrix/components/bitrix/crm.product.file/download.php";
		$showUrl = CComponentEngine::MakePathFromTemplate(
			"{$handlerUrl}?productId=#product_id#&fieldName=#field_name#&dynamic=#dynamic#&fileId=#file_id#",
			array(
				'field_name' => $fieldName,
				'file_id' => $fileID,
				'product_id' => $productID,
				'dynamic' => $dynamic ? 'Y' : 'N'
			)
		);

		$downloadUrl = CComponentEngine::MakePathFromTemplate(
			"{$handlerUrl}?auth=#auth#&productId=#product_id#&fieldName=#field_name#&dynamic=#dynamic#&fileId=#file_id#",
			array(
				'auth' => $this->getAuthToken(),
				'field_name' => $fieldName,
				'file_id' => $fileID,
				'product_id' => $productID,
				'dynamic' => $dynamic ? 'Y' : 'N'
			)
		);

		$result = array(
			'id' => $fileID,
			'showUrl' => $showUrl,
			'downloadUrl' => $downloadUrl
		);

		if ($dynamic)
			$result = array(
				'valueId' => $valueID,
				'value' => $result
			);

		return $result;
	}
	// WebDav -->
	protected function prepareWebDavIBlock($settings = null)
	{
		if($this->webdavIBlock !== null)
		{
			return $this->webdavIBlock;
		}

		if(!CModule::IncludeModule('webdav'))
		{
			throw new RestException('Could not load webdav module.');
		}

		if(!is_array($settings) || empty($settings))
		{
			$settings = $this->getWebDavSettings();
		}

		$iblockID = isset($settings['IBLOCK_ID']) ? $settings['IBLOCK_ID'] : 0;
		if($iblockID <= 0)
		{
			throw new RestException('Could not find webdav iblock.');
		}

		$sectionId = isset($settings['IBLOCK_SECTION_ID']) ? $settings['IBLOCK_SECTION_ID'] : 0;
		if($sectionId <= 0)
		{
			throw new RestException('Could not find webdav section.');
		}

		$user = CCrmSecurityHelper::GetCurrentUser();
		$this->webdavIBlock = new CWebDavIblock(
			$iblockID,
			'',
			array(
				'ROOT_SECTION_ID' => $sectionId,
				'DOCUMENT_TYPE' => array('webdav', 'CIBlockDocumentWebdavSocnet', 'iblock_'.$sectionId.'_user_'.$user->GetID())
			)
		);

		return $this->webdavIBlock;
	}
	protected function getWebDavSettings()
	{
		if($this->webdavSettings !== null)
		{
			return $this->webdavSettings;
		}

		if(!CModule::IncludeModule('webdav'))
		{
			throw new RestException('Could not load webdav module.');
		}

		$opt = COption::getOptionString('webdav', 'user_files', null);
		if($opt == null)
		{
			throw new RestException('Could not find webdav settings.');
		}

		$user = CCrmSecurityHelper::GetCurrentUser();

		$opt = unserialize($opt);
		$iblockID = intval($opt[CSite::GetDefSite()]['id']);
		$userSectionID = CWebDavIblock::getRootSectionIdForUser($iblockID, $user->GetID());
		if(!is_numeric($userSectionID) || $userSectionID <= 0)
		{
			throw new RestException('Could not find webdav section for user '.$user->GetLastName().'.');
		}

		return ($this->webdavSettings =
			array(
				'IBLOCK_ID' => $iblockID,
				'IBLOCK_SECTION_ID' => intval($userSectionID),
			)
		);
	}
	// <-- WebDav
	/**
	 * @return array
	 * @throws RestException
	 */
	protected function getFieldsInfo()
	{
		throw new RestException('The method is not implemented.');
	}
	protected function sanitizeHtml($html)
	{
		$html = strval($html);
		if($html === '' || strpos($html, '<') === false)
		{
			return $html;
		}

		if($this->sanitizer === null)
		{
			$this->sanitizer = new CBXSanitizer();
			$this->sanitizer->ApplyDoubleEncode(false);
			$this->sanitizer->SetLevel(CBXSanitizer::SECURE_LEVEL_MIDDLE);
		}

		return $this->sanitizer->SanitizeHtml($html);
	}
	protected function getIdentityFieldName()
	{
		return '';
	}
	protected function getIdentity(&$fields)
	{
		return 0;
	}
	protected static function getMultiFieldTypeIDs()
	{
		if(self::$MULTIFIELD_TYPE_IDS === null)
		{
			self::$MULTIFIELD_TYPE_IDS = array_keys(CCrmFieldMulti::GetEntityTypeInfos());
		}

		return self::$MULTIFIELD_TYPE_IDS;
	}
	protected function getSupportedMultiFieldTypeIDs()
	{
		return null;
	}
	protected function prepareListItemMultiFields(&$entityMap, $entityTypeID, $typeIDs)
	{
		$entityIDs = array_keys($entityMap);
		if(empty($entityIDs))
		{
			return;
		}

		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
		if($entityTypeName === '')
		{
			return;
		}

		$dbResult = CCrmFieldMulti::GetListEx(
			array(),
			array(
				'=ENTITY_ID' => $entityTypeName,
				'@ELEMENT_ID' => $entityIDs,
				'@TYPE_ID' => $typeIDs
			)
		);

		while($fm = $dbResult->Fetch())
		{
			$typeID = isset($fm['TYPE_ID']) ? $fm['TYPE_ID'] : '';
			if(!in_array($typeID, $typeIDs, true))
			{
				continue;
			}

			$entityID = isset($fm['ELEMENT_ID']) ? intval($fm['ELEMENT_ID']) : 0;
			if(!isset($entityMap[$entityID]))
			{
				continue;
			}

			$entity = &$entityMap[$entityID];
			if(!isset($entity['FM']))
			{
				$entity['FM'] = array();
			}

			if(!isset($entity['FM'][$typeID]))
			{
				$entity['FM'][$typeID] = array();
			}

			$entity['FM'][$typeID][] = array('ID' => $fm['ID'], 'VALUE_TYPE' => $fm['VALUE_TYPE'], 'VALUE' => $fm['VALUE']);
			unset($entity);
		}
	}
	protected function prepareMultiFieldData($entityTypeID, $entityID, &$entityFields, $typeIDs = null)
	{
		$entityTypeID = intval($entityTypeID);
		$entityID = intval($entityID);

		if(!CCrmOwnerType::IsDefined($entityTypeID) || $entityID <= 0)
		{
			return;
		}

		$dbResult = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => CCrmOwnerType::ResolveName($entityTypeID),
				'ELEMENT_ID' => $entityID
			)
		);

		if(!is_array($typeIDs) || empty($typeIDs))
		{
			$typeIDs = self::getMultiFieldTypeIDs();
		}

		$entityFields['FM'] = array();
		while($fm = $dbResult->Fetch())
		{
			$typeID = $fm['TYPE_ID'];
			if(!in_array($typeID, $typeIDs, true))
			{
				continue;
			}

			if(!isset($entityFields['FM'][$typeID]))
			{
				$entityFields['FM'][$typeID] = array();
			}

			$entityFields['FM'][$typeID][] = array('ID' => $fm['ID'], 'VALUE_TYPE' => $fm['VALUE_TYPE'], 'VALUE' => $fm['VALUE']);
		}
	}
	protected static function isBizProcEnabled()
	{
		return !Bitrix24Manager::isEnabled() || Bitrix24Manager::isRestBizProcEnabled();
	}
	protected static function isRequiredUserFieldCheckEnabled()
	{
		return RestSettings::getCurrent()->isRequiredUserFieldCheckEnabled();
	}
	public static function processEntityEvent($entityTypeID, array $arParams, array $arHandler)
	{
		$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
		if($entityTypeName === '')
		{
			throw new RestException("The 'entityTypeName' is not specified");
		}

		$eventName = $arHandler['EVENT_NAME'];
		if(strpos(strtoupper($eventName), 'ONCRM'.$entityTypeName) !== 0)
		{
			throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}

		$action = substr($eventName, 5 + strlen($entityTypeName));
		if($action === false || $action === '')
		{
			throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}

		switch (strtoupper($action))
		{
			case 'ADD':
			case 'UPDATE':
			{
				$fields = isset($arParams[0]) ? $arParams[0] : null;
				$ID = is_array($fields) && isset($fields['ID']) ? (int)$fields['ID'] : 0;
			}
			break;
			case 'DELETE':
			{
				$ID = isset($arParams[0]) ? (int)$arParams[0] : 0;
			}
			break;
			default:
				throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}

		if($ID <= 0)
		{
			throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
		}
		return array('FIELDS' => array('ID' => $ID));
	}
	protected static function getDefaultEventSettings()
	{
		return array('category' => \Bitrix\Rest\Sqs::CATEGORY_CRM);
	}
	protected static function createEventInfo($moduleName, $eventName, array $callback)
	{
		return array($moduleName, $eventName, $callback, array('category' => \Bitrix\Rest\Sqs::CATEGORY_CRM));
	}
}

class CCrmEnumerationRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;

	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = array(
				'ID' => array(
					'TYPE' => 'int',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'NAME' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
			);
		}
		return $this->FIELDS_INFO;
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$descriptions = null;

		$name = strtoupper($name);
		if($name === 'OWNERTYPE')
		{
			$descriptions = CCrmOwnerType::GetDescriptions(
				array(
					CCrmOwnerType::Lead,
					CCrmOwnerType::Deal,
					CCrmOwnerType::Contact,
					CCrmOwnerType::Company,
					CCrmOwnerType::Quote,
					CCrmOwnerType::Invoice,
					CCrmOwnerType::Requisite,
				)
			);
		}
		elseif($name === 'ADDRESSTYPE')
		{
			$descriptions = EntityAddressType::getDescriptions(
					array(
							EntityAddressType::Primary,
							EntityAddressType::Home,
							EntityAddressType::Registered,
							EntityAddressType::Beneficiary
					)
			);
		}
		elseif($name === 'CONTENTTYPE')
		{
			$descriptions = CCrmContentType::GetAllDescriptions();
		}
		elseif($name === 'ACTIVITYTYPE')
		{
			$descriptions = CCrmActivityType::GetAllDescriptions();
		}
		elseif($name === 'ACTIVITYPRIORITY')
		{
			$descriptions = CCrmActivityPriority::GetAllDescriptions();
		}
		elseif($name === 'ACTIVITYDIRECTION')
		{
			$descriptions = CCrmActivityDirection::GetAllDescriptions();
		}
		elseif($name === 'ACTIVITYNOTIFYTYPE')
		{
			$descriptions = CCrmActivityNotifyType::GetAllDescriptions();
		}
		elseif($name === 'ACTIVITYSTATUS')
		{
			$descriptions = CCrmActivityStatus::GetAllDescriptions();
		}

		if(!is_array($descriptions))
		{
			return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
		}

		$result = array();
		foreach($descriptions as $k => &$v)
		{
			$result[] = array('ID' => $k, 'NAME' => $v);
		}
		unset($v);
		return $result;
	}
}

class CCrmMultiFieldRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = array(
				'ID' => array(
					'TYPE' => 'int',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'TYPE_ID' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'VALUE' => array('TYPE' => 'string'),
				'VALUE_TYPE' => array('TYPE' => 'string')
			);
		}
		return $this->FIELDS_INFO;
	}
}

class CCrmCatalogRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmCatalog::GetFieldsInfo();
		}
		return $this->FIELDS_INFO;
	}

	protected function innerGet($ID, &$errors)
	{
		if(!CCrmProduct::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmCatalog::GetByID($ID);
		if(!is_array($result))
		{
			$errors[] = 'Catalog is not found.';
			return null;
		}

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmProduct::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return CCrmCatalog::GetList($order, $filter, false, $navigation, $select, array('IS_EXTERNAL_CONTEXT' => true));
	}
}

class CCrmProductRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;

	private $userTypes = null;
	private $properties = null;

	protected function initializePropertiesInfo($catalogID)
	{
		if ($this->userTypes === null)
			$this->userTypes = CCrmProductPropsHelper::GetPropsTypesByOperations(false, 'rest');
		if ($this->properties === null)
			$this->properties = CCrmProductPropsHelper::GetProps($catalogID, $this->userTypes);
	}
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmProduct::GetFieldsInfo();
			$this->preparePropertyFieldsInfo($this->FIELDS_INFO);
		}
		return $this->FIELDS_INFO;
	}
	protected function preparePropertyFieldsInfo(&$fieldsInfo)
	{
		$catalogID = CCrmCatalog::GetDefaultID();
		if($catalogID <= 0)
			return;
		$this->initializePropertiesInfo($catalogID);
		foreach($this->properties as $propertyName => $propertyInfo)
		{
			$propertyType = $propertyInfo['PROPERTY_TYPE'];
			$info = array(
				'TYPE' => 'product_property',
				'PROPERTY_TYPE' => $propertyType,
				'USER_TYPE' => $propertyInfo['USER_TYPE'],
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::Dynamic),
				'NAME' => $propertyInfo['NAME']
			);

			$isMultuple = isset($propertyInfo['MULTIPLE']) && $propertyInfo['MULTIPLE'] === 'Y';
			$isRequired = isset($propertyInfo['IS_REQUIRED']) && $propertyInfo['IS_REQUIRED'] === 'Y';
			if($isMultuple || $isRequired)
			{
				if($isMultuple)
					$info['ATTRIBUTES'][] = CCrmFieldInfoAttr::Multiple;
				if($isRequired)
					$info['ATTRIBUTES'][] = CCrmFieldInfoAttr::Required;
			}

			if ($propertyInfo['PROPERTY_TYPE'] === 'L')
			{
				$values = array();
				$resEnum = CIBlockProperty::GetPropertyEnum($propertyInfo['ID'], array('SORT' => 'ASC','ID' => 'ASC'));
				while($enumValue = $resEnum->Fetch())
				{
					$values[intval($enumValue['ID'])] = array(
						'ID' => $enumValue['ID'],
						'VALUE' => $enumValue['VALUE']
					);
				}
				$info['VALUES'] = $values;
			}

			$fieldsInfo[$propertyName] = $info;
		}
	}

	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		if(!is_array($fields))
		{
			throw new RestException("The parameter 'fields' must be array.");
		}

		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		if(!CCrmProduct::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$catalogID = intval(CCrmCatalog::EnsureDefaultExists());
		if($catalogID <= 0)
		{
			$errors[] = 'Default catalog is not exists.';
			return false;
		}

		// Product properties
		$this->initializePropertiesInfo($catalogID);
		$propertyValues = array();
		foreach ($this->properties as $propId => $property)
		{
			if (isset($fields[$propId]))
				$propertyValues[$property['ID']] = $fields[$propId];
			unset($fields[$propId]);
		}
		if(count($propertyValues) > 0)
			$fields['PROPERTY_VALUES'] = $propertyValues;

		$result = CCrmProduct::Add($fields);
		if(!is_int($result))
		{
			$errors[] = CCrmProduct::GetLastError();
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		if(!CCrmProduct::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$catalogID = CCrmCatalog::GetDefaultID();
		if($catalogID <= 0)
		{
			$errors[] = 'Product is not found.';
			return null;
		}

		$filter = array('ID' => $ID, 'CATALOG_ID'=> $catalogID);
		$dbResult = CCrmProduct::GetList(array(), $filter, array('*'), array('nTopCount' => 1));
		$result = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'Product is not found.';
			return null;
		}

		$this->initializePropertiesInfo($catalogID);
		$this->getProperties($catalogID, $result, array('PROPERTY_*'));

		return $result;
	}
	public function getList($order, $filter, $select, $start)
	{
		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		if(!CCrmProduct::CheckReadPermission(0))
		{
			throw new RestException('Access denied.');
		}

		$catalogID = CCrmCatalog::GetDefaultID();
		if($catalogID <= 0)
		{
			$result = array();
			$dbResult = new CDBResult();
			$dbResult->InitFromArray($result);
			return CCrmRestService::setNavData($result, $dbResult);
		}

		$navigation = CCrmRestService::getNavData($start);

		if(!is_array($order) || empty($order))
		{
			$order = array('sort' => 'asc');
		}

		if(!isset($navigation['bShowAll']))
		{
			$navigation['bShowAll'] = false;
		}

		$enableCatalogData = false;
		$catalogSelect = null;
		$priceSelect = null;
		$vatSelect = null;
		$propertiesSelect = array();

		$selectAll = false;
		if(is_array($select))
		{
			if(!empty($select))
			{
				// Remove '*' for get rid of inefficient construction of price data
				foreach($select as $k => $v)
				{
					if($v === '*')
					{
						$selectAll = true;
						unset($select[$k]);
					}
					else if (preg_match('/^PROPERTY_(\d+|\*)$/', $v))
					{
						$propertiesSelect[] = $v;
						unset($select[$k]);
					}
				}
			}

			if (!empty($propertiesSelect) && empty($select) && !$selectAll)
				$select = array('ID');

			if(empty($select))
			{
				$priceSelect = array('PRICE', 'CURRENCY_ID');
				$vatSelect = array('VAT_ID', 'VAT_INCLUDED', 'MEASURE');
			}
			else
			{
				$priceSelect = array();
				$vatSelect = array();

				$select = CCrmProduct::DistributeProductSelect($select, $priceSelect, $vatSelect);
			}

			$catalogSelect = array_merge($priceSelect, $vatSelect);
			$enableCatalogData = !empty($catalogSelect);
		}

		$filter['CATALOG_ID'] = $catalogID;
		$dbResult = CCrmProduct::GetList($order, $filter, $select, $navigation);
		if(!$enableCatalogData)
		{
			$result = array();
			$fieldsInfo = $this->getFieldsInfo();
			while($fields = $dbResult->Fetch())
			{
				$selectedFields = array();
				if (!empty($select))
				{
					$selectedFields['ID'] = $fields['ID'];
					foreach ($select as $k)
						$selectedFields[$k] = &$fields[$k];
					$fields = &$selectedFields;
				}
				unset($selectedFields);

				$this->getProperties($catalogID, $fields, $propertiesSelect);
				$this->externalizeFields($fields, $fieldsInfo);
				$result[] = $fields;
			}
		}
		else
		{
			$itemMap = array();
			$itemIDs = array();
			while($fields = $dbResult->Fetch())
			{
				$selectedFields = array();
				if (!empty($select))
				{
					$selectedFields['ID'] = $fields['ID'];
					foreach ($select as $k)
						$selectedFields[$k] = &$fields[$k];
					$fields = &$selectedFields;
				}
				unset($selectedFields);

				foreach ($catalogSelect as $fieldName)
				{
					$fields[$fieldName] = null;
				}

				$itemID = isset($fields['ID']) ? intval($fields['ID']) : 0;
				if($itemID > 0)
				{
					$itemIDs[] = $itemID;
					$itemMap[$itemID] = $fields;
				}

			}
			CCrmProduct::ObtainPricesVats($itemMap, $itemIDs, $priceSelect, $vatSelect, true);

			$result = array_values($itemMap);
			$fieldsInfo = $this->getFieldsInfo();
			foreach($result as &$fields)
			{
				$this->getProperties($catalogID, $fields, $propertiesSelect);
				$this->externalizeFields($fields, $fieldsInfo);
			}
			unset($fields);
		}

		return CCrmRestService::setNavData($result, $dbResult);
	}
	public function getProperties($catalogID, &$fields, $propertiesSelect)
	{
		if ($catalogID <= 0)
			return;

		if(!is_array($fields))
		{
			throw new RestException("The parameter 'fields' must be array.");
		}

		$productID = isset($fields['ID']) ? intval($fields['ID']) : 0;

		if ($productID <= 0)
			return;

		$this->initializePropertiesInfo($catalogID);

		$selectAll = false;
		foreach($propertiesSelect as $k => $v)
		{
			if($v === 'PROPERTY_*')
			{
				$selectAll = true;
				break;
			}
		}

		$propertyValues = array();
		if ($productID > 0 && count($this->properties) > 0)
		{
			$rsProperties = CIBlockElement::GetProperty(
				$catalogID,
				$productID,
				array(
					'sort' => 'asc',
					'id' => 'asc',
					'enum_sort' => 'asc',
					'value_id' => 'asc',
				),
				array(
					'ACTIVE' => 'Y',
					'EMPTY' => 'N',
					'CHECK_PERMISSIONS' => 'N'
				)
			);
			while ($property = $rsProperties->Fetch())
			{
				if (isset($property['USER_TYPE']) && !empty($property['USER_TYPE'])
					&& !array_key_exists($property['USER_TYPE'], $this->userTypes))
					continue;

				$propId = 'PROPERTY_' . $property['ID'];
				if(!isset($propertyValues[$propId]))
					$propertyValues[$propId] = array();
				$propertyValues[$propId][] =
					array('VALUE_ID' => $property['PROPERTY_VALUE_ID'], 'VALUE' => $property['VALUE']);
			}
			unset($rsProperties, $property, $propId);
		}
		foreach ($this->properties as $propId => $prop)
		{
			if ($selectAll || in_array($propId, $propertiesSelect, true))
			{
				$value = null;
				if (isset($propertyValues[$propId]))
				{
					if ($prop['MULTIPLE'] === 'Y')
						$value = $propertyValues[$propId];
					else if (count($propertyValues[$propId]) > 0)
						$value = end($propertyValues[$propId]);
				}
				$fields[$propId] = $value;
			}
		}
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		if(!is_array($fields))
		{
			throw new RestException("The parameter 'fields' must be array.");
		}

		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		if(!(CCrmProduct::CheckUpdatePermission($ID) && CCrmProduct::EnsureDefaultCatalogScope($ID)))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$catalogID = CCrmCatalog::GetDefaultID();
		if($catalogID <= 0)
		{
			$errors[] = 'Product catalog is not found.';
			return false;
		}

		if(!CCrmProduct::Exists($ID))
		{
			$errors[] = 'Product is not found';
			return false;
		}

		// Product properties
		$this->initializePropertiesInfo($catalogID);
		$propertyValues = array();
		foreach ($this->properties as $propId => $property)
		{
			if (isset($fields[$propId]))
				$propertyValues[$property['ID']] = $fields[$propId];
			unset($fields[$propId]);
		}
		if(count($propertyValues) > 0)
		{
			$fields['PROPERTY_VALUES'] = $propertyValues;
			$rsProperties = CIBlockElement::GetProperty(
				$catalogID,
				$ID,
				'sort', 'asc',
				array('ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'N')
			);
			while($property = $rsProperties->Fetch())
			{
				if (isset($property['USER_TYPE']) && !empty($property['USER_TYPE'])
					&& !array_key_exists($property['USER_TYPE'], $this->userTypes))
					continue;

				if($property['PROPERTY_TYPE'] !== 'F' && !array_key_exists($property['ID'], $propertyValues))
				{
					if(!array_key_exists($property['ID'], $fields['PROPERTY_VALUES']))
						$fields['PROPERTY_VALUES'][$property['ID']] = array();

					$fields['PROPERTY_VALUES'][$property['ID']][$property['PROPERTY_VALUE_ID']] = array(
						'VALUE' => $property['VALUE'],
						'DESCRIPTION' => $property['DESCRIPTION']
					);
				}
			}
		}

		$result = CCrmProduct::Update($ID, $fields);
		if($result !== true)
		{
			$errors[] = CCrmProduct::GetLastError();
		}
		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		if(!(CCrmProduct::CheckDeletePermission($ID) && CCrmProduct::EnsureDefaultCatalogScope($ID)))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmProduct::Delete($ID);
		if($result !== true)
		{
			$errors[] = CCrmProduct::GetLastError();
		}
		return $result;
	}

	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmProductRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmProductAdd'] = self::createEventInfo('catalog', 'OnProductAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmProductUpdate'] = self::createEventInfo('crm', 'OnAfterCrmProductUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmProductDelete'] = self::createEventInfo('iblock', 'OnAfterIBlockElementDelete', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		$eventName = $arHandler['EVENT_NAME'];
		switch (strtolower($eventName))
		{
			case 'oncrmproductadd':
			case 'oncrmproductupdate':
			{
				$ID = isset($arParams[0]) ? (int)$arParams[0] : 0;

				if($ID <= 0)
				{
					throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
				}

				$fields = CCrmProduct::GetByID($ID);
				$catalogID = is_array($fields) && isset($fields['CATALOG_ID']) ? (int)$fields['CATALOG_ID'] : 0;
				if($catalogID !== CCrmCatalog::GetDefaultID())
				{
					throw new RestException("Outside CRM product event is detected");
				}
				return array('FIELDS' => array('ID' => $ID));
			}
			break;
			case 'oncrmproductdelete':
			{
				$fields = isset($arParams[0]) && is_array($arParams[0]) ? $arParams[0] : array();
				$ID = isset($fields['ID']) ? (int)$fields['ID'] : 0;

				if($ID <= 0)
				{
					throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
				}

				$catalogID = isset($fields['IBLOCK_ID']) ? (int)$fields['IBLOCK_ID'] : 0;
				if($catalogID !== CCrmCatalog::GetDefaultID())
				{
					throw new RestException("Outside CRM product event is detected");
				}
				return array('FIELDS' => array('ID' => $ID));
			}
			break;
			default:
				throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}
	}
}

class CCrmProductPropertyRestProxy extends CCrmRestProxyBase
{
	private $TYPES_INFO = null;
	private $FIELDS_INFO = null;
	private $SETTINGS_FIELDS_INFO = null;
	private $ENUMERATION_FIELDS_INFO = null;
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = array(
				'ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'IBLOCK_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'XML_ID' => array(
					'TYPE' => 'string'
				),
				'NAME' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required)
				),
				'ACTIVE' => array(
					'TYPE' => 'char'
				),
				'IS_REQUIRED' => array(
					'TYPE' => 'char'
				),
				'SORT' => array(
					'TYPE' => 'integer'
				),
				'PROPERTY_TYPE' => array(
					'TYPE' => 'char',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Required, CCrmFieldInfoAttr::Immutable)
				),
				'MULTIPLE' => array(
					'TYPE' => 'char'
				),
				'DEFAULT_VALUE' => array(
					'TYPE' => 'object'
				),
				'ROW_COUNT' => array(
					'TYPE' => 'integer'
				),
				'COL_COUNT' => array(
					'TYPE' => 'integer'
				),
				'FILE_TYPE' => array(
					'TYPE' => 'string'
				),
				'LINK_IBLOCK_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'USER_TYPE' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Immutable)
				),
				'USER_TYPE_SETTINGS' => array(
					'TYPE' => 'object'
				),
				'VALUES' => array(
					'TYPE' => 'product_property_enum_element',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple)
				)
			);
		}

		return $this->FIELDS_INFO;
	}

	protected function getSettingsFieldsInfo($propertyType, $userType)
	{
		$fieldsInfo = array();

		if(!$this->SETTINGS_FIELDS_INFO)
		{
			$this->SETTINGS_FIELDS_INFO = array(
				'S' => array(
					'HTML' => array(
						'HEIGHT' => array(
							'TYPE' => 'integer'/*,
							'DEFAULT_VALUE' => 200*/
						)
					)
				),
				'E' => array(
					'Elist' => array(
						'SIZE' => array(
							'TYPE' => 'integer'/*,
							'DEFAULT_VALUE' => 1*/
						),
						'WIDTH' => array(
							'TYPE' => 'integer'/*,
							'DEFAULT_VALUE' => 0*/
						),
						'GROUP' => array(
							'TYPE' => 'char'/*,
							'DEFAULT_VALUE' => 'N'*/
						),
						'MULTIPLE' => array(
							'TYPE' => 'char'/*,
							'DEFAULT_VALUE' => 'N'*/
						)
					)
				),
				'N' => array(
					'Sequence' => array(
						'WRITE' => array(
							'TYPE' => 'char'/*,
							'DEFAULT_VALUE' => 'N'*/
						),
						'CURRENT_VALUE' => array(
							'TYPE' => 'integer'/*,
							'DEFAULT_VALUE' => '1'*/
						)
					)
				),
			);
		}

		if (isset($this->SETTINGS_FIELDS_INFO[$propertyType])
			&& isset($this->SETTINGS_FIELDS_INFO[$propertyType][$userType]))
		{
			$fieldsInfo = $this->SETTINGS_FIELDS_INFO[$propertyType][$userType];
		}

		return self::prepareFields($fieldsInfo);
	}

	protected function getEnumerationFieldsInfo()
	{
		if(!$this->ENUMERATION_FIELDS_INFO)
		{
			$this->ENUMERATION_FIELDS_INFO = array(
				'ID' => array('TYPE' => 'integer'),
				'VALUE' => array('TYPE' => 'string'),
				'XML_ID' => array('TYPE' => 'string'),
				'SORT' => array('TYPE' => 'integer'),
				'DEF' => array('TYPE' => 'char')
			);
		}

		return self::prepareFields($this->ENUMERATION_FIELDS_INFO);
	}

	protected function getTypesInfo()
	{
		$typesInfo = array();

		if(!$this->TYPES_INFO)
		{
			if(!CModule::IncludeModule('iblock'))
			{
				throw new RestException('Could not load iblock module.');
			}

			$descriptions = CCrmProductPropsHelper::GetPropsTypesDescriptions();
			$typesInfo = array(
				array('PROPERTY_TYPE' => 'S', 'USER_TYPE' => '', 'DESCRIPTION' => $descriptions['S']),
				array('PROPERTY_TYPE' => 'N', 'USER_TYPE' => '', 'DESCRIPTION' => $descriptions['N']),
				array('PROPERTY_TYPE' => 'L', 'USER_TYPE' => '', 'DESCRIPTION' => $descriptions['L']),
				array('PROPERTY_TYPE' => 'F', 'USER_TYPE' => '', 'DESCRIPTION' => $descriptions['F']),
				/*array('PROPERTY_TYPE' => 'G', 'USER_TYPE' => '', 'DESCRIPTION' => $descriptions['G']),*/
				array('PROPERTY_TYPE' => 'E', 'USER_TYPE' => '', 'DESCRIPTION' => $descriptions['E'])
			);
			$userTypes = CCrmProductPropsHelper::GetPropsTypesByOperations(false, 'rest');
			if (is_array($userTypes))
			{
				foreach ($userTypes as $propertyInfo)
				{
					$typesInfo[] = array(
						'PROPERTY_TYPE' => $propertyInfo['PROPERTY_TYPE'],
						'USER_TYPE' => $propertyInfo['USER_TYPE'],
						'DESCRIPTION' => $propertyInfo['DESCRIPTION']
					);
				}
			}

			$this->TYPES_INFO = $typesInfo;
		}

		return $this->TYPES_INFO;
	}

	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		/** @var CCrmPerms $userPerms */
		$userPerms = CCrmPerms::GetCurrentUserPermissions();
		if (!$userPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$iblockId = intval(CCrmCatalog::EnsureDefaultExists());

		$userTypeSettings = array();
		if (isset($fields['USER_TYPE_SETTINGS']) && is_array($fields['USER_TYPE_SETTINGS']))
			foreach ($fields['USER_TYPE_SETTINGS'] as $key => $value)
				$userTypeSettings[strtolower($key)] = $value;

		$arFields = array(
			'ACTIVE' => isset($fields['ACTIVE']) ? ($fields['ACTIVE'] === 'Y' ? 'Y' : 'N') : 'Y',
			'IBLOCK_ID' => $iblockId,
			'PROPERTY_TYPE' => $fields['PROPERTY_TYPE'],
			'USER_TYPE' => isset($fields['USER_TYPE']) ? $fields['USER_TYPE'] : '',
			'LINK_IBLOCK_ID' => ($fields['PROPERTY_TYPE'] === 'E' || $fields['PROPERTY_TYPE'] === 'G') ? $iblockId : 0,
			'NAME' => $fields['NAME'],
			'SORT' => isset($fields['SORT']) ? $fields['SORT'] : 500,
			'CODE' => '',
			'MULTIPLE' => isset($fields['MULTIPLE']) ? ($fields['MULTIPLE'] === 'Y' ? 'Y' : 'N') : 'N',
			'IS_REQUIRED' => isset($fields['IS_REQUIRED']) ? ($fields['IS_REQUIRED'] === 'Y' ? 'Y' : 'N') : 'N',
			'SEARCHABLE' => 'N',
			'FILTRABLE' => 'N',
			'WITH_DESCRIPTION' => '',
			'MULTIPLE_CNT' => isset($fields['MULTIPLE_CNT']) ? $fields['MULTIPLE_CNT'] : 0,
			'HINT' => '',
			'ROW_COUNT' => isset($fields['ROW_COUNT']) ? $fields['ROW_COUNT'] : 1,
			'COL_COUNT' => isset($fields['COL_COUNT']) ? $fields['COL_COUNT'] : 30,
			'DEFAULT_VALUE' => isset($fields['DEFAULT_VALUE']) ? $fields['DEFAULT_VALUE'] : null,
			'LIST_TYPE' => 'L',
			'USER_TYPE_SETTINGS' => $userTypeSettings,
			'FILE_TYPE' => isset($fields['FILE_TYPE']) ? $fields['FILE_TYPE'] : '',
			'XML_ID' => isset($fields['XML_ID']) ? $fields['XML_ID'] : ''
		);

		if ($arFields['PROPERTY_TYPE'].':'.$arFields['USER_TYPE'] === 'S:map_yandex')
			$arFields['MULTIPLE'] = 'N';

		if ($fields['PROPERTY_TYPE'] === 'L' && isset($fields['VALUES']) && is_array($fields['VALUES']))
		{
			$values = array();

			$newKey = 0;
			foreach ($fields['VALUES'] as $key => $value)
			{
				if (!is_array($value) || !isset($value['VALUE']) || '' == trim($value['VALUE']))
					continue;
				$values[(0 < intval($key) ? $key : 'n'.$newKey)] = array(
					'ID' => (0 < intval($key) ? $key : 'n'.$newKey),
					'VALUE' => strval($value['VALUE']),
					'XML_ID' => (isset($value['XML_ID']) ? strval($value['XML_ID']) : ''),
					'SORT' => (isset($value['SORT']) ? intval($value['SORT']) : 500),
					'DEF' => (isset($value['DEF']) ? ($value['DEF'] === 'Y' ? 'Y' : 'N') : 'N')
				);
				$newKey++;
			}

			$arFields['VALUES'] = $values;
		}

		$property = new CIBlockProperty;
		$result = $property->Add($arFields);

		if (intval($result) <= 0)
		{
			if (!empty($property->LAST_ERROR))
				$errors[] = $property->LAST_ERROR;
			else if($e = $APPLICATION->GetException())
				$errors[] = $e->GetString();
		}

		return $result;
	}

	protected function innerGet($id, &$errors)
	{
		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		/** @var CCrmPerms $userPerms */
		$userPerms = CCrmPerms::GetCurrentUserPermissions();
		if (!$userPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = false;
		$iblockId = intval(CCrmCatalog::EnsureDefaultExists());
		$userTypes = CCrmProductPropsHelper::GetPropsTypesByOperations(false, 'rest');
		$res = CIBlockProperty::GetByID($id, $iblockId);
		if (is_object($res))
			$result = $res->Fetch();
		unset($res);
		if(!is_array($result)
			|| (isset($result['USER_TYPE']) && !empty($result['USER_TYPE'])
				&& !array_key_exists($result['USER_TYPE'], $userTypes)))
		{
			$errors[] = 'Not found';
			return false;
		}

		$userTypeSettings = array();
		if (isset($result['USER_TYPE_SETTINGS']) && is_array($result['USER_TYPE_SETTINGS']))
		{
			foreach ($result['USER_TYPE_SETTINGS'] as $key => $value)
				$userTypeSettings[strtoupper($key)] = $value;
			$result['USER_TYPE_SETTINGS'] = $userTypeSettings;
		}

		return $result;
	}

	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		/** @var CCrmPerms $userPerms */
		$userPerms = CCrmPerms::GetCurrentUserPermissions();
		if (!$userPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$userTypes = CCrmProductPropsHelper::GetPropsTypesByOperations(false, 'rest');

		$filter['IBLOCK_ID'] = intval(CCrmCatalog::EnsureDefaultExists());
		$filter['CHECK_PERMISSIONS'] = 'N';
		$res = CIBlockProperty::GetList($order, $filter);
		$result = array();
		while ($row = $res->Fetch())
		{
			if ($row['PROPERTY_TYPE'] !== 'G'
				&& ($row['USER_TYPE'] == '' || array_key_exists($row['USER_TYPE'], $userTypes)))
			{
				$values = null;
				if ($row['PROPERTY_TYPE'] === 'L')
				{
					$values = array();
					$resEnum = CIBlockProperty::GetPropertyEnum($row['ID'], array('SORT' => 'ASC','ID' => 'ASC'));
					while($enumValue = $resEnum->Fetch())
					{
						$values[intval($enumValue['ID'])] = array(
							'ID' => $enumValue['ID'],
							'VALUE' => $enumValue['VALUE'],
							'XML_ID' => $enumValue['XML_ID'],
							'SORT' => $enumValue['SORT'],
							'DEF' => $enumValue['DEF']
						);
					}
				}
				$row['VALUES'] = $values;
				$result[] = $row;
			}
		}

		return $result;
	}

	protected function innerUpdate($id, &$fields, &$errors, array $params = null)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		/** @var CCrmPerms $userPerms */
		$userPerms = CCrmPerms::GetCurrentUserPermissions();
		if (!$userPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$iblockId = intval(CCrmCatalog::EnsureDefaultExists());
		$userTypes = CCrmProductPropsHelper::GetPropsTypesByOperations(false, 'rest');
		$res = CIBlockProperty::GetByID($id, $iblockId);
		$prop = false;
		if (is_object($res))
			$prop = $res->Fetch();
		unset($res);
		if(!is_array($prop)
			|| (isset($prop['USER_TYPE']) && !empty($prop['USER_TYPE'])
				&& !array_key_exists($prop['USER_TYPE'], $userTypes)))
		{
			$errors[] = 'Not found';
			return false;
		}

		$fields['IBLOCK_ID'] = $iblockId;
		$fields['PROPERTY_TYPE'] = $prop['PROPERTY_TYPE'];
		$fields['USER_TYPE'] = $prop['USER_TYPE'];

		if (isset($fields['USER_TYPE_SETTINGS']) && is_array($fields['USER_TYPE_SETTINGS']))
		{
			$userTypeSettings = array();
			foreach ($fields['USER_TYPE_SETTINGS'] as $key => $value)
				$userTypeSettings[strtolower($key)] = $value;
			$fields['USER_TYPE_SETTINGS'] = $userTypeSettings;
			unset($userTypeSettings);
		}

		if ($prop['PROPERTY_TYPE'] === 'L' && isset($fields['VALUES']) && is_array($fields['VALUES']))
		{
			$values = array();

			$newKey = 0;
			foreach ($fields['VALUES'] as $key => $value)
			{
				if (!is_array($value) || !isset($value['VALUE']) || '' == trim($value['VALUE']))
					continue;
				$values[(0 < intval($key) ? $key : 'n'.$newKey)] = array(
					'ID' => (0 < intval($key) ? $key : 'n'.$newKey),
					'VALUE' => strval($value['VALUE']),
					'XML_ID' => (isset($value['XML_ID']) ? strval($value['XML_ID']) : ''),
					'SORT' => (isset($value['SORT']) ? intval($value['SORT']) : 500),
					'DEF' => (isset($value['DEF']) ? ($value['DEF'] === 'Y' ? 'Y' : 'N') : 'N')
				);
				$newKey++;
			}
			$fields['VALUES'] = $values;
			unset($values);
		}

		if ($fields['PROPERTY_TYPE'].':'.$fields['USER_TYPE'] === 'S:map_yandex'
			&& isset($fields['MULTIPLE']) && $fields['MULTIPLE'] !== 'N')
		{
			$fields['MULTIPLE'] = 'N';
		}

		$property = new CIBlockProperty;
		$result = $property->Update($id, $fields);

		if (!$result)
		{
			if (!empty($property->LAST_ERROR))
				$errors[] = $property->LAST_ERROR;
			else if($e = $APPLICATION->GetException())
				$errors[] = $e->GetString();
		}

		return $result;
	}

	protected function innerDelete($id, &$errors, array $params = null)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(!CModule::IncludeModule('iblock'))
		{
			throw new RestException('Could not load iblock module.');
		}

		/** @var CCrmPerms $userPerms */
		$userPerms = CCrmPerms::GetCurrentUserPermissions();
		if (!$userPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$iblockId = intval(CCrmCatalog::EnsureDefaultExists());
		$userTypes = CCrmProductPropsHelper::GetPropsTypesByOperations(false, 'rest');
		$res = CIBlockProperty::GetByID($id, $iblockId);
		$result = false;
		if (is_object($res))
			$result = $res->Fetch();
		unset($res);
		if(!is_array($result)
			|| (isset($result['USER_TYPE']) && !empty($result['USER_TYPE'])
				&& !array_key_exists($result['USER_TYPE'], $userTypes)))
		{
			$errors[] = 'Not found';
			return false;
		}

		if(!CIBlockProperty::Delete($id))
		{
			if($e = $APPLICATION->GetException())
				$errors[] = $e->GetString();
			else
				$errors[] = 'Error on deleting product property.';
			return false;
		}

		return true;
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'PROPERTY')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if($nameSuffix === 'FIELDS')
			{
				return self::getFields();
			}
			elseif($nameSuffix === 'TYPES')
			{
				return $this->getTypesInfo();
			}
			else if($nameSuffix === 'SETTINGS_FIELDS')
			{
				$propertyType = $userType = '';
				foreach ($arParams as $name => $value)
				{
					switch (strtolower($name))
					{
						case 'propertytype':
							$propertyType = strval($value);
							break;
						case 'usertype':
							$userType = strval($value);
							break;
					}
				}
				if($propertyType === '')
				{
					throw new RestException("Parameter 'propertyType' is not specified or empty.");
				}
				if($userType === '')
				{
					throw new RestException("Parameter 'userType' is not specified or empty.");
				}

				return $this->getSettingsFieldsInfo($propertyType, $userType);
			}
			else if($nameSuffix === 'ENUMERATION_FIELDS')
			{
				return $this->getEnumerationFieldsInfo();
			}
			else if(in_array($nameSuffix, array('ADD', 'GET', 'LIST', 'UPDATE', 'DELETE'), true))
			{
				return parent::processMethodRequest($nameSuffix, '', $arParams, $nav, $server);
			}
		}

		throw new RestException("Resource '{$name}' is not supported in current context.");
	}
}

class CCrmProductSectionRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmProductSection::GetFieldsInfo();
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		if(!CCrmProduct::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmProductSection::Add($fields);
		if(!(is_int($result) && $result > 0))
		{
			$errors[] = CCrmProductSection::GetLastError();
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmProduct::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmProductSection::GetByID($ID);
		if(!is_array($result))
		{
			$errors[] = 'Product section is not found.';
			return null;
		}

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmProduct::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return CCrmProductSection::GetList($order, $filter, $select, $navigation);
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		if(!CCrmProduct::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmProductSection::Update($ID, $fields);
		if($result !== true)
		{
			$errors[] = CCrmProductSection::GetLastError();
		}
		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		if(!CCrmProduct::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmProductSection::Delete($ID);
		if($result !== true)
		{
			$errors[] = CCrmProductSection::GetLastError();
		}
		return $result;
	}
}

class CCrmProductRowRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmProductRow::GetFieldsInfo();
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		$ownerID = isset($fields['OWNER_ID']) ? intval($fields['OWNER_ID']) : 0;
		$ownerType = isset($fields['OWNER_TYPE']) ? $fields['OWNER_TYPE'] : '';

		if($ownerID <= 0 || $ownerType === '')
		{
			if ($ownerID <= 0)
			{
				$errors[] = 'The field OWNER_ID is required.';
			}

			if ($ownerType === '')
			{
				$errors[] = 'The field OWNER_TYPE is required.';
			}
			return false;
		}

		if(!CCrmAuthorizationHelper::CheckCreatePermission(
			CCrmProductRow::ResolveOwnerTypeName($ownerType)))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmProductRow::Add($fields, true, true);
		if(!is_int($result))
		{
			$errors[] = CCrmProductRow::GetLastError();
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		$result = CCrmProductRow::GetByID($ID);
		if(!is_array($result))
		{
			$errors[] = "Product Row not found";
		}

		if(!CCrmAuthorizationHelper::CheckReadPermission(
			CCrmProductRow::ResolveOwnerTypeName($result['OWNER_TYPE']),
			$result['OWNER_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		$ownerID = isset($filter['OWNER_ID']) ? intval($filter['OWNER_ID']) : 0;
		$ownerType = isset($filter['OWNER_TYPE']) ? $filter['OWNER_TYPE'] : '';

		if($ownerID <= 0 || $ownerType === '')
		{
			if ($ownerID <= 0)
			{
				$errors[] = 'The field OWNER_ID is required in filer.';
			}

			if ($ownerType === '')
			{
				$errors[] = 'The field OWNER_TYPE is required in filer.';
			}
			return false;
		}

		if($ownerType === 'I')
		{
			//Crutch for Invoices
			if(!CCrmInvoice::CheckReadPermission($ownerID))
			{
				$errors[] = 'Access denied.';
				return false;
			}

			$result = array();
			$productRows = CCrmInvoice::GetProductRows($ownerID);
			foreach($productRows as $productRow)
			{
				$price = isset($productRow['PRICE']) ? round((double)$productRow['PRICE'], 2) : 0.0;
				$discountSum = isset($productRow['DISCOUNT_PRICE']) ?
					round((double)$productRow['DISCOUNT_PRICE'], 2) : 0.0;
				$vatRate = isset($productRow['VAT_RATE']) ? (double)$productRow['VAT_RATE'] * 100 : 0.0;
				$taxRate = isset($productRow['VAT_RATE']) ? round((double)$productRow['VAT_RATE'] * 100, 2) : 0.0;

				if(isset($productRow['VAT_INCLUDED']) && $productRow['VAT_INCLUDED'] === 'N')
				{
					$exclusivePrice = $price;
					$price = round(CCrmProductRow::CalculateInclusivePrice($exclusivePrice, $vatRate), 2);
				}
				else
				{
					$exclusivePrice = round(CCrmProductRow::CalculateExclusivePrice($price, $vatRate), 2);
				}
				unset($vatRate);

				$discountRate = \Bitrix\Crm\Discount::calculateDiscountRate(($exclusivePrice + $discountSum), $exclusivePrice);

				$result[] = array(
					'ID' => $productRow['ID'],
					'OWNER_ID' => $ownerID,
					'OWNER_TYPE' => 'I',
					'PRODUCT_ID' => isset($productRow['PRODUCT_ID']) ? $productRow['PRODUCT_ID'] : 0,
					'PRODUCT_NAME' => isset($productRow['PRODUCT_NAME']) ? $productRow['PRODUCT_NAME'] : '',
					'PRICE' => $price,
					'QUANTITY' => isset($productRow['QUANTITY']) ? $productRow['QUANTITY'] : 0,
					'DISCOUNT_TYPE_ID' => \Bitrix\Crm\Discount::MONETARY,
					'DISCOUNT_RATE' => $discountRate,
					'DISCOUNT_SUM' => $discountSum,
					'TAX_RATE' => $taxRate,
					'TAX_INCLUDED' => isset($productRow['VAT_INCLUDED']) ? $productRow['VAT_INCLUDED'] : 'N',
					'MEASURE_CODE' => isset($productRow['MEASURE_CODE']) ? $productRow['MEASURE_CODE'] : '',
					'MEASURE_NAME' => isset($productRow['MEASURE_NAME']) ? $productRow['MEASURE_NAME'] : '',
					'CUSTOMIZED' => isset($productRow['CUSTOM_PRICE']) ? $productRow['CUSTOM_PRICE'] : 'N',
				);
			}
			return $result;
		}

		if(!CCrmAuthorizationHelper::CheckReadPermission(
			CCrmProductRow::ResolveOwnerTypeName($ownerType),
			$ownerID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return CCrmProductRow::GetList($order, $filter, false, $navigation, $select, array('IS_EXTERNAL_CONTEXT' => true));
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		$entity = CCrmProductRow::GetByID($ID);
		if(!is_array($entity))
		{
			$errors[] = "Product Row is not found";
			return false;
		}

		if(!CCrmAuthorizationHelper::CheckUpdatePermission(
			CCrmProductRow::ResolveOwnerTypeName($entity['OWNER_TYPE']),
			$entity['OWNER_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		// The fields OWNER_ID and OWNER_TYPE can not be changed.
		if(isset($fields['OWNER_ID']))
		{
			unset($fields['OWNER_ID']);
		}

		if(isset($fields['OWNER_TYPE']))
		{
			unset($fields['OWNER_TYPE']);
		}

		$result = CCrmProductRow::Update($ID, $fields, true, true);
		if($result !== true)
		{
			$errors[] = CCrmProductRow::GetLastError();
		}
		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		$entity = CCrmProductRow::GetByID($ID);
		if(!is_array($entity))
		{
			$errors[] = "Product Row is not found";
			return false;
		}

		if(!CCrmAuthorizationHelper::CheckDeletePermission(
			CCrmProductRow::ResolveOwnerTypeName($entity['OWNER_TYPE']),
			$entity['OWNER_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmProductRow::Delete($ID, true, true);
		if($result !== true)
		{
			$errors[] = CCrmProductRow::GetLastError();
		}
		return $result;
	}

	public function prepareForSave(&$fields)
	{
		$fieldsInfo = $this->getFieldsInfo();
		$this->internalizeFields($fields, $fieldsInfo);
	}
}

class CCrmLeadRestProxy extends CCrmRestProxyBase
{
	private static $ENTITY = null;
	private $FIELDS_INFO = null;
	public  function getOwnerTypeID()
	{
		return CCrmOwnerType::Lead;
	}
	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new CCrmLead(true);
		}

		return self::$ENTITY;
	}
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmLead::GetFieldsInfo();
			self::prepareMultiFieldsInfo($this->FIELDS_INFO);
			self::prepareUserFieldsInfo($this->FIELDS_INFO, CCrmLead::$sUFEntityID);
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		if(!CCrmLead::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		$entity = self::getEntity();
		$options = array();
		if(!$this->isRequiredUserFieldCheckEnabled())
		{
			$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
		}
		if(is_array($params) && isset($params['REGISTER_SONET_EVENT']))
		{
			$options['REGISTER_SONET_EVENT'] = strtoupper($params['REGISTER_SONET_EVENT']) === 'Y';
		}
		$result = $entity->Add($fields, true, $options);
		if($result <= 0)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		elseif(self::isBizProcEnabled())
		{
			CCrmBizProcHelper::AutoStartWorkflows(
				CCrmOwnerType::Lead,
				$result,
				CCrmBizProcEventType::Create,
				$errors
			);
		}

		//Region automation
		\Bitrix\Crm\Automation\Factory::runOnAdd(\CCrmOwnerType::Lead, $result);
		//End region

		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmLead::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbRes = CCrmLead::GetListEx(
			array(),
			array('=ID' => $ID),
			false,
			false,
			array(),
			array()
		);

		$result = $dbRes ? $dbRes->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'Not found';
			return false;
		}

		$result['FM'] = array();
		$fmResult = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => CCrmOwnerType::ResolveName(CCrmOwnerType::Lead),
				'ELEMENT_ID' => $ID
			)
		);

		while($fm = $fmResult->Fetch())
		{
			$fmTypeID = $fm['TYPE_ID'];
			if(!isset($result['FM'][$fmTypeID]))
			{
				$result['FM'][$fmTypeID] = array();
			}

			$result['FM'][$fmTypeID][] = array('ID' => $fm['ID'], 'VALUE_TYPE' => $fm['VALUE_TYPE'], 'VALUE' => $fm['VALUE']);
		}

		$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(CCrmLead::$sUFEntityID, $ID, LANGUAGE_ID);
		foreach($userFields as $ufName => &$ufData)
		{
			$result[$ufName] = isset($ufData['VALUE']) ? $ufData['VALUE'] : '';
		}
		unset($ufData);

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmLead::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$options = array('IS_EXTERNAL_CONTEXT' => true);
		if(is_array($order))
		{
			if(isset($order['STATUS_ID']))
			{
				$order['STATUS_SORT'] = $order['STATUS_ID'];
				unset($order['STATUS_ID']);

				$options['FIELD_OPTIONS'] = array('ADDITIONAL_FIELDS' => array('STATUS_SORT'));
			}
		}

		return CCrmLead::GetListEx($order, $filter, false, $navigation, $select, $options);
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		if(!CCrmLead::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(!CCrmLead::Exists($ID))
		{
			$errors[] = 'Lead is not found';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		$entity = self::getEntity();
		$compare = true;
		$options = array();
		if(!$this->isRequiredUserFieldCheckEnabled())
		{
			$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
		}
		if(is_array($params))
		{
			if(isset($params['REGISTER_HISTORY_EVENT']))
			{
				$compare = strtoupper($params['REGISTER_HISTORY_EVENT']) === 'Y';
			}

			if(isset($params['REGISTER_SONET_EVENT']))
			{
				$options['REGISTER_SONET_EVENT'] = strtoupper($params['REGISTER_SONET_EVENT']) === 'Y';
			}
		}

		//check STATUS_ID changes
		$statusChanged = false;
		if (isset($fields['STATUS_ID']))
		{
			$dbDocumentList = CCrmLead::GetListEx(
				array(),
				array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID', 'STATUS_ID')
			);
			$arPresentFields = $dbDocumentList->Fetch();
			if ($arPresentFields['STATUS_ID'] != $fields['STATUS_ID'])
				$statusChanged = true;
		}

		$result = $entity->Update($ID, $fields, $compare, true, $options);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		elseif(self::isBizProcEnabled())
		{
			CCrmBizProcHelper::AutoStartWorkflows(
				CCrmOwnerType::Lead,
				$ID,
				CCrmBizProcEventType::Edit,
				$errors
			);
		}

		//Region automation
		if ($statusChanged)
			\Bitrix\Crm\Automation\Factory::runOnStatusChanged(\CCrmOwnerType::Lead, $ID);
		//End region

		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		if(!CCrmLead::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();
		$result = $entity->Delete($ID, array('CHECK_DEPENDENCIES' => true));
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}

		return $result;
	}

	public function getProductRows($ID)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!CCrmLead::CheckReadPermission($ID))
		{
			throw new RestException('Access denied.');
		}

		return CCrmLead::LoadProductRows($ID);
	}
	public function setProductRows($ID, $rows)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!is_array($rows))
		{
			throw new RestException('The parameter rows must be array.');
		}

		if(!CCrmLead::CheckUpdatePermission($ID))
		{
			throw new RestException('Access denied.');
		}

		if(!CCrmLead::Exists($ID))
		{
			throw new RestException('Not found.');
		}

		$proxy = new CCrmProductRowRestProxy();

		$actualRows = array();
		$qty = count($rows);
		for($i = 0; $i < $qty; $i++)
		{
			$row = $rows[$i];
			if(!is_array($row))
			{
				continue;
			}

			$proxy->prepareForSave($row);
			if(isset($row['OWNER_TYPE']))
			{
				unset($row['OWNER_TYPE']);
			}

			if(isset($row['OWNER_ID']))
			{
				unset($row['OWNER_ID']);
			}

			$actualRows[] = $row;
		}

		return CCrmLead::SaveProductRows($ID, $actualRows, true, true, true);
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'PRODUCTROWS')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');

			if($nameSuffix === 'GET')
			{
				return $this->getProductRows($this->resolveEntityID($arParams));
			}
			elseif($nameSuffix === 'SET')
			{
				$ID = $this->resolveEntityID($arParams);
				$rows = $this->resolveArrayParam($arParams, 'rows');
				return $this->setProductRows($ID, $rows);
			}
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
	protected function getIdentityFieldName()
	{
		return 'ID';
	}
	protected function getIdentity(&$fields)
	{
		return isset($fields['ID']) ? intval($fields['ID']) : 0;
	}
	protected function getSupportedMultiFieldTypeIDs()
	{
		return self::getMultiFieldTypeIDs();
	}

	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmLeadRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmLeadAdd'] = self::createEventInfo('crm', 'OnAfterCrmLeadAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmLeadUpdate'] = self::createEventInfo('crm', 'OnAfterCrmLeadUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmLeadDelete'] = self::createEventInfo('crm', 'OnAfterCrmLeadDelete', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		return parent::processEntityEvent(CCrmOwnerType::Lead, $arParams, $arHandler);
	}
}

class CCrmDealRestProxy extends CCrmRestProxyBase
{
	private static $ENTITY = null;
	private $FIELDS_INFO = null;
	public  function getOwnerTypeID()
	{
		return CCrmOwnerType::Deal;
	}
	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new CCrmDeal(true);
		}

		return self::$ENTITY;
	}
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmDeal::GetFieldsInfo();
			self::prepareUserFieldsInfo($this->FIELDS_INFO, CCrmDeal::$sUFEntityID);
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		$categoryID = isset($fields['CATEGORY_ID']) ? (int)$fields['CATEGORY_ID'] : 0;
		$fields['CATEGORY_ID'] = $categoryID = max($categoryID, 0);
		if(!CCrmDeal::CheckCreatePermission(\CCrmPerms::GetCurrentUserPermissions(), $categoryID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		$entity = self::getEntity();
		$options = array();
		if(!$this->isRequiredUserFieldCheckEnabled())
		{
			$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
		}
		if(is_array($params) && isset($params['REGISTER_SONET_EVENT']))
		{
			$options['REGISTER_SONET_EVENT'] = strtoupper($params['REGISTER_SONET_EVENT']) === 'Y';
		}
		$result = $entity->Add($fields, true, $options);
		if($result <= 0)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		elseif(self::isBizProcEnabled())
		{
			CCrmBizProcHelper::AutoStartWorkflows(
				CCrmOwnerType::Deal,
				$result,
				CCrmBizProcEventType::Create,
				$errors
			);
		}

		//Region automation
		\Bitrix\Crm\Automation\Factory::runOnAdd(\CCrmOwnerType::Deal, $result);
		//End region

		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		$categoryID = CCrmDeal::GetCategoryID($ID);
		if($categoryID < 0)
		{
			$errors[] = !CCrmDeal::CheckReadPermission(0, $userPermissions) ? 'Access denied' : 'Not found';
			return false;
		}
		elseif(!CCrmDeal::CheckReadPermission($ID, CCrmPerms::GetCurrentUserPermissions(), $categoryID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbRes = CCrmDeal::GetListEx(
			array(),
			array('=ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array(),
			array()
		);

		$result = $dbRes ? $dbRes->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'Not found';
			return false;
		}

		$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(CCrmDeal::$sUFEntityID, $ID, LANGUAGE_ID);
		foreach($userFields as $ufName => &$ufData)
		{
			$result[$ufName] = isset($ufData['VALUE']) ? $ufData['VALUE'] : '';
		}
		unset($ufData);

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmDeal::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$options = array('IS_EXTERNAL_CONTEXT' => true);
		if(is_array($order))
		{
			if(isset($order['STAGE_ID']))
			{
				$order['STAGE_SORT'] = $order['STAGE_ID'];
				unset($order['STAGE_ID']);

				$options['FIELD_OPTIONS'] = array('ADDITIONAL_FIELDS' => array('STAGE_SORT'));
			}
		}

		return CCrmDeal::GetListEx($order, $filter, false, $navigation, $select, $options);
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		$categoryID = CCrmDeal::GetCategoryID($ID);
		if($categoryID < 0)
		{
			$errors[] = !CCrmDeal::CheckUpdatePermission(0, $userPermissions) ? 'Access denied' : 'Not found';
			return false;
		}
		elseif(!CCrmDeal::CheckUpdatePermission($ID, $userPermissions, $categoryID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		$entity = self::getEntity();
		$compare = true;
		$options = array();
		if(!$this->isRequiredUserFieldCheckEnabled())
		{
			$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
		}
		if(is_array($params))
		{
			if(isset($params['REGISTER_HISTORY_EVENT']))
			{
				$compare = strtoupper($params['REGISTER_HISTORY_EVENT']) === 'Y';
			}

			if(isset($params['REGISTER_SONET_EVENT']))
			{
				$options['REGISTER_SONET_EVENT'] = strtoupper($params['REGISTER_SONET_EVENT']) === 'Y';
			}
		}

		//check STAGE_ID changes
		$stageChanged = false;
		if (isset($fields['STAGE_ID']))
		{
			$dbDocumentList = CCrmDeal::GetListEx(
				array(),
				array('ID' => $ID, 'CHECK_PERMISSIONS' => 'N'),
				false,
				false,
				array('ID', 'STAGE_ID')
			);
			$arPresentFields = $dbDocumentList->Fetch();
			if ($arPresentFields['STAGE_ID'] != $fields['STAGE_ID'])
				$stageChanged = true;
		}

		$result = $entity->Update($ID, $fields, $compare, true, $options);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		elseif(self::isBizProcEnabled())
		{
			CCrmBizProcHelper::AutoStartWorkflows(
				CCrmOwnerType::Deal,
				$ID,
				CCrmBizProcEventType::Edit,
				$errors
			);
		}

		//Region automation
		if ($stageChanged)
			\Bitrix\Crm\Automation\Factory::runOnStatusChanged(\CCrmOwnerType::Deal, $ID);
		//End region

		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		$categoryID = CCrmDeal::GetCategoryID($ID);
		if($categoryID < 0)
		{
			$errors[] = !CCrmDeal::CheckDeletePermission(0, $userPermissions) ? 'Access denied' : 'Not found';
			return false;
		}
		elseif(!CCrmDeal::CheckDeletePermission($ID, $userPermissions, $categoryID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();
		$result = $entity->Delete($ID);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}

		return $result;
	}

	public function getProductRows($ID)
	{
		$ID = (int)$ID;
		if($ID <= 0)
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		$categoryID = CCrmDeal::GetCategoryID($ID);
		if($categoryID < 0)
		{
			throw new RestException(
				!CCrmDeal::CheckReadPermission(0, $userPermissions) ? 'Access denied' : 'Not found'
			);
		}
		elseif(!CCrmDeal::CheckReadPermission($ID, $userPermissions, $categoryID))
		{
			throw new RestException('Access denied.');
		}

		return CCrmDeal::LoadProductRows($ID);
	}
	public function setProductRows($ID, $rows)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!is_array($rows))
		{
			throw new RestException('The parameter rows must be array.');
		}

		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		$categoryID = CCrmDeal::GetCategoryID($ID);
		if($categoryID < 0)
		{
			throw new RestException(
				!CCrmDeal::CheckUpdatePermission(0, $userPermissions) ? 'Access denied' : 'Not found'
			);
		}
		elseif(!CCrmDeal::CheckUpdatePermission($ID, $userPermissions, $categoryID))
		{
			throw new RestException('Access denied.');
		}

		if(!CCrmDeal::Exists($ID))
		{
			throw new RestException('Not found.');
		}

		$proxy = new CCrmProductRowRestProxy();

		$actualRows = array();
		$qty = count($rows);
		for($i = 0; $i < $qty; $i++)
		{
			$row = $rows[$i];
			if(!is_array($row))
			{
				continue;
			}

			$proxy->prepareForSave($row);
			if(isset($row['OWNER_TYPE']))
			{
				unset($row['OWNER_TYPE']);
			}

			if(isset($row['OWNER_ID']))
			{
				unset($row['OWNER_ID']);
			}

			$actualRows[] = $row;
		}

		return CCrmDeal::SaveProductRows($ID, $actualRows, true, true, true);
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
		if($name === 'PRODUCTROWS')
		{
			if($nameSuffix === 'GET')
			{
				return $this->getProductRows($this->resolveEntityID($arParams));
			}
			elseif($nameSuffix === 'SET')
			{
				$ID = $this->resolveEntityID($arParams);
				$rows = $this->resolveArrayParam($arParams, 'rows');
				return $this->setProductRows($ID, $rows);
			}
		}
		elseif($name === 'CONTACT')
		{
			$bindRequestDetails = $nameDetails;
			$bindRequestName = array_shift($bindRequestDetails);
			$bindingProxy = new CCrmEntityBindingProxy(CCrmOwnerType::Deal, CCrmOwnerType::Contact);
			return $bindingProxy->processMethodRequest($bindRequestName, $bindRequestDetails, $arParams, $nav, $server);
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
	protected function getSupportedMultiFieldTypeIDs()
	{
		return self::getMultiFieldTypeIDs();
	}
	protected function getIdentityFieldName()
	{
		return 'ID';
	}
	protected function getIdentity(&$fields)
	{
		return isset($fields['ID']) ? intval($fields['ID']) : 0;
	}

	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmDealRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmDealAdd'] = self::createEventInfo('crm', 'OnAfterCrmDealAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmDealUpdate'] = self::createEventInfo('crm', 'OnAfterCrmDealUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmDealDelete'] = self::createEventInfo('crm', 'OnAfterCrmDealDelete', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		return parent::processEntityEvent(CCrmOwnerType::Deal, $arParams, $arHandler);
	}
}

class CCrmDealCategoryProxy extends CCrmRestProxyBase
{
	protected $FIELDS_INFO = null;
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = DealCategory::getFieldsInfo();
		}
		return $this->FIELDS_INFO;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmDeal::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		/** @var Main\DB\Result $dbResult */
		$dbResult = DealCategory::getList(array('filter' => array('=ID' => $ID)));
		$result = $dbResult->fetch();
		if(!is_array($result))
		{
			$errors[] = 'Not found';
			return false;
		}
		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmDeal::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$params = array();
		if(is_array($order) && !empty($order))
		{
			$params['order'] = $order;
		}

		if(is_array($filter) && !empty($filter))
		{
			$params['filter'] = $filter;
		}

		if(is_array($select) && !empty($select))
		{
			$params['select'] = $select;
		}

		/** @var Main\DB\Result $dbResult */
		$dbResult = DealCategory::getList($params);
		$items = array();
		while($fields = $dbResult->fetch())
		{
			$items[] = $fields;
		}
		return $items;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		if (!$userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		try
		{
			return DealCategory::add($fields);
		}
		catch(Main\SystemException $ex)
		{
			$errors[] = $ex->getMessage();
			return false;
		}
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		if (!$userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(!DealCategory::exists($ID))
		{
			$errors[] = 'Not found.';
			return false;
		}

		try
		{
			DealCategory::update($ID, $fields);
			return true;
		}
		catch(Main\SystemException $ex)
		{
			$errors[] = $ex->getMessage();
			return false;
		}
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		$userPermissions = \CCrmPerms::GetCurrentUserPermissions();
		if (!$userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE'))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(!DealCategory::exists($ID))
		{
			$errors[] = 'Not found.';
			return false;
		}

		try
		{
			DealCategory::delete($ID);
			return true;
		}
		catch(Main\SystemException $ex)
		{
			$errors[] = $ex->getMessage();
			return false;
		}
	}
	public function resolveStatusEntityID($ID)
	{
		return $ID > 0 ? DealCategory::convertToStatusEntityID($ID) : 'DEAL_STAGE';
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'STATUS')
		{
			return $this->resolveStatusEntityID(CCrmRestHelper::resolveEntityID($arParams));
		}
		elseif($name === 'STAGE')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if($nameSuffix === 'LIST')
			{
				$statusProxy = new CCrmStatusRestProxy();
				return $statusProxy->getEntityItems(
					$this->resolveStatusEntityID(CCrmRestHelper::resolveEntityID($arParams))
				);
			}
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
}

class CCrmCompanyRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private static $ENTITY = null;
	public  function getOwnerTypeID()
	{
		return CCrmOwnerType::Company;
	}
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmCompany::GetFieldsInfo();
			self::prepareMultiFieldsInfo($this->FIELDS_INFO);
			self::prepareUserFieldsInfo($this->FIELDS_INFO, CCrmCompany::$sUFEntityID);
		}
		return $this->FIELDS_INFO;
	}
	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new CCrmCompany(true);
		}

		return self::$ENTITY;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		if(!CCrmCompany::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		$entity = self::getEntity();
		$options = array();
		if(!$this->isRequiredUserFieldCheckEnabled())
		{
			$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
		}
		if(is_array($params) && isset($params['REGISTER_SONET_EVENT']))
		{
			$options['REGISTER_SONET_EVENT'] = strtoupper($params['REGISTER_SONET_EVENT']) === 'Y';
		}
		$result = $entity->Add($fields, true, $options);
		if($result <= 0)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		elseif(self::isBizProcEnabled())
		{
			CCrmBizProcHelper::AutoStartWorkflows(
				CCrmOwnerType::Company,
				$result,
				CCrmBizProcEventType::Create,
				$errors
			);
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmCompany::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbRes = CCrmCompany::GetListEx(
			array(),
			array('=ID' => $ID),
			false,
			false,
			array(),
			array()
		);

		$result = $dbRes ? $dbRes->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'Not found';
			return false;
		}

		$result['FM'] = array();
		$fmResult = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => CCrmOwnerType::ResolveName(CCrmOwnerType::Company),
				'ELEMENT_ID' => $ID
			)
		);

		while($fm = $fmResult->Fetch())
		{
			$fmTypeID = $fm['TYPE_ID'];
			if(!isset($result['FM'][$fmTypeID]))
			{
				$result['FM'][$fmTypeID] = array();
			}

			$result['FM'][$fmTypeID][] = array('ID' => $fm['ID'], 'VALUE_TYPE' => $fm['VALUE_TYPE'], 'VALUE' => $fm['VALUE']);
		}

		$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(CCrmCompany::$sUFEntityID, $ID, LANGUAGE_ID);
		foreach($userFields as $ufName => &$ufData)
		{
			$result[$ufName] = isset($ufData['VALUE']) ? $ufData['VALUE'] : '';
		}
		unset($ufData);

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmCompany::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return CCrmCompany::GetListEx(
			$order,
			$filter,
			false,
			$navigation,
			$select,
			array('IS_EXTERNAL_CONTEXT' => true)
		);
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		if(!CCrmCompany::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(!CCrmCompany::Exists($ID))
		{
			$errors[] = 'Company is not found';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		$entity = self::getEntity();
		$compare = true;
		$options = array();
		if(!$this->isRequiredUserFieldCheckEnabled())
		{
			$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
		}
		if(is_array($params))
		{
			if(isset($params['REGISTER_HISTORY_EVENT']))
			{
				$compare = strtoupper($params['REGISTER_HISTORY_EVENT']) === 'Y';
			}

			if(isset($params['REGISTER_SONET_EVENT']))
			{
				$options['REGISTER_SONET_EVENT'] = strtoupper($params['REGISTER_SONET_EVENT']) === 'Y';
			}
		}

		$result = $entity->Update($ID, $fields, $compare, true, $options);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		elseif(self::isBizProcEnabled())
		{
			CCrmBizProcHelper::AutoStartWorkflows(
				CCrmOwnerType::Company,
				$ID,
				CCrmBizProcEventType::Edit,
				$errors
			);
		}
		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		if(!CCrmCompany::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();
		$result = $entity->Delete($ID);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}

		return $result;
	}
	protected function getSupportedMultiFieldTypeIDs()
	{
		return self::getMultiFieldTypeIDs();
	}
	protected function getIdentityFieldName()
	{
		return 'ID';
	}
	protected function getIdentity(&$fields)
	{
		return isset($fields['ID']) ? intval($fields['ID']) : 0;
	}

	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmCompanyRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmCompanyAdd'] = self::createEventInfo('crm', 'OnAfterCrmCompanyAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmCompanyUpdate'] = self::createEventInfo('crm', 'OnAfterCrmCompanyUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmCompanyDelete'] = self::createEventInfo('crm', 'OnAfterCrmCompanyDelete', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		return parent::processEntityEvent(CCrmOwnerType::Company, $arParams, $arHandler);
	}
}

class CCrmContactRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private static $ENTITY = null;

	public  function getOwnerTypeID()
	{
		return CCrmOwnerType::Contact;
	}
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmContact::GetFieldsInfo();
			self::prepareMultiFieldsInfo($this->FIELDS_INFO);
			self::prepareUserFieldsInfo($this->FIELDS_INFO, CCrmContact::$sUFEntityID);
		}
		return $this->FIELDS_INFO;
	}
	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new CCrmContact(true);
		}

		return self::$ENTITY;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		if(!CCrmContact::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		$entity = self::getEntity();
		$options = array();
		if(!$this->isRequiredUserFieldCheckEnabled())
		{
			$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
		}
		if(is_array($params) && isset($params['REGISTER_SONET_EVENT']))
		{
			$options['REGISTER_SONET_EVENT'] = strtoupper($params['REGISTER_SONET_EVENT']) === 'Y';
		}
		$result = $entity->Add($fields, true, $options);
		if($result <= 0)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		elseif(self::isBizProcEnabled())
		{
			CCrmBizProcHelper::AutoStartWorkflows(
				CCrmOwnerType::Contact,
				$result,
				CCrmBizProcEventType::Create,
				$errors
			);
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmContact::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbRes = CCrmContact::GetListEx(
			array(),
			array('=ID' => $ID),
			false,
			false,
			array(),
			array()
		);

		$result = $dbRes ? $dbRes->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'Not found';
			return false;
		}

		$result['FM'] = array();
		$fmResult = CCrmFieldMulti::GetList(
			array('ID' => 'asc'),
			array(
				'ENTITY_ID' => CCrmOwnerType::ResolveName(CCrmOwnerType::Contact),
				'ELEMENT_ID' => $ID
			)
		);

		while($fm = $fmResult->Fetch())
		{
			$fmTypeID = $fm['TYPE_ID'];
			if(!isset($result['FM'][$fmTypeID]))
			{
				$result['FM'][$fmTypeID] = array();
			}

			$result['FM'][$fmTypeID][] = array('ID' => $fm['ID'], 'VALUE_TYPE' => $fm['VALUE_TYPE'], 'VALUE' => $fm['VALUE']);
		}

		$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(CCrmContact::$sUFEntityID, $ID, LANGUAGE_ID);
		foreach($userFields as $ufName => &$ufData)
		{
			$result[$ufName] = isset($ufData['VALUE']) ? $ufData['VALUE'] : '';
		}
		unset($ufData);

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmContact::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return CCrmContact::GetListEx(
			$order,
			$filter,
			false,
			$navigation,
			$select,
			array('IS_EXTERNAL_CONTEXT' => true)
		);
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		if(!CCrmContact::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(!CCrmContact::Exists($ID))
		{
			$errors[] = 'Contact is not found';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		$entity = self::getEntity();
		$compare = true;
		$options = array();
		if(!$this->isRequiredUserFieldCheckEnabled())
		{
			$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
		}
		if(is_array($params))
		{
			if(isset($params['REGISTER_HISTORY_EVENT']))
			{
				$compare = strtoupper($params['REGISTER_HISTORY_EVENT']) === 'Y';
			}

			if(isset($params['REGISTER_SONET_EVENT']))
			{
				$options['REGISTER_SONET_EVENT'] = strtoupper($params['REGISTER_SONET_EVENT']) === 'Y';
			}
		}

		$result = $entity->Update($ID, $fields, $compare, true, $options);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		elseif(self::isBizProcEnabled())
		{
			CCrmBizProcHelper::AutoStartWorkflows(
				CCrmOwnerType::Contact,
				$ID,
				CCrmBizProcEventType::Edit,
				$errors
			);
		}
		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		if(!CCrmContact::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();
		$result = $entity->Delete($ID);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}

		return $result;
	}
	protected function getSupportedMultiFieldTypeIDs()
	{
		return self::getMultiFieldTypeIDs();
	}
	protected function getIdentityFieldName()
	{
		return 'ID';
	}
	protected function getIdentity(&$fields)
	{
		return isset($fields['ID']) ? intval($fields['ID']) : 0;
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'COMPANY')
		{
			$bindRequestDetails = $nameDetails;
			$bindRequestName = array_shift($bindRequestDetails);
			$bindingProxy = new CCrmEntityBindingProxy(CCrmOwnerType::Contact, CCrmOwnerType::Company);
			return $bindingProxy->processMethodRequest($bindRequestName, $bindRequestDetails, $arParams, $nav, $server);
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}

	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmContactRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmContactAdd'] = self::createEventInfo('crm', 'OnAfterCrmContactAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmContactUpdate'] = self::createEventInfo('crm', 'OnAfterCrmContactUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmContactDelete'] = self::createEventInfo('crm', 'OnAfterCrmContactDelete', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		return parent::processEntityEvent(CCrmOwnerType::Contact, $arParams, $arHandler);
	}
}

class CCrmCurrencyRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private $LOC_FIELDS_INFO = null;
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmCurrency::GetFieldsInfo();
			$this->FIELDS_INFO['LANG'] = array(
				'TYPE' => 'currency_localization',
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple)
			);
		}
		return $this->FIELDS_INFO;
	}
	public function getLocalizationFieldsInfo()
	{
		if(!$this->LOC_FIELDS_INFO)
		{
			$this->LOC_FIELDS_INFO = CCrmCurrency::GetCurrencyLocalizationFieldsInfo();
		}
		return $this->LOC_FIELDS_INFO;
	}
	public function isValidID($ID)
	{
		return is_string($ID) && $ID !== '';
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		if(!CCrmCurrency::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmCurrency::Add($fields);
		if($result === false)
		{
			$errors[] = CCrmCurrency::GetLastError();
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmCurrency::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmCurrency::GetByID($ID);
		if(is_array($result))
		{
			return $result;
		}

		$errors[] = 'Not found';
		return false;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmCurrency::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return CCrmCurrency::GetList($order);
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		if(!CCrmCurrency::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(!CCrmCurrency::IsExists($ID))
		{
			$errors[] = 'Currency is not found';
			return false;
		}

		$result = CCrmCurrency::Update($ID, $fields);
		if($result !== true)
		{
			$errors[] = CCrmCurrency::GetLastError();
		}
		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		if(!CCrmCurrency::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmCurrency::Delete($ID);
		if($result !== true)
		{
			$errors[] = CCrmCurrency::GetLastError();
		}

		return $result;
	}
	protected function resolveEntityID(&$arParams)
	{
		return isset($arParams['ID'])
			? strtoupper($arParams['ID'])
			: (isset($arParams['id']) ? strtoupper($arParams['id']) : '');
	}
	protected function checkEntityID($ID)
	{
		return is_string($ID) && $ID !== '';
	}
	public function getLocalizations($ID)
	{
		$ID = strval($ID);
		if($ID === '')
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!CCrmCurrency::CheckReadPermission($ID))
		{
			throw new RestException('Access denied.');
		}

		return CCrmCurrency::GetCurrencyLocalizations($ID);
	}
	public function setLocalizations($ID, $localizations)
	{
		$ID = strval($ID);
		if($ID === '')
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!is_array($localizations) || empty($localizations))
		{
			return false;
		}

		if(!CCrmCurrency::CheckUpdatePermission($ID))
		{
			throw new RestException('Access denied.');
		}

		return CCrmCurrency::SetCurrencyLocalizations($ID, $localizations);
	}
	public function deleteLocalizations($ID, $langs)
	{
		$ID = strval($ID);
		if($ID === '')
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!is_array($langs) || empty($langs))
		{
			return false;
		}

		if(!CCrmCurrency::CheckUpdatePermission($ID))
		{
			throw new RestException('Access denied.');
		}

		return CCrmCurrency::DeleteCurrencyLocalizations($ID, $langs);
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'LOCALIZATIONS')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if($nameSuffix === 'FIELDS')
			{
				$fildsInfo = $this->getLocalizationFieldsInfo();
				return parent::prepareFields($fildsInfo);
			}
			elseif($nameSuffix === 'GET')
			{
				return $this->getLocalizations($this->resolveEntityID($arParams));
			}
			elseif($nameSuffix === 'SET')
			{
				$ID = $this->resolveEntityID($arParams);
				$localizations = $this->resolveArrayParam($arParams, 'localizations');
				return $this->setLocalizations($ID, $localizations);
			}
			elseif($nameSuffix === 'DELETE')
			{
				$ID = $this->resolveEntityID($arParams);
				$lids = $this->resolveArrayParam($arParams, 'lids');
				return $this->deleteLocalizations($ID, $lids);
			}
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}

	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmCurrencyRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmCurrencyAdd'] = self::createEventInfo('currency', 'OnCurrencyAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmCurrencyUpdate'] = self::createEventInfo('currency', 'OnCurrencyUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmCurrencyDelete'] = self::createEventInfo('currency', 'OnCurrencyDelete', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		$eventName = $arHandler['EVENT_NAME'];
		switch (strtolower($eventName))
		{
			case 'oncrmcurrencyadd':
			case 'oncrmcurrencyupdate':
			case 'oncrmcurrencydelete':
			{
				$ID = isset($arParams[0]) && is_string($arParams[0]) ? $arParams[0] : '';
			}
			break;
			default:
				throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}

		if($ID === '')
		{
			throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
		}
		return array('FIELDS' => array('ID' => $ID));
	}
}

class CCrmStatusRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private static $ENTITY_TYPES = null;
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmStatus::GetFieldsInfo();
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		if(!CCrmStatus::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entityID = isset($fields['ENTITY_ID']) ? $fields['ENTITY_ID'] : '';
		$statusID = isset($fields['STATUS_ID']) ? $fields['STATUS_ID'] : '';
		if($entityID === '' || $statusID === '')
		{
			if($entityID === '')
			{
				$errors[] = 'The field ENTITY_ID is required.';
			}

			if($statusID === '')
			{
				$errors[] = 'The field STATUS_ID is required.';
			}

			return false;
		}

		$entityTypes = self::prepareEntityTypes();
		if(!isset($entityTypes[$entityID]))
		{
			$errors[] = 'Specified entity type is not supported.';
			return false;
		}

		$fields['SYSTEM'] = 'N';
		$entity = new CCrmStatus($entityID);
		$result = $entity->Add($fields, true);
		if($result === false)
		{
			$errors[] = $entity->GetLastError();
		}
		elseif(isset($fields['EXTRA']))
		{
			self::saveExtra($fields);
		}
		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmStatus::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbResult = CCrmStatus::GetList(array(), array('ID' => $ID));
		$result = is_object($dbResult) ? $dbResult->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'CRM Status is not found.';
			return null;
		}

		self::prepareExtra($result);
		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmStatus::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(!is_array($order))
		{
			$order = array();
		}

		if(empty($order))
		{
			$order['sort'] = 'asc';
		}

		$results = array();
		$dbResult = CCrmStatus::GetList($order, $filter);
		if(is_object($dbResult))
		{
			while($item = $dbResult->Fetch())
			{
				self::prepareExtra($item);
				$results[] = $item;
			}
		}
		return $results;
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		if(!CCrmStatus::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbResult = CCrmStatus::GetList(array(), array('ID' => $ID));
		$currentFields = $dbResult ? $dbResult->Fetch() : null;
		if(!is_array($currentFields))
		{
			$errors[] = 'Status is not found.';
			return false;
		}

		$result = true;
		if(isset($fields['NAME']) || isset($fields['SORT']) || isset($fields['STATUS_ID']))
		{
			if(!isset($fields['NAME']))
			{
				$fields['NAME'] = $currentFields['NAME'];
			}

			if(!isset($fields['SORT']))
			{
				$fields['SORT'] = $currentFields['SORT'];
			}
			$entity = new CCrmStatus($currentFields['ENTITY_ID']);
			$result = $entity->Update($ID, $fields);
			if($result === false)
			{
				$errors[] = $entity->GetLastError();
			}
		}
		if($result && isset($fields['EXTRA']))
		{
			$fields['ENTITY_ID'] = $currentFields['ENTITY_ID'];
			if(!isset($fields['STATUS_ID']))
			{
				$fields['STATUS_ID'] = $currentFields['STATUS_ID'];
			}
			self::saveExtra($fields);
		}

		return $result !== false;

	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		if(!CCrmStatus::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbResult = CCrmStatus::GetList(array(), array('ID' => $ID));
		$currentFields = $dbResult ? $dbResult->Fetch() : null;
		if(!is_array($currentFields))
		{
			$errors[] = 'Status is not found.';
			return false;
		}

		$isSystem = isset($currentFields['SYSTEM']) && $currentFields['SYSTEM'] === 'Y';
		$forced = is_array($params) && isset($params['FORCED']) && $params['FORCED'] === 'Y';

		if($isSystem && !$forced)
		{
			$errors[] = 'CRM System Status can be deleted only if parameter FORCED is specified and equal to "Y".';
			return false;
		}

		$entity = new CCrmStatus($currentFields['ENTITY_ID']);
		$result = $entity->Delete($ID);
		if($result === false)
		{
			$errors[] = $entity->GetLastError();
		}
		return $result !== false;
	}
	private static function prepareExtra(array &$fields)
	{
		$statusID = isset($fields['STATUS_ID']) ? $fields['STATUS_ID'] : '';
		if($statusID === '')
		{
			return null;
		}

		$result = null;
		$colorScheme = null;
		$entityID = isset($fields['ENTITY_ID']) ? $fields['ENTITY_ID'] : '';
		if($entityID === 'STATUS')
		{
			$result = array('SEMANTICS' => CCrmLead::GetStatusSemantics($statusID));
			$colorScheme = LeadStatusColorScheme::getCurrent();
		}
		elseif($entityID === 'QUOTE_STATUS')
		{
			$result = array('SEMANTICS' => CCrmQuote::GetStatusSemantics($statusID));
			$colorScheme = QuoteStatusColorScheme::getCurrent();
		}
		elseif($entityID === 'DEAL_STAGE')
		{
			$result = array('SEMANTICS' => CCrmDeal::GetStageSemantics($statusID, 0));
			$colorScheme = DealStageColorScheme::getByCategory(0);
		}
		elseif(DealCategory::hasStatusEntity($entityID))
		{
			$categoryID = DealCategory::convertFromStatusEntityID($entityID);
			$result = array('SEMANTICS' => CCrmDeal::GetStageSemantics($statusID, $categoryID));
			$colorScheme = DealStageColorScheme::getByCategory($categoryID);
		}

		if(is_array($result))
		{
			if($colorScheme !== null && $colorScheme->isPersistent())
			{
				$element = $colorScheme->getElementByName($statusID);
				if($element !== null)
				{
					$result['COLOR'] = $element->getColor();
				}
			}
			$fields['EXTRA'] = $result;
		}
	}
	private static function saveExtra(array $fields)
	{
		$extra = isset($fields['EXTRA']) && is_array($fields['EXTRA']) ? $fields['EXTRA'] : null;
		if(empty($extra) || !isset($extra['COLOR']) || !is_string($extra['COLOR']))
		{
			return;
		}
		$color = $extra['COLOR'];

		$statusID = isset($fields['STATUS_ID']) ? $fields['STATUS_ID'] : '';
		if($statusID === '')
		{
			return;
		}

		$colorScheme = null;
		$entityID = isset($fields['ENTITY_ID']) ? $fields['ENTITY_ID'] : '';
		if($entityID === 'STATUS')
		{
			$colorScheme = LeadStatusColorScheme::getCurrent();
		}
		elseif($entityID === 'QUOTE_STATUS')
		{
			$colorScheme = QuoteStatusColorScheme::getCurrent();
		}
		elseif($entityID === 'DEAL_STAGE')
		{
			$colorScheme = DealStageColorScheme::getByCategory(0);
		}
		elseif(DealCategory::hasStatusEntity($entityID))
		{
			$colorScheme = DealStageColorScheme::getByCategory(
				DealCategory::convertFromStatusEntityID($entityID)
			);
		}

		if($colorScheme !== null)
		{
			$isChanged = false;

			$element = $colorScheme->getElementByName($statusID);
			if($element !== null)
			{
				if($color === '')
				{
					$color = $colorScheme->getDefaultColor($statusID);
				}

				if($element->getColor() !== $color)
				{
					$element->setColor($color);
					$isChanged = true;
				}
			}
			else
			{
				$colorScheme->addElement(new PhaseColorSchemeElement($statusID, $color));
				$isChanged = true;
			}

			if($isChanged)
			{
				$colorScheme->save();
			}
		}
	}
	private static function prepareEntityTypes()
	{
		if(!self::$ENTITY_TYPES)
		{
			self::$ENTITY_TYPES = CCrmStatus::GetEntityTypes();
		}

		return self::$ENTITY_TYPES;
	}
	public function getEntityTypes()
	{
		return array_values(self::prepareEntityTypes());
	}
	public function getEntityItems($entityID)
	{
		if(!CCrmStatus::CheckReadPermission(0))
		{
			throw new RestException('Access denied.');
		}

		if($entityID === '')
		{
			throw new RestException('The parameter entityId is not defined or invalid.');
		}

		//return CCrmStatus::GetStatusList($entityID);
		$dbResult = CCrmStatus::GetList(array('sort' => 'asc'), array('ENTITY_ID' => strtoupper($entityID)));
		if(!$dbResult)
		{
			return array();
		}

		$result = array();
		while($fields = $dbResult->Fetch())
		{
			$result[] = array(
				'NAME' => $fields['NAME'],
				'SORT' => intval($fields['SORT']),
				'STATUS_ID' => $fields['STATUS_ID']
			);
		}

		return $result;
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'ENTITY')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if($nameSuffix === 'TYPES')
			{
				return $this->getEntityTypes();
			}
			elseif($nameSuffix === 'ITEMS')
			{
				return $this->getEntityItems($this->resolveRelationID($arParams, 'entity'));
			}
		}
		elseif($name === 'EXTRA')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if($nameSuffix === 'FIELDS')
			{
				return CCrmStatus::GetFieldExtraTypeInfo();
			}
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
}

class CCrmStatusInvoiceRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmStatusInvoice::GetFieldsInfo();
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(!CCrmStatus::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$statusInvoice = new CCrmStatusInvoice('INVOICE_STATUS');
		$result = $statusInvoice->Add($fields);
		if($result === false)
		{
			if ($e = $APPLICATION->GetException())
				$errors[] = $e->GetString();
			else
				$errors[] = 'Error on creating status.';
		}
		elseif(is_string($result))
		{
			$result = ord($result);
		}

		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmStatus::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$crmStatus = new CCrmStatus('INVOICE_STATUS');
		$result = $crmStatus->getStatusById($ID);
		if($result === false)
		{
			$errors[] = 'Status is not found.';
		}

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmStatus::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return CCrmStatusInvoice::GetList($order, $filter, $select);
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(!CCrmStatus::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$statusInvoice = new CCrmStatusInvoice('INVOICE_STATUS');
		$currentFields = $statusInvoice->getStatusById($ID);
		if(!is_array($currentFields))
		{
			$errors[] = 'Status is not found.';
			return false;
		}

		$result = $statusInvoice->Update($ID, $fields);
		if($result === false)
		{
			if ($e = $APPLICATION->GetException())
				$errors[] = $e->GetString();
			else
				$errors[] = 'Error on updating status.';
		}

		return $result !== false;

	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if(!CCrmStatus::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$statusInvoice = new CCrmStatusInvoice('INVOICE_STATUS');
		$currentFields = $statusInvoice->getStatusById($ID);
		if(!is_array($currentFields))
		{
			$errors[] = 'Status is not found.';
			return false;
		}

		$statusId = intval($ID);
		if ($statusId === ($statusId & 0xFF) && $statusId >= 65 && $statusId <= 90)
		{
			$statusId = chr($statusId);
			if (isset($currentFields['SYSTEM']) && $currentFields['SYSTEM'] === 'Y')
			{
				$errors[] = "Can't delete system status";
				return false;
			}
		}
		unset($statusId);

		$result = $statusInvoice->Delete($ID);
		if($result === false)
		{
			if ($e = $APPLICATION->GetException())
				$errors[] = $e->GetString();
			else
				$errors[] = 'Error on deleting status.';
		}
		return $result !== false;
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'STATUS')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');

			switch ($nameSuffix)
			{
				case 'FIELDS':
				case 'ADD':
				case 'GET':
				case 'LIST':
				case 'UPDATE':
				case 'DELETE':
					return parent::processMethodRequest($nameSuffix, '', $arParams, $nav, $server);
					break;
			}
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
}

class CCrmActivityRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private $COMM_FIELDS_INFO = null;
	public function getOwnerTypeID()
	{
		return CCrmOwnerType::Activity;
	}
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmActivity::GetFieldsInfo();
			$this->FIELDS_INFO['COMMUNICATIONS'] = array(
				'TYPE' => 'crm_activity_communication',
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple, CCrmFieldInfoAttr::Required)
			);

			$storageTypeID =  CCrmActivity::GetDefaultStorageTypeID();
			if($storageTypeID === StorageType::Disk)
			{
				$this->FIELDS_INFO['FILES'] = array(
					'TYPE' => 'diskfile',
					'ALIAS' => 'WEBDAV_ELEMENTS',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple),
				);
				$this->FIELDS_INFO['WEBDAV_ELEMENTS'] = array(
					'TYPE' => 'diskfile',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Deprecated, CCrmFieldInfoAttr::Multiple)
				);
			}
			else
			{
				$this->FIELDS_INFO['WEBDAV_ELEMENTS'] = array(
					'TYPE' => 'webdav',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple)
				);
			}
			$this->FIELDS_INFO['BINDINGS'] = array(
				'TYPE' => 'crm_activity_binding',
				'ATTRIBUTES' => array(CCrmFieldInfoAttr::Multiple, CCrmFieldInfoAttr::ReadOnly)
			);
		}
		return $this->FIELDS_INFO;
	}
	protected function getCommunicationFieldsInfo()
	{
		if(!$this->COMM_FIELDS_INFO)
		{
			$this->COMM_FIELDS_INFO = CCrmActivity::GetCommunicationFieldsInfo();
		}
		return $this->COMM_FIELDS_INFO;
	}
	protected function prepareCommunications($ownerTypeID, $ownerID, $typeID, &$communications, &$bindings)
	{
		foreach($communications as $k => &$v)
		{
			$commEntityTypeID = $v['ENTITY_TYPE_ID'] ? intval($v['ENTITY_TYPE_ID']) : 0;
			$commEntityID = $v['ENTITY_ID'] ? intval($v['ENTITY_ID']) : 0;
			$commValue = $v['VALUE'] ? $v['VALUE'] : '';
			$commType = $v['TYPE'] ? $v['TYPE'] : '';

			if($commValue !== '' && ($commEntityTypeID <= 0 || $commEntityID <= 0))
			{
				// Push owner info into communication (if ommited)
				$commEntityTypeID = $v['ENTITY_TYPE_ID'] = $ownerTypeID;
				$commEntityID = $v['ENTITY_ID'] = $ownerID;
			}

			if($commEntityTypeID <= 0 || $commEntityID <= 0 || $commValue === '')
			{
				unset($communications[$k]);
				continue;
			}

			if($commType === '')
			{
				if($typeID === CCrmActivityType::Call)
				{
					$v['TYPE'] = 'PHONE';
				}
				elseif($typeID === CCrmActivityType::Email)
				{
					$v['TYPE'] = 'EMAIL';
				}
			}
			elseif(($typeID === CCrmActivityType::Call && $commType !== 'PHONE')
				|| ($typeID === CCrmActivityType::Email && $commType !== 'EMAIL'))
			{
				// Invalid communication type is specified
				unset($communications[$k]);
				continue;
			}

			$bindings["{$commEntityTypeID}_{$commEntityID}"] = array(
				'OWNER_TYPE_ID' => $commEntityTypeID,
				'OWNER_ID' => $commEntityID
			);
		}
		unset($v);
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		$ownerTypeID = isset($fields['OWNER_TYPE_ID']) ? intval($fields['OWNER_TYPE_ID']) : 0;
		$ownerID = isset($fields['OWNER_ID']) ? intval($fields['OWNER_ID']) : 0;

		$bindings = array();
		if($ownerTypeID > 0 && $ownerID > 0)
		{
			$bindings["{$ownerTypeID}_{$ownerID}"] = array(
				'OWNER_TYPE_ID' => $ownerTypeID,
				'OWNER_ID' => $ownerID
			);
		}

		$responsibleID = isset($fields['RESPONSIBLE_ID']) ? intval($fields['RESPONSIBLE_ID']) : 0;
		if($responsibleID <= 0 && $ownerTypeID > 0 && $ownerID > 0)
		{
			$fields['RESPONSIBLE_ID'] = $responsibleID = CCrmOwnerType::GetResponsibleID($ownerTypeID, $ownerID);
		}

		if($responsibleID <= 0)
		{
			$responsibleID = CCrmSecurityHelper::GetCurrentUserID();
		}

		if($responsibleID <= 0)
		{
			$errors[] = 'The field RESPONSIBLE_ID is not defined or invalid.';
			return false;
		}

		if (isset($fields['PROVIDER_ID']) && empty($fields['TYPE_ID']))
			$fields['TYPE_ID'] = CCrmActivityType::Provider;

		$typeID = isset($fields['TYPE_ID']) ? intval($fields['TYPE_ID']) : CCrmActivityType::Undefined;
		if(!CCrmActivityType::IsDefined($typeID))
		{
			$errors[] = 'The field TYPE_ID is not defined or invalid.';
			return false;
		}

		if ($typeID === CCrmActivityType::Provider && ($provider = CCrmActivity::GetActivityProvider($fields)) === null)
		{
			$errors[] = 'The custom activity without provider is not supported in current context.';
			return false;
		}

		if(!in_array($typeID, array(CCrmActivityType::Call, CCrmActivityType::Meeting, CCrmActivityType::Email, CCrmActivityType::Provider), true))
		{
			$errors[] = 'The activity type "'.CCrmActivityType::ResolveDescription($typeID).' is not supported in current context".';
			return false;
		}

		$description = isset($fields['DESCRIPTION']) ? $fields['DESCRIPTION'] : '';
		$descriptionType = isset($fields['DESCRIPTION_TYPE']) ? intval($fields['DESCRIPTION_TYPE']) : CCrmContentType::PlainText;
		if($description !== '' && CCrmActivity::AddEmailSignature($description, $descriptionType))
		{
			$fields['DESCRIPTION'] = $description;
		}

		$direction = isset($fields['DIRECTION']) ? intval($fields['DIRECTION']) : CCrmActivityDirection::Undefined;
		$completed = isset($fields['COMPLETED']) && strtoupper($fields['COMPLETED']) === 'Y';
		$communications = isset($fields['COMMUNICATIONS']) && is_array($fields['COMMUNICATIONS'])
			? $fields['COMMUNICATIONS'] : array();

		$this->prepareCommunications($ownerTypeID, $ownerID, $typeID, $communications, $bindings);

		if(empty($communications) && $typeID !== CCrmActivityType::Provider)
		{
			$errors[] = 'The field COMMUNICATIONS is not defined or invalid.';
			return false;
		}

		if(($typeID === CCrmActivityType::Call || $typeID === CCrmActivityType::Meeting)
			&& count($communications) > 1)
		{
			$errors[] = 'The only one communication is allowed for activity of specified type.';
			return false;
		}

		if(empty($bindings))
		{
			$errors[] = 'Could not build binding. Please ensure that owner info and communications are defined correctly.';
			return false;
		}

		foreach($bindings as &$binding)
		{
			if(!CCrmActivity::CheckUpdatePermission($binding['OWNER_TYPE_ID'], $binding['OWNER_ID']))
			{
				$errors[] = 'Access denied.';
				return false;
			}
		}
		unset($binding);

		$fields['BINDINGS'] = array_values($bindings);
		$fields['COMMUNICATIONS'] = $communications;
		$storageTypeID = $fields['STORAGE_TYPE_ID'] = CCrmActivity::GetDefaultStorageTypeID();
		$fields['STORAGE_ELEMENT_IDS'] = array();

		if($storageTypeID === StorageType::WebDav)
		{
			$webdavElements = isset($fields['WEBDAV_ELEMENTS']) && is_array($fields['WEBDAV_ELEMENTS'])
				? $fields['WEBDAV_ELEMENTS'] : array();

			foreach($webdavElements as &$element)
			{
				$elementID = isset($element['ELEMENT_ID']) ? intval($element['ELEMENT_ID']) : 0;
				if($elementID > 0)
				{
					$fields['STORAGE_ELEMENT_IDS'][] = $elementID;
				}
			}
			unset($element);
		}
		elseif($storageTypeID === StorageType::Disk)
		{
			$diskFiles = isset($fields['FILES']) && is_array($fields['FILES'])
				? $fields['FILES'] : array();

			if(empty($diskFiles))
			{
				//For backward compatibility only
				$diskFiles = isset($fields['WEBDAV_ELEMENTS']) && is_array($fields['WEBDAV_ELEMENTS'])
					? $fields['WEBDAV_ELEMENTS'] : array();
			}

			foreach($diskFiles as &$fileInfo)
			{
				$fileID = isset($fileInfo['FILE_ID']) ? (int)$fileInfo['FILE_ID'] : 0;
				if($fileID > 0)
				{
					$fields['STORAGE_ELEMENT_IDS'][] = $fileID;
				}
			}
			unset($fileInfo);
		}

		if(!($ID = CCrmActivity::Add($fields)))
		{
			$errors[] = CCrmActivity::GetLastErrorMessage();
			return false;
		}

		CCrmActivity::SaveCommunications($ID, $communications, $fields, false, false);

		if($completed
			&& $typeID === CCrmActivityType::Email
			&& $direction === CCrmActivityDirection::Outgoing)
		{
			$sendErrors = array();
			if(!CCrmActivityEmailSender::TrySendEmail($ID, $fields, $sendErrors))
			{
				foreach($sendErrors as &$error)
				{
					$code = $error['CODE'];
					if($code === CCrmActivityEmailSender::ERR_CANT_LOAD_SUBSCRIBE)
					{
						$errors[] = 'Email send error. Failed to load module "subscribe".';
					}
					elseif($code === CCrmActivityEmailSender::ERR_INVALID_DATA)
					{
						$errors[] = 'Email send error. Invalid data.';
					}
					elseif($code === CCrmActivityEmailSender::ERR_INVALID_EMAIL)
					{
						$errors[] = 'Email send error. Invalid email is specified.';
					}
					elseif($code === CCrmActivityEmailSender::ERR_CANT_FIND_EMAIL_FROM)
					{
						$errors[] = 'Email send error. "From" is not found.';
					}
					elseif($code === CCrmActivityEmailSender::ERR_CANT_FIND_EMAIL_TO)
					{
						$errors[] = 'Email send error. "To" is not found.';
					}
					elseif($code === CCrmActivityEmailSender::ERR_CANT_ADD_POSTING)
					{
						$errors[] = 'Email send error. Failed to add posting. Please see details below.';
					}
					elseif($code === CCrmActivityEmailSender::ERR_CANT_SAVE_POSTING_FILE)
					{
						$errors[] = 'Email send error. Failed to save posting file. Please see details below.';
					}
					elseif($code === CCrmActivityEmailSender::ERR_CANT_UPDATE_ACTIVITY)
					{
						$errors[] = 'Email send error. Failed to update activity.';
					}
					else
					{
						$errors[] = 'Email send error. General error.';
					}

					$msg = isset($error['MESSAGE']) ? $error['MESSAGE'] : '';
					if($msg !== '')
					{
						$errors[] = $msg;
					}
				}
				unset($error);
				return false;
			}
		}
		return $ID;
	}
	protected function innerGet($ID, &$errors)
	{
		// Permissions will be checked by default
		$dbResult = CCrmActivity::GetList(array(), array('ID' => $ID));
		if($dbResult)
		{
			return $dbResult->Fetch();
		}

		$errors[] = 'Activity is not found.';
		return null;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!is_array($order))
		{
			$order = array();
		}

		if(empty($order))
		{
			$order['START_TIME'] = 'ASC';
		}

		if(!is_array($select))
		{
			$select = array();
		}

		//Proces storage aliases
		if(array_search('STORAGE_ELEMENT_IDS', $select, true) === false
			&& (array_search('FILES', $select, true) !== false || array_search('WEBDAV_ELEMENTS', $select, true) !== false))
		{
			$select[] = 'STORAGE_ELEMENT_IDS';
		}

		// Permissions will be checked by default
		return CCrmActivity::GetList($order, $filter, false, $navigation, $select, array('IS_EXTERNAL_CONTEXT' => true));
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		$currentFields = CCrmActivity::GetByID($ID);
		CCrmActivity::PrepareStorageElementIDs($currentFields);

		if(!is_array($currentFields))
		{
			$errors[] = 'Activity is not found.';
			return false;
		}

		$typeID = intval($currentFields['TYPE_ID']);
		$currentOwnerID = intval($currentFields['OWNER_ID']);
		$currentOwnerTypeID = intval($currentFields['OWNER_TYPE_ID']);

		if(!CCrmActivity::CheckUpdatePermission($currentOwnerTypeID, $currentOwnerID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$ownerID = isset($fields['OWNER_ID']) ? intval($fields['OWNER_ID']) : 0;
		if($ownerID <= 0)
		{
			$ownerID = $currentOwnerID;
		}

		$ownerTypeID = isset($fields['OWNER_TYPE_ID']) ? intval($fields['OWNER_TYPE_ID']) : 0;
		if($ownerTypeID <= 0)
		{
			$ownerTypeID = $currentOwnerTypeID;
		}

		if(($ownerTypeID !== $currentOwnerTypeID || $ownerID !== $currentOwnerID)
			&& !CCrmActivity::CheckUpdatePermission($ownerTypeID, $ownerID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$communications = isset($fields['COMMUNICATIONS']) && is_array($fields['COMMUNICATIONS'])
			? $fields['COMMUNICATIONS'] : null;

		if(is_array($communications))
		{
			$bindings = array();
			if($ownerTypeID > 0 && $ownerID > 0)
			{
				$bindings["{$ownerTypeID}_{$ownerID}"] = array(
					'OWNER_TYPE_ID' => $ownerTypeID,
					'OWNER_ID' => $ownerID
				);
			}

			$this->prepareCommunications($ownerTypeID, $ownerID, $typeID, $communications, $bindings);

			if(empty($communications))
			{
				$errors[] = 'The field COMMUNICATIONS is not defined or invalid.';
				return false;
			}

			$fields['BINDINGS'] = array_values($bindings);
			$fields['COMMUNICATIONS'] = $communications;
		}


		$storageTypeID = $fields['STORAGE_TYPE_ID'] = CCrmActivity::GetDefaultStorageTypeID();
		$fields['STORAGE_ELEMENT_IDS'] = array();
		if($storageTypeID === StorageType::WebDav)
		{
			$webdavElements = isset($fields['WEBDAV_ELEMENTS']) && is_array($fields['WEBDAV_ELEMENTS'])
				? $fields['WEBDAV_ELEMENTS'] : array();

			$prevStorageElementIDs = isset($currentFields['STORAGE_ELEMENT_IDS']) ? $currentFields['STORAGE_ELEMENT_IDS'] : array();
			$oldStorageElementIDs = array();
			foreach($webdavElements as &$element)
			{
				$elementID = isset($element['ELEMENT_ID']) ? intval($element['ELEMENT_ID']) : 0;
				if($elementID > 0)
				{
					$fields['STORAGE_ELEMENT_IDS'][] = $elementID;
				}

				$oldElementID = isset($element['OLD_ELEMENT_ID']) ? intval($element['OLD_ELEMENT_ID']) : 0;
				if($oldElementID > 0
					&& ($elementID > 0 || (isset($element['DELETE']) && $element['DELETE'] === true)))
				{
					if(in_array($oldElementID, $prevStorageElementIDs))
					{
						$oldStorageElementIDs[] = $oldElementID;
					}
				}
			}
			unset($element);
		}
		else if($storageTypeID === StorageType::Disk)
		{
			$diskFiles = isset($fields['FILES']) && is_array($fields['FILES'])
				? $fields['FILES'] : array();

			if(empty($diskFiles))
			{
				//For backward compatibility only
				$diskFiles = isset($fields['WEBDAV_ELEMENTS']) && is_array($fields['WEBDAV_ELEMENTS'])
					? $fields['WEBDAV_ELEMENTS'] : array();
			}

			foreach($diskFiles as &$fileInfo)
			{
				$fileID = isset($fileInfo['FILE_ID']) ? (int)$fileInfo['FILE_ID'] : 0;
				if($fileID > 0)
				{
					$fields['STORAGE_ELEMENT_IDS'][] = $fileID;
				}
			}
			unset($fileInfo);
		}

		$regEvent = true;
		if(is_array($params) && isset($params['REGISTER_HISTORY_EVENT']))
		{
			$regEvent = strtoupper($params['REGISTER_HISTORY_EVENT']) === 'Y';
		}

		$result = CCrmActivity::Update($ID, $fields, false, $regEvent, array());
		if($result === false)
		{
			$errors[] = CCrmActivity::GetLastErrorMessage();
		}
		else
		{
			if(is_array($communications))
			{
				CCrmActivity::SaveCommunications($ID, $communications, $fields, false, false);
			}

			if(!empty($oldStorageElementIDs))
			{
				$webdavIBlock = $this->prepareWebDavIBlock();
				foreach($oldStorageElementIDs as $elementID)
				{
					$webdavIBlock->Delete(array('element_id' => $elementID));
				}
			}
		}

		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		$currentFields = CCrmActivity::GetByID($ID);
		if(!is_array($currentFields))
		{
			$errors[] = 'Activity is not found.';
			return false;
		}

		if(!CCrmActivity::CheckDeletePermission(
			$currentFields['OWNER_TYPE_ID'], $currentFields['OWNER_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$result = CCrmActivity::Delete($ID, false, true, array());
		if($result === false)
		{
			$errors[] = CCrmActivity::GetLastErrorMessage();
		}

		return $result;
	}
	protected function externalizeFields(&$fields, &$fieldsInfo)
	{
		$storageTypeID = isset($fields['STORAGE_TYPE_ID'])
			? $fields['STORAGE_TYPE_ID'] : CCrmActivity::GetDefaultStorageTypeID();

		if(isset($fields['STORAGE_ELEMENT_IDS']))
		{
			CCrmActivity::PrepareStorageElementIDs($fields);
			if($storageTypeID === Bitrix\Crm\Integration\StorageType::Disk)
			{
				$fields['FILES'] = $fields['STORAGE_ELEMENT_IDS'];
			}
			elseif($storageTypeID === Bitrix\Crm\Integration\StorageType::WebDav)
			{
				$fields['WEBDAV_ELEMENTS'] = $fields['STORAGE_ELEMENT_IDS'];
			}
			unset($fields['STORAGE_ELEMENT_IDS']);
		}
		parent::externalizeFields($fields, $fieldsInfo);
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'COMMUNICATION')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if($nameSuffix === 'FIELDS')
			{
				$fieldsInfo = $this->getCommunicationFieldsInfo();
				return parent::prepareFields($fieldsInfo);
			}
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmActivityRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmActivityAdd'] = self::createEventInfo('crm', 'OnActivityAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmActivityUpdate'] = self::createEventInfo('crm', 'OnActivityUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmActivityDelete'] = self::createEventInfo('crm', 'OnActivityDelete', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		$eventName = $arHandler['EVENT_NAME'];
		switch (strtolower($eventName))
		{
			case 'oncrmactivityadd':
			case 'oncrmactivityupdate':
			case 'oncrmactivitydelete':
			{
				$ID = isset($arParams[0]) ? (int)$arParams[0] : 0;
			}
			break;
			default:
				throw new RestException("The Event \"{$eventName}\" is not supported in current context");
		}

		if($ID <= 0)
		{
			throw new RestException("Could not find entity ID in fields of event \"{$eventName}\"");
		}
		return array('FIELDS' => array('ID' => $ID));
	}
}

class CCrmDuplicateRestProxy extends CCrmRestProxyBase
{
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$userPerms = CCrmPerms::GetCurrentUserPermissions();
		if(!CCrmLead::CheckReadPermission(0, $userPerms)
			&& !CCrmContact::CheckReadPermission(0, $userPerms)
			&& !CCrmCompany::CheckReadPermission(0, $userPerms))
		{
			throw new RestException('Access denied.');
		}

		if(strtoupper($name) === 'FINDBYCOMM')
		{
			$type = strtoupper($this->resolveParam($arParams, 'type'));
			if($type !== 'EMAIL' && $type !== 'PHONE')
			{
				if($type === '')
				{
					throw new RestException("Communication type is not defined.");
				}
				else
				{
					throw new RestException("Communication type '{$type}' is not supported in current context.");
				}
			}

			$values = $this->resolveArrayParam($arParams, 'values');
			if(!is_array($values) || count($values) === 0)
			{
				throw new RestException("Communication values is not defined.");
			}

			$entityTypeID = CCrmOwnerType::ResolveID(
				$this->resolveMultiPartParam($arParams, array('entity', 'type'))
			);

			if($entityTypeID === CCrmOwnerType::Deal)
			{
				throw new RestException("Deal is not supported in current context.");
			}

			$criterions = array();
			$dups = array();
			$qty = 0;
			foreach($values as $value)
			{
				if(!is_string($value) || $value === '')
				{
					continue;
				}

				$criterion = new \Bitrix\Crm\Integrity\DuplicateCommunicationCriterion($type, $value);
				$isExists = false;
				foreach($criterions as $curCriterion)
				{
					/** @var \Bitrix\Crm\Integrity\DuplicateCriterion $curCriterion */
					if($criterion->equals($curCriterion))
					{
						$isExists = true;
						break;
					}
				}

				if($isExists)
				{
					continue;
				}
				$criterions[] = $criterion;

				$duplicate = $criterion->find($entityTypeID, 20);
				if($duplicate !== null)
				{
					$dups[] = $duplicate;
				}

				$qty++;
				if($qty >= 20)
				{
					break;
				}
			}

			$entityByType = array();
			foreach($dups as $dup)
			{
				/** @var \Bitrix\Crm\Integrity\Duplicate $dup */
				$entities = $dup->getEntities();
				if(!(is_array($entities) && !empty($entities)))
				{
					continue;
				}

				//Each entity type limited by 50 items
				foreach($entities as $entity)
				{
					/** @var \Bitrix\Crm\Integrity\DuplicateEntity $entity */
					$entityTypeID = $entity->getEntityTypeID();
					$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);

					$entityID = $entity->getEntityID();

					if(!isset($entityByType[$entityTypeName]))
					{
						$entityByType[$entityTypeName] = array($entityID);
					}
					elseif(!in_array($entityID, $entityByType[$entityTypeName], true))
					{
						$entityByType[$entityTypeName][] = $entityID;
					}
				}
			}
			return $entityByType;
		}
		throw new RestException('Method not found!', RestException::ERROR_METHOD_NOT_FOUND, CRestServer::STATUS_NOT_FOUND);
	}
}

class CCrmLiveFeedMessageRestProxy extends CCrmRestProxyBase
{
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		global $USER;

		$name = strtoupper($name);
		if($name === 'ADD')
		{
			$fields = $this->resolveArrayParam($arParams, 'fields');

			$arComponentResult = array(
				'USER_ID' => $this->getCurrentUserID()
			);

			$arPOST = array(
				'ENABLE_POST_TITLE' => 'Y',
				'MESSAGE' => $fields['MESSAGE'],
				'SPERM' => $fields['SPERM']
			);

			if (
				isset($fields['POST_TITLE'])
				&& strlen($fields['POST_TITLE']) > 0
			)
			{
				$arPOST['POST_TITLE'] = $fields['POST_TITLE'];
			}

			$entityTypeID = $fields['ENTITYTYPEID'];
			$entityID = $fields['ENTITYID'];

			$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
			$userPerms = CCrmPerms::GetCurrentUserPermissions();

			if(
				$entityTypeName !== ''
				&& !CCrmAuthorizationHelper::CheckUpdatePermission($entityTypeName, $entityID, $userPerms)
			)
			{
				throw new RestException('Access denied.');
			}

			if (
				isset($fields["FILES"])
				&& \Bitrix\Main\Config\Option::get('disk', 'successfully_converted', false)
				&& CModule::includeModule('disk')
				&& ($storage = \Bitrix\Disk\Driver::getInstance()->getStorageByUserId($USER->getID()))
				&& ($folder = $storage->getFolderForUploadedFiles())
			)
			{
				$arComponentResult["WEB_DAV_FILE_FIELD_NAME"] = "UF_SONET_LOG_DOC";

				// upload to storage
				$arResultFile = array();

				foreach($fields["FILES"] as $tmp)
				{
					$arFile = CRestUtil::saveFile($tmp);

					if(is_array($arFile))
					{
						$file = $folder->uploadFile(
							$arFile, // file array
							array(
								'NAME' => $arFile["name"],
								'CREATED_BY' => $USER->getID()
							),
							array(),
							true
						);

						if ($file)
						{
							$arResultFile[] = \Bitrix\Disk\Uf\FileUserType::NEW_FILE_PREFIX.$file->getId();
						}
					}
				}

				if (!empty($arResultFile))
				{
					$arPOST['UF_SONET_LOG_DOC'] = $arResultFile;
				}
			}

			$res = CCrmLiveFeedComponent::ProcessLogEventEditPOST($arPOST, $entityTypeID, $entityID, $arComponentResult);

			if(is_array($res))
			{
				throw new RestException(implode(", ", $res));
			}

			return $res;
		}

		throw new RestException('Method not found!', RestException::ERROR_METHOD_NOT_FOUND, CRestServer::STATUS_NOT_FOUND);
	}
}

class CCrmEntityBindingProxy extends CCrmRestProxyBase
{
	protected $ownerEntityTypeID = CCrmOwnerType::Undefined;
	protected $entityTypeID = CCrmOwnerType::Undefined;
	protected $FIELDS_INFO = null;
	function __construct($ownerEntityTypeID, $entityTypeID)
	{
		$this->setOwnerEntityTypeID($ownerEntityTypeID);
		$this->setEntityTypeID($entityTypeID);
	}
	public function setOwnerEntityTypeID($entityTypeID)
	{
		if(is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new RestException("Parameter 'entityTypeID' is not defined");
		}

		if($entityTypeID !== CCrmOwnerType::Deal
			&& $entityTypeID !== CCrmOwnerType::Quote
			&& $entityTypeID !== CCrmOwnerType::Contact)
		{
			$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
			throw new RestException("The owner entity type '{$entityTypeName}' is not supported in current context.");
		}

		$this->ownerEntityTypeID = $entityTypeID;
	}
	public function getOwnerEntityTypeID()
	{
		return $this->ownerEntityTypeID;
	}
	public function setEntityTypeID($entityTypeID)
	{
		if(is_int($entityTypeID))
		{
			$entityTypeID = (int)$entityTypeID;
		}

		if(!CCrmOwnerType::IsDefined($entityTypeID))
		{
			throw new RestException("Parameter 'entityTypeID' is not defined");
		}

		if($entityTypeID !== CCrmOwnerType::Company && $entityTypeID !== CCrmOwnerType::Contact)
		{
			$entityTypeName = CCrmOwnerType::ResolveName($entityTypeID);
			throw new RestException("The entity type '{$entityTypeName}' is not supported in current context.");
		}

		$this->entityTypeID = $entityTypeID;
	}
	public function getEntityTypeID()
	{
		return $this->entityTypeID;
	}
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = array(
				'SORT' => array('TYPE' => 'integer'),
				'IS_PRIMARY' => array('TYPE' => 'char')
			);
			$entityFieldName = EntityBinding::resolveEntityFieldName($this->entityTypeID);
			if($entityFieldName !== '')
			{
				$this->FIELDS_INFO[$entityFieldName] = array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(\CCrmFieldInfoAttr::Required)
				);
			}
			else
			{
				$entityTypeName = CCrmOwnerType::ResolveName($this->entityTypeID);
				throw new RestException("The entity type '{$entityTypeName}' is not supported in current context.");
			}
		}
		return $this->FIELDS_INFO;
	}
	public function addItem($ownerEntityID, $fields)
	{
		$ownerEntityID = (int)$ownerEntityID;
		if($ownerEntityID <= 0)
		{
			throw new RestException("The parameter 'ownerEntityID' is invalid or not defined.");
		}

		if(!is_array($fields))
		{
			throw new RestException("The parameter 'fields' must be array.");
		}

		$fieldInfos = $this->getFieldsInfo();
		$this->internalizeFields($fields, $fieldInfos, array());

		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		if($this->ownerEntityTypeID === CCrmOwnerType::Deal
			&& $this->entityTypeID === CCrmOwnerType::Contact)
		{
			//DEAL -> CONTACT
			$categoryID = CCrmDeal::GetCategoryID($ownerEntityID);
			if($categoryID < 0)
			{
				throw new RestException(
					!CCrmDeal::CheckUpdatePermission(0, $userPermissions) ? 'Access denied.' : 'Not found.'
				);
			}
			elseif(!CCrmDeal::CheckUpdatePermission($ownerEntityID, $userPermissions, $categoryID))
			{
				throw new AccessException();
			}

			if(!CCrmDeal::Exists($ownerEntityID))
			{
				throw new RestException('Not found.');
			}

			if(!EntityBinding::verifyEntityBinding(CCrmOwnerType::Contact, $fields))
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$entityID = EntityBinding::resolveEntityID(CCrmOwnerType::Contact, $fields);
			if($entityID <= 0)
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$items = DealContactTable::getDealBindings($ownerEntityID);
			if(is_array(EntityBinding::findBindingByEntityID(CCrmOwnerType::Contact, $entityID, $items)))
			{
				return false;
			}

			$effectiveItems = array_merge($items, array($fields));
			if(EntityBinding::isPrimary($fields))
			{
				//Reassign primary entity. We want to ensure that we have only one primary entity in collection.
				EntityBinding::markAsPrimary($effectiveItems, CCrmOwnerType::Contact, $entityID);
			}

			$removedItems = array();
			$addedItems = array();

			EntityBinding::prepareBindingChanges(
				CCrmOwnerType::Contact,
				$items,
				$effectiveItems,
				$addedItems,
				$removedItems
			);

			if(!empty($addedItems))
			{
				DealContactTable::bindContacts($ownerEntityID, $addedItems);
			}

			return true;
		}
		elseif($this->ownerEntityTypeID === CCrmOwnerType::Quote
			&& $this->entityTypeID === CCrmOwnerType::Contact)
		{
			//QUOTE -> CONTACT
			if(!CCrmQuote::CheckUpdatePermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			if(!CCrmQuote::Exists($ownerEntityID))
			{
				throw new RestException('Not found.');
			}

			if(!EntityBinding::verifyEntityBinding(CCrmOwnerType::Contact, $fields))
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$entityID = EntityBinding::resolveEntityID(CCrmOwnerType::Contact, $fields);
			if($entityID <= 0)
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$items = QuoteContactTable::getQuoteBindings($ownerEntityID);
			if(is_array(EntityBinding::findBindingByEntityID(CCrmOwnerType::Contact, $entityID, $items)))
			{
				return false;
			}

			$effectiveItems = array_merge($items, array($fields));
			if(EntityBinding::isPrimary($fields))
			{
				//Reassign primary entity. We want to ensure that we have only one primary entity in collection.
				EntityBinding::markAsPrimary($effectiveItems, CCrmOwnerType::Contact, $entityID);
			}

			$removedItems = array();
			$addedItems = array();

			EntityBinding::prepareBindingChanges(
				CCrmOwnerType::Contact,
				$items,
				$effectiveItems,
				$addedItems,
				$removedItems
			);

			if(!empty($addedItems))
			{
				QuoteContactTable::bindContacts($ownerEntityID, $addedItems);
			}

			return true;
		}
		elseif($this->ownerEntityTypeID === CCrmOwnerType::Contact
			&& $this->entityTypeID === CCrmOwnerType::Company)
		{
			//CONTACT -> COMPANY
			if(!CCrmContact::CheckUpdatePermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			if(!CCrmContact::Exists($ownerEntityID))
			{
				throw new RestException('Not found.');
			}

			if(!EntityBinding::verifyEntityBinding(CCrmOwnerType::Company, $fields))
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$entityID = EntityBinding::resolveEntityID(CCrmOwnerType::Company, $fields);
			if($entityID <= 0)
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$items = ContactCompanyTable::getContactBindings($ownerEntityID);
			if(is_array(EntityBinding::findBindingByEntityID(CCrmOwnerType::Company, $entityID, $items)))
			{
				return false;
			}

			$effectiveItems = array_merge($items, array($fields));
			if(EntityBinding::isPrimary($fields))
			{
				//Reassign primary entity. We want to ensure that we have only one primary entity in collection.
				EntityBinding::markAsPrimary($effectiveItems, CCrmOwnerType::Company, $entityID);
			}

			$removedItems = array();
			$addedItems = array();

			EntityBinding::prepareBindingChanges(
				CCrmOwnerType::Company,
				$items,
				$effectiveItems,
				$addedItems,
				$removedItems
			);

			if(!empty($addedItems))
			{
				ContactCompanyTable::bindCompanies($ownerEntityID, $addedItems);
			}

			return true;
		}

		$ownerEntityTypeName = CCrmOwnerType::ResolveName($this->ownerEntityTypeID);
		$entityTypeName = CCrmOwnerType::ResolveName($this->entityTypeID);
		throw new RestException("The binding type '{$ownerEntityTypeName} - {$entityTypeName}' is not supported in current context.");
	}
	public function deleteItem($ownerEntityID, $fields)
	{
		$ownerEntityID = (int)$ownerEntityID;
		if($ownerEntityID <= 0)
		{
			throw new RestException("The parameter 'ownerEntityID' is invalid or not defined.");
		}

		if(!is_array($fields))
		{
			throw new RestException("The parameter 'item' must be array.");
		}

		$fieldInfos = $this->getFieldsInfo();
		$this->internalizeFields($fields, $fieldInfos, array());

		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		if($this->ownerEntityTypeID === CCrmOwnerType::Deal
			&& $this->entityTypeID === CCrmOwnerType::Contact)
		{
			//DEAL -> CONTACT
			$categoryID = CCrmDeal::GetCategoryID($ownerEntityID);
			if($categoryID < 0)
			{
				throw new RestException(
					!CCrmDeal::CheckUpdatePermission(0, $userPermissions) ? 'Access denied.' : 'Not found.'
				);
			}
			elseif(!CCrmDeal::CheckUpdatePermission($ownerEntityID, $userPermissions, $categoryID))
			{
				throw new AccessException();
			}

			if(!CCrmDeal::Exists($ownerEntityID))
			{
				throw new RestException('Not found.');
			}

			if(!EntityBinding::verifyEntityBinding(CCrmOwnerType::Contact, $fields))
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$entityID = EntityBinding::resolveEntityID(CCrmOwnerType::Contact, $fields);
			if($entityID <= 0)
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$items = DealContactTable::getDealBindings($ownerEntityID);
			$itemIndex = EntityBinding::findBindingIndexByEntityID(CCrmOwnerType::Contact, $entityID, $items);
			if($itemIndex < 0)
			{
				return false;
			}

			$item = $items[$itemIndex];
			$effectiveItems = $items;
			array_splice($effectiveItems, $itemIndex, 1);

			if(EntityBinding::isPrimary($item))
			{
				EntityBinding::markFirstAsPrimary($effectiveItems);
			}

			$removedItems = array();
			$addedItems = array();

			EntityBinding::prepareBindingChanges(
				CCrmOwnerType::Contact,
				$items,
				$effectiveItems,
				$addedItems,
				$removedItems
			);

			if(!empty($addedItems))
			{
				DealContactTable::bindContacts($ownerEntityID, $addedItems);
			}

			if(!empty($removedItems))
			{
				DealContactTable::unbindContacts($ownerEntityID, $removedItems);
			}

			return true;
		}
		elseif($this->ownerEntityTypeID === CCrmOwnerType::Quote
			&& $this->entityTypeID === CCrmOwnerType::Contact)
		{
			//QUOTE -> CONTACT
			if(!CCrmQuote::CheckUpdatePermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			if(!CCrmQuote::Exists($ownerEntityID))
			{
				throw new RestException('Not found.');
			}

			if(!EntityBinding::verifyEntityBinding(CCrmOwnerType::Contact, $fields))
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$entityID = EntityBinding::resolveEntityID(CCrmOwnerType::Contact, $fields);
			if($entityID <= 0)
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$items = QuoteContactTable::getQuoteBindings($ownerEntityID);
			$itemIndex = EntityBinding::findBindingIndexByEntityID(CCrmOwnerType::Contact, $entityID, $items);
			if($itemIndex < 0)
			{
				return false;
			}

			$item = $items[$itemIndex];
			$effectiveItems = $items;
			array_splice($effectiveItems, $itemIndex, 1);

			if(EntityBinding::isPrimary($item))
			{
				EntityBinding::markFirstAsPrimary($effectiveItems);
			}

			$removedItems = array();
			$addedItems = array();

			EntityBinding::prepareBindingChanges(
				CCrmOwnerType::Contact,
				$items,
				$effectiveItems,
				$addedItems,
				$removedItems
			);

			if(!empty($addedItems))
			{
				QuoteContactTable::bindContacts($ownerEntityID, $addedItems);
			}

			if(!empty($removedItems))
			{
				QuoteContactTable::unbindContacts($ownerEntityID, $removedItems);
			}

			return true;
		}
		elseif($this->ownerEntityTypeID === CCrmOwnerType::Contact
			&& $this->entityTypeID === CCrmOwnerType::Company)
		{
			//CONTACT -> COMPANY
			if(!CCrmContact::CheckUpdatePermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			if(!CCrmContact::Exists($ownerEntityID))
			{
				throw new RestException('Not found.');
			}

			if(!EntityBinding::verifyEntityBinding(CCrmOwnerType::Company, $fields))
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$entityID = EntityBinding::resolveEntityID(CCrmOwnerType::Company, $fields);
			if($entityID <= 0)
			{
				throw new RestException("The parameter 'fields' is not valid.");
			}

			$items = ContactCompanyTable::getContactBindings($ownerEntityID);
			$itemIndex = EntityBinding::findBindingIndexByEntityID(CCrmOwnerType::Company, $entityID, $items);
			if($itemIndex < 0)
			{
				return false;
			}

			$item = $items[$itemIndex];
			$effectiveItems = $items;
			array_splice($effectiveItems, $itemIndex, 1);

			if(EntityBinding::isPrimary($item))
			{
				EntityBinding::markFirstAsPrimary($effectiveItems);
			}

			$removedItems = array();
			$addedItems = array();

			EntityBinding::prepareBindingChanges(
				CCrmOwnerType::Company,
				$items,
				$effectiveItems,
				$addedItems,
				$removedItems
			);

			if(!empty($addedItems))
			{
				ContactCompanyTable::bindCompanies($ownerEntityID, $addedItems);
			}

			if(!empty($removedItems))
			{
				ContactCompanyTable::unbindCompanies($ownerEntityID, $removedItems);
			}

			return true;
		}

		$ownerEntityTypeName = CCrmOwnerType::ResolveName($this->ownerEntityTypeID);
		$entityTypeName = CCrmOwnerType::ResolveName($this->entityTypeID);
		throw new RestException("The binding type '{$ownerEntityTypeName} - {$entityTypeName}' is not supported in current context.");
	}
	public function getItems($ownerEntityID)
	{
		$ownerEntityID = (int)$ownerEntityID;
		if($ownerEntityID <= 0)
		{
			throw new RestException('The parameter ownerEntityID is invalid or not defined.');
		}

		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		if($this->ownerEntityTypeID === CCrmOwnerType::Deal
			&& $this->entityTypeID === CCrmOwnerType::Contact)
		{
			$categoryID = CCrmDeal::GetCategoryID($ownerEntityID);
			if($categoryID < 0)
			{
				throw new RestException(
					!CCrmDeal::CheckReadPermission(0, $userPermissions) ? 'Access denied' : 'Not found'
				);
			}
			elseif(!CCrmDeal::CheckReadPermission($ownerEntityID, $userPermissions, $categoryID))
			{
				throw new AccessException();
			}

			return DealContactTable::getDealBindings($ownerEntityID);
		}
		elseif($this->ownerEntityTypeID === CCrmOwnerType::Quote
			&& $this->entityTypeID === CCrmOwnerType::Contact)
		{
			if(!CCrmQuote::CheckReadPermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			return QuoteContactTable::getQuoteBindings($ownerEntityID);
		}
		elseif($this->ownerEntityTypeID === CCrmOwnerType::Contact
			&& $this->entityTypeID === CCrmOwnerType::Company)
		{
			if(!CCrmContact::CheckReadPermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			return ContactCompanyTable::getContactBindings($ownerEntityID);
		}

		$ownerEntityTypeName = CCrmOwnerType::ResolveName($this->ownerEntityTypeID);
		$entityTypeName = CCrmOwnerType::ResolveName($this->entityTypeID);
		throw new RestException("The binding type '{$ownerEntityTypeName} - {$entityTypeName}' is not supported in current context.");
	}
	public function setItems($ownerEntityID, $items)
	{
		$ownerEntityID = (int)$ownerEntityID;
		if($ownerEntityID <= 0)
		{
			throw new RestException('The parameter ownerEntityID is invalid or not defined.');
		}

		if(!is_array($items))
		{
			throw new RestException('The parameter items must be array.');
		}

		$effectiveItems = array();
		$fieldInfos = $this->getFieldsInfo();
		for($i = 0, $l = count($items); $i < $l; $i++)
		{
			$item = $items[$i];
			$this->internalizeFields($item, $fieldInfos, array());
			$effectiveItems[] = $item;
		}

		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		if($this->ownerEntityTypeID === CCrmOwnerType::Deal
			&& $this->entityTypeID === CCrmOwnerType::Contact)
		{
			//DEAL -> CONTACT
			$categoryID = CCrmDeal::GetCategoryID($ownerEntityID);
			if($categoryID < 0)
			{
				throw new RestException(
					!CCrmDeal::CheckUpdatePermission(0, $userPermissions) ? 'Access denied.' : 'Not found.'
				);
			}
			elseif(!CCrmDeal::CheckUpdatePermission($ownerEntityID, $userPermissions, $categoryID))
			{
				throw new AccessException();
			}

			if(!CCrmDeal::Exists($ownerEntityID))
			{
				throw new RestException('Not found.');
			}

			try
			{
				EntityBinding::normalizeEntityBindings(CCrmOwnerType::Contact, $effectiveItems);
			}
			catch(Main\SystemException $ex)
			{
				throw new RestException(
					$ex->getMessage(),
					RestException::ERROR_CORE,
					CRestServer::STATUS_INTERNAL,
					$ex
				);
			}

			$removedItems = array();
			$addedItems = array();

			EntityBinding::prepareBindingChanges(
				CCrmOwnerType::Contact,
				DealContactTable::getDealBindings($ownerEntityID),
				$effectiveItems,
				$addedItems,
				$removedItems
			);

			if(!empty($removedItems))
			{
				DealContactTable::unbindContacts($ownerEntityID, $removedItems);
			}

			if(!empty($addedItems))
			{
				DealContactTable::bindContacts($ownerEntityID, $addedItems);
			}
			return true;
		}
		elseif($this->ownerEntityTypeID === CCrmOwnerType::Quote
			&& $this->entityTypeID === CCrmOwnerType::Contact)
		{
			//QUOTE -> CONTACT
			if(!CCrmQuote::CheckUpdatePermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			if(!CCrmQuote::Exists($ownerEntityID))
			{
				throw new RestException('Not found.');
			}

			try
			{
				EntityBinding::normalizeEntityBindings(CCrmOwnerType::Contact, $effectiveItems);
			}
			catch(Main\SystemException $ex)
			{
				throw new RestException(
					$ex->getMessage(),
					RestException::ERROR_CORE,
					CRestServer::STATUS_INTERNAL,
					$ex
				);
			}

			$removedItems = array();
			$addedItems = array();

			EntityBinding::prepareBindingChanges(
				CCrmOwnerType::Contact,
				QuoteContactTable::getQuoteBindings($ownerEntityID),
				$effectiveItems,
				$addedItems,
				$removedItems
			);

			if(!empty($removedItems))
			{
				QuoteContactTable::unbindContacts($ownerEntityID, $removedItems);
			}

			if(!empty($addedItems))
			{
				QuoteContactTable::bindContacts($ownerEntityID, $addedItems);
			}
			return true;
		}
		elseif($this->ownerEntityTypeID === CCrmOwnerType::Contact
			&& $this->entityTypeID === CCrmOwnerType::Company)
		{
			//CONTACT -> COMPANY
			if(!CCrmContact::CheckUpdatePermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			if(!CCrmContact::Exists($ownerEntityID))
			{
				throw new RestException('Not found.');
			}

			try
			{
				EntityBinding::normalizeEntityBindings(CCrmOwnerType::Company, $effectiveItems);
			}
			catch(Main\SystemException $ex)
			{
				throw new RestException(
					$ex->getMessage(),
					RestException::ERROR_CORE,
					CRestServer::STATUS_INTERNAL,
					$ex
				);
			}

			$removedItems = array();
			$addedItems = array();

			EntityBinding::prepareBindingChanges(
				CCrmOwnerType::Company,
				ContactCompanyTable::getContactBindings($ownerEntityID),
				$effectiveItems,
				$addedItems,
				$removedItems
			);

			if(!empty($removedItems))
			{
				ContactCompanyTable::unbindCompanies($ownerEntityID, $removedItems);
			}

			if(!empty($addedItems))
			{
				ContactCompanyTable::bindCompanies($ownerEntityID, $addedItems);
			}
			return true;
		}

		$ownerEntityTypeName = CCrmOwnerType::ResolveName($this->ownerEntityTypeID);
		$entityTypeName = CCrmOwnerType::ResolveName($this->entityTypeID);
		throw new RestException("The binding type '{$ownerEntityTypeName} - {$entityTypeName}' is not supported in current context.");
	}
	public function deleteItems($ownerEntityID)
	{
		$ownerEntityID = (int)$ownerEntityID;
		if($ownerEntityID <= 0)
		{
			throw new RestException('The parameter ownerEntityID is invalid or not defined.');
		}

		$userPermissions = CCrmPerms::GetCurrentUserPermissions();
		if($this->ownerEntityTypeID === CCrmOwnerType::Deal
			&& $this->entityTypeID === CCrmOwnerType::Contact)
		{
			$categoryID = CCrmDeal::GetCategoryID($ownerEntityID);
			if($categoryID < 0)
			{
				throw new RestException(
					!CCrmDeal::CheckReadPermission(0, $userPermissions) ? 'Access denied' : 'Not found'
				);
			}
			elseif(!CCrmDeal::CheckReadPermission($ownerEntityID, $userPermissions, $categoryID))
			{
				throw new AccessException();
			}

			DealContactTable::unbindAllContacts($ownerEntityID);
			return true;
		}
		elseif($this->ownerEntityTypeID === CCrmOwnerType::Quote
			&& $this->entityTypeID === CCrmOwnerType::Contact)
		{
			if(!CCrmQuote::CheckReadPermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			QuoteContactTable::unbindAllContacts($ownerEntityID);
			return true;
		}
		elseif($this->ownerEntityTypeID === CCrmOwnerType::Contact
			&& $this->entityTypeID === CCrmOwnerType::Company)
		{
			if(!CCrmContact::CheckReadPermission($ownerEntityID, $userPermissions))
			{
				throw new AccessException();
			}

			ContactCompanyTable::unbindAllCompanies($ownerEntityID);
			return true;
		}

		$ownerEntityTypeName = CCrmOwnerType::ResolveName($this->ownerEntityTypeID);
		$entityTypeName = CCrmOwnerType::ResolveName($this->entityTypeID);
		throw new RestException("The binding type '{$ownerEntityTypeName} - {$entityTypeName}' is not supported in current context.");
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'FIELDS')
		{
			return $this->getFields();
		}
		elseif($name === 'ADD')
		{
			return $this->addItem(
				CCrmRestHelper::resolveEntityID($arParams),
				CCrmRestHelper::resolveArrayParam($arParams, 'fields')
			);
		}
		elseif($name === 'DELETE')
		{
			return $this->deleteItem(
				CCrmRestHelper::resolveEntityID($arParams),
				CCrmRestHelper::resolveArrayParam($arParams, 'fields')
			);
		}
		elseif($name === 'ITEMS')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if($nameSuffix === 'GET')
			{
				return $this->getItems(CCrmRestHelper::resolveEntityID($arParams));
			}
			elseif($nameSuffix === 'SET')
			{
				return $this->setItems(
					CCrmRestHelper::resolveEntityID($arParams),
					CCrmRestHelper::resolveArrayParam($arParams, 'items')
				);
			}
			elseif($nameSuffix === 'DELETE')
			{
				return $this->deleteItems(CCrmRestHelper::resolveEntityID($arParams));
			}
		}
		throw new RestException('Method not found!', RestException::ERROR_METHOD_NOT_FOUND, CRestServer::STATUS_NOT_FOUND);
	}
}

class CCrmUserFieldRestProxy extends UserFieldProxy implements ICrmRestProxy
{
	private $ownerTypeID = CCrmOwnerType::Undefined;
	/** @var CRestServer  */
	private $server = null;

	function __construct($ownerTypeID, \CUser $user = null)
	{
		$this->ownerTypeID = CCrmOwnerType::IsDefined($ownerTypeID) ? $ownerTypeID : CCrmOwnerType::Undefined;
		parent::__construct(CCrmOwnerType::ResolveUserFieldEntityID($this->ownerTypeID), $user);
		$this->setNamePrefix('crm');
	}
	public function getOwnerTypeID()
	{
		return $this->ownerTypeID;
	}
	public function getServer()
	{
		return $this->server;
	}
	public function setServer(CRestServer $server)
	{
		$this->server = $server;
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'FIELDS')
		{
			return self::getFields();
		}
		elseif($name === 'TYPES' && method_exists('\Bitrix\Rest\UserFieldProxy', 'getTypes'))
		{
			return self::getTypes();
		}
		elseif($name === 'SETTINGS')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if($nameSuffix === 'FIELDS')
			{
				$type = CCrmRestHelper::resolveParam($arParams, 'type', '');
				if($type === '')
				{
					throw new RestException("Parameter 'type' is not specified or empty.");
				}

				return self::getSettingsFields($type);
			}
		}
		elseif($name === 'ENUMERATION')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if($nameSuffix === 'FIELDS')
			{
				return self::getEnumerationElementFields();
			}
		}
		throw new RestException("Resource '{$name}' is not supported in current context.");
	}
	protected function isAuthorizedUser()
	{
		if($this->isAuthorizedUser === null)
		{
			/**@var \CCrmPerms $userPermissions @**/
			$userPermissions = CCrmPerms::GetUserPermissions($this->user->GetID());
			$this->isAuthorizedUser = $userPermissions->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'WRITE');
		}
		return $this->isAuthorizedUser;
	}
	protected function checkCreatePermission()
	{
		return $this->isAuthorizedUser();
	}
	protected function checkReadPermission()
	{
		return $this->isAuthorizedUser();
	}
	protected function checkUpdatePermission()
	{
		return $this->isAuthorizedUser();
	}
	protected function checkDeletePermission()
	{
		return $this->isAuthorizedUser();
	}

	public function get($ID)
	{
		$ufId = (int)$ID;
		if ($ufId > 0 && $this->entityID === CCrmInvoice::GetUserFieldEntityID() && parent::checkReadPermission())
		{
			$invoiceReservedFields = array_fill_keys(CCrmInvoice::GetUserFieldsReserved(), true);

			$entity = new \CUserTypeEntity();
			$result = $entity->GetByID($ID);
			if (is_array($result) && isset($result['FIELD_NAME'])
				&& isset($invoiceReservedFields[$result['FIELD_NAME']]))
			{
				throw new RestException("The entity with ID '{$ID}' is not found.", RestException::ERROR_NOT_FOUND);
			}
		}

		return parent::get($ID);
	}
	public function getList(array $order, array $filter)
	{
		$result = array();
		$tmpResult = parent::getList($order, $filter);

		if ($this->entityID === CCrmInvoice::GetUserFieldEntityID() && is_array($tmpResult) && !empty($tmpResult))
		{
			$invoiceReservedFields = array_fill_keys(CCrmInvoice::GetUserFieldsReserved(), true);

			foreach ($tmpResult as $index => $fieldInfo)
			{
				if ($index !== 'total'
					&& isset($fieldInfo['FIELD_NAME'])
					&& !isset($invoiceReservedFields[$fieldInfo['FIELD_NAME']]))
				{
					$result[] = $fieldInfo;
				}
			}

			$result['total'] = count($result);
		}
		else
		{
			$result = $tmpResult;
		}

		return $result;
	}
	public function update($ID, array $fields)
	{
		$ufId = (int)$ID;
		if ($ufId > 0 && $this->entityID === CCrmInvoice::GetUserFieldEntityID() && parent::checkUpdatePermission())
		{
			$invoiceReservedFields = array_fill_keys(CCrmInvoice::GetUserFieldsReserved(), true);

			$entity = new \CUserTypeEntity();
			$result = $entity->GetByID($ID);
			if (is_array($result) && isset($result['FIELD_NAME'])
				&& isset($invoiceReservedFields[$result['FIELD_NAME']]))
			{
				throw new RestException("The entity with ID '{$ID}' is not found.", RestException::ERROR_NOT_FOUND);
			}
		}

		return parent::update($ID, $fields);
	}
	public function delete($ID)
	{
		$ufId = (int)$ID;
		if ($ufId > 0 && $this->entityID === CCrmInvoice::GetUserFieldEntityID() && parent::checkDeletePermission())
		{
			$invoiceReservedFields = array_fill_keys(CCrmInvoice::GetUserFieldsReserved(), true);

			$entity = new \CUserTypeEntity();
			$result = $entity->GetByID($ID);
			if (is_array($result) && isset($result['FIELD_NAME'])
				&& isset($invoiceReservedFields[$result['FIELD_NAME']]))
			{
				throw new RestException("The entity with ID '{$ID}' is not found.", RestException::ERROR_NOT_FOUND);
			}
		}

		return parent::delete($ID);
	}
}

class CCrmQuoteRestProxy extends CCrmRestProxyBase
{
	private static $ENTITY = null;
	private $FIELDS_INFO = null;
	public  function getOwnerTypeID()
	{
		return CCrmOwnerType::Quote;
	}
	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new CCrmQuote(true);
		}

		return self::$ENTITY;
	}
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = CCrmQuote::GetFieldsInfo();
			self::prepareUserFieldsInfo($this->FIELDS_INFO, CCrmQuote::$sUFEntityID);
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		if(!CCrmQuote::CheckCreatePermission())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		if(isset($fields['CONTENT']))
		{
			$fields['CONTENT'] = $this->sanitizeHtml($fields['CONTENT']);
		}

		if(isset($fields['TERMS']))
		{
			$fields['TERMS'] = $this->sanitizeHtml($fields['TERMS']);
		}

		$entity = self::getEntity();
		$options = array();
		if(!$this->isRequiredUserFieldCheckEnabled())
		{
			$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
		}
		$result = $entity->Add($fields, true, $options);
		if($result <= 0)
		{
			$errors[] = $entity->LAST_ERROR;
		}

		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!CCrmQuote::CheckReadPermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$dbRes = CCrmQuote::GetList(
			array(),
			array('=ID' => $ID),
			false,
			false,
			array(),
			array()
		);

		$result = $dbRes ? $dbRes->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'Not found';
			return false;
		}

		$userFields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields(CCrmQuote::$sUFEntityID, $ID, LANGUAGE_ID);
		foreach($userFields as $ufName => &$ufData)
		{
			$result[$ufName] = isset($ufData['VALUE']) ? $ufData['VALUE'] : '';
		}
		unset($ufData);

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!CCrmQuote::CheckReadPermission(0))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$options = array('IS_EXTERNAL_CONTEXT' => true);
		if(is_array($order))
		{
			if(isset($order['STATUS_ID']))
			{
				$order['STATUS_SORT'] = $order['STATUS_ID'];
				unset($order['STATUS_ID']);

				$options['FIELD_OPTIONS'] = array('ADDITIONAL_FIELDS' => array('STATUS_SORT'));
			}
		}

		return CCrmQuote::GetList($order, $filter, false, $navigation, $select, $options);
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		if(!CCrmQuote::CheckUpdatePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if(!CCrmQuote::Exists($ID))
		{
			$errors[] = 'Quote is not found';
			return false;
		}

		if(isset($fields['COMMENTS']))
		{
			$fields['COMMENTS'] = $this->sanitizeHtml($fields['COMMENTS']);
		}

		if(isset($fields['CONTENT']))
		{
			$fields['CONTENT'] = $this->sanitizeHtml($fields['CONTENT']);
		}

		if(isset($fields['TERMS']))
		{
			$fields['TERMS'] = $this->sanitizeHtml($fields['TERMS']);
		}

		$entity = self::getEntity();
		$compare = true;
		$options = array();
		if(!$this->isRequiredUserFieldCheckEnabled())
		{
			$options['DISABLE_REQUIRED_USER_FIELD_CHECK'] = true;
		}
		if(is_array($params))
		{
			if(isset($params['REGISTER_HISTORY_EVENT']))
			{
				$compare = strtoupper($params['REGISTER_HISTORY_EVENT']) === 'Y';
			}
		}

		$result = $entity->Update($ID, $fields, $compare, true, $options);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}
		return $result;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		if(!CCrmQuote::CheckDeletePermission($ID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();
		$result = $entity->Delete($ID);
		if($result !== true)
		{
			$errors[] = $entity->LAST_ERROR;
		}

		return $result;
	}
	public function getProductRows($ID)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!CCrmQuote::CheckReadPermission($ID))
		{
			throw new RestException('Access denied.');
		}

		return CCrmQuote::LoadProductRows($ID);
	}
	public function setProductRows($ID, $rows)
	{
		$ID = intval($ID);
		if($ID <= 0)
		{
			throw new RestException('The parameter id is invalid or not defined.');
		}

		if(!is_array($rows))
		{
			throw new RestException('The parameter rows must be array.');
		}

		if(!CCrmQuote::CheckUpdatePermission($ID))
		{
			throw new RestException('Access denied.');
		}

		if(!CCrmQuote::Exists($ID))
		{
			throw new RestException('Not found.');
		}

		$proxy = new CCrmProductRowRestProxy();

		$actualRows = array();
		$qty = count($rows);
		for($i = 0; $i < $qty; $i++)
		{
			$row = $rows[$i];
			if(!is_array($row))
			{
				continue;
			}

			$proxy->prepareForSave($row);
			if(isset($row['OWNER_TYPE']))
			{
				unset($row['OWNER_TYPE']);
			}

			if(isset($row['OWNER_ID']))
			{
				unset($row['OWNER_ID']);
			}

			$actualRows[] = $row;
		}

		return CCrmQuote::SaveProductRows($ID, $actualRows, true, true, true);
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'PRODUCTROWS')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');

			if($nameSuffix === 'GET')
			{
				return $this->getProductRows($this->resolveEntityID($arParams));
			}
			elseif($nameSuffix === 'SET')
			{
				$ID = $this->resolveEntityID($arParams);
				$rows = $this->resolveArrayParam($arParams, 'rows');
				return $this->setProductRows($ID, $rows);
			}
		}
		elseif($name === 'CONTACT')
		{
			$bindRequestDetails = $nameDetails;
			$bindRequestName = array_shift($bindRequestDetails);
			$bindingProxy = new CCrmEntityBindingProxy(CCrmOwnerType::Quote, CCrmOwnerType::Contact);
			return $bindingProxy->processMethodRequest($bindRequestName, $bindRequestDetails, $arParams, $nav, $server);
		}
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
	protected function getSupportedMultiFieldTypeIDs()
	{
		return self::getMultiFieldTypeIDs();
	}
	protected function getIdentityFieldName()
	{
		return 'ID';
	}
	protected function getIdentity(&$fields)
	{
		return isset($fields['ID']) ? intval($fields['ID']) : 0;
	}
	public static function registerEventBindings(array &$bindings)
	{
		if(!isset($bindings[CRestUtil::EVENTS]))
		{
			$bindings[CRestUtil::EVENTS] = array();
		}

		$callback = array('CCrmQuoteRestProxy', 'processEvent');

		$bindings[CRestUtil::EVENTS]['onCrmQuoteAdd'] = self::createEventInfo('crm', 'OnAfterCrmQuoteAdd', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmQuoteUpdate'] = self::createEventInfo('crm', 'OnAfterCrmQuoteUpdate', $callback);
		$bindings[CRestUtil::EVENTS]['onCrmQuoteDelete'] = self::createEventInfo('crm', 'OnAfterCrmQuoteDelete', $callback);
	}
	public static function processEvent(array $arParams, array $arHandler)
	{
		return parent::processEntityEvent(CCrmOwnerType::Quote, $arParams, $arHandler);
	}
}

class CCrmInvoiceRestProxy extends CCrmRestProxyBase
{
	public  function getOwnerTypeID()
	{
		return CCrmOwnerType::Invoice;
	}
	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
	}
}

class CCrmRequisitePresetRestProxy extends CCrmRestProxyBase
{
	private static $ENTITY = null;
	private $FIELDS_INFO = null;

	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new EntityPreset();
		}

		return self::$ENTITY;
	}

	protected function getCountriesInfo()
	{
		$result = array();

		$countriesInfo = EntityPreset::getCountriesInfo();

		foreach (EntityRequisite::getAllowedRqFieldCountries() as $countryId)
		{
			$countryInfo = is_array($countriesInfo[$countryId]) ? $countriesInfo[$countryId] : array();
			$result[] = array(
					'ID' => $countryId,
					'CODE' => isset($countryInfo['CODE']) ? $countryInfo['CODE'] : '',
					'TITLE' => isset($countryInfo['TITLE']) ? $countryInfo['TITLE'] : ''
			);
		}

		return $result;
	}
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = EntityPreset::getFieldsInfo();
		}

		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		$entityTypeID = intval($this->resolveParam($fields, 'ENTITY_TYPE_ID'));

		if(!$this->isValidID($entityTypeID) || $entityTypeID !== EntityPreset::Requisite)
		{
			$errors[] = 'ENTITY_TYPE_ID is not defined or invalid.';
			return false;
		}

		if(!EntityPreset::checkCreatePermissionOwnerEntity($entityTypeID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if (!$this->checkFields($fields, $sError))
		{
			$errors[] = $sError;
			return false;
		}

		$entity = self::getEntity();
		$result = $entity->add($fields);

		if (is_object($result))
		{
			if($result->isSuccess())
			{
				$result = $result->getId();
			}
			else
			{
				$errors = $result->getErrors();
				$result = false;
			}
		}
		else
		{
			$errors[] = 'Error when adding preset.';
			$result = false;
		}

		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if(!EntityPreset::checkReadPermissionOwnerEntity())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$r = $this->getById($ID);
		if(!is_array($r))
		{
			$errors[] = "The Preset with ID '{$ID}' is not found";
			return false;
		}

		return $r;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!EntityPreset::checkReadPermissionOwnerEntity())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();

		$filter['=ENTITY_TYPE_ID'] = EntityPreset::Requisite;

		$page = isset($navigation['iNumPage']) ? (int)$navigation['iNumPage'] : 1;
		$limit = isset($navigation['nPageSize']) ? (int)$navigation['nPageSize'] : CCrmRestService::LIST_LIMIT;
		$offset = $limit * $page;

		if(empty($select))
			$select = array_keys($this->getFieldsInfo());

		$result = $entity->getList(
			array(
				'order' => $order,
				'filter' => $filter,
				'select' => $select,
				'offset' => $offset,
				'count_total' => true
			)
		);

		if (is_object($result))
		{
			$dbResult = new CDBResult($result);
		}
		else
		{
			$dbResult = new CDBResult();
			$dbResult->InitFromArray(array());
		}
		$dbResult->NavStart($limit, false, $page);

		return $dbResult;
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		$r = $this->getById($ID);
		if(!is_array($r))
		{
			$errors[] = "The Preset with ID '{$ID}' is not found";
			return false;
		}

		$entityTypeID = intval($r['ENTITY_TYPE_ID']);

		if(!$this->isValidID($entityTypeID) || $entityTypeID !== EntityPreset::Requisite)
		{
			$errors[] = "ENTITY_TYPE_ID is not defined or invalid.";
			return false;
		}

		if(!EntityPreset::checkUpdatePermissionOwnerEntity($entityTypeID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();
		$result = $entity->update($ID, $fields);

		if($result->isSuccess())
		{
			return true;
		}
		else
		{
			$errors = $result->getErrors();
			return false;
		}

		$errors[] = 'Error when updating preset.';
		return false;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		$r = $this->getById($ID);
		if(!is_array($r))
		{
			$errors[] = "The Preset with ID '{$ID}' is not found";
			return false;
		}

		$entityTypeID = intval($r['ENTITY_TYPE_ID']);

		if(!$this->isValidID($entityTypeID) || $entityTypeID !== EntityPreset::Requisite)
		{
			$errors[] = 'ENTITY_TYPE_ID is not defined or invalid.';
			return false;
		}

		if(!EntityPreset::checkDeletePermissionOwnerEntity($entityTypeID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();

		$result = $entity->delete($ID);

		if (is_object($result))
		{
			if($result->isSuccess())
			{
				$result = true;
			}
			else
			{
				$errors = $result->getErrors();
				$result = false;
			}
		}
		else
		{
			$errors[] = 'Error when deleting preset.';
			$result = false;
		}

		return $result;
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'PRESET')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if ($nameSuffix === 'COUNTRIES')
			{
				return $this->getCountriesInfo();
			}
			else if (in_array($nameSuffix, array('FIELDS', 'ADD', 'GET', 'LIST', 'UPDATE', 'DELETE'), true))
			{
				return parent::processMethodRequest($nameSuffix, '', $arParams, $nav, $server);
			}
		}

		throw new RestException("Resource '{$name}' is not supported in current context.");
	}

	protected function checkFields($fields, &$errors)
	{
		if (isset($fields['COUNTRY_ID']))
		{
			$countryId = intval($fields['COUNTRY_ID']);
			if (!in_array($countryId, EntityRequisite::getAllowedRqFieldCountries(), true))
			{
				$errors = 'Invalid value of field: COUNTRY_ID.';
				return false;
			}
		}

		return true;
	}
	protected function getById($ID)
	{
		$entity = self::getEntity();

		$result = $entity->getList(array('filter'=>array('ID' => $ID)));
		return $result->fetch();
	}
}

class CCrmRequisitePresetFieldRestProxy extends CCrmRestProxyBase
{
	private static $ENTITY = null;
	private static $ENTITY_OWNER = null;
	private $FIELDS_INFO = null;

	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new EntityPreset();
		}

		return self::$ENTITY;
	}
	private static function getOwnerEntity()
	{
		if(!self::$ENTITY_OWNER)
		{
			self::$ENTITY_OWNER = new EntityRequisite();
		}

		return self::$ENTITY_OWNER;
	}

	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = EntityPreset::getSettingsFieldsRestInfo();
		}

		return $this->FIELDS_INFO;
	}
	protected function innerAddField($presetId, &$fields, &$errors)
	{
		$r = $this->exists($presetId, EntityPreset::Requisite);
		if(!is_array($r))
		{
			$errors[] = "The Preset with ID '{$presetId}' is not found";
			return false;
		}

		if(!EntityPreset::checkCreatePermissionOwnerEntity($r['ENTITY_TYPE_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$preset = self::getEntity();
		$requisite = self::getOwnerEntity();

		if (!$this->checkFields('ADD', $presetId, $fields, $sError))
		{
			$errors[] = $sError;
			return false;
		}

		$presetData = $preset->getById($presetId);
		$presetCountryId = isset($presetData['COUNTRY_ID']) ? (int)$presetData['COUNTRY_ID'] : 0;

		$fieldsTitles = $requisite->getFieldsTitles($presetCountryId);

		$addFields = $fields;
		if (isset($addFields['FIELD_TITLE']) && isset($addFields['FIELD_NAME']))
		{
			if (isset($fieldsTitles[$addFields['FIELD_NAME']]))
			{
				$title = $fieldsTitles[$addFields['FIELD_NAME']];
				$origFieldTitle = empty($title) ? $addFields['FIELD_NAME'] : $title;
				if ($addFields['FIELD_TITLE'] === $origFieldTitle)
					$addFields['FIELD_TITLE'] = '';
			}
			unset($title);
		}
		if (!is_array($presetData['SETTINGS']))
			$presetData['SETTINGS'] = array();

		$id = $preset->settingsAddField($presetData['SETTINGS'], $addFields);
		if ($id > 0)
		{
			$result = $preset->update($presetId, array('SETTINGS' => $presetData['SETTINGS']));

			if (is_object($result))
			{
				if($result->isSuccess())
				{
					return $id;
				}
				else
				{
					$errors = $result->getErrors();
					return false;
				}
			}
			else
			{
				$errors[] = 'Added preset field. Error when updated Requisite';
				return false;
			}
		}
		else
		{
			$errors[] = 'Error when adding preset field.';
			return false;
		}
	}
	protected function innerGetFields($presetId, $id, &$errors)
	{
		$r = $this->exists($presetId, EntityPreset::Requisite);
		if(!is_array($r))
		{
			$errors[] = "The Preset with ID '{$presetId}' is not found";
			return false;
		}
		if(!is_array($r['SETTINGS']))
		{
			$r['SETTINGS'] = array();
		}

		$preset = self::getEntity();
		$result = $this->getByFieldId($id, $preset->settingsGetFields($r['SETTINGS']), array(
				'COUNTRY_ID' => $r['COUNTRY_ID']
		));
		if(empty($result))
		{
			$errors[] = "The PresetField with ID '{$id}' is not found";
			return false;
		}

		if(!EntityPreset::checkReadPermissionOwnerEntity())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return $result;
	}
	protected function innerGetListFields($presetId, &$errors)
	{
		$r = $this->exists($presetId, EntityPreset::Requisite);
		if(!is_array($r))
		{
			$errors[] = "The Preset with ID '{$presetId}' is not found";
			return false;
		}
		if(!is_array($r['SETTINGS']))
		{
			$r['SETTINGS'] = array();
		}

		if(!EntityPreset::checkReadPermissionOwnerEntity())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();
		return $entity->settingsGetFields($r['SETTINGS']);
	}
	protected function innerUpdateFields($presetId, $id, $fields, &$errors)
	{
		$r = $this->exists($presetId, EntityPreset::Requisite);
		if(!is_array($r))
		{
			$errors[] = "The Preset with ID '{$presetId}' is not found";
			return false;
		}

		$preset = self::getEntity();

		$presetField = $this->getByFieldId($id, $preset->settingsGetFields($r['SETTINGS']), array(
				'COUNTRY_ID' => $r['COUNTRY_ID']
		));
		if(empty($presetField))
		{
			$errors[] = "The PresetField with ID '{$id}' is not found";
			return false;
		}

		if(!EntityPreset::checkUpdatePermissionOwnerEntity($r['ENTITY_TYPE_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$fields['ID'] = $id;
		if (!$this->checkFields('UPD', $presetId, $fields, $sError))
		{
			$errors[] = $sError;
			return false;
		}

		$presetSettings = $r['SETTINGS'];
		if($preset->settingsUpdateField($presetSettings, $fields))
		{
			$result = $preset->update($presetId, array('SETTINGS' => $presetSettings));
			if (is_object($result))
			{
				if($result->isSuccess())
				{
					return $id;
				}
				else
				{
					$errors = $result->getErrors();
					return false;
				}
			}
			else
			{
				$errors[] = 'Update preset field. Error when updated Requisite';
				return false;
			}
		}
		else
		{
			$errors[] = 'Error when update preset field.';
			return false;
		}
	}
	protected function innerDeleteField($presetId, $id, &$errors)
	{
		$r = $this->exists($presetId, EntityPreset::Requisite);
		if(!is_array($r))
		{
			$errors[] = "The Preset with ID '{$presetId}' is not found";
			return false;
		}

		$preset = self::getEntity();

		$presetField = $this->getByFieldId($id, $preset->settingsGetFields($r['SETTINGS']), array(
				'COUNTRY_ID' => $r['COUNTRY_ID']
		));
		if(empty($presetField))
		{
			$errors[] = "The PresetField with ID '{$id}' is not found";
			return false;
		}

		if(!EntityPreset::checkDeletePermissionOwnerEntity($r['ENTITY_TYPE_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$presetSettings = $r['SETTINGS'];
		if($preset->settingsDeleteField($presetSettings, $id))
		{
			$result = $preset->update($presetId, array('SETTINGS' => $presetSettings));

			if (is_object($result))
			{
				if($result->isSuccess())
				{
					return true;
				}
				else
				{
					$errors = $result->getErrors();
					return false;
				}
			}
			else
			{
				$errors[] = 'Deleted preset field. Error when updated Requisite';
				return false;
			}
		}

		$errors[] = 'Error when deleted preset field.';
		return false;
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$errors = array();
		$name = strtoupper($name);
		if($name === 'PRESET' &&
				is_array($nameDetails) &&
				count($nameDetails) === 2 &&
				strtoupper($nameDetails[0]) === 'FIELD'
		)
		{
			$nameSuffix = strtoupper($nameDetails[1]);

			$presetId = 0;
			if($nameSuffix !== 'FIELDS')
			{
				$preset = $this->resolveArrayParam($arParams, 'preset');
				if(!is_array($preset))
				{
					throw new RestException("Parameter 'PRESET' is not specified or incorrect.");
				}

				$presetId = intval($this->resolveParam($preset, 'ID'));
				if(!$this->checkEntityID($presetId))
				{
					throw new RestException('PRESET[ID] is not defined or invalid.');
				}
			}

			if($nameSuffix === 'AVAILABLETOADD')
			{
				$preset = new EntityPreset();
				$result = $preset->getSettingsFieldsAvailableToAdd(EntityPreset::Requisite, $presetId);
				if (is_object($result))
				{
					if($result->isSuccess())
					{
						$result = $result->getData();
					}
					else
					{
						throw new RestException(implode("\n", $result->getErrors()));
					}
				}
				else
				{
					throw new RestException('Error when getting fields.');
				}

				return $result;
			}
			elseif($nameSuffix === 'FIELDS')
			{
				return parent::processMethodRequest($nameSuffix, '', $arParams, $nav, $server);
			}
			elseif($nameSuffix === 'ADD')
			{
				$fields = $this->resolveArrayParam($arParams, 'fields');
				$this->internalizeFields($fields, $this->getFieldsInfo(), array());

				$errors = array();
				$result = $this->innerAddField($presetId, $fields, $errors);

				if($this->isValidID($result))
				{
					return $result;
				}

				if(empty($errors))
				{
					$errors[] = "Failed to add. General error.";
				}

				throw new RestException(implode("\n", $errors));

			}
			elseif($nameSuffix === 'GET')
			{
				$id = intval($this->resolveParam($arParams, 'ID'));

				if(!$this->isValidID($id))
				{
					throw new RestException('ID is not defined or invalid.');
				}

				$errors = array();
				$result = $this->innerGetFields($presetId, $id, $errors);

				if(is_array($result))
				{
					$this->externalizeFields($result, $this->getFieldsInfo());
					return $result;
				}

				if(empty($errors))
				{
					$errors[] = "Failed to get. General error.";
				}

				throw new RestException(implode("\n", $errors));
			}
			elseif($nameSuffix === 'LIST')
			{
				$errors = array();
				$result = $this->innerGetListFields($presetId, $errors);

				if(is_array($result))
				{
					return $result;
				}

				if(empty($errors))
				{
					$errors[] = "Failed to get list. General error.";
				}

				throw new RestException(implode("\n", $errors));
			}
			elseif($nameSuffix === 'UPDATE')
			{
				$fields = $this->resolveArrayParam($arParams, 'fields');
				$id = intval($this->resolveParam($arParams, 'ID'));

				if(!$this->checkEntityID($id))
				{
					throw new RestException('ID is not defined or invalid.');
				}

				$this->internalizeFields($fields, $this->getFieldsInfo(), array());

				$errors = array();
				$result = $this->innerUpdateFields($presetId, $id, $fields, $errors);
				if($this->isValidID($result))
				{
					return true;
				}

				if(empty($errors))
				{
					$errors[] = "Failed to update. General error.";
				}

				throw new RestException(implode("\n", $errors));
			}
			elseif($nameSuffix === 'DELETE')
			{
				$id = intval($this->resolveParam($arParams, 'ID'));

				if(!$this->isValidID($id))
				{
					throw new RestException('ID is not defined or invalid.');
				}

				$result = $this->innerDeleteField($presetId, $id, $errors);

				if($result)
				{
					return true;
				}

				if(empty($errors))
				{
					$errors[] = "Failed to delete. General error.";
				}

				throw new RestException(implode("\n", $errors));
			}
		}

		throw new RestException("Resource '{$name}' is not supported in current context.");
	}

	protected function checkFields($action, $presetId, $fields, &$errors)
	{
		if (!isset($fields['FIELD_NAME']))
		{
			$errors = 'FIELD_NAME is not specified.';
			return false;
		}
		elseif($action === 'ADD')
		{
			$entity = self::getEntity();

			$fieldsAvailableToAdd = array();
			$result = $entity->getSettingsFieldsAvailableToAdd(EntityPreset::Requisite, $presetId);
			if ($result->isSuccess())
			{
				$fieldsAvailableToAdd = $result->getData();
				if (!is_array($fieldsAvailableToAdd))
					$fieldsAvailableToAdd = array();
			}

			if (!in_array($fields['FIELD_NAME'], $fieldsAvailableToAdd, true))
			{
				$errors = 'The field '.
						(isset($fields['FIELD_NAME']) ? "'".$fields['FIELD_NAME']."' " : "").
						'can not be added.';
				return false;
			}
		}
		return true;
	}
	protected function exists($ID, $entityTypeID)
	{
		$entity = self::getEntity();

		$res = $entity->getList(array(
				'order' => array('SORT' => 'ASC', 'ID' => 'ASC'),
				'filter' => array(
						'=ENTITY_TYPE_ID' => $entityTypeID,
						'=ID' => (int)$ID
				),
				'select' => array('ID', 'ENTITY_TYPE_ID', 'SETTINGS', 'COUNTRY_ID'),
				'limit' => 1
		));

		return $res->fetch();
	}
	protected function getByFieldId($id, $fields, $option = array())
	{
		$requisite = self::getOwnerEntity();

		$result = array();
		$presetCountryId = isset($option['COUNTRY_ID']) ? (int)$option['COUNTRY_ID'] : 0;
		$fieldsTitles = $requisite->getFieldsTitles($presetCountryId);
		foreach ($fields as $fieldInfo)
		{
			if (isset($fieldInfo['ID']) && $id === (int)$fieldInfo['ID'])
			{
				$result = $fieldInfo;
				if($result['FIELD_TITLE'] == '')
				{
					if (isset($fieldInfo['FIELD_NAME']) && !empty($fieldInfo['FIELD_NAME']))
					{
						if (isset($fieldsTitles[$fieldInfo['FIELD_NAME']]))
						{
							$title = $fieldsTitles[$fieldInfo['FIELD_NAME']];
							if (!empty($title))
								$result['FIELD_TITLE'] = $title;
						}
					}
				}
				break;
			}
		}
		return $result;
	}
}

class CCrmRequisiteRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private static $ENTITY = null;

	public  function getOwnerTypeID()
	{
		return CCrmOwnerType::Requisite;
	}
	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new EntityRequisite();
		}

		return self::$ENTITY;
	}

	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = EntityRequisite::getFieldsInfo();
			self::prepareUserFieldsInfo($this->FIELDS_INFO, EntityRequisite::$sUFEntityID);
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		$entityTypeID = intval($this->resolveParam($fields, 'ENTITY_TYPE_ID'));
		$entityID = intval($this->resolveParam($fields, 'ENTITY_ID'));
		$presetID = intval($this->resolveParam($fields, 'PRESET_ID'));

		if(!$this->isValidID($entityTypeID) || !CCrmOwnerType::IsDefined($entityTypeID))
		{
			$errors[] = 'ENTITY_TYPE_ID is not defined or invalid.';
			return false;
		}
		if(!$this->checkEntityID($entityID))
		{
			$errors[] = 'ENTITY_ID is not defined or invalid.';
			return false;
		}
		if(!$this->checkEntityID($presetID))
		{
			$errors[] = 'PRESET_ID is not defined or invalid.';
			return false;
		}

		if(!EntityRequisite::checkCreatePermissionOwnerEntity($entityTypeID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();

		$result = $entity->add($fields);

		if(!$result->isSuccess())
		{
			$errors = $result->getErrors();
		}
		else
		{
			CCrmEntityHelper::NormalizeUserFields($fields, EntityRequisite::$sUFEntityID, $GLOBALS['USER_FIELD_MANAGER'], array('IS_NEW' => true));
			$GLOBALS['USER_FIELD_MANAGER']->Update(EntityRequisite::$sUFEntityID, $result->getId(), $fields);

			if(self::isBizProcEnabled())
			{
				CCrmBizProcHelper::AutoStartWorkflows(
						CCrmOwnerType::Requisite,
						$result->getId(),
						CCrmBizProcEventType::Create,
						$errors
				);
			}
		}

		return $result->getId();
	}
	protected function innerGet($ID, &$errors)
	{
		$r = $this->getById($ID);
		if(!is_array($r))
		{
			$errors[] = "The Requisite with ID '{$ID}' is not found";
			return false;
		}

		if(!EntityRequisite::checkReadPermissionOwnerEntity($r['ENTITY_TYPE_ID'], $r['ENTITY_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return $r;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!EntityRequisite::checkReadPermissionOwnerEntity())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();

		$page = isset($navigation['iNumPage']) ? (int)$navigation['iNumPage'] : 1;
		$limit = isset($navigation['nPageSize']) ? (int)$navigation['nPageSize'] : CCrmRestService::LIST_LIMIT;
		$offset = $limit * $page;

		if(empty($select))
			$select = array_keys($this->getFieldsInfo());

		$result = $entity->getList(
				array(
						'order' => $order,
						'filter' => $filter,
						'select' => $select,
						'offset' => $offset,
						'count_total' => true
				)
		);

		$dbResult = new CDBResult($result);
		$dbResult->NavStart($limit, false, $page);

		return $dbResult;
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		$r = $this->getById($ID);
		if(!is_array($r))
		{
			$errors[] = "The Requisite with ID '{$ID}' is not found";
			return false;
		}

		$entityTypeID = intval($r['ENTITY_TYPE_ID']);
		$entityID = intval($r['ENTITY_ID']);
		$presetID = intval($r['PRESET_ID']);

		if(!$this->isValidID($entityTypeID) || !CCrmOwnerType::IsDefined($entityTypeID))
		{
			$errors[] = "ENTITY_TYPE_ID is not defined or invalid.";
			return false;
		}
		if(!$this->checkEntityID($entityID))
		{
			$errors[] = "ENTITY_ID is not defined or invalid.";
			return false;
		}
		if(!$this->checkEntityID($presetID))
		{
			$errors[] = "PRESET_ID is not defined or invalid.";
			return false;
		}

		if(!EntityRequisite::checkUpdatePermissionOwnerEntity($entityTypeID, $entityID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();

		$result = $entity->update($ID, $fields, $params);
		if(!$result->isSuccess())
		{
			$errors = $result->getErrors();
		}
		elseif(self::isBizProcEnabled())
		{
			CCrmEntityHelper::NormalizeUserFields($fields, EntityRequisite::$sUFEntityID, $GLOBALS['USER_FIELD_MANAGER'], array('IS_NEW' => false));
			$GLOBALS['USER_FIELD_MANAGER']->Update(EntityRequisite::$sUFEntityID, $ID, $fields);

			CCrmBizProcHelper::AutoStartWorkflows(
					CCrmOwnerType::Company,
					$result->getId(),
					CCrmBizProcEventType::Edit,
					$errors
			);
		}
		return $result->isSuccess();
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		$r = $this->getById($ID);
		if(!is_array($r))
		{
			$errors[] = "The Requisite with ID '{$ID}' is not found";
			return false;
		}

		if(!EntityRequisite::checkDeletePermissionOwnerEntity($r['ENTITY_TYPE_ID'], $r['ENTITY_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();
		$result = $entity->delete($ID);
		if(!$result->isSuccess())
		{
			$errors[] = $result->getErrors();
		}

		return $result->isSuccess();
	}

	protected function getById($ID)
	{
		$entity = self::getEntity();

		$result = $entity->getList(array('filter'=>array('ID' => $ID)));
		return $result->fetch();
	}
}

class CCrmRequisiteBankDetailRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private static $ENTITY = null;

	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new EntityBankDetail();
		}

		return self::$ENTITY;
	}

	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = EntityBankDetail::getFieldsInfo();
		}
		return $this->FIELDS_INFO;
	}
	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		$entityTypeID = $fields['ENTITY_TYPE_ID'] = CCrmOwnerType::Requisite;
		$entityID = intval($this->resolveParam($fields, 'ENTITY_ID'));

		if(!$this->checkEntityID($entityID))
		{
			$errors[] = 'ENTITY_ID is not defined or invalid.';
			return false;
		}

		if(!EntityBankDetail::checkCreatePermissionOwnerEntity($entityTypeID, $entityID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();

		$options = array();
		$result = $entity->add($fields, $options);
		if(!$result->isSuccess())
		{
			$errors[] = $result->getErrors();
		}
		elseif(self::isBizProcEnabled())
		{
			CCrmBizProcHelper::AutoStartWorkflows(
					CCrmOwnerType::Requisite,
					$result->getId(),
					CCrmBizProcEventType::Create,
					$errors
			);
		}
		return $result->getId();
	}
	protected function innerGet($ID, &$errors)
	{
		$r = $this->getById($ID);
		if(!is_array($r))
		{
			$errors[] = "The RequisiteBankDetail with ID '{$ID}' is not found";
			return false;
		}

		if(!EntityBankDetail::checkReadPermissionOwnerEntity($r['ENTITY_TYPE_ID'], $r['ENTITY_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		return $r;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!EntityBankDetail::checkReadPermissionOwnerEntity())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();

		$page = isset($navigation['iNumPage']) ? (int)$navigation['iNumPage'] : 1;
		$limit = isset($navigation['nPageSize']) ? (int)$navigation['nPageSize'] : CCrmRestService::LIST_LIMIT;
		$offset = $limit * $page;

		if(!is_array($order))
			$order = array();

		if(!is_array($filter))
			$filter = array();

		if(!is_array($select))
			$select = array();

		if(empty($select))
			$select = array_keys($this->getFieldsInfo());

		$result = $entity->getList(
				array(
						'order' => $order,
						'filter' => $filter,
						'select' => $select,
						'offset' => $offset,
						'count_total' => true
				)
		);

		$dbResult = new CDBResult($result);
		$dbResult->NavStart($limit, false, $page);

		return $dbResult;
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		$r = $this->getById($ID);
		if(!is_array($r))
		{
			$errors[] = "The RequisiteBankDetail with ID '{$ID}' is not found";
			return false;
		}

		$entityTypeID = intval($r['ENTITY_TYPE_ID']);
		$entityID = intval($r['ENTITY_ID']);

		if(!$this->isValidID($entityTypeID) || !CCrmOwnerType::IsDefined($entityTypeID))
		{
			$errors[] = "ENTITY_TYPE_ID is not defined or invalid.";
			return false;
		}
		if(!$this->checkEntityID($entityID))
		{
			$errors[] = "ENTITY_ID is not defined or invalid.";
			return false;
		}

		if(!EntityBankDetail::checkUpdatePermissionOwnerEntity($entityTypeID, $entityID))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();

		$result = $entity->update($ID, $fields, $params);
		if(!$result->isSuccess())
		{
			$errors[] = $result->getErrors();
		}
		elseif(self::isBizProcEnabled())
		{
			CCrmBizProcHelper::AutoStartWorkflows(
					CCrmOwnerType::Company,
					$result->getId(),
					CCrmBizProcEventType::Edit,
					$errors
			);
		}
		return $result->isSuccess();
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		$r = $this->getById($ID);
		if(!is_array($r))
		{
			$errors[] = "The RequisiteBankDetail with ID '{$ID}' is not found";
			return false;
		}

		if(!EntityBankDetail::checkDeletePermissionOwnerEntity($r['ENTITY_TYPE_ID'], $r['ENTITY_ID']))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();
		$result = $entity->delete($ID);
		if(!$result->isSuccess())
		{
			$errors[] = $result->getErrors();
		}

		return $result->isSuccess();
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');

		if(in_array($nameSuffix, array('FIELDS', 'ADD', 'GET', 'LIST', 'UPDATE', 'DELETE'), true))
		{
			return parent::processMethodRequest($nameSuffix, '', $arParams, $nav, $server);
		}

		throw new RestException("Resource '{$name}' is not supported in current context.");
	}

	protected function getById($ID)
	{
		$entity = self::getEntity();

		$result = $entity->getList(array('filter'=>array('ID' => $ID)));
		return $result->fetch();
	}
}

class CCrmRequisiteLinkRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private static $ENTITY = null;

	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = Bitrix\Crm\Requisite\EntityLink::getFieldsInfo();
		}
		return $this->FIELDS_INFO;
	}

	protected function checkRequisiteLinks($entityTypeId, $entityId,
											$requisiteId, $bankDetailId,
											$mcRequisiteId, $mcBankDetailId, &$errors)
	{
		$params = array(
			'ENTITY_TYPE_ID' => $entityTypeId,
			'ENTITY_ID' => $entityId,
			'REQUISITE_ID' => $requisiteId,
			'BANK_DETAIL_ID' => $bankDetailId,
			'MC_REQUISITE_ID' => $mcRequisiteId,
			'MC_BANK_DETAIL_ID' => $mcBankDetailId,
		);

		foreach ($params as $paramName => $value)
		{
			if ($value === '' || intval($value) < 0
				|| (($paramName === 'ENTITY_TYPE_ID' || $paramName === 'ENTITY_ID') && intval($value) === 0))
			{
				$errors[] = $paramName.' is not defined or invalid.';
				return false;
			}
		}

		return true;
	}

	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if (!Bitrix\Crm\Requisite\EntityLink::checkReadPermissionOwnerEntity())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$page = isset($navigation['iNumPage']) ? (int)$navigation['iNumPage'] : 1;
		$limit = isset($navigation['nPageSize']) ? (int)$navigation['nPageSize'] : CCrmRestService::LIST_LIMIT;
		$offset = $limit * $page;

		if(empty($select))
			$select = array_keys($this->getFieldsInfo());

		$result = Bitrix\Crm\Requisite\EntityLink::getList(
			array(
				'order' => $order,
				'filter' => $filter,
				'select' => $select,
				'offset' => $offset,
				'count_total' => true
			)
		);

		$dbResult = new CDBResult($result);
		$dbResult->NavStart($limit, false, $page);

		return $dbResult;
	}
	protected function innerRegister(&$fields, &$errors, array $params = null)
	{
		$entityTypeId = $this->resolveParam($fields, 'ENTITY_TYPE_ID');
		$entityId = $this->resolveParam($fields, 'ENTITY_ID');
		$requisiteId = $this->resolveParam($fields, 'REQUISITE_ID');
		$bankDetailId = $this->resolveParam($fields, 'BANK_DETAIL_ID');
		$mcRequisiteId = $this->resolveParam($fields, 'MC_REQUISITE_ID');
		$mcBankDetailId = $this->resolveParam($fields, 'MC_BANK_DETAIL_ID');

		if (!$this->checkRequisiteLinks($entityTypeId, $entityId,
			$requisiteId, $bankDetailId, $mcRequisiteId, $mcBankDetailId, $errors))
		{
			return false;
		}

		$entityTypeId = (int)$entityTypeId;
		$entityId = (int)$entityId;
		$requisiteId = (int)$requisiteId;
		$bankDetailId = (int)$bankDetailId;
		$mcRequisiteId = (int)$mcRequisiteId;
		$mcBankDetailId = (int)$mcBankDetailId;

		if (!Bitrix\Crm\Requisite\EntityLink::checkUpdatePermissionOwnerEntity($entityTypeId, $entityId))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		try
		{
			Bitrix\Crm\Requisite\EntityLink::checkConsistence(
				$entityTypeId, $entityId,
				$requisiteId, $bankDetailId,
				$mcRequisiteId, $mcBankDetailId
			);

			Bitrix\Crm\Requisite\EntityLink::register(
				$entityTypeId, $entityId,
				$requisiteId, $bankDetailId,
				$mcRequisiteId, $mcBankDetailId
			);
		}
		catch (Main\SystemException $e)
		{
			$errors[] = $e->getMessage();
			return false;
		}

		return true;
	}
	protected function innerUnregister($entityTypeId, $entityId, &$errors)
	{
		if(!Bitrix\Crm\Requisite\EntityLink::checkUpdatePermissionOwnerEntity($entityTypeId, $entityId))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		try
		{
			Bitrix\Crm\Requisite\EntityLink::unregister($entityTypeId, $entityId);
		}
		catch (Main\SystemException $e)
		{
			$errors[] = $e->getMessage();
			return false;
		}

		return true;
	}

	protected function getEntityRequisite($entityTypeId, $entityId, &$errors)
	{
		if(!Bitrix\Crm\Requisite\EntityLink::checkReadPermissionOwnerEntity($entityTypeId, $entityId))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$linkInfo = Bitrix\Crm\Requisite\EntityLink::getByEntity($entityTypeId, $entityId);
		if (is_array($linkInfo))
		{
			$result = array_merge(array('ENTITY_TYPE_ID' => $entityTypeId, 'ENTITY_ID' => $entityId), $linkInfo);
		}
		else
		{
			$errors[] = 'Not found';
			return false;
		}

		return $result;
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if($name === 'LINK')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if ($nameSuffix === 'FIELDS' || $nameSuffix === 'LIST')
			{
				return parent::processMethodRequest($nameSuffix, '', $arParams, $nav, $server);
			}
			else if ($nameSuffix === 'GET')
			{
				$entityTypeId = intval($this->resolveParam($arParams, 'entityTypeId'));
				$entityId = intval($this->resolveParam($arParams, 'entityId'));

				if(!$this->isValidID($entityTypeId)
					|| !($entityTypeId === CCrmOwnerType::Deal
						|| $entityTypeId === CCrmOwnerType::Quote
						|| $entityTypeId === CCrmOwnerType::Invoice))
				{
					$errors[] = 'entityTypeId is not defined or invalid.';
					return false;
				}
				if(!$this->checkEntityID($entityId))
				{
					$errors[] = 'entityId is not defined or invalid.';
					return false;
				}

				$errors = array();
				$result = $this->getEntityRequisite($entityTypeId, $entityId, $errors);
				if(!is_array($result))
				{
					throw new RestException(implode("\n", $errors));
				}
				$this->externalizeFields($result, $this->getFieldsInfo());
				return $result;
			}
			else if ($nameSuffix === 'REGISTER')
			{
				$fields = $this->resolveArrayParam($arParams, 'fields');
				$methodParams = $this->resolveArrayParam($arParams, 'params');
				$this->internalizeFields($fields, $this->getFieldsInfo(), array());
				$errors = array();
				if(!$this->innerRegister($fields, $errors, $methodParams))
				{
					throw new RestException(implode("\n", $errors));
				}

				return true;
			}
			else if ($nameSuffix === 'UNREGISTER')
			{
				$entityTypeId = intval($this->resolveParam($arParams, 'entityTypeId'));
				$entityId = intval($this->resolveParam($arParams, 'entityId'));

				if(!$this->isValidID($entityTypeId)
					|| !($entityTypeId === CCrmOwnerType::Deal
						|| $entityTypeId === CCrmOwnerType::Quote
						|| $entityTypeId === CCrmOwnerType::Invoice))
				{
					$errors[] = 'entityTypeId is not defined or invalid.';
					return false;
				}
				if(!$this->checkEntityID($entityId))
				{
					throw new RestException('entityId is not defined or invalid.');
				}

				$errors = array();
				if(!$this->innerUnregister($entityTypeId, $entityId, $errors))
				{
					throw new RestException(implode("\n", $errors));
				}

				return true;
			}
		}

		throw new RestException("Resource '{$name}' is not supported in current context.");
	}
}

class CCrmAddressRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	private static $ENTITY = null;

	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new EntityAddress();
		}

		return self::$ENTITY;
	}

	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = EntityAddress::getFieldsInfo();
		}
		return $this->FIELDS_INFO;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if(!EntityAddress::checkReadPermissionOwnerEntity())
		{
			$errors[] = 'Access denied.';
			return false;
		}

		$entity = self::getEntity();

		$page = isset($navigation['iNumPage']) ? (int)$navigation['iNumPage'] : 1;
		$limit = isset($navigation['nPageSize']) ? (int)$navigation['nPageSize'] : CCrmRestService::LIST_LIMIT;
		$offset = $limit * $page;

		if(empty($select))
			$select = array_keys($this->getFieldsInfo());

		//For backward compatibility only
		if(isset($filter['ENTITY_TYPE_ID']) &&
				($filter['ENTITY_TYPE_ID'] == CCrmOwnerType::Company || $filter['ENTITY_TYPE_ID'] == CCrmOwnerType::Contact))
		{
			$filter['ANCHOR_TYPE_ID'] = $filter['ENTITY_TYPE_ID'];
			unset($filter['ENTITY_TYPE_ID']);

			if(isset($filter['ENTITY_ID']))
			{
				$filter['ANCHOR_ID'] = $filter['ENTITY_ID'];
				unset($filter['ENTITY_ID']);
			}
		}

		$result = $entity->getList(
				array(
						'order' => $order,
						'filter' => $filter,
						'select' => $select,
						'offset' => $offset,
						'count_total' => true
				)
		);

		if (is_object($result))
		{
			$dbResult = new CDBResult($result);
		}
		else
		{
			$dbResult = new CDBResult();
			$dbResult->InitFromArray(array());
		}
		$dbResult->NavStart($limit, false, $page);

		return $dbResult;
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);

		if($name === 'FIELDS' || $name === 'LIST')
		{
			return parent::processMethodRequest($name, $nameDetails, $arParams, $nav, $server);
		}
		elseif($name === 'ADD')
		{
			$fields = $this->resolveArrayParam($arParams, 'fields');

			$entityID = $entityTypeID = $typeID = 0;

			if(is_array($fields))
			{
				$typeID = intval($this->resolveParam($fields, 'TYPE_ID'));
				$entityTypeID = intval($this->resolveParam($fields, 'ENTITY_TYPE_ID'));
				$entityID = intval($this->resolveParam($fields, 'ENTITY_ID'));
			}

			if(!$this->isValidID($typeID) || !EntityAddressType::isDefined($typeID))
			{
				throw new RestException('TYPE_ID is not defined or invalid.');
			}
			if(!$this->isValidID($entityTypeID) || !CCrmOwnerType::IsDefined($entityTypeID))
			{
				throw new RestException('ENTITY_TYPE_ID is not defined or invalid.');
			}
			if(!$this->checkEntityID($entityID))
			{
				throw new RestException('ENTITY_ID is not defined or invalid.');
			}

			$r = $this->exists($typeID, $entityTypeID, $entityID);
			if(is_array($r))
			{
				throw new RestException("TypeAddress exists.");
			}

			if(!EntityAddress::checkCreatePermissionOwnerEntity($entityTypeID, $entityID))
			{
				throw new RestException('Access denied.');
			}

			$this->internalizeFields($fields, $this->getFieldsInfo(), array());

			if ($entityTypeID === \CCrmOwnerType::Requisite)
			{
				$anchor = EntityRequisite::getOwnerEntityById($entityID);
				$fields['ANCHOR_TYPE_ID'] = intval($anchor['ENTITY_TYPE_ID']);
				$fields['ANCHOR_ID'] = intval($anchor['ENTITY_ID']);
			}

			EntityAddress::register($entityTypeID, $entityID, $typeID, $fields);

			return true;
		}
		elseif($name === 'UPDATE')
		{
			$fields = $this->resolveArrayParam($arParams, 'fields');
			$typeID = intval($this->resolveParam($fields, 'TYPE_ID'));
			$entityTypeID = intval($this->resolveParam($fields, 'ENTITY_TYPE_ID'));
			$entityID = intval($this->resolveParam($fields, 'ENTITY_ID'));

			if(!$this->isValidID($typeID) || !EntityAddressType::isDefined($typeID))
			{
				throw new RestException('TYPE_ID is not defined or invalid.');
			}
			if(!$this->isValidID($entityTypeID) || !CCrmOwnerType::IsDefined($entityTypeID))
			{
				throw new RestException('ENTITY_TYPE_ID is not defined or invalid.');
			}
			if(!$this->checkEntityID($entityID))
			{
				throw new RestException('ENTITY_ID is not defined or invalid.');
			}

			$r = $this->exists($typeID, $entityTypeID, $entityID);
			if(!is_array($r))
			{
				throw new RestException("TypeAddress not found.");
			}

			if(!EntityAddress::checkUpdatePermissionOwnerEntity($entityTypeID, $entityID))
			{
				throw new RestException('Access denied.');
			}

			$this->internalizeFields($fields, $this->getFieldsInfo(), array());

			EntityAddress::register($entityTypeID, $entityID, $typeID, $fields);

			return true;
		}
		elseif($name === 'DELETE')
		{
			$fields = $this->resolveArrayParam($arParams, 'fields');
			$typeID = intval($this->resolveParam($fields, 'TYPE_ID'));
			$entityTypeID = intval($this->resolveParam($fields, 'ENTITY_TYPE_ID'));
			$entityID = intval($this->resolveParam($fields, 'ENTITY_ID'));

			if(!$this->isValidID($typeID) || !EntityAddressType::isDefined($typeID))
			{
				throw new RestException('TYPE_ID is not defined or invalid.');
			}
			if(!$this->isValidID($entityTypeID) || !CCrmOwnerType::IsDefined($entityTypeID))
			{
				throw new RestException('ENTITY_TYPE_ID is not defined or invalid.');
			}
			if(!$this->checkEntityID($entityID))
			{
				throw new RestException('ENTITY_ID is not defined or invalid.');
			}

			$r = $this->exists($typeID, $entityTypeID, $entityID);
			if(!is_array($r))
			{
				throw new RestException("TypeAddress not found.");
			}

			if(!EntityAddress::checkDeletePermissionOwnerEntity($entityTypeID, $entityID))
			{
				throw new RestException('Access denied.');
			}

			EntityAddress::unregister($entityTypeID, $entityID, $typeID);

			return true;
		}

		throw new RestException("Resource '{$name}' is not supported in current context.");
	}

	protected function exists($typeID, $entityTypeID, $entityID)
	{
		$entity = self::getEntity();

		$result = $entity->getList(array(
				'filter' => array('TYPE_ID' => $typeID,
						'ENTITY_TYPE_ID' => $entityTypeID,
						'ENTITY_ID' => $entityID)
		));

		return $result->fetch();
	}
}

class CCrmExternalChannelConnectorRestProxy  extends CCrmRestProxyBase
{
	const ERROR_CONNECTOR_CREATE = 'ERROR_CONNECTOR_CREATE';
	const ERROR_CONNECTOR_REGISTRATION = 'ERROR_CONNECTOR_REGISTRATION';

	private $FIELDS_INFO = null;
	private static $ENTITY = null;

	private static function getEntity()
	{
		if(!self::$ENTITY)
		{
			self::$ENTITY = new Rest\CCrmExternalChannelConnector();
		}

		return self::$ENTITY;
	}
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = Rest\CCrmExternalChannelConnector::getFieldsInfo();
		}
		return $this->FIELDS_INFO;
	}

	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		$entity = self::getEntity();

		$page = isset($navigation['iNumPage']) ? (int)$navigation['iNumPage'] : 1;
		$limit = isset($navigation['nPageSize']) ? (int)$navigation['nPageSize'] : CCrmRestService::LIST_LIMIT;
		$offset = $limit * $page;

		if(empty($select))
			$select = array_keys($this->getFieldsInfo());

		$result = $entity->getList(
				array(
						'order' => $order,
						'filter' => $filter,
						'select' => $select,
						'offset' => $offset,
						'count_total' => true
				)
		);

		if (is_object($result))
		{
			$dbResult = new CDBResult($result);
		}
		else
		{
			$dbResult = new CDBResult();
			$dbResult->InitFromArray(array());
		}
		$dbResult->NavStart($limit, false, $page);

		return $dbResult;
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);

		if ($name === 'CONNECTOR')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');

			if($nameSuffix === 'FIELDS' || $nameSuffix === 'LIST')
			{
				return parent::processMethodRequest($nameSuffix, $nameDetails, $arParams, $nav, $server);
			}
			elseif($nameSuffix === 'REGISTER')
			{
				$entity = self::getEntity();
				$entity->setServer($this->getServer());

				$fields = $this->resolveArrayParam($arParams, 'fields');

				$entity->prepareFields($fields);
				$entity->checkFields($fields, $error);

				$this->internalizeFields($fields, $this->getFieldsInfo(), array());

				if(count($error)<=0)
				{
					$channelId = $entity::register($fields['TYPE_ID'], $fields['ORIGINATOR_ID'], $fields);
				}
				else
				{
					throw new RestException(implode('; ', $error), self::ERROR_CONNECTOR_REGISTRATION, CRestServer::STATUS_WRONG_REQUEST);
				}

				if(strlen($channelId)>0)
				{
					return array("result"=>$channelId);
				}
				else
				{
					throw new RestException('Connector not created', self::ERROR_CONNECTOR_CREATE, CRestServer::STATUS_INTERNAL);
				}
			}
			elseif($nameSuffix === 'UNREGISTER')
			{
				$entity = self::getEntity();

				$typeId = $originatorId = 0;

				$fields = $this->resolveArrayParam($arParams, 'fields');
				if(!is_array($fields))
				{
					$typeId = $this->resolveParam($fields, 'TYPE_ID');
					$originatorId = $this->resolveParam($fields, 'ORIGINATOR_ID');
				}

				if(!$this->isValidCode($typeId) || !Rest\CCrmExternalChannelType::isDefined(Rest\CCrmExternalChannelType::resolveID($typeId)))
				{
					throw new RestException('TYPE_ID is not defined or invalid.');
				}
				if(!$this->isValidCode($originatorId))
				{
					throw new RestException('ORIGINATOR_ID is not defined');
				}

				$r = $this->exists($typeId, $originatorId);
				if(!is_array($r))
				{
					throw new RestException("Connector not found.");
				}

				$entity::unregister($typeId, $originatorId);

				return true;
			}
		}
		throw new RestException("Resource '{$name}' is not supported in current context.");
	}

	public function isValidCode($code)
	{
		return is_string($code) && strlen($code) > 0;
	}

	protected function exists($typeID, $originatorId)
	{
		$entity = self::getEntity();

		$result = $entity->getList(array(
				'filter' => array('TYPE_ID' => $typeID,
						'ORIGINATOR_ID' => $originatorId)
		));

		return $result->fetch();
	}
}

class CCrmExternalChannelRestProxy  extends CCrmRestProxyBase
{
	const ERROR_IMPORT_BATCH = 'ERROR_IMPORT_BATCH';

	const ERROR_CONNECTOR_NOT_FOUND = 'ERROR_CONNECTOR_NOT_FOUND';

	const ERROR_CONNECTOR_INVALID = 'ERROR_CONNECTOR_INVALID';

	const ERROR_PRESET_NOT_FOUND = 'ERROR_PRESET_NOT_FOUND';

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$connector = new Rest\CCrmExternalChannelConnector();

		$name = strtoupper($name);

		if ($name === 'COMPANY' || $name === 'CONTACT' || $name === 'ACTIVITY')
		{
			$resultImport = array();

			$isRegistered = false;
			$methodParams = $this->resolveArrayParam($arParams, 'params');
			if(($channel_id = $methodParams['CHANNEL_ID']) && strlen($channel_id)>0)
			{
				$connector->setChannelId($channel_id);
				$isRegistered = $connector->isRegistered();
				$originator_id = $connector->getOriginatorId();
			}

			if(!$isRegistered)
			{
				throw new RestException('Connector not found!', self::ERROR_CONNECTOR_NOT_FOUND, CRestServer::STATUS_NOT_FOUND);
			}
			elseif(empty($originator_id) || $originator_id === '')
			{
				throw new RestException('Connector is invalid!', self::ERROR_CONNECTOR_INVALID, CRestServer::STATUS_FORBIDDEN);
			}
			else
			{
				$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');

				if($name === 'COMPANY' || $nameSuffix === 'COMPANY')
					$entity = new CCrmCompanyRestProxy();
				else
					$entity = new CCrmContactRestProxy();

				$preset = new Rest\CCrmExternalChannelImportPreset();
				$preset->setOwnerEntity($entity);

				if(!$this->isValidID($preset->getPresetId()))
				{
					throw new RestException("Preset is not defined.", self::ERROR_PRESET_NOT_FOUND, CRestServer::STATUS_FORBIDDEN);
				}

				$import = new Rest\CCrmExternalChannelImport($connector, $preset);

				$batch = $import->resolveParamsBatch($arParams);

				if (!is_array($batch) || count($batch) === 0)
				{
					throw new RestException("Batch is not defined.", self::ERROR_IMPORT_BATCH, CRestServer::STATUS_WRONG_REQUEST);
				}
				elseif(count($batch)>\CRestUtil::BATCH_MAX_LENGTH)
				{
					throw new RestException("Max batch length exceeded ".\CRestUtil::BATCH_MAX_LENGTH, \CRestProvider::ERROR_BATCH_LENGTH_EXCEEDED, CRestServer::STATUS_WRONG_REQUEST);
				}
				else
				{
					$added = 0;
					$updated = 0;
					$skipped = 0;
					$errorList = array();

					foreach($batch as $num => $items)
					{
						$activity = new Rest\CCrmExternalChannelImportActivity();
						$agent = 	new Rest\CCrmExternalChannelImportAgent();

						$activity->setOwnerEntity($entity);
						$agent->setEntity($entity);

						$activity->import = $import;
						$agent->import = $import;

						$import->setRawData($items);

						$isNewVersionAgent = false;

						$bAgentAdd = false;
						$bAgentUpd = false;
						$bAgentSkip = false;
						$bActivityAdd = false;
						$bActivityUpd = false;

						if(($agentFields = $items[Rest\CCrmExternalChannelImport::AGENT]) && count($agentFields)>0)
						{
							if((is_set($agentFields, Rest\CCrmExternalChannelImport::FIELDS) && count($agentFields[Rest\CCrmExternalChannelImport::FIELDS])>0) ||
									(is_set($agentFields, Rest\CCrmExternalChannelImport::EXTERNAL_FIELDS) && count($agentFields[Rest\CCrmExternalChannelImport::EXTERNAL_FIELDS])>0))
							{

								$errors = array();
								$agent->checkFields($agentFields[Rest\CCrmExternalChannelImport::FIELDS], $errors);
								$agent->checkExternalFields($agentFields[Rest\CCrmExternalChannelImport::EXTERNAL_FIELDS], $errors);

								if(count($errors)>0)
									$errorList[$num] = $import->formatErrorsPackage(implode('; ', $errors), $num);
								else
								{
									$ownerInfo = $agent->tryGetOwnerInfos($agentFields[Rest\CCrmExternalChannelImport::FIELDS], $errors);

									$isNewVersionAgent = ($ownerInfo['version'] == '' ||
											$ownerInfo['version'] !== $agentFields[Rest\CCrmExternalChannelImport::FIELDS]['ORIGIN_VERSION']);

									if($isNewVersionAgent)
									{
										if(count($errors)>0)
											$errorList[$num] = $import->formatErrorsPackage(implode('; ', $errors), $num);
										else
										{
											$resultAgent = array();
											$agent->modify($ownerInfo['id'], $agentFields, $resultAgent);

											$agentId = $resultAgent['id'];

											if(count($resultAgent['process']['error'])>0)
												$errorList[$num] = $import->formatErrorsPackage($resultAgent['process']['error'], $num);
											elseif(!$this->isValidID($agentId))
												$errorList[$num] = $import->formatErrorsPackage("Agent is not created", $num);
											else
											{
												$bAgentAdd = $resultAgent['process']['add'];
												$bAgentUpd = $resultAgent['process']['upd'];

												$activity->setOwnerEntityId($agentId);
											}
										}
									}
									else
									{
										$activity->setOwnerEntityId($ownerInfo['id']);

										$bAgentSkip = true;
									}
								}
							}
							else
								$errorList[$num] = $import->formatErrorsPackage("Agent fields or external fields is not defined.", $num);

							if(count($errorList[$num])<=0)
							{
								$error = array();

								if($name === 'ACTIVITY')
								{
									if(($activityInfo = $items[Rest\CCrmExternalChannelImport::ACTIVITY]) && count($activityInfo)>0)
									{
										$resultActivity = array();

										$activity->setTypeActivity(Rest\CCrmExternalChannelActivityType::ActivityName);

										$activity->import($activityInfo, $resultActivity);

										$activityId = $resultActivity['id'];

										if(count($resultActivity['process']['error'])>0)
											$error[] = implode(';',$resultActivity['process']['error']);
										elseif(!$this->isValidID($activityId))
											$error[] = "Activity is not imported";
										else
										{
											$bActivityAdd = $resultActivity['process']['add'];
											$bActivityUpd = $resultActivity['process']['upd'];
										}
									}
									else
										$error[] = "Activity is not defined.";


								}
								else
								{
									if($isNewVersionAgent)
									{
										$fields = array();

										$activity->setTypeActivity(Rest\CCrmExternalChannelActivityType::ImportAgentName);

										$activity->fillEmptyFields($fields, $agentFields);

										$items[Rest\CCrmExternalChannelImport::ACTIVITY] = array();

										$activity->fillFields($fields);

										$activityId = $activity->getEntity()->innerAdd($fields, $errors);

										if(count($errors)>0)
										{
											$error[] = implode('; ', $errors);
										}
										elseif(!$this->isValidID($activityId))
										{
											$error[] = "Activity is not created";
										}
										else
										{
											$activity->registerActivityInChannel($activityId, $connector);
										}
									}
								}

								if(count($error)>0)
									$errorList[$num] = $import->formatErrorsPackage($error, $num);
							}
						}
						else
							$errorList[$num] = $import->formatErrorsPackage("Agent is not defined.", $num);

						if($name === 'COMPANY' || $name === 'CONTACT')
						{
							if($bAgentAdd) $added++;
							if($bAgentUpd) $updated++;
							if($bAgentSkip) $skipped++;
						}
						elseif($name === 'ACTIVITY')
						{
							if($bActivityAdd) $added++;
							if($bActivityUpd) $updated++;
						}
					}
				}

				if($added>0)
					$resultImport['added'] = $added;
				if($updated>0)
					$resultImport['updated'] = $updated;
				if($skipped>0)
					$resultImport['skipped'] = $skipped;
				if(count($errorList)>0)
					$resultImport['result_error'] = implode(';', $errorList);

				return $resultImport;
			}
		}

		throw new RestException("Resource '{$name}' is not supported in current context.");
	}
}

class CCrmPersonTypeRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = array(
				'ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'NAME' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				)
			);
		}
		return $this->FIELDS_INFO;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		global $USER;

		$result = array();

		$crmPerms = new CCrmPerms($USER->GetID());
		if (!$crmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if (!CModule::IncludeModule('sale'))
		{
			$errors[] = 'Sale module is not installed.';
			return false;
		}

		$res = CSalePersonType::GetList($order, $filter, false, $navigation, $select);
		while($personType = $res->Fetch())
			$result[] = $personType;

		return $result;
	}
}

class CCrmPaySystemRestProxy extends CCrmRestProxyBase
{
	private $FIELDS_INFO = null;
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		if(!$this->FIELDS_INFO)
		{
			$this->FIELDS_INFO = array(
				'ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'NAME' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'ACTIVE' => array(
					'TYPE' => 'char',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'SORT' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'DESCRIPTION' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'PERSON_TYPE_ID' => array(
					'TYPE' => 'integer',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'ACTION_FILE' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'HANDLER' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'HANDLER_CODE' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
				'HANDLER_NAME' => array(
					'TYPE' => 'string',
					'ATTRIBUTES' => array(CCrmFieldInfoAttr::ReadOnly)
				),
			);
		}
		return $this->FIELDS_INFO;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		global $USER;

		$result = array();

		$crmPerms = new CCrmPerms($USER->GetID());
		if (!$crmPerms->HavePerm('CONFIG', BX_CRM_PERM_CONFIG, 'READ'))
		{
			$errors[] = 'Access denied.';
			return false;
		}

		if (!CModule::IncludeModule('sale'))
		{
			$errors[] = 'Sale module is not installed.';
			return false;
		}

		$personTypeIds = array();
		foreach (CCrmPaySystem::getPersonTypeIDs() as $ptId)
			$personTypeIds[] = (int)$ptId;
		$personTypeIds = array_values(CCrmPaySystem::getPersonTypeIDs());

		$page = isset($navigation['iNumPage']) ? (int)$navigation['iNumPage'] : 1;
		$limit = isset($navigation['nPageSize']) ? (int)$navigation['nPageSize'] : CCrmRestService::LIST_LIMIT;

		if (!empty($personTypeIds))
		{
			if (empty($select))
				$select = array('ID');

			$skip = array(
				'ACTION_FILE' => false,
				'HANDLER' => false,
				'HANDLER_CODE' => false,
				'HANDLER_NAME' => false
			);
			$selectMap = array_fill_keys($select, true);
			foreach (array_keys($skip) as $fieldName)
			{
				if (!isset($selectMap[$fieldName]))
				{
					if ($fieldName === 'ACTION_FILE')
					{
						$selectMap['ACTION_FILE'] = true;
						$skip[$fieldName] = true;
					}
					else
					{
						$skip[$fieldName] = true;
					}
				}
				else if ($fieldName !== 'ACTION_FILE')
				{
					unset($selectMap[$fieldName]);
				}
			}
			$select = array_keys($selectMap);
			unset($selectMap);

			$res = \Bitrix\Sale\PaySystem\Manager::getList(
				array(
					'order' => $order,
					'filter' => array(
						'LOGIC' => 'AND',
						$filter,
						array('!ID' => \Bitrix\Sale\PaySystem\Manager::getInnerPaySystemId()),
					),
					'select' => $select
				)
			);
			$handlerMap = null;
			$io = null;
			$actionList = null;
			while ($row = $res->fetch())
			{
				$actionFile = isset($row['ACTION_FILE']) ? $row['ACTION_FILE'] : '';
				/*// only quote or invoice handlers
				if (preg_match('/quote(_\w+)*$/i'.BX_UTF_PCRE_MODIFIER, $actionFile)
					|| preg_match('/bill(\w+)*$/i'.BX_UTF_PCRE_MODIFIER, $actionFile))
				{*/
				$paySystemPersonTypes = array();
				if (isset($row['ID']) && $row['ID'] > 0)
					$paySystemPersonTypes = \Bitrix\Sale\PaySystem\Manager::getPersonTypeIdList($row['ID']);
				if (empty($paySystemPersonTypes) || array_intersect($paySystemPersonTypes, $personTypeIds))
				{
					if (!$skip['HANDLER'])
						$row['HANDLER'] = $actionFile;
					if ($io === null)
						$io = CBXVirtualIo::GetInstance();
					$handlerCode = $io->ExtractNameFromPath($actionFile);
					if (!$skip['HANDLER_CODE'])
						$row['HANDLER_CODE'] = $handlerCode;
					if ($actionList === null)
						$actionList = CCrmPaySystem::getActionsList();
					if (!$skip['HANDLER_NAME'])
					{
						$row['HANDLER_NAME'] = isset($actionList[$handlerCode]) ?
							$actionList[$handlerCode] : '';
					}
					$handlerMap = CSalePaySystemAction::getOldToNewHandlersMap();
					$oldHandler = array_search($actionFile, $handlerMap);
					if ($skip['ACTION_FILE'])
					{
						unset($row['ACTION_FILE']);
					}
					else
					{
						if ($oldHandler !== false)
							$row['ACTION_FILE'] = $oldHandler;
					}

					$result[] = $row;
				}
			}
		}

		$dbResult = new CDBResult();
		$dbResult->InitFromArray($result);
		$dbResult->NavStart($limit, false, $page);

		return $dbResult;
	}
	protected function prepareListParams(&$order, &$filter, &$select)
	{
		parent::prepareListParams($order, $filter, $select); // TODO: Change the autogenerated stub

		$allowedFields = array_fill_keys(array_keys($this->getFieldsInfo()), true);

		if(empty($select) || in_array('*', $select, true))
			$select = array_keys($this->getFieldsInfo());

		foreach ($select as $fieldName)
		{
			if ($fieldName !== '*' && !isset($allowedFields[$fieldName]))
				unset($select[$fieldName]);
		}

		$restrictedFields = array('HANDLER', 'HANDLER_CODE', 'HANDLER_NAME');
		foreach ($restrictedFields as $fieldName)
		{
			if (isset($allowedFields[$fieldName]))
				unset($allowedFields[$fieldName]);
		}

		foreach (array_keys($order) as $orderKey)
		{
			if (!isset($allowedFields[$orderKey]))
				unset($order[$orderKey]);
		}

		$regExp = '/^([!><=%?][><=%]?[<]?|)'.'('.implode('|', array_keys($allowedFields)).')'.'$/';
		foreach (array_keys($filter) as $filterKey)
		{
			$matches = array();
			if (!preg_match($regExp, $filterKey, $matches))
				unset($filter[$filterKey]);
		}
	}
}

class CCrmMeasureRestProxy extends CCrmRestProxyBase
{
	/**
	 * @return array
	 */
	protected function getFieldsInfo()
	{
		return Bitrix\Crm\Measure::getFieldsInfo();
	}

	protected function innerAdd(&$fields, &$errors, array $params = null)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if (!CModule::IncludeModule('catalog'))
		{
			$errors[] = 'The Commercial Catalog module is not installed.';
			return false;
		}

		$userPermissions = CCrmAuthorizationHelper::GetUserPermissions();
		if (!CCrmAuthorizationHelper::CheckConfigurationUpdatePermission($userPermissions))
		{
			$errors[] = 'Access denied.';
			return false;
		}
		unset($userPermissions);

		$code = isset($fields['CODE']) ? trim($fields['CODE']) : '';
		if($code === '')
		{
			$errors[] = 'Please specify a code for the unit of measurement. The code should be a positive integer number.';
			return false;
		}
		elseif(preg_match('/^[0-9]+$/', $code) !== 1)
		{
			$errors[] = 'The CODE of unit of measurement can include only numbers.';
			return false;
		}
		else
		{
			$code = (int)$code;
		}

		$title = isset($fields['MEASURE_TITLE']) ? trim($fields['MEASURE_TITLE']) : '';
		if($title == '')
		{
			$errors[] = 'Please provide the name for the unit of measurement.';
			return false;
		}

		$result = CCatalogMeasure::getList(array(), array('=CODE' => $code));
		if(is_array($result->Fetch()))
		{
			$errors[] = 'A unit of measurement with the CODE "'.$code.'" already exists.';
			return false;
		}
		else
		{
			$result = CCatalogMeasure::add($fields);
			if($result <= 0)
			{
				if($exception = $APPLICATION->GetException())
					$errors[] = $exception->GetString();
				else
					$errors[] = 'Unknown error when creating unit of measurement.';
				return false;
			}
		}

		return $result;
	}
	protected function innerGet($ID, &$errors)
	{
		if (!CModule::IncludeModule('catalog'))
		{
			$errors[] = 'The Commercial Catalog module is not installed.';
			return false;
		}

		$userPermissions = CCrmAuthorizationHelper::GetUserPermissions();
		if (!CCrmAuthorizationHelper::CheckConfigurationReadPermission($userPermissions))
		{
			$errors[] = 'Access denied.';
			return false;
		}
		unset($userPermissions);

		$res = CCatalogMeasure::getList(array(), array('=ID' => $ID), false, false, array());
		$result = $res ? $res->Fetch() : null;
		if(!is_array($result))
		{
			$errors[] = 'Not found';
			return false;
		}

		return $result;
	}
	protected function innerGetList($order, $filter, $select, $navigation, &$errors)
	{
		if (!CModule::IncludeModule('catalog'))
		{
			$errors[] = 'The Commercial Catalog module is not installed.';
			return false;
		}

		$userPermissions = CCrmAuthorizationHelper::GetUserPermissions();
		if (!CCrmAuthorizationHelper::CheckConfigurationReadPermission($userPermissions))
		{
			$errors[] = 'Access denied.';
			return false;
		}
		unset($userPermissions);

		return CCatalogMeasure::getList($order, $filter, false, $navigation, $select);
	}
	protected function innerUpdate($ID, &$fields, &$errors, array $params = null)
	{
		/** @global CMain $APPLICATION */
		global $APPLICATION;

		if (!CModule::IncludeModule('catalog'))
		{
			$errors[] = 'The Commercial Catalog module is not installed.';
			return false;
		}

		$userPermissions = CCrmAuthorizationHelper::GetUserPermissions();
		if (!CCrmAuthorizationHelper::CheckConfigurationUpdatePermission($userPermissions))
		{
			$errors[] = 'Access denied.';
			return false;
		}
		unset($userPermissions);

		$result = CCatalogMeasure::update($ID, $fields);
		if($result !== $ID)
		{
			if($exception = $APPLICATION->GetException())
				$errors[] = $exception->GetString();
			else
				$errors[] = 'Unknown error when updating unit of measurement.';
			return false;
		}

		return true;
	}
	protected function innerDelete($ID, &$errors, array $params = null)
	{
		if (!CModule::IncludeModule('catalog'))
		{
			$errors[] = 'The Commercial Catalog module is not installed.';
			return false;
		}

		$userPermissions = CCrmAuthorizationHelper::GetUserPermissions();
		if (!CCrmAuthorizationHelper::CheckConfigurationUpdatePermission($userPermissions))
		{
			$errors[] = 'Access denied.';
			return false;
		}
		unset($userPermissions);

		$result = CCatalogMeasure::delete($ID);
		if($result !== true)
			$errors[] = 'Error when deleting unit of measurement.';

		return $result;
	}
}

class CCrmWebformRestProxy implements ICrmRestProxy
{
	/** @var CRestServer|null  */
	private $server = null;

	/**
	 * Get REST-server
	 * @return CRestServer
	 */
	public function getServer()
	{
		return $this->server;
	}

	/**
	 * Set REST-server
	 * @param CRestServer $server
	 */
	public function setServer(CRestServer $server)
	{
		$this->server = $server;
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if ($name === 'CONFIGURATION')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if($nameSuffix === 'GET')
			{
				return array('URL' => CCrmUrlUtil::ToAbsoluteUrl(Bitrix\Crm\WebForm\Manager::getUrl()));
			}
		}
		throw new RestException("Resource '{$name}' is not supported in current context.");
	}
}

class CCrmSiteButtonRestProxy implements ICrmRestProxy
{
	/** @var CRestServer|null  */
	private $server = null;

	/**
	 * Get REST-server
	 * @return CRestServer
	 */
	public function getServer()
	{
		return $this->server;
	}

	/**
	 * Set REST-server
	 * @param CRestServer $server
	 */
	public function setServer(CRestServer $server)
	{
		$this->server = $server;
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if ($name === 'CONFIGURATION')
		{
			$nameSuffix = strtoupper(!empty($nameDetails) ? implode('_', $nameDetails) : '');
			if($nameSuffix === 'GET')
			{
				return array('URL' => CCrmUrlUtil::ToAbsoluteUrl(Bitrix\Crm\SiteButton\Manager::getUrl()));
			}
		}
		throw new RestException("Resource '{$name}' is not supported in current context.");
	}
}
class CCrmAutomationRestProxy implements ICrmRestProxy
{
	/** @var CRestServer|null  */
	private $server = null;

	/**
	 * Get REST-server
	 * @return CRestServer
	 */
	public function getServer()
	{
		return $this->server;
	}

	/**
	 * Set REST-server
	 * @param CRestServer $server
	 */
	public function setServer(CRestServer $server)
	{
		$this->server = $server;
	}

	public function processMethodRequest($name, $nameDetails, $arParams, $nav, $server)
	{
		$name = strtoupper($name);
		if ($name === 'TRIGGER')
		{
			if (isset($arParams['target']))
			{
				$pairs = explode('_', $arParams['target']);
				if (count($pairs) > 1)
				{
					$entityTypeId = \CCrmOwnerType::ResolveID($pairs[0]);
					$entityId = (int)$pairs[1];

					if ($entityTypeId && $entityId)
					{
						if (\Bitrix\Crm\Automation\Trigger\WebHookTrigger::canExecute($entityTypeId, $entityId))
						{
							$data = array();
							if (isset($arParams['code']))
							{
								$data['code'] = (string)$arParams['code'];
							}

							\Bitrix\Crm\Automation\Trigger\WebHookTrigger::execute(array(array(
								'OWNER_TYPE_ID' => $entityTypeId,
								'OWNER_ID' => $entityId
							)), $data);
						}
						else
							throw new AccessException('There is no permissions to update the entity.');
					}
					else
						throw new RestException("Target is not found.");
				}
				else
					throw new RestException("Incorrect target format.");
			}
			else
				throw new RestException("Target is not set.");

			return true;
		}
		throw new RestException("Resource '{$name}' is not supported in current context.");
	}
}
