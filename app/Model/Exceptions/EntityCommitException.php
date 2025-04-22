<?php

declare(strict_types=1);

namespace App\Model\Exceptions;

use Throwable;

class EntityCommitException extends \Exception
{
	public function __construct(
		string $entityName,
		string $msg = '',
		int $code = 0,
		Throwable|null $previous = null,
	) {
		if (!\class_exists($entityName)) {
			throw new \InvalidArgumentException('Entity name "' . $entityName . '" is not a valid class name.');
		}

		$message = 'Commit failed for entity "' . $entityName . '"! ' . $msg;
		parent::__construct($message, $code, $previous);
	}
}
