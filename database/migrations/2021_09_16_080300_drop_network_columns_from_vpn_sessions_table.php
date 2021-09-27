<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropNetworkColumnsFromVpnSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->table('vpn_sessions', function (Blueprint $table) {
            $table->dropColumn(['local_networks', 'remote_networks']);
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
            $table->text('remote_networks')->nullable();
            $table->text('local_networks')->nullable();
        });
    }
}
