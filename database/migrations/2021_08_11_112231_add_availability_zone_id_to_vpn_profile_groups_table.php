<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAvailabilityZoneIdToVpnProfileGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('vpn_profile_groups', function (Blueprint $table) {
            $table->uuid('availability_zone_id')->nullable()->after('description')->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('vpn_profile_groups', function (Blueprint $table) {
            $table->dropColumn(['availability_zone_id']);
        });
    }
}
