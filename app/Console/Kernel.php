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
        \App\Console\Commands\Nsx\TestAuth::class,
        \App\Console\Commands\Nsx\UndeployDeletedNetworks::class,
        \App\Console\Commands\Nsx\UndeployDeletedDhcps::class,
        \App\Console\Commands\Nsx\UndeployDeletedRouters::class,
        \App\Console\Commands\Kingpin\TestAuth::class,
        \App\Console\Commands\Kingpin\Instance\Delete::class,
        \App\Console\Commands\Queue\TestRead::class,
        \App\Console\Commands\Credentials\Show::class,
        \App\Console\Commands\VPC\ProcessBilling::class,
        \App\Console\Commands\Billing\RouterThroughput\ProductPopulation::class,
        \App\Console\Commands\Router\SetDefaultBilling::class,
        \App\Console\Commands\Conjurer\TestAuth::class,
        \App\Console\Commands\Queue\PopulateFailedJobsUuids::class,
        \App\Console\Commands\Artisan\TestAuth::class,
        \App\Console\Commands\Host\Delete::class,
        \App\Console\Commands\Billing\ProductCreate::class,
        \App\Console\Commands\Image\Populate::class,
        \App\Console\Commands\Orchestrator\ScheduledDeploy::class,
        \App\Console\Commands\FloatingIp\SetPolymorphicRelationship::class,
        \App\Console\Commands\FloatingIp\PopulateAvailabilityZoneId::class,
        \App\Console\Commands\Router\CreateTestTask::class,
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
        if ($this->app->environment() == 'production') {
            $schedule->command('vpc:process-billing')
                ->monthlyOn(1, '01:00')
                ->emailOutputTo(config('alerts.billing.to'));
            $schedule->command('orchestrator:deploy')
                ->everyMinute();
        }
    }
}
