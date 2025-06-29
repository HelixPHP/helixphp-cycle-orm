<?php
namespace ExpressPHP\CycleORM\Commands;

use Express\Console\Command;

/**
 * Comando para gerar entidades
 */
class EntityCommand extends Command
{
    protected string $signature = 'make:entity {name : Entity name}';
    protected string $description = 'Generate a new Cycle ORM entity';

    public function handle(): int
    {
        $name = $this->argument('name');
        $className = ucfirst($name);

        $this->info("Creating entity: {$className}");

        $content = $this->generateEntityContent($className);
        $path = $this->getEntityPath($className);

        if (file_exists($path)) {
            $this->error("Entity {$className} already exists!");
            return self::FAILURE;
        }

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $content);

        $this->info("Entity created: {$path}");
        return self::SUCCESS;
    }

    /**
     * Gera conteúdo da entidade
     */
    private function generateEntityContent(string $className): string
    {
        return <<<PHP
<?php

namespace App\Models;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;

#[Entity(table: '{$this->getTableName($className)}')]
class {$className}
{
    #[Column(type: 'primary')]
    public int \$id;

    #[Column(type: 'datetime')]
    public \DateTimeInterface \$createdAt;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface \$updatedAt = null;

    public function __construct()
    {
        \$this->createdAt = new \DateTime();
    }
}
PHP;
    }

    /**
     * Obtém caminho da entidade
     */
    private function getEntityPath(string $className): string
    {
        return app_path("Models/{$className}.php");
    }

    /**
     * Obtém nome da tabela
     */
    private function getTableName(string $className): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $className));
    }
}
