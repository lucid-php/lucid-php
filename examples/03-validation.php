<?php

declare(strict_types=1);

/**
 * Example 3: Validation with DTOs
 * 
 * Demonstrates:
 * - Attribute-based validation
 * - Data Transfer Objects (DTOs)
 * - Built-in validation rules
 * - Automatic validation in controllers
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Attribute\Route;
use Core\Attribute\Assert\Required;
use Core\Attribute\Assert\Email;
use Core\Attribute\Assert\Length;
use Core\Attribute\Assert\In;
use Core\Attribute\Assert\Url;
use Core\Attribute\Assert\Range;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\ValidatedDTO;

// DTO with validation rules
class CreateUserDTO implements ValidatedDTO
{
    public function __construct(
        #[Required]
        #[Length(min: 3, max: 50)]
        public string $name,
        
        #[Required]
        #[Email]
        public string $email,
        
        #[Required]
        #[Length(min: 8)]
        public string $password,
        
        #[Required]
        #[In(['user', 'admin', 'moderator'])]
        public string $role = 'user',
        
        #[Url]
        public ?string $website = null,
        
        #[Range(min: 18, max: 120)]
        public ?int $age = null
    ) {}
}

class UpdateUserDTO implements ValidatedDTO
{
    public function __construct(
        #[Length(min: 3, max: 50)]
        public ?string $name = null,
        
        #[Email]
        public ?string $email = null,
        
        #[Length(min: 8)]
        public ?string $password = null
    ) {}
}

class CreatePostDTO implements ValidatedDTO
{
    public function __construct(
        #[Required]
        #[Length(min: 5, max: 200)]
        public string $title,
        
        #[Required]
        #[Length(min: 10)]
        public string $content,
        
        #[Required]
        #[In(['draft', 'published', 'archived'])]
        public string $status = 'draft',
        
        public ?array $tags = null,
        
        public ?bool $featured = false
    ) {}
}

class UserController
{
    // The DTO is automatically validated before the method is called
    #[Route('POST', '/users')]
    public function createUser(CreateUserDTO $dto): Response
    {
        // If we reach here, validation passed
        $user = [
            'id' => rand(1, 1000),
            'name' => $dto->name,
            'email' => $dto->email,
            'role' => $dto->role,
            'website' => $dto->website,
            'age' => $dto->age,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        return Response::json([
            'message' => 'User created successfully',
            'user' => $user
        ], 201);
    }
    
    #[Route('PUT', '/users/:id')]
    public function updateUser(UpdateUserDTO $dto): Response
    {
        $updates = array_filter([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $dto->password ? '***hidden***' : null
        ], fn($v) => $v !== null);
        
        return Response::json([
            'message' => 'User updated successfully',
            'updates' => $updates
        ]);
    }
}

echo "Validation Examples:\n";
echo "===================\n\n";

echo "Available Validation Rules:\n";
echo "- #[Required]              - Field must be present and not empty\n";
echo "- #[Email]                 - Must be a valid email address\n";
echo "- #[Length(min:n, max:n)]  - String length constraints\n";
echo "- #[Range(min:n, max:n)]   - Numeric value constraints\n";
echo "- #[In(['a','b'])]         - Value must be one of the listed options\n";
echo "- #[Url]                   - Must be a valid URL\n";
echo "- #[Integer]               - Must be an integer\n";
echo "- #[Boolean]               - Must be a boolean\n";
echo "- #[Numeric]               - Must be numeric\n";
echo "- #[Uuid]                  - Must be a valid UUID\n";
echo "- #[Pattern('/regex/')]    - Must match regex pattern\n";
echo "- #[Alpha]                 - Only alphabetic characters\n";
echo "- #[AlphaNumeric]          - Only alphanumeric characters\n\n";

echo "Test Valid Request:\n";
echo "curl -X POST -H 'Content-Type: application/json' -d '\n";
echo "{\n";
echo "  \"name\": \"John Doe\",\n";
echo "  \"email\": \"john@example.com\",\n";
echo "  \"password\": \"SecurePass123\",\n";
echo "  \"role\": \"user\",\n";
echo "  \"age\": 25\n";
echo "}\n";
echo "' http://localhost:8000/users\n\n";

echo "Test Invalid Request (will return 422 with errors):\n";
echo "curl -X POST -H 'Content-Type: application/json' -d '\n";
echo "{\n";
echo "  \"name\": \"Jo\",\n";
echo "  \"email\": \"invalid-email\",\n";
echo "  \"password\": \"weak\",\n";
echo "  \"role\": \"invalid-role\"\n";
echo "}\n";
echo "' http://localhost:8000/users\n\n";

echo "Error Response Format:\n";
echo "{\n";
echo "  \"error\": \"Validation Failed\",\n";
echo "  \"details\": {\n";
echo "    \"name\": \"The name must be at least 3 characters\",\n";
echo "    \"email\": \"The email must be a valid email address\",\n";
echo "    \"password\": \"The password must be at least 8 characters\",\n";
echo "    \"role\": \"The role must be one of: user, admin, moderator\"\n";
echo "  }\n";
echo "}\n";
