<?php

declare(strict_types=1);

namespace App\Repositories;

interface UserRepository
{
    public function create(string $email, string $password): object;
    public function findByEmail(string $email);
    public function find(int $id);
    public function all(): array;
}
