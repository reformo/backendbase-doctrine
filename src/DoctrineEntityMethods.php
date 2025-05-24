<?php

declare(strict_types=1);

namespace Backendbase\Doctrine;

use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\Mapping\PrePersist;

use function get_object_vars;

use const DATE_ATOM;

trait DoctrineEntityMethods
{
    #[PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public function toArray(array|null $excludeColumns = [], bool|null $includeDeletedAt = false, bool|null $getId = false): array
    {
        if ($getId !== true) {
            $excludeColumns[] = 'id';
        }

        if (! $includeDeletedAt) {
            $excludeColumns[] = 'deletedAt';
        }

        $values =  get_object_vars($this);

        foreach ($excludeColumns as $excludeColumn) {
            unset($values[$excludeColumn]);
        }

        foreach ($values as $key => $value) {
            if (! ($value instanceof DateTimeImmutable)) {
                continue;
            }

            $values[$key] = $value->format(DATE_ATOM);
        }

        return $values;
    }
}
