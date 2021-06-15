<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVpnEndpointVpnSessionTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('ecloud')->create('vpn_endpoint_vpn_session', function (Blueprint $table) {
            $table->uuid('vpn_endpoint_id')->index();
            $table->uuid('vpn_session_id')->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('ecloud')->dropIfExists('vpn_endpoint_vpn_session');
    }
}
