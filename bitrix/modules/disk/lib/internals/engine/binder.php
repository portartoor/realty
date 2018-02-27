<?php

namespace Bitrix\Disk\Internals\Engine;


use Bitrix\Main\ArgumentNullException;

final class Binder
{
	const STATUS_FOUND     = true;
	const STATUS_NOT_FOUND = false;

	private $instance;
	private $method;
	/** @var array */
	private $methodParams;
	/** @var array */
	private $args;
	private $listSourceParameters;
	/** @var \ReflectionMethod */
	private $reflectionMethod;

	/**
	 * Binder constructor.
	 * @param mixed  $instance Instance of the class that contains the method.
	 * @param string $method Name of the method.
	 * @param array  $listSourceParameters List of parameters source which we want to bind.
	 */
	public function __construct($instance, $method, array $listSourceParameters)
	{
		$this->instance = $instance;
		$this->method = $method;
		$this->listSourceParameters = $listSourceParameters;

		$this->buildReflectionMethod();
		$this->bindParams();
	}

	/**
	 * Builds instance of reflection method.
	 * @return void
	 */
	private function buildReflectionMethod()
	{
		$this->reflectionMethod = new \ReflectionMethod($this->instance, $this->method);
		$this->reflectionMethod->setAccessible(true);
	}

	/**
	 * Returns list of method params.
	 * @return array
	 */
	public function getMethodParams()
	{
		return $this->methodParams;
	}

	/**
	 * Returns list of method params which possible use in call_user_func_array().
	 * @return array
	 */
	public function getArgs()
	{
		return $this->args;
	}

	/**
	 * Invokes method with binded parameters.
	 * return @mixed
	 */
	public function invoke()
	{
		return $this->reflectionMethod->invokeArgs($this->instance, $this->getArgs());
	}

	private function findParameterInList($name, &$status)
	{
		$status = self::STATUS_FOUND;
		foreach($this->listSourceParameters as $source)
		{
			if(isset($source[$name]))
			{
				return $source[$name];
			}

			if($source instanceof \ArrayAccess && $source->offsetExists($name))
			{
				return $source[$name];
			}
			elseif(is_array($source) && array_key_exists($name, $source))
			{
				return $source[$name];
			}
		}
		unset($source);
		$status = self::STATUS_NOT_FOUND;

		return null;
	}

	private function bindParams()
	{
		$this->args = $this->methodParams = array();

		foreach($this->reflectionMethod->getParameters() as $param)
		{
			$name = $param->getName();
			$value = $this->findParameterInList($name, $status);
			if($status === self::STATUS_FOUND)
			{
				if($param->isArray())
				{
					$this->args[] = $this->methodParams[$name] = (array)$value;
				}
				else
				{
					$this->args[] = $this->methodParams[$name] = $value;
				}
			}
			elseif($param->isDefaultValueAvailable())
			{
				$this->args[] = $this->methodParams[$name] = $param->getDefaultValue();
			}
			else
			{
				throw new ArgumentNullException($name);
			}
		}
		unset($param);

		return $this->args;
	}
}