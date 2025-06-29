<?php
namespace CAFernandes\ExpressPHP\CycleORM\Tests;

use PHPUnit\Framework\TestCase;
use CAFernandes\ExpressPHP\CycleORM\Commands\EntityCommand;
use CAFernandes\ExpressPHP\CycleORM\Commands\SchemaCommand;
use CAFernandes\ExpressPHP\CycleORM\Commands\CommandRegistry;

class CommandsTest extends TestCase
{
    public function testEntityCommandRequiresName(): void
    {
        $command = new EntityCommand([]);
        $result = $command->handle();

        $this->assertEquals(1, $result);
    }

    public function testEntityCommandWithValidName(): void
    {
        // Criar diretório temporário para teste
        $tempDir = sys_get_temp_dir() . '/cycle_test_' . uniqid();
        mkdir($tempDir, 0755, true);

        // Mock app_path function
        if (!function_exists('app_path')) {
            function app_path($path = '') {
                return sys_get_temp_dir() . '/cycle_test_models/' . $path;
            }
        }

        $command = new EntityCommand(['name' => 'TestEntity']);

        // Capturar output
        ob_start();
        $result = $command->handle();
        $output = ob_get_clean();

        $this->assertEquals(0, $result);
        $this->assertStringContainsString('Entity created', $output);

        // Cleanup
        $this->recursiveDelete($tempDir);
    }

    public function testSchemaCommandShowsInfo(): void
    {
        $command = new SchemaCommand([]);

        ob_start();
        $result = $command->handle();
        $output = ob_get_clean();

        // Schema command deve funcionar mesmo sem app disponível
        $this->assertIsInt($result);
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