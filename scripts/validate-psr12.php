#!/usr/bin/env php
<?php
/**
 * Script de Validação PSR-12 Completa
 * HelixPHP Framework
 */

require_once __DIR__ . '/../vendor/autoload.php';

class PSR12Validator
{
    private array $errors = [];
    private array $warnings = [];
    private int $filesChecked = 0;

    public function validate(): void
    {
        echo "🔍 Iniciando validação PSR-12 completa...\n\n";

        $this->validateCodeStyle();
        $this->validateDocBlocks();
        $this->validateLineLength();
        $this->validateMethodFormatting();

        $this->generateReport();
    }

    private function validateCodeStyle(): void
    {
        echo "📋 Validando estilo de código...\n";

        $output = [];
        $return = 0;

        exec('composer run cs:check 2>&1', $output, $return);

        if ($return !== 0) {
            $this->errors[] = 'Violações de PSR-12 detectadas no código';
            echo "❌ Erros de estilo encontrados\n";
            foreach ($output as $line) {
                echo "   $line\n";
            }
        } else {
            echo "✅ Estilo de código conforme\n";
        }
    }

    private function validateDocBlocks(): void
    {
        echo "\n📖 Validando DocBlocks...\n";

        $files = $this->getPhpFiles();
        $docBlockIssues = 0;

        foreach ($files as $file) {
            $content = file_get_contents($file);

            // Verificar DocBlocks mal formatados
            if (preg_match_all('/\/\*\*[\s\S]*?\*\//', $content, $matches)) {
                foreach ($matches[0] as $docBlock) {
                    // Verificar alinhamento
                    if (!$this->isDocBlockWellFormatted($docBlock)) {
                        $docBlockIssues++;
                    }
                }
            }

            $this->filesChecked++;
        }

        if ($docBlockIssues > 0) {
            $this->warnings[] = "DocBlocks mal formatados: $docBlockIssues";
            echo "⚠️  $docBlockIssues DocBlocks precisam de formatação\n";
        } else {
            echo "✅ DocBlocks bem formatados\n";
        }
    }

    private function validateLineLength(): void
    {
        echo "\n📏 Validando comprimento de linhas...\n";

        $files = $this->getPhpFiles();
        $longLines = 0;

        foreach ($files as $file) {
            $lines = file($file);

            foreach ($lines as $lineNum => $line) {
                $length = strlen(rtrim($line));
                if ($length > 120) {
                    $longLines++;

                    if ($longLines <= 5) { // Mostrar apenas as primeiras 5
                        $actualLineNum = $lineNum + 1;
                        echo "   $file:$actualLineNum ($length chars)\n";
                    }
                }
            }
        }

        if ($longLines > 0) {
            $this->warnings[] = "Linhas longas encontradas: $longLines";
            echo "⚠️  $longLines linhas excedem 120 caracteres\n";
        } else {
            echo "✅ Comprimento de linhas conforme\n";
        }
    }

    private function validateMethodFormatting(): void
    {
        echo "\n🔧 Validando formatação de métodos...\n";

        $files = $this->getPhpFiles();
        $methodIssues = 0;

        foreach ($files as $file) {
            $content = file_get_contents($file);

            // Procurar métodos com muitos parâmetros em uma linha
            $pattern = '/public|private|protected.*function\s+\w+\([^)]{80,}\)/';
            if (preg_match_all($pattern, $content, $matches)) {
                $methodIssues += count($matches[0]);
            }
        }

        if ($methodIssues > 0) {
            $this->warnings[] = "Métodos com formatação subótima: $methodIssues";
            echo "⚠️  $methodIssues métodos precisam de quebra de linha\n";
        } else {
            echo "✅ Formatação de métodos conforme\n";
        }
    }

    private function getPhpFiles(): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(__DIR__ . '/../src')
        );

        $phpFiles = [];
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }

        return $phpFiles;
    }

    private function isDocBlockWellFormatted(string $docBlock): bool
    {
        $lines = explode("\n", $docBlock);

        // Verificar alinhamento básico
        foreach ($lines as $line) {
            $trimmed = ltrim($line);
            if (str_starts_with($trimmed, '*') && !str_starts_with($trimmed, '*/')) {
                if (!str_starts_with($trimmed, '* ') && $trimmed !== '*') {
                    return false;
                }
            }
        }

        return true;
    }

    private function generateReport(): void
    {
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "📊 RELATÓRIO FINAL DE VALIDAÇÃO PSR-12\n";
        echo str_repeat("=", 60) . "\n\n";

        echo "📁 Arquivos verificados: {$this->filesChecked}\n";
        echo "❌ Erros críticos: " . count($this->errors) . "\n";
        echo "⚠️  Avisos: " . count($this->warnings) . "\n\n";

        if (!empty($this->errors)) {
            echo "🚨 ERROS CRÍTICOS:\n";
            foreach ($this->errors as $error) {
                echo "   • $error\n";
            }
            echo "\n";
        }

        if (!empty($this->warnings)) {
            echo "⚠️  AVISOS:\n";
            foreach ($this->warnings as $warning) {
                echo "   • $warning\n";
            }
            echo "\n";
        }

        $score = $this->calculateScore();
        echo "🎯 SCORE PSR-12: $score/10\n\n";

        if ($score >= 9.5) {
            echo "🏆 EXCELÊNCIA ALCANÇADA! Parabéns!\n";
        } elseif ($score >= 8.5) {
            echo "🎉 ALTA CONFORMIDADE! Poucos ajustes necessários.\n";
        } else {
            echo "🔧 MELHORIAS NECESSÁRIAS. Veja os erros acima.\n";
        }

        // Salvar relatório
        $reportFile = __DIR__ . '/../reports/psr12-validation-' . date('Y-m-d-H-i-s') . '.txt';
        if (!is_dir(dirname($reportFile))) {
            mkdir(dirname($reportFile), 0755, true);
        }

        ob_start();
        $this->generateReport();
        $report = ob_get_clean();
        file_put_contents($reportFile, $report);

        echo "\n📄 Relatório salvo em: $reportFile\n";
    }

    private function calculateScore(): float
    {
        $baseScore = 10.0;

        // Deduzir por erros críticos
        $baseScore -= count($this->errors) * 2.0;

        // Deduzir por avisos
        $baseScore -= count($this->warnings) * 0.5;

        return max(0, min(10, $baseScore));
    }
}

// Executar validação
$validator = new PSR12Validator();
$validator->validate();
