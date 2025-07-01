<?php

namespace CAFernandes\ExpressPHP\CycleORM\Tests;

use CAFernandes\ExpressPHP\CycleORM\Commands\CommandRegistry;
use CAFernandes\ExpressPHP\CycleORM\Commands\EntityCommand;
use CAFernandes\ExpressPHP\CycleORM\Commands\SchemaCommand;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\DatabaseManager;
use Cycle\ORM\Factory;
use Cycle\ORM\Schema;
use Cycle\Schema\Compiler;
use Cycle\Schema\Registry;
use Express\Core\Application;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CAFernandes\ExpressPHP\CycleORM\Commands\CommandRegistry
 * @covers \CAFernandes\ExpressPHP\CycleORM\Commands\EntityCommand
 * @covers \CAFernandes\ExpressPHP\CycleORM\Commands\SchemaCommand
 *
 * @internal
 */
class CommandsTest extends TestCase
{
    public function testEntityCommandRequiresName(): void
    {
        $app = new Application();
        $container = $app->getContainer();
        $command = new EntityCommand([]);
        ob_start();
        $result = $command->handle();
        $output = ob_get_clean() ?: '';
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
        $app = new Application();
        $container = $app->getContainer();
        $command = new EntityCommand(['name' => 'TestEntity']);
        ob_start();
        $result = $command->handle();
        $output = ob_get_clean() ?: '';
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
        $dbal = new DatabaseManager(
            new DatabaseConfig(
                [
                    'default' => 'default',
                    'databases' => [
                        'default' => ['connection' => 'sqlite'],
                    ],
                    'connections' => [
                        'sqlite' => [
                            'driver' => 'sqlite',
                            'database' => ':memory:',
                        ],
                    ],
                ]
            )
        );
        // @phpstan-ignore-next-line
        $factory = new Factory($dbal);
        // @phpstan-ignore-next-line
        $registry = new Registry($dbal);
        $schemaArray = (new Compiler())->compile($registry, []);
        $schema = new Schema($schemaArray); // Corrigido: Schema real
        $orm = new class ($schema) {
            private object $schema;

            public function __construct(object $schema)
            {
                $this->schema = $schema;
            }

            public function getSchema(): object
            {
                return $this->schema;
            }
        };
        // Application Express-PHP real
        $app = new Application();
        $container = $app->getContainer();
        $container->bind('cycle.orm', fn () => $orm);
        $command = new SchemaCommand([], $container);
        ob_start();
        $result = $command->handle();
        $output = ob_get_clean() ?: '';
        $this->assertIsInt($result);
        $this->assertStringContainsString('Informações do Cycle ORM Schema', $output);
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

    /** @SuppressWarnings("unused") */
    // @phpstan-ignore-next-line
    private function recursiveDelete(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $scannedFiles = scandir($dir);
        if (false === $scannedFiles) {
            return;
        }
        $files = array_diff($scannedFiles, ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->recursiveDelete($path) : unlink($path);
        }
        rmdir($dir);
    }
}
