<?php

namespace App\Console\Commands\Queue;

use App\Console\Commands\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PopulateFailedJobsUuids extends Command
{
    protected $signature = 'queue:populate-failed-jobs-uuids';

    protected $description = 'Populate failed jobs uuuids';

    public function handle()
    {
        DB::connection('ecloud')->table('failed_jobs')->whereNull('uuid')->cursor()->each(function ($job) {
            DB::connection('ecloud')->table('failed_jobs')
                ->where('id', $job->id)
                ->update(['uuid' => (string) Str::uuid()]);
        });

        return Command::SUCCESS;
    }
}
