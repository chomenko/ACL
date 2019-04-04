<?php
/**
 * Author: Mykola Chomenko
 * Email: mykola.chomenko@dipcom.cz
 */

namespace Chomenko\ACL\Mapping;

use Chomenko\ACL\Annotations;
use Webmozart\Assert\Assert;

class Group extends AMapp
{

	/**
	 * @var string
	 */
	protected $className;

	/**
	 * @var Access[]
	 * @internal
	 */
	protected $accession = [];

	/**
	 * @var Group|null
	 */
	protected $parent;

	/**
	 * @var Group[]
	 */
	protected $children = [];

	/**
	 * @param string $className
	 */
	public function __construct(\ReflectionClass $class, Annotations\Group $groupAnnotation)
	{
		$name = $groupAnnotation->name;
		if (empty($groupAnnotation->name)) {
			$name = $class->getShortName();
		}

		Assert::alpha($name, "Class annotation in '{$class->getName()}' @Group must by only letters");
		$this->name = $name;

		$this->id = hash("crc32b", $class->getFileName());
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
	 * @return Access[]
	 */
	public function getAccessions(): array
	{
		return $this->accession;
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
	 * @param Access $accession
	 */
	public function addAccession(Access $accession)
	{
		$this->accession[] = $accession;
	}

	/**
	 * @return Group|null
	 */
	public function getParent(): ?Group
	{
		return $this->parent;
	}

	/**
	 * @param Group|null $parent
	 * @internal
	 */
	public function setParent($parent)
	{
		$this->parent = $parent;
		return $this;
	}

	/**
	 * @return Group[]
	 */
	public function getChildren(): array
	{
		return $this->children;
	}

	/**
	 * @param Group $children
	 * @internal
	 */
	public function addChildren(Group $children)
	{
		$this->children[] = $children;
	}

	/**
	 * @param string $className
	 * @return Group|null
	 */
	public function getGroupByClass(string $className): ?Group
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
	 * @return Group|null
	 */
	public function getGroupById(string $id): ?Group
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
