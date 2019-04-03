<?php
/**
 * Author: Mykola Chomenko
 * Email: mykola.chomenko@dipcom.cz
 */

namespace Chomenko\ACL\Exceptions;

class MappingExceptions extends \Exceptions
{

	/**
	 * @param $className
	 * @return Mapping
	 */
	public static function groupUndefine($className)
	{
		return new self("ACL group '{$className}' undefined");
	}

}
