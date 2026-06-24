<?php

declare(strict_types=1);

namespace Tests\Core\GraphQL;

use Core\GraphQL\GraphQLRegistry;
use PHPUnit\Framework\TestCase;

final class GraphQLRegistryTest extends TestCase
{
    public function testRegistersType(): void
    {
        $registry = new GraphQLRegistry();
        $type = new \stdClass();

        $registry->registerType('TestType', $type);

        self::assertSame($type, $registry->getTypes()['TestType']);
    }

    public function testRegistersQuery(): void
    {
        $registry = new GraphQLRegistry();
        $field = new \stdClass();

        $registry->registerQuery('testQuery', $field);

        self::assertSame($field, $registry->getQueries()['testQuery']);
    }

    public function testRegistersMutation(): void
    {
        $registry = new GraphQLRegistry();
        $field = new \stdClass();

        $registry->registerMutation('testMutation', $field);

        self::assertSame($field, $registry->getMutations()['testMutation']);
    }

    public function testReturnsEmptyArraysWhenNothing(): void
    {
        $registry = new GraphQLRegistry();

        self::assertEmpty($registry->getTypes());
        self::assertEmpty($registry->getQueries());
        self::assertEmpty($registry->getMutations());
    }
}
