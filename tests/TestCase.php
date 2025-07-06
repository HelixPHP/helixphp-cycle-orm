<?php

namespace CAFernandes\ExpressPHP\CycleORM\Tests;

use CAFernandes\ExpressPHP\CycleORM\CycleServiceProvider;
use Cycle\Database\DatabaseManager;
use Cycle\Database\Config\DatabaseConfig;
use Cycle\Database\Config\SQLite\FileConnectionConfig;
use Cycle\Database\Config\SQLiteDriverConfig;
use Cycle\ORM\EntityManager;
use Cycle\ORM\ORM;
use Cycle\ORM\Factory;
use Cycle\ORM\Schema;
use CAFernandes\ExpressPHP\CycleORM\Tests\Support\TestApplication;
use CAFernandes\ExpressPHP\CycleORM\Tests\Entities\User;
use CAFernandes\ExpressPHP\CycleORM\Tests\Entities\Post;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected TestApplication $app;
    protected DatabaseManager $dbal;
    protected ORM $orm;
    protected EntityManager $em;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup environment variables for testing
        $_ENV['APP_ENV'] = 'testing';
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';

        $this->app = $this->createApplication();

        $this->setupDatabase();
        $this->setupORM();
    }

    protected function tearDown(): void
    {
        $this->cleanupDatabase();
        parent::tearDown();
    }

    private function createApplication(): TestApplication
    {
        return new TestApplication();
    }

    private function setupDatabase(): void
    {
        // Create in-memory SQLite database for testing
        $config = new DatabaseConfig(
            [
                'default' => 'default',
                'databases' => [
                    'default' => ['connection' => 'default'],
                ],
                'connections' => [
                    'default' => new SQLiteDriverConfig(
                        connection: new FileConnectionConfig(database: ':memory:'),
                        queryCache: false
                    )
                ]
            ]
        );

        $this->dbal = new DatabaseManager($config);
        $this->app->getContainer()->bind('cycle.database', fn() => $this->dbal);
    }

    private function setupORM(): void
    {
        // Create a minimal ORM setup without the service provider
        $factory = new Factory($this->dbal);

        // Create basic schema for test entities
        $schema = new Schema(
            [
                User::class => [
                    Schema::ROLE => 'user',
                    Schema::MAPPER => \Cycle\ORM\Mapper\Mapper::class,
                    Schema::DATABASE => 'default',
                    Schema::TABLE => 'users',
                    Schema::PRIMARY_KEY => 'id',
                    Schema::COLUMNS => [
                        'id' => 'id',
                        'name' => 'name',
                        'email' => 'email',
                        'createdAt' => 'createdAt'
                    ],
                    Schema::TYPECAST => [
                        'id' => 'int',
                        'createdAt' => 'datetime'
                    ],
                    Schema::RELATIONS => []
                ],
                Post::class => [
                    Schema::ROLE => 'post',
                    Schema::MAPPER => \Cycle\ORM\Mapper\Mapper::class,
                    Schema::DATABASE => 'default',
                    Schema::TABLE => 'posts',
                    Schema::PRIMARY_KEY => 'id',
                    Schema::COLUMNS => [
                        'id' => 'id',
                        'title' => 'title',
                        'content' => 'content',
                        'userId' => 'userId',
                        'createdAt' => 'createdAt'
                    ],
                    Schema::TYPECAST => [
                        'id' => 'int',
                        'userId' => 'int',
                        'createdAt' => 'datetime'
                    ],
                    Schema::RELATIONS => []
                ]
            ]
        );

        $this->orm = new ORM($factory, $schema);
        $this->em = new EntityManager($this->orm);

        $this->app->getContainer()->bind('cycle.orm', fn() => $this->orm);
        $this->app->getContainer()->bind('cycle.em', fn() => $this->em);

        // Create tables for test entities
        $this->createTables();
    }

    private function createTables(): void
    {
        $database = $this->dbal->database();

        // Create users table
        $schema = $database->table('users')->getSchema();
        $schema->primary('id');
        $schema->string('name');
        $schema->string('email');
        $schema->datetime('createdAt')->nullable();
        $schema->save();

        // Create posts table
        $schema = $database->table('posts')->getSchema();
        $schema->primary('id');
        $schema->string('title');
        $schema->text('content');
        $schema->integer('userId');
        $schema->datetime('createdAt')->nullable();
        $schema->save();
    }

    private function cleanupDatabase(): void
    {
        if (isset($this->dbal)) {
            $database = $this->dbal->database();
            // SQLite doesn't support truncate, use delete instead
            $database->table('posts')->delete();
            $database->table('users')->delete();
        }
    }

    /**
     * Create a test user
     */
    protected function createUser(string $name = 'Test User', string $email = 'test@example.com'): int
    {
        $database = $this->dbal->database();
        return $database->table('users')->insertGetId(
            [
                'name' => $name,
                'email' => $email,
                'createdAt' => new \DateTimeImmutable()
            ]
        );
    }

    /**
     * Create a test post
     */
    protected function createPost(string $title = 'Test Post', string $content = 'Test content', int $userId = 1): int
    {
        $database = $this->dbal->database();
        return $database->table('posts')->insertGetId(
            [
                'title' => $title,
                'content' => $content,
                'userId' => $userId,
                'createdAt' => new \DateTimeImmutable()
            ]
        );
    }
}
