<?php

namespace CAFernandes\ExpressPHP\CycleORM\Tests;

use PHPUnit\Framework\TestCase;
use CAFernandes\ExpressPHP\CycleORM\Health\CycleHealthCheck;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Query\QueryParameters;
use Express\Core\Application;

class HealthCheckTest extends TestCase
{
  public function testHealthCheckWithNoServices(): void
  {
    $app = new class extends Application {
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
        if (!isset($this->services[$abstract])) throw new \RuntimeException("Service $abstract not found");
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
    $app = new class extends Application {
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
        if (!isset($this->services[$abstract])) throw new \RuntimeException("Service $abstract not found");
        $factory = $this->services[$abstract];
        return $factory();
      }
    };
    // Mock DBAL
    $pdo = new class {
      public function getAttribute($attr)
      {
        if ($attr === \PDO::ATTR_SERVER_VERSION) {
          return '5.7.0';
        }
        return null;
      }
      public function query($sql)
      {
        return new class {
          public function fetchColumn()
          {
            return '1';
          }
        };
      }
    };
    $driver = new class($pdo) implements \Cycle\Database\Driver\DriverInterface {
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
      public function getSchema(string $name = null)
      {
        return null;
      }
      public function getQuoter()
      {
        return null;
      }
      public function getQueryCompiler(): \Cycle\Database\Driver\CompilerInterface
      {
        return new class implements \Cycle\Database\Driver\CompilerInterface {
          public function compile(
            QueryParameters $params,
            string $prefix,
            FragmentInterface $fragment
          ): string {
            return '';
          }
        };
      }
      public function getTransaction(int $isolationLevel = null)
      {
        return null;
      }
      public static function create(\Cycle\Database\Config\DriverConfig $config): \Cycle\Database\Driver\DriverInterface
      {
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
      public function query(string $statement, array $parameters = []): \Cycle\Database\StatementInterface
      {
        return new class implements \Cycle\Database\StatementInterface, \IteratorAggregate {
          public function fetch(int $mode = \Cycle\Database\StatementInterface::FETCH_ASSOC): mixed
          {
            return '1';
          }
          public function fetchAll(int $mode = \Cycle\Database\StatementInterface::FETCH_ASSOC): array
          {
            return ['1'];
          }
          public function fetchColumn(?int $columnNumber = null): mixed
          {
            return '1';
          }
          public function fetchObject(string $className = 'stdClass', array $args = []): object|false
          {
            return (object)['col1' => '1'];
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
      public function getSchemaHandler(): \Cycle\Database\Driver\HandlerInterface
      {
        return new class implements \Cycle\Database\Driver\HandlerInterface {
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
      public function getQueryBuilder(): \Cycle\Database\Query\BuilderInterface
      {
        return new class implements \Cycle\Database\Query\BuilderInterface {
          public function withDriver($driver, $prefix = null): static
          {
            return $this;
          }
        };
      }
      public function quote(mixed $value, int $type = \PDO::PARAM_STR): string
      {
        return is_string($value) ? "'" . addslashes($value) . "'" : (string)$value;
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
    $database = new class($driver) implements \Cycle\Database\DatabaseInterface {
      private $driver;
      public const WRITE = 0;
      public function __construct($driver)
      {
        $this->driver = $driver;
      }
      public function getDriver(int $type = self::WRITE): \Cycle\Database\Driver\DriverInterface
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
      public function table(string $name): \Cycle\Database\TableInterface
      {
        return new class($name) implements \Cycle\Database\TableInterface {
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
            if ($this->name === 'users' || $this->name === 'posts') {
              return [
                'id' => [
                  'name' => 'id',
                  'type' => 'int',
                  'primary' => true,
                  'nullable' => false,
                ]
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
      public function query(string $query, array $parameters = []): \Cycle\Database\StatementInterface
      {
        return new class implements \Cycle\Database\StatementInterface, \IteratorAggregate {
          public function fetch(int $mode = \Cycle\Database\StatementInterface::FETCH_ASSOC): mixed
          {
            return '1';
          }
          public function fetchAll(int $mode = \Cycle\Database\StatementInterface::FETCH_ASSOC): array
          {
            return ['1'];
          }
          public function fetchColumn(?int $columnNumber = null): mixed
          {
            return '1';
          }
          public function fetchObject(string $className = 'stdClass', array $args = []): object|false
          {
            return (object)['col1' => '1'];
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
      public function withPrefix(string $prefix, bool $add = true): \Cycle\Database\DatabaseInterface
      {
        return $this;
      }
      public function insert(string $table = ''): \Cycle\Database\Query\InsertQuery
      {
        $query = new class implements \Cycle\Database\Query\InsertQuery {
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
        return $query;
      }
      public function update(string $table = '', array $values = [], array $where = []): \Cycle\Database\Query\UpdateQuery
      {
        $query = new class implements \Cycle\Database\Query\UpdateQuery {
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
        return $query;
      }
      public function delete(string $table = '', array $where = []): \Cycle\Database\Query\DeleteQuery
      {
        $query = new class implements \Cycle\Database\Query\DeleteQuery {
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
        return $query;
      }
      public function select(mixed $columns = '*'): \Cycle\Database\Query\SelectQuery
      {
        $query = new class implements \Cycle\Database\Query\SelectQuery {
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
            if ($this->table === 'users') {
              return [['id' => 1, 'name' => 'Test']];
            }
            if ($this->table === 'posts') {
              return [['id' => 1, 'title' => 'Hello']];
            }
            return [['id' => 1]];
          }
          public function fetchOne(): ?array
          {
            if ($this->table === 'users') {
              return ['id' => 1, 'name' => 'Test'];
            }
            if ($this->table === 'posts') {
              return ['id' => 1, 'title' => 'Hello'];
            }
            return ['id' => 1];
          }
          public function count(): int
          {
            return 1;
          }
        };
        return $query;
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
        if ($name === null || $name === 'default') {
          return $this->database;
        }
        return null;
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
        return $name === 'default';
      }
    };
    $app->singleton('cycle.database', fn() => $dbal);
    // Mock ORM
    $schema = $this->createMock(\Cycle\ORM\SchemaInterface::class);
    $schema->method('getRoles')->willReturn(['user', 'post']);
    $schema->method('define')->willReturnCallback(function ($role, $key) {
      if ($key === \Cycle\ORM\SchemaInterface::ENTITY) {
        return $role === 'user' ? 'App\\Models\\User' : 'App\\Models\\Post';
      }
      if ($key === \Cycle\ORM\SchemaInterface::TABLE) {
        return $role === 'user' ? 'users' : 'posts';
      }
      return null;
    });
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
    $app->singleton('cycle.orm', fn() => $orm);
    // Mock mínimos para EM e Schema
    $app->singleton('cycle.em', fn() => new class {});
    $app->singleton('cycle.schema', fn() => $schema);
    $app->singleton('cycle.migrator', fn() => new class {});
    $app->singleton('cycle.repository', fn() => new class {});
    $result = CycleHealthCheck::check($app);
    // var_dump($result); // DEBUG: Exibe resultado do health check
    $this->assertEquals('healthy', $result['cycle_orm']);
    $this->assertArrayHasKey('response_time_ms', $result);
    $this->assertIsFloat($result['response_time_ms']);
  }
}
