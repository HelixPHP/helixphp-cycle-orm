<?php
namespace CAFernandes\ExpressPHP\CycleORM\Tests;

use PHPUnit\Framework\TestCase;
use CAFernandes\ExpressPHP\CycleORM\Commands\EntityCommand;
use CAFernandes\ExpressPHP\CycleORM\Commands\SchemaCommand;
use CAFernandes\ExpressPHP\CycleORM\Commands\CommandRegistry;

/**
 * @covers \CAFernandes\ExpressPHP\CycleORM\Commands\EntityCommand
 * @covers \CAFernandes\ExpressPHP\CycleORM\Commands\SchemaCommand
 * @covers \CAFernandes\ExpressPHP\CycleORM\Commands\CommandRegistry
 */
class CommandsTest extends TestCase
{
    public function testEntityCommandRequiresName(): void
    {
        $command = new EntityCommand([]);
        ob_start();
        $result = $command->handle();
        $output = ob_get_clean();
        $this->assertEquals(1, $result);
        $this->assertStringContainsString('Entity name is required', $output);
    }

    public function testEntityCommandWithValidName(): void
    {
        $entityPath = sys_get_temp_dir() . '/cycle_test_models/TestEntity.php';
        // Limpa antes
        if (file_exists($entityPath)) {
            unlink($entityPath);
        }
        $command = new EntityCommand(['name' => 'TestEntity']);
        ob_start();
        $result = $command->handle();
        $output = ob_get_clean();
        $this->assertEquals(0, $result);
        $this->assertStringContainsString('Entity created', $output);
        $this->assertFileExists($entityPath);
        // Limpa depois
        if (file_exists($entityPath)) {
            unlink($entityPath);
        }
    }

    public function testSchemaCommandShowsInfo(): void
    {
        // Instancia um ORM real para o teste
        $pdo = new \PDO('sqlite::memory:');
        $dbal = new \Cycle\Database\DatabaseManager(new \Cycle\Database\Config\DatabaseConfig([
            'default' => 'default',
            'databases' => [
                'default' => ['connection' => 'sqlite']
            ],
            'connections' => [
                'sqlite' => [
                    'driver' => 'sqlite',
                    'database' => ':memory:'
                ]
            ]
        ]));
        $factory = new \Cycle\ORM\Factory($dbal);
        $registry = new \Cycle\Schema\Registry($dbal);
        $schemaArray = (new \Cycle\Schema\Compiler())->compile($registry, []);
        $schema = new \Cycle\ORM\Schema($schemaArray); // Corrigido: Schema real
        $orm = new class($schema) {
            private $schema;
            public function __construct($schema) { $this->schema = $schema; }
            public function getSchema() { return $this->schema; }
        };
        // Mock Application com container()->get('cycle.orm')
        $app = new class($orm) {
            private $orm;
            public function __construct($orm) { $this->orm = $orm; }
            public function container() {
                return new class($this->orm) {
                    private $orm;
                    public function __construct($orm) { $this->orm = $orm; }
                    public function get($service) {
                        if ($service === 'cycle.orm') return $this->orm;
                        throw new \RuntimeException("Service $service not found");
                    }
                };
            }
        };
        $command = new SchemaCommand([], $app); // Passa app mockado
        ob_start();
        $result = $command->handle();
        $output = ob_get_clean();
        $this->assertIsInt($result);
        $this->assertStringContainsString('Cycle ORM Schema Information', $output);
    }

    public function testCommandRegistry(): void
    {
        $registry = new CommandRegistry();

        $registry->register('test:command', EntityCommand::class);

        $this->assertTrue($registry->hasCommand('test:command'));
        $this->assertContains('test:command', $registry->getRegisteredCommands());
    }

    public function testCommandRegistryThrowsOnInvalidClass(): void
    {
        $registry = new CommandRegistry();

        $this->expectException(\InvalidArgumentException::class);
        $registry->register('invalid', 'NonExistentClass');
    }

    private function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
        }
        rmdir($dir);
    }
}