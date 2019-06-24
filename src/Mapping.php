<?php
/**
 * Author: Mykola Chomenko
 * Email: mykola.chomenko@dipcom.cz
 */

namespace Chomenko\ACL;

use Chomenko\ACL\Annotations\Action;
use Chomenko\ACL\Annotations\Control;
use Chomenko\ACL\Exceptions\MappingExceptions;
use Doctrine\Common\Annotations\Reader;
use Nette\SmartObject;
use Chomenko\ACL\Mapping as MappingTypes;

/**
 *  @method onInitialize(Mapping $mapping)
 */
class Mapping
{

	use SmartObject;

	/**
	 * @var callable[]
	 */
	public $onInitialize = [];

	/**
	 * @var Config
	 */
	private $config;

	/**
	 * @var Reader
	 */
	private $reader;

	/**
	 * @var array
	 */
	private $methodType = [
		"handle",
		"render",
		"action",
		"createComponent"
	];

	/**
	 * @var Control[]
	 */
	private $groups = [];

	/**
	 * @var array
	 */
	private $groupList = [
		"id" => [],
		"class" => [],
	];

	/**
	 * @var array
	 */
	private $accessList = [
		"id" => [],
		"class" => [],
	];

	/**
	 * @var array
	 */
	private $allowedRules = [];

	/**
	 * @param Config $config
	 * @param Reader $reader
	 */
	public function __construct(Config $config, Reader $reader)
	{
		$this->config = $config;
		$this->reader = $reader;
	}

	/**
	 * @return Config
	 */
	public function getConfig(): Config
	{
		return $this->config;
	}

	/**
	 * @throws \ReflectionException
	 * @throws \Throwable
	 */
	public function initialize(): void
	{
		$groups = $this->config->getCache()->load("groups", []);
		if (Config::$compile) {
			$groups = $this->createGroups();
			$this->config->getCache()->save("groups", $groups);
		}
		$this->groups = $groups;

		$this->createMetaLists($groups, $groupList, $accessList);
		$this->groupList = $groupList;
		$this->accessList = $accessList;

		$this->onInitialize($this);
	}

	/**
	 * @param MappingTypes\Control[] $groups
	 * @param array $groupList
	 * @param array $accessList
	 */
	private function createMetaLists(array $groups, &$groupList = ['id' => [], "class" => []], &$accessList = ['id' => [], "class" => []])
	{
		foreach ($groups as $group) {
			$groupList['id'][$group->getId()] = $group;
			$groupList['class'][$group->getClassName()] = $group;
			foreach ($group->getActions() as $access) {
				$accessList['id'][$access->getId()] = $access;
				$accessList['class'][$group->getClassName() . "::" . $access->getMethodName()] = $access;
			}
			$this->createMetaLists($group->getChildren(), $groupList, $accessList);
		}
	}

	/**
	 * @param \ReflectionClass $class
	 * @return array
	 */
	protected function getMethods(\ReflectionClass $class): array
	{
		$methods = [];
		foreach ($class->getMethods() as $method) {
			$name = $method->getName();
			foreach ($this->methodType as $type) {
				if (substr($name, 0, strlen($type)) === $type) {
					$methods[] = [
						"type" => $type,
						"reflection" => $method,
						"suffix" => substr($name, strlen($type), strlen($name))
					];
					break;
				}
			}
		}
		return $methods;
	}

	/**
	 * @return Control[]
	 * @throws \ReflectionException
	 */
	protected function createGroups(): array
	{
		$list = [];
		$groups = [];
		foreach ($this->findClassByInterfaces($this->config->getInterfacesMapping()) as $class) {
			$ref = new \ReflectionClass($class);
			if ($ref->isAbstract()) {
				continue;
			}
			/** @var Control $groupAnnotation */
			$groupAnnotation = $this->reader->getClassAnnotation($ref, Control::class);
			if ($groupAnnotation) {

				$group = new MappingTypes\Control($ref, $groupAnnotation);

				if (array_key_exists($group->getId(), $list)) {
					throw MappingExceptions::duplicitySignalId($list[$group->getId()], $group);
				}
				$list[$group->getId()] = $group;
				foreach ($this->getMethods($ref) as $method) {

					$type = $method["type"];
					$suffix = $method["suffix"];
					/** @var \ReflectionMethod $method */
					$method = $method["reflection"];

					/** @var Action $accessAnnotation */
					if ($accessAnnotation = $this->reader->getMethodAnnotation($method, Action::class)) {
						$access = new MappingTypes\Action($group, $method, $accessAnnotation, $type, lcfirst($suffix));
						if (array_key_exists($access->getId(), $list)) {
							throw MappingExceptions::duplicitySignalId($list[$access->getId()], $access);
						}
						$list[$access->getId()] = $access;
						$group->addAction($access);
					}
				}
				$groups[$class] = [
					"group" => $group,
					"parent" => $groupAnnotation->parent,
				];
			}
		}

		$list = [];

		foreach ($groups as $group) {
			if (empty($group["parent"])) {
				$list[] = $group["group"];
				continue;
			}

			if (!array_key_exists($group["parent"], $groups)) {
				throw MappingExceptions::groupUndefined($group["parent"]);
			}

			/** @var MappingTypes\Control $parent */
			$parent = $groups[$group["parent"]]["group"];
			$group = $group["group"];
			$parent->addChildren($group);
			$group->setParent($parent);
		}

		return $list;
	}

	/**
	 * @param array $interfaces
	 * @return array
	 */
	protected function findClassByInterfaces(array $interfaces): array
	{
		$classList = [];
		foreach (get_declared_classes() as $class) {
			$interfacesList = class_implements($class);
			foreach ($interfaces as $interface) {
				if (array_key_exists($interface, $interfacesList)) {
					$classList[] = $class;
					break;
				}
			}
		}
		return $classList;
	}

	/**
	 * @return MappingTypes\AMappingSignal[]
	 */
	public function getList(): array
	{
		return $this->groupList['id'] + $this->accessList['id'];
	}

	/**
	 * @return array
	 */
	public function getGroupList(): array
	{
		return $this->groupList;
	}

	/**
	 * @return array
	 */
	public function getAccessList(): array
	{
		return $this->accessList;
	}

	/**
	 * @return MappingTypes\Control[]
	 */
	public function getGroups(): array
	{
		return $this->groups;
	}

	/**
	 * @param string $id
	 * @return MappingTypes\Control|MappingTypes\Action|null
	 */
	public function findById(string $id)
	{
		if (array_key_exists($id, $this->groupList['id'])) {
			return $this->groupList['id'][$id];
		}

		if (array_key_exists($id, $this->accessList['id'])) {
			return $this->accessList['id'][$id];
		}
		return NULL;
	}

	/**
	 * @param string $class
	 * @return MappingTypes\Control|MappingTypes\Action|null
	 */
	public function findByClass(string $class)
	{
		if (array_key_exists($class, $this->groupList['class'])) {
			return $this->groupList['class'][$class];
		}
		if (array_key_exists($class, $this->accessList['class'])) {
			return $this->accessList['class'][$class];
		}
		return NULL;
	}

	/**
	 * @param string $className
	 * @return MappingTypes\Control|null
	 */
	public function getGroupByClass(string $className): ?MappingTypes\Control
	{
		if (array_key_exists($className, $this->groupList['class'])) {
			return $this->groupList['class'][$className];
		}
		return NULL;
	}

	/**
	 * @param string $id
	 * @return MappingTypes\Control|null
	 */
	public function getGroupById(string $id): ?MappingTypes\Control
	{
		if (array_key_exists($id, $this->groupList['id'])) {
			return $this->groupList['id'][$id];
		}
		return NULL;
	}

	/**
	 * @param string $className
	 * @return MappingTypes\Control|null
	 */
	public function getAccessByClass(string $className): ?MappingTypes\Control
	{
		if (array_key_exists($className, $this->accessList['class'])) {
			return $this->accessList['class'][$className];
		}
		return NULL;
	}

	/**
	 * @param string $id
	 * @return MappingTypes\Control|null
	 */
	public function getAccessById(string $id): ?MappingTypes\Control
	{
		if (array_key_exists($id, $this->accessList['id'])) {
			return $this->accessList['id'][$id];
		}
		return NULL;
	}

	/**
	 * @return array
	 */
	public function getAllowedRules(): array
	{
		return $this->allowedRules;
	}

	/**
	 * @param string $allowedRule
	 * @return $this
	 */
	public function addAllowedRule(string $allowedRule)
	{
		$this->allowedRules[$allowedRule] = $allowedRule;
		return $this;
	}

	/**
	 * @param string $rule
	 */
	public function removeAllowedRule(string $rule)
	{
		if (isset($this->allowedRules[$rule])) {
			unset($this->allowedRules[$rule]);
		}
	}

}
