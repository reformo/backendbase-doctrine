<?php

declare(strict_types=1);

namespace Backendbase\Doctrine\ValueObject;

use Backendbase\Doctrine\EntityId;
use Backendbase\Doctrine\Exception\InvalidIdValue;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;
use function is_int;

readonly class EntityUuidId implements EntityId
{
    public function __construct(private UuidInterface $id)
    {
    }

    public function id(): UuidInterface
    {
        return $this->id;
    }

    public static function fromValue(UuidInterface|string|int $id): self
    {
        if (is_int($id)) {
            throw new InvalidIdValue('Invalid UUID value: ' . $id);
        }

        if ($id instanceof UuidInterface) {
            return new self($id);
        }

        return new self(Uuid::fromString($id));
    }

    public function toValue(): string|int
    {
        return $this->id()->toString();
    }
}
