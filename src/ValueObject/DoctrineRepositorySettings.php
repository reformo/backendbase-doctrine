<?php

declare(strict_types=1);

namespace Backendbase\Doctrine\ValueObject;

readonly class DoctrineRepositorySettings
{
    public function __construct(private string|null $cacheDirectory)
    {

    }

    public function cacheDirectory(): ?string
    {
        return $this->cacheDirectory;
    }


}
