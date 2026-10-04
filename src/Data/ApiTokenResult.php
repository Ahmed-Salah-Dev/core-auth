<?php

declare(strict_types=1);

namespace AhmedSalahDev\CoreAuth\Data;

use DateTimeInterface;

final readonly class ApiTokenResult
{
    public function __construct(
        public int|string $id,
        public string $name,
        public string $token,
        public ?DateTimeInterface $expiresAt = null,
    ) {
    }
}
