<?php

declare(strict_types=1);

namespace Core\Validation;

use Core\Attribute\Assert\ValidatorRuleInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

/**
 * Validator
 * 
 * The validateAndHydrate method is marked with #[\NoDiscard] to ensure
 * the returned DTO is used. This prevents accidentally calling validation
 * without capturing the validated result.
 */
class Validator
{
    /**
     * Hydrate a DTO from an array and validate it.
     *
     * @param class-string $class
     * @param array $data
     * @return object
     * @throws ValidationException
     * @throws ReflectionException
     */
    #[\NoDiscard]
    public function validateAndHydrate(string $class, array $data): object
    {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        
        if (!$constructor) {
             // If no constructor, just return instance (assuming public properties set logic later?)
             // For strict DTOs, we prefer constructor promotion.
             return new $class;
        }

        $args = [];
        $errors = [];

        foreach ($constructor->getParameters() as $parameter) {
            $name = $parameter->getName();
            $value = $data[$name] ?? null; // Null if not present
            
            // 1. Validate
            $parameterErrors = $this->validateParameter($parameter, $value);
            if (!empty($parameterErrors)) {
                $errors[$name] = $parameterErrors;
            }

            // 2. Prepare Argument
            // Only add to args if no errors for this field (or we assume it's valid enough to try casting)
            // Ideally we fail before instantiation.
            if ($value !== null) {
                $args[] = $value;
            } else {
                 // Handle optional/default if needed
                 if ($parameter->isDefaultValueAvailable()) {
                     $args[] = $parameter->getDefaultValue();
                 } elseif ($parameter->allowsNull()) {
                     $args[] = null;
                 } else {
                    // Check if we already caught this as "Required" error?
                    // If not, and it's missing, we can't instantiate.
                    // But if it wasn't marked required, but is missing and no default... that's a PHP error.
                    // For now, let's assume valid strict DTOs have #[Required] or sensible defaults.
                    
                    // If we have errors, we won't instantiate anyway. But we need to fill the array index to avoid offset errors in this loop if we continued.
                    $args[] = null; 
                 }
            }
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // 3. Instantiate
        // Note: This relies on the input data matching the type hints (e.g. string vs int).
        // A smarter hydrator would cast compatible types.
        try {
            return $reflection->newInstanceArgs($args);
        } catch (\TypeError $e) {
            // Catch type error if user sent "abc" for an int field that passed validation (if validation was loose)
            throw new ValidationException(['general' => [$e->getMessage()]]);
        }
    }

    private function validateParameter(ReflectionParameter $parameter, mixed $value): array
    {
        $errors = [];
        $attributes = $parameter->getAttributes();

        foreach ($attributes as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance instanceof ValidatorRuleInterface) {
                if (!$instance->validate($value)) {
                    $errors[] = $instance->message($parameter->getName());
                }
            }
        }

        return $errors;
    }
}
