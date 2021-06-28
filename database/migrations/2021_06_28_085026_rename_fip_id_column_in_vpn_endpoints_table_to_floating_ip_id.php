<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameFipIdColumnInVpnEndpointsTableToFloatingIpId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('vpn_endpoints', function (Blueprint $table) {
            $table->renameColumn('fip_id', 'floating_ip_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vpn_endpoints', function (Blueprint $table) {
            $table->renameColumn('floating_ip_id', 'fip_id');
        });
    }
}
