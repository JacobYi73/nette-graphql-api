<?php

declare(strict_types=1);

namespace App\Model\Database;

use App\Model\Database\Entity\Book;
use App\Model\Database\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Nettrine\ORM\EntityManagerDecorator;

class EntityManager extends EntityManagerDecorator
{
	public function __construct(EntityManagerInterface $wrapped)
	{
		parent::__construct($wrapped);
	}

	public function getBookRepository(): BookRepository
	{
		$repo = $this->getRepository(Book::class);
		\assert($repo instanceof BookRepository);

		return $repo;
	}
}
