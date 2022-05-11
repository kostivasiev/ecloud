<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class PopulateFailedJobsTableUuids extends Migration
{
    public function up()
    {
        DB::connection('ecloud')->table('failed_jobs')->whereNull('uuid')->cursor()->each(function ($job) {
            DB::connection('ecloud')->table('failed_jobs')
                ->where('id', $job->id)
                ->update(['uuid' => (string) Illuminate\Support\Str::uuid()]);
        });
    }
}
