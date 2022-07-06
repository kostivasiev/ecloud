<?php

namespace App\Console\Commands\FastDesk;

use App\Console\Commands\Command;
use Database\Seeders\FastDesk\FastDeskSeeder;
use Illuminate\Support\Facades\Artisan;

class BackfillVpn extends Command
{
    protected $signature = 'fast-desk:backfill-vpn {--test-run}';
    protected $description = 'Backfills the VPNs for FastDesk';

    public function handle()
    {
        if (!$this->option('test-run')) {
            Artisan::call('db:seed', [
                '--class' => FastDeskSeeder::class
            ]);
        }
        $this->info('Database seeding completed successfully.');
    }
}