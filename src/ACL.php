<?php
/**
 * Author: Mykola Chomenko
 * Email: mykola.chomenko@dipcom.cz
 */

namespace Chomenko\ACL;

use Chomenko\ACL\Exceptions\AccessDenied;
use Chomenko\ACL\Mapping\AMappingSignal;
use Nette\Application\IPresenterFactory;
use Nette\Application\Request;
use Nette\Application\UI\InvalidLinkException;
use Nette\Application\UI\Presenter;
use Nette\SmartObject;

/**
 *  @method onAccessVerify(Signal $signal)
 *  @method onAccessDenied(AccessDenied $exception)
 */
class ACL
{

	use SmartObject;

	/**
	 * @var callable[]
	 */
	public $onAccessVerify;

	/**
	 * @var callable[]
	 */
	public $onAccessDenied;

	/**
	 * @var Mapping
	 */
	private $mapping;

	/**
	 * @var IPresenterFactory
	 */
	private $presenterFactory;

	/**
	 * @param Mapping $mapping
	 * @param IPresenterFactory $presenterFactory
	 */
	public function __construct(Mapping $mapping, IPresenterFactory $presenterFactory)
	{
		$this->mapping = $mapping;
		$this->presenterFactory = $presenterFactory;
	}

	/**
	 * @param Signal $signal
	 * @return bool|AccessDenied
	 */
	private function access(Signal $signal)
	{
		try {
			$this->onAccessVerify($signal);
			return TRUE;
		} catch (AccessDenied $exception) {
			return $exception;
		}
	}

	/**
	 * @param AccessDenied $exception
	 */
	private function accessDenied(AccessDenied $exception)
	{
		$this->onAccessDenied($exception);
	}

	/**
	 * @param Presenter $presenter
	 * @param Request $request
	 */
	public function presenterSignal(Presenter $presenter, Request $request)
	{
		$group = $this->mapping->getGroupByClass(get_class($presenter));
		$signal = NULL;
		$denied = FALSE;

		if ($group) {
			$signal = new Signal($presenter, $group);
			$signal->setRequest($request);

			if (($denied = $this->access($signal)) === TRUE) {
				$denied = FALSE;
				$types = [
					"action" => $request->getParameter(Presenter::ACTION_KEY),
					"handle" => $request->getParameter(Presenter::SIGNAL_KEY),
					"render" => $request->getParameter(Presenter::ACTION_KEY),
				];

				foreach ($types as $type => $suffix) {
					if (empty($suffix) || !($access = $group->getAction($type, lcfirst($suffix)))) {
						continue;
					}
					$typeSignal = new Signal($presenter, $access);
					$typeSignal->setParent($signal);
					$typeSignal->setRequest($request);

					$allowed = $this->access($typeSignal);
					if ($allowed !== TRUE) {
						$denied = $allowed;
						break;
					}
				}
			}
		}

		$do = $request->getParameter(Presenter::SIGNAL_KEY);
		if ($do || $denied !== FALSE) {
			$presenter->onStartup[] = function () use ($denied, $presenter, $request, $signal, $do) {
				if ($denied !== FALSE) {
					$this->accessDenied($denied);
					return;
				}
				$this->componentSignal($presenter, $do, $signal);
			};
		}
	}

	/**
	 * @param Presenter $presenter
	 * @param string $componentName
	 * @param Signal|null $parentSignal
	 */
	private function componentSignal(Presenter $presenter, string $componentName, ?Signal $parentSignal = NULL)
	{
		if ($this->hasHandleType($componentName)) {
			return;
		}

		$list = explode("-", $componentName);
		$control = $presenter;
		$request = $presenter->getRequest();
		$group = NULL;

		foreach ($list as $key => $name) {
			$component = $control->getComponent($name, FALSE);
			if (!$component) {
				break;
			}
			$control = $component;
			$group = $this->mapping->getGroupByClass(get_class($component));

			if (!$group) {
				continue;
			}

			$signal = new Signal($component, $group);
			$signal->setRequest($request);
			$signal->setParent($parentSignal);
			$parentSignal = $signal;

			if (($denied = $this->access($signal)) instanceof AccessDenied) {
				$this->accessDenied($denied);
				break;
			}
		}
	}

	/**
	 * @param string $str
	 * @return bool
	 */
	private function hasHandleType(string $str): bool
	{
		$chr = mb_substr($str, 0, 1, "UTF-8");
		return mb_strtolower($chr, "UTF-8") != $chr;
	}

	/**
	 * @return Mapping
	 */
	public function getMapping(): Mapping
	{
		return $this->mapping;
	}

	/**
	 * @param object $object
	 * @return Mapping\Action|Mapping\Control|null
	 */
	public function getSignalByObject(object $object)
	{
		return $this->getMapping()->findByClass(get_class($object));
	}

	/**
	 * Returned AMappingSignal by link destination $dest = string "[Admin=module]:[User=presenter]:[RemoveUser!=handle or action]"
	 * @param string$dest
	 * @return AMappingSignal|Mapping\Action|Mapping\Control|null
	 * @throws InvalidLinkException
	 * @throws \Nette\Application\InvalidPresenterException
	 */
	public function getMappingItemByDest(string $dest): ?AMappingSignal
	{
		if (!preg_match('~^([\w:]+):(\w*+)(!)?()\z~', $dest, $m)) {
			throw new InvalidLinkException("Invalid link destination '$dest'.");
		}
		list(, $presenter, $action, $type) = $m;

		try {
			$class = $this->presenterFactory ? $this->presenterFactory->getPresenterClass($presenter) : NULL;
		} catch (InvalidPresenterException $e) {
			throw new InvalidLinkException($e->getMessage(), 0, $e);
		}

		$control = $this->mapping->findByClass($class);
		if (!$control) {
			return NULL;
		}

		$actionItem = $control->getActionByMethod($class::formatActionMethod(ucfirst($action)));

		if (!$actionItem) {
			$actionItem = $control->getActionByMethod($class::formatRenderMethod(ucfirst($action)));
		}

		if (!$actionItem && $type === "!") {
			$actionItem = $control->getActionByMethod($class::formatSignalMethod(ucfirst($action)));
		}

		return $actionItem ? $actionItem : $control;
	}

	/**
	 * @param string $className
	 * @return bool
	 */
	public function isClassAccessed(string $className): bool
	{
		$item = $this->getMapping()->findByClass($className);
		if ($item && $item->isAllowed(TRUE)) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * @param string $className
	 * @param string $actionName
	 * @return bool
	 */
	public function isActionAccessed(string $className, string $actionName): bool
	{
		$item = $this->getMapping()->findByClass($className);
		if ($item) {
			foreach ($item->getActions() as $action) {
				if ($action->getSuffix() == $actionName) {
					return $action->isAllowed(TRUE);
				}
			}
		}
		return FALSE;
	}

	/**
	 * @param string $dest
	 * @return bool
	 * @throws InvalidLinkException
	 * @throws \Nette\Application\InvalidPresenterException
	 */
	public function isLinkAccessed(string $dest): bool
	{
		$item = $this->getMappingItemByDest($dest);
		if ($item) {
			return $item->isAllowed(TRUE);
		}
		return TRUE;
	}

}
