<?php

declare(strict_types=1);

namespace Backendbase\Doctrine\Repository;

use AutoMapper\AutoMapper;
use Backendbase\Doctrine\DoctrineEntity;
use Backendbase\Doctrine\EntityId;
use Backendbase\Doctrine\FQCN;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

use function array_unshift;
use function implode;
use function in_array;
use function is_array;
use function is_numeric;
use function is_string;
use function str_contains;
use function str_replace;
use function ucfirst;

readonly class GenericRepository implements \Backendbase\Doctrine\GenericRepository
{
    protected AutoMapper $autoMapper;

    public function __construct(protected EntityManagerInterface $entityManager, protected array $settings)
    {
        $cacheDir = null;
        if (isset($settings['cacheDirectory'])) {
            $cacheDir = $settings['cacheDirectory'];
        }

        $this->autoMapper = AutoMapper::create(cacheDirectory: $cacheDir);
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function getConnection(): Connection
    {
        return $this->entityManager
            ->getConnection();
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->entityManager
            ->createQueryBuilder();
    }

    public function getDBALQueryBuilder(): DBALQueryBuilder
    {
        return $this->entityManager
            ->getConnection()
            ->createQueryBuilder();
    }

    public function getById(FQCN $entityFQCN, EntityId $id): DoctrineEntity|null
    {
        $criteria = [
            'id' => $id->toValue(),
        ];

        return $this->entityManager->getRepository($entityFQCN->toString())
            ->findOneBy($criteria);
    }

    public function getIdFromUuid(FQCN $entityFQCN, string $uuid, FQCN $entityIdFQCN): string|int|null
    {
        $criteria = ['uuid' => $uuid];
        $id       = $this->getPartialByCriteria(
            $entityFQCN->toString(),
            $criteria,
            ['id'],
        );
        if ($id === null) {
            return null;
        }

        return $id->id;
    }

    public function getByCriteria(FQCN $entityFQCN, array $criteria): DoctrineEntity|null
    {
        return $this->entityManager->getRepository($entityFQCN->toString())
            ->findOneBy($criteria);
    }

    public function getPartialByCriteria(FQCN $entityFQCN, array $criteria, array $partialFields, array|null $orderBy = null): object|null
    {
        if (! in_array('id', $partialFields, true)) {
            array_unshift($partialFields, 'id');
        }

        $fields = implode(',', $partialFields);

        $additionalWhereClause = '';
        foreach ($criteria as $field => $value) {
            $additionalWhereClause .= ' AND E.' . $field . ' = :' . $field;
        }

        if ($orderBy !== null) {
            $additionalWhereClause .= ' ORDER BY ';
            $index                  = 0;
            foreach ($orderBy as $field => $value) {
                $index++;
                if ($index > 1) {
                    $additionalWhereClause .= ', ';
                }

                $additionalWhereClause .= 'E.' . $field . ' ' . $value;
            }
        }

        $dql = <<<DQL

            SELECT partial E.{{$fields}}
              FROM {$entityFQCN->toString()} E
              WHERE E.deletedAt IS NULL
              {$additionalWhereClause}

DQL;

        $query = $this->entityManager->createQuery($dql)
            ->setMaxResults(1);

        foreach ($criteria as $field => $value) {
            $query = $query->setParameter($field, $value);
        }

        $result = $query->getArrayResult()[0] ?? null;
        if (empty($result)) {
            return null;
        }

        $result['id'] = is_numeric($result['id']) ? (int) $result['id'] : $result['id'];

        return (object) $result;
    }

    private function setCriteria(QueryBuilder $queryBuilder, array $criteria): QueryBuilder
    {
        foreach ($criteria as $key => $value) {
            if ($value === null) {
                $queryBuilder = $queryBuilder
                    ->andWhere($queryBuilder->expr()->isNull('E.' . $key));
            } else {
                [$expression, $value] = self::findExpression($value);
                $queryBuilder         = $queryBuilder
                    ->andWhere($queryBuilder->expr()->{$expression}('E.' . $key, ':' . $key))
                    ->setParameter($key, $value);
            }
        }

        return $queryBuilder;
    }

    public function getAllCountByCriteria(FQCN $entityFQCN, array $criteria, array|null $orderBy = [], int|null $limit = 25, int|null $offset = 0, array|null $select = ['E'], bool|null $isDistinctValues = false): int
    {
        $queryBuilder = $this->entityManager
            ->createQueryBuilder();
        $query        = $queryBuilder
            ->select('COUNT(E.id)')
            ->from($entityFQCN->toString(), 'E');

        $query = $this->setCriteria($query, $criteria);

        if ($isDistinctValues) {
            $query = $query->distinct();
        }

        $query = $query
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $query->getQuery()
            ->getSingleScalarResult();
    }

    public function getAllByCriteria(FQCN $entityFQCN, array $criteria, array|null $orderBy = [], int|null $limit = 25, int|null $offset = 0, array|null $select = ['E'], bool|null $isDistinctValues = false): array
    {
        $queryBuilder = $this->entityManager
            ->createQueryBuilder();
        $query        = $queryBuilder
            ->select($select)
            ->from($entityFQCN->toString(), 'E');

        $query = $this->setCriteria($query, $criteria);

        foreach ($orderBy as $key => $value) {
            $query = $query->orderBy('E.' . $key, $value);
        }

        if ($isDistinctValues) {
            $query = $query->distinct();
        }

        $query = $query
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $query->getQuery()
            ->getResult();
    }

    private static function findExpression(string|int|bool|float|array|null $value): array
    {
        if ($value === null) {
            return ['isNull', null];
        }

        if (is_array($value)) {
            return ['in', $value];
        }

        if (! is_string($value)) {
            return ['eq', $value];
        }

        if (str_contains($value, '%')) {
            return ['like', $value];
        }

        if (str_contains($value, '!=')) {
            return ['not', str_replace('!=', '', $value)];
        }

        if (str_contains($value, '<=')) {
            return ['lte', str_replace('<=', '', $value)];
        }

        if (str_contains($value, '<')) {
            return ['lt', str_replace('<', '', $value)];
        }

        if (str_contains($value, '>=')) {
            return ['gte', str_replace('>=', '', $value)];
        }

        if (str_contains($value, '>')) {
            return ['gt', str_replace('>', '', $value)];
        }

        return ['eq', $value];
    }

    public function arrayToModel(FQCN $entityFQCN, array $data): DoctrineEntity
    {
        $domainEntity = new $entityFQCN();
        foreach ($data as $key => $value) {
            $setMethod = 'set' . ucfirst($key);
            $domainEntity->{$setMethod}($value);
        }

        return $domainEntity;
    }

    public function beginTransaction(): void
    {
        $this->entityManager->beginTransaction();
    }

    public function commit(): void
    {
        $this->entityManager->commit();
    }

    public function rollBack(): void
    {
        $this->entityManager->rollback();
    }

    public function persist(DoctrineEntity $entity): void
    {
        $this->entityManager->persist($entity);
    }

    public function persistImmediately(DoctrineEntity $entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    public function update(DoctrineEntity $entity): void
    {
        $this->entityManager->persist($entity);
    }

    public function remove(DoctrineEntity $entity): void
    {
        $this->entityManager->remove($entity);
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }

    public function getMaxId(FQCN $entityFQCN, int|null $offset = 0): int
    {
        return $this->entityManager->createQueryBuilder()
                ->select('MAX(e.id)')
                ->from($entityFQCN->toString(), 'e')
                ->getQuery()
                ->getSingleScalarResult() + 1 + $offset;
    }
}
