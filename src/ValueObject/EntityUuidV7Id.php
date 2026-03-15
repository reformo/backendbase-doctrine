<?php

declare(strict_types=1);

namespace Backendbase\Doctrine\ValueObject;

use Backendbase\Doctrine\EntityId;
use Backendbase\Doctrine\Exception\InvalidIdValue;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

use function is_int;

readonly class EntityUuidV7Id implements EntityId
{
    public function __construct(private UuidInterface $id)
    {
    }

    public function id(): UuidInterface
    {
        return $this->id;
    }

    public static function generate(): self
    {
        return new self(Uuid::uuid7());
    }

    public static function fromValue(UuidInterface|string|int $id): self
    {
        if (is_int($id)) {
            throw new InvalidIdValue('Invalid UUID value: ' . $id);
        }

        if ($id instanceof UuidInterface) {
            $uuid =  $id;
        } else {
            $uuid = Uuid::fromString($id);
        }

        if ($uuid->getFields()->getVersion() !== 7) {
            throw new InvalidIdValue('Invalid UUID v7 value. : ' . $id->toString());
        }

        return new self($uuid);
    }

    public function toValue(): string|int
    {
        return $this->id()->toString();
    }
}
