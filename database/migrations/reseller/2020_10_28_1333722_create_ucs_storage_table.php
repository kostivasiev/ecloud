<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class CreateUcsStorageTable extends Migration
{
    /**
     * Adds appliance_is_public column to appliance table
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ucs_storage', function ($table) {
            $table->increments('id');
            $table->string('ucs_datacentre_id');
            $table->string('server_id');
            $table->string('qos_enabled');
        });
    }

    public function down()
    {
        Schema::dropIfExists('ucs_storage');
    }
}
