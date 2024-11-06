<?php

declare(strict_types=1);

namespace App\Model\Database\Repository;

use App\Model\Database\Entity\BaseEntity;
use App\Model\Exceptions\EntityNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ObjectRepository;

/**
 * @template TEntity of BaseEntity
 * @extends  EntityRepository<TEntity>
 * @template TMetadata of ClassMetadata<TEntity>
 * @implements ObjectRepository<TEntity>
 */

abstract class BaseRepository extends EntityRepository implements ObjectRepository
{
    const SEPARATOR_ALIAS = '__';

    /** @var ClassMetadata */
    protected ClassMetadata $classMetadata;

    private EntityManagerInterface $entityManager;

    /**
     * @param ClassMetadata<TEntity> $classMetadata
     */
    public function __construct(EntityManagerInterface $entityManager, ClassMetadata $classMetadata)
    {
        $this->entityManager = $entityManager;
        $this->classMetadata = $classMetadata;

        parent::__construct($entityManager, $classMetadata);
    }
    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * @throws EntityNotFoundException
     */
    public function get(int $id): BaseEntity
    {
        /**
         * @var BaseEntity|null $entity
         */
        $entity = $this->findById($id);
        if ($entity === null) {
            throw new EntityNotFoundException($this->getEntityName(), 'id = ' . $id);
        }
        assert($entity instanceof BaseEntity);
        return $entity;
    }

    /**
     * @throws EntityNotFoundException
     */
    public function findById(int $id, bool $isMandatory = true): ?BaseEntity
    {
        /**
         * @var BaseEntity|null $entity
         */
        $entity = parent::findOneBy(['id' => $id]);

        if ($isMandatory && !($entity instanceof BaseEntity)) {
            throw new EntityNotFoundException($this->getEntityName(), $this->getColumnName('id') . ' = ' . $id);
        }

        if ($isMandatory) {
            assert($entity instanceof BaseEntity);
        }

        return $entity;
    }

    /**
     * @param  int[] $ids
     * @return BaseEntity[]
     */
    public function findByIds(array $ids): array
    {
        /**
         * @var BaseEntity[] $entities
         */
        $entities = parent::findBy(['id' => $ids]);

        if (!is_array($entities)) {
            throw new EntityNotFoundException($this->getEntityName(), $this->getColumnName('id') . ' in (' . implode(',', $ids) . ')');
        }

        foreach ($entities as $entity) {
            assert($entity instanceof BaseEntity);
        }
        return $entities;
    }

    /**
     * @return BaseEntity[]
     */
    public function findAll(): array
    {
        /**
         * @var BaseEntity[] $entities
         */
        $entities = parent::findAll();

        if (!is_array($entities)) {
            throw new EntityNotFoundException($this->getEntityName(), 'all records');
        }

        foreach ($entities as $entity) {
            assert($entity instanceof BaseEntity);
        }
        return $entities;
    }

    public function remove(BaseEntity $entity): void
    {
        $this->_em->remove($entity);
    }

    public function persist(BaseEntity $entity): void
    {
        $this->_em->persist($entity);
    }

    public function flush(): void
    {
        $this->_em->flush();
    }

    public function getColumnName(string $propertyName): string
    {
        return $this->_class->getColumnName($propertyName);
    }

    public function getEntityName(): string
    {
        return parent::getEntityName();
    }

    /**
     * @param array<string, array{callable(mixed): mixed, bool, object|null, string|null}> $definition
     * @param array<string, mixed> $values
     */
    public function setEntityData(BaseEntity $entity, array $definition, array $values): void
    {
        foreach ($definition as $argName => $def) {
            if ($def[1] && !isset($values[$argName])) {
                throw new \InvalidArgumentException("'{$argName}' is required.");
            }
            if (isset($values[$argName]) && !$def[0]($values[$argName])) {
                throw new \InvalidArgumentException("Expected '{$argName}' to be an " . gettype($def[0]));
            }

            $setMethodName = 'set' . ucfirst($argName);

            if (isset($values[$argName]) && !isset($def[2])) {
                $entity->$setMethodName($values[$argName]);
            }

            if (isset($values[$argName]) && is_numeric($values[$argName]) && isset($def[2])) {
                $id = (int) $values[$argName];


                /**
                 * @var BaseRepository<BaseEntity, ClassMetadata<BaseEntity>> $repo
                 */
                $repo = $def[2];
                $columnEntity = $repo->findById($id);

                if (!($columnEntity instanceof BaseEntity)) {
                    if ($def[3] === null) {
                        continue;
                    }
                    throw new EntityNotFoundException($this->getEntityName(), $this->getColumnName($def[3]) . ' = ' . $id);
                }
                $entity->$setMethodName($columnEntity);
            }
        }
    }

    /**
     * @param  BaseEntity[] $entities
     * @param class-string $entityClassName
     * @return array<int, array<string, mixed>>
     * @throws EntityNotFoundException
     */
    protected function mapEntitiesToResponse(array $entities, string $entityClassName): array
    {
        $response = [];
        foreach ($entities as $key => $entity) {
            if (!($entity instanceof $entityClassName)) {
                throw new EntityNotFoundException($entityClassName, "mapEntitiesToResponse");
            }
            $response[$key] = $entity->toArray();
        }
        return $response;
    }


    /**
     * @param class-string $entityClassName
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected function addResultSetMapping(ResultSetMapping $rsm, string $entityClassName, string $entityAlias): ResultSetMapping
    {
        $rsm->addEntityResult($entityClassName, $entityAlias);
        $rsm = $this->addFieldResultSetMapping($rsm, $entityClassName, $entityAlias);
        return $this->addKeysResultSetMapping($rsm, $entityClassName, $entityAlias);
    }

    /**
     * @param class-string $entityClassName
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected function addJoinResultSetMapping(ResultSetMapping $rsm, string $entityClassName, string $entityAlias, string $parentAlias, string $relation): ResultSetMapping
    {
        $rsm->addJoinedEntityResult($entityClassName, $entityAlias, $parentAlias, $relation);
        $rsm = $this->addFieldResultSetMapping($rsm, $entityClassName, $entityAlias);
        return $this->addKeysResultSetMapping($rsm, $entityClassName, $entityAlias);
    }

    /**
     * @param class-string $entityClassName
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected function addFieldResultSetMapping(ResultSetMapping $rsm, string $entityClassName, string $entityAlias): ResultSetMapping
    {
        $metadata = $this->getEntityManager()->getClassMetadata($entityClassName);
        foreach ($metadata->fieldMappings as $fieldMapping) {
            if (empty($fieldMapping['id'])) {
                $rsm->addFieldResult($entityAlias, $fieldMapping['columnName'], $fieldMapping['fieldName']);
            }
        }
        return $rsm;
    }

    /**
     * @param class-string $entityClassName
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    protected function addKeysResultSetMapping(ResultSetMapping $rsm, string $entityClassName, string $entityAlias): ResultSetMapping
    {
        $classMetadata = $this->getEntityManager()->getClassMetadata($entityClassName);

        foreach ($classMetadata->fieldMappings as $fieldMapping) {
            if (!empty($fieldMapping['id'])) {
                $rsm->addMetaResult($entityAlias, $fieldMapping['columnName'], $fieldMapping['fieldName'], false);
            }
        }

        foreach ($classMetadata->associationMappings as $associationMapping) {
            if (isset($associationMapping['joinColumns']) && in_array($associationMapping['type'], [ClassMetadataInfo::MANY_TO_ONE, ClassMetadataInfo::ONE_TO_ONE])) {
                foreach ($associationMapping['joinColumns'] as $joinColumn) {
                    if (!empty($fieldMapping['id'])) {
                        $rsm->addMetaResult($entityAlias, $joinColumn['name'], $associationMapping['fieldName']);
                    } else {
                        $rsm->addMetaResult($entityAlias, $joinColumn['name'], $associationMapping['fieldName'], true);
                    }
                }
            }
        }
        return $rsm;
    }

    /** return array, where key = column name and value is true for asociation/join column
     * @param class-string $entityClassName
     * @return array<string, bool>
     */
    public function getColumnNames(string $entityClassName, string $tableAlias = '', bool $addColumnNameAlias = false): array
    {
        /** @var ClassMetadataInfo $classMetadata */
        $classMetadata = $this->getEntityManager()->getClassMetadata($entityClassName);

        $prefix = '';
        if ($tableAlias !== '') {
            $prefix = $tableAlias . '.';
        }

        $columnNames = [];

        foreach ($classMetadata->fieldMappings as $fieldMapping) {
            $postfix = $this->getColumnAlias($addColumnNameAlias, $tableAlias, $fieldMapping['columnName']);
            $columnNames[$prefix . $fieldMapping['columnName'] . $postfix] = false;
        }

        foreach ($classMetadata->associationMappings as $associationMapping) {
            if (!$associationMapping['isOwningSide'] || isset($associationMapping['joinTable'])) {
                continue;
            }
            if (isset($associationMapping['joinColumns']) && in_array($associationMapping['type'], [ClassMetadataInfo::MANY_TO_ONE, ClassMetadataInfo::ONE_TO_ONE])) {
                foreach ($associationMapping['joinColumns'] as $joinColumn) {
                    $postfix = $this->getColumnAlias($addColumnNameAlias, $tableAlias, $joinColumn['name']);
                    $columnNames[$prefix . $joinColumn['name'] . $postfix] = true;
                }
            }
        }
        return $columnNames;
    }

    private function getColumnAlias(bool $addColumnNameAlias, string $tableAlias, string $columnName, bool $addAs = true): string
    {
        if (!$addColumnNameAlias) {
            return '';
        }
        $nameParts = explode('.', $columnName);
        $columnName = end($nameParts);

        return ($addAs ? ' AS ' : '') . $tableAlias . '__' . $columnName;
    }

    /**
     * @param class-string $entityClassName
     */
    public function addSerializeColumnNames(string $columnNameStr, string $entityClassName, string $tableAlias = '', bool $addColumnNamePostfix = false): string
    {
        $newCols = implode(', ', array_keys($this->getColumnNames($entityClassName, $tableAlias, $addColumnNamePostfix)));
        if ($columnNameStr) {
            $columnNameStr .= ', ' . $newCols;
        } else {
            $columnNameStr = $newCols;
        }
        return $columnNameStr;
    }

    /**
     * @param string $sql The SQL query to execute.
     * @param array<string|int, array<int, int>|\DateTime|string|int|float|bool|\DateTimeInterface|null> $parameters The parameters to bind to the query.
     * @param ResultSetMapping|null $rsm The result set mapping to use.
     * @return mixed The result of the query.
     * @throws \Doctrine\ORM\ORMException If there is a problem executing the query.
     */
    public function executeNativeQuery(string $sql, array $parameters = [], ?ResultSetMapping $rsm = null): mixed
    {
        if ($rsm === null) {
            $rsm = new ResultSetMapping();
        }
        $query = $this->getEntityManager()->createNativeQuery($sql, $rsm);

        foreach ($parameters as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                $sqlValue = $value->format('Y-m-d H:i:s');
                $query->setParameter($key, $sqlValue);
            } else {
                $query->setParameter($key, $value);
            }
        }

        try {
            return $query->getScalarResult();
            //return $query->getResult();
        } catch (ORMException $e) {
            // Handle exception or log it
            throw $e;
        }
    }

    /**
     * Represents the structure of join mappings.
     *
     * joinMappings structure: //TODO: here add interface and use it
     * [
     *     [{entityClassName}, {tableAlias}, {parentTableAlias}, {relation}, {colums}],
     *     ...
     * ]
     *
     * - entityClassName: The class name of the joined entity.
     * - tableAlias: The unique alias to use for the joined entity.
     * - parentTableAlias: The alias of the entity result that is the parent of this joined result.
     * - relation: The association field that connects the parent entity result.
     * - columns: The columns to select from the joined entity.
     *
     * @param array<array<mixed>> $joinMapings
     */
    public function createResultSetMaping(array $joinMapings, bool $addScalarResult = false, bool $addColumnNameAlias = false): ResultSetMapping
    {
        /** @var ResultSetMapping */
        $rsm = new ResultSetMapping();
        foreach ($joinMapings as $joinParams) {
            if (!isset($joinParams[0]) || !is_string($joinParams[0]) || !class_exists($joinParams[0])) {
                throw new \InvalidArgumentException("Entity class in joinMapings does not correspond to any existing class.");
            }
            $entityClassName = $joinParams[0];
            $tableAlias = $joinParams[1] ?? '';
            $parentTableAlias = $joinParams[2] ?? '';
            $relation = $joinParams[3] ?? '';

            if ($parentTableAlias === '') {
                $rsm = $this->addResultSetMapping($rsm, $entityClassName, $tableAlias);
            } else {
                $rsm = $this->addJoinResultSetMapping($rsm, $entityClassName, $tableAlias, $parentTableAlias, $relation);
            }
            if ($addScalarResult) {
                $cols = $joinParams[4] ?? $this->getColumnNames($entityClassName, $tableAlias, false);

                foreach (array_keys($cols) as $colName) {
                    if (!is_string($colName) || !is_string($tableAlias) || !is_string($entityClassName) || !is_bool($addColumnNameAlias)) {
                        throw new \InvalidArgumentException("Invalid type of parameters in createResultSetMaping");
                    }
                    $alias = $this->getColumnAlias($addColumnNameAlias, $tableAlias, $colName, false);
                    $rsm->addScalarResult($alias, $alias);
                }
            }
        }
        return $rsm;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function extractEntityData(array $row, string $tableAlias): array
    {
        $data = [];
        foreach ($row as $colName => $value) {
            if (strpos($colName, $tableAlias . self::SEPARATOR_ALIAS) === 0) {
                $data[str_replace($tableAlias . self::SEPARATOR_ALIAS, '', $colName)] = $value;
            }
        }
        return $data;
    }

    /**
     * Get all sql columns from entities
     *
     * entitiesAndAliases structure:
     * [
     *     [{entityClassName}, {tableAlias}],
     *     ...
     * ]
     *
     * - entityClassName: The class name of the joined entity.
     * - tableAlias: The unique alias to use for the joined entity.
     *
     * @param array<array<mixed>> $entitiesAndAliases An array containing arrays representing entities and their aliases.
     */
    public function getSerializeColumnNames(array $entitiesAndAliases, bool $addColumnNamePostfix = false): string
    {
        $queryColumns = '';
        foreach ($entitiesAndAliases as $entityParams) {
            if (!isset($entityParams[0]) || !is_string($entityParams[0]) || !class_exists($entityParams[0])) {
                throw new \InvalidArgumentException("Entity class in entities does not correspond to any existing class.");
            }
            $entityClassName = $entityParams[0];
            $tableAlias = $entityParams[1] ?? '';

            $queryColumns = $this->addSerializeColumnNames($queryColumns, $entityClassName, $tableAlias, $addColumnNamePostfix);
        }
        return $queryColumns;
    }

    private function isValidType(mixed $value): bool
    {
        if (is_null($value)) {
            return true;
        }
        //array<int, int>
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                if (!is_int($key) || !is_int($item)) {
                    return false;
                }
            }
            return true;
        }
        $type = gettype($value);
        $output = in_array($type, ['integer', 'string', 'double', 'boolean']) || $value instanceof \DateTime || $value instanceof \DateTimeInterface;
        return $output;
    }

    /**
     * @param array<mixed> $array
     */
    public function checkArrayTypes(array $array): bool
    {
        foreach ($array as $value) {
            if (!$this->isValidType($value)) {
                return false;
            }
        }
        return true;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string|int, mixed>
     */
    public function getEntityDataFromRow(array $row, string $alias): array
    {
        $data = array_filter($row, function ($key) use ($alias) {
            return strpos($key, $alias . '_') === 0;
        }, ARRAY_FILTER_USE_KEY);

        $newKeys = array_map(function ($key) use ($alias) {
            return str_replace($alias . '_', '', $key);
        }, array_keys($data));

        return array_combine($newKeys, array_values($data));
    }

    /**
     * @param array<array<string, mixed>> $rows
     * @return array<array<string|int, mixed>>
     */
    public function getEntityDataFromRows(array $rows, string $alias): array
    {
        if (empty($rows)) {
            return [];
        }
        $output = [];
        foreach ($rows as $row) {
            $output[] = $this->getEntityDataFromRow($row, $alias);
        }
        return $output;
    }
}
