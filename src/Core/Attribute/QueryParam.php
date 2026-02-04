<?php

declare(strict_types=1);

namespace Core\Attribute;

use Attribute;

/**
 * Mark a controller method parameter to be injected from query string.
 * 
 * Example:
 *   public function index(#[QueryParam] int $page = 1, #[QueryParam] string $sort = 'id') {
 *      // $page comes from ?page=2
 *      // $sort comes from ?sort=name
 *   }
 * 
 * The parameter name must match the query parameter name.
 * Type casting is performed based on the parameter's type hint.
 * Default values are used when the query parameter is not provided.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class QueryParam
{
}
