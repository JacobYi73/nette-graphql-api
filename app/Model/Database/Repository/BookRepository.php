<?php

declare(strict_types=1);

namespace App\Model\Database\Repository;

use App\Model\Database\Entity\Book;
use App\Model\Exceptions\EntityNotFoundException;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * @extends BaseRepository<Book, ClassMetadata<Book>>
 */
class BookRepository extends BaseRepository
{
    public function findById(int $id, bool $isMandatory = true): ?Book
    {

        $entity = $this->findOneBy(["id" => $id]);
        if ($isMandatory && ($entity === null || !($entity instanceof Book))) {
            throw new EntityNotFoundException($this->getClassName(), "findById");
        }
        if ($isMandatory) {
            assert($entity instanceof Book);
        }
        return $entity;
    }

    public function findByName(int $name): ?Book
    {
        $entity = $this->findOneBy(["name" => $name]);
        if ($entity === null || !($entity instanceof Book)) {
            throw new EntityNotFoundException($this->getClassName(), "findByName");
        }
        assert($entity instanceof Book);

        return $entity;
    }
}
