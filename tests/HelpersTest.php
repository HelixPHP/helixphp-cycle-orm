<?php

namespace CAFernandes\ExpressPHP\CycleORM\Tests;

use PHPUnit\Framework\TestCase;
use CAFernandes\ExpressPHP\CycleORM\Helpers\CycleHelpers;
use CAFernandes\ExpressPHP\CycleORM\Helpers\EnvironmentHelper;

/**
 * @covers \CAFernandes\ExpressPHP\CycleORM\Helpers\CycleHelpers
 * @covers \CAFernandes\ExpressPHP\CycleORM\Helpers\EnvironmentHelper
 */
class HelpersTest extends TestCase
{
    public function testPaginateValidation(): void
    {
        $mockQuery = $this->createMock(\Cycle\ORM\Select::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Page must be greater than 0');

        CycleHelpers::paginate($mockQuery, 0);
    }

    public function testPaginatePerPageValidation(): void
    {
        $mockQuery = $this->createMock(\Cycle\ORM\Select::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Per page must be between 1 and 1000');

        CycleHelpers::paginate($mockQuery, 1, 0);
    }

    public function testApplyFiltersWithAllowedFields(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        $dbal = new \Cycle\Database\DatabaseManager(
            new \Cycle\Database\Config\DatabaseConfig(
                [
                    'default' => 'default',
                    'databases' => [
                        'default' => ['connection' => 'sqlite']
                    ],
                    'connections' => [
                        'sqlite' => new \Cycle\Database\Config\SQLiteDriverConfig(
                            connection: new \Cycle\Database\Config\SQLite\MemoryConnectionConfig()
                        )
                    ]
                ]
            )
        );
        $factory = new \Cycle\ORM\Factory($dbal);
        $schema = new \Cycle\ORM\Schema(
            [
                'TestEntity' => [
                    \Cycle\ORM\Schema::ENTITY => \CAFernandes\ExpressPHP\CycleORM\Tests\Fixtures\TestEntity::class,
                    \Cycle\ORM\Schema::MAPPER => \Cycle\ORM\Mapper\Mapper::class,
                    \Cycle\ORM\Schema::DATABASE => 'default',
                    \Cycle\ORM\Schema::TABLE => 'test_entities',
                    \Cycle\ORM\Schema::PRIMARY_KEY => 'id',
                    \Cycle\ORM\Schema::COLUMNS => ['id', 'name', 'description', 'active', 'createdAt'],
                    \Cycle\ORM\Schema::TYPECAST => [
                        'id' => 'int',
                        'active' => 'bool',
                        'createdAt' => 'datetime',
                    ],
                ],
            ]
        );
        $orm = new \Cycle\ORM\ORM($factory, $schema);
        $em = new \Cycle\ORM\EntityManager($orm);
        $select = new \Cycle\ORM\Select($orm, \CAFernandes\ExpressPHP\CycleORM\Tests\Fixtures\TestEntity::class);
        $filters = ['name' => 'John', 'forbidden' => 'value'];
        $allowedFields = ['name'];
        $result = CycleHelpers::applyFilters($select, $filters, $allowedFields);
        $this->assertSame($select, $result);
    }

    public function testApplySortingWithInvalidDirection(): void
    {
        $mockQuery = $this->createMock(\Cycle\ORM\Select::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Sort direction must be 'asc' or 'desc'");

        CycleHelpers::applySorting($mockQuery, 'name', 'invalid');
    }

    public function testApplySortingWithDisallowedField(): void
    {
        $mockQuery = $this->createMock(\Cycle\ORM\Select::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Sort field 'forbidden' is not allowed");

        CycleHelpers::applySorting($mockQuery, 'forbidden', 'asc', ['name']);
    }

    public function testEnvironmentHelper(): void
    {
      // Mock environment variables
        $_ENV['APP_ENV'] = 'testing';

        $this->assertTrue(EnvironmentHelper::isTesting());
        $this->assertFalse(EnvironmentHelper::isProduction());
        $this->assertFalse(EnvironmentHelper::isDevelopment());
        $this->assertEquals('testing', EnvironmentHelper::getEnvironment());

      // Cleanup
        unset($_ENV['APP_ENV']);
    }
}
