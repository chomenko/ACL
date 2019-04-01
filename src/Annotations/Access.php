<?php
/**
 * Author: Mykola Chomenko
 * Email: mykola.chomenko@dipcom.cz
 */

namespace Chomenko\ACL\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("METHOD")
 */
class Access extends Accessor
{

	/**
	 * @var string
	 */
	private $id;

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
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * @param string $type
	 */
	public function setType($type): void
	{
		$this->type = $type;
	}

	/**
	 * @return string
	 */
	public function getMethodName(): string
	{
		return $this->methodName;
	}

	/**
	 * @param \ReflectionMethod $method
	 */
	public function setMethod(\ReflectionMethod $method): void
	{
		$className = $method->getDeclaringClass()->getName();
		$this->id = hash("crc32b", $className . "::" . $method->getName());
		$this->methodName = $method->getName();
	}

	/**
	 * @return string
	 */
	public function getName(): ?string
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getDescription(): ?string
	{
		return $this->description;
	}

	/**
	 * @param string $name
	 */
	public function setName(string $name)
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getSuffix(): string
	{
		return $this->suffix;
	}

	/**
	 * @param string $suffix
	 * @return $this
	 */
	public function setSuffix($suffix)
	{
		$this->suffix = $suffix;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getId(): string
	{
		return $this->id;
	}

}
