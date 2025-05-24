<?php

declare(strict_types=1);

namespace Backendbase\Doctrine\ValueObject;

readonly class FQCN implements \Backendbase\Doctrine\FQCN
{
    public function __construct(private string $fqcn)
    {
    }

    public function toString(): string
    {
        return $this->fqcn;
    }
}
