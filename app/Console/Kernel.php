<?php

namespace App\Console;

use App\Console\Commands\FastDesk\BackfillVpn;
use App\Console\Commands\Instance\SetHostGroupToStandard;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        \App\Console\Commands\Nsx\TestAuth::class,
        \App\Console\Commands\Kingpin\TestAuth::class,
        \App\Console\Commands\Credentials\Show::class,
        \App\Console\Commands\VPC\ProcessBilling::class,
        \App\Console\Commands\Artisan\TestAuth::class,
        \App\Console\Commands\Billing\ProductCreate::class,
        \App\Console\Commands\Conjurer\TestAuth::class,
        \App\Console\Commands\Credentials\Show::class,
        \App\Console\Commands\DiscountPlan\SendReminderEmails::class,
        \App\Console\Commands\FastDesk\BackfillVpn::class,
        \App\Console\Commands\FloatingIp\PopulateForIpRange::class,
        \App\Console\Commands\Health\FindOrphanedNats::class,
        \App\Console\Commands\Health\FindOrphanedNics::class,
        \App\Console\Commands\Image\Populate::class,
        \App\Console\Commands\Instance\SetHostGroupToStandard::class,
        \App\Console\Commands\Kingpin\TestAuth::class,
        \App\Console\Commands\Make\MakeTaskJob::class,
        \App\Console\Commands\Make\MakeTest::class,
        \App\Console\Commands\Nsx\TestAuth::class,
        \App\Console\Commands\Orchestrator\ScheduledDeploy::class,
        \App\Console\Commands\Task\TimeoutStuck::class,
        \App\Console\Commands\VPC\ChangeOwnership::class,
        \App\Console\Commands\VPC\FixBillingEndDates::class,
        \App\Console\Commands\VPC\ProcessBilling::class,
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
            $schedule->command('vpc:process-billing --debug --force')
                ->monthlyOn(1, '01:00')
                ->emailOutputTo(config('alerts.billing.to'));

            $schedule->command('orchestrator:deploy --force')
                ->everyMinute();

            $schedule->command('health:find-orphaned-nats --force')
                ->dailyAt("09:00")
                ->emailOutputOnFailure(config('alerts.health.to'));

            $schedule->command('health:find-orphaned-nics --force')
                ->dailyAt("09:00")
                ->emailOutputOnFailure(config('alerts.health.to'));

            $schedule->command('discount-plan:send-reminder-emails --force')
                ->dailyAt("08:50")
                ->emailOutputOnFailure(config('alerts.billing.to'));
        }

        $schedule->command('task:timeout-stuck --hours=12 --force')
            ->hourly();
    }
}
