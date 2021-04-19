<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateFailedJobsTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('failed_jobs', function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->nullable()->unique();
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->table('failed_jobs', function ($table) {
            $table->dropColumn('uuid');
        });
    }
}
