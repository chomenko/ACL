<?php
/**
 * Author: Mykola Chomenko
 * Email: mykola.chomenko@dipcom.cz
 */

namespace Chomenko\ACL\Mapping;

use Chomenko\ACL\Annotations;

class Access extends AMapp
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
	 * @param \ReflectionMethod $method
	 * @param Annotations\Access $access
	 * @param string $type
	 * @param string $suffix
	 */
	public function __construct(\ReflectionMethod $method, Annotations\Access $access, string $type, string $suffix)
	{
		$this->name = $access->name;
		if (!$access->name) {
			$this->name = lcfirst($suffix);
		}

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
