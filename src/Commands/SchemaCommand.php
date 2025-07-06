<?php

namespace Helix\CycleORM\Commands;

use Cycle\Migrations\Migrator;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\SchemaInterface;
use Psr\Container\ContainerInterface;

/**
 * Comando para exibir e sincronizar o schema do Cycle ORM.
 *
 * Exemplos de uso:
 *   php bin/console cycle:schema
 *   php bin/console cycle:schema --sync
 *
 * Dependências:
 *   - O container da aplicação deve fornecer 'cycle.orm' e 'cycle.migrator'.
 *
 * Métodos:
 *   - handle(): Executa o comando principal.
 *   - syncSchema(): Sincroniza o schema do banco de dados.
 *   - showSchema(): Exibe informações do schema atual.
 *
 * @property null|ContainerInterface|object $app Instância da aplicação ou container.
 */
class SchemaCommand extends BaseCommand
{
    /**
     * Instância da aplicação ou container.
     */
    protected ?ContainerInterface $app;

    /**
     * Construtor do comando.
     *
     * @param array<string, mixed>    $args argumentos do comando
     * @param null|ContainerInterface $app  instância da aplicação ou container
     */
    public function __construct(array $args = [], ?ContainerInterface $app = null)
    {
        parent::__construct($args);
        $this->app = $app;
    }

    /**
     * Executa o comando principal.
     *
     * @return int Código de status (0 = sucesso, 1 = erro)
     */
    public function handle(): int
    {
        if ($this->option('sync')) {
            return $this->syncSchema();
        }

        return $this->showSchema();
    }

    /**
     * Sincroniza o schema do banco de dados com as entidades.
     *
     * @return int Código de status (0 = sucesso, 1 = erro)
     */
    private function syncSchema(): int
    {
        $this->info('Sincronizando schema do banco de dados...');
        try {
            $migrator = $this->getService('cycle.migrator', Migrator::class);
            if (!$migrator) {
                $this->error('Serviço cycle.migrator não encontrado no container.');

                return 1;
            }
            if (method_exists($migrator, 'run')) {
                $result = $migrator->run();
            } elseif (method_exists($migrator, 'migrate')) {
                $result = $migrator->migrate();
            } else {
                $this->error('O migrator não possui método run() ou migrate().');

                return 1;
            }
            if ($result) {
                $this->info('Schema sincronizado com sucesso!');
            } else {
                $this->info('Schema já está atualizado.');
            }

            return 0;
        } catch (\Throwable $e) {
            $this->error('Falha ao sincronizar schema: ' . $e->getMessage());

            return 1;
        }
    }

    /**
     * Exibe informações do schema atual do Cycle ORM.
     *
     * @return int Código de status (0 = sucesso, 1 = erro)
     */
    private function showSchema(): int
    {
        $this->info('Informações do Cycle ORM Schema');
        try {
            /** @var null|ORMInterface $orm */
            $orm = $this->getService('cycle.orm', ORMInterface::class);
            if (!$orm) {
                $this->error('Serviço cycle.orm não encontrado no container.');

                return 1;
            }
            $schema = $orm->getSchema();
            if (!$schema instanceof SchemaInterface) {
                $this->error('Schema inválido ou não encontrado.');

                return 1;
            }
            $entities = [];
            foreach ($schema->getRoles() as $role) {
                $entities[] = [
                    $role,
                    $schema->define($role, SchemaInterface::ENTITY),
                    $schema->define($role, SchemaInterface::TABLE),
                    $schema->define($role, SchemaInterface::DATABASE),
                ];
            }
            $this->table(['Role', 'Entity', 'Table', 'Database'], $entities);

            return 0;
        } catch (\Throwable $e) {
            $this->error('Falha ao exibir schema: ' . $e->getMessage());

            return 1;
        }
    }

    /**
     * Obtém um serviço do container, com fallback para métodos Express-PHP e PSR-11.
     *
     * @param string            $service       nome do serviço
     * @param null|class-string $expectedClass classe esperada para validação
     */
    private function getService(string $service, ?string $expectedClass = null): ?object
    {
        $container = $this->app;
        if (!$container) {
            return null;
        }
        if (is_object($container) && method_exists($container, 'container')) {
            $container = $container->container();
        }
        if ($container instanceof ContainerInterface) {
            if (!$container->has($service)) {
                return null;
            }
            $instance = $container->get($service);
        } elseif (is_object($container) && method_exists($container, 'get')) {
            $instance = $container->get($service);
        } else {
            return null;
        }
        if ($expectedClass && !($instance instanceof $expectedClass)) {
            return null;
        }

        return $instance;
    }
}
