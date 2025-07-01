<?php

namespace CAFernandes\ExpressPHP\CycleORM\Tests;

use CAFernandes\ExpressPHP\CycleORM\Health\CycleHealthCheck;
use Cycle\Database\Config\DriverConfig;
use Cycle\Database\DatabaseInterface;
use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Driver\HandlerInterface;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Query\BuilderInterface;
use Cycle\Database\Query\DeleteQuery;
use Cycle\Database\Query\InsertQuery;
use Cycle\Database\Query\QueryParameters;
use Cycle\Database\Query\SelectQuery;
use Cycle\Database\Query\UpdateQuery;
use Cycle\Database\StatementInterface;
use Cycle\Database\TableInterface;
use Cycle\ORM\SchemaInterface;
use Express\Core\Application;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
class HealthCheckTest extends TestCase
{
    public function testHealthCheckWithNoServices(): void
    {
        $app = new class() extends Application {
            private $bootedCallbacks = [];

            private array $services = [];

            public function booted($callback = null)
            {
                if ($callback) {
                    $this->bootedCallbacks[] = $callback;
                } else {
                    foreach ($this->bootedCallbacks as $cb) {
                        $cb($this);
                    }
                }
            }

            public function singleton(string $abstract, mixed $concrete = null): self
            {
                $this->services[$abstract] = $concrete;

                return $this;
            }

            public function has(string $id): bool
            {
                return isset($this->services[$id]);
            }

            public function make(string $abstract): mixed
            {
                if (!isset($this->services[$abstract])) {
                    throw new \RuntimeException("Service {$abstract} not found");
                }
                $factory = $this->services[$abstract];

                return $factory();
            }
        };
        // Não registra nenhum serviço
        $result = CycleHealthCheck::check($app);

        $this->assertEquals('unhealthy', $result['cycle_orm']);
        $this->assertArrayHasKey('checks', $result);
        $this->assertArrayHasKey('services', $result['checks']);
        $this->assertEquals('unhealthy', $result['checks']['services']['status']);
    }

    public function testHealthCheckWithAllServices(): void
    {
        $app = new class() extends Application {
            private $bootedCallbacks = [];

            private array $services = [];

            public function booted($callback = null)
            {
                if ($callback) {
                    $this->bootedCallbacks[] = $callback;
                } else {
                    foreach ($this->bootedCallbacks as $cb) {
                        $cb($this);
                    }
                }
            }

            public function singleton(string $abstract, mixed $concrete = null): self
            {
                $this->services[$abstract] = $concrete;

                return $this;
            }

            public function has(string $id): bool
            {
                return isset($this->services[$id]);
            }

            public function make(string $abstract): mixed
            {
                if (!isset($this->services[$abstract])) {
                    throw new \RuntimeException("Service {$abstract} not found");
                }
                $factory = $this->services[$abstract];

                return $factory();
            }
        };
        // Mock DBAL
        $pdo = new class() {
            public function getAttribute($attr)
            {
                if (\PDO::ATTR_SERVER_VERSION === $attr) {
                    return '5.7.0';
                }
            }

            public function query($sql)
            {
                return new class() {
                    public function fetchColumn()
                    {
                        return '1';
                    }
                };
            }
        };
        $driver = new class($pdo) implements DriverInterface {
            private $pdo;

            public function __construct($pdo)
            {
                $this->pdo = $pdo;
            }

            public function getPDO()
            {
                return $this->pdo;
            }

            public function getType(): string
            {
                return 'mysql';
            }

            // Métodos não usados
            public function connect(): void {}

            public function disconnect(): void {}

            public function isConnected(): bool
            {
                return true;
            }

            public function getName(): string
            {
                return 'default';
            }

            public function getSchema(?string $name = null) {}

            public function getQuoter() {}

            public function getQueryCompiler(): CompilerInterface
            {
                return new class() implements CompilerInterface {
                    public function compile(
                        QueryParameters $params,
                        string $prefix,
                        FragmentInterface $fragment
                    ): string {
                        return '';
                    }
                };
            }

            public function getTransaction(?int $isolationLevel = null) {}

            public static function create(
                DriverConfig $config
            ): DriverInterface {
                return new self(null);
            }

            public function lastInsertID(?string $sequence = null)
            {
                return 1;
            }

            public function beginTransaction(?string $isolationLevel = null): bool
            {
                return true;
            }

            public function commit(): bool
            {
                return true;
            }

            public function rollBack(): bool
            {
                return true;
            }

            public function inTransaction(): bool
            {
                return false;
            }

            public function execute(string $statement, array $parameters = []): int
            {
                return 1;
            }

            public function query(string $statement, array $parameters = []): StatementInterface
            {
                return new class() implements StatementInterface, \IteratorAggregate {
                    public function fetch(int $mode = StatementInterface::FETCH_ASSOC): mixed
                    {
                        return '1';
                    }

                    public function fetchAll(int $mode = StatementInterface::FETCH_ASSOC): array
                    {
                        return ['1'];
                    }

                    public function fetchColumn(?int $columnNumber = null): mixed
                    {
                        return '1';
                    }

                    public function fetchObject(string $className = 'stdClass', array $args = []): false|object
                    {
                        return (object) ['col1' => '1'];
                    }

                    public function getIterator(): \Traversable
                    {
                        return new \ArrayIterator(['1']);
                    }

                    public function getQueryString(): string
                    {
                        return 'SELECT 1';
                    }

                    public function rowCount(): int
                    {
                        return 1;
                    }

                    public function columnCount(): int
                    {
                        return 1;
                    }

                    public function closeCursor(): bool
                    {
                        return true;
                    }

                    public function errorCode(): ?string
                    {
                        return null;
                    }

                    public function errorInfo(): array
                    {
                        return [];
                    }

                    public function close(): void {}
                };
            }

            public function getServerVersion(): string
            {
                return '5.7.0';
            }

            public function isReadonly(): bool
            {
                return false;
            }

            public function getTimezone(): \DateTimeZone
            {
                return new \DateTimeZone('UTC');
            }

            public function setTimezone(\DateTimeZone $timezone): void {}

            public function getSource(): ?string
            {
                return null;
            }

            public function setSource(?string $source): void {}

            public function getSchemaHandler(): HandlerInterface
            {
                return new class() implements HandlerInterface {
                    public function getPrimaryKeys(string $table): array
                    {
                        return [];
                    }

                    public function getColumns(string $table): array
                    {
                        return [];
                    }

                    public function getIndexes(string $table): array
                    {
                        return [];
                    }

                    public function getForeignKeys(string $table): array
                    {
                        return [];
                    }

                    public function getTableNames(?string $prefix = null): array
                    {
                        return [];
                    }

                    public function hasTable(string $table): bool
                    {
                        return true;
                    }
                };
            }

            public function getQueryBuilder(): BuilderInterface
            {
                return new class() implements BuilderInterface {
                    public function withDriver($driver, $prefix = null): static
                    {
                        return $this;
                    }
                };
            }

            public function quote(mixed $value, int $type = \PDO::PARAM_STR): string
            {
                return is_string($value) ? "'" . addslashes($value) . "'" : (string) $value;
            }

            public function commitTransaction(): bool
            {
                return true;
            }

            public function rollbackTransaction(): bool
            {
                return true;
            }

            public function getTransactionLevel(): int
            {
                return 0;
            }
        };
        $database = new class($driver) implements DatabaseInterface {
            private $driver;
            public const WRITE = 0;

            public function __construct($driver)
            {
                $this->driver = $driver;
            }

            public function getDriver(int $type = self::WRITE): DriverInterface
            {
                return $this->driver;
            }

            public function getName(): string
            {
                return 'default';
            }

            public function getPrefix(): string
            {
                return '';
            }

            public function getTables(): array
            {
                return ['users', 'posts'];
            }

            public function hasTable(string $name): bool
            {
                return in_array($name, ['users', 'posts'], true);
            }

            public function table(string $name): TableInterface
            {
                return new class($name) implements TableInterface {
                    private $name;

                    public function __construct($name)
                    {
                        $this->name = $name;
                    }

                    public function getName(): string
                    {
                        return $this->name;
                    }

                    public function getColumns(): array
                    {
                        // Simula colunas reais para as tabelas users e posts
                        if ('users' === $this->name || 'posts' === $this->name) {
                            return [
                                'id' => [
                                    'name' => 'id',
                                    'type' => 'int',
                                    'primary' => true,
                                    'nullable' => false,
                                ],
                            ];
                        }

                        return [];
                    }

                    public function getIndexes(): array
                    {
                        return [];
                    }

                    public function getForeignKeys(): array
                    {
                        return [];
                    }

                    public function getPrimaryKeys(): array
                    {
                        return ['id'];
                    }

                    public function getColumn(string $name): array
                    {
                        $cols = $this->getColumns();

                        return $cols[$name] ?? [];
                    }

                    public function hasColumn(string $name): bool
                    {
                        $cols = $this->getColumns();

                        return isset($cols[$name]);
                    }

                    public function getIndex(string $name): array
                    {
                        return [];
                    }

                    public function hasIndex(array $columns = []): bool
                    {
                        return false;
                    }

                    public function getForeignKey(string $name): array
                    {
                        return [];
                    }

                    public function hasForeignKey($name): bool
                    {
                        return false;
                    }
                };
            }

            public function execute(string $query, array $parameters = []): int
            {
                return 1;
            }

            public function query(string $query, array $parameters = []): StatementInterface
            {
                return new class() implements StatementInterface, \IteratorAggregate {
                    public function fetch(int $mode = StatementInterface::FETCH_ASSOC): mixed
                    {
                        return '1';
                    }

                    public function fetchAll(int $mode = StatementInterface::FETCH_ASSOC): array
                    {
                        return ['1'];
                    }

                    public function fetchColumn(?int $columnNumber = null): mixed
                    {
                        return '1';
                    }

                    public function fetchObject(string $className = 'stdClass', array $args = []): false|object
                    {
                        return (object) ['col1' => '1'];
                    }

                    public function getIterator(): \Traversable
                    {
                        return new \ArrayIterator(['1']);
                    }

                    public function getQueryString(): string
                    {
                        return 'SELECT 1';
                    }

                    public function rowCount(): int
                    {
                        return 1;
                    }

                    public function columnCount(): int
                    {
                        return 1;
                    }

                    public function closeCursor(): bool
                    {
                        return true;
                    }

                    public function errorCode(): ?string
                    {
                        return null;
                    }

                    public function errorInfo(): array
                    {
                        return [];
                    }

                    public function close(): void {}
                };
            }

            public function getType(): string
            {
                return 'mysql';
            }

            public function withPrefix(string $prefix, bool $add = true): DatabaseInterface
            {
                return $this;
            }

            public function insert(string $table = ''): InsertQuery
            {
                return new class() implements InsertQuery {
                    public function columns(array $columns): static
                    {
                        return $this;
                    }

                    public function values(array $values): static
                    {
                        return $this;
                    }

                    public function returning(array $fields): static
                    {
                        return $this;
                    }

                    public function run(): int
                    {
                        return 1;
                    }
                };
            }

            public function update(
                string $table = '',
                array $values = [],
                array $where = []
            ): UpdateQuery {
                return new class() implements UpdateQuery {
                    public function set(array $values): static
                    {
                        return $this;
                    }

                    public function where(string $column, string $operator, mixed $value = null): static
                    {
                        return $this;
                    }

                    public function andWhere(string $column, string $operator, mixed $value = null): static
                    {
                        return $this;
                    }

                    public function orWhere(string $column, string $operator, mixed $value = null): static
                    {
                        return $this;
                    }

                    public function run(): int
                    {
                        return 1;
                    }
                };
            }

            public function delete(string $table = '', array $where = []): DeleteQuery
            {
                return new class() implements DeleteQuery {
                    public function where(string $column, string $operator, mixed $value = null): static
                    {
                        return $this;
                    }

                    public function andWhere(string $column, string $operator, mixed $value = null): static
                    {
                        return $this;
                    }

                    public function orWhere(string $column, string $operator, mixed $value = null): static
                    {
                        return $this;
                    }

                    public function run(): int
                    {
                        return 1;
                    }
                };
            }

            public function select(mixed $columns = '*'): SelectQuery
            {
                return new class() implements SelectQuery {
                    private string $table = '';

                    public function from(string $table): static
                    {
                        $this->table = $table;

                        return $this;
                    }

                    public function distinct(): static
                    {
                        return $this;
                    }

                    public function columns(array $columns): static
                    {
                        return $this;
                    }

                    public function where(string $column, string $operator, mixed $value = null): static
                    {
                        return $this;
                    }

                    public function andWhere(string $column, string $operator, mixed $value = null): static
                    {
                        return $this;
                    }

                    public function orWhere(string $column, string $operator, mixed $value = null): static
                    {
                        return $this;
                    }

                    public function orderBy(string $column, string $direction = 'ASC'): static
                    {
                        return $this;
                    }

                    public function limit(int $limit, int $offset = 0): static
                    {
                        return $this;
                    }

                    public function offset(int $offset): static
                    {
                        return $this;
                    }

                    public function buildQuery(): array
                    {
                        return [];
                    }

                    public function getParameters(): array
                    {
                        return [];
                    }

                    public function fetchAll(): array
                    {
                        if ('users' === $this->table) {
                            return [['id' => 1, 'name' => 'Test']];
                        }
                        if ('posts' === $this->table) {
                            return [['id' => 1, 'title' => 'Hello']];
                        }

                        return [['id' => 1]];
                    }

                    public function fetchOne(): ?array
                    {
                        if ('users' === $this->table) {
                            return ['id' => 1, 'name' => 'Test'];
                        }
                        if ('posts' === $this->table) {
                            return ['id' => 1, 'title' => 'Hello'];
                        }

                        return ['id' => 1];
                    }

                    public function count(): int
                    {
                        return 1;
                    }
                };
            }

            public function transaction(callable $callback, ?string $isolationLevel = null): mixed
            {
                return $callback($this);
            }

            public function begin(?string $isolationLevel = null): bool
            {
                return true;
            }

            public function commit(): bool
            {
                return true;
            }

            public function rollback(): bool
            {
                return true;
            }
        };
        // Mock DBAL compatível com métodos comuns do DatabaseManager
        $dbal = new class($database) {
            private $database;

            public function __construct($database)
            {
                $this->database = $database;
            }

            public function database($name = null)
            {
                if (null === $name || 'default' === $name) {
                    return $this->database;
                }
            }

            public function getDatabases()
            {
                return ['default' => $this->database];
            }

            public function getDatabaseNames()
            {
                return ['default'];
            }

            public function has($name)
            {
                return 'default' === $name;
            }
        };
        $app->singleton('cycle.database', fn () => $dbal);
        // Mock ORM
        $schema = $this->createMock(SchemaInterface::class);
        $schema->method('getRoles')->willReturn(['user', 'post']);
        $schema->method('define')->willReturnCallback(
            function ($role, $key) {
                if (SchemaInterface::ENTITY === $key) {
                    return 'user' === $role ? 'App\Models\User' : 'App\Models\Post';
                }
                if (SchemaInterface::TABLE === $key) {
                    return 'user' === $role ? 'users' : 'posts';
                }
            }
        );
        $orm = new class($schema) {
            private $schema;

            public function __construct($schema)
            {
                $this->schema = $schema;
            }

            public function getSchema()
            {
                return $this->schema;
            }
        };
        $app->singleton('cycle.orm', fn () => $orm);
        // Mock mínimos para EM e Schema
        $app->singleton(
            'cycle.em',
            fn () => new class() {}
        );
        $app->singleton('cycle.schema', fn () => $schema);
        $app->singleton(
            'cycle.migrator',
            fn () => new class() {}
        );
        $app->singleton(
            'cycle.repository',
            fn () => new class() {}
        );
        $result = CycleHealthCheck::check($app);
        // var_dump($result); // DEBUG: Exibe resultado do health check
        $this->assertEquals('healthy', $result['cycle_orm']);
        $this->assertArrayHasKey('response_time_ms', $result);
        $this->assertIsFloat($result['response_time_ms']);
    }
}
