<?php

declare(strict_types=1);

namespace Core\Validation;

use Exception;

class ValidationException extends Exception
{
    public function __construct(
        public private(set) array $errors, 
        string $message = "Validation Failed"
    ) {
        parent::__construct($message, 422);
    }
}
