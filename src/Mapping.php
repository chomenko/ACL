<?php
/**
 * Author: Mykola Chomenko
 * Email: mykola.chomenko@dipcom.cz
 */

namespace Chomenko\ACL;

use Chomenko\ACL\Annotations\Access;
use Chomenko\ACL\Annotations\Group;
use Chomenko\ACL\Exceptions\MappingExceptions;
use Doctrine\Common\Annotations\Reader;
use Nette\SmartObject;
use Webmozart\Assert\Assert;
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
	 * @var Group[]
	 */
	private $groups = [];

	/**
	 * @var array
	 */
	private $groupList = [
		"id" => [],
		"class" => []
	];

	/**
	 * @var array
	 */
	private $accessList = [
		"id" => [],
		"class" => []
	];

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
	 * @param MappingTypes\Group[] $groups
	 * @param array $list
	 */
	private function createMetaLists(array $groups, &$groupList = ['id' => [], "class" => []], &$accessList = ['id' => [], "class" => []])
	{
		foreach ($groups as $group) {
			$groupList['id'][$group->getId()] = $group;
			$groupList['class'][$group->getClassName()] = $group;
			foreach ($group->getAccessions() as $access) {
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
	 * @return Group[]
	 * @throws \ReflectionException
	 */
	protected function createGroups(): array
	{
		$groups = [];
		foreach ($this->findClassByInterfaces($this->config->getInterfacesMapping()) as $class) {
			$ref = new \ReflectionClass($class);
			if ($ref->isAbstract()) {
				continue;
			}
			/** @var Group $groupAnnotation */
			$groupAnnotation = $this->reader->getClassAnnotation($ref, Group::class);
			if ($groupAnnotation) {

				$group = new MappingTypes\Group($ref, $groupAnnotation);

				foreach ($this->getMethods($ref) as $method) {

					$type = $method["type"];
					$suffix = $method["suffix"];
					/** @var \ReflectionMethod $method */
					$method = $method["reflection"];

					/** @var Access $accessAnnotation */
					if ($accessAnnotation = $this->reader->getMethodAnnotation($method, Access::class)) {
						$access = new MappingTypes\Access($method, $accessAnnotation, $type, lcfirst($suffix));
						$group->addAccession($access);
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
				throw MappingExceptions::groupUndefine($group["parent"]);
			}

			/** @var MappingTypes\Group $parent */
			$parent = $groups[$group["parent"]]["group"];
			$group = $group["group"];
			$parent->addChildren($group);
			$parent->setParent($parent);
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
	 * @return MappingTypes\Group[]
	 */
	public function getGroups(): array
	{
		return $this->groups;
	}

	/**
	 * @param string $id
	 * @return MappingTypes\Group|MappingTypes\Access|null
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
	 * @param string $id
	 * @return MappingTypes\Group|MappingTypes\Access|null
	 */
	public function findByClass(string $class)
	{
		if (array_key_exists($class, $this->groupList['class'])) {
			return $this->groupList['class'][$id];
		}
		if (array_key_exists($class, $this->accessList['class'])) {
			return $this->accessList['class'][$class];
		}
		return NULL;
	}

	/**
	 * @param string $className
	 * @return MappingTypes\Group|null
	 */
	public function getGroupByClass(string $className): ?MappingTypes\Group
	{
		if (array_key_exists($className, $this->groupList['class'])) {
			return $this->groupList['class'][$className];
		}
		return NULL;
	}

	/**
	 * @param string $className
	 * @return MappingTypes\Group|null
	 */
	public function getGroupById(string $id): ?MappingTypes\Group
	{
		if (array_key_exists($id, $this->groupList['id'])) {
			return $this->groupList['id'][$id];
		}
		return NULL;
	}

	/**
	 * @param string $className
	 * @return MappingTypes\Group|null
	 */
	public function getAccessByClass(string $className): ?MappingTypes\Group
	{
		if (array_key_exists($class, $this->accessList['class'])) {
			return $this->accessList['class'][$id];
		}
		return NULL;
	}

	/**
	 * @param string $className
	 * @return MappingTypes\Group|null
	 */
	public function getAccessByid(string $id): ?MappingTypes\Group
	{
		if (array_key_exists($class, $this->accessList['id'])) {
			return $this->accessList['id'][$id];
		}
		return NULL;
	}

}
