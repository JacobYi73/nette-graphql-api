<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;

final class Error4xxPresenter extends BasePresenter
{
    public function startup(): void
    {
        parent::startup();
        assert($this->getRequest() instanceof Nette\Application\Request);
        if (!$this->getRequest()->isMethod(Nette\Application\Request::FORWARD)) {
            $this->error();
        }
    }


    public function renderDefault(Nette\Application\BadRequestException $exception): void
    {
        // load template 403.latte or 404.latte or ... 4xx.latte
        $file = __DIR__ . "/templates/Error/{$exception->getCode()}.latte";
        $file = is_file($file) ? $file : __DIR__ . '/templates/Error/4xx.latte';
        assert($this->template instanceof Nette\Application\UI\ITemplate);
        $this->template->setFile($file);
    }
}
