<?php
/**
 * Author: Mykola Chomenko
 * Email: mykola.chomenko@dipcom.cz
 */

namespace Chomenko\ACL\Annotations;

use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Group extends Accessor
{

	/**
	 * @var Access[]
	 * @internal
	 */
	private $accession = [];

	/**
	 * @var string
	 */
	private $className;

	/**
	 * @var string
	 */
	private $id;

	/**
	 * @return Access[]
	 */
	public function getAccession(): array
	{
		return $this->accession;
	}

	/**
	 * @param Access $access
	 * @return $this
	 */
	public function addAccess(Access $access)
	{
		$this->accession[] = $access;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getClassName(): string
	{
		return $this->className;
	}

	/**
	 * @param string $className
	 */
	public function setClassName($className): void
	{
		$this->id = hash("crc32b", $className);
		$this->className = $className;
	}

	/**
	 * @param string $type
	 * @param string $name
	 * @return Access|null
	 */
	public function getAccess(string $type, string $name): ?Access
	{
		foreach ($this->accession as $access) {
			if ($access->getType() === $type && $access->getSuffix() === $name) {
				return $access;
			}
		}
		return NULL;
	}

	/**
	 * @return string
	 */
	public function getId(): string
	{
		return $this->id;
	}

}
