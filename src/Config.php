<?php
/**
 * Author: Mykola Chomenko
 * Email: mykola.chomenko@dipcom.cz
 */

namespace Chomenko\ACL;

use Nette\Caching\Cache;
use Nette\Caching\IStorage;

class Config
{

	/**
	 * @var array
	 */
	protected $interfacesMapping = [];

	/**
	 * @var string
	 */
	protected $cacheName = "acl";

	/**
	 * @var Cache
	 */
	private $cache;

	/**
	 * @var bool
	 * @internal
	 */
	public static $compile = FALSE;

	/**
	 * @param array $parameters
	 * @param IStorage $storage
	 */
	public function __construct(array $parameters, IStorage $storage)
	{
		foreach ($parameters as $key => $value) {
			if (property_exists($this, $key)) {
				$this->{$key} = $value;
			}
		}
		$this->cache = new Cache($storage);
	}

	/**
	 * @return array
	 */
	public function getInterfacesMapping(): array
	{
		return $this->interfacesMapping;
	}

	/**
	 * @param array $interfacesMapping
	 */
	public function setInterfacesMapping(array $interfacesMapping)
	{
		$this->interfacesMapping = $interfacesMapping;
	}

	/**
	 * @return Cache
	 */
	public function getCache(): Cache
	{
		return $this->cache;
	}

	/**
	 * @param Cache $cache
	 * @return $this
	 */
	public function setCache($cache)
	{
		$this->cache = $cache;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getCacheName(): string
	{
		return $this->cacheName;
	}

	/**
	 * @param string $cacheName
	 * @return $this
	 */
	public function setCacheName($cacheName)
	{
		$this->cacheName = $cacheName;
		return $this;
	}

}
