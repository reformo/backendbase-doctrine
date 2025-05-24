<?php

declare(strict_types=1);

namespace Backendbase\Doctrine;

use Ramsey\Uuid\UuidInterface;

interface EntityId
{
    public function id(): UuidInterface|string|int;
    public function toValue(): string|int;
    public static function fromValue(UuidInterface|string|int $id): self;
}
