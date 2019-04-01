<?php
/**
 * Author: Mykola Chomenko
 * Email: mykola.chomenko@dipcom.cz
 */

namespace Chomenko\ACL;

use Chomenko\ACL\Annotations\Access;
use Chomenko\ACL\Annotations\Group;
use Doctrine\Common\Annotations\Reader;
use Webmozart\Assert\Assert;

class Mapping
{

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
			/** @var Group $group */
			$group = $this->reader->getClassAnnotation($ref, Group::class);
			if ($group) {

				if (empty($group->getName())) {
					$group->setName($ref->getShortName());
				}

				Assert::alpha($group->getName(), "Class annotation in '{$class}' @Group must by only letters");
				$group->setClassName($class);
				foreach ($this->getMethods($ref) as $method) {

					$type = $method["type"];
					$suffix = $method["suffix"];
					/** @var \ReflectionMethod $method */
					$method = $method["reflection"];

					/** @var Access $access */
					if ($access = $this->reader->getMethodAnnotation($method, Access::class)) {
						$access->setType($type);
						$access->setMethod($method);
						$access->setSuffix(lcfirst($suffix));
						if (!$access->getName()) {
							$access->setName(lcfirst($suffix));
						}
						$group->addAccess($access);
					}
				}
				$groups[] = $group;
			}
		}
		return $groups;
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
	 * @return Group|null
	 */
	public function getGroupByClass(string $className): ?Group
	{
		foreach ($this->getGroups() as $group) {
			if ($group->getClassName() === $className) {
				return $group;
			}
		}
		return NULL;
	}

}
