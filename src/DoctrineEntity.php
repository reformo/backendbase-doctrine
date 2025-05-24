<?php

declare(strict_types=1);

namespace Backendbase\Doctrine;

interface DoctrineEntity
{
    public function toArray(array|null $excludeColumns = [], bool|null $includeDeletedAt = false, bool|null $getId = false): array;
}
