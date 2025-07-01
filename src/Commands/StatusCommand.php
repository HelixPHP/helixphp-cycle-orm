<?php

namespace CAFernandes\ExpressPHP\CycleORM\Commands;

class StatusCommand extends BaseCommand
{
    public function handle(): int
    {
        $this->info('Cycle ORM Status Check');
        $this->line('========================');

        try {
            if (function_exists('app')) {
                $app = app();
                $health = \CAFernandes\ExpressPHP\CycleORM\Health\CycleHealthCheck::check($app);

                $this->displayHealthStatus($health);

                return $health['cycle_orm'] === 'healthy' ? 0 : 1;
            } else {
                $this->error('Application container not available');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('Status check failed: ' . $e->getMessage());
            return 1;
        }
    }

  /**
   * Exibe o status de saúde do Cycle ORM.
   * @param array<string, mixed> $health
   */
    private function displayHealthStatus(array $health): void
    {
        $status = '';
        if (isset($health['cycle_orm']) && is_string($health['cycle_orm'])) {
            $status = $health['cycle_orm'];
        }
        $icon = $status === 'healthy' ? '✅' : '❌';

        $responseTime = isset($health['response_time_ms']) &&
            (is_string($health['response_time_ms']) || is_numeric($health['response_time_ms']))
            ? (string)$health['response_time_ms']
            : '';
        $this->line("{$icon} Overall Status: {$status}");
        $this->line("Response Time: {$responseTime}ms");
        $this->line('');

        if (isset($health['checks']) && is_array($health['checks'])) {
            foreach ($health['checks'] as $checkName => $check) {
                if (
                    !is_string($checkName) ||
                    !is_array($check) ||
                    !isset($check['status']) ||
                    !is_string($check['status'])
                ) {
                    continue;
                }
                $checkStatus = $check['status'];
                $checkIcon = $checkStatus === 'healthy' ? '✅' : '❌';
                $this->line("{$checkIcon} {$checkName}: {$checkStatus}");

                if (isset($check['error']) && is_string($check['error'])) {
                    $this->error("  Error: {$check['error']}");
                }
            }
        }
    }
}
