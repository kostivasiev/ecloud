<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVpnServiceAndVpnProfileGroupIdColumnsToVpnSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('vpn_sessions', function (Blueprint $table) {
            $table->uuid('vpn_profile_group_id')->index()->after('name');
            $table->uuid('vpn_service_id')->index()->after('vpn_profile_group_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->table('vpn_sessions', function (Blueprint $table) {
            $table->dropColumn(['vpn_service_id']);
        });
    }
}
