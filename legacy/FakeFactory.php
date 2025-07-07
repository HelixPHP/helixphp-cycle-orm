<?php
namespace PivotPHP\CycleORM\Tests\Repository\Fakes;

use Cycle\ORM\FactoryInterface;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\MapperInterface;
use Cycle\ORM\Select\LoaderInterface;
use Cycle\ORM\Select\Select;
use Cycle\ORM\Collection\CollectionFactoryInterface;
use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\Service\SourceProviderInterface;
use Cycle\ORM\ORMInterface;
use Cycle\Database\DatabaseInterface;
use Cycle\ORM\Parser\TypecastInterface;
use Cycle\ORM\RepositoryInterface;
use Cycle\ORM\Select\SourceInterface;
use Cycle\ORM\Select\ScopeInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\Command\CommandInterface;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\TableInterface;
use Cycle\Database\StatementInterface;
use Cycle\Database\Query\InsertQuery;
use Cycle\Database\Query\UpdateQuery;
use Cycle\Database\Query\DeleteQuery;
use Cycle\Database\Query\SelectQuery;
use Cycle\Database\Query\BuilderInterface;
use Cycle\Database\Driver\HandlerInterface;
use Cycle\Database\Config\DriverConfig;
use Cycle\Database\Driver\CompilerInterface;
use DateTimeZone;

class FakeFactory implements FactoryInterface {
    public function mapper(ORMInterface $orm, string $role): MapperInterface {
        return new class implements MapperInterface {
            public function getRole(): string { return 'fake'; }
            /**
     * @param array<string, mixed> $data
     */
    public function init(array $data): void {}
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function cast(array $data): array { return $data; }
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function uncast(array $data): array { return $data; }
    /**
     * @param array<string, mixed> $data
     */
    public function hydrate(object $entity, array $data): object { return $entity; }
    /**
     * @return array<string, mixed>
     */
    public function extract(object $entity): array { return []; }
    /**
     * @return array<string, mixed>
     */
    public function fetchFields(): array { return []; }
    /**
     * @return array<string, mixed>
     */
    public function fetchRelations(): array { return []; }
    /**
     * @param array<string, mixed> $values
     * @return array<string, mixed>
     */
    public function mapColumns(array $values): array { return $values; }
            public function queueCreate(object $entity, Node $node, State $state): CommandInterface { return new class implements CommandInterface {
                public function execute(): void {}
                public function isReady(): bool { return true; }
                public function complete(): void {}
                public function getState(): int { return 0; }
                public function getException(): ?\Throwable { return null; }
                public function getParent(): ?CommandInterface { return null; }
                public function getChildren(): array { return []; }
                public function addChild(CommandInterface $child): void {}
            }; }
            public function queueUpdate(object $entity, Node $node, State $state): CommandInterface { return $this->queueCreate($entity, $node, $state); }
            public function queueDelete(object $entity, Node $node, State $state): CommandInterface { return $this->queueCreate($entity, $node, $state); }
        };
    }
    public function loader(SchemaInterface $schema, SourceProviderInterface $sourceProvider, string $role, string $relation): LoaderInterface {
        return new class implements LoaderInterface {
            public function getAlias(): string { return 'fake'; }
            public function getTarget(): string { return 'fake'; }
            public function fieldAlias(string $field): ?string { return $field; }
            public function withContext(LoaderInterface $parent, array $options = []): LoaderInterface { return $this; }
            public function createNode(): \Cycle\ORM\Parser\AbstractNode { return new class extends \Cycle\ORM\Parser\AbstractNode {}; }
            public function loadData(\Cycle\ORM\Parser\AbstractNode $node, bool $includeRole = false): void {}
            public function setSubclassesLoading(bool $enabled): void {}
            public function isHierarchical(): bool { return false; }
        };
    }
    public function repository(ORMInterface $orm, SchemaInterface $schema, string $role, ?Select $select = null): RepositoryInterface {
        return new class implements RepositoryInterface {
            public function findByPK(mixed $id): ?object { return null; }
            public function findOne(array $scope = []): ?object { return null; }
            public function findAll(array $scope = []): iterable { return []; }
        };
    }
    public function source(SchemaInterface $schema, string $role): SourceInterface {
        return new class implements SourceInterface {
            public function getDatabase(): DatabaseInterface {
                return new class implements DatabaseInterface {
                    public function getName(): string { return 'fake'; }
                    public function getType(): string { return 'fake'; }
                    public function getDriver(int $type = self::WRITE): DriverInterface { return new class implements DriverInterface {
                        public static function create(DriverConfig $config): DriverInterface { return new self(); }
                        public function isReadonly(): bool { return false; }
                        public function getType(): string { return 'fake'; }
                        public function getTimezone(): DateTimeZone { return new DateTimeZone('UTC'); }
                        public function getSchemaHandler(): HandlerInterface { return new class implements HandlerInterface {
                            public function withDriver(DriverInterface $driver): HandlerInterface { return $this; }
                            public function getTableNames(string $prefix = ''): array { return []; /* @var array<int, non-empty-string> */ }
                            public function hasTable(string $table): bool { return false; }
                            public function getSchema(string $table, ?string $prefix = null): \Cycle\Database\Schema\AbstractTable { return new class extends \Cycle\Database\Schema\AbstractTable {
    public function createColumn(string $name, string $type): void {}
    public function createForeign(string $name, string $foreignTable, array $columns, array $foreignColumns): void {}
    public function createIndex(string $name, array $columns): void {}
    public function fetchColumns(): array { return []; }
    public function fetchIndexes(): array { return []; }
    public function fetchPrimaryKeys(): array { return []; }
    public function fetchReferences(): array { return []; }
}; }
public function createTable(\Cycle\Database\Schema\AbstractTable $table): void {}
public function eraseTable(\Cycle\Database\Schema\AbstractTable $table): void {}
public function dropTable(\Cycle\Database\Schema\AbstractTable $table): void {}
public function syncTable(\Cycle\Database\Schema\AbstractTable $table, int $operation = self::DO_ALL): void {}
public function renameTable(string $table, string $name): void {}
public function createColumn(\Cycle\Database\Schema\AbstractTable $table, \Cycle\Database\Schema\AbstractColumn $column): void {}
public function dropColumn(\Cycle\Database\Schema\AbstractTable $table, \Cycle\Database\Schema\AbstractColumn $column): void {}
public function alterColumn(\Cycle\Database\Schema\AbstractTable $table, \Cycle\Database\Schema\AbstractColumn $initial, \Cycle\Database\Schema\AbstractColumn $column): void {}
public function createIndex(\Cycle\Database\Schema\AbstractTable $table, \Cycle\Database\Schema\AbstractIndex $index): void {}
public function dropIndex(\Cycle\Database\Schema\AbstractTable $table, \Cycle\Database\Schema\AbstractIndex $index): void {}
public function alterIndex(\Cycle\Database\Schema\AbstractTable $table, \Cycle\Database\Schema\AbstractIndex $initial, \Cycle\Database\Schema\AbstractIndex $index): void {}
public function createForeignKey(\Cycle\Database\Schema\AbstractTable $table, \Cycle\Database\Schema\AbstractForeignKey $foreignKey): void {}
public function dropForeignKey(\Cycle\Database\Schema\AbstractTable $table, \Cycle\Database\Schema\AbstractForeignKey $foreignKey): void {}
public function alterForeignKey(\Cycle\Database\Schema\AbstractTable $table, \Cycle\Database\Schema\AbstractForeignKey $initial, \Cycle\Database\Schema\AbstractForeignKey $foreignKey): void {}
public function dropConstrain(\Cycle\Database\Schema\AbstractTable $table, string $constraint): void {}
                        }; }
                        public function getQueryCompiler(): CompilerInterface { return new class implements CompilerInterface {
                            public function quoteIdentifier(string $identifier): string { return $identifier; }
                            public function compile(\Cycle\Database\Query\QueryParameters $params, string $prefix, \Cycle\Database\Injection\FragmentInterface $fragment): string { return ''; }
                        }; }
                        public function getQueryBuilder(): BuilderInterface { return new class implements BuilderInterface {
                            public function withDriver(DriverInterface $driver): BuilderInterface { return $this; }
                            public function insertQuery(string $prefix, ?string $table = null): InsertQuery { return new InsertQuery(); }
                            public function selectQuery(string $prefix, array $from = [], array $columns = []): SelectQuery { return new SelectQuery(); }
                            public function deleteQuery(string $prefix, ?string $from = null, array $where = []): DeleteQuery { return new DeleteQuery(); }
                            public function updateQuery(string $prefix, ?string $table = null, array $where = [], array $values = []): UpdateQuery { return new UpdateQuery(); }
                        }; }
                        public function connect(): void {}
                        public function isConnected(): bool { return true; }
                        public function disconnect(): void {}
                        public function quote(mixed $value, int $type = \PDO::PARAM_STR): string { return (string)$value; }
                        public function query(string $statement, array $parameters = []): StatementInterface { return new class implements StatementInterface {
                            public function getQueryString(): string { return 'fake_query'; /* Garantir non-empty-string */ }
                            public function fetch(int $mode = self::FETCH_ASSOC): mixed { return false; }
                            public function fetchColumn(?int $columnNumber = null): mixed { return null; }
                            public function fetchAll(int $mode = self::FETCH_ASSOC): array { return []; /* @var array<int, array<string, mixed>> */ }
                            public function rowCount(): int { return 0; }
                            public function columnCount(): int { return 0; }
                            public function closeCursor(): bool { return true; }
                            public function errorCode(): ?string { return null; }
                            public function errorInfo(): array { return []; /* @var array<int, string|null> */ }
                            public function getIterator(): \Traversable { return new \ArrayIterator([]); }
                        }; }
                        public function execute(string $query, array $parameters = []): int { return 0; }
                        public function lastInsertID(?string $sequence = null) { return 1; }
                        public function beginTransaction(?string $isolationLevel = null): bool { return true; }
                        public function commitTransaction(): bool { return true; }
                        public function rollbackTransaction(): bool { return true; }
                        public function getTransactionLevel(): int { return 0; }
                    }; }
                    public function withPrefix(string $prefix, bool $add = true): DatabaseInterface { return $this; }
                    public function getPrefix(): string { return ''; }
                    public function hasTable(string $name): bool { return false; }
                    public function getTables(): array { return []; }
                    public function table(string $name): TableInterface { return new class implements TableInterface {
                        public function exists(): bool { return false; }
                        public function getName(): string { return 'fake'; }
                        public function getFullName(): string { return 'fake'; }
                        public function getPrimaryKeys(): array { return []; /* @var array<int, string> */ }
                        public function hasColumn(string $name): bool { return false; }
                        public function getColumns(): array { return []; /* @var array<string, mixed> */ }
                        public function hasIndex(array $columns = []): bool { return false; }
                        public function getIndexes(): array { return []; /* @var array<string, mixed> */ }
                        public function hasForeignKey(array $columns): bool { return false; }
                        public function getForeignKeys(): array { return []; /* @var array<string, mixed> */ }
                        public function getDependencies(): array { return []; /* @var array<string, mixed> */ }
                    }; }
                    public function execute(string $query, array $parameters = []): int { return 0; }
                    public function query(string $query, array $parameters = []): StatementInterface { return new class implements StatementInterface {
                        public function getQueryString(): string { return 'fake_query'; /* Garantir non-empty-string */ }
                        public function fetch(int $mode = self::FETCH_ASSOC): mixed { return false; }
                        public function fetchColumn(?int $columnNumber = null): mixed { return null; }
                        public function fetchAll(int $mode = self::FETCH_ASSOC): array { return []; /* @var array<int, array<string, mixed>> */ }
                        public function rowCount(): int { return 0; }
                        public function columnCount(): int { return 0; }
                        public function closeCursor(): bool { return true; }
                        public function errorCode(): ?string { return null; }
                        public function errorInfo(): array { return []; /* @var array<int, string|null> */ }
                        public function getIterator(): \Traversable { return new \ArrayIterator([]); }
                    }; }
                    public function insert(string $table = ''): InsertQuery { return new InsertQuery(); }
                    public function update(string $table = '', array $values = [], array $where = []): UpdateQuery { return new UpdateQuery(); }
                    public function delete(string $table = '', array $where = []): DeleteQuery { return new DeleteQuery(); }
                    public function select(mixed $columns = '*'): SelectQuery { return new SelectQuery(); }
                    public function transaction(callable $callback, ?string $isolationLevel = null): mixed { return $callback($this); }
                    public function begin(?string $isolationLevel = null): bool { return true; }
                    public function commit(): bool { return true; }
                    public function rollback(): bool { return true; }
                };
            }
            public function getTable(): string { return 'fake'; }
            public function withScope(?ScopeInterface $scope): SourceInterface { return $this; }
            public function getScope(): ?ScopeInterface { return null; }
        };
    }
    public function database(?string $database = null): DatabaseInterface {
        return (new self())->source(new class implements SchemaInterface {
            public function getRoles(): array { return []; }
            public function getRelations(string $role): array { return []; }
            public function defines(string $role): bool { return false; }
            public function define(string $role, int $property): mixed { return null; }
            public function defineRelation(string $role, string $relation): array { return []; }
            public function resolveAlias(string $role): ?string { return null; }
            public function getInheritedRoles(string $parent): array { return []; }
        }, 'fake')->getDatabase();
    }
    public function typecast(SchemaInterface $schema, DatabaseInterface $database, string $role): ?TypecastInterface { return null; }
    public function collection(?string $name = null): CollectionFactoryInterface {
        return new class implements CollectionFactoryInterface {
            public function getInterface(): ?string { return null; }
            public function withCollectionClass(string $class): static { return $this; }
            public function collect(iterable $data): iterable { return $data; }
        };
    }
    public function relation(ORMInterface $orm, SchemaInterface $schema, string $role, string $relation): RelationInterface {
        return new class implements RelationInterface {
            public function getInnerKeys(): array { return []; }
            public function getName(): string { return 'fake'; }
            public function isCascade(): bool { return false; }
            public function prepare(\Cycle\ORM\Transaction\Pool $pool, \Cycle\ORM\Transaction\Tuple $tuple, mixed $related, bool $load = true): void {}
            public function queue(\Cycle\ORM\Transaction\Pool $pool, \Cycle\ORM\Transaction\Tuple $tuple): void {}
        };
    }
    public function withDefaultSchemaClasses(array $defaults): FactoryInterface { return $this; }
    public function withCollectionFactory(string $alias, CollectionFactoryInterface $factory, ?string $interface = null): FactoryInterface { return $this; }
    public function getDatabaseProvider(): ?object { return null; }
    public function make(string $alias, array $parameters = []): mixed { return null; }
}
