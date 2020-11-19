<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class UpdateAzUsage2 extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('networks', function ($table) {
            $table->dropColumn('availability_zone_id');
        });
        Schema::connection('ecloud')->table('vpns', function ($table) {
            $table->dropColumn('availability_zone_id');
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->table('networks', function ($table) {
            $table->uuid('availability_zone_id')->default('');
        });
        Schema::connection('ecloud')->table('vpns', function ($table) {
            $table->uuid('availability_zone_id')->default('');
        });
    }
}
