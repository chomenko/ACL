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
 *  @method onInitialize(array $groups)
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
		$this->onInitialize($groups);
		$this->groups = $groups;
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
	 * @return Group[]
	 */
	public function getGroups(): array
	{
		return $this->groups;
	}

	/**
	 * @param string $className
	 * @return MappingTypes\Group|null
	 */
	public function getGroupByClass(string $className): ?MappingTypes\Group
	{
		foreach ($this->getGroups() as $group) {
			if ($group->getClassName() === $className) {
				return $group;
			}
			if ($group = $group->getGroupByClass($className)) {
				return $group;
			}
		}
		return NULL;
	}

}
