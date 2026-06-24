<?php

declare(strict_types=1);

namespace Core\Database;

abstract class AbstractRepository
{
    public function __construct(final protected Database $db)
    {
    }
}
