<?php

declare(strict_types=1);

namespace App\DTO;

use Core\Attribute\Assert\Email;
use Core\Attribute\Assert\Length;
use Core\Attribute\Assert\Required;
use Core\Http\ValidatedDTO;

/**
 * PHP 8.5: Readonly class with clone-with wither methods.
 * Clone-with reduces boilerplate in immutable value objects.
 */
readonly class CreateUserDTO implements ValidatedDTO
{
    public function __construct(
        #[Required]
        public string $name,

        #[Required]
        #[Email]
        public string $email,

        #[Required]
        #[Length(min: 8)]
        public string $password
    ) {}

    /**
     * PHP 8.5: Using clone-with syntax for immutable updates.
     * Before: new self(name: $name, email: $this->email, password: $this->password)
     * After: clone($this, ['name' => $name])
     */
    public function withName(string $name): self
    {
        return clone($this, ['name' => $name]);
    }

    public function withEmail(string $email): self
    {
        return clone($this, ['email' => $email]);
    }

    public function withPassword(string $password): self
    {
        return clone($this, ['password' => $password]);
    }
}
