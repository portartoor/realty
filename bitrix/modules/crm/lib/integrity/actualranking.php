<?php
namespace Bitrix\Crm\Integrity;

use Bitrix\Crm\DealTable;
use Bitrix\Crm\LeadTable;
use Bitrix\Crm\PhaseSemantics;
use Bitrix\Main\Entity\ExpressionField;
use Bitrix\Main\Entity\Query;

/**
 * Class ActualRanking
 * @package Bitrix\Crm\Integrity
 */
class ActualRanking
{
	/** @var integer Limit parameter for Query */
	protected $queryLimit = 50;

	/** @var bool Is list ranked */
	protected $isRanked = false;

	/** @var integer|null Entity type id */
	protected $entityTypeId;

	/** @var array List for rank */
	protected $list = array();

	/** @var integer|null Top entity id in ranked list */
	protected $entityId;

	/** @var integer|null Deal id of top entity in ranked list */
	protected $dealId;

	/** @var integer|null Lead(return customer type) id of top entity in ranked list. */
	protected $returnCustomerLeadId;

	protected function clearRuntime()
	{
		$this->list = array();
		$this->entityTypeId = null;

		$this->entityId = null;
		$this->dealId = null;
		$this->returnCustomerLeadId = null;
	}

	/**
	 * Get top entity id in ranked list.
	 *
	 * @return integer|null
	 */
	public function getEntityId()
	{
		return $this->entityId;
	}

	/**
	 * Get deal of top entity in ranked list.
	 *
	 * @return integer|null
	 */
	public function getDealId()
	{
		return $this->dealId;
	}

	/**
	 * Get lead(return customer type) id of top entity in ranked list.
	 *
	 * @return integer|null
	 */
	public function getReturnCustomerLeadId()
	{
		return $this->returnCustomerLeadId;
	}

	/**
	 * Get ranked list of entity ids.
	 *
	 * @return array
	 */
	public function getRankedList()
	{
		return $this->list;
	}

	/**
	 * Return true if list is ranked.
	 *
	 * @return bool
	 */
	public function isRanked()
	{
		return $this->isRanked;
	}


	/**
	 * Rank entity list.
	 *
	 * @param integer $entityTypeId Entity type id
	 * @param array $list List of entity ids
	 * @return $this
	 */
	public function rank($entityTypeId, array $list)
	{
		$this->clearRuntime();
		$this->entityTypeId = $entityTypeId;
		$this->list = $list;

		if (count($this->list) === 0)
		{
			return $this;
		}

		// ranking by deals
		$findDealIdOnly = !$this->entityId ? false : true;
		$this->rankByDeals($findDealIdOnly);

		// ranking by repeated leads
		$findLeadIdOnly = !$this->entityId ? false : true;
		$this->rankByReturnCustomerLeads($findLeadIdOnly);

		// other ranking
		// ...

		// default ranking
		if (!$this->entityId)
		{
			$this->rankByDefaults();
		}

		return $this;
	}

	protected function rankByDefaults()
	{
		if (!$this->list[0])
		{
			return;
		}

		$this->isRanked = true;
		$this->entityId = $this->list[0];
	}

	protected function rankByReturnCustomerLeads($findLeadIdOnly = false)
	{
		$query = LeadTable::query();
		$query->addFilter('=STATUS_SEMANTIC_ID', array(
			PhaseSemantics::PROCESS
		));
		$query->addFilter('=IS_RETURN_CUSTOMER', 'Y');
		$leadId = $this->rankByQuery($query, $findLeadIdOnly ? 1 : null);
		if (!$leadId)
		{
			return;
		}

		$this->isRanked = true;

		// set return customer lead id
		$this->returnCustomerLeadId = $leadId;
	}

	protected function rankByDeals($findDealIdOnly = false)
	{
		$query = DealTable::query();
		$query->addFilter('=STAGE_SEMANTIC_ID', array(
			PhaseSemantics::PROCESS
		));
		$dealId = $this->rankByQuery($query, $findDealIdOnly ? 1 : null);
		if (!$dealId)
		{
			return;
		}

		$this->isRanked = true;

		// set deal Id
		$this->dealId = $dealId;
	}

	protected function rankByQuery(Query $query, $limit = null)
	{
		switch ($this->entityTypeId)
		{
			case \CCrmOwnerType::Contact:
				$fieldName = 'CONTACT_ID';
				break;
			case \CCrmOwnerType::Company:
				$fieldName = 'COMPANY_ID';
				break;
			default:
				return null;
		}

		$queryId = null;
		$rankedList = array();
		$query->setSelect(array($fieldName, 'MAX_ID'));
		$query->addFilter('=' . $fieldName, $this->list);
		$query->registerRuntimeField(new ExpressionField('MAX_DATE_MODIFY', 'MAX(%s)', 'DATE_MODIFY'));
		$query->registerRuntimeField(new ExpressionField('MAX_DATE_CREATE', 'MAX(%s)', 'DATE_CREATE'));
		$query->registerRuntimeField(new ExpressionField('MAX_ID', 'MAX(%s)', 'ID'));
		$query->setOrder(array(
			'MAX_DATE_MODIFY' => 'DESC',
			'MAX_DATE_CREATE' => 'DESC',
			'MAX_ID' => 'DESC',
		));
		if ($limit)
		{
			$query->setLimit($limit);
		}

		$listDb = $query->exec();
		while ($item = $listDb->fetch())
		{
			$rankedList[] = $item[$fieldName];
			if (!$queryId)
			{
				$queryId = $item['MAX_ID'];
			}
		}

		$isRanked = $queryId > 0;

		if (!$isRanked)
		{
			return null;
		}

		// set entity id
		if (!$this->entityId)
		{
			$this->entityId = $rankedList[0];
		}

		if ($limit)
		{
			return $queryId;
		}

		$this->updateListByRankedList($rankedList);
		return $queryId;
	}

	protected function updateListByRankedList(array $rankedList)
	{
		foreach ($this->list as $entityId)
		{
			if (in_array($entityId, $rankedList))
			{
				continue;
			}

			$rankedList[] = $entityId;
		}

		$this->list = $rankedList;
	}
}