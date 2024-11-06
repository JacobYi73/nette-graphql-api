<?php

declare(strict_types=1);

namespace App\Model\Exceptions;

use Throwable;

class EntityNotFoundException extends \Exception
{
    public function __construct(string $entityName = '', string $filterString = '', int $code = 0, ?Throwable $previous = null)
    {
        $message = 'Entity "' . $entityName . '" searched by "' . $filterString . '" not found!';
        parent::__construct($message, $code, $previous);
    }
}
