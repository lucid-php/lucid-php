<?php

declare(strict_types=1);

namespace Core\Attribute\Assert;

use Attribute;
use Core\Http\UploadedFile;

/**
 * Validates that an uploaded file does not exceed maximum size.
 * 
 * Explicit validation - no hidden size limits.
 * Works with UploadedFile value objects only.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class FileMaxSize implements ValidatorRuleInterface
{
    /**
     * @param int $maxBytes Maximum file size in bytes
     */
    public function __construct(
        private readonly int $maxBytes,
    ) {
    }

    public function validate(mixed $value): bool
    {
        if (!$value instanceof UploadedFile) {
            return false;
        }

        if (!$value->isValid()) {
            return false;
        }

        return $value->size <= $this->maxBytes;
    }

    public function message(string $field): string
    {
        $maxMb = round($this->maxBytes / 1024 / 1024, 2);
        return "Field $field must not exceed {$maxMb}MB";
    }
}
