<?php

declare(strict_types=1);

namespace Core\Http;

class Request
{
    /**
     * @param array<string, UploadedFile> $files Uploaded files keyed by field name
     */
    public function __construct(
        public private(set) string $method,
        public private(set) string $uri,
        public private(set) array $query = [],
        public private(set) array $body = [],
        public private(set) array $server = [],
        public private(set) array $files = [],
        public private(set) array $attributes = [],
    ) {
    }

    public function withAttribute(string $name, mixed $value): self
    {
        $clone = clone $this;
        $clone->attributes[$name] = $value;
        return $clone;
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    public function getQueryParam(string $name, mixed $default = null): mixed
    {
        return $this->query[$name] ?? $default;
    }

    /**
     * Get uploaded file by field name.
     * 
     * @return UploadedFile|array<UploadedFile>|null Single file, array of files, or null
     */
    public function getFile(string $name): UploadedFile|array|null
    {
        return $this->files[$name] ?? null;
    }

    /**
     * Check if request has an uploaded file.
     */
    public function hasFile(string $name): bool
    {
        return isset($this->files[$name]);
    }

    public static function createFromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Parse raw body for JSON content
        $body = $_POST;
        if ($method === 'POST' || $method === 'PUT' || $method === 'PATCH') {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (str_contains($contentType, 'application/json')) {
                $input = file_get_contents('php://input');
                $decoded = json_decode($input, true);
                if (is_array($decoded)) {
                    $body = array_merge($body, $decoded);
                }
            }
        }

        // Parse uploaded files
        $files = [];
        foreach ($_FILES as $fieldName => $fileData) {
            // Handle multiple files (array of files)
            if (is_array($fileData['name'])) {
                $files[$fieldName] = [];
                $fileCount = count($fileData['name']);
                for ($i = 0; $i < $fileCount; $i++) {
                    $files[$fieldName][] = UploadedFile::fromArray([
                        'name' => $fileData['name'][$i],
                        'tmp_name' => $fileData['tmp_name'][$i],
                        'size' => $fileData['size'][$i],
                        'error' => $fileData['error'][$i],
                        'type' => $fileData['type'][$i] ?? null,
                    ]);
                }
            } else {
                // Single file
                $files[$fieldName] = UploadedFile::fromArray($fileData);
            }
        }

        return new self(
            method: $method,
            uri: $uri,
            query: $_GET,
            body: $body,
            server: $_SERVER,
            files: $files,
        );
    }
}
