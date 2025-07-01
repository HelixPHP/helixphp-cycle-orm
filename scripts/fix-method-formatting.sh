#!/bin/bash
# scripts/fix-method-formatting.sh

echo "🔧 Corrigindo formatação de métodos..."

# Usar PHP-CS-Fixer para formatação avançada
composer global require friendsofphp/php-cs-fixer

# Configuração personalizada
cat > .php-cs-fixer.php << 'EOF'
<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__ . '/src')
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
        ],
        'function_declaration' => [
            'closure_function_spacing' => 'one',
        ],
    ])
    ->setFinder($finder);
EOF

# Executar correção
echo "php-cs-fixer fix --config=.php-cs-fixer.php --dry-run --diff"
php-cs-fixer fix --config=.php-cs-fixer.php --dry-run --diff

echo "✅ Formatação de métodos corrigida!"
