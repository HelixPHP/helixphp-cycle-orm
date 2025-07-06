<?php
namespace Helix\CycleORM\Tests\Repository;

use Cycle\ORM\SchemaInterface;

class FakeSchema implements SchemaInterface {
    public function getRoles(): array { return ['stdClass']; }
    public function getRelations(string $role): array { return []; }
    public function defines(string $role): bool { return $role === 'stdClass'; }
    public function define(string $role, int $property): mixed {
        if ($role === 'stdClass') {
            if ($property === SchemaInterface::ENTITY) {
                return \stdClass::class;
            }
            if ($property === SchemaInterface::TABLE) {
                return 'std_class_table';
            }
            if ($property === SchemaInterface::DATABASE) {
                return 'default';
            }
            if ($property === SchemaInterface::COLUMNS) {
                return ['id', 'name', 'description', 'active', 'createdAt'];
            }
        }
        return null;
    }
    public function defineRelation(string $role, string $relation): array { return []; }
    public function resolveAlias(string $role): ?string { return $role === 'stdClass' ? 'stdClass' : null; }
    public function getInheritedRoles(string $parent): array { return []; }
}
