<?php

declare(strict_types=1);

namespace Core\Attribute\Assert;

use Attribute;
use Core\Http\UploadedFile;

/**
 * Validates uploaded file MIME type against whitelist.
 * 
 * Uses actual MIME type detection (finfo), not client-provided type.
 * Explicit whitelist - no pattern matching or wildcards.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class FileMimeType implements ValidatorRuleInterface
{
    /**
     * @param array<string> $allowed Allowed MIME types
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

        $actualMime = $value->getActualMimeType();
        if ($actualMime === null) {
            return false;
        }

        return in_array($actualMime, $this->allowed, strict: true);
    }

    public function message(string $field): string
    {
        $types = implode(', ', $this->allowed);
        return "Field $field must be one of: $types";
    }
}
