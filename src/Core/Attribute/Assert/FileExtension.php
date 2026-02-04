<?php

declare(strict_types=1);

namespace Core\Attribute\Assert;

use Attribute;
use Core\Http\UploadedFile;

/**
 * Validates uploaded file extension against whitelist.
 * 
 * Explicit extension whitelist - no guessing allowed.
 * Extensions are case-insensitive.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class FileExtension implements ValidatorRuleInterface
{
    /**
     * @param array<string> $allowed Allowed file extensions (without dots)
     */
    public function __construct(
        private readonly array $allowed,
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

        $extension = $value->getExtension();
        $allowedLower = array_map('strtolower', $this->allowed);

        return in_array($extension, $allowedLower, strict: true);
    }

    public function message(string $field): string
    {
        $extensions = implode(', ', $this->allowed);
        return "Field $field must be one of: $extensions";
    }
}
