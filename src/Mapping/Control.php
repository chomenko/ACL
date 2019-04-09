<?php
/**
 * Author: Mykola Chomenko
 * Email: mykola.chomenko@dipcom.cz
 */

namespace Chomenko\ACL\Mapping;

use Chomenko\ACL\Annotations;
use Webmozart\Assert\Assert;

class Control extends AMappingSignal
{

	/**
	 * @var string
	 */
	protected $className;

	/**
	 * @var Action[]
	 * @internal
	 */
	protected $accession = [];

	/**
	 * @var Control[]
	 */
	protected $children = [];

	/**
	 * @param \ReflectionClass $class
	 * @param Annotations\Control $groupAnnotation
	 */
	public function __construct(\ReflectionClass $class, Annotations\Control $groupAnnotation)
	{
		$name = $groupAnnotation->name;
		if (empty($groupAnnotation->name)) {
			$name = $class->getShortName();
		}
		$this->setMessage($groupAnnotation->message);
		Assert::alpha($name, "Class annotation in '{$class->getName()}' @Group must by only letters");
		$this->name = $name;

		$id = $groupAnnotation->id;
		if (!$id) {
			$id = hash("crc32b", $class->getFileName());
		}

		$this->id = $id;
		$this->className = $class->getName();
		$this->description = $groupAnnotation->description;
		$this->options = $groupAnnotation->options;
	}

	/**
	 * @return string
	 */
	public function getClassName(): string
	{
		return $this->className;
	}

	/**
	 * @return Action[]
	 */
	public function getAccessions(): array
	{
		return $this->accession;
	}

	/**
	 * @param string $type
	 * @param string $name
	 * @return Action|null
	 */
	public function getAccess(string $type, string $name): ?Action
	{
		foreach ($this->accession as $access) {
			if ($access->getType() === $type && $access->getSuffix() === $name) {
				return $access;
			}
		}
		return NULL;
	}

	/**
	 * @param Action $accession
	 */
	public function addAccession(Action $accession)
	{
		$this->accession[] = $accession;
	}

	/**
	 * @return Control[]
	 */
	public function getChildren(): array
	{
		return $this->children;
	}

	/**
	 * @param Control $children
	 * @internal
	 */
	public function addChildren(Control $children)
	{
		$this->children[] = $children;
	}

	/**
	 * @param string $className
	 * @return Control|null
	 */
	public function getGroupByClass(string $className): ?Control
	{
		foreach ($this->children as $group) {
			if ($group->getClassName() === $className) {
				return $group;
			} elseif ($group = $group->getGroupByClass($className)) {
				return $group;
			}
		}
		return NULL;
	}

	/**
	 * @param string $id
	 * @return Control|null
	 */
	public function getGroupById(string $id): ?Control
	{
		foreach ($this->children as $group) {
			if ($group->getId() === $id) {
				return $group;
			} elseif ($group = $group->getGroupById($id)) {
				return $group;
			}
		}
		return NULL;
	}

}
