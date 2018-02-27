<?php

namespace Bitrix\Voximplant;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Voximplant\Model\QueueTable;
use Bitrix\Voximplant\Model\QueueUserTable;

class Queue
{
	private $id;
	private $name;
	private $type;
	private $waitTime;
	private $noAnswerRule;

	/** @var int Id of the queue to forward call to in case of no answer */
	private $nextQueueId;

	private $forwardNumber;
	private $forwardLine;

	public function __construct($id = null)
	{
		$id = (int)$id;
		if($id > 0)
		{
			$fields = QueueTable::getById($id)->fetch();
			if(!$fields)
				throw new ArgumentException('Queue is not found');

			$this->setFromArray($fields);
		}
	}

	/**
	 * @return mixed
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return mixed
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param mixed $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return mixed
	 */
	public function getType()
	{
		return $this->type;
	}

	/**
	 * @param mixed $type
	 */
	public function setType($type)
	{
		$this->type = $type;
	}

	/**
	 * @return mixed
	 */
	public function getWaitTime()
	{
		return $this->waitTime;
	}

	/**
	 * @param mixed $waitTime
	 */
	public function setWaitTime($waitTime)
	{
		$this->waitTime = $waitTime;
	}

	/**
	 * @return mixed
	 */
	public function getNoAnswerRule()
	{
		return $this->noAnswerRule;
	}

	/**
	 * @param mixed $noAnswerRule
	 */
	public function setNoAnswerRule($noAnswerRule)
	{
		$this->noAnswerRule = $noAnswerRule;
	}

	/**
	 * @return int
	 */
	public function getNextQueueId()
	{
		return $this->nextQueueId;
	}

	/**
	 * @param int $nextQueueId
	 */
	public function setNextQueueId($nextQueueId)
	{
		$this->nextQueueId = $nextQueueId;
	}

	/**
	 * @return mixed
	 */
	public function getForwardNumber()
	{
		return $this->forwardNumber;
	}

	/**
	 * @param mixed $forwardNumber
	 */
	public function setForwardNumber($forwardNumber)
	{
		$this->forwardNumber = $forwardNumber;
	}

	public function toArray()
	{
		return array(
			'ID' => $this->id,
			'NAME' => $this->name,
			'TYPE' => $this->type,
			'WAIT_TIME' => $this->waitTime,
			'NO_ANSWER_RULE' => $this->noAnswerRule,
			'NEXT_QUEUE_ID' => $this->nextQueueId,
			'FORWARD_NUMBER' => $this->forwardNumber,
		);
	}

	public function setFromArray(array $fields)
	{
		if(isset($fields['ID']))
			$this->id = (int)$fields['ID'];

		if(isset($fields['NAME']))
			$this->name = $fields['NAME'];

		if(isset($fields['TYPE']))
			$this->type = $fields['TYPE'];

		if(isset($fields['WAIT_TIME']))
			$this->waitTime = (int)$fields['WAIT_TIME'];

		if(isset($fields['NO_ANSWER_RULE']))
			$this->noAnswerRule = $fields['NO_ANSWER_RULE'];

		if(isset($fields['NEXT_QUEUE_ID']))
			$this->nextQueueId = (int)$fields['NEXT_QUEUE_ID'];

		if(isset($fields['FORWARD_NUMBER']))
			$this->forwardNumber = $fields['FORWARD_NUMBER'];
	}

	public function persist()
	{
		if($this->id > 0)
		{
			QueueTable::update($this->id, array(
				'NAME' => $this->name,
				'TYPE' => $this->type,
				'WAIT_TIME' => $this->waitTime,
				'NO_ANSWER_RULE' => $this->noAnswerRule,
				'NEXT_QUEUE_ID' => $this->nextQueueId,
				'FORWARD_NUMBER' => $this->forwardNumber,
			));
		}
		else
		{
			$insertResult = QueueTable::add(array(
				'NAME' => $this->name,
				'TYPE' => $this->type,
				'WAIT_TIME' => $this->waitTime,
				'NO_ANSWER_RULE' => $this->noAnswerRule,
				'NEXT_QUEUE_ID' => $this->nextQueueId,
				'FORWARD_NUMBER' => $this->forwardNumber,
			));

			$this->id = $insertResult->getId();
		}
	}

	/**
	 * Returns id of the first active user in queue
	 * @return int|false
	 * @throws ArgumentException
	 */
	public function getFirstUserId($checkTimeman = false)
	{
		if($this->type == \CVoxImplantConfig::QUEUE_TYPE_STRICTLY)
		{
			$order = array('ID' => 'asc');
		}
		else
		{
			$order = array('LAST_ACTIVITY_DATE' => 'asc');
		}

		$cursor = QueueUserTable::getList(array(
			'select' => array('ID', 'USER_ID'),
			'filter' => array(
				'=QUEUE_ID' => $this->id,
				'=USER.ACTIVE' => 'Y'
			),
			'order' => $order,
		));

		while($row = $cursor->fetch())
		{
			$userId = (int)$row['USER_ID'];
			if ($checkTimeman && !\CVoxImplantUser::GetActiveStatusByTimeman($userId))
				continue;

			return $userId;
		}

		return false;
	}

	/**
	 * Updates user's last activity date to now()
	 * @param int $userId Id of the user
	 */
	public function touchUser($userId)
	{
		$userId = (int)$userId;

		$row = QueueUserTable::getList(array(
			'filter' => array(
				'=QUEUE_ID' => $this->id,
				'=USER_ID' => $userId
			)
		))->fetch();

		if($row)
		{
			$recordId = $row['ID'];

			QueueUserTable::update($recordId, array(
				'LAST_ACTIVITY_DATE' => new DateTime()
			));
			return true;
		}
		return false;
	}
}