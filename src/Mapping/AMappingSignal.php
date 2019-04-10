<?php
/**
 * Author: Mykola Chomenko
 * Email: mykola.chomenko@dipcom.cz
 */

namespace Chomenko\ACL\Mapping;

abstract class AMappingSignal
{

	/**
	 * @var string
	 */
	protected $id;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var array
	 */
	protected $options = [];

	/**
	 * @var string|null
	 */
	protected $description;

	/**
	 * @var string|null
	 */
	protected $message;

	/**
	 * @var bool
	 */
	protected $allowed = FALSE;

	/**
	 * @var AMappingSignal|null
	 */
	protected $parent;

	/**
	 * @return string
	 */
	public function getId(): string
	{
		return $this->id;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return array
	 */
	public function getOptions(): array
	{
		return $this->options;
	}

	/**
	 * @return string|null
	 */
	public function getDescription(): ?string
	{
		return $this->description;
	}

	/**
	 * @return Control|null
	 * @internal
	 */
	public function getParent(): ?AMappingSignal
	{
		return $this->parent;
	}

	/**
	 * @param AMappingSignal|null $parent
	 * @internal
	 */
	public function setParent(?AMappingSignal $parent)
	{
		$this->parent = $parent;
	}

	/**
	 * @param bool $parents checks all parent
	 * @return bool
	 */
	public function isAllowed(bool $parents = FALSE): bool
	{
		if ($parents) {
			if ($this->allowed) {
				if ($this->parent) {
					return $this->parent->isAllowed(TRUE);
				}
				return $this->allowed;
			}
		}
		return $this->allowed;
	}

	/**
	 * @param bool $allowed
	 * @return $this
	 */
	public function setAllowed(bool $allowed)
	{
		$this->allowed = $allowed;
		return $this;
	}

	/**
	 * @return string|null
	 */
	public function getMessage(): ?string
	{
		return $this->message;
	}

	/**
	 * @param string|null $message
	 * @return $this
	 */
	public function setMessage($message)
	{
		$this->message = $message;
		return $this;
	}

}
