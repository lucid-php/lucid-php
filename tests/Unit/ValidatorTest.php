<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Validation\Validator;
use Core\Validation\ValidationException;
use Core\Http\ValidatedDTO;
use Core\Attribute\Assert\Required;
use Core\Attribute\Assert\Email;
use Core\Attribute\Assert\Length;
use PHPUnit\Framework\TestCase;

class TestDTO implements ValidatedDTO
{
    public function __construct(
        #[Required]
        public string $name,

        #[Required]
        #[Email]
        public string $email,

        #[Length(min: 8)]
        public string $password
    ) {}
}

class ValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function testValidDataPassesValidation(): void
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secure123'
        ];

        $dto = $this->validator->validateAndHydrate(TestDTO::class, $data);

        $this->assertInstanceOf(TestDTO::class, $dto);
        $this->assertSame('John Doe', $dto->name);
        $this->assertSame('john@example.com', $dto->email);
    }

    public function testMissingRequiredFieldThrowsValidationException(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'email' => 'john@example.com',
            'password' => 'secure123'
        ];

        (void) $this->validator->validateAndHydrate(TestDTO::class, $data);
    }

    public function testInvalidEmailThrowsValidationException(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'name' => 'John Doe',
            'email' => 'not-an-email',
            'password' => 'secure123'
        ];

        (void) $this->validator->validateAndHydrate(TestDTO::class, $data);
    }

    public function testLengthValidationFails(): void
    {
        $this->expectException(ValidationException::class);

        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'short'
        ];

        (void) $this->validator->validateAndHydrate(TestDTO::class, $data);
    }
}
