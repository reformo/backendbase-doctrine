<?php

declare(strict_types=1);

namespace Backendbase\Doctrine\ValueObject;

use Backendbase\Doctrine\EntityId;
use Backendbase\Doctrine\Exception\InvalidIdValue;
use Ramsey\Uuid\UuidInterface;
use function is_int;

readonly class EntityIntId implements EntityId
{
    public function __construct(private int $id)
    {
    }

    public function id(): int
    {
        return $this->id;
    }

    public static function fromValue(UuidInterface|int|string $id): EntityId
    {
        if (! is_int($id)) {
            throw new InvalidIdValue('Invalid int value: ' . $id);
        }

        return new self($id);
    }

    public function toValue(): string|int
    {
        return $this->id();
    }
}
