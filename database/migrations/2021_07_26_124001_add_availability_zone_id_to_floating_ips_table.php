<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAvailabilityZoneIdToFloatingIpsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('floating_ips', function (Blueprint $table) {
            $table->uuid('availability_zone_id')->after('vpc_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('floating_ips', function (Blueprint $table) {
            $table->dropColumn('availability_zone_id');
        });
    }
}
