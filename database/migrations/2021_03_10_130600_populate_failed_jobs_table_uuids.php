<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class PopulateFailedJobsTableTableUuids extends Migration
{
    public function up()
    {
        DB::table('failed_jobs')->whereNull('uuid')->cursor()->each(function ($job) {
            DB::table('failed_jobs')
                ->where('id', $job->id)
                ->update(['uuid' => (string) Illuminate\Support\Str::uuid()]);
        });
    }
}
