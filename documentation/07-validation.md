# Validation (DTOs)

Validation is performed by injecting a DTO (Data Transfer Object) implementing `ValidatedDTO` into your controller action. The framework automatically:
1. Reads the Request Body (JSON/Form).
2. Hydrates the DTO.
3. Validates using attribute rules.
4. Throws 422 Unprocessable Entity if validation fails.

## Basic Example

```php
use Core\Http\ValidatedDTO;
use Core\Attribute\Assert\Required;
use Core\Attribute\Assert\Email;
use Core\Attribute\Assert\Length;

class CreateUserDTO implements ValidatedDTO
{
    public function __construct(
        #[Required]
        #[Length(min: 2, max: 100)]
        public string $name,

        #[Required]
        #[Email]
        public string $email,
        
        #[Required]
        #[Length(min: 8)]
        public string $password
    ) {}
}

// Controller
public function create(CreateUserDTO $data): Response
{
    // $data is guaranteed valid here
    return Response::json(['name' => $data->name]);
}
```

## Available Validation Rules

### Required & Basic
- `#[Required]` - Field must not be empty
- `#[Email]` - Valid email address
- `#[Length(min: 3, max: 100)]` - String length constraints

### Numeric
- `#[Min(18)]` - Minimum numeric value
- `#[Max(100)]` - Maximum numeric value  
- `#[Range(0, 100)]` - Value within range (inclusive)
- `#[Numeric]` - Must be numeric (int/float/numeric string)
- `#[Integer]` - Must be integer or integer string

### String
- `#[Pattern('/^[A-Z][a-z]+$/')]` - Regex pattern match
- `#[Url]` - Valid URL
- `#[Url(schemes: ['https'])]` - URL with specific scheme
- `#[Alpha]` - Only letters
- `#[Alpha(allowSpaces: true)]` - Letters with spaces
- `#[AlphaNumeric]` - Letters and numbers only

### Type & Format
- `#[Boolean]` - Boolean or boolean-like value
- `#[Json]` - Valid JSON string
- `#[Uuid]` - Valid UUID
- `#[Uuid(version: 4)]` - Specific UUID version

### Comparison
- `#[In(['draft', 'published'])]` - Value in allowed list (whitelist)
- `#[NotIn(['admin', 'root'])]` - Value not in forbidden list (blacklist)

## Complex DTO Example

```php
use Core\Attribute\Assert\*;

class UpdateProductDTO implements ValidatedDTO
{
    public function __construct(
        #[Required]
        #[Length(min: 3, max: 200)]
        public string $name,
        
        #[Required]
        #[Range(0, 99999.99)]
        public float $price,
        
        #[Required]
        #[In(['draft', 'published', 'archived'])]
        public string $status,
        
        #[Url(schemes: ['https'])]
        public ?string $image = null,
        
        #[Json]
        public ?string $metadata = null,
        
        #[Min(0)]
        #[Integer]
        public int $stock = 0
    ) {}
}
```

## Custom Error Messages

Some rules accept custom messages:

```php
#[Pattern('/^\d{4}$/', customMessage: 'Must be a 4-digit PIN')]
public string $pin
```

## Validation Response (422)

```json
{
  "error": "Validation Failed",
  "details": {
    "email": ["The field [email] must be a valid email address."],
    "age": ["The field [age] must be at least 18."],
    "status": ["The field [status] must be one of: 'draft', 'published', 'archived'."]
  }
}
```
