<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AvailabilityZonesAddRegionId extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('availability_zones', function ($table) {
            $table->uuid('region_id')->default('');
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->table('availability_zones', function ($table) {
            $table->dropColumn('region_id');
        });
    }
}
