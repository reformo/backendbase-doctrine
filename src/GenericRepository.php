<?php

declare(strict_types=1);

namespace Backendbase\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder as DBALQueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

interface GenericRepository
{
    public function getEntityManager(): EntityManagerInterface;

    public function getConnection(): Connection;

    public function getQueryBuilder(): QueryBuilder;

    public function getDBALQueryBuilder(): DBALQueryBuilder;

    public function getIdFromUuid(FQCN $entityFQCN, string $uuid, FQCN $entityIdFQCN): string|int|null;

    public function getById(FQCN $entityFQCN, EntityId $id): DoctrineEntity|null;

    public function getByCriteria(FQCN $entityFQCN, array $criteria): DoctrineEntity|null;

    public function getPartialByCriteria(FQCN $entityFQCN, array $criteria, array $partialFields, array|null $orderBy = null): object|null;

    public function getAllByCriteria(FQCN $entityFQCN, array $criteria, array|null $orderBy = [], int|null $limit = 25, int|null $offset = 0, array|null $select = ['E']): array;

    public function getAllCountByCriteria(FQCN $entityFQCN, array $criteria, array|null $orderBy = [], int|null $limit = 25, int|null $offset = 0, array|null $select = ['E']): int;

    public function arrayToModel(FQCN $entityFQCN, array $data): DoctrineEntity;

    public function beginTransaction(): void;

    public function commit(): void;

    public function rollBack(): void;

    public function persist(DoctrineEntity $entity): void;

    public function persistImmediately(DoctrineEntity $entity): void;

    public function update(DoctrineEntity $entity): void;

    public function remove(DoctrineEntity $entity): void;

    public function flush(): void;

    public function getMaxId(FQCN $entityFQCN, int|null $offset = 0): int;
}
