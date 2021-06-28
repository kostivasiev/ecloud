<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVpnProfileGroupIdColumnToVpnSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('vpn_sessions', function (Blueprint $table) {
            $table->uuid('vpn_profile_group_id')->index()->after('name')->nullable();
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
            $table->dropColumn(['vpn_profile_group_id']);
        });
    }
}
