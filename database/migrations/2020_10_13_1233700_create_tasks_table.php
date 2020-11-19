<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::connection('ecloud')->create('tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('resource_id')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::connection('ecloud')->drop('tasks');
    }
}
