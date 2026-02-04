<?php

declare(strict_types=1);

namespace Core\Attribute\Assert;

use Attribute;
use Core\Http\UploadedFile;

/**
 * Validates that a file was uploaded successfully.
 * 
 * Checks both that value is UploadedFile and upload was successful.
 * Explicit file requirement - no assumptions.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class FileRequired implements ValidatorRuleInterface
{
    public function validate(mixed $value): bool
    {
        if (!$value instanceof UploadedFile) {
            return false;
        }

        return $value->isValid();
    }

    public function message(string $field): string
    {
        return "Field $field requires a valid file upload";
    }
}
