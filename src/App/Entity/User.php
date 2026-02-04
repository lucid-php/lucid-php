<?php

declare(strict_types=1);

namespace App\Entity;

class User
{
    public function __construct(
        public private(set) ?int $id,
        public string $name,
        public string $email,
        public string $password, // Hashed
        public ?string $created_at = null,
    ) {}
}
