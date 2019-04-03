<?php
/**
 * Author: Mykola Chomenko
 * Email: mykola.chomenko@dipcom.cz
 */

namespace Chomenko\ACL\Exceptions;

use Chomenko\ACL\Signal;

class AccessDenied extends \Exception
{

	/**
	 * @var Signal
	 */
	private $signal;

	public function __construct(Signal $signal, string $message = "Access Denied", int $code = 401)
	{
		parent::__construct($message, $code);
		$this->signal = $signal;
	}

	/**
	 * @return Signal
	 */
	public function getSignal(): Signal
	{
		return $this->signal;
	}

}
