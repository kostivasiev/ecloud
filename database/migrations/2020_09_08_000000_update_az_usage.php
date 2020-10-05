<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class UpdateAzUsage extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('routers', function ($table) {
            $table->uuid('availability_zone_id')->default('');
        });
        Schema::connection('ecloud')->table('dhcps', function ($table) {
            $table->uuid('availability_zone_id')->default('');
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->table('routers', function ($table) {
            $table->dropColumn('availability_zone_id');
        });
        Schema::connection('ecloud')->table('dhcps', function ($table) {
            $table->dropColumn('availability_zone_id');
        });
    }
}
