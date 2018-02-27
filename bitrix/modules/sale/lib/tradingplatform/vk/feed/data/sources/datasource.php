<?php

namespace Bitrix\Sale\TradingPlatform\Vk\Feed\Data\Sources;

abstract class DataSource implements \Iterator
{
	protected $startPos = 0;
	protected $startFeed = 0;

	/**
	 * Set start position for complex source iterator
	 *
	 * @param string $startPos - string in format iBlockNumber_RecordNumber
	 */

	abstract protected function setStartPosition($startPosition);
	
	/**
	 * Owerwrite ITERATOR method
	 * @return mixed
	 */
	abstract public function current();
	
	/**
	 * Owerwrite ITERATOR method
	 * @return mixed
	 */
	abstract public function key();
	
	/**
	 * Owerwrite ITERATOR method
	 * @return mixed
	 */
	abstract public function next();
	
	/**
	 * Owerwrite ITERATOR method
	 * @return mixed
	 */
	abstract public function rewind();
	
	/**
	 * Owerwrite ITERATOR method
	 * @return mixed
	 */
	abstract public function valid();
}