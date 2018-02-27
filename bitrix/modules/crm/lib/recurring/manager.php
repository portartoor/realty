<?php
namespace Bitrix\Crm\Recurring;

use Bitrix\Crm\InvoiceRecurTable,
	Bitrix\Main\Type\Date,
	Bitrix\Main\Type\DateTime,
	Bitrix\Main\Localization\Loc,
	Bitrix\Main\Entity,
	Bitrix\Sale\PaySystem,
	Bitrix\Crm\Requisite\EntityLink,
	Bitrix\Main,
	Bitrix\Main\Result;

Loc::loadMessages(__FILE__);

class Manager
{
	/**
	 * Create recurring invoice
	 *
	 * @param array $invoiceFields
	 * @param array $recurParams
	 *
	 * @return Main\Result
	 * @throws \Exception
	 */
	public static function createInvoice(array $invoiceFields, array $recurParams)
	{
		$result = new Main\Result();
		$newInvoice = new \CCrmInvoice();
		$requisite = new \Bitrix\Crm\EntityRequisite();

		unset($invoiceFields['ID'], $invoiceFields['ACCOUNT_NUMBER']);
		$recurringParams = $recurParams['PARAMS'];

		try
		{
			$invoiceFields['DATE_BILL'] = new Date();
			$invoiceFields['RECURRING_ID'] = null;
			$invoiceFields['IS_RECURRING'] = 'Y';

			$requisiteEntityList = array();
			$mcRequisiteEntityList = array();

			$requisiteIdLinked = 0;
			$bankDetailIdLinked = 0;
			$mcRequisiteIdLinked = 0;
			$mcBankDetailIdLinked = 0;

			if (isset($invoiceFields['UF_COMPANY_ID']) && $invoiceFields['UF_COMPANY_ID'] > 0)
				$requisiteEntityList[] = array('ENTITY_TYPE_ID' => \CCrmOwnerType::Company, 'ENTITY_ID' => $invoiceFields['UF_COMPANY_ID']);
			if (isset($invoiceFields['UF_CONTACT_ID']) && $invoiceFields['UF_CONTACT_ID'] > 0)
				$requisiteEntityList[] = array('ENTITY_TYPE_ID' => \CCrmOwnerType::Contact, 'ENTITY_ID' => $invoiceFields['UF_CONTACT_ID']);
			if (isset($invoiceFields['UF_MYCOMPANY_ID']) && $invoiceFields['UF_MYCOMPANY_ID'] > 0)
				$mcRequisiteEntityList[] = array('ENTITY_TYPE_ID' => \CCrmOwnerType::Company, 'ENTITY_ID' => $invoiceFields['UF_MYCOMPANY_ID']);
			$requisiteInfoLinked = $requisite->getDefaultRequisiteInfoLinked($requisiteEntityList);

			if (is_array($requisiteInfoLinked))
			{
				if (isset($requisiteInfoLinked['REQUISITE_ID']))
					$requisiteIdLinked = (int)$requisiteInfoLinked['REQUISITE_ID'];
				if (isset($requisiteInfoLinked['BANK_DETAIL_ID']))
					$bankDetailIdLinked = (int)$requisiteInfoLinked['BANK_DETAIL_ID'];
			}
			$mcRequisiteInfoLinked = $requisite->getDefaultMyCompanyRequisiteInfoLinked($mcRequisiteEntityList);
			if (is_array($mcRequisiteInfoLinked))
			{
				if (isset($mcRequisiteInfoLinked['MC_REQUISITE_ID']))
					$mcRequisiteIdLinked = (int)$mcRequisiteInfoLinked['MC_REQUISITE_ID'];
				if (isset($mcRequisiteInfoLinked['MC_BANK_DETAIL_ID']))
					$mcBankDetailIdLinked = (int)$mcRequisiteInfoLinked['MC_BANK_DETAIL_ID'];
			}
			unset($requisite, $requisiteEntityList, $mcRequisiteEntityList, $requisiteInfoLinked, $mcRequisiteInfoLinked);

			$recalculate = false;
			$idRecurringInvoice = $newInvoice->Add($invoiceFields, $recalculate, SITE_ID, array('REGISTER_SONET_EVENT' => true, 'UPDATE_SEARCH' => true));

			if ($requisiteIdLinked > 0 || $mcRequisiteIdLinked > 0)
			{
				EntityLink::register(
					\CCrmOwnerType::Invoice, $idRecurringInvoice,
					$requisiteIdLinked, $bankDetailIdLinked,
					$mcRequisiteIdLinked, $mcBankDetailIdLinked
				);
			}

			if (!$idRecurringInvoice)
			{
				$result->addError(new Main\Error(Loc::getMessage("CRM_RECUR_INVOICE_ERROR_ADDITION")));
				return $result;
			}

			if (!($recurParams['NEXT_EXECUTION'] instanceof Date))
			{
				if ($recurParams['START_DATE'] instanceof Date)
					$recurParams['START_DATE'] = new Date($recurParams['START_DATE']);
				$recurParams['NEXT_EXECUTION'] = Calculator::getNextDate($recurringParams, $recurParams['START_DATE']);
			}

			if ($recurParams['IS_LIMIT'] != 'N')
			{
				$isActive = static::isActiveExecutionDate($recurParams);
				if (!$isActive)
				{
					$recurParams['NEXT_EXECUTION'] = null;
					$recurParams['ACTIVE'] = "N";
				}
				else
				{
					$recurParams['ACTIVE'] = "Y";
				}
			}
			else
			{
				$recurParams['ACTIVE'] = "Y";
			}

			$recurParams['EMAIL_ID'] = ((int)$recurParams['EMAIL_ID'] > 0) ? (int)$recurParams['EMAIL_ID'] : null;

			if (is_null((int)$recurParams['EMAIL_ID']))
			{
				$recurParams['SEND_BILL'] = 'N';
			}

			$recurParams['INVOICE_ID'] = $idRecurringInvoice;

			$r = InvoiceRecurTable::add($recurParams);

			if ($r->isSuccess())
			{
				static::initCheckAgent();

				$result->setData(
					array(
						"INVOICE_ID" => $idRecurringInvoice,
						"ID" => $r->getId()
					)
				);
			}
			else
			{
				$result->addErrors($r->getErrors());
			}
		}
		catch (Main\SystemException $exception)
		{
			$result->addError(new Main\Error($exception->getMessage(), $exception->getCode()));
		}

		return $result;
	}

	/**
	 * Update recurring invoice
	 *
	 * @param int $primary
	 * @param array $data
	 *
	 * @return Entity\UpdateResult
	 * @throws \Exception
	 */
	public static function updateRecurring($primary, array $data)
	{
		$primary = (int)$primary;
		if ($primary <= 0)
		{
			return false;
		}

		$data['NEXT_EXECUTION'] = null;

		$recur = InvoiceRecurTable::getById($primary);
		$recurData = $recur->fetch();

		if (!$recurData)
		{
			return false;
		}

		$data = array_merge($recurData, $data);

		$recurringParams = $data['PARAMS'];

		if (is_array($recurringParams))
		{
			$today = new Date();

			if ($data['START_DATE'] instanceof Date)
			{
				$startDay = $today->getTimestamp() > $data['START_DATE']->getTimestamp() ? $today : $data['START_DATE'];
			}
			else
			{
				$startDay = $today;
			}

			if ($data['LAST_EXECUTION'] instanceof Date)
			{
				if ($data['LAST_EXECUTION']->getTimestamp() >= $startDay->getTimestamp())
				{
					$startDay->add('+1 day');
				}
			}

			$data['NEXT_EXECUTION'] = Calculator::getNextDate($recurringParams, $startDay);
		}

		if ($data['IS_LIMIT'] !== 'N' || empty($data['NEXT_EXECUTION']))
		{
			if (static::isActiveExecutionDate($data))
			{
				$data['ACTIVE'] = 'Y';
			}
			else
			{
				$data['ACTIVE'] = 'N';
				$data['NEXT_EXECUTION'] = null;
			}
		}
		else
		{
			$data['ACTIVE'] = 'Y';
		}

		return InvoiceRecurTable::update($primary, $data);
	}

	/**
	 * @param $limit
	 *
	 * @return Main\Result
	 */
	public static function exposeTodayInvoices($limit = null)
	{
		$today = new Date();
		return static::exposeInvoices(
			array(
				'<=NEXT_EXECUTION' => $today,
				array(
					"LOGIC" => "OR",
					array("LAST_EXECUTION" => null),
					array("<LAST_EXECUTION" => $today)
				),
				'=ACTIVE' => "Y"
			),
			$limit
		);
	}

	/**
	 * Start controlling agent.
	 *
	 * @return string
	 */
	public static function initCheckAgent()
	{
		$agentData = \CAgent::GetList(
			array("ID"=>"DESC"),
			array(
				"MODULE_ID" => "crm",
				"NAME" => "\\".__CLASS__."::checkAgent();"
			)
		);

		$agent = $agentData->Fetch();

		if (!($agent))
		{
			$tomorrow = DateTime::createFromTimestamp(strtotime('tomorrow 00:01:00'));
			\CAgent::AddAgent("\\".__CLASS__."::checkAgent();", "crm", "N", 43200, "", "Y", $tomorrow->toString());
		}

		static::exposeAgent();

		return static::checkAgent();
	}

	/**
	 * Control of exposing agent.
	 *
	 * @return string
	 */
	public static function checkAgent()
	{
		$agentData = \CAgent::GetList(
			array("ID"=>"DESC"),
			array(
				"MODULE_ID" => "crm",
				"NAME" => "\\".__CLASS__."::exposeAgent();"
			)
		);

		if ($agent = $agentData->Fetch())
		{
			if ($agent['LAST_EXEC'] < $agent['NEXT_EXEC'])
			{
				$agentId = $agent['ID'];
			}
			else
			{
				\CAgent::Delete($agent['ID']);
			}
		}

		if (empty($agentId))
		{
			\CAgent::AddAgent("\\".__CLASS__."::exposeAgent();", "crm", "N", 60, "", "Y");
		}

		return "\\".__CLASS__."::checkAgent();";
	}


	/**
	 * Create new invoices in agent.
	 *
	 * @return string
	 */
	public static function exposeAgent()
	{
		global $USER;

		@set_time_limit(0);

		$today = new Date();
		$limit = Main\Config\Option::get('crm', 'day_limit_exposing_invoices', 10);

		$todayInvoices = InvoiceRecurTable::getList(
			array(
				'filter' => array(
					'<=NEXT_EXECUTION' => $today,
					array(
						"LOGIC" => "OR",
						array("LAST_EXECUTION" => null),
						array("<LAST_EXECUTION" => $today)
					),
					'=ACTIVE' => "Y"
				)
			)
		);

		$todayCount = count($todayInvoices->fetchAll());

		if ($todayCount > 0)
		{
			if (!(isset($USER) && $USER instanceof \CUser))
			{
				$USER = new \CUser();
			}

			static::exposeTodayInvoices($limit);
		}
		else
		{
			return '';
		}

		return "\\".__CLASS__."::exposeAgent();";
	}

	/**
	 * Create new invoices from recurring invoices. Invoices's selection is from InvoiceRecurTable.
	 *
	 * @param $filter
	 * @param $limit
	 *
	 * @return Main\Result
	 * @throws Main\ArgumentException
	 */
	public static function exposeInvoices($filter, $limit = null)
	{
		$result = new Main\Result();

		$idInvoicesList = array();
		$recurParamsList = array();
		$linkEntityList = array();
		$newInvoiceIds = array();
		$emailList = array();
		$emailData = array();

		$getParams['filter'] = $filter;		
		if ((int)$limit > 0)
		{
			$getParams['limit'] = (int)$limit;
		}
		
		$recurring = InvoiceRecurTable::getList($getParams);

		while ($recurData = $recurring->fetch())
		{
			$recurData['INVOICE_ID'] = (int)$recurData['INVOICE_ID'];
			$idInvoicesList[] = $recurData['INVOICE_ID'];
			$recurParamsList[$recurData['INVOICE_ID']] = $recurData;
		}

		if (empty($idInvoicesList))
		{
			return $result;
		}

		try
		{
			$newInvoice = new \CCrmInvoice(false);
			$today = new Date();
			$tomorrow = Date::createFromTimestamp(strtotime('tomorrow'));

			$linkData = EntityLink::getList(
				array(
					'filter' => array(
						'=ENTITY_TYPE_ID' => \CCrmOwnerType::Invoice,
						'=ENTITY_ID' => $idInvoicesList
					)
				)
			);

			while ($link = $linkData->fetch())
			{
				$linkEntityList[$link['ENTITY_ID']] = $link;
			}

			$idListChunks = array_chunk($idInvoicesList, 999);

			foreach ($idListChunks as $idList)
			{
				$products = array();
				$properties = array();

				$productRowData = \CCrmInvoice::GetProductRows($idList);

				foreach ($productRowData as $row)
				{
					$products[$row['ORDER_ID']][] = $row;
				}

				$propertiesRowData = \CCrmInvoice::getPropertiesList($idList);

				foreach ($propertiesRowData as $invoiceId => $row)
				{
					$properties[$invoiceId] = $row;
				}

				unset($row);

				$invoicesData = \CCrmInvoice::GetList(
					array(),
					array(
						"=ID" => $idList,
						"CHECK_PERMISSIONS" => 'N'
					)
				);

				while ($invoice = $invoicesData->Fetch())
				{
					$recurData = $recurParamsList[$invoice['ID']];
					$invoice['RECURRING_ID'] = $invoice['ID'];
					$newInvoiceProperties = array();
					$invoice['IS_RECURRING'] = 'N';
					$invoice['PRODUCT_ROWS'] = $products[$invoice['ID']];

					if(is_array($properties[$invoice['ID']]))
					{
						foreach($properties[$invoice['ID']] as $invoiceProperty)
						{
							$value = $invoiceProperty['VALUE'];
							$newInvoiceProperties[$value['ORDER_PROPS_ID']] = $value['VALUE'];
						}
						$invoice['INVOICE_PROPERTIES'] = $newInvoiceProperties;
					}

					$recurParam = $recurData['PARAMS'];

					$invoice['DATE_BILL'] = $today;
					$invoiceTemplateId = $invoice['ID'];
					unset($invoice['ID'], $invoice['ACCOUNT_NUMBER'], $invoice['DATE_STATUS'], $invoice['DATE_UPDATE'], $invoice['DATE_INSERT']);
					$reCalculate = false;
					$resultInvoice = $newInvoice->Add($invoice, $reCalculate, $invoice['LID'], array('REGISTER_SONET_EVENT' => true, 'UPDATE_SEARCH' => true));

					if ($resultInvoice)
					{
						$requisiteInvoice = $linkEntityList[$invoiceTemplateId];

						EntityLink::register(
							\CCrmOwnerType::Invoice,
							$resultInvoice,
							$requisiteInvoice['REQUISITE_ID'],
							$requisiteInvoice['BANK_DETAIL_ID'],
							$requisiteInvoice['MC_REQUISITE_ID'],
							$requisiteInvoice['MC_BANK_DETAIL_ID']
						);

						$newInvoiceIds[] = $resultInvoice;

						$nextData = Calculator::getNextDate($recurParam, $tomorrow);
						
						$updateData = array(
							"LAST_EXECUTION" => $today,
							"COUNTER_REPEAT" => (int)$recurData['COUNTER_REPEAT'] + 1,
							"NEXT_EXECUTION" => $nextData
						);

						if ($recurData['IS_LIMIT'] !== 'N')
						{
							if (static::isActiveExecutionDate(array_merge($updateData, $recurData, $recurParam)))
							{
								$updateData['ACTIVE'] = 'Y';
							}
							else
							{
								$updateData['ACTIVE'] = 'N';
								$updateData['NEXT_EXECUTION'] = null;
							}
						}
						else
						{
							$updateData['ACTIVE'] = 'Y';
						}

						if ($recurData['SEND_BILL'] === 'Y' && (int)$recurData['EMAIL_ID'] > 0)
						{
							$emailList[] = (int)$recurData['EMAIL_ID'];
							$emailData[$resultInvoice] = array(
								'EMAIL_ID' => (int)$recurData['EMAIL_ID'],
								'TEMPLATE_ID' => (int)$recurParam['EMAIL_TEMPLATE_ID'] ? (int)$recurParam['EMAIL_TEMPLATE_ID'] : null,
								'INVOICE_ID' => $resultInvoice
							);
						}

						InvoiceRecurTable::update($recurData['ID'], $updateData);
					}
					else
					{
						$result->addError(new Main\Error(Loc::getMessage("CRM_RECUR_INVOICE_ERROR_GET_EMPTY")));
					}
				}
			}

			unset($idListChunks, $idList);

			if (!empty($emailList))
			{
				$emails = array();

				$emailFieldsData = \CCrmFieldMulti::GetListEx(
					array('ID' => 'asc'),
					array(
						'=ID' => $emailList,
						'=TYPE_ID' => 'EMAIL'
					)
				);
				while ($email = $emailFieldsData->Fetch())
				{
					$emails[$email['ID']] = $email;
				}

				if (!empty($emails))
				{
					$idListChunks = array_chunk(array_keys($emailData), 999);
					$mail = new Mail();
					foreach ($idListChunks as $idList)
					{
						$newInvoiceData = \CCrmInvoice::GetList(
							array(),
							array(
								"=ID" => $idList,
								"CHECK_PERMISSIONS" => 'N'
							)
						);

						while($invoice = $newInvoiceData->Fetch())
						{
							$emailId = $emailData[$invoice['ID']]['EMAIL_ID'];
							$templateId = $emailData[$invoice['ID']]['TEMPLATE_ID'];
							$r = $mail->setData($invoice, $emails[$emailId], $templateId);

							if ($r->isSuccess())
							{
								$mailResult = $mail->sendInvoice();
								if (!($mailResult->isSuccess()))
								{
									$result->addErrors($mailResult->getErrors());
								}
							}
							else
							{
								$result->addErrors($r->getErrors());
							}
						}
					}
				}
			}
		}
		catch (Main\SystemException $exception)
		{
			$result->addError(new Main\Error($exception->getMessage(), $exception->getCode()));
		}

		if (!empty($newInvoiceIds))
		{
			$result->setData(array("ID" => $newInvoiceIds));
		}

		return $result;
	}

	/**
	 * @param $invoiceId
	 * @param string $reason
	 *
	 * @throws Main\ArgumentException
	 * @throws \Exception
	 */
	public static function cancel($invoiceId, $reason = "")
	{
		$invoiceId = (int)$invoiceId;
		if ($invoiceId <= 0)
		{
			throw new Main\ArgumentException('Wrong invoice id.');
		}

		$invoice =  new \CCrmInvoice();
		$invoice->Update(
			$invoiceId,
			array(
				"CANCELED" => "Y",
				"DATE_CANCELED" => new DateTime(),
				"REASON_CANCELED" => $reason
			)
		);

		$recurringData = InvoiceRecurTable::getList(
			array(
				"filter" => array("=INVOICE_ID" => $invoiceId)
			)
		);

		while ($recurring = $recurringData->fetch())
		{
			static::updateRecurring(
				$recurring['ID'],
				array(
					"ACTIVE" => "N",
					"NEXT_EXECUTION" => null
				)
			);
		}
	}

	/**
	 * Check date of next invoicing by params.
	 *
	 * @param $params
	 * @return bool
	 */
	public static function isActiveExecutionDate($params)
	{
		$nextTimeStamp = ($params['NEXT_EXECUTION'] instanceof Date) ? $params['NEXT_EXECUTION']->getTimestamp() : 0;
		$endTimeStamp = ($params['LIMIT_DATE'] instanceof Date) ? $params['LIMIT_DATE']->getTimestamp() : 0;

		if ($params['IS_LIMIT'] === "T")
			return (int)$params['LIMIT_REPEAT'] > (int)$params['COUNTER_REPEAT'];
		elseif ($params['IS_LIMIT'] === "D")
			return $nextTimeStamp < $endTimeStamp;

		return false;
	}

	/**
	 * Deactivate recurring invoices
	 *
	 * @param $invoiceId
	 *
	 * @return Entity\UpdateResult
	 * @throws Main\ArgumentException
	 * @throws \Exception
	 */
	public static function deactivate($invoiceId)
	{
		if ((int)$invoiceId > 0)
		{
			$invoiceId = (int)$invoiceId;
		}
		else
		{
			throw new Main\ArgumentException('Wrong invoice id.');
		}
		
		return InvoiceRecurTable::update(
			$invoiceId,
			array(
				"ACTIVE" => 'N', 
				"NEXT_EXECUTION" => null
			)
		);
	}

	/**
	 * Activate recurring invoices
	 *
	 * @param $invoiceId
	 *
	 * @return Entity\UpdateResult|Result
	 * @throws Main\ArgumentException
	 * @throws \Exception
	 */
	public static function activate($invoiceId)
	{
		$result = new Result();
		
		if ((int)$invoiceId > 0)
		{
			$invoiceId = (int)$invoiceId;
		}
		else
		{
			$result->addError(new Main\Error(Loc::getMessage('CRM_RECUR_WRONG_ID')));
			return $result;
		}	
		
		$invoiceData = InvoiceRecurTable::getList(
			array(
				"filter" => array("INVOICE_ID" => $invoiceId)
			)
		);
		if ($invoice = $invoiceData->fetch())
		{
			$recurringParams = $invoice['PARAMS'];
			$invoice['NEXT_EXECUTION'] = Calculator::getNextDate($recurringParams);
			$invoice["COUNTER_REPEAT"] = (int)$invoice["COUNTER_REPEAT"] + 1;
			$isActive = static::isActiveExecutionDate($invoice);
			if ($isActive)
			{
				$result = InvoiceRecurTable::update(
					$invoiceId,
					array(
						"ACTIVE" => 'Y',
						"NEXT_EXECUTION" => $invoice['NEXT_EXECUTION'],
						"COUNTER_REPEAT" => $invoice['COUNTER_REPEAT']
					)
				);
			}
			else
			{
				if ((int)$invoice['COUNTER_REPEAT'] > (int)$invoice['LIMIT_REPEAT'])
				{
					$result->addError(new Main\Error(Loc::getMessage('CRM_RECUR_ACTIVATE_LIMIT_REPEAT')));
				}
				else
				{
					$result->addError(new Main\Error(Loc::getMessage('CRM_RECUR_ACTIVATE_LIMIT_DATA')));
				}
			}
		}
		else
		{
			$result->addError(new Main\Error(Loc::getMessage('CRM_RECUR_WRONG_ID')));
		}
		return $result;
	}
}