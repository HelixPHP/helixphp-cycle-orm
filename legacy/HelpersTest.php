<?php

namespace CAFernandes\ExpressPHP\CycleORM\Tests;

use CAFernandes\ExpressPHP\CycleORM\Helpers\CycleHelpers;
use CAFernandes\ExpressPHP\CycleORM\Helpers\EnvironmentHelper;
use CAFernandes\ExpressPHP\CycleORM\Tests\Fixtures\TestEntity;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\SQLite\MemoryConnectionConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\Database\DatabaseManager;
use Cycle\ORM\EntityManager;
use Cycle\ORM\Factory;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\ORM;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CAFernandes\ExpressPHP\CycleORM\Helpers\CycleHelpers
 * @covers \CAFernandes\ExpressPHP\CycleORM\Helpers\EnvironmentHelper
 *
 * @internal
 */
class HelpersTest extends TestCase
{
    public function testPaginateValidation(): void
    {
        $mockQuery = $this->createMock(Select::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Page must be greater than 0');

        CycleHelpers::paginate($mockQuery, 0);
    }

    public function testPaginatePerPageValidation(): void
    {
        $mockQuery = $this->createMock(Select::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Per page must be between 1 and 1000');

        CycleHelpers::paginate($mockQuery, 1, 0);
    }

    public function testApplyFiltersWithAllowedFields(): void
    {
        $pdo = new \PDO('sqlite::memory:');
        $dbal = new DatabaseManager(
            new DatabaseConfig(
                [
                    'default' => 'default',
                    'databases' => [
                        'default' => ['connection' => 'sqlite'],
                    ],
                    'connections' => [
                        'sqlite' => new SQLiteDriverConfig(
                            connection: new MemoryConnectionConfig()
                        ),
                    ],
                ]
            )
        );
        // @phpstan-ignore-next-line
        $factory = new Factory($dbal);
        $schema = new Schema(
            [
                'TestEntity' => [
                    Schema::ENTITY => TestEntity::class,
                    Schema::MAPPER => Mapper::class,
                    Schema::DATABASE => 'default',
                    Schema::TABLE => 'test_entities',
                    Schema::PRIMARY_KEY => 'id',
                    Schema::COLUMNS => ['id', 'name', 'description', 'active', 'createdAt'],
                    Schema::TYPECAST => [
                        'id' => 'int',
                        'active' => 'bool',
                        'createdAt' => 'datetime',
                    ],
                ],
            ]
        );
        $orm = new ORM($factory, $schema);
        $em = new EntityManager($orm);
        // @phpstan-ignore-next-line
        $select = new Select($orm, TestEntity::class);
        $filters = ['name' => 'John', 'forbidden' => 'value'];
        $allowedFields = ['name'];
        $result = CycleHelpers::applyFilters($select, $filters, $allowedFields);
        $this->assertSame($select, $result);
    }

    public function testApplySortingWithInvalidDirection(): void
    {
        $mockQuery = $this->createMock(Select::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("Sort direction must be 'asc' or 'desc'");

        CycleHelpers::applySorting($mockQuery, 'name', 'invalid');
    }

    public function testApplySortingWithDisallowedField(): void
    {
        $mockQuery = $this->createMock(Select::class);

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
