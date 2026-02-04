<?php

declare(strict_types=1);

namespace Core\Database;

abstract class AbstractRepository
{
    public function __construct(protected final Database $db) {}
}
