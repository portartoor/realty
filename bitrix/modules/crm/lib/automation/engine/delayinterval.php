<?php
namespace Bitrix\Crm\Automation\Engine;

use Bitrix\Crm\Automation\Helper;

class DelayInterval
{
	const TYPE_BEFORE = 'before';
	const TYPE_AFTER = 'after';

	protected $type = 'after'; //TYPE_AFTER
	protected $value;
	protected $valueType;
	protected $basis;

	public function __construct(array $params = null)
	{
		if ($params)
		{
			if (isset($params['type']))
				$this->setType($params['type']);
			if (isset($params['value']))
				$this->setValue($params['value']);
			if (isset($params['valueType']))
				$this->setValueType($params['valueType']);
			$this->setBasis(isset($params['basis']) ? $params['basis'] : Helper::CURRENT_DATETIME_BASIS);
		}
	}

	public static function createFromActivityProperties(array $properties)
	{
		$params = array();
		if (is_array($properties))
		{
			if (isset($properties['TimeoutTime']))
			{
				$params = Helper::parseDateTimeInterval($properties['TimeoutTime']);
			}
			elseif
			(
				isset($properties['TimeoutDuration'])
				&& isset($properties['TimeoutDurationType'])
				&& is_numeric($properties['TimeoutDuration'])
				&& $properties['TimeoutDurationType'] !== 's'
			)
			{
				if ($properties['TimeoutDurationType'] === 'm')
					$properties['TimeoutDurationType'] = 'i';
				$params = array(
					'type' => static::TYPE_AFTER,
					'value' => (int)$properties['TimeoutDuration'],
					'valueType' => $properties['TimeoutDurationType'],
				);
			}
		}

		return new static($params);
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
	 * @return DelayInterval
	 */
	public function setType($type)
	{
		$type = (string)$type;
		if ($type === static::TYPE_BEFORE || $type === static::TYPE_AFTER)
			$this->type = $type;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @param mixed $value
	 * @return DelayInterval
	 */
	public function setValue($value)
	{
		$this->value = (int)$value;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getValueType()
	{
		return $this->valueType;
	}

	/**
	 * @param mixed $valueType
	 * @return DelayInterval
	 */
	public function setValueType($valueType)
	{
		if ($valueType === 'i' || $valueType === 'h' || $valueType === 'd')
			$this->valueType = $valueType;

		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getBasis()
	{
		return $this->basis;
	}

	/**
	 * @param mixed $basis
	 * @return DelayInterval
	 */
	public function setBasis($basis)
	{
		$this->basis = $basis;

		return $this;
	}

	public function toArray()
	{
		return array(
			'type' => $this->getType(),
			'value' => $this->getValue(),
			'valueType' => $this->getValueType(),
			'basis' => $this->getBasis()
		);
	}

	public function toActivityProperties()
	{
		if ($this->getBasis() === Helper::CURRENT_DATETIME_BASIS && $this->getType() === static::TYPE_AFTER)
		{
			$valueType = $this->getValueType();
			if ($valueType === 'i')
				$valueType = 'm';
			return array(
				'TimeoutDuration' => $this->getValue(),
				'TimeoutDurationType' => $valueType,
			);
		}
		return array(
			'TimeoutTime' => Helper::getDateTimeIntervalString(array(
				'type' => $this->getType(),
				'value' => $this->getValue(),
				'valueType' => $this->getValueType(),
				'basis' => $this->getBasis(),
			))
		);
	}

	public function isNow()
	{
		return ($this->getBasis() === Helper::CURRENT_DATETIME_BASIS && $this->getValue() === 0);
	}
}