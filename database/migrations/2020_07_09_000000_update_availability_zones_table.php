<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAvailabilityZonesTable extends Migration
{
    public function up()
    {
        Schema::connection('ecloud')->table('availability_zones', function (Blueprint $table) {
            $table->string('nsx_manager_endpoint')->nullable();
        });
    }

    public function down()
    {
        Schema::connection('ecloud')->table('availability_zones', function (Blueprint $table) {
            $table->dropColumn('nsx_manager_endpoint');
        });
    }
}
