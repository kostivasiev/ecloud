<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class GpuProfileTable extends Migration
{
    /**
     * Adds appliance_is_public column to appliance table
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->create('gpu_profile', function ($table) {
            $table->increments('id');
            $table->string('uuid');
            $table->string('name');
            $table->string('profile_name');
            $table->string('card_type');
            $table->timestamp('created_at');
            $table->timestamp('updated_at');
            $table->timestamp('deleted_at')->nullable();
        });

        Schema::connection('ecloud')->create('gpu_profile_pod_availability', function ($table) {
            $table->increments('id');
            $table->integer('gpu_profile_id')->index();
            $table->integer('ucs_datacentre_id');
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->dropIfExists('gpu_profile');
        Schema::connection('ecloud')->dropIfExists('gpu_profile_pod_availability');
    }
}
