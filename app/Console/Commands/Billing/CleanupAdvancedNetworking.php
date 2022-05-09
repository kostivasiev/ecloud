<?php

namespace App\Console\Commands\Billing;

use App\Models\V2\Vpc;
use App\Console\Commands\Command;

class CleanupAdvancedNetworking extends Command
{
    protected $signature = 'billing:advanced-networking-cleanup {--T|test-run}';

    protected $description = 'Cleans up advanced networking billing metrics';

    public function handle()
    {
        $this->info('Cleaning up Advanced Networking Billing Metrics' . PHP_EOL);
        Vpc::where('advanced_networking', '=', false)->each(function ($vpc) {
            $vpc->billingMetrics()->where('key', '=', 'networking.advanced')->each(function ($billingMetric) use ($vpc) {
                $this->info(
                    $billingMetric->id . ' is for ' . $billingMetric->key .
                    ' but ' . $vpc->id . ' does not have advanced networking enabled.'
                );
                if (!$this->option('test-run')) {
                    $billingMetric->delete();
                }
                $this->info($billingMetric->id . ' deleted.' . PHP_EOL);
            });
        });
        return Command::SUCCESS;
    }
}
