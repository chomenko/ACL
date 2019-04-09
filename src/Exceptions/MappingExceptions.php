<?php
/**
 * Author: Mykola Chomenko
 * Email: mykola.chomenko@dipcom.cz
 */

namespace Chomenko\ACL\Exceptions;

use Chomenko\ACL\Mapping\Action;
use Chomenko\ACL\Mapping\AMappingSignal;
use Chomenko\ACL\Mapping\Control;

class MappingExceptions extends \Exceptions
{

	/**
	 * @param string $className
	 * @return $this
	 */
	public static function groupUndefined($className)
	{
		return new self("ACL group '{$className}' undefined");
	}

	/**
	 * @param AMappingSignal|Control|Action $signal
	 * @param  AMappingSignal|Control|Action $in
	 * @return $this
	 */
	public static function duplicitySignalId(AMappingSignal $signal, AMappingSignal $in)
	{
		$class = $signal->getClassName();
		if ($signal instanceof Action) {
			$class += $signal->getMethodName();
		}
		$twoClass = $in->getClassName();
		if ($in instanceof Action) {
			$twoClass += $in->getMethodName();
		}
		return new self("Duplicity id '{$signal->getId()}' in '{$class}' and '{$twoClass}' edit annotation change id @ACL\Control(id=\"myUniqueId\")");
	}

}
