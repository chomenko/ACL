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
	protected $actions = [];

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
			$id = hash("crc32b", $class->getName());
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
	public function getActions(): array
	{
		return $this->actions;
	}

	/**
	 * @param string $type
	 * @param string $name
	 * @return Action|null
	 */
	public function getAction(string $type, string $name): ?Action
	{
		foreach ($this->actions as $action) {
			if ($action->getType() === $type && $action->getSuffix() === $name) {
				return $action;
			}
		}
		return NULL;
	}

	/**
	 * @param string $method
	 * @return Action|null
	 */
	public function getActionByMethod(string $method): ?Action
	{
		foreach ($this->actions as $action) {
			if ($action->getMethodName() === $method) {
				return $action;
			}
		}
		return NULL;
	}

	/**
	 * @param Action $accession
	 */
	public function addAction(Action $accession)
	{
		$this->actions[] = $accession;
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
