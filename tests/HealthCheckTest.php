<?php
namespace CAFernandes\ExpressPHP\CycleORM\Tests;

use PHPUnit\Framework\TestCase;
use CAFernandes\ExpressPHP\CycleORM\Health\CycleHealthCheck;
use Express\Core\Application;

class HealthCheckTest extends TestCase
{
    public function testHealthCheckWithNoServices(): void
    {
        $app = $this->createMock(Application::class);
        $app->method('has')->willReturn(false);

        $result = CycleHealthCheck::check($app);

        $this->assertEquals('unhealthy', $result['cycle_orm']);
        $this->assertArrayHasKey('checks', $result);
        $this->assertArrayHasKey('services', $result['checks']);
        $this->assertEquals('unhealthy', $result['checks']['services']['status']);
    }

    public function testHealthCheckWithAllServices(): void
    {
        $app = $this->createMock(Application::class);
        $app->method('has')->willReturn(true);

        // Mock database
        $pdo = $this->createMock(\PDO::class);
        $pdo->method('getAttribute')->willReturn('5.7.0');
        $pdo->method('query')->willReturn($this->createQueryResult());

        $driver = $this->createMock(\Cycle\Database\Driver\DriverInterface::class);
        $driver->method('getPDO')->willReturn($pdo);
        $driver->method('getType')->willReturn('mysql');

        $database = $this->createMock(\Cycle\Database\DatabaseInterface::class);
        $database->method('getDriver')->willReturn($driver);

        $dbal = $this->createMock(\Cycle\Database\DatabaseManager::class);
        $dbal->method('database')->willReturn($database);

        // Mock ORM
        $schema = $this->createMock(\Cycle\ORM\SchemaInterface::class);
        $schema->method('getRoles')->willReturn(['user', 'post']);
        $schema->method('define')->willReturnCallback(function($role, $key) {
            if ($key === \Cycle\ORM\SchemaInterface::ENTITY) {
                return $role === 'user' ? 'App\\Models\\User' : 'App\\Models\\Post';
            }
            if ($key === \Cycle\ORM\SchemaInterface::TABLE) {
                return $role === 'user' ? 'users' : 'posts';
            }
            return 'default';
        });

        $orm = $this->createMock(\Cycle\ORM\ORM::class);
        $orm->method('getSchema')->willReturn($schema);

        $app->method('make')->willReturnMap([
            ['cycle.database', $dbal],
            ['cycle.orm', $orm]
        ]);

        $result = CycleHealthCheck::check($app);

        $this->assertEquals('healthy', $result['cycle_orm']);
        $this->assertArrayHasKey('response_time_ms', $result);
        $this->assertIsFloat($result['response_time_ms']);
    }

    private function createQueryResult()
    {
        $result = $this->createMock(\PDOStatement::class);
        $result->method('fetchColumn')->willReturn('1');
        return $result;
    }
}