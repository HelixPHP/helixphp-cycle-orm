<?php
namespace CAFernandes\ExpressPHP\CycleORM\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Express\Core\Application;
use CAFernandes\ExpressPHP\CycleORM\CycleServiceProvider;
use CAFernandes\ExpressPHP\CycleORM\Tests\Fixtures\TestEntity;

/**
 * Teste de integração completa (requer SQLite)
 */
class FullIntegrationTest extends TestCase
{
    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('SQLite PDO extension not available');
        }

        $this->app = new Application();

        // Configurar para usar SQLite em memória
        $this->app->config([
            'cycle' => [
                'database' => [
                    'default' => 'sqlite',
                    'databases' => ['default' => ['connection' => 'sqlite']],
                    'connections' => [
                        'sqlite' => [
                            'driver' => 'sqlite',
                            'database' => ':memory:',
                            'options' => [
                                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                            ]
                        ]
                    ]
                ],
                'entities' => [
                    'directories' => [__DIR__ . '/../Fixtures'],
                    'namespace' => 'CAFernandes\\ExpressPHP\\CycleORM\\Tests\\Fixtures'
                ],
                'schema' => [
                    'cache' => false,
                    'auto_sync' => false
                ]
            ]
        ]);

        // Registrar o service provider
        $provider = new CycleServiceProvider($this->app);
        $provider->register();
        $provider->boot();
    }

    public function testCompleteWorkflow(): void
    {
        // Verificar se serviços foram registrados
        $this->assertTrue($this->app->has('cycle.database'));
        $this->assertTrue($this->app->has('cycle.orm'));
        $this->assertTrue($this->app->has('cycle.em'));

        // Criar tabelas
        $dbal = $this->app->make('cycle.database');
        $db = $dbal->database();

        $db->execute('CREATE TABLE test_entities (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            active INTEGER DEFAULT 1,
            created_at DATETIME NOT NULL
        )');

        // Testar operações CRUD
        $orm = $this->app->make('cycle.orm');
        $em = $this->app->make('cycle.em');

        // Create
        $entity = new TestEntity();
        $entity->name = 'Test Entity';
        $entity->description = 'Test Description';

        $em->persist($entity);
        $em->run();

        $this->assertGreaterThan(0, $entity->id);

        // Read
        $repository = $orm->getRepository(TestEntity::class);
        $foundEntity = $repository->findByPK($entity->id);

        $this->assertNotNull($foundEntity);
        $this->assertEquals('Test Entity', $foundEntity->name);
        $this->assertEquals('Test Description', $foundEntity->description);

        // Update
        $foundEntity->name = 'Updated Entity';
        $em->persist($foundEntity);
        $em->run();

        $updatedEntity = $repository->findByPK($entity->id);
        $this->assertEquals('Updated Entity', $updatedEntity->name);

        // Delete
        $em->delete($updatedEntity);
        $em->run();

        $deletedEntity = $repository->findByPK($entity->id);
        $this->assertNull($deletedEntity);
    }

    public function testHealthCheck(): void
    {
        $health = \CAFernandes\ExpressPHP\CycleORM\Health\CycleHealthCheck::check($this->app);

        $this->assertEquals('healthy', $health['cycle_orm']);
        $this->assertArrayHasKey('checks', $health);
        $this->assertArrayHasKey('response_time_ms', $health);
    }
}
