<?php

declare(strict_types=1);

namespace App\GraphQL\Resolvers;

use App\GraphQL\GraphqlConfig;
use App\Model\Database\Entity\BaseEntity;
use App\Model\Database\EntityManager;
use App\Model\Database\Repository\BaseRepository;
use App\Model\Exceptions\EntityNotFoundException;
use GraphQL\Type\Definition\ResolveInfo;

/**
 * @template T of BaseRepository
 */
abstract class BaseResolver
{
    /**
     * @var T
     */
    protected $repository;

    protected EntityManager $entityManager;
    protected GraphqlConfig $graphqlConfig;

    /**
     * @param T $repository
     */
    public function __construct(mixed $repository, GraphqlConfig $graphqlConfig)
    {
        $this->repository = $repository;
        $this->graphqlConfig = $graphqlConfig;
    }

    public function getDefaultLangId(): int
    {
        return $this->graphqlConfig->getDefaultLangId();
    }

    /**
     * @param array<string, mixed> $args The arguments.
     * @return array<string, mixed>|null The resolved value.
     */
    public function queryById(mixed $root, array $args, mixed $context, ResolveInfo $info): ?array
    {
        if (!isset($args['id']) || !is_int($args['id'])) {
            throw new \InvalidArgumentException("Expected 'id' argument of type int.");
        }
        /**
         * @var BaseEntity $entity
         */
        $entity = $this->repository->findById($args['id']);

        if (!($entity instanceof BaseEntity)) {
            throw new EntityNotFoundException($this->repository->getEntityName(), $this->repository->getColumnName('id') . ' = ' . $args['id']);
        }
        return $entity->toArray();
    }

    /**
     * @param array<string, mixed> $args
     * @return array<array<string, mixed>>
     */
    public function queryAll(mixed $root, array $args, mixed $context, ResolveInfo $info): array
    {
        $entites = $this->repository->findAll();
        if (!is_array($entites)) {
            throw new EntityNotFoundException($this->repository->getEntityName(), 'all records');
        }
        $result = [];
        foreach ($entites as $key => $entity) {
            if (!($entity instanceof BaseEntity)) {
                throw new EntityNotFoundException($this->repository->getEntityName(), $this->repository->getColumnName('id') . ' = ' . $args['id']);
            }
            $result[$key] = $entity->toArray();
        }
        return $result;
    }

    /**
     * @param array<string, mixed> $args
     * @return array<array<string, mixed>>
     */
    public function queryByIds(mixed $root, array $args, mixed $context, ResolveInfo $info): array
    {
        if (isset($args['ids']) && is_array($args['ids']) && sizeof($args['ids']) > 0) {
            $entites = $this->repository->findByIds($args['ids']);
            if (!is_array($entites)) {
                throw new EntityNotFoundException($this->repository->getEntityName(), $this->repository->getColumnName('id') . ' in (' . implode(',', $args['ids']));
            }
            $result = [];
            foreach ($entites as $key => $entity) {
                if (!($entity instanceof BaseEntity)) {
                    throw new EntityNotFoundException($this->repository->getEntityName(), $this->repository->getColumnName('id') . ' in (' . implode(',', $args['ids']));
                }
                $result[$key] = $entity->toArray();
            }
            return $result;
        }

        return [];
    }
}
