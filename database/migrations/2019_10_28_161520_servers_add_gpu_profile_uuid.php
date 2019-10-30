<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
