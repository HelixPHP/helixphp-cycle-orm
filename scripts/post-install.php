<?php

/**
 * Script executado automaticamente após instalação via Composer
 */

class PostInstallScript
{
    public static function run(): void
    {
        if (!self::isInteractive()) {
            return;
        }

        echo "\n🎉 PivotPHP Cycle ORM Extension installed successfully!\n\n";

        $shouldInstall = self::askYesNo("Would you like to run the setup wizard? (y/N)");

        if ($shouldInstall) {
            $installerPath = __DIR__ . '/../install.php';
            if (file_exists($installerPath)) {
                require_once $installerPath;
                $installer = new ExpressCycleInstaller();
                $installer->install();
            } else {
                echo "❌ Installer not found. Please run manually:\n";
                echo "   php vendor/cafernandes/express-php-cycle-orm-extension/install.php\n";
            }
        } else {
            echo "📚 To setup manually later, run:\n";
            echo "   php vendor/cafernandes/express-php-cycle-orm-extension/install.php\n\n";
            echo "📖 Documentation: https://github.com/CAFernandes/express-php-cycle-orm-extension\n";
        }
    }

    private static function isInteractive(): bool
    {
        return php_sapi_name() === 'cli' &&
               function_exists('posix_isatty') &&
               posix_isatty(STDOUT);
    }

    private static function askYesNo(string $question): bool
    {
        echo $question . " ";
        $handle = fopen('php://stdin', 'r');
        $answer = trim(fgets($handle));
        fclose($handle);

        return in_array(strtolower($answer), ['y', 'yes']);
    }
}

PostInstallScript::run();
