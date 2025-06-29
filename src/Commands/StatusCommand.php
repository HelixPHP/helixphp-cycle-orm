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

    private function displayHealthStatus(array $health): void
    {
        $status = $health['cycle_orm'];
        $icon = $status === 'healthy' ? '✅' : '❌';

        $this->line("{$icon} Overall Status: {$status}");
        $this->line("Response Time: {$health['response_time_ms']}ms");
        $this->line('');

        foreach ($health['checks'] as $checkName => $check) {
            $checkIcon = $check['status'] === 'healthy' ? '✅' : '❌';
            $this->line("{$checkIcon} {$checkName}: {$check['status']}");

            if (isset($check['error'])) {
                $this->error("  Error: {$check['error']}");
            }
        }
    }
}