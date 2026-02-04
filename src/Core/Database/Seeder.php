<?php

declare(strict_types=1);

namespace Core\Database;

abstract class Seeder
{
    public function __construct(protected Database $db)
    {
    }

    abstract public function run(): void;
}
