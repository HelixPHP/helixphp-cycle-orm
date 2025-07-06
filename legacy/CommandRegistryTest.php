<?php

namespace Helix\CycleORM\Tests;

use Helix\CycleORM\Commands\CommandRegistry;
use Helix\CycleORM\Commands\EntityCommand;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Helix\CycleORM\Commands\CommandRegistry
 *
 * @internal
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
