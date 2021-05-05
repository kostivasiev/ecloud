<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class ServersAddGpuProfileUuid extends Migration
{
    /**
     * Adds appliance_is_public column to appliance table
     *
     * @return void
     */
    public function up()
    {
        Schema::table('servers', function ($table) {
            $table->string('servers_ecloud_gpu_profile_uuid')->nullable();
        });
    }
}
