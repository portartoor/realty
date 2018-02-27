<?
if (!CModule::IncludeModule('bizproc'))
	return;

IncludeModuleLangFile(dirname(__FILE__)."/crm_document.php");

use Bitrix\Crm\Category\DealCategory;

class CCrmDocumentDeal extends CCrmDocument
	implements IBPWorkflowDocument
{
	static public function GetDocumentFields($documentType)
	{
		$arDocumentID = self::GetDocumentInfo($documentType.'_0');
		if (empty($arDocumentID))
			throw new CBPArgumentNullException('documentId');

		$arResult = self::getEntityFields($arDocumentID['TYPE']);

		return $arResult;
	}

	public static function getEntityFields($entityType)
	{
		\Bitrix\Main\Localization\Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].BX_ROOT.'/components/bitrix/crm.'.
			strtolower($entityType).'.edit/component.php');

		$printableFieldNameSuffix = ' ('.GetMessage('CRM_FIELD_BP_TEXT').')';
		$emailFieldNameSuffix = ' ('.GetMessage('CRM_FIELD_BP_EMAIL').')';

		$arResult = array(
			'ID' => array(
				'Name' => GetMessage('CRM_FIELD_ID'),
				'Type' => 'int',
				'Filterable' => true,
				'Editable' => false,
				'Required' => false,
			),
			'TITLE' => array(
				'Name' => GetMessage('CRM_FIELD_TITLE_DEAL'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => true,
			),
			'OPPORTUNITY' => array(
				'Name' => GetMessage('CRM_FIELD_OPPORTUNITY'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'CURRENCY_ID' => array(
				'Name' => GetMessage('CRM_FIELD_CURRENCY_ID'),
				'Type' => 'select',
				'Options' => CCrmCurrencyHelper::PrepareListItems(),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'OPPORTUNITY_ACCOUNT' => array(
				'Name' => GetMessage('CRM_FIELD_OPPORTUNITY_ACCOUNT'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'ACCOUNT_CURRENCY_ID' => array(
				'Name' => GetMessage('CRM_FIELD_ACCOUNT_CURRENCY_ID'),
				'Type' => 'select',
				'Options' => CCrmCurrencyHelper::PrepareListItems(),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'PROBABILITY' => array(
				'Name' => GetMessage('CRM_FIELD_PROBABILITY'),
				'Type' => 'string',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'ASSIGNED_BY_ID' => array(
				'Name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID'),
				'Type' => 'user',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'ASSIGNED_BY_PRINTABLE' => array(
				'Name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID').$printableFieldNameSuffix,
				'Type' => 'string',
				'Filterable' => false,
				'Editable' => false,
				'Required' => false,
			),
			'ASSIGNED_BY_EMAIL' => array(
				'Name' => GetMessage('CRM_FIELD_ASSIGNED_BY_ID').$emailFieldNameSuffix,
				'Type' => 'string',
				'Filterable' => false,
				'Editable' => false,
				'Required' => false,
			),
			'CATEGORY_ID' => array(
				'Name' => GetMessage('CRM_FIELD_CATEGORY_ID'),
				'Type' => 'select',
				'Options' => DealCategory::getSelectListItems(true),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false
			),
			'CATEGORY_ID_PRINTABLE' => array(
				'Name' => GetMessage('CRM_FIELD_CATEGORY_ID').$printableFieldNameSuffix,
				'Type' => 'string',
				'Filterable' => false,
				'Editable' => false,
				'Required' => false,
			),
			'STAGE_ID' => array(
				'Name' => GetMessage('CRM_FIELD_STAGE_ID'),
				'Type' => 'select',
				'Options' => DealCategory::getFullStageList(),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
				'Settings' => array('Groups' => DealCategory::getStageGroupInfos())
			),
			'STAGE_ID_PRINTABLE' => array(
				'Name' => GetMessage('CRM_FIELD_STAGE_ID').$printableFieldNameSuffix,
				'Type' => 'string',
				'Filterable' => false,
				'Editable' => false,
				'Required' => false,
			),
			'CLOSED' => array(
				'Name' => GetMessage('CRM_FIELD_CLOSED'),
				'Type' => 'bool',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'TYPE_ID' => array(
				'Name' => GetMessage('CRM_FIELD_TYPE_ID'),
				'Type' => 'select',
				'Options' => CCrmStatus::GetStatusListEx('DEAL_TYPE'),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'COMMENTS' => array(
				'Name' => GetMessage('CRM_FIELD_COMMENTS'),
				'Type' => 'text',
				'Filterable' => false,
				'Editable' => true,
				'Required' => false,
			),
			'BEGINDATE' => array(
				'Name' => GetMessage('CRM_FIELD_BEGINDATE'),
				'Type' => 'datetime',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'CLOSEDATE' => array(
				'Name' => GetMessage('CRM_FIELD_CLOSEDATE'),
				'Type' => 'datetime',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'EVENT_DATE' => array(
				'Name' => GetMessage('CRM_FIELD_EVENT_DATE'),
				'Type' => 'datetime',
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'EVENT_ID' => array(
				'Name' => GetMessage('CRM_FIELD_EVENT_ID'),
				'Type' => 'select',
				'Options' => CCrmStatus::GetStatusListEx('EVENT_TYPE'),
				'Filterable' => true,
				'Editable' => true,
				'Required' => false,
			),
			'EVENT_DESCRIPTION' => array(
				'Name' => GetMessage('CRM_FIELD_EVENT_DESCRIPTION'),
				'Type' => 'text',
				'Filterable' => false,
				'Editable' => true,
				'Required' => false,
			),
			"OPENED" => array(
				"Name" => GetMessage("CRM_FIELD_OPENED"),
				"Type" => "bool",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"LEAD_ID" => array(
				"Name" => GetMessage("CRM_FIELD_LEAD_ID"),
				"Type" => "int",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"ORIGINATOR_ID" => array(
				"Name" => GetMessage("CRM_FIELD_ORIGINATOR_ID"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"ORIGIN_ID" => array(
				"Name" => GetMessage("CRM_FIELD_ORIGIN_ID"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
			),
			"CONTACT_ID" => array(
				"Name" => GetMessage("CRM_FIELD_CONTACT_ID"),
				"Type" => "UF:crm",
				"Options" => array('CONTACT' => 'Y'),
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
				"Multiple" => false,
			),
			"CONTACT_IDS" => array(
				"Name" => GetMessage("CRM_FIELD_CONTACT_IDS"),
				"Type" => "string",
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
				"Multiple" => true,
			),
			"COMPANY_ID" => array(
				"Name" => GetMessage("CRM_FIELD_COMPANY_ID"),
				"Type" => "UF:crm",
				"Options" => array('COMPANY' => 'Y'),
				"Filterable" => true,
				"Editable" => true,
				"Required" => false,
				"Multiple" => false,
			),
			"DATE_CREATE" => array(
				"Name" => GetMessage("CRM_DEAL_EDIT_FIELD_DATE_CREATE"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
			"DATE_MODIFY" => array(
				"Name" => GetMessage("CRM_DEAL_EDIT_FIELD_DATE_MODIFY"),
				"Type" => "datetime",
				"Filterable" => true,
				"Editable" => false,
				"Required" => false,
			),
		);

		global $USER_FIELD_MANAGER;
		$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, 'CRM_DEAL');
		$CCrmUserType->AddBPFields($arResult, array('PRINTABLE_SUFFIX' => GetMessage("CRM_FIELD_BP_TEXT")));

		return $arResult;
	}

	static public function PrepareDocument(array &$arFields)
	{
		$categoryID = isset($arFields['CATEGORY_ID']) ? (int)$arFields['CATEGORY_ID'] : 0;
		$arFields['CATEGORY_ID_PRINTABLE'] = DealCategory::getName($categoryID);

		$stageID = isset($arFields['STAGE_ID']) ? $arFields['STAGE_ID'] : '';
		$arFields['STAGE_ID_PRINTABLE'] = DealCategory::getStageName($stageID, $categoryID);

		$arFields['CONTACT_IDS'] = \Bitrix\Crm\Binding\DealContactTable::getDealContactIDs($arFields['ID']);
	}

	static public function CreateDocument($parentDocumentId, $arFields)
	{
		if(!is_array($arFields))
		{
			throw new Exception("Entity fields must be array");
		}

		global $DB;
		$arDocumentID = self::GetDocumentInfo($parentDocumentId);
		if ($arDocumentID == false)
			$arDocumentID['TYPE'] = $parentDocumentId;

		$arDocumentFields = self::GetDocumentFields($arDocumentID['TYPE']);

		$arKeys = array_keys($arFields);
		foreach ($arKeys as $key)
		{
			if (!array_key_exists($key, $arDocumentFields))
			{
				//Fix for issue #40374
				unset($arFields[$key]);
				continue;
			}

			$arFields[$key] = (is_array($arFields[$key]) && !CBPHelper::IsAssociativeArray($arFields[$key])) ? $arFields[$key] : array($arFields[$key]);

			if ($arDocumentFields[$key]["Type"] == "user")
			{
				$ar = array();
				foreach ($arFields[$key] as $v1)
				{
					if (substr($v1, 0, strlen("user_")) == "user_")
					{
						$ar[] = substr($v1, strlen("user_"));
					}
					else
					{
						$a1 = self::GetUsersFromUserGroup($v1, "DEAL_0");
						foreach ($a1 as $a11)
							$ar[] = $a11;
					}
				}

				$arFields[$key] = $ar;
			}
			elseif ($arDocumentFields[$key]["Type"] == "select" && substr($key, 0, 3) == "UF_")
			{
				self::InternalizeEnumerationField('CRM_DEAL', $arFields, $key);
			}
			elseif ($arDocumentFields[$key]["Type"] == "file")
			{
				$arFileOptions = array('ENABLE_ID' => true);
				foreach ($arFields[$key] as &$value)
				{
					//Issue #40380. Secure URLs and file IDs are allowed.
					$file = false;
					CCrmFileProxy::TryResolveFile($value, $file, $arFileOptions);
					$value = $file;
				}
				unset($value);
			}
			elseif ($arDocumentFields[$key]["Type"] == "S:HTML")
			{
				foreach ($arFields[$key] as &$value)
				{
					$value = array("VALUE" => $value);
				}
				unset($value);
			}

			if (!$arDocumentFields[$key]["Multiple"] && is_array($arFields[$key]))
			{
				if (count($arFields[$key]) > 0)
				{
					$a = array_values($arFields[$key]);
					$arFields[$key] = $a[0];
				}
				else
				{
					$arFields[$key] = null;
				}
			}
		}

		if(isset($arFields['COMMENTS']))
		{
			if(preg_match('/<[^>]+[\/]?>/i', $arFields['COMMENTS']) === 1)
			{
				$arFields['COMMENTS'] = htmlspecialcharsbx($arFields['COMMENTS']);
			}
			$arFields['COMMENTS'] = str_replace(array("\r\n", "\r", "\n"), "<br>", $arFields['COMMENTS']);
		}

		//region Category & Stage
		if(isset($arFields['STAGE_ID']))
		{
			if($arFields['STAGE_ID'] === '')
			{
				unset($arFields['STAGE_ID']);
			}
			else
			{
				$stageID = $arFields['STAGE_ID'];
				$stageCategoryID = DealCategory::resolveFromStageID($stageID);
				if(!isset($arFields['CATEGORY_ID']))
				{
					$arFields['CATEGORY_ID'] = $stageCategoryID;
				}
				else
				{
					$categoryID = (int)$arFields['CATEGORY_ID'];
					if($categoryID !== $stageCategoryID)
					{
						throw new Exception(
							GetMessage(
								'CRM_DOCUMENT_DEAL_STAGE_MISMATCH_ERROR',
								array(
									'#CATEGORY#' => DealCategory::getName($categoryID),
									'#TARG_CATEGORY#' => DealCategory::getName($stageCategoryID),
									'#TARG_STAGE#' => DealCategory::getStageName($stageID, $stageCategoryID)
								)
							)
						);
					}
				}
			}
		}
		//endregion

		$DB->StartTransaction();

		$CCrmEntity = new CCrmDeal(false);
		$id = $CCrmEntity->Add(
			$arFields,
			true,
			array('REGISTER_SONET_EVENT' => true)
		);

		if (!$id || $id <= 0)
		{
			$DB->Rollback();
			throw new Exception($CCrmEntity->LAST_ERROR);
		}

		if (COption::GetOptionString("crm", "start_bp_within_bp", "N") == "Y")
		{
			$CCrmBizProc = new CCrmBizProc('DEAL');
			if (false === $CCrmBizProc->CheckFields(false, true))
				throw new Exception($CCrmBizProc->LAST_ERROR);

			if ($id && $id > 0 && !$CCrmBizProc->StartWorkflow($id))
			{
				$DB->Rollback();
				throw new Exception($CCrmBizProc->LAST_ERROR);
			}
		}

		//Region automation
		\Bitrix\Crm\Automation\Factory::runOnAdd(\CCrmOwnerType::Deal, $id);
		//End region

		if ($id && $id > 0)
			$DB->Commit();

		return $id;
	}

	static public function UpdateDocument($documentId, $arFields)
	{
		global $DB;

		$arDocumentID = self::GetDocumentInfo($documentId);
		if (empty($arDocumentID))
			throw new CBPArgumentNullException('documentId');

		$dbDocumentList = CCrmDeal::GetListEx(
			array(),
			array('ID' => $arDocumentID['ID'], 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('ID', 'CATEGORY_ID', 'STAGE_ID')
		);

		$arPresentFields = $dbDocumentList->Fetch();
		if (!is_array($arPresentFields))
			throw new Exception(GetMessage('CRM_DOCUMENT_ELEMENT_IS_NOT_FOUND'));

		$arDocumentFields = self::GetDocumentFields($arDocumentID['TYPE']);

		$arKeys = array_keys($arFields);
		foreach ($arKeys as $key)
		{
			if (!array_key_exists($key, $arDocumentFields))
			{
				//Fix for issue #40374
				unset($arFields[$key]);
				continue;
			}

			$arFields[$key] = (is_array($arFields[$key]) && !CBPHelper::IsAssociativeArray($arFields[$key])) ? $arFields[$key] : array($arFields[$key]);

			if ($arDocumentFields[$key]["Type"] == "user")
			{
				$ar = array();
				foreach ($arFields[$key] as $v1)
				{
					if (substr($v1, 0, strlen("user_")) == "user_")
					{
						$ar[] = substr($v1, strlen("user_"));
					}
					else
					{
						$a1 = self::GetUsersFromUserGroup($v1, $documentId);
						foreach ($a1 as $a11)
							$ar[] = $a11;
					}
				}

				$arFields[$key] = $ar;
			}
			elseif ($arDocumentFields[$key]["Type"] == "select" && substr($key, 0, 3) == "UF_")
			{
				self::InternalizeEnumerationField('CRM_DEAL', $arFields, $key);
			}
			elseif ($arDocumentFields[$key]["Type"] == "file")
			{
				$arFileOptions = array('ENABLE_ID' => true);
				foreach ($arFields[$key] as &$value)
				{
					//Issue #40380. Secure URLs and file IDs are allowed.
					$file = false;
					CCrmFileProxy::TryResolveFile($value, $file, $arFileOptions);
					$value = $file;
				}
				unset($value);
			}
			elseif ($arDocumentFields[$key]["Type"] == "S:HTML")
			{
				foreach ($arFields[$key] as &$value)
				{
					$value = array("VALUE" => $value);
				}
				unset($value);
			}

			if (!$arDocumentFields[$key]["Multiple"] && is_array($arFields[$key]))
			{
				if (count($arFields[$key]) > 0)
				{
					$a = array_values($arFields[$key]);
					$arFields[$key] = $a[0];
				}
				else
				{
					$arFields[$key] = null;
				}
			}
		}

		if(isset($arFields['COMMENTS']) && $arFields['COMMENTS'] !== '')
		{
			$arFields['COMMENTS'] = preg_replace("/[\r\n]+/".BX_UTF_PCRE_MODIFIER, "<br/>", $arFields['COMMENTS']);
		}

		//region Category & Stage
		$stageChanged = false;
		$categoryID = isset($arPresentFields['CATEGORY_ID']) ? (int)$arPresentFields['CATEGORY_ID'] : 0;
		if(isset($arFields['CATEGORY_ID']) && $arFields['CATEGORY_ID'] != $categoryID)
		{
			throw new Exception(GetMessage('CRM_DOCUMENT_DEAL_CATEGORY_CHANGE_ERROR'));
		}

		if(isset($arFields['STAGE_ID']))
		{
			if($arFields['STAGE_ID'] === '')
			{
				unset($arFields['STAGE_ID']);
			}
			else
			{
				$stageID = $arFields['STAGE_ID'];
				$stageCategoryID = DealCategory::resolveFromStageID($stageID);
				if($stageCategoryID !== $categoryID)
				{
					throw new Exception(
						GetMessage(
							'CRM_DOCUMENT_DEAL_STAGE_MISMATCH_ERROR',
							array(
								'#CATEGORY#' => DealCategory::getName($categoryID),
								'#TARG_CATEGORY#' => DealCategory::getName($stageCategoryID),
								'#TARG_STAGE#' => DealCategory::getStageName($stageID, $stageCategoryID)
							)
						)
					);
				}
				elseif ($arPresentFields['STAGE_ID'] !== $stageID)
					$stageChanged = true;
			}
		}
		//endregion

		if(empty($arFields))
		{
			return;
		}

		$DB->StartTransaction();

		$CCrmEntity = new CCrmDeal(false);
		$res = $CCrmEntity->Update(
			$arDocumentID['ID'],
			$arFields,
			true,
			true,
			array('REGISTER_SONET_EVENT' => true)
		);

		if (!$res)
		{
			$DB->Rollback();
			throw new Exception($CCrmEntity->LAST_ERROR);
		}

		if (COption::GetOptionString("crm", "start_bp_within_bp", "N") == "Y")
		{
			$CCrmBizProc = new CCrmBizProc('DEAL');
			if (false === $CCrmBizProc->CheckFields($arDocumentID['ID'], true))
				throw new Exception($CCrmBizProc->LAST_ERROR);

			if ($res && !$CCrmBizProc->StartWorkflow($arDocumentID['ID']))
			{
				$DB->Rollback();
				throw new Exception($CCrmBizProc->LAST_ERROR);
			}
		}
		//Region automation
		if ($stageChanged)
			\Bitrix\Crm\Automation\Factory::runOnStatusChanged(\CCrmOwnerType::Deal, $arDocumentID['ID']);
		//End region

		if ($res)
			$DB->Commit();
	}

	public function getDocumentName($documentId)
	{
		$arDocumentID = self::GetDocumentInfo($documentId);
		$dbDocumentList = CCrmDeal::GetListEx(
			array(),
			array('ID' => $arDocumentID['ID'], 'CHECK_PERMISSIONS' => 'N'),
			false,
			false,
			array('TITLE')
		);
		if ($arPresentFields = $dbDocumentList->Fetch())
		{
			return $arPresentFields['TITLE'];
		}

		return null;
	}
}
