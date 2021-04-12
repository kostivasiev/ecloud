<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAvailabilityZoneHostSpecTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->create('availability_zone_host_spec', function (Blueprint $table) {
            $table->uuid('availability_zone_id');
            $table->uuid('host_spec_id');
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->dropIfExists('availability_zone_host_spec');
    }
}
