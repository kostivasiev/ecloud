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
        \App\Console\Commands\Dev\CreateExampleTask::class,
        \App\Console\Commands\Artisan\TestAuth::class,
        \App\Console\Commands\Host\Delete::class,
        \App\Console\Commands\Billing\ProductCreate::class,
        \App\Console\Commands\Image\Populate::class,
        \App\Console\Commands\Orchestrator\ScheduledDeploy::class,
        \App\Console\Commands\FloatingIp\SetPolymorphicRelationship::class,
        \App\Console\Commands\FloatingIp\PopulateForIpRange::class,
        \App\Console\Commands\FloatingIp\PopulateAvailabilityZoneId::class,
        \App\Console\Commands\Health\FindOrphanedNats::class,
        \App\Console\Commands\Health\FindOrphanedNics::class,
        \App\Console\Commands\Task\TimeoutStuck::class,
        \App\Console\Commands\IpAddress\PopulateNetworkId::class,
        \App\Console\Commands\Billing\CleanupAdvancedNetworking::class,
        \App\Console\Commands\Billing\FixPriceOnAdvancedNetworkingBillingMetrics::class,
        \App\Console\Commands\Billing\SetFriendlyNames::class,
        \App\Console\Commands\VPC\ChangeOwnership::class,
        \App\Console\Commands\Credentials\UpdatePleskCredentials::class,
        \App\Console\Commands\VPC\FixBillingEndDates::class,
        \App\Console\Commands\Router\RemoveBlockAllOutbound::class,
        \App\Console\Commands\Rules\ModifyEdRules::class,
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
            $schedule->command('vpc:process-billing --debug')
                ->monthlyOn(1, '01:00')
                ->emailOutputTo(config('alerts.billing.to'));
                
            $schedule->command('orchestrator:deploy')
                ->everyMinute();

            $schedule->command('health:find-orphaned-nats')
                ->dailyAt("09:00")
                ->emailOutputOnFailure(config('alerts.health.to'));

            $schedule->command('health:find-orphaned-nics')
                ->dailyAt("09:00")
                ->emailOutputOnFailure(config('alerts.health.to'));
        }

        $schedule->command('task:timeout-stuck --hours=12')
            ->hourly();
    }
}
