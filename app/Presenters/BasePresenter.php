<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
	public function __construct()
	{
		parent::__construct();
	}

	protected function startup(): void
	{
		parent::startup();
	}
}
