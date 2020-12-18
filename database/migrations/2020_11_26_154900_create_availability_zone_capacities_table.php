<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAvailabilityZoneCapacitiesTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->create('availability_zone_capacities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('availability_zone_id');
            $table->string('type');
            $table->float('current')->nullable();
            $table->integer('alert_warning')->nullable();
            $table->integer('alert_critical')->nullable();
            $table->integer('max')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->dropIfExists('availability_zone_capacities');
    }
}
