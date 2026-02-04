<?php

declare(strict_types=1);

namespace Core\Collection;

use Countable;
use IteratorAggregate;
use Traversable;
use ArrayIterator;

/**
 * Collection - Fluent Array Operations
 * 
 * Provides explicit, type-safe array manipulation with fluent API.
 * No global helpers - explicit instantiation required.
 * 
 * Philosophy Compliance:
 * - Zero Magic: Explicit `new Collection(...)` or `Collection::make(...)`
 * - Strict Typing: All methods typed with proper return types
 * - Traceable: Command+Click works on all methods
 * - Immutable: Most operations return new Collection instance
 * - No Hidden Behavior: All transformations are explicit method calls
 * 
 * Usage:
 *   $collection = new Collection([1, 2, 3, 4, 5]);
 *   $result = $collection
 *       ->filter(fn($n) => $n > 2)
 *       ->map(fn($n) => $n * 2)
 *       ->toArray(); // [6, 8, 10]
 */
class Collection implements Countable, IteratorAggregate
{
    /**
     * @param array<int|string, mixed> $items
     */
    public function __construct(
        private array $items = []
    ) {
    }

    /**
     * Static factory for fluent instantiation
     * 
     * @param array<int|string, mixed> $items
     */
    public static function make(array $items = []): self
    {
        return new self($items);
    }

    /**
     * Filter items using callback
     * 
     * @param callable(mixed, int|string): bool $callback
     */
    public function filter(callable $callback): self
    {
        return new self(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * Transform each item using callback
     * 
     * @param callable(mixed, int|string): mixed $callback
     */
    public function map(callable $callback): self
    {
        $keys = array_keys($this->items);
        $items = array_map($callback, $this->items, $keys);
        
        return new self(array_combine($keys, $items));
    }

    /**
     * Reduce collection to single value
     * 
     * @template TReduce
     * @param callable(TReduce, mixed): TReduce $callback
     * @param TReduce $initial
     * @return TReduce
     */
    public function reduce(callable $callback, mixed $initial = null): mixed
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Get first item (or first matching callback)
     * 
     * @param callable(mixed, int|string): bool|null $callback
     */
    public function first(?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            return array_first($this->items) ?? $default;
        }

        foreach ($this->items as $key => $item) {
            if ($callback($item, $key)) {
                return $item;
            }
        }

        return $default;
    }

    /**
     * Get last item (or last matching callback)
     * 
     * @param callable(mixed, int|string): bool|null $callback
     */
    public function last(?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            return array_last($this->items) ?? $default;
        }

        $items = array_reverse($this->items, true);
        foreach ($items as $key => $item) {
            if ($callback($item, $key)) {
                return $item;
            }
        }

        return $default;
    }

    /**
     * Pluck a single column from items
     */
    public function pluck(string $key): self
    {
        $results = [];
        
        foreach ($this->items as $item) {
            if (is_array($item) && isset($item[$key])) {
                $results[] = $item[$key];
            } elseif (is_object($item) && isset($item->$key)) {
                $results[] = $item->$key;
            }
        }
        
        return new self($results);
    }

    /**
     * Group items by key or callback
     * 
     * @param string|callable(mixed, int|string): mixed $groupBy
     */
    public function groupBy(string|callable $groupBy): self
    {
        $results = [];

        foreach ($this->items as $key => $item) {
            $groupKey = is_callable($groupBy) 
                ? $groupBy($item, $key)
                : (is_array($item) ? $item[$groupBy] : $item->$groupBy);

            $results[$groupKey][] = $item;
        }

        return new self($results);
    }

    /**
     * Sort items using callback
     * 
     * @param callable(mixed, mixed): int|null $callback
     */
    public function sort(?callable $callback = null): self
    {
        $items = $this->items;

        if ($callback) {
            uasort($items, $callback);
        } else {
            asort($items);
        }

        return new self($items);
    }

    /**
     * Sort items by key
     */
    public function sortKeys(int $flags = SORT_REGULAR): self
    {
        $items = $this->items;
        ksort($items, $flags);
        return new self($items);
    }

    /**
     * Reverse items
     */
    public function reverse(): self
    {
        return new self(array_reverse($this->items, true));
    }

    /**
     * Get unique items
     */
    public function unique(int $flags = SORT_REGULAR): self
    {
        return new self(array_unique($this->items, $flags));
    }

    /**
     * Reset array keys to sequential integers
     */
    public function values(): self
    {
        return new self(array_values($this->items));
    }

    /**
     * Get keys as new collection
     */
    public function keys(): self
    {
        return new self(array_keys($this->items));
    }

    /**
     * Chunk items into arrays of given size
     */
    public function chunk(int $size): self
    {
        $chunks = [];
        
        foreach (array_chunk($this->items, $size, true) as $chunk) {
            $chunks[] = $chunk;
        }

        return new self($chunks);
    }

    /**
     * Take first n items
     */
    public function take(int $limit): self
    {
        if ($limit < 0) {
            return $this->slice($limit, abs($limit));
        }

        return $this->slice(0, $limit);
    }

    /**
     * Skip first n items
     */
    public function skip(int $offset): self
    {
        return $this->slice($offset);
    }

    /**
     * Slice items (array_slice wrapper)
     */
    public function slice(int $offset, ?int $length = null): self
    {
        return new self(array_slice($this->items, $offset, $length, true));
    }

    /**
     * Merge with another array or collection
     * 
     * @param array<int|string, mixed>|self $items
     */
    public function merge(array|self $items): self
    {
        $mergeItems = $items instanceof self ? $items->toArray() : $items;
        return new self(array_merge($this->items, $mergeItems));
    }

    /**
     * Flatten multi-dimensional array
     */
    public function flatten(int $depth = PHP_INT_MAX): self
    {
        $result = [];

        foreach ($this->items as $item) {
            if (!is_array($item)) {
                $result[] = $item;
            } elseif ($depth === 1) {
                $result = array_merge($result, array_values($item));
            } else {
                $result = array_merge($result, (new self($item))->flatten($depth - 1)->toArray());
            }
        }

        return new self($result);
    }

    /**
     * Check if any item matches callback
     * 
     * @param callable(mixed, int|string): bool $callback
     */
    public function some(callable $callback): bool
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if all items match callback
     * 
     * @param callable(mixed, int|string): bool $callback
     */
    public function every(callable $callback): bool
    {
        foreach ($this->items as $key => $item) {
            if (!$callback($item, $key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Execute callback for each item (side effects)
     * 
     * @param callable(mixed, int|string): void $callback
     */
    public function each(callable $callback): self
    {
        foreach ($this->items as $key => $item) {
            $callback($item, $key);
        }

        return $this;
    }

    /**
     * Tap into collection for debugging without breaking chain
     * 
     * @param callable(self): void $callback
     */
    public function tap(callable $callback): self
    {
        $callback($this);
        return $this;
    }

    /**
     * Get item by key
     */
    public function get(int|string $key, mixed $default = null): mixed
    {
        return $this->items[$key] ?? $default;
    }

    /**
     * Check if key exists
     */
    public function has(int|string $key): bool
    {
        return array_key_exists($key, $this->items);
    }

    /**
     * Check if collection is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Check if collection is not empty
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Get sum of all items (or specific key)
     */
    public function sum(?string $key = null): int|float
    {
        if ($key === null) {
            return array_sum($this->items);
        }

        return $this->pluck($key)->sum();
    }

    /**
     * Get average of all items (or specific key)
     */
    public function avg(?string $key = null): int|float|null
    {
        $count = $this->count();
        
        if ($count === 0) {
            return null;
        }

        return $this->sum($key) / $count;
    }

    /**
     * Get minimum value
     */
    public function min(): mixed
    {
        return min($this->items);
    }

    /**
     * Get maximum value
     */
    public function max(): mixed
    {
        return max($this->items);
    }

    /**
     * Join items into string
     */
    public function join(string $glue): string
    {
        return implode($glue, $this->items);
    }

    /**
     * Convert to plain array
     * 
     * @return array<int|string, mixed>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Convert to JSON string
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->items, $options);
    }

    /**
     * Countable interface
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * IteratorAggregate interface
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }
}
