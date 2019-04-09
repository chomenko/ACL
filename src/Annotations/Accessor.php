<?php
/**
 * Author: Mykola Chomenko
 * Email: mykola.chomenko@dipcom.cz
 */

namespace Chomenko\ACL\Annotations;

abstract class Accessor
{

	/**
	 * Unique id
	 * @var string
	 */
	public $id;

	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $description;

	/**
	 * @var string
	 */
	public $message = "You are not sufficiently entitled to access this section.";

	/**
	 * @var array
	 */
	public $options = [];

}
