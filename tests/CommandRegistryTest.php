<?php
namespace CAFernandes\ExpressPHP\CycleORM\Tests;

use PHPUnit\Framework\TestCase;
use CAFernandes\ExpressPHP\CycleORM\Commands\CommandRegistry;
use CAFernandes\ExpressPHP\CycleORM\Commands\EntityCommand;

/**
 * @covers \CAFernandes\ExpressPHP\CycleORM\Commands\CommandRegistry
 */
class CommandRegistryTest extends TestCase
{
    private CommandRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new CommandRegistry();
    }

    public function testCanRegisterCommand(): void
    {
        $this->registry->register('test:entity', EntityCommand::class);

        $this->assertTrue($this->registry->hasCommand('test:entity'));
        $this->assertContains('test:entity', $this->registry->getRegisteredCommands());
    }

    public function testThrowsOnInvalidCommand(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->registry->register('invalid', 'NonExistentClass');
    }

    public function testCanRunCommand(): void
    {
        $this->registry->register('entity', EntityCommand::class);

        ob_start();
        $result = $this->registry->run('entity', ['name' => 'TestEntity']);
        ob_end_clean();

        $this->assertIsInt($result);
    }
}