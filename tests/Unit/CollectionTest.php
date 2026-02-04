<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Core\Collection\Collection;

class CollectionTest extends TestCase
{
    public function test_can_create_collection_with_constructor(): void
    {
        $collection = new Collection([1, 2, 3]);
        
        $this->assertEquals([1, 2, 3], $collection->toArray());
    }

    public function test_can_create_collection_with_make(): void
    {
        $collection = Collection::make([1, 2, 3]);
        
        $this->assertEquals([1, 2, 3], $collection->toArray());
    }

    public function test_filter_removes_items_not_matching_callback(): void
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        
        $result = $collection->filter(fn($n) => $n > 2);
        
        $this->assertEquals([2 => 3, 3 => 4, 4 => 5], $result->toArray());
    }

    public function test_map_transforms_each_item(): void
    {
        $collection = new Collection([1, 2, 3]);
        
        $result = $collection->map(fn($n) => $n * 2);
        
        $this->assertEquals([2, 4, 6], $result->toArray());
    }

    public function test_reduce_reduces_to_single_value(): void
    {
        $collection = new Collection([1, 2, 3, 4]);
        
        $result = $collection->reduce(fn($carry, $n) => $carry + $n, 0);
        
        $this->assertEquals(10, $result);
    }

    public function test_first_returns_first_item(): void
    {
        $collection = new Collection([1, 2, 3]);
        
        $this->assertEquals(1, $collection->first());
    }

    public function test_first_returns_first_matching_item(): void
    {
        $collection = new Collection([1, 2, 3, 4]);
        
        $result = $collection->first(fn($n) => $n > 2);
        
        $this->assertEquals(3, $result);
    }

    public function test_first_returns_default_when_empty(): void
    {
        $collection = new Collection([]);
        
        $this->assertEquals('default', $collection->first(default: 'default'));
    }

    public function test_last_returns_last_item(): void
    {
        $collection = new Collection([1, 2, 3]);
        
        $this->assertEquals(3, $collection->last());
    }

    public function test_last_returns_last_matching_item(): void
    {
        $collection = new Collection([1, 2, 3, 4]);
        
        $result = $collection->last(fn($n) => $n < 3);
        
        $this->assertEquals(2, $result);
    }

    public function test_pluck_extracts_column_from_array(): void
    {
        $collection = new Collection([
            ['name' => 'Alice', 'age' => 30],
            ['name' => 'Bob', 'age' => 25],
        ]);
        
        $result = $collection->pluck('name');
        
        $this->assertEquals(['Alice', 'Bob'], $result->toArray());
    }

    public function test_pluck_extracts_property_from_objects(): void
    {
        $alice = (object)['name' => 'Alice', 'age' => 30];
        $bob = (object)['name' => 'Bob', 'age' => 25];
        
        $collection = new Collection([$alice, $bob]);
        
        $result = $collection->pluck('name');
        
        $this->assertEquals(['Alice', 'Bob'], $result->toArray());
    }

    public function test_group_by_groups_items_by_key(): void
    {
        $collection = new Collection([
            ['type' => 'fruit', 'name' => 'apple'],
            ['type' => 'fruit', 'name' => 'banana'],
            ['type' => 'vegetable', 'name' => 'carrot'],
        ]);
        
        $result = $collection->groupBy('type');
        
        $this->assertCount(2, $result);
        $this->assertCount(2, $result->get('fruit'));
        $this->assertCount(1, $result->get('vegetable'));
    }

    public function test_group_by_groups_items_by_callback(): void
    {
        $collection = new Collection([1, 2, 3, 4, 5, 6]);
        
        $result = $collection->groupBy(fn($n) => $n % 2 === 0 ? 'even' : 'odd');
        
        $this->assertCount(2, $result);
        $this->assertEquals([1, 3, 5], $result->get('odd'));
        $this->assertEquals([2, 4, 6], $result->get('even'));
    }

    public function test_sort_sorts_items(): void
    {
        $collection = new Collection([3, 1, 4, 1, 5]);
        
        $result = $collection->sort();
        
        $this->assertEquals([1, 1, 3, 4, 5], array_values($result->toArray()));
    }

    public function test_sort_sorts_with_callback(): void
    {
        $collection = new Collection([3, 1, 4, 1, 5]);
        
        $result = $collection->sort(fn($a, $b) => $b <=> $a);
        
        $this->assertEquals([5, 4, 3, 1, 1], array_values($result->toArray()));
    }

    public function test_sort_keys_sorts_by_keys(): void
    {
        $collection = new Collection(['c' => 3, 'a' => 1, 'b' => 2]);
        
        $result = $collection->sortKeys();
        
        $this->assertEquals(['a' => 1, 'b' => 2, 'c' => 3], $result->toArray());
    }

    public function test_reverse_reverses_items(): void
    {
        $collection = new Collection([1, 2, 3]);
        
        $result = $collection->reverse();
        
        $this->assertEquals([3, 2, 1], array_values($result->toArray()));
    }

    public function test_unique_removes_duplicates(): void
    {
        $collection = new Collection([1, 2, 2, 3, 3, 3]);
        
        $result = $collection->unique();
        
        $this->assertEquals([1, 2, 3], array_values($result->toArray()));
    }

    public function test_values_resets_keys(): void
    {
        $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
        
        $result = $collection->values();
        
        $this->assertEquals([1, 2, 3], $result->toArray());
    }

    public function test_keys_returns_keys(): void
    {
        $collection = new Collection(['a' => 1, 'b' => 2, 'c' => 3]);
        
        $result = $collection->keys();
        
        $this->assertEquals(['a', 'b', 'c'], $result->toArray());
    }

    public function test_chunk_splits_into_chunks(): void
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        
        $result = $collection->chunk(2);
        
        $this->assertCount(3, $result);
        $this->assertEquals([1, 2], $result->get(0));
        $this->assertEquals([2 => 3, 3 => 4], $result->get(1));
        $this->assertEquals([4 => 5], $result->get(2));
    }

    public function test_take_gets_first_n_items(): void
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        
        $result = $collection->take(3);
        
        $this->assertEquals([1, 2, 3], $result->toArray());
    }

    public function test_take_negative_gets_last_n_items(): void
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        
        $result = $collection->take(-2);
        
        $this->assertEquals([3 => 4, 4 => 5], $result->toArray());
    }

    public function test_skip_skips_first_n_items(): void
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        
        $result = $collection->skip(2);
        
        $this->assertEquals([2 => 3, 3 => 4, 4 => 5], $result->toArray());
    }

    public function test_slice_gets_slice_of_items(): void
    {
        $collection = new Collection([1, 2, 3, 4, 5]);
        
        $result = $collection->slice(1, 3);
        
        $this->assertEquals([1 => 2, 2 => 3, 3 => 4], $result->toArray());
    }

    public function test_merge_merges_with_array(): void
    {
        $collection = new Collection([1, 2]);
        
        $result = $collection->merge([3, 4]);
        
        $this->assertEquals([1, 2, 3, 4], $result->toArray());
    }

    public function test_merge_merges_with_collection(): void
    {
        $collection1 = new Collection([1, 2]);
        $collection2 = new Collection([3, 4]);
        
        $result = $collection1->merge($collection2);
        
        $this->assertEquals([1, 2, 3, 4], $result->toArray());
    }

    public function test_flatten_flattens_array(): void
    {
        $collection = new Collection([1, [2, 3], [4, [5, 6]]]);
        
        $result = $collection->flatten();
        
        $this->assertEquals([1, 2, 3, 4, 5, 6], $result->toArray());
    }

    public function test_flatten_with_depth(): void
    {
        $collection = new Collection([1, [2, 3], [4, [5, 6]]]);
        
        $result = $collection->flatten(1);
        
        $this->assertEquals([1, 2, 3, 4, [5, 6]], $result->toArray());
    }

    public function test_some_returns_true_if_any_match(): void
    {
        $collection = new Collection([1, 2, 3, 4]);
        
        $result = $collection->some(fn($n) => $n > 3);
        
        $this->assertTrue($result);
    }

    public function test_some_returns_false_if_none_match(): void
    {
        $collection = new Collection([1, 2, 3]);
        
        $result = $collection->some(fn($n) => $n > 5);
        
        $this->assertFalse($result);
    }

    public function test_every_returns_true_if_all_match(): void
    {
        $collection = new Collection([2, 4, 6]);
        
        $result = $collection->every(fn($n) => $n % 2 === 0);
        
        $this->assertTrue($result);
    }

    public function test_every_returns_false_if_any_dont_match(): void
    {
        $collection = new Collection([2, 3, 4]);
        
        $result = $collection->every(fn($n) => $n % 2 === 0);
        
        $this->assertFalse($result);
    }

    public function test_each_executes_callback_for_each_item(): void
    {
        $collection = new Collection([1, 2, 3]);
        $results = [];
        
        $collection->each(function($n) use (&$results) {
            $results[] = $n * 2;
        });
        
        $this->assertEquals([2, 4, 6], $results);
    }

    public function test_tap_executes_callback_and_returns_collection(): void
    {
        $collection = new Collection([1, 2, 3]);
        $tapped = null;
        
        $result = $collection->tap(function($c) use (&$tapped) {
            $tapped = $c->count();
        });
        
        $this->assertSame($collection, $result);
        $this->assertEquals(3, $tapped);
    }

    public function test_get_returns_item_by_key(): void
    {
        $collection = new Collection(['a' => 1, 'b' => 2]);
        
        $this->assertEquals(1, $collection->get('a'));
        $this->assertEquals(2, $collection->get('b'));
    }

    public function test_get_returns_default_for_missing_key(): void
    {
        $collection = new Collection(['a' => 1]);
        
        $this->assertEquals('default', $collection->get('b', 'default'));
    }

    public function test_has_checks_if_key_exists(): void
    {
        $collection = new Collection(['a' => 1, 'b' => null]);
        
        $this->assertTrue($collection->has('a'));
        $this->assertTrue($collection->has('b'));
        $this->assertFalse($collection->has('c'));
    }

    public function test_is_empty_checks_if_collection_is_empty(): void
    {
        $this->assertTrue((new Collection([]))->isEmpty());
        $this->assertFalse((new Collection([1]))->isEmpty());
    }

    public function test_is_not_empty_checks_if_collection_is_not_empty(): void
    {
        $this->assertFalse((new Collection([]))->isNotEmpty());
        $this->assertTrue((new Collection([1]))->isNotEmpty());
    }

    public function test_sum_calculates_sum(): void
    {
        $collection = new Collection([1, 2, 3, 4]);
        
        $this->assertEquals(10, $collection->sum());
    }

    public function test_sum_calculates_sum_of_key(): void
    {
        $collection = new Collection([
            ['price' => 10],
            ['price' => 20],
            ['price' => 30],
        ]);
        
        $this->assertEquals(60, $collection->sum('price'));
    }

    public function test_avg_calculates_average(): void
    {
        $collection = new Collection([1, 2, 3, 4]);
        
        $this->assertEquals(2.5, $collection->avg());
    }

    public function test_avg_returns_null_for_empty_collection(): void
    {
        $collection = new Collection([]);
        
        $this->assertNull($collection->avg());
    }

    public function test_min_returns_minimum_value(): void
    {
        $collection = new Collection([3, 1, 4, 1, 5]);
        
        $this->assertEquals(1, $collection->min());
    }

    public function test_max_returns_maximum_value(): void
    {
        $collection = new Collection([3, 1, 4, 1, 5]);
        
        $this->assertEquals(5, $collection->max());
    }

    public function test_join_joins_items_into_string(): void
    {
        $collection = new Collection(['apple', 'banana', 'cherry']);
        
        $result = $collection->join(', ');
        
        $this->assertEquals('apple, banana, cherry', $result);
    }

    public function test_to_json_converts_to_json(): void
    {
        $collection = new Collection(['name' => 'Alice', 'age' => 30]);
        
        $result = $collection->toJson();
        
        $this->assertEquals('{"name":"Alice","age":30}', $result);
    }

    public function test_count_returns_item_count(): void
    {
        $collection = new Collection([1, 2, 3]);
        
        $this->assertCount(3, $collection);
        $this->assertEquals(3, $collection->count());
    }

    public function test_can_iterate_over_collection(): void
    {
        $collection = new Collection([1, 2, 3]);
        $results = [];
        
        foreach ($collection as $item) {
            $results[] = $item;
        }
        
        $this->assertEquals([1, 2, 3], $results);
    }

    public function test_fluent_chaining(): void
    {
        $collection = new Collection([1, 2, 3, 4, 5, 6]);
        
        $result = $collection
            ->filter(fn($n) => $n > 2)
            ->map(fn($n) => $n * 2)
            ->values()
            ->toArray();
        
        $this->assertEquals([6, 8, 10, 12], $result);
    }

    public function test_immutability_original_unchanged(): void
    {
        $collection = new Collection([1, 2, 3]);
        
        $collection->filter(fn($n) => $n > 2);
        $collection->map(fn($n) => $n * 2);
        
        $this->assertEquals([1, 2, 3], $collection->toArray());
    }
}
