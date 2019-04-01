<?php
/**
 * Author: Mykola Chomenko
 * Email: mykola.chomenko@dipcom.cz
 */

namespace Chomenko\ACL\Application;

use Chomenko\ACL\ACL;
use Kdyby\Events\Subscriber;
use Nette\Application\Application;
use Nette\Application\UI\Presenter;

class Listener implements Subscriber
{

	/**
	 * @var ACL
	 */
	private $acl;

	public function __construct(ACL $acl)
	{
		$this->acl = $acl;
	}

	/**
	 * @return array|string[]
	 */
	public function getSubscribedEvents()
	{
		if (php_sapi_name() == "cli") {
			return [];
		}
		return [
			Application::class . '::onPresenter' => "request",
		];
	}

	/**
	 * @param Application $application
	 * @param Presenter $presenter
	 */
	public function request(Application $application, Presenter $presenter): void
	{
		$requests = $application->getRequests();
		$request = end($requests);
		$this->acl->presenterSignal($presenter, $request);
	}

}
