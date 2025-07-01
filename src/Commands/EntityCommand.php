<?php

namespace CAFernandes\ExpressPHP\CycleORM\Commands;

/**
 * Comando para gerar entidades do Cycle ORM.
 *
 * Exemplos de uso:
 *   php bin/console make:entity User
 *
 * Métodos:
 *   - handle(): Executa o comando principal.
 *   - generateEntityContent(): Gera o conteúdo da classe de entidade.
 *   - getEntityPath(): Retorna o caminho do arquivo da entidade.
 *   - getTableName(): Gera o nome da tabela a partir do nome da classe.
 */
class EntityCommand extends BaseCommand
{
  /**
   * Executa o comando principal para criar uma nova entidade.
   *
   * @return int Código de status (0 = sucesso, 1 = erro)
   */
  public function handle(): int
  {
    $name = $this->argument('name');

    if (!$name) {
      $this->error('Entity name is required');
      return 1;
    }

    $className = ucfirst($name);
    $this->info("Creating entity: {$className}");

    try {
      $content = $this->generateEntityContent($className);
      $path = $this->getEntityPath($className);

      if (file_exists($path)) {
        $this->error("Entity {$className} already exists!");
        return 1;
      }

      if (!is_dir(dirname($path))) {
        mkdir(dirname($path), 0755, true);
      }

      file_put_contents($path, $content);
      $this->info("Entity created: {$path}");

      return 0;
    } catch (\Exception $e) {
      $this->error("Failed to create entity: " . $e->getMessage());
      return 1;
    }
  }

  /**
   * Gera o conteúdo PHP da classe de entidade.
   *
   * @param string $className Nome da classe da entidade.
   * @return string Código PHP da entidade.
   */
  private function generateEntityContent(string $className): string
  {
    $tableName = $this->getTableName($className);

    return <<<PHP
<?php

namespace App\Models;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;

#[Entity(table: '{$tableName}')]
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
   * Retorna o caminho do arquivo da entidade.
   *
   * @param string $className Nome da classe da entidade.
   * @return string Caminho absoluto do arquivo.
   */
  private function getEntityPath(string $className): string
  {
    // Se rodando em ambiente de teste, salvar no sys_get_temp_dir()
    if (\defined('PHPUNIT_COMPOSER_INSTALL') || getenv('APP_ENV') === 'testing') {
      $dir = sys_get_temp_dir() . '/cycle_test_models';
      if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
      }
      return $dir . "/{$className}.php";
    }
    // Verificar se função app_path existe
    if (function_exists('app_path')) {
      return app_path("Models/{$className}.php");
    }
    // Fallback para estrutura padrão
    return __DIR__ . "/../../../../app/Models/{$className}.php";
  }

  /**
   * Gera o nome da tabela a partir do nome da classe.
   *
   * @param string $className Nome da classe da entidade.
   * @return string Nome da tabela.
   */
  private function getTableName(string $className): string
  {
    $snake = preg_replace('/([a-z])([A-Z])/', '$1_$2', $className);
    return $this->toLower($snake ?? '');
  }

  /**
   * Exemplo de método que usa strtolower
   *
   * @param string $string
   * @return string
   */
  private function toLower(string $string): string
  {
    return strtolower($string);
  }
}
