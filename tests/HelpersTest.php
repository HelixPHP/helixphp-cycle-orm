<?php

namespace CAFernandes\ExpressPHP\CycleORM\Tests;

use PHPUnit\Framework\TestCase;
use CAFernandes\ExpressPHP\CycleORM\Helpers\CycleHelpers;
use CAFernandes\ExpressPHP\CycleORM\Helpers\EnvironmentHelper;

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
        $mockQuery = $this->createMock(\Cycle\ORM\Select::class);
        $mockQuery->expects($this->once())
                  ->method('where')
                  ->with('name', 'John')
                  ->willReturnSelf();

        $filters = ['name' => 'John', 'forbidden' => 'value'];
        $allowedFields = ['name'];

        $result = CycleHelpers::applyFilters($mockQuery, $filters, $allowedFields);

        $this->assertSame($mockQuery, $result);
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