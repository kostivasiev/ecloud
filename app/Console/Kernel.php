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
        \App\Console\Commands\Kingpin\TestAuth::class,
        \App\Console\Commands\Kingpin\Instance\Delete::class,
        \App\Console\Commands\Job\TestJob::class,
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
        //
    }
}
