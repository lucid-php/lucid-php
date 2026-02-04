<?php

declare(strict_types=1);

namespace App\DTO;

use Core\Http\ValidatedDTO;
use Core\Attribute\Assert\Required;
use Core\Attribute\Assert\Email;

/**
 * PHP 8.5: Readonly class with clone-with wither methods.
 */
readonly class LoginDTO implements ValidatedDTO
{
    public function __construct(
        #[Required]
        #[Email]
        public string $email,

        #[Required]
        public string $password,
    ) {}

    /**
     * PHP 8.5: Clone-with syntax for immutable updates.
     */
    public function withEmail(string $email): self
    {
        return clone($this, ['email' => $email]);
    }

    public function withPassword(string $password): self
    {
        return clone($this, ['password' => $password]);
    }
}
