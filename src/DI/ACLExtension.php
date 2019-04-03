<?php
/**
 * Author: Mykola Chomenko
 * Email: mykola.chomenko@dipcom.cz
 */

namespace Chomenko\ACL\DI;

use Chomenko\ACL\ACL;
use Chomenko\ACL\Application\Listener;
use Chomenko\ACL\Config;
use Chomenko\ACL\Mapping;
use Kdyby\Events\DI\EventsExtension;
use Nette\ComponentModel\IContainer;
use Nette\Configurator;
use Nette\DI\Compiler;
use Nette\DI\CompilerExtension;
use Nette;

class ACLExtension extends CompilerExtension
{

	/**
	 * @var array
	 */
	private $default = [
		"interfacesMapping" => [
			IContainer::class,
		],
	];

	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->default);
		$builder->addDefinition($this->prefix("config"))
			->setFactory(Config::class, ["parameters" => $config]);

		$builder->addDefinition($this->prefix("mapping"))
			->setFactory(Mapping::class);

		$builder->addDefinition($this->prefix("acl"))
			->setFactory(ACL::class);

		$builder->addDefinition($this->prefix("application.listener"))
			->setFactory(Listener::class)
			->addTag(EventsExtension::TAG_SUBSCRIBER);

		Config::$compile = TRUE;
	}

	/**
	 * @param Nette\PhpGenerator\ClassType $class
	 */
	public function afterCompile(Nette\PhpGenerator\ClassType $class)
	{
		$ini = $class->getMethod("initialize");
		$name = $this->prefix('mapping');
		$body = $ini->getBody();
		$body .= '$this->getService("' . $name . '")->initialize();' . "\n";
		$ini->setBody($body);
	}

	/**
	 * @param Configurator $configurator
	 */
	public static function register(Configurator $configurator)
	{
		$configurator->onCompile[] = function ($config, Compiler $compiler) {
			$compiler->addExtension('acl', new ACLExtension());
		};
	}

}
