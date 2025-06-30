<?php

/**
 * Script de instalação da Express-PHP Cycle ORM Extension
 * Configura o ambiente e cria arquivos necessários
 */

class ExpressCycleInstaller
{
  private string $projectRoot;
  private array $createdFiles = [];
  private array $errors = [];

  public function __construct()
  {
    $this->projectRoot = $this->findProjectRoot();
  }

  public function install(): int
  {
    $this->printHeader();

    try {
      $this->checkRequirements();
      $this->createDirectories();
      $this->createConfigFiles();
      $this->createExampleEntity();
      $this->updateGitIgnore();
      $this->printSummary();

      return 0;
    } catch (\Exception $e) {
      $this->printError("Installation failed: " . $e->getMessage());
      return 1;
    }
  }

  private function printHeader(): void
  {
    echo "\n";
    echo "🚀 Express-PHP Cycle ORM Extension Installer\n";
    echo "=============================================\n\n";
  }

  private function checkRequirements(): void
  {
    echo "📋 Checking requirements...\n";

    // Verificar versão do PHP
    if (version_compare(PHP_VERSION, '8.1.0', '<')) {
      throw new \RuntimeException('PHP 8.1.0 or higher is required');
    }
    echo "  ✅ PHP " . PHP_VERSION . " (OK)\n";

    // Verificar extensões necessárias
    $requiredExtensions = ['pdo', 'json'];
    foreach ($requiredExtensions as $ext) {
      if (!extension_loaded($ext)) {
        throw new \RuntimeException("Required extension '{$ext}' is not loaded");
      }
      echo "  ✅ Extension {$ext} (OK)\n";
    }

    // Verificar se é um projeto Express-PHP
    $composerFile = $this->projectRoot . '/composer.json';
    if (file_exists($composerFile)) {
      $composer = json_decode(file_get_contents($composerFile), true);
      if (isset($composer['require']['cafernandes/express-php'])) {
        echo "  ✅ Express-PHP project detected (OK)\n";
      } else {
        echo "  ⚠️  Express-PHP not detected in composer.json\n";
      }
    }

    echo "\n";
  }

  private function createDirectories(): void
  {
    echo "📁 Creating directories...\n";

    $directories = [
      'app/Models',
      'config',
      'database/migrations',
      'database/seeds'
    ];

    foreach ($directories as $dir) {
      $fullPath = $this->projectRoot . '/' . $dir;
      if (!is_dir($fullPath)) {
        if (mkdir($fullPath, 0755, true)) {
          echo "  ✅ Created {$dir}\n";
        } else {
          throw new \RuntimeException("Failed to create directory: {$dir}");
        }
      } else {
        echo "  ➡️  {$dir} already exists\n";
      }
    }

    echo "\n";
  }

  private function createConfigFiles(): void
  {
    echo "⚙️  Creating configuration files...\n";

    // .env.example se não existir
    $envExample = $this->projectRoot . '/.env.example';
    if (!file_exists($envExample)) {
      $envContent = $this->getEnvExampleContent();
      file_put_contents($envExample, $envContent);
      $this->createdFiles[] = '.env.example';
      echo "  ✅ Created .env.example\n";
    } else {
      echo "  ➡️  .env.example already exists\n";
    }

    // config/cycle.php se não existir
    $cycleConfig = $this->projectRoot . '/config/cycle.php';
    if (!file_exists($cycleConfig)) {
      $configContent = $this->getCycleConfigContent();
      file_put_contents($cycleConfig, $configContent);
      $this->createdFiles[] = 'config/cycle.php';
      echo "  ✅ Created config/cycle.php\n";
    } else {
      echo "  ➡️  config/cycle.php already exists\n";
    }

    echo "\n";
  }

  private function createExampleEntity(): void
  {
    echo "🏗️  Creating example entity...\n";

    $userEntity = $this->projectRoot . '/app/Models/User.php';
    if (!file_exists($userEntity)) {
      $entityContent = $this->getUserEntityContent();
      file_put_contents($userEntity, $entityContent);
      $this->createdFiles[] = 'app/Models/User.php';
      echo "  ✅ Created app/Models/User.php\n";
    } else {
      echo "  ➡️  app/Models/User.php already exists\n";
    }

    echo "\n";
  }

  private function updateGitIgnore(): void
  {
    echo "📝 Updating .gitignore...\n";

    $gitignoreFile = $this->projectRoot . '/.gitignore';
    $cyclEntries = [
      '',
      '# Cycle ORM',
      '*.sqlite',
      '*.sqlite3',
      '/storage/cache/cycle_*.cache'
    ];

    if (file_exists($gitignoreFile)) {
      $content = file_get_contents($gitignoreFile);
      if (strpos($content, '# Cycle ORM') === false) {
        file_put_contents($gitignoreFile, $content . "\n" . implode("\n", $cyclEntries));
        echo "  ✅ Updated .gitignore\n";
      } else {
        echo "  ➡️  .gitignore already contains Cycle ORM entries\n";
      }
    } else {
      file_put_contents($gitignoreFile, implode("\n", $cyclEntries));
      echo "  ✅ Created .gitignore\n";
    }

    echo "\n";
  }

  private function printSummary(): void
  {
    echo "🎉 Installation completed successfully!\n\n";

    if (!empty($this->createdFiles)) {
      echo "📄 Files created:\n";
      foreach ($this->createdFiles as $file) {
        echo "  • {$file}\n";
      }
      echo "\n";
    }

    echo "🚀 Next steps:\n";
    echo "  1. Configure your database in .env:\n";
    echo "     DB_CONNECTION=mysql\n";
    echo "     DB_HOST=localhost\n";
    echo "     DB_DATABASE=your_database\n";
    echo "     DB_USERNAME=your_username\n";
    echo "     DB_PASSWORD=your_password\n\n";

    echo "  2. Sync your database schema:\n";
    echo "     php express-cycle cycle:schema --sync\n\n";

    echo "  3. Create your first entity:\n";
    echo "     php express-cycle make:entity Post\n\n";

    echo "  4. Check system status:\n";
    echo "     php express-cycle cycle:status\n\n";

    echo "📚 Documentation: https://github.com/CAFernandes/express-php-cycle-orm-extension\n";
  }

  private function printError(string $message): void
  {
    echo "❌ {$message}\n";
  }

  private function findProjectRoot(): string
  {
    $current = getcwd();
    $maxDepth = 10;

    for ($i = 0; $i < $maxDepth; $i++) {
      if (file_exists($current . '/composer.json')) {
        return $current;
      }

      $parent = dirname($current);
      if ($parent === $current) {
        break; // Reached filesystem root
      }
      $current = $parent;
    }

    return getcwd(); // Fallback to current directory
  }

  private function getEnvExampleContent(): string
  {
    return <<<ENV
# Application
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=express_api
DB_USERNAME=root
DB_PASSWORD=

# Cycle ORM
CYCLE_SCHEMA_CACHE=true
CYCLE_AUTO_SYNC=false
CYCLE_SCHEMA_STRICT=false
CYCLE_LOG_QUERIES=false
CYCLE_DEBUG=false

# Performance
CYCLE_LAZY_LOADING=true
CYCLE_QUERY_CACHE=false
CYCLE_PRELOAD_RELATIONS=false

# Development
CYCLE_SLOW_QUERY_MS=100
CYCLE_PROFILE_QUERIES=false
CYCLE_VALIDATE_SCHEMA=false
ENV;
  }

  private function getCycleConfigContent(): string
  {
    return <<<'PHP'
<?php

// Helper functions para compatibilidade
if (!function_exists('env')) {
    function env(string $key, $default = null) {
        return $_ENV[$key] ?? getenv($key) ?: $default;
    }
}

if (!function_exists('app_path')) {
    function app_path(string $path = ''): string {
        $basePath = dirname(__DIR__);
        return $basePath . '/app' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('database_path')) {
    function database_path(string $path = ''): string {
        $basePath = dirname(__DIR__);
        return $basePath . '/database' . ($path ? '/' . ltrim($path, '/') : '');
    }
}

return [
    'database' => [
        'default' => env('DB_CONNECTION', 'mysql'),
        'databases' => [
            'default' => ['connection' => env('DB_CONNECTION', 'mysql')]
        ],
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => env('DB_HOST', 'localhost'),
                'port' => (int) env('DB_PORT', 3306),
                'database' => env('DB_DATABASE'),
                'username' => env('DB_USERNAME'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => env('DB_CHARSET', 'utf8mb4'),
                'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            ],
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => env('DB_DATABASE', database_path('database.sqlite')),
                'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            ]
        ]
    ],

    'entities' => [
        'directories' => [
            app_path('Models'),
        ],
        'namespace' => env('CYCLE_ENTITY_NAMESPACE', 'App\\Models')
    ],

    'schema' => [
        'cache' => (bool) env('CYCLE_SCHEMA_CACHE', true),
        'cache_key' => env('CYCLE_CACHE_KEY', 'cycle_schema'),
        'auto_sync' => (bool) env('CYCLE_AUTO_SYNC', false),
        'strict' => (bool) env('CYCLE_SCHEMA_STRICT', false),
    ],

    'migrations' => [
        'directory' => env('CYCLE_MIGRATIONS_PATH', database_path('migrations')),
        'table' => env('CYCLE_MIGRATIONS_TABLE', 'cycle_migrations'),
        'safe' => (bool) env('CYCLE_SAFE_MIGRATIONS', true),
    ],

    'performance' => [
        'query_cache' => (bool) env('CYCLE_QUERY_CACHE', false),
        'lazy_loading' => (bool) env('CYCLE_LAZY_LOADING', true),
        'preload_relations' => (bool) env('CYCLE_PRELOAD_RELATIONS', false),
    ],

    'development' => [
        'log_queries' => (bool) env('CYCLE_LOG_QUERIES', false),
        'slow_query_threshold' => (int) env('CYCLE_SLOW_QUERY_MS', 100),
        'debug_mode' => (bool) env('CYCLE_DEBUG', false),
        'profile_queries' => (bool) env('CYCLE_PROFILE_QUERIES', false),
    ]
];
PHP;
  }

  private function getUserEntityContent(): string
  {
    return <<<'PHP'
<?php

namespace App\Models;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;

#[Entity(table: 'users')]
class User
{
    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'string', length: 255)]
    public string $name;

    #[Column(type: 'string', length: 255, unique: true)]
    public string $email;

    #[Column(type: 'string', length: 255)]
    public string $password;

    #[Column(type: 'boolean', default: true)]
    public bool $active = true;

    #[Column(type: 'datetime')]
    public \DateTimeInterface $createdAt;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
        $this->touch();
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
        $this->touch();
    }

    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_DEFAULT);
        $this->touch();
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function activate(): void
    {
        $this->active = true;
        $this->touch();
    }

    public function deactivate(): void
    {
        $this->active = false;
        $this->touch();
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
PHP;
  }
}

// Executar instalador se chamado diretamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
  $installer = new ExpressCycleInstaller();
  exit($installer->install());
}
