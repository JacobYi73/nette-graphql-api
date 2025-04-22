<?php

declare(strict_types=1);

namespace App\GraphQL\Resolvers;

use App\GraphQL\GraphqlConfig;
use App\Model\Database\Entity\Book;
use App\Model\Database\EntityManager;
use App\Model\Database\Repository\BookRepository;
use App\Model\Exceptions\EntityNotFoundException;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * @extends BaseResolver<BookRepository>
 */
class BookResolver extends BaseResolver
{
	public function __construct(EntityManager $entityManager, GraphqlConfig $graphqlConfig)
	{
		$this->entityManager = $entityManager;

		parent::__construct($this->entityManager->getBookRepository(), $graphqlConfig);
	}

	/**
	 * @param array<string, mixed> $args
	 * @return array<string, mixed>
	 */
	public function mutationInsert(
		mixed $root,
		array $args,
		mixed $context,
		ResolveInfo $info,
	): array {
		$entity = new Book();
		$this->repository->setEntityData($entity, $this->getDefinitions(), $args);
		$this->repository->persist($entity);
		$this->repository->flush();

		return $entity->toArray();
	}

	/**
	 * @param array<string, mixed> $args
	 * @return array<string, mixed>
	 */
	public function mutationUpdate(
		mixed $root,
		array $args,
		mixed $context,
		ResolveInfo $info,
	): array {
		if (!isset($args['id']) || !\is_int($args['id'])) {
			throw new \InvalidArgumentException("Expected 'id' argument of type int.");
		}

		$id = $args['id'];

		$entity = $this->repository->findById($id);

		if (!($entity instanceof Book)) {
			throw new EntityNotFoundException('Book with id ' . $id . ' not found.');
		}

		//set data
		$this->repository->setEntityData($entity, $this->getDefinitions(), $args);
		$this->repository->flush();

		return $entity->toArray();
	}

	/**
	 * @param array<string, mixed> $args
	 * @return array<string, mixed>
	 */
	public function mutationRemove(
		mixed $root,
		array $args,
		mixed $context,
		ResolveInfo $info,
	): array {
		if (!isset($args['id']) || !\is_int($args['id'])) {
			throw new \InvalidArgumentException("Expected 'id' argument of type int.");
		}

		$id = $args['id'];

		$entity = $this->repository->findById($id);

		if (!($entity instanceof Book)) {
			throw new EntityNotFoundException('Book with id ' . $id . ' not found.');
		}

		//store data only for response
		$response = $entity->toArray();

		//remove record
		$this->repository->remove($entity);
		$this->repository->flush();

		return $response;
	}

	/**
	 * @return array<string, array{callable(mixed): mixed, bool, object|null, string|null}>
	 */
	private function getDefinitions(): array
	{
		return [
			'name' => [fn ($value) => \is_string($value), false, null, null],
			'author' => [fn ($value) => \is_string($value), false, null, null],
			'releaseYear' => [fn ($value) => \is_int($value), false, null, null],
			'genre' => [fn ($value) => \is_string($value), false, null, null],
			'description' => [fn ($value) => \is_string($value), false, null, null],
		];
	}
}
