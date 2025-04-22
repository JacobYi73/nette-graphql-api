<?php

declare(strict_types=1);

namespace App\Model\Exceptions;

use Throwable;

class DuplicateEntryException extends \Exception
{
	public function __construct(Throwable|null $previous = null)
	{
		parent::__construct('Tried to insert or update entry with duplicate entry on unique column!', 0, $previous);
	}
}
