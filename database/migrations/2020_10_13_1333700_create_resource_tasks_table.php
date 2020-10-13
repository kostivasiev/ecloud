<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateResourceTaskStatusesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::connection('ecloud')->create('resource_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('job_id')->index()->nullable();
            $table->string('type')->index();
            $table->string('queue')->index()->nullable();
            $table->integer('attempts')->default(0);
            $table->integer('progress_now')->default(0);
            $table->integer('progress_max')->default(0);
            $table->string('status', 16)->default(\App\Models\V2\ResourceTask::STATUS_QUEUED)->index();
            $table->longText('input')->nullable();
            $table->longText('output')->nullable();
            $table->timestamps();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::connection('ecloud')->drop('resource_tasks');
    }
}
