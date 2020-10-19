<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddUUIDColumnToFailedJobsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::connection('ecloud')->create('failed_jobs', function (Blueprint $table) {
            $table->string('uuid')->after('id')->nullable()->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::connection('ecloud')->table('failed_jobs', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
}
