<?php
/**
 * Author: Mykola Chomenko
 * Email: mykola.chomenko@dipcom.cz
 */

namespace Chomenko\ACL\Mapping;

use Chomenko\ACL\Annotations;

class Action extends AMappingSignal
{

	/**
	 * @var string
	 */
	private $type;

	/**
	 * @var string
	 */
	private $methodName;

	/**
	 * @var string
	 */
	private $suffix;

	/**
	 * @param Control $control
	 * @param \ReflectionMethod $method
	 * @param Annotations\Action $access
	 * @param string $type
	 * @param string $suffix
	 */
	public function __construct(Control $control, \ReflectionMethod $method, Annotations\Action $access, string $type, string $suffix)
	{
		$this->name = $access->name;
		if (!$access->name) {
			$this->name = lcfirst($suffix);
		}

		$this->setParent($control);
		$this->setMessage($access->message);
		$this->type = $type;
		$this->suffix = $suffix;
		$this->description = $access->description;
		$this->options = $access->options;

		$className = $method->getDeclaringClass()->getName();
		$this->id = hash("crc32b", $className . "::" . $method->getName());
		$this->methodName = $method->getName();
	}

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getMethodName(): string
	{
		return $this->methodName;
	}

	/**
	 * @return string
	 */
	public function getSuffix(): string
	{
		return $this->suffix;
	}

}
