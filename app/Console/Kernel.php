<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Laravel\Lumen\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\Nsx\TestAuth::class,
        Commands\Nsx\UndeployDeletedNetworks::class,
        Commands\Nsx\UndeployDeletedRouters::class,
        Commands\Kingpin\TestAuth::class,
        Commands\Kingpin\Instance\Delete::class,
        Commands\Queue\TestRead::class,
        Commands\Credentials\Show::class,
        Commands\VPC\ProcessBilling::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return                  void
     * @codeCoverageIgnore
     * @SuppressWarnings(PHPMD)
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('vpc:process-billing')->monthlyOn(1, '01:00')->emailOutputTo(config('alerts.billing.to'));
    }
}
