<?php

declare(strict_types=1);

namespace Core\Console;

/**
 * Marker interface for console commands.
 * Commands must have an execute() method, but signature is flexible
 * to allow explicit argument/option declarations via attributes.
 */
interface CommandInterface
{
    // No method signature - commands define their own execute() with explicit parameters
}
